<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('karyawans', function (Blueprint $table) {
            $table->date('join_date')->nullable()->after('departemen');            // Tanggal Masuk
            $table->boolean('must_change_password')->default(false)->after('password'); // paksa ganti password (hasil bulk import)
        });
    }

    public function down(): void
    {
        Schema::table('karyawans', function (Blueprint $table) {
            $table->dropColumn(['join_date', 'must_change_password']);
        });
    }
};
