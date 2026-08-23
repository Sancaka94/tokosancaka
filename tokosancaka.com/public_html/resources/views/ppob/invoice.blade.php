<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Struk Pembayaran - {{ $transaction->ref_id }}</title>
    <style>
        /* Pengaturan ukuran kertas thermal wajib 10x15cm */
        @page {
            size: 10cm 15cm;
            margin: 0;
        }

        body {
            font-family: 'Courier New', Courier, monospace;
            width: 10cm;
            height: 15cm;
            margin: 0 auto;
            padding: 15px;
            box-sizing: border-box;
            background: #fff;
            color: #000;
            font-size: 12px;
            line-height: 1.4;
        }

        .header {
            text-align: center;
            border-bottom: 1px dashed #000;
            padding-bottom: 10px;
            margin-bottom: 10px;
        }

        .header h3 {
            margin: 0;
            font-size: 18px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .header p {
            margin: 3px 0;
            font-size: 10px;
        }

        .content {
            margin-bottom: 10px;
        }

        .content-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
        }

        .content-row .label {
            font-weight: bold;
            flex: 0 0 35%; /* Proporsi lebar label */
        }

        .content-row .value {
            flex: 1;
            text-align: right;
            word-break: break-all; /* Agar teks panjang tidak merusak layout */
        }

        .divider {
            border-top: 1px dashed #000;
            margin: 10px 0;
        }

        .total-row {
            font-size: 14px;
            font-weight: bold;
            border-top: 1px solid #000;
            padding-top: 5px;
        }

        .status-badge {
            font-weight: bold;
            text-transform: uppercase;
        }

        .footer {
            margin-top: 15px;
            text-align: center;
            border-top: 1px dashed #000;
            padding-top: 10px;
            font-size: 10px;
        }

        /* PERBAIKAN: Kontainer untuk tombol agar sejajar/responsive */
        .action-buttons {
            display: flex;
            gap: 10px; /* Jarak antar tombol */
            margin-top: 15px;
            flex-wrap: wrap;
        }

        .btn-print, .btn-check {
            flex: 1; /* Akan membagi ruang sama rata, jika 1 tombol maka full width */
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 10px;
            text-align: center;
            text-decoration: none;
            border: none;
            border-radius: 5px;
            font-family: sans-serif;
            font-weight: bold;
            font-size: 12px;
            cursor: pointer;
            box-sizing: border-box;
            min-width: 130px; /* Memastikan tombol tidak terlalu sempit di layar kecil */
        }

        .btn-print {
            background: #0d6efd;
            color: white;
        }

        .btn-print:hover {
            background: #0b5ed7;
        }

        .btn-check {
            background: #ffc107; /* Warna kuning peringatan */
            color: #000;
        }

        .btn-check:hover {
            background: #e0a800;
        }

        .btn-back {
            display: block;
            text-align: center;
            margin-top: 15px;
            color: #6c757d;
            text-decoration: none;
            font-family: sans-serif;
            font-size: 12px;
        }

        /* Elemen yang disembunyikan saat proses cetak berlangsung */
        @media print {
            .no-print {
                display: none !important;
            }
            body {
                padding: 5px; /* Kurangi padding saat dicetak agar ruang lebih lega */
            }
        }
    </style>
</head>
<body>

    <div class="header">
        <h3>SANCAKA STORE</h3>
        <p>Struk Pembelian & Pembayaran</p>
        <p>{{ \Carbon\Carbon::parse($transaction->created_at)->format('d/m/Y H:i:s') }}</p>
    </div>

    <div class="content">
        <div class="content-row">
            <span class="label">Ref ID</span>
            <span class="value">{{ $transaction->ref_id }}</span>
        </div>
        <div class="content-row">
            <span class="label">Tipe</span>
            <span class="value" style="text-transform: capitalize;">{{ $transaction->type }}</span>
        </div>
        <div class="content-row">
            <span class="label">Tujuan</span>
            <span class="value">{{ $transaction->customer_id }}</span>
        </div>
        <div class="content-row">
            <span class="label">Produk</span>
            <span class="value">{{ $transaction->product_code }}</span>
        </div>

        <div class="divider"></div>

        @if($transaction->sn)
        @php
            $snRaw = $transaction->sn;
            $isPln = str_contains(strtoupper($transaction->product_code), 'PLN') || str_contains(strtoupper($transaction->product_code), 'TOKEN');
            $snParts = explode('/', $snRaw);
        @endphp

        @if($isPln && count($snParts) > 1)
            <div class="content-row" style="flex-direction: column; text-align: center; padding: 5px 0;">
                <span style="font-size: 12px; font-weight: bold; margin-bottom: 5px;">TOKEN LISTRIK</span>
                <span style="font-size: 18px; font-weight: bold; letter-spacing: 2px; margin-bottom: 8px;">{{ trim($snParts[0]) }}</span>
                <span style="font-size: 10px;">Nama: {{ trim($snParts[1] ?? '-') }}</span>
                <span style="font-size: 10px;">Daya: {{ trim($snParts[2] ?? '-') }}</span>
                <span style="font-size: 10px;">KWH: {{ trim($snParts[4] ?? '-') }}</span>
            </div>
            <div class="divider"></div>
        @elseif(count($snParts) > 1)
            <div class="content-row" style="flex-direction: column;">
                <span class="label" style="margin-bottom: 5px; width: 100%;">Detail SN / Voucher:</span>
                @foreach($snParts as $part)
                    <span class="value" style="text-align: left; font-size: 11px; margin-bottom: 2px; font-weight: bold;">{{ trim($part) }}</span>
                @endforeach
            </div>
            <div class="divider"></div>
        @else
            <div class="content-row">
                <span class="label">SN / Ref</span>
                <span class="value" style="font-weight: bold; font-size: 13px;">{{ $snRaw }}</span>
            </div>
            <div class="divider"></div>
        @endif
        @endif

        <div class="content-row">
            <span class="label">Status</span>
            <span class="value status-badge">{{ $transaction->status }}</span>
        </div>

        <div class="content-row" style="font-size: 10px; color: #333;">
            <span class="label">Ket:</span>
            <span class="value" style="text-align: left;">{{ $transaction->message }}</span>
        </div>

        <div class="content-row total-row">
            <span class="label">Total Bayar</span>
            <span class="value">Rp {{ number_format($transaction->price, 0, ',', '.') }}</span>
        </div>
    </div>

    <div class="footer">
        <p>Terima kasih atas kepercayaan Anda</p>
        <p>Simpan struk ini sebagai bukti pembayaran yang sah</p>
        <p><i>* Layanan didukung oleh Sancaka Express</i></p>
    </div>

    <!-- ELEMEN YANG TIDAK IKUT TERCETAK KE KERTAS (TOMBOL & FORM BAYAR) -->
    <div class="no-print">

        <!-- ========================================== -->
        <!-- TOMBOL BAYAR KHUSUS PASCABAYAR PENDING     -->
        <!-- ========================================== -->
        @if($transaction->status === 'PENDING' && $transaction->type === 'pascabayar')
            <div style="margin-top: 20px; text-align: left; padding: 15px; background: #fffbeb; border: 1px solid #fde68a; border-radius: 8px;">
                <p style="font-size: 13px; font-weight: bold; color: #b45309; margin-bottom: 12px; text-align: center;">
                    ⚠️ Tagihan Belum Dibayar
                </p>
                <form action="{{ route('ppob.pay_postpaid') }}" method="POST">
                    @csrf
                    <!-- Lempar ID Transaksi ke Controller -->
                    <input type="hidden" name="tr_id" value="{{ $transaction->tr_id }}">

                    <label style="font-size: 12px; font-weight: bold; display: block; margin-bottom: 5px;">Metode Pembayaran:</label>
                    <select name="payment_method" id="payMethodPasca" onchange="togglePascaFields()" style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 5px; margin-bottom: 10px;" required>
                        <option value="" disabled selected>-- Pilih Metode --</option>
                        <option value="SALDO">💰 Potong Saldo</option>
                        <option value="DANA">🔵 DANA Otomatis</option>
                        <option value="DOKU">🛡️ DOKU Gateway</option>
                    </select>

                    <!-- Form Input WA & PIN (Hanya Muncul Jika Pilih Saldo) -->
                    <div id="pascaSaldoFields" style="display: none;">
                        <input type="number" name="wa_pembayaran" id="wa_pembayaran" placeholder="Nomor WA Akun" style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 5px; margin-bottom: 10px;">
                        <input type="password" name="pin_pembayaran" id="pin_pembayaran" placeholder="PIN Keamanan" style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 5px; margin-bottom: 10px;">
                    </div>

                    <button type="submit" style="width: 100%; padding: 12px; background: #16a34a; color: #fff; font-weight: bold; border-radius: 5px; border: none; cursor: pointer; margin-bottom: 10px;">
                        💸 Bayar Sekarang (Rp {{ number_format($transaction->price, 0, ',', '.') }})
                    </button>
                </form>
            </div>

            <script>
                function togglePascaFields() {
                    var method = document.getElementById('payMethodPasca').value;
                    var fields = document.getElementById('pascaSaldoFields');
                    var wa = document.getElementById('wa_pembayaran');
                    var pin = document.getElementById('pin_pembayaran');

                    if(method === 'SALDO') {
                        fields.style.display = 'block';
                        wa.required = true;
                        pin.required = true;
                    } else {
                        fields.style.display = 'none';
                        wa.required = false;
                        pin.required = false;
                    }
                }
            </script>
        @endif
        <!-- ========================================== -->

        {{-- Kontainer Flexbox untuk tombol --}}
        <div class="action-buttons">
            @if($transaction->status === 'PROCESS' || $transaction->status === 'PENDING')
                @if($transaction->type === 'prabayar')
                    <a href="{{ route('ppob.check_status_prepaid', $transaction->ref_id) }}" class="btn-check">
                        Cek Status
                    </a>
                @else
                    <a href="{{ route('ppob.check_status', $transaction->tr_id) }}" class="btn-check">
                        Refresh Status
                    </a>
                @endif
            @endif

            <button class="btn-print" onclick="window.print()">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" style="vertical-align: middle; margin-right: 5px;">
                    <path d="M5 1a2 2 0 0 0-2 2v1h10V3a2 2 0 0 0-2-2H5zm6 8H5a1 1 0 0 0-1 1v3a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1v-3a1 1 0 0 0-1-1z"/>
                    <path d="M0 7a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v3a2 2 0 0 1-2 2h-1v-2a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v2H2a2 2 0 0 1-2-2V7zm2.5 1a.5.5 0 1 0 0-1 .5.5 0 0 0 0 1z"/>
                </svg>
                Cetak Struk
            </button>
        </div>

        <a href="{{ route('ppob.index') }}" class="btn-back">Kembali ke Halaman Transaksi</a>
    </div>

</body>
</html>
