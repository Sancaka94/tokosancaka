@extends('layouts.admin')

@section('title', 'Edit Data Pelanggan')

@section('styles')
{{-- CSS Khusus untuk Select2 --}}
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
{{-- Font Awesome untuk ikon password --}}
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" />
<style>
    /* Menyesuaikan Select2 dengan gaya Tailwind CSS Modern */
    .select2-container .select2-selection--single {
        height: 42px !important;
        border: 1px solid #d1d5db !important; /* gray-300 */
        border-radius: 0.5rem !important; /* rounded-lg */
        padding: 0.35rem 0.75rem !important;
        box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05) !important;
        transition: all 0.15s ease-in-out;
    }
    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 1.5rem !important;
        padding-left: 0 !important;
        color: #374151 !important; /* gray-700 */
        font-size: 0.875rem !important; /* text-sm */
    }
    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 40px !important;
        right: 0.5rem !important;
    }
    .select2-dropdown {
        border: 1px solid #e5e7eb !important; /* gray-200 */
        border-radius: 0.5rem !important;
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05) !important;
    }
    .select2-search__field {
        border-radius: 0.375rem !important;
        border: 1px solid #d1d5db !important;
    }
    .select2-search__field:focus {
        outline: none !important;
        border-color: #4f46e5 !important; /* indigo-600 */
        box-shadow: 0 0 0 1px #4f46e5 !important;
    }
    .select2-container.select2-container--focus .select2-selection {
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
        <div id="alert-success" class="flex items-center justify-between p-4 mb-6 text-sm text-green-800 border border-green-200 rounded-lg bg-green-50 shadow-sm" role="alert">
            <div class="flex items-center">
                <svg class="flex-shrink-0 inline w-5 h-5 mr-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                <span class="font-medium">{{ session('success') }}</span>
            </div>
            <button type="button" onclick="document.getElementById('alert-success').style.display='none'" class="text-green-600 hover:text-green-800 font-bold focus:outline-none"><i class="fa fa-times"></i></button>
        </div>
    @endif

    @if($errors->any())
        <div id="alert-validation" class="flex p-4 mb-6 text-sm text-red-800 border border-red-200 rounded-lg bg-red-50 shadow-sm" role="alert">
            <svg class="flex-shrink-0 inline w-5 h-5 mr-3 mt-[2px]" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
            <div>
                <span class="font-bold">Oops! Ada kesalahan pengisian form:</span>
                <ul class="mt-1.5 ml-4 list-disc list-outside">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
            <button type="button" onclick="document.getElementById('alert-validation').style.display='none'" class="ml-auto -mx-1.5 -my-1.5 text-red-500 rounded-lg focus:ring-2 focus:ring-red-400 p-1.5 hover:bg-red-200 inline-flex h-8 w-8"><i class="fa fa-times m-auto"></i></button>
        </div>
    @endif

    {{-- HEADER PAGE --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-8">
        <div>
            <h1 class="text-2xl md:text-3xl font-bold text-gray-900 tracking-tight">Edit Data Pelanggan</h1>
            <p class="mt-1 text-sm text-gray-500">Perbarui informasi profil, alamat, dan keamanan dari <span class="font-semibold text-gray-700">{{ $user->nama_lengkap }}</span>.</p>
        </div>
        <a href="{{ route('admin.customers.index') }}" class="mt-4 sm:mt-0 inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-lg shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors">
            <i class="fa fa-arrow-left mr-2"></i> Kembali
        </a>
    </div>

    {{-- FORM UTAMA --}}
    <form action="{{ route('admin.customers.update', ['customer' => $user]) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">

            {{-- KOLOM KIRI (Informasi Dasar & Alamat) --}}
            <div class="lg:col-span-8 space-y-8">

                {{-- CARD: INFORMASI DASAR --}}
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="px-6 py-5 border-b border-gray-200 bg-gray-50/50">
                        <h3 class="text-base font-semibold leading-6 text-gray-900 flex items-center">
                            <i class="fa fa-user-circle text-indigo-500 mr-2"></i> Informasi Dasar
                        </h3>
                    </div>
                    <div class="p-6">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                            <div class="sm:col-span-2">
                                <label for="nama_lengkap" class="block text-sm font-medium text-gray-700 mb-1">Nama Lengkap <span class="text-red-500">*</span></label>
                                <input type="text" id="nama_lengkap" name="nama_lengkap" value="{{ old('nama_lengkap', $user->nama_lengkap) }}" required
                                       class="block w-full rounded-lg border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm transition-colors @error('nama_lengkap') border-red-300 text-red-900 placeholder-red-300 focus:ring-red-500 focus:border-red-500 @enderror">
                                @error('nama_lengkap')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                            </div>

                            <div>
                                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Alamat Email <span class="text-red-500">*</span></label>
                                <input type="email" id="email" name="email" value="{{ old('email', $user->email) }}" required
                                       class="block w-full rounded-lg border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm transition-colors @error('email') border-red-300 @enderror">
                                @error('email')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                            </div>

                            <div>
                                <label for="no_wa" class="block text-sm font-medium text-gray-700 mb-1">Nomor WhatsApp <span class="text-red-500">*</span></label>
                                <div class="relative rounded-md shadow-sm">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <i class="fab fa-whatsapp text-green-500"></i>
                                    </div>
                                    <input type="text" id="no_wa" name="no_wa" value="{{ old('no_wa', $user->no_wa) }}" required
                                           class="block w-full pl-10 rounded-lg border-gray-300 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm transition-colors @error('no_wa') border-red-300 @enderror">
                                </div>
                                @error('no_wa')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                            </div>

                            <div>
                                <label for="store_name" class="block text-sm font-medium text-gray-700 mb-1">Nama Toko (Opsional)</label>
                                <input type="text" id="store_name" name="store_name" value="{{ old('store_name', $user->store_name) }}" placeholder="Contoh: Toko Sancaka"
                                       class="block w-full rounded-lg border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm transition-colors">
                                @error('store_name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                            </div>

                            <div>
                                <label for="role" class="block text-sm font-medium text-gray-700 mb-1">Hak Akses (Role) <span class="text-red-500">*</span></label>
                                <select id="role" name="role" required class="block w-full rounded-lg border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm transition-colors">
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
                    <div class="px-6 py-5 border-b border-gray-200 bg-gray-50/50">
                        <h3 class="text-base font-semibold leading-6 text-gray-900 flex items-center">
                            <i class="fa fa-map-marker-alt text-teal-500 mr-2"></i> Alamat Pengiriman
                        </h3>
                    </div>
                    <div class="p-6">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                            <div>
                                <label for="province_id" class="block text-sm font-medium text-gray-700 mb-1">Provinsi <span class="text-red-500">*</span></label>
                                <select id="province_id" name="province_id" required class="select2 mt-1 block w-full rounded-lg">
                                    <option value="">Pilih Provinsi...</option>
                                    @foreach($provinces as $province)
                                        <option value="{{ $province->id }}" {{ old('province_id', $userProvinceId ?? '') == $province->id ? 'selected' : '' }}>
                                            {{ $province->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('province_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                            </div>

                            <div>
                                <label for="regency_id" class="block text-sm font-medium text-gray-700 mb-1">Kabupaten/Kota <span class="text-red-500">*</span></label>
                                <select id="regency_id" name="regency_id" required class="select2 mt-1 block w-full rounded-lg">
                                    <option value="">Pilih Provinsi terlebih dahulu...</option>
                                </select>
                                @error('regency_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                            </div>

                            <div>
                                <label for="district_id" class="block text-sm font-medium text-gray-700 mb-1">Kecamatan <span class="text-red-500">*</span></label>
                                <select id="district_id" name="district_id" required class="select2 mt-1 block w-full rounded-lg">
                                    <option value="">Pilih Kabupaten/Kota terlebih dahulu...</option>
                                </select>
                                @error('district_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                            </div>

                            <div>
                                <label for="village_id" class="block text-sm font-medium text-gray-700 mb-1">Desa/Kelurahan <span class="text-red-500">*</span></label>
                                <select id="village_id" name="village_id" required class="select2 mt-1 block w-full rounded-lg">
                                    <option value="">Pilih Kecamatan terlebih dahulu...</option>
                                </select>
                                @error('village_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                            </div>

                            <div class="sm:col-span-2">
                                <label for="address_detail" class="block text-sm font-medium text-gray-700 mb-1">Alamat Detail (Jalan, RT/RW, Patokan)</label>
                                <textarea id="address_detail" name="address_detail" rows="3" placeholder="Contoh: Jl. Merdeka No. 123, Depan minimarket"
                                          class="block w-full rounded-lg border-gray-300 shadow-sm focus:ring-teal-500 focus:border-teal-500 sm:text-sm transition-colors">{{ old('address_detail', $user->address_detail) }}</textarea>
                                @error('address_detail')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- KOLOM KANAN (Keamanan: Password & PIN) --}}
            <div class="lg:col-span-4 space-y-8">

                <div class="bg-white rounded-xl shadow-sm border border-red-100 overflow-hidden relative">
                    <div class="absolute top-0 left-0 w-1 h-full bg-red-500"></div>
                    <div class="px-6 py-5 border-b border-gray-100 bg-white">
                        <h3 class="text-base font-semibold leading-6 text-gray-900 flex items-center">
                            <i class="fa fa-shield-alt text-red-500 mr-2"></i> Keamanan Akun
                        </h3>
                        <p class="mt-1 text-xs text-gray-500">Kosongkan jika tidak ingin diubah.</p>
                    </div>

                    <div class="p-6 space-y-6 bg-red-50/30">
                        {{-- UBAH PASSWORD --}}
                        <div class="space-y-4">
                            <h4 class="text-sm font-bold text-gray-700 border-b border-gray-200 pb-2">Password Login</h4>

                            <div>
                                <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password Baru</label>
                                <div class="relative rounded-md shadow-sm">
                                    <input type="password" id="password" name="password" autocomplete="new-password" placeholder="Minimal 8 Karakter"
                                           class="block w-full pr-10 rounded-lg border-gray-300 focus:ring-red-500 focus:border-red-500 sm:text-sm transition-colors @error('password') border-red-300 @enderror">
                                    <button type="button" id="togglePassword" class="absolute inset-y-0 right-0 px-3 flex items-center text-gray-400 hover:text-gray-600 focus:outline-none">
                                        <i class="fa fa-eye-slash" id="togglePasswordIcon"></i>
                                    </button>
                                </div>
                                @error('password')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                            </div>

                            <div>
                                <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">Konfirmasi Password Baru</label>
                                <div class="relative rounded-md shadow-sm">
                                    <input type="password" id="password_confirmation" name="password_confirmation" autocomplete="new-password" placeholder="Ulangi Password Baru"
                                           class="block w-full pr-10 rounded-lg border-gray-300 focus:ring-red-500 focus:border-red-500 sm:text-sm transition-colors">
                                    <button type="button" id="togglePasswordConfirmation" class="absolute inset-y-0 right-0 px-3 flex items-center text-gray-400 hover:text-gray-600 focus:outline-none">
                                        <i class="fa fa-eye-slash" id="togglePasswordConfirmationIcon"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        {{-- UBAH PIN TRANSAKSI --}}
                        <div class="space-y-4 pt-2">
                            <div class="flex justify-between items-center border-b border-gray-200 pb-2">
                                <h4 class="text-sm font-bold text-gray-700">PIN Transaksi</h4>
                                @if(empty($user->pin))
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800">
                                      Belum Diatur
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800 border border-green-200">
                                      Terlindungi
                                    </span>
                                @endif
                            </div>

                            <div>
                                <label for="pin" class="block text-sm font-medium text-gray-700 mb-1">Paksa Reset / Buat PIN Baru</label>
                                <div class="relative rounded-md shadow-sm">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <i class="fa fa-key text-gray-400"></i>
                                    </div>
                                    <input type="password" id="pin" name="pin" autocomplete="off" maxlength="6" pattern="\d{6}" placeholder="6 Digit Angka"
                                           class="block w-full pl-10 pr-10 rounded-lg border-gray-300 focus:ring-red-500 focus:border-red-500 sm:text-sm font-mono tracking-widest transition-colors @error('pin') border-red-300 @enderror">
                                    <button type="button" id="togglePin" class="absolute inset-y-0 right-0 px-3 flex items-center text-gray-400 hover:text-gray-600 focus:outline-none">
                                        <i class="fa fa-eye-slash" id="togglePinIcon"></i>
                                    </button>
                                </div>
                                <p class="mt-1.5 text-xs text-gray-500">Gunakan ini jika pelanggan lupa PIN transaksi mereka.</p>
                                @error('pin')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                            </div>
                        </div>

                    </div>
                </div>

            </div>
        </div>

        {{-- AREA TOMBOL SUBMIT --}}
        <div class="mt-8 bg-white border border-gray-200 rounded-xl p-5 shadow-sm flex flex-col-reverse sm:flex-row sm:justify-end gap-3">
            <a href="{{ route('admin.customers.index') }}" class="w-full sm:w-auto flex justify-center items-center px-5 py-2.5 border border-gray-300 rounded-lg shadow-sm text-sm font-semibold text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors">
                Batal
            </a>
            <button type="submit" class="w-full sm:w-auto flex justify-center items-center px-6 py-2.5 border border-transparent rounded-lg shadow-sm text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors">
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

    // Inisialisasi Select2
    $('.select2').select2({
        width: '100%'
    });

    // Fungsi untuk memuat data wilayah (Kabupaten, Kecamatan, Desa)
    function loadRegions(url, targetSelector, placeholder, selectedValue = null) {
        const target = $(targetSelector);
        // Kosongkan dan nonaktifkan dropdown target saat memuat
        target.html(`<option value="">${placeholder}</option>`).prop('disabled', true);

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
        loadRegions(url, '#regency_id', 'Memuat kabupaten...');
        $('#district_id').html('<option value="">Pilih Kabupaten/Kota terlebih dahulu...</option>').trigger('change');
        $('#village_id').html('<option value="">Pilih Kecamatan terlebih dahulu...</option>').trigger('change');
    });

    // Event listener untuk perubahan Kabupaten/Kota
    $('#regency_id').on('change', function() {
        const regencyId = $(this).val();
        const url = regencyId ? `/api/districts/${regencyId}` : null;
        loadRegions(url, '#district_id', 'Memuat kecamatan...');
        $('#village_id').html('<option value="">Pilih Kecamatan terlebih dahulu...</option>').trigger('change');
    });

    // Event listener untuk perubahan Kecamatan
    $('#district_id').on('change', function() {
        const districtId = $(this).val();
        const url = districtId ? `/api/villages/${districtId}` : null;
        loadRegions(url, '#village_id', 'Memuat desa/kelurahan...');
    });

    // --- Inisialisasi data alamat ---
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
        loadRegions(urlRegencies, '#regency_id', 'Memuat kabupaten...', initialRegencyId);
    }
    if (initialRegencyId) {
        const urlDistricts = `/api/districts/${initialRegencyId}`;
        loadRegions(urlDistricts, '#district_id', 'Memuat kecamatan...', initialDistrictId);
    }
    if (initialDistrictId) {
        const urlVillages = `/api/villages/${initialDistrictId}`;
        loadRegions(urlVillages, '#village_id', 'Memuat desa/kelurahan...', initialVillageId);
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
    setupPasswordToggle('togglePin', 'pin', 'togglePinIcon'); // Toggle khusus untuk PIN
});
</script>
@endpush
