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
    </style>
@endpush

@section('content')
<div class="bg-gray-50 dark:bg-gray-900 text-gray-800 dark:text-gray-200">
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
                             $imageUrl = $product->image_url ? asset('storage/' ( $product->image_url)) : 'https://placehold.co/600x600/E2E8F0/4A5568?text=Image+Not+Found';
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
                        @if($product->images && is_array(json_decode($product->images)))
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
                        @endif
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

                    <div class="bg-gray-100 dark:bg-gray-700 rounded-lg p-4 my-4">
                        <div class="flex items-baseline gap-2">
                           <span class="text-3xl lg:text-4xl font-bold text-red-600 dark:text-red-500">Rp{{ number_format($product->price, 0, ',', '.') }}</span>
                           @if($product->original_price)
                               <span class="text-lg text-gray-400 dark:text-gray-500 line-through">Rp{{ number_format($product->original_price, 0, ',', '.') }}</span>
                           @endif
                        </div>
                        @if($product->original_price && $product->original_price > $product->price)
                            <span class="text-sm font-semibold text-green-600 dark:text-green-400 bg-green-100 dark:bg-green-900/50 px-2 py-1 rounded-full mt-2 inline-block">
                                Diskon {{ round((($product->original_price - $product->price) / $product->original_price) * 100) }}%
                            </span>
                        @endif
                    </div>
                    
                    @php
                        $attributesData = is_string($product->attributes_data) ? json_decode($product->attributes_data, true) : ($product->attributes_data ?? []);
                    @endphp

                    {{-- ✅ PERBAIKAN: Cek apakah $product->category adalah object --}}
                    @if (!empty($attributesData) && $product->category && is_object($product->category) && $product->category->attributes->isNotEmpty())
                        <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                            <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-4">Spesifikasi Produk</h3>
                            <dl class="space-y-3">
                                @foreach ($product->category->attributes as $attribute)
                                    @if (isset($attributesData[$attribute->slug]) && !empty($attributesData[$attribute->slug]))
                                        @php
                                            $value = $attributesData[$attribute->slug];
                                            if (is_array($value)) {
                                                $value = implode(', ', $value);
                                            }
                                        @endphp
                                        <div class="grid grid-cols-3 gap-4 text-sm">
                                            <dt class="text-gray-500 dark:text-gray-400">{{ $attribute->name }}</dt>
                                            <dd class="col-span-2 font-medium text-gray-800 dark:text-gray-200">{{ $value }}</dd>
                                        </div>
                                    @endif
                                @endforeach
                            </dl>
                        </div>
                    @endif
                    
                    <form action="{{ route('cart.add', ['product' => $product->id]) }}" method="POST">
                        @csrf
                        <input type="hidden" name="product_id" value="{{ $product->id }}">
                        
                        <div class="mt-6">
                            <label for="quantity" class="text-base font-semibold text-gray-900 dark:text-white">Kuantitas</label>
                            <div class="flex items-center mt-3">
                                <div class="flex items-center border border-gray-300 dark:border-gray-600 rounded-lg">
                                    <button id="button-minus" type="button" class="px-3 py-2 text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-l-lg transition">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                                    </button>
                                    <input type="number" id="quantity" name="quantity" class="w-16 text-center bg-transparent border-x border-gray-300 dark:border-gray-600 focus:outline-none focus:ring-0" value="1" min="1" max="{{ $product->stock ?? 99 }}">
                                    <button id="button-plus" type="button" class="px-3 py-2 text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-r-lg transition">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                                    </button>
                                </div>
                                <span class="ml-4 text-sm text-gray-500 dark:text-gray-400">Tersisa {{ $product->stock ?? '100+' }} buah</span>
                            </div>
                        </div>
                        
                        <div class="mt-8 pt-6 border-t border-gray-200 dark:border-gray-700">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <button type="submit" name="action" value="add_to_cart" class="w-full flex items-center justify-center gap-2 px-6 py-3.5 border border-blue-600 text-blue-600 dark:border-blue-500 dark:text-blue-500 font-semibold rounded-lg shadow-sm hover:bg-blue-50 dark:hover:bg-blue-500/10 transition-colors duration-200">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
                                    Masukkan Keranjang
                                </button>
                                 <button type="submit" name="action" value="buy_now" class="w-full flex items-center justify-center gap-2 px-6 py-3.5 bg-blue-600 text-white font-semibold rounded-lg shadow-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition-colors duration-200">
                                    Beli Sekarang
                                </button>
                            </div>
                        </div>
                    </form>
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
                        <span class="w-2.5 h-2.5 bg-gray-400 rounded-full mr-2"></span>
                        <span>Offline</span>
                    </div>
                </div>

                <div class="flex w-full sm:w-auto flex-col sm:flex-row gap-3 mt-4 sm:mt-0">
                    @if(Auth::check() && $product->seller_wa)
                        <a href="https://wa.me/{{ preg_replace('/^0/', '62', $product->seller_wa) }}" target="_blank" class="w-full sm:w-auto flex items-center justify-center gap-2 px-4 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg transition-colors hover:bg-gray-100 dark:hover:bg-gray-700">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m3 21 1.65-3.8a9 9 0 1 1 3.4 2.9l-5.05.9z"/></svg>
                            Chat Penjual
                        </a>
                    @endif
                    <a href="{{ route('toko.profile', ['name' => $product->store_name]) }}" class="w-full sm:w-auto flex items-center justify-center gap-2 px-4 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m21 7-4-4-4 4M17 3v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V3"/><path d="M7 21h10"/><path d="M12 16v5"/></svg>
                        Kunjungi Toko
                    </a>
                </div>
            </div>
        </div>
        @endif 
        
        <!-- Deskripsi Produk -->
        <div class="mt-8 lg:mt-12 bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6">
            <h2 class="text-xl font-bold text-gray-800 dark:text-white mb-4">Deskripsi Produk</h2>
            <div id="short-description" class="prose prose-sm dark:prose-invert max-w-none">
                {!! nl2br(e(Str::limit($product->description, 350))) !!}
            </div>
            <div id="full-description" class="prose prose-sm dark:prose-invert max-w-none hidden">
                {!! nl2br(e($product->description)) !!}
            </div>
            @if(strlen($product->description) > 350)
            <div class="flex justify-center">
                <button id="toggle-description" class="mt-4 px-6 py-2 bg-blue-600 text-white rounded-lg shadow hover:bg-blue-700 transition">
                    Baca Selengkapnya
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
        if (element.src.includes('placehold.co')) return;
        mainImage.src = element.src;
        document.querySelectorAll('.thumbnail-img').forEach(thumb => thumb.classList.remove('thumbnail-active'));
        element.classList.add('thumbnail-active');
    }

    const quantityInput = document.getElementById('quantity');
    const minusButton = document.getElementById('button-minus');
    const plusButton = document.getElementById('button-plus');
    const maxStock = parseInt(quantityInput.max) || 999;

    minusButton.addEventListener('click', () => {
        let currentValue = parseInt(quantityInput.value);
        if (currentValue > 1) quantityInput.value = currentValue - 1;
    });

    plusButton.addEventListener('click', () => {
        let currentValue = parseInt(quantityInput.value);
        if(currentValue < maxStock) quantityInput.value = currentValue + 1;
    });

    const toggleBtn = document.getElementById("toggle-description");
    if (toggleBtn) {
        toggleBtn.addEventListener("click", function () {
            const shortDesc = document.getElementById("short-description");
            const fullDesc = document.getElementById("full-description");
            
            if (fullDesc.classList.contains("hidden")) {
                shortDesc.classList.add("hidden");
                fullDesc.classList.remove("hidden");
                this.textContent = "Tampilkan Lebih Sedikit";
            } else {
                shortDesc.classList.remove("hidden");
                fullDesc.classList.add("hidden");
                this.textContent = "Baca Selengkapnya";
            }
        });
    }
});
</script>
@endpush
