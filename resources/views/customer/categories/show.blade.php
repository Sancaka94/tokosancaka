{{-- resources/views/customer/categories/show.blade.php --}}

@extends('layouts.customer')

@section('title', 'Kategori: ' . $category->name)

@section('content')
<div class="container mx-auto py-6 px-4">
    <div class="bg-white p-4 rounded-lg shadow-sm mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Kategori: {{ $category->name }}</h1>
    </div>

    <section>
        <div class="bg-white p-3 rounded-lg shadow-sm">
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-4">
                @forelse ($products as $product)
                    {{-- Ini adalah komponen card produk, bisa disesuaikan dengan desain Anda --}}
                    <div class="bg-white border rounded-lg overflow-hidden group hover:shadow-lg transition-shadow flex flex-col text-left">
                        <a href="#">
                            <div class="aspect-square bg-white relative">
                                <img src="{{ $product->image_url ? asset($product->image_url) : 'https://placehold.co/400' }}" alt="{{ $product->name }}" class="w-full h-full object-contain p-2">
                            </div>
                        </a>
                        <div class="p-3 flex flex-col flex-grow">
                            <h3 class="text-sm font-medium text-gray-800 leading-tight h-10">{{ Str::limit($product->name, 45) }}</h3>
                            <p class="text-base font-bold text-red-600 mt-2">Rp{{ number_format($product->price) }}</p>
                             <div class="text-xs text-gray-500 mt-2">Terjual {{ $product->sold_count }}</div>
                            <div class="mt-auto pt-3">
                                <form action="{{ route('customer.cart.add', $product) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="w-full bg-red-500 text-white font-bold py-2 rounded-md text-sm hover:bg-red-600 transition-colors">+ Keranjang</button>
                                </form>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-span-full text-center py-16">
                        <p class="text-gray-500">Belum ada produk untuk kategori ini.</p>
                    </div>
                @endforelse
            </div>
             <div class="flex justify-center mt-8">{{ $products->links() }}</div>
        </div>
    </section>
</div>
@endsection