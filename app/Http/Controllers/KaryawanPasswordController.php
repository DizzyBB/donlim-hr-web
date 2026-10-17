<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class KaryawanPasswordController extends Controller
{
    public function edit()
    {
        return view('karyawan.password');
    }

    public function update(Request $request)
    {
        $karyawan = auth('karyawan')->user();

        $data = $request->validate([
            'password_lama' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'password.min' => 'Password baru minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi password tidak sama.',
        ]);

        if (! Hash::check($data['password_lama'], $karyawan->password)) {
            throw ValidationException::withMessages(['password_lama' => 'Password lama salah.']);
        }

        if ($data['password'] === $data['password_lama'] || $data['password'] === $karyawan->nik) {
            throw ValidationException::withMessages(['password' => 'Password baru tidak boleh sama dengan password lama atau NIK.']);
        }

        $karyawan->update([
            'password' => $data['password'], // di-hash otomatis oleh cast
            'must_change_password' => false,
        ]);

        return redirect()->route('karyawan.ujian.index')->with('success', 'Password berhasil diganti.');
    }
}
