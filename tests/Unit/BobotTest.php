<?php

namespace Tests\Unit;

use App\Support\Bobot;
use App\Support\Nama;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class BobotTest extends TestCase
{
    public function test_total_selalu_tepat_100(): void
    {
        foreach ([[1, 1, 1], [1, 2, 3, 4, 5, 6, 7], [10, 10, 10], [3.3, 3.3, 3.4], [1]] as $mentah) {
            $hasil = Bobot::normalisasi($mentah);
            $this->assertEqualsWithDelta(100.0, array_sum($hasil), 0.0001, json_encode($mentah));
        }
    }

    public function test_rasio_dipertahankan_dan_kunci_sama(): void
    {
        $hasil = Bobot::normalisasi([5 => 1, 9 => 1, 12 => 2]);
        $this->assertSame([5, 9, 12], array_keys($hasil));
        $this->assertEquals([25.0, 25.0, 50.0], array_values($hasil));
    }

    public function test_sisa_pembulatan_ke_soal_terakhir(): void
    {
        $hasil = Bobot::normalisasi([1, 1, 1]);
        $this->assertEquals([33.33, 33.33, 33.34], array_values($hasil));
    }

    public function test_bobot_nol_atau_negatif_ditolak(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Bobot::normalisasi([1, 0]);
    }

    public function test_normalisasi_nama_abaikan_huruf_besar_dan_spasi(): void
    {
        $this->assertSame(Nama::normalisasi('  Budi   Santoso '), Nama::normalisasi('budi santoso'));
        $this->assertSame('budisantoso', Nama::normalisasi("BUDI\tSantoso"));
        $this->assertNotSame(Nama::normalisasi('Budi Santosa'), Nama::normalisasi('Budi Santoso'));
    }
}
