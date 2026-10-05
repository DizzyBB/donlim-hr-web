<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\HasilUjian;
use App\Models\JawabanPeserta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PenilaianController extends Controller
{
    /**
     * Poin mentah maksimal per tipe soal (bukan dari kolom "poin" di Bank Soal lagi).
     * PG dinilai otomatis (benar/salah), Jawaban Singkat & Uraian dinilai manual
     * dengan skor 0..maks di bawah ini.
     */
    protected const POIN_MENTAH = [
        'pg' => 5,
        'bs' => 10,
        'ur' => 20,
    ];

    /**
     * Daftar hasil ujian yang perlu dinilai (default) atau sudah dinilai.
     */
    public function index(Request $request)
    {
        $status = $request->input('status', 'belum_dinilai');

        $hasilUjian = HasilUjian::with(['ujian', 'karyawan'])
            ->status($status)
            ->when($request->filled('search'), function ($q) use ($request) {
                $s = $request->input('search');
                $q->where(function ($qq) use ($s) {
                    $qq->whereHas('ujian', fn ($u) => $u->where('nama_ujian', 'like', "%{$s}%"))
                       ->orWhereHas('karyawan', fn ($k) => $k->where('nama', 'like', "%{$s}%"));
                });
            })
            ->latest('waktu_selesai')
            ->paginate(10)
            ->withQueryString();

        return view('examination.penilaian-index', compact('hasilUjian', 'status'));
    }

    /**
     * Form penilaian: PG ditampilkan otomatis (benar/salah + skor),
     * Uraian & Jawaban Singkat diisi skor manual oleh penilai.
     */
    public function show(HasilUjian $hasilUjian)
    {
        $hasilUjian->load(['ujian', 'karyawan', 'jawaban.soal']);

        // Pastikan skor PG selalu ter-update sesuai kunci jawaban soal saat ini.
        $this->hitungUlangSkorPG($hasilUjian);
        $hasilUjian->refresh()->load('jawaban.soal');

        // Preview nilai skala 100 (belum disimpan) supaya kartu ringkasan akurat
        // walau penilaian manual belum di-submit.
        [$nilaiPG, $nilaiManual, $nilaiTotal] = $this->hitungNilaiSkalaSeratus($hasilUjian);
        $hasilUjian->nilai_pilihan_ganda = $nilaiPG;
        $hasilUjian->nilai_manual = $nilaiManual;
        $hasilUjian->nilai_total = $nilaiTotal;

        $jawabanPG = $hasilUjian->jawaban->where('tipe', 'pg');
        $jawabanManual = $hasilUjian->jawaban->whereIn('tipe', ['bs', 'ur']);

        return view('examination.penilaian-show', compact('hasilUjian', 'jawabanPG', 'jawabanManual'));
    }

    /**
     * Simpan skor manual untuk tiap jawaban uraian/jawaban singkat,
     * lalu hitung ulang nilai_pilihan_ganda + nilai_manual + nilai_total.
     */
    public function update(Request $request, HasilUjian $hasilUjian)
    {
        $hasilUjian->load(['jawaban.soal']);

        $jawabanManual = $hasilUjian->jawaban->whereIn('tipe', ['bs', 'ur']);

        $rules = [];
        foreach ($jawabanManual as $j) {
            $maxSkor = self::POIN_MENTAH[$j->tipe] ?? 10;
            $rules["skor.{$j->id}"] = ['required', 'numeric', 'min:0', "max:{$maxSkor}"];
            $rules["catatan.{$j->id}"] = ['nullable', 'string'];
        }

        $validated = $request->validate($rules);

        DB::transaction(function () use ($hasilUjian, $jawabanManual, $validated) {
            foreach ($jawabanManual as $j) {
                $j->update([
                    'skor' => $validated['skor'][$j->id] ?? 0,
                    'catatan_penilai' => $validated['catatan'][$j->id] ?? null,
                ]);
            }

            $this->hitungUlangSkorPG($hasilUjian);

            [$nilaiPG, $nilaiManual, $nilaiTotal] = $this->hitungNilaiSkalaSeratus($hasilUjian->fresh(['jawaban.soal']));

            $hasilUjian->update([
                'nilai_pilihan_ganda' => $nilaiPG,
                'nilai_manual' => $nilaiManual,
                'nilai_total' => $nilaiTotal,
                'status' => 'selesai_dinilai',
                'dinilai_pada' => now(),
                'dinilai_oleh' => auth('web')->id(),
            ]);
        });

        return redirect()
            ->route('examination.penilaian.index')
            ->with('success', "Penilaian untuk \"{$hasilUjian->karyawan->nama}\" berhasil disimpan. Nilai total: {$hasilUjian->nilai_total}");
    }

    /**
     * Hitung ulang benar/salah + skor mentah untuk semua jawaban pilihan ganda
     * berdasarkan kunci jawaban (jawaban_index) yang tersimpan di soal.
     * Skor mentah PG selalu 5 (POIN_MENTAH['pg']) kalau benar, 0 kalau salah.
     */
    protected function hitungUlangSkorPG(HasilUjian $hasilUjian): void
    {
        $jawabanPG = $hasilUjian->jawaban->where('tipe', 'pg');
        $poinPG = self::POIN_MENTAH['pg'];

        foreach ($jawabanPG as $j) {
            if (! $j->soal) {
                continue;
            }
            $benar = $j->jawaban_index !== null && $j->jawaban_index === $j->soal->jawaban_index;
            $j->update([
                'benar' => $benar,
                'skor' => $benar ? $poinPG : 0,
            ]);
        }
    }

    /**
     * Skala nilai ke 0-100: total skor mentah yang didapat dibagi total skor
     * mentah maksimal yang mungkin (5 x jumlah PG + 10 x jumlah Jawaban Singkat
     * + 20 x jumlah Uraian), lalu dikali 100. Kalau semua soal benar/dapat
     * skor penuh, hasilnya otomatis 100 — berapa pun jumlah & komposisi soalnya.
     *
     * @return array{0: float, 1: float, 2: float} [nilaiPG, nilaiManual, nilaiTotal]
     */
    protected function hitungNilaiSkalaSeratus(HasilUjian $hasilUjian): array
    {
        $jawaban = $hasilUjian->jawaban;

        $maksimalMentah = $jawaban->sum(fn ($j) => self::POIN_MENTAH[$j->tipe] ?? 0);

        if ($maksimalMentah <= 0) {
            return [0, 0, 0];
        }

        $mentahPG = $jawaban->where('tipe', 'pg')->sum('skor');
        $mentahManual = $jawaban->whereIn('tipe', ['bs', 'ur'])->sum('skor');

        $nilaiPG = round(($mentahPG / $maksimalMentah) * 100, 2);
        $nilaiManual = round(($mentahManual / $maksimalMentah) * 100, 2);

        return [$nilaiPG, $nilaiManual, $nilaiPG + $nilaiManual];
    }
}
