<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Struk Parkir</title>
    <style>
        /* Desain struk khusus ukuran kertas thermal 58mm */
        body {
            font-family: 'Courier New', Courier, monospace;
            width: 58mm;
            margin: 0 auto;
            padding: 10px;
            font-size: 12px;
            color: #000;
        }
        .text-center { text-align: center; }
        .font-bold { font-weight: bold; }
        .divider { border-bottom: 1px dashed #000; margin: 5px 0; }
        .qr-container { margin: 10px 0; display: flex; justify-content: center; }
    </style>
</head>
<body onload="window.print();">

    <div class="text-center font-bold" style="font-size: 16px;">
        SANCAKA PARKIR
    </div>
    <div class="text-center">
        Jl. Dr. Wahidin No. 18A, Ngawi<br>
        WA: 085745808809
    </div>

    <div class="divider"></div>

    <table style="width: 100%; font-size: 12px;">
        <tr>
            <td style="width: 30%;">No. Plat</td>
            <td>: {{ $transaction->plate_number }}</td>
        </tr>
        <tr>
            <td>Jenis</td>
            <td>: {{ ucfirst($transaction->vehicle_type) }}</td>
        </tr>
        <tr>
            <td>Masuk</td>
            <td>: {{ \Carbon\Carbon::parse($transaction->entry_time)->format('d/m/Y H:i') }}</td>
        </tr>
    </table>

    <div class="divider"></div>

    <div class="qr-container text-center">
        {!! QrCode::size(100)->margin(0)->generate((string)$transaction->id) !!}
    </div>

    <div class="text-center font-bold" style="font-size: 14px; margin-top: 5px;">
        TRX-{{ str_pad($transaction->id, 5, '0', STR_PAD_LEFT) }}
    </div>

    <div class="divider"></div>

    <div class="text-center" style="font-size: 10px; margin-top: 10px;">
        Simpan karcis ini sebagai<br>
        bukti parkir yang sah.<br>
        Terima Kasih.
    </div>

</body>
</html>
