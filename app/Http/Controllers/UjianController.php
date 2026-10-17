<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Karyawan;
use App\Models\Soal;
use App\Models\Ujian;
use App\Support\Bobot;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class UjianController extends Controller
{
    public function index(Request $request)
    {
        $ujian = Ujian::with('karyawan')
            ->jenis($request->input('jenis'))
            ->when($request->filled('search'), fn ($q) => $q->where('nama_ujian', 'like', '%' . $request->input('search') . '%'))
            ->withCount('soal')
            ->withCount(['tokens as token_aktif_count' => fn ($q) => $q->where('status', 'aktif')])
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('examination.ujian-index', compact('ujian'));
    }

    public function create()
    {
        $ujian = null;

        return view('examination.ujian-create', compact('ujian'));
    }

    public function store(Request $request)
    {
        $validated = $this->validasi($request);

        $ujian = DB::transaction(function () use ($validated, $request) {
            $ujian = Ujian::create($this->atribut($validated, $request));

            $ujian->soal()->sync($this->bobotPivot($validated, $request));

            return $ujian;
        });

        return redirect()
            ->route('examination.ujian.index')
            ->with('success', "Ujian \"{$ujian->nama_ujian}\" berhasil dibuat dengan " . count($validated['soal_ids']) . ' soal. Bobot dinormalisasi otomatis ke total 100.');
    }

    public function edit(Ujian $ujian)
    {
        $ujian->load('soal');

        return view('examination.ujian-create', compact('ujian'));
    }

    public function update(Request $request, Ujian $ujian)
    {
        $validated = $this->validasi($request, $ujian);

        DB::transaction(function () use ($validated, $request, $ujian) {
            $ujian->update($this->atribut($validated, $request));

            $ujian->soal()->sync($this->bobotPivot($validated, $request));
        });

        return redirect()
            ->route('examination.ujian.index')
            ->with('success', "Ujian \"{$ujian->nama_ujian}\" berhasil diperbarui dengan " . count($validated['soal_ids']) . ' soal. Bobot dinormalisasi otomatis ke total 100.');
    }

    /** Aktifkan / nonaktifkan ujian. Ujian aktif bisa diakses kapan saja (selama peserta punya token). */
    public function toggleStatus(Ujian $ujian)
    {
        $ujian->update(['status' => $ujian->status === 'aktif' ? 'nonaktif' : 'aktif']);

        return back()->with('success', "Ujian \"{$ujian->nama_ujian}\" sekarang {$ujian->status}.");
    }

    public function destroy(Ujian $ujian)
    {
        $jumlahHasil = $ujian->hasilUjian()->count();

        if ($jumlahHasil > 0) {
            return redirect()
                ->route('examination.ujian.index')
                ->with('error', "Ujian \"{$ujian->nama_ujian}\" tidak bisa dihapus karena sudah ada {$jumlahHasil} hasil pengerjaan/penilaian yang terekam untuk ujian ini.");
        }

        $namaUjian = $ujian->nama_ujian;
        $ujian->delete();

        return redirect()
            ->route('examination.ujian.index')
            ->with('success', "Ujian \"{$namaUjian}\" berhasil dihapus.");
    }

    /**
     * Validasi bersama untuk store() & update(). $ujian diisi saat mode edit
     * supaya aturan unique/khusus bisa mengabaikan record itu sendiri kalau perlu.
     */
    protected function atribut(array $validated, Request $request): array
    {
        return [
            'jenis' => $validated['jenis'],
            'nama_ujian' => $validated['nama_ujian'],
            'status' => $validated['status'],
            'karyawan_id' => $validated['karyawan_id'] ?? null,
            'durasi_menit' => $validated['durasi_menit'],
            'passing_grade' => $validated['passing_grade'],
            'acak_soal' => $request->boolean('acak_soal'),
            'acak_jawaban' => $request->boolean('acak_jawaban'),
        ];
    }

    /**
     * Bobot yang diinput admin dianggap angka relatif lalu dinormalisasi otomatis
     * sehingga total tiap ujian selalu tepat 100. Bobot kosong memakai poin soal di Bank Soal.
     *
     * @return array<int, array{bobot: float}>
     */
    protected function bobotPivot(array $validated, Request $request): array
    {
        $input = (array) $request->input('bobot', []);
        $poin = Soal::whereIn('id', $validated['soal_ids'])->pluck('poin', 'id');

        $mentah = [];
        foreach ($validated['soal_ids'] as $id) {
            $nilai = $input[$id] ?? null;
            $mentah[$id] = is_numeric($nilai) && (float) $nilai > 0 ? (float) $nilai : max(1, (int) ($poin[$id] ?? 1));
        }

        return collect(Bobot::normalisasi($mentah))
            ->map(fn ($bobot) => ['bobot' => $bobot])
            ->all();
    }

    /**
     * Validasi bersama untuk store() & update(). $ujian diisi saat mode edit
     * supaya aturan unique/khusus bisa mengabaikan record itu sendiri kalau perlu.
     */
    protected function validasi(Request $request, ?Ujian $ujian = null): array
    {
        $validated = $request->validate([
            'jenis' => ['required', Rule::in(['perorangan', 'karyawan_baru'])],
            'nama_ujian' => ['required', 'string', 'max:255'],
            'status' => ['required', Rule::in(['aktif', 'nonaktif'])],
            'karyawan_id' => ['required_if:jenis,perorangan', 'nullable', 'exists:karyawans,id'],
            'durasi_menit' => ['required', 'integer', 'min:1', 'max:1440'],
            'passing_grade' => ['required', 'numeric', 'min:0', 'max:100'],
            'acak_soal' => ['nullable', 'boolean'],
            'acak_jawaban' => ['nullable', 'boolean'],
            'soal_ids' => ['required', 'array', 'min:1'],
            'soal_ids.*' => ['integer', 'exists:soals,id'],
            'bobot' => ['nullable', 'array'],
            'bobot.*' => ['nullable', 'numeric', 'gt:0', 'max:1000'],
        ], [
            'karyawan_id.required_if' => 'Karyawan wajib dipilih untuk Ujian Perorangan.',
            'passing_grade.required' => 'Passing grade wajib diisi (0 - 100).',
            'passing_grade.max' => 'Passing grade maksimal 100.',
            'soal_ids.required' => 'Minimal satu soal harus dipilih.',
            'soal_ids.min' => 'Minimal satu soal harus dipilih.',
            'bobot.*.gt' => 'Bobot setiap soal harus lebih besar dari 0.',
            'bobot.*.numeric' => 'Bobot harus berupa angka.',
        ]);

        // Ujian Karyawan Baru tidak terikat ke karyawan tertentu.
        if ($validated['jenis'] === 'karyawan_baru') {
            $validated['karyawan_id'] = null;
        }

        return $validated;
    }

    /**
     * Pencarian karyawan untuk searchable-select (dipanggil via fetch/AJAX).
     * Sesuaikan nama kolom 'nama' dan 'nik' kalau berbeda di tabel karyawan kamu.
     */
    public function karyawanSearch(Request $request)
    {
        $q = trim($request->input('q', ''));
        $id = $request->input('id');

        $query = Karyawan::query();

        if ($id) {
            $query->where('id', $id);
        } elseif ($q !== '') {
            $query->where(function ($sub) use ($q) {
                $sub->where('nama', 'like', "%{$q}%")
                    ->orWhere('nik', 'like', "%{$q}%");
            });
        }

        $karyawan = $query->orderBy('nama')->limit(20)->get(['id', 'nama', 'nik']);

        return response()->json($karyawan->map(fn ($k) => [
            'id' => $k->id,
            'text' => $k->nama . ' — ' . $k->nik,
        ]));
    }

    /**
     * Data soal untuk tabel pemilihan Bank Soal (paginated, dipanggil via fetch/AJAX).
     */
    public function soalData(Request $request)
    {
        $soal = Soal::query()
            ->when($request->filled('search'), fn ($q) => $q->where('pertanyaan', 'like', '%' . $request->input('search') . '%'))
            ->tipe($request->input('tipe'))
            ->orderBy('id')
            ->paginate(10, ['id', 'tipe', 'pertanyaan', 'kategori', 'poin'], 'page', $request->input('page', 1));

        return response()->json([
            'data' => collect($soal->items())->map(fn (Soal $s) => [
                'id' => $s->id,
                'tipe' => $s->tipe,
                'tipe_label' => $s->tipe_label,
                'pertanyaan' => $s->pertanyaan,
                'kategori' => $s->kategori,
                'poin' => $s->poin,
            ]),
            'current_page' => $soal->currentPage(),
            'last_page' => $soal->lastPage(),
            'total' => $soal->total(),
        ]);
    }
}
