<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\MembuatPdfHasil;
use App\Models\HasilUjian;
use App\Models\Ujian;
use App\Services\DuplikatService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class RiwayatUjianController extends Controller
{
    use MembuatPdfHasil;

    public function __construct(protected DuplikatService $duplikat)
    {
    }

    public function index(Request $request)
    {
        $hasilUjian = $this->query($request)
            ->latest('waktu_selesai')
            ->paginate(10)
            ->withQueryString();

        return view('riwayat.riwayat-ujian-index', [
            'hasilUjian' => $hasilUjian,
            'daftarUjian' => Ujian::orderBy('nama_ujian')->get(['id', 'nama_ujian']),
            'duplikat' => $this->duplikat->petakan($hasilUjian->getCollection()),
        ]);
    }

    public function show(HasilUjian $hasilUjian)
    {
        $hasilUjian->load(['ujian', 'karyawan', 'pesertaBaru', 'jawaban.soal', 'penilai', 'pengoreksi']);

        $auto = $hasilUjian->jawaban->whereIn('tipe', ['pg', 'tf']);

        return view('riwayat.riwayat-ujian-show', [
            'hasilUjian' => $hasilUjian,
            'jumlahBenar' => $auto->where('benar', true)->count(),
            'jumlahSalah' => $auto->where('benar', false)->count(),
            'jumlahAuto' => $auto->count(),
            'infoDuplikat' => $hasilUjian->pesertaBaru ? $this->duplikat->untukPesertaBaru($hasilUjian->pesertaBaru) : null,
        ]);
    }

    /** Cetak / unduh hasil ujian (lengkap dengan detail jawaban untuk HRD). */
    public function pdf(HasilUjian $hasilUjian)
    {
        abort_if($hasilUjian->status === 'berlangsung', 404);

        return $this->responsPdf($hasilUjian, detail: true);
    }

    /** Ekspor rekap hasil ujian ke Excel (xlsx) atau CSV, mengikuti filter yang sedang dipakai. */
    public function export(Request $request)
    {
        $format = $request->input('format', 'xlsx');
        abort_unless(in_array($format, ['xlsx', 'csv'], true), 404);

        $hasil = $this->query($request)->orderBy('hasil_ujians.id')->get();
        $peta = $this->duplikat->petakan($hasil);

        $judul = ['No', 'Nama Peserta', 'Nomor Karyawan', 'Departemen', 'Jenis Peserta', 'Ujian', 'Tanggal Tes',
            'Waktu Mulai', 'Waktu Selesai', 'Nilai Otomatis (PG + B/S)', 'Nilai Esai', 'Nilai Total (%)',
            'Passing Grade', 'Status Penilaian', 'Kelulusan', 'Peringatan Duplikat'];

        $baris = [];
        foreach ($hasil as $i => $h) {
            $baris[] = [
                $i + 1,
                $h->nama_peserta,
                $h->karyawan?->nik ?? '-',
                $h->departemen_peserta ?? '-',
                $h->is_peserta_baru ? 'Karyawan Baru' : 'Karyawan Aktif',
                $h->ujian?->nama_ujian ?? '-',
                $h->waktu_mulai?->format('Y-m-d') ?? '-',
                $h->waktu_mulai?->format('Y-m-d H:i') ?? '-',
                $h->waktu_selesai?->format('Y-m-d H:i') ?? '-',
                (float) $h->nilai_pilihan_ganda,
                (float) $h->nilai_manual,
                (float) $h->nilai_total,
                $h->passing_grade !== null ? (float) $h->passing_grade : '-',
                $h->status_label,
                $h->kelulusan_label,
                isset($peta[$h->id]) ? $peta[$h->id]['pesan'] : '',
            ];
        }

        $nama = 'rekap-hasil-ujian-' . now()->format('Ymd-His');

        if ($format === 'csv') {
            return response()->streamDownload(function () use ($judul, $baris) {
                $out = fopen('php://output', 'w');
                fwrite($out, "\xEF\xBB\xBF"); // BOM supaya Excel membaca UTF-8 dengan benar
                fputcsv($out, $judul, ';');
                foreach ($baris as $b) {
                    fputcsv($out, array_map([$this, 'amankanSel'], $b), ';');
                }
                fclose($out);
            }, $nama . '.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Rekap Hasil Ujian');
        $sheet->fromArray($judul, null, 'A1');
        $sheet->getStyle('A1:P1')->getFont()->setBold(true);

        foreach ($baris as $r => $b) {
            foreach (array_map([$this, 'amankanSel'], $b) as $c => $nilai) {
                // Teks selalu ditulis sebagai string agar tidak pernah dievaluasi sebagai rumus.
                $sel = $sheet->getCell([$c + 1, $r + 2]);
                is_string($nilai)
                    ? $sel->setValueExplicit($nilai, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING)
                    : $sel->setValue($nilai);
            }
        }
        foreach (range('A', 'P') as $kolom) {
            $sheet->getColumnDimension($kolom)->setAutoSize(true);
        }

        return response()->streamDownload(function () use ($spreadsheet) {
            (new Xlsx($spreadsheet))->save('php://output');
        }, $nama . '.xlsx', ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }

    /**
     * Nama peserta karyawan baru diketik sendiri oleh peserta, jadi sel yang diawali = + - @ dinetralkan
     * supaya tidak dieksekusi sebagai rumus saat file dibuka di Excel (CSV/formula injection).
     */
    protected function amankanSel($nilai)
    {
        if (is_string($nilai) && $nilai !== '' && in_array($nilai[0], ['=', '+', '-', '@', "\t", "\r"], true)) {
            return "'" . $nilai;
        }

        return $nilai;
    }

    /** Query bersama untuk daftar & ekspor, supaya filter keduanya selalu identik. */
    protected function query(Request $request): Builder
    {
        return HasilUjian::query()
            ->with(['ujian', 'karyawan', 'pesertaBaru'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->when($request->filled('search'), function ($q) use ($request) {
                $s = '%' . $request->input('search') . '%';
                // Dibungkus satu grup supaya filter lain (status, jenis, dst.) tetap berlaku.
                $q->where(function ($qq) use ($s) {
                    $qq->whereHas('ujian', fn ($u) => $u->where('nama_ujian', 'like', $s))
                        ->orWhereHas('karyawan', fn ($k) => $k->where('nama', 'like', $s)->orWhere('nik', 'like', $s))
                        ->orWhereHas('pesertaBaru', fn ($p) => $p->where('nama', 'like', $s));
                });
            })
            ->when($request->filled('jenis'), fn ($q) => $q->whereHas('ujian', fn ($u) => $u->where('jenis', $request->input('jenis'))))
            ->when($request->filled('ujian_id'), fn ($q) => $q->where('ujian_id', $request->integer('ujian_id')))
            ->when($request->input('kelulusan') === 'lulus', fn ($q) => $q->where('lulus', true))
            ->when($request->input('kelulusan') === 'tidak_lulus', fn ($q) => $q->where('lulus', false))
            ->when($request->input('kelulusan') === 'pending', fn ($q) => $q->where('status', 'belum_dinilai'))
            ->when($request->filled('dari'), fn ($q) => $q->whereDate('waktu_mulai', '>=', $request->input('dari')))
            ->when($request->filled('sampai'), fn ($q) => $q->whereDate('waktu_mulai', '<=', $request->input('sampai')));
    }
}
