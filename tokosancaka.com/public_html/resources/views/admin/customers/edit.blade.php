@extends('layouts.admin')

@section('title', 'Edit Data Pelanggan')

@section('styles')
{{-- CSS Khusus untuk Select2 --}}
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
{{-- Font Awesome untuk ikon password --}}
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" />
<style>
    /* Menambahkan style dasar untuk Select2 agar berfungsi di layout Tailwind */
    .select2-container .select2-selection--single {
        height: 38px !important; /* Sesuaikan dengan tinggi input Tailwind */
        border: 1px solid #d1d3e2 !important;
        border-radius: 0.375rem !important; /* rounded-md */
        padding: 0.5rem 0.75rem !important; /* px-3 py-2 */
    }
    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 1.5rem !important;
        padding-left: 0 !important;
    }
    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 36px !important;
        right: 0.5rem !important;
    }
    .select2-dropdown {
        border: 1px solid #d1d3e2 !important;
        border-radius: 0.375rem !important;
    }
    .select2-container.select2-container--focus .select2-selection {
        border-color: #3b82f6 !important; /* focus:border-blue-500 */
        outline: 1px solid #3b82f6 !important;
    }
</style>
@endsection

@section('content')
{{-- Kontainer utama dengan padding --}}
<div class="p-4 md:p-6 lg:p-8">
    
     {{-- ====================================================== --}}
    {{-- ============= BLOK FLASH MESSAGE (BARU) ============ --}}
    {{-- ====================================================== --}}

    {{-- Pesan Sukses (Hijau) --}}
    @if(session('success'))
        <div id="alert-success" class="relative bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-md mb-4" role="alert">
            <span class="block sm:inline">{{ session('success') }}</span>
            <button type="button" onclick="document.getElementById('alert-success').style.display='none'" class="absolute top-0 bottom-0 right-0 px-4 py-3 text-green-700 hover:text-green-900">
                <i class="fa fa-times"></i>
            </button>
        </div>
    @endif

    {{-- Pesan Error (Merah) --}}
    @if(session('error'))
        <div id="alert-error" class="relative bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-md mb-4" role="alert">
            <span class="block sm:inline">{{ session('error') }}</span>
            <button type="button" onclick="document.getElementById('alert-error').style.display='none'" class="absolute top-0 bottom-0 right-0 px-4 py-3 text-red-700 hover:text-red-900">
                <i class="fa fa-times"></i>
            </button>
        </div>
    @endif

    {{-- Pesan Warning/Info (Kuning) --}}
    @if(session('warning') || session('info'))
        <div id="alert-warning" class="relative bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded-md mb-4" role="alert">
            <span class="block sm:inline">{{ session('warning') ?? session('info') }}</span>
            <button type="button" onclick="document.getElementById('alert-warning').style.display='none'" class="absolute top-0 bottom-0 right-0 px-4 py-3 text-yellow-700 hover:text-yellow-900">
                <i class="fa fa-times"></i>
            </button>
        </div>
    @endif

    {{-- Menampilkan error validasi "salah sintak" (Merah) --}}
    @if($errors->any())
        <div id="alert-validation" class="relative bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-md mb-4" role="alert">
            <strong class="font-bold">Oops! Ada kesalahan:</strong>
            <ul class="mt-2 list-disc list-inside">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" onclick="document.getElementById('alert-validation').style.display='none'" class="absolute top-0 bottom-0 right-0 px-4 py-3 text-red-700 hover:text-red-900">
                <i class="fa fa-times"></i>
            </button>
        </div>
    @endif
    {{-- ====================================================== --}}
    {{-- ============ AKHIR BLOK FLASH MESSAGE ============ --}}
    {{-- ====================================================== --}}
    <h1 class="text-2xl md:text-3xl font-semibold mb-4 text-gray-800">Edit Data Pelanggan: {{ $user->nama_lengkap }}</h1>

    {{-- Kontainer form seperti card, menggunakan bg, shadow, dan rounded Tailwind --}}
    <div class="bg-white shadow-lg rounded-lg mb-4 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h6 class="m-0 text-lg font-bold text-blue-600">Formulir Data Pelanggan</h6>
        </div>

        <div class="p-6">
            <form action="{{ route('admin.customers.update', ['customer' => $user]) }}" method="POST">
                @csrf
                @method('PUT')

                {{-- Layout Grid Responsif Tailwind --}}
                <div class="grid grid-cols-1 lg:grid-cols-2 lg:gap-8">

                    {{-- Kolom Kiri --}}
                    <div class="space-y-4">
                        <h5 class="text-xl font-semibold text-gray-900">Informasi Dasar</h5>
                        <hr class="mt-1 mb-3 border-t border-gray-200">

                        <div>
                            <label for="nama_lengkap" class="block text-sm font-medium text-gray-700 mb-1">Nama Lengkap</label>
                            <input type="text" id="nama_lengkap" name="nama_lengkap" value="{{ old('nama_lengkap', $user->nama_lengkap) }}" required
                                   class="mt-1 block w-full rounded-md shadow-sm sm:text-sm 
                                          @error('nama_lengkap') border-red-500 focus:border-red-500 focus:ring-red-500 @else border-gray-300 focus:border-blue-500 focus:ring-blue-500 @enderror">
                            @error('nama_lengkap')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Alamat Email</label>
                            <input type="email" id="email" name="email" value="{{ old('email', $user->email) }}" required
                                   class="mt-1 block w-full rounded-md shadow-sm sm:text-sm
                                          @error('email') border-red-500 focus:border-red-500 focus:ring-red-500 @else border-gray-300 focus:border-blue-500 focus:ring-blue-500 @enderror">
                            @error('email')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="no_wa" class="block text-sm font-medium text-gray-700 mb-1">Nomor WhatsApp</label>
                            <input type="text" id="no_wa" name="no_wa" value="{{ old('no_wa', $user->no_wa) }}" required
                                   class="mt-1 block w-full rounded-md shadow-sm sm:text-sm
                                          @error('no_wa') border-red-500 focus:border-red-500 focus:ring-red-500 @else border-gray-300 focus:border-blue-500 focus:ring-blue-500 @enderror">
                            @error('no_wa')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="store_name" class="block text-sm font-medium text-gray-700 mb-1">Nama Toko</label>
                            <input type="text" id="store_name" name="store_name" value="{{ old('store_name', $user->store_name) }}"
                                   class="mt-1 block w-full rounded-md shadow-sm sm:text-sm
                                          @error('store_name') border-red-500 focus:border-red-500 focus:ring-red-500 @else border-gray-300 focus:border-blue-500 focus:ring-blue-500 @enderror">
                            @error('store_name')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="role" class="block text-sm font-medium text-gray-700 mb-1">Role</label>
                            <select id="role" name="role" required
                                    class="mt-1 block w-full rounded-md shadow-sm sm:text-sm
                                           @error('role') border-red-500 focus:border-red-500 focus:ring-red-500 @else border-gray-300 focus:border-blue-500 focus:ring-blue-500 @enderror">
                                <option value="Pelanggan" {{ old('role', $user->role) == 'Pelanggan' ? 'selected' : '' }}>Pelanggan</option>
                                <option value="Seller" {{ old('role', $user->role) == 'Seller' ? 'selected' : '' }}>Seller</option>
                                <option value="Admin" {{ old('role', $user->role) == 'Admin' ? 'selected' : '' }}>Admin</option>
                            </select>
                            @error('role')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <h5 class="text-xl font-semibold text-gray-900 pt-4">Ubah Password (Opsional)</h5>
                        <hr class="mt-1 mb-3 border-t border-gray-200">
                        <p class="text-gray-500 text-sm">Kosongkan jika tidak ingin mengubah password.</p>

                        <div>
                            <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password Baru</label>
                            <div class="relative mt-1 rounded-md shadow-sm">
                                <input type="password" id="password" name="password"
                                       class="block w-full pr-10 sm:text-sm rounded-md 
                                              @error('password') border-red-500 focus:border-red-500 focus:ring-red-500 @else border-gray-300 focus:border-blue-500 focus:ring-blue-500 @enderror">
                                <button type="button" id="togglePassword" class="absolute inset-y-0 right-0 z-10 flex items-center px-3 text-gray-500 hover:text-gray-700 focus:outline-none">
                                    <i class="fa fa-eye-slash" id="togglePasswordIcon"></i>
                                </button>
                            </div>
                            @error('password')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">Konfirmasi Password Baru</label>
                            <div class="relative mt-1 rounded-md shadow-sm">
                                <input type="password" id="password_confirmation" name="password_confirmation"
                                       class="block w-full pr-10 sm:text-sm border-gray-300 rounded-md focus:border-blue-500 focus:ring-blue-500">
                                <button type="button" id="togglePasswordConfirmation" class="absolute inset-y-0 right-0 z-10 flex items-center px-3 text-gray-500 hover:text-gray-700 focus:outline-none">
                                    <i class="fa fa-eye-slash" id="togglePasswordConfirmationIcon"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    {{-- Kolom Kanan --}}
                    <div class="space-y-4 mt-8 lg:mt-0">
                        <h5 class="text-xl font-semibold text-gray-900">Alamat Pengguna</h5>
                        <hr class="mt-1 mb-3 border-t border-gray-200">

                        <div>
                            <label for="province_id" class="block text-sm font-medium text-gray-700 mb-1">Provinsi</label>
                            <select id="province_id" name="province_id" required
                                    class="select2 mt-1 block w-full rounded-md shadow-sm sm:text-sm
                                           @error('province_id') border-red-500 @else border-gray-300 @enderror">
                                <option value="">Pilih Provinsi...</option>
                                @foreach($provinces as $province)
                                    <option value="{{ $province->id }}" {{ old('province_id', $userProvinceId ?? '') == $province->id ? 'selected' : '' }}>
                                        {{ $province->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('province_id')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="regency_id" class="block text-sm font-medium text-gray-700 mb-1">Kabupaten/Kota</label>
                            <select id="regency_id" name="regency_id" required
                                    class="select2 mt-1 block w-full rounded-md shadow-sm sm:text-sm
                                           @error('regency_id') border-red-500 @else border-gray-300 @enderror">
                                <option value="">Pilih Provinsi terlebih dahulu...</option>
                            </select>
                            @error('regency_id')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="district_id" class="block text-sm font-medium text-gray-700 mb-1">Kecamatan</label>
                            <select id="district_id" name="district_id" required
                                    class="select2 mt-1 block w-full rounded-md shadow-sm sm:text-sm
                                           @error('district_id') border-red-500 @else border-gray-300 @enderror">
                                <option value="">Pilih Kabupaten/Kota terlebih dahulu...</option>
                            </select>
                            @error('district_id')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="village_id" class="block text-sm font-medium text-gray-700 mb-1">Desa/Kelurahan</label>
                            <select id="village_id" name="village_id" required
                                    class="select2 mt-1 block w-full rounded-md shadow-sm sm:text-sm
                                           @error('village_id') border-red-500 @else border-gray-300 @enderror">
                                <option value="">Pilih Kecamatan terlebih dahulu...</option>
                            </select>
                            @error('village_id')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="address_detail" class="block text-sm font-medium text-gray-700 mb-1">Alamat Detail</label>
                            <textarea id="address_detail" name="address_detail" rows="3"
                                      class="mt-1 block w-full rounded-md shadow-sm sm:text-sm
                                             @error('address_detail') border-red-500 focus:border-red-500 focus:ring-red-500 @else border-gray-300 focus:border-blue-500 focus:ring-blue-500 @enderror"
                            >{{ old('address_detail', $user->address_detail) }}</textarea>
                            @error('address_detail')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                </div>

                {{-- Tombol Aksi --}}
                <div class="mt-8 pt-6 border-t border-gray-200 flex items-center justify-end gap-x-4">
                    <a href="{{ route('admin.customers.index') }}"
                       class="rounded-md bg-gray-200 px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-300 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gray-400">
                        Batal
                    </a>
                    <button type="submit"
                            class="rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
$(document).ready(function() {

    // Inisialisasi Select2 (tanpa tema bootstrap)
    $('.select2').select2();

    // Fungsi untuk memuat data wilayah (Kabupaten, Kecamatan, Desa)
    function loadRegions(url, targetSelector, placeholder, selectedValue = null) {
        const target = $(targetSelector);
        // Kosongkan dan nonaktifkan dropdown target saat memuat
        target.html(`<option value="">${placeholder}</option>`).prop('disabled', true);

        if (!url) {
            target.prop('disabled', false); // Aktifkan jika tidak ada URL
            return;
        }

        $.ajax({
            url: url,
            type: 'GET',
            dataType: 'json',
            success: function(data) {
                target.prop('disabled', false);
                // Tambahkan opsi baru dari data yang diterima
                data.forEach(function(item) {
                    target.append(new Option(item.name, item.id));
                });
                // Jika ada nilai yang harus dipilih sebelumnya, pilih nilai tersebut
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
        // Reset dropdown di bawahnya
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

    // --- Inisialisasi data alamat (BERDASARKAN FIX CONTROLLER) ---
    // Variabel ini HARUS dikirim dari CustomerController@edit
    const initialProvinceId = '{{ old('province_id', $userProvinceId ?? '') }}';
    const initialRegencyId = '{{ old('regency_id', $userRegencyId ?? '') }}';
    const initialDistrictId = '{{ old('district_id', $userDistrictId ?? '') }}';
    const initialVillageId = '{{ old('village_id', $userVillageId ?? '') }}';

    // Trigger pemuatan data wilayah saat halaman dibuka
    // (JavaScript ini sudah benar dan tidak perlu diubah)
    if (initialProvinceId) {
        // Cek apakah nilai provinsi sudah diset (mungkin dari old()
        if ($('#province_id').val() !== initialProvinceId) {
             $('#province_id').val(initialProvinceId);
        }
        // Trigger change HANYA jika nilainya ada, untuk memuat kabupaten
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

    // --- SCRIPT: Toggle Lihat Password ---
    function setupPasswordToggle(toggleBtnId, inputId, iconId) {
        const toggleBtn = $('#' + toggleBtnId);
        const passwordInput = $('#' + inputId);
        const icon = $('#' + iconId);

        toggleBtn.on('click', function() {
            const type = passwordInput.attr('type') === 'password' ? 'text' : 'password';
            passwordInput.attr('type', type);

            // Ganti ikon
            if (type === 'password') {
                icon.removeClass('fa-eye').addClass('fa-eye-slash');
            } else {
                icon.removeClass('fa-eye-slash').addClass('fa-eye');
            }
        });
    }

    setupPasswordToggle('togglePassword', 'password', 'togglePasswordIcon');
    setupPasswordToggle('togglePasswordConfirmation', 'password_confirmation', 'togglePasswordConfirmationIcon');
});
</script>
@endpush