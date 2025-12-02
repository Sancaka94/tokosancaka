@extends('layouts.marketplace')

@section('title', 'Isi Ulang Pulsa')

@push('styles')
    <script src="https://cdn.tailwindcss.com"></script>
@endpush

@section('content')
<div class="bg-gray-100 min-h-screen py-8">
    <div class="container mx-auto px-4 max-w-3xl">
        
        {{-- Header --}}
        <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
            <h1 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
                <i class="fas fa-mobile-alt text-blue-600 mr-3"></i> Isi Ulang Pulsa
            </h1>

            {{-- Input Nomor HP --}}
            <div class="relative">
                <label class="block text-sm font-medium text-gray-700 mb-1">Nomor Handphone</label>
                <input type="number" id="phone_number" class="w-full border border-gray-300 rounded-lg px-4 py-3 text-lg focus:ring-blue-500 focus:border-blue-500" placeholder="0812xxxx">
                <div class="absolute right-4 top-9 text-gray-400">
                    <i class="fas fa-address-book"></i>
                </div>
            </div>
            <p id="operator_name" class="text-sm text-green-600 font-semibold mt-2 h-5"></p>
        </div>

        {{-- Daftar Produk (Grid) --}}
        <div class="bg-white rounded-xl shadow-sm p-6">
            <h2 class="text-lg font-semibold text-gray-800 mb-4">Pilih Nominal</h2>
            
            {{-- Filter Operator (Hidden by default, shown by JS) --}}
            <div id="product_grid" class="grid grid-cols-2 md:grid-cols-3 gap-4">
                @foreach($products as $product)
                    <div class="product-item hidden border border-gray-200 rounded-lg p-4 cursor-pointer hover:border-blue-500 hover:bg-blue-50 transition"
                         data-brand="{{ $product['brand'] }}"
                         data-sku="{{ $product['buyer_sku_code'] }}"
                         data-price="{{ $product['price'] }}">
                        
                        <div class="flex justify-between items-start mb-2">
                            <span class="text-xs font-bold bg-gray-100 text-gray-600 px-2 py-1 rounded">{{ $product['brand'] }}</span>
                        </div>
                        <h3 class="text-md font-bold text-gray-800">{{ $product['product_name'] }}</h3>
                        {{-- Harga Jual (Contoh Markup 2000) --}}
                        <p class="text-blue-600 font-bold mt-1">Rp{{ number_format($product['price'] + 2000, 0, ',', '.') }}</p>
                    </div>
                @endforeach
            </div>

            <div id="empty_state" class="text-center py-10 text-gray-500">
                Masukkan nomor HP untuk melihat pilihan paket.
            </div>
        </div>

    </div>
</div>
@endsection

@push('scripts')
<script>
    const phoneInput = document.getElementById('phone_number');
    const operatorLabel = document.getElementById('operator_name');
    const productItems = document.querySelectorAll('.product-item');
    const emptyState = document.getElementById('empty_state');

    // Helper sederhana untuk deteksi operator berdasarkan prefix (bisa dikembangkan)
    function detectOperator(number) {
        if (number.startsWith('0811') || number.startsWith('0812') || number.startsWith('0813') || number.startsWith('0821') || number.startsWith('0822') || number.startsWith('0852') || number.startsWith('0853')) return 'TELKOMSEL';
        if (number.startsWith('0814') || number.startsWith('0815') || number.startsWith('0816') || number.startsWith('0855') || number.startsWith('0856') || number.startsWith('0857') || number.startsWith('0858')) return 'INDOSAT';
        if (number.startsWith('0817') || number.startsWith('0818') || number.startsWith('0819') || number.startsWith('0859') || number.startsWith('0877') || number.startsWith('0878')) return 'XL';
        if (number.startsWith('0896') || number.startsWith('0897') || number.startsWith('0898') || number.startsWith('0899')) return 'TRI';
        if (number.startsWith('0881') || number.startsWith('0882') || number.startsWith('0883') || number.startsWith('0884') || number.startsWith('0885') || number.startsWith('0886') || number.startsWith('0887') || number.startsWith('0888') || number.startsWith('0889')) return 'SMARTFREN';
        if (number.startsWith('0831') || number.startsWith('0832') || number.startsWith('0833') || number.startsWith('0838')) return 'AXIS';
        return null;
    }

    phoneInput.addEventListener('input', function(e) {
        const number = e.target.value;
        if (number.length >= 4) {
            const operator = detectOperator(number);
            
            if (operator) {
                operatorLabel.textContent = operator;
                emptyState.classList.add('hidden');
                
                // Filter produk
                let hasProduct = false;
                productItems.forEach(item => {
                    if (item.dataset.brand === operator) {
                        item.classList.remove('hidden');
                        hasProduct = true;
                    } else {
                        item.classList.add('hidden');
                    }
                });
            } else {
                operatorLabel.textContent = '';
                // Sembunyikan semua jika prefix tidak dikenal
                productItems.forEach(item => item.classList.add('hidden'));
                emptyState.classList.remove('hidden');
            }
        } else {
            operatorLabel.textContent = '';
            productItems.forEach(item => item.classList.add('hidden'));
            emptyState.classList.remove('hidden');
        }
    });
</script>
@endpush