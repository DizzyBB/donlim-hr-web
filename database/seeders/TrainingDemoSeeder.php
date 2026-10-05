<?php

namespace Database\Seeders;

use App\Models\Karyawan;
use App\Models\Room;
use App\Models\TrainingRequest;
use Illuminate\Database\Seeder;

class TrainingDemoSeeder extends Seeder
{
    public function run(): void
    {
        $ruangA = Room::firstOrCreate(['name' => 'Ruang Meeting A'], ['capacity' => 12, 'location' => 'Lantai 2']);
        $ruangB = Room::firstOrCreate(['name' => 'Ruang Training Utama'], ['capacity' => 30, 'location' => 'Lantai 3']);
        Room::firstOrCreate(['name' => 'Ruang Meeting B'], ['capacity' => 8, 'location' => 'Lantai 2', 'is_active' => false]);

        $karyawan = Karyawan::inRandomOrder()->take(3)->get();
        if ($karyawan->isEmpty()) {
            $this->command?->warn('Belum ada data Karyawan, jalankan KaryawanSeeder dulu.');
            return;
        }

        TrainingRequest::firstOrCreate(
            ['title' => 'Workshop Laravel Advanced'],
            [
                'karyawan_id' => $karyawan[0]->id,
                'kind' => 'training',
                'type' => 'internal',
                'reason' => 'Upskilling tim development',
                'objective' => 'Mampu membangun API dan fitur kompleks dengan Laravel',
                'room_id' => $ruangB->id,
                'start_time' => now()->addDays(3)->setTime(9, 0),
                'end_time' => now()->addDays(3)->setTime(12, 0),
                'status' => 'pending',
            ]
        );

        TrainingRequest::firstOrCreate(
            ['title' => 'Sertifikasi K3 (Eksternal)'],
            [
                'karyawan_id' => $karyawan[1]->id,
                'kind' => 'training',
                'type' => 'external',
                'reason' => 'Kepatuhan regulasi keselamatan kerja',
                'objective' => 'Karyawan tersertifikasi K3',
                'room_id' => null,
                'start_time' => now()->addDays(5)->setTime(8, 0),
                'end_time' => now()->addDays(6)->setTime(17, 0),
                'status' => 'approved',
                'reviewed_at' => now(),
            ]
        );

        TrainingRequest::firstOrCreate(
            ['title' => 'Rapat Koordinasi Bulanan'],
            [
                'karyawan_id' => $karyawan[2]->id,
                'kind' => 'room',
                'type' => 'internal',
                'room_id' => $ruangA->id,
                'start_time' => now()->addDay()->setTime(14, 0),
                'end_time' => now()->addDay()->setTime(16, 0),
                'status' => 'pending',
            ]
        );
    }
}
