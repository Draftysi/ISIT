<?php
declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));
define('APP_PATH', BASE_PATH . '/app');

require APP_PATH . '/autoload.php';

use App\Core\Database;

$config = require BASE_PATH . '/config/app.php';
$dbConfig = $config['db'];

// Защита от случайного сброса локальной базы.
if (
    $dbConfig['host'] !== '127.0.0.1' ||
    (int) $dbConfig['port'] !== 5433 ||
    $dbConfig['dbname'] !== 'room_booking_lab9'
) {
    exit("Reset is allowed only for the Docker database.\n");
}

try {
    $db = Database::getConnection();
    $db->beginTransaction();

    // Удаление старых таблиц.
    $db->exec('
        DROP TABLE IF EXISTS auth_tokens;
        DROP TABLE IF EXISTS bookings;
        DROP TABLE IF EXISTS room_equipment;
        DROP TABLE IF EXISTS equipment;
        DROP TABLE IF EXISTS rooms;
        DROP TABLE IF EXISTS user_profiles;
        DROP TABLE IF EXISTS users;
    ');

    // Создание таблиц и заполнение данными.
    $files = [
        '01_schema.sql',
        '02_seed.sql',
        '07_auth.sql',
    ];

    foreach ($files as $file) {
        $sql = file_get_contents(BASE_PATH . '/sql/' . $file);

        if ($sql === false) {
            throw new RuntimeException("Cannot read " . $file);
        }

        $db->exec($sql);
        echo "[OK] " . $file . PHP_EOL;
    }

    $db->commit();

    echo "Database reset successfully." . PHP_EOL;

} catch (Throwable $error) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }

    echo "Error: " . $error->getMessage() . PHP_EOL;
    exit(1);
}