@extends('layouts.app')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card shadow-sm border-0 rounded-3">
                <div class="card-header bg-primary text-white text-center py-3">
                    <h5 class="mb-0 fw-bold">Verifikasi Kode OTP</h5>
                </div>

                <div class="card-body p-4 p-md-5">
                    <div class="text-center mb-4">
                        <i class="fas fa-shield-alt fa-3x text-primary mb-3"></i>
                        <p class="text-muted">
                            Pendaftaran berhasil! Kami telah mengirimkan 6 karakter kode OTP ke nomor WhatsApp Anda. Silakan masukkan kode tersebut di bawah ini.
                        </p>
                    </div>

                    {{-- Notifikasi Error --}}
                    @if (session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-circle me-2"></i> {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    {{-- Notifikasi Info/Success --}}
                    @if (session('info'))
                        <div class="alert alert-info alert-dismissible fade show" role="alert">
                            <i class="fas fa-info-circle me-2"></i> {{ session('info') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('customer.otp.process') }}">
                        @csrf

                        <div class="mb-4">
                            <label for="otp" class="form-label fw-bold text-secondary">Masukkan 6 Karakter OTP</label>
                            <input type="text"
                                   class="form-control form-control-lg text-center @error('otp') is-invalid @enderror"
                                   id="otp"
                                   name="otp"
                                   value="{{ old('otp') }}"
                                   maxlength="6"
                                   placeholder="Contoh: A9X2B1"
                                   required
                                   autofocus
                                   autocomplete="off"
                                   style="text-transform: uppercase; letter-spacing: 8px; font-weight: bold; font-size: 1.5rem;">

                            @error('otp')
                                <div class="invalid-feedback text-center fw-bold mt-2">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-lg fw-bold">
                                Verifikasi Sekarang
                            </button>
                        </div>
                    </form>
                </div>

                <div class="card-footer text-center py-3 bg-light border-0 rounded-bottom-3">
                    <small class="text-muted">
                        Mengalami kendala? <a href="https://wa.me/628819435180" target="_blank" class="text-decoration-none fw-bold">Hubungi Admin</a>
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
