@extends('layouts.admin')

@section('title', 'Edit Data Pelanggan')

@section('styles')
{{-- CSS Khusus untuk Select2 --}}
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
    /* Penyesuaian style agar Select2 cocok dengan tema Bootstrap */
    .select2-container .select2-selection--single {
        height: calc(1.5em + .75rem + 2px);
        padding: .375rem .75rem;
        border: 1px solid #d1d3e2;
        border-radius: .35rem;
    }
    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 1.5;
        padding-left: 0;
    }
    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: calc(1.5em + .75rem);
        right: 0.5rem;
    }
    .select2-container--default .select2-dropdown {
        border: 1px solid #d1d3e2;
    }
</style>
@endsection

@section('content')
<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">Edit Data Pelanggan: {{ $user->nama_lengkap }}</h1>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Formulir Data Pelanggan</h6>
        </div>
        <div class="card-body">
            {{-- PERBAIKAN: Secara eksplisit memetakan parameter 'customer' dengan variabel '$user' --}}
            <form action="{{ route('admin.customers.update', ['customer' => $user]) }}" method="POST">
                @csrf
                @method('PUT') {{-- Gunakan method PUT untuk proses update --}}

                <div class="row">
                    {{-- Kolom Kiri: Informasi Dasar & Password --}}
                    <div class="col-md-6">
                        <h5>Informasi Dasar</h5>
                        <hr class="mt-1 mb-3">
                        <div class="mb-3">
                            <label for="nama_lengkap" class="form-label">Nama Lengkap</label>
                            <input type="text" class="form-control @error('nama_lengkap') is-invalid @enderror" id="nama_lengkap" name="nama_lengkap" value="{{ old('nama_lengkap', $user->nama_lengkap) }}" required>
                            @error('nama_lengkap')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">Alamat Email</label>
                            <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email', $user->email) }}" required>
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="no_wa" class="form-label">Nomor WhatsApp</label>
                            <input type="text" class="form-control @error('no_wa') is-invalid @enderror" id="no_wa" name="no_wa" value="{{ old('no_wa', $user->no_wa) }}" required>
                            @error('no_wa')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="store_name" class="form-label">Nama Toko</label>
                            <input type="text" class="form-control @error('store_name') is-invalid @enderror" id="store_name" name="store_name" value="{{ old('store_name', $user->store_name) }}">
                            @error('store_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="role" class="form-label">Role</label>
                            <select class="form-select @error('role') is-invalid @enderror" id="role" name="role" required>
                                <option value="Pelanggan" {{ old('role', $user->role) == 'Pelanggan' ? 'selected' : '' }}>Pelanggan</option>
                                <option value="Admin" {{ old('role', $user->role) == 'Admin' ? 'selected' : '' }}>Admin</option>
                            </select>
                            @error('role')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <h5 class="mt-4">Ubah Password (Opsional)</h5>
                        <hr class="mt-1 mb-3">
                        <p class="text-muted small">Kosongkan jika tidak ingin mengubah password.</p>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password Baru</label>
                            <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password">
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label for="password_confirmation" class="form-label">Konfirmasi Password Baru</label>
                            <input type="password" class="form-control" id="password_confirmation" name="password_confirmation">
                        </div>
                    </div>

                    {{-- Kolom Kanan: Alamat --}}
                    <div class="col-md-6">
                        <h5>Alamat Pengguna</h5>
                        <hr class="mt-1 mb-3">
                        <div class="mb-3">
                            <label for="province_id" class="form-label">Provinsi</label>
                            <select class="form-select select2 @error('province_id') is-invalid @enderror" id="province_id" name="province_id" required>
                                <option value="">Pilih Provinsi...</option>
                                @foreach($provinces as $province)
                                    <option value="{{ $province->id }}" {{ old('province_id', $user->province_id) == $province->id ? 'selected' : '' }}>{{ $province->name }}</option>
                                @endforeach
                            </select>
                            @error('province_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label for="regency_id" class="form-label">Kabupaten/Kota</label>
                            <select class="form-select select2 @error('regency_id') is-invalid @enderror" id="regency_id" name="regency_id" required>
                                <option value="">Pilih Provinsi terlebih dahulu...</option>
                            </select>
                             @error('regency_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label for="district_id" class="form-label">Kecamatan</label>
                            <select class="form-select select2 @error('district_id') is-invalid @enderror" id="district_id" name="district_id" required>
                                <option value="">Pilih Kabupaten/Kota terlebih dahulu...</option>
                            </select>
                            @error('district_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label for="village_id" class="form-label">Desa/Kelurahan</label>
                            <select class="form-select select2 @error('village_id') is-invalid @enderror" id="village_id" name="village_id" required>
                                <option value="">Pilih Kecamatan terlebih dahulu...</option>
                            </select>
                            @error('village_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label for="address_detail" class="form-label">Alamat Detail</label>
                            <textarea class="form-control @error('address_detail') is-invalid @enderror" id="address_detail" name="address_detail" rows="3">{{ old('address_detail', $user->address_detail) }}</textarea>
                            @error('address_detail')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="mt-4">
                    <a href="{{ route('admin.customers.index') }}" class="btn btn-secondary">Batal</a>
                    <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
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
    // Inisialisasi Select2
    $('.select2').select2({
        theme: "bootstrap-5" // Menggunakan tema yang lebih modern
    });

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
        $('#district_id').html('<option value="">Pilih Kabupaten/Kota terlebih dahulu...</option>');
        $('#village_id').html('<option value="">Pilih Kecamatan terlebih dahulu...</option>');
    });

    // Event listener untuk perubahan Kabupaten/Kota
    $('#regency_id').on('change', function() {
        const regencyId = $(this).val();
        const url = regencyId ? `/api/districts/${regencyId}` : null;
        loadRegions(url, '#district_id', 'Memuat kecamatan...');
        $('#village_id').html('<option value="">Pilih Kecamatan terlebih dahulu...</option>');
    });

    // Event listener untuk perubahan Kecamatan
    $('#district_id').on('change', function() {
        const districtId = $(this).val();
        const url = districtId ? `/api/villages/${districtId}` : null;
        loadRegions(url, '#village_id', 'Memuat desa/kelurahan...');
    });

    // --- Inisialisasi data alamat jika sudah ada (saat halaman dimuat) ---
    const initialProvinceId = '{{ old('province_id', $user->province_id) }}';
    const initialRegencyId = '{{ old('regency_id', $user->regency_id) }}';
    const initialDistrictId = '{{ old('district_id', $user->district_id) }}';
    const initialVillageId = '{{ old('village_id', $user->village_id) }}';

    if (initialProvinceId) {
        const url = `/api/regencies/${initialProvinceId}`;
        loadRegions(url, '#regency_id', 'Memuat kabupaten...', initialRegencyId);
    }
    if (initialRegencyId) {
        const url = `/api/districts/${initialRegencyId}`;
        loadRegions(url, '#district_id', 'Memuat kecamatan...', initialDistrictId);
    }
    if (initialDistrictId) {
        const url = `/api/villages/${initialDistrictId}`;
        loadRegions(url, '#village_id', 'Memuat desa/kelurahan...', initialVillageId);
    }
});
</script>
@endpush
