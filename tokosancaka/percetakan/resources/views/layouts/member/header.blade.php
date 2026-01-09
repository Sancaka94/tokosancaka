<header class="bg-white shadow-sm sticky top-0 z-30 h-16 flex items-center justify-between px-4 lg:px-8">
    
    <div class="flex items-center">
        <button @click="sidebarOpen = !sidebarOpen" class="p-2 rounded-lg text-slate-500 hover:bg-slate-100 md:hidden focus:outline-none">
            <i class="fas fa-bars text-xl"></i>
        </button>
        
        <h2 class="hidden md:block font-bold text-slate-700 ml-4">
            @yield('title', 'Member Area')
        </h2>
    </div>

    <div class="flex items-center gap-4">
        
        <div class="hidden sm:flex flex-col items-end mr-2">
            <span class="text-[10px] text-slate-400 uppercase font-bold">Saldo</span>
            <span class="text-sm font-black text-slate-800">Rp {{ number_format(Auth::guard('member')->user()->balance, 0, ',', '.') }}</span>
        </div>

        <div class="flex items-center gap-3 pl-4 border-l border-slate-100">
            <div class="text-right hidden md:block">
                <div class="text-sm font-bold text-slate-800">{{ Auth::guard('member')->user()->name }}</div>
                <div class="text-[10px] text-slate-500">Member</div>
            </div>
            <div class="h-9 w-9 rounded-full bg-gradient-to-tr from-blue-600 to-indigo-600 text-white flex items-center justify-center font-bold shadow-md shadow-blue-200">
                {{ substr(Auth::guard('member')->user()->name, 0, 1) }}
            </div>
        </div>
    </div>
</header>