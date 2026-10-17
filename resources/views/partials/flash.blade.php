@foreach(['success' => 'success', 'error' => 'danger', 'info' => 'info'] as $kunci => $warna)
    @if(session($kunci))
        <div class="alert alert-{{ $warna }} alert-dismissible fade show" role="alert">
            {{ session($kunci) }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
        </div>
    @endif
@endforeach
