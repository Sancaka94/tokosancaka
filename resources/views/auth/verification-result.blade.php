@extends('layouts.auth')

@section('title', 'Status Verifikasi Pendaftaran')

@section('content')
    @if($status == 'success')
        <div class="alert alert-success text-center">
            <h4 class="alert-heading"><i class="fa fa-check-circle"></i> Berhasil!</h4>
            <p>{{ $message }}</p>
        </div>
        <div class="card">
            <div class="card-header">
                <strong>Detail Login untuk Dikirim ke Pengguna:</strong>
            </div>
            <ul class="list-group list-group-flush">
                <li class="list-group-item"><strong>Email:</strong> {{ $email }}</li>
                <li class="list-group-item"><strong>Password:</strong> <kbd>{{ $password }}</kbd></li>
            </ul>
        </div>
        <div class="text-center mt-4">
            <a href="{{ url('/admin/dashboard') }}" class="btn btn-primary">Kembali ke Dashboard</a>
        </div>
    @else
        <div class="alert alert-danger text-center">
            <h4 class="alert-heading"><i class="fa fa-times-circle"></i> Gagal!</h4>
            <p>{{ $message }}</p>
        </div>
        <div class="text-center mt-4">
            <a href="{{ url('/') }}" class="btn btn-secondary">Kembali ke Halaman Utama</a>
        </div>
    @endif
@endsection
