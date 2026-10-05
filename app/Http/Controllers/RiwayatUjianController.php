<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\HasilUjian;
use Illuminate\Http\Request;

class RiwayatUjianController extends Controller
{
    public function index(Request $request)
    {
        $hasilUjian = HasilUjian::with(['ujian', 'karyawan'])
            ->status($request->input('status'))
            ->when($request->filled('search'), function ($q) use ($request) {
                $s = $request->input('search');
                $q->whereHas('ujian', fn ($qq) => $qq->where('nama_ujian', 'like', "%{$s}%"))
                  ->orWhereHas('karyawan', fn ($qq) => $qq->where('nama', 'like', "%{$s}%"));
            })
            ->when($request->filled('jenis'), function ($q) use ($request) {
                $q->whereHas('ujian', fn ($qq) => $qq->where('jenis', $request->input('jenis')));
            })
            ->latest('waktu_selesai')
            ->paginate(10)
            ->withQueryString();

        return view('riwayat.riwayat-ujian-index', compact('hasilUjian'));
    }

    public function show(HasilUjian $hasilUjian)
    {
        $hasilUjian->load(['ujian', 'karyawan', 'jawaban.soal']);

        $jumlahBenar = $hasilUjian->jawaban->where('tipe', 'pg')->where('benar', true)->count();
        $jumlahSalah = $hasilUjian->jawaban->where('tipe', 'pg')->where('benar', false)->count();
        $jumlahPG = $hasilUjian->jawaban->where('tipe', 'pg')->count();

        return view('riwayat.riwayat-ujian-show', compact('hasilUjian', 'jumlahBenar', 'jumlahSalah', 'jumlahPG'));
    }
}
