<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Favicon -->
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

    <!-- Toolbar -->
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
     
      // Pesan untuk penerima
    $waMessageReceiver = urlencode("Halo " . $pesanan->nama_pembeli . ", pesanan Anda dengan resi " . $pesanan->resi . " dari " . $pesanan->sender_name . " telah berhasil dibuat. Anda dapat melihat detail resi di sini: " . route('admin.pesanan.cetak_thermal', ['resi' => $pesanan->resi]));
    $waPhoneReceiver = preg_replace('/^0/', '62', $pesanan->telepon_pembeli);

    // Pesan untuk pengirim
    $waMessageSender = urlencode("Halo " . $pesanan->sender_name . ", pesanan Anda dengan resi " . $pesanan->resi . " untuk " . $pesanan->nama_pembeli . " telah berhasil dibuat. Anda dapat melihat detail resi di sini: " . route('admin.pesanan.cetak_thermal', ['resi' => $pesanan->resi]));
    $waPhoneSender = preg_replace('/^0/', '62', $pesanan->sender_phone);
        @endphp

        <button onclick="window.print()" class="bg-indigo-600 text-white px-5 py-2 rounded-md shadow hover:bg-indigo-700 transition">🖨 Cetak Resi</button>
        <a href="https://wa.me/{{ $waPhoneReceiver }}?text={{ $waMessageReceiver }}" target="_blank" class="ml-2 bg-green-600 text-white px-5 py-2 rounded-md shadow hover:bg-green-700 transition">📱 Kirim WA (Penerima)</a>
        <a href="https://wa.me/{{ $waPhoneSender }}?text={{ $waMessageSender }}" target="_blank" class="ml-2 bg-green-600 text-white px-5 py-2 rounded-md shadow hover:bg-green-700 transition">📱 Kirim WA (Pengirim)</a>
        <a href="{{ $backUrl }}" class="ml-2 bg-gray-200 text-gray-800 px-5 py-2 rounded-md shadow hover:bg-gray-300 transition">⬅ Kembali</a>
    </div>

    <!-- Halaman Resi -->
    <div class="page">

        @php
            // ---------- BLok partnerLogos & logoPath (DIPULIHKAN) ----------
            $partnerLogos = [
                'JNE' => 'https://upload.wikimedia.org/wikipedia/commons/9/92/New_Logo_JNE.png',
                'J&T EXPRESS' => 'https://upload.wikimedia.org/wikipedia/commons/0/01/J%26T_Express_logo.svg',
                'J&T CARGO' => 'https://i.pinimg.com/736x/22/cf/92/22cf92368c1f901d17e38e99061f4849.jpg',
                'WAHANA EXPRESS' => 'https://account.wahana.com/assets/images/Logo.png',
                'POS INDONESIA' => 'https://kiriminaja.com/assets/home-v4/pos.png',
                'SAP EXPRESS' => 'https://kiriminaja.com/assets/home-v4/sap.png',
                'INDAH CARGO' => 'https://blogger.googleusercontent.com/img/b/R29vZ2xl/AVvXsEicOAaLoH2eElQ93_gbkzhvk4dRhWVlk5wQsGgilihIB58321aHchlJLdjyz1ToS25P_nWrHJ_E4QBiW_OVlI7tQt7cZ5I0HZqk6StS7jZltLVvDXp2d5ZDLB9yklhV4x6z2iXyURURDv_unhf-U6vyiD_8to9OC4PBwMwyU_5wAqOiCl6tKiaTA-ri1Q/s851/Logo%20Indah%20Logistik%20Cargo@0.5x.png',
                'LION PARCEL' => 'https://kiriminaja.com/assets/home-v4/lion.png',
                'ID EXPRESS' => 'https://assets.bukalapak.com/beagle/images/courier_logo/id-express.png',
                'SPX EXPRESS' => 'https://images.seeklogo.com/logo-png/49/1/spx-express-indonesia-logo-png_seeklogo-499970.png',
                'NCS' => 'https://blogger.googleusercontent.com/img/b/R29vZ2xl/AVvXsEjxj3iyyZEjK2L4A4yCIr_E-4W3hF2lk_yb-t0Oj2oFPErCPCMHie5LHqps02xMb6sNa-Gqz5NSX_P_hzWlYpUpJUlCD4iN6_QxiSG9fzY4bsZ9XvLFDn7HCiORtNvIlPfuQbSSdW96p7x7uN8ek3FWyHW9c2bznrFBQkoLd5A9sVAFVKWLhUhT3Dxh/s320/GKL41_NCS%20Kurir%20-%20Koleksilogo.com.jpg',
                'SENTRAL CARGO' => 'https://kiriminaja.com/assets/home-v4/central-cargo.png',
                'SICEPAT' => 'https://kiriminaja.com/assets/home-v4/sicepat.png',
                'NINJA XPRESS' => 'https://kiriminaja.com/assets/home-v4/ninja.png',
                'PAXEL' => 'https://paxel.co/images/logo-paxel.png',
                'ANTERAJA' => 'https://kiriminaja.com/assets/home-v4/anter-aja.png',
                'TIKI' => 'https://kiriminaja.com/assets/home-v4/tiki.png',
                'REX' => 'https://assets.bukalapak.com/beagle/images/courier_logo/rex.png',
                'FIRST LOGISTICS' => 'https://assets.bukalapak.com/beagle/images/courier_logo/first-logistics.png',
                'LEX (LAZADA EXPRESS)' => 'https://sellercenter.lazada.co.id/assets/images/knowledge-management/lex-logo.png',
                'OEXPRESS' => 'https://assets.autokirim.com/courier/oexpress.png',
                'BORZO' => 'https://kiriminaja.com/assets/home-v4/borzo.png',
                'GOSEND' => 'https://kiriminaja.com/assets/home-v4/gosend.png',
                'GRABEXPRESS' => 'https://kiriminaja.com/assets/home-v4/grab.svg',
            ];

            // Ubah semua key menjadi uppercase
            $partnerLogos = array_change_key_case($partnerLogos, CASE_UPPER);

            $partnerKey = strtoupper(explode('-', $pesanan->expedition)[1] ?? '');
            $partnerLogoUrl = $partnerLogos[$partnerKey] ?? 'https://placehold.co/150x50/e2e8f0/334155?text=' . urlencode($partnerKey);

            $expeditionParts = explode('-', $pesanan->expedition);
            $expeditionName = $expeditionParts[1] ?? 'POSINDONESIA';
            $expeditionService = $expeditionParts[2] ?? 'Regular';
            $logoPath = strtolower(str_replace(' ', '', $expeditionName));
        @endphp

        <!-- Header -->
        <div class="flex justify-between items-center border-b border-dashed border-gray-500 pb-2">
            <img src="https://tokosancaka.biz.id/storage/uploads/sancaka.png" alt="Sancaka Express" class="h-10" onerror="this.style.display='none'">
            <img src="{{ asset('storage/logo-ekspedisi/' . $logoPath . '.png') }}" alt="{{ $expeditionName }} Logo" class="h-8">
        </div>

        <!-- Barcode Resi -->
        <div class="text-center mt-2">
            <p class="font-bold text-sm tracking-wide"><strong>RESI SANCAKA</strong></p>
            <svg id="barcodeSancaka" class="barcode"></svg>
        </div>

        <!-- Pengirim & Penerima serta Detail Paket & Barcode 2D -->
        <div class="grid grid-cols-2 gap-3 mt-2 border-b border-dashed border-gray-400 pb-2">
            <!-- Kolom Kiri: Pengirim & Rincian Paket -->
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

            <!-- Kolom Kanan: Penerima & Barcode 2D -->
            <div class="pl-2">
                <p class="label"><strong>PENERIMA:</strong></p>
                <p class="value">{{ $pesanan->nama_pembeli }}</p>
                <p class="text-xs">{{ $pesanan->telepon_pembeli }}</p>
                <p class="text-xs leading-snug mt-1">
                    {{ implode(', ', array_filter([
                        $pesanan->alamat_pengiriman,
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

        <!-- Detail Order -->
        <div class="grid grid-cols-3 gap-2 text-center mt-2 border-b border-dashed border-gray-400 pb-2">
            <div><p class="label"><strong>ORDER ID</strong></p><p class="value">{{ $pesanan->nomor_invoice }}</p></div>
            <div><p class="label"><strong>BERAT</strong></p><p class="value">{{ $pesanan->weight }} gr</p></div>
            <div><p class="label"><strong>VOLUME</strong></p><p class="value">{{ $pesanan->length ?? 0 }}x{{ $pesanan->width ?? 0 }}x{{ $pesanan->height ?? 0 }} cm</p></div>
            <div><p class="label"><strong>LAYANAN</strong></p><p class="value">{{ strtoupper($pesanan->service_type) }}</p></div>
            <div><p class="label"><strong>EKSPEDISI</strong></p><p class="value">{{ strtoupper(explode('-', $pesanan->expedition)[1]) }}</p></div>
        </div>

        <!-- COD -->
        @if($pesanan->payment_method == 'COD' || $pesanan->payment_method == 'CODBARANG')
        <div class="text-center mt-2 border-b border-dashed border-gray-400 pb-2">
            <p class="label">HARGA COD</p>
            <p class="value text-red-600">Rp {{ number_format($pesanan->price, 0, ',', '.') }}</p>
        </div>
        @endif

        <!-- Resi Aktual -->
        @if($pesanan->resi_aktual)
        <div class="text-center mt-3 pt-2 border-t border-dashed border-gray-400">
            <p class="label">RESI AKTUAL ({{ $pesanan->jasa_ekspedisi_aktual }})</p>
            <svg id="barcodeAktual" class="barcode"></svg>
        </div>
        @endif

        <!-- Footer -->
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
