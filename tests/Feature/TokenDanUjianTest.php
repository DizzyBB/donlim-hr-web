<?php

namespace Tests\Feature;

use App\Models\Departemen;
use App\Models\HasilUjian;
use App\Models\PesertaBaru;
use App\Models\UjianToken;
use App\Services\TokenService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\MembuatUjian;
use Tests\TestCase;

class TokenDanUjianTest extends TestCase
{
    use MembuatUjian, RefreshDatabase;

    private function tokenGlobal($ujian): UjianToken
    {
        return app(TokenService::class)->buat($ujian, UjianToken::TIPE_GLOBAL_BARU);
    }

    /** Jalankan alur karyawan baru sampai ujian dimulai. */
    private function mulaiSebagaiPesertaBaru($token, string $nama = 'Rina Wulandari'): HasilUjian
    {
        $this->post('/ujian-baru/token', ['token' => $token->kode_tampil])->assertRedirect('/ujian-baru/identitas');
        $this->get('/ujian-baru/identitas')->assertOk()->assertSee('Token valid');

        $this->post('/ujian-baru/mulai', [
            'nama' => $nama,
            'departemen_id' => Departemen::where('nama', 'IT')->value('id'),
        ])->assertRedirect();

        return HasilUjian::latest('id')->firstOrFail();
    }

    public function test_alur_karyawan_baru_token_form_lalu_ujian_dan_token_sekali_pakai(): void
    {
        [$ujian] = $this->ujian();
        $token = $this->tokenGlobal($ujian);

        $hasil = $this->mulaiSebagaiPesertaBaru($token);

        $this->assertSame('berlangsung', $hasil->status);
        $this->assertNull($hasil->karyawan_id);
        $this->assertSame('Rina Wulandari', $hasil->pesertaBaru->nama);
        $this->assertSame('IT', $hasil->pesertaBaru->departemen_nama);
        $this->assertSame(UjianToken::STATUS_TERPAKAI, $token->refresh()->status);
        $this->assertSame($hasil->id, $token->hasil_ujian_id);
        $this->assertSame(3, $hasil->jawaban()->count());
        $this->assertEqualsWithDelta(100, $hasil->jawaban()->sum('bobot'), 0.001);

        // Halaman ujian bisa dibuka oleh browser yang sama...
        $this->get("/ujian-baru/pengerjaan/{$hasil->id}")->assertOk()->assertSee('Rina Wulandari');

        // ...tetapi token tidak bisa dipakai lagi (sekali pakai).
        $this->flushSession();
        $this->post('/ujian-baru/token', ['token' => $token->token])->assertSessionHasErrors('token');
    }

    public function test_peserta_lain_tidak_bisa_membuka_pengerjaan_orang_lain(): void
    {
        [$ujian] = $this->ujian();
        $hasil = $this->mulaiSebagaiPesertaBaru($this->tokenGlobal($ujian));

        $this->flushSession(); // browser lain
        $this->get("/ujian-baru/pengerjaan/{$hasil->id}")->assertForbidden();
    }

    public function test_token_salah_atau_ujian_nonaktif_ditolak(): void
    {
        [$ujian] = $this->ujian();
        $token = $this->tokenGlobal($ujian);

        $this->post('/ujian-baru/token', ['token' => 'SALAH123'])->assertSessionHasErrors('token');

        $ujian->update(['status' => 'nonaktif']);
        $this->post('/ujian-baru/token', ['token' => $token->token])->assertSessionHasErrors('token');
        $this->assertSame(UjianToken::STATUS_AKTIF, $token->refresh()->status);
    }

    public function test_klaim_token_atomik_hanya_satu_yang_berhasil(): void
    {
        [$ujian] = $this->ujian();
        $token = $this->tokenGlobal($ujian);
        $svc = app(TokenService::class);

        $this->assertTrue($svc->klaim($token));
        $this->assertFalse($svc->klaim($token), 'klaim kedua harus gagal');
    }

    public function test_token_kedaluwarsa_oleh_admin_tidak_bisa_dipakai(): void
    {
        [$ujian] = $this->ujian();
        $token = $this->tokenGlobal($ujian);
        app(TokenService::class)->kedaluwarsakan($token);

        $this->post('/ujian-baru/token', ['token' => $token->token])->assertSessionHasErrors('token');
    }

    public function test_token_karyawan_terikat_ke_akun_dan_tidak_bisa_dipindahtangankan(): void
    {
        $budi = $this->karyawan('1001', 'Budi');
        $sari = $this->karyawan('1002', 'Sari');
        [$ujian] = $this->ujian('perorangan', $budi);
        $token = app(TokenService::class)->buat($ujian, UjianToken::TIPE_KARYAWAN, ['karyawan_id' => $budi->id]);

        // Akun lain ditolak dan token tetap aktif.
        $this->actingAs($sari, 'karyawan')->post('/karyawan/ujian/token', ['token' => $token->token])
            ->assertSessionHasErrors('token');
        $this->assertSame(UjianToken::STATUS_AKTIF, $token->refresh()->status);
        $this->assertSame(0, HasilUjian::count());

        // Peserta karyawan baru juga tidak bisa memakai token akun.
        $this->post('/ujian-baru/token', ['token' => $token->token])->assertSessionHasErrors('token');

        // Pemilik berhasil, lalu tidak bisa dipakai kedua kali.
        $this->actingAs($budi, 'karyawan')->post('/karyawan/ujian/token', ['token' => $token->kode_tampil])
            ->assertRedirect();
        $hasil = HasilUjian::firstOrFail();
        $this->assertSame($budi->id, $hasil->karyawan_id);
        $this->assertSame(UjianToken::STATUS_TERPAKAI, $token->refresh()->status);

        $this->actingAs($budi, 'karyawan')->post('/karyawan/ujian/token', ['token' => $token->token])
            ->assertSessionHasErrors('token');
        $this->assertSame(1, HasilUjian::count());
    }

    public function test_ujian_bisa_diakses_kapan_saja_selama_aktif_tanpa_batas_jam(): void
    {
        $budi = $this->karyawan('1001', 'Budi');
        [$ujian] = $this->ujian('perorangan', $budi, ['jam_mulai' => '00:00:00', 'jam_selesai' => '00:01:00']);
        $token = app(TokenService::class)->buat($ujian, UjianToken::TIPE_KARYAWAN, ['karyawan_id' => $budi->id]);

        $this->travelTo(now()->setTime(15, 30)); // jauh di luar jam lama
        $this->actingAs($budi, 'karyawan')->post('/karyawan/ujian/token', ['token' => $token->token])->assertRedirect();
        $this->assertSame(1, HasilUjian::count());
    }

    public function test_token_pengganti_melanjutkan_pengerjaan_yang_sama_tanpa_reset_waktu(): void
    {
        [$ujian] = $this->ujian();
        $hasil = $this->mulaiSebagaiPesertaBaru($this->tokenGlobal($ujian));
        $mulai = $hasil->waktu_mulai->copy();

        $this->actingAs($this->admin(), 'web')
            ->post("/examination/penilaian/{$hasil->id}/token-pengganti")
            ->assertSessionHas('token_pengganti');
        auth('web')->logout();

        $pengganti = UjianToken::where('tipe', 'pengganti')->firstOrFail();
        $this->assertSame($hasil->id, $pengganti->hasil_ujian_id);

        $this->flushSession(); // browser baru
        $this->post('/ujian-baru/token', ['token' => $pengganti->token])
            ->assertRedirect("/ujian-baru/pengerjaan/{$hasil->id}");

        $this->get("/ujian-baru/pengerjaan/{$hasil->id}")->assertOk();
        $this->assertEquals($mulai, $hasil->refresh()->waktu_mulai);
        $this->assertSame(1, HasilUjian::count());
        $this->assertSame(1, PesertaBaru::count());

        // Token pengganti juga sekali pakai.
        $this->flushSession();
        $this->post('/ujian-baru/token', ['token' => $pengganti->token])->assertSessionHasErrors('token');
    }

    public function test_token_pengganti_setelah_waktu_habis_ditolak_dan_ujian_dikumpulkan(): void
    {
        [$ujian] = $this->ujian();
        $hasil = $this->mulaiSebagaiPesertaBaru($this->tokenGlobal($ujian));

        $pengganti = app(TokenService::class)->buat($ujian, UjianToken::TIPE_PENGGANTI, ['hasil_ujian_id' => $hasil->id]);

        $this->travel(31)->minutes();
        $this->flushSession();
        $this->post('/ujian-baru/token', ['token' => $pengganti->token])->assertSessionHasErrors('token');

        $this->assertNotSame('berlangsung', $hasil->refresh()->status);
    }

    public function test_alur_peserta_tidak_bisa_mengulang_setelah_waktu_habis(): void
    {
        $budi = $this->karyawan('1001', 'Budi');
        [$ujian] = $this->ujian('perorangan', $budi);
        $svc = app(TokenService::class);
        $t1 = $svc->buat($ujian, UjianToken::TIPE_KARYAWAN, ['karyawan_id' => $budi->id]);

        $this->actingAs($budi, 'karyawan')->post('/karyawan/ujian/token', ['token' => $t1->token]);
        $hasil = HasilUjian::firstOrFail();

        $this->travel(31)->minutes();
        $this->actingAs($budi, 'karyawan')->get("/karyawan/pengerjaan/{$hasil->id}")
            ->assertRedirect("/karyawan/pengerjaan/{$hasil->id}/hasil");
        $this->assertNotSame('berlangsung', $hasil->refresh()->status);
        $this->assertSame(UjianToken::STATUS_KEDALUWARSA, $t1->refresh()->status);

        // Token baru pun tidak bisa memulai ujian yang sama lagi (satu kali per karyawan).
        $t2 = $svc->buat($ujian, UjianToken::TIPE_KARYAWAN, ['karyawan_id' => $budi->id]);
        $this->actingAs($budi, 'karyawan')->post('/karyawan/ujian/token', ['token' => $t2->token])
            ->assertRedirect('/karyawan/ujian');
        $this->assertSame(1, HasilUjian::count());
    }

    public function test_deteksi_duplikat_nama_peserta_baru_dan_karyawan_aktif(): void
    {
        $this->karyawan('1001', 'Rina  Wulandari', 'HRD');
        [$ujian] = $this->ujian();
        $this->mulaiSebagaiPesertaBaru($this->tokenGlobal($ujian), 'Rina Wulandari');
        $this->flushSession();
        $kedua = $this->mulaiSebagaiPesertaBaru($this->tokenGlobal($ujian), 'rina wulandari');

        $info = app(\App\Services\DuplikatService::class)->untukPesertaBaru($kedua->pesertaBaru);

        $this->assertTrue($info['duplikat']);
        $this->assertSame(1, $info['jumlah_peserta_sama']);
        $this->assertSame('1001', $info['karyawan'][0]['nik']);

        // Hanya peringatan: kedua peserta tetap bisa mengerjakan, dan admin melihat alert.
        $this->assertSame(2, HasilUjian::count());
        $this->actingAs($this->admin(), 'web')->get('/examination/riwayat-ujian')
            ->assertOk()->assertSee('kemungkinan duplikat');
    }

    public function test_input_nama_tidak_valid_ditolak(): void
    {
        [$ujian] = $this->ujian();
        $token = $this->tokenGlobal($ujian);
        $this->post('/ujian-baru/token', ['token' => $token->token]);

        $this->post('/ujian-baru/mulai', ['nama' => '<script>x</script>', 'departemen_id' => Departemen::first()->id])
            ->assertSessionHasErrors('nama');
        $this->assertSame(UjianToken::STATUS_AKTIF, $token->refresh()->status);
        $this->assertSame(0, PesertaBaru::count());
    }
}
