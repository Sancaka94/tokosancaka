{{-- resources/views/customer/pesanan/status.blade.php --}}
@extends('layouts.app') {{-- Pastikan ini adalah layout publik atau customer Anda --}}

@section('title', 'Status Pembuatan Pesanan')

@push('styles')
    {{-- Font Awesome dibutuhkan untuk ikon --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
@endpush

@section('content')
<div class="container" style="padding-top: 4rem; padding-bottom: 4rem;">
    <div class="row justify-content-center">
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm text-center p-4">
                <div class="card-body">

                    {{-- KONDISI JIKA PESANAN BERHASIL DIBUAT --}}
                    @if(session('status') == 'success' && session('order'))
                        @php $order = session('order'); @endphp

                        <i class="fas fa-check-circle fa-4x text-success mb-3"></i>
                        <h2 class="fw-bold">Pesanan Berhasil Dibuat!</h2>
                        <p class="text-muted">Terima kasih, pesanan Anda dengan nomor resi di bawah ini akan segera kami proses.</p>
                        
                        <div class="mt-4 p-3 bg-light rounded">
                            <p class="mb-1">Nomor Resi Anda:</p>
                            <h3 class="fw-bold user-select-all text-primary">{{ $order->resi }}</h3>
                        </div>
                        
                        <div class="mt-4 border-top pt-4">
                            <div class="d-grid gap-2">
                                <a href="{{ route('customer.pesanan.index') }}" class="btn btn-primary btn-lg">
                                    <i class="fas fa-table me-2"></i>Lihat Riwayat Pesanan
                                </a>
                                <a href="{{ route('pesanan.customer.create') }}" class="btn btn-outline-secondary">
                                    <i class="fas fa-plus me-2"></i>Buat Pesanan Baru
                                </a>
                            </div>
                        </div>

                    {{-- KONDISI JIKA PESANAN GAGAL (CONTOH: SALDO TIDAK CUKUP) --}}
                    @elseif(session('status') == 'failed')
                        <i class="fas fa-times-circle fa-4x text-danger mb-3"></i>
                        <h2 class="fw-bold">Oops, Pesanan Gagal!</h2>
                        <p class="text-muted">{{ session('message') ?? 'Terjadi kesalahan saat memproses pesanan Anda.' }}</p>

                        <div class="mt-4 border-top pt-4">
                            <div class="d-grid gap-2">
                                {{-- Jika error karena saldo, tampilkan tombol Top Up --}}
                                @if(session('reason') == 'insufficient_balance')
                                    <a href="{{ route('topup.index') }}" class="btn btn-success btn-lg">
                                        <i class="fas fa-wallet me-2"></i>Top Up Saldo Sekarang
                                    </a>
                                @endif
                                <a href="{{ route('pesanan.customer.create') }}" class="btn btn-outline-secondary">
                                    <i class="fas fa-arrow-left me-2"></i>Coba Buat Pesanan Lagi
                                </a>
                            </div>
                        </div>

                    {{-- Tampilan jika halaman diakses tanpa status yang jelas --}}
                    @else
                        <i class="fas fa-info-circle fa-4x text-warning mb-3"></i>
                        <h2 class="fw-bold">Tidak Ada Informasi</h2>
                        <p class="text-muted">Silakan buat pesanan terlebih dahulu untuk melihat statusnya.</p>
                        <a href="{{ route('pesanan.customer.create') }}" class="btn btn-primary mt-3">Buat Pesanan</a>
                    @endif

                </div>
            </div>
        </div>
    </div>
</div>
@endsection
