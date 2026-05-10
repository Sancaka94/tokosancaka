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

    /* Style Utama Kotak Select2 */
    .select2-container--default .select2-selection--single {
        height: 50px !important;
        border: 1px solid #d1d5db !important;
        border-radius: 0.5rem !important;
        display: flex !important;
        align-items: center !important;
        background-color: #fff !important;
        box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05) !important;
    }

    /* Teks Pilihan */
    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: normal !important;
        padding-left: 0.75rem !important;
        color: #374151 !important;
        font-size: 1rem !important;
    }

    /* Teks Placeholder (Warna Merah) */
    .select2-container--default .select2-selection--single .select2-selection__placeholder {
        color: #ef4444 !important; /* Merah Tailwind */
        font-weight: 600 !important;
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
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1) !important;
        overflow: hidden;
    }
    .select2-search--dropdown {
        padding: 10px !important;
        background-color: #f8fafc !important; /* Warna abu-abu super muda */
        border-bottom: 1px solid #e2e8f0 !important;
    }
    .select2-search__field {
        border: 1px solid #cbd5e1 !important;
        border-radius: 0.375rem !important;
        padding: 0.5rem 0.75rem !important;
        height: 40px !important;
        font-size: 0.875rem !important;
    }
    .select2-search__field:focus {
        outline: none !important;
        border-color: #4f46e5 !important;
        box-shadow: 0 0 0 1px #4f46e5 !important;
    }
</style>
@endsection

@section('content')
<div class="p-4 md:p-6 lg:p-8 max-w-7xl mx-auto">

    {{-- ====================================================== --}}
    {{-- ============= BLOK FLASH MESSAGE ===================== --}}
    {{-- ====================================================== --}}
    @if(session('success'))
        <div id="alert-success" class="flex items-center justify-between p-4 mb-6 text-base text-green-800 border border-green-200 rounded-lg bg-green-50 shadow-sm" role="alert">
            <div class="flex items-center">
                <i class="fa fa-check-circle w-6 h-6 mr-3 text-xl text-green-500"></i>
                <span class="font-medium">{{ session('success') }}</span>
            </div>
            <button type="button" onclick="document.getElementById('alert-success').style.display='none'" class="text-green-600 hover:text-green-800 font-bold focus:outline-none text-xl"><i class="fa fa-times"></i></button>
        </div>
    @endif

    @if($errors->any())
        <div id="alert-validation" class="flex p-4 mb-6 text-base text-red-800 border border-red-200 rounded-lg bg-red-50 shadow-sm" role="alert">
            <i class="fa fa-exclamation-triangle w-6 h-6 mr-3 mt-1 text-xl text-red-500"></i>
            <div>
                <span class="font-bold text-lg">Oops! Ada kesalahan pengisian form:</span>
                <ul class="mt-2 ml-5 list-disc list-outside text-red-700">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
            <button type="button" onclick="document.getElementById('alert-validation').style.display='none'" class="ml-auto -mx-1.5 -my-1.5 text-red-500 rounded-lg focus:ring-2 focus:ring-red-400 p-1.5 hover:bg-red-200 inline-flex h-10 w-10 text-xl"><i class="fa fa-times m-auto"></i></button>
        </div>
    @endif

    {{-- HEADER PAGE --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-8">
        <div>
            <h1 class="text-3xl md:text-4xl font-extrabold text-gray-900 tracking-tight">Edit Data Pelanggan</h1>
            <p class="mt-2 text-base text-gray-500">Perbarui informasi profil, alamat, dan keamanan dari <span class="font-bold text-gray-800">{{ $user->nama_lengkap }}</span>.</p>
        </div>
        <a href="{{ route('admin.customers.index') }}" class="mt-5 sm:mt-0 inline-flex items-center px-5 py-3 bg-white border border-gray-300 rounded-lg shadow-sm text-base font-semibold text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors">
            <i class="fa fa-arrow-left mr-2"></i> Kembali
        </a>
    </div>

    {{-- FORM UTAMA --}}
    <form action="{{ route('admin.customers.update', ['customer' => $user]) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 xl:grid-cols-12 gap-8">

            {{-- KOLOM KIRI (Informasi Dasar & Alamat) --}}
            <div class="xl:col-span-8 space-y-8">

                {{-- CARD: INFORMASI DASAR --}}
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="px-6 py-5 border-b border-gray-200 bg-gray-50">
                        <h3 class="text-xl font-bold leading-6 text-gray-900 flex items-center">
                            <i class="fa fa-user-circle text-indigo-600 mr-3 text-2xl"></i> Informasi Dasar
                        </h3>
                    </div>
                    <div class="p-6 md:p-8">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-8">

                            <div class="sm:col-span-2">
                                <label for="nama_lengkap" class="block text-base font-bold text-gray-700 mb-2">Nama Lengkap <span class="text-red-500">*</span></label>
                                <input type="text" id="nama_lengkap" name="nama_lengkap" value="{{ old('nama_lengkap', $user->nama_lengkap) }}" required
                                       class="block w-full rounded-lg border-gray-300 px-4 py-3 text-base shadow-sm focus:ring-indigo-500 focus:border-indigo-500 transition-colors @error('nama_lengkap') border-red-400 focus:border-red-500 focus:ring-red-500 bg-red-50 @enderror">
                                @error('nama_lengkap')<p class="mt-2 text-sm text-red-600 font-medium">{{ $message }}</p>@enderror
                            </div>

                            <div>
                                <label for="email" class="block text-base font-bold text-gray-700 mb-2">Alamat Email <span class="text-red-500">*</span></label>
                                <input type="email" id="email" name="email" value="{{ old('email', $user->email) }}" required
                                       class="block w-full rounded-lg border-gray-300 px-4 py-3 text-base shadow-sm focus:ring-indigo-500 focus:border-indigo-500 transition-colors @error('email') border-red-400 bg-red-50 @enderror">
                                @error('email')<p class="mt-2 text-sm text-red-600 font-medium">{{ $message }}</p>@enderror
                            </div>

                            <div>
                                <label for="no_wa" class="block text-base font-bold text-gray-700 mb-2">Nomor WhatsApp <span class="text-red-500">*</span></label>
                                <div class="relative rounded-lg shadow-sm">
                                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                        <i class="fab fa-whatsapp text-green-500 text-lg"></i>
                                    </div>
                                    <input type="text" id="no_wa" name="no_wa" value="{{ old('no_wa', $user->no_wa) }}" required
                                           class="block w-full pl-12 pr-4 py-3 rounded-lg border-gray-300 text-base focus:ring-indigo-500 focus:border-indigo-500 transition-colors @error('no_wa') border-red-400 bg-red-50 @enderror">
                                </div>
                                @error('no_wa')<p class="mt-2 text-sm text-red-600 font-medium">{{ $message }}</p>@enderror
                            </div>

                            <div>
                                <label for="store_name" class="block text-base font-bold text-gray-700 mb-2">Nama Toko (Opsional)</label>
                                <input type="text" id="store_name" name="store_name" value="{{ old('store_name', $user->store_name) }}" placeholder="Contoh: Toko Sancaka"
                                       class="block w-full rounded-lg border-gray-300 px-4 py-3 text-base shadow-sm focus:ring-indigo-500 focus:border-indigo-500 transition-colors">
                                @error('store_name')<p class="mt-2 text-sm text-red-600 font-medium">{{ $message }}</p>@enderror
                            </div>

                            <div>
                                <label for="role" class="block text-base font-bold text-gray-700 mb-2">Hak Akses (Role) <span class="text-red-500">*</span></label>
                                <select id="role" name="role" required class="block w-full rounded-lg border-gray-300 px-4 py-3 text-base shadow-sm focus:ring-indigo-500 focus:border-indigo-500 transition-colors">
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
                    <div class="px-6 py-5 border-b border-gray-200 bg-gray-50">
                        <h3 class="text-xl font-bold leading-6 text-gray-900 flex items-center">
                            <i class="fa fa-map-marker-alt text-teal-600 mr-3 text-2xl"></i> Alamat Pengiriman
                        </h3>
                    </div>
                    <div class="p-6 md:p-8">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-8">

                            <div>
                                <label for="province_id" class="block text-base font-bold text-gray-700 mb-2">Provinsi <span class="text-red-500">*</span></label>
                                <select id="province_id" name="province_id" required class="select2-searchable w-full" data-placeholder="Pilih Provinsi...">
                                    <option></option> @foreach($provinces as $province)
                                        <option value="{{ $province->id }}" {{ old('province_id', $userProvinceId ?? '') == $province->id ? 'selected' : '' }}>
                                            {{ $province->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('province_id')<p class="mt-2 text-sm text-red-600 font-medium">{{ $message }}</p>@enderror
                            </div>

                            <div>
                                <label for="regency_id" class="block text-base font-bold text-gray-700 mb-2">Kabupaten/Kota <span class="text-red-500">*</span></label>
                                <select id="regency_id" name="regency_id" required class="select2-searchable w-full" data-placeholder="Pilih Provinsi terlebih dahulu...">
                                    <option></option>
                                </select>
                                @error('regency_id')<p class="mt-2 text-sm text-red-600 font-medium">{{ $message }}</p>@enderror
                            </div>

                            <div>
                                <label for="district_id" class="block text-base font-bold text-gray-700 mb-2">Kecamatan <span class="text-red-500">*</span></label>
                                <select id="district_id" name="district_id" required class="select2-searchable w-full" data-placeholder="Pilih Kabupaten terlebih dahulu...">
                                    <option></option>
                                </select>
                                @error('district_id')<p class="mt-2 text-sm text-red-600 font-medium">{{ $message }}</p>@enderror
                            </div>

                            <div>
                                <label for="village_id" class="block text-base font-bold text-gray-700 mb-2">Desa/Kelurahan <span class="text-red-500">*</span></label>
                                <select id="village_id" name="village_id" required class="select2-searchable w-full" data-placeholder="Pilih Kecamatan terlebih dahulu...">
                                    <option></option>
                                </select>
                                @error('village_id')<p class="mt-2 text-sm text-red-600 font-medium">{{ $message }}</p>@enderror
                            </div>

                            <div class="sm:col-span-2">
                                <label for="address_detail" class="block text-base font-bold text-gray-700 mb-2">Alamat Detail (Jalan, RT/RW, Patokan)</label>
                                <textarea id="address_detail" name="address_detail" rows="4" placeholder="Contoh: Jl. Merdeka No. 123, Depan minimarket"
                                        class="block w-full rounded-lg border-gray-300 px-4 py-3 text-base shadow-sm focus:ring-teal-500 focus:border-teal-500 transition-colors">{{ old('address_detail', $user->address_detail) }}</textarea>
                                @error('address_detail')<p class="mt-2 text-sm text-red-600 font-medium">{{ $message }}</p>@enderror
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- KOLOM KANAN (Keamanan: Password & PIN) --}}
            <div class="xl:col-span-4 space-y-8">

                <div class="bg-white rounded-xl shadow-sm border border-red-200 overflow-hidden relative">
                    <div class="absolute top-0 left-0 w-2 h-full bg-red-600"></div>
                    <div class="px-6 py-5 border-b border-red-100 bg-white ml-2">
                        <h3 class="text-xl font-bold leading-6 text-gray-900 flex items-center">
                            <i class="fa fa-shield-alt text-red-600 mr-3 text-2xl"></i> Keamanan Akun
                        </h3>
                        <p class="mt-2 text-sm text-red-500 font-semibold">Kosongkan kolom di bawah jika tidak ingin diubah.</p>
                    </div>

                    <div class="p-6 md:p-8 space-y-8 bg-red-50/20 ml-2">

                        {{-- UBAH PASSWORD --}}
                        <div class="space-y-5">
                            <h4 class="text-lg font-bold text-gray-800 border-b border-gray-200 pb-3">Password Login</h4>

                            <div>
                                <label for="password" class="block text-base font-bold text-gray-700 mb-2">Password Baru</label>
                                <div class="relative rounded-lg shadow-sm">
                                    <input type="password" id="password" name="password" autocomplete="new-password" placeholder="Minimal 8 Karakter"
                                           class="block w-full px-4 py-3 pr-12 rounded-lg border-gray-300 text-base focus:ring-red-500 focus:border-red-500 transition-colors @error('password') border-red-400 bg-red-50 @enderror">
                                    <button type="button" id="togglePassword" class="absolute inset-y-0 right-0 px-4 flex items-center text-gray-400 hover:text-gray-700 focus:outline-none">
                                        <i class="fa fa-eye-slash text-lg" id="togglePasswordIcon"></i>
                                    </button>
                                </div>
                                @error('password')<p class="mt-2 text-sm text-red-600 font-medium">{{ $message }}</p>@enderror
                            </div>

                            <div>
                                <label for="password_confirmation" class="block text-base font-bold text-gray-700 mb-2">Konfirmasi Password</label>
                                <div class="relative rounded-lg shadow-sm">
                                    <input type="password" id="password_confirmation" name="password_confirmation" autocomplete="new-password" placeholder="Ulangi Password Baru"
                                           class="block w-full px-4 py-3 pr-12 rounded-lg border-gray-300 text-base focus:ring-red-500 focus:border-red-500 transition-colors">
                                    <button type="button" id="togglePasswordConfirmation" class="absolute inset-y-0 right-0 px-4 flex items-center text-gray-400 hover:text-gray-700 focus:outline-none">
                                        <i class="fa fa-eye-slash text-lg" id="togglePasswordConfirmationIcon"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        {{-- UBAH PIN TRANSAKSI --}}
                        <div class="space-y-5 pt-4">
                            <div class="flex justify-between items-center border-b border-gray-200 pb-3">
                                <h4 class="text-lg font-bold text-gray-800">PIN Transaksi</h4>
                                @if(empty($user->pin))
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-yellow-100 text-yellow-800 border border-yellow-200">
                                      Belum Diatur
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-green-100 text-green-800 border border-green-200">
                                      Terlindungi
                                    </span>
                                @endif
                            </div>

                            <div>
                                <label for="pin" class="block text-base font-bold text-gray-700 mb-2">Set PIN Baru / Reset</label>
                                <div class="relative rounded-lg shadow-sm">
                                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                        <i class="fa fa-key text-gray-400 text-lg"></i>
                                    </div>
                                    <input type="password" id="pin" name="pin" autocomplete="off" maxlength="6" pattern="\d{6}" placeholder="6 Digit Angka"
                                           class="block w-full pl-12 pr-12 py-3 rounded-lg border-gray-300 focus:ring-red-500 focus:border-red-500 text-lg font-mono tracking-[0.25em] transition-colors @error('pin') border-red-400 bg-red-50 @enderror">
                                    <button type="button" id="togglePin" class="absolute inset-y-0 right-0 px-4 flex items-center text-gray-400 hover:text-gray-700 focus:outline-none">
                                        <i class="fa fa-eye-slash text-lg" id="togglePinIcon"></i>
                                    </button>
                                </div>
                                <p class="mt-2 text-sm text-gray-500">Masukkan 6 digit angka untuk mereset paksa PIN user ini.</p>
                                @error('pin')<p class="mt-2 text-sm text-red-600 font-medium">{{ $message }}</p>@enderror
                            </div>
                        </div>

                    </div>
                </div>

            </div>
        </div>

        {{-- AREA TOMBOL SUBMIT --}}
        <div class="mt-10 bg-white border border-gray-200 rounded-xl p-6 shadow-sm flex flex-col-reverse sm:flex-row sm:justify-end gap-4">
            <a href="{{ route('admin.customers.index') }}" class="w-full sm:w-auto flex justify-center items-center px-6 py-3 border border-gray-300 rounded-lg shadow-sm text-base font-bold text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors">
                Batal
            </a>
            <button type="submit" class="w-full sm:w-auto flex justify-center items-center px-8 py-3 border border-transparent rounded-lg shadow-sm text-base font-bold text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors">
                <i class="fa fa-save mr-2 text-lg"></i> Simpan Perubahan
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
        // [Kode toggle Anda yang lama tidak perlu diubah, biarkan saja di sini]
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
