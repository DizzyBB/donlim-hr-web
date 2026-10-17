<?php

namespace App\Http\Controllers;

use App\Models\HasilUjian;
use App\Services\DuplikatService;
use App\Services\PenilaianService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Penilaian & koreksi oleh admin / HRD.
 *  - Esai dinilai manual (0 .. bobot soal). Selama masih ada esai belum dinilai, status ujian = Pending.
 *  - HRD dapat mengoreksi skor semua soal, passing grade hasil, atau menghitung ulang otomatis bila kunci jawaban berubah.
 *    Setiap koreksi pada hasil final / skor otomatis wajib disertai catatan dan tercatat (siapa & kapan).
 */
class PenilaianController extends Controller
{
    public function __construct(
        protected PenilaianService $penilaian,
        protected DuplikatService $duplikat,
    ) {
    }

    /** Daftar hasil ujian yang perlu dinilai (default: Pending) atau sudah dinilai. */
    public function index(Request $request)
    {
        $status = $request->input('status', 'belum_dinilai');

        $hasilUjian = HasilUjian::with(['ujian', 'karyawan', 'pesertaBaru'])
            ->status($status)
            ->when($request->filled('search'), function ($q) use ($request) {
                $s = '%' . $request->input('search') . '%';
                $q->where(function ($qq) use ($s) {
                    $qq->whereHas('ujian', fn ($u) => $u->where('nama_ujian', 'like', $s))
                        ->orWhereHas('karyawan', fn ($k) => $k->where('nama', 'like', $s)->orWhere('nik', 'like', $s))
                        ->orWhereHas('pesertaBaru', fn ($p) => $p->where('nama', 'like', $s));
                });
            })
            ->latest('waktu_selesai')
            ->paginate(10)
            ->withQueryString();

        $duplikat = $this->duplikat->petakan($hasilUjian->getCollection());

        return view('examination.penilaian-index', compact('hasilUjian', 'status', 'duplikat'));
    }

    public function show(HasilUjian $hasilUjian)
    {
        $hasilUjian->load(['ujian', 'karyawan', 'pesertaBaru', 'jawaban.soal', 'pengoreksi', 'penilai']);

        $infoDuplikat = $hasilUjian->pesertaBaru
            ? $this->duplikat->untukPesertaBaru($hasilUjian->pesertaBaru)
            : null;

        return view('examination.penilaian-show', [
            'hasilUjian' => $hasilUjian,
            'jawabanOtomatis' => $hasilUjian->jawaban->whereIn('tipe', PenilaianService::TIPE_OTOMATIS),
            'jawabanEsai' => $hasilUjian->jawaban->whereIn('tipe', PenilaianService::TIPE_MANUAL),
            'infoDuplikat' => $infoDuplikat,
        ]);
    }

    /**
     * Simpan skor (esai wajib; PG/Benar-Salah opsional sebagai koreksi), passing grade hasil, dan catatan koreksi.
     */
    public function update(Request $request, HasilUjian $hasilUjian)
    {
        abort_if($hasilUjian->status === 'berlangsung', 404);

        $hasilUjian->load(['ujian', 'jawaban.soal']);

        $rules = [
            'passing_grade' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'catatan_koreksi' => ['nullable', 'string', 'max:2000'],
        ];

        foreach ($hasilUjian->jawaban as $j) {
            $maks = (float) $j->bobot;
            $esaiBerisi = $j->tipe === 'ur' && filled($j->jawaban_teks);

            $rules["skor.{$j->id}"] = [$esaiBerisi ? 'required' : 'nullable', 'numeric', 'min:0', "max:{$maks}"];
            $rules["catatan.{$j->id}"] = ['nullable', 'string', 'max:2000'];
        }

        $validated = $request->validate($rules, [
            'skor.*.required' => 'Skor esai wajib diisi.',
            'skor.*.max' => 'Skor tidak boleh melebihi bobot soal.',
            'skor.*.numeric' => 'Skor harus berupa angka.',
            'skor.*.min' => 'Skor tidak boleh negatif.',
        ]);

        // Apakah ini sebuah KOREKSI (bukan sekadar penilaian esai pertama kali)?
        $skorBaru = $validated['skor'] ?? [];
        $ubahOtomatis = false;
        foreach ($hasilUjian->jawaban as $j) {
            if (in_array($j->tipe, PenilaianService::TIPE_OTOMATIS, true)
                && array_key_exists($j->id, $skorBaru)
                && $skorBaru[$j->id] !== null
                && abs((float) $skorBaru[$j->id] - (float) $j->skor) > 0.001) {
                $ubahOtomatis = true;
            }
        }

        $passingBaru = isset($validated['passing_grade']) && $validated['passing_grade'] !== ''
            ? (float) $validated['passing_grade']
            : null;
        $ubahPassing = $passingBaru !== null
            && $hasilUjian->passing_grade !== null
            && abs($passingBaru - (float) $hasilUjian->passing_grade) > 0.001;

        $koreksi = $ubahOtomatis || $ubahPassing || $hasilUjian->status === 'selesai_dinilai';

        if ($koreksi && blank($validated['catatan_koreksi'] ?? null)) {
            throw ValidationException::withMessages([
                'catatan_koreksi' => 'Catatan koreksi wajib diisi saat mengubah hasil yang sudah dinilai, skor otomatis, atau passing grade.',
            ]);
        }

        DB::transaction(function () use ($hasilUjian, $validated, $skorBaru, $passingBaru, $koreksi) {
            foreach ($hasilUjian->jawaban as $j) {
                $perubahan = [];

                if (array_key_exists($j->id, $skorBaru) && $skorBaru[$j->id] !== null) {
                    $perubahan['skor'] = $skorBaru[$j->id];
                } elseif ($j->tipe === 'ur' && blank($j->jawaban_teks) && $j->skor === null) {
                    $perubahan['skor'] = 0;
                }

                if (array_key_exists($j->id, $validated['catatan'] ?? [])) {
                    $perubahan['catatan_penilai'] = $validated['catatan'][$j->id];
                }

                if ($perubahan !== []) {
                    $j->update($perubahan);
                }
            }

            $hasilUjian->refresh();
            $this->penilaian->tetapkanStatus($hasilUjian, $passingBaru);

            $hasilUjian->update([
                'dinilai_oleh' => $hasilUjian->dinilai_oleh ?? auth('web')->id(),
                'dinilai_pada' => $hasilUjian->dinilai_pada ?? now(),
            ]);

            if ($koreksi) {
                $hasilUjian->update([
                    'dikoreksi_oleh' => auth('web')->id(),
                    'dikoreksi_pada' => now(),
                    'catatan_koreksi' => $validated['catatan_koreksi'],
                ]);
            }
        });

        $hasilUjian->refresh();

        return redirect()
            ->route('examination.penilaian.show', $hasilUjian)
            ->with('success', "Penilaian untuk \"{$hasilUjian->nama_peserta}\" disimpan. Nilai: {$hasilUjian->nilai_total}% - {$hasilUjian->kelulusan_label}.");
    }

    /**
     * Hitung ulang skor PG & Benar/Salah terhadap kunci jawaban saat ini (mis. setelah kunci soal diperbaiki).
     * Skor esai yang sudah dinilai tidak diubah.
     */
    public function hitungUlang(HasilUjian $hasilUjian)
    {
        abort_if($hasilUjian->status === 'berlangsung', 404);

        $hasilUjian->load(['ujian', 'jawaban.soal']);

        DB::transaction(function () use ($hasilUjian) {
            $this->penilaian->evaluasiOtomatis($hasilUjian);
            $this->penilaian->tetapkanStatus($hasilUjian->refresh());

            if ($hasilUjian->status === 'selesai_dinilai') {
                $hasilUjian->update([
                    'dikoreksi_oleh' => auth('web')->id(),
                    'dikoreksi_pada' => now(),
                    'catatan_koreksi' => 'Hitung ulang otomatis berdasarkan kunci jawaban terbaru.',
                ]);
            }
        });

        return back()->with('success', 'Skor otomatis dihitung ulang berdasarkan kunci jawaban terbaru.');
    }
}
