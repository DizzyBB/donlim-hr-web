<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JawabanPeserta extends Model
{
    use HasFactory;

    protected $fillable = [
        'hasil_ujian_id',
        'soal_id',
        'tipe',
        'urutan',
        'urutan_opsi',
        'jawaban_index',
        'jawaban_teks',
        'benar',
        'skor',
        'catatan_penilai',
    ];

    protected $casts = [
        'urutan' => 'integer',
        'urutan_opsi' => 'array',
        'jawaban_index' => 'integer',
        'benar' => 'boolean',
        'skor' => 'decimal:2',
    ];

    public function hasilUjian()
    {
        return $this->belongsTo(HasilUjian::class);
    }

    public function soal()
    {
        return $this->belongsTo(Soal::class);
    }

    /**
     * Label opsi jawaban peserta untuk tipe pilihan ganda, contoh: "B. Jakarta"
     */
    public function getJawabanPilihanLabelAttribute(): ?string
    {
        if ($this->tipe !== 'pg' || $this->jawaban_index === null || ! $this->soal) {
            return null;
        }

        $huruf = chr(65 + $this->jawaban_index);
        $teks = $this->soal->opsi[$this->jawaban_index] ?? '-';

        return "{$huruf}. {$teks}";
    }
}
