@extends('layouts.customer')

@section('content')
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <h2 class="text-2xl font-semibold text-gray-800 mb-6">Profil Toko Saya</h2>

        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 bg-white border-b border-gray-200">

                @if (session('success'))
                    <div class="mb-4 p-4 bg-green-100 text-green-700 rounded-lg">
                        {{ session('success') }}
                    </div>
                @endif
                
                @if ($errors->any())
                    <div class="mb-4 p-4 bg-red-100 text-red-700 rounded-lg">
                        <ul class="list-disc list-inside">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                {{-- Notifikasi Sukses/Error Geocoding --}}
                <div id="geocode-alert" class="hidden mb-6 p-4 rounded-r-lg" role="alert"></div>

                <form method="POST" action="{{ route('seller.profile.update') }}" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    <div class="mb-4">
                        <label for="name" class="block font-medium text-sm text-gray-700">Nama Toko</label>
                        <input id="name" class="block mt-1 w-full border-gray-300 rounded-md shadow-sm focus:border-red-500 focus:ring-red-500" type="text" name="name" value="{{ old('name', $store->name) }}" required />
                    </div>

                    <div class="mb-4">
                        <label for="description" class="block font-medium text-sm text-gray-700">Deskripsi</label>
                        <textarea id="description" name="description" class="block mt-1 w-full border-gray-300 rounded-md shadow-sm focus:border-red-500 focus:ring-red-500" rows="4" required>{{ old('description', $store->description) }}</textarea>
                    </div>

                    <div class="mb-4">
                        <label for="province" class="block font-medium text-sm text-gray-700">Provinsi</label>
                        <input id="province" class="block mt-1 w-full border-gray-300 rounded-md shadow-sm focus:border-red-500 focus:ring-red-500" type="text" name="province" value="{{ old('province', $store->province) }}" />
                    </div>

                    <div class="mb-4">
                        <label for="regency" class="block font-medium text-sm text-gray-700">Kabupaten/Kota</label>
                        <input id="regency" class="block mt-1 w-full border-gray-300 rounded-md shadow-sm focus:border-red-500 focus:ring-red-500" type="text" name="regency" value="{{ old('regency', $store->regency) }}" />
                    </div>

                    <div class="mb-4">
                        <label for="district" class="block font-medium text-sm text-gray-700">Kecamatan</label>
                        <input id="district" class="block mt-1 w-full border-gray-300 rounded-md shadow-sm focus:border-red-500 focus:ring-red-500" type="text" name="district" value="{{ old('district', $store->district) }}" />
                    </div>

                    <div class="mb-4">
                        <label for="village" class="block font-medium text-sm text-gray-700">Desa/Kelurahan</label>
                        <input id="village" class="block mt-1 w-full border-gray-300 rounded-md shadow-sm focus:border-red-500 focus:ring-red-500" type="text" name="village" value="{{ old('village', $store->village) }}" />
                    </div>
                    
                    <div class="mb-4">
                        <label for="address_detail" class="block font-medium text-sm text-gray-700">Detail Alamat</label>
                        <textarea id="address_detail" name="address_detail" class="block mt-1 w-full border-gray-300 rounded-md shadow-sm focus:border-red-500 focus:ring-red-500" rows="3">{{ old('address_detail', $store->address_detail) }}</textarea>
                    </div>

                    <div class="mb-4">
                        <label for="zip_code" class="block font-medium text-sm text-gray-700">Kode Pos</label>
                        <input id="zip_code" class="block mt-1 w-full border-gray-300 rounded-md shadow-sm focus:border-red-500 focus:ring-red-500" type="text" name="zip_code" value="{{ old('zip_code', $store->zip_code) }}" />
                    </div>

                    <div class="mb-4 sm:col-span-2 p-4 bg-slate-50 rounded-lg border border-slate-200">
                        <div class="flex justify-between items-center mb-3">
                            <h4 class="text-sm font-medium text-gray-700">Koordinat Peta</h4>
                            <button type="button" id="btn-cari-koordinat" class="inline-flex items-center px-3 py-1.5 bg-red-100 hover:bg-red-200 text-red-700 font-semibold text-xs rounded-lg transition">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4 mr-1.5">
                                  <path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 1 0 0 11 5.5 5.5 0 0 0 0-11ZM2 9a7 7 0 1 1 12.452 4.391l3.328 3.329a.75.75 0 1 1-1.06 1.06l-3.329-3.328A7 7 0 0 1 2 9Z" clip-rule="evenodd" />
                                </svg>
                                Cari Koordinat
                            </button>
                        </div>
                        <p class="text-xs text-gray-500 mb-3 -mt-2">Klik 'Cari' untuk mengisi otomatis Lat/Long berdasarkan Kelurahan & Kecamatan.</p>
                        
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label for="latitude" class="block font-medium text-sm text-gray-700">Latitude</label>
                                <input type="text" name="latitude" id="latitude" value="{{ old('latitude', $store->latitude ?? '') }}" class="block mt-1 w-full border-gray-300 rounded-md shadow-sm focus:ring-red-500 focus:border-red-500 bg-gray-100" placeholder="-7.12345">
                                @error('latitude') <p class="text-xs text-red-600 mt-1">{{ $message }}</p @enderror
                            </div>
                            <div>
                                <label for="longitude" class="block font-medium text-sm text-gray-700">Longitude</label>
                                <input type="text" name="longitude" id="longitude" value="{{ old('longitude', $store->longitude ?? '') }}" class="block mt-1 w-full border-gray-300 rounded-md shadow-sm focus:ring-red-500 focus:border-red-500 bg-gray-100" placeholder="110.12345">
                                @error('longitude') <p class="text-xs text-red-600 mt-1">{{ $message }}</p @enderror
                            </div>
                        </div>
                    </div>
                    <div class="mb-4">
                        <label for="logo" class="block font-medium text-sm text-gray-700">Logo Toko</label>
                        <input id="logo" class="block mt-1 w-full border-gray-300 rounded-md shadow-sm p-2 focus:border-red-500 focus:ring-red-500" type="file" name="logo" />
                        @if($store->seller_logo)
                        <div class="mt-2">
                            <img src="{{ $store->seller_logo }}" alt="Logo saat ini" class="w-32 h-32 object-cover rounded-full">
                            <small class="text-gray-500">Logo saat ini. Upload file baru untuk mengganti.</small>
                        </div>
                        @endif
                    </div>

                    <div class="flex items-center justify-end mt-4">
                        <button type="submit" class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
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
<script>
document.addEventListener('DOMContentLoaded', function () {
    // --- START: Geocoding Logic ---
    const searchButton = document.getElementById('btn-cari-koordinat');
    const latInput = document.getElementById('latitude');
    const lonInput = document.getElementById('longitude');
    const alertBox = document.getElementById('geocode-alert');

    // Input fields untuk alamat
    const provinceInput = document.getElementById('province');
    const regencyInput = document.getElementById('regency');
    const districtInput = document.getElementById('district');
    const villageInput = document.getElementById('village');

    if (searchButton) {
        searchButton.addEventListener('click', async function() {
            // Mengambil nilai dari form (HANYA YANG RELEVAN)
            const province = provinceInput.value;
            const regency = regencyInput.value;
            const district = districtInput.value;
            const village = villageInput.value;

            // Validasi: Kelurahan dan Kecamatan wajib diisi untuk pencarian
            if (!village || !district) {
                showAlert('Harap isi **Desa/Kelurahan** dan **Kecamatan** terlebih dahulu untuk mencari koordinat.', 'error');
                return;
            }

            // Membangun string alamat (Kelurahan, Kecamatan, Kabupaten/Kota, Provinsi)
            let addressParts = [village, district, regency, province];
            let validAddressParts = addressParts.filter(part => part && part.trim() !== '');
            
            let fullAddress = validAddressParts.join(', ');

            // Tampilkan status loading
            this.disabled = true;
            this.innerHTML = `<svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-red-700" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Mencari...`;
            showAlert('Mencari koordinat untuk: ' + fullAddress, 'info');

            try {
                // Menggunakan Nominatim (OpenStreetMap) API
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
                    showAlert('Koordinat tidak ditemukan. Pastikan Kelurahan, Kecamatan, dan Kota sudah benar.', 'error');
                    latInput.value = '';
                    lonInput.value = '';
                }

            } catch (error) {
                console.error('Error fetching geocode:', error);
                showAlert('Gagal mengambil koordinat. Periksa koneksi internet Anda.', 'error');
            } finally {
                // Kembalikan tombol ke keadaan semula
                this.disabled = false;
                this.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4 mr-1.5"><path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 1 0 0 11 5.5 5.5 0 0 0 0-11ZM2 9a7 7 0 1 1 12.452 4.391l3.328 3.329a.75.75 0 1 1-1.06 1.06l-3.329-3.328A7 7 0 0 1 2 9Z" clip-rule="evenodd" /></svg> Cari Koordinat`;
            }
        });
    }

    function showAlert(message, type = 'info') {
        alertBox.classList.remove('hidden', 'bg-red-50', 'border-red-400', 'text-red-800', 'bg-green-50', 'border-green-400', 'text-green-800', 'bg-blue-50', 'border-blue-400', 'text-blue-800');
        
        if (type === 'error') {
            alertBox.classList.add('bg-red-50', 'border-red-400', 'text-red-800');
        } else if (type === 'success') {
            alertBox.classList.add('bg-green-50', 'border-green-400', 'text-green-800');
        } else { // info
            alertBox.classList.add('bg-blue-50', 'border-blue-400', 'text-blue-800');
        }
        
        alertBox.innerHTML = `<p>${message}</p>`;
        alertBox.classList.remove('hidden');

        // Sembunyikan setelah 5 detik jika bukan error atau info
        if(type === 'success') {
            setTimeout(() => {
                alertBox.classList.add('hidden');
            }, 5000);
        }
    }
    // --- END: Geocoding Logic ---
});
</script>
@endpush