<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\RoomRepository;

final class RoomController
{
    private RoomRepository $rooms;

    public function __construct()
    {
        $this->rooms = new RoomRepository();
    }

    // GET /rooms - список переговорных комнат.
    public function index(): void
    {
        view('rooms/index', [
            'title' => 'Переговорные комнаты',
            'rooms' => $this->rooms->all(),
        ]);
    }

    // GET /rooms/{id} - информация о переговорной.
    public function show(string $id): void
    {
        $room = $this->rooms->find((int) $id);

        if ($room === null) {
            notFound();
            return;
        }

        view('rooms/show', [
            'title' => $room['name'],
            'room' => $room,
        ]);
    }
}
