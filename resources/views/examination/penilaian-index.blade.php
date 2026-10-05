@extends('layouts.app')

@section('title', 'Penilaian Ujian')

@section('content')
<div class="container-fluid py-4">

    <div class="mb-4">
        <h4 class="mb-1">Penilaian Ujian</h4>
        <p class="text-muted mb-0">Nilai jawaban uraian &amp; jawaban singkat. Pilihan ganda dihitung otomatis.</p>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card mb-3">
        <div class="card-body">
            <form action="{{ route('examination.penilaian.index') }}" method="GET" class="row g-2 align-items-center">
                <div class="col-md-5">
                    <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Cari nama ujian / karyawan...">
                </div>
                <div class="col-md-3">
                    <select name="status" class="form-select">
                        <option value="belum_dinilai" @selected($status == 'belum_dinilai')>Belum Dinilai</option>
                        <option value="selesai_dinilai" @selected($status == 'selesai_dinilai')>Sudah Dinilai</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-outline-secondary w-100">
                        <i class="bi bi-search me-1"></i> Cari
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Nama Ujian</th>
                            <th>Karyawan</th>
                            <th style="width:150px;">Selesai Dikerjakan</th>
                            <th style="width:110px;">Nilai Total</th>
                            <th style="width:130px;">Status</th>
                            <th style="width:110px;" class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($hasilUjian as $item)
                            <tr>
                                <td>{{ $item->ujian->nama_ujian ?? '-' }}</td>
                                <td>{{ $item->karyawan->nama ?? '-' }}</td>
                                <td>{{ $item->waktu_selesai?->format('d M Y H:i') ?? '-' }}</td>
                                <td>
                                    @if($item->status === 'selesai_dinilai')
                                        <span class="fw-semibold">{{ $item->nilai_total }}</span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    @if($item->status === 'belum_dinilai')
                                        <span class="badge bg-warning-subtle text-warning">Belum Dinilai</span>
                                    @else
                                        <span class="badge bg-success-subtle text-success">Sudah Dinilai</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <a href="{{ route('examination.penilaian.show', $item) }}" class="btn btn-sm btn-primary">
                                        {{ $item->status === 'belum_dinilai' ? 'Nilai' : 'Lihat' }}
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">
                                    @if($status === 'belum_dinilai')
                                        Tidak ada ujian yang perlu dinilai saat ini.
                                    @else
                                        Belum ada ujian yang sudah dinilai.
                                    @endif
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $hasilUjian->links() }}
        </div>
    </div>
</div>
@endsection
