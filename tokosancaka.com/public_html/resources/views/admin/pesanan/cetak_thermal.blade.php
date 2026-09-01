<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="icon" type="image/png" href="https://tokosancaka.com/storage/uploads/sancaka.png">

    <title>Cetak Resi - {{ $pesanan->resi }}</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;900&display=swap" rel="stylesheet">

    {{-- LIBRARY WAJIB: JSBARCODE (1D) --}}
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
    {{-- LIBRARY WAJIB: QRCODE.JS (2D) --}}
    <script src="https://cdn.jsdelivr.net/npm/qrcodejs/qrcode.min.js"></script>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    {{-- WAJIB UNTUK AJAX FONTTE --}}
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #F3F4F6;
            color: #111827;
        }
        .page {
            width: 100mm;
            min-height: 150mm;
            padding: 6mm;
            margin: 10mm auto;
            background: #fff;
            border: 1px solid #E5E7EB;
            border-radius: 6px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            display: flex;
            flex-direction: column;
            font-size: 8pt;
            position: relative; /* WAJIB UNTUK WATERMARK */
            overflow: hidden;   /* WAJIB UNTUK WATERMARK AGAR TIDAK KELUAR BATAS */
        }
        /* Penyesuaian Mobile */
        @media (max-width: 640px) {
            .page {
                margin: 5px auto;
                box-shadow: none;
                border: none;
            }
        }
        .barcode {
            width: 100%;
            height: 50px;
            margin-top: 5px;
            margin-bottom: 5px;
        }
        .label { font-weight: 600; font-size: 12px; color: #374151; }
        .value { font-weight: 500; font-size: 9px; }

        /* --- CSS WATERMARK DIBATALKAN (MERAH) --- */
        .watermark-batal {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 38px;
            font-weight: 900;
            color: rgba(220, 38, 38, 0.25) !important;
            border: 4px solid rgba(220, 38, 38, 0.25);
            padding: 10px 20px;
            border-radius: 10px;
            text-transform: uppercase;
            letter-spacing: 5px;
            white-space: nowrap;
            pointer-events: none;
            z-index: 9999;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        /* --- CSS WATERMARK CREATE (ABU-ABU) --- */
        .watermark-create {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 42px;
            font-weight: 900;
            color: rgba(0, 0, 0, 0.1) !important;
            border: 4px solid rgba(0, 0, 0, 0.1);
            padding: 10px 20px;
            border-radius: 10px;
            text-transform: uppercase;
            letter-spacing: 5px;
            white-space: nowrap;
            pointer-events: none;
            z-index: 9999;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        /* --- CSS WATERMARK SUCCESS (HIJAU) --- */
        .watermark-sukses {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 40px;
            font-weight: 900;
            color: rgba(34, 197, 94, 0.25) !important;
            border: 4px solid rgba(34, 197, 94, 0.25);
            padding: 10px 20px;
            border-radius: 10px;
            text-transform: uppercase;
            letter-spacing: 5px;
            white-space: nowrap;
            pointer-events: none;
            z-index: 9999;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        /* --- CSS EFEK BLUR & PIN OVERLAY --- */
        .content-blurred {
            filter: blur(12px);
            opacity: 0.2;
            pointer-events: none;
            user-select: none;
            transition: all 0.5s ease;
        }
        .pin-overlay {
            position: fixed; top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(15, 23, 42, 0.85);
            backdrop-filter: blur(5px);
            z-index: 10500;
            display: flex; align-items: center; justify-content: center;
            transition: opacity 0.3s ease;
        }

        @media print {
            * {
                -webkit-print-color-adjust: exact !important;
                color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            body { background: none; overflow: visible !important; }
            .no-print { display: none !important; }
            .pin-overlay { display: none !important; }

            .page {
                margin: 0;
                border: none;
                border-radius: 0;
                width: 100mm;
                min-height: 150mm;
                box-shadow: none;
                page-break-after: always;
            }
        }
    </style>
</head>
<body class="overflow-hidden">

    @php
        // =========================================================
        // LOGIKA PERSIAPAN VARIABEL DAN KEAMANAN PIN
        // =========================================================
        if (!function_exists('maskText')) {
            function maskText($text, $keepFirst = 3, $keepLast = 3) {
                if (empty($text) || $text === '-') return '-';
                $text = trim($text);
                $length = strlen($text);

                if ($length <= 4) {
                    return substr($text, 0, 1) . str_repeat('*', $length - 1);
                }
                if ($length <= ($keepFirst + $keepLast)) {
                    $keepFirst = 1;
                    $keepLast = 1;
                }

                $start = substr($text, 0, $keepFirst);
                $end = substr($text, -$keepLast);
                $masked = str_repeat('*', $length - $keepFirst - $keepLast);

                return $start . $masked . $end;
            }
        }

        // Logika Auth: Menentukan Back URL
        $backUrl = url()->previous();
        if (Auth::check()) {
            if (Auth::user()->hasRole('Admin')) {
                $backUrl = route('admin.pesanan.index');
            } elseif (Auth::user()->hasRole('Pelanggan')) {
                $backUrl = route('customer.pesanan.index');
            }
        }

        // Ambil 4 angka terakhir nomor HP Pengirim untuk PIN
        $hpPengirim = preg_replace('/[^0-9]/', '', $pesanan->sender_phone ?? '');
        $pinRahasia = substr($hpPengirim, -4);
        if (strlen($pinRahasia) < 4) $pinRahasia = str_pad($pinRahasia, 4, '0', STR_PAD_LEFT);

        // RUMUS KISI-KISI NOMOR HP (Tampilkan awal + Bintang)
        $panjangHp = strlen($hpPengirim);
        $tampilDepan = substr($hpPengirim, 0, 7);
        $jumlahBintang = $panjangHp > 7 ? $panjangHp - 7 : 4;
        $kisiKisiHp = $tampilDepan . str_repeat('*', $jumlahBintang);
    @endphp

    <!-- MODAL PIN SECURITY -->
    <div id="pinValidationModal" class="pin-overlay">
        <div class="bg-white rounded-2xl shadow-2xl p-8 max-w-sm w-full text-center relative mx-4">
            <div class="w-16 h-16 bg-blue-50 rounded-full flex items-center justify-center mx-auto mb-4 border-2 border-blue-100">
                <i class="fas fa-shield-alt text-2xl text-blue-600"></i>
            </div>
            <h3 class="text-xl font-black text-gray-900 mb-2">Resi Terkunci</h3>
            <p class="text-sm text-gray-500 mb-6 leading-relaxed">
                Masukkan <strong class="text-red-600">4 Angka Terakhir</strong> Nomor HP Pengirim untuk membuka dan mencetak resi ini.<br>
                <span class="inline-block mt-3 px-4 py-1.5 bg-slate-100 border border-slate-200 rounded-lg text-gray-700 font-mono tracking-widest text-[14px] font-bold shadow-sm">
                    {{ $kisiKisiHp }}
                </span>
            </p>
            <input type="password" id="pinInput" maxlength="4" pattern="\d*" inputmode="numeric" class="w-full text-center text-3xl tracking-[0.5em] font-bold border-2 border-gray-200 rounded-xl p-4 mb-2 focus:outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-50 transition-all" placeholder="••••">
            <p id="pinError" class="text-xs text-red-600 mb-4 hidden font-bold animate-pulse"><i class="fas fa-exclamation-circle mr-1"></i> PIN Salah! Silakan coba lagi.</p>
            <button onclick="verifyPin()" id="btnVerify" class="w-full bg-blue-600 text-white font-bold py-3.5 rounded-xl hover:bg-blue-700 transition-colors shadow-md mt-2">
                <i class="fas fa-unlock-alt mr-2"></i> Buka Dokumen
            </button>
        </div>
    </div>

    <!-- WADAH KONTEN UTAMA (DIBLUR SEBELUM PIN BENAR) -->
    <div id="mainContentWrapper" class="content-blurred">

        <div class="no-print p-3 bg-white border-b border-gray-200 shadow-sm sticky top-0 z-10">
            <div class="flex flex-wrap justify-center gap-2 max-w-5xl mx-auto">

                <button onclick="window.print()" class="w-full sm:w-auto lg:flex-none lg:min-w-[150px] bg-red-600 text-white px-5 py-2 rounded-md shadow hover:bg-red-700 transition flex justify-center items-center">
                    <i class="fas fa-print mr-1"></i> Cetak Resi
                </button>

                <button id="downloadBtn" class="w-full sm:w-auto lg:flex-none lg:min-w-[150px] bg-blue-600 text-white px-5 py-2 rounded-md shadow hover:bg-blue-700 transition flex justify-center items-center">
                    <i class="fas fa-download mr-2"></i> Download JPG
                </button>

                <button onclick="sendWaNotificationApi('receiver')" class="w-full sm:w-auto lg:flex-none lg:min-w-[150px] bg-green-600 text-white px-5 py-2 rounded-md shadow hover:bg-green-700 transition flex justify-center items-center">
                    <i class="fab fa-whatsapp mr-1"></i> Kirim WA (Penerima)
                </button>

                <button onclick="sendWaNotificationApi('sender')" class="w-full sm:w-auto lg:flex-none lg:min-w-[150px] bg-green-600 text-white px-5 py-2 rounded-md shadow hover:bg-green-700 transition flex justify-center items-center">
                    <i class="fab fa-whatsapp mr-1"></i> Kirim WA (Pengirim)
                </button>

                <a href="{{ $backUrl }}" class="w-full sm:w-auto lg:flex-none lg:min-w-[150px] bg-gray-200 text-gray-800 px-5 py-2 rounded-md shadow hover:bg-gray-300 transition flex justify-center items-center">
                    <i class="fas fa-arrow-left mr-1"></i> Kembali
                </a>

            </div>
        </div>

        <!-- WADAH RESI KERTAS THERMAL -->
        <div class="page" id="label-resi">

            @php
                // 1. Parsing Helper Asli
                $ship = \App\Helpers\ShippingHelper::parseShippingMethod($pesanan->expedition);
                $expeditionName = $ship['courier_name'] ?? 'SANCAKA';
                $expeditionService = $ship['service_name'] ?? 'Regular';

                // 2. Definisi Mapping Manual (DATABASE LINK GAMBAR)
                $courierMap = [
                    'jne'           => 'https://tokosancaka.com/public/storage/logo-ekspedisi/jne.png',
                    'tiki'          => 'https://tokosancaka.com/public/storage/logo-ekspedisi/tiki.png',
                    'pos'           => 'https://tokosancaka.com/public/storage/logo-ekspedisi/posindonesia.png',
                    'posindonesia'  => 'https://tokosancaka.com/public/storage/logo-ekspedisi/posindonesia.png',
                    'sicepat'       => 'https://tokosancaka.com/public/storage/logo-ekspedisi/sicepat.png',
                    'sap'           => 'https://tokosancaka.com/public/storage/logo-ekspedisi/sap.png',
                    'ncs'           => 'https://blogger.googleusercontent.com/img/b/R29vZ2xl/AVvXsEjxj3iyyZEjK2L4A4yCIr_E-4W3hF2lk_yb-t0Oj2oFPErCPCMHie5LHqps02xMb6sNa-Gqz5NSX_P_hzWlYpUpJUlCD4iN6_QxiSG9fzY4bsZ9XvLFDn7HCiORtNvIlPfuQbSSdW96p7x7uN8ek3FWyHW9c2bznrFBQkoLd5A9sVAFVKWLfUhT3Dxh/s320/GKL41_NCS%20Kurir%20-%20Koleksilogo.com.jpg',
                    'idx'           => 'https://tokosancaka.com/public/storage/logo-ekspedisi/idx.png',
                    'idexpress'     => 'https://tokosancaka.com/public/storage/logo-ekspedisi/idx.png',
                    'gojek'         => 'https://tokosancaka.com/public/storage/logo-ekspedisi/gosend.png',
                    'gosend'        => 'https://tokosancaka.com/public/storage/logo-ekspedisi/gosend.png',
                    'grab'          => 'https://tokosancaka.com/public/storage/logo-ekspedisi/grab.png',
                    'jnt'           => 'https://tokosancaka.com/public/storage/logo-ekspedisi/jnt.png',
                    'j&t'           => 'https://tokosancaka.com/public/storage/logo-ekspedisi/jnt.png',
                    'indah'         => 'https://blogger.googleusercontent.com/img/b/R29vZ2xl/AVvXsEicOAaLoH2eElQ93_gbkzhvk4dRhWVlk5wQsGgilihIB58321aHchlJLdjyz1ToS25P_nWrHJ_E4QBiW_OVlI7tQt7cZ5I0HZqk6StS7jZltLVvDXp2d5ZDLB9yklhV4x6z2iXyURURDv_unhf-U6vyiD_8to9OC4PBwMwyU_5wAqOiCl6tKiaTA-ri1Q/s851/Logo%20Indah%20Logistik%20Cargo@0.5x.png',
                    'jtcargo'       => 'https://tokosancaka.com/public/storage/logo-ekspedisi/jtcargo.png',
                    'lion'          => 'https://tokosancaka.com/public/storage/logo-ekspedisi/lion.png',
                    'spx'           => 'https://tokosancaka.com/public/storage/logo-ekspedisi/spx.png',
                    'shopee'        => 'https://tokosancaka.com/public/storage/logo-ekspedisi/spx.png',
                    'ninja'         => 'https://tokosancaka.com/public/storage/logo-ekspedisi/ninja.png',
                    'anteraja'      => 'https://tokosancaka.com/public/storage/logo-ekspedisi/anteraja.png',
                    'sentral'       => 'https://tokosancaka.com/public/storage/logo-ekspedisi/centralcargo.png',
                    'borzo'         => 'https://tokosancaka.com/public/storage/logo-ekspedisi/borzo.png',
                ];

                // 3. Logika Pencocokan (Smart Matching)
                $normalizedName = strtolower(str_replace(' ', '', $expeditionName));
                $finalLogoUrl = null;

                if (str_contains($normalizedName, 'cargo') && (str_contains($normalizedName, 'j&t') || str_contains($normalizedName, 'jt'))) {
                    $finalLogoUrl = $courierMap['jtcargo'];
                }
                else {
                    foreach ($courierMap as $key => $url) {
                        if (str_contains($normalizedName, $key)) {
                            $finalLogoUrl = $url;
                            break;
                        }
                    }
                }

                if (!$finalLogoUrl) {
                    $logoUrlFromHelper = $ship['logo_url'] ?? null;
                    $localLogoPath = strtolower(str_replace(' ', '', $expeditionName));
                    $localLogoAssetUrl = asset('public/storage/logo-ekspedisi/' . $localLogoPath . '.png');
                    $finalLogoUrl = $logoUrlFromHelper ?: $localLogoAssetUrl;
                }

                // ========================================================
                // 🔥 LOGIKA WATERMARK BERDASARKAN STATUS PESANAN 🔥
                // ========================================================
                $statusPesanan = strtolower($pesanan->status ?? '');

                // Kumpulan tipe status
                $statusBatal = ['batal', 'gagal', 'dibatalkan', 'cancel', 'cancelled', 'returned'];
                $statusSukses = ['sedang dikirim', 'perjalanan', 'terkirim', 'selesai', 'delivered', 'dikirim', 'diproses', 'success', 'completed'];
                $statusCreate = ['sedang dibuat', 'create', 'pesanan dibuat', 'menunggu pembayaran', 'menunggu pickup', 'booking_created', 'dikemas', 'menunggu resi', 'diproses'];

                // Tentukan Class CSS dan Teks
                if (in_array($statusPesanan, $statusBatal)) {
                    $watermarkText = 'NOT VALID CENCEL';
                    $watermarkClass = 'watermark-batal';
                } elseif (in_array($statusPesanan, $statusSukses)) {
                    $watermarkText = 'VALID SUCCESS';
                    $watermarkClass = 'watermark-sukses';
                } else {
                    // Termasuk create, sedang dibuat, dsb.
                    $watermarkText = 'VALID CREATE';
                    $watermarkClass = 'watermark-create';
                }
            @endphp

            <!-- ELEMENT WATERMARK DITEMPATKAN DI SINI -->
            <div class="{{ $watermarkClass }}">{{ $watermarkText }}</div>

            <div class="flex justify-between items-center border-b border-gray-700 pb-2">
                <img src="https://tokosancaka.com/storage/uploads/sancaka.png" alt="Sancaka Express" class="h-10" onerror="this.style.display='none'">

                <img src="{{ $finalLogoUrl }}"
                     alt="{{ $expeditionName }}"
                     class="h-8 object-contain"
                     onerror="this.style.opacity='0'">
            </div>

            <div class="text-center mt-2">
                <p class="font-bold text-sm tracking-wide"><strong>NOMOR RESI TOKOSANCAKA.COM</strong></p>
                {{-- Elemen SVG barcode, margin vertikal ditangani oleh CSS .barcode --}}
                <svg id="barcodeSancaka" class="barcode"></svg>
            </div>

            {{-- LOGIKA COD --}}
            @php
                $pm = strtoupper($pesanan->payment_method);
                $isCodBarang = ($pm === 'CODBARANG');
                $isCodOngkir = ($pm === 'COD');

                // Variabel Default
                $labelCod = "NILAI COD";
                $nilaiCodFinal = 0;
                $showCodBlock = false;

                if ($isCodBarang) {
                    // Ambil langsung dari harga final di DB
                    $nilaiCodFinal = $pesanan->price;
                    $labelCod = "NILAI COD (BARANG + ONGKIR)";
                    $showCodBlock = true;
                } elseif ($isCodOngkir) {
                    $nilaiCodFinal = $pesanan->price;
                    $labelCod = "NILAI COD (ONGKIR)";
                    $showCodBlock = true;
                }
            @endphp

            <div class="grid grid-cols-2 gap-3 mt-2 border-b border-gray-700 pb-2 z-10 relative">

                <div class="pr-2">
                    <p class="label"><strong>PENGIRIM:</strong></p>
                    <p class="value">{{ maskText($pesanan->sender_name) }}</p>
                    <p class="text-xs">{{ maskText($pesanan->sender_phone) }}</p>
                    <p class="text-xs leading-snug mt-1">
                        {{ implode(', ', array_filter([
                            $pesanan->sender_address,
                            $pesanan->sender_village,
                            $pesanan->sender_district,
                            $pesanan->sender_regency,
                            $pesanan->sender_province,
                            $pesanan->sender_postal_code,
                        ])) }}
                    </p>

                    <div class="mt-2 pt-2">
                        <p class="label"><strong>Rincian Paket:</strong></p>
                        <p class="value">- Berat: {{ $pesanan->weight }} Gram</p>
                        <p class="value">- Harga Barang: Rp {{ number_format($pesanan->item_price, 0, ',', '.') }}</p>
                        <p class="value">- Isi Paket: {{ $pesanan->item_description }}</p>
                        <p class="value">- Dimensi: {{ $pesanan->length ?? 0 }} x {{ $pesanan->width ?? 0 }} x {{ $pesanan->height ?? 0 }} cm</p>
                        <p class="value">- Layanan: {{ strtoupper($expeditionService) }}</p><br>
                        @if($showCodBlock)
                            {{-- TAMPILAN JIKA PESANAN COD --}}
                            <p class="label text-gray-700"><strong>{{ $labelCod }}:</strong></p>
                            <p class="value text-gray-700 text-lg mb-0">
                                <strong>Rp {{ number_format($nilaiCodFinal, 0, ',', '.') }}</strong>
                            </p>
                            @if($isCodOngkir)
                                <p class="text-[9px] italic mt-0 font-bold text-red-600 mb-2">(JANGAN TAGIH HARGA BARANG)</p>
                            @endif
                        @else
                            {{-- TAMPILAN JIKA BUKAN COD (REGULER) --}}
                            <p class="label text-green-600"><strong>Total Ongkir:</strong></p>
                            <p class="value text-red-600 text-lg mb-2">
                                <strong>Rp {{ number_format($pesanan->shipping_cost, 0, ',', '.') }}</strong>
                            </p>
                        @endif
                    </div>
                </div>

                <div class="pl-2">
                    <p class="label"><strong>PENERIMA:</strong></p>
                    <p class="value">{{ maskText($pesanan->receiver_name) }}</p>
                    <p class="text-xs">{{ maskText($pesanan->receiver_phone) }}</p>
                    <p class="text-xs leading-snug mt-1">
                        {{ implode(', ', array_filter([
                            $pesanan->receiver_address,
                            $pesanan->receiver_village,
                            $pesanan->receiver_district,
                            $pesanan->receiver_regency,
                            $pesanan->receiver_province,
                            $pesanan->receiver_postal_code,
                        ])) }}
                    </p>

                    <div class="flex justify-center mt-4">
                        <div class="border border-gray-400 rounded-md p-3 inline-block bg-white">
                            <div id="qrcode"></div>
                        </div>
                    </div>

                    <p class="flex justify-center mt-1 mb-1"><strong>TRACKING ME</strong></p>
                    <p class="value text-center">CV. SANCAKA KARYA HUTAMA</p>
                    <p class="value text-center">Helpdesk: 08574580809</p>

                </div>
            </div>

            <div class="grid grid-cols-3 gap-2 text-center mt-2 border-b border-gray-700 pb-2 z-10 relative">
                <div><p class="label"><strong>ORDER ID / RESI</strong></p><p class="value">{{ $pesanan->nomor_invoice }}</p></div>
                <div><p class="label"><strong>BERAT</strong></p><p class="value">{{ $pesanan->weight }} Gram</p></div>
                <div><p class="label"><strong>VOLUME (cm)</strong></p><p class="value">{{ $pesanan->length ?? 0 }} x {{ $pesanan->width ?? 0 }} x {{ $pesanan->height ?? 0 }}</p></div>

                <div>
                    <p class="label"><strong>LAYANAN</strong></p>
                    <p class="value break-all max-w-full leading-tight">{{ strtoupper($expeditionService) }}</p>
                </div>

                <div><p class="label"><strong>EKSPEDISI</strong></p><p class="value">{{ strtoupper($expeditionName) }}</p></div>
                <div><p class="label"><strong>Pembayaran</strong></p><p class="value">{{ strtoupper($pesanan->payment_method) === 'POTONG SALDO' ? 'SALDO / CASH' : $pesanan->payment_method }}</p></div>
            </div>


            @if($pesanan->resi_aktual)
            <div class="text-center mt-3 pt-2 border-t border-gray-700 z-10 relative">
                <p class="label">RESI AKTUAL ({{ $pesanan->jasa_ekspedisi_aktual }})</p>
                {{-- Elemen SVG barcode aktual --}}
                <svg id="barcodeAktual" class="barcode"></svg>
            </div>
            @endif

            <div class="mt-auto pt-3 text-center text-xs z-10 relative">
                <p>Terima kasih telah menggunakan <span class="font-semibold">Sancaka Express</span>.</p>
                <p class="font-bold mt-1">{{ \Carbon\Carbon::parse($pesanan->created_at)->format('d M Y H:i') }} Kirim Paket DI TOKOSANCAKA.COM</p>
            </div>
        </div>
    </div> <!-- PENUTUP WADAH KONTEN BLUR -->

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>

    <script>
        const RESI = {!! json_encode($pesanan->resi) !!};
        const TOKEN = document.querySelector('meta[name="csrf-token"]').content;

        // =========================================================
        // SCRIPT VALIDASI PIN KEAMANAN
        // =========================================================
        const correctPin = "{{ $pinRahasia }}";

        function verifyPin() {
            const pinInput = document.getElementById('pinInput');
            const pinError = document.getElementById('pinError');
            const pinModal = document.getElementById('pinValidationModal');
            const mainContent = document.getElementById('mainContentWrapper');

            if (pinInput.value === correctPin) {
                // Animasi hilangnya modal dan efek blur
                pinModal.style.opacity = '0';
                setTimeout(() => {
                    pinModal.remove();
                    mainContent.classList.remove('content-blurred');
                    document.body.classList.remove('overflow-hidden'); // Kembalikan fungsi scroll
                }, 300);
            } else {
                pinError.classList.remove('hidden');
                pinInput.value = '';
                pinInput.focus();
                pinInput.classList.add('translate-x-[-10px]', 'border-red-500');
                setTimeout(() => pinInput.classList.remove('translate-x-[-10px]', 'border-red-500'), 150);
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            const pinInput = document.getElementById('pinInput');
            if (pinInput) {
                pinInput.focus();
                // Eksekusi jika tekan Enter
                pinInput.addEventListener('keypress', function (e) {
                    if (e.key === 'Enter') verifyPin();
                });
                // Auto-cek jika sudah input 4 angka
                pinInput.addEventListener('input', function() {
                    this.value = this.value.replace(/[^0-9]/g, '');
                    if(this.value.length === 4) verifyPin();
                });
            }

            // --- BARCODE GENERATION (1D) ---
            try {
                const resiSancaka = RESI;
                if (resiSancaka) {
                    JsBarcode("#barcodeSancaka", resiSancaka, {
                        format: "CODE128",
                        textMargin: 10,
                        fontOptions: "bold",
                        height: 50,
                        width: 3.5,
                        fontSize: 30
                    });
                }
                @if($pesanan->resi_aktual)
                    const resiAktual = {!! json_encode($pesanan->resi_aktual ?? '') !!};
                    if (resiAktual) {
                        JsBarcode("#barcodeAktual", resiAktual, {
                            format: "CODE128",
                            textMargin: 10,
                            fontOptions: "bold",
                            height: 50,
                            width: 3.5,
                            fontSize: 30
                        });
                    }
                @endif
            } catch (e) {
                console.error("Gagal membuat barcode:", e);
            }

            // --- QR CODE GENERATION (2D) ---
            try {
                 new QRCode(document.getElementById("qrcode"), {
                     text: "https://tokosancaka.com/tracking/search?resi=" + RESI,
                     width: 75,
                     height: 75
                 });
            } catch (e) {
                console.error("Gagal membuat QR Code:", e);
            }
        });

        // --- FUNGSI FONTTE/WHATSAPP API ---
        function sendWaNotificationApi(target) {
            const button = (target === 'receiver')
                ? document.querySelector('button[onclick*="receiver"]')
                : document.querySelector('button[onclick*="sender"]');

            if (button) {
                button.disabled = true;
                button.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Mengirim...';
            }

            fetch('{{ route('api.whatsapp.send_resi') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': TOKEN
                },
                body: JSON.stringify({
                    resi: RESI,
                    target: target
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    Swal.fire('Berhasil!', data.message, 'success');
                } else {
                    Swal.fire('Gagal!', data.message || 'Terjadi kesalahan tidak dikenal.', 'error');
                }
                if (button) {
                    button.disabled = false;
                    button.innerHTML = (target === 'receiver')
                        ? '<i class="fab fa-whatsapp mr-1"></i> Kirim WA (Penerima)'
                        : '<i class="fab fa-whatsapp mr-1"></i> Kirim WA (Pengirim)';
                }
            })
            .catch(error => {
                console.error('API Error:', error);
                Swal.fire('Error', 'Gagal terhubung ke API Fonnte/Server. Periksa koneksi.', 'error');
                if (button) {
                    button.disabled = false;
                    button.innerHTML = (target === 'receiver')
                        ? '<i class="fab fa-whatsapp mr-1"></i> Kirim WA (Penerima)'
                        : '<i class="fab fa-whatsapp mr-1"></i> Kirim WA (Pengirim)';
                }
            });
        }

        // --- DOWNLOAD JPG SCRIPT ---
        document.getElementById('downloadBtn').addEventListener('click', function() {
            const downloadBtn = this;
            const labelElement = document.getElementById('label-resi');
            const resi = RESI;

            downloadBtn.disabled = true;
            downloadBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Mengonversi...';

            html2canvas(labelElement, {
                useCORS: true,
                scale: 2
            }).then(canvas => {
                const link = document.createElement('a');
                link.href = canvas.toDataURL('image/jpeg', 0.95);
                link.download = `resi-${resi}.jpg`;

                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);

                downloadBtn.disabled = false;
                downloadBtn.innerHTML = '<i class="fas fa-download mr-2"></i> Download JPG';

            }).catch(err => {
                console.error('Gagal konversi ke JPG:', err);
                Swal.fire('Gagal', 'Maaf, gagal mengunduh gambar. Silakan coba lagi.', 'error');
                downloadBtn.disabled = false;
                downloadBtn.innerHTML = '<i class="fas fa-download mr-2"></i> Download JPG';
            });
        });

    </script>

</body>
</html>
