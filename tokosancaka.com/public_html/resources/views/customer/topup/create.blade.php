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


                        {{-- Area Preview Consult Pay DANA (AJAX) - JANGAN UBAH --}}
                        <div id="payment-methods-preview" class="mt-6 p-5 bg-gradient-to-br from-blue-50 to-indigo-50 rounded-xl border border-blue-100 hidden shadow-inner">
                            <div class="flex items-center mb-3">
                                <img src="https://tokosancaka.com/public/storage/logo/dana.png" class="h-6 mr-2" alt="DANA">
                                <span class="text-xs font-extrabold text-blue-800 uppercase tracking-wider">
                                      ESTIMASI PROMO (JIKA BAYAR PAKAI DANA):
                                </span>
                            </div>
                            <div id="payment-icons" class="flex flex-wrap gap-2 mt-2">
                                {{-- Icon Logo Bank akan muncul di sini via AJAX --}}
                            </div>
                            <p class="text-xs text-blue-500 mt-3 flex items-center"><i class="fas fa-lightbulb mr-1.5"></i> Metode di atas akan muncul otomatis di aplikasi DANA saat pembayaran.</p>
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



                    {{-- 2. PILIH METODE PEMBAYARAN (GRID VIEW) --}}
                    <div class="space-y-8 mt-6">

                       {{-- CEK STATUS BINDING & TAMPILKAN INFO SALDO ATAU TOMBOL BINDING --}}
                        @php
                            $user = Auth::user();
                            $isDanaBound = $user && !empty($user->dana_access_token);
                        @endphp

                        @if($isDanaBound)
                            <div class="p-5 bg-gradient-to-r from-blue-50 to-white border border-blue-200 rounded-2xl flex flex-col sm:flex-row sm:items-center justify-between shadow-sm mb-6 gap-4">
                                <div>
                                    <p class="text-sm text-gray-500 font-medium mb-1"><i class="fas fa-wallet mr-1 text-blue-500"></i> Saldo DANA Terhubung:</p>
                                    <h2 id="dana-balance-text" class="text-3xl font-extrabold text-blue-700 tracking-tight">Rp ******</h2>
                                    <p id="dana-balance-msg" class="text-xs text-red-500 mt-1 font-medium bg-red-50 px-2 py-1 rounded inline-block" style="display:none;"></p>
                                </div>
                                <button type="button" id="btn-cek-saldo-dana" class="w-full sm:w-auto px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white text-sm font-bold rounded-xl shadow-md transition-all flex items-center justify-center group">
                                    <i class="fas fa-sync mr-2 group-hover:rotate-180 transition-transform duration-500"></i> Cek Saldo
                                </button>
                            </div>
                        @else
                            <div class="p-5 bg-gradient-to-r from-yellow-50 to-orange-50 border border-yellow-200 rounded-2xl flex flex-col sm:flex-row sm:items-center justify-between shadow-sm mb-6 gap-4">
                                <div>
                                    <p class="text-base text-gray-800 font-bold mb-1"><i class="fas fa-exclamation-triangle text-yellow-500 mr-2 text-lg"></i> DANA Belum Terhubung</p>
                                    <p class="text-sm text-gray-600">Hubungkan akun DANA Anda untuk menikmati fitur bayar instan (Auto-Debit).</p>
                                </div>
                                {{-- URL BINDING TANPA AFFILIATE ID --}}
                                <a href="{{ url('/customer/dana/bind') }}" class="w-full sm:w-auto px-6 py-3 bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white text-sm font-bold rounded-xl shadow-md transition-all flex items-center justify-center">
                                    <i class="fas fa-link mr-2"></i> Hubungkan Sekarang
                                </a>
                            </div>
                        @endif

                            <div id="dynamic-payment-fields" class="mt-6 p-4 bg-gray-50 rounded-xl border border-gray-200 hidden">
                        <div id="ovo-field" class="hidden">
                            <label class="block text-sm font-bold mb-2">Nomor OVO (No. HP):</label>
                            <input type="text" name="ovo_id" class="w-full p-3 border rounded-lg" placeholder="0812xxxxxx">
                        </div>
                        <div id="jenius-field" class="hidden">
                            <label class="block text-sm font-bold mb-2">Cashtag Jenius:</label>
                            <input type="text" name="jenius_cashtag" class="w-full p-3 border rounded-lg" placeholder="$cashtag">
                        </div>
                    </div>

                        {{-- GROUP 1: MANUAL & GATEWAY LAIN --}}
                        <div>
                            <h5 class="text-sm font-extrabold text-gray-400 uppercase tracking-wider mb-4 pl-3 border-l-4 border-gray-400">Transfer & E-Wallet</h5>
                            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">

                                {{-- DANA DIRECT DEBIT --}}

                                <label class="relative cursor-pointer group">
                                    <input type="radio" name="payment_method" value="DANA_DIRECT_DEBIT" class="peer sr-only">
                                    <div class="h-full p-4 bg-white border-2 border-gray-200 rounded-xl hover:border-blue-400 peer-checked:border-blue-600 peer-checked:bg-blue-50 peer-checked:shadow-md transition-all flex flex-col items-center text-center">
                                        <img src="{{ asset('assets/dana.webp') }}" class="h-12 w-12 object-contain mb-3 rounded-lg shadow-sm" onerror="this.src='https://upload.wikimedia.org/wikipedia/commons/7/72/Logo_dana_blue.svg'">
                                        <span class="text-sm font-bold text-gray-800">DANA BALANCE</span>
                                        <span class="text-[10px] text-blue-500 font-semibold bg-blue-100 px-2 py-0.5 rounded mt-1">Topup Instan</span>
                                        <div class="absolute top-3 right-3 text-blue-600 opacity-0 peer-checked:opacity-100 transform scale-50 peer-checked:scale-100 transition-all">
                                            <i class="fas fa-check-circle text-xl"></i>
                                        </div>
                                    </div>
                                </label>


                                {{-- DANA DIRECT GAPURA --}}
                                <label class="relative cursor-pointer group">
                                    <input type="radio" name="payment_method" value="DANA" class="peer sr-only">
                                    <div class="h-full p-4 bg-white border-2 border-gray-200 rounded-xl hover:border-blue-400 peer-checked:border-blue-600 peer-checked:bg-blue-50 peer-checked:shadow-md transition-all flex flex-col items-center text-center">
                                        <img src="{{ asset('assets/dana.webp') }}" class="h-12 w-12 object-contain mb-3 rounded-lg shadow-sm" onerror="this.src='https://upload.wikimedia.org/wikipedia/commons/7/72/Logo_dana_blue.svg'">
                                        <span class="text-sm font-bold text-gray-800">DANA</span>
                                        <span class="text-[10px] text-gray-500 font-semibold bg-gray-100 px-2 py-0.5 rounded mt-1">Payment Gateway</span>
                                        <div class="absolute top-3 right-3 text-blue-600 opacity-0 peer-checked:opacity-100 transform scale-50 peer-checked:scale-100 transition-all">
                                            <i class="fas fa-check-circle text-xl"></i>
                                        </div>
                                    </div>
                                </label>

                                {{-- TOMBOL POTONG SALDO DANA --}}
                                <label class="relative cursor-pointer group">
                                    <input type="radio" name="payment_method" value="DANA_BINDING" class="peer sr-only" {{ !$isDanaBound ? 'disabled' : '' }} required>
                                    <div class="h-full p-4 bg-white border-2 border-gray-200 rounded-xl hover:border-blue-400 peer-checked:border-blue-600 peer-checked:bg-blue-50 peer-checked:shadow-md transition-all flex flex-col items-center text-center {{ !$isDanaBound ? 'opacity-60 cursor-not-allowed bg-gray-50 grayscale' : '' }}">
                                        <img src="{{ asset('assets/dana.webp') }}" class="h-12 w-12 object-contain mb-3 rounded-lg shadow-sm" onerror="this.src='https://upload.wikimedia.org/wikipedia/commons/7/72/Logo_dana_blue.svg'">
                                        <span class="text-sm font-bold text-gray-800">Saldo DANA</span>
                                        @if($isDanaBound)
                                            <span class="text-[10px] text-green-700 font-bold mt-1 bg-green-100 px-2 py-0.5 rounded border border-green-200"><i class="fas fa-link mr-1"></i>Tersambung</span>
                                        @else
                                            <span class="text-[10px] text-red-600 font-bold mt-1 bg-red-100 px-2 py-0.5 rounded border border-red-200"><i class="fas fa-unlink mr-1"></i>Belum Terhubung</span>
                                        @endif
                                        <div class="absolute top-3 right-3 text-blue-600 opacity-0 peer-checked:opacity-100 transform scale-50 peer-checked:scale-100 transition-all">
                                            <i class="fas fa-check-circle text-xl"></i>
                                        </div>
                                    </div>
                                </label>

                                {{-- TRANSFER MANUAL --}}
                                <label class="relative cursor-pointer group">
                                    <input type="radio" name="payment_method" value="TRANSFER_MANUAL" class="peer sr-only">
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

                                {{-- MANDIRI VA
                                <label class="relative cursor-pointer group">
                                    <input type="radio" name="payment_method" value="MANDIRI_VA" class="peer sr-only">
                                    <div class="h-full p-4 bg-white border-2 border-gray-200 rounded-xl hover:border-blue-400 peer-checked:border-blue-600 peer-checked:bg-blue-50 peer-checked:shadow-md transition-all flex flex-col items-center text-center">
                                        <img src="{{ asset('assets/mandiri.webp') }}" class="h-12 object-contain mb-3 rounded-lg shadow-sm p-1" onerror="this.src='https://tokosancaka.com/public/assets/mandiri.png'">
                                        <span class="text-sm font-bold text-gray-800">Mandiri VA</span>
                                        <span class="text-[10px] text-gray-500 font-semibold bg-gray-100 px-2 py-0.5 rounded mt-1">Virtual Account</span>
                                        <div class="absolute top-3 right-3 text-blue-600 opacity-0 peer-checked:opacity-100 transform scale-50 peer-checked:scale-100 transition-all">
                                            <i class="fas fa-check-circle text-xl"></i>
                                        </div>
                                    </div>
                                </label>
                                {{-- AKHIR KODE MANDIRI VA --}}

                                {{-- DOKU --}}
                                <label class="relative cursor-pointer group">
                                    <input type="radio" name="payment_method" value="DOKU_JOKUL" class="peer sr-only">
                                    <div class="h-full p-4 bg-white border-2 border-gray-200 rounded-xl hover:border-blue-400 peer-checked:border-blue-600 peer-checked:bg-blue-50 peer-checked:shadow-md transition-all flex flex-col items-center text-center">
                                        <img src="https://tokosancaka.com/public/storage/logo/doku-ewallet.png" class="h-12 object-contain mb-3 rounded-lg shadow-sm p-1">
                                        <span class="text-sm font-bold text-gray-800">DOKU</span>
                                        <span class="text-[10px] text-gray-500 font-semibold bg-gray-100 px-2 py-0.5 rounded mt-1">Payment Gateway</span>
                                        <div class="absolute top-3 right-3 text-blue-600 opacity-0 peer-checked:opacity-100 transform scale-50 peer-checked:scale-100 transition-all">
                                            <i class="fas fa-check-circle text-xl"></i>
                                        </div>
                                    </div>
                                </label>

                                {{-- PAYPAL --}}
                                <label class="relative cursor-pointer group">
                                    <input type="radio" name="payment_method" value="PAYPAL" class="peer sr-only">
                                    <div class="h-full p-4 bg-white border-2 border-gray-200 rounded-xl hover:border-blue-400 peer-checked:border-blue-600 peer-checked:bg-blue-50 peer-checked:shadow-md transition-all flex flex-col items-center text-center">
                                        <img src="https://tokosancaka.com/public/assets/paypal.png" class="h-12 object-contain mb-3 rounded-lg shadow-sm p-1" onerror="this.src='https://placehold.co/40x40/EFEFEF/AAAAAA?text=PP'">
                                        <span class="text-sm font-bold text-gray-800">PayPal / CC</span>
                                        <span class="text-[10px] text-gray-500 font-semibold bg-gray-100 px-2 py-0.5 rounded mt-1">Auto Konversi USD</span>
                                        <div class="absolute top-3 right-3 text-blue-600 opacity-0 peer-checked:opacity-100 transform scale-50 peer-checked:scale-100 transition-all">
                                            <i class="fas fa-check-circle text-xl"></i>
                                        </div>
                                    </div>
                                </label>
                            </div>
                        </div>



                        {{-- GROUP IPAYMU --}}
                        {{-- <div class="mt-8">
                            <h5 class="text-sm font-extrabold text-gray-400 uppercase tracking-wider mb-4 pl-3 border-l-4 border-purple-500">
                                iPaymu Payment
                            </h5>
                            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">

                                <label class="relative cursor-pointer group">
                                    <input type="radio" name="payment_method" value="IPAYMU" class="peer sr-only">
                                    <div class="h-full p-4 bg-white border-2 border-gray-200 rounded-xl hover:border-blue-400 peer-checked:border-blue-600 peer-checked:bg-blue-50 peer-checked:shadow-md transition-all flex flex-col items-center text-center">
                                        <img src="https://tokosancaka.com/public/assets/ipaymu.jpg" class="h-12 object-contain mb-3 rounded-lg shadow-sm p-1" onerror="this.src='https://placehold.co/100x40/EFEFEF/AAAAAA?text=iPaymu'">
                                        <span class="text-sm font-bold text-gray-800">iPaymu</span>
                                        <span class="text-[10px] text-gray-500 font-semibold bg-gray-100 px-2 py-0.5 rounded mt-1">Pilih via iPaymu</span>
                                        <div class="absolute top-3 right-3 text-blue-600 opacity-0 peer-checked:opacity-100 transform scale-50 peer-checked:scale-100 transition-all">
                                            <i class="fas fa-check-circle text-xl"></i>
                                        </div>
                                    </div>
                                </label>

                            </div>
                        </div> --}}

                        {{-- GROUP 2: TRIPAY OTOMATIS (Looping Data API) --}}
                         @if(isset($groupedChannels) && count($groupedChannels) > 0)
                            @foreach($groupedChannels as $groupName => $channels)
                                <div class="mt-8">
                                    <h5 class="text-sm font-extrabold text-gray-400 uppercase tracking-wider mb-4 pl-3 border-l-4 border-blue-500">
                                        {{ $groupName }} <span class="text-[10px] bg-blue-100 text-blue-600 px-2 py-0.5 rounded ml-2 normal-case">Otomatis</span>
                                    </h5>

                                    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                                        @foreach($channels as $channel)
                                            @if($channel['active'] ?? true)
                                            <label class="relative cursor-pointer group">
                                                <input type="radio" name="payment_method" value="{{ $channel['code'] }}" class="peer sr-only">
                                                <div class="h-full p-4 bg-white border-2 border-gray-200 rounded-xl hover:border-blue-400 peer-checked:border-blue-600 peer-checked:bg-blue-50 transition-all flex flex-col items-center text-center shadow-sm hover:shadow-md">
                                                    <div class="h-10 flex items-center justify-center mb-3">
                                                        <img src="{{ $channel['icon'] ?? asset('assets/default-payment.png') }}" alt="{{ $channel['name'] }}" class="max-h-full max-w-full object-contain rounded-lg" onerror="this.src='https://placehold.co/40x40/EFEFEF/AAAAAA?text=Logo'">
                                                    </div>
                                                    <span class="text-xs font-bold text-gray-800 leading-tight group-hover:text-blue-700 peer-checked:text-blue-700 mb-2">
                                                        {{ $channel['name'] }}
                                                    </span>
                                                    <span class="mt-auto text-[10px] text-red-700 font-medium bg-gray-50 border border-gray-100 px-2 py-1 rounded w-full">
                                                        Admin: Rp {{ number_format($channel['total_fee']['flat'] ?? 0, 0, ',', '.') }}
                                                    </span>
                                                    <div class="absolute top-2 right-2 text-blue-600 opacity-0 peer-checked:opacity-100 transform scale-50 peer-checked:scale-100 transition-all">
                                                        <i class="fas fa-check-circle text-lg"></i>
                                                    </div>
                                                </div>
                                            </label>
                                            @endif
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        @else
                            <div class="p-5 bg-orange-50 text-orange-800 rounded-xl border border-orange-200 text-sm flex items-start mt-6">
                                <i class="fas fa-exclamation-circle mt-0.5 mr-3 text-lg text-orange-500"></i>
                                <div>
                                    <strong class="font-bold block mb-1">Gagal memuat gateway otomatis</strong>
                                    <span>Sistem tidak dapat memuat metode pembayaran Tripay saat ini. Silakan gunakan opsi DANA, DOKU, iPaymu, atau Transfer Manual di atas.</span>
                                </div>
                            </div>
                        @endif


                        {{-- PREVIEW METODE PEMBAYARAN OTOMATIS (Hanya Muncul Saat Input Nominal Valid) --}}

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

    {{-- SCRIPT AJAX CONSULT PAY & QUICK BUTTON --}}
    @push('scripts')
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            let typingTimer;
            const doneTypingInterval = 1000;
            const $input = $('#amount');
            const $previewArea = $('#payment-methods-preview');
            const $iconArea = $('#payment-icons');

            // ====================================================================
            // 🛠️ FIX AUTO SCROLL: Menggunakan Native Window Scroll (Anti-Konflik CSS)
            // ====================================================================
            $(document).on('change', 'input[name="payment_method"]', function() {
                if ($(this).is(':checked')) {
                    // Ambil posisi pixel dari element tombol submit
                    const targetPosition = $('#submit-section').offset().top - 120;

                    // Eksekusi smooth scroll native browser
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


            // --- KAMUS LOGO ---
            const logoMap = {
                'VIRTUAL_ACCOUNT_BCA':      'bca.webp',
                'VIRTUAL_ACCOUNT_BNI':      'bni.webp',
                'VIRTUAL_ACCOUNT_BRI':      'bri.webp',
                'VIRTUAL_ACCOUNT_MANDIRI':  'mandiri.webp',
                'VIRTUAL_ACCOUNT_PERMATA':  'permata.webp',
                'VIRTUAL_ACCOUNT_CIMB':     'cimb.svg',
                'VIRTUAL_ACCOUNT_DANAMON':  'danamon.png',
                'VIRTUAL_ACCOUNT_BSI':      'bsi.png',
                'VIRTUAL_ACCOUNT_MUAMALAT': 'muamalat.png',
                'VIRTUAL_ACCOUNT_BTPN':     'btpn.png',

                'NETWORK_PAY_PG_OVO':       'ovo.webp',
                'NETWORK_PAY_PG_GOPAY':     'gopay.webp',
                'NETWORK_PAY_PG_SHOPEEPAY': 'shopeepay.webp',
                'NETWORK_PAY_PG_LINKAJA':   'linkaja.png',
                'NETWORK_PAY_PG_DANA':      'dana.webp',
                'NETWORK_PAY_PG_CARD':      'card.png',

                'BALANCE':                  'saldo.png',
                'CARD':                     'card.png',
                'CREDIT_CARD':              'card.png',
                'DEBIT_CARD':               'card.png'
            };

            $input.on('keyup', function () {
                clearTimeout(typingTimer);
                if ($input.val()) typingTimer = setTimeout(cekMetodePembayaran, doneTypingInterval);
            });

            $input.on('change', function () {
                clearTimeout(typingTimer);
                cekMetodePembayaran();
            });

            function cekMetodePembayaran() {
                let nominal = $input.val();

                if(nominal < 10000) {
                    $previewArea.addClass('hidden');
                    return;
                }

                $previewArea.removeClass('hidden');
                $iconArea.html('<div class="w-full text-center text-blue-500 text-sm py-4 font-medium"><i class="fas fa-circle-notch fa-spin mr-2"></i> Sinkronisasi ke Server DANA...</div>');

                $.ajax({
                    url: "{{ route('topup.consult') }}",
                    method: "POST",
                    dataType: "json",
                    data: {
                        _token: "{{ csrf_token() }}",
                        amount: nominal
                    },
                    success: function(response) {
                        $iconArea.empty();

                        if(response.success && response.data.length > 0) {
                            $.each(response.data, function(index, item) {
                                let apiCode = item.option;

                                let cleanName = item.method.replace(/_/g, ' ')
                                                           .replace('VIRTUAL ACCOUNT', 'VA')
                                                           .replace('NETWORK PAY PG', '')
                                                           .replace('DIRECT DEBIT', '');

                                let filename = logoMap[apiCode];
                                let cardContent = '';

                                if (filename) {
                                    let logoUrl = "{{ asset('assets') }}/" + filename;
                                    cardContent = `<img src="${logoUrl}" alt="${cleanName}" class="h-8 object-contain mb-1 rounded-md" onerror="this.style.display='none'; this.nextElementSibling.style.display='inline-block'"> <span style="display:none" class="text-xs font-bold text-gray-600 border rounded px-1">${cleanName.substring(0,4)}</span>`;
                                } else {
                                    cardContent = `<span class="text-xs font-bold text-gray-600 border rounded px-1">${cleanName.substring(0,4)}</span>`;
                                }

                                let promoBadge = (item.promo === 'Ada Promo')
                                    ? `<span class="absolute top-0 right-0 bg-gradient-to-r from-red-500 to-pink-500 text-white text-[9px] px-1.5 py-0.5 rounded-bl-lg font-bold shadow-sm">PROMO</span>`
                                    : '';

                                let badge = `
                                    <div class="relative inline-flex flex-col items-center justify-center p-2 bg-white border border-blue-100 rounded-xl shadow-sm w-[110px] h-24 text-center hover:border-blue-400 hover:shadow-md transition-all cursor-default overflow-hidden group">
                                        ${promoBadge}
                                        <div class="flex-grow flex items-center justify-center w-full mt-2">
                                            ${cardContent}
                                        </div>
                                        <span class="text-[10px] text-gray-600 leading-tight font-bold mt-1 w-full whitespace-normal break-words group-hover:text-blue-600">${cleanName}</span>
                                    </div>
                                `;
                                $iconArea.append(badge);
                            });

                        } else {
                            $iconArea.html('<span class="text-gray-500 text-sm font-medium py-2 px-2"><i class="fas fa-info-circle text-blue-400 mr-2"></i>Metode pembayaran standar tersedia.</span>');
                        }
                    },
                    error: function(xhr) {
                        console.error("Consult Pay Error:", xhr.responseText);
                        $iconArea.html('<span class="text-red-500 text-sm font-medium py-2 px-2"><i class="fas fa-exclamation-triangle mr-2"></i>Gagal memuat integrasi DANA.</span>');
                    }
                });
            }
        });


        // Tambahkan di dalam $(document).ready(function() { ... });
        $('input[name="payment_method"]').on('change', function() {
            let method = $(this).val();
            $('#dynamic-payment-fields').removeClass('hidden');
            $('#ovo-field').addClass('hidden');
            $('#jenius-field').addClass('hidden');

            if (method === 'OVO') {
                $('#ovo-field').removeClass('hidden');
            } else if (method === 'JENIUS_PAY') {
                $('#jenius-field').removeClass('hidden');
            } else {
                $('#dynamic-payment-fields').addClass('hidden');
            }
        });

        // ==========================================
        // SCRIPT CEK SALDO DANA REAL-TIME
        // ==========================================
        $('#btn-cek-saldo-dana').on('click', function() {
            let $btn = $(this);
            let $textSaldo = $('#dana-balance-text');
            let $textMsg = $('#dana-balance-msg');

            let originalText = $btn.html();
            $btn.html('<i class="fas fa-circle-notch fa-spin mr-2"></i> Mengecek...');
            $btn.prop('disabled', true);
            $textMsg.hide();

            $.ajax({
                url: "{{ route('customer.dana.check_balance') }}",
                method: "GET",
                dataType: "json",
                success: function(response) {
                    if(response.success) {
                        $textSaldo.html(response.formatted_balance);
                    } else {
                        $textMsg.html(response.message).show();
                    }
                },
                error: function() {
                    $textMsg.html('Terjadi kesalahan koneksi server.').show();
                },
                complete: function() {
                    $btn.html(originalText);
                    $btn.prop('disabled', false);
                }
            });
        });
    </script>
    @endpush
@endsection
