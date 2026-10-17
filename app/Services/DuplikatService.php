<?php

namespace App\Services;

use App\Models\HasilUjian;
use App\Models\Karyawan;
use App\Models\PesertaBaru;
use Illuminate\Support\Collection;

/**
 * Deteksi kemungkinan duplikat peserta karyawan baru. Hanya untuk peringatan di sisi admin,
 * tidak pernah memblokir peserta.
 *
 * Dua sumber kecocokan (nama dibandingkan tanpa membedakan huruf besar/kecil dan spasi):
 *  1. peserta karyawan baru lain yang memakai nama yang sama
 *  2. karyawan aktif (hasil import) yang namanya sama, beserta jumlah ujian yang sudah mereka ikuti
 */
class DuplikatService
{
    /**
     * @return array{duplikat: bool, jumlah_peserta_sama: int, jumlah_ujian_sebelumnya: int, karyawan: array<int, array{nik: string, nama: string, departemen: ?string, jumlah_ujian: int}>, pesan: string}
     */
    public function untukPesertaBaru(PesertaBaru $peserta): array
    {
        $norm = $peserta->nama_normalisasi;

        $pesertaLain = PesertaBaru::where('nama_normalisasi', $norm)
            ->where('id', '!=', $peserta->id)
            ->pluck('id');

        $ujianSebelumnya = $pesertaLain->isEmpty()
            ? 0
            : HasilUjian::whereIn('peserta_baru_id', $pesertaLain)->count();

        $karyawan = Karyawan::query()
            ->whereRaw("LOWER(REPLACE(nama, ' ', '')) = ?", [$norm])
            ->get(['id', 'nik', 'nama', 'departemen'])
            ->map(fn (Karyawan $k) => [
                'nik' => $k->nik,
                'nama' => $k->nama,
                'departemen' => $k->departemen,
                'jumlah_ujian' => HasilUjian::where('karyawan_id', $k->id)->count(),
            ])
            ->all();

        $duplikat = $pesertaLain->isNotEmpty() || $karyawan !== [];

        $pesan = '';
        if ($pesertaLain->isNotEmpty()) {
            $pesan .= "Nama ini sudah tercatat {$pesertaLain->count()} kali sebagai peserta karyawan baru lain ({$ujianSebelumnya} ujian sebelumnya). ";
        }
        if ($karyawan !== []) {
            $daftar = collect($karyawan)->map(fn ($k) => "{$k['nama']} (NIK {$k['nik']}, {$k['jumlah_ujian']} ujian)")->implode('; ');
            $pesan .= "Cocok dengan karyawan aktif: {$daftar}.";
        }

        return [
            'duplikat' => $duplikat,
            'jumlah_peserta_sama' => $pesertaLain->count(),
            'jumlah_ujian_sebelumnya' => $ujianSebelumnya,
            'karyawan' => $karyawan,
            'pesan' => trim($pesan),
        ];
    }

    /**
     * Peta [hasil_ujian_id => info duplikat] untuk satu halaman daftar hasil (hanya peserta karyawan baru
     * yang terdeteksi duplikat yang dimasukkan).
     *
     * @param  Collection<int, HasilUjian>|iterable  $daftarHasil
     * @return array<int, array>
     */
    public function petakan(iterable $daftarHasil): array
    {
        $peta = [];

        foreach ($daftarHasil as $hasil) {
            if (! $hasil->peserta_baru_id) {
                continue;
            }

            $hasil->loadMissing('pesertaBaru');
            if (! $hasil->pesertaBaru) {
                continue;
            }

            $info = $this->untukPesertaBaru($hasil->pesertaBaru);
            if ($info['duplikat']) {
                $peta[$hasil->id] = $info;
            }
        }

        return $peta;
    }
}
