@extends('layouts.customer')

@section('content')
    <h3 class="text-3xl font-semibold text-gray-700">Top Up Saldo</h3>

    <div class="mt-8">
        {{-- ✅ PERBAIKAN: Menghapus Alpine.js untuk menyederhanakan form --}}
        <div class="max-w-2xl mx-auto bg-white rounded-lg shadow-lg">
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
                            <label for="bank_name" class="block text-sm font-medium text-gray-700">Pilih Metode Pembayaran</label>
                            <select id="bank_name" name="bank_name" 
                                class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 
                                       focus:outline-none focus:ring-blue-500 focus:border-blue-500 
                                       sm:text-sm rounded-md" required>
                                <option value="" disabled selected>-- Pilih Metode Pembayaran --</option>
                            
                                <!-- Virtual Account -->
                                <option value="PERMATAVA">Permata Virtual Account</option>
                                <option value="BNIVA">BNI Virtual Account</option>
                                <option value="BRIVA">BRI Virtual Account</option>
                                <option value="MANDIRIVA">Mandiri Virtual Account</option>
                                <option value="BCAVA">BCA Virtual Account</option>
                                <option value="MUAMALATVA">Muamalat Virtual Account</option>
                                <option value="CIMBVA">CIMB Niaga Virtual Account</option>
                                <option value="BSIVA">BSI Virtual Account</option>
                                <option value="OCBCVA">OCBC NISP Virtual Account</option>
                                <option value="DANAMONVA">Danamon Virtual Account</option>
                                <option value="OTHERBANKVA">Other Bank Virtual Account</option>
                            
                                <option value="ALFAMART">Alfamart</option>
                                <option value="INDOMARET">Indomaret</option>
                                <option value="ALFAMIDI">Alfamidi</option>
                            
                                <option value="OVO">OVO</option>
                                <option value="DANA">DANA</option>
                                <option value="SHOPEEPAY">ShopeePay</option>
                                <option value="QRIS">QRIS</option>
                            </select>
                        </div>

                        <!--<div class="mb-6">-->
                        <!--    <label for="proof_of_payment" class="block text-sm font-medium text-gray-700">Upload Bukti Transfer</label>-->
                        <!--    <input type="file" name="proof_of_payment" id="proof_of_payment" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100" required>-->
                        <!--    <p class="mt-2 text-xs text-gray-500">Format: JPG, PNG. Maksimal 2MB.</p>-->
                        <!--</div>-->
                    </div>

                    <div class="mt-8">
                        {{-- ✅ PERBAIKAN: Mengganti teks tombol menjadi statis --}}
                        <button type="submit" class="w-full flex justify-center py-3 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            Kirim Permintaan Top Up
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
