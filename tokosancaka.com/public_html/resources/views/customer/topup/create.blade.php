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


                        {{-- Disini KODE SEMUA PAYMENT GATEWAY --}}

                       <div class="space-y-8">
                            @foreach($groupedChannels as $groupName => $channels)
                                <div>
                                    <!-- Judul Kategori (Virtual Account, E-Wallet, dll) -->
                                    <div class="flex items-center mb-4">
                                        <div class="w-1.5 h-6 bg-red-600 rounded-full mr-2"></div>
                                        <h3 class="text-base md:text-lg font-bold text-gray-800 uppercase tracking-wide">
                                            {{ $groupName }}
                                        </h3>
                                    </div>

                                    <!-- Grid Card Channel Pembayaran -->
                                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3.5">
                                        @foreach($channels as $channel)
                                            <label class="relative border border-gray-200 rounded-xl p-3.5 flex flex-col items-center justify-between cursor-pointer hover:border-red-500 hover:shadow-md transition-all bg-white group">
                                                <!-- Input Radio -->
                                                <input type="radio" name="payment_method" value="{{ $channel['code'] }}" class="sr-only peer" required>

                                                <!-- Efek Border Merah saat Dipilih (Selected State) -->
                                                <div class="absolute inset-0 border-2 border-transparent peer-checked:border-red-600 rounded-xl pointer-events-none transition-all"></div>

                                                <!-- Logo Bank/E-Wallet dengan Fallback jika Gambar Rusak -->
                                                <div class="h-12 w-full flex items-center justify-center mb-2.5 px-1">
                                                    <img src="{{ $channel['icon'] ?? asset('images/default-payment.png') }}"
                                                        onerror="this.onerror=null; this.src='https://assets.tripay.co.id/upload/payment-icon/qQYo61sIDa1702995837.png';"
                                                        alt="{{ $channel['name'] }}"
                                                        class="max-h-10 max-w-full object-contain filter group-hover:scale-105 transition-transform duration-200">
                                                </div>

                                                <!-- Nama Metode -->
                                                <div class="text-center w-full">
                                                    <span class="text-xs font-semibold text-gray-700 block truncate w-full" title="{{ $channel['name'] }}">
                                                        {{ $channel['name'] }}
                                                    </span>

                                                    <!-- Biaya Admin -->
                                                    <span class="text-[11px] font-medium text-gray-400 mt-0.5 block">
                                                        @if($channel['fee'] > 0)
                                                            Biaya: Rp {{ number_format($channel['fee'], 0, ',', '.') }}
                                                        @else
                                                            <span class="text-green-600 font-semibold">Bebas Biaya</span>
                                                        @endif
                                                    </span>
                                                </div>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>


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
