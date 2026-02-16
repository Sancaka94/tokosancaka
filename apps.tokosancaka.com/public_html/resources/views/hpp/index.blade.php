@extends('layouts.app')

@section('title', 'Pilih Produk - Analisa HPP')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

    {{-- Header Halaman --}}
    <div class="mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">Analisa HPP & Resep</h1>
            <p class="text-sm text-slate-500">Pilih produk (Barang Jadi / Jasa) untuk mulai menghitung modal pokok.</p>
        </div>

        {{-- Search Box Sederhana (Opsional, visual saja) --}}
        <div class="relative w-full md:w-64">
            <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                <i class="fas fa-search"></i>
            </span>
            <input type="text" placeholder="Cari produk..." disabled
                   class="w-full pl-10 pr-4 py-2 border border-slate-200 rounded-lg bg-slate-50 text-sm focus:outline-none cursor-not-allowed opacity-70"
                   title="Fitur pencarian belum aktif">
        </div>
    </div>

    {{-- Tabel Daftar Produk --}}
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm whitespace-nowrap">
                <thead class="bg-slate-50 text-slate-500 font-bold uppercase text-[10px] tracking-wider border-b border-slate-100">
                    <tr>
                        <th class="px-6 py-4">Nama Produk</th>
                        <th class="px-6 py-4">Kategori & Tipe</th>
                        <th class="px-6 py-4 text-right">Harga Jual</th>
                        <th class="px-6 py-4 text-right">HPP / Modal (Base Price)</th>
                        <th class="px-6 py-4 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @forelse($products as $product)
                    <tr class="hover:bg-blue-50/50 transition-colors group">

                        {{-- Nama Produk --}}
                        <td class="px-6 py-4">
                            <div class="font-bold text-slate-700 text-sm">{{ $product->name }}</div>
                            <div class="text-[10px] text-slate-400 font-mono">{{ $product->sku ?? '-' }}</div>
                        </td>

                        {{-- Tipe --}}
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-2">
                                <span class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase border
                                    {{ $product->type == 'service' ? 'bg-purple-50 text-purple-700 border-purple-100' : 'bg-blue-50 text-blue-700 border-blue-100' }}">
                                    {{ $product->type == 'service' ? 'Jasa / Layanan' : 'Barang Jadi' }}
                                </span>
                            </div>
                        </td>

                        {{-- Harga Jual --}}
                        <td class="px-6 py-4 text-right">
                            <span class="text-slate-600">Rp {{ number_format($product->sell_price, 0, ',', '.') }}</span>
                        </td>

                        {{-- HPP (Highlight) --}}
                        <td class="px-6 py-4 text-right">
                            <span class="font-bold text-slate-800 bg-slate-100 px-2 py-1 rounded">
                                Rp {{ number_format($product->base_price, 0, ',', '.') }}
                            </span>
                        </td>

                        {{-- Tombol Aksi --}}
                        <td class="px-6 py-4 text-center">
                            <a href="{{ route('hpp.analysis', $product->id) }}"
                               class="inline-flex items-center gap-2 bg-white border border-blue-200 text-blue-600 px-4 py-2 rounded-lg text-xs font-bold hover:bg-blue-600 hover:text-white hover:border-blue-600 transition-all shadow-sm group-hover:shadow-md">
                                <i class="fas fa-calculator"></i>
                                <span>Hitung Resep</span>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center text-slate-400">
                            <div class="flex flex-col items-center justify-center gap-2">
                                <i class="fas fa-box-open text-4xl opacity-30"></i>
                                <p class="text-sm font-medium">Belum ada produk bertipe 'Barang Jadi' atau 'Jasa'.</p>
                                <p class="text-xs">Silakan tambah produk baru terlebih dahulu di menu Data Produk.</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        <div class="px-6 py-4 border-t border-slate-100 bg-slate-50">
            {{ $products->links() }}
        </div>
    </div>
</div>
@endsection
