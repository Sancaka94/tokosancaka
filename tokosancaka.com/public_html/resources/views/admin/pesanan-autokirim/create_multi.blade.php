{{--
    File: resources/views/admin/pesanan_autokirim/create_multi.blade.php
    Deskripsi: Halaman Multi-Koli Admin dengan UI Alpine.js (Modern Design)
--}}
@extends('layouts.admin')

{{-- 🔥 TAMBAHAN KODE PENGAMAN (IDEMPOTENCY) 🔥 --}}
@php
    if (!isset($idempotencyKey)) {
        $idempotencyKey = (string) \Illuminate\Support\Str::uuid();
    }
@endphp

@section('content')
<div class="max-w-6xl mx-auto px-4 py-8 font-sans" x-data="orderFormData()">
    <div class="mb-8 border-b border-gray-200 pb-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <!-- Bagian Kiri: Judul dan Deskripsi -->
        <div>
            <h1 class="text-3xl font-extrabold text-black tracking-tight">Kirim Paket Massal <span class="text-grey-500 font-medium">Autokirim </span><span class="text-red-500 font-medium"> (Paket Lebih Dari 1 Koli)</span></h1>
            <p class="text-gray-500 mt-2 text-sm">Buat 1 pesanan untuk banyak paket (koli) ke tujuan yang sama dengan mudah.</p>
        </div>

        <!-- Bagian Kanan: Mode Satuan -->
        <a href="{{ route('admin.pesanan-autokirim.create') }}"
           class="inline-flex items-center gap-2 px-4 py-2.5 bg-white border border-gray-300 rounded-lg shadow-sm text-sm font-bold text-gray-700 hover:bg-gray-50 hover:text-black hover:border-gray-400 transition-all shrink-0">
            <i class="fa-solid fa-exchange-alt"></i>
            <span>Mode Satuan</span>
        </a>
    </div>

    <!-- Alert Notifikasi Berhasil -->
    @if(session('success'))
        <div class="p-4 mb-6 text-sm text-gray-800 bg-gray-50 rounded-md border border-gray-300 flex items-center gap-3 shadow-sm">
            <i class="fa-solid fa-circle-check text-lg text-black"></i>
            <div><span class="font-bold">Sukses!</span> {{ session('success') }}</div>
        </div>
    @endif

    <!-- Alert Notifikasi Gagal -->
    @if(session('error'))
        <div class="p-4 mb-6 text-sm text-red-700 bg-red-50 rounded-md border border-red-200 flex items-center gap-3 shadow-sm">
            <i class="fa-solid fa-circle-xmark text-lg text-red-600"></i>
            <div><span class="font-bold">Gagal!</span> {{ session('error') }}</div>
        </div>
    @endif

    <!-- TAMBAHKAN BLOK INI -->
    <!-- Alert Error Validasi Input Form -->
    @if ($errors->any())
        <div class="p-4 mb-6 text-sm text-red-700 bg-red-50 rounded-md border border-red-200 shadow-sm">
            <div class="flex items-center gap-3 mb-2">
                <i class="fa-solid fa-triangle-exclamation text-lg text-red-600"></i>
                <span class="font-bold">Periksa kembali data Anda!</span>
            </div>
            <ul class="list-disc pl-9">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- FORM UTAMA -->
    <form x-ref="orderForm" action="{{ route('admin.autokirim.koli.store') }}" method="POST" @submit="validateForm($event)" class="space-y-8">
        @csrf
        <input type="hidden" name="idempotency_key" value="{{ $idempotencyKey }}">

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">

            <!-- ========================================== -->
            <!-- SISI KIRI: DATA PENGIRIM & PENERIMA -->
            <!-- ========================================== -->
            <div class="lg:col-span-7 space-y-6">

                <!-- Card Data Pengirim -->
                <div class="bg-white p-6 rounded-lg border border-gray-200 shadow-sm">
                   <div class="flex items-center justify-between mb-5">
                        <h2 class="text-base font-bold text-black flex items-center"><i class="fa-solid fa-user-check text-gray-800 mr-2.5"></i> Data Pengirim</h2>
                        <div class="flex items-center gap-3">
                            <!-- Badge Kode Pickup Point -->
                            <div class="bg-gray-100 border border-gray-200 text-[10px] font-bold px-2 py-1.5 rounded flex items-center gap-1.5 uppercase tracking-wider">
                                <template x-if="isGeneratingPickup"><i class="fa-solid fa-spinner fa-spin text-blue-600"></i></template>
                                <template x-if="!isGeneratingPickup"><i class="fa-solid fa-store" :class="pickupPointCode ? 'text-green-600' : 'text-red-500'"></i></template>
                                <span x-show="isGeneratingPickup" class="text-blue-600">MEMPROSES KODE...</span>
                                <span x-show="!isGeneratingPickup" x-text="pickupPointCode ? 'PICKUP: ' + pickupPointCode : 'BELUM ADA PICKUP POINT'" :class="pickupPointCode ? 'text-gray-800' : 'text-red-500'"></span>
                            </div>

                            <button type="button" @click="simpanPengirim = !simpanPengirim"
                                    class="text-xs font-medium border px-3 py-1.5 rounded transition duration-200 flex items-center gap-1.5"
                                    :class="simpanPengirim ? 'bg-black text-white border-black shadow-inner' : 'bg-white text-black border-gray-300 hover:bg-gray-50'">
                                <i class="fa-solid" :class="simpanPengirim ? 'fa-circle-check' : 'fa-bookmark'"></i>
                                <span x-text="simpanPengirim ? 'Akan Disimpan' : 'Simpan Kontak'"></span>
                            </button>
                            <input type="hidden" name="save_sender" :value="simpanPengirim ? 'on' : ''">
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="col-span-2 sm:col-span-1 relative" @click.away="showContactSender = false">
                            <label class="block text-xs font-medium text-gray-700 mb-1.5">NAMA LENGKAP PENGIRIM <span class="text-red-500">*</span></label>
                            <div class="relative">
                                <input type="text" id="pengirim_nama" name="pengirim_nama" x-model="pengirimNama" @input.debounce.400ms="searchContact('sender')" @focus="if(pengirimNama.length >= 2) showContactSender = true" required placeholder="Ketik nama kontak..." class="uppercase w-full border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-1 focus:ring-black focus:border-black px-4 py-2.5 bg-white transition duration-200" autocomplete="off">
                                <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                    <i class="fa-solid fa-spinner fa-spin text-black" x-show="isSearchingContactSender" x-cloak></i>
                                </div>
                            </div>

                            <!-- Dropdown Hasil Kontak Pengirim -->
                            <div x-show="showContactSender" x-transition class="absolute z-[120] w-full mt-1 bg-white rounded-md shadow-lg border border-gray-200 max-h-48 overflow-y-auto" x-cloak>
                                <template x-if="contactResultsSender.length > 0">
                                    <div>
                                        <template x-for="kontak in contactResultsSender">
                                            <div @click="selectContact('sender', kontak)" class="px-4 py-3 hover:bg-gray-50 cursor-pointer border-b border-gray-100 text-sm transition">
                                                <p class="font-bold text-black uppercase" x-text="kontak.nama"></p>
                                                <p class="text-xs text-gray-500 mt-0.5" x-text="kontak.no_hp"></p>
                                            </div>
                                        </template>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <div class="col-span-2 sm:col-span-1">
                            <label class="block text-xs font-medium text-gray-700 mb-1.5">NOMOR HP / WA <span class="text-red-500">*</span></label>
                            <input type="text" id="pengirim_hp" name="pengirim_hp" value="{{ old('pengirim_hp') }}" required @input.debounce.1000ms="autoGeneratePickup()" class="uppercase w-full border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-1 focus:ring-black focus:border-black px-4 py-2.5 bg-white transition duration-200">
                        </div>

                        <!-- Autocomplete Alamat Pengirim -->
                        <div class="col-span-2 relative" @click.away="showSenderDropdown = false">
                            <label class="block text-xs font-medium text-gray-700 mb-1.5">KECAMATAN / KABUPATEN PENGIRIM <span class="text-red-500">*</span></label>
                            <div class="relative">
                                <input type="text" x-model="senderQuery" @input.debounce.400ms="searchAddress('sender')" @focus="showSenderDropdown = true" placeholder="Ketik minimal 3 karakter wilayah..." class="uppercase w-full border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-1 focus:ring-black focus:border-black pl-4 pr-10 py-2.5 bg-white transition" autocomplete="off" required>
                                <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none text-gray-400 text-xs">
                                    <i class="fa-solid fa-magnifying-glass" x-show="!isSearchingSender"></i>
                                    <i class="fa-solid fa-spinner fa-spin text-black" x-show="isSearchingSender" x-cloak></i>
                                </div>
                            </div>

                            <input type="hidden" name="pengirim_district_id" x-model="senderDistrictId">
                            <input type="hidden" name="pengirim_subdistrict_id" x-model="senderSubdistrictId">
                            <input type="hidden" name="pengirim_kodepos" x-model="senderPostalCode">
                            <input type="hidden" name="pengirim_province" x-model="senderProvince">
                            <input type="hidden" name="pengirim_regency" x-model="senderRegency">
                            <input type="hidden" name="pengirim_district" x-model="senderDistrict">
                            <input type="hidden" name="pengirim_village" x-model="senderVillage">

                            <div x-show="showSenderDropdown" x-transition class="absolute z-[110] w-full mt-1 bg-white rounded-md shadow-lg border border-gray-200 max-h-48 overflow-y-auto" x-cloak>
                                <template x-if="senderResults.length > 0">
                                    <div>
                                        <template x-for="res in senderResults">
                                            <div @click="selectAddress('sender', res)" class="px-4 py-3 hover:bg-gray-50 cursor-pointer border-b border-gray-100 flex items-start gap-3 transition">
                                                <i class="fa-solid fa-location-dot text-red-600 mt-0.5 shrink-0 text-base"></i>
                                                <div>
                                                    <p class="font-medium text-black text-[11px] leading-relaxed" x-text="res.full_address_display"></p>
                                                    <p class="text-[10px] font-bold text-red-600 mt-1" x-text="'KODEPOS: ' + res.postal_code"></p>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <div class="col-span-2">
                            <label class="block text-xs font-medium text-gray-700 mb-1.5">ALAMAT JALAN PENGIRIM <span class="text-red-500">*</span></label>
                            <textarea id="pengirim_alamat" name="pengirim_alamat" rows="2" required minlength="15" @input.debounce.1000ms="autoGeneratePickup()" class="uppercase w-full border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-1 focus:ring-black focus:border-black px-4 py-2.5 bg-white transition">{{ old('pengirim_alamat') }}</textarea>
                        </div>
                    </div>
                </div>

                <!-- Card Data Penerima -->
                <div class="bg-white p-6 rounded-lg border border-gray-200 shadow-sm">
                    <div class="flex items-center justify-between mb-5">
                        <h2 class="text-base font-bold text-black flex items-center"><i class="fa-solid fa-location-dot text-gray-800 mr-2.5"></i> Data Penerima</h2>
                        <button type="button" @click="simpanPenerima = !simpanPenerima"
                                class="text-xs font-medium border px-3 py-1.5 rounded transition duration-200 flex items-center gap-1.5"
                                :class="simpanPenerima ? 'bg-black text-white border-black shadow-inner' : 'bg-white text-black border-gray-300 hover:bg-gray-50'">
                            <i class="fa-solid" :class="simpanPenerima ? 'fa-circle-check' : 'fa-bookmark'"></i>
                            <span x-text="simpanPenerima ? 'Akan Disimpan' : 'Simpan Kontak'"></span>
                        </button>
                        <input type="hidden" name="save_receiver" :value="simpanPenerima ? 'on' : ''">
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="col-span-2 sm:col-span-1 relative" @click.away="showContactReceiver = false">
                            <label class="block text-xs font-medium text-gray-700 mb-1.5">NAMA LENGKAP PENERIMA <span class="text-red-500">*</span></label>
                            <div class="relative">
                                <input type="text" id="penerima_nama" name="penerima_nama" x-model="penerimaNama" @input.debounce.400ms="searchContact('receiver')" @focus="if(penerimaNama.length >= 2) showContactReceiver = true" required placeholder="Ketik nama kontak..." class="uppercase w-full border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-1 focus:ring-black focus:border-black px-4 py-2.5 bg-white transition" autocomplete="off">
                            </div>

                            <div x-show="showContactReceiver" x-transition class="absolute z-[120] w-full mt-1 bg-white rounded-md shadow-lg border border-gray-200 max-h-48 overflow-y-auto" x-cloak>
                                <template x-if="contactResultsReceiver.length > 0">
                                    <div>
                                        <template x-for="kontak in contactResultsReceiver">
                                            <div @click="selectContact('receiver', kontak)" class="px-4 py-3 hover:bg-gray-50 cursor-pointer border-b border-gray-100 text-sm transition">
                                                <p class="font-bold text-black uppercase" x-text="kontak.nama"></p>
                                                <p class="text-xs text-gray-500 mt-0.5" x-text="kontak.no_hp"></p>
                                            </div>
                                        </template>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <div class="col-span-2 sm:col-span-1">
                            <label class="block text-xs font-medium text-gray-700 mb-1.5">NOMOR HP / WA <span class="text-red-500">*</span></label>
                            <input type="text" id="penerima_hp" name="penerima_hp" value="{{ old('penerima_hp') }}" required class="uppercase w-full border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-1 focus:ring-black focus:border-black px-4 py-2.5 bg-white transition">
                        </div>

                        <!-- Autocomplete Alamat Penerima -->
                        <div class="col-span-2 relative" @click.away="showReceiverDropdown = false">
                            <label class="block text-xs font-medium text-gray-700 mb-1.5">KECAMATAN / KABUPATEN PENERIMA <span class="text-red-500">*</span></label>
                            <div class="relative">
                                <input type="text" x-model="receiverQuery" @input.debounce.400ms="searchAddress('receiver')" @focus="showReceiverDropdown = true" placeholder="Ketik minimal 3 karakter wilayah..." class="uppercase w-full border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-1 focus:ring-black focus:border-black pl-4 pr-10 py-2.5 bg-white transition" autocomplete="off" required>
                                <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none text-gray-400 text-xs">
                                    <i class="fa-solid fa-magnifying-glass" x-show="!isSearchingReceiver"></i>
                                    <i class="fa-solid fa-spinner fa-spin text-black" x-show="isSearchingReceiver" x-cloak></i>
                                </div>
                            </div>

                            <input type="hidden" name="penerima_district_id" x-model="receiverDistrictId">
                            <input type="hidden" name="penerima_subdistrict_id" x-model="receiverSubdistrictId">
                            <input type="hidden" name="penerima_kodepos" x-model="receiverPostalCode">
                            <input type="hidden" name="penerima_province" x-model="receiverProvince">
                            <input type="hidden" name="penerima_regency" x-model="receiverRegency">
                            <input type="hidden" name="penerima_district" x-model="receiverDistrict">
                            <input type="hidden" name="penerima_village" x-model="receiverVillage">

                            <div x-show="showReceiverDropdown" x-transition class="absolute z-[110] w-full mt-1 bg-white rounded-md shadow-lg border border-gray-200 max-h-48 overflow-y-auto" x-cloak>
                                <template x-if="receiverResults.length > 0">
                                    <div>
                                        <template x-for="res in receiverResults">
                                            <div @click="selectAddress('receiver', res)" class="px-4 py-3 hover:bg-gray-50 cursor-pointer border-b border-gray-100 flex items-start gap-3 transition">
                                                <i class="fa-solid fa-location-dot text-blue-600 mt-0.5 shrink-0 text-base"></i>
                                                <div>
                                                    <p class="font-medium text-black text-[11px] leading-relaxed" x-text="res.full_address_display"></p>
                                                    <p class="text-[10px] font-bold text-blue-600 mt-1" x-text="'KODEPOS: ' + res.postal_code"></p>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <div class="col-span-2">
                            <label class="block text-xs font-medium text-gray-700 mb-1.5">ALAMAT JALAN PENERIMA <span class="text-red-500">*</span></label>
                            <textarea id="penerima_alamat" name="penerima_alamat" rows="2" required minlength="15" class="uppercase w-full border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-1 focus:ring-black focus:border-black px-4 py-2.5 bg-white transition">{{ old('penerima_alamat') }}</textarea>
                        </div>
                    </div>
                </div>

            </div> <!-- END SISI KIRI -->

            <!-- ========================================== -->
            <!-- SISI KANAN: DETAIL KOLI & ACTION -->
            <!-- ========================================== -->
            <div class="lg:col-span-5 space-y-6">

                <!-- Card Pengaturan Umum (Berlaku Semua Koli) -->
                <div class="bg-white p-6 rounded-lg border border-gray-200 shadow-sm">
                    <h2 class="text-base font-bold text-black mb-5 flex items-center"><i class="fa-solid fa-layer-group text-gray-800 mr-2.5"></i> Informasi Barang (Umum)</h2>

                    <div class="space-y-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1.5">TIPE PESANAN</label>
                            <div class="grid grid-cols-2 gap-2">
                                <label class="flex flex-col items-center justify-center p-2.5 border rounded cursor-pointer transition-all duration-200" :class="tipePesanan === 'reguler' ? 'border-black bg-black text-white font-medium shadow-sm' : 'border-gray-300 hover:border-gray-400 text-gray-600 bg-white'">
                                    <input type="radio" value="reguler" x-model="tipePesanan" @change="resetSemuaOngkir()" class="hidden">
                                    <span class="text-xs tracking-wide">REGULER</span>
                                </label>
                                <label class="flex flex-col items-center justify-center p-2.5 border rounded cursor-pointer transition-all duration-200" :class="tipePesanan === 'cod' ? 'border-black bg-black text-white font-medium shadow-sm' : 'border-gray-300 hover:border-gray-400 text-gray-600 bg-white'">
                                    <input type="radio" value="cod" x-model="tipePesanan" @change="resetSemuaOngkir()" class="hidden">
                                    <span class="text-xs tracking-wide">COD</span>
                                </label>
                            </div>
                        </div>

                        <!-- Pilihan COD -->
                        <div x-show="tipePesanan === 'cod'" x-transition class="pt-2" x-cloak>
                            <label class="block text-xs font-medium text-gray-700 mb-2.5">PILIH JENIS COD <span class="text-red-500">*</span></label>
                            <div class="flex flex-col gap-2 bg-gray-50 p-3 rounded border border-gray-200">
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="radio" name="payment_method_cod" value="CODBARANG" x-model="jenisCod" class="text-black focus:ring-black border-gray-300">
                                    <span class="text-[11px] font-medium text-gray-800">COD Barang + Ongkir</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer mt-1">
                                    <input type="radio" name="payment_method_cod" value="COD" x-model="jenisCod" class="text-black focus:ring-black border-gray-300">
                                    <span class="text-[11px] font-medium text-gray-800">COD Ongkir Saja</span>
                                </label>
                            </div>
                            <input type="hidden" name="metode_pembayaran" x-bind:value="tipePesanan === 'cod' ? jenisCod : selectedPayment" :disabled="tipePesanan !== 'cod'">
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <!-- 1. Kategori -->
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1.5">KATEGORI BARANG PAKET</label>
                                <select name="item_type" x-model="kategoriBarang" required class="uppercase w-full border border-gray-300 rounded-md text-xs focus:outline-none focus:ring-1 focus:ring-black focus:border-black px-3 py-2.5 bg-white">
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

                            <!-- 2. Asuransi -->
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1.5">ASURANSI PENGIRIMAN</label>
                                <select name="ansuransi" x-model="asuransi" @change="resetSemuaOngkir()" class="uppercase w-full border border-gray-300 rounded-md text-xs focus:outline-none focus:ring-1 focus:ring-black focus:border-black px-3 py-2.5 bg-white">
                                    <option value="tidak">TIDAK</option>
                                    <option value="iya">YA, ASURANSIKAN</option>
                                </select>
                            </div>

                            <!-- 3. Metode Serah Terima (BARU) -->
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1.5">METODE SERAH TERIMA</label>
                                <select name="is_sender_pp" x-model="isSenderPp" @change="resetSemuaOngkir()" class="uppercase w-full border border-gray-300 rounded-md text-xs focus:outline-none focus:ring-1 focus:ring-black focus:border-black px-3 py-2.5 bg-white text-gray-800">
                                    <option value="1">KURIR JEMPUT (PICKUP)</option>
                                    <option value="0">ANTAR KE CABANG (DROPOFF)</option>
                                </select>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1.5">DESKRIPSI UMUM</label>
                            <input type="text" name="item_description" x-model="deskripsiBarang" placeholder="CONTOH: PAKAIAN BEKAS" required class="uppercase w-full border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-1 focus:ring-black px-4 py-2 bg-white">
                        </div>

                        <div class="bg-blue-50 p-4 border border-blue-200 rounded-md" x-show="asuransi === 'iya' || tipePesanan === 'cod'">
                            <label class="block text-xs font-bold text-gray-800 mb-1.5">NILAI HARGA BARANG (TOTAL RP) <span class="text-red-500">*</span></label>
                            <input type="hidden" name="item_price" :value="nilaiBarang || 1000">
                            <input type="text" inputmode="numeric" x-model="displayNilaiBarang"
                                   @input="let clean = $event.target.value.replace(/\D/g, ''); nilaiBarang = clean; displayNilaiBarang = clean ? new Intl.NumberFormat('id-ID').format(clean) : ''; resetSemuaOngkir();"
                                   @focus="displayNilaiBarang = nilaiBarang ? new Intl.NumberFormat('id-ID').format(nilaiBarang) : ''"
                                   @blur="displayNilaiBarang = nilaiBarang ? 'Rp ' + new Intl.NumberFormat('id-ID').format(nilaiBarang) : ''"
                                   placeholder="Rp 0" class="w-full border border-gray-300 rounded-md text-sm focus:outline-none px-4 py-2.5 bg-white font-bold text-gray-900"
                                   :required="asuransi === 'iya' || tipePesanan === 'cod'">
                            <p class="text-[10px] mt-1.5 text-gray-600">Harga ini dibagi rata ke semua koli untuk dasar perhitungan COD / Asuransi.</p>
                        </div>
                    </div>
                </div>

                <!-- DAFTAR PAKET / KOLI DINAMIS -->
                <div class="space-y-4">
                    <template x-for="(paket, index) in packages" :key="paket.id">
                        <div class="bg-white p-5 rounded-lg border border-gray-300 shadow-sm transition-all" :class="paket.selectedOngkir > 0 ? 'border-green-400 ring-1 ring-green-400' : ''">
                            <div class="flex justify-between items-center border-b border-gray-100 pb-3 mb-3">
                                <h3 class="font-bold text-gray-800 text-sm">📦 PAKET KOLI #<span x-text="index + 1"></span></h3>
                                <button type="button" x-show="packages.length > 1" @click="removePackage(index)" class="text-red-500 hover:text-red-700 text-xs font-bold bg-red-50 px-2 py-1 rounded">
                                    <i class="fa-solid fa-trash mr-1"></i>HAPUS
                                </button>
                            </div>

                            <div class="grid grid-cols-2 gap-4 mb-3">
                                <div>
                                    <label class="block text-[10px] font-bold text-gray-700 mb-1">BERAT (GRAM) *</label>
                                    <input type="text" inputmode="numeric" x-model="paket.berat" @input="paket.berat = $event.target.value.replace(/\D/g, ''); paket.selectedOngkir = 0;" placeholder="1000" class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:border-black font-medium" required>
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-gray-700 mb-1">DIMENSI PxLxT (CM)</label>
                                    <div class="flex gap-1.5">
                                        <input type="text" x-model="paket.panjang" @input="paket.panjang = $event.target.value.replace(/\D/g, ''); paket.selectedOngkir = 0;" placeholder="P" class="w-1/3 border border-gray-300 rounded text-center text-xs py-2 focus:border-black">
                                        <input type="text" x-model="paket.lebar" @input="paket.lebar = $event.target.value.replace(/\D/g, ''); paket.selectedOngkir = 0;" placeholder="L" class="w-1/3 border border-gray-300 rounded text-center text-xs py-2 focus:border-black">
                                        <input type="text" x-model="paket.tinggi" @input="paket.tinggi = $event.target.value.replace(/\D/g, ''); paket.selectedOngkir = 0;" placeholder="T" class="w-1/3 border border-gray-300 rounded text-center text-xs py-2 focus:border-black">
                                    </div>
                                </div>
                            </div>

                            <!-- Tombol Cek Ongkir Koli -->
                            <button type="button" @click="cekOngkir(index)" :disabled="paket.isLoading"
                                    class="w-full py-2.5 rounded text-xs font-bold transition flex items-center justify-center gap-2"
                                    :class="paket.selectedOngkir > 0 ? 'bg-gray-100 text-gray-700 hover:bg-gray-200' : 'bg-black text-white hover:bg-gray-800'">
                                <i class="fa-solid fa-spinner fa-spin" x-show="paket.isLoading"></i>
                                <i class="fa-solid fa-magnifying-glass" x-show="!paket.isLoading && paket.selectedOngkir === 0"></i>
                                <span x-text="paket.isLoading ? 'MEMUAT...' : (paket.selectedOngkir > 0 ? 'GANTI EKSPEDISI' : 'CEK & PILIH EKSPEDISI')"></span>
                            </button>

                            <!-- Hasil Pilihan Ekspedisi per Koli -->
                            <div x-show="paket.selectedOngkir > 0" x-transition class="mt-3 p-3 border border-green-200 bg-green-50 rounded flex justify-between items-center">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 bg-white border border-gray-200 p-1 rounded flex items-center justify-center">
                                        <template x-if="paket.selectedLogoUrl"><img :src="paket.selectedLogoUrl" class="w-full h-full object-contain"></template>
                                        <template x-if="!paket.selectedLogoUrl"><i class="fa-solid fa-truck text-gray-400"></i></template>
                                    </div>
                                    <div>
                                        <p class="text-[11px] font-bold uppercase text-gray-900 leading-tight" x-text="paket.selectedKurir"></p>
                                        <p class="text-[9px] text-gray-600 uppercase" x-text="paket.selectedLayanan"></p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm font-extrabold text-green-700">Rp <span x-text="paket.selectedOngkir.toLocaleString('id-ID')"></span></p>
                                </div>
                            </div>

                            <!-- Hidden Inputs Binding ke Backend -->
                            <input type="hidden" :name="`packages[${index}][weight]`" :value="paket.berat">
                            <input type="hidden" :name="`packages[${index}][length]`" :value="paket.panjang || 10">
                            <input type="hidden" :name="`packages[${index}][width]`" :value="paket.lebar || 10">
                            <input type="hidden" :name="`packages[${index}][height]`" :value="paket.tinggi || 10">
                            <input type="hidden" :name="`packages[${index}][courier_code]`" :value="paket.selectedKurir">
                            <input type="hidden" :name="`packages[${index}][service_code]`" :value="paket.selectedServiceCode">
                            <input type="hidden" :name="`packages[${index}][shipping_cost]`" :value="paket.selectedOngkir">
                        </div>
                    </template>

                    <button type="button" @click="addPackage()" class="w-full py-3.5 border-2 border-dashed border-gray-300 text-gray-500 rounded-lg font-bold text-xs hover:bg-gray-50 hover:text-black transition">
                        <i class="fa-solid fa-plus mr-2"></i> TAMBAH PAKET KOLI LAIN
                    </button>
                </div> <!-- End Daftar Paket -->

                <!-- RINCIAN BIAYA GLOBAL -->
                <div x-show="totalOngkirDasar > 0" x-transition class="bg-gray-50 p-6 rounded-lg border border-gray-200 mt-6" x-cloak>
                    <h3 class="text-xs font-bold text-gray-800 uppercase tracking-widest mb-4 border-b border-gray-200 pb-2 flex items-center">
                        <i class="fa-solid fa-file-invoice-dollar mr-2"></i> Rincian Biaya (Semua Koli)
                    </h3>

                    <div class="space-y-2.5 text-sm">
                        <div class="flex justify-between items-center text-gray-600">
                            <span>Total Ongkos Kirim (<span x-text="packages.length"></span> Paket)</span>
                            <span class="font-medium text-black">Rp <span x-text="totalOngkirDasar.toLocaleString('id-ID')"></span></span>
                        </div>
                        <div x-show="asuransi === 'iya'" class="flex justify-between items-center text-gray-600" x-cloak>
                            <span>Biaya Asuransi Total</span>
                            <span class="font-medium text-black">Rp <span x-text="biayaAsuransi.toLocaleString('id-ID')"></span></span>
                        </div>
                        <div x-show="tipePesanan === 'cod'" class="flex justify-between items-center text-gray-600" x-cloak>
                            <span>Admin COD Total</span>
                            <span class="font-medium text-black">Rp <span x-text="biayaCod.toLocaleString('id-ID')"></span></span>
                        </div>

                        <!-- Grand Total Potong Saldo (NON COD) -->
                        <div x-show="tipePesanan !== 'cod'" class="mt-4 pt-3 border-t-2 border-dashed border-gray-300 flex justify-between items-center" x-cloak>
                            <span class="font-bold text-black uppercase">Total Potong Saldo</span>
                            <span class="font-extrabold text-red-600 text-lg">Rp <span x-text="grandTotalPotongan.toLocaleString('id-ID')"></span></span>
                        </div>

                        <!-- Grand Total Tagihan Kurir (COD) -->
                        <div x-show="tipePesanan === 'cod'" class="mt-4 pt-3 border-t-2 border-dashed border-gray-300 flex justify-between items-center" x-cloak>
                            <div class="flex flex-col">
                                <span class="font-bold text-black uppercase">Tagihan Kurir (COD)</span>
                            </div>
                            <span class="font-extrabold text-blue-600 text-lg">Rp <span x-text="grandTotalCod.toLocaleString('id-ID')"></span></span>
                        </div>
                    </div>
                </div>

            </div> <!-- END SISI KANAN -->
        </div> <!-- END ROW 1 (GRID) -->

        <!-- ========================================================================= -->
        <!-- ROW 2: SISI BAWAH (METODE PEMBAYARAN & SUBMIT FULL WIDTH LANDSCAPE) -->
        <!-- ========================================================================= -->
        <div class="bg-white p-6 rounded-lg border border-gray-200 shadow-sm mt-6">
            <div class="flex flex-col lg:flex-row lg:items-stretch justify-between gap-8">

                <!-- BAGIAN KIRI: PILIHAN PEMBAYARAN -->
                <div class="w-full lg:w-2/3">
                    <h3 class="text-sm font-bold text-black uppercase tracking-widest mb-4 flex items-center">
                        <i class="fa-solid fa-wallet mr-2"></i> Pilih Metode Pembayaran
                    </h3>

                    <div x-show="tipePesanan !== 'cod'" x-transition x-cloak>
                        <button type="button" @click="if(formIsValid) showPaymentModal = true" :disabled="!formIsValid"
                            class="flex items-center justify-between w-full border p-4 rounded-lg transition-all"
                            :class="!formIsValid ? 'bg-gray-100 border-gray-200 opacity-60 cursor-not-allowed' : (selectedPayment ? 'border-black ring-1 ring-black shadow-sm cursor-pointer hover:bg-gray-50' : 'border-gray-300 cursor-pointer hover:bg-gray-50')">

                            <div class="flex items-center gap-4">
                                <div class="w-10 h-10 rounded bg-white flex items-center justify-center shrink-0 p-1 border border-gray-100 shadow-sm" :class="!formIsValid ? 'opacity-50' : ''">
                                    <template x-if="!selectedPaymentIcon"><i class="fa-solid fa-wallet text-gray-400 text-xl"></i></template>
                                    <template x-if="selectedPaymentIcon">
                                        <div class="w-full h-full flex items-center justify-center">
                                            <template x-if="selectedPaymentIcon.includes('http')"><img :src="selectedPaymentIcon" class="max-w-full max-h-full object-contain"></template>
                                            <template x-if="!selectedPaymentIcon.includes('http')"><i :class="selectedPaymentIcon + ' text-xl'"></i></template>
                                        </div>
                                    </template>
                                </div>
                                <div class="flex flex-col text-left">
                                    <span class="text-sm font-bold uppercase" :class="!formIsValid ? 'text-gray-400' : 'text-gray-900'" x-text="selectedPaymentName || 'PILIH METODE PEMBAYARAN'"></span>
                                    <span class="text-[11px] uppercase mt-0.5" :class="!formIsValid ? 'text-gray-400' : 'text-gray-500'" x-text="!formIsValid ? 'Isi form alamat & pilih ongkir terlebih dahulu' : (selectedPaymentName ? 'Klik untuk mengganti metode' : 'Pilih metode untuk melanjutkan')"></span>
                                </div>
                            </div>
                            <i class="fa-solid fa-chevron-right text-gray-400"></i>
                        </button>

                        <!-- HIDDEN INPUT METODE NON COD -->
                        <input type="hidden" name="metode_pembayaran" :value="selectedPayment" :disabled="tipePesanan === 'cod'">

                        <div x-show="selectedPayment === 'cash'" x-transition class="mt-4 p-4 border border-emerald-200 rounded-md text-xs text-black bg-emerald-50" x-cloak>
                            <span class="font-bold block mb-1 uppercase text-sm">Pembayaran Tunai (Cash)</span>
                            <span class="leading-relaxed">Resi (AWB) tercetak otomatis <b class="font-bold text-emerald-700">tanpa memotong saldo dompet digital</b> Anda.</span>
                        </div>

                        <div x-show="selectedPayment === 'potong_saldo'" x-transition class="mt-4 p-4 border border-gray-200 rounded-md text-xs text-black bg-gray-50" x-cloak>
                            <div class="flex items-center justify-between mb-2 pb-2 border-b border-gray-200">
                                <span class="font-medium text-gray-600">SALDO WALLET SAAT INI:</span>
                                <span class="font-bold text-black text-sm">Rp {{ number_format(auth()->user()->saldo ?? 0, 0, ',', '.') }}</span>
                            </div>
                            <template x-if="grandTotalPotongan > {{ auth()->user()->saldo ?? 0 }}">
                                <div class="mt-2 text-red-600 font-medium flex items-start gap-2">
                                    <i class="fa-solid fa-triangle-exclamation mt-0.5"></i>
                                    <span>Saldo tidak mencukupi! Silahkan isi ulang atau pilih metode lain.</span>
                                </div>
                            </template>
                        </div>
                    </div>

                    <div x-show="tipePesanan === 'cod'" x-transition class="p-4 border border-blue-200 rounded-md bg-blue-50 text-blue-800 text-xs mt-2" x-cloak>
                        <i class="fa-solid fa-info-circle mr-1"></i> Mode Multi Koli COD. Pastikan nominal harga barang disesuaikan karena biaya admin COD dihitung dari keseluruhan tagihan.
                    </div>
                </div>

                <!-- BAGIAN KANAN: TOMBOL SUBMIT -->
                <div class="w-full lg:w-1/3 flex flex-col justify-center border-t lg:border-t-0 lg:border-l border-gray-200 pt-6 lg:pt-0 lg:pl-8 mt-4 lg:mt-0">
                    <button type="submit"
                        :disabled="!isFormSubmitReady"
                        class="w-full py-4 rounded-md font-bold transition-all text-sm tracking-widest flex justify-center items-center gap-3 uppercase"
                        :class="!isFormSubmitReady ? 'bg-gray-200 text-gray-400 cursor-not-allowed' : 'bg-emerald-600 hover:bg-emerald-700 text-white cursor-pointer shadow-lg'">
                        <span x-text="isSubmitting ? 'MEMPROSES...' : 'KIRIM SEMUA PAKET'"></span>
                        <i class="fa-solid" :class="isSubmitting ? 'fa-spinner fa-spin' : 'fa-arrow-right'"></i>
                    </button>

                    <p x-show="!formIsValid" class="text-[10px] text-gray-400 font-bold text-center mt-3 uppercase tracking-widest">* Lengkapi form & pilih semua ongkir koli</p>
                    <p x-show="formIsValid && tipePesanan !== 'cod' && !selectedPayment" class="text-[10px] text-red-500 font-bold text-center mt-3 uppercase tracking-widest">* Wajib pilih metode pembayaran</p>
                </div>

            </div>
        </div>
    </form>

    <!-- ========================================================================= -->
    <!-- MODAL POP-UP PILIH EKSPEDISI PER KOLI -->
    <!-- ========================================================================= -->
    <div x-show="showModal" class="fixed inset-0 z-50 overflow-y-auto" aria-modal="true" x-cloak>
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="showModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm transition-opacity" @click="showModal = false"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>

            <div x-show="showModal" class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl w-full border border-gray-200">
                <div class="bg-white px-6 py-5 border-b border-gray-200 flex items-center justify-between">
                    <div>
                        <h3 class="text-base font-bold text-black uppercase tracking-wide">Pilih Ekspedisi Paket #<span x-text="activePackageIndex + 1"></span></h3>
                        <p class="text-xs text-gray-500 mt-1">Tarif real-time disesuaikan dengan berat paket ini</p>
                    </div>
                    <button type="button" @click="showModal = false" class="text-gray-400 hover:text-black transition">
                        <i class="fa-solid fa-xmark text-lg"></i>
                    </button>
                </div>

                <div class="bg-white px-6 py-3 border-b border-gray-200 flex items-center gap-2 overflow-x-auto custom-scrollbar">
                    <button type="button" @click="filterEkspedisi = 'Semua'" :class="filterEkspedisi === 'Semua' ? 'bg-red-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'" class="px-4 py-1.5 rounded-full text-xs font-bold whitespace-nowrap transition-colors">Semua</button>
                    <button type="button" @click="filterEkspedisi = 'Reguler'" :class="filterEkspedisi === 'Reguler' ? 'bg-red-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'" class="px-4 py-1.5 rounded-full text-xs font-bold whitespace-nowrap transition-colors">Reguler</button>
                    <button type="button" @click="filterEkspedisi = 'Cargo'" :class="filterEkspedisi === 'Cargo' ? 'bg-red-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'" class="px-4 py-1.5 rounded-full text-xs font-bold whitespace-nowrap transition-colors">Cargo</button>
                </div>

                <div class="p-6 max-h-[60vh] overflow-y-auto space-y-3 custom-scrollbar bg-gray-50">
                    <template x-for="(ongkir, index) in ongkirList" :key="index">
                        <div x-show="
                                filterEkspedisi === 'Semua' ||
                                (filterEkspedisi === 'Cargo' && /CARGO|JTR|GOKIL|TRUCK/i.test(ongkir.layanan)) ||
                                (filterEkspedisi === 'Reguler' && !/CARGO|JTR|GOKIL|TRUCK/i.test(ongkir.layanan))
                             "
                             @click="tempSelected = ongkir"
                             class="p-4 border rounded-md cursor-pointer transition-all duration-200 flex flex-col justify-between gap-3 bg-white"
                             :class="tempSelected && tempSelected.kode_layanan === ongkir.kode_layanan ? 'border-black ring-1 ring-black shadow-sm' : 'border-gray-200 hover:border-gray-300'">

                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-4">
                                    <input type="radio" class="w-4 h-4 text-black focus:ring-black border-gray-300 pointer-events-none" :checked="tempSelected && tempSelected.kode_layanan === ongkir.kode_layanan">
                                    <div class="w-12 h-12 rounded border border-gray-100 flex items-center justify-center p-1.5 shrink-0 bg-white shadow-sm">
                                        <template x-if="ongkir.logo_url"><img :src="ongkir.logo_url" class="max-w-full max-h-full object-contain" onerror="this.style.display='none'"></template>
                                        <template x-if="!ongkir.logo_url"><i class="fa-solid fa-truck-fast text-gray-400 text-lg"></i></template>
                                    </div>
                                    <div>
                                        <p class="font-bold text-black text-sm uppercase" x-text="ongkir.kurir"></p>
                                        <p class="text-xs text-gray-500 uppercase mt-1" x-text="ongkir.layanan"></p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <p class="font-bold text-black text-base">Rp <span x-text="ongkir.harga.toLocaleString('id-ID')"></span></p>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>

                <div class="bg-white px-6 py-5 border-t border-gray-200 flex items-center justify-end gap-4">
                    <button type="button" @click="showModal = false" class="px-5 py-2.5 rounded-md text-black font-medium text-sm hover:bg-gray-100 transition border border-transparent">BATAL</button>
                    <button type="button" @click="applySelection()" :disabled="!tempSelected"
                            class="px-6 py-2.5 rounded-md font-medium text-white text-sm transition tracking-widest uppercase flex items-center gap-2"
                            :class="tempSelected ? 'bg-black hover:bg-gray-800 shadow-sm' : 'bg-gray-200 text-gray-400 cursor-not-allowed'">
                        <span>Gunakan Layanan</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- MODAL POP-UP PILIH PEMBAYARAN -->
    <!-- ========================================================================= -->
    <div x-show="showPaymentModal" class="fixed inset-0 z-[120] flex items-center justify-center bg-black/60 backdrop-blur-sm p-4" x-cloak>
        <div x-show="showPaymentModal" @click.away="showPaymentModal = false" class="bg-white rounded-xl shadow-2xl w-full max-w-5xl flex flex-col max-h-[90vh] relative z-10 mx-auto">
            <div class="bg-white px-6 py-5 border-b border-gray-200 flex items-center justify-between rounded-t-xl shrink-0">
                <h3 class="text-base font-bold text-black uppercase tracking-wide">Pilih Metode Pembayaran</h3>
                <button type="button" @click="showPaymentModal = false" class="text-gray-400 hover:text-red-600 bg-gray-100 hover:bg-red-50 p-2 rounded-full transition-colors">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>

            <div class="p-2 overflow-y-auto custom-scrollbar flex-1 bg-gray-50 rounded-b-xl">
                <ul class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3 p-4">
                    <!-- Gunakan array $metodePembayaran dari Controller (tanpa COD di sini karena sudah dipisah ui-nya) -->
                    @foreach($metodePembayaran ?? [] as $bayar)
                        @if(!in_array(strtolower($bayar['id']), ['cod', 'codbarang', 'cod_ongkir', 'cod_barang']))
                            @php
                                $finalIcon = $bayar['icon'] ?? '';
                                if(str_contains(strtolower($bayar['id']), 'dana') && !str_contains(strtolower($bayar['id']), 'tripay')) $finalIcon = 'https://tokosancaka.com/public/assets/dana.png';
                                elseif(str_contains(strtolower($bayar['id']), 'doku')) $finalIcon = 'https://tokosancaka.com/public/assets/doku.png';
                                $isImageLink = str_contains($finalIcon, 'http');
                            @endphp

                            <li @click="selectPayment('{{ $bayar['id'] }}', '{{ $bayar['nama'] }}', '{{ $finalIcon }}')"
                                class="col-span-1 cursor-pointer flex items-center p-3 border rounded-lg transition-all duration-200 bg-white"
                                :class="selectedPayment === '{{ $bayar['id'] }}' ? 'border-black ring-1 ring-black shadow-sm' : 'border-gray-200 hover:border-gray-400 hover:bg-red-50'">

                                <div class="w-12 h-12 rounded bg-white flex items-center justify-center shrink-0 p-1.5 border border-gray-100 shadow-sm mr-4">
                                    @if($isImageLink)
                                        <img src="{{ $finalIcon }}" class="w-full h-full object-contain">
                                    @else
                                        <i class="{{ $finalIcon }} text-2xl"></i>
                                    @endif
                                </div>
                                <div class="flex flex-col">
                                    <span class="text-sm font-bold text-gray-900 uppercase">{{ $bayar['nama'] }}</span>
                                    <span class="text-[11px] text-gray-500 uppercase mt-0.5">{{ $bayar['deskripsi'] }}</span>
                                </div>
                            </li>
                        @endif
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- ========================================== -->
<!-- SCRIPTS ENGINE LOGIC (COMPATIBLE MODE) -->
<!-- ========================================== -->
<script>
function orderFormData() {
    return {
        // Data Global Koli
        kategoriBarang: '',
        tipePesanan: 'reguler',
        deskripsiBarang: '',
        nilaiBarang: '',
        displayNilaiBarang: '',
        asuransi: 'tidak',
        jenisCod: 'CODBARANG',
        isSenderPp: 1,

        simpanPengirim: false,
        simpanPenerima: false,
        isSubmitting: false,
        isGeneratingPickup: false,
        formIsValid: false,

        // Daftar Paket / Koli
        packages: [],
        activePackageIndex: null,

        // Kontak Pengirim
        pengirimNama: @json(old('sender_name', '')),
        senderQuery: '',
        senderDistrictId: '',
        senderSubdistrictId: '',
        senderPostalCode: '',
        senderProvince: '',
        senderRegency: '',
        senderDistrict: '',
        senderVillage: '',
        contactResultsSender: [],
        showContactSender: false,
        isSearchingContactSender: false,
        showSenderDropdown: false,
        isSearchingSender: false,
        senderResults: [],
        pickupPointCode: '{{ auth()->user()->pickup_point_code ?? '' }}',

        // Kontak Penerima
        penerimaNama: @json(old('receiver_name', '')),
        receiverQuery: '',
        receiverDistrictId: '',
        receiverSubdistrictId: '',
        receiverPostalCode: '',
        receiverProvince: '',
        receiverRegency: '',
        receiverDistrict: '',
        receiverVillage: '',
        contactResultsReceiver: [],
        showContactReceiver: false,
        isSearchingContactReceiver: false,
        showReceiverDropdown: false,
        isSearchingReceiver: false,
        receiverResults: [],

        // Modal Ongkir
        showModal: false,
        filterEkspedisi: 'Semua',
        ongkirList: [],
        tempSelected: null,

        // Modal Payment
        showPaymentModal: false,
        selectedPayment: '',
        selectedPaymentName: '',
        selectedPaymentIcon: '',

        init() {
            this.addPackage();
            this.$watch('packages', () => this.checkFormValidity(), { deep: true });
        },

        addPackage() {
            this.packages.push({
                id: Date.now(),
                berat: '1000', panjang: '', lebar: '', tinggi: '',
                selectedOngkir: 0,
                selectedKurir: '',
                selectedLayanan: '',
                selectedServiceCode: '',
                selectedLogoUrl: '',
                selectedEtd: '',
                selectedInsuranceRate: 0,
                selectedCodRate: 0,
                isLoading: false
            });
        },

        removePackage(index) {
            this.packages.splice(index, 1);
            this.checkFormValidity();
        },

        resetSemuaOngkir() {
            this.packages.forEach(p => { p.selectedOngkir = 0; });
        },

        checkFormValidity() {
            if (this.$refs.orderForm) {
                let allPriced = this.packages.length > 0 && this.packages.every(p => p.selectedOngkir > 0);
                this.formIsValid = this.$refs.orderForm.checkValidity() && allPriced;
            }
        },

        get totalOngkirDasar() {
            return this.packages.reduce((sum, p) => sum + (parseInt(p.selectedOngkir) || 0), 0);
        },

        get biayaAsuransi() {
            if (this.asuransi !== 'iya' || !this.nilaiBarang) return 0;
            let rate = this.packages.length > 0 ? (parseFloat(this.packages[0].selectedInsuranceRate) || 0) : 0;
            return Math.round((parseInt(this.nilaiBarang) || 0) * rate);
        },

        get biayaCod() {
            if (this.tipePesanan !== 'cod') return 0;
            let rate = this.packages.length > 0 ? (parseFloat(this.packages[0].selectedCodRate) || 0) : 0;
            if (rate === 0) return 0;

            let baseCod = this.totalOngkirDasar;
            if (this.jenisCod === 'CODBARANG') {
                baseCod += (parseInt(this.nilaiBarang) || 0);
            }

            let fee = baseCod * rate;
            let minFee = 1500;
            if (this.packages.length > 0 && this.packages[0].selectedKurir.toUpperCase().includes('SICEPAT')) minFee = 2000;
            if (fee > 0 && fee < minFee) fee = minFee;

            return Math.round(fee);
        },

        get grandTotalPotongan() {
            return this.totalOngkirDasar + this.biayaAsuransi;
        },

        get grandTotalCod() {
            let tagihan = this.totalOngkirDasar + this.biayaAsuransi + this.biayaCod;
            if (this.jenisCod === 'CODBARANG') tagihan += (parseInt(this.nilaiBarang) || 0);
            return tagihan;
        },

        get isFormSubmitReady() {
            if (!this.formIsValid) return false;
            if (this.isSubmitting) return false;
            if (this.tipePesanan !== 'cod') {
                if (!this.selectedPayment) return false;
                if (this.selectedPayment === 'potong_saldo' && this.grandTotalPotongan > {{ auth()->user()->saldo ?? 0 }}) return false;
            }
            return true;
        },

        async searchContact(type) {
            let query = type === 'sender' ? this.pengirimNama : this.penerimaNama;
            if (query.length < 2) {
                if (type === 'sender') { this.contactResultsSender = []; this.showContactSender = false; }
                else { this.contactResultsReceiver = []; this.showContactReceiver = false; }
                return;
            }

            if (type === 'sender') { this.isSearchingContactSender = true; this.showContactSender = true; }
            else { this.isSearchingContactReceiver = true; this.showContactReceiver = true; }

            try {
                let response = await fetch(`/api/search-kontak?q=${encodeURIComponent(query)}`);
                let data = await response.json();
                if (type === 'sender') this.contactResultsSender = data;
                else this.contactResultsReceiver = data;
            } catch (error) { console.error(error); }
            finally {
                if (type === 'sender') this.isSearchingContactSender = false;
                else this.isSearchingContactReceiver = false;
            }
        },

        selectContact(type, kontak) {
            if (type === 'sender') {
                this.pengirimNama = kontak.nama || '';
                document.getElementById('pengirim_hp').value = kontak.no_hp || '';

                // 1. Auto-fill Alamat Jalan Pengirim
                if (kontak.alamat) {
                    document.getElementById('pengirim_alamat').value = kontak.alamat;
                }

                // 2. Auto-fill Wilayah Pengirim (Kecamatan/Kota/Provinsi) jika ada di database
                if (kontak.district_id) {
                    this.senderDistrictId = kontak.district_id;
                    this.senderPostalCode = kontak.postal_code || '';
                    this.senderSubdistrictId = kontak.subdistrict_id || '';
                    this.senderProvince = kontak.province || '';
                    this.senderRegency = kontak.regency || '';
                    this.senderDistrict = kontak.district || '';
                    this.senderVillage = kontak.village || '';

                    let display = [];
                    if(kontak.village) display.push(kontak.village);
                    if(kontak.district) display.push(kontak.district);
                    if(kontak.regency) display.push(kontak.regency);
                    if(kontak.province) display.push(kontak.province);
                    if(kontak.postal_code) display.push(kontak.postal_code);

                    if (display.length > 0) {
                        this.senderQuery = display.join(', ').toUpperCase();
                    }
                }

                this.pickupPointCode = kontak.pickup_point_code || '';
                this.showContactSender = false;

                // Trigger auto-pickup generation setelah data terisi
                setTimeout(() => this.autoGeneratePickup(), 300);

            } else {
                this.penerimaNama = kontak.nama || '';
                document.getElementById('penerima_hp').value = kontak.no_hp || '';

                // 1. Auto-fill Alamat Jalan Penerima
                if (kontak.alamat) {
                    document.getElementById('penerima_alamat').value = kontak.alamat;
                }

                // 2. Auto-fill Wilayah Penerima jika ada di database
                if (kontak.district_id) {
                    this.receiverDistrictId = kontak.district_id;
                    this.receiverPostalCode = kontak.postal_code || '';
                    this.receiverSubdistrictId = kontak.subdistrict_id || '';
                    this.receiverProvince = kontak.province || '';
                    this.receiverRegency = kontak.regency || '';
                    this.receiverDistrict = kontak.district || '';
                    this.receiverVillage = kontak.village || '';

                    let display = [];
                    if(kontak.village) display.push(kontak.village);
                    if(kontak.district) display.push(kontak.district);
                    if(kontak.regency) display.push(kontak.regency);
                    if(kontak.province) display.push(kontak.province);
                    if(kontak.postal_code) display.push(kontak.postal_code);

                    if (display.length > 0) {
                        this.receiverQuery = display.join(', ').toUpperCase();
                    }
                }

                this.showContactReceiver = false;
            }
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
                if(type === 'sender') this.senderResults = data;
                else this.receiverResults = data;
            } catch (error) { console.error(error); }
            finally {
                if (type === 'sender') this.isSearchingSender = false;
                else this.isSearchingReceiver = false;
            }
        },

        selectAddress(type, res) {
            let parts = res.full_address ? res.full_address.split(',').map(s => s.trim()) : [];
            let vDesa="", vKec="", vKota="", vProv="";
            if(parts.length >= 4) { vDesa=parts[0]; vKec=parts[1]; vKota=parts[2]; vProv=parts[3]; }

            if(type === 'sender') {
                this.senderQuery = res.full_address_display;
                this.senderDistrictId = res.district_id;
                this.senderPostalCode = res.postal_code;
                this.senderSubdistrictId = res.subdistrict_id || '';
                this.senderVillage = vDesa; this.senderDistrict = vKec; this.senderRegency = vKota; this.senderProvince = vProv;
                this.showSenderDropdown = false;
                setTimeout(() => this.autoGeneratePickup(), 200);
            } else {
                this.receiverQuery = res.full_address_display;
                this.receiverDistrictId = res.district_id;
                this.receiverPostalCode = res.postal_code;
                this.receiverSubdistrictId = res.subdistrict_id || '';
                this.receiverVillage = vDesa; this.receiverDistrict = vKec; this.receiverRegency = vKota; this.receiverProvince = vProv;
                this.showReceiverDropdown = false;
            }
        },

        async autoGeneratePickup() {
            let hp = document.getElementById('pengirim_hp').value;
            let alamat = document.getElementById('pengirim_alamat').value;
            if (this.pengirimNama.length >= 2 && hp.length >= 9 && this.senderDistrictId && alamat.length >= 15) {
                this.isGeneratingPickup = true;
                try {
                    let fd = new FormData();
                    fd.append('pengirim_nama', this.pengirimNama);
                    fd.append('pengirim_hp', hp);
                    fd.append('pengirim_alamat', alamat);
                    fd.append('pengirim_district_id', this.senderDistrictId);
                    fd.append('_token', document.querySelector('input[name="_token"]').value);

                    let res = await fetch("{{ route('admin.pesanan-autokirim.ajax-pickup') }}", { method: 'POST', body: fd });
                    let data = await res.json();
                    if(data.success) this.pickupPointCode = data.pickup_point_code;
                } catch(e) { console.error(e); }
                finally { this.isGeneratingPickup = false; }
            }
        },

        selectPayment(id, name, icon) {
            this.selectedPayment = id;
            this.selectedPaymentName = name;
            this.selectedPaymentIcon = icon;
            this.showPaymentModal = false;
        },

        async cekOngkir(index) {
            let pkt = this.packages[index];
            if(!this.senderDistrictId || !this.receiverDistrictId || !pkt.berat) {
                alert("Mohon lengkapi alamat wilayah Pengirim, Penerima, dan Berat Paket Koli!");
                return;
            }

            if (this.tipePesanan === 'cod') {
                let harga = parseInt(this.nilaiBarang) || 0;
                if (harga < 10000 || harga > 5000000) {
                    alert("COD Ditolak!\nHarga paket untuk COD minimal Rp 10.000 dan maksimal Rp 5.000.000 (Pastikan sudah diisi pada Pengaturan Umum).");
                    return;
                }
            }

            this.activePackageIndex = index;
            pkt.isLoading = true;
            this.ongkirList = [];
            this.tempSelected = null;

            try {
                let fd = new FormData();
                fd.append('origin_id', this.senderDistrictId);
                fd.append('destination_id', this.receiverDistrictId);
                fd.append('berat_gram', pkt.berat);
                fd.append('qty', 1);
                fd.append('is_sender_pp', this.isSenderPp);
                fd.append('panjang_cm', pkt.panjang || 10);
                fd.append('lebar_cm', pkt.lebar || 10);
                fd.append('tinggi_cm', pkt.tinggi || 10);
                fd.append('kategori_barang', this.kategoriBarang || 'OTH001');

                fd.append('pengirim_nama', this.pengirimNama || 'Sancaka');
                fd.append('pengirim_hp', document.getElementById('pengirim_hp').value || '08000000');
                fd.append('pengirim_alamat', document.getElementById('pengirim_alamat').value || 'Sancaka Express');
                fd.append('_token', document.querySelector('input[name="_token"]').value);

                let response = await fetch(`/api/autokirim/cek-ongkir`, { method: 'POST', body: fd });
                let result = await response.json();

                if(result.success) {
                    this.ongkirList = result.data.sort((a, b) => a.harga - b.harga);
                    this.filterEkspedisi = 'Semua';
                    this.showModal = true;
                } else {
                    alert("Gagal: " + result.message);
                }
            } catch (error) {
                alert("Gangguan jaringan saat memuat tarif.");
            } finally {
                pkt.isLoading = false;
            }
        },

        applySelection() {
            if(!this.tempSelected) return;
            let pkt = this.packages[this.activePackageIndex];

            pkt.selectedKurir = this.tempSelected.kurir;
            pkt.selectedLayanan = this.tempSelected.layanan;
            pkt.selectedOngkir = this.tempSelected.harga;
            pkt.selectedServiceCode = this.tempSelected.kode_layanan;
            pkt.selectedLogoUrl = this.tempSelected.logo_url;
            pkt.selectedEtd = this.tempSelected.etd;
            pkt.selectedInsuranceRate = this.tempSelected.asuransi_rate || 0;
            pkt.selectedCodRate = this.tempSelected.fee_cod || 0;

            this.showModal = false;
            setTimeout(() => this.checkFormValidity(), 100);
        },

        validateForm(e) {
            let allPriced = this.packages.every(p => p.selectedOngkir > 0);
            if(this.packages.length === 0 || !allPriced) {
                e.preventDefault();
                alert("Semua paket (Koli) harus dipilihkan ekspedisinya!");
                return;
            }

            if(this.tipePesanan === 'cod') {
                if(this.grandTotalCod < 10000) { e.preventDefault(); alert("Total COD kurang dari Rp10.000"); return; }
            } else {
                if(!this.selectedPayment) { e.preventDefault(); alert("Pilih metode pembayaran!"); return; }
                if(this.selectedPayment === 'potong_saldo' && this.grandTotalPotongan > {{ auth()->user()->saldo ?? 0 }}) {
                    e.preventDefault(); alert("Saldo tidak mencukupi!"); return;
                }
            }

            if (this.isSubmitting) { e.preventDefault(); return; }
            this.isSubmitting = true;
        }
    };
}
</script>

<style>
/* Grayscale Scrollbar untuk Alpine UI */
.custom-scrollbar::-webkit-scrollbar { width: 4px; }
.custom-scrollbar::-webkit-scrollbar-track { background: #fafafa; border-radius: 8px; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #d4d4d8; border-radius: 8px; }
.custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #a1a1aa; }
</style>
@endsection
