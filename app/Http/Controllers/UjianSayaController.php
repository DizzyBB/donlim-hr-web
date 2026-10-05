<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\HasilUjian;
use App\Models\JawabanPeserta;
use App\Models\Ujian;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UjianSayaController extends Controller
{
    /**
     * Poin mentah per tipe soal. Harus sama dengan PenilaianController::POIN_MENTAH.
     */
    protected const POIN_MENTAH = ['pg' => 5, 'bs' => 10, 'ur' => 20];

    /**
     * Toleransi (detik) untuk simpanan jawaban yang tiba sedikit setelah batas waktu
     * (jaringan lambat) supaya jawaban terakhir tidak hilang.
     */
    protected const TOLERANSI_DETIK = 10;

    /* =========================================================
     |  Daftar ujian untuk karyawan yang sedang login
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
                    $this->finalisasi($h);
                }
            });

        $sedangBerlangsung = HasilUjian::with('ujian')
            ->where('karyawan_id', $karyawan->id)
            ->where('status', 'berlangsung')
            ->get();

        // Ujian yang tampil: perorangan (khusus karyawan ini) + karyawan baru (untuk semua),
        // hanya selama masa berlaku, dan belum pernah dikerjakan.
        $sekarang = now()->format('H:i:s');

        $tersedia = Ujian::query()
            ->withCount('soal')
            ->has('soal')
            ->where(function ($q) use ($karyawan) {
                $q->where('jenis', 'karyawan_baru')
                  ->orWhere(function ($qq) use ($karyawan) {
                      $qq->where('jenis', 'perorangan')->where('karyawan_id', $karyawan->id);
                  });
            })
            ->whereTime('jam_mulai', '<=', $sekarang)
            ->whereTime('jam_selesai', '>', $sekarang)
            ->whereDoesntHave('hasilUjian', fn ($q) => $q->where('karyawan_id', $karyawan->id))
            ->orderBy('jam_selesai')
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
     |  Mulai ujian
     * =========================================================*/

    public function mulai(Ujian $ujian)
    {
        $karyawan = auth('karyawan')->user();

        $sudahAda = HasilUjian::where('ujian_id', $ujian->id)
            ->where('karyawan_id', $karyawan->id)
            ->first();

        if ($sudahAda) {
            return $sudahAda->status === 'berlangsung'
                ? redirect()->route('karyawan.ujian.kerjakan', $sudahAda)
                : redirect()->route('karyawan.ujian.index')->with('error', 'Kamu sudah mengerjakan ujian ini.');
        }

        if (! $this->bolehDiikuti($ujian, $karyawan)) {
            return redirect()->route('karyawan.ujian-index')
                ->with('error', 'Ujian ini tidak tersedia untuk kamu pada waktu sekarang.');
        }

        if ($ujian->soal()->count() === 0) {
            return redirect()->route('karyawan.ujian-index')->with('error', 'Ujian ini belum memiliki soal.');
        }

        try {
            $hasil = DB::transaction(function () use ($ujian, $karyawan) {
                $hasil = HasilUjian::create([
                    'ujian_id' => $ujian->id,
                    'karyawan_id' => $karyawan->id,
                    'status' => 'berlangsung',
                    'waktu_mulai' => now(),
                ]);

                $daftarSoal = $ujian->soal()->orderBy('soals.id')->get();
                if ($ujian->acak_soal) {
                    $daftarSoal = $daftarSoal->shuffle();
                }

                foreach ($daftarSoal->values() as $i => $soal) {
                    $urutanOpsi = null;
                    if ($soal->tipe === 'pg') {
                        $urutanOpsi = array_keys($soal->opsi ?? []);
                        if ($ujian->acak_jawaban) {
                            shuffle($urutanOpsi);
                        }
                    }

                    JawabanPeserta::create([
                        'hasil_ujian_id' => $hasil->id,
                        'soal_id' => $soal->id,
                        'tipe' => $soal->tipe,
                        'urutan' => $i + 1,
                        'urutan_opsi' => $urutanOpsi,
                    ]);
                }

                return $hasil;
            });
        } catch (UniqueConstraintViolationException $e) {
            // Klik ganda / dua tab: pengerjaan sudah terbuat oleh request lain.
            return redirect()->route('karyawan.ujian.index');
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
            return redirect()->route('karyawan.ujian.hasil', $hasilUjian);
        }

        if ($hasilUjian->sisaDetik() <= 0) {
            $this->finalisasi($hasilUjian);

            return redirect()->route('karyawan.ujian.hasil', $hasilUjian)
                ->with('info', 'Waktu pengerjaan sudah habis, jawabanmu otomatis dikumpulkan.');
        }

        $hasilUjian->load([
            'ujian',
            'karyawan',
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
                $this->finalisasi($hasilUjian);
            }

            return response()->json([
                'selesai' => true,
                'redirect' => route('karyawan.ujian.hasil', $hasilUjian),
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
            $this->finalisasi($hasilUjian);
        }

        return redirect()->route('karyawan.ujian.hasil', $hasilUjian);
    }

    public function hasil(HasilUjian $hasilUjian)
    {
        $this->pastikanMilik($hasilUjian);

        if ($hasilUjian->status === 'berlangsung') {
            return redirect()->route('karyawan.ujian.kerjakan', $hasilUjian);
        }

        $hasilUjian->load('ujian');

        $totalSoal = $hasilUjian->jawaban()->count();
        $terjawab = $hasilUjian->jawaban()
            ->where(function ($q) {
                $q->whereNotNull('jawaban_index')->orWhereNotNull('jawaban_teks');
            })
            ->count();

        return view('karyawan.ujian-hasil', compact('hasilUjian', 'totalSoal', 'terjawab'));
    }

    /* =========================================================
     |  Helper
     * =========================================================*/

    protected function pastikanMilik(HasilUjian $hasilUjian): void
    {
        abort_unless((int) $hasilUjian->karyawan_id === (int) auth('karyawan')->id(), 403);
    }

    /**
     * Ujian boleh diikuti kalau: ditujukan untuk karyawan ini (atau untuk semua),
     * dan jam sekarang berada di dalam masa berlaku (jam mulai <= sekarang < jam selesai).
     */
    protected function bolehDiikuti(Ujian $ujian, $karyawan): bool
    {
        $untukKaryawanIni = $ujian->jenis === 'karyawan_baru'
            || (int) $ujian->karyawan_id === (int) $karyawan->id;

        $sekarang = now()->format('H:i:s');

        return $untukKaryawanIni
            && $sekarang >= $ujian->jam_mulai
            && $sekarang < $ujian->jam_selesai;
    }

    /**
     * Kumpulkan pengerjaan: nilai otomatis untuk PG, soal manual dibiarkan menunggu
     * penilaian admin. Kalau tidak ada jawaban yang perlu dinilai manual, nilai
     * langsung dihitung dan status menjadi selesai_dinilai.
     */
    protected function finalisasi(HasilUjian $hasilUjian): void
    {
        DB::transaction(function () use ($hasilUjian) {
            // Kunci baris supaya finalisasi tidak jalan dua kali bersamaan.
            $hasil = HasilUjian::with(['ujian', 'jawaban.soal'])
                ->lockForUpdate()
                ->find($hasilUjian->id);

            if (! $hasil || $hasil->status !== 'berlangsung') {
                return;
            }

            $mentahPG = 0;
            $adaYangPerluDinilai = false;

            foreach ($hasil->jawaban as $j) {
                if ($j->tipe === 'pg') {
                    $benar = $j->jawaban_index !== null
                        && $j->soal
                        && $j->jawaban_index === $j->soal->jawaban_index;
                    $skor = $benar ? self::POIN_MENTAH['pg'] : 0;
                    $mentahPG += $skor;

                    $j->update(['benar' => $benar, 'skor' => $skor]);
                } elseif (blank($j->jawaban_teks)) {
                    // Soal manual yang tidak dijawab: langsung 0, tidak perlu dinilai penilai.
                    $j->update(['skor' => 0]);
                } else {
                    $adaYangPerluDinilai = true;
                }
            }

            $update = [
                'waktu_selesai' => now()->min($hasil->batasWaktu()),
            ];

            if ($adaYangPerluDinilai) {
                $update['status'] = 'belum_dinilai';
            } else {
                $maksimalMentah = $hasil->jawaban->sum(fn ($j) => self::POIN_MENTAH[$j->tipe] ?? 0);
                $nilaiPG = $maksimalMentah > 0 ? round(($mentahPG / $maksimalMentah) * 100, 2) : 0;

                $update += [
                    'status' => 'selesai_dinilai',
                    'nilai_pilihan_ganda' => $nilaiPG,
                    'nilai_manual' => 0,
                    'nilai_total' => $nilaiPG,
                    'dinilai_pada' => now(),
                ];
            }

            $hasil->update($update);
        });
    }
}
