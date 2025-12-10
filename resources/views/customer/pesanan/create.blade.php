{{--
    File: resources/views/customer/pesanan/create.blade.php
    Deskripsi: Halaman pembuatan pesanan untuk pelanggan,
    disempurnakan dengan fitur dari panel admin.
    PERBAIKAN LENGKAP: Menghilangkan error Call to undefined function error() dan menambahkan Autosave Kontak via AJAX.
--}}

@extends('layouts.customer')

@section('title', 'Buat Pesanan Baru')
@section('page-title', 'Buat Pesanan Baru')

@push('styles')
{{-- Font Awesome untuk ikon --}}
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
    /* Style untuk hasil pencarian custom, karena butuh absolute positioning */
    .search-results-container {
        position: absolute;
        z-index: 1000;
        width: 100%;
        max-height: 250px;
        overflow-y: auto;
        background-color: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 0 0 0.5rem 0.5rem; /* rounded bottom */
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        margin-top: -1px; /* Nempel dengan input */
    }
    .modal-body-scroll {
        max-height: 70vh;
        overflow-y: auto;
    }
    /* Style untuk tanda wajib diisi */
    .required-label::after {
        content: '*';
        color: #ef4444; /* text-red-500 */
        margin-left: 0.25rem;
    }
    /* Tambahan style untuk input error real-time */
    .is-invalid {
        border-color: #ef4444 !important;
    }
</style>
@endpush

@section('content')

@include('layouts.partials.notifications')

<div class="max-w-7xl mx-auto">
    <form id="orderForm" action="{{ route('customer.pesanan.store') }}" method="POST">
        @csrf
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

            <div class="lg:col-span-2 space-y-8">
                
                {{-- MULAI KODE DINAMIS --}}
    @php
        // 1. Mengambil data Info Admin
        $infoAdmin = \App\Models\Setting::where('key', 'info_pesanan')->value('value');

        // 2. PERBAIKAN ERROR SCREENSHOT:
        // Cek apakah $idempotencyKey sudah dikirim controller? Jika belum, buat sendiri disini.
        // Ini mencegah error "Undefined variable $idempotencyKey"
        if (!isset($idempotencyKey)) {
            $idempotencyKey = (string) \Illuminate\Support\Str::uuid();
        }
    @endphp

    @if(!empty($infoAdmin)) 
        {{-- Alert hanya muncul jika admin mengisi pesan --}}
        <div class="bg-red-50 border-l-4 border-red-500 p-4 rounded-r-lg shadow-sm mb-6">
            <div class="flex">
                <div class="flex-shrink-0">
                    <i class="fas fa-info-circle text-red-500 mt-0.5"></i>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-bold text-red-800">
                        Informasi Penting Admin
                    </h3>
                    <div class="mt-1 text-sm text-red-700">
                        {{-- nl2br agar ENTER yang diketik admin menjadi baris baru --}}
                        <p>{!! nl2br(e($infoAdmin)) !!}</p>
                    </div>
                </div>
            </div>
        </div>
    @endif
    {{-- SELESAI KODE DINAMIS --}}

                <div class="bg-white p-6 rounded-lg shadow-md border border-red-700 
    transition-all duration-200 hover:ring-4 hover:ring-red-400 hover:shadow-lg">

    <!-- HEADER BOX MERAH -->
    <div class="bg-red-600 backdrop-blur px-4 py-3 rounded-lg shadow 
        flex items-center justify-between mb-6
        border border-red-700
        transition-all duration-200
        hover:shadow-2xl hover:border-red-400 hover:ring-2 hover:ring-red-300">

        <h3 class="text-xl font-semibold text-white">
            <i class="fas fa-arrow-up-from-bracket text-white mr-2"></i>
            Informasi Pengirim
        </h3>

        <!-- SEARCH INPUT -->
        <div class="relative w-1/2">
            <input type="search" id="sender_contact_search"
                class="w-full pl-10 pr-4 py-2 bg-white border border-gray-300 rounded-lg text-sm text-gray-900
                transition-all duration-200
                hover:border-red-500 hover:shadow-lg hover:ring-2 hover:ring-red-300
                focus:outline-none focus:border-red-500 focus:ring-4 focus:ring-red-300 focus:shadow-lg"
                placeholder="Cari dari kontak pengirim..." autocomplete="off">

            <div class="absolute top-0 left-0 inline-flex items-center p-2 h-full text-gray-400">
                <i class="fas fa-search"></i>
            </div>

            <div id="sender_contact_results"
                    class="absolute z-50 w-full bg-white border border-red-300 rounded-lg shadow-lg mt-1 hidden">
            </div>
        </div>
    </div>

    <!-- GRID FORM -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

        <!-- Nama Pengirim -->
        <div class="relative">
            <label for="sender_name" class="block mb-2 text-sm font-medium text-gray-700 required-label">
                Nama Pengirim
            </label>

            <input type="search" id="sender_name" name="sender_name"
                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5
                transition-all duration-200
                hover:border-red-400 hover:ring-2 hover:ring-red-200 hover:shadow-md
                focus:outline-none focus:border-red-500 focus:ring-4 focus:ring-red-300 focus:shadow-md
                @error('sender_name') is-invalid @enderror"
                value="{{ old('sender_name', auth()->user()->nama_lengkap) }}" required autocomplete="off">

            @error('sender_name')
            <div class="invalid-feedback text-sm text-red-600 mt-1">{{ $message }}</div>
            @enderror
        </div>

        <!-- Nomor HP -->
        <div class="relative">
            <label for="sender_phone" class="block mb-2 text-sm font-medium text-gray-700 required-label">
                Nomor HP
            </label>

            <input type="tel" id="sender_phone" name="sender_phone"
                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5
                transition-all duration-200
                hover:border-red-400 hover:ring-2 hover:ring-red-200 hover:shadow-md
                focus:outline-none focus:border-red-500 focus:ring-4 focus:ring-red-300 focus:shadow-md
                @error('sender_phone') is-invalid @enderror"
                value="{{ old('sender_phone', auth()->user()->no_wa) }}" required autocomplete="off">

            @error('sender_phone')
            <div class="invalid-feedback text-sm text-red-600 mt-1">{{ $message }}</div>
            @enderror
        </div>

        <!-- Cari Alamat -->
        <div class="md:col-span-2 relative">
            <label for="sender_address_search" class="block mb-2 text-sm font-medium text-gray-700 required-label">
                Cari Alamat Ongkir (Kec/Kel/Kodepos)
            </label>

            <div class="relative">
                <input type="text" id="sender_address_search"
                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5 pr-8
                    transition-all duration-200
                    hover:border-red-400 hover:ring-2 hover:ring-red-200 hover:shadow-md
                    focus:outline-none focus:border-red-500 focus:ring-4 focus:ring-red-300 focus:shadow-md"
                    placeholder="Ketik untuk mencari alamat..." autocomplete="off">

                <i id="sender_address_check"
                    class="fas fa-check-circle text-green-500 absolute top-1/2 right-3 transform -translate-y-1/2 hidden"></i>
            </div>

            <div id="sender_address_results" class="search-results-container hidden"></div>
        </div>

        <!-- Detail Alamat -->
        <div class="md:col-span-2">
            <label for="sender_address" class="block mb-2 text-sm font-medium text-gray-700 required-label">
                Detail Alamat Lengkap Pengirim (Min. 10 Karakter)
            </label>

            <textarea id="sender_address" name="sender_address" rows="3"
                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5
                transition-all duration-200
                hover:border-red-400 hover:ring-2 hover:ring-red-200 hover:shadow-md
                focus:outline-none focus:border-red-500 focus:ring-4 focus:ring-red-300 focus:shadow-md
                @error('sender_address') is-invalid @enderror"
                placeholder="Contoh: Jl. Pahlawan No. 12, RT 01/RW 05" required>{{ old('sender_address', auth()->user()->address_detail) }}</textarea>

            @error('sender_address')
            <div class="invalid-feedback text-sm text-red-600 mt-1">{{ $message }}</div>
            @enderror

            <div id="sender_address_feedback" class="invalid-feedback text-sm text-red-600 mt-1" style="display:none;">
                Alamat minimal 10 karakter.
            </div>
        </div>

        <!-- Checkbox -->
        <div class="md:col-span-2">
            <label class="flex items-center text-sm text-gray-600">
                <input type="checkbox" id="save_sender_checkbox" name="save_sender" value="on"
                    class="h-4 w-4 rounded border-gray-300 text-red-600 focus:ring-red-500 mr-2">
                Simpan/Perbarui data pengirim ini
            </label>
        </div>
    </div>
</div>


                <div class="bg-white p-6 rounded-lg shadow-md border border-blue-700 transition-all duration-200 hover:ring-4 hover:ring-blue-400 hover:shadow-lg">
                    <div class="flex justify-between items-center bg-blue-700 px-4 py-3 mb-6 rounded-lg shadow 
            border border-transparent
            transition-all duration-200
            hover:shadow-2xl
            hover:border-blue-300
            hover:ring-2 hover:ring-blue-300">

                        <h3 class="text-xl font-semibold text-white">
                            <i class="fas fa-map-marker-alt text-white mr-2"></i>Informasi Penerima
                        </h3>
                       <div class="relative w-1/2">
                            <input type="search" id="receiver_contact_search" 
                            class="w-full pl-10 pr-4 py-2 bg-white border border-gray-300 rounded-lg text-sm text-gray-900 transition-all duration-200 hover:border-blue-400 hover:ring-2 hover:ring-blue-200 hover:shadow-md focus:outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-300 focus:shadow-md" placeholder="Cari dari kontak penerima..." autocomplete="off">
                           <div class="absolute top-0 left-0 inline-flex items-center p-2 h-full text-gray-400">
                                <i class="fas fa-search"></i>
                            </div>
                            <div id="receiver_contact_results" class="search-results-container hidden"></div>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="relative">
                            <label for="receiver_name" class="block mb-2 text-sm font-medium text-gray-700 required-label">Nama Penerima</label>
                            <input type="search" id="receiver_name" name="receiver_name" 
                            
                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5
       transition-all duration-200
       hover:border-blue-400 hover:ring-2 hover:ring-blue-200 hover:shadow-md
       focus:outline-none
       focus:border-blue-500
       focus:ring-4 focus:ring-blue-300
       focus:shadow-md"

                            
                            @error('receiver_name') is-invalid @enderror" required autocomplete="off">
                            @error('receiver_name')
                                <div class="invalid-feedback text-sm text-red-600 mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="relative">
                            <label for="receiver_phone" class="block mb-2 text-sm font-medium text-gray-700 required-label">Nomor HP</label>
                            <input type="tel" id="receiver_phone" name="receiver_phone" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5 @error('receiver_phone') is-invalid @enderror" required autocomplete="off">
                            @error('receiver_phone')
                                <div class="invalid-feedback text-sm text-red-600 mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="md:col-span-2 relative">
                            <label for="receiver_address_search" class="block mb-2 text-sm font-medium text-gray-700 required-label">Cari Alamat Ongkir (Kec/Kel/Kodepos)</label>
                            <div class="relative">
                                <input type="text" id="receiver_address_search" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5 pr-8" placeholder="Ketik untuk mencari alamat..." autocomplete="off">
                                <i id="receiver_address_check" class="fas fa-check-circle text-green-500 absolute top-1/2 right-3 transform -translate-y-1/2 hidden"></i>
                            </div>
                            <div id="receiver_address_results" class="search-results-container hidden"></div>
                        </div>
                        <div class="md:col-span-2">
                            <label for="receiver_address" class="block mb-2 text-sm font-medium text-gray-700 required-label">Alamat Penerima Lengkap (Min. 10 Karakter)</label>
                            <textarea id="receiver_address" name="receiver_address" rows="3" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5 @error('receiver_address') is-invalid @enderror" placeholder="Contoh: Jl. Merdeka No. 45, RT 02/RW 03" required>{{ old('receiver_address') }}</textarea>
                            
                            {{-- BLOK ERROR SERVER LARAVEL --}}
                            @error('receiver_address')
                                <div class="invalid-feedback text-sm text-red-600 mt-1">{{ $message }}</div>
                            @enderror

                            {{-- BLOK ERROR KUSTOM JAVASCRIPT (Default hidden) --}}
                            <div id="receiver_address_feedback" class="invalid-feedback text-sm text-red-600 mt-1" style="display:none;">
                                Alamat minimal 10 karakter.
                            </div>
                        </div>
                         <div class="md:col-span-2">
                                <label class="flex items-center text-sm text-gray-600"><input type="checkbox" id="save_receiver_checkbox" name="save_receiver" value="on" class="h-4 w-4 rounded border-gray-300 text-red-600 focus:ring-red-500 mr-2"> Simpan data penerima ini</label>
                         </div>
                    </div>
                </div>
            </div>

            <div class="lg:col-span-1 space-y-8">
                <div class="bg-white p-6 rounded-lg shadow-md sticky top-8">
                    <h3 class="text-xl font-semibold text-gray-800 border-b pb-4 mb-6">
                        <i class="fas fa-box-open text-yellow-500 mr-2"></i>Detail Paket
                    </h3>
                    <div class="space-y-4">
                        <div>
                            <label for="item_description" class="block mb-2 text-sm font-medium text-gray-700 required-label">Deskripsi Barang</label>
                            <input type="text" id="item_description" name="item_description" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5 @error('item_description') is-invalid @enderror" required>
                            @error('item_description')
                                <div class="invalid-feedback text-sm text-red-600 mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                        <div>
                            <label for="item_price" class="block mb-2 text-sm font-medium text-gray-700 required-label">Harga Barang (Rp)</label>
                            <input type="number" name="item_price" id="item_price" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5 @error('item_price') is-invalid @enderror" required min="1">
                            @error('item_price')
                                <div class="invalid-feedback text-sm text-red-600 mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                        <div>
                            <label for="weight" class="block mb-2 text-sm font-medium text-gray-700 required-label">Berat (gram)</label>
                            <input type="number" id="weight" name="weight" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5 @error('weight') is-invalid @enderror" required min="1">
                            @error('weight')
                                <div class="invalid-feedback text-sm text-red-600 mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="grid grid-cols-3 gap-4">
                            <div><label for="length" class="block mb-2 text-sm font-medium text-gray-700">P (cm)</label><input type="number" id="length" name="length" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5"></div>
                            <div><label for="width" class="block mb-2 text-sm font-medium text-gray-700">L (cm)</label><input type="number" id="width" name="width" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5"></div>
                            <div><label for="height" class="block mb-2 text-sm font-medium text-gray-700">T (cm)</label><input type="number" id="height" name="height" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5"></div>
                        </div>
                        <div>
                            <label for="item_type" class="block mb-2 text-sm font-medium text-gray-700 required-label">Jenis Barang</label>
                            <select name="item_type" id="item_type" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5 @error('item_type') is-invalid @enderror" required>
                                <option value="" disabled selected>Pilih...</option>
                                <option value="1">Elektronik</option>
                                <option value="2">Pakaian</option>
                                <option value="3">Pecah Belah</option>
                                <option value="4">Dokumen</option>
                                <option value="5">Rumah Tangga</option>
                                <option value="6">Aksesoris</option>
                                <option value="7">Lainnya</option>
                                <option value="8">Makanan & Minuman</option>
                                <option value="9">Peralatan Dapur</option>
                                <option value="10">Peralatan Kantor</option>
                                <option value="11">Buku & Alat Tulis</option>
                                <option value="12">Mainan & Hobi</option>
                                <option value="13">Peralatan Olahraga</option>
                                <option value="14">Kosmetik & Kecantikan</option>
                                <option value="15">Kesehatan & Obat</option>
                                <option value="16">Alat Musik</option>
                                <option value="17">Perhiasan</option>
                                <option value="18">Otomotif</option>
                                <option value="19">Peralatan Pertukangan</option>
                                <option value="20">Dekorasi Rumah</option>
                                <option value="21">Produk Bayi & Anak</option>
                                <option value="22">Peralatan Kebersihan</option>
                                <option value="23">Bahan Bangunan</option>
                                <option value="24">Alat Elektrik</option>
                                <option value="25">Tanaman & Pertanian</option>
                            </select>
                            @error('item_type')
                                <div class="invalid-feedback text-sm text-red-600 mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                        <div>
    <label for="service_type" class="block mb-2 text-sm font-medium text-gray-700 required-label">Jenis Layanan</label>
    <select name="service_type" id="service_type" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5 @error('service_type') is-invalid @enderror" required>
        <option value="" disabled {{ old('service_type') == '' ? 'selected' : '' }}>Pilih...</option>
        
        {{-- Menambahkan logika 'selected' menggunakan old() --}}
        <option value="regular" {{ old('service_type') == 'regular' ? 'selected' : '' }}>Regular / Cargo</option>
        <option value="sameday" {{ old('service_type') == 'sameday' ? 'selected' : '' }}>Grab / Gosend</option>


    </select>
    @error('service_type')
        <div class="invalid-feedback text-sm text-red-600 mt-1">{{ $message }}</div>
    @enderror
</div>
                        <div>
                            <label for="ansuransi" class="block mb-2 text-sm font-medium text-gray-700 required-label">Asuransi</label>
                            <select name="ansuransi" id="ansuransi" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5 @error('ansuransi') is-invalid @enderror" required>
                                <option value="tidak" selected>Tidak</option><option value="iya">Iya</option>
                            </select>
                            @error('ansuransi')
                                <div class="invalid-feedback text-sm text-red-600 mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                        <hr/>
                        <div>
                            <label for="selected_expedition_display" class="block mb-2 text-sm font-medium text-gray-700 required-label">Pilih Ekspedisi</label>
                            <input type="text" id="selected_expedition_display" class="cursor-pointer bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5 text-center font-semibold" placeholder="Lengkapi data & klik di sini" readonly required>
                        </div>
                        <div>
                            <label for="paymentMethodButton" class="block mb-2 text-sm font-medium text-gray-700 required-label">Metode Pembayaran</label>
                            <div id="paymentMethodButton" class="cursor-pointer bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg w-full p-2.5 flex justify-between items-center">
                                <div class="flex items-center"><img id="selectedPaymentLogo" src="https://cdn-icons-png.flaticon.com/512/2331/2331941.png" alt="Logo" class="w-6 h-6 mr-2"><span id="selectedPaymentName">Pilih...</span></div><i class="fas fa-chevron-down text-gray-400"></i>
                            </div>
                        </div>
                        <div class="pt-4">
                            <button type="button" id="confirmBtn" class="w-full text-white bg-red-600 hover:bg-red-700 font-medium rounded-lg text-sm px-5 py-3 text-center">
                                Buat Pesanan
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Hidden fields untuk data API --}}
        <input type="hidden" name="sender_id" id="sender_id">
        <input type="hidden" name="receiver_id" id="receiver_id">
        <input type="hidden" name="idempotency_key" value="{{ $idempotencyKey }}">
        <input type="hidden" name="sender_lat" id="sender_lat"><input type="hidden" name="sender_lng" id="sender_lng">
        <input type="hidden" name="sender_province" id="sender_province"><input type="hidden" name="sender_regency" id="sender_regency">
        <input type="hidden" name="sender_district" id="sender_district"><input type="hidden" name="sender_village" id="sender_village">
        <input type="hidden" name="sender_postal_code" id="sender_postal_code">
        <input type="hidden" name="sender_district_id" id="sender_district_id" required>
        <input type="hidden" name="sender_subdistrict_id" id="sender_subdistrict_id" required>
        <input type="hidden" name="receiver_lat" id="receiver_lat"><input type="hidden" name="receiver_lng" id="receiver_lng">
        <input type="hidden" name="receiver_province" id="receiver_province"><input type="hidden" name="receiver_regency" id="receiver_regency">
        <input type="hidden" name="receiver_district" id="receiver_district"><input type="hidden" name="receiver_village" id="receiver_village">
        <input type="hidden" name="receiver_postal_code" id="receiver_postal_code">
        <input type="hidden" name="receiver_district_id" id="receiver_district_id" required>
        <input type="hidden" name="receiver_subdistrict_id" id="receiver_subdistrict_id" required>
        <input type="hidden" name="expedition" id="expedition" required>
        <input type="hidden" name="payment_method" id="payment_method" required>
    </form>
</div>

<div id="ongkirModal" class="fixed inset-0 bg-gray-800 bg-opacity-60 z-50 hidden flex items-center justify-center">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-2xl mx-4">
        <div class="p-4 border-b flex justify-between items-center">
            <h5 class="text-lg font-semibold"><i class="fas fa-shipping-fast mr-2 text-red-600"></i>Pilihan Ekspedisi</h5>
            <button type="button" class="close-modal-btn text-gray-500 hover:text-gray-800">&times;</button>
        </div>
        <div id="ongkirModalBody" class="p-6 modal-body-scroll"></div>
        <div class="p-4 border-t text-right">
            <button type="button" class="close-modal-btn px-4 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300">Tutup</button>
        </div>
        <input type="hidden" id="selected_expedition_value">
    </div>
</div>

<div id="paymentMethodModal" class="fixed inset-0 bg-gray-800 bg-opacity-60 z-50 hidden flex items-center justify-center">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-md mx-4">
        <div class="p-4 border-b flex justify-between items-center">
            <h5 class="text-lg font-semibold"><i class="fas fa-credit-card mr-2 text-red-600"></i>Pilih Metode Pembayaran</h5>
            <button type="button" class="close-modal-btn text-gray-500 hover:text-gray-800">&times;</button>
        </div>
        <div class="modal-body-scroll">
            <ul id="paymentOptionsList" class="divide-y">
                <li class="payment-option p-4 flex items-center cursor-pointer hover:bg-gray-50" data-value="Potong Saldo" data-label="Potong Saldo"><img src="https://cdn-icons-png.flaticon.com/512/1086/1086060.png" class="w-8 h-8 mr-4">Potong Saldo (Tersedia: Rp {{ number_format(Auth::user()->saldo ?? 0) }})</li>
                <li class="payment-option p-4 flex items-center cursor-pointer hover:bg-gray-50" data-value="DOKU_JOKUL" data-label="REKOMEN SANCAKA"><img src="https://tokosancaka.com/public/assets/doku.png" class="w-8 h-8 mr-4">Rekomendasi Sancaka Express Via VA, QRIS Dan E-Wallet</li>
                <li class="payment-option p-4 flex items-center cursor-pointer hover:bg-gray-50 cod-payment-option" data-value="COD" data-label="COD Ongkir"><img src="{{ asset('public/assets/cod.png') }}" class="w-8 h-8 mr-4">COD Ongkir</li>
                <li class="payment-option p-4 flex items-center cursor-pointer hover:bg-gray-50 cod-payment-option" data-value="CODBARANG" data-label="COD Barang + Ongkir"><img src="{{ asset('public/assets/cod.png') }}" class="w-8 h-8 mr-4">COD Barang + Ongkir</li>
                <li class="payment-option p-4 flex items-center cursor-pointer hover:bg-gray-50" data-value="PERMATAVA" data-label="Permata VA"><img src="{{ asset('public/assets/permata.webp') }}" class="w-8 h-8 mr-4">Permata VA</li>
                <li class="payment-option p-4 flex items-center cursor-pointer hover:bg-gray-50" data-value="BNIVA" data-label="BNI VA"><img src="{{ asset('public/assets/bni.webp') }}" class="w-8 h-8 mr-4">BNI VA</li>
                <li class="payment-option p-4 flex items-center cursor-pointer hover:bg-gray-50" data-value="BRIVA" data-label="BRI VA"><img src="{{ asset('public/assets/bri.webp') }}" class="w-8 h-8 mr-4">BRI VA</li>
                <li class="payment-option p-4 flex items-center cursor-pointer hover:bg-gray-50" data-value="MANDIRIVA" data-label="Mandiri VA"><img src="{{ asset('public/assets/mandiri.webp') }}" class="w-8 h-8 mr-4">Mandiri VA</li>
                <li class="payment-option p-4 flex items-center cursor-pointer hover:bg-gray-50" data-value="BCAVA" data-label="BCA VA"><img src="{{ asset('public/assets/bca.webp') }}" class="w-8 h-8 mr-4">BCA VA</li>
                <li class="payment-option p-4 flex items-center cursor-pointer hover:bg-gray-50" data-value="ALFAMART" data-label="Alfamart"><img src="{{ asset('public/assets/alfamart.webp') }}" class="w-8 h-8 mr-4">Alfamart</li>
                <li class="payment-option p-4 flex items-center cursor-pointer hover:bg-gray-50" data-value="INDOMARET" data-label="Indomaret"><img src="{{ asset('public/assets/indomaret.webp') }}" class="w-8 h-8 mr-4">Indomaret</li>
                <li class="payment-option p-4 flex items-center cursor-pointer hover:bg-gray-50" data-value="OVO" data-label="OVO"><img src="{{ asset('public/assets/ovo.webp') }}" class="w-8 h-8 mr-4">OVO</li>
                <li class="payment-option p-4 flex items-center cursor-pointer hover:bg-gray-50" data-value="DANA" data-label="DANA"><img src="{{ asset('public/assets/dana.webp') }}" class="w-8 h-8 mr-4">DANA</li>
                <li class="payment-option p-4 flex items-center cursor-pointer hover:bg-gray-50" data-value="SHOPEEPAY" data-label="ShopeePay"><img src="{{ asset('public/assets/shopeepay.webp') }}" class="w-8 h-8 mr-4">ShopeePay</li>
                <li class="payment-option p-4 flex items-center cursor-pointer hover:bg-gray-50" data-value="QRIS" data-label="QRIS"><img src="{{ asset('public/assets/qris2.png') }}" class="w-8 h-8 mr-4">QRIS</li>
            </ul>
        </div>
           <div class="p-4 border-t text-right">
                <button type="button" class="close-modal-btn px-4 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300">Tutup</button>
            </div>
    </div>
</div>
@endsection

@push('scripts')
{{-- SweetAlert2 untuk notifikasi --}}
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const ongkirModalEl = document.getElementById('ongkirModal');
    const paymentMethodModal = document.getElementById('paymentMethodModal');
    let searchTimeout = null;
    const minAddressLength = 10; // Definisi panjang minimum alamat

    // --- HELPER FUNCTIONS ---
    const debounce = (func, delay) => {
        return (...args) => { clearTimeout(searchTimeout); searchTimeout = setTimeout(() => func.apply(this, args), delay); };
    };
    function formatRupiah(angka) { 
        return 'Rp ' + (parseInt(angka, 10) || 0).toLocaleString('id-ID'); 
    }

    // --- FUNGSI BARU: VALIDASI ALAMAT REAL-TIME (SISI KLIEN) ---
    function validateAddressRealtime(inputElement, feedbackElement, fieldName) {
        const value = inputElement.value.trim();
        const isInvalid = value.length > 0 && value.length < minAddressLength;

        inputElement.classList.toggle('is-invalid', isInvalid);
        inputElement.classList.toggle('border-red-500', isInvalid);
        inputElement.classList.toggle('border-gray-300', !isInvalid);

        if (feedbackElement) {
            if (isInvalid) {
                feedbackElement.textContent = `${fieldName} harus diisi minimal ${minAddressLength} karakter. Saat ini ${value.length} karakter.`;
                feedbackElement.style.display = 'block';
            } else {
                feedbackElement.style.display = 'none';
            }
        }
    }
    // ----------------------------------------------------------------------------------

    // --- FUNGSI AUTOSAVE KONTAK VIA AJAX ---
    function saveContactAutosave(prefix) {
        const addressSearchInput = document.getElementById(`${prefix}_address_search`);

        const contactData = {
            _token: document.querySelector('input[name="_token"]').value,

            prefix: prefix,
            // Ambil semua data input yang relevan
            // Ambil semua data input yang relevan
        // Gunakan penamaan field yang SAMA dengan yang di-validate oleh Controller
        [`${prefix}_name`]: document.getElementById(`${prefix}_name`).value,
        [`${prefix}_phone`]: document.getElementById(`${prefix}_phone`).value,
        [`${prefix}_address`]: document.getElementById(`${prefix}_address`).value,
        [`${prefix}_province`]: document.getElementById(`${prefix}_province`).value,
        [`${prefix}_regency`]: document.getElementById(`${prefix}_regency`).value,
        [`${prefix}_district`]: document.getElementById(`${prefix}_district`).value,
        [`${prefix}_village`]: document.getElementById(`${prefix}_village`).value,
        [`${prefix}_postal_code`]: document.getElementById(`${prefix}_postal_code`).value,
            
            tipe: (prefix === 'sender' ? 'Pengirim' : 'Penerima'),
            id: document.getElementById(`${prefix}_id`).value
        };

        // Cek data minimal yang harus ada (Nama, HP, Alamat Detail, dan Alamat Ongkir)
        if (!contactData.name || !contactData.phone || contactData.address.length < minAddressLength || !addressSearchInput.value) {
            Swal.fire('Peringatan', `Data ${contactData.tipe} (Nama, HP, Alamat Detail min 10 karakter, dan Alamat Ongkir) wajib diisi sebelum disimpan.`, 'warning');
            return;
        }

        fetch('{{ route('customer.pesanan.save_contact') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(contactData)
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                if (data.contact_id) {
                     document.getElementById(`${prefix}_id`).value = data.contact_id;
                }
                Swal.fire('Berhasil', `${contactData.tipe} berhasil disimpan/diperbarui.`, 'success');
            } else {
                Swal.fire('Gagal', `Gagal menyimpan data ${contactData.tipe}. Pesan: ` + (data.message || 'Error server.'), 'error');
            }
        })
        .catch(error => {
            console.error('AJAX Save Error:', error);
            Swal.fire('Error', 'Gagal terhubung ke server untuk menyimpan data.', 'error');
        });
    }
    // ----------------------------------------------------------------------------------


    // --- FUNGSI PENCARIAN KONTAK DARI DATABASE (TETAP) ---
    function setupContactSearch(prefix) {
        const searchInput = document.getElementById(`${prefix}_contact_search`);
        const resultsContainer = document.getElementById(`${prefix}_contact_results`);
        const contactType = (prefix === 'sender') ? 'Pengirim' : 'Penerima';

        const performSearch = async (query) => {
            if (query.length < 3) {
                resultsContainer.classList.add('hidden');
                return;
            }

            try {
                const url = `{{ route('api.contacts.search') }}?search=${encodeURIComponent(query)}&tipe=${contactType}`;
                const response = await fetch(url);
                
                if (!response.ok) {
                    throw new Error(`Server error: ${response.statusText}`);
                }
                
                const contacts = await response.json();
                resultsContainer.innerHTML = '';
                resultsContainer.classList.remove('hidden');

                if (contacts && contacts.length > 0) {
                    contacts.forEach(contact => {
                        const resultDiv = document.createElement('div');
                        resultDiv.className = 'p-3 border-b hover:bg-gray-100 cursor-pointer text-sm';
                        resultDiv.innerHTML = `<div class="font-semibold">${contact.nama}</div><div class="text-xs text-gray-500">${contact.no_hp}</div>`;
                        
                        resultDiv.addEventListener('click', () => {
                            document.getElementById(`${prefix}_id`).value = contact.id || '';
                            document.getElementById(`${prefix}_name`).value = contact.nama || '';
                            document.getElementById(`${prefix}_phone`).value = contact.no_hp || '';
                            document.getElementById(`${prefix}_address`).value = contact.alamat || '';
                            
                            document.getElementById(`${prefix}_province`).value = contact.province || '';
                            document.getElementById(`${prefix}_regency`).value = contact.regency || '';
                            document.getElementById(`${prefix}_district`).value = contact.district || '';
                            document.getElementById(`${prefix}_village`).value = contact.village || '';
                            document.getElementById(`${prefix}_postal_code`).value = contact.postal_code || '';
                            
                            const kiriminAjaSearchString = [contact.village, contact.district, contact.regency, contact.postal_code].filter(Boolean).join(', ');
                            const addressSearchInput = document.getElementById(`${prefix}_address_search`);
                            addressSearchInput.value = kiriminAjaSearchString;

                            resultsContainer.classList.add('hidden');
                            
                            const addressInput = document.getElementById(`${prefix}_address`);
                            const feedback = document.getElementById(`${prefix}_address_feedback`);
                            validateAddressRealtime(addressInput, feedback, (prefix === 'sender' ? 'Alamat Pengirim' : 'Alamat Penerima'));

                            if (kiriminAjaSearchString) {
                                performAddressSearch(prefix, kiriminAjaSearchString, contact);
                            }
                        });
                        resultsContainer.appendChild(resultDiv);
                    });
                } else {
                    resultsContainer.innerHTML = '<div class="p-3 text-gray-500">Kontak tidak ditemukan.</div>';
                }
            } catch (error) {
                console.error(`[${prefix}] Gagal melakukan pencarian kontak:`, error);
                resultsContainer.classList.remove('hidden');
                resultsContainer.innerHTML = `<div class="p-3 text-red-500">Gagal memuat data. Periksa koneksi atau log server.</div>`;
            }
        };

        searchInput.addEventListener('input', debounce(() => performSearch(searchInput.value), 400));
    }
    
    // --- FUNGSI PENCARIAN ALAMAT ONGKIR (TETAP) ---
    function selectAddress(prefix, item) {
        const searchInput = document.getElementById(`${prefix}_address_search`);
        const resultsContainer = document.getElementById(`${prefix}_address_results`);
        const checkIcon = document.getElementById(`${prefix}_address_check`);

        searchInput.value = item.full_address;
        const parts = item.full_address.split(',').map(s => s.trim());
        document.getElementById(`${prefix}_village`).value = parts[0] || '';
        document.getElementById(`${prefix}_district`).value = parts[1] || '';
        document.getElementById(`${prefix}_regency`).value = parts[2] || '';
        document.getElementById(`${prefix}_province`).value = parts[3] || '';
        document.getElementById(`${prefix}_postal_code`).value = parts[4] || '';
        document.getElementById(`${prefix}_district_id`).value = item.district_id;
        document.getElementById(`${prefix}_subdistrict_id`).value = item.subdistrict_id;

        resultsContainer.classList.add('hidden');
        checkIcon.classList.remove('hidden');
        
       Swal.fire({
    title: `Alamat ${prefix === 'sender' ? 'Pengirim' : 'Penerima'} Terpilih`,
    html: `Pastikan Detail Alamat Lengkap (Jl. No. RT/RW) Desa/Kelurahan, Kecamatan sudah benar Ya Kak. 
           <br><br>Data Alamat: <b>${item.full_address}</b>
           <br><br>Jika Salah Silahkan Klik Pencarian Google`,
    icon: 'info',

    confirmButtonText: 'Klik Jika Sudah Benar',
    confirmButtonColor: '#d33', // Merah

    showCancelButton: true,
    cancelButtonText: 'Kunjungi Pencarian Google',
    cancelButtonColor: '#3085d6', // Biru tombol Google

    timer: 300000,  // 5 menit
    timerProgressBar: true
}).then((result) => {
    if (result.dismiss === Swal.DismissReason.cancel) {
        // 🔎 BUKA GOOGLE SEARCH OTOMATIS
        const query = encodeURIComponent(item.full_address);
        window.open(`https://www.google.com/search?q=${query}`, '_blank');
    }
});


    }

    async function performAddressSearch(prefix, query, contactToMatch = null) {
        const resultsContainer = document.getElementById(`${prefix}_address_results`);
        
        if (query.length < 3) { 
            resultsContainer.classList.add('hidden'); 
            return; 
        }

        try {
            const response = await fetch(`{{ route('api.address.search') }}?search=${encodeURIComponent(query)}`);
            if (!response.ok) throw new Error('Network response error');
            const data = await response.json();
            
            resultsContainer.innerHTML = '';
            resultsContainer.classList.remove('hidden');

            if (data && data.length > 0) {
                if (contactToMatch) {
                    const exactMatch = data.find(item => {
                        const normalizedApiAddress = item.full_address.toLowerCase();
                        const village = (contactToMatch.village || '').toLowerCase();
                        const district = (contactToMatch.district || '').toLowerCase();
                        const regency = (contactToMatch.regency || '').toLowerCase().replace('kabupaten ', '').replace('kota ', '');
                        const postalCode = (contactToMatch.postal_code || '');

                        return village && district && regency && postalCode &&
                               normalizedApiAddress.includes(village) &&
                               normalizedApiAddress.includes(district) &&
                               normalizedApiAddress.includes(regency) &&
                               normalizedApiAddress.includes(postalCode);
                    });

                    if (exactMatch) {
                        selectAddress(prefix, exactMatch); 
                        return; 
                    }
                }

                if (data.length === 1) {
                    selectAddress(prefix, data[0]);
                    return;
                }

                data.forEach(item => {
                    const resultDiv = document.createElement('div');
                    resultDiv.className = 'p-3 border-b hover:bg-gray-100 cursor-pointer text-sm';
                    resultDiv.innerHTML = `<div class="font-semibold">${item.full_address}</div>`;
                    resultDiv.addEventListener('click', () => {
                        selectAddress(prefix, item);
                    });
                    resultsContainer.appendChild(resultDiv);
                });
            } else {
                resultsContainer.innerHTML = '<div class="p-3 text-gray-500">Alamat tidak ditemukan.</div>';
            }
        } catch (error) {
            console.error('Address search failed:', error);
            resultsContainer.innerHTML = '<div class="p-3 text-red-500">Gagal memuat data alamat.</div>';
        }
    }
    
    function setupAddressSearch(prefix) {
        const searchInput = document.getElementById(`${prefix}_address_search`);
        const checkIcon = document.getElementById(`${prefix}_address_check`);
        
        searchInput.addEventListener('input', debounce(() => {
            checkIcon.classList.add('hidden');
            performAddressSearch(prefix, searchInput.value, null);
        }, 400));
    }
    
    // --- SETUP EVENT LISTENER CHECKBOX AUTOSAVE ---
    const senderSaveCheckbox = document.getElementById('save_sender_checkbox');
    const receiverSaveCheckbox = document.getElementById('save_receiver_checkbox');

    if (senderSaveCheckbox) {
        senderSaveCheckbox.addEventListener('change', function() {
            if (this.checked) {
                saveContactAutosave('sender');
            }
        });
    }

    if (receiverSaveCheckbox) {
        receiverSaveCheckbox.addEventListener('change', function() {
            if (this.checked) {
                saveContactAutosave('receiver');
            }
        });
    }
    // ----------------------------------------------------------------------------------

    // --- INISIALISASI & EVENT LISTENERS ---
    setupContactSearch('sender');
    setupContactSearch('receiver');
    setupAddressSearch('sender');
    setupAddressSearch('receiver');

    const senderAddressInput = document.getElementById('sender_address');
    const senderAddressFeedback = document.getElementById('sender_address_feedback');
    const receiverAddressInput = document.getElementById('receiver_address');
    const receiverAddressFeedback = document.getElementById('receiver_address_feedback');

    senderAddressInput.addEventListener('input', () => {
        validateAddressRealtime(senderAddressInput, senderAddressFeedback, 'Alamat Pengirim');
    });

    receiverAddressInput.addEventListener('input', () => {
        validateAddressRealtime(receiverAddressInput, receiverAddressFeedback, 'Alamat Penerima');
    });
    // ----------------------------------------------------------------------------------

    // --- FUNGSI CEK ONGKIR (TETAP) ---
    async function runCekOngkir() {
        // Panggil validasi real-time sebelum cek ongkir
        validateAddressRealtime(senderAddressInput, senderAddressFeedback, 'Alamat Pengirim');
        validateAddressRealtime(receiverAddressInput, receiverAddressFeedback, 'Alamat Penerima');

        // Cek apakah ada error validasi kustom (>= 10 karakter)
        if (senderAddressInput.value.trim().length < minAddressLength) {
             Swal.fire('Data Belum Lengkap', 'Alamat Pengirim wajib minimal 10 karakter.', 'warning');
             return;
        }
        if (receiverAddressInput.value.trim().length < minAddressLength) {
             Swal.fire('Data Belum Lengkap', 'Alamat Penerima wajib minimal 10 karakter.', 'warning');
             return;
        }

        const requiredFields = { '#sender_subdistrict_id': 'Alamat Pengirim', '#receiver_subdistrict_id': 'Alamat Penerima', '#item_price': 'Harga Barang', '#weight': 'Berat', '#service_type': 'Jenis Layanan', '#ansuransi': 'Asuransi' };
        let missing = Object.keys(requiredFields).filter(s => !document.querySelector(s).value);
        if (missing.length > 0) {
            Swal.fire('Data Belum Lengkap', 'Harap lengkapi: ' + missing.map(s => requiredFields[s]).join(', '), 'warning');
            return;
        }

        const ongkirModalBody = document.getElementById('ongkirModalBody');
        ongkirModalBody.innerHTML = `<div class="text-center p-5"><i class="fas fa-spinner fa-spin text-3xl text-red-600"></i><p class="mt-2 text-gray-500">Memuat tarif...</p></div>`;
        ongkirModalEl.classList.remove('hidden');

        try {
            const formData = new FormData(document.getElementById('orderForm'));
            const params = new URLSearchParams(formData).toString();
            const response = await fetch(`{{ route('kirimaja.cekongkir') }}?${params}`);
            if (!response.ok) {
                const errorData = await response.json();
                throw new Error(errorData.message || 'Gagal mengambil data ongkir');
            }
            const res = await response.json();

            ongkirModalBody.innerHTML = '';
            let results = (res.results || []).concat((res.result || []).flatMap(v => v.costs.map(c => ({...c, service: v.name, service_name: `${v.name.toUpperCase()} - ${c.service_type}`, cost: c.price.total_price, etd: c.estimation || '-', setting: c.setting || {}, insurance: c.price.insurance_fee || 0, cod: c.cod }))));
            if (results.length === 0) {
                ongkirModalBody.innerHTML = '<div class="bg-yellow-100 text-yellow-800 p-4 rounded-md text-center">Layanan pengiriman tidak ditemukan. Cek kembali alamat dan jenis layanan.</div>';
                return;
            }

            results.sort((a, b) => a.cost - b.cost).forEach(item => {
                const isCod = item.cod;
                const insuranceFee = item.insurance || 0;
                const codFee = item.setting?.cod_fee_amount || 0;
                const value = `${document.getElementById('service_type').value}-${item.service}-${item.service_type}-${item.cost}-${insuranceFee}-${codFee}`;
                let details = `<small class="text-gray-500 block">Estimasi: ${item.etd}</small>`;
                if (document.getElementById('ansuransi').value == 'iya' && insuranceFee > 0) details += `<small class="text-gray-500 block">Asuransi: ${formatRupiah(insuranceFee)}</small>`;
                if (isCod && codFee > 0) details += `<small class="text-gray-500 block">Biaya COD: ${formatRupiah(codFee)}</small>`;
                if (isCod) details += `<small class="text-green-600 font-bold block">COD Tersedia</small>`;
                
                const card = document.createElement('div');
                card.className = 'border rounded-lg mb-3 shadow-sm';
                card.innerHTML = `
                    <div class="p-4 flex justify-between items-center">
                        <div class="flex items-center">
                            <img src="{{ asset('public/storage/logo-ekspedisi/') }}/${item.service.toLowerCase().replace(/\s+/g, '')}.png" class="w-16 h-auto mr-4 object-contain" onerror="this.src='https://placehold.co/100x40?text=${item.service}'">
                            <div>
                                <h6 class="font-bold text-gray-800">${item.service_name}</h6>
                                ${details}
                            </div>
                        </div>
                        <div class="text-right">
                            <small class="text-gray-500">Ongkir</small>
                            <strong class="block text-lg text-red-600">${formatRupiah(item.cost)}</strong>
                            <button type="button" class="select-ongkir-btn mt-1 bg-red-600 text-white px-3 py-1 rounded-md hover:bg-red-700 text-sm" data-value="${value}" data-display="${item.service_name}" data-cod-supported="${isCod}">Pilih</button>
                        </div>
                    </div>
                `;
                ongkirModalBody.appendChild(card);
            });
        } catch (error) {
            console.error('Cek Ongkir failed:', error);
            ongkirModalBody.innerHTML = `<div class="bg-red-100 text-red-800 p-4 rounded-md text-center">${error.message}</div>`;
        }
    }

    // --- EVENT LISTENERS ---
    document.getElementById('selected_expedition_display').addEventListener('click', runCekOngkir);

    ongkirModalEl.addEventListener('click', function(e) {
        if (e.target.classList.contains('select-ongkir-btn')) {
            document.getElementById('expedition').value = e.target.dataset.value;
            document.getElementById('selected_expedition_display').value = e.target.dataset.display;
            
            const codOptions = document.querySelectorAll('.cod-payment-option');
            if (e.target.dataset.codSupported === 'true') {
                codOptions.forEach(opt => opt.style.display = 'flex');
            } else {
                if (['COD', 'CODBARANG'].includes(document.getElementById('payment_method').value)) {
                    document.getElementById('payment_method').value = '';
                    document.getElementById('selectedPaymentName').textContent = 'Pilih...';
                    document.getElementById('selectedPaymentLogo').src = 'https://cdn-icons-png.flaticon.com/512/2331/2331941.png';
                }
                codOptions.forEach(opt => opt.style.display = 'none');
            }
            ongkirModalEl.classList.add('hidden');
        }
    });

    document.getElementById('paymentMethodButton').addEventListener('click', () => paymentMethodModal.classList.remove('hidden'));

    document.querySelectorAll('.payment-option').forEach(item => {
        item.addEventListener('click', function() {
            document.getElementById('payment_method').value = this.dataset.value;
            document.getElementById('selectedPaymentName').textContent = this.dataset.label;
            document.getElementById('selectedPaymentLogo').src = this.querySelector('img').src;
            
            document.querySelectorAll('.payment-option').forEach(opt => opt.classList.remove('bg-red-50'));
            this.classList.add('bg-red-50');
            
            paymentMethodModal.classList.add('hidden');
        });
    });

    document.querySelectorAll('.close-modal-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            ongkirModalEl.classList.add('hidden');
            paymentMethodModal.classList.add('hidden');
        });
    });

    document.querySelectorAll('input, select, textarea').forEach(el => {
        if(el.type !== 'hidden' && !el.classList.contains('select-ongkir-btn')){
             el.addEventListener('change', () => {
                 document.getElementById('expedition').value = '';
                 document.getElementById('selected_expedition_display').value = '';
                 document.getElementById('selected_expedition_display').placeholder = 'Data berubah, klik untuk cek ulang';
             });
        }
    });

    document.getElementById('confirmBtn').addEventListener('click', (e) => {
        e.preventDefault();
        const form = document.getElementById('orderForm');
        const expedition = document.getElementById('expedition').value;
        const paymentMethod = document.getElementById('payment_method').value;

        // Pengecekan Klien tambahan untuk alamat min 10 karakter
        let addressError = false;
        // Panggil validasi real-time untuk memastikan feedback kustom muncul/hilang
        validateAddressRealtime(senderAddressInput, senderAddressFeedback, 'Alamat Pengirim');
        validateAddressRealtime(receiverAddressInput, receiverAddressFeedback, 'Alamat Penerima');

        if (senderAddressInput.value.trim().length < minAddressLength) {
             addressError = true;
        }
        if (receiverAddressInput.value.trim().length < minAddressLength) {
             addressError = true;
        }
        
        if (!form.checkValidity() || !expedition || !paymentMethod || addressError) {
            // Memaksa browser menampilkan error validasi HTML5
            form.reportValidity();
            
            let missingFields = [];
            if (!expedition) missingFields.push('Ekspedisi');
            if (!paymentMethod) missingFields.push('Metode Pembayaran');
             if (addressError) missingFields.push('Alamat (Min. 10 Karakter)'); 

            let message = 'Harap lengkapi semua field yang wajib diisi.';
            if (missingFields.length > 0) {
                message += ` Anda belum: ${missingFields.join(', ')}.`;
            }

            Swal.fire('Peringatan', message, 'warning');
            return;
        }

        Swal.fire({
            title: 'Konfirmasi Pesanan',
            text: "Apakah semua data sudah benar?",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#4f46e5',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Ya, Buat Pesanan',
            cancelButtonText: 'Batal'
        }).then((result) => {
            // Menjadi:
if (result.isConfirmed) {
    const confirmBtn = document.getElementById('confirmBtn');
    
    // Nonaktifkan tombol secara visual & fungsional
    confirmBtn.disabled = true;
    confirmBtn.classList.add('opacity-50', 'cursor-not-allowed'); // Tambahkan visual disabled
    confirmBtn.innerHTML = `<i class="fas fa-spinner fa-spin mr-2"></i>Memproses...`;

    // Pastikan form hanya bisa disubmit sekali di frontend
    form.addEventListener('submit', function(e) {
        if (form.hasSubmitted) {
            e.preventDefault();
        } else {
            form.hasSubmitted = true;
        }
    });
    
    // Tandai form sudah disubmit sebelum kirim
    form.hasSubmitted = true;
    form.submit(); 
}
        });
    });

    document.querySelectorAll('.cod-payment-option').forEach(opt => opt.style.display = 'none');

    document.addEventListener('click', function(event) {
        if (!event.target.closest('#sender_address_search, #sender_address_results')) {
            document.getElementById('sender_address_results').classList.add('hidden');
        }
        if (!event.target.closest('#receiver_address_search, #receiver_address_results')) {
            document.getElementById('receiver_address_results').classList.add('hidden');
        }
        
        if (!event.target.closest('#sender_contact_search, #sender_contact_results')) {
            document.getElementById('sender_contact_results').classList.add('hidden');
        }
        if (!event.target.closest('#receiver_contact_search, #receiver_contact_results')) {
            document.getElementById('receiver_contact_results').classList.add('hidden');
        }
    });
});
</script>
@endpush