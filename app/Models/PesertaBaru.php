<?php

namespace App\Models;

use App\Support\Nama;
use Illuminate\Database\Eloquent\Model;

class PesertaBaru extends Model
{
    protected $table = 'peserta_baru';

    protected $fillable = [
        'ujian_id',
        'nama',
        'nama_normalisasi',
        'departemen_id',
        'departemen_nama',
        'tanggal_tes',
    ];

    protected $casts = [
        'tanggal_tes' => 'date',
    ];

    protected static function booted(): void
    {
        static::saving(function (PesertaBaru $p) {
            $p->nama_normalisasi = Nama::normalisasi($p->nama);
        });
    }

    public function ujian()
    {
        return $this->belongsTo(Ujian::class);
    }

    public function departemen()
    {
        return $this->belongsTo(Departemen::class);
    }

    public function hasilUjian()
    {
        return $this->hasMany(HasilUjian::class);
    }
}
