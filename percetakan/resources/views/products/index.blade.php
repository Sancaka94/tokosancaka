@extends('layouts.app')

@section('title', 'Manajemen Produk')

@section('content')
    
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-800 tracking-tight">Manajemen Produk</h1>
            <p class="text-slate-500 font-medium text-sm">Kelola stok, supplier, dan harga produk.</p>
        </div>
        <a href="{{ route('orders.create') }}" class="flex items-center gap-2 px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-bold shadow-md transition-all transform hover:-translate-y-0.5">
            <i class="fas fa-cash-register"></i> <span>Buka Kasir</span>
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
                        <p class="text-xs text-slate-400">Input data stok & supplier baru</p>
                    </div>
                </div>

                <form action="{{ route('products.store') }}" method="POST" class="space-y-4" 
      x-data="{ submitting: false }" 
      @submit="submitting = true">
    
    @csrf
    
    <button type="submit" 
            class="w-full py-3 bg-slate-800 hover:bg-slate-900 text-white rounded-lg font-bold shadow-md transition flex items-center justify-center gap-2 mt-4 disabled:opacity-50 disabled:cursor-not-allowed"
            :disabled="submitting">
        
        <span x-show="!submitting" class="flex items-center gap-2">
            <i class="fas fa-save"></i> Simpan Data
        </span>

        <span x-show="submitting" class="flex items-center gap-2" style="display: none;">
            <i class="fas fa-spinner fa-spin"></i> Menyimpan...
        </span>
        
    </button>
</form>
            </div>
        </div>

        <div class="lg:col-span-2">
            <div class="bg-white rounded-lg shadow-sm border border-slate-200 overflow-hidden">
                <div class="px-6 py-5 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
                    <h2 class="font-bold text-slate-700 flex items-center gap-2"><i class="fas fa-boxes text-slate-400"></i> Inventaris Barang</h2>
                    <span class="text-xs font-bold bg-white border border-slate-200 px-3 py-1 rounded-full text-slate-500">{{ count($products) }} Item</span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="bg-slate-50 text-slate-500 uppercase text-[10px] font-bold tracking-wider border-b border-slate-200">
                            <tr>
                                <th class="px-6 py-3">Produk & Supplier</th>
                                <th class="px-6 py-3 text-center">Stok</th>
                                <th class="px-6 py-3 text-center">Terjual</th>
                                <th class="px-6 py-3 text-right">Harga Jual</th>
                                <th class="px-6 py-3 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($products as $product)
                            <tr class="hover:bg-slate-50 transition group">
                                <td class="px-6 py-4">
                                    <div class="font-bold text-slate-700 group-hover:text-indigo-600 transition-colors">{{ $product->name }}</div>
                                    <div class="flex items-center gap-1 mt-1 text-[10px] text-slate-400 uppercase tracking-wider">
                                        <i class="fas fa-truck text-xs"></i> {{ $product->supplier ?? '-' }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    @if($product->stock <= 5)
                                        <span class="inline-block px-2 py-1 bg-red-100 text-red-600 rounded text-xs font-bold">
                                            {{ $product->stock }} {{ $product->unit }} (Tipis)
                                        </span>
                                    @else
                                        <span class="font-bold text-slate-600">{{ $product->stock }}</span> <span class="text-xs text-slate-400">{{ $product->unit }}</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="font-bold text-slate-700">{{ $product->sold }}</span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <span class="font-black text-emerald-600">Rp {{ number_format($product->sell_price, 0, ',', '.') }}</span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        <a href="{{ route('products.show', $product->id) }}" class="h-8 w-8 rounded-full border border-slate-200 text-slate-400 hover:text-blue-500 hover:bg-blue-50 flex items-center justify-center"><i class="fas fa-eye text-xs"></i></a>
                                        <a href="{{ route('products.edit', $product->id) }}" class="h-8 w-8 rounded-full border border-slate-200 text-slate-400 hover:text-amber-500 hover:bg-amber-50 flex items-center justify-center"><i class="fas fa-pencil-alt text-xs"></i></a>
                                        <form action="{{ route('products.destroy', $product->id) }}" method="POST" onsubmit="return confirm('Hapus produk ini?');">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="h-8 w-8 rounded-full border border-slate-200 text-slate-400 hover:text-red-500 hover:bg-red-50 flex items-center justify-center"><i class="fas fa-trash-alt text-xs"></i></button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="5" class="text-center py-8 text-slate-400">Belum ada data inventaris.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                
                <div class="px-6 py-4 border-t border-slate-100 bg-slate-50/30">
                    {{ $products->links() }}
                </div>
            </div>
        </div>
    </div>
@endsection