{{-- MODAL PILIH VARIAN PRODUK (TAMBAHAN BARU) --}}
<div x-show="variantSelectorOpen"
     style="display: none;"
     class="fixed inset-0 z-[60] flex items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4"
     x-transition.opacity>

    <div class="bg-white w-full max-w-md rounded-2xl shadow-2xl border border-slate-200 overflow-hidden flex flex-col max-h-[80vh]"
         @click.away="variantSelectorOpen = false"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 scale-95 translate-y-4"
         x-transition:enter-end="opacity-100 scale-100 translate-y-0">

        <div class="px-5 py-4 border-b border-slate-100 bg-slate-50 flex justify-between items-center">
            <div>
                <h3 class="font-bold text-lg text-slate-800 leading-tight">Pilih Varian</h3>
                <p class="text-xs text-slate-500 font-medium" x-text="selectedProductForVariant?.name"></p>
            </div>
            <button @click="variantSelectorOpen = false" class="text-slate-400 hover:text-red-500 transition"><i class="fas fa-times text-lg"></i></button>
        </div>

        <div class="p-2 overflow-y-auto bg-slate-50 grow">
            <template x-if="productVariants.length === 0">
                <div class="text-center py-8 text-slate-400">
                    <i class="fas fa-spinner fa-spin text-2xl mb-2"></i>
                    <p class="text-xs">Memuat varian...</p>
                </div>
            </template>

            <div class="grid grid-cols-1 gap-2">
                <template x-for="variant in productVariants" :key="variant.id">
                    <button @click="addVariantToCart(variant)"
                            class="flex items-center justify-between p-3 bg-white border border-slate-200 rounded-xl hover:border-indigo-400 hover:shadow-md transition-all group text-left w-full relative overflow-hidden"
                            :disabled="variant.stock <= 0">

                        <div class="flex flex-col">
                            <span class="font-bold text-slate-700 text-sm group-hover:text-indigo-600" x-text="variant.name"></span>
                            <span class="text-[10px] text-slate-400" x-show="variant.sku">SKU: <span x-text="variant.sku"></span></span>
                        </div>

                        <div class="flex flex-col items-end">
                            <span class="font-black text-emerald-600 text-sm" x-text="rupiah(variant.price)"></span>
                            <span class="text-[10px] font-bold px-2 py-0.5 rounded bg-slate-100 text-slate-500"
                                  :class="variant.stock <= 0 ? 'bg-red-100 text-red-600' : ''"
                                  x-text="variant.stock > 0 ? 'Stok: ' + variant.stock : 'Habis'"></span>
                        </div>

                        <div x-show="variant.stock <= 0" class="absolute inset-0 bg-white/60 cursor-not-allowed"></div>
                    </button>
                </template>
            </div>
        </div>
    </div>
</div>
