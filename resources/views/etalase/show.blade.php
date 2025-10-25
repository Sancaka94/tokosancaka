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
{{-- Siapkan data varian untuk JS di sini --}}
@php
    $jsProductVariantsData = [];
    $productHasVariants = $product->relationLoaded('productVariantTypes') && $product->productVariantTypes->isNotEmpty();
    if ($productHasVariants && $product->relationLoaded('productVariants')) {
        $jsProductVariantsData = $product->productVariants->mapWithKeys(function($variant) {
            $key = '';
            if ($variant->relationLoaded('options')) {
                $key = $variant->options->sortBy(function($option) {
                            return optional(optional($option)->productVariantType)->name ?? '';
                        })
                        ->map(fn($option) => (optional(optional($option)->productVariantType)->name ?? 'UNKNOWN') . ':' . ($option->name ?? 'UNKNOWN'))
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
        })->filter()->all();
    }
    $jsVariantTypesCount = $productHasVariants ? $product->productVariantTypes->count() : 0;
    $initialStock = $productHasVariants ? 0 : ($product->stock ?? 0); // Stok awal 0 jika ada varian
@endphp

<div class="bg-gray-100 dark:bg-gray-900 text-gray-800 dark:text-gray-200 min-h-screen">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Breadcrumb -->
        <nav aria-label="Breadcrumb" class="mb-6 text-sm text-gray-500 dark:text-gray-400">
             <ol class="flex items-center space-x-2 overflow-x-auto whitespace-nowrap py-1">
                <li><a href="{{ route('etalase.index') }}" class="hover:text-orange-600">Sancaka</a></li>
                <li><i class="fas fa-chevron-right text-xs"></i></li>
                @if($product->category && is_object($product->category))
                <li><a href="{{ route('etalase.category.show', $product->category->slug) }}" class="hover:text-orange-600">{{ $product->category->name }}</a></li>
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
                            <img src="{{ $imageUrl }}" alt="Thumbnail 1" class="thumbnail-img w-full h-auto object-cover rounded cursor-pointer border border-gray-200 dark:border-gray-700 hover:border-orange-500 thumbnail-active" onclick="changeImage(this)" onerror="this.onerror=null;this.style.display='none';">
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
                             <span class="font-semibold text-orange-500 mr-1">{{ number_format($product->rating ?? 5, 1) }}</span>
                             {{-- Simple Stars --}}
                             <div class="flex text-orange-400">
                                 @for ($i = 1; $i <= 5; $i++)
                                     <i class="fas fa-star {{ ($product->rating ?? 5) >= $i ? 'text-orange-400' : 'text-gray-300' }} text-xs"></i>
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

                        <span id="display-price" class="text-2xl lg:text-3xl font-bold text-orange-600 dark:text-orange-500">
                           Rp{{ number_format($product->price, 0, ',', '.') }}
                        </span>

                        @if($product->original_price && $product->original_price > $product->price)
                            <span id="display-discount" class="text-xs font-semibold text-orange-600 dark:text-orange-500 bg-orange-100 dark:bg-orange-900/50 px-2 py-0.5 rounded-sm">
                                {{ round((($product->original_price - $product->price) / $product->original_price) * 100) }}% OFF
                            </span>
                        @else
                             <span id="display-discount" class="text-xs font-semibold text-orange-600 dark:text-orange-500 bg-orange-100 dark:bg-orange-900/50 px-2 py-0.5 rounded-sm hidden"></span>
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
                                    <div class="flex items-center">
                                        <label class="w-24 text-sm text-gray-500 dark:text-gray-400 capitalize">{{ $type->name }}</label>
                                        <div class="flex flex-wrap gap-2">
                                            @if($type->relationLoaded('options'))
                                                @foreach($type->options as $option)
                                                    <button
                                                        type="button"
                                                        class="variant-option text-sm" {{-- Hapus padding & min-width di style --}}
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
                             <label for="quantity" class="w-24 text-sm text-gray-500 dark:text-gray-400">Kuantitas</label>
                            <div class="flex items-center">
                                <div class="flex items-center border border-gray-300 dark:border-gray-600 rounded">
                                    <button id="button-minus" type="button" class="px-3 py-1 text-gray-600 dark:text-gray-400 hover:text-orange-600 dark:hover:text-orange-500 transition disabled:text-gray-300 dark:disabled:text-gray-500" disabled>
                                        <i class="fas fa-minus text-xs"></i>
                                    </button>
                                    <input type="number" id="quantity" name="quantity" class="w-12 h-8 text-center text-sm bg-transparent border-x border-gray-300 dark:border-gray-600 focus:outline-none focus:ring-0 disabled:bg-gray-100 dark:disabled:bg-gray-700" value="1" min="1" max="{{ $initialStock > 0 ? $initialStock : 1 }}" {{ $initialStock <= 0 ? 'disabled' : '' }}>
                                    <button id="button-plus" type="button" class="px-3 py-1 text-gray-600 dark:text-gray-400 hover:text-orange-600 dark:hover:text-orange-500 transition disabled:text-gray-300 dark:disabled:text-gray-500" {{ $initialStock <= 1 ? 'disabled' : '' }}>
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
                                 <button id="buy-now-button" type="submit" name="action" value="buy_now" class="flex-1 flex items-center justify-center gap-2 px-6 py-3 text-sm btn-shopee-solid rounded-sm disabled:opacity-70 disabled:hover:bg-orange-600" {{ $initialStock <= 0 ? 'disabled' : '' }}>
                                    Beli Sekarang
                                </button>
                            </div>
                        </div>
                    </form>

                     {{-- Bagian Spesifikasi / Atribut --}}
                     @php
                         $hasAttributesToShow = false;
                         $attributesProductData = []; // Definisikan di luar if
                         if ($product->relationLoaded('category') && $product->category && $product->category->relationLoaded('attributes')) {
                             $attributesProductData = is_string($product->attributes_data) ? json_decode($product->attributes_data, true) : ($product->attributes_data ?? []);
                             if(!is_array($attributesProductData)) $attributesProductData = []; // Fallback jika decode gagal

                             foreach ($product->category->attributes as $attributeDefinition) {
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
                                 @foreach ($product->category->attributes as $attributeDefinition)
                                    @php
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
                                         <div class="grid grid-cols-3 gap-4 text-sm">
                                             <dt class="text-gray-500 dark:text-gray-400">{{ $attributeDefinition->name }}</dt>
                                             <dd class="col-span-2 font-medium text-gray-800 dark:text-gray-200">{{ $displayValue }}</dd>
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
                 <a href="{{ Route::has('toko.profile') ? route('toko.profile', ['slug' => $product->store_slug ?? Str::slug($product->store_name)]) : '#' }}" class="flex-shrink-0">
                    <img src="{{ $product->seller_logo ? asset('storage/' . $product->seller_logo) : 'https://placehold.co/64x64/E2E8F0/AAAAAA?text=Toko' }}"
                         alt="Logo {{ $product->store_name }}"
                         class="w-16 h-16 rounded-full border border-gray-200 dark:border-gray-700 object-cover">
                 </a>
                <div class="flex-grow">
                     <a href="{{ Route::has('toko.profile') ? route('toko.profile', ['slug' => $product->store_slug ?? Str::slug($product->store_name)]) : '#' }}" class="hover:underline">
                        <h3 class="text-base font-bold text-gray-900 dark:text-white">{{ $product->store_name }}</h3>
                    </a>
                    <div class="flex items-center text-xs text-gray-500 dark:text-gray-400 mt-1">
                        <span class="w-2 h-2 bg-gray-400 rounded-full mr-1.5"></span>
                        <span>Offline</span> {{-- Ganti dengan status toko jika ada --}}
                    </div>
                </div>

                <div class="flex w-full sm:w-auto flex-col sm:flex-row gap-3 mt-4 sm:mt-0 self-stretch sm:self-center">
                    @if(Auth::check() && $product->seller_wa)
                        @php $wa_number = formatWaNumber($product->seller_wa); @endphp
                        <a href="https://wa.me/{{ $wa_number }}" target="_blank" class="w-full sm:w-auto flex items-center justify-center gap-2 px-4 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded transition-colors hover:bg-gray-100 dark:hover:bg-gray-700">
                            <i class="fab fa-whatsapp text-green-500"></i> Chat
                        </a>
                    @endif
                     @if(Route::has('toko.profile'))
                         <a href="{{ route('toko.profile', ['slug' => $product->store_slug ?? Str::slug($product->store_name)]) }}" class="w-full sm:w-auto flex items-center justify-center gap-2 px-4 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
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
                 <button id="toggle-description" class="text-sm text-orange-600 dark:text-orange-500 font-medium hover:underline">
                    Baca Selengkapnya <i class="fas fa-chevron-down text-xs ml-1"></i>
                </button>
            </div>
            @endif
        </div>

    </div>
</div>
@endsection

{{-- Helper function to format WhatsApp number --}}
@php
function formatWaNumber($number) {
    $number = preg_replace('/[^0-9]/', '', $number);
    if (substr($number, 0, 1) === '0') {
        return '62' . substr($number, 1);
    } elseif (substr($number, 0, 2) !== '62') {
        return '62' . $number;
    }
    return $number;
}
@endphp


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
            minusButton.disabled = currentValue <= minVal || quantityInput.disabled;
            plusButton.disabled = currentValue >= maxVal || quantityInput.disabled;
        } catch (e) {
            console.error("Error updating quantity buttons:", e);
            // Disable both if error
            minusButton.disabled = true;
            plusButton.disabled = true;
        }
    }

    if(minusButton) {
        minusButton.addEventListener('click', () => {
            if (!quantityInput) return;
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
             if (!quantityInput) return;
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

    // Data varian dari Blade (menggunakan variabel $jsProductVariantsData)
    const productVariantsData = @json($jsProductVariantsData);
    const variantTypesCount = {{ $jsVariantTypesCount }};
    let selectedOptions = {}; // { 'Warna': 'Merah', 'Ukuran': 'S' }

    if (variantSelectionDiv && variantTypesCount > 0) {
        const optionButtons = variantSelectionDiv.querySelectorAll('.variant-option');

        optionButtons.forEach(button => {
            button.addEventListener('click', () => {
                const typeName = button.dataset.typeName;
                const optionName = button.dataset.optionName;

                // Update selected state
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
                     disableActionsAndResetVariantId('Pilih semua varian untuk melihat stok');
                }
            });
        });
    }

    function generateVariantKey(options) {
        if (Object.keys(options).length !== variantTypesCount) return null;
        return Object.keys(options)
            .sort()
            .map(typeName => `${typeName}:${options[typeName]}`)
            .join(';');
    }

    function updateProductDetails() {
        const currentKey = generateVariantKey(selectedOptions);
        if (!currentKey) return;

        const selectedVariant = productVariantsData[currentKey];

        if (selectedVariant) {
            // Update Harga
            if(displayPriceEl) displayPriceEl.textContent = `Rp${number_format(selectedVariant.price, 0, ',', '.')}`;
            // Handle original price & discount for variant (assuming variant doesn't have its own original price)
            if(displayOriginalPriceEl) displayOriginalPriceEl.textContent = ''; // Kosongkan harga coret produk utama
            if(displayDiscountEl) displayDiscountEl.classList.add('hidden');   // Sembunyikan diskon produk utama

            // Update Stok
            const currentStock = selectedVariant.stock;
            if(displayStockEl) displayStockEl.textContent = `Tersisa ${currentStock} buah`;

            if(quantityInput) {
                quantityInput.max = currentStock > 0 ? currentStock : 1;
                if (parseInt(quantityInput.value) > currentStock || currentStock <= 0) {
                     quantityInput.value = currentStock > 0 ? 1 : 1;
                }
                 quantityInput.disabled = currentStock <= 0;
            }

            updateQuantityButtonsState();

            // Update hidden input variant ID
            if(selectedVariantIdInput) selectedVariantIdInput.value = selectedVariant.id;

            // Enable/Disable tombol beli/keranjang
            const disableActions = currentStock <= 0;
            if(addToCartButton) addToCartButton.disabled = disableActions;
            if(buyNowButton) buyNowButton.disabled = disableActions;
            if(disableActions && displayStockEl) displayStockEl.textContent = 'Stok habis';

        } else {
            console.error("Kombinasi varian TIDAK DITEMUKAN:", currentKey);
            disableActionsAndResetVariantId('Kombinasi tidak tersedia');
        }
    }

     // Fungsi untuk disable aksi dan reset ID varian
     function disableActionsAndResetVariantId(message = 'Pilih varian') {
        if(displayStockEl) displayStockEl.textContent = message;
        if(quantityInput) {
             quantityInput.max = 1;
             quantityInput.value = 1;
             quantityInput.disabled = true;
        }
        if(selectedVariantIdInput) selectedVariantIdInput.value = '';

        updateQuantityButtonsState();

        if(addToCartButton) addToCartButton.disabled = true;
        if(buyNowButton) buyNowButton.disabled = true;
     }

    // Reset ke state awal produk (harga utama, dll)
    function resetProductDetailsToDefault(message = 'Pilih varian untuk melihat stok') {
        const defaultPrice = {{ (float) ($product->price ?? 0) }}; // Cast ke float
        const defaultOriginalPrice = {{ (float) ($product->original_price ?? 0) }}; // Cast ke float

        if(displayPriceEl) displayPriceEl.textContent = `Rp${number_format(defaultPrice, 0, ',', '.')}`;

        if(displayOriginalPriceEl) {
            if (defaultOriginalPrice > 0) {
                displayOriginalPriceEl.textContent = `Rp${number_format(defaultOriginalPrice, 0, ',', '.')}`;
                displayOriginalPriceEl.classList.remove('hidden'); // Tampilkan jika ada
            } else {
                 displayOriginalPriceEl.textContent = '';
                 displayOriginalPriceEl.classList.add('hidden'); // Sembunyikan jika tidak ada
            }
        }
        if(displayDiscountEl) {
            if (defaultOriginalPrice > defaultPrice) {
                const discount = Math.round(((defaultOriginalPrice - defaultPrice) / defaultOriginalPrice) * 100);
                displayDiscountEl.textContent = `${discount}% OFF`; // Ubah format
                displayDiscountEl.classList.remove('hidden');
            } else {
                displayDiscountEl.classList.add('hidden');
            }
        }

       disableActionsAndResetVariantId(message); // Disable action & reset ID

    }

    // Fungsi format angka (integer only, titik ribuan)
    function number_format(number, decimals, dec_point, thousands_sep) {
        number = (number + '').replace(/[^0-9+\-Ee.]/g, '');
        var n = !isFinite(+number) ? 0 : +number,
            prec = 0, // Force 0 decimals
            sep = '.', // Titik ribuan
            dec = ',', // Koma desimal (tidak digunakan)
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

     // Validasi sebelum submit form
    const productForm = document.getElementById('add-to-cart-form');
    if(productForm && variantTypesCount > 0) {
        productForm.addEventListener('submit', function(event) {
            if (Object.keys(selectedOptions).length !== variantTypesCount || !selectedVariantIdInput || !selectedVariantIdInput.value) {
                event.preventDefault();
                if(variantErrorEl) {
                    variantErrorEl.textContent = 'Silakan pilih semua opsi varian.';
                    variantErrorEl.classList.remove('hidden');
                }
                if(variantSelectionDiv) variantSelectionDiv.scrollIntoView({ behavior: 'smooth', block: 'center' });
            } else {
                 if(variantErrorEl) variantErrorEl.classList.add('hidden');
            }
        });
    }

     // Inisialisasi awal
    if (variantTypesCount > 0) {
        resetProductDetailsToDefault(); // Panggil ini untuk state awal jika ada varian
    } else {
        // Jika tidak ada varian, update tombol quantity & action berdasarkan stok produk utama
        updateQuantityButtonsState();
        const initialStock = {{ $initialStock }}; // Gunakan var PHP yang sudah disiapkan
        if (quantityInput) quantityInput.disabled = initialStock <= 0;
        if (addToCartButton) addToCartButton.disabled = initialStock <= 0;
        if (buyNowButton) buyNowButton.disabled = initialStock <= 0;
         if (initialStock <= 0 && displayStockEl) displayStockEl.textContent = 'Stok habis';
    }


}); // Akhir DOMContentLoaded
</script>
@endpush

