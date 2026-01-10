@extends('layouts.customer')

@section('title', 'Registrasi Merchant DANA')

@push('styles')
    {{-- Tailwind CSS (Scoped jika perlu) --}}
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Inter', 'sans-serif'] },
                    colors: {
                        primary: '#0d6efd', // Warna Biru Bootstrap
                        primaryHover: '#0b5ed7',
                    }
                }
            }
        }
    </script>
    <style>
        /* Override reset agar tidak merusak navbar bawaan layout.customer */
        .tailwind-scope h1, .tailwind-scope h2, .tailwind-scope h3 { margin: 0; }
        
        /* Custom Helper Classes agar kodingan Blade lebih bersih */
        .form-label { @apply block text-sm font-medium text-gray-700 mb-1; }
        .form-control { @apply mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary sm:text-sm px-3 py-2 border; }
        .card-custom { @apply bg-white shadow-sm rounded-lg overflow-hidden mb-6 border border-gray-100; }
        .card-header-custom { @apply bg-gray-50 px-6 py-4 border-b border-gray-200 font-bold text-gray-800 text-base; }
        .card-body-custom { @apply p-6; }
    </style>
@endpush

@section('content')
<div class="tailwind-scope max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">

    <div class="mb-8 flex flex-col md:flex-row md:justify-between md:items-center gap-4">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Registrasi Merchant DANA</h1>
            <p class="mt-1 text-sm text-gray-600">Formulir pendaftaran API <code>dana.merchant.shop.createShop</code>.</p>
        </div>
        <div>
            <a href="{{ route('customer.dashboard') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none transition">
                <i class="fas fa-arrow-left mr-2"></i> Kembali
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="bg-green-50 border-l-4 border-green-500 text-green-700 p-4 mb-6 shadow-sm rounded-r" role="alert">
            <div class="flex">
                <div class="py-1"><i class="fas fa-check-circle mr-3"></i></div>
                <div>
                    <p class="font-bold">Berhasil!</p>
                    <p class="text-sm">{{ session('success') }}</p>
                </div>
            </div>
        </div>
    @endif

    @if(session('error'))
        <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 mb-6 shadow-sm rounded-r" role="alert">
            <div class="flex">
                <div class="py-1"><i class="fas fa-exclamation-triangle mr-3"></i></div>
                <div>
                    <p class="font-bold">Gagal!</p>
                    <p class="text-sm">{{ session('error') }}</p>
                </div>
            </div>
        </div>
    @endif

    @if ($errors->any())
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded mb-6">
            <ul class="list-disc pl-5 text-sm">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('customer.merchant.store') }}" method="POST" enctype="multipart/form-data">
        @csrf

        <div class="card-custom">
            <div class="card-header-custom">1. Informasi Dasar Toko</div>
            <div class="card-body-custom">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="form-label">Merchant ID <span class="text-red-500">*</span></label>
                        <input type="text" name="merchantId" value="216622222444445555555" class="form-control bg-gray-50" readonly>
                    </div>
                    <div>
                        <label class="form-label">Parent Division ID</label>
                        <input type="text" name="parentDivisionId" value="216622222444445555555" class="form-control bg-gray-50" readonly>
                    </div>

                    <div class="md:col-span-2">
                        <label class="form-label">Nama Toko (Main Name) <span class="text-red-500">*</span></label>
                        <input type="text" name="mainName" placeholder="Contoh: Sancaka Official Store" class="form-control" required>
                    </div>

                    <div>
                        <label class="form-label">External Shop ID <span class="text-red-500">*</span></label>
                        <input type="text" name="externalShopId" placeholder="ID Toko Unik Anda" class="form-control" required>
                    </div>
                    <div>
                        <label class="form-label">Tipe Struktur</label>
                        <select name="shopParentType" class="form-control">
                            <option value="MERCHANT">MERCHANT</option>
                            <option value="DIVISION">DIVISION</option>
                        </select>
                    </div>

                    <div class="md:col-span-2">
                        <label class="form-label">Deskripsi Toko</label>
                        <textarea name="shopDesc" rows="2" class="form-control" placeholder="Deskripsi singkat toko..."></textarea>
                    </div>
                    
                    <div>
                        <label class="form-label">Ukuran Usaha (Size)</label>
                        <select name="sizeType" class="form-control">
                            <option value="UMI">UMI (Mikro)</option>
                            <option value="UKE">UKE (Kecil)</option>
                            <option value="UME">UME (Menengah)</option>
                            <option value="UBE">UBE (Besar)</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Kepemilikan</label>
                        <select name="shopOwning" class="form-control">
                            <option value="DIRECT_OWNED">Milik Sendiri</option>
                            <option value="FRANCHISED">Waralaba</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Jenis Operasional</label>
                        <select name="shopBizType" class="form-control">
                            <option value="ONLINE">ONLINE</option>
                            <option value="OFFLINE">OFFLINE</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div class="card-custom">
            <div class="card-header-custom">2. Lokasi & Alamat Toko</div>
            <div class="card-body-custom">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div>
                        <label class="form-label">Latitude <span class="text-red-500">*</span></label>
                        <input type="text" name="lat" placeholder="-6.200000" class="form-control" required>
                    </div>
                    <div>
                        <label class="form-label">Longitude <span class="text-red-500">*</span></label>
                        <input type="text" name="ln" placeholder="106.816666" class="form-control" required>
                    </div>
                </div>

                <div class="border-t border-gray-100 pt-4 grid grid-cols-1 md:grid-cols-3 gap-5">
                    <div class="md:col-span-3">
                        <label class="form-label">Alamat Lengkap 1 <span class="text-red-500">*</span></label>
                        <input type="text" name="shopAddress[address1]" class="form-control" placeholder="Nama Jalan, Nomor Gedung" required>
                    </div>
                    <div class="md:col-span-3">
                        <label class="form-label">Alamat Lengkap 2</label>
                        <input type="text" name="shopAddress[address2]" class="form-control" placeholder="RT/RW, Patokan">
                    </div>
                    <div>
                        <label class="form-label">Provinsi <span class="text-red-500">*</span></label>
                        <input type="text" name="shopAddress[province]" class="form-control" required>
                    </div>
                    <div>
                        <label class="form-label">Kota/Kabupaten <span class="text-red-500">*</span></label>
                        <input type="text" name="shopAddress[city]" class="form-control" required>
                    </div>
                    <div>
                        <label class="form-label">Kecamatan <span class="text-red-500">*</span></label>
                        <input type="text" name="shopAddress[area]" class="form-control" required>
                    </div>
                    <div>
                        <label class="form-label">Kode Pos <span class="text-red-500">*</span></label>
                        <input type="text" name="shopAddress[postcode]" maxlength="5" class="form-control" required>
                    </div>
                    <input type="hidden" name="shopAddress[country]" value="Indonesia">
                    <input type="hidden" name="shopAddress[subDistrict]" value="-">
                </div>
            </div>
        </div>

        <div class="card-custom">
            <div class="card-header-custom">3. Informasi Bisnis & PIC</div>
            <div class="card-body-custom">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="form-label">Email PIC <span class="text-red-500">*</span></label>
                        <input type="email" name="extInfo[PIC_EMAIL]" class="form-control" required>
                    </div>
                    <div>
                        <label class="form-label">No. HP PIC (628...) <span class="text-red-500">*</span></label>
                        <input type="text" name="extInfo[PIC_PHONENUMBER]" class="form-control" required>
                    </div>
                    <div>
                        <label class="form-label">Email Admin</label>
                        <input type="email" name="extInfo[SUBMITTER_EMAIL]" class="form-control" required>
                    </div>
                    <div>
                        <label class="form-label">Link Website/Sosmed</label>
                        <input type="url" name="extInfo[EXT_URLS]" class="form-control">
                    </div>

                    <div>
                        <label class="form-label">Tipe Barang</label>
                        <select name="extInfo[GOODS_SOLD_TYPE]" class="form-control">
                            <option value="DIGITAL">DIGITAL</option>
                            <option value="NON_DIGITAL">FISIK (Non-Digital)</option>
                            <option value="SERVICES">JASA (Services)</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Use Case</label>
                        <select name="extInfo[USECASE]" class="form-control">
                            <option value="QRIS_DIGITAL">QRIS DIGITAL</option>
                            <option value="QRIS_NON_DIGITAL">QRIS NON DIGITAL</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Target Customer</label>
                        <select name="extInfo[USER_PROFILING]" class="form-control">
                            <option value="B2B">B2B</option>
                            <option value="B2C">B2C (End User)</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="form-label">Rata-rata Transaksi</label>
                        <select name="extInfo[AVG_TICKET]" class="form-control">
                            <option value="<100000">&lt; 100rb</option>
                            <option value="100000-500000">100rb - 500rb</option>
                            <option value=">500000">&gt; 500rb</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Omzet Tahunan</label>
                        <select name="extInfo[OMZET]" class="form-control">
                            <option value="<2BIO">&lt; 2 Milyar</option>
                            <option value="2BIO-5BIO">2 - 5 Milyar</option>
                            <option value="5BIO-10BIO">5 - 10 Milyar</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div class="card-custom">
            <div class="card-header-custom">4. Data Pemilik Usaha</div>
            <div class="card-body-custom">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="form-label">Nama Depan <span class="text-red-500">*</span></label>
                        <input type="text" name="ownerName[firstName]" class="form-control" required>
                    </div>
                    <div>
                        <label class="form-label">Nama Belakang <span class="text-red-500">*</span></label>
                        <input type="text" name="ownerName[lastName]" class="form-control" required>
                    </div>
                    <div>
                        <label class="form-label">No. HP Pemilik <span class="text-red-500">*</span></label>
                        <input type="text" name="ownerPhoneNumber[mobileNo]" placeholder="628..." class="form-control" required>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="form-label">Tipe ID</label>
                            <select name="ownerIdType" class="form-control">
                                <option value="KTP">KTP</option>
                                <option value="SIM">SIM</option>
                                <option value="PASSPORT">Passport</option>
                            </select>
                        </div>
                        <div>
                            <label class="form-label">Nomor ID</label>
                            <input type="text" name="ownerIdNo" class="form-control" required>
                        </div>
                    </div>

                    <div class="md:col-span-2 mt-2">
                        <label class="form-label font-semibold">Alamat Domisili Pemilik</label>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <input type="text" name="ownerAddress[address1]" placeholder="Alamat Lengkap" class="form-control" required>
                            <input type="text" name="ownerAddress[city]" placeholder="Kota" class="form-control" required>
                            <input type="text" name="ownerAddress[province]" placeholder="Provinsi" class="form-control" required>
                            <input type="text" name="ownerAddress[postcode]" placeholder="Kode Pos" class="form-control" required>
                            <input type="hidden" name="ownerAddress[country]" value="Indonesia">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card-custom">
            <div class="card-header-custom">5. Legalitas & Pajak</div>
            <div class="card-body-custom">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="form-label">Badan Usaha</label>
                        <select name="businessEntity" class="form-control">
                            <option value="individu">Perorangan</option>
                            <option value="pt">PT</option>
                            <option value="cv">CV</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label">MCC Code</label>
                        <input type="text" name="mccCodes[]" value="0783" class="form-control" required>
                    </div>
                    <div>
                        <label class="form-label">Brand Name (Merk)</label>
                        <input type="text" name="brandName" class="form-control" required>
                    </div>
                    <div>
                        <label class="form-label">NPWP</label>
                        <input type="text" name="taxNo" class="form-control" required>
                    </div>
                    <div class="md:col-span-2">
                        <label class="form-label font-semibold">Alamat NPWP</label>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <input type="text" name="taxAddress[address1]" placeholder="Jalan" class="form-control" required>
                            <input type="text" name="taxAddress[city]" placeholder="Kota" class="form-control" required>
                            <input type="text" name="taxAddress[postcode]" placeholder="Kode Pos" class="form-control" required>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card-custom">
            <div class="card-header-custom">6. Struktur Manajemen</div>
            <div class="card-body-custom">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="border border-gray-200 p-4 rounded bg-gray-50">
                        <h5 class="font-bold text-gray-700 mb-3 text-sm">DIREKTUR (Finance)</h5>
                        <div class="space-y-3">
                            <input type="text" name="directorPics[0][picName]" placeholder="Nama Lengkap" class="form-control bg-white">
                            <input type="hidden" name="directorPics[0][picPosition]" value="DIRECTOR_FINANCE">
                        </div>
                    </div>
                    <div class="border border-gray-200 p-4 rounded bg-gray-50">
                        <h5 class="font-bold text-gray-700 mb-3 text-sm">OPERASIONAL</h5>
                        <div class="space-y-3">
                            <input type="text" name="nonDirectorPics[0][picName]" placeholder="Nama Lengkap" class="form-control bg-white">
                            <input type="hidden" name="nonDirectorPics[0][picPosition]" value="OPERATION">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card-custom">
            <div class="card-header-custom">7. Upload Dokumen</div>
            <div class="card-body-custom">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="border-2 border-dashed border-blue-200 rounded-lg p-6 text-center hover:bg-blue-50 transition cursor-pointer relative group">
                        <input type="file" name="shop_logo" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10" required accept="image/png">
                        <div class="space-y-2">
                            <i class="fas fa-image text-4xl text-blue-400 group-hover:text-blue-600"></i>
                            <p class="font-medium text-blue-600">Upload Logo Toko (PNG)</p>
                            <p class="text-xs text-gray-400">Max 2MB</p>
                        </div>
                    </div>

                    <div class="border-2 border-dashed border-green-200 rounded-lg p-6 text-center hover:bg-green-50 transition cursor-pointer relative group">
                        <input type="file" name="business_doc_file" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10" required accept="image/*,application/pdf">
                        <div class="space-y-2">
                            <i class="fas fa-file-contract text-4xl text-green-400 group-hover:text-green-600"></i>
                            <p class="font-medium text-green-600">Upload Dokumen Bisnis</p>
                            <p class="text-xs text-gray-400">KTP/SIUP (PDF/IMG, Max 2MB)</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex justify-end items-center gap-4 mt-8 pb-12">
            <button type="reset" class="px-6 py-3 border border-gray-300 rounded-lg text-gray-700 font-medium hover:bg-gray-100 transition">
                Reset
            </button>
            <button type="submit" class="px-8 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-bold shadow-lg hover:shadow-xl transition transform hover:-translate-y-1">
                <i class="fas fa-paper-plane mr-2"></i> Kirim Pendaftaran
            </button>
        </div>

    </form>
</div>
@endsection