<?php

namespace App\Support;

class Nama
{
    /**
     * Bentuk pembanding nama untuk deteksi duplikat: huruf kecil dan tanpa spasi apa pun.
     * "  Budi   Santoso " dan "budi santoso" dianggap sama.
     */
    public static function normalisasi(?string $nama): string
    {
        return mb_strtolower(preg_replace('/\s+/u', '', (string) $nama));
    }
}
