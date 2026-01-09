{{-- 
    File: resources/views/marketplace/partials/product_card.blade.php
    Deskripsi: Komponen kartu produk yang bisa digunakan ulang.
--}}
<div class="bg-white border rounded-lg overflow-hidden group hover:shadow-lg transition-shadow flex flex-col text-left h-full">
    <a href="#">
        <div class="aspect-square bg-white relative">
            {{-- PERBAIKAN UTAMA ADA DI SINI --}}
            <img src="{{ $product->image_url ? asset('public/storage/' . $product->image_url) : 'https://placehold.co/400x400/e2e8f0/94a3b8?text=Produk' }}" 
                 alt="{{ $product->name }}" 
                 class="w-full h-full object-contain p-2">
            
            @if(isset($product->discount_percentage) && $product->discount_percentage > 0)
            <span class="absolute top-2 left-2 bg-red-100 text-red-600 text-xs font-bold px-2 py-0.5 rounded-full">{{ $product->discount_percentage }}%</span>
            @endif
        </div>
    </a>
    <div class="p-3 flex flex-col flex-grow">
        <h3 class="text-sm font-medium text-gray-800 leading-tight h-10">{{ Str::limit($product->name, 45) }}</h3>
        <p class="text-base font-bold text-red-600 mt-2">Rp{{ number_format($product->price) }}</p>
        @if(isset($product->original_price) && $product->original_price > $product->price)
        <s class="text-xs text-gray-400">Rp{{ number_format($product->original_price) }}</s>
        @endif
        <div class="text-xs text-gray-500 mt-2">Terjual {{ $product->sold_count ?? 0 }}</div>
        <div class="mt-auto pt-3">
            @if($product->stock > 0)
            <form action="{{ route('customer.cart.add', $product) }}" method="POST">
                @csrf
                <button type="submit" class="w-full bg-red-500 text-white font-bold py-2 rounded-md text-sm hover:bg-red-600 transition-colors">+ Keranjang</button>
            </form>
            @else
            <button class="w-full bg-gray-300 text-gray-500 font-bold py-2 rounded-md text-sm cursor-not-allowed">Stok Habis</button>
            @endif
        </div>
    </div>
</div>
