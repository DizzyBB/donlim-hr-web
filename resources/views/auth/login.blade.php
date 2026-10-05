<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Wellcome Donlim HR WEB</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.3.1/css/all.min.css" />
    @vite(['resources/sass/login.css'])
    </head>
    <body>
      <div class="container">
      <div class="forms-container">
        <div class="signin-signup">
          <form action="{{ route('login') }}" method="POST" class="sign-in-form">
              @csrf
              <img src="{{ asset('assets/images/login-page/Picture.svg') }}" class="logo" alt="">
              <h2 class="title">Sign In</h2>
              @if ($errors->any())
                <div class="alert alert-danger fadeOut py-2">
                    {{ $errors->first() }}
                </div>
            @endif

            @if (session('status'))
                <div class="alert alert-success py-2">{{ session('status') }}</div>
            @endif
              <div class="input-field">
                <i class="fas fa-user"></i>
                <input type="text" placeholder="Masukan Nomor Karyawan" name="nik" id="nik" class="form-control @error('nik') is-invalid @enderror" value="{{ old('nik') }}" autofocus required/>
              </div>
              <div class="input-field">
                <i class="fas fa-lock"></i>
                <input type="password" placeholder="Password" name="password" id="password" class="form-control" required />
              </div>
              <input type="submit" value="Login" class="btn solid" />  
            </form>
        </div>
      </div>
      <div class="panels-container">

        <div class="panel left-panel">
          <img src="{{ asset('assets/images/login-page/register.svg') }}" class="image" alt="">
        </div>
      </div>
    </div>    
  </body>
</html>
