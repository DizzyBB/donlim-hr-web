<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Tipe soal menjadi: pg (Pilihan Ganda), tf (Benar/Salah), ur (Esai).
 * Tipe lama "bs" (Jawaban Singkat, dinilai manual) dikonversi ke "ur";
 * teks jawaban benarnya dipindah ke kolom `kunci` supaya tidak hilang.
 */
return new class extends Migration
{
    public function up(): void
    {
        $mysql = in_array(DB::getDriverName(), ['mysql', 'mariadb'], true);

        if ($mysql) {
            DB::statement("ALTER TABLE soals MODIFY tipe ENUM('pg','bs','tf','ur') NOT NULL");
        }

        Schema::table('soals', function (Blueprint $table) {
            $table->boolean('jawaban_tf')->nullable()->after('jawaban_index'); // kunci tipe tf: true = Benar
        });

        DB::table('soals')->where('tipe', 'bs')->update([
            'kunci' => DB::raw('COALESCE(kunci, jawaban_singkat)'),
            'tipe' => 'ur',
        ]);
        DB::table('jawaban_pesertas')->where('tipe', 'bs')->update(['tipe' => 'ur']);

        if ($mysql) {
            DB::statement("ALTER TABLE soals MODIFY tipe ENUM('pg','tf','ur') NOT NULL");
        }
    }

    public function down(): void
    {
        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            DB::statement("ALTER TABLE soals MODIFY tipe ENUM('pg','bs','tf','ur') NOT NULL");
        }

        DB::table('soals')->where('tipe', 'tf')->update(['tipe' => 'bs']);

        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            DB::statement("ALTER TABLE soals MODIFY tipe ENUM('pg','bs','ur') NOT NULL");
        }

        Schema::table('soals', function (Blueprint $table) {
            $table->dropColumn('jawaban_tf');
        });
    }
};
