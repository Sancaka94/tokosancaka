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

                        {{-- Info Pengiriman (CERDAS) --}}
                        @php
                            // Cek apakah pengiriman ini murni digital
                            $isPureDigitalShipping = str_contains(strtolower($order->shipping_method), 'digital');
                        @endphp

                        <div class="mb-8 p-4 bg-blue-50 rounded-xl border border-blue-100">
                            <h3 class="text-xs font-bold text-blue-600 uppercase tracking-wider mb-2">
                                {{ $isPureDigitalShipping ? 'Informasi Penerima' : 'Dikirim Kepada' }}
                            </h3>
                            <p class="text-base font-bold text-gray-900">{{ $order->user->nama_lengkap ?? ($order->receiver_name ?? 'Pelanggan') }}</p>
                            <p class="text-sm text-gray-600">{{ $order->user->no_wa ?? ($order->receiver_phone ?? '-') }}</p>
                            <p class="text-sm text-gray-600 mt-1">{{ $order->shipping_address ?? 'Alamat tidak tersedia' }}</p>

                            @if(in_array(strtolower($order->status), ['paid', 'processing', 'shipped', 'completed']))
                                <div class="pt-3 border-t border-blue-200 mt-3">
                                    @if($isPureDigitalShipping)
                                        <p class="text-sm text-gray-600">Metode: <span class="font-bold text-green-700">Pengiriman Otomatis (Sistem)</span></p>
                                    @else
                                        @php
                                            $kurirParts = explode('-', $order->shipping_method);
                                            $namaKurir = strtoupper(($kurirParts[1] ?? 'KURIR') . ' - ' . ($kurirParts[2] ?? ''));
                                        @endphp
                                        <p class="text-sm text-gray-600">Ekspedisi: <span class="font-bold text-gray-900">{{ $namaKurir }}</span></p>

                                        <div class="mt-1 flex items-center">
                                            <span class="text-sm text-gray-600 mr-2">No. Resi:</span>
                                            @php $nomorResi = $order->shipping_reference ?? $order->resi ?? null; @endphp

                                            @if(!empty($nomorResi) && $nomorResi !== '-' && $nomorResi !== 'Menunggu Penjual')
                                                <span class="px-2 py-1 bg-white border border-blue-300 text-blue-700 font-mono font-bold rounded text-xs select-all">
                                                    {{ $nomorResi }}
                                                </span>
                                            @else
                                                <span class="px-2 py-1 bg-yellow-100 text-yellow-700 text-xs font-semibold rounded italic">
                                                    Menunggu update kurir...
                                                </span>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            @endif
                        </div>

                        {{-- Daftar Item (CERDAS) --}}
                        <div class="mb-8">
                            <h3 class="text-sm font-bold text-gray-900 mb-4">Rincian Item</h3>
                            <div class="space-y-6">
                                @foreach($order->items as $item)
                                @php
                                    // Cek apakah item ini adalah produk digital
                                    $katObj = $item->product ? $item->product->category()->first() : null;
                                    $isItemDigital = ($katObj && in_array($katObj->category_group, ['produk_digital', 'jasa'])) || str_contains(strtolower($order->shipping_method), 'digital');
                                @endphp

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

                                        {{-- 🔥 AREA AKSES DIGITAL (CERDAS) 🔥 --}}
                                        @if($isItemDigital && in_array(strtolower($order->status), ['paid', 'processing', 'completed', 'shipped']))
                                            @php
                                                $aksesData = null;
                                                $aksesTipe = null;

                                                // Cari URL
                                                if (!empty($item->product->digital_url)) {
                                                    $aksesData = $item->product->digital_url;
                                                    $aksesTipe = 'url';
                                                } 
                                                // Cari File Download
                                                elseif (!empty($item->product->digital_file_path)) {
                                                    $aksesData = asset('public/storage/' . $item->product->digital_file_path);
                                                    $aksesTipe = 'file';
                                                } 
                                                // Cari Serial Number / Teks 
                                                // (Sistem checkout Anda melempar SN ke shipping_reference order)
                                                elseif (!empty($order->shipping_reference) && !str_contains($order->shipping_reference, 'Menunggu') && $isPureDigitalShipping) {
                                                    $aksesData = $order->shipping_reference;
                                                    $aksesTipe = filter_var($aksesData, FILTER_VALIDATE_URL) ? 'url' : 'text';
                                                }
                                            @endphp

                                            <div class="mt-3">
                                                @if($aksesData)
                                                    <div class="p-3 bg-green-50 border border-green-200 rounded-lg inline-block w-full">
                                                        <p class="text-xs font-bold text-green-800 mb-2"><i class="fas fa-key mr-1"></i> Akses Produk Digital:</p>
                                                        
                                                        @if($aksesTipe === 'url' || $aksesTipe === 'file')
                                                            <a href="{{ $aksesData }}" target="_blank" class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-xs font-bold rounded-md shadow-sm transition">
                                                                <i class="fas {{ $aksesTipe === 'file' ? 'fa-download' : 'fa-external-link-alt' }} mr-2"></i> {{ $aksesTipe === 'file' ? 'Download File' : 'Buka Tautan Akses' }}
                                                            </a>
                                                        @else
                                                            <div class="flex items-center gap-2">
                                                                <code class="px-3 py-1.5 bg-white border border-green-300 text-green-800 font-mono text-sm rounded shadow-sm select-all">{{ $aksesData }}</code>
                                                                <span class="text-[10px] text-green-600 italic">Copy teks di atas</span>
                                                            </div>
                                                        @endif
                                                    </div>
                                                @elseif($order->shipping_reference === 'Menunggu Penjual' || strtolower($order->status) === 'processing')
                                                    <div class="p-2 bg-yellow-50 border border-yellow-200 rounded-md inline-block w-full">
                                                        <p class="text-xs text-yellow-700 font-medium"><i class="fas fa-clock mr-1"></i> Menunggu penjual mengunggah file/akses.</p>
                                                    </div>
                                                @endif
                                            </div>
                                        @endif
                                        {{-- 🔥 AKHIR AREA DIGITAL 🔥 --}}

                                    </div>
                                    <p class="text-sm font-bold text-gray-900 whitespace-nowrap">Rp {{ number_format($item->price * $item->quantity, 0, ',', '.') }}</p>
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
                            @if($order->shipping_cost > 0)
                            <div class="flex justify-between text-sm text-gray-600">
                                <span>Ongkir ({{ strtoupper(explode('-', $order->shipping_method)[1] ?? 'Kurir') }})</span>
                                <span>Rp {{ number_format($order->shipping_cost, 0, ',', '.') }}</span>
                            </div>
                            @endif
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

                    {{-- KOLOM KANAN: INSTRUKSI PEMBAYARAN --}}
                    <div class="w-full lg:w-2/5 p-8 bg-gray-50">

                        {{-- Status Badge --}}
                        <div class="text-center mb-8">
                            @php
                                $status = strtolower($order->status);
                                $badges = [
                                    'paid'       => ['bg'=>'bg-green-100', 'text'=>'text-green-800', 'label'=>'LUNAS', 'icon'=>'fa-check-circle'],
                                    'completed'  => ['bg'=>'bg-green-100', 'text'=>'text-green-800', 'label'=>'SELESAI', 'icon'=>'fa-star'],
                                    'processing' => ['bg'=>'bg-green-100', 'text'=>'text-green-800', 'label'=>'SUDAH DIBAYAR (DIPROSES)', 'icon'=>'fa-box-open'],
                                    'shipped'    => ['bg'=>'bg-purple-100', 'text'=>'text-purple-800', 'label'=>'SEDANG DIKIRIM', 'icon'=>'fa-shipping-fast'],
                                    'pending'    => ['bg'=>'bg-yellow-100', 'text'=>'text-yellow-800', 'label'=>'MENUNGGU PEMBAYARAN', 'icon'=>'fa-clock'],
                                    'failed'     => ['bg'=>'bg-red-100', 'text'=>'text-red-800', 'label'=>'GAGAL', 'icon'=>'fa-times-circle'],
                                    'expired'    => ['bg'=>'bg-gray-200', 'text'=>'text-gray-600', 'label'=>'KADALUARSA', 'icon'=>'fa-hourglass-end'],
                                    'canceled'   => ['bg'=>'bg-red-100', 'text'=>'text-red-800', 'label'=>'DIBATALKAN', 'icon'=>'fa-ban'],
                                ];
                                $current = $badges[$status] ?? $badges['pending'];
                            @endphp

                            <div class="inline-flex items-center px-4 py-2 rounded-lg {{ $current['bg'] }} {{ $current['text'] }}">
                                <i class="fas {{ $current['icon'] }} mr-2"></i>
                                <span class="font-bold text-sm tracking-wide">{{ $current['label'] }}</span>
                            </div>
                        </div>

                        {{-- Logika Tampilan Pembayaran (Dipertahankan sama persis) --}}
                        @if(in_array($status, ['paid', 'processing', 'shipped', 'completed']))
                            <div class="text-center">
                                <div class="mb-4">
                                    <img src="{{ asset('public/assets/success_payment.png') }}" class="h-32 mx-auto object-contain" onerror="this.src='https://placehold.co/200x200?text=LUNAS'">
                                </div>
                                <h3 class="text-lg font-bold text-gray-900">Terima Kasih!</h3>
                                <p class="text-sm text-gray-500 mt-1">Pembayaran telah diterima. Pesanan Anda sedang diproses.</p>
                                <a href="{{ url('etalase') }}" class="block w-full mt-6 py-3 bg-gray-900 text-white font-bold rounded-xl hover:bg-gray-800 transition">
                                    Belanja Lagi
                                </a>

                                <a href="{{ url('invoice/' . $order->invoice_number . '/pdf') }}" target="_blank" class="block w-full mt-3 py-3 bg-white border-2 border-gray-200 text-gray-700 font-bold rounded-xl shadow-sm hover:bg-gray-50 hover:border-gray-300 transition flex items-center justify-center">
                                    <i class="fas fa-file-pdf text-red-500 mr-2 text-lg"></i> Download Invoice (PDF)
                                </a>
                            </div>

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

                        @elseif($order->payment_method === 'DANA')
                            <div class="bg-white p-6 rounded-xl shadow-sm border border-blue-200 text-center">
                                <img src="{{ asset('public/assets/dana.webp') }}" class="h-10 mx-auto mb-4">
                                <p class="text-sm text-gray-600 mb-4">Selesaikan pembayaran via Aplikasi DANA.</p>
                                <a href="{{ $order->payment_url }}" class="block w-full py-3 bg-[#118EEA] hover:bg-[#0b79c9] text-white font-bold rounded-xl shadow-md transition transform hover:-translate-y-1">
                                    Buka Aplikasi DANA
                                </a>
                            </div>

                        @elseif($order->pay_code || $order->qr_url || $order->payment_url)
                            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
                                <div class="text-center mb-6 border-b border-dashed border-gray-200 pb-4">
                                    <p class="text-xs text-gray-400 uppercase tracking-wider mb-1">Metode Pembayaran</p>
                                    <h3 class="font-bold text-gray-800 text-lg">{{ $order->payment_method }}</h3>
                                </div>

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
                                                <li>Simpan bukti transfer.</li>
                                                <li>Status akan terupdate otomatis dalam 5-10 menit.</li>
                                            </ul>
                                        </div>
                                    </div>
                                @elseif(!empty($order->payment_url))
                                    <div class="text-center">
                                        <p class="text-sm text-gray-600 mb-4">Lanjutkan pembayaran di aplikasi:</p>
                                        <a href="{{ $order->payment_url }}" target="_blank" class="block w-full py-3 bg-red-600 hover:bg-red-700 text-white font-bold rounded-xl shadow-md transition transform hover:-translate-y-1">
                                            Bayar Sekarang <i class="fas fa-external-link-alt ml-2"></i>
                                        </a>
                                    </div>
                                @endif
                                <div class="mt-6 pt-4 border-t border-dashed border-gray-200 text-center">
                                    <a href="https://tripay.co.id/cara-pembayaran" target="_blank" class="text-xs text-blue-500 hover:text-blue-700 hover:underline flex items-center justify-center">
                                        <i class="fas fa-book-reader mr-2"></i> Lihat Panduan Cara Pembayaran
                                    </a>
                                </div>
                            </div>
                        @else
                            <div class="text-center py-8">
                                <p class="text-gray-600 mb-4 text-sm">Gagal memuat data pembayaran. Silakan hubungi admin atau:</p>
                                <a href="{{ $order->payment_url ?? 'https://wa.me/6285745808809' }}" target="_blank" class="block w-full py-3 bg-gray-800 text-white rounded-xl font-bold">
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