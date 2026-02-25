<header class="h-16 bg-white border-b border-gray-200 flex items-center justify-between px-4 md:px-6 shadow-sm shrink-0">
    <div class="flex items-center gap-2 md:gap-3">

        <button @click="sidebarOpen = !sidebarOpen" type="button" class="p-2 mr-1 text-gray-600 rounded-md hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-400">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
            </svg>
        </button>

        <img src="https://tokosancaka.com/storage/uploads/logo.jpeg" alt="Logo Perusahaan" class="h-8 w-8 shrink-0">
        <span class="font-bold text-blue-600 text-lg md:text-xl tracking-tight hidden sm:block whitespace-nowrap">sancakaPARKIR</span>

        @if(auth()->user()->tenant)
            <span class="hidden lg:inline-block ml-2 px-2 py-1 bg-blue-100 text-blue-800 text-xs rounded-full font-semibold max-w-[150px] truncate">
                Cabang: {{ auth()->user()->tenant->name }}
            </span>
        @endif
    </div>

    <div class="flex items-center gap-3 md:gap-4" x-data="{ dropdownOpen: false }">

        <div class="text-right hidden md:block">
            <p class="text-sm font-bold text-gray-800 leading-none">{{ auth()->user()->name }}</p>
            <p class="text-xs text-gray-500 mt-1 capitalize">{{ auth()->user()->role }}</p>
        </div>

        <div class="relative">
            <button @click="dropdownOpen = !dropdownOpen" class="flex items-center focus:outline-none">
                <div class="h-9 w-9 md:h-10 md:w-10 rounded-full bg-blue-600 text-white flex items-center justify-center font-bold border-2 border-blue-200 hover:border-blue-600 transition">
                    {{ substr(auth()->user()->name, 0, 1) }}
                </div>
            </button>

            <div x-show="dropdownOpen"
                 @click.away="dropdownOpen = false"
                 x-transition:enter="transition ease-out duration-100"
                 x-transition:enter-start="transform opacity-0 scale-95"
                 x-transition:enter-end="transform opacity-100 scale-100"
                 x-transition:leave="transition ease-in duration-75"
                 x-transition:leave-start="transform opacity-100 scale-100"
                 x-transition:leave-end="transform opacity-0 scale-95"
                 class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg overflow-hidden z-20 border border-gray-100"
                 style="display: none;">

                <div class="px-4 py-3 md:py-2 bg-gray-50 border-b border-gray-100">
                    <div class="block md:hidden mb-2 pb-2 border-b border-gray-200">
                        <p class="text-sm font-bold text-gray-800 leading-none">{{ auth()->user()->name }}</p>
                        <p class="text-xs text-gray-500 mt-1 capitalize">{{ auth()->user()->role }}</p>
                    </div>
                    <p class="text-xs md:text-sm text-gray-800 font-semibold tracking-wider">Pengaturan Akun</p>
                </div>

                <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-600">Rubah Profil & Logo</a>
                <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-600">Rubah Password</a>
                <hr class="border-gray-100">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="block w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50 font-semibold">
                        Logout
                    </button>
                </form>
            </div>
        </div>
    </div>
</header>
