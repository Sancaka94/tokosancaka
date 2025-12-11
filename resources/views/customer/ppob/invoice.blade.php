@extends('layouts.marketplace')

@section('title', 'Invoice #' . $transaction->order_id)

@section('content')
<div class="bg-gray-50 min-h-screen py-6 sm:py-10 print:bg-white print:py-0">
    <div class="container mx-auto px-4 max-w-xl print:max-w-full print:px-0">
        
        {{-- Tombol Kembali (Disembunyikan saat Print) --}}
        <a href="{{ route('customer.dashboard') }}" class="inline-flex items-center text-gray-500 hover:text-blue-600 mb-6 transition print:hidden">
            <i class="fas fa-arrow-left mr-2"></i> Kembali ke Dashboard
        </a>

        <div class="bg-white rounded-3xl shadow-xl overflow-hidden border border-gray-100 relative print:shadow-none print:border-0 print:rounded-none">
            
            {{-- Hiasan Atas --}}
            <div class="h-2 bg-gradient-to-r from-blue-500 to-blue-600 w-full print:hidden"></div>

            <div class="p-6 sm:p-8">
                
                {{-- 1. Header Status --}}
                <div class="text-center mb-8">
                    @php
                        $status = strtolower($transaction->status);
                        $isSuccess = in_array($status, ['success', 'sukses']);
                        $isPending = in_array($status, ['pending', 'menunggu']);
                    @endphp

                    @if($isSuccess)
                        <div class="w-16 h-16 bg-green-100 text-green-600 rounded-full flex items-center justify-center mx-auto mb-4 text-3xl ring-4 ring-green-50 print:hidden">
                            <i class="fas fa-check"></i>
                        </div>
                        <h2 class="text-2xl font-extrabold text-gray-900">Pembayaran Berhasil</h2>
                        <p class="text-gray-500 mt-1 text-sm">Transaksi telah berhasil diproses.</p>
                    
                    @elseif($isPending)
                        <div class="w-16 h-16 bg-yellow-100 text-yellow-600 rounded-full flex items-center justify-center mx-auto mb-4 text-3xl ring-4 ring-yellow-50 print:hidden">
                            <i class="fas fa-clock"></i>
                        </div>
                        <h2 class="text-2xl font-extrabold text-gray-900">Menunggu Pembayaran</h2>
                        <p class="text-gray-500 mt-1 text-sm">Selesaikan pembayaran sebelum batas waktu.</p>
                    
                    @else
                        <div class="w-16 h-16 bg-red-100 text-red-600 rounded-full flex items-center justify-center mx-auto mb-4 text-3xl ring-4 ring-red-50 print:hidden">
                            <i class="fas fa-times"></i>
                        </div>
                        <h2 class="text-2xl font-extrabold text-gray-900">Transaksi Gagal</h2>
                        <p class="text-gray-500 mt-1 text-sm">{{ $transaction->message ?? 'Terjadi kesalahan sistem.' }}</p>
                    @endif
                </div>

                {{-- 2. Tabel Informasi Utama (Struk Style) --}}
                <div class="border-t-2 border-dashed border-gray-200 py-6 mb-6 space-y-3">
                    <div class="flex justify-between items-center">
                        <span class="text-gray-500 text-sm">No. Invoice</span>
                        <span class="font-mono font-bold text-gray-800 tracking-wide">{{ $transaction->order_id }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-gray-500 text-sm">Tanggal</span>
                        <span class="font-medium text-gray-800 text-sm">{{ $transaction->created_at->format('d M Y, H:i') }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-gray-500 text-sm">Metode Bayar</span>
                        <span class="font-medium text-gray-800 uppercase text-sm">{{ str_replace('_', ' ', $transaction->payment_method) }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-gray-500 text-sm">Status</span>
                        @php
                            $badgeClass = match(true) {
                                $isSuccess => 'bg-green-100 text-green-700 border-green-200',
                                $isPending => 'bg-yellow-100 text-yellow-700 border-yellow-200',
                                default => 'bg-red-100 text-red-700 border-red-200'
                            };
                        @endphp
                        <span class="{{ $badgeClass }} border px-2.5 py-0.5 rounded-md text-xs font-bold uppercase tracking-wide">
                            {{ $transaction->status }}
                        </span>
                    </div>
                </div>

                {{-- 3. Rincian Produk --}}
                <div class="bg-gray-50 rounded-xl p-5 mb-6 border border-gray-200 print:bg-white print:border-black print:border-2">
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-4 border-b border-gray-200 pb-2">Rincian Produk</p>
                    
                    {{-- Info Produk Utama --}}
                    <div class="flex justify-between items-start mb-4">
                        <div>
                            <h3 class="font-bold text-gray-900 text-lg leading-tight">{{ $transaction->buyer_sku_code }}</h3>
                            <p class="text-sm text-gray-500 mt-1">
                                ID Pel: <span class="font-mono font-semibold text-gray-700">{{ $transaction->customer_no }}</span>
                            </p>
                        </div>
                        <div class="text-right">
                            <p class="font-extrabold text-xl text-blue-600 print:text-black">Rp {{ number_format($transaction->selling_price, 0, ',', '.') }}</p>
                        </div>
                    </div>

                    {{-- Logic Parse Detail (PLN Pascabayar/PDAM) --}}
                    @php
                        $descData = $transaction->desc;
                        if (is_string($descData)) {
                            $descData = json_decode($descData, true);
                        }
                        $details = $descData['detail'] ?? []; 
                    @endphp

                    @if(!empty($details) && is_array($details))
                        <div class="mt-4 pt-3 border-t border-dashed border-gray-300">
                            <div class="space-y-3">
                                @foreach($details as $item)
                                    <div class="bg-white border border-gray-200 p-3 rounded-lg text-sm shadow-sm print:shadow-none print:border-gray-400">
                                        <div class="flex justify-between mb-1">
                                            <span class="text-gray-500 text-xs">Periode</span>
                                            <span class="font-bold text-gray-800">{{ $item['periode'] ?? '-' }}</span>
                                        </div>
                                        
                                        @if(isset($item['meter_awal']) && isset($item['meter_akhir']))
                                        <div class="flex justify-between mb-1">
                                            <span class="text-gray-500 text-xs">Stand Meter</span>
                                            <span class="text-gray-800 text-xs">{{ $item['meter_awal'] }} - {{ $item['meter_akhir'] }}</span>
                                        </div>
                                        @endif

                                        <div class="flex justify-between items-center mt-2 pt-2 border-t border-gray-100">
                                            <span class="font-bold text-gray-700">Tagihan</span>
                                            <span class="font-bold text-gray-900">Rp {{ number_format($item['nilai_tagihan'] ?? 0, 0, ',', '.') }}</span>
                                        </div>
                                        
                                        @if(($item['denda'] ?? 0) > 0)
                                            <div class="flex justify-between text-red-500 text-xs mt-1">
                                                <span>Denda</span>
                                                <span>+ Rp {{ number_format($item['denda'], 0, ',', '.') }}</span>
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- 4. Serial Number / Token (Highlight) --}}
                    @if($transaction->sn && $isSuccess)
                        <div class="mt-6">
                            <div class="text-center mb-2">
                                <span class="text-[10px] font-bold text-gray-500 uppercase tracking-widest">Serial Number / Token</span>
                            </div>
                            <div class="bg-yellow-50 border-2 border-yellow-300 p-4 rounded-xl text-center relative group print:bg-white print:border-black">
                                <p class="font-mono font-bold text-xl sm:text-2xl tracking-widest text-gray-900 select-all break-all">
                                    {{ $transaction->sn }}
                                </p>
                            </div>
                            <p class="text-[10px] text-center text-gray-400 mt-2 print:hidden">Salin kode di atas untuk digunakan.</p>
                        </div>
                    @endif
                </div>

                {{-- 5. Tombol Aksi (Hidden saat Print) --}}
                <div class="flex flex-col sm:flex-row gap-3 print:hidden">
                    {{-- Tombol Bayar --}}
                    @if($transaction->payment_method !== 'saldo' && $isPending && $transaction->payment_url)
                        <a href="{{ $transaction->payment_url }}" target="_blank" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-4 rounded-xl text-center transition shadow-lg shadow-blue-200 transform hover:-translate-y-0.5 flex items-center justify-center gap-2">
                            <span>Bayar Sekarang</span>
                            <i class="fas fa-external-link-alt text-sm"></i>
                        </a>
                    @endif
                    
                    {{-- Tombol Cetak --}}
                    <button onclick="window.print()" class="flex-1 border border-gray-300 bg-white hover:bg-gray-50 text-gray-700 font-bold py-3 px-4 rounded-xl text-center transition flex items-center justify-center gap-2">
                        <i class="fas fa-print"></i> Cetak Struk
                    </button>

                    {{-- Tombol Beli Lagi --}}
                    @if($isSuccess || $status == 'failed')
                         <a href="{{ route('etalase.index') }}" class="flex-1 bg-gray-900 hover:bg-black text-white font-bold py-3 px-4 rounded-xl text-center transition flex items-center justify-center gap-2">
                            <i class="fas fa-shopping-cart"></i> Beli Lagi
                        </a>
                    @endif
                </div>

            </div>
        </div>

        {{-- Bantuan --}}
        <div class="text-center mt-8 pb-8 print:hidden">
            <p class="text-sm text-gray-500">Butuh bantuan? <a href="#" class="text-blue-600 font-bold hover:underline">Hubungi CS</a></p>
        </div>

    </div>
</div>

{{-- Style Khusus Print yang Benar --}}
<style>
    @media print {
        @page { margin: 0; size: auto; }
        body { background-color: white; -webkit-print-color-adjust: exact; }
        nav, footer, .header, .sidebar { display: none !important; } /* Sesuaikan dengan class layout utamamu */
        .container { max-width: 100% !important; width: 100% !important; margin: 0 !important; padding: 20px !important; }
    }
</style>
@endsection