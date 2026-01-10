@extends('layouts.customer')

@section('title', 'Edit Toko DANA')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

    {{-- HEADER --}}
    <div class="md:flex md:items-center md:justify-between mb-8">
        <div class="flex-1 min-w-0">
            <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
                Update Informasi Toko
            </h2>
            <p class="mt-1 text-sm text-gray-500">
                Perbarui data toko: <strong>{{ $shop->main_name }}</strong> (ID: {{ $shop->dana_shop_id ?? 'Belum Terdaftar' }})
            </p>
        </div>
        <div class="mt-4 flex md:mt-0 md:ml-4">
            <a href="{{ route('customer.merchant.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                <i class="fas fa-arrow-left mr-2"></i> Kembali
            </a>
        </div>
    </div>

    {{-- ALERTS --}}
    @if(session('error'))
        <div class="rounded-md bg-red-50 p-4 mb-6 border border-red-200">
            <div class="flex"><i class="fas fa-times-circle text-red-400 mt-1 mr-3"></i><p class="text-sm font-medium text-red-800">{{ session('error') }}</p></div>
        </div>
    @endif

    {{-- FORM EDIT --}}
    <form action="{{ route('customer.merchant.update', $shop->id) }}" method="POST" enctype="multipart/form-data">
        @csrf
        
        {{-- CARD 1: INFORMASI DASAR --}}
        <div class="bg-white overflow-hidden shadow rounded-lg border border-gray-200 mb-6">
            <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
                <h3 class="text-lg leading-6 font-medium text-gray-900">1. Informasi Dasar</h3>
            </div>
            <div class="px-6 py-6 bg-white">
                <div class="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
                    
                    {{-- Readonly IDs --}}
                    <div class="sm:col-span-3">
                        <label class="block text-sm font-medium text-gray-500 mb-1">Merchant ID</label>
                        <input type="text" value="{{ $shop->merchant_id }}" readonly class="bg-gray-100 text-gray-500 block w-full sm:text-sm border-gray-300 rounded-md px-3 py-2 border cursor-not-allowed">
                    </div>
                    <div class="sm:col-span-3">
                        <label class="block text-sm font-medium text-gray-500 mb-1">External Shop ID</label>
                        <input type="text" value="{{ $shop->external_shop_id }}" readonly class="bg-gray-100 text-gray-500 block w-full sm:text-sm border-gray-300 rounded-md px-3 py-2 border cursor-not-allowed">
                    </div>

                    {{-- Main Name --}}
                    <div class="sm:col-span-3">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nama Toko <span class="text-red-500">*</span></label>
                        <input type="text" name="mainName" required value="{{ old('mainName', $shop->main_name) }}" class="shadow-sm border-gray-300 rounded-md block w-full sm:text-sm px-3 py-2 border focus:ring-blue-500 focus:border-blue-500">
                    </div>

                    {{-- Description --}}
                    <div class="sm:col-span-6">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Deskripsi Toko</label>
                        <textarea name="shopDesc" rows="3" class="shadow-sm border-gray-300 rounded-md block w-full sm:text-sm px-3 py-2 border focus:ring-blue-500 focus:border-blue-500">{{ old('shopDesc', $shop->shop_desc) }}</textarea>
                    </div>

                    {{-- Dropdowns --}}
                    <div class="sm:col-span-3">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tipe Struktur</label>
                        <select name="shopParentType" class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm sm:text-sm">
                            <option value="MERCHANT" {{ (old('shopParentType', $shop->shop_parent_type) == 'MERCHANT') ? 'selected' : '' }}>MERCHANT</option>
                            <option value="DIVISION" {{ (old('shopParentType', $shop->shop_parent_type) == 'DIVISION') ? 'selected' : '' }}>DIVISION</option>
                        </select>
                    </div>
                    <div class="sm:col-span-3">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Ukuran Usaha</label>
                        <select name="sizeType" class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm sm:text-sm">
                            <option value="UMI" {{ (old('sizeType', $shop->size_type) == 'UMI') ? 'selected' : '' }}>UMI (Mikro)</option>
                            <option value="UKE" {{ (old('sizeType', $shop->size_type) == 'UKE') ? 'selected' : '' }}>UKE (Kecil)</option>
                            <option value="UME" {{ (old('sizeType', $shop->size_type) == 'UME') ? 'selected' : '' }}>UME (Menengah)</option>
                        </select>
                    </div>

                    <div class="sm:col-span-3">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Kepemilikan Toko</label>
                        <select name="shopOwning" class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm sm:text-sm">
                            <option value="DIRECT_OWNED" {{ (old('shopOwning', $shop->shop_owning ?? '') == 'DIRECT_OWNED') ? 'selected' : '' }}>Milik Sendiri (Direct Owned)</option>
                            <option value="FRANCHISED" {{ (old('shopOwning', $shop->shop_owning ?? '') == 'FRANCHISED') ? 'selected' : '' }}>Waralaba (Franchised)</option>
                        </select>
                    </div>

                    <div class="sm:col-span-3">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tipe Bisnis</label>
                        <select name="shopBizType" class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm sm:text-sm">
                            <option value="ONLINE" {{ (old('shopBizType', $shop->shop_biz_type ?? '') == 'ONLINE') ? 'selected' : '' }}>Online</option>
                            <option value="OFFLINE" {{ (old('shopBizType', $shop->shop_biz_type ?? '') == 'OFFLINE') ? 'selected' : '' }}>Offline</option>
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
                    <div class="sm:col-span-3">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Latitude</label>
                        <input type="text" name="lat" required value="{{ old('lat', $shop->lat) }}" class="shadow-sm border-gray-300 rounded-md block w-full sm:text-sm px-3 py-2 border">
                    </div>
                    <div class="sm:col-span-3">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Longitude</label>
                        <input type="text" name="ln" required value="{{ old('ln', $shop->ln) }}" class="shadow-sm border-gray-300 rounded-md block w-full sm:text-sm px-3 py-2 border">
                    </div>
                    
                    {{-- Address Fields (Ambil dari array shop_address) --}}
                    <div class="sm:col-span-6 mt-2 border-t pt-2"></div>
                    
                    <div class="sm:col-span-6">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Alamat Lengkap 1</label>
                        <input type="text" name="shopAddress[address1]" required value="{{ old('shopAddress.address1', $shop->shop_address['address1'] ?? '') }}" class="shadow-sm border-gray-300 rounded-md block w-full sm:text-sm px-3 py-2 border">
                    </div>
                    <div class="sm:col-span-6">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Alamat Lengkap 2 (Patokan/RT RW)</label>
                        <input type="text" name="shopAddress[address2]" value="{{ old('shopAddress.address2', $shop->shop_address['address2'] ?? '-') }}" class="shadow-sm border-gray-300 rounded-md block w-full sm:text-sm px-3 py-2 border">
                    </div>
                    
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Provinsi</label>
                        <input type="text" name="shopAddress[province]" required value="{{ old('shopAddress.province', $shop->shop_address['province'] ?? '') }}" class="shadow-sm border-gray-300 rounded-md block w-full sm:text-sm px-3 py-2 border">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Kota/Kabupaten</label>
                        <input type="text" name="shopAddress[city]" required value="{{ old('shopAddress.city', $shop->shop_address['city'] ?? '') }}" class="shadow-sm border-gray-300 rounded-md block w-full sm:text-sm px-3 py-2 border">
                        <p class="text-xs text-gray-500 mt-1">Contoh: Kab. Ngawi / Kota Madiun</p>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Kecamatan</label>
                        <input type="text" name="shopAddress[area]" required value="{{ old('shopAddress.area', $shop->shop_address['area'] ?? '') }}" class="shadow-sm border-gray-300 rounded-md block w-full sm:text-sm px-3 py-2 border">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Kode Pos</label>
                        <input type="text" name="shopAddress[postcode]" required value="{{ old('shopAddress.postcode', $shop->shop_address['postcode'] ?? '') }}" class="shadow-sm border-gray-300 rounded-md block w-full sm:text-sm px-3 py-2 border">
                    </div>
                </div>
            </div>
        </div>

        {{-- CARD 3: EXT INFO (Bisnis) --}}
        <div class="bg-white overflow-hidden shadow rounded-lg border border-gray-200 mb-6">
            <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
                <h3 class="text-lg leading-6 font-medium text-gray-900">3. Informasi Bisnis & Profiling</h3>
            </div>
            <div class="px-6 py-6 bg-white">
                <div class="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
                    <div class="sm:col-span-3">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Email PIC</label>
                        <input type="email" name="extInfo[PIC_EMAIL]" required value="{{ old('extInfo.PIC_EMAIL', $shop->ext_info['PIC_EMAIL'] ?? '') }}" class="shadow-sm border-gray-300 rounded-md block w-full sm:text-sm px-3 py-2 border">
                    </div>
                    <div class="sm:col-span-3">
                        <label class="block text-sm font-medium text-gray-700 mb-1">No. HP PIC (62...)</label>
                        <input type="text" name="extInfo[PIC_PHONENUMBER]" required value="{{ old('extInfo.PIC_PHONENUMBER', $shop->ext_info['PIC_PHONENUMBER'] ?? '') }}" class="shadow-sm border-gray-300 rounded-md block w-full sm:text-sm px-3 py-2 border">
                    </div>
                    <div class="sm:col-span-3">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Email Admin</label>
                        <input type="email" name="extInfo[SUBMITTER_EMAIL]" required value="{{ old('extInfo.SUBMITTER_EMAIL', $shop->ext_info['SUBMITTER_EMAIL'] ?? '') }}" class="shadow-sm border-gray-300 rounded-md block w-full sm:text-sm px-3 py-2 border">
                    </div>
                    <div class="sm:col-span-3">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Link URL</label>
                        <input type="url" name="extInfo[EXT_URLS]" value="{{ old('extInfo.EXT_URLS', $shop->ext_info['EXT_URLS'] ?? '') }}" class="shadow-sm border-gray-300 rounded-md block w-full sm:text-sm px-3 py-2 border">
                    </div>

                    {{-- Dropdown Bisnis --}}
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tipe Barang</label>
                        <select name="extInfo[GOODS_SOLD_TYPE]" class="mt-1 block w-full py-2 px-3 border border-gray-300 rounded-md shadow-sm sm:text-sm">
                            @php $val = old('extInfo.GOODS_SOLD_TYPE', $shop->ext_info['GOODS_SOLD_TYPE'] ?? ''); @endphp
                            <option value="DIGITAL" {{ $val == 'DIGITAL' ? 'selected' : '' }}>DIGITAL</option>
                            <option value="NON_DIGITAL" {{ $val == 'NON_DIGITAL' ? 'selected' : '' }}>NON DIGITAL</option>
                        </select>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Use Case</label>
                        <select name="extInfo[USECASE]" class="mt-1 block w-full py-2 px-3 border border-gray-300 rounded-md shadow-sm sm:text-sm">
                            @php $val = old('extInfo.USECASE', $shop->ext_info['USECASE'] ?? ''); @endphp
                            <option value="QRIS_DIGITAL" {{ $val == 'QRIS_DIGITAL' ? 'selected' : '' }}>QRIS DIGITAL</option>
                            <option value="QRIS_NON_DIGITAL" {{ $val == 'QRIS_NON_DIGITAL' ? 'selected' : '' }}>QRIS NON DIGITAL</option>
                        </select>
                    </div>
                    
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Target Pasar (Profiling)</label>
                        <select name="extInfo[USER_PROFILING]" class="mt-1 block w-full py-2 px-3 border border-gray-300 rounded-md shadow-sm sm:text-sm">
                            @php $val = old('extInfo.USER_PROFILING', $shop->ext_info['USER_PROFILING'] ?? ''); @endphp
                            <option value="B2C" {{ $val == 'B2C' ? 'selected' : '' }}>B2C</option>
                            <option value="B2B" {{ $val == 'B2B' ? 'selected' : '' }}>B2B</option>
                        </select>
                    </div>
                    <div class="sm:col-span-3">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Rata-rata Transaksi</label>
                        <select name="extInfo[AVG_TICKET]" class="mt-1 block w-full py-2 px-3 border border-gray-300 rounded-md shadow-sm sm:text-sm">
                            @php $val = old('extInfo.AVG_TICKET', $shop->ext_info['AVG_TICKET'] ?? ''); @endphp
                            <option value="10000-50000" {{ $val == '10000-50000' ? 'selected' : '' }}>10rb - 50rb</option>
                            <option value="50000-100000" {{ $val == '50000-100000' ? 'selected' : '' }}>50rb - 100rb</option>
                            <option value="100000-500000" {{ $val == '100000-500000' ? 'selected' : '' }}>100rb - 500rb</option>
                        </select>
                    </div>
                    <div class="sm:col-span-3">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Omzet Per Tahun</label>
                        <select name="extInfo[OMZET]" class="mt-1 block w-full py-2 px-3 border border-gray-300 rounded-md shadow-sm sm:text-sm">
                            @php $val = old('extInfo.OMZET', $shop->ext_info['OMZET'] ?? ''); @endphp
                            <option value="<100JT" {{ $val == '<100JT' ? 'selected' : '' }}>< 100jt</option>
                            <option value="100JT-500JT" {{ $val == '100JT-500JT' ? 'selected' : '' }}>100jt - 500jt</option>
                            <option value="500JT-2M" {{ $val == '500JT-2M' ? 'selected' : '' }}>500jt - 2 Milyar</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        {{-- CARD 4: OWNER --}}
        <div class="bg-white overflow-hidden shadow rounded-lg border border-gray-200 mb-6">
            <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
                <h3 class="text-lg leading-6 font-medium text-gray-900">4. Data Pemilik</h3>
            </div>
            <div class="px-6 py-6 bg-white">
                <div class="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
                    <div class="sm:col-span-3">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nama Depan</label>
                        <input type="text" name="ownerName[firstName]" required value="{{ old('ownerName.firstName', $shop->owner_first_name) }}" class="shadow-sm border-gray-300 rounded-md block w-full sm:text-sm px-3 py-2 border">
                    </div>
                    <div class="sm:col-span-3">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nama Belakang</label>
                        <input type="text" name="ownerName[lastName]" required value="{{ old('ownerName.lastName', $shop->owner_last_name) }}" class="shadow-sm border-gray-300 rounded-md block w-full sm:text-sm px-3 py-2 border">
                    </div>
                    <div class="sm:col-span-3">
                        <label class="block text-sm font-medium text-gray-700 mb-1">No. HP Pemilik</label>
                        <input type="text" name="ownerPhoneNumber[mobileNo]" required value="{{ old('ownerPhoneNumber.mobileNo', $shop->owner_phone) }}" class="shadow-sm border-gray-300 rounded-md block w-full sm:text-sm px-3 py-2 border">
                    </div>
                    <div class="sm:col-span-3">
                        <label class="block text-sm font-medium text-gray-700 mb-1">ID (KTP)</label>
                        <div class="flex">
                            <span class="inline-flex items-center px-3 rounded-l-md border border-r-0 border-gray-300 bg-gray-50 text-gray-500 sm:text-sm">KTP</span>
                            <input type="text" name="ownerIdNo" required value="{{ old('ownerIdNo', $shop->owner_id_no) }}" class="flex-1 min-w-0 block w-full px-3 py-2 rounded-none rounded-r-md border border-gray-300 sm:text-sm">
                            <input type="hidden" name="ownerIdType" value="KTP">
                        </div>
                    </div>

                    {{-- Alamat Owner --}}
                    <div class="sm:col-span-6 mt-2 border-t pt-2"></div>
                    <div class="sm:col-span-3">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Alamat Owner</label>
                        <input type="text" name="ownerAddress[address1]" required value="{{ old('ownerAddress.address1', $shop->owner_address['address1'] ?? '') }}" class="shadow-sm border-gray-300 rounded-md block w-full sm:text-sm px-3 py-2 border">
                    </div>
                    <div class="sm:col-span-3">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Kecamatan Owner</label>
                        <input type="text" name="ownerAddress[area]" required value="{{ old('ownerAddress.area', $shop->owner_address['area'] ?? '') }}" class="shadow-sm border-gray-300 rounded-md block w-full sm:text-sm px-3 py-2 border">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Kota</label>
                        <input type="text" name="ownerAddress[city]" required value="{{ old('ownerAddress.city', $shop->owner_address['city'] ?? '') }}" class="shadow-sm border-gray-300 rounded-md block w-full sm:text-sm px-3 py-2 border">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Provinsi</label>
                        <input type="text" name="ownerAddress[province]" required value="{{ old('ownerAddress.province', $shop->owner_address['province'] ?? '') }}" class="shadow-sm border-gray-300 rounded-md block w-full sm:text-sm px-3 py-2 border">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Kode Pos</label>
                        <input type="text" name="ownerAddress[postcode]" required value="{{ old('ownerAddress.postcode', $shop->owner_address['postcode'] ?? '') }}" class="shadow-sm border-gray-300 rounded-md block w-full sm:text-sm px-3 py-2 border">
                    </div>
                </div>
            </div>
        </div>

        {{-- CARD 5: LEGALITAS & DOKUMEN BISNIS --}}
        <div class="bg-white overflow-hidden shadow rounded-lg border border-gray-200 mb-6">
            <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
                <h3 class="text-lg leading-6 font-medium text-gray-900">5. Legalitas & Dokumen Bisnis</h3>
            </div>
            <div class="px-6 py-6 bg-white">
                <div class="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
                    
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Brand Name</label>
                        <input type="text" name="brandName" required value="{{ old('brandName', $shop->brand_name) }}" class="shadow-sm border-gray-300 rounded-md block w-full sm:text-sm px-3 py-2 border">
                    </div>

                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">MCC Code</label>
                        <input type="text" name="mccCodes[]" value="0783" required class="shadow-sm border-gray-300 rounded-md block w-full sm:text-sm px-3 py-2 border">
                    </div>
                    
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">NPWP (Tax No)</label>
                        <input type="text" name="taxNo" required value="{{ old('taxNo', $shop->tax_no) }}" class="shadow-sm border-gray-300 rounded-md block w-full sm:text-sm px-3 py-2 border">
                    </div>

                    {{-- ALAMAT NPWP --}}
                    <div class="sm:col-span-6">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Alamat NPWP</label>
                        <input type="text" name="taxAddress[address1]" required value="{{ old('taxAddress.address1', $shop->tax_address['address1'] ?? '') }}" class="shadow-sm border-gray-300 rounded-md block w-full sm:text-sm px-3 py-2 border">
                    </div>

                    <div class="sm:col-span-6 border-t border-gray-100 my-2 pt-4">
                        <h4 class="text-sm font-bold text-gray-900 uppercase mb-4">Detail Dokumen Bisnis</h4>
                    </div>

                    {{-- BADAN USAHA (Pemicu Perubahan Dropdown) --}}
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Badan Usaha</label>
                        <select name="businessEntity" id="businessEntity" onchange="adjustDocType()" class="mt-1 block w-full py-2 px-3 border border-gray-300 rounded-md shadow-sm sm:text-sm">
                            <option value="individu" {{ (old('businessEntity', $shop->business_entity ?? '') == 'individu') ? 'selected' : '' }}>Perorangan (Individu)</option>
                            <option value="pt" {{ (old('businessEntity', $shop->business_entity ?? '') == 'pt') ? 'selected' : '' }}>PT (Perseroan Terbatas)</option>
                            <option value="cv" {{ (old('businessEntity', $shop->business_entity ?? '') == 'cv') ? 'selected' : '' }}>CV</option>
                            <option value="yayasan" {{ (old('businessEntity', $shop->business_entity ?? '') == 'yayasan') ? 'selected' : '' }}>Yayasan</option>
                        </select>
                    </div>

                    {{-- TIPE DOKUMEN (Isinya berubah via JS) --}}
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tipe Dokumen</label>
                        <select name="docType" id="docType" class="mt-1 block w-full py-2 px-3 border border-gray-300 rounded-md shadow-sm sm:text-sm">
                            {{-- Option akan diisi oleh Javascript --}}
                        </select>
                        {{-- Hidden Input untuk menyimpan value lama saat validasi gagal --}}
                        <input type="hidden" id="oldDocType" value="{{ old('docType', 'KTP') }}">
                    </div>

                    {{-- NOMOR DOKUMEN --}}
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nomor Dokumen (docId)</label>
                        <input type="text" name="docId" required 
                            value="{{ old('docId', $shop->owner_id_no ?? '') }}" 
                            placeholder="NIK / NIB / No. SIUP"
                            class="shadow-sm border-gray-300 rounded-md block w-full sm:text-sm px-3 py-2 border">
                        <p class="text-xs text-gray-500 mt-1">KTP (16 digit), NIB (13 digit)</p>
                    </div>

                </div>
            </div>
        </div>

        {{-- CARD 6: DOKUMEN (PREVIEW) --}}
        <div class="bg-white overflow-hidden shadow rounded-lg border border-gray-200 mb-6">
            <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
                <h3 class="text-lg leading-6 font-medium text-gray-900">6. Dokumen (Upload Jika Ingin Ganti)</h3>
            </div>
            <div class="px-6 py-6 bg-white">
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Logo Toko</label>
                        <div class="flex items-center space-x-4 mb-3">
                            @if($shop->logo_path)
                                <img src="{{ Storage::url($shop->logo_path) }}" class="h-16 w-16 object-cover rounded-lg border border-gray-300" alt="Logo">
                                <span class="text-xs text-gray-500">Logo Saat Ini</span>
                            @else
                                <span class="text-xs text-gray-400">Belum ada logo</span>
                            @endif
                        </div>
                        <input type="file" name="shop_logo" accept="image/png" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                        <p class="text-xs text-gray-500 mt-1">*Upload ulang hanya jika ingin mengganti.</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Dokumen KTP</label>
                        <div class="flex items-center space-x-4 mb-3">
                            @if($shop->doc_path)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    <i class="fas fa-file-check mr-1"></i> Dokumen Tersimpan
                                </span>
                            @else
                                <span class="text-xs text-gray-400">Belum ada dokumen</span>
                            @endif
                        </div>
                        <input type="file" name="business_doc_file" accept="image/*,application/pdf" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-green-50 file:text-green-700 hover:file:bg-green-100">
                        <p class="text-xs text-gray-500 mt-1">*Upload ulang hanya jika ingin mengganti.</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- TOMBOL UPDATE --}}
        <div class="flex justify-end mb-12">
            <button type="submit" class="inline-flex justify-center py-2 px-6 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                <i class="fas fa-save mr-2"></i> Update Data Toko
            </button>
        </div>

    </form>
</div>

{{-- SCRIPT PENGATUR LOGIKA DOKUMEN --}}
<script>
    function adjustDocType() {
        var entity = document.getElementById("businessEntity").value;
        var docSelect = document.getElementById("docType");
        var oldVal = document.getElementById("oldDocType").value;
        
        // Kosongkan opsi saat ini
        docSelect.innerHTML = "";

        var options = [];

        if (entity === "individu") {
            // Jika Individu: KTP, SIM, PASSPORT
            options = [
                {val: "KTP", text: "KTP (Kartu Tanda Penduduk)"},
                {val: "SIM", text: "SIM (Surat Izin Mengemudi)"},
                {val: "PASSPORT", text: "Passport"}
            ];
        } else {
            // Jika Badan Usaha Lain: NIB, SIUP
            options = [
                {val: "NIB", text: "NIB (Nomor Induk Berusaha)"},
                {val: "SIUP", text: "SIUP (Surat Izin Usaha Perdagangan)"}
            ];
        }

        // Masukkan opsi ke dropdown
        options.forEach(function(opt) {
            var option = document.createElement("option");
            option.value = opt.val;
            option.text = opt.text;
            // Pilih kembali value lama jika cocok
            if(opt.val === oldVal) {
                option.selected = true;
            }
            docSelect.appendChild(option);
        });
    }

    // Jalankan saat halaman dimuat pertama kali
    document.addEventListener("DOMContentLoaded", function() {
        adjustDocType();
    });
</script>

@endsection