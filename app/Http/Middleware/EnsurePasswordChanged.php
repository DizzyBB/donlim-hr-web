<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Karyawan hasil bulk import memakai password awal (NIK) dan wajib menggantinya
 * sebelum bisa membuka halaman portal lain.
 */
class EnsurePasswordChanged
{
    public function handle(Request $request, Closure $next): Response
    {
        $karyawan = auth('karyawan')->user();

        if ($karyawan && $karyawan->must_change_password) {
            return redirect()
                ->route('karyawan.password.edit')
                ->with('info', 'Demi keamanan, silakan ganti password awal kamu terlebih dahulu.');
        }

        return $next($request);
    }
}
