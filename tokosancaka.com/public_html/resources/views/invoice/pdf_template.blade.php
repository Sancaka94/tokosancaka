<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: 'Helvetica', sans-serif; color: #333; margin: 0; padding: 0; }
        .header-bg { background-color: #003399; color: white; padding: 40px; border-bottom-left-radius: 50% 20%; border-bottom-right-radius: 50% 20%; }
        .invoice-title { font-size: 40px; font-weight: bold; }
        .content { padding: 30px; }
        .info-table { width: 100%; margin-bottom: 30px; }
        .info-table td { vertical-align: top; width: 50%; }
        .items-table { width: 100%; border-collapse: collapse; }
        .items-table th { background-color: #003399; color: white; padding: 10px; text-align: left; }
        .items-table td { border: 1px solid #ddd; padding: 10px; }
        .footer { margin-top: 50px; text-align: right; padding-right: 50px; }
        .signature-img { width: 150px; margin-bottom: 10px; }
        .logo { position: absolute; top: 30px; right: 30px; width: 80px; }
    </style>
</head>
<body>
    <div class="header-bg">
        <span class="invoice-title">INVOICE</span>
        <div style="float: right; text-align: right;">
            <p>NO: {{ $invoice->invoice_no }}</p>
            <img src="https://tokosancaka.com/storage/uploads/logo.jpeg" class="logo">
        </div>
    </div>

    <div class="content">
        <table class="info-table">
            <tr>
                <td>
                    <strong>Bill To:</strong><br>
                    {{ $invoice->customer_name }}<br>
                    @if($invoice->company_name)
                        {{ $invoice->company_name }}<br>
                    @endif
                    @if($invoice->alamat)
                        {{ $invoice->alamat }}<br>
                    @endif
                    <br>
                    Date: {{ date('d F Y', strtotime($invoice->date)) }}
                </td>
                <td style="text-align: right;">
                    <strong>From:</strong><br>
                    CV. SANCAKA KARYA HUTAMA<br>
                    Jl. Dr. Wahidin no.18A Ngawi<br>
                    085745808809
                </td>
            </tr>
        </table>

        <table class="items-table">
            <thead>
                <tr>
                    <th>Description</th>
                    <th>Qty</th>
                    <th>Price</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($invoice->items as $item)
                <tr>
                    <td>{{ $item->description }}</td>
                    <td>{{ $item->qty }}</td>
                    <td>Rp {{ number_format($item->price, 0, ',', '.') }}</td>
                    <td>Rp {{ number_format($item->total, 0, ',', '.') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        @if($invoice->keterangan)
        <div style="margin-top: 20px; width: 60%;">
            <strong>Note:</strong><br>
            <p style="margin-top: 5px; font-size: 14px; color: #555;">{{ nl2br($invoice->keterangan) }}</p>
        </div>
        @endif

        <div style="margin-top: 20px; text-align: right; background: #003399; color: white; padding: 10px;">
            <strong>Grand Total: Rp {{ number_format($invoice->grand_total, 0, ',', '.') }}</strong>
        </div>

        <div class="footer">
            <p>Hormat Kami,</p>
            @if($invoice->signature_path)
                <img src="{{ storage_path('app/public/' . $invoice->signature_path) }}" class="signature-img"><br>
            @else
                <div style="height: 100px;"></div>
            @endif
            <strong>Amal Ibnu Muharram</strong><br>
            Direktur
        </div>
    </div>
</body>
</html>
