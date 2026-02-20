<header class="h-16 bg-white border-b border-gray-200 flex items-center justify-between px-6 shadow-sm">
    <div class="flex items-center gap-3">
        <img src="https://tokosancaka.com/storage/uploads/logo.jpeg" alt="Logo Perusahaan" class="h-8 w-8">
        <span class="font-bold text-blue-600 text-xl tracking-tight">sancakaPARKIR</span>

        @if(auth()->user()->tenant)
            <span class="hidden md:inline-block ml-2 px-2 py-1 bg-blue-100 text-blue-800 text-xs rounded-full font-semibold">
                Cabang: {{ auth()->user()->tenant->name }}
            </span>
        @endif
    </div>

    <div class="flex items-center gap-4" x-data="{ dropdownOpen: false }">

        <div class="text-right hidden md:block">
            <p class="text-sm font-bold text-gray-800 leading-none">{{ auth()->user()->name }}</p>
            <p class="text-xs text-gray-500 mt-1 capitalize">{{ auth()->user()->role }}</p>
        </div>

        <div class="relative">
            <button @click="dropdownOpen = !dropdownOpen" class="flex items-center focus:outline-none">
                <div class="h-10 w-10 rounded-full bg-blue-600 text-white flex items-center justify-center font-bold border-2 border-blue-200 hover:border-blue-600 transition">
                    {{ substr(auth()->user()->name, 0, 1) }}
                </div>
            </button>

            <div x-show="dropdownOpen" @click.away="dropdownOpen = false" class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg overflow-hidden z-20 border border-gray-100" style="display: none;">
                <div class="px-4 py-2 bg-gray-50 border-b border-gray-100">
                    <p class="text-sm text-gray-800 font-semibold">Pengaturan Akun</p>
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
