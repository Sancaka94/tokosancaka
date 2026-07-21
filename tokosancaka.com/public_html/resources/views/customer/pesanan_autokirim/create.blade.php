@extends('layouts.customer')

@section('content')
<div class="max-w-6xl mx-auto px-4 py-8 font-sans" x-data="orderForm" x-init="initPaymentChannels()">
    <div class="mb-8">
        <h1 class="text-3xl font-extrabold text-gray-900 tracking-tight">Kirim Paket <span class="text-blue-600">Autokirim</span></h1>
        <p class="text-gray-500 mt-2">Isi detail pengiriman dengan cepat, akurat, dan dapatkan tarif terbaik dari server logistik.</p>
    </div>

    @if(session('success'))
        <div class="p-4 mb-6 text-sm text-green-700 bg-green-50 rounded-xl border border-green-200 shadow-sm font-bold">
            <i class="fa-solid fa-circle-check"></i> {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="p-4 mb-6 text-sm text-red-700 bg-red-50 rounded-xl border border-red-200 shadow-sm font-bold">
            <i class="fa-solid fa-circle-xmark"></i> {{ session('error') }}
        </div>
    @endif

    <form action="{{ route('customer.pesanan-autokirim.store') }}" method="POST" class="grid grid-cols-1 lg:grid-cols-12 gap-8">
        @csrf

        <!-- SISI KIRI: DATA PENGIRIM & PENERIMA -->
        <div class="lg:col-span-7 space-y-6">
            <!-- (Isi Form Pengirim & Penerima persis sama seperti kode Anda sebelumnya) -->
            <!-- Card Data Pengirim -->
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200/80">
                <h2 class="text-lg font-bold text-gray-800 mb-4"><i class="fa-solid fa-user-check text-blue-500 mr-2"></i> Data Pengirim</h2>
                <div class="grid grid-cols-2 gap-4">
                    <div class="col-span-2 sm:col-span-1">
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Nama Lengkap</label>
                        <input type="text" name="pengirim_nama" required class="w-full border-gray-200 rounded-xl text-sm focus:ring-1 focus:ring-blue-500 p-2.5 bg-gray-50">
                    </div>
                    <div class="col-span-2 sm:col-span-1">
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Nomor HP</label>
                        <input type="number" name="pengirim_hp" required class="w-full border-gray-200 rounded-xl text-sm focus:ring-1 focus:ring-blue-500 p-2.5 bg-gray-50">
                    </div>
                    <div class="col-span-2 relative" @click.away="showSenderDropdown = false">
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Cari Kecamatan / Kabupaten</label>
                        <input type="text" x-model="senderQuery" @input.debounce.400ms="searchAddress('sender')" @focus="showSenderDropdown = true" autocomplete="off" class="w-full border-gray-200 rounded-xl text-sm focus:ring-1 focus:ring-blue-500 p-2.5 bg-gray-50">
                        <input type="hidden" name="pengirim_district_id" x-model="senderDistrictId">
                        <!-- Dropdown Sender -->
                        <div x-show="showSenderDropdown" class="absolute z-50 w-full mt-1 bg-white rounded-xl shadow-xl border border-gray-200 max-h-48 overflow-y-auto" x-cloak>
                            <template x-for="res in senderResults">
                                <div @click="selectAddress('sender', res)" class="p-3 hover:bg-blue-50 cursor-pointer border-b text-sm">
                                    <p class="font-bold text-gray-800" x-text="res.district_name + ', ' + res.regency_name"></p>
                                    <p class="text-xs text-gray-500" x-text="res.province_name + ' (' + res.zip + ')'"></p>
                                </div>
                            </template>
                        </div>
                    </div>
                    <div class="col-span-2">
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Alamat Lengkap</label>
                        <textarea name="pengirim_alamat" rows="2" required class="w-full border-gray-200 rounded-xl text-sm p-2.5 bg-gray-50"></textarea>
                    </div>
                </div>
            </div>

            <!-- Card Data Penerima -->
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200/80">
                <h2 class="text-lg font-bold text-gray-800 mb-4"><i class="fa-solid fa-location-dot text-red-500 mr-2"></i> Data Penerima</h2>
                <div class="grid grid-cols-2 gap-4">
                    <div class="col-span-2 sm:col-span-1">
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Nama Lengkap</label>
                        <input type="text" name="penerima_nama" required class="w-full border-gray-200 rounded-xl text-sm focus:ring-1 focus:ring-blue-500 p-2.5 bg-gray-50">
                    </div>
                    <div class="col-span-2 sm:col-span-1">
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Nomor HP</label>
                        <input type="number" name="penerima_hp" required class="w-full border-gray-200 rounded-xl text-sm focus:ring-1 focus:ring-blue-500 p-2.5 bg-gray-50">
                    </div>
                    <div class="col-span-2 relative" @click.away="showReceiverDropdown = false">
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Cari Kecamatan / Kabupaten</label>
                        <input type="text" x-model="receiverQuery" @input.debounce.400ms="searchAddress('receiver')" @focus="showReceiverDropdown = true" autocomplete="off" class="w-full border-gray-200 rounded-xl text-sm focus:ring-1 focus:ring-blue-500 p-2.5 bg-gray-50">
                        <input type="hidden" name="penerima_district_id" x-model="receiverDistrictId">
                        <!-- Dropdown Receiver -->
                        <div x-show="showReceiverDropdown" class="absolute z-50 w-full mt-1 bg-white rounded-xl shadow-xl border border-gray-200 max-h-48 overflow-y-auto" x-cloak>
                            <template x-for="res in receiverResults">
                                <div @click="selectAddress('receiver', res)" class="p-3 hover:bg-blue-50 cursor-pointer border-b text-sm">
                                    <p class="font-bold text-gray-800" x-text="res.district_name + ', ' + res.regency_name"></p>
                                    <p class="text-xs text-gray-500" x-text="res.province_name + ' (' + res.zip + ')'"></p>
                                </div>
                            </template>
                        </div>
                    </div>
                    <div class="col-span-2">
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Alamat Lengkap</label>
                        <textarea name="penerima_alamat" rows="2" required class="w-full border-gray-200 rounded-xl text-sm p-2.5 bg-gray-50"></textarea>
                    </div>
                </div>
            </div>
        </div>

        <!-- SISI KANAN: DETAIL BARANG & PEMBAYARAN -->
        <div class="lg:col-span-5 space-y-6">
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200/80">
                <h2 class="text-lg font-bold text-gray-800 mb-4"><i class="fa-solid fa-box-open text-orange-500 mr-2"></i> Detail Paket</h2>

                <!-- Kategori, Deskripsi, Berat, Qty (Sama persis) -->
                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Kategori Barang</label>
                        <select name="kategori_barang" class="w-full border-gray-200 rounded-xl text-sm p-2.5 bg-gray-50">
                            @foreach($kategoriBarang as $kategori)
                                <option value="{{ $kategori }}">{{ $kategori }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Deskripsi Isi Paket</label>
                        <input type="text" name="deskripsi_barang" required class="w-full border-gray-200 rounded-xl text-sm p-2.5 bg-gray-50">
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">Berat Total (Gr)</label>
                            <input type="number" name="berat_gram" x-model="berat" min="1" required class="w-full border-gray-200 rounded-xl text-sm p-2.5 bg-gray-50">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">Jumlah Koli</label>
                            <input type="number" name="qty" x-model="qty" min="1" required class="w-full border-gray-200 rounded-xl text-sm p-2.5 bg-gray-50">
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">Metode Serah Terima</label>
                            <select name="is_sender_pp" x-model="isSenderPp" class="w-full border-gray-200 rounded-xl text-sm p-2.5 bg-gray-50">
                                <option value="1">🚗 Kurir Jemput (Pickup)</option>
                                <option value="0">🏢 Antar ke Cabang</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">Harga Barang (Wajib)</label>
                            <input type="number" name="nilai_barang" placeholder="Nominal Rp" required class="w-full border-gray-200 rounded-xl text-sm p-2.5 bg-gray-50">
                        </div>
                    </div>
                </div>

                <div class="mt-6 border-t border-gray-100 pt-6">
                    <button type="button" @click="cekOngkir()" :disabled="isLoading" class="w-full py-3.5 rounded-xl font-bold text-white bg-gray-900 hover:bg-black transition shadow-md flex justify-center items-center">
                        <i class="fa-solid fa-spinner fa-spin mr-2" x-show="isLoading" x-cloak></i>
                        <span x-text="isLoading ? 'Mencari Tarif...' : 'Hitung & Pilih Ekspedisi'"></span>
                    </button>
                </div>
            </div>

            <!-- MODAL & LIST PEMBAYARAN TRIPAY (DINAMIS ALpine.js) -->
            <div x-show="selectedOngkir > 0" x-transition class="bg-gradient-to-br from-blue-50 to-indigo-50/50 p-6 rounded-2xl border border-blue-200 shadow-sm" x-cloak>

                <input type="hidden" name="kurir_terpilih" x-model="selectedKurir">
                <input type="hidden" name="layanan_terpilih" x-model="selectedLayanan">
                <input type="hidden" name="ongkir_terpilih" x-model="selectedOngkir">
                <input type="hidden" name="service_code_terpilih" x-model="selectedServiceCode">
                <input type="hidden" name="metode_pembayaran" x-model="selectedPayment">

                <!-- Ringkasan Ekspedisi -->
                <div class="bg-white p-4 rounded-xl shadow-sm flex items-center justify-between mb-5">
                    <div>
                        <p class="font-black text-gray-900 text-sm" x-text="selectedKurir"></p>
                        <p class="text-xs text-gray-500" x-text="selectedLayanan"></p>
                    </div>
                    <div class="text-right">
                        <p class="font-black text-blue-700 text-lg">Rp <span x-text="selectedOngkir.toLocaleString('id-ID')"></span></p>
                    </div>
                </div>

                <!-- LIST METODE PEMBAYARAN -->
                <h3 class="text-xs font-extrabold text-gray-700 uppercase mb-3"><i class="fa-solid fa-wallet text-indigo-600 mr-1"></i> Metode Pembayaran</h3>

                <div class="space-y-2 max-h-60 overflow-y-auto pr-1 custom-scrollbar">

                    <!-- 1. POTONG SALDO -->
                    <div @click="selectedPayment = 'saldo_wallet'"
                         class="p-3 bg-white border rounded-xl cursor-pointer flex items-center justify-between"
                         :class="selectedPayment === 'saldo_wallet' ? 'border-indigo-600 bg-indigo-50/50' : 'border-gray-200'">
                        <div class="flex items-center gap-3">
                            <input type="radio" name="pay_rad" class="w-4 h-4 text-indigo-600 pointer-events-none" :checked="selectedPayment === 'saldo_wallet'">
                            <div class="w-8 h-8 rounded-lg bg-green-100 flex items-center justify-center"><i class="fa-solid fa-money-bill-wave text-green-600"></i></div>
                            <div>
                                <p class="font-bold text-gray-900 text-sm">Potong Saldo Akun</p>
                                <p class="text-xs text-green-600 font-semibold">Sisa Saldo: Rp {{ number_format(auth()->user()->saldo ?? 0, 0, ',', '.') }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- 2. TRIPAY CHANNELS (AJAX) -->
                    <p x-show="isFetchingTripay" class="text-xs text-center text-gray-500 py-2"><i class="fa-solid fa-spinner fa-spin"></i> Memuat Pembayaran Online...</p>

                    <template x-for="channel in tripayChannels" :key="channel.code">
                        <div @click="selectedPayment = channel.code"
                             class="p-3 bg-white border rounded-xl cursor-pointer flex items-center justify-between"
                             :class="selectedPayment === channel.code ? 'border-indigo-600 bg-indigo-50/50' : 'border-gray-200'">
                            <div class="flex items-center gap-3">
                                <input type="radio" name="pay_rad" class="w-4 h-4 text-indigo-600 pointer-events-none" :checked="selectedPayment === channel.code">
                                <div class="w-8 h-8 rounded-lg bg-gray-50 flex items-center justify-center p-1 border">
                                    <img :src="channel.icon_url" class="max-w-full max-h-full">
                                </div>
                                <div>
                                    <p class="font-bold text-gray-900 text-sm" x-text="channel.name"></p>
                                    <p class="text-[10px] text-gray-400 uppercase" x-text="channel.group_name"></p>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>

                <!-- SUBMIT -->
                <div class="mt-5">
                    <button type="submit" :disabled="!selectedPayment" class="w-full py-4 rounded-xl font-bold text-white transition text-base shadow-lg flex justify-center items-center" :class="selectedPayment ? 'bg-blue-600 hover:bg-blue-700' : 'bg-gray-300'">
                        <span x-text="selectedPayment === 'saldo_wallet' ? 'BAYAR & TERBITKAN RESI' : 'BAYAR SEKARANG'"></span>
                    </button>
                </div>
            </div>

        </div>

        <!-- MODAL PILIH EKSPEDISI (Sama seperti sebelumnya) -->
        <div x-show="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-sm" x-cloak>
            <div class="bg-white rounded-2xl w-full max-w-2xl overflow-hidden shadow-2xl">
                <div class="p-5 border-b flex justify-between"><h3 class="font-black text-lg">Pilih Ekspedisi</h3><button type="button" @click="showModal = false"><i class="fa-solid fa-xmark text-xl"></i></button></div>
                <div class="p-5 max-h-[60vh] overflow-y-auto space-y-3">
                    <template x-for="ongkir in ongkirList">
                        <div @click="tempSelected = ongkir" class="p-4 border rounded-xl cursor-pointer flex justify-between items-center" :class="tempSelected === ongkir ? 'border-blue-600 bg-blue-50' : 'border-gray-200'">
                            <div>
                                <p class="font-bold" x-text="ongkir.kurir"></p>
                                <p class="text-xs text-gray-500" x-text="ongkir.layanan + ' (' + ongkir.estimasi + ')'"></p>
                            </div>
                            <p class="font-black text-blue-700">Rp <span x-text="ongkir.harga.toLocaleString('id-ID')"></span></p>
                        </div>
                    </template>
                </div>
                <div class="p-4 border-t text-right bg-gray-50">
                    <button type="button" @click="applySelection()" :disabled="!tempSelected" class="px-6 py-2 rounded-xl text-white font-bold" :class="tempSelected ? 'bg-blue-600' : 'bg-gray-300'">Gunakan Ekspedisi Ini</button>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('orderForm', () => ({
        berat: 1000, qty: 1, isSenderPp: 1,
        senderQuery: '', senderDistrictId: '', senderResults: [], showSenderDropdown: false,
        receiverQuery: '', receiverDistrictId: '', receiverResults: [], showReceiverDropdown: false,

        showModal: false, isLoading: false, ongkirList: [], tempSelected: null,
        selectedKurir: '', selectedLayanan: '', selectedOngkir: 0, selectedServiceCode: '', selectedPayment: '',

        // Data Tripay
        tripayChannels: [], isFetchingTripay: false,

        async searchAddress(type) {
            let q = type === 'sender' ? this.senderQuery : this.receiverQuery;
            if(q.length < 3) return;
            try {
                let res = await (await fetch(`/api/autokirim/search-address?q=${encodeURIComponent(q)}`)).json();
                if(type === 'sender') this.senderResults = res; else this.receiverResults = res;
            } catch(e) {}
        },
        selectAddress(type, res) {
            if(type === 'sender') { this.senderQuery = res.district_name+', '+res.regency_name; this.senderDistrictId = res.district_id; this.showSenderDropdown = false; }
            else { this.receiverQuery = res.district_name+', '+res.regency_name; this.receiverDistrictId = res.district_id; this.showReceiverDropdown = false; }
        },
        async cekOngkir() {
            this.isLoading = true; this.ongkirList = []; this.tempSelected = null;
            try {
                let formData = new FormData(document.querySelector('form'));
                let res = await (await fetch(`/api/autokirim/cek-ongkir`, { method: 'POST', body: formData })).json();
                if(res.success) { this.ongkirList = res.data; this.showModal = true; }
                else alert("Gagal: " + res.message);
            } catch(e) {} finally { this.isLoading = false; }
        },
        applySelection() {
            if(!this.tempSelected) return;
            this.selectedKurir = this.tempSelected.kurir;
            this.selectedLayanan = this.tempSelected.layanan;
            this.selectedOngkir = this.tempSelected.harga;
            this.selectedServiceCode = this.tempSelected.kode_layanan;
            this.showModal = false;
        },

        // Memuat Channel Tripay via AJAX saat halaman pertama dibuka
        async initPaymentChannels() {
            this.isFetchingTripay = true;
            try {
                // Pastikan Anda sudah mendaftarkan route untuk ini di web.php
                let res = await (await fetch(`/api/autokirim/tripay-channels`)).json();
                if(res.success) this.tripayChannels = res.data.filter(c => c.active);
            } catch(e) { console.error('Tripay fetch error', e); }
            this.isFetchingTripay = false;
        }
    }));
});
</script>
@endsection
