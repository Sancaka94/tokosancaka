@extends('layouts.marketplace')

@section('title', 'Invoice Pesanan - ' . ($order->invoice_number ?? ''))

{{-- Tambahkan script AlpineJS untuk interaktivitas (Accordion Cara Bayar) --}}
@push('scripts')
<script src="//unpkg.com/alpinejs" defer></script>
<script>
    function copyToClipboard(elementId) {
        var text = document.getElementById(elementId).innerText;
        navigator.clipboard.writeText(text).then(function() {
            alert("Nomor berhasil disalin!");
        }, function(err) {
            alert('Gagal menyalin: ' + err);
        });
    }
</script>
@endpush

@section('content')

    {{-- Main container --}}
    <div class="bg-gray-100 min-h-screen flex items-center justify-center p-4 sm:p-6 font-sans">

        {{-- Invoice Card --}}
        <div class="bg-white rounded-xl shadow-lg w-full max-w-5xl overflow-hidden border border-gray-200">

            {{-- Header Section --}}
            <div class="p-6 border-b border-gray-100 bg-gray-50">
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                    {{-- Company Branding --}}
                    <div class="flex items-center">
                        <img src="https://tokosancaka.biz.id/storage/uploads/logo.jpeg" alt="Logo" class="h-14 w-14 mr-4 rounded-lg object-cover shadow-sm" onerror="this.style.display='none';">
                        <div>
                            <h2 class="text-lg font-bold text-gray-800 tracking-tight">CV. Sancaka Karya Hutama</h2>
                            <p class="text-xs text-gray-500 max-w-sm leading-tight mt-1">
                                JL.DR.WAHIDIN NO.18A KEL.KETANGGI KEC.NGAWI 63211
                            </p>
                            <p class="text-xs text-gray-500 mt-1"><i class="fas fa-phone-alt mr-1"></i> 085745808809</p>
                        </div>
                    </div>
                    {{-- Invoice Details --}}
                    <div class="text-left sm:text-right">
                        <span class="bg-blue-100 text-blue-800 text-xs font-semibold px-2.5 py-0.5 rounded uppercase tracking-wide">Invoice</span>
                        <h1 class="text-xl font-bold text-gray-900 mt-1">#{{ $order->invoice_number }}</h1>
                        <p class="text-xs text-gray-500">{{ $order->created_at->translatedFormat('d F Y, H:i') }} WIB</p>
                    </div>
                </div>
            </div>

            {{-- Main Content Section --}}
            <div class="flex flex-col lg:flex-row">

                {{-- Left Column: Order Details --}}
                <div class="w-full lg:w-3/5 p-8 border-r border-gray-100">

                    {{-- Penerima --}}
                    <div class="mb-8">
                        <h2 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">Penerima</h2>
                        <div class="flex items-start">
                            <div class="bg-blue-50 p-2 rounded-lg mr-3">
                                <i class="fas fa-map-marker-alt text-blue-600"></i>
                            </div>
                            <div>
                                <p class="text-sm font-bold text-gray-900">{{ $order->user->nama_lengkap ?? 'Pelanggan' }}</p>
                                <p class="text-sm text-gray-600 mt-0.5">{{ $order->user->no_wa ?? '-' }}</p>
                                <p class="text-sm text-gray-600 mt-1 leading-relaxed">{{ $order->shipping_address ?? '-' }}</p>
                            </div>
                        </div>
                    </div>

                    {{-- Daftar Produk --}}
                    <div class="mb-8">
                        <h2 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">Item Pesanan</h2>
                        <div class="space-y-4">
                            @foreach($order->items as $item)
                            <div class="flex items-center p-3 bg-gray-50 rounded-lg border border-gray-100">
                                <div class="h-12 w-12 flex-shrink-0 overflow-hidden rounded bg-white border border-gray-200">
                                    @if($item->product && $item->product->image_url)
                                        <img src="{{ asset('public/storage/'.$item->product->image_url) }}" class="h-full w-full object-cover">
                                    @else
                                        <img src="https://placehold.co/64x64?text=IMG" class="h-full w-full object-cover">
                                    @endif
                                </div>
                                <div class="ml-4 flex-1">
                                    <h4 class="text-sm font-semibold text-gray-900">{{ $item->product->name ?? 'Produk Dihapus' }}</h4>
                                    @if($item->variant)
                                        <p class="text-xs text-gray-500">Var: {{ $item->variant->name ?? '-' }}</p>
                                    @endif
                                </div>
                                <div class="text-right">
                                    <p class="text-xs text-gray-500">{{ $item->quantity }} x Rp {{ number_format($item->price, 0, ',', '.') }}</p>
                                    <p class="text-sm font-bold text-gray-900">Rp {{ number_format($item->price * $item->quantity, 0, ',', '.') }}</p>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- Rincian Biaya --}}
                    <div class="border-t border-gray-100 pt-4">
                        <div class="flex justify-between text-sm text-gray-600 mb-2">
                            <span>Subtotal Produk</span>
                            <span>Rp {{ number_format($order->subtotal, 0, ',', '.') }}</span>
                        </div>
                        <div class="flex justify-between text-sm text-gray-600 mb-2">
                            <span>Ongkos Kirim</span>
                            <span>Rp {{ number_format($order->shipping_cost, 0, ',', '.') }}</span>
                        </div>
                        @if($order->insurance_cost > 0)
                        <div class="flex justify-between text-sm text-gray-600 mb-2">
                            <span>Asuransi</span>
                            <span>Rp {{ number_format($order->insurance_cost, 0, ',', '.') }}</span>
                        </div>
                        @endif
                        @if($order->cod_fee > 0)
                        <div class="flex justify-between text-sm text-gray-600 mb-2">
                            <span>Biaya Layanan (COD)</span>
                            <span>Rp {{ number_format($order->cod_fee, 0, ',', '.') }}</span>
                        </div>
                        @endif

                        <div class="flex justify-between text-lg font-bold text-gray-900 mt-4 pt-4 border-t border-dashed border-gray-200">
                            <span>Total Tagihan</span>
                            <span class="text-red-600">Rp {{ number_format($order->total_amount, 0, ',', '.') }}</span>
                        </div>
                    </div>
                </div>

                {{-- Right Column: Payment Instructions (SMART INVOICE LOGIC) --}}
                <div class="w-full lg:w-2/5 p-8 bg-gray-50">

                    {{-- Status Badge --}}
                    <div class="mb-6 text-center">
                        @php
                            $statusLabels = [
                                'pending' => ['bg' => 'bg-yellow-100', 'text' => 'text-yellow-800', 'label' => 'Menunggu Pembayaran'],
                                'paid' => ['bg' => 'bg-green-100', 'text' => 'text-green-800', 'label' => 'Lunas'],
                                'processing' => ['bg' => 'bg-blue-100', 'text' => 'text-blue-800', 'label' => 'Diproses'],
                                'shipped' => ['bg' => 'bg-purple-100', 'text' => 'text-purple-800', 'label' => 'Dikirim'],
                                'completed' => ['bg' => 'bg-green-100', 'text' => 'text-green-800', 'label' => 'Selesai'],
                                'expired' => ['bg' => 'bg-gray-200', 'text' => 'text-gray-600', 'label' => 'Kadaluarsa'],
                                'failed' => ['bg' => 'bg-red-100', 'text' => 'text-red-800', 'label' => 'Gagal'],
                            ];
                            $currStatus = $statusLabels[$order->status] ?? $statusLabels['pending'];
                        @endphp
                        <span class="{{ $currStatus['bg'] }} {{ $currStatus['text'] }} px-4 py-1.5 rounded-full text-xs font-bold uppercase tracking-wider">
                            {{ $currStatus['label'] }}
                        </span>
                    </div>

                    {{-- LOGIKA UTAMA TAMPILAN PEMBAYARAN --}}

                    {{-- 1. JIKA SUDAH LUNAS --}}
                    @if(in_array($order->status, ['paid', 'processing', 'shipped', 'completed']))
                        <div class="text-center py-10">
                            <div class="mx-auto flex items-center justify-center h-20 w-20 rounded-full bg-green-100 mb-4 animate-bounce">
                                <i class="fas fa-check text-3xl text-green-600"></i>
                            </div>
                            <h3 class="text-xl font-bold text-gray-900">Pembayaran Berhasil!</h3>
                            <p class="text-sm text-gray-500 mt-2">Terima kasih, pesanan Anda sedang kami proses.</p>
                            <a href="{{ url('etalase') }}" class="inline-block mt-6 px-6 py-2 bg-gray-900 text-white rounded-lg hover:bg-gray-800 transition shadow-lg text-sm font-medium">
                                Lanjut Belanja
                            </a>
                        </div>

                    {{-- 2. JIKA COD --}}
                    @elseif(in_array($order->payment_method, ['cod', 'CODBARANG']))
                        <div class="text-center bg-white p-6 rounded-xl shadow-sm border border-orange-200">
                            <img src="{{ asset('public/assets/cod.png') }}" class="h-16 mx-auto mb-4 object-contain">
                            <h3 class="font-bold text-gray-900">Bayar Ditempat (COD)</h3>
                            <p class="text-sm text-gray-600 mt-2">Siapkan uang tunai pas saat kurir tiba.</p>
                            <div class="mt-4 p-3 bg-orange-50 rounded-lg text-orange-800 font-bold text-lg border border-orange-100">
                                Rp {{ number_format($order->total_amount, 0, ',', '.') }}
                            </div>
                        </div>

                    {{-- 3. JIKA DANA DIRECT DEBIT (Modul Kustom) --}}
                    @elseif($order->payment_method === 'DANA')
                        <div class="text-center bg-white p-6 rounded-xl shadow-sm border border-blue-200">
                            <img src="{{ asset('public/assets/dana.webp') }}" class="h-12 mx-auto mb-4 object-contain">
                            <p class="text-sm text-gray-600 mb-4">Selesaikan pembayaran via Aplikasi DANA.</p>
                            <a href="{{ $order->payment_url }}" class="block w-full py-3 bg-[#118EEA] hover:bg-[#0b79c9] text-white font-bold rounded-lg shadow-md transition transform hover:-translate-y-1">
                                Buka Aplikasi DANA
                            </a>
                        </div>

                    {{-- 4. JIKA TRIPAY (INVOICE CERDAS) --}}
                    @elseif(isset($tripayDetail))

                        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
                            <div class="text-center mb-6">
                                <p class="text-xs text-gray-400 uppercase tracking-wider mb-2">Metode Pembayaran</p>
                                <h3 class="font-bold text-gray-800 text-lg">{{ $tripayDetail['payment_name'] }}</h3>
                            </div>

                            {{-- A. TAMPILAN QRIS --}}
                            @if($tripayDetail['payment_method'] == 'QRIS' || $tripayDetail['payment_method'] == 'QRIS2' || isset($tripayDetail['qr_url']))
                                <div class="text-center">
                                    <p class="text-xs text-gray-500 mb-3">Scan QR Code ini dengan E-Wallet Anda:</p>
                                    <div class="inline-block p-2 bg-white border rounded-lg shadow-inner mb-4">
                                        <img src="{{ $tripayDetail['qr_url'] }}" class="w-48 h-48 object-contain">
                                    </div>
                                    <div class="flex items-center justify-center space-x-2 text-xs text-gray-400">
                                        <i class="fas fa-check-circle text-green-500"></i>
                                        <span>Gopay, OVO, Dana, ShopeePay, LinkAja</span>
                                    </div>
                                </div>

                            {{-- B. TAMPILAN VIRTUAL ACCOUNT / RETAIL --}}
                            @elseif(isset($tripayDetail['pay_code']))
                                <div class="text-center">
                                    <p class="text-xs text-gray-500 mb-2">Nomor Kode Bayar / VA:</p>
                                    <div class="relative group cursor-pointer" onclick="copyToClipboard('payCode')">
                                        <div class="bg-blue-50 border border-blue-200 p-4 rounded-xl flex items-center justify-center space-x-3 hover:bg-blue-100 transition">
                                            <span id="payCode" class="text-2xl font-mono font-bold text-blue-700 tracking-wider">
                                                {{ $tripayDetail['pay_code'] }}
                                            </span>
                                            <i class="far fa-copy text-blue-400"></i>
                                        </div>
                                        <div class="text-xs text-blue-600 mt-2 font-medium">Klik untuk menyalin</div>
                                    </div>
                                </div>

                            {{-- C. TAMPILAN REDIRECT LINK (OVO/SHOPEEPAY APP) --}}
                            @elseif(isset($tripayDetail['checkout_url']))
                                <div class="text-center">
                                    <p class="text-sm text-gray-600 mb-4">Klik tombol di bawah untuk lanjut bayar:</p>
                                    <a href="{{ $tripayDetail['checkout_url'] }}" target="_blank" class="block w-full py-3 bg-red-600 hover:bg-red-700 text-white font-bold rounded-lg shadow-md transition">
                                        Bayar Sekarang <i class="fas fa-external-link-alt ml-2"></i>
                                    </a>
                                </div>
                            @endif

                            {{-- ACCORDION CARA PEMBAYARAN --}}
                            @if(!empty($tripayDetail['instructions']))
                            <div class="mt-8 pt-6 border-t border-dashed border-gray-200">
                                <h4 class="text-sm font-bold text-gray-800 mb-3 flex items-center">
                                    <i class="fas fa-info-circle text-gray-400 mr-2"></i> Cara Pembayaran
                                </h4>
                                <div class="space-y-2" x-data="{ selected: null }">
                                    @foreach($tripayDetail['instructions'] as $index => $instruction)
                                        <div class="border border-gray-200 rounded-lg overflow-hidden">
                                            <button @click="selected !== {{ $index }} ? selected = {{ $index }} : selected = null"
                                                    class="w-full text-left px-4 py-3 bg-gray-50 hover:bg-white text-xs font-semibold text-gray-700 flex justify-between items-center transition">
                                                <span>{{ $instruction['title'] }}</span>
                                                <i class="fas" :class="selected === {{ $index }} ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
                                            </button>
                                            <div x-show="selected === {{ $index }}" class="px-4 py-3 bg-white text-xs text-gray-600 leading-relaxed border-t border-gray-100">
                                                <ol class="list-decimal list-inside space-y-1">
                                                    @foreach($instruction['steps'] as $step)
                                                        <li>{!! $step !!}</li>
                                                    @endforeach
                                                </ol>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                            @endif

                            {{-- TIMER --}}
                            <div class="mt-6 pt-4 border-t border-gray-100 text-center">
                                <p class="text-xs text-red-500 font-medium">
                                    <i class="far fa-clock mr-1"></i> Batas Waktu:
                                    {{ \Carbon\Carbon::createFromTimestamp($tripayDetail['expired_time'])->format('d M Y, H:i') }}
                                </p>
                            </div>
                        </div>

                    {{-- 5. FALLBACK (JIKA DATA NULL) --}}
                    @else
                        <div class="text-center py-8">
                            <p class="text-gray-600 mb-4 text-sm">Silakan selesaikan pembayaran Anda melalui link berikut:</p>
                            <a href="{{ $order->payment_url }}" target="_blank" class="block w-full py-3 bg-blue-600 text-white rounded-lg font-bold hover:bg-blue-700 transition shadow-lg">
                                Halaman Pembayaran <i class="fas fa-external-link-alt ml-1"></i>
                            </a>
                        </div>
                    @endif

                    <div class="mt-8 text-center">
                        <a href="{{ route('checkout.index') }}" class="text-sm text-gray-500 hover:text-gray-800 transition">
                            &larr; Kembali ke Toko
                        </a>
                    </div>

                </div>
            </div>
        </div>
    </div>
@endsection
