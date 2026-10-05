@extends('layouts.app')

@section('title', 'Bank Soal')

@section('content')
<div class="container-fluid py-4">

    <div class="d-flex justify-content-between align-items-center flex-wrap mb-4">
        <div>
            <h4 class="mb-1">Bank Soal</h4>
            <p class="text-muted mb-0">Seluruh soal pilihan ganda, jawaban singkat, dan uraian.</p>
        </div>
        <div class="d-flex gap-2 mt-2 mt-md-0">
            <a href="{{ route('examination.input-soal.index') }}" class="btn btn-primary">
                <i class="bi bi-plus-lg me-1"></i> Tambah Soal
            </a>
            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalImportSoal">
                <i class="bi bi-upload me-1"></i> Import Soal
            </button>
        </div>
    </div>

    <div id="alertArea"></div>

    {{-- Filter --}}
    <div class="card mb-3">
        <div class="card-body">
            <form action="{{ route('examination.question-bank.index') }}" method="GET" class="row g-2 align-items-center">
                <div class="col-md-5">
                    <input type="text" name="search" value="{{ request('search') }}" class="form-control"
                           placeholder="Cari pertanyaan...">
                </div>
                <div class="col-md-3">
                    <select name="tipe" class="form-select">
                        <option value="">Semua Tipe Soal</option>
                        <option value="pg" @selected(request('tipe') == 'pg')>Pilihan Ganda</option>
                        <option value="bs" @selected(request('tipe') == 'bs')>Jawaban Singkat</option>
                        <option value="ur" @selected(request('tipe') == 'ur')>Uraian</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-outline-secondary">
                        <i class="lni lni-search-alt"></i>
                    </button>
                    @if(request('search') || request('tipe'))
                
                    <a href="{{ route('examination.question-bank.index') }}" class="btn btn-outline-danger"><i class="lni lni-cross-circle"></i></a>
                @endif
                </div>                
            </form>
        </div>
    </div>

    {{-- Tabel Soal --}}
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th style="width:50px;">No</th>
                            <th>Pertanyaan</th>
                            <th style="width:130px;">Tipe Soal</th>
                            <th style="width:130px;">Kategori</th>
                            <th style="width:80px;">Poin</th>
                            <th style="width:120px;">Dibuat</th>
                            <th style="width:110px;" class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($soal as $index => $item)
                            <tr>
                                <td>{{ $soal->firstItem() + $index }}</td>
                                <td>{{ Str::limit($item->pertanyaan, 80) }}</td>
                                <td>
                                    @switch($item->tipe)
                                        @case('pg')
                                            <span class="badge bg-primary-subtle text-primary">Pilihan Ganda</span>
                                            @break
                                        @case('bs')
                                            <span class="badge bg-warning-subtle text-warning">Jawaban Singkat</span>
                                            @break
                                        @case('ur')
                                            <span class="badge bg-info-subtle text-info">Uraian</span>
                                            @break
                                    @endswitch
                                </td>
                                <td>{{ $item->kategori ?? '-' }}</td>
                                <td>{{ $item->poin }}</td>
                                <td>{{ $item->created_at?->format('d M Y') }}</td>
                                <td class="text-center">
                                    <a href="{{ route('examination.input-soal.index') }}?edit={{ $item->id }}"
                                       class="btn btn-sm btn-outline-secondary p-2" title="Edit">
                                        <i class="lni lni-pencil-alt"></i>
                                    </a>
                                    <button type="button" class="btn btn-sm btn-outline-danger btn-hapus-soal p-2"
                                            data-id="{{ $item->id }}" title="Hapus">
                                        <i class="lni lni-trash-can"></i>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">
                                    Belum ada soal. Tambah manual atau import dari file.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $soal->links() }}
        </div>
    </div>
</div>

{{-- Modal Import Soal --}}
<div class="modal fade" id="modalImportSoal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form id="formImport" action="{{ route('examination.question-bank.import') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Import Soal</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">

                    <div class="mb-3">
                        <label class="form-label">Format File</label>
                        <div class="d-flex gap-3">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="format" id="formatExcel" value="excel" checked>
                                <label class="form-check-label" for="formatExcel">
                                    <i class="bi bi-file-earmark-excel text-success me-1"></i> Excel (.xlsx)
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="format" id="formatWord" value="word">
                                <label class="form-check-label" for="formatWord">
                                    <i class="bi bi-file-earmark-word text-primary me-1"></i> Word (.docx)
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="fileImport" class="form-label">Pilih File</label>
                        <input type="file" name="file" id="fileImport" class="form-control" accept=".xlsx,.xls,.docx" required>
                        <div class="form-text">Maks. 5 MB. Gunakan template resmi agar format terbaca sistem.</div>
                    </div>

                    <div class="alert alert-light border d-flex align-items-center justify-content-between mb-0">
                        <span><i class="bi bi-download me-1"></i> Belum punya template?</span>
                        <div class="d-flex gap-2">
                            <a href="{{ route('examination.question-bank.template', ['type' => 'excel']) }}"
                               class="btn btn-sm btn-outline-success">Template Excel</a>
                            <a href="{{ route('examination.question-bank.template', ['type' => 'word']) }}"
                               class="btn btn-sm btn-outline-primary">Template Word</a>
                        </div>
                    </div>

                    <div id="importFeedback" class="mt-3"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success" id="btnImportSubmit">
                        <i class="bi bi-upload me-1"></i> Import Sekarang
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function(){
  const formImport = document.getElementById('formImport');
  const btnImportSubmit = document.getElementById('btnImportSubmit');
  const importFeedback = document.getElementById('importFeedback');
  const alertArea = document.getElementById('alertArea');
  const CSRF_TOKEN = @json(csrf_token());

  formImport.addEventListener('submit', function(e){
    e.preventDefault();
    importFeedback.innerHTML = '';
    btnImportSubmit.disabled = true;
    btnImportSubmit.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Mengimport...';

    fetch(formImport.action, {
      method: 'POST',
      headers: { 'X-CSRF-TOKEN': CSRF_TOKEN, 'Accept': 'application/json' },
      body: new FormData(formImport),
    })
      .then(function(res){
        return res.json().then(function(body){ return { ok: res.ok, body: body }; });
      })
      .then(function({ ok, body }){
        btnImportSubmit.disabled = false;
        btnImportSubmit.innerHTML = '<i class="bi bi-upload me-1"></i> Import Sekarang';

        if(!ok){
          importFeedback.innerHTML =
            '<div class="alert alert-danger mb-0">' + (body.message || 'Import gagal.') + '</div>';
          return;
        }

        let errHtml = '';
        if(body.errors && body.errors.length){
          errHtml = '<ul class="small mb-0 mt-2">' +
            body.errors.slice(0, 5).map(function(e){ return '<li>' + e + '</li>'; }).join('') +
            '</ul>';
        }
        importFeedback.innerHTML =
          '<div class="alert alert-success mb-0">' + body.message + errHtml + '</div>';

        alertArea.innerHTML =
          '<div class="alert alert-success alert-dismissible fade show" role="alert">' +
            body.message +
            '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>' +
          '</div>';

        setTimeout(function(){ window.location.reload(); }, 1200);
      })
      .catch(function(){
        btnImportSubmit.disabled = false;
        btnImportSubmit.innerHTML = '<i class="bi bi-upload me-1"></i> Import Sekarang';
        importFeedback.innerHTML = '<div class="alert alert-danger mb-0">Terjadi kesalahan jaringan.</div>';
      });
  });

  document.querySelectorAll('.btn-hapus-soal').forEach(function(btn){
    btn.addEventListener('click', function(){
      if(!confirm('Hapus soal ini?')) return;
      const id = btn.dataset.id;
      fetch("{{ url('examination/question-bank') }}/" + id, {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': CSRF_TOKEN, 'Accept': 'application/json' },
      })
        .then(function(res){ return res.json(); })
        .then(function(){ window.location.reload(); })
        .catch(function(){ alert('Gagal menghapus soal'); });
    });
  });
})();
</script>
@endpush
