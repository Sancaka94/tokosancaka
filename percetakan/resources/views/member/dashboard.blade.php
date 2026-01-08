@extends('layouts.member')

@section('title', 'Dashboard')

@section('content')

    <div class="relative bg-slate-800 rounded-3xl p-6 text-white shadow-xl overflow-hidden mb-6 group transition-all hover:shadow-2xl hover:scale-[1.01]">
        <div class="absolute top-0 right-0 -mr-8 -mt-8 w-32 h-32 rounded-full bg-white/10 blur-2xl group-hover:bg-white/15 transition"></div>
        <div class="absolute bottom-0 left-0 -ml-8 -mb-8 w-24 h-24 rounded-full bg-blue-500/20 blur-xl group-hover:bg-blue-500/30 transition"></div>

        <div class="relative z-10">
            <div class="flex justify-between items-start mb-4">
                <div>
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Saldo Komisi</p>
                    <h2 class="text-3xl font-black mt-1">Rp {{ number_format($mmember->balance, 0, ',', '.') }}</h2>
                </div>
                <div class="bg-white/20 p-2.5 rounded-xl backdrop-blur-sm border border-white/10">
                    <i class="fas fa-wallet text-xl"></i>
                </div>
            </div>

            <div class="bg-white/10 rounded-xl p-3 flex items-center justify-between border border-white/5 backdrop-blur-md">
                <div>
                    <p class="text-[9px] text-slate-300 uppercase mb-0.5">Kode Referal Anda</p>
                    <p class="font-mono font-bold text-lg text-yellow-400 tracking-wide leading-none">{{ $member->coupon_code }}</p>
                </div>
                <button onclick="navigator.clipboard.writeText('{{ $member->coupon_code }}'); alert('Kode Kupon berhasil disalin!')" 
                        class="text-[10px] bg-white text-slate-900 px-3 py-2 rounded-lg font-bold hover:bg-yellow-400 transition flex items-center gap-1 active:scale-95">
                    <i class="far fa-copy"></i> Salin
                </button>
            </div>
        </div>
    </div>

    <div class="mb-6">
        <h3 class="font-bold text-slate-700 text-sm mb-3 ml-1">Menu Utama</h3>
        <div class="grid grid-cols-4 gap-3">
            <a href="#" class="bg-white p-3 rounded-2xl shadow-sm border border-slate-100 flex flex-col items-center gap-2 hover:border-blue-300 hover:shadow-md transition group">
                <div class="w-10 h-10 rounded-full bg-blue-50 text-blue-600 flex items-center justify-center text-lg group-hover:bg-blue-600 group-hover:text-white transition">
                    <i class="fas fa-print"></i>
                </div>
                <span class="text-[10px] font-bold text-slate-600 group-hover:text-blue-600">Cetak</span>
            </a>
            
            <a href="https://wa.me/62812345678" class="bg-white p-3 rounded-2xl shadow-sm border border-slate-100 flex flex-col items-center gap-2 hover:border-green-300 hover:shadow-md transition group">
                <div class="w-10 h-10 rounded-full bg-green-50 text-green-600 flex items-center justify-center text-lg group-hover:bg-green-600 group-hover:text-white transition">
                    <i class="fab fa-whatsapp"></i>
                </div>
                <span class="text-[10px] font-bold text-slate-600 group-hover:text-green-600">Admin</span>
            </a>
            
            <a href="#" class="bg-white p-3 rounded-2xl shadow-sm border border-slate-100 flex flex-col items-center gap-2 hover:border-purple-300 hover:shadow-md transition group">
                <div class="w-10 h-10 rounded-full bg-purple-50 text-purple-600 flex items-center justify-center text-lg group-hover:bg-purple-600 group-hover:text-white transition">
                    <i class="fas fa-users"></i>
                </div>
                <span class="text-[10px] font-bold text-slate-600 group-hover:text-purple-600">Tim</span>
            </a>
            
            <a href="#" class="bg-white p-3 rounded-2xl shadow-sm border border-slate-100 flex flex-col items-center gap-2 hover:border-orange-300 hover:shadow-md transition group">
                <div class="w-10 h-10 rounded-full bg-orange-50 text-orange-600 flex items-center justify-center text-lg group-hover:bg-orange-600 group-hover:text-white transition">
                    <i class="fas fa-cog"></i>
                </div>
                <span class="text-[10px] font-bold text-slate-600 group-hover:text-orange-600">Akun</span>
            </a>
        </div>
    </div>

    <div>
        <div class="flex justify-between items-center mb-3 px-1">
            <h3 class="font-bold text-slate-700 text-sm">Pesanan Terakhir</h3>
            <a href="#" class="text-[10px] font-bold text-blue-600 hover:underline">Lihat Semua</a>
        </div>

        <div class="space-y-3">
            @forelse($orders as $order)
            <div class="bg-white p-4 rounded-2xl border border-slate-100 shadow-sm flex justify-between items-center hover:shadow-md transition cursor-pointer">
                <div class="flex items-center gap-3">
                    <div class="h-10 w-10 shrink-0 rounded-xl bg-slate-50 flex items-center justify-center text-slate-400 border border-slate-100">
                        <i class="fas fa-receipt"></i>
                    </div>
                    <div>
                        <p class="text-xs font-bold text-slate-800">{{ $order->order_number }}</p>
                        <p class="text-[10px] text-slate-400 flex items-center gap-1">
                            <i class="far fa-clock text-[9px]"></i>
                            {{ \Carbon\Carbon::parse($order->created_at)->translatedFormat('d M, H:i') }}
                        </p>
                    </div>
                </div>
                <div class="text-right">
                    <p class="text-xs font-black text-slate-800">Rp {{ number_format($order->final_price, 0, ',', '.') }}</p>
                    
                    @php
                        $statusClass = match($order->status) {
                            'completed' => 'text-emerald-600 bg-emerald-50 border-emerald-100',
                            'processing' => 'text-blue-600 bg-blue-50 border-blue-100',
                            'pending' => 'text-amber-600 bg-amber-50 border-amber-100',
                            'cancelled' => 'text-red-600 bg-red-50 border-red-100',
                            default => 'text-slate-500 bg-slate-50'
                        };
                    @endphp
                    
                    <span class="inline-block mt-1 px-2 py-0.5 rounded text-[9px] font-bold uppercase border {{ $statusClass }}">
                        {{ $order->status }}
                    </span>
                </div>
            </div>
            @empty
            <div class="text-center py-10 px-4 text-slate-400 bg-white rounded-2xl border border-dashed border-slate-200">
                <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-3">
                    <i class="fas fa-history text-2xl text-slate-300"></i>
                </div>
                <p class="text-xs font-bold text-slate-500">Belum ada pesanan</p>
                <p class="text-[10px]">Riwayat transaksi Anda akan muncul di sini.</p>
            </div>
            @endforelse
        </div>
    </div>

@endsection