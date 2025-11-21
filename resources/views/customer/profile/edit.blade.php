{{--
    File: resources/views/customer/profile/edit.blade.php
    Deskripsi: Halaman untuk mengedit detail profil pelanggan, dilengkapi autofill alamat KiriminAja.
--}}
@extends('layouts.customer')

@section('styles')
{{-- Tambahan CSS untuk tampilan hasil pencarian --}}
<style>
    .search-results-list {
        max-height: 250px;
        overflow-y: auto;
        border-top: none;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }
    .search-result-item {
        padding: 0.75rem 1rem;
        cursor: pointer;
        transition: background-color 0.15s;
    }
    .search-result-item:hover {
        background-color: #f7f7f7;
    }
</style>
@endsection

@section('content')
<div class="p-6 lg:p-8 bg-slate-50 min-h-screen">
    <div class="max-w-7xl mx-auto">

        <div class="mb-8">
            <h1 class="text-3xl font-bold text-slate-800 tracking-tight">Edit Profil</h1>
            <p class="mt-2 text-sm text-slate-500">Perbarui informasi akun, alamat, dan toko Anda di sini.</p>
        </div>

        {{-- Menampilkan pesan error validasi --}}
        @if ($errors->any())
            <div class="mb-6 bg-red-50 border-l-4 border-red-400 text-red-800 p-4 rounded-r-lg" role="alert">
                <p class="font-bold">Oops! Terjadi kesalahan.</p>
                <ul class="mt-2 list-disc list-inside text-sm">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        
        {{-- Notifikasi Sukses/Error Geocoding atau Autofill --}}
        <div id="geocode-alert" class="hidden mb-6 p-4 rounded-r-lg border-l-4" role="alert"></div>

        <form method="POST" action="{{ route('customer.profile.update') }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div class="bg-white shadow-lg rounded-xl overflow-hidden border border-slate-200">
                <div class="p-6 md:p-8">
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                        
                        {{-- Kolom Kiri (Informasi Akun & Toko) --}}
                        <div class="lg:col-span-1 space-y-6">
                            <div class="flex flex-col items-center text-center">
                                <img id="logo-preview" src="{{ $user->store_logo_path ? asset('public/storage/' . $user->store_logo_path) : 'https://placehold.co/128x128/e2e8f0/64748b?text=Logo' }}" 
                                    alt="Logo Toko" 
                                    class="h-24 w-24 rounded-full object-cover bg-slate-200 border-4 border-white shadow-md">
                                <label for="store_logo" class="mt-4 cursor-pointer text-sm font-semibold text-red-600 hover:text-red-800 transition">
                                    Ubah Logo
                                </label>
                                <input type="file" name="store_logo" id="store_logo" class="sr-only" accept="image/*">
                                @error('store_logo') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="nama_lengkap" class="block text-sm font-medium text-slate-700">Nama Lengkap</label>
                                <input type="text" name="nama_lengkap" id="nama_lengkap" value="{{ old('nama_lengkap', $user->nama_lengkap) }}" class="mt-1 block w-full border-slate-300 rounded-lg shadow-sm focus:ring-red-500 focus:border-red-500">
                                @error('nama_lengkap') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="email_display" class="block text-sm font-medium text-slate-700">Email</label>
                                <input type="email" id="email_display" value="{{ $user->email }}" class="mt-1 block w-full border-slate-300 rounded-lg shadow-sm bg-slate-100 cursor-not-allowed" readonly>
                                <input type="hidden" name="email" value="{{ $user->email }}">
                            </div>
                            <div>
                                <label for="no_wa" class="block text-sm font-medium text-slate-700">No. WhatsApp</label>
                                <input type="text" name="no_wa" id="no_wa" value="{{ old('no_wa', $user->no_wa) }}" class="mt-1 block w-full border-slate-300 rounded-lg shadow-sm focus:ring-red-500 focus:border-red-500">
                                @error('no_wa') <p class="text-xs text-red-600 mt-1">{{ $message }}</p @enderror
                            </div>
                            <div>
                                <label for="store_name" class="block text-sm font-medium text-slate-700">Nama Toko</label>
                                <input type="text" name="store_name" id="store_name" value="{{ old('store_name', $user->store_name) }}" class="mt-1 block w-full border-slate-300 rounded-lg shadow-sm focus:ring-red-500 focus:border-red-500">
                                @error('store_name') <p class="text-xs text-red-600 mt-1">{{ $message }}</p @enderror
                            </div>
                        </div>

                        {{-- Kolom Kanan (Informasi Alamat & Bank) --}}
                        <div class="lg:col-span-2 grid grid-cols-1 sm:grid-cols-2 gap-8">
                            
                            {{-- BLOK ALAMAT UTAMA --}}
                            <div>
                                <h3 class="text-lg font-semibold text-slate-800 border-b border-slate-200 pb-3 mb-4">Alamat Utama</h3>
                                
                                <div class="space-y-4">
                                    
                                    {{-- 🔑 FIELD PENCARIAN UTAMA --}}
                                    <div>
                                        <label for="address_search_input" class="block text-sm font-medium text-slate-700">Cari Alamat (Kelurahan, Kecamatan)</label>
                                        <div class="relative">
                                            <input type="text" id="address_search_input" placeholder="Ketik Kelurahan/Kecamatan/Kota..." 
                                                   class="mt-1 block w-full border-red-500 rounded-lg shadow-sm focus:ring-red-500 focus:border-red-500 border-2 p-2">
                                            {{-- Container untuk menampilkan hasil pencarian AJAX --}}
                                            <div id="address_search_results" class="absolute z-10 w-full mt-1 bg-white border border-slate-300 rounded-lg shadow-xl hidden search-results-list">
                                                {{-- Hasil pencarian akan diinjeksi di sini oleh JS --}}
                                            </div>
                                        </div>
                                        <p class="text-xs text-slate-500 mt-1">Gunakan format: Kelurahan, Kecamatan, Kota</p>
                                    </div>
                                    
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                        {{-- 1. PROVINSI (Auto-filled) --}}
                                        <div>
                                            <label for="province" class="block text-sm font-medium text-slate-700">Provinsi</label>
                                            <input type="text" name="province" id="province" value="{{ old('province', $user->province) }}" 
                                                   class="mt-1 block w-full border-slate-300 rounded-lg shadow-sm bg-slate-100 cursor-not-allowed" readonly>
                                        </div>
                                        {{-- 2. KABUPATEN/KOTA (Auto-filled) --}}
                                        <div>
                                            <label for="regency" class="block text-sm font-medium text-slate-700">Kabupaten/Kota</label>
                                            <input type="text" name="regency" id="regency" value="{{ old('regency', $user->regency) }}" 
                                                   class="mt-1 block w-full border-slate-300 rounded-lg shadow-sm bg-slate-100 cursor-not-allowed" readonly>
                                        </div>
                                        {{-- 3. KECAMATAN (Auto-filled) --}}
                                        <div>
                                            <label for="district" class="block text-sm font-medium text-slate-700">Kecamatan</label>
                                            <input type="text" name="district" id="district" value="{{ old('district', $user->district) }}" 
                                                   class="mt-1 block w-full border-slate-300 rounded-lg shadow-sm bg-slate-100 cursor-not-allowed" readonly>
                                        </div>
                                        {{-- 4. DESA/KELURAHAN (Auto-filled) --}}
                                        <div>
                                            <label for="village" class="block text-sm font-medium text-slate-700">Desa/Kelurahan</label>
                                            <input type="text" name="village" id="village" value="{{ old('village', $user->village) }}" 
                                                   class="mt-1 block w-full border-slate-300 rounded-lg shadow-sm bg-slate-100 cursor-not-allowed" readonly>
                                        </div>
                                        {{-- 5. KODE POS (Auto-filled) --}}
                                        <div>
                                            <label for="postal_code" class="block text-sm font-medium text-slate-700">Kode Pos</label>
                                            <input type="text" name="postal_code" id="postal_code" value="{{ old('postal_code', $user->postal_code) }}" 
                                                   class="mt-1 block w-full border-slate-300 rounded-lg shadow-sm bg-slate-100 cursor-not-allowed" readonly>
                                        </div>
                                    </div>
                                    
                                    <div class="sm:col-span-2">
                                        <label for="address_detail" class="block text-sm font-medium text-slate-700">Alamat Detail (No. Rumah, RT/RW, Patokan)</label>
                                        <textarea name="address_detail" id="address_detail" rows="3" 
                                                  class="mt-1 block w-full border-slate-300 rounded-lg shadow-sm focus:ring-red-500 focus:border-red-500">{{ old('address_detail', $user->address_detail) }}</textarea>
                                        @error('address_detail') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                                    </div>

                                    {{-- START: Tambahan Longitude & Latitude dengan Tombol Pencarian --}}
                                    <div class="sm:col-span-2 p-4 bg-slate-50 rounded-lg border border-slate-200">
                                        <div class="flex justify-between items-center mb-3">
                                            <h4 class="text-sm font-medium text-slate-700">Koordinat Peta</h4>
                                            <button type="button" id="btn-cari-koordinat" class="inline-flex items-center px-3 py-1.5 bg-red-600 hover:bg-red-700 text-white font-semibold text-xs rounded-lg transition">
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4 mr-1.5">
                                                    <path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 1 0 0 11 5.5 5.5 0 0 0 0-11ZM2 9a7 7 0 1 1 12.452 4.391l3.328 3.329a.75.75 0 1 1-1.06 1.06l-3.329-3.328A7 7 0 0 1 2 9Z" clip-rule="evenodd" />
                                                </svg>
                                                Cari Koordinat
                                            </button>
                                        </div>
                                        <p class="text-xs text-slate-500 mb-3 -mt-2">Klik 'Cari' untuk mengisi otomatis Lat/Long berdasarkan Kelurahan & Kecamatan.</p>
                                        
                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                            <div>
                                                <label for="latitude" class="block text-sm font-medium text-slate-700">Latitude</label>
                                                <input type="text" name="latitude" id="latitude" value="{{ old('latitude', $user->latitude ?? '') }}" class="mt-1 block w-full border-slate-300 rounded-lg shadow-sm focus:ring-red-500 focus:border-red-500 bg-slate-100" placeholder="-7.12345" readonly>
                                                @error('latitude') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                                            </div>
                                            <div>
                                                <label for="longitude" class="block text-sm font-medium text-slate-700">Longitude</label>
                                                <input type="text" name="longitude" id="longitude" value="{{ old('longitude', $user->longitude ?? '') }}" class="mt-1 block w-full border-slate-300 rounded-lg shadow-sm focus:ring-red-500 focus:border-red-500 bg-slate-100" placeholder="110.12345" readonly>
                                                @error('longitude') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                                            </div>
                                        </div>
                                    </div>
                                    {{-- END: Tambahan Longitude & Latitude --}}

                                </div>
                            </div>
                            
                            {{-- BLOK INFORMASI BANK --}}
                            <div>
                                <h3 class="text-lg font-semibold text-slate-800 border-b border-slate-200 pb-3 mb-4">Informasi Bank</h3>
                                <div class="space-y-4">
                                    <div>
                                        <label for="bank_name" class="block text-sm font-medium text-slate-700">Nama Bank</label>
                                        <input type="text" name="bank_name" id="bank_name" value="{{ old('bank_name', $user->bank_name) }}" class="mt-1 block w-full border-slate-300 rounded-lg shadow-sm focus:ring-red-500 focus:border-red-500">
                                    </div>
                                    <div>
                                        <label for="bank_account_name" class="block text-sm font-medium text-slate-700">Nama Pemilik Rekening</label>
                                        <input type="text" name="bank_account_name" id="bank_account_name" value="{{ old('bank_account_name', $user->bank_account_name) }}" class="mt-1 block w-full border-slate-300 rounded-lg shadow-sm focus:ring-red-500 focus:border-red-500">
                                    </div>
                                    <div>
                                        <label for="bank_account_number" class="block text-sm font-medium text-slate-700">Nomor Rekening</label>
                                        <input type="text" name="bank_account_number" id="bank_account_number" value="{{ old('bank_account_number', $user->bank_account_number) }}" class="mt-1 block w-full border-slate-300 rounded-lg shadow-sm focus:ring-red-500 focus:border-red-500">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-8 pt-6 border-t border-slate-200 flex justify-end items-center gap-4 p-6 md:p-8">
                    <a href="{{ url('/customer/profile') }}" class="text-sm font-semibold text-slate-600 hover:text-slate-800">Batal</a>
                    <button type="submit" class="inline-flex items-center px-5 py-2.5 bg-red-600 hover:bg-red-700 text-white font-semibold text-sm rounded-lg shadow-md transition duration-150 ease-in-out">
                        Simpan Perubahan
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Logo preview logic
    const logoInput = document.getElementById('store_logo');
    const logoPreview = document.getElementById('logo-preview');
    
    if(logoInput && logoPreview) {
        logoInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(event) {
                    logoPreview.src = event.target.result;
                }
                reader.readAsDataURL(file);
            }
        });
    }

    // --- INPUT FIELDS ---
    const searchInput = document.getElementById('address_search_input');
    const searchResults = document.getElementById('address_search_results');
    const provinceInput = document.getElementById('province');
    const regencyInput = document.getElementById('regency');
    const districtInput = document.getElementById('district');
    const villageInput = document.getElementById('village');
    const postalInput = document.getElementById('postal_code');
    const latInput = document.getElementById('latitude');
    const lonInput = document.getElementById('longitude');
    const alertBox = document.getElementById('geocode-alert');
    const searchCoordButton = document.getElementById('btn-cari-koordinat');
    const DETAIL_ADDRESS_INPUT = document.getElementById('address_detail');


    let debounceTimer;
    const DEBOUNCE_DELAY = 500;
    // 🚨 PASTIKAN ROUTE INI SUDAH DIDEFINISIKAN DI FILE ROUTING LARAVEL ANDA
    const SEARCH_ROUTE = '{{ route("customer.kiriminaja.address_search") }}'; 
    
    // ====================================================================
    // 1. LOGIKA PENCARIAN ALAMAT (KiriminAja Autofill)
    // ====================================================================

    searchInput.addEventListener('input', function() {
        const query = this.value.trim();

        clearTimeout(debounceTimer);

        if (query.length < 3) {
            searchResults.classList.add('hidden');
            return;
        }

        debounceTimer = setTimeout(() => fetchAddresses(query), DEBOUNCE_DELAY);
    });

    // Melakukan panggilan AJAX ke server Laravel (yang meneruskan ke KiriminAja)
    async function fetchAddresses(query) {
        searchResults.innerHTML = '<div class="p-2 text-sm text-slate-500">Mencari...</div>';
        searchResults.classList.remove('hidden');

        try {
            // Menggunakan route customer.kiriminaja.address_search
            const url = SEARCH_ROUTE + '?q=' + encodeURIComponent(query);
            const response = await fetch(url);
            
            if (!response.ok) {
                throw new Error('Gagal mengambil data dari server. (Status: ' + response.status + ')');
            }

            const data = await response.json(); 
            displayResults(data);

        } catch (error) {
            console.error('AJAX Error:', error);
            searchResults.innerHTML = `<div class="p-2 text-sm text-red-500">${error.message || 'Koneksi gagal'}</div>`;
        }
    }

    // Menampilkan hasil dan mengizinkan autofill
    function displayResults(results) {
        searchResults.innerHTML = '';

        if (!results || results.length === 0) {
            searchResults.innerHTML = '<div class="p-2 text-sm text-slate-500">Tidak ada hasil ditemukan.</div>';
            return;
        }

        results.forEach(item => {
            const div = document.createElement('div');
            div.className = 'p-2 cursor-pointer hover:bg-slate-100 text-sm search-result-item';
            
            // ASUMSI KEY DARI CONTROLLER:
            // Item harus memiliki keys: province, regency, district, village, postal_code, full_address_display
            
            // Teks yang ditampilkan kepada pengguna
            div.textContent = item.full_address_display || `${item.village}, ${item.district}, ${item.regency}`;
            
            // Simpan data lengkap di elemen HTML (Data Atribut)
            div.dataset.province = item.province;
            div.dataset.regency = item.regency;
            div.dataset.district = item.district;
            div.dataset.village = item.village;
            div.dataset.postalCode = item.postal_code;

            // Event klik untuk autofill
            div.addEventListener('click', function() {
                // Isi field yang read-only
                provinceInput.value = this.dataset.province;
                regencyInput.value = this.dataset.regency;
                districtInput.value = this.dataset.district;
                villageInput.value = this.dataset.village;
                postalInput.value = this.dataset.postalCode;
                
                // Opsional: Kosongkan address_detail agar pengguna mengisi sisanya
                DETAIL_ADDRESS_INPUT.value = '';

                searchResults.classList.add('hidden');
                searchInput.value = this.textContent; 
            });

            searchResults.appendChild(div);
        });
    }

    // Sembunyikan hasil jika klik di luar
    document.addEventListener('click', function(e) {
        if (!searchInput.contains(e.target) && !searchResults.contains(e.target)) {
            searchResults.classList.add('hidden');
        }
    });

    // ====================================================================
    // 2. LOGIKA KOORDINAT (Geocoding)
    // ====================================================================

    if (searchCoordButton) {
        searchCoordButton.addEventListener('click', async function() {
            
            const province = provinceInput.value;
            const regency = regencyInput.value;
            const district = districtInput.value;
            const village = villageInput.value;

            if (!village || !district) {
                showAlert('Harap isi Desa/Kelurahan dan Kecamatan terlebih dahulu!', 'error');
                return;
            }

            let addressParts = [village, district, regency, province];
            let validAddressParts = addressParts.filter(part => part && part.trim() !== '');
            let fullAddress = validAddressParts.join(', ');

            this.disabled = true;
            this.innerHTML = `<svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-red-700" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Mencari...`;
            showAlert('Mencari koordinat untuk: ' + fullAddress, 'info');

            try {
                // Menggunakan Nominatim (OpenStreetMap) API - Alternatif geocoding
                const response = await fetch(`https://nominatim.openstreetmap.org/search?q=${encodeURIComponent(fullAddress)}&format=json&limit=1&countrycodes=id`);
                
                if (!response.ok) {
                    throw new Error('Respon jaringan tidak baik.');
                }

                const data = await response.json();

                if (data && data.length > 0) {
                    const lat = data[0].lat;
                    const lon = data[0].lon;

                    latInput.value = parseFloat(lat).toFixed(7);
                    lonInput.value = parseFloat(lon).toFixed(7);

                    showAlert('Koordinat berhasil ditemukan!', 'success');
                } else {
                    showAlert('Koordinat tidak ditemukan. Periksa kembali alamat Anda.', 'error');
                    latInput.value = '';
                    lonInput.value = '';
                }

            } catch (error) {
                console.error('Error fetching geocode:', error);
                showAlert('Gagal mengambil koordinat. Cek koneksi.', 'error');
            } finally {
                this.disabled = false;
                this.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4 mr-1.5"><path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 1 0 0 11 5.5 5.5 0 0 0 0-11ZM2 9a7 7 0 1 1 12.452 4.391l3.328 3.329a.75.75 0 1 1-1.06 1.06l-3.329-3.328A7 7 0 0 1 2 9Z" clip-rule="evenodd" /></svg> Cari Koordinat`;
            }
        });
    }

    // Fungsi Pembantu untuk menampilkan alert
    function showAlert(message, type = 'info') {
        alertBox.classList.remove('hidden', 'bg-red-50', 'border-red-400', 'text-red-800', 'bg-green-50', 'border-green-400', 'text-green-800', 'bg-blue-50', 'border-blue-400', 'text-blue-800');
        
        // Atur kelas Bootstrap-like Tailwind
        if (type === 'error') {
            alertBox.classList.add('bg-red-100', 'border-red-400', 'text-red-800');
        } else if (type === 'success') {
            alertBox.classList.add('bg-green-100', 'border-green-400', 'text-green-800');
        } else { // info
            alertBox.classList.add('bg-blue-100', 'border-blue-400', 'text-blue-800');
        }
        
        alertBox.innerHTML = `<p class="font-medium">${message}</p>`;
        alertBox.classList.remove('hidden');

        if(type === 'success' || type === 'info') {
            setTimeout(() => {
                alertBox.classList.add('hidden');
            }, 5000);
        }
    }
});
</script>
@endpush