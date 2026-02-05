<header class="h-20 bg-white/80 backdrop-blur-md border-b border-slate-200 flex items-center justify-between px-4 lg:px-8 sticky top-0 z-40" x-data="{ userOpen: false }">

    {{-- ================================================================= --}}
    {{-- [LOGIC PENYELAMAT] AMBIL SUBDOMAIN MANUAL UNTUK HEADER            --}}
    {{-- ================================================================= --}}
    @php
        // Ambil host (contoh: toko1.tokosancaka.com)
        $host = request()->getHost();
        $parts = explode('.', $host);
        // Ambil bagian depan (toko1), jika gagal pakai 'admin'
        $currentSubdomain = $parts[0] ?? 'admin';
        // Parameter untuk disuntikkan ke route
        $params = ['subdomain' => $currentSubdomain];
    @endphp

    <div class="flex items-center gap-4">
        {{-- Tombol Toggle Sidebar (Mobile) --}}
        <button @click="sidebarOpen = !sidebarOpen" class="text-slate-500 hover:text-blue-600 focus:outline-none lg:hidden p-2.5 rounded-xl hover:bg-slate-50 transition-all border border-transparent hover:border-slate-100">
            <i class="fas fa-bars-staggered text-xl"></i>
        </button>

        {{-- Search Bar --}}
        <div class="hidden md:flex relative group">
            <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-slate-400 group-focus-within:text-blue-600 transition-colors">
                <i class="fas fa-search text-sm"></i>
            </span>
            <input type="text" placeholder="Cari data..." class="bg-slate-100/50 border-none rounded-2xl py-2.5 pl-10 pr-4 text-sm w-64 focus:ring-4 focus:ring-blue-600/5 focus:bg-white transition-all outline-none text-slate-600 font-medium">
        </div>
    </div>

    <div class="flex items-center gap-3 sm:gap-5">

        {{-- Notifikasi --}}
        <button class="relative p-2.5 text-slate-400 hover:text-blue-600 hover:bg-blue-50 rounded-2xl transition-all group border border-transparent hover:border-blue-100">
            <i class="fas fa-bell text-lg group-hover:shake"></i>
            <span class="absolute top-2.5 right-2.5 h-2.5 w-2.5 rounded-full bg-red-600 ring-2 ring-white"></span>
        </button>

        <div class="h-8 w-px bg-slate-200 hidden sm:block"></div>

        {{-- User Dropdown --}}
        <div class="relative">
            <button @click="userOpen = !userOpen"
                    @click.outside="userOpen = false"
                    class="flex items-center gap-3 p-1 rounded-2xl hover:bg-slate-50 transition-all focus:outline-none border border-transparent hover:border-slate-100 group">

                <div class="text-right hidden md:block leading-tight px-1">
                    @auth
                        <p class="text-sm font-bold text-slate-800 group-hover:text-blue-600 transition-colors truncate max-w-[150px]">
                            {{ Auth::user()->name }}
                        </p>
                        <p class="text-[10px] font-bold text-emerald-600 uppercase tracking-widest mt-0.5">
                            {{ str_replace('_', ' ', Auth::user()->role ?? 'Administrator') }}
                        </p>
                    @else
                        <p class="text-sm font-bold text-slate-800">Tamu</p>
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">GUEST</p>
                    @endauth
                </div>

                <div class="h-10 w-10 rounded-2xl bg-gradient-to-tr from-blue-600 to-blue-400 p-0.5 shadow-lg shadow-blue-100 group-hover:rotate-3 transition-transform">
                    <div class="h-full w-full rounded-[14px] bg-white overflow-hidden p-0.5">
                        @auth
                            <img class="h-full w-full object-cover rounded-[12px]"
                                 src="https://ui-avatars.com/api/?name={{ urlencode(Auth::user()->name) }}&background=f1f5f9&color=2563eb&size=128&bold=true"
                                 alt="{{ Auth::user()->name }}">
                        @else
                            <img class="h-full w-full object-cover rounded-[12px]"
                                 src="https://ui-avatars.com/api/?name=Guest&background=f1f5f9&color=64748b&size=128&bold=true"
                                 alt="Guest">
                        @endauth
                    </div>
                </div>

                <i class="fas fa-chevron-down text-[10px] text-slate-300 group-hover:text-blue-600 transition-colors hidden sm:block mr-2"></i>
            </button>

            {{-- Dropdown Menu --}}
            <div x-show="userOpen"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-95 translate-y-2"
                 x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                 x-transition:leave-end="opacity-0 scale-95 translate-y-2"
                 class="absolute right-0 top-full mt-3 w-64 bg-white rounded-2xl shadow-2xl shadow-slate-200/60 border border-slate-100 py-2 z-50 overflow-hidden origin-top-right"
                 style="display: none;">

                @auth
                    <div class="px-5 py-4 bg-slate-50/50 border-b border-slate-100 mb-2">
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Informasi Login</p>
                        <p class="text-sm font-black text-slate-800 truncate">{{ Auth::user()->name }}</p>
                        <p class="text-xs text-slate-500 truncate">{{ Auth::user()->email }}</p>
                    </div>

                    <div class="px-2 space-y-1">
                        {{-- FIX: Tambahkan $params di route --}}
                        <a href="{{ route('profile.index', $params) }}" class="flex items-center gap-3 px-3 py-2.5 text-sm font-semibold text-slate-600 rounded-xl hover:bg-blue-50 hover:text-blue-600 transition-all">
                            <i class="fas fa-user-circle w-5 text-center text-slate-400 group-hover:text-blue-600"></i> Profile Saya
                        </a>
                        <a href="{{ route('profile.edit', $params) }}" class="flex items-center gap-3 px-3 py-2.5 text-sm font-semibold text-slate-600 rounded-xl hover:bg-blue-50 hover:text-blue-600 transition-all">
                            <i class="fas fa-user-gear w-5 text-center text-slate-400"></i> Pengaturan Akun
                        </a>
                    </div>

                    <div class="h-px bg-slate-100 my-2 mx-4"></div>

                    <div class="px-2">
                        {{-- FIX: Tambahkan $params di route logout --}}
                        <form method="POST" action="{{ route('logout', $params) }}">
                            @csrf
                            <button type="submit" class="w-full flex items-center gap-3 px-3 py-2.5 text-sm font-bold text-red-600 rounded-xl hover:bg-red-50 transition-all">
                                <i class="fas fa-arrow-right-from-bracket w-5 text-center"></i> Logout Sesi
                            </button>
                        </form>
                    </div>
                @else
                    <div class="p-3">
                        {{-- FIX: Tambahkan $params di route login --}}
                        <a href="{{ route('login', $params) }}" class="flex items-center justify-center gap-3 px-4 py-3 text-sm font-bold text-white bg-blue-600 rounded-xl hover:bg-blue-700 transition-all shadow-lg shadow-blue-200">
                            <i class="fas fa-sign-in-alt"></i> Login Sekarang
                        </a>
                    </div>
                @endauth
            </div>
        </div>
    </div>
</header>
