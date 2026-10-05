<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('soals', function (Blueprint $table) {
            $table->id();
            $table->enum('tipe', ['pg', 'bs', 'ur']); // pg = Pilihan Ganda, bs = Jawaban Singkat, ur = Uraian
            $table->text('pertanyaan');
            $table->string('kategori')->nullable();
            $table->unsignedInteger('poin')->default(10); // catatan: tidak dipakai untuk penghitungan nilai ujian (lihat PenilaianController::POIN_MENTAH), hanya informasi di Bank Soal

            // Khusus tipe "pg" (pilihan ganda)
            $table->json('opsi')->nullable();               // array teks opsi jawaban
            $table->unsignedTinyInteger('jawaban_index')->nullable(); // index opsi yang benar

            // Khusus tipe "bs" (jawaban singkat)
            $table->string('jawaban_singkat')->nullable();

            // Khusus tipe "ur" (uraian)
            $table->text('kunci')->nullable(); // kunci jawaban / rubrik penilaian, opsional

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('soals');
    }
};
