<?php

namespace App\Http\Controllers;

use App\Models\Departemen;
use App\Models\Karyawan;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DepartemenController extends Controller
{
    public function index()
    {
        $departemen = Departemen::orderBy('nama')->get();

        $jumlahKaryawan = Karyawan::query()
            ->selectRaw('departemen, COUNT(*) as total')
            ->whereNotNull('departemen')
            ->groupBy('departemen')
            ->pluck('total', 'departemen');

        return view('departemen.index', compact('departemen', 'jumlahKaryawan'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nama' => ['required', 'string', 'max:255', Rule::unique('departemens', 'nama')],
        ], [
            'nama.unique' => 'Departemen tersebut sudah ada.',
        ]);

        Departemen::create(['nama' => trim($data['nama'])]);

        return back()->with('success', 'Departemen berhasil ditambahkan.');
    }

    public function destroy(Departemen $departemen)
    {
        $dipakai = Karyawan::where('departemen', $departemen->nama)->count();

        if ($dipakai > 0) {
            return back()->with('error', "Departemen \"{$departemen->nama}\" masih dipakai {$dipakai} karyawan.");
        }

        $departemen->delete();

        return back()->with('success', 'Departemen dihapus.');
    }
}
