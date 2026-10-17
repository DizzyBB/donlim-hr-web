<?php

namespace App\Http\Controllers;

use App\Exceptions\TokenException;
use App\Http\Controllers\Concerns\MembuatPdfHasil;
use App\Models\HasilUjian;
use App\Models\JawabanPeserta;
use App\Models\Ujian;
use App\Models\UjianToken;
use App\Services\PengerjaanService;
use App\Services\PenilaianService;
use App\Services\TokenService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Pengerjaan ujian. Dipakai dua jenis peserta:
 *  - karyawan aktif (guard "karyawan", route karyawan.ujian.*)
 *  - karyawan baru lewat token tanpa akun (kepemilikan dicek lewat session, route peserta.ujian.*)
 */
class UjianSayaController extends Controller
{
    use MembuatPdfHasil;

    /**
     * Toleransi (detik) untuk simpanan jawaban yang tiba sedikit setelah batas waktu
     * (jaringan lambat) supaya jawaban terakhir tidak hilang.
     */
    protected const TOLERANSI_DETIK = 10;

    public function __construct(
        protected TokenService $tokens,
        protected PengerjaanService $pengerjaan,
        protected PenilaianService $penilaian,
    ) {
    }

    /* =========================================================
     |  Daftar ujian untuk karyawan aktif
     * =========================================================*/

    public function index()
    {
        $karyawan = auth('karyawan')->user();

        // Tutup otomatis pengerjaan yang waktunya sudah habis (mis. browser ditutup di tengah ujian).
        HasilUjian::with('ujian')
            ->where('karyawan_id', $karyawan->id)
            ->where('status', 'berlangsung')
            ->get()
            ->each(function (HasilUjian $h) {
                if ($h->sisaDetik() <= 0) {
                    $this->penilaian->finalisasiPengerjaan($h);
                }
            });

        $sedangBerlangsung = HasilUjian::with('ujian')
            ->where('karyawan_id', $karyawan->id)
            ->where('status', 'berlangsung')
            ->get();

        // Ujian lanjutan milik karyawan ini yang masih aktif dan belum dikerjakan.
        // Ujian tidak dibatasi jam: bisa diakses kapan saja selama status aktif (dan punya token).
        $tersedia = Ujian::query()
            ->aktif()
            ->withCount('soal')
            ->has('soal')
            ->where('jenis', 'perorangan')
            ->where('karyawan_id', $karyawan->id)
            ->whereDoesntHave('hasilUjian', fn ($q) => $q->where('karyawan_id', $karyawan->id))
            ->latest()
            ->get();

        $riwayat = HasilUjian::with('ujian')
            ->where('karyawan_id', $karyawan->id)
            ->where('status', '!=', 'berlangsung')
            ->latest('waktu_selesai')
            ->limit(10)
            ->get();

        return view('karyawan.ujian-index', compact('sedangBerlangsung', 'tersedia', 'riwayat'));
    }

    /* =========================================================
     |  Masuk ujian lanjutan dengan token (terikat akun)
     * =========================================================*/

    public function mulaiDenganToken(Request $request)
    {
        $request->validate(['token' => ['required', 'string', 'max:32']], [
            'token.required' => 'Kode token wajib diisi.',
        ]);

        $karyawan = auth('karyawan')->user();
        $token = $this->tokens->cari($request->input('token'));

        $gagal = fn (string $pesan) => back()->withErrors(['token' => $pesan])->withInput();

        if (! $token || $token->status !== UjianToken::STATUS_AKTIF) {
            return $gagal('Token tidak valid atau sudah digunakan.');
        }

        // Token pengganti: lanjutkan pengerjaan yang terputus.
        if ($token->tipe === UjianToken::TIPE_PENGGANTI) {
            try {
                $hasil = $this->pengerjaan->lanjutkan($token, $this->tokens, $this->penilaian, $karyawan->id);
            } catch (TokenException $e) {
                return $gagal($e->getMessage());
            }

            return redirect()->route('karyawan.ujian.kerjakan', $hasil);
        }

        // Token ujian lanjutan terikat ke satu akun: tidak bisa dipindah tangankan.
        if ($token->tipe !== UjianToken::TIPE_KARYAWAN || (int) $token->karyawan_id !== (int) $karyawan->id) {
            return $gagal('Token ini tidak dapat digunakan pada akun kamu.');
        }

        $ujian = $token->ujian;

        if (! $ujian->is_aktif) {
            return $gagal('Ujian ini sedang tidak aktif.');
        }
        if ($ujian->soal()->count() === 0) {
            return $gagal('Ujian ini belum memiliki soal.');
        }

        try {
            $hasil = DB::transaction(function () use ($ujian, $karyawan, $token) {
                $hasil = $this->pengerjaan->mulai($ujian, $karyawan->id, null);

                // Atomik: kalau token keburu dipakai request lain, seluruh transaksi dibatalkan.
                if (! $this->tokens->klaim($token, $hasil->id)) {
                    throw new TokenException('Token tidak valid atau sudah digunakan.');
                }

                return $hasil;
            });
        } catch (TokenException $e) {
            return $gagal($e->getMessage());
        } catch (UniqueConstraintViolationException) {
            // Sudah pernah mengerjakan ujian ini (satu kali per ujian).
            return redirect()->route('karyawan.ujian.index')->with('error', 'Kamu sudah pernah mengerjakan ujian ini.');
        }

        return redirect()->route('karyawan.ujian.kerjakan', $hasil);
    }

    /* =========================================================
     |  Halaman mengerjakan
     * =========================================================*/

    public function kerjakan(HasilUjian $hasilUjian)
    {
        $this->pastikanMilik($hasilUjian);

        if ($hasilUjian->status !== 'berlangsung') {
            return redirect($this->rute('hasil', $hasilUjian));
        }

        if ($hasilUjian->sisaDetik() <= 0) {
            $this->penilaian->finalisasiPengerjaan($hasilUjian);

            return redirect($this->rute('hasil', $hasilUjian))
                ->with('info', 'Waktu pengerjaan sudah habis, jawabanmu otomatis dikumpulkan.');
        }

        $hasilUjian->load([
            'ujian',
            'karyawan',
            'pesertaBaru',
            'jawaban' => fn ($q) => $q->orderBy('urutan'),
            'jawaban.soal',
        ]);

        // Hanya kirim data yang aman ke browser: TANPA kunci jawaban.
        $soal = $hasilUjian->jawaban->map(function (JawabanPeserta $j) {
            $item = [
                'id' => $j->id,
                'tipe' => $j->tipe,
                'pertanyaan' => $j->soal->pertanyaan,
                'jawabanIndex' => $j->jawaban_index,
                'jawabanTeks' => $j->jawaban_teks,
            ];

            if ($j->tipe === 'pg') {
                $opsi = $j->soal->opsi ?? [];
                $item['opsi'] = collect($j->urutan_opsi ?? array_keys($opsi))
                    ->map(fn ($idx) => ['index' => $idx, 'teks' => $opsi[$idx] ?? ''])
                    ->values();
            }

            return $item;
        })->values();

        return view('karyawan.ujian-kerjakan', [
            'hasilUjian' => $hasilUjian,
            'soal' => $soal,
            'sisaDetik' => max(0, $hasilUjian->sisaDetik()),
            'urlJawab' => $this->rute('jawab', $hasilUjian),
            'urlSelesai' => $this->rute('selesai', $hasilUjian),
        ]);
    }

    /**
     * Simpan satu jawaban (dipanggil otomatis dari browser setiap jawaban berubah).
     */
    public function jawab(Request $request, HasilUjian $hasilUjian)
    {
        $this->pastikanMilik($hasilUjian);

        if ($hasilUjian->status !== 'berlangsung' || $hasilUjian->sisaDetik() + self::TOLERANSI_DETIK <= 0) {
            if ($hasilUjian->status === 'berlangsung') {
                $this->penilaian->finalisasiPengerjaan($hasilUjian);
            }

            return response()->json([
                'selesai' => true,
                'redirect' => $this->rute('hasil', $hasilUjian),
            ], 409);
        }

        $data = $request->validate([
            'jawaban_id' => ['required', 'integer'],
            'jawaban_index' => ['nullable', 'integer', 'min:0'],
            'jawaban_teks' => ['nullable', 'string', 'max:10000'],
        ]);

        $jawaban = $hasilUjian->jawaban()->whereKey($data['jawaban_id'])->firstOrFail();

        if ($jawaban->tipe === 'pg') {
            $index = isset($data['jawaban_index']) ? (int) $data['jawaban_index'] : null;

            if ($index !== null && ! in_array($index, $jawaban->urutan_opsi ?? [], true)) {
                return response()->json(['message' => 'Opsi jawaban tidak valid.'], 422);
            }

            $jawaban->update(['jawaban_index' => $index]);
        } elseif ($jawaban->tipe === 'tf') {
            // 1 = Benar, 0 = Salah
            $index = isset($data['jawaban_index']) ? (int) $data['jawaban_index'] : null;

            if ($index !== null && ! in_array($index, [0, 1], true)) {
                return response()->json(['message' => 'Jawaban tidak valid.'], 422);
            }

            $jawaban->update(['jawaban_index' => $index]);
        } else {
            $teks = trim($data['jawaban_teks'] ?? '');
            $jawaban->update(['jawaban_teks' => $teks === '' ? null : $teks]);
        }

        return response()->json(['ok' => true]);
    }

    public function selesai(HasilUjian $hasilUjian)
    {
        $this->pastikanMilik($hasilUjian);

        if ($hasilUjian->status === 'berlangsung') {
            $this->penilaian->finalisasiPengerjaan($hasilUjian);
        }

        return redirect($this->rute('hasil', $hasilUjian));
    }

    public function hasil(HasilUjian $hasilUjian)
    {
        $this->pastikanMilik($hasilUjian);

        if ($hasilUjian->status === 'berlangsung') {
            return redirect($this->rute('kerjakan', $hasilUjian));
        }

        $hasilUjian->load('ujian', 'pesertaBaru');

        $totalSoal = $hasilUjian->jawaban()->count();
        $terjawab = $hasilUjian->jawaban()
            ->where(function ($q) {
                $q->whereNotNull('jawaban_index')->orWhereNotNull('jawaban_teks');
            })
            ->count();

        // Skor langsung (PG + Benar/Salah). Bersifat sementara selama masih ada esai yang belum dinilai.
        $auto = $hasilUjian->jawaban()->whereIn('tipe', PenilaianService::TIPE_OTOMATIS);
        $jumlahAuto = (clone $auto)->count();
        $jumlahBenar = (clone $auto)->where('benar', true)->count();
        $jumlahEsai = $hasilUjian->jawaban()->whereIn('tipe', PenilaianService::TIPE_MANUAL)->count();

        return view('karyawan.ujian-hasil', [
            'hasilUjian' => $hasilUjian,
            'totalSoal' => $totalSoal,
            'terjawab' => $terjawab,
            'jumlahAuto' => $jumlahAuto,
            'jumlahBenar' => $jumlahBenar,
            'jumlahEsai' => $jumlahEsai,
            'pesertaBaru' => $hasilUjian->peserta_baru_id !== null,
            'urlPdf' => $this->rute('pdf', $hasilUjian),
            'urlKembali' => $hasilUjian->peserta_baru_id !== null ? route('welcome') : route('karyawan.ujian.index'),
        ]);
    }

    /** Cetak / unduh hasil ujian (ringkasan, tanpa kunci jawaban). */
    public function pdf(HasilUjian $hasilUjian)
    {
        $this->pastikanMilik($hasilUjian);

        abort_if($hasilUjian->status === 'berlangsung', 404);

        return $this->responsPdf($hasilUjian, detail: false);
    }

    /* =========================================================
     |  Helper
     * =========================================================*/

    /** Karyawan aktif: harus pemilik hasil. Peserta baru: id hasil harus tercatat di session browser ini. */
    protected function pastikanMilik(HasilUjian $hasilUjian): void
    {
        if (auth('karyawan')->check()) {
            abort_unless((int) $hasilUjian->karyawan_id === (int) auth('karyawan')->id(), 403);

            return;
        }

        abort_unless(
            $hasilUjian->peserta_baru_id !== null
                && in_array($hasilUjian->id, session('peserta.hasil_ids', []), true),
            403
        );
    }

    protected function prefixRute(): string
    {
        return auth('karyawan')->check() ? 'karyawan.ujian' : 'peserta.ujian';
    }

    protected function rute(string $nama, HasilUjian $hasilUjian): string
    {
        return route($this->prefixRute() . '.' . $nama, $hasilUjian);
    }
}
