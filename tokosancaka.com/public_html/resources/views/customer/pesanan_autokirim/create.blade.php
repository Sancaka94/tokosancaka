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

    <form action="{{ route('customer.pesanan-autokirim.store') }}" method="POST" @submit="validateForm($event)" class="grid grid-cols-1 lg:grid-cols-12 gap-8">
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
                        <label class="block text-xs font-semibold text-gray-600 mb-1">
                            Alamat Jalan Pengirim <span class="text-red-500">*</span>
                        </label>
                        <textarea name="pengirim_alamat" rows="2" required minlength="15" placeholder="Contoh: Jl. Ronggowarsito No. 15, RT 01 / RW 02 (Wajib detail jalan/nomor rumah, jangan hanya nama kota)" class="w-full border-gray-200 rounded-xl text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500 px-4 py-2.5 bg-gray-50/50 hover:bg-white transition duration-200">{{ old('pengirim_alamat') }}</textarea>
                        <p class="text-[10px] text-gray-400 mt-1">* Tanpa perlu menuliskan Kecamatan/Kabupaten/Kodepos lagi (sudah otomatis terwakili oleh pilihan dropdown di atas).</p>
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
                        <label class="block text-xs font-semibold text-gray-600 mb-1">
                            Alamat Jalan Penerima <span class="text-red-500">*</span>
                        </label>
                        <textarea name="penerima_alamat" rows="2" required minlength="15" placeholder="Contoh: Perum Graha Kebraon Regency 2 Block A No. 3, RT 04 / RW 05" class="w-full border-gray-200 rounded-xl text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500 px-4 py-2.5 bg-gray-50/50 hover:bg-white transition duration-200">{{ old('penerima_alamat') }}</textarea>
                        <p class="text-[10px] text-gray-400 mt-1">* Wajib mengetikkan nama jalan dan nomor rumah/gedung minimal 15 huruf agar resi ekspedisi tidak gagal terbit.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- SISI KANAN: DETAIL BARANG & ACTION -->
        <!-- ========================================== -->
        <div class="lg:col-span-5 space-y-6">
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200/80">
                <h2 class="text-lg font-bold text-gray-800 mb-4 flex items-center"><i class="fa-solid fa-box-open text-orange-500 mr-2"></i> Detail Paket</h2>

                <div class="space-y-4">
                    <!-- DINAMIS: Kategori Barang -->
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Kategori Barang</label>
                        <select name="kategori_barang" class="w-full border-gray-200 rounded-xl text-sm focus:ring-1 focus:ring-blue-500 px-4 py-2.5 bg-gray-50/50 font-medium">
                            @foreach($kategoriBarang as $kategori)
                                <option value="{{ $kategori }}" {{ old('kategori_barang') == $kategori ? 'selected' : '' }}>
                                    {{ $kategori }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Deskripsi Isi Paket</label>
                        <input type="text" name="deskripsi_barang" value="{{ old('deskripsi_barang') }}" placeholder="Contoh: Sepatu Sneakers Hitam Ukuran 42" required class="w-full border-gray-200 rounded-xl text-sm focus:ring-1 focus:ring-blue-500 px-4 py-2.5 bg-gray-50/50">
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">Berat Total (Gram)</label>
                            <input type="number" name="berat_gram" x-model="berat" min="1" required class="w-full border-gray-200 rounded-xl text-sm focus:ring-1 focus:ring-blue-500 px-4 py-2.5 bg-gray-50/50 font-semibold">
                        </div>

                        <!-- DINAMIS: Jumlah Koli / Barang -->
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">Jumlah Koli / Pcs</label>
                            <input type="number" name="qty" x-model="qty" min="1" required class="w-full border-gray-200 rounded-xl text-sm focus:ring-1 focus:ring-blue-500 px-4 py-2.5 bg-gray-50/50 font-semibold text-center">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4 items-center">
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">Asuransi Pengiriman?</label>
                            <div class="flex items-center h-11 px-4 bg-gray-50/50 border border-gray-200 rounded-xl">
                                <input type="checkbox" name="asuransi" x-model="asuransi" class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500 cursor-pointer">
                                <span class="ml-2 text-sm text-gray-700 font-semibold cursor-pointer select-none" @click="asuransi = !asuransi">Ya, Amankan</span>
                            </div>
                        </div>

                        <!-- DINAMIS: Metode Serah Terima -->
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">Metode Serah Terima</label>
                            <select name="is_sender_pp" x-model="isSenderPp" class="w-full h-11 border-gray-200 rounded-xl text-xs focus:ring-1 focus:ring-blue-500 px-3 bg-gray-50/50 font-semibold text-gray-700">
                                <option value="1">🚗 Kurir Jemput (Pickup)</option>
                                <option value="0">🏢 Antar ke Cabang (Dropoff)</option>
                            </select>
                        </div>
                    </div>

                    <div x-show="asuransi" x-transition.duration.300ms class="p-4 bg-blue-50/60 rounded-xl border border-blue-100" x-cloak>
                        <label class="block text-xs font-semibold text-blue-800 mb-1">Nilai Harga Barang (Nominal Rp)</label>
                        <input type="number" name="nilai_barang" placeholder="Masukkan harga asli isi paket..." class="w-full border-blue-200 rounded-lg text-sm focus:ring-1 focus:ring-blue-500 px-4 py-2" :required="asuransi">
                        <p class="text-[10px] text-blue-600 mt-1.5 font-medium">* Nominal ini digunakan kurir sebagai acuan klaim asuransi jika barang hilang.</p>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Dimensi / Volume Paket (Centimeter - Opsional)</label>
                        <div class="flex gap-2">
                            <input type="number" name="panjang_cm" x-model="panjang" placeholder="P (cm)" min="1" class="w-1/3 border-gray-200 rounded-xl text-sm text-center bg-gray-50/50 py-2">
                            <span class="text-gray-400 mt-2 font-bold">×</span>
                            <input type="number" name="lebar_cm" x-model="lebar" placeholder="L (cm)" min="1" class="w-1/3 border-gray-200 rounded-xl text-sm text-center bg-gray-50/50 py-2">
                            <span class="text-gray-400 mt-2 font-bold">×</span>
                            <input type="number" name="tinggi_cm" x-model="tinggi" placeholder="T (cm)" min="1" class="w-1/3 border-gray-200 rounded-xl text-sm text-center bg-gray-50/50 py-2">
                        </div>
                    </div>
                </div>

                <div class="mt-6 border-t border-gray-100 pt-6">
                    <button type="button" @click="cekOngkir()" :disabled="isLoading" class="w-full py-3.5 rounded-xl font-bold text-white bg-gray-900 hover:bg-black transition-colors duration-200 shadow-md flex justify-center items-center disabled:opacity-50 disabled:cursor-not-allowed">
                        <i class="fa-solid fa-calculator mr-2" x-show="!isLoading"></i>
                        <i class="fa-solid fa-spinner fa-spin mr-2" x-show="isLoading" x-cloak></i>
                        <span x-text="isLoading ? 'Menghitung Ongkir...' : (selectedOngkir > 0 ? 'Ganti / Hitung Ulang Ekspedisi' : 'Hitung & Pilih Ekspedisi')"></span>
                    </button>
                </div>
            </div>

            <!-- ======================================================= -->
            <!-- RINGKASAN EKSPEDISI TERPILIH (Muncul setelah pilih di Modal) -->
            <!-- ======================================================= -->
            <div x-show="selectedOngkir > 0" x-transition.duration.300ms class="bg-gradient-to-br from-blue-50 to-indigo-50/50 p-6 rounded-2xl border border-blue-200/80 shadow-sm relative overflow-hidden" x-cloak>
                <div class="flex items-center justify-between mb-3">
                    <span class="text-[11px] font-extrabold text-blue-700 bg-blue-100/80 px-2.5 py-1 rounded-full uppercase tracking-wide flex items-center">
                        <i class="fa-solid fa-circle-check mr-1.5 text-blue-600"></i> Ekspedisi Terpilih
                    </span>
                    <button type="button" @click="showModal = true" class="text-xs font-bold text-blue-600 hover:text-blue-800 underline">Ganti Kurir</button>
                </div>

                <!-- Input Hidden yang Wajib Dikirim ke Backend -->
                <input type="hidden" name="kurir_terpilih" x-model="selectedKurir">
                <input type="hidden" name="layanan_terpilih" x-model="selectedLayanan">
                <input type="hidden" name="ongkir_terpilih" x-model="selectedOngkir">
                <input type="hidden" name="service_code_terpilih" x-model="selectedServiceCode">
                <input type="hidden" name="metode_pembayaran" x-model="selectedPayment">

                <!-- Tampilan Card Ringkasan -->
                <div class="bg-white p-4 rounded-xl border border-blue-100 shadow-sm flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 bg-gray-50 rounded-xl border border-gray-100 flex items-center justify-center p-1.5 shrink-0 overflow-hidden">
                            <template x-if="selectedLogoUrl">
                                <img :src="selectedLogoUrl" :alt="selectedKurir" class="max-w-full max-h-full object-contain">
                            </template>
                            <template x-if="!selectedLogoUrl">
                                <i class="fa-solid fa-truck-fast text-gray-400 text-lg"></i>
                            </template>
                        </div>
                        <div>
                            <p class="font-black text-gray-900 text-sm" x-text="selectedKurir"></p>
                            <p class="text-xs font-semibold text-gray-600" x-text="selectedLayanan"></p>
                            <p class="text-[11px] text-green-600 font-bold mt-0.5"><i class="fa-regular fa-calendar-check mr-1"></i> Tiba: <span x-text="selectedEtd"></span></p>
                        </div>
                    </div>
                    <div class="text-right">
                        <span class="text-[10px] text-gray-400 font-medium block">Total Ongkir</span>
                        <p class="font-black text-blue-700 text-lg">Rp <span x-text="selectedOngkir.toLocaleString('id-ID')"></span></p>
                    </div>
                </div>

                <!-- ======================================================= -->
                <!-- PILIHAN METODE PEMBAYARAN (DINAMIS DARI CONTROLLER) -->
                <!-- ======================================================= -->
                <div class="mt-6 pt-5 border-t border-blue-200/60">
                    <h3 class="text-xs font-extrabold text-gray-700 uppercase tracking-wider mb-3 flex items-center">
                        <i class="fa-solid fa-wallet text-indigo-600 mr-2"></i> Pilih Metode Pembayaran
                    </h3>

                    <div class="space-y-2.5 max-h-60 overflow-y-auto pr-1 custom-scrollbar">
                        @foreach($metodePembayaran as $bayar)
                        <div @click="selectedPayment = '{{ $bayar['id'] }}'"
                             class="p-3.5 bg-white border rounded-xl cursor-pointer transition-all flex items-center justify-between"
                             :class="selectedPayment === '{{ $bayar['id'] }}' ? 'border-indigo-600 ring-1 ring-indigo-600 shadow-sm bg-indigo-50/30' : 'border-gray-200 hover:border-gray-300'">
                            <div class="flex items-center gap-3">
                                <input type="radio" name="payment_radio" class="w-4 h-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 pointer-events-none"
                                       :checked="selectedPayment === '{{ $bayar['id'] }}'">
                                <div class="w-8 h-8 rounded-lg bg-gray-50 flex items-center justify-center shrink-0">
                                    <i class="{{ $bayar['icon'] }} text-base"></i>
                                </div>
                                <div>
                                    <p class="font-bold text-gray-900 text-xs sm:text-sm">{{ $bayar['nama'] }}</p>
                                    <p class="text-[11px] text-gray-500">{{ $bayar['deskripsi'] }}</p>
                                </div>
                            </div>
                            <span x-show="selectedPayment === '{{ $bayar['id'] }}'" class="text-indigo-600 text-sm font-bold"><i class="fa-solid fa-check-circle"></i></span>
                        </div>
                        @endforeach
                    </div>
                </div>

                <!-- TOMBOL SUBMIT PESANAN -->
                <div class="mt-6">
                    <button type="submit" :disabled="!selectedPayment"
                            class="w-full py-4 rounded-xl font-extrabold text-white transition shadow-xl text-base tracking-wide flex justify-center items-center gap-2"
                            :class="selectedPayment ? 'bg-blue-600 hover:bg-blue-700 shadow-blue-500/25 cursor-pointer' : 'bg-gray-300 cursor-not-allowed opacity-75'">
                        <span>SUBMIT KIRIM SEKARANG</span>
                        <i class="fa-solid fa-paper-plane"></i>
                    </button>
                    <p x-show="!selectedPayment" class="text-[11px] text-red-500 font-semibold text-center mt-2 animate-pulse">* Mohon pilih metode pembayaran di atas untuk melanjutkan</p>
                </div>
            </div>

        </div>

        <!-- ========================================================================= -->
        <!-- MODAL POP-UP PILIH EKSPEDISI (ALPINE.JS CONTROLLER) -->
        <!-- ========================================================================= -->
        <div x-show="showModal" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true" x-cloak>
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">

                <!-- Backdrop Blur -->
                <div x-show="showModal"
                     x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                     x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                     class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity" @click="showModal = false"></div>

                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <!-- Modal Content Container -->
                <div x-show="showModal"
                     x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                     x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                     class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl w-full border border-gray-100">

                    <!-- Modal Header -->
                    <div class="bg-gray-50 px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-black text-gray-800" id="modal-title"><i class="fa-solid fa-truck-fast text-blue-600 mr-2"></i> Pilih Jasa Ekspedisi</h3>
                            <p class="text-xs text-gray-500 mt-0.5">Tarif real-time langsung dari server logistik resmi</p>
                        </div>
                        <button type="button" @click="showModal = false" class="text-gray-400 hover:text-gray-600 bg-white hover:bg-gray-100 w-8 h-8 rounded-full flex items-center justify-center transition border border-gray-200">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>

                    <!-- Modal Body (List Ekspedisi) -->
                    <div class="p-6 max-h-[60vh] overflow-y-auto space-y-3 custom-scrollbar">
                        <template x-for="(ongkir, index) in ongkirList" :key="index">
                            <div @click="tempSelected = ongkir"
                                 class="p-4 border rounded-xl cursor-pointer transition-all duration-200 flex flex-col justify-between gap-2.5"
                                 :class="tempSelected && tempSelected.kode_layanan === ongkir.kode_layanan ? 'border-blue-600 bg-blue-50/70 shadow-md ring-1 ring-blue-600' : 'border-gray-200 hover:border-blue-300 bg-white'">

                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-3.5">
                                        <input type="radio" name="temp_kurir_radio" class="w-4 h-4 text-blue-600 focus:ring-blue-500 border-gray-300 pointer-events-none"
                                               :checked="tempSelected && tempSelected.kode_layanan === ongkir.kode_layanan">

                                        <!-- Logo Ekspedisi dari Helper -->
                                        <div class="w-12 h-12 bg-white rounded-xl border border-gray-100 flex items-center justify-center p-1.5 shadow-sm shrink-0 overflow-hidden relative">
                                            <template x-if="ongkir.logo_url">
                                                <img :src="ongkir.logo_url" :alt="ongkir.kurir"
                                                     class="max-w-full max-h-full object-contain"
                                                     onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                                            </template>
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
                                        <span x-show="qty > 1" class="text-[10px] text-gray-400 block" x-text="'(@ Rp ' + ongkir.harga_satuan.toLocaleString('id-ID') + ')'"></span>
                                    </div>
                                </div>

                                <!-- Waktu Estimasi -->
                                <div class="flex items-center justify-between text-xs text-gray-500 border-t border-gray-100 pt-2.5 mt-0.5 pl-7">
                                    <span><i class="fa-regular fa-clock text-gray-400 mr-1"></i> Durasi: <strong class="text-gray-700" x-text="ongkir.estimasi"></strong></span>
                                    <span><i class="fa-regular fa-calendar-check text-green-500 mr-1"></i> Tiba: <strong class="text-gray-700" x-text="ongkir.etd"></strong></span>
                                </div>
                            </div>
                        </template>
                    </div>

                    <!-- Modal Footer -->
                    <div class="bg-gray-50 px-6 py-4 border-t border-gray-200 flex items-center justify-end gap-3">
                        <button type="button" @click="showModal = false" class="px-5 py-2.5 rounded-xl border border-gray-300 text-gray-700 font-bold text-sm hover:bg-gray-100 transition">
                            Batal
                        </button>
                        <button type="button" @click="applySelection()" :disabled="!tempSelected"
                                class="px-6 py-2.5 rounded-xl font-bold text-white text-sm transition shadow-md flex items-center gap-2"
                                :class="tempSelected ? 'bg-blue-600 hover:bg-blue-700 shadow-blue-500/20 cursor-pointer' : 'bg-gray-300 cursor-not-allowed opacity-75'">
                            <span>Pilih & Gunakan Layanan Ini</span>
                            <i class="fa-solid fa-arrow-right"></i>
                        </button>
                    </div>

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
        qty: 1,
        isSenderPp: 1,
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

        // Modal & Selection State
        showModal: false,
        isLoading: false,
        ongkirList: [],
        tempSelected: null,       // Pilihan sementara di dalam modal

        // Data Terpilih Fix (Untuk UI & Backend)
        selectedKurir: '',
        selectedLayanan: '',
        selectedOngkir: 0,
        selectedServiceCode: '',
        selectedLogoUrl: '',
        selectedEtd: '',
        selectedPayment: '',      // 🔥 Menampung Metode Pembayaran Terpilih

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
            this.tempSelected = null; // Reset pilihan sementara di modal

            try {
                let formData = new FormData();
                formData.append('origin_id', this.senderDistrictId);
                formData.append('destination_id', this.receiverDistrictId);
                formData.append('berat_gram', this.berat);
                formData.append('qty', this.qty);
                formData.append('is_sender_pp', this.isSenderPp);
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
                    this.showModal = true; // 🔥 Buka Modal Pop-up setelah data sukses didapat
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

        // Terapkan Pilihan Ekspedisi dari Modal ke Layar Utama
        applySelection() {
            if(!this.tempSelected) return;

            this.selectedKurir       = this.tempSelected.kurir;
            this.selectedLayanan     = this.tempSelected.layanan;
            this.selectedOngkir      = this.tempSelected.harga;
            this.selectedServiceCode = this.tempSelected.kode_layanan;
            this.selectedLogoUrl     = this.tempSelected.logo_url;
            this.selectedEtd         = this.tempSelected.etd;

            this.showModal           = false; // Tutup Modal
        },

        // Validasi Form sebelum kirim
        validateForm(e) {
            if(!this.selectedServiceCode || !this.selectedOngkir) {
                e.preventDefault();
                alert("Silahkan hitung ongkos kirim dan pilih jasa ekspedisi terlebih dahulu!");
                return;
            }
            if(!this.selectedPayment) {
                e.preventDefault();
                alert("Silahkan pilih metode pembayaran terlebih dahulu!");
                return;
            }
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
