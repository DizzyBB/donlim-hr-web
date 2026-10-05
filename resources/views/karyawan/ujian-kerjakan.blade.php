@extends('layouts.layouts-karyawan')

@section('title', $hasilUjian->ujian->nama_ujian)
@section('hide_topbar', '1')

@push('styles')
<style>
    .exam-card{ background:var(--panel); border-radius:20px; overflow:hidden; box-shadow:0 12px 32px rgba(15,36,56,.18); }
    .exam-head{ background:var(--navy-2); color:#f6efe4; padding:1.5rem 1.75rem; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1rem; }
    .eyebrow{ color:#d9825b; font-size:.72rem; letter-spacing:.14em; font-weight:600; text-transform:uppercase; }
    .exam-head h1{ font-size:1.5rem; font-weight:700; margin:.15rem 0 .1rem; }
    .head-stats{ display:flex; align-items:center; gap:1.75rem; }
    .head-stats .label{ font-size:.68rem; letter-spacing:.12em; color:#aab6c5; text-transform:uppercase; }
    .head-stats .value{ font-size:1.25rem; font-weight:700; font-variant-numeric:tabular-nums; }
    .head-stats .timer{ font-size:1.9rem; }
    .timer.danger{ color:#ff8a7a; }

    .exam-body{ display:flex; min-height:480px; }
    .exam-nav{ width:290px; flex:0 0 290px; background:#faf6ee; border-right:1px solid #ebe4d6; padding:1.25rem; }
    .nav-grid{ display:grid; grid-template-columns:repeat(5, 1fr); gap:.5rem; }
    .nav-btn{ aspect-ratio:1; border:1px solid var(--line); background:#fff; border-radius:8px; font-weight:600; font-size:.9rem; padding:0; }
    .nav-btn.answered{ background:#e3f1ea; border-color:#7fb69a; }
    .nav-btn.current{ background:var(--navy); color:#fff; border-color:var(--navy); }

    .exam-main{ flex:1; padding:1.75rem 2rem; min-width:0; }
    .q-kicker{ color:var(--copper); font-size:.8rem; letter-spacing:.1em; text-transform:uppercase; }
    .q-text{ font-size:1.5rem; font-weight:700; margin:.25rem 0 1.25rem; white-space:pre-wrap; overflow-wrap:anywhere; }

    .opsi{ display:flex; align-items:center; gap:.85rem; padding:.9rem 1.1rem; border:1px solid var(--line); border-radius:12px; background:#fff; margin-bottom:.7rem; cursor:pointer; }
    .opsi:hover{ border-color:var(--navy); }
    .opsi.terpilih{ border-color:var(--navy); background:#eef2f7; }
    .opsi .huruf{ font-weight:700; color:var(--copper); width:1.4rem; flex:0 0 1.4rem; }
    .opsi .teks{ overflow-wrap:anywhere; }

    @media (max-width: 767.98px){
        .exam-body{ flex-direction:column; }
        .exam-nav{ width:100%; flex:0 0 auto; border-right:0; border-bottom:1px solid #ebe4d6; }
        .exam-main{ padding:1.25rem; }
        .head-stats{ gap:1rem; }
        .head-stats .timer{ font-size:1.5rem; }
    }
</style>
@endpush

@section('content')
<div class="container-xl my-3">
    <div class="exam-card">

        <header class="exam-head">
            <div>
                <div class="eyebrow">Ujian sedang berlangsung</div>
                <h1>{{ $hasilUjian->ujian->nama_ujian }}</h1>
                <div class="small" style="color:#aab6c5;">{{ $hasilUjian->karyawan->nama }} &middot; {{ $hasilUjian->karyawan->nik }}</div>
            </div>
            <div class="head-stats">
                <div>
                    <div class="label">Soal</div>
                    <div class="value" id="hdrCounter">1 / {{ $soal->count() }}</div>
                </div>
                <div>
                    <div class="label">Sisa waktu</div>
                    <div class="value timer" id="timer">--:--</div>
                </div>
                <button type="button" class="btn btn-copper fw-semibold" id="btnFinish">Selesai</button>
            </div>
        </header>

        <div class="exam-body">
            <aside class="exam-nav">
                <div class="small text-muted mb-2">Lompat ke soal</div>
                <div class="nav-grid" id="navGrid"></div>
                <div class="small text-muted mt-3" id="navInfo"></div>
            </aside>

            <section class="exam-main">
                <div class="q-kicker" id="qLabel"></div>
                <h2 class="q-text" id="qText"></h2>
                <div id="qAnswer"></div>

                <div class="d-flex justify-content-between align-items-center mt-4 gap-2">
                    <button type="button" class="btn btn-outline-secondary" id="btnPrev">Sebelumnya</button>
                    <span class="small text-muted" id="saveStatus"></span>
                    <button type="button" class="btn btn-navy" id="btnNext">Soal berikutnya</button>
                </div>
            </section>
        </div>
    </div>
</div>

<form id="formSelesai" method="POST" action="{{ route('karyawan.ujian.selesai', $hasilUjian) }}" class="d-none">
    @csrf
</form>

<div class="modal fade" id="modalSelesai" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Selesaikan ujian?</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="mb-1" id="modalSelesaiInfo"></p>
                <p class="small text-muted mb-0">Setelah diselesaikan, jawaban tidak bisa diubah lagi.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Kembali mengerjakan</button>
                <button type="button" class="btn btn-copper" id="btnKonfirmasiSelesai">Ya, selesaikan</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function(){
  const SOAL = @json($soal);
  const URL_JAWAB = @json(route('karyawan.ujian.jawab', $hasilUjian));
  const CSRF = @json(csrf_token());
  const KEY_POSISI = 'posisi_ujian_' + @json($hasilUjian->id);
  // Batas waktu dihitung dari sisa detik versi server, lalu dijalankan pakai jam browser.
  const BATAS = Date.now() + (@json($sisaDetik) * 1000);

  const el = function(id){ return document.getElementById(id); };
  const qLabel = el('qLabel'), qText = el('qText'), qAnswer = el('qAnswer');
  const navGrid = el('navGrid'), navInfo = el('navInfo'), hdrCounter = el('hdrCounter');
  const timerEl = el('timer'), saveStatus = el('saveStatus');
  const btnPrev = el('btnPrev'), btnNext = el('btnNext'), btnFinish = el('btnFinish');
  const formSelesai = el('formSelesai');
  const modal = new bootstrap.Modal(el('modalSelesai'));

  let indeks = parseInt(sessionStorage.getItem(KEY_POSISI), 10);
  if (isNaN(indeks) || indeks < 0 || indeks >= SOAL.length) indeks = 0;
  let selesaiDipicu = false;

  function huruf(i){ return String.fromCharCode(65 + i); }

  function terjawab(s){
    if (s.tipe === 'pg') return s.jawabanIndex !== null && s.jawabanIndex !== undefined;
    return (s.jawabanTeks || '').trim() !== '';
  }

  function setStatus(teks, error){
    saveStatus.textContent = teks;
    saveStatus.className = 'small ' + (error ? 'text-danger' : 'text-muted');
  }

  /* ---------- Simpan jawaban ke server ---------- */
  function simpan(s){
    // Kalau simpanan sebelumnya masih berjalan, tunggu dulu lalu simpan nilai terbaru.
    if (s.promise) {
      return s.promise.then(function(){ return s.dirty ? simpan(s) : undefined; });
    }

    const nilai = s.tipe === 'pg' ? s.jawabanIndex : (s.jawabanTeks || '');
    const body = s.tipe === 'pg'
      ? { jawaban_id: s.id, jawaban_index: s.jawabanIndex }
      : { jawaban_id: s.id, jawaban_teks: s.jawabanTeks || '' };

    setStatus('Menyimpan...');
    s.promise = fetch(URL_JAWAB, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF },
      body: JSON.stringify(body)
    })
      .then(function(res){
        if (res.status === 409) {
          return res.json().then(function(d){
            selesaiDipicu = true;
            window.location.href = d.redirect;
            throw new Error('selesai');
          });
        }
        if (!res.ok) throw new Error('gagal');
        const sekarang = s.tipe === 'pg' ? s.jawabanIndex : (s.jawabanTeks || '');
        if (sekarang === nilai) s.dirty = false;
        setStatus('Tersimpan');
      })
      .catch(function(err){
        if (err.message !== 'selesai') setStatus('Gagal menyimpan, mencoba lagi...', true);
      })
      .finally(function(){ s.promise = null; });

    return s.promise;
  }

  function flushSaatIni(){
    const s = SOAL[indeks];
    clearTimeout(s.timer);
    if (s.dirty) simpan(s);
  }

  // Coba ulang jawaban yang gagal tersimpan (mis. koneksi putus sebentar).
  setInterval(function(){
    SOAL.forEach(function(s){ if (s.dirty && !s.promise && !s.timer_aktif) simpan(s); });
  }, 5000);

  /* ---------- Tampilan ---------- */
  function renderNav(){
    navGrid.innerHTML = '';
    let jumlah = 0;
    SOAL.forEach(function(s, i){
      const b = document.createElement('button');
      b.type = 'button';
      b.textContent = i + 1;
      const ok = terjawab(s);
      if (ok) jumlah++;
      b.className = 'nav-btn' + (ok ? ' answered' : '') + (i === indeks ? ' current' : '');
      b.addEventListener('click', function(){ pindah(i); });
      navGrid.appendChild(b);
    });
    navInfo.textContent = 'Terjawab: ' + jumlah + ' dari ' + SOAL.length + ' soal';
  }

  function renderSoal(){
    const s = SOAL[indeks];
    qLabel.textContent = 'Soal ' + (indeks + 1) + ' dari ' + SOAL.length;
    qText.textContent = s.pertanyaan;
    hdrCounter.textContent = (indeks + 1) + ' / ' + SOAL.length;
    qAnswer.innerHTML = '';

    if (s.tipe === 'pg') {
      s.opsi.forEach(function(o, i){
        const label = document.createElement('label');
        label.className = 'opsi' + (s.jawabanIndex === o.index ? ' terpilih' : '');

        const input = document.createElement('input');
        input.type = 'radio';
        input.name = 'opsi';
        input.className = 'form-check-input mt-0';
        input.checked = s.jawabanIndex === o.index;
        input.addEventListener('change', function(){
          s.jawabanIndex = o.index;
          s.dirty = true;
          simpan(s);
          renderSoal();
        });

        const h = document.createElement('span');
        h.className = 'huruf';
        h.textContent = huruf(i) + '.';

        const t = document.createElement('span');
        t.className = 'teks';
        t.textContent = o.teks;

        label.appendChild(input);
        label.appendChild(h);
        label.appendChild(t);
        qAnswer.appendChild(label);
      });
    } else {
      const isUraian = s.tipe === 'ur';
      const field = document.createElement(isUraian ? 'textarea' : 'input');
      if (isUraian) { field.rows = 8; } else { field.type = 'text'; field.maxLength = 255; }
      field.className = 'form-control' + (isUraian ? '' : ' form-control-lg');
      field.placeholder = isUraian ? 'Tulis jawaban uraianmu di sini...' : 'Tulis jawaban singkatmu...';
      field.value = s.jawabanTeks || '';

      field.addEventListener('input', function(){
        s.jawabanTeks = field.value;
        s.dirty = true;
        s.timer_aktif = true;
        clearTimeout(s.timer);
        s.timer = setTimeout(function(){ s.timer_aktif = false; simpan(s); }, 800);
        renderNav();
      });
      field.addEventListener('blur', function(){ s.timer_aktif = false; flushSaatIni(); });
      qAnswer.appendChild(field);
    }

    btnPrev.disabled = indeks === 0;
    btnNext.textContent = indeks === SOAL.length - 1 ? 'Selesai' : 'Soal berikutnya';
    renderNav();
  }

  function pindah(i){
    flushSaatIni();
    indeks = Math.max(0, Math.min(SOAL.length - 1, i));
    sessionStorage.setItem(KEY_POSISI, indeks);
    renderSoal();
  }

  btnPrev.addEventListener('click', function(){ pindah(indeks - 1); });
  btnNext.addEventListener('click', function(){
    if (indeks === SOAL.length - 1) { bukaKonfirmasi(); } else { pindah(indeks + 1); }
  });

  /* ---------- Selesai ---------- */
  function bukaKonfirmasi(){
    flushSaatIni();
    const belum = SOAL.filter(function(s){ return !terjawab(s); }).length;
    el('modalSelesaiInfo').textContent = belum > 0
      ? 'Masih ada ' + belum + ' soal yang belum dijawab. Soal yang kosong bernilai 0.'
      : 'Semua soal sudah dijawab.';
    modal.show();
  }

  function kirimSelesai(otomatis){
    if (selesaiDipicu) return;
    selesaiDipicu = true;
    if (otomatis) setStatus('Waktu habis, mengumpulkan jawaban...');

    // Pastikan semua jawaban yang tertunda tersimpan dulu, baru kumpulkan.
    Promise.all(SOAL.map(function(s){
      clearTimeout(s.timer);
      return s.dirty ? simpan(s) : null;
    })).then(function(){ formSelesai.submit(); }, function(){ formSelesai.submit(); });
  }

  btnFinish.addEventListener('click', bukaKonfirmasi);
  el('btnKonfirmasiSelesai').addEventListener('click', function(){
    modal.hide();
    kirimSelesai(false);
  });

  window.addEventListener('beforeunload', function(e){
    if (!selesaiDipicu && SOAL.some(function(s){ return s.dirty; })) {
      e.preventDefault();
      e.returnValue = '';
    }
  });

  /* ---------- Timer ---------- */
  function format(detik){
    const h = Math.floor(detik / 3600);
    const m = Math.floor((detik % 3600) / 60);
    const s = detik % 60;
    const pad = function(n){ return String(n).padStart(2, '0'); };
    return (h > 0 ? h + ':' : '') + pad(m) + ':' + pad(s);
  }

  function tick(){
    const sisa = Math.max(0, Math.ceil((BATAS - Date.now()) / 1000));
    timerEl.textContent = format(sisa);
    timerEl.classList.toggle('danger', sisa <= 300);
    if (sisa <= 0) kirimSelesai(true);
  }

  setInterval(tick, 500);
  tick();
  renderSoal();
})();
</script>
@endpush
