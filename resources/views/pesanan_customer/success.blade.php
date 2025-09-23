{{-- resources/views/pesanan_customer/success.blade.php --}}



@extends('layouts.app')



@section('title', 'Pesanan Berhasil Dibuat!')



@push('styles')

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

@endpush



@section('content')

<div class="container" style="padding-top: 4rem; padding-bottom: 4rem;">

    <div class="row justify-content-center">

        <div class="col-lg-7">

            <div class="card border-0 shadow-sm text-center p-4">

                <div class="card-body">



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



                        <i class="fas fa-check-circle fa-4x text-success mb-3"></i>

                        <h2 class="fw-bold">Pesanan Berhasil Dibuat!</h2>

                        <h4 class="fw-bold">#{{$order->nomor_invoice}}</h2>

                        <p class="text-muted">Terima kasih telah menggunakan layanan Sancaka Express. Pesanan Anda akan segera kami proses.</p>

                        

                        <div class="mt-4 p-3 bg-light rounded">

                            <p class="mb-1">Nomor Resi Anda:</p>

                            <h3 class="fw-bold user-select-all text-primary">{{ $order->resi }}</h3>

                        </div>

                        <p class="mt-3 small text-muted">Silakan simpan nomor resi ini untuk melacak status pengiriman Anda.</p>



                        {{-- === Tambahan tombol aksi berdasarkan status pesanan === --}}

                        <div class="mt-4">

                            @if ($order->status === 'Menunggu Pembayaran' && $order->payment_url)



                                @php

                                    $virtualAccounts = [

                                        'PERMATAVA','BNIVA','BRIVA','MANDIRIVA','BCAVA','MUAMALATVA',

                                        'CIMBVA','BSIVA','OCBCVA','DANAMONVA','OTHERBANKVA'

                                    ];

                                    $paymentMethod = $order->payment_method ?? ''; // pastikan ada field ini

                                @endphp

                            

                                @if ($paymentMethod === 'QRIS')

                                    {{-- Tampilkan QR Code --}}

                                    <div class="text-center my-3">

                                        <img src="{{ $order->payment_url }}" alt="QRIS Payment" class="img-fluid" style="max-width: 300px;">

                                        <p class="mt-2 text-muted">Scan QRIS untuk melakukan pembayaran</p>

                                    </div>

                            

                                @elseif (in_array($paymentMethod, ['DANA', 'SHOPEEPAY', 'OVO']))

                                    

                            

                                    <a href="{{ $order->payment_url }}" 

                                       class="btn btn-warning btn-lg w-100 mb-2" 

                                       target="_blank">

                                        <i class="fas fa-credit-card me-2"></i> Bayar dengan {{ $paymentMethod }}

                                    </a>

                            

                                @elseif (in_array($paymentMethod, $virtualAccounts))

                                    {{-- Teks saja --}}

                                    <div class="text-center my-3">

                                        <h4 class="fw-bold">Pembayaran via Virtual Account</h4>

                                        <p class="mb-0">{{ $order->payment_url }}</p>

                                    </div>

                            

                                @else

                            

                                    <a href="{{ $order->payment_url }}" 

                                       class="btn btn-warning btn-lg w-100 mb-2" 

                                       target="_blank">

                                        <i class="fas fa-credit-card me-2"></i> Bayar Sekarang

                                    </a>

                                @endif

                            

                            @elseif ($order->status === 'Menunggu Pickup')

                                <a href="https://tokosancaka.com/tracking?resi={{ $order->resi }}" 
                                    target="_blank"
                                    class="btn btn-info btn-lg w-100 mb-2">
                                <i class="fas fa-truck me-2"></i> Lacak Pesanan
                                </a>


                            @endif



                        </div>

                        {{-- === End tombol aksi status === --}}



                        <div class="mt-4 border-top pt-4">

                            <div class="d-grid gap-2">

                                {{-- Kirim via WhatsApp --}}

                                <a href="https://wa.me/{{ $receiverPhone }}?text={{ urlencode($whatsappMessage) }}" target="_blank" class="btn btn-success btn-lg">

                                    <i class="fab fa-whatsapp me-2"></i>Kirim Resi ke WA Penerima

                                </a>



                                <div class="row g-2">

                                    <div class="col">

                                        {{-- Cetak Thermal --}}

                                        <a href="{{ route('admin.pesanan.cetak_thermal', ['resi' => $order->resi]) }}" target="_blank" class="btn btn-primary w-100">

                                            <i class="fas fa-print me-2"></i>Cetak Resi

                                        </a>

                                    </div>

                                    <div class="col">

                                        {{-- Edit via Admin --}}

                                        @php

                                            $waAdmin = '628819435180';

                                            $pesan = "Halo Admin Sancaka,\n\nMohon bantuannya untuk *edit data pesanan* saya karena ada kesalahan input.\n\nBerikut nomor invoice saya:\n*{$order->nomor_invoice}*.\n\nTerima kasih 🙏";

                                            $waLink = "https://wa.me/{$waAdmin}?text=" . urlencode($pesan);

                                        @endphp

                                        <a href="{{ $waLink }}" target="_blank" class="btn btn-secondary w-100">

                                            <i class="fab fa-whatsapp me-2"></i>Edit Pesanan

                                        </a>

                                    </div>

                                </div>



                                <a href="{{ route('home') }}" class="btn btn-link text-muted mt-2">

                                    <i class="fas fa-arrow-left me-2"></i>Kembali ke Beranda

                                </a>

                            </div>

                        </div>

                    @else

                        <i class="fas fa-info-circle fa-4x text-warning mb-3"></i>

                        <h2 class="fw-bold">Tidak Ada Data Pesanan</h2>

                        <p class="text-muted">Sepertinya Anda mengakses halaman ini secara langsung. Silakan buat pesanan terlebih dahulu.</p>

                        <a href="{{ route('home') }}" class="btn btn-primary mt-3">Kembali ke Beranda</a>

                    @endif



                </div>

            </div>

        </div>

    </div>

</div>

@endsection

