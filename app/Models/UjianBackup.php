<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UjianBackup extends Model
{
    use HasFactory;

    protected $fillable = [
        'jenis',          // perorangan | karyawan_baru
        'nama_ujian',
        'karyawan_id',    // hanya diisi untuk jenis "perorangan"
        'jam_mulai',
        'jam_selesai',
        'durasi_menit',
        'acak_soal',
        'acak_jawaban',
    ];

    protected $casts = [
        'acak_soal' => 'boolean',
        'acak_jawaban' => 'boolean',
        'durasi_menit' => 'integer',
    ];

    public function karyawan()
    {
        // Sesuaikan dengan model Karyawan yang sudah ada di project kamu
        // kalau namespace/nama modelnya berbeda.
        return $this->belongsTo(Karyawan::class);
    }

    public function soal()
    {
        return $this->belongsToMany(Soal::class, 'soal_ujian');
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
