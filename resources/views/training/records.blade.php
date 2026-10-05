@extends(auth()->user()->can('manage-training-requests') ? 'training.layouts.admin' : 'training.layouts.employee')
@section('title', 'Record Training')

@section('content')
<div style="background:#fff;border-radius:12px;padding:20px"><h2 style="margin-top:0">Record Training</h2><div id="app"></div></div>
@endsection

@push('scripts')
@include('training.partials.helpers')
@include('training.partials.crud')
<script>
  const admin = @json(auth()->user()->can('manage-training-requests'));
  const ATT = { present: 'Hadir', partial: 'Sebagian', absent: 'Tidak hadir' };
  const RES = { passed: 'Lulus', failed: 'Tidak lulus', in_progress: 'Berlangsung', not_assessed: 'Belum dinilai' };
  const RES_COLOR = { passed: '#22c55e', failed: '#ef4444', in_progress: '#3b82f6', not_assessed: '#9ca3af' };
  const opts = o => Object.entries(o);

  trCrud({
    el: '#app', title: 'Record Training',
    list: '/api/training-records', base: '/api/admin/training-records',
    canCreate: admin, canEdit: admin, canDelete: admin,
    importApi: admin ? '/api/admin/training-records/import' : null,
    templateApi: '/api/admin/training-records/template',
    filters: [
      { name: 'q', label: 'Cari judul / NIK…' },
      { name: 'attendance_status', label: 'Semua kehadiran', options: opts(ATT) },
      { name: 'result_status', label: 'Semua hasil', options: opts(RES) },
    ],
    columns: [
      { label: 'Peserta', render: r => `${trEsc(r.user?.name ?? '-')}<br><small>${trEsc(r.nik)}</small>` },
      { label: 'Training', render: r => `${trEsc(r.title)}<br><small>${trEsc(r.provider ?? '')} · ${r.type === 'external' ? 'Eksternal' : 'Internal'}</small>` },
      { label: 'Tanggal', render: r => `${r.start_date}<br><small>s/d ${r.end_date}</small>` },
      { label: 'Kehadiran', render: r => ATT[r.attendance_status] },
      { label: 'Hasil', render: r => trPill(RES[r.result_status], RES_COLOR[r.result_status]) + (r.score ? `<br><small>Nilai ${r.score}</small>` : '') },
      { label: 'Laporan', render: r => r.report ? trEsc(r.report.status) : '<small>belum ada</small>' },
      { label: 'Sertifikat', render: r => r.certificates_count },
    ],
    fields: [
      { name: 'nik', label: 'NIK peserta', required: true, createOnly: true },
      { name: 'title', label: 'Judul training', required: true },
      { name: 'provider', label: 'Penyelenggara' },
      { name: 'type', label: 'Jenis', type: 'select', default: 'internal', options: [['internal', 'Internal'], ['external', 'Eksternal']] },
      { name: 'location', label: 'Lokasi' },
      { name: 'start_date', label: 'Tanggal mulai', type: 'date', required: true },
      { name: 'end_date', label: 'Tanggal selesai', type: 'date' },
      { name: 'attendance_status', label: 'Kehadiran', type: 'select', default: 'present', options: opts(ATT) },
      { name: 'result_status', label: 'Hasil', type: 'select', default: 'not_assessed', options: opts(RES) },
      { name: 'score', label: 'Nilai (0-100)', type: 'number' },
      { name: 'notes', label: 'Catatan', type: 'textarea' },
    ],
  });
</script>
@endpush
