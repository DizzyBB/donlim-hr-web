<?php

namespace Database\Seeders;

use App\Models\Karyawan;
use App\Models\Soal;
use App\Models\Ujian;
use Illuminate\Database\Seeder;

class UjianDemoSeeder extends Seeder
{
    /**
     * Membuat 2 ujian demo yang masa berlakunya 00:00 - 23:59 supaya selalu muncul saat dicoba:
     *  1. Ujian untuk semua karyawan (isi: semua soal, acak soal & acak jawaban aktif)
     *  2. Ujian perorangan khusus karyawan KRY004 (isi: hanya pilihan ganda, jadi nilainya langsung keluar)
     *
     * Jalankan SoalSeeder dan KaryawanSeeder terlebih dahulu.
     */
    public function run(): void
    {
        $semuaSoal = Soal::all();

        if ($semuaSoal->isEmpty()) {
            $this->command?->warn('Belum ada data Soal, jalankan SoalSeeder dulu.');
            return;
        }

        $umum = Ujian::firstOrCreate(
            ['nama_ujian' => 'Ujian Demo - Untuk Semua Karyawan'],
            [
                'jenis' => 'karyawan_baru',
                'karyawan_id' => null,
                'jam_mulai' => '00:00',
                'jam_selesai' => '23:59',
                'durasi_menit' => 15,
                'acak_soal' => true,
                'acak_jawaban' => true,
            ]
        );
        $umum->soal()->syncWithoutDetaching($semuaSoal->pluck('id'));

        $peserta = Karyawan::where('nik', 'KRY004')->first();

        if (! $peserta) {
            $this->command?->warn('Karyawan KRY004 tidak ditemukan, ujian perorangan demo dilewati (jalankan KaryawanSeeder dulu).');
            return;
        }

        $perorangan = Ujian::firstOrCreate(
            ['nama_ujian' => 'Ujian Demo - Perorangan (KRY004)'],
            [
                'jenis' => 'perorangan',
                'karyawan_id' => $peserta->id,
                'jam_mulai' => '00:00',
                'jam_selesai' => '23:59',
                'durasi_menit' => 10,
                'acak_soal' => false,
                'acak_jawaban' => false,
            ]
        );
        $perorangan->soal()->syncWithoutDetaching($semuaSoal->where('tipe', 'pg')->pluck('id'));
    }
}
