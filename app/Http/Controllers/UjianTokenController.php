<?php

namespace App\Http\Controllers;

use App\Models\HasilUjian;
use App\Models\Ujian;
use App\Models\UjianToken;
use App\Services\PenilaianService;
use App\Services\TokenService;
use Illuminate\Http\Request;

/** Manajemen token ujian oleh admin / HRD. */
class UjianTokenController extends Controller
{
    public function __construct(protected TokenService $tokens)
    {
    }

    public function index(Request $request, Ujian $ujian)
    {
        $ujian->loadMissing('karyawan');

        $daftar = $ujian->tokens()
            ->with(['karyawan', 'hasilUjian.karyawan', 'hasilUjian.pesertaBaru'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->latest('id')
            ->paginate(25)
            ->withQueryString();

        $ringkasan = $ujian->tokens()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('examination.ujian-token', [
            'ujian' => $ujian,
            'tokens' => $daftar,
            'ringkasan' => $ringkasan,
            'tokenBaru' => session('token_baru', []),
        ]);
    }

    public function store(Request $request, Ujian $ujian)
    {
        $userId = auth('web')->id();

        if ($ujian->jenis === 'karyawan_baru') {
            // Token global: tidak terikat orang tertentu, tiap token sekali pakai.
            $data = $request->validate(['jumlah' => ['required', 'integer', 'min:1', 'max:100']]);
            $dibuat = $this->tokens->buatBanyak($ujian, (int) $data['jumlah'], $userId);

            return redirect()
                ->route('examination.ujian.token.index', $ujian)
                ->with('success', "{$dibuat->count()} token berhasil dibuat.")
                ->with('token_baru', $dibuat->pluck('kode_tampil')->all());
        }

        // Ujian lanjutan: satu token terikat ke karyawan pada ujian ini.
        if (! $ujian->karyawan_id) {
            return back()->with('error', 'Ujian ini belum terhubung ke karyawan.');
        }

        if ($ujian->hasilUjian()->where('karyawan_id', $ujian->karyawan_id)->exists()) {
            return back()->with('error', 'Karyawan ini sudah mengerjakan ujian tersebut, token baru tidak diperlukan.');
        }

        if ($ujian->tokens()->where('tipe', UjianToken::TIPE_KARYAWAN)->aktif()->exists()) {
            return back()->with('error', 'Token aktif untuk karyawan ini sudah ada. Batalkan token lama bila ingin membuat yang baru.');
        }

        $token = $this->tokens->buat($ujian, UjianToken::TIPE_KARYAWAN, [
            'karyawan_id' => $ujian->karyawan_id,
            'dibuat_oleh' => $userId,
        ]);

        return redirect()
            ->route('examination.ujian.token.index', $ujian)
            ->with('success', 'Token untuk ' . $ujian->karyawan->nama . ' berhasil dibuat.')
            ->with('token_baru', [$token->kode_tampil]);
    }

    public function kedaluwarsa(Ujian $ujian, UjianToken $token)
    {
        abort_unless($token->ujian_id === $ujian->id, 404);

        if ($this->tokens->kedaluwarsakan($token)) {
            return back()->with('success', 'Token dinonaktifkan.');
        }

        return back()->with('error', 'Hanya token berstatus aktif yang bisa dinonaktifkan.');
    }

    /**
     * Token pengganti: peserta yang keluar/terputus di tengah ujian bisa melanjutkan
     * pengerjaan yang sama (waktu tidak direset) dengan token baru dari admin.
     */
    public function pengganti(HasilUjian $hasilUjian, PenilaianService $penilaian)
    {
        $hasilUjian->loadMissing('ujian');

        if ($hasilUjian->status !== 'berlangsung') {
            return back()->with('error', 'Token pengganti hanya untuk pengerjaan yang masih berlangsung.');
        }

        if ($hasilUjian->sisaDetik() <= 0) {
            $penilaian->finalisasiPengerjaan($hasilUjian);

            return back()->with('error', 'Waktu pengerjaan sudah habis, ujian tidak dapat dilanjutkan.');
        }

        // Token pengganti lama yang belum dipakai dinonaktifkan agar hanya ada satu yang berlaku.
        UjianToken::where('hasil_ujian_id', $hasilUjian->id)
            ->where('tipe', UjianToken::TIPE_PENGGANTI)
            ->aktif()
            ->update(['status' => UjianToken::STATUS_KEDALUWARSA]);

        $token = $this->tokens->buat($hasilUjian->ujian, UjianToken::TIPE_PENGGANTI, [
            'karyawan_id' => $hasilUjian->karyawan_id,
            'hasil_ujian_id' => $hasilUjian->id,
            'dibuat_oleh' => auth('web')->id(),
        ]);

        return back()
            ->with('success', 'Token pengganti berhasil dibuat. Berikan ke peserta; waktu ujian tetap berjalan.')
            ->with('token_pengganti', $token->kode_tampil);
    }
}
