<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Akun Administrator. Login pakai field "Email / Nomor Karyawan" di halaman
     * login diisi dengan email di bawah ini (khusus Administrator memang pakai email,
     * bukan NIK — karena Administrator disimpan di tabel `users` bawaan Laravel).
     */
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'admin@donlim.test'],
            [
                'name' => 'Admin Utama',
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
            ]
        );
    }
}
