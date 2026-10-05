@extends('layouts.app')

@section('title', 'Detail Hasil Ujian')

@section('content')
<div class="container-fluid py-4">

    <div class="d-flex justify-content-between align-items-center flex-wrap mb-4">
        <div>
            <h4 class="mb-1">{{ $hasilUjian->ujian->nama_ujian ?? '-' }}</h4>
            <p class="text-muted mb-0">
                Peserta: <strong>{{ $hasilUjian->karyawan->nama ?? '-' }}</strong>
                ({{ $hasilUjian->karyawan->nik ?? '-' }})
            </p>
        </div>
        <a href="{{ route('examination.riwayat-ujian.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Kembali
        </a>
    </div>

    {{-- Ringkasan --}}
    <div class="row g-3 mb-4">
        <div class="col-sm-3">
            <div class="card text-center h-100">
                <div class="card-body">
                    <div class="text-muted small">Jawaban Benar (PG)</div>
                    <div class="fs-4 fw-bold text-success">{{ $jumlahBenar }} <span class="fs-6 text-muted">/ {{ $jumlahPG }}</span></div>
                </div>
            </div>
        </div>
        <div class="col-sm-3">
            <div class="card text-center h-100">
                <div class="card-body">
                    <div class="text-muted small">Jawaban Salah (PG)</div>
                    <div class="fs-4 fw-bold text-danger">{{ $jumlahSalah }} <span class="fs-6 text-muted">/ {{ $jumlahPG }}</span></div>
                </div>
            </div>
        </div>
        <div class="col-sm-3">
            <div class="card text-center h-100">
                <div class="card-body">
                    <div class="text-muted small">Waktu Pengerjaan</div>
                    <div class="small fw-semibold mt-1">
                        {{ $hasilUjian->waktu_mulai?->format('d M Y H:i') ?? '-' }}
                        <br>s/d {{ $hasilUjian->waktu_selesai?->format('H:i') ?? '-' }}
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-3">
            <div class="card text-center h-100 border-success">
                <div class="card-body">
                    <div class="text-muted small">Nilai Total</div>
                    <div class="fs-3 fw-bold text-success">{{ $hasilUjian->nilai_total }}</div>
                    <div class="small text-muted">PG: {{ $hasilUjian->nilai_pilihan_ganda }} + Manual: {{ $hasilUjian->nilai_manual }}</div>
                </div>
            </div>
        </div>
    </div>

    @if($hasilUjian->status === 'belum_dinilai')
        <div class="alert alert-warning d-flex justify-content-between align-items-center">
            <span><i class="bi bi-exclamation-triangle me-1"></i> Ujian ini belum dinilai sepenuhnya (uraian/jawaban singkat menunggu penilaian manual).</span>
            <a href="{{ route('examination.penilaian.show', $hasilUjian) }}" class="btn btn-sm btn-warning">Nilai Sekarang</a>
        </div>
    @endif

    {{-- Detail per soal --}}
    <div class="card">
        <div class="card-header bg-white">
            <h6 class="mb-0">Detail Jawaban</h6>
        </div>
        <div class="card-body">
            @foreach($hasilUjian->jawaban as $i => $j)
                <div class="border rounded p-3 mb-3">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <span class="text-muted small">Soal {{ $i + 1 }}</span>
                        <span class="badge text-bg-light border">
                            {{ ['pg' => 'Pilihan Ganda', 'bs' => 'Jawaban Singkat', 'ur' => 'Uraian'][$j->tipe] ?? $j->tipe }}
                        </span>
                    </div>

                    <p class="fw-semibold mb-2">{{ $j->soal->pertanyaan ?? '-' }}</p>

                    @if($j->tipe === 'pg')
                        <div class="row g-2 small mb-2">
                            <div class="col-md-6">
                                <span class="text-muted">Jawaban peserta:</span>
                                <span class="{{ $j->benar ? 'text-success fw-semibold' : 'text-danger fw-semibold' }}">
                                    {{ $j->jawaban_pilihan_label ?? '(tidak dijawab)' }}
                                </span>
                            </div>
                            @if(! $j->benar && $j->soal && $j->soal->jawaban_index !== null)
                                <div class="col-md-6">
                                    <span class="text-muted">Kunci jawaban:</span>
                                    <span class="text-success fw-semibold">
                                        {{ chr(65 + $j->soal->jawaban_index) }}. {{ $j->soal->opsi[$j->soal->jawaban_index] ?? '-' }}
                                    </span>
                                </div>
                            @endif
                        </div>
                        <div>
                            @if($j->benar)
                                <span class="badge bg-success"><i class="bi bi-check-lg"></i> Benar</span>
                            @else
                                <span class="badge bg-danger"><i class="bi bi-x-lg"></i> Salah</span>
                            @endif
                            <span class="badge bg-light text-dark border ms-1">Skor: {{ $j->skor }} / 5</span>
                        </div>
                    @else
                        <div class="mb-2">
                            <span class="text-muted small">Jawaban peserta:</span>
                            <div class="border rounded p-2 bg-light small">{{ $j->jawaban_teks ?: '(tidak dijawab)' }}</div>
                        </div>
                        @if($j->catatan_penilai)
                            <div class="mb-2">
                                <span class="text-muted small">Catatan penilai:</span>
                                <div class="small fst-italic text-muted">{{ $j->catatan_penilai }}</div>
                            </div>
                        @endif
                        <div>
                            @if($j->skor !== null)
                                @php $maxSkorRiwayat = $j->tipe === 'ur' ? 20 : 10; @endphp
                                <span class="badge bg-light text-dark border">Skor: {{ $j->skor }} / {{ $maxSkorRiwayat }}</span>
                            @else
                                <span class="badge bg-warning-subtle text-warning">Belum dinilai</span>
                            @endif
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
</div>
@endsection
