@extends('layouts.customer')

@section('content')
<div class="max-w-6xl mx-auto px-4 py-8 font-sans" x-data="orderForm">
    <div class="mb-8 border-b border-gray-200 pb-5">
        <h1 class="text-3xl font-extrabold text-black tracking-tight">Kirim Paket <span class="text-gray-500 font-medium">Sancaka Express</span></h1>
        <p class="text-gray-500 mt-2 text-sm">Isi detail pengiriman dengan cepat, akurat, dan dapatkan tarif terbaik dari server logistik.</p>
    </div>

    <!-- Alert Notifikasi Berhasil -->
    @if(session('success'))
        <div class="p-4 mb-6 text-sm text-gray-800 bg-gray-50 rounded-md border border-gray-300 flex items-center gap-3 shadow-sm">
            <i class="fa-solid fa-circle-check text-lg text-black"></i>
            <div><span class="font-bold">Sukses!</span> {{ session('success') }}</div>
        </div>
    @endif

    <!-- Alert Notifikasi Gagal (Merah Penting) -->
    @if(session('error'))
        <div class="p-4 mb-6 text-sm text-red-700 bg-red-50 rounded-md border border-red-200 flex items-center gap-3 shadow-sm">
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
            <div class="bg-white p-6 rounded-lg border border-gray-200 shadow-sm">
                <div class="flex items-center justify-between mb-5">
                    <h2 class="text-base font-bold text-black flex items-center"><i class="fa-solid fa-user-check text-gray-800 mr-2.5"></i> Data Pengirim</h2>
                    <button type="button" @click="saveContact('Pengirim')" class="text-xs font-medium text-black bg-white border border-gray-300 px-3 py-1.5 rounded hover:bg-gray-50 transition duration-200">Simpan Kontak</button>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div class="col-span-2 sm:col-span-1">
                        <label class="block text-xs font-medium text-gray-700 mb-1.5">NAMA LENGKAP</label>
                        <input type="text" id="pengirim_nama" name="pengirim_nama" value="{{ old('pengirim_nama') }}" required class="uppercase w-full border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-1 focus:ring-black focus:border-black px-4 py-2.5 bg-white transition duration-200 placeholder-gray-400">
                    </div>
                    <div class="col-span-2 sm:col-span-1">
                        <label class="block text-xs font-medium text-gray-700 mb-1.5">NOMOR HP / WA</label>
                        <input type="text" id="pengirim_hp" name="pengirim_hp" value="{{ old('pengirim_hp') }}" required class="uppercase w-full border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-1 focus:ring-black focus:border-black px-4 py-2.5 bg-white transition duration-200 placeholder-gray-400">
                    </div>

                    <!-- Autocomplete Alamat Pengirim -->
                    <div class="col-span-2 relative" @click.away="showSenderDropdown = false">
                        <label class="block text-xs font-medium text-gray-700 mb-1.5">KECAMATAN / KABUPATEN PENGIRIM</label>
                        <div class="relative">
                            <input type="text" x-model="senderQuery" @input.debounce.400ms="searchAddress('sender')" @focus="showSenderDropdown = true" placeholder="Ketik minimal 3 karakter wilayah pengirim..." class="uppercase w-full border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-1 focus:ring-black focus:border-black pl-4 pr-10 py-2.5 bg-white transition duration-200 placeholder-gray-400" autocomplete="off">
                            <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none text-gray-400 text-xs">
                                <i class="fa-solid fa-magnifying-glass" x-show="!isSearchingSender"></i>
                                <i class="fa-solid fa-spinner fa-spin text-black" x-show="isSearchingSender" x-cloak></i>
                            </div>
                        </div>
                        <input type="hidden" name="pengirim_district_id" x-model="senderDistrictId">

                        <div x-show="showSenderDropdown" x-transition class="absolute z-[110] w-full mt-1 bg-white rounded-md shadow-lg border border-gray-200 max-h-48 overflow-y-auto" x-cloak>
                            <template x-if="senderResults.length > 0">
                                <div>
                                    <template x-for="res in senderResults">
                                        <div @click="selectAddress('sender', res)" class="px-4 py-3 hover:bg-gray-50 cursor-pointer border-b border-gray-100 text-sm transition duration-150">
                                            <p class="font-medium text-black" x-text="res.district_name + ', ' + res.regency_name"></p>
                                            <p class="text-xs text-gray-500 mt-0.5" x-text="res.province_name + ' (Kodepos: ' + res.zip + ')'"></p>
                                        </div>
                                    </template>
                                </div>
                            </template>
                            <template x-if="senderResults.length === 0 && !isSearchingSender && senderQuery.length >= 3">
                                <div class="px-4 py-3 text-sm text-red-600 italic text-center font-medium bg-red-50">
                                    <i class="fa-solid fa-circle-exclamation mr-1"></i> Wilayah tidak ditemukan di database.
                                </div>
                            </template>
                        </div>
                    </div>

                    <div class="col-span-2">
                        <label class="block text-xs font-medium text-gray-700 mb-1.5">
                            ALAMAT JALAN PENGIRIM <span class="text-red-500">*</span>
                        </label>
                        <textarea id="pengirim_alamat" name="pengirim_alamat" rows="2" required minlength="15" placeholder="Contoh: JL RONGGOWARSITO NO 15 RT 01 RW 02" class="uppercase w-full border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-1 focus:ring-black focus:border-black px-4 py-2.5 bg-white transition duration-200 placeholder-gray-400">{{ old('pengirim_alamat') }}</textarea>

                        <div class="mt-2 p-2.5 bg-red-50 border border-red-200 rounded text-red-700">
                            <p class="text-[11px] font-bold flex items-start gap-1.5 leading-tight">
                                <i class="fa-solid fa-triangle-exclamation mt-0.5"></i>
                                <span>PENTING: from.address hanya diisi alamat jalan saja tanpa kecamatan, kabupaten, provinsi dan kode pos.</span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Card Data Penerima -->
            <div class="bg-white p-6 rounded-lg border border-gray-200 shadow-sm">
                <div class="flex items-center justify-between mb-5">
                    <h2 class="text-base font-bold text-black flex items-center"><i class="fa-solid fa-location-dot text-gray-800 mr-2.5"></i> Data Penerima</h2>
                    <button type="button" @click="saveContact('Penerima')" class="text-xs font-medium text-black bg-white border border-gray-300 px-3 py-1.5 rounded hover:bg-gray-50 transition duration-200">Simpan Kontak</button>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div class="col-span-2 sm:col-span-1">
                        <label class="block text-xs font-medium text-gray-700 mb-1.5">NAMA LENGKAP</label>
                        <input type="text" id="penerima_nama" name="penerima_nama" value="{{ old('penerima_nama') }}" required class="uppercase w-full border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-1 focus:ring-black focus:border-black px-4 py-2.5 bg-white transition duration-200 placeholder-gray-400">
                    </div>
                    <div class="col-span-2 sm:col-span-1">
                        <label class="block text-xs font-medium text-gray-700 mb-1.5">NOMOR HP / WA</label>
                        <input type="text" id="penerima_hp" name="penerima_hp" value="{{ old('penerima_hp') }}" required class="uppercase w-full border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-1 focus:ring-black focus:border-black px-4 py-2.5 bg-white transition duration-200 placeholder-gray-400">
                    </div>

                    <!-- Autocomplete Alamat Penerima -->
                    <div class="col-span-2 relative" @click.away="showReceiverDropdown = false">
                        <label class="block text-xs font-medium text-gray-700 mb-1.5">KECAMATAN / KABUPATEN PENERIMA</label>
                        <div class="relative">
                            <input type="text" x-model="receiverQuery" @input.debounce.400ms="searchAddress('receiver')" @focus="showReceiverDropdown = true" placeholder="Ketik minimal 3 karakter wilayah penerima..." class="uppercase w-full border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-1 focus:ring-black focus:border-black pl-4 pr-10 py-2.5 bg-white transition duration-200 placeholder-gray-400" autocomplete="off">
                            <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none text-gray-400 text-xs">
                                <i class="fa-solid fa-magnifying-glass" x-show="!isSearchingReceiver"></i>
                                <i class="fa-solid fa-spinner fa-spin text-black" x-show="isSearchingReceiver" x-cloak></i>
                            </div>
                        </div>
                        <input type="hidden" name="penerima_district_id" x-model="receiverDistrictId">

                        <div x-show="showReceiverDropdown" x-transition class="absolute z-[110] w-full mt-1 bg-white rounded-md shadow-lg border border-gray-200 max-h-48 overflow-y-auto" x-cloak>
                            <template x-if="receiverResults.length > 0">
                                <div>
                                    <template x-for="res in receiverResults">
                                        <div @click="selectAddress('receiver', res)" class="px-4 py-3 hover:bg-gray-50 cursor-pointer border-b border-gray-100 text-sm transition duration-150">
                                            <p class="font-medium text-black" x-text="res.district_name + ', ' + res.regency_name"></p>
                                            <p class="text-xs text-gray-500 mt-0.5" x-text="res.province_name + ' (Kodepos: ' + res.zip + ')'"></p>
                                        </div>
                                    </template>
                                </div>
                            </template>
                            <template x-if="receiverResults.length === 0 && !isSearchingReceiver && receiverQuery.length >= 3">
                                <div class="px-4 py-3 text-sm text-red-600 italic text-center font-medium bg-red-50">
                                    <i class="fa-solid fa-circle-exclamation mr-1"></i> Wilayah tidak ditemukan di database.
                                </div>
                            </template>
                        </div>
                    </div>

                    <div class="col-span-2">
                        <label class="block text-xs font-medium text-gray-700 mb-1.5">
                            ALAMAT JALAN PENERIMA <span class="text-red-500">*</span>
                        </label>
                        <textarea id="penerima_alamat" name="penerima_alamat" rows="2" required minlength="15" placeholder="Contoh: PERUM GRAHA KEBRAON REGENCY 2 BLOK A NO 3 RT 04 RW 05" class="uppercase w-full border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-1 focus:ring-black focus:border-black px-4 py-2.5 bg-white transition duration-200 placeholder-gray-400">{{ old('penerima_alamat') }}</textarea>

                        <div class="mt-2 p-2.5 bg-red-50 border border-red-200 rounded text-red-700">
                            <p class="text-[11px] font-bold flex items-start gap-1.5 leading-tight">
                                <i class="fa-solid fa-triangle-exclamation mt-0.5"></i>
                                <span>PENTING: to.address hanya diisi alamat jalan saja tanpa kecamatan, kabupaten, provinsi dan kode pos.</span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- SISI KANAN: DETAIL BARANG & ACTION -->
        <!-- ========================================== -->
        <div class="lg:col-span-5 space-y-6">
            <div class="bg-white p-6 rounded-lg border border-gray-200 shadow-sm">
                <h2 class="text-base font-bold text-black mb-5 flex items-center"><i class="fa-solid fa-box-open text-gray-800 mr-2.5"></i> Detail Paket</h2>

                <div class="space-y-5">

                    <!-- DINAMIS: Tipe Pesanan -->
                    <div class="p-4 border border-gray-200 rounded-md">
                        <label class="block text-xs font-medium text-gray-700 mb-2.5">TIPE PESANAN</label>
                        <div class="grid grid-cols-3 gap-2">
                            <label class="flex flex-col items-center justify-center p-2.5 border rounded cursor-pointer transition-all duration-200"
                                   :class="tipePesanan === 'reguler' ? 'border-black bg-black text-white font-medium shadow-sm' : 'border-gray-300 hover:border-gray-400 text-gray-600 bg-white'">
                                <input type="radio" name="tipe_pesanan" value="reguler" x-model="tipePesanan" class="hidden">
                                <span class="text-xs tracking-wide">REGULER</span>
                            </label>
                            <label class="flex flex-col items-center justify-center p-2.5 border rounded cursor-pointer transition-all duration-200"
                                   :class="tipePesanan === 'cod' ? 'border-black bg-black text-white font-medium shadow-sm' : 'border-gray-300 hover:border-gray-400 text-gray-600 bg-white'">
                                <input type="radio" name="tipe_pesanan" value="cod" x-model="tipePesanan" class="hidden">
                                <span class="text-xs tracking-wide">COD</span>
                            </label>
                            <label class="flex flex-col items-center justify-center p-2.5 border rounded cursor-pointer transition-all duration-200"
                                   :class="tipePesanan === 'cashless' ? 'border-black bg-black text-white font-medium shadow-sm' : 'border-gray-300 hover:border-gray-400 text-gray-600 bg-white'">
                                <input type="radio" name="tipe_pesanan" value="cashless" x-model="tipePesanan" class="hidden">
                                <span class="text-xs tracking-wide">CASHLESS</span>
                            </label>
                        </div>

                        <!-- Input Ekstra untuk Cashless -->
                        <div x-show="tipePesanan === 'cashless'" x-transition class="mt-4" x-cloak>
                            <label class="block text-xs font-medium text-gray-700 mb-1.5">NOMOR RESI MARKETPLACE (AWB) <span class="text-red-500">*</span></label>
                            <input type="text" name="resi_cashless" x-model="resiCashless" placeholder="CONTOH: JP1234567890" class="uppercase w-full border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-1 focus:ring-black focus:border-black px-4 py-2.5 bg-white">
                        </div>

                        <!-- Info & Pilihan Ekstra untuk COD -->
                        <div x-show="tipePesanan === 'cod'" x-transition class="mt-4 pt-4 border-t border-gray-200" x-cloak>
                            <label class="block text-xs font-medium text-gray-700 mb-2.5">PILIH JENIS COD <span class="text-red-500">*</span></label>
                            <div class="flex flex-col gap-3">
                                <label class="flex items-center gap-2.5 cursor-pointer">
                                    <input type="radio" name="jenis_cod_radio" value="cod_barang" x-model="jenisCod" class="text-black focus:ring-black border-gray-300 w-4 h-4">
                                    <span class="text-xs font-medium text-gray-800">COD Barang + Ongkir (Tagihan gabungan)</span>
                                </label>
                                <label class="flex items-center gap-2.5 cursor-pointer">
                                    <input type="radio" name="jenis_cod_radio" value="cod_ongkir" x-model="jenisCod" class="text-black focus:ring-black border-gray-300 w-4 h-4">
                                    <span class="text-xs font-medium text-gray-800">COD Ongkir Saja (Tagihan hanya biaya kurir)</span>
                                </label>
                            </div>
                            <p class="text-[11px] text-gray-500 font-medium leading-tight mt-3 flex items-start gap-1.5">
                                <i class="fa-solid fa-circle-info mt-0.5"></i>
                                <span x-show="jenisCod === 'cod_barang'">Masukkan Total Tagihan di kolom "Nilai Harga Barang" di bawah.</span>
                                <span x-show="jenisCod === 'cod_ongkir'">Hanya nominal Ongkir yang ditagihkan. Nilai barang di bawah hanya dipakai sebagai acuan klaim asuransi.</span>
                            </p>
                        </div>
                    </div>

                    <!-- Kategori Barang -->
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1.5">KATEGORI BARANG</label>
                        <select name="kategori_barang" x-model="kategoriBarang" required class="uppercase w-full border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-1 focus:ring-black focus:border-black px-4 py-2.5 bg-white">
                            <option value="" disabled selected>PILIH...</option>
                            <option value="ELK001">PERALATAN ELEKTRONIK & GADGET</option>
                            <option value="PAK001">PAKAIAN / BAJU / KAIN</option>
                            <option value="PCH001">PECAH BELAH</option>
                            <option value="DOC001">DOKUMEN / BERKAS / BUKU</option>
                            <option value="RTG001">PERALATAN RUMAH TANGGA</option>
                            <option value="AKS001">AKSESORIS</option>
                            <option value="OTH001">LAIN-LAIN</option>
                            <option value="DHS001">DOKUMEN BERHARGA</option>
                            <option value="KSM001">PERALATAN KESEHATAN / KECANTIKAN / KOSMETIK</option>
                            <option value="OLH001">PERALATAN OLAHRAGA & HIBURAN</option>
                            <option value="OTM001">PERLENGKAPAN MOBIL & MOTOR</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1.5">DESKRIPSI ISI PAKET</label>
                        <input type="text" name="deskripsi_barang" value="{{ old('deskripsi_barang') }}" placeholder="CONTOH: SEPATU SNEAKERS HITAM UKURAN 42" required class="uppercase w-full border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-1 focus:ring-black focus:border-black px-4 py-2.5 bg-white placeholder-gray-400">
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1.5">BERAT TOTAL (GRAM)</label>
                            <input type="number" name="berat_gram" x-model="berat" min="1" required class="uppercase w-full border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-1 focus:ring-black focus:border-black px-4 py-2.5 bg-white">
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1.5">JUMLAH KOLI / PCS</label>
                            <input type="number" name="qty" x-model="qty" min="1" required class="uppercase w-full border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-1 focus:ring-black focus:border-black px-4 py-2.5 bg-white text-center">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4 items-center">
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1.5">ASURANSI PENGIRIMAN?</label>
                            <div class="flex items-center h-[42px] px-4 border border-gray-300 rounded-md bg-white">
                                <input type="checkbox" name="asuransi" x-model="asuransi" class="w-4 h-4 text-black border-gray-300 rounded focus:ring-black cursor-pointer">
                                <span class="ml-2.5 text-xs text-gray-700 font-medium cursor-pointer select-none" @click="asuransi = !asuransi">Ya, Amankan</span>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1.5">METODE SERAH TERIMA</label>
                            <select name="is_sender_pp" x-model="isSenderPp" class="uppercase w-full h-[42px] border border-gray-300 rounded-md text-xs focus:outline-none focus:ring-1 focus:ring-black focus:border-black px-3 bg-white text-gray-800">
                                <option value="1">KURIR JEMPUT (PICKUP)</option>
                                <option value="0">ANTAR KE CABANG (DROPOFF)</option>
                            </select>
                        </div>
                    </div>

                    <div x-show="asuransi || tipePesanan === 'cod'" x-transition.duration.300ms class="p-4 rounded-md border border-gray-200 bg-gray-50" x-cloak>
                        <label class="block text-xs font-medium text-black mb-1.5">NILAI HARGA BARANG / TAGIHAN COD (RP) <span class="text-red-500">*</span></label>
                        <input type="number" name="nilai_barang" x-model="nilaiBarang" placeholder="MASUKKAN NOMINAL RUPIAH..." class="uppercase w-full border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-1 focus:ring-black focus:border-black px-4 py-2 bg-white" :required="asuransi || tipePesanan === 'cod'">
                        <p class="text-[10px] mt-2 text-gray-500" x-text="tipePesanan === 'cod' ? '* Nominal ini adalah total Rupiah yang akan ditagihkan kurir kepada penerima paket.' : '* Nominal ini digunakan kurir sebagai acuan klaim asuransi jika barang hilang.'"></p>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1.5">DIMENSI / VOLUME PAKET (CM - OPSIONAL)</label>
                        <div class="flex gap-2 items-center">
                            <input type="number" name="panjang_cm" x-model="panjang" placeholder="P" min="1" class="uppercase w-1/3 border border-gray-300 rounded-md text-sm text-center bg-white py-2.5 focus:outline-none focus:ring-1 focus:ring-black focus:border-black">
                            <span class="text-gray-400 font-medium">×</span>
                            <input type="number" name="lebar_cm" x-model="lebar" placeholder="L" min="1" class="uppercase w-1/3 border border-gray-300 rounded-md text-sm text-center bg-white py-2.5 focus:outline-none focus:ring-1 focus:ring-black focus:border-black">
                            <span class="text-gray-400 font-medium">×</span>
                            <input type="number" name="tinggi_cm" x-model="tinggi" placeholder="T" min="1" class="uppercase w-1/3 border border-gray-300 rounded-md text-sm text-center bg-white py-2.5 focus:outline-none focus:ring-1 focus:ring-black focus:border-black">
                        </div>
                    </div>
                </div>

                <div class="mt-8">
                    <button type="button" @click="cekOngkir()" :disabled="isLoading" class="w-full py-3.5 rounded-md font-medium text-white bg-black hover:bg-gray-800 transition duration-200 flex justify-center items-center text-sm disabled:opacity-50 disabled:cursor-not-allowed">
                        <i class="fa-solid fa-calculator mr-2" x-show="!isLoading"></i>
                        <i class="fa-solid fa-spinner fa-spin mr-2" x-show="isLoading" x-cloak></i>
                        <span x-text="isLoading ? 'MENGHITUNG ONGKIR...' : (selectedOngkir > 0 ? 'GANTI / HITUNG ULANG EKSPEDISI' : 'HITUNG & PILIH EKSPEDISI')" class="tracking-wide"></span>
                    </button>
                </div>
            </div>

            <!-- ======================================================= -->
            <!-- RINGKASAN EKSPEDISI TERPILIH -->
            <!-- ======================================================= -->
            <div x-show="selectedOngkir > 0" x-transition.duration.300ms class="bg-white p-6 rounded-lg border border-gray-200 shadow-sm relative" x-cloak>
                <div class="flex items-center justify-between mb-4 border-b border-gray-100 pb-3">
                    <span class="text-[10px] font-bold text-black bg-gray-100 px-2 py-1 rounded-sm uppercase tracking-widest flex items-center">
                        Ekspedisi Terpilih
                    </span>
                    <button type="button" @click="showModal = true" class="text-xs font-medium text-gray-500 hover:text-black underline">Ganti Kurir</button>
                </div>

                <!-- Input Hidden yang Wajib Dikirim ke Backend -->
                <input type="hidden" name="kurir_terpilih" x-model="selectedKurir">
                <input type="hidden" name="layanan_terpilih" x-model="selectedLayanan">
                <input type="hidden" name="ongkir_terpilih" x-model="selectedOngkir">
                <input type="hidden" name="service_code_terpilih" x-model="selectedServiceCode">
                <input type="hidden" name="metode_pembayaran" x-bind:value="tipePesanan === 'cod' ? jenisCod : selectedPayment">

                <!-- Tampilan Card Ringkasan -->
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 bg-white rounded border border-gray-200 flex items-center justify-center p-1.5 shrink-0">
                            <!-- HAPUS GRAYSCALE DARI SINI -->
                            <template x-if="selectedLogoUrl">
                                <img :src="selectedLogoUrl" :alt="selectedKurir" class="max-w-full max-h-full object-contain">
                            </template>
                            <template x-if="!selectedLogoUrl">
                                <i class="fa-solid fa-truck-fast text-gray-400 text-lg"></i>
                            </template>
                        </div>
                        <div>
                            <p class="font-bold text-black text-sm uppercase tracking-wide" x-text="selectedKurir"></p>
                            <p class="text-xs text-gray-500 uppercase mt-0.5" x-text="selectedLayanan"></p>
                            <p class="text-[11px] text-gray-600 mt-1"><i class="fa-regular fa-calendar-check mr-1.5"></i> Tiba: <span x-text="selectedEtd"></span></p>
                        </div>
                    </div>
                    <div class="text-right">
                        <span class="text-[10px] text-gray-400 uppercase tracking-widest block mb-0.5">Total Ongkir</span>
                        <p class="font-bold text-black text-lg">Rp <span x-text="selectedOngkir.toLocaleString('id-ID')"></span></p>
                    </div>
                </div>

                <!-- ========================================================================================= -->
                <!-- PILIHAN METODE PEMBAYARAN -->
                <!-- ========================================================================================= -->
                <div x-show="tipePesanan !== 'cod'" x-transition class="mt-6 pt-5 border-t border-gray-200" x-cloak>
                    <h3 class="text-xs font-medium text-black uppercase tracking-widest mb-4">
                        Pilih Metode Pembayaran
                    </h3>

                    <!-- TOMBOL PEMICU MODAL PEMBAYARAN -->
                    <button type="button" @click="showPaymentModal = true" class="flex items-center justify-between w-full border p-4 rounded-lg cursor-pointer hover:bg-gray-50 focus:outline-none transition-all" :class="selectedPayment ? 'border-black ring-1 ring-black shadow-sm' : 'border-gray-300'">
                        <div class="flex items-center gap-4">
                            <div class="w-10 h-10 rounded bg-white flex items-center justify-center shrink-0 p-1 border border-gray-100 shadow-sm">
                                <!-- Tampil jika belum ada yang dipilih -->
                                <template x-if="!selectedPaymentIcon">
                                    <i class="fa-solid fa-wallet text-gray-400 text-xl"></i>
                                </template>
                                <!-- Tampil jika sudah memilih -->
                                <template x-if="selectedPaymentIcon">
                                    <div class="w-full h-full flex items-center justify-center">
                                        <template x-if="selectedPaymentIcon.includes('http')">
                                            <img :src="selectedPaymentIcon" class="max-w-full max-h-full object-contain">
                                        </template>
                                        <template x-if="!selectedPaymentIcon.includes('http')">
                                            <i :class="selectedPaymentIcon + ' text-xl'"></i>
                                        </template>
                                    </div>
                                </template>
                            </div>
                            <div class="flex flex-col text-left">
                                <span class="text-sm font-bold text-gray-900 uppercase" x-text="selectedPaymentName || 'PILIH METODE PEMBAYARAN'"></span>
                                <span class="text-[11px] text-gray-500 uppercase mt-0.5" x-text="selectedPaymentName ? 'Klik untuk mengganti metode' : 'Pilih metode untuk melanjutkan'"></span>
                            </div>
                        </div>
                        <i class="fa-solid fa-chevron-right text-gray-400"></i>
                    </button>
                    <!-- Selesai Tombol Pemicu -->

                    <!-- KOTAK INFORMASI -->
                    <div x-show="selectedPayment === 'potong_saldo'" x-transition class="mt-4 p-4 border border-gray-200 rounded-md text-xs text-black bg-gray-50" x-cloak>
                        <div class="flex items-center justify-between mb-2 pb-2 border-b border-gray-200">
                            <span class="font-medium text-gray-600">SALDO WALLET SAAT INI:</span>
                            <span class="font-bold text-black text-sm">Rp {{ number_format(auth()->user()->saldo ?? 0, 0, ',', '.') }}</span>
                        </div>
                        <template x-if="selectedOngkir > {{ auth()->user()->saldo ?? 0 }}">
                            <div class="mt-2 text-red-600 font-medium flex items-start gap-2">
                                <i class="fa-solid fa-triangle-exclamation mt-0.5"></i>
                                <span>Saldo tidak mencukupi untuk bayar ongkir ini! Silahkan isi ulang atau pilih metode lain.</span>
                            </div>
                        </template>
                        <template x-if="selectedOngkir <= {{ auth()->user()->saldo ?? 0 }}">
                            <p class="text-gray-600 mt-1">Saldo mencukupi. Saldo akan dipotong otomatis setelah menekan submit.</p>
                        </template>
                    </div>

                    <div x-show="selectedPayment === 'dana_binding'" x-transition class="mt-4 p-4 border border-gray-200 rounded-md text-xs bg-gray-50" x-cloak>
                        @if(!empty(auth()->user()->dana_token))
                            <div class="flex items-start gap-2 text-black">
                                <i class="fa-solid fa-circle-check text-gray-800 text-sm mt-0.5"></i>
                                <span>Akun DANA terhubung. Pembayaran ongkir akan dipotong instan.</span>
                            </div>
                        @else
                            <div class="text-red-600 font-medium flex items-start gap-2">
                                <i class="fa-solid fa-triangle-exclamation mt-0.5"></i>
                                <div>
                                    <p>AKUN DANA BELUM DIIKAT (BIND)!</p>
                                    <span class="text-[11px] font-normal text-red-500 block mt-1">Silahkan hubungkan akun di pengaturan profil, atau pilih metode "DANA Payment Gateway".</span>
                                </div>
                            </div>
                        @endif
                    </div>

                    <div x-show="selectedPayment && selectedPayment !== 'potong_saldo' && selectedPayment !== 'dana_binding'" x-transition class="mt-4 p-4 border border-gray-200 rounded-md text-xs bg-gray-50 flex items-start gap-3" x-cloak>
                        <i class="fa-solid fa-shield-halved text-gray-500 text-base mt-0.5"></i>
                        <div>
                            <span class="font-bold block text-black mb-1">PEMBAYARAN AMAN</span>
                            <span class="text-gray-500 leading-relaxed">Anda akan diarahkan ke halaman pembayaran resmi. Resi AWB logistik akan terbit otomatis setelah pembayaran lunas 24/7.</span>
                        </div>
                    </div>
                </div>

                <!-- TOMBOL SUBMIT PESANAN -->
                <div class="mt-6 pt-5 border-t border-gray-200">
                    <button type="submit"
                            :disabled="(tipePesanan !== 'cod' && (!selectedPayment || (selectedPayment === 'potong_saldo' && selectedOngkir > {{ auth()->user()->saldo ?? 0 }}) @if(empty(auth()->user()->dana_token)) || selectedPayment === 'dana_binding' @endif))"
                            class="w-full py-4 rounded-md font-bold text-white transition-all text-sm tracking-widest flex justify-center items-center gap-3 uppercase"
                            :class="(tipePesanan === 'cod' || (selectedPayment && !(selectedPayment === 'potong_saldo' && selectedOngkir > {{ auth()->user()->saldo ?? 0 }}) @if(empty(auth()->user()->dana_token)) && selectedPayment !== 'dana_binding' @endif)) ? 'bg-black hover:bg-gray-800 cursor-pointer shadow-md' : 'bg-gray-200 text-gray-400 cursor-not-allowed'">
                        <span>Submit Kirim Sekarang</span>
                        <i class="fa-solid fa-arrow-right"></i>
                    </button>
                    <p x-show="tipePesanan !== 'cod' && !selectedPayment" class="text-[10px] text-gray-500 font-medium text-center mt-3 uppercase tracking-widest">* Mohon pilih metode pembayaran</p>
                </div>
            </div>

        </div>

        <!-- ========================================================================= -->
        <!-- MODAL POP-UP PILIH EKSPEDISI -->
        <!-- ========================================================================= -->
        <div x-show="showModal" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true" x-cloak>
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">

                <!-- Backdrop Blur -->
                <div x-show="showModal"
                     x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                     x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                     class="fixed inset-0 bg-black/50 backdrop-blur-sm transition-opacity" @click="showModal = false"></div>

                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <!-- Modal Content Container -->
                <div x-show="showModal"
                     x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                     x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                     class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl w-full border border-gray-200">

                    <!-- Modal Header -->
                    <div class="bg-white px-6 py-5 border-b border-gray-200 flex items-center justify-between">
                        <div>
                            <h3 class="text-base font-bold text-black uppercase tracking-wide" id="modal-title">Pilih Ekspedisi</h3>
                            <p class="text-xs text-gray-500 mt-1">Tarif real-time dari server logistik resmi</p>
                        </div>
                        <button type="button" @click="showModal = false" class="text-gray-400 hover:text-black transition">
                            <i class="fa-solid fa-xmark text-lg"></i>
                        </button>
                    </div>

                    <!-- Modal Body (List Ekspedisi) -->
                    <div class="p-6 max-h-[60vh] overflow-y-auto space-y-3 custom-scrollbar bg-gray-50">
                        <template x-for="(ongkir, index) in ongkirList" :key="index">
                            <div @click="tempSelected = ongkir"
                                 class="p-4 border rounded-md cursor-pointer transition-all duration-200 flex flex-col justify-between gap-3 bg-white"
                                 :class="tempSelected && tempSelected.kode_layanan === ongkir.kode_layanan ? 'border-black ring-1 ring-black shadow-sm' : 'border-gray-200 hover:border-gray-300'">

                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-4">
                                        <input type="radio" name="temp_kurir_radio" class="w-4 h-4 text-black focus:ring-black border-gray-300 pointer-events-none"
                                               :checked="tempSelected && tempSelected.kode_layanan === ongkir.kode_layanan">

                                        <!-- HAPUS GRAYSCALE DARI MODAL LIST EKSPEDISI JUGA -->
                                        <div class="w-12 h-12 rounded border border-gray-100 flex items-center justify-center p-1.5 shrink-0 bg-white shadow-sm">
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
                                                <p class="font-bold text-black text-sm uppercase" x-text="ongkir.kurir"></p>
                                                <span x-show="ongkir.is_pickup" class="text-[9px] border border-black text-black font-bold px-1.5 py-0.5 rounded-sm uppercase tracking-wider">Pickup</span>
                                            </div>
                                            <p class="text-xs text-gray-500 uppercase mt-1" x-text="ongkir.layanan"></p>
                                        </div>
                                    </div>

                                    <div class="text-right">
                                        <p class="font-bold text-black text-base">Rp <span x-text="ongkir.harga.toLocaleString('id-ID')"></span></p>
                                        <span x-show="qty > 1" class="text-[10px] text-gray-400 block mt-0.5" x-text="'(@ Rp ' + ongkir.harga_satuan.toLocaleString('id-ID') + ')'"></span>
                                    </div>
                                </div>

                                <!-- Waktu Estimasi -->
                                <div class="flex items-center justify-between text-xs text-gray-500 border-t border-gray-100 pt-3 mt-1 pl-8">
                                    <span>Durasi: <strong class="text-black font-medium" x-text="ongkir.estimasi"></strong></span>
                                    <span>Tiba: <strong class="text-black font-medium" x-text="ongkir.etd"></strong></span>
                                </div>
                            </div>
                        </template>
                    </div>

                    <!-- Modal Footer -->
                    <div class="bg-white px-6 py-5 border-t border-gray-200 flex items-center justify-end gap-4">
                        <button type="button" @click="showModal = false" class="px-5 py-2.5 rounded-md text-black font-medium text-sm hover:bg-gray-100 transition border border-transparent">
                            BATAL
                        </button>
                        <button type="button" @click="applySelection()" :disabled="!tempSelected"
                                class="px-6 py-2.5 rounded-md font-medium text-white text-sm transition tracking-widest uppercase flex items-center gap-2"
                                :class="tempSelected ? 'bg-black hover:bg-gray-800 cursor-pointer shadow-sm' : 'bg-gray-200 text-gray-400 cursor-not-allowed'">
                            <span>Gunakan Layanan</span>
                        </button>
                    </div>

                </div>
            </div>
        </div>

    </form>


        <!-- ========================================================================= -->
        <!-- MODAL POP-UP PILIH PEMBAYARAN -->
        <!-- ========================================================================= -->
        <div x-show="showPaymentModal"
             style="display: none;"
             class="fixed inset-0 z-[120] flex items-center justify-center bg-black/60 backdrop-blur-sm p-4 transition-opacity"
             x-cloak>

            <!-- Modal Content Container -->
            <div x-show="showPaymentModal"
                 @click.away="showPaymentModal = false"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95"
                 class="bg-white rounded-xl shadow-2xl w-full max-w-5xl transform transition-all flex flex-col max-h-[90vh] relative z-10 mx-auto">

                <!-- Modal Header -->
                <div class="bg-white px-6 py-5 border-b border-gray-200 flex items-center justify-between rounded-t-xl shrink-0">
                    <div>
                        <h3 class="text-base font-bold text-black uppercase tracking-wide">Pilih Metode Pembayaran</h3>
                    </div>
                    <button type="button" @click="showPaymentModal = false" class="text-gray-400 hover:text-red-600 bg-gray-100 hover:bg-red-50 p-2 rounded-full transition-colors">
                        <i class="fa-solid fa-xmark text-lg"></i>
                    </button>
                </div>

                <!-- Modal Body (Grid Pembayaran) -->
                <div class="p-2 overflow-y-auto custom-scrollbar flex-1 bg-gray-50 rounded-b-xl">
                    <ul class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3 p-4">
                        @foreach($metodePembayaran as $bayar)
                            @php
                                $imgUrl = '';
                                if(str_contains(strtolower($bayar['id']), 'dana')) $imgUrl = 'https://tokosancaka.com/public/assets/dana.png';
                                elseif(str_contains(strtolower($bayar['id']), 'doku')) $imgUrl = 'https://tokosancaka.com/public/assets/doku.png';
                                elseif(str_contains(strtolower($bayar['id']), 'tripay')) $imgUrl = 'https://tokosancaka.com/public/assets/tripay.png';

                                $jsIconParam = $imgUrl ? $imgUrl : $bayar['icon'];
                            @endphp

                            <li @click="selectPayment('{{ $bayar['id'] }}', '{{ $bayar['nama'] }}', '{{ $jsIconParam }}')"
                                class="col-span-1 cursor-pointer flex items-center p-3 border rounded-lg transition-all duration-200 bg-white"
                                :class="selectedPayment === '{{ $bayar['id'] }}' ? 'border-black ring-1 ring-black shadow-sm' : 'border-gray-200 hover:border-gray-400 hover:bg-red-50'">

                                <div class="w-12 h-12 rounded bg-white flex items-center justify-center shrink-0 p-1.5 border border-gray-100 shadow-sm mr-4">
                                    @if($imgUrl)
                                        <img src="{{ $imgUrl }}" alt="{{ $bayar['nama'] }}" class="w-full h-full object-contain">
                                    @else
                                        <i class="{{ $bayar['icon'] }} text-2xl"></i>
                                    @endif
                                </div>

                                <div class="flex flex-col">
                                    <span class="text-sm font-bold text-gray-900 uppercase">{{ $bayar['nama'] }}</span>
                                    <span class="text-[11px] text-gray-500 uppercase mt-0.5">{{ $bayar['deskripsi'] }}</span>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>

            </div>
        </div>
        <!-- ========================================================================= -->

</div> {{-- Penutup From X DATA --}}

<!-- ========================================== -->
<!-- SCRIPTS ENGINE LOGIC (ALPINE.JS V3) -->
<!-- ========================================== -->
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('orderForm', () => ({
        kategoriBarang: '',
        tipePesanan: 'reguler',
        resiCashless: '',
        nilaiBarang: '',
        berat: 1000,
        qty: 1,
        isSenderPp: 1,
        asuransi: false,
        panjang: '',
        lebar: '',
        tinggi: '',
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
        selectedPayment: '',
        showPaymentModal: false,
        selectedPaymentName: '',
        selectedPaymentIcon: '',

        selectPayment(id, name, icon) {
            this.selectedPayment = id;
            this.selectedPaymentName = name;
            this.selectedPaymentIcon = icon;
            this.showPaymentModal = false; // Otomatis tutup modal setelah memilih
        },

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
                this.tempSelected = null;

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
                    formData.append('kategori_barang', this.kategoriBarang);

                    // TAMBAHKAN KODE INI UNTUK UPDATE PICKUP POINT DINAMIS
                    let namaVal = document.getElementById('pengirim_nama') ? document.getElementById('pengirim_nama').value : 'Pengirim';
                    let hpVal = document.getElementById('pengirim_hp') ? document.getElementById('pengirim_hp').value : '0800000000';
                    let alamatVal = document.getElementById('pengirim_alamat') ? document.getElementById('pengirim_alamat').value : 'Alamat Default';

                    formData.append('pengirim_nama', namaVal);
                    formData.append('pengirim_hp', hpVal);
                    formData.append('pengirim_alamat', alamatVal);

                    formData.append('_token', document.querySelector('input[name="_token"]').value);

                    let response = await fetch(`/api/autokirim/cek-ongkir`, {
                        method: 'POST',
                        body: formData
                    });

                    let result = await response.json();
                    if(result.success) {
                        this.ongkirList = result.data;
                        this.showModal = true;
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

            let kurir = this.tempSelected.kurir.toUpperCase();

            // Ambil data panjang karakter langsung dari DOM Form
            let pengirimNama = document.getElementById('pengirim_nama') ? document.getElementById('pengirim_nama').value : '';
            let pengirimHp = document.getElementById('pengirim_hp') ? document.getElementById('pengirim_hp').value : '';
            let pengirimAlamat = document.getElementById('pengirim_alamat') ? document.getElementById('pengirim_alamat').value : '';
            let penerimaNama = document.getElementById('penerima_nama') ? document.getElementById('penerima_nama').value : '';
            let penerimaHp = document.getElementById('penerima_hp') ? document.getElementById('penerima_hp').value : '';
            let penerimaAlamat = document.getElementById('penerima_alamat') ? document.getElementById('penerima_alamat').value : '';
            let deskripsiBarang = document.querySelector('input[name="deskripsi_barang"]') ? document.querySelector('input[name="deskripsi_barang"]').value : '';

            let errors = [];

            // Validasi Batas Karakter ANTERAJA
            if (kurir.includes('ANTERAJA')) {
                if (pengirimNama.length > 50) errors.push("- Nama Pengirim maksimal 50 karakter");
                if (penerimaNama.length > 50) errors.push("- Nama Penerima maksimal 50 karakter");
                if (pengirimHp.length > 16) errors.push("- No HP Pengirim maksimal 16 karakter");
                if (penerimaHp.length > 16) errors.push("- No HP Penerima maksimal 16 karakter");
                if (pengirimAlamat.length > 256) errors.push("- Alamat Pengirim maksimal 256 karakter");
                if (penerimaAlamat.length > 256) errors.push("- Alamat Penerima maksimal 256 karakter");
                if (deskripsiBarang.length > 50) errors.push("- Deskripsi Barang maksimal 50 karakter");
            }

            // Validasi Batas Karakter JNE
            else if (kurir.includes('JNE')) {
                if (pengirimNama.length > 30) errors.push("- Nama Pengirim maksimal 30 karakter (Aturan JNE)");
                if (pengirimHp.length > 30) errors.push("- No HP Pengirim maksimal 30 karakter");
                if (pengirimAlamat.length > 85) errors.push("- Alamat Pengirim maksimal 85 karakter (Aturan Ketat JNE)");
                if (penerimaNama.length > 20) errors.push("- Nama Penerima maksimal 20 karakter (Aturan Ketat JNE)");
                if (penerimaHp.length > 50) errors.push("- No HP Penerima maksimal 50 karakter");
                if (penerimaAlamat.length > 85) errors.push("- Alamat Penerima maksimal 85 karakter (Aturan Ketat JNE)");
                if (deskripsiBarang.length > 60) errors.push("- Deskripsi Barang maksimal 60 karakter");
            }

            // Jika ada pelanggaran batas karakter, tampilkan Alert dan batalkan pemilihan
            if (errors.length > 0) {
                alert("GAGAL MEMILIH " + this.tempSelected.kurir + "\n\nMohon perbaiki data berikut sebelum memilih kurir ini:\n" + errors.join("\n") + "\n\nSilakan tutup pop-up ini (Batal) dan persingkat teks form Anda.");
                return;
            }

            // Jika lolos validasi, terapkan data ke layar utama
            this.selectedKurir       = this.tempSelected.kurir;
            this.selectedLayanan     = this.tempSelected.layanan;
            this.selectedOngkir      = this.tempSelected.harga;
            this.selectedServiceCode = this.tempSelected.kode_layanan;
            this.selectedLogoUrl     = this.tempSelected.logo_url;
            this.selectedEtd         = this.tempSelected.etd;

            this.showModal           = false; // Tutup Modal
        },

        jenisCod: 'cod_barang',

       // Validasi Form sebelum kirim (DENGAN PROTEKSI SALDO & TOKEN DANA)
        validateForm(e) {
            if(!this.selectedServiceCode || !this.selectedOngkir) {
                e.preventDefault();
                alert("Silahkan hitung ongkos kirim dan pilih jasa ekspedisi terlebih dahulu!");
                return;
            }

            // Jika BUKAN COD, wajib melakukan validasi saldo dan gateway pembayaran
            if (this.tipePesanan !== 'cod') {

                if(!this.selectedPayment) {
                    e.preventDefault();
                    alert("Silahkan pilih metode pembayaran terlebih dahulu!");
                    return;
                }

                if(this.selectedPayment === 'potong_saldo') {
                    let userSaldo = {{ auth()->user()->saldo ?? 0 }};
                    if(this.selectedOngkir > userSaldo) {
                        e.preventDefault();
                        alert("Gagal! Saldo akun Anda (Rp " + userSaldo.toLocaleString('id-ID') + ") tidak mencukupi untuk membayar ongkos kirim ini (Rp " + this.selectedOngkir.toLocaleString('id-ID') + "). Silahkan isi ulang atau gunakan metode pembayaran lainnya!");
                        return;
                    }
                }

                @if(empty(auth()->user()->dana_token))
                if(this.selectedPayment === 'dana_binding') {
                    e.preventDefault();
                    alert("Gagal! Akun DANA Anda belum diikat (bind). Silahkan pilih metode 'DANA Payment Gateway' atau hubungkan akun DANA Anda terlebih dahulu di pengaturan profil!");
                    return;
                }
                @endif
            }
        },

        saveContact(role) {
            alert(`Fitur Sukses: Kontak ${role} berhasil diamankan ke Buku Alamat Anda!`);
        }
    }));
});

// ==========================================
// AUTO-FORMATTER INPUT (NAMA, HP, ALAMAT)
// ==========================================
document.addEventListener('DOMContentLoaded', function() {

    // 1. Format Nama (Huruf & Spasi Saja + Kapital)
    const nameInputs = ['pengirim_nama', 'penerima_nama'];
    nameInputs.forEach(id => {
        let el = document.getElementById(id);
        if(el) {
            el.addEventListener('input', function() {
                let val = this.value.replace(/[^a-zA-Z\s]/g, '');
                this.value = val.toUpperCase();
            });
        }
    });

    // 2. Format HP (Murni Angka, Hapus +62/62, Otomatis tambah 0)
    const phoneInputs = ['pengirim_hp', 'penerima_hp'];
    phoneInputs.forEach(id => {
        let el = document.getElementById(id);
        if(el) {
            el.addEventListener('input', function() {
                let val = this.value.replace(/\D/g, '');

                if(val.startsWith('62')) {
                    val = '0' + val.substring(2);
                }
                else if(val.length > 0 && !val.startsWith('0')) {
                    val = '0' + val;
                }

                this.value = val;
            });
        }
    });

    // 3. Format Alamat (Hanya Huruf, Angka, Spasi + Kapital) -> Murni Tanpa Tanda Baca
    const addressInputs = ['pengirim_alamat', 'penerima_alamat'];
    addressInputs.forEach(id => {
        let el = document.getElementById(id);
        if(el) {
            el.addEventListener('input', function() {
                let val = this.value.replace(/[^a-zA-Z0-9\s]/g, '');
                this.value = val.toUpperCase();
            });
        }
    });

});
</script>

<style>
/* Grayscale Scrollbar */
.custom-scrollbar::-webkit-scrollbar { width: 4px; }
.custom-scrollbar::-webkit-scrollbar-track { background: #fafafa; border-radius: 8px; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #d4d4d8; border-radius: 8px; }
.custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #a1a1aa; }
</style>
@endsection
