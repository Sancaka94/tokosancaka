<div x-show="showPaymentModal" style="display: none;"
     class="fixed inset-0 z-50 bg-slate-100 flex flex-col"
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="opacity-0 translate-y-full"
     x-transition:enter-end="opacity-100 translate-y-0"
     x-transition:leave="transition ease-in duration-200"
     x-transition:leave-start="opacity-100 translate-y-0"
     x-transition:leave-end="opacity-0 translate-y-full">

    <div class="h-16 px-6 bg-white border-b border-slate-200 flex justify-between items-center shadow-sm shrink-0 z-20">
        <div class="flex items-center gap-3">
            <div class="h-10 w-10 rounded-xl bg-red-50 text-red-600 flex items-center justify-center">
                <i class="fas fa-cash-register text-xl"></i>
            </div>
            <div>
                <h3 class="font-black text-xl text-slate-800 leading-tight">Pembayaran</h3>
                <p class="text-xs text-slate-500 font-medium">Selesaikan transaksi pesanan ini</p>
            </div>
        </div>

        <button @click="showPaymentModal = false" class="group flex items-center gap-2 px-4 py-2 rounded-full bg-red-500 hover:bg-red-600 text-slate-500 hover:text-red-100 transition border border-transparent hover:border-red-100">
            <span class="text-xs font-bold hidden sm:block">BATAL / TUTUP</span>
            <i class="fas fa-times text-lg"></i>
        </button>
    </div>

    <div class="flex-1 flex flex-col lg:flex-row overflow-hidden relative">

        <div class="lg:w-[35%] bg-white border-r border-slate-200 overflow-y-auto custom-scrollbar flex flex-col order-2 lg:order-1 h-full shadow-[4px_0_24px_rgba(0,0,0,0.02)] z-10">
            <div class="p-6 space-y-6">
                <div class="text-center p-6 bg-slate-50 rounded-2xl border border-slate-100">
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-2">Total Yang Harus Dibayar</p>
                    <h2 class="text-4xl sm:text-5xl font-black text-slate-800 tracking-tight break-all" x-text="'Rp ' + rupiah(grandTotal)"></h2>
                    <div x-show="discountAmount > 0" class="mt-2 inline-flex items-center gap-1 px-3 py-1 bg-emerald-100 text-emerald-700 rounded-full text-xs font-bold">
                        <i class="fas fa-check-circle"></i> Hemat Rp <span x-text="rupiah(discountAmount)"></span>
                    </div>
                </div>

                <div class="space-y-3">
                    <h4 class="text-sm font-bold text-slate-700 border-b border-slate-100 pb-2">Rincian Biaya</h4>

                    <div class="flex justify-between items-center text-sm text-slate-600">
                        <span>Subtotal (<span x-text="cartTotalQty"></span> Item)</span>
                        <span class="font-bold text-slate-800" x-text="'Rp ' + rupiah(subtotal)"></span>
                    </div>

                    <div x-show="discountAmount > 0" class="flex justify-between items-center text-sm text-emerald-600">
                        <span>Potongan Diskon</span>
                        <span class="font-bold" x-text="'- Rp ' + rupiah(discountAmount)"></span>
                    </div>

                    <div x-show="deliveryType === 'shipping'" class="flex justify-between items-center text-sm text-blue-600">
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

            <div class="mt-auto p-6 bg-slate-50 border-t border-slate-100 hidden lg:block">
                <div class="flex items-center gap-3 text-slate-400 text-xs">
                    <i class="fas fa-shield-alt text-xl"></i>
                    <p>Transaksi aman. Pastikan data sudah benar.</p>
                </div>
            </div>
        </div>

        <div class="lg:w-[65%] bg-slate-50/50 overflow-y-auto custom-scrollbar p-4 sm:p-8 order-1 lg:order-2 h-full">
            <div class="max-w-3xl mx-auto space-y-6">

                <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
                    <label class="flex items-center gap-2 text-xs font-bold text-slate-500 uppercase tracking-widest mb-4">
                        <span class="w-6 h-6 rounded-full bg-slate-100 flex items-center justify-center text-slate-600">1</span>
                        Data Pelanggan & Pengiriman
                    </label>

                    <div class="flex p-1 bg-slate-100 border border-slate-200 rounded-xl mb-4 w-full sm:w-80">
                        <button @click="customerType = 'guest'; selectedCustomerId = '';"
                                class="flex-1 py-2 text-xs font-bold rounded-lg transition-all"
                                :class="customerType === 'guest' ? 'bg-white text-red-600 shadow-sm ring-1 ring-slate-200' : 'text-slate-500 hover:text-slate-700'">
                            Tamu (Guest)
                        </button>
                        <button @click="customerType = 'member'"
                                class="flex-1 py-2 text-xs font-bold rounded-lg transition-all"
                                :class="customerType === 'member' ? 'bg-white text-green-600 shadow-sm ring-1 ring-slate-200' : 'text-slate-500 hover:text-slate-700'">
                            Member
                        </button>
                    </div>

                    <div class="mb-4">
                        <label class="text-[10px] font-bold text-slate-500 mb-1 block">Metode Penyerahan</label>
                        <div class="grid grid-cols-2 gap-3">
                            <button @click="deliveryType = 'pickup'; shippingCost = 0; selectedCourier = null"
                                    class="py-2 px-3 rounded-lg border text-xs font-bold flex items-center justify-center gap-2 transition"
                                    :class="deliveryType === 'pickup' ? 'border-blue-500 bg-blue-50 text-blue-700' : 'border-slate-200 bg-white hover:bg-slate-50'">
                                <i class="fas fa-store"></i> Ambil di Toko
                            </button>
                            <button @click="deliveryType = 'shipping'"
                                    class="py-2 px-3 rounded-lg border text-xs font-bold flex items-center justify-center gap-2 transition"
                                    :class="deliveryType === 'shipping' ? 'border-blue-500 bg-blue-50 text-blue-700' : 'border-slate-200 bg-white hover:bg-slate-50'">
                                <i class="fas fa-truck"></i> Kirim (Ekspedisi)
                            </button>
                        </div>
                    </div>

                    <div x-show="customerType === 'guest'" x-transition>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-3">
                            <div>
                                <label class="text-[10px] font-bold text-slate-500">Nama Penerima*</label>
                                <input type="text" x-model="customerName" class="w-full mt-1 px-3 py-2 rounded-lg border border-slate-300 text-sm focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div>
                                <label class="text-[10px] font-bold text-slate-500">WhatsApp*</label>
                                <input type="number" x-model="customerPhone" class="w-full mt-1 px-3 py-2 rounded-lg border border-slate-300 text-sm focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div class="md:col-span-2">
                                    <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Alamat Lengkap (Opsional)</label>
                                    <textarea x-model="customerAddressDetail" rows="2" placeholder="Alamat jalan, nomor rumah, patokan..."
                                              class="w-full px-4 py-2.5 rounded-xl border border-slate-300 focus:ring-red-500 focus:border-red-500 text-sm font-medium text-slate-700 resize-none"></textarea>
                            </div>
                        </div>

                        <div x-show="deliveryType === 'shipping'">
                            <div class="mb-3">
                                <label class="text-[10px] font-bold text-slate-500">Cari Kecamatan / Kelurahan*</label>
                                <div class="relative mt-1">
                                    <input type="text" x-model="searchQuery" @input.debounce.500ms="searchLocation()" placeholder="Ketik nama kecamatan..." class="w-full pl-8 pr-4 py-2 rounded-lg border border-slate-300 text-sm">
                                    <i class="fas fa-search absolute left-3 top-3 text-slate-400 text-xs"></i>

                                    <div x-show="searchResults.length > 0" @click.outside="searchResults = []" class="absolute z-50 w-full mt-1 bg-white border border-slate-200 rounded-lg shadow-xl max-h-48 overflow-y-auto">
                                        <template x-for="loc in searchResults">
                                            <div @click="selectLocation(loc)" class="px-4 py-2 hover:bg-blue-50 cursor-pointer border-b border-slate-50 text-xs">
                                                <p class="font-bold text-slate-700" x-text="loc.full_address"></p>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="text-[10px] font-bold text-slate-500">Detail Alamat (Jalan, RT/RW)*</label>
                                <textarea x-model="customerAddressDetail" rows="2" class="w-full mt-1 px-3 py-2 rounded-lg border border-slate-300 text-sm resize-none"></textarea>
                            </div>

                            <div x-show="isLoadingShipping" class="text-center py-4 bg-slate-50 rounded-lg border border-dashed border-slate-300">
                                <i class="fas fa-circle-notch fa-spin text-blue-500"></i> <span class="text-xs text-slate-500 ml-2">Cek Ongkir...</span>
                            </div>

                            <div x-show="!isLoadingShipping && courierList.length > 0" class="mt-4">
                                <p class="text-[10px] font-bold text-slate-400 uppercase mb-2">Pilih Layanan Pengiriman</p>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 max-h-60 overflow-y-auto custom-scrollbar p-1">
                                    <template x-for="courier in courierList" :key="courier.service + courier.cost">
                                        <div @click="selectCourier(courier)"
                                             class="flex items-center p-2 rounded-lg border cursor-pointer transition hover:bg-blue-50 relative"
                                             :class="selectedCourier && selectedCourier.service === courier.service && selectedCourier.cost === courier.cost ? 'border-blue-500 bg-blue-50 ring-1 ring-blue-500' : 'border-slate-200 bg-white'">
                                            <div class="w-10 h-10 bg-white rounded border border-slate-100 flex items-center justify-center p-1 mr-3 shrink-0">
                                                <img :src="courier.logo" alt="Logo" class="w-full h-full object-contain" x-show="courier.logo">
                                                <i class="fas fa-box text-slate-300" x-show="!courier.logo"></i>
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <div class="flex justify-between items-center">
                                                    <p class="text-[11px] font-bold text-slate-700 truncate" x-text="courier.name"></p>
                                                    <p class="text-xs font-black text-blue-600" x-text="rupiah(courier.cost)"></p>
                                                </div>
                                                <p class="text-[10px] text-slate-500 truncate" x-text="courier.service + ' (' + courier.etd + ' Hari)'"></p>
                                            </div>
                                            <div x-show="selectedCourier && selectedCourier.service === courier.service" class="absolute top-1 right-1 text-blue-600"><i class="fas fa-check-circle text-[10px]"></i></div>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div x-show="customerType === 'member'" style="display: none;" x-transition>
                        <label class="text-[10px] font-bold text-slate-500">Cari Member Terdaftar</label>
                        <select x-model="selectedCustomerId" class="w-full mt-1 px-4 py-3 rounded-xl border border-slate-200 text-sm bg-slate-50 font-bold text-slate-700 focus:ring-2 focus:ring-red-500">
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

                        <div x-show="selectedCustomerId" class="mt-3 flex gap-3">
                            <div class="flex-1 p-3 bg-blue-50 rounded-xl border border-blue-100 flex flex-col items-center">
                                <span class="text-[10px] text-blue-400 font-bold uppercase">Saldo Topup</span>
                                <span class="text-sm font-black text-blue-700" x-text="'Rp ' + rupiah(getSelectedMemberSaldo())"></span>
                            </div>
                            <div class="flex-1 p-3 bg-purple-50 rounded-xl border border-purple-100 flex flex-col items-center">
                                <span class="text-[10px] text-purple-400 font-bold uppercase">Profit Afiliasi</span>
                                <span class="text-sm font-black text-purple-700" x-text="'Rp ' + rupiah(getSelectedAffiliateBalance())"></span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
                    <label class="flex items-center gap-2 text-xs font-bold text-slate-500 uppercase tracking-widest mb-4">
                        <span class="w-6 h-6 rounded-full bg-slate-100 flex items-center justify-center text-slate-600">2</span>
                        Metode Pembayaran
                    </label>

                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-3">

                        <div @click="paymentMethod = 'pay_later'"
                            class="cursor-pointer border-2 rounded-xl p-2 flex flex-col items-center justify-center gap-1 transition relative overflow-hidden group h-20"
                            :class="paymentMethod === 'pay_later' ? 'border-amber-500 bg-amber-50 text-amber-700' : 'border-slate-100 bg-white hover:border-amber-200 hover:bg-slate-50'">
                            <i class="fas fa-clock text-lg"></i>
                            <span class="text-[10px] font-bold text-center">Bayar Nanti</span>
                            <div x-show="paymentMethod === 'pay_later'" class="absolute top-1 right-1 text-amber-500"><i class="fas fa-check-circle text-[10px]"></i></div>
                        </div>

                        <div @click="paymentMethod = 'qris_manual'"
                            class="cursor-pointer border-2 rounded-xl p-2 flex flex-col items-center justify-center gap-1 transition relative overflow-hidden group h-20"
                            :class="paymentMethod === 'qris_manual' ? 'border-gray-800 bg-gray-100 text-gray-900' : 'border-slate-100 bg-white hover:border-gray-400 hover:bg-slate-50'">
                            <i class="fas fa-qrcode text-lg"></i>
                            <span class="text-[10px] font-bold text-center">QRIS Manual</span>
                            <div x-show="paymentMethod === 'qris_manual'" class="absolute top-1 right-1 text-gray-800"><i class="fas fa-check-circle text-[10px]"></i></div>
                        </div>

                        <div @click="paymentMethod = 'cash'"
                             class="cursor-pointer border-2 rounded-xl p-2 flex flex-col items-center justify-center gap-1 transition relative overflow-hidden group h-20"
                             :class="paymentMethod === 'cash' ? 'border-red-500 bg-red-50 text-red-700' : 'border-slate-100 bg-white hover:border-red-200 hover:bg-slate-50'">
                            <i class="fas fa-money-bill-wave text-lg"></i>
                            <span class="text-[10px] font-bold text-center">Tunai</span>
                            <div x-show="paymentMethod === 'cash'" class="absolute top-1 right-1 text-red-500"><i class="fas fa-check-circle text-[10px]"></i></div>
                        </div>

                        <div @click="paymentMethod = 'saldo'"
                             class="cursor-pointer border-2 rounded-xl p-2 flex flex-col items-center justify-center gap-1 transition relative overflow-hidden group h-20"
                             :class="paymentMethod === 'saldo' ? 'border-blue-500 bg-blue-50 text-blue-700' : 'border-slate-100 bg-white hover:border-blue-200 hover:bg-slate-50'">
                            <i class="fas fa-wallet text-lg"></i>
                            <span class="text-[10px] font-bold text-center">Saldo</span>
                            <div x-show="paymentMethod === 'saldo'" class="absolute top-1 right-1 text-blue-500"><i class="fas fa-check-circle text-[10px]"></i></div>
                        </div>

                        <div @click="selectAffiliatePayment()"
                             class="cursor-pointer border-2 rounded-xl p-2 flex flex-col items-center justify-center gap-1 transition relative overflow-hidden group h-20"
                             :class="paymentMethod === 'affiliate_balance' ? 'border-purple-500 bg-purple-50 text-purple-700' : 'border-slate-100 bg-white hover:border-purple-200 hover:bg-slate-50'">
                            <i class="fas fa-coins text-lg"></i>
                            <span class="text-[10px] font-bold text-center">Profit</span>
                            <div x-show="paymentMethod === 'affiliate_balance'" class="absolute top-1 right-1 text-purple-500"><i class="fas fa-check-circle text-[10px]"></i></div>
                        </div>

                        <div @click="paymentMethod = 'tripay'; fetchTripayChannels()"
                             class="cursor-pointer border-2 rounded-xl p-2 flex flex-col items-center justify-center gap-1 transition relative overflow-hidden group h-20"
                             :class="paymentMethod === 'tripay' ? 'border-red-500 bg-red-50 text-red-700' : 'border-slate-100 bg-white hover:border-red-200 hover:bg-slate-50'">
                            <i class="fas fa-qrcode text-lg"></i>
                            <span class="text-[10px] font-bold text-center">QRIS/VA</span>
                            <div x-show="paymentMethod === 'tripay'" class="absolute top-1 right-1 text-red-500"><i class="fas fa-check-circle text-[10px]"></i></div>
                        </div>

                        <div @click="paymentMethod = 'doku'"
                             class="cursor-pointer border-2 rounded-xl p-2 flex flex-col items-center justify-center gap-1 transition relative overflow-hidden group h-20"
                             :class="paymentMethod === 'doku' ? 'border-red-500 bg-red-50 text-red-700' : 'border-slate-100 bg-white hover:border-red-200 hover:bg-slate-50'">
                            <i class="fas fa-credit-card text-lg"></i>
                            <span class="text-[10px] font-bold text-center">DOKU</span>
                            <div x-show="paymentMethod === 'doku'" class="absolute top-1 right-1 text-red-500"><i class="fas fa-check-circle text-[10px]"></i></div>
                        </div>

                        <div @click="paymentMethod = 'dana'"
                            class="cursor-pointer border-2 rounded-xl p-2 flex flex-col items-center justify-center gap-1 transition relative overflow-hidden group h-20"
                            :class="paymentMethod === 'dana' ? 'border-blue-500 bg-blue-50 text-blue-700' : 'border-slate-100 bg-white hover:border-blue-200 hover:bg-slate-50'">

                            <img src="https://tokosancaka.com/storage/logo/dana.png"
                                alt="DANA" class="h-4 object-contain mb-1 transition-all group-hover:scale-110">

                            <span class="text-[10px] font-bold text-center uppercase tracking-tighter">DANA</span>

                            <div x-show="paymentMethod === 'dana'" class="absolute top-1 right-1 text-blue-500 animate-bounce">
                                <i class="fas fa-check-circle text-[10px]"></i>
                            </div>
                        </div>

                    </div>


                    <div class="mt-5 pt-5 border-t border-dashed border-slate-200">

                        <div x-show="paymentMethod === 'cash'" x-transition>
                            <label class="block text-[10px] font-bold text-slate-500 uppercase mb-2">Nominal Diterima</label>
                            <div class="relative">
                                <span class="absolute left-4 top-3.5 text-slate-400 font-bold text-lg">Rp</span>
                                <input type="number" x-model="cashAmount" placeholder="0"
                                       class="w-full pl-12 pr-4 py-3 text-2xl font-black text-slate-800 bg-slate-50 rounded-xl border border-slate-200 focus:ring-2 focus:ring-red-500 transition">
                            </div>

                            <div class="grid grid-cols-4 sm:grid-cols-7 gap-2 mt-3">
                                 <button @click="cashAmount = 10000" class="text-[10px] px-2 py-2 bg-white border border-slate-200 rounded-lg font-bold hover:border-slate-400 text-center">10k</button>
                                 <button @click="cashAmount = 20000" class="text-[10px] px-2 py-2 bg-white border border-slate-200 rounded-lg font-bold hover:border-slate-400 text-center">20k</button>
                                 <button @click="cashAmount = 30000" class="text-[10px] px-2 py-2 bg-white border border-slate-200 rounded-lg font-bold hover:border-slate-400 text-center">30k</button>
                                 <button @click="cashAmount = 40000" class="text-[10px] px-2 py-2 bg-white border border-slate-200 rounded-lg font-bold hover:border-slate-400 text-center">40k</button>
                                 <button @click="cashAmount = 50000" class="text-[10px] px-2 py-2 bg-white border border-slate-200 rounded-lg font-bold hover:border-slate-400 text-center">50k</button>
                                 <button @click="cashAmount = 100000" class="text-[10px] px-2 py-2 bg-white border border-slate-200 rounded-lg font-bold hover:border-slate-400 text-center">100k</button>
                                 <button @click="cashAmount = grandTotal" class="col-span-2 sm:col-span-1 text-[10px] px-2 py-2 bg-slate-800 text-white border border-slate-800 rounded-lg font-bold hover:bg-black text-center">Uang Pas</button>
                            </div>

                            <div class="mt-4 p-4 rounded-xl flex justify-between items-center transition-colors"
                                 :class="change < 0 ? 'bg-red-50 text-red-700' : 'bg-emerald-50 text-emerald-700'">
                                <span class="font-bold text-sm">Kembalian</span>
                                <span class="font-black text-xl" x-text="change < 0 ? 'Kurang Rp ' + rupiah(Math.abs(change)) : 'Rp ' + rupiah(change)"></span>
                            </div>
                        </div>

                        <div x-show="paymentMethod === 'affiliate_balance'" x-transition class="text-center py-2">
                            <label class="block text-[10px] font-bold text-purple-600 uppercase mb-2">PIN Keamanan (6 Digit)</label>
                            <div class="relative max-w-[240px] mx-auto">
                                <input type="password" x-model="affiliatePin" placeholder="******" maxlength="6"
                                       class="w-full px-4 py-3 text-center text-3xl font-black text-purple-800 bg-white rounded-xl border border-purple-200 focus:ring-4 focus:ring-purple-100 tracking-[0.5em] transition placeholder-purple-200">
                            </div>
                        </div>

                        <div x-show="paymentMethod === 'tripay'" x-transition class="mt-4">
                            <div x-show="isLoadingChannels" class="text-center py-4 text-slate-400"><i class="fas fa-circle-notch fa-spin"></i></div>
                            <div x-show="!isLoadingChannels && tripayChannels.length > 0" class="grid grid-cols-2 sm:grid-cols-4 gap-2 max-h-60 overflow-y-auto custom-scrollbar p-1">
                                <template x-for="channel in tripayChannels" :key="channel.code">
                                    <button @click="paymentChannel = channel.code" x-show="channel.active"
                                            class="p-2 rounded-lg border transition flex flex-col items-center justify-center gap-1 h-16 bg-white hover:border-red-300 relative"
                                            :class="paymentChannel === channel.code ? 'border-red-600 bg-red-50 ring-1 ring-red-600' : 'border-slate-200'">
                                        <img :src="channel.icon_url" class="h-5 object-contain">
                                        <span class="text-[9px] font-bold text-slate-600 text-center leading-none" x-text="channel.name"></span>
                                    </button>
                                </template>
                            </div>
                        </div>

                        <div x-show="paymentMethod === 'qris_manual'" x-transition class="mt-4 p-4 bg-gray-50 border border-gray-200 rounded-xl text-center">
                            <p class="text-xs font-bold text-gray-500 uppercase mb-2">Scan QRIS Toko</p>
                            <div class="bg-white p-2 inline-block rounded-lg shadow-sm border">
                                <img src="https://tokosancaka.com/storage/qris_toko.jpg" alt="QRIS Manual" class="w-48 h-48 object-contain">
                            </div>
                            <p class="text-xs text-gray-400 mt-2">Tunjukkan bukti bayar ke kasir setelah scan.</p>
                        </div>

                    </div>
                </div>

            </div>
        </div>
    </div>

    <div class="p-4 sm:p-6 bg-white border-t border-slate-200 shrink-0 z-20 flex justify-end gap-3 shadow-[0_-4px_20px_rgba(0,0,0,0.05)]">
        <div class="hidden lg:block mr-auto">
            <p class="text-xs text-slate-400">Pastikan data sudah benar sebelum memproses.</p>
        </div>

        <button @click="showPaymentModal = false" class="px-6 py-3 rounded-xl font-bold text-slate-500 hover:bg-slate-100 transition">
            Kembali
        </button>

        <button @click="checkout()"
            :disabled="isProcessing || (paymentMethod === 'cash' && change < 0)"
            class="w-full sm:w-auto px-8 py-3 rounded-xl font-bold text-lg shadow-lg active:scale-[0.98] transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
            :class="{
                'bg-blue-600 shadow-blue-200 hover:bg-blue-700': paymentMethod === 'dana',
                'bg-amber-500 shadow-amber-200 hover:bg-amber-600 text-white': paymentMethod === 'pay_later',
                'bg-gray-800 shadow-gray-300 hover:bg-black text-white': paymentMethod === 'qris_manual',
                'bg-red-600 shadow-red-200 hover:bg-red-700': !['dana', 'pay_later', 'qris_manual'].includes(paymentMethod)
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
