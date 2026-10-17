<?php

namespace App\Services;

use App\Models\Ujian;
use App\Models\UjianToken;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Collection;

class TokenService
{
    /** Tanpa karakter yang mudah tertukar (0/O, 1/I). */
    private const ALFABET = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    private const PANJANG = 8;

    /** Rapikan input peserta: huruf besar, buang spasi dan tanda hubung. */
    public static function bersihkan(?string $input): string
    {
        return strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) $input));
    }

    public function generateKode(): string
    {
        $maks = strlen(self::ALFABET) - 1;
        $kode = '';

        for ($i = 0; $i < self::PANJANG; $i++) {
            $kode .= self::ALFABET[random_int(0, $maks)];
        }

        return $kode;
    }

    /**
     * Buat satu token. Kode acak diulang kalau bentrok dengan token lain (kolom unik).
     *
     * @param  array{karyawan_id?: ?int, hasil_ujian_id?: ?int, dibuat_oleh?: ?int}  $atribut
     */
    public function buat(Ujian $ujian, string $tipe, array $atribut = []): UjianToken
    {
        for ($percobaan = 0; $percobaan < 5; $percobaan++) {
            try {
                return UjianToken::create([
                    'ujian_id' => $ujian->id,
                    'token' => $this->generateKode(),
                    'tipe' => $tipe,
                    'karyawan_id' => $atribut['karyawan_id'] ?? null,
                    'hasil_ujian_id' => $atribut['hasil_ujian_id'] ?? null,
                    'status' => UjianToken::STATUS_AKTIF,
                    'dibuat_oleh' => $atribut['dibuat_oleh'] ?? null,
                ]);
            } catch (UniqueConstraintViolationException) {
                // kode bentrok, coba kode lain
            }
        }

        throw new \RuntimeException('Gagal membuat token unik, silakan coba lagi.');
    }

    /** @return Collection<int, UjianToken> */
    public function buatBanyak(Ujian $ujian, int $jumlah, ?int $dibuatOleh = null): Collection
    {
        $hasil = collect();

        for ($i = 0; $i < $jumlah; $i++) {
            $hasil->push($this->buat($ujian, UjianToken::TIPE_GLOBAL_BARU, ['dibuat_oleh' => $dibuatOleh]));
        }

        return $hasil;
    }

    public function cari(?string $input): ?UjianToken
    {
        $kode = self::bersihkan($input);

        if ($kode === '') {
            return null;
        }

        return UjianToken::with('ujian')->where('token', $kode)->first();
    }

    /**
     * Tandai token terpakai secara ATOMIK: hanya satu pemanggil yang berhasil
     * walaupun dua orang mengirim token yang sama di detik yang sama.
     */
    public function klaim(UjianToken $token, ?int $hasilUjianId = null): bool
    {
        $data = [
            'status' => UjianToken::STATUS_TERPAKAI,
            'digunakan_pada' => now(),
        ];

        if ($hasilUjianId !== null) {
            $data['hasil_ujian_id'] = $hasilUjianId;
        }

        return UjianToken::query()
            ->whereKey($token->id)
            ->where('status', UjianToken::STATUS_AKTIF)
            ->update($data) === 1;
    }

    /** Batalkan token yang masih aktif (oleh admin). */
    public function kedaluwarsakan(UjianToken $token): bool
    {
        return UjianToken::query()
            ->whereKey($token->id)
            ->where('status', UjianToken::STATUS_AKTIF)
            ->update(['status' => UjianToken::STATUS_KEDALUWARSA]) === 1;
    }
}
