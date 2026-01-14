@extends('layouts.customer')

@section('title', 'Top Up Saldo')

@section('content')
    <h3 class="text-3xl font-semibold text-gray-700">Top Up Saldo</h3>

    <div class="mt-8">
        <div x-data="{ paymentMethod: '{{ old('payment_method', '') }}' }" class="max-w-2xl mx-auto bg-white rounded-lg shadow-lg">
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

                    {{-- Input Jumlah --}}
                    <div class="mb-6">
                        <label for="amount" class="block text-sm font-medium text-gray-700">Jumlah Top Up</label>
                        <div class="mt-1 relative rounded-md shadow-sm">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <span class="text-gray-500 sm:text-sm">Rp</span>
                            </div>
                            {{-- ID="amount" penting untuk AJAX Consult Pay --}}
                            <input type="number" name="amount" id="amount" class="focus:ring-blue-500 focus:border-blue-500 block w-full pl-10 pr-12 sm:text-sm border-gray-300 rounded-md" placeholder="10000" min="10000" required value="{{ old('amount') }}">
                        </div>
                        <p class="mt-2 text-xs text-gray-500">Minimal top up adalah Rp 10.000.</p>

                        {{-- Area Preview Metode Pembayaran DANA (Consult Pay) --}}
                        <div id="payment-methods-preview" class="mt-4 p-3 bg-gray-50 rounded-lg border border-gray-100 hidden">
                            <span class="text-xs font-bold text-gray-700 uppercase tracking-wide">
                                <i class="fas fa-search-dollar mr-1"></i> Estimasi Metode di Aplikasi DANA:
                            </span>
                            <div id="payment-icons" class="flex flex-wrap gap-2 mt-2">
                                {{-- Icon Logo Bank akan muncul di sini via AJAX --}}
                            </div>
                            <p class="text-[10px] text-gray-400 mt-2 italic">*Metode di atas akan muncul saat Anda dialihkan ke aplikasi DANA.</p>
                        </div>
                    </div>

                    {{-- Pilihan Metode Pembayaran --}}
                    <div class="space-y-6">
                        <div class="mb-6">
                            <label for="payment_method" class="block text-sm font-medium text-gray-700">Pilih Metode Pembayaran</label>

                            <select id="payment_method" name="payment_method" x-model="paymentMethod"
                                    class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300
                                           focus:outline-none focus:ring-blue-500 focus:border-blue-500
                                           sm:text-sm rounded-md" required>

                                <option value="" disabled>-- Pilih Metode Pembayaran --</option>

                                {{-- Opsi DANA Direct Debit (BARU) --}}
                                <optgroup label="E-Wallet & Direct Debit (Rekomendasi)">
                                    <option value="DANA">DANA (Saldo, Kartu Debit/Kredit, Virtual Account)</option>
                                </optgroup>

                                {{-- Opsi Transfer Manual --}}
                                <optgroup label="Transfer Manual">
                                    <option value="TRANSFER_MANUAL">Transfer Bank (Upload Bukti)</option>
                                </optgroup>

                                {{-- Opsi Tripay --}}
                                <optgroup label="Tripay - Virtual Account & Retail">
                                    <option value="QRIS">QRIS (All E-Wallet)</option>
                                    <option value="BCAVA">BCA Virtual Account</option>
                                    <option value="BNIVA">BNI Virtual Account</option>
                                    <option value="BRIVA">BRI Virtual Account</option>
                                    <option value="MANDIRIVA">Mandiri Virtual Account</option>
                                    <option value="ALFAMART">Alfamart</option>
                                    <option value="INDOMARET">Indomaret</option>
                                </optgroup>

                                {{-- Opsi DOKU --}}
                                <optgroup label="Payment Gateway Lain">
                                    <option value="DOKU_JOKUL">DOKU Checkout</option>
                                </optgroup>

                            </select>
                        </div>
                    </div>

                    {{-- Tombol Submit --}}
                    <div class="mt-8">
                        <button type="submit" class="w-full flex justify-center py-3 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition duration-150 ease-in-out">
                            Lanjutkan Pembayaran
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
                            // 1. KAMUS MAPPING (Sesuaikan nama file Anda)
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
                                'NETWORK_PAY_PG_SPAY':      'shopeepay.webp',
                                'NETWORK_PAY_PG_LINKAJA':   'linkaja.png',
                                'NETWORK_PAY_PG_DANA':      'dana.webp',
                                'NETWORK_PAY_PG_CARD':      'card.png',


                                'BALANCE':                  'saldo.png',
                                'CARD':                     'card.png',
                                'CREDIT_CARD':              'card.png',
                                'DEBIT_CARD':               'card.png'
                            };

                            // 2. Loop Data
                            $.each(response.data, function(index, item) {
                                let apiCode = item.option;
                                // Bersihkan nama agar enak dibaca
                                let cleanName = apiCode.replace(/_/g, ' ')
                                                       .replace('VIRTUAL ACCOUNT', 'VA')
                                                       .replace('NETWORK PAY PG', '')
                                                       .replace('DIRECT DEBIT', '');

                                let filename = logoMap[apiCode];
                                let cardContent = '';

                                if (filename) {
                                    let logoUrl = "{{ asset('assets') }}/" + filename;
                                    // Menggunakan onerror agar jika file tidak ada, fallback ke teks
                                    cardContent = `<img src="${logoUrl}" alt="${cleanName}" class="h-8 object-contain mb-1" onerror="this.style.display='none'; this.nextElementSibling.style.display='inline-block'"> <span style="display:none" class="text-xs font-bold text-gray-600 border rounded px-1">${cleanName.substring(0,4)}</span>`;
                                } else {
                                    cardContent = `<span class="text-xs font-bold text-gray-600 border rounded px-1">${cleanName.substring(0,4)}</span>`;
                                }

                                // Badge Promo
                                let promoBadge = (item.promo === 'Ada Promo')
                                    ? `<span class="absolute top-0 right-0 bg-red-500 text-white text-[8px] px-1 rounded-bl-lg font-bold">PROMO</span>`
                                    : '';

                                // Template HTML Card (Style Baru)
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
    </script>
    @endpush
@endsection
