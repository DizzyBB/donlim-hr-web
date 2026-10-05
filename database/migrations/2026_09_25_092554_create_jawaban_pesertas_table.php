<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jawaban_pesertas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hasil_ujian_id')->constrained('hasil_ujians')->cascadeOnDelete();
            $table->foreignId('soal_id')->constrained('soals')->cascadeOnDelete();

            $table->string('tipe'); // pg | bs | ur (disalin dari soal saat pengerjaan, untuk histori)

            $table->unsignedTinyInteger('jawaban_index')->nullable(); // khusus pg: index opsi yang dipilih peserta
            $table->text('jawaban_teks')->nullable();                // khusus bs / ur: teks jawaban peserta

            $table->boolean('benar')->nullable();  // khusus pg: true/false otomatis. bs/ur: null (dinilai via skor)
            $table->decimal('skor', 6, 2)->nullable(); // poin yang didapat untuk soal ini
            $table->text('catatan_penilai')->nullable(); // opsional, feedback untuk uraian/jawaban singkat

            $table->timestamps();

            $table->unique(['hasil_ujian_id', 'soal_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jawaban_pesertas');
    }
};
