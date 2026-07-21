@extends('layouts.customer')

@section('content')
<div class="max-w-6xl mx-auto px-4 py-8 font-sans" x-data="orderForm">
    <div class="mb-8">
        <h1 class="text-3xl font-extrabold text-gray-900 tracking-tight">Kirim Paket <span class="text-blue-600">Autokirim</span></h1>
        <p class="text-gray-500 mt-2">Isi detail pengiriman dengan cepat, akurat, dan dapatkan tarif terbaik dari server logistik.</p>
    </div>

    <!-- Alert Notifikasi Berhasil -->
    @if(session('success'))
        <div class="p-4 mb-6 text-sm text-green-700 bg-green-50 rounded-xl border border-green-200 flex items-center gap-2 shadow-sm">
            <i class="fa-solid fa-circle-check text-lg text-green-600"></i>
            <div><span class="font-bold">Sukses!</span> {{ session('success') }}</div>
        </div>
    @endif

    <!-- Alert Notifikasi Gagal -->
    @if(session('error'))
        <div class="p-4 mb-6 text-sm text-red-700 bg-red-50 rounded-xl border border-red-200 flex items-center gap-2 shadow-sm">
            <i class="fa-solid fa-circle-xmark text-lg text-red-600"></i>
            <div><span class="font-bold">Gagal!</span> {{ session('error') }}</div>
        </div>
    @endif

    <form action="{{ route('customer.pesanan-autokirim.store') }}" method="POST" class="grid grid-cols-1 lg:grid-cols-12 gap-8">
        @csrf

        <!-- ========================================== -->
        <!-- SISI KIRI: DATA PENGIRIM & PENERIMA -->
        <!-- ========================================== -->
        <div class="lg:col-span-7 space-y-6">

            <!-- Card Data Pengirim -->
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200/80">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-bold text-gray-800 flex items-center"><i class="fa-solid fa-user-check text-blue-500 mr-2"></i> Data Pengirim</h2>
                    <button type="button" @click="saveContact('Pengirim')" class="text-xs font-semibold text-blue-600 bg-blue-50 px-3 py-1.5 rounded-lg hover:bg-blue-100 transition duration-200">Simpan Kontak</button>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div class="col-span-2 sm:col-span-1">
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Nama Lengkap</label>
                        <input type="text" name="pengirim_nama" value="{{ old('pengirim_nama') }}" required class="w-full border-gray-200 rounded-xl text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500 px-4 py-2.5 bg-gray-50/50 hover:bg-white transition duration-200">
                    </div>
                    <div class="col-span-2 sm:col-span-1">
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Nomor HP / WA</label>
                        <input type="number" name="pengirim_hp" value="{{ old('pengirim_hp') }}" required class="w-full border-gray-200 rounded-xl text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500 px-4 py-2.5 bg-gray-50/50 hover:bg-white transition duration-200">
                    </div>

                    <!-- Autocomplete Alamat Pengirim -->
                    <div class="col-span-2 relative" @click.away="showSenderDropdown = false">
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Kecamatan / Kabupaten Pengirim</label>
                        <div class="relative">
                            <input type="text" x-model="senderQuery" @input.debounce.400ms="searchAddress('sender')" @focus="showSenderDropdown = true" placeholder="Ketik minimal 3 karakter wilayah pengirim..." class="w-full border-gray-200 rounded-xl text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500 pl-4 pr-10 py-2.5 bg-gray-50/50 hover:bg-white transition duration-200" autocomplete="off">
                            <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none text-gray-400 text-xs">
                                <i class="fa-solid fa-magnifying-glass" x-show="!isSearchingSender"></i>
                                <i class="fa-solid fa-spinner fa-spin text-blue-500" x-show="isSearchingSender" x-cloak></i>
                            </div>
                        </div>
                        <input type="hidden" name="pengirim_district_id" x-model="senderDistrictId">

                        <div x-show="showSenderDropdown" x-transition class="absolute z-[110] w-full mt-1 bg-white rounded-xl shadow-xl border border-gray-200 max-h-48 overflow-y-auto" x-cloak>
                            <template x-if="senderResults.length > 0">
                                <div>
                                    <template x-for="res in senderResults">
                                        <div @click="selectAddress('sender', res)" class="px-4 py-3 hover:bg-blue-50/80 cursor-pointer border-b border-gray-100 text-sm transition duration-150">
                                            <p class="font-bold text-gray-800" x-text="res.district_name + ', ' + res.regency_name"></p>
                                            <p class="text-xs text-gray-500 mt-0.5" x-text="res.province_name + ' (Kodepos: ' + res.zip + ')'"></p>
                                        </div>
                                    </template>
                                </div>
                            </template>
                            <template x-if="senderResults.length === 0 && !isSearchingSender && senderQuery.length >= 3">
                                <div class="px-4 py-3 text-sm text-red-500 italic text-center font-medium bg-red-50">
                                    <i class="fa-solid fa-circle-exclamation mr-1"></i> Wilayah tidak ditemukan di database.
                                </div>
                            </template>
                        </div>
                    </div>

                    <div class="col-span-2">
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Alamat Lengkap (Nama Jalan, RT/RW, Nomor Rumah)</label>
                        <textarea name="pengirim_alamat" rows="2" required class="w-full border-gray-200 rounded-xl text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500 px-4 py-2.5 bg-gray-50/50 hover:bg-white transition duration-200">{{ old('pengirim_alamat') }}</textarea>
                    </div>
                </div>
            </div>

            <!-- Card Data Penerima -->
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200/80">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-bold text-gray-800 flex items-center"><i class="fa-solid fa-location-dot text-red-500 mr-2"></i> Data Penerima</h2>
                    <button type="button" @click="saveContact('Penerima')" class="text-xs font-semibold text-red-600 bg-red-50 px-3 py-1.5 rounded-lg hover:bg-red-100 transition duration-200">Simpan Kontak</button>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div class="col-span-2 sm:col-span-1">
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Nama Lengkap</label>
                        <input type="text" name="penerima_nama" value="{{ old('penerima_nama') }}" required class="w-full border-gray-200 rounded-xl text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500 px-4 py-2.5 bg-gray-50/50 hover:bg-white transition duration-200">
                    </div>
                    <div class="col-span-2 sm:col-span-1">
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Nomor HP / WA</label>
                        <input type="number" name="penerima_hp" value="{{ old('penerima_hp') }}" required class="w-full border-gray-200 rounded-xl text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500 px-4 py-2.5 bg-gray-50/50 hover:bg-white transition duration-200">
                    </div>

                    <!-- Autocomplete Alamat Penerima -->
                    <div class="col-span-2 relative" @click.away="showReceiverDropdown = false">
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Kecamatan / Kabupaten Penerima</label>
                        <div class="relative">
                            <input type="text" x-model="receiverQuery" @input.debounce.400ms="searchAddress('receiver')" @focus="showReceiverDropdown = true" placeholder="Ketik minimal 3 karakter wilayah penerima..." class="w-full border-gray-200 rounded-xl text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500 pl-4 pr-10 py-2.5 bg-gray-50/50 hover:bg-white transition duration-200" autocomplete="off">
                            <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none text-gray-400 text-xs">
                                <i class="fa-solid fa-magnifying-glass" x-show="!isSearchingReceiver"></i>
                                <i class="fa-solid fa-spinner fa-spin text-blue-500" x-show="isSearchingReceiver" x-cloak></i>
                            </div>
                        </div>
                        <input type="hidden" name="penerima_district_id" x-model="receiverDistrictId">

                        <div x-show="showReceiverDropdown" x-transition class="absolute z-[110] w-full mt-1 bg-white rounded-xl shadow-xl border border-gray-200 max-h-48 overflow-y-auto" x-cloak>
                            <template x-if="receiverResults.length > 0">
                                <div>
                                    <template x-for="res in receiverResults">
                                        <div @click="selectAddress('receiver', res)" class="px-4 py-3 hover:bg-blue-50/80 cursor-pointer border-b border-gray-100 text-sm transition duration-150">
                                            <p class="font-bold text-gray-800" x-text="res.district_name + ', ' + res.regency_name"></p>
                                            <p class="text-xs text-gray-500 mt-0.5" x-text="res.province_name + ' (Kodepos: ' + res.zip + ')'"></p>
                                        </div>
                                    </template>
                                </div>
                            </template>
                            <template x-if="receiverResults.length === 0 && !isSearchingReceiver && receiverQuery.length >= 3">
                                <div class="px-4 py-3 text-sm text-red-500 italic text-center font-medium bg-red-50">
                                    <i class="fa-solid fa-circle-exclamation mr-1"></i> Wilayah tidak ditemukan di database.
                                </div>
                            </template>
                        </div>
                    </div>

                    <div class="col-span-2">
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Alamat Lengkap (Nama Jalan, RT/RW, Nomor Rumah)</label>
                        <textarea name="penerima_alamat" rows="2" required class="w-full border-gray-200 rounded-xl text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500 px-4 py-2.5 bg-gray-50/50 hover:bg-white transition duration-200">{{ old('penerima_alamat') }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- SISI KANAN: DETAIL BARANG & TARIF LOGISTIK -->
        <!-- ========================================== -->
        <div class="lg:col-span-5 space-y-6">
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200/80">
                <h2 class="text-lg font-bold text-gray-800 mb-4 flex items-center"><i class="fa-solid fa-box-open text-orange-500 mr-2"></i> Detail Paket</h2>

                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Kategori Barang</label>
                        <select name="kategori_barang" class="w-full border-gray-200 rounded-xl text-sm focus:ring-1 focus:ring-blue-500 px-4 py-2.5 bg-gray-50/50 font-medium">
                            <option value="Pakaian">Pakaian / Fashion</option>
                            <option value="Elektronik">Elektronik</option>
                            <option value="Dokumen">Dokumen / Surat</option>
                            <option value="Makanan">Makanan Kering</option>
                            <option value="Lainnya">Lainnya</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Deskripsi Isi Paket</label>
                        <input type="text" name="deskripsi_barang" placeholder="Contoh: Sepatu Sneakers Hitam Ukuran 42" required class="w-full border-gray-200 rounded-xl text-sm focus:ring-1 focus:ring-blue-500 px-4 py-2.5 bg-gray-50/50">
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">Berat Aktual (Gram)</label>
                            <input type="number" name="berat_gram" x-model="berat" required class="w-full border-gray-200 rounded-xl text-sm focus:ring-1 focus:ring-blue-500 px-4 py-2.5 bg-gray-50/50">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">Asuransi Pengiriman?</label>
                            <div class="flex items-center h-11 px-4 bg-gray-50/50 border border-gray-200 rounded-xl">
                                <input type="checkbox" name="asuransi" x-model="asuransi" class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500 cursor-pointer">
                                <span class="ml-2 text-sm text-gray-700 font-semibold cursor-pointer select-none" @click="asuransi = !asuransi">Ya, Amankan</span>
                            </div>
                        </div>
                    </div>

                    <!-- Input Nilai Barang Jika Asuransi Dicentang -->
                    <div x-show="asuransi" x-transition.duration.300ms class="p-4 bg-blue-50/60 rounded-xl border border-blue-100" x-cloak>
                        <label class="block text-xs font-semibold text-blue-800 mb-1">Nilai Harga Barang (Nominal Rp)</label>
                        <input type="number" name="nilai_barang" placeholder="Masukkan harga asli isi paket..." class="w-full border-blue-200 rounded-lg text-sm focus:ring-1 focus:ring-blue-500 px-4 py-2" :required="asuransi">
                        <p class="text-[10px] text-blue-600 mt-1.5 font-medium">* Nominal ini digunakan kurir sebagai acuan klaim asuransi jika barang hilang.</p>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Dimensi / Volume Paket (Centimeter - Opsional)</label>
                        <div class="flex gap-2">
                            <input type="number" name="panjang_cm" x-model="panjang" placeholder="P (cm)" class="w-1/3 border-gray-200 rounded-xl text-sm text-center bg-gray-50/50 py-2">
                            <span class="text-gray-400 mt-2 font-bold">×</span>
                            <input type="number" name="lebar_cm" x-model="lebar" placeholder="L (cm)" class="w-1/3 border-gray-200 rounded-xl text-sm text-center bg-gray-50/50 py-2">
                            <span class="text-gray-400 mt-2 font-bold">×</span>
                            <input type="number" name="tinggi_cm" x-model="tinggi" placeholder="T (cm)" class="w-1/3 border-gray-200 rounded-xl text-sm text-center bg-gray-50/50 py-2">
                        </div>
                    </div>
                </div>

                <div class="mt-6 border-t border-gray-100 pt-6">
                    <button type="button" @click="cekOngkir()" :disabled="isLoading" class="w-full py-3.5 rounded-xl font-bold text-white bg-gray-900 hover:bg-black transition-colors duration-200 shadow-md flex justify-center items-center disabled:opacity-50 disabled:cursor-not-allowed">
                        <i class="fa-solid fa-calculator mr-2" x-show="!isLoading"></i>
                        <i class="fa-solid fa-spinner fa-spin mr-2" x-show="isLoading" x-cloak></i>
                        <span x-text="isLoading ? 'Menghitung Ongkir...' : 'Hitung Ongkos Kirim'"></span>
                    </button>
                </div>
            </div>

            <!-- ======================================================= -->
            <!-- TAMPILAN PILIHAN JASA EKSPEDISI + LOGO DARI HELPER LOGISTIC -->
            <!-- ======================================================= -->
            <div x-show="ongkirList.length > 0" x-transition.duration.300ms class="bg-white p-6 rounded-2xl shadow-sm border border-blue-400" x-cloak>
                <h2 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-3">Pilih Jasa Kurir & Layanan</h2>

                <!-- Input Hidden ke Backend -->
                <input type="hidden" name="kurir_terpilih" x-model="selectedKurir">
                <input type="hidden" name="layanan_terpilih" x-model="selectedLayanan">
                <input type="hidden" name="ongkir_terpilih" x-model="selectedOngkir">
                <input type="hidden" name="service_code_terpilih" x-model="selectedServiceCode">

                <div class="space-y-3 max-h-[380px] overflow-y-auto pr-2 custom-scrollbar">
                    <template x-for="(ongkir, index) in ongkirList" :key="index">
                        <div @click="selectOngkir(ongkir)"
                             class="p-4 border rounded-xl cursor-pointer transition-all duration-200 flex flex-col justify-between gap-2.5"
                             :class="selectedServiceCode === ongkir.kode_layanan ? 'border-blue-600 bg-blue-50/70 shadow-sm ring-1 ring-blue-600' : 'border-gray-200 hover:border-blue-300 bg-white'">

                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-3.5">
                                    <input type="radio" name="pilih_kurir_radio" class="w-4 h-4 text-blue-600 focus:ring-blue-500 border-gray-300 pointer-events-none"
                                           :checked="selectedServiceCode === ongkir.kode_layanan">

                                    <!-- 🔥 KOTAK LOGO EKSPEDISI DARI HELPER -->
                                    <div class="w-12 h-12 bg-white rounded-xl border border-gray-100 flex items-center justify-center p-1.5 shadow-sm shrink-0 overflow-hidden relative">
                                        <template x-if="ongkir.logo_url">
                                            <!-- onerror trigger otomatis menghilangkan gambar jika link mati agar layout tidak berantakan -->
                                            <img :src="ongkir.logo_url" :alt="ongkir.kurir"
                                                 class="max-w-full max-h-full object-contain"
                                                 onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                                        </template>
                                        <!-- Fallback Icon jika logo rusak / url mati -->
                                        <i class="fa-solid fa-truck-fast text-gray-400 text-lg" style="display: none;"></i>
                                        <template x-if="!ongkir.logo_url">
                                            <i class="fa-solid fa-truck-fast text-gray-400 text-lg"></i>
                                        </template>
                                    </div>

                                    <div>
                                        <div class="flex items-center gap-2">
                                            <p class="font-extrabold text-gray-900 text-sm" x-text="ongkir.kurir"></p>
                                            <span x-show="ongkir.is_pickup" class="text-[10px] bg-green-100 text-green-700 font-bold px-1.5 py-0.5 rounded">Free Pickup</span>
                                        </div>
                                        <p class="text-xs font-semibold text-gray-600 mt-0.5" x-text="ongkir.layanan"></p>
                                    </div>
                                </div>

                                <div class="text-right">
                                    <p class="font-black text-blue-700 text-base">Rp <span x-text="ongkir.harga.toLocaleString('id-ID')"></span></p>
                                </div>
                            </div>

                            <!-- Baris Info Waktu: Estimasi Hari dan Tanggal Tiba (ETD) -->
                            <div class="flex items-center justify-between text-xs text-gray-500 border-t border-gray-100 pt-2.5 mt-0.5 pl-7">
                                <span><i class="fa-regular fa-clock text-gray-400 mr-1"></i> Durasi: <strong class="text-gray-700" x-text="ongkir.estimasi"></strong></span>
                                <span><i class="fa-regular fa-calendar-check text-green-500 mr-1"></i> Tiba: <strong class="text-gray-700" x-text="ongkir.etd"></strong></span>
                            </div>
                        </div>
                    </template>
                </div>

                <div class="mt-5">
                    <button type="submit" x-show="selectedOngkir > 0" class="w-full py-4 rounded-xl font-extrabold text-white bg-blue-600 hover:bg-blue-700 transition shadow-blue-500/20 shadow-xl text-lg tracking-wide" x-cloak>
                        BUAT PESANAN SEKARANG
                    </button>
                </div>
            </div>

        </div>
    </form>
</div>

<!-- ========================================== -->
<!-- SCRIPTS ENGINE LOGIC (ALPINE.JS V3) -->
<!-- ========================================== -->
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('orderForm', () => ({
        berat: 1000,
        asuransi: false,
        panjang: '',
        lebar: '',
        tinggi: '',

        // Autocomplete Pengirim
        senderQuery: '',
        senderDistrictId: '',
        senderResults: [],
        showSenderDropdown: false,
        isSearchingSender: false,

        // Autocomplete Penerima
        receiverQuery: '',
        receiverDistrictId: '',
        receiverResults: [],
        showReceiverDropdown: false,
        isSearchingReceiver: false,

        // Hitung Ongkir
        isLoading: false,
        ongkirList: [],
        selectedKurir: '',
        selectedLayanan: '',
        selectedOngkir: 0,
        selectedServiceCode: '',

        async searchAddress(type) {
            let query = type === 'sender' ? this.senderQuery : this.receiverQuery;

            if (query.length < 3) {
                if(type === 'sender') { this.senderResults = []; this.showSenderDropdown = false; }
                else { this.receiverResults = []; this.showReceiverDropdown = false; }
                return;
            }

            if (type === 'sender') { this.isSearchingSender = true; this.showSenderDropdown = true; }
            else { this.isSearchingReceiver = true; this.showReceiverDropdown = true; }

            try {
                let response = await fetch(`/api/autokirim/search-address?q=${encodeURIComponent(query)}`);
                let data = await response.json();

                if(type === 'sender') {
                    this.senderResults = data;
                } else {
                    this.receiverResults = data;
                }
            } catch (error) {
                console.error("Gagal memuat alamat dari server:", error);
            } finally {
                if (type === 'sender') this.isSearchingSender = false;
                else this.isSearchingReceiver = false;
            }
        },

        selectAddress(type, res) {
            let formatText = `${res.district_name}, ${res.regency_name}`;
            if(type === 'sender') {
                this.senderQuery = formatText;
                this.senderDistrictId = res.district_id;
                this.showSenderDropdown = false;
            } else {
                this.receiverQuery = formatText;
                this.receiverDistrictId = res.district_id;
                this.showReceiverDropdown = false;
            }
        },

        async cekOngkir() {
            if(!this.senderDistrictId || !this.receiverDistrictId || !this.berat) {
                alert("Mohon lengkapi wilayah Pengirim, wilayah Penerima dari dropdown yang muncul, dan Berat paket Anda!");
                return;
            }

            this.isLoading = true;
            this.ongkirList = [];
            this.selectedOngkir = 0;
            this.selectedServiceCode = '';

            try {
                let formData = new FormData();
                formData.append('origin_id', this.senderDistrictId);
                formData.append('destination_id', this.receiverDistrictId);
                formData.append('berat_gram', this.berat);
                formData.append('panjang_cm', this.panjang);
                formData.append('lebar_cm', this.lebar);
                formData.append('tinggi_cm', this.tinggi);
                formData.append('_token', document.querySelector('input[name="_token"]').value);

                let response = await fetch(`/api/autokirim/cek-ongkir`, {
                    method: 'POST',
                    body: formData
                });

                let result = await response.json();
                if(result.success) {
                    this.ongkirList = result.data;
                } else {
                    alert("Gagal memuat tarif kurir: " + result.message);
                }
            } catch (error) {
                console.error("Error Cek Ongkir:", error);
                alert("Terjadi masalah jaringan saat menghubungi server logistik.");
            } finally {
                this.isLoading = false;
            }
        },

        selectOngkir(ongkir) {
            this.selectedKurir = ongkir.kurir;
            this.selectedLayanan = ongkir.layanan;
            this.selectedOngkir = ongkir.harga;
            this.selectedServiceCode = ongkir.kode_layanan;
        },

        saveContact(role) {
            alert(`Fitur Sukses: Kontak ${role} berhasil diamankan ke Buku Alamat Anda!`);
        }
    }));
});
</script>

<style>
.custom-scrollbar::-webkit-scrollbar { width: 5px; }
.custom-scrollbar::-webkit-scrollbar-track { background: #f8fafc; border-radius: 10px; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
.custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
</style>
@endsection
