<div class="md:hidden fixed bottom-0 left-0 z-50 w-full h-16 bg-white border-t border-slate-200 shadow-[0_-5px_10px_rgba(0,0,0,0.02)] flex justify-around items-center px-2 pb-safe">

    {{-- 1. HOME --}}
    <a wire:navigate href="{{ route('dashboard') }}"
       class="flex flex-col items-center justify-center w-full h-full space-y-1 {{ request()->routeIs('dashboard') ? 'text-blue-600' : 'text-slate-400' }}">
        <i class="fas fa-home text-lg"></i>
        <span class="text-[10px] font-medium">Home</span>
    </a>

    {{-- 2. TRANSAKSI --}}
    <a wire:navigate href="{{ route('orders.create') }}"
       class="flex flex-col items-center justify-center w-full h-full space-y-1 {{ request()->routeIs('orders.*') ? 'text-blue-600' : 'text-slate-400' }}">
        <div class="w-10 h-10 bg-blue-600 rounded-full flex items-center justify-center -mt-6 shadow-lg border-4 border-slate-100">
            <i class="fas fa-plus text-white text-lg"></i>
        </div>
    </a>

    {{-- 3. RIWAYAT --}}
    <a wire:navigate href="{{ route('orders.index') }}"
       class="flex flex-col items-center justify-center w-full h-full space-y-1 {{ request()->routeIs('reports.*') ? 'text-blue-600' : 'text-slate-400' }}">
        <i class="fas fa-history text-lg"></i>
        <span class="text-[10px] font-medium">Riwayat</span>
    </a>

    {{-- 4. AKUN --}}
    <a wire:navigate href="{{ route('profile.index') }}"
       class="flex flex-col items-center justify-center w-full h-full space-y-1 {{ request()->routeIs('profile.*') ? 'text-blue-600' : 'text-slate-400' }}">
        <i class="fas fa-user text-lg"></i>
        <span class="text-[10px] font-medium">Akun</span>
    </a>
</div>
