<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class UserRepository
{
    private PDO $db;

    private const FIELDS = '
        id, full_name AS name, email, role
    ';

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    // Получить всех пользователей.
    public function all(): array
    {
        return $this->db->query(
            'SELECT ' . self::FIELDS .
            ' FROM users ORDER BY id'
        )->fetchAll();
    }

    // Найти пользователя по ID.
    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT ' . self::FIELDS .
            ' FROM users WHERE id = :id'
        );

        $stmt->execute(['id' => $id]);

        return $stmt->fetch() ?: null;
    }

    // Найти пользователя для авторизации.
    public function findByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT ' . self::FIELDS . ', password_hash
             FROM users WHERE email = :email'
        );

        $stmt->execute(['email' => $email]);

        return $stmt->fetch() ?: null;
    }

    // Создать пользователя.
    public function add(array $data): array
    {
        $stmt = $this->db->prepare(
            'INSERT INTO users (
                full_name, email, password_hash, role
            ) VALUES (
                :name, :email, :password_hash, :role
            ) RETURNING ' . self::FIELDS
        );

        $stmt->execute([
            'name' => $data['name'],
            'email' => $data['email'],
            'password_hash' => password_hash(
                $data['password'],
                PASSWORD_DEFAULT
            ),
            'role' => 'user',
        ]);

        return $stmt->fetch();
    }

    // Изменить данные пользователя.
    public function update(int $id, array $data): ?array
    {
        $stmt = $this->db->prepare(
            'UPDATE users SET
                full_name = :name,
                email = :email
            WHERE id = :id
            RETURNING ' . self::FIELDS
        );

        $stmt->execute([
            'id' => $id,
            'name' => $data['name'],
            'email' => $data['email'],
        ]);

        return $stmt->fetch() ?: null;
    }

    // Удалить пользователя.
    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare(
            'DELETE FROM users WHERE id = :id'
        );

        $stmt->execute(['id' => $id]);

        return $stmt->rowCount() > 0;
    }
}