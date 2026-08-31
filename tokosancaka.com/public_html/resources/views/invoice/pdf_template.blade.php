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

        // 2. SENSOR NAMA (Amal Ibnu -> A*** I***)
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

        // 3. SENSOR HP (085745808809 -> 0857458*****)
        $maskPhone = function($phone) {
            $phone = preg_replace('/[^0-9]/', '', (string) $phone);
            if (strlen($phone) > 7) {
                return substr($phone, 0, 7) . str_repeat('*', strlen($phone) - 7);
            } elseif (strlen($phone) > 0) {
                return $phone . '***';
            }
            return '-';
        };

        // 4. TEKS WATERMARK
        $tglTerbitWmk = date('d M Y', strtotime($invoice->date ?? now()));
        $nomorResiInvoice = $invoice->resi ?? $invoice->invoice_no;
        $wmText = "VALID {$statusText} CV SANCAKA KARYA HUTAMA SANCAKA EXPRESS CREATED {$tglTerbitWmk} {$nomorResiInvoice}";

        // 5. FETCH QR CODE (SUPER ROBUST)
        $qrBase64 = null;
        $qrData = $invoice->invoice_no;

        // Coba pakai library lokal Laravel jika ada (Sangat disarankan & cepat)
        if (class_exists('\SimpleSoftwareIO\QrCode\Facades\QrCode')) {
            try {
                $qrContent = \SimpleSoftwareIO\QrCode\Facades\QrCode::format('png')->margin(1)->size(100)->generate($qrData);
                $qrBase64 = 'data:image/png;base64,' . base64_encode($qrContent);
            } catch (\Exception $e) {}
        }

        // Jika library lokal tidak ada, ambil dari API eksternal pakai cURL (Menembus blokir server)
        if (!$qrBase64) {
            $qrUrl = "https://api.qrserver.com/v1/create-qr-code/?size=100x100&data=" . urlencode($qrData);

            if (function_exists('curl_init')) {
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $qrUrl);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Abaikan error SSL
                curl_setopt($ch, CURLOPT_TIMEOUT, 5); // Timeout 5 detik agar PDF tidak hang
                $qrContent = curl_exec($ch);
                curl_close($ch);

                if ($qrContent) {
                    $qrBase64 = 'data:image/png;base64,' . base64_encode($qrContent);
                }
            }
        }
    @endphp

    <style>
        /* PENGATURAN KERTAS DOMPDF */
        @page {
            margin: 0px;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 11px;
            color: #000;
            line-height: 1.4;
            padding: 40px 50px;
        }

        /* WATERMARK NATIVE HTML (Mendukung DomPDF Penuh) */
        .watermark-container {
            position: fixed;
            top: -20%;
            left: -50%;
            width: 200%;
            height: 200%;
            z-index: 9999;
            transform: rotate(-35deg);
            text-align: center;
        }
        .watermark-container p {
            color: rgba(0, 0, 0, 0.04); /* Samar */
            font-size: 14px;
            font-weight: bold;
            line-height: 4;
            white-space: nowrap;
            margin: 0;
            padding: 0;
            letter-spacing: 1px;
        }

        /* PITA STATUS POJOK KANAN */
        .ribbon-wrapper {
            position: absolute;
            top: 0px;
            right: 0px;
            width: 140px;
            height: 140px;
            overflow: hidden;
            z-index: 10000;
        }
        .ribbon {
            position: absolute;
            display: block;
            width: 200px;
            padding: 6px 0;
            background-color: #dc2626; /* MERAH */
            color: #fff;
            font-weight: bold;
            font-size: 11px;
            text-transform: uppercase;
            text-align: center;
            transform: rotate(45deg);
            top: 30px;
            right: -45px;
            letter-spacing: 1px;
        }
        .ribbon.paid { background-color: #16a34a; } /* HIJAU */

        /* LAYOUT & TABEL */
        table { width: 100%; border-collapse: collapse; }
        td { vertical-align: top; z-index: 10; position: relative; }

        /* HEADER & LOGO */
        .header-table { margin-bottom: 30px; border-bottom: 2px solid #000; padding-bottom: 15px; }
        .logo-img { height: 50px; width: auto; }
        .invoice-title { font-size: 32px; font-weight: 900; text-transform: uppercase; margin: 0; letter-spacing: 1px; }

        /* INFO PELANGGAN */
        .info-table { margin-bottom: 30px; }
        .label { font-size: 9px; font-weight: bold; color: #666; text-transform: uppercase; letter-spacing: 1px; border-bottom: 1px solid #e5e7eb; padding-bottom: 4px; margin-bottom: 6px; }
        .info-data { font-size: 11px; font-weight: bold; text-transform: uppercase; margin-bottom: 3px; }
        .info-text { color: #333; font-size: 11px; line-height: 1.5; }

        /* TABEL ITEM */
        .item-table { margin-bottom: 35px; border-bottom: 1px solid #000; }
        .item-table th { background-color: #000; color: #fff; padding: 10px 12px; font-size: 9px; text-transform: uppercase; letter-spacing: 1px; text-align: left; font-weight: bold; }
        .item-table td { padding: 12px; border-bottom: 1px solid #e5e7eb; font-size: 11px; color: #000; }
        .item-table tr:nth-child(even) { background-color: #f9fafb; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .font-bold { font-weight: bold; }

        /* PERHITUNGAN TOTAL */
        .notes-area { width: 50%; float: left; padding-right: 20px; position: relative; z-index: 10;}
        .math-area { width: 45%; float: right; position: relative; z-index: 10;}
        .math-table td { padding: 6px 0; font-size: 11px; border-bottom: 1px dashed #e5e7eb; }
        .math-table .grand-total { font-weight: 900; font-size: 14px; border-top: 2px solid #000; border-bottom: 2px solid #000; padding: 10px 0; border-style: solid; }
        .clear { clear: both; }

        /* TANDA TANGAN */
        .signature-area { text-align: center; float: right; width: 200px; margin-top: 40px; position: relative; z-index: 10;}
        .signature-img { height: 60px; width: auto; margin: 0 auto 5px auto; }
        .signature-line { border-top: 1px solid #000; font-weight: 900; font-size: 11px; padding-top: 5px; margin-top: 60px; text-transform: uppercase; }
        .signature-role { font-size: 9px; color: #666; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; margin-top: 2px; }
    </style>
</head>
<body>

    <!-- LAYER WATERMARK HTML -->
    <div class="watermark-container">
        @for($i=0; $i<25; $i++)
            <p>{{ $wmText }} &nbsp;&nbsp;&nbsp;&nbsp;&nbsp; {{ $wmText }} &nbsp;&nbsp;&nbsp;&nbsp;&nbsp; {{ $wmText }}</p>
        @endfor
    </div>

    <!-- PITA STATUS -->
    <div class="ribbon-wrapper">
        <div class="ribbon {{ $isLunas ? 'paid' : '' }}">
            {{ $isLunas ? 'LUNAS' : ($isCancelled ? 'REFUND' : 'BELUM LUNAS') }}
        </div>
    </div>

    <!-- HEADER LOGO & JUDUL -->
    <table class="header-table">
        <tr>
            <td style="width: 50%; vertical-align: middle;">
                @if(file_exists(storage_path('app/public/uploads/logo.jpeg')))
                    <img src="data:image/jpeg;base64,{{ base64_encode(file_get_contents(storage_path('app/public/uploads/logo.jpeg'))) }}" class="logo-img">
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
    <table class="info-table">
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
                <div class="info-text" style="margin-top: 5px;">Phone: {{ $maskPhone($invoice->phone ?? '') }}</div>
            </td>

            <!-- Kolom Tengah -->
            <td style="width: 35%; padding-left: 20px;">
                <div class="label">FROM</div>
                <div class="info-data">CV SANCAKA KARYA HUTAMA</div>
                <div class="info-text">Jl. Dr. Wahidin no.18A RT.22/05</div>
                <div class="info-text">Ketanggi, Ngawi, Jawa Timur 63211</div>
            </td>

            <!-- Kolom Kanan (QR Code dari Base64 lewat cURL) -->
            <td style="width: 30%; text-align: right;">
                <div class="label" style="text-align: right;">DATE DETAILS</div>
                <div class="info-text" style="margin-bottom: 5px;">Tanggal Terbit:</div>
                <div class="info-data">{{ date('d F Y', strtotime($invoice->date)) }}</div>

                <div style="margin-top: 15px;">
                    @if($qrBase64)
                        <img src="{{ $qrBase64 }}" style="width: 70px; height: 70px; border: 1px solid #ccc; padding: 2px; background: white;">
                    @else
                        <!-- Fallback jika gagal (server tetap memblokir koneksi luar) -->
                        <div style="width: 70px; height: 70px; border: 1px dashed #ccc; text-align: center; line-height: 70px; font-size: 9px; color: #999; float: right;">QR ERROR</div>
                    @endif
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
                <td class="text-center font-bold">{{ $item->qty }}</td>
                <td class="text-right font-bold">Rp {{ number_format($item->total, 0, ',', '.') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <!-- PERHITUNGAN & CATATAN -->
    <div>
        <div class="notes-area">
            @if($invoice->keterangan)
                <div class="label">CATATAN TAMBAHAN</div>
                <div class="info-text" style="padding: 12px; background-color: #f9fafb; border: 1px solid #e5e7eb; margin-bottom: 15px;">
                    {!! nl2br(e($invoice->keterangan)) !!}
                </div>
            @endif

            <div class="label">PAYMENT INFO</div>
            <div class="info-text" style="color: #666; font-size: 10px;">
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
                    <td style="color: #666; padding-top: 10px; border-bottom: none;">DP / UANG MUKA</td>
                    <td class="text-right font-bold" style="color: #666; padding-top: 10px; border-bottom: none;">Rp {{ number_format($invoice->dp, 0, ',', '.') }}</td>
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
            <img src="{{ storage_path('app/public/' . $invoice->signature_path) }}" class="signature-img">
            <div class="signature-line">A*** I*** M**********</div>
        @else
            <div class="signature-line">A*** I*** M**********</div>
        @endif
        <div class="signature-role">DIRECTOR</div>
    </div>
    <div class="clear"></div>

</body>
</html>
