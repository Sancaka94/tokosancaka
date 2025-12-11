@extends('layouts.marketplace')

@section('title', 'Invoice #' . $transaction->order_id)

@section('content')
<div class="min-h-screen bg-gray-100 flex items-center justify-center py-10 px-4 print:bg-white print:p-0 print:items-start">
    
    <div id="invoice-card" class="bg-white w-full max-w-md p-6 rounded-xl shadow-lg relative print:shadow-none print:w-full print:max-w-full print:p-0">
        
        <div class="text-center border-b-2 border-dashed border-gray-300 pb-5 mb-5">
            <h2 class="text-2xl font-extrabold text-blue-900 uppercase tracking-wider">Sancaka Store</h2>
            <p class="text-xs text-gray-500 mt-1">Pusat Belanja Online Terpercaya</p>
            
            <div class="mt-4">
                @if(strtolower($transaction->status) == 'success')
                    <span class="border-2 border-green-500 text-green-600 px-3 py-1 text-xs font-bold uppercase rounded-full tracking-wide">
                        BERHASIL
                    </span>
                @elseif(strtolower($transaction->status) == 'pending')
                    <span class="border-2 border-yellow-500 text-yellow-600 px-3 py-1 text-xs font-bold uppercase rounded-full tracking-wide">
                        PENDING
                    </span>
                @else
                    <span class="border-2 border-red-500 text-red-600 px-3 py-1 text-xs font-bold uppercase rounded-full tracking-wide">
                        GAGAL
                    </span>
                @endif
            </div>
        </div>

        <div class="space-y-3 text-sm mb-6">
            <div class="flex justify-between">
                <span class="text-gray-500">No. Invoice</span>
                <span class="font-mono font-bold text-gray-800">{{ $transaction->order_id }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-500">Tanggal</span>
                <span class="font-semibold text-gray-800">{{ $transaction->created_at->format('d/m/Y H:i') }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-500">Pembayaran</span>
                <span class="font-semibold text-gray-800 uppercase">{{ str_replace('_', ' ', $transaction->payment_method) }}</span>
            </div>
        </div>

        <div class="bg-gray-50 p-4 rounded-lg border border-gray-200 mb-6 print:border-black print:bg-transparent">
            <div class="flex justify-between items-start mb-2">
                <div>
                    <h3 class="font-bold text-gray-900">{{ strtoupper($transaction->buyer_sku_code) }}</h3>
                    <p class="text-xs text-gray-500 mt-0.5">ID: {{ $transaction->customer_no }}</p>
                </div>
                <div class="text-right">
                    <span class="block font-bold text-lg text-blue-600 print:text-black">
                        Rp {{ number_format($transaction->selling_price, 0, ',', '.') }}
                    </span>
                </div>
            </div>

            @if($transaction->sn)
            <div class="mt-4 pt-3 border-t border-dashed border-gray-300">
                <p class="text-[10px] text-center text-gray-400 uppercase tracking-widest mb-1">Serial Number / Token</p>
                <div class="bg-white border-2 border-gray-800 p-3 rounded text-center print:border-black">
                    <span class="font-mono font-bold text-xl tracking-widest text-gray-900 select-all">
                        {{ $transaction->sn }}
                    </span>
                </div>
            </div>
            @endif
        </div>

        <div class="text-center text-xs text-gray-400 mt-6 print:mt-10">
            <p>Terima kasih telah bertransaksi di Sancaka Store.</p>
            <p class="mt-1">Simpan struk ini sebagai bukti pembayaran yang sah.</p>
        </div>

        <div class="mt-8 flex gap-3 print:hidden">
            <a href="{{ route('customer.dashboard') }}" class="flex-1 py-3 bg-gray-200 text-gray-700 font-bold rounded-lg text-center hover:bg-gray-300 transition">
                Kembali
            </a>
            <button onclick="window.print()" class="flex-1 py-3 bg-blue-600 text-white font-bold rounded-lg hover:bg-blue-700 transition shadow-lg shadow-blue-200">
                Cetak Struk
            </button>
        </div>

        <div class="absolute -bottom-2 left-0 w-full h-4 bg-transparent print:hidden" 
             style="background-image: radial-gradient(circle, transparent 50%, #f3f4f6 50%); background-size: 20px 20px; background-position: 0 10px;">
        </div>
    </div>

</div>

<style>
    @media print {
        /* Sembunyikan SEMUA elemen body */
        body * {
            visibility: hidden;
            height: 0;
            overflow: hidden;
        }

        /* Kecuali elemen ID invoice-card dan anak-anaknya */
        #invoice-card, #invoice-card * {
            visibility: visible;
            height: auto;
            overflow: visible;
        }

        /* Posisikan Invoice di pojok kiri atas kertas */
        #invoice-card {
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
            margin: 0;
            padding: 0;
            box-shadow: none !important;
            border: none !important;
        }

        /* Hapus background browser */
        @page {
            margin: 0;
            size: auto;
        }
        
        /* Paksa sembunyikan navbar/footer jika class-nya diketahui (jaga-jaga) */
        header, nav, footer, .navbar, .sidebar {
            display: none !important;
        }
    }
</style>
@endsection