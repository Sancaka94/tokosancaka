@extends('layouts.customer')
@section('title', 'Buka Toko Baru Anda')

@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
<style>
    /* Style untuk stepper */
    .stepper-item { display: flex; align-items: center; gap: 1rem; }
    .stepper-dot { display: flex; align-items: center; justify-content: center; width: 2rem; height: 2rem; border-radius: 50%; color: white; font-weight: 600; flex-shrink: 0; }
    .stepper-line { width: 1px; height: 3rem; background-color: #D1D5DB; margin-left: 0.9375rem; }

    /* Style untuk spinner */
    .spinner-border {
        width: 1rem; height: 1rem;
        border: 2px solid currentColor;
        border-right-color: transparent;
        border-radius: 50%;
        animation: spinner-border .75s linear infinite;
    }
    @keyframes spinner-border { to { transform: rotate(360deg); } }
</style>
@endpush

@section('content')
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-10">

            {{-- KOLOM KIRI: STEPPER / INFORMASI --}}
            <div class="lg:col-span-1">
                <h2 class="text-2xl font-semibold text-gray-800">Satu Langkah Lagi</h2>
                <p class="mt-2 text-gray-600">Selesaikan pendaftaran toko Anda untuk mulai berjualan.</p>

                <div class="mt-8">
                    <div class="flex flex-col">
                        <div class="stepper-item">
                            <div class="stepper-dot bg-green-500">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                            </div>
                            <div>
                                <h3 class="font-medium text-lg text-gray-900">1. Akun Dibuat</h3>
                                <p class="text-sm text-gray-600">Anda telah berhasil membuat akun pelanggan.</p>
                            </div>
                        </div>
                        <div class="stepper-line"></div>
                        <div class="stepper-item">
                            <div class="stepper-dot bg-red-600">
                                <span class="font-bold text-sm">2</span>
                            </div>
                            <div>
                                <h3 class="font-medium text-lg text-gray-900">2. Detail Toko & Alamat</h3>
                                <p class="text-sm text-gray-600">Isi nama toko dan atur alamat pengiriman Anda.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- KOLOM KANAN: FORM --}}
            <div class="lg:col-span-2">
                <div class="bg-white overflow-hidden shadow-lg sm:rounded-lg">

                    {{-- Ganti seluruh blok <form> Anda dengan kode ini --}}
                    <form id="store-register-form"
                        action="{{ route('seller.register.submit') }}"
                        method="POST"
                        x-data="addressForm(
                            '{{ route('seller.address.geocode') }}',
                            '{{ csrf_token() }}',
                            {
                                lat: '{{ old('latitude', auth()->user()->latitude) }}',
                                lng: '{{ old('longitude', auth()->user()->longitude) }}',
                                province: '{{ old('province', auth()->user()->province) }}',
                                regency: '{{ old('regency', auth()->user()->regency) }}',
                                district: '{{ old('district', auth()->user()->district) }}',
                                village: '{{ old('village', auth()->user()->village) }}',
                                postal_code: '{{ old('postal_code', auth()->user()->postal_code) }}',
                                address_detail: `{{ old('address_detail', auth()->user()->address_detail) }}`
                            }
                        )">
                        @csrf

                        <div class="p-6 sm:p-8">
                            <h3 class="text-xl font-semibold text-gray-800 mb-6">Lengkapi Detail Toko</h3>

                            {{-- Tampilkan error (jika ada) --}}
                            @if ($errors->any())
                                <div class="mb-6 p-4 bg-red-100 border border-red-300 text-red-700 rounded-lg">
                                    <ul class="list-disc list-inside text-sm">
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            {{-- 1. NAMA TOKO (DIKUNCI) --}}
                            <div class="mb-6">
                                <label for="store_name" class="block text-sm font-medium text-gray-700">Nama Toko <span class="text-red-500">*</span></label>
                                <input type="text" name="store_name" id="store_name"
                                    value="{{ old('store_name', $currentStoreName ?? auth()->user()->store_name ?? '') }}"
                                    {{-- ✅ READONLY & ABU-ABU --}}
                                    readonly
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm bg-gray-200 text-gray-600 cursor-not-allowed focus:border-gray-300 focus:ring-0"
                                    required>
                                <p class="mt-1 text-xs text-gray-500">Nama toko sesuai data registrasi awal (tidak dapat diubah).</p>
                            </div>

                            {{-- 2. DESKRIPSI (BISA DIEDIT) --}}
                            <div class="mb-6">
                                <label for="description" class="block text-sm font-medium text-gray-700">Deskripsi Singkat Toko (Opsional)</label>
                                <textarea name="description" id="description" rows="3"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500"
                                        placeholder="Jelaskan tentang Toko Anda, produk apa yang dijual, dll.">{{ old('description') }}</textarea>
                            </div>

                            {{-- Divider --}}
                            <h3 class="text-lg font-semibold text-gray-800 mb-4 border-t pt-6">Alamat Toko (Pengiriman)</h3>

                            {{-- Hidden Input untuk Lat/Long (agar tetap terkirim ke backend) --}}
                            <input type="hidden" name="latitude" id="latitude" x-model="fields.lat">
                            <input type="hidden" name="longitude" id="longitude" x-model="fields.lng">

                            {{-- 3. LATITUDE & LONGITUDE (BISA DIEDIT / AUTO) --}}
                            <div class="grid grid-cols-2 gap-4 mb-4">
                                <div>
                                    <label for="lat_display" class="block text-sm font-medium text-gray-700">Latitude <span class="text-red-500">*</span></label>
                                    {{-- ✅ TIDAK READONLY (Bisa diketik manual) --}}
                                    <input type="text" id="lat_display" x-model="fields.lat"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500"
                                        placeholder="-7.xxxxx">
                                </div>
                                <div>
                                    <label for="lng_display" class="block text-sm font-medium text-gray-700">Longitude <span class="text-red-500">*</span></label>
                                    {{-- ✅ TIDAK READONLY (Bisa diketik manual) --}}
                                    <input type="text" id="lng_display" x-model="fields.lng"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500"
                                        placeholder="111.xxxxx">
                                </div>
                            </div>


                            {{-- 4. KOLOM ALAMAT (SEMUA DIKUNCI) --}}
                            <div class="grid grid-cols-2 gap-4 mb-4">
                                <div>
                                    <label for="province" class="block text-sm font-medium text-gray-700">Provinsi</label>
                                    <input type="text" name="province" id="province" x-model="fields.province"
                                        readonly class="mt-1 block w-full rounded-md border-gray-300 shadow-sm bg-gray-200 text-gray-600 cursor-not-allowed focus:ring-0">
                                </div>
                                <div>
                                    <label for="regency" class="block text-sm font-medium text-gray-700">Kabupaten / Kota</label>
                                    <input type="text" name="regency" id="regency" x-model="fields.regency"
                                        readonly class="mt-1 block w-full rounded-md border-gray-300 shadow-sm bg-gray-200 text-gray-600 cursor-not-allowed focus:ring-0">
                                </div>
                                <div>
                                    <label for="district" class="block text-sm font-medium text-gray-700">Kecamatan</label>
                                    <input type="text" name="district" id="district" x-model="fields.district"
                                        readonly class="mt-1 block w-full rounded-md border-gray-300 shadow-sm bg-gray-200 text-gray-600 cursor-not-allowed focus:ring-0">
                                </div>
                                <div>
                                    <label for="village" class="block text-sm font-medium text-gray-700">Desa / Kelurahan</label>
                                    <input type="text" name="village" id="village" x-model="fields.village"
                                        readonly class="mt-1 block w-full rounded-md border-gray-300 shadow-sm bg-gray-200 text-gray-600 cursor-not-allowed focus:ring-0">
                                </div>
                                <div>
                                    <label for="postal_code" class="block text-sm font-medium text-gray-700">Kode Pos</label>
                                    <input type="text" name="postal_code" id="postal_code" x-model="fields.postal_code"
                                        readonly class="mt-1 block w-full rounded-md border-gray-300 shadow-sm bg-gray-200 text-gray-600 cursor-not-allowed focus:ring-0">
                                </div>
                            </div>

                            {{-- 5. DETAIL ALAMAT (DIKUNCI) --}}
                            <div class="mb-4">
                                <label for="address_detail" class="block text-sm font-medium text-gray-700">Detail Alamat</label>
                                <textarea name="address_detail" id="address_detail" rows="3" x-model="fields.address_detail"
                                        readonly class="mt-1 block w-full rounded-md border-gray-300 shadow-sm bg-gray-200 text-gray-600 cursor-not-allowed focus:ring-0">{{ old('address_detail', auth()->user()->address_detail) }}</textarea>
                            </div>

                            {{-- TOMBOL DAPATKAN KOORDINAT --}}
                            <div class="mb-4">
                                <button type="button" @click="getCoords" :disabled="geocoding" class="inline-flex items-center px-4 py-2 bg-blue-50 text-blue-700 border border-blue-300 rounded-md font-semibold text-xs uppercase tracking-widest hover:bg-blue-100 transition">
                                    <span x-show="!geocoding" class="flex items-center">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                                        Auto-Generate Koordinat (Dari Alamat)
                                    </span>
                                    <span x-show="geocoding" class="flex items-center">
                                        <span class="spinner-border mr-2"></span> Mencari...
                                    </span>
                                </button>
                                <p class="mt-1 text-xs text-gray-500">Klik tombol di atas untuk mengisi Latitude & Longitude secara otomatis berdasarkan alamat Anda.</p>
                                <span x-show="geocodeMessage" x-text="geocodeMessage" :class="geocodeSuccess ? 'text-green-600' : 'text-red-500'" class="block mt-2 text-sm font-bold"></span>
                            </div>

                            {{-- TOMBOL ACTION --}}
                            <div class="px-6 py-4 bg-gray-50 flex items-center justify-end gap-4 border-t border-gray-200 -mx-6 -mb-6 mt-6">
                                <a href="{{ route('customer.dashboard') }}" class="text-sm font-medium text-gray-600 hover:text-gray-900">Batal</a>
                                <button type="submit" class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-500 focus:outline-none focus:ring ring-red-300 transition ease-in-out duration-150">
                                    Daftarkan Toko Saya
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection


@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('addressForm', (geocodeUrl, csrfToken, initialFields) => ({
        fields: {
            lat: initialFields.lat || '',
            lng: initialFields.lng || '',
            province: initialFields.province || '',
            regency: initialFields.regency || '',
            district: initialFields.district || '',
            village: initialFields.village || '',
            postal_code: initialFields.postal_code || '',
            address_detail: initialFields.address_detail || ''
        },
        geocoding: false,
        geocodeMessage: '',
        geocodeSuccess: false,

        // Fungsi untuk mendapatkan koordinat
        async getCoords() {
            this.geocoding = true;
            this.geocodeMessage = 'Mencari koordinat...';
            this.geocodeSuccess = false;


            // ===============================================
            // PERUBAHAN LOGIKA PENCOCOKAN
            // ===============================================
            // Alamat yang dikirim ke Nominatim tidak perlu sedetail 'address_detail'
            // Cukup kelurahan, kecamatan, kota, provinsi.
            const fullAddress = [
                // this.fields.address_detail, // Tidak diikutkan dalam pencarian
                this.fields.village,
                this.fields.district,
                this.fields.regency,
                this.fields.province,
                // this.fields.postal_code // Tidak diikutkan dalam pencarian
            ].filter(Boolean).join(', '); // Gabungkan field yang relevan
            // ===============================================
            // AKHIR PERUBAHAN
            // ===============================================

            // Validasi baru: Memastikan kelurahan dan kecamatan diisi
            if (!this.fields.village || !this.fields.district) {
                this.geocodeMessage = 'Harap isi Desa/Kelurahan dan Kecamatan terlebih dahulu.';
                this.geocodeSuccess = false;
                this.geocoding = false;
                return;
            }

            try {
                const response = await fetch(geocodeUrl, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({ address: fullAddress })
                });

                const result = await response.json();

                if (result.success && result.data.lat) {
                    this.fields.lat = result.data.lat;
                    this.fields.lng = result.data.lng;
                    this.geocodeMessage = 'Koordinat berhasil ditemukan!';
                    this.geocodeSuccess = true;
                } else {
                    this.fields.lat = '';
                    this.fields.lng = '';
                    // Pesan error yang lebih spesifik
                    this.geocodeMessage = result.message || 'Koordinat tidak ditemukan. Pastikan Kelurahan, Kecamatan, dan Kota sudah benar.';
                    this.geocodeSuccess = false;
                }
            } catch (error) {
                console.error('Error fetching geocode:', error);
                this.geocodeMessage = 'Error: Gagal terhubung ke server.';
                this.geocodeSuccess = false;
            } finally {
                this.geocoding = false;
            }
        },

        // Panggil geocode saat load jika data alamat ada tapi lat/long kosong
        init() {
            if ((!this.fields.lat || !this.fields.lng) && this.fields.address_detail) {
                this.geocodeMessage = 'Koordinat kosong. Klik "Dapatkan Koordinat"';
                this.geocodeSuccess = false;
            }
        }
    }));
});
</script>
@endpush
