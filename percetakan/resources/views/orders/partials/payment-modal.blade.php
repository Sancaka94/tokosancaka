<div x-show="showPaymentModal" style="display: none;"
     class="fixed inset-0 z-50 bg-slate-100 flex flex-col"
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="opacity-0 translate-y-full"
     x-transition:enter-end="opacity-100 translate-y-0"
     x-transition:leave="transition ease-in duration-200"
     x-transition:leave-start="opacity-100 translate-y-0"
     x-transition:leave-end="opacity-0 translate-y-full">

    {{-- HEADER --}}
    <div class="h-16 px-4 sm:px-6 bg-white border-b border-slate-200 flex justify-between items-center shadow-sm shrink-0 z-20">
        <div class="flex items-center gap-3">
            <div class="h-10 w-10 rounded-xl bg-red-50 text-red-600 flex items-center justify-center">
                <i class="fas fa-cash-register text-xl"></i>
            </div>
            <div>
                <h3 class="font-black text-lg sm:text-xl text-slate-800 leading-tight">Pembayaran</h3>
                <p class="text-xs text-slate-500 font-medium">Selesaikan transaksi</p>
            </div>
        </div>
        <button @click="showPaymentModal = false" class="group flex items-center gap-2 px-3 py-2 rounded-full bg-red-100 text-red-600 hover:bg-red-200 transition">
            <span class="text-xs font-bold hidden sm:block">TUTUP</span>
            <i class="fas fa-times text-lg"></i>
        </button>
    </div>

    {{-- BODY --}}
    <div class="flex-1 flex flex-col lg:flex-row overflow-hidden relative">

        {{-- KOLOM KIRI (RINGKASAN HARGA & DISKON) --}}
        <div class="lg:w-[35%] bg-white border-b lg:border-b-0 lg:border-r border-slate-200 overflow-y-auto custom-scrollbar flex flex-col order-1 lg:order-1 shadow-sm z-10 shrink-0 max-h-[30vh] lg:max-h-full">
            <div class="p-4 sm:p-6 space-y-4 sm:space-y-6">
                <div class="text-center p-4 bg-slate-50 rounded-2xl border border-slate-100">
                    <p class="text-[10px] sm:text-xs font-bold text-slate-400 uppercase tracking-widest mb-1">Total Tagihan</p>
                    <h2 class="text-3xl sm:text-5xl font-black text-slate-800 tracking-tight break-all" x-text="'Rp ' + rupiah(grandTotal)"></h2>

                    {{-- Badge Hemat --}}
                    <div x-show="discountAmount > 0" x-transition class="mt-2 inline-flex items-center gap-1 px-3 py-1 bg-emerald-100 text-emerald-700 rounded-full text-xs font-bold">
                        <i class="fas fa-check-circle"></i> Hemat Rp <span x-text="rupiah(discountAmount)"></span>
                    </div>
                </div>

                {{-- Rincian hanya muncul di Desktop agar HP tidak penuh --}}
                <div class="hidden lg:block space-y-3">
                    <h4 class="text-sm font-bold text-slate-700 border-b border-slate-100 pb-2">Rincian Biaya</h4>
                    <div class="flex justify-between items-center text-sm text-slate-600">
                        <span>Subtotal (<span x-text="cartTotalQty"></span> Item)</span>
                        <span class="font-bold text-slate-800" x-text="'Rp ' + rupiah(subtotal)"></span>
                    </div>
                    <div x-show="discountAmount > 0" x-transition class="flex justify-between items-center text-sm text-emerald-600">
                        <span>Potongan Diskon</span>
                        <span class="font-bold" x-text="'- Rp ' + rupiah(discountAmount)"></span>
                    </div>
                    <div x-show="deliveryType === 'shipping' || deliveryType === 'delivery'" x-transition class="flex justify-between items-center text-sm text-blue-600">
                        <span>Ongkos Kirim</span>
                        <span class="font-bold" x-text="shippingCost > 0 ? '+ Rp ' + rupiah(shippingCost) : 'Rp 0'"></span>
                    </div>
                    <div class="border-t border-dashed border-slate-300 my-2"></div>
                    <div class="flex justify-between items-center text-base">
                        <span class="font-bold text-slate-800">Total Akhir</span>
                        <span class="font-black text-red-600 text-lg" x-text="'Rp ' + rupiah(grandTotal)"></span>
                    </div>
                </div>
            </div>
        </div>

        {{-- KOLOM KANAN (FORM INPUT) --}}
        <div class="lg:w-[65%] bg-slate-50/50 overflow-y-auto custom-scrollbar p-4 sm:p-8 order-2 lg:order-2 h-full flex-1">
            <div class="max-w-3xl mx-auto space-y-6 pb-20 lg:pb-0">

                {{-- SECTION 1: DATA PELANGGAN --}}
                <div class="bg-white p-4 sm:p-5 rounded-2xl border border-slate-200 shadow-sm">
                    <label class="flex items-center gap-2 text-xs font-bold text-slate-500 uppercase tracking-widest mb-4">
                        <span class="w-6 h-6 rounded-full bg-slate-100 flex items-center justify-center text-slate-600">1</span>
                        Data Pelanggan
                    </label>

                    {{-- Toggle Guest/Member --}}
                    <div class="flex p-1 bg-slate-100 border border-slate-200 rounded-xl mb-4 w-full">
                        <button @click="customerType = 'guest'; selectedCustomerId = '';"
                                class="flex-1 py-3 text-sm font-bold rounded-lg transition-all"
                                :class="customerType === 'guest' ? 'bg-white text-red-600 shadow-sm ring-1 ring-slate-200' : 'text-slate-500 hover:text-slate-700'">
                            Tamu (Guest)
                        </button>
                        <button @click="customerType = 'member'"
                                class="flex-1 py-3 text-sm font-bold rounded-lg transition-all"
                                :class="customerType === 'member' ? 'bg-white text-green-600 shadow-sm ring-1 ring-slate-200' : 'text-slate-500 hover:text-slate-700'">
                            Member
                        </button>
                    </div>

                    {{-- Delivery Method Buttons --}}
                    <div class="mb-4">
                        <label class="text-xs font-bold text-slate-500 mb-2 block">Metode Penyerahan</label>
                        <div class="grid grid-cols-3 gap-2">
                            {{-- Pickup --}}
                            <button @click="deliveryType = 'pickup'; shippingCost = 0; selectedCourier = null"
                                    class="py-3 px-2 rounded-lg border text-xs sm:text-sm font-bold flex flex-col items-center justify-center gap-1 transition h-20 sm:h-auto"
                                    :class="deliveryType === 'pickup' ? 'border-blue-500 bg-blue-50 text-blue-700 ring-1 ring-blue-500' : 'border-slate-200 bg-white hover:bg-slate-50 text-slate-600'">
                                <i class="fas fa-store text-xl sm:text-lg mb-1"></i>
                                <span class="text-center leading-tight">Ambil<br>di Toko</span>
                            </button>

                            {{-- Delivery (Antar Jemput) --}}
                            <button @click="deliveryType = 'delivery'; getGeoLocation(); shippingCost = 0; selectedCourier = null"
                                    class="py-3 px-2 rounded-lg border text-xs sm:text-sm font-bold flex flex-col items-center justify-center gap-1 transition h-20 sm:h-auto"
                                    :class="deliveryType === 'delivery' ? 'border-purple-500 bg-purple-50 text-purple-700 ring-1 ring-purple-500' : 'border-slate-200 bg-white hover:bg-slate-50 text-slate-600'">
                                <i class="fas fa-motorcycle text-xl sm:text-lg mb-1"></i>
                                <span class="text-center leading-tight">Antar<br>Jemput</span>
                            </button>

                            {{-- Shipping (Ekspedisi) --}}
                            <button @click="deliveryType = 'shipping'"
                                    class="py-3 px-2 rounded-lg border text-xs sm:text-sm font-bold flex flex-col items-center justify-center gap-1 transition h-20 sm:h-auto"
                                    :class="deliveryType === 'shipping' ? 'border-orange-500 bg-orange-50 text-orange-700 ring-1 ring-orange-500' : 'border-slate-200 bg-white hover:bg-slate-50 text-slate-600'">
                                <i class="fas fa-truck text-xl sm:text-lg mb-1"></i>
                                <span class="text-center leading-tight">Kirim<br>Ekspedisi</span>
                            </button>
                        </div>
                    </div>

                    {{-- GUEST FORM --}}
                    <div x-show="customerType === 'guest'" x-transition class="space-y-4 mb-6">
                        <div class="bg-slate-50 p-4 rounded-xl border border-slate-200" :class="{'bg-purple-50 border-purple-200': deliveryType === 'delivery'}">

                            {{-- Input Hidden GPS --}}
                            <input type="hidden" name="latitude" x-model="latitude">
                            <input type="hidden" name="longitude" x-model="longitude">

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                {{-- Name --}}
                                <div>
                                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Nama Pelanggan <span class="text-red-500">*</span></label>
                                    <input type="text" x-model="customerName" placeholder="Contoh: Budi"
                                           class="w-full px-4 py-3 rounded-xl border border-slate-300 focus:ring-blue-500 focus:border-blue-500 text-base font-bold text-slate-800 placeholder-slate-400">
                                </div>
                                {{-- WhatsApp --}}
                                <div>
                                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">WhatsApp <span class="text-red-500">*</span></label>
                                    <div class="relative">
                                        <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-slate-400"><i class="fab fa-whatsapp text-lg"></i></span>
                                        <input type="number" x-model="customerPhone" placeholder="08xxxxxxx"
                                               class="w-full pl-12 pr-4 py-3 rounded-xl border border-slate-300 focus:ring-blue-500 focus:border-blue-500 text-base font-bold text-slate-800 placeholder-slate-400">
                                    </div>
                                </div>

                                {{-- ADDRESS FIELD --}}
                                <div class="md:col-span-2" x-show="deliveryType === 'pickup' || deliveryType === 'delivery'" x-transition>
                                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">
                                        <span x-text="deliveryType === 'delivery' ? 'Alamat Lengkap (Wajib)' : 'Alamat (Opsional)'"></span>
                                        <span x-show="deliveryType === 'delivery'" class="text-red-500">*</span>
                                    </label>
                                    <textarea x-model="customerAddressDetail" rows="3"
                                              :placeholder="deliveryType === 'delivery' ? 'Tulis alamat lengkap untuk kurir...' : 'Catatan tambahan (jika ada)...'"
                                              class="w-full px-4 py-3 rounded-xl border border-slate-300 focus:ring-blue-500 focus:border-blue-500 text-base font-medium text-slate-800 resize-none transition-all placeholder-slate-400"></textarea>

                                    {{-- GPS Status --}}
                                    <div x-show="deliveryType === 'delivery'" class="mt-2 text-xs font-bold flex items-center gap-1 transition-colors p-2 bg-white rounded-lg border border-slate-200"
                                         :class="latitude ? 'text-green-600 border-green-200' : 'text-orange-500 border-orange-200'">
                                        <i class="fas" :class="latitude ? 'fa-check-circle' : 'fa-map-marker-alt animate-bounce'"></i>
                                        <span x-text="latitude ? 'Lokasi GPS Terkunci' : 'Menunggu Lokasi GPS... (Pastikan Izin Browser Aktif)'"></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- MEMBER SELECT --}}
                    <div x-show="customerType === 'member'" x-transition class="mb-4">
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Pilih Member</label>
                        <select x-model="selectedCustomerId" class="w-full px-4 py-3 rounded-xl border border-slate-200 text-base bg-slate-50 font-bold text-slate-700 focus:ring-2 focus:ring-red-500">
                            <option value="">-- Cari Nama Member --</option>
                            @foreach($customers as $c)
                                <option value="{{ $c->id }}"
                                        data-saldo="{{ $c->saldo }}"
                                        data-affiliate-balance="{{ $c->affiliate_balance ?? 0 }}">
                                    {{ $c->name }} (Saldo: Rp {{ number_format($c->saldo,0,',','.') }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- SHIPPING FORM (Detailed) --}}
                    <div x-show="deliveryType === 'shipping'" class="mt-4 pt-4 border-t border-dashed border-slate-200 space-y-4">
                        <div>
                            <label class="text-xs font-bold text-slate-500">Cari Kecamatan / Kelurahan*</label>
                            <div class="relative mt-1">
                                <input type="text" x-model="searchQuery" @input.debounce.500ms="searchLocation()" placeholder="Ketik nama kecamatan..." class="w-full pl-10 pr-4 py-3 rounded-xl border border-slate-300 text-base focus:ring-blue-500 focus:border-blue-500">
                                <i class="fas fa-search absolute left-4 top-4 text-slate-400"></i>
                                <div x-show="searchResults.length > 0" @click.outside="searchResults = []" class="absolute z-50 w-full mt-1 bg-white border border-slate-200 rounded-lg shadow-xl max-h-48 overflow-y-auto">
                                    <template x-for="loc in searchResults">
                                        <div @click="selectLocation(loc)" class="px-4 py-3 hover:bg-blue-50 cursor-pointer border-b border-slate-50 text-sm">
                                            <p class="font-bold text-slate-700" x-text="loc.full_address"></p>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>
                        <div>
                            <label class="text-xs font-bold text-slate-500 uppercase mb-1">Detail Alamat (Jalan, RT/RW)*</label>
                            <textarea x-model="customerAddressDetail" rows="2" class="w-full px-4 py-3 rounded-xl border border-slate-300 focus:ring-blue-500 focus:border-blue-500 text-base font-medium resize-none" placeholder="Isi alamat lengkap..."></textarea>
                        </div>
                        <div x-show="isLoadingShipping" class="text-center py-4 bg-slate-50 rounded-lg border border-dashed border-slate-300">
                            <i class="fas fa-circle-notch fa-spin text-blue-500"></i> <span class="text-xs text-slate-500 ml-2">Cek Ongkir...</span>
                        </div>
                        <div x-show="!isLoadingShipping && courierList.length > 0">
                            <p class="text-xs font-bold text-slate-400 uppercase mb-2">Pilih Kurir</p>
                            <div class="grid grid-cols-1 gap-2 max-h-60 overflow-y-auto custom-scrollbar p-1">
                                <template x-for="courier in courierList" :key="courier.service + courier.cost">
                                    <div @click="selectCourier(courier)"
                                         class="flex items-center p-3 rounded-lg border cursor-pointer transition hover:bg-blue-50 relative"
                                         :class="selectedCourier && selectedCourier.service === courier.service && selectedCourier.cost === courier.cost ? 'border-blue-500 bg-blue-50 ring-1 ring-blue-500' : 'border-slate-200 bg-white'">
                                        <div class="w-10 h-10 bg-white rounded border border-slate-100 flex items-center justify-center p-1 mr-3 shrink-0">
                                            <img :src="courier.logo" alt="Logo" class="w-full h-full object-contain" x-show="courier.logo">
                                            <i class="fas fa-box text-slate-300" x-show="!courier.logo"></i>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <div class="flex justify-between items-center">
                                                <p class="text-sm font-bold text-slate-700 truncate" x-text="courier.name"></p>
                                                <p class="text-sm font-black text-blue-600" x-text="rupiah(courier.cost)"></p>
                                            </div>
                                            <p class="text-xs text-slate-500 truncate" x-text="courier.service + ' (' + courier.etd + ' Hari)'"></p>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- SECTION: KUPON --}}
                <div class="bg-white p-4 sm:p-5 rounded-2xl border border-slate-200 shadow-sm">
                    <label class="flex items-center gap-2 text-xs font-bold text-slate-500 uppercase tracking-widest mb-3">
                        <i class="fas fa-ticket-alt text-amber-500"></i> Kode Promo
                    </label>
                    <div class="relative">
                        <input type="text" x-model="couponCode" @input.debounce.500ms="checkCoupon()"
                               placeholder="Masukkan kode diskon..."
                               class="w-full pl-4 pr-10 py-3 rounded-xl border border-slate-200 focus:ring-amber-500 focus:border-amber-500 text-base font-bold text-slate-700 uppercase placeholder:normal-case transition-all"
                               :class="discountAmount > 0 ? 'border-emerald-500 bg-emerald-50 text-emerald-700' : (couponMessage && discountAmount === 0 ? 'border-red-300 bg-red-50 text-red-700' : '')">

                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                            <i x-show="isValidatingCoupon" class="fas fa-circle-notch fa-spin text-slate-400"></i>
                            <i x-show="!isValidatingCoupon && discountAmount > 0" class="fas fa-check-circle text-emerald-500 text-lg"></i>
                        </div>
                    </div>
                    <p x-show="couponMessage" x-text="couponMessage" class="text-xs font-bold mt-2"
                       :class="discountAmount > 0 ? 'text-emerald-600' : 'text-red-500'"></p>
                </div>

                {{-- SECTION 2: METODE PEMBAYARAN --}}
                <div class="bg-white p-4 sm:p-5 rounded-2xl border border-slate-200 shadow-sm">
                    <label class="flex items-center gap-2 text-xs font-bold text-slate-500 uppercase tracking-widest mb-4">
                        <span class="w-6 h-6 rounded-full bg-slate-100 flex items-center justify-center text-slate-600">2</span>
                        Pembayaran
                    </label>

                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                        <div @click="paymentMethod = 'cash'" class="cursor-pointer border-2 rounded-xl p-3 flex flex-col items-center justify-center gap-1 transition h-20 relative" :class="paymentMethod === 'cash' ? 'border-red-500 bg-red-50 text-red-700' : 'border-slate-100 bg-white hover:border-red-200 hover:bg-slate-50'">
                            <i class="fas fa-money-bill-wave text-xl mb-1"></i><span class="text-xs font-bold">Tunai</span>
                            <div x-show="paymentMethod === 'cash'" class="absolute top-1 right-1 text-red-500"><i class="fas fa-check-circle"></i></div>
                        </div>
                        <div @click="paymentMethod = 'saldo'" class="cursor-pointer border-2 rounded-xl p-3 flex flex-col items-center justify-center gap-1 transition h-20 relative" :class="paymentMethod === 'saldo' ? 'border-blue-500 bg-blue-50 text-blue-700' : 'border-slate-100 bg-white hover:border-blue-200 hover:bg-slate-50'">
                            <i class="fas fa-wallet text-xl mb-1"></i><span class="text-xs font-bold">Saldo</span>
                            <div x-show="paymentMethod === 'saldo'" class="absolute top-1 right-1 text-blue-500"><i class="fas fa-check-circle"></i></div>
                        </div>
                        <div @click="paymentMethod = 'tripay'; fetchTripayChannels()" class="cursor-pointer border-2 rounded-xl p-3 flex flex-col items-center justify-center gap-1 transition h-20 relative" :class="paymentMethod === 'tripay' ? 'border-purple-500 bg-purple-50 text-purple-700' : 'border-slate-100 bg-white hover:bg-slate-50'">
                            <i class="fas fa-qrcode text-xl mb-1"></i><span class="text-xs font-bold">QRIS/VA</span>
                            <div x-show="paymentMethod === 'tripay'" class="absolute top-1 right-1 text-purple-500"><i class="fas fa-check-circle"></i></div>
                        </div>
                    </div>

                    {{-- Cash Form --}}
                    <div x-show="paymentMethod === 'cash'" x-transition class="mt-5 pt-5 border-t border-dashed border-slate-200">
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Uang Diterima</label>
                        <div class="relative">
                            <span class="absolute left-4 top-4 text-slate-400 font-bold text-lg">Rp</span>
                            <input type="number" x-model="cashAmount" placeholder="0" class="w-full pl-12 pr-4 py-4 text-3xl font-black text-slate-800 bg-slate-50 rounded-xl border border-slate-200 focus:ring-2 focus:ring-red-500 transition">
                        </div>
                        <div class="grid grid-cols-4 gap-2 mt-3">
                            <button @click="cashAmount = grandTotal" class="text-xs py-3 bg-slate-800 text-white rounded-lg font-bold">Uang Pas</button>
                            <button @click="cashAmount = 20000" class="text-xs py-3 bg-white border rounded-lg font-bold">20k</button>
                            <button @click="cashAmount = 50000" class="text-xs py-3 bg-white border rounded-lg font-bold">50k</button>
                            <button @click="cashAmount = 100000" class="text-xs py-3 bg-white border rounded-lg font-bold">100k</button>
                        </div>
                        <div class="mt-4 p-4 rounded-xl flex justify-between items-center transition-colors" :class="change < 0 ? 'bg-red-50 text-red-700' : 'bg-emerald-50 text-emerald-700'">
                            <span class="font-bold text-sm">Kembalian</span>
                            <span class="font-black text-xl" x-text="change < 0 ? 'Kurang Rp ' + rupiah(Math.abs(change)) : 'Rp ' + rupiah(change)"></span>
                        </div>
                    </div>

                    {{-- Tripay Loading --}}
                    <div x-show="paymentMethod === 'tripay' && isLoadingChannels" class="py-4 text-center text-slate-400">
                        <i class="fas fa-spinner fa-spin mr-2"></i> Memuat...
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- FOOTER --}}
    <div class="p-4 sm:p-6 bg-white border-t border-slate-200 shrink-0 z-20 flex justify-end gap-3 shadow-[0_-4px_20px_rgba(0,0,0,0.05)]">
        <button @click="showPaymentModal = false" class="px-4 sm:px-6 py-3 rounded-xl font-bold text-slate-500 hover:bg-slate-100 transition text-sm">Kembali</button>
        <button @click="checkout()" :disabled="isProcessing || (paymentMethod === 'cash' && change < 0)" class="flex-1 sm:flex-none sm:w-auto px-6 py-3 rounded-xl font-bold text-base shadow-lg active:scale-[0.98] transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2 bg-red-600 text-white hover:bg-red-700">
            <span x-show="!isProcessing">Bayar & Cetak</span>
            <span x-show="isProcessing"><i class="fas fa-spinner fa-spin"></i> Proses...</span>
            <i x-show="!isProcessing" class="fas fa-arrow-right"></i>
        </button>
    </div>
</div>
