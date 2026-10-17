@extends('layouts.layouts-karyawan')

@section('title', 'Ujian Selesai')
@if($pesertaBaru)
    @section('hide_topbar', '1')
@endif

@section('content')
<div class="container-xl py-5" style="max-width: 720px;">

    @if(session('info'))
        <div class="alert alert-info">{{ session('info') }}</div>
    @endif

    <div class="panel p-4 p-md-5 text-center">
        <i class="bi bi-check-circle-fill text-success" style="font-size:3rem;"></i>
        <h4 class="mt-3 mb-1">Ujian selesai</h4>
        <p class="text-muted">{{ $hasilUjian->ujian->nama_ujian }} &middot; {{ $hasilUjian->nama_peserta }}</p>

        <div class="row g-3 my-4 text-start">
            <div class="col-sm-6">
                <div class="border rounded-3 p-3 h-100">
                    <div class="small text-muted">Soal terjawab</div>
                    <div class="fs-5 fw-semibold">{{ $terjawab }} dari {{ $totalSoal }}</div>
                </div>
            </div>
            <div class="col-sm-6">
                <div class="border rounded-3 p-3 h-100">
                    <div class="small text-muted">Dikumpulkan pada</div>
                    <div class="fs-5 fw-semibold">{{ $hasilUjian->waktu_selesai?->format('d M Y H:i') ?? '-' }}</div>
                </div>
            </div>
            @if($jumlahAuto > 0)
                <div class="col-12">
                    <div class="border rounded-3 p-3">
                        <div class="small text-muted">Pilihan Ganda &amp; Benar/Salah</div>
                        <div class="fs-5 fw-semibold">{{ $jumlahBenar }} benar &middot; {{ $jumlahAuto - $jumlahBenar }} salah / kosong dari {{ $jumlahAuto }} soal</div>
                    </div>
                </div>
            @endif
        </div>

        @if($hasilUjian->status === 'selesai_dinilai')
            <div class="border {{ $hasilUjian->lulus ? 'border-success' : 'border-danger' }} rounded-3 p-3 mb-4">
                <div class="small text-muted">Nilai akhir</div>
                <div class="display-5 fw-bold {{ $hasilUjian->lulus ? 'text-success' : 'text-danger' }}">{{ rtrim(rtrim(number_format($hasilUjian->nilai_total, 2), '0'), '.') }}%</div>
                <div class="mt-1">
                    <span class="badge fs-6 {{ $hasilUjian->lulus ? 'bg-success' : 'bg-danger' }}">{{ $hasilUjian->kelulusan_label }}</span>
                    <span class="small text-muted ms-2">Passing grade {{ rtrim(rtrim(number_format($hasilUjian->passing_grade, 2), '0'), '.') }}%</span>
                </div>
            </div>
        @else
            <div class="border border-warning rounded-3 p-3 mb-3">
                <div class="small text-muted">Skor sementara (belum final)</div>
                <div class="display-6 fw-bold text-warning-emphasis">{{ rtrim(rtrim(number_format($hasilUjian->nilai_pilihan_ganda, 2), '0'), '.') }}%</div>
                <div class="small text-muted">dari soal Pilihan Ganda &amp; Benar/Salah</div>
            </div>
            <div class="alert alert-warning text-start">
                <span class="badge bg-warning text-dark me-1">Pending</span>
                {{ $jumlahEsai }} soal esai menunggu penilaian HRD. Nilai akhir dan status Lulus / Tidak Lulus ditentukan setelah seluruh esai dinilai.
            </div>
        @endif

        <div class="d-flex gap-2 justify-content-center flex-wrap">
            <a href="{{ $urlPdf }}" class="btn btn-outline-secondary" target="_blank"><i class="bi bi-file-earmark-pdf me-1"></i> Cetak / Unduh PDF</a>
            <a href="{{ $urlKembali }}" class="btn btn-navy">{{ $pesertaBaru ? 'Selesai' : 'Kembali ke daftar ujian' }}</a>
        </div>
    </div>
</div>
@endsection
