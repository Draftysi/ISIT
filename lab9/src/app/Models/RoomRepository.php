<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class RoomRepository
{
    private PDO $db;

    private const SELECT_ROOMS = "
        SELECT
            r.id,
            r.room_number,
            'Переговорная №' || r.room_number AS name,
            r.capacity,
            r.location,
            COALESCE((
                SELECT string_agg(e.name, ', ' ORDER BY e.id)
                FROM room_equipment re
                JOIN equipment e ON e.id = re.equipment_id
                WHERE re.room_id = r.id
            ), '') AS equipment
        FROM rooms r
    ";

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    // Получить все переговорные комнаты.
    public function all(): array
    {
        return $this->db->query(
            self::SELECT_ROOMS . ' ORDER BY r.id'
        )->fetchAll();
    }

    // Найти переговорную комнату по ID.
    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare(
            self::SELECT_ROOMS . ' WHERE r.id = :id'
        );

        $stmt->execute(['id' => $id]);

        return $stmt->fetch() ?: null;
    }

    // Создать переговорную комнату.
    public function add(array $data): array
    {
        $this->db->beginTransaction();

        try {
            $stmt = $this->db->prepare(
                'INSERT INTO rooms (
                    room_number, capacity, location
                ) VALUES (
                    :room_number, :capacity, :location
                ) RETURNING id'
            );

            $stmt->execute([
                'room_number' => $data['room_number'],
                'capacity' => $data['capacity'],
                'location' => $data['location'],
            ]);

            $id = (int) $stmt->fetchColumn();

            $this->saveEquipment($id, $data['equipment']);

            $room = $this->find($id);

            $this->db->commit();

            return $room;

        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    // Изменить переговорную комнату.
    public function update(int $id, array $data): ?array
    {
        $existing = $this->find($id);

        if ($existing === null) {
            return null;
        }

        $data = array_replace($existing, $data);

        $this->db->beginTransaction();

        try {
            $stmt = $this->db->prepare(
                'UPDATE rooms SET
                    room_number = :room_number,
                    capacity = :capacity,
                    location = :location
                WHERE id = :id'
            );

            $stmt->execute([
                'id' => $id,
                'room_number' => $data['room_number'],
                'capacity' => $data['capacity'],
                'location' => $data['location'],
            ]);

            $this->saveEquipment($id, $data['equipment']);

            $room = $this->find($id);

            $this->db->commit();

            return $room;

        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    // Удалить переговорную комнату.
    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare(
            'DELETE FROM rooms WHERE id = :id'
        );

        $stmt->execute(['id' => $id]);

        return $stmt->rowCount() > 0;
    }

    // Сохранить оборудование переговорной комнаты.
    private function saveEquipment(
        int $roomId,
        string $equipment
    ): void {
        $stmt = $this->db->prepare(
            'DELETE FROM room_equipment
             WHERE room_id = :room_id'
        );

        $stmt->execute(['room_id' => $roomId]);

        $items = array_unique(
            array_map('trim', explode(',', $equipment))
        );

        foreach ($items as $name) {
            if ($name === '') {
                continue;
            }

            $stmt = $this->db->prepare(
                'INSERT INTO equipment (name)
                 VALUES (:name)
                 ON CONFLICT (name)
                 DO UPDATE SET name = EXCLUDED.name
                 RETURNING id'
            );

            $stmt->execute(['name' => $name]);

            $equipmentId = (int) $stmt->fetchColumn();

            $stmt = $this->db->prepare(
                'INSERT INTO room_equipment (
                    room_id, equipment_id
                ) VALUES (
                    :room_id, :equipment_id
                )'
            );

            $stmt->execute([
                'room_id' => $roomId,
                'equipment_id' => $equipmentId,
            ]);
        }
    }
}