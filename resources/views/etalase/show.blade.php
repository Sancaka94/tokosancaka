@extends('layouts.marketplace')

@section('title', $product->name . ' - Sancaka Marketplace')

@push('styles')
    {{-- Tailwind CSS & Google Fonts --}}
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    {{-- Font Awesome for Icons --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body { font-family: 'Inter', sans-serif; -webkit-font-smoothing: antialiased; -moz-osx-font-smoothing: grayscale; }
        .thumbnail-active { outline: 2px solid #EE4D2D; outline-offset: 2px; } /* Shopee Orange */
        input[type=number]::-webkit-inner-spin-button, input[type=number]::-webkit-outer-spin-button { -webkit-appearance: none; margin: 0; }
        input[type=number] { -moz-appearance: textfield; }

        /* Style untuk tombol varian - Mirip Shopee */
        .variant-option {
            min-width: 5rem; /* Minimum width */
            padding: 0.5rem 1rem;
            border: 1px solid #d1d5db; /* gray-300 */
            border-radius: 0.25rem; /* rounded-sm */
            text-align: center;
            cursor: pointer;
            transition: all 0.2s ease-in-out;
            background-color: #fff; /* White background */
            color: #1f2937; /* gray-800 */
        }
        .variant-option:hover {
            border-color: #EE4D2D;
            color: #EE4D2D;
        }
        .variant-option.active {
            border-color: #EE4D2D;
            color: #EE4D2D;
            background-color: rgba(238, 77, 45, 0.05); /* Light orange tint */
            position: relative; /* For the checkmark */
        }
        /* Checkmark for active variant */
        .variant-option.active::after {
             content: '';
             position: absolute;
             bottom: 0;
             right: 0;
             width: 0;
             height: 0;
             border-bottom: 12px solid #EE4D2D;
             border-left: 12px solid transparent;
        }
         .variant-option.active::before {
             content: '\f00c'; /* Font Awesome check icon */
             font-family: 'Font Awesome 6 Free';
             font-weight: 900;
             position: absolute;
             bottom: -2px;
             right: 1px;
             color: white;
             font-size: 8px;
             z-index: 1;
         }

        .variant-option:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            background-color: #f3f4f6; /* gray-100 */
            color: #9ca3af; /* gray-400 */
            border-color: #d1d5db; /* gray-300 */
        }
        .dark .variant-option {
            background-color: #374151; /* gray-700 */
            border-color: #4b5563; /* gray-600 */
            color: #e5e7eb; /* gray-200 */
        }
         .dark .variant-option:hover {
             border-color: #F97316; /* orange-500 */
             color: #F97316;
         }
        .dark .variant-option.active {
            border-color: #F97316;
            color: #F97316;
            background-color: rgba(249, 115, 22, 0.1); /* Light orange tint */
        }
         .dark .variant-option.active::after { border-bottom-color: #F97316; }
         .dark .variant-option:disabled {
             background-color: #4b5563; /* gray-600 */
             color: #6b7280; /* gray-500 */
             border-color: #4b5563;
         }


        /* Style untuk tombol disable */
         button:disabled {
            opacity: 0.7;
            cursor: not-allowed;
         }
         input:disabled {
            opacity: 0.7;
             cursor: not-allowed;
         }

         /* Shopee Button Styles */
         .btn-shopee-outline {
             background-color: rgba(255, 87, 34, 0.1);
             border: 1px solid #EE4D2D;
             color: #EE4D2D;
             transition: background-color 0.2s ease;
         }
         .btn-shopee-outline:hover {
             background-color: rgba(255, 87, 34, 0.15);
         }
         .btn-shopee-solid {
             background-color: #EE4D2D;
             border: 1px solid #EE4D2D;
             color: white;
              transition: background-color 0.2s ease;
         }
         .btn-shopee-solid:hover {
             background-color: #d73210; /* Slightly darker orange */
         }

         /* Prose adjustments */
         .prose {
             color: #374151; /* gray-700 */
         }
         .dark .prose-invert {
            color: #d1d5db; /* gray-300 */
         }
         .prose h1, .prose h2, .prose h3, .prose h4, .prose h5, .prose h6 {
             color: #111827; /* gray-900 */
         }
         .dark .prose-invert h1, .dark .prose-invert h2, .dark .prose-invert h3, .dark .prose-invert h4, .dark .prose-invert h5, .dark .prose-invert h6 {
             color: #f3f4f6; /* gray-100 */
         }
         .prose a { color: #2563eb; } /* blue-600 */
         .dark .prose-invert a { color: #60a5fa; } /* blue-400 */

    </style>
@endpush

@section('content')
{{-- Helper function to format WhatsApp number --}}
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

{{-- Siapkan data varian untuk JS di sini --}}
@php
    $jsProductVariantsData = [];
    $productHasVariants = $product->relationLoaded('productVariantTypes') && $product->productVariantTypes->isNotEmpty();
    if ($productHasVariants && $product->relationLoaded('productVariants')) {
        $jsProductVariantsData = $product->productVariants->mapWithKeys(function($variant) {
            $key = '';
            if ($variant->relationLoaded('options')) {
                $key = $variant->options->sortBy(function($option) {
                            // Safely access nested properties
                            return optional(optional($option)->productVariantType)->name ?? '';
                        })
                        ->map(fn($option) => (optional(optional($option)->productVariantType)->name ?? 'UNKNOWN') . ':' . (optional($option)->name ?? 'UNKNOWN')) // Safely access option name
                        ->implode(';');
            }
            if (!empty($key)) {
                return [$key => [
                    'id' => $variant->id,
                    'price' => (float) $variant->price, // Pastikan float
                    'stock' => (int) $variant->stock,   // Pastikan integer
                    'sku' => $variant->sku_code,
                    // 'image_url' => $variant->image_url ? asset('storage/' . $variant->image_url) : null
                ]];
            }
            return [];
        })->filter()->all(); // filter() removes empty arrays if key wasn't generated
    }
    $jsVariantTypesCount = $productHasVariants ? $product->productVariantTypes->count() : 0;
    // Initial stock is 0 if product has variants, otherwise use product stock
    $initialStock = $productHasVariants ? 0 : (int)($product->stock ?? 0); // Cast to int
@endphp


<div class="bg-gray-100 dark:bg-gray-900 text-gray-800 dark:text-gray-200 min-h-screen">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Breadcrumb -->
        <nav aria-label="Breadcrumb" class="mb-6 text-sm text-gray-500 dark:text-gray-400">
             <ol class="flex items-center space-x-2 overflow-x-auto whitespace-nowrap py-1">
                <li><a href="{{ route('etalase.index') }}" class="hover:text-red-600">Sancaka</a></li>
                <li><i class="fas fa-chevron-right text-xs"></i></li>
                @if($product->category && is_object($product->category))
                <li><a href="{{ route('etalase.category.show', $product->category->slug) }}" class="hover:text-red-600">{{ $product->category->name }}</a></li>
                <li><i class="fas fa-chevron-right text-xs"></i></li>
                @endif
                <li class="font-medium text-gray-700 dark:text-gray-300 truncate">{{ $product->name }}</li>
            </ol>
        </nav>

        <main class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4 sm:p-6 lg:p-8">
            <div class="grid grid-cols-1 md:grid-cols-5 gap-8 lg:gap-12">
                <!-- Product Images (Kolom Kiri - 2/5) -->
                <div class="md:col-span-2 image-gallery">
                    <div class="aspect-w-1 aspect-h-1 w-full overflow-hidden rounded-md shadow-sm mb-4 border border-gray-200 dark:border-gray-700">
                         @php
                             $imageUrl = $product->image_url ? asset('storage/' . $product->image_url) : 'https://placehold.co/600x600/EFEFEF/AAAAAA?text=Gambar+Tidak+Ada';
                         @endphp
                        <img id="main-product-image"
                             src="{{ $imageUrl }}"
                             alt="{{ $product->name }}"
                             class="w-full h-full object-contain object-center" {{-- Ganti object-cover ke object-contain --}}
                             onerror="this.onerror=null;this.src='https://placehold.co/600x600/EFEFEF/AAAAAA?text=Gambar+Error';">
                    </div>
                    {{-- Thumbnails --}}
                    <div class="grid grid-cols-5 gap-2 sm:gap-3">
                        <div>
                            <img src="{{ $imageUrl }}" alt="Thumbnail 1" class="thumbnail-img w-full h-auto object-cover rounded cursor-pointer border border-gray-200 dark:border-gray-700 hover:border-red-500 thumbnail-active" onclick="changeImage(this)" onerror="this.onerror=null;this.style.display='none';">
                        </div>
                        {{-- Logika untuk gambar tambahan (jika ada) --}}
                        {{-- @if($product->images && is_array(json_decode($product->images))) ... @endif --}}
                    </div>
                </div>

                <!-- Product Info (Kolom Kanan - 3/5) -->
                <div class="md:col-span-3 product-info">
                    <h1 class="text-xl lg:text-2xl font-semibold text-gray-900 dark:text-white mb-2 leading-tight">{{ $product->name }}</h1>

                    <div class="flex items-center space-x-4 mb-4 text-sm text-gray-500 dark:text-gray-400 border-b border-gray-200 dark:border-gray-700 pb-4">
                        <div class="flex items-center">
                             <span class="font-semibold text-red-500 mr-1">{{ number_format($product->rating ?? 5, 1) }}</span>
                             {{-- Simple Stars --}}
                             <div class="flex text-red-400">
                                 @for ($i = 1; $i <= 5; $i++)
                                     <i class="fas fa-star {{ ($product->rating ?? 5) >= $i ? 'text-red-400' : 'text-gray-300' }} text-xs"></i>
                                 @endfor
                             </div>
                        </div>
                        <span class="text-gray-300 dark:text-gray-600">|</span>
                        <div><span class="font-semibold text-gray-700 dark:text-gray-300">{{ $product->reviews_count ?? rand(50, 200) }}</span> Penilaian</div>
                        <span class="text-gray-300 dark:text-gray-600">|</span>
                        <div><span class="font-semibold text-gray-700 dark:text-gray-300">{{ $product->sold_count ?? '100+' }}</span> Terjual</div>
                    </div>

                    {{-- Harga --}}
                    <div class="bg-gray-50 dark:bg-gray-700/50 rounded p-4 my-4 flex items-center gap-4">
                        @if($product->original_price && $product->original_price > $product->price)
                            <span id="display-original-price" class="text-base text-gray-400 dark:text-gray-500 line-through">
                                Rp{{ number_format($product->original_price, 0, ',', '.') }}
                            </span>
                        @else
                             <span id="display-original-price" class="text-base text-gray-400 dark:text-gray-500 line-through hidden"></span>
                        @endif

                        {{-- [PERBAIKAN WARNA] Kembali ke merah --}}
                        <span id="display-price" class="text-2xl lg:text-3xl font-bold text-red-600 dark:text-red-500">
                           Rp{{ number_format($product->price, 0, ',', '.') }}
                        </span>

                        @if($product->original_price && $product->original_price > $product->price)
                             {{-- [PERBAIKAN WARNA] Sesuaikan diskon dengan warna harga utama --}}
                            <span id="display-discount" class="text-xs font-semibold text-red-600 dark:text-red-500 bg-red-100 dark:bg-red-900/50 px-2 py-0.5 rounded-sm">
                                {{ round((($product->original_price - $product->price) / $product->original_price) * 100) }}% OFF
                            </span>
                        @else
                             <span id="display-discount" class="text-xs font-semibold text-red-600 dark:text-red-500 bg-red-100 dark:bg-red-900/50 px-2 py-0.5 rounded-sm hidden"></span>
                        @endif
                    </div>

                    <form id="add-to-cart-form" action="{{ route('cart.add') }}" method="POST">
                        @csrf
                        <input type="hidden" name="product_id" value="{{ $product->id }}">
                        <input type="hidden" name="product_variant_id" id="selected_variant_id" value="">

                        {{-- Pilihan Varian --}}
                        @if($productHasVariants)
                            <div id="variant-selection" class="mt-6 space-y-4">
                                @foreach($product->productVariantTypes as $type)
                                    <div class="flex items-start sm:items-center flex-col sm:flex-row"> {{-- Allow stacking on small screens --}}
                                        <label class="w-full sm:w-24 text-sm text-gray-500 dark:text-gray-400 capitalize mb-2 sm:mb-0 flex-shrink-0">{{ $type->name }}</label>
                                        <div class="flex flex-wrap gap-2">
                                            @if($type->relationLoaded('options'))
                                                @foreach($type->options as $option)
                                                    <button
                                                        type="button"
                                                        class="variant-option text-sm"
                                                        data-type-name="{{ $type->name }}"
                                                        data-option-name="{{ $option->name }}">
                                                        {{ $option->name }}
                                                    </button>
                                                @endforeach
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            <p id="variant-error" class="text-red-600 text-sm mt-2 hidden">Silakan pilih semua opsi varian.</p>
                        @endif

                        {{-- Kuantitas --}}
                        <div class="mt-6 flex items-center">
                             <label for="quantity" class="w-24 text-sm text-gray-500 dark:text-gray-400 flex-shrink-0">Kuantitas</label>
                            <div class="flex items-center">
                                <div class="flex items-center border border-gray-300 dark:border-gray-600 rounded">
                                    <button id="button-minus" type="button" class="px-3 py-1 text-gray-600 dark:text-gray-400 hover:text-red-600 dark:hover:text-red-500 transition disabled:text-gray-300 dark:disabled:text-gray-500" disabled>
                                        <i class="fas fa-minus text-xs"></i>
                                    </button>
                                    <input type="number" id="quantity" name="quantity" class="w-12 h-8 text-center text-sm bg-transparent border-x border-gray-300 dark:border-gray-600 focus:outline-none focus:ring-0 disabled:bg-gray-100 dark:disabled:bg-gray-700" value="1" min="1" max="{{ $initialStock > 0 ? $initialStock : 1 }}" {{ $initialStock <= 0 ? 'disabled' : '' }}>
                                    <button id="button-plus" type="button" class="px-3 py-1 text-gray-600 dark:text-gray-400 hover:text-red-600 dark:hover:text-red-500 transition disabled:text-gray-300 dark:disabled:text-gray-500" {{ $initialStock <= 1 ? 'disabled' : '' }}>
                                        <i class="fas fa-plus text-xs"></i>
                                    </button>
                                </div>
                                <span id="display-stock" class="ml-4 text-sm text-gray-500 dark:text-gray-400">
                                    @if(!$productHasVariants)
                                        @if($initialStock > 0)
                                            Tersisa {{ $initialStock }} buah
                                        @else
                                            Stok habis
                                        @endif
                                    @else
                                        Pilih varian
                                    @endif
                                </span>
                            </div>
                        </div>

                        {{-- Tombol Aksi --}}
                        <div class="mt-8 pt-6 border-t border-gray-200 dark:border-gray-700">
                            <div class="flex items-center gap-4">
                                <button id="add-to-cart-button" type="submit" name="action" value="add_to_cart" class="flex-1 flex items-center justify-center gap-2 px-6 py-3 text-sm btn-shopee-outline rounded-sm disabled:opacity-70 disabled:hover:bg-opacity-10" {{ $initialStock <= 0 ? 'disabled' : '' }}>
                                    <i class="fas fa-cart-plus"></i> Masukkan Keranjang
                                </button>
                                 <button id="buy-now-button" type="submit" name="action" value="buy_now" class="flex-1 flex items-center justify-center gap-2 px-6 py-3 text-sm btn-shopee-solid rounded-sm disabled:opacity-70 disabled:hover:bg-red-600" {{ $initialStock <= 0 ? 'disabled' : '' }}>
                                    Beli Sekarang
                                </button>
                            </div>
                        </div>
                    </form>

                     {{-- Bagian Spesifikasi / Atribut --}}
                     @php
                         $hasAttributesToShow = false;
                         $attributesProductData = [];
                         if ($product->relationLoaded('category') && $product->category && $product->category->relationLoaded('attributes')) {
                             $attributesProductData = is_string($product->attributes_data) ? json_decode($product->attributes_data, true) : ($product->attributes_data ?? []);
                             if(!is_array($attributesProductData)) $attributesProductData = [];

                             foreach ($product->category->attributes as $attributeDefinition) {
                                 // Check both slug and name for robust compatibility
                                 $attributeValue = $attributesProductData[$attributeDefinition->slug] ?? ($attributesProductData[$attributeDefinition->name] ?? null);
                                 if ($attributeValue !== null && $attributeValue !== '' && (!is_array($attributeValue) || !empty(array_filter($attributeValue)))) {
                                     $hasAttributesToShow = true;
                                     break;
                                 }
                             }
                         }
                     @endphp

                     @if ($hasAttributesToShow)
                         <div class="mt-8 pt-6 border-t border-gray-200 dark:border-gray-700">
                             <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-4">Spesifikasi Produk</h3>
                             <div class="space-y-3">
                                 @foreach ($product->category->attributes->sortBy('name') as $attributeDefinition) {{-- Sort attributes by name --}}
                                    @php
                                        // Check both slug and name
                                        $value = $attributesProductData[$attributeDefinition->slug] ?? ($attributesProductData[$attributeDefinition->name] ?? null);
                                        $displayValue = '';
                                        if ($value !== null && $value !== '') {
                                             if (is_array($value)) {
                                                  $filteredValue = array_filter($value);
                                                  if (!empty($filteredValue)) $displayValue = implode(', ', $filteredValue);
                                             } else { $displayValue = $value; }
                                        }
                                    @endphp
                                     @if (!empty($displayValue))
                                         <div class="grid grid-cols-1 sm:grid-cols-3 gap-x-4 gap-y-1 text-sm">
                                             <dt class="text-gray-500 dark:text-gray-400">{{ $attributeDefinition->name }}</dt>
                                             <dd class="sm:col-span-2 font-medium text-gray-800 dark:text-gray-200">{{ $displayValue }}</dd>
                                         </div>
                                     @endif
                                 @endforeach
                             </div>
                         </div>
                     @endif
                     {{-- Akhir Bagian Spesifikasi --}}
                </div>
            </div>
        </main>

        {{-- Store Info --}}
        @if ($product->store_name)
        <div class="mt-8 bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4 sm:p-6">
            <div class="flex flex-col sm:flex-row items-start sm:items-center gap-4">
                 {{-- [PERBAIKAN ROUTE] Ganti 'slug' menjadi 'name' --}}
                 <a href="{{ Route::has('toko.profile') ? route('toko.profile', ['name' => $product->store_slug ?? Str::slug($product->store_name)]) : '#' }}" class="flex-shrink-0">
                    <img src="{{ $product->seller_logo ? asset('storage/' . $product->seller_logo) : 'https://placehold.co/64x64/E2E8F0/AAAAAA?text=Toko' }}"
                         alt="Logo {{ $product->store_name }}"
                         class="w-16 h-16 rounded-full border border-gray-200 dark:border-gray-700 object-cover">
                 </a>
                <div class="flex-grow">
                     {{-- [PERBAIKAN ROUTE] Ganti 'slug' menjadi 'name' --}}
                     <a href="{{ Route::has('toko.profile') ? route('toko.profile', ['name' => $product->store_slug ?? Str::slug($product->store_name)]) : '#' }}" class="hover:underline">
                        <h3 class="text-base font-bold text-gray-900 dark:text-white">{{ $product->store_name }}</h3>
                    </a>
                    <div class="flex items-center text-xs text-gray-500 dark:text-gray-400 mt-1">
                        <span class="w-2 h-2 bg-gray-400 rounded-full mr-1.5"></span>
                        <span>Offline</span> {{-- Ganti dengan status toko jika ada --}}
                    </div>
                     {{-- Tambahkan info lokasi toko --}}
                     @if($product->seller_city)
                         <div class="flex items-center text-xs text-gray-500 dark:text-gray-400 mt-1">
                             <i class="fas fa-map-marker-alt mr-1.5"></i> {{ $product->seller_city }}
                         </div>
                     @endif
                </div>

                <div class="flex w-full sm:w-auto flex-col sm:flex-row gap-3 mt-4 sm:mt-0 self-stretch sm:self-center">
                    @if(Auth::check() && $product->seller_wa)
                        @php $wa_number = formatWaNumber($product->seller_wa); @endphp
                        <a href="https://wa.me/{{ $wa_number }}?text=Halo%2C%20saya%20tertarik%20dengan%20produk%20Anda%3A%20{{ urlencode($product->name) }}" target="_blank" class="w-full sm:w-auto flex items-center justify-center gap-2 px-4 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded transition-colors hover:bg-gray-100 dark:hover:bg-gray-700">
                            <i class="fab fa-whatsapp text-green-500"></i> Chat
                        </a>
                    @endif
                     @if(Route::has('toko.profile'))
                          {{-- [PERBAIKAN ROUTE] Ganti 'slug' menjadi 'name' --}}
                         <a href="{{ route('toko.profile', ['name' => $product->store_slug ?? Str::slug($product->store_name)]) }}" class="w-full sm:w-auto flex items-center justify-center gap-2 px-4 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                             <i class="fas fa-store text-gray-500 dark:text-gray-400"></i> Kunjungi Toko
                        </a>
                     @endif
                </div>
            </div>
        </div>
        @endif

        <!-- Deskripsi Produk -->
        <div class="mt-8 bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4 sm:p-6">
            <h2 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">Deskripsi Produk</h2>
            <div id="short-description" class="prose prose-sm dark:prose-invert max-w-none break-words leading-relaxed">
                {!! nl2br(e(Str::limit($product->description, 350))) !!}
            </div>
            <div id="full-description" class="prose prose-sm dark:prose-invert max-w-none break-words leading-relaxed hidden">
                {!! nl2br(e($product->description)) !!}
            </div>
            @if(strlen($product->description ?? '') > 350)
            <div class="text-center mt-4">
                 <button id="toggle-description" class="text-sm text-red-600 dark:text-red-500 font-medium hover:underline">
                    Baca Selengkapnya <i class="fas fa-chevron-down text-xs ml-1"></i>
                </button>
            </div>
            @endif
        </div>

    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {

    const mainImage = document.getElementById('main-product-image');

    window.changeImage = function(element) {
        if (!element || !element.src || element.src.includes('placehold.co')) return;
        if(mainImage) mainImage.src = element.src;
        document.querySelectorAll('.thumbnail-img').forEach(thumb => thumb.classList.remove('thumbnail-active'));
        element.classList.add('thumbnail-active');
    }

    const quantityInput = document.getElementById('quantity');
    const minusButton = document.getElementById('button-minus');
    const plusButton = document.getElementById('button-plus');

    function updateQuantityButtonsState() {
        if (!quantityInput || !minusButton || !plusButton) return;
        try {
            const currentValue = parseInt(quantityInput.value);
            const minVal = parseInt(quantityInput.min);
            const maxVal = parseInt(quantityInput.max);
            // Also consider if the input itself is disabled
            minusButton.disabled = currentValue <= minVal || quantityInput.disabled;
            plusButton.disabled = currentValue >= maxVal || quantityInput.disabled;
        } catch (e) {
            console.error("Error updating quantity buttons:", e);
            minusButton.disabled = true;
            plusButton.disabled = true;
        }
    }

    if(minusButton) {
        minusButton.addEventListener('click', () => {
            if (!quantityInput || quantityInput.disabled) return; // Check disabled state
            let currentValue = parseInt(quantityInput.value);
            const minVal = parseInt(quantityInput.min);
            if (!isNaN(currentValue) && !isNaN(minVal) && currentValue > minVal) {
                quantityInput.value = currentValue - 1;
                updateQuantityButtonsState();
            }
        });
    }

    if(plusButton) {
        plusButton.addEventListener('click', () => {
             if (!quantityInput || quantityInput.disabled) return; // Check disabled state
            let currentValue = parseInt(quantityInput.value);
            const maxVal = parseInt(quantityInput.max);
            if(!isNaN(currentValue) && !isNaN(maxVal) && currentValue < maxVal) {
                quantityInput.value = currentValue + 1;
                 updateQuantityButtonsState();
            }
        });
    }

     if(quantityInput) {
        quantityInput.addEventListener('input', () => {
             let value = parseInt(quantityInput.value);
             const minVal = parseInt(quantityInput.min);
             const maxVal = parseInt(quantityInput.max);

             if (isNaN(value) || value < minVal) {
                 quantityInput.value = minVal;
             } else if (value > maxVal) {
                 quantityInput.value = maxVal;
             }
             // Ensure value is not empty if manually cleared
             if (quantityInput.value === '') {
                quantityInput.value = minVal;
             }
             updateQuantityButtonsState();
        });
     }


    const toggleBtn = document.getElementById("toggle-description");
    if (toggleBtn) {
        toggleBtn.addEventListener("click", function () {
            const shortDesc = document.getElementById("short-description");
            const fullDesc = document.getElementById("full-description");
            const icon = this.querySelector('i');

            if (shortDesc && fullDesc) {
                if (fullDesc.classList.contains("hidden")) {
                    shortDesc.classList.add("hidden");
                    fullDesc.classList.remove("hidden");
                    this.innerHTML = `Tampilkan Lebih Sedikit <i class="fas fa-chevron-up text-xs ml-1"></i>`;
                } else {
                    shortDesc.classList.remove("hidden");
                    fullDesc.classList.add("hidden");
                    this.innerHTML = `Baca Selengkapnya <i class="fas fa-chevron-down text-xs ml-1"></i>`;
                }
            }
        });
    }

    // --- Logika Varian ---
    const variantSelectionDiv = document.getElementById('variant-selection');
    const displayPriceEl = document.getElementById('display-price');
    const displayOriginalPriceEl = document.getElementById('display-original-price');
    const displayDiscountEl = document.getElementById('display-discount');
    const displayStockEl = document.getElementById('display-stock');
    const selectedVariantIdInput = document.getElementById('selected_variant_id');
    const variantErrorEl = document.getElementById('variant-error');
    const addToCartButton = document.getElementById('add-to-cart-button');
    const buyNowButton = document.getElementById('buy-now-button');

    const productVariantsData = @json($jsProductVariantsData);
    const variantTypesCount = {{ $jsVariantTypesCount }};
    let selectedOptions = {};

    // Initial product state (non-variant)
    const initialProductPrice = {{ (float) ($product->price ?? 0) }};
    const initialProductOriginalPrice = {{ (float) ($product->original_price ?? 0) }};
    const initialProductStock = {{ $initialStock }};

    if (variantSelectionDiv && variantTypesCount > 0) {
        const optionButtons = variantSelectionDiv.querySelectorAll('.variant-option');

        optionButtons.forEach(button => {
            button.addEventListener('click', () => {
                if (button.disabled) return; // Ignore clicks on disabled buttons

                const typeName = button.dataset.typeName;
                const optionName = button.dataset.optionName;

                selectedOptions[typeName] = optionName;

                // Update visual state for the group
                variantSelectionDiv.querySelectorAll(`.variant-option[data-type-name="${typeName}"]`).forEach(btn => {
                    btn.classList.remove('active');
                });
                button.classList.add('active');

                // Check if all variants are selected
                if (Object.keys(selectedOptions).length === variantTypesCount) {
                    updateProductDetails();
                    if(variantErrorEl) variantErrorEl.classList.add('hidden');
                } else {
                     // Keep UI disabled but show selected options
                     disableActionsAndResetVariantId('Pilih semua varian');
                }
            });
        });
    }

    function generateVariantKey(options) {
        if (variantTypesCount === 0 || Object.keys(options).length !== variantTypesCount) return null;
        return Object.keys(options)
            .sort()
            .map(typeName => `${typeName}:${options[typeName]}`)
            .join(';');
    }

    function updateProductDetails() {
        const currentKey = generateVariantKey(selectedOptions);
        if (!currentKey) {
            console.warn("Attempted to update details but key generation failed. Options:", selectedOptions);
            return; // Should not happen if called correctly
        }

        const selectedVariant = productVariantsData[currentKey];

        if (selectedVariant) {
            // Update Harga
            if(displayPriceEl) displayPriceEl.textContent = `Rp${number_format(selectedVariant.price)}`;
            // Hide original price and discount when a variant is selected
            if(displayOriginalPriceEl) displayOriginalPriceEl.classList.add('hidden');
            if(displayDiscountEl) displayDiscountEl.classList.add('hidden');

            // Update Stok
            const currentStock = selectedVariant.stock;
            if(displayStockEl) displayStockEl.textContent = `Tersisa ${currentStock} buah`;

            if(quantityInput) {
                quantityInput.max = currentStock > 0 ? currentStock : 1;
                 // Reset quantity only if current value exceeds new max stock, or if stock is 0
                 const currentQuantity = parseInt(quantityInput.value);
                if (isNaN(currentQuantity) || currentQuantity > currentStock || currentStock <= 0) {
                    quantityInput.value = 1; // Reset to 1
                }
                 quantityInput.disabled = currentStock <= 0;
            }

            updateQuantityButtonsState();

            // Update hidden input variant ID
            if(selectedVariantIdInput) selectedVariantIdInput.value = selectedVariant.id;

            // Enable/Disable action buttons
            const disableActions = currentStock <= 0;
            if(addToCartButton) addToCartButton.disabled = disableActions;
            if(buyNowButton) buyNowButton.disabled = disableActions;
            if(disableActions && displayStockEl) displayStockEl.textContent = 'Stok habis';

        } else {
            console.error("Kombinasi varian TIDAK DITEMUKAN:", currentKey);
            disableActionsAndResetVariantId('Kombinasi tidak tersedia');
            // Optionally: Visually indicate invalid combination, e.g., reset active buttons
             variantSelectionDiv.querySelectorAll('.variant-option.active').forEach(btn => btn.classList.remove('active'));
             selectedOptions = {}; // Clear selections on invalid combo
        }
    }

     // Disables actions, resets variant ID, updates stock message, disables quantity input
     function disableActionsAndResetVariantId(message = 'Pilih varian') {
        if(displayStockEl) displayStockEl.textContent = message;
        if(quantityInput) {
             quantityInput.max = 1;
             quantityInput.value = 1;
             quantityInput.disabled = true; // Always disable quantity if state is invalid/incomplete
        }
        if(selectedVariantIdInput) selectedVariantIdInput.value = '';

        updateQuantityButtonsState(); // Will disable +/- because quantity input is disabled

        if(addToCartButton) addToCartButton.disabled = true;
        if(buyNowButton) buyNowButton.disabled = true;
     }

    // Resets display to the main product's details and disables actions
    function resetProductDetailsToDefault(message = 'Pilih varian untuk melihat stok') {
        if(displayPriceEl) displayPriceEl.textContent = `Rp${number_format(initialProductPrice)}`;

        if(displayOriginalPriceEl) {
            if (initialProductOriginalPrice > 0) {
                displayOriginalPriceEl.textContent = `Rp${number_format(initialProductOriginalPrice)}`;
                displayOriginalPriceEl.classList.remove('hidden');
            } else {
                 displayOriginalPriceEl.textContent = '';
                 displayOriginalPriceEl.classList.add('hidden');
            }
        }
        if(displayDiscountEl) {
            if (initialProductOriginalPrice > initialProductPrice) {
                const discount = Math.round(((initialProductOriginalPrice - initialProductPrice) / initialProductOriginalPrice) * 100);
                displayDiscountEl.textContent = `${discount}% OFF`;
                displayDiscountEl.classList.remove('hidden');
            } else {
                displayDiscountEl.classList.add('hidden');
            }
        }

       // Call the function to disable actions, quantity, and reset variant ID
       disableActionsAndResetVariantId(message);
    }

    // Format number as integer with dot thousands separator
    function number_format(number) {
        number = (number + '').replace(/[^0-9+\-Ee.]/g, '');
        var n = !isFinite(+number) ? 0 : +number,
            prec = 0,
            sep = '.',
            s = '',
            toFixedFix = function (n, prec) {
                var k = Math.pow(10, prec);
                return '' + Math.round(n * k) / k;
            };
        s = (prec ? toFixedFix(n, prec) : '' + Math.round(n)).split('.');
        if (s[0].length > 3) {
            s[0] = s[0].replace(/\B(?=(?:\d{3})+(?!\d))/g, sep);
        }
        return s[0];
    }

     // Form validation on submit
    const productForm = document.getElementById('add-to-cart-form');
    if(productForm && variantTypesCount > 0) {
        productForm.addEventListener('submit', function(event) {
            // Re-check if variant selection is complete and ID is set
            if (Object.keys(selectedOptions).length !== variantTypesCount || !selectedVariantIdInput || !selectedVariantIdInput.value) {
                event.preventDefault(); // Stop submission
                if(variantErrorEl) {
                    variantErrorEl.textContent = 'Silakan pilih semua opsi varian.';
                    variantErrorEl.classList.remove('hidden'); // Show error
                }
                // Scroll to variant section for visibility
                if(variantSelectionDiv) variantSelectionDiv.scrollIntoView({ behavior: 'smooth', block: 'center' });
            } else {
                 if(variantErrorEl) variantErrorEl.classList.add('hidden'); // Hide error if valid
            }
        });
    }

     // Initial page load setup
    if (variantTypesCount > 0) {
        resetProductDetailsToDefault('Pilih varian'); // Initial state for variant products
    } else {
        // Initial state for non-variant products
        updateQuantityButtonsState(); // Update based on initialStock
        const stockIsZero = initialProductStock <= 0;
        if (quantityInput) quantityInput.disabled = stockIsZero;
        if (addToCartButton) addToCartButton.disabled = stockIsZero;
        if (buyNowButton) buyNowButton.disabled = stockIsZero;
        if (stockIsZero && displayStockEl) displayStockEl.textContent = 'Stok habis';
    }

}); // Akhir DOMContentLoaded
</script>
@endpush

