@extends('layouts.marketplace')

@section('title', 'Invoice #' . $order->invoice_number)

@section('content')

    {{-- Container Utama --}}
    <div class="bg-gray-100 min-h-screen py-10 px-4 sm:px-6 lg:px-8 font-sans">

        <div class="max-w-5xl mx-auto">

            {{-- Kartu Invoice --}}
            <div class="bg-white rounded-2xl shadow-xl overflow-hidden border border-gray-200">

                {{-- HEADER: Logo & Info Perusahaan --}}
                <div class="bg-gradient-to-r from-gray-50 to-white p-8 border-b border-gray-200">
                    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-6">
                        <div class="flex items-center">
                            {{-- Logo Perusahaan --}}
                            <img src="https://tokosancaka.com/storage/uploads/logo.jpeg"
                                 alt="Logo"
                                 class="h-16 w-16 rounded-xl shadow-sm object-cover mr-5"
                                 onerror="this.style.display='none';">
                            <div>
                                <h1 class="text-2xl font-extrabold text-gray-900 tracking-tight">CV. Sancaka Karya Hutama</h1>
                                <p class="text-sm text-gray-500 mt-1">
                                    <i class="fas fa-map-marker-alt mr-1"></i> JL.DR.WAHIDIN NO.18A, NGAWI
                                </p>
                                <p class="text-sm text-gray-500">
                                    <i class="fas fa-phone mr-1"></i> 085745808809
                                </p>
                            </div>
                        </div>
                        <div class="text-left md:text-right">
                            <span class="bg-blue-100 text-blue-800 text-xs font-bold px-3 py-1 rounded-full uppercase tracking-wide">Invoice</span>
                            <h2 class="text-xl font-bold text-gray-800 mt-2">#{{ $order->invoice_number }}</h2>
                            <p class="text-sm text-gray-500">{{ $order->created_at->translatedFormat('d F Y, H:i') }} WIB</p>
                        </div>
                    </div>
                </div>

                <div class="flex flex-col lg:flex-row">

                    {{-- KOLOM KIRI: Detail Order & Produk --}}
                    <div class="w-full lg:w-3/5 p-8 border-r border-gray-100">

                        {{-- Info Pengiriman --}}
                        <div class="mb-8 p-4 bg-blue-50 rounded-xl border border-blue-100">
                            <h3 class="text-xs font-bold text-blue-600 uppercase tracking-wider mb-2">Dikirim Kepada</h3>
                            <p class="text-base font-bold text-gray-900">{{ $order->user->nama_lengkap ?? 'Pelanggan' }}</p>
                            <p class="text-sm text-gray-600">{{ $order->user->no_wa ?? '-' }}</p>
                            <p class="text-sm text-gray-600 mt-1">{{ $order->shipping_address ?? 'Alamat tidak tersedia' }}</p>
                        </div>

                        {{-- Daftar Item --}}
                        <div class="mb-8">
                            <h3 class="text-sm font-bold text-gray-900 mb-4">Rincian Item</h3>
                            <div class="space-y-4">
                                @foreach($order->items as $item)
                                <div class="flex items-start">
                                    <div class="h-16 w-16 flex-shrink-0 overflow-hidden rounded-lg border border-gray-200 bg-white">
                                        @if($item->product && $item->product->image_url)
                                            <img src="{{ asset('public/storage/'.$item->product->image_url) }}" class="h-full w-full object-cover">
                                        @else
                                            <img src="https://placehold.co/100x100?text=IMG" class="h-full w-full object-cover opacity-50">
                                        @endif
                                    </div>
                                    <div class="ml-4 flex-1">
                                        <p class="text-sm font-semibold text-gray-900">{{ $item->product->name ?? 'Produk dihapus' }}</p>
                                        @if($item->variant)
                                            <p class="text-xs text-gray-500">Varian: {{ $item->variant->name }}</p>
                                        @endif
                                        <p class="text-xs text-gray-500 mt-1">{{ $item->quantity }} x Rp {{ number_format($item->price, 0, ',', '.') }}</p>
                                    </div>
                                    <p class="text-sm font-bold text-gray-900">Rp {{ number_format($item->price * $item->quantity, 0, ',', '.') }}</p>
                                </div>
                                @endforeach
                            </div>
                        </div>

                        {{-- Total Biaya --}}
                        <div class="border-t border-gray-200 pt-4 space-y-2">
                            <div class="flex justify-between text-sm text-gray-600">
                                <span>Subtotal</span>
                                <span>Rp {{ number_format($order->subtotal, 0, ',', '.') }}</span>
                            </div>
                            <div class="flex justify-between text-sm text-gray-600">
                                <span>Ongkir ({{ strtoupper(explode('-', $order->shipping_method)[1] ?? 'Kurir') }})</span>
                                <span>Rp {{ number_format($order->shipping_cost, 0, ',', '.') }}</span>
                            </div>
                            @if($order->cod_fee > 0)
                            <div class="flex justify-between text-sm text-gray-600">
                                <span>Biaya Layanan COD</span>
                                <span>Rp {{ number_format($order->cod_fee, 0, ',', '.') }}</span>
                            </div>
                            @endif
                            @if($order->insurance_cost > 0)
                            <div class="flex justify-between text-sm text-gray-600">
                                <span>Asuransi Pengiriman</span>
                                <span>Rp {{ number_format($order->insurance_cost, 0, ',', '.') }}</span>
                            </div>
                            @endif

                            <div class="flex justify-between text-lg font-bold text-gray-900 pt-4 border-t border-dashed border-gray-300 mt-4">
                                <span>Total Tagihan</span>
                                <span class="text-red-600">Rp {{ number_format($order->total_amount, 0, ',', '.') }}</span>
                            </div>
                        </div>
                    </div>

                    {{-- KOLOM KANAN: INSTRUKSI PEMBAYARAN (CERDAS & DATABASE BASED) --}}
                    <div class="w-full lg:w-2/5 p-8 bg-gray-50">

                        {{-- Status Badge --}}
                        <div class="text-center mb-8">
                            @php
                                $status = strtolower($order->status);
                                $badges = [
                                    // Status Hijau (Lunas/Selesai)
                                    'paid'       => ['bg'=>'bg-green-100', 'text'=>'text-green-800', 'label'=>'LUNAS', 'icon'=>'fa-check-circle'],
                                    'completed'  => ['bg'=>'bg-green-100', 'text'=>'text-green-800', 'label'=>'SELESAI', 'icon'=>'fa-star'],

                                    // Status Biru/Ungu (Proses) -> INI YANG TADI KURANG
                                    'processing' => ['bg'=>'bg-green-100', 'text'=>'text-green-800', 'label'=>'SUDAH DIBAYAR (DIPROSES)', 'icon'=>'fa-box-open'],
                                    'shipped'    => ['bg'=>'bg-purple-100', 'text'=>'text-purple-800', 'label'=>'SEDANG DIKIRIM', 'icon'=>'fa-shipping-fast'],

                                    // Status Kuning (Pending)
                                    'pending'    => ['bg'=>'bg-yellow-100', 'text'=>'text-yellow-800', 'label'=>'MENUNGGU PEMBAYARAN', 'icon'=>'fa-clock'],

                                    // Status Merah/Abu (Gagal)
                                    'failed'     => ['bg'=>'bg-red-100', 'text'=>'text-red-800', 'label'=>'GAGAL', 'icon'=>'fa-times-circle'],
                                    'expired'    => ['bg'=>'bg-gray-200', 'text'=>'text-gray-600', 'label'=>'KADALUARSA', 'icon'=>'fa-hourglass-end'],
                                    'canceled'   => ['bg'=>'bg-red-100', 'text'=>'text-red-800', 'label'=>'DIBATALKAN', 'icon'=>'fa-ban'],
                                ];

                                // Ambil badge sesuai status, jika tidak ada fallback ke pending
                                $current = $badges[$status] ?? $badges['pending'];
                            @endphp

                            <div class="inline-flex items-center px-4 py-2 rounded-lg {{ $current['bg'] }} {{ $current['text'] }}">
                                <i class="fas {{ $current['icon'] }} mr-2"></i>
                                <span class="font-bold text-sm tracking-wide">{{ $current['label'] }}</span>
                            </div>
                        </div>

                        {{-- === LOGIKA TAMPILAN PEMBAYARAN === --}}

                        {{-- 1. JIKA SUDAH LUNAS --}}
                        @if(in_array($status, ['paid', 'processing', 'shipped', 'completed']))
                            <div class="text-center">
                                <div class="mb-4">
                                    <img src="{{ asset('public/assets/success_payment.png') }}" class="h-32 mx-auto object-contain" onerror="this.src='https://placehold.co/200x200?text=LUNAS'">
                                </div>
                                <h3 class="text-lg font-bold text-gray-900">Terima Kasih!</h3>
                                <p class="text-sm text-gray-500 mt-1">Pembayaran telah diterima. Pesanan Anda akan segera diproses.</p>
                                <a href="{{ url('etalase') }}" class="block w-full mt-6 py-3 bg-gray-900 text-white font-bold rounded-xl hover:bg-gray-800 transition">
                                    Belanja Lagi
                                </a>
                            </div>

                        {{-- 2. JIKA COD (BAYAR DITEMPAT) --}}
                        @elseif(in_array($order->payment_method, ['cod', 'CODBARANG']))
                            <div class="bg-white p-6 rounded-xl shadow-sm border border-orange-200 text-center">
                                <div class="bg-orange-50 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                                    <i class="fas fa-hand-holding-usd text-2xl text-orange-600"></i>
                                </div>
                                <h3 class="font-bold text-gray-800 text-lg">Bayar Ditempat (COD)</h3>
                                <p class="text-sm text-gray-600 mt-2">Siapkan uang tunai pas saat kurir tiba.</p>
                                <div class="mt-4 text-2xl font-bold text-orange-600">
                                    Rp {{ number_format($order->total_amount, 0, ',', '.') }}
                                </div>
                            </div>

                        {{-- 3. JIKA DANA DIRECT DEBIT --}}
                        @elseif($order->payment_method === 'DANA')
                            <div class="bg-white p-6 rounded-xl shadow-sm border border-blue-200 text-center">
                                <img src="{{ asset('public/assets/dana.webp') }}" class="h-10 mx-auto mb-4">
                                <p class="text-sm text-gray-600 mb-4">Selesaikan pembayaran via Aplikasi DANA.</p>
                                <a href="{{ $order->payment_url }}" class="block w-full py-3 bg-[#118EEA] hover:bg-[#0b79c9] text-white font-bold rounded-xl shadow-md transition transform hover:-translate-y-1">
                                    Buka Aplikasi DANA
                                </a>
                            </div>

                        {{-- 4. JIKA TRIPAY (DATA DARI DATABASE) --}}
                        {{-- Mengecek apakah kolom pay_code atau qr_url di database terisi --}}
                        @elseif($order->pay_code || $order->qr_url || $order->payment_url)

                            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
                                <div class="text-center mb-6 border-b border-dashed border-gray-200 pb-4">
                                    <p class="text-xs text-gray-400 uppercase tracking-wider mb-1">Metode Pembayaran</p>
                                    <h3 class="font-bold text-gray-800 text-lg">{{ $order->payment_method }}</h3>
                                </div>

                                {{-- A. TAMPILKAN QRIS (Jika kolom qr_url ada isinya) --}}
                                @if(!empty($order->qr_url))
                                    <div class="text-center">
                                        <p class="text-sm text-gray-600 mb-3">Scan QR Code di bawah ini:</p>
                                        <div class="inline-block p-3 border rounded-xl bg-white shadow-inner mb-3">
                                            <img src="{{ $order->qr_url }}" alt="QRIS" class="w-48 h-48 object-contain">
                                        </div>
                                        <p class="text-xs text-green-600 font-medium flex items-center justify-center">
                                            <i class="fas fa-check-circle mr-1"></i> Support Semua E-Wallet
                                        </p>
                                    </div>

                                {{-- B. TAMPILKAN KODE BAYAR / VA (Jika kolom pay_code ada isinya) --}}
                                @elseif(!empty($order->pay_code))
                                    <div class="text-center">
                                        <p class="text-sm text-gray-600 mb-2">Nomor Kode Bayar / VA:</p>

                                        <div class="relative group cursor-pointer" onclick="copyToClipboard('payCode')">
                                            <div class="bg-blue-50 border border-blue-200 p-4 rounded-xl flex items-center justify-center space-x-3 hover:bg-blue-100 transition duration-200">
                                                <span id="payCode" class="text-2xl font-mono font-bold text-blue-700 tracking-wider">
                                                    {{ $order->pay_code }}
                                                </span>
                                                <button class="text-blue-400 hover:text-blue-600 focus:outline-none">
                                                    <i class="far fa-copy text-xl"></i>
                                                </button>
                                            </div>
                                            <p class="text-xs text-blue-600 mt-2 font-medium opacity-0 group-hover:opacity-100 transition">Klik nomor untuk menyalin</p>
                                        </div>

                                        <div class="mt-4 p-3 bg-yellow-50 rounded-lg text-xs text-yellow-800 border border-yellow-100 text-left">
                                            <p class="font-bold mb-1"><i class="fas fa-info-circle mr-1"></i> Penting:</p>
                                            <ul class="list-disc list-inside">
                                                <li>Lakukan transfer sesuai nominal tagihan.</li>
                                                <li>Simpan bukti transfer Anda.</li>
                                                <li>Status akan terupdate otomatis dalam 5-10 menit.</li>
                                            </ul>
                                        </div>
                                    </div>

                                {{-- C. LINK REDIRECT (Jika tidak ada VA/QR, tapi ada Link) --}}
                                @elseif(!empty($order->payment_url))
                                    <div class="text-center">
                                        <p class="text-sm text-gray-600 mb-4">Lanjutkan pembayaran di aplikasi:</p>
                                        <a href="{{ $order->payment_url }}" target="_blank" class="block w-full py-3 bg-red-600 hover:bg-red-700 text-white font-bold rounded-xl shadow-md transition transform hover:-translate-y-1">
                                            Bayar Sekarang <i class="fas fa-external-link-alt ml-2"></i>
                                        </a>
                                    </div>
                                @endif

                                {{-- PANDUAN BAYAR STATIS (Karena data instruksi API tidak disimpan di DB) --}}
                                <div class="mt-6 pt-4 border-t border-dashed border-gray-200 text-center">
                                    <a href="https://tripay.co.id/cara-pembayaran" target="_blank" class="text-xs text-blue-500 hover:text-blue-700 hover:underline flex items-center justify-center">
                                        <i class="fas fa-book-reader mr-2"></i> Lihat Panduan Cara Pembayaran
                                    </a>
                                </div>
                            </div>

                        {{-- 5. FALLBACK (JIKA ERROR) --}}
                        @else
                            <div class="text-center py-8">
                                <p class="text-gray-600 mb-4 text-sm">Gagal memuat data pembayaran. Silakan hubungi admin atau:</p>
                                <a href="{{ $order->payment_url ?? '#' }}" target="_blank" class="block w-full py-3 bg-gray-800 text-white rounded-xl font-bold">
                                    Coba Link Alternatif
                                </a>
                            </div>
                        @endif

                        <div class="mt-8 text-center">
                            <a href="{{ route('checkout.index') }}" class="text-sm text-gray-500 hover:text-gray-900 transition flex items-center justify-center">
                                <i class="fas fa-arrow-left mr-2"></i> Kembali ke Halaman Checkout
                            </a>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- SCRIPT COPY TO CLIPBOARD --}}
    @push('scripts')
    <script>
        function copyToClipboard(elementId) {
            var text = document.getElementById(elementId).innerText.trim();
            navigator.clipboard.writeText(text).then(function() {
                alert('Nomor berhasil disalin: ' + text);
            }, function(err) {
                alert('Gagal menyalin text. Silakan copy manual.');
            });
        }
    </script>
    @endpush

@endsection
