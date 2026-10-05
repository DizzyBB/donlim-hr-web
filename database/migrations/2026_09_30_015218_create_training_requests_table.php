<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('training_requests', function (Blueprint $table) {
            $table->id();

            // Pemohon selalu karyawan (guard 'karyawan'), BUKAN users (admin ada di tabel terpisah).
            $table->foreignId('karyawan_id')->constrained('karyawans')->cascadeOnDelete();

            // 'training' = Request Training, 'room' = Pesan Ruang Training (form ke-2)
            $table->enum('kind', ['training', 'room'])->default('training');
            $table->enum('type', ['internal', 'external'])->default('internal');

            $table->string('title');                // Nama/Judul training atau nama kegiatan
            $table->text('description')->nullable();
            $table->text('reason')->nullable();      // Alasan kebutuhan training
            $table->text('objective')->nullable();    // Tujuan/kompetensi yang diharapkan

            // nullable: training eksternal tidak wajib memakai ruangan
            $table->foreignId('room_id')->nullable()->constrained('rooms')->nullOnDelete();
            // diisi kalau pemohon pilih "Lainnya" di form Pesan Ruang (ruangan di luar data `rooms`,
            // jadi TIDAK ikut dicek bentrok jadwal — hanya catatan teks bebas)
            $table->string('room_name_custom')->nullable();
            $table->dateTime('start_time');
            $table->dateTime('end_time');

            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('admin_note')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete(); // admin penilai
            $table->timestamp('reviewed_at')->nullable();

            $table->timestamps();

            // mempercepat cek bentrok & query kalender
            $table->index(['room_id', 'status', 'start_time', 'end_time'], 'tr_room_status_time_idx');
            $table->index(['status', 'start_time']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('training_requests');
    }
};
