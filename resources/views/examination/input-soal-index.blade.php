@extends('layouts.app')

@section('title', 'Input Soal')

@push('styles')
<style>
  /* Sedikit custom CSS pelengkap Bootstrap */
  .opsi-row.benar{ background-color:#d1e7dd; border-color:#198754; }
  .opsi-row .huruf{
    width:28px; height:28px; border-radius:50%;
    background:#212529; color:#fff; display:flex; align-items:center;
    justify-content:center; font-size:.8rem; font-weight:600; flex:0 0 28px;
  }
  .opsi-row.benar .huruf{ background:#198754; }
  .btn-hapus-opsi:disabled{ opacity:.25; cursor:not-allowed; }
  .soal-card .opsi-list li.benar{ background:#d1e7dd; font-weight:600; }
  .soal-card .opsi-list{ list-style:none; padding-left:0; }
  .soal-card .opsi-list li{ padding:.35rem .6rem; border-radius:.35rem; margin-bottom:.25rem; }
</style>
@endpush

@section('content')
<div class="container-fluid py-4">

    <div class="d-flex justify-content-between align-items-center flex-wrap mb-3">
        <div>
            <h4 class="mb-1">Input Soal</h4>
            <p class="text-muted mb-0">Tambahkan soal Pilihan Ganda, Benar / Salah, atau Esai.</p>
        </div>
        <div class="d-flex gap-2">
            <span class="badge text-bg-light border">Total: <b id="statTotal">0</b></span>
            <span class="badge text-bg-primary-subtle text-primary-emphasis border">PG: <b id="statPG">0</b></span>
            <span class="badge text-bg-warning-subtle text-warning-emphasis border">Benar/Salah: <b id="statTF">0</b></span>
            <span class="badge text-bg-info-subtle text-info-emphasis border">Esai: <b id="statUR">0</b></span>
        </div>
    </div>

    {{-- Form --}}
    <div class="card mb-4">
        <div class="card-body">

            <div class="btn-group w-100 mb-3" role="group">
                <button type="button" class="btn btn-outline-primary tipe-btn active" data-tipe="pg">
                    Pilihan Ganda
                </button>
                <button type="button" class="btn btn-outline-primary tipe-btn" data-tipe="tf">
                    Benar / Salah
                </button>
                <button type="button" class="btn btn-outline-primary tipe-btn" data-tipe="ur">
                    Esai
                </button>
            </div>

            <h5 id="formTitle" class="mb-1">Tambah Soal — Pilihan Ganda</h5>
            <p class="text-muted small" id="formHint">
                Tulis pertanyaan, isi opsi jawaban, lalu tandai satu opsi sebagai jawaban benar.
            </p>

            <form id="formSoal" novalidate>
                <input type="hidden" id="editId" value="">
                <input type="hidden" id="tipeAktif" value="pg">

                <div class="mb-3">
                    <label class="form-label" for="pertanyaan">Pertanyaan</label>
                    <textarea class="form-control" id="pertanyaan" rows="3"
                              placeholder="Contoh: Apa ibu kota Indonesia?"></textarea>
                    <div class="invalid-feedback d-block d-none" id="errPertanyaan">Pertanyaan tidak boleh kosong.</div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-sm-6">
                        <label class="form-label" for="kategori">Kategori <span class="text-muted fw-normal">(opsional)</span></label>
                        <input type="text" class="form-control" id="kategori" placeholder="Contoh: Sejarah">
                    </div>
                    <div class="col-sm-6">
                        <label class="form-label" for="poin">Poin <span class="text-muted fw-normal">(bobot awal; dinormalisasi ke 100 saat dipakai di ujian)</span></label>
                        <input type="number" class="form-control" id="poin" value="10" min="1">
                    </div>
                </div>

                {{-- Blok Pilihan Ganda --}}
                <div id="blokPG" class="mb-2">
                    <label class="form-label">Opsi Jawaban</label>
                    <div id="daftarOpsi"></div>
                    <div class="invalid-feedback d-block d-none" id="errOpsi">Isi minimal 2 opsi dan pilih satu jawaban benar.</div>
                    <button type="button" class="btn btn-sm btn-outline-secondary mt-2" id="btnTambahOpsi">
                        <i class="bi bi-plus-lg"></i> Tambah opsi
                    </button>
                </div>

                {{-- Blok Benar / Salah --}}
                <div id="blokTF" class="mb-2 d-none">
                    <label class="form-label d-block">Kunci Jawaban</label>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="jawabanTF" id="tfBenar" value="benar">
                        <label class="form-check-label" for="tfBenar">Benar</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="jawabanTF" id="tfSalah" value="salah">
                        <label class="form-check-label" for="tfSalah">Salah</label>
                    </div>
                    <div class="invalid-feedback d-block d-none" id="errTF">Pilih kunci jawaban: Benar atau Salah.</div>
                </div>

                {{-- Blok Uraian --}}
                <div id="blokUR" class="mb-2 d-none">
                    <label class="form-label" for="kunciUraian">
                        Kunci Jawaban / Rubrik Penilaian Esai <span class="text-muted fw-normal">(opsional)</span>
                    </label>
                    <textarea class="form-control" id="kunciUraian" rows="3"
                              placeholder="Poin-poin jawaban yang diharapkan, untuk panduan penilaian manual"></textarea>
                </div>

                <div class="d-flex gap-2 mt-4">
                    <button type="submit" class="btn btn-success" id="btnSubmit">
                        <i class="bi bi-check-lg"></i> Simpan Soal
                    </button>
                    <button type="button" class="btn btn-outline-secondary d-none" id="btnBatal">Batal Edit</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Daftar Soal --}}
    <div class="d-flex justify-content-between align-items-center mb-2">
        <h5 class="mb-0">Daftar Soal</h5>
        <div class="btn-group btn-group-sm" role="group">
            <button type="button" class="btn btn-outline-dark filter-chip active" data-filter="semua">Semua</button>
            <button type="button" class="btn btn-outline-dark filter-chip" data-filter="pg">Pilihan Ganda</button>
            <button type="button" class="btn btn-outline-dark filter-chip" data-filter="tf">Benar / Salah</button>
            <button type="button" class="btn btn-outline-dark filter-chip" data-filter="ur">Esai</button>
        </div>
    </div>

    <div id="daftarSoal"></div>
    <div class="text-center text-muted border border-dashed rounded-3 py-5 d-none" id="emptyState">
        <i class="bi bi-journal-text fs-2 d-block mb-2"></i>
        Belum ada soal. Mulai tambahkan soal pertamamu di atas.
    </div>
</div>

{{-- Toast --}}
<div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index:1080">
    <div id="toast" class="toast align-items-center text-bg-dark border-0" role="alert">
        <div class="d-flex">
            <div class="toast-body" id="toastBody"></div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function(){
  // --- Guard: pastikan Bootstrap JS termuat sebelum lanjut ---
  if (typeof bootstrap === 'undefined') {
    console.error(
      '[Input Soal] Objek "bootstrap" masih tidak ditemukan meski sudah menunggu DOMContentLoaded. ' +
      'Cek lagi apakah "window.bootstrap = bootstrap;" sudah ditambahkan di resources/js/app.js ' +
      'dan npm run build/dev sudah dijalankan ulang.'
    );
  }

  const API = {
    data: @json(route('examination.input-soal.data')),
    store: @json(route('examination.input-soal.store')),
    update: function(id){ return "{{ url('examination/input-soal') }}/" + id; },
    destroy: function(id){ return "{{ url('examination/input-soal') }}/" + id; },
  };
  const CSRF_TOKEN = @json(csrf_token());

  let opsiCounter = 0;
  let editingId = null;
  let filterAktif = 'semua';
  let cacheSoal = [];

  const daftarOpsiEl = document.getElementById('daftarOpsi');
  const formEl = document.getElementById('formSoal');
  const daftarSoalEl = document.getElementById('daftarSoal');
  const emptyStateEl = document.getElementById('emptyState');
  const toastEl = document.getElementById('toast');
  const toastBodyEl = document.getElementById('toastBody');
  // Dibuat dengan try/catch supaya kalau bootstrap belum termuat,
  // sisa script (tombol tipe soal, tambah opsi, submit, dll) TETAP jalan.
  let toastInstance = null;
  try {
    toastInstance = new bootstrap.Toast(toastEl, { delay: 2200 });
  } catch (e) {
    console.error('[Input Soal] Gagal membuat instance Toast:', e);
  }
  const formTitleEl = document.getElementById('formTitle');
  const formHintEl = document.getElementById('formHint');
  const btnBatalEl = document.getElementById('btnBatal');
  const btnSubmitEl = document.getElementById('btnSubmit');
  const tipeAktifEl = document.getElementById('tipeAktif');

  const LABEL_TIPE = { pg: 'Pilihan Ganda', tf: 'Benar / Salah', ur: 'Esai' };
  const HINT_TIPE = {
    pg: 'Tulis pertanyaan, isi opsi jawaban (minimal 2), lalu tandai satu opsi sebagai jawaban benar.',
    tf: 'Tulis pernyataan, lalu pilih apakah kunci jawabannya Benar atau Salah.',
    ur: 'Tulis pertanyaan esai. Kunci jawaban bersifat opsional sebagai panduan penilaian HRD (esai dinilai manual).'
  };

  function hurufOpsi(i){ return String.fromCharCode(65 + i); }

  document.querySelectorAll('.tipe-btn').forEach(function(btn){
    btn.addEventListener('click', function(){
      if(editingId) return;
      gantiTipe(btn.dataset.tipe);
    });
  });

  function gantiTipe(tipe){
    tipeAktifEl.value = tipe;
    document.querySelectorAll('.tipe-btn').forEach(function(b){
      b.classList.toggle('active', b.dataset.tipe === tipe);
    });
    document.getElementById('blokPG').classList.toggle('d-none', tipe !== 'pg');
    document.getElementById('blokTF').classList.toggle('d-none', tipe !== 'tf');
    document.getElementById('blokUR').classList.toggle('d-none', tipe !== 'ur');
    formTitleEl.textContent = (editingId ? 'Edit Soal — ' : 'Tambah Soal — ') + LABEL_TIPE[tipe];
    formHintEl.textContent = HINT_TIPE[tipe];
    sembunyikanError();
  }

  function sembunyikanError(){
    document.getElementById('errPertanyaan').classList.add('d-none');
    document.getElementById('errOpsi').classList.add('d-none');
    document.getElementById('errTF').classList.add('d-none');
  }

  function buatBarisOpsi(teks){
    teks = teks || '';
    const id = 'opsi_' + (opsiCounter++);
    const row = document.createElement('div');
    row.className = 'opsi-row d-flex align-items-center gap-2 mb-2 p-2 border rounded';
    row.dataset.opsiId = id;
    row.innerHTML =
      '<span class="huruf">?</span>' +
      '<input type="text" class="form-control form-control-sm border-0 opsi-text" placeholder="Tulis opsi jawaban...">' +
      '<div class="form-check mb-0">' +
        '<input class="form-check-input radio-benar" type="checkbox" title="Tandai sebagai jawaban benar">' +
      '</div>' +
      '<button type="button" class="btn btn-sm btn-link text-danger btn-hapus-opsi p-0"><i class="bi bi-x-lg"></i></button>';
    row.querySelector('.opsi-text').value = teks;
    daftarOpsiEl.appendChild(row);

    row.querySelector('.btn-hapus-opsi').addEventListener('click', function(){
      if(daftarOpsiEl.querySelectorAll('.opsi-row').length <= 2){
        return; // minimal 2 opsi harus tetap ada
      }
      row.remove();
      perbaruiHurufOpsi();
    });
    row.querySelector('.radio-benar').addEventListener('change', function(e){
      if(e.target.checked){
        document.querySelectorAll('#daftarOpsi .radio-benar').forEach(function(cb){
          if(cb !== e.target) cb.checked = false;
        });
      }
      perbaruiHurufOpsi();
    });
    perbaruiHurufOpsi();
  }

  function perbaruiHurufOpsi(){
    const rows = daftarOpsiEl.querySelectorAll('.opsi-row');
    rows.forEach(function(row, i){
      row.querySelector('.huruf').textContent = hurufOpsi(i);
      row.classList.toggle('benar', row.querySelector('.radio-benar').checked);
      row.querySelector('.btn-hapus-opsi').disabled = rows.length <= 2;
    });
  }

  document.getElementById('btnTambahOpsi').addEventListener('click', function(){ buatBarisOpsi(''); });

  document.querySelectorAll('input[name="jawabanTF"]').forEach(function(r){
    r.addEventListener('change', function(){
      document.getElementById('errTF').classList.add('d-none');
    });
  });
  function setJawabanTF(nilai){
    document.getElementById('tfBenar').checked = (nilai === 'benar');
    document.getElementById('tfSalah').checked = (nilai === 'salah');
  }

  function resetForm(){
    daftarOpsiEl.innerHTML = '';
    document.getElementById('pertanyaan').value = '';
    document.getElementById('kategori').value = '';
    document.getElementById('poin').value = 10;
    document.getElementById('kunciUraian').value = '';
    document.getElementById('editId').value = '';
    setJawabanTF(null);
    editingId = null;
    btnSubmitEl.disabled = false;
    btnSubmitEl.innerHTML = '<i class="bi bi-check-lg"></i> Simpan Soal';
    btnBatalEl.classList.add('d-none');
    sembunyikanError();
    for(let i = 0; i < 4; i++) buatBarisOpsi('');
    gantiTipe(tipeAktifEl.value || 'pg');
  }

  function apiFetch(url, options){
    options = options || {};
    options.headers = Object.assign({ 'X-CSRF-TOKEN': CSRF_TOKEN, 'Accept': 'application/json' }, options.headers || {});
    return fetch(url, options).then(function(res){
      if(!res.ok){
        return res.json().catch(function(){ return {}; }).then(function(body){
          throw new Error(body.message || 'Terjadi kesalahan pada server');
        });
      }
      return res.json();
    });
  }

  function muatSoal(){
    return apiFetch(API.data).then(function(list){ cacheSoal = list; return list; });
  }

  function tampilkanToast(pesan){
    toastBodyEl.textContent = pesan;
    if (toastInstance) {
      toastInstance.show();
    } else {
      // Fallback kalau Bootstrap Toast tidak tersedia, supaya pesan tetap terlihat
      alert(pesan);
    }
  }

  function escapeHtml(str){
    const div = document.createElement('div');
    div.textContent = str == null ? '' : str;
    return div.innerHTML;
  }

  document.querySelectorAll('.filter-chip').forEach(function(chip){
    chip.addEventListener('click', function(){
      filterAktif = chip.dataset.filter;
      document.querySelectorAll('.filter-chip').forEach(function(c){ c.classList.toggle('active', c === chip); });
      render();
    });
  });

  function render(){
    const listSemua = cacheSoal;
    document.getElementById('statTotal').textContent = listSemua.length;
    document.getElementById('statPG').textContent = listSemua.filter(function(s){ return s.tipe === 'pg'; }).length;
    document.getElementById('statTF').textContent = listSemua.filter(function(s){ return s.tipe === 'tf'; }).length;
    document.getElementById('statUR').textContent = listSemua.filter(function(s){ return s.tipe === 'ur'; }).length;

    const list = filterAktif === 'semua' ? listSemua : listSemua.filter(function(s){ return s.tipe === filterAktif; });
    daftarSoalEl.innerHTML = '';

    if(list.length === 0){
      emptyStateEl.classList.remove('d-none');
      return;
    }
    emptyStateEl.classList.add('d-none');

    list.forEach(function(soal, idx){
      const card = document.createElement('div');
      card.className = 'card soal-card mb-2';

      let bodyHtml = '';
      if(soal.tipe === 'pg'){
        bodyHtml += '<ul class="opsi-list mb-2">';
        (soal.opsi || []).forEach(function(o, i){
          bodyHtml += '<li class="' + (i === soal.jawabanIndex ? 'benar' : '') + '">' +
            hurufOpsi(i) + '. ' + escapeHtml(o) + (i === soal.jawabanIndex ? ' <i class="bi bi-check-circle-fill text-success"></i>' : '') + '</li>';
        });
        bodyHtml += '</ul>';
      } else if(soal.tipe === 'tf'){
        bodyHtml += '<div class="small text-muted border rounded p-2 mb-2"><strong>Kunci jawaban:</strong> ' + (soal.jawabanTF === 'benar' ? 'Benar' : 'Salah') + '</div>';
      } else if(soal.tipe === 'ur' && soal.kunci){
        bodyHtml += '<div class="small text-muted border rounded p-2 mb-2"><strong>Kunci/rubrik:</strong> ' + escapeHtml(soal.kunci) + '</div>';
      }

      card.innerHTML =
        '<div class="card-body">' +
          '<div class="d-flex justify-content-between align-items-center mb-1">' +
            '<span class="text-muted small">Soal ' + (idx + 1) + '</span>' +
            '<span class="badge text-bg-light border">' + LABEL_TIPE[soal.tipe] + '</span>' +
          '</div>' +
          '<div class="fw-semibold mb-2">' + escapeHtml(soal.pertanyaan) + '</div>' +
          bodyHtml +
          '<div class="d-flex justify-content-between align-items-center border-top pt-2 small text-muted">' +
            '<span>' + (soal.kategori ? escapeHtml(soal.kategori) + ' • ' : '') + soal.poin + ' poin</span>' +
            '<span>' +
              '<button type="button" class="btn btn-sm btn-link edit" data-id="' + soal.id + '">Edit</button>' +
              '<button type="button" class="btn btn-sm btn-link text-danger hapus" data-id="' + soal.id + '">Hapus</button>' +
            '</span>' +
          '</div>' +
        '</div>';
      daftarSoalEl.appendChild(card);
    });

    daftarSoalEl.querySelectorAll('.edit').forEach(function(btn){
      btn.addEventListener('click', function(){ mulaiEdit(btn.dataset.id); });
    });
    daftarSoalEl.querySelectorAll('.hapus').forEach(function(btn){
      btn.addEventListener('click', function(){ hapusSoal(btn.dataset.id); });
    });
  }

  function mulaiEdit(id){
    const soal = cacheSoal.find(function(s){ return String(s.id) === String(id); });
    if(!soal) return;

    editingId = id;
    document.getElementById('editId').value = id;
    document.getElementById('pertanyaan').value = soal.pertanyaan;
    document.getElementById('kategori').value = soal.kategori || '';
    document.getElementById('poin').value = soal.poin;

    gantiTipe(soal.tipe);

    if(soal.tipe === 'pg'){
      daftarOpsiEl.innerHTML = '';
      (soal.opsi || []).forEach(function(teks, i){
        buatBarisOpsi(teks);
        if(i === soal.jawabanIndex){
          const rows = daftarOpsiEl.querySelectorAll('.opsi-row');
          rows[rows.length - 1].querySelector('.radio-benar').checked = true;
        }
      });
      perbaruiHurufOpsi();
    } else if(soal.tipe === 'tf'){
      setJawabanTF(soal.jawabanTF);
    } else if(soal.tipe === 'ur'){
      document.getElementById('kunciUraian').value = soal.kunci || '';
    }

    formTitleEl.textContent = 'Edit Soal — ' + LABEL_TIPE[soal.tipe];
    btnSubmitEl.innerHTML = '<i class="bi bi-check-lg"></i> Update Soal';
    btnBatalEl.classList.remove('d-none');
    window.scrollTo({ top: 0, behavior: 'smooth' });
  }

  function hapusSoal(id){
    if(!confirm('Hapus soal ini?')) return;
    apiFetch(API.destroy(id), { method: 'DELETE' })
      .then(function(){ return muatSoal(); })
      .then(function(){
        render();
        tampilkanToast('Soal dihapus');
        if(String(editingId) === String(id)) resetForm();
      })
      .catch(function(err){ tampilkanToast(err.message || 'Gagal menghapus soal'); });
  }

  btnBatalEl.addEventListener('click', resetForm);

  formEl.addEventListener('submit', function(e){
    e.preventDefault();

    const tipe = tipeAktifEl.value;
    const pertanyaan = document.getElementById('pertanyaan').value.trim();
    const kategori = document.getElementById('kategori').value.trim();
    const poin = parseInt(document.getElementById('poin').value, 10) || 10;

    let valid = true;
    if(pertanyaan === ''){
      document.getElementById('errPertanyaan').classList.remove('d-none');
      valid = false;
    } else {
      document.getElementById('errPertanyaan').classList.add('d-none');
    }

    let payload = { tipe: tipe, pertanyaan: pertanyaan, kategori: kategori, poin: poin };

    if(tipe === 'pg'){
      const rows = Array.from(daftarOpsiEl.querySelectorAll('.opsi-row'));
      const opsi = [];
      let jawabanIndex = -1;
      rows.forEach(function(row){
        const teks = row.querySelector('.opsi-text').value.trim();
        if(teks !== ''){
          opsi.push(teks);
          if(row.querySelector('.radio-benar').checked) jawabanIndex = opsi.length - 1;
        }
      });
      if(opsi.length < 2 || jawabanIndex === -1){
        document.getElementById('errOpsi').classList.remove('d-none');
        valid = false;
      } else {
        document.getElementById('errOpsi').classList.add('d-none');
      }
      payload.opsi = opsi;
      payload.jawabanIndex = jawabanIndex;
    } else if(tipe === 'tf'){
      const dipilih = document.querySelector('input[name="jawabanTF"]:checked');
      if(!dipilih){
        document.getElementById('errTF').classList.remove('d-none');
        valid = false;
      } else {
        document.getElementById('errTF').classList.add('d-none');
        payload.jawabanTF = dipilih.value;
      }
    } else if(tipe === 'ur'){
      payload.kunci = document.getElementById('kunciUraian').value.trim();
    }

    if(!valid) return;

    btnSubmitEl.disabled = true;

    const request = editingId
      ? apiFetch(API.update(editingId), { method: 'PUT', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload) })
      : apiFetch(API.store, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload) });

    request
      .then(function(){ return muatSoal(); })
      .then(function(){
        tampilkanToast(editingId ? 'Soal berhasil diperbarui' : 'Soal berhasil disimpan');
        resetForm();
        render();
      })
      .catch(function(err){
        btnSubmitEl.disabled = false;
        tampilkanToast(err.message || 'Gagal menyimpan soal');
      });
  });

  resetForm();
  muatSoal().then(function(){
    render();
    // Deep-link dari Bank Soal: ?edit=ID -> otomatis buka form edit soal tsb.
    const editIdDariUrl = new URLSearchParams(window.location.search).get('edit');
    if(editIdDariUrl){
      mulaiEdit(editIdDariUrl);
    }
  });
});
</script>
@endpush
