@extends('layouts.customer')

@section('title', 'Top Up Saldo')

@section('content')
    <h3 class="text-3xl font-semibold text-gray-700">Top Up Saldo</h3>

    <div class="mt-8">
        <div class="max-w-5xl mx-auto bg-white rounded-lg shadow-lg">
            <div class="p-6 md:p-8">

                {{-- Alert Error --}}
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
                        <strong class="font-bold">Error!</strong>
                        <span class="block sm:inline">{{ session('error') }}</span>
                    </div>
                @endif

                <form action="{{ route('customer.topup.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf

                    {{-- 1. INPUT JUMLAH --}}
                    <div class="mb-8">
                        <label for="amount" class="block text-lg font-bold text-gray-700 mb-2">Mau isi saldo berapa?</label>
                        <div class="relative rounded-md shadow-sm">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <span class="text-gray-500 text-lg font-bold">Rp</span>
                            </div>
                            <input type="number" name="amount" id="amount"
                                class="focus:ring-blue-500 focus:border-blue-500 block w-full pl-12 pr-4 py-4 text-xl font-bold border-gray-300 rounded-xl"
                                placeholder="10000" min="10000" required value="{{ old('amount') }}">
                        </div>
                        <p class="mt-2 text-sm text-gray-500">*Minimal top up adalah Rp 10.000.</p>

                        {{-- Area Preview Consult Pay DANA (AJAX) - JANGAN UBAH --}}
                        <div id="payment-methods-preview" class="mt-4 p-4 bg-blue-50 rounded-lg border border-blue-100 hidden">
                            <div class="flex items-center mb-3">
                                <img src="https://tokosancaka.com/public/storage/logo/dana.png" class="h-6 mr-2" alt="DANA">
                                <span class="text-xs font-bold text-blue-800 uppercase tracking-wide">
                                     ESTIMASI PROMO (JIKA BAYAR PAKAI DANA):
                                </span>
                            </div>
                            <div id="payment-icons" class="flex flex-wrap gap-2 mt-2">
                                {{-- Icon Logo Bank akan muncul di sini via AJAX --}}
                            </div>
                            <p class="text-[11px] text-blue-500 mt-2 italic">*Metode di atas akan muncul otomatis di aplikasi DANA saat pembayaran.</p>
                        </div>
                    </div>

                    <hr class="my-8 border-gray-200">

                    {{-- 2. PILIH METODE PEMBAYARAN (GRID VIEW) --}}
                    <div class="space-y-8">
                        <h4 class="text-xl font-bold text-gray-800">Pilih Metode Pembayaran</h4>

                       {{-- CEK STATUS BINDING & TAMPILKAN INFO SALDO ATAU TOMBOL BINDING --}}
                        @php
                            $user = Auth::user();
                            $isDanaBound = $user && !empty($user->dana_access_token);
                        @endphp

                        @if($isDanaBound)
                            <div class="p-4 bg-blue-50 border border-blue-200 rounded-xl flex items-center justify-between shadow-sm mb-4">
                                <div>
                                    <p class="text-sm text-gray-500 font-medium mb-1"><i class="fas fa-wallet mr-1"></i> Saldo DANA Terhubung:</p>
                                    <h2 id="dana-balance-text" class="text-2xl font-bold text-blue-700">Rp ******</h2>
                                    <p id="dana-balance-msg" class="text-xs text-red-500 mt-1 font-medium" style="display:none;"></p>
                                </div>
                                <button type="button" id="btn-cek-saldo-dana" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-bold rounded-lg shadow transition-all flex items-center">
                                    <i class="fas fa-sync mr-2"></i> Cek Saldo
                                </button>
                            </div>
                        @else
                            <div class="p-4 bg-yellow-50 border border-yellow-200 rounded-xl flex items-center justify-between shadow-sm mb-4">
                                <div>
                                    <p class="text-sm text-gray-800 font-bold mb-1"><i class="fas fa-exclamation-triangle text-yellow-500 mr-1"></i> DANA Belum Terhubung</p>
                                    <p class="text-xs text-gray-600">Hubungkan akun DANA Anda untuk menikmati fitur bayar instan (Auto-Debit).</p>
                                </div>
                                {{-- URL BINDING TANPA AFFILIATE ID --}}
                                <a href="{{ url('/customer/dana/bind') }}" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-bold rounded-lg shadow transition-all flex items-center whitespace-nowrap">
                                    <i class="fas fa-link mr-2"></i> Hubungkan
                                </a>
                            </div>
                        @endif

                        {{-- GROUP 1: MANUAL & GATEWAY LAIN --}}
                        <div>
                            <h5 class="text-sm font-bold text-gray-500 uppercase tracking-wider mb-3 pl-1 border-l-4 border-gray-400">Transfer & E-Wallet</h5>
                            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">

                                {{-- DANA DIRECT DEBIT (Fitur Baru API Direct Debit) --}}
                                <label class="relative cursor-pointer group">
                                    <input type="radio" name="payment_method" value="DANA_DIRECT_DEBIT" class="peer sr-only">
                                    <div class="h-full p-4 bg-white border-2 border-gray-200 rounded-xl hover:border-blue-500 peer-checked:border-blue-600 peer-checked:bg-blue-50 transition-all flex flex-col items-center text-center">
                                        <img src="{{ asset('assets/dana.webp') }}" class="h-10 w-10 object-contain mb-2 rounded-md" onerror="this.src='https://upload.wikimedia.org/wikipedia/commons/7/72/Logo_dana_blue.svg'">
                                        <span class="text-sm font-bold text-gray-700">DANA Direct</span>
                                        <span class="text-[10px] text-gray-400">Checkout Instan</span>

                                        {{-- Centang --}}
                                        <div class="absolute top-2 right-2 text-blue-600 opacity-0 peer-checked:opacity-100">
                                            <i class="fas fa-check-circle"></i>
                                        </div>
                                    </div>
                                </label>

                                {{-- DANA DIRECT --}}
                                <label class="relative cursor-pointer group">
                                    <input type="radio" name="payment_method" value="DANA" class="peer sr-only">
                                    <div class="h-full p-4 bg-white border-2 border-gray-200 rounded-xl hover:border-blue-500 peer-checked:border-blue-600 peer-checked:bg-blue-50 transition-all flex flex-col items-center text-center">
                                        <img src="{{ asset('assets/dana.webp') }}" class="h-10 w-10 object-contain mb-2 rounded-md" onerror="this.src='https://upload.wikimedia.org/wikipedia/commons/7/72/Logo_dana_blue.svg'">
                                        <span class="text-sm font-bold text-gray-700">DANA</span>
                                        <span class="text-[10px] text-gray-400">PAYMENT GATEWAY</span>
                                        {{-- Centang --}}
                                        <div class="absolute top-2 right-2 text-blue-600 opacity-0 peer-checked:opacity-100"><i class="fas fa-check-circle"></i></div>
                                    </div>
                                </label>

                                {{-- 1. CEK STATUS BINDING AKUN DANA (DARI TABEL PENGGUNA) --}}
                                @php
                                    // Ambil status bind langsung dari user yang login (karena tabelnya Pengguna)
                                    // Pastikan kolom 'dana_access_token' sudah ada di tabel Pengguna.
                                    // Jika kolomnya berbeda, silakan sesuaikan.
                                    $user = Auth::user();
                                    $isDanaBound = $user && !empty($user->dana_access_token);
                                @endphp

                                {{-- 2. TOMBOL POTONG SALDO DANA --}}
                                <label class="relative cursor-pointer group">
                                    <input type="radio" name="payment_method" value="DANA_BINDING" class="peer sr-only" {{ !$isDanaBound ? 'disabled' : '' }} required>
                                    <div class="h-full p-4 bg-white border-2 border-gray-200 rounded-xl hover:border-blue-500 peer-checked:border-blue-600 peer-checked:bg-blue-50 transition-all flex flex-col items-center text-center {{ !$isDanaBound ? 'opacity-50 cursor-not-allowed bg-gray-50' : '' }}">

                                        <img src="{{ asset('assets/dana.webp') }}" class="h-10 w-10 object-contain mb-2 rounded-md" onerror="this.src='https://upload.wikimedia.org/wikipedia/commons/7/72/Logo_dana_blue.svg'">
                                        <span class="text-sm font-bold text-gray-700">Saldo DANA</span>

                                        @if($isDanaBound)
                                            <span class="text-[10px] text-green-600 font-bold mt-1 bg-green-100 px-2 py-0.5 rounded-full"><i class="fas fa-link mr-1"></i>Tersambung</span>
                                        @else
                                            <span class="text-[10px] text-red-500 font-bold mt-1 bg-red-100 px-2 py-0.5 rounded-full"><i class="fas fa-unlink mr-1"></i>Belum Terhubung</span>
                                        @endif

                                        <div class="absolute top-2 right-2 text-blue-600 opacity-0 peer-checked:opacity-100"><i class="fas fa-check-circle"></i></div>
                                    </div>
                                </label>

                                {{-- TRANSFER MANUAL --}}
                                <label class="relative cursor-pointer group">
                                    <input type="radio" name="payment_method" value="TRANSFER_MANUAL" class="peer sr-only">
                                    <div class="h-full p-4 bg-white border-2 border-gray-200 rounded-xl hover:border-blue-500 peer-checked:border-blue-600 peer-checked:bg-blue-50 transition-all flex flex-col items-center text-center">
                                        <div class="h-10 w-10 bg-gray-100 rounded-md flex items-center justify-center mb-2">
                                            <img
                                                src="https://tokosancaka.com/public/assets/saldo.png"
                                                alt="Saldo"
                                                class="w-5 h-5"
                                            />

                                        </div>
                                        <span class="text-sm font-bold text-gray-700">Transfer Bank</span>
                                        <span class="text-[10px] text-gray-400">Cek Manual Admin</span>
                                        <div class="absolute top-2 right-2 text-blue-600 opacity-0 peer-checked:opacity-100"><i class="fas fa-check-circle"></i></div>
                                    </div>
                                </label>

                                {{-- DOKU --}}
                                <label class="relative cursor-pointer group">
                                    <input type="radio" name="payment_method" value="DOKU_JOKUL" class="peer sr-only">
                                    <div class="h-full p-4 bg-white border-2 border-gray-200 rounded-xl hover:border-blue-500 peer-checked:border-blue-600 peer-checked:bg-blue-50 transition-all flex flex-col items-center text-center">
                                        <img src="https://tokosancaka.com/public/storage/logo/doku-ewallet.png" class="h-8 object-contain mb-2 mt-2 rounded-md">
                                        <span class="text-sm font-bold text-gray-700">DOKU</span>
                                        <span class="text-[10px] text-gray-400">Payment Gateway</span>
                                        <div class="absolute top-2 right-2 text-blue-600 opacity-0 peer-checked:opacity-100"><i class="fas fa-check-circle"></i></div>
                                    </div>
                                </label>
                            </div>
                        </div>

                        {{-- GROUP 2: TRIPAY OTOMATIS (Looping Data API) --}}
                        @if(isset($groupedChannels) && count($groupedChannels) > 0)
                            @foreach($groupedChannels as $groupName => $channels)
                                <div>
                                    <h5 class="text-sm font-bold text-gray-500 uppercase tracking-wider mb-3 pl-1 border-l-4 border-blue-500">
                                        {{ $groupName }} (Otomatis)
                                    </h5>

                                    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                                        @foreach($channels as $channel)
                                            @if($channel['active'])
                                            <label class="relative cursor-pointer group">
                                                <input type="radio" name="payment_method" value="{{ $channel['code'] }}" class="peer sr-only">

                                                <div class="h-full p-4 bg-white border-2 border-gray-200 rounded-xl hover:border-blue-500 peer-checked:border-blue-600 peer-checked:bg-blue-50 transition-all flex flex-col items-center text-center shadow-sm hover:shadow-md">

                                                    {{-- GAMBAR DARI API TRIPAY (icon_url) --}}
                                                    <img src="{{ $channel['icon_url'] }}" alt="{{ $channel['name'] }}" class="h-8 w-auto object-contain mb-3 rounded-md grayscale group-hover:grayscale-0 peer-checked:grayscale-0 transition-all">

                                                    <span class="text-xs font-bold text-gray-800 leading-tight group-hover:text-blue-600">
                                                        {{ $channel['name'] }}
                                                    </span>

                                                    <span class="text-[10px] text-gray-500 mt-1 bg-gray-100 px-2 py-0.5 rounded-md">
                                                        Admin Bank: Rp {{ number_format($channel['total_fee']['flat'] ?? 0, 0, ',', '.') }}
                                                    </span>

                                                    {{-- Checkmark --}}
                                                    <div class="absolute top-2 right-2 text-blue-600 opacity-0 peer-checked:opacity-100 transition-opacity">
                                                        <i class="fas fa-check-circle"></i>
                                                    </div>
                                                </div>
                                            </label>
                                            @endif
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        @else
                            {{-- Fallback jika API Tripay Error / Kosong --}}
                            <div class="p-4 bg-yellow-50 text-yellow-800 rounded-xl border border-yellow-200 text-sm flex items-center">
                                <i class="fas fa-exclamation-triangle mr-2"></i>
                                <span>Gagal memuat metode pembayaran otomatis. Silakan gunakan Transfer Manual atau DOKU.</span>
                            </div>
                        @endif

                    </div>

                    {{-- TOMBOL SUBMIT --}}
                    <div class="mt-10 pt-6 border-t border-gray-100">
                        <button type="submit" class="w-full py-4 px-6 rounded-xl shadow-lg shadow-blue-500/30 text-lg font-bold text-white bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 focus:outline-none focus:ring-4 focus:ring-blue-300 transition-all transform hover:-translate-y-1 active:translate-y-0">
                            <i class="fas fa-lock mr-2"></i> Lanjutkan Pembayaran
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- SCRIPT AJAX CONSULT PAY --}}
    @push('scripts')
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            let typingTimer;
            const doneTypingInterval = 1000;
            const $input = $('#amount');
            const $previewArea = $('#payment-methods-preview');
            const $iconArea = $('#payment-icons');

            // --- 1. KAMUS LOGO (MAPPING DANA TETAP SEPERTI PERMINTAAN) ---
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

                // UI Loading
                $previewArea.removeClass('hidden');
                $iconArea.html('<div class="w-full text-center text-gray-400 text-xs py-2"><i class="fas fa-spinner fa-spin mr-1"></i> Mengecek promo & metode pembayaran...</div>');

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

                            // 2. Loop Data
                            $.each(response.data, function(index, item) {
                                let apiCode = item.option;

                                let cleanName = item.method.replace(/_/g, ' ')
                                                       .replace('VIRTUAL ACCOUNT', 'VA')
                                                       .replace('NETWORK PAY PG', '')
                                                       .replace('DIRECT DEBIT', '');

                                let filename = logoMap[apiCode];
                                let cardContent = '';

                                // TAMPILKAN LOGO SESUAI MAPPING DANA
                                if (filename) {
                                    let logoUrl = "{{ asset('assets') }}/" + filename;
                                    cardContent = `<img src="${logoUrl}" alt="${cleanName}" class="h-8 object-contain mb-1 rounded-md" onerror="this.style.display='none'; this.nextElementSibling.style.display='inline-block'"> <span style="display:none" class="text-xs font-bold text-gray-600 border rounded px-1">${cleanName.substring(0,4)}</span>`;
                                } else {
                                    cardContent = `<span class="text-xs font-bold text-gray-600 border rounded px-1">${cleanName.substring(0,4)}</span>`;
                                }

                                // Badge Promo
                                let promoBadge = (item.promo === 'Ada Promo')
                                    ? `<span class="absolute top-0 right-0 bg-red-500 text-white text-[8px] px-1 rounded-bl-lg font-bold">PROMO</span>`
                                    : '';

                                // Template HTML Card (Style Baru - KOTAK)
                                let badge = `
                                    <div class="relative inline-flex flex-col items-center justify-center p-2 bg-white border border-gray-200 rounded-lg shadow-sm w-28 h-20 text-center hover:border-blue-500 transition-all cursor-default overflow-hidden group">
                                        ${promoBadge}
                                        <div class="flex-grow flex items-center justify-center w-full">
                                            ${cardContent}
                                        </div>
                                        <span class="text-[9px] text-gray-600 leading-tight font-medium mt-1 w-full whitespace-normal break-words group-hover:text-blue-600">${cleanName}</span>
                                    </div>
                                `;
                                $iconArea.append(badge);
                            });

                        } else {
                            $iconArea.html('<span class="text-gray-500 text-xs italic">Metode pembayaran standar tersedia.</span>');
                        }
                    },
                    error: function(xhr) {
                        console.error("Consult Pay Error:", xhr.responseText);
                        $iconArea.html('<span class="text-red-400 text-xs">Gagal memuat preview metode pembayaran.</span>');
                    }
                });
            }
        });

        // ==========================================
            // SCRIPT CEK SALDO DANA REAL-TIME
            // ==========================================
            $('#btn-cek-saldo-dana').on('click', function() {
                let $btn = $(this);
                let $textSaldo = $('#dana-balance-text');
                let $textMsg = $('#dana-balance-msg');

                // State Loading
                let originalText = $btn.html();
                $btn.html('<i class="fas fa-spinner fa-spin mr-2"></i> Mengecek...');
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
                        // Kembalikan tombol ke semula
                        $btn.html(originalText);
                        $btn.prop('disabled', false);
                    }
                });
            });

    </script>
    @endpush
@endsection
