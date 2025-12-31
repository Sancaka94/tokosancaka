<header class="bg-white shadow-sm z-30 relative">
    <div class="flex items-center justify-between px-6 py-4">
        
        <h1 class="text-2xl font-semibold text-gray-700">
            @yield('page_title', 'Dashboard')
        </h1>

        <div class="flex items-center gap-4">

            @auth
            <div class="hidden md:flex items-center bg-gray-50 border border-gray-200 rounded-full px-4 py-1.5 shadow-sm">
                <div class="p-1 bg-green-100 rounded-full mr-3 text-green-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                    </svg>
                </div>

                <div class="flex flex-col items-end mr-3">
                    <span class="text-[10px] text-gray-500 uppercase font-bold tracking-wider">Total Saldo</span>
                    <span class="text-sm font-bold text-gray-800 font-mono">
                        Rp {{ number_format(Auth::user()->saldo ?? 0, 0, ',', '.') }}
                    </span>
                </div>

                @if(Auth::user()->role === 'admin')
                    <a href="{{ route('admin.transaksi-kas-bank.index') }}" 
                       title="Isi Saldo User"
                       class="bg-green-600 hover:bg-green-700 text-white rounded-full p-1 transition-colors duration-200">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                        </svg>
                    </a>
                @else
                    <a href="#" onclick="alert('Fitur Request Topup User (Link route belum dibuat)')" 
                       title="Topup Saldo Saya"
                       class="bg-blue-600 hover:bg-blue-700 text-white rounded-full p-1 transition-colors duration-200">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                        </svg>
                    </a>
                @endif
            </div>
            @endauth

            <div x-data="{ open: false }" class="relative">
                
                <button @click="open = !open" class="flex items-center focus:outline-none gap-2 hover:bg-gray-50 p-2 rounded-lg transition-colors">
                    <div class="text-right hidden md:block">
                        <div class="text-sm font-bold text-gray-800">{{ Auth::user()->name }}</div>
                        <div class="text-xs text-gray-500 capitalize">{{ Auth::user()->role }}</div> </div>
                    
                    <img class="h-10 w-10 rounded-full object-cover border-2 border-gray-200"
                         src="https://ui-avatars.com/api/?name={{ urlencode(Auth::user()->name) }}&background=random" 
                         alt="{{ Auth::user()->name }}" />
                    
                    <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </button>

                <div x-show="open" 
                     @click.away="open = false"
                     x-transition:enter="transition ease-out duration-100"
                     x-transition:enter-start="transform opacity-0 scale-95"
                     x-transition:enter-end="transform opacity-100 scale-100"
                     x-transition:leave="transition ease-in duration-75"
                     x-transition:leave-start="transform opacity-100 scale-100"
                     x-transition:leave-end="transform opacity-0 scale-95"
                     class="absolute right-0 mt-2 w-56 bg-white rounded-md shadow-lg py-1 border border-gray-100"
                     style="display: none;">
                    
                    <div class="px-4 py-3 border-b border-gray-100">
                        <p class="text-sm text-gray-500">Login sebagai:</p>
                        <p class="text-sm font-bold text-gray-800 capitalize flex items-center gap-1">
                            @if(Auth::user()->role == 'admin')
                                🛡️ Administrator
                            @else
                                👤 User / Santri
                            @endif
                        </p>
                    </div>

                    <a href="{{ route('profile.edit') }}" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                        </svg>
                        Edit Profile
                    </a>

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <a href="{{ route('logout') }}"
                           onclick="event.preventDefault(); this.closest('form').submit();"
                           class="flex items-center px-4 py-2 text-sm text-red-600 hover:bg-red-50 transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                            </svg>
                            Keluar / Logout
                        </a>
                    </form>
                </div>
            </div>

        </div>
    </div>
</header>