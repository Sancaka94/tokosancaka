@extends('layouts.customer')

@push('styles')
    {{-- CSS Select2 untuk Pencarian API KiriminAja --}}
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        /* Custom Select2 agar menyatu dengan style Tailwind */
        .select2-container .select2-selection--single {
            height: 42px !important;
            border: 1px solid #d1d5db !important;
            border-radius: 0.5rem !important;
            display: flex;
            align-items: center;
            padding-left: 0.5rem;
            background-color: #ffffff;
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

        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold text-gray-800">Profil Toko Saya</h2>
        </div>

        <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-gray-100">
            <div class="p-8">

                {{-- Alert Sukses dari Controller --}}
                @if (session('success'))
                    <div class="mb-6 p-4 bg-green-50 border-l-4 border-green-500 text-green-700 rounded-r-lg flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                        {{ session('success') }}
                    </div>
                @endif

                {{-- Alert Error dari Controller --}}
                @if (session('error'))
                    <div class="mb-6 p-4 bg-red-50 border-l-4 border-red-500 text-red-700 rounded-r-lg">
                        {{ session('error') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div class="mb-6 p-4 bg-red-50 border-l-4 border-red-500 text-red-700 rounded-r-lg">
                        <ul class="list-disc list-inside space-y-1 text-sm">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                {{-- Notifikasi Sukses/Error Geocoding Javascript (Hidden by default) --}}
                <div id="geocode-alert" class="hidden mb-6 p-4 border-l-4 rounded-r-lg text-sm transition-all duration-300" role="alert"></div>

                <form method="POST" action="{{ route('seller.profile.update') }}" enctype="multipart/form-data" class="space-y-6">
                    @csrf
                    @method('PUT')

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="md:col-span-2">
                            <label for="name" class="block font-semibold text-sm text-gray-700 mb-1">Nama Toko</label>
                            <input id="name" class="block w-full border-gray-300 rounded-lg shadow-sm focus:border-red-500 focus:ring-red-500 transition" type="text" name="name" value="{{ old('name', $store->name) }}" required />
                        </div>

                        <div class="md:col-span-2">
                            <label for="description" class="block font-semibold text-sm text-gray-700 mb-1">Deskripsi</label>
                            <textarea id="description" name="description" class="block w-full border-gray-300 rounded-lg shadow-sm focus:border-red-500 focus:ring-red-500 transition" rows="3" required>{{ old('description', $store->description) }}</textarea>
                        </div>

                        {{-- Section API KiriminAja --}}
                        <div class="md:col-span-2 p-5 bg-blue-50 border border-blue-100 rounded-xl">
                            <label class="block font-bold text-sm text-blue-800 mb-2">Pencarian Wilayah Otomatis (API KiriminAja)</label>
                            <select id="select2_alamat_toko" class="block w-full border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500"></select>
                            <p class="text-xs text-blue-600 mt-2"><i class="fas fa-info-circle"></i> Ketik nama Kecamatan / Kelurahan untuk mengisi otomatis form wilayah di bawah ini, dan sistem akan langsung mencari titik koordinat Anda.</p>
                        </div>

                        {{-- Input Wilayah (Dibuat Readonly agar ejaan sesuai dengan API KiriminAja) --}}
                        <div>
                            <label for="province" class="block font-semibold text-sm text-gray-700 mb-1">Provinsi</label>
                            <input id="province" class="block w-full border-gray-300 rounded-lg shadow-sm bg-gray-100 text-gray-600 cursor-not-allowed focus:outline-none" type="text" name="province" value="{{ old('province', $store->province) }}" readonly required />
                        </div>

                        <div>
                            <label for="regency" class="block font-semibold text-sm text-gray-700 mb-1">Kabupaten/Kota</label>
                            <input id="regency" class="block w-full border-gray-300 rounded-lg shadow-sm bg-gray-100 text-gray-600 cursor-not-allowed focus:outline-none" type="text" name="regency" value="{{ old('regency', $store->regency) }}" readonly required />
                        </div>

                        <div>
                            <label for="district" class="block font-semibold text-sm text-gray-700 mb-1">Kecamatan</label>
                            <input id="district" class="block w-full border-gray-300 rounded-lg shadow-sm bg-gray-100 text-gray-600 cursor-not-allowed focus:outline-none" type="text" name="district" value="{{ old('district', $store->district) }}" readonly required />
                        </div>

                        <div>
                            <label for="village" class="block font-semibold text-sm text-gray-700 mb-1">Desa/Kelurahan</label>
                            <input id="village" class="block w-full border-gray-300 rounded-lg shadow-sm bg-gray-100 text-gray-600 cursor-not-allowed focus:outline-none" type="text" name="village" value="{{ old('village', $store->village) }}" readonly required />
                        </div>

                        <div>
                            <label for="zip_code" class="block font-semibold text-sm text-gray-700 mb-1">Kode Pos</label>
                            <input id="zip_code" class="block w-full border-gray-300 rounded-lg shadow-sm bg-gray-100 text-gray-600 cursor-not-allowed focus:outline-none" type="text" name="zip_code" value="{{ old('zip_code', $store->zip_code) }}" readonly required />
                        </div>

                        <div class="md:col-span-2">
                            <label for="address_detail" class="block font-semibold text-sm text-gray-700 mb-1">Detail Alamat (Nama Jalan, RT/RW, Blok)</label>
                            <textarea id="address_detail" name="address_detail" class="block w-full border-gray-300 rounded-lg shadow-sm focus:border-red-500 focus:ring-red-500 transition" rows="2" placeholder="Contoh: Jl. Sudirman No. 10, RT 01 / RW 02" required>{{ old('address_detail', $store->address_detail) }}</textarea>
                        </div>

                        {{-- Section Koordinat GPS --}}
                        <div class="md:col-span-2 p-5 bg-red-50/50 rounded-xl border border-red-100">
                            <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center mb-4 gap-3">
                                <div>
                                    <h4 class="text-base font-bold text-gray-800">Koordinat Peta</h4>
                                    <p class="text-xs text-gray-500 mt-1">Koordinat akan terisi otomatis saat Anda memilih wilayah dari form pencarian KiriminAja di atas.</p>
                                </div>
                                <button type="button" id="btn-cari-koordinat" class="inline-flex items-center justify-center px-4 py-2 bg-red-100 hover:bg-red-200 text-red-700 font-bold text-sm rounded-lg transition-colors shadow-sm whitespace-nowrap">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4 mr-2">
                                      <path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 1 0 0 11 5.5 5.5 0 0 0 0-11ZM2 9a7 7 0 1 1 12.452 4.391l3.328 3.329a.75.75 0 1 1-1.06 1.06l-3.329-3.328A7 7 0 0 1 2 9Z" clip-rule="evenodd" />
                                    </svg>
                                    Cari Ulang Koordinat
                                </button>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label for="latitude" class="block font-semibold text-sm text-gray-700 mb-1">Latitude</label>
                                    <input type="text" name="latitude" id="latitude" value="{{ old('latitude', $store->latitude ?? '') }}" class="block w-full border-gray-300 rounded-lg shadow-sm focus:ring-red-500 focus:border-red-500 bg-white" placeholder="-7.1234567">
                                    @error('latitude') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label for="longitude" class="block font-semibold text-sm text-gray-700 mb-1">Longitude</label>
                                    <input type="text" name="longitude" id="longitude" value="{{ old('longitude', $store->longitude ?? '') }}" class="block w-full border-gray-300 rounded-lg shadow-sm focus:ring-red-500 focus:border-red-500 bg-white" placeholder="110.1234567">
                                    @error('longitude') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                                </div>
                            </div>
                        </div>

                        <div class="md:col-span-2">
                            <label for="logo" class="block font-semibold text-sm text-gray-700 mb-1">Logo Toko</label>
                            <input id="logo" class="block w-full border-gray-300 rounded-lg shadow-sm p-2 bg-gray-50 focus:border-red-500 focus:ring-red-500 transition" type="file" name="logo" accept="image/jpeg,image/png,image/jpg" />
                            @if($store->seller_logo)
                            <div class="mt-4 flex items-center gap-4">
                                <img src="{{ asset('storage/' . $store->seller_logo) }}" alt="Logo saat ini" class="w-16 h-16 object-cover rounded-xl shadow-sm border border-gray-200">
                                <span class="text-sm text-gray-500">Logo saat ini. Upload file baru jika ingin mengganti.</span>
                            </div>
                            @endif
                        </div>
                    </div>

                    <div class="flex items-center justify-end mt-8 pt-6 border-t border-gray-100">
                        <button type="submit" class="inline-flex items-center px-6 py-3 bg-red-600 border border-transparent rounded-lg font-bold text-sm text-white uppercase tracking-wider hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-all shadow-md hover:shadow-lg">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                            Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
{{-- Library jQuery & Select2 --}}
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
$(document).ready(function() {
    // ---------------------------------------------------------
    // 1. Inisialisasi Pencarian Alamat KiriminAja (Select2)
    // ---------------------------------------------------------
    $('#select2_alamat_toko').select2({
        width: '100%',
        placeholder: 'Ketik min. 3 huruf (Cth: Ngawi / Margomulyo)...',
        allowClear: true,
        ajax: {
            url: "{{ url('/checkout/search-address-ajax') }}",
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

    // ---------------------------------------------------------
    // 2. Aksi Saat Wilayah Dipilih dari Dropdown Select2
    // ---------------------------------------------------------
    $('#select2_alamat_toko').on('select2:select', function (e) {
        const data = e.params.data;

        // Memecah format response: "Kelurahan, Kecamatan, Kota, Provinsi, Kode Pos"
        let parts = data.raw_address.split(', ');

        let kelurahan = parts[0] || '';
        let kecamatan = parts[1] || '';
        let kota      = parts[2] || '';
        let provinsi  = parts[3] || '';
        let kode_pos  = parts[4] || '';

        // Auto-fill form input yang readonly
        $('#village').val(kelurahan);
        $('#district').val(kecamatan);
        $('#regency').val(kota);
        $('#province').val(provinsi);
        $('#zip_code').val(kode_pos);

        // Arahkan kursor ke input detail alamat
        $('#address_detail').focus();

        // TRIGGER OTOMATIS: Klik tombol cari koordinat
        setTimeout(() => {
            $('#btn-cari-koordinat').click();
        }, 500);
    });

    // ---------------------------------------------------------
    // 3. Logika Geocoding (Pencarian Otomatis Titik Peta API Mapbox/Nominatim)
    // ---------------------------------------------------------
    const searchButton = document.getElementById('btn-cari-koordinat');
    const latInput = document.getElementById('latitude');
    const lonInput = document.getElementById('longitude');
    const alertBox = document.getElementById('geocode-alert');

    if (searchButton) {
        searchButton.addEventListener('click', async function() {
            const province = document.getElementById('province').value.trim();
            const regency = document.getElementById('regency').value.trim();
            const district = document.getElementById('district').value.trim();
            const village = document.getElementById('village').value.trim();

            if (!district || !regency) {
                showAlert('Harap pilih wilayah dari pencarian KiriminAja di atas terlebih dahulu.', 'error');
                return;
            }

            // Membangun array pencarian (diurutkan dari spesifik ke umum)
            let addressParts = [village, district, regency, province].filter(part => part !== '');
            let fullAddress = addressParts.join(', ');

            // UI Loading state
            this.disabled = true;
            this.innerHTML = `<svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-red-700" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Mencari...`;
            showAlert('Mencari koordinat untuk: ' + fullAddress, 'info');

            try {
                // Tembak API Nominatim OpenStreetMap
                let response = await fetch(`https://nominatim.openstreetmap.org/search?q=${encodeURIComponent(fullAddress)}&format=json&limit=1&countrycodes=id`);
                let data = await response.json();

                // Fallback: Jika Kelurahan tidak dikenali, cari menggunakan Kecamatan + Kabupaten
                if (data.length === 0 && village !== '') {
                    showAlert('Mencoba memperluas pencarian wilayah...', 'info');
                    let fallbackAddress = [district, regency, province].filter(part => part !== '').join(', ');
                    response = await fetch(`https://nominatim.openstreetmap.org/search?q=${encodeURIComponent(fallbackAddress)}&format=json&limit=1&countrycodes=id`);
                    data = await response.json();
                }

                if (data && data.length > 0) {
                    latInput.value = parseFloat(data[0].lat).toFixed(7);
                    lonInput.value = parseFloat(data[0].lon).toFixed(7);
                    showAlert('Koordinat berhasil ditemukan dan diisi otomatis!', 'success');

                    // Efek visual kedip sukses pada input form
                    latInput.classList.add('ring-2', 'ring-green-500');
                    lonInput.classList.add('ring-2', 'ring-green-500');
                    setTimeout(() => {
                        latInput.classList.remove('ring-2', 'ring-green-500');
                        lonInput.classList.remove('ring-2', 'ring-green-500');
                    }, 2000);

                } else {
                    showAlert('Koordinat tidak ditemukan secara presisi. Anda dapat mengisinya secara manual dari Google Maps.', 'error');
                }
            } catch (error) {
                console.error('Error fetching geocode:', error);
                showAlert('Gagal mengambil koordinat. Periksa koneksi internet Anda.', 'error');
            } finally {
                // Kembalikan status tombol pencari
                this.disabled = false;
                this.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4 mr-2"><path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 1 0 0 11 5.5 5.5 0 0 0 0-11ZM2 9a7 7 0 1 1 12.452 4.391l3.328 3.329a.75.75 0 1 1-1.06 1.06l-3.329-3.328A7 7 0 0 1 2 9Z" clip-rule="evenodd" /></svg> Cari Ulang Koordinat`;
            }
        });
    }

    // Custom Alert Logic UI
    function showAlert(message, type = 'info') {
        alertBox.classList.remove('hidden', 'bg-red-50', 'border-red-500', 'text-red-800', 'bg-green-50', 'border-green-500', 'text-green-800', 'bg-blue-50', 'border-blue-500', 'text-blue-800');

        if (type === 'error') {
            alertBox.classList.add('bg-red-50', 'border-red-500', 'text-red-800');
        } else if (type === 'success') {
            alertBox.classList.add('bg-green-50', 'border-green-500', 'text-green-800');
        } else {
            alertBox.classList.add('bg-blue-50', 'border-blue-500', 'text-blue-800');
        }

        alertBox.innerHTML = `<p class="font-medium">${message}</p>`;
        alertBox.classList.remove('hidden');

        // Sembunyikan alert jika sukses setelah 6 detik
        if(type === 'success') {
            setTimeout(() => {
                alertBox.classList.add('hidden');
            }, 6000);
        }
    }
});
</script>
@endpush
