@extends('layouts.marketplace')

@section('title', $product->name)

@section('content')
<div class="bg-gray-50 py-8 min-h-screen"
     x-data="productPage()"
     x-init="initData()">

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        {{-- BREADCRUMB --}}
        <nav class="flex text-sm text-gray-500 mb-6 items-center">
            <a href="{{ route('storefront.index', $subdomain) }}" class="hover:text-blue-600 font-medium">Beranda</a>
            <i data-lucide="chevron-right" class="w-4 h-4 mx-2"></i>
            <span class="text-gray-800 font-bold truncate" x-text="productName"></span>
        </nav>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden flex flex-col md:flex-row">

            {{-- KIRI: GALERI FOTO --}}
            <div class="md:w-2/5 p-4 md:p-6 flex flex-col bg-white border-r border-gray-100">
                <div class="aspect-square w-full relative overflow-hidden rounded-xl bg-gray-50 flex justify-center items-center shadow-inner mb-4">
                    <template x-if="mainImage">
                        <img :src="mainImage" alt="{{ $product->name }}" class="w-full h-full object-contain transition-all duration-300">
                    </template>
                    <template x-if="!mainImage">
                        <div class="text-gray-300 flex flex-col items-center">
                            <i data-lucide="image" class="w-20 h-20"></i>
                            <p class="text-xs font-bold mt-2">No Image</p>
                        </div>
                    </template>

                    {{-- BADGE DISKON OVERLAY (MERAH) --}}
                    <template x-if="discountBadge">
                        <div class="absolute top-0 right-0 bg-red-600 text-white text-xs md:text-sm font-black px-3 py-1.5 rounded-bl-xl shadow-md z-10" x-text="discountBadge"></div>
                    </template>
                </div>

                <div class="flex gap-3 overflow-x-auto pb-2 snap-x hide-scrollbar">
                    <template x-for="(img, index) in gallery" :key="index">
                        <div @click="mainImage = img"
                             class="w-16 h-16 md:w-20 md:h-20 flex-shrink-0 rounded-lg overflow-hidden cursor-pointer border-2 transition-all duration-200 snap-start"
                             :class="mainImage === img ? 'border-blue-600 shadow-md' : 'border-transparent hover:border-gray-300 bg-gray-50'">
                            <img :src="img" class="w-full h-full object-cover">
                        </div>
                    </template>
                </div>
            </div>

            {{-- KANAN: INFO & PILIHAN --}}
            <div class="md:w-3/5 p-6 md:p-8 flex flex-col">

                {{-- GROUP BADGE DINAMIS --}}
                <div class="flex flex-wrap items-center gap-2 mb-4">
                    @if($product->is_flash_sale)
                        <span class="bg-orange-600 text-white text-[10px] font-bold px-2 py-1 rounded flex items-center gap-1 shadow-sm">
                            <i data-lucide="zap" class="w-3 h-3 fill-current"></i> FLASH SALE
                        </span>
                    @endif

                    @if($product->is_cashback_extra)
                        <span class="bg-white text-red-500 border border-red-500 text-[10px] font-bold px-2 py-1 rounded shadow-sm">
                            CASHBACK XTRA
                        </span>
                    @endif

                    @if($product->is_free_ongkir)
                        <span class="bg-teal-50 text-teal-600 border border-teal-500 text-[10px] font-bold px-2 py-1 rounded flex items-center gap-1 shadow-sm">
                            <i data-lucide="truck" class="w-3 h-3"></i> GRATIS ONGKIR
                        </span>
                    @endif

                    @if($product->is_best_seller)
                        <span class="bg-blue-600 text-white text-[10px] font-bold px-2 py-1 rounded shadow-sm">BEST SELLER</span>
                    @endif

                    <div class="flex items-center text-yellow-400 text-sm ml-auto">
                        <i data-lucide="star" class="w-4 h-4 fill-current"></i>
                        <span class="text-gray-700 ml-1 font-bold">{{ $product->rating ?? '5.0' }}</span>
                        <span class="text-gray-300 mx-2">|</span>
                        <span class="text-gray-600 text-xs">{{ $product->sold ?? 0 }} Terjual</span>
                    </div>
                </div>

                <h1 class="text-2xl md:text-3xl font-bold text-gray-900 mb-4 leading-tight">{{ $product->name }}</h1>

                {{-- AREA HARGA & DISKON DINAMIS --}}
                <div class="bg-blue-50/50 p-5 rounded-2xl mb-6 border border-blue-100">
                    <div class="flex items-center gap-3 flex-wrap">
                        <span class="text-3xl md:text-4xl font-black text-blue-700">Rp <span x-text="formatPrice(currentPrice)"></span></span>

                        {{-- Harga Coret (Hanya muncul jika ada diskon) --}}
                        <template x-if="originalPrice > currentPrice">
                            <div class="flex items-center gap-2">
                                <span class="text-gray-400 line-through text-sm md:text-base">Rp <span x-text="formatPrice(originalPrice)"></span></span>
                                <span class="bg-red-100 text-red-600 text-xs font-black px-2 py-0.5 rounded" x-text="discountBadge"></span>
                            </div>
                        </template>
                    </div>

                    {{-- Pesan Hemat --}}
                    <template x-if="originalPrice > currentPrice">
                        <div class="text-[11px] md:text-xs text-emerald-600 font-bold mt-2 flex items-center gap-1 bg-emerald-50 w-fit px-2 py-1 rounded-lg border border-emerald-100">
                            <i data-lucide="badge-check" class="w-3 h-3"></i>
                            Anda Lebih Hemat Rp <span x-text="formatPrice(originalPrice - currentPrice)"></span>
                        </div>
                    </template>
                </div>

                {{-- PILIHAN VARIAN LEVEL 1 --}}
                <template x-if="variants.length > 0">
                    <div class="mb-5">
                        <h3 class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-3">Pilih Varian:</h3>
                        <div class="flex flex-wrap gap-3">
                            <template x-for="variant in variants" :key="variant.id">
                                <button @click="selectVariant(variant)"
                                        class="px-4 py-2 border-2 rounded-xl text-sm font-bold transition-all relative overflow-hidden"
                                        :class="selectedVariant && selectedVariant.id === variant.id ? 'border-blue-600 bg-blue-50 text-blue-700' : 'border-gray-100 text-gray-600 hover:border-blue-300 bg-white'">
                                    <span x-text="variant.name"></span>
                                    {{-- Mini Badge Diskon di Tombol Varian --}}
                                    <template x-if="variant.discount_value > 0">
                                        <div class="absolute top-0 right-0 bg-red-500 text-white text-[8px] px-1 font-black" x-text="variant.discount_type === 'percent' ? '-' + Math.round(variant.discount_value) + '%' : 'PROMO'"></div>
                                    </template>
                                </button>
                            </template>
                        </div>
                    </div>
                </template>

                {{-- PILIHAN SUB VARIAN LEVEL 2 --}}
                <template x-if="selectedVariant && selectedVariant.sub_variants && selectedVariant.sub_variants.length > 0">
                    <div class="mb-6 animate-fade-in-down p-4 bg-orange-50/30 rounded-2xl border border-orange-100">
                        <h3 class="text-xs font-bold text-orange-500 uppercase tracking-widest mb-3">Pilih Ukuran/Tipe:</h3>
                        <div class="flex flex-wrap gap-3">
                            <template x-for="sub in selectedVariant.sub_variants" :key="sub.id">
                                <button @click="selectSubVariant(sub)"
                                        :disabled="sub.stock <= 0"
                                        class="px-4 py-2 border-2 rounded-xl text-sm font-bold transition-all relative"
                                        :class="{
                                            'border-orange-500 bg-orange-50 text-orange-700': selectedSubVariant && selectedSubVariant.id === sub.id,
                                            'border-white text-gray-600 hover:border-orange-300 bg-white shadow-sm': (!selectedSubVariant || selectedSubVariant.id !== sub.id) && sub.stock > 0,
                                            'border-transparent bg-gray-100 text-gray-300 cursor-not-allowed': sub.stock <= 0
                                        }">
                                    <span x-text="sub.name"></span>
                                    <template x-if="sub.stock <= 0"><span class="text-[8px] block">(Habis)</span></template>
                                    {{-- Mini Badge Diskon di Tombol Sub Varian --}}
                                    <template x-if="sub.discount_value > 0">
                                        <div class="absolute -top-1 -right-1 bg-red-500 text-white text-[8px] px-1 rounded-full font-black shadow-sm" x-text="sub.discount_type === 'percent' ? '-' + Math.round(sub.discount_value) + '%' : '!!'"></div>
                                    </template>
                                </button>
                            </template>
                        </div>
                    </div>
                </template>

                {{-- INFO PRODUK --}}
                <div class="grid grid-cols-2 gap-y-3 gap-x-4 mb-8 text-sm border-t border-gray-100 pt-6">
                    <div class="text-gray-500">Kondisi</div>
                    <div class="font-semibold text-gray-800 text-right">Baru</div>
                    <div class="text-gray-500">Berat</div>
                    <div class="font-semibold text-gray-800 text-right"><span x-text="currentWeight"></span> Gram</div>
                    <div class="text-gray-500">Stok Tersedia</div>
                    <div class="font-bold text-blue-600 text-right"><span x-text="currentStock"></span> {{ $product->unit ?? 'pcs' }}</div>
                </div>

                {{-- TOMBOL BELI --}}
                <div class="mt-auto">
                    <button @click="handleAddToCart()"
                            :disabled="!isReadyToBuy"
                            class="w-full px-6 py-4 rounded-2xl font-black text-lg shadow-xl transition-all duration-200 flex items-center justify-center gap-3 active:scale-[0.98]"
                            :class="isReadyToBuy ? 'bg-blue-600 text-white hover:bg-blue-700 shadow-blue-200' : 'bg-gray-200 text-gray-400 cursor-not-allowed shadow-none'">
                        <i data-lucide="shopping-cart" class="w-6 h-6"></i>
                        <span x-text="currentStock <= 0 ? 'Stok Habis' : 'Masukkan Keranjang'"></span>
                    </button>

                    <p x-show="!isReadyToBuy && currentStock > 0" class="text-center text-xs text-orange-500 mt-4 font-bold animate-pulse">
                        <i class="fas fa-hand-pointer mr-1"></i> Silakan pilih varian & tipe terlebih dahulu
                    </p>
                </div>

            </div>
        </div>

        {{-- DESKRIPSI --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 md:p-8 mt-6">
            <h3 class="text-lg font-bold text-gray-900 border-b border-gray-200 pb-3 mb-4">Deskripsi Produk</h3>
            <div class="prose max-w-none text-gray-700 text-sm leading-relaxed whitespace-pre-line">
                {{ $product->description ?? 'Tidak ada deskripsi detail untuk produk ini.' }}
            </div>
        </div>

    </div>
</div>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('productPage', () => ({
            productName: '{{ $product->name }}',
            productId: {{ $product->id }},

            // Gunakan fallback ?? 0 untuk mencegah error jika data kosong
            baseSellPrice: {{ $product->sell_price ?? 0 }},
            baseWeight: {{ $product->weight ?? 0 }},

            currentPrice: 0,
            originalPrice: 0,
            discountBadge: '',
            currentStock: {{ $product->stock ?? 0 }},
            currentWeight: {{ $product->weight ?? 0 }},

            mainImage: '{{ $product->image ? asset("storage/".$product->image) : "" }}',
            gallery: ['{{ $product->image ? asset("storage/".$product->image) : "" }}'].filter(Boolean),

            // Pastikan tidak error jika relasi variants kosong
            variants: @json($product->variants ? $product->variants->load('subVariants') : []),
            selectedVariant: null,
            selectedSubVariant: null,

            initData() {
                this.calculatePrice(
                    this.baseSellPrice,
                    '{{ $product->discount_type ?? "" }}',
                    {{ $product->discount_value ?? 0 }}
                );
            },

            // Fungsi Kalkulator Diskon Universal (Diperbarui agar kebal terhadap error NaN)
            calculatePrice(rawPrice, discType, rawDiscValue) {
                let price = parseFloat(rawPrice) || 0;
                let discValue = parseFloat(rawDiscValue) || 0;

                this.originalPrice = price;
                let final = price;

                if (discValue > 0) {
                    if (discType === 'percent') {
                        final = price - (price * (discValue / 100));
                        this.discountBadge = '-' + Math.round(discValue) + '%';
                    } else {
                        final = price - discValue;
                        this.discountBadge = '-Rp' + (discValue / 1000) + 'k';
                    }
                } else {
                    this.discountBadge = '';
                }

                // Math.round agar tidak ada angka desimal berlebih
                this.currentPrice = Math.max(0, Math.round(final));
            },

            selectVariant(variant) {
                this.selectedVariant = variant;
                this.selectedSubVariant = null;

                // PERBAIKAN: Antisipasi jika nama kolom di database adalah sell_price alih-alih price
                let variantPrice = variant.sell_price !== undefined ? variant.sell_price : variant.price;
                this.calculatePrice(variantPrice, variant.discount_type, variant.discount_value);
                this.currentStock = variant.stock || 0;
            },

            selectSubVariant(sub) {
                if(sub.stock <= 0) return;
                this.selectedSubVariant = sub;

                // PERBAIKAN: Antisipasi jika nama kolom di database adalah sell_price
                let subPrice = sub.sell_price !== undefined ? sub.sell_price : sub.price;
                this.calculatePrice(subPrice, sub.discount_type, sub.discount_value);
                this.currentStock = sub.stock || 0;
                this.currentWeight = sub.weight || this.baseWeight;
            },

            formatPrice(price) {
                return new Intl.NumberFormat('id-ID').format(price || 0);
            },

            get isReadyToBuy() {
                if (this.currentStock <= 0) return false;
                if (this.variants && this.variants.length > 0 && !this.selectedVariant) return false;
                if (this.selectedVariant && this.selectedVariant.sub_variants && this.selectedVariant.sub_variants.length > 0 && !this.selectedSubVariant) return false;
                return true;
            },

            handleAddToCart() {
                if(!this.isReadyToBuy) return;

                let fullName = this.productName;
                if(this.selectedVariant) fullName += ' - ' + this.selectedVariant.name;
                if(this.selectedSubVariant) fullName += ' (' + this.selectedSubVariant.name + ')';

                // Bikin unique ID agar varian yang berbeda dari produk yang sama tidak tertumpuk di keranjang
                let uniqueId = this.productId + '-' + (this.selectedVariant?.id || '0') + '-' + (this.selectedSubVariant?.id || '0');

                const payload = {
                    id: this.productId,
                    unique_id: uniqueId,
                    variant_id: this.selectedVariant ? this.selectedVariant.id : null,
                    sub_variant_id: this.selectedSubVariant ? this.selectedSubVariant.id : null,
                    name: fullName,
                    price: this.currentPrice,
                    qty: 1, // PERBAIKAN PENTING: Set default quantity
                    weight: this.currentWeight,
                    image: this.mainImage,
                    is_free_ongkir: {{ $product->is_free_ongkir ? 1 : 0 }},
                    is_cashback_extra: {{ $product->is_cashback_extra ? 1 : 0 }} // PERBAIKAN PENTING: Tambahkan data ini
                };

                console.log("DATA YANG DIKIRIM KE KERANJANG:", payload);

                if (typeof addToCart === 'function') {
                    addToCart(payload);
                    // Opsional: Redirect ke halaman checkout / keranjang
                    // window.location.href = "/cart";
                } else {
                    console.error("Fungsi addToCart tidak ditemukan di layout induk.");
                }
            }
        }));
    });
</script>
@endsection
