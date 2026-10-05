@extends('layouts.layouts-karyawan')

@section('title', 'Ujian Selesai')

@section('content')
<div class="container-xl py-5" style="max-width: 720px;">

    @if(session('info'))
        <div class="alert alert-info">{{ session('info') }}</div>
    @endif

    <div class="panel p-4 p-md-5 text-center">
        <i class="bi bi-check-circle-fill text-success" style="font-size:3rem;"></i>
        <h4 class="mt-3 mb-1">Ujian selesai</h4>
        <p class="text-muted">{{ $hasilUjian->ujian->nama_ujian }}</p>

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
        </div>

        @if($hasilUjian->status === 'selesai_dinilai')
            <div class="border border-success rounded-3 p-3 mb-4">
                <div class="small text-muted">Nilai kamu</div>
                <div class="display-5 fw-bold text-success">{{ $hasilUjian->nilai_total }}</div>
                <div class="small text-muted">dari 100</div>
            </div>
        @else
            <div class="alert alert-warning text-start">
                <i class="bi bi-hourglass-split me-1"></i>
                Jawabanmu sudah tersimpan. Soal jawaban singkat / uraian sedang menunggu penilaian, nilai akhir akan muncul di Riwayat Ujian setelah selesai dinilai.
            </div>
        @endif

        <a href="{{ route('karyawan.ujian.index') }}" class="btn btn-navy">Kembali ke daftar ujian</a>
    </div>
</div>
@endsection
