<?php

namespace Database\Seeders;

use App\Models\Soal;
use Illuminate\Database\Seeder;

class SoalSeeder extends Seeder
{
    /**
     * Poin di bawah ini disamakan dengan bobot nilai baku yang dipakai saat
     * penilaian (lihat PenilaianController::POIN_MENTAH):
     *   - Pilihan Ganda     : 5  poin / soal
     *   - Jawaban Singkat   : 10 poin / soal
     *   - Uraian            : 20 poin / soal
     * Kolom "poin" ini sekarang murni informatif di Bank Soal — nilai ujian
     * yang sebenarnya dihitung dengan bobot tetap di atas, bukan dari sini.
     */
    public function run(): void
    {
        $soal = [
            // ===== Pilihan Ganda (5 poin) =====
            [
                'tipe' => 'pg',
                'pertanyaan' => 'Ibu kota Indonesia adalah...',
                'kategori' => 'Geografi',
                'poin' => 5,
                'opsi' => ['Bandung', 'Jakarta', 'Surabaya', 'Medan', 'Makassar'],
                'jawaban_index' => 1,
            ],
            [
                'tipe' => 'pg',
                'pertanyaan' => 'Rumus kimia air adalah...',
                'kategori' => 'Kimia',
                'poin' => 5,
                'opsi' => ['CO2', 'H2O', 'O2', 'NaCl'],
                'jawaban_index' => 1,
            ],
            [
                'tipe' => 'pg',
                'pertanyaan' => 'Planet terbesar di tata surya adalah...',
                'kategori' => 'IPA',
                'poin' => 5,
                'opsi' => ['Bumi', 'Mars', 'Jupiter', 'Saturnus'],
                'jawaban_index' => 2,
            ],
            [
                'tipe' => 'pg',
                'pertanyaan' => 'Proklamasi Kemerdekaan Indonesia dibacakan pada tanggal...',
                'kategori' => 'Sejarah',
                'poin' => 5,
                'opsi' => ['16 Agustus 1945', '17 Agustus 1945', '18 Agustus 1945', '20 Mei 1945'],
                'jawaban_index' => 1,
            ],
            [
                'tipe' => 'pg',
                'pertanyaan' => 'Satuan SI untuk kuat arus listrik adalah...',
                'kategori' => 'Fisika',
                'poin' => 5,
                'opsi' => ['Volt', 'Ampere', 'Ohm', 'Watt'],
                'jawaban_index' => 1,
            ],

            // ===== Jawaban Singkat (10 poin) =====
            [
                'tipe' => 'bs',
                'pertanyaan' => 'Planet terdekat dari matahari adalah...',
                'kategori' => 'IPA',
                'poin' => 10,
                'jawaban_singkat' => 'Merkurius',
            ],
            [
                'tipe' => 'bs',
                'pertanyaan' => 'Ibu kota Provinsi Jawa Barat adalah...',
                'kategori' => 'Geografi',
                'poin' => 10,
                'jawaban_singkat' => 'Bandung',
            ],
            [
                'tipe' => 'bs',
                'pertanyaan' => 'Alat pernapasan utama pada ikan adalah...',
                'kategori' => 'IPA',
                'poin' => 10,
                'jawaban_singkat' => 'Insang',
            ],

            // ===== Uraian (20 poin) =====
            [
                'tipe' => 'ur',
                'pertanyaan' => 'Jelaskan proses terjadinya hujan!',
                'kategori' => 'IPA',
                'poin' => 20,
                'kunci' => 'Hujan terjadi karena proses evaporasi, kondensasi, dan presipitasi.',
            ],
            [
                'tipe' => 'ur',
                'pertanyaan' => 'Sebutkan dan jelaskan 3 penyebab utama inflasi!',
                'kategori' => 'Ekonomi',
                'poin' => 20,
                'kunci' => 'Inflasi tarikan permintaan, inflasi dorongan biaya, dan inflasi karena ekspektasi.',
            ],
        ];

        foreach ($soal as $item) {
            Soal::create($item);
        }
    }
}
