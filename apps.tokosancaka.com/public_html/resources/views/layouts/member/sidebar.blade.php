<aside 
    class="fixed inset-y-0 left-0 z-50 w-64 bg-slate-900 text-white transition-transform duration-300 ease-in-out transform md:translate-x-0 md:static md:inset-0 shadow-xl"
    :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
>
    <div class="flex items-center justify-center h-16 bg-slate-950 border-b border-slate-800">
        <div class="flex items-center gap-2 font-black text-xl tracking-wider text-blue-500">
            <i class="fas fa-print"></i> <span>SANCAKA</span>
        </div>
    </div>

    <div class="p-4 border-b border-slate-800 md:hidden">
        <div class="flex items-center gap-3">
            <div class="h-10 w-10 rounded-full bg-blue-600 flex items-center justify-center font-bold">
                {{ substr(Auth::guard('member')->user()->name, 0, 1) }}
            </div>
            <div>
                <p class="text-sm font-bold truncate w-40">{{ Auth::guard('member')->user()->name }}</p>
                <p class="text-[10px] text-slate-400">Member</p>
            </div>
        </div>
    </div>

    <nav class="p-4 space-y-2 mt-2">
        
        <a href="{{ route('member.dashboard') }}" 
           class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-200 group {{ request()->routeIs('member.dashboard') ? 'bg-blue-600 text-white shadow-lg shadow-blue-900' : 'text-slate-400 hover:bg-slate-800 hover:text-white' }}">
            <i class="fas fa-home w-5 text-center"></i>
            <span class="text-sm font-bold">Dashboard</span>
        </a>

        <a href="{{ route('member.orders.index') }}" 
           class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-200 group {{ request()->routeIs('member.orders.*') ? 'bg-blue-600 text-white shadow-lg shadow-blue-900' : 'text-slate-400 hover:bg-slate-800 hover:text-white' }}">
            <i class="fas fa-history w-5 text-center"></i>
            <span class="text-sm font-bold">Riwayat Pesanan</span>
        </a>

        <a href="{{ route('member.settings.index') }}" 
           class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-200 group {{ request()->routeIs('member.profile.*') ? 'bg-blue-600 text-white shadow-lg shadow-blue-900' : 'text-slate-400 hover:bg-slate-800 hover:text-white' }}">
            <i class="fas fa-user-cog w-5 text-center"></i>
            <span class="text-sm font-bold">Pengaturan Akun</span>
        </a>

        <div class="border-t border-slate-800 my-4"></div>

        <form action="{{ route('member.logout') }}" method="POST">
            @csrf
            <button type="submit" class="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-red-400 hover:bg-red-500/10 hover:text-red-500 transition-all duration-200">
                <i class="fas fa-sign-out-alt w-5 text-center"></i>
                <span class="text-sm font-bold">Keluar Aplikasi</span>
            </button>
        </form>
    </nav>
</aside>

<div x-show="sidebarOpen" 
     @click="sidebarOpen = false"
     class="fixed inset-0 z-40 bg-black/50 backdrop-blur-sm md:hidden"
     style="display: none;">
</div>