@extends('layouts.app')

@section('title', 'Analisa HPP - ' . $product->name)

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8"
     x-data="hppCalculator({{ $product->recipeItems }}, {{ $product->base_price ?? 0 }}, {{ $product->sell_price ?? 0 }})">

    {{-- HEADER --}}
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
        <div>
            <div class="flex items-center gap-2 text-sm text-slate-400 mb-1">
                <a href="{{ route('hpp.index') }}" class="hover:text-blue-600 transition"><i class="fas fa-arrow-left mr-1"></i> Kembali</a>
                <span>/</span>
                <span>Analisa Produk</span>
            </div>
            <h1 class="text-3xl font-black text-slate-800 tracking-tight">{{ $product->name }}</h1>
            <p class="text-slate-500 font-medium">Kalkulator HPP & Break Even Point</p>
        </div>
        <div class="flex gap-3">
            <button type="button" @click="resetToDefault()" class="px-5 py-2.5 bg-white border border-slate-300 text-slate-600 rounded-xl text-sm font-bold hover:bg-slate-50 transition shadow-sm">
                <i class="fas fa-undo-alt mr-2"></i> Reset
            </button>
            <button type="button" @click="saveRecipe()" class="px-6 py-2.5 bg-blue-600 text-white rounded-xl text-sm font-bold hover:bg-blue-700 transition shadow-lg shadow-blue-200 flex items-center">
                <i class="fas fa-save mr-2"></i> Simpan Analisa
            </button>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">

        {{-- KOLOM KIRI: INPUT DATA (8 Kolom) --}}
        <div class="lg:col-span-8 space-y-8">

            {{-- 1. TABEL KOMPONEN BIAYA --}}
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="p-5 border-b border-slate-200 flex justify-between items-center bg-slate-50">
                    <div>
                        <h3 class="font-bold text-lg text-slate-800">Komponen HPP (Modal Pokok)</h3>
                        <p class="text-xs text-slate-500">Masukkan rincian biaya per 1 unit produk.</p>
                    </div>
                    <button @click="addItem()" class="flex items-center gap-2 bg-green-600 text-white px-4 py-2 rounded-lg text-xs font-bold hover:bg-green-700 transition shadow-sm">
                        <i class="fas fa-plus"></i> Tambah Biaya
                    </button>
                </div>

                <div class="overflow-x-auto p-4">
                    <table class="w-full text-left text-sm border-collapse">
                        <thead class="bg-slate-100 text-slate-600 uppercase text-[11px] font-bold tracking-wider border border-slate-200">
                            <tr>
                                <th class="px-4 py-3 border border-slate-200 min-w-[250px]">Bahan Baku / Biaya</th>
                                <th class="px-2 py-3 border border-slate-200 w-24 text-center">Qty</th>
                                <th class="px-2 py-3 border border-slate-200 w-36 text-right">Biaya Satuan</th>
                                <th class="px-4 py-3 border border-slate-200 w-36 text-right bg-slate-50">Subtotal</th>
                                <th class="px-2 py-3 border border-slate-200 w-10 text-center"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 border border-slate-200">
                            <template x-for="(item, index) in items" :key="index">
                                <tr class="hover:bg-slate-50 transition">

                                    {{-- Input Nama / Dropdown --}}
                                    <td class="px-3 py-3 border border-slate-200 align-top">
                                        <div class="flex flex-col gap-2">
                                            <select x-model="item.child_product_id" @change="updateCostFromMaterial(index, $event)"
                                                    class="w-full text-xs font-medium text-slate-700 border border-slate-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 py-2 bg-white shadow-sm">
                                                <option value="">-- Input Manual / Tenaga --</option>
                                                @foreach($materials as $mat)
                                                    <option value="{{ $mat->id }}" data-price="{{ $mat->base_price }}">
                                                        📦 {{ $mat->name }} (Stok: {{ $mat->stock }})
                                                    </option>
                                                @endforeach
                                            </select>
                                            <input x-show="!item.child_product_id" type="text" x-model="item.custom_name"
                                                   placeholder="Contoh: Listrik / Tinta"
                                                   class="w-full text-xs border border-slate-300 rounded-md px-3 py-2 bg-slate-50 focus:bg-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all shadow-sm">
                                        </div>
                                    </td>

                                    {{-- Input Qty --}}
                                    <td class="px-2 py-3 border border-slate-200 align-top">
                                        <input type="number" step="0.0001" x-model="item.quantity"
                                               class="w-full text-center text-sm font-bold text-slate-800 border border-slate-300 rounded-md focus:ring-2 focus:ring-blue-500 py-2 shadow-sm">
                                    </td>

                                    {{-- Input Biaya Satuan --}}
                                    <td class="px-2 py-3 border border-slate-200 align-top">
                                        <div class="relative">
                                            <span class="absolute left-2 top-1/2 -translate-y-1/2 text-slate-400 text-xs">Rp</span>
                                            <input type="number" step="1" x-model="item.cost" :readonly="!!item.child_product_id"
                                                   class="w-full text-right pl-7 pr-2 py-2 text-sm font-bold border border-slate-300 rounded-md focus:ring-2 focus:ring-blue-500 shadow-sm"
                                                   :class="item.child_product_id ? 'bg-slate-100 text-slate-500 cursor-not-allowed' : 'bg-white text-slate-800'">
                                        </div>
                                    </td>

                                    {{-- Subtotal (Readonly) --}}
                                    <td class="px-4 py-3 border border-slate-200 align-middle text-right bg-slate-50">
                                        <span class="font-black text-slate-700" x-text="formatRupiah(item.quantity * item.cost)"></span>
                                    </td>

                                    {{-- Hapus --}}
                                    <td class="px-2 py-3 border border-slate-200 align-middle text-center">
                                        <button @click="removeItem(index)" class="w-7 h-7 rounded bg-white border border-slate-200 flex items-center justify-center text-slate-400 hover:text-red-600 hover:border-red-300 transition shadow-sm">
                                            <i class="fas fa-trash-alt text-xs"></i>
                                        </button>
                                    </td>
                                </tr>
                            </template>
                            <tr x-show="items.length === 0">
                                <td colspan="5" class="py-8 text-center text-slate-400 bg-slate-50 border border-slate-200 border-dashed">
                                    <i class="fas fa-arrow-up mb-2"></i><br>
                                    Silakan tambah komponen biaya di atas.
                                </td>
                            </tr>
                        </tbody>
                        <tfoot class="bg-slate-100 border border-slate-200">
                            <tr>
                                <td colspan="3" class="px-4 py-3 text-right font-bold text-slate-600 uppercase text-xs tracking-wider">Total HPP (Modal):</td>
                                <td class="px-4 py-3 text-right font-black text-slate-800 text-lg border-l border-slate-200 bg-white">
                                    <span x-text="formatRupiah(totalHpp)"></span>
                                </td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            {{-- 2. ANALISA BEP (DESAIN BARU - GRADIENT CARD) --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                {{-- Kiri: Input Biaya Operasional --}}
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5 h-full">
                    <h3 class="font-bold text-slate-700 mb-4 flex items-center gap-2">
                        <i class="fas fa-store text-blue-500"></i> Biaya Operasional Toko
                    </h3>

                    <div class="space-y-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-600 uppercase mb-1">Total Biaya Tetap (Sebulan)</label>
                            <div class="relative">
                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 font-bold text-xs">Rp</span>
                                <input type="number" x-model="fixedCost"
                                       class="w-full pl-9 pr-4 py-2 border border-slate-300 rounded-lg text-sm font-bold text-slate-700 focus:ring-blue-500 focus:border-blue-500 shadow-sm"
                                       placeholder="Contoh: 1.500.000">
                            </div>
                            <p class="text-[10px] text-slate-400 mt-1">*Total Gaji, Listrik, Sewa, Internet, dll.</p>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-600 uppercase mb-1">Target Penjualan Harian</label>
                            <div class="flex items-center gap-2">
                                <input type="number" x-model="salesPerDay"
                                       class="w-full px-4 py-2 border border-slate-300 rounded-lg text-sm font-bold text-slate-700 focus:ring-blue-500 focus:border-blue-500 shadow-sm"
                                       placeholder="10">
                                <span class="text-xs font-bold text-slate-500 bg-slate-100 px-3 py-2 rounded-lg border border-slate-200">Unit/Hari</span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Kanan: Hasil Analisa (Gradient Card seperti Referensi) --}}
                <div class="bg-gradient-to-br from-indigo-600 to-purple-600 rounded-xl shadow-lg p-6 text-white relative overflow-hidden flex flex-col justify-center min-h-[220px]">
                    {{-- Dekorasi Background --}}
                    <div class="absolute top-0 right-0 -mr-8 -mt-8 w-32 h-32 bg-white opacity-10 rounded-full blur-3xl"></div>
                    <div class="absolute bottom-0 left-0 -ml-8 -mb-8 w-32 h-32 bg-blue-500 opacity-20 rounded-full blur-3xl"></div>

                    <div class="grid grid-cols-2 gap-4 text-center relative z-10">
                        <div class="border-r border-white/20 pr-4">
                            <span class="block text-[10px] font-bold uppercase opacity-80 mb-1">Target Jual (BEP)</span>
                            <span class="block text-3xl font-black tracking-tight" x-text="formatRupiah(bepUnit, false)"></span>
                            <span class="text-[10px] font-medium opacity-80 bg-white/20 px-2 py-0.5 rounded-full mt-1 inline-block">Unit Terjual</span>
                        </div>
                        <div class="pl-4">
                            <span class="block text-[10px] font-bold uppercase opacity-80 mb-1">Balik Modal Dalam</span>
                            <span class="block text-3xl font-black tracking-tight" x-text="daysToBep"></span>
                            <span class="text-[10px] font-medium opacity-80 bg-white/20 px-2 py-0.5 rounded-full mt-1 inline-block">Hari Kerja</span>
                        </div>
                    </div>

                    <div class="mt-6 pt-4 border-t border-white/20 text-center relative z-10">
                        <p class="text-xs leading-relaxed opacity-90" x-show="profit > 0 && fixedCost > 0">
                            Anda harus menjual <span class="font-bold border-b border-white/50">@{{ formatRupiah(bepUnit, false) }} unit</span> sebulan<br>untuk menutup biaya operasional Rp @{{ formatRupiah(fixedCost, false) }}.
                        </p>
                        <p class="text-xs font-bold bg-red-500/20 py-2 rounded text-center border border-red-400/50" x-show="profit <= 0">
                            <i class="fas fa-exclamation-triangle mr-1"></i> Harga Jual terlalu rendah! (Rugi)
                        </p>
                        <p class="text-xs opacity-70" x-show="profit > 0 && fixedCost == 0">
                            Isi biaya operasional di samping untuk melihat analisa.
                        </p>
                    </div>
                </div>

            </div>

        </div>

        {{-- KOLOM KANAN: SIMULASI HARGA (Sticky - 4 Kolom) --}}
        <div class="lg:col-span-4 space-y-6">
            <div class="bg-white rounded-xl shadow-lg border border-slate-200 sticky top-6 overflow-hidden">
                <div class="p-5 bg-slate-900 text-white flex justify-between items-center">
                    <h3 class="font-bold flex items-center gap-2"><i class="fas fa-calculator text-slate-400"></i> Simulasi Harga</h3>
                </div>

                <div class="p-6 space-y-6">

                    {{-- Summary HPP --}}
                    <div class="bg-slate-50 p-4 rounded-lg border border-slate-200 text-center">
                        <span class="block text-xs font-bold text-slate-500 uppercase mb-1">Total Modal (HPP)</span>
                        <span class="block text-2xl font-black text-slate-800" x-text="formatRupiah(totalHpp)"></span>
                    </div>

                    {{-- Margin Slider --}}
                    <div>
                        <div class="flex justify-between items-center mb-3">
                            <label class="text-xs font-bold text-slate-800 uppercase">Target Margin (%)</label>
                            <input type="number" x-model="marginPercent" class="w-16 py-1 px-1 text-center text-xs font-bold border border-slate-300 rounded focus:ring-blue-500">
                        </div>
                        <input type="range" min="0" max="200" step="5" x-model="marginPercent" class="w-full h-2 bg-slate-200 rounded-lg appearance-none cursor-pointer accent-blue-600 hover:accent-blue-700 transition-all">

                        {{-- Suggestion Box --}}
                        <div class="mt-4 bg-amber-50 border border-amber-200 p-3 rounded-lg flex justify-between items-center cursor-pointer hover:bg-amber-100 transition group"
                             @click="sellingPrice = suggestedPrice">
                            <div class="flex items-center gap-2">
                                <i class="fas fa-lightbulb text-amber-500 text-lg"></i>
                                <div>
                                    <span class="block text-[10px] font-bold text-amber-700 uppercase">Saran Harga</span>
                                    <span class="text-[10px] text-amber-600">Klik untuk pakai</span>
                                </div>
                            </div>
                            <span class="text-lg font-black text-amber-700 group-hover:underline">
                                <span x-text="formatRupiah(suggestedPrice)"></span>
                            </span>
                        </div>
                    </div>

                    <hr class="border-slate-200 border-dashed">

                    {{-- Final Price Input --}}
                    <div>
                        <label class="block text-center text-xs font-bold text-slate-800 uppercase mb-2">Harga Jual Final</label>
                        <div class="relative group">
                            <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 font-bold text-lg group-focus-within:text-blue-600 transition-colors">Rp</span>
                            <input type="number" x-model="sellingPrice"
                                   class="w-full pl-12 pr-4 py-4 text-center text-3xl font-black text-slate-900 border-2 border-slate-300 rounded-xl focus:ring-0 focus:border-blue-600 transition-all shadow-sm">
                        </div>
                    </div>

                    {{-- Profit Indicators --}}
                    <div class="grid grid-cols-2 gap-3">
                        <div class="p-3 rounded-xl border flex flex-col items-center justify-center gap-1 transition-colors duration-300"
                             :class="profit > 0 ? 'bg-emerald-50 border-emerald-200 text-emerald-700' : 'bg-red-50 border-red-200 text-red-700'">
                            <span class="text-[9px] font-black uppercase opacity-70">Profit Bersih</span>
                            <span class="text-sm font-bold" x-text="formatRupiah(profit)"></span>
                        </div>
                        <div class="p-3 rounded-xl border flex flex-col items-center justify-center gap-1 transition-colors duration-300"
                             :class="realMargin >= 20 ? 'bg-blue-50 border-blue-200 text-blue-700' : (realMargin > 0 ? 'bg-orange-50 border-orange-200 text-orange-700' : 'bg-red-50 border-red-200 text-red-700')">
                            <span class="text-[9px] font-black uppercase opacity-70">Margin Real</span>
                            <span class="text-sm font-bold" x-text="realMargin + '%'"></span>
                        </div>
                    </div>

                </div>
            </div>
        </div>

    </div>
</div>

{{-- SCRIPT LOGIC (TIDAK ADA PERUBAHAN LOGIC) --}}
<script>
    function hppCalculator(initialItems, currentBasePrice, currentSellPrice) {
        return {
            items: [],
            sellingPrice: currentSellPrice,
            marginPercent: 30,
            fixedCost: 0,
            salesPerDay: 5,

            init() {
                if (initialItems && initialItems.length > 0) {
                    this.items = initialItems.map(item => ({
                        child_product_id: item.child_product_id,
                        custom_name: item.custom_item_name,
                        quantity: parseFloat(item.quantity),
                        cost: parseFloat(item.cost_per_unit)
                    }));
                } else {
                    this.addItem();
                }
            },

            addItem() { this.items.push({ child_product_id: '', custom_name: '', quantity: 1, cost: 0 }); },
            removeItem(index) { this.items.splice(index, 1); },
            resetToDefault() { if(confirm('Reset kalkulator?')) { this.items = []; this.addItem(); this.sellingPrice = 0; this.fixedCost = 0; } },

            updateCostFromMaterial(index, event) {
                const selectedOption = event.target.options[event.target.selectedIndex];
                const price = selectedOption.getAttribute('data-price');
                if (price) {
                    this.items[index].cost = parseFloat(price);
                    this.items[index].custom_name = null;
                } else {
                    this.items[index].cost = 0;
                }
            },

            get totalHpp() { return this.items.reduce((sum, item) => sum + (item.quantity * item.cost), 0); },
            get suggestedPrice() { if (this.totalHpp === 0) return 0; return Math.ceil(this.totalHpp + (this.totalHpp * (this.marginPercent / 100))); },
            get profit() { return this.sellingPrice - this.totalHpp; },
            get realMargin() { if (this.totalHpp === 0 || this.sellingPrice === 0) return 0; return ((this.profit / this.sellingPrice) * 100).toFixed(1); },

            get bepUnit() { if (this.profit <= 0 || this.fixedCost <= 0) return 0; return Math.ceil(this.fixedCost / this.profit); },
            get daysToBep() { if (this.bepUnit === 0 || this.salesPerDay <= 0) return '-'; return Math.ceil(this.bepUnit / this.salesPerDay); },

            formatRupiah(number, prefix = true) {
                if (isNaN(number)) return '0';
                let val = Math.round(number).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
                return prefix ? 'Rp ' + val : val;
            },

            async saveRecipe() {
                if (this.items.length === 0 || this.totalHpp <= 0) { alert('Isi komponen biaya dulu.'); return; }
                if (!confirm('Simpan resep? Harga dasar produk akan diupdate.')) return;
                try {
                    const response = await fetch("{{ route('hpp.updateRecipe', $product->id) }}", {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content') },
                        body: JSON.stringify({ items: this.items, new_selling_price: this.sellingPrice })
                    });
                    const result = await response.json();
                    if (result.status === 'success') { alert('Berhasil!'); window.location.reload(); }
                    else { alert('Gagal: ' + result.message); }
                } catch (e) { alert('Error sistem.'); }
            }
        };
    }
</script>
@endsection
