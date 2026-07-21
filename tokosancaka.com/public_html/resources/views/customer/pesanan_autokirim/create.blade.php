@extends('layouts.customer')

@section('content')
<div class="max-w-6xl mx-auto px-4 py-8 font-sans" x-data="orderForm()">
    <div class="mb-8">
        <h1 class="text-3xl font-extrabold text-gray-900 tracking-tight">Kirim Paket <span class="text-blue-600">Autokirim</span></h1>
        <p class="text-gray-500 mt-2">Isi detail pengiriman dengan cepat, akurat, dan dapatkan tarif terbaik.</p>
    </div>

    @if(session('success'))
        <div class="p-4 mb-6 text-sm text-green-700 bg-green-50 rounded-xl border border-green-200">
            <i class="fa-solid fa-circle-check mr-2"></i> {{ session('success') }}
        </div>
    @endif

    <form action="{{ route('customer.pesanan-autokirim.store') }}" method="POST" class="grid grid-cols-1 lg:grid-cols-12 gap-8">
        @csrf

        <!-- KIRI: Pengirim & Penerima -->
        <div class="lg:col-span-7 space-y-6">

            <!-- Box Pengirim -->
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-bold text-gray-800 flex items-center"><i class="fa-solid fa-user-check text-blue-500 mr-2"></i> Data Pengirim</h2>
                    <button type="button" @click="saveContact('pengirim')" class="text-xs font-semibold text-blue-600 bg-blue-50 px-3 py-1.5 rounded-lg hover:bg-blue-100 transition">Simpan Kontak</button>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div class="col-span-2 sm:col-span-1">
                        <label class="block text-xs font-medium text-gray-600 mb-1">Nama Lengkap</label>
                        <input type="text" name="pengirim_nama" required class="w-full border-gray-200 rounded-xl text-sm focus:ring-blue-500 focus:border-blue-500 px-4 py-2.5 bg-gray-50 hover:bg-white transition">
                    </div>
                    <div class="col-span-2 sm:col-span-1">
                        <label class="block text-xs font-medium text-gray-600 mb-1">Nomor HP / WA</label>
                        <input type="number" name="pengirim_hp" required class="w-full border-gray-200 rounded-xl text-sm focus:ring-blue-500 focus:border-blue-500 px-4 py-2.5 bg-gray-50 hover:bg-white transition">
                    </div>

                    <!-- Autocomplete Kodepos Pengirim -->
                    <div class="col-span-2 relative" @click.away="showSenderDropdown = false">
                        <label class="block text-xs font-medium text-gray-600 mb-1">Kecamatan / Kodepos Pengirim</label>
                        <input type="text" x-model="senderQuery" @input.debounce.500ms="searchAddress('sender')" @focus="showSenderDropdown = true" placeholder="Ketik nama kecamatan atau kota..." class="w-full border-gray-200 rounded-xl text-sm focus:ring-blue-500 focus:border-blue-500 px-4 py-2.5 bg-gray-50 hover:bg-white transition">
                        <input type="hidden" name="pengirim_kodepos" x-model="senderZip">

                        <div x-show="showSenderDropdown && senderResults.length > 0" class="absolute z-50 w-full mt-1 bg-white rounded-xl shadow-lg border border-gray-100 max-h-48 overflow-y-auto">
                            <template x-for="res in senderResults">
                                <div @click="selectAddress('sender', res)" class="px-4 py-2 hover:bg-blue-50 cursor-pointer border-b border-gray-50 text-sm">
                                    <p class="font-semibold text-gray-800" x-text="res.district_name + ', ' + res.regency_name"></p>
                                    <p class="text-xs text-gray-500" x-text="res.province_name + ' - ' + res.zip"></p>
                                </div>
                            </template>
                        </div>
                    </div>

                    <div class="col-span-2">
                        <label class="block text-xs font-medium text-gray-600 mb-1">Alamat Lengkap (Jalan, RT/RW, Patokan)</label>
                        <textarea name="pengirim_alamat" rows="2" required class="w-full border-gray-200 rounded-xl text-sm focus:ring-blue-500 focus:border-blue-500 px-4 py-2.5 bg-gray-50 hover:bg-white transition"></textarea>
                    </div>
                </div>
            </div>

            <!-- Box Penerima -->
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-bold text-gray-800 flex items-center"><i class="fa-solid fa-location-dot text-red-500 mr-2"></i> Data Penerima</h2>
                    <button type="button" @click="saveContact('penerima')" class="text-xs font-semibold text-red-600 bg-red-50 px-3 py-1.5 rounded-lg hover:bg-red-100 transition">Simpan Kontak</button>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div class="col-span-2 sm:col-span-1">
                        <label class="block text-xs font-medium text-gray-600 mb-1">Nama Lengkap</label>
                        <input type="text" name="penerima_nama" required class="w-full border-gray-200 rounded-xl text-sm focus:ring-blue-500 focus:border-blue-500 px-4 py-2.5 bg-gray-50 hover:bg-white transition">
                    </div>
                    <div class="col-span-2 sm:col-span-1">
                        <label class="block text-xs font-medium text-gray-600 mb-1">Nomor HP / WA</label>
                        <input type="number" name="penerima_hp" required class="w-full border-gray-200 rounded-xl text-sm focus:ring-blue-500 focus:border-blue-500 px-4 py-2.5 bg-gray-50 hover:bg-white transition">
                    </div>

                    <!-- Autocomplete Kodepos Penerima -->
                    <div class="col-span-2 relative" @click.away="showReceiverDropdown = false">
                        <label class="block text-xs font-medium text-gray-600 mb-1">Kecamatan / Kodepos Penerima</label>
                        <input type="text" x-model="receiverQuery" @input.debounce.500ms="searchAddress('receiver')" @focus="showReceiverDropdown = true" placeholder="Ketik nama kecamatan atau kota..." class="w-full border-gray-200 rounded-xl text-sm focus:ring-blue-500 focus:border-blue-500 px-4 py-2.5 bg-gray-50 hover:bg-white transition">
                        <input type="hidden" name="penerima_kodepos" x-model="receiverZip">

                        <div x-show="showReceiverDropdown && receiverResults.length > 0" class="absolute z-50 w-full mt-1 bg-white rounded-xl shadow-lg border border-gray-100 max-h-48 overflow-y-auto">
                            <template x-for="res in receiverResults">
                                <div @click="selectAddress('receiver', res)" class="px-4 py-2 hover:bg-blue-50 cursor-pointer border-b border-gray-50 text-sm">
                                    <p class="font-semibold text-gray-800" x-text="res.district_name + ', ' + res.regency_name"></p>
                                    <p class="text-xs text-gray-500" x-text="res.province_name + ' - ' + res.zip"></p>
                                </div>
                            </template>
                        </div>
                    </div>

                    <div class="col-span-2">
                        <label class="block text-xs font-medium text-gray-600 mb-1">Alamat Lengkap (Jalan, RT/RW, Patokan)</label>
                        <textarea name="penerima_alamat" rows="2" required class="w-full border-gray-200 rounded-xl text-sm focus:ring-blue-500 focus:border-blue-500 px-4 py-2.5 bg-gray-50 hover:bg-white transition"></textarea>
                    </div>
                </div>
            </div>
        </div>

        <!-- KANAN: Detail Paket & Eksekusi -->
        <div class="lg:col-span-5 space-y-6">
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
                <h2 class="text-lg font-bold text-gray-800 mb-4 flex items-center"><i class="fa-solid fa-box-open text-orange-500 mr-2"></i> Detail Paket</h2>

                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Kategori Barang</label>
                        <select name="kategori_barang" class="w-full border-gray-200 rounded-xl text-sm focus:ring-blue-500 px-4 py-2.5 bg-gray-50">
                            <option value="Pakaian">Pakaian / Fashion</option>
                            <option value="Elektronik">Elektronik</option>
                            <option value="Dokumen">Dokumen</option>
                            <option value="Makanan">Makanan Kering</option>
                            <option value="Lainnya">Lainnya</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Deskripsi Isi Paket</label>
                        <input type="text" name="deskripsi_barang" placeholder="Contoh: Sepatu Sneakers Hitam" required class="w-full border-gray-200 rounded-xl text-sm focus:ring-blue-500 px-4 py-2.5 bg-gray-50">
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Berat Aktual (Gram)</label>
                            <input type="number" name="berat_gram" x-model="berat" required class="w-full border-gray-200 rounded-xl text-sm focus:ring-blue-500 px-4 py-2.5 bg-gray-50">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Asuransi Pengiriman?</label>
                            <div class="flex items-center h-10 px-4 bg-gray-50 border border-gray-200 rounded-xl">
                                <input type="checkbox" name="asuransi" x-model="asuransi" class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                                <span class="ml-2 text-sm text-gray-700 font-medium">Ya, Asuransikan</span>
                            </div>
                        </div>
                    </div>

                    <div x-show="asuransi" x-transition class="p-4 bg-blue-50 rounded-xl border border-blue-100">
                        <label class="block text-xs font-medium text-blue-800 mb-1">Nilai Barang (Rp)</label>
                        <input type="number" name="nilai_barang" placeholder="Harga asli barang..." class="w-full border-blue-200 rounded-lg text-sm focus:ring-blue-500 px-4 py-2">
                        <p class="text-[10px] text-blue-600 mt-1">* Wajib diisi jika klaim asuransi.</p>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Dimensi / Volume (Opsional)</label>
                        <div class="flex gap-2">
                            <input type="number" name="panjang_cm" placeholder="P (cm)" class="w-1/3 border-gray-200 rounded-xl text-sm text-center bg-gray-50">
                            <span class="text-gray-400 mt-2">x</span>
                            <input type="number" name="lebar_cm" placeholder="L (cm)" class="w-1/3 border-gray-200 rounded-xl text-sm text-center bg-gray-50">
                            <span class="text-gray-400 mt-2">x</span>
                            <input type="number" name="tinggi_cm" placeholder="T (cm)" class="w-1/3 border-gray-200 rounded-xl text-sm text-center bg-gray-50">
                        </div>
                    </div>
                </div>

                <div class="mt-6 border-t border-gray-100 pt-6">
                    <button type="button" @click="cekOngkir()" :disabled="isLoading" class="w-full py-3.5 rounded-xl font-bold text-white bg-gray-900 hover:bg-black transition shadow-lg flex justify-center items-center">
                        <i class="fa-solid fa-calculator mr-2" x-show="!isLoading"></i>
                        <i class="fa-solid fa-spinner fa-spin mr-2" x-show="isLoading"></i>
                        <span x-text="isLoading ? 'Menghitung...' : 'Cek Ongkos Kirim'"></span>
                    </button>
                </div>
            </div>

            <!-- HASIL CEK ONGKIR -->
            <div x-show="ongkirList.length > 0" x-transition class="bg-white p-6 rounded-2xl shadow-sm border border-blue-200">
                <h2 class="text-sm font-bold text-gray-800 mb-3 uppercase tracking-wide">Pilih Layanan Pengiriman</h2>

                <input type="hidden" name="kurir_terpilih" x-model="selectedKurir">
                <input type="hidden" name="layanan_terpilih" x-model="selectedLayanan">
                <input type="hidden" name="ongkir_terpilih" x-model="selectedOngkir">

                <div class="space-y-3 max-h-60 overflow-y-auto pr-2 custom-scrollbar">
                    <template x-for="(ongkir, index) in ongkirList" :key="index">
                        <label class="flex items-center justify-between p-4 border rounded-xl cursor-pointer transition-all duration-200"
                               :class="selectedKurir === ongkir.kurir && selectedLayanan === ongkir.layanan ? 'border-blue-500 bg-blue-50 shadow-md ring-1 ring-blue-500' : 'border-gray-200 hover:border-blue-300'">
                            <div class="flex items-center gap-3">
                                <input type="radio" name="pilih_kurir" class="w-4 h-4 text-blue-600 focus:ring-blue-500 border-gray-300"
                                       @click="selectOngkir(ongkir)">
                                <div>
                                    <p class="font-bold text-gray-900 text-sm" x-text="ongkir.kurir"></p>
                                    <p class="text-xs text-gray-500" x-text="ongkir.layanan + ' (' + ongkir.estimasi + ')'"></p>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="font-bold text-blue-700 text-sm">Rp <span x-text="ongkir.harga.toLocaleString('id-ID')"></span></p>
                            </div>
                        </label>
                    </template>
                </div>

                <div class="mt-5">
                    <button type="submit" x-show="selectedOngkir > 0" class="w-full py-4 rounded-xl font-extrabold text-white bg-blue-600 hover:bg-blue-700 transition shadow-blue-500/30 shadow-lg text-lg">
                        BUAT PESANAN SEKARANG
                    </button>
                </div>
            </div>

        </div>
    </form>
</div>

<!-- Script Alpine JS untuk Logika Form -->
<script>
function orderForm() {
    return {
        // Form Data
        berat: 1000,
        asuransi: false,

        // Sender Autocomplete
        senderQuery: '',
        senderZip: '',
        senderResults: [],
        showSenderDropdown: false,

        // Receiver Autocomplete
        receiverQuery: '',
        receiverZip: '',
        receiverResults: [],
        showReceiverDropdown: false,

        // Ongkir Data
        isLoading: false,
        ongkirList: [],
        selectedKurir: '',
        selectedLayanan: '',
        selectedOngkir: 0,

        // Fitur Pencarian Alamat Ajax
        async searchAddress(type) {
            let query = type === 'sender' ? this.senderQuery : this.receiverQuery;
            if (query.length < 3) {
                if(type === 'sender') this.senderResults = [];
                else this.receiverResults = [];
                return;
            }

            try {
                let response = await fetch(`/api/autokirim/search-address?q=${query}`);
                let data = await response.json();

                if(type === 'sender') {
                    this.senderResults = data;
                    this.showSenderDropdown = true;
                } else {
                    this.receiverResults = data;
                    this.showReceiverDropdown = true;
                }
            } catch (error) {
                console.error("Error fetching address:", error);
            }
        },

        selectAddress(type, res) {
            let formatText = `${res.district_name}, ${res.regency_name}`;
            if(type === 'sender') {
                this.senderQuery = formatText;
                this.senderZip = res.zip;
                this.showSenderDropdown = false;
            } else {
                this.receiverQuery = formatText;
                this.receiverZip = res.zip;
                this.showReceiverDropdown = false;
            }
        },

        // Fitur Cek Ongkir Ajax
        async cekOngkir() {
            if(!this.senderZip || !this.receiverZip || !this.berat) {
                alert("Harap lengkapi wilayah Pengirim, wilayah Penerima, dan Berat Barang!");
                return;
            }

            this.isLoading = true;
            this.ongkirList = [];
            this.selectedOngkir = 0;

            try {
                let formData = new FormData();
                formData.append('origin_zip', this.senderZip);
                formData.append('destination_zip', this.receiverZip);
                formData.append('berat_gram', this.berat);
                // Tambahkan token CSRF
                formData.append('_token', document.querySelector('input[name="_token"]').value);

                let response = await fetch(`/api/autokirim/cek-ongkir`, {
                    method: 'POST',
                    body: formData
                });

                let result = await response.json();
                if(result.success) {
                    this.ongkirList = result.data;
                } else {
                    alert("Gagal mengambil tarif: " + result.message);
                }
            } catch (error) {
                console.error("Error cek ongkir:", error);
                alert("Terjadi kesalahan sistem saat mengecek tarif.");
            } finally {
                this.isLoading = false;
            }
        },

        selectOngkir(ongkir) {
            this.selectedKurir = ongkir.kurir;
            this.selectedLayanan = ongkir.layanan;
            this.selectedOngkir = ongkir.harga;
        },

        saveContact(type) {
            alert(`Kontak ${type} berhasil disimpan ke Buku Alamat! (Dummy action)`);
        }
    }
}
</script>

<style>
/* Custom Scrollbar untuk Box Ongkir */
.custom-scrollbar::-webkit-scrollbar { width: 5px; }
.custom-scrollbar::-webkit-scrollbar-track { background: #f1f5f9; border-radius: 10px; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
</style>
@endsection
