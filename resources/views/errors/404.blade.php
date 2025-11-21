@extends('layouts.app') {{-- Ganti sesuai layout utama kamu --}}

@section('title', '404 - Halaman Tidak Ditemukan')

@section('content')
<div class="d-flex align-items-center justify-content-center vh-100 bg-light text-center px-3">
    <div>
        <h1 class="display-1 fw-bold text-danger">404</h1>
        <p class="fs-3"> <span class="text-danger">Oops!</span> Halaman tidak ditemukan.</p>
        <p class="lead"> Halaman yang kamu cari mungkin telah dihapus, dipindahkan, atau tidak pernah ada.</p>
        <a href="{{ url('/') }}" class="btn btn-warning px-4 py-2 mt-3 fw-semibold">
            <i class="fas fa-arrow-left me-2"></i> Kembali ke Beranda
        </a>
    </div>
</div>
@endsection