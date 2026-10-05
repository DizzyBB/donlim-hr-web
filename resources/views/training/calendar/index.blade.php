@extends('layouts.app')

@section('title', 'Kalender Ruang Training')

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.css" rel="stylesheet">
<style>
    #calendar{ background:#fff; border:1px solid #dee2e6; border-radius:.5rem; padding:1rem; }
    .legend-dot{ width:.7rem; height:.7rem; border-radius:50%; display:inline-block; margin-right:.35rem; }
</style>
@endpush

@section('content')
<div class="container-fluid py-4">

    <div class="d-flex justify-content-between align-items-center flex-wrap mb-4">
        <div>
            <h4 class="mb-1">Kalender Ruang Training</h4>
            <p class="text-muted mb-0">
                <span class="legend-dot" style="background:#22c55e;"></span>Disetujui
                <span class="legend-dot ms-3" style="background:#f59e0b;"></span>Menunggu
            </p>
        </div>
        <div class="d-flex gap-2">
            <select id="filterRoom" class="form-select">
                <option value="">Semua Ruangan</option>
                @foreach($rooms as $room)
                    <option value="{{ $room->id }}">{{ $room->name }}</option>
                @endforeach
            </select>
            <div class="form-check form-switch d-flex align-items-center">
                <input class="form-check-input me-2" type="checkbox" role="switch" id="includePending" checked>
                <label class="form-check-label" for="includePending">Tampilkan pending</label>
            </div>
        </div>
    </div>

    <div id="calendar"></div>
</div>

<div class="modal fade" id="modalDetailEvent" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="detailTitle"></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="detailBody"></div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function(){
    const EVENTS_URL = @json(route('training.calendar.events'));
    const filterRoom = document.getElementById('filterRoom');
    const includePending = document.getElementById('includePending');
    const modal = (function(){
        try {
            return new bootstrap.Modal(document.getElementById('modalDetailEvent'));
        } catch (e) {
            console.error('[Kalender] Bootstrap Modal gagal dibuat, detail event saat diklik tidak akan tampil:', e);
            return null;
        }
    })();

    const calendar = new FullCalendar.Calendar(document.getElementById('calendar'), {
        initialView: 'dayGridMonth',
        headerToolbar: { left: 'prev,next today', center: 'title', right: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek' },
        height: 'auto',
        events: function(info, success, failure){
            const qs = new URLSearchParams({
                start: info.startStr,
                end: info.endStr,
                include_pending: includePending.checked ? '1' : '0',
            });
            if (filterRoom.value) qs.set('room_id', filterRoom.value);

            fetch(EVENTS_URL + '?' + qs.toString(), { headers: { Accept: 'application/json' } })
                .then(function(r){ return r.json(); })
                .then(success)
                .catch(failure);
        },
        eventClick: function(info){
            const p = info.event.extendedProps;
            document.getElementById('detailTitle').textContent = info.event.title;
            document.getElementById('detailBody').innerHTML =
                '<p class="mb-1"><strong>Pemohon:</strong> ' + (p.requester || '-') + '</p>' +
                '<p class="mb-1"><strong>Ruangan:</strong> ' + (p.room || '-') + '</p>' +
                '<p class="mb-1"><strong>Jenis:</strong> ' + (p.kind === 'training' ? 'Request Training' : 'Pesan Ruang') +
                    ' (' + (p.type === 'internal' ? 'Internal' : 'Eksternal') + ')</p>' +
                '<p class="mb-1"><strong>Status:</strong> ' + p.status + '</p>' +
                (p.description ? '<p class="mb-0 small text-muted">' + p.description + '</p>' : '');
            if (modal) modal.show();
        },
    });

    calendar.render();

    filterRoom.addEventListener('change', function(){ calendar.refetchEvents(); });
    includePending.addEventListener('change', function(){ calendar.refetchEvents(); });
});
</script>
@endpush
