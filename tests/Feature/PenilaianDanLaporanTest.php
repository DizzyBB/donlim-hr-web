<?php

namespace Tests\Feature;

use App\Models\HasilUjian;
use App\Models\UjianToken;
use App\Services\PengerjaanService;
use App\Services\PenilaianService;
use App\Services\TokenService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\MembuatUjian;
use Tests\TestCase;

class PenilaianDanLaporanTest extends TestCase
{
    use MembuatUjian, RefreshDatabase;

    /** Peserta karyawan aktif mengerjakan: PG benar, B/S dijawab sesuai $tfIndex, esai diisi. */
    private function kerjakan(array $soal, $ujian, $karyawan, int $tfIndex = 0, bool $esai = true): HasilUjian
    {
        $hasil = app(PengerjaanService::class)->mulai($ujian, $karyawan->id, null);
        foreach ($hasil->jawaban as $j) {
            match ($j->tipe) {
                'pg' => $j->update(['jawaban_index' => 1]),
                'tf' => $j->update(['jawaban_index' => $tfIndex]),
                'ur' => $j->update(['jawaban_teks' => $esai ? 'Ajukan lewat HRIS' : null]),
            };
        }

        return $hasil->refresh();
    }

    public function test_pg_dan_tf_otomatis_esai_pending_lalu_final_dan_lulus(): void
    {
        $k = $this->karyawan();
        [$ujian, $soal] = $this->ujian('perorangan', $k);
        $hasil = $this->kerjakan($soal, $ujian, $k);

        app(PenilaianService::class)->finalisasiPengerjaan($hasil);
        $hasil->refresh();

        // PG (25) + B/S (25) benar = 50 sementara; esai belum dinilai => Pending, belum ada lulus/tidak.
        $this->assertSame('belum_dinilai', $hasil->status);
        $this->assertEquals(50.0, (float) $hasil->nilai_pilihan_ganda);
        $this->assertNull($hasil->lulus);
        $this->assertSame('Pending', $hasil->kelulusan_label);

        // Skor sementara tampil langsung ke peserta.
        $this->actingAs($k, 'karyawan')->get("/karyawan/pengerjaan/{$hasil->id}/hasil")
            ->assertOk()->assertSee('Skor sementara')->assertSee('Pending');

        // HRD memberi esai 20 dari 50 => total 70 => sama dengan passing grade => Lulus.
        $esai = $hasil->jawaban()->where('tipe', 'ur')->first();
        $this->actingAs($this->admin(), 'web')->put("/examination/penilaian/{$hasil->id}", [
            'skor' => [$esai->id => 20],
        ])->assertRedirect();

        $hasil->refresh();
        $this->assertSame('selesai_dinilai', $hasil->status);
        $this->assertEquals(70.0, (float) $hasil->nilai_total);
        $this->assertTrue($hasil->lulus);
        $this->assertEquals(70.0, (float) $hasil->passing_grade);
        $this->assertNull($hasil->dikoreksi_pada, 'penilaian esai pertama kali bukan koreksi');
    }

    public function test_tidak_lulus_dan_skor_esai_tidak_boleh_melebihi_bobot(): void
    {
        $k = $this->karyawan();
        [$ujian, $soal] = $this->ujian('perorangan', $k);
        $hasil = $this->kerjakan($soal, $ujian, $k, tfIndex: 1); // B/S salah
        app(PenilaianService::class)->finalisasiPengerjaan($hasil);
        $esai = $hasil->jawaban()->where('tipe', 'ur')->first();
        $admin = $this->admin();

        $this->actingAs($admin, 'web')->put("/examination/penilaian/{$hasil->id}", ['skor' => [$esai->id => 51]])
            ->assertSessionHasErrors("skor.{$esai->id}");

        $this->actingAs($admin, 'web')->put("/examination/penilaian/{$hasil->id}", ['skor' => [$esai->id => 30]]);
        $hasil->refresh();
        $this->assertEquals(55.0, (float) $hasil->nilai_total); // 25 + 0 + 30
        $this->assertFalse($hasil->lulus);
        $this->assertSame('Tidak Lulus', $hasil->kelulusan_label);
    }

    public function test_tanpa_esai_langsung_final_dan_esai_kosong_bernilai_nol(): void
    {
        $k = $this->karyawan();
        [$ujian, $soal] = $this->ujian('perorangan', $k);
        $hasil = $this->kerjakan($soal, $ujian, $k, esai: false);

        app(PenilaianService::class)->finalisasiPengerjaan($hasil);
        $hasil->refresh();

        $this->assertSame('selesai_dinilai', $hasil->status);
        $this->assertEquals(50.0, (float) $hasil->nilai_total);
        $this->assertFalse($hasil->lulus);
    }

    public function test_koreksi_hasil_final_wajib_catatan_dan_tercatat(): void
    {
        $k = $this->karyawan();
        [$ujian, $soal] = $this->ujian('perorangan', $k);
        $hasil = $this->kerjakan($soal, $ujian, $k);
        app(PenilaianService::class)->finalisasiPengerjaan($hasil);
        $esai = $hasil->jawaban()->where('tipe', 'ur')->first();
        $admin = $this->admin();
        $this->actingAs($admin, 'web')->put("/examination/penilaian/{$hasil->id}", ['skor' => [$esai->id => 10]]); // 60 => tidak lulus
        $this->assertFalse($hasil->refresh()->lulus);

        // Mengubah hasil yang sudah final tanpa catatan ditolak.
        $this->actingAs($admin, 'web')->put("/examination/penilaian/{$hasil->id}", ['skor' => [$esai->id => 30]])
            ->assertSessionHasErrors('catatan_koreksi');
        $this->assertEquals(60.0, (float) $hasil->refresh()->nilai_total);

        // Dengan catatan: tersimpan beserta siapa & kapan.
        $this->actingAs($admin, 'web')->put("/examination/penilaian/{$hasil->id}", [
            'skor' => [$esai->id => 30], 'catatan_koreksi' => 'Salah input skor esai',
        ])->assertRedirect();
        $hasil->refresh();
        $this->assertEquals(80.0, (float) $hasil->nilai_total);
        $this->assertTrue($hasil->lulus);
        $this->assertSame($admin->id, $hasil->dikoreksi_oleh);
        $this->assertSame('Salah input skor esai', $hasil->catatan_koreksi);

        // Koreksi passing grade khusus hasil ini (mis. 85) membuat status berubah.
        $this->actingAs($admin, 'web')->put("/examination/penilaian/{$hasil->id}", [
            'skor' => [$esai->id => 30], 'passing_grade' => 85, 'catatan_koreksi' => 'Standar posisi senior',
        ]);
        $this->assertFalse($hasil->refresh()->lulus);
        $this->assertEquals(85.0, (float) $hasil->passing_grade);
    }

    public function test_hitung_ulang_setelah_kunci_jawaban_diperbaiki(): void
    {
        $k = $this->karyawan();
        [$ujian, $soal] = $this->ujian('perorangan', $k);
        $hasil = $this->kerjakan($soal, $ujian, $k, esai: false);
        app(PenilaianService::class)->finalisasiPengerjaan($hasil);
        $this->assertEquals(50.0, (float) $hasil->refresh()->nilai_total);

        // Kunci B/S ternyata keliru: seharusnya "Benar" (1). Jawaban peserta = 0 jadi salah.
        $soal['tf']->update(['jawaban_tf' => true]);
        $soal['pg']->update(['jawaban_index' => 0]); // dan kunci PG berubah; jawaban peserta 1 jadi salah

        $this->actingAs($this->admin(), 'web')->post("/examination/penilaian/{$hasil->id}/hitung-ulang")->assertRedirect();
        $this->assertEquals(0.0, (float) $hasil->refresh()->nilai_total);
        $this->assertNotNull($hasil->dikoreksi_pada);
    }

    public function test_bobot_disalin_ke_jawaban_perubahan_bobot_tidak_mengubah_nilai_lama(): void
    {
        $k = $this->karyawan();
        [$ujian, $soal] = $this->ujian('perorangan', $k);
        $hasil = $this->kerjakan($soal, $ujian, $k, esai: false);
        app(PenilaianService::class)->finalisasiPengerjaan($hasil);

        $ujian->soal()->updateExistingPivot($soal['pg']->id, ['bobot' => 90]);
        $this->actingAs($this->admin(), 'web')->post("/examination/penilaian/{$hasil->id}/hitung-ulang");

        $this->assertEquals(50.0, (float) $hasil->refresh()->nilai_total);
    }

    public function test_admin_membuat_ujian_bobot_dinormalisasi_ke_100(): void
    {
        [, $soal] = $this->ujian();
        $this->actingAs($this->admin(), 'web')->post('/examination/ujian', [
            'jenis' => 'karyawan_baru', 'nama_ujian' => 'Ujian Baru', 'status' => 'aktif',
            'durasi_menit' => 45, 'passing_grade' => 75,
            'soal_ids' => [$soal['pg']->id, $soal['tf']->id, $soal['ur']->id],
            'bobot' => [$soal['pg']->id => 1, $soal['tf']->id => 1, $soal['ur']->id => 1],
        ])->assertRedirect('/examination/ujian');

        $ujian = \App\Models\Ujian::where('nama_ujian', 'Ujian Baru')->firstOrFail();
        $this->assertEqualsWithDelta(100, $ujian->soal()->sum('soal_ujian.bobot'), 0.001);
        $this->assertSame('aktif', $ujian->status);
        $this->assertEquals(75.0, (float) $ujian->passing_grade);
    }

    public function test_bobot_nol_atau_passing_grade_di_luar_rentang_ditolak(): void
    {
        [, $soal] = $this->ujian();
        $payload = [
            'jenis' => 'karyawan_baru', 'nama_ujian' => 'X', 'status' => 'aktif', 'durasi_menit' => 30,
            'passing_grade' => 70, 'soal_ids' => [$soal['pg']->id], 'bobot' => [$soal['pg']->id => 0],
        ];
        $admin = $this->admin();
        $this->actingAs($admin, 'web')->post('/examination/ujian', $payload)->assertSessionHasErrors('bobot.' . $soal['pg']->id);
        $this->actingAs($admin, 'web')->post('/examination/ujian', ['passing_grade' => 101, 'bobot' => [$soal['pg']->id => 1]] + $payload)
            ->assertSessionHasErrors('passing_grade');
    }

    public function test_simpan_jawaban_benar_salah_dan_validasi(): void
    {
        $k = $this->karyawan();
        [$ujian] = $this->ujian('perorangan', $k);
        $hasil = app(PengerjaanService::class)->mulai($ujian, $k->id, null);
        $tf = $hasil->jawaban()->where('tipe', 'tf')->first();

        $this->actingAs($k, 'karyawan')->postJson("/karyawan/pengerjaan/{$hasil->id}/jawab", ['jawaban_id' => $tf->id, 'jawaban_index' => 1])->assertOk();
        $this->assertSame(1, $tf->refresh()->jawaban_index);
        $this->actingAs($k, 'karyawan')->postJson("/karyawan/pengerjaan/{$hasil->id}/jawab", ['jawaban_id' => $tf->id, 'jawaban_index' => 5])->assertStatus(422);
    }

    public function test_export_csv_dan_xlsx_dengan_filter_dan_netralisasi_rumus(): void
    {
        $k = $this->karyawan('1001', 'Budi');
        [$ujian, $soal] = $this->ujian('perorangan', $k);
        $hasil = $this->kerjakan($soal, $ujian, $k, esai: false);
        app(PenilaianService::class)->finalisasiPengerjaan($hasil);

        // Peserta baru dengan nama berbahaya (rumus Excel).
        [$ujian2] = $this->ujian('karyawan_baru', null, ['nama_ujian' => 'Ujian Baru']);
        $token = app(TokenService::class)->buat($ujian2, UjianToken::TIPE_GLOBAL_BARU);
        $pb = \App\Models\PesertaBaru::create(['ujian_id' => $ujian2->id, 'nama' => '=HYPERLINK("http://x")', 'tanggal_tes' => now()]);
        $h2 = app(PengerjaanService::class)->mulai($ujian2, null, $pb->id);
        app(PenilaianService::class)->finalisasiPengerjaan($h2);

        $admin = $this->admin();
        $csv = $this->actingAs($admin, 'web')->get('/examination/riwayat-ujian/export?format=csv')->assertOk()->streamedContent();
        $this->assertStringContainsString('Nilai Total (%)', $csv);
        $this->assertStringContainsString('Budi', $csv);
        $this->assertStringContainsString("'=HYPERLINK", $csv, 'rumus harus dinetralkan');
        $this->assertStringNotContainsString(";=HYPERLINK", $csv);

        // Filter: hanya karyawan baru.
        $csv2 = $this->actingAs($admin, 'web')->get('/examination/riwayat-ujian/export?format=csv&jenis=karyawan_baru')->streamedContent();
        $this->assertStringNotContainsString('Budi', $csv2);

        $xlsx = $this->actingAs($admin, 'web')->get('/examination/riwayat-ujian/export?format=xlsx')->assertOk();
        $this->assertStringContainsString('spreadsheetml', $xlsx->headers->get('content-type'));
        $this->assertStringStartsWith('PK', $xlsx->streamedContent()); // file zip/xlsx valid
    }

    public function test_filter_status_tetap_berlaku_saat_mencari(): void
    {
        $k = $this->karyawan('1001', 'Budi');
        [$ujian, $soal] = $this->ujian('perorangan', $k);
        $hasil = $this->kerjakan($soal, $ujian, $k, esai: false);
        app(PenilaianService::class)->finalisasiPengerjaan($hasil); // selesai_dinilai

        $this->actingAs($this->admin(), 'web')->get('/examination/riwayat-ujian?search=Budi&status=belum_dinilai')
            ->assertOk()->assertSee('Belum ada riwayat ujian');
        $this->actingAs($this->admin(), 'web')->get('/examination/riwayat-ujian?search=Budi&status=selesai_dinilai')
            ->assertOk()->assertDontSee('Belum ada riwayat ujian')->assertSee('Orientasi');
    }

    public function test_pdf_hasil_ujian_dan_akses_peserta(): void
    {
        $k = $this->karyawan();
        $lain = $this->karyawan('1002', 'Sari');
        [$ujian, $soal] = $this->ujian('perorangan', $k);
        $hasil = $this->kerjakan($soal, $ujian, $k, esai: false);
        app(PenilaianService::class)->finalisasiPengerjaan($hasil);

        $this->actingAs($k, 'karyawan')->get("/karyawan/pengerjaan/{$hasil->id}/pdf")->assertOk()->assertSee('Hasil Ujian')->assertSee('TIDAK LULUS');
        $this->actingAs($lain, 'karyawan')->get("/karyawan/pengerjaan/{$hasil->id}/pdf")->assertForbidden();
        $this->actingAs($this->admin(), 'web')->get("/examination/riwayat-ujian/{$hasil->id}/pdf")->assertOk()->assertSee('Rincian Jawaban');
    }

    public function test_halaman_admin_utama_dapat_dirender(): void
    {
        $k = $this->karyawan();
        [$ujian, $soal] = $this->ujian('perorangan', $k);
        $hasil = $this->kerjakan($soal, $ujian, $k);
        app(PenilaianService::class)->finalisasiPengerjaan($hasil);
        $admin = $this->admin();

        foreach (['/examination/ujian', "/examination/ujian/{$ujian->id}/edit", '/examination/ujian/create', "/examination/ujian/{$ujian->id}/token",
            '/examination/penilaian', "/examination/penilaian/{$hasil->id}", '/examination/riwayat-ujian', "/examination/riwayat-ujian/{$hasil->id}",
            '/examination/question-bank', '/examination/input-soal', '/karyawan/import', '/departemen', '/examination/question-bank/template/excel'] as $url) {
            $this->actingAs($admin, 'web')->get($url)->assertOk();
        }
    }
}
