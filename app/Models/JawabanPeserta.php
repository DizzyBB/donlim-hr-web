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
        'bobot',
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
        'bobot' => 'decimal:2',
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

    /** Label jawaban peserta untuk soal Benar/Salah (jawaban_index: 1 = Benar, 0 = Salah). */
    public function getJawabanTfLabelAttribute(): ?string
    {
        if ($this->tipe !== 'tf' || $this->jawaban_index === null) {
            return null;
        }

        return $this->jawaban_index === 1 ? 'Benar' : 'Salah';
    }
}
