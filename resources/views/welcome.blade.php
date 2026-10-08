<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <link rel="shortcut icon" href="{{ asset('assets/images/favicon.svg') }}" type="image/x-icon"/>
  <title>Donlim HR WEB</title>
  <link rel="stylesheet" href="">
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Public+Sans:wght@400;500;600;700&display=swap');

        :root {
        --bg: #eef2f5;
        --surface: #ffffff;
        --ink: #12263a;
        --ink-soft: #52667a;
        --line: #d5dde4;
        --accent: #003cb5;
        --accent-dark: #f90;
        --accent-tint: #e3f3f4;
        --danger: #b3392b;
        --danger-tint: #fbeceb;
        --ok: #1d7d52;
        --ok-tint: #e6f5ee;
        --warn: #8a5a00;
        --warn-tint: #fdf3dc;
        --radius: 10px;
        --nav-h: 64px;
        --font: 'Public Sans', system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif;
        }

        *, *::before, *::after { box-sizing: border-box; }
        html { scroll-behavior: smooth; scroll-padding-top: calc(var(--nav-h) + 16px); }

        body {
        margin: 0;
        background: var(--bg);
        color: var(--ink);
        font-family: var(--font);
        font-size: 15px;
        line-height: 1.5;
        }

        /* ---------- Navbar ---------- */
        .navbar {
        position: sticky; top: 0; z-index: 50;
        background: var(--surface);
        border-bottom: 1px solid var(--line);
        }
        .logo{
            width: 100px;
        }
        .nav-inner {
        display: flex; align-items: center; gap: 24px;
        max-width: 1080px; height: var(--nav-h);
        margin: 0 auto; padding: 0 20px;
        }
        .brand {
        display: flex; align-items: center; gap: 10px;
        font-weight: 700; font-size: 17px; letter-spacing: -0.01em;
        color: var(--ink); text-decoration: none;
        }
        .brand-mark {
        display: grid; place-items: center;
        width: 30px; height: 30px;
        font-size: 15px; font-weight: 700; color: #fff;
        background: var(--accent); border-radius: 8px;
        }
        .nav-links {
        display: flex; align-items: center; gap: 4px;
        margin: 0 0 0 auto; padding: 0; list-style: none;
        }
        .nav-links a {
        display: block; padding: 8px 14px;
        font-weight: 500; color: var(--ink-soft);
        text-decoration: none; border-radius: 8px;
        transition: color .15s, background .15s;
        }
        .nav-links a:hover { color: var(--ink); background: var(--bg); }
        .nav-links a.active { color: var(--accent); background: var(--accent-tint); }
        .nav-links .nav-cta {
        margin-left: 8px; color: #fff; background: var(--accent); font-weight: 600;
        }
        .nav-links .nav-cta:hover { color: #fff; background: var(--accent-dark); }
        .nav-user { padding: 0 8px 0 14px; font-weight: 600; font-size: 14px; }
        .nav-logout {
        padding: 8px 14px; font: inherit; font-weight: 500; color: var(--danger);
        background: none; border: 1px solid var(--line); border-radius: 8px; cursor: pointer;
        }
        .nav-logout:hover { background: var(--danger-tint); }

        .nav-toggle {
        display: none; margin-left: auto;
        width: 40px; height: 40px; padding: 0;
        color: var(--ink); background: none;
        border: 1px solid var(--line); border-radius: 8px; cursor: pointer;
        }
        .nav-toggle span, .nav-toggle span::before, .nav-toggle span::after {
        display: block; width: 18px; height: 2px; margin: auto;
        background: currentColor; border-radius: 2px;
        transition: transform .2s, background .2s;
        }
        .nav-toggle span { position: relative; }
        .nav-toggle span::before, .nav-toggle span::after { content: ''; position: absolute; left: 0; }
        .nav-toggle span::before { top: -6px; }
        .nav-toggle span::after  { top: 6px; }
        .nav-toggle[aria-expanded="true"] span { background: transparent; }
        .nav-toggle[aria-expanded="true"] span::before { transform: translateY(6px) rotate(45deg); }
        .nav-toggle[aria-expanded="true"] span::after  { transform: translateY(-6px) rotate(-45deg); }

        /* ---------- Layout ---------- */
        .page { padding: 32px 20px 64px; }
        .grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 24px;
        max-width: 1080px;
        margin: 0 auto;
        align-items: start;
        }
        .card {
        max-width: 1080px; margin-inline: auto; padding: 28px;
        background: var(--surface);
        border: 1px solid var(--line);
        border-radius: var(--radius);
        }
        .grid > .card { margin: 0; }
        .card h2 { margin: 0 0 4px; font-size: 20px; font-weight: 700; letter-spacing: -0.01em; }
        .sub { margin: 0 0 20px; color: var(--ink-soft); font-size: 14px; }

        /* ---------- Form ---------- */
        form { display: flex; flex-direction: column; }
        input, select, textarea {
        width: 100%; margin-top: 14px; padding: 10px 12px;
        font: inherit; color: var(--ink); background: var(--surface);
        border: 1px solid var(--line); border-radius: 8px;
        transition: border-color .15s, box-shadow .15s;
        }
        textarea { min-height: 84px; resize: vertical; }
        input::placeholder, textarea::placeholder { color: var(--ink-soft); opacity: .8; }
        input:hover, select:hover, textarea:hover { border-color: var(--ink-soft); }
        input:disabled, select:disabled { opacity: .55; cursor: not-allowed; }

        input:focus-visible, select:focus-visible, textarea:focus-visible {
        outline: 3px solid var(--accent-tint);
        border-color: var(--accent);
        box-shadow: 0 0 0 1px var(--accent);
        }
        .btn:focus-visible, .pager button:focus-visible, .pager a:focus-visible, .nav-toggle:focus-visible,
        .nav-links a:focus-visible, .nav-logout:focus-visible, .brand:focus-visible {
        outline: 3px solid var(--accent); outline-offset: 2px;
        }

        .small { margin-top: 14px; font-size: 13px; font-weight: 600; color: var(--ink-soft); }
        .small + input { margin-top: 6px; }

        input.is-invalid, select.is-invalid, textarea.is-invalid {
        border-color: var(--danger); background: var(--danger-tint);
        }
        .err { margin-top: 4px; font-size: 13px; color: var(--danger); }
        .err:empty { display: none; }

        .btn {
        align-self: flex-start; margin-top: 22px; padding: 11px 22px;
        font: inherit; font-weight: 600; color: #fff; background: var(--accent);
        border: 0; border-radius: 8px; cursor: pointer;
        transition: background .15s, transform .05s;
        text-decoration: none; display: inline-block;
        }
        .btn:hover { background: var(--accent-dark); }
        .btn:active { transform: translateY(1px); }
        .btn:disabled { opacity: .6; cursor: progress; }
        .btn.loading::after {
        content: ''; display: inline-block; width: 12px; height: 12px;
        margin-left: 10px; vertical-align: -1px;
        border: 2px solid rgba(255,255,255,.5); border-top-color: #fff; border-radius: 50%;
        animation: spin .7s linear infinite;
        }
        @keyframes spin { to { transform: rotate(360deg); } }

        /* ---------- Alert ---------- */
        .alert {
        display: none; margin-bottom: 8px; padding: 11px 14px;
        font-size: 14px; border: 1px solid transparent; border-radius: 8px;
        }
        .alert.show { display: block; }
        .alert.success { color: var(--ok); background: var(--ok-tint); border-color: var(--ok); }
        .alert.error   { color: var(--danger); background: var(--danger-tint); border-color: var(--danger); }

        /* ---------- Tabel ---------- */
        .card:has(table) { overflow-x: auto; }
        table { width: 100%; min-width: 620px; margin-top: 16px; border-collapse: collapse; }
        th, td { padding: 12px 14px; text-align: left; vertical-align: top; border-bottom: 1px solid var(--line); }
        th { font-size: 13px; font-weight: 600; color: var(--ink-soft); background: var(--bg); }
        th:first-child { border-top-left-radius: 8px; }
        th:last-child  { border-top-right-radius: 8px; }
        tbody tr:hover { background: var(--accent-tint); }
        td .muted { display: block; font-size: 13px; color: var(--ink-soft); }

        .badge { display: inline-block; padding: 3px 10px; font-size: 13px; font-weight: 600; border-radius: 999px; white-space: nowrap; }
        .badge.pending  { color: var(--warn);   background: var(--warn-tint); }
        .badge.approved { color: var(--ok);     background: var(--ok-tint); }
        .badge.rejected { color: var(--danger); background: var(--danger-tint); }

        /* ---------- Cek Riwayat (form GET) ---------- */
        .riwayat-search { display: flex; flex-direction: row; gap: 10px; align-items: flex-start; flex-wrap: wrap; }
        .riwayat-search input { margin-top: 0; max-width: 260px; }
        .riwayat-search .btn { margin-top: 0; }

        /* ---------- Pager ---------- */
        .pager { display: flex; align-items: center; justify-content: flex-end; gap: 12px; margin-top: 16px; font-size: 14px; color: var(--ink-soft); }
        .pager button, .pager a {
        width: 36px; height: 36px; font-size: 18px; line-height: 1; color: var(--ink);
        background: var(--surface); border: 1px solid var(--line); border-radius: 8px; cursor: pointer;
        display: inline-flex; align-items: center; justify-content: center; text-decoration: none;
        }
        .pager a:hover { border-color: var(--accent); color: var(--accent); }
        .pager button:disabled { opacity: .4; cursor: not-allowed; }

        /* ---------- Responsif ---------- */
        @media (max-width: 860px) {
        .grid { grid-template-columns: 1fr; }
        .card { padding: 22px; }
        }
        @media (max-width: 720px) {
        .nav-toggle { display: block; }
        .nav-links {
            display: none; position: absolute; top: var(--nav-h); left: 0; right: 0;
            flex-direction: column; align-items: stretch; gap: 2px;
            padding: 12px 20px 16px; background: var(--surface);
            border-bottom: 1px solid var(--line);
        }
        .nav-links.open { display: flex; }
        .nav-links a, .nav-logout { padding: 12px 14px; text-align: left; }
        .nav-links .nav-cta { margin: 8px 0 0; text-align: center; }
        .nav-user { padding: 8px 14px; }
        }
        @media (max-width: 480px) {
        .page { padding: 20px 12px 48px; }
        .nav-inner { padding: 0 12px; }
        .btn { align-self: stretch; }
        }
        @media (prefers-reduced-motion: reduce) {
        * { transition: none !important; animation-duration: .01ms !important; }
        html { scroll-behavior: auto; }
        }

  </style>
</head>
<body>

  {{-- ============ Navbar ============ --}}
  <header class="navbar">
    <nav class="nav-inner" aria-label="Navigasi utama">
      <a href="{{ url('/') }}" class="brand">
        <img src="{{ asset('assets/images/logo/donlim.svg') }}" alt="logo" class="logo"/>
      </a>

      <button class="nav-toggle" type="button" aria-label="Buka menu" aria-expanded="false" aria-controls="navLinks">
        <span></span>
      </button>

      <ul class="nav-links" id="navLinks">
        <li><a href="#request-training">Request Training</a></li>
        <li><a href="#pesan-ruang">Pesan Ruang</a></li>        
        {{-- <li><a href="{{ route('login') }}" target="_blank" class="nav-cta">Login</a></li> --}}
      </ul>
    </nav>
  </header>

  <main class="page">
    <div class="grid">
      {{-- ============ FORM 1: Request Training ============ --}}
      <div class="card" id="request-training">
        <h2>Request Training</h2>
        <p class="sub">Ajukan kebutuhan training internal atau eksternal.</p>

        @if(session('success_training'))
          <div class="alert show success" role="status">{{ session('success_training') }}</div>
        @elseif($errors->training->any())
          <div class="alert show error" role="status">{{ $errors->training->first() }}</div>
        @else
          <div class="alert" role="status"></div>
        @endif

        <form id="formTraining" action="{{ route('public.training.store') }}" method="POST" novalidate>
          @csrf

          <input name="nik" placeholder="NIK Pemohon" inputmode="numeric" autocomplete="off" required
                 value="{{ old('nik', $nikDicari ?? '') }}"
                 class="{{ $errors->training->has('nik') ? 'is-invalid' : '' }}">
          <div class="err" data-error-for="nik">{{ $errors->training->first('nik') }}</div>

          <input name="title" placeholder="Nama Pemohon" required value="{{ old('title') }}"
                 class="{{ $errors->training->has('title') ? 'is-invalid' : '' }}">
          <div class="err" data-error-for="title">{{ $errors->training->first('title') }}</div>

          <select name="type" id="trainingType" class="{{ $errors->training->has('type') ? 'is-invalid' : '' }}">
            <option value="internal" {{ old('type', 'internal') == 'internal' ? 'selected' : '' }}>Internal</option>
            <option value="external" {{ old('type') == 'external' ? 'selected' : '' }}>Eksternal</option>
          </select>
          <div class="err" data-error-for="type">{{ $errors->training->first('type') }}</div>

          <textarea name="reason" placeholder="Alasan kebutuhan training"
                    class="{{ $errors->training->has('reason') ? 'is-invalid' : '' }}">{{ old('reason') }}</textarea>
          <div class="err" data-error-for="reason">{{ $errors->training->first('reason') }}</div>

          <textarea name="objective" placeholder="Tujuan/kompetensi yang diharapkan"
                    class="{{ $errors->training->has('objective') ? 'is-invalid' : '' }}">{{ old('objective') }}</textarea>
          <div class="err" data-error-for="objective">{{ $errors->training->first('objective') }}</div>

          <textarea name="description" placeholder="Deskripsi (opsional)">{{ old('description') }}</textarea>

          <select name="room_id" id="trainingRoom" class="room-select {{ $errors->training->has('room_id') ? 'is-invalid' : '' }}">
            <option value="">Pilih ruangan (wajib untuk internal)</option>
            @foreach($rooms as $room)
              <option value="{{ $room->id }}" {{ old('room_id') == $room->id ? 'selected' : '' }}>
                {{ $room->name }}{{ $room->capacity ? " (kapasitas {$room->capacity})" : '' }}
              </option>
            @endforeach
          </select>
          <div class="err" data-error-for="room_id">{{ $errors->training->first('room_id') }}</div>

          <label class="small" for="t_start">Mulai</label>
          <input id="t_start" type="datetime-local" name="start_time" required value="{{ old('start_time') }}"
                 class="{{ $errors->training->has('start_time') ? 'is-invalid' : '' }}">
          <div class="err" data-error-for="start_time">{{ $errors->training->first('start_time') }}</div>

          <label class="small" for="t_end">Selesai</label>
          <input id="t_end" type="datetime-local" name="end_time" required value="{{ old('end_time') }}"
                 class="{{ $errors->training->has('end_time') ? 'is-invalid' : '' }}">
          <div class="err" data-error-for="end_time">{{ $errors->training->first('end_time') }}</div>

          <button type="submit" class="btn">Kirim</button>
        </form>
      </div>

      {{-- ============ FORM 2: Pesan Ruang Training ============ --}}
      <div class="card" id="pesan-ruang">
        <h2>Pesan Ruang Training</h2>
        <p class="sub">Untuk rapat atau kegiatan internal; maksimal {{ config('training.room_max_hours') }} jam.</p>

        @if(session('success_room'))
          <div class="alert show success" role="status">{{ session('success_room') }}</div>
        @elseif($errors->room->any())
          <div class="alert show error" role="status">{{ $errors->room->first() }}</div>
        @else
          <div class="alert" role="status"></div>
        @endif

        <form id="formRoom" action="{{ route('public.room.store') }}" method="POST" novalidate>
          @csrf

          <input name="nik" placeholder="NIK" inputmode="numeric" autocomplete="off" required
                 value="{{ old('nik', $nikDicari ?? '') }}"
                 class="{{ $errors->room->has('nik') ? 'is-invalid' : '' }}">
          <div class="err" data-error-for="nik">{{ $errors->room->first('nik') }}</div>

          <input name="title" placeholder="Nama kegiatan" required value="{{ old('title') }}"
                 class="{{ $errors->room->has('title') ? 'is-invalid' : '' }}">
          <div class="err" data-error-for="title">{{ $errors->room->first('title') }}</div>

          {{-- Cuma 2 pilihan tetap: Ruang Pelatihan HRD, atau Lainnya (isi manual). --}}
          <select name="room_choice" id="roomChoice" class="{{ $errors->room->has('room_choice') ? 'is-invalid' : '' }}" required>
            <option value="">Pilih ruangan</option>
            <option value="hrd" {{ old('room_choice') == 'hrd' ? 'selected' : '' }}>Ruang Pelatihan HRD</option>
            <option value="lainnya" {{ old('room_choice') == 'lainnya' ? 'selected' : '' }}>Lainnya</option>
          </select>
          <div class="err" data-error-for="room_choice">{{ $errors->room->first('room_choice') }}</div>

          <input type="text" name="room_name_custom" id="roomNameCustom"
                 placeholder="Tulis nama/lokasi ruangan"
                 value="{{ old('room_name_custom') }}"
                 class="{{ $errors->room->has('room_name_custom') ? 'is-invalid' : '' }}"
                 style="{{ old('room_choice') == 'lainnya' ? '' : 'display:none;' }}">
          <div class="err" data-error-for="room_name_custom">{{ $errors->room->first('room_name_custom') }}</div>

          <label class="small" for="r_start">Mulai</label>
          <input id="r_start" type="datetime-local" name="start_time" required value="{{ old('start_time') }}"
                 class="{{ $errors->room->has('start_time') ? 'is-invalid' : '' }}">
          <div class="err" data-error-for="start_time">{{ $errors->room->first('start_time') }}</div>

          <label class="small" for="r_end">Selesai</label>
          <input id="r_end" type="datetime-local" name="end_time" required value="{{ old('end_time') }}"
                 class="{{ $errors->room->has('end_time') ? 'is-invalid' : '' }}">
          <div class="err" data-error-for="end_time">{{ $errors->room->first('end_time') }}</div>

          <button type="submit" class="btn">Kirim</button>
        </form>
      </div>
    </div>    
  </main>  

  <script>
    (() => {
      'use strict';
      // Murni interaksi tampilan -- TIDAK ada fetch/AJAX/API di sini.
      const $  = (s, el = document) => el.querySelector(s);
      const $$ = (s, el = document) => [...el.querySelectorAll(s)];

      /* ===== Navbar: toggle menu mobile ===== */
      const toggle = $('.nav-toggle');
      const menu = $('#navLinks');
      const closeMenu = () => {
        menu?.classList.remove('open');
        toggle?.setAttribute('aria-expanded', 'false');
      };
      toggle?.addEventListener('click', () => {
        const open = menu.classList.toggle('open');
        toggle.setAttribute('aria-expanded', String(open));
      });
      menu?.addEventListener('click', e => { if (e.target.closest('a')) closeMenu(); });
      document.addEventListener('keydown', e => { if (e.key === 'Escape') closeMenu(); });
      document.addEventListener('click', e => { if (!e.target.closest('.navbar')) closeMenu(); });

      /* ===== Navbar: tandai menu aktif sesuai section yang terlihat ===== */
      const anchors = $$('.nav-links a[href^="#"]');
      const sections = anchors.map(a => $(a.getAttribute('href'))).filter(Boolean);
      if ('IntersectionObserver' in window && sections.length) {
        const io = new IntersectionObserver(entries => {
          entries.forEach(en => {
            if (!en.isIntersecting) return;
            anchors.forEach(a => a.classList.toggle('active', a.getAttribute('href') === `#${en.target.id}`));
          });
        }, { rootMargin: '-30% 0px -60% 0px' });
        sections.forEach(s => io.observe(s));
      }

      /* ===== Form Request Training: nonaktifkan pilihan ruangan kalau Eksternal ===== */
      const trainingType = $('#trainingType');
      const trainingRoom = $('#trainingRoom');
      function sinkronRuanganTraining(){
        const internal = trainingType.value === 'internal';
        trainingRoom.disabled = !internal;
        if (!internal) trainingRoom.value = '';
      }
      trainingType?.addEventListener('change', sinkronRuanganTraining);
      if (trainingType) sinkronRuanganTraining();

      /* ===== Form Pesan Ruang: munculkan input nama ruangan kalau pilih "Lainnya" ===== */
      const roomChoice = $('#roomChoice');
      const roomNameCustom = $('#roomNameCustom');
      function sinkronRuanganLainnya(){
        const tampil = roomChoice.value === 'lainnya';
        roomNameCustom.style.display = tampil ? '' : 'none';
        roomNameCustom.required = tampil;
        if (!tampil) roomNameCustom.value = '';
      }
      roomChoice?.addEventListener('change', sinkronRuanganLainnya);
      if (roomChoice) sinkronRuanganLainnya();
    })();
  </script>
</body>
</html>
