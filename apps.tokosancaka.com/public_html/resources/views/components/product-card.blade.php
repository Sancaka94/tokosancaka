@php
    // LOGIKA PERHITUNGAN DISKON DINAMIS DARI DATABASE
    $originalPrice = $product->sell_price ?? 0;
    $finalPrice = $originalPrice;
    $discountAmount = 0;
    $hasDiscount = false;
    $discountBadge = '';
    $coretPrice = 0;

    // Cek apakah produk ini memiliki input diskon baru
    if (isset($product->discount_value) && $product->discount_value > 0) {
        $hasDiscount = true;
        $coretPrice = $originalPrice;

        if ($product->discount_type === 'percent') {
            $discountAmount = $originalPrice * ($product->discount_value / 100);
            $discountBadge = '-' . round($product->discount_value) . '%';
        } else {
            $discountAmount = $product->discount_value;
            $discountBadge = '-Rp' . number_format($discountAmount / 1000, 0, '', '') . 'k';
        }
        $finalPrice = max(0, $originalPrice - $discountAmount);
    }
    // Fallback: Jika tidak pakai input diskon baru, tapi ada selisih base_price dan sell_price lama
    elseif (isset($product->base_price) && $product->base_price > $product->sell_price) {
        $hasDiscount = true;
        $coretPrice = $product->base_price;
        $finalPrice = $product->sell_price;
        $discountAmount = $coretPrice - $finalPrice;
        $discountBadge = '-' . round(($discountAmount / $coretPrice) * 100) . '%';
    }
@endphp

<div class="{{ $is_horizontal ? 'w-36 md:w-48 flex-shrink-0 snap-start' : '' }} bg-white rounded-md shadow-sm border border-gray-200 overflow-hidden hover:shadow-lg hover:-translate-y-0.5 transition-all duration-200 flex flex-col h-full group relative">

    <a href="{{ route('storefront.product.detail', ['subdomain' => $subdomain ?? '', 'slug' => $product->slug ?? $product->id]) }}" class="flex flex-col h-full">

        <div class="aspect-square bg-gray-100 relative overflow-hidden flex items-center justify-center">
            @if($product->image)
                <img src="{{ asset('storage/'.$product->image) }}" alt="{{ $product->name }}" class="w-full h-full object-cover">
            @else
                <i data-lucide="image" class="w-10 h-10 text-gray-300"></i>
            @endif

            {{-- BADGE DISKON MERAH --}}
            @if($hasDiscount)
                <div class="absolute top-0 right-0 bg-red-600 text-white text-[10px] md:text-xs font-black px-1.5 py-1 rounded-bl-lg shadow-sm z-10">
                    {{ $discountBadge }}
                </div>
            @endif

            @if(($product->sold ?? 0) > 50)
                <div class="absolute top-2 left-0 bg-orange-500 text-white text-[9px] md:text-[10px] font-bold px-1.5 py-0.5 rounded-r-sm shadow-sm flex items-center gap-0.5 z-10">
                    <i data-lucide="star" class="w-2.5 h-2.5 fill-current"></i> Star+
                </div>
            @endif

            @if($product->stock < 5 && $product->stock > 0)
                <div class="absolute bottom-0 left-0 w-full bg-red-500 bg-opacity-80 text-white text-[10px] font-bold text-center py-1 z-10 backdrop-blur-sm">
                    Sisa {{ $product->stock }}
                </div>
            @endif
        </div>

        <div class="p-2 md:p-3 flex flex-col flex-grow">
            <h4 class="text-xs md:text-sm text-gray-800 line-clamp-2 leading-snug mb-1 md:mb-2 flex-grow">
                {{ $product->name }}
            </h4>

            <div class="flex flex-wrap gap-1 mb-1">
                {{-- BADGE CASHBACK EXTRA DINAMIS --}}
                @if($product->is_cashback_extra)
                <span class="text-[9px] md:text-[10px] text-red-500 border border-red-500 px-1 rounded-sm bg-white font-medium">
                    Cashback XTRA
                </span>
                @endif

                {{-- BADGE GRATIS ONGKIR DINAMIS --}}
                @if($product->is_free_ongkir)
                <span class="text-[9px] md:text-[10px] text-teal-600 border border-teal-500 bg-teal-50 px-1 rounded-sm flex items-center gap-0.5 font-bold">
                    <i data-lucide="truck" class="w-2.5 h-2.5"></i> Gratis Ongkir
                </span>
                @endif
            </div>

            <div class="mt-auto">
                {{-- TAMPILAN HARGA --}}
                @if($hasDiscount)
                    {{-- HARGA CORET DI KIRI, HARGA FINAL DI KANAN --}}
                    <div class="flex items-baseline gap-1.5 mb-0.5">
                        <div class="text-red-700 font-black text-sm md:text-base truncate">
                            Rp {{ number_format($finalPrice, 0, ',', '.') }}
                        </div>

                        <div class="text-[10px] md:text-xs text-red-400 line-through truncate">
                            Rp {{ number_format($coretPrice, 0, ',', '.') }}
                        </div>

                    </div>
                    <div class="text-[9px] md:text-[10px] text-emerald-600 font-bold mt-0.5 bg-emerald-50 w-max px-1.5 py-0.5 rounded">
                        Anda Lebih Hemat Rp {{ number_format($discountAmount, 0, ',', '.') }}
                    </div>
                @else
                    <div class="text-blue-700 font-bold text-sm md:text-base truncate">
                        Rp {{ number_format($finalPrice, 0, ',', '.') }}
                    </div>
                @endif
            </div>

            <div class="flex justify-between items-center mt-1.5 pt-1.5 border-t border-gray-100 text-[9px] md:text-[11px] text-gray-500">
                <div class="flex items-center text-yellow-400">
                    <i data-lucide="star" class="w-3 h-3 fill-current"></i>
                    <span class="text-gray-500 ml-1">{{ $product->rating ?? '5.0' }}</span>
                </div>
                <div>{{ $product->sold ?? 0 }} Terjual</div>
            </div>
        </div>
    </a>

    <div class="px-2 pb-2 mt-auto">
        <button @click.prevent="addToCart({{ json_encode([
                    'id' => $product->id,
                    'name' => $product->name,
                    'sell_price' => $finalPrice,
                    'image' => $product->image ? asset('storage/'.$product->image) : ''
                ]) }})"
                class="w-full bg-red-600 text-white py-1.5 md:py-2 rounded border border-red-600 text-[11px] md:text-sm font-semibold hover:bg-red-700 hover:shadow-md transition flex justify-center items-center gap-1 active:scale-95 group-hover:visible">
            <i data-lucide="shopping-cart" class="w-3 h-3 md:w-4 md:h-4"></i> Beli
        </button>
    </div>
</div>
