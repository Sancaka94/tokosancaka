@extends('layouts.marketplace')

@section('title', $pageInfo['title'] ?? 'Daftar Harga & Promo')

@push('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />
    <style>
        .scrollbar-hide::-webkit-scrollbar { display: none; }
        .scrollbar-hide { -ms-overflow-style: none; scrollbar-width: none; }
        .swiper-pagination-bullet-active { background-color: #ef4444 !important; width: 20px; border-radius: 5px; }
        
        .swiper-button-next, .swiper-button-prev {
            color: white; background-color: rgba(0,0,0,0.3); width: 35px; height: 35px; border-radius: 50%;
        }
        .swiper-button-next:after, .swiper-button-prev:after { font-size: 14px; font-weight: bold; }
        
        /* Animasi Loading */
        .loader { border: 3px solid #f3f3f3; border-radius: 50%; border-top: 3px solid #ef4444; width: 20px; height: 20px; animation: spin 1s linear infinite; }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
    </style>
@endpush

@section('content')
<div class="bg-gray-50 min-h-screen pb-20">
    
    <div class="container mx-auto px-4 pt-6 relative z-10 max-w-6xl">
        
        {{-- ================================================= --}}
        {{-- 1. HERO SECTION (SLIDER & BANNER)                 --}}
        {{-- ================================================= --}}
        <section class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-8">
            <div class="lg:col-span-2 rounded-2xl shadow-lg overflow-hidden relative group h-[200px] md:h-[350px]">
                <div class="swiper heroSwiper w-full h-full">
                    <div class="swiper-wrapper">
                        @forelse($banners as $banner)
                            <div class="swiper-slide">
                                <img src="{{ asset('public/storage/' . $banner->image) }}" class="w-full h-full object-fill" alt="Promo">
                            </div>
                        @empty
                            <div class="swiper-slide"><img src="https://placehold.co/800x400/ee4d2d/ffffff?text=Promo+Sancaka" class="w-full h-full object-fill"></div>
                        @endforelse
                    </div>
                    <div class="swiper-pagination"></div>
                    <div class="swiper-button-next opacity-0 group-hover:opacity-100 transition"></div>
                    <div class="swiper-button-prev opacity-0 group-hover:opacity-100 transition"></div>
                </div>
            </div>
            
            <div class="grid grid-cols-2 lg:grid-cols-1 lg:grid-rows-2 gap-4 h-auto lg:h-[350px]">
                <div class="rounded-2xl shadow-lg overflow-hidden h-[100px] md:h-full w-full">
                    <img src="{{ isset($settings['banner_2']) ? asset('public/storage/' . $settings['banner_2']) : 'https://placehold.co/400x200/fbbf24/ffffff?text=Promo+2' }}" class="w-full h-full object-fill hover:scale-105 transition">
                </div>
                <div class="rounded-2xl shadow-lg overflow-hidden h-[100px] md:h-full w-full">
                    <img src="{{ isset($settings['banner_3']) ? asset('public/storage/' . $settings['banner_3']) : 'https://placehold.co/400x200/10b981/ffffff?text=Promo+3' }}" class="w-full h-full object-fill hover:scale-105 transition">
                </div>
            </div>
        </section>

        {{-- ================================================= --}}
        {{-- 2. NAVIGASI ICON MENU                             --}}
        {{-- ================================================= --}}
        <section class="mb-8">
            <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100">
                <h2 class="text-sm font-bold mb-4 text-gray-700 uppercase tracking-wide">Kategori Layanan</h2>
                <div class="grid grid-cols-4 md:grid-cols-8 gap-4 text-center">
                    {{-- Icon Pulsa --}}
                    <a href="{{ route('public.category', 'pulsa') }}" class="group flex flex-col items-center gap-2 hover:-translate-y-1 transition">
                        <div class="w-12 h-12 rounded-xl bg-blue-50 flex items-center justify-center text-blue-600 group-hover:bg-blue-600 group-hover:text-white transition shadow-sm">
                            <i class="fas fa-mobile-screen-button text-xl"></i>
                        </div>
                        <span class="text-xs font-medium text-gray-600 group-hover:text-blue-600">Pulsa</span>
                    </a>
                    {{-- Icon Data --}}
                    <a href="{{ route('public.category', 'data') }}" class="group flex flex-col items-center gap-2 hover:-translate-y-1 transition">
                        <div class="w-12 h-12 rounded-xl bg-green-50 flex items-center justify-center text-green-600 group-hover:bg-green-600 group-hover:text-white transition shadow-sm">
                            <i class="fas fa-wifi text-xl"></i>
                        </div>
                        <span class="text-xs font-medium text-gray-600 group-hover:text-green-600">Data</span>
                    </a>
                    {{-- Icon PLN Token --}}
                    <a href="{{ route('public.category', 'pln-token') }}" class="group flex flex-col items-center gap-2 hover:-translate-y-1 transition">
                        <div class="w-12 h-12 rounded-xl bg-yellow-50 flex items-center justify-center text-yellow-500 group-hover:bg-yellow-500 group-hover:text-white transition shadow-sm">
                            <i class="fas fa-bolt text-xl"></i>
                        </div>
                        <span class="text-xs font-medium text-gray-600 group-hover:text-yellow-600">Token PLN</span>
                    </a>
                    {{-- Icon PLN Pasca --}}
                    <a href="{{ route('public.category', 'pln-pascabayar') }}" class="group flex flex-col items-center gap-2 hover:-translate-y-1 transition">
                        <div class="w-12 h-12 rounded-xl bg-orange-50 flex items-center justify-center text-orange-500 group-hover:bg-orange-500 group-hover:text-white transition shadow-sm">
                            <i class="fas fa-file-invoice-dollar text-xl"></i>
                        </div>
                        <span class="text-xs font-medium text-gray-600 group-hover:text-orange-600">PLN Pasca</span>
                    </a>
                    {{-- Icon PDAM --}}
                    <a href="{{ route('public.category', 'pdam') }}" class="group flex flex-col items-center gap-2 hover:-translate-y-1 transition">
                        <div class="w-12 h-12 rounded-xl bg-cyan-50 flex items-center justify-center text-cyan-600 group-hover:bg-cyan-600 group-hover:text-white transition shadow-sm">
                            <i class="fas fa-faucet text-xl"></i>
                        </div>
                        <span class="text-xs font-medium text-gray-600 group-hover:text-cyan-600">PDAM</span>
                    </a>
                    {{-- Icon E-Wallet --}}
                    <a href="{{ route('public.category', 'e-money') }}" class="group flex flex-col items-center gap-2 hover:-translate-y-1 transition">
                        <div class="w-12 h-12 rounded-xl bg-purple-50 flex items-center justify-center text-purple-600 group-hover:bg-purple-600 group-hover:text-white transition shadow-sm">
                            <i class="fas fa-wallet text-xl"></i>
                        </div>
                        <span class="text-xs font-medium text-gray-600 group-hover:text-purple-600">E-Wallet</span>
                    </a>
                    {{-- Icon Games --}}
                    <a href="{{ route('public.category', 'voucher-game') }}" class="group flex flex-col items-center gap-2 hover:-translate-y-1 transition">
                        <div class="w-12 h-12 rounded-xl bg-red-50 flex items-center justify-center text-red-600 group-hover:bg-red-600 group-hover:text-white transition shadow-sm">
                            <i class="fas fa-gamepad text-xl"></i>
                        </div>
                        <span class="text-xs font-medium text-gray-600 group-hover:text-red-600">Games</span>
                    </a>
                    {{-- Icon TV --}}
                    <a href="{{ route('public.category', 'streaming') }}" class="group flex flex-col items-center gap-2 hover:-translate-y-1 transition">
                        <div class="w-12 h-12 rounded-xl bg-pink-50 flex items-center justify-center text-pink-600 group-hover:bg-pink-600 group-hover:text-white transition shadow-sm">
                            <i class="fas fa-tv text-xl"></i>
                        </div>
                        <span class="text-xs font-medium text-gray-600 group-hover:text-pink-600">TV Kabel</span>
                    </a>
                </div>
            </div>
        </section>

        {{-- ================================================= --}}
        {{-- 3. KONTEN UTAMA (KONDISIONAL: PASCA / PRABAYAR)   --}}
        {{-- ================================================= --}}
        
        {{-- 🅰️ MODE PASCABAYAR (PLN Pasca, PDAM, BPJS) --}}
        @if(isset($pageInfo['is_postpaid']) && $pageInfo['is_postpaid'])
            <div class="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden max-w-3xl mx-auto">
                {{-- Header Card --}}
                <div class="bg-gradient-to-r from-blue-600 to-blue-500 p-6 text-white">
                    <h2 class="text-2xl font-bold flex items-center gap-3">
                        <i class="fas {{ $pageInfo['icon'] ?? 'fa-file-invoice' }}"></i> 
                        {{ $pageInfo['title'] ?? 'Cek Tagihan' }}
                    </h2>
                    <p class="text-blue-100 text-sm mt-1">Cek dan bayar tagihan bulanan Anda dengan mudah.</p>
                </div>

                <div class="p-6 md:p-8">
                    {{-- Form Input --}}
                    <div class="mb-6">
                        <label class="block text-sm font-bold text-gray-700 mb-2 ml-1">
                            {{ $pageInfo['input_label'] ?? 'Nomor Pelanggan / ID' }}
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <i class="fas fa-id-card text-gray-400"></i>
                            </div>
                            <input type="number" id="customer_no" 
                                class="w-full pl-12 pr-4 py-4 bg-gray-50 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition text-lg font-semibold text-gray-800 placeholder-gray-400"
                                placeholder="{{ $pageInfo['input_place'] ?? 'Contoh: 5300xxxx' }}">
                        </div>
                    </div>

                    {{-- Tombol Cek --}}
                    <button onclick="cekTagihan()" id="btn-cek-tagihan" 
                        class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-4 rounded-xl shadow-lg shadow-blue-200 transition transform hover:-translate-y-1 flex justify-center items-center gap-2">
                        <span>Cek Tagihan Sekarang</span>
                        <div id="loading-spinner" class="loader hidden border-t-white"></div>
                    </button>

                    {{-- Hasil Pengecekan --}}
                    <div id="bill_result" class="hidden mt-8 animate-fade-in-up">
                        <div class="bg-blue-50 border border-blue-200 rounded-xl p-6 relative overflow-hidden">
                            <div class="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-blue-100 rounded-full opacity-50 blur-xl"></div>
                            
                            <h3 class="text-blue-800 font-bold border-b border-blue-200 pb-2 mb-4">Rincian Tagihan</h3>
                            
                            <div class="space-y-3 relative z-10">
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-500">Nama Pelanggan</span>
                                    <span class="font-bold text-gray-800 text-right" id="bill_name">-</span>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-500">ID Pelanggan</span>
                                    <span class="font-bold text-gray-800 font-mono" id="bill_id">-</span>
                                </div>
                                <div class="border-t border-blue-200 pt-3 flex justify-between items-center mt-2">
                                    <span class="text-gray-600 font-bold">Total Tagihan</span>
                                    <span class="text-2xl font-extrabold text-blue-600" id="bill_amount">Rp 0</span>
                                </div>
                            </div>
                        </div>

                        <div class="mt-6">
                            <button onclick="bayarTagihan()" class="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-4 rounded-xl shadow-lg shadow-green-200 transition transform hover:scale-[1.02] flex items-center justify-center gap-2">
                                <i class="fas fa-check-circle"></i> Lanjut Pembayaran
                            </button>
                            <p class="text-center text-xs text-gray-400 mt-3">*Anda akan diarahkan ke halaman login untuk pembayaran.</p>
                        </div>
                    </div>

                    {{-- State Kosong / Error --}}
                    <div id="bill_empty" class="hidden mt-6 text-center p-6 bg-red-50 rounded-xl border border-red-100 text-red-600">
                        <i class="fas fa-exclamation-circle text-2xl mb-2"></i>
                        <p id="error_message" class="font-medium text-sm">Tagihan tidak ditemukan.</p>
                    </div>

                </div>
            </div>

        {{-- 🅱️ MODE PRABAYAR (Pulsa, Data, Token, Game) --}}
        @else
            {{-- Search & Filter --}}
            <div class="bg-white p-4 rounded-xl shadow-sm mb-6 border border-gray-100">
                <div class="relative mb-4">
                    <input type="text" id="searchInput" placeholder="Cari Produk (Telkomsel, Indosat, 10GB)..." 
                        class="w-full py-3 pl-12 pr-4 rounded-lg bg-gray-50 border border-gray-200 text-gray-800 focus:ring-2 focus:ring-blue-500 outline-none transition">
                    <div class="absolute top-1/2 left-4 transform -translate-y-1/2 text-gray-400"><i class="fas fa-search"></i></div>
                </div>
                
                {{-- Kategori Filter --}}
                <div class="overflow-x-auto whitespace-nowrap scrollbar-hide flex gap-2">
                    <button onclick="filterCategory('all')" class="cat-btn active px-5 py-2 rounded-lg font-bold text-xs bg-blue-600 text-white shadow-md transition">SEMUA</button>
                    @foreach($categories as $cat)
                        <button onclick="filterCategory('{{ $cat }}')" class="cat-btn px-5 py-2 rounded-lg font-bold text-xs bg-gray-100 text-gray-600 hover:bg-blue-50 hover:text-blue-600 transition">{{ strtoupper($cat) }}</button>
                    @endforeach
                </div>
            </div>

            {{-- Tabel Produk --}}
            <div class="bg-white rounded-xl shadow-lg overflow-hidden border border-gray-100">
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead class="bg-gray-50 text-gray-500 uppercase text-xs font-bold">
                            <tr>
                                <th class="p-4 border-b">Produk</th>
                                <th class="p-4 border-b hidden sm:table-cell">Detail</th>
                                <th class="p-4 border-b text-right">Harga</th>
                            </tr>
                        </thead>
                        <tbody id="productTableBody" class="text-sm divide-y divide-gray-100">
                            @foreach($products as $product)
                            <tr class="hover:bg-blue-50 transition duration-150" data-category="{{ $product->category }}" data-name="{{ strtolower($product->product_name . ' ' . $product->brand) }}">
                                <td class="p-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-lg bg-white p-1 border border-gray-200 flex-shrink-0">
                                            <img src="{{ get_operator_logo($product->brand) }}" class="w-full h-full object-contain" alt="{{ $product->brand }}" onerror="this.style.display='none'">
                                        </div>
                                        <div>
                                            <p class="font-bold text-gray-800 text-sm line-clamp-2">{{ $product->product_name }}</p>
                                            <p class="text-[10px] text-gray-400 uppercase hidden sm:block">{{ $product->category }} • {{ $product->brand }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="p-4 hidden sm:table-cell">
                                    <span class="text-xs text-gray-500 font-mono bg-gray-100 px-2 py-1 rounded">{{ $product->buyer_sku_code }}</span>
                                    <span class="bg-green-100 text-green-700 px-2 py-1 rounded text-[10px] font-bold ml-2">Ready</span>
                                </td>
                                <td class="p-4 text-right">
                                    <div class="font-bold text-blue-600 text-base">Rp{{ number_format($product->sell_price, 0, ',', '.') }}</div>
                                    <a href="{{ route('login') }}" class="text-[10px] text-blue-500 hover:underline sm:hidden">Beli</a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                
                {{-- Empty State --}}
                <div id="emptyState" class="hidden py-16 text-center">
                    <i class="fas fa-search text-3xl text-gray-300 mb-2"></i>
                    <p class="text-gray-500 text-sm">Produk tidak ditemukan.</p>
                </div>
            </div>
        @endif

    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
<script>
    // 1. Slider Banner
    var swiper = new Swiper(".heroSwiper", {
        loop: true, autoplay: { delay: 4000, disableOnInteraction: false },
        pagination: { el: ".swiper-pagination", clickable: true },
        navigation: { nextEl: ".swiper-button-next", prevEl: ".swiper-button-prev" },
    });

    // ==========================================
    // LOGIK A: UNTUK HALAMAN PRABAYAR (PULSA/DATA/DLL)
    // ==========================================
    @if(!isset($pageInfo['is_postpaid']) || !$pageInfo['is_postpaid'])
        const searchInput = document.getElementById('searchInput');
        // Gunakan selector yang lebih aman jika tabel tidak ada
        const tableRows = document.querySelectorAll('#productTableBody tr'); 
        const emptyState = document.getElementById('emptyState');
        let currentCategory = 'all';

        if(searchInput) {
            searchInput.addEventListener('keyup', filterTable);
        }

        function filterCategory(cat) {
            currentCategory = cat;
            document.querySelectorAll('.cat-btn').forEach(btn => {
                btn.classList.remove('bg-blue-600', 'text-white', 'shadow-md');
                btn.classList.add('bg-gray-100', 'text-gray-600');
            });
            // Highlight tombol aktif
            const activeBtn = Array.from(document.querySelectorAll('.cat-btn')).find(b => b.textContent.trim() === cat.toUpperCase() || (cat === 'all' && b.textContent.trim() === 'SEMUA'));
            if(activeBtn) {
                activeBtn.classList.remove('bg-gray-100', 'text-gray-600');
                activeBtn.classList.add('bg-blue-600', 'text-white', 'shadow-md');
            }
            filterTable();
        }

        function filterTable() {
            if(!searchInput) return;
            const filter = searchInput.value.toLowerCase();
            let visibleCount = 0;
            
            tableRows.forEach(row => {
                const name = row.getAttribute('data-name');
                const cat = row.getAttribute('data-category');
                
                // Pastikan atribut ada sebelum cek includes
                const matchSearch = name && name.includes(filter);
                const matchCat = currentCategory === 'all' || (cat && cat === currentCategory);
                
                if (matchSearch && matchCat) { 
                    row.style.display = ""; 
                    visibleCount++; 
                } else { 
                    row.style.display = "none"; 
                }
            });

            if(emptyState) {
                emptyState.classList.toggle('hidden', visibleCount > 0);
            }
        }
    @endif

    // ==========================================
    // LOGIK B: UNTUK HALAMAN PASCABAYAR (CEK TAGIHAN)
    // ==========================================
    @if(isset($pageInfo['is_postpaid']) && $pageInfo['is_postpaid'])
        let inquiryRefId = null;

        function cekTagihan() {
            const no = document.getElementById('customer_no').value;
            let skuPasca = 'pln'; 
            
            // Mapping SKU Otomatis
            @if(isset($pageInfo['slug']))
                @if($pageInfo['slug'] == 'pdam') skuPasca = 'pdam'; @endif
                @if($pageInfo['slug'] == 'bpjs') skuPasca = 'bpjs'; @endif
            @endif

            if(no.length < 5) { alert("Masukkan ID Pelanggan yang valid!"); return; }

            // UI Elements
            const btn = document.getElementById('btn-cek-tagihan');
            const spinner = document.getElementById('loading-spinner');
            const resultBox = document.getElementById('bill_result');
            const errorBox = document.getElementById('bill_empty');
            const errorMsg = document.getElementById('error_message');

            // Reset UI
            btn.disabled = true;
            btn.classList.add('opacity-75');
            spinner.classList.remove('hidden');
            if(resultBox) resultBox.classList.add('hidden');
            if(errorBox) errorBox.classList.add('hidden');

            // AJAX Request
            fetch('{{ route("ppob.check.bill") }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                body: JSON.stringify({ customer_no: no, sku: skuPasca })
            })
            .then(res => res.json())
            .then(data => {
                btn.disabled = false;
                btn.classList.remove('opacity-75');
                spinner.classList.add('hidden');

                if(data.status === 'success') {
                    document.getElementById('bill_name').innerText = data.customer_name;
                    document.getElementById('bill_id').innerText = data.customer_no;
                    document.getElementById('bill_amount').innerText = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(data.amount);
                    inquiryRefId = data.ref_id;
                    resultBox.classList.remove('hidden');
                } else {
                    if(errorMsg) errorMsg.innerText = data.message || 'Tagihan tidak ditemukan.';
                    errorBox.classList.remove('hidden');
                }
            })
            .catch(err => {
                btn.disabled = false;
                spinner.classList.add('hidden');
                if(errorMsg) errorMsg.innerText = "Terjadi kesalahan koneksi.";
                errorBox.classList.remove('hidden');
            });
        }

        function bayarTagihan() {
            if(!inquiryRefId) return;
            window.location.href = "{{ route('login') }}"; 
        }
    @endif
</script>
@endpush