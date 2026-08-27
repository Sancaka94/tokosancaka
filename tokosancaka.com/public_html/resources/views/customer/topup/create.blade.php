@extends('layouts.customer')

@section('title', 'Top Up Saldo')

@section('content')
    <div class="mb-6">
        <h3 class="text-3xl font-semibold text-gray-700 tracking-tight">Top Up Saldo</h3>
        <p class="text-gray-500 mt-1">Pilih nominal dan metode pembayaran untuk mengisi saldo Anda.</p>
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

                <form action="{{ route('customer.topup.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf

                    {{-- 1. INPUT JUMLAH --}}
                    <div class="mb-10">
                        <label class="block text-lg font-bold text-gray-800 mb-4">Mau isi saldo berapa?</label>

                        {{-- Tombol Pilihan Nominal Cepat --}}
                        <div class="grid grid-cols-3 md:grid-cols-5 gap-3 mb-5">
                            <button type="button" class="btn-quick-amount py-3 px-2 rounded-xl border-2 border-blue-100 bg-blue-50/50 text-blue-700 font-bold hover:bg-blue-100 hover:border-blue-300 transition-all shadow-sm flex flex-col items-center justify-center group" data-amount="10000">
                                <span class="text-xs text-gray-500 font-medium mb-0.5 group-hover:text-blue-500">Rp</span>
                                <span class="text-lg">10.000</span>
                            </button>
                            <button type="button" class="btn-quick-amount py-3 px-2 rounded-xl border-2 border-blue-100 bg-blue-50/50 text-blue-700 font-bold hover:bg-blue-100 hover:border-blue-300 transition-all shadow-sm flex flex-col items-center justify-center group" data-amount="20000">
                                <span class="text-xs text-gray-500 font-medium mb-0.5 group-hover:text-blue-500">Rp</span>
                                <span class="text-lg">20.000</span>
                            </button>
                            <button type="button" class="btn-quick-amount py-3 px-2 rounded-xl border-2 border-blue-100 bg-blue-50/50 text-blue-700 font-bold hover:bg-blue-100 hover:border-blue-300 transition-all shadow-sm flex flex-col items-center justify-center group" data-amount="30000">
                                <span class="text-xs text-gray-500 font-medium mb-0.5 group-hover:text-blue-500">Rp</span>
                                <span class="text-lg">30.000</span>
                            </button>
                            <button type="button" class="btn-quick-amount py-3 px-2 rounded-xl border-2 border-blue-100 bg-blue-50/50 text-blue-700 font-bold hover:bg-blue-100 hover:border-blue-300 transition-all shadow-sm flex flex-col items-center justify-center group" data-amount="50000">
                                <span class="text-xs text-gray-500 font-medium mb-0.5 group-hover:text-blue-500">Rp</span>
                                <span class="text-lg">50.000</span>
                            </button>
                            <button type="button" class="btn-quick-amount py-3 px-2 rounded-xl border-2 border-blue-100 bg-blue-50/50 text-blue-700 font-bold hover:bg-blue-100 hover:border-blue-300 transition-all shadow-sm flex flex-col items-center justify-center group" data-amount="100000">
                                <span class="text-xs text-gray-500 font-medium mb-0.5 group-hover:text-blue-500">Rp</span>
                                <span class="text-lg">100.000</span>
                            </button>
                        </div>

                        {{-- Input Manual --}}
                        <div class="relative rounded-xl shadow-sm group">
                            <div class="absolute inset-y-0 left-0 pl-5 flex items-center pointer-events-none">
                                <span class="text-gray-400 group-focus-within:text-blue-600 text-xl font-bold transition-colors">Rp</span>
                            </div>
                            <input type="number" name="amount" id="amount"
                                class="block w-full pl-14 pr-4 py-5 text-2xl font-bold text-gray-800 border-2 border-gray-200 rounded-xl focus:ring-0 focus:border-blue-500 transition-colors bg-gray-50 focus:bg-white"
                                placeholder="Nominal lainnya (Min. 10000)" min="10000" required value="{{ old('amount') }}">
                        </div>
                        <p class="mt-3 text-sm text-gray-500 flex items-center"><i class="fas fa-info-circle mr-1.5 text-blue-400"></i> Minimal top up adalah Rp 10.000.</p>
                    </div>

                    <div class="relative py-4">
                        <div class="absolute inset-0 flex items-center" aria-hidden="true">
                            <div class="w-full border-t border-gray-200"></div>
                        </div>
                        <div class="relative flex justify-center">
                            <span class="px-4 bg-white text-sm text-gray-400 font-medium">METODE PEMBAYARAN</span>
                        </div>
                    </div>

                    {{-- 2. PILIH METODE PEMBAYARAN --}}
                    <div class="space-y-8 mt-6">
                        <div>
                            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">

                                {{-- TRANSFER MANUAL --}}
                                <label class="relative cursor-pointer group">
                                    <input type="radio" name="payment_method" value="TRANSFER_MANUAL" class="peer sr-only" checked>
                                    <div class="h-full p-4 bg-white border-2 border-gray-200 rounded-xl hover:border-blue-400 peer-checked:border-blue-600 peer-checked:bg-blue-50 peer-checked:shadow-md transition-all flex flex-col items-center text-center">
                                        <div class="h-12 w-12 bg-gray-100 rounded-lg shadow-sm flex items-center justify-center mb-3">
                                            <img src="https://tokosancaka.com/public/assets/saldo.png" alt="Saldo" class="w-7 h-7 opacity-80" />
                                        </div>
                                        <span class="text-sm font-bold text-gray-800">Transfer Bank</span>
                                        <span class="text-[10px] text-gray-500 font-semibold bg-gray-100 px-2 py-0.5 rounded mt-1">Cek Manual Admin</span>
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
                        <button type="submit" class="w-full py-5 px-6 rounded-xl shadow-xl shadow-blue-600/20 text-xl font-extrabold text-white bg-gradient-to-r from-blue-600 via-blue-700 to-indigo-700 hover:from-blue-700 hover:to-indigo-800 focus:outline-none focus:ring-4 focus:ring-blue-300 transition-all transform hover:-translate-y-1 active:translate-y-0 flex items-center justify-center">
                            <i class="fas fa-lock mr-3 text-blue-200"></i> LANJUTKAN PEMBAYARAN
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- SCRIPT --}}
    @push('scripts')
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            const $input = $('#amount');

            // ====================================================================
            // 🛠️ FIX AUTO SCROLL: Menggunakan Native Window Scroll
            // ====================================================================
            $(document).on('change', 'input[name="payment_method"]', function() {
                if ($(this).is(':checked')) {
                    const targetPosition = $('#submit-section').offset().top - 120;
                    window.scrollTo({
                        top: targetPosition,
                        behavior: 'smooth'
                    });
                }
            });

            // ==========================================
            // SCRIPT TOMBOL PILIHAN CEPAT
            // ==========================================
            $('.btn-quick-amount').on('click', function() {
                let val = $(this).data('amount');

                // Styling Reset
                $('.btn-quick-amount').removeClass('bg-blue-600 text-white border-blue-600 shadow-md transform -translate-y-1')
                                      .addClass('bg-blue-50/50 text-blue-700 border-blue-100');
                $('.btn-quick-amount').find('span:first-child').removeClass('text-blue-200').addClass('text-gray-500 group-hover:text-blue-500');

                // Styling Active
                $(this).removeClass('bg-blue-50/50 text-blue-700 border-blue-100')
                       .addClass('bg-blue-600 text-white border-blue-600 shadow-md transform -translate-y-1');
                $(this).find('span:first-child').removeClass('text-gray-500 group-hover:text-blue-500').addClass('text-blue-200');

                $input.val(val).trigger('change');
            });

            $input.on('input', function() {
                $('.btn-quick-amount').removeClass('bg-blue-600 text-white border-blue-600 shadow-md transform -translate-y-1')
                                      .addClass('bg-blue-50/50 text-blue-700 border-blue-100');
                $('.btn-quick-amount').find('span:first-child').removeClass('text-blue-200').addClass('text-gray-500 group-hover:text-blue-500');
            });
        });
    </script>
    @endpush
@endsection
