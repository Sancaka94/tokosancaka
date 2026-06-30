@extends('layouts.customer')

@push('styles')
    {{-- CSS Select2 untuk Pencarian API KiriminAja --}}
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        /* Custom Select2 agar menyatu dengan style Tailwind dan terlihat BESAR (ala Bootstrap 5 LG) */
        .select2-container .select2-selection--single {
            height: 52px !important;
            border: 1px solid #d1d5db !important;
            border-radius: 0.75rem !important; /* setara rounded-xl */
            display: flex;
            align-items: center;
            padding-left: 1rem;
            background-color: #ffffff;
            font-size: 1rem !important; /* Ukuran teks besar */
        }
        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 50px !important;
            right: 12px !important;
        }
        .select2-search__field:focus {
            border-color: #ef4444 !important;
            box-shadow: 0 0 0 1px #ef4444 !important;
            outline: none !important;
        }
        .select2-container--default .select2-selection--single .select2-selection__rendered {
            color: #374151 !important;
        }
    </style>
@endpush

@section('content')
<div class="py-12 bg-gray-50 min-h-screen">
    <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">

        <div class="flex justify-between items-center mb-8">
            <h2 class="text-3xl font-extrabold text-gray-900 tracking-tight">Profil Toko Saya</h2>
        </div>

        <div class="bg-white overflow-hidden shadow-lg sm:rounded-2xl border border-gray-100">
            <div class="p-8 md:p-10">

                {{-- Alert Sukses dari Controller --}}
                @if (session('success'))
                    <div class="mb-8 p-5 bg-green-50 border-l-4 border-green-500 text-green-800 rounded-r-xl flex items-center shadow-sm text-base">
                        <svg class="w-6 h-6 mr-3 text-green-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                        <span class="font-medium">{{ session('success') }}</span>
                    </div>
                @endif

                {{-- Alert Error dari Controller --}}
                @if (session('error'))
                    <div class="mb-8 p-5 bg-red-50 border-l-4 border-red-500 text-red-800 rounded-r-xl shadow-sm text-base font-medium">
                        {{ session('error') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div class="mb-8 p-5 bg-red-50 border-l-4 border-red-500 text-red-800 rounded-r-xl shadow-sm">
                        <ul class="list-disc list-inside space-y-1 text-base font-medium">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                {{-- Notifikasi Sukses/Error Geocoding Javascript (Hidden by default) --}}
                <div id="geocode-alert" class="hidden mb-8 p-5 border-l-4 rounded-r-xl text-base transition-all duration-300 shadow-sm" role="alert"></div>

                <form method="POST" action="{{ route('seller.profile.update') }}" enctype="multipart/form-data" class="space-y-8">
                    @csrf
                    @method('PUT')

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">

                        <div class="md:col-span-2">
                            <label for="name" class="block font-bold text-gray-800 mb-2 text-base">Nama Toko</label>
                            <input id="name" class="block w-full border-gray-300 rounded-xl shadow-sm focus:border-red-500 focus:ring-red-500 transition px-4 py-3 text-base text-gray-800" type="text" name="name" value="{{ old('name', $store->name) }}" required placeholder="Masukkan nama toko Anda" />
                        </div>

                        <div class="md:col-span-2">
                            <label for="description" class="block font-bold text-gray-800 mb-2 text-base">Deskripsi Toko</label>
                            <textarea id="description" name="description" class="block w-full border-gray-300 rounded-xl shadow-sm focus:border-red-500 focus:ring-red-500 transition px-4 py-3 text-base text-gray-800 leading-relaxed" rows="4" required placeholder="Ceritakan sedikit tentang toko Anda...">{{ old('description', $store->description) }}</textarea>
                        </div>

                        {{-- Section API KiriminAja --}}
                        <div class="md:col-span-2 p-6 md:p-8 bg-blue-50/50 border border-blue-100 rounded-2xl shadow-sm">
                            <label class="block font-extrabold text-lg text-blue-900 mb-3">Pencarian Wilayah Otomatis</label>
                            <select id="select2_alamat_toko" class="block w-full border-gray-300 rounded-xl shadow-sm focus:ring-blue-500 focus:border-blue-500"></select>
                            <div class="flex items-start mt-3 text-blue-700 text-sm md:text-base">
                                <svg class="w-5 h-5 mr-2 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path></svg>
                                <p>Ketik nama <b>Kecamatan</b> atau <b>Kelurahan</b> untuk mengisi kolom alamat di bawah secara otomatis.</p>
                            </div>
                        </div>

                        {{-- Input Wilayah (Readonly) --}}
                        <div>
                            <label for="province" class="block font-bold text-gray-800 mb-2 text-base">Provinsi</label>
                            <input id="province" class="block w-full border-gray-300 rounded-xl shadow-sm bg-gray-100 text-gray-600 cursor-not-allowed focus:outline-none px-4 py-3 text-base" type="text" name="province" value="{{ old('province', $store->province) }}" readonly required />
                        </div>

                        <div>
                            <label for="regency" class="block font-bold text-gray-800 mb-2 text-base">Kabupaten/Kota</label>
                            <input id="regency" class="block w-full border-gray-300 rounded-xl shadow-sm bg-gray-100 text-gray-600 cursor-not-allowed focus:outline-none px-4 py-3 text-base" type="text" name="regency" value="{{ old('regency', $store->regency) }}" readonly required />
                        </div>

                        <div>
                            <label for="district" class="block font-bold text-gray-800 mb-2 text-base">Kecamatan</label>
                            <input id="district" class="block w-full border-gray-300 rounded-xl shadow-sm bg-gray-100 text-gray-600 cursor-not-allowed focus:outline-none px-4 py-3 text-base" type="text" name="district" value="{{ old('district', $store->district) }}" readonly required />
                        </div>

                        <div>
                            <label for="village" class="block font-bold text-gray-800 mb-2 text-base">Desa/Kelurahan</label>
                            <input id="village" class="block w-full border-gray-300 rounded-xl shadow-sm bg-gray-100 text-gray-600 cursor-not-allowed focus:outline-none px-4 py-3 text-base" type="text" name="village" value="{{ old('village', $store->village) }}" readonly required />
                        </div>

                        <div>
                            <label for="zip_code" class="block font-bold text-gray-800 mb-2 text-base">Kode Pos</label>
                            <input id="zip_code" class="block w-full border-gray-300 rounded-xl shadow-sm bg-gray-100 text-gray-600 cursor-not-allowed focus:outline-none px-4 py-3 text-base" type="text" name="zip_code" value="{{ old('zip_code', $store->zip_code) }}" readonly required />
                        </div>

                        <div class="md:col-span-2">
                            <label for="address_detail" class="block font-bold text-gray-800 mb-2 text-base">Detail Alamat (Nama Jalan, RT/RW, Blok)</label>
                            <textarea id="address_detail" name="address_detail" class="block w-full border-gray-300 rounded-xl shadow-sm focus:border-red-500 focus:ring-red-500 transition px-4 py-3 text-base text-gray-800" rows="3" placeholder="Contoh: Jl. Sudirman No. 10, RT 01 / RW 02" required>{{ old('address_detail', $store->address_detail) }}</textarea>
                        </div>

                        {{-- Section Koordinat GPS --}}
                        <div class="md:col-span-2 p-6 md:p-8 bg-red-50/40 rounded-2xl border border-red-100 shadow-sm">
                            <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center mb-6 gap-4">
                                <div>
                                    <h4 class="text-lg font-extrabold text-gray-900">Koordinat Peta (GPS)</h4>
                                    <p class="text-sm md:text-base text-gray-600 mt-1">Sistem otomatis mengisi koordinat saat Anda memilih wilayah di atas.</p>
                                </div>
                                <button type="button" id="btn-cari-koordinat" class="inline-flex items-center justify-center px-5 py-3 bg-red-100 hover:bg-red-200 text-red-700 font-bold text-sm md:text-base rounded-xl transition-colors shadow-sm whitespace-nowrap">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5 mr-2">
                                      <path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 1 0 0 11 5.5 5.5 0 0 0 0-11ZM2 9a7 7 0 1 1 12.452 4.391l3.328 3.329a.75.75 0 1 1-1.06 1.06l-3.329-3.328A7 7 0 0 1 2 9Z" clip-rule="evenodd" />
                                    </svg>
                                    Cari Ulang Koordinat
                                </button>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                                <div>
                                    <label for="latitude" class="block font-bold text-gray-800 mb-2 text-base">Latitude</label>
                                    <input type="text" name="latitude" id="latitude" value="{{ old('latitude', $store->latitude ?? '') }}" class="block w-full border-gray-300 rounded-xl shadow-sm focus:ring-red-500 focus:border-red-500 bg-white px-4 py-3 text-base" placeholder="-7.1234567">
                                    @error('latitude') <p class="text-sm font-medium text-red-600 mt-2">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label for="longitude" class="block font-bold text-gray-800 mb-2 text-base">Longitude</label>
                                    <input type="text" name="longitude" id="longitude" value="{{ old('longitude', $store->longitude ?? '') }}" class="block w-full border-gray-300 rounded-xl shadow-sm focus:ring-red-500 focus:border-red-500 bg-white px-4 py-3 text-base" placeholder="110.1234567">
                                    @error('longitude') <p class="text-sm font-medium text-red-600 mt-2">{{ $message }}</p> @enderror
                                </div>
                            </div>
                        </div>

                        <div class="md:col-span-2">
                            <label for="logo" class="block font-bold text-gray-800 mb-2 text-base">Logo Toko (Opsional)</label>
                            <input id="logo" class="block w-full border-gray-300 rounded-xl shadow-sm px-4 py-3 bg-gray-50 focus:border-red-500 focus:ring-red-500 transition text-base" type="file" name="logo" accept="image/jpeg,image/png,image/jpg" />
                            @if($store->seller_logo)
                            <div class="mt-6 flex items-center gap-5 p-4 border border-gray-200 rounded-xl bg-gray-50">
                                <img src="{{ asset('storage/' . $store->seller_logo) }}" alt="Logo saat ini" class="w-20 h-20 object-cover rounded-xl shadow-sm border border-gray-300">
                                <div class="text-gray-600 text-sm md:text-base">
                                    <p class="font-bold text-gray-800">Logo Saat Ini</p>
                                    <p>Unggah file baru jika Anda ingin mengganti logo ini.</p>
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>

                    <div class="flex items-center justify-end mt-10 pt-8 border-t border-gray-200">
                        <button type="submit" class="inline-flex items-center px-8 py-4 bg-red-600 border border-transparent rounded-xl font-extrabold text-base text-white tracking-wide hover:bg-red-700 focus:outline-none focus:ring-4 focus:ring-red-500/50 transition-all shadow-lg hover:shadow-xl hover:-translate-y-0.5">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                            SIMPAN PERUBAHAN
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
                showAlert('Harap pilih wilayah dari pencarian di atas terlebih dahulu.', 'error');
                return;
            }

            // Membangun array pencarian (diurutkan dari spesifik ke umum)
            let addressParts = [village, district, regency, province].filter(part => part !== '');
            let fullAddress = addressParts.join(', ');

            // UI Loading state
            this.disabled = true;
            this.innerHTML = `<svg class="animate-spin -ml-1 mr-2 h-5 w-5 text-red-700" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Memproses...`;
            showAlert('Mencari titik koordinat untuk: ' + fullAddress, 'info');

            try {
                // Tembak API Nominatim OpenStreetMap
                let response = await fetch(`https://nominatim.openstreetmap.org/search?q=${encodeURIComponent(fullAddress)}&format=json&limit=1&countrycodes=id`);
                let data = await response.json();

                // Fallback: Jika Kelurahan tidak dikenali, cari menggunakan Kecamatan + Kabupaten
                if (data.length === 0 && village !== '') {
                    showAlert('Memperluas jangkauan pencarian wilayah...', 'info');
                    let fallbackAddress = [district, regency, province].filter(part => part !== '').join(', ');
                    response = await fetch(`https://nominatim.openstreetmap.org/search?q=${encodeURIComponent(fallbackAddress)}&format=json&limit=1&countrycodes=id`);
                    data = await response.json();
                }

                if (data && data.length > 0) {
                    latInput.value = parseFloat(data[0].lat).toFixed(7);
                    lonInput.value = parseFloat(data[0].lon).toFixed(7);
                    showAlert('Berhasil! Koordinat ditemukan dan diisi otomatis.', 'success');

                    // Efek visual kedip sukses pada input form
                    latInput.classList.add('ring-4', 'ring-green-400', 'border-green-400');
                    lonInput.classList.add('ring-4', 'ring-green-400', 'border-green-400');
                    setTimeout(() => {
                        latInput.classList.remove('ring-4', 'ring-green-400', 'border-green-400');
                        lonInput.classList.remove('ring-4', 'ring-green-400', 'border-green-400');
                    }, 2500);

                } else {
                    showAlert('Koordinat presisi tidak ditemukan. Anda dapat mengisi kolom Latitude & Longitude secara manual.', 'error');
                }
            } catch (error) {
                console.error('Error fetching geocode:', error);
                showAlert('Koneksi terputus. Gagal mengambil koordinat.', 'error');
            } finally {
                // Kembalikan status tombol pencari
                this.disabled = false;
                this.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5 mr-2"><path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 1 0 0 11 5.5 5.5 0 0 0 0-11ZM2 9a7 7 0 1 1 12.452 4.391l3.328 3.329a.75.75 0 1 1-1.06 1.06l-3.329-3.328A7 7 0 0 1 2 9Z" clip-rule="evenodd" /></svg> Cari Ulang Koordinat`;
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

        alertBox.innerHTML = `<div class="flex items-center"><svg class="w-5 h-5 mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path></svg><p class="font-semibold text-base">${message}</p></div>`;
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
