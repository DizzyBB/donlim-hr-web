@extends('layouts.karyawan')

@section('title', 'Ajukan Training')

@section('content')
<div class="container-xl py-4" style="max-width: 720px;">

    <div class="mb-4">
        <h4 class="mb-1">Ajukan Training / Ruang Training</h4>
        <p class="text-muted mb-0">Pilih jenis pengajuan, lalu lengkapi detailnya.</p>
    </div>

    @if($errors->any())
        <div class="alert alert-danger">
            <strong>Periksa kembali form-nya:</strong>
            <ul class="mb-0 mt-1">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="panel p-4">
        <form action="{{ route('karyawan.training.store') }}" method="POST" id="formTraining">
            @csrf

            <div class="mb-3">
                <label class="form-label fw-semibold">Jenis Pengajuan</label>
                <div class="btn-group w-100" role="group">
                    <input type="radio" class="btn-check" name="kind" id="kindTraining" value="training"
                           {{ old('kind', 'training') == 'training' ? 'checked' : '' }}>
                    <label class="btn btn-outline-primary" for="kindTraining">Request Training</label>

                    <input type="radio" class="btn-check" name="kind" id="kindRoom" value="room"
                           {{ old('kind') == 'room' ? 'checked' : '' }}>
                    <label class="btn btn-outline-primary" for="kindRoom">Pesan Ruang Training</label>
                </div>
            </div>

            <div class="mb-3" id="blokTipe">
                <label class="form-label">Tipe Training</label>
                <div class="btn-group w-100" role="group">
                    <input type="radio" class="btn-check" name="type" id="typeInternal" value="internal"
                           {{ old('type', 'internal') == 'internal' ? 'checked' : '' }}>
                    <label class="btn btn-outline-secondary" for="typeInternal">Internal</label>

                    <input type="radio" class="btn-check" name="type" id="typeExternal" value="external"
                           {{ old('type') == 'external' ? 'checked' : '' }}>
                    <label class="btn btn-outline-secondary" for="typeExternal">Eksternal</label>
                </div>
                <div class="form-text">Training eksternal tidak wajib memakai ruangan internal.</div>
            </div>

            <div class="mb-3" id="blokReasonObjective">
                <div class="mb-3">
                    <label class="form-label" for="reason">Alasan Kebutuhan Training</label>
                    <textarea name="reason" id="reason" rows="2" class="form-control">{{ old('reason') }}</textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="objective">Tujuan / Kompetensi yang Diharapkan</label>
                    <textarea name="objective" id="objective" rows="2" class="form-control">{{ old('objective') }}</textarea>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label" for="title">Judul / Nama Kegiatan</label>
                <input type="text" name="title" id="title" class="form-control @error('title') is-invalid @enderror"
                       value="{{ old('title') }}" placeholder="Contoh: Workshop Laravel Advanced">
                @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="mb-3">
                <label class="form-label" for="description">Deskripsi <span class="text-muted fw-normal">(opsional)</span></label>
                <textarea name="description" id="description" rows="2" class="form-control">{{ old('description') }}</textarea>
            </div>

            <div class="mb-3" id="blokRoom">
                <label class="form-label" for="room_id">Ruangan</label>
                <select name="room_id" id="room_id" class="form-select @error('room_id') is-invalid @enderror">
                    <option value="">-- Pilih ruangan --</option>
                    @foreach($rooms as $room)
                        <option value="{{ $room->id }}" {{ old('room_id') == $room->id ? 'selected' : '' }}>
                            {{ $room->name }}{{ $room->capacity ? " (kapasitas {$room->capacity})" : '' }}
                        </option>
                    @endforeach
                </select>
                @error('room_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="row g-3 mb-4">
                <div class="col-sm-6">
                    <label class="form-label" for="start_time">Waktu Mulai</label>
                    <input type="datetime-local" name="start_time" id="start_time"
                           class="form-control @error('start_time') is-invalid @enderror" value="{{ old('start_time') }}">
                    @error('start_time')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-sm-6">
                    <label class="form-label" for="end_time">Waktu Selesai</label>
                    <input type="datetime-local" name="end_time" id="end_time"
                           class="form-control @error('end_time') is-invalid @enderror" value="{{ old('end_time') }}">
                    @error('end_time')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    <div class="form-text" id="hintDurasi"></div>
                </div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-navy">
                    <i class="bi bi-send-fill me-1"></i> Kirim Pengajuan
                </button>
                <a href="{{ route('karyawan.training.index') }}" class="btn btn-outline-secondary">Batal</a>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function(){
    const blokTipe = document.getElementById('blokTipe');
    const blokRoom = document.getElementById('blokRoom');
    const blokReasonObjective = document.getElementById('blokReasonObjective');
    const hintDurasi = document.getElementById('hintDurasi');

    function sinkronTampilan(){
        const isRoom = document.querySelector('input[name="kind"]:checked')?.value === 'room';
        const isInternal = document.querySelector('input[name="type"]:checked')?.value === 'internal';

        // Pesan Ruang: tidak perlu pilih tipe internal/eksternal & alasan/tujuan training.
        blokTipe.classList.toggle('d-none', isRoom);
        blokReasonObjective.classList.toggle('d-none', isRoom);

        // Ruangan wajib untuk Pesan Ruang ATAU Request Training bertipe internal.
        blokRoom.classList.toggle('d-none', !isRoom && !isInternal);

        hintDurasi.textContent = isRoom ? 'Durasi pemesanan ruangan maksimal 24 jam.' : '';
    }

    document.querySelectorAll('input[name="kind"], input[name="type"]').forEach(function(el){
        el.addEventListener('change', sinkronTampilan);
    });
    sinkronTampilan();
});
</script>
@endpush
