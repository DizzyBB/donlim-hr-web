@extends('layouts.layouts-karyawan')

@section('title', 'Ujian Saya')

@section('content')
<div class="container-xl py-4">

    <div class="mb-4">
        <h4 class="mb-1">Halo, {{ auth('karyawan')->user()->nama }}</h4>
        <p class="text-muted mb-0">Berikut ujian yang tersedia untukmu saat ini.</p>
    </div>

    @include('partials.flash')

    {{-- Masuk ujian lanjutan dengan token (terikat akun ini) --}}
    <div class="panel p-3 p-md-4 mb-4">
        <h6 class="fw-semibold mb-1"><i class="bi bi-key me-1"></i> Masuk Ujian dengan Token</h6>
        <p class="small text-muted mb-3">Masukkan token dari HRD. Token hanya berlaku untuk akunmu dan hanya bisa dipakai satu kali.</p>
        <form method="POST" action="{{ route('karyawan.ujian.token') }}" class="row g-2">
            @csrf
            <div class="col-sm-6 col-md-4">
                <input type="text" name="token" value="{{ old('token') }}" maxlength="16" autocomplete="off" placeholder="XXXX-XXXX"
                       class="form-control text-uppercase @error('token') is-invalid @enderror" style="letter-spacing:.2em;font-weight:700;" required>
                @error('token')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-auto"><button type="submit" class="btn btn-navy">Mulai</button></div>
        </form>
    </div>

    {{-- Ujian yang sedang dikerjakan --}}
    @foreach($sedangBerlangsung as $h)
        <div class="alert alert-warning d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <i class="bi bi-hourglass-split me-1"></i>
                Kamu masih mengerjakan <strong>{{ $h->ujian->nama_ujian }}</strong>.
                Sisa waktu: <strong>{{ gmdate('H:i:s', max(0, $h->sisaDetik())) }}</strong>
            </div>
            <a href="{{ route('karyawan.ujian.kerjakan', $h) }}" class="btn btn-sm btn-copper">Lanjutkan</a>
        </div>
    @endforeach

    {{-- Ujian tersedia --}}
    <h6 class="fw-semibold mb-3">Ujian Tersedia</h6>
    <div class="row g-3 mb-5">
        @forelse($tersedia as $ujian)
            <div class="col-md-6 col-lg-4">
                <div class="panel h-100 p-3 d-flex flex-column">
                    <div class="mb-2">
                        @if($ujian->jenis === 'perorangan')
                            <span class="badge bg-primary-subtle text-primary">Ujian Perorangan</span>
                        @else
                            <span class="badge bg-info-subtle text-info">Ujian Karyawan Baru</span>
                        @endif
                    </div>
                    <h5 class="mb-3">{{ $ujian->nama_ujian }}</h5>
                    <ul class="list-unstyled small text-muted mb-3">
                        <li><i class="bi bi-clock me-2"></i>Bisa diakses kapan saja (dengan token)</li>
                        <li><i class="bi bi-hourglass me-2"></i>Durasi pengerjaan {{ $ujian->durasi_label }}</li>
                        <li><i class="bi bi-list-check me-2"></i>{{ $ujian->soal_count }} soal</li>
                    </ul>
                    <p class="small text-muted mb-0">Minta token ke HRD, lalu masukkan di kotak token di atas. Waktu mulai dihitung setelah token dipakai; ujian hanya bisa dikerjakan satu kali.</p>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="panel text-center text-muted py-5">
                    <i class="bi bi-calendar-x fs-2 d-block mb-2"></i>
                    Tidak ada ujian yang tersedia saat ini.
                </div>
            </div>
        @endforelse
    </div>

    {{-- Riwayat --}}
    <h6 class="fw-semibold mb-3">Riwayat Ujian Saya</h6>
    <div class="panel p-3">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Ujian</th>
                        <th style="width:170px;">Selesai</th>
                        <th style="width:180px;">Status</th>
                        <th style="width:90px;"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($riwayat as $r)
                        <tr>
                            <td>{{ $r->ujian->nama_ujian ?? '-' }}</td>
                            <td>{{ $r->waktu_selesai?->format('d M Y H:i') ?? '-' }}</td>
                            <td>
                                @if($r->status === 'selesai_dinilai')
                                    <span class="badge {{ $r->lulus ? 'bg-success' : 'bg-danger' }}">{{ $r->nilai_total }}% &middot; {{ $r->kelulusan_label }}</span>
                                @else
                                    <span class="badge bg-warning text-dark">Pending</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <a href="{{ route('karyawan.ujian.hasil', $r) }}" class="btn btn-sm btn-outline-secondary">Lihat</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-muted py-3">Belum ada ujian yang dikerjakan.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

