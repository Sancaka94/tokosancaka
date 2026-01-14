@extends('layouts.customer')

{{-- LENGKAPI: Menambahkan section title --}}
@section('title', 'Top Up Saldo')

@section('content')
    <h3 class="text-3xl font-semibold text-gray-700">Top Up Saldo</h3>

    <div class="mt-8">
        {{-- ========================================================== --}}
        {{-- PERBAIKAN: Inisialisasi paymentMethod dengan old() --}}
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
                            {{-- Tambahkan ID="amount" agar bisa dibaca JQuery --}}
                            <input type="number" name="amount" id="amount" class="focus:ring-blue-500 focus:border-blue-500 block w-full pl-10 pr-12 sm:text-sm border-gray-300 rounded-md" placeholder="10000" min="10000" required value="{{ old('amount') }}">
                        </div>
                        <p class="mt-2 text-xs text-gray-500">Minimal top up adalah Rp 10.000.</p>

                        {{-- Area untuk menampilkan hasil Consult Pay (Logo Payment yang tersedia) --}}
                        <div id="payment-methods-preview" class="mt-2 hidden">
                            <span class="text-xs font-semibold text-gray-600">Metode Tersedia Untuk DANA:</span>
                            <div id="payment-icons" class="flex flex-wrap gap-2 mt-1">
                                {{-- Icon akan muncul di sini via AJAX --}}
                            </div>
                        </div>
                    </div>


                    <div class="space-y-6">
                        <div class="mb-6">
                            <label for="payment_method" class="block text-sm font-medium text-gray-700">Pilih Metode Pembayaran</label>

                            <select id="payment_method" name="payment_method" x-model="paymentMethod"
                                    class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300
                                           focus:outline-none focus:ring-blue-500 focus:border-blue-500
                                           sm:text-sm rounded-md" required>

                                <option value="" disabled>-- Pilih Metode Pembayaran --</option>

                                {{-- Grup 1: Manual --}}
                                <optgroup label="Transfer Manual (Konfirmasi Admin)">
                                    <option value="TRANSFER_MANUAL">Transfer Bank (Upload Bukti)</option>
                                </optgroup>

                                {{-- Grup 2: DOKU --}}
                                <optgroup label="DOKU (Otomatis)">
                                    <option value="DOKU_JOKUL">DOKU (Semua Bank, E-Wallet, QRIS)</option>
                                </optgroup>

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
                            </select>
                        </div>
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

    {{--
       SCRIPT AJAX UNTUK CONSULT PAY (GAPURA)
       Pastikan layout Anda memuat jQuery. Jika tidak, tambahkan CDN jQuery di head layout.
    --}}

    {{-- Jika error, ganti @push('scripts') dengan <script> biasa --}}
    @push('scripts')
    {{-- Pastikan jQuery sudah dimuat --}}
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            let typingTimer;
            const doneTypingInterval = 1000; // Delay 1 detik
            const $input = $('#amount');
            const $previewArea = $('#payment-methods-preview');
            const $iconArea = $('#payment-icons');

            // Event Listener saat mengetik
            $input.on('keyup', function () {
                clearTimeout(typingTimer);
                if ($input.val()) {
                    typingTimer = setTimeout(cekMetodePembayaran, doneTypingInterval);
                }
            });

            // Event Listener saat nilai berubah (misal copas/spinner)
            $input.on('change', function () {
                clearTimeout(typingTimer);
                cekMetodePembayaran();
            });

            function cekMetodePembayaran() {
                let nominal = $input.val();

                // [LOG 1] Cek Nominal Awal
                console.log('[FRONTEND] Cek Nominal:', nominal);

                if(nominal < 10000) {
                    console.log('[FRONTEND] Nominal kurang dari 10.000, batalkan request.');
                    $previewArea.addClass('hidden');
                    return;
                }

                // Tampilkan Loading
                $iconArea.html('<span class="text-gray-400 text-xs animate-pulse">Sedang mengecek promo & metode pembayaran...</span>');
                $previewArea.removeClass('hidden');

                // [LOG 2] Mulai Mengirim Request AJAX
                console.log('[FRONTEND] Mengirim request ke: {{ route("topup.consult") }}');

                $.ajax({
                    url: "{{ route('topup.consult') }}",
                    method: "POST",
                    dataType: "json", // Memaksa respon harus JSON
                    data: {
                        _token: "{{ csrf_token() }}",
                        amount: nominal
                    },
                    success: function(response) {
                        console.log('[FRONTEND] Response Sukses Diterima:', response);
                        $iconArea.empty();

                        if(response.success && response.data.length > 0) {

                            // 1. KAMUS MAPPING: Kode API DANA -> Nama File Anda
                            // Sesuaikan persis dengan nama file di folder assets Anda
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
                                'NETWORK_PAY_PG_SPAY':      'shopeepay.webp', // Mapping khusus SPAY -> shopeepay
                                'NETWORK_PAY_PG_LINKAJA':   'linkaja.png',
                                'NETWORK_PAY_PG_DANA':      'dana.webp',
                                'NETWORK_PAY_PG_CARD':      'card.png',

                                'BALANCE':                  'saldo.png', // Mapping BALANCE -> saldo.png
                                'CARD':                     'other.png'  // Fallback untuk Kartu
                            };

                            // 2. Loop Data
                            $.each(response.data, function(index, item) {
                                let apiCode = item.option; // Contoh: VIRTUAL_ACCOUNT_BNI
                                let cleanName = apiCode.replace(/_/g, ' ').replace('VIRTUAL ACCOUNT', 'VA').replace('NETWORK PAY PG', '');

                                // Cek apakah ada di kamus mapping?
                                let filename = logoMap[apiCode];

                                // Tentukan Konten Kartu (Gambar atau Teks)
                                let cardContent = '';

                                if (filename) {
                                    // Jika gambar ditemukan di mapping
                                    let logoUrl = "{{ asset('assets') }}/" + filename;
                                    cardContent = `<img src="${logoUrl}" alt="${cleanName}" class="h-8 object-contain mb-1">`;
                                } else {
                                    // Jika gambar TIDAK ada (Fallback ke Icon Teks)
                                    cardContent = `<span class="text-xs font-bold text-gray-600 border rounded px-1">${cleanName.substring(0,4)}</span>`;
                                }

                                // Template HTML Card
                                let badge = `
                                <div class="inline-flex flex-col items-center justify-center p-3 m-1 bg-white border border-gray-200 rounded-lg shadow-sm w-32 h-24 text-center hover:border-blue-500 hover:bg-blue-50 transition-all cursor-default" title="${item.option}">
                                    <div class="flex-grow flex items-center justify-center">
                                        ${cardContent}
                                    </div>
                                    <span class="text-[11px] text-gray-700 leading-tight font-medium mt-2 w-full whitespace-normal break-words">${cleanName}</span>
                                </div>
                            `;
                            $iconArea.append(badge);

                            });

                        } else {
                            $iconArea.html('<span class="text-gray-500 text-xs">Standar (Tidak ada promo khusus).</span>');
                        }
                    },
                    error: function(xhr, status, error) {
                        // [LOG 4] Error Terjadi
                        console.error('================ [ERROR LOG] ================');
                        console.error('Status:', status);
                        console.error('Error Thrown:', error);
                        console.error('Response Text (Server):', xhr.responseText);
                        console.error('Response Code:', xhr.status);

                        // Coba parse JSON error jika ada message dari controller
                        let errorMsg = 'Gagal memuat info promo.';
                        try {
                            let jsonResp = JSON.parse(xhr.responseText);
                            if(jsonResp.message) {
                                errorMsg = jsonResp.message;
                                console.error('Pesan dari Controller:', jsonResp.message);
                            }
                        } catch(e) {
                            console.error('Respon bukan JSON valid.');
                        }
                        console.error('=============================================');

                        // Tampilkan pesan error ke user (kecil saja)
                        $iconArea.html(`<span class="text-red-400 text-xs">Info: ${errorMsg}</span>`);
                    }
                });
            }
        });
    </script>
    @endpush

@endsection
