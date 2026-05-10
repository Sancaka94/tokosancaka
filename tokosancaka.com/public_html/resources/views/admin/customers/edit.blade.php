@extends('layouts.admin')

@section('title', 'Edit Data Pelanggan')

@section('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" />
<style>
    /* Reset bawaan select agar tidak bentrok */
    select.select2-hidden-accessible {
        border: 0 !important;
        clip: rect(0 0 0 0) !important;
        height: 1px !important;
        margin: -1px !important;
        overflow: hidden !important;
        padding: 0 !important;
        position: absolute !important;
        width: 1px !important;
    }

    /* Style Utama Kotak Select2 menyesuaikan desain Bootstrap 5 / Tailwind Modern */
    .select2-container--default .select2-selection--single {
        height: 42px !important; /* Disesuaikan dengan tinggi input Tailwind px-4 py-2.5 */
        border: 1px solid #d1d5db !important; /* border-gray-300 */
        border-radius: 0.5rem !important; /* rounded-lg */
        display: flex !important;
        align-items: center !important;
        background-color: #f9fafb !important; /* bg-gray-50 */
        transition: all 0.2s ease-in-out;
    }

    .select2-container--default.select2-container--open .select2-selection--single {
        border-color: #3b82f6 !important; /* border-blue-500 */
        box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.2) !important; /* ring-4 ring-blue-500/20 */
        background-color: #ffffff !important;
    }

    /* Teks Pilihan */
    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: normal !important;
        padding-left: 1rem !important; /* px-4 */
        color: #111827 !important; /* text-gray-900 */
        font-size: 0.875rem !important; /* text-sm */
    }

    /* Teks Placeholder */
    .select2-container--default .select2-selection--single .select2-selection__placeholder {
        color: #6b7280 !important; /* text-gray-500 */
        font-weight: 400 !important;
    }

    /* Posisi Panah */
    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 100% !important;
        right: 0.75rem !important;
    }

    /* Kotak Dropdown & Pencarian */
    .select2-dropdown {
        border: 1px solid #d1d5db !important;
        border-radius: 0.5rem !important;
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05) !important;
        overflow: hidden;
        margin-top: 4px;
    }
    .select2-search--dropdown {
        padding: 8px !important;
        background-color: #ffffff !important;
        border-bottom: 1px solid #e5e7eb !important;
    }
    .select2-search__field {
        border: 1px solid #d1d5db !important;
        border-radius: 0.375rem !important;
        padding: 0.5rem 0.75rem !important;
        height: 36px !important;
        font-size: 0.875rem !important;
        transition: all 0.2s ease-in-out;
    }
    .select2-search__field:focus {
        outline: none !important;
        border-color: #3b82f6 !important;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2) !important;
    }
</style>
@endsection

@section('content')
<div class="px-4 py-6 md:px-6 lg:px-8 max-w-7xl mx-auto">

    {{-- ====================================================== --}}
    {{-- ============= BLOK FLASH MESSAGE ===================== --}}
    {{-- ====================================================== --}}
    @if(session('success'))
        <div id="alert-success" class="flex items-center justify-between p-4 mb-6 text-sm text-emerald-800 border border-emerald-200 rounded-lg bg-emerald-50 shadow-sm transition-all" role="alert">
            <div class="flex items-center gap-3">
                <i class="fa fa-check-circle text-xl text-emerald-500"></i>
                <span class="font-medium">{{ session('success') }}</span>
            </div>
            <button type="button" onclick="document.getElementById('alert-success').style.display='none'" class="text-emerald-600 hover:text-emerald-900 transition-colors focus:outline-none p-1">
                <i class="fa fa-times text-lg"></i>
            </button>
        </div>
    @endif

    @if($errors->any())
        <div id="alert-validation" class="flex p-4 mb-6 text-sm text-red-800 border border-red-200 rounded-lg bg-red-50 shadow-sm" role="alert">
            <i class="fa fa-exclamation-circle text-xl text-red-500 mr-3 mt-0.5"></i>
            <div>
                <span class="font-semibold text-base">Oops! Terdapat kesalahan pada form:</span>
                <ul class="mt-2 ml-4 list-disc list-outside text-red-700 space-y-1">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
            <button type="button" onclick="document.getElementById('alert-validation').style.display='none'" class="ml-auto text-red-500 hover:text-red-700 p-1 focus:outline-none transition-colors h-fit">
                <i class="fa fa-times text-lg"></i>
            </button>
        </div>
    @endif

    {{-- HEADER PAGE --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-8 gap-4">
        <div>
            <h1 class="text-2xl md:text-3xl font-bold text-gray-900 tracking-tight">Edit Data Pelanggan</h1>
            <p class="mt-1.5 text-sm text-gray-500">
                Perbarui informasi profil, alamat, dan keamanan dari <span class="font-semibold text-gray-800">{{ $user->nama_lengkap }}</span>.
            </p>
        </div>
        <a href="{{ route('admin.customers.index') }}" class="inline-flex items-center justify-center px-4 py-2.5 bg-white border border-gray-300 rounded-lg shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 hover:text-blue-600 focus:outline-none focus:ring-4 focus:ring-gray-200 transition-all">
            <i class="fa fa-arrow-left mr-2"></i> Kembali
        </a>
    </div>

    {{-- FORM UTAMA --}}
    <form action="{{ route('admin.customers.update', ['customer' => $user]) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 lg:gap-8">

            {{-- KOLOM KIRI (Informasi Dasar & Alamat) --}}
            <div class="lg:col-span-8 space-y-6 lg:space-y-8">

                {{-- CARD: INFORMASI DASAR --}}
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200 bg-gray-50/50">
                        <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                            <div class="bg-blue-100 text-blue-600 p-2 rounded-lg mr-3">
                                <i class="fa fa-user-circle text-lg w-5 h-5 flex items-center justify-center"></i>
                            </div>
                            Informasi Dasar
                        </h3>
                    </div>
                    <div class="p-6">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">

                            <div class="sm:col-span-2">
                                <label for="nama_lengkap" class="block text-sm font-medium text-gray-900 mb-2">Nama Lengkap <span class="text-red-500">*</span></label>
                                <input type="text" id="nama_lengkap" name="nama_lengkap" value="{{ old('nama_lengkap', $user->nama_lengkap) }}" required
                                       class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-4 focus:ring-blue-500/20 focus:border-blue-500 focus:bg-white block w-full px-4 py-2.5 transition-all @error('nama_lengkap') border-red-500 focus:ring-red-500/20 focus:border-red-500 bg-red-50 @enderror">
                                @error('nama_lengkap')<p class="mt-1.5 text-sm text-red-600 font-medium">{{ $message }}</p>@enderror
                            </div>

                            <div>
                                <label for="email" class="block text-sm font-medium text-gray-900 mb-2">Alamat Email <span class="text-red-500">*</span></label>
                                <input type="email" id="email" name="email" value="{{ old('email', $user->email) }}" required
                                       class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-4 focus:ring-blue-500/20 focus:border-blue-500 focus:bg-white block w-full px-4 py-2.5 transition-all @error('email') border-red-500 bg-red-50 @enderror">
                                @error('email')<p class="mt-1.5 text-sm text-red-600 font-medium">{{ $message }}</p>@enderror
                            </div>

                            <div>
                                <label for="no_wa" class="block text-sm font-medium text-gray-900 mb-2">Nomor WhatsApp <span class="text-red-500">*</span></label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                                        <i class="fab fa-whatsapp text-emerald-500 text-lg"></i>
                                    </div>
                                    <input type="text" id="no_wa" name="no_wa" value="{{ old('no_wa', $user->no_wa) }}" required
                                           class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-4 focus:ring-blue-500/20 focus:border-blue-500 focus:bg-white block w-full pl-10 pr-4 py-2.5 transition-all @error('no_wa') border-red-500 bg-red-50 @enderror">
                                </div>
                                @error('no_wa')<p class="mt-1.5 text-sm text-red-600 font-medium">{{ $message }}</p>@enderror
                            </div>

                            <div>
                                <label for="store_name" class="block text-sm font-medium text-gray-900 mb-2">Nama Toko <span class="text-gray-400 font-normal">(Opsional)</span></label>
                                <input type="text" id="store_name" name="store_name" value="{{ old('store_name', $user->store_name) }}" placeholder="Contoh: Toko Sancaka"
                                       class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-4 focus:ring-blue-500/20 focus:border-blue-500 focus:bg-white block w-full px-4 py-2.5 transition-all">
                                @error('store_name')<p class="mt-1.5 text-sm text-red-600 font-medium">{{ $message }}</p>@enderror
                            </div>

                            <div>
                                <label for="role" class="block text-sm font-medium text-gray-900 mb-2">Hak Akses (Role) <span class="text-red-500">*</span></label>
                                <select id="role" name="role" required class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-4 focus:ring-blue-500/20 focus:border-blue-500 focus:bg-white block w-full px-4 py-2.5 transition-all">
                                    <option value="Pelanggan" {{ old('role', $user->role) == 'Pelanggan' ? 'selected' : '' }}>Pelanggan</option>
                                    <option value="Seller" {{ old('role', $user->role) == 'Seller' ? 'selected' : '' }}>Seller</option>
                                    <option value="Admin" {{ old('role', $user->role) == 'Admin' ? 'selected' : '' }}>Admin</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- CARD: ALAMAT PENGGUNA --}}
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200 bg-gray-50/50">
                        <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                            <div class="bg-teal-100 text-teal-600 p-2 rounded-lg mr-3">
                                <i class="fa fa-map-marker-alt text-lg w-5 h-5 flex items-center justify-center"></i>
                            </div>
                            Alamat Pengiriman
                        </h3>
                    </div>
                    <div class="p-6">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">

                            <div>
                                <label for="province_id" class="block text-sm font-medium text-gray-900 mb-2">Provinsi <span class="text-red-500">*</span></label>
                                <select id="province_id" name="province_id" required class="select2-searchable w-full" data-placeholder="Pilih Provinsi...">
                                    <option></option>
                                    @foreach($provinces as $province)
                                        <option value="{{ $province->id }}" {{ old('province_id', $userProvinceId ?? '') == $province->id ? 'selected' : '' }}>
                                            {{ $province->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('province_id')<p class="mt-1.5 text-sm text-red-600 font-medium">{{ $message }}</p>@enderror
                            </div>

                            <div>
                                <label for="regency_id" class="block text-sm font-medium text-gray-900 mb-2">Kabupaten/Kota <span class="text-red-500">*</span></label>
                                <select id="regency_id" name="regency_id" required class="select2-searchable w-full" data-placeholder="Pilih Provinsi terlebih dahulu...">
                                    <option></option>
                                </select>
                                @error('regency_id')<p class="mt-1.5 text-sm text-red-600 font-medium">{{ $message }}</p>@enderror
                            </div>

                            <div>
                                <label for="district_id" class="block text-sm font-medium text-gray-900 mb-2">Kecamatan <span class="text-red-500">*</span></label>
                                <select id="district_id" name="district_id" required class="select2-searchable w-full" data-placeholder="Pilih Kabupaten terlebih dahulu...">
                                    <option></option>
                                </select>
                                @error('district_id')<p class="mt-1.5 text-sm text-red-600 font-medium">{{ $message }}</p>@enderror
                            </div>

                            <div>
                                <label for="village_id" class="block text-sm font-medium text-gray-900 mb-2">Desa/Kelurahan <span class="text-red-500">*</span></label>
                                <select id="village_id" name="village_id" required class="select2-searchable w-full" data-placeholder="Pilih Kecamatan terlebih dahulu...">
                                    <option></option>
                                </select>
                                @error('village_id')<p class="mt-1.5 text-sm text-red-600 font-medium">{{ $message }}</p>@enderror
                            </div>

                            <div class="sm:col-span-2">
                                <label for="address_detail" class="block text-sm font-medium text-gray-900 mb-2">Alamat Detail <span class="text-gray-400 font-normal">(Jalan, RT/RW, Patokan)</span></label>
                                <textarea id="address_detail" name="address_detail" rows="3" placeholder="Contoh: Jl. Merdeka No. 123, Depan minimarket"
                                          class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-4 focus:ring-teal-500/20 focus:border-teal-500 focus:bg-white block w-full px-4 py-2.5 transition-all resize-y">{{ old('address_detail', $user->address_detail) }}</textarea>
                                @error('address_detail')<p class="mt-1.5 text-sm text-red-600 font-medium">{{ $message }}</p>@enderror
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- KOLOM KANAN (Keamanan: Password & PIN) --}}
            <div class="lg:col-span-4 space-y-6 lg:space-y-8">

                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200 bg-gray-50/50">
                        <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                            <div class="bg-slate-100 text-slate-700 p-2 rounded-lg mr-3">
                                <i class="fa fa-shield-alt text-lg w-5 h-5 flex items-center justify-center"></i>
                            </div>
                            Keamanan Akun
                        </h3>
                    </div>

                    <div class="p-6 space-y-8">
                        <div class="bg-blue-50 text-blue-800 text-xs font-medium px-3 py-2 rounded-md mb-4 border border-blue-100">
                            <i class="fa fa-info-circle mr-1"></i> Kosongkan form di bawah jika tidak ingin mengubah password atau PIN.
                        </div>

                        {{-- UBAH PASSWORD --}}
                        <div class="space-y-4">
                            <h4 class="text-sm font-bold text-gray-900 uppercase tracking-wider border-b border-gray-100 pb-2">Password Login</h4>

                            <div>
                                <label for="password" class="block text-sm font-medium text-gray-900 mb-2">Password Baru</label>
                                <div class="relative">
                                    <input type="password" id="password" name="password" autocomplete="new-password" placeholder="Minimal 8 Karakter"
                                           class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-4 focus:ring-slate-500/20 focus:border-slate-500 focus:bg-white block w-full px-4 py-2.5 pr-10 transition-all @error('password') border-red-500 bg-red-50 @enderror">
                                    <button type="button" id="togglePassword" class="absolute inset-y-0 right-0 px-3 flex items-center text-gray-400 hover:text-gray-600 focus:outline-none">
                                        <i class="fa fa-eye-slash" id="togglePasswordIcon"></i>
                                    </button>
                                </div>
                                @error('password')<p class="mt-1.5 text-sm text-red-600 font-medium">{{ $message }}</p>@enderror
                            </div>

                            <div>
                                <label for="password_confirmation" class="block text-sm font-medium text-gray-900 mb-2">Konfirmasi Password</label>
                                <div class="relative">
                                    <input type="password" id="password_confirmation" name="password_confirmation" autocomplete="new-password" placeholder="Ulangi Password Baru"
                                           class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-4 focus:ring-slate-500/20 focus:border-slate-500 focus:bg-white block w-full px-4 py-2.5 pr-10 transition-all">
                                    <button type="button" id="togglePasswordConfirmation" class="absolute inset-y-0 right-0 px-3 flex items-center text-gray-400 hover:text-gray-600 focus:outline-none">
                                        <i class="fa fa-eye-slash" id="togglePasswordConfirmationIcon"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        {{-- UBAH PIN TRANSAKSI --}}
                        <div class="space-y-4 pt-2">
                            <div class="flex justify-between items-center border-b border-gray-100 pb-2">
                                <h4 class="text-sm font-bold text-gray-900 uppercase tracking-wider">PIN Transaksi</h4>
                                @if(empty($user->pin))
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-yellow-100 text-yellow-800 border border-yellow-200">
                                      Belum Diatur
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-emerald-100 text-emerald-800 border border-emerald-200">
                                      <i class="fa fa-check mr-1"></i> Terlindungi
                                    </span>
                                @endif
                            </div>

                            <div>
                                <label for="pin" class="block text-sm font-medium text-gray-900 mb-2">Set PIN Baru / Reset</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <i class="fa fa-key text-gray-400"></i>
                                    </div>
                                    <input type="password" id="pin" name="pin" autocomplete="off" maxlength="6" pattern="\d{6}" placeholder="6 Digit Angka"
                                           class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-4 focus:ring-slate-500/20 focus:border-slate-500 focus:bg-white block w-full pl-9 pr-10 py-2.5 font-mono tracking-widest transition-all @error('pin') border-red-500 bg-red-50 @enderror">
                                    <button type="button" id="togglePin" class="absolute inset-y-0 right-0 px-3 flex items-center text-gray-400 hover:text-gray-600 focus:outline-none">
                                        <i class="fa fa-eye-slash" id="togglePinIcon"></i>
                                    </button>
                                </div>
                                <p class="mt-2 text-xs text-gray-500">Masukkan 6 digit angka untuk mereset paksa PIN.</p>
                                @error('pin')<p class="mt-1.5 text-sm text-red-600 font-medium">{{ $message }}</p>@enderror
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>

        {{-- AREA TOMBOL SUBMIT --}}
        <div class="mt-8 pt-6 border-t border-gray-200 flex flex-col-reverse sm:flex-row sm:justify-end gap-3">
            <a href="{{ route('admin.customers.index') }}" class="w-full sm:w-auto inline-flex justify-center items-center px-5 py-2.5 bg-white border border-gray-300 rounded-lg shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 hover:text-blue-600 focus:outline-none focus:ring-4 focus:ring-gray-200 transition-all">
                Batal
            </a>
            <button type="submit" class="w-full sm:w-auto inline-flex justify-center items-center px-6 py-2.5 bg-blue-600 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-4 focus:ring-blue-300 transition-all">
                <i class="fa fa-save mr-2"></i> Simpan Perubahan
            </button>
        </div>

    </form>
</div>
@endsection

@push('scripts')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
$(document).ready(function() {

    // Inisialisasi Select2 dengan fitur Search
    $('.select2-searchable').each(function() {
        $(this).select2({
            width: '100%',
            placeholder: $(this).data('placeholder'),
            allowClear: true // Memunculkan tombol X untuk menghapus pilihan
        });
    });

    // Fungsi untuk memuat data wilayah (Kabupaten, Kecamatan, Desa)
    function loadRegions(url, targetSelector, placeholderText, selectedValue = null) {
        const target = $(targetSelector);

        // Kosongkan opsi, tambahkan option kosong, lalu update placeholder
        target.empty().append('<option></option>');
        target.attr('data-placeholder', placeholderText);

        // Re-inisialisasi agar placeholder terupdate
        target.select2({
            width: '100%',
            placeholder: placeholderText,
            allowClear: true
        }).prop('disabled', true);

        if (!url) {
            target.prop('disabled', false);
            return;
        }

        $.ajax({
            url: url,
            type: 'GET',
            dataType: 'json',
            success: function(data) {
                target.prop('disabled', false);
                data.forEach(function(item) {
                    target.append(new Option(item.name, item.id));
                });
                if (selectedValue) {
                    target.val(selectedValue).trigger('change');
                }
            },
            error: function() {
                console.error('Gagal memuat data wilayah.');
                target.prop('disabled', false);
            }
        });
    }

    // Event listener untuk perubahan Provinsi
    $('#province_id').on('change', function() {
        const provinceId = $(this).val();
        const url = provinceId ? `/api/regencies/${provinceId}` : null;
        loadRegions(url, '#regency_id', 'Ketik nama Kabupaten/Kota...');

        // Reset bawahnya
        $('#district_id').empty().append('<option></option>').attr('data-placeholder', 'Pilih Kabupaten terlebih dahulu...').select2({placeholder: 'Pilih Kabupaten terlebih dahulu...'});
        $('#village_id').empty().append('<option></option>').attr('data-placeholder', 'Pilih Kecamatan terlebih dahulu...').select2({placeholder: 'Pilih Kecamatan terlebih dahulu...'});
    });

    // Event listener untuk perubahan Kabupaten/Kota
    $('#regency_id').on('change', function() {
        const regencyId = $(this).val();
        const url = regencyId ? `/api/districts/${regencyId}` : null;
        loadRegions(url, '#district_id', 'Ketik nama Kecamatan...');

        // Reset bawahnya
        $('#village_id').empty().append('<option></option>').attr('data-placeholder', 'Pilih Kecamatan terlebih dahulu...').select2({placeholder: 'Pilih Kecamatan terlebih dahulu...'});
    });

    // Event listener untuk perubahan Kecamatan
    $('#district_id').on('change', function() {
        const districtId = $(this).val();
        const url = districtId ? `/api/villages/${districtId}` : null;
        loadRegions(url, '#village_id', 'Ketik nama Desa/Kelurahan...');
    });

    // --- Inisialisasi data alamat (Untuk Mode Edit) ---
    const initialProvinceId = '{{ old('province_id', $userProvinceId ?? '') }}';
    const initialRegencyId = '{{ old('regency_id', $userRegencyId ?? '') }}';
    const initialDistrictId = '{{ old('district_id', $userDistrictId ?? '') }}';
    const initialVillageId = '{{ old('village_id', $userVillageId ?? '') }}';

    if (initialProvinceId) {
        if ($('#province_id').val() !== initialProvinceId) {
             $('#province_id').val(initialProvinceId);
        }
        $('#province_id').trigger('change');

        const urlRegencies = `/api/regencies/${initialProvinceId}`;
        loadRegions(urlRegencies, '#regency_id', 'Ketik nama Kabupaten/Kota...', initialRegencyId);
    }
    if (initialRegencyId) {
        const urlDistricts = `/api/districts/${initialRegencyId}`;
        loadRegions(urlDistricts, '#district_id', 'Ketik nama Kecamatan...', initialDistrictId);
    }
    if (initialDistrictId) {
        const urlVillages = `/api/villages/${initialDistrictId}`;
        loadRegions(urlVillages, '#village_id', 'Ketik nama Desa/Kelurahan...', initialVillageId);
    }

    // --- SCRIPT: Toggle Lihat Password / PIN ---
    function setupPasswordToggle(toggleBtnId, inputId, iconId) {
        const toggleBtn = $('#' + toggleBtnId);
        const passwordInput = $('#' + inputId);
        const icon = $('#' + iconId);

        toggleBtn.on('click', function() {
            const type = passwordInput.attr('type') === 'password' ? 'text' : 'password';
            passwordInput.attr('type', type);

            if (type === 'password') {
                icon.removeClass('fa-eye').addClass('fa-eye-slash');
            } else {
                icon.removeClass('fa-eye-slash').addClass('fa-eye');
            }
        });
    }

    setupPasswordToggle('togglePassword', 'password', 'togglePasswordIcon');
    setupPasswordToggle('togglePasswordConfirmation', 'password_confirmation', 'togglePasswordConfirmationIcon');
    setupPasswordToggle('togglePin', 'pin', 'togglePinIcon');
});
</script>

@endpush
