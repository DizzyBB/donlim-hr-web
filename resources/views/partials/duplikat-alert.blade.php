{{-- Peringatan duplikat peserta karyawan baru (hanya info untuk admin, tidak memblokir). --}}
@if(!empty($info) && $info['duplikat'])
    <div class="alert alert-warning d-flex gap-2 align-items-start mb-3" role="alert">
        <i class="bi bi-exclamation-triangle-fill"></i>
        <div>
            <strong>Kemungkinan duplikat peserta.</strong> {{ $info['pesan'] }}
        </div>
    </div>
@endif
