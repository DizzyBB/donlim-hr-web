@extends('layouts.layouts-karyawan')

@section('title', 'Data Peserta')
@section('hide_topbar', '1')

@section('content')
<div class="container-xl py-5" style="max-width: 560px;">
    <div class="panel p-4 p-md-5">
        <div class="text-center mb-3">
            <i class="bi bi-check-circle-fill text-success" style="font-size:2.2rem;"></i>
            <h4 class="mt-2 mb-1">Token valid</h4>
            <p class="text-muted mb-0">{{ $ujian->nama_ujian }}</p>
        </div>
        <div class="d-flex justify-content-center gap-4 small text-muted mb-4">
            <span><i class="bi bi-hourglass me-1"></i>{{ $ujian->durasi_label }}</span>
            <span><i class="bi bi-list-check me-1"></i>{{ $ujian->soal_count }} soal</span>
        </div>

        <form method="POST" action="{{ route('peserta.mulai') }}" onsubmit="return confirm('Mulai ujian sekarang? Waktu langsung berjalan dan token tidak bisa dipakai lagi.')">
            @csrf
            <div class="mb-3">
                <label for="nama" class="form-label">Nama Lengkap</label>
                <input type="text" id="nama" name="nama" value="{{ old('nama') }}" maxlength="255" autocomplete="name" autofocus required
                       class="form-control @error('nama') is-invalid @enderror">
                @error('nama')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label for="departemen_id" class="form-label">Departemen</label>
                <select id="departemen_id" name="departemen_id" class="form-select @error('departemen_id') is-invalid @enderror" required>
                    <option value="">-- Pilih departemen --</option>
                    @foreach($daftarDepartemen as $d)
                        <option value="{{ $d->id }}" @selected(old('departemen_id') == $d->id)>{{ $d->nama }}</option>
                    @endforeach
                </select>
                @error('departemen_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <p class="small text-muted">Waktu pengerjaan mulai dihitung setelah kamu menekan tombol di bawah. Ujian hanya bisa dikerjakan satu kali.</p>
            <button type="submit" class="btn btn-copper w-100"><i class="bi bi-play-fill me-1"></i> Mulai Ujian</button>
        </form>
    </div>
</div>
@endsection
