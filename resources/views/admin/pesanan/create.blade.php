@extends('layouts.admin')

@section('title', 'Tambah Pesanan Baru')
@section('page-title', 'Buat Pesanan Baru')

@push('styles')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
    .search-results-container {
        position: absolute; z-index: 1000; width: 100%; max-height: 250px; overflow-y: auto;
        background-color: #fff; border: 1px solid #e2e8f0; border-radius: 0 0 0.5rem 0.5rem;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); margin-top: -1px;
    }
    .modal-body-scroll { max-height: 70vh; overflow-y: auto; }
    #confirmBtn:disabled { background-color: #bebebeff; cursor: not-allowed; }
</style>
@endpush

@php
    if (!isset($idempotencyKey)) { $idempotencyKey = (string) \Illuminate\Support\Str::uuid(); }
@endphp

@section('content')

@include('layouts.partials.notifications')

<div class="max-w-7xl mx-auto">
    <form id="orderForm" action="{{ route('admin.pesanan.store') }}" method="POST">
        @csrf
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

            <div class="lg:col-span-2 space-y-8">
                <div class="bg-white p-6 rounded-lg shadow-md">
                    <div class="flex justify-between items-center border-b pb-4 mb-6">
                        <h3 class="text-xl font-semibold text-gray-800"><i class="fas fa-arrow-up-from-bracket text-red-500 mr-2"></i>Informasi Pengirim</h3>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="relative">
                            <label class="block mb-2 text-sm font-medium text-gray-700">Nama Pengirim</label>
                            <input type="text" id="sender_name" name="sender_name" class="bg-white border border-gray-300 text-sm rounded-lg block w-full p-2.5 focus:ring-red-500 focus:border-red-500" required autocomplete="off">
                            <div id="sender_contact_results" class="search-results-container hidden"></div>
                        </div>
                        <div class="relative">
                            <label class="block mb-2 text-sm font-medium text-gray-700">Nomor HP</label>
                            <input type="tel" id="sender_phone" name="sender_phone" class="bg-white border border-gray-300 text-sm rounded-lg block w-full p-2.5 focus:ring-red-500 focus:border-red-500" required autocomplete="off">
                        </div>
                        <div class="md:col-span-2 relative">
                            <label class="block mb-2 text-sm font-medium text-gray-700">Cari Alamat Ongkir (Kec/Kel/Kodepos)</label>
                            <div class="relative">
                                <input type="text" id="sender_address_search" class="bg-white border border-gray-300 text-sm rounded-lg block w-full p-2.5 focus:ring-red-500 focus:border-red-500" required autocomplete="off">
                                <i id="sender_address_check" class="fas fa-check-circle text-green-500 absolute top-1/2 right-3 transform -translate-y-1/2 hidden"></i>
                            </div>
                            <div id="sender_address_results" class="search-results-container hidden"></div>
                        </div>
                        <div class="md:col-span-2">
                            <label class="block mb-2 text-sm font-medium text-gray-700">Detail Alamat Lengkap</label>
                            <textarea id="sender_address" name="sender_address" rows="3" class="w-full rounded-lg border border-gray-300 p-2.5 text-sm focus:ring-red-500 focus:border-red-500" required></textarea>
                        </div>
                        <div class="md:col-span-2">
                            <label class="flex items-center text-sm text-gray-600"><input type="checkbox" name="save_sender" value="1" class="mr-2"> Simpan data pengirim ini</label>
                        </div>
                    </div>
                </div>

                <div class="bg-white p-6 rounded-lg shadow-md">
                    <div class="flex justify-between items-center border-b pb-4 mb-6">
                        <h3 class="text-xl font-semibold text-gray-800"><i class="fas fa-map-marker-alt text-green-500 mr-2"></i>Informasi Penerima</h3>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="relative">
                            <label class="block mb-2 text-sm font-medium text-gray-700">Nama Penerima</label>
                            <input type="text" id="receiver_name" name="receiver_name" class="bg-white border border-gray-300 text-sm rounded-lg block w-full p-2.5 focus:ring-green-500 focus:border-green-500" required autocomplete="off">
                            <div id="receiver_contact_results" class="search-results-container hidden"></div>
                        </div>
                        <div class="relative">
                            <label class="block mb-2 text-sm font-medium text-gray-700">Nomor HP</label>
                            <input type="tel" id="receiver_phone" name="receiver_phone" class="bg-white border border-gray-300 text-sm rounded-lg block w-full p-2.5 focus:ring-green-500 focus:border-green-500" required autocomplete="off">
                        </div>
                        <div class="md:col-span-2 relative">
                            <label class="block mb-2 text-sm font-medium text-gray-700">Cari Alamat Ongkir (Kec/Kel/Kodepos)</label>
                            <div class="relative">
                                <input type="text" id="receiver_address_search" class="bg-white border border-gray-300 text-sm rounded-lg block w-full p-2.5 focus:ring-green-500 focus:border-green-500" required autocomplete="off">
                                <i id="receiver_address_check" class="fas fa-check-circle text-green-500 absolute top-1/2 right-3 transform -translate-y-1/2 hidden"></i>
                            </div>
                            <div id="receiver_address_results" class="search-results-container hidden"></div>
                        </div>
                        <div class="md:col-span-2">
                            <label class="block mb-2 text-sm font-medium text-gray-700">Alamat Penerima Lengkap</label>
                            <textarea id="receiver_address" name="receiver_address" rows="3" class="w-full rounded-lg border border-gray-300 p-2.5 text-sm focus:ring-green-500 focus:border-green-500" required></textarea>
                        </div>
                        <div class="md:col-span-2">
                            <label class="flex items-center text-sm text-gray-600"><input type="checkbox" name="save_receiver" value="1" class="mr-2"> Simpan data penerima ini</label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="lg:col-span-1 space-y-8">
                <div class="bg-white p-6 rounded-lg shadow-md sticky top-8">
                    <h3 class="text-xl font-semibold text-gray-800 border-b pb-4 mb-6"><i class="fas fa-box-open text-yellow-500 mr-2"></i>Detail Paket</h3>
                    <div class="space-y-4">
                        <div>
                            <label class="block mb-2 text-sm font-medium text-gray-700">Deskripsi Barang</label>
                            <input type="text" id="item_description" name="item_description" value="Barang Umum" class="bg-gray-50 border border-gray-300 text-sm rounded-lg block w-full p-2.5" required>
                        </div>
                        <div>
                            <label class="block mb-2 text-sm font-medium text-gray-700">Harga Barang (Rp)</label>
                            <input type="number" name="item_price" id="item_price" value="1000" class="bg-gray-50 border border-gray-300 text-sm rounded-lg block w-full p-2.5" required min="1">
                        </div>
                        <div>
                            <label class="block mb-2 text-sm font-medium text-gray-700">Berat (gram)</label>
                            <input type="number" id="weight" name="weight" value="1000" class="bg-gray-50 border border-gray-300 text-sm rounded-lg block w-full p-2.5" required min="1">
                        </div>
                        <div class="grid grid-cols-3 gap-4">
                            <div><label class="block text-xs mb-1">P (cm)</label><input type="number" id="length" name="length" value="1" class="bg-gray-50 border border-gray-300 text-sm rounded-lg block w-full p-2.5"></div>
                            <div><label class="block text-xs mb-1">L (cm)</label><input type="number" id="width" name="width" value="1" class="bg-gray-50 border border-gray-300 text-sm rounded-lg block w-full p-2.5"></div>
                            <div><label class="block text-xs mb-1">T (cm)</label><input type="number" id="height" name="height" value="1" class="bg-gray-50 border border-gray-300 text-sm rounded-lg block w-full p-2.5"></div>
                        </div>
                        <div>
                            <label class="block mb-2 text-sm font-medium text-gray-700">Jenis Barang</label>
                            <select name="item_type" id="item_type" class="bg-gray-50 border border-gray-300 text-sm rounded-lg block w-full p-2.5" required>
                                <option value="1">Peralatan Elektronik & Gadget</option>
                                <option value="2">Pakaian / Baju / Kain</option>
                                <option value="3">Pecah Belah</option>
                                <option value="7" selected>Lain-Lain</option>
                                {{-- Opsi lain disederhanakan --}}
                            </select>
                        </div>
                        <div>
                            <label class="block mb-2 text-sm font-medium text-gray-700">Jenis Layanan</label>
                            <select name="service_type" id="service_type" class="bg-gray-50 border border-gray-300 text-sm rounded-lg block w-full p-2.5" required>
                                <option value="regular" selected>Regular</option>
                                <option value="express">Express</option>
                                <option value="sameday">Sameday</option>
                                <option value="instant">Instant</option>
                                <option value="cargo">Cargo</option>
                            </select>
                        </div>
                        <div>
                            <label class="block mb-2 text-sm font-medium text-gray-700">Asuransi</label>
                            <select name="ansuransi" id="ansuransi" class="bg-gray-50 border border-gray-300 text-sm rounded-lg block w-full p-2.5" required>
                                <option value="tidak" selected>Tidak</option><option value="iya">Iya</option>
                            </select>
                        </div>
                        <hr/>
                        <div>
                            <label class="block mb-2 text-sm font-medium text-gray-700">Pilih Ekspedisi</label>
                            <input type="text" id="selected_expedition_display" class="cursor-pointer bg-red-50 border border-red-300 text-red-600 text-sm rounded-lg block w-full p-2.5 text-center font-semibold" placeholder="Klik untuk cek ongkir" readonly required>
                        </div>
                        <div>
                            <label class="block mb-2 text-sm font-medium text-gray-700">Metode Pembayaran</label>
                            <div id="paymentMethodButton" class="cursor-pointer bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg w-full p-2.5 flex justify-between items-center">
                                <div class="flex items-center">
                                    <img id="selectedPaymentLogo" src="https://cdn-icons-png.flaticon.com/512/2331/2331941.png" alt="Logo" class="w-6 h-6 mr-2 object-contain">
                                    <span id="selectedPaymentName">Pilih...</span>
                                </div>
                                <i class="fas fa-chevron-down text-gray-400"></i>
                            </div>
                        </div>
                        <div id="customer_container" class="hidden">
                            <label class="block mb-2 text-sm font-medium text-gray-700">Pelanggan (Potong Saldo)</label>
                            <select id="customer_id" name="customer_id" class="bg-gray-50 border border-gray-300 text-sm rounded-lg block w-full p-2.5">
                                <option value="">-- Pilih Pelanggan --</option>
                                @foreach($customers as $customer)
                                    <option value="{{ $customer->id_pengguna }}">{{ $customer->nama_lengkap }} (Saldo: Rp {{ number_format($customer->saldo ?? 0) }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="pt-4">
                            <button type="button" id="confirmBtn" class="w-full text-white bg-red-600 hover:bg-red-700 font-medium rounded-lg text-sm px-5 py-3 text-center" disabled>Buat Pesanan</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Hidden Fields --}}
        <input type="hidden" name="pengirim_id" id="sender_id">
        <input type="hidden" name="sender_lat" id="sender_lat"><input type="hidden" name="sender_lng" id="sender_lng">
        <input type="hidden" name="sender_province" id="sender_province"><input type="hidden" name="sender_regency" id="sender_regency">
        <input type="hidden" name="sender_district" id="sender_district"><input type="hidden" name="sender_village" id="sender_village">
        <input type="hidden" name="sender_postal_code" id="sender_postal_code">
        <input type="hidden" name="sender_district_id" id="sender_district_id" required>
        <input type="hidden" name="sender_subdistrict_id" id="sender_subdistrict_id" required>
        <input type="hidden" name="penerima_id" id="receiver_id">
        <input type="hidden" name="receiver_lat" id="receiver_lat"><input type="hidden" name="receiver_lng" id="receiver_lng">
        <input type="hidden" name="receiver_province" id="receiver_province"><input type="hidden" name="receiver_regency" id="receiver_regency">
        <input type="hidden" name="receiver_district" id="receiver_district"><input type="hidden" name="receiver_village" id="receiver_village">
        <input type="hidden" name="receiver_postal_code" id="receiver_postal_code">
        <input type="hidden" name="receiver_district_id" id="receiver_district_id" required>
        <input type="hidden" name="receiver_subdistrict_id" id="receiver_subdistrict_id" required>
        <input type="hidden" name="expedition" id="expedition" required>
        <input type="hidden" name="payment_method" id="payment_method" required>
        <input type="hidden" name="idempotency_key" value="{{ $idempotencyKey }}">
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
                {{-- Opsi Internal Tetap Ada --}}
                <li class="payment-option p-4 flex items-center cursor-pointer hover:bg-gray-50" data-value="Potong Saldo" data-label="Potong Saldo">
                    <img src="https://cdn-icons-png.flaticon.com/512/1086/1086060.png" class="w-8 h-8 mr-4 object-contain">
                    <div>
                        <div class="font-semibold text-gray-800">Potong Saldo</div>
                        <small class="text-gray-500">Saldo Customer</small>
                    </div>
                </li>

                {{-- Opsi COD (Dikontrol JS) --}}
                <li class="payment-option p-4 flex items-center cursor-pointer hover:bg-gray-50 cod-payment-option hidden" data-value="COD" data-label="COD Ongkir">
                    <img src="{{ asset('public/assets/cod.png') }}" class="w-8 h-8 mr-4 object-contain">
                    <div>
                        <div class="font-semibold text-gray-800">COD Ongkir</div>
                        <small class="text-gray-500">Bayar ongkir ditempat</small>
                    </div>
                </li>
                <li class="payment-option p-4 flex items-center cursor-pointer hover:bg-gray-50 cod-payment-option hidden" data-value="CODBARANG" data-label="COD Barang + Ongkir">
                    <img src="{{ asset('public/assets/cod.png') }}" class="w-8 h-8 mr-4 object-contain">
                    <div>
                        <div class="font-semibold text-gray-800">COD Lengkap</div>
                        <small class="text-gray-500">Bayar barang & ongkir</small>
                    </div>
                </li>

                {{-- Pemisah --}}
                <li class="bg-gray-100 p-2 text-xs font-bold text-gray-500 uppercase tracking-wider">Transfer & Online (Tripay)</li>

                {{-- WADAH OTOMATIS TRIPAY --}}
                <div id="tripayChannelsContainer">
                    <div class="p-4 text-center text-gray-500">
                        <i class="fas fa-spinner fa-spin mr-2"></i>Memuat saluran pembayaran...
                    </div>
                </div>
            </ul>
        </div>
        <div class="p-4 border-t text-right">
            <button type="button" class="close-modal-btn px-4 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300">Tutup</button>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const ongkirModalEl = document.getElementById('ongkirModal');
    const paymentModalEl = document.getElementById('paymentMethodModal');
    let runValidityChecks = () => {};

    function formatRupiah(angka) { return 'Rp ' + (parseInt(angka, 10) || 0).toLocaleString('id-ID'); }
    const debounce = (func, wait) => {
        let timeout;
        return function(...args) { clearTimeout(timeout); timeout = setTimeout(() => func.apply(this, args), wait); };
    };

    // --- SETUP PENCARIAN KONTAK & ALAMAT (Sama seperti sebelumnya) ---
    function setupContactSearch(prefix) {
        const nameInput = document.getElementById(`${prefix}_name`);
        const phoneInput = document.getElementById(`${prefix}_phone`);
        const resultsContainer = document.getElementById(`${prefix}_contact_results`);

        const performSearch = async (query) => {
            if (query.length < 3) { resultsContainer.classList.add('hidden'); return; }
            try {
                const response = await fetch(`{{ route('api.contacts.search') }}?search=${encodeURIComponent(query)}`);
                const contacts = await response.json();
                resultsContainer.innerHTML = ''; resultsContainer.classList.remove('hidden');
                if (contacts && contacts.length > 0) {
                    contacts.forEach(contact => {
                        const resultDiv = document.createElement('div');
                        resultDiv.className = 'p-3 border-b hover:bg-gray-100 cursor-pointer text-sm';
                        resultDiv.innerHTML = `<div class="font-semibold">${contact.nama}</div><div class="text-xs text-gray-500">${contact.no_hp}</div><div class="text-xs text-gray-400 truncate">${contact.alamat || '-'}</div>`;
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
                            const searchStr = [contact.village, contact.district, contact.regency, contact.postal_code].filter(Boolean).join(', ');
                            document.getElementById(`${prefix}_address_search`).value = searchStr;
                            resultsContainer.classList.add('hidden');
                            if (searchStr) performAddressSearch(prefix, searchStr, contact);
                            runValidityChecks();
                        });
                        resultsContainer.appendChild(resultDiv);
                    });
                } else { resultsContainer.innerHTML = '<div class="p-3 text-gray-500">Kontak tidak ditemukan.</div>'; }
            } catch (error) { resultsContainer.classList.add('hidden'); }
        };
        const dSearch = debounce(performSearch, 400);
        nameInput.addEventListener('input', () => dSearch(nameInput.value));
        phoneInput.addEventListener('input', () => dSearch(phoneInput.value));
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
            const data = await response.json();
            resultsContainer.innerHTML = ''; resultsContainer.classList.remove('hidden');
            if (data && data.length > 0) {
                if (contactToMatch) {
                    const exactMatch = data.find(item => {
                        const normAddr = item.full_address.toLowerCase();
                        const v = (contactToMatch.village || '').toLowerCase();
                        const d = (contactToMatch.district || '').toLowerCase();
                        return v && d && normAddr.includes(v) && normAddr.includes(d);
                    });
                    if (exactMatch) { selectAddress(prefix, exactMatch); return; }
                }
                data.forEach(item => {
                    const div = document.createElement('div');
                    div.className = 'p-3 border-b hover:bg-gray-100 cursor-pointer text-sm';
                    div.innerHTML = `<div class="font-semibold">${item.full_address}</div>`;
                    div.addEventListener('click', () => selectAddress(prefix, item));
                    resultsContainer.appendChild(div);
                });
            } else { resultsContainer.innerHTML = '<div class="p-3 text-gray-500">Alamat tidak ditemukan.</div>'; }
        } catch (error) { resultsContainer.innerHTML = '<div class="p-3 text-red-500">Error memuat alamat.</div>'; }
    }

    function setupAddressSearch(prefix) {
        const input = document.getElementById(`${prefix}_address_search`);
        const dSearch = debounce(() => {
            document.getElementById(`${prefix}_address_check`).classList.add('hidden');
            performAddressSearch(prefix, input.value, null);
        }, 400);
        input.addEventListener('input', dSearch);
    }

    setupContactSearch('sender'); setupContactSearch('receiver');
    setupAddressSearch('sender'); setupAddressSearch('receiver');

    // --- CEK ONGKIR ---
    async function runCekOngkir() {
        const requiredFields = { '#sender_subdistrict_id': 'Alamat Pengirim', '#receiver_subdistrict_id': 'Alamat Penerima', '#item_price': 'Harga Barang', '#weight': 'Berat', '#service_type': 'Jenis Layanan' };
        let missing = Object.keys(requiredFields).filter(s => !document.querySelector(s).value);
        if (missing.length > 0) { Swal.fire('Data Belum Lengkap', 'Lengkapi: ' + missing.map(s => requiredFields[s]).join(', '), 'warning'); return; }

        const modalBody = document.getElementById('ongkirModalBody');
        modalBody.innerHTML = `<div class="text-center p-5"><i class="fas fa-spinner fa-spin text-3xl text-red-600"></i><p class="mt-2">Memuat tarif...</p></div>`;
        ongkirModalEl.classList.remove('hidden');

        try {
            const params = new URLSearchParams(new FormData(document.getElementById('orderForm'))).toString();
            const response = await fetch(`{{ route('kirimaja.cekongkir') }}?${params}`);
            const res = await response.json();
            modalBody.innerHTML = '';

            let results = (res.results || []).concat((res.result || []).flatMap(v => v.costs.map(c => ({...c, service: v.name, service_name: `${v.name.toUpperCase()} - ${c.service_type}`, cost: c.price.total_price, etd: c.estimation || '-', setting: c.setting || {}, insurance: c.price.insurance_fee || 0, cod: c.cod }))));

            if (results.length === 0) { modalBody.innerHTML = '<div class="bg-yellow-100 p-4 rounded text-center">Layanan tidak ditemukan.</div>'; return; }

            results.sort((a, b) => a.cost - b.cost).forEach(item => {
                const isCod = item.cod;
                const value = `${document.getElementById('service_type').value}-${item.service}-${item.service_type}-${item.cost}-${item.insurance||0}-${item.setting?.cod_fee_amount||0}`;
                let details = `<small class="text-gray-500 block">Estimasi: ${item.etd}</small>`;
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
                            <strong class="block text-lg text-red-600">${formatRupiah(item.cost)}</strong>
                            <button type="button" class="select-ongkir-btn mt-1 bg-red-600 text-white px-3 py-1 rounded-md hover:bg-red-700 text-sm" data-value="${value}" data-display="${item.service_name}" data-cod-supported="${isCod}">Pilih</button>
                        </div>
                    </div>`;
                modalBody.appendChild(card);
            });
        } catch (error) { modalBody.innerHTML = `<div class="bg-red-100 p-4 rounded text-center">${error.message}</div>`; }
    }

    document.getElementById('selected_expedition_display').addEventListener('click', runCekOngkir);

    // --- PILIH ONGKIR ---
    ongkirModalEl.addEventListener('click', function(e) {
        if (e.target.classList.contains('select-ongkir-btn')) {
            document.getElementById('expedition').value = e.target.dataset.value;
            document.getElementById('selected_expedition_display').value = e.target.dataset.display;

            const codOptions = document.querySelectorAll('.cod-payment-option');
            const currentMethod = document.getElementById('payment_method').value;

            if (e.target.dataset.codSupported === 'true') {
                codOptions.forEach(opt => opt.classList.remove('hidden'));
                codOptions.forEach(opt => opt.style.display = 'flex');
            } else {
                if (['COD', 'CODBARANG'].includes(currentMethod)) {
                    document.getElementById('payment_method').value = '';
                    document.getElementById('selectedPaymentName').textContent = 'Pilih...';
                    document.getElementById('selectedPaymentLogo').src = 'https://cdn-icons-png.flaticon.com/512/2331/2331941.png';
                }
                codOptions.forEach(opt => opt.classList.add('hidden'));
                codOptions.forEach(opt => opt.style.display = 'none');
            }
            ongkirModalEl.classList.add('hidden');
            runValidityChecks();
        }
    });

    // --- LOGIKA TRIPAY & PEMBAYARAN ---
    let tripayLoaded = false;
    async function loadTripayChannels() {
        if (tripayLoaded) return;
        const container = document.getElementById('tripayChannelsContainer');
        try {
            const res = await fetch("{{ route('admin.pesanan.get_channels') }}"); // PASTIKAN ROUTE INI ADA
            const json = await res.json();
            if (json.success && json.data.length > 0) {
                container.innerHTML = '';
                json.data.forEach(ch => {
                    if (ch.active) {
                        const li = document.createElement('li');
                        li.className = 'payment-option p-4 flex items-center cursor-pointer hover:bg-gray-50';
                        li.dataset.value = ch.code;
                        li.dataset.label = ch.name;
                        li.dataset.img = ch.icon_url;
                        li.innerHTML = `
                            <img src="${ch.icon_url}" class="w-10 h-10 mr-4 object-contain p-1 border rounded bg-white" onerror="this.src='https://placehold.co/50'">
                            <div>
                                <div class="font-semibold text-gray-800">${ch.name}</div>
                                <div class="text-xs text-gray-500">${ch.group_name}</div>
                            </div>
                        `;
                        li.addEventListener('click', () => selectPayment(li));
                        container.appendChild(li);
                    }
                });
                tripayLoaded = true;
            } else { container.innerHTML = '<div class="p-4 text-center text-red-500 text-sm">Gagal memuat saluran pembayaran.</div>'; }
        } catch (e) { container.innerHTML = '<div class="p-4 text-center text-red-500 text-sm">Terjadi kesalahan koneksi.</div>'; }
    }

    function selectPayment(el) {
        const val = el.dataset.value;
        document.getElementById('payment_method').value = val;
        document.getElementById('selectedPaymentName').textContent = el.dataset.label;
        document.getElementById('selectedPaymentLogo').src = el.dataset.img || el.querySelector('img').src;

        document.querySelectorAll('.payment-option').forEach(opt => opt.classList.remove('bg-red-50', 'border-l-4', 'border-red-500'));
        el.classList.add('bg-red-50', 'border-l-4', 'border-red-500');

        const custContainer = document.getElementById('customer_container');
        const custSelect = document.getElementById('customer_id');

        if (val === 'Potong Saldo') {
            custContainer.classList.remove('hidden');
            custSelect.setAttribute('required', 'required');
        } else {
            custContainer.classList.add('hidden');
            custSelect.removeAttribute('required');
            custSelect.value = '';
        }
        paymentModalEl.classList.add('hidden');
        runValidityChecks();
    }

    document.getElementById('paymentMethodButton').addEventListener('click', () => {
        paymentModalEl.classList.remove('hidden');
        loadTripayChannels();
    });

    // Attach listener ke opsi manual (Potong Saldo & COD)
    document.querySelectorAll('#paymentOptionsList > li.payment-option').forEach(li => {
        li.addEventListener('click', () => selectPayment(li));
    });

    // --- TOMBOL TUTUP MODAL ---
    document.querySelectorAll('.close-modal-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            ongkirModalEl.classList.add('hidden');
            paymentModalEl.classList.add('hidden');
        });
    });

    // --- KLIK LUAR TUTUP SUGGESTION ---
    document.addEventListener('click', (e) => {
        if (!e.target.closest('#sender_address_search, #sender_address_results')) document.getElementById('sender_address_results').classList.add('hidden');
        if (!e.target.closest('#receiver_address_search, #receiver_address_results')) document.getElementById('receiver_address_results').classList.add('hidden');
        if (!e.target.closest('#sender_name, #sender_contact_results, #sender_phone')) document.getElementById('sender_contact_results').classList.add('hidden');
        if (!e.target.closest('#receiver_name, #receiver_contact_results, #receiver_phone')) document.getElementById('receiver_contact_results').classList.add('hidden');
    });

    // --- VALIDASI ---
    runValidityChecks = function() {
        const form = document.getElementById('orderForm');
        const btn = document.getElementById('confirmBtn');
        const exp = document.getElementById('expedition').value;
        const pay = document.getElementById('payment_method').value;
        const cust = document.getElementById('customer_id').value;

        let valid = form.checkValidity() && exp && pay;
        if (pay === 'Potong Saldo' && !cust) valid = false;

        btn.disabled = !valid;
        if (valid) {
            btn.classList.remove('opacity-60', 'cursor-not-allowed');
        } else {
            btn.classList.add('opacity-60', 'cursor-not-allowed');
        }
    };

    const inputs = document.querySelectorAll('input, select, textarea');
    inputs.forEach(el => {
        el.addEventListener('input', debounce(runValidityChecks, 200));
        el.addEventListener('change', runValidityChecks);
    });

    // --- SUBMIT ---
    document.getElementById('confirmBtn').addEventListener('click', (e) => {
        e.preventDefault();
        runValidityChecks();
        if (document.getElementById('confirmBtn').disabled) { Swal.fire('Data Belum Lengkap', 'Lengkapi form dulu.', 'warning'); return; }

        Swal.fire({
            title: 'Konfirmasi Pesanan', text: "Data sudah benar?", icon: 'question',
            showCancelButton: true, confirmButtonColor: '#d33', confirmButtonText: 'Ya, Buat', cancelButtonText: 'Batal'
        }).then((res) => {
            if (res.isConfirmed) {
                const btn = document.getElementById('confirmBtn');
                btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Memproses...';
                document.getElementById('orderForm').submit();
            }
        });
    });
});
</script>
@endpush
