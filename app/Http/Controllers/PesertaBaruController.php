<?php

namespace App\Http\Controllers;

use App\Exceptions\TokenException;
use App\Models\Departemen;
use App\Models\PesertaBaru;
use App\Models\UjianToken;
use App\Services\PengerjaanService;
use App\Services\PenilaianService;
use App\Services\TokenService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Alur karyawan baru (tanpa akun):
 *   1. masukkan Kode Token
 *   2. token valid -> form singkat: Nama Lengkap + Departemen
 *   3. setelah diisi, ujian langsung dimulai (token otomatis kedaluwarsa / sekali pakai)
 */
class PesertaBaruController extends Controller
{
    private const PESAN_TOKEN_TIDAK_VALID = 'Token tidak valid atau sudah digunakan.';

    public function __construct(
        protected TokenService $tokens,
        protected PengerjaanService $pengerjaan,
        protected PenilaianService $penilaian,
    ) {
    }

    public function tokenForm()
    {
        return view('peserta.token');
    }

    public function verifikasiToken(Request $request)
    {
        $request->validate(['token' => ['required', 'string', 'max:32']], [
            'token.required' => 'Kode token wajib diisi.',
        ]);

        $token = $this->tokens->cari($request->input('token'));

        $gagal = fn (string $pesan) => back()->withErrors(['token' => $pesan])->withInput();

        if (! $token || $token->status !== UjianToken::STATUS_AKTIF) {
            return $gagal(self::PESAN_TOKEN_TIDAK_VALID);
        }

        // Token pengganti dari admin: lanjutkan pengerjaan yang terputus (tanpa isi form lagi).
        if ($token->tipe === UjianToken::TIPE_PENGGANTI) {
            try {
                $hasil = $this->pengerjaan->lanjutkan($token, $this->tokens, $this->penilaian);
            } catch (TokenException $e) {
                return $gagal($e->getMessage());
            }

            $this->catatKepemilikan($hasil->id);

            return redirect()->route('peserta.ujian.kerjakan', $hasil);
        }

        if ($token->tipe === UjianToken::TIPE_KARYAWAN) {
            return $gagal('Token ini khusus akun karyawan. Silakan login terlebih dahulu di halaman Login.');
        }

        if (! $token->ujian->is_aktif) {
            return $gagal('Ujian ini sedang tidak aktif.');
        }

        session(['peserta.token_id' => $token->id]);

        return redirect()->route('peserta.identitas');
    }

    public function identitasForm()
    {
        $token = $this->tokenDariSession();

        if (! $token) {
            return redirect()->route('peserta.token')->withErrors(['token' => 'Sesi token berakhir. Silakan masukkan token kembali.']);
        }

        return view('peserta.identitas', [
            'ujian' => $token->ujian->loadCount('soal'),
            'daftarDepartemen' => Departemen::orderBy('nama')->get(),
        ]);
    }

    public function mulai(Request $request)
    {
        $data = $request->validate([
            'nama' => ['required', 'string', 'min:3', 'max:255', "regex:/^[\pL\pM\s.'\-]+$/u"],
            'departemen_id' => ['required', 'integer', 'exists:departemens,id'],
        ], [
            'nama.required' => 'Nama lengkap wajib diisi.',
            'nama.min' => 'Nama lengkap minimal 3 karakter.',
            'nama.regex' => 'Nama hanya boleh berisi huruf, spasi, titik, apostrof, dan tanda hubung.',
            'departemen_id.required' => 'Pilih departemen.',
            'departemen_id.exists' => 'Departemen tidak valid.',
        ]);

        $token = $this->tokenDariSession();

        if (! $token) {
            return redirect()->route('peserta.token')->withErrors(['token' => 'Sesi token berakhir. Silakan masukkan token kembali.']);
        }

        $ujian = $token->ujian;

        if ($ujian->soal()->count() === 0) {
            return back()->withErrors(['nama' => 'Ujian ini belum memiliki soal. Hubungi HRD.']);
        }

        $departemen = Departemen::findOrFail($data['departemen_id']);

        try {
            $hasil = DB::transaction(function () use ($data, $ujian, $token, $departemen) {
                $peserta = PesertaBaru::create([
                    'ujian_id' => $ujian->id,
                    'nama' => preg_replace('/\s+/u', ' ', trim($data['nama'])),
                    'departemen_id' => $departemen->id,
                    'departemen_nama' => $departemen->nama,
                    'tanggal_tes' => now()->toDateString(),
                ]);

                $hasil = $this->pengerjaan->mulai($ujian, null, $peserta->id);

                // Atomik: kalau token keburu dipakai orang lain, seluruh transaksi (peserta + hasil) dibatalkan.
                if (! $this->tokens->klaim($token, $hasil->id)) {
                    throw new TokenException(self::PESAN_TOKEN_TIDAK_VALID);
                }

                return $hasil;
            });
        } catch (TokenException $e) {
            session()->forget('peserta.token_id');

            return redirect()->route('peserta.token')->withErrors(['token' => $e->getMessage()]);
        }

        session()->forget('peserta.token_id');
        $this->catatKepemilikan($hasil->id);
        session()->regenerate();

        return redirect()->route('peserta.ujian.kerjakan', $hasil);
    }

    /** Token dari session, hanya kalau masih aktif, bertipe global_baru, dan ujiannya aktif. */
    protected function tokenDariSession(): ?UjianToken
    {
        $id = session('peserta.token_id');
        if (! $id) {
            return null;
        }

        $token = UjianToken::with('ujian')->find($id);

        if (! $token
            || $token->status !== UjianToken::STATUS_AKTIF
            || $token->tipe !== UjianToken::TIPE_GLOBAL_BARU
            || ! $token->ujian->is_aktif) {
            session()->forget('peserta.token_id');

            return null;
        }

        return $token;
    }

    /** Browser/sesi ini berhak melanjutkan pengerjaan tersebut. */
    protected function catatKepemilikan(int $hasilId): void
    {
        $ids = session('peserta.hasil_ids', []);
        $ids[] = $hasilId;
        session(['peserta.hasil_ids' => array_values(array_unique($ids))]);
    }
}
