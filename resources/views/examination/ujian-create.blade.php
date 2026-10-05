@extends('layouts.app')

@section('title', isset($ujian) ? 'Edit Ujian' : 'Buat Ujian')

@push('styles')
<style>
  .karyawan-dropdown{
    max-height: 240px; overflow-y: auto; z-index: 1055;
    display: none;
  }
  .karyawan-dropdown .item{ cursor: pointer; }
  .karyawan-dropdown .item:hover{ background: #f1f3f5; }
  .soal-row td{ vertical-align: middle; }
  .soal-row .pertanyaan-cell{ max-width: 420px; }
  #selectedCountBadge{ font-size: .95rem; }
  .switch-label{ cursor: pointer; }
</style>
@endpush

@section('content')
<div class="container-fluid py-4">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1">{{ isset($ujian) ? 'Edit Ujian' : 'Buat Ujian' }}</h4>
            <p class="text-muted mb-0">Konfigurasikan ujian perorangan atau ujian karyawan baru.</p>
        </div>
        <a href="{{ route('examination.ujian.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Kembali
        </a>
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

    <form action="{{ isset($ujian) ? route('examination.ujian.update', $ujian) : route('examination.ujian.store') }}"
          method="POST" id="formUjian">
        @csrf
        @if(isset($ujian))
            @method('PUT')
        @endif

        {{-- Jenis Ujian --}}
        <div class="card mb-3">
            <div class="card-body">
                <label class="form-label fw-semibold">Jenis Ujian</label>
                <div class="btn-group w-100" role="group">
                    <input type="radio" class="btn-check" name="jenis" id="jenisPerorangan" value="perorangan"
                           {{ old('jenis', $ujian->jenis ?? 'perorangan') == 'perorangan' ? 'checked' : '' }}>
                    <label class="btn btn-outline-primary" for="jenisPerorangan">Ujian Perorangan</label>

                    <input type="radio" class="btn-check" name="jenis" id="jenisKaryawanBaru" value="karyawan_baru"
                           {{ old('jenis', $ujian->jenis ?? '') == 'karyawan_baru' ? 'checked' : '' }}>
                    <label class="btn btn-outline-primary" for="jenisKaryawanBaru">Ujian Karyawan Baru</label>
                </div>
            </div>
        </div>

        {{-- Informasi Ujian --}}
        <div class="card mb-3">
            <div class="card-body">
                <h6 class="fw-semibold mb-3">Informasi Ujian</h6>

                <div class="mb-3">
                    <label class="form-label" for="namaUjian">Nama Ujian</label>
                    <input type="text" name="nama_ujian" id="namaUjian" class="form-control @error('nama_ujian') is-invalid @enderror"
                           value="{{ old('nama_ujian', $ujian->nama_ujian ?? '') }}" placeholder="Contoh: Ujian Kompetensi Dasar Q3 2026">
                    @error('nama_ujian')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="mb-0" id="blokKaryawan">
                    <label class="form-label" for="karyawanSearchInput">Karyawan</label>
                    <div class="position-relative">
                        <input type="text" id="karyawanSearchInput" class="form-control @error('karyawan_id') is-invalid @enderror"
                               placeholder="Cari nama atau NIK karyawan..." autocomplete="off">
                        <input type="hidden" name="karyawan_id" id="karyawanId" value="{{ old('karyawan_id', $ujian->karyawan_id ?? '') }}">
                        <div id="karyawanDropdown" class="list-group karyawan-dropdown position-absolute w-100 shadow-sm"></div>
                        @error('karyawan_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-text">Ketik minimal 2 huruf untuk mencari karyawan.</div>
                </div>
            </div>
        </div>

        {{-- Waktu Ujian --}}
        <div class="card mb-3">
            <div class="card-body">
                <h6 class="fw-semibold mb-3">Masa Berlaku &amp; Durasi</h6>
                <div class="row g-3">
                    <div class="col-sm-4">
                        <label class="form-label" for="jamMulai">Jam Mulai</label>
                        <input type="time" name="jam_mulai" id="jamMulai" class="form-control @error('jam_mulai') is-invalid @enderror"
                               value="{{ old('jam_mulai', isset($ujian) ? substr($ujian->jam_mulai, 0, 5) : '') }}">
                        @error('jam_mulai')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-sm-4">
                        <label class="form-label" for="jamSelesai">Jam Selesai</label>
                        <input type="time" name="jam_selesai" id="jamSelesai" class="form-control @error('jam_selesai') is-invalid @enderror"
                               value="{{ old('jam_selesai', isset($ujian) ? substr($ujian->jam_selesai, 0, 5) : '') }}">
                        @error('jam_selesai')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div class="invalid-feedback d-block d-none" id="errJamRange">Jam selesai harus lebih besar dari jam mulai.</div>
                    </div>
                    <div class="col-sm-4">
                        <label class="form-label">Durasi Pengerjaan</label>
                        <div class="input-group">
                            <input type="number" min="0" id="durasiJam" class="form-control" placeholder="Jam" value="{{ intdiv((int) old('durasi_menit', $ujian->durasi_menit ?? 30), 60) }}">
                            <span class="input-group-text">jam</span>
                            <input type="number" min="0" max="59" id="durasiMenit" class="form-control" placeholder="Menit" value="{{ ((int) old('durasi_menit', $ujian->durasi_menit ?? 30)) % 60 }}">
                            <span class="input-group-text">menit</span>
                        </div>
                        <input type="hidden" name="durasi_menit" id="durasiMenitTotal" value="{{ old('durasi_menit', $ujian->durasi_menit ?? 30) }}">
                        @error('durasi_menit')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>
        </div>

        {{-- Pengaturan Soal --}}
        <div class="card mb-3">
            <div class="card-body">
                <h6 class="fw-semibold mb-3">Pengaturan</h6>
                <div class="d-flex gap-4 flex-wrap">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" role="switch" name="acak_soal" id="acakSoal" value="1"
                               {{ old('acak_soal', $ujian->acak_soal ?? false) ? 'checked' : '' }}>
                        <label class="form-check-label switch-label" for="acakSoal">Acak Soal</label>
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" role="switch" name="acak_jawaban" id="acakJawaban" value="1"
                               {{ old('acak_jawaban', $ujian->acak_jawaban ?? false) ? 'checked' : '' }}>
                        <label class="form-check-label switch-label" for="acakJawaban">Acak Jawaban</label>
                    </div>
                </div>
            </div>
        </div>

        {{-- Bank Soal --}}
        <div class="card mb-3">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                    <h6 class="fw-semibold mb-0">Pilih Soal dari Bank Soal</h6>
                    <span class="badge text-bg-success" id="selectedCountBadge">Soal dipilih: 0 soal</span>
                </div>

                <div class="row g-2 mb-3">
                    <div class="col-md-6">
                        <input type="text" id="soalSearchInput" class="form-control" placeholder="Cari pertanyaan...">
                    </div>
                    <div class="col-md-4">
                        <select id="soalTipeFilter" class="form-select">
                            <option value="">Semua Tipe Soal</option>
                            <option value="pg">Pilihan Ganda</option>
                            <option value="bs">Jawaban Singkat</option>
                            <option value="ur">Uraian</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="button" class="btn btn-outline-secondary w-100" id="btnResetFilterSoal">Reset</button>
                    </div>
                </div>

                @error('soal_ids')<div class="alert alert-danger py-2">{{ $message }}</div>@enderror

                <div class="table-responsive border rounded">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width:44px;">
                                    <input type="checkbox" class="form-check-input" id="checkAllPage" title="Pilih semua di halaman ini">
                                </th>
                                <th>Pertanyaan</th>
                                <th style="width:140px;">Tipe</th>
                                <th style="width:130px;">Kategori</th>
                                <th style="width:70px;">Poin</th>
                            </tr>
                        </thead>
                        <tbody id="soalTableBody">
                            <tr><td colspan="5" class="text-center text-muted py-4">Memuat soal...</td></tr>
                        </tbody>
                    </table>
                </div>

                <nav class="mt-3">
                    <ul class="pagination pagination-sm justify-content-center mb-0" id="soalPagination"></ul>
                </nav>

                <div id="soalIdsContainer"></div>
            </div>
        </div>

        {{-- Tombol --}}
        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-success">
                <i class="bi bi-check-lg me-1"></i> {{ isset($ujian) ? 'Update Ujian' : 'Simpan Ujian' }}
            </button>
            <a href="{{ route('examination.ujian.index') }}" class="btn btn-outline-secondary">Batal</a>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function(){

  /* =========================================================
   |  Jenis Ujian: tampilkan/sembunyikan blok Karyawan
   * =========================================================*/
  const blokKaryawan = document.getElementById('blokKaryawan');
  const karyawanIdEl = document.getElementById('karyawanId');
  const karyawanSearchInput = document.getElementById('karyawanSearchInput');

  function toggleBlokKaryawan(){
    const jenis = document.querySelector('input[name="jenis"]:checked')?.value;
    const tampil = jenis === 'perorangan';
    blokKaryawan.classList.toggle('d-none', !tampil);
    if(!tampil){
      karyawanIdEl.value = '';
      karyawanSearchInput.value = '';
    }
  }
  document.querySelectorAll('input[name="jenis"]').forEach(function(el){
    el.addEventListener('change', toggleBlokKaryawan);
  });
  toggleBlokKaryawan();

  /* =========================================================
   |  Searchable Select Karyawan (custom, vanilla JS)
   * =========================================================*/
  const karyawanDropdown = document.getElementById('karyawanDropdown');
  const KARYAWAN_SEARCH_URL = @json(route('examination.ujian.karyawan-search'));
  let karyawanDebounce = null;

  function renderKaryawanDropdown(items){
    if(items.length === 0){
      karyawanDropdown.innerHTML = '<div class="list-group-item text-muted small">Tidak ditemukan</div>';
    } else {
      karyawanDropdown.innerHTML = items.map(function(item){
        return '<button type="button" class="list-group-item list-group-item-action item" data-id="' + item.id + '" data-text="' + item.text.replace(/"/g, '&quot;') + '">' + item.text + '</button>';
      }).join('');
    }
    karyawanDropdown.style.display = 'block';
  }

  karyawanSearchInput.addEventListener('input', function(){
    const q = karyawanSearchInput.value.trim();
    karyawanIdEl.value = '';
    clearTimeout(karyawanDebounce);
    if(q.length < 2){
      karyawanDropdown.style.display = 'none';
      return;
    }
    karyawanDebounce = setTimeout(function(){
      fetch(KARYAWAN_SEARCH_URL + '?q=' + encodeURIComponent(q))
        .then(function(res){ return res.json(); })
        .then(renderKaryawanDropdown)
        .catch(function(){ karyawanDropdown.style.display = 'none'; });
    }, 300);
  });

  karyawanDropdown.addEventListener('click', function(e){
    const btn = e.target.closest('.item');
    if(!btn) return;
    karyawanIdEl.value = btn.dataset.id;
    karyawanSearchInput.value = btn.dataset.text;
    karyawanDropdown.style.display = 'none';
  });

  document.addEventListener('click', function(e){
    if(!karyawanDropdown.contains(e.target) && e.target !== karyawanSearchInput){
      karyawanDropdown.style.display = 'none';
    }
  });

  // Prefill kalau ada old('karyawan_id') (validasi gagal sebelumnya)
  const oldKaryawanId = @json(old('karyawan_id', $ujian->karyawan_id ?? null));
  if(oldKaryawanId){
    fetch(KARYAWAN_SEARCH_URL + '?id=' + oldKaryawanId)
      .then(function(res){ return res.json(); })
      .then(function(items){
        if(items[0]){
          karyawanSearchInput.value = items[0].text;
          karyawanIdEl.value = items[0].id;
        }
      });
  }

  /* =========================================================
   |  Durasi Pengerjaan: gabungkan jam + menit -> total menit
   * =========================================================*/
  const durasiJamEl = document.getElementById('durasiJam');
  const durasiMenitEl = document.getElementById('durasiMenit');
  const durasiMenitTotalEl = document.getElementById('durasiMenitTotal');

  function updateDurasiTotal(){
    const jam = parseInt(durasiJamEl.value, 10) || 0;
    const menit = parseInt(durasiMenitEl.value, 10) || 0;
    durasiMenitTotalEl.value = (jam * 60) + menit;
  }
  durasiJamEl.addEventListener('input', updateDurasiTotal);
  durasiMenitEl.addEventListener('input', updateDurasiTotal);
  updateDurasiTotal();

  /* =========================================================
   |  Validasi jam mulai/selesai (client-side, pelengkap validasi server)
   * =========================================================*/
  const jamMulaiEl = document.getElementById('jamMulai');
  const jamSelesaiEl = document.getElementById('jamSelesai');
  const errJamRange = document.getElementById('errJamRange');

  function cekRentangJam(){
    if(jamMulaiEl.value && jamSelesaiEl.value && jamSelesaiEl.value <= jamMulaiEl.value){
      errJamRange.classList.remove('d-none');
      return false;
    }
    errJamRange.classList.add('d-none');
    return true;
  }
  jamMulaiEl.addEventListener('change', cekRentangJam);
  jamSelesaiEl.addEventListener('change', cekRentangJam);

  /* =========================================================
   |  Bank Soal: tabel paginated + pilihan multi-halaman
   * =========================================================*/
  const SOAL_DATA_URL = @json(route('examination.ujian.soal-data'));
  const soalTableBody = document.getElementById('soalTableBody');
  const soalPagination = document.getElementById('soalPagination');
  const selectedCountBadge = document.getElementById('selectedCountBadge');
  const checkAllPage = document.getElementById('checkAllPage');
  const soalSearchInput = document.getElementById('soalSearchInput');
  const soalTipeFilter = document.getElementById('soalTipeFilter');
  const btnResetFilterSoal = document.getElementById('btnResetFilterSoal');
  const soalIdsContainer = document.getElementById('soalIdsContainer');

  const LABEL_TIPE = { pg: 'Pilihan Ganda', bs: 'Jawaban Singkat', ur: 'Uraian' };

  let currentPage = 1;
  let currentItemsOnPage = [];
  let soalDebounce = null;
  const selectedIds = new Set(@json(array_map('intval', old('soal_ids', isset($ujian) ? $ujian->soal->pluck('id')->all() : []))));

  function updateSelectedCountBadge(){
    selectedCountBadge.textContent = 'Soal dipilih: ' + selectedIds.size + ' soal';
  }

  function escapeHtml(str){
    const div = document.createElement('div');
    div.textContent = str == null ? '' : str;
    return div.innerHTML;
  }

  function muatSoal(page){
    currentPage = page || 1;
    const params = new URLSearchParams();
    params.set('page', currentPage);
    if(soalSearchInput.value.trim()) params.set('search', soalSearchInput.value.trim());
    if(soalTipeFilter.value) params.set('tipe', soalTipeFilter.value);

    soalTableBody.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-4">Memuat soal...</td></tr>';

    fetch(SOAL_DATA_URL + '?' + params.toString())
      .then(function(res){ return res.json(); })
      .then(function(res){
        currentItemsOnPage = res.data;
        renderSoalTable(res.data);
        renderPagination(res.current_page, res.last_page);
        updateCheckAllState();
      })
      .catch(function(){
        soalTableBody.innerHTML = '<tr><td colspan="5" class="text-center text-danger py-4">Gagal memuat soal.</td></tr>';
      });
  }

  function renderSoalTable(items){
    if(items.length === 0){
      soalTableBody.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-4">Tidak ada soal yang cocok.</td></tr>';
      return;
    }
    soalTableBody.innerHTML = items.map(function(s){
      const checked = selectedIds.has(s.id) ? 'checked' : '';
      return '<tr class="soal-row">' +
        '<td><input type="checkbox" class="form-check-input soal-checkbox" data-id="' + s.id + '" ' + checked + '></td>' +
        '<td class="pertanyaan-cell">' + escapeHtml(s.pertanyaan) + '</td>' +
        '<td><span class="badge text-bg-light border">' + LABEL_TIPE[s.tipe] + '</span></td>' +
        '<td>' + escapeHtml(s.kategori || '-') + '</td>' +
        '<td>' + s.poin + '</td>' +
        '</tr>';
    }).join('');
  }

  function renderPagination(current, last){
    if(last <= 1){ soalPagination.innerHTML = ''; return; }
    let html = '';
    html += '<li class="page-item ' + (current <= 1 ? 'disabled' : '') + '"><button type="button" class="page-link" data-page="' + (current - 1) + '">&laquo;</button></li>';
    for(let p = 1; p <= last; p++){
      html += '<li class="page-item ' + (p === current ? 'active' : '') + '"><button type="button" class="page-link" data-page="' + p + '">' + p + '</button></li>';
    }
    html += '<li class="page-item ' + (current >= last ? 'disabled' : '') + '"><button type="button" class="page-link" data-page="' + (current + 1) + '">&raquo;</button></li>';
    soalPagination.innerHTML = html;
  }

  soalPagination.addEventListener('click', function(e){
    const btn = e.target.closest('.page-link');
    if(!btn || btn.closest('.page-item').classList.contains('disabled')) return;
    muatSoal(parseInt(btn.dataset.page, 10));
  });

  soalTableBody.addEventListener('change', function(e){
    if(!e.target.classList.contains('soal-checkbox')) return;
    const id = parseInt(e.target.dataset.id, 10);
    if(e.target.checked){
      selectedIds.add(id);
    } else {
      selectedIds.delete(id);
    }
    updateSelectedCountBadge();
    updateCheckAllState();
  });

  function updateCheckAllState(){
    if(currentItemsOnPage.length === 0){
      checkAllPage.checked = false;
      checkAllPage.indeterminate = false;
      return;
    }
    const semuaTerpilih = currentItemsOnPage.every(function(s){ return selectedIds.has(s.id); });
    const sebagianTerpilih = currentItemsOnPage.some(function(s){ return selectedIds.has(s.id); });
    checkAllPage.checked = semuaTerpilih;
    checkAllPage.indeterminate = !semuaTerpilih && sebagianTerpilih;
  }

  checkAllPage.addEventListener('change', function(){
    currentItemsOnPage.forEach(function(s){
      if(checkAllPage.checked){
        selectedIds.add(s.id);
      } else {
        selectedIds.delete(s.id);
      }
    });
    renderSoalTable(currentItemsOnPage);
    updateSelectedCountBadge();
  });

  soalSearchInput.addEventListener('input', function(){
    clearTimeout(soalDebounce);
    soalDebounce = setTimeout(function(){ muatSoal(1); }, 350);
  });
  soalTipeFilter.addEventListener('change', function(){ muatSoal(1); });
  btnResetFilterSoal.addEventListener('click', function(){
    soalSearchInput.value = '';
    soalTipeFilter.value = '';
    muatSoal(1);
  });

  /* =========================================================
   |  Submit: injeksikan hidden input soal_ids[] dari selectedIds,
   |  dan validasi ringan sebelum submit.
   * =========================================================*/
  document.getElementById('formUjian').addEventListener('submit', function(e){
    const jenis = document.querySelector('input[name="jenis"]:checked')?.value;

    if(!cekRentangJam()){
      e.preventDefault();
      jamSelesaiEl.focus();
      return;
    }

    if(jenis === 'perorangan' && !karyawanIdEl.value){
      e.preventDefault();
      alert('Silakan pilih karyawan untuk Ujian Perorangan.');
      karyawanSearchInput.focus();
      return;
    }

    if(selectedIds.size === 0){
      e.preventDefault();
      alert('Minimal satu soal harus dipilih dari Bank Soal.');
      return;
    }

    soalIdsContainer.innerHTML = '';
    selectedIds.forEach(function(id){
      const input = document.createElement('input');
      input.type = 'hidden';
      input.name = 'soal_ids[]';
      input.value = id;
      soalIdsContainer.appendChild(input);
    });
  });

  updateSelectedCountBadge();
  muatSoal(1);
});
</script>
@endpush
