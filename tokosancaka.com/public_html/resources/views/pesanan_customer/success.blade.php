@extends('layouts.app')

@section('content')
<div class="container py-5">

    <div class="row justify-content-center">
        <div class="col-lg-7">

            <div class="card border-0 shadow-lg rounded-4 overflow-hidden">
                <div class="card-body text-center px-4 py-5">

                    @if(session('order'))
                        @php
                            $order = session('order');

                            // Format nomor WA penerima
                            $receiverPhone = preg_replace('/[^0-9]/', '', $order->receiver_phone ?? '');
                            if (substr($receiverPhone, 0, 1) === '0') {
                                $receiverPhone = '62' . substr($receiverPhone, 1);
                            }

                            $trackingLink = route('tracking.index') . '?resi=' . $order->resi;
                            $whatsappMessage = "Halo Kak *{$order->receiver_name}*,\n\nKabar baik! Paket untuk Anda dari *{$order->sender_name}* telah kami terima dan akan segera diproses. 📦✨\n\nBerikut adalah detail pengiriman Anda:\nNomor Resi: *{$order->resi}*\n\nAnda dapat melacak posisi paket Anda secara real-time melalui link di bawah ini:\n{$trackingLink}\n\nTerima kasih telah mempercayakan pengiriman Anda kepada Sancaka Express! 🙏";
                        @endphp

                        {{-- Icon sukses --}}
                        <div class="mb-4">
                            <i class="fas fa-check-circle fa-5x text-success"></i>
                        </div>

                        <h2 class="fw-bold text-dark mb-2">Pesanan Berhasil Dibuat!</h2>
                        <h4 class="fw-bold text-primary">#{{ $order->nomor_invoice }}</h4>
                        <p class="text-muted mb-4">
                            Terima kasih telah menggunakan layanan <strong>Sancaka Express</strong>. Pesanan Anda akan segera kami proses.
                        </p>

                        {{-- Nomor resi --}}
                        <div class="bg-light p-4 rounded-3 shadow-sm mb-3">
                            <p class="mb-1 text-secondary">Nomor Resi Anda:</p>
                            <h3 class="fw-bold text-primary user-select-all mb-0">
                                {{ $order->resi ?: '-' }}
                            </h3>
                        </div>
                        <p class="small text-muted">Simpan nomor resi ini untuk melacak status pengiriman Anda.</p>

                        {{-- Tombol aksi utama --}}
                        <div class="mt-4">
                            @if ($order->status === 'Menunggu Pembayaran' && $order->payment_url)

                                @php
                                    $virtualAccounts = [
                                        'PERMATAVA','BNIVA','BRIVA','MANDIRIVA','BCAVA','MUAMALATVA',
                                        'CIMBVA','BSIVA','OCBCVA','DANAMONVA','OTHERBANKVA'
                                    ];
                                    $paymentMethod = $order->payment_method ?? '';
                                @endphp

                                @if ($paymentMethod === 'QRIS')
                                    <div class="text-center my-3">
                                        <img src="{{ $order->payment_url }}" alt="QRIS Payment" class="img-fluid shadow-sm rounded" style="max-width: 280px;">
                                        <p class="mt-2 text-muted">Scan QRIS untuk melakukan pembayaran</p>
                                    </div>

                                @elseif (in_array($paymentMethod, ['DANA', 'SHOPEEPAY', 'OVO']))
                                    <a href="{{ $order->payment_url }}" target="_blank" 
                                       class="btn btn-warning btn-lg w-100 mb-2 shadow-sm">
                                        <i class="fas fa-credit-card me-2"></i> Bayar dengan {{ $paymentMethod }}
                                    </a>

                                @elseif (in_array($paymentMethod, $virtualAccounts))
                                    <div class="alert alert-info text-start my-3">
                                        <h5 class="fw-bold mb-1">Pembayaran via Virtual Account</h5>
                                        <p class="mb-0 small">{{ $order->payment_url }}</p>
                                    </div>

                                @else
                                    <a href="{{ $order->payment_url }}" target="_blank" 
                                       class="btn btn-warning btn-lg w-100 mb-2 shadow-sm">
                                        <i class="fas fa-credit-card me-2"></i> Bayar Sekarang
                                    </a>
                                @endif

                            @elseif ($order->status === 'Menunggu Pickup')

                                @if (!empty($order->resi))
                                    <a href="https://tokosancaka.com/tracking?resi={{ $order->resi }}" 
                                       target="_blank"
                                       class="btn btn-info btn-lg w-100 mb-2 shadow-sm">
                                        <i class="fas fa-truck me-2"></i> Lacak Pesanan
                                    </a>
                                @else
                                    <a href="https://tokosancaka.com/tracking?resi={{ $order->nomor_invoice }}" 
                                       target="_blank"
                                       class="btn btn-secondary btn-lg w-100 mb-2 shadow-sm">
                                        <i class="fas fa-file-invoice me-2"></i> Lacak Pesanan (Invoice)
                                    </a>
                                @endif

                            @endif
                        </div>

                        {{-- Tombol tambahan --}}
                        <div class="mt-5 border-top pt-4">
                            <div class="d-grid gap-3">

                                <a href="https://wa.me/{{ $receiverPhone }}?text={{ urlencode($whatsappMessage) }}" 
                                   target="_blank" 
                                   class="btn btn-success btn-lg shadow-sm">
                                    <i class="fab fa-whatsapp me-2"></i> Kirim Resi ke WA Penerima
                                </a>

                                <div class="row g-3">
                                    <div class="col-md-6">
                                        @if (!empty($order->resi))
                                            <a href="{{ route('cetak_thermal', ['resi' => $order->resi]) }}" 
                                               target="_blank" 
                                               class="btn btn-primary w-100 shadow-sm">
                                                <i class="fas fa-print me-2"></i> Cetak Resi
                                            </a>
                                        @else
                                            <button class="btn btn-primary w-100 shadow-sm" disabled>
                                                <i class="fas fa-print me-2"></i> Cetak Resi (Belum tersedia)
                                            </button>
                                        @endif
                                    </div>
                                    <div class="col-md-6">
                                        @php
                                            $waAdmin = '628819435180';
                                            $pesan = "Halo Admin Sancaka,\n\nMohon bantuannya untuk *edit data pesanan* saya karena ada kesalahan input.\n\nBerikut nomor invoice saya:\n*{$order->nomor_invoice}*.\n\nTerima kasih 🙏";
                                            $waLink = "https://wa.me/{$waAdmin}?text=" . urlencode($pesan);
                                        @endphp
                                        <a href="{{ $waLink }}" target="_blank" 
                                           class="btn btn-secondary w-100 shadow-sm">
                                            <i class="fab fa-whatsapp me-2"></i> Edit Pesanan
                                        </a>
                                    </div>
                                </div>

                                <a href="{{ route('home') }}" 
                                   class="btn btn-link text-muted mt-3">
                                    <i class="fas fa-arrow-left me-2"></i> Kembali ke Beranda
                                </a>
                            </div>
                        </div>

                    @else
                        <div class="mb-4">
                            <i class="fas fa-info-circle fa-5x text-warning"></i>
                        </div>
                        <h2 class="fw-bold">Tidak Ada Data Pesanan</h2>
                        <p class="text-muted">Sepertinya Anda mengakses halaman ini secara langsung. Silakan buat pesanan terlebih dahulu.</p>
                        <a href="{{ route('home') }}" class="btn btn-primary mt-3 shadow-sm">Kembali ke Beranda</a>
                    @endif

                </div>
            </div>
        </div>
    </div>
</div>
@endsection
