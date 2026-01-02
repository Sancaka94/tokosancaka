<header class="h-16 bg-white border-b border-slate-200 flex items-center justify-between px-4 lg:px-6 sticky top-0 z-20 shadow-sm transition-all duration-300">
    
    <div class="flex items-center">
        <button @click="sidebarOpen = !sidebarOpen" 
                class="p-2 -ml-2 text-slate-500 hover:text-indigo-600 hover:bg-slate-100 rounded-lg lg:hidden focus:outline-none transition-colors">
            <i class="fas fa-bars text-xl"></i>
        </button>
    </div>

    <div class="flex items-center gap-4">
        
        <button class="relative p-2 text-slate-400 hover:text-indigo-600 hover:bg-slate-50 rounded-full transition-colors group">
            <i class="fas fa-bell text-lg group-hover:animate-swing"></i>
            <span class="absolute top-2 right-2 h-2 w-2 rounded-full bg-red-500 ring-2 ring-white"></span>
        </button>

        <div class="h-6 w-px bg-slate-200 hidden sm:block"></div>

        <div class="relative" x-data="{ userOpen: false }">
            
            <button @click="userOpen = !userOpen" 
                    @click.outside="userOpen = false"
                    class="flex items-center gap-3 focus:outline-none group">
                
                <div class="text-right hidden md:block">
                    <p class="text-sm font-bold text-slate-700 group-hover:text-indigo-600 transition-colors">
                        {{ Auth::user()->name }}
                    </p>
                    <p class="text-[10px] text-slate-400 font-medium uppercase tracking-wider">
                        {{ Auth::user()->role ?? 'Admin' }}
                    </p>
                </div>

                <div class="h-9 w-9 rounded-full bg-slate-100 border border-slate-200 p-0.5 shadow-sm overflow-hidden group-hover:ring-2 group-hover:ring-indigo-500/30 transition-all">
                    <img class="h-full w-full rounded-full object-cover" 
                         src="https://ui-avatars.com/api/?name={{ urlencode(Auth::user()->name) }}&background=6366f1&color=fff&size=64" 
                         alt="{{ Auth::user()->name }}">
                </div>
                
                <i class="fas fa-chevron-down text-xs text-slate-300 group-hover:text-slate-500 transition-colors hidden sm:block"></i>
            </button>

            <div x-show="userOpen" 
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-95 transform"
                 x-transition:enter-end="opacity-100 scale-100 transform"
                 x-transition:leave="transition ease-in duration-75"
                 x-transition:leave-start="opacity-100 scale-100 transform"
                 x-transition:leave-end="opacity-0 scale-95 transform"
                 class="absolute right-0 mt-2 w-64 bg-white rounded-xl shadow-2xl border border-slate-100 py-2 z-50 origin-top-right"
                 style="display: none;">

                <div class="px-4 py-3 border-b border-slate-100 bg-slate-50/50">
                    <p class="text-xs text-slate-400 font-bold uppercase tracking-widest mb-1">Selamat Datang,</p>
                    <p class="text-sm font-black text-slate-800 truncate">{{ Auth::user()->name }} 👋</p>
                    <p class="text-xs text-slate-500 truncate">{{ Auth::user()->email }}</p>
                    
                    <div class="mt-2">
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-indigo-100 text-indigo-700 uppercase">
                            {{ Auth::user()->role ?? 'Customer' }}
                        </span>
                    </div>
                </div>

                <div class="py-1">
                    <a href="#" class="block px-4 py-2 text-sm text-slate-600 hover:bg-indigo-50 hover:text-indigo-600 transition-colors">
                        <i class="fas fa-user-circle w-5 text-center mr-2 text-slate-400"></i> Profile Saya
                    </a>
                    <a href="#" class="block px-4 py-2 text-sm text-slate-600 hover:bg-indigo-50 hover:text-indigo-600 transition-colors">
                        <i class="fas fa-cog w-5 text-center mr-2 text-slate-400"></i> Pengaturan
                    </a>
                </div>

                <div class="border-t border-slate-100 my-1"></div>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="w-full text-left px-4 py-2 text-sm text-red-500 hover:bg-red-50 hover:text-red-600 transition-colors flex items-center">
                        <i class="fas fa-sign-out-alt w-5 text-center mr-2"></i> Keluar Sistem
                    </button>
                </form>
            </div>

        </div>
    </div>
</header>