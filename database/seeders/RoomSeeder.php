<?php

namespace Database\Seeders;

use App\Models\Room;
use Illuminate\Database\Seeder;

class RoomSeeder extends Seeder
{
    public function run(): void
    {
        $rooms = [
            ['name' => 'Ruang Training A', 'capacity' => 20, 'location' => 'Lantai 2, Gedung A', 'is_active' => true],
            ['name' => 'Ruang Training B', 'capacity' => 30, 'location' => 'Lantai 2, Gedung A', 'is_active' => true],
            ['name' => 'Ruang Meeting Kecil', 'capacity' => 8, 'location' => 'Lantai 1, Gedung B', 'is_active' => true],
            ['name' => 'Ruang Meeting Besar', 'capacity' => 15, 'location' => 'Lantai 3, Gedung A', 'is_active' => true],
            ['name' => 'Auditorium', 'capacity' => 100, 'location' => 'Lantai 1, Gedung Utama', 'is_active' => true],
        ];

        foreach ($rooms as $room) {
            Room::firstOrCreate(['name' => $room['name']], $room);
        }
    }
}