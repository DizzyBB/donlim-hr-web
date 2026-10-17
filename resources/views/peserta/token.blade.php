@extends('layouts.layouts-karyawan')

@section('title', 'Ujian Karyawan Baru')
@section('hide_topbar', '1')

@section('content')
<div class="container-xl py-5" style="max-width: 480px;">
    <div class="panel p-4 p-md-5 text-center">
        <i class="bi bi-key-fill" style="font-size:2.5rem;color:var(--copper);"></i>
        <h4 class="mt-2 mb-1">Ujian Karyawan Baru</h4>
        <p class="text-muted">Masukkan kode token dari HRD untuk memulai ujian.</p>

        <form method="POST" action="{{ route('peserta.token.verifikasi') }}" class="text-start">
            @csrf
            <label for="token" class="form-label">Kode Token</label>
            <input type="text" id="token" name="token" value="{{ old('token') }}" maxlength="16" autocomplete="off" autofocus
                   class="form-control form-control-lg text-center text-uppercase @error('token') is-invalid @enderror"
                   style="letter-spacing:.25em;font-weight:700;" placeholder="XXXX-XXXX" required>
            @error('token')<div class="invalid-feedback">{{ $message }}</div>@enderror
            <button type="submit" class="btn btn-navy w-100 mt-3">Lanjut</button>
        </form>
        <p class="small text-muted mt-3 mb-0">Token hanya bisa dipakai satu kali. Karyawan aktif silakan <a href="{{ route('login') }}">login</a>.</p>
    </div>
</div>
@endsection
