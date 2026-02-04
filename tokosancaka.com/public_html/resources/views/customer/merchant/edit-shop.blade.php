@extends('layouts.customer')

@section('title', 'Update Data Toko DANA')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

    {{-- HEADER --}}
    <div class="md:flex md:items-center md:justify-between mb-8">
        <div class="flex-1 min-w-0">
            <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
                Update Informasi Toko
            </h2>
            <p class="mt-1 text-sm text-gray-500">
                Shop ID: <span class="font-mono font-bold">{{ $shop->dana_shop_id ?? 'Draft' }}</span> | External ID: {{ $shop->external_shop_id }}
            </p>
        </div>
        <div class="mt-4 flex md:mt-0 md:ml-4">
            <a href="{{ route('customer.merchant.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                <i class="fas fa-arrow-left mr-2"></i> Kembali
            </a>
        </div>
    </div>

    {{-- ERROR ALERT --}}
    @if(session('error'))
        <div class="rounded-md bg-red-50 p-4 mb-6 border border-red-200">
            <div class="flex">
                <i class="fas fa-exclamation-circle text-red-400 mt-0.5 mr-3"></i>
                <p class="text-sm font-medium text-red-800">{{ session('error') }}</p>
            </div>
        </div>
    @endif

    <form action="{{ route('customer.merchant.update', $shop->id) }}" method="POST" enctype="multipart/form-data">
        @csrf

        {{-- =========================================================
             CARD 1: INFORMASI DASAR & STRUKTUR
             ========================================================= --}}
        <div class="bg-white overflow-hidden shadow rounded-lg border border-gray-200 mb-6">
            <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">1. Informasi Dasar Toko</h3>
            </div>
            <div class="px-6 py-6 bg-white">
                <div class="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
                    
                    {{-- Nama Toko --}}
                    <div class="sm:col-span-3">
                        <label class="block text-sm font-medium text-gray-700">Nama Toko (Main Name) <span class="text-red-500">*</span></label>
                        <input type="text" name="mainName" required value="{{ old('mainName', $shop->main_name) }}" class="mt-1 shadow-sm border-gray-300 rounded-md block w-full sm:text-sm px-3 py-2 border">
                    </div>

                    {{-- Deskripsi --}}
                    <div class="sm:col-span-6">
                        <label class="block text-sm font-medium text-gray-700">Deskripsi Toko</label>
                        <textarea name="shopDesc" rows="2" class="mt-1 shadow-sm border-gray-300 rounded-md block w-full sm:text-sm px-3 py-2 border">{{ old('shopDesc', $shop->shop_desc) }}</textarea>
                    </div>

                    {{-- Kepemilikan --}}
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-gray-700">Kepemilikan</label>
                        <select name="shopOwning" class="mt-1 block w-full py-2 px-3 border border-gray-300 rounded-md shadow-sm sm:text-sm">
                            <option value="DIRECT_OWNED" {{ (old('shopOwning', $shop->shop_owning ?? '') == 'DIRECT_OWNED') ? 'selected' : '' }}>Milik Sendiri</option>
                            <option value="FRANCHISED" {{ (old('shopOwning', $shop->shop_owning ?? '') == 'FRANCHISED') ? 'selected' : '' }}>Waralaba</option>
                        </select>
                    </div>

                    {{-- Tipe Bisnis --}}
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-gray-700">Tipe Bisnis</label>
                        <select name="shopBizType" class="mt-1 block w-full py-2 px-3 border border-gray-300 rounded-md shadow-sm sm:text-sm">
                            <option value="ONLINE" {{ (old('shopBizType', $shop->shop_biz_type ?? '') == 'ONLINE') ? 'selected' : '' }}>Online</option>
                            <option value="OFFLINE" {{ (old('shopBizType', $shop->shop_biz_type ?? '') == 'OFFLINE') ? 'selected' : '' }}>Offline</option>
                        </select>
                    </div>

                    {{-- Struktur & Ukuran --}}
                    <div class="sm:col-span-1">
                        <label class="block text-sm font-medium text-gray-700">Struktur</label>
                        <select name="shopParentType" class="mt-1 block w-full py-2 px-3 border border-gray-300 rounded-md shadow-sm sm:text-sm">
                            <option value="MERCHANT" selected>Merchant</option>
                            <option value="DIVISION">Division</option>
                        </select>
                    </div>
                    <div class="sm:col-span-1">
                        <label class="block text-sm font-medium text-gray-700">Ukuran</label>
                        <select name="sizeType" class="mt-1 block w-full py-2 px-3 border border-gray-300 rounded-md shadow-sm sm:text-sm">
                            <option value="UMI" {{ (old('sizeType', $shop->size_type ?? '') == 'UMI') ? 'selected' : '' }}>Mikro (UMI)</option>
                            <option value="UKE" {{ (old('sizeType', $shop->size_type ?? '') == 'UKE') ? 'selected' : '' }}>Kecil (UKE)</option>
                            <option value="UME" {{ (old('sizeType', $shop->size_type ?? '') == 'UME') ? 'selected' : '' }}>Menengah (UME)</option>
                        </select>
                    </div>

                </div>
            </div>
        </div>

        {{-- =========================================================
             CARD 2: ALAMAT LENGKAP TOKO
             ========================================================= --}}
        <div class="bg-white overflow-hidden shadow rounded-lg border border-gray-200 mb-6">
            <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">2. Lokasi & Alamat Toko</h3>
            </div>
            <div class="px-6 py-6 bg-white">
                <div class="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
                    
                    {{-- Koordinat --}}
                    <div class="sm:col-span-3">
                        <label class="block text-sm font-medium text-gray-700">Latitude</label>
                        <input type="text" name="lat" required value="{{ old('lat', $shop->lat) }}" class="mt-1 shadow-sm border-gray-300 rounded-md block w-full sm:text-sm px-3 py-2 border">
                    </div>
                    <div class="sm:col-span-3">
                        <label class="block text-sm font-medium text-gray-700">Longitude</label>
                        <input type="text" name="ln" required value="{{ old('ln', $shop->ln) }}" class="mt-1 shadow-sm border-gray-300 rounded-md block w-full sm:text-sm px-3 py-2 border">
                    </div>

                    <div class="sm:col-span-6 border-t my-2"></div>

                    {{-- Detail Alamat --}}
                    <div class="sm:col-span-6">
                        <label class="block text-sm font-medium text-gray-700">Alamat Lengkap 1 (Nama Jalan/Gedung)</label>
                        <input type="text" name="shopAddress[address1]" required value="{{ old('shopAddress.address1', $shop->shop_address['address1'] ?? '') }}" class="mt-1 shadow-sm border-gray-300 rounded-md block w-full sm:text-sm px-3 py-2 border">
                    </div>
                    <div class="sm:col-span-6">
                        <label class="block text-sm font-medium text-gray-700">Alamat Lengkap 2 (RT/RW/Patokan)</label>
                        <input type="text" name="shopAddress[address2]" required value="{{ old('shopAddress.address2', $shop->shop_address['address2'] ?? '-') }}" class="mt-1 shadow-sm border-gray-300 rounded-md block w-full sm:text-sm px-3 py-2 border">
                    </div>

                    <div class="sm:col-span-3">
                        <label class="block text-sm font-medium text-gray-700">Kelurahan (Sub District)</label>
                        <input type="text" name="shopAddress[subDistrict]" required value="{{ old('shopAddress.subDistrict', $shop->shop_address['subDistrict'] ?? '-') }}" class="mt-1 shadow-sm border-gray-300 rounded-md block w-full sm:text-sm px-3 py-2 border">
                    </div>
                    <div class="sm:col-span-3">
                        <label class="block text-sm font-medium text-gray-700">Kecamatan (Area)</label>
                        <input type="text" name="shopAddress[area]" required value="{{ old('shopAddress.area', $shop->shop_address['area'] ?? '') }}" class="mt-1 shadow-sm border-gray-300 rounded-md block w-full sm:text-sm px-3 py-2 border">
                    </div>

                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-gray-700">Kota/Kabupaten</label>
                        <input type="text" name="shopAddress[city]" required value="{{ old('shopAddress.city', $shop->shop_address['city'] ?? '') }}" class="mt-1 shadow-sm border-gray-300 rounded-md block w-full sm:text-sm px-3 py-2 border">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-gray-700">Provinsi</label>
                        <input type="text" name="shopAddress[province]" required value="{{ old('shopAddress.province', $shop->shop_address['province'] ?? '') }}" class="mt-1 shadow-sm border-gray-300 rounded-md block w-full sm:text-sm px-3 py-2 border">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-gray-700">Kode Pos</label>
                        <input type="text" name="shopAddress[postcode]" required value="{{ old('shopAddress.postcode', $shop->shop_address['postcode'] ?? '') }}" class="mt-1 shadow-sm border-gray-300 rounded-md block w-full sm:text-sm px-3 py-2 border">
                    </div>
                </div>
            </div>
        </div>

        {{-- =========================================================
             CARD 3: PROFILING & PIC (EXT INFO)
             ========================================================= --}}
        <div class="bg-white overflow-hidden shadow rounded-lg border border-gray-200 mb-6">
            <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">3. Informasi Bisnis & PIC</h3>
            </div>
            <div class="px-6 py-6 bg-white">
                <div class="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
                    {{-- Kontak PIC --}}
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-gray-700">Email PIC</label>
                        <input type="email" name="extInfo[PIC_EMAIL]" required value="{{ old('extInfo.PIC_EMAIL', $shop->ext_info['PIC_EMAIL'] ?? '') }}" class="mt-1 shadow-sm border-gray-300 rounded-md block w-full sm:text-sm px-3 py-2 border">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-gray-700">No. HP PIC (62...)</label>
                        <input type="text" name="extInfo[PIC_PHONENUMBER]" required value="{{ old('extInfo.PIC_PHONENUMBER', $shop->ext_info['PIC_PHONENUMBER'] ?? '') }}" class="mt-1 shadow-sm border-gray-300 rounded-md block w-full sm:text-sm px-3 py-2 border">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-gray-700">Email Admin (Submitter)</label>
                        <input type="email" name="extInfo[SUBMITTER_EMAIL]" required value="{{ old('extInfo.SUBMITTER_EMAIL', $shop->ext_info['SUBMITTER_EMAIL'] ?? '') }}" class="mt-1 shadow-sm border-gray-300 rounded-md block w-full sm:text-sm px-3 py-2 border">
                    </div>

                    {{-- Detail Bisnis --}}
                    <div class="sm:col-span-3">
                        <label class="block text-sm font-medium text-gray-700">Link Website/Sosmed</label>
                        <input type="url" name="extInfo[EXT_URLS]" value="{{ old('extInfo.EXT_URLS', $shop->ext_info['EXT_URLS'] ?? '') }}" class="mt-1 shadow-sm border-gray-300 rounded-md block w-full sm:text-sm px-3 py-2 border">
                    </div>
                    <div class="sm:col-span-3">
                        <label class="block text-sm font-medium text-gray-700">Tipe Barang</label>
                        <select name="extInfo[GOODS_SOLD_TYPE]" class="mt-1 block w-full py-2 px-3 border border-gray-300 rounded-md shadow-sm sm:text-sm">
                            <option value="DIGITAL" {{ (old('extInfo.GOODS_SOLD_TYPE', $shop->ext_info['GOODS_SOLD_TYPE'] ?? '') == 'DIGITAL') ? 'selected' : '' }}>Digital</option>
                            <option value="NON_DIGITAL" {{ (old('extInfo.GOODS_SOLD_TYPE', $shop->ext_info['GOODS_SOLD_TYPE'] ?? '') == 'NON_DIGITAL') ? 'selected' : '' }}>Fisik (Non Digital)</option>
                        </select>
                    </div>

                    {{-- Profiling Keuangan --}}
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-gray-700">Target Pasar</label>
                        <select name="extInfo[USER_PROFILING]" class="mt-1 block w-full py-2 px-3 border border-gray-300 rounded-md shadow-sm sm:text-sm">
                            <option value="B2C" {{ (old('extInfo.USER_PROFILING', $shop->ext_info['USER_PROFILING'] ?? '') == 'B2C') ? 'selected' : '' }}>B2C (Konsumen)</option>
                            <option value="B2B" {{ (old('extInfo.USER_PROFILING', $shop->ext_info['USER_PROFILING'] ?? '') == 'B2B') ? 'selected' : '' }}>B2B (Bisnis)</option>
                        </select>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-gray-700">Rata-rata Transaksi</label>
                        <select name="extInfo[AVG_TICKET]" class="mt-1 block w-full py-2 px-3 border border-gray-300 rounded-md shadow-sm sm:text-sm">
                            <option value="10000-50000" {{ (old('extInfo.AVG_TICKET', $shop->ext_info['AVG_TICKET'] ?? '') == '10000-50000') ? 'selected' : '' }}>10rb - 50rb</option>
                            <option value="50000-100000" {{ (old('extInfo.AVG_TICKET', $shop->ext_info['AVG_TICKET'] ?? '') == '50000-100000') ? 'selected' : '' }}>50rb - 100rb</option>
                            <option value="100000-500000" {{ (old('extInfo.AVG_TICKET', $shop->ext_info['AVG_TICKET'] ?? '') == '100000-500000') ? 'selected' : '' }}>100rb - 500rb</option>
                        </select>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-gray-700">Omzet Per Tahun</label>
                        <select name="extInfo[OMZET]" class="mt-1 block w-full py-2 px-3 border border-gray-300 rounded-md shadow-sm sm:text-sm">
                            <option value="<100JT" {{ (old('extInfo.OMZET', $shop->ext_info['OMZET'] ?? '') == '<100JT') ? 'selected' : '' }}>< 100 Juta</option>
                            <option value="100JT-500JT" {{ (old('extInfo.OMZET', $shop->ext_info['OMZET'] ?? '') == '100JT-500JT') ? 'selected' : '' }}>100jt - 500jt</option>
                            <option value="500JT-2M" {{ (old('extInfo.OMZET', $shop->ext_info['OMZET'] ?? '') == '500JT-2M') ? 'selected' : '' }}>500jt - 2 Milyar</option>
                        </select>
                    </div>
                    {{-- Hidden Usecase Default --}}
                    <input type="hidden" name="extInfo[USECASE]" value="QRIS_DIGITAL">
                </div>
            </div>
        </div>

        {{-- =========================================================
             CARD 4: DATA PEMILIK (OWNER) LENGKAP
             ========================================================= --}}
        <div class="bg-white overflow-hidden shadow rounded-lg border border-gray-200 mb-6">
            <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">4. Data & Alamat Pemilik</h3>
            </div>
            <div class="px-6 py-6 bg-white">
                <div class="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
                    {{-- Nama Owner --}}
                    <div class="sm:col-span-3">
                        <label class="block text-sm font-medium text-gray-700">Nama Depan</label>
                        <input type="text" name="ownerName[firstName]" required value="{{ old('ownerName.firstName', $shop->owner_first_name) }}" class="mt-1 shadow-sm border-gray-300 rounded-md block w-full sm:text-sm px-3 py-2 border">
                    </div>
                    <div class="sm:col-span-3">
                        <label class="block text-sm font-medium text-gray-700">Nama Belakang</label>
                        <input type="text" name="ownerName[lastName]" required value="{{ old('ownerName.lastName', $shop->owner_last_name) }}" class="mt-1 shadow-sm border-gray-300 rounded-md block w-full sm:text-sm px-3 py-2 border">
                    </div>
                    
                    {{-- Kontak Owner --}}
                    <div class="sm:col-span-3">
                        <label class="block text-sm font-medium text-gray-700">No. HP Pemilik</label>
                        <input type="text" name="ownerPhoneNumber[mobileNo]" required value="{{ old('ownerPhoneNumber.mobileNo', $shop->owner_phone) }}" class="mt-1 shadow-sm border-gray-300 rounded-md block w-full sm:text-sm px-3 py-2 border">
                    </div>

                    {{-- ALAMAT OWNER --}}
                    <div class="sm:col-span-6 border-t mt-2 pt-4">
                        <h4 class="text-xs font-bold text-gray-500 uppercase">Alamat Domisili Pemilik</h4>
                    </div>
                    
                    <div class="sm:col-span-6">
                        <label class="block text-sm font-medium text-gray-700">Alamat Lengkap (Jalan)</label>
                        <input type="text" name="ownerAddress[address1]" required value="{{ old('ownerAddress.address1', $shop->owner_address['address1'] ?? '') }}" class="mt-1 shadow-sm border-gray-300 rounded-md block w-full sm:text-sm px-3 py-2 border">
                    </div>
                    <div class="sm:col-span-6">
                        <label class="block text-sm font-medium text-gray-700">Detail Lain (RT/RW)</label>
                        <input type="text" name="ownerAddress[address2]" required value="{{ old('ownerAddress.address2', $shop->owner_address['address2'] ?? '-') }}" class="mt-1 shadow-sm border-gray-300 rounded-md block w-full sm:text-sm px-3 py-2 border">
                    </div>
                    <div class="sm:col-span-3">
                        <label class="block text-sm font-medium text-gray-700">Kelurahan (Sub District)</label>
                        <input type="text" name="ownerAddress[subDistrict]" required value="{{ old('ownerAddress.subDistrict', $shop->owner_address['subDistrict'] ?? '-') }}" class="mt-1 shadow-sm border-gray-300 rounded-md block w-full sm:text-sm px-3 py-2 border">
                    </div>
                    <div class="sm:col-span-3">
                        <label class="block text-sm font-medium text-gray-700">Kecamatan (Area)</label>
                        <input type="text" name="ownerAddress[area]" required value="{{ old('ownerAddress.area', $shop->owner_address['area'] ?? '') }}" class="mt-1 shadow-sm border-gray-300 rounded-md block w-full sm:text-sm px-3 py-2 border">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-gray-700">Kota</label>
                        <input type="text" name="ownerAddress[city]" required value="{{ old('ownerAddress.city', $shop->owner_address['city'] ?? '') }}" class="mt-1 shadow-sm border-gray-300 rounded-md block w-full sm:text-sm px-3 py-2 border">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-gray-700">Provinsi</label>
                        <input type="text" name="ownerAddress[province]" required value="{{ old('ownerAddress.province', $shop->owner_address['province'] ?? '') }}" class="mt-1 shadow-sm border-gray-300 rounded-md block w-full sm:text-sm px-3 py-2 border">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-gray-700">Kode Pos</label>
                        <input type="text" name="ownerAddress[postcode]" required value="{{ old('ownerAddress.postcode', $shop->owner_address['postcode'] ?? '') }}" class="mt-1 shadow-sm border-gray-300 rounded-md block w-full sm:text-sm px-3 py-2 border">
                    </div>
                </div>
            </div>
        </div>

        {{-- =========================================================
             CARD 5: LEGALITAS, PAJAK & DOKUMEN
             ========================================================= --}}
        <div class="bg-white overflow-hidden shadow rounded-lg border border-gray-200 mb-6">
            <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">5. Legalitas & Dokumen Bisnis</h3>
            </div>
            <div class="px-6 py-6 bg-white">
                <div class="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
                    
                    <div class="sm:col-span-3">
                        <label class="block text-sm font-medium text-gray-700">Brand Name</label>
                        <input type="text" name="brandName" required value="{{ old('brandName', $shop->brand_name) }}" class="mt-1 shadow-sm border-gray-300 rounded-md block w-full sm:text-sm px-3 py-2 border">
                    </div>
                    <div class="sm:col-span-3">
                        <label class="block text-sm font-medium text-gray-700">MCC Code</label>
                        <input type="text" name="mccCodes[]" value="0783" required class="mt-1 shadow-sm border-gray-300 rounded-md block w-full sm:text-sm px-3 py-2 border">
                    </div>

                    {{-- INFO PAJAK --}}
                    <div class="sm:col-span-6 border-t mt-2 pt-2"><h4 class="text-xs font-bold text-gray-500 uppercase">Informasi Pajak (NPWP)</h4></div>
                    
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-gray-700">Nomor NPWP</label>
                        <input type="text" name="taxNo" required value="{{ old('taxNo', $shop->tax_no) }}" class="mt-1 shadow-sm border-gray-300 rounded-md block w-full sm:text-sm px-3 py-2 border">
                    </div>
                    <div class="sm:col-span-4">
                        <label class="block text-sm font-medium text-gray-700">Alamat di NPWP</label>
                        <input type="text" name="taxAddress[address1]" required value="{{ old('taxAddress.address1', $shop->tax_address['address1'] ?? '') }}" class="mt-1 shadow-sm border-gray-300 rounded-md block w-full sm:text-sm px-3 py-2 border">
                    </div>

                    {{-- DOKUMEN IDENTITAS --}}
                    <div class="sm:col-span-6 border-t mt-4 pt-4">
                        <h4 class="text-sm font-bold text-gray-900 uppercase mb-4">Dokumen Identitas / Izin Usaha</h4>
                    </div>

                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-gray-700">Bentuk Badan Usaha</label>
                        <select name="businessEntity" id="businessEntity" onchange="adjustDocType()" class="mt-1 block w-full py-2 px-3 border border-gray-300 rounded-md shadow-sm sm:text-sm">
                            <option value="individu" {{ (old('businessEntity', $shop->business_entity ?? '') == 'individu') ? 'selected' : '' }}>Perorangan (Individu)</option>
                            <option value="pt" {{ (old('businessEntity', $shop->business_entity ?? '') == 'pt') ? 'selected' : '' }}>PT (Perseroan Terbatas)</option>
                            <option value="cv" {{ (old('businessEntity', $shop->business_entity ?? '') == 'cv') ? 'selected' : '' }}>CV</option>
                            <option value="yayasan" {{ (old('businessEntity', $shop->business_entity ?? '') == 'yayasan') ? 'selected' : '' }}>Yayasan</option>
                            <option value="usaha_dagang" {{ (old('businessEntity', $shop->business_entity ?? '') == 'usaha_dagang') ? 'selected' : '' }}>Usaha Dagang</option>
                            <option value="koperasi" {{ (old('businessEntity', $shop->business_entity ?? '') == 'koperasi') ? 'selected' : '' }}>Koperasi</option>
                        </select>
                    </div>

                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-gray-700">Tipe Dokumen</label>
                        <select name="docType" id="docType" class="mt-1 block w-full py-2 px-3 border border-gray-300 rounded-md shadow-sm sm:text-sm"></select>
                        <input type="hidden" id="oldDocType" value="{{ old('docType', $shop->owner_id_type ?? 'KTP') }}">
                    </div>

                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-gray-700">Nomor Dokumen (ID)</label>
                        <input type="text" name="docId" required value="{{ old('docId', $shop->owner_id_no) }}" placeholder="NIK / NIB" class="mt-1 shadow-sm border-gray-300 rounded-md block w-full sm:text-sm px-3 py-2 border">
                    </div>
                    
                    {{-- Hidden: ownerIdType akan diset sama dengan docType via JS --}}
                    <input type="hidden" name="ownerIdType" id="hiddenOwnerIdType" value="KTP">
                </div>
            </div>
        </div>

        {{-- =========================================================
             CARD 6: MANAJEMEN (DIRECTORS) - Required by API
             ========================================================= --}}
        <div class="bg-white overflow-hidden shadow rounded-lg border border-gray-200 mb-6">
            <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">6. Struktur Manajemen (PIC)</h3>
            </div>
            <div class="px-6 py-6 bg-white">
                <div class="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
                    {{-- Direktur --}}
                    <div class="sm:col-span-3">
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Direktur (Keuangan)</label>
                        <input type="text" name="directorPics[0][picName]" required 
                            value="{{ old('directorPics.0.picName', $shop->director_pics[0]['picName'] ?? $shop->owner_first_name) }}" 
                            class="shadow-sm border-gray-300 rounded-md block w-full sm:text-sm px-3 py-2 border" placeholder="Nama Lengkap">
                        <input type="hidden" name="directorPics[0][picPosition]" value="DIRECTOR_FINANCE">
                    </div>

                    {{-- Operasional --}}
                    <div class="sm:col-span-3">
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Manajer (Operasional)</label>
                        <input type="text" name="nonDirectorPics[0][picName]" required 
                            value="{{ old('nonDirectorPics.0.picName', $shop->non_director_pics[0]['picName'] ?? $shop->owner_first_name) }}" 
                            class="shadow-sm border-gray-300 rounded-md block w-full sm:text-sm px-3 py-2 border" placeholder="Nama Lengkap">
                        <input type="hidden" name="nonDirectorPics[0][picPosition]" value="OPERATION">
                    </div>
                </div>
            </div>
        </div>

        {{-- =========================================================
             CARD 7: UPLOAD FILE
             ========================================================= --}}
        <div class="bg-white overflow-hidden shadow rounded-lg border border-gray-200 mb-6">
            <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">7. Upload Dokumen (Jika Diubah)</h3>
            </div>
            <div class="px-6 py-6 bg-white">
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    
                    {{-- LOGO --}}
                    <div class="border rounded-md p-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Logo Toko (PNG)</label>
                        <div class="flex items-center space-x-4 mb-3">
                            @if($shop->logo_path)
                                <img src="{{ Storage::url($shop->logo_path) }}" class="h-16 w-16 object-cover rounded bg-gray-100 border" alt="Logo Saat Ini">
                                <span class="text-xs text-gray-500">Logo Tersimpan</span>
                            @else
                                <span class="text-xs text-red-500">Belum ada logo</span>
                            @endif
                        </div>
                        <input type="file" name="shop_logo" accept="image/png" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                    </div>

                    {{-- DOKUMEN --}}
                    <div class="border rounded-md p-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Dokumen Bisnis (PDF/IMG)</label>
                        <div class="flex items-center space-x-4 mb-3">
                            @if($shop->doc_path)
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    <i class="fas fa-file-check mr-1"></i> File Tersimpan
                                </span>
                            @else
                                <span class="text-xs text-red-500">Belum ada dokumen</span>
                            @endif
                        </div>
                        <input type="file" name="business_doc_file" accept="image/*,application/pdf" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-green-50 file:text-green-700 hover:file:bg-green-100">
                    </div>

                </div>
            </div>
        </div>

        {{-- TOMBOL SUBMIT --}}
        <div class="flex justify-end mb-12">
            <button type="submit" class="inline-flex justify-center py-3 px-8 border border-transparent shadow-sm text-base font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                <i class="fas fa-save mr-2 mt-1"></i> Update Data Toko
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
                {val: "PASSPORT", text: "Passport"}
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
        
        // Set hidden input value saat update
        if(docSelect.value) {
            hiddenIdType.value = docSelect.value;
        }

        docSelect.onchange = function() {
            hiddenIdType.value = this.value;
        };
    }

    document.addEventListener("DOMContentLoaded", function() {
        adjustDocType();
    });
</script>
@endsection