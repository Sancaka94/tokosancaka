<header class="h-20 bg-white border-b border-slate-200 flex items-center justify-between px-6 sticky top-0 z-20">
    <button @click="sidebarOpen = !sidebarOpen" class="text-slate-500 lg:hidden focus:outline-none">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7"></path></svg>
    </button>

    <div class="hidden md:block">
        <h1 class="text-slate-400 text-xs font-bold uppercase tracking-widest">Selamat Datang,</h1>
        <p class="text-slate-800 font-black">{{ Auth::user()->name }} 👋</p>
    </div>

    <div class="flex items-center gap-4">
        <div class="text-right hidden sm:block">
            <p class="text-sm font-bold text-slate-800">{{ Auth::user()->email }}</p>
            <span class="text-[10px] bg-green-100 text-green-700 px-2 py-0.5 rounded-full font-black uppercase">{{ Auth::user()->role ?? 'Admin' }}</span>
        </div>
        <div class="h-10 w-10 bg-slate-200 rounded-full border-2 border-white shadow-sm overflow-hidden">
            <img src="https://ui-avatars.com/api/?name={{ Auth::user()->name }}&background=6366f1&color=fff" alt="User">
        </div>
    </div>
</header>