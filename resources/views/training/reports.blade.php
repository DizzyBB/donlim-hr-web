@extends(auth()->user()->can('manage-training-requests') ? 'training.layouts.admin' : 'training.layouts.employee')
@section('title', 'Laporan Pelatihan')

@section('content')
<div style="background:#fff;border-radius:12px;padding:20px"><h2 style="margin-top:0">Laporan Pelatihan</h2><div id="app"></div></div>
@endsection

@push('scripts')
@include('training.partials.helpers')
@include('training.partials.crud')
<script>
  const admin = @json(auth()->user()->can('manage-training-requests'));
  const ST = { draft: ['Draft', '#9ca3af'], submitted: ['Diserahkan', '#f59e0b'], reviewed: ['Sudah direview', '#22c55e'] };
  const editable = r => admin || r.status !== 'reviewed';   // terkunci untuk karyawan setelah direview

  trCrud({
    el: '#app', title: 'Laporan',
    list: '/api/training-reports', base: '/api/training-reports',
    canCreate: true, canEdit: editable, canDelete: editable,
    importApi: admin ? '/api/admin/training-reports/import' : null,
    templateApi: '/api/admin/training-reports/template',
    filters: [
      { name: 'q', label: 'Cari judul training…' },
      { name: 'status', label: 'Semua status', options: Object.entries(ST).map(([k, v]) => [k, v[0]]) },
    ],
    columns: [
      { label: 'Training', render: r => `${trEsc(r.training_record?.title ?? '-')}<br><small>${r.training_record?.start_date ?? ''}</small>` },
      { label: 'Penulis', render: r => trEsc(r.user?.name ?? '-') },
      { label: 'Status', render: r => trPill(...ST[r.status]) },
      { label: 'Rating', render: r => r.rating ? '★'.repeat(r.rating) : '-' },
      { label: 'Lampiran', render: r => r.attachments_count },
    ],
    fields: [
      { name: 'training_record_id', label: 'Training', type: 'select', required: true, createOnly: true,
        load: async () => (await trApi('/api/training-records?per_page=100')).data.map(x => [x.id, `${x.title} (${x.start_date})`]) },
      { name: 'summary', label: 'Rangkuman materi', type: 'textarea', required: true },
      { name: 'evaluation_result', label: 'Hasil evaluasi', type: 'textarea' },
      { name: 'evaluation_score', label: 'Nilai evaluasi (0-100)', type: 'number' },
      { name: 'feedback', label: 'Feedback untuk penyelenggara', type: 'textarea' },
      { name: 'rating', label: 'Rating (1-5)', type: 'number', min: 1, max: 5 },
      { name: 'application_plan', label: 'Rencana penerapan', type: 'textarea' },
      { name: 'status', label: 'Status', type: 'select', default: 'draft', options: [['draft', 'Draft'], ['submitted', 'Serahkan untuk review']] },
      { name: 'files[]', label: 'Lampiran laporan', type: 'file', multiple: true,
        accept: '.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.jpg,.jpeg,.png', help: 'Maks. 5 file, 10 MB per file.' },
    ],
    actions: admin ? {
      approve: { label: 'Setujui', cls: 'k', show: r => r.status === 'submitted',
        run: async r => { try { await trApi(`/api/admin/training-reports/${r.id}/review`, { method: 'PATCH', body: JSON.stringify({ status: 'reviewed' }) }); return true; } catch (e) { alert(e.message); } } },
      revise: { label: 'Revisi', cls: 'g', show: r => r.status === 'submitted',
        run: async r => { const note = prompt('Catatan revisi (wajib):'); if (!note) return;
          try { await trApi(`/api/admin/training-reports/${r.id}/review`, { method: 'PATCH', body: JSON.stringify({ status: 'draft', review_note: note }) }); return true; } catch (e) { alert(e.message); } } },
    } : {},
  });
</script>
@endpush
