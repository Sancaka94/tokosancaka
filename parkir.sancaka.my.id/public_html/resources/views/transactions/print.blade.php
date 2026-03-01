<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <style>
        /* Desain dioptimalkan murni untuk pembacaan engine RawBT */
        body {
            font-family: 'Courier New', Courier, monospace;
            width: 100%;
            margin: 0;
            padding: 0;
            color: #000;
        }
        .text-center { text-align: center; }
        .font-bold { font-weight: bold; }
        .text-xl { font-size: 22px; margin-bottom: 5px; }
        .text-sm { font-size: 14px; }
        .divider { border-top: 2px dashed #000; margin: 10px 0; }
        .qr-code { width: 160px; height: 160px; margin: 10px auto; display: block; }

        /* Tabel agar tulisan kiri dan kanan rapi */
        table { width: 100%; font-size: 16px; font-weight: bold; }
        td { padding: 2px 0; vertical-align: top; }
    </style>
</head>
<body>

    <div class="text-center font-bold text-xl">
        SANCAKA PARKIR
    </div>
    <div class="text-center text-sm">
        Jl. Dr. Wahidin No. 18A, Ngawi
    </div>

    <div class="divider"></div>

    <table>
        <tr>
            <td style="width: 35%;">No. Plat</td>
            <td style="width: 5%;">:</td>
            <td style="font-size: 20px;">{{ $transaction->plate_number }}</td>
        </tr>
        <tr>
            <td>Jenis</td>
            <td>:</td>
            <td>{{ ucfirst($transaction->vehicle_type) }}</td>
        </tr>
        <tr>
            <td>Masuk</td>
            <td>:</td>
            <td>{{ $transaction->entry_time->format('d/m/Y H:i') }}</td>
        </tr>
    </table>

    <div class="divider"></div>

    <img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data={{ $transaction->id }}" class="qr-code" alt="QR Code">

    <div class="text-center font-bold text-xl">
        TRX-{{ str_pad($transaction->id, 5, '0', STR_PAD_LEFT) }}
    </div>

    <div class="divider"></div>

    <div class="text-center text-sm" style="margin-top: 10px;">
        Simpan karcis ini sebagai<br>bukti parkir yang sah.<br>Terima Kasih.
    </div>

</body>
</html>
