<?php

namespace Tests\Feature;

use App\Models\Karyawan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Tests\Concerns\MembuatUjian;
use Tests\TestCase;

class ImportKaryawanTest extends TestCase
{
    use MembuatUjian, RefreshDatabase;

    private function csv(string $isi): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('karyawan.csv', $isi);
    }

    public function test_preview_menandai_duplikat_lalu_konfirmasi_hanya_menyimpan_yang_baru(): void
    {
        $this->karyawan('1001', 'Budi Lama', 'HRD'); // sudah ada
        $admin = $this->admin();

        $isi = "NIK,Nama,Departemen\n1001,Budi Baru,IT\n2001,Sari,Finance\n2001,Sari Dobel,Finance\n,Tanpa Nik,IT\n3001,sari,IT\n";

        $this->actingAs($admin, 'web')->post('/karyawan/import/preview', ['file' => $this->csv($isi)])
            ->assertRedirect('/karyawan/import');

        $rows = collect(session('karyawan_import.rows'))->keyBy('baris');
        $this->assertSame('duplikat_db', $rows[2]['status']);
        $this->assertSame('baru', $rows[3]['status']);
        $this->assertSame('duplikat_file', $rows[4]['status']);
        $this->assertSame('tidak_valid', $rows[5]['status']);
        $this->assertSame('baru', $rows[6]['status']);
        $this->assertNotNull($rows[6]['peringatan'], 'nama sama (tanpa beda huruf) harus diberi peringatan');

        // Belum ada yang tersimpan sebelum konfirmasi.
        $this->assertDatabaseMissing('karyawans', ['nik' => '2001']);

        $this->actingAs($admin, 'web')->post('/karyawan/import/konfirmasi')->assertRedirect('/karyawan');

        $this->assertSame('Budi Lama', Karyawan::where('nik', '1001')->value('nama'), 'NIK ganda tidak boleh menimpa');
        $baru = Karyawan::where('nik', '2001')->firstOrFail();
        $this->assertTrue(Hash::check('2001', $baru->password), 'password awal = NIK');
        $this->assertTrue($baru->must_change_password);
        $this->assertSame('karyawan', $baru->role);
        $this->assertDatabaseHas('karyawans', ['nik' => '3001']);
        $this->assertDatabaseHas('departemens', ['nama' => 'Finance']);
        $this->assertSame(3, Karyawan::count()); // 1001 + 2001 + 3001
    }

    public function test_file_melebihi_batas_baris_ditolak(): void
    {
        $isi = "NIK,Nama,Departemen\n";
        for ($i = 1; $i <= 501; $i++) {
            $isi .= "N{$i},Nama {$i},IT\n";
        }

        $this->actingAs($this->admin(), 'web')->post('/karyawan/import/preview', ['file' => $this->csv($isi)])
            ->assertSessionHas('error');

        $this->assertNull(session('karyawan_import'));
    }

    public function test_karyawan_hasil_import_wajib_ganti_password(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin, 'web')->post('/karyawan/import/preview', ['file' => $this->csv("NIK,Nama,Departemen\n5001,Tono,IT\n")]);
        $this->actingAs($admin, 'web')->post('/karyawan/import/konfirmasi');
        auth('web')->logout();

        $k = Karyawan::where('nik', '5001')->firstOrFail();

        $this->actingAs($k, 'karyawan')->get('/karyawan/ujian')->assertRedirect('/karyawan/password');

        $this->actingAs($k, 'karyawan')->put('/karyawan/password', [
            'password_lama' => '5001', 'password' => '5001', 'password_confirmation' => '5001',
        ])->assertSessionHasErrors('password');

        $this->actingAs($k, 'karyawan')->put('/karyawan/password', [
            'password_lama' => '5001', 'password' => 'PasswordBaru9', 'password_confirmation' => 'PasswordBaru9',
        ])->assertRedirect('/karyawan/ujian');

        $this->assertFalse($k->refresh()->must_change_password);
        $this->actingAs($k, 'karyawan')->get('/karyawan/ujian')->assertOk();
    }
}
