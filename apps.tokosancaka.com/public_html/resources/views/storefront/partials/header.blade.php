<header class="bg-white shadow-sm sticky top-0 z-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-20">

            <a href="{{ route('storefront.index', $subdomain) }}" class="flex items-center gap-3">
                <div class="w-10 h-10 bg-blue-600 rounded-xl flex justify-center items-center text-white font-black text-xl shadow-inner">
                    {{ strtoupper(substr($tenant->name ?? 'S', 0, 1)) }}
                </div>
                <div>
                    <h1 class="font-bold text-gray-900 text-lg leading-tight">{{ $tenant->name ?? 'Toko Anda' }}</h1>
                    <p class="text-[10px] text-gray-500 uppercase tracking-wider font-semibold">Official Store</p>
                </div>
            </a>

            <div class="hidden md:flex flex-1 max-w-lg mx-8">
                <form action="{{ route('storefront.index', $subdomain) }}" method="GET" class="w-full relative">
                    <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari produk di toko ini..."
                           class="w-full bg-gray-100 border-none rounded-full py-2.5 pl-5 pr-12 focus:ring-2 focus:ring-blue-500 outline-none text-sm transition">
                    <button type="submit" class="absolute right-3 top-2.5 text-gray-400 hover:text-blue-600">
                        <i data-lucide="search" class="w-5 h-5"></i>
                    </button>
                </form>
            </div>

            <div class="flex items-center gap-4">
                <a href="{{ route('storefront.cart', $subdomain) }}" class="relative p-2 text-gray-600 hover:text-blue-600 transition group">
                    <i data-lucide="shopping-bag" class="w-6 h-6 group-hover:scale-110 transition transform"></i>
                    <span x-cloak x-show="cartCount > 0" x-text="cartCount" class="absolute top-0 right-0 bg-red-500 text-white text-[10px] font-bold px-1.5 py-0.5 rounded-full border-2 border-white transform translate-x-1/4 -translate-y-1/4"></span>
                </a>
            </div>

        </div>
    </div>
</header>
