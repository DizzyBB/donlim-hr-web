<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ujian tidak lagi dibatasi jendela jam harian: bisa diakses kapan saja selama status = aktif.
 * Kolom jam_mulai/jam_selesai dipertahankan (nullable) agar data lama tidak hilang.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ujians', function (Blueprint $table) {
            $table->enum('status', ['aktif', 'nonaktif'])->default('aktif')->after('nama_ujian');
            $table->decimal('passing_grade', 5, 2)->default(70)->after('durasi_menit');
            $table->time('jam_mulai')->nullable()->change();
            $table->time('jam_selesai')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('ujians', function (Blueprint $table) {
            $table->dropColumn(['status', 'passing_grade']);
        });
    }
};
