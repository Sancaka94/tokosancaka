<div x-show="showPaymentModal" style="display: none;"
     class="fixed inset-0 z-50 flex flex-col bg-slate-100/90 backdrop-blur-sm"
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="opacity-0 translate-y-full sm:translate-y-10 sm:scale-95"
     x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
     x-transition:leave="transition ease-in duration-200"
     x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
     x-transition:leave-end="opacity-0 translate-y-full sm:translate-y-10 sm:scale-95"
     style="will-change: transform, opacity;"> <div class="flex items-center justify-between h-16 px-4 bg-white border-b shadow-sm sm:px-6 border-slate-200 shrink-0 z-20">
        <div class="flex items-center gap-3">
            <div class="flex items-center justify-center w-10 h-10 text-red-600 rounded-xl bg-red-50">
                <i class="fas fa-cash-register text-xl"></i>
            </div>
            <div>
                <h3 class="text-lg font-black leading-tight text-slate-800 sm:text-xl">Pembayaran</h3>
                <p class="text-xs font-medium text-slate-500">Selesaikan transaksi pesanan ini</p>
            </div>
        </div>

        <button @click="showPaymentModal = false" class="group flex items-center gap-2 px-4 py-2 rounded-full bg-red-500 hover:bg-red-600 text-slate-500 hover:text-red-100 transition border border-transparent hover:border-red-100 active:scale-95">
            <span class="hidden text-xs font-bold sm:block">BATAL / TUTUP</span>
            <i class="fas fa-times text-lg"></i>
        </button>
    </div>

    <div class="relative flex flex-col flex-1 overflow-hidden lg:flex-row">

        <div class="lg:w-[35%] bg-white border-r border-slate-200 overflow-y-auto overflow-x-hidden custom-scrollbar flex flex-col order-2 lg:order-1 h-full shadow-[4px_0_24px_rgba(0,0,0,0.02)] z-10 overscroll-contain">
            <div class="p-4 space-y-4 sm:p-6 sm:space-y-6">
                <div class="p-4 text-center border sm:p-6 bg-slate-50 rounded-2xl border-slate-100">
                    <p class="mb-2 text-xs font-bold tracking-widest uppercase text-slate-400">Total Yang Harus Dibayar</p>
                    <h2 class="text-3xl font-black tracking-tight text-slate-800 sm:text-5xl break-all" x-text="'Rp ' + rupiah(grandTotal)"></h2>
                    <div x-show="discountAmount > 0" class="inline-flex items-center gap-1 px-3 py-1 mt-2 text-xs font-bold rounded-full bg-emerald-100 text-emerald-700">
                        <i class="fas fa-check-circle"></i> Hemat Rp <span x-text="rupiah(discountAmount)"></span>
                    </div>
                </div>

                <div class="space-y-3">
                    <h4 class="pb-2 text-sm font-bold border-b text-slate-700 border-slate-100">Rincian Biaya</h4>

                    <div class="flex items-center justify-between text-sm text-slate-600">
                        <span>Subtotal (<span x-text="cartTotalQty"></span> Item)</span>
                        <span class="font-bold text-slate-800" x-text="'Rp ' + rupiah(subtotal)"></span>
                    </div>

                    <div x-show="discountAmount > 0" class="flex items-center justify-between text-sm text-emerald-600">
                        <span>Potongan Diskon</span>
                        <span class="font-bold" x-text="'- Rp ' + rupiah(discountAmount)"></span>
                    </div>

                    <div x-show="deliveryType === 'shipping'" class="flex items-center justify-between text-sm text-blue-600">
                        <span>Ongkos Kirim</span>
                        <span class="font-bold" x-text="shippingCost > 0 ? '+ Rp ' + rupiah(shippingCost) : 'Rp 0'"></span>
                    </div>

                    <div class="my-2 border-t border-dashed border-slate-300"></div>

                    <div class="flex items-center justify-between text-base">
                        <span class="font-bold text-slate-800">Total Akhir</span>
                        <span class="text-lg font-black text-red-600" x-text="'Rp ' + rupiah(grandTotal)"></span>
                    </div>
                </div>
            </div>

            <div class="hidden p-6 mt-auto border-t bg-slate-50 border-slate-100 lg:block">
                <div class="flex items-center gap-3 text-xs text-slate-400">
                    <i class="fas fa-shield-alt text-xl"></i>
                    <p>Transaksi aman. Pastikan data sudah benar.</p>
                </div>
            </div>
        </div>

        <div class="lg:w-[65%] bg-slate-50/50 overflow-y-auto overflow-x-hidden custom-scrollbar p-4 sm:p-8 order-1 lg:order-2 h-full overscroll-contain">
            <div class="max-w-3xl mx-auto space-y-6">

                <div class="p-5 bg-white border shadow-sm rounded-2xl border-slate-200">
                    <label class="flex items-center gap-2 mb-4 text-xs font-bold tracking-widest uppercase text-slate-500">
                        <span class="flex items-center justify-center w-6 h-6 rounded-full bg-slate-100 text-slate-600">1</span>
                        Data Pelanggan & Pengiriman
                    </label>

                    <div class="flex w-full p-1 mb-4 border bg-slate-100 border-slate-200 rounded-xl sm:w-80">
                        <button @click="customerType = 'guest'; selectedCustomerId = '';"
                                class="flex-1 py-2 text-xs font-bold transition-all rounded-lg active:scale-95"
                                :class="customerType === 'guest' ? 'bg-white text-red-600 shadow-sm ring-1 ring-slate-200' : 'text-slate-500 hover:text-slate-700'">
                            Tamu (Guest)
                        </button>
                        <button @click="customerType = 'member'"
                                class="flex-1 py-2 text-xs font-bold transition-all rounded-lg active:scale-95"
                                :class="customerType === 'member' ? 'bg-white text-green-600 shadow-sm ring-1 ring-slate-200' : 'text-slate-500 hover:text-slate-700'">
                            Member
                        </button>
                    </div>

                    {{-- Metode Penyerahan --}}
                    <div class="mb-4">
                        <label class="text-[10px] font-bold text-slate-500 mb-1 block">Metode Penyerahan</label>

                        {{-- Grid 3 Tombol --}}
                        <div class="grid grid-cols-3 gap-2">
                            {{-- 1. Ambil di Toko --}}
                            <button @click="deliveryType = 'pickup'; shippingCost = 0; selectedCourier = null"
                                    class="py-2 px-2 rounded-lg border text-[10px] sm:text-xs font-bold flex flex-col sm:flex-row items-center justify-center gap-1 transition h-14 sm:h-auto active:scale-95"
                                    :class="deliveryType === 'pickup' ? 'border-blue-500 bg-blue-50 text-blue-700 ring-1 ring-blue-500' : 'border-slate-200 bg-white hover:bg-slate-50 text-slate-600'">
                                <i class="text-lg fas fa-store sm:text-sm"></i>
                                <span class="text-center">Ambil di Toko</span>
                            </button>

                            {{-- 2. Antar Jemput --}}
                            <button @click="deliveryType = 'delivery'; getGeoLocation(); shippingCost = 0; selectedCourier = null"
                                    class="py-2 px-2 rounded-lg border text-[10px] sm:text-xs font-bold flex flex-col sm:flex-row items-center justify-center gap-1 transition h-14 sm:h-auto active:scale-95"
                                    :class="deliveryType === 'delivery' ? 'border-purple-500 bg-purple-50 text-purple-700 ring-1 ring-purple-500' : 'border-slate-200 bg-white hover:bg-slate-50 text-slate-600'">
                                <i class="text-lg fas fa-motorcycle sm:text-sm"></i>
                                <span class="text-center">Antar Jemput</span>
                            </button>

                            {{-- 3. Ekspedisi --}}
                            <button @click="deliveryType = 'shipping'"
                                    class="py-2 px-2 rounded-lg border text-[10px] sm:text-xs font-bold flex flex-col sm:flex-row items-center justify-center gap-1 transition h-14 sm:h-auto active:scale-95"
                                    :class="deliveryType === 'shipping' ? 'border-orange-500 bg-orange-50 text-orange-700 ring-1 ring-orange-500' : 'border-slate-200 bg-white hover:bg-slate-50 text-slate-600'">
                                <i class="text-lg fas fa-truck sm:text-sm"></i>
                                <span class="text-center">Ekspedisi</span>
                            </button>
                        </div>
                    </div>

                    {{-- INPUT DATA PELANGGAN (AUTOCOMPLETE & AUTOFILL) --}}
                    <div x-show="customerType === 'guest'" x-transition class="space-y-4 mb-6">
                        <div class="p-4 border bg-slate-50 rounded-xl border-slate-200 relative"
                            :class="{'bg-purple-50 border-purple-200': deliveryType === 'delivery'}">

                            {{-- Hidden Input untuk Data --}}
                            <input type="hidden" name="customer_id" x-model="selectedCustomerId">
                            <input type="hidden" name="latitude" x-model="latitude">
                            <input type="hidden" name="longitude" x-model="longitude">

                            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">

                                {{-- WhatsApp (Pencarian & Validasi) --}}
                                <div class="relative">
                                    <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">WhatsApp <span class="text-red-500">*</span></label>
                                    <div class="relative">
                                        <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                                            <i class="fab fa-whatsapp"></i>
                                        </span>

                                        {{-- Input WA dengan sanitizePhone() --}}
                                        <input type="tel"
                                            x-model="customerPhone"
                                            @input.debounce.500ms="searchCustomerByPhone()"
                                            @blur="sanitizePhone()"
                                            placeholder="08xxxxxxxxxx"
                                            class="w-full pl-10 pr-10 py-2.5 rounded-xl border border-slate-300 focus:ring-blue-500 focus:border-blue-500 text-base sm:text-sm font-bold text-slate-700 shadow-sm transition-all"
                                            :class="{'ring-2 ring-emerald-400 border-emerald-500': isCustomerFound}">

                                        {{-- Indikator Loading / Sukses --}}
                                        <div class="absolute inset-y-0 right-0 flex items-center pr-3">
                                            <i x-show="isSearchingCustomer" class="fas fa-circle-notch fa-spin text-slate-400"></i>
                                            <i x-show="isCustomerFound" class="fas fa-check-circle text-emerald-500" title="Data Pelanggan Ditemukan"></i>
                                        </div>
                                    </div>

                                    {{-- Dropdown Hasil Pencarian --}}
                                    <div x-show="customerSearchResults.length > 0" @click.outside="customerSearchResults = []"
                                        class="absolute z-50 w-full mt-1 bg-white border border-slate-200 rounded-lg shadow-xl max-h-48 overflow-y-auto">
                                        <template x-for="cust in customerSearchResults" :key="cust.id">
                                            <div @click="fillCustomerData(cust)" class="px-4 py-2 text-xs border-b cursor-pointer hover:bg-blue-50 border-slate-50 flex flex-col">
                                                <span class="font-bold text-slate-700" x-text="cust.name"></span>
                                                <span class="text-[10px] text-slate-500" x-text="cust.whatsapp"></span>
                                            </div>
                                        </template>
                                    </div>
                                </div>

                                {{-- Nama Pelanggan (BARU - DENGAN PENCARIAN) --}}
                                <div class="relative">
                                    <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Nama Pelanggan <span class="text-red-500">*</span></label>

                                    <div class="relative">
                                        <input type="text"
                                            x-model="customerName"
                                            @input.debounce.500ms="searchCustomerByName()"
                                            placeholder="Ketik Nama untuk cari..."
                                            class="w-full px-4 py-2.5 rounded-xl border border-slate-300 focus:ring-blue-500 focus:border-blue-500 text-base sm:text-sm font-bold text-slate-700 shadow-sm transition-all"
                                            :readonly="isCustomerFound">

                                        {{-- Indikator Loading di Nama --}}
                                        <div class="absolute inset-y-0 right-0 flex items-center pr-3" x-show="isSearchingCustomer">
                                            <i class="fas fa-circle-notch fa-spin text-slate-400"></i>
                                        </div>
                                    </div>

                                    {{-- Dropdown Hasil Pencarian (Untuk Nama) --}}
                                    <div x-show="customerNameSearchResults.length > 0" @click.outside="customerNameSearchResults = []"
                                        class="absolute z-50 w-full mt-1 bg-white border border-slate-200 rounded-lg shadow-xl max-h-48 overflow-y-auto">
                                        <template x-for="cust in customerNameSearchResults" :key="cust.id">
                                            <div @click="fillCustomerData(cust); customerNameSearchResults = []"
                                                class="px-4 py-2 text-xs border-b cursor-pointer hover:bg-blue-50 border-slate-50 flex flex-col">
                                                <span class="font-bold text-slate-700" x-text="cust.name"></span>
                                                <span class="text-[10px] text-slate-500" x-text="cust.whatsapp"></span>
                                                <span class="text-[9px] text-slate-400 truncate" x-text="cust.address"></span>
                                            </div>
                                        </template>
                                    </div>
                                </div>

                                {{-- Alamat --}}
                                <div class="md:col-span-2" x-show="deliveryType === 'pickup' || deliveryType === 'delivery'" x-transition>
                                    <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">
                                        <span x-text="deliveryType === 'delivery' ? 'Alamat Lengkap (Wajib)' : 'Alamat (Opsional)'"></span>
                                        <span x-show="deliveryType === 'delivery'" class="text-red-500">*</span>
                                    </label>
                                    <textarea x-model="customerAddressDetail" rows="2"
                                            :placeholder="deliveryType === 'delivery' ? 'Mohon tulis alamat lengkap untuk kurir...' : 'Catatan tambahan (jika ada)...'"
                                            class="w-full px-4 py-2.5 rounded-xl border border-slate-300 focus:ring-blue-500 focus:border-blue-500 text-base sm:text-sm font-medium text-slate-700 resize-none transition-all shadow-sm"></textarea>

                                    {{-- Status GPS --}}
                                    <div x-show="deliveryType === 'delivery'" class="mt-2 flex items-center justify-between">
                                        <div class="text-[10px] font-bold flex items-center gap-1 transition-colors"
                                            :class="latitude ? 'text-green-600' : 'text-red-500'">
                                            <i class="fas" :class="latitude ? 'fa-check-circle' : 'fa-map-marker-alt animate-bounce'"></i>
                                            <span x-text="latitude ? 'Lokasi GPS Tersimpan' : 'Belum ada data GPS'"></span>
                                        </div>
                                        <button @click="getGeoLocation()" class="text-[10px] text-blue-500 hover:underline">
                                            <i class="fas fa-sync-alt"></i> Update Lokasi Saya
                                        </button>
                                    </div>
                                </div>
                            </div>

                            {{-- Tombol Reset --}}
                            <button x-show="isCustomerFound" @click="resetCustomerData()"
                                    class="absolute top-2 right-2 text-xs text-red-400 hover:text-red-600 font-bold bg-white px-2 py-1 rounded shadow-sm border border-slate-200">
                                Reset / Baru
                            </button>
                        </div>
                    </div>

                    {{-- Alamat Pengiriman (Jika Ekspedisi) --}}

                    <div x-show="deliveryType === 'shipping'">
                        <div class="mb-3">
                            <label class="text-[10px] font-bold text-slate-500">Cari Kecamatan / Kelurahan*</label>
                            <div class="relative mt-1">
                                <input type="text" x-model="searchQuery" @input.debounce.500ms="searchLocation()" placeholder="Ketik nama kecamatan..."
                                       class="w-full pl-8 pr-4 py-2 border rounded-lg border-slate-300 text-base sm:text-sm shadow-sm">
                                <i class="absolute text-xs fas fa-search left-3 top-3 text-slate-400"></i>

                                <div x-show="searchResults.length > 0" @click.outside="searchResults = []" class="absolute z-50 w-full mt-1 overflow-y-auto bg-white border shadow-xl border-slate-200 rounded-lg max-h-48">
                                    <template x-for="loc in searchResults">
                                        <div @click="selectLocation(loc)" class="px-4 py-2 text-xs border-b cursor-pointer hover:bg-blue-50 border-slate-50">
                                            <p class="font-bold text-slate-700" x-text="loc.full_address"></p>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="text-[10px] font-bold text-slate-500">Detail Alamat (Jalan, RT/RW)*</label>
                            <textarea x-model="customerAddressDetail" rows="2" class="w-full px-3 py-2 mt-1 border rounded-lg border-slate-300 text-base sm:text-sm resize-none shadow-sm"></textarea>
                        </div>

                        <div x-show="isLoadingShipping" class="py-4 text-center border border-dashed rounded-lg bg-slate-50 border-slate-300">
                            <i class="text-blue-500 fas fa-circle-notch fa-spin"></i> <span class="ml-2 text-xs text-slate-500">Cek Ongkir...</span>
                        </div>

                        <div x-show="!isLoadingShipping && courierList.length > 0" class="mt-4">
                            <p class="mb-2 text-[10px] font-bold text-slate-400 uppercase">Pilih Layanan Pengiriman</p>
                            <div class="grid grid-cols-1 gap-2 p-1 overflow-y-auto sm:grid-cols-2 max-h-60 custom-scrollbar">
                                <template x-for="courier in courierList" :key="courier.service + courier.cost">
                                    <div @click="selectCourier(courier)"
                                         class="relative flex items-center p-2 transition border rounded-lg cursor-pointer hover:bg-blue-50 active:scale-[0.98]"
                                         :class="selectedCourier && selectedCourier.service === courier.service && selectedCourier.cost === courier.cost ? 'border-blue-500 bg-blue-50 ring-1 ring-blue-500' : 'border-slate-200 bg-white'">
                                        <div class="flex items-center justify-center w-10 h-10 p-1 mr-3 bg-white border rounded shrink-0 border-slate-100">
                                            <img :src="courier.logo" loading="lazy" alt="Logo" class="object-contain w-full h-full" x-show="courier.logo">
                                            <i class="text-slate-300 fas fa-box" x-show="!courier.logo"></i>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-center justify-between">
                                                <p class="text-[11px] font-bold text-slate-700 truncate" x-text="courier.name"></p>
                                                <p class="text-xs font-black text-blue-600" x-text="rupiah(courier.cost)"></p>
                                            </div>
                                            <p class="text-[10px] text-slate-500 truncate" x-text="courier.service + ' (' + courier.etd + ' Hari)'"></p>
                                        </div>
                                        <div x-show="selectedCourier && selectedCourier.service === courier.service" class="absolute text-blue-600 top-1 right-1"><i class="fas fa-check-circle text-[10px]"></i></div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>

                    <div x-show="customerType === 'member'" style="display: none;" x-transition>
                        <label class="text-[10px] font-bold text-slate-500">Cari Member Terdaftar</label>
                        <select x-model="selectedCustomerId" class="w-full px-4 py-3 mt-1 font-bold border border-slate-200 bg-slate-50 rounded-xl text-base sm:text-sm text-slate-700 focus:ring-2 focus:ring-red-500">
                            <option value="">-- Pilih Member --</option>
                            @foreach($customers as $c)
                                <option value="{{ $c->id }}"
                                        data-saldo="{{ $c->saldo }}"
                                        data-affiliate-balance="{{ $c->affiliate_balance ?? 0 }}"
                                        data-has-pin="{{ $c->has_pin ? 'yes' : 'no' }}">
                                    {{ $c->name }} (Saldo: Rp {{ number_format($c->saldo,0,',','.') }})
                                </option>
                            @endforeach
                        </select>

                        <div x-show="selectedCustomerId" class="flex gap-3 mt-3">
                            <div class="flex flex-col items-center flex-1 p-3 border border-blue-100 bg-blue-50 rounded-xl">
                                <span class="text-[10px] text-blue-400 font-bold uppercase">Saldo Topup</span>
                                <span class="text-sm font-black text-blue-700" x-text="'Rp ' + rupiah(getSelectedMemberSaldo())"></span>
                            </div>
                            <div class="flex flex-col items-center flex-1 p-3 border border-purple-100 bg-purple-50 rounded-xl">
                                <span class="text-[10px] text-purple-400 font-bold uppercase">Profit Afiliasi</span>
                                <span class="text-sm font-black text-purple-700" x-text="'Rp ' + rupiah(getSelectedAffiliateBalance())"></span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="p-5 bg-white border shadow-sm rounded-2xl border-slate-200">
                    <label class="flex items-center gap-2 mb-4 text-xs font-bold tracking-widest uppercase text-slate-500">
                        <span class="flex items-center justify-center w-6 h-6 rounded-full bg-slate-100 text-slate-600">2</span>
                        Metode Pembayaran
                    </label>

                    <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-5">

                        <div @click="paymentMethod = 'pay_later'"
                            class="relative flex flex-col items-center justify-center h-20 gap-1 p-2 transition border-2 cursor-pointer rounded-xl group active:scale-95"
                            :class="paymentMethod === 'pay_later' ? 'border-amber-500 bg-amber-50 text-amber-700' : 'border-slate-100 bg-white hover:border-amber-200 hover:bg-slate-50'">
                            <i class="text-lg fas fa-clock"></i>
                            <span class="text-[10px] font-bold text-center">Bayar Nanti</span>
                            <div x-show="paymentMethod === 'pay_later'" class="absolute text-amber-500 top-1 right-1"><i class="fas fa-check-circle text-[10px]"></i></div>
                        </div>

                        <div @click="paymentMethod = 'qris_manual'"
                            class="relative flex flex-col items-center justify-center h-20 gap-1 p-2 transition border-2 cursor-pointer rounded-xl group active:scale-95"
                            :class="paymentMethod === 'qris_manual' ? 'border-gray-800 bg-gray-100 text-gray-900' : 'border-slate-100 bg-white hover:border-gray-400 hover:bg-slate-50'">
                            <i class="text-lg fas fa-qrcode"></i>
                            <span class="text-[10px] font-bold text-center">QRIS Manual</span>
                            <div x-show="paymentMethod === 'qris_manual'" class="absolute text-gray-800 top-1 right-1"><i class="fas fa-check-circle text-[10px]"></i></div>
                        </div>

                        <div @click="paymentMethod = 'cash'"
                             class="relative flex flex-col items-center justify-center h-20 gap-1 p-2 transition border-2 cursor-pointer rounded-xl group active:scale-95"
                             :class="paymentMethod === 'cash' ? 'border-red-500 bg-red-50 text-red-700' : 'border-slate-100 bg-white hover:border-red-200 hover:bg-slate-50'">
                            <i class="text-lg fas fa-money-bill-wave"></i>
                            <span class="text-[10px] font-bold text-center">Tunai</span>
                            <div x-show="paymentMethod === 'cash'" class="absolute text-red-500 top-1 right-1"><i class="fas fa-check-circle text-[10px]"></i></div>
                        </div>

                        <div @click="paymentMethod = 'saldo'"
                             class="relative flex flex-col items-center justify-center h-20 gap-1 p-2 transition border-2 cursor-pointer rounded-xl group active:scale-95"
                             :class="paymentMethod === 'saldo' ? 'border-blue-500 bg-blue-50 text-blue-700' : 'border-slate-100 bg-white hover:border-blue-200 hover:bg-slate-50'">
                            <i class="text-lg fas fa-wallet"></i>
                            <span class="text-[10px] font-bold text-center">Saldo</span>
                            <div x-show="paymentMethod === 'saldo'" class="absolute text-blue-500 top-1 right-1"><i class="fas fa-check-circle text-[10px]"></i></div>
                        </div>

                        <div @click="selectAffiliatePayment()"
                             class="relative flex flex-col items-center justify-center h-20 gap-1 p-2 transition border-2 cursor-pointer rounded-xl group active:scale-95"
                             :class="paymentMethod === 'affiliate_balance' ? 'border-purple-500 bg-purple-50 text-purple-700' : 'border-slate-100 bg-white hover:border-purple-200 hover:bg-slate-50'">
                            <i class="text-lg fas fa-coins"></i>
                            <span class="text-[10px] font-bold text-center">Profit</span>
                            <div x-show="paymentMethod === 'affiliate_balance'" class="absolute text-purple-500 top-1 right-1"><i class="fas fa-check-circle text-[10px]"></i></div>
                        </div>

                        <div @click="paymentMethod = 'tripay'; fetchTripayChannels()"
                             class="relative flex flex-col items-center justify-center h-20 gap-1 p-2 transition border-2 cursor-pointer rounded-xl group active:scale-95"
                             :class="paymentMethod === 'tripay' ? 'border-red-500 bg-red-50 text-red-700' : 'border-slate-100 bg-white hover:border-red-200 hover:bg-slate-50'">
                            <i class="text-lg fas fa-qrcode"></i>
                            <span class="text-[10px] font-bold text-center">QRIS/VA</span>
                            <div x-show="paymentMethod === 'tripay'" class="absolute text-red-500 top-1 right-1"><i class="fas fa-check-circle text-[10px]"></i></div>
                        </div>

                        <div @click="paymentMethod = 'doku'"
                             class="relative flex flex-col items-center justify-center h-20 gap-1 p-2 transition border-2 cursor-pointer rounded-xl group active:scale-95"
                             :class="paymentMethod === 'doku' ? 'border-red-500 bg-red-50 text-red-700' : 'border-slate-100 bg-white hover:border-red-200 hover:bg-slate-50'">
                            <i class="text-lg fas fa-credit-card"></i>
                            <span class="text-[10px] font-bold text-center">DOKU</span>
                            <div x-show="paymentMethod === 'doku'" class="absolute text-red-500 top-1 right-1"><i class="fas fa-check-circle text-[10px]"></i></div>
                        </div>

                        <div @click="paymentMethod = 'dana'"
                            class="relative flex flex-col items-center justify-center h-20 gap-1 p-2 transition border-2 cursor-pointer rounded-xl group active:scale-95"
                            :class="paymentMethod === 'dana' ? 'border-blue-500 bg-blue-50 text-blue-700' : 'border-slate-100 bg-white hover:border-blue-200 hover:bg-slate-50'">

                            <img src="https://tokosancaka.com/storage/logo/dana.png" loading="lazy"
                                alt="DANA" class="object-contain h-4 mb-1 transition-all group-hover:scale-110">

                            <span class="text-[10px] font-bold text-center uppercase tracking-tighter">DANA</span>

                            <div x-show="paymentMethod === 'dana'" class="absolute text-blue-500 top-1 right-1 animate-bounce">
                                <i class="fas fa-check-circle text-[10px]"></i>
                            </div>
                        </div>

                    </div>

                    <div @click="paymentMethod = 'dana_sdk'"
                             class="relative flex flex-col items-center justify-center h-20 gap-1 p-2 transition border-2 cursor-pointer rounded-xl group active:scale-95"
                             :class="paymentMethod === 'dana_sdk' ? 'border-blue-500 bg-blue-50 text-blue-700' : 'border-slate-100 bg-white hover:border-blue-200 hover:bg-slate-50'">

                            <img src="https://tokosancaka.com/storage/logo/dana.png" loading="lazy"
                                 alt="DANA Widget" class="object-contain h-4 mb-1 transition-all group-hover:scale-110">

                            <span class="text-[10px] font-bold text-center uppercase tracking-tighter">SALDO DANA</span>

                            <div x-show="paymentMethod === 'dana_sdk'" class="absolute text-blue-500 top-1 right-1 animate-bounce">
                                <i class="fas fa-check-circle text-[10px]"></i>
                            </div>
                        </div>


                    <div class="pt-5 mt-5 border-t border-dashed border-slate-200">

                        <div x-show="paymentMethod === 'cash'" x-transition>
                            <label class="block mb-2 text-[10px] font-bold uppercase text-slate-500">Nominal Diterima</label>
                            <div class="relative">
                                <span class="absolute text-lg font-bold left-4 top-3.5 text-slate-400">Rp</span>
                                <input type="number" x-model="cashAmount" placeholder="0" inputmode="numeric"
                                       class="w-full pl-12 pr-4 py-3 text-2xl font-black transition border bg-slate-50 rounded-xl text-slate-800 border-slate-200 focus:ring-2 focus:ring-red-500">
                            </div>

                            <div class="grid grid-cols-4 gap-2 mt-3 sm:grid-cols-7">
                                 <button @click="cashAmount = 10000" class="text-[10px] px-2 py-2 bg-white border border-slate-200 rounded-lg font-bold hover:border-slate-400 text-center active:scale-95 transition">10k</button>
                                 <button @click="cashAmount = 20000" class="text-[10px] px-2 py-2 bg-white border border-slate-200 rounded-lg font-bold hover:border-slate-400 text-center active:scale-95 transition">20k</button>
                                 <button @click="cashAmount = 30000" class="text-[10px] px-2 py-2 bg-white border border-slate-200 rounded-lg font-bold hover:border-slate-400 text-center active:scale-95 transition">30k</button>
                                 <button @click="cashAmount = 40000" class="text-[10px] px-2 py-2 bg-white border border-slate-200 rounded-lg font-bold hover:border-slate-400 text-center active:scale-95 transition">40k</button>
                                 <button @click="cashAmount = 50000" class="text-[10px] px-2 py-2 bg-white border border-slate-200 rounded-lg font-bold hover:border-slate-400 text-center active:scale-95 transition">50k</button>
                                 <button @click="cashAmount = 100000" class="text-[10px] px-2 py-2 bg-white border border-slate-200 rounded-lg font-bold hover:border-slate-400 text-center active:scale-95 transition">100k</button>
                                 <button @click="cashAmount = grandTotal" class="col-span-2 text-white border sm:col-span-1 text-[10px] px-2 py-2 bg-slate-800 border-slate-800 rounded-lg font-bold hover:bg-black text-center active:scale-95 transition">Uang Pas</button>
                            </div>

                            <div class="flex items-center justify-between p-4 mt-4 transition-colors rounded-xl"
                                 :class="change < 0 ? 'bg-red-50 text-red-700' : 'bg-emerald-50 text-emerald-700'">
                                <span class="text-sm font-bold">Kembalian</span>
                                <span class="text-xl font-black" x-text="change < 0 ? 'Kurang Rp ' + rupiah(Math.abs(change)) : 'Rp ' + rupiah(change)"></span>
                            </div>
                        </div>

                        <div x-show="paymentMethod === 'affiliate_balance'" x-transition class="py-2 text-center">
                            <label class="block mb-2 text-[10px] font-bold text-purple-600 uppercase">PIN Keamanan (6 Digit)</label>
                            <div class="relative max-w-[240px] mx-auto">
                                <input type="password" x-model="affiliatePin" placeholder="******" maxlength="6" inputmode="numeric"
                                       class="w-full px-4 py-3 text-center text-3xl font-black text-purple-800 bg-white rounded-xl border border-purple-200 focus:ring-4 focus:ring-purple-100 tracking-[0.5em] transition placeholder-purple-200">
                            </div>
                        </div>

                        <div x-show="paymentMethod === 'tripay'" x-transition class="mt-4">
                            <div x-show="isLoadingChannels" class="py-4 text-center text-slate-400"><i class="fas fa-circle-notch fa-spin"></i></div>
                            <div x-show="!isLoadingChannels && tripayChannels.length > 0" class="grid grid-cols-2 gap-2 p-1 overflow-y-auto sm:grid-cols-4 max-h-60 custom-scrollbar">
                                <template x-for="channel in tripayChannels" :key="channel.code">
                                    <button @click="paymentChannel = channel.code" x-show="channel.active"
                                            class="relative flex flex-col items-center justify-center h-16 gap-1 p-2 transition bg-white border rounded-lg hover:border-red-300 active:scale-95"
                                            :class="paymentChannel === channel.code ? 'border-red-600 bg-red-50 ring-1 ring-red-600' : 'border-slate-200'">
                                        <img :src="channel.icon_url" loading="lazy" class="object-contain h-5">
                                        <span class="text-[9px] font-bold text-slate-600 text-center leading-none" x-text="channel.name"></span>
                                    </button>
                                </template>
                            </div>
                        </div>

                        <div x-show="paymentMethod === 'qris_manual'" x-transition class="p-4 mt-4 text-center border bg-gray-50 border-gray-200 rounded-xl">
                            <p class="mb-2 text-xs font-bold text-gray-500 uppercase">Scan QRIS Toko</p>
                            <div class="inline-block p-2 bg-white border shadow-sm rounded-lg">
                                <img src="https://tokosancaka.com/storage/qris_toko.jpg" loading="lazy" alt="QRIS Manual" class="object-contain w-48 h-48">
                            </div>
                            <p class="mt-2 text-xs text-gray-400">Tunjukkan bukti bayar ke kasir setelah scan.</p>
                        </div>

                    </div>
                </div>

            </div>
        </div>
    </div>

    <div class="flex justify-end gap-3 p-4 bg-white border-t shadow-[0_-4px_20px_rgba(0,0,0,0.05)] shrink-0 z-20 sm:p-6">
        <div class="hidden mr-auto lg:block">
            <p class="text-xs text-slate-400">Pastikan data sudah benar sebelum memproses.</p>
        </div>

        <button @click="showPaymentModal = false" class="px-6 py-3 font-bold transition rounded-xl text-slate-500 hover:bg-slate-100 active:scale-95">
            Kembali
        </button>

        <button @click="checkout()"
            :disabled="isProcessing || (paymentMethod === 'cash' && change < 0)"
            class="w-full sm:w-auto px-8 py-3 rounded-xl font-bold text-lg shadow-lg active:scale-95 transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
            :class="{
                'bg-blue-600 shadow-blue-200 hover:bg-blue-700': paymentMethod === 'dana',
                'bg-amber-500 shadow-amber-200 hover:bg-amber-600 text-white': paymentMethod === 'pay_later',
                'bg-gray-800 shadow-gray-300 hover:bg-black text-white': paymentMethod === 'qris_manual',
                'bg-red-600 shadow-red-200 hover:bg-red-700 text-white': !['dana', 'pay_later', 'qris_manual'].includes(paymentMethod)
            }">

            <span x-show="!isProcessing">
                <span x-text="paymentMethod === 'dana' ? 'Bayar via DANA' : (paymentMethod === 'pay_later' ? 'Simpan Tagihan' : 'Bayar & Cetak Struk')"></span>
            </span>

            <span x-show="isProcessing">
                <i class="fas fa-spinner fa-spin"></i> Sedang Memproses...
            </span>

            <i x-show="!isProcessing" class="fas fa-arrow-right"></i>
        </button>

    </div>
</div>
