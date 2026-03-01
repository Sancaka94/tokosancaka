<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Karcis Parkir - {{ $transaction->plate_number }}</title>
    <style>
        /* Pengaturan ukuran kertas Thermal 58mm */
        @page { margin: 0; size: 58mm auto; }

        body {
            box-sizing: border-box; /* KUNCI: Mencegah lebar melar melebihi 58mm akibat padding */
            font-family: 'Courier New', Courier, monospace; /* Font standar mesin kasir */
            width: 58mm;
            margin: 0 auto;
            padding: 2mm;
            font-size: 11px;
            color: #000;
            line-height: 1.2;
            background-color: #fff;
        }

        .text-center { text-align: center; }
        .font-bold { font-weight: bold; }
        .text-xl { font-size: 16px; margin: 2px 0; }
        .divider { border-top: 1px dashed #000; margin: 4px 0; }
        .qr-code { width: 35mm; height: 35mm; margin: 5px auto; display: block; max-width: 100%; }
        .mb-1 { margin-bottom: 5px; }
        .mt-1 { margin-top: 5px; }
    </style>
</head>

<body onload="setTimeout(function() { window.print(); }, 500);">

    <div class="text-center font-bold text-xl mb-1">
        {{ $tenant->name ?? 'SANCAKA PARKIR' }}
    </div>
    <div class="text-center" style="font-size: 9px;">
        {{ $tenant->company_address ?? 'Jl. Dr. Wahidin No. 18A, Ngawi' }}
    </div>

    <div class="divider"></div>

    <div>No. Plat : <span class="font-bold text-xl">{{ $transaction->plate_number }}</span></div>
    <div>Jenis    : {{ ucfirst($transaction->vehicle_type) }}</div>
    <div>Masuk    : {{ $transaction->entry_time->format('d/m/Y H:i') }}</div>
    <div>Petugas  : {{ $transaction->operator->name ?? 'Sistem' }}</div>

    <div class="divider"></div>

    <img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data={{ $transaction->id }}" class="qr-code" alt="QR Code">

    <div class="text-center font-bold mt-1">
        TRX-{{ str_pad($transaction->id, 5, '0', STR_PAD_LEFT) }}
    </div>

    <div class="divider"></div>

    <div class="text-center" style="font-size: 9px; margin-top: 5px;">
        Simpan karcis ini sebagai<br>bukti parkir yang sah.<br>Terima Kasih.
    </div>

</body>
</html>
