{{-- resources/views/admin/pesanan/cetak_thermal.blade.php --}}

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

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>



    <style>

        body {

            font-family: 'Inter', sans-serif;

            background-color: #E5E7EB;

        }

        .page {

            width: 100mm;

            min-height: 150mm;

            padding: 4mm;

            margin: 10mm auto;

            background: white;

            box-shadow: 0 0 5px rgba(0, 0, 0, 0.1);

            display: flex;

            flex-direction: column;

            font-size: 9pt;

        }

        .barcode {

            width: 100%;

            height: 50px;

        }

        @media print {

            body { background: none; }

            .no-print { display: none; }

            .page {

                margin: 0;

                border: initial;

                border-radius: initial;

                width: 100%;

                height: 100%;

                box-shadow: initial;

                background: initial;

                page-break-after: always;

            }

        }

    </style>

</head>

<body>



    <div class="no-print p-4 text-center bg-white shadow-md sticky top-0 z-10">

        @php

            $backUrl = route('home');

            if (Auth::check()) {

                if (Auth::user()->hasRole('Admin')) {

                    $backUrl = route('admin.pesanan.index');

                } elseif (Auth::user()->hasRole('Pelanggan')) {

                    $backUrl = route('customer.pesanan.index');

                }

            }

        @endphp

        <button onclick="window.print()" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700">Cetak Resi</button>

        <a href="{{ route('admin.pesanan.index') }}" class="bg-gray-200 text-gray-700 px-6 py-2 rounded-lg hover:bg-gray-300">Kembali</a>

    </div>



    <div class="page">

        @php

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

        'NCS' => 'https://blogger.googleusercontent.com/img/b/R29vZ2xl/AVvXsEjxj3iyyZEjK2L4A4yCIr_E-4W3hF2lk_yb-t0Oj2oFPErCPCMHie5LHqps02xMb6sNa-Gqz5NSX_P_hzWlYpUpJUlCD4iN6_QxiSG9fzY4bsZ9XvLFDn7HCiORtNvIlPfuQbSSdW96p7x7uN8ek3FWyHW9c2bznrFBQkoLd5A9sVAFVKWLfUhT3Dxh/s320/GKL41_NCS%20Kurir%20-%20Koleksilogo.com.jpg',

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

$partnerKey = strtoupper(explode('-', $pesanan->expedition)[1]);

$partnerLogoUrl = $partnerLogos[$partnerKey] ?? 'https://placehold.co/150x50/e2e8f0/334155?text=' . urlencode($partnerKey);


$expeditionParts = explode('-', $order->expedition);
$expeditionName = $expeditionParts[1] ?? 'POSINDONESIA';
$expeditionService = $expeditionParts[2] ?? 'Regular';
$logoPath = strtolower(str_replace(' ', '', $expeditionName));
               

@endphp





        <div class="flex justify-between items-start pb-1 border-b-2 border-dashed border-black">

            {{-- ✅ PERBAIKAN: Menggunakan URL logo Sancaka yang benar --}}

            <img src="https://tokosancaka.biz.id/storage/uploads/sancaka.png" alt="Sancaka Express" class="h-10" onerror="this.style.display='none'">

            <img src="{{ asset('storage/logo-ekspedisi/' . $logoPath . '.png') }}" alt="{{ $partnerKey }} Logo" class="w-200 h-auto mr-2">

        </div>



        <div class="grid grid-cols-2 gap-2 mt-1 pb-1 border-b-2 border-dashed border-black">

           <div class="border-r-2 border-dashed border-black pr-2">

                <p class="font-bold">PENGIRIM:</p>

                <p class="font-semibold">{{ $pesanan->sender_name }}</p>

                <p>{{ $pesanan->sender_phone }}</p>

                <p>

                    {{ implode(', ', array_filter([

                        $pesanan->sender_address,

                        $pesanan->sender_village,

                        $pesanan->sender_district,

                        $pesanan->sender_regency,

                        $pesanan->sender_province,

                        $pesanan->sender_postal_code,

                    ])) }}

                </p>

            </div>

            

            <div>

                <p class="font-bold">PENERIMA:</p>

                <p class="font-semibold">{{ $pesanan->nama_pembeli }}</p>

                <p>{{ $pesanan->telepon_pembeli }}</p>

                <p>

                    {{ implode(', ', array_filter([

                        $pesanan->alamat_pengiriman,

                        $pesanan->receiver_village,

                        $pesanan->receiver_district,

                        $pesanan->receiver_regency,

                        $pesanan->receiver_province,

                        $pesanan->receiver_postal_code,

                    ])) }}

                </p>

            </div>



        </div>



        <div class="grid grid-cols-3 text-center mt-1 pb-1 border-b-2 border-black border-dashed">

            <div><p class="font-bold">ORDER ID</p><p>{{ $pesanan->nomor_invoice  }}</p></div>

            <div><p class="font-bold">BERAT</p><p>{{ $pesanan->weight }} gr</p></div>

            <div><p class="font-bold">VOLUME</p><p>{{ $pesanan->length ?? 0 }}x{{ $pesanan->width ?? 0 }}x{{ $pesanan->height ?? 0 }} cm</p></div>

              <div><p class="font-bold">LAYANAN</p><p class="font-semibold">{{ strtoupper($pesanan->service_type) }}</p></div>

            <div><p class="font-bold">EKSPEDISI</p><p class="font-semibold">{{ strtoupper(explode('-', $pesanan->expedition)[1]) }}</p></div>

        </div>

        

        @if($pesanan->payment_method == 'COD' || $pesanan->payment_method == 'CODBARANG')

             <div class="grid grid-cols-1 text-center mt-1 pb-1 border-b-2 border-black border-dashed">

                <div><p class="font-bold">HARGA COD</p><p>Rp {{ number_format($pesanan->price, 0, ',', '.') }}</p></div>

            </div>

        @endif

        

        <div class="text-center mt-2">

            <p class="font-bold">RESI SANCAKA</p>

            <svg id="barcodeSancaka" class="barcode"></svg>

        </div>



        @if($pesanan->resi_aktual)

        <div class="text-center mt-2 pt-1 border-t-2 border-dashed border-black">

            <p class="font-bold">RESI AKTUAL ({{ $pesanan->jasa_ekspedisi_aktual }})</p>

            <svg id="barcodeAktual" class="barcode"></svg>

        </div>

        @endif



        <div class="mt-auto pt-2 text-center text-xs border-t-2 border-dashed border-black">

            <p>Terima kasih telah menggunakan layanan jasa pengiriman Sancaka Express.</p>

            <p class="font-bold">{{ \Carbon\Carbon::parse($pesanan->created_at)->format('d M Y H:i') }}</p>

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



</body>

</html>

