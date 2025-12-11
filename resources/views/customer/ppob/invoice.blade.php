<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Faktur Transaksi</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            margin: 0;
            padding: 40px;
            background-color: #f4f6f9;
            color: #333;
        }
        .invoice-container {
            max-width: 800px;
            margin: 0 auto;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.05);
            overflow: hidden;
        }
        .header {
            background-color: #1a2a47; /* Biru tua */
            color: #fff;
            padding: 30px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 6px solid #f8b300; /* Kuning emas */
        }
        .header .company-info h1 {
            margin: 0;
            font-size: 28px;
            text-transform: uppercase;
        }
        .header .company-info p {
            margin: 5px 0 0;
            font-size: 14px;
            color: #ddd;
        }
        .header .contact-info {
            text-align: right;
            font-size: 14px;
        }
        .header .contact-info p {
            margin: 4px 0;
            display: flex;
            align-items: center;
            justify-content: flex-end;
        }
        .invoice-details {
            padding: 40px;
            display: flex;
            justify-content: space-between;
        }
        .invoice-to {
            width: 45%;
        }
        .invoice-to h3 {
            color: #1a2a47;
            font-size: 20px;
            margin-top: 0;
        }
        .invoice-to p {
            margin: 5px 0;
            font-size: 14px;
            color: #555;
        }
        .invoice-info {
            width: 35%;
            background-color: #f8b300; /* Kuning emas */
            color: #fff;
            padding: 20px;
            border-radius: 8px;
            text-align: left;
        }
        .invoice-info h3 {
            color: #1a2a47;
            font-size: 24px;
            margin-top: 0;
            text-transform: uppercase;
        }
        .invoice-info table {
            width: 100%;
            font-size: 14px;
        }
        .invoice-info td {
            padding: 4px 0;
            color: #1a2a47;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin: 0 40px 40px 40px;
            width: calc(100% - 80px);
        }
        .items-table thead {
            background-color: #f8b300; /* Kuning emas */
            color: #1a2a47;
        }
        .items-table th {
            padding: 15px;
            text-align: left;
            font-size: 14px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .items-table tbody tr {
            border-bottom: 1px solid #eee;
        }
        .items-table tbody tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .items-table td {
            padding: 15px;
            font-size: 14px;
            color: #555;
        }
        .footer-summary {
            padding: 0 40px 40px 40px;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }
        .payment-info {
            width: 50%;
        }
        .payment-info h4 {
            color: #1a2a47;
            font-size: 16px;
            margin-top: 0;
        }
        .payment-info p {
            margin: 5px 0;
            font-size: 14px;
            color: #555;
        }
        .totals {
            width: 40%;
            text-align: right;
        }
        .totals table {
            width: 100%;
            font-size: 14px;
        }
        .totals td {
            padding: 8px 0;
        }
        .grand-total {
            background-color: #f8b300; /* Kuning emas */
            color: #1a2a47;
            padding: 10px;
            border-radius: 6px;
            font-size: 18px;
            font-weight: bold;
            margin-top: 15px;
        }
        .thank-you {
            background-color: #1a2a47; /* Biru tua */
            color: #fff;
            padding: 20px 40px;
            text-align: center;
            font-size: 16px;
        }
        .thank-you p {
            margin: 5px 0;
            font-size: 12px;
            color: #ddd;
        }
    </style>
</head>
<body>

<div class="invoice-container">
    <div class="header">
        <div class="company-info">
            <h1>SANCAKA STORE</h1>
            <p>Pusat Belanja Online Terpercaya</p>
        </div>
        <div class="contact-info">
            <p><i class="fas fa-phone-alt"></i> +62 881 9435 180</p>
            <p><i class="fas fa-globe"></i> www.tokosancaka.com</p>
            <p><i class="fas fa-envelope"></i> support@tokosancaka.com</p>
        </div>
    </div>

    <div class="invoice-details">
        <div class="invoice-to">
            <h3>INVOICE TO:</h3>
            <p><strong>{{ $transaction->customer_no }}</strong></p>
            @if(isset($transaction->desc['detail'][0]['nama_pelanggan']))
                <p>{{ $transaction->desc['detail'][0]['nama_pelanggan'] }}</p>
            @endif
        </div>
        <div class="invoice-info">
            <h3>INVOICE</h3>
            <table>
                <tr>
                    <td>Invoice No:</td>
                    <td><strong>{{ $transaction->order_id }}</strong></td>
                </tr>
                <tr>
                    <td>Date:</td>
                    <td>{{ $transaction->created_at->format('d/m/Y H:i') }}</td>
                </tr>
                <tr>
                    <td>Status:</td>
                    <td style="color: green; font-weight: bold;">SUCCESS</td>
                </tr>
            </table>
        </div>
    </div>

    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 5%;">NO.</th>
                <th style="width: 45%;">ITEM DESCRIPTION</th>
                <th style="width: 15%;">PRICE</th>
                <th style="width: 10%;">QTY</th>
                <th style="width: 25%;">TOTAL</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>01.</td>
                <td>
                    <strong>{{ strtoupper($transaction->buyer_sku_code) }}</strong>
                    <br>
                    @if($transaction->sn)
                        <span style="font-size: 12px; color: #777;">SN: {{ $transaction->sn }}</span>
                    @endif
                </td>
                <td>Rp {{ number_format($transaction->selling_price, 0, ',', '.') }}</td>
                <td>1</td>
                <td>Rp {{ number_format($transaction->selling_price, 0, ',', '.') }}</td>
            </tr>
            </tbody>
    </table>

    <div class="footer-summary">
        <div class="payment-info">
            <h4>Payment Info:</h4>
            <p>Metode: <strong>{{ str_replace('_', ' ', $transaction->payment_method) }}</strong></p>
        </div>
        <div class="totals">
            <table>
                <tr>
                    <td>SUB TOTAL:</td>
                    <td>Rp {{ number_format($transaction->selling_price, 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td>TAX (0%):</td>
                    <td>Rp 0</td>
                </tr>
            </table>
            <div class="grand-total">
                TOTAL: Rp {{ number_format($transaction->selling_price, 0, ',', '.') }}
            </div>
        </div>
    </div>

    <div class="thank-you">
        <h3>THANK YOU FOR YOUR BUSINESS</h3>
        <p>Terima kasih telah bertransaksi di Sancaka Store.</p>
        <p>Simpan struk ini sebagai bukti pembayaran yang sah.</p>
    </div>
</div>

</body>
</html>