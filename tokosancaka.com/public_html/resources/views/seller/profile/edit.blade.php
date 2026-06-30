@extends('layouts.customer')

@push('styles')
    {{-- Select2 & Custom CSS agar menyatu dengan Tailwind --}}
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .select2-container .select2-selection--single {
            height: 42px !important;
            border: 1px solid #d1d5db !important;
            border-radius: 0.375rem !important;
            display: flex;
            align-items: center;
            padding-left: 0.25rem;
        }
        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 40px !important;
            right: 8px !important;
        }
        .select2-search__field:focus {
            border-color: #ef4444 !important;
            box-shadow: 0 0 0 1px #ef4444 !important;
        }
    </style>
@endpush

@section('content')
<div class="py-12 bg-gray-50 min-h-screen">
    <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">

        <div class="mb-6 flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">Profil Toko Saya</h2>
                <p class="text-sm text-gray-500 mt-1">Kelola informasi dasar dan lokasi toko Anda untuk pengiriman.</p>
            </div>
        </div>

        <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-gray-200">
            <div class="p-6 sm:p-8">

                {{-- Alert Messages --}}
                @if (session('success'))
                    <div class="mb-6 p-4 bg-green-50 border border-green-200 text-green-700 rounded-lg flex items-center">
                        <i class="fas fa-check-circle mr-3 text-xl"></i>
                        <span class="font-medium">{{ session('success') }}</span>
                    </div>
                @endif

                @if ($errors->any())
                    <div class="mb-6 p-4 bg-red-50 border border-red-200 text-red-700 rounded-lg flex items-start">
                        <i class="fas fa-exclamation-triangle mr-3 text-xl mt-0.5"></i>
                        <div>
                            <span class="font-bold">Gagal menyimpan perubahan:</span>
                            <ul class="list-disc list-inside mt-1 text-sm">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                @endif

                <div id="geocode-alert" class="hidden mb-6 p-4 rounded-lg flex items-center font-medium" role="alert"></div>

                <form method="POST" action="{{ route('seller.profile.update') }}" enctype="multipart/form-data" class="space-y-8">
                    @csrf
                    @method('PUT')

                    {{-- 1. INFORMASI DASAR --}}
                    <div>
                        <h3 class="text-lg font-bold text-gray-900 border-b pb-2 mb-4"><i class="fas fa-store text-red-600 mr-2"></i> Informasi Dasar</h3>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            {{-- Logo Upload (Kiri) --}}
                            <div class="col-span-1 flex flex-col items-center justify-start p-4 border-2 border-dashed border-gray-300 rounded-xl bg-gray-50">
                                <label class="block font-semibold text-sm text-gray-700 mb-3 w-full text-center">Logo Toko</label>
                                @if($store->seller_logo)
                                    <img src="{{ asset('public/storage/' . $store->seller_logo) }}" alt="Logo saat ini" class="w-32 h-32 object-cover rounded-full shadow-md mb-4 border-4 border-white">
                                @else
                                    <div class="w-32 h-32 bg-gray-200 rounded-full flex items-center justify-center text-gray-400 mb-4 shadow-inner">
                                        <i class="fas fa-image text-3xl"></i>
                                    </div>
                                @endif
                                <label for="logo" class="cursor-pointer bg-white px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none w-full text-center transition">
                                    <i class="fas fa-upload mr-2"></i> Ubah Logo
                                </label>
                                <input id="logo" class="hidden" type="file" name="logo" accept="image/jpeg,image/png,image/jpg" />
                                <p class="text-xs text-gray-400 mt-2 text-center">Format: JPG, PNG. Maks 2MB.</p>
                            </div>

                            {{-- Nama & Deskripsi (Kanan) --}}
                            <div class="col-span-1 md:col-span-2 space-y-4">
                                <div>
                                    <label for="name" class="block font-medium text-sm text-gray-700">Nama Toko <span class="text-red-500">*</span></label>
                                    <input id="name" class="block mt-1 w-full border-gray-300 rounded-md shadow-sm focus:border-red-500 focus:ring-red-500 sm:text-sm" type="text" name="name" value="{{ old('name', $store->name) }}" required />
                                </div>
                                <div>
                                    <label for="description" class="block font-medium text-sm text-gray-700">Deskripsi Toko</label>
                                    <textarea id="description" name="description" class="block mt-1 w-full border-gray-300 rounded-md shadow-sm focus:border-red-500 focus:ring-red-500 sm:text-sm" rows="5" placeholder="Ceritakan tentang toko dan produk Anda...">{{ old('description', $store->description) }}</textarea>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- 2. ALAMAT PENGIRIMAN --}}
                    <div>
                        <h3 class="text-lg font-bold text-gray-900 border-b pb-2 mb-4"><i class="fas fa-map-marked-alt text-red-600 mr-2"></i> Alamat & Pengiriman</h3>

                        {{-- Fitur Pencarian API KiriminAja --}}
                        <div class="mb-5 p-4 bg-red-50 border border-red-200 rounded-lg">
                            <label class="block font-bold text-sm text-red-800 mb-1"><i class="fas fa-search-location mr-1"></i> Pencarian Wilayah Otomatis</label>
                            <p class="text-xs text-red-600 mb-2">Ketik nama Kecamatan atau Desa, lalu pilih dari daftar agar ongkos kirim presisi.</p>
                            <select id="select2_alamat_toko" class="w-full">
                                @if($store->village && $store->district)
                                    <option value="" selected="selected">{{ $store->village }}, {{ $store->district }}, {{ $store->regency }}, {{ $store->province }}</option>
                                @endif
                            </select>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block font-medium text-sm text-gray-700">Provinsi</label>
                                <input id="province" class="block mt-1 w-full border-gray-200 bg-gray-100 rounded-md shadow-sm sm:text-sm text-gray-600" type="text" name="province" value="{{ old('province', $store->province) }}" readonly required />
                            </div>
                            <div>
                                <label class="block font-medium text-sm text-gray-700">Kabupaten/Kota</label>
                                <input id="regency" class="block mt-1 w-full border-gray-200 bg-gray-100 rounded-md shadow-sm sm:text-sm text-gray-600" type="text" name="regency" value="{{ old('regency', $store->regency) }}" readonly required />
                            </div>
                            <div>
                                <label class="block font-medium text-sm text-gray-700">Kecamatan</label>
                                <input id="district" class="block mt-1 w-full border-gray-200 bg-gray-100 rounded-md shadow-sm sm:text-sm text-gray-600" type="text" name="district" value="{{ old('district', $store->district) }}" readonly required />
                            </div>
                            <div>
                                <label class="block font-medium text-sm text-gray-700">Desa/Kelurahan</label>
                                <input id="village" class="block mt-1 w-full border-gray-200 bg-gray-100 rounded-md shadow-sm sm:text-sm text-gray-600" type="text" name="village" value="{{ old('village', $store->village) }}" readonly required />
                            </div>
                            <div class="sm:col-span-2">
                                <label class="block font-medium text-sm text-gray-700">Detail Jalan / Patokan <span class="text-red-500">*</span></label>
                                <textarea id="address_detail" name="address_detail" class="block mt-1 w-full border-gray-300 rounded-md shadow-sm focus:border-red-500 focus:ring-red-500 sm:text-sm" rows="2" placeholder="Cth: Jl. Mawar No 12, Samping Masjid" required>{{ old('address_detail', $store->address_detail) }}</textarea>
                            </div>
                            <div>
                                <label class="block font-medium text-sm text-gray-700">Kode Pos</label>
                                <input id="zip_code" class="block mt-1 w-full border-gray-200 bg-gray-100 rounded-md shadow-sm sm:text-sm text-gray-600" type="text" name="zip_code" value="{{ old('zip_code', $store->zip_code) }}" readonly />
                            </div>
                        </div>
                    </div>

                    {{-- 3. KOORDINAT PETA (MAPBOX) --}}
                    <div>
                        <div class="mb-4 sm:col-span-2 p-5 bg-blue-50 rounded-xl border border-blue-200 shadow-sm relative overflow-hidden">
                            <div class="absolute top-0 right-0 opacity-10">
                                <i class="fas fa-map text-9xl -mt-4 -mr-4 text-blue-900"></i>
                            </div>
                            <div class="relative z-10">
                                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-4 gap-3">
                                    <div>
                                        <h4 class="text-base font-bold text-blue-900"><i class="fas fa-satellite-dish mr-2"></i> Titik Koordinat Peta</h4>
                                        <p class="text-xs text-blue-700 mt-1">Dibutuhkan untuk pengiriman kurir lokal & instan.</p>
                                    </div>
                                    <button type="button" id="btn-get-gps" class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold text-xs rounded-lg shadow-sm transition">
                                        <i class="fas fa-crosshairs mr-2"></i> Deteksi via GPS (Akurat)
                                    </button>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block font-semibold text-xs text-blue-800 uppercase tracking-wider mb-1">Latitude</label>
                                        <input type="text" name="latitude" id="latitude" value="{{ old('latitude', $store->latitude ?? '') }}" class="block w-full border-blue-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm font-mono" placeholder="-7.1234567" readonly>
                                    </div>
                                    <div>
                                        <label class="block font-semibold text-xs text-blue-800 uppercase tracking-wider mb-1">Longitude</label>
                                        <input type="text" name="longitude" id="longitude" value="{{ old('longitude', $store->longitude ?? '') }}" class="block w-full border-blue-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm font-mono" placeholder="110.1234567" readonly>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- TOMBOL SIMPAN --}}
                    <div class="flex items-center justify-end pt-4 border-t border-gray-200">
                        <button type="submit" class="inline-flex items-center px-6 py-3 bg-red-600 border border-transparent rounded-lg font-bold text-sm text-white uppercase tracking-widest hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 shadow-md transition-all">
                            <i class="fas fa-save mr-2"></i> Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
$(document).ready(function() {
    const latInput = document.getElementById('latitude');
    const lonInput = document.getElementById('longitude');
    const alertBox = document.getElementById('geocode-alert');
    const btnGps = document.getElementById('btn-get-gps');

    // ========================================================
    // 1. SELECT 2 UNTUK PENCARIAN ALAMAT (API KIRIMINAJA)
    // ========================================================
    $('#select2_alamat_toko').select2({
        width: '100%',
        placeholder: 'Ketik Kelurahan / Kecamatan (min. 3 huruf)...',
        allowClear: true,
        ajax: {
            url: "{{ url('/checkout/search-address-ajax') }}", // Panggil route ajax pencarian alamat Anda
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return { q: params.term };
            },
            processResults: function (data) {
                return { results: data.results };
            },
            cache: true
        },
        minimumInputLength: 3,
    });

    $('#select2_alamat_toko').on('select2:select', function (e) {
        const data = e.params.data;
        let parts = data.raw_address.split(', ');

        // Isi form otomatis
        $('#village').val(parts[0] || '');
        $('#district').val(parts[1] || '');
        $('#regency').val(parts[2] || '');
        $('#province').val(parts[3] || '');
        $('#zip_code').val(parts[4] || '');

        // Otomatis tembak API Koordinat setelah alamat terpilih
        autoGeocodeAddress(data.raw_address);

        // Fokuskan kursor ke detail alamat
        document.getElementById('address_detail').focus();
    });

    // ========================================================
    // 2. FUNGSI TEMBAK API KOORDINAT (NOMINATIM)
    // ========================================================
    async function autoGeocodeAddress(addressText) {
        showAlert(`Mencari titik koordinat peta untuk: <b>${addressText}</b>...`, 'info');

        try {
            const response = await fetch(`https://nominatim.openstreetmap.org/search?q=${encodeURIComponent(addressText)}&format=json&limit=1&countrycodes=id`);
            if (!response.ok) throw new Error('Network response was not ok.');

            const data = await response.json();
            if (data && data.length > 0) {
                latInput.value = parseFloat(data[0].lat).toFixed(7);
                lonInput.value = parseFloat(data[0].lon).toFixed(7);
                showAlert('Koordinat berhasil dipetakan dari alamat!', 'success');
            } else {
                showAlert('Koordinat presisi gagal ditemukan. Coba gunakan fitur "Deteksi via GPS" untuk hasil lebih akurat.', 'error');
            }
        } catch (error) {
            console.error('Geocode Error:', error);
            showAlert('Sistem gagal mengambil koordinat. Anda bisa menyimpannya, sistem akan mencari ulang di background.', 'error');
        }
    }

    // ========================================================
    // 3. FUNGSI GPS BROWSER (HP / LAPTOP)
    // ========================================================
    btnGps.addEventListener('click', function() {
        if (!navigator.geolocation) {
            showAlert("Browser perangkat Anda tidak mendukung fitur lokasi GPS.", "error");
            return;
        }

        const originalBtnText = btnGps.innerHTML;
        btnGps.innerHTML = `<i class="fas fa-spinner fa-spin mr-2"></i> Mencari Satelit...`;
        btnGps.disabled = true;

        navigator.geolocation.getCurrentPosition(
            function(position) {
                latInput.value = position.coords.latitude;
                lonInput.value = position.coords.longitude;
                showAlert('<i class="fas fa-satellite text-green-600 mr-2"></i> Titik koordinat GPS yang sangat presisi berhasil diamankan!', 'success');

                btnGps.innerHTML = originalBtnText;
                btnGps.disabled = false;
            },
            function(error) {
                let msg = "Gagal mengambil lokasi. ";
                if(error.code === 1) msg += "Mohon izinkan akses lokasi (Location) pada browser Anda.";
                else if(error.code === 2) msg += "Sinyal GPS / Internet tidak stabil.";
                else msg += "Waktu permintaan habis (Timeout).";

                showAlert(msg, 'error');
                btnGps.innerHTML = originalBtnText;
                btnGps.disabled = false;
            },
            { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
        );
    });

    // ========================================================
    // HELPER: TAMPILAN ALERT
    // ========================================================
    function showAlert(message, type = 'info') {
        alertBox.className = 'mb-6 p-4 rounded-lg flex items-center font-medium transition-all duration-300'; // reset

        if (type === 'error') {
            alertBox.classList.add('bg-red-50', 'border', 'border-red-200', 'text-red-700');
            alertBox.innerHTML = `<i class="fas fa-times-circle mr-3 text-xl"></i> <div>${message}</div>`;
        } else if (type === 'success') {
            alertBox.classList.add('bg-green-50', 'border', 'border-green-200', 'text-green-700');
            alertBox.innerHTML = `<i class="fas fa-check-circle mr-3 text-xl"></i> <div>${message}</div>`;
        } else {
            alertBox.classList.add('bg-blue-50', 'border', 'border-blue-200', 'text-blue-700');
            alertBox.innerHTML = `<i class="fas fa-info-circle mr-3 text-xl"></i> <div>${message}</div>`;
        }

        alertBox.classList.remove('hidden');
    }
});
</script>
@endpush
