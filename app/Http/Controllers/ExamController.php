<?php

namespace App\Http\Controllers;


use App\Http\Controllers\Controller;
use App\Models\Soal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use PhpOffice\PhpSpreadsheet\IOFactory as SpreadsheetIOFactory;
use PhpOffice\PhpWord\IOFactory as WordIOFactory;

class ExamController extends Controller
{
     public function index()
    {
        return view('examination.input-soal-index');
    }

    public function data()
    {
        $soal = Soal::latest()->get()->map(fn (Soal $s) => $s->toFrontendArray());

        return response()->json($soal);
    }

    public function store(Request $request)
    {
        $validated = $this->validasi($request);

        $soal = Soal::create($validated);

        return response()->json([
            'message' => 'Soal berhasil disimpan',
            'soal' => $soal->toFrontendArray(),
        ], 201);
    }

    public function update(Request $request, Soal $soal)
    {
        $validated = $this->validasi($request);

        $soal->update($validated);

        return response()->json([
            'message' => 'Soal berhasil diperbarui',
            'soal' => $soal->fresh()->toFrontendArray(),
        ]);
    }

    public function destroy(Soal $soal)
    {
        $soal->delete();

        return response()->json(['message' => 'Soal dihapus']);
    }

    protected function validasi(Request $request): array
    {
        $tipe = $request->input('tipe');

        $rules = [
            'tipe' => ['required', Rule::in(['pg', 'tf', 'ur'])],
            'pertanyaan' => ['required', 'string'],
            'kategori' => ['nullable', 'string', 'max:255'],
            'poin' => ['nullable', 'integer', 'min:1'],
        ];

        if ($tipe === 'pg') {
            $rules['opsi'] = ['required', 'array', 'min:2'];
            $rules['opsi.*'] = ['required', 'string'];
            $rules['jawabanIndex'] = ['required', 'integer', 'min:0'];
        } elseif ($tipe === 'tf') {
            $rules['jawabanTF'] = ['required', Rule::in(['benar', 'salah'])];
        } elseif ($tipe === 'ur') {
            $rules['kunci'] = ['nullable', 'string'];
        }

        $validated = Validator::make($request->all(), $rules)->validate();

        return [
            'tipe' => $validated['tipe'],
            'pertanyaan' => $validated['pertanyaan'],
            'kategori' => $validated['kategori'] ?? null,
            'poin' => $validated['poin'] ?? 10,
            'opsi' => $validated['opsi'] ?? null,
            'jawaban_index' => $validated['jawabanIndex'] ?? null,
            'jawaban_tf' => isset($validated['jawabanTF']) ? $validated['jawabanTF'] === 'benar' : null,
            'kunci' => $validated['kunci'] ?? null,
        ];
    }

    /* =========================================================
     |  BANK SOAL (tabel semua soal + import Excel/Word)
     * =========================================================*/

    public function bankSoal(Request $request)
    {
        $query = Soal::query()->latest();

        if ($request->filled('tipe')) {
            $query->tipe($request->input('tipe'));
        }

        if ($request->filled('search')) {
            $query->where('pertanyaan', 'like', '%' . $request->input('search') . '%');
        }

        $soal = $query->paginate(10)->withQueryString();

        return view('examination.bank-soal-index', compact('soal'));
    }

    /**
     * Download template import (excel|word).
     * Simpan file template di storage/app/templates/ lalu di-download di sini.
     */
    public function template(string $type)
    {
        abort_unless(in_array($type, ['excel', 'word']), 404);

        // Template Excel dibuat langsung supaya selalu sesuai tipe soal terbaru (PG, Benar/Salah, Esai).
        if ($type === 'excel') {
            return $this->templateExcel();
        }

        $path = $type === 'excel'
            ? storage_path('app/templates/template-bank-soal.xlsx')
            : storage_path('app/templates/template-bank-soal.docx');

        abort_unless(file_exists($path), 404, 'Template belum tersedia di server.');

        return response()->download($path);
    }

    protected function templateExcel()
    {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Template Soal');
        $sheet->fromArray([
            ['No', 'Tipe Soal', 'Pertanyaan', 'Pilihan A', 'Pilihan B', 'Pilihan C', 'Pilihan D', 'Pilihan E', 'Jawaban Benar', 'Kategori', 'Poin'],
            [1, 'Pilihan Ganda', 'Ibu kota Indonesia adalah ...', 'Bandung', 'Jakarta', 'Surabaya', 'Medan', '', 'B', 'Umum', 10],
            [2, 'Benar / Salah', 'Matahari terbit dari barat.', '', '', '', '', '', 'Salah', 'Umum', 10],
            [3, 'Esai', 'Jelaskan alur pengajuan cuti di perusahaan.', '', '', '', '', '', 'Kunci / rubrik penilaian (opsional)', 'HR', 20],
        ], null, 'A1');
        $sheet->getStyle('A1:K1')->getFont()->setBold(true);
        $sheet->getColumnDimension('C')->setWidth(50);

        return response()->streamDownload(function () use ($spreadsheet) {
            (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet))->save('php://output');
        }, 'template-bank-soal.xlsx');
    }

    public function import(Request $request)
    {
        $request->validate([
            'format' => ['required', Rule::in(['excel', 'word'])],
            'file' => ['required', 'file', 'max:5120'],
        ]);

        $rows = $request->input('format') === 'excel'
            ? $this->bacaExcel($request->file('file'))
            : $this->bacaWord($request->file('file'));

        $imported = 0;
        $skipped = 0;
        $errors = [];

        foreach ($rows as $i => $row) {
            $baris = $i + 2; // +2: baris 1 header, index mulai dari 0

            $tipeRaw = Str::lower(trim($row['tipe_soal'] ?? ''));
            $pertanyaan = trim($row['pertanyaan'] ?? '');

            if ($pertanyaan === '' || $tipeRaw === '') {
                $skipped++;
                continue; // baris kosong / pemisah, lewati diam-diam
            }

            $tipe = match (true) {
                str_contains($tipeRaw, 'pilihan ganda') => 'pg',
                str_contains($tipeRaw, 'benar'), str_contains($tipeRaw, 'salah') => 'tf',
                str_contains($tipeRaw, 'esai'), str_contains($tipeRaw, 'essay'),
                str_contains($tipeRaw, 'uraian'), str_contains($tipeRaw, 'jawaban singkat') => 'ur',
                default => null,
            };

            if (! $tipe) {
                $errors[] = "Baris {$baris}: tipe soal \"{$row['tipe_soal']}\" tidak dikenali.";
                $skipped++;
                continue;
            }

            $data = [
                'tipe' => $tipe,
                'pertanyaan' => $pertanyaan,
                'kategori' => trim($row['kategori'] ?? '') ?: null,
                'poin' => is_numeric($row['poin'] ?? null) ? (int) $row['poin'] : 10,
            ];

            if ($tipe === 'pg') {
                $opsi = array_values(array_filter([
                    trim($row['pilihan_a'] ?? ''),
                    trim($row['pilihan_b'] ?? ''),
                    trim($row['pilihan_c'] ?? ''),
                    trim($row['pilihan_d'] ?? ''),
                    trim($row['pilihan_e'] ?? ''),
                ], fn ($v) => $v !== ''));

                $hurufBenar = Str::upper(trim($row['jawaban_benar'] ?? ''));
                $index = $hurufBenar !== '' ? (ord($hurufBenar) - 65) : -1;

                if (count($opsi) < 2 || $index < 0 || $index >= count($opsi)) {
                    $errors[] = "Baris {$baris}: opsi pilihan ganda / jawaban benar tidak valid.";
                    $skipped++;
                    continue;
                }

                $data['opsi'] = $opsi;
                $data['jawaban_index'] = $index;
            } elseif ($tipe === 'tf') {
                $nilai = Str::lower(trim($row['jawaban_benar'] ?? ''));
                if (! in_array($nilai, ['benar', 'salah'])) {
                    $errors[] = "Baris {$baris}: Jawaban Benar harus \"Benar\" atau \"Salah\".";
                    $skipped++;
                    continue;
                }
                $data['jawaban_tf'] = $nilai === 'benar';
            } else { // esai
                $data['kunci'] = trim($row['jawaban_benar'] ?? '') ?: null;
            }

            Soal::create($data);
            $imported++;
        }

        return response()->json([
            'message' => "{$imported} soal berhasil diimport" . ($skipped ? ", {$skipped} baris dilewati." : '.'),
            'imported' => $imported,
            'skipped' => $skipped,
            'errors' => $errors,
        ]);
    }

    /**
     * Baca file Excel -> array baris asosiatif dengan key snake_case
     * sesuai header: no, tipe_soal, pertanyaan, pilihan_a..e, jawaban_benar, kategori, poin
     */
    protected function bacaExcel($file): array
    {
        $spreadsheet = SpreadsheetIOFactory::load($file->getRealPath());
        $sheet = $spreadsheet->getSheetByName('Template Soal') ?? $spreadsheet->getActiveSheet();
        $data = $sheet->toArray(null, true, true, false);

        return $this->petakanBaris($data);
    }

    /**
     * Baca file Word (.docx) -> ambil tabel pertama, baris pertama = header.
     */
    protected function bacaWord($file): array
    {
        $phpWord = WordIOFactory::load($file->getRealPath(), 'Word2007');
        $data = [];

        foreach ($phpWord->getSections() as $section) {
            foreach ($section->getElements() as $element) {
                if (! $element instanceof \PhpOffice\PhpWord\Element\Table) {
                    continue;
                }
                foreach ($element->getRows() as $row) {
                    $baris = [];
                    foreach ($row->getCells() as $cell) {
                        $teks = '';
                        foreach ($cell->getElements() as $cellElement) {
                            if (method_exists($cellElement, 'getText')) {
                                $teks .= $cellElement->getText();
                            } elseif (method_exists($cellElement, 'getElements')) {
                                foreach ($cellElement->getElements() as $sub) {
                                    if (method_exists($sub, 'getText')) {
                                        $teks .= $sub->getText();
                                    }
                                }
                            }
                        }
                        $baris[] = trim($teks);
                    }
                    $data[] = $baris;
                }
                break 2; // hanya ambil tabel pertama
            }
        }

        return $this->petakanBaris($data);
    }

    /**
     * Baris pertama dianggap header. Cocokkan ke key standar berdasarkan
     * urutan kolom template (No, Tipe Soal, Pertanyaan, Pilihan A-E, Jawaban Benar, Kategori, Poin).
     */
    protected function petakanBaris(array $data): array
    {
        if (count($data) < 2) {
            return [];
        }

        array_shift($data); // buang baris header

        $keys = ['no', 'tipe_soal', 'pertanyaan', 'pilihan_a', 'pilihan_b', 'pilihan_c',
                 'pilihan_d', 'pilihan_e', 'jawaban_benar', 'kategori', 'poin'];

        return array_map(function ($row) use ($keys) {
            $row = array_pad($row, count($keys), '');
            return array_combine($keys, array_slice($row, 0, count($keys)));
        }, $data);
    }
}
