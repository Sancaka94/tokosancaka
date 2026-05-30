@extends('layouts.customer')

@section('title', 'Top Up Saldo DANA')

@section('content')
    <div class="mb-6">
        <h3 class="text-3xl font-semibold text-gray-700 tracking-tight">Isi Saldo DANA</h3>
        <p class="text-gray-500 mt-1">Masukkan nomor DANA tujuan, pilih nominal, dan selesaikan pembayaran.</p>
    </div>

    <div class="mt-4">
        <div class="max-w-5xl mx-auto bg-white rounded-2xl shadow-xl border border-gray-100 overflow-hidden">
            <div class="p-6 md:p-8">

                {{-- Alert Error --}}
                @if ($errors->any())
                    <div class="mb-6 bg-red-50 border-l-4 border-red-500 p-4 rounded-r-lg shadow-sm" role="alert">
                        <div class="flex items-center">
                            <i class="fas fa-exclamation-circle text-red-500 text-lg mr-3"></i>
                            <strong class="font-bold text-red-800">Oops! Terjadi kesalahan.</strong>
                        </div>
                        <ul class="mt-2 ml-7 list-disc list-inside text-sm text-red-700">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if (session('error'))
                    <div class="mb-6 bg-red-50 border-l-4 border-red-500 p-4 rounded-r-lg shadow-sm" role="alert">
                        <div class="flex items-center">
                            <i class="fas fa-times-circle text-red-500 text-lg mr-3"></i>
                            <strong class="font-bold text-red-800 mr-2">Error!</strong>
                            <span class="block sm:inline text-red-700">{{ session('error') }}</span>
                        </div>
                    </div>
                @endif

                <form action="{{ route('customer.topupdana.store') }}" method="POST">
                    @csrf

                    {{-- 1. INPUT NOMOR DANA TUJUAN --}}
                    <div class="mb-8">
                        <label class="block text-lg font-bold text-gray-800 mb-3">Nomor DANA Tujuan</label>
                        <div class="relative rounded-xl shadow-sm group">
                            <div class="absolute inset-y-0 left-0 pl-5 flex items-center pointer-events-none">
                                <i class="fas fa-mobile-alt text-gray-400 group-focus-within:text-blue-600 text-xl transition-colors"></i>
                            </div>
                            <input type="number" name="dana_number" id="dana_number"
                                class="block w-full pl-14 pr-4 py-4 text-xl font-bold text-gray-800 border-2 border-gray-200 rounded-xl focus:ring-0 focus:border-blue-500 transition-colors bg-gray-50 focus:bg-white"
                                placeholder="Contoh: 081234567890" required value="{{ old('dana_number') }}">
                        </div>
                    </div>

                    {{-- 2. INPUT JUMLAH NOMINAL --}}
                    <div class="mb-10">
                        <label class="block text-lg font-bold text-gray-800 mb-4">Pilih Nominal Top Up</label>
                        
                        <div class="grid grid-cols-3 md:grid-cols-5 gap-3 mb-5">
                            <button type="button" class="btn-quick-amount py-3 px-2 rounded-xl border-2 border-blue-100 bg-blue-50/50 text-blue-700 font-bold hover:bg-blue-100 shadow-sm flex flex-col items-center justify-center group" data-amount="10000">
                                <span class="text-xs text-gray-500 font-medium mb-0.5 group-hover:text-blue-500">Rp</span>
                                <span class="text-lg">10.000</span>
                            </button>
                            <button type="button" class="btn-quick-amount py-3 px-2 rounded-xl border-2 border-blue-100 bg-blue-50/50 text-blue-700 font-bold hover:bg-blue-100 shadow-sm flex flex-col items-center justify-center group" data-amount="20000">
                                <span class="text-xs text-gray-500 font-medium mb-0.5 group-hover:text-blue-500">Rp</span>
                                <span class="text-lg">20.000</span>
                            </button>
                            <button type="button" class="btn-quick-amount py-3 px-2 rounded-xl border-2 border-blue-100 bg-blue-50/50 text-blue-700 font-bold hover:bg-blue-100 shadow-sm flex flex-col items-center justify-center group" data-amount="50000">
                                <span class="text-xs text-gray-500 font-medium mb-0.5 group-hover:text-blue-500">Rp</span>
                                <span class="text-lg">50.000</span>
                            </button>
                        </div>

                        <div class="relative rounded-xl shadow-sm group">
                            <div class="absolute inset-y-0 left-0 pl-5 flex items-center pointer-events-none">
                                <span class="text-gray-400 group-focus-within:text-blue-600 text-xl font-bold transition-colors">Rp</span>
                            </div>
                            <input type="number" name="amount" id="amount"
                                class="block w-full pl-14 pr-4 py-4 text-2xl font-bold text-gray-800 border-2 border-gray-200 rounded-xl focus:ring-0 focus:border-blue-500 transition-colors bg-gray-50 focus:bg-white"
                                placeholder="Nominal lainnya (Min. 10000)" min="10000" required value="{{ old('amount') }}">
                        </div>
                    </div>

                    <div class="relative py-4">
                        <div class="absolute inset-0 flex items-center" aria-hidden="true">
                            <div class="w-full border-t border-gray-200"></div>
                        </div>
                        <div class="relative flex justify-center">
                            <span class="px-4 bg-white text-sm text-gray-400 font-medium">METODE PEMBAYARAN</span>
                        </div>
                    </div>

                    {{-- INFORMASI SALDO USER --}}
                    <div class="bg-gradient-to-r from-blue-50 to-indigo-50 border border-blue-100 rounded-xl p-5 mb-6 flex items-center justify-between shadow-sm mt-4">
                        <div>
                            <span class="block text-sm font-bold text-blue-800 mb-1">Saldo Sancaka Anda saat ini</span>
                            <span class="block text-3xl font-black text-blue-700">Rp {{ number_format(auth()->user()->saldo ?? 0, 0, ',', '.') }}</span>
                        </div>
                        <div class="h-14 w-14 bg-white rounded-full flex items-center justify-center shadow-md text-blue-600">
                            <i class="fas fa-wallet text-2xl"></i>
                        </div>
                    </div>

                    {{-- 3. PILIH METODE PEMBAYARAN --}}
                    <div class="space-y-8 mt-4">
                        <div>
                            <h5 class="text-sm font-extrabold text-gray-400 uppercase tracking-wider mb-4 pl-3 border-l-4 border-gray-400">Pilih Metode</h5>
                            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">

                                {{-- POTONG SALDO (INTERNAL) --}}
                                <label class="relative cursor-pointer group">
                                    <input type="radio" name="payment_method" value="POTONG SALDO" class="peer sr-only" required>
                                    <div class="h-full p-4 bg-white border-2 border-gray-200 rounded-xl hover:border-green-400 peer-checked:border-green-600 peer-checked:bg-green-50 peer-checked:shadow-md transition-all flex flex-col items-center justify-center text-center">
                                        <div class="h-12 w-12 bg-green-100 text-green-600 rounded-full flex items-center justify-center mb-3">
                                            <i class="fas fa-wallet text-2xl"></i>
                                        </div>
                                        <span class="text-sm font-bold text-gray-800">Potong Saldo</span>
                                        <div class="absolute top-3 right-3 text-green-600 opacity-0 peer-checked:opacity-100 transform scale-50 peer-checked:scale-100 transition-all">
                                            <i class="fas fa-check-circle text-xl"></i>
                                        </div>
                                    </div>
                                </label>

                                {{-- DOKU --}}
                                <label class="relative cursor-pointer group">
                                    <input type="radio" name="payment_method" value="DOKU_JOKUL" class="peer sr-only" required>
                                    <div class="h-full p-4 bg-white border-2 border-gray-200 rounded-xl hover:border-blue-400 peer-checked:border-blue-600 peer-checked:bg-blue-50 peer-checked:shadow-md transition-all flex flex-col items-center justify-center text-center">
                                        <img src="https://tokosancaka.com/public/storage/logo/doku-ewallet.png" class="h-12 object-contain mb-3 rounded-lg shadow-sm p-1">
                                        <span class="text-sm font-bold text-gray-800">DOKU</span>
                                        <div class="absolute top-3 right-3 text-blue-600 opacity-0 peer-checked:opacity-100 transform scale-50 peer-checked:scale-100 transition-all">
                                            <i class="fas fa-check-circle text-xl"></i>
                                        </div>
                                    </div>
                                </label>

                                {{-- TRIPAY (Contoh QRIS) --}}
                                <label class="relative cursor-pointer group">
                                    <input type="radio" name="payment_method" value="QRIS" class="peer sr-only" required>
                                    <div class="h-full p-4 bg-white border-2 border-gray-200 rounded-xl hover:border-blue-400 peer-checked:border-blue-600 peer-checked:bg-blue-50 peer-checked:shadow-md transition-all flex flex-col items-center justify-center text-center">
                                        <span class="text-xl font-black text-blue-800 mb-3 flex items-center h-12">QRIS</span>
                                        <span class="text-sm font-bold text-gray-800">Tripay (Otomatis)</span>
                                        <div class="absolute top-3 right-3 text-blue-600 opacity-0 peer-checked:opacity-100 transform scale-50 peer-checked:scale-100 transition-all">
                                            <i class="fas fa-check-circle text-xl"></i>
                                        </div>
                                    </div>
                                </label>

                            </div>
                        </div>
                    </div>

                    {{-- TOMBOL SUBMIT --}}
                    <div id="submit-section" class="mt-12 pt-8 border-t border-gray-200">
                        <button type="submit" class="w-full py-5 px-6 rounded-xl shadow-xl shadow-blue-600/20 text-xl font-extrabold text-white bg-gradient-to-r from-blue-600 via-blue-700 to-indigo-700 hover:from-blue-700 hover:to-indigo-800 transition-all transform hover:-translate-y-1 flex items-center justify-center">
                            <i class="fas fa-bolt mr-3 text-blue-200"></i> BAYAR & ISI SALDO DANA
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @push('scripts')
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            const $input = $('#amount');
            $('.btn-quick-amount').on('click', function() {
                let val = $(this).data('amount');
                $('.btn-quick-amount').removeClass('bg-blue-600 text-white border-blue-600 shadow-md transform -translate-y-1')
                                      .addClass('bg-blue-50/50 text-blue-700 border-blue-100');
                $(this).removeClass('bg-blue-50/50 text-blue-700 border-blue-100')
                       .addClass('bg-blue-600 text-white border-blue-600 shadow-md transform -translate-y-1');
                $input.val(val).trigger('change');
            });
            $input.on('input', function() {
                $('.btn-quick-amount').removeClass('bg-blue-600 text-white border-blue-600 shadow-md transform -translate-y-1')
                                      .addClass('bg-blue-50/50 text-blue-700 border-blue-100');
            });
        });
    </script>
    @endpush
@endsection