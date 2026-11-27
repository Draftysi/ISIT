<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class BookingRepository
{
    private PDO $db;

    private const FIELDS = "
        id,
        room_id,
        user_id,
        subject,
        to_char(starts_at, 'YYYY-MM-DD') AS date,
        to_char(starts_at, 'HH24:MI') AS start_time,
        to_char(ends_at, 'HH24:MI') AS end_time,
        participants,
        status
    ";

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    // Получить все бронирования.
    public function all(): array
    {
        return $this->db->query(
            'SELECT ' . self::FIELDS .
            ' FROM bookings ORDER BY id'
        )->fetchAll();
    }

        // Поиск бронирований по теме встречи.
    public function search(string $query): array
    {
        $stmt = $this->db->prepare(
            'SELECT ' . self::FIELDS .
            " FROM bookings
              WHERE subject ILIKE :q ESCAPE '!'
              ORDER BY id"
        );

        $query = strtr($query, [
            '!' => '!!',
            '%' => '!%',
            '_' => '!_'
        ]);

        $stmt->execute(['q' => '%' . $query . '%']);

        return $stmt->fetchAll();
    }

    // Найти бронирование по ID.
    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT ' . self::FIELDS .
            ' FROM bookings WHERE id = :id'
        );

        $stmt->execute(['id' => $id]);

        return $stmt->fetch() ?: null;
    }

    // Получить бронирования выбранной комнаты.
    public function byRoom(int $roomId): array
    {
        $stmt = $this->db->prepare(
            'SELECT ' . self::FIELDS .
            ' FROM bookings WHERE room_id = :room_id
              ORDER BY starts_at'
        );

        $stmt->execute(['room_id' => $roomId]);

        return $stmt->fetchAll();
    }

    // Создать бронирование.
    public function add(array $data): array
    {
        $stmt = $this->db->prepare(
            'INSERT INTO bookings (
                room_id, user_id, subject,
                starts_at, ends_at, participants, status
            ) VALUES (
                :room_id, :user_id, :subject,
                :starts_at, :ends_at, :participants, :status
            ) RETURNING ' . self::FIELDS
        );

        $stmt->execute($this->parameters($data));

        return $stmt->fetch();
    }

    // Изменить бронирование.
    public function update(int $id, array $data): ?array
    {
        $existing = $this->find($id);

        if ($existing === null) {
            return null;
        }

        $data = array_replace($existing, $data);

        $stmt = $this->db->prepare(
            'UPDATE bookings SET
                room_id = :room_id,
                user_id = :user_id,
                subject = :subject,
                starts_at = :starts_at,
                ends_at = :ends_at,
                participants = :participants,
                status = :status
            WHERE id = :id
            RETURNING ' . self::FIELDS
        );

        $params = $this->parameters($data);
        $params['id'] = $id;

        $stmt->execute($params);

        return $stmt->fetch() ?: null;
    }

    // Удалить бронирование.
    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare(
            'DELETE FROM bookings WHERE id = :id'
        );

        $stmt->execute(['id' => $id]);

        return $stmt->rowCount() > 0;
    }

    // Преобразовать данные API в параметры SQL.
    private function parameters(array $data): array
    {
        return [
            'room_id' => $data['room_id'],
            'user_id' => $data['user_id'] ?? null,
            'subject' => $data['subject'],
            'starts_at' => $data['date'] . ' ' . $data['start_time'],
            'ends_at' => $data['date'] . ' ' . $data['end_time'],
            'participants' => $data['participants'],
            'status' => $data['status'] ?? 'active',
        ];
    }
}