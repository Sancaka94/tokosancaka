@extends('layouts.app')

@section('title', 'Manajemen Produk')

@section('content')
    
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-800 tracking-tight">Manajemen Produk</h1>
            <p class="text-slate-500 font-medium text-sm">Kelola harga modal, harga jual, dan stok barang.</p>
        </div>
        <a href="{{ route('orders.create') }}" 
           class="flex items-center gap-2 px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-bold shadow-md hover:shadow-lg transition-all transform hover:-translate-y-0.5">
            <i class="fas fa-cash-register"></i>
            <span>Buka Kasir</span>
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        <div class="lg:col-span-1">
            <div class="bg-white p-6 rounded-lg shadow-sm border border-slate-200 sticky top-24">
                <div class="flex items-center gap-3 mb-6 pb-4 border-b border-slate-100">
                    <div class="h-10 w-10 rounded-lg bg-indigo-50 flex items-center justify-center text-indigo-600">
                        <i class="fas fa-plus"></i>
                    </div>
                    <div>
                        <h2 class="text-lg font-bold text-slate-800">Tambah Produk</h2>
                        <p class="text-xs text-slate-400">Input data layanan baru</p>
                    </div>
                </div>

                <form action="{{ route('products.store') }}" method="POST" class="space-y-5">
                    @csrf
                    
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-2">Nama Produk <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400"><i class="fas fa-tag"></i></span>
                            <input type="text" name="name" required placeholder="Contoh: Cetak A3+" 
                                   class="w-full pl-10 pr-4 py-2.5 rounded-lg border-slate-300 focus:border-indigo-500 focus:ring focus:ring-indigo-200 transition text-sm font-medium">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-2">Modal (Rp) <span class="text-red-500">*</span></label>
                            <input type="number" name="base_price" required placeholder="0" 
                                   class="w-full px-3 py-2.5 rounded-lg border-slate-300 focus:border-indigo-500 focus:ring focus:ring-indigo-200 transition text-sm font-bold text-slate-600 bg-slate-50">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-emerald-600 uppercase tracking-wide mb-2">Jual (Rp) <span class="text-red-500">*</span></label>
                            <input type="number" name="sell_price" required placeholder="0" 
                                   class="w-full px-3 py-2.5 rounded-lg border-emerald-300 focus:border-emerald-500 focus:ring focus:ring-emerald-200 transition text-sm font-bold text-emerald-700 bg-emerald-50">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-2">Satuan <span class="text-red-500">*</span></label>
                        <select name="unit" class="w-full px-4 py-2.5 rounded-lg border-slate-300 focus:border-indigo-500 focus:ring focus:ring-indigo-200 transition text-sm bg-white">
                            <option value="pcs">Pcs</option>
                            <option value="lembar">Lembar</option>
                            <option value="meter">Meter</option>
                            <option value="box">Box</option>
                            <option value="paket">Paket</option>
                        </select>
                    </div>

                    <button type="submit" class="w-full py-3 bg-slate-800 hover:bg-slate-900 text-white rounded-lg font-bold shadow-md transition-all flex items-center justify-center gap-2">
                        <i class="fas fa-save"></i> Simpan Produk
                    </button>
                </form>
            </div>
        </div>

        <div class="lg:col-span-2">
            <div class="bg-white rounded-lg shadow-sm border border-slate-200 overflow-hidden">
                <div class="px-6 py-5 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
                    <h2 class="font-bold text-slate-700 flex items-center gap-2"><i class="fas fa-list text-slate-400"></i> Daftar Produk</h2>
                    <span class="text-xs font-bold bg-white border border-slate-200 px-3 py-1 rounded-full text-slate-500">{{ count($products) }} Item</span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="bg-slate-50 text-slate-500 uppercase text-[10px] font-bold tracking-wider border-b border-slate-200">
                            <tr>
                                <th class="px-6 py-3 w-10 text-center">No</th>
                                <th class="px-6 py-3">Produk</th>
                                <th class="px-6 py-3 text-right">Harga Modal</th>
                                <th class="px-6 py-3 text-right">Harga Jual</th>
                                <th class="px-6 py-3 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($products as $product)
                            <tr class="hover:bg-slate-50 transition group">
                                <td class="px-6 py-4 text-center text-slate-400 font-medium">{{ $loop->iteration }}</td>
                                <td class="px-6 py-4">
                                    <div class="font-bold text-slate-700 group-hover:text-indigo-600 transition-colors">{{ $product->name }}</div>
                                    <div class="text-[10px] text-slate-400 font-bold uppercase tracking-wider mt-0.5">Per {{ $product->unit }}</div>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <span class="text-slate-500">Rp {{ number_format($product->base_price, 0, ',', '.') }}</span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <span class="font-black text-emerald-600">Rp {{ number_format($product->sell_price, 0, ',', '.') }}</span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        <a href="{{ route('products.show', $product->id) }}" class="h-8 w-8 rounded-full bg-white border border-slate-200 text-slate-400 hover:text-blue-500 hover:border-blue-200 hover:bg-blue-50 transition-all flex items-center justify-center shadow-sm">
                                            <i class="fas fa-eye text-xs"></i>
                                        </a>
                                        <a href="{{ route('products.edit', $product->id) }}" class="h-8 w-8 rounded-full bg-white border border-slate-200 text-slate-400 hover:text-amber-500 hover:border-amber-200 hover:bg-amber-50 transition-all flex items-center justify-center shadow-sm">
                                            <i class="fas fa-pencil-alt text-xs"></i>
                                        </a>
                                        <form action="{{ route('products.destroy', $product->id) }}" method="POST" onsubmit="return confirm('Hapus produk ini?');">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="h-8 w-8 rounded-full bg-white border border-slate-200 text-slate-400 hover:text-red-500 hover:border-red-200 hover:bg-red-50 transition-all flex items-center justify-center shadow-sm">
                                                <i class="fas fa-trash-alt text-xs"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="5" class="text-center py-8 text-slate-400">Belum ada data.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection