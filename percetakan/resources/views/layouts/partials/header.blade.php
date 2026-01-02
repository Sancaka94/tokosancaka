<header class="h-16 bg-white border-b border-slate-200 flex items-center justify-between px-4 lg:px-6 sticky top-0 z-20 shadow-sm transition-all duration-300">
    
    <div class="flex items-center gap-4">
        <button @click="sidebarOpen = !sidebarOpen" 
                class="p-2 -ml-2 text-slate-500 hover:text-indigo-600 hover:bg-slate-100 rounded-lg lg:hidden focus:outline-none transition-colors">
            <i class="fas fa-bars text-xl"></i>
        </button>

        <div class="hidden md:block">
            <h1 class="text-slate-400 text-[10px] font-bold uppercase tracking-widest leading-tight">Selamat Datang,</h1>
            <p class="text-slate-800 text-sm font-bold truncate max-w-[200px]">{{ Auth::user()->name }} 👋</p>
        </div>
    </div>

    <div class="flex items-center gap-3 sm:gap-4">
        
        <button class="relative p-2 text-slate-400 hover:text-indigo-600 hover:bg-slate-50 rounded-full transition-colors group">
            <i class="fas fa-bell text-lg group-hover:animate-swing"></i>
            <span class="absolute top-1.5 right-1.5 block h-2 w-2 rounded-full bg-red-500 ring-2 ring-white"></span>
        </button>

        <div class="h-8 w-px bg-slate-200 hidden sm:block"></div>

        <div class="flex items-center gap-3 pl-1 sm:pl-0">
            <div class="text-right hidden sm:block">
                <p class="text-sm font-bold text-slate-800 leading-tight">{{ Auth::user()->name }}</p>
                <div class="flex justify-end mt-0.5">
                    <span class="text-[10px] bg-indigo-50 text-indigo-600 border border-indigo-100 px-2 py-0.5 rounded-full font-bold uppercase tracking-wide">
                        {{ Auth::user()->role ?? 'Admin' }}
                    </span>
                </div>
            </div>

            <div class="h-9 w-9 rounded-full bg-slate-100 border border-slate-200 p-0.5 shadow-sm overflow-hidden cursor-pointer hover:ring-2 hover:ring-indigo-500/30 transition-all">
                <img class="h-full w-full rounded-full object-cover" 
                     src="https://ui-avatars.com/api/?name={{ urlencode(Auth::user()->name) }}&background=6366f1&color=fff&size=64" 
                     alt="{{ Auth::user()->name }}">
            </div>
        </div>
    </div>
</header>