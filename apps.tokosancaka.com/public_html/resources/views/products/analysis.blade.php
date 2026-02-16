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
            <button type="button" @click="resetToDefault()" class="px-5 py-2.5 bg-white border-2 border-slate-200 text-slate-600 rounded-xl text-sm font-bold hover:border-red-200 hover:text-red-600 hover:bg-red-50 transition active:scale-95">
                <i class="fas fa-undo-alt mr-2"></i> Reset
            </button>
            <button type="button" @click="saveRecipe()" class="px-6 py-2.5 bg-blue-600 text-white rounded-xl text-sm font-bold hover:bg-blue-700 transition shadow-lg shadow-blue-200 active:scale-95 flex items-center">
                <i class="fas fa-save mr-2"></i> Simpan Analisa
            </button>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">

        {{-- KOLOM KIRI: INPUT DATA (8 Kolom) --}}
        <div class="lg:col-span-8 space-y-8">

            {{-- 1. TABEL KOMPONEN BIAYA --}}
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="p-6 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
                    <div>
                        <h3 class="font-bold text-lg text-slate-800 flex items-center gap-2">
                            <span class="w-8 h-8 rounded-lg bg-blue-100 text-blue-600 flex items-center justify-center text-sm"><i class="fas fa-cubes"></i></span>
                            Komponen HPP
                        </h3>
                        <p class="text-xs text-slate-400 mt-1 ml-10">Masukkan bahan baku atau biaya langsung per unit.</p>
                    </div>
                    <button @click="addItem()" class="group flex items-center gap-2 bg-blue-50 text-blue-600 px-4 py-2 rounded-lg text-xs font-bold hover:bg-blue-600 hover:text-white transition">
                        <i class="fas fa-plus bg-blue-200 text-blue-700 rounded-full p-1 w-5 h-5 flex items-center justify-center group-hover:bg-white group-hover:text-blue-600"></i>
                        <span>Tambah Item</span>
                    </button>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-slate-50/80 text-slate-500 uppercase text-[10px] font-bold tracking-wider border-b border-slate-200">
                            <tr>
                                <th class="px-6 py-4 min-w-[250px]">Nama Komponen / Bahan</th>
                                <th class="px-4 py-4 w-28 text-center">Qty Pemakaian</th>
                                <th class="px-4 py-4 w-40 text-right">Biaya Satuan</th>
                                <th class="px-6 py-4 w-40 text-right">Subtotal</th>
                                <th class="px-4 py-4 w-10 text-center"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <template x-for="(item, index) in items" :key="index">
                                <tr class="hover:bg-blue-50/30 transition group">
                                    {{-- Input Bahan --}}
                                    <td class="px-6 py-3 align-top">
                                        <div class="flex flex-col gap-2">
                                            <select x-model="item.child_product_id" @change="updateCostFromMaterial(index, $event)"
                                                    class="w-full text-xs font-bold text-slate-700 border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-slate-50 hover:bg-white transition-colors py-2">
                                                <option value="">-- Input Manual / Custom --</option>
                                                @foreach($materials as $mat)
                                                    <option value="{{ $mat->id }}" data-price="{{ $mat->base_price }}">
                                                        📦 {{ $mat->name }} (Stok: {{ $mat->stock }})
                                                    </option>
                                                @endforeach
                                            </select>
                                            <input x-show="!item.child_product_id" type="text" x-model="item.custom_name"
                                                   placeholder="Contoh: Listrik / Upah Jahit"
                                                   class="w-full text-xs border-0 border-b border-slate-200 bg-transparent placeholder-slate-400 focus:ring-0 focus:border-blue-500 px-1 py-1 transition-all">
                                        </div>
                                    </td>

                                    {{-- Qty --}}
                                    <td class="px-4 py-3 align-top">
                                        <input type="number" step="0.0001" x-model="item.quantity"
                                               class="w-full text-center text-sm font-bold text-slate-800 border-slate-200 rounded-lg focus:ring-blue-500 focus:border-blue-500 py-2">
                                    </td>

                                    {{-- Cost --}}
                                    <td class="px-4 py-3 align-top">
                                        <div class="relative">
                                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs font-medium">Rp</span>
                                            <input type="number" step="1" x-model="item.cost" :readonly="!!item.child_product_id"
                                                   class="w-full text-right pl-8 pr-3 py-2 text-sm font-bold border-slate-200 rounded-lg focus:ring-blue-500 focus:border-blue-500 transition-colors"
                                                   :class="item.child_product_id ? 'bg-slate-100 text-slate-500 cursor-not-allowed' : 'bg-white text-slate-800'">
                                        </div>
                                    </td>

                                    {{-- Subtotal --}}
                                    <td class="px-6 py-3 align-top text-right pt-4">
                                        <span class="font-black text-slate-700" x-text="formatRupiah(item.quantity * item.cost)"></span>
                                    </td>

                                    {{-- Hapus --}}
                                    <td class="px-4 py-3 align-top text-center pt-3">
                                        <button @click="removeItem(index)" class="w-8 h-8 rounded-full flex items-center justify-center text-slate-300 hover:text-red-500 hover:bg-red-50 transition"><i class="fas fa-times"></i></button>
                                    </td>
                                </tr>
                            </template>
                            <tr x-show="items.length === 0">
                                <td colspan="5" class="py-12 text-center text-slate-400">
                                    <div class="flex flex-col items-center gap-2">
                                        <i class="fas fa-clipboard-list text-3xl opacity-20"></i>
                                        <p class="text-sm">Belum ada komponen biaya. Klik tombol "Tambah Item" diatas.</p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                        <tfoot class="bg-slate-50/50 border-t border-slate-200">
                            <tr>
                                <td colspan="3" class="px-6 py-4 text-right">
                                    <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Total HPP (Modal Dasar)</span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <span class="text-lg font-black text-slate-800" x-text="formatRupiah(totalHpp)"></span>
                                </td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            {{-- 2. ANALISA BEP (CARD) --}}
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="p-6 border-b border-slate-100 flex items-center gap-3">
                    <span class="w-10 h-10 rounded-xl bg-indigo-100 text-indigo-600 flex items-center justify-center text-lg"><i class="fas fa-chart-pie"></i></span>
                    <div>
                        <h3 class="font-bold text-lg text-slate-800">Analisa BEP (Titik Impas)</h3>
                        <p class="text-xs text-slate-400">Hitung kapan modal Anda kembali berdasarkan biaya operasional.</p>
                    </div>
                </div>

                <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-8 items-center">

                    {{-- Input --}}
                    <div class="space-y-5">
                        <div>
                            <label class="flex justify-between text-xs font-bold text-slate-600 uppercase mb-2">
                                Biaya Operasional Tetap (Sebulan)
                                <i class="fas fa-question-circle text-slate-300 cursor-help" title="Total Gaji, Listrik, Sewa, Internet, dll"></i>
                            </label>
                            <div class="relative">
                                <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 font-bold">Rp</span>
                                <input type="number" x-model="fixedCost"
                                       class="w-full pl-10 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-slate-700 font-bold focus:bg-white focus:ring-indigo-500 focus:border-indigo-500 transition-colors"
                                       placeholder="0">
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-600 uppercase mb-2">Target Penjualan Harian</label>
                            <div class="flex items-center gap-3">
                                <div class="relative flex-1">
                                    <input type="number" x-model="salesPerDay"
                                           class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-slate-700 font-bold focus:bg-white focus:ring-indigo-500 focus:border-indigo-500 transition-colors"
                                           placeholder="10">
                                </div>
                                <span class="text-sm font-bold text-slate-400">Unit/Hari</span>
                            </div>
                        </div>
                    </div>

                    {{-- Result Box --}}
                    <div class="bg-gradient-to-br from-indigo-500 to-purple-600 rounded-2xl p-6 text-white shadow-lg shadow-indigo-200 relative overflow-hidden">
                        <div class="absolute top-0 right-0 -mr-6 -mt-6 w-24 h-24 bg-white opacity-10 rounded-full blur-2xl"></div>

                        <div class="grid grid-cols-2 gap-4 text-center relative z-10">
                            <div class="p-2 border-r border-white/20">
                                <span class="block text-[10px] font-bold uppercase opacity-80 mb-1">Target Jual (BEP)</span>
                                <span class="block text-2xl font-black" x-text="formatRupiah(bepUnit, false)"></span>
                                <span class="text-[10px] opacity-80">Unit Terjual</span>
                            </div>
                            <div class="p-2">
                                <span class="block text-[10px] font-bold uppercase opacity-80 mb-1">Balik Modal Dalam</span>
                                <span class="block text-2xl font-black" x-text="daysToBep"></span>
                                <span class="text-[10px] opacity-80">Hari Kerja</span>
                            </div>
                        </div>

                        <div class="mt-4 pt-4 border-t border-white/20 text-center relative z-10">
                            <p class="text-[11px] leading-relaxed opacity-90" x-show="profit > 0 && fixedCost > 0">
                                <i class="fas fa-info-circle mr-1"></i>
                                Anda harus menjual <span class="font-bold border-b border-white/50">@{{ formatRupiah(bepUnit, false) }} unit</span> sebulan untuk menutup biaya operasional Rp @{{ formatRupiah(fixedCost, false) }}.
                            </p>
                            <p class="text-[11px] font-bold bg-white/20 py-1 rounded text-center" x-show="profit <= 0">
                                <i class="fas fa-exclamation-triangle mr-1"></i> Margin Minus! Perbaiki Harga Jual.
                            </p>
                            <p class="text-[11px]" x-show="profit > 0 && fixedCost == 0">
                                Isi biaya operasional untuk melihat analisa BEP.
                            </p>
                        </div>
                    </div>

                </div>
            </div>

        </div>

        {{-- KOLOM KANAN: SIMULASI HARGA (Sticky - 4 Kolom) --}}
        <div class="lg:col-span-4 space-y-6">
            <div class="bg-white rounded-2xl shadow-lg border border-slate-200 sticky top-6 overflow-hidden">
                <div class="p-5 bg-slate-900 text-white flex justify-between items-center">
                    <h3 class="font-bold flex items-center gap-2"><i class="fas fa-calculator text-slate-400"></i> Simulasi Harga</h3>
                    <span class="text-[10px] bg-slate-700 px-2 py-1 rounded font-bold uppercase tracking-wider text-slate-300">Live Preview</span>
                </div>

                <div class="p-6 space-y-6">

                    {{-- Summary HPP --}}
                    <div class="flex justify-between items-end border-b border-slate-100 pb-4">
                        <span class="text-xs font-bold text-slate-500 uppercase">Total Modal (HPP)</span>
                        <span class="text-xl font-bold text-slate-700 tracking-tight" x-text="formatRupiah(totalHpp)"></span>
                    </div>

                    {{-- Margin Slider --}}
                    <div>
                        <div class="flex justify-between items-center mb-3">
                            <label class="text-xs font-bold text-slate-800 uppercase">Target Margin</label>
                            <span class="text-sm font-black text-blue-600 bg-blue-50 px-2 py-0.5 rounded" x-text="marginPercent + '%'"></span>
                        </div>
                        <input type="range" min="0" max="200" step="5" x-model="marginPercent" class="w-full h-2 bg-slate-200 rounded-lg appearance-none cursor-pointer accent-blue-600 hover:accent-blue-700 transition-all">

                        {{-- Suggestion Box --}}
                        <div class="mt-4 bg-amber-50 border border-amber-100 p-3 rounded-xl flex justify-between items-center cursor-pointer hover:bg-amber-100 transition group"
                             @click="sellingPrice = suggestedPrice">
                            <div>
                                <span class="block text-[10px] font-bold text-amber-700 uppercase">Rekomendasi Harga</span>
                                <span class="text-xs text-amber-600">Klik untuk menggunakan</span>
                            </div>
                            <span class="text-lg font-black text-amber-700 flex items-center gap-1 group-hover:scale-105 transition-transform">
                                <span x-text="formatRupiah(suggestedPrice)"></span>
                                <i class="fas fa-check-circle text-sm opacity-50 group-hover:opacity-100"></i>
                            </span>
                        </div>
                    </div>

                    <div class="relative">
                        <div class="absolute inset-0 flex items-center" aria-hidden="true">
                            <div class="w-full border-t border-dashed border-slate-200"></div>
                        </div>
                        <div class="relative flex justify-center">
                            <span class="bg-white px-2 text-xs text-slate-400 font-bold uppercase">Keputusan Anda</span>
                        </div>
                    </div>

                    {{-- Final Price Input --}}
                    <div>
                        <label class="block text-center text-xs font-bold text-slate-500 uppercase mb-2">Harga Jual Final</label>
                        <div class="relative group">
                            <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 font-bold text-lg group-focus-within:text-blue-500 transition-colors">Rp</span>
                            <input type="number" x-model="sellingPrice"
                                   class="w-full pl-12 pr-4 py-4 text-center text-3xl font-black text-slate-800 border-2 border-slate-200 rounded-2xl focus:ring-0 focus:border-blue-600 transition-all shadow-sm">
                        </div>
                    </div>

                    {{-- Profit Indicators --}}
                    <div class="grid grid-cols-2 gap-3">
                        <div class="p-3 rounded-xl border flex flex-col items-center justify-center gap-1 transition-colors duration-300"
                             :class="profit > 0 ? 'bg-emerald-50 border-emerald-100 text-emerald-700' : 'bg-red-50 border-red-100 text-red-700'">
                            <span class="text-[9px] font-black uppercase opacity-70">Profit Bersih</span>
                            <span class="text-sm font-bold" x-text="formatRupiah(profit)"></span>
                        </div>
                        <div class="p-3 rounded-xl border flex flex-col items-center justify-center gap-1 transition-colors duration-300"
                             :class="realMargin >= 20 ? 'bg-blue-50 border-blue-100 text-blue-700' : (realMargin > 0 ? 'bg-orange-50 border-orange-100 text-orange-700' : 'bg-red-50 border-red-100 text-red-700')">
                            <span class="text-[9px] font-black uppercase opacity-70">Margin Real</span>
                            <span class="text-sm font-bold" x-text="realMargin + '%'"></span>
                        </div>
                    </div>

                </div>
            </div>
        </div>

    </div>
</div>

{{-- SCRIPT LOGIC (TIDAK BERUBAH) --}}
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
