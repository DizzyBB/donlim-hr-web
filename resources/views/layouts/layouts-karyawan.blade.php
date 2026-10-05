<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Portal Karyawan')</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="shortcut icon" href="{{ asset('assets/images/favicon.svg') }}" type="image/x-icon" />
    {{-- Layout ini sengaja mandiri (Bootstrap dari CDN) dan TIDAK memakai @vite / app.js milik
         template admin, supaya halaman ujian tidak ikut menjalankan script bawaan template admin. --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        :root{
            --navy:#17324f; --navy-2:#0f2438; --copper:#b15c38; --copper-deep:#8c4328;
            --paper:#f3eee4; --panel:#fffdf8; --line:#d9d0c3; --moss:#3d6b4f;
        }
        body{ background:var(--paper); color:#1c1914; }
        .portal-topbar{ background:var(--navy-2); }
        .btn-navy{ background:var(--navy); color:#fff; border-color:var(--navy); }
        .btn-navy:hover, .btn-navy:focus{ background:var(--navy-2); color:#fff; }
        .btn-copper{ background:var(--copper); color:#fff; border-color:var(--copper); }
        .btn-copper:hover, .btn-copper:focus{ background:var(--copper-deep); color:#fff; }
        .panel{ background:var(--panel); border:1px solid var(--line); border-radius:14px; }
    </style>
    @stack('styles')
</head>
<body>
    @sectionMissing('hide_topbar')
        <header class="portal-topbar text-white">
            <div class="container-xl d-flex justify-content-between align-items-center py-3">
                <div class="fw-semibold"><i class="bi bi-journal-check me-2"></i>Portal Karyawan</div>
                <div class="d-flex align-items-center gap-3">
                    <div class="text-end small lh-sm d-none d-sm-block">
                        <div class="fw-semibold">{{ auth('karyawan')->user()->nama ?? '-' }}</div>
                        <div class="text-white-50">{{ auth('karyawan')->user()->nik ?? '' }}</div>
                    </div>
                    <form method="POST" action="{{ route('logout') }}" class="m-0">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-outline-light">Keluar</button>
                    </form>
                </div>
            </div>
        </header>
    @endif

    @yield('content')

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    @stack('scripts')
</body>
</html>
