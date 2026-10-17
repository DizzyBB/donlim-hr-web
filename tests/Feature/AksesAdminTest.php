<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\MembuatUjian;
use Tests\TestCase;

class AksesAdminTest extends TestCase
{
    use MembuatUjian, RefreshDatabase;

    public function test_halaman_admin_butuh_login(): void
    {
        foreach (['/dashboard', '/karyawan', '/karyawan/import', '/departemen', '/examination/ujian',
            '/examination/penilaian', '/examination/riwayat-ujian', '/examination/question-bank'] as $url) {
            $this->get($url)->assertRedirect('/login');
        }
    }

    public function test_karyawan_aktif_tidak_bisa_membuka_halaman_admin(): void
    {
        $k = $this->karyawan();

        $this->actingAs($k, 'karyawan')->get('/karyawan')->assertRedirect('/login');
    }

    public function test_admin_membuka_manajemen_karyawan_tidak_tertutup_redirect_portal(): void
    {
        $this->karyawan('1001', 'Budi');

        $this->actingAs($this->admin(), 'web')->get('/karyawan')->assertOk()->assertSee('Data Karyawan');
    }

    public function test_pencarian_berdasarkan_nik_dan_nama(): void
    {
        $this->karyawan('EMP-0042', 'Sari Dewi');
        $this->karyawan('EMP-0099', 'Andi Pratama');
        $admin = $this->admin();

        $this->actingAs($admin, 'web')->get('/karyawan?search=0042')
            ->assertOk()->assertSee('Sari Dewi')->assertDontSee('Andi Pratama');

        $this->actingAs($admin, 'web')->get('/karyawan?search=Pratama')
            ->assertOk()->assertSee('Andi Pratama')->assertDontSee('Sari Dewi');
    }

    public function test_join_date_tersimpan_dan_departemen_masuk_master(): void
    {
        $this->actingAs($this->admin(), 'web')->post('/karyawan', [
            'nama' => 'Rina', 'nik' => '777', 'departemen' => 'Finance',
            'join_date' => '2026-01-15', 'role' => 'karyawan',
            'password' => 'password123', 'password_confirmation' => 'password123',
        ])->assertRedirect();

        $this->assertDatabaseHas('karyawans', ['nik' => '777', 'join_date' => '2026-01-15']);
        $this->assertDatabaseHas('departemens', ['nama' => 'Finance']);
    }
}
