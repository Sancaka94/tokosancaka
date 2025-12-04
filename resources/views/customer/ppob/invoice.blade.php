@extends('layouts.marketplace')

@section('title', 'Invoice Transaksi #' . $transaction->order_id)

@section('content')
<div class="bg-gray-50 min-h-screen py-10">
    <div class="container mx-auto px-4 max-w-2xl">
        
        {{-- Tombol Kembali --}}
        <a href="{{ route('customer.dashboard') }}" class="inline-flex items-center text-gray-500 hover:text-blue-600 mb-6 transition">
            <i class="fas fa-arrow-left mr-2"></i> Kembali ke Dashboard
        </a>

        <div class="bg-white rounded-2xl shadow-lg overflow-hidden border border-gray-200 relative">
            
            {{-- Hiasan Atas --}}
            <div class="h-2 bg-blue-600 w-full"></div>

            <div class="p-8">
                
                {{-- Header Status --}}
                <div class="text-center mb-8">
                    @php
                        $status = strtolower($transaction->status);
                    @endphp

                    @if(in_array($status, ['success', 'processing']))
                        <div class="w-16 h-16 bg-green-100 text-green-600 rounded-full flex items-center justify-center mx-auto mb-4 text-3xl shadow-sm">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <h2 class="text-2xl font-extrabold text-gray-900">Pembayaran Berhasil</h2>
                        <p class="text-gray-500 mt-1">Transaksi Anda sedang diproses oleh sistem.</p>
                    
                    @elseif($status == 'pending')
                        <div class="w-16 h-16 bg-yellow-100 text-yellow-600 rounded-full flex items-center justify-center mx-auto mb-4 text-3xl shadow-sm">
                            <i class="fas fa-clock"></i>
                        </div>
                        <h2 class="text-2xl font-extrabold text-gray-900">Menunggu Pembayaran</h2>
                        <p class="text-gray-500 mt-1">Silakan selesaikan pembayaran Anda.</p>
                    
                    @else
                        <div class="w-16 h-16 bg-red-100 text-red-600 rounded-full flex items-center justify-center mx-auto mb-4 text-3xl shadow-sm">
                            <i class="fas fa-times-circle"></i>
                        </div>
                        <h2 class="text-2xl font-extrabold text-gray-900">Transaksi Gagal</h2>
                        <p class="text-gray-500 mt-1">Mohon hubungi admin jika ada kendala.</p>
                    @endif
                </div>

                {{-- Detail Transaksi (Tabel Style) --}}
                <div class="border-t border-b border-dashed border-gray-200 py-6 mb-6 space-y-3">
                    <div class="flex justify-between">
                        <span class="text-gray-500 text-sm">No. Invoice</span>
                        <span class="font-mono font-bold text-gray-800">{{ $transaction->order_id }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500 text-sm">Tanggal</span>
                        <span class="font-medium text-gray-800">{{ $transaction->created_at->format('d M Y H:i') }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500 text-sm">Metode Bayar</span>
                        <span class="font-medium text-gray-800 uppercase">{{ $transaction->payment_method }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-gray-500 text-sm">Status</span>
                        @php
                            $badgeColor = match($status) {
                                'success' => 'bg-green-100 text-green-800 border-green-200',
                                'processing' => 'bg-blue-100 text-blue-800 border-blue-200',
                                'pending' => 'bg-yellow-100 text-yellow-800 border-yellow-200',
                                default => 'bg-red-100 text-red-800 border-red-200'
                            };
                        @endphp
                        <span class="{{ $badgeColor }} border px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wide">
                            {{ $transaction->status }}
                        </span>
                    </div>
                </div>

                {{-- Rincian Produk --}}
                <div class="bg-gray-50 rounded-xl p-5 mb-6 border border-gray-100">
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">Rincian Produk</p>
                    
                    <div class="flex justify-between items-start mb-1">
                        <div>
                            <h3 class="font-bold text-gray-900 text-lg">{{ $transaction->buyer_sku_code }}</h3>
                            <p class="text-sm text-gray-500 mt-1">
                                ID Pelanggan: <span class="font-mono font-semibold text-gray-700 bg-gray-200 px-2 rounded">{{ $transaction->customer_no }}</span>
                            </p>
                        </div>
                        <div class="text-right">
                            <p class="font-extrabold text-xl text-blue-600">Rp {{ number_format($transaction->selling_price, 0, ',', '.') }}</p>
                        </div>
                    </div>

                    {{-- Tampilkan SN / Token Listrik Jika Sukses --}}
                    @if($transaction->sn && $status === 'success')
                        <div class="mt-4 pt-4 border-t border-gray-200 border-dashed">
                            <p class="text-xs font-bold text-gray-500 uppercase mb-2 text-center">Serial Number / Token</p>
                            <div class="bg-yellow-50 border border-yellow-200 p-3 rounded-lg text-center relative group cursor-pointer hover:bg-yellow-100 transition">
                                <p class="font-mono font-bold text-lg tracking-widest text-gray-800 select-all break-all">
                                    {{ $transaction->sn }}
                                </p>
                                <span class="absolute right-2 top-2 text-gray-400 opacity-0 group-hover:opacity-100 text-xs">
                                    <i class="fas fa-copy"></i>
                                </span>
                            </div>
                            <p class="text-[10px] text-center text-gray-400 mt-2">Salin kode di atas untuk digunakan.</p>
                        </div>
                    @endif

                    {{-- Pesan Error Jika Gagal --}}
                    @if($status === 'failed')
                        <div class="mt-4 pt-4 border-t border-red-200 border-dashed text-center">
                            <p class="text-sm text-red-600 italic">
                                "{{ $transaction->message ?? 'Transaksi gagal diproses provider.' }}"
                            </p>
                        </div>
                    @endif
                </div>

                {{-- Tombol Aksi --}}
                <div class="flex flex-col sm:flex-row gap-3">
                    {{-- Tombol Bayar (Hanya jika Pending & Online Payment) --}}
                    @if($transaction->payment_method !== 'saldo' && $status == 'pending' && $transaction->payment_url)
                        <a href="{{ $transaction->payment_url }}" target="_blank" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-bold py-3.5 rounded-xl text-center transition shadow-lg transform hover:-translate-y-0.5 flex items-center justify-center gap-2">
                            <span>Bayar Sekarang</span>
                            <i class="fas fa-external-link-alt text-sm"></i>
                        </a>
                    @endif
                    
                    {{-- Tombol Cetak --}}
                    <button onclick="window.print()" class="flex-1 border border-gray-300 hover:bg-gray-50 text-gray-700 font-bold py-3.5 rounded-xl text-center transition flex items-center justify-center gap-2">
                        <i class="fas fa-print"></i> Cetak Struk
                    </button>

                    {{-- Tombol Beli Lagi (Jika Sukses/Gagal) --}}
                    @if(in_array($status, ['success', 'failed']))
                         <a href="{{ route('etalase.index') }}" class="flex-1 bg-gray-800 hover:bg-gray-900 text-white font-bold py-3.5 rounded-xl text-center transition flex items-center justify-center gap-2">
                            <i class="fas fa-shopping-cart"></i> Beli Lagi
                        </a>
                    @endif
                </div>

            </div>
        </div>

        {{-- Bantuan --}}
        <div class="text-center mt-8 pb-8">
            <p class="text-sm text-gray-500">Butuh bantuan? Hubungi <a href="https://wa.me/6281234567890" class="text-blue-600 font-bold hover:underline">Customer Service</a></p>
        </div>

    </div>
</div>

{{-- CSS Print Khusus --}}
<style>
    @media print {
        body * { visibility: hidden; }
        .container, .container * { visibility: visible; }
        .container { position: absolute; left: 0; top: 0; width: 100%; margin: 0; padding: 0; }
        a[href], button { display: none !important; } /* Sembunyikan tombol saat print */
        .shadow-lg { box-shadow: none !important; border: 1px solid #000 !important; }
    }
</style>
@endsection