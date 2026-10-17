@extends('layouts.app')

@section('title', 'Token Ujian')

@section('content')
<div class="container-fluid py-4">

    <div class="d-flex justify-content-between align-items-center flex-wrap mb-4 gap-2">
        <div>
            <h4 class="mb-1">Token Ujian</h4>
            <p class="text-muted mb-0">
                {{ $ujian->nama_ujian }} &middot;
                @if($ujian->jenis === 'karyawan_baru')
                    <span class="badge bg-info-subtle text-info">Karyawan Baru (token global, sekali pakai)</span>
                @else
                    <span class="badge bg-primary-subtle text-primary">Perorangan (terikat akun {{ $ujian->karyawan->nama ?? '-' }})</span>
                @endif
                @unless($ujian->is_aktif)
                    <span class="badge bg-secondary">Ujian nonaktif</span>
                @endunless
            </p>
        </div>
        <a href="{{ route('examination.ujian.index') }}" class="btn btn-outline-secondary">Kembali</a>
    </div>

    @foreach(['success' => 'success', 'error' => 'danger'] as $kunci => $warna)
        @if(session($kunci))
            <div class="alert alert-{{ $warna }} alert-dismissible fade show">{{ session($kunci) }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
    @endforeach

    @if(count($tokenBaru))
        <div class="alert alert-warning">
            <div class="fw-semibold mb-2">Token baru &mdash; salin sekarang dan berikan ke peserta:</div>
            <div class="d-flex flex-wrap gap-2">
                @foreach($tokenBaru as $kode)
                    <code class="fs-5 px-2 py-1 bg-white border rounded">{{ $kode }}</code>
                @endforeach
            </div>
            <div class="small mt-2">
                @if($ujian->jenis === 'karyawan_baru')
                    Peserta membuka <strong>{{ route('peserta.token') }}</strong> lalu memasukkan token. Tiap token hanya bisa dipakai satu kali.
                @else
                    Karyawan login lalu memasukkan token di halaman Ujian Saya. Token ini hanya berlaku untuk akun tersebut.
                @endif
            </div>
        </div>
    @endif

    <div class="row g-3 mb-3">
        <div class="col-lg-5">
            <div class="card h-100">
                <div class="card-body">
                    <h6 class="mb-3">Buat Token</h6>
                    <form method="POST" action="{{ route('examination.ujian.token.store', $ujian) }}" class="row g-2 align-items-end">
                        @csrf
                        @if($ujian->jenis === 'karyawan_baru')
                            <div class="col-6">
                                <label class="form-label" for="jumlah">Jumlah token</label>
                                <input type="number" id="jumlah" name="jumlah" class="form-control @error('jumlah') is-invalid @enderror" value="{{ old('jumlah', 1) }}" min="1" max="100">
                                @error('jumlah')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-6"><button class="btn btn-primary w-100" type="submit">Buat Token</button></div>
                        @else
                            <div class="col-12">
                                <p class="small text-muted mb-2">Satu token untuk {{ $ujian->karyawan->nama ?? '-' }}. Token tidak bisa dipakai akun lain.</p>
                                <button class="btn btn-primary" type="submit">Buat Token untuk {{ $ujian->karyawan->nama ?? '-' }}</button>
                            </div>
                        @endif
                    </form>
                </div>
            </div>
        </div>
        <div class="col-lg-7">
            <div class="card h-100">
                <div class="card-body">
                    <h6 class="mb-3">Ringkasan</h6>
                    <div class="d-flex gap-4">
                        <div><div class="fs-4 fw-bold text-success">{{ $ringkasan['aktif'] ?? 0 }}</div><div class="small text-muted">Aktif</div></div>
                        <div><div class="fs-4 fw-bold">{{ $ringkasan['terpakai'] ?? 0 }}</div><div class="small text-muted">Terpakai</div></div>
                        <div><div class="fs-4 fw-bold text-secondary">{{ $ringkasan['kedaluwarsa'] ?? 0 }}</div><div class="small text-muted">Kedaluwarsa</div></div>
                    </div>
                    <form method="GET" class="mt-3">
                        <select name="status" class="form-select form-select-sm w-auto d-inline-block" onchange="this.form.submit()">
                            <option value="">Semua status</option>
                            @foreach(['aktif' => 'Aktif', 'terpakai' => 'Terpakai', 'kedaluwarsa' => 'Kedaluwarsa'] as $v => $l)
                                <option value="{{ $v }}" @selected(request('status') === $v)>{{ $l }}</option>
                            @endforeach
                        </select>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Token</th><th>Tipe</th><th>Status</th><th>Dipakai oleh</th><th>Dipakai pada</th><th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($tokens as $t)
                            <tr>
                                <td><code class="fs-6">{{ $t->kode_tampil }}</code></td>
                                <td>{{ $t->tipe_label }}</td>
                                <td>
                                    @if($t->status === 'aktif')<span class="badge bg-success">Aktif</span>
                                    @elseif($t->status === 'terpakai')<span class="badge bg-dark">Terpakai</span>
                                    @else<span class="badge bg-secondary">Kedaluwarsa</span>@endif
                                </td>
                                <td>
                                    @if($t->hasilUjian)
                                        <a href="{{ route('examination.riwayat-ujian.show', $t->hasilUjian) }}">{{ $t->hasilUjian->nama_peserta }}</a>
                                    @elseif($t->karyawan)
                                        {{ $t->karyawan->nama }} <span class="text-muted small">(terikat)</span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>{{ $t->digunakan_pada?->format('d M Y H:i') ?? '-' }}</td>
                                <td class="text-end">
                                    @if($t->status === 'aktif')
                                        <form method="POST" action="{{ route('examination.ujian.token.kedaluwarsa', [$ujian, $t]) }}" class="d-inline"
                                              onsubmit="return confirm('Nonaktifkan token ini?')">
                                            @csrf
                                            <button class="btn btn-sm btn-outline-danger">Nonaktifkan</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted py-4">Belum ada token.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $tokens->links() }}
        </div>
    </div>
</div>
@endsection
