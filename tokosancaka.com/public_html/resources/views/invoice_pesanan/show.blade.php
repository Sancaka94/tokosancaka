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

    <style>
        /* Desain Pita (Ribbon) UNPAID / LUNAS */
        .ribbon-wrapper {
            position: absolute; right: -5px; top: -5px; z-index: 10;
            overflow: hidden; width: 150px; height: 150px; text-align: right;
        }
        .ribbon {
            font-size: 1.1rem; font-weight: 900; color: #FFF;
            text-transform: uppercase; text-align: center; line-height: 40px;
            transform: rotate(45deg); -webkit-transform: rotate(45deg);
            width: 200px; display: block; background: #dc2626; 
            position: absolute; top: 25px; right: -45px;
            box-shadow: 0 3px 10px -5px rgba(0, 0, 0, 1);
            letter-spacing: 1px;
        }
        .ribbon.paid { background: #16a34a; }
        .ribbon.cancelled { background: #4b5563; }
        
        @media print {
            /* 1. Reset Ukuran Kertas & Margin */
            @page {
                size: A4 portrait;
                margin: 5mm; 
            }
            body { 
                background: white !important; 
                padding: 0 !important; 
                margin: 0 !important; 
                -webkit-print-color-adjust: exact; 
                print-color-adjust: exact; 
            }
            .no-print { display: none !important; }

            /* 2. Hapus Shadow & Padding Container Utama */
            .print-container { 
                box-shadow: none !important; 
                max-width: 100% !important; 
                width: 100% !important; 
                margin: 0 !important; 
                padding: 0 !important; 
                border: none !important; 
            }

            /* 3. PAKSA ELEMEN BERSEBELAHAN */
            .flex.flex-col.md\:flex-row {
                flex-direction: row !important;
                justify-content: space-between !important;
                align-items: center !important;
                gap: 10px !important;
            }
            
            .grid.grid-cols-1.md\:grid-cols-2 {
                display: grid !important;
                grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
                gap: 10px !important;
            }

            .flex.flex-col-reverse.md\:flex-row {
                flex-direction: row !important;
                gap: 10px !important;
            }
            .w-full.md\:w-3\/4 { width: 75% !important; }
            .w-full.md\:w-1\/4 { width: 25% !important; }

            /* 4. PRESS SEMUA PADDING & MARGIN */
            .mb-6, .md\:mb-8, .mb-8, .mb-4 { margin-bottom: 8px !important; }
            .pb-6 { padding-bottom: 8px !important; }
            .p-4, .p-5, .sm\:p-8, .md\:p-12 { padding: 8px !important; }
            .mt-8 { margin-top: 10px !important; }
            
            table th, table td { padding: 4px 6px !important; }
            .py-4 { padding-top: 6px !important; padding-bottom: 6px !important; }

            /* 5. KECILKAN UKURAN FONT GLOBAL SAAT PRINT */
            * { font-size: 10px !important; line-height: 1.3 !important; }
            h2, .text-xl, .sm\:text-2xl, .font-extrabold { font-size: 14px !important; margin-bottom: 2px !important; }
            .text-lg, .sm\:text-lg { font-size: 12px !important; }
            .text-xs, .sm\:text-sm { font-size: 9px !important; }

            /* 6. KECILKAN LOGO & BARCODE SAAT PRINT */
            img[alt="Sancaka Express"] { height: 40px !important; margin-bottom: 5px !important; }
            svg#barcodeResi { height: 35px !important; max-width: 180px !important; }
            #qrcode { padding: 2px !important; }
            #qrcode img { width: 45px !important; height: 45px !important; margin: 0 auto; }
        }

        /* Custom Scrollbar Modal */
        .custom-scrollbar::-webkit-scrollbar { width: 6px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: #f8fafc; border-radius: 8px; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 8px; }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
    </style>
</head>
<body class="bg-gray-100 py-4 sm:py-8 font-sans text-gray-800">

    @php
        // 1. Parsing Helper Ekspedisi
        $ship = \App\Helpers\ShippingHelper::parseShippingMethod($pesanan->expedition);
        $expeditionName = $ship['courier_name'] ?? 'SANCAKA'; 
        $expeditionService = $ship['service_name'] ?? 'Regular';

        // 2. Mapping Logo Ekspedisi
        $courierMap = [
            'jne'           => 'https://tokosancaka.com/public/storage/logo-ekspedisi/jne.png',
            'tiki'          => 'https://tokosancaka.com/public/storage/logo-ekspedisi/tiki.png',
            'pos'           => 'https://tokosancaka.com/public/storage/logo-ekspedisi/posindonesia.png',
            'posindonesia'  => 'https://tokosancaka.com/public/storage/logo-ekspedisi/posindonesia.png',
            'sicepat'       => 'https://tokosancaka.com/public/storage/logo-ekspedisi/sicepat.png',
            'sap'           => 'https://tokosancaka.com/public/storage/logo-ekspedisi/sap.png',
            'jnt'           => 'https://tokosancaka.com/public/storage/logo-ekspedisi/jnt.png',
            'j&t'           => 'https://tokosancaka.com/public/storage/logo-ekspedisi/jnt.png',
            'jtcargo'       => 'https://tokosancaka.com/public/storage/logo-ekspedisi/jtcargo.png',
            'lion'          => 'https://tokosancaka.com/public/storage/logo-ekspedisi/lion.png',
            'spx'           => 'https://tokosancaka.com/public/storage/logo-ekspedisi/spx.png',
            'ninja'         => 'https://tokosancaka.com/public/storage/logo-ekspedisi/ninja.png',
            'anteraja'      => 'https://tokosancaka.com/public/storage/logo-ekspedisi/anteraja.png',
        ];

        $normalizedName = strtolower(str_replace(' ', '', $expeditionName));
        $finalLogoUrl = $ship['logo_url'] ?? asset('public/storage/logo-ekspedisi/' . $normalizedName . '.png');

        if (str_contains($normalizedName, 'cargo') && (str_contains($normalizedName, 'j&t') || str_contains($normalizedName, 'jt'))) {
            $finalLogoUrl = $courierMap['jtcargo'];
        } else {
            foreach ($courierMap as $key => $url) {
                if (str_contains($normalizedName, $key)) {
                    $finalLogoUrl = $url; break;
                }
            }
        }

        // 3. Format Alamat
        $senderAddress = implode(', ', array_filter([
            $pesanan->sender_address, $pesanan->sender_village, 
            $pesanan->sender_district, $pesanan->sender_regency, 
            $pesanan->sender_province, $pesanan->sender_postal_code
        ]));

        $receiverAddress = implode(', ', array_filter([
            $pesanan->receiver_address, $pesanan->receiver_village, 
            $pesanan->receiver_district, $pesanan->receiver_regency, 
            $pesanan->receiver_province, $pesanan->receiver_postal_code
        ]));

        $statusPesanan = strtolower($pesanan->status ?? '');
        $isCancelled = in_array($statusPesanan, ['batal', 'cancel', 'gagal', 'dibatalkan', 'cancelled']);

    @endphp

    <!-- ACTION BAR -->
    <div class="max-w-4xl mx-auto mb-4 sm:mb-6 flex flex-col sm:flex-row justify-between items-center no-print px-4 md:px-0 gap-3">
        <a href="javascript:history.back()" class="w-full sm:w-auto text-center bg-white border border-gray-300 text-gray-800 px-5 py-2.5 rounded shadow-sm hover:bg-gray-50 font-semibold transition">
            <i class="fas fa-arrow-left mr-2"></i> Kembali
        </a>
        <button onclick="window.print()" class="w-full sm:w-auto text-center bg-gray-800 text-white px-6 py-2.5 rounded shadow-md hover:bg-gray-700 font-bold transition">
            <i class="fas fa-print mr-2"></i> Print Invoice
        </button>
    </div>

    <!-- KERTAS INVOICE A4 -->
    <div class="max-w-4xl mx-auto bg-white p-5 sm:p-8 md:p-12 print-container relative shadow-2xl sm:rounded-lg border border-gray-200">
        
        <!-- PITA STATUS (UNPAID / LUNAS / BATAL) -->
        <div class="ribbon-wrapper no-print">
            <div class="ribbon {{ $isCancelled ? 'cancelled' : ($statusLunas ? 'paid' : '') }}">
                {{ $isCancelled ? 'DIBATALKAN' : ($statusLunas ? 'LUNAS' : 'UNPAID') }}
            </div>
        </div>

        <!-- HEADER INVOICE -->
        <div class="mb-6 md:mb-8 border-b border-gray-300 pb-6 text-center md:text-left">
            <img src="https://tokosancaka.com/storage/uploads/sancaka.png" alt="Sancaka Express" class="h-16 md:h-20 object-contain mx-auto md:mx-0 mb-3" onerror="this.src='https://placehold.co/250x80?text=Sancaka+Express'">
            <div class="text-sm">
                <p class="font-extrabold text-xl text-gray-900 mb-1 uppercase tracking-wider">Sancaka Express</p>
                <p class="text-gray-600">Jl. Dr. Wahidin No. 18A, Ketanggi</p>
                <p class="text-gray-600">Kabupaten Ngawi, Jawa Timur 63211</p>
                <p class="text-gray-600 font-medium">Telp: 08574580809</p>
            </div>
        </div>

        <!-- INVOICE INFO & BARCODE RESI (1D) -->
        <div class="bg-gray-50 p-4 sm:p-5 border border-gray-200 mb-6 md:mb-8 rounded-lg flex flex-col md:flex-row justify-between items-center gap-4">
            <div class="w-full md:w-auto text-center md:text-left">
                <h2 class="text-xl sm:text-2xl font-black text-gray-800 mb-1 uppercase tracking-wider break-all">Invoice #{{ $pesanan->nomor_invoice }}</h2>
                <p class="text-xs sm:text-sm text-gray-600"><strong>Tgl Order:</strong> {{ \Carbon\Carbon::parse($pesanan->tanggal_pesanan)->format('d M Y, H:i') }}</p>
                <p class="text-xs sm:text-sm text-gray-600"><strong>Batas Bayar:</strong> {{ \Carbon\Carbon::parse($pesanan->tanggal_pesanan)->addDays(1)->format('d M Y, H:i') }}</p>
            </div>
            
            <div class="w-full md:w-auto text-center bg-white p-3 rounded-lg border {{ $isCancelled ? 'border-gray-300 bg-gray-50' : ($statusLunas ? 'border-green-400 bg-green-50 shadow-sm' : 'border-gray-200') }}">
                <p class="text-xs font-bold uppercase tracking-widest mb-1 {{ $isCancelled ? 'text-gray-500' : ($statusLunas ? 'text-green-700' : 'text-gray-500') }}">NO. RESI (AWB)</p>
                
                @if($isCancelled)
                    <span class="bg-gray-100 text-gray-500 border border-gray-200 px-3 py-1.5 rounded text-sm font-bold inline-block mt-1 uppercase tracking-wider">
                        <i class="fas fa-ban mr-1"></i> Dibatalkan
                    </span>
                @elseif($statusLunas && $pesanan->resi)
                    <div class="bg-white rounded px-2 py-1 mb-2">
                        <svg id="barcodeResi" class="w-full max-w-[220px] mx-auto h-12"></svg>
                    </div>
                    
                    <a href="https://tokosancaka.com/tracking/search?resi={{ $pesanan->resi }}" target="_blank" class="inline-flex items-center justify-center bg-green-600 hover:bg-green-700 text-white text-xs font-bold py-1.5 px-4 rounded shadow-sm transition-colors w-full uppercase tracking-wider mt-1">
                        <i class="fas fa-truck-fast mr-2"></i> Lacak Pengiriman
                    </a>
                @else
                    <span class="bg-red-100 text-red-600 border border-red-200 px-3 py-1.5 rounded text-sm font-semibold italic inline-block mt-1">
                        <i class="fas fa-lock mr-1"></i> Diterbitkan setelah lunas
                    </span>
                @endif
            </div>
        </div>

        <!-- PENGIRIM & PENERIMA -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-8 mb-6 md:mb-8 text-sm">
            <!-- PENGIRIM -->
            <div class="border border-gray-200 rounded-lg p-4 sm:p-5 shadow-sm bg-white">
                <p class="font-bold text-xs text-gray-400 mb-2 uppercase tracking-wider border-b border-gray-100 pb-2">Pengirim (Invoiced To)</p>
                <p class="font-bold text-base sm:text-lg text-gray-800 uppercase">{{ $pesanan->sender_name }}</p>
                <p class="text-gray-600 mt-2 leading-relaxed text-xs sm:text-sm">{{ $senderAddress }}</p>
                <p class="mt-2 font-medium text-gray-800"><i class="fas fa-phone-alt text-gray-400 mr-2"></i> {{ $pesanan->sender_phone }}</p>
            </div>
            
            <!-- PENERIMA -->
            <div class="border border-gray-200 rounded-lg p-4 sm:p-5 shadow-sm bg-white">
                <p class="font-bold text-xs text-gray-400 mb-2 uppercase tracking-wider border-b border-gray-100 pb-2">Penerima (Ship To)</p>
                <p class="font-bold text-base sm:text-lg text-gray-800 uppercase">{{ $pesanan->receiver_name }}</p>
                <p class="text-gray-600 mt-2 leading-relaxed text-xs sm:text-sm">{{ $receiverAddress }}</p>
                <p class="mt-2 font-medium text-gray-800"><i class="fas fa-phone-alt text-gray-400 mr-2"></i> {{ $pesanan->receiver_phone }}</p>
            </div>
        </div>

        <!-- RINCIAN TAGIHAN TABLE -->
        <div class="overflow-x-auto mb-6 md:mb-8 border border-gray-300 rounded-lg">
            <table class="w-full text-sm border-collapse min-w-[500px]">
                <thead>
                    <tr class="bg-gray-800 text-white">
                        <th class="border-b border-gray-700 px-4 py-3 text-left font-bold w-3/4">Rincian Paket & Layanan Pengiriman</th>
                        <th class="border-b border-gray-700 px-4 py-3 text-right font-bold w-1/4">Biaya</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white">
                    <tr>
                        <td class="px-4 py-4">
                            <div class="flex items-center mb-3">
                                <div class="w-12 h-12 flex-shrink-0 flex items-center justify-center border border-gray-200 rounded p-1 bg-white mr-3">
                                    <img src="{{ $finalLogoUrl }}" alt="{{ $expeditionName }}" class="max-h-full max-w-full object-contain" onerror="this.style.display='none'">
                                </div>
                                <div>
                                    <p class="font-bold text-base text-gray-900 leading-tight">{{ strtoupper($expeditionName) }} - {{ strtoupper($expeditionService) }}</p>
                                    <p class="text-gray-500 text-[11px] uppercase tracking-wide">Layanan Ekspedisi Utama</p>
                                </div>
                            </div>
                            <ul class="text-gray-700 space-y-1 mt-3 bg-gray-50 p-3 rounded-lg border border-gray-100 text-xs sm:text-sm">
                                <li><span class="font-semibold text-gray-900">Isi Paket:</span> {{ $pesanan->item_description }}</li>
                                <li><span class="font-semibold text-gray-900">Berat Aktual:</span> {{ number_format($pesanan->weight, 0, ',', '.') }} Gram</li>
                                <li><span class="font-semibold text-gray-900">Dimensi (PxLxT):</span> {{ $pesanan->length ?? 0 }} x {{ $pesanan->width ?? 0 }} x {{ $pesanan->height ?? 0 }} cm</li>
                                <li><span class="font-semibold text-gray-900">Nilai Barang:</span> Rp {{ number_format($pesanan->item_price, 0, ',', '.') }}</li>
                            </ul>
                        </td>
                        <td class="px-4 py-4 text-right align-top font-bold text-gray-800 text-base">
                            Rp {{ number_format($pesanan->shipping_cost, 0, ',', '.') }}
                        </td>
                    </tr>
                    
                    @if($pesanan->insurance_cost > 0)
                    <tr class="bg-gray-50">
                        <td class="px-4 py-3 font-medium text-gray-700">Biaya Asuransi Pengiriman</td>
                        <td class="px-4 py-3 text-right font-medium">Rp {{ number_format($pesanan->insurance_cost, 0, ',', '.') }}</td>
                    </tr>
                    @endif
                    
                    @if($pesanan->cod_fee > 0)
                    <tr class="bg-gray-50">
                        <td class="px-4 py-3 font-medium text-gray-700">Biaya Penanganan (Fee Layanan)</td>
                        <td class="px-4 py-3 text-right font-medium">Rp {{ number_format($pesanan->cod_fee, 0, ',', '.') }}</td>
                    </tr>
                    @endif
                    
                    <!-- TOTALS SECTION -->
                    <tr>
                        <td class="px-4 py-3 text-right font-bold text-gray-600">Sub Total</td>
                        <td class="px-4 py-3 text-right font-bold text-gray-900">Rp {{ number_format($pesanan->price, 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td class="px-4 py-3 text-right font-bold text-gray-600">Credit</td>
                        <td class="px-4 py-3 text-right font-bold text-gray-900">Rp 0,00</td>
                    </tr>
                    <tr class="bg-gray-800 text-white">
                        <td class="px-4 py-4 text-right font-black text-lg uppercase tracking-wider">Grand Total</td>
                        <td class="px-4 py-4 text-right font-black text-lg text-green-400">Rp {{ number_format($pesanan->price, 0, ',', '.') }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- TRANSACTIONS & QR CODE SECTION -->
        <div class="flex flex-col-reverse md:flex-row gap-6 mb-8">
            
            <div class="w-full md:w-3/4">
                <h3 class="font-bold text-lg mb-3 text-gray-800 border-b border-gray-300 pb-2">Riwayat Transaksi</h3>
                <div class="overflow-x-auto border border-gray-200 rounded-lg">
                    <table class="w-full text-sm border-collapse text-center min-w-[400px]">
                        <thead>
                            <tr class="bg-gray-100 border-b border-gray-200">
                                <th class="px-3 py-3 font-bold text-gray-700">Tanggal</th>
                                <th class="px-3 py-3 font-bold text-gray-700">Metode</th>
                                <th class="px-3 py-3 font-bold text-gray-700 text-right">Nominal</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            @if($statusLunas)
                            <tr>
                                <td class="px-3 py-3">{{ \Carbon\Carbon::parse($pesanan->updated_at)->format('d/m/Y H:i') }}</td>
                                <td class="px-3 py-3 font-semibold uppercase text-xs">{{ str_replace('_', ' ', $pesanan->payment_method) }}</td>
                                <td class="px-3 py-3 text-right text-green-600 font-bold">Rp {{ number_format($pesanan->price, 0, ',', '.') }}</td>
                            </tr>
                            @else
                            <tr>
                                <td colspan="3" class="px-3 py-5 italic text-gray-400">Belum ada transaksi pembayaran masuk.</td>
                            </tr>
                            @endif
                            <tr class="bg-gray-50 border-t border-gray-200">
                                <td colspan="2" class="px-3 py-3 text-right font-bold text-gray-700 uppercase">Sisa Tagihan</td>
                                <td class="px-3 py-3 text-right font-black text-base {{ !$statusLunas ? 'text-red-600' : 'text-gray-900' }}">
                                    Rp {{ $statusLunas ? '0,00' : number_format($pesanan->price, 0, ',', '.') }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- QR CODE TRACKING & STATUS PAKET TERAKHIR -->
            <div class="w-full md:w-1/4 flex flex-col justify-center items-center border-2 border-dashed {{ $isCancelled ? 'border-gray-300 bg-gray-50' : ($statusLunas ? 'border-green-300 bg-green-50' : 'border-gray-300 bg-gray-50') }} rounded-xl p-3 sm:p-4">
                <p class="text-xs font-bold mb-3 text-center uppercase tracking-widest {{ $isCancelled ? 'text-gray-500' : ($statusLunas ? 'text-green-700' : 'text-gray-500') }}">Lacak Pengiriman</p>
                
                @if($isCancelled)
                    <div class="w-[100px] h-[100px] bg-gray-100 border border-gray-200 flex flex-col items-center justify-center rounded-lg shadow-sm">
                        <i class="fas fa-ban text-gray-400 text-3xl mb-2"></i>
                        <span class="text-[9px] text-gray-500 font-bold uppercase tracking-widest mt-1">Batal</span>
                    </div>
                @elseif($statusLunas && $pesanan->resi)
                    
                    @php
                        // TEMBAK DATABASE ATAU API UNTUK MENDAPATKAN STATUS
                        $statusText = 'Diproses / Manifest';
                        
                        try {
                            // 1. Cek dari Database Lokal (Ganti 'tracking_histories' dengan nama tabel riwayat Anda)
                            $cekDb = \Illuminate\Support\Facades\DB::table('tracking_histories')
                                        ->where('resi', $pesanan->resi)
                                        ->orderBy('created_at', 'desc')
                                        ->first();
                            
                            if ($cekDb && isset($cekDb->status)) {
                                $statusText = $cekDb->status;
                            }
                            
                            // 2. ATAU TEMBAK API LANGSUNG (Jika Anda punya helper/fungsi tracking tersendiri)
                            // Hapus komentar di bawah ini jika ingin langsung hit API
                            /*
                            $apiData = \App\Helpers\TrackingHelper::getTrackingData($pesanan->resi);
                            if(isset($apiData['status'])) {
                                $statusText = $apiData['status'];
                            }
                            */
                        } catch(\Exception $e) {
                            // Biarkan kosong agar tidak crash jika tabel tidak ada
                        }
                    @endphp

                    <!-- TAMPILAN STATUS PAKET -->
                    <div class="w-full bg-white border border-green-200 rounded p-1.5 mb-2 shadow-sm text-center">
                        <p class="text-[8px] text-gray-500 font-bold uppercase tracking-widest mb-0.5">Status Paket:</p>
                        <p class="text-[10px] font-bold text-green-700 leading-tight" style="display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                            {{ $statusText }}
                        </p>
                    </div>

                    <div id="qrcode" class="p-2 bg-white border border-green-200 rounded-lg shadow-sm"></div>
                    <p class="text-[10px] font-bold text-green-600 mt-2 text-center uppercase tracking-widest"><i class="fas fa-check-circle mr-1"></i> VALID (LUNAS)</p>
                @else
                    <div class="w-[100px] h-[100px] bg-white border border-gray-200 flex flex-col items-center justify-center rounded-lg shadow-sm">
                        <i class="fas fa-qrcode text-gray-300 text-3xl mb-2"></i>
                        <span class="text-[9px] text-gray-400 font-bold">Terkunci</span>
                    </div>
                @endif
            </div>

        </div>

        <div class="text-center text-[11px] text-gray-400 mt-8 border-t border-gray-200 pt-4">
            Dokumen ini dicetak otomatis dari sistem <strong>tokosancaka.com</strong> pada {{ date('d M Y, H:i') }} WIB dan sah tanpa tanda tangan fisik.
        </div>

        <!-- ======================================================== -->
        <!-- AREA PEMBAYARAN -->
        <!-- ======================================================== -->
        @if($isCancelled)
            <div class="no-print mt-8 border border-gray-300 bg-gray-50 p-6 rounded-xl shadow-sm text-center">
                <i class="fas fa-file-excel text-5xl text-gray-400 mb-4 drop-shadow-sm"></i>
                <h4 class="text-xl font-black text-gray-700 mb-2 uppercase tracking-widest">Pesanan Dibatalkan</h4>
                <p class="text-sm text-gray-600 mt-2">Tagihan ini sudah tidak berlaku karena pesanan telah dibatalkan atau gagal diproses.</p>
            </div>
        @else

            @if(session('error'))
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mt-6 text-center font-medium shadow-sm no-print">
                    <i class="fas fa-exclamation-triangle mr-1"></i> {{ session('error') }}
                </div>
            @endif

            @if(!$statusLunas && empty($pesanan->payment_url) && !in_array($pesanan->payment_method, ['COD', 'CODBARANG', 'Cash', 'Potong Saldo']))
            <div class="no-print mt-8 border-2 border-red-100 bg-red-50/50 p-5 sm:p-8 rounded-2xl shadow-sm">
                <h4 class="text-lg sm:text-xl font-black mb-5 text-center text-red-700 uppercase tracking-widest">Selesaikan Pembayaran Anda</h4>
                
                <form id="invoice-payment-form" action="{{ route('invoice.proses_bayar', $pesanan->nomor_invoice) }}" method="POST" class="max-w-xl mx-auto">
                    @csrf
                    <button type="button" id="paymentMethodButton" class="flex items-center justify-between w-full bg-white border-2 border-red-300 p-3 sm:p-4 rounded-xl cursor-pointer hover:border-red-600 hover:shadow-md focus:outline-none transition-all mb-5 group">
                        <div class="flex items-center overflow-hidden">
                            <div class="w-12 h-10 sm:w-14 sm:h-10 flex-shrink-0 flex items-center justify-center border border-gray-200 rounded-lg bg-gray-50 mr-3 sm:mr-4">
                                <img id="paymentMethodImg" src="https://tokosancaka.com/public/assets/saldo.png" alt="Logo" class="max-h-full max-w-full object-contain p-1">
                            </div>
                            <div class="text-left flex-1 min-w-0">
                                <span class="block text-[10px] sm:text-xs font-bold text-gray-400 uppercase tracking-wider mb-0.5">Pilih Bank / E-Wallet</span>
                                <span id="paymentMethodLabel" class="block text-sm sm:text-base font-bold text-gray-900 truncate group-hover:text-red-600 transition-colors">Klik untuk memilih metode...</span>
                            </div>
                        </div>
                        <i class="fas fa-chevron-right text-gray-300 group-hover:text-red-500 ml-2 flex-shrink-0"></i>
                    </button>
                    <input type="hidden" name="payment_method" id="payment_method" required>
                    
                    <button type="submit" id="submit-button" class="bg-red-600 hover:bg-red-700 text-white font-bold py-3.5 sm:py-4 px-10 rounded-xl text-base sm:text-lg transition duration-300 w-full shadow-lg disabled:opacity-50 disabled:cursor-not-allowed uppercase tracking-wider">
                        <i class="fas fa-shield-alt mr-2"></i> Bayar Tagihan
                    </button>
                </form>
            </div>

            @elseif(!$statusLunas && !empty($pesanan->payment_url))
            <div class="no-print mt-8 border border-blue-200 bg-blue-50 p-6 rounded-xl shadow-sm text-center">
                <h4 class="text-xl font-black mb-2 text-blue-800 uppercase tracking-widest">Selesaikan Pembayaran Anda</h4>
                
                @if($pesanan->payment_method == 'BCA_QRIS')
                    <p class="mb-4 text-sm text-gray-700">Scan QR Code di bawah ini menggunakan M-Banking / E-Wallet Anda:</p>
                    <div class="flex justify-center mb-4">
                        <div class="bg-white p-4 rounded-xl shadow-lg border-2 border-blue-200 inline-block">
                            <img src="https://api.qrserver.com/v1/create-qr-code/?size=250x250&data={{ urlencode($pesanan->payment_url) }}" alt="QRIS BCA" class="w-48 h-48">
                        </div>
                    </div>
                    <p class="font-bold text-blue-700 text-lg">BCA QRIS</p>
                @else
                    <p class="mb-5 text-gray-700">Anda telah memilih pembayaran menggunakan <strong class="uppercase text-blue-800">{{ str_replace('_', ' ', $pesanan->payment_method) }}</strong></p>
                    <a href="{{ $pesanan->payment_url }}" class="inline-block bg-blue-600 hover:bg-blue-700 text-white font-bold py-4 px-10 rounded-xl text-lg transition duration-300 shadow-lg uppercase tracking-wider">
                        Bayar Sekarang <i class="fas fa-external-link-alt ml-2"></i>
                    </a>
                @endif
            </div>
            
            @elseif(!$statusLunas)
            <div class="no-print mt-8 border border-yellow-200 bg-yellow-50 p-6 rounded-xl shadow-sm text-center">
                <i class="fas fa-clock text-5xl text-yellow-500 mb-4 drop-shadow-sm"></i>
                <h4 class="text-xl font-black text-yellow-800 mb-2 uppercase tracking-widest">Menunggu Verifikasi Admin</h4>
                <p class="text-base text-gray-700">Metode: <strong class="uppercase bg-yellow-200 px-2 py-1 rounded">{{ $pesanan->payment_method }}</strong></p>
                <p class="text-sm text-gray-600 mt-3">Pesanan akan diproses otomatis setelah admin Sancaka memverifikasi pembayaran Anda.</p>
            </div>
            @endif

        @endif

    </div>

    <!-- ======================================================== -->
    <!-- MODAL PEMILIHAN PEMBAYARAN -->
    <!-- ======================================================== -->
    <div id="paymentModal" class="no-print fixed inset-0 bg-gray-900/60 backdrop-blur-sm flex items-center justify-center z-50 hidden transition-all duration-300">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-5xl mx-4 sm:mx-auto transform transition-all flex flex-col max-h-[90vh] md:max-h-[85vh]">
            
            <!-- Modal Header -->
            <div class="flex justify-between items-center p-5 md:p-6 border-b border-gray-100 bg-gray-50/50 rounded-t-2xl">
                <h3 class="text-lg md:text-xl font-black text-gray-900 tracking-wide">Pilih Metode Pembayaran</h3>
                <button type="button" id="closeModalButton" class="text-gray-400 hover:text-red-600 bg-white border border-gray-200 hover:border-red-200 hover:bg-red-50 p-2 md:p-2.5 rounded-full transition-all shadow-sm">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>

            <!-- Modal Body -->
            <div class="p-2 overflow-y-auto custom-scrollbar flex-1 bg-gray-50/30">
                <ul id="paymentOptionsList" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 md:gap-4 p-4 md:p-6">
                    
                    <li class="col-span-full pb-2 text-xs font-black text-gray-500 uppercase tracking-widest border-b border-gray-200 mt-2 first:mt-0">
                        Direct Payment (Bebas Biaya Tripay)
                    </li>
                    
                    <li class="payment-option col-span-1 cursor-pointer flex items-center p-3 border border-gray-200 rounded-xl bg-white hover:border-red-500 hover:bg-red-50 hover:shadow-md transition-all group"
                        data-value="BCA_QRIS" data-label="BCA QRIS (Generate Barcode)" data-img="https://tokosancaka.com/assets/bca.png">
                        <div class="w-16 h-12 flex-shrink-0 flex items-center justify-center bg-white border border-gray-100 rounded-lg mr-3.5 p-1 group-hover:border-red-200 transition-colors">
                            <img src="https://tokosancaka.com/assets/bca.png" class="max-h-full max-w-full object-contain" alt="BCA">
                        </div>
                        <div class="flex flex-col flex-1 overflow-hidden">
                            <span class="text-sm font-bold text-gray-900 truncate group-hover:text-red-700 transition-colors">BCA QRIS</span>
                            <span class="text-[11px] text-gray-500 mt-0.5 truncate">Generate Barcode Pembayaran</span>
                        </div>
                    </li>

                    <li class="payment-option col-span-1 cursor-pointer flex items-center p-3 border border-gray-200 rounded-xl bg-white hover:border-red-500 hover:bg-red-50 hover:shadow-md transition-all group"
                        data-value="DOKU_JOKUL" data-label="DOKU Payment Gateway" data-img="https://tokosancaka.com/public/assets/doku.png">
                        <div class="w-16 h-12 flex-shrink-0 flex items-center justify-center bg-white border border-gray-100 rounded-lg mr-3.5 p-1 group-hover:border-red-200 transition-colors">
                            <img src="https://tokosancaka.com/public/assets/doku.png" class="max-h-full max-w-full object-contain" alt="DOKU">
                        </div>
                        <div class="flex flex-col flex-1 overflow-hidden">
                            <span class="text-sm font-bold text-gray-900 truncate group-hover:text-red-700 transition-colors">DOKU Gateway</span>
                            <span class="text-[11px] text-gray-500 mt-0.5 truncate">VA, E-Wallet, Kartu Kredit Lokal</span>
                        </div>
                    </li>

                    <li class="col-span-full pt-4 pb-2 text-xs font-black text-gray-500 uppercase tracking-widest border-b border-gray-200 mt-2">
                        DANA Enterprise
                    </li>

                    @php
                        $user = Auth::user();
                        $userDanaToken = $user ? $user->dana_access_token : null;
                        $userDanaBalance = $user ? ($user->dana_user_balance ?? 0) : 0;
                        $hasDanaBinding = !empty($userDanaToken);
                    @endphp

                    <li class="payment-option col-span-1 cursor-pointer flex items-center p-3 border border-gray-200 rounded-xl bg-white hover:border-red-500 hover:bg-red-50 hover:shadow-md transition-all group"
                        data-value="DANA" data-label="DANA (Web Checkout)" data-img="{{ asset('public/assets/dana.webp') }}">
                        <div class="w-16 h-12 flex-shrink-0 flex items-center justify-center bg-white border border-gray-100 rounded-lg mr-3.5 p-1 group-hover:border-red-200 transition-colors">
                            <img src="{{ asset('public/assets/dana.webp') }}" alt="DANA" class="max-h-full max-w-full object-contain" onerror="this.src='https://upload.wikimedia.org/wikipedia/commons/7/72/Logo_dana_blue.svg'">
                        </div>
                        <div class="flex flex-col flex-1 overflow-hidden">
                            <span class="text-sm font-bold text-gray-900 truncate group-hover:text-red-700 transition-colors">DANA Checkout</span>
                            <span class="text-[11px] text-gray-500 mt-0.5 truncate">Diarahkan ke aplikasi DANA</span>
                        </div>
                    </li>

                    @if($hasDanaBinding)
                        <li class="payment-option col-span-1 cursor-pointer flex items-center p-3 border border-blue-200 rounded-xl bg-blue-50 hover:border-blue-400 hover:bg-blue-100 hover:shadow-md transition-all group"
                            data-value="DANA_BINDING" data-label="DANA Auto-Debit" data-img="{{ asset('public/assets/dana.webp') }}">
                            <div class="w-16 h-12 flex-shrink-0 flex items-center justify-center bg-white border border-blue-100 rounded-lg mr-3.5 p-1 group-hover:border-blue-300 transition-colors">
                                <img src="{{ asset('public/assets/dana.webp') }}" alt="DANA" class="max-h-full max-w-full object-contain" onerror="this.src='https://upload.wikimedia.org/wikipedia/commons/7/72/Logo_dana_blue.svg'">
                            </div>
                            <div class="flex flex-col flex-1 overflow-hidden">
                                <span class="text-sm font-bold text-gray-900 truncate group-hover:text-blue-700 transition-colors">DANA Auto-Debit</span>
                                <span class="text-[11px] text-gray-600 font-medium mt-0.5 truncate">Saldo: <span class="text-blue-700">Rp{{ number_format($userDanaBalance, 0, ',', '.') }}</span></span>
                            </div>
                            <span class="ml-2 bg-blue-600 text-white text-[9px] font-bold px-1.5 py-0.5 rounded shadow-sm whitespace-nowrap">
                                Tersambung
                            </span>
                        </li>
                    @else
                        <li class="col-span-1 flex items-center p-3 border border-dashed border-gray-300 rounded-xl bg-gray-50 justify-between">
                            <div class="flex items-center overflow-hidden mr-2">
                                <div class="w-16 h-12 flex-shrink-0 flex items-center justify-center bg-gray-100 border border-gray-200 rounded-lg mr-3.5 p-1 opacity-60 grayscale">
                                    <img src="{{ asset('public/assets/dana.webp') }}" alt="DANA" class="max-h-full max-w-full object-contain" onerror="this.src='https://upload.wikimedia.org/wikipedia/commons/7/72/Logo_dana_blue.svg'">
                                </div>
                                <div class="flex flex-col flex-1 overflow-hidden">
                                    <span class="text-sm font-bold text-gray-500 truncate">DANA Auto-Debit</span>
                                    <span class="text-[11px] text-gray-400 mt-0.5 truncate">Bayar instan 1-klik</span>
                                </div>
                            </div>
                            <a href="{{ url('/dana/start-binding') }}" class="flex-shrink-0 px-2.5 py-1.5 bg-blue-600 text-white text-xs font-bold rounded-lg hover:bg-blue-700 shadow-sm transition-colors text-center">
                                Hubungkan
                            </a>
                        </li>
                    @endif

                    <li class="col-span-full pt-4 pb-2 text-xs font-black text-gray-500 uppercase tracking-widest border-b border-gray-200 mt-2">
                        Kartu Kredit Global & PayPal
                    </li>

                    <li class="payment-option col-span-1 cursor-pointer flex items-center p-3 border border-gray-200 rounded-xl bg-white hover:border-red-500 hover:bg-red-50 hover:shadow-md transition-all group"
                        data-value="PAYPAL" data-label="PayPal / Credit Card" data-img="https://tokosancaka.com/public/assets/paypal.png">
                        <div class="w-16 h-12 flex-shrink-0 flex items-center justify-center bg-white border border-gray-100 rounded-lg mr-3.5 p-1 group-hover:border-red-200 transition-colors">
                            <img src="https://tokosancaka.com/public/assets/paypal.png" alt="PayPal" class="max-h-full max-w-full object-contain" onerror="this.src='https://placehold.co/40x40/EFEFEF/AAAAAA?text=PP'">
                        </div>
                        <div class="flex flex-col flex-1 overflow-hidden">
                            <span class="text-sm font-bold text-gray-900 truncate group-hover:text-red-700 transition-colors">PayPal / Kartu Kredit</span>
                            <span class="text-[11px] text-gray-500 mt-0.5 truncate">Pembayaran Global (USD)</span>
                        </div>
                    </li>

                    @if(isset($tripayChannels) && count($tripayChannels) > 0)
                    <li class="col-span-full pt-4 pb-2 text-xs font-black text-gray-500 uppercase tracking-widest border-b border-gray-200 mt-2">
                        Transfer Bank & Minimarket (Otomatis)
                    </li>
                    @foreach($tripayChannels as $channel)
                        @if($channel['active'])
                        <li class="payment-option col-span-1 cursor-pointer flex items-center p-3 border border-gray-200 rounded-xl bg-white hover:border-red-500 hover:bg-red-50 hover:shadow-md transition-all group"
                            data-value="{{ $channel['code'] }}" data-label="{{ $channel['name'] }}" data-img="{{ $channel['icon_url'] }}">
                            <div class="w-16 h-12 flex-shrink-0 flex items-center justify-center bg-white border border-gray-100 rounded-lg mr-3.5 p-1.5 group-hover:border-red-200 transition-colors">
                                <img src="{{ $channel['icon_url'] }}" alt="{{ $channel['name'] }}" class="max-h-full max-w-full object-contain" onerror="this.src='https://placehold.co/40x40/EFEFEF/AAAAAA?text=IMG'">
                            </div>
                            <div class="flex flex-col flex-1 overflow-hidden">
                                <span class="text-sm font-bold text-gray-900 truncate group-hover:text-red-700 transition-colors">{{ $channel['name'] }}</span>
                                <span class="text-[11px] text-gray-500 mt-0.5 truncate">Tripay Payment</span>
                            </div>
                        </li>
                        @endif
                    @endforeach
                    @endif
                </ul>
            </div>
        </div>
    </div>

    <!-- JAVASCRIPT: BARCODE, QR CODE & MODAL -->
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const isPaid = {{ $statusLunas ? 'true' : 'false' }};
        const isCancelled = {{ $isCancelled ? 'true' : 'false' }};
        const resiSancaka = "{!! $pesanan->resi !!}";

        if (!isCancelled && isPaid && resiSancaka) {
            try {
                JsBarcode("#barcodeResi", resiSancaka, {
                    format: "CODE128", 
                    lineColor: "#16a34a", 
                    textMargin: 4, 
                    fontOptions: "bold", 
                    fontSize: 14,
                    height: 45, 
                    width: 2, 
                    displayValue: true 
                });
            } catch (e) { console.error("Gagal JSBarcode:", e); }

            try {
                new QRCode(document.getElementById("qrcode"), {
                    text: "https://tokosancaka.com/tracking/search?resi=" + resiSancaka,
                    width: 85, 
                    height: 85,
                    colorDark : "#16a34a",
                    colorLight : "#ffffff",
                    correctLevel : QRCode.CorrectLevel.M
                });
            } catch (e) { console.error("Gagal QRCode:", e); }
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
                        li.classList.remove('bg-red-50', 'border-red-500', 'ring-1', 'ring-red-500');
                        li.classList.add('bg-white', 'border-gray-200');
                    });
                    
                    this.classList.remove('bg-white', 'border-gray-200');
                    this.classList.add('bg-red-50', 'border-red-500', 'ring-1', 'ring-red-500');
                    
                    document.getElementById('paymentMethodLabel').textContent = this.dataset.label;
                    document.getElementById('paymentMethodImg').src = this.dataset.img;
                    
                    closePaymentModal();
                });
            });

            invoiceForm.addEventListener('submit', function(e) {
                if (paymentMethodInput.value === "") {
                    e.preventDefault(); alert('Silakan Klik tombol kotak putih di atas untuk memilih bank/metode pembayaran terlebih dahulu.'); return;
                }
                submitButton.disabled = true;
                submitButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Memproses...';
            });
        }
    });
    </script>
</body>
</html>