@extends('layouts.customer')

@section('title', 'Pencairan Saldo ke Bank')

{{-- Tambahkan CSS Select2 di bagian atas (bisa juga dilempar via stack/push jika ada) --}}
@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        /* Penyesuaian tema Select2 dengan Tailwind */
        .select2-container .select2-selection--single {
            height: 3rem; /* Setara py-3 */
            border-color: #d1d5db;
            border-radius: 0.5rem;
            background-color: #f9fafb;
            display: flex;
            align-items: center;
        }
        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 100%;
        }
    </style>
@endpush

@section('content')
    <h3 class="text-3xl font-semibold text-gray-700">Tarik Saldo ke Rekening Bank</h3>
    <p class="text-gray-500 mt-1">Cairkan saldo komisi/profit Anda langsung ke rekening bank tujuan (DANA Disbursement).</p>

    <div class="mt-8">
        <div class="max-w-4xl mx-auto bg-white rounded-lg shadow-lg">
            <div class="p-6 md:p-8">

                {{-- Alert Error / Success --}}
                @if ($errors->any())
                    <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                        <strong class="font-bold">Oops! Terjadi kesalahan.</strong>
                        <ul class="mt-2 list-disc list-inside">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if (session('error'))
                    <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                        <strong class="font-bold">Gagal!</strong>
                        <span class="block sm:inline">{!! nl2br(e(session('error'))) !!}</span>
                    </div>
                @endif

                @if (session('success'))
                    <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                        <strong class="font-bold">Berhasil!</strong>
                        <span class="block sm:inline">{!! session('success') !!}</span>
                    </div>
                @endif

                @if (session('warning'))
                    <div class="mb-4 bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded relative" role="alert">
                        <strong class="font-bold">Pending:</strong>
                        <span class="block sm:inline">{!! nl2br(e(session('warning'))) !!}</span>
                    </div>
                @endif

                {{-- FORM ALUR 1 ATAU ALUR 2 --}}
                @if(!session('valid_account_name'))
                    {{-- ALUR 1: CEK REKENING (INQUIRY) --}}
                    <form id="inquiry-form" action="{{ route('customer.dana.bank_inquiry') }}" method="POST">
                        @csrf
                        <input type="hidden" name="affiliate_id" value="{{ auth()->user()->id_pengguna ?? '' }}">

                        <div class="space-y-6">
                            <div>
                                <label for="bank_code" class="block text-sm font-bold text-gray-700 mb-2">Pilih Bank Tujuan</label>
                                {{-- GANTI HARDCODE DENGAN DATABASE --}}
                                <select name="bank_code" id="bank_code" required class="select2-dropdown w-full">
                                    <option value="">-- Cari & Pilih Bank --</option>
                                    @foreach($banks as $bank)
                                        <option value="{{ $bank->bank_code }}" {{ old('bank_code') == $bank->bank_code ? 'selected' : '' }}>
                                            {{ $bank->bank_code }} - {{ $bank->bank_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label for="account_no" class="block text-sm font-bold text-gray-700 mb-2">Nomor Rekening</label>
                                <input type="number" name="account_no" id="account_no" value="{{ old('account_no') }}" required placeholder="Contoh: 1234567890"
                                    class="focus:ring-blue-500 focus:border-blue-500 block w-full p-3 border-gray-300 rounded-lg bg-gray-50">
                            </div>

                            <div>
                                <label for="amount" class="block text-sm font-bold text-gray-700 mb-2">Nominal Pencairan</label>
                                <div class="relative rounded-md shadow-sm">
                                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                        <span class="text-gray-500 text-lg font-bold">Rp</span>
                                    </div>
                                    <input type="number" name="amount" id="amount" value="{{ old('amount') }}" required min="10000" placeholder="10000"
                                        class="focus:ring-blue-500 focus:border-blue-500 block w-full pl-12 pr-4 py-3 text-lg font-bold border-gray-300 rounded-lg bg-gray-50">
                                </div>
                                <p class="mt-1 text-xs text-gray-500">*Minimal pencairan Rp 10.000</p>
                            </div>
                        </div>

                        <div class="mt-8">
                            <button type="submit" id="btn-inquiry" class="w-full py-4 px-6 rounded-xl shadow-lg text-lg font-bold text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-4 focus:ring-blue-300 transition-all flex justify-center items-center">
                                <i class="fas fa-search mr-2"></i> Cek Rekening
                            </button>
                        </div>
                    </form>
                @else
                    {{-- ALUR 2: EKSEKUSI TRANSFER (SETELAH INQUIRY BERHASIL) --}}
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-5 mb-6 flex items-start">
                        <i class="fas fa-check-circle text-green-500 text-2xl mr-3 mt-1"></i>
                        <div>
                            <h4 class="text-lg font-bold text-gray-800">Rekening Ditemukan!</h4>
                            <p class="text-sm text-gray-600">Pastikan data di bawah ini sudah benar sebelum Anda melakukan transfer.</p>
                        </div>
                    </div>

                    <form id="transfer-form" action="{{ route('customer.dana.transfer_bank') }}" method="POST">
                        @csrf
                        <input type="hidden" name="affiliate_id" value="{{ auth()->user()->id_pengguna ?? '' }}">
                        <input type="hidden" name="bank_code" value="{{ old('bank_code') }}">
                        <input type="hidden" name="account_no" value="{{ old('account_no') }}">
                        <input type="hidden" name="account_name" value="{{ session('valid_account_name') }}">
                        <input type="hidden" name="amount" value="{{ old('amount') }}">

                        <div class="space-y-4 bg-gray-50 p-6 rounded-lg border border-gray-200">
                            <div class="flex justify-between border-b pb-2">
                                <span class="text-gray-500 font-medium">Bank Tujuan</span>
                                {{-- Mengambil nama bank yang sudah disimpan ke session dari Controller --}}
                                <span class="font-bold text-gray-800">
                                    {{ session('valid_bank_name') ?? old('bank_code') }}
                                </span>
                            </div>
                            <div class="flex justify-between border-b pb-2">
                                <span class="text-gray-500 font-medium">No. Rekening</span>
                                <span class="font-bold text-gray-800">{{ old('account_no') }}</span>
                            </div>
                            <div class="flex justify-between border-b pb-2">
                                <span class="text-gray-500 font-medium">Nama Pemilik</span>
                                <span class="font-bold text-blue-600">{{ session('valid_account_name') }}</span>
                            </div>
                            <div class="flex justify-between pt-2">
                                <span class="text-gray-500 font-medium">Nominal Ditransfer</span>
                                <span class="font-bold text-xl text-green-600">Rp {{ number_format(old('amount'), 0, ',', '.') }}</span>
                            </div>
                        </div>

                        <div class="mt-8 flex flex-col sm:flex-row gap-4">
                            <a href="{{ url()->current() }}" class="w-full sm:w-1/3 py-4 px-6 rounded-xl border border-gray-300 text-lg font-bold text-gray-700 bg-white hover:bg-gray-50 text-center transition-all">
                                Batal
                            </a>
                            <button type="submit" id="btn-transfer" class="w-full sm:w-2/3 py-4 px-6 rounded-xl shadow-lg text-lg font-bold text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-4 focus:ring-green-300 transition-all flex justify-center items-center">
                                <i class="fas fa-paper-plane mr-2"></i> Transfer Sekarang
                            </button>
                        </div>
                    </form>
                @endif

            </div>
        </div>
    </div>

    @push('scripts')
    <!-- jQuery dan Select2 JS -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            // Inisialisasi Fitur Pencarian Select2
            if ($('.select2-dropdown').length) {
                $('.select2-dropdown').select2({
                    placeholder: "-- Cari & Pilih Bank --",
                    allowClear: true,
                    width: '100%'
                });
            }

            // Fitur loading untuk mencegah Double Submit
            const inquiryForm = document.getElementById('inquiry-form');
            if(inquiryForm) {
                inquiryForm.addEventListener('submit', function() {
                    const btn = document.getElementById('btn-inquiry');
                    btn.disabled = true;
                    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Mengecek Data...';
                    btn.classList.add('opacity-75', 'cursor-not-allowed');
                });
            }

            const transferForm = document.getElementById('transfer-form');
            if(transferForm) {
                transferForm.addEventListener('submit', function() {
                    const btn = document.getElementById('btn-transfer');
                    btn.disabled = true;
                    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Memproses Transfer...';
                    btn.classList.add('opacity-75', 'cursor-not-allowed');
                });
            }
        });
    </script>
    @endpush
@endsection
