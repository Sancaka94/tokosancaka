<header class="z-30 py-4 bg-white shadow-md">
    <div class="container flex items-center justify-between h-full px-6 mx-auto text-indigo-600">
        
        {{-- Tombol Toggle Sidebar (hanya muncul di mobile) --}}
        <button class="p-1 -ml-1 mr-5 rounded-md md:hidden focus:outline-none focus:shadow-outline-indigo" @click="sidebarOpen = !sidebarOpen" aria-label="Menu">
            <i class="fas fa-bars w-6 h-6 text-gray-600"></i>
        </button>

        {{-- Bagian kiri bisa untuk search bar atau kosong --}}
        <div class="flex-1"></div>

        {{-- Bagian Kanan Topbar --}}
        <ul class="flex items-center flex-shrink-0 space-x-2 sm:space-x-4">
            
            {{-- ========================================== --}}
            {{-- 1. PERBAIKAN SALDO MOBILE                  --}}
            {{-- ========================================== --}}
            <li class="flex md:hidden items-center space-x-2">
                <span class="fas fa-wallet font-semibold text-xs sm:text-sm text-gray-700">
                    <a class="text-green-600">Saldo Anda: </a> 
                    {{-- GANTI $saldo JADI Auth::user()->saldo --}}
                    <strong id="saldo-mobile">Rp {{ number_format(Auth::user()->saldo ?? 0, 0, ',', '.') }}</strong>
                </span>
                <a href="{{ route('customer.topup.create') }}" class="inline-flex items-center justify-center w-8 h-8 text-sm font-medium text-white bg-indigo-600 border border-transparent rounded-full hover:bg-indigo-700 transition-colors duration-150 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <i class="fas fa-plus"></i>
                </a>
            </li>

            {{-- ========================================== --}}
            {{-- 2. PERBAIKAN SALDO DESKTOP                 --}}
            {{-- ========================================== --}}
            <li class="hidden md:flex items-center space-x-2 bg-gray-50 px-3 py-1.5 rounded-lg">
                <i class="fas fa-wallet text-gray-500"></i>
                <span class="font-semibold text-sm text-gray-700">
                    <a class="text-green-600">Saldo Anda: </a> 
                    {{-- GANTI $saldo JADI Auth::user()->saldo --}}
                    <strong id="saldo-desktop">Rp {{ number_format(Auth::user()->saldo ?? 0, 0, ',', '.') }}</strong>
                </span>
                <a href="{{ route('customer.topup.create') }}" class="inline-flex items-center px-3 py-1 text-xs font-medium text-white bg-red-600 border border-transparent rounded-md hover:bg-red-700 transition-colors duration-150">
                    <i class="fas fa-plus mr-1"></i>
                    Top Up
                </a>
            </li>

            {{-- ... SISA KODE KE BAWAH TETAP SAMA ... --}}

            {{-- Tombol Ikon Toko --}}
            @if(Auth::user() && Auth::user()->role == 'Seller')
            <li class="relative">
                <a href="{{ url('https://tokosancaka.com/seller/dashboard') }}" 
                   class="relative align-middle rounded-md focus:outline-none p-2 text-gray-600 hover:text-indigo-600" 
                   aria-label="Dashboard Toko"
                   title="Dashboard Toko">
                    <i class="fas fa-store w-5 h-5"></i>
                </a>
            </li>
            @endif

            <li class="relative">
                <button class="relative align-middle rounded-md focus:outline-none" @click="isNotificationsMenuOpen = !isNotificationsMenuOpen" aria-label="Notifikasi" aria-haspopup="true"
                        title="Notifikasi">
                    <i class="fas fa-bell w-5 h-5 text-gray-600"></i>
                    <span id="notification-count-badge" class="absolute -top-2 -right-2 bg-red-600 text-white text-xs w-5 h-5 flex items-center justify-center rounded-full" style="display: none;">0</span>
                </button>

                <div x-show="isNotificationsMenuOpen" @click.away="isNotificationsMenuOpen = false" x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 transform scale-95" x-transition:enter-end="opacity-100 transform scale-100" x-transition:leave="transition ease-in duration-75" x-transition:leave-start="opacity-100 transform scale-100" x-transition:leave-end="opacity-0 transform scale-95" class="absolute right-0 w-80 mt-2 origin-top-right bg-white rounded-md shadow-lg">
                    <div class="p-2">
                        <div class="flex justify-between items-center p-2 border-b">
                            <h4 class="font-semibold text-gray-700">Notifikasi</h4>
                        </div>
                        <div id="notification-list" class="max-h-64 overflow-y-auto">
                            <p id="notification-empty-state" class="p-4 text-sm text-center text-gray-500" style="display: none;">Tidak ada notifikasi baru.</p>
                        </div>
                    </div>
                </div>
            </li>

            {{-- Tombol Keranjang Belanja --}}
            <a href="{{ route('customer.cart.index') }}" class="text-gray-600 hover:text-red-600 relative"
               title="Keranjang Belanja">
                <i class="fas fa-shopping-cart"></i>
                @php $cartCount = count((array) session('cart')) @endphp
                @if($cartCount > 0)
                    <span class="absolute -top-2 -right-2 bg-red-600 text-white text-xs w-5 h-5 flex items-center justify-center rounded-full">{{ $cartCount }}</span>
                @endif
            </a>

            <li class="relative">
                <button class="align-middle rounded-full focus:outline-none" @click="isProfileMenuOpen = !isProfileMenuOpen" aria-label="Akun" aria-haspopup="true"
                        title="Akun Anda">
                    <img class="object-cover w-8 h-8 rounded-full" src="{{ Auth::user()->store_logo_path ? asset('public/storage/'. Auth::user()->store_logo_path) : 'https://ui-avatars.com/api/?name=' . urlencode(Auth::user()->nama_lengkap) . '&background=random&color=fff' }}" alt="Avatar Pengguna" />
                </button>
                <div x-show="isProfileMenuOpen" @click.away="isProfileMenuOpen = false" x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 transform scale-95" x-transition:enter-end="opacity-100 transform scale-100" x-transition:leave="transition ease-in duration-75" x-transition:leave-start="opacity-100 transform scale-100" x-transition:leave-end="opacity-0 transform scale-95" class="absolute right-0 w-56 mt-2 origin-top-right bg-white rounded-md shadow-lg">
                    <div class="p-2">
                        <div class="px-4 py-3 border-b">
                            <p class="font-semibold text-gray-800">{{ Auth::user()->nama_lengkap }}</p>
                            <p class="text-xs text-gray-500">{{ Auth::user()->role }}</p>
                        </div>
                        <a class="flex items-center w-full text-left px-4 py-2 text-sm text-gray-700 rounded-md hover:bg-indigo-600 hover:text-white" href="{{ route('customer.profile.show') }}">
                            <i class="fas fa-user-circle w-4 mr-2"></i> Profil
                        </a>
                        <a class="flex items-center w-full text-left px-4 py-2 text-sm text-gray-700 rounded-md hover:bg-indigo-600 hover:text-white" href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                            <i class="fas fa-sign-out-alt w-4 mr-2"></i> Keluar
                        </a>
                        <form id="logout-form" action="{{ route('logout') }}" method="POST" class="hidden">@csrf</form>
                    </div>
                </div>
            </li>
        </ul>
    </div>
</header>