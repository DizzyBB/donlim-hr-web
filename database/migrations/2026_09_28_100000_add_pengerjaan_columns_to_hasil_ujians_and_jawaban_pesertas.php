<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Urutan soal & urutan opsi jawaban per peserta (dipakai kalau Acak Soal / Acak Jawaban aktif)
        Schema::table('jawaban_pesertas', function (Blueprint $table) {
            $table->unsignedInteger('urutan')->default(0)->after('tipe');
            $table->json('urutan_opsi')->nullable()->after('urutan');
        });

        // Status baru "berlangsung" = peserta sedang mengerjakan (khusus MySQL)
        DB::statement("ALTER TABLE hasil_ujians MODIFY status ENUM('berlangsung','belum_dinilai','selesai_dinilai') NOT NULL DEFAULT 'berlangsung'");

        // Satu karyawan hanya boleh punya satu kali pengerjaan per ujian
        Schema::table('hasil_ujians', function (Blueprint $table) {
            $table->unique(['ujian_id', 'karyawan_id']);
        });
    }

    public function down(): void
    {
        Schema::table('hasil_ujians', function (Blueprint $table) {
            $table->dropUnique(['ujian_id', 'karyawan_id']);
        });

        DB::table('hasil_ujians')->where('status', 'berlangsung')->update(['status' => 'belum_dinilai']);
        DB::statement("ALTER TABLE hasil_ujians MODIFY status ENUM('belum_dinilai','selesai_dinilai') NOT NULL DEFAULT 'belum_dinilai'");

        Schema::table('jawaban_pesertas', function (Blueprint $table) {
            $table->dropColumn(['urutan', 'urutan_opsi']);
        });
    }
};
