@extends('layouts.app')

@section('title', 'Analisa HPP - ' . $product->name)

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8"
     x-data="hppCalculator({{ $product->recipeItems }}, {{ $product->base_price ?? 0 }}, {{ $product->sell_price ?? 0 }})">

    {{-- HEADER --}}
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">Kalkulator HPP & BEP</h1>
            <p class="text-sm text-slate-500">Analisa biaya dan potensi keuntungan: <span class="font-bold text-blue-600">{{ $product->name }}</span></p>
        </div>
        <div class="flex gap-2">
            <button type="button" @click="resetToDefault()" class="px-4 py-2 bg-white border border-slate-300 text-slate-600 rounded-lg text-sm font-bold hover:bg-slate-50 transition">
                <i class="fas fa-undo mr-1"></i> Reset
            </button>
            <button type="button" @click="saveRecipe()" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-bold hover:bg-blue-700 transition shadow-lg shadow-blue-200">
                <i class="fas fa-save mr-1"></i> Simpan Analisa
            </button>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- KOLOM KIRI: INPUT KOMPONEN BIAYA (BOM) --}}
        <div class="lg:col-span-2 space-y-6">

            <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="p-4 bg-slate-50 border-b border-slate-200 flex justify-between items-center">
                    <h3 class="font-bold text-slate-700"><i class="fas fa-list-ul mr-2"></i> Komponen HPP (Modal Pokok)</h3>
                    <button @click="addItem()" class="text-xs bg-green-100 text-green-700 px-3 py-1.5 rounded-lg font-bold hover:bg-green-200 transition">
                        <i class="fas fa-plus"></i> Tambah Biaya
                    </button>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-slate-50 text-slate-500 uppercase text-[10px] font-bold tracking-wider">
                            <tr>
                                <th class="px-4 py-3 min-w-[200px]">Bahan / Biaya</th>
                                <th class="px-4 py-3 w-24 text-center">Qty</th>
                                <th class="px-4 py-3 w-32 text-right">Biaya Satuan</th>
                                <th class="px-4 py-3 w-32 text-right">Subtotal</th>
                                <th class="px-4 py-3 w-10 text-center"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <template x-for="(item, index) in items" :key="index">
                                <tr class="hover:bg-slate-50 transition">
                                    {{-- Input Bahan --}}
                                    <td class="px-4 py-2">
                                        <div class="flex flex-col gap-1">
                                            <select x-model="item.child_product_id" @change="updateCostFromMaterial(index, $event)"
                                                    class="w-full text-xs border-slate-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                                                <option value="">-- Biaya Custom / Tenaga --</option>
                                                @foreach($materials as $mat)
                                                    <option value="{{ $mat->id }}" data-price="{{ $mat->base_price }}">
                                                        {{ $mat->name }} (Stok: {{ $mat->stock }})
                                                    </option>
                                                @endforeach
                                            </select>
                                            <input x-show="!item.child_product_id" type="text" x-model="item.custom_name"
                                                   placeholder="Nama biaya (misal: Listrik)"
                                                   class="w-full text-left pl-6 pr-2 py-1.5 text-xs font-bold border-slate-300 rounded-lg focus:ring-blue-500">
                                        </div>
                                    </td>
                                    {{-- Qty --}}
                                    <td class="px-4 py-2">
                                        <input type="number" step="0.001" x-model="item.quantity" class="w-full text-center pl-6 pr-2 py-1.5 text-xs font-bold border-slate-300 rounded-lg focus:ring-blue-500">
                                    </td>
                                    {{-- Cost --}}
                                    <td class="px-4 py-2">
                                        <div class="relative">
                                            <span class="absolute left-2 top-1/2 -translate-y-1/2 text-slate-400 text-[10px]">Rp</span>
                                            <input type="number" step="1" x-model="item.cost" :readonly="!!item.child_product_id"
                                                   class="w-full text-right pl-6 pr-2 py-1.5 text-xs font-bold border-slate-300 rounded-lg focus:ring-blue-500"
                                                   :class="item.child_product_id ? 'bg-slate-100 text-slate-500' : 'bg-white text-slate-800'">
                                        </div>
                                    </td>
                                    {{-- Subtotal --}}
                                    <td class="px-4 py-2 text-right font-bold text-slate-700">
                                        <span x-text="formatRupiah(item.quantity * item.cost)"></span>
                                    </td>
                                    {{-- Hapus --}}
                                    <td class="px-4 py-2 text-center">
                                        <button @click="removeItem(index)" class="text-slate-300 hover:text-red-500 transition"><i class="fas fa-trash-alt"></i></button>
                                    </td>
                                </tr>
                            </template>
                            <tr x-show="items.length === 0">
                                <td colspan="5" class="py-8 text-center text-slate-400 italic text-sm">Belum ada data biaya.</td>
                            </tr>
                        </tbody>
                        <tfoot class="bg-slate-50 border-t border-slate-200">
                            <tr>
                                <td colspan="3" class="px-4 py-3 text-right font-bold text-slate-600 text-sm">TOTAL HPP (MODAL):</td>
                                <td class="px-4 py-3 text-right font-black text-slate-800 text-base">
                                    <span x-text="formatRupiah(totalHpp)"></span>
                                </td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            {{-- 🟢 FITUR BARU: ANALISA BEP (BREAK EVEN POINT) --}}
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5">
                <h3 class="font-bold text-slate-700 mb-4 flex items-center gap-2">
                    <div class="bg-indigo-100 text-indigo-600 p-1.5 rounded-lg"><i class="fas fa-chart-line"></i></div>
                    Analisa BEP & Waktu Balik Modal
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                    {{-- Input Parameter BEP --}}
                    <div class="space-y-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-1">
                                Biaya Operasional Tetap (Per Bulan)
                                <i class="fas fa-info-circle text-slate-300 ml-1" title="Contoh: Sewa tempat, Gaji Karyawan, Internet yang dibebankan ke produk ini"></i>
                            </label>
                            <div class="relative">
                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 font-bold">Rp</span>
                                <input type="number" x-model="fixedCost"
                                       class="w-full pl-10 pr-4 py-2 border border-slate-300 rounded-lg text-sm font-bold focus:ring-indigo-500 focus:border-indigo-500"
                                       placeholder="0">
                            </div>
                            <p class="text-[10px] text-slate-400 mt-1">Biaya tetap yang harus ditutup oleh profit produk ini.</p>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Estimasi Penjualan (Unit / Hari)</label>
                            <div class="flex items-center gap-2">
                                <input type="number" x-model="salesPerDay"
                                       class="w-full px-4 py-2 border border-slate-300 rounded-lg text-sm font-bold focus:ring-indigo-500 focus:border-indigo-500"
                                       placeholder="10">
                                <span class="text-xs font-bold text-slate-500">Pcs/Hari</span>
                            </div>
                        </div>
                    </div>

                    {{-- Hasil Perhitungan BEP --}}
                    <div class="bg-indigo-50 rounded-xl p-4 border border-indigo-100 flex flex-col justify-center">
                        <div class="grid grid-cols-2 gap-4 text-center">

                            {{-- BEP Qty --}}
                            <div>
                                <span class="block text-[10px] font-bold text-indigo-400 uppercase">Titik Impas (BEP)</span>
                                <span class="block text-xl font-black text-indigo-700" x-text="formatRupiah(bepUnit, false)"></span>
                                <span class="text-[10px] text-indigo-500">Unit Terjual</span>
                            </div>

                            {{-- Waktu BEP --}}
                            <div>
                                <span class="block text-[10px] font-bold text-indigo-400 uppercase">Waktu Balik Modal</span>
                                <span class="block text-xl font-black text-indigo-700" x-text="daysToBep"></span>
                                <span class="text-[10px] text-indigo-500">Hari Kerja</span>
                            </div>

                        </div>

                        <div class="mt-4 pt-3 border-t border-indigo-200/50">
                            <p class="text-xs text-indigo-800 text-center leading-relaxed">
                                <span x-show="profit > 0 && fixedCost > 0">
                                    Anda perlu menjual <span class="font-bold" x-text="formatRupiah(bepUnit, false)"></span> unit untuk menutup biaya operasional.
                                    Dengan penjualan <span x-text="salesPerDay"></span>/hari, modal akan kembali dalam <span class="font-bold" x-text="daysToBep"></span> hari.
                                </span>
                                <span x-show="profit <= 0" class="text-red-500 font-bold">
                                    <i class="fas fa-exclamation-triangle"></i> Margin minus/nol. Tidak bisa hitung BEP.
                                </span>
                                <span x-show="profit > 0 && fixedCost == 0">
                                    <i class="fas fa-check-circle"></i> Tidak ada biaya tetap. Profit langsung dihitung dari unit pertama.
                                </span>
                            </p>
                        </div>
                    </div>

                </div>
            </div>

        </div>

        {{-- KOLOM KANAN: SIMULASI HARGA --}}
        <div class="lg:col-span-1 space-y-6">
            <div class="bg-white rounded-xl shadow-lg border border-slate-200 sticky top-6">
                <div class="p-4 bg-slate-800 text-white rounded-t-xl">
                    <h3 class="font-bold"><i class="fas fa-tags mr-2"></i> Simulasi Harga Jual</h3>
                </div>

                <div class="p-5 space-y-5">

                    {{-- HPP --}}
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Total HPP (Modal)</label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 font-bold">Rp</span>
                            <input type="text" :value="formatRupiah(totalHpp, false)" readonly class="w-full pl-10 pr-4 py-2 bg-slate-100 border-slate-200 rounded-lg font-bold text-slate-600">
                        </div>
                    </div>

                    {{-- Slider Margin --}}
                    <div>
                        <label class="flex justify-between text-xs font-bold text-slate-500 uppercase mb-1">
                            <span>Target Margin (%)</span>
                            <span class="text-blue-600" x-text="marginPercent + '%'"></span>
                        </label>
                        <div class="flex items-center gap-2">
                            <input type="range" min="0" max="200" step="5" x-model="marginPercent" class="w-full h-2 bg-slate-200 rounded-lg appearance-none cursor-pointer accent-blue-600">
                            <input type="number" x-model="marginPercent" class="w-14 py-1 px-1 text-center text-xs font-bold border-slate-300 rounded-lg">
                        </div>

                        {{-- Saran Harga --}}
                        <div class="mt-2 bg-amber-50 border border-amber-100 p-2 rounded-lg flex justify-between items-center">
                            <span class="text-[10px] font-bold text-amber-700">Saran Harga:</span>
                            <span class="text-sm font-black text-amber-600 cursor-pointer hover:underline"
                                  @click="sellingPrice = suggestedPrice" title="Klik untuk pakai harga ini">
                                <span x-text="formatRupiah(suggestedPrice)"></span> <i class="fas fa-arrow-up text-[10px]"></i>
                            </span>
                        </div>
                    </div>

                    <hr class="border-dashed border-slate-200">

                    {{-- Harga Jual --}}
                    <div>
                        <label class="block text-xs font-bold text-slate-800 uppercase mb-1">Harga Jual Final</label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 font-bold">Rp</span>
                            <input type="number" x-model="sellingPrice" class="w-full pl-10 pr-4 py-3 border-2 border-slate-200 rounded-xl text-xl font-black text-slate-800 focus:ring-0 focus:border-blue-600">
                        </div>
                    </div>

                    {{-- Summary Profit --}}
                    <div class="grid grid-cols-2 gap-2 text-center">
                        <div class="p-2 rounded-lg border" :class="profit > 0 ? 'bg-green-50 border-green-100 text-green-700' : 'bg-red-50 border-red-100 text-red-700'">
                            <span class="block text-[9px] font-bold uppercase">Profit/Unit</span>
                            <span class="block text-sm font-black" x-text="formatRupiah(profit)"></span>
                        </div>
                        <div class="p-2 rounded-lg border" :class="profit > 0 ? 'bg-blue-50 border-blue-100 text-blue-700' : 'bg-red-50 border-red-100 text-red-700'">
                            <span class="block text-[9px] font-bold uppercase">Margin Real</span>
                            <span class="block text-sm font-black" x-text="realMargin + '%'"></span>
                        </div>
                    </div>

                </div>
            </div>
        </div>

    </div>
</div>

{{-- LOGIC JS --}}
<script>
    function hppCalculator(initialItems, currentBasePrice, currentSellPrice) {
        return {
            items: [],
            sellingPrice: currentSellPrice,
            marginPercent: 30,

            // Variabel BEP
            fixedCost: 0, // Biaya operasional tetap
            salesPerDay: 5, // Default estimasi penjualan

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

            addItem() {
                this.items.push({ child_product_id: '', custom_name: '', quantity: 1, cost: 0 });
            },

            removeItem(index) {
                this.items.splice(index, 1);
            },

            resetToDefault() {
                if(confirm('Reset kalkulator?')) {
                    this.items = []; this.addItem(); this.sellingPrice = 0; this.fixedCost = 0;
                }
            },

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

            // --- COMPUTED ---

            get totalHpp() {
                return this.items.reduce((sum, item) => sum + (item.quantity * item.cost), 0);
            },

            get suggestedPrice() {
                if (this.totalHpp === 0) return 0;
                return Math.ceil(this.totalHpp + (this.totalHpp * (this.marginPercent / 100)));
            },

            get profit() {
                return this.sellingPrice - this.totalHpp;
            },

            get realMargin() {
                if (this.totalHpp === 0 || this.sellingPrice === 0) return 0;
                return ((this.profit / this.sellingPrice) * 100).toFixed(1); // Margin on Sales
            },

            // --- BEP CALCULATION ---

            // BEP dalam Unit (Berapa pcs harus dijual untuk tutup Fixed Cost)
            get bepUnit() {
                if (this.profit <= 0 || this.fixedCost <= 0) return 0;
                // Rumus: Biaya Tetap / Profit per Unit
                return Math.ceil(this.fixedCost / this.profit);
            },

            // Waktu Balik Modal (Hari)
            get daysToBep() {
                if (this.bepUnit === 0 || this.salesPerDay <= 0) return '-';
                // Rumus: Total Unit BEP / Penjualan per Hari
                return Math.ceil(this.bepUnit / this.salesPerDay);
            },

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
