<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Bobot per soal per ujian (total tiap ujian = 100).
 * jawaban_pesertas.bobot menyimpan salinan bobot saat ujian dikerjakan, supaya
 * perubahan bobot di kemudian hari tidak mengubah nilai yang sudah ada.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('soal_ujian', function (Blueprint $table) {
            $table->decimal('bobot', 6, 2)->default(0)->after('soal_id');
        });

        Schema::table('jawaban_pesertas', function (Blueprint $table) {
            $table->decimal('bobot', 6, 2)->default(0)->after('tipe');
        });

        // Backfill: bobot awal = poin soal, dinormalisasi ke total 100 per ujian.
        foreach (DB::table('ujians')->pluck('id') as $ujianId) {
            $rows = DB::table('soal_ujian')
                ->join('soals', 'soals.id', '=', 'soal_ujian.soal_id')
                ->where('soal_ujian.ujian_id', $ujianId)
                ->orderBy('soal_ujian.id')
                ->get(['soal_ujian.id', 'soals.poin']);

            $total = (float) $rows->sum('poin');
            if ($rows->isEmpty() || $total <= 0) {
                continue;
            }

            $akumulasi = 0.0;
            $terakhir = $rows->count() - 1;
            foreach ($rows as $i => $row) {
                $bobot = $i === $terakhir
                    ? round(100 - $akumulasi, 2)
                    : round($row->poin / $total * 100, 2);
                $akumulasi += $bobot;

                DB::table('soal_ujian')->where('id', $row->id)->update(['bobot' => $bobot]);
            }
        }

        DB::table('jawaban_pesertas')
            ->join('hasil_ujians', 'hasil_ujians.id', '=', 'jawaban_pesertas.hasil_ujian_id')
            ->join('soal_ujian', function ($join) {
                $join->on('soal_ujian.ujian_id', '=', 'hasil_ujians.ujian_id')
                    ->on('soal_ujian.soal_id', '=', 'jawaban_pesertas.soal_id');
            })
            ->update(['jawaban_pesertas.bobot' => DB::raw('soal_ujian.bobot')]);
    }

    public function down(): void
    {
        Schema::table('soal_ujian', fn (Blueprint $t) => $t->dropColumn('bobot'));
        Schema::table('jawaban_pesertas', fn (Blueprint $t) => $t->dropColumn('bobot'));
    }
};
