<?php

namespace App\Http\Controllers;

use App\Models\HasilUjian;
use App\Models\Karyawan;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class KaryawanController extends Controller
{
    public function index(Request $request)
    {
        $karyawan = Karyawan::query()
            ->when($request->filled('search'), fn ($q) => $q->where('nama', 'like', '%' . $request->input('search') . '%'))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('karyawan.index', compact('karyawan'));
    }

    public function create()
    {
        return view('karyawan.edit');
    }

    public function store(Request $request)
    {
        $validated = $this->validateKaryawan($request);
        Karyawan::create($validated);

        return redirect()
            ->route('karyawan.index')
            ->with('success', "Data karyawan {$validated['nama']} berhasil ditambahkan.");
    }

    public function edit(Karyawan $karyawan)
    {
        return view('karyawan.edit', compact('karyawan'));
    }

    public function update(Request $request, Karyawan $karyawan)
    {
        $validated = $this->validateKaryawan($request, $karyawan);

        $karyawan->update($validated);

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

    private function validateKaryawan(Request $request, ?Karyawan $karyawan = null): array
    {
        $rules = [
            'nik' => ['required', 'string', 'max:255', Rule::unique('karyawans', 'nik')->ignore($karyawan?->id)],
            'nama' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('karyawans', 'email')->ignore($karyawan?->id)],
            'jabatan' => ['nullable', 'string', 'max:255'],
            'departemen' => ['nullable', 'string', 'max:255'],
            'role' => ['required', Rule::in(['administrator', 'karyawan'])],
        ];

        if ($karyawan === null) {
            $rules['password'] = ['required', 'string', 'min:8', 'confirmed'];
        }

        return $request->validate($rules);
    }
}
