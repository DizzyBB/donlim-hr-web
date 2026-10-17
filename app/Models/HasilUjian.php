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
        'peserta_baru_id',
        'status',
        'nilai_pilihan_ganda',
        'nilai_manual',
        'nilai_total',
        'passing_grade',
        'lulus',
        'dikoreksi_oleh',
        'dikoreksi_pada',
        'catatan_koreksi',
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
        'passing_grade' => 'decimal:2',
        'lulus' => 'boolean',
        'dikoreksi_pada' => 'datetime',
    ];

    public function ujian()
    {
        return $this->belongsTo(Ujian::class);
    }

    public function karyawan()
    {
        return $this->belongsTo(Karyawan::class);
    }

    public function pesertaBaru()
    {
        return $this->belongsTo(PesertaBaru::class, 'peserta_baru_id');
    }

    public function penilai()
    {
        return $this->belongsTo(User::class, 'dinilai_oleh');
    }

    public function pengoreksi()
    {
        return $this->belongsTo(User::class, 'dikoreksi_oleh');
    }

    public function tokens()
    {
        return $this->hasMany(UjianToken::class);
    }

    /** Nama peserta: karyawan aktif atau peserta karyawan baru. */
    public function getNamaPesertaAttribute(): string
    {
        return $this->karyawan?->nama ?? $this->pesertaBaru?->nama ?? '-';
    }

    public function getDepartemenPesertaAttribute(): ?string
    {
        return $this->karyawan?->departemen ?? $this->pesertaBaru?->departemen_nama;
    }

    public function getNomorPesertaAttribute(): string
    {
        return $this->karyawan?->nik ?? 'Karyawan Baru';
    }

    public function getIsPesertaBaruAttribute(): bool
    {
        return $this->peserta_baru_id !== null;
    }

    /** Label status untuk tampilan: belum_dinilai ditampilkan sebagai "Pending". */
    public function getStatusLabelAttribute(): string
    {
        return [
            'berlangsung' => 'Berlangsung',
            'belum_dinilai' => 'Pending',
            'selesai_dinilai' => 'Selesai',
        ][$this->status] ?? $this->status;
    }

    public function getKelulusanLabelAttribute(): string
    {
        if ($this->status !== 'selesai_dinilai' || $this->lulus === null) {
            return $this->status === 'berlangsung' ? 'Sedang dikerjakan' : 'Pending';
        }

        return $this->lulus ? 'Lulus' : 'Tidak Lulus';
    }

    public function jawaban()
    {
        return $this->hasMany(JawabanPeserta::class);
    }

    /**
     * Batas akhir pengerjaan = waktu mulai + durasi ujian.
     * Ujian bisa diakses kapan saja selama aktif, jadi tidak ada lagi batas jam harian.
     */
    public function batasWaktu(): CarbonInterface
    {
        return $this->waktu_mulai->copy()->addMinutes($this->ujian->durasi_menit);
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
     * Butuh penilaian manual (esai) selama status masih Pending.
     */
    public function getPerluDinilaiAttribute(): bool
    {
        return $this->status === 'belum_dinilai';
    }
}
