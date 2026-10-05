@extends('layouts.app')

@section('title', 'Data Karyawan')

@section('content')
<div class="container-fluid py-4 employee-page">
	<div class="row align-items-end g-3 mb-4">
		<div class="col">
			<div class="employee-eyebrow">People directory</div>
			<h1 class="h3 mb-1 fw-bold">Data Karyawan</h1>
			<p class="text-muted mb-0">Kelola informasi karyawan dalam satu tempat.</p>
		</div>
		<div class="col-auto d-flex align-items-center gap-4">
			<div class="employee-total">
				<span class="employee-total__number">{{ number_format($karyawan->total()) }}</span>
				<span class="employee-total__label">Total karyawan</span>
			</div>
			<a href="{{ route('karyawan.create') }}" class="btn btn-primary text-nowrap">Tambah Karyawan</a>
		</div>
	</div>

	@if(session('success'))
		<div class="alert alert-success alert-dismissible fade show" role="alert">
			{{ session('success') }}
			<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
		</div>
	@endif
	@if(session('error'))
		<div class="alert alert-danger alert-dismissible fade show" role="alert">
			{{ session('error') }}
			<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
		</div>
	@endif

	<div class="card border-0 shadow-sm employee-table-card">
		<div class="card-body p-3 p-lg-4">
			<form action="{{ route('karyawan.index') }}" method="GET" class="row g-2 align-items-center mb-4">
				<div class="col-12 col-md-7 col-lg-5">
					<label for="employee-search" class="visually-hidden">Cari nama karyawan</label>
					<input
						id="employee-search"
						type="search"
						name="search"
						value="{{ request('search') }}"
						class="form-control"
						placeholder="Cari nama karyawan..."
					>
				</div>
				<div class="col-6 col-md-auto">
					<button type="submit" class="btn btn-primary w-100"><i class="lni lni-search-alt"></i></button>
				</div>
				@if(request()->filled('search'))
					<div class="col-6 col-md-auto">
						<a href="{{ route('karyawan.index') }}" class="btn btn-outline-secondary w-100"><i class="lni lni-cross-circle"></i></a>
					</div>
				@endif
				<div class="col-12 col-md text-md-end">
					<span class="small text-muted">
						Menampilkan {{ $karyawan->firstItem() ?? 0 }}–{{ $karyawan->lastItem() ?? 0 }} dari {{ number_format($karyawan->total()) }} karyawan
					</span>
				</div>
			</form>

			<div class="table-responsive">
				<table class="table table-hover align-middle mb-0 employee-table">
					<thead>
						<tr>
							<th scope="col">Karyawan</th>
							<th scope="col">NIK</th>
							<th scope="col">Jabatan</th>
							<th scope="col">Departemen</th>
							<th scope="col">Role</th>
							<th scope="col" class="text-end">Aksi</th>
						</tr>
					</thead>
					<tbody>
						@forelse($karyawan as $item)
							<tr>
								<td>
									<div class="d-flex align-items-center gap-3">
										<span class="employee-avatar" aria-hidden="true">{{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($item->nama, 0, 1)) }}</span>
										<div class="min-w-0">
											<div class="fw-semibold text-dark">{{ $item->nama }}</div>
											<div class="small text-muted text-truncate">{{ $item->email ?: 'Email belum tersedia' }}</div>
										</div>
									</div>
								</td>
								<td class="text-nowrap">{{ $item->nik }}</td>
								<td>{{ $item->jabatan ?: '-' }}</td>
								<td>{{ $item->departemen ?: '-' }}</td>
								<td>
									@if($item->role === 'administrator')
										<span class="badge employee-role employee-role--admin">Administrator</span>
									@else
										<span class="badge employee-role employee-role--staff">Karyawan</span>
									@endif
								</td>
								<td class="text-end text-nowrap">
									<a href="{{ route('karyawan.edit', $item) }}" class="btn btn-sm btn-outline-primary p-2"><i class="lni lni-pencil-alt"></i></a>
									<form action="{{ route('karyawan.destroy', $item) }}" method="POST" class="d-inline"
										onsubmit="return confirm('Yakin ingin menghapus data karyawan ini?')">
										@csrf
										@method('DELETE')
										<button type="submit" class="btn btn-sm btn-outline-danger p-2"><i class="lni lni-trash-can"></i></button>
									</form>
								</td>
							</tr>
						@empty
							<tr>
								<td colspan="6" class="text-center py-5">
									<div class="fw-semibold mb-1">Data karyawan tidak ditemukan</div>
									<div class="small text-muted">Coba gunakan kata kunci nama yang berbeda.</div>
								</td>
							</tr>
						@endforelse
					</tbody>
				</table>
			</div>

			@if($karyawan->hasPages())
				<div class="d-flex justify-content-end mt-4">
					{{ $karyawan->links('pagination::bootstrap-5') }}
				</div>
			@endif
		</div>
	</div>
</div>
@endsection

@push('styles')
<style>
	.employee-page {
		--employee-ink: #202c35;
		--employee-green: #23785f;
		--employee-line: #e7ece9;
		color: var(--employee-ink);
	}

	.employee-eyebrow {
		color: var(--employee-green);
		font-size: .72rem;
		font-weight: 700;
		letter-spacing: .08em;
		margin-bottom: .45rem;
		text-transform: uppercase;
	}

	.employee-total {
		align-items: center;
		border-left: 3px solid var(--employee-green);
		display: flex;
		flex-direction: column;
		padding-left: .9rem;
	}

	.employee-total__number {
		font-size: 1.35rem;
		font-weight: 700;
		line-height: 1.2;
	}

	.employee-total__label {
		color: #738078;
		font-size: .75rem;
		margin-top: .2rem;
	}

	.employee-table-card {
		border-radius: .5rem;
	}

	.employee-table thead th {
		background: #f5f8f6;
		border-bottom: 1px solid var(--employee-line);
		color: #66736c;
		font-size: .72rem;
		font-weight: 700;
		padding: .85rem 1rem;
		text-transform: uppercase;
		white-space: nowrap;
	}

	.employee-table tbody td {
		border-color: var(--employee-line);
		padding: .9rem 1rem;
	}

	.employee-avatar {
		align-items: center;
		background: #e8f2ed;
		border-radius: 50%;
		color: var(--employee-green);
		display: inline-flex;
		flex: 0 0 2.5rem;
		font-size: .9rem;
		font-weight: 700;
		height: 2.5rem;
		justify-content: center;
		width: 2.5rem;
	}

	.employee-role {
		font-size: .72rem;
		font-weight: 600;
		padding: .4rem .6rem;
	}

	.employee-role--admin {
		background: #fff2dc;
		color: #8b5a0a;
	}

	.employee-role--staff {
		background: #e8f2ed;
		color: #23785f;
	}

	@media (max-width: 575.98px) {
		.employee-total {
			align-items: flex-end;
		}
	}
</style>
@endpush
