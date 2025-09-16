@extends('layouts.customer')

@section('title', 'Cek Ongkos Kirim')

@push('styles')
<style>
    .search-result-item { transition: background-color 0.2s ease-in-out; }
    .search-result-item:hover { background-color: #f0f4f8; }

    /* Gaya baru untuk filter radio dan hasil ongkir */
    .filter-radio:checked + label {
        background-color: #fee2e2; /* red-50 */
        border-color: #fca5a5; /* red-300 */
        color: #b91c1c;       /* red-700 */
    }
    .filter-radio {
        display: none;
    }
    .filter-label {
        display: flex;
        align-items: center;
        padding: 0.75rem 1.25rem;
        border: 2px solid transparent;
        border-radius: 0.5rem;
        cursor: pointer;
        transition: all 0.2s ease-in-out;
        font-weight: 500;
        color: #374151;
        background-color: #f9fafb;
        border: 1px solid #e5e7eb;
    }
    .filter-label:hover {
        border-color: #d1d5db;
    }
    .filter-label span {
        display: inline-block;
        width: 1rem;
        height: 1rem;
        border-radius: 50%;
        border: 2px solid #d1d5db;
        margin-right: 0.75rem;
        transition: all 0.2s ease-in-out;
    }
    .filter-radio:checked + label span {
        border-color: #dc2626; /* red-600 */
        background-color: #dc2626; /* red-600 */
        box-shadow: 0 0 0 2px white inset;
    }
</style>
@endpush

@section('content')
<div class="w-full max-w-4xl mx-auto bg-white p-8 rounded-xl shadow-lg">
    <div class="text-center mb-8">
        <h1 class="text-3xl font-bold text-gray-800">Cek Ongkos Kirim</h1>
        <p class="text-gray-500 mt-2">Ditenagai oleh KiriminAja</p>
    </div>

    <form id="shipping-form" class="space-y-6">
        @csrf
        <div class="relative">
            <label for="origin" class="block text-sm font-medium text-gray-700 mb-1">Alamat Asal</label>
            <input type="text" id="origin" name="origin_text" autocomplete="off" class="w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm" placeholder="Ketik nama Kecamatan/Kelurahan..." required>
            <input type="hidden" id="origin_id" name="origin_id">
            <input type="hidden" id="origin_subdistrict_id" name="origin_subdistrict_id">
            <div id="origin-results" class="absolute z-10 w-full bg-white border border-gray-300 rounded-md mt-1 max-h-60 overflow-y-auto shadow-lg hidden"></div>
        </div>

        <div class="relative">
            <label for="destination" class="block text-sm font-medium text-gray-700 mb-1">Alamat Tujuan</label>
            <input type="text" id="destination" name="destination_text" autocomplete="off" class="w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm" placeholder="Ketik nama Kecamatan/Kelurahan..." required>
            <input type="hidden" id="destination_id" name="destination_id">
            <input type="hidden" id="destination_subdistrict_id" name="destination_subdistrict_id">
            <div id="destination-results" class="absolute z-10 w-full bg-white border border-gray-300 rounded-md mt-1 max-h-60 overflow-y-auto shadow-lg hidden"></div>
        </div>

        <div>
            <label for="weight" class="block text-sm font-medium text-gray-700 mb-1">Berat (gram)</label>
            <input type="number" id="weight" name="weight" class="w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm" placeholder="Contoh: 1000" min="1" required>
        </div>

        <div id="dimension-fields">
            <label class="block text-sm font-medium text-gray-700 mb-1">Dimensi Paket (cm)</label>
            <div class="grid grid-cols-3 gap-4">
                <input type="number" id="length" name="length" placeholder="Panjang" class="w-full px-4 py-2 border border-gray-300 rounded-md">
                <input type="number" id="width" name="width" placeholder="Lebar" class="w-full px-4 py-2 border border-gray-300 rounded-md">
                <input type="number" id="height" name="height" placeholder="Tinggi" class="w-full px-4 py-2 border border-gray-300 rounded-md">
            </div>
            <p class="text-xs text-gray-500 mt-1">Isi jika ongkir dihitung berdasarkan volume.</p>
        </div>

        <div>
            <label for="item_value" class="block text-sm font-medium text-gray-700 mb-1">Nilai Barang (Rp)</label>
            <input type="number" id="item_value" name="item_value" placeholder="Contoh: 500000" class="w-full px-4 py-2 border border-gray-300 rounded-md">
            <p class="text-xs text-gray-500 mt-1">Isi untuk perhitungan biaya asuransi.</p>
            <div class="mt-2">
                <label for="insurance" class="flex items-center">
                    <input type="checkbox" id="insurance" name="insurance" class="h-4 w-4 text-red-600 border-gray-300 rounded focus:ring-red-500">
                    <span class="ml-2 text-sm text-gray-700">Gunakan Asuransi</span>
                </label>
            </div>
        </div>

        <div>
            <button type="submit" class="w-full bg-red-600 text-white font-bold py-3 px-4 rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition duration-300 flex items-center justify-center" id="submit-button">
                Cek Harga
            </button>
        </div>
    </form>

    <div id="cost-results-container" class="mt-10"></div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    
    // ... (fungsi debounce dan setupAutocomplete tidak diubah) ...
    const debounce = (func, delay) => {
        let timeout;
        return (...args) => { clearTimeout(timeout); timeout = setTimeout(() => func.apply(this, args), delay); };
    };
    const setupAutocomplete = (inputId, resultsId, hiddenId, hiddenSubId) => {
        const input = document.getElementById(inputId);
        const resultsContainer = document.getElementById(resultsId);
        const hiddenInput = document.getElementById(hiddenId);
        const hiddenSubInput = document.getElementById(hiddenSubId);
        const handleSearch = async (event) => {
            const query = event.target.value;
            if (query.length < 3) { resultsContainer.classList.add('hidden'); return; }
            try {
                const response = await fetch(`{{ route('api.ongkir.address.search') }}?search=${query}`);
                if (!response.ok) { const errorData = await response.json().catch(() => ({ message: 'Gagal memuat data alamat.' })); throw new Error(errorData.message || 'Terjadi kesalahan pada server.'); }
                const result = await response.json();
                resultsContainer.innerHTML = '';
                if (result && Array.isArray(result) && result.length > 0) {
                    resultsContainer.classList.remove('hidden');
                    result.forEach(item => {
                        const div = document.createElement('div');
                        div.className = 'p-3 cursor-pointer search-result-item';
                        const displayText = item.full_address;
                        div.textContent = displayText;
                        div.dataset.id = item.district_id;
                        div.dataset.subId = item.subdistrict_id;
                        div.addEventListener('click', () => {
                            input.value = item.full_address;
                            hiddenInput.value = item.district_id;
                            hiddenSubInput.value = item.subdistrict_id;
                            resultsContainer.classList.add('hidden');
                        });
                        resultsContainer.appendChild(div);
                    });
                } else {
                    resultsContainer.innerHTML = `<div class="p-3 text-gray-500">Alamat tidak ditemukan.</div>`;
                    resultsContainer.classList.remove('hidden');
                }
            } catch (error) {
                console.error('Error fetching address:', error);
                resultsContainer.innerHTML = `<div class="p-3 text-red-500">${error.message}</div>`;
                resultsContainer.classList.remove('hidden');
            }
        };
        input.addEventListener('input', debounce(handleSearch, 350));
        document.addEventListener('click', (event) => {
            if (!resultsContainer.contains(event.target) && !input.contains(event.target)) {
                resultsContainer.classList.add('hidden');
            }
        });
    };
    setupAutocomplete('origin', 'origin-results', 'origin_id', 'origin_subdistrict_id');
    setupAutocomplete('destination', 'destination-results', 'destination_id', 'destination_subdistrict_id');

    const shippingForm = document.getElementById('shipping-form');
    const costResultsContainer = document.getElementById('cost-results-container');
    const submitButton = document.getElementById('submit-button');

    shippingForm.addEventListener('submit', async function(event) {
        event.preventDefault();
        submitButton.disabled = true;
        submitButton.innerHTML = `<svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Sedang Menghitung Ongkos Kirim, Mohon ditunggu Ya Kak...`;
        costResultsContainer.innerHTML = '';
        const formData = new FormData(this);
        if (document.getElementById('insurance').checked) { formData.set('insurance', 'on'); } else { formData.delete('insurance'); }
        try {
            const response = await fetch("{{ route('api.ongkir.cost.check') }}", { method: 'POST', body: formData, headers: { 'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value, 'Accept': 'application/json', } });
            const result = await response.json();
            if (response.ok && result.success) {
                displayResults(result);
            } else { throw new Error(result.message || 'Terjadi kesalahan.'); }
        } catch (error) {
            costResultsContainer.innerHTML = `<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert"><strong class="font-bold">Error!</strong> <span class="block sm:inline">${error.message}</span></div>`;
        } finally {
            submitButton.disabled = false;
            submitButton.innerHTML = 'Cek Harga';
        }
    });

    function displayResults(result) {
        const { final_weight, data } = result;
        const instantServices = data.instant || [];
        const expressCargoServices = data.express_cargo || [];

        let html = '';
        if (final_weight) {
            const weightInKg = (final_weight / 1000).toFixed(2).replace('.', ',');
            html += `<div class="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                        <p class="text-sm text-blue-700 font-semibold">Total Berat Dihitung</p>
                        <p class="text-2xl font-bold text-blue-800">${weightInKg} kg (${final_weight.toLocaleString('id-ID')} gram)</p>
                     </div>`;
        }

        html += `<h2 class="text-xl font-bold mb-4 text-gray-800">Pilihan Kurir</h2>
                 <div class="flex flex-col md:flex-row gap-8">
                    <div class="w-full md:w-1/4">
                        <div class="space-y-3">
                            <div>
                                <input type="radio" name="service_filter" id="filter_regular" value="regular" class="filter-radio" checked>
                                <label for="filter_regular" class="filter-label"><span></span>Reguler</label>
                            </div>
                             <div>
                                <input type="radio" name="service_filter" id="filter_instant" value="instant" class="filter-radio">
                                <label for="filter_instant" class="filter-label"><span></span>Instant</label>
                            </div>
                            <div>
                                <input type="radio" name="service_filter" id="filter_cargo" value="trucking" class="filter-radio">
                                <label for="filter_cargo" class="filter-label"><span></span>Cargo</label>
                            </div>
                        </div>
                    </div>
                    <div class="w-full md:w-3/4" id="service-list-container">
                        {{-- Hasil akan dirender di sini oleh JavaScript --}}
                    </div>
                 </div>`;
        
        costResultsContainer.innerHTML = html;

        const serviceListContainer = document.getElementById('service-list-container');

        const renderServices = (filter) => {
            let servicesHtml = '';
            if (filter === 'instant') {
                servicesHtml = generateInstantHtml(instantServices);
            } else {
                const filteredServices = expressCargoServices.filter(service => {
                    const group = service.group ? service.group.toLowerCase() : '';
                    const serviceName = service.service_name ? service.service_name.toLowerCase() : '';
                    if (filter === 'trucking') {
                        return group === 'trucking' || serviceName.includes('cargo');
                    }
                    if (filter === 'regular') {
                        return group === 'regular' && !serviceName.includes('cargo');
                    }
                    return false;
                });
                servicesHtml = generateExpressCargoHtml(filteredServices);
            }
            serviceListContainer.innerHTML = servicesHtml;
        };

        document.querySelectorAll('input[name="service_filter"]').forEach(radio => {
            radio.addEventListener('change', (e) => renderServices(e.target.value));
        });

        renderServices('regular');
    }

    function generateExpressCargoHtml(services) {
        if (!services || services.length === 0) {
            return '<div class="text-center p-4 text-gray-600 bg-yellow-100 rounded-lg">Layanan tidak tersedia.</div>';
        }
        let html = `<div class="hidden md:grid grid-cols-5 gap-4 text-xs font-bold text-gray-500 uppercase pb-2 border-b mb-4">
                        <div class="col-span-2">Kurir & Layanan</div>
                        <div class="text-right">Estimasi</div>
                        <div class="text-right">Harga</div>
                        <div class="text-right">Aksi</div>
                     </div>`;
        services.sort((a, b) => parseInt(a.cost) - parseInt(b.cost));
        services.forEach(service => {
            const cost = parseInt(service.cost).toLocaleString('id-ID');
            html += `<div class="grid grid-cols-2 md:grid-cols-5 gap-4 items-center p-4 border-b">
                        <div class="col-span-2 flex items-center gap-4">
                            <img src="https://tokosancaka.com/storage/logo-ekspedisi/${service.service}.png" alt="${service.service}" class="h-6 object-contain" onerror="this.style.display='none'">
                            <div>
                                <p class="font-semibold text-gray-800">${service.service.toUpperCase()}</p>
                                <p class="text-xs text-gray-500">${service.service_name}</p>
                            </div>
                        </div>
                        <div class="hidden md:block text-sm text-gray-600 text-right">${service.etd} Hari</div>
                        <div class="text-right font-semibold text-gray-800">Rp ${cost}</div>
                        <div class="col-span-2 md:col-span-1 mt-2 md:mt-0 flex justify-end">
                            <button class="bg-red-500 text-white px-4 py-2 rounded-md text-sm font-semibold hover:bg-red-600">Pilih</button>
                        </div>
                        <div class="md:hidden col-span-2 text-sm text-gray-600 mt-2">Estimasi: ${service.etd} Hari</div>
                     </div>`;
        });
        return html;
    }

    function generateInstantHtml(services) {
        if (!services || services.length === 0) {
            return '<div class="text-center p-4 text-gray-600 bg-yellow-100 rounded-lg">Layanan Instant tidak tersedia.</div>';
        }
        let html = `<div class="hidden md:grid grid-cols-4 gap-4 text-xs font-bold text-gray-500 uppercase pb-2 border-b mb-4">
                        <div class="col-span-2">Kurir & Layanan</div>
                        <div class="text-right">Harga</div>
                        <div class="text-right">Aksi</div>
                     </div>`;
        let allServices = [];
        services.forEach(courier => {
            if(courier.costs && Array.isArray(courier.costs)) {
                courier.costs.forEach(cost => { allServices.push({ courierName: courier.name, ...cost }); });
            }
        });
        allServices.sort((a, b) => a.price.total_price - b.price.total_price);
        allServices.forEach(service => {
            const cost = parseInt(service.price.total_price).toLocaleString('id-ID');
            html += `<div class="grid grid-cols-2 md:grid-cols-4 gap-4 items-center p-4 border-b">
                        <div class="col-span-2 flex items-center gap-4">
                             <img src="https://tokosancaka.com/storage/logo-ekspedisi/${service.courierName}.png" alt="${service.courierName}" class="h-6 object-contain" onerror="this.style.display='none'">
                             <div>
                                <p class="font-semibold text-gray-800">${(service.courierName || 'N/A').toUpperCase()}</p>
                                <p class="text-xs text-gray-500">${service.service_type}</p>
                             </div>
                        </div>
                        <div class="text-right font-semibold text-gray-800">Rp ${cost}</div>
                        <div class="col-span-2 md:col-span-1 mt-2 md:mt-0 flex justify-end">
                            <button class="bg-red-500 text-white px-4 py-2 rounded-md text-sm font-semibold hover:bg-red-600">Pilih</button>
                        </div>
                     </div>`;
        });
        return html;
    }
});
</script>
@endpush

