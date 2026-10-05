@extends('layouts.app')

@php($isEdit = isset($karyawan))

@section('title', $isEdit ? 'Edit Data Karyawan' : 'Tambah Karyawan')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
        <div>
            <div class="text-success small fw-bold text-uppercase mb-1">People directory</div>
            <h1 class="h3 mb-1 fw-bold">{{ $isEdit ? 'Edit Data Karyawan' : 'Tambah Karyawan' }}</h1>
            <p class="text-muted mb-0">{{ $isEdit ? 'Perbarui informasi ' . $karyawan->nama . '.' : 'Masukkan informasi karyawan baru.' }}</p>
        </div>
        <a href="{{ route('karyawan.index') }}" class="btn btn-outline-secondary">Kembali ke daftar</a>
    </div>

   <div class="row justify-content-center">
     <div class="card border-0 shadow-sm" style="max-width: 90%;">
        <div class="card-body p-3 p-lg-4">
            <form action="{{ $isEdit ? route('karyawan.update', $karyawan) : route('karyawan.store') }}" method="POST">
                @csrf
                @if($isEdit)
                    @method('PUT')
                @endif

                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="nama" class="form-label">Nama lengkap</label>
                        <input id="nama" name="nama" type="text" value="{{ old('nama', $karyawan->nama ?? '') }}"
                            class="form-control @error('nama') is-invalid @enderror" required>
                        @error('nama')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label for="nik" class="form-label">NIK</label>
                        <input id="nik" name="nik" type="text" value="{{ old('nik', $karyawan->nik ?? '') }}"
                            class="form-control @error('nik') is-invalid @enderror" required>
                        @error('nik')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label for="email" class="form-label">Email</label>
                        <input id="email" name="email" type="email" value="{{ old('email', $karyawan->email ?? '') }}"
                            class="form-control @error('email') is-invalid @enderror">
                        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label for="jabatan" class="form-label">Jabatan</label>
                        <input id="jabatan" name="jabatan" type="text" value="{{ old('jabatan', $karyawan->jabatan ?? '') }}"
                            class="form-control @error('jabatan') is-invalid @enderror">
                        @error('jabatan')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label for="departemen" class="form-label">Departemen</label>
                        <input id="departemen" name="departemen" type="text" value="{{ old('departemen', $karyawan->departemen ?? '') }}"
                            class="form-control @error('departemen') is-invalid @enderror">
                        @error('departemen')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label for="role" class="form-label">Role</label>
                        <select id="role" name="role" class="form-select @error('role') is-invalid @enderror" required>
                            <option value="karyawan" @selected(old('role', $karyawan->role ?? 'karyawan') === 'karyawan')>Karyawan</option>
                            <option value="administrator" @selected(old('role', $karyawan->role ?? 'karyawan') === 'administrator')>Administrator</option>
                        </select>
                        @error('role')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    @unless($isEdit)
                        <div class="col-md-6">
                            <label for="password" class="form-label">Password awal</label>
                            <input id="password" name="password" type="password" autocomplete="new-password"
                                class="form-control @error('password') is-invalid @enderror" required>
                            @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <div class="form-text">Minimal 8 karakter.</div>
                        </div>
                        <div class="col-md-6">
                            <label for="password_confirmation" class="form-label">Konfirmasi password</label>
                            <input id="password_confirmation" name="password_confirmation" type="password"
                                autocomplete="new-password" class="form-control" required>
                        </div>
                    @endunless
                </div>

                <div class="d-flex justify-content-end gap-2 mt-4">
                    <a href="{{ route('karyawan.index') }}" class="btn btn-light">Batal</a>
                    <button type="submit" class="btn btn-primary">{{ $isEdit ? 'Simpan perubahan' : 'Tambah karyawan' }}</button>
                </div>
            </form>
        </div>
    </div>
   </div>
</div>
@endsection