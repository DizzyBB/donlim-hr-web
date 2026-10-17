@extends('layouts.app')

@section('title', 'Penilaian - ' . $hasilUjian->nama_peserta)

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
        <a href="{{ route('examination.penilaian.index', ['status' => $hasilUjian->status === 'selesai_dinilai' ? 'selesai_dinilai' : 'belum_dinilai']) }}" class="btn btn-outline-secondary">Kembali</a>
    </div>

    @include('partials.flash')
    @include('partials.duplikat-alert', ['info' => $infoDuplikat])

    <div class="row g-3 mb-3">
        <div class="col-md-3"><div class="card h-100"><div class="card-body">
            <div class="small text-muted">Nilai otomatis (PG + B/S)</div>
            <div class="fs-4 fw-bold">{{ rtrim(rtrim(number_format($hasilUjian->nilai_pilihan_ganda, 2), '0'), '.') }}</div>
        </div></div></div>
        <div class="col-md-3"><div class="card h-100"><div class="card-body">
            <div class="small text-muted">Nilai esai</div>
            <div class="fs-4 fw-bold">{{ rtrim(rtrim(number_format($hasilUjian->nilai_manual, 2), '0'), '.') }}</div>
        </div></div></div>
        <div class="col-md-3"><div class="card h-100"><div class="card-body">
            <div class="small text-muted">Nilai total {{ $hasilUjian->status !== 'selesai_dinilai' ? '(sementara)' : '' }}</div>
            <div class="fs-4 fw-bold">{{ rtrim(rtrim(number_format($hasilUjian->nilai_total, 2), '0'), '.') }}%</div>
        </div></div></div>
        <div class="col-md-3"><div class="card h-100"><div class="card-body">
            <div class="small text-muted">Status</div>
            @if($hasilUjian->status === 'selesai_dinilai')
                <span class="badge fs-6 {{ $hasilUjian->lulus ? 'bg-success' : 'bg-danger' }}">{{ $hasilUjian->kelulusan_label }}</span>
                <div class="small text-muted mt-1">Passing grade {{ rtrim(rtrim(number_format($hasilUjian->passing_grade, 2), '0'), '.') }}%</div>
            @else
                <span class="badge fs-6 bg-warning text-dark">Pending</span>
            @endif
        </div></div></div>
    </div>

    @if($hasilUjian->dikoreksi_pada)
        <div class="alert alert-secondary small">
            <strong>Koreksi terakhir</strong> oleh {{ $hasilUjian->pengoreksi->name ?? 'admin' }}, {{ $hasilUjian->dikoreksi_pada->format('d M Y H:i') }}:
            {{ $hasilUjian->catatan_koreksi }}
        </div>
    @endif

    <form method="POST" action="{{ route('examination.penilaian.update', $hasilUjian) }}">
        @csrf @method('PUT')

        @if($jawabanEsai->count())
            <h6 class="fw-semibold mt-4 mb-2">Soal Esai (dinilai manual)</h6>
            @foreach($jawabanEsai as $j)
                <div class="card mb-3"><div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <div class="fw-semibold">{{ $loop->iteration }}. {{ $j->soal->pertanyaan }}</div>
                        <span class="badge text-bg-light border">Bobot {{ rtrim(rtrim(number_format($j->bobot, 2), '0'), '.') }}</span>
                    </div>
                    <div class="border rounded p-2 mb-2 bg-light" style="white-space:pre-wrap;">{{ $j->jawaban_teks ?: '(tidak dijawab)' }}</div>
                    @if($j->soal->kunci)
                        <div class="small text-muted mb-2"><strong>Kunci / rubrik:</strong> {{ $j->soal->kunci }}</div>
                    @endif
                    <div class="row g-2">
                        <div class="col-sm-3">
                            <label class="form-label small">Skor (0 - {{ rtrim(rtrim(number_format($j->bobot, 2), '0'), '.') }})</label>
                            <input type="number" step="0.01" min="0" max="{{ (float) $j->bobot }}" name="skor[{{ $j->id }}]"
                                   value="{{ old('skor.' . $j->id, $j->skor) }}"
                                   class="form-control @error('skor.' . $j->id) is-invalid @enderror" {{ filled($j->jawaban_teks) ? 'required' : '' }}>
                            @error('skor.' . $j->id)<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-sm-9">
                            <label class="form-label small">Catatan penilai (opsional)</label>
                            <input type="text" name="catatan[{{ $j->id }}]" value="{{ old('catatan.' . $j->id, $j->catatan_penilai) }}" class="form-control" maxlength="2000">
                        </div>
                    </div>
                </div></div>
            @endforeach
        @endif

        @if($jawabanOtomatis->count())
            <h6 class="fw-semibold mt-4 mb-2">Pilihan Ganda &amp; Benar/Salah (otomatis, bisa dikoreksi)</h6>
            <div class="card mb-3"><div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead class="table-light"><tr><th style="width:44px;">#</th><th>Soal</th><th>Jawaban peserta</th><th>Kunci</th><th style="width:70px;">Hasil</th><th style="width:130px;">Skor / {{ 'bobot' }}</th></tr></thead>
                        <tbody>
                            @foreach($jawabanOtomatis as $j)
                                @php
                                    $opsi = $j->soal->opsi ?? [];
                                    if ($j->tipe === 'pg') {
                                        $jwb = $j->jawaban_index !== null ? chr(65 + $j->jawaban_index) . '. ' . ($opsi[$j->jawaban_index] ?? '') : '-';
                                        $kunci = chr(65 + (int) $j->soal->jawaban_index) . '. ' . ($opsi[$j->soal->jawaban_index] ?? '');
                                    } else {
                                        $jwb = $j->jawaban_tf_label ?? '-';
                                        $kunci = $j->soal->jawaban_tf ? 'Benar' : 'Salah';
                                    }
                                @endphp
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ \Illuminate\Support\Str::limit($j->soal->pertanyaan, 90) }}<div class="small text-muted">{{ $j->soal->tipe_label }}</div></td>
                                    <td>{{ $jwb }}</td>
                                    <td>{{ $kunci }}</td>
                                    <td>@if($j->benar)<span class="badge bg-success">Benar</span>@else<span class="badge bg-danger">Salah</span>@endif</td>
                                    <td>
                                        <div class="input-group input-group-sm">
                                            <input type="number" step="0.01" min="0" max="{{ (float) $j->bobot }}" name="skor[{{ $j->id }}]" value="{{ old('skor.' . $j->id, $j->skor) }}" class="form-control @error('skor.' . $j->id) is-invalid @enderror">
                                            <span class="input-group-text">/ {{ rtrim(rtrim(number_format($j->bobot, 2), '0'), '.') }}</span>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div></div>
        @endif

        <div class="card mb-3"><div class="card-body">
            <h6 class="fw-semibold mb-3">Kelulusan &amp; Koreksi</h6>
            <div class="row g-3">
                <div class="col-sm-3">
                    <label class="form-label" for="passing_grade">Passing grade hasil ini (%)</label>
                    <input type="number" step="0.01" min="0" max="100" id="passing_grade" name="passing_grade"
                           value="{{ old('passing_grade', $hasilUjian->passing_grade ?? $hasilUjian->ujian->passing_grade) }}"
                           class="form-control @error('passing_grade') is-invalid @enderror">
                    @error('passing_grade')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    <div class="form-text">Awal diambil dari ujian ({{ rtrim(rtrim(number_format($hasilUjian->ujian->passing_grade, 2), '0'), '.') }}%).</div>
                </div>
                <div class="col-sm-9">
                    <label class="form-label" for="catatan_koreksi">Catatan koreksi
                        @if($hasilUjian->status === 'selesai_dinilai')<span class="text-danger">*</span>@endif
                    </label>
                    <textarea id="catatan_koreksi" name="catatan_koreksi" rows="2" maxlength="2000"
                              class="form-control @error('catatan_koreksi') is-invalid @enderror"
                              placeholder="Wajib bila mengubah hasil yang sudah dinilai, skor otomatis, atau passing grade">{{ old('catatan_koreksi') }}</textarea>
                    @error('catatan_koreksi')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
        </div></div>

        <div class="d-flex gap-2 flex-wrap">
            <button type="submit" class="btn btn-success"><i class="bi bi-check-lg me-1"></i> Simpan Penilaian</button>
            <button type="submit" form="formHitungUlang" class="btn btn-outline-secondary"
                    onclick="return confirm('Hitung ulang skor PG & Benar/Salah berdasarkan kunci jawaban terbaru? Koreksi manual pada soal otomatis akan tertimpa.')">Hitung Ulang Otomatis</button>
            <a href="{{ route('examination.riwayat-ujian.pdf', $hasilUjian) }}" class="btn btn-outline-secondary" target="_blank"><i class="bi bi-file-earmark-pdf me-1"></i> PDF</a>
        </div>
    </form>
    <form id="formHitungUlang" method="POST" action="{{ route('examination.penilaian.hitung-ulang', $hasilUjian) }}" class="d-none">@csrf</form>
</div>
@endsection
