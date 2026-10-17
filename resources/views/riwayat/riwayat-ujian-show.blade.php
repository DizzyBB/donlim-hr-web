@extends('layouts.app')

@section('title', 'Detail Hasil - ' . $hasilUjian->nama_peserta)

@section('content')
<div class="container-fluid py-4">

    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
        <div>
            <h4 class="mb-1">{{ $hasilUjian->nama_peserta }}</h4>
            <p class="text-muted mb-0">
                {{ $hasilUjian->ujian->nama_ujian }} &middot;
                {{ $hasilUjian->is_peserta_baru ? 'Karyawan Baru' : 'NIK ' . $hasilUjian->nomor_peserta }} &middot;
                {{ $hasilUjian->departemen_peserta ?? '-' }}
            </p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            @if($hasilUjian->status !== 'berlangsung')
                <a href="{{ route('examination.riwayat-ujian.pdf', $hasilUjian) }}" target="_blank" class="btn btn-danger"><i class="bi bi-file-earmark-pdf me-1"></i> Cetak / Unduh PDF</a>
                <a href="{{ route('examination.penilaian.show', $hasilUjian) }}" class="btn btn-outline-primary">Nilai / Koreksi</a>
            @else
                <form method="POST" action="{{ route('examination.penilaian.token-pengganti', $hasilUjian) }}"
                      onsubmit="return confirm('Terbitkan token pengganti? Peserta melanjutkan pengerjaan yang sama dan waktu tetap berjalan.')">
                    @csrf
                    <button class="btn btn-warning"><i class="bi bi-key me-1"></i> Terbitkan Token Pengganti</button>
                </form>
            @endif
            <a href="{{ route('examination.riwayat-ujian.index') }}" class="btn btn-outline-secondary">Kembali</a>
        </div>
    </div>

    @include('partials.flash')
    @if(session('token_pengganti'))
        <div class="alert alert-warning">Token pengganti: <code class="fs-5 px-2 bg-white border rounded">{{ session('token_pengganti') }}</code>
            &mdash; berikan ke peserta, masukkan di halaman token ujian.</div>
    @endif
    @include('partials.duplikat-alert', ['info' => $infoDuplikat])

    <div class="row g-3 mb-3">
        <div class="col-md-3"><div class="card h-100"><div class="card-body"><div class="small text-muted">Nilai total</div>
            <div class="fs-3 fw-bold">{{ rtrim(rtrim(number_format($hasilUjian->nilai_total, 2), '0'), '.') }}%</div>
            @if($hasilUjian->status !== 'selesai_dinilai' && $hasilUjian->status !== 'berlangsung')<div class="small text-muted">sementara</div>@endif</div></div></div>
        <div class="col-md-3"><div class="card h-100"><div class="card-body"><div class="small text-muted">Kelulusan</div>
            @if($hasilUjian->status === 'selesai_dinilai')
                <span class="badge fs-6 {{ $hasilUjian->lulus ? 'bg-success' : 'bg-danger' }}">{{ $hasilUjian->kelulusan_label }}</span>
                <div class="small text-muted mt-1">Passing grade {{ rtrim(rtrim(number_format($hasilUjian->passing_grade, 2), '0'), '.') }}%</div>
            @else
                <span class="badge fs-6 bg-warning text-dark">{{ $hasilUjian->kelulusan_label }}</span>
            @endif</div></div></div>
        <div class="col-md-3"><div class="card h-100"><div class="card-body"><div class="small text-muted">PG &amp; Benar/Salah</div>
            <div class="fs-5 fw-semibold">{{ $jumlahBenar }} benar / {{ $jumlahSalah }} salah</div><div class="small text-muted">dari {{ $jumlahAuto }} soal</div></div></div></div>
        <div class="col-md-3"><div class="card h-100"><div class="card-body"><div class="small text-muted">Waktu</div>
            <div>{{ $hasilUjian->waktu_mulai?->format('d M Y H:i') }}</div><div class="small text-muted">s/d {{ $hasilUjian->waktu_selesai?->format('H:i') ?? 'berlangsung' }}</div></div></div></div>
    </div>

    @if($hasilUjian->dikoreksi_pada)
        <div class="alert alert-secondary small"><strong>Dikoreksi</strong> oleh {{ $hasilUjian->pengoreksi->name ?? 'admin' }}, {{ $hasilUjian->dikoreksi_pada->format('d M Y H:i') }}: {{ $hasilUjian->catatan_koreksi }}</div>
    @endif

    <div class="card"><div class="card-body p-0">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light"><tr><th style="width:44px;">#</th><th>Soal</th><th>Jawaban</th><th style="width:100px;">Hasil</th><th style="width:110px;">Skor</th></tr></thead>
                <tbody>
                    @foreach($hasilUjian->jawaban as $j)
                        @php
                            if ($j->tipe === 'pg') {
                                $jwb = $j->jawaban_index !== null ? chr(65 + $j->jawaban_index) . '. ' . (($j->soal->opsi ?? [])[$j->jawaban_index] ?? '') : '-';
                            } elseif ($j->tipe === 'tf') {
                                $jwb = $j->jawaban_tf_label ?? '-';
                            } else {
                                $jwb = $j->jawaban_teks ?: '-';
                            }
                        @endphp
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $j->soal->pertanyaan }}<div class="small text-muted">{{ $j->soal->tipe_label }}</div></td>
                            <td style="white-space:pre-wrap;">{{ $jwb }}</td>
                            <td>
                                @if($j->tipe === 'ur')
                                    <span class="badge {{ $j->skor === null ? 'bg-warning text-dark' : 'bg-info text-dark' }}">{{ $j->skor === null ? 'Pending' : 'Dinilai' }}</span>
                                @else
                                    <span class="badge {{ $j->benar ? 'bg-success' : 'bg-danger' }}">{{ $j->benar ? 'Benar' : 'Salah' }}</span>
                                @endif
                            </td>
                            <td>{{ $j->skor !== null ? rtrim(rtrim(number_format($j->skor, 2), '0'), '.') : '-' }} / {{ rtrim(rtrim(number_format($j->bobot, 2), '0'), '.') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div></div>
</div>
@endsection
