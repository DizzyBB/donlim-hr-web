@extends(auth()->user()->can('manage-training-requests') ? 'training.layouts.admin' : 'training.layouts.employee')
@section('title', 'Sertifikat')

@section('content')
<div style="background:#fff;border-radius:12px;padding:20px">
  <h2 style="margin-top:0">Sertifikat Pelatihan</h2>
  <div id="sum" style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:14px"></div>
  <div id="app"></div>
</div>
@endsection

@push('scripts')
@include('training.partials.helpers')
@include('training.partials.crud')
<script>
  const admin = @json(auth()->user()->can('manage-training-requests'));
  const COLOR = { active: '#22c55e', expiring_soon: '#f59e0b', expired: '#ef4444' };
  let crud;

  async function loadSummary() {
    const s = await trApi('/api/certificates/summary');
    const chip = (label, val, status, color) =>
      `<button data-s="${status}" style="border:0;cursor:pointer;background:${color};color:#fff;border-radius:10px;padding:10px 16px;font-weight:700">${label}: ${val}</button>`;
    document.getElementById('sum').innerHTML =
      chip('Semua', s.total, '', '#6c63ff') + chip('Aktif', s.active, 'active', COLOR.active) +
      chip(`Segera Expired (≤${s.expiring_within_days} hr)`, s.expiring_soon, 'expiring_soon', COLOR.expiring_soon) +
      chip('Expired', s.expired, 'expired', COLOR.expired);
  }
  document.getElementById('sum').onclick = e => { const b = e.target.closest('button'); if (b) crud.setFilter('status', b.dataset.s); };

  crud = trCrud({
    el: '#app', title: 'Sertifikat',
    list: '/api/certificates', base: '/api/certificates',
    canCreate: true, canEdit: true, canDelete: true,
    importApi: admin ? '/api/admin/certificates/import' : null,
    templateApi: '/api/admin/certificates/template',
    onChange: loadSummary,
    filters: [
      { name: 'q', label: 'Cari nama / nomor / penerbit…' },
      { name: 'status', label: 'Semua status', options: [['active', 'Aktif'], ['expiring_soon', 'Segera Expired'], ['expired', 'Expired']] },
    ],
    columns: [
      ...(admin ? [{ label: 'Pemilik', render: r => `${trEsc(r.user?.name ?? '-')}<br><small>${trEsc(r.nik)}</small>` }] : []),
      { label: 'Sertifikat', render: r => `${trEsc(r.name)}<br><small>${trEsc(r.certificate_number ?? '')}</small>` },
      { label: 'Penerbit', render: r => trEsc(r.issuer ?? '-') },
      { label: 'Terbit', key: 'issued_date' },
      { label: 'Kedaluwarsa', render: r => !r.expiry_date ? 'Selamanya'
          : `${r.expiry_date}<br><small>${r.days_left < 0 ? 'lewat ' + (-r.days_left) + ' hari' : r.days_left + ' hari lagi'}</small>` },
      { label: 'Status', render: r => trPill(r.validity_label, COLOR[r.validity_status]) },
      { label: 'File', render: r => { const a = r.attachments?.[0];
          return a ? `<a href="${a.download_url}?inline=1" target="_blank">Lihat</a> · <a href="${a.download_url}">Unduh</a>` : '-'; } },
    ],
    fields: [
      ...(admin ? [{ name: 'nik', label: 'NIK pemilik', createOnly: true, help: 'Kosongkan untuk sertifikat milik sendiri.' }] : []),
      { name: 'name', label: 'Nama sertifikat', required: true },
      { name: 'certificate_number', label: 'Nomor sertifikat' },
      { name: 'issuer', label: 'Lembaga penerbit' },
      { name: 'issued_date', label: 'Tanggal diterbitkan', type: 'date', required: true },
      { name: 'expiry_date', label: 'Tanggal kedaluwarsa', type: 'date', help: 'Kosongkan bila berlaku selamanya.' },
      { name: 'training_record_id', label: 'Terkait training (opsional)', type: 'select',
        load: async () => (await trApi('/api/training-records?per_page=100')).data.map(x => [x.id, `${x.title} (${x.start_date})`]) },
      { name: 'notes', label: 'Catatan', type: 'textarea' },
      { name: 'file', label: 'File sertifikat', type: 'file', accept: '.pdf,.jpg,.jpeg,.png', help: 'PDF/JPG/PNG, maks. 5 MB. Upload baru menggantikan file lama.' },
    ],
  });
  loadSummary();
</script>
@endpush
