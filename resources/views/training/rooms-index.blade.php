@extends('layouts.app')

@section('title', 'Ruang Training')

@section('content')
<div class="container-fluid py-4">

    <div class="d-flex justify-content-between align-items-center flex-wrap mb-4">
        <div>
            <h4 class="mb-1">Ruang Training</h4>
            <p class="text-muted mb-0">Kelola daftar ruangan yang bisa dipesan karyawan.</p>
        </div>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalTambahRoom">
            <i class="bi bi-plus-lg me-1"></i> Tambah Ruang
        </button>
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

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Nama Ruang</th>
                            <th style="width:120px;">Kapasitas</th>
                            <th>Lokasi</th>
                            <th style="width:100px;">Status</th>
                            <th style="width:100px;">Dipakai</th>
                            <th style="width:120px;" class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rooms as $room)
                            <tr>
                                <td>{{ $room->name }}</td>
                                <td>{{ $room->capacity ?? '-' }}</td>
                                <td>{{ $room->location ?? '-' }}</td>
                                <td>
                                    @if($room->is_active)
                                        <span class="badge bg-success-subtle text-success">Aktif</span>
                                    @else
                                        <span class="badge bg-secondary-subtle text-secondary">Nonaktif</span>
                                    @endif
                                </td>
                                <td>{{ $room->training_requests_count }}x</td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-sm btn-outline-secondary" title="Edit"
                                            data-bs-toggle="modal" data-bs-target="#modalEditRoom{{ $room->id }}">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <form action="{{ route('training.rooms.destroy', $room) }}" method="POST" class="d-inline"
                                          onsubmit="return confirm('Hapus ruang &quot;{{ $room->name }}&quot;?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Hapus">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>

                            {{-- Modal Edit --}}
                            <div class="modal fade" id="modalEditRoom{{ $room->id }}" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog">
                                    <form action="{{ route('training.rooms.update', $room) }}" method="POST">
                                        @csrf
                                        @method('PUT')
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Edit Ruang</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="mb-3">
                                                    <label class="form-label">Nama Ruang</label>
                                                    <input type="text" name="name" class="form-control" value="{{ $room->name }}" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">Kapasitas</label>
                                                    <input type="number" name="capacity" class="form-control" value="{{ $room->capacity }}" min="1">
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">Lokasi</label>
                                                    <input type="text" name="location" class="form-control" value="{{ $room->location }}">
                                                </div>
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" role="switch" name="is_active" value="1"
                                                           id="isActive{{ $room->id }}" {{ $room->is_active ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="isActive{{ $room->id }}">Aktif (tampil di pilihan karyawan)</label>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                                <button type="submit" class="btn btn-primary">Simpan</button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">Belum ada ruang training.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $rooms->links() }}
        </div>
    </div>
</div>

{{-- Modal Tambah --}}
<div class="modal fade" id="modalTambahRoom" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form action="{{ route('training.rooms.store') }}" method="POST">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Ruang</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nama Ruang</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Kapasitas</label>
                        <input type="number" name="capacity" class="form-control" min="1">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Lokasi</label>
                        <input type="text" name="location" class="form-control">
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" role="switch" name="is_active" value="1" id="isActiveBaru" checked>
                        <label class="form-check-label" for="isActiveBaru">Aktif (tampil di pilihan karyawan)</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
