<?php

namespace App\Support;

use InvalidArgumentException;

class Bobot
{
    /**
     * Normalisasi bobot mentah ke total persis 100.00.
     *
     * Input : [soal_id => bobot mentah (> 0)]
     * Output: [soal_id => bobot ternormalisasi (2 desimal)], jumlahnya selalu 100.00.
     * Sisa pembulatan dibebankan ke soal terakhir supaya total tetap tepat 100.
     *
     * @param  array<int|string, int|float|string>  $mentah
     * @return array<int|string, float>
     */
    public static function normalisasi(array $mentah): array
    {
        if ($mentah === []) {
            return [];
        }

        foreach ($mentah as $nilai) {
            if (! is_numeric($nilai) || (float) $nilai <= 0) {
                throw new InvalidArgumentException('Bobot setiap soal harus berupa angka lebih besar dari 0.');
            }
        }

        $total = array_sum(array_map('floatval', $mentah));
        $hasil = [];
        $akumulasi = 0.0;
        $kunciTerakhir = array_key_last($mentah);

        foreach ($mentah as $kunci => $nilai) {
            $bobot = $kunci === $kunciTerakhir
                ? round(100 - $akumulasi, 2)
                : round(((float) $nilai / $total) * 100, 2);

            $akumulasi += $bobot;
            $hasil[$kunci] = $bobot;
        }

        return $hasil;
    }
}
