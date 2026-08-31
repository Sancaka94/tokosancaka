<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice #{{ $pesanan->nomor_invoice }}</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    {{-- LIBRARY WAJIB UNTUK BARCODE & QR CODE --}}
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/qrcodejs/qrcode.min.js"></script>

    @php
        // 1. CEK STATUS PESANAN (DASAR & API)
        $statusPesanan = strtolower($pesanan->status ?? '');
        $isCancelled = in_array($statusPesanan, ['batal', 'cancel', 'gagal', 'dibatalkan', 'cancelled']);
        $statusText = $pesanan->status ?? 'Diproses / Menunggu Manifest';

        if (!empty($pesanan->resi) || !empty($pesanan->nomor_invoice)) {
            try {
                $resi = $pesanan->resi;
                $nomorInvoice = $pesanan->nomor_invoice ?? $resi;
                $expeditionRaw = strtolower($pesanan->expedition ?? $pesanan->jasa_ekspedisi_aktual ?? $pesanan->service_type ?? '');
                $apiStatusDitemukan = false;

                if (!str_contains($expeditionRaw, 'deliveree') && !str_contains($expeditionRaw, 'lalamove') && !str_contains($expeditionRaw, 'ipaymu') && !str_contains($expeditionRaw, 'komship') && !isset($pesanan->is_autokirim)) {
                    if (class_exists(\App\Services\KiriminAjaService::class)) {
                        $kiriminAja = new \App\Services\KiriminAjaService();
                        $serviceType = $pesanan->service_type ?? 'regular';
                        if (str_contains($serviceType, '-')) $serviceType = explode('-', $serviceType)[0];

                        $trackingData = $kiriminAja->track($serviceType, $nomorInvoice);
                        if (!$trackingData || !isset($trackingData['status']) || $trackingData['status'] !== true) {
                            if($resi) $trackingData = $kiriminAja->track($serviceType, $resi);
                        }

                        if ($trackingData && isset($trackingData['status']) && $trackingData['status'] === true) {
                            if (isset($trackingData['text']) && !empty($trackingData['text'])) {
                                $statusText = $trackingData['text'];
                                $apiStatusDitemukan = true;
                            } elseif (isset($trackingData['histories']) && is_array($trackingData['histories']) && count($trackingData['histories']) > 0) {
                                $statusText = $trackingData['histories'][0]['status'];
                                $apiStatusDitemukan = true;
                            }
                        }
                    }
                }

                if (!$apiStatusDitemukan && $resi) {
                    $cekDb = \Illuminate\Support\Facades\DB::table('tracking_histories')->where('resi', $resi)->orderBy('created_at', 'desc')->first();
                    if ($cekDb && isset($cekDb->status)) {
                        $statusText = $cekDb->status;
                    } elseif (class_exists(\App\Models\ScannedPackage::class)) {
                        $scanned = \App\Models\ScannedPackage::where('resi_number', $resi)->orderBy('created_at', 'desc')->first();
                        if ($scanned) $statusText = $scanned->status;
                    }
                }

                $rtStatusLower = strtolower($statusText);
                if (str_contains($rtStatusLower, 'cancel') || str_contains($rtStatusLower, 'batal') || str_contains($rtStatusLower, 'retur') || str_contains($rtStatusLower, 'gagal')) {
                    $isCancelled = true;
                }
            } catch(\Exception $e) {}
        }

        // =========================================================
        // FITUR BARU: GENERATE WATERMARK & PIN KEAMANAN DINAMIS
        // =========================================================

        // Mengambil 4 angka terakhir nomor HP Pengirim untuk PIN (TETAP RAHASIA)
        $hpPengirim = preg_replace('/[^0-9]/', '', $pesanan->sender_phone);
        $pinRahasia = substr($hpPengirim, -4);
        if (strlen($pinRahasia) < 4) $pinRahasia = str_pad($pinRahasia, 4, '0', STR_PAD_LEFT);

        // RUMUS KISI-KISI NOMOR HP (7 Angka Depan + Bintang)
        $panjangHp = strlen($hpPengirim);
        $tampilDepan = substr($hpPengirim, 0, 7);
        $jumlahBintang = $panjangHp > 7 ? $panjangHp - 7 : 4;
        $kisiKisiHp = $tampilDepan . str_repeat('*', $jumlahBintang);

        // Format Teks Watermark
        $tglTerbitWmk = \Carbon\Carbon::parse($pesanan->tanggal_pesanan)->format('d M Y H:i');
        if ($statusLunas && !empty($pesanan->resi)) {
            $wmText = "VALID LUNAS CV SANCAKA KARYA HUTAMA SANCAKA EXPRESS CREATED {$tglTerbitWmk} {$pesanan->resi}";
        } else {
            $wmText = "VALID BELUM LUNAS CV SANCAKA KARYA HUTAMA SANCAKA EXPRESS CREATED {$tglTerbitWmk} {$pesanan->nomor_invoice}";
        }
    @endphp

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap');

        body { font-family: 'Inter', sans-serif; }

        /* WATERMARK DINAMIS (Pola Tiling Seamless Seperti "PREVIEW") */
        .watermark-bg {
            position: fixed;
            top: -100%; left: -100%; width: 300%; height: 300%;
            z-index: 9999;
            pointer-events: none; /* Klik tetap tembus ke tombol di bawahnya */
            transform: rotate(-35deg);
            background-image: url("data:image/svg+xml,%3Csvg width='850' height='70' xmlns='http://www.w3.org/2000/svg'%3E%3Ctext x='425' y='45' fill='black' font-family='Arial, sans-serif' font-size='12' font-weight='800' text-anchor='middle'%3E{{ rawurlencode($wmText) }}%3C/text%3E%3C/svg%3E");
            background-repeat: repeat;
            background-position: center;
            opacity: 0.04; /* Sangat samar agar tidak mengganggu bacaan (hanya 4%) */
        }

        /* Desain Pita (Ribbon) UNPAID / LUNAS / REFUND */
        .ribbon-wrapper {
            position: absolute; right: -5px; top: -5px; z-index: 20;
            overflow: hidden; width: 150px; height: 150px; text-align: right;
        }
        .ribbon {
            font-size: 0.9rem; font-weight: 800; color: #FFF;
            text-transform: uppercase; text-align: center; line-height: 36px;
            transform: rotate(45deg); -webkit-transform: rotate(45deg);
            width: 200px; display: block; background: #dc2626; /* Merah */
            position: absolute; top: 25px; right: -45px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1); letter-spacing: 1px;
        }
        .ribbon.paid { background: #16a34a; } /* Hijau */
        .ribbon.cancelled { background: #ef4444; } /* Merah */

       @media print {
            @page { size: A4 portrait; margin: 10mm; }
            body {
                background: white !important; padding: 0 !important; margin: 0 !important;
                -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important;
                zoom: 0.90;
            }
            .no-print { display: none !important; }

            /* Memastikan watermark ikut terprint dengan baik */
            .watermark-bg {
                position: fixed !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            .print-container { box-shadow: none !important; max-width: 100% !important; width: 100% !important; margin: 0 !important; padding: 0 !important; border: none !important; }
            .md\:flex-row { flex-direction: row !important; display: flex !important; }
            .flex-col-reverse.md\:flex-row { flex-direction: row !important; }
            .md\:grid-cols-2 { display: grid !important; grid-template-columns: repeat(2, minmax(0, 1fr)) !important; }
            .md\:w-1\/2 { width: 50% !important; } .md\:w-3\/4 { width: 75% !important; } .md\:w-1\/4 { width: 25% !important; } .md\:w-\[320px\] { width: 280px !important; }
            .md\:items-center { align-items: center !important; }
            .gap-8 { gap: 1.5rem !important; } .mb-10, .mb-12, .mb-8 { margin-bottom: 15px !important; }
            .pb-8 { padding-bottom: 15px !important; } .pt-8 { padding-top: 15px !important; }
            .py-6, .py-8 { padding-top: 10px !important; padding-bottom: 10px !important; }
            .mt-12 { margin-top: 20px !important; }
            * { font-size: 11px !important; line-height: 1.4 !important; }
            h2.text-2xl { font-size: 18px !important; margin-bottom: 4px !important; }
            .text-\[13px\] { font-size: 11px !important; } .text-sm { font-size: 11px !important; } .text-base { font-size: 12px !important; }
            table, .grid, .print-header, .ribbon-wrapper { page-break-inside: avoid !important; }
            .ribbon-wrapper { right: -5px !important; top: -5px !important; }
        }

        .custom-scrollbar::-webkit-scrollbar { width: 4px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: #fafafa; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #e5e5e5; border-radius: 4px; }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #d4d4d4; }
    </style>
</head>
<body id="bodyMain" class="bg-slate-100 text-black overflow-hidden relative min-h-screen">

    <div class="watermark-bg"></div>

    <div id="pinValidationModal" class="fixed inset-0 bg-slate-900/80 backdrop-blur-md z-[10000] flex items-center justify-center p-4 transition-opacity duration-300">
        <div class="bg-white rounded-2xl shadow-2xl p-8 max-w-sm w-full text-center relative z-[10001]">
            <div class="w-16 h-16 bg-slate-100 rounded-full flex items-center justify-center mx-auto mb-4 border-2 border-slate-200">
                <i class="fas fa-shield-alt text-2xl text-gray-700"></i>
            </div>
            <h3 class="text-xl font-black text-gray-900 mb-2">Dokumen Terkunci</h3>

            <p class="text-sm text-gray-500 mb-6 leading-relaxed">
                Masukkan <strong class="text-red-600">4 Angka Terakhir</strong> Nomor HP Pengirim untuk membuka invoice ini.<br>
                <span class="inline-block mt-3 px-4 py-1.5 bg-slate-100 border border-slate-200 rounded-lg text-gray-700 font-mono tracking-widest text-[14px] font-bold shadow-sm">
                    {{ $kisiKisiHp }}
                </span>
            </p>

            <input type="password" id="pinInput" maxlength="4" pattern="\d*" inputmode="numeric" class="w-full text-center text-3xl tracking-[0.5em] font-bold border-2 border-gray-200 rounded-xl p-4 mb-2 focus:outline-none focus:border-black focus:ring-4 focus:ring-gray-100 transition-all" placeholder="••••">
            <p id="pinError" class="text-xs text-red-600 mb-4 hidden font-bold animate-pulse"><i class="fas fa-exclamation-circle mr-1"></i> PIN Salah! Silakan coba lagi.</p>

            <button onclick="verifyPin()" id="btnVerify" class="w-full bg-black text-white font-bold py-3.5 rounded-xl hover:bg-gray-800 transition-colors shadow-md mt-2">
                <i class="fas fa-unlock-alt mr-2"></i> Buka Dokumen
            </button>
        </div>
    </div>

    <div id="mainContentWrapper" class="py-8 blur-xl opacity-20 pointer-events-none select-none transition-all duration-700 relative z-40">

        @php
            // Pengaturan Ekspedisi
            $ship = \App\Helpers\ShippingHelper::parseShippingMethod($pesanan->expedition);
            $expeditionName = $ship['courier_name'] ?? 'SANCAKA';
            $expeditionService = $ship['service_name'] ?? 'Regular';

            $courierMap = [
                'jne' => 'https://tokosancaka.com/public/storage/logo-ekspedisi/jne.png',
                'tiki' => 'https://tokosancaka.com/public/storage/logo-ekspedisi/tiki.png',
                'pos' => 'https://tokosancaka.com/public/storage/logo-ekspedisi/posindonesia.png',
                'posindonesia' => 'https://tokosancaka.com/public/storage/logo-ekspedisi/posindonesia.png',
                'sicepat' => 'https://tokosancaka.com/public/storage/logo-ekspedisi/sicepat.png',
                'sap' => 'https://tokosancaka.com/public/storage/logo-ekspedisi/sap.png',
                'jnt' => 'https://tokosancaka.com/public/storage/logo-ekspedisi/jnt.png',
                'j&t' => 'https://tokosancaka.com/public/storage/logo-ekspedisi/jnt.png',
                'jtcargo' => 'https://tokosancaka.com/public/storage/logo-ekspedisi/jtcargo.png',
                'lion' => 'https://tokosancaka.com/public/storage/logo-ekspedisi/lion.png',
                'spx' => 'https://tokosancaka.com/public/storage/logo-ekspedisi/spx.png',
                'ninja' => 'https://tokosancaka.com/public/storage/logo-ekspedisi/ninja.png',
                'anteraja' => 'https://tokosancaka.com/public/storage/logo-ekspedisi/anteraja.png',
            ];

            $normalizedName = strtolower(str_replace(' ', '', $expeditionName));
            $finalLogoUrl = $ship['logo_url'] ?? asset('public/storage/logo-ekspedisi/' . $normalizedName . '.png');

            if (str_contains($normalizedName, 'cargo') && (str_contains($normalizedName, 'j&t') || str_contains($normalizedName, 'jt'))) {
                $finalLogoUrl = $courierMap['jtcargo'];
            } else {
                foreach ($courierMap as $key => $url) {
                    if (str_contains($normalizedName, $key)) { $finalLogoUrl = $url; break; }
                }
            }

            $senderAddress = implode(', ', array_filter([$pesanan->sender_address, $pesanan->sender_village, $pesanan->sender_district, $pesanan->sender_regency, $pesanan->sender_province, $pesanan->sender_postal_code]));
            $receiverAddress = implode(', ', array_filter([$pesanan->receiver_address, $pesanan->receiver_village, $pesanan->receiver_district, $pesanan->receiver_regency, $pesanan->receiver_province, $pesanan->receiver_postal_code]));

            // Sensor Nama
            $maskName = function($name) {
                if (empty($name)) return '';
                $words = explode(' ', $name);
                foreach ($words as &$word) {
                    if (strlen($word) > 1) {
                        $word = substr($word, 0, 1) . str_repeat('*', strlen($word) - 1);
                    }
                }
                return implode(' ', $words);
            };
        @endphp

        <div class="max-w-4xl mx-auto mb-6 flex flex-col sm:flex-row justify-between items-center no-print px-4 md:px-0 gap-3">
            <a href="javascript:history.back()" class="w-full sm:w-auto text-center bg-white border border-gray-200 text-black px-4 py-2 rounded-md hover:bg-slate-100 text-sm font-medium transition shadow-sm">
                <i class="fas fa-arrow-left mr-2"></i> Kembali
            </a>
            <button onclick="window.print()" class="w-full sm:w-auto text-center bg-black text-white px-4 py-2 rounded-md hover:bg-gray-800 text-sm font-medium transition shadow-sm">
                <i class="fas fa-print mr-2"></i> Print Invoice
            </button>
        </div>

        <div class="max-w-4xl mx-auto bg-white p-8 md:p-12 print-container relative border border-gray-200 shadow-sm">

            <div class="ribbon-wrapper no-print">
                <div class="ribbon {{ $isCancelled ? 'cancelled' : ($statusLunas ? 'paid' : '') }}">
                    {{ $isCancelled ? 'REFUND' : ($statusLunas ? 'LUNAS' : 'UNPAID') }}
                </div>
            </div>

            <div class="print-header flex flex-col md:flex-row justify-between items-start mb-10 pb-8 border-b border-gray-100 gap-8">

                <div class="w-full md:w-1/2">
                    <img src="https://tokosancaka.com/storage/uploads/sancaka.png" alt="Sancaka Express" class="h-24 mb-4 object-contain" onerror="this.src='https://placehold.co/200x50/FFFFFF/000000?text=SANCAKA+EXPRESS'">
                    <div class="text-[13px] text-gray-500 leading-relaxed">
                        <p class="font-bold text-black uppercase tracking-wide">Sancaka Express</p>
                        <p>Powered By CV SANCAKA KARYA HUTAMA</p>
                        <p>Jl. Dr. Wahidin No. 18A, Ketanggi Kab. Ngawi, Jawa Timur 63211</p>
                        <p class="font-medium text-black mt-1">Helpdesk: 085 745 808 809</p>
                    </div>
                </div>

                <div class="w-full md:w-[320px] pt-2 md:pt-8 relative z-10 no-print">
                    @if($isCancelled)
                        <div class="border border-gray-200 bg-slate-100 p-4 rounded-md text-right">
                            <h4 class="text-sm font-bold text-black uppercase tracking-wider mb-1">Dibatalkan & Refund</h4>
                            <p class="text-xs text-gray-500">Status: {{ $statusText }}</p>
                        </div>
                    @else
                        @if(session('error'))
                            <div class="border border-gray-300 bg-slate-100 text-black px-3 py-2 rounded-md mb-3 text-xs font-medium">
                                {{ session('error') }}
                            </div>
                        @endif

                        @if(!$statusLunas && empty($pesanan->payment_url) && !in_array($pesanan->payment_method, ['COD', 'CODBARANG', 'Cash', 'Potong Saldo']))
                            <div class="text-right mb-2">
                                <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Selesaikan Pembayaran</p>
                            </div>
                            <form id="invoice-payment-form" action="{{ route('invoice.proses_bayar', $pesanan->nomor_invoice) }}" method="POST">
                                @csrf
                                <button type="button" id="paymentMethodButton" class="w-full bg-white border border-gray-200 hover:border-black p-3 rounded-md flex items-center justify-between transition-colors mb-3 group">
                                    <div class="flex items-center gap-3">
                                        <div class="w-6 h-6 flex items-center justify-center">
                                            <img id="paymentMethodImg" src="https://tokosancaka.com/public/assets/saldo.png" class="max-w-full max-h-full">
                                        </div>
                                        <span id="paymentMethodLabel" class="text-sm font-semibold text-black">Pilih Bank...</span>
                                    </div>
                                    <i class="fas fa-chevron-down text-gray-400 group-hover:text-black text-xs"></i>
                                </button>
                                <input type="hidden" name="payment_method" id="payment_method" required>
                                <button type="submit" id="submit-button" class="w-full bg-black text-white font-medium py-2.5 px-4 rounded-md text-sm hover:bg-gray-800 transition-colors disabled:opacity-50 shadow-sm">
                                    Bayar Tagihan
                                </button>
                            </form>

                        @elseif(!$statusLunas && !empty($pesanan->payment_url))
                            <div class="border border-gray-200 bg-slate-100 p-4 rounded-md text-center">
                                @if($pesanan->payment_method == 'BCA_QRIS')
                                    <p class="text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-3">Scan QR Code BCA</p>
                                    <div class="bg-white p-2 rounded border border-gray-200 inline-block mb-2 shadow-sm">
                                        <img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data={{ urlencode($pesanan->payment_url) }}" alt="QRIS" class="w-24 h-24">
                                    </div>
                                @else
                                    <p class="text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-2">Pembayaran via {{ str_replace('_', ' ', $pesanan->payment_method) }}</p>
                                    <a href="{{ $pesanan->payment_url }}" class="block bg-black text-white font-medium py-2.5 px-4 rounded-md text-sm hover:bg-gray-800 transition-colors mt-2 shadow-sm">
                                        Lanjut Bayar &rarr;
                                    </a>
                                @endif
                            </div>

                        @elseif(!$statusLunas)
                            <div class="border border-gray-200 bg-slate-100 p-4 rounded-md text-right">
                                <p class="text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1">Menunggu Verifikasi</p>
                                <p class="text-sm font-semibold text-black">{{ $pesanan->payment_method }}</p>
                            </div>
                        @endif
                    @endif
                </div>
            </div>

            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-6 mb-10">
                <div>
                    <h2 class="text-2xl font-black text-black tracking-tight mb-2">INVOICE #{{ $pesanan->nomor_invoice }}</h2>
                    <div class="text-[13px] text-gray-500 flex flex-col gap-1">
                        <p><span class="w-24 inline-block font-medium text-black">Tgl Order</span>: {{ \Carbon\Carbon::parse($pesanan->tanggal_pesanan)->format('d M Y, H:i') }}</p>
                        <p><span class="w-24 inline-block font-medium text-black">Batas Bayar</span>: {{ \Carbon\Carbon::parse($pesanan->tanggal_pesanan)->addDays(1)->format('d M Y, H:i') }}</p>
                    </div>
                </div>

                <div class="w-full md:w-auto p-4 border border-gray-200 rounded-md text-center bg-slate-100/50 min-w-[200px]">
                    <p class="text-[10px] font-bold uppercase tracking-widest text-gray-400 mb-2">NO. RESI (AWB)</p>

                    @if($isCancelled)
                        <p class="text-sm font-bold text-black uppercase">REFUND / BATAL</p>
                    @elseif($statusLunas && $pesanan->resi)
                        <div class="bg-white rounded border border-gray-200 p-2 mb-2 shadow-sm">
                            <svg id="barcodeResi" class="w-full max-w-[180px] mx-auto h-10"></svg>
                        </div>
                        <a href="https://tokosancaka.com/tracking/search?resi={{ $pesanan->resi }}" target="_blank" class="text-[11px] font-bold text-black hover:underline uppercase tracking-wide">
                            Lacak Pengiriman &rarr;
                        </a>
                    @else
                        <p class="text-xs font-medium text-gray-500 italic">Diterbitkan setelah lunas</p>
                    @endif
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-12">
                <div>
                    <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-3 border-b border-gray-100 pb-2">Pengirim (Invoiced To)</p>
                    <p class="font-bold text-base text-black uppercase">{{ $maskName($pesanan->sender_name) }}</p>
                    <p class="text-gray-500 mt-2 text-[13px] leading-relaxed">{{ $senderAddress }}</p>
                    <p class="mt-2 text-[13px] font-medium text-black">{{ substr($pesanan->sender_phone, 0, 4) }}*** *** ***</p>
                </div>
                <div>
                    <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-3 border-b border-gray-100 pb-2">Penerima (Ship To)</p>
                    <p class="font-bold text-base text-black uppercase">{{ $maskName($pesanan->receiver_name) }}</p>
                    <p class="text-gray-500 mt-2 text-[13px] leading-relaxed">{{ $receiverAddress }}</p>
                    <p class="mt-2 text-[13px] font-medium text-black">{{ substr($pesanan->receiver_phone, 0, 4) }}*** *** ***</p>
                </div>
            </div>

            <div class="mb-10 border border-gray-200 rounded-xl overflow-hidden relative z-40">
                <table class="w-full text-sm text-left">
                    <thead class="bg-slate-100 border-b border-gray-200">
                        <tr>
                            <th class="py-4 px-6 font-bold text-black uppercase text-[11px] tracking-wider w-3/4">Rincian Layanan</th>
                            <th class="py-4 px-6 font-bold text-black uppercase text-[11px] tracking-wider w-1/4 text-right">Biaya</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        <tr>
                            <td class="py-6 px-6 align-top">
                                <div class="flex items-start gap-4">
                                    <div class="w-12 h-12 flex-shrink-0 flex items-center justify-center border border-gray-200 rounded-md p-1.5 bg-white shadow-sm">
                                        <img src="{{ $finalLogoUrl }}" class="max-h-full max-w-full object-contain" alt="Ekspedisi" onerror="this.style.display='none'">
                                    </div>
                                    <div class="w-full">
                                        <h4 class="font-bold text-black text-sm uppercase mb-1">{{ $expeditionName }} - {{ $expeditionService }}</h4>
                                        <p class="text-[11px] text-gray-500 mb-3">Layanan Pengiriman Ekspedisi</p>

                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-y-3 gap-x-6 text-[12px] bg-slate-50 p-4 rounded-lg border border-gray-200">
                                            <p class="flex items-center"><span class="font-bold text-gray-700 w-24 flex-shrink-0">Isi Paket:</span> <span class="text-gray-600 font-medium">{{ $pesanan->item_description }}</span></p>
                                            <p class="flex items-center"><span class="font-bold text-gray-700 w-24 flex-shrink-0">Berat:</span> <span class="text-gray-600 font-medium">{{ number_format($pesanan->weight, 0, ',', '.') }} Gram</span></p>
                                            <p class="flex items-center"><span class="font-bold text-gray-700 w-24 flex-shrink-0">Dimensi:</span> <span class="text-gray-600 font-medium">{{ $pesanan->length ?? 0 }}x{{ $pesanan->width ?? 0 }}x{{ $pesanan->height ?? 0 }} cm</span></p>
                                            <p class="flex items-center"><span class="font-bold text-gray-700 w-24 flex-shrink-0">Nilai Brg:</span> <span class="text-gray-600 font-medium">Rp {{ number_format($pesanan->item_price, 0, ',', '.') }}</span></p>
                                            <p class="flex items-center"><span class="font-bold text-gray-700 w-24 flex-shrink-0">Layanan:</span> <span class="text-gray-600 font-medium uppercase">{{ $pesanan->service_type ?? $expeditionService }}</span></p>
                                            <p class="flex items-center"><span class="font-bold text-gray-700 w-24 flex-shrink-0">Asuransi:</span> <span class="text-gray-600 font-medium">{{ (strtolower($pesanan->ansuransi ?? '') == 'iya' || $pesanan->insurance_cost > 0) ? 'Ya' : 'Tidak' }}</span></p>
                                        </div>

                                    </div>
                                </div>
                            </td>
                            <td class="py-6 px-6 text-right align-top font-semibold text-black text-[13px]">
                                Rp {{ number_format($pesanan->shipping_cost, 0, ',', '.') }}
                            </td>
                        </tr>

                        @if($pesanan->insurance_cost > 0)
                        <tr class="bg-white">
                            <td class="py-4 px-6 text-gray-500 text-[13px] font-medium text-right">Biaya Asuransi</td>
                            <td class="py-4 px-6 text-right font-semibold text-black text-[13px]">Rp {{ number_format($pesanan->insurance_cost, 0, ',', '.') }}</td>
                        </tr>
                        @endif

                        @if($pesanan->cod_fee > 0)
                        <tr class="bg-white">
                            <td class="py-4 px-6 text-gray-500 text-[13px] font-medium text-right">Biaya Penanganan (Fee)</td>
                            <td class="py-4 px-6 text-right font-semibold text-black text-[13px]">Rp {{ number_format($pesanan->cod_fee, 0, ',', '.') }}</td>
                        </tr>
                        @endif
                    </tbody>
                </table>

                <div class="bg-slate-100 border-t border-gray-200 p-6 flex justify-end">
                    <div class="w-full sm:w-1/2 md:w-1/3">
                        <div class="flex justify-between py-1 text-[13px] text-gray-500 mb-2">
                            <span>Sub Total</span>
                            <span class="font-semibold text-black">Rp {{ number_format($pesanan->price, 0, ',', '.') }}</span>
                        </div>
                        <div class="flex justify-between py-1 text-[13px] text-gray-500 mb-3">
                            <span>Credit</span>
                            <span class="font-semibold text-black">Rp 0,00</span>
                        </div>
                        <div class="flex justify-between py-3 border-t border-gray-200 text-sm font-black text-black uppercase tracking-wide">
                            <span>Grand Total</span>
                            <span>Rp {{ number_format($pesanan->price, 0, ',', '.') }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex flex-col-reverse md:flex-row gap-8 mb-8 relative z-40">

                <div class="w-full md:w-3/4">
                    <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-4 border-b border-gray-100 pb-2">Riwayat Transaksi</p>
                    <div class="border border-gray-200 rounded-lg overflow-hidden">
                        <table class="w-full text-[13px] text-left">
                            <thead class="bg-slate-100 border-b border-gray-200 text-gray-500">
                                <tr>
                                    <th class="py-3 px-4 font-semibold">Tanggal</th>
                                    <th class="py-3 px-4 font-semibold">Metode</th>
                                    <th class="py-3 px-4 font-semibold text-right">Nominal</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 bg-white">
                                @if($statusLunas)
                                <tr>
                                    <td class="py-4 px-4">{{ \Carbon\Carbon::parse($pesanan->updated_at)->format('d/m/Y H:i') }}</td>
                                    <td class="py-4 px-4 uppercase text-xs font-semibold text-black">{{ str_replace('_', ' ', $pesanan->payment_method) }}</td>
                                    <td class="py-4 px-4 text-right font-semibold text-black">Rp {{ number_format($pesanan->price, 0, ',', '.') }}</td>
                                </tr>
                                @else
                                <tr>
                                    <td colspan="3" class="py-6 italic text-gray-400 text-center text-xs">Belum ada transaksi masuk.</td>
                                </tr>
                                @endif
                            </tbody>
                            <tfoot class="bg-slate-100 border-t border-gray-200">
                                <tr>
                                    <td colspan="2" class="py-3 px-4 text-right font-semibold text-gray-500 text-xs uppercase tracking-wide">Sisa Tagihan</td>
                                    <td class="py-3 px-4 text-right font-black text-sm text-black">
                                        Rp {{ $statusLunas ? '0,00' : number_format($pesanan->price, 0, ',', '.') }}
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <div class="w-full md:w-1/4">
                    <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-4 border-b border-gray-100 pb-2 text-center md:text-left">Status Lacak</p>

                    @if($isCancelled)
                        <div class="border border-gray-200 bg-slate-100 rounded-lg p-4 text-center">
                            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Status Paket</p>
                            <p class="text-xs font-bold text-black mb-4">{{ $statusText }}</p>
                            <button onclick="syncTracking(this)" class="no-print w-full bg-white border border-gray-300 hover:border-black text-black text-[11px] font-bold py-2 rounded-md transition-colors uppercase tracking-wider shadow-sm">
                                Sync API
                            </button>
                        </div>
                    @elseif($statusLunas && $pesanan->resi)
                        <div class="border border-gray-200 bg-slate-100 rounded-lg p-4 text-center mb-3">
                            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Status Paket</p>
                            <p class="text-xs font-bold text-black mb-4 truncate" title="{{ $statusText }}">{{ $statusText }}</p>
                            <button onclick="syncTracking(this)" class="no-print w-full bg-white border border-gray-300 hover:border-black text-black text-[11px] font-bold py-2 rounded-md transition-colors uppercase tracking-wider shadow-sm">
                                Sync API
                            </button>
                        </div>
                        <div id="qrcode" class="p-3 bg-white border border-gray-200 rounded-lg flex justify-center shadow-sm"></div>
                    @else
                        <div class="h-[120px] bg-slate-100 border border-dashed border-gray-300 flex flex-col items-center justify-center rounded-lg">
                            <i class="fas fa-lock text-gray-300 text-2xl mb-2"></i>
                            <span class="text-[11px] text-gray-400 font-medium tracking-wide">Terkunci</span>
                        </div>
                    @endif
                </div>

            </div>

            <div class="text-center text-[11px] text-gray-400 mt-12 pt-6 border-t border-gray-100 relative z-40">
                Dicetak otomatis dari sistem <strong>tokosancaka.com</strong> pada {{ date('d M Y, H:i') }} WIB.<br>Dokumen sah tanpa tanda tangan fisik.
            </div>

        </div>
    </div> <div id="paymentModal" class="no-print fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-[10500] hidden transition-all duration-300">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-3xl mx-4 sm:mx-auto flex flex-col max-h-[85vh]">
            <div class="flex justify-between items-center p-5 border-b border-gray-100">
                <h3 class="text-lg font-bold text-black tracking-wide">Pilih Metode Pembayaran</h3>
                <button type="button" id="closeModalButton" class="text-gray-400 hover:text-black transition-colors p-1">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
            <div class="p-5 overflow-y-auto custom-scrollbar flex-1 bg-slate-50">
                <ul id="paymentOptionsList" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <li class="col-span-full pb-1 text-[11px] font-bold text-gray-400 uppercase tracking-widest border-b border-gray-200">Direct Payment</li>

                    <li class="payment-option cursor-pointer flex items-center p-3 border border-gray-200 rounded-xl bg-white hover:border-black hover:shadow-md transition-all group" data-value="BCA_QRIS" data-label="BCA QRIS" data-img="https://tokosancaka.com/assets/bca.png">
                        <img src="https://tokosancaka.com/assets/bca.png" class="w-12 h-auto mr-4 object-contain" alt="BCA">
                        <div class="flex flex-col"><span class="text-[13px] font-bold text-black">BCA QRIS</span><span class="text-[11px] text-gray-500">Generate Barcode</span></div>
                    </li>
                    <li class="payment-option cursor-pointer flex items-center p-3 border border-gray-200 rounded-xl bg-white hover:border-black hover:shadow-md transition-all group" data-value="DOKU_JOKUL" data-label="DOKU Gateway" data-img="https://tokosancaka.com/public/assets/doku.png">
                        <img src="https://tokosancaka.com/public/assets/doku.png" class="w-12 h-auto mr-4 object-contain" alt="DOKU">
                        <div class="flex flex-col"><span class="text-[13px] font-bold text-black">DOKU Gateway</span><span class="text-[11px] text-gray-500">VA, E-Wallet, CC Lokal</span></div>
                    </li>

                    <li class="col-span-full pt-4 pb-1 text-[11px] font-bold text-gray-400 uppercase tracking-widest border-b border-gray-200">DANA Enterprise</li>
                    @php
                        $user = Auth::user();
                        $userDanaToken = $user ? $user->dana_access_token : null;
                        $userDanaBalance = $user ? ($user->dana_user_balance ?? 0) : 0;
                        $hasDanaBinding = !empty($userDanaToken);
                    @endphp
                    <li class="payment-option cursor-pointer flex items-center p-3 border border-gray-200 rounded-xl bg-white hover:border-black hover:shadow-md transition-all group" data-value="DANA" data-label="DANA Checkout" data-img="{{ asset('public/assets/dana.webp') }}">
                        <img src="{{ asset('public/assets/dana.webp') }}" class="w-12 h-auto mr-4 object-contain" onerror="this.src='https://upload.wikimedia.org/wikipedia/commons/7/72/Logo_dana_blue.svg'">
                        <div class="flex flex-col"><span class="text-[13px] font-bold text-black">DANA Web</span><span class="text-[11px] text-gray-500">Arahkan ke App</span></div>
                    </li>
                    @if($hasDanaBinding)
                        <li class="payment-option cursor-pointer flex items-center p-3 border border-gray-400 rounded-xl bg-slate-100 hover:border-black hover:shadow-md transition-all group" data-value="DANA_BINDING" data-label="DANA Auto-Debit" data-img="{{ asset('public/assets/dana.webp') }}">
                            <img src="{{ asset('public/assets/dana.webp') }}" class="w-12 h-auto mr-4 object-contain" onerror="this.src='https://upload.wikimedia.org/wikipedia/commons/7/72/Logo_dana_blue.svg'">
                            <div class="flex flex-col flex-1"><span class="text-[13px] font-bold text-black">DANA Auto-Debit</span><span class="text-[11px] text-gray-600 font-medium">Saldo: Rp{{ number_format($userDanaBalance, 0, ',', '.') }}</span></div>
                            <span class="bg-black text-white text-[10px] font-semibold px-2 py-1 rounded">Tersambung</span>
                        </li>
                    @else
                        <li class="col-span-1 flex items-center p-3 border border-dashed border-gray-300 rounded-xl bg-slate-100 justify-between">
                            <div class="flex items-center"><img src="{{ asset('public/assets/dana.webp') }}" class="w-12 h-auto mr-4 object-contain opacity-60" onerror="this.src='https://upload.wikimedia.org/wikipedia/commons/7/72/Logo_dana_blue.svg'"><div class="flex flex-col"><span class="text-[13px] font-bold text-gray-500">DANA Auto-Debit</span></div></div>
                            <a href="{{ url('/dana/start-binding') }}" class="bg-white border border-gray-300 text-black hover:border-black text-[11px] font-semibold px-3 py-1.5 rounded transition-colors shadow-sm">Hubungkan</a>
                        </li>
                    @endif

                    <li class="col-span-full pt-4 pb-1 text-[11px] font-bold text-gray-400 uppercase tracking-widest border-b border-gray-200">Global & Otomatis</li>
                    <li class="payment-option cursor-pointer flex items-center p-3 border border-gray-200 rounded-xl bg-white hover:border-black hover:shadow-md transition-all group" data-value="PAYPAL" data-label="PayPal" data-img="https://tokosancaka.com/public/assets/paypal.png">
                        <img src="https://tokosancaka.com/public/assets/paypal.png" class="w-12 h-auto mr-4 object-contain" onerror="this.src='https://placehold.co/40x40/EFEFEF/AAAAAA?text=PP'">
                        <div class="flex flex-col"><span class="text-[13px] font-bold text-black">PayPal / CC</span><span class="text-[11px] text-gray-500">Pembayaran USD</span></div>
                    </li>

                    @if(isset($tripayChannels) && count($tripayChannels) > 0)
                        @foreach($tripayChannels as $channel)
                            @if($channel['active'])
                            <li class="payment-option cursor-pointer flex items-center p-3 border border-gray-200 rounded-xl bg-white hover:border-black hover:shadow-md transition-all group" data-value="{{ $channel['code'] }}" data-label="{{ $channel['name'] }}" data-img="{{ $channel['icon_url'] }}">
                                <img src="{{ $channel['icon_url'] }}" class="w-12 h-auto mr-4 object-contain" onerror="this.src='https://placehold.co/40x40/EFEFEF/AAAAAA?text=IMG'">
                                <div class="flex flex-col"><span class="text-[13px] font-bold text-black">{{ $channel['name'] }}</span></div>
                            </li>
                            @endif
                        @endforeach
                    @endif
                </ul>
            </div>
        </div>
    </div>

    <script>
    const correctPin = "{{ $pinRahasia }}";

    function verifyPin() {
        const pinInput = document.getElementById('pinInput');
        const pinError = document.getElementById('pinError');
        const pinModal = document.getElementById('pinValidationModal');
        const mainContent = document.getElementById('mainContentWrapper');

        if (pinInput.value === correctPin) {
            pinModal.style.opacity = '0';
            setTimeout(() => {
                pinModal.remove();
                document.getElementById('bodyMain').classList.remove('overflow-hidden');
                mainContent.classList.remove('blur-xl', 'opacity-20', 'pointer-events-none', 'select-none');
            }, 300);
        } else {
            pinError.classList.remove('hidden');
            pinInput.value = '';
            pinInput.focus();
            pinInput.classList.add('translate-x-[-10px]', 'border-red-500');
            setTimeout(() => pinInput.classList.remove('translate-x-[-10px]', 'border-red-500'), 150);
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        const pinInput = document.getElementById('pinInput');
        pinInput.focus();
        pinInput.addEventListener('keypress', function (e) {
            if (e.key === 'Enter') verifyPin();
        });
        pinInput.addEventListener('input', function() {
            this.value = this.value.replace(/[^0-9]/g, '');
            if(this.value.length === 4) verifyPin();
        });

        const isPaid = {{ $statusLunas ? 'true' : 'false' }};
        const isCancelled = {{ $isCancelled ? 'true' : 'false' }};
        const resiSancaka = "{!! $pesanan->resi !!}";

        if (!isCancelled && isPaid && resiSancaka) {
            try { JsBarcode("#barcodeResi", resiSancaka, { format: "CODE128", lineColor: "#000000", textMargin: 4, fontOptions: "bold", fontSize: 13, height: 40, width: 2, displayValue: true }); } catch (e) {}
            try { new QRCode(document.getElementById("qrcode"), { text: "https://tokosancaka.com/tracking/search?resi=" + resiSancaka, width: 75, height: 75, colorDark : "#000000", colorLight : "#ffffff", correctLevel : QRCode.CorrectLevel.M }); } catch (e) {}
        }

        const paymentModal = document.getElementById('paymentModal');
        const paymentMethodButton = document.getElementById('paymentMethodButton');
        const closeModalButton = document.getElementById('closeModalButton');
        const paymentOptionsList = document.getElementById('paymentOptionsList');
        const paymentMethodInput = document.getElementById('payment_method');
        const invoiceForm = document.getElementById('invoice-payment-form');
        const submitButton = document.getElementById('submit-button');

        function openPaymentModal() { paymentModal.classList.remove('hidden'); document.body.style.overflow = 'hidden'; }
        function closePaymentModal() { paymentModal.classList.add('hidden'); document.body.style.overflow = 'auto'; }

        if(paymentMethodButton) {
            paymentMethodButton.addEventListener('click', openPaymentModal);
            closeModalButton.addEventListener('click', closePaymentModal);
            paymentModal.addEventListener('click', e => { if (e.target === paymentModal) closePaymentModal(); });

            paymentOptionsList.querySelectorAll('.payment-option').forEach(item => {
                item.addEventListener('click', function () {
                    paymentMethodInput.value = this.dataset.value;
                    paymentOptionsList.querySelectorAll('.payment-option').forEach(li => {
                        li.classList.remove('border-black', 'ring-1', 'ring-black');
                        li.classList.add('border-gray-200');
                    });
                    this.classList.remove('border-gray-200');
                    this.classList.add('border-black', 'ring-1', 'ring-black');
                    document.getElementById('paymentMethodLabel').textContent = this.dataset.label;
                    document.getElementById('paymentMethodImg').src = this.dataset.img;
                    closePaymentModal();
                });
            });

            invoiceForm.addEventListener('submit', function(e) {
                if (paymentMethodInput.value === "") {
                    e.preventDefault();
                    alert('Silakan pilih metode pembayaran terlebih dahulu.');
                    return;
                }
                submitButton.disabled = true;
                submitButton.innerHTML = 'Memproses...';
            });
        }
    });

    function syncTracking(btn) {
        btn.innerHTML = 'Syncing...';
        btn.disabled = true;
        setTimeout(() => { window.location.reload(); }, 500);
    }
    </script>
</body>
</html>
