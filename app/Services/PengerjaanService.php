<?php

namespace App\Services;

use App\Models\HasilUjian;
use App\Models\JawabanPeserta;
use App\Exceptions\TokenException;
use App\Models\Ujian;
use App\Models\UjianToken;
use Illuminate\Support\Facades\DB;

class PengerjaanService
{
    /**
     * Mulai pengerjaan: buat baris hasil + satu baris jawaban per soal.
     * Bobot tiap soal disalin ke jawaban supaya nilai tidak berubah kalau bobot ujian diedit nanti.
     * Memakai transaksi sendiri; pemanggil boleh membungkusnya dengan transaksi lebih luas.
     */
    public function mulai(Ujian $ujian, ?int $karyawanId, ?int $pesertaBaruId): HasilUjian
    {
        return DB::transaction(function () use ($ujian, $karyawanId, $pesertaBaruId) {
            $hasil = HasilUjian::create([
                'ujian_id' => $ujian->id,
                'karyawan_id' => $karyawanId,
                'peserta_baru_id' => $pesertaBaruId,
                'status' => 'berlangsung',
                'waktu_mulai' => now(),
            ]);

            $daftarSoal = $ujian->soal()->orderBy('soals.id')->get();
            if ($ujian->acak_soal) {
                $daftarSoal = $daftarSoal->shuffle();
            }

            foreach ($daftarSoal->values() as $i => $soal) {
                $urutanOpsi = null;
                if ($soal->tipe === 'pg') {
                    $urutanOpsi = array_keys($soal->opsi ?? []);
                    if ($ujian->acak_jawaban) {
                        shuffle($urutanOpsi);
                    }
                }

                JawabanPeserta::create([
                    'hasil_ujian_id' => $hasil->id,
                    'soal_id' => $soal->id,
                    'tipe' => $soal->tipe,
                    'bobot' => $soal->pivot->bobot,
                    'urutan' => $i + 1,
                    'urutan_opsi' => $urutanOpsi,
                ]);
            }

            return $hasil;
        });
    }

    /**
     * Lanjutkan pengerjaan yang terputus memakai token pengganti dari admin.
     * Waktu tidak direset: hitung mundur tetap mengacu ke waktu mulai awal.
     *
     * @throws TokenException
     */
    public function lanjutkan(UjianToken $token, TokenService $tokens, PenilaianService $penilaian, ?int $karyawanId = null): HasilUjian
    {
        $hasil = $token->hasilUjian;

        if (! $hasil || $token->tipe !== UjianToken::TIPE_PENGGANTI) {
            throw new TokenException('Token tidak valid atau sudah digunakan.');
        }

        // Token pengganti untuk akun karyawan aktif tidak boleh dipakai akun lain.
        if ($hasil->karyawan_id !== null && (int) $hasil->karyawan_id !== (int) $karyawanId) {
            throw new TokenException('Token ini tidak dapat digunakan pada akun kamu.');
        }

        $hasil->loadMissing('ujian');

        if ($hasil->status !== 'berlangsung' || $hasil->sisaDetik() <= 0) {
            if ($hasil->status === 'berlangsung') {
                $penilaian->finalisasiPengerjaan($hasil);
            }
            $tokens->kedaluwarsakan($token);

            throw new TokenException('Waktu pengerjaan sudah habis, ujian tidak dapat dilanjutkan.');
        }

        if (! $tokens->klaim($token)) {
            throw new TokenException('Token tidak valid atau sudah digunakan.');
        }

        return $hasil;
    }
}
