@extends('layouts.marketplace')

@section('title', 'Isi Ulang Pulsa')

@push('styles')
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .product-card { transition: all 0.2s ease-in-out; }
        .product-card:hover { transform: translateY(-2px); border-color: #3b82f6; }
        /* Animasi loading */
        .loading-skeleton { background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%); background-size: 200% 100%; animation: loading 1.5s infinite; }
        @keyframes loading { 0% { background-position: 200% 0; } 100% { background-position: -200% 0; } }
    </style>
@endpush

@section('content')

{{-- ================================================================= --}}
    {{-- WIDGET SALDO DIGIFLAZZ (HANYA UNTUK ADMIN) --}}
    {{-- ================================================================= --}}
    @if(auth()->check() && auth()->user()->role === 'Admin')
    <div class="container mx-auto px-4 mt-6 max-w-3xl">
        <div class="bg-gradient-to-r from-indigo-600 to-blue-600 rounded-xl shadow-lg p-6 text-white relative overflow-hidden">
            
            {{-- Dekorasi Background --}}
            <div class="absolute right-0 top-0 opacity-10 transform translate-x-4 -translate-y-4">
                <i class="fas fa-wallet text-9xl"></i>
            </div>

            <div class="relative z-10 flex flex-col md:flex-row justify-between items-center gap-4">
                {{-- Info Saldo --}}
                <div>
                    <p class="text-blue-100 text-sm font-medium mb-1 flex items-center gap-2">
                        <i class="fas fa-coins"></i> Saldo Modal (Digiflazz)
                    </p>
                    <h2 class="text-3xl font-bold tracking-tight" id="digi-saldo">
                        Rp ...
                    </h2>
                    <p class="text-xs text-blue-200 mt-1">Pastikan saldo cukup sebelum transaksi.</p>
                </div>

                {{-- Tombol Refresh --}}
                <button onclick="refreshSaldo()" id="btn-refresh-saldo" 
                        class="group bg-white/20 hover:bg-white/30 text-white px-5 py-2.5 rounded-lg backdrop-blur-sm transition-all flex items-center gap-2 text-sm font-semibold border border-white/10 shadow-sm">
                    <i class="fas fa-sync-alt transition-transform group-hover:rotate-180" id="icon-refresh"></i> 
                    Cek Saldo Terbaru
                </button>
            </div>
        </div>
    </div>
    @endif
    {{-- ================================================================= --}}

<div class="bg-gray-100 min-h-screen py-8">
    <div class="container mx-auto px-4 max-w-3xl">
        
        {{-- Flash Message (Sukses/Error) --}}
        @if(session('success'))
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4 rounded shadow-sm relative" role="alert">
                <p>{{ session('success') }}</p>
            </div>
        @endif
        @if(session('error'))
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4 rounded shadow-sm relative" role="alert">
                <p>{{ session('error') }}</p>
            </div>
        @endif

        {{-- Header & Input Nomor --}}
        <div class="bg-white rounded-xl shadow-md p-6 mb-6 relative overflow-hidden">
            {{-- Tombol Sync (Pojok Kanan Atas) --}}
            <div class="absolute top-0 right-0 p-4">
                <a href="{{ route('ppob.sync') }}" class="text-xs bg-blue-50 text-blue-600 hover:bg-blue-100 px-3 py-1.5 rounded-full font-medium transition flex items-center gap-1" title="Perbarui Harga dari Pusat">
                    <i class="fas fa-sync-alt"></i> Update Harga
                </a>
            </div>

            <h1 class="text-xl font-bold text-gray-800 mb-6 flex items-center">
                <span class="bg-blue-100 text-blue-600 p-2 rounded-lg mr-3">
                    <i class="fas fa-mobile-alt"></i>
                </span>
                Isi Ulang Pulsa
            </h1>

            {{-- Input Nomor HP --}}
            <div class="relative group">
                <label class="block text-sm font-medium text-gray-700 mb-1 ml-1">Nomor Handphone</label>
                <div class="relative">
                    <input type="tel" id="phone_number" 
                           class="w-full border border-gray-300 rounded-xl pl-4 pr-12 py-4 text-xl font-semibold tracking-wide focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all shadow-sm" 
                           placeholder="Contoh: 0812xxxx"
                           oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                    
                    {{-- Ikon Operator (Muncul Otomatis) --}}
                    <div class="absolute right-4 top-1/2 transform -translate-y-1/2">
                        <img id="operator_logo" src="" alt="" class="h-8 w-auto hidden object-contain">
                        <i id="default_icon" class="fas fa-address-book text-gray-400 text-xl"></i>
                    </div>
                </div>
                <p id="operator_name" class="text-sm text-blue-600 font-bold mt-2 ml-1 h-5 transition-opacity duration-300"></p>
            </div>
        </div>

        {{-- Daftar Produk Grid --}}
        <div class="bg-white rounded-xl shadow-md p-6 min-h-[300px]">
            <h2 class="text-lg font-bold text-gray-800 mb-4 border-b pb-2">Pilih Nominal</h2>
            
            {{-- State Kosong (Belum Input Nomor) --}}
            <div id="empty_state" class="flex flex-col items-center justify-center py-10 text-center">
                <div class="bg-gray-50 p-6 rounded-full mb-4">
                    <i class="fas fa-keyboard text-4xl text-gray-300"></i>
                </div>
                <p class="text-gray-500 font-medium">Masukkan nomor HP untuk melihat pilihan paket.</p>
            </div>

            {{-- State Tidak Ditemukan --}}
            <div id="not_found_state" class="hidden flex flex-col items-center justify-center py-10 text-center">
                <div class="bg-red-50 p-6 rounded-full mb-4">
                    <i class="fas fa-exclamation-triangle text-4xl text-red-300"></i>
                </div>
                <p class="text-gray-500 font-medium">Operator tidak dikenali atau produk tidak tersedia.</p>
            </div>

            {{-- GRID PRODUK --}}
            <div id="product_grid" class="grid grid-cols-2 md:grid-cols-3 gap-4 hidden">
                @foreach($products as $product)
                    {{-- 
                        Data Attributes untuk Filtering JS 
                        Pastikan brand di database UPPERCASE agar sesuai logika JS
                    --}}
                    <div class="product-item hidden group relative bg-white border border-gray-200 rounded-xl p-4 cursor-pointer hover:shadow-lg hover:border-blue-500 transition-all duration-200"
                         data-brand="{{ strtoupper($product->brand) }}"
                         data-sku="{{ $product->buyer_sku_code }}"
                         data-price="{{ $product->sell_price }}"
                         data-name="{{ $product->product_name }}"
                         data-status="{{ $product->buyer_product_status && $product->seller_product_status ? '1' : '0' }}"
                         onclick="selectProduct(this)">
                        
                        {{-- Badge Brand --}}
                        <div class="flex justify-between items-start mb-3">
                            <span class="text-[10px] font-bold bg-gray-100 text-gray-600 px-2 py-1 rounded uppercase tracking-wider">
                                {{ $product->brand }}
                            </span>
                            @if(!$product->buyer_product_status || !$product->seller_product_status)
                                <span class="text-[10px] font-bold bg-red-100 text-red-600 px-2 py-1 rounded">
                                    GANGGUAN
                                </span>
                            @elseif($product->stock < 1 && !$product->unlimited_stock)
                                <span class="text-[10px] font-bold bg-orange-100 text-orange-600 px-2 py-1 rounded">
                                    HABIS
                                </span>
                            @endif
                        </div>

                        {{-- Nama Produk --}}
                        <h3 class="text-sm font-semibold text-gray-800 leading-tight mb-2 group-hover:text-blue-600">
                            {{ $product->product_name }}
                        </h3>
                        
                        {{-- Harga Jual --}}
                        <div class="flex items-end justify-between mt-auto pt-2 border-t border-dashed border-gray-100">
                            <div>
                                <p class="text-xs text-gray-400">Harga</p>
                                <p class="text-lg font-bold text-blue-600">
                                    Rp{{ number_format($product->sell_price, 0, ',', '.') }}
                                </p>
                            </div>
                            <div class="w-8 h-8 rounded-full bg-blue-50 text-blue-500 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity transform translate-y-2 group-hover:translate-y-0">
                                <i class="fas fa-shopping-cart text-sm"></i>
                            </div>
                        </div>

                        {{-- Overlay jika gangguan/habis --}}
                        @if((!$product->buyer_product_status || !$product->seller_product_status) || ($product->stock < 1 && !$product->unlimited_stock))
                            <div class="absolute inset-0 bg-white bg-opacity-60 cursor-not-allowed z-10 flex items-center justify-center">
                                <span class="bg-gray-800 text-white text-xs px-2 py-1 rounded shadow">Tidak Tersedia</span>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

{{-- FORM HIDDEN UNTUK CHECKOUT --}}
<form id="checkout_form" action="{{ route('ppob.store') }}" method="POST" class="hidden">
    @csrf
    <input type="hidden" name="buyer_sku_code" id="form_sku">
    <input type="hidden" name="customer_no" id="form_no">
    {{-- Anda bisa menambahkan input PIN atau Password di langkah konfirmasi selanjutnya --}}
</form>

{{-- MODAL KONFIRMASI (Opsional, mempercantik UX) --}}
<div id="confirmModal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" onclick="closeModal()"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg w-full">
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="sm:flex sm:items-start">
                    <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-blue-100 sm:mx-0 sm:h-10 sm:w-10">
                        <i class="fas fa-shopping-basket text-blue-600"></i>
                    </div>
                    <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                        <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">Konfirmasi Pembelian</h3>
                        <div class="mt-2 space-y-2 text-sm text-gray-500">
                            <p>Pastikan nomor tujuan sudah benar.</p>
                            <div class="bg-gray-50 p-3 rounded text-left">
                                <div class="flex justify-between mb-1">
                                    <span>Produk:</span>
                                    <span class="font-bold text-gray-800" id="modal_product_name">-</span>
                                </div>
                                <div class="flex justify-between mb-1">
                                    <span>Nomor HP:</span>
                                    <span class="font-bold text-gray-800" id="modal_customer_no">-</span>
                                </div>
                                <div class="flex justify-between border-t border-gray-200 pt-1 mt-1">
                                    <span>Harga:</span>
                                    <span class="font-bold text-blue-600 text-lg" id="modal_price">-</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <button type="button" onclick="submitCheckout()" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none sm:ml-3 sm:w-auto sm:text-sm">
                    Bayar Sekarang
                </button>
                <button type="button" onclick="closeModal()" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                    Batal
                </button>
            </div>
        </div>
    </div>
</div>

{{-- FORM HIDDEN UNTUK CHECKOUT --}}
<form id="checkout_form" action="{{ route('ppob.store') }}" method="POST" class="hidden">
    @csrf
    <input type="hidden" name="buyer_sku_code" id="form_sku">
    <input type="hidden" name="customer_no" id="form_no">
</form>

@endsection

@push('scripts')
<script>
    const phoneInput = document.getElementById('phone_number');
    const operatorLabel = document.getElementById('operator_name');
    const productGrid = document.getElementById('product_grid');
    const emptyState = document.getElementById('empty_state');
    const notFoundState = document.getElementById('not_found_state');
    const productItems = document.querySelectorAll('.product-item');
    const operatorLogo = document.getElementById('operator_logo');
    const defaultIcon = document.getElementById('default_icon');

    // Logo URL Asset (Sesuaikan dengan file yang Anda miliki atau gunakan CDN)
    // Jika tidak punya, biarkan defaultIcon muncul
    const logos = {
        'TELKOMSEL': 'https://upload.wikimedia.org/wikipedia/commons/b/bc/Telkomsel_2021_icon.svg',
        'INDOSAT': 'https://upload.wikimedia.org/wikipedia/commons/a/a1/Indosat_Ooredoo_Hutchison_logo.svg',
        'XL': 'https://upload.wikimedia.org/wikipedia/commons/5/53/XL_Axiata_logo.svg',
        'TRI': 'https://upload.wikimedia.org/wikipedia/commons/b/bb/Tri_Indonesia_2016_logo.svg',
        'AXIS': 'https://upload.wikimedia.org/wikipedia/commons/8/83/Axis_logo_2015.svg',
        'SMARTFREN': 'https://upload.wikimedia.org/wikipedia/commons/0/02/Smartfren_logo.svg'
    };

    function detectOperator(number) {
        // Regex Prefix Indonesia
        if (/^08(11|12|13|21|22|52|53|51)/.test(number)) return 'TELKOMSEL';
        if (/^08(14|15|16|55|56|57|58)/.test(number)) return 'INDOSAT';
        if (/^08(17|18|19|59|77|78)/.test(number)) return 'XL';
        if (/^08(95|96|97|98|99)/.test(number)) return 'TRI';
        if (/^08(81|82|83|84|85|86|87|88|89)/.test(number)) return 'SMARTFREN';
        if (/^08(31|32|33|38)/.test(number)) return 'AXIS';
        return null;
    }

    phoneInput.addEventListener('input', function(e) {
        const number = e.target.value;
        
        if (number.length >= 4) {
            const operator = detectOperator(number);
            
            if (operator) {
                // UI Update: Operator Ditemukan
                operatorLabel.textContent = operator;
                operatorLabel.classList.remove('opacity-0');
                
                // Ganti Icon/Logo
                if (logos[operator]) {
                    operatorLogo.src = logos[operator];
                    operatorLogo.classList.remove('hidden');
                    defaultIcon.classList.add('hidden');
                } else {
                    operatorLogo.classList.add('hidden');
                    defaultIcon.classList.remove('hidden');
                }

                // Filter Produk
                emptyState.classList.add('hidden');
                productGrid.classList.remove('hidden');
                
                let countVisible = 0;
                productItems.forEach(item => {
                    // Cek brand produk (harus sama persis uppercase)
                    if (item.dataset.brand === operator) {
                        item.classList.remove('hidden');
                        countVisible++;
                    } else {
                        item.classList.add('hidden');
                    }
                });

                if (countVisible === 0) {
                    notFoundState.classList.remove('hidden');
                    productGrid.classList.add('hidden');
                } else {
                    notFoundState.classList.add('hidden');
                }

            } else {
                // UI Update: Operator Tidak Dikenal
                operatorLabel.textContent = 'Operator tidak dikenali';
                operatorLogo.classList.add('hidden');
                defaultIcon.classList.remove('hidden');
                
                productItems.forEach(item => item.classList.add('hidden'));
                emptyState.classList.add('hidden');
                notFoundState.classList.remove('hidden');
            }
        } else {
            // UI Update: Nomor terlalu pendek
            operatorLabel.textContent = '';
            operatorLogo.classList.add('hidden');
            defaultIcon.classList.remove('hidden');
            
            productGrid.classList.add('hidden');
            emptyState.classList.remove('hidden');
            notFoundState.classList.add('hidden');
        }
    });

    // --- LOGIKA MODAL & CHECKOUT ---
    let selectedSku = null;

    function selectProduct(element) {
        const status = element.dataset.status;
        if (status === '0') {
            alert('Mohon maaf, produk ini sedang gangguan atau stok habis.');
            return;
        }

        const sku = element.dataset.sku;
        const name = element.dataset.name;
        const price = element.dataset.price;
        const phone = phoneInput.value;

        if (phone.length < 10) {
            alert('Mohon masukkan nomor HP yang valid terlebih dahulu.');
            phoneInput.focus();
            return;
        }

        // Isi Data Modal
        document.getElementById('modal_product_name').innerText = name;
        document.getElementById('modal_customer_no').innerText = phone;
        document.getElementById('modal_price').innerText = 'Rp ' + parseInt(price).toLocaleString('id-ID');
        
        // Isi Data Form
        document.getElementById('form_sku').value = sku;
        document.getElementById('form_no').value = phone;

        // Tampilkan Modal
        document.getElementById('confirmModal').classList.remove('hidden');
    }

    function closeModal() {
        document.getElementById('confirmModal').classList.add('hidden');
    }

    function submitCheckout() {
        // Disable tombol biar ga double klik
        const btn = document.querySelector('#confirmModal button[onclick="submitCheckout()"]');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Memproses...';
        
        document.getElementById('checkout_form').submit();
    }

    // --- SKRIP CEK SALDO DIGIFLAZZ ---
    function refreshSaldo() {
        const saldoEl = document.getElementById('digi-saldo');
        const icon = document.getElementById('icon-refresh');
        const btn = document.getElementById('btn-refresh-saldo');
        
        // Cek jika elemen ada (karena hanya admin yg punya elemen ini)
        if (!saldoEl) return;

        // Efek Loading
        saldoEl.innerText = 'Memuat...';
        saldoEl.classList.add('animate-pulse');
        icon.classList.add('fa-spin');
        btn.disabled = true;
        btn.classList.add('opacity-75', 'cursor-not-allowed');

        // Panggil Route Controller
        fetch("{{ route('ppob.cek-saldo') }}", {
            headers: {
                "X-Requested-With": "XMLHttpRequest",
                "Accept": "application/json"
            }
        })
        .then(response => response.json())
        .then(data => {
            // Hentikan Loading
            icon.classList.remove('fa-spin');
            saldoEl.classList.remove('animate-pulse');
            btn.disabled = false;
            btn.classList.remove('opacity-75', 'cursor-not-allowed');

            if(data.status) {
                // Sukses
                saldoEl.innerText = data.formatted;
            } else {
                // Gagal
                saldoEl.innerText = 'Error';
                console.error(data.message);
                alert('Gagal cek saldo: ' + data.message);
            }
        })
        .catch(error => {
            // Error Jaringan
            icon.classList.remove('fa-spin');
            saldoEl.classList.remove('animate-pulse');
            btn.disabled = false;
            saldoEl.innerText = 'Gagal Koneksi';
            console.error('Error:', error);
        });
    }

    // Jalankan otomatis saat halaman selesai dimuat (khusus Admin)
    document.addEventListener('DOMContentLoaded', () => {
        if(document.getElementById('digi-saldo')) {
            refreshSaldo();
        }
    });

</script>
@endpush