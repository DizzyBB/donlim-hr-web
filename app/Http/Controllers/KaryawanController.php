<?php

namespace App\Http\Controllers;

use App\Models\Departemen;
use App\Models\HasilUjian;
use App\Models\Karyawan;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class KaryawanController extends Controller
{
    public function index(Request $request)
    {
        // Satu kotak pencarian: cocok ke Nomor Karyawan (NIK) ATAU nama.
        $cari = trim((string) $request->input('search', ''));

        $karyawan = Karyawan::query()
            ->when($cari !== '', fn ($q) => $q->where(function ($qq) use ($cari) {
                $like = '%' . addcslashes($cari, '%_\\') . '%';
                $qq->where('nik', 'like', $like)->orWhere('nama', 'like', $like);
            }))
            ->orderByRaw('CASE WHEN nik = ? THEN 0 ELSE 1 END', [$cari]) // NIK persis tampil paling atas
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('karyawan.index', compact('karyawan'));
    }

    public function create()
    {
        return view('karyawan.edit', ['daftarDepartemen' => Departemen::orderBy('nama')->pluck('nama')]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateKaryawan($request);
        Karyawan::create($validated);
        $this->pastikanDepartemenAda($validated['departemen'] ?? null);

        return redirect()
            ->route('karyawan.index')
            ->with('success', "Data karyawan {$validated['nama']} berhasil ditambahkan.");
    }

    public function edit(Karyawan $karyawan)
    {
        return view('karyawan.edit', [
            'karyawan' => $karyawan,
            'daftarDepartemen' => Departemen::orderBy('nama')->pluck('nama'),
        ]);
    }

    public function update(Request $request, Karyawan $karyawan)
    {
        $validated = $this->validateKaryawan($request, $karyawan);

        $karyawan->update($validated);
        $this->pastikanDepartemenAda($validated['departemen'] ?? null);

        return redirect()
            ->route('karyawan.index')
            ->with('success', "Data karyawan {$karyawan->nama} berhasil diperbarui.");
    }

    public function destroy(Karyawan $karyawan)
    {
        if (HasilUjian::where('karyawan_id', $karyawan->id)->exists()) {
            return redirect()
                ->route('karyawan.index')
                ->with('error', "Karyawan {$karyawan->nama} tidak dapat dihapus karena memiliki riwayat ujian.");
        }

        $nama = $karyawan->nama;
        $karyawan->delete();

        return redirect()
            ->route('karyawan.index')
            ->with('success', "Data karyawan {$nama} berhasil dihapus.");
    }

    /** Departemen yang diketik manual otomatis masuk ke master supaya muncul di dropdown. */
    private function pastikanDepartemenAda(?string $nama): void
    {
        $nama = trim((string) $nama);
        if ($nama !== '') {
            Departemen::firstOrCreate(['nama' => $nama]);
        }
    }

    private function validateKaryawan(Request $request, ?Karyawan $karyawan = null): array
    {
        $rules = [
            'nik' => ['required', 'string', 'max:255', Rule::unique('karyawans', 'nik')->ignore($karyawan?->id)],
            'nama' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('karyawans', 'email')->ignore($karyawan?->id)],
            'jabatan' => ['nullable', 'string', 'max:255'],
            'departemen' => ['nullable', 'string', 'max:255'],
            'join_date' => ['nullable', 'date', 'before_or_equal:today'],
            'role' => ['required', Rule::in(['administrator', 'karyawan'])],
        ];

        if ($karyawan === null) {
            $rules['password'] = ['required', 'string', 'min:8', 'confirmed'];
        }

        return $request->validate($rules);
    }
}
