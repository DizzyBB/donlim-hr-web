<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Soal extends Model
{
    use HasFactory;

    public const TIPE_PG = 'pg';   // Pilihan Ganda (otomatis)
    public const TIPE_TF = 'tf';   // Benar / Salah (otomatis)
    public const TIPE_UR = 'ur';   // Esai (dinilai manual oleh HRD)

    public const LABEL_TIPE = [
        self::TIPE_PG => 'Pilihan Ganda',
        self::TIPE_TF => 'Benar / Salah',
        self::TIPE_UR => 'Esai',
    ];

    protected $fillable = [
        'tipe',          // pg | tf | ur
        'pertanyaan',
        'kategori',
        'poin',
        'opsi',             // array opsi jawaban (khusus pg)
        'jawaban_index',    // index opsi benar (khusus pg)
        'jawaban_tf',       // kunci tipe tf: true = Benar, false = Salah
        'kunci',            // kunci/rubrik jawaban (khusus ur / esai)
    ];

    protected $casts = [
        'opsi' => 'array',
        'poin' => 'integer',
        'jawaban_index' => 'integer',
        'jawaban_tf' => 'boolean',
    ];

    /**
     * Bentuk array soal sesuai format yang dipakai front-end
     * (camelCase: jawabanIndex, jawabanSingkat) supaya JS tidak perlu diubah banyak.
     */
    public function toFrontendArray(): array
    {
        return [
            'id' => (string) $this->id,
            'tipe' => $this->tipe,
            'pertanyaan' => $this->pertanyaan,
            'kategori' => $this->kategori,
            'poin' => $this->poin,
            'opsi' => $this->opsi ?? [],
            'jawabanIndex' => $this->jawaban_index,
            'jawabanTF' => $this->jawaban_tf === null ? null : ($this->jawaban_tf ? 'benar' : 'salah'),
            'kunci' => $this->kunci,
        ];
    }

    public function getTipeLabelAttribute(): string
    {
        return self::LABEL_TIPE[$this->tipe] ?? $this->tipe;
    }

    public function scopeTipe($query, ?string $tipe)
    {
        return $tipe ? $query->where('tipe', $tipe) : $query;
    }
}
