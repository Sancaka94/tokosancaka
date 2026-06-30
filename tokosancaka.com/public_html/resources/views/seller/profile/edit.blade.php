@extends('layouts.customer')

@push('styles')
    {{-- CSS Select2 untuk Pencarian API  --}}
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        /* Custom Select2 ala Next.js / Vercel */
        .select2-container .select2-selection--single {
            height: 38px !important;
            border: 1px solid #e5e7eb !important;
            border-radius: 0.375rem !important;
            display: flex;
            align-items: center;
            padding-left: 0.5rem;
            background-color: #ffffff;
            font-size: 0.875rem !important;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
        }
        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 36px !important;
            right: 8px !important;
        }
        .select2-search__field:focus {
            border-color: #ef4444 !important;
            box-shadow: 0 0 0 1px #ef4444 !important;
            outline: none !important;
        }
        .select2-container--default .select2-selection--single .select2-selection__rendered {
            color: #111827 !important;
        }
    </style>
@endpush

@section('content')
<div class="py-10 bg-[#fafafa] min-h-screen font-sans">
    <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">

        {{-- Header Ala Next.js --}}
        <div class="mb-8">
            <h2 class="text-2xl font-semibold text-gray-900 tracking-tight">Pengaturan Toko</h2>
            <p class="text-sm text-gray-500 mt-1">Kelola profil, informasi, dan lokasi toko Anda di sini.</p>
        </div>

        {{-- Alert Area --}}
        <div id="alert-container" class="mb-6 space-y-4">
            @if (session('success'))
                <div class="p-4 bg-white border border-green-200 rounded-lg shadow-sm flex items-start text-sm">
                    <svg class="w-5 h-5 mr-3 text-green-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                    <span class="text-gray-700 font-medium">{{ session('success') }}</span>
                </div>
            @endif

            @if (session('error') || $errors->any())
                <div class="p-4 bg-white border border-red-200 rounded-lg shadow-sm flex items-start text-sm">
                    <svg class="w-5 h-5 mr-3 text-red-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path></svg>
                    <div class="text-gray-700 font-medium">
                        @if (session('error')) {{ session('error') }} @endif
                        @if ($errors->any())
                            <ul class="list-disc list-inside mt-1">
                                @foreach ($errors->all() as $error) <li>{{ $error }}</li> @endforeach
                            </ul>
                        @endif
                    </div>
                </div>
            @endif

            {{-- Notifikasi JS --}}
            <div id="geocode-alert" class="hidden p-4 bg-white border rounded-lg shadow-sm items-start text-sm transition-all duration-300" role="alert"></div>
        </div>

        <div class="bg-white shadow-sm border border-gray-200 sm:rounded-lg">
            <form method="POST" action="{{ route('seller.profile.update') }}" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                {{-- BAGIAN 1: INFORMASI DASAR --}}
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8 p-8 border-b border-gray-200">
                    <div class="md:col-span-1">
                        <h3 class="text-base font-medium text-gray-900">Profil Umum</h3>
                        <p class="text-sm text-gray-500 mt-1">Informasi ini akan ditampilkan kepada publik secara luas agar pembeli lebih mudah mengenali toko Anda.</p>
                    </div>

                    <div class="md:col-span-2 space-y-6">
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Nama Toko</label>
                            <input id="name" class="block w-full border-gray-200 rounded-md shadow-sm focus:border-red-500 focus:ring-red-500 sm:text-sm py-2 px-3 transition-colors" type="text" name="name" value="{{ old('name', $store->name) }}" required placeholder="Contoh: Sancaka Store" />
                        </div>

                        <div>
                            <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Deskripsi Toko</label>
                            <textarea id="description" name="description" class="block w-full border-gray-200 rounded-md shadow-sm focus:border-red-500 focus:ring-red-500 sm:text-sm py-2 px-3 transition-colors" rows="4" required placeholder="Jelaskan produk atau jasa yang Anda tawarkan...">{{ old('description', $store->description) }}</textarea>
                        </div>

                        <div>
                            <label for="logo" class="block text-sm font-medium text-gray-700 mb-1">Logo Toko</label>
                            <div class="flex items-center gap-4 mt-2">
                                @if($store->seller_logo)
                                    <img src="{{ asset('storage/' . $store->seller_logo) }}" alt="Logo" class="w-12 h-12 object-cover rounded-md border border-gray-200 shadow-sm">
                                @else
                                    <div class="w-12 h-12 rounded-md bg-gray-100 border border-gray-200 flex items-center justify-center text-gray-400">
                                        <i class="fa fa-image text-lg"></i>
                                    </div>
                                @endif
                                <div class="flex-1">
                                    <input id="logo" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-gray-50 file:text-gray-700 hover:file:bg-gray-100 border border-gray-200 rounded-md shadow-sm bg-white" type="file" name="logo" accept="image/jpeg,image/png,image/jpg" />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- BAGIAN 2: ALAMAT & WILAYAH --}}
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8 p-8 border-b border-gray-200">
                    <div class="md:col-span-1">
                        <h3 class="text-base font-medium text-gray-900">Alamat & Pengiriman</h3>
                        <p class="text-sm text-gray-500 mt-1">Gunakan pencarian otomatis untuk memastikan wilayah Anda valid untuk perhitungan ongkos kirim (API ).</p>
                    </div>

                    <div class="md:col-span-2 space-y-6">

                        <div class="bg-gray-50/80 p-4 border border-gray-200 rounded-lg">
                            <label class="block text-sm font-medium text-gray-800 mb-2">Cari Wilayah Otomatis</label>
                            <select id="select2_alamat_toko" class="block w-full border-gray-200 rounded-md shadow-sm focus:ring-red-500 focus:border-red-500"></select>
                            <p class="text-xs text-gray-500 mt-2">Ketik nama <b>Kecamatan</b> atau <b>Kelurahan</b>, lalu pilih dari daftar.</p>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label for="province" class="block text-sm font-medium text-gray-700 mb-1">Provinsi</label>
                                <input id="province" class="block w-full border-gray-200 rounded-md shadow-sm bg-gray-50 text-gray-500 cursor-not-allowed sm:text-sm py-2 px-3" type="text" name="province" value="{{ old('province', $store->province) }}" readonly required />
                            </div>
                            <div>
                                <label for="regency" class="block text-sm font-medium text-gray-700 mb-1">Kabupaten/Kota</label>
                                <input id="regency" class="block w-full border-gray-200 rounded-md shadow-sm bg-gray-50 text-gray-500 cursor-not-allowed sm:text-sm py-2 px-3" type="text" name="regency" value="{{ old('regency', $store->regency) }}" readonly required />
                            </div>
                            <div>
                                <label for="district" class="block text-sm font-medium text-gray-700 mb-1">Kecamatan</label>
                                <input id="district" class="block w-full border-gray-200 rounded-md shadow-sm bg-gray-50 text-gray-500 cursor-not-allowed sm:text-sm py-2 px-3" type="text" name="district" value="{{ old('district', $store->district) }}" readonly required />
                            </div>
                            <div>
                                <label for="village" class="block text-sm font-medium text-gray-700 mb-1">Desa/Kelurahan</label>
                                <input id="village" class="block w-full border-gray-200 rounded-md shadow-sm bg-gray-50 text-gray-500 cursor-not-allowed sm:text-sm py-2 px-3" type="text" name="village" value="{{ old('village', $store->village) }}" readonly required />
                            </div>
                            <div class="sm:col-span-2">
                                <label for="zip_code" class="block text-sm font-medium text-gray-700 mb-1">Kode Pos</label>
                                <input id="zip_code" class="block w-1/2 border-gray-200 rounded-md shadow-sm bg-gray-50 text-gray-500 cursor-not-allowed sm:text-sm py-2 px-3" type="text" name="zip_code" value="{{ old('zip_code', $store->zip_code) }}" readonly required />
                            </div>
                        </div>

                        <div>
                            <label for="address_detail" class="block text-sm font-medium text-gray-700 mb-1">Detail Jalan / Bangunan</label>
                            <textarea id="address_detail" name="address_detail" class="block w-full border-gray-200 rounded-md shadow-sm focus:border-red-500 focus:ring-red-500 sm:text-sm py-2 px-3" rows="2" placeholder="Contoh: Jl. Sudirman No. 10, RT 01 / RW 02" required>{{ old('address_detail', $store->address_detail) }}</textarea>
                        </div>
                    </div>
                </div>

                {{-- BAGIAN 3: KOORDINAT PETA --}}
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8 p-8">
                    <div class="md:col-span-1">
                        <h3 class="text-base font-medium text-gray-900">Titik Koordinat (GPS)</h3>
                        <p class="text-sm text-gray-500 mt-1">Titik lokasi akurat digunakan untuk melacak pengiriman atau pickup kurir.</p>

                        {{-- DIV PERINGATAN MERAH --}}
                        <div class="mt-4 p-4 bg-red-50 border border-red-200 rounded-md text-sm shadow-sm">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <i class="fa fa-info-circle text-red-500 mt-0.5"></i>
                                </div>
                                <div class="ml-3 text-red-700 font-medium">
                                    <p>Jika Anda ingin koordinat lebih akurat, lebih baik setting halaman ini menggunakan HP Anda agar lebih presisi dan detail.</p>
                                    <p class="mt-2 text-xs opacity-90"><i class="fa fa-exclamation-triangle mr-1"></i> Jangan lupa nyalakan dan izinkan GPS HP Anda saat diminta.</p>
                                </div>
                            </div>
                        </div>

                        {{-- TOMBOL CARI & GPS --}}
                        <div class="mt-4 flex flex-col gap-2">
                            <button type="button" id="btn-gps-koordinat" class="w-full inline-flex items-center justify-center px-4 py-2.5 bg-red-600 border border-transparent rounded-md shadow-sm text-sm font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-colors">
                                <i class="fa fa-crosshairs mr-2"></i> Ambil Koordinat GPS
                            </button>
                            <button type="button" id="btn-cari-koordinat" class="w-full inline-flex items-center justify-center px-4 py-2 bg-white border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none transition-colors">
                                <i class="fa fa-search-location mr-2 text-gray-500"></i> Cari via Alamat (Nominatim)
                            </button>
                        </div>
                    </div>

                    <div class="md:col-span-2 grid grid-cols-1 sm:grid-cols-2 gap-4 items-start">
                        <div>
                            <label for="latitude" class="block text-sm font-medium text-gray-700 mb-1">Latitude</label>
                            <input type="text" name="latitude" id="latitude" value="{{ old('latitude', $store->latitude ?? '') }}" class="block w-full border-gray-200 rounded-md shadow-sm focus:border-red-500 focus:ring-red-500 sm:text-sm py-2 px-3 bg-white" placeholder="-7.1234567">
                        </div>
                        <div>
                            <label for="longitude" class="block text-sm font-medium text-gray-700 mb-1">Longitude</label>
                            <input type="text" name="longitude" id="longitude" value="{{ old('longitude', $store->longitude ?? '') }}" class="block w-full border-gray-200 rounded-md shadow-sm focus:border-red-500 focus:ring-red-500 sm:text-sm py-2 px-3 bg-white" placeholder="110.1234567">
                        </div>
                        <div class="sm:col-span-2">
                            <p class="text-xs text-gray-400"><i class="fa fa-question-circle mr-1"></i> Jika API atau GPS gagal, Anda bisa membuka Google Maps, tekan dan tahan pada titik lokasi toko Anda, lalu salin angka kordinatnya secara manual ke kolom di atas.</p>
                        </div>
                    </div>
                </div>

                {{-- FOOTER / ACTION BUTTON --}}
                <div class="bg-gray-50 px-8 py-5 flex items-center justify-end rounded-b-lg border-t border-gray-200">
                    <button type="submit" class="inline-flex items-center px-5 py-2.5 bg-gray-900 border border-transparent rounded-md shadow-sm text-sm font-medium text-white hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-900 transition-colors">
                        Simpan Perubahan
                    </button>
                </div>
            </form>
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
    // 1. Inisialisasi Pencarian Alamat  (Select2)
    // ---------------------------------------------------------
    $('#select2_alamat_toko').select2({
        width: '100%',
        placeholder: 'Ketik nama kecamatan atau desa...',
        allowClear: true,
        ajax: {
            url: "{{ url('/checkout/search-address-ajax') }}",
            dataType: 'json',
            delay: 250,
            data: function (params) { return { q: params.term }; },
            processResults: function (data) { return { results: data.results }; },
            cache: true
        },
        minimumInputLength: 3,
    });

    // ---------------------------------------------------------
    // 2. Aksi Saat Wilayah Dipilih dari Dropdown Select2
    // ---------------------------------------------------------
    $('#select2_alamat_toko').on('select2:select', function (e) {
        const data = e.params.data;
        let parts = data.raw_address.split(', ');

        $('#village').val(parts[0] || '');
        $('#district').val(parts[1] || '');
        $('#regency').val(parts[2] || '');
        $('#province').val(parts[3] || '');
        $('#zip_code').val(parts[4] || '');

        $('#address_detail').focus();

        // Trigger pencarian kordinat Nominatim sebagai Fallback/Otomatis
        setTimeout(() => { $('#btn-cari-koordinat').click(); }, 500);
    });

    // Deklarasi Elemen
    const alertBox = document.getElementById('geocode-alert');
    const latInput = document.getElementById('latitude');
    const lonInput = document.getElementById('longitude');

    // ---------------------------------------------------------
    // 3A. Logika Ambil GPS Perangkat (Lebih Akurat)
    // ---------------------------------------------------------
    const gpsButton = document.getElementById('btn-gps-koordinat');
    if (gpsButton) {
        gpsButton.addEventListener('click', function() {
            if (!navigator.geolocation) {
                showAlert('Browser Anda tidak mendukung fitur lokasi GPS.', 'error');
                return;
            }

            // Visual Loading
            let originalText = this.innerHTML;
            this.disabled = true;
            this.innerHTML = `<i class="fa fa-spinner fa-spin mr-2"></i> Mengakses GPS...`;

            navigator.geolocation.getCurrentPosition(
                function(position) {
                    // Sukses mendapatkan lokasi
                    latInput.value = position.coords.latitude.toFixed(7);
                    lonInput.value = position.coords.longitude.toFixed(7);

                    showAlert('Koordinat berhasil diambil dari GPS perangkat Anda!', 'success');

                    // Efek visual sukses
                    latInput.classList.add('ring-1', 'ring-green-500', 'border-green-500');
                    lonInput.classList.add('ring-1', 'ring-green-500', 'border-green-500');
                    setTimeout(() => {
                        latInput.classList.remove('ring-1', 'ring-green-500', 'border-green-500');
                        lonInput.classList.remove('ring-1', 'ring-green-500', 'border-green-500');
                    }, 2000);

                    // Reset tombol
                    gpsButton.disabled = false;
                    gpsButton.innerHTML = originalText;
                },
                function(error) {
                    // Gagal atau ditolak
                    let errorMsg = 'Gagal mengambil GPS.';
                    switch(error.code) {
                        case error.PERMISSION_DENIED:
                            errorMsg = "Izin GPS ditolak. Silakan izinkan akses lokasi di pengaturan browser/HP Anda.";
                            break;
                        case error.POSITION_UNAVAILABLE:
                            errorMsg = "Informasi lokasi GPS tidak tersedia saat ini.";
                            break;
                        case error.TIMEOUT:
                            errorMsg = "Waktu pencarian GPS habis (Timeout).";
                            break;
                    }
                    showAlert(errorMsg, 'error');
                    gpsButton.disabled = false;
                    gpsButton.innerHTML = originalText;
                },
                {
                    enableHighAccuracy: true, // Memaksa mencari lokasi paling akurat (GPS satelit)
                    timeout: 10000,           // Batas tunggu 10 detik
                    maximumAge: 0             // Jangan gunakan cache
                }
            );
        });
    }

    // ---------------------------------------------------------
    // 3B. Logika Geocoding (Pencarian Nominatim API)
    // ---------------------------------------------------------
    const searchButton = document.getElementById('btn-cari-koordinat');
    if (searchButton) {
        searchButton.addEventListener('click', async function() {
            const province = document.getElementById('province').value.trim();
            const regency = document.getElementById('regency').value.trim();
            const district = document.getElementById('district').value.trim();
            const village = document.getElementById('village').value.trim();

            if (!district || !regency) {
                showAlert('Pilih wilayah dari form pencarian alamat terlebih dahulu.', 'error');
                return;
            }

            let addressParts = [village, district, regency, province].filter(part => part !== '');
            let fullAddress = addressParts.join(', ');

            // UI Loading state
            let originalText = this.innerHTML;
            this.disabled = true;
            this.innerHTML = `<i class="fa fa-spinner fa-spin mr-2 text-gray-500"></i> Memproses...`;

            try {
                let response = await fetch(`https://nominatim.openstreetmap.org/search?q=${encodeURIComponent(fullAddress)}&format=json&limit=1&countrycodes=id`);
                let data = await response.json();

                if (data.length === 0 && village !== '') {
                    let fallbackAddress = [district, regency, province].filter(part => part !== '').join(', ');
                    response = await fetch(`https://nominatim.openstreetmap.org/search?q=${encodeURIComponent(fallbackAddress)}&format=json&limit=1&countrycodes=id`);
                    data = await response.json();
                }

                if (data && data.length > 0) {
                    latInput.value = parseFloat(data[0].lat).toFixed(7);
                    lonInput.value = parseFloat(data[0].lon).toFixed(7);
                    showAlert('Koordinat ditemukan berdasarkan alamat (Nominatim).', 'success');

                    // Visual ringkasan singkat (ala Vercel)
                    latInput.classList.add('ring-1', 'ring-green-500', 'border-green-500');
                    lonInput.classList.add('ring-1', 'ring-green-500', 'border-green-500');
                    setTimeout(() => {
                        latInput.classList.remove('ring-1', 'ring-green-500', 'border-green-500');
                        lonInput.classList.remove('ring-1', 'ring-green-500', 'border-green-500');
                    }, 2000);
                } else {
                    showAlert('Gagal mencari titik peta via alamat.', 'error');
                }
            } catch (error) {
                showAlert('Koneksi terputus saat menghubungi server.', 'error');
            } finally {
                this.disabled = false;
                this.innerHTML = originalText;
            }
        });
    }

    // Custom Alert Logic UI ala Next.js (Minimalist Inline Toast)
    function showAlert(message, type = 'info') {
        alertBox.className = 'p-4 bg-white border rounded-lg shadow-sm flex items-center text-sm transition-all duration-300';

        let icon = '';
        if (type === 'error') {
            alertBox.classList.add('border-red-200', 'text-red-700');
            icon = `<svg class="w-5 h-5 mr-3 text-red-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path></svg>`;
        } else if (type === 'success') {
            alertBox.classList.add('border-green-200', 'text-green-700');
            icon = `<svg class="w-5 h-5 mr-3 text-green-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>`;
        } else {
            alertBox.classList.add('border-gray-200', 'text-gray-700');
            icon = `<svg class="w-5 h-5 mr-3 text-gray-400 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path></svg>`;
        }

        alertBox.innerHTML = `${icon} <span class="font-medium">${message}</span>`;
        alertBox.classList.remove('hidden');

        if(type === 'success') {
            setTimeout(() => { alertBox.classList.add('hidden'); }, 5000);
        }
    }
});
</script>
@endpush
