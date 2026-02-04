@extends('layouts.marketplace')

@section('title', 'Checkout - Sancaka Marketplace')

@push('styles')
    {{-- Tailwind CSS dan Font sudah ada di sini --}}
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    {{-- Font Awesome (Dibutuhkan untuk ikon) --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .form-radio:checked {
            background-image: url("data:image/svg+xml,%3csvg viewBox='0 0 16 16' fill='white' xmlns='http://www.w3.org/2000/svg'%3e%3ccircle cx='8' cy='8' r='3'/%3e%3c/svg%3e");
            border-color: transparent;
            background-color: currentColor;
            background-size: 100% 100%;
            background-position: center;
            background-repeat: no-repeat;
        }
        /* Style untuk tombol non-aktif */
        button:disabled {
            cursor: not-allowed;
            background-color: #9ca3af; /* Tailwind gray-400 */
            opacity: 0.7;
        }
        /* Style untuk tab aktif */
        .group-button.active {
            background-color: #dc2626; /* GANTI KE Tailwind red-600 */
            color: white;
            border-color: #dc2626; /* GANTI KE Tailwind red-600 */
        }
    </style>
@endpush

@section('content')


<div class="bg-gray-100">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-12">

        <div class="mb-8 text-center">
            <h1 class="text-3xl font-extrabold text-gray-900">Checkout</h1>
            <p class="mt-2 text-sm text-gray-500">Selesaikan pesanan Anda dalam beberapa langkah mudah.</p>
        </div>

        {{-- ====================================================================== --}}
        {{-- ================== BLOK NOTIFIKASI (SUDAH TAILWIND) ================== --}}
        {{-- ====================================================================== --}}
@if ($errors->any())
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
        <strong class="font-bold"><i class="fas fa-exclamation-triangle"></i> Gagal!</strong>
        <span class="block sm:inline">Ada beberapa kesalahan pada input Anda:</span>
        <ul class="list-disc list-inside mt-2">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

@if (session('error'))
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
        <strong class="font-bold"><i class="fas fa-times-circle"></i> Error!</strong>
        <span class="block sm:inline">{{ session('error') }}</span>
    </div>
@endif

@if (session('success'))
    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
        <strong class="font-bold"><i class="fas fa-check-circle"></i> Sukses!</strong>
        <span class="block sm:inline">{{ session('success') }}</span>
    </div>
@endif

@if (session('info'))
    <div class="bg-blue-100 border border-blue-400 text-blue-700 px-4 py-3 rounded relative mb-4" role="alert">
        <strong class="font-bold"><i class="fas fa-info-circle"></i> Info:</strong>
        <span class="block sm:inline">{{ session('info') }}</span>
    </div>
@endif

@if (session('warning'))
    <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded relative mb-4" role="alert">
        <strong class="font-bold"><i class="fas fa-exclamation-triangle"></i> Peringatan:</strong>
        <span class="block sm:inline">{{ session('warning') }}</span>
    </div>
@endif
        {{-- ====================================================================== --}}
        {{-- =================== AKHIR BLOK NOTIFIKASI ================== --}}
        {{-- ====================================================================== --}}

        <form id="checkout-form" action="{{ route('checkout.store') }}" method="POST">
            @csrf

             <!-- ====================================================== -->
            <!-- === KODE GPS (INPUT TERSEMBUNYI) DITARUH DI SINI === -->
            <!-- ====================================================== -->
            <input type="hidden" name="latitude" id="latitude">
            <input type="hidden" name="longitude" id="longitude">
            <!-- ====================================================== -->

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

                <!-- Kolom Kiri: Alamat, Pengiriman, Pembayaran -->
                <div class="lg:col-span-2 space-y-8">

                    <!-- Alamat Pengiriman -->
                    <div class="bg-white rounded-xl shadow-md p-6">
                        <h2 class="text-lg font-bold text-gray-900 mb-4">Alamat Pengiriman</h2>
                        <div class="border border-gray-200 rounded-lg p-4">
                            @php
                                $user = Auth::user();
                                $alamat = $user->address_detail ?? 'Mohon lengkapi alamat Anda.';
                            @endphp
                            <p class="font-semibold">{{ $user->nama_lengkap }}</p>
                            <p class="text-sm text-gray-600">{{ $user->no_wa }}</p>
                            <p class="text-sm text-gray-600 mt-2">{{ $alamat }}</p>
                            <a href="{{ route('customer.profile.edit') }}?redirect_to=checkout" class="text-sm text-red-600 hover:underline mt-2 inline-block">Ubah Alamat</a>
                        </div>
                    </div>

                    <!-- Opsi Pengiriman -->
                   <div class="bg-white rounded-xl shadow-md p-6">
                        <h2 class="text-lg font-bold text-gray-900 mb-4">Pilih Metode Pengiriman</h2>
                        <div class="space-y-4">

                        {{-- ====================================================================== --}}
                        {{-- ================== AWAL BLOK PENGIRIMAN ================== --}}
                        {{-- ====================================================================== --}}
                        @php
                            // 1. Ambil hasil Express (sudah bersih dari controller)
                            $expressResults = collect($expressOptions['results'] ?? []);

                            // 2. Ambil hasil Instant (sudah bersih dari controller)
                            $instantResults = collect([]); // Default ke array kosong
                            if (isset($instantOptions['status']) && $instantOptions['status'] === true && isset($instantOptions['results'])) {
                                $instantResults = collect($instantOptions['results']);
                            }

                            // 3. Gabungkan keduanya
                            $allResults = $expressResults->merge($instantResults);

                            // 4. Buat grup. Gunakan 'group' key yang sudah ada.
                            $groupedOptions = $allResults->groupBy('group');

                            // 5. Pastikan urutan tab benar
                            $groupOrder = ['Regular', 'One Day', 'Instant', 'Cargo', 'Trucking'];
                            $finalGrouped = collect($groupOrder)
                                ->mapWithKeys(function($key) use ($groupedOptions) {
                                    $key = \Illuminate\Support\Str::title($key);
                                    return [$key => $groupedOptions->get(strtolower($key), collect())];
                                })
                                ->filter(fn($group) => $group->isNotEmpty()); // Hapus tab yang kosong

                            $firstActiveGroup = $finalGrouped->keys()->first();
                        @endphp

                        <div class="flex flex-wrap gap-2 mb-4">
                            @forelse($finalGrouped as $group => $options)
                                <button type="button"
                                        class="px-4 py-2 rounded-lg border hover:bg-gray-100 group-button {{ $group === $firstActiveGroup ? 'active' : '' }}"
                                        data-group="{{ $group }}">
                                    {{ $group }}
                                </button>
                            @empty
                                <p class="text-sm text-gray-500">Tidak ada opsi pengiriman yang tersedia untuk alamat Anda.</p>
                            @endforelse
                        </div>

                        @foreach($finalGrouped as $group => $options)
                            <div class="shipping-group-options {{ $group !== $firstActiveGroup ? 'hidden' : '' }}" data-group="{{ $group }}">
                                @php
                                    $sortedOptions = $options->sortBy('final_price')->values();
                                @endphp

                                @foreach($sortedOptions as $i => $option)
                                    @php
                                        $serviceName = $option['service_name'];
                                        $service = $option['service'];
                                        $serviceType = $option['service_type'];
                                        $etd = $option['etd'];
                                        $finalPrice = $option['final_price'];

                                        // ======================================================
                                        // ==== INI ADALAH PERBAIKAN UNTUK ERROR DI SCREENSHOT ANDA ====
                                        // ==== SAYA TETAP MENGGUNAKAN 'insurance_cost' BUKAN 'insurance' ====
                                        $insurance = $option['insurance_cost'] ?? 0;
                                        // ======================================================

                                        $codAvailable = $option['cod_available'] ?? false;
                                        $codFee = $option['cod_fee'] ?? 0;
                                        $groupName = $option['group'];

                                        $logoName = strtolower(str_replace(' ', '', $service)) . '.png';

                                        $value = sprintf('%s-%s-%s-%d-%d-%d',
                                            strtolower($groupName),
                                            $service,
                                            $serviceType,
                                            $finalPrice,
                                            $insurance,
                                            $codFee
                                        );
                                    @endphp
                                    <label class="flex items-center border border-gray-200 p-4 rounded-lg cursor-pointer hover:bg-gray-50 has-[:checked]:bg-red-50 has-[:checked]:border-red-500 mb-2">
                                        <input
                                            type="radio"
                                            name="shipping_method"
                                            value="{{ $value }}"
                                            class="form-radio h-5 w-5 text-red-600"
                                            data-cost="{{ $finalPrice }}"
                                            data-insurance="{{ $insurance }}"
                                            data-cod="{{ $codAvailable ? 'true' : 'false' }}"
                                            data-cod-fee="{{ $codFee }}"
                                            {{ $group === $firstActiveGroup && $loop->first ? 'checked' : '' }}
                                        >
                                        <div class="ml-4 flex justify-between w-full items-center">
                                            <div class="flex items-center gap-3">
                                                <img src="{{ asset('public/storage/logo-ekspedisi/'.$logoName) }}"
                                                     alt="{{ $serviceName }}"
                                                     class="w-8 h-8 object-contain"
                                                     onerror="this.style.display='none'">
                                                <div>
                                                    <span class="text-sm font-medium text-gray-900"><strong>{{ $serviceName }}</strong></span>
                                                    <span class="block text-xs text-gray-500">
                                                        Estimasi In Syaa Allah: {{ $etd }}
                                                        @if( !\Illuminate\Support\Str::contains($etd, ['menit', 'minutes', 'Jam', 'hours',]) )
                                                            Hari
                                                        @endif
                                                    </span>
                                                </div>
                                            </div>
                                            <span class="text-sm font-medium text-gray-900">
                                                Rp{{ number_format($finalPrice, 0, ',', '.') }}
                                            </span>
                                        </div>
                                    </label>
                                @endforeach
                            </div>
                        @endforeach
                        {{-- ====================================================================== --}}
                        {{-- =================== AKHIR BLOK PENGIRIMAN ================== --}}
                        {{-- ====================================================================== --}}

                        </div>
                   </div>

                    <!-- Opsi Pembayaran -->
                    <div class="bg-white rounded-xl shadow-md p-6">
                        <h2 class="text-lg font-bold text-gray-900 mb-4">Pilih Metode Pembayaran</h2>
                        <div class="w-full">
                            {{-- ====================================================================== --}}
                            {{-- ================== PENGEMBALIAN KODE PEMBAYARAN DEFAULT ================== --}}
                            {{-- ====================================================================== --}}
                            <button type="button" id="paymentMethodButton" class="flex items-center justify-between w-full border border-gray-200 p-4 rounded-lg cursor-pointer hover:bg-gray-50 focus:outline-none">
                                <div class="flex items-center">
                                    <img id="paymentMethodImg" src="{{ asset('public/assets/permata.webp') }}" alt="Permata Logo" class="h-8 w-8 object-contain mr-4">
                                    <span id="paymentMethodLabel" class="text-sm font-medium text-gray-900">Permata Virtual Account</span>
                                </div>
                                <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </button>
                            <input type="hidden" name="payment_method" id="payment_method" value="PERMATAVA">
                            {{-- ====================================================================== --}}
                            {{-- ================== AKHIR PENGEMBALIAN KODE ================== --}}
                            {{-- ====================================================================== --}}
                        </div>
                    </div>

                </div>

                <!-- Kolom Kanan: Ringkasan Pesanan -->
                <div class="lg:col-span-1">
                    <div class="bg-white rounded-xl shadow-md p-6 sticky top-24">
                        <h2 class="text-lg font-medium text-gray-900">Ringkasan Pesanan</h2>

                        <ul role="list" class="divide-y divide-gray-200 my-6">
                            @php $subtotal = 0; @endphp
                            @foreach(session('cart', []) as $id => $details)
                                @php $subtotal += $details['price'] * $details['quantity']; @endphp
                                <li class="flex py-4">
                                    <div class="h-16 w-16 flex-shrink-0 overflow-hidden rounded-md">
                                        {{-- ====================================================================== --}}
                                        {{-- ================== PENGEMBALIAN LINK GAMBAR PRODUK ================== --}}
                                        {{-- ====================================================================== --}}
                                        <img src="{{ url('public/storage/' . $details['image_url']) }}" alt="{{ $details['name'] }}" class="h-full w-full object-cover object-center">
                                    </div>
                                    <div class="ml-4 flex flex-1 flex-col text-sm">
                                        <h3 class="font-medium text-gray-800">{{ $details['name'] }}</h3>
                                        <p class="text-gray-500">Qty: {{ $details['quantity'] }}</p>
                                        <p class="text-gray-500 mt-auto">Rp{{ number_format($details['price'], 0, ',', '.') }}</p>
                                    </div>
                                </li>
                            @endforeach
                        </ul>

                        <div class="border-t border-gray-200 pt-6 space-y-4">
                            <div class="flex items-center justify-between">
                                <dt class="text-sm text-gray-600">Subtotal</dt>
                                <dd class="text-sm font-medium text-gray-900">Rp{{ number_format($subtotal, 0, ',', '.') }}</dd>
                            </div>
                             <div class="flex items-center justify-between">
                                <dt class="text-sm text-gray-600">Ongkos Kirim</dt>
                                <dd class="text-sm font-medium text-gray-900" id="ongkos_kirim">Pilih pengiriman</dd>
                            </div>

                            <!-- Baris Asuransi (Baru) -->
                            {{-- ====================================================== --}}
                            {{-- ==== PERBAIKAN: Menghapus style="display:none;" ==== --}}
                            {{-- ====================================================== --}}
                            <div class="flex items-center justify-between" id="insurance_row">
                                <dt class="text-sm text-gray-600">
                                    <label for="use_insurance" class="cursor-pointer">Gunakan Asuransi</label>
                                </dt>
                                <dd class="flex items-center">
                                    {{-- ====================================================== --}}
                                    {{-- ==== PERBAIKAN: Memunculkan kembali <span> biaya ==== --}}
                                    {{-- ====================================================== --}}
                                    <span id="insurance_cost_text" class="text-sm font-medium text-gray-900 mr-2">Rp0</span>
                                    <input type="checkbox" id="use_insurance" name="use_insurance" class="h-4 w-4 rounded border-gray-300 text-red-600 focus:ring-red-600">
                                </dd>
                            </div>

                            <div class="flex items-center justify-between" id="cod_fee_row" style="display:none;">
                                <dt class="text-sm text-gray-600">Biaya Penanganan COD</dt>
                                <dd class="text-sm font-medium text-gray-900" id="cod_fee_text">Rp0</dd>
                            </div>
                           <div class="flex items-center justify-between border-t border-gray-200 pt-4">
                                <dt class="text-base font-bold text-gray-900">Total Pesanan</dt>
                                <dd class="text-base font-bold text-gray-900" id="total_pesanan">
                                    Rp{{ number_format($subtotal, 0, ',', '.') }}
                                </dd>
                           </div>
                        </div>

                        <div class="mt-6 border-t pt-6">
                            <div class="relative flex items-start">
                                <div class="flex h-6 items-center">
                                    <input id="terms-and-conditions" name="terms-and-conditions" type="checkbox" class="h-4 w-4 rounded border-gray-300 text-red-600 focus:ring-red-600">
                                </div>
                                <div class="ml-3 text-sm leading-6">
                                    <label for="terms-and-conditions" class="font-medium text-gray-900">
                                        Saya telah membaca dan menyetujui
                                        <a href="https://tokosancaka.com/terms-and-conditions" target="_blank" class="underline text-red-600 hover:text-red-700">Syarat & Ketentuan</a>
                                        dan
                                        <a href="https://tokosancaka.com/privacy-policy" target="_blank" class="underline text-red-600 hover:text-red-700">Kebijakan Privasi</a>
                                        yang berlaku.
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="mt-6">
                            <button type="submit" id="submit-button" class="flex w-full items-center justify-center rounded-md border border-transparent bg-red-600 px-6 py-3 text-base font-medium text-white shadow-sm hover:bg-red-700" disabled>
                                Buat Pesanan & Bayar
                            </button>
                        </div>
                    </div>
                </div>

            </div>
        </form>
    </div>
</div>

<div id="paymentModal" class="fixed inset-0 bg-gray-800 bg-opacity-75 flex items-center justify-center z-50 hidden transition-opacity">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-lg mx-4 transform transition-all">
        <div class="flex justify-between items-center p-4 border-b">
            <h3 class="text-lg font-semibold text-gray-800">Pilih Metode Pembayaran</h3>
            <button type="button" id="closeModalButton" class="text-gray-400 hover:text-gray-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>
        <div class="p-2">
            <ul id="paymentOptionsList" class="max-h-[60vh] overflow-y-auto space-y-2 p-4">

                {{-- 1. OPSI INTERNAL (SALDO) --}}
                <li class="payment-option cursor-pointer flex items-center p-4 border rounded-lg hover:bg-red-50"
                    data-value="cash"
                    data-label="Saldo Sancaka"
                    data-img="{{ asset('public/assets/saldo.png') }}">
                    <img src="{{ asset('public/assets/saldo.png') }}" class="h-8 w-8 object-contain mr-4">
                    <span class="text-sm font-medium text-gray-900">Saldo {{ Auth::user()->nama_lengkap }}: (Rp{{ number_format(Auth::user()->saldo, 0, ',', '.') }})</span>
                </li>

                {{-- 2. OPSI KHUSUS (DOKU) --}}
                <li class="payment-option cursor-pointer flex items-center p-4 border rounded-lg hover:bg-red-50"
                    data-value="DOKU_JOKUL"
                    data-label="Doku (Kartu Kredit, E-Wallet, dll)"
                    data-img="{{ asset('public/assets/doku.png') }}">
                    <img src="{{ asset('public/assets/doku.png') }}" class="h-8 w-8 object-contain mr-4">
                    <span class="text-sm font-medium text-gray-900">Rekomendasi Sancaka (Kartu Kredit, E-Wallet, dll)</span>
                </li>

                {{-- OPSI DANA (DIRECT DEBIT) --}}
                <li class="payment-option cursor-pointer flex items-center p-4 border rounded-lg hover:bg-red-50 transition-colors duration-200"
                    data-value="DANA"
                    data-label="DANA Indonesia"
                    data-img="{{ asset('public/assets/dana.webp') }}">

                    {{-- Logo DANA --}}
                    <img src="{{ asset('public/assets/dana.webp') }}"
                         alt="DANA"
                         class="h-8 w-8 object-contain mr-4"
                         onerror="this.src='https://upload.wikimedia.org/wikipedia/commons/7/72/Logo_dana_blue.svg'">

                    <div class="flex flex-col">
                        <span class="text-sm font-bold text-gray-900">DANA</span>
                        <span class="text-xs text-gray-500">Sambungkan akun DANA (Direct Debit)</span>
                    </div>

                    {{-- Badge Bebas Admin (Opsional, pemanis tampilan) --}}
                    <span class="ml-auto bg-blue-100 text-blue-800 text-[10px] font-semibold px-2 py-0.5 rounded">
                        Otomatis
                    </span>
                </li>

                {{-- 3. OPSI INTERNAL (COD) --}}
                <li class="px-2 pt-2 text-xs font-bold text-gray-500 uppercase tracking-wider">Bayar Ditempat</li>

                <li id="codPaymentOption" class="payment-option cursor-pointer flex items-center p-4 border rounded-lg hover:bg-red-50"
                    data-value="cod"
                    data-label="COD (Bayar Ongkir)"
                    data-img="{{ asset('public/assets/cod.png') }}">
                    <img src="{{ asset('public/assets/cod.png') }}" class="h-8 w-8 object-contain mr-4">
                    <span class="text-sm font-medium text-gray-900">COD (Cash on Delivery)</span>
                </li>

                <li class="payment-option cursor-pointer flex items-center p-4 border rounded-lg hover:bg-red-50"
                    data-value="CODBARANG"
                    data-label="COD BARANG"
                    data-img="{{ asset('public/assets/cod.png') }}">
                    <img src="{{ asset('public/assets/cod.png') }}" class="h-8 w-8 object-contain mr-4">
                    <span class="text-sm font-medium text-gray-900">COD BARANG</span>
                </li>

                {{-- 4. OPSI OTOMATIS DARI TRIPAY (MENGGANTIKAN MAPPING MANUAL) --}}
                @if(isset($tripayChannels) && count($tripayChannels) > 0)
                    {{-- Header Opsional untuk memisahkan --}}
                    <li class="px-2 pt-2 text-xs font-bold text-gray-500 uppercase tracking-wider">Metode Pembayaran Otomatis</li>

                    @foreach($tripayChannels as $channel)
                        <li class="payment-option cursor-pointer flex items-center p-4 border rounded-lg hover:bg-red-50"
                            data-value="{{ $channel['code'] }}"
                            data-label="{{ $channel['name'] }}"
                            data-img="{{ $channel['icon_url'] }}">

                            {{-- Logo dari Tripay --}}
                            <img src="{{ $channel['icon_url'] }}"
                                 alt="{{ $channel['name'] }}"
                                 class="h-8 w-8 object-contain mr-4"
                                 onerror="this.src='https://placehold.co/32x32?text=IMG'">

                            {{-- Nama Metode --}}
                            <span class="text-sm font-medium text-gray-900">
                                {{ $channel['name'] }}
                            </span>
                        </li>
                    @endforeach
                @else
                    <li class="p-4 text-center text-gray-500 text-sm border border-dashed rounded-lg">
                        Gagal memuat metode pembayaran otomatis.
                    </li>
                @endif

            </ul>
        </div>
        <div class="flex justify-end p-4 border-t bg-gray-50 rounded-b-lg space-x-4">
            <button type="button" id="backButton" class="px-4 py-2 bg-white border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">Kembali</button>
            <button type="button" id="continueButton" class="px-4 py-2 bg-red-600 border border-transparent rounded-md text-sm font-medium text-white hover:bg-red-700">Lanjutkan</button>
        </div>
    </div>
</div>

{{-- Modal S&K dihapus --}}

<script>
document.addEventListener('DOMContentLoaded', function () {
    const subtotal = {{ $subtotal ?? 0 }};
    const codFeePercentage = 0.03;
    let shippingCost = 0;

    // --- PERUBAHAN ASURANSI & KODE JS LENGKAP ---
    let insuranceCost = 0; // Biaya asuransi final yang ditambahkan ke total
    let availableInsuranceCost = 0; // Biaya asuransi dari kurir
    let isCodPayment = false;
    let isCodSupportedByCourier = false;
    let codAddCost = 0; // Biaya COD dari API

    const ongkirEl = document.getElementById('ongkos_kirim');
    const codFeeRow = document.getElementById('cod_fee_row');
    const codFeeTextEl = document.getElementById('cod_fee_text');

    // --- ELEMEN BARU ASURANSI ---
    const insuranceRow = document.getElementById('insurance_row');
    // ======================================================
    // ==== PERBAIKAN: Mengaktifkan kembali const ====
    // ======================================================
    const insuranceCostTextEl = document.getElementById('insurance_cost_text'); // DI-AKTIFKAN KEMBALI
    const useInsuranceCheckbox = document.getElementById('use_insurance');
    // --- AKHIR ELEMEN BARU ---

    const totalEl = document.getElementById('total_pesanan');
    const paymentMethodInput = document.getElementById('payment_method');
    const codPaymentOption = document.getElementById("codPaymentOption"); // Pastikan ID ini ada di <li> COD

    const paymentModal = document.getElementById('paymentModal');
    const paymentMethodButton = document.getElementById('paymentMethodButton');
    const closeModalButton = document.getElementById('closeModalButton');
    const backButton = document.getElementById('backButton');
    const continueButton = document.getElementById('continueButton');
    const paymentOptionsList = document.getElementById('paymentOptionsList');

    const termsCheckbox = document.getElementById('terms-and-conditions');
    const submitButton = document.getElementById('submit-button');
    const checkoutForm = document.getElementById('checkout-form');

    // Modal S&K dihapus
    // ...

    function formatRupiah(num) {
        return 'Rp' + new Intl.NumberFormat('id-ID').format(num || 0);
    }

    function updateTotal() {
        let totalCodFee = 0; // Reset biaya COD

        // ======================================================
        // ==== PERBAIKAN JS: Logika Asuransi di updateTotal ====
        // ======================================================
        // Tampilkan baris asuransi (selalu)
        insuranceRow.style.display = 'flex';

        // Hitung asuransi HANYA jika dicentang DAN tersedia
        if (useInsuranceCheckbox.checked && availableInsuranceCost > 0) {
            insuranceCost = availableInsuranceCost;
            // ======================================================
            // ==== PERBAIKAN: Memunculkan kembali <span> biaya ====
            insuranceCostTextEl.innerText = formatRupiah(insuranceCost);
            // ======================================================
        } else {
            insuranceCost = 0;
            // ======================================================
            // ==== PERBAIKAN: Reset <span> biaya jika tidak dicentang ====
            insuranceCostTextEl.innerText = 'Rp0'; // Reset ke Rp0 jika tidak dicentang
            // ======================================================
        }
        // ======================================================


        // Hitung biaya COD
        if (isCodPayment && isCodSupportedByCourier) {
            // Cek apakah API memberikan biaya COD (dari data-cod-fee)
            if (codAddCost > 0) {
                totalCodFee = codAddCost;
            } else {
                // Jika tidak, hitung manual 3%
                const baseTotal = subtotal + shippingCost + insuranceCost;
                totalCodFee = Math.ceil(baseTotal * codFeePercentage);
            }
            codFeeRow.style.display = 'flex';
            codFeeTextEl.innerText = formatRupiah(totalCodFee);
        } else {
            codFeeRow.style.display = 'none';
        }

        const grandTotal = subtotal + shippingCost + insuranceCost + totalCodFee;
        ongkirEl.innerText = formatRupiah(shippingCost);
        totalEl.innerText = formatRupiah(grandTotal);
    }

    function handleShippingChange() {
        const selectedShipping = document.querySelector('input[name="shipping_method"]:checked');

        if (!selectedShipping) {
            shippingCost = 0;
            availableInsuranceCost = 0;
            isCodSupportedByCourier = false;
            codAddCost = 0;
        } else {
            shippingCost = parseInt(selectedShipping.dataset.cost || 0, 10);
            availableInsuranceCost = parseInt(selectedShipping.dataset.insurance || 0, 10);
            isCodSupportedByCourier = selectedShipping.dataset.cod === 'true';
            codAddCost = parseInt(selectedShipping.dataset.codFee || 0, 10); // Ambil biaya COD dari API
        }

        // ======================================================
        // ==== PERBAIKAN JS: Logika Asuransi di handleShippingChange ====
        // ======================================================
        // Selalu tampilkan baris asuransi
        insuranceRow.style.display = 'flex';

        if (availableInsuranceCost > 0) {
            // Jika asuransi tersedia, aktifkan checkbox
            useInsuranceCheckbox.disabled = false;
            useInsuranceCheckbox.classList.remove('opacity-50', 'cursor-not-allowed');
            // ======================================================
            // ==== PERBAIKAN: Memunculkan kembali <span> biaya ====
            insuranceCostTextEl.innerText = formatRupiah(availableInsuranceCost);
            // ======================================================
        } else {
            // Jika asuransi TIDAK tersedia, non-aktifkan dan reset
            useInsuranceCheckbox.disabled = true;
            useInsuranceCheckbox.checked = false;
            useInsuranceCheckbox.classList.add('opacity-50', 'cursor-not-allowed');
            // ======================================================
            // ==== PERBAIKAN: Reset <span> biaya jika tidak tersedia ====
            insuranceCostTextEl.innerText = 'Rp0'; // Reset ke Rp0 jika tidak tersedia
            // ======================================================
        }
        // ======================================================


        if (isCodSupportedByCourier) {
            codPaymentOption.style.display = 'flex';
        } else {
            codPaymentOption.style.display = 'none';
            if(isCodPayment) {
                // Jika kurir tidak support COD, reset pembayaran ke default
                const defaultPayment = document.querySelector('.payment-option[data-value="PERMATAVA"]'); // Ganti ke default payment Anda
                if (defaultPayment) {
                    defaultPayment.click();
                } else {
                    // Fallback jika PERMATAVA tidak ada
                    isCodPayment = false;
                    paymentMethodInput.value = '';
                    document.getElementById('paymentMethodLabel').textContent = 'Pilih Pembayaran';
                    document.getElementById('paymentMethodImg').src = 'https://placehold.co/32x32/EFEFEF/AAAAAA?text=?';
                }
            }
        }
        updateTotal();
    }

    function openPaymentModal() { paymentModal.classList.remove('hidden'); }
    function closePaymentModal() { paymentModal.classList.add('hidden'); }

    paymentMethodButton.addEventListener('click', openPaymentModal);
    closeModalButton.addEventListener('click', closePaymentModal);
    backButton.addEventListener('click', closePaymentModal);
    continueButton.addEventListener('click', closePaymentModal);

    // Logika Checkbox S&K Disederhanakan
    termsCheckbox.addEventListener('change', function() {
        submitButton.disabled = !this.checked;
    });

    // Event listener modal S&K dihapus
    // ...

    document.querySelectorAll('input[name="shipping_method"]').forEach(radio => {
        radio.addEventListener('change', handleShippingChange);
    });

    // TAMBAHKAN EVENT LISTENER UNTUK CHECKBOX ASURANSI
    useInsuranceCheckbox.addEventListener('change', updateTotal);

    paymentOptionsList.querySelectorAll('.payment-option').forEach(item => {
        item.addEventListener('click', function () {
            const paymentValue = this.dataset.value;
            paymentMethodInput.value = paymentValue;

            isCodPayment = (paymentValue === 'cod' || paymentValue === 'CODBARANG');

            paymentOptionsList.querySelectorAll('li').forEach(li => li.classList.remove('bg-red-100', 'border-red-500'));
            this.classList.add('bg-red-100', 'border-red-500');

            const label = this.dataset.label;
            const img = this.dataset.img;
            document.getElementById('paymentMethodLabel').textContent = label;
            document.getElementById('paymentMethodImg').src = img;

            updateTotal();
            closePaymentModal();
        });
    });

    checkoutForm.addEventListener('submit', function(e) {
        if (!termsCheckbox.checked) {
            e.preventDefault();
            alert('Anda harus menyetujui Syarat & Ketentuan untuk melanjutkan.'); // Gunakan modal custom jika ada
            return;
        }

        const selectedShipping = document.querySelector('input[name="shipping_method"]:checked');
        if (!selectedShipping) {
            e.preventDefault();
            alert('Anda harus memilih metode pengiriman terlebih dahulu.'); // Gunakan modal custom jika ada
            return;
        }

        // Validasi pembayaran
        if (paymentMethodInput.value === "") {
             e.preventDefault();
             alert('Anda harus memilih metode pembayaran terlebih dahulu.'); // Gunakan modal custom jika ada
             return;
        }

        submitButton.disabled = true;
        submitButton.innerHTML = 'Memproses...';
    });

    // Inisialisasi awal
    const initialPayment = document.querySelector(`#paymentOptionsList li[data-value="${paymentMethodInput.value}"]`);
    if(initialPayment) {
        initialPayment.classList.add('bg-red-100', 'border-red-500');
        // Update tampilan tombol utama saat load
        // SAYA KEMBALIKAN KODE INI AGAR SESUAI DENGAN HTML YANG DI-HARDCODE
        document.getElementById('paymentMethodLabel').textContent = initialPayment.dataset.label;
        document.getElementById('paymentMethodImg').src = initialPayment.dataset.img;
    } else {
        // Fallback ini seharusnya tidak berjalan lagi karena value="PERMATAVA" sudah ada
        paymentMethodInput.value = "";
        document.getElementById('paymentMethodLabel').textContent = 'Pilih Pembayaran';
        document.getElementById('paymentMethodImg').src = 'https://placehold.co/32x32/EFEFEF/AAAAAA?text=?';
    }

    handleShippingChange();

    // Logika Tab Pengiriman
    const groupButtons = document.querySelectorAll('.group-button');
    const groupOptions = document.querySelectorAll('.shipping-group-options');

    groupButtons.forEach(button => {
        button.addEventListener('click', () => {
            const group = button.dataset.group;

            groupButtons.forEach(b => b.classList.remove('active'));
            button.classList.add('active');

            groupOptions.forEach(div => {
                div.classList.toggle('hidden', div.dataset.group !== group);
            });

            const firstRadioInGroup = document.querySelector(`.shipping-group-options[data-group="${group}"] input[name="shipping_method"]`);
            if (firstRadioInGroup) {
                firstRadioInGroup.checked = true;
            }
            handleShippingChange();
        });
    });

    // Inisialisasi tab dan radio button pertama
    const firstActiveRadio = document.querySelector('input[name="shipping_method"]:checked');
    if (!firstActiveRadio && groupButtons.length > 0) {
        // Jika tidak ada yang checked, klik tombol tab pertama
        groupButtons[0].click();
    }

});
</script>

<!-- ====================================================== -->
<!-- === KODE GPS (SCRIPT) DITARUH DI SINI === -->
<!-- ====================================================== -->
<script>
// Skrip ini akan otomatis mengambil lokasi GPS saat halaman checkout dimuat
// dan mengisinya ke input 'latitude' dan 'longitude' di atas.
window.addEventListener('load', function() {
    if ('geolocation' in navigator) {
        console.log('Mencoba mengambil lokasi GPS...');
        navigator.geolocation.getCurrentPosition(
            function(position) {
                // Sukses dapat lokasi
                document.getElementById('latitude').value = position.coords.latitude;
                document.getElementById('longitude').value = position.coords.longitude;
                console.log('Lokasi GPS didapat:', position.coords.latitude, position.coords.longitude);
            },
            function(error) {
                // Gagal dapat lokasi
                console.warn('Gagal mendapatkan lokasi GPS:', error.message);
                // Biarkan input kosong, controller akan menanganinya (jadi null)
            },
            {
                enableHighAccuracy: true, // Minta akurasi tinggi (GPS)
                timeout: 10000,           // Batas waktu 10 detik
                maximumAge: 0             // Jangan pakai cache lokasi lama
            }
        );
    } else {
        console.warn('Geolocation tidak didukung browser ini.');
    }
});
</script>
<!-- ====================================================== -->

@endsection
