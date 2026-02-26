<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $invoice->invoice_no }}</title>
    <style>
        body { font-family: 'Helvetica', sans-serif; color: #333; margin: 0; padding: 0; font-size: 14px; }
        .header-bg { background-color: #003399; color: white; padding: 30px 40px; border-bottom-left-radius: 40% 15%; border-bottom-right-radius: 40% 15%; height: 100px; position: relative;}
        .invoice-title { font-size: 38px; font-weight: bold; letter-spacing: 2px;}
        .invoice-no { font-size: 16px; font-weight: normal; margin-top: 5px;}
        .logo-area { position: absolute; top: 30px; right: 40px; text-align: right; }
        .logo { width: 80px; border-radius: 8px; margin-bottom: 5px; background: white; padding: 5px;}

        .content { padding: 40px; }
        .info-table { width: 100%; margin-bottom: 30px; }
        .info-table td { vertical-align: top; width: 50%; line-height: 1.5; }

        .items-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .items-table th { background-color: #003399; color: white; padding: 12px; text-align: left; font-size: 13px; }
        .items-table td { border-bottom: 1px solid #eee; padding: 12px; font-size: 13px; }
        .items-table tr:nth-child(even) { background-color: #f9f9f9; }

        .totals-container { width: 100%; margin-top: 20px; }
        .notes-area { float: left; width: 50%; color: #555; }
        .totals-area { float: right; width: 45%; }
        .totals-table { width: 100%; border-collapse: collapse; }
        .totals-table td { padding: 8px; }
        .grand-total-row td { background: #003399; color: white; font-weight: bold; font-size: 16px; border-radius: 4px; }

        .footer { margin-top: 50px; text-align: right; padding-right: 20px; clear: both;}
        .signature-img { width: 140px; height: auto; margin: 5px 0; }
        .signature-space { height: 80px; }
    </style>
</head>
<body>
    <div class="header-bg">
        <div style="float: left;">
            <div class="invoice-title">INVOICE</div>
            <div class="invoice-no">NO: {{ $invoice->invoice_no }}</div>
        </div>
        <div class="logo-area">
            <img src="https://tokosancaka.com/storage/uploads/logo.jpeg" class="logo" alt="Sancaka Logo">
        </div>
    </div>

    <div class="content">
        <table class="info-table">
            <tr>
                <td>
                    <strong style="color:#003399; font-size: 16px;">Bill To:</strong><br>
                    <strong>{{ $invoice->customer_name }}</strong><br>
                    @if($invoice->company_name)
                        {{ $invoice->company_name }}<br>
                    @endif
                    @if($invoice->alamat)
                        {{ $invoice->alamat }}<br>
                    @endif
                    <div style="margin-top: 10px; color: #666;">
                        Date: {{ date('d F Y', strtotime($invoice->date)) }}
                    </div>
                </td>
                <td style="text-align: right;">
                    <strong style="color:#003399; font-size: 16px;">From:</strong><br>
                    <strong>CV. SANCAKA KARYA HUTAMA</strong><br>
                    Jl. Dr. Wahidin no.18A RT.22/05<br>
                    Ketanggi, Ngawi, Jawa Timur 63211<br>
                    Phone: 085745808809
                </td>
            </tr>
        </table>

        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 40%;">Description</th>
                    <th style="width: 10%; text-align: center;">Qty</th>
                    <th style="width: 25%; text-align: right;">Price</th>
                    <th style="width: 25%; text-align: right;">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($invoice->items as $item)
                <tr>
                    <td>{{ $item->description }}</td>
                    <td style="text-align: center;">{{ $item->qty }}</td>
                    <td style="text-align: right;">Rp {{ number_format($item->price, 0, ',', '.') }}</td>
                    <td style="text-align: right;">Rp {{ number_format($item->total, 0, ',', '.') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div class="totals-container">
            <div class="notes-area">
                @if($invoice->keterangan)
                    <strong style="color:#003399;">Keterangan / Notes:</strong><br>
                    <p style="margin-top: 5px; font-size: 12px; line-height: 1.4;">{!! nl2br(e($invoice->keterangan)) !!}</p>
                @endif
            </div>

            <div class="totals-area">
                <table class="totals-table">
                    <tr>
                        <td style="text-align: left; color: #555;">Subtotal:</td>
                        <td style="text-align: right; color: #555;">Rp {{ number_format($invoice->subtotal, 0, ',', '.') }}</td>
                    </tr>

                    @if($invoice->discount_amount > 0)
                    <tr>
                        <td style="text-align: left; color: #d9534f;">Diskon:</td>
                        <td style="text-align: right; color: #d9534f;">- Rp {{ number_format($invoice->discount_amount, 0, ',', '.') }}</td>
                    </tr>
                    @endif

                    <tr class="grand-total-row">
                        <td style="text-align: left;">Grand Total:</td>
                        <td style="text-align: right;">Rp {{ number_format($invoice->grand_total, 0, ',', '.') }}</td>
                    </tr>

                    @if($invoice->dp > 0)
                    <tr>
                        <td style="text-align: left; color: #555; padding-top: 15px;">DP / Uang Muka:</td>
                        <td style="text-align: right; color: #555; padding-top: 15px;">Rp {{ number_format($invoice->dp, 0, ',', '.') }}</td>
                    </tr>
                    <tr style="border-top: 1px dashed #ccc;">
                        <td style="text-align: left; color: #d9534f; font-weight: bold;">Sisa Kekurangan:</td>
                        <td style="text-align: right; color: #d9534f; font-weight: bold;">Rp {{ number_format($invoice->sisa_tagihan, 0, ',', '.') }}</td>
                    </tr>
                    @else
                        @if($invoice->grand_total <= 0)
                        <tr>
                            <td colspan="2" style="text-align: right; color: #28a745; font-weight: bold; padding-top: 10px;">STATUS: LUNAS</td>
                        </tr>
                        @endif
                    @endif
                </table>
            </div>
        </div>

        <div class="footer">
            <p style="margin-bottom: 0; color: #555;">Hormat Kami,</p>
            @if($invoice->signature_path)
                <img src="{{ storage_path('app/public/' . $invoice->signature_path) }}" class="signature-img">
            @else
                <div class="signature-space"></div>
            @endif
            <br>
            <strong>Amal Ibnu Muharram</strong><br>
            <span style="color: #666; font-size: 12px;">Direktur</span>
        </div>
    </div>
</body>
</html>
