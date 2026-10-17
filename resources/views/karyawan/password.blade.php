@extends('layouts.layouts-karyawan')

@section('title', 'Ganti Password')

@section('content')
<div class="container-xl py-5" style="max-width: 520px;">
    @include('partials.flash')
    <div class="panel p-4">
        <h5 class="mb-1">Ganti Password</h5>
        <p class="text-muted small">
            @if(auth('karyawan')->user()->must_change_password)
                Akunmu masih memakai password awal (NIK). Buat password baru untuk melanjutkan.
            @else
                Masukkan password lama dan password baru.
            @endif
        </p>
        <form method="POST" action="{{ route('karyawan.password.update') }}">
            @csrf @method('PUT')
            <div class="mb-3">
                <label class="form-label" for="password_lama">Password lama</label>
                <input type="password" id="password_lama" name="password_lama" class="form-control @error('password_lama') is-invalid @enderror" autocomplete="current-password" required>
                @error('password_lama')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label class="form-label" for="password">Password baru (min. 8 karakter)</label>
                <input type="password" id="password" name="password" class="form-control @error('password') is-invalid @enderror" autocomplete="new-password" required>
                @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label class="form-label" for="password_confirmation">Ulangi password baru</label>
                <input type="password" id="password_confirmation" name="password_confirmation" class="form-control" autocomplete="new-password" required>
            </div>
            <button class="btn btn-navy w-100" type="submit">Simpan Password</button>
        </form>
    </div>
</div>
@endsection
