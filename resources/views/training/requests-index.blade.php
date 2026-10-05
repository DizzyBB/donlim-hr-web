@extends('layouts.app')

@section('title', 'Request Training')

@section('content')
<div class="container-fluid py-4">

    <div class="mb-4">
        <h4 class="mb-1">Request Training</h4>
        <p class="text-muted mb-0">Tinjau pengajuan training dan pemesanan ruang training dari karyawan.</p>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error') || $errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') ?? $errors->first() }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card mb-3">
        <div class="card-body">
            <form action="{{ route('training.requests.index') }}" method="GET" class="row g-2 align-items-center">
                <div class="col-md-4">
                    <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Cari judul / nama karyawan...">
                </div>
                <div class="col-md-3">
                    <select name="status" class="form-select">
                        <option value="">Semua Status</option>
                        <option value="pending" @selected(request('status') == 'pending')>Menunggu</option>
                        <option value="approved" @selected(request('status') == 'approved')>Disetujui</option>
                        <option value="rejected" @selected(request('status') == 'rejected')>Ditolak</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="kind" class="form-select">
                        <option value="">Semua Jenis</option>
                        <option value="training" @selected(request('kind') == 'training')>Request Training</option>
                        <option value="room" @selected(request('kind') == 'room')>Pesan Ruang</option>
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
                            <th>Judul</th>
                            <th style="width:150px;">Karyawan</th>
                            <th style="width:120px;">Jenis</th>
                            <th style="width:150px;">Ruangan</th>
                            <th style="width:170px;">Waktu</th>
                            <th style="width:110px;">Status</th>
                            <th style="width:180px;" class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($items as $item)
                            <tr>
                                <td>
                                    {{ $item->title }}
                                    <div class="small text-muted">{{ $item->type === 'internal' ? 'Internal' : 'Eksternal' }}</div>
                                </td>
                                <td>
                                    {{ $item->karyawan->nama ?? '-' }}
                                    <div class="small text-muted">{{ $item->karyawan->nik ?? '' }}</div>
                                </td>
                                <td>{{ $item->kind === 'training' ? 'Request Training' : 'Pesan Ruang' }}</td>
                                <td>{{ $item->room->name ?? '-' }}</td>
                                <td class="small">
                                    {{ $item->start_time->format('d M Y H:i') }}<br>
                                    s/d {{ $item->end_time->format('d M Y H:i') }}
                                </td>
                                <td>
                                    @if($item->status === 'pending')
                                        <span class="badge bg-warning-subtle text-warning">Menunggu</span>
                                    @elseif($item->status === 'approved')
                                        <span class="badge bg-success-subtle text-success">Disetujui</span>
                                    @else
                                        <span class="badge bg-danger-subtle text-danger">Ditolak</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($item->status === 'pending')
                                        <form action="{{ route('training.requests.approve', $item) }}" method="POST" class="d-inline"
                                              onsubmit="return confirm('Setujui pengajuan ini?')">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-success p-2" title="Setujui">
                                                <i class="lni lni-checkmark"></i>
                                            </button>
                                        </form>
                                        <button type="button" class="btn btn-sm btn-danger p-2" title="Tolak"
                                                data-bs-toggle="modal" data-bs-target="#modalTolak{{ $item->id }}">
                                            <i class="lni lni-close"></i>
                                        </button>
                                    @else
                                        <span class="text-muted small">{{ $item->reviewer->name ?? '-' }}</span>
                                    @endif
                                </td>
                            </tr>

                            @if($item->status === 'pending')
                                <div class="modal fade" id="modalTolak{{ $item->id }}" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <form action="{{ route('training.requests.reject', $item) }}" method="POST">
                                            @csrf
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Tolak Pengajuan</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <p>Tolak pengajuan <strong>{{ $item->title }}</strong>?</p>
                                                    <label class="form-label" for="admin_note{{ $item->id }}">Alasan Penolakan</label>
                                                    <textarea name="admin_note" id="admin_note{{ $item->id }}" class="form-control" rows="3" required></textarea>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                                    <button type="submit" class="btn btn-danger">Tolak Pengajuan</button>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            @endif
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">Belum ada pengajuan.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $items->links() }}
        </div>
    </div>
</div>
@endsection
