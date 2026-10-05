<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Masuk - Donlim HR Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body{ background:#f3eee4; min-height:100vh; display:flex; align-items:center; }
        .login-card{ max-width:420px; width:100%; margin:0 auto; background:#fffdf8; border:1px solid #d9d0c3; border-radius:16px; }
        .btn-navy{ background:#17324f; border-color:#17324f; color:#fff; }
        .btn-navy:hover, .btn-navy:focus{ background:#0f2438; color:#fff; }
    </style>
</head>
<body>
    <div class="container">
        <div class="login-card p-4 p-md-5">
            <div class="text-center mb-4">
                <i class="bi bi-shield-lock fs-1" style="color:#17324f;"></i>
                <h4 class="mt-2 mb-0">Masuk</h4>
                <p class="text-muted small mb-0">Gunakan email (Administrator) atau nomor karyawan (Karyawan)</p>
            </div>

            @if ($errors->any())
                <div class="alert alert-danger py-2">
                    {{ $errors->first() }}
                </div>
            @endif

            @if (session('status'))
                <div class="alert alert-success py-2">{{ session('status') }}</div>
            @endif

            <form action="{{ route('login') }}" method="POST">
                @csrf

                <div class="mb-3">
                    <label class="form-label" for="identifier">Email / Nomor Karyawan</label>
                    <input type="text" name="identifier" id="identifier"
                           class="form-control @error('identifier') is-invalid @enderror"
                           value="{{ old('identifier') }}" autofocus placeholder="admin@donlim.test atau KRY001">
                    @error('identifier')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="mb-3">
                    <label class="form-label" for="password">Password</label>
                    <div class="input-group">
                        <input type="password" name="password" id="password" class="form-control" required>
                        <button class="btn btn-outline-secondary" type="button" id="btnTogglePassword">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                </div>

                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" name="remember" id="remember">
                    <label class="form-check-label" for="remember">Ingat saya</label>
                </div>

                <button type="submit" class="btn btn-navy w-100">Masuk</button>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('btnTogglePassword').addEventListener('click', function(){
            const input = document.getElementById('password');
            const icon = this.querySelector('i');
            const tampil = input.type === 'password';
            input.type = tampil ? 'text' : 'password';
            icon.className = tampil ? 'bi bi-eye-slash' : 'bi bi-eye';
        });
    </script>
</body>
</html>
