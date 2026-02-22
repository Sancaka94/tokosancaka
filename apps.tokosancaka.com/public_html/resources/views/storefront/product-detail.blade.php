@extends('layouts.marketplace')

@section('title', $product->name)

@section('content')
<div class="bg-gray-50 py-8 min-h-screen"
     x-data="productPage()"
     x-init="initData()">

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <nav class="flex text-sm text-gray-500 mb-6 items-center">
            <a href="{{ route('storefront.index', $subdomain) }}" class="hover:text-blue-600 font-medium">Beranda</a>
            <i data-lucide="chevron-right" class="w-4 h-4 mx-2"></i>
            <span class="text-gray-800 font-bold truncate">{{ $product->name }}</span>
        </nav>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden flex flex-col md:flex-row">

            <div class="md:w-2/5 p-4 md:p-6 flex flex-col bg-white border-r border-gray-100">
                <div class="aspect-square w-full relative overflow-hidden rounded-xl bg-gray-50 flex justify-center items-center shadow-inner mb-4">
                    <template x-if="mainImage">
                        <img :src="mainImage" alt="{{ $product->name }}" class="w-full h-full object-contain transition-all duration-300">
                    </template>
                    <template x-if="!mainImage">
                        <i data-lucide="image" class="w-24 h-24 text-gray-300"></i>
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

            <div class="md:w-3/5 p-6 md:p-8 flex flex-col">

                <div class="flex items-center gap-3 mb-3 text-sm">
                    @if(($product->sold ?? 0) > 50)
                        <span class="bg-orange-500 text-white text-[10px] font-bold px-2 py-0.5 rounded shadow-sm flex items-center gap-1">
                            <i data-lucide="star" class="w-3 h-3 fill-current"></i> Star+
                        </span>
                    @endif
                    <div class="flex items-center text-yellow-400">
                        <i data-lucide="star" class="w-4 h-4 fill-current"></i>
                        <span class="text-gray-700 ml-1 font-medium">{{ $product->rating ?? '5.0' }}</span>
                    </div>
                    <span class="text-gray-300">|</span>
                    <span class="text-gray-600">{{ $product->sold ?? 0 }} Terjual</span>
                </div>

                <h1 class="text-2xl md:text-3xl font-bold text-gray-900 mb-4 leading-snug">{{ $product->name }}</h1>

                <div class="bg-gray-50 p-5 rounded-xl mb-6 border border-gray-100">
                    <div class="flex items-center gap-3">
                        <span class="text-3xl md:text-4xl font-black text-blue-700">Rp <span x-text="formatPrice(currentPrice)"></span></span>

                        <template x-if="!selectedVariant && basePrice > currentPrice">
                            <span class="bg-red-100 text-red-600 text-xs font-black px-2 py-1 rounded">
                                -<span x-text="Math.round(((basePrice - currentPrice) / basePrice) * 100)"></span>%
                            </span>
                        </template>
                    </div>
                    <template x-if="!selectedVariant && basePrice > currentPrice">
                        <div class="text-gray-400 line-through text-sm mt-1">
                            Rp <span x-text="formatPrice(basePrice)"></span>
                        </div>
                    </template>
                </div>

                <template x-if="variants.length > 0">
                    <div class="mb-6">
                        <h3 class="text-sm font-semibold text-gray-700 mb-3">Pilih Varian:</h3>
                        <div class="flex flex-wrap gap-3">
                            <template x-for="variant in variants" :key="variant.id">
                                <button @click="selectVariant(variant)"
                                        :disabled="variant.stock <= 0"
                                        class="px-4 py-2 border rounded-lg text-sm font-semibold transition-all duration-200"
                                        :class="{
                                            'border-blue-600 bg-blue-50 text-blue-700 ring-1 ring-blue-600': selectedVariant && selectedVariant.id === variant.id,
                                            'border-gray-200 text-gray-600 hover:border-blue-400': (!selectedVariant || selectedVariant.id !== variant.id) && variant.stock > 0,
                                            'border-gray-200 bg-gray-100 text-gray-400 cursor-not-allowed opacity-60': variant.stock <= 0
                                        }">
                                    <span x-text="variant.name"></span>
                                </button>
                            </template>
                        </div>
                        <template x-if="!selectedVariant">
                            <p class="text-xs text-orange-500 mt-2"><i data-lucide="info" class="w-3 h-3 inline"></i> Silakan pilih varian terlebih dahulu.</p>
                        </template>
                    </div>
                </template>

                <div class="flex items-center gap-4 mb-3 text-sm">
                    <span class="text-gray-500 w-24">Kondisi</span>
                    <span class="font-semibold text-gray-800">Baru</span>
                </div>
                <div class="flex items-center gap-4 mb-3 text-sm">
                    <span class="text-gray-500 w-24">Berat</span>
                    <span class="font-semibold text-gray-800">{{ $product->weight ?? 1000 }} Gram</span>
                </div>
                <div class="flex items-center gap-4 mb-8 text-sm">
                    <span class="text-gray-500 w-24">Sisa Stok</span>
                    <span class="font-bold text-blue-600">
                        <span x-text="currentStock"></span> {{ $product->unit ?? 'pcs' }}
                    </span>
                </div>

                <div class="mt-auto flex gap-3">
                    <button @click="handleAddToCart()"
                            :disabled="currentStock <= 0 || (variants.length > 0 && !selectedVariant)"
                            class="flex-1 px-6 py-3.5 rounded-lg font-bold text-lg shadow-sm transition-all duration-200 flex items-center justify-center gap-2 active:scale-95"
                            :class="(currentStock > 0 && (variants.length === 0 || selectedVariant))
                                    ? 'bg-blue-600 text-white hover:bg-blue-700 hover:shadow-lg'
                                    : 'bg-gray-300 text-gray-500 cursor-not-allowed'">
                        <i data-lucide="shopping-cart" class="w-5 h-5"></i> Masukkan Keranjang
                    </button>
                </div>

            </div>
        </div>

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
            // Data Induk
            productName: '{{ $product->name }}',
            productId: {{ $product->id }},
            basePrice: {{ $product->base_price ?? $product->sell_price }},

            // State Harga & Stok Aktif (Bisa berubah jika varian dipilih)
            currentPrice: {{ $product->sell_price }},
            currentStock: {{ $product->stock }},

            // State Galeri Foto
            // State Galeri Foto
            mainImage: '{{ $product->image ? asset("storage/".$product->image) : "" }}',
            gallery: [
                '{{ $product->image ? asset("storage/".$product->image) : "" }}',
            ].filter(Boolean), // Hapus url kosong

            // State Varian
            variants: @json($product->variants ?? []),
            selectedVariant: null,

            initData() {
                // Opsional: Jika mau auto-select varian pertama yang stoknya ada
                /*
                if(this.variants.length > 0) {
                    let firstAvail = this.variants.find(v => v.stock > 0);
                    if(firstAvail) this.selectVariant(firstAvail);
                }
                */
            },

            selectVariant(variant) {
                if(variant.stock <= 0) return; // Cegah klik jika stok habis
                this.selectedVariant = variant;
                this.currentPrice = variant.price;
                this.currentStock = variant.stock;
            },

            formatPrice(price) {
                return new Intl.NumberFormat('id-ID').format(price);
            },

            handleAddToCart() {
                // Validasi jika produk punya varian tapi belum dipilih
                if (this.variants.length > 0 && !this.selectedVariant) {
                    alert('Silakan pilih varian produk terlebih dahulu!');
                    return;
                }

                // Susun nama akhir (Gabungan nama induk + nama varian)
                let finalName = this.productName;
                if (this.selectedVariant) {
                    finalName = this.productName + ' (' + this.selectedVariant.name + ')';
                }

                // Panggil fungsi addToCart global yang ada di layouts.marketplace
                addToCart({
                    id: this.productId,
                    variant_id: this.selectedVariant ? this.selectedVariant.id : null,
                    name: finalName,
                    sell_price: this.currentPrice,
                    image: this.mainImage
                });
            }
        }));
    });
</script>
@endsection
