<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Sancaka Marketplace')</title>

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="https://sancaka.bisnis.pro/wp-content/uploads/sites/5/2024/10/WhatsApp_Image_2024-10-08_at_10.14.16-removebg-preview.png">

    <!-- Frameworks & Libraries CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />
    <link href="https://unpkg.com/aos@2.3.4/dist/aos.css" rel="stylesheet">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- AlpineJS for mobile menu interactivity -->
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <!-- Custom Styles -->
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f0f2f5;
        }
    </style>
    @stack('styles')
</head>
<body class="bg-gray-100">

    <!-- Header Section -->
    <header class="bg-white shadow-md sticky top-0 z-50" x-data="{ mobileMenuOpen: false }">
        <div class="container mx-auto px-4">
            <div class="flex justify-between items-center h-20">
                <!-- Logo -->
              <a href="{{ route('home') }}">
                    <img src="{{ asset('storage/' . $weblogo) }}" alt="SANCAKA STORE" class="h-12">
                </a>

                <!-- Search Bar (Desktop) -->
                <div class="hidden lg:flex flex-grow max-w-xl mx-4 relative">
                    <form action="{{ route('etalase.index') }}" method="GET" class="w-full">
                        <input type="search" name="search" placeholder="Cari diskon, cashback, & produk impianmu..." class="w-full py-2.5 px-5 border border-gray-300 rounded-full focus:outline-none focus:ring-2 focus:ring-red-400">
                        <button type="submit" class="absolute right-0 top-0 mt-1 mr-1 bg-red-500 text-white p-2 rounded-full hover:bg-red-600 transition-colors">
                            <i class="fas fa-search w-4 h-4"></i>
                        </button>
                    </form>
                </div>

                <!-- User Actions (Desktop) -->
                <div class="hidden lg:flex items-center space-x-4">
                    <a href="{{ route('cart.index') }}" class="relative text-gray-600 hover:text-red-500 p-2">
                        <i class="fas fa-shopping-cart text-2xl"></i>
                        @if(session('cart') && count(session('cart')) > 0)
                            <span class="absolute top-0 right-0 bg-red-500 text-white text-xs w-5 h-5 flex items-center justify-center rounded-full">{{ count((array) session('cart')) }}</span>
                        @endif
                    </a>
                    
                    {{-- ✅ PERBAIKAN: Logika dinamis untuk tombol otentikasi --}}
                    @auth
                        {{-- Jika pengguna sudah login --}}
                        @if (Auth::user()->role === 'Admin')
                            {{-- Jika yang login adalah Admin --}}
                            <a href="{{ route('admin.dashboard') }}" class="px-5 py-2 text-sm font-bold text-gray-700 border-2 border-gray-300 rounded-full hover:bg-gray-100 transition-colors">Dashboard</a>
                            <form action="{{ route('admin.logout') }}" method="POST">
                                @csrf
                                <button type="submit" class="px-5 py-2 text-sm font-bold text-white bg-gray-700 rounded-full hover:bg-gray-800 transition-colors">LOGOUT</button>
                            </form>
                        @else
                            {{-- Jika yang login adalah Pelanggan --}}
                            <a href="{{ route('customer.dashboard') }}" class="px-5 py-2 text-sm font-bold text-gray-700 border-2 border-gray-300 rounded-full hover:bg-gray-100 transition-colors">Dashboard</a>
                            <form action="{{ route('logout') }}" method="POST">
                                @csrf
                                <button type="submit" class="px-5 py-2 text-sm font-bold text-white bg-gray-700 rounded-full hover:bg-gray-800 transition-colors">LOGOUT</button>
                            </form>
                        @endif
                    @else
                        {{-- Jika pengguna adalah tamu (belum login) --}}
                        <a href="{{ route('login') }}" class="px-5 py-2 text-sm font-bold text-red-500 border-2 border-red-500 rounded-full hover:bg-red-50 transition-colors">LOGIN</a>
                        <a href="{{ route('register') }}" class="px-5 py-2 text-sm font-bold text-white bg-red-500 rounded-full hover:bg-red-600 transition-colors">DAFTAR</a>
                    @endauth
                </div>

                <!-- Mobile Menu Button -->
                <button @click="mobileMenuOpen = !mobileMenuOpen" class="lg:hidden text-gray-600 hover:text-red-500">
                    <i class="fas fa-bars text-2xl"></i>
                </button>
            </div>
        </div>

        <!-- Mobile Menu -->
        <div x-show="mobileMenuOpen" @click.away="mobileMenuOpen = false" class="lg:hidden bg-white border-t border-gray-200" x-transition>
            <div class="p-4 space-y-4">
                <!-- Mobile Search -->
                <form action="{{ route('etalase.index') }}" method="GET" class="relative">
                    <input type="search" name="search" placeholder="Cari produk..." class="w-full py-2.5 px-5 border border-gray-300 rounded-full focus:outline-none focus:ring-2 focus:ring-red-400">
                    <button type="submit" class="absolute right-0 top-0 mt-1 mr-1 bg-red-500 text-white p-2 rounded-full hover:bg-red-600">
                        <i class="fas fa-search w-4 h-4"></i>
                    </button>
                </form>
                <!-- Mobile Links -->
                @auth
                    @if (Auth::user()->role === 'Admin')
                        <a href="{{ route('admin.dashboard') }}" class="block w-full text-center px-5 py-2 text-sm font-bold text-gray-700 border-2 border-gray-300 rounded-full hover:bg-gray-100 transition-colors">Dashboard</a>
                        <form action="{{ route('admin.logout') }}" method="POST" class="w-full">
                            @csrf
                            <button type="submit" class="w-full text-center px-5 py-2 text-sm font-bold text-white bg-gray-700 rounded-full hover:bg-gray-800 transition-colors">LOGOUT</button>
                        </form>
                    @else
                        <a href="{{ route('customer.dashboard') }}" class="block w-full text-center px-5 py-2 text-sm font-bold text-gray-700 border-2 border-gray-300 rounded-full hover:bg-gray-100 transition-colors">Dashboard</a>
                        <form action="{{ route('logout') }}" method="POST" class="w-full">
                            @csrf
                            <button type="submit" class="w-full text-center px-5 py-2 text-sm font-bold text-white bg-gray-700 rounded-full hover:bg-gray-800 transition-colors">LOGOUT</button>
                        </form>
                    @endif
                @else
                    <a href="{{ route('login') }}" class="block w-full text-center px-5 py-2 text-sm font-bold text-red-500 border-2 border-red-500 rounded-full hover:bg-red-50 transition-colors">LOGIN</a>
                    <a href="{{ route('register') }}" class="block w-full text-center px-5 py-2 text-sm font-bold text-white bg-red-500 rounded-full hover:bg-red-600 transition-colors">DAFTAR</a>
                @endauth
            </div>
        </div>
    </header>

    <main class="container mx-auto p-4">
        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="bg-gray-800 text-white mt-12">
        <div class="container mx-auto px-4 py-10">
            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-8">
                <div class="lg:col-span-2 pr-8">
                    <h3 class="text-xl font-bold text-red-500 mb-2">SANCAKA STORE</h3>
                    <p class="text-gray-400 text-sm">Pusat Belanja Online No. 1 di Indonesia. Belanja lebih hemat, aman, dan cepat. Dijamin!</p>
                </div>
                <div>
                    <h4 class="font-bold mb-3">Jelajahi</h4>
                    <ul class="space-y-2 text-sm text-gray-400">
                        <li><a href="#" class="hover:text-white">Tentang Kami</a></li>
                        <li><a href="#" class="hover:text-white">Karir</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="font-bold mb-3">Bantuan</h4>
                    <ul class="space-y-2 text-sm text-gray-400">
                        <li><a href="#" class="hover:text-white">Pusat Bantuan</a></li>
                        <li><a href="#" class="hover:text-white">Hubungi Kami</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="font-bold mb-3">Ikuti Kami</h4>
                    <div class="flex space-x-4">
                        <a href="#" class="text-gray-400 hover:text-white"><i class="fab fa-facebook fa-lg"></i></a>
                        <a href="#" class="text-gray-400 hover:text-white"><i class="fab fa-twitter fa-lg"></i></a>
                        <a href="#" class="text-gray-400 hover:text-white"><i class="fab fa-instagram fa-lg"></i></a>
                    </div>
                </div>
            </div>
            <div class="border-t border-gray-700 mt-8 pt-6 text-center text-sm text-gray-500">
                &copy; <span id="year"></span> Sancaka Marketplace. Semua Hak Dilindungi.
            </div>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
    <script src="https://unpkg.com/aos@2.3.4/dist/aos.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
@if(session('success'))
<script>
    Swal.fire({
        title: 'Berhasil!',
        text: "{{ session('success') }}",
        imageUrl: "{{ asset('public/assets/logo.jpg') }}", 
        imageWidth: 80,
        imageHeight: 80,
        confirmButtonColor: '#16a34a',
    });
</script>
@endif

@if(session('error'))
<script>
    Swal.fire({
        title: 'Gagal!',
        text: "{{ session('error') }}",
        imageUrl: "{{ asset('public/assets/logo.jpg') }}", 
        imageWidth: 80,
        imageHeight: 80,
        confirmButtonColor: '#dc2626',
    });
</script>
@endif
    <script>
        // Inisialisasi AOS
        AOS.init({
            duration: 800,
            once: true,
        });

        // Set tahun di footer
        document.getElementById('year').textContent = new Date().getFullYear();
    </script>
    @stack('scripts')
</body>
</html>
