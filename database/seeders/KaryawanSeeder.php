<?php

namespace Database\Seeders;

use App\Models\Karyawan;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class KaryawanSeeder extends Seeder
{
    public function run(): void
    {
        // Karyawan biasa, dipakai untuk isi dropdown pemilihan di Ujian Perorangan
        $karyawan = [
            ['nik' => 'KRY001', 'nama' => 'Andi Saputra', 'jabatan' => 'Staff Finance', 'departemen' => 'Finance'],
            ['nik' => 'KRY002', 'nama' => 'Budi Hartono', 'jabatan' => 'Staff IT', 'departemen' => 'IT'],
            ['nik' => 'KRY003', 'nama' => 'Citra Dewi', 'jabatan' => 'Staff Marketing', 'departemen' => 'Marketing'],
            ['nik' => 'KRY004', 'nama' => 'Dedi Kurniawan', 'jabatan' => 'Staff Produksi', 'departemen' => 'Produksi'],
            ['nik' => 'KRY005', 'nama' => 'Eka Putri', 'jabatan' => 'Staff HR', 'departemen' => 'Human Resources'],
            ['nik' => 'KRY006', 'nama' => 'Fajar Nugroho', 'jabatan' => 'Staff Gudang', 'departemen' => 'Logistik'],
            ['nik' => 'KRY007', 'nama' => 'Gita Lestari', 'jabatan' => 'Staff Legal', 'departemen' => 'Legal'],
            ['nik' => 'KRY008', 'nama' => 'Hendra Wijaya', 'jabatan' => 'Staff IT', 'departemen' => 'IT'],
            ['nik' => 'KRY009', 'nama' => 'Indah Permata', 'jabatan' => 'Staff Finance', 'departemen' => 'Finance'],
            ['nik' => 'KRY010', 'nama' => 'Joko Susilo', 'jabatan' => 'Staff Produksi', 'departemen' => 'Produksi'],
            ['nik' => 'KRY011', 'nama' => 'Kartika Sari', 'jabatan' => 'Staff Marketing', 'departemen' => 'Marketing'],
            ['nik' => 'KRY012', 'nama' => 'Lukman Hakim', 'jabatan' => 'Staff Gudang', 'departemen' => 'Logistik'],
            ['nik' => 'KRY013', 'nama' => 'Maya Anggraini', 'jabatan' => 'Staff HR', 'departemen' => 'Human Resources'],
            ['nik' => 'KRY014', 'nama' => 'Nanda Pratama', 'jabatan' => 'Staff IT', 'departemen' => 'IT'],
            ['nik' => 'KRY015', 'nama' => 'Oki Setiawan', 'jabatan' => 'Staff Legal', 'departemen' => 'Legal'],
        ];

        foreach ($karyawan as $item) {
            Karyawan::create([
                'nik' => $item['nik'],
                'nama' => $item['nama'],
                'email' => strtolower(str_replace(' ', '.', $item['nama'])) . '@donlim.test',
                'jabatan' => $item['jabatan'],
                'departemen' => $item['departemen'],
                'password' => Hash::make('password123'),
                'role' => 'karyawan',
            ]);
        }
    }
}
