@extends('layouts.app')

@section('title', 'Penilaian Ujian')

@section('content')
<div class="container-fluid py-4">

    <div class="mb-4">
        <h4 class="mb-1">Penilaian Ujian</h4>
        <p class="text-muted mb-0">Nilai jawaban esai. Pilihan Ganda &amp; Benar/Salah dihitung otomatis. Status <strong>Pending</strong> = masih ada esai belum dinilai.</p>
    </div>

    @include('partials.flash')

    @if(count($duplikat))
        <div class="alert alert-warning">
            <i class="bi bi-exclamation-triangle-fill me-1"></i>
            {{ count($duplikat) }} peserta di halaman ini terdeteksi <strong>kemungkinan duplikat</strong> (ditandai di tabel).
        </div>
    @endif

    <div class="card mb-3">
        <div class="card-body">
            <form action="{{ route('examination.penilaian.index') }}" method="GET" class="row g-2 align-items-center">
                <div class="col-md-5">
                    <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Cari ujian / nama / NIK peserta...">
                </div>
                <div class="col-md-3">
                    <select name="status" class="form-select">
                        <option value="belum_dinilai" @selected($status == 'belum_dinilai')>Pending (belum dinilai)</option>
                        <option value="selesai_dinilai" @selected($status == 'selesai_dinilai')>Sudah dinilai</option>
                    </select>
                </div>
                <div class="col-md-2"><button class="btn btn-outline-secondary w-100"><i class="bi bi-search me-1"></i> Cari</button></div>
            </form>
        </div>
    </div>

    <div class="card"><div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr><th>Peserta</th><th>Ujian</th><th style="width:150px;">Selesai</th><th style="width:130px;">Nilai</th><th style="width:140px;">Status</th><th style="width:110px;" class="text-center">Aksi</th></tr>
                </thead>
                <tbody>
                    @forelse($hasilUjian as $h)
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $h->nama_peserta }}
                                    @if(isset($duplikat[$h->id]))
                                        <span class="badge bg-warning text-dark" title="{{ $duplikat[$h->id]['pesan'] }}"><i class="bi bi-exclamation-triangle-fill"></i> Duplikat?</span>
                                    @endif
                                </div>
                                <div class="small text-muted">{{ $h->is_peserta_baru ? 'Karyawan Baru' : $h->nomor_peserta }} &middot; {{ $h->departemen_peserta ?? '-' }}</div>
                            </td>
                            <td>{{ $h->ujian->nama_ujian ?? '-' }}</td>
                            <td>{{ $h->waktu_selesai?->format('d M Y H:i') ?? '-' }}</td>
                            <td>{{ rtrim(rtrim(number_format($h->nilai_total, 2), '0'), '.') }}%
                                @if($h->status !== 'selesai_dinilai')<span class="small text-muted">(sementara)</span>@endif</td>
                            <td>
                                @if($h->status === 'selesai_dinilai')
                                    <span class="badge {{ $h->lulus ? 'bg-success' : 'bg-danger' }}">{{ $h->kelulusan_label }}</span>
                                @else
                                    <span class="badge bg-warning text-dark">Pending</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <a href="{{ route('examination.penilaian.show', $h) }}" class="btn btn-sm btn-primary">{{ $h->status === 'selesai_dinilai' ? 'Lihat / Koreksi' : 'Nilai' }}</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-4">Tidak ada data.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $hasilUjian->links() }}
    </div></div>
</div>
@endsection
