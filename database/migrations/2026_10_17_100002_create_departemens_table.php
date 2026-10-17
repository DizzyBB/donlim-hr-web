<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('departemens', function (Blueprint $table) {
            $table->id();
            $table->string('nama')->unique();
            $table->timestamps();
        });

        // Isi master dari nilai departemen yang sudah ada di data karyawan.
        $now = now();
        $existing = DB::table('karyawans')
            ->whereNotNull('departemen')
            ->where('departemen', '!=', '')
            ->distinct()
            ->pluck('departemen')
            ->map(fn ($d) => trim($d))
            ->filter()
            ->unique()
            ->values();

        foreach ($existing as $nama) {
            DB::table('departemens')->insertOrIgnore([
                'nama' => $nama,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('departemens');
    }
};
