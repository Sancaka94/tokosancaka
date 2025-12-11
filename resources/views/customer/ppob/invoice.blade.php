@extends('layouts.marketplace')

@section('title', 'Invoice #' . $transaction->order_id)

@section('content')
<div class="min-h-screen bg-gray-100 flex items-center justify-center py-10 px-4 print:bg-white print:p-0 print:block">
    
    <div id="invoice-card" class="bg-white w-full max-w-md p-6 rounded-xl shadow-lg relative print:shadow-none print:w-full print:max-w-full print:p-2 print:m-0 print:rounded-none">
        
        <div class="text-center border-b-2 border-dashed border-gray-800 pb-3 mb-3">
            <h2 class="text-xl font-extrabold text-blue-900 uppercase tracking-wider print:text-black">Sancaka Store</h2>
            <p class="text-[10px] text-gray-500 mt-1 print:text-black">Bukti Pembayaran Sah</p>
        </div>

        <div class="space-y-1 text-xs mb-3 font-mono">
            <div class="flex justify-between">
                <span class="text-gray-500 print:text-black">No. Invoice</span>
                <span class="font-bold text-gray-800 print:text-black">{{ $transaction->order_id }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-500 print:text-black">Tgl</span>
                <span class="font-semibold text-gray-800 print:text-black">{{ $transaction->created_at->format('d/m/y H:i') }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-500 print:text-black">Metode</span>
                <span class="font-semibold text-gray-800 uppercase print:text-black">{{ str_replace('_', ' ', $transaction->payment_method) }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-500 print:text-black">Status</span>
                <span class="font-bold uppercase print:text-black">{{ $transaction->status }}</span>
            </div>
        </div>

        <div class="bg-gray-50 p-3 rounded border border-gray-200 mb-3 print:bg-transparent print:border-black print:border-y-2 print:border-x-0 print:rounded-none print:py-2">
            <div class="flex justify-between items-start">
                <div class="w-2/3">
                    <h3 class="font-bold text-sm text-gray-900 leading-tight print:text-black">{{ strtoupper($transaction->buyer_sku_code) }}</h3>
                    <p class="text-[10px] text-gray-500 mt-0.5 print:text-black">ID: {{ $transaction->customer_no }}</p>
                </div>
                <div class="w-1/3 text-right">
                    <span class="block font-bold text-sm text-blue-600 print:text-black">
                        Rp {{ number_format($transaction->selling_price, 0, ',', '.') }}
                    </span>
                </div>
            </div>

            @if($transaction->sn)
            <div class="mt-2 pt-2 border-t border-dashed border-gray-400 print:border-black">
                <p class="text-[9px] text-center text-gray-500 uppercase tracking-widest mb-1 print:text-black">Serial Number / Token</p>
                <div class="border border-gray-800 p-2 rounded text-center print:border-2 print:border-black">
                    <span class="font-mono font-bold text-lg tracking-widest text-gray-900 select-all print:text-black">
                        {{ $transaction->sn }}
                    </span>
                </div>
            </div>
            @endif
        </div>

        <div class="text-center text-[10px] text-gray-400 mt-4 print:text-black print:mt-2">
            <p>Terima kasih - Sancaka Store</p>
        </div>

        <div class="mt-6 flex gap-3 print:hidden">
            <a href="{{ route('customer.dashboard') }}" class="flex-1 py-3 bg-gray-200 text-gray-700 font-bold rounded-lg text-center hover:bg-gray-300 text-sm">
                Kembali
            </a>
            <button onclick="window.print()" class="flex-1 py-3 bg-blue-600 text-white font-bold rounded-lg hover:bg-blue-700 text-sm shadow">
                Cetak (100x150)
            </button>
        </div>
    </div>
</div>

<style>
    @media print {
        /* 1. SETUP UKURAN KERTAS */
        @page {
            size: 100mm 150mm; /* Lebar x Tinggi */
            margin: 0mm;       /* Hilangkan margin default browser */
        }

        /* 2. HILANGKAN ELEMEN WEBSITE LAIN */
        body * {
            visibility: hidden;
        }

        /* 3. TAMPILKAN HANYA STRUK */
        #invoice-card, #invoice-card * {
            visibility: visible;
        }

        /* 4. POSISIKAN STRUK */
        #invoice-card {
            position: fixed;
            left: 0;
            top: 0;
            width: 100mm;      /* Paksa lebar sesuai kertas */
            min-height: 150mm; /* Paksa tinggi minimal */
            padding: 5mm !important; /* Beri sedikit padding biar ga nempel tepi */
            margin: 0 !important;
            border: none !important;
            box-shadow: none !important;
            font-family: 'Courier New', Courier, monospace; /* Font struk biar jelas */
        }

        /* Hilangkan Navbar dll secara paksa */
        header, nav, footer, .navbar, .sidebar {
            display: none !important;
        }
    }
</style>
@endsection