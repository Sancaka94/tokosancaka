@extends('layouts.app')

@section('title', 'Manajemen Produk')

@section('content')

{{-- 1. LOAD LIBRARY VISUAL BARCODE --}}
<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>

<div class="space-y-6" x-data="productManager()">

    {{-- HEADER & TOMBOL AKSI UTAMA --}}
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <h1 class="text-2xl font-black text-slate-800">Manajemen Produk</h1>
            <p class="text-slate-500 text-sm">Kelola stok, varian, harga modal, dan supplier Anda.</p>
        </div>
        <div class="flex gap-2">
            {{-- Tombol Cetak PDF (Menggunakan Filter Saat Ini) --}}
            <div x-data="{ openPdfModal: false }">

            <button @click="openPdfModal = true"
            class="flex items-center gap-2 px-4 py-2.5 bg-red-600 hover:bg-red-700 text-white rounded-xl font-bold shadow-lg shadow-red-200 transition-all transform hover:-translate-y-0.5">
                <i class="fas fa-file-pdf"></i>
                <span>Cetak PDF</span>
            </button>

            <div x-show="openPdfModal" style="display: none;"
                class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-sm p-4"
                x-transition.opacity>

                <div class="bg-white w-full max-w-md rounded-2xl shadow-2xl overflow-hidden"
                    @click.outside="openPdfModal = false"
                    x-transition.scale>

                    <div class="bg-slate-50 px-6 py-4 border-b border-slate-100 flex justify-between items-center">
                        <h3 class="font-bold text-lg text-slate-800">
                            <i class="fas fa-print text-red-600 mr-2"></i> Filter Cetak PDF
                        </h3>
                        <button @click="openPdfModal = false" class="text-slate-400 hover:text-red-500 transition">
                            <i class="fas fa-times text-xl"></i>
                        </button>
                    </div>

                    <form action="{{ route('products.downloadPdf') }}" method="GET" target="_blank" @submit="openPdfModal = false">
                        <div class="p-6 space-y-4">

                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-2">Pilih Kategori</label>
                                <div class="relative">
                                    <select name="category_id" class="w-full bg-slate-50 border border-slate-200 text-slate-700 text-sm rounded-lg focus:ring-red-500 focus:border-red-500 block p-2.5 appearance-none">
                                        <option value="all" selected>Semua Kategori</option>

                                        {{-- Ambil data kategori langsung (jika $categories tidak dipassing dari controller) --}}
                                        @php $categories = \App\Models\Category::all(); @endphp
                                        @foreach($categories as $cat)
                                            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                        @endforeach
                                    </select>
                                    <div class="absolute inset-y-0 right-0 flex items-center px-2 pointer-events-none text-slate-500">
                                        <i class="fas fa-chevron-down text-xs"></i>
                                    </div>
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-2">Jenis Produk</label>
                                <div class="grid grid-cols-3 gap-2">
                                    <label class="cursor-pointer">
                                        <input type="radio" name="type" value="all" checked class="peer sr-only">
                                        <div class="p-2 text-center rounded-lg border border-slate-200 bg-white text-slate-600 hover:bg-slate-50 peer-checked:bg-red-50 peer-checked:text-red-600 peer-checked:border-red-200 transition text-xs font-bold">
                                            Semua
                                        </div>
                                    </label>

                                    <label class="cursor-pointer">
                                        <input type="radio" name="type" value="single" class="peer sr-only">
                                        <div class="p-2 text-center rounded-lg border border-slate-200 bg-white text-slate-600 hover:bg-slate-50 peer-checked:bg-red-50 peer-checked:text-red-600 peer-checked:border-red-200 transition text-xs font-bold">
                                            Tunggal
                                        </div>
                                    </label>

                                    <label class="cursor-pointer">
                                        <input type="radio" name="type" value="variant" class="peer sr-only">
                                        <div class="p-2 text-center rounded-lg border border-slate-200 bg-white text-slate-600 hover:bg-slate-50 peer-checked:bg-indigo-50 peer-checked:text-indigo-600 peer-checked:border-indigo-200 transition text-xs font-bold">
                                            Varian
                                        </div>
                                    </label>
                                </div>
                            </div>

                        </div>

                        <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex justify-end gap-2">
                            <button type="button" @click="openPdfModal = false" class="px-4 py-2 text-sm font-bold text-slate-500 hover:text-slate-700 bg-white border border-slate-200 hover:bg-slate-50 rounded-lg transition">
                                Batal
                            </button>
                            <button type="submit" class="px-4 py-2 text-sm font-bold text-white bg-red-600 hover:bg-red-700 rounded-lg shadow-lg shadow-red-200 transition flex items-center gap-2">
                                <i class="fas fa-download"></i> Download PDF
                            </button>
                        </div>
                    </form>

                </div>
            </div>
        </div>

            <a href="{{ route('orders.create') }}" class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl font-bold shadow-lg shadow-indigo-200 transition-all flex items-center gap-2">
                <i class="fas fa-cash-register"></i> <span>Buka Kasir</span>
            </a>
        </div>
    </div>

    {{-- CARD FILTER & PENCARIAN --}}
    <div class="bg-white p-4 rounded-2xl shadow-sm border border-slate-200">
        <form method="GET" action="{{ route('products.index') }}" class="flex flex-col lg:flex-row gap-4 items-center justify-between">

            {{-- BAGIAN KIRI: FILTER KATEGORI & JENIS --}}
            <div class="flex flex-col sm:flex-row w-full lg:w-auto gap-3">

                {{-- 1. Filter Kategori --}}
                <div class="relative min-w-[200px]">
                    <select name="category_id" onchange="this.form.submit()"
                            class="w-full pl-3 pr-10 py-2.5 bg-slate-50 border border-slate-200 text-slate-700 text-sm rounded-xl focus:ring-red-500 focus:border-red-500 appearance-none font-bold cursor-pointer hover:bg-slate-100 transition">
                        <option value="all">📂 Semua Kategori</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}" {{ request('category_id') == $cat->id ? 'selected' : '' }}>
                                {{ $cat->name }}
                            </option>
                        @endforeach
                    </select>
                    <div class="absolute inset-y-0 right-0 flex items-center px-3 pointer-events-none text-slate-500">
                        <i class="fas fa-chevron-down text-xs"></i>
                    </div>
                </div>

                {{-- 2. Filter Jenis (Tombol Group) --}}
                <div class="flex bg-slate-100 p-1 rounded-xl border border-slate-200">
                    {{-- Tombol Semua --}}
                    <label class="cursor-pointer">
                        <input type="radio" name="type" value="all" class="sr-only peer" onchange="this.form.submit()" {{ request('type', 'all') == 'all' ? 'checked' : '' }}>
                        <span class="px-4 py-1.5 rounded-lg text-xs font-bold block transition-all
                                     text-slate-500 hover:text-slate-700
                                     peer-checked:bg-white peer-checked:text-slate-800 peer-checked:shadow-sm">
                            Semua
                        </span>
                    </label>

                    {{-- Tombol Tunggal --}}
                    <label class="cursor-pointer">
                        <input type="radio" name="type" value="single" class="sr-only peer" onchange="this.form.submit()" {{ request('type') == 'single' ? 'checked' : '' }}>
                        <span class="px-4 py-1.5 rounded-lg text-xs font-bold block transition-all
                                     text-slate-500 hover:text-slate-700
                                     peer-checked:bg-white peer-checked:text-emerald-600 peer-checked:shadow-sm">
                            Single
                        </span>
                    </label>

                    {{-- Tombol Varian --}}
                    <label class="cursor-pointer">
                        <input type="radio" name="type" value="variant" class="sr-only peer" onchange="this.form.submit()" {{ request('type') == 'variant' ? 'checked' : '' }}>
                        <span class="px-4 py-1.5 rounded-lg text-xs font-bold block transition-all
                                     text-slate-500 hover:text-slate-700
                                     peer-checked:bg-white peer-checked:text-indigo-600 peer-checked:shadow-sm">
                            Varian
                        </span>
                    </label>
                </div>
            </div>

            {{-- BAGIAN KANAN: PENCARIAN --}}
            <div class="w-full lg:w-auto flex-1 max-w-md relative group">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <i class="fas fa-search text-slate-400 group-focus-within:text-red-500 transition-colors"></i>
                </div>
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="Cari nama, SKU, atau barcode..."
                       class="block w-full pl-10 pr-4 py-2.5 bg-white border border-slate-200 text-slate-700 placeholder-slate-400 focus:border-red-500 focus:ring-red-500 rounded-xl text-sm font-medium transition-all shadow-sm group-focus-within:shadow-md">

                {{-- Tombol Submit Search (Hidden tapi perlu buat Enter) --}}
                <button type="submit" class="hidden"></button>
            </div>

        </form>
    </div>

    {{-- LAYOUT GRID 2 KOLOM (KIRI: FORM, KANAN: TABEL) --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 items-start">

        {{-- === BAGIAN KIRI: FORM TAMBAH PRODUK === --}}
        <div class="lg:col-span-1 sticky top-6">
            <div class="bg-white p-6 rounded-2xl shadow-lg border border-slate-100/50">

                <div class="flex items-center justify-between mb-6 pb-4 border-b border-slate-50">
                    <div class="flex items-center gap-3">
                        <div class="h-10 w-10 rounded-xl bg-indigo-50 flex items-center justify-center text-indigo-600 shadow-sm">
                            <i class="fas fa-plus-circle text-lg"></i>
                        </div>
                        <div>
                            <h2 class="text-lg font-black text-slate-800">Input Produk</h2>
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Data Baru</p>
                        </div>
                    </div>
                    <a href="{{ route('categories.index') }}" class="text-xs font-bold text-indigo-600 hover:text-indigo-800 bg-indigo-50 px-3 py-1.5 rounded-lg hover:bg-indigo-100 transition-colors">
                        + Kategori
                    </a>
                </div>

                {{-- ALERT ERROR --}}
                @if ($errors->any())
                    <div class="mb-4 bg-red-50 border border-red-100 p-4 rounded-xl">
                        <div class="flex gap-3">
                            <i class="fas fa-exclamation-triangle text-red-500 mt-0.5"></i>
                            <div>
                                <h3 class="text-sm font-bold text-red-800">Gagal Menyimpan</h3>
                                <ul class="mt-1 text-xs text-red-600 list-disc list-inside">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- ALERT SUKSES --}}
                @if (session('success'))
                    <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)"
                         class="mb-4 bg-emerald-50 border border-emerald-100 p-4 rounded-xl flex items-center gap-3">
                        <i class="fas fa-check-circle text-emerald-500 text-lg"></i>
                        <div>
                            <p class="text-sm text-emerald-800 font-bold">Berhasil!</p>
                            <p class="text-xs text-emerald-600">{{ session('success') }}</p>
                        </div>
                    </div>
                @endif

                {{-- FORM START --}}
                <form action="{{ route('products.store') }}" method="POST" enctype="multipart/form-data" class="space-y-5"
                    x-data="{
                        submitting: false,
                        imgPreview: null,
                        isService: false,
                        presets: [],
                        unit: 'pcs',

                        handleCategoryChange(event) {
                        const option = event.target.options[event.target.selectedIndex];
                        this.isService = option.dataset.type === 'service';
                        this.unit = option.dataset.unit || 'pcs';
                        const rawPresets = option.dataset.presets;

                        if (rawPresets && rawPresets !== 'null') {
                            this.presets = JSON.parse(rawPresets);
                                } else {
                                    this.presets = [];
                                }
                        }
                    }"
                    @submit="submitting = true">

                    @csrf

                    {{-- Upload Gambar --}}
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase mb-2 tracking-wider">Foto Produk</label>
                        <div class="flex items-center gap-4 group">
                            <div class="h-16 w-16 rounded-xl border-2 border-dashed border-slate-300 flex items-center justify-center bg-slate-50 overflow-hidden shrink-0 group-hover:border-indigo-400 transition-colors">
                                <template x-if="imgPreview"><img :src="imgPreview" class="h-full w-full object-cover"></template>
                                <template x-if="!imgPreview"><i class="fas fa-camera text-slate-300 text-xl group-hover:text-indigo-400 transition-colors"></i></template>
                            </div>
                            <div class="flex-1">
                                <input type="file" name="image" accept="image/*"
                                       class="block w-full text-xs text-slate-500 file:mr-3 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-bold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 transition-all cursor-pointer"
                                       @change="imgPreview = URL.createObjectURL($event.target.files[0])">
                                <p class="text-[10px] text-slate-400 mt-1 pl-1">Max 2MB (JPG/PNG)</p>
                            </div>
                        </div>
                    </div>

                    {{--
                        2. BAGIAN INPUT BARCODE DENGAN VISUAL GENERATOR
                        Ini yang Anda cari: Ada tempat (SVG) untuk menampilkan garis barcode
                    --}}
                    <div x-show="!isService" x-data="{
                            code: '',
                            renderBarcode() {
                                if(this.code.length > 0) {
                                    this.$nextTick(() => {
                                        try {
                                            JsBarcode(this.$refs.barcodeCanvas, this.code, {
                                                format: 'CODE128',
                                                lineColor: '#334155',
                                                width: 2,
                                                height: 40,
                                                displayValue: true
                                            });
                                        } catch(e) { console.log('Barcode error', e); }
                                    });
                                }
                            }
                        }">
                        <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1 tracking-wider">Barcode / SKU</label>

                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400"><i class="fas fa-barcode"></i></span>

                            {{-- Input Field --}}
                            <input type="text" name="barcode"
                                   x-model="code"
                                   @input.debounce.300ms="renderBarcode()"
                                   @keydown.enter.prevent="renderBarcode()"
                                   placeholder="Scan atau Ketik Manual..."
                                   class="w-full pl-9 pr-4 py-2.5 rounded-xl border-slate-200 focus:ring-2 focus:ring-indigo-500 transition text-sm font-mono tracking-wide font-bold">
                        </div>

                        {{-- AREA VISUAL (GAMBAR BARCODE MUNCUL DISINI) --}}
                        <div class="mt-2 flex items-center justify-center bg-white border border-slate-100 rounded-lg overflow-hidden py-2"
                             x-show="code.length > 0" x-transition>
                            <svg x-ref="barcodeCanvas" class="w-full h-12 object-contain"></svg>
                        </div>

                        <p class="text-[9px] text-slate-400 mt-1 italic pl-1" x-show="code.length === 0">
                            *Kosongkan field ini, sistem akan membuatkan barcode otomatis.
                        </p>
                    </div>

                    {{-- Kategori --}}
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1 tracking-wider">Kategori <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <select name="category_id" required @change="handleCategoryChange($event)"
                                    class="w-full px-4 py-3 rounded-xl border-slate-200 bg-slate-50 focus:bg-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition text-sm font-medium appearance-none">
                                <option value="" data-type="physical" data-presets="[]">-- Pilih Kategori --</option>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}"
                                            data-type="{{ $cat->type }}"
                                            data-unit="{{ $cat->default_unit }}"
                                            {{-- Tambahkan tanda tanya dua kali dan kurung siku --}}
                                            data-presets="{{ json_encode($cat->product_presets ?? []) }}">
                                        {{ $cat->name }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="absolute inset-y-0 right-0 flex items-center px-3 pointer-events-none text-slate-500">
                                <i class="fas fa-chevron-down text-xs"></i>
                            </div>
                        </div>
                    </div>

                    {{-- === TAMBAHAN BARU: PILIH JENIS PRODUK (SUPAYA BARCODE 100/200 JALAN) === --}}
                    <div class="mt-4">
                        <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1 tracking-wider">Jenis Produk <span class="text-red-500">*</span></label>
                        <div class="grid grid-cols-2 gap-3" x-data="{ activeType: 'physical' }">

                            {{-- Pilihan Barang Fisik --}}
                            <label class="cursor-pointer">
                                <input type="radio" name="type" value="physical" class="peer sr-only" x-model="activeType" checked>
                                <div class="p-3 rounded-xl border border-slate-200 text-center transition-all peer-checked:border-indigo-500 peer-checked:bg-indigo-50 peer-checked:text-indigo-700 hover:bg-slate-50">
                                    <i class="fas fa-box mb-1 text-lg"></i>
                                    <div class="text-xs font-bold">Barang Fisik</div>
                                    <div class="text-[9px] text-slate-400">Barcode: 100...</div>
                                </div>
                            </label>

                            {{-- Pilihan Jasa / Layanan --}}
                            <label class="cursor-pointer">
                                <input type="radio" name="type" value="service" class="peer sr-only" x-model="activeType">
                                <div class="p-3 rounded-xl border border-slate-200 text-center transition-all peer-checked:border-indigo-500 peer-checked:bg-indigo-50 peer-checked:text-indigo-700 hover:bg-slate-50">
                                    <i class="fas fa-hands-helping mb-1 text-lg"></i>
                                    <div class="text-xs font-bold">Jasa / Layanan</div>
                                    <div class="text-[9px] text-slate-400">Barcode: 200...</div>
                                </div>
                            </label>
                        </div>
                    </div>
                    {{-- === BATAS AKHIR TAMBAHAN === --}}

                    {{-- Nama Produk --}}
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1 tracking-wider">Nama Produk / Layanan <span class="text-red-500">*</span></label>

                        {{-- 1. Input Manual (Matikan jika ada preset) --}}
                        <input type="text" name="name"
                            x-show="presets.length === 0"
                            :required="presets.length === 0"
                            :disabled="presets.length > 0"  {{-- <--- TAMBAHAN KRITIS: Biar gak dikirim kalau sembunyi --}}
                            placeholder="Contoh: Kertas A4 70gsm"
                            class="w-full px-4 py-3 rounded-xl border-slate-200 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition text-sm font-bold text-slate-700 placeholder-slate-300">

                        {{-- 2. Dropdown Preset (Matikan jika tidak ada preset) --}}
                        <div class="relative" x-show="presets.length > 0" style="display: none;">
                            <select name="name"
                                    :required="presets.length > 0"
                                    :disabled="presets.length === 0" {{-- <--- TAMBAHAN KRITIS: Biar gak dikirim kalau sembunyi --}}
                                    class="w-full px-4 py-3 rounded-xl border-indigo-200 bg-indigo-50 text-indigo-700 font-bold focus:ring-2 focus:ring-indigo-500 transition text-sm appearance-none">
                                <option value="">-- Pilih Layanan --</option>
                                <template x-for="p in presets">
                                    <option :value="p" x-text="p"></option>
                                </template>
                            </select>
                            <div class="absolute inset-y-0 right-0 flex items-center px-3 pointer-events-none text-indigo-500">
                                <i class="fas fa-check-circle text-xs"></i>
                            </div>
                        </div>
                    </div>

                    {{-- Grid Harga --}}
                    <div class="grid grid-cols-2 gap-4">
                        <div x-show="!isService">
                            <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1 tracking-wider">Modal</label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400 text-xs">Rp</span>
                                <input type="number" name="base_price" :value="isService ? 0 : ''" placeholder="0"
                                       class="w-full pl-8 pr-3 py-2.5 rounded-xl border-slate-200 bg-slate-50 focus:bg-white focus:ring-2 focus:ring-indigo-500 transition text-sm">
                            </div>
                        </div>

                        <div :class="isService ? 'col-span-2' : ''">
                            <label class="block text-[10px] font-bold text-emerald-600 uppercase mb-1 tracking-wider">Harga Jual <span class="text-red-500">*</span></label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-emerald-500 text-xs font-bold">Rp</span>
                                <input type="number" name="sell_price" required placeholder="0"
                                       class="w-full pl-8 pr-3 py-2.5 rounded-xl border-emerald-200 bg-emerald-50 text-emerald-700 font-bold focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition text-sm">
                            </div>
                        </div>
                    </div>

                    {{-- Grid Stok & Satuan --}}
                    <div class="grid grid-cols-2 gap-4">
                        <div x-show="!isService">
                            <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1 tracking-wider">Stok Awal</label>
                            <input type="number" name="stock" :value="isService ? 10000 : ''" placeholder="0"
                                   class="w-full px-4 py-2.5 rounded-xl border-slate-200 focus:ring-2 focus:ring-indigo-500 transition text-sm font-bold text-slate-700">
                        </div>

                        <div :class="isService ? 'col-span-2' : ''">
                            <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1 tracking-wider">Satuan</label>
                            <div class="relative">
                                <select name="unit" x-model="unit" class="w-full px-4 py-2.5 rounded-xl border-slate-200 bg-slate-50 focus:bg-white text-sm appearance-none">
                                    <option value="pcs">Pcs</option>
                                    <option value="kg">Kg</option>
                                    <option value="lembar">Lembar</option>
                                    <option value="box">Box</option>
                                    <option value="paket">Paket</option>
                                    <option value="meter">Meter</option>
                                </select>
                                <div class="absolute inset-y-0 right-0 flex items-center px-3 pointer-events-none text-slate-400">
                                    <i class="fas fa-chevron-down text-xs"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Supplier --}}
                    <div x-show="!isService">
                        <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1 tracking-wider">Supplier</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400"><i class="fas fa-truck text-xs"></i></span>
                            <input type="text" name="supplier" placeholder="Contoh: PT. Sinar Dunia"
                                   class="w-full pl-9 pr-4 py-2.5 rounded-xl border-slate-200 focus:ring-2 focus:ring-indigo-500 transition text-sm">
                        </div>
                    </div>

                    {{-- Tombol Submit --}}
                    <button type="submit" class="w-full py-3.5 bg-slate-800 hover:bg-slate-900 text-white rounded-xl font-bold shadow-lg shadow-slate-200 transition-all transform active:scale-[0.98] flex justify-center items-center gap-2 mt-2" :disabled="submitting">
                        <span x-show="!submitting" class="flex items-center gap-2"><i class="fas fa-save"></i> Simpan Produk</span>
                        <span x-show="submitting" class="flex items-center gap-2"><i class="fas fa-spinner fa-spin"></i> Menyimpan...</span>
                    </button>
                </form>
            </div>
        </div>

        {{-- === BAGIAN KANAN: TABEL PRODUK === --}}
        <div class="lg:col-span-2">
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">

                <div class="px-6 py-5 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
                    <h2 class="font-bold text-slate-700 flex items-center gap-2">
                        <i class="fas fa-boxes text-indigo-500"></i> Inventaris Barang
                    </h2>
                    <span class="text-xs font-bold bg-white border border-slate-200 px-3 py-1 rounded-full text-slate-500 shadow-sm">
                        Total: {{ $products->total() }} Item
                    </span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="bg-slate-50 text-slate-500 uppercase text-[10px] font-bold tracking-wider border-b border-slate-200">
                            <tr>
                                <th class="px-6 py-4 w-16 text-center">Img</th>
                                <th class="px-6 py-4">Nama Produk & Barcode</th>
                                <th class="px-6 py-4 text-center">Stok</th>
                                <th class="px-6 py-4 text-right">Harga</th>
                                <th class="px-6 py-4 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($products as $product)
                            <tr class="hover:bg-slate-50/80 transition-colors group">
                                {{-- Image --}}
                                <td class="px-6 py-4">
                                    <div class="h-12 w-12 rounded-xl bg-slate-100 border border-slate-200 flex items-center justify-center overflow-hidden shadow-sm">
                                        @if($product->image)
                                            <img src="{{ asset('storage/' . $product->image) }}" alt="{{ $product->name }}" class="h-full w-full object-cover">
                                        @else
                                            <i class="fas fa-cube text-slate-300 text-xl"></i>
                                        @endif
                                    </div>
                                </td>

                                {{-- Name, Barcode & Category --}}
                                <td class="px-6 py-4">
                                    <div class="font-bold text-slate-700 text-base group-hover:text-indigo-600 transition-colors">
                                        {{ $product->name }}
                                    </div>

                                    {{-- TAMPILAN BARCODE DI TABEL --}}
                                    @if($product->barcode)
                                        <div class="text-[10px] font-mono text-slate-500 bg-slate-100 px-1.5 py-0.5 rounded w-fit mt-1 border border-slate-200 flex items-center gap-1">
                                            <i class="fas fa-barcode"></i> {{ $product->barcode }}
                                        </div>
                                    @endif

                                    <div class="flex items-center gap-2 mt-1.5">
                                        @if(isset($product->category))
                                            <span class="text-[9px] bg-indigo-50 text-indigo-600 px-2 py-0.5 rounded border border-indigo-100 uppercase font-bold tracking-wide">
                                                {{ $product->category->name }}
                                            </span>
                                        @endif
                                        <span class="text-[9px] bg-slate-100 px-2 py-0.5 rounded text-slate-500 border border-slate-200 uppercase font-bold">
                                            {{ $product->unit }}
                                        </span>
                                    </div>
                                    @if($product->has_variant)
                                        <div class="mt-1">
                                            <span class="text-[9px] text-purple-600 font-bold flex items-center gap-1">
                                                <i class="fas fa-layer-group"></i> Multi Varian
                                            </span>
                                        </div>
                                    @endif
                                </td>

                                {{-- Stock --}}
                                <td class="px-6 py-4 text-center">
                                    @if($product->stock <= 5)
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 bg-red-50 text-red-600 rounded-full text-xs font-bold border border-red-100">
                                            <div class="w-1.5 h-1.5 rounded-full bg-red-500"></div> {{ $product->stock }}
                                        </span>
                                    @else
                                        <span class="text-sm font-bold text-slate-700 bg-slate-100 px-3 py-1 rounded-lg border border-slate-200">
                                            {{ $product->stock }}
                                        </span>
                                    @endif
                                    <div class="text-[10px] text-slate-400 mt-1">Terjual: <b>{{ $product->sold }}</b></div>
                                </td>

                                {{-- Price --}}
                                <td class="px-6 py-4 text-right">
                                    <div class="font-black text-emerald-600 text-base tracking-tight">
                                        Rp {{ number_format($product->sell_price, 0, ',', '.') }}
                                    </div>
                                    @if($product->base_price > 0)
                                    <div class="text-[10px] text-slate-400 mt-0.5">
                                        Modal: Rp {{ number_format($product->base_price, 0, ',', '.') }}
                                    </div>
                                    @endif
                                </td>

                                {{-- Actions --}}
                                <td class="px-6 py-4 text-center">
                                    <div class="flex items-center justify-end gap-2 opacity-100 lg:opacity-0 lg:group-hover:opacity-100 transition-opacity">

                                        {{-- TOMBOL KELOLA VARIAN --}}
                                        <button @click="openVariantModal({{ $product->id }})"
                                                class="h-8 w-8 rounded-lg bg-purple-50 text-purple-600 hover:bg-purple-100 hover:text-purple-700 border border-purple-100 transition flex items-center justify-center shadow-sm"
                                                title="Kelola Varian & Stok">
                                            <i class="fas fa-layer-group text-xs"></i>
                                        </button>

                                        {{-- EDIT --}}
                                        <a href="{{ route('products.edit', $product->id) }}"
                                           class="h-8 w-8 rounded-lg bg-amber-50 text-amber-600 hover:bg-amber-100 hover:text-amber-700 border border-amber-100 transition flex items-center justify-center shadow-sm"
                                           title="Edit Produk">
                                            <i class="fas fa-pencil-alt text-xs"></i>
                                        </a>

                                        {{-- DELETE --}}
                                        <form action="{{ route('products.destroy', $product->id) }}" method="POST" onsubmit="return confirm('Hapus produk {{ $product->name }}?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    class="h-8 w-8 rounded-lg bg-red-50 text-red-600 hover:bg-red-100 hover:text-red-700 border border-red-100 transition flex items-center justify-center shadow-sm"
                                                    title="Hapus">
                                                <i class="fas fa-trash-alt text-xs"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="px-6 py-16 text-center">
                                    <div class="flex flex-col items-center justify-center text-slate-400">
                                        <div class="h-16 w-16 bg-slate-50 rounded-full flex items-center justify-center mb-3">
                                            <i class="fas fa-search text-3xl opacity-20"></i>
                                        </div>
                                        <p class="font-bold text-slate-500">Data tidak ditemukan.</p>
                                        <p class="text-xs">Coba kata kunci lain atau tambah produk baru.</p>
                                    </div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Pagination --}}
                <div class="px-6 py-4 border-t border-slate-100 bg-slate-50/30">
                    {{ $products->withQueryString()->links() }}
                </div>
            </div>
        </div>
    </div>

    {{-- MODAL VARIANT MANAGER --}}
    <div x-show="variantModalOpen"
         style="display: none;"
         class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-sm p-4"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">

        <div class="bg-white w-full max-w-2xl rounded-2xl shadow-2xl border border-slate-200 overflow-hidden flex flex-col max-h-[85vh]"
             @click.away="variantModalOpen = false"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 scale-95 translate-y-4"
             x-transition:enter-end="opacity-100 scale-100 translate-y-0">

            {{-- Header --}}
            <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center bg-slate-50 shrink-0">
                <div>
                    <h3 class="font-bold text-lg text-slate-800">Kelola Varian & Stok</h3>
                    <p class="text-xs text-slate-500">Produk: <span x-text="activeProductName" class="font-bold text-indigo-600"></span></p>
                </div>
                <button @click="variantModalOpen = false" class="h-8 w-8 flex items-center justify-center rounded-full bg-white hover:bg-red-50 text-slate-400 hover:text-red-500 transition shadow-sm border border-slate-200">
                    <i class="fas fa-times text-sm"></i>
                </button>
            </div>

            {{-- Body (Scrollable) --}}
            <div class="p-6 overflow-y-auto grow bg-white">

                {{-- Loading State --}}
                <div x-show="isLoadingVariant" class="flex justify-center py-12">
                    <div class="flex flex-col items-center gap-3">
                        <i class="fas fa-circle-notch fa-spin text-3xl text-indigo-500"></i>
                        <span class="text-xs text-slate-400 font-bold uppercase tracking-wider">Memuat Data...</span>
                    </div>
                </div>

                {{-- Content --}}
                <div x-show="!isLoadingVariant">

                    <div class="flex justify-between items-end mb-4">
                        <div>
                            <h4 class="text-sm font-bold text-slate-700">Daftar Varian</h4>
                            <p class="text-[10px] text-slate-400">Atur harga, stok, dan barcode berbeda untuk setiap varian.</p>
                        </div>
                        <button @click="addVariantRow()" class="px-3 py-1.5 bg-indigo-50 text-indigo-600 text-xs font-bold rounded-lg border border-indigo-100 hover:bg-indigo-100 transition flex items-center gap-1 shadow-sm">
                            <i class="fas fa-plus"></i> Tambah Baris
                        </button>
                    </div>

                    {{-- Empty State --}}
                    <template x-if="variants.length === 0">
                        <div class="text-center py-8 bg-slate-50 rounded-xl border border-dashed border-slate-300">
                            <div class="h-12 w-12 bg-slate-100 rounded-full flex items-center justify-center mx-auto mb-3">
                                <i class="fas fa-layer-group text-slate-300 text-xl"></i>
                            </div>
                            <p class="text-sm text-slate-500 font-medium">Belum ada varian.</p>
                            <p class="text-xs text-slate-400 mb-3">Produk ini menggunakan stok tunggal (Single Product).</p>
                            <button @click="addVariantRow()" class="text-indigo-600 text-xs font-bold underline hover:text-indigo-800">Mulai Tambah Varian</button>
                        </div>
                    </template>

                    {{-- Table Headers --}}
                    <div x-show="variants.length > 0" class="space-y-2">
                        <div class="grid grid-cols-12 gap-3 text-[9px] font-bold text-slate-400 uppercase tracking-wider px-1">
                            <div class="col-span-3">Nama Varian <span class="text-red-500">*</span></div>
                            <div class="col-span-3">Barcode (Scan)</div>
                            <div class="col-span-3">Harga (Rp) <span class="text-red-500">*</span></div>
                            <div class="col-span-2">Stok</div>
                            <div class="col-span-1 text-center">Aksi</div>
                        </div>

                        {{-- Rows --}}
                        <template x-for="(variant, index) in variants" :key="index">
                            <div class="grid grid-cols-12 gap-3 items-center group animate-fade-in-down">

                                {{-- Nama --}}
                                <div class="col-span-3">
                                    <input type="text" x-model="variant.name" placeholder="Cth: Merah"
                                           class="w-full px-3 py-2 rounded-lg border-slate-300 text-sm focus:ring-2 focus:ring-indigo-500 font-bold text-slate-700 bg-slate-50 focus:bg-white transition-all">
                                </div>

                                {{-- Barcode (Input di dalam Varian) --}}
                                <div class="col-span-3 relative">
                                    <span class="absolute inset-y-0 left-0 pl-2 flex items-center text-slate-400"><i class="fas fa-barcode text-xs"></i></span>
                                    <input type="text" x-model="variant.barcode" placeholder="Scan..."
                                           @keydown.enter.prevent
                                           class="w-full pl-6 pr-2 py-2 rounded-lg border-slate-300 text-xs font-mono focus:ring-2 focus:ring-indigo-500 bg-white transition-all">
                                </div>

                                {{-- Harga --}}
                                <div class="col-span-3 relative">
                                    <input type="number" x-model="variant.price" placeholder="0"
                                           class="w-full px-3 py-2 rounded-lg border-emerald-200 text-sm focus:ring-2 focus:ring-emerald-500 text-emerald-700 font-bold bg-emerald-50/50 focus:bg-white text-right transition-all">
                                </div>

                                {{-- Stok --}}
                                <div class="col-span-2">
                                    <input type="number" x-model="variant.stock" placeholder="0"
                                           class="w-full px-3 py-2 rounded-lg border-slate-300 text-sm focus:ring-2 focus:ring-indigo-500 text-center bg-white transition-all">
                                </div>

                                {{-- Hapus --}}
                                <div class="col-span-1 text-center">
                                    <button @click="removeVariantRow(index)" class="h-8 w-8 inline-flex items-center justify-center text-slate-400 hover:text-red-500 hover:bg-red-50 rounded-lg transition">
                                        <i class="fas fa-trash-alt text-xs"></i>
                                    </button>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            {{-- Footer --}}
            <div class="px-6 py-4 border-t border-slate-100 bg-slate-50 shrink-0 flex justify-end gap-3">
                <button @click="variantModalOpen = false" class="px-4 py-2 text-slate-600 font-bold text-sm hover:bg-slate-200 rounded-lg transition">
                    Batal
                </button>
                <button @click="saveVariants()" class="px-6 py-2 bg-indigo-600 text-white font-bold text-sm rounded-lg hover:bg-indigo-700 shadow-lg shadow-indigo-200 transition flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed" :disabled="isSavingVariant">
                    <i class="fas fa-save" x-show="!isSavingVariant"></i>
                    <i class="fas fa-spinner fa-spin" x-show="isSavingVariant"></i>
                    <span x-text="isSavingVariant ? 'Menyimpan...' : 'Simpan Perubahan'"></span>
                </button>
            </div>
        </div>
    </div>

</div>

{{-- SCRIPT ALPINE JS --}}
<script>
    function productManager() {
        return {
            // --- STATE UNTUK MODAL VARIAN ---
            variantModalOpen: false,
            isLoadingVariant: false,
            isSavingVariant: false,
            activeProductId: null,
            activeProductName: '',
            variants: [],

            // 1. Buka Modal & Fetch Data
            async openVariantModal(productId) {
                this.activeProductId = productId;
                this.variantModalOpen = true;
                this.isLoadingVariant = true;
                this.variants = []; // Reset dulu

                try {
                    let url = `/percetakan/public/products/${productId}/variants`;
                    let response = await fetch(url);
                    if (!response.ok) throw new Error('Gagal ambil data');

                    let data = await response.json();

                    this.activeProductName = data.product_name;
                    // Map data dari DB ke struktur JS (termasuk barcode)
                    this.variants = data.variants.map(v => ({
                        id: v.id,
                        name: v.name,
                        price: v.price,
                        stock: v.stock,
                        sku: v.sku,
                        barcode: v.barcode || '' // Load barcode jika ada
                    }));

                } catch (error) {
                    console.error(error);
                    alert('Terjadi kesalahan saat mengambil data varian.');
                    this.variantModalOpen = false;
                } finally {
                    this.isLoadingVariant = false;
                }
            },

            // 2. Tambah Baris Baru di Modal
            addVariantRow() {
                this.variants.push({
                    name: '',
                    price: 0,
                    stock: 0,
                    sku: '',
                    barcode: '' // Field baru
                });
                this.$nextTick(() => {
                    let container = document.querySelector('.overflow-y-auto');
                    if(container) container.scrollTop = container.scrollHeight;
                });
            },

            // 3. Hapus Baris di Modal
            removeVariantRow(index) {
                this.variants.splice(index, 1);
            },

            // 4. Simpan ke Database
            // 4. Simpan ke Database (VERSI FULL LOGGING)
            async saveVariants() {
                // LOG 1: Cek Data Sebelum Dikirim
                console.log("🔥 LOG 1 [START]: Memulai proses simpan...");
                console.log("🔥 LOG 2 [DATA]: Data Varian yang akan dikirim:", JSON.parse(JSON.stringify(this.variants)));

                // Validasi Sederhana
                if (this.variants.length > 0) {
                    for (let v of this.variants) {
                        if (!v.name || v.name.trim() === '') {
                            console.warn("⚠️ LOG [VALIDASI]: Nama varian kosong ditemukan!");
                            alert('Nama varian tidak boleh kosong!');
                            return;
                        }
                    }
                }

                this.isSavingVariant = true;

                try {
                    // Cek URL Target
                    let url = `/percetakan/public/products/${this.activeProductId}/variants`;
                    console.log("🔥 LOG 3 [URL]: Menembak ke ->", url);

                    let response = await fetch(url, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json', // PENTING: Minta balasan JSON
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            variants: this.variants
                        })
                    });

                    console.log("🔥 LOG 4 [STATUS]: HTTP Status Code ->", response.status);

                    // JIKA ERROR (Bukan 200 OK)
                    if (!response.ok) {
                        // Kita ambil text aslinya (biasanya halaman error Laravel warna abu-abu/merah)
                        let errorText = await response.text();

                        console.error("❌ LOG 5 [SERVER ERROR MESSAGE]:");
                        console.error("---------------------------------------------------");
                        console.error(errorText); // <--- INI ISI PESAN ERRORNYA BOS!
                        console.error("---------------------------------------------------");

                        throw new Error(`Server Error: ${response.status}`);
                    }

                    // JIKA SUKSES
                    let result = await response.json();
                    console.log("✅ LOG 6 [RESPONSE]:", result);

                    if (result.success) {
                        alert('Berhasil! Varian dan Stok telah diperbarui.');
                        this.variantModalOpen = false;
                        window.location.reload();
                    } else {
                        console.warn("⚠️ LOG [GAGAL LOGIC]:", result.message);
                        alert('Gagal: ' + (result.message || 'Terjadi kesalahan.'));
                    }

                } catch (error) {
                    console.error("❌ LOG [EXCEPTION]: Javascript Error ->", error);
                    alert('Gagal menghubungi server. Cek Console (F12) untuk melihat LOG merah.');
                } finally {
                    this.isSavingVariant = false;
                }
            }
        };
    }
</script>
@endsection
