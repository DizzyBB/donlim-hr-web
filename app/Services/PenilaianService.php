<?php

namespace App\Services;

use App\Models\HasilUjian;
use App\Models\UjianToken;
use Illuminate\Support\Facades\DB;

/**
 * Seluruh perhitungan nilai ada di sini supaya pengumpulan ujian (peserta),
 * penilaian esai, dan koreksi HRD memakai rumus yang sama.
 *
 *  - Pilihan Ganda & Benar/Salah : dinilai otomatis, skor = bobot soal bila benar.
 *  - Esai                        : dinilai manual oleh HRD, skor 0..bobot soal.
 *  - Total bobot setiap ujian = 100, sehingga nilai akhir = jumlah skor = persentase.
 */
class PenilaianService
{
    public const TIPE_OTOMATIS = ['pg', 'tf'];

    public const TIPE_MANUAL = ['ur'];

    /** Evaluasi ulang jawaban PG & Benar/Salah terhadap kunci soal saat ini. */
    public function evaluasiOtomatis(HasilUjian $hasil): void
    {
        $hasil->loadMissing('jawaban.soal');

        foreach ($hasil->jawaban as $j) {
            if (! in_array($j->tipe, self::TIPE_OTOMATIS, true)) {
                continue;
            }

            $benar = $this->apakahBenar($j->tipe, $j->jawaban_index, $j->soal);
            $j->update([
                'benar' => $benar,
                'skor' => $benar ? $j->bobot : 0,
            ]);
        }
    }

    protected function apakahBenar(string $tipe, ?int $jawabanIndex, $soal): bool
    {
        if ($jawabanIndex === null || ! $soal) {
            return false;
        }

        if ($tipe === 'pg') {
            return $jawabanIndex === $soal->jawaban_index;
        }

        // tf: 1 = Benar, 0 = Salah
        return $soal->jawaban_tf !== null && ($jawabanIndex === 1) === (bool) $soal->jawaban_tf;
    }

    /**
     * Jumlahkan skor.
     *
     * @return array{otomatis: float, manual: float, total: float}
     */
    public function ringkas(HasilUjian $hasil): array
    {
        $jawaban = $hasil->jawaban()->get(['tipe', 'skor']);

        $otomatis = (float) $jawaban->whereIn('tipe', self::TIPE_OTOMATIS)->sum('skor');
        $manual = (float) $jawaban->whereIn('tipe', self::TIPE_MANUAL)->sum('skor');

        return [
            'otomatis' => round($otomatis, 2),
            'manual' => round($manual, 2),
            'total' => round(min(100, $otomatis + $manual), 2),
        ];
    }

    /** Masih ada esai yang berisi jawaban tetapi belum diberi skor? */
    public function perluDinilai(HasilUjian $hasil): bool
    {
        return $hasil->jawaban()
            ->whereIn('tipe', self::TIPE_MANUAL)
            ->whereNotNull('jawaban_teks')
            ->whereNull('skor')
            ->exists();
    }

    /**
     * Kumpulkan pengerjaan (dipanggil saat peserta menekan Selesai atau waktu habis).
     * Aman dipanggil berulang: baris dikunci dan hanya status "berlangsung" yang diproses.
     */
    public function finalisasiPengerjaan(HasilUjian $hasilUjian): void
    {
        DB::transaction(function () use ($hasilUjian) {
            $hasil = HasilUjian::with(['ujian', 'jawaban.soal'])
                ->lockForUpdate()
                ->find($hasilUjian->id);

            if (! $hasil || $hasil->status !== 'berlangsung') {
                return;
            }

            $this->evaluasiOtomatis($hasil);

            // Esai kosong: langsung 0, tidak perlu dinilai HRD.
            foreach ($hasil->jawaban as $j) {
                if (in_array($j->tipe, self::TIPE_MANUAL, true) && blank($j->jawaban_teks)) {
                    $j->update(['skor' => 0]);
                }
            }

            $hasil->waktu_selesai = now()->min($hasil->batasWaktu());
            $hasil->save();

            $this->tetapkanStatus($hasil->refresh());

            // Token sudah tidak berguna lagi setelah ujian berakhir.
            UjianToken::where('hasil_ujian_id', $hasil->id)
                ->where('status', UjianToken::STATUS_TERPAKAI)
                ->update(['status' => UjianToken::STATUS_KEDALUWARSA]);
        });
    }

    /**
     * Hitung ulang nilai dan tentukan status:
     *  - ada esai belum dinilai  -> Pending (belum_dinilai), nilai bersifat sementara, belum ada Lulus/Tidak
     *  - semua sudah dinilai     -> selesai_dinilai, lulus ditentukan dari passing grade
     *
     * @param  float|null  $passingGrade  paksa nilai kelulusan tertentu (koreksi HRD); null = pakai snapshot / nilai ujian
     */
    public function tetapkanStatus(HasilUjian $hasil, ?float $passingGrade = null): void
    {
        $ringkas = $this->ringkas($hasil);

        $data = [
            'nilai_pilihan_ganda' => $ringkas['otomatis'],
            'nilai_manual' => $ringkas['manual'],
            'nilai_total' => $ringkas['total'],
        ];

        if ($this->perluDinilai($hasil)) {
            $data += [
                'status' => 'belum_dinilai',
                'passing_grade' => null,
                'lulus' => null,
            ];
        } else {
            $batas = $passingGrade
                ?? ($hasil->passing_grade !== null ? (float) $hasil->passing_grade : null)
                ?? (float) $hasil->ujian->passing_grade;

            $data += [
                'status' => 'selesai_dinilai',
                'passing_grade' => $batas,
                'lulus' => $ringkas['total'] >= $batas,
                'dinilai_pada' => $hasil->dinilai_pada ?? now(),
            ];
        }

        $hasil->update($data);
    }
}
