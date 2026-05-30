<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pembayaran Berhasil</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f4f7f6; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;">
    <table border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #f4f7f6; padding: 30px 0;">
        <tr>
            <td align="center">
                <table border="0" cellpadding="0" cellspacing="0" width="600" style="background-color: #ffffff; border-radius: 10px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.05);">
                    <tr>
                        <td align="center" style="background-color: #0d9488; padding: 30px 20px;">
                            <h1 style="color: #ffffff; margin: 0; font-size: 24px; letter-spacing: 1px;">SANCAKA SERVER</h1>
                        </td>
                    </tr>
                    
                    <tr>
                        <td style="padding: 40px 30px;">
                            <p style="color: #333333; font-size: 16px; margin-bottom: 20px;">Halo, <strong>{{ $name }}</strong>,</p>
                            <p style="color: #555555; font-size: 15px; line-height: 1.6; margin-bottom: 30px;">
                                Selamat! Transaksi Anda telah berhasil kami proses. Berikut adalah rincian transaksi Anda:
                            </p>

                            <table border="0" cellpadding="15" cellspacing="0" width="100%" style="background-color: #f8fafc; border-radius: 8px; border: 1px solid #e2e8f0;">
                                <tr>
                                    <td style="border-bottom: 1px solid #e2e8f0;">
                                        <span style="color: #64748b; font-size: 13px; text-transform: uppercase; font-weight: bold;">No. Invoice / Ref</span><br>
                                        <span style="color: #0f172a; font-size: 16px; font-weight: bold;">{{ $invoice }}</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="border-bottom: 1px solid #e2e8f0;">
                                        <span style="color: #64748b; font-size: 13px; text-transform: uppercase; font-weight: bold;">Jenis Transaksi</span><br>
                                        <span style="color: #0f172a; font-size: 16px; font-weight: 500;">{{ $type }}</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="border-bottom: 1px solid #e2e8f0;">
                                        <span style="color: #64748b; font-size: 13px; text-transform: uppercase; font-weight: bold;">Waktu Selesai</span><br>
                                        <span style="color: #0f172a; font-size: 15px;">{{ $date }} WIB</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td align="center" style="background-color: #d1fae5; border-radius: 0 0 8px 8px;">
                                        <span style="color: #065f46; font-size: 14px; text-transform: uppercase; font-weight: bold;">Nominal</span><br>
                                        <span style="color: #059669; font-size: 24px; font-weight: bold;">Rp {{ number_format($amount, 0, ',', '.') }}</span>
                                    </td>
                                </tr>
                            </table>

                            <p style="color: #555555; font-size: 15px; line-height: 1.6; margin-top: 30px;">
                                Terima kasih telah mempercayakan transaksi Anda kepada Sancaka. Jika Anda memiliki pertanyaan, jangan ragu untuk menghubungi layanan pelanggan kami.
                            </p>
                        </td>
                    </tr>
                    
                    <tr>
                        <td align="center" style="background-color: #f1f5f9; padding: 20px; border-top: 1px solid #e2e8f0;">
                            <p style="color: #94a3b8; font-size: 13px; margin: 0;">
                                &copy; {{ date('Y') }} Toko Sancaka. Hak Cipta Dilindungi.<br>
                                Email ini dibuat secara otomatis, mohon tidak membalas email ini.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>