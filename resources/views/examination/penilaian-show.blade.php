@extends('layouts.app')

@section('title', 'Nilai Ujian')

@push('styles')
<style>
  .skor-input{ max-width: 110px; }
  .soal-nilai-card.dinilai{ border-color:#198754; background:#f8fdfa; }
</style>
@endpush

@section('content')
<div class="container-fluid py-4">

    <div class="d-flex justify-content-between align-items-center flex-wrap mb-4">
        <div>
            <h4 class="mb-1">{{ $hasilUjian->ujian->nama_ujian ?? '-' }}</h4>
            <p class="text-muted mb-0">
                Peserta: <strong>{{ $hasilUjian->karyawan->nama ?? '-' }}</strong>
                ({{ $hasilUjian->karyawan->nik ?? '-' }})
                &middot; Selesai: {{ $hasilUjian->waktu_selesai?->format('d M Y H:i') ?? '-' }}
            </p>
        </div>
        <a href="{{ route('examination.penilaian.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Kembali
        </a>
    </div>

    {{-- Ringkasan Nilai --}}
    <div class="row g-3 mb-4">
        <div class="col-sm-4">
            <div class="card text-center">
                <div class="card-body">
                    <div class="text-muted small">Kontribusi Nilai PG <span class="d-block">(skala 100)</span></div>
                    <div class="fs-4 fw-bold text-primary">{{ $hasilUjian->nilai_pilihan_ganda }}</div>
                </div>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="card text-center">
                <div class="card-body">
                    <div class="text-muted small">Kontribusi Nilai Manual <span class="d-block">(skala 100)</span></div>
                    <div class="fs-4 fw-bold text-warning">{{ $hasilUjian->nilai_manual }}</div>
                </div>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="card text-center border-success">
                <div class="card-body">
                    <div class="text-muted small">Nilai Total (maks. 100)</div>
                    <div class="fs-4 fw-bold text-success">{{ $hasilUjian->nilai_total }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Pilihan Ganda (otomatis, read-only) --}}
    @if($jawabanPG->count())
    <div class="card mb-4">
        <div class="card-header bg-white">
            <h6 class="mb-0"><i class="bi bi-check2-square me-1"></i> Pilihan Ganda ({{ $jawabanPG->count() }} soal — dinilai otomatis)</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Pertanyaan</th>
                            <th style="width:220px;">Jawaban Peserta</th>
                            <th style="width:220px;">Kunci Jawaban</th>
                            <th style="width:90px;" class="text-center">Hasil</th>
                            <th style="width:70px;" class="text-center">Skor</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($jawabanPG as $j)
                            <tr>
                                <td>{{ $j->soal->pertanyaan ?? '-' }}</td>
                                <td>{{ $j->jawaban_pilihan_label ?? '(tidak dijawab)' }}</td>
                                <td>
                                    @if($j->soal && $j->soal->jawaban_index !== null)
                                        {{ chr(65 + $j->soal->jawaban_index) }}. {{ $j->soal->opsi[$j->soal->jawaban_index] ?? '-' }}
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($j->benar)
                                        <i class="bi bi-check-circle-fill text-success"></i>
                                    @else
                                        <i class="bi bi-x-circle-fill text-danger"></i>
                                    @endif
                                </td>
                                <td class="text-center">{{ $j->skor }} / 5</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif

    {{-- Uraian & Jawaban Singkat (manual) --}}
    <form action="{{ route('examination.penilaian.update', $hasilUjian) }}" method="POST">
        @csrf
        @method('PUT')

        @if($jawabanManual->count())
        <div class="card mb-4">
            <div class="card-header bg-white">
                <h6 class="mb-0"><i class="bi bi-pencil-square me-1"></i> Uraian &amp; Jawaban Singkat ({{ $jawabanManual->count() }} soal — nilai manual)</h6>
            </div>
            <div class="card-body">
                @foreach($jawabanManual as $j)
                    @php $maxSkor = $j->tipe === 'ur' ? 20 : 10; @endphp
                    <div class="card soal-nilai-card mb-3 {{ $hasilUjian->status === 'selesai_dinilai' ? 'dinilai' : '' }}">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <span class="badge text-bg-light border">
                                    {{ $j->tipe === 'ur' ? 'Uraian' : 'Jawaban Singkat' }}
                                </span>
                                <span class="text-muted small">Poin maksimal: {{ $maxSkor }}</span>
                            </div>

                            <p class="fw-semibold mb-2">{{ $j->soal->pertanyaan ?? '-' }}</p>

                            <div class="mb-2">
                                <label class="form-label small text-muted mb-1">Jawaban Peserta:</label>
                                <div class="border rounded p-2 bg-light">{{ $j->jawaban_teks ?: '(tidak dijawab)' }}</div>
                            </div>

                            @if($j->soal && $j->soal->kunci)
                                <div class="mb-3">
                                    <label class="form-label small text-muted mb-1">Kunci / Rubrik (acuan penilai):</label>
                                    <div class="border rounded p-2 bg-light-subtle small text-muted">{{ $j->soal->kunci }}</div>
                                </div>
                            @endif

                            <div class="row g-2 align-items-start">
                                <div class="col-auto">
                                    <label class="form-label" for="skor_{{ $j->id }}">Skor</label>
                                    <input type="number" step="0.5" min="0" max="{{ $maxSkor }}"
                                           name="skor[{{ $j->id }}]" id="skor_{{ $j->id }}"
                                           class="form-control skor-input @error('skor.' . $j->id) is-invalid @enderror"
                                           value="{{ old('skor.' . $j->id, $j->skor) }}">
                                    @error('skor.' . $j->id)<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col">
                                    <label class="form-label" for="catatan_{{ $j->id }}">Catatan <span class="text-muted fw-normal">(opsional)</span></label>
                                    <input type="text" name="catatan[{{ $j->id }}]" id="catatan_{{ $j->id }}"
                                           class="form-control" value="{{ old('catatan.' . $j->id, $j->catatan_penilai) }}"
                                           placeholder="Feedback untuk peserta...">
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
        @endif

        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-success">
                <i class="bi bi-check-lg me-1"></i> Simpan Penilaian
            </button>
            <a href="{{ route('examination.penilaian.index') }}" class="btn btn-outline-secondary">Batal</a>
        </div>
    </form>
</div>
@endsection
