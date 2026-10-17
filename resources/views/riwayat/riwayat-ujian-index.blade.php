@extends('layouts.app')

@section('title', 'Riwayat Ujian')

@section('content')
<div class="container-fluid py-4">

    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
        <div>
            <h4 class="mb-1">Riwayat Ujian</h4>
            <p class="text-muted mb-0">Rekap hasil ujian karyawan aktif dan karyawan baru.</p>
        </div>
        <div class="btn-group">
            <a href="{{ route('examination.riwayat-ujian.export', array_merge(request()->query(), ['format' => 'xlsx'])) }}" class="btn btn-success"><i class="bi bi-file-earmark-excel me-1"></i> Ekspor Excel</a>
            <a href="{{ route('examination.riwayat-ujian.export', array_merge(request()->query(), ['format' => 'csv'])) }}" class="btn btn-outline-success">CSV</a>
        </div>
    </div>

    @include('partials.flash')

    @if(count($duplikat))
        <div class="alert alert-warning">
            <i class="bi bi-exclamation-triangle-fill me-1"></i>
            {{ count($duplikat) }} peserta di halaman ini terdeteksi <strong>kemungkinan duplikat</strong> (ditandai di tabel).
        </div>
    @endif

    <div class="card mb-3"><div class="card-body">
        <form method="GET" action="{{ route('examination.riwayat-ujian.index') }}" class="row g-2">
            <div class="col-md-3"><input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Nama / NIK / ujian..."></div>
            <div class="col-md-2">
                <select name="ujian_id" class="form-select">
                    <option value="">Semua ujian</option>
                    @foreach($daftarUjian as $u)<option value="{{ $u->id }}" @selected(request('ujian_id') == $u->id)>{{ $u->nama_ujian }}</option>@endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select name="jenis" class="form-select">
                    <option value="">Semua peserta</option>
                    <option value="perorangan" @selected(request('jenis') === 'perorangan')>Karyawan Aktif</option>
                    <option value="karyawan_baru" @selected(request('jenis') === 'karyawan_baru')>Karyawan Baru</option>
                </select>
            </div>
            <div class="col-md-2">
                <select name="kelulusan" class="form-select">
                    <option value="">Semua kelulusan</option>
                    <option value="lulus" @selected(request('kelulusan') === 'lulus')>Lulus</option>
                    <option value="tidak_lulus" @selected(request('kelulusan') === 'tidak_lulus')>Tidak Lulus</option>
                    <option value="pending" @selected(request('kelulusan') === 'pending')>Pending</option>
                </select>
            </div>
            <div class="col-md-3 d-flex gap-2">
                <input type="date" name="dari" value="{{ request('dari') }}" class="form-control" title="Dari tanggal">
                <input type="date" name="sampai" value="{{ request('sampai') }}" class="form-control" title="Sampai tanggal">
            </div>
            <div class="col-12 d-flex gap-2">
                <button class="btn btn-outline-secondary"><i class="bi bi-search me-1"></i> Terapkan</button>
                @if(request()->query())<a href="{{ route('examination.riwayat-ujian.index') }}" class="btn btn-outline-danger">Reset</a>@endif
            </div>
        </form>
    </div></div>

    <div class="card"><div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr><th>Peserta</th><th>Ujian</th><th>Tanggal tes</th><th>Nilai</th><th>Status</th><th>Kelulusan</th><th class="text-center" style="width:120px;">Aksi</th></tr>
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
                            <td>{{ $h->waktu_mulai?->format('d M Y') ?? '-' }}</td>
                            <td>{{ rtrim(rtrim(number_format($h->nilai_total, 2), '0'), '.') }}%</td>
                            <td>{{ $h->status_label }}</td>
                            <td>
                                @if($h->status === 'selesai_dinilai')
                                    <span class="badge {{ $h->lulus ? 'bg-success' : 'bg-danger' }}">{{ $h->kelulusan_label }}</span>
                                @else
                                    <span class="badge bg-warning text-dark">{{ $h->kelulusan_label }}</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <a href="{{ route('examination.riwayat-ujian.show', $h) }}" class="btn btn-sm btn-outline-secondary p-2" title="Detail"><i class="bi bi-eye"></i></a>
                                @if($h->status !== 'berlangsung')
                                    <a href="{{ route('examination.riwayat-ujian.pdf', $h) }}" target="_blank" class="btn btn-sm btn-outline-danger p-2" title="PDF"><i class="bi bi-file-earmark-pdf"></i></a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted py-4">Belum ada riwayat ujian.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $hasilUjian->links() }}
    </div></div>
</div>
@endsection
