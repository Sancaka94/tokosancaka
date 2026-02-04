@extends('layouts.app')

@section('title', 'Daftar Pelanggan')

@section('content')
<div class="card shadow-sm">
    <div class="card-body">
        <h1 class="card-title text-center fs-2 mb-5">Daftar Pelanggan</h1>
        
        <!-- Header: Search -->
        <div class="row justify-content-center mb-4">
            <div class="col-md-8">
                <form action="{{ route('pelanggan.public.index') }}" method="GET">
                    <div class="input-group">
                        <span class="input-group-text"><i class="fa-solid fa-search"></i></span>
                        <input type="text" name="search" class="form-control" placeholder="Cari Nama, ID, atau No. WA..." value="{{ request('search') }}">
                        <button class="btn btn-primary" type="submit">Cari</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Tabel Data Pelanggan -->
        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th scope="col">ID Pelanggan</th>
                        <th scope="col">Nama Pelanggan</th>
                        <th scope="col">No. WA</th>
                        <th scope="col">Alamat</th>
                        <th scope="col">Keterangan</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($pelanggans as $pelanggan)
                    <tr>
                        <td><strong>{{ $pelanggan->id_pelanggan }}</strong></td>
                        <td>{{ $pelanggan->nama_pelanggan }}</td>
                        <td>{{ $pelanggan->nomor_wa ?? '-' }}</td>
                        <td>{{ $pelanggan->alamat }}</td>
                        <td>{{ $pelanggan->keterangan ?? '-' }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center py-5">
                            <div class="text-muted">
                                <i class="fa-solid fa-folder-open fa-3x mb-3"></i>
                                <p class="fs-5">Data pelanggan tidak ditemukan.</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Paginasi -->
        @if ($pelanggans->hasPages())
        <div class="d-flex flex-column align-items-center mt-4">
            {{-- Info jumlah data --}}
            <div class="mb-2 text-muted">
                Menampilkan {{ $pelanggans->firstItem() }} - {{ $pelanggans->lastItem() }}
                dari total {{ $pelanggans->total() }} pelanggan
            </div>

            {{-- Tombol pagination --}}
            <div>
                {{ $pelanggans->appends(request()->query())->links('pagination::bootstrap-5') }}
            </div>
        </div>
        @endif
    </div>
</div>
@endsection

