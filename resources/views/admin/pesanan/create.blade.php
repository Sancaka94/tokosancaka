@extends('layouts.admin')

@section('title', 'Tambah Pesanan Baru')
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
    /* Style untuk tombol yang non-aktif */
    #confirmBtn:disabled {
        background-color: #bebebeff; /* gray-400 */
        cursor: not-allowed;
    }
</style>
@endpush

@section('content')

@include('layouts.partials.notifications')

<div class="max-w-7xl mx-auto">
    <form id="orderForm" action="{{ route('admin.pesanan.store') }}" method="POST">
        @csrf
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

            <!-- Kolom Kiri: Pengirim & Penerima -->
            <div class="lg:col-span-2 space-y-8">

                <!-- Informasi Pengirim -->
                <div class="bg-white p-6 rounded-lg shadow-md">
                    <div class="flex justify-between items-center border-b pb-4 mb-6">
                        <h3 class="text-xl font-semibold text-gray-800">
                            <i class="fas fa-arrow-up-from-bracket text-red-500 mr-2"></i>Informasi Pengirim
                        </h3>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                        <div class="relative">
                            <label for="sender_name" class="block mb-2 text-sm font-medium text-gray-700">Nama Pengirim</label>
                            <input type="text" id="sender_name" name="sender_name" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5" required autocomplete="off">
                            <div id="sender_contact_results" class="search-results-container hidden"></div>
                        </div>
                        <div class="relative">
                            <label for="sender_phone" class="block mb-2 text-sm font-medium text-gray-700">Nomor HP</label>
                            <input type="tel" id="sender_phone" name="sender_phone" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5" required autocomplete="off">
                        </div>
                        <div class="md:col-span-2 relative">
                            <label for="sender_address_search" class="block mb-2 text-sm font-medium text-gray-700">Cari Alamat Ongkir (Kec/Kel/Kodepos)</label>
                            <div class="relative">
                                <input type="text" id="sender_address_search" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5 pr-8" placeholder="Ketik untuk mencari alamat..." autocomplete="off">
                                <i id="sender_address_check" class="fas fa-check-circle text-green-500 absolute top-1/2 right-3 transform -translate-y-1/2 hidden"></i>
                            </div>
                            <div id="sender_address_results" class="search-results-container hidden"></div>
                        </div>
                        <div class="md:col-span-2">
                            <label for="sender_address" class="block mb-2 text-sm font-medium text-gray-700">Detail Alamat Lengkap Pengirim</label>
                            <textarea id="sender_address" name="sender_address" rows="3" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5" placeholder="Contoh: Jl. Pahlawan No. 12, RT 01/RW 05, (Patokan: Sebelah Kantor Pos)" required></textarea>
                        </div>
                          <div class="md:col-span-2">
                                <label class="flex items-center text-sm text-gray-600"><input type="checkbox" name="save_sender" value="1" class="h-4 w-4 rounded border-gray-300 text-red-600 focus:ring-red-500 mr-2"> Simpan data pengirim ini</label>
                          </div>
                    </div>
                </div>

                <!-- Informasi Penerima -->
                <div class="bg-white p-6 rounded-lg shadow-md">
                    <div class="flex justify-between items-center border-b pb-4 mb-6">
                        <h3 class="text-xl font-semibold text-gray-800">
                            <i class="fas fa-map-marker-alt text-green-500 mr-2"></i>Informasi Penerima
                        </h3>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="relative">
                            <label for="receiver_name" class="block mb-2 text-sm font-medium text-gray-700">Nama Penerima</label>
                            <input type="text" id="receiver_name" name="receiver_name" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5" required autocomplete="off">
                            <div id="receiver_contact_results" class="search-results-container hidden"></div>
                        </div>
                        <div class="relative">
                            <label for="receiver_phone" class="block mb-2 text-sm font-medium text-gray-700">Nomor HP</label>
                            <input type="tel" id="receiver_phone" name="receiver_phone" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5" required autocomplete="off">
                        </div>
                        <div class="md:col-span-2 relative">
                            <label for="receiver_address_search" class="block mb-2 text-sm font-medium text-gray-700">Cari Alamat Ongkir (Kec/Kel/Kodepos)</label>
                            <div class="relative">
                                <input type="text" id="receiver_address_search" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5 pr-8" placeholder="Ketik untuk mencari alamat..." autocomplete="off">
                                <i id="receiver_address_check" class="fas fa-check-circle text-green-500 absolute top-1/2 right-3 transform -translate-y-1/2 hidden"></i>
                            </div>
                            <div id="receiver_address_results" class="search-results-container hidden"></div>
                        </div>
                        <div class="md:col-span-2">
                            <label for="receiver_address" class="block mb-2 text-sm font-medium text-gray-700">Alamat Penerima Lengkap</label>
                            <textarea id="receiver_address" name="receiver_address" rows="3" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5" placeholder="Contoh: Jl. Pahlawan No. 12, RT 01/RW 05, (Patokan: Sebelah Kantor Pos)" required></textarea>
                        </div>
                          <div class="md:col-span-2">
                                <label class="flex items-center text-sm text-gray-600"><input type="checkbox" name="save_receiver" value="1" class="h-4 w-4 rounded border-gray-300 text-red-600 focus:ring-red-500 mr-2"> Simpan data penerima ini</label>
                          </div>
                    </div>
                </div>

            </div>

            <!-- Kolom Kanan: Detail Paket & Pembayaran -->
            <div class="lg:col-span-1 space-y-8">
                <div class="bg-white p-6 rounded-lg shadow-md sticky top-8">
                    <h3 class="text-xl font-semibold text-gray-800 border-b pb-4 mb-6">
                        <i class="fas fa-box-open text-yellow-500 mr-2"></i>Detail Paket
                    </h3>
                    <div class="space-y-4">
                        <div>
                            <label for="item_description" class="block mb-2 text-sm font-medium text-gray-700">Deskripsi Barang</label>
                            <input type="text" id="item_description" name="item_description" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5" required>
                        </div>
                        <div>
                            <label for="item_price" class="block mb-2 text-sm font-medium text-gray-700">Harga Barang (Rp)</label>
                            <input type="number" name="item_price" id="item_price" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5" required min="1">
                        </div>
                        <div>
                            <label for="weight" class="block mb-2 text-sm font-medium text-gray-700">Berat (gram)</label>
                            <input type="number" id="weight" name="weight" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5" required min="1">
                        </div>
                        <div class="grid grid-cols-3 gap-4">
                            <div><label for="length" class="block mb-2 text-sm font-medium text-gray-700">P (cm)</label><input type="number" id="length" name="length" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5"></div>
                            <div><label for="width" class="block mb-2 text-sm font-medium text-gray-700">L (cm)</label><input type="number" id="width" name="width" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5"></div>
                            <div><label for="height" class="block mb-2 text-sm font-medium text-gray-700">T (cm)</label><input type="number" id="height" name="height" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5"></div>
                        </div>
                         <div>
                            <label for="item_type" class="block mb-2 text-sm font-medium text-gray-700">Jenis Barang</label>

<select name="item_type" id="item_type"
    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5"
    required>
    <option value="" disabled selected>Pilih...</option>
    <option value="1">Elektronik</option>
    <option value="2">Pakaian</option>
    <option value="3">Pecah Belah</option>
    <option value="4">Dokumen</option>
    <option value="5">Peralatan Rumah Tangga</option>
    <option value="6">Aksesoris</option>
    <option value="7">Kosmetik & Perawatan</option>
    <option value="8">Makanan / Minuman</option>
    <option value="9">Buku & Alat Tulis</option>
    <option value="10">Mainan / Hobi</option>
    <option value="11">Obat-obatan / Suplemen</option>
    <option value="12">Sparepart / Komponen</option>
    <option value="13">Alat Olahraga</option>
    <option value="14">Alat Musik</option>
    <option value="15">Perhiasan / Jam Tangan</option>
    <option value="16">Alat Kesehatan</option>
    <option value="17">Lainnya</option>
</select>

                        </div>
                        <div>
                            <label for="service_type" class="block mb-2 text-sm font-medium text-gray-700">Jenis Layanan</label>
                            <select name="service_type" id="service_type" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5" required>
                                <option value="" disabled selected>Pilih...</option>
                                <option value="regular">Regular</option><option value="express">Express</option><option value="sameday">Sameday</option>
                                <option value="instant">Instant</option><option value="cargo">Cargo</option>
                            </select>
                        </div>
                        <div>
                            <label for="ansuransi" class="block mb-2 text-sm font-medium text-gray-700">Asuransi</label>
                            <select name="ansuransi" id="ansuransi" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5" required>
                                <option value="tidak" selected>Tidak</option><option value="iya">Iya</option>
                            </select>
                        </div>
                        <hr/>
                        <div>
                            <label for="selected_expedition_display" class="block mb-2 text-sm font-medium text-gray-700">Pilih Ekspedisi</label>
                            <input type="text" id="selected_expedition_display" class="cursor-pointer bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5 text-center font-semibold" placeholder="Lengkapi data & klik di sini" readonly required>
                        </div>
                        <div>
                            <label for="paymentMethodButton" class="block mb-2 text-sm font-medium text-gray-700">Metode Pembayaran</label>
                            <div id="paymentMethodButton" class="cursor-pointer bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg w-full p-2.5 flex justify-between items-center">
                                <div class="flex items-center"><img id="selectedPaymentLogo" src="https://cdn-icons-png.flaticon.com/512/2331/2331941.png" alt="Logo" class="w-6 h-6 mr-2"><span id="selectedPaymentName">Pilih...</span></div><i class="fas fa-chevron-down text-gray-400"></i>
                            </div>
                        </div>

                         <div id="customer_container" class="md:col-span-2 hidden">
                            <label for="customer_id" class="block mb-2 text-sm font-medium text-gray-700">Pelanggan (Wajib untuk Potong Saldo)</label>
                            <select id="customer_id" name="customer_id" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5">
                                <option value="">-- Pilih Pelanggan --</option>
                                @foreach($customers as $customer)
                                    <option value="{{ $customer->id_pengguna }}">
                                        {{ $customer->nama_lengkap }} (Saldo: Rp {{ number_format($customer->saldo ?? 0) }})
                                    </option>

                                @endforeach
                            </select>
                        </div>
                        
                        <div class="pt-4">
                            <button type="button" id="confirmBtn" class="w-full text-white bg-red-600 hover:bg-red-700 font-medium rounded-lg text-sm px-5 py-3 text-center" disabled>
                                Buat Pesanan
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Hidden fields untuk data API --}}
        <input type="hidden" name="pengirim_id" id="sender_id">
        <input type="hidden" name="sender_lat" id="sender_lat"><input type="hidden" name="sender_lng" id="sender_lng">
        <input type="hidden" name="sender_province" id="sender_province" ><input type="hidden" name="sender_regency" id="sender_regency" >
        <input type="hidden" name="sender_district" id="sender_district" ><input type="hidden" name="sender_village" id="sender_village" >
        <input type="hidden" name="sender_postal_code" id="sender_postal_code" >
        <input type="hidden" name="sender_district_id" id="sender_district_id" required>
        <input type="hidden" name="sender_subdistrict_id" id="sender_subdistrict_id" required>
        <input type="hidden" name="penerima_id" id="receiver_id">
        <input type="hidden" name="receiver_lat" id="receiver_lat"><input type="hidden" name="receiver_lng" id="receiver_lng">
        <input type="hidden" name="receiver_province" id="receiver_province" ><input type="hidden" name="receiver_regency" id="receiver_regency" >
        <input type="hidden" name="receiver_district" id="receiver_district" ><input type="hidden" name="receiver_village" id="receiver_village" >
        <input type="hidden" name="receiver_postal_code" id="receiver_postal_code" >
        <input type="hidden" name="receiver_district_id" id="receiver_district_id" required>
        <input type="hidden" name="receiver_subdistrict_id" id="receiver_subdistrict_id" required>
        <input type="hidden" name="expedition" id="expedition" required>
        <input type="hidden" name="payment_method" id="payment_method" required>
    </form>
</div>

<!-- Modal Pilihan Ekspedisi -->
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
    </div>
</div>

<!-- Modal Metode Pembayaran -->
<div id="paymentMethodModal" class="fixed inset-0 bg-gray-800 bg-opacity-60 z-50 hidden flex items-center justify-center">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-md mx-4">
        <div class="p-4 border-b flex justify-between items-center">
            <h5 class="text-lg font-semibold"><i class="fas fa-credit-card mr-2 text-red-600"></i>Pilih Metode Pembayaran</h5>
            <button type="button" class="close-modal-btn text-gray-500 hover:text-gray-800">&times;</button>
        </div>
        <div class="modal-body-scroll">
           <ul id="paymentOptionsList" class="divide-y">
                {{-- Opsi Potong Saldo khusus Admin --}}
                <li class="payment-option p-4 flex items-center cursor-pointer hover:bg-gray-50" data-value="Potong Saldo" data-label="Potong Saldo"><img src="https://cdn-icons-png.flaticon.com/512/1086/1086060.png" class="w-8 h-8 mr-4">Potong Saldo</li>

                {{-- Opsi dari KiriminAja --}}
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
    const paymentModalEl = document.getElementById('paymentMethodModal');
    let runValidityChecks = () => {}; // Placeholder function

    // --- HELPER FUNCTIONS (accessible by all) ---
    function formatRupiah(angka) { 
        return 'Rp ' + (parseInt(angka, 10) || 0).toLocaleString('id-ID'); 
    }
    
    const debounce = (func, wait) => {
        let timeout;
        return function(...args) {
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(this, args), wait);
        };
    };
    
    // --- FUNGSI PENCARIAN & PEMILIHAN ---
    function setupContactSearch(prefix) {
        const nameInput = document.getElementById(`${prefix}_name`);
        const phoneInput = document.getElementById(`${prefix}_phone`);
        const resultsContainer = document.getElementById(`${prefix}_contact_results`);
        

        const performSearch = async (query) => {
            if (query.length < 3) { resultsContainer.classList.add('hidden'); return; }
            try {
                const url = `{{ route('api.contacts.search') }}?search=${encodeURIComponent(query)}`;
                const response = await fetch(url);
                if (!response.ok) throw new Error(`Server error: ${response.statusText}`);
                const contacts = await response.json();
                resultsContainer.innerHTML = '';
                resultsContainer.classList.remove('hidden');

                if (contacts && contacts.length > 0) {
                    contacts.forEach(contact => {
                        const resultDiv = document.createElement('div');
                        resultDiv.className = 'p-3 border-b hover:bg-gray-100 cursor-pointer text-sm';
                        resultDiv.innerHTML = `
                            <div class="font-semibold">${contact.nama}</div>
                            <div class="text-xs text-gray-500">${contact.no_hp}</div>
                            <div class="text-xs text-gray-400 truncate">${contact.alamat || '-'}</div>`;

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
                            document.getElementById(`${prefix}_address_search`).value = kiriminAjaSearchString;
                            resultsContainer.classList.add('hidden');
                            if (kiriminAjaSearchString) performAddressSearch(prefix, kiriminAjaSearchString, contact);
                            runValidityChecks();
                        });
                        resultsContainer.appendChild(resultDiv);
                    });
                } else {
                    resultsContainer.innerHTML = '<div class="p-3 text-gray-500">Kontak tidak ditemukan.</div>';
                }
            } catch (error) {
                console.error(`[${prefix}] Gagal melakukan pencarian kontak:`, error);
                resultsContainer.classList.remove('hidden');
                resultsContainer.innerHTML = `<div class="p-3 text-red-500">Gagal memuat data.</div>`;
            }
        };
        const debouncedSearch = debounce(performSearch, 400);
        nameInput.addEventListener('input', () => debouncedSearch(nameInput.value));
        phoneInput.addEventListener('input', () => debouncedSearch(phoneInput.value));
    }
    
    function selectAddress(prefix, item) {
        document.getElementById(`${prefix}_address_search`).value = item.full_address;
        const parts = item.full_address.split(',').map(s => s.trim());
        document.getElementById(`${prefix}_village`).value = parts[0] || '';
        document.getElementById(`${prefix}_district`).value = parts[1] || '';
        document.getElementById(`${prefix}_regency`).value = parts[2] || '';
        document.getElementById(`${prefix}_province`).value = parts[3] || '';
        document.getElementById(`${prefix}_postal_code`).value = parts[4] || '';
        document.getElementById(`${prefix}_district_id`).value = item.district_id;
        document.getElementById(`${prefix}_subdistrict_id`).value = item.subdistrict_id;
        document.getElementById(`${prefix}_address_results`).classList.add('hidden');
        document.getElementById(`${prefix}_address_check`).classList.remove('hidden');
        runValidityChecks();
    }

    async function performAddressSearch(prefix, query, contactToMatch = null) {
        const resultsContainer = document.getElementById(`${prefix}_address_results`);
        if (query.length < 3) { resultsContainer.classList.add('hidden'); return; }
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
                        const regency = (contactToMatch.regency || '').toLowerCase().replace(/kabupaten |kota /g, '');
                        const postalCode = (contactToMatch.postal_code || '');
                        return village && district && regency && postalCode &&
                               normalizedApiAddress.includes(village) &&
                               normalizedApiAddress.includes(district) &&
                               normalizedApiAddress.includes(regency) &&
                               normalizedApiAddress.includes(postalCode);
                    });
                    if (exactMatch) { selectAddress(prefix, exactMatch); return; }
                }
                if (data.length === 1) { selectAddress(prefix, data[0]); return; }
                data.forEach(item => {
                    const resultDiv = document.createElement('div');
                    resultDiv.className = 'p-3 border-b hover:bg-gray-100 cursor-pointer text-sm';
                    resultDiv.innerHTML = `<div class="font-semibold">${item.full_address}</div>`;
                    resultDiv.addEventListener('click', () => selectAddress(prefix, item));
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
        const debouncedSearch = debounce(() => {
            document.getElementById(`${prefix}_address_check`).classList.add('hidden');
            performAddressSearch(prefix, searchInput.value, null);
        }, 400);
        searchInput.addEventListener('input', debouncedSearch);
    }
    
    async function runCekOngkir() {
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
                ongkirModalBody.innerHTML = '<div class="bg-yellow-100 text-yellow-800 p-4 rounded-md text-center">Layanan pengiriman tidak ditemukan.</div>';
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
                            <img src="{{ asset('/public/storage/logo-ekspedisi/') }}/${item.service.toLowerCase().replace(/\s+/g, '')}.png" class="w-16 h-auto mr-4 object-contain" onerror="this.src='https://placehold.co/100x40?text=${item.service}'">
                            <div><h6 class="font-bold text-gray-800">${item.service_name}</h6>${details}</div>
                        </div>
                        <div class="text-right">
                            <small class="text-gray-500">Ongkir</small>
                            <strong class="block text-lg text-red-600">${formatRupiah(item.cost)}</strong>
                            <button type="button" class="select-ongkir-btn mt-1 bg-red-600 text-white px-3 py-1 rounded-md hover:bg-red-700 text-sm" data-value="${value}" data-display="${item.service_name}" data-cod-supported="${isCod}">Pilih</button>
                        </div>
                    </div>`;
                ongkirModalBody.appendChild(card);
            });
        } catch (error) {
            console.error('Cek Ongkir failed:', error);
            ongkirModalBody.innerHTML = `<div class="bg-red-100 text-red-800 p-4 rounded-md text-center">${error.message}</div>`;
        }
    }

    // --- INISIALISASI & EVENT LISTENERS ---
    setupContactSearch('sender');
    setupContactSearch('receiver');
    setupAddressSearch('sender');
    setupAddressSearch('receiver');

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
            runValidityChecks();
        }
    });

    document.getElementById('paymentMethodButton').addEventListener('click', () => paymentModalEl.classList.remove('hidden'));
    
    document.querySelectorAll('.payment-option').forEach(item => {
        item.addEventListener('click', function() {
            const paymentValue = this.dataset.value;
            const customerContainer = document.getElementById('customer_container');
            const customerSelect = document.getElementById('customer_id');
            document.getElementById('payment_method').value = paymentValue;
            document.getElementById('selectedPaymentName').textContent = this.dataset.label;
            document.getElementById('selectedPaymentLogo').src = this.querySelector('img').src;
            document.querySelectorAll('.payment-option').forEach(opt => opt.classList.remove('bg-red-50'));
            this.classList.add('bg-red-50');
            
            if (paymentValue === 'Potong Saldo') {
                customerContainer.classList.remove('hidden');
                customerSelect.setAttribute('required', 'required');
            } else {
                customerContainer.classList.add('hidden');
                customerSelect.removeAttribute('required');
                customerSelect.value = '';
            }
            paymentModalEl.classList.add('hidden');
            runValidityChecks();
        });
    });

    document.querySelectorAll('.close-modal-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            ongkirModalEl.classList.add('hidden');
            paymentModalEl.classList.add('hidden');
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
        if (!event.target.closest('#sender_name, #sender_contact_results, #sender_phone')) {
            document.getElementById('sender_contact_results').classList.add('hidden');
        }
        if (!event.target.closest('#receiver_name, #receiver_contact_results, #receiver_phone')) {
            document.getElementById('receiver_contact_results').classList.add('hidden');
        }
    });
    
    // --- START: Validity + Potong Saldo check logic ---
    (function() {
        const form = document.getElementById('orderForm');
        const confirmBtn = document.getElementById('confirmBtn');
        const expeditionInput = document.getElementById('expedition');
        const paymentMethodInput = document.getElementById('payment_method');
        const customerSelect = document.getElementById('customer_id');

        function debugLog(line, msg) {
            console.log(`create:${line} - ${msg}`);
        }

        // Assign the function to the outer-scoped variable so other parts of the script can call it
        runValidityChecks = function() {
            debugLog(1082, 'Memeriksa Validitas Form');

            const html5Valid = form.checkValidity();
            debugLog(1091, `Validitas Bawaan HTML5 (form.checkValidity()): ${html5Valid}`);

            const expeditionChosen = expeditionInput && expeditionInput.value && expeditionInput.value.trim() !== '';
            debugLog(1098, `Kondisi Ekspedisi: ${expeditionChosen ? 'Lolos' : 'Gagal'}`);

            const paymentChosen = paymentMethodInput && paymentMethodInput.value && paymentMethodInput.value.trim() !== '';
            debugLog(1106, `Kondisi Metode Pembayaran: ${paymentChosen ? 'Lolos' : 'Gagal'}`);

            let potongSaldoFailsCustomer = false;
            if (paymentChosen && paymentMethodInput.value === 'Potong Saldo') {
                const customerChosen = customerSelect && customerSelect.value && customerSelect.value.trim() !== '';
                if (!customerChosen) {
                    debugLog(1111, "Kondisi Gagal: 'Potong Saldo' dipilih tapi Pelanggan kosong.");
                    potongSaldoFailsCustomer = true;
                } else {
                    debugLog(1111, "Kondisi Lolos: 'Potong Saldo' dan Pelanggan terpilih.");
                }
            }

            const allOk = html5Valid && expeditionChosen && paymentChosen && !potongSaldoFailsCustomer;
            if (!allOk) {
                confirmBtn.disabled = true;
                confirmBtn.classList.add('opacity-60', 'cursor-not-allowed');
                confirmBtn.setAttribute('title', potongSaldoFailsCustomer ? 'Pilih pelanggan saat menggunakan Potong Saldo' : 'Lengkapi form terlebih dahulu');
                debugLog(1118, 'Hasil Akhir: Tombol akan DINONAKTIFKAN');
            } else {
                confirmBtn.disabled = false;
                confirmBtn.classList.remove('opacity-60', 'cursor-not-allowed');
                confirmBtn.removeAttribute('title');
                debugLog(1118, 'Hasil Akhir: Tombol AKTIF');
            }
        };

        runValidityChecks();

        const watchEls = Array.from(document.querySelectorAll('input, select, textarea'));
        watchEls.forEach(el => {
            if (el.type === 'hidden') return;
            const debouncedCheck = debounce(runValidityChecks, 200);
            el.addEventListener('input', debouncedCheck);
            el.addEventListener('change', runValidityChecks);
            el.addEventListener('blur', runValidityChecks);
        });

        confirmBtn.addEventListener('click', function(e) {
            e.preventDefault();
            runValidityChecks();

            if (confirmBtn.disabled) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Data Belum Lengkap',
                    text: (paymentMethodInput.value === 'Potong Saldo' && (!customerSelect.value || customerSelect.value === '')) 
                            ? 'Anda harus memilih pelanggan jika menggunakan metode Potong Saldo.' 
                            : 'Harap lengkapi semua field yang wajib diisi, termasuk memilih ekspedisi dan metode pembayaran.'
                });
                return;
            }
            
            Swal.fire({
                title: 'Konfirmasi Pesanan',
                text: "Apakah semua data sudah benar?",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#ff0000ff',
                cancelButtonColor: '#0be628ff',
                confirmButtonText: 'Ya, Buat Pesanan',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    confirmBtn.disabled = true;
                    confirmBtn.innerHTML = `<i class="fas fa-spinner fa-spin mr-2"></i>Memproses...`;
                    form.submit();
                }
            });
        });
    })();
});
</script>
@endpush

