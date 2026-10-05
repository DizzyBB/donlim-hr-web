<?php

namespace Database\Seeders;

use App\Models\HasilUjian;
use App\Models\JawabanPeserta;
use App\Models\Karyawan;
use App\Models\Soal;
use App\Models\Ujian;
use Illuminate\Database\Seeder;

class HasilUjianSeeder extends Seeder
{
    public function run(): void
    {
        // Pastikan ada minimal 1 ujian contoh berisi seluruh soal dari seeder Soal.
        $ujian = Ujian::firstOrCreate(
            ['nama_ujian' => 'Ujian Simulasi - Data Contoh'],
            [
                'jenis' => 'karyawan_baru',
                'karyawan_id' => null,
                'jam_mulai' => '09:00',
                'jam_selesai' => '11:00',
                'durasi_menit' => 30,
                'acak_soal' => true,
                'acak_jawaban' => true,
            ]
        );

        $semuaSoal = Soal::all();
        if ($semuaSoal->isEmpty()) {
            $this->command?->warn('Belum ada data Soal — jalankan SoalSeeder dulu sebelum HasilUjianSeeder.');
            return;
        }
        $ujian->soal()->syncWithoutDetaching($semuaSoal->pluck('id'));

        $pesertaList = Karyawan::where('role', 'karyawan')->take(3)->get();
        if ($pesertaList->isEmpty()) {
            $this->command?->warn('Belum ada data Karyawan — jalankan KaryawanSeeder dulu sebelum HasilUjianSeeder.');
            return;
        }

        // Skenario: peserta 1 = sudah dinilai lengkap, peserta 2 = belum dinilai,
        // peserta 3 = sudah dinilai dengan beberapa jawaban salah (kalau datanya cukup).
        $skenario = ['selesai_dinilai', 'belum_dinilai', 'selesai_dinilai'];

        foreach ($pesertaList as $i => $peserta) {
            $status = $skenario[$i] ?? 'belum_dinilai';

            $hasilUjian = HasilUjian::create([
                'ujian_id' => $ujian->id,
                'karyawan_id' => $peserta->id,
                'status' => 'belum_dinilai', // dihitung ulang di bawah, di-update di akhir
                'waktu_mulai' => now()->subDays(2)->setTime(9, 5),
                'waktu_selesai' => now()->subDays(2)->setTime(9, 32),
            ]);

            // Poin mentah tetap per tipe soal (sama seperti PenilaianController::POIN_MENTAH)
            $poinMentah = ['pg' => 5, 'bs' => 10, 'ur' => 20];

            $mentahPG = 0;
            $mentahManual = 0;
            $maksimalMentah = $semuaSoal->sum(fn ($s) => $poinMentah[$s->tipe] ?? 0);

            foreach ($semuaSoal as $index => $soal) {
                if ($soal->tipe === 'pg') {
                    // Peserta ke-3 sengaja dibuat salah di soal pertama untuk contoh riwayat "salah".
                    $jawabPeserta = ($i === 2 && $index === 0)
                        ? ($soal->jawaban_index === 0 ? 1 : 0) // pilih index lain yang pasti salah
                        : $soal->jawaban_index; // peserta lain jawab benar

                    $benar = $jawabPeserta === $soal->jawaban_index;
                    $skor = $benar ? $poinMentah['pg'] : 0;
                    $mentahPG += $skor;

                    JawabanPeserta::create([
                        'hasil_ujian_id' => $hasilUjian->id,
                        'soal_id' => $soal->id,
                        'tipe' => 'pg',
                        'jawaban_index' => $jawabPeserta,
                        'benar' => $benar,
                        'skor' => $skor,
                    ]);
                } elseif ($soal->tipe === 'bs') {
                    $skorManual = $status === 'selesai_dinilai' ? $poinMentah['bs'] : null;
                    $mentahManual += $skorManual ?? 0;

                    JawabanPeserta::create([
                        'hasil_ujian_id' => $hasilUjian->id,
                        'soal_id' => $soal->id,
                        'tipe' => 'bs',
                        'jawaban_teks' => 'Jawaban contoh dari ' . $peserta->nama,
                        'skor' => $skorManual,
                    ]);
                } else { // uraian
                    $skorManual = $status === 'selesai_dinilai' ? round($poinMentah['ur'] * 0.8) : null; // contoh: 80% dari maksimal
                    $mentahManual += $skorManual ?? 0;

                    JawabanPeserta::create([
                        'hasil_ujian_id' => $hasilUjian->id,
                        'soal_id' => $soal->id,
                        'tipe' => 'ur',
                        'jawaban_teks' => 'Jawaban uraian contoh dari ' . $peserta->nama . '. Menjelaskan poin-poin utama sesuai topik pertanyaan.',
                        'skor' => $skorManual,
                        'catatan_penilai' => $status === 'selesai_dinilai' ? 'Jawaban cukup lengkap, kurang detail di bagian akhir.' : null,
                    ]);
                }
            }

            $nilaiPG = $maksimalMentah > 0 ? round(($mentahPG / $maksimalMentah) * 100, 2) : 0;
            $nilaiManual = $maksimalMentah > 0 ? round(($mentahManual / $maksimalMentah) * 100, 2) : 0;

            $hasilUjian->update([
                'status' => $status,
                'nilai_pilihan_ganda' => $nilaiPG,
                'nilai_manual' => $nilaiManual,
                'nilai_total' => $nilaiPG + $nilaiManual,
                'dinilai_pada' => $status === 'selesai_dinilai' ? now()->subDay() : null,
            ]);
        }
    }
}
