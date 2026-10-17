<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Peserta karyawan baru tidak punya akun di tabel karyawans.
        Schema::table('hasil_ujians', function (Blueprint $table) {
            $table->dropForeign(['karyawan_id']);
        });

        Schema::table('hasil_ujians', function (Blueprint $table) {
            $table->unsignedBigInteger('karyawan_id')->nullable()->change();
        });

        Schema::table('hasil_ujians', function (Blueprint $table) {
            $table->foreign('karyawan_id')->references('id')->on('karyawans')->cascadeOnDelete();

            $table->foreignId('peserta_baru_id')->nullable()->after('karyawan_id')
                ->constrained('peserta_baru')->cascadeOnDelete();
            $table->unique(['ujian_id', 'peserta_baru_id']);

            $table->decimal('passing_grade', 5, 2)->nullable()->after('nilai_total'); // snapshot saat hasil final
            $table->boolean('lulus')->nullable()->after('passing_grade');             // null = belum final (Pending)

            $table->foreignId('dikoreksi_oleh')->nullable()->after('dinilai_oleh')->constrained('users')->nullOnDelete();
            $table->dateTime('dikoreksi_pada')->nullable()->after('dikoreksi_oleh');
            $table->text('catatan_koreksi')->nullable()->after('dikoreksi_pada');
        });

        // Backfill hasil lama memakai passing grade awal 70.
        DB::table('hasil_ujians')->where('status', 'selesai_dinilai')->update([
            'passing_grade' => 70,
            'lulus' => DB::raw('CASE WHEN nilai_total >= 70 THEN 1 ELSE 0 END'),
        ]);
    }

    public function down(): void
    {
        Schema::table('hasil_ujians', function (Blueprint $table) {
            $table->dropUnique(['ujian_id', 'peserta_baru_id']);
            $table->dropConstrainedForeignId('peserta_baru_id');
            $table->dropConstrainedForeignId('dikoreksi_oleh');
            $table->dropColumn(['passing_grade', 'lulus', 'dikoreksi_pada', 'catatan_koreksi']);
        });
    }
};
