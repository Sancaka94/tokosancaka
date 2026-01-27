@extends('layouts.customer')

@section('title', 'Top Up Saldo')

@section('content')
    <h3 class="text-3xl font-semibold text-gray-700">Top Up Saldo</h3>

    <div class="mt-8">
        <div class="max-w-4xl mx-auto bg-white rounded-lg shadow-lg">
            <div class="p-6 md:p-8">

                {{-- Alert Error --}}
                @if ($errors->any())
                    <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                        <strong class="font-bold">Oops!</strong>
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

                <form action="{{ route('customer.topup.store') }}" method="POST">
                    @csrf

                    {{-- 1. INPUT NOMINAL --}}
                    <div class="mb-8">
                        <label for="amount" class="block text-sm font-medium text-gray-700 mb-2">Jumlah Top Up</label>
                        <div class="relative rounded-md shadow-sm">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <span class="text-gray-500 sm:text-sm font-bold">Rp</span>
                            </div>
                            <input type="number" name="amount" id="amount" class="focus:ring-indigo-500 focus:border-indigo-500 block w-full pl-12 pr-12 py-3 text-lg font-bold border-gray-300 rounded-md" placeholder="10000" min="10000" required value="{{ old('amount') }}">
                        </div>
                        <p class="mt-2 text-xs text-gray-500">Minimal top up adalah Rp 10.000.</p>

                        {{-- Area Preview Consult Pay DANA (AJAX) --}}
                        <div id="payment-methods-preview" class="mt-4 p-3 bg-blue-50 rounded-lg border border-blue-100 hidden transition-all">
                            <div class="flex items-center mb-3">
                                {{-- Logo DANA Header --}}
                                <img src="https://tokosancaka.com/public/storage/logo/dana.png" class="h-5 mr-2" alt="DANA">
                                <span class="text-xs font-bold text-blue-800 uppercase tracking-wide">
                                    Estimasi Promo (Jika bayar pakai DANA):
                                </span>
                            </div>

                            {{-- Container Icon --}}
                            <div id="payment-icons" class="flex flex-wrap gap-2">
                                {{-- Icon Logo Bank akan muncul di sini via AJAX --}}
                            </div>

                            <p class="text-[10px] text-blue-400 mt-2 italic">
                                *Metode di atas akan muncul otomatis di aplikasi DANA saat pembayaran.
                            </p>
                        </div>
                    </div>

                    {{-- 2. PILIH METODE PEMBAYARAN (DINAMIS DARI CONTROLLER) --}}
                    <div class="mb-8">
                        <label class="block text-gray-700 font-bold mb-4">Pilih Metode Pembayaran</label>

                        {{-- Opsi Transfer Manual --}}
                        <div class="mb-6">
                            <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Transfer Manual</h3>
                            <label class="cursor-pointer relative group block">
                                <input type="radio" name="payment_method" value="TRANSFER_MANUAL" class="peer sr-only">
                                <div class="p-4 border border-gray-200 rounded-xl hover:bg-gray-50 peer-checked:border-indigo-600 peer-checked:bg-indigo-50 peer-checked:ring-1 peer-checked:ring-indigo-600 transition-all flex items-center">
                                    <div class="w-10 h-10 bg-indigo-100 rounded-full flex items-center justify-center mr-3 text-indigo-600">
                                        <i class="fas fa-university"></i>
                                    </div>
                                    <div>
                                        <h4 class="font-bold text-gray-800 text-sm">Transfer Bank (Manual)</h4>
                                        <p class="text-xs text-gray-500">Cek manual oleh admin, upload bukti transfer.</p>
                                    </div>
                                </div>
                            </label>
                        </div>

                        {{-- Opsi DOKU --}}
                        <div class="mb-6">
                            <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">DOKU Payment Gateway</h3>
                            <label class="cursor-pointer relative group block">
                                <input type="radio" name="payment_method" value="DOKU_JOKUL" class="peer sr-only">
                                <div class="p-4 border border-gray-200 rounded-xl hover:bg-gray-50 peer-checked:border-indigo-600 peer-checked:bg-indigo-50 peer-checked:ring-1 peer-checked:ring-indigo-600 transition-all flex items-center">
                                    <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center mr-3">
                                        <img src="https://tokosancaka.com/public/storage/logo/doku-ewallet.png" class="h-6 object-contain" alt="DOKU">
                                    </div>
                                    <div>
                                        <h4 class="font-bold text-gray-800 text-sm">DOKU Checkout</h4>
                                        <p class="text-xs text-gray-500">QRIS, VA, E-Wallet lengkap via DOKU.</p>
                                    </div>
                                </div>
                            </label>
                        </div>

                        {{-- Opsi Tripay (Dinamis) --}}
                        @if(isset($groupedChannels) && count($groupedChannels) > 0)
                            @foreach($groupedChannels as $groupName => $channels)
                                <div class="mb-6">
                                    <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">
                                        {{ $groupName }} (Tripay)
                                    </h3>

                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                        @foreach($channels as $channel)
                                            @if($channel['active'])
                                            <label class="cursor-pointer relative group">
                                                <input type="radio" name="payment_method" value="{{ $channel['code'] }}" class="peer sr-only">

                                                <div class="p-3 border border-gray-200 rounded-xl hover:shadow-sm peer-checked:border-indigo-600 peer-checked:bg-indigo-50 peer-checked:ring-1 peer-checked:ring-indigo-600 transition-all h-full flex items-center">

                                                    <div class="w-12 h-8 flex items-center justify-center mr-3 bg-white rounded border border-gray-100 p-1">
                                                        <img src="{{ $channel['icon_url'] }}" alt="{{ $channel['name'] }}" class="max-h-full max-w-full object-contain">
                                                    </div>

                                                    <div class="flex-grow">
                                                        <h4 class="font-bold text-gray-800 text-xs">{{ $channel['name'] }}</h4>
                                                        <p class="text-[10px] text-gray-500">
                                                            Fee: Rp {{ number_format($channel['total_fee']['flat'] ?? 0, 0, ',', '.') }}
                                                        </p>
                                                    </div>
                                                </div>
                                            </label>
                                            @endif
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        @else
                            <div class="p-4 bg-yellow-50 text-yellow-700 text-sm rounded-lg border border-yellow-200">
                                <i class="fas fa-info-circle mr-1"></i> Metode pembayaran otomatis (Tripay) sedang tidak dapat dimuat. Silakan gunakan Transfer Manual atau DOKU.
                            </div>
                        @endif

                    </div>

                    {{-- Tombol Submit --}}
                    <div class="mt-8">
                        <button type="submit" class="w-full flex justify-center py-4 px-4 border border-transparent rounded-xl shadow-md text-base font-bold text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition duration-150 ease-in-out transform hover:-translate-y-1">
                            <i class="fas fa-lock mr-2 mt-1"></i> Lanjutkan Pembayaran
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- SCRIPT AJAX CONSULT PAY DENGAN LOGO MAPPING --}}
    @push('scripts')
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            let typingTimer;
            const doneTypingInterval = 800;
            const $input = $('#amount');
            const $previewArea = $('#payment-methods-preview');
            const $iconArea = $('#payment-icons');

            // --- 1. KAMUS LOGO (MENGEMBALIKAN GAMBAR ANDA) ---
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
                $iconArea.html('<span class="text-xs text-gray-500"><i class="fas fa-spinner fa-spin"></i> Mengecek metode DANA...</span>');

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
                                let badgeColor = (item.promo === 'Ada Promo') ? 'bg-green-100 text-green-700 border-green-200 ring-1 ring-green-400' : 'bg-white text-gray-600 border-gray-200';

                                // Bersihkan Nama Metode
                                let cleanName = item.method.replace(/_/g, ' ')
                                                           .replace('VIRTUAL ACCOUNT', 'VA')
                                                           .replace('NETWORK PAY PG', '')
                                                           .replace('DIRECT DEBIT', '')
                                                           .trim();

                                // Cari Gambar dari LogoMap
                                let filename = logoMap[item.method];
                                let iconHtml = '';

                                if (filename) {
                                    // Jika ada gambar di map, tampilkan gambar
                                    let assetUrl = "{{ asset('assets') }}/" + filename;
                                    iconHtml = `<img src="${assetUrl}" class="h-4 mr-1 object-contain" onerror="this.style.display='none'">`;
                                } else {
                                    // Jika tidak ada, tampilkan icon default
                                    iconHtml = `<i class="fas fa-credit-card mr-1 text-gray-400"></i>`;
                                }

                                let html = `
                                    <div class="px-2 py-1 border rounded text-[10px] font-bold ${badgeColor} flex items-center shadow-sm">
                                        ${iconHtml}
                                        <span>${cleanName}</span>
                                        ${item.promo === 'Ada Promo' ? '<i class="fas fa-tag ml-1 text-green-600 animate-pulse"></i>' : ''}
                                    </div>
                                `;
                                $iconArea.append(html);
                            });

                        } else {
                            $iconArea.html('<span class="text-[10px] text-gray-400 italic">Metode pembayaran standar tersedia.</span>');
                        }
                    },
                    error: function(xhr) {
                        console.error("Consult Pay Error:", xhr.responseText);
                        $iconArea.html('<span class="text-[10px] text-red-300">Gagal cek promo DANA.</span>');
                    }
                });
            }
        });
    </script>
    @endpush
@endsection
