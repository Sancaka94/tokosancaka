<div class="fixed inset-y-0 left-0 z-30 w-64 bg-slate-900 text-white transition-all duration-300 transform lg:translate-x-0 lg:static lg:inset-0 shadow-2xl" 
     :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'">
    
    <div class="flex items-center justify-center h-20 bg-slate-950 gap-3 border-b border-slate-800">
        <img src="https://tokosancaka.com/storage/uploads/sancaka.png" class="h-10 w-10">
        <span class="text-xl font-black tracking-tighter uppercase italic">Sancaka<span class="text-indigo-500">POS</span></span>
    </div>

    <nav class="mt-6 px-4 space-y-2">
        <a href="{{ route('dashboard') }}" class="flex items-center gap-3 p-3 rounded-xl transition {{ request()->routeIs('dashboard') ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-500/30' : 'text-slate-400 hover:bg-slate-800' }}">
            <span class="text-lg">📊</span>
            <span class="font-bold text-sm">Dashboard</span>
        </a>

        <div class="pt-4 pb-2 px-3 text-[10px] font-bold text-slate-500 uppercase tracking-[0.2em]">Transaksi</div>
        
        <a href="{{ route('orders.create') }}" class="flex items-center gap-3 p-3 rounded-xl transition {{ request()->routeIs('orders.create') ? 'bg-emerald-600 text-white shadow-lg shadow-emerald-500/30' : 'text-slate-400 hover:bg-slate-800' }}">
            <span class="text-lg">➕</span>
            <span class="font-bold text-sm">Buat Pesanan Baru</span>
        </a>

        <div class="pt-4 pb-2 px-3 text-[10px] font-bold text-slate-500 uppercase tracking-[0.2em]">Manajemen</div>

        <a href="#" class="flex items-center gap-3 p-3 rounded-xl text-slate-400 hover:bg-slate-800 transition">
            <span class="text-lg">📦</span>
            <span class="font-bold text-sm">Data Produk</span>
        </a>

        <a href="#" class="flex items-center gap-3 p-3 rounded-xl text-slate-400 hover:bg-slate-800 transition">
            <span class="text-lg">📑</span>
            <span class="font-bold text-sm">Laporan Pesanan</span>
        </a>

        <a href="#" class="flex items-center gap-3 p-3 rounded-xl text-slate-400 hover:bg-slate-800 transition">
            <span class="text-lg">🏷️</span>
            <span class="font-bold text-sm">Kupon & Promo</span>
        </a>

        <div class="pt-10">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="w-full flex items-center gap-3 p-3 rounded-xl text-red-400 hover:bg-red-500/10 transition border border-transparent hover:border-red-500/20">
                    <span class="text-lg">🚪</span>
                    <span class="font-bold text-sm">Keluar Sistem</span>
                </button>
            </form>
        </div>
    </nav>
</div>