@extends('layouts.app')

@section('title', 'Import Karyawan')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
        <div>
            <h4 class="mb-1">Import Karyawan Aktif</h4>
            <p class="text-muted mb-0">Upload file Excel/CSV berisi <strong>NIK, Nama, Departemen</strong>. Data ditinjau dulu sebelum disimpan.</p>
        </div>
        <a href="{{ route('karyawan.index') }}" class="btn btn-outline-secondary">Kembali</a>
    </div>

    @include('partials.flash')

    @if(! $preview)
        <div class="card">
            <div class="card-body">
                <form method="POST" action="{{ route('karyawan.import.preview') }}" enctype="multipart/form-data" class="row g-3 align-items-end">
                    @csrf
                    <div class="col-md-6">
                        <label for="file" class="form-label">File Excel / CSV (maks. {{ $maksBaris }} baris)</label>
                        <input type="file" id="file" name="file" accept=".xlsx,.xls,.csv" class="form-control @error('file') is-invalid @enderror" required>
                        @error('file')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-auto"><button class="btn btn-primary" type="submit">Upload &amp; Preview</button></div>
                    <div class="col-md-auto"><a href="{{ route('karyawan.import.template') }}" class="btn btn-outline-primary">Unduh Template</a></div>
                </form>
                <ul class="small text-muted mt-3 mb-0">
                    <li>Password awal akun baru = <strong>NIK</strong>; karyawan wajib menggantinya saat login pertama.</li>
                    <li>NIK yang sudah terdaftar ditandai dan <strong>dilewati</strong> (data lama tidak ditimpa).</li>
                    <li>Departemen baru otomatis masuk ke master departemen.</li>
                </ul>
            </div>
        </div>
    @else
        @php($r = $preview['ringkasan'])
        <div class="card mb-3">
            <div class="card-body">
                <div class="d-flex flex-wrap gap-4 align-items-center">
                    <div><div class="small text-muted">File</div><div class="fw-semibold">{{ $preview['file'] }}</div></div>
                    <div><div class="fs-4 fw-bold">{{ $r['total'] }}</div><div class="small text-muted">Total baris</div></div>
                    <div><div class="fs-4 fw-bold text-success">{{ $r['baru'] }}</div><div class="small text-muted">Akan diimport</div></div>
                    <div><div class="fs-4 fw-bold text-warning">{{ $r['duplikat_db'] + $r['duplikat_file'] }}</div><div class="small text-muted">NIK ganda (dilewati)</div></div>
                    <div><div class="fs-4 fw-bold text-danger">{{ $r['tidak_valid'] }}</div><div class="small text-muted">Tidak valid</div></div>
                    <div><div class="fs-4 fw-bold text-info">{{ $r['peringatan'] }}</div><div class="small text-muted">Nama sama (periksa)</div></div>
                </div>
                <div class="d-flex gap-2 mt-3">
                    <form method="POST" action="{{ route('karyawan.import.konfirmasi') }}"
                          onsubmit="return confirm('Import {{ $r['baru'] }} karyawan baru sekarang?')">
                        @csrf
                        <button class="btn btn-success" type="submit" @disabled($r['baru'] === 0)>Konfirmasi Import ({{ $r['baru'] }})</button>
                    </form>
                    <a href="{{ route('karyawan.import') }}" onclick="event.preventDefault();document.getElementById('batal').submit();" class="btn btn-outline-secondary">Batal</a>
                    <form id="batal" method="POST" action="{{ route('karyawan.import.preview') }}" class="d-none">@csrf</form>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="table-light">
                            <tr><th>Baris</th><th>NIK</th><th>Nama</th><th>Departemen</th><th>Penanda</th></tr>
                        </thead>
                        <tbody>
                            @foreach($preview['rows'] as $row)
                                <tr class="{{ in_array($row['status'], ['tidak_valid']) ? 'table-danger' : (in_array($row['status'], ['duplikat_db', 'duplikat_file']) ? 'table-warning' : '') }}">
                                    <td>{{ $row['baris'] }}</td>
                                    <td>{{ $row['nik'] }}</td>
                                    <td>{{ $row['nama'] }}</td>
                                    <td>{{ $row['departemen'] ?? '-' }}</td>
                                    <td>
                                        @switch($row['status'])
                                            @case('baru') <span class="badge bg-success">Baru</span> @break
                                            @case('duplikat_db') <span class="badge bg-warning text-dark">Sudah terdaftar</span> @break
                                            @case('duplikat_file') <span class="badge bg-warning text-dark">Ganda di file</span> @break
                                            @default <span class="badge bg-danger">Tidak valid</span>
                                        @endswitch
                                        @if($row['catatan']) <span class="small text-muted">{{ $row['catatan'] }}</span> @endif
                                        @if($row['peringatan']) <span class="badge bg-info text-dark">Periksa</span> <span class="small text-muted">{{ $row['peringatan'] }}</span> @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif
</div>
@endsection
