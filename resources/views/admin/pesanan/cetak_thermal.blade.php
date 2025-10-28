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
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>

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
        }
        .barcode { width: 100%; height: 50px; }
        .label { font-weight: 600; font-size: 8px; color: #374151; }
        .value { font-weight: 500; font-size: 9px; }
        @media print {
            body { background: none; }
            .no-print { display: none; }
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
<body>

    <div class="no-print p-3 text-center bg-white border-b border-gray-200 shadow-sm sticky top-0 z-10">
        @php
            $backUrl = url()->previous();

            if (Auth::check()) {
                if (Auth::user()->hasRole('Admin')) {
                    $backUrl = route('admin.pesanan.index');
                } elseif (Auth::user()->hasRole('Pelanggan')) {
                    $backUrl = route('customer.pesanan.index');
                }
            }
            
            $waMessageReceiver = urlencode("Halo " . $pesanan->receiver_name . ", pesanan Anda dengan resi " . $pesanan->resi . " dari " . $pesanan->sender_name . " telah berhasil dibuat. Anda dapat melihat detail resi di sini: " . route('admin.pesanan.cetak_thermal', ['resi' => $pesanan->resi]));
            $waPhoneReceiver = preg_replace('/^0/', '62', $pesanan->receiver_phone);

            $waMessageSender = urlencode("Halo " . $pesanan->sender_name . ", pesanan Anda dengan resi " . $pesanan->resi . " untuk " . $pesanan->receiver_name . " telah berhasil dibuat. Anda dapat melihat detail resi di sini: " . route('admin.pesanan.cetak_thermal', ['resi' => $pesanan->resi]));
            $waPhoneSender = preg_replace('/^0/', '62', $pesanan->sender_phone);
        @endphp

        <button onclick="window.print()" class="bg-indigo-600 text-white px-5 py-2 rounded-md shadow hover:bg-indigo-700 transition">🖨 Cetak Resi</button>
        <a href="https://wa.me/{{ $waPhoneReceiver }}?text={{ $waMessageReceiver }}" target="_blank" class="ml-2 bg-green-600 text-white px-5 py-2 rounded-md shadow hover:bg-green-700 transition">📱 Kirim WA (Penerima)</a>
        <a href="https://wa.me/{{ $waPhoneSender }}?text={{ $waMessageSender }}" target="_blank" class="ml-2 bg-green-600 text-white px-5 py-2 rounded-md shadow hover:bg-green-700 transition">📱 Kirim WA (Pengirim)</a>
        <a href="{{ $backUrl }}" class="ml-2 bg-gray-200 text-gray-800 px-5 py-2 rounded-md shadow hover:bg-gray-300 transition">← Kembali</a>
    </div>

    <div class="page">

        @php
            // Menggunakan helper untuk mem-parsing metode pengiriman
            // Pastikan helper ini ada dan berfungsi dengan benar
            $ship = \App\Helpers\ShippingHelper::parseShippingMethod($pesanan->expedition);

            $expeditionName = $ship['expedition'] ?? 'SANCACA'; // Default jika tidak ditemukan
            $expeditionService = $ship['service'] ?? 'Regular'; // Default jika tidak ditemukan
            
            // Format nama ekspedisi menjadi lowercase dan tanpa spasi untuk path logo
            $logoPath = strtolower(str_replace(' ', '', $expeditionName));
        @endphp

        <div class="flex justify-between items-center border-b border-dashed border-gray-500 pb-2">
            <img src="https://tokosancaka.biz.id/storage/uploads/sancaka.png" alt="Sancaka Express" class="h-10" onerror="this.style.display='none'">
            {{-- Menggunakan logo dari storage berdasarkan expeditionName dari helper --}}
            <img src="{{ asset('public/storage/logo-ekspedisi/' . $logoPath . '.png') }}" alt="{{ $expeditionName }} Logo" class="h-8">
        </div>

        <div class="text-center mt-2">
            <p class="font-bold text-sm tracking-wide"><strong>RESI SANCAKA</strong></p>
            <svg id="barcodeSancaka" class="barcode"></svg>
        </div>

        <div class="grid grid-cols-2 gap-3 mt-2 border-b border-dashed border-gray-400 pb-2">
            <div class="pr-2">
                <p class="label"><strong>PENGIRIM:</strong></p>
                <p class="value">{{ $pesanan->sender_name }}</p>
                <p class="text-xs">{{ $pesanan->sender_phone }}</p>
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
                
                {{-- Rincian Paket --}}
                <div class="mt-2 pt-2">
                    <p class="label"><strong>Rincian Paket:</strong></p>
                    <p class="value">- Berat: {{ $pesanan->weight }} gr</p>
                    <p class="value">- Dimensi: {{ $pesanan->length ?? 0 }}x{{ $pesanan->width ?? 0 }}x{{ $pesanan->height ?? 0 }} cm</p>
                    <p class="value">- Layanan: {{ strtoupper($pesanan->service_type) }}</p><br><br>

                    <p class="label"><strong>Nomor Resi:</strong></p>
                    <p class="label"><strong>{{ $pesanan->resi }}</strong></p>
                </div>
            </div>

            <div class="pl-2">
                <p class="label"><strong>PENERIMA:</strong></p>
                <p class="value">{{ $pesanan->receiver_name }}</p>
                <p class="text-xs">{{ $pesanan->receiver_phone }}</p>
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

                {{-- Barcode 2D (QR Code) --}}
                <div class="flex justify-center mt-4">
                    <div id="qrcode"></div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-3 gap-2 text-center mt-2 border-b border-dashed border-gray-400 pb-2">
            <div><p class="label"><strong>ORDER ID</strong></p><p class="value">{{ $pesanan->nomor_invoice }}</p></div>
            <div><p class="label"><strong>BERAT</strong></p><p class="value">{{ $pesanan->weight }} gr</p></div>
            <div><p class="label"><strong>VOLUME</strong></p><p class="value">{{ $pesanan->length ?? 0 }}x{{ $pesanan->width ?? 0 }}x{{ $pesanan->height ?? 0 }} cm</p></div>
            <div><p class="label"><strong>LAYANAN</strong></p><p class="value">{{ strtoupper($expeditionService) }}</p></div> {{-- Menggunakan $expeditionService dari helper --}}
            <div><p class="label"><strong>EKSPEDISI</strong></p><p class="value">{{ strtoupper($expeditionName) }}</p></div> {{-- Menggunakan $expeditionName dari helper --}}
        </div>

        @if($pesanan->payment_method == 'COD' || $pesanan->payment_method == 'CODBARANG')
        <div class="text-center mt-2 border-b border-dashed border-gray-400 pb-2">
            <p class="label">HARGA COD</p>
            <p class="value text-red-600">Rp {{ number_format($pesanan->price, 0, ',', '.') }}</p>
        </div>
        @endif

        @if($pesanan->resi_aktual)
        <div class="text-center mt-3 pt-2 border-t border-dashed border-gray-400">
            <p class="label">RESI AKTUAL ({{ $pesanan->jasa_ekspedisi_aktual }})</p>
            <svg id="barcodeAktual" class="barcode"></svg>
        </div>
        @endif

        <div class="mt-auto pt-3 text-center text-xs">
            <p>Terima kasih telah menggunakan <span class="font-semibold">Sancaka Express</span>.</p>
            <p class="font-bold mt-1">{{ \Carbon\Carbon::parse($pesanan->created_at)->format('d M Y H:i') }}</p>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            try {
                const resiSancaka = {!! json_encode($pesanan->resi ?? '') !!};
                if (resiSancaka) {
                    JsBarcode("#barcodeSancaka", resiSancaka, {
                        format: "CODE128", textMargin: 0, fontOptions: "bold", height: 40, width: 2
                    });
                }
                @if($pesanan->resi_aktual)
                    const resiAktual = {!! json_encode($pesanan->resi_aktual ?? '') !!};
                    if (resiAktual) {
                        JsBarcode("#barcodeAktual", resiAktual, {
                            format: "CODE128", textMargin: 0, fontOptions: "bold", height: 40, width: 2
                        });
                    }
                @endif
            } catch (e) {
                console.error("Gagal membuat barcode:", e);
            }
        });
    </script>

   {{-- Barcode 2D (QR Code) --}}
<div class="flex justify-center mt-2">
    <div id="qrcode"></div>
</div>

<script src="https://cdn.jsdelivr.net/npm/qrcodejs/qrcode.min.js"></script>
<script>
    new QRCode(document.getElementById("qrcode"), {
        text: "https://tokosancaka.com/tracking/search?resi={{ $pesanan->resi }}",
        width: 100,
        height: 100
    });
</script>


</body>
</html>