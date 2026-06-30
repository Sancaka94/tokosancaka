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
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

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

        /* Custom Scrollbar untuk Modal */
        .custom-scrollbar::-webkit-scrollbar {
            width: 6px;
        }
        .custom-scrollbar::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 8px;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 8px;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }

        /* ====================================================== */
        /* === CUSTOM CSS SELECT2 AGAR MENYATU DENGAN TAILWIND === */
        /* ====================================================== */
        .select2-container {
            width: 100% !important; /* Paksa lebar 100% */
        }
        .select2-container .select2-selection--single {
            height: 42px !important; /* Samakan tinggi dengan input Tailwind */
            border: 1px solid #d1d5db !important; /* Warna border abu-abu Tailwind */
            border-radius: 0.375rem !important; /* Lengkungan Tailwind */
            display: flex;
            align-items: center;
            padding-left: 0.25rem;
        }
        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 40px !important;
            right: 8px !important;
        }
        .select2-dropdown {
            border: 1px solid #d1d5db !important;
            border-radius: 0.375rem !important;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1) !important; /* Efek shadow Tailwind */
            z-index: 9999; /* Pastikan dropdown selalu di atas */
        }

        .select2-results__option {
            color: #1f2937 !important; /* Warna teks gelap */
            padding: 8px 12px !important; /* Jarak agar nyaman dibaca */
        }

        .select2-search__field {
            border: 1px solid #d1d5db !important;
            border-radius: 0.375rem !important;
            padding: 0.5rem !important;
            outline: none !important;
        }
        .select2-search__field:focus {
            border-color: #ef4444 !important; /* Berubah merah saat diketik (Tailwind red-500) */
            box-shadow: 0 0 0 1px #ef4444 !important;
        }

    </style>
@endpush

@section('content')

<div class="bg-gray-100 min-h-screen">
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

        <form id="checkout-form" action="{{ route('checkout.store') }}" method="POST">
            @csrf

            <!-- === KODE GPS (INPUT TERSEMBUNYI) === -->
            <input type="hidden" name="latitude" id="latitude">
            <input type="hidden" name="longitude" id="longitude">

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

                <!-- Kolom Kiri: Alamat, Pengiriman, Pembayaran -->
                <div class="lg:col-span-2 space-y-8">


                   {{-- UBAH IF INI AGAR MAKANAN LOKAL JUGA MEMUNCULKAN FORM PENERIMA --}}
                    @if($isStrictlyDigital || (isset($isLocalFood) && $isLocalFood))
                    <div class="bg-white rounded-xl shadow-md p-6 border-l-4 {{ (isset($isLocalFood) && $isLocalFood) ? 'border-orange-500' : 'border-red-600' }}">
                        <h2 class="text-lg font-bold text-gray-900 mb-4">Data Penerima {{ (isset($isLocalFood) && $isLocalFood) ? '(Kurir Sancaka Lokal)' : ($hasPhysical ? '(Khusus E-Ticket)' : '(Produk Digital / E-Ticket)') }}</h2>
                            <p class="text-sm text-gray-500 mb-4">Mohon lengkapi data di bawah ini untuk kelancaran pesanan Anda.</p>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Nama Lengkap</label>
                                    <input type="text" name="nama_penerima" id="nama_penerima" value="{{ optional(Auth::user())->nama_lengkap }}" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-red-500 focus:border-red-500 sm:text-sm" required>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Nomor WhatsApp</label>
                                    <input type="text" name="no_wa_penerima" id="no_wa_penerima" value="{{ optional(Auth::user())->no_wa }}" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-red-500 focus:border-red-500 sm:text-sm" required>
                                </div>

                                @if(!Auth::check())
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700">Email (Untuk Bukti Pembayaran)</label>
                                    <input type="email" name="email" id="email" class="mt-1 block w-full border border-gray-300 rounded-md p-2 focus:ring-red-500 focus:border-red-500 sm:text-sm" required>
                                </div>
                                @endif

                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700">NIK (Opsional)</label>
                                    <input type="text" name="nik_penerima" id="nik_penerima" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-red-500 focus:border-red-500 sm:text-sm">
                                </div>
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700">Alamat Lengkap</label>
                                    <textarea name="alamat_lengkap_penerima" id="alamat_lengkap_penerima" rows="2" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-red-500 focus:border-red-500 sm:text-sm"></textarea>
                                </div>
                                <!-- OPSI CARI MANUAL KIRIMINAJA -->
                                <div class="md:col-span-2 border-t border-gray-200 pt-4 mt-2">
                                    <label class="block text-sm font-medium text-gray-700">Pencarian Wilayah Otomatis (Kelurahan / Kecamatan)</label>
                                    <select id="select2_alamat_digital" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm focus:ring-red-500 focus:border-red-500 sm:text-sm"></select>
                                    <p class="text-xs text-gray-500 mt-1"><i class="fas fa-info-circle"></i> Gunakan pencarian ini jika data wilayah dari GPS kurang akurat.</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Provinsi</label>
                                    <input type="text" name="provinsi_penerima" id="provinsi_penerima" class="mt-1 block w-full border border-gray-200 rounded-md shadow-sm p-2 bg-gray-50 sm:text-sm" readonly>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Kota / Kabupaten</label>
                                    <input type="text" name="kota_penerima" id="kota_penerima" class="mt-1 block w-full border border-gray-200 rounded-md shadow-sm p-2 bg-gray-50 sm:text-sm" readonly>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Kecamatan</label>
                                    <input type="text" name="kecamatan_penerima" id="kecamatan_penerima" class="mt-1 block w-full border border-gray-200 rounded-md shadow-sm p-2 bg-gray-50 sm:text-sm" readonly>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Desa / Kelurahan</label>
                                    <input type="text" name="kelurahan_penerima" id="kelurahan_penerima" class="mt-1 block w-full border border-gray-200 rounded-md shadow-sm p-2 bg-gray-50 sm:text-sm" readonly>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Kode Pos</label>
                                    <input type="text" name="kode_pos_penerima" id="kode_pos_penerima" class="mt-1 block w-full border border-gray-200 rounded-md shadow-sm p-2 bg-gray-50 sm:text-sm" readonly>
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Alamat Pengiriman -->
                    <div class="bg-white rounded-xl shadow-md p-6">
                        <h2 class="text-lg font-bold text-gray-900 mb-4">Alamat Pengiriman</h2>
                        <div class="border border-gray-200 rounded-lg p-4">
                            @php
                                $user = Auth::user();
                                $alamat = $user ? ($user->address_detail ?? 'Mohon lengkapi alamat Anda.') : 'Alamat Guest (Lihat form di atas)';
                                $nama = optional($user)->nama_lengkap ?? 'Guest (Tamu)';
                                $wa = optional($user)->no_wa ?? '-';
                            @endphp
                            <p class="font-semibold" id="preview_nama">{{ $nama }}</p>
                            <p class="text-sm text-gray-600" id="preview_wa">{{ $wa }}</p>
                            <p class="text-sm text-gray-600 mt-2" id="preview_alamat">{{ $alamat }}</p>
                            @auth
                            <a href="{{ route('customer.profile.edit') }}?redirect_to=checkout" class="text-sm text-red-600 hover:underline mt-2 inline-block">Ubah Alamat</a>
                            @endauth
                        </div>
                    </div>

                    <div class="bg-white rounded-xl shadow-md p-6">
                        <h2 class="text-lg font-bold text-gray-900 mb-4">Pilih Metode Pengiriman</h2>

                       @if(isset($isLocalFood) && $isLocalFood)
                        <div class="p-4 bg-orange-50 border border-orange-200 rounded-lg mb-4" id="mapbox-loading">
                            <div class="flex items-center text-orange-600">
                                <i class="fas fa-spinner fa-spin text-2xl mr-3"></i>
                                <div>
                                    <h3 class="font-bold">Menghitung Jarak & Tarif...</h3>
                                    <p class="text-xs">Sistem sedang mendeteksi lokasi Anda via GPS.</p>
                                </div>
                            </div>
                        </div>

                        <!-- Container hasil perhitungan Mapbox yang disembunyikan awalnya -->
                        <div id="mapbox-result-container" class="hidden">
                            <label class="flex items-center border border-orange-300 bg-orange-50 p-4 rounded-lg cursor-pointer mb-2">
                                <input type="radio" name="shipping_method" id="radio_sancaka_local" value="" data-cost="0" data-insurance="0" data-cod="true" data-cod-fee="0" class="form-radio h-5 w-5 text-orange-600" checked>
                                <div class="ml-4 flex justify-between w-full items-center">
                                    <div class="flex items-center gap-3">
                                        <i class="fas fa-motorcycle text-orange-500 text-3xl"></i>
                                        <div>
                                            <span class="text-sm font-bold text-gray-900">Kurir Sancaka Lokal (Motor)</span>
                                            <span class="block text-xs text-gray-500">Jarak: <span id="mapbox_km">0</span> KM | Estimasi: <span id="mapbox_min">0</span> Menit</span>
                                        </div>
                                    </div>
                                    <span class="text-lg font-bold text-orange-600" id="mapbox_price">Rp0</span>
                                </div>
                            </label>
                        </div>
                    @endif


                        {{-- ====================================================================== --}}
                        {{-- 2. LOGIKA LAMA ANDA UNTUK DIGITAL / E-TICKET                           --}}
                        {{-- ====================================================================== --}}
                        @elseif($isStrictlyDigital)
                            <div class="p-4 bg-green-50 border border-green-200 rounded-lg flex items-start mb-4">
                                <i class="fas fa-bolt text-green-500 text-2xl mr-4 mt-1"></i>
                                <div>
                                    <h3 class="font-bold text-green-800">Pengiriman Instan (Otomatis)</h3>
                                    <p class="text-sm text-green-600 mt-1">Sistem mendeteksi ini adalah produk Digital/E-Ticket/Jasa. Produk ini tidak memerlukan pengiriman kurir fisik dan akan langsung diproses setelah pembayaran lunas.</p>
                                </div>
                            </div>

                            <input type="radio"
                                name="shipping_method"
                                value="digital_delivery-eticket-noncod-0-0-0"
                                data-cost="0"
                                data-insurance="0"
                                data-cod="false"
                                data-cod-fee="0"
                                class="hidden"
                                checked>
                        @else
                            <div class="space-y-4">
                                @php
                                    $expressResults = collect($expressOptions['results'] ?? []);
                                    $instantResults = collect([]);
                                    if (isset($instantOptions['status']) && $instantOptions['status'] === true && isset($instantOptions['results'])) {
                                        $instantResults = collect($instantOptions['results']);
                                    }
                                    $allResults = $expressResults->merge($instantResults);
                                    $groupedOptions = $allResults->groupBy('group');

                                    $groupOrder = ['Regular', 'One Day', 'Instant', 'Cargo', 'Trucking'];
                                    $finalGrouped = collect($groupOrder)
                                        ->mapWithKeys(function($key) use ($groupedOptions) {
                                            $key = \Illuminate\Support\Str::title($key);
                                            return [$key => $groupedOptions->get(strtolower($key), collect())];
                                        })
                                        ->filter(fn($group) => $group->isNotEmpty());

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
                                                $insurance = $option['insurance_cost'] ?? 0;
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
                            </div>
                        @endif
                    </div>

                    <!-- Pilih Metode Pembayaran -->
                    <div class="bg-white rounded-xl shadow-md p-6 relative">
                        <h2 class="text-lg font-bold text-gray-900 mb-4">Pilih Metode Pembayaran</h2>
                        <div class="w-full">
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

                            <!-- Baris Asuransi -->
                            <div class="flex items-center justify-between" id="insurance_row">
                                <dt class="text-sm text-gray-600">
                                    <label for="use_insurance" class="cursor-pointer">Gunakan Asuransi</label>
                                </dt>
                                <dd class="flex items-center">
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

<!-- ====================================================================== -->
<!-- ============ MODAL PEMBAYARAN: LEBAR DI DESKTOP, POTRAIT DI HP ======= -->
<!-- ====================================================================== -->
<div id="paymentModal" class="fixed inset-0 bg-gray-900 bg-opacity-75 flex items-center justify-center z-50 hidden transition-opacity">
    {{-- max-w-5xl membuat modal sangat lebar di layar Desktop seperti Mega Menu --}}
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-5xl mx-4 transform transition-all flex flex-col max-h-[90vh]">

        <div class="flex justify-between items-center p-5 border-b border-gray-200">
            <h3 class="text-lg font-bold text-gray-900">Pilih Metode Pembayaran</h3>
            <button type="button" id="closeModalButton" class="text-gray-400 hover:text-red-600 bg-gray-100 hover:bg-red-50 p-2 rounded-full transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>

        {{-- Area list dengan Custom Scrollbar jika terlalu panjang --}}
        <div class="p-2 overflow-y-auto custom-scrollbar flex-1">
            {{-- GRID: 1 kolom di HP, 2 kolom di Tablet, 3 kolom di Desktop --}}
            <ul id="paymentOptionsList" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3 p-4">

                {{-- 1. OPSI INTERNAL (SALDO) --}}
                @auth
                <li class="payment-option col-span-full cursor-pointer flex items-center p-3 border rounded-lg hover:bg-red-50 transition-colors"
                    data-value="SALDO" data-label="Saldo Sancaka" data-img="{{ asset('public/assets/saldo.png') }}">
                    <img src="{{ asset('public/assets/saldo.png') }}" class="h-8 w-8 object-contain mr-4">
                    <span class="text-sm font-medium text-gray-900">Saldo {{ optional(Auth::user())->nama_lengkap }}: (Rp{{ number_format(optional(Auth::user())->saldo ?? 0, 0, ',', '.') }})</span>
                </li>
                @endauth

                {{-- 2. OPSI KHUSUS (DOKU) --}}
                <li class="payment-option col-span-full cursor-pointer flex items-center p-3 border rounded-lg hover:bg-red-50 transition-colors"
                    data-value="DOKU_JOKUL" data-label="Doku (Kartu Kredit, E-Wallet, dll)" data-img="{{ asset('public/assets/doku.png') }}">
                    <img src="{{ asset('public/assets/doku.png') }}" class="h-8 w-8 object-contain mr-4">
                    <span class="text-sm font-medium text-gray-900">Rekomendasi Sancaka (Semua Pembayaran via Doku)</span>
                </li>

                  {{-- ========================================================== --}}
                {{-- DANA BINDING --}}
                {{-- ========================================================== --}}
                <li class="col-span-full px-1 pt-4 pb-1 text-xs font-bold text-gray-500 uppercase tracking-wider border-b border-gray-100">
                    DANA Enterprise
                </li>

                @php
                    $user = Auth::user();
                    $userDanaToken = $user ? $user->dana_access_token : null;
                    $userDanaBalance = $user ? ($user->dana_user_balance ?? 0) : 0;
                    $hasDanaBinding = !empty($userDanaToken);
                @endphp

                <li class="payment-option col-span-1 cursor-pointer flex items-center p-3 border rounded-lg hover:bg-red-50 transition-colors"
                    data-value="DANA" data-label="DANA (Web Checkout)" data-img="{{ asset('public/assets/dana.webp') }}">
                    <img src="{{ asset('public/assets/dana.webp') }}" alt="DANA" class="h-8 w-8 object-contain mr-4" onerror="this.src='https://upload.wikimedia.org/wikipedia/commons/7/72/Logo_dana_blue.svg'">
                    <div class="flex flex-col">
                        <span class="text-sm font-bold text-gray-900">DANA Checkout</span>
                        <span class="text-[11px] text-gray-500 mt-0.5">Diarahkan ke aplikasi DANA</span>
                    </div>
                </li>

                @if($hasDanaBinding)
                    <li class="payment-option col-span-1 cursor-pointer flex items-center p-3 border rounded-lg border-blue-200 bg-blue-50 hover:bg-blue-100 transition-colors"
                        data-value="DANA_BINDING" data-label="DANA Auto-Debit" data-img="{{ asset('public/assets/dana.webp') }}">
                        <img src="{{ asset('public/assets/dana.webp') }}" alt="DANA" class="h-8 w-8 object-contain mr-4">
                        <div class="flex flex-col flex-1">
                            <span class="text-sm font-bold text-gray-900">DANA Auto-Debit</span>
                            <span class="text-[11px] text-gray-600 font-medium mt-0.5">Saldo: <span class="text-blue-700">Rp{{ number_format($userDanaBalance, 0, ',', '.') }}</span></span>
                        </div>
                        <span class="ml-auto bg-blue-600 text-white text-[10px] font-semibold px-2 py-0.5 rounded shadow-sm">
                            Tersambung
                        </span>
                    </li>
                @else
                    <li class="col-span-1 flex items-center p-3 border border-dashed border-gray-300 rounded-lg bg-gray-50 justify-between">
                        <div class="flex items-center">
                            <img src="{{ asset('public/assets/dana.webp') }}" alt="DANA" class="h-8 w-8 object-contain mr-4 grayscale opacity-50">
                            <div class="flex flex-col">
                                <span class="text-sm font-bold text-gray-500">DANA Auto-Debit</span>
                                <span class="text-[11px] text-gray-400 mt-0.5">Bayar instan 1-klik</span>
                            </div>
                        </div>
                        <a href="{{ url('/dana/start-binding') }}" class="px-2.5 py-1.5 bg-blue-600 text-white text-xs font-semibold rounded hover:bg-blue-700 shadow-sm transition-colors">
                            Hubungkan
                        </a>
                    </li>
                @endif

                {{-- ========================================================== --}}
                {{-- COD & TRIPAY --}}
                {{-- ========================================================== --}}
                <li class="col-span-full px-1 pt-4 pb-1 text-xs font-bold text-gray-500 uppercase tracking-wider border-b border-gray-100">
                    Lainnya
                </li>

                <li id="codPaymentOption" class="payment-option col-span-1 cursor-pointer flex items-center p-3 border rounded-lg hover:bg-red-50 transition-colors"
                    data-value="cod" data-label="COD (Bayar Ongkir)" data-img="{{ asset('public/assets/cod.png') }}">
                    <img src="{{ asset('public/assets/cod.png') }}" class="h-8 w-8 object-contain mr-4">
                    <span class="text-sm font-medium text-gray-900">COD (Cash on Delivery)</span>
                </li>

                <li class="payment-option col-span-1 cursor-pointer flex items-center p-3 border rounded-lg hover:bg-red-50 transition-colors"
                    data-value="CODBARANG" data-label="COD BARANG" data-img="{{ asset('public/assets/cod.png') }}">
                    <img src="{{ asset('public/assets/cod.png') }}" class="h-8 w-8 object-contain mr-4">
                    <span class="text-sm font-medium text-gray-900">COD BARANG</span>
                </li>

                  <li class="payment-option col-span-1 cursor-pointer flex items-center p-3 border rounded-lg hover:bg-red-50 transition-colors"
                    data-value="PAYPAL" data-label="PayPal / Credit Card" data-img="https://tokosancaka.com/public/assets/paypal.png">
                    <img src="https://tokosancaka.com/public/assets/paypal.png" alt="PayPal" class="h-8 object-contain mr-4" onerror="this.src='https://placehold.co/32x32/EFEFEF/AAAAAA?text=PP'">
                    <div class="flex flex-col">
                        <span class="text-sm font-bold text-gray-900">PayPal / Kartu Kredit</span>
                        <span class="text-[11px] text-gray-500 mt-0.5">Pembayaran Global (Otomatis USD)</span>
                    </div>
                </li>

                {{-- ========================================================== --}}
                {{-- VIRTUAL ACCOUNT --}}
                {{-- ========================================================== --}}
                <li class="col-span-full px-1 pt-4 pb-1 text-xs font-bold text-gray-500 uppercase tracking-wider border-b border-gray-100">
                    Virtual Account (Transfer Bank)
                </li>

                <li class="payment-option col-span-1 cursor-pointer flex items-center p-3 border rounded-lg hover:bg-red-50 transition-colors"
                    data-value="DOKU_BCA_VA" data-label="BCA Virtual Account" data-img="{{ asset('public/assets/bca.webp') }}">
                    <img src="{{ asset('public/assets/bca.webp') }}" alt="BCA" class="h-6 w-12 object-contain mr-4">
                    <div class="flex flex-col">
                        <span class="text-sm font-bold text-gray-900">BCA Virtual Account</span>
                        <span class="text-[11px] text-gray-500 mt-0.5">Diverifikasi Otomatis</span>
                    </div>
                </li>

                <li class="payment-option col-span-1 cursor-pointer flex items-center p-3 border rounded-lg hover:bg-red-50 transition-colors"
                    data-value="DOKU_MANDIRI_VA" data-label="Mandiri Virtual Account" data-img="{{ asset('public/assets/mandiri.webp') }}">
                    <img src="{{ asset('public/assets/mandiri.webp') }}" alt="Mandiri" class="h-6 w-12 object-contain mr-4">
                    <div class="flex flex-col">
                        <span class="text-sm font-bold text-gray-900">Mandiri Virtual Account</span>
                        <span class="text-[11px] text-gray-500 mt-0.5">Diverifikasi Otomatis</span>
                    </div>
                </li>

                <li class="payment-option col-span-1 cursor-pointer flex items-center p-3 border rounded-lg hover:bg-red-50 transition-colors"
                    data-value="DOKU_BRI_VA" data-label="BRI Virtual Account" data-img="{{ asset('public/assets/bri.webp') }}">
                    <img src="{{ asset('public/assets/bri.webp') }}" alt="BRI" class="h-6 w-12 object-contain mr-4">
                    <div class="flex flex-col">
                        <span class="text-sm font-bold text-gray-900">BRIVA</span>
                        <span class="text-[11px] text-gray-500 mt-0.5">Diverifikasi Otomatis</span>
                    </div>
                </li>

                <li class="payment-option col-span-1 cursor-pointer flex items-center p-3 border rounded-lg hover:bg-red-50 transition-colors"
                    data-value="DOKU_BNI_VA" data-label="BNI Virtual Account" data-img="{{ asset('public/assets/bni.webp') }}">
                    <img src="{{ asset('public/assets/bni.webp') }}" alt="BNI" class="h-6 w-12 object-contain mr-4">
                    <div class="flex flex-col">
                        <span class="text-sm font-bold text-gray-900">BNI Virtual Account</span>
                        <span class="text-[11px] text-gray-500 mt-0.5">Diverifikasi Otomatis</span>
                    </div>
                </li>

                <li class="payment-option col-span-1 cursor-pointer flex items-center p-3 border rounded-lg hover:bg-red-50 transition-colors"
                    data-value="DOKU_BSI_VA" data-label="BSI Virtual Account" data-img="{{ asset('public/assets/bsi.png') }}">
                    <img src="{{ asset('public/assets/bsi.png') }}" alt="BSI" class="h-6 w-12 object-contain mr-4">
                    <div class="flex flex-col">
                        <span class="text-sm font-bold text-gray-900">BSI Virtual Account</span>
                        <span class="text-[11px] text-gray-500 mt-0.5">Diverifikasi Otomatis</span>
                    </div>
                </li>

                <li class="payment-option col-span-1 cursor-pointer flex items-center p-3 border rounded-lg hover:bg-red-50 transition-colors"
                    data-value="DOKU_PERMATA_VA" data-label="Permata Virtual Account" data-img="{{ asset('public/assets/permata.webp') }}">
                    <img src="{{ asset('public/assets/permata.webp') }}" alt="Permata" class="h-6 w-12 object-contain mr-4">
                    <div class="flex flex-col">
                        <span class="text-sm font-bold text-gray-900">Permata Virtual Account</span>
                        <span class="text-[11px] text-gray-500 mt-0.5">Diverifikasi Otomatis</span>
                    </div>
                </li>

                <li class="payment-option col-span-1 cursor-pointer flex items-center p-3 border rounded-lg hover:bg-red-50 transition-colors"
                    data-value="DOKU_CIMB_VA" data-label="CIMB Niaga Virtual Account" data-img="{{ asset('public/assets/cimb.svg') }}">
                    <img src="{{ asset('public/assets/cimb.svg') }}" alt="CIMB" class="h-6 w-12 object-contain mr-4">
                    <div class="flex flex-col">
                        <span class="text-sm font-bold text-gray-900">CIMB Niaga VA</span>
                        <span class="text-[11px] text-gray-500 mt-0.5">Diverifikasi Otomatis</span>
                    </div>
                </li>

                <li class="payment-option col-span-1 cursor-pointer flex items-center p-3 border rounded-lg hover:bg-red-50 transition-colors"
                    data-value="DOKU_DANAMON_VA" data-label="Danamon Virtual Account" data-img="{{ asset('public/assets/danamon.png') }}">
                    <img src="{{ asset('public/assets/danamon.png') }}" alt="Danamon" class="h-6 w-12 object-contain mr-4">
                    <div class="flex flex-col">
                        <span class="text-sm font-bold text-gray-900">Danamon Virtual Account</span>
                        <span class="text-[11px] text-gray-500 mt-0.5">Diverifikasi Otomatis</span>
                    </div>
                </li>

                <li class="payment-option col-span-1 cursor-pointer flex items-center p-3 border rounded-lg hover:bg-red-50 transition-colors"
                    data-value="DOKU_DOKU_VA" data-label="DOKU Virtual Account" data-img="{{ asset('public/assets/doku.png') }}">
                    <img src="{{ asset('public/assets/doku.png') }}" alt="DOKU VA" class="h-6 w-12 object-contain mr-4">
                    <div class="flex flex-col">
                        <span class="text-sm font-bold text-gray-900">DOKU Virtual Account</span>
                        <span class="text-[11px] text-gray-500 mt-0.5">Diverifikasi Otomatis</span>
                    </div>
                </li>

                {{-- ========================================================== --}}
                {{-- QRIS & MINIMARKET --}}
                {{-- ========================================================== --}}
                <li class="col-span-full px-1 pt-4 pb-1 text-xs font-bold text-gray-500 uppercase tracking-wider border-b border-gray-100">
                    Scan QRIS & Minimarket
                </li>

                <li class="payment-option col-span-1 cursor-pointer flex items-center p-3 border rounded-lg hover:bg-red-50 transition-colors"
                    data-value="DOKU_QRIS" data-label="QRIS (Gopay, OVO, Dana, LinkAja)" data-img="{{ asset('public/assets/qris.png') }}">
                    <img src="{{ asset('public/assets/qris.png') }}" alt="QRIS" class="h-8 w-14 object-contain mr-3">
                    <div class="flex flex-col">
                        <span class="text-sm font-bold text-gray-900">QRIS (E-Wallet & Bank)</span>
                        <span class="text-[11px] text-gray-500 mt-0.5">Scan kode barcode di Invoice</span>
                    </div>
                </li>

                <li class="payment-option col-span-1 cursor-pointer flex items-center p-3 border rounded-lg hover:bg-red-50 transition-colors"
                    data-value="DOKU_ALFAMART" data-label="Alfamart / Alfamidi" data-img="{{ asset('public/assets/alfamart.webp') }}">
                    <img src="{{ asset('public/assets/alfamart.webp') }}" alt="Alfamart" class="h-6 w-12 object-contain mr-4">
                    <div class="flex flex-col">
                        <span class="text-sm font-bold text-gray-900">Alfamart / Alfamidi</span>
                        <span class="text-[11px] text-gray-500 mt-0.5">Tunjukkan kode bayar ke kasir</span>
                    </div>
                </li>

                {{-- ========================================================== --}}
                {{-- E-WALLET & KARTU KREDIT --}}
                {{-- ========================================================== --}}
                <li class="col-span-full px-1 pt-4 pb-1 text-xs font-bold text-gray-500 uppercase tracking-wider border-b border-gray-100">
                    E-Wallet & Kartu Kredit
                </li>

                <li class="payment-option col-span-1 cursor-pointer flex items-center p-3 border rounded-lg hover:bg-red-50 transition-colors"
                    data-value="DOKU_SHOPEEPAY" data-label="ShopeePay" data-img="{{ asset('public/assets/shopeepay.webp') }}">
                    <img src="{{ asset('public/assets/shopeepay.webp') }}" alt="ShopeePay" class="h-6 w-12 object-contain mr-4">
                    <div class="flex flex-col">
                        <span class="text-sm font-bold text-gray-900">ShopeePay</span>
                        <span class="text-[11px] text-gray-500 mt-0.5">Akan diarahkan ke aplikasi Shopee</span>
                    </div>
                </li>

                <li class="payment-option col-span-1 cursor-pointer flex items-center p-3 border rounded-lg hover:bg-red-50 transition-colors"
                    data-value="DOKU_OVO" data-label="OVO" data-img="{{ asset('public/assets/ovo.webp') }}">
                    <img src="{{ asset('public/assets/ovo.webp') }}" alt="OVO" class="h-6 w-12 object-contain mr-4">
                    <div class="flex flex-col">
                        <span class="text-sm font-bold text-gray-900">OVO</span>
                        <span class="text-[11px] text-gray-500 mt-0.5">Akan diarahkan ke aplikasi OVO</span>
                    </div>
                </li>

                <li class="payment-option col-span-1 cursor-pointer flex items-center p-3 border rounded-lg hover:bg-red-50 transition-colors"
                    data-value="DOKU_CREDIT_CARD" data-label="Kartu Kredit / Debit Online" data-img="{{ asset('public/assets/card.png') }}">
                    <img src="{{ asset('public/assets/card.png') }}" alt="Credit Card" class="h-6 w-12 object-contain mr-4">
                    <div class="flex flex-col">
                        <span class="text-sm font-bold text-gray-900">Kartu Kredit / Debit</span>
                        <span class="text-[11px] text-gray-500 mt-0.5">Pembayaran aman dengan 3D Secure</span>
                    </div>
                </li>

                {{-- ========================================================== --}}
                {{-- TRIPAY (SEMUA METODE OTOMATIS) --}}
                {{-- ========================================================== --}}


                @if(isset($tripayChannels) && count($tripayChannels) > 0)
                    <li class="col-span-full px-1 pt-4 pb-1 text-xs font-bold text-gray-500 uppercase tracking-wider border-b border-gray-100">
                        Metode Pembayaran Otomatis
                    </li>
                    @foreach($tripayChannels as $channel)
                        <li class="payment-option col-span-1 cursor-pointer flex items-center p-3 border rounded-lg hover:bg-red-50 transition-colors"
                            data-value="{{ $channel['code'] }}" data-label="{{ $channel['name'] }}" data-img="{{ $channel['icon_url'] }}">
                            <img src="{{ $channel['icon_url'] }}" alt="{{ $channel['name'] }}" class="h-8 w-8 object-contain mr-4" onerror="this.src='https://placehold.co/32x32?text=IMG'">
                            <span class="text-sm font-medium text-gray-900">{{ $channel['name'] }}</span>
                        </li>
                    @endforeach
                @endif

            </ul>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const subtotal = {{ $subtotal ?? 0 }};
    const codFeePercentage = 0.03;
    let shippingCost = 0;

    let insuranceCost = 0;
    let availableInsuranceCost = 0;
    let isCodPayment = false;
    let isCodSupportedByCourier = false;
    let codAddCost = 0;

    const ongkirEl = document.getElementById('ongkos_kirim');
    const codFeeRow = document.getElementById('cod_fee_row');
    const codFeeTextEl = document.getElementById('cod_fee_text');

    const insuranceRow = document.getElementById('insurance_row');
    const insuranceCostTextEl = document.getElementById('insurance_cost_text');
    const useInsuranceCheckbox = document.getElementById('use_insurance');

    const totalEl = document.getElementById('total_pesanan');
    const paymentMethodInput = document.getElementById('payment_method');
    const codPaymentOption = document.getElementById("codPaymentOption");

    const termsCheckbox = document.getElementById('terms-and-conditions');
    const submitButton = document.getElementById('submit-button');
    const checkoutForm = document.getElementById('checkout-form');

    // ============================================================================
    // === SCRIPT MODAL PEMBAYARAN ===
    // ============================================================================
    const paymentModal = document.getElementById('paymentModal');
    const paymentMethodButton = document.getElementById('paymentMethodButton');
    const closeModalButton = document.getElementById('closeModalButton');
    const paymentOptionsList = document.getElementById('paymentOptionsList');

    function openPaymentModal() {
        paymentModal.classList.remove('hidden');
        document.body.style.overflow = 'hidden'; // Mencegah scrolling body saat modal terbuka
    }

    function closePaymentModal() {
        paymentModal.classList.add('hidden');
        document.body.style.overflow = 'auto'; // Mengembalikan scroll
    }

    // Toggle klik tombol utama
    paymentMethodButton.addEventListener('click', openPaymentModal);
    closeModalButton.addEventListener('click', closePaymentModal);

    // Menutup modal jika area gelap di-klik
    paymentModal.addEventListener('click', function(e) {
        if (e.target === paymentModal) {
            closePaymentModal();
        }
    });

    // Logic saat memilih salah satu opsi pembayaran
    paymentOptionsList.querySelectorAll('.payment-option').forEach(item => {
        item.addEventListener('click', function () {
            const paymentValue = this.dataset.value;
            paymentMethodInput.value = paymentValue;
            isCodPayment = (paymentValue === 'cod' || paymentValue === 'CODBARANG');

            // Hapus warna state aktif dari semua LI
            paymentOptionsList.querySelectorAll('.payment-option').forEach(li => li.classList.remove('bg-red-100', 'border-red-500'));
            // Beri warna state aktif pada opsi terpilih
            this.classList.add('bg-red-100', 'border-red-500');

            const label = this.dataset.label;
            const img = this.dataset.img;
            document.getElementById('paymentMethodLabel').textContent = label;
            document.getElementById('paymentMethodImg').src = img;

            updateTotal();
            closePaymentModal(); // Langsung tutup modal setelah memilih
        });
    });
    // ============================================================================


    function formatRupiah(num) {
        return 'Rp' + new Intl.NumberFormat('id-ID').format(num || 0);
    }

    function updateTotal() {
        let totalCodFee = 0;

        insuranceRow.style.display = 'flex';

        if (useInsuranceCheckbox.checked && availableInsuranceCost > 0) {
            insuranceCost = availableInsuranceCost;
            insuranceCostTextEl.innerText = formatRupiah(insuranceCost);
        } else {
            insuranceCost = 0;
            insuranceCostTextEl.innerText = 'Rp0';
        }

        if (isCodPayment && isCodSupportedByCourier) {
            if (codAddCost > 0) {
                totalCodFee = codAddCost;
            } else {
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
            codAddCost = parseInt(selectedShipping.dataset.codFee || 0, 10);
        }

        insuranceRow.style.display = 'flex';

        if (availableInsuranceCost > 0) {
            useInsuranceCheckbox.disabled = false;
            useInsuranceCheckbox.classList.remove('opacity-50', 'cursor-not-allowed');
            insuranceCostTextEl.innerText = formatRupiah(availableInsuranceCost);
        } else {
            useInsuranceCheckbox.disabled = true;
            useInsuranceCheckbox.checked = false;
            useInsuranceCheckbox.classList.add('opacity-50', 'cursor-not-allowed');
            insuranceCostTextEl.innerText = 'Rp0';
        }

        if (isCodSupportedByCourier) {
            codPaymentOption.style.display = 'flex';
        } else {
            codPaymentOption.style.display = 'none';
            if(isCodPayment) {
                const defaultPayment = document.querySelector('.payment-option[data-value="PERMATAVA"]');
                if (defaultPayment) {
                    defaultPayment.click();
                } else {
                    isCodPayment = false;
                    paymentMethodInput.value = '';
                    document.getElementById('paymentMethodLabel').textContent = 'Pilih Pembayaran';
                    document.getElementById('paymentMethodImg').src = 'https://placehold.co/32x32/EFEFEF/AAAAAA?text=?';
                }
            }
        }
        updateTotal();
    }

    termsCheckbox.addEventListener('change', function() {
        submitButton.disabled = !this.checked;
    });

    document.querySelectorAll('input[name="shipping_method"]').forEach(radio => {
        radio.addEventListener('change', handleShippingChange);
    });

    useInsuranceCheckbox.addEventListener('change', updateTotal);

    checkoutForm.addEventListener('submit', function(e) {
        if (!termsCheckbox.checked) {
            e.preventDefault();
            alert('Anda harus menyetujui Syarat & Ketentuan untuk melanjutkan.');
            return;
        }

        const selectedShipping = document.querySelector('input[name="shipping_method"]:checked');
        if (!selectedShipping) {
            e.preventDefault();
            alert('Anda harus memilih metode pengiriman terlebih dahulu.');
            return;
        }

        if (paymentMethodInput.value === "") {
             e.preventDefault();
             alert('Anda harus memilih metode pembayaran terlebih dahulu.');
             return;
        }

        submitButton.disabled = true;
        submitButton.innerHTML = 'Memproses...';
    });

    // Inisialisasi awal
    const initialPayment = document.querySelector(`#paymentOptionsList li.payment-option[data-value="${paymentMethodInput.value}"]`);
    if(initialPayment) {
        initialPayment.classList.add('bg-red-100', 'border-red-500');
        document.getElementById('paymentMethodLabel').textContent = initialPayment.dataset.label;
        document.getElementById('paymentMethodImg').src = initialPayment.dataset.img;
    } else {
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

    const firstActiveRadio = document.querySelector('input[name="shipping_method"]:checked');
    if (!firstActiveRadio && groupButtons.length > 0) {
        groupButtons[0].click();
    }

});
</script>

<!-- ====================================================== -->
<!-- === SCRIPT PENCARIAN ALAMAT KIRIMINAJA (SELECT2) === -->
<!-- ====================================================== -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
$(document).ready(function() {
    $('#select2_alamat_digital').select2({
        width: '100%',
        placeholder: 'Ketik min. 3 huruf (Cth: Ngawi / Margomulyo)...',
        allowClear: true,
        ajax: {
            url: "{{ url('/checkout/search-address-ajax') }}",
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return {
                    q: params.term
                };
            },
            processResults: function (data) {
                return {
                    results: data.results
                };
            },
            cache: true
        },
        minimumInputLength: 3,
    });

    $('#select2_alamat_digital').on('select2:select', function (e) {
        const data = e.params.data;

        let parts = data.raw_address.split(', ');

        let kelurahan = parts[0] || '';
        let kecamatan = parts[1] || '';
        let kota      = parts[2] || '';
        let provinsi  = parts[3] || '';
        let kode_pos  = parts[4] || '';

        $('#provinsi_penerima').val(provinsi);
        $('#kota_penerima').val(kota);
        $('#kecamatan_penerima').val(kecamatan);
        $('#kelurahan_penerima').val(kelurahan);
        $('#kode_pos_penerima').val(kode_pos);

        document.getElementById('alamat_lengkap_penerima').focus();
    });
});
</script>

<script>
function updateCardPreview() {
    if ($('#nama_penerima').length === 0) return;

    let nama = $('#nama_penerima').val();
    let wa = $('#no_wa_penerima').val();
    let alamat = $('#alamat_lengkap_penerima').val();
    let kel = $('#kelurahan_penerima').val();
    let kec = $('#kecamatan_penerima').val();
    let kota = $('#kota_penerima').val();
    let prov = $('#provinsi_penerima').val();
    let pos = $('#kode_pos_penerima').val();

    let arrAlamat = [alamat, kel, kec, kota, prov, pos].filter(Boolean);
    let fullAlamat = arrAlamat.join(', ');

    if(nama) $('#preview_nama').text(nama);
    if(wa) $('#preview_wa').text(wa);
    if(fullAlamat) $('#preview_alamat').text(fullAlamat);
}

$(document).ready(function() {
    $('#nama_penerima, #no_wa_penerima, #alamat_lengkap_penerima').on('input', function() {
        updateCardPreview();
    });

    $('#select2_alamat_digital').on('select2:select', function () {
        setTimeout(updateCardPreview, 300);
    });

    setInterval(function() {
        updateCardPreview();
    }, 1500);
});
</script>

<!-- ====================================================== -->
<!-- === SCRIPT GPS & MAPBOX TERPADU === -->
<!-- ====================================================== -->
<script>
window.addEventListener('load', function() {
    const isLocalFood = {{ (isset($isLocalFood) && $isLocalFood) ? 'true' : 'false' }};
    const storeLat = '{{ $storeLat ?? "" }}';
    const storeLng = '{{ $storeLng ?? "" }}';

    if ('geolocation' in navigator) {
        navigator.geolocation.getCurrentPosition(
            function(position) {
                let userLat = position.coords.latitude;
                let userLng = position.coords.longitude;

                // 1. Selalu catat koordinat (Berguna untuk digital & fisik)
                document.getElementById('latitude').value = userLat;
                document.getElementById('longitude').value = userLng;

                // 2. Jika Makanan Lokal, tembak API Mapbox
                if (isLocalFood) {
                    fetch(`/api/mapbox/calculate?origin_lat=${storeLat}&origin_lng=${storeLng}&dest_lat=${userLat}&dest_lng=${userLng}`)
                        .then(response => response.json())
                        .then(data => {
                            document.getElementById('mapbox-loading').style.display = 'none';
                            if (data.success) {
                                document.getElementById('mapbox-result-container').classList.remove('hidden');

                                let cost = data.data.estimated_cost;
                                let radioValue = `sancaka_local-motor-food-${cost}-0-0`;

                                // Update DOM
                                document.getElementById('radio_sancaka_local').value = radioValue;
                                document.getElementById('radio_sancaka_local').dataset.cost = cost;
                                document.getElementById('mapbox_km').innerText = data.data.distance_km;
                                document.getElementById('mapbox_min').innerText = data.data.duration_minutes;
                                document.getElementById('mapbox_price').innerText = 'Rp' + new Intl.NumberFormat('id-ID').format(cost);

                                // Trigger kalkulasi total harga
                                if(typeof handleShippingChange === "function") { handleShippingChange(); }
                            } else {
                                alert('Gagal menghitung rute: ' + data.message);
                            }
                        })
                        .catch(err => console.error('Mapbox API Error:', err));
                }
            },
            function(error) {
                console.warn('Gagal mendapatkan lokasi GPS:', error.message);
                // Munculkan peringatan error khusus untuk Makanan Lokal
                if (isLocalFood) {
                    document.getElementById('mapbox-loading').innerHTML = '<p class="text-red-500 font-bold p-3">Gagal membaca GPS. Mohon izinkan akses lokasi browser Anda.</p>';
                }
            },
            { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
        );
    } else {
        console.warn('Geolocation tidak didukung browser ini.');
    }
});
</script>

@endsection
