<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ujians', function (Blueprint $table) {
            $table->id();
            $table->enum('jenis', ['perorangan', 'karyawan_baru']);
            $table->string('nama_ujian');

            // Hanya diisi untuk jenis "perorangan".
            // Sesuaikan nama tabel/kolom foreign key ini dengan tabel karyawan yang sudah ada di project kamu.
            $table->foreignId('karyawan_id')->nullable()->constrained('karyawans')->nullOnDelete();

            $table->time('jam_mulai');
            $table->time('jam_selesai');
            $table->unsignedInteger('durasi_menit'); // durasi pengerjaan, disimpan dalam menit

            $table->boolean('acak_soal')->default(false);
            $table->boolean('acak_jawaban')->default(false);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ujians');
    }
};
