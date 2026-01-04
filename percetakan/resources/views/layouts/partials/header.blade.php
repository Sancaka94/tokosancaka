<header class="h-16 bg-white border-b border-slate-200 flex items-center justify-between px-4 lg:px-6 sticky top-0 z-40 shadow-sm" x-data="{ userOpen: false }">
    
    <div class="flex items-center gap-4">
        {{-- Pastikan variable sidebarOpen didefinisikan di parent (misal di body) --}}
        <button @click="sidebarOpen = !sidebarOpen" class="text-slate-500 hover:text-indigo-600 focus:outline-none lg:hidden p-2 rounded-md hover:bg-slate-50 transition-colors">
            <i class="fas fa-bars text-xl"></i>
        </button>
    </div>

    <div class="flex items-center gap-2 sm:gap-4">
        
        <button class="relative p-2 text-slate-400 hover:text-indigo-600 hover:bg-slate-50 rounded-full transition-all group">
            <i class="fas fa-bell text-xl group-hover:animate-swing"></i>
            <span class="absolute top-2 right-2.5 h-2 w-2 rounded-full bg-red-500 ring-2 ring-white"></span>
        </button>

        <div class="h-8 w-px bg-slate-200 hidden sm:block"></div>

        <div class="relative">
            
            <button @click="userOpen = !userOpen" 
                    @click.outside="userOpen = false"
                    class="flex items-center gap-3 p-1.5 rounded-lg hover:bg-slate-50 transition-all focus:outline-none border border-transparent hover:border-slate-200 group">
                
                <div class="text-right hidden md:block leading-tight">
                    @auth
                        <p class="text-sm font-bold text-slate-700 group-hover:text-indigo-700 truncate max-w-[150px]">
                            {{ Auth::user()->name }}
                        </p>
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">
                            {{ Auth::user()->role ?? 'Member' }}
                        </p>
                    @else
                        <p class="text-sm font-bold text-slate-700 group-hover:text-indigo-700">
                            Tamu
                        </p>
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">
                            Guest
                        </p>
                    @endauth
                </div>

                <div class="h-9 w-9 rounded-full bg-indigo-100 border-2 border-white shadow-sm overflow-hidden group-hover:ring-2 group-hover:ring-indigo-500/20 transition-all">
                    @auth
                        <img class="h-full w-full object-cover" 
                             src="https://ui-avatars.com/api/?name={{ urlencode(Auth::user()->name) }}&background=6366f1&color=fff&size=128&bold=true" 
                             alt="{{ Auth::user()->name }}">
                    @else
                        <img class="h-full w-full object-cover" 
                             src="https://ui-avatars.com/api/?name=Guest&background=94a3b8&color=fff&size=128&bold=true" 
                             alt="Guest">
                    @endauth
                </div>

                <i class="fas fa-chevron-down text-xs text-slate-300 group-hover:text-indigo-500 transition-colors hidden sm:block"></i>
            </button>

            <div x-show="userOpen" 
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-95 -translate-y-2"
                 x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                 x-transition:leave-end="opacity-0 scale-95 -translate-y-2"
                 class="absolute right-0 top-full mt-1 w-64 bg-white rounded-xl shadow-xl border border-slate-100 py-0 z-50 overflow-hidden origin-top-right"
                 style="display: none;">
                
                @auth
                    {{-- TAMPILAN JIKA SUDAH LOGIN --}}
                    <div class="px-5 py-4 bg-slate-50 border-b border-slate-100">
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Akun Pengguna</p>
                        <p class="text-sm font-black text-slate-800 truncate">{{ Auth::user()->name }} 👋</p>
                        <p class="text-xs text-slate-500 truncate mt-0.5">{{ Auth::user()->email }}</p>
                    </div>

                    <div class="p-2 space-y-1">
                        <a href="{{ route('profile.index') }}" class="flex items-center gap-3 px-3 py-2 text-sm font-medium text-slate-600 rounded-lg hover:bg-indigo-50 hover:text-indigo-600 transition-colors">
                            <i class="fas fa-user w-5 text-center text-slate-400"></i> Profile Saya
                        </a>
                        <a href="{{ route('profile.edit') }}" class="flex items-center gap-3 px-3 py-2 text-sm font-medium text-slate-600 rounded-lg hover:bg-indigo-50 hover:text-indigo-600 transition-colors">
                            <i class="fas fa-cog w-5 text-center text-slate-400"></i> Pengaturan
                        </a>
                    </div>

                    <div class="h-px bg-slate-100 my-1 mx-2"></div>

                    <div class="p-2">
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="w-full flex items-center gap-3 px-3 py-2 text-sm font-bold text-red-500 rounded-lg hover:bg-red-50 hover:text-red-600 transition-colors">
                                <i class="fas fa-sign-out-alt w-5 text-center"></i> Keluar
                            </button>
                        </form>
                    </div>
                @else
                    {{-- TAMPILAN JIKA BELUM LOGIN (GUEST) --}}
                    <div class="px-5 py-4 bg-slate-50 border-b border-slate-100 text-center">
                        <p class="text-sm text-slate-500">Anda belum login.</p>
                    </div>
                    <div class="p-2">
                        <a href="{{ route('login') }}" class="flex items-center justify-center gap-3 px-3 py-2 text-sm font-bold text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 transition-colors shadow-md shadow-indigo-200">
                            <i class="fas fa-sign-in-alt"></i> Login Sekarang
                        </a>
                    </div>
                @endauth

            </div>
        </div>

    </div>
</header>