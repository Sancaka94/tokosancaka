@extends('layouts.customer')

{{-- LENGKAPI: Menambahkan section title --}}
@section('title', 'Top Up Saldo')

@section('content')
    <h3 class="text-3xl font-semibold text-gray-700">Top Up Saldo</h3>

    <div class="mt-8">
        {{-- ========================================================== --}}
        {{-- PERBAIKAN: Inisialisasi paymentMethod dengan old() --}}
        {{-- Ini penting agar dropdown & upload box muncul jika validasi gagal --}}
        {{-- ========================================================== --}}
        <div x-data="{ paymentMethod: '{{ old('payment_method', '') }}' }" class="max-w-2xl mx-auto bg-white rounded-lg shadow-lg">
            <div class="p-6 md:p-8">
                
                {{-- Menampilkan error validasi jika ada --}}
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
                
                {{-- Menampilkan error session jika ada --}}
                @if (session('error'))
                    <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                        <strong class="font-bold">Error!</strong>
                        <span class="block sm:inline">{{ session('error') }}</span>
                    </div>
                @endif

                <form action="{{ route('customer.topup.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf

                    {{-- Input Jumlah Top Up --}}
                    <div class="mb-6">
                        <label for="amount" class="block text-sm font-medium text-gray-700">Jumlah Top Up</label>
                        <div class="mt-1 relative rounded-md shadow-sm">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <span class="text-gray-500 sm:text-sm">Rp</span>
                            </div>
                            <input type="number" name="amount" id="amount" class="focus:ring-blue-500 focus:border-blue-500 block w-full pl-10 pr-12 sm:text-sm border-gray-300 rounded-md" placeholder="10000" min="10000" required value="{{ old('amount') }}">
                        </div>
                        <p class="mt-2 text-xs text-gray-500">Minimal top up adalah Rp 10.000.</p>
                    </div>

                    
                    <div class="space-y-6">
                        <div class="mb-6">
                            <label for="payment_method" class="block text-sm font-medium text-gray-700">Pilih Metode Pembayaran</label>
                            
                            <select id="payment_method" name="payment_method" x-model="paymentMethod"
                                    class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 
                                           focus:outline-none focus:ring-blue-500 focus:border-blue-500 
                                           sm:text-sm rounded-md" required>
                                
                                {{-- PERBAIKAN: Hapus 'selected' agar x-model bisa mengontrol --}}
                                <option value="" disabled>-- Pilih Metode Pembayaran --</option>
                                
                                {{-- Grup 1: Manual --}}
                                <optgroup label="Transfer Manual (Konfirmasi Admin)">
                                    <option value="TRANSFER_MANUAL">Transfer Bank (Upload Bukti)</option>
                                </optgroup>

                                {{-- Grup 2: DOKU --}}
                                <optgroup label="DOKU (Otomatis)">
                                    <option value="DOKU_JOKUL">DOKU (Semua Bank, E-Wallet, QRIS)</option>
                                </optgroup>
                                
                                {{-- ========================================================== --}}
                                {{-- PERBAIKAN: Mengelompokkan semua opsi Tripay --}}
                                {{-- ========================================================== --}}
                                
                                {{-- Grup 3: Tripay - QRIS & E-Wallet --}}
                                <optgroup label="Tripay (Otomatis) - QRIS & E-Wallet">
                                    <option value="QRIS">QRIS (Semua E-Wallet & M-Banking)</option>
                                    <option value="OVO">OVO</option>
                                    <option value="DANA">DANA</option>
                                    <option value="SHOPEEPAY">ShopeePay</option>
                                    <option value="LINKAJA">LinkAja</option>
                                </optgroup>

                                {{-- Grup 4: Tripay - Virtual Account --}}
                                <optgroup label="Tripay (Otomatis) - Virtual Account">
                                    <option value="BCAVA">BCA Virtual Account</option>
                                    <option value="BNIVA">BNI Virtual Account</option>
                                    <option value="BRIVA">BRI Virtual Account</option>
                                    <option value="MANDIRIVA">Mandiri Virtual Account</option>
                                    <option value="PERMATAVA">Permata Virtual Account</option>
                                    <option value="CIMBVA">CIMB Niaga Virtual Account</option>
                                    <option value="DANAMONVA">Danamon Virtual Account</option>
                                    {{-- Pastikan kode BSI Anda 'BSIH2H' atau 'BSIVA' sesuai di Tripay --}}
                                    <option value="BSIVA">BSI Virtual Account</option> 
                                    <option value="MUAMALATVA">Bank Muamalat Virtual Account</option>
                                </optgroup>

                                {{-- Grup 5: Tripay - Retail Outlet --}}
                                <optgroup label="Tripay (Otomatis) - Retail Outlet">
                                    <option value="ALFAMART">Alfamart</option>
                                    <option value="INDOMARET">Indomaret</option>
                                    <option value="ALFAMIDI">Alfamidi</option>
                                    <option value="DAN_DAN">Dan+Dan</option>
                                    <option value="LAWSON">Lawson</option>
                                </optgroup>
                                
                                {{-- CATATAN: Opsi Bank Transfer (Manual) dari Tripay dihapus --}}
                                {{-- agar tidak membingungkan dengan 'Transfer Manual (Upload Bukti)' --}}

                            </select>
                        </div>

                        {{-- ========================================================== --}}
                        {{-- === PERUBAHAN: Kotak Upload Bukti Transfer DIHAPUS === --}}
                        {{-- ========================================================== --}}
                        {{-- <div x-show="paymentMethod === 'TRANSFER_MANUAL'" ...> ... </div> --}}
                        {{-- ========================================================== --}}

                    </div>

                    <div class="mt-8">
                        <button type="submit" class="w-full flex justify-center py-3 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            Lanjutkan Pembayaran
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection