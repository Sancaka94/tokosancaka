@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-8">
        <div>
            <h1 class="text-2xl font-bold text-slate-800 tracking-tight">Ringkasan Operasional</h1>
            <p class="text-slate-500 font-medium text-sm">
                Data transaksi per hari ini, {{ \Carbon\Carbon::now()->translatedFormat('d F Y') }}
            </p>
        </div>
        
        <a href="{{ route('orders.create') }}" 
           class="flex items-center gap-2 px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-bold shadow-md hover:shadow-lg transition-all transform hover:-translate-y-0.5">
            <i class="fas fa-plus"></i>
            <span>Transaksi Baru</span>
        </a>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 xl:grid-cols-5 gap-6 mb-8">
    
    {{-- Total Omzet --}}
    <div class="bg-white rounded-lg p-5 shadow-sm border-l-4 border-indigo-500 flex items-center justify-between group hover:shadow-md transition-all">
        <div class="min-w-0">
            <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Total Omzet</p>
            <h3 class="text-xl font-black text-slate-800 truncate">
                Rp {{ number_format($totalOmzet ?? 0, 0, ',', '.') }}
            </h3>
            <p class="text-[10px] text-emerald-600 font-bold mt-1">
                <i class="fas fa-check-circle"></i> Paid Only
            </p>
        </div>
        <div class="h-12 w-12 flex-shrink-0 rounded-full bg-indigo-50 flex items-center justify-center text-indigo-600 text-xl group-hover:bg-indigo-600 group-hover:text-white transition-colors">
            <i class="fas fa-wallet"></i>
        </div>
    </div>

    {{-- Item Terjual --}}
    <div class="bg-white rounded-lg p-5 shadow-sm border-l-4 border-emerald-500 flex items-center justify-between group hover:shadow-md transition-all">
        <div class="min-w-0">
            <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Item Terjual</p>
            <h3 class="text-xl font-black text-slate-800">
                {{ number_format($totalTerjual ?? 0) }}
            </h3>
            <p class="text-[10px] text-slate-400 font-bold mt-1 uppercase">Unit Produk</p>
        </div>
        <div class="h-12 w-12 flex-shrink-0 rounded-full bg-emerald-50 flex items-center justify-center text-emerald-600 text-xl group-hover:bg-emerald-600 group-hover:text-white transition-colors">
            <i class="fas fa-box-open"></i>
        </div>
    </div>

    {{-- CARD SALDO MERCHANT DANA - DIPERBAIKI --}}
    <div class="bg-white rounded-lg p-5 shadow-sm border-l-4 border-blue-600 flex items-center justify-between group hover:shadow-md transition-all">
        <div class="min-w-0 flex-1">
            <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Saldo Deposit DANA</p>
            <h3 class="text-lg font-black text-slate-800 truncate" title="Rp {{ number_format($merchantBalance ?? 0, 0, ',', '.') }}">
                Rp {{ number_format($merchantBalance ?? 0, 0, ',', '.') }}
            </h3>
            {{-- Perbaikan Rute: Pastikan menggunakan member.dana.checkMerchantBalance --}}
            <form action="{{ route('dana.checkMerchantBalance') }}" method="POST" class="mt-2">
                @csrf
                <input type="hidden" name="affiliate_id" value="11">
                <button type="submit" class="text-[9px] font-black text-blue-600 uppercase flex items-center gap-1 hover:text-blue-800 transition">
                    <i class="fas fa-sync-alt"></i> Refresh Saldo
                </button>
            </form>
        </div>
        <div class="h-12 w-12 flex-shrink-0 rounded-full bg-blue-50 flex items-center justify-center text-blue-600 text-xl group-hover:bg-blue-600 group-hover:text-white transition-colors ml-2">
            <i class="fas fa-vault"></i>
        </div>
    </div>

    {{-- Pelanggan --}}
    <div class="bg-white rounded-lg p-5 shadow-sm border-l-4 border-amber-500 flex items-center justify-between group hover:shadow-md transition-all">
        <div class="min-w-0">
            <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Pelanggan</p>
            <h3 class="text-xl font-black text-slate-800">
                {{ number_format($totalPelanggan ?? 0) }}
            </h3>
            <p class="text-[10px] text-slate-400 font-bold mt-1 uppercase">Orang</p>
        </div>
        <div class="h-12 w-12 flex-shrink-0 rounded-full bg-amber-50 flex items-center justify-center text-amber-600 text-xl group-hover:bg-amber-500 group-hover:text-white transition-colors">
            <i class="fas fa-users"></i>
        </div>
    </div>

    {{-- User / Staff --}}
    <div class="bg-white rounded-lg p-5 shadow-sm border-l-4 border-red-500 flex items-center justify-between group hover:shadow-md transition-all">
        <div class="min-w-0">
            <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">User / Staff</p>
            <h3 class="text-xl font-black text-slate-800">
                {{ number_format($totalUser ?? 0) }}
            </h3>
            <p class="text-[10px] text-slate-400 font-bold mt-1 uppercase">Akun Aktif</p>
        </div>
        <div class="h-12 w-12 flex-shrink-0 rounded-full bg-red-50 flex items-center justify-center text-red-600 text-xl group-hover:bg-red-500 group-hover:text-white transition-colors">
            <i class="fas fa-user-shield"></i>
        </div>
    </div>

</div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        <div class="lg:col-span-2 bg-white rounded-lg shadow-sm border border-slate-200 overflow-hidden">
            <div class="p-5 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
                <h3 class="font-bold text-slate-700 flex items-center gap-2">
                    <i class="fas fa-history text-slate-400"></i> Transaksi Terbaru
                </h3>
                <a href="#" class="text-xs font-bold text-indigo-600 hover:text-indigo-800">Lihat Semua</a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="bg-slate-50 text-slate-500 uppercase text-[10px] font-bold tracking-wider border-b border-slate-100">
                        <tr>
                            <th class="px-6 py-3">Order ID</th>
                            <th class="px-6 py-3">Pelanggan</th>
                            <th class="px-6 py-3 text-right">Total</th>
                            <th class="px-6 py-3 text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($recentOrders ?? [] as $order)
                        <tr class="hover:bg-slate-50 transition">
                            <td class="px-6 py-4 font-bold text-indigo-600 whitespace-nowrap">
                                #{{ $order->order_number }}
                            </td>
                            <td class="px-6 py-4 font-medium text-slate-700">
                                {{ $order->customer_name }}
                                <div class="text-[10px] text-slate-400 font-normal">{{ $order->created_at->diffForHumans() }}</div>
                            </td>
                            <td class="px-6 py-4 text-right font-bold text-slate-800">
                                Rp {{ number_format($order->final_price, 0, ',', '.') }}
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span class="px-2.5 py-1 rounded text-[10px] font-bold uppercase {{ $order->payment_status == 'paid' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">
                                    {{ $order->payment_status }}
                                </span>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="px-6 py-8 text-center text-slate-400 text-sm">
                                Belum ada data transaksi hari ini.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-slate-200">
            <div class="p-5 border-b border-slate-100 bg-slate-50/50">
                <h3 class="font-bold text-slate-700 flex items-center gap-2">
                    <i class="fas fa-star text-slate-400"></i> Layanan Populer
                </h3>
            </div>
            <div class="p-4 space-y-3">
                @forelse($newProducts ?? [] as $product)
                <div class="flex items-center gap-3 p-3 rounded-lg hover:bg-slate-50 transition border border-transparent hover:border-slate-200 group">
                    <div class="w-10 h-10 rounded-lg bg-indigo-50 flex items-center justify-center text-indigo-600 group-hover:bg-indigo-600 group-hover:text-white transition-colors">
                        <i class="fas fa-print"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <h4 class="text-sm font-bold text-slate-700 truncate">{{ $product->name }}</h4>
                        <p class="text-[10px] text-slate-400 font-bold uppercase">{{ $product->unit }}</p>
                    </div>
                    <div class="text-sm font-black text-slate-800 whitespace-nowrap">
                        {{ number_format($product->base_price, 0, ',', '.') }}
                    </div>
                </div>
                @empty
                <div class="text-center text-slate-400 text-xs py-4">
                    Belum ada data produk.
                </div>
                @endforelse
                
                <div class="pt-2">
                    <a href="{{ route('products.index') }}" class="block w-full py-2 text-center text-xs font-bold text-slate-500 border border-slate-200 rounded hover:bg-slate-50 hover:text-indigo-600 transition">
                        Lihat Semua Produk
                    </a>
                </div>
            </div>
        </div>

    </div>

@endsection