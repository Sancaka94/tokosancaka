{{--
    File: resources/views/auth/passwords/email.blade.php
    Ini adalah halaman formulir untuk meminta link reset password.
--}}
@extends('layouts.app')

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<style>
    body, .gradient-background {
        background: linear-gradient(135deg, #e0c3fc 0%, #ffffff 50%, #8ec5fc 100%);
    }
    .card-custom {
        border: none;
        border-radius: 1.25rem;
        box-shadow: 0 1rem 3rem rgba(0,0,0,.175)!important;
    }
    .btn-lg-custom {
        padding: 0.75rem 1.25rem;
        font-size: 1.1rem;
    }
</style>
@endpush

@section('content')
<div class="gradient-background mt-4">
    <div class="container">
        <div class="row vh-100 justify-content-center align-items-center">
            <div class="col-11 col-sm-8 col-md-6 col-lg-5 col-xl-4">
                <div class="card card-custom">
                    <div class="card-body p-4 p-sm-5">
                        <div class="text-center mb-4">
                            <i class="bi bi-key-fill text-primary" style="font-size: 3rem;"></i>
                            <h2 class="h3 fw-bold mt-3">Lupa Password?</h2>
                            <p class="text-muted">Jangan khawatir! Cukup masukkan email Anda di bawah ini.</p>
                        </div>

                        @if (session('status'))
                            <div class="alert alert-success d-flex align-items-center" role="alert">
                                <i class="bi bi-check-circle-fill me-2"></i>
                                <div>
                                    {{ session('status') }}
                                </div>
                            </div>
                        @endif

                        {{-- ✅ PERBAIKAN KUNCI: Menentukan action form dan link secara dinamis --}}
                        @php
                            // Cek apakah URL saat ini berada di bawah prefix 'admin' atau 'customer'
                            $isCustomerRoute = request()->is('customer/*');
                            
                            // Tentukan nama route berdasarkan konteks
                            $formAction = route('password.email'); // Asumsi admin punya route ini
                            $loginRoute = route('login');
                        @endphp

                        <form action="{{ $formAction }}" method="POST">
                            @csrf
                            <div class="mb-4">
                                <label for="email" class="form-label fw-medium">Alamat Email Terdaftar</label>
                                <input id="email" name="email" type="email" value="{{ old('email') }}" required class="form-control form-control-lg @error('email') is-invalid @enderror" placeholder="contoh@email.com">
                                @error('email')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>

                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-lg-custom">
                                    Kirim Link Reset
                                </button>
                            </div>
                        </form>

                        <div class="text-center mt-4">
                            {{-- ✅ PERBAIKAN: Mengarahkan ke halaman login yang benar --}}
                            <a href="{{ $loginRoute }}" class="text-decoration-none fw-medium">
                                <i class="bi bi-arrow-left me-1"></i> Kembali ke halaman login
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
