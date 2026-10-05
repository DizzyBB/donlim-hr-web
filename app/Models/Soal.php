<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Soal extends Model
{
    use HasFactory;

    protected $fillable = [
        'tipe',          // pg | bs | ur
        'pertanyaan',
        'kategori',
        'poin',
        'opsi',             // array opsi jawaban (khusus pg)
        'jawaban_index',    // index opsi benar (khusus pg)
        'jawaban_singkat',  // teks jawaban benar (khusus bs / jawaban singkat)
        'kunci',            // kunci/rubrik jawaban (khusus ur)
    ];

    protected $casts = [
        'opsi' => 'array',
        'poin' => 'integer',
        'jawaban_index' => 'integer',
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
            'jawabanSingkat' => $this->jawaban_singkat,
            'kunci' => $this->kunci,
        ];
    }

    public function scopeTipe($query, ?string $tipe)
    {
        return $tipe ? $query->where('tipe', $tipe) : $query;
    }
}
