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

    <div class="bg-white p-6 rounded-lg shadow-md border border-red-700 transition-all duration-200 hover:ring-4 hover:ring-red-400 hover:shadow-lg">

    <div class="relative z-20 bg-red-700 backdrop-blur px-4 py-3 rounded-lg shadow flex items-center justify-between mb-6 border border-transparent transition-all duration-200 hover:shadow-2xl hover:border-red-300 hover:ring-2 hover:ring-red-300">
        
        <h3 class="text-xl font-semibold text-white">
            <i class="fas fa-arrow-up-from-bracket text-white mr-2"></i>
            Informasi Pengirim
        </h3>

        <div class="relative w-1/2">
            <input type="search" 
                id="sender_contact_search"
                class="w-full pl-10 pr-4 py-2 bg-red-50 border border-red-200 rounded-lg text-sm text-gray-900 transition-all duration-200 hover:border-red-400 hover:shadow-lg hover:ring-2 hover:ring-red-200 focus:outline-none focus:border-red-500 focus:ring-4 focus:ring-red-300 focus:shadow-lg"
                placeholder="" 
                autocomplete="off">

            <div class="absolute top-0 left-0 inline-flex items-center p-2 h-full text-red-400">
                <i class="fas fa-search"></i>
            </div>

            <div id="sender_contact_results" class="absolute z-50 w-full bg-white border border-red-300 rounded-lg shadow-lg mt-1 hidden">
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 relative z-10">

        <div class="relative">
            <label for="sender_name" class="block mb-2 text-sm font-medium text-red-900 required-label">
                Nama Pengirim
            </label>

            <input type="search" 
                id="sender_name" 
                name="sender_name"
                class="bg-red-50 border border-red-200 text-gray-900 text-sm rounded-lg block w-full p-2.5 transition-all duration-200 hover:border-red-400 hover:ring-2 hover:ring-red-200 hover:shadow-md focus:outline-none focus:border-red-500 focus:ring-4 focus:ring-red-300 focus:shadow-md @error('sender_name') is-invalid @enderror"
                value="{{ old('sender_name', auth()->user()->nama_lengkap) }}" 
                required 
                autocomplete="off">

            @error('sender_name')
                <div class="invalid-feedback text-sm text-red-600 mt-1">{{ $message }}</div>
            @enderror
        </div>

        <div class="relative">
            <label for="sender_phone" class="block mb-2 text-sm font-medium text-red-900 required-label">
                Nomor HP
            </label>

            <input type="tel" 
                id="sender_phone" 
                name="sender_phone"
                class="bg-red-50 border border-red-200 text-gray-900 text-sm rounded-lg block w-full p-2.5 transition-all duration-200 hover:border-red-400 hover:ring-2 hover:ring-red-200 hover:shadow-md focus:outline-none focus:border-red-500 focus:ring-4 focus:ring-red-300 focus:shadow-md @error('sender_phone') is-invalid @enderror"
                value="{{ old('sender_phone', auth()->user()->no_wa) }}" 
                required 
                autocomplete="off">

            @error('sender_phone')
                <div class="invalid-feedback text-sm text-red-600 mt-1">{{ $message }}</div>
            @enderror
        </div>

        <div class="md:col-span-2 relative">
            <label for="sender_address_search" class="block mb-2 text-sm font-medium text-red-900 required-label">
                Cari Alamat Ongkir (Kec/Kel/Kodepos)
            </label>

            <div class="relative">
                <input type="text" 
                    id="sender_address_search"
                    class="bg-red-50 border border-red-200 text-gray-900 text-sm rounded-lg block w-full p-2.5 pr-8 transition-all duration-200 hover:border-red-400 hover:ring-2 hover:ring-red-200 hover:shadow-md focus:outline-none focus:border-red-500 focus:ring-4 focus:ring-red-300 focus:shadow-md"
                    placeholder="Ketik untuk mencari alamat..." 
                    autocomplete="off">

                <i id="sender_address_check" class="fas fa-check-circle text-green-500 absolute top-1/2 right-3 transform -translate-y-1/2 hidden"></i>
            </div>

            <div id="sender_address_results" class="search-results-container hidden"></div>
        </div>

        <div class="md:col-span-2">
            <label for="sender_address" class="block mb-2 text-sm font-medium text-red-900 required-label">
                Detail Alamat Lengkap Pengirim (Min. 10 Karakter)
            </label>

            <textarea id="sender_address" 
                name="sender_address" 
                rows="3"
                class="bg-red-50 border border-red-200 text-gray-900 text-sm rounded-lg block w-full p-2.5 transition-all duration-200 hover:border-red-400 hover:ring-2 hover:ring-red-200 hover:shadow-md focus:outline-none focus:border-red-500 focus:ring-4 focus:ring-red-300 focus:shadow-md @error('sender_address') is-invalid @enderror"
                placeholder="Contoh: Jl. Pahlawan No. 12, RT 01/RW 05" 
                required>{{ old('sender_address', auth()->user()->address_detail) }}</textarea>

            @error('sender_address')
                <div class="invalid-feedback text-sm text-red-600 mt-1">{{ $message }}</div>
            @enderror

            <div id="sender_address_feedback" class="invalid-feedback text-sm text-red-600 mt-1" style="display:none;">
                Alamat minimal 10 karakter.
            </div>
        </div>

        <div class="md:col-span-2">
            <label class="flex items-center text-sm text-red-900">
                <input type="checkbox" 
                    id="save_sender_checkbox" 
                    name="save_sender" 
                    value="on"
                    class="h-4 w-4 rounded border-red-300 text-red-600 focus:ring-red-500 mr-2 transition-all duration-200">
                Simpan/Perbarui data pengirim ini
            </label>
        </div>

    </div>
</div>


             <div class="bg-white p-6 rounded-lg shadow-md border border-blue-700 transition-all duration-200 hover:ring-4 hover:ring-blue-400 hover:shadow-lg">
    <div class="flex justify-between items-center bg-blue-700 px-4 py-3 mb-6 rounded-lg shadow border border-transparent transition-all duration-200 hover:shadow-2xl hover:border-blue-300 hover:ring-2 hover:ring-blue-300">

        <h3 class="text-xl font-semibold text-white">
            <i class="fas fa-map-marker-alt text-white mr-2"></i>Informasi Penerima
        </h3>
        <div class="relative w-1/2">
            <input type="search" id="receiver_contact_search" 
            class="w-full pl-10 pr-4 py-2 bg-blue-50 border border-blue-200 rounded-lg text-sm text-gray-900 transition-all duration-200 hover:border-blue-400 hover:ring-2 hover:ring-blue-200 hover:shadow-md focus:outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-300 focus:shadow-md" placeholder="Cari Nama Atau No Hp Dari Data penerima" autocomplete="off">
            <div class="absolute top-0 left-0 inline-flex items-center p-2 h-full text-blue-400">
                <i class="fas fa-search"></i>
            </div>
            <div id="receiver_contact_results" class="search-results-container hidden"></div>
        </div>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="relative">
            <label for="receiver_name" class="block mb-2 text-sm font-medium text-blue-800 required-label">Nama Penerima</label>
            <input type="search" id="receiver_name" name="receiver_name" 
            
            class="bg-blue-50 border border-blue-200 text-gray-900 text-sm rounded-lg block w-full p-2.5
            transition-all duration-200
            hover:border-blue-400 hover:ring-2 hover:ring-blue-200 hover:shadow-md
            focus:outline-none
            focus:border-blue-500
            focus:ring-4 focus:ring-blue-300
            focus:shadow-md
            @error('receiver_name') is-invalid @enderror" required autocomplete="off">
            @error('receiver_name')
                <div class="invalid-feedback text-sm text-red-600 mt-1">{{ $message }}</div>
            @enderror
        </div>
        <div class="relative">
            <label for="receiver_phone" class="block mb-2 text-sm font-medium text-blue-800 required-label">Nomor HP</label>
            <input type="tel" id="receiver_phone" name="receiver_phone" class="bg-blue-50 border border-blue-200 text-gray-900 text-sm rounded-lg block w-full p-2.5 transition-all duration-200 hover:border-blue-400 hover:ring-2 hover:ring-blue-200 hover:shadow-md focus:outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-300 focus:shadow-md @error('receiver_phone') is-invalid @enderror" required autocomplete="off">
            @error('receiver_phone')
                <div class="invalid-feedback text-sm text-red-600 mt-1">{{ $message }}</div>
            @enderror
        </div>
        <div class="md:col-span-2 relative">
            <label for="receiver_address_search" class="block mb-2 text-sm font-medium text-blue-800 required-label">Cari Alamat Ongkir (Kec/Kel/Kodepos)</label>
            <div class="relative">
                <input type="text" id="receiver_address_search" class="bg-blue-50 border border-blue-200 text-gray-900 text-sm rounded-lg block w-full p-2.5 pr-8 transition-all duration-200 hover:border-blue-400 hover:ring-2 hover:ring-blue-200 hover:shadow-md focus:outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-300 focus:shadow-md" placeholder="Ketik untuk mencari alamat..." autocomplete="off">
                <i id="receiver_address_check" class="fas fa-check-circle text-green-500 absolute top-1/2 right-3 transform -translate-y-1/2 hidden"></i>
            </div>
            <div id="receiver_address_results" class="search-results-container hidden"></div>
        </div>
        <div class="md:col-span-2">
            <label for="receiver_address" class="block mb-2 text-sm font-medium text-blue-800 required-label">Alamat Penerima Lengkap (Min. 10 Karakter)</label>
            <textarea id="receiver_address" name="receiver_address" rows="3" class="bg-blue-50 border border-blue-200 text-gray-900 text-sm rounded-lg block w-full p-2.5 transition-all duration-200 hover:border-blue-400 hover:ring-2 hover:ring-blue-200 hover:shadow-md focus:outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-300 focus:shadow-md @error('receiver_address') is-invalid @enderror" placeholder="Contoh: Jl. Merdeka No. 45, RT 02/RW 03" required>{{ old('receiver_address') }}</textarea>
            
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
                <label class="flex items-center text-sm text-blue-800"><input type="checkbox" id="save_receiver_checkbox" name="save_receiver" value="on" class="h-4 w-4 rounded border-blue-300 text-blue-600 focus:ring-blue-500 mr-2 transition-all duration-200"> Simpan data penerima ini</label>
         </div>
    </div>
</div>
</div>

            <div class="lg:col-span-1 space-y-8">
               <div class="bg-white p-6 rounded-lg shadow-md sticky top-8
            border border-green-600
            transition-all duration-200
            focus:ring-4 focus:ring-green-400
            hover:border-green-700
            hover:shadow-xl
            hover:ring-4 hover:ring-green-300">
    
    <h3 class="text-xl font-semibold text-white px-4 py-3 mb-6
           bg-green-600 border border-green-700 rounded-lg
           flex items-center gap-2
           transition-all duration-200
           hover:bg-green-700
           hover:border-green-800
           hover:shadow-lg
           hover:ring-2 hover:ring-green-300">
    
    <i class="fas fa-box-open text-white"></i>
    Detail Paket
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
                <li class="payment-option p-4 flex items-center cursor-pointer hover:bg-gray-50" data-value="DOKU_JOKUL" data-label="REKOMENDASI SANCAKA"><img src="https://tokosancaka.com/public/assets/doku.png" class="w-8 h-8 mr-4">Rekomendasi Sancaka Express Via VA, QRIS Dan E-Wallet</li>
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

{{-- 1. TAMBAHKAN INI DI DALAM FORM (ATAU DI ATASNYA) AGAR JS BISA BACA SALDO USER --}}
<input type="hidden" id="user_current_balance" value="{{ Auth::user()->saldo ?? 0 }}">
<input type="hidden" id="selected_expedition_logo_url"> {{-- Penampung URL Logo --}}

<div id="confirmationModal" class="fixed inset-0 bg-gray-900 bg-opacity-80 z-[60] hidden flex items-center justify-center transition-opacity duration-300 backdrop-blur-sm">
    <div class="bg-white w-full max-w-2xl mx-4 rounded-xl shadow-2xl overflow-hidden transform transition-all scale-100 flex flex-col max-h-[90vh]">
        
        <div class="bg-red-600 px-6 py-4 flex justify-between items-center shadow-md shrink-0">
            <div class="flex items-center gap-3">
                <div class="bg-white p-1 rounded-md h-12 w-20 flex items-center justify-center overflow-hidden">
                    <img id="confirm_expedition_logo" src="" alt="Logo" class="max-h-full max-w-full object-contain">
                </div>
                <div>
                    <h3 class="text-lg font-bold text-white leading-tight">Konfirmasi Pengiriman</h3>
                    <p class="text-red-100 text-xs" id="confirm_expedition_name">-</p>
                </div>
            </div>
            <button type="button" onclick="closeConfirmationModal()" class="text-white hover:text-red-200 text-2xl font-bold">&times;</button>
        </div>

        <div class="p-6 overflow-y-auto custom-scrollbar flex-grow">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                
                <div class="bg-gray-50 p-4 rounded-lg border border-gray-200 shadow-sm">
                    <h4 class="text-xs font-bold text-gray-500 uppercase mb-3 border-b pb-1">Rincian Biaya</h4>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Ongkir Dasar</span>
                            <span class="font-semibold" id="detail_cost_base">Rp 0</span>
                        </div>
                        <div class="flex justify-between hidden" id="row_detail_insurance">
                            <span class="text-gray-600">Asuransi</span>
                            <span class="font-semibold" id="detail_cost_insurance">Rp 0</span>
                        </div>
                        <div class="flex justify-between hidden" id="row_detail_item_price">
                            <span class="text-gray-600">Harga Barang (COD)</span>
                            <span class="font-semibold text-blue-600" id="detail_cost_item">Rp 0</span>
                        </div>
                        <div class="flex justify-between hidden" id="row_detail_cod">
                            <span class="text-gray-600">Biaya Layanan COD</span>
                            <span class="font-semibold" id="detail_cost_cod">Rp 0</span>
                        </div>
                        <div class="border-t border-gray-300 my-2 pt-2 flex justify-between items-center">
                            <span class="font-bold text-red-600">TOTAL BAYAR</span>
                            <span class="font-extrabold text-xl text-red-600" id="confirm_total_final">Rp 0</span>
                        </div>
                    </div>
                </div>

                <div class="space-y-3">
                    <div class="bg-blue-50 p-3 rounded-lg border border-blue-100">
                        <p class="text-xs text-blue-500 mb-1">Metode Pembayaran</p>
                        <p class="font-bold text-blue-800 text-lg flex items-center gap-2">
                            <i class="fas fa-wallet"></i> <span id="confirm_payment_method">-</span>
                        </p>
                    </div>

                    <div id="balance_simulation_box" class="hidden bg-green-50 p-3 rounded-lg border border-green-100 text-sm">
                        <div class="flex justify-between mb-1">
                            <span class="text-gray-600">Saldo Awal:</span>
                            <span class="font-semibold" id="sim_initial_balance">Rp 0</span>
                        </div>
                        <div class="flex justify-between mb-1 text-red-600">
                            <span>Tagihan:</span>
                            <span>- <span id="sim_bill_amount">Rp 0</span></span>
                        </div>
                        <div class="border-t border-green-200 mt-2 pt-2 flex justify-between font-bold text-green-800">
                            <span>Sisa Saldo:</span>
                            <span id="sim_final_balance">Rp 0</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-4 relative">
                <div class="hidden md:block absolute left-1/2 top-0 bottom-0 w-px bg-gray-200 transform -translate-x-1/2"></div>
                
                <div>
                    <h4 class="text-xs font-bold text-red-500 uppercase mb-2 flex items-center gap-2">
                        <i class="fas fa-arrow-up bg-red-100 p-1 rounded"></i> Pengirim
                    </h4>
                    <div class="pl-2 border-l-2 border-red-200 text-sm">
                        <p class="font-bold text-gray-800" id="confirm_sender_name">-</p>
                        <p class="text-xs text-red-500 mb-1" id="confirm_sender_phone">-</p>
                        <p class="text-gray-600 leading-snug" id="confirm_sender_address">-</p>
                    </div>
                </div>

                <div>
                    <h4 class="text-xs font-bold text-blue-500 uppercase mb-2 flex items-center gap-2">
                         <i class="fas fa-arrow-down bg-blue-100 p-1 rounded"></i> Penerima
                    </h4>
                    <div class="pl-2 border-l-2 border-blue-200 text-sm">
                        <p class="font-bold text-gray-800" id="confirm_receiver_name">-</p>
                        <p class="text-xs text-blue-500 mb-1" id="confirm_receiver_phone">-</p>
                        <p class="text-gray-600 leading-snug" id="confirm_receiver_address">-</p>
                    </div>
                </div>
            </div>

            <div class="bg-gray-50 rounded p-3 text-sm grid grid-cols-3 gap-2 border border-gray-200 items-center">
                <div class="text-left">
                    <span class="text-gray-500 text-xs block">Isi Paket:</span>
                    <span class="font-semibold block truncate uppercase" id="confirm_item_desc">-</span>
                </div>
                <div class="text-center border-l border-r border-gray-200 px-2">
                    <span class="text-gray-500 text-xs block">Dimensi (PxLxT):</span>
                    <span class="font-semibold block" id="confirm_dimensions">-</span>
                </div>
                <div class="text-right">
                    <span class="text-gray-500 text-xs block">Berat:</span>
                    <span class="font-semibold block" id="confirm_weight">- Gram</span>
                </div>
            </div>
            
             <div class="mt-4 bg-yellow-50 p-3 rounded text-xs text-yellow-800 border border-yellow-200 flex items-start gap-2">
                <i class="fas fa-exclamation-triangle mt-0.5 flex-shrink-0"></i>
                <span>Pastikan data alamat sudah benar. Kesalahan input bukan tanggung jawab sistem.</span>
            </div>
        </div>

        <div class="px-6 py-4 bg-gray-50 border-t flex flex-col-reverse sm:flex-row gap-3 sm:justify-end shrink-0">
            <button type="button" onclick="closeConfirmationModal()" class="w-full sm:w-auto px-5 py-2.5 bg-white border border-gray-300 rounded-lg text-gray-700 font-medium hover:bg-gray-100 transition">Perbaiki Data</button>
            <button type="button" onclick="submitFinalForm()" class="w-full sm:w-auto px-6 py-2.5 bg-red-600 text-white rounded-lg font-bold hover:bg-red-700 transition flex items-center justify-center gap-2 shadow-lg">
                <span>Lanjut Kirim</span> <i class="fas fa-paper-plane"></i>
            </button>
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
    // --- FUNGSI AUTOSAVE KONTAK VIA AJAX (UPDATED) ---
    function saveContactAutosave(prefix) {
        const addressSearchInput = document.getElementById(`${prefix}_address_search`);

        // Ambil ID user yang sedang login dari blade (ditaruh di meta tag atau echo langsung)
        // Cara paling aman di script blade adalah pakai {{ Auth::id() }}
        const authUserId = "{{ Auth::id() }}"; 

        const contactData = {
            _token: document.querySelector('input[name="_token"]').value,

            // Data Identitas
            prefix: prefix,
            user_id: authUserId, // <--- INI TAMBAHANNYA (User ID Auth)
            
            // Data Input Form
            [`${prefix}_name`]: document.getElementById(`${prefix}_name`).value,
            [`${prefix}_phone`]: document.getElementById(`${prefix}_phone`).value,
            [`${prefix}_address`]: document.getElementById(`${prefix}_address`).value,
            
            // Data Wilayah
            [`${prefix}_province`]: document.getElementById(`${prefix}_province`).value,
            [`${prefix}_regency`]: document.getElementById(`${prefix}_regency`).value,
            [`${prefix}_district`]: document.getElementById(`${prefix}_district`).value,
            [`${prefix}_village`]: document.getElementById(`${prefix}_village`).value,
            [`${prefix}_postal_code`]: document.getElementById(`${prefix}_postal_code`).value,
            
            // Hidden ID untuk Update (Jika ada)
            id: document.getElementById(`${prefix}_id`).value,
            
            // Tipe Kontak
            tipe: (prefix === 'sender' ? 'Pengirim' : 'Penerima')
        };

        // Cek Validasi Client-Side Sederhana
        if (!contactData[`${prefix}_name`] || !contactData[`${prefix}_phone`] || contactData[`${prefix}_address`].length < 10 || !addressSearchInput.value) {
            Swal.fire('Peringatan', `Data ${contactData.tipe} (Nama, HP, Alamat Detail min 10 karakter, dan Alamat Ongkir) wajib diisi lengkap sebelum disimpan.`, 'warning');
            return;
        }

        // Kirim ke Controller
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
            Swal.close(); // Tutup loading jika ada

            if (data.status === 'success') {
                // --- SKENARIO SUKSES ---
                if (data.contact_id) {
                     document.getElementById(`${prefix}_id`).value = data.contact_id;
                }
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil',
                    text: `${contactData.tipe} berhasil disimpan ke Buku Alamat.`,
                    timer: 1500,
                    showConfirmButton: false
                });

            } else {
                // --- SKENARIO GAGAL / DUPLIKAT ---
                
                // Cek apakah server mengirim data kontak lama (kasus duplikat)
                if (data.existing_contact) {
                    const oldContact = data.existing_contact;

                    // 1. UPDATE HIDDEN ID (KUNCI UTAMA)
                    // Ini membuat klik 'Simpan' berikutnya akan dianggap UPDATE, bukan CREATE baru
                    document.getElementById(`${prefix}_id`).value = oldContact.id;

                    // 2. OPSIONAL: Tampilkan info ke user
                    Swal.fire({
                        icon: 'info',
                        title: 'Nomor HP Terdaftar!',
                        html: `Nomor <b>${oldContact.no_hp}</b> sudah tersimpan atas nama <b>${oldContact.nama}</b>.<br><br>
                               Sistem otomatis menghubungkan formulir ini dengan data tersebut.<br>
                               <small class="text-gray-500">Klik simpan lagi untuk memperbarui data lama dengan data baru ini.</small>`,
                        confirmButtonText: 'Oke, Mengerti'
                    });

                } else {
                    // Error Murni (Validasi lain atau Server Error)
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal',
                        text: data.message || 'Terjadi kesalahan server.',
                    });
                    
                    // Uncheck checkbox karena gagal
                    const cb = document.getElementById(`save_${prefix}_checkbox`);
                    if(cb) cb.checked = false;
                }
            }
        })
        .catch(error => {
            console.error('AJAX Save Error:', error);
            Swal.fire('Error', 'Gagal terhubung ke server.', 'error');
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
    
    function selectAddress(prefix, item) {
    // 1. Debugging: Cek di Console browser apa isi data sebenarnya
    console.log("Data Alamat Diterima:", item);

    const searchInput = document.getElementById(`${prefix}_address_search`);
    const resultsContainer = document.getElementById(`${prefix}_address_results`);
    const checkIcon = document.getElementById(`${prefix}_address_check`);

    // 2. SAFETY CHECK: Cari string alamat di berbagai kemungkinan properti
    // API KiriminAja kadang pakai 'label', kadang 'value', kadang 'full_address'
    let addressString = item.full_address || item.label || item.value || '';

    // Jika addressString masih kosong, stop fungsi agar tidak crash
    if (!addressString) {
        console.error("Error: String alamat tidak ditemukan pada item:", item);
        return; 
    }

    // Isi input pencarian
    searchInput.value = addressString;

    // 3. LOGIKA PEMETAAN DATA (Mapping)
    // Kita coba ambil data detail langsung dari objek jika ada (lebih akurat daripada split)
    if (item.data_lengkap) {
        // Jika API mengembalikan properti 'data_lengkap' (seperti screenshot awal Anda)
        document.getElementById(`${prefix}_village`).value = item.data_lengkap.village || '';
        document.getElementById(`${prefix}_district`).value = item.data_lengkap.district || '';
        document.getElementById(`${prefix}_regency`).value = item.data_lengkap.regency || '';
        document.getElementById(`${prefix}_province`).value = item.data_lengkap.province || '';
        document.getElementById(`${prefix}_postal_code`).value = item.data_lengkap.postal_code || '';
        // ID Wilayah seringkali ada di root object atau data_lengkap
        document.getElementById(`${prefix}_district_id`).value = item.district_id || item.data_lengkap.district_id || '';
        document.getElementById(`${prefix}_subdistrict_id`).value = item.subdistrict_id || item.data_lengkap.subdistrict_id || '';
    } 
    else {
        // FALLBACK: Jika tidak ada data_lengkap, baru kita coba split string (Cara Lama)
        // Pastikan addressString ada isinya sebelum di-split
        if (typeof addressString === 'string') {
            const parts = addressString.split(',').map(s => s.trim());
            // Sesuaikan urutan array ini dengan format teks alamat dari API Anda
            document.getElementById(`${prefix}_village`).value = parts[0] || '';
            document.getElementById(`${prefix}_district`).value = parts[1] || '';
            document.getElementById(`${prefix}_regency`).value = parts[2] || '';
            document.getElementById(`${prefix}_province`).value = parts[3] || '';
            document.getElementById(`${prefix}_postal_code`).value = parts[4] || '';
        }
        
        // Mapping ID (fallback)
        document.getElementById(`${prefix}_district_id`).value = item.district_id || '';
        document.getElementById(`${prefix}_subdistrict_id`).value = item.subdistrict_id || '';
    }

    // Sembunyikan hasil pencarian & tampilkan centang hijau
    if(resultsContainer) resultsContainer.classList.add('hidden');
    if(checkIcon) checkIcon.classList.remove('hidden');

    // Tampilkan SweetAlert Konfirmasi (Kode Anda sebelumnya)
    Swal.fire({
        title: `Alamat ${prefix === 'sender' ? 'Pengirim' : 'Penerima'} Terpilih`,
        html: `Pastikan Detail Alamat Lengkap (Jl. No. RT/RW) Desa/Kelurahan, Kecamatan sudah benar.<br><br>Data: <b>${addressString}</b>`,
        icon: 'info',
        confirmButtonText: 'Data Sudah Benar',
        confirmButtonColor: '#10B981', // Hijau
        showCancelButton: true,
        cancelButtonText: 'Cari di Google Maps',
        cancelButtonColor: '#3B82F6' // Biru
    }).then((result) => {
        if (result.dismiss === Swal.DismissReason.cancel) {
            const query = encodeURIComponent(addressString);
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
                    // --- PERBAIKAN 1: Mencegah Crash .toLowerCase() ---
                    // Ambil teks dari label, value, atau full_address. Jika semua kosong, pakai string kosong.
                    const rawAddress = item.label || item.value || item.full_address || ''; 
                    const normalizedApiAddress = rawAddress.toLowerCase();

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
                
                // --- PERBAIKAN 2: Mengatasi tampilan "undefined" ---
                // Gunakan label (karena itu yang dikirim API), fallback ke value atau full_address
                const displayText = item.label || item.value || item.full_address || 'Alamat tidak diketahui';
                
                resultDiv.innerHTML = `<div class="font-semibold">${displayText}</div>`;
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
                const logoUrl = `{{ asset('public/storage/logo-ekspedisi/') }}/${item.service.toLowerCase().replace(/\s+/g, '')}.png`;
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

<button type="button" class="select-ongkir-btn mt-1 bg-red-600 text-white px-3 py-1 rounded-md hover:bg-red-700 text-sm" 
    data-value="${value}" 
    data-display="${item.service_name}" 
    data-cod-supported="${isCod}"
    data-logo="${logoUrl}"> Pilih
</button>
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
            document.getElementById('selected_expedition_logo_url').value = e.target.dataset.logo;
            
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

    // --- PERBAIKAN LOGIKA TOMBOL KONFIRMASI ---
    document.getElementById('confirmBtn').addEventListener('click', (e) => {
        e.preventDefault();
        const form = document.getElementById('orderForm');
        
        // 1. Validasi Input Dasar
        const expedition = document.getElementById('expedition').value;
        const paymentMethod = document.getElementById('payment_method').value;
        
        // Pengecekan Klien tambahan untuk alamat min 10 karakter
        let addressError = false;
        validateAddressRealtime(senderAddressInput, senderAddressFeedback, 'Alamat Pengirim');
        validateAddressRealtime(receiverAddressInput, receiverAddressFeedback, 'Alamat Penerima');

        if (senderAddressInput.value.trim().length < minAddressLength) addressError = true;
        if (receiverAddressInput.value.trim().length < minAddressLength) addressError = true;
        
        if (!form.checkValidity() || !expedition || !paymentMethod || addressError) {
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

        // 2. JIKA VALID, PANGGIL FUNGSI BUKA MODAL (BUKAN SWAL)
        openConfirmationModal();
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


const text = "Cari Nama Atau No Hp Dari Database";
let index = 0;
const input = document.getElementById("sender_contact_search");

function type() {
    input.placeholder = text.slice(0, index);
    index++;

    if (index <= text.length) {
        setTimeout(type, 60); // kecepatan mengetik
    } else {
        setTimeout(() => {
            index = 0;
            type(); // ulangi dari awal
        }, 3000); // jeda ketika sudah selesai
    }
}

type();

  function formatRupiah(angka) { 
        return 'Rp ' + (parseInt(angka, 10) || 0).toLocaleString('id-ID'); 
    }

    // --- FUNGSI GLOBAL UNTUK MODAL KONFIRMASI (VERSI REVISI LOGIKA BIAYA) ---

// --- HELPER UNTUK MENGHINDARI ERROR CSS CONFLICT ---
function showRow(elementId) {
    const el = document.getElementById(elementId);
    if(el) {
        el.classList.remove('hidden'); // Hapus hidden dulu
        el.classList.add('flex');      // Baru tambah flex
    }
}

function hideRow(elementId) {
    const el = document.getElementById(elementId);
    if(el) {
        el.classList.remove('flex');   // Hapus flex dulu
        el.classList.add('hidden');    // Baru tambah hidden
    }
}

// --- FUNGSI BUKA MODAL FINAL (FIXED: UPDATE VALUE INPUT) ---
function openConfirmationModal() {
    // 1. DATA INPUT DASAR
    const senderAddress = document.getElementById('sender_address').value;
    const receiverAddress = document.getElementById('receiver_address').value;
    const paymentMethodVal = document.getElementById('payment_method').value;
    const logoUrl = document.getElementById('selected_expedition_logo_url').value;

    // 2. AMBIL HARGA BARANG (Sanitize: Hapus titik/koma)
    const rawPrice = document.getElementById('item_price').value;
    const itemValue = parseInt(rawPrice.replace(/[^0-9]/g, '')) || 0;

    // 3. PARSING DATA ONGKIR (String dari API)
    // Format: ServiceType-Ekspedisi-Layanan-Ongkir-Asuransi-CodFee
    const rawExpedition = document.getElementById('expedition').value;
    const parts = rawExpedition.split('-');
    
    const shippingCost = parseInt(parts[3]) || 0; 
    const insuranceAmount = parseInt(parts[4]) || 0;
    let codFee = parseInt(parts[5]) || 0; // Ini seringkali 0 dari API

    // Cek Jenis Pembayaran
    const isCODBarang = (paymentMethodVal === 'CODBARANG'); 
    const isCODRegular = (paymentMethodVal === 'COD');      

    // --- 4. LOGIKA HITUNG MANUAL (WAJIB KARENA API RETURN 0) ---
    // Jika metode COD tapi fee dari API 0, kita hitung manual di sini
    if ((isCODBarang || isCODRegular) && codFee === 0) {
        // KONFIGURASI RATE (3% Min 2500)
        const codRate = 0.03;   
        const minCodFee = 2500; 

        // Hitung Basis: Ongkir + Asuransi + (Harga Barang jika COD Barang)
        let basisPerhitungan = shippingCost + insuranceAmount;
        if(isCODBarang) {
            basisPerhitungan += itemValue;
        }

        // Rumus Fee
        let manualFee = Math.ceil(basisPerhitungan * codRate);
        if (manualFee < minCodFee) manualFee = minCodFee;

        codFee = manualFee; // Update variabel lokal dengan hasil hitungan
    }
    
    // Reset Fee jadi 0 jika BUKAN metode COD (Transfer/Saldo)
    if (!isCODBarang && !isCODRegular) {
        codFee = 0;
    }

    // =========================================================================
    // [BAGIAN PERBAIKAN UTAMA] 
    // Update Value Input Hidden 'expedition' agar terkirim ke Controller
    // =========================================================================
    
    // 1. Masukkan fee hasil hitungan ke array data (index ke-5)
    parts[5] = codFee; 
    
    // 2. Gabungkan kembali jadi string (Contoh: "regular-sicepat-halu-10000-0-2500")
    const newExpeditionString = parts.join('-');
    
    // 3. Timpa value di elemen HTML
    document.getElementById('expedition').value = newExpeditionString;
    
    // (Opsional) Jika punya input khusus cod_fee, isi juga
    const inputFeeKhusus = document.getElementById('final_cod_fee');
    if(inputFeeKhusus) inputFeeKhusus.value = codFee;

    console.log("Data Terkirim ke Server:", newExpeditionString);
    // =========================================================================


    // --- 5. HITUNG TOTAL BAYAR VISUAL ---
    let totalBayar = shippingCost + insuranceAmount + codFee;
    if (isCODBarang) {
        totalBayar += itemValue;
    }

    // --- 6. UPDATE TAMPILAN MODAL (UI) ---
    document.getElementById('confirm_expedition_name').innerText = document.getElementById('selected_expedition_display').value;
    document.getElementById('confirm_expedition_logo').src = logoUrl || 'https://placehold.co/100x40?text=Kurir';

    // Format Alamat
    const sVals = ['village','district','regency','province','postal_code'].map(id => document.getElementById('sender_'+id).value || '');
    const sFull = `${senderAddress}, ${sVals.filter(Boolean).join(', ')}`;
    const rVals = ['village','district','regency','province','postal_code'].map(id => document.getElementById('receiver_'+id).value || '');
    const rFull = `${receiverAddress}, ${rVals.filter(Boolean).join(', ')}`;

    document.getElementById('confirm_sender_name').innerText = document.getElementById('sender_name').value;
    document.getElementById('confirm_sender_phone').innerText = document.getElementById('sender_phone').value;
    document.getElementById('confirm_sender_address').innerText = sFull;

    document.getElementById('confirm_receiver_name').innerText = document.getElementById('receiver_name').value;
    document.getElementById('confirm_receiver_phone').innerText = document.getElementById('receiver_phone').value;
    document.getElementById('confirm_receiver_address').innerText = rFull;

    // Detail Paket
    document.getElementById('confirm_item_desc').innerText = document.getElementById('item_description').value;
    document.getElementById('confirm_weight').innerText = document.getElementById('weight').value + ' gram';
    
    const p = document.getElementById('length').value;
    const l = document.getElementById('width').value;
    const t = document.getElementById('height').value;
    document.getElementById('confirm_dimensions').innerText = (p && l && t) ? `${p} x ${l} x ${t} cm` : '-';
    document.getElementById('confirm_payment_method').innerText = document.getElementById('selectedPaymentName').innerText;

    // Rincian Biaya UI
    document.getElementById('detail_cost_base').innerText = formatRupiah(shippingCost);
    document.getElementById('detail_cost_insurance').innerText = formatRupiah(insuranceAmount);
    (insuranceAmount > 0) ? showRow('row_detail_insurance') : hideRow('row_detail_insurance');

    document.getElementById('detail_cost_item').innerText = formatRupiah(itemValue);
    (isCODBarang && itemValue > 0) ? showRow('row_detail_item_price') : hideRow('row_detail_item_price');

    // Tampilkan Fee COD di Modal
    document.getElementById('detail_cost_cod').innerText = formatRupiah(codFee);
    if (codFee > 0 && (isCODBarang || isCODRegular)) {
        showRow('row_detail_cod'); 
    } else {
        hideRow('row_detail_cod');
    }

    document.getElementById('confirm_total_final').innerText = formatRupiah(totalBayar);

    // Simulasi Saldo
    const balBox = document.getElementById('balance_simulation_box');
    if (paymentMethodVal === 'Potong Saldo') {
        const curBal = parseInt(document.getElementById('user_current_balance').value) || 0;
        const remBal = curBal - totalBayar;
        
        document.getElementById('sim_initial_balance').innerText = formatRupiah(curBal);
        document.getElementById('sim_bill_amount').innerText = formatRupiah(totalBayar);
        document.getElementById('sim_final_balance').innerText = formatRupiah(remBal);
        
        const finalEl = document.getElementById('sim_final_balance');
        if(remBal < 0) {
            finalEl.className = "font-bold text-red-600";
            finalEl.innerText += " (Saldo Kurang)";
        } else {
            finalEl.className = "font-bold text-green-800";
        }
        balBox.classList.remove('hidden');
    } else {
        balBox.classList.add('hidden');
    }

    // Animasi Buka Modal
    const modal = document.getElementById('confirmationModal');
    modal.classList.remove('hidden');
    const modalBox = modal.querySelector('div');
    setTimeout(() => {
        modalBox.classList.remove('scale-95', 'opacity-0');
        modalBox.classList.add('scale-100', 'opacity-100');
    }, 10);
}

// --- FUNGSI TUTUP MODAL ---
function closeConfirmationModal() {
    const modal = document.getElementById('confirmationModal');
    // Langsung sembunyikan untuk mencegah stuck
    modal.classList.add('hidden');
    
    // Reset state kotak putih untuk animasi berikutnya
    const modalBox = modal.querySelector('div');
    modalBox.classList.remove('scale-100', 'opacity-100');
    modalBox.classList.add('scale-95', 'opacity-0');
}

function submitFinalForm() {
    const form = document.getElementById('orderForm');
    const btn = document.querySelector('#confirmationModal button[onclick="submitFinalForm()"]');
    
    // Visual Loading pada tombol modal
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-circle-notch fa-spin mr-2"></i> Mengirim...';
    
    // Submit form yang sebenarnya
    form.submit();
}

</script>
@endpush