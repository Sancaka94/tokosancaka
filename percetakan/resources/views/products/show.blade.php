<div class="p-8">
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
        
        <div class="p-4 bg-slate-50 rounded-xl border border-slate-100 text-center">
            <p class="text-xs font-bold text-slate-400 uppercase mb-1">Sisa Stok</p>
            <p class="text-2xl font-black text-slate-800">{{ number_format($product->stock) }}</p>
            <p class="text-[10px] text-slate-400 uppercase">{{ $product->unit }}</p>
        </div>

        <div class="p-4 bg-slate-50 rounded-xl border border-slate-100 text-center">
            <p class="text-xs font-bold text-slate-400 uppercase mb-1">Terjual</p>
            <p class="text-2xl font-black text-slate-800">{{ number_format($product->sold) }}</p>
            <p class="text-[10px] text-slate-400 uppercase">Unit</p>
        </div>

        <div class="p-4 bg-blue-50 rounded-xl border border-blue-100 text-center col-span-2">
            <p class="text-xs font-bold text-blue-500 uppercase mb-1">Estimasi Keuntungan</p>
            <p class="text-2xl font-black text-blue-600">Rp {{ number_format($product->sell_price - $product->base_price, 0, ',', '.') }}</p>
            <p class="text-[10px] text-blue-400">Per satu {{ $product->unit }}</p>
        </div>
    </div>

    <div class="space-y-3">
        <div class="flex justify-between p-3 border-b border-slate-100">
            <span class="text-sm text-slate-500 font-medium">Supplier</span>
            <span class="text-sm text-slate-800 font-bold">{{ $product->supplier ?? '-' }}</span>
        </div>
        <div class="flex justify-between p-3 border-b border-slate-100">
            <span class="text-sm text-slate-500 font-medium">Harga Modal</span>
            <span class="text-sm text-slate-800 font-bold">Rp {{ number_format($product->base_price, 0, ',', '.') }}</span>
        </div>
        <div class="flex justify-between p-3 border-b border-slate-100">
            <span class="text-sm text-slate-500 font-medium">Harga Jual</span>
            <span class="text-sm text-emerald-600 font-black">Rp {{ number_format($product->sell_price, 0, ',', '.') }}</span>
        </div>
    </div>
</div>