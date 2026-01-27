@extends('layouts.customer')

@section('title', 'Top Up Saldo')

@section('content')
    <div class="max-w-5xl mx-auto py-8">
        <h3 class="text-3xl font-bold text-gray-800 mb-2">Top Up Saldo</h3>
        <p class="text-gray-500 mb-8">Silakan isi nominal dan pilih metode pembayaran.</p>

        <div class="bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden">
            <div class="p-6 md:p-8">

                {{-- Alert Error --}}
                @if ($errors->any())
                    <div class="mb-6 bg-red-50 border-l-4 border-red-500 text-red-700 p-4 rounded-r shadow-sm" role="alert">
                        <strong class="font-bold block mb-1">Oops! Ada kesalahan:</strong>
                        <ul class="list-disc list-inside text-sm">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if (session('error'))
                    <div class="mb-6 bg-red-50 border-l-4 border-red-500 text-red-700 p-4 rounded-r shadow-sm">
                        <strong class="font-bold">Error!</strong> {{ session('error') }}
                    </div>
                @endif

                <form action="{{ route('customer.topup.store') }}" method="POST">
                    @csrf

                    {{-- BAGIAN 1: INPUT NOMINAL --}}
                    <div class="mb-10">
                        <label for="amount" class="block text-lg font-bold text-gray-700 mb-3">Mau isi saldo berapa?</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <span class="text-gray-500 text-xl font-bold">Rp</span>
                            </div>
                            <input type="number" name="amount" id="amount"
                                class="block w-full pl-12 pr-4 py-4 text-2xl font-bold text-gray-700 bg-gray-50 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all placeholder-gray-300"
                                placeholder="Min. 10.000" min="10000" required value="{{ old('amount') }}">
                        </div>
                        <p class="mt-2 text-sm text-gray-500">*Minimal top up Rp 10.000</p>

                        {{-- Area Preview Consult Pay DANA (AJAX) - JANGAN DIUBAH --}}
                        <div id="payment-methods-preview" class="mt-6 p-4 bg-blue-50 rounded-xl border border-blue-100 hidden transition-all">
                            <div class="flex items-center mb-3">
                                <img src="https://a.m.dana.id/resource/imgs/skywalker/logo/dana-logo-blue.png" class="h-6 mr-2" alt="DANA">
                                <span class="text-xs font-extrabold text-blue-800 uppercase tracking-widest">
                                     ESTIMASI PROMO (JIKA BAYAR PAKAI DANA)
                                </span>
                            </div>
                            <div id="payment-icons" class="flex flex-wrap gap-2">
                                {{-- Icon Logo Bank akan muncul di sini via AJAX --}}
                            </div>
                            <p class="text-[11px] text-blue-500 mt-3 font-medium">
                                *Metode di atas akan muncul otomatis di aplikasi DANA saat pembayaran.
                            </p>
                        </div>
                    </div>

                    <hr class="border-gray-100 my-8">

                    {{-- BAGIAN 2: PILIH METODE PEMBAYARAN (GRID CARD) --}}
                    <div>
                        <h4 class="text-lg font-bold text-gray-800 mb-6">Pilih Metode Pembayaran</h4>

                        {{-- 1. GROUP MANUAL & LAINNYA --}}
                        <div class="mb-8">
                            <h5 class="text-sm font-bold text-gray-500 uppercase tracking-wider mb-4 border-l-4 border-gray-300 pl-3">Transfer & E-Wallet Utama</h5>
                            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">

                                {{-- DANA --}}
                                <label class="relative cursor-pointer group">
                                    <input type="radio" name="payment_method" value="DANA" class="peer sr-only">
                                    <div class="h-full p-4 bg-white border-2 border-gray-200 rounded-xl hover:border-blue-400 peer-checked:border-blue-600 peer-checked:bg-blue-50 transition-all flex flex-col items-center justify-center text-center shadow-sm hover:shadow-md">
                                        <img src="{{ asset('assets/dana.webp') }}" class="h-10 w-10 object-contain mb-3 rounded-md" onerror="this.src='https://upload.wikimedia.org/wikipedia/commons/7/72/Logo_dana_blue.svg'">
                                        <span class="text-sm font-bold text-gray-700 group-hover:text-blue-600">DANA</span>
                                        <span class="text-[10px] text-gray-400 mt-1">Direct Debit</span>
                                        {{-- Checkmark Icon --}}
                                        <div class="absolute top-2 right-2 text-blue-600 opacity-0 peer-checked:opacity-100 transition-opacity">
                                            <i class="fas fa-check-circle"></i>
                                        </div>
                                    </div>
                                </label>

                                {{-- TRANSFER MANUAL --}}
                                <label class="relative cursor-pointer group">
                                    <input type="radio" name="payment_method" value="TRANSFER_MANUAL" class="peer sr-only">
                                    <div class="h-full p-4 bg-white border-2 border-gray-200 rounded-xl hover:border-blue-400 peer-checked:border-blue-600 peer-checked:bg-blue-50 transition-all flex flex-col items-center justify-center text-center shadow-sm hover:shadow-md">
                                        <div class="h-10 w-10 bg-gray-100 rounded-md flex items-center justify-center mb-3 text-gray-600">
                                            <i class="fas fa-university fa-lg"></i>
                                        </div>
                                        <span class="text-sm font-bold text-gray-700 group-hover:text-blue-600">Transfer Bank</span>
                                        <span class="text-[10px] text-gray-400 mt-1">Cek Manual</span>
                                        <div class="absolute top-2 right-2 text-blue-600 opacity-0 peer-checked:opacity-100 transition-opacity">
                                            <i class="fas fa-check-circle"></i>
                                        </div>
                                    </div>
                                </label>

                                {{-- DOKU --}}
                                <label class="relative cursor-pointer group">
                                    <input type="radio" name="payment_method" value="DOKU_JOKUL" class="peer sr-only">
                                    <div class="h-full p-4 bg-white border-2 border-gray-200 rounded-xl hover:border-blue-400 peer-checked:border-blue-600 peer-checked:bg-blue-50 transition-all flex flex-col items-center justify-center text-center shadow-sm hover:shadow-md">
                                        <img src="https://doku.com/Logo_DOKU-01.png" class="h-8 object-contain mb-3 mt-2 rounded-md">
                                        <span class="text-sm font-bold text-gray-700 group-hover:text-blue-600">DOKU</span>
                                        <span class="text-[10px] text-gray-400 mt-1">Gateway</span>
                                        <div class="absolute top-2 right-2 text-blue-600 opacity-0 peer-checked:opacity-100 transition-opacity">
                                            <i class="fas fa-check-circle"></i>
                                        </div>
                                    </div>
                                </label>
                            </div>
                        </div>

                        {{-- 2. LOOPING DATA TRIPAY (DINAMIS DARI CONTROLLER) --}}
                        @if(isset($groupedChannels) && count($groupedChannels) > 0)
                            @foreach($groupedChannels as $groupName => $channels)
                                <div class="mb-8">
                                    <h5 class="text-sm font-bold text-gray-500 uppercase tracking-wider mb-4 border-l-4 border-blue-500 pl-3">
                                        {{ $groupName }}
                                    </h5>

                                    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                                        @foreach($channels as $channel)
                                            @if($channel['active'])
                                            <label class="relative cursor-pointer group">
                                                <input type="radio" name="payment_method" value="{{ $channel['code'] }}" class="peer sr-only">

                                                <div class="h-full p-4 bg-white border-2 border-gray-200 rounded-xl hover:border-blue-400 peer-checked:border-blue-600 peer-checked:bg-blue-50 transition-all flex flex-col items-center justify-center text-center shadow-sm hover:shadow-md">

                                                    {{-- GAMBAR DARI API TRIPAY (icon_url) --}}
                                                    <img src="{{ $channel['icon_url'] }}" alt="{{ $channel['name'] }}" class="h-10 w-auto object-contain mb-3 rounded-md grayscale group-hover:grayscale-0 peer-checked:grayscale-0 transition-all">

                                                    <span class="text-xs font-bold text-gray-700 leading-tight group-hover:text-blue-600">
                                                        {{ $channel['name'] }}
                                                    </span>

                                                    {{-- Fee --}}
                                                    <span class="text-[10px] text-gray-400 mt-1 bg-gray-100 px-2 py-0.5 rounded-full">
                                                        Biaya: Rp {{ number_format($channel['total_fee']['flat'] ?? 0, 0, ',', '.') }}
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
                            {{-- Fallback jika API Tripay Error --}}
                            <div class="p-4 bg-yellow-50 text-yellow-800 rounded-xl border border-yellow-200 text-sm flex items-center">
                                <i class="fas fa-exclamation-triangle mr-2"></i>
                                <span>Gagal memuat metode pembayaran otomatis. Silakan gunakan Transfer Manual atau DOKU.</span>
                            </div>
                        @endif

                    </div>

                    {{-- TOMBOL BAYAR --}}
                    <div class="mt-10 pt-6 border-t border-gray-100">
                        <button type="submit" class="w-full py-4 px-6 rounded-xl shadow-lg shadow-blue-500/30 text-lg font-bold text-white bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 focus:outline-none focus:ring-4 focus:ring-blue-300 transition-all transform hover:-translate-y-1 active:translate-y-0">
                            <i class="fas fa-shield-alt mr-2"></i> Bayar Sekarang Aman
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- SCRIPT AJAX CONSULT PAY DANA (JANGAN DIUBAH LOGICNYA) --}}
    @push('scripts')
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            let typingTimer;
            const doneTypingInterval = 800;
            const $input = $('#amount');
            const $previewArea = $('#payment-methods-preview');
            const $iconArea = $('#payment-icons');

            // --- KAMUS LOGO (SESUAI REQUEST: JANGAN UBAH) ---
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
                if ($input.val()) typingTimer = setTimeout(cekPromoDana, doneTypingInterval);
            });

            $input.on('change', function () {
                clearTimeout(typingTimer);
                cekPromoDana();
            });

            function cekPromoDana() {
                let nominal = $input.val();

                if(nominal < 10000) {
                    $previewArea.addClass('hidden');
                    return;
                }

                $previewArea.removeClass('hidden');
                $iconArea.html('<span class="text-xs text-blue-600 font-medium animate-pulse">Memuat promo...</span>');

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
                                let badgeColor = (item.promo === 'Ada Promo')
                                    ? 'bg-gradient-to-r from-green-50 to-green-100 text-green-800 border-green-200'
                                    : 'bg-white text-gray-600 border-gray-200';

                                let cleanName = item.method.replace(/_/g, ' ')
                                                           .replace('VIRTUAL ACCOUNT', 'VA')
                                                           .replace('NETWORK PAY PG', '')
                                                           .replace('DIRECT DEBIT', '')
                                                           .trim();

                                let filename = logoMap[item.method];
                                let iconHtml = '';

                                if (filename) {
                                    let assetUrl = "{{ asset('assets') }}/" + filename;
                                    // Style: Kotak (rounded-md)
                                    iconHtml = `<img src="${assetUrl}" class="h-5 mr-2 object-contain rounded-md" onerror="this.style.display='none'">`;
                                } else {
                                    iconHtml = `<i class="fas fa-credit-card mr-2 text-gray-400"></i>`;
                                }

                                let html = `
                                    <div class="px-3 py-2 border rounded-lg text-xs font-bold ${badgeColor} flex items-center shadow-sm">
                                        ${iconHtml}
                                        <span>${cleanName}</span>
                                        ${item.promo === 'Ada Promo' ? '<span class="ml-2 bg-green-600 text-white text-[9px] px-1.5 py-0.5 rounded uppercase">Promo</span>' : ''}
                                    </div>
                                `;
                                $iconArea.append(html);
                            });

                        } else {
                            $iconArea.html('<span class="text-xs text-gray-400 italic">Metode pembayaran standar tersedia.</span>');
                        }
                    },
                    error: function(xhr) {
                        console.error("Consult Pay Error:", xhr.responseText);
                        $iconArea.html('<span class="text-xs text-red-400">Gagal cek promo DANA.</span>');
                    }
                });
            }
        });
    </script>
    @endpush
@endsection
