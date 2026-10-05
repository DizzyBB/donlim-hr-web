<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Karyawan;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * Redirect kalau salah satu guard sudah login, supaya orang yang sudah
     * login tidak bisa balik ke halaman login lewat back button / URL langsung.
     */
    public function showLoginForm()
    {
        if (Auth::guard('web')->check()) {
            return redirect($this->tujuanAdmin());
        }

        if (Auth::guard('karyawan')->check()) {
            return redirect()->route('karyawan.ujian.index');
        }

        return view('auth.login');
    }

    /**
     * Login 2 tahap:
     *   1. Cocokkan ke tabel `users` (Administrator). Kalau cocok -> masuk sebagai admin.
     *   2. Kalau tidak ketemu/tidak cocok, cocokkan ke tabel `karyawans`. Kalau cocok -> masuk sebagai karyawan.
     *   3. Kalau dua-duanya tidak cocok -> gagal login, tanpa membocorkan tabel mana yang cocok/tidak
     *      (pesan errornya sengaja digeneralisir untuk alasan keamanan).
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'nik' => ['required', 'string'],
            'password' => ['required', 'string'],
        ], [
            'nik.required' => 'Email atau nomor karyawan wajib diisi.',
        ]);

        $ingat = $request->boolean('remember');

        // ----- 1) Coba sebagai Administrator (tabel users, login pakai email) -----
        $admin = User::where('email', $credentials['nik'])->first();

        if ($admin && Hash::check($credentials['password'], $admin->password)) {
            Auth::guard('web')->login($admin, $ingat);
            $request->session()->regenerate();

            return redirect()->intended($this->tujuanAdmin());
        }

        // ----- 2) Coba sebagai Karyawan (tabel karyawans, login pakai NIK) -----
        $karyawan = Karyawan::where('nik', $credentials['nik'])->first();

        if ($karyawan && Hash::check($credentials['password'], $karyawan->password)) {
            Auth::guard('karyawan')->login($karyawan, $ingat);
            $request->session()->regenerate();

            return redirect()->intended(route('karyawan.ujian.index'));
        }

        // ----- 3) Tidak ketemu di keduanya -----
        return back()
            ->withErrors(['nik' => 'Email/nomor karyawan atau password salah.'])
            ->onlyInput('nik');
    }

    public function logout(Request $request)
    {
        if (Auth::guard('web')->check()) {
            Auth::guard('web')->logout();
        } elseif (Auth::guard('karyawan')->check()) {
            Auth::guard('karyawan')->logout();
        }

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    /**
     * Sesuaikan dengan route dashboard admin kamu yang sebenarnya kalau sudah dinamai
     * (mis. route('dashboard')). Untuk sekarang mengikuti link sidebar template:
     * href="{{ url('/dashboard') }}".
     */
    protected function tujuanAdmin(): string
    {
        return url('/dashboard');
    }
}
