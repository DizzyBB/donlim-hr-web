@extends('layouts.karyawan')

@section('title', 'Request Training')

@section('content')
<div class="container-xl py-4">

    <div class="d-flex justify-content-between align-items-center flex-wrap mb-4">
        <div>
            <h4 class="mb-1">Request Training</h4>
            <p class="text-muted mb-0">Riwayat pengajuan training dan pemesanan ruang training kamu.</p>
        </div>
        <a href="{{ route('karyawan.training.create') }}" class="btn btn-navy">
            <i class="bi bi-plus-lg me-1"></i> Ajukan Baru
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="panel p-3">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Judul</th>
                        <th style="width:120px;">Jenis</th>
                        <th style="width:150px;">Ruangan</th>
                        <th style="width:180px;">Waktu</th>
                        <th style="width:120px;">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($riwayat as $r)
                        <tr>
                            <td>
                                {{ $r->title }}
                                <div class="small text-muted">{{ $r->type === 'internal' ? 'Internal' : 'Eksternal' }}</div>
                            </td>
                            <td>{{ $r->kind === 'training' ? 'Request Training' : 'Pesan Ruang' }}</td>
                            <td>{{ $r->room->name ?? '-' }}</td>
                            <td class="small">
                                {{ $r->start_time->format('d M Y H:i') }}<br>
                                s/d {{ $r->end_time->format('d M Y H:i') }}
                            </td>
                            <td>
                                @if($r->status === 'pending')
                                    <span class="badge bg-warning-subtle text-warning">Menunggu</span>
                                @elseif($r->status === 'approved')
                                    <span class="badge bg-success-subtle text-success">Disetujui</span>
                                @else
                                    <span class="badge bg-danger-subtle text-danger" title="{{ $r->admin_note }}">Ditolak</span>
                                @endif
                            </td>
                        </tr>
                        @if($r->status === 'rejected' && $r->admin_note)
                            <tr>
                                <td colspan="5" class="small text-danger pt-0">
                                    <i class="bi bi-info-circle me-1"></i>Alasan: {{ $r->admin_note }}
                                </td>
                            </tr>
                        @endif
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-4">Belum ada pengajuan.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{ $riwayat->links() }}
</div>
@endsection
