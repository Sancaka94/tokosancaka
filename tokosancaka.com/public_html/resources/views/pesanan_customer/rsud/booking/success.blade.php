@extends('layouts.app')

@section('title', 'Booking Berhasil — Kode Booking Anda')

@section('content')
<div class="container py-5" style="max-width: 640px;">

    {{-- Header Sukses --}}
    <div class="text-center mb-4">
        <div class="mb-3">
            <span style="font-size: 4rem;">💊</span>
        </div>
        <h3 class="fw-bold text-success">Booking Obat Berhasil!</h3>
        <p class="text-muted">
            Simpan kode booking Anda. Admin apotek akan segera memproses obat Anda.
        </p>
    </div>

    @if($order)

    {{-- Kode Booking — Bagian Paling Menonjol --}}
    <div class="card border-0 shadow mb-4" style="background: linear-gradient(135deg, #dc3545, #b02a37); border-radius: 1rem;">
        <div class="card-body text-center py-4">
            <p class="text-white mb-1" style="font-size: 0.9rem; opacity: 0.85;">Kode Booking Anda</p>
            <h2 class="fw-bold text-white mb-3" id="kodeBooking" style="letter-spacing: 2px; font-size: 1.8rem;">
                {{ $order->kode_booking }}
            </h2>
            <button onclick="copyKode()" class="btn btn-light btn-sm rounded-pill px-4">
                <i class="fas fa-copy me-2"></i>Salin Kode
            </button>
        </div>
    </div>

    {{-- Status Pembayaran --}}
    <div class="card border-0 shadow-sm mb-3" style="border-radius: 1rem;">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
                <span class="text-muted">Status Pembayaran</span>
                @if(in_array($order->payment_status, ['Lunas', 'Lunas / COD']))
                    <span class="badge bg-success fs-6 px-3 py-2">
                        <i class="fas fa-check-circle me-1"></i>{{ $order->payment_status }}
                    </span>
                @else
                    <span class="badge bg-warning text-dark fs-6 px-3 py-2">
                        <i class="fas fa-clock me-1"></i>{{ $order->payment_status }}
                    </span>
                @endif
            </div>
            <div class="d-flex justify-content-between align-items-center mt-2">
                <span class="text-muted">Metode Bayar</span>
                <span class="fw-bold">{{ strtoupper($order->payment_method) }}</span>
            </div>
        </div>
    </div>

    {{-- Detail Pesanan --}}
    <div class="card border-0 shadow-sm mb-3" style="border-radius: 1rem;">
        <div class="card-header bg-transparent fw-bold border-bottom">
            <i class="fas fa-box-open text-danger me-2"></i>Detail Pesanan
        </div>
        <div class="card-body">
            <table class="table table-borderless mb-0" style="font-size: 0.92rem;">
                <tr>
                    <td class="text-muted ps-0" style="width: 45%;">Nama Pasien</td>
                    <td class="fw-bold">{{ $order->receiver_name }}</td>
                </tr>
                @if($order->nomor_rm)
                <tr>
                    <td class="text-muted ps-0">Nomor RM</td>
                    <td class="fw-bold">{{ $order->nomor_rm }}</td>
                </tr>
                @endif
                <tr>
                    <td class="text-muted ps-0">No. HP</td>
                    <td>{{ $order->receiver_phone }}</td>
                </tr>
                <tr>
                    <td class="text-muted ps-0">Alamat Kirim</td>
                    <td>{{ $order->receiver_address }}, {{ $order->receiver_district }}, {{ $order->receiver_regency }}</td>
                </tr>
                <tr>
                    <td class="text-muted ps-0">Deskripsi Obat</td>
                    <td>{{ $order->item_description }}</td>
                </tr>
            </table>
        </div>
    </div>

    {{-- Rincian Biaya --}}
    <div class="card border-0 shadow-sm mb-3" style="border-radius: 1rem;">
        <div class="card-header bg-transparent fw-bold border-bottom">
            <i class="fas fa-receipt text-danger me-2"></i>Rincian Biaya
        </div>
        <div class="card-body">
            <table class="table table-borderless mb-0" style="font-size: 0.92rem;">
                <tr>
                    <td class="text-muted ps-0">Ongkir</td>
                    <td class="text-end">Rp {{ number_format($order->shipping_cost, 0, ',', '.') }}</td>
                </tr>
                @if($order->insurance_cost > 0)
                <tr>
                    <td class="text-muted ps-0">Asuransi</td>
                    <td class="text-end">Rp {{ number_format($order->insurance_cost, 0, ',', '.') }}</td>
                </tr>
                @endif
                @if($order->cod_fee > 0)
                <tr>
                    <td class="text-muted ps-0">Biaya COD</td>
                    <td class="text-end">Rp {{ number_format($order->cod_fee, 0, ',', '.') }}</td>
                </tr>
                @endif
                <tr class="border-top">
                    <td class="fw-bold ps-0">Total</td>
                    <td class="text-end fw-bold text-danger fs-5">
                        Rp {{ number_format($order->total_price, 0, ',', '.') }}
                    </td>
                </tr>
            </table>
        </div>
    </div>

    {{-- Alur Selanjutnya --}}
    <div class="card border-0 shadow-sm mb-4" style="border-radius: 1rem; background-color: #f8f9fa;">
        <div class="card-body">
            <h6 class="fw-bold mb-3"><i class="fas fa-route text-primary me-2"></i>Proses Selanjutnya</h6>
            <div class="d-flex mb-2">
                <div class="me-3">
                    <span class="badge bg-success rounded-circle" style="width:28px; height:28px; line-height:28px; font-size:0.75rem;">✓</span>
                </div>
                <div>
                    <strong>Booking Diterima</strong><br>
                    <small class="text-muted">Kode booking Anda sudah tersimpan di sistem.</small>
                </div>
            </div>
            <div class="d-flex mb-2">
                <div class="me-3">
                    <span class="badge bg-primary rounded-circle" style="width:28px; height:28px; line-height:28px; font-size:0.75rem;">2</span>
                </div>
                <div>
                    <strong>Apoteker Meracik Obat</strong><br>
                    <small class="text-muted">Tim apotek RSUD Soeroto sedang menyiapkan obat Anda.</small>
                </div>
            </div>
            <div class="d-flex mb-2">
                <div class="me-3">
                    <span class="badge bg-secondary rounded-circle" style="width:28px; height:28px; line-height:28px; font-size:0.75rem;">3</span>
                </div>
                <div>
                    <strong>Diserahkan ke Kurir</strong><br>
                    <small class="text-muted">Admin akan memanggil ekspedisi dan Anda mendapat nomor resi.</small>
                </div>
            </div>
            <div class="d-flex">
                <div class="me-3">
                    <span class="badge bg-secondary rounded-circle" style="width:28px; height:28px; line-height:28px; font-size:0.75rem;">4</span>
                </div>
                <div>
                    <strong>Obat Dikirim</strong><br>
                    <small class="text-muted">Lacak pengiriman dengan nomor resi yang akan dikirim via WhatsApp.</small>
                </div>
            </div>
        </div>
    </div>

    {{-- Tombol --}}
    <div class="d-grid gap-2">
        <a href="{{ route('rsud.pesanan.create') }}" class="btn btn-danger btn-lg rounded-pill">
            <i class="fas fa-plus me-2"></i>Buat Booking Baru
        </a>
        <a href="https://wa.me/6285745808809?text={{ urlencode('Halo, saya ingin tanya status booking obat saya. Kode Booking: ' . $order->kode_booking) }}"
           class="btn btn-outline-success rounded-pill" target="_blank">
            <i class="fab fa-whatsapp me-2"></i>Tanya Status via WhatsApp
        </a>
    </div>

    @else
    <div class="alert alert-warning text-center">
        <i class="fas fa-exclamation-triangle me-2"></i>
        Data pesanan tidak ditemukan.
        <a href="{{ route('rsud.pesanan.create') }}" class="alert-link">Buat pesanan baru.</a>
    </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
function copyKode() {
    const kode = document.getElementById('kodeBooking').innerText.trim();
    navigator.clipboard.writeText(kode).then(() => {
        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'success',
            title: 'Kode booking disalin!',
            showConfirmButton: false,
            timer: 2000
        });
    });
}
</script>
@endpush