@extends('layouts.app')

@section('title', 'Master Departemen')

@section('content')
<div class="container-fluid py-4" style="max-width: 760px;">
    <h4 class="mb-1">Master Departemen</h4>
    <p class="text-muted">Daftar ini menjadi sumber dropdown departemen (termasuk form karyawan baru saat ujian).</p>
    @include('partials.flash')

    <div class="card mb-3"><div class="card-body">
        <form method="POST" action="{{ route('departemen.store') }}" class="row g-2">
            @csrf
            <div class="col"><input type="text" name="nama" class="form-control @error('nama') is-invalid @enderror" placeholder="Nama departemen baru" value="{{ old('nama') }}" required>
                @error('nama')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            <div class="col-auto"><button class="btn btn-primary" type="submit">Tambah</button></div>
        </form>
    </div></div>

    <div class="card"><div class="card-body p-0">
        <table class="table mb-0 align-middle">
            <thead class="table-light"><tr><th>Departemen</th><th style="width:130px;">Karyawan</th><th style="width:90px;"></th></tr></thead>
            <tbody>
                @forelse($departemen as $d)
                    <tr>
                        <td>{{ $d->nama }}</td>
                        <td>{{ $jumlahKaryawan[$d->nama] ?? 0 }}</td>
                        <td class="text-end">
                            <form method="POST" action="{{ route('departemen.destroy', $d) }}" onsubmit="return confirm('Hapus departemen ini?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger">Hapus</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="text-center text-muted py-4">Belum ada departemen.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div></div>
</div>
@endsection
