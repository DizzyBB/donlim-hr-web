@extends('layouts.app')

@section('title', 'Riwayat Ujian')

@section('content')
<div class="container-fluid py-4">

    <div class="mb-4">
        <h4 class="mb-1">Riwayat Ujian</h4>
        <p class="text-muted mb-0">Seluruh ujian yang telah dikerjakan beserta hasilnya.</p>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <form action="{{ route('examination.riwayat-ujian.index') }}" method="GET" class="row g-2 align-items-center">
                <div class="col-md-4">
                    <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Cari nama ujian / karyawan...">
                </div>
                <div class="col-md-3">
                    <select name="jenis" class="form-select">
                        <option value="">Semua Jenis Ujian</option>
                        <option value="perorangan" @selected(request('jenis') == 'perorangan')>Ujian Perorangan</option>
                        <option value="karyawan_baru" @selected(request('jenis') == 'karyawan_baru')>Ujian Karyawan Baru</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="status" class="form-select">
                        <option value="">Semua Status</option>
                        <option value="belum_dinilai" @selected(request('status') == 'belum_dinilai')>Belum Dinilai</option>
                        <option value="selesai_dinilai" @selected(request('status') == 'selesai_dinilai')>Sudah Dinilai</option>
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
                            <th style="width:150px;">Jenis</th>
                            <th>Karyawan</th>
                            <th style="width:150px;">Selesai Dikerjakan</th>
                            <th style="width:110px;">Nilai Total</th>
                            <th style="width:130px;">Status</th>
                            <th style="width:100px;" class="text-center">Detail</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($hasilUjian as $item)
                            <tr>
                                <td>{{ $item->ujian->nama_ujian ?? '-' }}</td>
                                <td>
                                    @if(($item->ujian->jenis ?? null) === 'perorangan')
                                        <span class="badge bg-primary-subtle text-primary">Perorangan</span>
                                    @else
                                        <span class="badge bg-info-subtle text-info">Karyawan Baru</span>
                                    @endif
                                </td>
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
                                    <a href="{{ route('examination.riwayat-ujian.show', $item) }}" class="btn btn-sm btn-outline-primary p-2">
                                        <i class="lni lni-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">Belum ada riwayat ujian.</td>
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
