<header class="z-30 py-4 bg-white shadow-md dark:bg-gray-800">
    <div class="container flex items-center justify-between h-full px-6 mx-auto text-indigo-600 dark:text-indigo-300">
        
        {{-- Tombol Toggle Sidebar (hanya muncul di mobile) --}}
        <button class="p-1 -ml-1 mr-5 rounded-md md:hidden focus:outline-none focus:shadow-outline-indigo" @click="sidebarOpen = !sidebarOpen" aria-label="Menu">
            <i class="fas fa-bars w-6 h-6 text-gray-600 dark:text-gray-400"></i>
        </button>

        {{-- Bagian kiri bisa untuk search bar atau kosong --}}
        <div class="flex-1"></div>

        {{-- Bagian Kanan Topbar --}}
        <ul class="flex items-center flex-shrink-0 space-x-2 sm:space-x-4">
            
            {{-- Saldo & Tombol Top Up (Mobile) --}}
            <li class="flex md:hidden items-center space-x-2">
                <span class="fas fa-wallet font-semibold text-xs sm:text-sm text-gray-700 dark:text-gray-200">
                    <a class="text-green-600 dark:text-green-400">Saldo Anda: </a> <strong>Rp {{ number_format($saldo ?? 0, 0, ',', '.') }}</strong>
                </span>
                <a href="{{ route('customer.topup.create') }}" class="inline-flex items-center justify-center w-8 h-8 text-sm font-medium text-white bg-indigo-600 border border-transparent rounded-full hover:bg-indigo-700 transition-colors duration-150 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <i class="fas fa-plus"></i>
                </a>
            </li>

            <!-- Saldo & Tombol Top Up (Desktop) -->
            <li class="hidden md:flex items-center space-x-2 bg-gray-50 dark:bg-gray-700/50 px-3 py-1.5 rounded-lg">
                <i class="fas fa-wallet text-gray-500 dark:text-gray-400"></i>
                <span class="font-semibold text-sm text-gray-700 dark:text-gray-200">
                    <a class="text-green-600 dark:text-green-400">Saldo Anda: </a> <strong>Rp {{ number_format($saldo ?? 0, 0, ',', '.') }}</strong>
                </span>
                <a href="{{ route('customer.topup.create') }}" class="inline-flex items-center px-3 py-1 text-xs font-medium text-white bg-red-600 border border-transparent rounded-md hover:bg-red-700 transition-colors duration-150">
                    <i class="fas fa-plus mr-1"></i>
                    Top Up
                </a>
            </li>

            <!-- Tombol Notifikasi -->
            <li class="relative">
                <button class="relative align-middle rounded-md focus:outline-none" @click="isNotificationsMenuOpen = !isNotificationsMenuOpen" aria-label="Notifikasi" aria-haspopup="true">
                    <i class="fas fa-bell w-5 h-5 text-gray-600 dark:text-gray-400"></i>
                    @if(isset($notifications) && $notifications->isNotEmpty())
                        <span class="absolute top-0 right-0 inline-block w-3 h-3 transform translate-x-1 -translate-y-1 bg-red-600 border-2 border-white rounded-full dark:border-gray-800"></span>
                    @endif
                </button>

                <div x-show="isNotificationsMenuOpen" @click.away="isNotificationsMenuOpen = false" x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 transform scale-95" x-transition:enter-end="opacity-100 transform scale-100" x-transition:leave="transition ease-in duration-75" x-transition:leave-start="opacity-100 transform scale-100" x-transition:leave-end="opacity-0 transform scale-95" class="absolute right-0 w-80 mt-2 origin-top-right bg-white rounded-md shadow-lg dark:bg-gray-800">
                    <div class="p-2">
                        <div class="flex justify-between items-center p-2 border-b dark:border-gray-700">
                             <h4 class="font-semibold text-gray-700 dark:text-gray-300">Notifikasi</h4>
                             @if(isset($notifications) && $notifications->isNotEmpty())
                                <span class="px-2 py-1 text-xs font-bold text-red-100 bg-red-600 rounded-full">{{ $notifications->count() }}</span>
                             @endif
                        </div>
                        <div class="max-h-64 overflow-y-auto">
                        @forelse($notifications ?? [] as $notification)
                            <a class="flex items-start w-full px-4 py-3 text-sm text-gray-700 rounded-md hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-700" href="#">
                               <div class="mr-3 pt-1">
                                   <div class="p-2 bg-blue-100 dark:bg-blue-900/50 rounded-full">
                                      <i class="fas fa-info-circle text-blue-500"></i>
                                   </div>
                               </div>
                               <div>
                                   <p class="font-semibold">{{ $notification->data['title'] ?? 'Notifikasi Baru' }}</p>
                                   <p class="text-xs text-gray-500">{{ $notification->data['message'] ?? 'Anda memiliki pembaruan baru.' }}</p>
                                   <p class="text-xs text-gray-400 mt-1">{{ $notification->created_at->diffForHumans() }}</p>
                               </div>
                            </a>
                        @empty
                            <p class="p-4 text-sm text-center text-gray-500">Tidak ada notifikasi baru.</p>
                        @endforelse
                        </div>
                    </div>
                </div>
            </li>

            <a href="{{ route('customer.cart.index') }}" class="text-gray-600 hover:text-red-600 relative">
                <i class="fas fa-shopping-cart"></i>
                @php $cartCount = count((array) session('cart')) @endphp
                @if($cartCount > 0)
                    <span class="absolute -top-2 -right-2 bg-red-600 text-white text-xs w-5 h-5 flex items-center justify-center rounded-full">{{ $cartCount }}</span>
                @endif
            </a>

            <!-- Menu Profil Pengguna -->
            <li class="relative">
                <button class="align-middle rounded-full focus:outline-none" @click="isProfileMenuOpen = !isProfileMenuOpen" aria-label="Akun" aria-haspopup="true">
                    <img class="object-cover w-8 h-8 rounded-full" src="{{ Auth::user()->store_logo_path ? asset('storage/' . Auth::user()->store_logo_path) : 'https://ui-avatars.com/api/?name=' . urlencode(Auth::user()->nama_lengkap) . '&background=random&color=fff' }}" alt="Avatar Pengguna" />
                </button>
                <div x-show="isProfileMenuOpen" @click.away="isProfileMenuOpen = false" x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 transform scale-95" x-transition:enter-end="opacity-100 transform scale-100" x-transition:leave="transition ease-in duration-75" x-transition:leave-start="opacity-100 transform scale-100" x-transition:leave-end="opacity-0 transform scale-95" class="absolute right-0 w-56 mt-2 origin-top-right bg-white rounded-md shadow-lg dark:bg-gray-800">
                    <div class="p-2">
                        <div class="px-4 py-3 border-b dark:border-gray-700">
                            <p class="font-semibold text-gray-800 dark:text-gray-300">{{ Auth::user()->nama_lengkap }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ Auth::user()->role }}</p>
                        </div>
                        <a class="flex items-center w-full text-left px-4 py-2 text-sm text-gray-700 rounded-md hover:bg-indigo-600 hover:text-white dark:hover:bg-gray-700 dark:text-gray-400 dark:hover:text-white" href="{{ route('customer.profile.show') }}">
                            <i class="fas fa-user-circle w-4 mr-2"></i> Profil
                        </a>
                        <a class="flex items-center w-full text-left px-4 py-2 text-sm text-gray-700 rounded-md hover:bg-indigo-600 hover:text-white dark:hover:bg-gray-700 dark:text-gray-400 dark:hover:text-white" href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                            <i class="fas fa-sign-out-alt w-4 mr-2"></i> Keluar
                        </a>
                        <form id="logout-form" action="{{ route('logout') }}" method="POST" class="hidden">@csrf</form>
                    </div>
                </div>
            </li>
        </ul>
    </div>
</header>

