<!DOCTYPE html>
<html>
<head>
    <title>Pembayaran Berhasil</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <h2>Halo, {{ $name }}!</h2>
    <p>Terima kasih, transaksi <strong>{{ $type }}</strong> kamu telah berhasil diproses.</p>
    
    <table style="width: 100%; max-width: 400px; border-collapse: collapse; margin-top: 20px;">
        <tr>
            <td style="padding: 8px; border-bottom: 1px solid #ddd;"><strong>No. Invoice:</strong></td>
            <td style="padding: 8px; border-bottom: 1px solid #ddd;">{{ $invoice }}</td>
        </tr>
        <tr>
            <td style="padding: 8px; border-bottom: 1px solid #ddd;"><strong>Nominal:</strong></td>
            <td style="padding: 8px; border-bottom: 1px solid #ddd;">Rp {{ number_format($amount, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td style="padding: 8px; border-bottom: 1px solid #ddd;"><strong>Tanggal:</strong></td>
            <td style="padding: 8px; border-bottom: 1px solid #ddd;">{{ $date }}</td>
        </tr>
    </table>

    <p style="margin-top: 20px;">Saldo akun Sancaka kamu sudah otomatis bertambah.</p>
    <p>Salam hangat,<br><strong>Tim Sancaka</strong></p>
</body>
</html>