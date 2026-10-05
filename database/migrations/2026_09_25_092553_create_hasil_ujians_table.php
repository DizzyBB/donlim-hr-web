<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hasil_ujians', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ujian_id')->constrained('ujians')->cascadeOnDelete();
            $table->foreignId('karyawan_id')->constrained('karyawans')->cascadeOnDelete(); // peserta yang mengerjakan

            $table->enum('status', ['belum_dinilai', 'selesai_dinilai'])->default('belum_dinilai');

            $table->decimal('nilai_pilihan_ganda', 6, 2)->default(0); // otomatis
            $table->decimal('nilai_manual', 6, 2)->default(0);        // uraian + jawaban singkat
            $table->decimal('nilai_total', 6, 2)->default(0);         // pg + manual

            $table->dateTime('waktu_mulai')->nullable();
            $table->dateTime('waktu_selesai')->nullable();
            $table->dateTime('dinilai_pada')->nullable();
            $table->foreignId('dinilai_oleh')->nullable()->constrained('users')->nullOnDelete(); // admin penilai, tabel users

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hasil_ujians');
    }
};
