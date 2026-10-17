<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ujian extends Model
{
    use HasFactory;

    protected $fillable = [
        'jenis',          // perorangan | karyawan_baru
        'nama_ujian',
        'status',         // aktif | nonaktif (ujian bisa diakses kapan saja selama aktif)
        'karyawan_id',    // hanya diisi untuk jenis "perorangan"
        'jam_mulai',      // tidak dipakai lagi (dipertahankan untuk data lama)
        'jam_selesai',    // tidak dipakai lagi (dipertahankan untuk data lama)
        'durasi_menit',
        'passing_grade',
        'acak_soal',
        'acak_jawaban',
    ];

    protected $casts = [
        'acak_soal' => 'boolean',
        'acak_jawaban' => 'boolean',
        'durasi_menit' => 'integer',
        'passing_grade' => 'decimal:2',
    ];

    public function karyawan()
    {
        // Sesuaikan dengan model Karyawan yang sudah ada di project kamu
        // kalau namespace/nama modelnya berbeda.
        return $this->belongsTo(Karyawan::class);
    }

    public function soal()
    {
        return $this->belongsToMany(Soal::class, 'soal_ujian')->withPivot('bobot');
    }

    public function hasilUjian()
    {
        return $this->hasMany(HasilUjian::class);
    }

    public function tokens()
    {
        return $this->hasMany(UjianToken::class);
    }

    public function scopeAktif($query)
    {
        return $query->where('status', 'aktif');
    }

    public function getIsAktifAttribute(): bool
    {
        return $this->status === 'aktif';
    }

    public function scopeJenis($query, ?string $jenis)
    {
        return $jenis ? $query->where('jenis', $jenis) : $query;
    }

    public function getDurasiLabelAttribute(): string
    {
        $jam = intdiv($this->durasi_menit, 60);
        $menit = $this->durasi_menit % 60;

        if ($jam > 0 && $menit > 0) {
            return "{$jam} jam {$menit} menit";
        } elseif ($jam > 0) {
            return "{$jam} jam";
        }

        return "{$menit} menit";
    }
}
