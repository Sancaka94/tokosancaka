<nav class="bg-white shadow-md">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16">
            <!-- Logo -->
            <div class="flex-shrink-0">
                <a href="{{ route('home') }}" class="flex items-center">
                    <i class="fas fa-shipping-fast text-3xl text-blue-600"></i>
                    <span class="ml-2 text-xl font-bold text-gray-800">Sancaka Express</span>
                </a>
            </div>

            <!-- Menu Utama -->
            <div class="hidden md:block">
                <div class="ml-10 flex items-baseline space-x-4">
                    <a href="{{ route('home') }}" class="text-gray-700 hover:bg-gray-200 px-3 py-2 rounded-md text-sm font-medium">Beranda</a>
                    <a href="{{ route('tracking.index') }}" class="text-gray-700 hover:bg-gray-200 px-3 py-2 rounded-md text-sm font-medium">Lacak Paket</a>
                    <a href="{{ route('etalase.index') }}" class="text-gray-700 hover:bg-gray-200 px-3 py-2 rounded-md text-sm font-medium">Marketplace</a>
                    <a href="{{ route('pesanan.customer.create') }}" class="text-gray-700 hover:bg-gray-200 px-3 py-2 rounded-md text-sm font-medium">Kirim Paket</a>
                    <a href="{{ route('kontak.search') }}" class="text-gray-700 hover:bg-gray-200 px-3 py-2 rounded-md text-sm font-medium">Kontak Kami</a>
                </div>
            </div>

            <!-- Tombol Login/Register -->
            <div class="hidden md:block">
                @guest
                    <a href="{{ route('login') }}" class="text-gray-700 hover:text-blue-600 text-sm font-medium">Login</a>
                    <a href="{{ route('register') }}" class="ml-4 inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700">
                        Register
                    </a>
                @endguest
                @auth
                    <a href="{{ route('customer.dashboard') }}" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700">
                        Dashboard
                    </a>
                @endauth
            </div>
            
            <!-- Tombol Mobile Menu -->
            <div class="-mr-2 flex md:hidden">
                <button type="button" class="bg-gray-200 inline-flex items-center justify-center p-2 rounded-md text-gray-800 hover:text-white hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-gray-800 focus:ring-white" aria-controls="mobile-menu" aria-expanded="false">
                    <span class="sr-only">Open main menu</span>
                    <i class="fas fa-bars"></i>
                </button>
            </div>
        </div>
    </div>
</nav>
