<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ujian_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ujian_id')->constrained('ujians')->cascadeOnDelete();
            $table->string('token', 16)->unique();

            // global_baru : karyawan baru, tidak terikat orang tertentu, sekali pakai
            // karyawan    : ujian lanjutan, terikat ke satu akun karyawan
            // pengganti   : diterbitkan admin untuk melanjutkan pengerjaan yang terputus
            $table->enum('tipe', ['global_baru', 'karyawan', 'pengganti']);
            $table->foreignId('karyawan_id')->nullable()->constrained('karyawans')->cascadeOnDelete();
            $table->foreignId('hasil_ujian_id')->nullable()->constrained('hasil_ujians')->nullOnDelete();

            $table->enum('status', ['aktif', 'terpakai', 'kedaluwarsa'])->default('aktif');
            $table->dateTime('digunakan_pada')->nullable();
            $table->foreignId('dibuat_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['ujian_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ujian_tokens');
    }
};
