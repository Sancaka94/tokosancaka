@extends('layouts.marketplace')

@section('title', $product->name . ' - Sancaka Marketplace')

@push('styles')
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body { font-family: 'Inter', sans-serif; -webkit-font-smoothing: antialiased; -moz-osx-font-smoothing: grayscale; }
        :root {
            --shopee-red: #d73210;
            --shopee-red-dark: #d73210;
            --shopee-red-light: rgba(255, 87, 34, 0.1);
        }
        .thumbnail-active { 
            outline: 2px solid var(--shopee-red); 
            outline-offset: 1px;
            border-color: var(--shopee-red) !important;
        }
        input[type=number]::-webkit-inner-spin-button, 
        input[type=number]::-webkit-outer-spin-button { 
            -webkit-appearance: none; 
            margin: 0; 
        }
        input[type=number] { 
            -moz-appearance: textfield; 
        }
        button:disabled, input:disabled { 
            opacity: 0.6; 
            cursor: not-allowed; 
        }
        .btn-shopee-outline { 
            background-color: var(--shopee-red-light); 
            border: 1px solid var(--shopee-red); 
            color: var(--shopee-red); 
            transition: background-color 0.2s ease;
        }
        .btn-shopee-outline:hover:not(:disabled) { 
            background-color: rgba(255, 87, 34, 0.15); 
        }
        .btn-shopee-solid { 
            background-color: var(--shopee-red); 
            border: 1px solid var(--shopee-red); 
            color: white; 
            transition: background-color 0.2s ease;
        }
        .btn-shopee-solid:hover:not(:disabled) { 
            background-color: var(--shopee-red-dark); 
        }
        .discount-badge {
            background-color: var(--shopee-red);
            color: white;
            padding: 0.25rem 0.5rem;
            border-radius: 0.125rem;
            font-size: 0.75rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
        }
        .variant-btn-container {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
        }
        .variant-btn {
            border: 1px solid #e5e7eb;
            padding: 0.5rem 1rem;
            border-radius: 0.25rem;
            cursor: pointer;
            transition: all 0.2s ease;
            background-color: white;
            color: #374151;
            font-size: 0.875rem;
            line-height: 1.25rem;
        }
        .variant-btn:hover:not(:disabled) {
            border-color: var(--shopee-red);
            color: var(--shopee-red);
        }
        .variant-btn.active {
            border-color: var(--shopee-red);
            background-color: var(--shopee-red-light);
            color: var(--shopee-red);
            font-weight: 600;
        }
        .variant-btn:disabled {
            background-color: #f3f4f6;
            color: #9ca3af;
            cursor: not-allowed;
            border-color: #e5e7eb;
            text-decoration: line-through;
        }
        .prose { font-size: 0.9375rem; line-height: 1.6; }
        .prose p, .prose ul, .prose ol { margin-top: 0.75em; margin-bottom: 0.75em; }
        .prose li { margin-top: 0.25em; margin-bottom: 0.25em; }
        .dark .prose-invert { color: #d1d5db; }
        .prose h1, .prose h2, .prose h3 { color: #111827; margin-bottom: 0.5em; }
        .dark .prose-invert h1, .dark .prose-invert h2, .dark .prose-invert h3 { color: #f3f4f6; }
        .prose a { color: #2563eb; }
        .dark .prose-invert a { color: #60a5fa; }
    </style>
@endpush

@section('content')
@php
if (!function_exists('formatWaNumber')) {
    function formatWaNumber($number) {
        if(empty($number)) return '';
        $number = preg_replace('/[^0-9]/', '', $number);
        if (substr($number, 0, 1) === '0') {
            return '62' . substr($number, 1);
        } elseif (substr($number, 0, 2) !== '62') {
            return '62' . $number;
        }
        return $number;
    }
}
@endphp

@php
    $hasVariants = $product->productVariantTypes->isNotEmpty() && $product->productVariants->isNotEmpty();
    $initialStock = (int)(!$hasVariants ? $product->stock : 0);
@endphp

<div class="bg-gray-100 text-gray-800 min-h-screen">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-6 md:py-8">
        {{-- Breadcrumb --}}
        <nav aria-label="Breadcrumb" class="mb-6 text-sm text-gray-500">
             <ol class="flex items-center space-x-2 overflow-x-auto whitespace-nowrap py-1">
                 <li><a href="{{ route('etalase.index') }}" class="hover:text-red-600">Sancaka</a></li>
                 <li><i class="fas fa-chevron-right text-xs mx-1"></i></li>
                 
                 @if($product->categoryData)
                 <li><a href="{{ route('etalase.index', ['categories' => [$product->categoryData->id]]) }}" class="hover:text-red-600">{{ $product->categoryData->name }}</a></li>
                 <li><i class="fas fa-chevron-right text-xs mx-1"></i></li>
                 @endif
                 
                 <li class="font-medium text-gray-700 truncate">{{ $product->name }}</li>
             </ol>
        </nav>

        {{-- Main Product Section --}}
        <main class="bg-white rounded-lg shadow-sm p-4 md:p-6 lg:p-8">
            <div class="grid grid-cols-1 md:grid-cols-5 gap-6 lg:gap-8">
                {{-- Product Image Gallery --}}
                <div class="md:col-span-2 image-gallery">
                    @php
                        // URL Gambar Utama (Default)
                        $imageUrl = $product->image_url ? asset('public/storage/' . $product->image_url) : 'https://placehold.co/600x600/EFEFEF/AAAAAA?text=Gambar+Tidak+Ada';
                    @endphp
                    
                    {{-- Gambar Besar (Preview) --}}
                    <div class="aspect-w-1 aspect-h-1 w-full overflow-hidden rounded-md shadow-sm mb-3 border border-gray-200">
                        <img id="main-product-image"
                             src="{{ $imageUrl }}"
                             alt="{{ $product->name }}"
                             class="w-full h-full object-contain object-center"
                             onerror="this.onerror=null;this.src='https://placehold.co/600x600/EFEFEF/AAAAAA?text=Gambar+Error';">
                    </div>

                    {{-- === GALERI THUMBNAIL (DIPERBAIKI) === --}}
                    <div class="grid grid-cols-5 gap-2 mt-2">
                        @if($product->images && $product->images->count() > 0)
                            {{-- Loop Gambar dari Database (ProductImage) --}}
                            @foreach($product->images->sortBy('sort_order') as $media)
                                <div class="aspect-square w-full h-full overflow-hidden rounded cursor-pointer">
                                    <img src="{{ asset('public/storage/' . $media->path) }}" 
                                         alt="Gambar {{ $loop->iteration }}" 
                                         class="thumbnail-img w-full h-full object-cover border-2 {{ $loop->first ? 'thumbnail-active' : 'border-transparent' }} hover:border-red-500 transition-all duration-200" 
                                         onclick="changeImage(this)" 
                                         onerror="this.onerror=null;this.style.display='none';">
                                </div>
                            @endforeach
                        @else
                            {{-- Fallback jika hanya ada 1 gambar di tabel products --}}
                            <div class="aspect-square w-full h-full overflow-hidden rounded cursor-pointer">
                                <img src="{{ $imageUrl }}" 
                                     alt="Thumbnail" 
                                     class="thumbnail-img w-full h-full object-cover border-2 thumbnail-active hover:border-red-500" 
                                     onclick="changeImage(this)">
                            </div>
                        @endif
                    </div>
                    {{-- === AKHIR GALERI THUMBNAIL === --}}
                </div>

                {{-- Product Info & Action --}}
                <div class="md:col-span-3 product-info">
                    <h1 class="text-xl lg:text-2xl font-semibold text-gray-900 mb-2 leading-tight">{{ $product->name }}</h1>

                    <div class="flex items-center space-x-3 mb-4 text-sm text-gray-500 border-b border-gray-100 pb-4">
                        <div class="flex items-center">
                            <span class="font-semibold text-red-500 mr-1">{{ number_format($product->rating ?? 0, 1) }}</span>
                            <div class="flex text-red-400">
                                @for ($i = 1; $i <= 5; $i++)
                                    <i class="fas fa-star {{ ($product->rating ?? 0) >= $i ? 'text-red-400' : 'text-gray-300' }} text-xs"></i>
                                @endfor
                            </div>
                        </div>
                        <span class="text-gray-300 mx-1">|</span>
                        <div><span class="font-semibold text-gray-700">{{ $product->reviews_count ?? 0 }}</span> Penilaian</div>
                        <span class="text-gray-300 mx-1">|</span>
                        <div><span class="font-semibold text-gray-700">{{ $product->sold_count ?? 0 }}</span> Terjual</div>
                    </div>

                    <div class="bg-gray-50 rounded-md p-4 my-4 flex items-center flex-wrap gap-x-4 gap-y-2">
                        <span id="display-original-price" class="text-base text-gray-400 line-through {{ ($product->original_price && $product->original_price > $product->price && !$hasVariants) ? '' : 'hidden' }}">
                            Rp{{ number_format($product->original_price, 0, ',', '.') }}
                        </span>

                        <span id="display-price" class="text-2xl lg:text-3xl font-bold text-red-600">
                           Rp{{ number_format($product->price, 0, ',', '.') }}
                        </span>

                        @if($product->original_price && $product->original_price > $product->price && !$hasVariants)
                        <span id="display-discount" class="discount-badge">
                            {{ round((($product->original_price - $product->price) / $product->original_price) * 100) }}% OFF
                        </span>
                        @else
                        <span id="display-discount" class="discount-badge hidden"></span>
                        @endif
                    </div>

                    <form id="add-to-cart-form" action="{{ route('cart.add') }}" method="POST">
                        @csrf
                        <input type="hidden" name="product_id" value="{{ $product->id }}">
                        <input type="hidden" id="product_variant_id" name="product_variant_id" value="">

                        {{-- === TATA LETAK SESUAI PERMINTAAN: VARIAN DI ATAS KUANTITAS === --}}
                        @if ($hasVariants)
                            <div class="space-y-4 mb-6 pt-4 border-t border-gray-100">
                                @foreach ($product->productVariantTypes as $type)
                                <div class="variant-group" data-type-name="{{ $type->name }}">
                                    <div class="flex flex-col sm:flex-row sm:items-center">
                                        <label class="w-24 text-sm text-gray-500 flex-shrink-0 mb-2 sm:mb-0">{{ $type->name }}</label>
                                        <div class="variant-btn-container">
                                            @foreach ($type->options as $option)
                                            <button type="button" class="variant-btn" data-option-value="{{ $option->value }}">
                                                {{ $option->value }}
                                            </button>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        @endif
                        {{-- === AKHIR VARIAN === --}}


                        {{-- Quantity Selector --}}
                        <div class="mt-6 flex items-center">
                            <label for="quantity" class="w-24 text-sm text-gray-500 flex-shrink-0">Kuantitas</label>
                            <div class="flex items-center">
                                <div class="flex items-center border border-gray-300 rounded-sm overflow-hidden">
                                    <button id="button-minus" type="button" class="w-9 h-9 flex items-center justify-center text-gray-600 hover:text-red-600 transition-colors disabled:text-gray-300" disabled>
                                        <i class="fas fa-minus text-xs"></i>
                                    </button>
                                    <input type="number" id="quantity" name="quantity" class="w-12 h-9 text-center text-sm bg-transparent border-x border-gray-300 focus:outline-none focus:ring-0 disabled:bg-gray-100" 
                                           value="1" min="1" 
                                           max="{{ $initialStock > 0 ? $initialStock : 1 }}" 
                                           {{ $initialStock <= 0 ? 'disabled' : '' }}>
                                    <button id="button-plus" type="button" class="w-9 h-9 flex items-center justify-center text-gray-600 hover:text-red-600 transition-colors disabled:text-gray-300" {{ $initialStock <= 1 ? 'disabled' : '' }}>
                                        <i class="fas fa-plus text-xs"></i>
                                    </button>
                                </div>
                                
                                <span id="display-stock" class="ml-4 text-sm text-gray-500">
                                    @if($initialStock > 0)
                                        Tersisa {{ $initialStock }} buah
                                    @elseif (!$hasVariants && $initialStock <= 0)
                                        Stok habis
                                    @else
                                        Pilih varian
                                    @endif
                                </span>
                            </div>
                        </div>

                        {{-- Action Buttons --}}
<div class="mt-8 pt-6 border-t border-gray-100">
    <div class="flex flex-col sm:flex-row items-center gap-3">

        {{-- Tombol Masukkan Keranjang (Merah Outline) --}}
        <button id="add-to-cart-button"
            type="submit"
            name="action"
            value="add_to_cart"
            class="w-full sm:w-auto flex-1 flex items-center justify-center gap-2 px-6 py-3 
                   text-sm rounded-sm font-medium border border-red-600 text-red-600
                   hover:bg-red-50 transition disabled:opacity-50 disabled:cursor-not-allowed"
            {{ $initialStock <= 0 ? 'disabled' : '' }}>
            <i class="fas fa-cart-plus text-base"></i> Masukkan Keranjang
        </button>

        {{-- Tombol Beli Sekarang (Merah Solid) --}}
        <button id="buy-now-button"
            type="submit"
            name="action"
            value="buy_now"
            class="w-full sm:w-auto flex-1 flex items-center justify-center gap-2 px-6 py-3 
                   text-sm rounded-sm font-medium bg-red-600 text-white
                   hover:bg-red-700 transition disabled:opacity-50 disabled:cursor-not-allowed"
            {{ $initialStock <= 0 ? 'disabled' : '' }}>
            Beli Sekarang
        </button>

    </div>
</div>

                    </form>
                    
                </div>
            </div>
        </main>

 
@if ($product->store && $product->store->user)
<div class="mt-8 bg-white rounded-lg shadow-sm p-4 md:p-6">
    <div class="flex flex-col sm:flex-row items-start sm:items-center gap-4">
@php
    // 1. Ambil path logo dari relasi 'store.user'
    $sellerLogo = $product->store->user->store_logo_path;
    $sellerStoreName = $product->store->user->store_name;

    // 2. Bersihkan prefix "public/" jika ada
    if ($sellerLogo && Str::startsWith($sellerLogo, 'public/')) {
        $sellerLogo = Str::remove('public/', $sellerLogo);
    }

    // 3. Buat URL lengkap
    $sellerLogoUrl = $sellerLogo
        ? asset('public/storage/' . $sellerLogo)
        : 'https://placehold.co/64x64/E2E8F0/AAAAAA?text=Toko';

    // 4. Slug atau nama toko untuk route
    $tokoProfileParam = $product->store->slug ?? $product->store->name;
@endphp

        <a href="{{ Route::has('toko.profile') ? route('toko.profile', ['name' => $tokoProfileParam]) : '#' }}" class="flex-shrink-0">
            <img src="{{ $sellerLogoUrl }}"
                 alt="Logo {{ $sellerStoreName }}"
                 class="w-16 h-16 rounded-full border border-gray-200 object-cover">
        </a>

        <div class="flex-grow">
            <a href="{{ Route::has('toko.profile') ? route('toko.profile', ['name' => $tokoProfileParam]) : '#' }}" class="hover:underline">
                <h3 class="text-base font-bold text-gray-900">{{ $sellerStoreName }}</h3>
            </a>

            {{-- ✅ [PERBAIKAN DINAMIS] Dari 'Offline' menjadi dinamis --}}
                        @php
                            $lastSeen = \Carbon\Carbon::parse($product->store->user->last_seen_at);
                            $isOnline = $lastSeen->diffInMinutes(now()) < 10;
                        @endphp
                        <div class="flex items-center text-xs text-gray-500 mt-1">
                            <span class="w-2 h-2 {{ $isOnline ? 'bg-green-500' : 'bg-gray-400' }} rounded-full mr-1.5"></span>
                            <span>{{ $isOnline ? 'Online' : 'Aktif ' . $lastSeen->diffForHumans() }}</span>
                        </div>

            @if($product->store->user->regency)
                <div class="flex items-center text-xs text-gray-500 mt-1">
                    <i class="fas fa-map-marker-alt mr-1.5"></i> {{ $product->store->user->regency }}
                </div>
            @endif
        </div>

        <div class="flex w-full sm:w-auto flex-col sm:flex-row gap-3 mt-4 sm:mt-0 self-stretch sm:self-center">
            @if(Auth::check() && $product->store->user->no_wa)
                @php $wa_number = formatWaNumber($product->store->user->no_wa); @endphp
                <a href="https://wa.me/{{ $wa_number }}?text=Halo%2C%20saya%20tertarik%20dengan%20produk%20Anda%3A%20{{ urlencode($product->name) }}"
                   target="_blank"
                   class="w-full sm:w-auto flex items-center justify-center gap-2 px-4 py-2 text-sm border border-gray-300 rounded transition-colors hover:bg-gray-100">
                    <i class="fab fa-whatsapp text-green-500"></i> Chat
                </a>
            @endif

            @if(Route::has('toko.profile'))
                <a href="{{ route('toko.profile', ['name' => $tokoProfileParam]) }}"
                   class="w-full sm:w-auto flex items-center justify-center gap-2 px-4 py-2 text-sm border border-gray-300 rounded hover:bg-gray-100 transition-colors">
                    <i class="fas fa-store text-gray-500"></i> Kunjungi Toko
                </a>
            @endif
        </div>

    </div>
</div>
@endif
{{-- === AKHIR BLOK PERBAIKAN === --}}

{{-- === TATA LETAK SESUAI PERMINTAAN: SPESIFIKASI DI ATAS DESKRIPSI === --}}
@php
    // Helper untuk Decode JSON Value (Array Detection)
    $parseSpecValue = function($value) {
        if (is_string($value) && str_starts_with(trim($value), '[')) {
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $decoded; // Return Array
            }
        }
        return $value; // Return String Biasa
    };

    // 1. Ambil semua atribut yang memiliki NILAI
    $groupedAttributes = $product->productAttributes->filter(function($attr) {
        return !empty($attr->value);
    })->groupBy(function($attr) {
        // Jika nama kosong, masukkan ke grup "Info Lainnya"
        return !empty($attr->name) ? $attr->name : 'Info Lainnya';
    });
@endphp

{{-- Tampilkan blok jika ada data spesifikasi --}}
@if ($groupedAttributes->isNotEmpty())
    <div class="mt-8 bg-white rounded-lg shadow-sm border border-gray-100 p-4 md:p-6">
        <h2 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2">
            <span class="bg-indigo-100 text-indigo-600 p-1 rounded">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                </svg>
            </span>
            Spesifikasi Produk
        </h2>
        
        <div class="space-y-4 divide-y divide-gray-100">
            @foreach ($groupedAttributes as $name => $items)
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 py-3 first:pt-0">
                    {{-- Nama Atribut --}}
                    <dt class="text-sm font-medium text-gray-500 capitalize pt-1">
                        {{ $name }}
                    </dt>
                    
                    {{-- Nilai Atribut --}}
                    <dd class="col-span-1 sm:col-span-2 text-sm text-gray-800 font-medium">
                        @foreach ($items as $item)
                            @php $parsedVal = $parseSpecValue($item->value); @endphp

                            @if (is_array($parsedVal))
                                {{-- Jika Array (Checkbox) -> Tampilkan Badge --}}
                                <div class="flex flex-wrap gap-2">
                                    @foreach ($parsedVal as $val)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded text-xs font-medium bg-indigo-50 text-indigo-700 border border-indigo-100">
                                            {{ $val }}
                                        </span>
                                    @endforeach
                                </div>
                            @else
                                {{-- Jika String Biasa -> Teks --}}
                                <p class="leading-relaxed">{{ $parsedVal }}</p>
                            @endif
                        @endforeach
                    </dd>
                </div>
            @endforeach
        </div>
    </div>
@endif
{{-- === AKHIR SPESIFIKASI PRODUK === --}}

        {{-- Product Description Section --}}
        <div class="mt-8 bg-white rounded-lg shadow-sm p-4 md:p-6">
            <h2 class="text-lg font-semibold text-gray-800 mb-4">Deskripsi Produk</h2>
            <div id="short-description" class="prose prose-sm max-w-none break-words leading-relaxed">
                {!! nl2br(e(Str::limit($product->description, 350))) !!}
            </div>
            <div id="full-description" class="prose prose-sm max-w-none break-words leading-relaxed hidden">
                {!! nl2br(e($product->description)) !!}
            </div>
            @if(strlen($product->description ?? '') > 350)
            <div class="text-center mt-4">
                 <button id="toggle-description" class="text-sm text-red-600 font-medium hover:underline">
                     Baca Selengkapnya <i class="fas fa-chevron-down text-xs ml-1"></i>
                 </button>
            </div>
            @endif
        </div>

    </div>
</div>
@endsection

@push('scripts')
{{-- Kirim data Varian dari PHP ke JavaScript --}}
<script>
    window.variantData = @json($variantData ?? ['variants' => [], 'types' => []]);
    window.initialProduct = {
        price: {{ $product->price ?? 0 }},
        original_price: {{ $product->original_price ?? 0 }},
        stock: {{ (int)$product->stock ?? 0 }}
    };
    window.hasVariants = {{ $hasVariants ? 'true' : 'false' }};
</script>

{{-- JavaScript Lengkap (Tidak terpotong) --}}
<script>
document.addEventListener('DOMContentLoaded', () => {
    
    // --- PENGATURAN AWAL ---
    const mainImage = document.getElementById('main-product-image');
    const quantityInput = document.getElementById('quantity');
    const minusButton = document.getElementById('button-minus');
    const plusButton = document.getElementById('button-plus');
    const toggleBtn = document.getElementById("toggle-description");
    
    // Elemen Varian
    const variantGroups = document.querySelectorAll('.variant-group');
    const variantIdInput = document.getElementById('product_variant_id');
    const displayPrice = document.getElementById('display-price');
    const displayOriginalPrice = document.getElementById('display-original-price');
    const displayDiscount = document.getElementById('display-discount');
    const displayStock = document.getElementById('display-stock');
    const addToCartButton = document.getElementById('add-to-cart-button');
    const buyNowButton = document.getElementById('buy-now-button');

    // Data dari Blade
    const allVariants = window.variantData.variants || [];
    const variantTypes = window.variantData.types || [];
    let selectedOptions = {}; // { Warna: "Merah", Ukuran: "L" }
    const initialImageUrl = mainImage.src; // Simpan gambar utama

    // --- FUNGSI FORMAT ANGKA ---
    function formatCurrency(number) {
        return 'Rp' + new Intl.NumberFormat('id-ID').format(number);
    }

    // --- FUNGSI GAMBAR THUMBNAIL ---
    window.changeImage = function(element) {
        if (!element || !element.src || element.src.includes('placehold.co')) return;
        if(mainImage) mainImage.src = element.src;
        document.querySelectorAll('.thumbnail-img').forEach(thumb => {
            thumb.classList.remove('thumbnail-active');
            thumb.classList.add('border-transparent');
        });
        element.classList.add('thumbnail-active');
        element.classList.remove('border-transparent');
    }

    // --- FUNGSI KUANTITAS ---
    function updateQuantityButtonsState(maxStock = 0) {
        if (!quantityInput || !minusButton || !plusButton) return;
        try {
            const minVal = 1; 
            const maxVal = maxStock;
            let currentValue = parseInt(quantityInput.value);

            if (maxVal <= 0) {
                quantityInput.value = 1;
                quantityInput.disabled = true;
                minusButton.disabled = true;
                plusButton.disabled = true;
            } else {
                quantityInput.disabled = false;
                
                if (isNaN(currentValue) || currentValue < minVal) {
                    quantityInput.value = minVal;
                } else if (currentValue > maxVal) {
                    quantityInput.value = maxVal;
                }
                currentValue = parseInt(quantityInput.value); // Ambil nilai setelah penyesuaian

                minusButton.disabled = currentValue <= minVal;
                plusButton.disabled = currentValue >= maxVal;
            }
            quantityInput.max = maxVal;

        } catch (e) {
            console.error("Error updating quantity buttons:", e);
        }
    }

    if(minusButton) {
        minusButton.addEventListener('click', () => {
            if (!quantityInput || quantityInput.disabled) return;
            let currentValue = parseInt(quantityInput.value);
            const minVal = parseInt(quantityInput.min);
            if (!isNaN(currentValue) && currentValue > minVal) {
                quantityInput.value = currentValue - 1;
                updateQuantityButtonsState(parseInt(quantityInput.max));
            }
        });
    }
    if(plusButton) {
        plusButton.addEventListener('click', () => {
            if (!quantityInput || quantityInput.disabled) return;
            let currentValue = parseInt(quantityInput.value);
            const maxVal = parseInt(quantityInput.max);
            if(!isNaN(currentValue) && currentValue < maxVal) {
                quantityInput.value = currentValue + 1;
                updateQuantityButtonsState(maxVal);
            }
        });
    }
    if(quantityInput) {
        quantityInput.addEventListener('input', () => {
             updateQuantityButtonsState(parseInt(quantityInput.max));
        });
    }

    // --- FUNGSI DESKRIPSI ---
    if (toggleBtn) {
        const shortDesc = document.getElementById("short-description");
        const fullDesc = document.getElementById("full-description");
        
        toggleBtn.addEventListener("click", function () {
            if (fullDesc.classList.contains("hidden")) {
                shortDesc.classList.add("hidden");
                fullDesc.classList.remove("hidden");
                this.innerHTML = `Tampilkan Lebih Sedikit <i class="fas fa-chevron-up text-xs ml-1"></i>`;
            } else {
                shortDesc.classList.remove("hidden");
                fullDesc.classList.add("hidden");
                this.innerHTML = `Baca Selengkapnya <i class="fas fa-chevron-down text-xs ml-1"></i>`;
            }
        });
    }

    // --- LOGIKA UTAMA VARIAN ---

    function updateVariantUI() {
        if (!window.hasVariants) return; // Keluar jika produk ini tidak punya varian

        const allTypesSelected = Object.keys(selectedOptions).length === variantTypes.length;
        let foundVariant = null;

        if (allTypesSelected) {
            // Cari varian yang cocok dengan semua pilihan
            foundVariant = allVariants.find(variant => {
                let match = true;
                for (const typeName in selectedOptions) {
                    // Pastikan 'variant.options' ada dan sesuai
                    if (!variant.options || variant.options[typeName] !== selectedOptions[typeName]) {
                        match = false;
                        break;
                    }
                }
                return match;
            });
        }

        if (foundVariant) {
            // Varian DITEMUKAN
            displayPrice.textContent = formatCurrency(foundVariant.price);
            displayStock.textContent = `Tersisa ${foundVariant.stock} buah`;
            
            // Tampilkan harga asli & diskon jika ada
            if (foundVariant.original_price && foundVariant.original_price > foundVariant.price) {
                displayOriginalPrice.textContent = formatCurrency(foundVariant.original_price);
                displayOriginalPrice.classList.remove('hidden');
                
                const discount = Math.round(((foundVariant.original_price - foundVariant.price) / foundVariant.original_price) * 100);
                displayDiscount.textContent = `${discount}% OFF`;
                displayDiscount.classList.remove('hidden');
            } else {
                displayOriginalPrice.classList.add('hidden');
                displayDiscount.classList.add('hidden');
            }

            // Update Kuantitas
            updateQuantityButtonsState(foundVariant.stock);
            
            // Update Tombol Cart
            addToCartButton.disabled = foundVariant.stock <= 0;
            buyNowButton.disabled = foundVariant.stock <= 0;
            
            // Set ID Varian
            variantIdInput.value = foundVariant.id;

            // Ganti gambar utama jika varian punya gambar
            if (foundVariant.image_url) {
                mainImage.src = `{{ asset('public/storage') }}/${foundVariant.image_url}`;
            }

        } else {
            // Varian TIDAK DITEMUKAN (kombinasi tidak valid atau belum lengkap)
            
            // Reset ke harga produk utama
            displayPrice.textContent = formatCurrency(window.initialProduct.price);
            displayOriginalPrice.classList.add('hidden');
            displayDiscount.classList.add('hidden');
            
            displayStock.textContent = allTypesSelected ? "Kombinasi tidak tersedia" : "Pilih varian";
            updateQuantityButtonsState(0);
            
            // Disable tombol
            addToCartButton.disabled = true;
            buyNowButton.disabled = true;
            variantIdInput.value = "";
            
            // Kembalikan ke gambar utama produk
            mainImage.src = initialImageUrl;
        }
    }

    if (window.hasVariants) {
        // Tambahkan event listener ke semua tombol varian
        variantGroups.forEach(group => {
            const typeName = group.dataset.typeName;
            const buttons = group.querySelectorAll('.variant-btn');
            
            buttons.forEach(button => {
                button.addEventListener('click', () => {
                    const optionValue = button.dataset.optionValue;
                    
                    // Update state pilihan
                    if (selectedOptions[typeName] === optionValue) {
                        // Deselect (klik tombol yang sama lagi)
                        delete selectedOptions[typeName];
                        button.classList.remove('active');
                    } else {
                        // Select
                        selectedOptions[typeName] = optionValue;
                        // Hapus 'active' dari tombol lain di grup ini
                        buttons.forEach(btn => btn.classList.remove('active'));
                        // Tambah 'active' ke tombol yang diklik
                        button.classList.add('active');
                    }
                    
                    // Update UI (harga, stok, dll)
                    updateVariantUI();
                });
            });
        });
        
        // Inisialisasi UI Varian saat halaman dimuat
        updateVariantUI();
        
    } else {
        // --- Inisialisasi Halaman (untuk produk NON-VARIAN) ---
        const stock = window.initialProduct.stock;
        updateQuantityButtonsState(stock);
        addToCartButton.disabled = stock <= 0;
        buyNowButton.disabled = stock <= 0;
    }

}); // Akhir DOMContentLoaded
</script>
@endpush