<!DOCTYPE html>
<html lang="id">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Invoice #{{ $invoice->invoice_no }}</title>

    @php
        // 1. LOGIKA STATUS
        $isLunas = (isset($invoice->sisa_tagihan) && $invoice->sisa_tagihan <= 0) || (isset($invoice->grand_total) && $invoice->grand_total <= 0);
        $statusText = $isLunas ? 'LUNAS' : 'BELUM LUNAS';
        $isCancelled = false; // Ganti jika ada status batal

        // 2. SENSOR NAMA
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

        // 3. SENSOR HP (Menyisakan 4 angka depan)
        $maskPhone = function($phone) {
            $phone = preg_replace('/[^0-9]/', '', $phone ?? '');
            if (strlen($phone) >= 4) {
                return substr($phone, 0, 4) . '*** *** ***';
            }
            return '*** *** ***';
        };

        // 4. WATERMARK
        $tglTerbitWmk = date('d M Y', strtotime($invoice->date ?? now()));
        $nomorResiInvoice = $invoice->resi ?? $invoice->invoice_no;
        $wmText = "VALID {$statusText} CV SANCAKA KARYA HUTAMA SANCAKA EXPRESS CREATED {$tglTerbitWmk} {$nomorResiInvoice}";
    @endphp

    <style>
        /* CSS MURNI - AMAN UNTUK DOMPDF & WKHTMLTOPDF */
        @page {
            margin: 30px 40px;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 11px;
            color: #000;
            line-height: 1.4;
            position: relative;
        }

        /* SEMBUNYIKAN TOMBOL DI PDF */
        .no-print, button { display: none !important; }

        /* PITA STATUS POJOK KANAN */
        .ribbon-wrapper {
            position: absolute;
            top: -30px;
            right: -40px;
            width: 150px;
            height: 150px;
            overflow: hidden;
            z-index: 100;
        }
        .ribbon {
            position: absolute;
            display: block;
            width: 220px;
            padding: 8px 0;
            background-color: #dc2626; /* Merah = Belum Lunas */
            color: #fff;
            font-weight: bold;
            font-size: 11px;
            text-transform: uppercase;
            text-align: center;
            transform: rotate(45deg);
            top: 30px;
            right: -50px;
            letter-spacing: 2px;
        }
        .ribbon.paid { background-color: #16a34a; } /* Hijau = Lunas */

        /* WATERMARK (Teks statis di tengah PDF) */
        .watermark {
            position: fixed;
            top: 35%;
            left: -10%;
            width: 120%;
            text-align: center;
            font-size: 18px;
            font-weight: bold;
            color: #000;
            opacity: 0.04; /* Sangat Samar */
            transform: rotate(-35deg);
            z-index: -999;
            word-wrap: break-word;
            line-height: 2;
        }

        /* STRUKTUR TABEL (Pengganti Flexbox) */
        table { width: 100%; border-collapse: collapse; }
        td { vertical-align: top; }

        /* HEADER */
        .header { border-bottom: 2px solid #000; padding-bottom: 20px; margin-bottom: 25px; }
        .logo-img { max-width: 150px; max-height: 60px; object-fit: contain; } /* Kunci Ukuran Gambar! */
        .invoice-title { font-size: 32px; font-weight: 900; text-transform: uppercase; margin: 0; letter-spacing: 1px; }

        /* INFO PELANGGAN */
        .info-section { margin-bottom: 30px; }
        .label { font-size: 9px; font-weight: bold; color: #666; text-transform: uppercase; letter-spacing: 1px; border-bottom: 1px solid #ddd; padding-bottom: 3px; margin-bottom: 5px; }
        .info-data { font-size: 11px; font-weight: bold; text-transform: uppercase; margin-bottom: 2px; }
        .info-text { color: #444; font-size: 11px; line-height: 1.5; }

        /* TABEL ITEM */
        .item-table { margin-bottom: 30px; border: 1px solid #000; }
        .item-table th { background-color: #000; color: #fff; padding: 10px; font-size: 9px; text-transform: uppercase; letter-spacing: 1px; text-align: left; }
        .item-table td { padding: 10px; border-bottom: 1px solid #ddd; font-size: 11px; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .font-bold { font-weight: bold; }

        /* PERHITUNGAN TOTAL */
        .totals-wrapper { width: 100%; margin-bottom: 40px; }
        .notes-area { width: 55%; float: left; padding-right: 20px; }
        .math-area { width: 40%; float: right; }

        .math-table td { padding: 6px 0; font-size: 11px; border-bottom: 1px solid #eee; }
        .math-table .grand-total { font-weight: 900; font-size: 14px; border-top: 2px solid #000; border-bottom: 2px solid #000; padding: 10px 0; }

        .clear { clear: both; }

        /* TANDA TANGAN */
        .signature-area { text-align: center; float: right; width: 180px; margin-top: 30px; }
        .signature-img { max-width: 120px; height: 60px; margin: 5px auto; object-fit: contain; }
        .signature-line { border-top: 1px solid #000; font-weight: 900; font-size: 11px; padding-top: 5px; margin-top: 50px; text-transform: uppercase; }
        .signature-role { font-size: 9px; color: #666; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; margin-top: 2px; }
    </style>
</head>
<body>

    <!-- LAYER WATERMARK (Statis untuk PDF) -->
    <div class="watermark">
        {{ $wmText }}<br><br>
        {{ $wmText }}<br><br>
        {{ $wmText }}<br><br>
        {{ $wmText }}
    </div>

    <!-- PITA STATUS -->
    <div class="ribbon-wrapper">
        <div class="ribbon {{ $isLunas ? 'paid' : '' }}">
            {{ $isLunas ? 'LUNAS' : ($isCancelled ? 'REFUND' : 'BELUM LUNAS') }}
        </div>
    </div>

    <!-- HEADER LOGO & JUDUL -->
    <table class="header">
        <tr>
            <td style="width: 50%; vertical-align: middle;">
                @if(file_exists(storage_path('app/public/uploads/logo.jpeg')))
                    <!-- HARUS PAKAI ATRIBUT WIDTH AGAR GAMBAR TIDAK MELEDAK DI PDF -->
                    <img src="data:image/jpeg;base64,{{ base64_encode(file_get_contents(storage_path('app/public/uploads/logo.jpeg'))) }}" width="150" class="logo-img">
                @else
                    <h2 style="margin:0; font-weight:900; font-size:18px;">CV SANCAKA KARYA HUTAMA</h2>
                @endif
            </td>
            <td style="width: 50%; text-align: right; vertical-align: middle;">
                <h1 class="invoice-title">INVOICE</h1>
                <p style="margin: 5px 0 0 0; color: #666; font-size: 11px; font-weight: bold;">NO: {{ $invoice->invoice_no }}</p>
            </td>
        </tr>
    </table>

    <!-- INFORMASI PELANGGAN -->
    <table class="info-section">
        <tr>
            <!-- Kolom Kiri -->
            <td style="width: 35%;">
                <div class="label">INVOICE TO</div>
                <div class="info-data">{{ $maskName($invoice->customer_name) }}</div>
                @if($invoice->company_name)
                    <div class="info-text font-bold" style="margin-bottom: 2px;">{{ $maskName($invoice->company_name) }}</div>
                @endif
                @if($invoice->alamat)
                    <div class="info-text">{{ $invoice->alamat }}</div>
                @endif
                <div class="info-text" style="margin-top: 4px;">Phone: {{ $maskPhone($invoice->phone ?? '') }}</div>
            </td>

            <!-- Kolom Tengah -->
            <td style="width: 35%; padding-left: 20px;">
                <div class="label">FROM</div>
                <div class="info-data">CV SANCAKA KARYA HUTAMA</div>
                <div class="info-text">Jl. Dr. Wahidin no.18A RT.22/05</div>
                <div class="info-text">Ketanggi, Ngawi, Jawa Timur 63211</div>
            </td>

            <!-- Kolom Kanan -->
            <td style="width: 30%; text-align: right;">
                <div class="label" style="text-align: right;">DATE DETAILS</div>
                <div class="info-text" style="margin-bottom: 5px;">Tanggal Terbit:</div>
                <div class="info-data">{{ date('d F Y', strtotime($invoice->date)) }}</div>

                <!-- QR Code Dummy yang dibatasi ukurannya -->
                <div style="margin-top: 10px;">
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=60x60&data={{ urlencode($invoice->invoice_no) }}" width="60" height="60" style="border: 1px solid #ccc; padding: 2px;">
                </div>
            </td>
        </tr>
    </table>

    <!-- TABEL ITEM -->
    <table class="item-table">
        <thead>
            <tr>
                <th style="width: 45%;">DESCRIPTION</th>
                <th style="width: 20%;" class="text-center">PRICE</th>
                <th style="width: 10%;" class="text-center">QTY</th>
                <th style="width: 25%;" class="text-right">TOTAL</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->items as $item)
            <tr>
                <td>{{ $item->description }}</td>
                <td class="text-center">Rp {{ number_format($item->price, 0, ',', '.') }}</td>
                <td class="text-center">{{ $item->qty }}</td>
                <td class="text-right font-bold">Rp {{ number_format($item->total, 0, ',', '.') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <!-- PERHITUNGAN & CATATAN -->
    <div class="totals-wrapper">
        <div class="notes-area">
            @if($invoice->keterangan)
                <div class="label">CATATAN TAMBAHAN</div>
                <div class="info-text" style="padding: 10px; background-color: #f9f9f9; border: 1px solid #eee;">
                    {!! nl2br(e($invoice->keterangan)) !!}
                </div>
            @endif

            <div class="label" style="margin-top: 15px;">PAYMENT INFO</div>
            <div class="info-text" style="color: #666; font-size: 9px;">
                Pembayaran dianggap sah apabila telah divalidasi oleh sistem atau masuk ke rekening resmi CV SANCAKA KARYA HUTAMA.
            </div>
        </div>

        <div class="math-area">
            <table class="math-table">
                <tr>
                    <td>SUBTOTAL</td>
                    <td class="text-right font-bold">Rp {{ number_format($invoice->subtotal, 0, ',', '.') }}</td>
                </tr>
                @if($invoice->discount_amount > 0)
                <tr>
                    <td>DISKON</td>
                    <td class="text-right font-bold">- Rp {{ number_format($invoice->discount_amount, 0, ',', '.') }}</td>
                </tr>
                @endif
                <tr>
                    <td class="grand-total">GRAND TOTAL</td>
                    <td class="text-right grand-total">Rp {{ number_format($invoice->grand_total, 0, ',', '.') }}</td>
                </tr>
                @if($invoice->dp > 0)
                <tr>
                    <td style="color: #666; padding-top: 10px;">DP / UANG MUKA</td>
                    <td class="text-right font-bold" style="color: #666; padding-top: 10px;">Rp {{ number_format($invoice->dp, 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td class="font-bold">SISA KEKURANGAN</td>
                    <td class="text-right font-bold">Rp {{ number_format($invoice->sisa_tagihan, 0, ',', '.') }}</td>
                </tr>
                @endif
            </table>
        </div>
        <div class="clear"></div>
    </div>

    <!-- TANDA TANGAN -->
    <div class="signature-area">
        @if($invoice->signature_path)
            <img src="{{ storage_path('app/public/' . $invoice->signature_path) }}" width="100" class="signature-img">
            <div class="signature-line">A*** I*** M**********</div>
        @else
            <div class="signature-line">A*** I*** M**********</div>
        @endif
        <div class="signature-role">DIRECTOR</div>
    </div>
    <div class="clear"></div>

</body>
</html>
