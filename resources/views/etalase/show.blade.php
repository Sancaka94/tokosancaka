@extends('layouts.marketplace')

@section('title', $product->name . ' - Sancaka Marketplace')

@push('styles')
    {{-- Tailwind CSS & Google Fonts --}}
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; -webkit-font-smoothing: antialiased; -moz-osx-font-smoothing: grayscale; }
        .thumbnail-active { outline: 2px solid #2563eb; outline-offset: 2px; }
        input[type=number]::-webkit-inner-spin-button, input[type=number]::-webkit-outer-spin-button { -webkit-appearance: none; margin: 0; }
        input[type=number] { -moz-appearance: textfield; }
        /* Style untuk tombol varian aktif */
        .variant-option.active {
            background-color: #DBEAFE; /* bg-blue-100 */
            border-color: #3B82F6; /* border-blue-500 */
            font-weight: 600; /* font-semibold */
        }
        /* Style untuk tombol varian aktif dark mode */
        .dark .variant-option.active {
             background-color: #1E3A8A; /* dark:bg-blue-900 */
             border-color: #60A5FA; /* dark:border-blue-400 */
        }
        /* Style untuk tombol disable */
         button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
         }
    </style>
@endpush

@section('content')
<div class="bg-gray-50 dark:bg-gray-900 text-gray-800 dark:text-gray-200 min-h-screen">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Breadcrumb -->
        <nav aria-label="Breadcrumb" class="mb-6 text-sm text-gray-500 dark:text-gray-400">
            <ol class="flex items-center space-x-2">
                <li><a href="{{ route('etalase.index') }}" class="hover:text-blue-600">Sancaka</a></li>
                <li><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-chevron-right"><path d="m9 18 6-6-6-6"/></svg></li>

                {{-- ✅ PERBAIKAN: Cek apakah $product->category adalah object --}}
                @if($product->category && is_object($product->category))
                <li><a href="{{ route('etalase.category.show', $product->category->slug) }}" class="hover:text-blue-600">{{ $product->category->name }}</a></li>
                <li><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-chevron-right"><path d="m9 18 6-6-6-6"/></svg></li>
                @endif

                <li class="font-medium text-gray-700 dark:text-gray-300 truncate">{{ $product->name }}</li>
            </ol>
        </nav>

        <main class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-4 sm:p-6 lg:p-8">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 lg:gap-12">
                <!-- Product Images -->
                <div class="image-gallery">
                    <div class="aspect-w-1 aspect-h-1 w-full overflow-hidden rounded-xl shadow-md mb-4">
                         @php
                             // ✅ PERBAIKAN SINTAKS: Menambahkan titik (.) untuk konkatenasi
                             $imageUrl = $product->image_url ? asset('storage/' . $product->image_url) : 'https://placehold.co/600x600/E2E8F0/4A5568?text=Image+Not+Found';
                         @endphp
                        <img id="main-product-image"
                             src="{{ $imageUrl }}"
                             alt="{{ $product->name }}"
                             class="w-full h-full object-cover object-center transition-transform duration-300 ease-in-out hover:scale-105"
                             onerror="this.onerror=null;this.src='https://placehold.co/600x600/E2E8F0/4A5568?text=Image+Not+Found';">
                    </div>
                    {{-- Thumbnails --}}
                    <div class="grid grid-cols-5 gap-2 sm:gap-3">
                        <div>
                            <img src="{{ $imageUrl }}" alt="Thumbnail 1" class="thumbnail-img w-full h-auto object-cover rounded-lg cursor-pointer transition-all duration-200 hover:opacity-80 thumbnail-active" onclick="changeImage(this)" onerror="this.onerror=null;this.src='https://placehold.co/100x100/E2E8F0/4A5568?text=N/A';">
                        </div>
                        {{-- Logika untuk gambar tambahan (jika ada) --}}
                        {{-- @if($product->images && is_array(json_decode($product->images)))
                            @foreach(json_decode($product->images) as $index => $imagePath)
                                @if($loop->index < 4)
                                <div>
                                    <img src="{{ asset('storage/' . $imagePath) }}"
                                         alt="Thumbnail {{ $index + 2 }}"
                                         class="thumbnail-img w-full h-auto object-cover rounded-lg cursor-pointer transition-all duration-200 hover:opacity-80"
                                         onclick="changeImage(this)"
                                         onerror="this.onerror=null;this.src='https://placehold.co/100x100/E2E8F0/4A5568?text=N/A';">
                                </div>
                                @endif
                            @endforeach
                        @endif --}}
                    </div>
                </div>

                <!-- Product Info -->
                <div class="product-info">
                    <h1 class="text-2xl sm:text-3xl lg:text-4xl font-extrabold text-gray-900 dark:text-white mb-2">{{ $product->name }}</h1>

                    <div class="flex items-center space-x-4 mb-4 text-sm text-gray-500 dark:text-gray-400">
                        <div class="flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="currentColor" class="text-yellow-400 mr-1"><path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg>
                            <span class="font-semibold text-gray-700 dark:text-gray-300">{{ number_format($product->rating ?? 5, 1) }}</span>
                            <span class="mx-2">|</span>
                            <span>{{ $product->reviews_count ?? rand(50, 200) }} Ulasan</span>
                        </div>
                        <span class="text-gray-300 dark:text-gray-600">|</span>
                        <div class="font-semibold text-blue-600 dark:text-blue-400">{{ $product->sold_count ?? '100+' }} Terjual</div>
                    </div>

                    {{-- [DIUBAH] Tambahkan ID unik ke elemen harga, coret, dan diskon --}}
                    <div class="bg-gray-100 dark:bg-gray-700 rounded-lg p-4 my-4">
                        <div class="flex items-baseline gap-2">
                             <span id="display-price" class="text-3xl lg:text-4xl font-bold text-red-600 dark:text-red-500">
                                Rp{{ number_format($product->price, 0, ',', '.') }}
                            </span>
                            <span id="display-original-price" class="text-lg text-gray-400 dark:text-gray-500 line-through">
                                @if($product->original_price)
                                    Rp{{ number_format($product->original_price, 0, ',', '.') }}
                                @endif
                            </span>
                        </div>
                        <span id="display-discount" class="text-sm font-semibold text-green-600 dark:text-green-400 bg-green-100 dark:bg-green-900/50 px-2 py-1 rounded-full mt-2 inline-block {{ !($product->original_price && $product->original_price > $product->price) ? 'hidden' : '' }}">
                             @if($product->original_price && $product->original_price > $product->price)
                                 Diskon {{ round((($product->original_price - $product->price) / $product->original_price) * 100) }}%
                             @endif
                        </span>
                    </div>

                    <form id="add-to-cart-form" action="{{ route('cart.add') }}" method="POST"> {{-- Ubah route jika perlu --}}
                        @csrf
                        {{-- Input product_id tetap ada sebagai fallback atau info tambahan --}}
                        <input type="hidden" name="product_id" value="{{ $product->id }}">
                        {{-- Hidden input untuk menyimpan ID varian yang dipilih --}}
                         <input type="hidden" name="product_variant_id" id="selected_variant_id" value="">


                        {{-- [BARU] Bagian Pilihan Varian --}}
                        {{-- Pastikan relasi 'productVariantTypes.options' sudah di-load di Controller --}}
                        @if($product->relationLoaded('productVariantTypes') && $product->productVariantTypes->isNotEmpty())
                            <div id="variant-selection" class="mt-6 space-y-4 border-t border-b border-gray-200 dark:border-gray-700 py-6">
                                @foreach($product->productVariantTypes as $type)
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-900 dark:text-white mb-2">{{ $type->name }}</label>
                                        <div class="flex flex-wrap gap-2">
                                            @if($type->relationLoaded('options'))
                                                @foreach($type->options as $option)
                                                    <button
                                                        type="button"
                                                        class="variant-option px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm transition hover:border-blue-500 dark:hover:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800"
                                                        data-type-name="{{ $type->name }}"
                                                        data-option-name="{{ $option->name }}">
                                                        {{ $option->name }}
                                                    </button>
                                                @endforeach
                                            @endif
                                        </div>
                                        {{-- Input hidden untuk menyimpan pilihan (opsional, bisa dihandle JS saja) --}}
                                        {{-- <input type="hidden" name="selected_variants[{{ $type->name }}]" class="selected-variant-input" value=""> --}}
                                    </div>
                                @endforeach
                            </div>
                            {{-- Pesan error jika varian belum dipilih --}}
                            <p id="variant-error" class="text-red-600 text-sm mt-2 hidden">Silakan pilih semua varian.</p>
                        @endif
                        {{-- [AKHIR BARU] --}}


                        {{-- Bagian Kuantitas --}}
                        <div class="mt-6">
                            <label for="quantity" class="text-base font-semibold text-gray-900 dark:text-white">Kuantitas</label>
                            <div class="flex items-center mt-3">
                                <div class="flex items-center border border-gray-300 dark:border-gray-600 rounded-lg">
                                    <button id="button-minus" type="button" class="px-3 py-2 text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-l-lg transition disabled:opacity-50" disabled>
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                                    </button>
                                    <input type="number" id="quantity" name="quantity" class="w-16 text-center bg-transparent border-x border-gray-300 dark:border-gray-600 focus:outline-none focus:ring-0 disabled:opacity-50" value="1" min="1" max="{{ $product->stock ?? 1 }}" {{ ($product->stock ?? 0) <= 0 && !$product->productVariantTypes->isNotEmpty() ? 'disabled' : '' }}>
                                    <button id="button-plus" type="button" class="px-3 py-2 text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-r-lg transition disabled:opacity-50" {{ ($product->stock ?? 0) <= 1 && !$product->productVariantTypes->isNotEmpty() ? 'disabled' : '' }}>
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                                    </button>
                                </div>
                                <span id="display-stock" class="ml-4 text-sm text-gray-500 dark:text-gray-400">
                                    @if(!$product->productVariantTypes->isNotEmpty())
                                        Tersisa {{ $product->stock ?? '0' }} buah
                                    @else
                                        Pilih varian untuk melihat stok
                                    @endif
                                </span>
                            </div>
                        </div>

                        {{-- Tombol Aksi --}}
                        <div class="mt-8 pt-6 border-t border-gray-200 dark:border-gray-700">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <button id="add-to-cart-button" type="submit" name="action" value="add_to_cart" class="w-full flex items-center justify-center gap-2 px-6 py-3.5 border border-blue-600 text-blue-600 dark:border-blue-500 dark:text-blue-500 font-semibold rounded-lg shadow-sm hover:bg-blue-50 dark:hover:bg-blue-500/10 transition-colors duration-200 disabled:opacity-50 disabled:hover:bg-transparent" {{ ($product->stock ?? 0) <= 0 && !$product->productVariantTypes->isNotEmpty() ? 'disabled' : '' }}>
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
                                    Masukkan Keranjang
                                </button>
                                 <button id="buy-now-button" type="submit" name="action" value="buy_now" class="w-full flex items-center justify-center gap-2 px-6 py-3.5 bg-blue-600 text-white font-semibold rounded-lg shadow-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition-colors duration-200 disabled:opacity-50 disabled:hover:bg-blue-600" {{ ($product->stock ?? 0) <= 0 && !$product->productVariantTypes->isNotEmpty() ? 'disabled' : '' }}>
                                    Beli Sekarang
                                </button>
                            </div>
                        </div>
                    </form>

                     {{-- Bagian Spesifikasi / Atribut --}}
                     {{-- Pastikan relasi 'category.attributes' sudah di-load di Controller --}}
                     @php
                         $hasAttributesToShow = false;
                         if ($product->relationLoaded('category') && $product->category && $product->category->relationLoaded('attributes')) {
                             // Decode attributes_data sekali saja
                             $attributesProductData = is_string($product->attributes_data) ? json_decode($product->attributes_data, true) : ($product->attributes_data ?? []);

                             // Cek apakah ada atribut yang punya nilai untuk ditampilkan
                             foreach ($product->category->attributes as $attributeDefinition) {
                                 // Gunakan $attributeDefinition->name sebagai key jika controller syncAttributes pakai 'name'
                                 // ATAU cari berdasarkan slug jika $attributesProductData key-nya adalah slug
                                 $attributeValue = $attributesProductData[$attributeDefinition->slug] ?? ($attributesProductData[$attributeDefinition->name] ?? null);
                                 if ($attributeValue !== null && $attributeValue !== '' && (!is_array($attributeValue) || !empty(array_filter($attributeValue)))) {
                                     $hasAttributesToShow = true;
                                     break; // Cukup satu atribut ada nilai
                                 }
                             }
                         }
                     @endphp

                     @if ($hasAttributesToShow)
                         <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                             <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-4">Spesifikasi Produk</h3>
                             <dl class="space-y-3">
                                 @foreach ($product->category->attributes as $attributeDefinition)
                                    @php
                                        // Ambil nilai berdasarkan slug atau nama
                                        $value = $attributesProductData[$attributeDefinition->slug] ?? ($attributesProductData[$attributeDefinition->name] ?? null);
                                        $displayValue = '';
                                        if ($value !== null && $value !== '') {
                                             if (is_array($value)) {
                                                  // Filter array kosong jika perlu
                                                  $filteredValue = array_filter($value);
                                                  if (!empty($filteredValue)) {
                                                      $displayValue = implode(', ', $filteredValue);
                                                  }
                                             } else {
                                                  $displayValue = $value;
                                             }
                                        }
                                    @endphp
                                     @if (!empty($displayValue))
                                         <div class="grid grid-cols-3 gap-4 text-sm">
                                             <dt class="text-gray-500 dark:text-gray-400">{{ $attributeDefinition->name }}</dt>
                                             <dd class="col-span-2 font-medium text-gray-800 dark:text-gray-200">{{ $displayValue }}</dd>
                                         </div>
                                     @endif
                                 @endforeach
                             </dl>
                         </div>
                     @endif
                     {{-- Akhir Bagian Spesifikasi --}}

                </div>
            </div>
        </main>

        @if ($product->store_name)
        <!-- Store Info -->
        <div class="mt-8 lg:mt-12 bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6">
            <div class="flex flex-col sm:flex-row items-start sm:items-center gap-4">
                <img src="{{ $product->seller_logo ? asset('storage/' . $product->seller_logo) : 'https://placehold.co/80x80/E2E8F0/4A5568?text=Toko' }}"
                     alt="Logo {{ $product->store_name }}"
                     class="w-16 h-16 sm:w-20 sm:h-20 rounded-full border-2 border-gray-200 dark:border-gray-700 object-cover">

                <div class="flex-grow">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">{{ $product->store_name }}</h3>
                    <div class="flex items-center text-sm text-gray-500 dark:text-gray-400 mt-1">
                        {{-- Logika status toko bisa ditambahkan di sini jika ada --}}
                        <span class="w-2.5 h-2.5 bg-gray-400 rounded-full mr-2"></span>
                        <span>Offline</span>
                    </div>
                </div>

                <div class="flex w-full sm:w-auto flex-col sm:flex-row gap-3 mt-4 sm:mt-0">
                    @if(Auth::check() && $product->seller_wa)
                        <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $product->seller_wa) }}" target="_blank" class="w-full sm:w-auto flex items-center justify-center gap-2 px-4 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg transition-colors hover:bg-gray-100 dark:hover:bg-gray-700">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m3 21 1.65-3.8a9 9 0 1 1 3.4 2.9l-5.05.9z"/></svg>
                            Chat Penjual
                        </a>
                    @endif
                     {{-- Pastikan route 'toko.profile' ada dan menerima parameter 'name' --}}
                     @if(Route::has('toko.profile'))
                         <a href="{{ route('toko.profile', ['name' => $product->store_slug ?? Str::slug($product->store_name)]) }}" class="w-full sm:w-auto flex items-center justify-center gap-2 px-4 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m21 7-4-4-4 4M17 3v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V3"/><path d="M7 21h10"/><path d="M12 16v5"/></svg>
                            Kunjungi Toko
                        </a>
                     @endif
                </div>
            </div>
        </div>
        @endif

        <!-- Deskripsi Produk -->
        <div class="mt-8 lg:mt-12 bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6">
            <h2 class="text-xl font-bold text-gray-800 dark:text-white mb-4">Deskripsi Produk</h2>
            <div id="short-description" class="prose prose-sm dark:prose-invert max-w-none break-words"> {{-- Tambah break-words --}}
                {!! nl2br(e(Str::limit($product->description, 350))) !!}
            </div>
            <div id="full-description" class="prose prose-sm dark:prose-invert max-w-none break-words hidden"> {{-- Tambah break-words --}}
                {!! nl2br(e($product->description)) !!}
            </div>
            @if(strlen($product->description ?? '') > 350)
            <div class="flex justify-center">
                <button id="toggle-description" class="mt-4 px-6 py-2 bg-blue-600 text-white rounded-lg shadow hover:bg-blue-700 transition">
                    Baca Selengkapnya
                </button>
            </div>
            @endif
        </div>

         {{-- Produk Terkait / Rekomendasi (Opsional) --}}
         {{-- Anda bisa menambahkan bagian ini jika perlu --}}

    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {

    const mainImage = document.getElementById('main-product-image');

    window.changeImage = function(element) {
        if (!element || element.src.includes('placehold.co')) return; // Tambah cek null
        if(mainImage) mainImage.src = element.src; // Tambah cek null
        document.querySelectorAll('.thumbnail-img').forEach(thumb => thumb.classList.remove('thumbnail-active'));
        element.classList.add('thumbnail-active');
    }

    const quantityInput = document.getElementById('quantity');
    const minusButton = document.getElementById('button-minus');
    const plusButton = document.getElementById('button-plus');

    function updateQuantityButtonsState() {
        if (!quantityInput || !minusButton || !plusButton) return;
        const currentValue = parseInt(quantityInput.value);
        const minVal = parseInt(quantityInput.min);
        const maxVal = parseInt(quantityInput.max);
        minusButton.disabled = currentValue <= minVal || quantityInput.disabled;
        plusButton.disabled = currentValue >= maxVal || quantityInput.disabled;
    }


    if(minusButton) {
        minusButton.addEventListener('click', () => {
            let currentValue = parseInt(quantityInput.value);
            if (currentValue > parseInt(quantityInput.min)) {
                quantityInput.value = currentValue - 1;
                updateQuantityButtonsState(); // Update state setelah berubah
            }
        });
    }

    if(plusButton) {
        plusButton.addEventListener('click', () => {
            let currentValue = parseInt(quantityInput.value);
            const maxStock = parseInt(quantityInput.max);
            if(currentValue < maxStock) {
                quantityInput.value = currentValue + 1;
                 updateQuantityButtonsState(); // Update state setelah berubah
            }
        });
    }

     if(quantityInput) {
        quantityInput.addEventListener('input', () => {
             // Pastikan nilai tidak kurang dari min atau lebih dari max
             let value = parseInt(quantityInput.value);
             const minVal = parseInt(quantityInput.min);
             const maxVal = parseInt(quantityInput.max);
             if (isNaN(value) || value < minVal) {
                 quantityInput.value = minVal;
             } else if (value > maxVal) {
                 quantityInput.value = maxVal;
             }
             updateQuantityButtonsState(); // Update state setelah berubah
        });
     }


    const toggleBtn = document.getElementById("toggle-description");
    if (toggleBtn) {
        toggleBtn.addEventListener("click", function () {
            const shortDesc = document.getElementById("short-description");
            const fullDesc = document.getElementById("full-description");

            if (shortDesc && fullDesc) { // Tambah cek null
                if (fullDesc.classList.contains("hidden")) {
                    shortDesc.classList.add("hidden");
                    fullDesc.classList.remove("hidden");
                    this.textContent = "Tampilkan Lebih Sedikit";
                } else {
                    shortDesc.classList.remove("hidden");
                    fullDesc.classList.add("hidden");
                    this.textContent = "Baca Selengkapnya";
                }
            }
        });
    }

    // --- [BARU] Logika Varian ---
    const variantSelectionDiv = document.getElementById('variant-selection');
    const displayPriceEl = document.getElementById('display-price');
    const displayOriginalPriceEl = document.getElementById('display-original-price');
    const displayDiscountEl = document.getElementById('display-discount');
    const displayStockEl = document.getElementById('display-stock');
    const selectedVariantIdInput = document.getElementById('selected_variant_id');
    const variantErrorEl = document.getElementById('variant-error');
    const addToCartButton = document.getElementById('add-to-cart-button');
    const buyNowButton = document.getElementById('buy-now-button');

    // Data varian dari Blade
    const productVariantsData = @json($product->relationLoaded('productVariants') ? $product->productVariants->mapWithKeys(function($variant) {
        $key = '';
        if ($variant->relationLoaded('options')) {
            $key = $variant->options->sortBy('productVariantType.name')
                                ->map(fn($option) => optional($option->productVariantType)->name . ':' . $option->name) // Gunakan optional()
                                ->implode(';');
        }
        return [$key => [
            'id' => $variant->id,
            'price' => $variant->price,
            'stock' => $variant->stock,
            'sku' => $variant->sku_code,
            // 'image_url' => $variant->image_url ? asset('storage/' . $variant->image_url) : null
        ]];
    })->all() : []);


    const variantTypesCount = {{ $product->relationLoaded('productVariantTypes') ? $product->productVariantTypes->count() : 0 }};
    let selectedOptions = {}; // { 'Warna': 'Merah', 'Ukuran': 'S' }

    if (variantSelectionDiv && variantTypesCount > 0) {
        const optionButtons = variantSelectionDiv.querySelectorAll('.variant-option');

        optionButtons.forEach(button => {
            button.addEventListener('click', () => {
                const typeName = button.dataset.typeName;
                const optionName = button.dataset.optionName;

                // Toggle selection or select new
                if (selectedOptions[typeName] === optionName) {
                    // Deselect if clicking the active one again
                    // delete selectedOptions[typeName]; // Hapus ini jika tidak ingin bisa deselect
                    // button.classList.remove('active'); // Hapus ini jika tidak ingin bisa deselect
                } else {
                    // Select the new option
                    selectedOptions[typeName] = optionName;

                     // Update visual state for the group
                     variantSelectionDiv.querySelectorAll(`.variant-option[data-type-name="${typeName}"]`).forEach(btn => {
                        btn.classList.remove('active'); // Gunakan kelas CSS
                    });
                    button.classList.add('active'); // Gunakan kelas CSS
                }


                // Check if all variants are selected
                if (Object.keys(selectedOptions).length === variantTypesCount) {
                    updateProductDetails();
                    if(variantErrorEl) variantErrorEl.classList.add('hidden');
                } else {
                    resetProductDetailsToDefault('Pilih varian untuk melihat stok');
                }
            });
        });
    }

    function generateVariantKey(options) {
        if (Object.keys(options).length !== variantTypesCount) return null; // Belum lengkap
        return Object.keys(options)
            .sort()
            .map(typeName => `${typeName}:${options[typeName]}`)
            .join(';');
    }

    function updateProductDetails() {
        const currentKey = generateVariantKey(selectedOptions);
        if (!currentKey) return; // Belum lengkap

        const selectedVariant = productVariantsData[currentKey];

        if (selectedVariant) {
            // Update Harga
            if(displayPriceEl) displayPriceEl.textContent = `Rp${number_format(selectedVariant.price, 0, ',', '.')}`;
            if(displayOriginalPriceEl) displayOriginalPriceEl.textContent = ''; // Kosongkan harga coret
            if(displayDiscountEl) displayDiscountEl.classList.add('hidden'); // Sembunyikan diskon

            // Update Stok
            const currentStock = selectedVariant.stock;
            if(displayStockEl) displayStockEl.textContent = `Tersisa ${currentStock} buah`;

            if(quantityInput) {
                quantityInput.max = currentStock > 0 ? currentStock : 1;
                if (parseInt(quantityInput.value) > currentStock || currentStock <= 0) {
                    quantityInput.value = currentStock > 0 ? 1 : 1; // Reset ke 1 jika stok ada, atau tetap 1 jika habis
                }
                 quantityInput.disabled = currentStock <= 0;
            }

            updateQuantityButtonsState(); // Update tombol +/-

            // Update hidden input variant ID
            if(selectedVariantIdInput) selectedVariantIdInput.value = selectedVariant.id;

             // Enable/Disable tombol beli/keranjang
            const disableActions = currentStock <= 0;
            if(addToCartButton) addToCartButton.disabled = disableActions;
            if(buyNowButton) buyNowButton.disabled = disableActions;
            if(disableActions && displayStockEl) displayStockEl.textContent = 'Stok habis';


            // (Opsional) Update Gambar Utama
            // if (selectedVariant.image_url && mainImage) { mainImage.src = selectedVariant.image_url; }

        } else {
            console.warn("Kombinasi varian tidak ditemukan:", currentKey);
            resetProductDetailsToDefault('Kombinasi tidak tersedia');
        }
    }

    function resetProductDetailsToDefault(message = 'Pilih varian untuk melihat stok') {
        const defaultPrice = {{ $product->price ?? 0 }};
        const defaultOriginalPrice = {{ $product->original_price ?? 0 }};

        if(displayPriceEl) displayPriceEl.textContent = `Rp${number_format(defaultPrice, 0, ',', '.')}`;

        if(displayOriginalPriceEl) {
            if (defaultOriginalPrice > 0) {
                displayOriginalPriceEl.textContent = `Rp${number_format(defaultOriginalPrice, 0, ',', '.')}`;
            } else {
                 displayOriginalPriceEl.textContent = '';
            }
        }
        if(displayDiscountEl) {
            if (defaultOriginalPrice > defaultPrice) {
                const discount = Math.round(((defaultOriginalPrice - defaultPrice) / defaultOriginalPrice) * 100);
                displayDiscountEl.textContent = `Diskon ${discount}%`;
                displayDiscountEl.classList.remove('hidden');
            } else {
                displayDiscountEl.classList.add('hidden');
            }
        }

        if(displayStockEl) displayStockEl.textContent = message;
        if(quantityInput) {
            quantityInput.max = 1;
            quantityInput.value = 1;
            quantityInput.disabled = true; // Disable sampai varian dipilih
        }
        if(selectedVariantIdInput) selectedVariantIdInput.value = '';

        updateQuantityButtonsState(); // Disable tombol +/-

        // Disable tombol beli/keranjang
        if(addToCartButton) addToCartButton.disabled = true;
        if(buyNowButton) buyNowButton.disabled = true;

    }

    // Fungsi format angka
    function number_format(number, decimals, dec_point, thousands_sep) {
        number = (number + '').replace(/[^0-9+\-Ee.]/g, '');
        var n = !isFinite(+number) ? 0 : +number,
            prec = !isFinite(+decimals) ? 0 : Math.abs(decimals),
            sep = (typeof thousands_sep === 'undefined') ? '.' : thousands_sep, // Ubah ke titik
            dec = (typeof dec_point === 'undefined') ? ',' : dec_point, // Ubah ke koma
            s = '',
            toFixedFix = function (n, prec) {
                var k = Math.pow(10, prec);
                return '' + Math.round(n * k) / k;
            };
        s = (prec ? toFixedFix(n, prec) : '' + Math.round(n)).split('.');
        if (s[0].length > 3) {
            s[0] = s[0].replace(/\B(?=(?:\d{3})+(?!\d))/g, sep);
        }
        // if ((s[1] || '').length < prec) { // Komen ini jika tidak butuh desimal 00
        //    s[1] = s[1] || '';
        //    s[1] += new Array(prec - s[1].length + 1).join('0');
        // }
        return s.join(dec);
    }

     // Validasi sebelum submit form
    const productForm = document.getElementById('add-to-cart-form');
    if(productForm && variantTypesCount > 0) {
        productForm.addEventListener('submit', function(event) {
            if (Object.keys(selectedOptions).length !== variantTypesCount || !selectedVariantIdInput.value) {
                event.preventDefault();
                if(variantErrorEl) variantErrorEl.classList.remove('hidden');
                if(variantSelectionDiv) variantSelectionDiv.scrollIntoView({ behavior: 'smooth', block: 'center' });
            } else {
                 if(variantErrorEl) variantErrorEl.classList.add('hidden');
            }
        });
    }

     // Inisialisasi: Disable tombol dan quantity jika ada varian, ATAU jika tidak ada varian tapi stok 0
    if (variantTypesCount > 0) {
        resetProductDetailsToDefault(); // Panggil ini untuk state awal jika ada varian
    } else {
        // Jika tidak ada varian, update tombol quantity berdasarkan stok produk utama
        updateQuantityButtonsState(); // Update tombol +/- berdasarkan stok awal
        const initialStock = {{ $product->stock ?? 0 }};
        if (quantityInput) quantityInput.disabled = initialStock <= 0;
        if (addToCartButton) addToCartButton.disabled = initialStock <= 0;
        if (buyNowButton) buyNowButton.disabled = initialStock <= 0;
         if (initialStock <= 0 && displayStockEl) displayStockEl.textContent = 'Stok habis';
    }


}); // Akhir DOMContentLoaded
</script>
@endpush
