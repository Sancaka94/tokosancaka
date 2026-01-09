@extends('layouts.customer')

@push('styles')
    <style>
        /* Sembunyikan panah di input number */
        input::-webkit-outer-spin-button,
        input::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }
        input[type=number] {
            -moz-appearance: textfield;
        }
        
        /* Style untuk hasil pencarian */
        .search-results {
            max-height: 300px;
        }
        .search-results .list-group-item:last-child {
            border-bottom-width: 1px;
        }

        /* [BARU] Style untuk Tombol Tab Aktif */
        /* (Kita gunakan kelas Tailwind 'active' sebagai penanda) */
        .shipping-tab-btn.active {
            color: #2563eb; /* text-blue-600 */
            border-color: #2563eb; /* border-blue-600 */
        }
    </style>
    @endpush

@section('content')
    <div class="container max-w-5xl mx-auto my-12 px-4">
        <div class="flex justify-center">
            <div class="w-full lg:w-11/12">
                <div class="bg-white shadow-lg rounded-xl overflow-hidden">
                    <div class="bg-blue-600 p-5">
                        <h3 class="text-2xl font-bold text-white">Cek Ongkos Kirim</h3>
                    </div>
                    <div class="p-6 md:p-8">
                        <form id="ongkir-form" class="space-y-6">
                            @csrf

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <h5 class="text-lg font-semibold text-gray-800 mb-2">Alamat Asal</h5>
                                    <div class="relative">
                                        <input type="text" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 transition" id="origin-search" placeholder="Ketik nama jalan, kelurahan..." autocomplete="off">
                                        <div class="search-results absolute w-full bg-white border border-gray-300 rounded-b-lg shadow-lg z-10 overflow-y-auto hidden" id="origin-results"></div>
                                    </div>
                                    <input type="hidden" id="origin_id">
                                    <input type="hidden" id="origin_subdistrict_id">
                                    <input type="hidden" id="origin_lat">
                                    <input type="hidden" id="origin_lon">
                                    <input type="hidden" id="origin_village">
                                    <input type="hidden" id="origin_district">
                                    <input type="hidden" id="origin_city">
                                    <input type="hidden" id="origin_province">
                                </div>

                                <div>
                                    <h5 class="text-lg font-semibold text-gray-800 mb-2">Alamat Tujuan</h5>
                                    <div class="relative">
                                        <input type="text" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 transition" id="destination-search" placeholder="Ketik nama jalan, kelurahan..." autocomplete="off">
                                        <div class="search-results absolute w-full bg-white border border-gray-300 rounded-b-lg shadow-lg z-10 overflow-y-auto hidden" id="destination-results"></div>
                                    </div>
                                    <input type="hidden" id="destination_id">
                                    <input type="hidden" id="destination_subdistrict_id">
                                    <input type="hidden" id="destination_lat">
                                    <input type="hidden" id="destination_lon">
                                    <input type="hidden" id="destination_village">
                                    <input type="hidden" id="destination_district">
                                    <input type="hidden" id="destination_city">
                                    <input type="hidden" id="destination_province">
                                </div>
                            </div>

                            <hr class="border-t border-gray-200">

                            <div>
                                <h5 class="text-lg font-semibold text-gray-800 mb-4">Detail Paket</h5>
                                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-x-6 gap-y-4">
                                    <div>
                                        <label for="weight" class="block text-sm font-medium text-gray-700 mb-1">Berat (gram) <span class="text-red-500">*</span></label>
                                        <input type="number" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 transition" id="weight" value="1000" min="1" required>
                                    </div>
                                    <div>
                                        <label for="length" class="block text-sm font-medium text-gray-700 mb-1">Panjang (cm)</label>
                                        <input type="number" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 transition" id="length" placeholder="1" min="1">
                                    </div>
                                    <div>
                                        <label for="width" class="block text-sm font-medium text-gray-700 mb-1">Lebar (cm)</label>
                                        <input type="number" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 transition" id="width" placeholder="1" min="1">
                                    </div>
                                    <div>
                                        <label for="height" class="block text-sm font-medium text-gray-700 mb-1">Tinggi (cm)</label>
                                        <input type="number" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 transition" id="height" placeholder="1" min="1">
                                    </div>
                                    <div class="sm:col-span-2">
                                        <label for="item-value" class="block text-sm font-medium text-gray-700 mb-1">Nilai Barang (Rp)</label>
                                        <input type="number" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 transition" id="item-value" placeholder="0" min="0">
                                    </div>
                                    <div class="sm:col-span-2 flex items-end pb-2">
                                        <div class="flex items-center">
                                            <input id="insurance" type="checkbox" class="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                                            <label for="insurance" class="ml-2 block text-sm font-medium text-gray-900">
                                                Gunakan Asuransi
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div>
                                <button type="submit" class="w-full bg-blue-600 text-white font-bold text-lg py-3 px-6 rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition duration-150 ease-in-out flex items-center justify-center h-14" id="cek-ongkir-btn">
                                    <span id="btn-text" class="">Cek Ongkos Kirim</span>
                                    <svg id="btn-spinner" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white hidden" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <div id="results-container" class="mt-8"></div>

            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            
            // Ambil elemen-elemen penting
            const ongkirForm = document.getElementById('ongkir-form');
            const originSearch = document.getElementById('origin-search');
            const originResults = document.getElementById('origin-results');
            const destSearch = document.getElementById('destination-search');
            const destResults = document.getElementById('destination-results');
            const resultsContainer = document.getElementById('results-container');
            const submitButton = document.getElementById('cek-ongkir-btn');
            const btnText = document.getElementById('btn-text');
            const btnSpinner = document.getElementById('btn-spinner');

            // --- FUNGSI DEBOUNCE ---
            let debounceTimer;
            function debounce(func, delay) {
                return function(...args) {
                    clearTimeout(debounceTimer);
                    debounceTimer = setTimeout(() => {
                        func.apply(this, args);
                    }, delay);
                };
            }

            // --- FUNGSI PENCARIAN ALAMAT ---
            async function searchAddress(searchTerm, type) {
                const resultsEl = (type === 'origin' ? originResults : destResults);
                if (searchTerm.length < 3) {
                    resultsEl.classList.add('hidden');
                    return;
                }

                try {
                    // Panggil rute dari web.php (CekOngkirController@searchAddress)
                    const response = await fetch(`/search-address?search=${encodeURIComponent(searchTerm)}`, {
                        method: 'GET',
                        headers: {
                            'Accept': 'application/json'
                        }
                    });

                    // Cek jika response TIDAK ok (misal: error 500 dari server)
                    if (!response.ok) {
                        let errorMessage = 'Gagal mengambil data alamat. Coba lagi.';
                        try {
                            const errorData = await response.json();
                            if (errorData && errorData.message) {
                                errorMessage = errorData.message; // Tampilkan pesan error dari backend
                            }
                        } catch (jsonError) {
                            // Biarkan, gunakan errorMessage default
                        }
                        throw new Error(errorMessage); // Lemparkan error dengan pesan yang lebih baik
                    }

                    const data = await response.json();
                    displayAddressResults(data, type);

                } catch (error) {
                    console.error('Error fetching address:', error);
                    // Tampilkan pesan error yang lebih spesifik
                    displayAddressResults([], type, error.message || 'Error mengambil data alamat.');
                }
            }
            
            // --- FUNGSI TAMPILKAN HASIL ALAMAT ---
            function displayAddressResults(results, type, errorMessage = null) {
                const resultsEl = (type === 'origin' ? originResults : destResults);
                resultsEl.innerHTML = ''; // Kosongkan hasil sebelumnya

                if (errorMessage) {
                    resultsEl.innerHTML = `<div class="px-4 py-3 text-red-600">${errorMessage}</div>`;
                    resultsEl.classList.remove('hidden');
                    return;
                }

                if (results.length === 0) {
                    resultsEl.innerHTML = '<div class="px-4 py-3 text-gray-500">Alamat tidak ditemukan.</div>';
                    resultsEl.classList.remove('hidden');
                    return;
                }
                
                // Filter hasil yang alamat lengkapnya kosong
                const validResults = results.filter(addr => addr.full_address && addr.full_address.trim() !== '');

                if (validResults.length === 0) {
                    resultsEl.innerHTML = '<div class="px-4 py-3 text-gray-500">Alamat tidak ditemukan (data tidak lengkap).</div>';
                    resultsEl.classList.remove('hidden');
                    return;
                }
                
                validResults.forEach(addr => {
                    const item = document.createElement('div');
                    item.className = 'list-group-item px-4 py-3 hover:bg-gray-100 cursor-pointer border-b border-gray-200';
                    item.textContent = addr.full_address; // Tampilkan alamat lengkap
                    
                    // Simpan SEMUA data dari controller di data-attributes
                    item.dataset.id = addr.district_id; // Map district_id -> origin_id/destination_id
                    item.dataset.subdistrictId = addr.subdistrict_id;
                    item.dataset.lat = addr.lat || '';
                    item.dataset.lon = addr.lon || '';
                    item.dataset.village = addr.subdistrict_name; // Map subdistrict_name -> village
                    item.dataset.district = addr.district_name;
                    item.dataset.city = addr.city_name;
                    item.dataset.province = addr.province_name;
                    item.dataset.fullAddress = addr.full_address;
                    
                    item.addEventListener('click', () => selectAddress(type, item));
                    resultsEl.appendChild(item);
                });

                resultsEl.classList.remove('hidden');
            }

            // --- [LENGKAP] FUNGSI PILIH ALAMAT ---
            function selectAddress(type, selectedItem) {
                // Ambil semua data dari dataset
                const { id, subdistrictId, lat, lon, village, district, city, province, fullAddress } = selectedItem.dataset;

                if (type === 'origin') {
                    // Set nilai input yang terlihat
                    originSearch.value = fullAddress;
                    // Set semua nilai input hidden untuk origin
                    document.getElementById('origin_id').value = id;
                    document.getElementById('origin_subdistrict_id').value = subdistrictId;
                    document.getElementById('origin_lat').value = lat;
                    document.getElementById('origin_lon').value = lon;
                    document.getElementById('origin_village').value = village;
                    document.getElementById('origin_district').value = district;
                    document.getElementById('origin_city').value = city;
                    document.getElementById('origin_province').value = province;
                    // Sembunyikan dropdown hasil
                    originResults.classList.add('hidden');
                } else {
                    // Set nilai input yang terlihat
                    destSearch.value = fullAddress;
                    // Set semua nilai input hidden untuk destination
                    document.getElementById('destination_id').value = id;
                    document.getElementById('destination_subdistrict_id').value = subdistrictId;
                    document.getElementById('destination_lat').value = lat;
                    document.getElementById('destination_lon').value = lon;
                    document.getElementById('destination_village').value = village;
                    document.getElementById('destination_district').value = district;
                    document.getElementById('destination_city').value = city;
                    document.getElementById('destination_province').value = province;
                    // Sembunyikan dropdown hasil
                    destResults.classList.add('hidden');
                }
            }

            // --- EVENT LISTENER UNTUK INPUT SEARCH (DENGAN DEBOUNCE) ---
            originSearch.addEventListener('input', debounce(() => {
                searchAddress(originSearch.value, 'origin');
            }, 500)); // Tunda 500ms

            destSearch.addEventListener('input', debounce(() => {
                searchAddress(destSearch.value, 'destination');
            }, 500)); // Tunda 500ms

            // Sembunyikan hasil jika klik di luar
            document.addEventListener('click', function(e) {
                if (!originSearch.contains(e.target) && !originResults.contains(e.target)) {
                    originResults.classList.add('hidden');
                }
                if (!destSearch.contains(e.target) && !destResults.contains(e.target)) {
                    destResults.classList.add('hidden');
                }
            });


            // --- FUNGSI SUBMIT FORM CEK ONGKIR ---
            ongkirForm.addEventListener('submit', async function(e) {
                e.preventDefault();
                setLoading(true);
                resultsContainer.innerHTML = ''; // Kosongkan hasil

                // --- [LENGKAP] Kumpulkan semua data dari form (termasuk hidden inputs) ---
                const payload = {
                    origin_id: document.getElementById('origin_id').value,
                    origin_subdistrict_id: document.getElementById('origin_subdistrict_id').value,
                    destination_id: document.getElementById('destination_id').value,
                    destination_subdistrict_id: document.getElementById('destination_subdistrict_id').value,
                    weight: document.getElementById('weight').value,
                    length: document.getElementById('length').value || 1,
                    width: document.getElementById('width').value || 1,
                    height: document.getElementById('height').value || 1,
                    item_value: document.getElementById('item-value').value || 0,
                    insurance: document.getElementById('insurance').checked ? 1 : 0, // Kirim 1 atau 0
                    origin_lat: document.getElementById('origin_lat').value,
                    origin_lon: document.getElementById('origin_lon').value,
                    origin_text: originSearch.value, // Kirim teks alamat untuk geocode
                    destination_lat: document.getElementById('destination_lat').value,
                    destination_lon: document.getElementById('destination_lon').value,
                    destination_text: destSearch.value, // Kirim teks alamat untuk geocode
                    origin_village: document.getElementById('origin_village').value,
                    origin_district: document.getElementById('origin_district').value,
                    origin_city: document.getElementById('origin_city').value,
                    origin_province: document.getElementById('origin_province').value,
                    destination_village: document.getElementById('destination_village').value,
                    destination_district: document.getElementById('destination_district').value,
                    destination_city: document.getElementById('destination_city').value,
                    destination_province: document.getElementById('destination_province').value,
                    _token: document.querySelector('input[name="_token"]').value
                };
                
                // Validasi sederhana di frontend
                if (!payload.origin_id || !payload.destination_id || !payload.weight) {
                    displayError('Harap lengkapi alamat asal, alamat tujuan, dan berat paket.');
                    setLoading(false);
                    return;
                }

                try {
                    // Panggil rute dari web.php (CekOngkirController@checkCost)
                    const response = await fetch('/check-cost', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': payload._token // Kirim CSRF token di header
                        },
                        body: JSON.stringify(payload)
                    });

                    const result = await response.json();

                    if (!response.ok) {
                        // Tangani error validasi (422) atau server (500)
                        throw new Error(result.message || 'Terjadi kesalahan saat menghubungi server.');
                    }
                    
                    if (result.success === true) {
                        // Panggil fungsi display dengan data gabungan
                        displayCostResults(result.data, result.final_weight);
                    } else {
                        // Tangani kasus 'success: false' dari backend
                        throw new Error(result.message || 'Gagal mengambil data ongkir.');
                    }

                } catch (error) {
                    console.error('Error checking cost:', error);
                    displayError(error.message);
                } finally {
                    setLoading(false);
                }
            });

            // --- FUNGSI UNTUK MENGAKTIFKAN TAB ---
            function attachTabListeners() {
                // Cari tombol dan panel di dalam #results-container
                const tabButtons = resultsContainer.querySelectorAll('.shipping-tab-btn');
                const tabPanes = resultsContainer.querySelectorAll('.shipping-tab-pane');

                tabButtons.forEach(button => {
                    button.addEventListener('click', (e) => {
                        e.preventDefault();
                        const targetPaneId = button.getAttribute('data-tab-target');

                        // 1. Nonaktifkan semua tombol
                        tabButtons.forEach(btn => {
                            btn.classList.remove('text-blue-600', 'border-blue-600', 'active');
                            btn.classList.add('border-transparent', 'hover:text-gray-600', 'hover:border-gray-300');
                            btn.setAttribute('aria-selected', 'false');
                        });
                        
                        // 2. Aktifkan tombol yang diklik
                        button.classList.add('text-blue-600', 'border-blue-600', 'active');
                        button.classList.remove('border-transparent', 'hover:text-gray-600', 'hover:border-gray-300');
                        button.setAttribute('aria-selected', 'true');


                        // 3. Sembunyikan semua panel
                        tabPanes.forEach(pane => {
                            pane.classList.add('hidden');
                        });

                        // 4. Tampilkan panel target
                        const targetPane = resultsContainer.querySelector(targetPaneId);
                        if (targetPane) {
                            targetPane.classList.remove('hidden');
                        }
                    });
                });
            }


            // --- FUNGSI TAMPILKAN HASIL ONGKIR (DENGAN TAB) ---
            function displayCostResults(data, finalWeight) {
                resultsContainer.innerHTML = ''; // Kosongkan dulu

                // Helper untuk format Rupiah
                const formatRupiah = (number) => {
                    return new Intl.NumberFormat('id-ID', {
                        style: 'currency',
                        currency: 'IDR',
                        minimumFractionDigits: 0
                    }).format(number);
                };

                // Helper untuk mengambil harga (memastikan konsistensi)
const getPrice = (service) => {
    return parseFloat(service.cost ?? service.rate ?? service.final_price ?? 0);
};

// --- [PERBAIKAN FUNGSI LOGO] ---
// Helper untuk membuat card layanan (DENGAN LOGO)
const createServiceCard = (service) => {
    const price = service.numeric_price;

    // Estimasi
    let etd = '';
    if (service.etd) {
        let etdText = service.etd;
        let etdLower = etdText.toLowerCase();

        if (
            !etdLower.includes('menit') &&
            !etdLower.includes('minutes') &&
            !etdLower.includes('jam') &&
            !etdLower.includes('hours')
        ) {
            etdText += ' Hari';
        }

        etd = `
            <small class="text-gray-500 text-sm">
                Estimasi In Syaa Allah: ${etdText}
            </small>`;
    }

    // Nama layanan
    let serviceName = service.service_name || service.service_type || "Layanan";

    // LOGO
    let logoUrl = '';
    const serviceKey = (service.service || 'default').toLowerCase().replace(/_/g, ' ');

    if (serviceKey.includes('gosend')) {
        logoUrl = 'https://tokosancaka.com/public/storage/logo-ekspedisi/gosend.png';
    } else if (serviceKey.includes('grab')) {
        logoUrl = 'https://tokosancaka.com/public/storage/logo-ekspedisi/grab.png';
    } else if (service.service) {
        logoUrl = `https://tokosancaka.com/public/storage/logo-ekspedisi/${service.service.toLowerCase()}.png`;
    }

    // HTML logo
    let imgHtml = '';
    if (logoUrl) {
        imgHtml = `
            <img src="${logoUrl}" alt="${serviceName}"
                 class="mr-4 h-10 w-16 object-contain"
                 onerror="this.style.display='none';">`;
    }

    // RETURN CARD
    return `
        <div class="flex justify-between items-center py-4">
            <div class="flex items-center">
                ${imgHtml}
                <div>
                    <h6 class="mb-0 text-blue-600 font-semibold text-lg">${serviceName}</h6>
                    <p class="mb-0 text-gray-700">${service.service_type || 'Layanan'}</p>
                    ${etd}
                </div>
            </div>
            <h5 class="mb-0 text-green-600 font-bold text-xl">
                ${formatRupiah(price)}
            </h5>
        </div>
    `;
};
// --- [AKHIR PERBAIKAN FUNGSI LOGO] ---


                // 1. Kategorisasi dan tambahkan numeric_price
                let instantServices = [];
                let expressServices = [];
                let cargoServices = [];

                // --- [PERBAIKAN LOGIKA INSTANT] ---
if (data.instant && data.instant.length > 0) {
    console.log("Memproses data Instant..."); // <-- LOG 7
    instantServices = data.instant.map(service => {
        if (!service) { return null; }
        service.numeric_price = getPrice(service);
        return service;
    }).filter(Boolean); // Filter null jika ada
}
// --- [AKHIR PERBAIKAN LOGIKA INSTANT] ---


                // Proses data 'express_cargo' (dari CekOngkirController@getExpressPricing)
                if (data.express_cargo && data.express_cargo.length > 0) {
                    data.express_cargo.forEach(service => {
                        service.numeric_price = getPrice(service);
                        const serviceType = (service.service_type || '').toLowerCase();
                        const serviceName = (service.service_name || '').toLowerCase();

                        // Logika pemisahan:
                        if (serviceType.includes('cargo') || serviceType.includes('kargo') || serviceName.includes('cargo') || serviceName.includes('kargo')) {
                            cargoServices.push(service);
                        } else {
                            expressServices.push(service);
                        }
                    });
                }

                // 2. Sortir setiap kategori dari termurah ke termahal
                instantServices.sort((a, b) => a.numeric_price - b.numeric_price);
                expressServices.sort((a, b) => a.numeric_price - b.numeric_price);
                cargoServices.sort((a, b) => a.numeric_price - b.numeric_price);

                // Cek jika tidak ada hasil sama sekali
                if (instantServices.length === 0 && expressServices.length === 0 && cargoServices.length === 0) {
                    resultsContainer.innerHTML = `
                        <div class="bg-white shadow-lg rounded-xl overflow-hidden p-6 md:p-8">
                            <h4 class="text-2xl font-bold text-gray-900">Hasil Pengecekan Ongkir</h4>
                            <p class="text-base text-gray-600 mb-4">Berat Dihitung: <strong class="font-semibold text-gray-800">${finalWeight} gram</strong></p>
                            <hr class="border-t border-gray-200 mb-4">
                            <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4" role="alert">
                                <p>Tidak ada layanan pengiriman yang ditemukan untuk rute ini.</p>
                            </div>
                        </div>`;
                    return; // Stop eksekusi
                }

                // 3. Tentukan tab aktif pertama
                let firstActiveTab = '';
                if (instantServices.length > 0) firstActiveTab = 'instant';
                else if (expressServices.length > 0) firstActiveTab = 'express';
                else if (cargoServices.length > 0) firstActiveTab = 'cargo';

                // 4. Bangun HTML (Header + Tab Nav)
                let html = `
                    <div class="bg-white shadow-lg rounded-xl overflow-hidden">
                        <div class="p-6 md:p-8">
                            <h4 class="text-2xl font-bold text-gray-900">Hasil Pengecekan Ongkir</h4>
                            <p class="text-base text-gray-600 mb-4">Berat Dihitung: <strong class="font-semibold text-gray-800">${finalWeight} gram</strong></p>
                            
                            <div class="border-b border-gray-200">
                                <ul class="flex flex-wrap -mb-px text-sm font-medium text-center" id="shipping-tabs" role="tablist">
                `;

                // 5. Bangun Tombol Tab (dinamis)
                if (instantServices.length > 0) {
                    const isActive = firstActiveTab === 'instant';
                    html += `
                        <li class="mr-2" role="presentation">
                            <button class="shipping-tab-btn inline-block p-4 border-b-2 rounded-t-lg ${isActive ? 'text-blue-600 border-blue-600 active' : 'border-transparent hover:text-gray-600 hover:border-gray-300'}" 
                                    id="tab-btn-instant" data-tab-target="#tab-instant" type="button" role="tab" aria-controls="tab-instant" aria-selected="${isActive}">
                                Instant (${instantServices.length})
                            </button>
                        </li>
                    `;
                }
                if (expressServices.length > 0) {
                    const isActive = firstActiveTab === 'express';
                    html += `
                        <li class="mr-2" role="presentation">
                            <button class="shipping-tab-btn inline-block p-4 border-b-2 rounded-t-lg ${isActive ? 'text-blue-600 border-blue-600 active' : 'border-transparent hover:text-gray-600 hover:border-gray-300'}" 
                                    id="tab-btn-express" data-tab-target="#tab-express" type="button" role="tab" aria-controls="tab-express" aria-selected="${isActive}">
                                Express (${expressServices.length})
                            </button>
                        </li>
                    `;
                }
                if (cargoServices.length > 0) {
                    const isActive = firstActiveTab === 'cargo';
                    html += `
                        <li role="presentation">
                            <button class="shipping-tab-btn inline-block p-4 border-b-2 rounded-t-lg ${isActive ? 'text-blue-600 border-blue-600 active' : 'border-transparent hover:text-gray-600 hover:border-gray-300'}" 
                                    id="tab-btn-cargo" data-tab-target="#tab-cargo" type="button" role="tab" aria-controls="tab-cargo" aria-selected="${isActive}">
                                Cargo (${cargoServices.length})
                            </button>
                        </li>
                    `;
                }

                html += `</ul></div>`; // Tutup Navigasi Tab

                // 6. Bangun Konten Tab
                html += `<div id="shipping-tab-content" class="mt-4">`;

                if (instantServices.length > 0) {
                    html += `<div id="tab-instant" class="shipping-tab-pane divide-y divide-gray-200 ${firstActiveTab !== 'instant' ? 'hidden' : ''}" 
                                  role="tabpanel" aria-labelledby="tab-btn-instant">`;
                    instantServices.forEach(service => { html += createServiceCard(service); });
                    html += `</div>`;
                }
                if (expressServices.length > 0) {
                    html += `<div id="tab-express" class="shipping-tab-pane divide-y divide-gray-200 ${firstActiveTab !== 'express' ? 'hidden' : ''}"
                                  role="tabpanel" aria-labelledby="tab-btn-express">`;
                    expressServices.forEach(service => { html += createServiceCard(service); });
                    html += `</div>`;
                }
                if (cargoServices.length > 0) {
                    html += `<div id="tab-cargo" class="shipping-tab-pane divide-y divide-gray-200 ${firstActiveTab !== 'cargo' ? 'hidden' : ''}"
                                  role="tabpanel" aria-labelledby="tab-btn-cargo">`;
                    cargoServices.forEach(service => { html += createServiceCard(service); });
                    html += `</div>`;
                }

                html += `</div>`; // Tutup Konten Tab
                html += `</div></div>`; // Tutup Card
                
                // 7. Injeksi HTML
                resultsContainer.innerHTML = html;

                // 8. Panggil fungsi untuk menambahkan listener ke tab
                attachTabListeners();
            }

            // --- FUNGSI TAMPILKAN ERROR ---
            function displayError(message) {
                resultsContainer.innerHTML = `
                    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4" role="alert">
                        <strong class="font-bold">Oops!</strong>
                        <span class="block sm:inline">${message}</span>
                    </div>
                `;
            }

            // --- FUNGSI ATUR STATUS LOADING (disesuaikan untuk Tailwind) ---
            function setLoading(isLoading) {
                if (isLoading) {
                    submitButton.disabled = true;
                    btnText.classList.add('hidden');
                    btnSpinner.classList.remove('hidden');
                } else {
                    submitButton.disabled = false;
                    btnText.classList.remove('hidden');
                    btnSpinner.classList.add('hidden');
                }
            }

        });
    </script>
@endpush