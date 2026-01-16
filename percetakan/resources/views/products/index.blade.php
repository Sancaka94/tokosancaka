@extends('layouts.app')

@section('title', 'Manajemen Produk')

@section('content')

    <div class="flex flex-col md:flex-row justify-between items-start md:items-end mb-6 gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-800 tracking-tight">Manajemen Produk</h1>
            <p class="text-slate-500 font-medium text-sm">Kelola stok, harga modal, harga jual, dan supplier.</p>
        </div>

        <div class="flex flex-col sm:flex-row gap-3 w-full md:w-auto">
            <form action="{{ route('products.index') }}" method="GET" class="relative w-full sm:w-64">
                <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400">
                    <i class="fas fa-search"></i>
                </span>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari produk / supplier..."
                       class="w-full pl-10 pr-4 py-2.5 rounded-lg border-slate-200 focus:ring-indigo-500 focus:border-indigo-500 text-sm">
            </form>

            <a href="{{ route('orders.create') }}"
               class="flex items-center justify-center gap-2 px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-bold shadow-md transition-all transform hover:-translate-y-0.5 whitespace-nowrap">
                <i class="fas fa-cash-register"></i>
                <span>Buka Kasir</span>
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

        {{-- BAGIAN FORM --}}
        <div class="lg:col-span-1">
            <div class="bg-white p-6 rounded-lg shadow-sm border border-slate-200 sticky top-24">

                <div class="flex items-center justify-between mb-6 pb-4 border-b border-slate-100">
                    <div class="flex items-center gap-3">
                        <div class="h-10 w-10 rounded-lg bg-indigo-50 flex items-center justify-center text-indigo-600">
                            <i class="fas fa-plus"></i>
                        </div>
                        <div>
                            <h2 class="text-lg font-bold text-slate-800">Produk</h2>
                            <p class="text-xs text-slate-400">Input data baru</p>
                        </div>
                    </div>
                    {{-- TOMBOL TAMBAH KATEGORI --}}
                    {{-- Pastikan route 'categories.index' ada, atau ganti '#' dengan route yang sesuai --}}
                    <a href="{{ route('categories.index') ?? '#' }}" class="text-xs font-bold text-indigo-600 hover:text-indigo-800 hover:underline">
                        + Kategori
                    </a>
                </div>

                @if ($errors->any())
                    <div class="mb-4 bg-red-50 border-l-4 border-red-500 p-4 rounded-r shadow-sm">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <i class="fas fa-exclamation-circle text-red-500"></i>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-bold text-red-800">Gagal Menyimpan Data</h3>
                                <ul class="mt-2 text-xs text-red-700 list-disc list-inside">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    </div>
                @endif

                @if (session('success'))
                    <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 3000)"
                         class="mb-4 bg-green-50 border-l-4 border-green-500 p-4 rounded-r shadow-sm flex items-center gap-3">
                        <i class="fas fa-check-circle text-green-500"></i>
                        <p class="text-sm text-green-700 font-bold">{{ session('success') }}</p>
                    </div>
                @endif

                {{-- FORM START --}}
                {{-- Kita tambahkan logic AlpineJS untuk menghandle Laundry --}}
                <form action="{{ route('products.store') }}" method="POST" enctype="multipart/form-data" class="space-y-4"
                    x-data="{
                        submitting: false,
                        imgPreview: null,

                        // State Dinamis (Diambil dari Database)
                        isService: false,      // Apakah ini jasa? (Jika ya, stok & modal hilang)
                        presets: [],           // Daftar menu dropdown
                        unit: 'pcs',           // Satuan default

                        // Logic saat memilih kategori
                        handleCategoryChange(event) {
                            const option = event.target.options[event.target.selectedIndex];

                            // Ambil data logic dari atribut data- HTML
                            this.isService = option.dataset.type === 'service';
                            this.unit = option.dataset.unit || 'pcs';

                            // Ambil JSON Presets (Menu Dropdown)
                            const rawPresets = option.dataset.presets;
                            this.presets = rawPresets ? JSON.parse(rawPresets) : [];
                        }
                    }"
                    @submit="submitting = true">

                    @csrf

                    {{-- Upload Gambar (Tetap) --}}
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Foto Produk</label>
                        <div class="flex items-center gap-4">
                            <div class="h-16 w-16 rounded-lg border-2 border-dashed border-slate-300 flex items-center justify-center bg-slate-50 overflow-hidden shrink-0">
                                <template x-if="imgPreview"><img :src="imgPreview" class="h-full w-full object-cover"></template>
                                <template x-if="!imgPreview"><i class="fas fa-image text-slate-300 text-xl"></i></template>
                            </div>
                            <div class="flex-1">
                                <input type="file" name="image" accept="image/*" class="block w-full text-xs text-slate-500"
                                    @change="imgPreview = URL.createObjectURL($event.target.files[0])">
                            </div>
                        </div>
                    </div>

                    {{-- KATEGORI (OTOMATIS MEMBAWA LOGIKA DB) --}}
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Kategori <span class="text-red-500">*</span></label>
                        <select name="category_id" required @change="handleCategoryChange($event)"
                                class="w-full px-3 py-2.5 rounded-lg border-slate-300 bg-white focus:ring-indigo-500 transition text-sm">
                            <option value="" data-type="physical" data-presets="[]">-- Pilih Kategori --</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}"
                                        data-type="{{ $cat->type }}"
                                        data-unit="{{ $cat->default_unit }}"
                                        data-presets="{{ $cat->product_presets }}"> {{ $cat->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- NAMA PRODUK (BERUBAH OTOMATIS JADI DROPDOWN JIKA ADA PRESETS) --}}
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Nama Produk / Layanan <span class="text-red-500">*</span></label>

                        {{-- Tampil jika TIDAK ada presets (Mode Ketik Manual) --}}
                        <input type="text" name="name" x-show="presets.length === 0" :required="presets.length === 0"
                            placeholder="Nama Produk..."
                            class="w-full px-3 py-2.5 rounded-lg border-slate-300 focus:ring-indigo-500 transition text-sm font-medium">

                        {{-- Tampil jika ADA presets (Mode Pilih Menu Laundry/Jasa) --}}
                        <select name="name" x-show="presets.length > 0" :required="presets.length > 0" style="display: none;"
                                class="w-full px-3 py-2.5 rounded-lg border-slate-300 bg-indigo-50 text-indigo-700 font-bold focus:ring-indigo-500 transition text-sm">
                            <option value="">-- Pilih Layanan --</option>
                            <template x-for="p in presets">
                                <option :value="p" x-text="p"></option>
                            </template>
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        {{-- MODAL (HILANG JIKA SERVICE) --}}
                        <div x-show="!isService">
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Modal (Rp)</label>
                            <input type="number" name="base_price" :value="isService ? 0 : ''" placeholder="0"
                                class="w-full px-3 py-2.5 rounded-lg border-slate-300 bg-slate-50 transition text-sm">
                        </div>

                        {{-- HARGA JUAL (SELALU ADA) --}}
                        <div :class="isService ? 'col-span-2' : ''">
                            <label class="block text-xs font-bold text-emerald-600 uppercase mb-1">Jual (Rp) <span class="text-red-500">*</span></label>
                            <input type="number" name="sell_price" required placeholder="0"
                                class="w-full px-3 py-2.5 rounded-lg border-emerald-300 bg-emerald-50 text-emerald-700 font-bold transition text-sm">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        {{-- STOK (HILANG JIKA SERVICE, OTOMATIS 10000) --}}
                        <div x-show="!isService">
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Stok Awal</label>
                            <input type="number" name="stock" :value="isService ? 10000 : ''" placeholder="0"
                                class="w-full px-3 py-2.5 rounded-lg border-slate-300 transition text-sm">
                        </div>

                        {{-- SATUAN (OTOMATIS BERUBAH SESUAI KATEGORI) --}}
                        <div :class="isService ? 'col-span-2' : ''">
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Satuan <span class="text-red-500">*</span></label>
                            <select name="unit" x-model="unit" class="w-full px-3 py-2.5 rounded-lg border-slate-300 bg-white text-sm">
                                <option value="pcs">Pcs</option>
                                <option value="kg">Kg</option>
                                <option value="lembar">Lembar</option>
                                <option value="box">Box</option>
                                <option value="paket">Paket</option>
                                <option value="meter">Meter</option>
                            </select>
                        </div>
                    </div>

                    {{-- SUPPLIER (HILANG JIKA SERVICE) --}}
                    <div x-show="!isService">
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Supplier</label>
                        <input type="text" name="supplier" placeholder="Contoh: PT. Sinar Dunia"
                            class="w-full px-3 py-2.5 rounded-lg border-slate-300 transition text-sm">
                    </div>

                    <button type="submit" class="w-full py-3 bg-slate-800 hover:bg-slate-900 text-white rounded-lg font-bold mt-4" :disabled="submitting">
                        <span x-show="!submitting"><i class="fas fa-save"></i> Simpan Data</span>
                        <span x-show="submitting"><i class="fas fa-spinner fa-spin"></i> Menyimpan...</span>
                    </button>
                </form>
            </div>
        </div>

        {{-- BAGIAN TABEL (Kanan) --}}
        <div class="lg:col-span-2">
            <div class="bg-white rounded-lg shadow-sm border border-slate-200 overflow-hidden">
                <div class="px-6 py-5 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
                    <h2 class="font-bold text-slate-700 flex items-center gap-2">
                        <i class="fas fa-boxes text-slate-400"></i> Inventaris Barang
                    </h2>
                    <span class="text-xs font-bold bg-white border border-slate-200 px-3 py-1 rounded-full text-slate-500 shadow-sm">
                        {{ $products->total() }} Item
                    </span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="bg-slate-50 text-slate-500 uppercase text-[10px] font-bold tracking-wider border-b border-slate-200">
                            <tr>
                                <th class="px-6 py-3 w-16">Img</th>
                                <th class="px-6 py-3">Produk / Kategori</th>
                                <th class="px-6 py-3 text-center">Stok</th>
                                <th class="px-6 py-3 text-right">Harga (Modal / Jual)</th>
                                <th class="px-6 py-3 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($products as $product)
                            <tr class="hover:bg-slate-50 transition group">
                                <td class="px-6 py-4">
                                    <div class="h-10 w-10 rounded bg-slate-100 border border-slate-200 flex items-center justify-center overflow-hidden">
                                        @if($product->image)
                                            <img src="{{ asset('storage/' . $product->image) }}" alt="{{ $product->name }}" class="h-full w-full object-cover">
                                        @else
                                            <i class="fas fa-box text-slate-300 text-lg"></i>
                                        @endif
                                    </div>
                                </td>

                                <td class="px-6 py-4">
                                    <div class="font-bold text-slate-700 group-hover:text-indigo-600 transition-colors">
                                        {{ $product->name }}
                                    </div>
                                    <div class="flex items-center gap-2 mt-1">
                                        {{-- Tampilkan Kategori --}}
                                        @if(isset($product->category))
                                            <span class="text-[10px] bg-indigo-50 text-indigo-600 px-1.5 py-0.5 rounded border border-indigo-100 uppercase font-bold">
                                                {{ $product->category->name }}
                                            </span>
                                        @endif
                                        <span class="text-[10px] bg-slate-100 px-1.5 py-0.5 rounded text-slate-500 border border-slate-200 uppercase">
                                            {{ $product->unit }}
                                        </span>
                                    </div>
                                    @if($product->supplier)
                                    <div class="text-[10px] text-slate-400 mt-1 flex items-center gap-1">
                                        <i class="fas fa-truck text-[9px]"></i> {{ $product->supplier }}
                                    </div>
                                    @endif
                                </td>

                                <td class="px-6 py-4 text-center">
                                    @if($product->stock <= 5)
                                        <span class="inline-flex items-center gap-1 px-2 py-1 bg-red-100 text-red-600 rounded text-xs font-bold border border-red-200">
                                            {{ $product->stock }} (Tipis)
                                        </span>
                                    @else
                                        <span class="font-bold text-slate-700">{{ $product->stock }}</span>
                                    @endif
                                    <div class="text-[10px] text-slate-400 mt-1">Terjual: {{ $product->sold }}</div>
                                </td>

                                <td class="px-6 py-4 text-right">
                                    <div class="font-black text-emerald-600">
                                        Rp {{ number_format($product->sell_price, 0, ',', '.') }}
                                    </div>
                                    <div class="text-xs text-slate-400 mt-0.5">
                                        Modal: Rp {{ number_format($product->base_price, 0, ',', '.') }}
                                    </div>
                                </td>

                                <td class="px-6 py-4 text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        <a href="{{ route('products.edit', $product->id) }}"
                                           class="h-8 w-8 rounded-full bg-white border border-slate-200 text-slate-400 hover:text-amber-500 hover:border-amber-200 hover:bg-amber-50 transition-all flex items-center justify-center shadow-sm"
                                           title="Edit Produk">
                                            <i class="fas fa-pencil-alt text-xs"></i>
                                        </a>

                                        <form action="{{ route('products.destroy', $product->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus produk {{ $product->name }}?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    class="h-8 w-8 rounded-full bg-white border border-slate-200 text-slate-400 hover:text-red-500 hover:border-red-200 hover:bg-red-50 transition-all flex items-center justify-center shadow-sm"
                                                    title="Hapus Produk">
                                                <i class="fas fa-trash-alt text-xs"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="px-6 py-12 text-center">
                                    <div class="flex flex-col items-center justify-center text-slate-400">
                                        <i class="fas fa-search text-4xl mb-3 opacity-20"></i>
                                        <p class="font-medium">Data produk tidak ditemukan.</p>
                                    </div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="px-6 py-4 border-t border-slate-100 bg-slate-50/30">
                    {{ $products->withQueryString()->links() }}
                </div>
            </div>
        </div>
    </div>
@endsection
