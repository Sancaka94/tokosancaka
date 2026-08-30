<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice #{{ $pesanan->nomor_invoice }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .ribbon-wrapper {
            position: absolute;
            right: -5px; top: -5px;
            z-index: 1;
            overflow: hidden;
            width: 150px; height: 150px;
            text-align: right;
        }
        .ribbon {
            font-size: 1.25rem;
            font-weight: bold;
            color: #FFF;
            text-transform: uppercase;
            text-align: center;
            line-height: 40px;
            transform: rotate(45deg);
            -webkit-transform: rotate(45deg);
            width: 200px;
            display: block;
            background: #d9534f;
            position: absolute;
            top: 25px; right: -45px;
            box-shadow: 0 3px 10px -5px rgba(0, 0, 0, 1);
        }
        .ribbon.paid { background: #5cb85c; }
        @media print {
            .no-print { display: none !important; }
            body { background: white; }
            .print-container { box-shadow: none; max-width: 100%; margin: 0; padding: 0; }
        }
    </style>
</head>
<body class="bg-gray-100 py-10 font-sans text-gray-800">

    <div class="max-w-4xl mx-auto bg-white p-10 print-container relative shadow-lg relative border border-gray-200">

        <!-- PITA STATUS (UNPAID / LUNAS) -->
        <div class="ribbon-wrapper">
            <div class="ribbon {{ $statusLunas ? 'paid' : '' }}">
                {{ $statusLunas ? 'LUNAS' : 'UNPAID' }}
            </div>
        </div>

        <!-- HEADER INVOICE -->
        <div class="flex justify-between items-start mb-8">
            <div>
                <!-- Ganti link logo dengan logo Sancaka Express Anda -->
                <img src="https://tokosancaka.com/assets/sancaka.png" alt="Sancaka Express" class="h-16 mb-4 object-contain" onerror="this.src='https://placehold.co/200x50?text=Logo+Sancaka'">
            </div>
            <div class="text-right text-sm">
                <p class="font-bold text-lg">Sancaka Express</p>
                <p>Jl. Dr. Wahidin No. 18A</p>
                <p>Kabupaten Ngawi, Jawa Timur 63211</p>
                <p>Indonesia</p>
            </div>
        </div>

        <!-- INVOICE INFO BLOCK -->
        <div class="bg-gray-100 p-4 border border-gray-300 mb-8 text-sm">
            <h2 class="text-xl font-bold mb-2">Invoice #{{ $pesanan->nomor_invoice }}</h2>
            <p>Invoice Date: {{ \Carbon\Carbon::parse($pesanan->tanggal_pesanan)->format('d/m/Y') }}</p>
            <p>Due Date: {{ \Carbon\Carbon::parse($pesanan->tanggal_pesanan)->addDays(1)->format('d/m/Y') }}</p>
        </div>

        <!-- INVOICED TO -->
        <div class="mb-8 text-sm">
            <p class="font-bold mb-1">Invoiced To</p>
            <p class="font-bold uppercase">{{ $pesanan->sender_name }}</p>
            <p class="uppercase">{{ $pesanan->sender_address }}</p>
            <p class="uppercase">{{ $pesanan->sender_phone }}</p>
            <p>Indonesia</p>
        </div>

        <!-- RINCIAN TAGIHAN TABLE -->
        <table class="w-full text-sm border-collapse border border-gray-300 mb-8">
            <thead>
                <tr class="bg-gray-100">
                    <th class="border border-gray-300 px-4 py-2 text-left font-bold w-3/4">Description</th>
                    <th class="border border-gray-300 px-4 py-2 text-center font-bold w-1/4">Total</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="border border-gray-300 px-4 py-2">
                        Pengiriman Paket: {{ $pesanan->item_description }} <br>
                        <span class="text-gray-500">Penerima: {{ $pesanan->receiver_name }} ({{ $pesanan->weight }} gram)</span><br>
                        <span class="text-gray-500">Ekspedisi: {{ $pesanan->expedition }} - {{ strtoupper($pesanan->service_type) }}</span>
                    </td>
                    <td class="border border-gray-300 px-4 py-2 text-right align-top">
                        Rp {{ number_format($pesanan->shipping_cost, 0, ',', '.') }}
                    </td>
                </tr>

                @if($pesanan->insurance_cost > 0)
                <tr>
                    <td class="border border-gray-300 px-4 py-2">Biaya Asuransi Paket</td>
                    <td class="border border-gray-300 px-4 py-2 text-right">Rp {{ number_format($pesanan->insurance_cost, 0, ',', '.') }}</td>
                </tr>
                @endif

                @if($pesanan->cod_fee > 0)
                <tr>
                    <td class="border border-gray-300 px-4 py-2">Biaya Layanan (Fee)</td>
                    <td class="border border-gray-300 px-4 py-2 text-right">Rp {{ number_format($pesanan->cod_fee, 0, ',', '.') }}</td>
                </tr>
                @endif

                <!-- TOTALS SECTION -->
                <tr>
                    <td class="border border-gray-300 px-4 py-2 text-right font-bold bg-gray-50">Sub Total</td>
                    <td class="border border-gray-300 px-4 py-2 text-right font-bold bg-gray-50">Rp {{ number_format($pesanan->price, 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td class="border border-gray-300 px-4 py-2 text-right font-bold bg-gray-50">Credit</td>
                    <td class="border border-gray-300 px-4 py-2 text-right font-bold bg-gray-50">Rp 0,00</td>
                </tr>
                <tr>
                    <td class="border border-gray-300 px-4 py-2 text-right font-bold bg-gray-200">Total</td>
                    <td class="border border-gray-300 px-4 py-2 text-right font-bold bg-gray-200">Rp {{ number_format($pesanan->price, 0, ',', '.') }}</td>
                </tr>
            </tbody>
        </table>

        <!-- TRANSACTIONS SECTION -->
        <h3 class="font-bold text-lg mb-2">Transactions</h3>
        <table class="w-full text-sm border-collapse border border-gray-300 mb-8 text-center">
            <thead>
                <tr class="bg-gray-100">
                    <th class="border border-gray-300 px-4 py-2 font-bold">Transaction Date</th>
                    <th class="border border-gray-300 px-4 py-2 font-bold">Gateway</th>
                    <th class="border border-gray-300 px-4 py-2 font-bold">Transaction ID</th>
                    <th class="border border-gray-300 px-4 py-2 font-bold">Amount</th>
                </tr>
            </thead>
            <tbody>
                @if($statusLunas)
                <tr>
                    <td class="border border-gray-300 px-4 py-2">{{ \Carbon\Carbon::parse($pesanan->updated_at)->format('d/m/Y H:i') }}</td>
                    <td class="border border-gray-300 px-4 py-2">{{ $pesanan->payment_method }}</td>
                    <td class="border border-gray-300 px-4 py-2">{{ $pesanan->nomor_invoice }}</td>
                    <td class="border border-gray-300 px-4 py-2 text-right">Rp {{ number_format($pesanan->price, 0, ',', '.') }}</td>
                </tr>
                @else
                <tr>
                    <td colspan="4" class="border border-gray-300 px-4 py-2">No Related Transactions Found</td>
                </tr>
                @endif
                <tr>
                    <td colspan="3" class="border border-gray-300 px-4 py-2 text-right font-bold bg-gray-50">Balance</td>
                    <td class="border border-gray-300 px-4 py-2 text-right font-bold bg-gray-50">
                        Rp {{ $statusLunas ? '0,00' : number_format($pesanan->price, 0, ',', '.') }}
                    </td>
                </tr>
            </tbody>
        </table>

        <div class="text-center text-xs text-gray-500 mb-8">
            PDF Generated on {{ date('d/m/Y') }}
        </div>

        <!-- PAYMENT GATEWAY BUTTON (AKAN MUNCUL JIKA BELUM LUNAS) -->
        @if(!$statusLunas)
        <div class="no-print border-t border-gray-300 pt-6 text-center">
            <h4 class="text-lg font-bold mb-4">Silakan Lakukan Pembayaran</h4>

            @if($pesanan->payment_method == 'BCA_QRIS' && $pesanan->payment_url)
                <p class="mb-2 text-sm text-gray-600">Scan QR Code di bawah ini menggunakan M-Banking / E-Wallet Anda:</p>
                <div class="flex justify-center mb-4">
                    <!-- Generate QR from BCA content -->
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=250x250&data={{ urlencode($pesanan->payment_url) }}" alt="QRIS BCA" class="border p-2 rounded shadow">
                </div>
            @elseif($pesanan->payment_url)
                <p class="mb-4 text-sm text-gray-600">Anda memilih pembayaran menggunakan <strong>{{ $pesanan->payment_method }}</strong></p>
                <a href="{{ $pesanan->payment_url }}" class="inline-block bg-green-600 hover:bg-green-700 text-white font-bold py-3 px-8 rounded text-lg transition duration-300">
                    <i class="fas fa-credit-card mr-2"></i> Bayar Sekarang
                </a>
            @else
                <p class="text-red-500 italic">Menunggu konfirmasi admin untuk instruksi pembayaran.</p>
            @endif
        </div>
        @endif

        <!-- TOMBOL PRINT -->
        <div class="no-print absolute top-10 left-10">
            <button onclick="window.print()" class="bg-gray-800 text-white px-4 py-2 rounded text-sm hover:bg-gray-700">Print Invoice</button>
        </div>

    </div>

</body>
</html>
