<?php
declare(strict_types=1);

namespace App\Core;

final class Auth
{
    // Создать токен авторизации.
    public static function issue(int $userId): string
    {
        $db = Database::getConnection();
        $config = require BASE_PATH . '/config/app.php';

        $token = bin2hex(random_bytes(32));

        $stmt = $db->prepare(
            "INSERT INTO auth_tokens (
                token_hash, user_id, expires_at
            ) VALUES (
                :token_hash,
                :user_id,
                NOW() + CAST(:ttl AS integer) * INTERVAL '1 second'
            )"
        );

        $stmt->execute([
            'token_hash' => hash('sha256', $token),
            'user_id' => $userId,
            'ttl' => $config['token_ttl'],
        ]);

        return $token;
    }

    // Получить пользователя по действующему токену.
    public static function user(string $token): ?array
    {
        $db = Database::getConnection();

        $stmt = $db->prepare(
            'SELECT
                u.id,
                u.full_name AS name,
                u.email,
                u.role
            FROM auth_tokens t
            JOIN users u ON u.id = t.user_id
            WHERE t.token_hash = :token_hash
              AND t.expires_at > NOW()'
        );

        $stmt->execute([
            'token_hash' => hash('sha256', $token),
        ]);

        return $stmt->fetch() ?: null;
    }

    // Удалить токен при выходе из системы.
    public static function revoke(string $token): void
    {
        $db = Database::getConnection();

        $stmt = $db->prepare(
            'DELETE FROM auth_tokens
             WHERE token_hash = :token_hash'
        );

        $stmt->execute([
            'token_hash' => hash('sha256', $token),
        ]);
    }
}