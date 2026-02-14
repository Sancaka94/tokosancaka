@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')

    {{-- ======================================================================= --}}
    {{-- 1. AREA NOTIFIKASI (ALERT SYSTEM)                                       --}}
    {{-- ======================================================================= --}}
    <div class="mb-8">
        {{-- LOGIC SUCCESS: Cek Session ATAU URL Parameter (Redirect Gateway) --}}
        @php
            $successMsg = session('success');

            // Jika session kosong, cek apakah ada operan dari Gateway Pusat?
            if (!$successMsg && request('dana_status') == 'success') {
                $successMsg = urldecode(request('msg') ?? 'Operasi Berhasil!');
            }
            if (!$successMsg && request('dana_bind') == 'success') {
                $successMsg = 'Akun DANA Berhasil Terhubung!';
            }
            if (!$successMsg && request('payment_status') == 'success') {
                $successMsg = 'Pembayaran Berhasil Diterima!';
            }
        @endphp

        @if($successMsg)
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 px-5 py-4 rounded-2xl flex items-center gap-4 shadow-sm animate-fade-in mb-4 relative overflow-hidden">
            <div class="absolute left-0 top-0 bottom-0 w-1.5 bg-emerald-500"></div>
            <div class="bg-emerald-100 p-2 rounded-full">
                <i class="fas fa-check-circle text-xl"></i>
            </div>
            <div class="flex-1">
                <h4 class="font-bold text-sm uppercase tracking-wide text-emerald-800">Sukses</h4>
                <p class="text-xs font-medium opacity-90 whitespace-pre-line">{{ $successMsg }}</p>
            </div>
            <button onclick="this.parentElement.remove()" class="text-emerald-400 hover:text-emerald-800 transition p-2">
                <i class="fas fa-times"></i>
            </button>
        </div>
        @endif

        {{-- LOGIC ERROR: Cek Session ATAU URL Parameter --}}
        @php
            $errorMsg = session('error');

            // Cek URL jika session kosong
            if (!$errorMsg && in_array(request('dana_status'), ['failed', 'error', 'cancelled'])) {
                $errorMsg = urldecode(request('msg') ?? 'Terjadi kesalahan saat menghubungkan DANA.');
            }
            if (!$errorMsg && request('dana_bind') == 'failed') {
                $errorMsg = 'Gagal menghubungkan akun DANA.';
            }
            if (!$errorMsg && request('payment_status') == 'failed') {
                $errorMsg = 'Pembayaran Gagal diproses.';
            }
        @endphp

        @if($errorMsg)
        <div class="bg-rose-50 border border-rose-200 text-rose-700 px-5 py-4 rounded-2xl flex items-center gap-4 shadow-sm animate-fade-in mb-4 relative overflow-hidden">
            <div class="absolute left-0 top-0 bottom-0 w-1.5 bg-rose-500"></div>
            <div class="bg-rose-100 p-2 rounded-full">
                <i class="fas fa-exclamation-triangle text-xl"></i>
            </div>
            <div class="flex-1">
                <h4 class="font-bold text-sm uppercase tracking-wide text-rose-800">Perhatian</h4>
                <p class="text-xs font-medium opacity-90">{{ $errorMsg }}</p>
            </div>
            <button onclick="this.parentElement.remove()" class="text-rose-400 hover:text-rose-800 transition p-2">
                <i class="fas fa-times"></i>
            </button>
        </div>
        @endif
    </div>
    {{-- ======================================================================= --}}

    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-10">
        <div>
            <h1 class="text-3xl font-black text-slate-900 tracking-tight">Ringkasan Operasional</h1>
            <p class="text-slate-500 font-medium text-sm mt-1">
                Pantau performa bisnis Anda hari ini, <span class="text-blue-600 font-bold">{{ \Carbon\Carbon::now()->translatedFormat('d F Y') }}</span>
            </p>
        </div>

        <a href="{{ route('orders.create', ['subdomain' => $currentSubdomain]) }}">Buat Pesanan</a>
           class="flex items-center gap-3 px-6 py-3.5 bg-blue-600 hover:bg-blue-700 text-white rounded-2xl font-bold shadow-xl shadow-blue-200 transition-all transform hover:-translate-y-1 active:scale-95">
            <i class="fas fa-plus-circle text-lg"></i>
            <span>Buat Transaksi Baru</span>
        </a>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-6 mb-10">

        {{-- Total Omzet --}}
        <div class="bg-white rounded-[2rem] p-6 shadow-sm border border-slate-100 relative overflow-hidden group hover:shadow-2xl hover:shadow-slate-200 transition-all duration-500">
            <div class="absolute -right-4 -top-4 w-24 h-24 bg-blue-50 rounded-full opacity-50 group-hover:scale-150 transition-transform duration-700"></div>
            <div class="relative z-10">
                <div class="w-12 h-12 rounded-2xl bg-blue-600 flex items-center justify-center text-white mb-4 shadow-lg shadow-blue-100">
                    <i class="fas fa-wallet text-xl"></i>
                </div>
                <p class="text-xs font-bold text-slate-400 uppercase tracking-[0.1em] mb-1">Total Omzet</p>
                <h3 class="text-2xl font-black text-slate-900 truncate">
                    <span class="text-sm font-bold text-blue-600">Rp</span> {{ number_format($totalOmzet ?? 0, 0, ',', '.') }}
                </h3>
                <div class="flex items-center gap-1.5 mt-3 text-[10px] font-black text-emerald-600 bg-emerald-50 w-fit px-2.5 py-1 rounded-full uppercase">
                    <i class="fas fa-circle-check"></i> Paid Only
                </div>
            </div>
        </div>

        {{-- Item Terjual --}}
        <div class="bg-white rounded-[2rem] p-6 shadow-sm border border-slate-100 relative overflow-hidden group hover:shadow-2xl hover:shadow-slate-200 transition-all duration-500">
            <div class="relative z-10">
                <div class="w-12 h-12 rounded-2xl bg-emerald-500 flex items-center justify-center text-white mb-4 shadow-lg shadow-emerald-100">
                    <i class="fas fa-box-open text-xl"></i>
                </div>
                <p class="text-xs font-bold text-slate-400 uppercase tracking-[0.1em] mb-1">Item Terjual</p>
                <h3 class="text-2xl font-black text-slate-900">
                    {{ number_format($totalTerjual ?? 0) }} <span class="text-sm font-medium text-slate-400">Pcs</span>
                </h3>
                <p class="text-[10px] text-slate-400 font-bold mt-4 uppercase">Volume Produk</p>
            </div>
        </div>

        {{-- Saldo DANA --}}
        <div class="bg-gradient-to-br from-blue-600 to-blue-700 rounded-[2rem] p-6 shadow-xl shadow-blue-100 relative overflow-hidden group active:scale-95 transition-all">
            <div class="absolute right-0 bottom-0 opacity-10 transform translate-x-4 translate-y-4">
                <i class="fas fa-vault text-[100px]"></i>
            </div>
            <div class="relative z-10">
                <div class="flex justify-between items-start mb-4">
                    <div class="w-10 h-10 rounded-xl bg-white/20 backdrop-blur-md flex items-center justify-center text-white">
                        <i class="fas fa-shield-halved text-lg"></i>
                    </div>
                    <form action="{{ route('dana.checkMerchantBalance') }}" method="POST">
                        @csrf
                        <input type="hidden" name="affiliate_id" value="11">
                        <button type="submit" class="p-2 bg-white/10 hover:bg-white/30 rounded-lg text-white transition-colors">
                            <i class="fas fa-arrows-rotate text-sm"></i>
                        </button>
                    </form>
                </div>
                <p class="text-[10px] font-bold text-blue-100 uppercase tracking-widest mb-1">Saldo Deposit DANA</p>
                <h3 class="text-xl font-black text-white truncate">
                    Rp {{ number_format($merchantBalance ?? 0, 0, ',', '.') }}
                </h3>
                <p class="text-[9px] font-bold text-blue-200 mt-3 italic">*Data Terkini API</p>
            </div>
        </div>

        {{-- Pelanggan --}}
        <div class="bg-white rounded-[2rem] p-6 shadow-sm border border-slate-100 relative overflow-hidden group hover:shadow-2xl transition-all duration-500">
            <div class="relative z-10">
                <div class="w-12 h-12 rounded-2xl bg-amber-500 flex items-center justify-center text-white mb-4 shadow-lg shadow-amber-100 text-xl">
                    <i class="fas fa-users"></i>
                </div>
                <p class="text-xs font-bold text-slate-400 uppercase tracking-[0.1em] mb-1">Pelanggan</p>
                <h3 class="text-2xl font-black text-slate-900">
                    {{ number_format($totalPelanggan ?? 0) }}
                </h3>
                <p class="text-[10px] text-slate-400 font-bold mt-4 uppercase text-amber-600">Terdaftar Aktif</p>
            </div>
        </div>

        {{-- Staff --}}
        <div class="bg-white rounded-[2rem] p-6 shadow-sm border border-slate-100 relative overflow-hidden group hover:shadow-2xl transition-all duration-500">
            <div class="relative z-10">
                <div class="w-12 h-12 rounded-2xl bg-red-600 flex items-center justify-center text-white mb-4 shadow-lg shadow-red-100 text-xl text-white">
                    <i class="fas fa-user-shield"></i>
                </div>
                <p class="text-xs font-bold text-slate-400 uppercase tracking-[0.1em] mb-1">User / Staff</p>
                <h3 class="text-2xl font-black text-slate-900">
                    {{ number_format($totalUser ?? 0) }}
                </h3>
                <p class="text-[10px] text-red-600 font-bold mt-4 uppercase">Akun Terotorisasi</p>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

        <div class="lg:col-span-2 bg-white rounded-[2.5rem] shadow-sm border border-slate-100 overflow-hidden">
            <div class="p-8 border-b border-slate-50 flex justify-between items-center">
                <div>
                    <h3 class="font-black text-slate-800 text-lg flex items-center gap-3">
                        <span class="w-2 h-6 bg-blue-600 rounded-full"></span>
                        Transaksi Terbaru
                    </h3>
                </div>
                <a href="{{ route('orders.index') }}" class="text-xs font-bold text-blue-600 hover:bg-blue-50 px-4 py-2 rounded-xl transition-all">Lihat Semua</a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left border-separate border-spacing-y-2 px-6">
                    <thead class="text-slate-400 uppercase text-[10px] font-bold tracking-[0.15em]">
                        <tr>
                            <th class="px-4 py-4">Order ID</th>
                            <th class="px-4 py-4">Pelanggan</th>
                            <th class="px-4 py-4 text-right">Total</th>
                            <th class="px-4 py-4 text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentOrders ?? [] as $order)
                        <tr class="group hover:bg-slate-50/80 transition-all duration-300">
                            <td class="px-4 py-5 bg-slate-50 rounded-l-2xl group-hover:bg-white transition-colors">
                                <span class="font-black text-blue-600">#{{ $order->order_number }}</span>
                            </td>
                            <td class="px-4 py-5 group-hover:bg-white transition-colors">
                                <p class="font-bold text-slate-800 leading-none mb-1">{{ $order->customer_name }}</p>
                                <span class="text-[10px] text-slate-400 font-bold">{{ $order->created_at->diffForHumans() }}</span>
                            </td>
                            <td class="px-4 py-5 text-right group-hover:bg-white transition-colors">
                                <span class="font-black text-slate-900">Rp {{ number_format($order->final_price, 0, ',', '.') }}</span>
                            </td>
                            <td class="px-4 py-5 text-center rounded-r-2xl group-hover:bg-white transition-colors">
                                <span class="px-3 py-1.5 rounded-xl text-[10px] font-black uppercase tracking-wider {{ $order->payment_status == 'paid' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">
                                    {{ $order->payment_status }}
                                </span>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="px-6 py-12 text-center">
                                <div class="flex flex-col items-center gap-2">
                                    <i class="fas fa-file-circle-xmark text-4xl text-slate-200"></i>
                                    <p class="text-slate-400 text-sm font-medium italic">Belum ada transaksi hari ini.</p>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="bg-white rounded-[2.5rem] shadow-sm border border-slate-100 p-8">
            <h3 class="font-black text-slate-800 text-lg flex items-center gap-3 mb-8">
                <span class="w-2 h-6 bg-red-600 rounded-full"></span>
                Layanan Populer
            </h3>

            <div class="space-y-5">
                @forelse($newProducts ?? [] as $product)
                <div class="flex items-center gap-4 group cursor-pointer">
                    <div class="w-14 h-14 rounded-2xl bg-slate-50 flex items-center justify-center text-slate-400 group-hover:bg-blue-600 group-hover:text-white group-hover:shadow-lg group-hover:shadow-blue-200 transition-all duration-300 transform group-hover:-rotate-6">
                        <i class="fas fa-cube text-xl"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <h4 class="text-sm font-black text-slate-800 group-hover:text-blue-600 transition-colors truncate">{{ $product->name }}</h4>
                        <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest">{{ $product->unit }}</p>
                    </div>
                    <div class="text-right">
                        <p class="text-sm font-black text-slate-900 leading-none">Rp {{ number_format($product->base_price, 0, ',', '.') }}</p>
                    </div>
                </div>
                @empty
                <div class="text-center py-10">
                    <i class="fas fa-box-open text-3xl text-slate-100 mb-2"></i>
                    <p class="text-xs text-slate-400 font-medium">Kosong</p>
                </div>
                @endforelse

                <div class="pt-6 border-t border-slate-50">
                    <a href="{{ route('products.index') }}" class="flex items-center justify-center gap-2 w-full py-4 text-xs font-black text-slate-500 bg-slate-50 rounded-2xl hover:bg-blue-600 hover:text-white transition-all shadow-sm">
                        LIHAT SEMUA PRODUK <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
            </div>
        </div>

    </div>
@endsection
