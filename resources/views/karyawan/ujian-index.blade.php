@extends('layouts.layouts-karyawan')

@section('title', 'Ujian Saya')

@section('content')
<div class="container-xl py-4">

    <div class="mb-4">
        <h4 class="mb-1">Halo, {{ auth('karyawan')->user()->nama }}</h4>
        <p class="text-muted mb-0">Berikut ujian yang tersedia untukmu saat ini.</p>
    </div>

    @foreach(['success' => 'success', 'error' => 'danger', 'info' => 'info'] as $kunci => $warna)
        @if(session($kunci))
            <div class="alert alert-{{ $warna }} alert-dismissible fade show" role="alert">
                {{ session($kunci) }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
    @endforeach

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
                        <li><i class="bi bi-clock me-2"></i>Berlaku {{ substr($ujian->jam_mulai, 0, 5) }} - {{ substr($ujian->jam_selesai, 0, 5) }}</li>
                        <li><i class="bi bi-hourglass me-2"></i>Durasi pengerjaan {{ $ujian->durasi_label }}</li>
                        <li><i class="bi bi-list-check me-2"></i>{{ $ujian->soal_count }} soal</li>
                    </ul>
                    <p class="small text-muted">Waktu pengerjaan mulai dihitung saat kamu menekan tombol Mulai. Ujian hanya bisa dikerjakan satu kali.</p>
                    <form action="{{ route('karyawan.ujian.mulai', $ujian) }}" method="POST" class="mt-auto"
                          onsubmit="return confirm('Mulai ujian &quot;{{ $ujian->nama_ujian }}&quot; sekarang? Waktu langsung berjalan.')">
                        @csrf
                        <button type="submit" class="btn btn-navy w-100">
                            <i class="bi bi-play-fill me-1"></i> Mulai Ujian
                        </button>
                    </form>
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
                                    <span class="badge bg-success-subtle text-success">Nilai: {{ $r->nilai_total }}</span>
                                @else
                                    <span class="badge bg-warning-subtle text-warning">Menunggu penilaian</span>
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

@push('scripts')
<script>
    // Muat ulang tiap 60 detik supaya ujian yang masa berlakunya baru mulai / sudah berakhir ikut ter-update.
    setTimeout(function(){ window.location.reload(); }, 60000);
</script>
@endpush
