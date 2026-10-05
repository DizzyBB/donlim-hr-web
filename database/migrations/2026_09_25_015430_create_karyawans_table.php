<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('karyawans', function (Blueprint $table) {
            $table->id();
            $table->string('nik')->unique();     // Nomor Induk Karyawan, dipakai juga untuk login
            $table->string('nama');
            $table->string('email')->nullable()->unique();
            $table->string('jabatan')->nullable();
            $table->string('departemen')->nullable();
            $table->string('password');
            $table->enum('role', ['administrator', 'karyawan'])->default('karyawan');
            $table->rememberToken();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('karyawans');
    }
};
