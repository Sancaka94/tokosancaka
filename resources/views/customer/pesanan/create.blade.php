{{-- resources/views/customer/pesanan/create.blade.php --}}



@extends('layouts.customer')



@section('title', 'Buat Pesanan Baru')

@section('page-title', 'Buat Pesanan Baru')



@push('styles')

<style>

    /* Style kustom untuk scrollbar di hasil pencarian agar lebih ramping */

    .search-result-container::-webkit-scrollbar {

        width: 5px;

    }

    .search-result-container::-webkit-scrollbar-track {

        background: #f1f5f9;

    }

    .search-result-container::-webkit-scrollbar-thumb {

        background: #94a3b8;

        border-radius: 10px;

    }

</style>

@endpush



@section('content')

<div class="max-w-7xl mx-auto">

    <form id="orderForm" x-data="{ paymentMethod: '{{ old('payment_method', 'Potong Saldo') }}' }" action="{{ route('customer.pesanan.store') }}" method="POST"> 

        @csrf

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

            

            <!-- Kolom Kiri: Pengirim & Penerima -->

            <div class="lg:col-span-2 space-y-8">



    <!-- Informasi Pengirim -->

            <div class="bg-white p-6 rounded-lg shadow-md">

                <div class="flex justify-between items-center border-b pb-4 mb-6">

                    <h3 class="text-xl font-semibold text-gray-800">Informasi Pengirim</h3>

                    <div id="search_sender_container" class="relative w-1/2">

                        <input type="search" id="search_sender" class="w-full pl-10 pr-4 py-2 border rounded-lg text-sm" placeholder="Cari dari kontak...">

                        <div class="absolute top-0 left-0 inline-flex items-center p-2 h-full text-gray-400">

                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">

                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>

                            </svg>

                        </div>

                        <div id="sender_results" class="search-result-container absolute z-10 w-full bg-white border rounded-b-lg mt-1 hidden shadow-lg max-h-48 overflow-y-auto"></div>

                    </div>

                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                    <div>

                        <label for="sender_name" class="block mb-2 text-sm font-medium text-gray-700">Nama Pengirim</label>

                        <input type="text" id="sender_name" name="sender_name" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5"

                               value="{{ old('sender_name', auth()->user()->nama_lengkap) }}" required>

                    </div>

                    <div>

                        <label for="sender_phone" class="block mb-2 text-sm font-medium text-gray-700">Nomor HP</label>

                        <input type="tel" id="sender_phone" name="sender_phone" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5"

                               value="{{ old('sender_phone', auth()->user()->no_wa) }}" required>

                    </div>

                    <div>

                        <label for="sender_province" class="block mb-2 text-sm font-medium text-gray-700">Provinsi</label>

                        <input type="text" id="sender_province" name="sender_province" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5" value="{{ old('sender_phone', auth()->user()->province) }}" required>

                    </div>

                    <div>

                        <label for="sender_regency" class="block mb-2 text-sm font-medium text-gray-700">Kabupaten/Kota</label>

                        <input type="text" id="sender_regency" name="sender_regency" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5" value="{{ old('sender_phone', auth()->user()->regency) }}" required>

                    </div>

                    <div>

                        <label for="sender_district" class="block mb-2 text-sm font-medium text-gray-700">Kecamatan</label>

                        <input type="text" id="sender_district" name="sender_district" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5" value="{{ old('sender_phone', auth()->user()->district) }}" required>

                    </div>

                    <div>

                        <label for="sender_village" class="block mb-2 text-sm font-medium text-gray-700">Desa/Kelurahan</label>

                        <input type="text" id="sender_village" name="sender_village" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5" value="{{ old('sender_phone', auth()->user()->village) }}" required>

                    </div>

                    <div>

                        <label for="sender_postal_code" class="block mb-2 text-sm font-medium text-gray-700">Kode Pos</label>

                        <input type="text" id="sender_postal_code" name="sender_postal_code" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5" value="{{ old('sender_phone', auth()->user()->postal_code) }}" required>

                    </div>

                    <div class="md:col-span-2">

                        <label for="sender_address" class="block mb-2 text-sm font-medium text-gray-700">Alamat Lengkap</label>

                        <textarea id="sender_address" name="sender_address" rows="3" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5" required>{{ old('sender_address', auth()->user()->address_detail) }}</textarea>

                    </div>

                    <div class="md:col-span-2">

                        <label class="flex items-center text-sm text-gray-600">

                            <input type="checkbox" name="save_sender" class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 mr-2"> Simpan/Perbarui data pengirim ini

                        </label>

                    </div>

                </div>

            </div>

        

            <!-- Informasi Penerima -->

            <div class="bg-white p-6 rounded-lg shadow-md">

                <div class="flex justify-between items-center border-b pb-4 mb-6">

                    <h3 class="text-xl font-semibold text-gray-800">Informasi Penerima</h3>

                    <div id="search_receiver_container" class="relative w-1/2">

                        <input type="search" id="search_receiver" class="w-full pl-10 pr-4 py-2 border rounded-lg text-sm" placeholder="Cari dari kontak...">

                        <div class="absolute top-0 left-0 inline-flex items-center p-2 h-full text-gray-400">

                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">

                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>

                            </svg>

                        </div>

                        <div id="receiver_results" class="search-result-container absolute z-10 w-full bg-white border rounded-b-lg mt-1 hidden shadow-lg max-h-48 overflow-y-auto"></div>

                    </div>

                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                    <div>

                        <label for="receiver_name" class="block mb-2 text-sm font-medium text-gray-700">Nama Penerima</label>

                        <input type="text" id="receiver_name" name="receiver_name" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5" required>

                    </div>

                    <div>

                        <label for="receiver_phone" class="block mb-2 text-sm font-medium text-gray-700">Nomor HP</label>

                        <input type="tel" id="receiver_phone" name="receiver_phone" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5" required>

                    </div>

                    <div>

                        <label for="receiver_province" class="block mb-2 text-sm font-medium text-gray-700">Provinsi</label>

                        <input type="text" id="receiver_province" name="receiver_province" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5" required>

                    </div>

                    <div>

                        <label for="receiver_regency" class="block mb-2 text-sm font-medium text-gray-700">Kabupaten/Kota</label>

                        <input type="text" id="receiver_regency" name="receiver_regency" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5" required>

                    </div>

                    <div>

                        <label for="receiver_district" class="block mb-2 text-sm font-medium text-gray-700">Kecamatan</label>

                        <input type="text" id="receiver_district" name="receiver_district" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5" required>

                    </div>

                    <div>

                        <label for="receiver_village" class="block mb-2 text-sm font-medium text-gray-700">Desa/Kelurahan</label>

                        <input type="text" id="receiver_village" name="receiver_village" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5" required>

                    </div>

                    <div>

                        <label for="receiver_postal_code" class="block mb-2 text-sm font-medium text-gray-700">Kode Pos</label>

                        <input type="text" id="receiver_postal_code" name="receiver_postal_code" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5" required>

                    </div>

                    <div class="md:col-span-2">

                        <label for="receiver_address" class="block mb-2 text-sm font-medium text-gray-700">Alamat Lengkap</label>

                        <textarea id="receiver_address" name="receiver_address" rows="3" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5" required></textarea>

                    </div>

                    <div class="md:col-span-2">

                        <label class="flex items-center text-sm text-gray-600">

                            <input type="checkbox" name="save_receiver" class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 mr-2"> Simpan data penerima ini

                        </label>

                    </div>

                </div>

            </div>

        </div>





            <!-- Kolom Kanan: Detail Paket & Pembayaran -->

            <div class="lg:col-span-1 space-y-8">

                <!-- Detail Paket -->

                <div class="bg-white p-6 rounded-lg shadow-md">

                    <h3 class="text-xl font-semibold text-gray-800 border-b pb-4 mb-6">Detail Paket</h3>

                    <div class="mt-4">

                        <label for="item_description" class="block mb-2 text-sm font-medium text-gray-700">Deskripsi Barang</label>

                        <input type="text" id="item_description" name="item_description" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5" required>

                    </div>

                    <div class="col-12">

                        <label class="block mb-2 text-sm font-medium text-gray-700">Harga Barang</label>

                        <input type="number" name="item_price" id="item_price" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5" required>

                    </div>

                    <div class="mt-4">

                        <label for="weight" class="block mb-2 text-sm font-medium text-gray-700">Berat (gram)</label>

                        <input type="number" id="weight" name="weight" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5" required>

                    </div>

                    <div class="grid grid-cols-3 gap-4 mt-4">

                        <div><label for="length" class="block mb-2 text-sm font-medium text-gray-700">P (cm)</label><input type="number" id="length" name="length" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5"></div>

                        <div><label for="width" class="block mb-2 text-sm font-medium text-gray-700">L (cm)</label><input type="number" id="width" name="width" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5"></div>

                        <div><label for="height" class="block mb-2 text-sm font-medium text-gray-700">T (cm)</label><input type="number" id="height" name="height" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5"></div>

                    </div>

                    <div class="col-12 mb-2">

                            <label class="block mb-2 text-sm font-medium text-gray-700">Jenis Barang</label>

                            <select name="item_type" id="item_type" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5">

                                <option value="">Pilih Jenis Barang</option>

                                <option value="1">Peralatan Elektronik & Gadget</option>

                                <option value="2">Pakaian</option>

                                <option value="3">Pecah Belah</option>

                                <option value="4">Dokumen</option>

                                <option value="5">Peralatan Rumah Tangga</option>

                                <option value="6">Aksesoris</option>

                                <option value="7">Lain-lain</option>

                                <option value="8">Dokumen Berharga</option>

                                <option value="9">Peralatan Kesehatan & Kecantikan</option>

                                <option value="10">Peralatan Olahraga & Hiburan</option>

                                <option value="11">Perlengkapan Mobil & Motor</option>

                            </select>

                        </div>

                    <div>

                        <label for="service_type" class="block mb-2 text-sm font-medium text-gray-700">Jenis Layanan</label>

                        <select id="service_type" name="service_type" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5">

                            <option value="">Pilih Jenis Layanan</option>

                            <option value="express">Express</option>

                            <option value="instant">Instant</option>

                        </select>

                    </div>

                    <div>

                        <label for="ansuransi" class="block mb-2 text-sm font-medium text-gray-700">Ansuransi</label>

                        <select id="ansuransi" name="ansuransi" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5">

                            <option value="">Pilih Ansuransi</option>

                            <option value="iya">Iya</option>

                            <option value="tidak">Tidak</option>

                        </select>

                    </div>

                   

                      <div class="col-span-12">

                            <label class="block mb-2 text-sm font-medium text-gray-700">Pilih Ekspedisi</label>

                            <input 

                                type="text" 

                                id="selected_expedition_display" 

                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500" 

                                placeholder="Pilih Jenis Layanan Dahulu..." 

                                readonly

                            >

                            <input type="hidden" name="expedition" id="expedition" required>

                        </div>

                        

                        <div class="col-span-12 mt-4">

                            <label class="block mb-2 text-sm font-medium text-gray-700">Metode Pembayaran</label>

                            <div 

                                id="paymentMethodButton" 

                                class="w-full px-3 py-2 border border-gray-300 rounded-md flex justify-between items-center cursor-pointer hover:ring-2 hover:ring-blue-500"

                            >

                                <div class="flex items-center">

                                    <img 

                                        id="selectedPaymentLogo" 

                                        src="https://placehold.co/32x32/EFEFEF/333333?text=?" 

                                        alt="Logo Pembayaran" 

                                        class="w-8 h-8 mr-2 rounded"

                                    >

                                    <span id="selectedPaymentName" class="text-gray-700">Pilih Pembayaran...</span>

                                </div>

                                <i class="fas fa-chevron-down text-gray-400"></i>

                            </div>

                            <input type="hidden" name="payment_method" id="payment_method" required>

                        </div>

                </div>

                

                <!-- Aksi -->

                <div class="bg-white p-6 rounded-lg shadow-md">

                    <div class="space-y-4">

                        <button type="button" id="confirmBtn" class="w-full text-white bg-indigo-600 hover:bg-indigo-700 font-medium rounded-lg text-sm px-5 py-3 text-center">

                            Lanjutkan ke Rincian

                        </button>

                    </div>

                </div>

            </div>

        </div>

    </form>

</div>



<!-- Modal Konfirmasi Pesanan -->

<div id="confirmationModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 z-50 hidden flex items-center justify-center">

    <div class="p-5 border w-full max-w-lg shadow-lg rounded-md bg-white">

        <div class="mt-3">

            <h3 class="text-xl leading-6 font-medium text-gray-900 text-center">Konfirmasi Rincian Pesanan</h3>

            <div class="mt-4 px-7 py-3 bg-gray-50 rounded-lg">

                <h4 class="font-semibold text-gray-700 mb-2">Rincian Berat & Biaya</h4>

                <div id="modal_cost_summary" class="space-y-2 text-sm text-gray-600">

                    {{-- Rincian akan diisi oleh JavaScript --}}

                </div>

            </div>

            <div class="mt-4 text-center text-sm text-gray-500">

                <p>Pastikan semua data sudah benar sebelum melanjutkan.</p>

            </div>

            <div class="items-center px-4 py-3 mt-4 flex justify-end gap-3 bg-gray-50 rounded-b-md">

                <button id="closeModalBtn" type="button" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300">

                    Batal

                </button>

                <button id="cekOngkirBtn" type="button" class="inline-flex items-center justify-center px-4 py-2 bg-green-500 hover:bg-green-600 text-white rounded-md">

                    <i class="fab fa-whatsapp w-5 h-5 mr-2"></i>

                    Cek Ongkir WA

                </button>

                <button id="submitOrderBtn" type="button" class="inline-flex items-center justify-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-md">

                    <i class="fas fa-paper-plane w-5 h-5 mr-2"></i>

                    Simpan Data

                </button>

            </div>

        </div>

    </div>

</div>



<div id="ongkirModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">

    <!-- Modal Content -->

    <div class="bg-white rounded-lg shadow-lg w-full max-w-2xl mx-4 max-h-[90vh] flex flex-col overflow-hidden">

        <!-- Header -->

        <div class="flex justify-between items-center p-4 border-b">

            <h3 class="text-lg font-semibold flex items-center gap-2">

                <i class="fas fa-shipping-fast"></i> Pilihan Ekspedisi

            </h3>

            <button class="text-gray-500 hover:text-gray-700" onclick="closeModalss('ongkirModal')">

                &times;

            </button>

        </div>



        <!-- Body -->

        <div id="ongkirModalBody" class="p-5 text-center overflow-y-auto">

            <div class="flex flex-col items-center">

                <svg class="animate-spin h-10 w-10 text-red-500 mb-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">

                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>

                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>

                </svg>

                <p class="text-gray-500 mt-2">Memuat tarif pengiriman...</p>

            </div>

        </div>



        <!-- Footer -->

        <div class="p-4 border-t text-right">

            <button class="px-4 py-2 bg-gray-200 text-gray-700 rounded hover:bg-gray-300" onclick="closeModalss('ongkirModal')">Tutup</button>

        </div>

    </div>

</div>





{{-- MODAL METODE PEMBAYARAN --}}

<div id="paymentMethodModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">

    <div class="bg-white rounded-lg shadow-lg w-full max-w-2xl mx-4 overflow-hidden">

        <!-- Header -->

        <div class="flex justify-between items-center p-4 border-b">

            <h3 class="text-lg font-semibold flex items-center gap-2">

                <i class="fas fa-credit-card"></i> Pilih Metode Pembayaran

            </h3>

            <button class="text-gray-500 hover:text-gray-700" onclick="closeModalss('paymentMethodModal')">&times;</button>

        </div>



        <!-- Body -->

        <div class="p-4 max-h-[400px] overflow-y-auto">

            <ul class="flex flex-col gap-2 max-h-[400px] overflow-y-auto">

    <li class="flex items-center gap-3 p-3 border rounded hover:bg-gray-100 cursor-pointer"

        data-value="Potong Saldo" onclick="selectPayment(this)">

        <img src="https://uxwing.com/wp-content/themes/uxwing/download/banking-finance/cash-icon.png" class="w-8 h-8 rounded">

        <span>

            Potong Saldo (Tersedia Rp {{ number_format(Auth::user()->saldo, 0, ',', '.') }})

        </span>

    </li>

    <li class="flex items-center gap-3 p-3 border rounded hover:bg-gray-100 cursor-pointer"

        data-value="cash" onclick="selectPayment(this)">

        <img src="https://uxwing.com/wp-content/themes/uxwing/download/banking-finance/cash-icon.png" class="w-8 h-8 rounded">

        <span>

            CASH

        </span>

    </li>

     <li class="flex items-center gap-3 p-3 border rounded hover:bg-gray-100 cursor-pointer"

        data-value="CODBARANG" onclick="selectPayment(this)">

        <img src="{{ asset('public/assets/cod.png') }}" class="w-8 h-8 rounded">

        <span>COD BARANG (Bayar di Tempat)</span>

    </li>

    <li class="flex items-center gap-3 p-3 border rounded hover:bg-gray-100 cursor-pointer"

        data-value="COD" onclick="selectPayment(this)">

        <img src="{{ asset('public/assets/cod.png') }}" class="w-8 h-8 rounded">

        <span>COD (Bayar di Tempat)</span>

    </li>

    <li class="flex items-center gap-3 p-3 border rounded hover:bg-gray-100 cursor-pointer"

        data-value="PERMATAVA" onclick="selectPayment(this)">

        <img src="{{ asset('public/assets/permata.webp') }}" class="w-8 h-8 rounded">

        <span>Permata Virtual Account</span>

    </li>

    <li class="flex items-center gap-3 p-3 border rounded hover:bg-gray-100 cursor-pointer"

        data-value="BNIVA" onclick="selectPayment(this)">

        <img src="{{ asset('public/assets/bni.webp') }}" class="w-8 h-8 rounded">

        <span>BNI Virtual Account</span>

    </li>

    <li class="flex items-center gap-3 p-3 border rounded hover:bg-gray-100 cursor-pointer"

        data-value="BRIVA" onclick="selectPayment(this)">

        <img src="{{ asset('public/assets/bri.webp') }}" class="w-8 h-8 rounded">

        <span>BRI Virtual Account</span>

    </li>

    <li class="flex items-center gap-3 p-3 border rounded hover:bg-gray-100 cursor-pointer"

        data-value="MANDIRIVA" onclick="selectPayment(this)">

        <img src="{{ asset('public/assets/mandiri.webp') }}" class="w-8 h-8 rounded">

        <span>Mandiri Virtual Account</span>

    </li>

    <li class="flex items-center gap-3 p-3 border rounded hover:bg-gray-100 cursor-pointer"

        data-value="BCAVA" onclick="selectPayment(this)">

        <img src="{{ asset('public/assets/bca.webp') }}" class="w-8 h-8 rounded">

        <span>BCA Virtual Account</span>

    </li>

    <li class="flex items-center gap-3 p-3 border rounded hover:bg-gray-100 cursor-pointer"

        data-value="ALFAMART" onclick="selectPayment(this)">

        <img src="{{ asset('public/assets/alfamart.webp') }}" class="w-8 h-8 rounded">

        <span>Alfamart</span>

    </li>

    <li class="flex items-center gap-3 p-3 border rounded hover:bg-gray-100 cursor-pointer"

        data-value="INDOMARET" onclick="selectPayment(this)">

        <img src="{{ asset('public/assets/indomaret.webp') }}" class="w-8 h-8 rounded">

        <span>Indomaret</span>

    </li>

    <li class="flex items-center gap-3 p-3 border rounded hover:bg-gray-100 cursor-pointer"

        data-value="OVO" onclick="selectPayment(this)">

        <img src="{{ asset('public/assets/ovo.webp') }}" class="w-8 h-8 rounded">

        <span>OVO</span>

    </li>

    <li class="flex items-center gap-3 p-3 border rounded hover:bg-gray-100 cursor-pointer"

        data-value="DANA" onclick="selectPayment(this)">

        <img src="{{ asset('public/assets/dana.webp') }}" class="w-8 h-8 rounded">

        <span>DANA</span>

    </li>

    <li class="flex items-center gap-3 p-3 border rounded hover:bg-gray-100 cursor-pointer"

        data-value="SHOPEEPAY" onclick="selectPayment(this)">

        <img src="{{ asset('public/assets/shopeepay.webp') }}" class="w-8 h-8 rounded">

        <span>ShopeePay</span>

    </li>

    <li class="flex items-center gap-3 p-3 border rounded hover:bg-gray-100 cursor-pointer"

        data-value="QRIS" onclick="selectPayment(this)">

        <img src="{{ asset('public/assets/qris2.png') }}" class="w-8 h-8 rounded">

        <span>QRIS</span>

    </li>

</ul>



        </div>



        <!-- Footer -->

        <div class="p-4 border-t text-right">

            <button class="px-4 py-2 bg-gray-200 text-gray-700 rounded hover:bg-gray-300" onclick="closeModalss('paymentMethodModal')">Tutup</button>

        </div>

    </div>

</div>



@endsection



@push('scripts')

 <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

 <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

 <script>

    function openModalss(id) {

        document.getElementById(id).classList.remove('hidden');

    }



    function closeModalss(id) {

        document.getElementById(id).classList.add('hidden');

    }



    document.getElementById('paymentMethodButton').addEventListener('click', function() {

        openModalss('paymentMethodModal');

    });



    function selectPayment(el) {

        const name = el.querySelector('span').innerText;

        const logo = el.querySelector('img').src;

        const value = el.getAttribute('data-value');



        document.getElementById('selectedPaymentName').innerText = name;

        document.getElementById('selectedPaymentLogo').src = logo;

        document.getElementById('payment_method').value = value;



        closeModalss('paymentMethodModal');

    }

    

   function runCekOngkir() {

    let serviceType = $('#service_type').val();

    let weight = parseInt($('#weight').val()) || 0;

    let price = parseInt($('#item_price').val()) || 0;

    let length = parseInt($('#length').val()) || 0;

    let width = parseInt($('#width').val()) || 0;

    let height = parseInt($('#height').val()) || 0;

    let receiver_village = $('#receiver_village').val();

    let ansuransi = $('#ansuransi').val();



    if (weight <= 0 || price <= 0 || !serviceType || !receiver_village) {

        Swal.fire({

            icon: 'warning',

            title: 'Data Belum Lengkap',

            text: 'Harap lengkapi informasi Pengirim, Penerima, Harga, Berat, dan Jenis Layanan terlebih dahulu.'

        });

        return;

    }



    openModalss('ongkirModal');



    $.ajax({

        url: "{{ route('kirimaja.cekongkir') }}",

        type: "GET",

        data: {

            sender_province: $('#sender_province').val(),

            sender_regency: $('#sender_regency').val(),

            sender_district: $('#sender_district').val(),

            sender_village: $('#sender_village').val(),

            receiver_province: $('#receiver_province').val(),

            receiver_regency: $('#receiver_regency').val(),

            receiver_district: $('#receiver_district').val(),

            receiver_village: $('#receiver_village').val(),

            weight: weight,

            length: length,

            width: width,

            height: height,

            service: serviceType,

            price: price,

            ansuransi: ansuransi,

            item_type: $('#item_type').val(),

            _token: "{{ csrf_token() }}"

        },

        success: function (res) {

            const ongkirModalBody = $('#ongkirModalBody');

            ongkirModalBody.empty();



            function formatRupiah(angka) { 

                return 'Rp ' + parseInt(angka, 10).toLocaleString('id-ID'); 

            }



            let groupedResults = {}; 



             if (res.results) {

                res.results.forEach(function(item) {

                    let optionValue = `express-${item.service}-${item.service_type}-${item.cost}`;

                    let details = `Estimasi: ${item.etd} hari`;

                    let isCod = false;

            

                    if (item.setting && item.setting.cod_fee_amount) {

                        optionValue += `-${item.setting.cod_fee_amount}`;

                        details += ` | Biaya COD: ${formatRupiah(item.setting.cod_fee_amount)}`;

                        isCod = true;

                    }

            

                    if (item.insurance) {

                        optionValue += `-${item.insurance}`;

                        details += ` | Asuransi: ${formatRupiah(item.insurance)}`;

                    }

            

                    let groupName = 'Regular';

                    const serviceNameLower = item.service_name.toLowerCase();

                    if (serviceNameLower.includes('instant')) {

                        groupName = 'Instant';

                    } else if (serviceNameLower.includes('cargo')) {

                        groupName = 'Cargo';

                    }

            

                    if (!groupedResults[groupName]) groupedResults[groupName] = [];

                    groupedResults[groupName].push({

                        service: item.service,

                        name: item.service_name,

                        price: item.cost,

                        details: details,

                        value: optionValue,

                        cod_supported: isCod

                    });

                });

            }

            

            if (res.result) {

                res.result.forEach(function(vendor) {

                    vendor.costs.forEach(function(cost) {

                        let groupName = 'Regular'; // default regular

                        const vendorNameLower = vendor.name.toLowerCase();

                        if (vendorNameLower.includes('instant')) {

                            groupName = 'Instant';

                        } else if (vendorNameLower.includes('cargo')) {

                            groupName = 'Cargo';

                        }

            

                        if (!groupedResults[groupName]) groupedResults[groupName] = [];

                         groupedResults[groupName].push({

                            service: vendor.name,

                            name: `${vendor.name.toUpperCase()} - ${cost.service_type}`,

                            price: cost.price.total_price,

                            details: `Estimasi: ${cost.estimation ?? '-'}`,

                            value: `instant-${vendor.name}-${cost.service_type}-${cost.price.total_price}-0-${cost.price.admin_fee}`,

                            cod_supported: false

                        });

                    });

                });

            }



            // Buat tombol grup (Tailwind)

            let groupButtonsHtml = '<div class="flex flex-wrap gap-2 mb-4">';

            for (const group in groupedResults) {

                groupButtonsHtml += `<button type="button" class="px-4 py-2 text-sm font-medium rounded-lg border border-blue-500 text-blue-500 hover:bg-blue-500 hover:text-white transition group-button" data-group="${group}">${group}</button>`;

            }

            groupButtonsHtml += '</div>';

            ongkirModalBody.html(groupButtonsHtml + '<div id="group-items"></div>');



            let groupItemsContainer = $('#group-items');

            const firstGroup = Object.keys(groupedResults)[0];

            displayGroupItems(firstGroup);



            ongkirModalBody.on('click', '.group-button', function() {

                const group = $(this).data('group');

                displayGroupItems(group);

            });



            function displayGroupItems(group) {

                groupItemsContainer.html(''); 

                const items = groupedResults[group];

                if (!items || items.length === 0) {

                    groupItemsContainer.html('<div class="p-4 bg-yellow-100 text-yellow-800 rounded-lg">Tidak ada layanan untuk grup ini.</div>');

                    return;

                }



                items.sort((a, b) => a.price - b.price);



                 items.forEach(item => {

                    let ekspedisi = item.service ? item.service.toLowerCase() : 'default';

                    let logoPath = `/storage/logo-ekspedisi/${ekspedisi}.png`;

                

                    groupItemsContainer.append(`

                        <div class="card mb-2 shadow-sm">

                            <div class="card-body d-flex justify-content-between align-items-center p-2">

                                <div class="d-flex align-items-center">

                                    <img src="${logoPath}" alt="${ekspedisi}" class="me-2" style="width:40px;height:auto;">

                                    <div>

                                        <h6 class="card-title mb-1 fw-bold">${item.name}</h6>

                                        <small class="text-muted">${item.details}</small>

                                    </div>

                                </div>

                                <div class="text-end">

                                    <strong class="d-block fs-5 text-danger">${formatRupiah(item.price)}</strong>

                                    <button type="button" class="btn btn-sm btn-danger mt-1 select-ongkir-btn"

                                        data-value="${item.value}"

                                        data-display="${item.name} - ${formatRupiah(item.price)}"

                                        data-cod-supported="${item.cod_supported}">

                                        <i class="fas fa-check-circle me-1"></i>Pilih

                                    </button>

                                </div>

                            </div>

                        </div>

                    `);

                });

            }

        },

        error: function () { 

            $('#ongkirModalBody').html('<div class="p-4 bg-red-100 text-red-800 rounded-lg">Gagal mengambil data ongkir. Silakan coba lagi.</div>'); 

        }

    });

}





$('#selected_expedition_display').on('click', runCekOngkir);



$(document).on('click', '.select-ongkir-btn', function() {

    $('#expedition').val($(this).data('value'));

    $('#selected_expedition_display').val($(this).data('display'));



    if ($(this).data('cod-supported')) {

        $('#codPaymentOption').removeClass('hidden'); 

    } else {

        if ($('#payment_method').val() === 'COD') {

            $('#payment_method').val('');

            $('#selectedPaymentName').text('Pilih Pembayaran...');

            $('#selectedPaymentLogo').attr('src', 'https://placehold.co/32x32/EFEFEF/333333?text=?');

        }

        $('#codPaymentOption').addClass('hidden'); 

    }



    closeModalss('ongkirModal');

});



</script>

<script>



document.getElementById('confirmBtn').addEventListener('click', function () {

    Swal.fire({

        title: 'Apakah Anda yakin?',

        text: "Data akan dilanjutkan ke rincian.",

        icon: 'question',

        showCancelButton: true,

        confirmButtonText: 'Ya, lanjutkan',

        cancelButtonText: 'Batal',

        reverseButtons: true

    }).then((result) => {

        if (result.isConfirmed) {

            document.getElementById('orderForm').submit();

        }

    });

});

</script>

<script>

    $(document).ready(function () {

        $('select[name="payment_method"] option[value="COD"]').hide();

        

        $('#service_type').on('change', function () {

            let serviceType = $(this).val();



            let sender_province   = $('#sender_province').val();

            let sender_regency    = $('#sender_regency').val();

            let sender_district   = $('#sender_district').val();

            let sender_village    = $('#sender_village').val();

            let sender_postal     = $('#sender_postal_code').val();

            let sender_address    = $('#sender_address').val();



            let receiver_province = $('#receiver_province').val();

            let receiver_regency  = $('#receiver_regency').val();

            let receiver_district = $('#receiver_district').val();

            let receiver_village  = $('#receiver_village').val();

            let receiver_postal   = $('#receiver_postal_code').val();

            let receiver_address  = $('#receiver_address').val();



            // ambil value dari paket

            let weight      = $('#weight').val();

            let length      = $('#length').val();

            let width       = $('#width').val();

            let height      = $('#height').val();

            let price       = $('#item_price').val();



            let payment     = $('#payment_method').val();

            let item_type     = $('#item_type').val();

 

            // validasi field wajib

            if (!weight || !serviceType || !price) {

                alert("Lengkapi data pengirim, penerima,harga paket, dan berat paket terlebih dahulu.");

                $(this).val(""); // reset select

                return;

            }



            // AJAX request

            $.ajax({

                url: "{{ route('kirimaja.cekongkir') }}",

                type: "GET",

                data: {

                    sender_province: sender_province,

                    sender_regency: sender_regency,

                    sender_district: sender_district,

                    sender_village: sender_village,

                    sender_postal: sender_postal,

                    sender_address: sender_address,



                    receiver_province: receiver_province,

                    receiver_regency: receiver_regency,

                    receiver_district: receiver_district,

                    receiver_village: receiver_village,

                    receiver_postal: receiver_postal,

                    receiver_address: receiver_address,



                    weight: weight,

                    length: length,

                    width: width,

                    height: height,

                    service: serviceType,

                    price: price,

                    item_type:item_type,

                    _token: "{{ csrf_token() }}"

                },

                beforeSend: function () {

                    $('#expedition').html('<option>Loading...</option>');

                },

                success: function (res) {

                    $('#expedition').empty().append('<option value="">Pilih Ekspedisi</option>');



                    function formatRupiah(angka) {

                        return 'Rp' + parseInt(angka, 10).toLocaleString('id-ID');

                    }

                    

                    if (res.results) {

                        let groups = {};

                    

                        res.results.forEach(function (item) {

                            let optionValue = `express-${item.service}-${item.service_type}-${item.cost}`;

                            let optionText  = `${item.service_name} - ${formatRupiah(item.cost)} (ETD: ${item.etd} hari)`;

                    

                            if (item.cod && item.setting && item.setting.cod_fee_amount) {

                                optionValue += `-${item.setting.cod_fee_amount}_${item.setting.cod_fee}`;

                                optionText  += ` | COD Fee: ${formatRupiah(item.setting.cod_fee_amount)}`;

                            }

                    

                            if (item && item.insurance) {

                                optionValue += `-${item.insurance}`;

                                optionText  += ` | Insurance: ${formatRupiah(item.insurance)}`;

                            }

                    

                            if (!groups[item.group]) {

                                groups[item.group] = [];

                            }

                            groups[item.group].push({ value: optionValue, text: optionText });

                        });

                    

                        $('#expedition').empty();

                    

                        for (const groupName in groups) {

                            let formattedGroup = groupName.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase()); // ganti _ jadi spasi & kapitalisasi

                            let $optgroup = $(`<optgroup label="${formattedGroup}"></optgroup>`);

                    

                            groups[groupName].forEach(option => {

                                $optgroup.append(`<option value="${option.value}">${option.text}</option>`);

                            });

                    

                            $('#expedition').append($optgroup);

                        }

                    }



                    

                    if (res.result) {

                        res.result.forEach(function (vendor) {

                            vendor.costs.forEach(function (cost) {

                                let optionValue = `instant-${vendor.name}-${cost.service_type}-${cost.price.total_price}`;

                                let optionText  = `${vendor.name.toUpperCase()} - ${cost.service_type} : ${formatRupiah(cost.price.total_price)} (ETA: ${cost.estimation ?? '-'})`;

                    

                                $('#expedition').append(

                                    `<option value="${optionValue}">${optionText}</option>`

                                );

                            });

                        });

                    }







                    if (serviceType === "express") {

                        $('select[name="payment_method"] option[value="COD"]').show();

                    } else {

                        $('select[name="payment_method"]').val(""); 

                        $('select[name="payment_method"] option[value="COD"]').hide();

                    }

                },

                error: function () {

                    alert("Gagal mengambil ongkir. Coba lagi.");

                    $('#expedition').html('<option value="">Error</option>');

                }

            });

        });



        // default hide COD option

        $('select[name="payment_method"] option[value="COD"]').hide();

    });

</script>

<script>

document.addEventListener('DOMContentLoaded', function () {

    // --- FUNGSI PENCARIAN KONTAK ---

    async function searchKontak(query, resultsContainerId) {

        const resultsContainer = document.getElementById(resultsContainerId);

        if (query.length < 2) {

            resultsContainer.classList.add('hidden');

            return;

        }



        try {

            const response = await fetch(`{{ route('api.kontak.search') }}?query=${query}`);

            if (!response.ok) throw new Error('Network response was not ok');

            const kontaks = await response.json();

            

            resultsContainer.innerHTML = '';

            if (kontaks.length > 0) {

                kontaks.forEach(kontak => {

                    const item = document.createElement('div');

                    item.className = 'p-2 border-b hover:bg-gray-100 cursor-pointer text-sm';

                    item.textContent = `${kontak.nama} - ${kontak.no_hp}`;

                    item.addEventListener('click', () => {

                        if (resultsContainerId === 'sender_results') {

                            document.getElementById('sender_name').value = kontak.nama;

                            document.getElementById('sender_phone').value = kontak.no_hp;

                            document.getElementById('sender_address').value = kontak.alamat;

                        } else {

                            document.getElementById('receiver_name').value = kontak.nama;

                            document.getElementById('receiver_phone').value = kontak.no_hp;

                            document.getElementById('receiver_address').value = kontak.alamat;

                        }

                        resultsContainer.classList.add('hidden');

                    });

                    resultsContainer.appendChild(item);

                });

                resultsContainer.classList.remove('hidden');

            } else {

                resultsContainer.classList.add('hidden');

            }

        } catch (error) {

            console.error('Pencarian gagal:', error);

        }

    }



    document.getElementById('search_sender').addEventListener('keyup', (e) => searchKontak(e.target.value, 'sender_results'));

    document.getElementById('search_receiver').addEventListener('keyup', (e) => searchKontak(e.target.value, 'receiver_results'));



    document.addEventListener('click', function(event) {

        if (!document.getElementById('search_sender_container').contains(event.target)) {

            document.getElementById('sender_results').classList.add('hidden');

        }

        if (!document.getElementById('search_receiver_container').contains(event.target)) {

             document.getElementById('receiver_results').classList.add('hidden');

        }

    });



    // Data & Element References

    const allExpeditions = {'JNE': 'JNE', 'J&T Express': 'J&T Express', 'J&T Cargo': 'J&T Cargo', 'Wahana Express': 'Wahana Express', 'POS Indonesia': 'POS Indonesia', 'SAP Express': 'SAP Express', 'Indah Cargo': 'Indah Cargo', 'Lion Parcel': 'Lion Parcel', 'ID Express': 'ID Express', 'SPX Express': 'SPX Express', 'NCS': 'NCS', 'Sentral Cargo': 'Sentral Cargo', 'Sancaka Express': 'Sancaka Express'};

    const cargoAndMotorExpeditions = ['J&T Cargo', 'Indah Cargo', 'Sancaka Cargo'];

    

    const serviceTypeSelect = document.getElementById('service_type');

    const expeditionSelect = document.getElementById('expedition');

    const motorcycleChecklist = document.getElementById('motorcycle_checklist');

    const openModalBtn = document.getElementById('openConfirmationModalBtn');

    const modal = document.getElementById('confirmationModal');

    const closeModalBtn = document.getElementById('closeModalBtn');

    const submitOrderBtn = document.getElementById('submitOrderBtn');

    const orderForm = document.getElementById('orderForm');

    const cekOngkirBtn = document.getElementById('cekOngkirBtn');

    const modalCostSummary = document.getElementById('modal_cost_summary');





    function calculateAndShowSummary() {

        const weight = parseFloat(document.getElementById('weight').value) || 0;

        const length = parseFloat(document.getElementById('length').value) || 0;

        const width = parseFloat(document.getElementById('width').value) || 0;

        const height = parseFloat(document.getElementById('height').value) || 0;

        const selectedExpedition = expeditionSelect.value;

        const totalBiaya = parseFloat(document.getElementById('total_harga_barang').value) || 0;



        if (weight <= 0 || totalBiaya <= 0) {

            modalCostSummary.innerHTML = '<p class="text-red-500">Berat dan Total Biaya wajib diisi.</p>';

            return false;

        }



        let divisor = 6000;

        if (selectedExpedition === 'Indah Cargo') divisor = 5000;



        const volumetricWeight = (length * width * height) / divisor;

        const actualWeightInKg = weight / 1000;

        const chargeableWeight = Math.max(actualWeightInKg, volumetricWeight);



        modalCostSummary.innerHTML = `

            <div class="flex justify-between"><span>Berat Asli:</span> <span class="font-medium">${actualWeightInKg.toFixed(2)} kg</span></div>

            <div class="flex justify-between"><span>Berat Volume:</span> <span class="font-medium">${volumetricWeight.toFixed(2)} kg</span></div>

            <div class="flex justify-between"><span>Total Berat Dikenakan:</span> <span class="font-medium">${chargeableWeight.toFixed(2)} kg</span></div>

            <div class="flex justify-between text-base font-semibold pt-3 border-t mt-2"><span>Total Biaya:</span> <span class="text-indigo-600">Rp ${totalBiaya.toLocaleString('id-ID')}</span></div>

        `;

        return true;

    }



    function sendToWhatsApp() {

        const data = new FormData(orderForm);

        let checklistItems = '';

        if (serviceTypeSelect.value === 'motor') {

            const checkedItems = Array.from(document.querySelectorAll('input[name="kelengkapan[]"]:checked')).map(cb => cb.value);

            checklistItems = `*--- KELENGKAPAN MOTOR ---*\n${checkedItems.map(item => `- ✅ ${item}`).join('\n')}\n\n`;

        }

        const message = `

Halo Sancaka Express,

Saya ingin melakukan pengecekan ongkos kirim dengan detail sebagai berikut:

*--- DATA PENGIRIM ---*

👤 *Nama:* ${data.get('sender_name')}

📞 *No. HP:* ${data.get('sender_phone')}

🏠 *Alamat:* ${data.get('sender_address')}

*--- DATA PENERIMA ---*

� *Nama:* ${data.get('receiver_name')}

📞 *No. HP:* ${data.get('receiver_phone')}

🏠 *Alamat:* ${data.get('receiver_address')}

*--- DETAIL PAKET ---*

🚚 *Layanan:* ${data.get('service_type')}

✈️ *Ekspedisi:* ${data.get('expedition')}

📦 *Isi Paket:* ${data.get('item_description')}

⚖️ *Berat Asli:* ${data.get('weight')} gram

📐 *Dimensi:* ${data.get('length')} x ${data.get('width')} x ${data.get('height')} cm

${checklistItems}*--- PEMBAYARAN ---*

💰 *Metode:* ${data.get('payment_method')}

Mohon informasikan estimasi biayanya. Terima kasih.

        `.trim();

        const phoneNumber = '6285745808809';

        const whatsappUrl = `https://wa.me/${phoneNumber}?text=${encodeURIComponent(message)}`;

        window.open(whatsappUrl, '_blank');

    }



    

    openModalBtn.addEventListener('click', () => {

        if (orderForm.checkValidity()) {

            if (calculateAndShowSummary()) {

                modal.classList.remove('hidden');

            }

        } else {

            orderForm.reportValidity();

        }

    });



    closeModalBtn.addEventListener('click', () => modal.classList.add('hidden'));

    

    submitOrderBtn.addEventListener('click', () => {

        orderForm.submit();

    });



    cekOngkirBtn.addEventListener('click', sendToWhatsApp);



});

</script>

@endpush