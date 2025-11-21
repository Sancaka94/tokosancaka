@extends('layouts.marketplace')

@section('title', $name . ' - Sancaka Marketplace')

@push('styles')
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
    </style>
@endpush

@section('content')
<div class="bg-gray-50 dark:bg-gray-900 text-gray-800 dark:text-gray-200 min-h-screen">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8">

        {{-- Profile Toko --}}
        <div class="mt-8 bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6">
            <div class="flex flex-col sm:flex-row items-start sm:items-center gap-4">
                <img src="{{ $products->first()?->seller_logo ? asset('storage/' . $products->first()?->seller_logo) : 'https://placehold.co/80x80/E2E8F0/4A5568?text=Toko' }}"
                     alt="Logo Toko"
                     class="w-16 h-16 sm:w-20 sm:h-20 rounded-full border-2 border-gray-200 dark:border-gray-700">

                <div class="flex-grow">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">{{ $name ?? 'Toko Default' }}</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ $products->first()?->seller_city ?? 'Kota Tidak Diketahui' }}</p>
                    <div class="flex items-center text-sm text-gray-500 dark:text-gray-400 mt-1">
                        <span class="w-2.5 h-2.5 bg-green-500 rounded-full mr-2"></span>
                        <span>Aktif 5 menit lalu</span>
                    </div>
                </div>

             <div class="flex w-full sm:w-auto flex-col sm:flex-row gap-3 mt-4 sm:mt-0">
                     @if(Auth::check())
                         <a href="https://wa.me/{{ preg_replace('/^0/', '62', $products->first()?->seller_wa ?? '') }}"
                            target="_blank"
                            class="w-full sm:w-auto flex items-center justify-center gap-2 px-4 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg transition-colors hover:bg-gray-100 dark:hover:bg-gray-700">
                             <i class="fab fa-whatsapp text-green-500"></i> Chat Penjual
                         </a>
                     @else
                         <button type="button"
                                 data-modal-target="waModal"
                                 data-modal-toggle="waModal"
                                 class="w-full sm:w-auto flex items-center justify-center gap-2 px-4 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg opacity-80 cursor-pointer">
                             <i class="fab fa-whatsapp text-green-500"></i> Chat Penjual
                         </button>
                     @endif
                 </div>

                 <div id="waModal" tabindex="-1" aria-hidden="true"
                     class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
                    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl max-w-md w-full p-6 relative">

                        <div class="flex justify-center mb-4">
                            <img src="{{ asset('public/assets/logo.jpg') }}" alt="Logo Toko"
                                 class="w-16 h-16 shadow-md">
                        </div>

                        <h2 class="text-xl font-bold text-center text-gray-800 dark:text-white mb-3">
                            Mohon Maaf
                        </h2>

                        <p class="text-center text-gray-600 dark:text-gray-300 mb-6 leading-relaxed">
                            <strong>Apakah kakak ingin menghubungi Penjual Via Whatsapp???</strong><br>
                            Jika iya, silahkan klik tombol di bawah ini untuk mendaftar sebagai <span class="font-semibold">customer terdaftar</span>.
                        </p>

                        <div class="flex justify-center gap-3">
                            <a href="/customer/register"
                               class="px-5 py-2 bg-green-500 text-white font-medium rounded-lg hover:bg-green-600 transition">
                                Lanjut WA
                            </a>
                            <button type="button"
                                    data-modal-hide="waModal"
                                    class="px-5 py-2 bg-gray-300 dark:bg-gray-700 text-gray-800 dark:text-white font-medium rounded-lg hover:bg-gray-400 dark:hover:bg-gray-600 transition">
                                Close
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Produk Toko --}}
        <section data-aos="fade-up" class="mt-10">
            <div class="bg-white p-5 rounded-t-2xl border-b-2 border-gray-100">
                <h2 class="text-xl font-bold text-center text-gray-800">PRODUK TOKO</h2>
            </div>
            <div class="p-5 bg-white rounded-b-2xl shadow-md">
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-5">
                    @forelse ($products as $product)
                        <a href="{{ route('products.show', $product->slug) }}" class="bg-white border rounded-xl overflow-hidden group hover:shadow-2xl transition-all duration-300 flex flex-col">
                            <div>
                                <div class="h-48 bg-gray-50 relative">
                                    @php
                                        $imageUrl = $product->image_url
                                            ? (Illuminate\Support\Str::startsWith($product->image_url, '/storage/')
                                                ? asset($product->image_url)
                                                : route('storage.show', ['path' => $product->image_url]))
                                            : 'https://placehold.co/400x400/EFEFEF/333333?text=N/A';
                                    @endphp
                                    <img src="{{ $imageUrl }}"
                                         alt="{{ $product->name }}"
                                         class="w-full h-full object-fill group-hover:scale-105 transition-transform">
                                </div>
                            </div>
                            <div class="p-4 flex flex-col flex-grow">
                                <h3 class="text-sm font-semibold text-gray-800 mb-1 h-10">{{ Str::limit($product->name, 50) }}</h3>
                                <p class="text-lg font-extrabold text-red-500">Rp{{ number_format($product->price, 0, ',', '.') }}</p>
                                <div class="mt-auto pt-3">
                                    <form action="{{ route('cart.add', ['product' => $product->id]) }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="product_id" value="{{ $product->id }}">
                                        <input type="hidden" name="quantity" value="1">
                                        <button type="submit" class="w-full bg-red-500 text-white font-bold py-2.5 rounded-lg text-sm hover:bg-red-600 transition-colors flex items-center justify-center gap-2">
                                            <i class="fas fa-cart-plus"></i>
                                            <span>Keranjang</span>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </a>
                    @empty
                        <div class="col-span-full text-center py-16">
                            <p class="text-gray-500">Oops! Belum ada produk yang bisa ditampilkan.</p>
                        </div>
                    @endforelse
                </div>

                <div class="text-center mt-10">
                    {{ $products->links() }}
                </div>
            </div>
        </section>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.querySelectorAll('[data-modal-toggle]').forEach(btn => {
    btn.addEventListener('click', () => {
        const target = document.getElementById(btn.getAttribute('data-modal-target'));
        target.classList.remove('hidden');
    });
});

document.querySelectorAll('[data-modal-hide]').forEach(btn => {
    btn.addEventListener('click', () => {
        const target = document.getElementById(btn.getAttribute('data-modal-hide'));
        target.classList.add('hidden');
    });
});
</script>
@endpush