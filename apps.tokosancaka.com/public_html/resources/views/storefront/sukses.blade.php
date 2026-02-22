@extends('layouts.marketplace')

@section('title', 'Pesanan Berhasil')

@section('content')
<div class="max-w-2xl mx-auto px-4 py-20 text-center" x-data x-init="localStorage.removeItem('sancaka_cart_{{ $tenant->id }}')">

    <div class="w-24 h-24 bg-green-100 text-green-600 rounded-full flex items-center justify-center mx-auto mb-6 border-4 border-white shadow-lg">
        <i data-lucide="check-check" class="w-12 h-12"></i>
    </div>

    <h2 class="text-3xl font-black text-gray-900 mb-2">Checkout Berhasil!</h2>
    <p class="text-gray-500 mb-8">Terima kasih, pesanan Anda telah masuk ke sistem kami dengan nomor nota <br><strong class="text-gray-900 text-lg">#{{ $order->order_number }}</strong>.</p>

    <div class="bg-white p-6 rounded-3xl shadow-sm border border-gray-100 mb-8 text-left max-w-md mx-auto">
        <div class="flex justify-between mb-2">
            <span class="text-gray-500">Total Tagihan</span>
            <span class="font-black text-lg text-blue-600">Rp {{ number_format($order->final_price, 0, ',', '.') }}</span>
        </div>
        <div class="flex justify-between mb-4 pb-4 border-b border-gray-100">
            <span class="text-gray-500">Status Pembayaran</span>
            @if($order->payment_status === 'paid')
                <span class="font-bold text-green-600 bg-green-50 px-2 py-1 rounded text-xs">LUNAS</span>
            @else
                <span class="font-bold text-orange-600 bg-orange-50 px-2 py-1 rounded text-xs">MENUNGGU PEMBAYARAN</span>
            @endif
        </div>

        @if($order->payment_status === 'unpaid' && $order->payment_url)
            <p class="text-xs text-gray-500 mb-3 text-center">Silakan selesaikan pembayaran Anda melalui tautan di bawah ini:</p>
            <a href="{{ $order->payment_url }}" target="_blank" class="block w-full bg-blue-600 text-white text-center py-3 rounded-xl font-bold hover:bg-blue-700 shadow-lg transition">
                Bayar Sekarang Tembus DANA/QRIS
            </a>
        @endif
    </div>

    <div class="flex justify-center gap-4">
        <a href="{{ route('storefront.index', $subdomain) }}" class="px-6 py-2 border-2 border-gray-200 text-gray-600 font-bold rounded-xl hover:bg-gray-50 transition">Kembali ke Beranda</a>
        <a href="{{ route('orders.invoice', ['subdomain' => $subdomain, 'orderNumber' => $order->order_number]) }}" target="_blank" class="px-6 py-2 bg-gray-900 text-white font-bold rounded-xl hover:bg-gray-800 transition">Lihat Invoice</a>
    </div>
</div>
@endsection
