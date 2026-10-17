<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Hasil Ujian - {{ $hasil->nama_peserta }}</title>
<style>
    body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 12px; color: #1c1914; margin: 24px; }
    h1 { font-size: 18px; margin: 0 0 4px; }
    .muted { color: #666; }
    table { width: 100%; border-collapse: collapse; margin-top: 10px; }
    th, td { border: 1px solid #ccc; padding: 6px 8px; text-align: left; vertical-align: top; }
    th { background: #f1f1f1; }
    .kv td { border: none; padding: 3px 0; }
    .kv td:first-child { width: 150px; color: #555; }
    .hasil { margin: 14px 0; padding: 12px; border: 2px solid #333; text-align: center; }
    .hasil .nilai { font-size: 30px; font-weight: bold; }
    .lulus { color: #1e7e34; } .gagal { color: #b02a37; } .pending { color: #b8860b; }
    .noprint { margin-bottom: 12px; }
    @media print { .noprint { display: none; } }
</style>
</head>
<body>
@if($cetakLangsung)
    <div class="noprint"><button onclick="window.print()">Cetak / Simpan sebagai PDF</button></div>
@endif

<h1>Hasil Ujian</h1>
<div class="muted">{{ $hasil->ujian->nama_ujian }}</div>

<table class="kv" style="margin-top:12px;">
    <tr><td>Nama peserta</td><td><strong>{{ $hasil->nama_peserta }}</strong></td></tr>
    <tr><td>Nomor karyawan</td><td>{{ $hasil->nomor_peserta }}</td></tr>
    <tr><td>Departemen</td><td>{{ $hasil->departemen_peserta ?? '-' }}</td></tr>
    <tr><td>Tanggal tes</td><td>{{ $hasil->waktu_mulai?->format('d M Y') }}</td></tr>
    <tr><td>Waktu pengerjaan</td><td>{{ $hasil->waktu_mulai?->format('H:i') }} - {{ $hasil->waktu_selesai?->format('H:i') ?? '-' }}</td></tr>
    <tr><td>Soal PG & Benar/Salah</td><td>{{ $jumlahBenar }} benar dari {{ $jumlahAuto }} soal</td></tr>
</table>

<div class="hasil">
    @if($hasil->status === 'selesai_dinilai')
        <div class="muted">Nilai akhir</div>
        <div class="nilai {{ $hasil->lulus ? 'lulus' : 'gagal' }}">{{ rtrim(rtrim(number_format($hasil->nilai_total, 2), '0'), '.') }}%</div>
        <div class="{{ $hasil->lulus ? 'lulus' : 'gagal' }}"><strong>{{ strtoupper($hasil->kelulusan_label) }}</strong>
            <span class="muted">(passing grade {{ rtrim(rtrim(number_format($hasil->passing_grade, 2), '0'), '.') }}%)</span></div>
    @else
        <div class="muted">Skor sementara (belum final)</div>
        <div class="nilai pending">{{ rtrim(rtrim(number_format($hasil->nilai_pilihan_ganda, 2), '0'), '.') }}%</div>
        <div class="pending"><strong>PENDING</strong> - menunggu penilaian esai oleh HRD</div>
    @endif
</div>

@if($detail)
    <h3 style="margin-bottom:0;">Rincian Jawaban</h3>
    <table>
        <thead><tr><th style="width:28px;">#</th><th>Soal</th><th>Jawaban</th><th style="width:60px;">Hasil</th><th style="width:70px;">Skor</th></tr></thead>
        <tbody>
        @foreach($hasil->jawaban as $j)
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
                <td>{{ $j->soal->pertanyaan }} <span class="muted">({{ $j->soal->tipe_label }})</span></td>
                <td>{{ $jwb }}</td>
                <td>@if($j->tipe === 'ur'){{ $j->skor === null ? 'Pending' : 'Dinilai' }}@else{{ $j->benar ? 'Benar' : 'Salah' }}@endif</td>
                <td>{{ $j->skor !== null ? rtrim(rtrim(number_format($j->skor, 2), '0'), '.') : '-' }} / {{ rtrim(rtrim(number_format($j->bobot, 2), '0'), '.') }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
@endif

<p class="muted" style="margin-top:18px;">Dicetak {{ now()->format('d M Y H:i') }}</p>
@if($cetakLangsung)
<script>window.addEventListener('load', function(){ setTimeout(function(){ window.print(); }, 300); });</script>
@endif
</body>
</html>
