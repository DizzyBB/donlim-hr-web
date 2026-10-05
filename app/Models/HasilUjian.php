<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HasilUjian extends Model
{
    use HasFactory;

    protected $fillable = [
        'ujian_id',
        'karyawan_id',
        'status',
        'nilai_pilihan_ganda',
        'nilai_manual',
        'nilai_total',
        'waktu_mulai',
        'waktu_selesai',
        'dinilai_pada',
        'dinilai_oleh',
    ];

    protected $casts = [
        'waktu_mulai' => 'datetime',
        'waktu_selesai' => 'datetime',
        'dinilai_pada' => 'datetime',
        'nilai_pilihan_ganda' => 'decimal:2',
        'nilai_manual' => 'decimal:2',
        'nilai_total' => 'decimal:2',
    ];

    public function ujian()
    {
        return $this->belongsTo(Ujian::class);
    }

    public function karyawan()
    {
        return $this->belongsTo(Karyawan::class);
    }

    public function penilai()
    {
        return $this->belongsTo(Karyawan::class, 'dinilai_oleh');
    }

    public function jawaban()
    {
        return $this->hasMany(JawabanPeserta::class);
    }

    /**
     * Batas akhir pengerjaan = yang lebih awal antara
     * (waktu mulai + durasi pengerjaan) dan jam selesai masa berlaku ujian di hari itu.
     */
    public function batasWaktu(): CarbonInterface
    {
        $mulai = $this->waktu_mulai;
        $batasDurasi = $mulai->copy()->addMinutes($this->ujian->durasi_menit);
        $batasJendela = $mulai->copy()->setTimeFromTimeString($this->ujian->jam_selesai);

        return $batasDurasi->min($batasJendela);
    }

    /**
     * Sisa detik sampai batas waktu. Bernilai negatif kalau batas sudah lewat.
     */
    public function sisaDetik(): int
    {
        return $this->batasWaktu()->getTimestamp() - now()->getTimestamp();
    }

    public function scopeStatus($query, ?string $status)
    {
        return $status ? $query->where('status', $status) : $query;
    }

    /**
     * Butuh penilaian manual kalau ada jawaban tipe bs/ur yang skornya masih null.
     */
    public function getPerluDinilaiAttribute(): bool
    {
        return $this->status === 'belum_dinilai';
    }
}
