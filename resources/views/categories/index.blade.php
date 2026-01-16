@extends('layouts.app')

@section('title', 'Kelola Kategori')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-slate-800">Kategori Produk</h1>
        <a href="{{ route('products.index') }}" class="text-sm text-indigo-600 hover:underline">&larr; Kembali ke Produk</a>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        {{-- FORM TAMBAH KATEGORI --}}
        <div class="md:col-span-1">
            <div class="bg-white p-5 rounded-xl shadow-sm border border-slate-200">
                <h3 class="font-bold text-slate-700 mb-4">Tambah Baru</h3>

                <form action="{{ route('categories.store') }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Nama Kategori</label>
                        <input type="text" name="name" required placeholder="Contoh: Laundry Kiloan"
                               class="w-full px-3 py-2 rounded-lg border-slate-300 text-sm focus:ring-indigo-500 focus:border-indigo-500">
                    </div>
                    <div class="mb-4">
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Deskripsi (Opsional)</label>
                        <textarea name="description" rows="2" placeholder="Keterangan singkat..."
                                  class="w-full px-3 py-2 rounded-lg border-slate-300 text-sm focus:ring-indigo-500 focus:border-indigo-500"></textarea>
                    </div>
                    <button type="submit" class="w-full py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-bold transition">
                        Simpan
                    </button>
                </form>
            </div>
        </div>

        {{-- TABEL DAFTAR KATEGORI --}}
        <div class="md:col-span-2">
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                <table class="w-full text-sm text-left">
                    <thead class="bg-slate-50 text-slate-500 font-bold border-b border-slate-200">
                        <tr>
                            <th class="px-4 py-3">Nama Kategori</th>
                            <th class="px-4 py-3">Slug</th>
                            <th class="px-4 py-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($categories as $cat)
                        <tr class="hover:bg-slate-50">
                            <td class="px-4 py-3 font-medium text-slate-700">{{ $cat->name }}</td>
                            <td class="px-4 py-3 text-slate-400 italic">{{ $cat->slug }}</td>
                            <td class="px-4 py-3 text-right">
                                <form action="{{ route('categories.destroy', $cat->id) }}" method="POST" onsubmit="return confirm('Hapus kategori ini?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-500 hover:text-red-700 text-xs font-bold">Hapus</button>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                <div class="p-3">
                    {{ $categories->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
