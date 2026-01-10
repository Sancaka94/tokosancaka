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
                Formulir pendaftaran API <code>dana.merchant.shop.createShop</code>
            </p>
        </div>
        <div class="mt-4 flex md:mt-0 md:ml-4">
            <a href="{{ route('customer.dashboard') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
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

    @if ($errors->any())
        <div class="rounded-md bg-red-50 p-4 mb-6 border border-red-200">
            <ul class="list-disc pl-5 text-sm text-red-700">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('customer.merchant.store') }}" method="POST" enctype="multipart/form-data">
        @csrf

        {{-- CARD 1: INFORMASI DASAR --}}
        <div class="bg-white overflow-hidden shadow rounded-lg border border-gray-200 mb-6">
            {{-- Card Header ala Bootstrap --}}
            <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
                <h3 class="text-lg leading-6 font-medium text-gray-900">
                    1. Informasi Dasar Toko
                </h3>
            </div>
            {{-- Card Body --}}
            <div class="px-6 py-6 bg-white">
                <div class="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
                    
                    {{-- Form Group ala Bootstrap --}}
                    <div class="sm:col-span-3">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Merchant ID <span class="text-red-500">*</span></label>
                        <input type="text" name="merchantId" value="216622222444445555555" readonly 
                            class="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 rounded-md bg-gray-100 cursor-not-allowed px-3 py-2 border">
                    </div>

                    <div class="sm:col-span-3">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Parent Division ID</label>
                        <input type="text" name="parentDivisionId" value="216622222444445555555" readonly 
                            class="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 rounded-md bg-gray-100 cursor-not-allowed px-3 py-2 border">
                    </div>

                    <div class="sm:col-span-3">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nama Toko (Main Name) <span class="text-red-500">*</span></label>
                        <input type="text" name="mainName" placeholder="Contoh: Sancaka Store" required
                            class="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 rounded-md px-3 py-2 border">
                    </div>

                    <div class="sm:col-span-3">
                        <label class="block text-sm font-medium text-gray-700 mb-1">External Shop ID <span class="text-red-500">*</span></label>
                        <input type="text" name="externalShopId" placeholder="ID Unik Toko Anda" required
                            class="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 rounded-md px-3 py-2 border">
                    </div>

                    <div class="sm:col-span-6">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Deskripsi Toko</label>
                        <textarea name="shopDesc" rows="3" class="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 rounded-md px-3 py-2 border"></textarea>
                    </div>

                    <div class="sm:col-span-3">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tipe Struktur</label>
                        <select name="shopParentType" class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                            <option value="MERCHANT">MERCHANT</option>
                            <option value="DIVISION">DIVISION</option>
                        </select>
                    </div>

                    <div class="sm:col-span-3">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Ukuran Usaha (Size Type)</label>
                        <select name="sizeType" class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                            <option value="UMI">UMI (Mikro)</option>
                            <option value="UKE">UKE (Kecil)</option>
                            <option value="UME">UME (Menengah)</option>
                            <option value="UBE">UBE (Besar)</option>
                        </select>
                    </div>

                    <div class="sm:col-span-3">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Kepemilikan</label>
                        <select name="shopOwning" class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                            <option value="DIRECT_OWNED">Milik Sendiri</option>
                            <option value="FRANCHISED">Waralaba</option>
                        </select>
                    </div>

                    <div class="sm:col-span-3">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Jenis Operasional</label>
                        <select name="shopBizType" class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                            <option value="ONLINE">ONLINE</option>
                            <option value="OFFLINE">OFFLINE</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        {{-- CARD 2: ALAMAT --}}
        <div class="bg-white overflow-hidden shadow rounded-lg border border-gray-200 mb-6">
            <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
                <h3 class="text-lg leading-6 font-medium text-gray-900">
                    2. Lokasi & Alamat Toko
                </h3>
            </div>
            <div class="px-6 py-6 bg-white">
                <div class="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
                    
                    <div class="sm:col-span-3">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Latitude <span class="text-red-500">*</span></label>
                        <input type="text" name="lat" placeholder="-6.200000" required
                            class="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 rounded-md px-3 py-2 border">
                    </div>

                    <div class="sm:col-span-3">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Longitude <span class="text-red-500">*</span></label>
                        <input type="text" name="ln" placeholder="106.816666" required
                            class="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 rounded-md px-3 py-2 border">
                    </div>

                    <div class="sm:col-span-6 border-t border-gray-100 my-2"></div>

                    <div class="sm:col-span-6">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Alamat Lengkap 1 <span class="text-red-500">*</span></label>
                        <input type="text" name="shopAddress[address1]" placeholder="Jalan, Nomor Gedung" required
                            class="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 rounded-md px-3 py-2 border">
                    </div>

                    <div class="sm:col-span-6">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Alamat Lengkap 2</label>
                        <input type="text" name="shopAddress[address2]" placeholder="RT/RW, Patokan"
                            class="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 rounded-md px-3 py-2 border">
                    </div>

                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Provinsi <span class="text-red-500">*</span></label>
                        <input type="text" name="shopAddress[province]" required
                            class="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 rounded-md px-3 py-2 border">
                    </div>

                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Kota/Kabupaten <span class="text-red-500">*</span></label>
                        <input type="text" name="shopAddress[city]" required
                            class="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 rounded-md px-3 py-2 border">
                    </div>

                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Kecamatan <span class="text-red-500">*</span></label>
                        <input type="text" name="shopAddress[area]" required
                            class="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 rounded-md px-3 py-2 border">
                    </div>

                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Kode Pos <span class="text-red-500">*</span></label>
                        <input type="text" name="shopAddress[postcode]" maxlength="5" required
                            class="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 rounded-md px-3 py-2 border">
                    </div>
                    
                    {{-- Hidden Fields --}}
                    <input type="hidden" name="shopAddress[country]" value="Indonesia">
                    <input type="hidden" name="shopAddress[subDistrict]" value="-">
                </div>
            </div>
        </div>

        {{-- CARD 3: EXT INFO --}}
        <div class="bg-white overflow-hidden shadow rounded-lg border border-gray-200 mb-6">
            <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
                <h3 class="text-lg leading-6 font-medium text-gray-900">
                    3. Informasi Bisnis & PIC
                </h3>
            </div>
            <div class="px-6 py-6 bg-white">
                <div class="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
                    
                    <div class="sm:col-span-3">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Email PIC <span class="text-red-500">*</span></label>
                        <input type="email" name="extInfo[PIC_EMAIL]" required
                            class="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 rounded-md px-3 py-2 border">
                    </div>

                    <div class="sm:col-span-3">
                        <label class="block text-sm font-medium text-gray-700 mb-1">No. HP PIC (628...) <span class="text-red-500">*</span></label>
                        <input type="text" name="extInfo[PIC_PHONENUMBER]" required
                            class="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 rounded-md px-3 py-2 border">
                    </div>

                    <div class="sm:col-span-3">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Email Admin</label>
                        <input type="email" name="extInfo[SUBMITTER_EMAIL]" required
                            class="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 rounded-md px-3 py-2 border">
                    </div>

                    <div class="sm:col-span-3">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Link Website/Sosmed</label>
                        <input type="url" name="extInfo[EXT_URLS]"
                            class="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 rounded-md px-3 py-2 border">
                    </div>

                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tipe Barang</label>
                        <select name="extInfo[GOODS_SOLD_TYPE]" class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                            <option value="DIGITAL">DIGITAL</option>
                            <option value="NON_DIGITAL">NON DIGITAL (Fisik)</option>
                            <option value="SERVICES">SERVICES (Jasa)</option>
                        </select>
                    </div>

                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Use Case</label>
                        <select name="extInfo[USECASE]" class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                            <option value="QRIS_DIGITAL">QRIS DIGITAL</option>
                            <option value="QRIS_NON_DIGITAL">QRIS NON DIGITAL</option>
                        </select>
                    </div>

                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Target Customer</label>
                        <select name="extInfo[USER_PROFILING]" class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                            <option value="B2B">B2B</option>
                            <option value="B2C">B2C (End User)</option>
                        </select>
                    </div>

                    <div class="sm:col-span-3">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Rata-rata Transaksi</label>
                        <select name="extInfo[AVG_TICKET]" class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                            <option value="<100000">&lt; 100rb</option>
                            <option value="100000-500000">100rb - 500rb</option>
                            <option value=">500000">&gt; 500rb</option>
                        </select>
                    </div>

                    <div class="sm:col-span-3">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Omzet Tahunan</label>
                        <select name="extInfo[OMZET]" class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                            <option value="<2BIO">&lt; 2 Milyar</option>
                            <option value="2BIO-5BIO">2 - 5 Milyar</option>
                            <option value="5BIO-10BIO">5 - 10 Milyar</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        {{-- CARD 4: DATA PEMILIK --}}
        <div class="bg-white overflow-hidden shadow rounded-lg border border-gray-200 mb-6">
            <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
                <h3 class="text-lg leading-6 font-medium text-gray-900">
                    4. Data Pemilik Usaha
                </h3>
            </div>
            <div class="px-6 py-6 bg-white">
                <div class="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
                    <div class="sm:col-span-3">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nama Depan <span class="text-red-500">*</span></label>
                        <input type="text" name="ownerName[firstName]" required
                            class="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 rounded-md px-3 py-2 border">
                    </div>

                    <div class="sm:col-span-3">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nama Belakang <span class="text-red-500">*</span></label>
                        <input type="text" name="ownerName[lastName]" required
                            class="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 rounded-md px-3 py-2 border">
                    </div>

                    <div class="sm:col-span-3">
                        <label class="block text-sm font-medium text-gray-700 mb-1">No. HP Pemilik <span class="text-red-500">*</span></label>
                        <input type="text" name="ownerPhoneNumber[mobileNo]" placeholder="628..." required
                            class="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 rounded-md px-3 py-2 border">
                    </div>

                    <div class="sm:col-span-1">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tipe ID</label>
                        <select name="ownerIdType" class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                            <option value="KTP">KTP</option>
                            <option value="SIM">SIM</option>
                            <option value="PASSPORT">Passport</option>
                        </select>
                    </div>

                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nomor ID</label>
                        <input type="text" name="ownerIdNo" required
                            class="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 rounded-md px-3 py-2 border">
                    </div>

                    {{-- Alamat Owner --}}
                    <div class="sm:col-span-6 mt-2">
                        <h4 class="text-sm font-bold text-gray-900 mb-3 uppercase tracking-wide border-b pb-1">Alamat Domisili Pemilik</h4>
                    </div>

                    <div class="sm:col-span-3">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Alamat Lengkap</label>
                        <input type="text" name="ownerAddress[address1]" required
                            class="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 rounded-md px-3 py-2 border">
                    </div>
                    <div class="sm:col-span-1">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Kota</label>
                        <input type="text" name="ownerAddress[city]" required
                            class="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 rounded-md px-3 py-2 border">
                    </div>
                    <div class="sm:col-span-1">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Provinsi</label>
                        <input type="text" name="ownerAddress[province]" required
                            class="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 rounded-md px-3 py-2 border">
                    </div>
                    <div class="sm:col-span-1">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Kode Pos</label>
                        <input type="text" name="ownerAddress[postcode]" required
                            class="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 rounded-md px-3 py-2 border">
                    </div>
                    <input type="hidden" name="ownerAddress[country]" value="Indonesia">
                </div>
            </div>
        </div>

        {{-- CARD 5: LEGALITAS --}}
        <div class="bg-white overflow-hidden shadow rounded-lg border border-gray-200 mb-6">
            <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
                <h3 class="text-lg leading-6 font-medium text-gray-900">
                    5. Legalitas & Pajak
                </h3>
            </div>
            <div class="px-6 py-6 bg-white">
                <div class="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Badan Usaha</label>
                        <select name="businessEntity" class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                            <option value="individu">Perorangan</option>
                            <option value="pt">PT</option>
                            <option value="cv">CV</option>
                        </select>
                    </div>

                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">MCC Code</label>
                        <input type="text" name="mccCodes[]" value="0783" required
                            class="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 rounded-md px-3 py-2 border">
                    </div>

                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Brand Name (Merk)</label>
                        <input type="text" name="brandName" required
                            class="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 rounded-md px-3 py-2 border">
                    </div>

                    <div class="sm:col-span-3">
                        <label class="block text-sm font-medium text-gray-700 mb-1">NPWP</label>
                        <input type="text" name="taxNo" required
                            class="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 rounded-md px-3 py-2 border">
                    </div>

                    <div class="sm:col-span-3">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Alamat NPWP</label>
                        <input type="text" name="taxAddress[address1]" placeholder="Jalan" required
                            class="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 rounded-md px-3 py-2 border">
                    </div>
                </div>
            </div>
        </div>

        {{-- CARD 6: STRUKTUR MANAJEMEN --}}
        <div class="bg-white overflow-hidden shadow rounded-lg border border-gray-200 mb-6">
            <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
                <h3 class="text-lg leading-6 font-medium text-gray-900">
                    6. Struktur Manajemen
                </h3>
            </div>
            <div class="px-6 py-6 bg-white">
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <div class="border border-gray-200 rounded-md p-4 bg-gray-50">
                        <h4 class="text-sm font-bold text-gray-700 uppercase mb-3">Direktur (Finance)</h4>
                        <div class="space-y-3">
                            <div>
                                <label class="block text-xs font-medium text-gray-500 mb-1">Nama Lengkap</label>
                                <input type="text" name="directorPics[0][picName]" class="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 rounded-md px-3 py-2 border">
                            </div>
                            <input type="hidden" name="directorPics[0][picPosition]" value="DIRECTOR_FINANCE">
                        </div>
                    </div>

                    <div class="border border-gray-200 rounded-md p-4 bg-gray-50">
                        <h4 class="text-sm font-bold text-gray-700 uppercase mb-3">Operasional</h4>
                        <div class="space-y-3">
                            <div>
                                <label class="block text-xs font-medium text-gray-500 mb-1">Nama Lengkap</label>
                                <input type="text" name="nonDirectorPics[0][picName]" class="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 rounded-md px-3 py-2 border">
                            </div>
                            <input type="hidden" name="nonDirectorPics[0][picPosition]" value="OPERATION">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- CARD 7: UPLOAD DOKUMEN --}}
        <div class="bg-white overflow-hidden shadow rounded-lg border border-gray-200 mb-6">
            <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
                <h3 class="text-lg leading-6 font-medium text-gray-900">
                    7. Upload Dokumen
                </h3>
            </div>
            <div class="px-6 py-6 bg-white">
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    
                    {{-- Input File 1 --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Upload Logo Toko (PNG)</label>
                        <div class="flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-md hover:bg-gray-50 transition-colors">
                            <div class="space-y-1 text-center">
                                <i class="fas fa-image text-gray-400 text-3xl mb-2"></i>
                                <div class="flex text-sm text-gray-600 justify-center">
                                    <label for="shop_logo" class="relative cursor-pointer bg-white rounded-md font-medium text-blue-600 hover:text-blue-500 focus-within:outline-none">
                                        <span>Upload a file</span>
                                        <input id="shop_logo" name="shop_logo" type="file" class="sr-only" required accept="image/png">
                                    </label>
                                </div>
                                <p class="text-xs text-gray-500">PNG up to 2MB</p>
                            </div>
                        </div>
                    </div>

                    {{-- Input File 2 --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Upload KTP/SIUP (PDF/IMG)</label>
                        <div class="flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-md hover:bg-gray-50 transition-colors">
                            <div class="space-y-1 text-center">
                                <i class="fas fa-file-contract text-gray-400 text-3xl mb-2"></i>
                                <div class="flex text-sm text-gray-600 justify-center">
                                    <label for="business_doc_file" class="relative cursor-pointer bg-white rounded-md font-medium text-blue-600 hover:text-blue-500 focus-within:outline-none">
                                        <span>Upload a file</span>
                                        <input id="business_doc_file" name="business_doc_file" type="file" class="sr-only" required accept="image/*,application/pdf">
                                    </label>
                                </div>
                                <p class="text-xs text-gray-500">PDF/IMG up to 2MB</p>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        {{-- TOMBOL SUBMIT --}}
        <div class="flex justify-end mb-12">
            <button type="button" class="bg-white py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 mr-3">
                Batal
            </button>
            <button type="submit" class="inline-flex justify-center py-2 px-6 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                Kirim Pendaftaran
            </button>
        </div>

    </form>
</div>
@endsection