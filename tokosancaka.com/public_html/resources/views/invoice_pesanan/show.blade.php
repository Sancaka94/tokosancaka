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
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap');

        body {
            font-family: 'Inter', sans-serif;
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
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            letter-spacing: 1px;
        }
        .ribbon.paid { background: #16a34a; } /* Hijau */
        .ribbon.cancelled { background: #ef4444; } /* Merah */

        @media print {
            @page { size: A4 portrait; margin: 5mm; }
            body {
                background: white !important; padding: 0 !important; margin: 0 !important;
                -webkit-print-color-adjust: exact; print-color-adjust: exact;
            }
            .no-print { display: none !important; }
            .print-container {
                box-shadow: none !important; max-width: 100% !important; width: 100% !important;
                margin: 0 !important; padding: 0 !important; border: none !important;
            }
            * { font-size: 11px !important; line-height: 1.4 !important; }
            .print-header { flex-direction: row !important; }
            .print-header .w-full { width: 50% !important; }
            img { max-height: 40px !important; }
        }

        .custom-scrollbar::-webkit-scrollbar { width: 4px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: #fafafa; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #e5e5e5; border-radius: 4px; }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #d4d4d4; }
    </style>
</head>
<body class="bg-[#fafafa] py-8 text-black">

    @php
        // 1. Pengecekan Ekspedisi & Alamat
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

        // 2. CEK STATUS DASAR (Database pesanan)
        $statusPesanan = strtolower($pesanan->status ?? '');
        $isCancelled = in_array($statusPesanan, ['batal', 'cancel', 'gagal', 'dibatalkan', 'cancelled']);

        // 3. OVERRIDE DENGAN STATUS REALTIME TRACKING API / DATABASE HISTORI
        $statusText = $pesanan->status ?? 'Diproses / Menunggu Manifest';

        if (!empty($pesanan->resi) || !empty($pesanan->nomor_invoice)) {
            try {
                $resi = $pesanan->resi;
                $nomorInvoice = $pesanan->nomor_invoice ?? $resi;
                $expeditionRaw = strtolower($pesanan->expedition ?? $pesanan->jasa_ekspedisi_aktual ?? $pesanan->service_type ?? '');

                $apiStatusDitemukan = false;

                // --- PRIORITAS 1: TEMBAK API LANGSUNG (KIRIMINAJA DLL) ---
                if (!str_contains($expeditionRaw, 'deliveree') &&
                    !str_contains($expeditionRaw, 'lalamove') &&
                    !str_contains($expeditionRaw, 'ipaymu') &&
                    !str_contains($expeditionRaw, 'komship') &&
                    !isset($pesanan->is_autokirim)) {

                    if (class_exists(\App\Services\KiriminAjaService::class)) {
                        $kiriminAja = new \App\Services\KiriminAjaService();
                        $serviceType = $pesanan->service_type ?? 'regular';
                        if (str_contains($serviceType, '-')) {
                            $serviceType = explode('-', $serviceType)[0];
                        }

                        $trackingData = $kiriminAja->track($serviceType, $nomorInvoice);

                        if (!$trackingData || !isset($trackingData['status']) || $trackingData['status'] !== true) {
                            if($resi) {
                                $trackingData = $kiriminAja->track($serviceType, $resi);
                            }
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

                // --- PRIORITAS 2: JIKA API GAGAL, BARU CEK DB LOKAL ---
                if (!$apiStatusDitemukan && $resi) {
                    $cekDb = \Illuminate\Support\Facades\DB::table('tracking_histories')
                                ->where('resi', $resi)
                                ->orderBy('created_at', 'desc')
                                ->first();

                    if ($cekDb && isset($cekDb->status)) {
                        $statusText = $cekDb->status;
                    } elseif (class_exists(\App\Models\ScannedPackage::class)) {
                        $scanned = \App\Models\ScannedPackage::where('resi_number', $resi)->orderBy('created_at', 'desc')->first();
                        if ($scanned) {
                            $statusText = $scanned->status;
                        }
                    }
                }

                // --- PENENTUAN STATUS BATAL (REFUND MERAH) ---
                $rtStatusLower = strtolower($statusText);
                if (str_contains($rtStatusLower, 'cancel') || str_contains($rtStatusLower, 'batal') || str_contains($rtStatusLower, 'retur') || str_contains($rtStatusLower, 'gagal')) {
                    $isCancelled = true;
                }

            } catch(\Exception $e) {
                // Abaikan error agar halaman tidak mati
            }
        }
    @endphp

    <div class="max-w-4xl mx-auto mb-6 flex flex-col sm:flex-row justify-between items-center no-print px-4 md:px-0 gap-3">
        <a href="javascript:history.back()" class="w-full sm:w-auto text-center bg-white border border-gray-200 text-black px-4 py-2 rounded-md hover:bg-gray-50 text-sm font-medium transition">
            <i class="fas fa-arrow-left mr-2"></i> Kembali
        </a>
        <button onclick="window.print()" class="w-full sm:w-auto text-center bg-black text-white px-4 py-2 rounded-md hover:bg-gray-800 text-sm font-medium transition">
            <i class="fas fa-print mr-2"></i> Print Invoice
        </button>
    </div>

    <div class="max-w-4xl mx-auto bg-white p-8 md:p-12 print-container relative border border-gray-200">

        <div class="ribbon-wrapper no-print">
            <div class="ribbon {{ $isCancelled ? 'cancelled' : ($statusLunas ? 'paid' : '') }}">
                {{ $isCancelled ? 'REFUND' : ($statusLunas ? 'LUNAS' : 'UNPAID') }}
            </div>
        </div>

        <div class="print-header flex flex-col md:flex-row justify-between items-start mb-10 pb-8 border-b border-gray-100 gap-8">

            <div class="w-full md:w-1/2">
                <img src="https://tokosancaka.com/storage/uploads/sancaka.png" alt="Sancaka Express" class="h-10 mb-5 object-contain grayscale" onerror="this.src='https://placehold.co/200x50/FFFFFF/000000?text=SANCAKA+EXPRESS'">
                <div class="text-[13px] text-gray-500 leading-relaxed">
                    <p class="font-bold text-black uppercase tracking-wide">Sancaka Express</p>
                    <p>Jl. Dr. Wahidin No. 18A, Ketanggi</p>
                    <p>Kabupaten Ngawi, Jawa Timur 63211</p>
                    <p class="font-medium text-black mt-1">Telp: 08574580809</p>
                </div>
            </div>

            <div class="w-full md:w-[320px] pt-2 md:pt-8 relative z-10 no-print">
                @if($isCancelled)
                    <div class="border border-gray-200 bg-gray-50 p-4 rounded-md text-right">
                        <h4 class="text-sm font-bold text-black uppercase tracking-wider mb-1">Dibatalkan & Refund</h4>
                        <p class="text-xs text-gray-500">Status: {{ $statusText }}</p>
                    </div>
                @else
                    @if(session('error'))
                        <div class="border border-gray-300 bg-gray-50 text-black px-3 py-2 rounded-md mb-3 text-xs font-medium">
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
                                    <div class="w-6 h-6 flex items-center justify-center grayscale">
                                        <img id="paymentMethodImg" src="https://tokosancaka.com/public/assets/saldo.png" class="max-w-full max-h-full">
                                    </div>
                                    <span id="paymentMethodLabel" class="text-sm font-semibold text-black">Pilih Metode...</span>
                                </div>
                                <i class="fas fa-chevron-down text-gray-400 group-hover:text-black text-xs"></i>
                            </button>
                            <input type="hidden" name="payment_method" id="payment_method" required>
                            <button type="submit" id="submit-button" class="w-full bg-black text-white font-medium py-2.5 px-4 rounded-md text-sm hover:bg-gray-800 transition-colors disabled:opacity-50">
                                Bayar Tagihan
                            </button>
                        </form>

                    @elseif(!$statusLunas && !empty($pesanan->payment_url))
                        <div class="border border-gray-200 bg-gray-50 p-4 rounded-md text-center">
                            @if($pesanan->payment_method == 'BCA_QRIS')
                                <p class="text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-3">Scan QR Code BCA</p>
                                <div class="bg-white p-2 rounded border border-gray-200 inline-block mb-2">
                                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data={{ urlencode($pesanan->payment_url) }}" alt="QRIS" class="w-24 h-24">
                                </div>
                            @else
                                <p class="text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-2">Pembayaran via {{ str_replace('_', ' ', $pesanan->payment_method) }}</p>
                                <a href="{{ $pesanan->payment_url }}" class="block bg-black text-white font-medium py-2.5 px-4 rounded-md text-sm hover:bg-gray-800 transition-colors mt-2">
                                    Lanjut Bayar &rarr;
                                </a>
                            @endif
                        </div>

                    @elseif(!$statusLunas)
                        <div class="border border-gray-200 bg-gray-50 p-4 rounded-md text-right">
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

            <div class="w-full md:w-auto p-4 border border-gray-200 rounded-md text-center bg-gray-50/50 min-w-[200px]">
                <p class="text-[10px] font-bold uppercase tracking-widest text-gray-400 mb-2">NO. RESI (AWB)</p>

                @if($isCancelled)
                    <p class="text-sm font-bold text-black uppercase">REFUND / BATAL</p>
                @elseif($statusLunas && $pesanan->resi)
                    <div class="bg-white rounded border border-gray-200 p-2 mb-2">
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
                <p class="font-bold text-base text-black uppercase">{{ substr($pesanan->sender_name, 0, 3) }}*** *** ***</p>
                <p class="text-gray-500 mt-2 text-[13px] leading-relaxed">{{ $senderAddress }}</p>
                <p class="mt-2 text-[13px] font-medium text-black">{{ substr($pesanan->sender_phone, 0, 4) }}*** *** ***</p>
            </div>
            <div>
                <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-3 border-b border-gray-100 pb-2">Penerima (Ship To)</p>
                <p class="font-bold text-base text-black uppercase">{{ substr($pesanan->receiver_name, 0, 3) }}*** *** ***</p>
                <p class="text-gray-500 mt-2 text-[13px] leading-relaxed">{{ $receiverAddress }}</p>
                <p class="mt-2 text-[13px] font-medium text-black">{{ substr($pesanan->receiver_phone, 0, 4) }}*** *** ***</p>
            </div>
        </div>

        <div class="mb-10 overflow-hidden">
            <table class="w-full text-sm border-collapse">
                <thead>
                    <tr class="border-b border-black text-black">
                        <th class="py-3 text-left font-semibold uppercase text-[11px] tracking-wider w-3/4">Rincian Layanan</th>
                        <th class="py-3 text-right font-semibold uppercase text-[11px] tracking-wider w-1/4">Biaya</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-[13px]">
                    <tr>
                        <td class="py-5 pr-4 align-top">
                            <div class="flex items-center mb-3">
                                <div class="w-10 h-10 flex-shrink-0 flex items-center justify-center border border-gray-200 rounded bg-white mr-3 p-1 grayscale">
                                    <img src="{{ $finalLogoUrl }}" class="max-h-full max-w-full object-contain" onerror="this.style.display='none'">
                                </div>
                                <div>
                                    <p class="font-bold text-black uppercase">{{ $expeditionName }} - {{ $expeditionService }}</p>
                                    <p class="text-gray-400 text-[11px]">Layanan Ekspedisi</p>
                                </div>
                            </div>
                            <div class="text-gray-500 space-y-1 mt-3 text-xs border-l-2 border-gray-200 pl-3">
                                <p><span class="font-medium text-black">Isi Paket:</span> {{ $pesanan->item_description }}</p>
                                <p><span class="font-medium text-black">Berat:</span> {{ number_format($pesanan->weight, 0, ',', '.') }} Gram</p>
                                <p><span class="font-medium text-black">Dimensi:</span> {{ $pesanan->length ?? 0 }}x{{ $pesanan->width ?? 0 }}x{{ $pesanan->height ?? 0 }} cm</p>
                                <p><span class="font-medium text-black">Nilai Brg:</span> Rp {{ number_format($pesanan->item_price, 0, ',', '.') }}</p>
                            </div>
                        </td>
                        <td class="py-5 text-right align-top font-medium text-black">
                            Rp {{ number_format($pesanan->shipping_cost, 0, ',', '.') }}
                        </td>
                    </tr>

                    @if($pesanan->insurance_cost > 0)
                    <tr>
                        <td class="py-3 text-gray-500">Biaya Asuransi</td>
                        <td class="py-3 text-right font-medium">Rp {{ number_format($pesanan->insurance_cost, 0, ',', '.') }}</td>
                    </tr>
                    @endif

                    @if($pesanan->cod_fee > 0)
                    <tr>
                        <td class="py-3 text-gray-500">Biaya Penanganan (Fee)</td>
                        <td class="py-3 text-right font-medium">Rp {{ number_format($pesanan->cod_fee, 0, ',', '.') }}</td>
                    </tr>
                    @endif
                </tbody>
                <tfoot class="border-t border-black">
                    <tr>
                        <td class="py-3 text-right text-gray-500 text-[13px]">Sub Total</td>
                        <td class="py-3 text-right font-semibold text-black">Rp {{ number_format($pesanan->price, 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td class="py-2 text-right text-gray-500 text-[13px]">Credit</td>
                        <td class="py-2 text-right font-semibold text-black">Rp 0,00</td>
                    </tr>
                    <tr>
                        <td class="py-4 text-right font-black text-sm uppercase tracking-wide text-black">Grand Total</td>
                        <td class="py-4 text-right font-black text-sm text-black border-t-2 border-black border-dashed">
                            Rp {{ number_format($pesanan->price, 0, ',', '.') }}
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <div class="flex flex-col-reverse md:flex-row gap-8 mb-8 border-t border-gray-200 pt-8">

            <div class="w-full md:w-3/4">
                <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-4">Riwayat Transaksi</p>
                <table class="w-full text-[13px] border-collapse text-left">
                    <thead class="border-b border-gray-200 text-gray-400">
                        <tr>
                            <th class="py-2 font-medium">Tanggal</th>
                            <th class="py-2 font-medium">Metode</th>
                            <th class="py-2 font-medium text-right">Nominal</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @if($statusLunas)
                        <tr>
                            <td class="py-3">{{ \Carbon\Carbon::parse($pesanan->updated_at)->format('d/m/Y H:i') }}</td>
                            <td class="py-3 uppercase text-xs font-semibold text-black">{{ str_replace('_', ' ', $pesanan->payment_method) }}</td>
                            <td class="py-3 text-right font-semibold text-black">Rp {{ number_format($pesanan->price, 0, ',', '.') }}</td>
                        </tr>
                        @else
                        <tr>
                            <td colspan="3" class="py-4 italic text-gray-400 text-center text-xs">Belum ada transaksi masuk.</td>
                        </tr>
                        @endif
                    </tbody>
                    <tfoot class="border-t border-gray-200">
                        <tr>
                            <td colspan="2" class="py-3 text-right font-medium text-gray-500 text-xs uppercase">Sisa Tagihan</td>
                            <td class="py-3 text-right font-black text-sm text-black">
                                Rp {{ $statusLunas ? '0,00' : number_format($pesanan->price, 0, ',', '.') }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div class="w-full md:w-1/4">
                <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-4 text-center">Status Lacak</p>

                @if($isCancelled)
                    <div class="border border-gray-200 bg-gray-50 rounded-md p-3 text-center">
                        <p class="text-[10px] text-gray-400 uppercase tracking-wider mb-1">Status Paket</p>
                        <p class="text-[11px] font-bold text-black mb-3">{{ $statusText }}</p>
                        <button onclick="syncTracking(this)" class="no-print w-full bg-white border border-gray-300 hover:border-black text-black text-[10px] font-bold py-1.5 rounded transition-colors uppercase tracking-wider">
                            Sync API
                        </button>
                    </div>
                @elseif($statusLunas && $pesanan->resi)
                    <div class="border border-gray-200 bg-gray-50 rounded-md p-3 text-center mb-3">
                        <p class="text-[10px] text-gray-400 uppercase tracking-wider mb-1">Status Paket</p>
                        <p class="text-[11px] font-bold text-black mb-3 truncate" title="{{ $statusText }}">{{ $statusText }}</p>
                        <button onclick="syncTracking(this)" class="no-print w-full bg-white border border-gray-300 hover:border-black text-black text-[10px] font-bold py-1.5 rounded transition-colors uppercase tracking-wider">
                            Sync API
                        </button>
                    </div>
                    <div id="qrcode" class="p-2 bg-white border border-gray-200 rounded flex justify-center grayscale"></div>
                @else
                    <div class="h-[100px] bg-gray-50 border border-dashed border-gray-200 flex flex-col items-center justify-center rounded-md">
                        <i class="fas fa-lock text-gray-300 text-xl mb-1"></i>
                        <span class="text-[10px] text-gray-400 font-medium">Terkunci</span>
                    </div>
                @endif
            </div>

        </div>

        <div class="text-center text-[10px] text-gray-400 mt-12">
            Dicetak otomatis dari sistem <strong>tokosancaka.com</strong> pada {{ date('d M Y, H:i') }} WIB.<br>Dokumen sah tanpa tanda tangan fisik.
        </div>

    </div>

    <div id="paymentModal" class="no-print fixed inset-0 bg-black/40 backdrop-blur-sm flex items-center justify-center z-50 hidden transition-all duration-300">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-3xl mx-4 sm:mx-auto flex flex-col max-h-[85vh]">

            <div class="flex justify-between items-center p-5 border-b border-gray-200">
                <h3 class="text-base font-bold text-black tracking-wide">Pilih Metode Pembayaran</h3>
                <button type="button" id="closeModalButton" class="text-gray-400 hover:text-black transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>

            <div class="p-4 overflow-y-auto custom-scrollbar flex-1 bg-gray-50/50">
                <ul id="paymentOptionsList" class="grid grid-cols-1 sm:grid-cols-2 gap-3">

                    <li class="col-span-full pb-1 text-[10px] font-bold text-gray-400 uppercase tracking-widest border-b border-gray-200">
                        Direct Payment
                    </li>

                    <li class="payment-option cursor-pointer flex items-center p-3 border border-gray-200 rounded-lg bg-white hover:border-black transition-all group grayscale hover:grayscale-0"
                        data-value="BCA_QRIS" data-label="BCA QRIS" data-img="https://tokosancaka.com/assets/bca.png">
                        <img src="https://tokosancaka.com/assets/bca.png" class="w-10 h-auto mr-3 object-contain" alt="BCA">
                        <div class="flex flex-col">
                            <span class="text-[13px] font-semibold text-black">BCA QRIS</span>
                            <span class="text-[10px] text-gray-500">Generate Barcode</span>
                        </div>
                    </li>

                    <li class="payment-option cursor-pointer flex items-center p-3 border border-gray-200 rounded-lg bg-white hover:border-black transition-all group grayscale hover:grayscale-0"
                        data-value="DOKU_JOKUL" data-label="DOKU Gateway" data-img="https://tokosancaka.com/public/assets/doku.png">
                        <img src="https://tokosancaka.com/public/assets/doku.png" class="w-10 h-auto mr-3 object-contain" alt="DOKU">
                        <div class="flex flex-col">
                            <span class="text-[13px] font-semibold text-black">DOKU Gateway</span>
                            <span class="text-[10px] text-gray-500">VA, E-Wallet, CC Lokal</span>
                        </div>
                    </li>

                    <li class="col-span-full pt-3 pb-1 text-[10px] font-bold text-gray-400 uppercase tracking-widest border-b border-gray-200">
                        DANA Enterprise
                    </li>

                    @php
                        $user = Auth::user();
                        $userDanaToken = $user ? $user->dana_access_token : null;
                        $userDanaBalance = $user ? ($user->dana_user_balance ?? 0) : 0;
                        $hasDanaBinding = !empty($userDanaToken);
                    @endphp

                    <li class="payment-option cursor-pointer flex items-center p-3 border border-gray-200 rounded-lg bg-white hover:border-black transition-all group grayscale hover:grayscale-0"
                        data-value="DANA" data-label="DANA Checkout" data-img="{{ asset('public/assets/dana.webp') }}">
                        <img src="{{ asset('public/assets/dana.webp') }}" class="w-10 h-auto mr-3 object-contain" onerror="this.src='https://upload.wikimedia.org/wikipedia/commons/7/72/Logo_dana_blue.svg'">
                        <div class="flex flex-col">
                            <span class="text-[13px] font-semibold text-black">DANA Web</span>
                            <span class="text-[10px] text-gray-500">Arahkan ke App</span>
                        </div>
                    </li>

                    @if($hasDanaBinding)
                        <li class="payment-option cursor-pointer flex items-center p-3 border border-gray-400 rounded-lg bg-gray-100 hover:border-black transition-all group grayscale hover:grayscale-0"
                            data-value="DANA_BINDING" data-label="DANA Auto-Debit" data-img="{{ asset('public/assets/dana.webp') }}">
                            <img src="{{ asset('public/assets/dana.webp') }}" class="w-10 h-auto mr-3 object-contain" onerror="this.src='https://upload.wikimedia.org/wikipedia/commons/7/72/Logo_dana_blue.svg'">
                            <div class="flex flex-col flex-1">
                                <span class="text-[13px] font-semibold text-black">DANA Auto-Debit</span>
                                <span class="text-[10px] text-gray-600">Saldo: Rp{{ number_format($userDanaBalance, 0, ',', '.') }}</span>
                            </div>
                            <span class="bg-black text-white text-[9px] px-1.5 py-0.5 rounded">Tersambung</span>
                        </li>
                    @else
                        <li class="col-span-1 flex items-center p-3 border border-dashed border-gray-300 rounded-lg bg-transparent justify-between grayscale">
                            <div class="flex items-center">
                                <img src="{{ asset('public/assets/dana.webp') }}" class="w-10 h-auto mr-3 object-contain opacity-50" onerror="this.src='https://upload.wikimedia.org/wikipedia/commons/7/72/Logo_dana_blue.svg'">
                                <div class="flex flex-col">
                                    <span class="text-[13px] font-semibold text-gray-500">DANA Auto-Debit</span>
                                </div>
                            </div>
                            <a href="{{ url('/dana/start-binding') }}" class="bg-white border border-gray-300 text-black hover:border-black text-[10px] px-2 py-1 rounded transition-colors">
                                Hubungkan
                            </a>
                        </li>
                    @endif

                    <li class="col-span-full pt-3 pb-1 text-[10px] font-bold text-gray-400 uppercase tracking-widest border-b border-gray-200">
                        Global & Otomatis
                    </li>

                    <li class="payment-option cursor-pointer flex items-center p-3 border border-gray-200 rounded-lg bg-white hover:border-black transition-all group grayscale hover:grayscale-0"
                        data-value="PAYPAL" data-label="PayPal" data-img="https://tokosancaka.com/public/assets/paypal.png">
                        <img src="https://tokosancaka.com/public/assets/paypal.png" class="w-10 h-auto mr-3 object-contain" onerror="this.src='https://placehold.co/40x40/EFEFEF/AAAAAA?text=PP'">
                        <div class="flex flex-col">
                            <span class="text-[13px] font-semibold text-black">PayPal / CC</span>
                            <span class="text-[10px] text-gray-500">Pembayaran USD</span>
                        </div>
                    </li>

                    @if(isset($tripayChannels) && count($tripayChannels) > 0)
                        @foreach($tripayChannels as $channel)
                            @if($channel['active'])
                            <li class="payment-option cursor-pointer flex items-center p-3 border border-gray-200 rounded-lg bg-white hover:border-black transition-all group grayscale hover:grayscale-0"
                                data-value="{{ $channel['code'] }}" data-label="{{ $channel['name'] }}" data-img="{{ $channel['icon_url'] }}">
                                <img src="{{ $channel['icon_url'] }}" class="w-10 h-auto mr-3 object-contain" onerror="this.src='https://placehold.co/40x40/EFEFEF/AAAAAA?text=IMG'">
                                <div class="flex flex-col">
                                    <span class="text-[13px] font-semibold text-black">{{ $channel['name'] }}</span>
                                </div>
                            </li>
                            @endif
                        @endforeach
                    @endif

                </ul>
            </div>
        </div>
    </div>

    <script>
    function syncTracking(btn) {
        btn.innerHTML = 'Syncing...';
        btn.disabled = true;
        setTimeout(() => { window.location.reload(); }, 500);
    }

    document.addEventListener('DOMContentLoaded', function () {
        const isPaid = {{ $statusLunas ? 'true' : 'false' }};
        const isCancelled = {{ $isCancelled ? 'true' : 'false' }};
        const resiSancaka = "{!! $pesanan->resi !!}";

        if (!isCancelled && isPaid && resiSancaka) {
            try {
                // Barcode Hitam Putih
                JsBarcode("#barcodeResi", resiSancaka, {
                    format: "CODE128", lineColor: "#000000", textMargin: 4,
                    fontOptions: "bold", fontSize: 13, height: 40, width: 2, displayValue: true
                });
            } catch (e) {}

            try {
                // QRCode Hitam Putih
                new QRCode(document.getElementById("qrcode"), {
                    text: "https://tokosancaka.com/tracking/search?resi=" + resiSancaka,
                    width: 75, height: 75, colorDark : "#000000", colorLight : "#ffffff",
                    correctLevel : QRCode.CorrectLevel.M
                });
            } catch (e) {}
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
                        li.classList.remove('border-black', 'bg-gray-50');
                        li.classList.add('border-gray-200', 'bg-white');
                    });

                    this.classList.remove('border-gray-200', 'bg-white');
                    this.classList.add('border-black', 'bg-gray-50');

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
    </script>
</body>
</html>
