@extends('layouts.app')

@section('content')
<div class="min-vh-100 d-flex align-items-center justify-content-center py-5" style="background-color: #f8f9fa;">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-12 col-md-8 col-lg-6 col-xl-5">
                <div class="card border-0 shadow-sm rounded-4 p-4 p-sm-5 text-center">
                    
                    {{-- Ikon Peringatan --}}
                    <div class="mb-3 text-danger">
                        {{-- Jika Anda menggunakan Bootstrap Icons, aktifkan baris di bawah ini --}}
                        {{-- <i class="bi bi-exclamation-triangle-fill" style="font-size: 4rem;"></i> --}}
                        
                        {{-- Jika tidak ada ikon, gunakan teks/emoji standar --}}
                        <span style="font-size: 4rem;">⚠️</span>
                    </div>

                    <h2 class="h3 fw-bold text-danger mb-3">Akun Dibekukan</h2>
                    
                    <p class="text-secondary mb-3">
                        Mohon maaf, akses ke akun Anda saat ini <strong>ditolak</strong> karena status akun Anda telah dibekukan akibat aktivitas yang tidak sesuai dengan kebijakan kami.
                    </p>

                    <p class="text-secondary mb-4">
                        Silakan hubungi Admin Sancaka untuk informasi lebih lanjut atau untuk melakukan proses pemulihan akun Anda.
                    </p>

                    <a href="https://wa.me/6285745808809" target="_blank" class="btn btn-success btn-lg w-100 fw-semibold mb-3 rounded-3 shadow-sm">
                        {{-- <i class="bi bi-whatsapp me-2"></i> --}}
                        Hubungi Admin (085 745 808 809)
                    </a>

                    <div class="mt-2">
                        <a href="{{ route('login') }}" class="text-decoration-none text-muted small">
                            Kembali ke Halaman Login
                        </a>
                    </div>
                    
                </div>
            </div>
        </div>
    </div>
</div>
@endsection