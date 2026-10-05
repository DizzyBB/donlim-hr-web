<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Training Management Configuration
    |--------------------------------------------------------------------------
    */

    'colors' => [
        'pending'  => '#f59e0b', // amber
        'approved' => '#22c55e', // green
        'rejected' => '#ef4444', // red
    ],

    /*
    | Semua user di tabel `users` dianggap admin (guard: web).
    | Jika nanti ada role, bisa tambahkan kolom role di migration users.
    */
    'admin_role' => null,

    /*
    | Guard yang digunakan untuk admin (manajemen approval, kalender).
    */
    'admin_guard' => 'web',

    /*
    | Guard yang digunakan untuk karyawan (pengajuan training).
    */
    'karyawan_guard' => 'karyawan',
];