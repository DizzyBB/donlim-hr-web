<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UjianToken extends Model
{
    public const TIPE_GLOBAL_BARU = 'global_baru';
    public const TIPE_KARYAWAN = 'karyawan';
    public const TIPE_PENGGANTI = 'pengganti';

    public const STATUS_AKTIF = 'aktif';
    public const STATUS_TERPAKAI = 'terpakai';
    public const STATUS_KEDALUWARSA = 'kedaluwarsa';

    protected $fillable = [
        'ujian_id',
        'token',
        'tipe',
        'karyawan_id',
        'hasil_ujian_id',
        'status',
        'digunakan_pada',
        'dibuat_oleh',
    ];

    protected $casts = [
        'digunakan_pada' => 'datetime',
    ];

    public function ujian()
    {
        return $this->belongsTo(Ujian::class);
    }

    public function karyawan()
    {
        return $this->belongsTo(Karyawan::class);
    }

    public function hasilUjian()
    {
        return $this->belongsTo(HasilUjian::class);
    }

    public function scopeAktif($query)
    {
        return $query->where('status', self::STATUS_AKTIF);
    }

    /** Tampilan token yang mudah dibaca: ABCD-EFGH */
    public function getKodeTampilAttribute(): string
    {
        return strlen($this->token) === 8
            ? substr($this->token, 0, 4) . '-' . substr($this->token, 4)
            : $this->token;
    }

    public function getTipeLabelAttribute(): string
    {
        return [
            self::TIPE_GLOBAL_BARU => 'Karyawan Baru (global)',
            self::TIPE_KARYAWAN => 'Karyawan Aktif',
            self::TIPE_PENGGANTI => 'Pengganti',
        ][$this->tipe] ?? $this->tipe;
    }
}
