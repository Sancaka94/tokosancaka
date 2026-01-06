{{-- ================================================== --}}
    {{-- MODAL CATATAN PESANAN (FIXED NAME VARIABLE) --}}
    {{-- ================================================== --}}
    <div x-show="noteModalOpen" class="fixed inset-0 z-[110] flex items-center justify-center px-4 font-sans" style="display: none;">
        {{-- BACKDROP --}}
        <div x-show="noteModalOpen" x-transition.opacity @click="noteModalOpen = false" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm"></div>

        {{-- MODAL CONTENT --}}
        <div x-show="noteModalOpen" 
             x-transition:enter="transition ease-out duration-300" 
             x-transition:enter-start="opacity-0 scale-95 translate-y-4" 
             x-transition:enter-end="opacity-100 scale-100 translate-y-0"
             class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden z-10 flex flex-col max-h-[90vh]">
            
            <div class="px-5 py-4 border-b border-slate-100 flex justify-between items-center bg-slate-50">
                <h3 class="font-bold text-slate-800 text-lg">Catatan Pesanan</h3>
                <button @click="noteModalOpen = false" class="text-slate-400 hover:text-red-500 transition"><i class="fas fa-times text-lg"></i></button>
            </div>

            <div class="p-5 overflow-y-auto">
                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-2">Tulis Catatan Untuk Admin / Kasir</label>
                
                {{-- PERBAIKAN: Gunakan customerNote agar sinkron dengan posSystem() --}}
                <textarea x-model="customerNote" rows="5" 
                          class="w-full rounded-xl border-slate-200 bg-slate-50 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm p-3 resize-none" 
                          placeholder="Contoh: Tolong dipacking plastik rapi, atau dikirim jam 10 pagi..."></textarea>
                
                <p class="text-[10px] text-slate-400 mt-2 text-right">Maksimal 250 karakter</p>
            </div>

            <div class="p-5 border-t border-slate-100 bg-slate-50 flex gap-3">
                <button @click="customerNote = ''; noteModalOpen = false" class="flex-1 py-3 rounded-xl border border-slate-200 text-slate-600 font-bold hover:bg-slate-100 transition">Hapus</button>
                <button @click="noteModalOpen = false" class="flex-1 py-3 rounded-xl bg-blue-600 text-white font-bold hover:bg-blue-700 shadow-lg shadow-blue-200 transition">Simpan Catatan</button>
            </div>
        </div>
    </div>
    {{-- ================================================== --}}