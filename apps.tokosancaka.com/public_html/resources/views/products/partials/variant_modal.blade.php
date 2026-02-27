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

                        {{-- Rows Varian & Sub Varian --}}
                        <template x-for="(variant, index) in variants" :key="index">
                            <div class="bg-white rounded-xl border border-slate-200 mb-4 overflow-hidden shadow-sm">

                                {{-- BARIS VARIAN UTAMA --}}
                                <div class="grid grid-cols-12 gap-3 items-center p-3 bg-slate-50 border-b border-slate-200">
                                    {{-- Nama --}}
                                    <div class="col-span-3">
                                        <input type="text" x-model="variant.name" placeholder="Cth: Merah"
                                               class="w-full px-3 py-2 rounded-lg border-slate-300 text-sm focus:ring-2 focus:ring-indigo-500 font-bold text-slate-700 bg-white transition-all">
                                    </div>

                                    {{-- Barcode --}}
                                    <div class="col-span-3 relative">
                                        <span class="absolute inset-y-0 left-0 pl-2 flex items-center text-slate-400"><i class="fas fa-barcode text-xs"></i></span>
                                        <input type="text" x-model="variant.barcode" placeholder="Scan..." @keydown.enter.prevent
                                               class="w-full pl-6 pr-2 py-2 rounded-lg border-slate-300 text-xs font-mono focus:ring-2 focus:ring-indigo-500 bg-white transition-all">
                                    </div>

                                    {{-- Harga --}}
                                    <div class="col-span-3 relative">
                                        <input type="number" x-model="variant.price" placeholder="0"
                                               class="w-full px-3 py-2 rounded-lg border-emerald-200 text-sm focus:ring-2 focus:ring-emerald-500 text-emerald-700 font-bold bg-white text-right transition-all">
                                    </div>

                                    {{-- Stok (Readonly jika punya sub varian) --}}
                                    <div class="col-span-2">
                                        <input type="number" x-model="variant.stock" placeholder="0" :readonly="variant.sub_variants && variant.sub_variants.length > 0"
                                               :class="variant.sub_variants && variant.sub_variants.length > 0 ? 'bg-slate-100 text-slate-400 cursor-not-allowed' : 'bg-white'"
                                               class="w-full px-3 py-2 rounded-lg border-slate-300 text-sm focus:ring-2 focus:ring-indigo-500 text-center transition-all">
                                    </div>

                                    {{-- Hapus Varian Utama --}}
                                    <div class="col-span-1 text-center">
                                        <button @click="removeVariantRow(index)" class="h-8 w-8 inline-flex items-center justify-center text-slate-400 hover:text-red-500 hover:bg-red-50 rounded-lg transition">
                                            <i class="fas fa-trash-alt text-xs"></i>
                                        </button>
                                    </div>
                                </div>

                               {{-- Hapus Varian Utama --}}
                                    <div class="col-span-1 text-center">
                                        <button @click="removeVariantRow(index)" class="h-8 w-8 inline-flex items-center justify-center text-slate-400 hover:text-red-500 hover:bg-red-50 rounded-lg transition">
                                            <i class="fas fa-trash-alt text-xs"></i>
                                        </button>
                                    </div>
                                </div>

                                {{-- MULAI KODE BARU: UI AREA SUB VARIAN --}}
                                <div class="p-3 bg-white">
                                    <div class="flex justify-between items-center mb-2">
                                        <span class="text-[10px] font-bold text-orange-500 uppercase tracking-wider"><i class="fas fa-code-branch"></i> Sub Varian (Anak)</span>
                                        <button @click.prevent="addSubVariantRow(index)" class="text-[10px] bg-orange-50 text-orange-600 px-2 py-1 rounded border border-orange-200 hover:bg-orange-100 font-bold">
                                            + Tambah Anak
                                        </button>
                                    </div>

                                    {{-- Looping Sub Varian --}}
                                    <div class="space-y-2 pl-4 border-l-2 border-orange-100">
                                        <template x-for="(sub, subIndex) in variant.sub_variants" :key="subIndex">
                                            <div class="grid grid-cols-12 gap-2 items-center">
                                                <div class="col-span-3">
                                                    <input type="text" x-model="sub.name" placeholder="Cth: Ukuran L" class="w-full px-2 py-1.5 text-xs border border-slate-300 rounded focus:ring-1 focus:ring-orange-500">
                                                </div>
                                                <div class="col-span-3">
                                                    <input type="text" x-model="sub.barcode" placeholder="Barcode" class="w-full px-2 py-1.5 text-xs font-mono border border-slate-300 rounded focus:ring-1 focus:ring-orange-500">
                                                </div>
                                                <div class="col-span-2">
                                                    <input type="number" x-model="sub.price" placeholder="Harga" class="w-full px-2 py-1.5 text-xs border border-emerald-300 text-emerald-700 font-bold rounded focus:ring-1 focus:ring-emerald-500 text-right">
                                                </div>
                                                <div class="col-span-2">
                                                    <input type="number" x-model="sub.stock" placeholder="Stok" @input="calculateTotalStock(index)" class="w-full px-2 py-1.5 text-xs border border-slate-300 rounded focus:ring-1 focus:ring-orange-500 text-center">
                                                </div>
                                                <div class="col-span-1">
                                                    <input type="number" x-model="sub.weight" placeholder="Berat(g)" class="w-full px-2 py-1.5 text-xs border border-slate-300 rounded focus:ring-1 focus:ring-orange-500 text-center" title="Berat dalam Gram">
                                                </div>
                                                <div class="col-span-1 text-center">
                                                    <button @click.prevent="removeSubVariantRow(index, subIndex); calculateTotalStock(index)" class="text-slate-400 hover:text-red-500 p-1">
                                                        <i class="fas fa-times text-xs"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </template>
                                        <template x-if="!variant.sub_variants || variant.sub_variants.length === 0">
                                            <p class="text-[9px] text-slate-400 italic">Tidak ada sub varian. Harga & stok mengikuti varian utama di atas.</p>
                                        </template>
                                    </div>
                                </div>
                                {{-- AKHIR KODE BARU: UI AREA SUB VARIAN --}}

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
