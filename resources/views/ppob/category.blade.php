@extends('layouts.marketplace')

@section('title', $pageInfo['title'])

@push('styles')
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .brand-radio:checked + div { border-color: #2563eb; background-color: #eff6ff; color: #1d4ed8; }
        .product-item:hover { transform: translateY(-2px); box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); }
    </style>
@endpush

@section('content')
<div class="bg-gray-100 min-h-screen py-8">
    <div class="container mx-auto px-4 max-w-4xl">
        
        {{-- Pesan Flash --}}
        @if(session('success')) <div class="bg-green-100 text-green-700 p-4 rounded mb-4">{{ session('success') }}</div> @endif
        @if(session('error')) <div class="bg-red-100 text-red-700 p-4 rounded mb-4">{{ session('error') }}</div> @endif

        <div class="flex flex-col md:flex-row gap-6">
            
            {{-- KOLOM KIRI: Input & Filter Brand --}}
            <div class="md:w-1/3 space-y-6">
                
                {{-- Card Input --}}
                <div class="bg-white rounded-xl shadow-sm p-6">
                    <h1 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2">
                        <i class="fas {{ $pageInfo['icon'] }} text-blue-600"></i> {{ $pageInfo['title'] }}
                    </h1>

                    <div class="mb-4">
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">{{ $pageInfo['input_label'] }}</label>
                        <input type="text" id="customer_no" 
                               class="w-full border border-gray-300 rounded-lg px-4 py-3 font-semibold focus:ring-2 focus:ring-blue-500 outline-none"
                               placeholder="{{ $pageInfo['input_place'] }}">
                        <p class="text-xs text-gray-400 mt-1">Pastikan nomor tujuan benar.</p>
                    </div>
                </div>

                {{-- Card Pilih Brand / Operator --}}
                <div class="bg-white rounded-xl shadow-sm p-6">
                    <h2 class="text-sm font-bold text-gray-700 mb-3">Pilih Operator / Produk</h2>
                    <div class="grid grid-cols-2 gap-2 max-h-96 overflow-y-auto pr-1">
                        {{-- Tombol "Semua" --}}
                        <label class="cursor-pointer">
                            <input type="radio" name="brand_filter" value="all" class="brand-radio hidden" checked onchange="filterProducts('all')">
                            <div class="border border-gray-200 rounded-lg p-2 text-center text-xs font-bold hover:bg-gray-50 transition">
                                SEMUA
                            </div>
                        </label>

                        {{-- Loop Brand dari Database --}}
                        @foreach($brands as $brand)
                        <label class="cursor-pointer">
                            <input type="radio" name="brand_filter" value="{{ $brand }}" class="brand-radio hidden" onchange="filterProducts('{{ $brand }}')">
                            <div class="border border-gray-200 rounded-lg p-2 text-center text-xs font-bold hover:bg-gray-50 transition uppercase">
                                {{ $brand }}
                            </div>
                        </label>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- KOLOM KANAN: Daftar Produk --}}
            <div class="md:w-2/3">
                <div class="bg-white rounded-xl shadow-sm p-6 min-h-[500px]">
                    <h2 class="text-lg font-bold text-gray-800 mb-4 border-b pb-2 flex justify-between items-center">
                        <span>Daftar Harga</span>
                        <a href="{{ route('ppob.sync') }}" class="text-xs text-blue-600 hover:underline"><i class="fas fa-sync"></i> Update</a>
                    </h2>

                    <div id="product_list" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        @foreach($products as $product)
                        <div class="product-item border border-gray-200 rounded-lg p-4 cursor-pointer relative bg-white transition-all duration-200"
                             data-brand="{{ $product->brand }}"
                             data-sku="{{ $product->buyer_sku_code }}"
                             data-name="{{ $product->product_name }}"
                             data-price="{{ $product->sell_price }}"
                             onclick="selectProduct(this)">
                            
                            {{-- Header Produk --}}
                            <div class="flex justify-between items-start mb-2">
                                <span class="bg-gray-100 text-gray-600 text-[10px] font-bold px-2 py-1 rounded uppercase">
                                    {{ $product->brand }}
                                </span>
                                @if($product->stock < 5 && !$product->unlimited_stock)
                                    <span class="bg-red-100 text-red-600 text-[10px] font-bold px-2 py-1 rounded">Sisa {{ $product->stock }}</span>
                                @endif
                            </div>

                            <h3 class="text-sm font-bold text-gray-800 mb-1 leading-tight">{{ $product->product_name }}</h3>
                            <p class="text-xs text-gray-500 mb-3 line-clamp-1">{{ $product->desc }}</p>
                            
                            <div class="flex justify-between items-end border-t border-dashed pt-2">
                                <div>
                                    <p class="text-[10px] text-gray-400">Harga</p>
                                    <p class="text-lg font-bold text-blue-600">Rp{{ number_format($product->sell_price, 0, ',', '.') }}</p>
                                </div>
                                <button class="bg-blue-50 text-blue-600 w-8 h-8 rounded-full flex items-center justify-center hover:bg-blue-600 hover:text-white transition">
                                    <i class="fas fa-shopping-cart text-xs"></i>
                                </button>
                            </div>
                        </div>
                        @endforeach
                    </div>

                    {{-- State Kosong --}}
                    <div id="empty_state" class="hidden flex flex-col items-center justify-center py-10 text-gray-400">
                        <i class="fas fa-search text-4xl mb-2"></i>
                        <p>Produk tidak ditemukan untuk operator ini.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- MODAL KONFIRMASI CHECKOUT --}}
<div id="confirmModal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-900 bg-opacity-50 transition-opacity" onclick="closeModal()"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white rounded-xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md w-full">
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="sm:flex sm:items-start">
                    <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-blue-100 sm:mx-0 sm:h-10 sm:w-10">
                        <i class="fas fa-wallet text-blue-600"></i>
                    </div>
                    <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                        <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">Konfirmasi Pembayaran</h3>
                        <div class="mt-4 bg-gray-50 p-4 rounded-lg space-y-2 text-sm">
                            <div class="flex justify-between">
                                <span class="text-gray-500">Nomor Tujuan</span>
                                <span class="font-bold text-gray-800" id="modal_no">-</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-500">Produk</span>
                                <span class="font-bold text-gray-800 text-right" id="modal_product">-</span>
                            </div>
                            <div class="border-t pt-2 mt-2 flex justify-between items-center">
                                <span class="text-gray-500">Total Bayar</span>
                                <span class="font-bold text-blue-600 text-lg" id="modal_price">-</span>
                            </div>
                        </div>
                        <p class="text-xs text-gray-400 mt-2 text-center">Saldo Anda akan terpotong otomatis.</p>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse gap-2">
                <form action="{{ route('ppob.store') }}" method="POST" id="checkoutForm" class="w-full sm:w-auto">
                    @csrf
                    <input type="hidden" name="buyer_sku_code" id="form_sku">
                    <input type="hidden" name="customer_no" id="form_no">
                    <button type="submit" class="w-full inline-flex justify-center rounded-lg border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none sm:text-sm">
                        Bayar Sekarang
                    </button>
                </form>
                <button type="button" onclick="closeModal()" class="mt-3 w-full inline-flex justify-center rounded-lg border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-100 focus:outline-none sm:mt-0 sm:w-auto sm:text-sm">
                    Batal
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    const items = document.querySelectorAll('.product-item');
    const emptyState = document.getElementById('empty_state');
    const inputNo = document.getElementById('customer_no');
    
    // Logic Filter Brand
    function filterProducts(brand) {
        let count = 0;
        items.forEach(item => {
            if (brand === 'all' || item.dataset.brand === brand) {
                item.classList.remove('hidden');
                count++;
            } else {
                item.classList.add('hidden');
            }
        });

        if (count === 0) {
            emptyState.classList.remove('hidden');
        } else {
            emptyState.classList.add('hidden');
        }
    }

    // Logic Deteksi Operator Otomatis (Opsional untuk Pulsa/Data)
    // Jika slugnya 'pulsa' atau 'data', kita aktifkan auto detect
    @if($pageInfo['slug'] == 'pulsa' || $pageInfo['slug'] == 'data')
    inputNo.addEventListener('input', function(e) {
        const val = e.target.value;
        if(val.length >= 4) {
            let operator = null;
            if(/^08(11|12|13|21|22|52|53|51)/.test(val)) operator = 'TELKOMSEL';
            else if(/^08(14|15|16|55|56|57|58)/.test(val)) operator = 'INDOSAT';
            else if(/^08(17|18|19|59|77|78)/.test(val)) operator = 'XL';
            else if(/^08(95|96|97|98|99)/.test(val)) operator = 'TRI';
            else if(/^08(81|82|83|84|85|86|87|88|89)/.test(val)) operator = 'SMARTFREN';
            else if(/^08(31|32|33|38)/.test(val)) operator = 'AXIS';

            if(operator) {
                // Cari radio button yg value-nya operator ini dan klik
                const radio = document.querySelector(`input[name="brand_filter"][value="${operator}"]`);
                if(radio) {
                    radio.checked = true;
                    filterProducts(operator);
                }
            }
        }
    });
    @endif

    // Logic Modal Checkout
    function selectProduct(el) {
        const no = inputNo.value;
        if(no.length < 5) {
            alert("{{ $pageInfo['input_label'] }} belum diisi dengan benar!");
            inputNo.focus();
            return;
        }

        // Isi data modal
        document.getElementById('modal_no').innerText = no;
        document.getElementById('modal_product').innerText = el.dataset.name;
        document.getElementById('modal_price').innerText = 'Rp ' + parseInt(el.dataset.price).toLocaleString('id-ID');
        
        // Isi form hidden
        document.getElementById('form_sku').value = el.dataset.sku;
        document.getElementById('form_no').value = no;

        // Tampilkan modal
        document.getElementById('confirmModal').classList.remove('hidden');
    }

    function closeModal() {
        document.getElementById('confirmModal').classList.add('hidden');
    }
</script>
@endpush
