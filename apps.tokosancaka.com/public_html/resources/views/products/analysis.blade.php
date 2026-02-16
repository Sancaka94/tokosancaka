@extends('layouts.app')

@section('title', 'Analisa HPP - ' . $product->name)

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8"
     x-data="hppCalculator({{ $product->recipeItems }}, {{ $product->base_price ?? 0 }}, {{ $product->sell_price ?? 0 }})">

    {{-- HEADER --}}
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">Kalkulator HPP & Margin</h1>
            <p class="text-sm text-slate-500">Analisa biaya produksi untuk: <span class="font-bold text-blue-600">{{ $product->name }}</span></p>
        </div>
        <div class="flex gap-2">
            <button type="button" @click="resetToDefault()" class="px-4 py-2 bg-white border border-slate-300 text-slate-600 rounded-lg text-sm font-bold hover:bg-slate-50 transition">
                <i class="fas fa-undo mr-1"></i> Reset Data
            </button>
            <button type="button" @click="saveRecipe()" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-bold hover:bg-blue-700 transition shadow-lg shadow-blue-200">
                <i class="fas fa-save mr-1"></i> Simpan Resep & Harga
            </button>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- KOLOM KIRI: INPUT KOMPONEN BIAYA (TABEL) --}}
        <div class="lg:col-span-2 space-y-6">

            <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="p-4 bg-slate-50 border-b border-slate-200 flex justify-between items-center">
                    <h3 class="font-bold text-slate-700"><i class="fas fa-list-ul mr-2"></i> Komponen Biaya (BOM)</h3>
                    <button @click="addItem()" class="text-xs bg-green-100 text-green-700 px-3 py-1.5 rounded-lg font-bold hover:bg-green-200 transition">
                        <i class="fas fa-plus"></i> Tambah Item
                    </button>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-slate-50 text-slate-500 uppercase text-[10px] font-bold tracking-wider">
                            <tr>
                                <th class="px-4 py-3 min-w-[200px]">Nama Komponen / Bahan</th>
                                <th class="px-4 py-3 w-24 text-center">Qty</th>
                                <th class="px-4 py-3 w-32 text-right">Biaya Satuan</th>
                                <th class="px-4 py-3 w-32 text-right">Subtotal</th>
                                <th class="px-4 py-3 w-10 text-center"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <template x-for="(item, index) in items" :key="index">
                                <tr class="hover:bg-slate-50 transition">
                                    {{-- Input Nama / Pilih Bahan --}}
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

                                            {{-- Jika pilih Custom, muncul input teks --}}
                                            <input x-show="!item.child_product_id" type="text" x-model="item.custom_name"
                                                   placeholder="Contoh: Listrik / Upah Jahit"
                                                   class="w-full text-xs border-slate-300 rounded-lg bg-slate-50 placeholder-slate-400 focus:ring-blue-500">
                                        </div>
                                    </td>

                                    {{-- Input Qty --}}
                                    <td class="px-4 py-2">
                                        <input type="number" step="0.001" x-model="item.quantity"
                                               class="w-full text-center text-xs font-bold border-slate-300 rounded-lg focus:ring-blue-500">
                                    </td>

                                    {{-- Input Biaya (Readonly jika ambil dari bahan, Editable jika custom) --}}
                                    <td class="px-4 py-2">
                                        <div class="relative">
                                            <span class="absolute left-2 top-1/2 -translate-y-1/2 text-slate-400 text-[10px]">Rp</span>
                                            <input type="number" step="1" x-model="item.cost"
                                                   :readonly="!!item.child_product_id"
                                                   :class="item.child_product_id ? 'bg-slate-100 text-slate-500' : 'bg-white text-slate-800'"
                                                   class="w-full text-right pl-6 pr-2 py-1.5 text-xs font-bold border-slate-300 rounded-lg focus:ring-blue-500">
                                        </div>
                                    </td>

                                    {{-- Subtotal (Otomatis) --}}
                                    <td class="px-4 py-2 text-right font-bold text-slate-700">
                                        <span x-text="formatRupiah(item.quantity * item.cost)"></span>
                                    </td>

                                    {{-- Hapus --}}
                                    <td class="px-4 py-2 text-center">
                                        <button @click="removeItem(index)" class="text-slate-300 hover:text-red-500 transition">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </td>
                                </tr>
                            </template>

                            {{-- Baris Kosong jika tidak ada data --}}
                            <tr x-show="items.length === 0">
                                <td colspan="5" class="py-8 text-center text-slate-400 italic text-sm">
                                    Belum ada komponen biaya. Klik "Tambah Item" untuk memulai.
                                </td>
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

            {{-- TIPS BOX --}}
            <div class="bg-blue-50 border border-blue-100 rounded-xl p-4 flex gap-3">
                <div class="text-blue-500"><i class="fas fa-info-circle text-xl"></i></div>
                <div class="text-xs text-blue-800">
                    <p class="font-bold mb-1">Tips Menghitung HPP:</p>
                    <ul class="list-disc ml-4 space-y-1">
                        <li>Untuk <b>Jasa</b> (ex: Laundry), masukkan biaya deterjen per kg, plastik, listrik, dan upah karyawan per pengerjaan.</li>
                        <li>Untuk <b>Manufaktur</b> (ex: Percetakan), masukkan bahan baku (Kertas, Tinta) dan biaya overhead.</li>
                        <li>Pastikan satuan Qty sesuai (Misal: 0.05 Liter, 2 Lembar, 1 Jam).</li>
                    </ul>
                </div>
            </div>

        </div>

        {{-- KOLOM KANAN: SIMULASI HARGA JUAL --}}
        <div class="lg:col-span-1">
            <div class="bg-white rounded-xl shadow-lg border border-slate-200 sticky top-6">
                <div class="p-4 bg-slate-800 text-white rounded-t-xl">
                    <h3 class="font-bold"><i class="fas fa-calculator mr-2"></i> Simulasi Laba</h3>
                </div>

                <div class="p-5 space-y-5">

                    {{-- 1. HPP (Readonly) --}}
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">HPP / Modal Dasar</label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 font-bold">Rp</span>
                            <input type="text" :value="formatRupiah(totalHpp, false)" readonly
                                   class="w-full pl-10 pr-4 py-3 bg-slate-100 border-slate-200 rounded-xl text-lg font-bold text-slate-600">
                        </div>
                    </div>

                    <hr class="border-dashed border-slate-200">

                    {{-- 2. Target Margin (%) --}}
                    <div>
                        <label class="flex justify-between text-xs font-bold text-slate-500 uppercase mb-1">
                            <span>Ingin Margin Berapa?</span>
                            <span class="text-blue-600" x-text="marginPercent + '%'"></span>
                        </label>
                        <div class="flex items-center gap-3">
                            <input type="range" min="0" max="100" step="5" x-model="marginPercent" class="w-full h-2 bg-slate-200 rounded-lg appearance-none cursor-pointer accent-blue-600">
                            <input type="number" x-model="marginPercent" class="w-16 py-1 px-2 text-center text-sm font-bold border-slate-300 rounded-lg focus:ring-blue-500">
                        </div>
                        <p class="text-[10px] text-slate-400 mt-1 italic">Geser slider untuk simulasi persentase laba.</p>
                    </div>

                    {{-- 3. Saran Harga Jual --}}
                    <div class="bg-amber-50 border border-amber-100 p-3 rounded-xl flex justify-between items-center">
                        <span class="text-xs font-bold text-amber-700">Saran Harga Jual:</span>
                        <span class="text-lg font-black text-amber-600" x-text="formatRupiah(suggestedPrice)"></span>
                    </div>

                    <hr class="border-dashed border-slate-200">

                    {{-- 4. Harga Jual Final (Input) --}}
                    <div>
                        <label class="block text-xs font-bold text-slate-800 uppercase mb-1">Tetapkan Harga Jual</label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 font-bold">Rp</span>
                            <input type="number" x-model="sellingPrice"
                                   class="w-full pl-10 pr-4 py-3 border-2 border-slate-200 rounded-xl text-xl font-black text-slate-800 focus:ring-0 focus:border-blue-600 transition-colors shadow-sm placeholder-slate-300">
                        </div>
                    </div>

                    {{-- 5. Estimasi Laba --}}
                    <div class="grid grid-cols-2 gap-3 text-center">
                        <div class="p-3 rounded-xl" :class="profit > 0 ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'">
                            <span class="block text-[10px] font-bold uppercase opacity-70">Nominal Laba</span>
                            <span class="block text-sm font-black" x-text="formatRupiah(profit)"></span>
                        </div>
                        <div class="p-3 rounded-xl" :class="profit > 0 ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'">
                            <span class="block text-[10px] font-bold uppercase opacity-70">Margin Real</span>
                            <span class="block text-sm font-black" x-text="realMargin + '%'"></span>
                        </div>
                    </div>

                </div>
            </div>
        </div>

    </div>
</div>

{{-- ALPINE JS LOGIC --}}
<script>
    function hppCalculator(initialItems, currentBasePrice, currentSellPrice) {
        return {
            // Data Utama
            items: [], // Array komponen biaya
            sellingPrice: currentSellPrice,
            marginPercent: 30, // Default margin target 30%

            // Inisialisasi
            init() {
                // Konversi data dari database ke format JS
                if (initialItems && initialItems.length > 0) {
                    this.items = initialItems.map(item => ({
                        child_product_id: item.child_product_id,
                        custom_name: item.custom_item_name,
                        quantity: parseFloat(item.quantity),
                        cost: parseFloat(item.cost_per_unit) // Nanti perlu logic ambil harga stok terkini di controller
                    }));
                } else {
                    // Jika kosong, tambah 1 baris default
                    this.addItem();
                }
            },

            // Tambah Baris Baru
            addItem() {
                this.items.push({
                    child_product_id: '',
                    custom_name: '',
                    quantity: 1,
                    cost: 0
                });
            },

            // Hapus Baris
            removeItem(index) {
                this.items.splice(index, 1);
            },

            // Reset Data
            resetToDefault() {
                if(confirm('Reset semua kalkulasi?')) {
                    this.items = [];
                    this.addItem();
                    this.sellingPrice = 0;
                }
            },

            // Update harga otomatis saat bahan baku dipilih di dropdown
            updateCostFromMaterial(index, event) {
                const selectedOption = event.target.options[event.target.selectedIndex];
                const price = selectedOption.getAttribute('data-price');

                if (price) {
                    this.items[index].cost = parseFloat(price);
                    this.items[index].custom_name = null; // Clear nama custom jika pilih bahan
                } else {
                    this.items[index].cost = 0; // Reset jika pilih opsi kosong
                }
            },

            // COMPUTED PROPERTIES (Hitungan Otomatis)

            // 1. Total HPP
            get totalHpp() {
                return this.items.reduce((sum, item) => {
                    return sum + (item.quantity * item.cost);
                }, 0);
            },

            // 2. Saran Harga Jual (Berdasarkan HPP + Margin Target)
            get suggestedPrice() {
                if (this.totalHpp === 0) return 0;
                // Rumus Markup: HPP + (HPP * Margin%)
                return this.totalHpp + (this.totalHpp * (this.marginPercent / 100));
            },

            // 3. Profit (Laba Bersih)
            get profit() {
                return this.sellingPrice - this.totalHpp;
            },

            // 4. Margin Real (%)
            get realMargin() {
                if (this.totalHpp === 0 || this.sellingPrice === 0) return 0;
                // Rumus Margin: (Profit / HPP) * 100
                return ((this.profit / this.totalHpp) * 100).toFixed(1);
            },

            // Format Rupiah UI
            formatRupiah(number, prefix = true) {
                if (isNaN(number)) return '0';
                let val = Math.round(number).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
                return prefix ? 'Rp ' + val : val;
            },

            // Simpan Data ke Server
            async saveRecipe() {
                if (this.items.length === 0 || this.totalHpp <= 0) {
                    alert('Mohon isi komponen biaya terlebih dahulu.');
                    return;
                }

                if (!confirm('Simpan resep HPP ini? Harga dasar (Base Price) produk akan diperbarui.')) return;

                try {
                    // Kirim ke Controller HppController@updateRecipe
                    const response = await fetch("{{ route('hpp.updateRecipe', $product->id) }}", { // Sesuaikan route
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify({
                            items: this.items,
                            new_selling_price: this.sellingPrice // Opsional jika ingin update harga jual sekalian
                        })
                    });

                    const result = await response.json();

                    if (result.status === 'success') {
                        alert('Berhasil disimpan!');
                        window.location.reload();
                    } else {
                        alert('Gagal: ' + result.message);
                    }

                } catch (error) {
                    console.error(error);
                    alert('Terjadi kesalahan sistem.');
                }
            }
        };
    }
</script>
@endsection
