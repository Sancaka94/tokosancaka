@extends('layouts.marketplace')

@section('title', 'Keranjang & Checkout')

@push('styles')
<style>
    /* Custom Scrollbar untuk list payment */
    .payment-scroll::-webkit-scrollbar { width: 6px; }
    .payment-scroll::-webkit-scrollbar-track { background: #f1f1f1; border-radius: 10px; }
    .payment-scroll::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
    .payment-scroll::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
</style>
@endpush

@section('content')
<div class="bg-gray-50 min-h-screen py-8 md:py-12">
    <div class="container mx-auto px-4 max-w-6xl">

        {{-- 1. ALERT NOTIFIKASI --}}
        @if(session('success'))
            <div class="mb-6 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl flex items-center gap-3 shadow-sm" role="alert">
                <i class="fas fa-check-circle text-xl"></i> <span class="font-medium">{{ session('success') }}</span>
            </div>
        @endif
        @if(session('error'))
            <div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl flex items-center gap-3 shadow-sm" role="alert">
                <i class="fas fa-exclamation-circle text-xl"></i> <span class="font-medium">{{ session('error') }}</span>
            </div>
        @endif

        {{-- 2. HEADER HALAMAN --}}
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
            <div>
                <h1 class="text-2xl font-extrabold text-gray-800 flex items-center gap-3">
                    <i class="fas fa-shopping-cart text-blue-600"></i> Checkout Transaksi
                </h1>
                <p class="text-gray-500 text-sm mt-1">Periksa kembali pesanan digital Anda sebelum melakukan pembayaran.</p>
            </div>

            {{-- TOMBOL BATALKAN SEMUA TRANSAKSI --}}
            @if(!empty($cart) && count($cart) > 0)
            <form action="{{ route('ppob.cart.clear') }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin mengosongkan keranjang?');">
                @csrf
                <button type="submit" class="group flex items-center gap-2 bg-white border border-red-200 text-red-500 hover:bg-red-50 hover:border-red-300 hover:text-red-700 px-5 py-2.5 rounded-xl transition font-semibold shadow-sm text-sm">
                    <i class="fas fa-trash-alt group-hover:scale-110 transition-transform"></i>
                    Batalkan Semua
                </button>
            </form>
            @endif
        </div>

        {{-- LOGIKA JIKA KERANJANG KOSONG --}}
        @if(empty($cart) || count($cart) == 0)
            <div class="bg-white rounded-3xl p-12 text-center border border-dashed border-gray-300 shadow-sm max-w-2xl mx-auto mt-10">
                <div class="w-24 h-24 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-6">
                    <i class="fas fa-shopping-basket text-5xl text-gray-300"></i>
                </div>
                <h2 class="text-xl font-bold text-gray-800 mb-2">Keranjang Anda Kosong</h2>
                <p class="text-gray-500 mb-6">Sepertinya Anda belum memilih produk apapun untuk dibeli.</p>
                <a href="{{ route('ppob.pricelist') }}" class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-8 rounded-xl transition-all shadow-lg hover:shadow-blue-200 transform hover:-translate-y-1">
                    <i class="fas fa-arrow-left"></i> Kembali Belanja
                </a>
            </div>
        @else
            {{-- FORM UTAMA CHECKOUT --}}
            <form action="{{ route('ppob.checkout.store') }}" method="POST" id="checkout-form">
                @csrf

                {{-- IDEMPOTENCY KEY --}}
                @php
                    if (!isset($idempotencyKey)) {
                        $idempotencyKey = (string) \Illuminate\Support\Str::uuid();
                    }
                @endphp
                <input type="hidden" name="idempotency_key" value="{{ $idempotencyKey }}">

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

                    {{-- === KOLOM KIRI (LIST ITEM) === --}}
                    <div class="lg:col-span-2 space-y-6">

                        {{-- LOOPING ITEM KERANJANG --}}
                        @foreach($cart as $item)
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden relative transition hover:shadow-md hover:border-blue-200 group">
                            {{-- DEKORASI SIDEBAR WARNA --}}
                            <div class="absolute left-0 top-0 bottom-0 w-1.5 bg-blue-500"></div>

                            <div class="p-5 pl-7 flex flex-col sm:flex-row gap-5 items-start sm:items-center justify-between">
                                {{-- Info Produk --}}
                                <div class="flex items-start gap-4 flex-1">
                                    <div class="w-12 h-12 rounded-xl bg-blue-50 flex items-center justify-center text-blue-600 shadow-sm border border-blue-100 flex-shrink-0">
                                        <i class="fas fa-receipt text-xl"></i>
                                    </div>

                                    <div>
                                        <h4 class="font-bold text-gray-800 text-lg leading-tight mb-2">{{ $item['name'] }}</h4>
                                        <div class="flex flex-wrap items-center gap-2 text-sm text-gray-500">
                                            <div class="flex items-center gap-1 bg-gray-100 px-2 py-1 rounded-md font-mono text-xs font-semibold text-gray-700">
                                                <i class="fas fa-user text-gray-400"></i> {{ $item['customer_no'] }}
                                            </div>
                                            <span class="text-gray-300">|</span>
                                            <span class="text-xs bg-gray-50 px-2 py-1 rounded border border-gray-100">SKU: {{ $item['sku'] }}</span>
                                        </div>

                                        {{-- Tampilkan Rincian Tagihan (Jika Ada) --}}
                                        @if(!empty($item['desc']))
                                            <div class="mt-3 text-xs text-gray-600 bg-yellow-50 p-2.5 rounded-lg border border-yellow-100 inline-block w-full sm:w-auto">
                                                @if(isset($item['desc']['customer_name']))
                                                    <span class="font-bold block sm:inline mb-1 sm:mb-0"><i class="fas fa-user-circle text-yellow-600 mr-1"></i> {{ $item['desc']['customer_name'] }}</span>
                                                @endif
                                                @if(isset($item['desc']['power']))
                                                    <span class="hidden sm:inline text-gray-400 mx-1">/</span>
                                                    <span class="font-mono text-yellow-700"><i class="fas fa-bolt mr-1"></i> {{ $item['desc']['power'] }}</span>
                                                @endif
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                {{-- Harga & Hapus --}}
                                <div class="flex items-center justify-between sm:justify-end gap-6 w-full sm:w-auto pt-4 sm:pt-0 border-t sm:border-t-0 border-gray-100 mt-2 sm:mt-0">
                                    <div class="text-left sm:text-right">
                                        <p class="text-[10px] text-gray-400 uppercase font-bold tracking-wider mb-0.5">Harga</p>
                                        <p class="text-xl font-extrabold text-blue-600">Rp {{ number_format($item['price'], 0, ',', '.') }}</p>
                                    </div>

                                    {{-- TOMBOL HAPUS --}}
                                    <a href="{{ route('ppob.cart.remove', $item['id']) }}"
                                       class="w-10 h-10 rounded-xl bg-red-50 text-red-500 flex items-center justify-center hover:bg-red-500 hover:text-white transition shadow-sm border border-red-100 tooltip-trigger"
                                       onclick="return confirm('Hapus produk ini dari keranjang?')"
                                       title="Hapus Item">
                                        <i class="fas fa-trash-alt"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                        @endforeach

                        {{-- TOTAL TAGIHAN --}}
                        <div class="bg-gradient-to-r from-blue-600 to-blue-800 rounded-2xl shadow-lg p-6 text-white flex flex-col sm:flex-row justify-between items-center gap-4">
                            <div class="flex items-center gap-4 w-full sm:w-auto">
                                <div class="w-14 h-14 bg-white/20 rounded-full flex items-center justify-center flex-shrink-0 backdrop-blur-sm">
                                    <i class="fas fa-wallet text-2xl"></i>
                                </div>
                                <div>
                                    <p class="text-blue-100 text-sm font-medium mb-1">Total Pembayaran ({{ count($cart) }} Item)</p>
                                    <h2 class="text-3xl font-bold">Rp {{ number_format($totalPrice, 0, ',', '.') }}</h2>
                                </div>
                            </div>
                            <div class="text-blue-200 text-xs text-left sm:text-right w-full sm:w-auto bg-white/10 sm:bg-transparent p-3 sm:p-0 rounded-lg">
                                <p class="flex items-center sm:justify-end gap-1"><i class="fas fa-check-circle"></i> Tidak ada biaya tersembunyi.</p>
                                <p class="flex items-center sm:justify-end gap-1 mt-1"><i class="fas fa-bolt"></i> Transaksi diproses instan.</p>
                            </div>
                        </div>
                    </div>

                    {{-- === KOLOM KANAN (PEMBAYARAN) === --}}
                    <div class="lg:col-span-1 space-y-6">

                        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 sticky top-24">
                            <div class="p-5 border-b border-gray-100 bg-gray-50/80 rounded-t-2xl">
                                <h3 class="font-bold text-gray-800 flex items-center gap-2">
                                    <i class="fas fa-credit-card text-blue-500"></i> Metode Pembayaran
                                </h3>
                            </div>

                            <div class="p-5 space-y-5 max-h-[500px] overflow-y-auto payment-scroll">

                                {{-- A. SALDO AKUN --}}
                                @if(isset($paymentChannels['saldo']))
                                    @php
                                        $saldo = $paymentChannels['saldo'];
                                        $isCukup = $saldo['balance'] >= $totalPrice;
                                    @endphp
                                    <div>
                                        <p class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-3 pl-1">Dompet Internal</p>
                                        <label class="relative block cursor-pointer group">
                                            <input type="radio" name="payment_method" value="saldo" class="peer sr-only" {{ !$isCukup ? 'disabled' : '' }} required>

                                            <div class="p-4 rounded-xl border-2 border-gray-200 peer-checked:border-blue-500 peer-checked:bg-blue-50 transition-all hover:border-blue-300 flex items-center gap-4 {{ !$isCukup ? 'opacity-60 bg-gray-50 cursor-not-allowed' : 'bg-white' }}">

                                                <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center text-blue-600 flex-shrink-0">
                                                    <i class="fas fa-wallet text-xl"></i>
                                                </div>

                                                <div class="flex-1">
                                                    <h4 class="font-bold text-gray-800 text-sm">Saldo Akun</h4>
                                                    <p class="text-sm font-bold {{ $isCukup ? 'text-green-600' : 'text-red-500' }}">
                                                        Rp {{ number_format($saldo['balance'] ?? 0, 0, ',', '.') }}
                                                    </p>
                                                    @if(!$isCukup)
                                                        <span class="text-[10px] text-red-500 font-bold bg-red-50 px-2 py-0.5 rounded mt-1 inline-block border border-red-100">Saldo Tidak Cukup</span>
                                                    @endif
                                                </div>

                                                <div class="w-5 h-5 rounded-full border-2 border-gray-300 peer-checked:border-blue-600 peer-checked:bg-blue-600 flex items-center justify-center transition-colors">
                                                    <div class="w-2 h-2 bg-white rounded-full hidden peer-checked:block"></div>
                                                </div>
                                            </div>
                                        </label>
                                    </div>
                                @endif

                                {{-- B. DOKU CHANNELS --}}
                                @if(isset($paymentChannels['doku']) && count($paymentChannels['doku']) > 0)
                                    <div>
                                        <p class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-3 pl-1">Rekomendasi Sancaka</p>
                                        <div class="space-y-3">
                                            @foreach($paymentChannels['doku'] as $channel)
                                                <label class="relative block cursor-pointer group">
                                                    <input type="radio" name="payment_method" value="{{ $channel['code'] }}" class="peer sr-only" required>
                                                    <div class="p-3.5 rounded-xl border-2 border-gray-200 peer-checked:border-blue-500 peer-checked:bg-blue-50 transition-all hover:border-blue-300 flex items-center gap-3 bg-white">
                                                        <div class="w-10 h-6 flex items-center justify-center bg-white rounded border border-gray-100 overflow-hidden">
                                                            <img src="{{ $channel['icon_url'] }}" class="max-h-full max-w-full object-contain" alt="{{ $channel['name'] }}">
                                                        </div>
                                                        <span class="text-sm font-semibold text-gray-700 flex-1">{{ $channel['name'] }}</span>
                                                        <div class="w-5 h-5 rounded-full border-2 border-gray-300 peer-checked:bg-blue-600 peer-checked:border-blue-600 flex items-center justify-center transition-colors">
                                                            <div class="w-2 h-2 bg-white rounded-full hidden peer-checked:block"></div>
                                                        </div>
                                                    </div>
                                                </label>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif

                                {{-- C. TRIPAY CHANNELS --}}
                                @if(isset($paymentChannels['tripay']) && count($paymentChannels['tripay']) > 0)
                                    <div>
                                        <p class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-3 pl-1">Virtual Account & E-Wallet</p>
                                        <div class="space-y-3">
                                            @foreach($paymentChannels['tripay'] as $channel)
                                                <label class="relative block cursor-pointer group">
                                                    <input type="radio" name="payment_method" value="{{ $channel['code'] }}" class="peer sr-only" required>
                                                    <div class="p-3.5 rounded-xl border-2 border-gray-200 peer-checked:border-blue-500 peer-checked:bg-blue-50 transition-all hover:border-blue-300 flex items-center gap-3 bg-white">
                                                        <div class="w-10 h-6 flex items-center justify-center bg-white rounded border border-gray-100 overflow-hidden p-0.5">
                                                            <img src="{{ $channel['icon_url'] }}" class="max-h-full max-w-full object-contain" alt="{{ $channel['name'] }}">
                                                        </div>
                                                        <span class="text-sm font-semibold text-gray-700 flex-1">{{ $channel['name'] }}</span>
                                                        <div class="w-5 h-5 rounded-full border-2 border-gray-300 peer-checked:bg-blue-600 peer-checked:border-blue-600 flex items-center justify-center transition-colors">
                                                            <div class="w-2 h-2 bg-white rounded-full hidden peer-checked:block"></div>
                                                        </div>
                                                    </div>
                                                </label>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif

                            </div>

                            <div class="p-5 bg-gray-50 border-t border-gray-200 rounded-b-2xl">
                                <button type="submit" id="btn-pay" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-4 rounded-xl shadow-lg shadow-blue-200 transition transform hover:-translate-y-1 flex justify-center items-center gap-2 group">
                                    <span>Bayar Sekarang</span>
                                    <i class="fas fa-arrow-right group-hover:translate-x-1 transition-transform"></i>
                                </button>
                                <p class="text-center text-xs text-gray-400 mt-4 flex justify-center items-center gap-1.5">
                                    <i class="fas fa-shield-alt text-green-500"></i> Pembayaran aman & terenkripsi SSL.
                                </p>
                            </div>
                        </div>

                        {{-- INFORMASI USER --}}
                        <div class="bg-white rounded-2xl p-5 border border-gray-200 shadow-sm flex items-center gap-4">
                            <div class="w-12 h-12 rounded-full bg-gray-100 border border-gray-200 flex items-center justify-center text-gray-600 font-bold text-lg flex-shrink-0">
                                {{ strtoupper(substr($user->nama_lengkap, 0, 1)) }}
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-xs text-gray-400 uppercase font-bold tracking-wide">Informasi Pembeli</p>
                                <p class="text-sm font-bold text-gray-800 truncate">{{ $user->nama_lengkap }}</p>
                                <p class="text-xs text-gray-500 truncate mt-0.5"><i class="fas fa-envelope mr-1"></i>{{ $user->email }}</p>
                            </div>
                        </div>

                    </div>
                </div>
            </form>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
    // UX Tambahan: Merubah teks tombol submit saat di-klik agar user tidak melakukan double-click
    document.addEventListener("DOMContentLoaded", function() {
        const form = document.getElementById('checkout-form');
        const btnPay = document.getElementById('btn-pay');

        if(form && btnPay) {
            form.addEventListener('submit', function(e) {
                // Memastikan form lolos validasi HTML5 (required pada input radio)
                if (form.checkValidity()) {
                    btnPay.disabled = true;
                    btnPay.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sedang Memproses...';
                    btnPay.classList.add('opacity-75', 'cursor-not-allowed');
                }
            });
        }
    });
</script>
@endpush
