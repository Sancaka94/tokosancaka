@extends('layouts.customer')

@section('title', 'Registrasi Merchant DANA')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

    {{-- HEADER PAGE --}}
    <div class="md:flex md:items-center md:justify-between mb-8">
        <div class="flex-1 min-w-0">
            <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
                Registrasi Merchant DANA
            </h2>
            <p class="mt-1 text-sm text-gray-500">
                Lengkapi data di bawah ini. Data otomatis diambil dari profil Anda jika tersedia.
            </p>
        </div>
        <div class="mt-4 flex md:mt-0 md:ml-4">
            <a href="{{ route('customer.merchant.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                <i class="fas fa-arrow-left mr-2"></i> Kembali
            </a>
        </div>
    </div>

    {{-- ALERT MESSAGES --}}
    @if(session('success'))
        <div class="rounded-md bg-green-50 p-4 mb-6 border border-green-200">
            <div class="flex">
                <div class="flex-shrink-0">
                    <i class="fas fa-check-circle text-green-400"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-green-800">{{ session('success') }}</p>
                </div>
            </div>
        </div>
    @endif

    @if(session('error'))
        <div class="rounded-md bg-red-50 p-4 mb-6 border border-red-200">
            <div class="flex">
                <div class="flex-shrink-0">
                    <i class="fas fa-times-circle text-red-400"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-red-800">{{ session('error') }}</p>
                </div>
            </div>
        </div>
    @endif

    <form action="{{ route('customer.merchant.store') }}" method="POST" enctype="multipart/form-data">
        @csrf

        {{-- CARD 1: INFORMASI DASAR --}}
        <div class="bg-white overflow-hidden shadow rounded-lg border border-gray-200 mb-6">
            <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
                <h3 class="text-lg leading-6 font-medium text-gray-900">1. Informasi Dasar Toko</h3>
            </div>
            <div class="px-6 py-6 bg-white">
                <div class="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
                    
                    <div class="sm:col-span-3">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Merchant ID <span class="text-red-500">*</span></label>
                        {{-- Ini ID default/sistem, tidak diambil dari DB user --}}
                        <input type="text" value="{{ config('services.dana.merchant_id') }}" readonly 
                            class="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 rounded-md bg-gray-100 cursor-not-allowed px-3 py-2 border">
                        <input type="hidden" name="merchantId" value="{{ config('services.dana.merchant_id') }}">
                    </div>

                    <div class="sm:col-span-3">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Parent Division ID</label>
                        <input type="text" name="parentDivisionId" value="{{ config('services.dana.merchant_id') }}" readonly 
                            class="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 rounded-md bg-gray-100 cursor-not-allowed px-3 py-2 border">
                    </div>

                    {{-- NAMA TOKO: Ambil dari Auth 'store_name' --}}
                    <div class="sm:col-span-3">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nama Toko (Main Name) <span class="text-red-500">*</span></label>
                        <input type="text" name="mainName" placeholder="Contoh: Sancaka Store" required
                            value="{{ old('mainName', auth()->user()->store_name) }}"
                            class="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 rounded-md px-3 py-2 border">
                    </div>

                    {{-- ID TOKO UNIK: Default pakai slug dari Nama Toko --}}
                    <div class="sm:col-span-3">
                        <label class="block text-sm font-medium text-gray-700 mb-1">External Shop ID <span class="text-red-500">*</span></label>
                        <input type="text" name="externalShopId" placeholder="ID Unik Toko Anda" required
                            value="{{ old('externalShopId', Str::slug(auth()->user()->store_name ?? 'shop-'.auth()->id())) }}"
                            class="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 rounded-md px-3 py-2 border">
                    </div>

                    <div class="sm:col-span-6">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Deskripsi Toko</label>
                        <textarea name="shopDesc" rows="3" class="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 rounded-md px-3 py-2 border">{{ old('shopDesc', 'Toko Resmi ' . (auth()->user()->store_name ?? '')) }}</textarea>
                    </div>

                    {{-- DROPDOWNS --}}
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Kepemilikan</label>
                        <select name="shopOwning" class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm sm:text-sm">
                            <option value="DIRECT_OWNED">Milik Sendiri</option>
                            <option value="FRANCHISED">Waralaba</option>
                        </select>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tipe Bisnis</label>
                        <select name="shopBizType" class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm sm:text-sm">
                            <option value="ONLINE">Online</option>
                            <option value="OFFLINE">Offline</option>
                        </select>
                    </div>
                    <div class="sm:col-span-1">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Struktur</label>
                        <select name="shopParentType" class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm sm:text-sm">
                            <option value="MERCHANT">MERCHANT</option>
                            <option value="DIVISION">DIVISION</option>
                        </select>
                    </div>
                    <div class="sm:col-span-1">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Ukuran</label>
                        <select name="sizeType" class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm sm:text-sm">
                            <option value="UMI">UMI (Mikro)</option>
                            <option value="UKE">UKE (Kecil)</option>
                            <option value="UME">UME (Menengah)</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        {{-- CARD 2: LOKASI --}}
        <div class="bg-white overflow-hidden shadow rounded-lg border border-gray-200 mb-6">
            <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
                <h3 class="text-lg leading-6 font-medium text-gray-900">2. Lokasi & Alamat Toko</h3>
            </div>
            <div class="px-6 py-6 bg-white">
                <div class="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
                    
                    {{-- KOORDINAT: Ambil dari Auth latitude/longitude --}}
                    <div class="sm:col-span-3">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Latitude <span class="text-red-500">*</span></label>
                        <input type="text" name="lat" placeholder="-6.200000" required
                            value="{{ old('lat', auth()->user()->latitude) }}"
                            class="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 rounded-md px-3 py-2 border">
                    </div>

                    <div class="sm:col-span-3">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Longitude <span class="text-red-500">*</span></label>
                        <input type="text" name="ln" placeholder="106.816666" required
                            value="{{ old('ln', auth()->user()->longitude) }}"
                            class="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 rounded-md px-3 py-2 border">
                    </div>

                    <div class="sm:col-span-6 border-t border-gray-100 my-2"></div>

                    {{-- ALAMAT: Ambil dari Auth address_detail, province, regency --}}
                    <div class="sm:col-span-6">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Alamat Lengkap 1 (Jalan) <span class="text-red-500">*</span></label>
                        <input type="text" name="shopAddress[address1]" placeholder="Jalan, Nomor Gedung" required
                            value="{{ old('shopAddress.address1', auth()->user()->address_detail) }}"
                            class="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 rounded-md px-3 py-2 border">
                    </div>

                    <div class="sm:col-span-6">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Alamat Lengkap 2 (RT/RW/Patokan)</label>
                        <input type="text" name="shopAddress[address2]" placeholder="RT/RW, Patokan"
                            value="{{ old('shopAddress.address2', '-') }}"
                            class="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 rounded-md px-3 py-2 border">
                    </div>

                    <div class="sm:col-span-3">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Kelurahan (Sub District) <span class="text-red-500">*</span></label>
                        <input type="text" name="shopAddress[subDistrict]" required
                            value="{{ old('shopAddress.subDistrict', auth()->user()->village ?? '-') }}"
                            class="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 rounded-md px-3 py-2 border">
                    </div>

                    <div class="sm:col-span-3">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Kecamatan <span class="text-red-500">*</span></label>
                        <input type="text" name="shopAddress[area]" required
                            value="{{ old('shopAddress.area', auth()->user()->district) }}"
                            class="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 rounded-md px-3 py-2 border">
                    </div>

                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Provinsi <span class="text-red-500">*</span></label>
                        <input type="text" name="shopAddress[province]" required
                            value="{{ old('shopAddress.province', auth()->user()->province) }}"
                            class="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 rounded-md px-3 py-2 border">
                    </div>

                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Kota/Kabupaten <span class="text-red-500">*</span></label>
                        <input type="text" name="shopAddress[city]" required
                            value="{{ old('shopAddress.city', auth()->user()->regency) }}"
                            class="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 rounded-md px-3 py-2 border">
                    </div>

                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Kode Pos <span class="text-red-500">*</span></label>
                        <input type="text" name="shopAddress[postcode]" maxlength="5" required
                            value="{{ old('shopAddress.postcode', auth()->user()->postal_code) }}"
                            class="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 rounded-md px-3 py-2 border">
                    </div>
                    
                    <input type="hidden" name="shopAddress[country]" value="Indonesia">
                </div>
            </div>
        </div>

        {{-- CARD 3: EXT INFO --}}
        <div class="bg-white overflow-hidden shadow rounded-lg border border-gray-200 mb-6">
            <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
                <h3 class="text-lg leading-6 font-medium text-gray-900">3. Informasi Bisnis & PIC</h3>
            </div>
            <div class="px-6 py-6 bg-white">
                <div class="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
                    
                    {{-- PIC EMAIL: Ambil dari Auth Email --}}
                    <div class="sm:col-span-3">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Email PIC <span class="text-red-500">*</span></label>
                        <input type="email" name="extInfo[PIC_EMAIL]" required
                            value="{{ old('extInfo.PIC_EMAIL', auth()->user()->email) }}"
                            class="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 rounded-md px-3 py-2 border">
                    </div>

                    {{-- NO HP: Ambil dari Auth no_wa --}}
                    <div class="sm:col-span-3">
                        <label class="block text-sm font-medium text-gray-700 mb-1">No. HP PIC (628...) <span class="text-red-500">*</span></label>
                        @php
                            // Normalisasi No HP (08 -> 628)
                            $phone = auth()->user()->no_wa;
                            if(substr($phone, 0, 1) == '0') $phone = '62' . substr($phone, 1);
                        @endphp
                        <input type="text" name="extInfo[PIC_PHONENUMBER]" required
                            value="{{ old('extInfo.PIC_PHONENUMBER', $phone) }}"
                            class="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 rounded-md px-3 py-2 border">
                    </div>

                    <div class="sm:col-span-3">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Email Admin</label>
                        <input type="email" name="extInfo[SUBMITTER_EMAIL]" required
                            value="{{ old('extInfo.SUBMITTER_EMAIL', auth()->user()->email) }}"
                            class="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 rounded-md px-3 py-2 border">
                    </div>

                    {{-- DROPDOWNS BISNIS (Tetap Manual karena tidak ada di DB User) --}}
                    <div class="sm:col-span-3">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Link Website/Sosmed</label>
                        <input type="url" name="extInfo[EXT_URLS]" placeholder="https://..."
                            class="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 rounded-md px-3 py-2 border">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tipe Barang</label>
                        <select name="extInfo[GOODS_SOLD_TYPE]" class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm sm:text-sm">
                            <option value="DIGITAL">DIGITAL</option>
                            <option value="NON_DIGITAL">NON DIGITAL (Fisik)</option>
                        </select>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Use Case</label>
                        <select name="extInfo[USECASE]" class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm sm:text-sm">
                            <option value="QRIS_DIGITAL">QRIS DIGITAL</option>
                            <option value="QRIS_NON_DIGITAL">QRIS NON DIGITAL</option>
                        </select>
                    </div>

                    {{-- TAMBAHAN BARU: Field Wajib DANA --}}
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Target Pasar (Profiling) <span class="text-red-500">*</span></label>
                        <select name="extInfo[USER_PROFILING]" required class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm sm:text-sm">
                            <option value="B2C">B2C (Ke Konsumen)</option>
                            <option value="B2B">B2B (Ke Bisnis Lain)</option>
                            <option value="B2B2C">B2B2C (Campuran)</option>
                        </select>
                    </div>

                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Rata-rata Nilai Transaksi <span class="text-red-500">*</span></label>
                        <select name="extInfo[AVG_TICKET]" required class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm sm:text-sm">
                            <option value="0-10000">< Rp 10.000</option>
                            <option value="10000-50000">Rp 10.000 - Rp 50.000</option>
                            <option value="50000-100000">Rp 50.000 - Rp 100.000</option>
                            <option value="100000-500000">Rp 100.000 - Rp 500.000</option>
                            <option value=">500000">> Rp 500.000</option>
                        </select>
                    </div>

                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Omzet Per Tahun <span class="text-red-500">*</span></label>
                        <select name="extInfo[OMZET]" required class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm sm:text-sm">
                            <option value="<100JT">< Rp 100 Juta</option>
                            <option value="100JT-500JT">Rp 100 Juta - Rp 500 Juta</option>
                            <option value="500JT-2M">Rp 500 Juta - Rp 2 Milyar</option>
                            <option value=">2M">> Rp 2 Milyar</option>
                        </select>
                    </div>

                </div>
            </div>
        </div>

        {{-- CARD 4: DATA PEMILIK --}}
        <div class="bg-white overflow-hidden shadow rounded-lg border border-gray-200 mb-6">
            <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
                <h3 class="text-lg leading-6 font-medium text-gray-900">4. Data Pemilik Usaha</h3>
            </div>
            <div class="px-6 py-6 bg-white">
                <div class="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
                    
                    @php
                        // Split Nama Lengkap menjadi Depan & Belakang
                        $fullName = auth()->user()->nama_lengkap ?? '';
                        $parts = explode(' ', $fullName);
                        $firstName = $parts[0] ?? '';
                        $lastName = count($parts) > 1 ? implode(' ', array_slice($parts, 1)) : $firstName;
                    @endphp

                    <div class="sm:col-span-3">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nama Depan <span class="text-red-500">*</span></label>
                        <input type="text" name="ownerName[firstName]" required
                            value="{{ old('ownerName.firstName', $firstName) }}"
                            class="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 rounded-md px-3 py-2 border">
                    </div>

                    <div class="sm:col-span-3">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nama Belakang <span class="text-red-500">*</span></label>
                        <input type="text" name="ownerName[lastName]" required
                            value="{{ old('ownerName.lastName', $lastName) }}"
                            class="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 rounded-md px-3 py-2 border">
                    </div>

                    <div class="sm:col-span-3">
                        <label class="block text-sm font-medium text-gray-700 mb-1">No. HP Pemilik <span class="text-red-500">*</span></label>
                        <input type="text" name="ownerPhoneNumber[mobileNo]" required
                            value="{{ old('ownerPhoneNumber.mobileNo', $phone) }}"
                            class="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 rounded-md px-3 py-2 border">
                    </div>

                    {{-- ALAMAT OWNER: Disamakan dengan alamat toko (Auth) --}}
                    <div class="sm:col-span-6 mt-2">
                        <h4 class="text-sm font-bold text-gray-900 mb-3 uppercase tracking-wide border-b pb-1">Alamat Domisili Pemilik</h4>
                    </div>

                    <div class="sm:col-span-6">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Alamat Lengkap (Jalan)</label>
                        <input type="text" name="ownerAddress[address1]" required
                            value="{{ old('ownerAddress.address1', auth()->user()->address_detail) }}"
                            class="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 rounded-md px-3 py-2 border">
                    </div>
                    
                    {{-- Tambahkan ini di CARD 4 (Owner), setelah Provinsi/Kota --}}
                    <div class="sm:col-span-6">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Detail Lain (RT/RW)</label>
                        <input type="text" name="ownerAddress[address2]" required
                            value="{{ old('ownerAddress.address2', '-') }}"
                            class="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 rounded-md px-3 py-2 border">
                    </div>

                    <div class="sm:col-span-3">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Kelurahan (Sub District)</label>
                        <input type="text" name="ownerAddress[subDistrict]" required
                            value="{{ old('ownerAddress.subDistrict', auth()->user()->village ?? '-') }}"
                            class="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 rounded-md px-3 py-2 border">
                    </div>

                    <div class="sm:col-span-3">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Kecamatan</label>
                        <input type="text" name="ownerAddress[area]" required
                            value="{{ old('ownerAddress.area', auth()->user()->district) }}"
                            class="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 rounded-md px-3 py-2 border">
                    </div>
                    
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Kota</label>
                        <input type="text" name="ownerAddress[city]" required
                            value="{{ old('ownerAddress.city', auth()->user()->regency) }}"
                            class="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 rounded-md px-3 py-2 border">
                    </div>
                    
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Provinsi</label>
                        <input type="text" name="ownerAddress[province]" required
                            value="{{ old('ownerAddress.province', auth()->user()->province) }}"
                            class="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 rounded-md px-3 py-2 border">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Kode Pos</label>
                        <input type="text" name="ownerAddress[postcode]" required
                            value="{{ old('ownerAddress.postcode', auth()->user()->postal_code) }}"
                            class="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 rounded-md px-3 py-2 border">
                    </div>
                    <input type="hidden" name="ownerAddress[country]" value="Indonesia">
                </div>
            </div>
        </div>

        {{-- CARD 5: LEGALITAS & DOKUMEN (LOGIKA LENGKAP) --}}
        <div class="bg-white overflow-hidden shadow rounded-lg border border-gray-200 mb-6">
            <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
                <h3 class="text-lg leading-6 font-medium text-gray-900">5. Legalitas & Pajak</h3>
            </div>
            <div class="px-6 py-6 bg-white">
                <div class="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Brand Name</label>
                        <input type="text" name="brandName" required
                            value="{{ old('brandName', auth()->user()->store_name) }}"
                            class="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 rounded-md px-3 py-2 border">
                    </div>

                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">MCC Code</label>
                        <input type="text" name="mccCodes[]" value="0783" required
                            class="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 rounded-md px-3 py-2 border">
                    </div>

                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">NPWP (Optional)</label>
                        <input type="text" name="taxNo" value="0000000000000000" required
                            class="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 rounded-md px-3 py-2 border">
                    </div>

                    <div class="sm:col-span-6">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Alamat NPWP</label>
                        <input type="text" name="taxAddress[address1]" placeholder="Jalan" required
                            value="{{ old('taxAddress.address1', auth()->user()->address_detail) }}"
                            class="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 rounded-md px-3 py-2 border">
                    </div>

                    <div class="sm:col-span-6 border-t mt-4 pt-4"><h4 class="text-sm font-bold text-gray-900 uppercase">Dokumen Identitas / Bisnis</h4></div>

                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Badan Usaha</label>
                        <select name="businessEntity" id="businessEntity" onchange="adjustDocType()" class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm sm:text-sm">
                            <option value="individu">Perorangan</option>
                            <option value="pt">PT</option>
                            <option value="cv">CV</option>
                            <option value="yayasan">Yayasan</option>
                            <option value="usaha_dagang">Usaha Dagang</option>
                            <option value="koperasi">Koperasi</option>
                        </select>
                    </div>

                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tipe Dokumen</label>
                        <select name="docType" id="docType" class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm sm:text-sm"></select>
                        <input type="hidden" id="oldDocType" value="{{ old('docType', 'KTP') }}">
                    </div>

                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nomor Dokumen</label>
                        <input type="text" name="docId" required placeholder="NIK / NIB"
                            value="{{ old('docId') }}"
                            class="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 rounded-md px-3 py-2 border">
                    </div>
                    
                    {{-- Hidden: ownerIdType akan diset sama dengan docType di controller/js --}}
                    <input type="hidden" name="ownerIdType" id="hiddenOwnerIdType" value="KTP">
                </div>
            </div>
        </div>

        {{-- CARD 6: MANAJEMEN (Otomatis Nama Owner) --}}
        <div class="bg-white overflow-hidden shadow rounded-lg border border-gray-200 mb-6">
            <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
                <h3 class="text-lg leading-6 font-medium text-gray-900">6. Struktur Manajemen</h3>
            </div>
            <div class="px-6 py-6 bg-white">
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <div class="border border-gray-200 rounded-md p-4 bg-gray-50">
                        <h4 class="text-sm font-bold text-gray-700 uppercase mb-3">Direktur (Finance)</h4>
                        <div class="space-y-3">
                            <div>
                                <label class="block text-xs font-medium text-gray-500 mb-1">Nama Lengkap</label>
                                <input type="text" name="directorPics[0][picName]" 
                                    value="{{ old('directorPics.0.picName', auth()->user()->nama_lengkap) }}"
                                    class="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 rounded-md px-3 py-2 border">
                            </div>
                            <input type="hidden" name="directorPics[0][picPosition]" value="DIRECTOR_FINANCE">
                        </div>
                    </div>

                    <div class="border border-gray-200 rounded-md p-4 bg-gray-50">
                        <h4 class="text-sm font-bold text-gray-700 uppercase mb-3">Operasional</h4>
                        <div class="space-y-3">
                            <div>
                                <label class="block text-xs font-medium text-gray-500 mb-1">Nama Lengkap</label>
                                <input type="text" name="nonDirectorPics[0][picName]" 
                                    value="{{ old('nonDirectorPics.0.picName', auth()->user()->nama_lengkap) }}"
                                    class="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 rounded-md px-3 py-2 border">
                            </div>
                            <input type="hidden" name="nonDirectorPics[0][picPosition]" value="OPERATION">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- CARD 7: UPLOAD --}}
        <div class="bg-white overflow-hidden shadow rounded-lg border border-gray-200 mb-6">
            <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
                <h3 class="text-lg leading-6 font-medium text-gray-900">7. Upload Dokumen</h3>
            </div>
            <div class="px-6 py-6 bg-white">
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Upload Logo Toko (PNG)</label>
                        <input type="file" name="shop_logo" accept="image/png" required class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Upload KTP (PDF/IMG)</label>
                        <input type="file" name="business_doc_file" accept="image/*,application/pdf" required class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-green-50 file:text-green-700 hover:file:bg-green-100">
                    </div>
                </div>
            </div>
        </div>

        {{-- TOMBOL --}}
        <div class="flex justify-end mb-12">
            <button type="submit" class="inline-flex justify-center py-2 px-6 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none">
                Kirim Pendaftaran
            </button>
        </div>

    </form>
</div>

{{-- SCRIPT LOGIKA DOKUMEN --}}
<script>
    function adjustDocType() {
        var entity = document.getElementById("businessEntity").value;
        var docSelect = document.getElementById("docType");
        var oldVal = document.getElementById("oldDocType").value;
        var hiddenIdType = document.getElementById("hiddenOwnerIdType");

        docSelect.innerHTML = "";
        var options = [];

        if (entity === "individu") {
            options = [
                {val: "KTP", text: "KTP (Kartu Tanda Penduduk)"},
                {val: "SIM", text: "SIM (Surat Izin Mengemudi)"},
                {val: "PASSPORT", text: "PASSPORT"}
            ];
        } else {
            options = [
                {val: "NIB", text: "NIB (Nomor Induk Berusaha)"},
                {val: "SIUP", text: "SIUP (Surat Izin Usaha)"}
            ];
        }

        options.forEach(function(opt) {
            var option = document.createElement("option");
            option.value = opt.val;
            option.text = opt.text;
            if(opt.val === oldVal) option.selected = true;
            docSelect.appendChild(option);
        });
        
        // Update hidden input saat dropdown berubah
        if(docSelect.value) hiddenIdType.value = docSelect.value;
        
        docSelect.onchange = function() {
            hiddenIdType.value = this.value;
        };
    }

    document.addEventListener("DOMContentLoaded", function() {
        adjustDocType();
    });
</script>
@endsection