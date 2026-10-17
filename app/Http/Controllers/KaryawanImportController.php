<?php

namespace App\Http\Controllers;

use App\Models\Departemen;
use App\Models\Karyawan;
use App\Support\Nama;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Bulk import karyawan AKTIF (Excel / CSV): NIK, Nama, Departemen.
 *
 * Dua langkah: (1) upload -> preview dengan penanda tiap baris, (2) konfirmasi -> hanya baris "baru" disimpan.
 * NIK yang sudah ada tidak ditimpa. Password awal = NIK dan karyawan wajib menggantinya saat login pertama.
 */
class KaryawanImportController extends Controller
{
    public const MAKS_BARIS = 500;

    private const SESSION_KEY = 'karyawan_import';

    public function form()
    {
        return view('karyawan.import', [
            'preview' => session(self::SESSION_KEY),
            'maksBaris' => self::MAKS_BARIS,
        ]);
    }

    public function template()
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Karyawan');
        $sheet->fromArray([['NIK', 'Nama', 'Departemen'], ['1001', 'Contoh Nama', 'HRD']], null, 'A1');
        $sheet->getStyle('A1:C1')->getFont()->setBold(true);
        // Kolom NIK diformat teks supaya angka nol di depan tidak hilang.
        $sheet->getStyle('A2:A' . (self::MAKS_BARIS + 1))->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_TEXT);
        $sheet->setCellValueExplicit('A2', '1001', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        foreach (['A' => 18, 'B' => 32, 'C' => 24] as $kolom => $lebar) {
            $sheet->getColumnDimension($kolom)->setWidth($lebar);
        }

        return response()->streamDownload(function () use ($spreadsheet) {
            (new Xlsx($spreadsheet))->save('php://output');
        }, 'template-import-karyawan.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /** Langkah 1: baca file dan tampilkan preview. Belum menyimpan apa pun ke tabel karyawan. */
    public function preview(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv,txt', 'max:5120'],
        ], [
            'file.required' => 'Pilih file Excel/CSV terlebih dahulu.',
            'file.mimes' => 'Format file harus .xlsx, .xls, atau .csv.',
            'file.max' => 'Ukuran file maksimal 5 MB.',
        ]);

        try {
            $baris = $this->bacaFile($request->file('file')->getRealPath());
        } catch (\Throwable $e) {
            return back()->with('error', 'File tidak bisa dibaca. Pastikan formatnya benar dan gunakan template yang disediakan.');
        }

        if (count($baris) === 0) {
            return back()->with('error', 'File tidak berisi data karyawan.');
        }
        if (count($baris) > self::MAKS_BARIS) {
            return back()->with('error', 'Maksimal ' . self::MAKS_BARIS . ' baris per file. File kamu berisi ' . count($baris) . ' baris, silakan pecah menjadi beberapa file.');
        }

        $rows = $this->tandai($baris);

        session([self::SESSION_KEY => [
            'file' => $request->file('file')->getClientOriginalName(),
            'rows' => $rows,
            'ringkasan' => $this->ringkasan($rows),
        ]]);

        return redirect()->route('karyawan.import');
    }

    /** Langkah 2: simpan baris berstatus "baru". Status diperiksa ulang karena data bisa berubah sejak preview. */
    public function konfirmasi(Request $request)
    {
        $data = session(self::SESSION_KEY);

        if (! $data) {
            return redirect()->route('karyawan.import')->with('error', 'Sesi preview sudah berakhir. Silakan upload ulang file.');
        }

        @set_time_limit(300);

        $rows = $this->tandai(array_map(fn ($r) => [
            'baris' => $r['baris'], 'nik' => $r['nik'], 'nama' => $r['nama'], 'departemen' => $r['departemen'],
        ], $data['rows']));

        $baru = array_values(array_filter($rows, fn ($r) => $r['status'] === 'baru'));

        if ($baru === []) {
            session()->forget(self::SESSION_KEY);

            return redirect()->route('karyawan.import')->with('error', 'Tidak ada data baru untuk diimport.');
        }

        // Cocokkan nama departemen ke master (tanpa membedakan huruf besar/kecil); buat bila belum ada.
        $master = Departemen::pluck('nama')->mapWithKeys(fn ($n) => [mb_strtolower($n) => $n])->all();

        DB::transaction(function () use ($baru, &$master) {
            $sekarang = now();
            $batch = [];

            foreach ($baru as $r) {
                $dept = $r['departemen'];
                if ($dept !== null) {
                    $kunci = mb_strtolower($dept);
                    if (! isset($master[$kunci])) {
                        Departemen::create(['nama' => $dept]);
                        $master[$kunci] = $dept;
                    }
                    $dept = $master[$kunci];
                }

                $batch[] = [
                    'nik' => $r['nik'],
                    'nama' => $r['nama'],
                    'departemen' => $dept,
                    // Password awal = NIK. Cost 10 supaya import ratusan baris tetap cepat; wajib diganti saat login pertama.
                    'password' => Hash::make($r['nik'], ['rounds' => 10]),
                    'must_change_password' => true,
                    'role' => 'karyawan',
                    'created_at' => $sekarang,
                    'updated_at' => $sekarang,
                ];
            }

            foreach (array_chunk($batch, 100) as $potongan) {
                DB::table('karyawans')->insert($potongan);
            }
        });

        $ringkasan = $this->ringkasan($rows);
        session()->forget(self::SESSION_KEY);

        $pesan = count($baru) . ' karyawan berhasil diimport.';
        $dilewati = $ringkasan['duplikat_db'] + $ringkasan['duplikat_file'] + $ringkasan['tidak_valid'];
        if ($dilewati > 0) {
            $pesan .= " {$dilewati} baris dilewati (NIK ganda / tidak valid).";
        }

        return redirect()->route('karyawan.index')->with('success', $pesan);
    }

    /* ===================== helper ===================== */

    /**
     * Baca baris data dari xlsx/xls/csv. Header dikenali dari namanya (NIK / Nomor Karyawan, Nama, Departemen);
     * kalau tidak ada header yang dikenali, dipakai urutan kolom A, B, C.
     *
     * @return array<int, array{baris:int, nik:string, nama:string, departemen:?string}>
     */
    protected function bacaFile(string $path): array
    {
        $reader = IOFactory::createReaderForFile($path);
        if (method_exists($reader, 'setReadDataOnly')) {
            $reader->setReadDataOnly(true);
        }

        $sheet = $reader->load($path)->getActiveSheet();
        $data = $sheet->toArray(null, true, false, false);

        if ($data === []) {
            return [];
        }

        $peta = ['nik' => 0, 'nama' => 1, 'departemen' => 2];
        $mulai = 0;

        $header = array_map(fn ($h) => $this->normalHeader($h), $data[0]);
        $kolomNik = $this->cariKolom($header, ['nik', 'nomorkaryawan', 'nokaryawan', 'noinduk', 'nomorindukkaryawan', 'employeeid']);
        $kolomNama = $this->cariKolom($header, ['nama', 'namalengkap', 'namakaryawan', 'name']);

        if ($kolomNik !== null && $kolomNama !== null) {
            $peta = [
                'nik' => $kolomNik,
                'nama' => $kolomNama,
                'departemen' => $this->cariKolom($header, ['departemen', 'department', 'dept', 'bagian']),
            ];
            $mulai = 1;
        }

        $hasil = [];
        for ($i = $mulai; $i < count($data); $i++) {
            $row = $data[$i];
            $nik = $this->teks($row[$peta['nik']] ?? '');
            $nama = $this->teks($row[$peta['nama']] ?? '');
            $dept = $peta['departemen'] === null ? '' : $this->teks($row[$peta['departemen']] ?? '');

            if ($nik === '' && $nama === '' && $dept === '') {
                continue; // baris kosong
            }

            $hasil[] = [
                'baris' => $i + 1,
                'nik' => $nik,
                'nama' => $nama,
                'departemen' => $dept === '' ? null : $dept,
            ];
        }

        return $hasil;
    }

    protected function normalHeader($h): string
    {
        return preg_replace('/[^a-z0-9]/', '', mb_strtolower((string) $h));
    }

    protected function cariKolom(array $header, array $alias): ?int
    {
        foreach ($header as $i => $h) {
            if (in_array($h, $alias, true)) {
                return $i;
            }
        }

        return null;
    }

    /** Sel Excel angka (mis. NIK 1.0E+15) dikembalikan ke teks tanpa notasi ilmiah. */
    protected function teks($nilai): string
    {
        if (is_float($nilai) && floor($nilai) === $nilai) {
            return number_format($nilai, 0, '', '');
        }

        return trim(preg_replace('/\s+/u', ' ', (string) $nilai));
    }

    /**
     * Beri status tiap baris:
     *  baru          -> akan disimpan
     *  duplikat_db   -> NIK sudah terdaftar (dilewati, tidak ditimpa)
     *  duplikat_file -> NIK muncul lebih dari sekali di file (hanya kemunculan pertama dipakai)
     *  tidak_valid   -> NIK / nama kosong atau terlalu panjang
     * Tambahan peringatan (tidak melewati baris): nama_sama = nama cocok dengan karyawan lain ber-NIK berbeda.
     */
    protected function tandai(array $baris): array
    {
        $niks = array_values(array_unique(array_filter(array_column($baris, 'nik'), fn ($n) => $n !== '')));
        $sudahAda = $niks === [] ? [] : array_flip(Karyawan::whereIn('nik', $niks)->pluck('nik')->all());

        $namaTerdaftar = [];
        foreach (Karyawan::pluck('nama', 'nik') as $nik => $nama) {
            $namaTerdaftar[Nama::normalisasi($nama)][] = (string) $nik;
        }

        $terlihat = [];
        $namaDiFile = [];
        $hasil = [];

        foreach ($baris as $r) {
            $status = 'baru';
            $catatan = null;

            if ($r['nik'] === '' || $r['nama'] === '') {
                $status = 'tidak_valid';
                $catatan = $r['nik'] === '' ? 'NIK kosong' : 'Nama kosong';
            } elseif (mb_strlen($r['nik']) > 255 || mb_strlen($r['nama']) > 255 || mb_strlen((string) $r['departemen']) > 255) {
                $status = 'tidak_valid';
                $catatan = 'Isi kolom terlalu panjang';
            } elseif (isset($sudahAda[$r['nik']])) {
                $status = 'duplikat_db';
                $catatan = 'NIK sudah terdaftar, tidak ditimpa';
            } elseif (isset($terlihat[$r['nik']])) {
                $status = 'duplikat_file';
                $catatan = 'NIK ganda di file (baris ' . $terlihat[$r['nik']] . ')';
            }

            if ($status === 'baru' || $status === 'duplikat_file') {
                $terlihat[$r['nik']] = $terlihat[$r['nik']] ?? $r['baris'];
            }

            $peringatan = null;
            if ($status === 'baru') {
                $norm = Nama::normalisasi($r['nama']);
                if (isset($namaTerdaftar[$norm])) {
                    $peringatan = 'Nama sama dengan karyawan NIK ' . implode(', ', $namaTerdaftar[$norm]);
                } elseif (isset($namaDiFile[$norm])) {
                    $peringatan = 'Nama sama dengan baris ' . $namaDiFile[$norm] . ' di file ini';
                }
                $namaDiFile[$norm] = $namaDiFile[$norm] ?? $r['baris'];
            }

            $hasil[] = $r + ['status' => $status, 'catatan' => $catatan, 'peringatan' => $peringatan];
        }

        return $hasil;
    }

    protected function ringkasan(array $rows): array
    {
        $r = ['total' => count($rows), 'baru' => 0, 'duplikat_db' => 0, 'duplikat_file' => 0, 'tidak_valid' => 0, 'peringatan' => 0];

        foreach ($rows as $row) {
            $r[$row['status']]++;
            if ($row['peringatan']) {
                $r['peringatan']++;
            }
        }

        return $r;
    }
}
