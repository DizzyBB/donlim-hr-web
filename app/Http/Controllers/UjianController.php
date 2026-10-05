<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Karyawan;
use App\Models\Soal;
use App\Models\Ujian;
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
            $ujian = Ujian::create([
                'jenis' => $validated['jenis'],
                'nama_ujian' => $validated['nama_ujian'],
                'karyawan_id' => $validated['karyawan_id'] ?? null,
                'jam_mulai' => $validated['jam_mulai'],
                'jam_selesai' => $validated['jam_selesai'],
                'durasi_menit' => $validated['durasi_menit'],
                'acak_soal' => $request->boolean('acak_soal'),
                'acak_jawaban' => $request->boolean('acak_jawaban'),
            ]);

            $ujian->soal()->sync($validated['soal_ids']);

            return $ujian;
        });

        return redirect()
            ->route('examination.ujian.index')
            ->with('success', "Ujian \"{$ujian->nama_ujian}\" berhasil dibuat dengan " . count($validated['soal_ids']) . ' soal.');
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
            $ujian->update([
                'jenis' => $validated['jenis'],
                'nama_ujian' => $validated['nama_ujian'],
                'karyawan_id' => $validated['karyawan_id'] ?? null,
                'jam_mulai' => $validated['jam_mulai'],
                'jam_selesai' => $validated['jam_selesai'],
                'durasi_menit' => $validated['durasi_menit'],
                'acak_soal' => $request->boolean('acak_soal'),
                'acak_jawaban' => $request->boolean('acak_jawaban'),
            ]);

            $ujian->soal()->sync($validated['soal_ids']);
        });

        return redirect()
            ->route('examination.ujian.index')
            ->with('success', "Ujian \"{$ujian->nama_ujian}\" berhasil diperbarui dengan " . count($validated['soal_ids']) . ' soal.');
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
    protected function validasi(Request $request, ?Ujian $ujian = null): array
    {
        $validated = $request->validate([
            'jenis' => ['required', Rule::in(['perorangan', 'karyawan_baru'])],
            'nama_ujian' => ['required', 'string', 'max:255'],
            'karyawan_id' => ['required_if:jenis,perorangan', 'nullable', 'exists:karyawans,id'],
            'jam_mulai' => ['required', 'date_format:H:i'],
            'jam_selesai' => ['required', 'date_format:H:i', 'after:jam_mulai'],
            'durasi_menit' => ['required', 'integer', 'min:1'],
            'acak_soal' => ['nullable', 'boolean'],
            'acak_jawaban' => ['nullable', 'boolean'],
            'soal_ids' => ['required', 'array', 'min:1'],
            'soal_ids.*' => ['integer', 'exists:soals,id'],
        ], [
            'karyawan_id.required_if' => 'Karyawan wajib dipilih untuk Ujian Perorangan.',
            'jam_selesai.after' => 'Jam selesai harus lebih besar dari jam mulai.',
            'soal_ids.required' => 'Minimal satu soal harus dipilih.',
            'soal_ids.min' => 'Minimal satu soal harus dipilih.',
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
