<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Produk - Sancaka POS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="bg-slate-50 font-sans" x-data="{ sidebarOpen: false }">

    <div class="flex h-screen overflow-hidden">
        @include('layouts.partials.sidebar')

        <div class="flex-1 flex flex-col overflow-y-auto">
            @include('layouts.partials.header')

            <main class="p-6">
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
                    <div>
                        <h1 class="text-2xl font-black text-slate-800 italic">MANAJEMEN PRODUK</h1>
                        <p class="text-slate-500 text-sm">Kelola layanan percetakan dan stok barang Anda.</p>
                    </div>
                    <a href="{{ route('orders.create') }}" class="px-6 py-3 bg-indigo-600 text-white rounded-2xl font-bold shadow-lg shadow-indigo-200 hover:bg-indigo-700 transition">Buka Kasir →</a>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <div class="bg-white p-6 rounded-[32px] shadow-sm border border-slate-100 h-fit sticky top-24">
                        <h2 class="text-lg font-bold text-slate-800 mb-6 flex items-center gap-2">
                            <span class="bg-indigo-100 p-2 rounded-lg text-indigo-600">➕</span> Tambah Produk
                        </h2>
                        <form action="{{ route('products.store') }}" method="POST" class="space-y-4">
                            @csrf
                            <div>
                                <label class="block text-xs font-bold text-slate-400 uppercase mb-1">Nama Layanan/Produk</label>
                                <input type="text" name="name" required placeholder="Contoh: Cetak Spanduk" class="w-full rounded-xl border-slate-200 focus:ring-indigo-500 focus:border-indigo-500 p-3">
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-bold text-slate-400 uppercase mb-1">Harga (Rp)</label>
                                    <input type="number" name="base_price" required placeholder="0" class="w-full rounded-xl border-slate-200 p-3">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-400 uppercase mb-1">Satuan</label>
                                    <select name="unit" class="w-full rounded-xl border-slate-200 p-3 bg-white">
                                        <option value="meter">Meter</option>
                                        <option value="lembar">Lembar</option>
                                        <option value="pcs">Pcs</option>
                                        <option value="box">Box</option>
                                    </select>
                                </div>
                            </div>
                            <button type="submit" class="w-full py-4 bg-slate-900 text-white rounded-2xl font-bold hover:bg-black transition shadow-xl">Simpan ke POS</button>
                        </form>
                    </div>

                    <div class="lg:col-span-2 bg-white rounded-[32px] shadow-sm border border-slate-100 overflow-hidden">
                        <div class="p-6 border-b border-slate-50 flex justify-between items-center">
                            <h2 class="font-bold text-slate-800 uppercase tracking-widest text-sm">Daftar Layanan Aktif</h2>
                            <span class="text-xs font-bold bg-slate-100 px-3 py-1 rounded-full text-slate-500">{{ count($products) }} Produk</span>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead class="bg-slate-50/50">
                                    <tr>
                                        <th class="px-6 py-4 text-left text-xs font-bold text-slate-400 uppercase italic">Produk</th>
                                        <th class="px-6 py-4 text-left text-xs font-bold text-slate-400 uppercase italic">Harga</th>
                                        <th class="px-6 py-4 text-center text-xs font-bold text-slate-400 uppercase italic">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    @foreach($products as $product)
                                    <tr class="hover:bg-slate-50/50 transition">
                                        <td class="px-6 py-4">
                                            <div class="font-bold text-slate-800">{{ $product->name }}</div>
                                            <div class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">{{ $product->unit }}</div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="font-black text-indigo-600">Rp {{ number_format($product->base_price, 0, ',', '.') }}</span>
                                        </td>
                                        <td class="px-6 py-4 text-center">
                                            <form action="{{ route('products.destroy', $product->id) }}" method="POST" onsubmit="return confirm('Hapus produk ini?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-300 hover:text-red-600 transition">
                                                    🗑️
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>

            @include('layouts.partials.footer')
        </div>
    </div>
</body>
</html>