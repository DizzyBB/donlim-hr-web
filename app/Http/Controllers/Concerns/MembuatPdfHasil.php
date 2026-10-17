<?php

namespace App\Http\Controllers\Concerns;

use App\Models\HasilUjian;
use Illuminate\Support\Str;

trait MembuatPdfHasil
{
    /**
     * Unduh hasil ujian sebagai PDF. Memakai barryvdh/laravel-dompdf bila sudah terpasang
     * (composer require barryvdh/laravel-dompdf); kalau belum, halaman yang sama ditampilkan
     * dalam mode cetak sehingga bisa disimpan sebagai PDF lewat dialog cetak browser.
     */
    protected function responsPdf(HasilUjian $hasil, bool $detail)
    {
        $hasil->load(['ujian', 'karyawan', 'pesertaBaru', 'jawaban.soal', 'penilai']);

        $data = [
            'hasil' => $hasil,
            'detail' => $detail,
            'jumlahAuto' => $hasil->jawaban->whereIn('tipe', ['pg', 'tf'])->count(),
            'jumlahBenar' => $hasil->jawaban->whereIn('tipe', ['pg', 'tf'])->where('benar', true)->count(),
        ];

        $namaFile = 'hasil-ujian-' . Str::slug($hasil->nama_peserta ?: 'peserta') . '-' . $hasil->id . '.pdf';

        if (class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
            return \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.hasil-ujian', $data + ['cetakLangsung' => false])
                ->setPaper('a4')
                ->download($namaFile);
        }

        return response()->view('pdf.hasil-ujian', $data + ['cetakLangsung' => true]);
    }
}
