{{-- resources/views/admin/pesanan/cetak.blade.php --}}
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Surat Jalan - {{ $pesanan->resi }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>

    <style>
        body {
            font-family: 'Roboto', sans-serif;
            background-color: #E5E7EB;
        }
        .page {
            width: 210mm; /* Ukuran A4 Portrait */
            min-height: 148mm; /* Setengah A4 */
            padding: 1cm;
            margin: 1cm auto;
            background: white;
            box-shadow: 0 0 5px rgba(0, 0, 0, 0.1);
            font-size: 10pt;
        }
        .barcode {
            height: 50px;
        }
        .info-box {
            border: 1px solid #D1D5DB;
            padding: 0.75rem;
            border-radius: 0.5rem;
        }
        .info-title {
            font-size: 0.7rem;
            font-weight: 700;
            color: #6B7280;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 0.25rem;
        }
        @media print {
            body { background: none; }
            .no-print { display: none; }
            .page {
                margin: 0;
                border: initial;
                width: 100%;
                min-height: 100%;
                box-shadow: initial;
                background: initial;
                page-break-after: always;
            }
        }
    </style>
</head>
<body>

    {{-- Tombol di luar cetakan --}}
    <div class="no-print p-4 text-center bg-white shadow-md sticky top-0 z-10">
        <button onclick="window.print()" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700">
            Cetak Surat Jalan
        </button>
        <a href="{{ route('admin.pesanan.index') }}" class="bg-gray-200 text-gray-700 px-6 py-2 rounded-lg hover:bg-gray-300">
            Kembali
        </a>
    </div>

    {{-- Konten cetak --}}
    <div class="page">
        @php
            $partnerLogos = [
                'JNE' => 'https://upload.wikimedia.org/wikipedia/commons/9/92/New_Logo_JNE.png',
                'J&T EXPRESS' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/3/35/J%26T_Express_logo.svg/1200px-J%26T_Express_logo.svg.png',
                'J&T CARGO' => 'https://www.jtcargo.id/images/logo/logo-cargo.png',
                'WAHANA EXPRESS' => 'https://wahana.com/assets/img/logo-wahana-new.png',
                'POS INDONESIA' => 'https://www.posindonesia.co.id/images/logo-pos.png',
                'SAP EXPRESS' => 'https://sap-express.id/wp-content/uploads/2022/07/LOGO-SAPEXPRESS-1.png',
                'INDAH CARGO' => 'https://blogger.googleusercontent.com/img/b/R29vZ2xl/AVvXsEicOAaLoH2eElQ93_gbkzhvk4dRhWVlk5wQsGgilihIB58321aHchlJLdjyz1ToS25P_nWrHJ_E4QBiW_OVlI7tQt7cZ5I0HZqk6StS7jZltLVvDXp2d5ZDLB9yklhV4x6z2iXyURURDv_unhf-U6vyiD_8to9OC4PBwMwyU_5wAqOiCl6tKiaTA-ri1Q/s851/Logo%20Indah%20Logistik%20Cargo@0.5x.png',
                'LION PARCEL' => 'https://assets.bukalapak.com/beagle/images/courier_logo/lionparcel.png',
                'ID EXPRESS' => 'https://idexpress.com/assets/images/logo/logo.png',
                'SPX EXPRESS' => 'https://deo.shopeemobile.com/shopee/shopee-spx-live-track-id/static/media/logo-spx.12b3c3c3.svg',
                'NCS' => 'https://www.ncskurir.com/wp-content/uploads/2021/01/logo-ncs.png',
                'SENTRAL CARGO' => 'https://sentralcargo.co.id/assets/img/logo.png',
                'SANCAKA EXPRESS' => 'https://sancaka.bisnis.pro/wp-content/uploads/sites/5/2024/10/WhatsApp_Image_2024-10-08_at_10.14.16-removebg-preview.png',
                'Sicepat' => 'https://www.sicepat.com/images/logo-sicepat-2.png',
                'Ninja Xpress' => 'https://cdn.ninjavan.co/3/ninja-xpress-logo.png',
                'Paxel' => 'https://paxel.co/images/logo-paxel.png',
                'LEX (Lazada Express)' => 'https://sellercenter.lazada.co.id/assets/images/knowledge-management/lex-logo.png',
                'OExpress' => 'https://assets.autokirim.com/courier/oexpress.png',
            ];
            $partnerKey = $pesanan->jasa_ekspedisi_aktual ?? $pesanan->expedition;
            $partnerLogoUrl = $partnerLogos[$partnerKey] ?? 'https://placehold.co/150x50/e2e8f0/334155?text=' . urlencode($partnerKey);
        @endphp

        <!-- Header -->
        <div class="flex justify-between items-start border-b-2 border-black pb-4">
            <div>
                <img src="https://tokosancaka.biz.id/storage/uploads/sancaka.png" alt="Logo Sancaka" class="h-16">
                <div class="text-xs mt-2">
                    <p class="font-bold">CV. SANCAKA KARYA HUTAMA</p>
                    <p>Hotline: 0857-4580-8809</p>
                    <p>Email: admin@sancaka.biz.id</p>
                </div>
            </div>
            
<div class="flex flex-col items-end justify-start w-1/3 text-right space-y-2">
    {{-- Logo --}}
    <img src="{{ $partnerLogoUrl }}" alt="Logo Rekanan" class="h-12 object-contain">

    {{-- Barcode --}}
    <svg class="barcode max-w-[180px] w-full h-12"
        jsbarcode-format="CODE128"
        jsbarcode-value="{{ $pesanan->resi_aktual ?? $pesanan->resi }}"
        jsbarcode-textmargin="2"
        jsbarcode-fontoptions="bold"
        jsbarcode-fontsize="16">
    </svg>
</div>


        </div>

        <!-- Detail Pengiriman -->
        <div class="grid grid-cols-3 gap-4 mt-4 text-xs">
            <div><strong class="text-gray-500">No. Resi:</strong><p class="font-bold">{{ $pesanan->resi }}</p></div>
            <div><strong class="text-gray-500">Tanggal:</strong><p>{{ \Carbon\Carbon::parse($pesanan->tanggal_pesanan)->format('d M Y, H:i') }}</p></div>
            <div><strong class="text-gray-500">Asal Pengiriman:</strong><p>NGAWI</p></div>
        </div>

        <!-- Info Pengirim & Penerima -->
        <div class="grid grid-cols-2 gap-6 mt-4">
            <div class="info-box">
                <p class="info-title">Pengirim</p>
                <p class="font-bold text-lg">{{ $pesanan->sender_name }}</p>
                <p>{{ $pesanan->sender_phone }}</p>
                <p class="mt-2 text-gray-600">{{ $pesanan->sender_address }}</p>
            </div>
            <div class="info-box">
                <p class="info-title">Penerima</p>
                {{-- ✅ PERBAIKAN: Menggunakan nama kolom yang benar --}}
                <p class="font-bold text-lg">{{ $pesanan->nama_pembeli }}</p>
                <p>{{ $pesanan->telepon_pembeli }}</p>
                <p class="mt-2 text-gray-600">{{ $pesanan->alamat_pengiriman }}</p>
            </div>
        </div>

        <!-- Detail Barang & Biaya -->
        <div class="grid grid-cols-3 gap-6 mt-4 info-box">
            <div>
                <p class="info-title">Detail Kiriman</p>
                <p><strong>Jenis:</strong> {{ $pesanan->service_type }}</p>
                <p><strong>Via:</strong> DARAT</p>
                <p><strong>Ekspedisi:</strong> {{ $pesanan->jasa_ekspedisi_aktual ?? $pesanan->expedition }}</p>
            </div>
            <div>
                <p class="info-title">Detail Paket</p>
                <p><strong>Isi:</strong> {{ $pesanan->item_description }}</p>
                <p><strong>Berat:</strong> {{ $pesanan->weight }} Kg</p>
                <p><strong>Volume:</strong> {{ number_format((($pesanan->length ?? 0) * ($pesanan->width ?? 0) * ($pesanan->height ?? 0)) / 6000, 3) }} Kg</p>
            </div>
            <div>
                <p class="info-title">Pembayaran</p>
                <p><strong>Metode:</strong> {{ $pesanan->payment_method }}</p>
                <p class="font-bold text-lg mt-2">Rp {{ number_format($pesanan->total_harga_barang, 0, ',', '.') }}</p>
            </div>
        </div>

        <!-- Tanda Tangan -->
        <div class="grid grid-cols-3 gap-8 mt-12 text-center text-xs">
            <div>
                <p class="mb-16">Tanda Tangan Pengirim,</p>
                <p class="border-t border-gray-400 pt-1 font-semibold">{{ $pesanan->sender_name }}</p>
            </div>
            <div>
                <p class="mb-16">Tanda Tangan Petugas,</p>
                <p class="border-t border-gray-400 pt-1 font-semibold">SANCAKA EXPRESS</p>
            </div>
            <div>
                <p class="mb-16">Tanda Tangan Penerima,</p>
                {{-- ✅ PERBAIKAN: Menggunakan nama kolom yang benar --}}
                <p class="border-t border-gray-400 pt-1 font-semibold">{{ $pesanan->nama_pembeli }}</p>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            JsBarcode(".barcode").init();
        });
    </script>

</body>
</html>
