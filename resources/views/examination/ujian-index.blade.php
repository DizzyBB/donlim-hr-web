@extends('layouts.app')

@section('title', 'Manajemen Ujian')

@section('content')
<div class="container-fluid py-4">

    <div class="d-flex justify-content-between align-items-center flex-wrap mb-4">
        <div>
            <h4 class="mb-1">Manajemen Ujian</h4>
            <p class="text-muted mb-0">Daftar ujian perorangan dan ujian karyawan baru.</p>
        </div>
        <a href="{{ route('examination.ujian.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i> Buat Ujian
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card mb-3">
        <div class="card-body">
            <form action="{{ route('examination.ujian.index') }}" method="GET" class="row g-2 align-items-center">
                <div class="col-md-5">
                    <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Cari nama ujian...">
                </div>
                <div class="col-md-3">
                    <select name="jenis" class="form-select">
                        <option value="">Semua Jenis</option>
                        <option value="perorangan" @selected(request('jenis') == 'perorangan')>Ujian Perorangan</option>
                        <option value="karyawan_baru" @selected(request('jenis') == 'karyawan_baru')>Ujian Karyawan Baru</option>
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
                            <th style="width:180px;">Karyawan</th>
                            <th style="width:110px;">Status</th>
                            <th style="width:110px;">Durasi</th>
                            <th style="width:110px;">Passing Grade</th>
                            <th style="width:90px;">Soal</th>
                            <th style="width:140px;">Pengacakan</th>
                            <th style="width:170px;" class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($ujian as $item)
                            <tr>
                                <td>{{ $item->nama_ujian }}</td>
                                <td>
                                    @if($item->jenis === 'perorangan')
                                        <span class="badge bg-primary-subtle text-primary">Perorangan</span>
                                    @else
                                        <span class="badge bg-info-subtle text-info">Karyawan Baru</span>
                                    @endif
                                </td>
                                <td>{{ $item->karyawan->nama ?? '-' }}</td>
                                <td>
                                    <form action="{{ route('examination.ujian.status', $item) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="badge border-0 {{ $item->is_aktif ? 'bg-success' : 'bg-secondary' }}"
                                                title="Klik untuk {{ $item->is_aktif ? 'menonaktifkan' : 'mengaktifkan' }}">
                                            {{ $item->is_aktif ? 'Aktif' : 'Nonaktif' }}
                                        </button>
                                    </form>
                                    <div class="small text-muted">Bisa diakses kapan saja</div>
                                </td>
                                <td>{{ $item->durasi_label }}</td>
                                <td>{{ rtrim(rtrim(number_format($item->passing_grade, 2), '0'), '.') }}%</td>
                                <td>{{ $item->soal_count }} soal</td>
                                <td>
                                    @if($item->acak_soal)<span class="badge bg-light text-dark border">Acak Soal</span>@endif
                                    @if($item->acak_jawaban)<span class="badge bg-light text-dark border">Acak Jawaban</span>@endif
                                </td>
                                <td class="text-center">
                                    <a href="{{ route('examination.ujian.token.index', $item) }}" class="btn btn-sm btn-outline-primary p-2 position-relative" title="Kelola token">
                                        <i class="lni lni-key"></i>
                                        @if($item->token_aktif_count > 0)
                                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-success">{{ $item->token_aktif_count }}</span>
                                        @endif
                                    </a>
                                    <a href="{{ route('examination.ujian.edit', $item) }}" class="btn btn-sm btn-outline-secondary p-2" title="Edit">
                                         <i class="lni lni-pencil-alt"></i>
                                    </a>
                                    <form action="{{ route('examination.ujian.destroy', $item) }}" method="POST" class="d-inline"
                                          onsubmit="return confirm('Hapus ujian &quot;{{ $item->nama_ujian }}&quot;? Tindakan ini tidak bisa dibatalkan.')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger p-2" title="Hapus">
                                             <i class="lni lni-trash-can"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted py-4">Belum ada ujian dibuat.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $ujian->links() }}
        </div>
    </div>
</div>
@endsection
