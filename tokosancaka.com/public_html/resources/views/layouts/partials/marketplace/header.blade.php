<header class="bg-white shadow-sm sticky top-0 z-50" x-data="{ mobileMenuOpen: false }">
    <div class="container mx-auto px-4 max-w-7xl">
        <div class="flex justify-between items-center h-16 md:h-20">

            {{-- LOGO (Diperbaiki: Tambah max-w agar tidak mendorong layout di HP) --}}
            <a href="{{ url('/etalase') }}" class="flex-shrink-0 mr-2">
                <img src="{{ isset($weblogo) ? asset('public/storage/' . $weblogo) : asset('images/logo-default.png') }}"
                     alt="SANCAKA STORE"
                     class="h-9 md:h-12 w-auto max-w-[130px] md:max-w-[200px] object-contain">
            </a>

            {{-- SEARCH DESKTOP --}}
            <div class="hidden md:flex flex-grow max-w-2xl mx-6">
                <form action="{{ route('etalase.index') }}" method="GET" class="w-full flex border border-gray-300 rounded-lg overflow-hidden focus-within:ring-2 focus-within:ring-red-400 transition-all">
                    <input type="search" name="search" placeholder="Cari produk..." class="w-full py-2.5 px-5 text-sm border-none focus:outline-none focus:ring-0">
                    <button type="submit" class="px-6 py-2 bg-red-500 text-white hover:bg-red-600 transition-colors flex-shrink-0">
                        <i class="fas fa-search"></i>
                    </button>
                </form>
            </div>

            {{-- MENU DESKTOP --}}
            <div class="hidden md:flex items-center space-x-4 flex-shrink-0">
                <a href="{{ route('cart.index') }}" class="relative text-gray-600 hover:text-red-500 p-2">
                    <i class="fas fa-shopping-cart text-2xl"></i>
                    @if(session('cart') && count(session('cart')) > 0)
                        <span class="absolute top-0 right-0 bg-red-500 text-white text-xs w-5 h-5 flex items-center justify-center rounded-full">
                            {{ count((array) session('cart')) }}
                        </span>
                    @endif
                </a>

                @auth
                    <a href="{{ Auth::user()->role === 'Admin' ? route('admin.dashboard') : route('customer.dashboard') }}" class="px-5 py-2 text-sm font-semibold text-gray-700 border border-gray-300 rounded-full hover:bg-gray-100 transition-colors">Dashboard</a>
                @else
                    <a href="{{ route('login') }}" class="px-5 py-2 text-sm font-semibold text-red-500 border border-red-500 rounded-full hover:bg-red-50 transition-colors">LOGIN</a>
                    <a href="{{ route('register') }}" class="px-5 py-2 text-sm font-semibold text-white bg-red-500 rounded-full hover:bg-red-600 transition-colors">DAFTAR</a>
                @endauth
            </div>

            {{-- MENU MOBILE (Diperbaiki: Tambah flex-shrink-0 agar tidak tertekan Logo) --}}
            <div class="flex items-center space-x-3 md:hidden flex-shrink-0">

                {{-- ICON KERANJANG MOBILE --}}
                <a href="{{ route('cart.index') }}" class="relative text-gray-600 hover:text-red-500 p-1.5 flex items-center justify-center">
                    <i class="fas fa-shopping-cart text-[22px]"></i>
                    @if(session('cart') && count(session('cart')) > 0)
                        <span class="absolute -top-1 -right-1 bg-red-500 text-white text-[9px] font-bold w-4 h-4 flex items-center justify-center rounded-full border border-white">
                            {{ count((array) session('cart')) }}
                        </span>
                    @endif
                </a>

                {{-- TOMBOL MENU BURGER --}}
                <button @click="mobileMenuOpen = !mobileMenuOpen" class="text-gray-600 hover:text-red-500 p-1.5 focus:outline-none flex items-center justify-center">
                    <i class="fas fa-bars text-[24px]"></i>
                </button>

            </div>

        </div>
    </div>

    {{-- DROPDOWN MOBILE MENU --}}
    <div x-show="mobileMenuOpen" @click.away="mobileMenuOpen = false" class="md:hidden bg-white border-t border-gray-200" style="display: none;" x-transition>
        <div class="p-4 space-y-4">

            {{-- Search Mobile --}}
            <form action="{{ route('etalase.index') }}" method="GET" class="w-full flex border border-gray-300 rounded-lg overflow-hidden">
                <input type="search" name="search" placeholder="Cari produk..." class="w-full py-2 px-4 text-sm border-none focus:outline-none">
                <button type="submit" class="px-4 py-2 bg-red-500 text-white flex-shrink-0"><i class="fas fa-search"></i></button>
            </form>

            {{-- Tombol Keranjang Ekstra di Mobile --}}
            <a href="{{ route('cart.index') }}" class="flex justify-between items-center w-full px-5 py-2.5 font-semibold text-gray-700 border border-gray-300 rounded-full hover:bg-gray-50">
                <span><i class="fas fa-shopping-cart mr-2 text-gray-500"></i> Keranjang Belanja</span>
                @if(session('cart') && count(session('cart')) > 0)
                    <span class="bg-red-500 text-white text-xs px-2 py-1 rounded-full">{{ count((array) session('cart')) }}</span>
                @endif
            </a>

            {{-- Menu Links Mobile --}}
            @auth
                <a href="{{ Auth::user()->role === 'Admin' ? route('admin.dashboard') : route('customer.dashboard') }}" class="block w-full text-center px-5 py-2.5 font-semibold text-gray-700 border border-gray-300 rounded-full">Dashboard</a>
                <form action="{{ Auth::user()->role === 'Admin' ? route('admin.logout') : route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="w-full text-center px-5 py-2.5 font-semibold text-white bg-gray-900 rounded-full mt-2">LOGOUT</button>
                </form>
            @else
                <a href="{{ route('login') }}" class="block w-full text-center px-5 py-2.5 font-semibold text-red-500 border border-red-500 rounded-full">LOGIN</a>
                <a href="{{ route('register') }}" class="block w-full text-center px-5 py-2.5 font-semibold text-white bg-red-500 rounded-full mt-2">DAFTAR</a>
            @endauth
        </div>
    </div>
</header>
