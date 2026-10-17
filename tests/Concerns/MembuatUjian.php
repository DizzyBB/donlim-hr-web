<?php

namespace Tests\Concerns;

use App\Models\Departemen;
use App\Models\Karyawan;
use App\Models\Soal;
use App\Models\Ujian;
use App\Models\User;
use App\Support\Bobot;
use Illuminate\Support\Facades\Hash;

trait MembuatUjian
{
    protected function admin(): User
    {
        return User::forceCreate([
            'name' => 'HRD',
            'email' => 'hrd' . uniqid() . '@example.test',
            'password' => Hash::make('secret123'),
        ]);
    }

    protected function karyawan(string $nik = '1001', string $nama = 'Budi Santoso', string $dept = 'HRD'): Karyawan
    {
        return Karyawan::create([
            'nik' => $nik, 'nama' => $nama, 'departemen' => $dept,
            'password' => 'password123', 'role' => 'karyawan',
        ]);
    }

    /**
     * Ujian dengan 1 PG, 1 Benar/Salah, 1 Esai. Bobot relatif 1:1:2 => 25 / 25 / 50.
     *
     * @return array{0: Ujian, 1: array<string, Soal>}
     */
    protected function ujian(string $jenis = 'karyawan_baru', ?Karyawan $karyawan = null, array $attr = []): array
    {
        $pg = Soal::create(['tipe' => 'pg', 'pertanyaan' => 'Ibu kota?', 'poin' => 10,
            'opsi' => ['Bandung', 'Jakarta', 'Medan'], 'jawaban_index' => 1]);
        $tf = Soal::create(['tipe' => 'tf', 'pertanyaan' => 'Matahari terbit di barat.', 'poin' => 10, 'jawaban_tf' => false]);
        $ur = Soal::create(['tipe' => 'ur', 'pertanyaan' => 'Jelaskan alur cuti.', 'poin' => 10]);

        $ujian = Ujian::create($attr + [
            'jenis' => $jenis,
            'nama_ujian' => 'Ujian Orientasi',
            'status' => 'aktif',
            'karyawan_id' => $jenis === 'perorangan' ? $karyawan?->id : null,
            'durasi_menit' => 30,
            'passing_grade' => 70,
        ]);

        $bobot = Bobot::normalisasi([$pg->id => 1, $tf->id => 1, $ur->id => 2]);
        $ujian->soal()->sync(collect($bobot)->map(fn ($b) => ['bobot' => $b])->all());

        Departemen::firstOrCreate(['nama' => 'IT']);

        return [$ujian, ['pg' => $pg, 'tf' => $tf, 'ur' => $ur]];
    }
}
