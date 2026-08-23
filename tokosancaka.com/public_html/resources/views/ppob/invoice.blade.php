<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Struk Pembayaran - {{ $transaction->ref_id }}</title>
    <style>
        /* ========================================================= */
        /* 1. PENGATURAN KERTAS THERMAL (JANGAN DIUBAH)              */
        /* ========================================================= */
        @page { size: 10cm 15cm; margin: 0; }
        body { font-family: 'Courier New', Courier, monospace; width: 10cm; height: 15cm; margin: 0 auto; padding: 15px; box-sizing: border-box; background: #fff; color: #000; font-size: 12px; line-height: 1.4; }
        .header { text-align: center; border-bottom: 1px dashed #000; padding-bottom: 10px; margin-bottom: 10px; }
        .header h3 { margin: 0; font-size: 18px; font-weight: bold; text-transform: uppercase; }
        .header p { margin: 3px 0; font-size: 10px; }
        .content { margin-bottom: 10px; }
        .content-row { display: flex; justify-content: space-between; margin-bottom: 5px; }
        .content-row .label { font-weight: bold; flex: 0 0 35%; }
        .content-row .value { flex: 1; text-align: right; word-break: break-all; }
        .divider { border-top: 1px dashed #000; margin: 10px 0; }
        .total-row { font-size: 14px; font-weight: bold; border-top: 1px solid #000; padding-top: 5px; }
        .status-badge { font-weight: bold; text-transform: uppercase; }
        .footer { margin-top: 15px; text-align: center; border-top: 1px dashed #000; padding-top: 10px; font-size: 10px; }

        .action-buttons { display: flex; gap: 10px; margin-top: 15px; flex-wrap: wrap; }
        .btn-print, .btn-check { flex: 1; display: flex; align-items: center; justify-content: center; padding: 10px; text-align: center; text-decoration: none; border: none; border-radius: 5px; font-family: sans-serif; font-weight: bold; font-size: 12px; cursor: pointer; box-sizing: border-box; min-width: 130px; }
        .btn-print { background: #0d6efd; color: white; }
        .btn-print:hover { background: #0b5ed7; }
        .btn-check { background: #ffc107; color: #000; }
        .btn-check:hover { background: #e0a800; }
        .btn-back { display: block; text-align: center; margin-top: 15px; color: #6c757d; text-decoration: none; font-family: sans-serif; font-size: 12px; }

        /* Sembunyikan form dan tombol saat di-print */
        @media print { .no-print { display: none !important; } body { padding: 5px; } }

        /* ========================================================= */
        /* 2. CSS MODAL MEGA CHECKOUT (MIMIC TAILWIND)               */
        /* ========================================================= */
        .modal-overlay { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(17, 24, 39, 0.75); display: none; justify-content: center; align-items: center; z-index: 9999; font-family: sans-serif; padding: 20px; }
        .modal-overlay.active { display: flex; }
        .modal-content-large { background: #fff; width: 100%; max-width: 1000px; max-height: 90vh; border-radius: 12px; display: flex; flex-direction: column; overflow: hidden; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); animation: zoomIn 0.2s ease-out; }
        @keyframes zoomIn { from { transform: scale(0.95); opacity: 0; } to { transform: scale(1); opacity: 1; } }

        .modal-header { display: flex; justify-content: space-between; align-items: center; padding: 20px; border-bottom: 1px solid #e5e7eb; }
        .modal-header h3 { margin: 0; font-size: 18px; font-weight: bold; color: #111827; }
        .modal-close-btn { background: #f3f4f6; border: none; width: 32px; height: 32px; border-radius: 50%; font-size: 20px; cursor: pointer; color: #9ca3af; display: flex; align-items: center; justify-content: center; transition: 0.2s; }
        .modal-close-btn:hover { background: #fee2e2; color: #dc2626; }

        .modal-body { overflow-y: auto; padding: 0; background-color: #f9fafb; flex: 1; }
        .custom-scrollbar::-webkit-scrollbar { width: 6px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: #f1f1f1; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }

        .payment-grid { display: grid; grid-template-columns: 1fr; gap: 12px; padding: 20px; }
        @media (min-width: 768px) { .payment-grid { grid-template-columns: repeat(2, 1fr); } }
        @media (min-width: 1024px) { .payment-grid { grid-template-columns: repeat(3, 1fr); } }
        .col-span-full { grid-column: 1 / -1; }

        .group-title { font-size: 11px; font-weight: 800; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em; border-bottom: 1px solid #e5e7eb; padding-bottom: 4px; margin-top: 15px; margin-bottom: 5px; }

        .payment-card { display: flex; align-items: center; padding: 12px 15px; background: #fff; border: 1px solid #e5e7eb; border-radius: 8px; cursor: pointer; transition: all 0.2s ease; }
        .payment-card:hover { background-color: #fef2f2; border-color: #fca5a5; }
        .payment-card.active { background-color: #fef2f2; border-color: #ef4444; box-shadow: 0 0 0 1px #ef4444; }
        .payment-card img { width: 32px; height: 32px; object-fit: contain; margin-right: 15px; }
        .payment-card.grayscale img { filter: grayscale(100%); opacity: 0.5; }

        .payment-info { flex: 1; display: flex; flex-direction: column; justify-content: center; }
        .payment-title { font-size: 14px; font-weight: bold; color: #111827; line-height: 1.2; margin-bottom: 4px; }
        .payment-desc { font-size: 11px; color: #6b7280; line-height: 1.2; }

        .badge { background: #2563eb; color: white; font-size: 10px; font-weight: bold; padding: 2px 6px; border-radius: 4px; margin-left: auto; }
        .btn-link { background: #2563eb; color: white; font-size: 11px; font-weight: bold; padding: 4px 8px; border-radius: 4px; text-decoration: none; margin-left: auto; transition: 0.2s; }
        .btn-link:hover { background: #1d4ed8; }
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

    <!-- =================================================================== -->
    <!-- ELEMEN YANG TIDAK IKUT TERCETAK KE KERTAS (TOMBOL & FORM BAYAR)     -->
    <!-- =================================================================== -->
    <div class="no-print">

        <!-- KOTAK PERINGATAN PASCABAYAR PENDING -->
        @if($transaction->status === 'PENDING' && $transaction->type === 'pascabayar')
            <div style="margin-top: 20px; text-align: left; padding: 15px; background: #fffbeb; border: 1px solid #fde68a; border-radius: 8px; font-family: sans-serif;">
                <p style="font-size: 13px; font-weight: bold; color: #b45309; margin-bottom: 12px; text-align: center;">
                    ⚠️ Tagihan Belum Dibayar
                </p>
                <form action="{{ route('ppob.pay_postpaid') }}" method="POST" id="formBayarTagihan">
                    @csrf
                    <input type="hidden" name="tr_id" value="{{ $transaction->tr_id }}">
                    <input type="hidden" name="payment_method" id="payment_method" value="">

                    <label style="font-size: 12px; font-weight: bold; display: block; margin-bottom: 5px; color: #374151;">Pilih Metode Pembayaran</label>

                    <!-- BUTTON TRIGGER MODAL MEGA -->
                    <button type="button" id="paymentMethodButton" style="display:flex; justify-content:space-between; align-items:center; width:100%; padding:14px; border:1px solid #d1d5db; border-radius:8px; background:#f9fafb; cursor:pointer; margin-bottom:12px; transition: 0.2s;">
                        <div style="display:flex; align-items:center;">
                            <img id="paymentMethodImg" src="https://placehold.co/32x32/EFEFEF/AAAAAA?text=?" style="width:24px; height:24px; object-fit:contain; margin-right:10px;">
                            <span id="paymentMethodLabel" style="font-weight:bold; font-size:13px; color:#111827;">-- Pilih Pembayaran --</span>
                        </div>
                        <span style="color:#9ca3af; font-size:14px; font-weight:bold;">▼</span>
                    </button>

                    <!-- INPUT WA & PIN SALDO (TERSEMBUNYI DULU) -->
                    <div id="pascaSaldoFields" style="display: none; background: #fee2e2; padding: 12px; border-radius: 8px; border: 1px solid #fca5a5; margin-bottom: 12px;">
                        <label style="font-size: 11px; font-weight: bold; color: #991b1b; margin-bottom: 4px; display: block;">No. WhatsApp Akun (Pembayar)</label>
                        <input type="number" name="wa_pembayaran" id="wa_pembayaran" placeholder="Contoh: 0812..." style="width: 100%; padding: 10px; border: 1px solid #f87171; border-radius: 6px; margin-bottom: 10px; font-family:sans-serif; box-sizing:border-box; outline:none;">

                        <label style="font-size: 11px; font-weight: bold; color: #991b1b; margin-bottom: 4px; display: block;">PIN Keamanan Sancaka</label>
                        <input type="password" name="pin_pembayaran" id="pin_pembayaran" placeholder="******" style="width: 100%; padding: 10px; border: 1px solid #f87171; border-radius: 6px; font-family:sans-serif; box-sizing:border-box; outline:none;">
                    </div>

                    <button type="button" onclick="validateAndSubmitPasca()" style="width: 100%; padding: 14px; background: #2563eb; color: #fff; font-weight: bold; border-radius: 8px; border: none; cursor: pointer; font-family:sans-serif; font-size:14px; box-shadow: 0 4px 6px -1px rgba(37, 99, 235, 0.2);">
                        Bayar Sekarang (Rp {{ number_format($transaction->price, 0, ',', '.') }})
                    </button>
                </form>
            </div>
        @endif

        <div class="action-buttons">
            @if($transaction->status === 'PROCESS' || $transaction->status === 'PENDING')
                @if($transaction->type === 'prabayar')
                    <a href="{{ route('ppob.check_status_prepaid', $transaction->ref_id) }}" class="btn-check">Cek Status</a>
                @else
                    <a href="{{ route('ppob.check_status', $transaction->tr_id) }}" class="btn-check">Refresh Status</a>
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

    <!-- =================================================================== -->
    <!-- MODAL POPUP MEGA CHECKOUT (GRID 3 KOLOM)                            -->
    <!-- =================================================================== -->
    <div id="paymentModal" class="modal-overlay no-print">
        <div class="modal-content-large">
            <div class="modal-header">
                <h3>Pilih Metode Pembayaran</h3>
                <button class="modal-close-btn" id="closeModalButton">&times;</button>
            </div>

            <div class="modal-body custom-scrollbar">
                <div class="payment-grid">

                    <!-- 1. OPSI INTERNAL (SALDO) -->
                    @auth
                    <div class="payment-card col-span-full" data-value="SALDO" data-label="Saldo Sancaka" data-img="{{ asset('public/assets/saldo.png') }}">
                        <img src="{{ asset('public/assets/saldo.png') }}" onerror="this.src='https://placehold.co/32x32/EFEFEF/AAAAAA?text=Rp'">
                        <div class="payment-info">
                            <span class="payment-title">Saldo ADMIN SANCAKA</span>
                            <span class="payment-desc">Sisa Saldo: Rp{{ number_format(optional(Auth::user())->saldo ?? 0, 0, ',', '.') }}</span>
                        </div>
                    </div>
                    @endauth

                    <div class="payment-card col-span-full" data-value="DOKU_JOKUL" data-label="Doku (Kartu Kredit, E-Wallet, VA)" data-img="{{ asset('public/assets/doku.png') }}">
                        <img src="{{ asset('public/assets/doku.png') }}" onerror="this.src='https://placehold.co/32x32/EFEFEF/AAAAAA?text=DK'">
                        <div class="payment-info">
                            <span class="payment-title">Rekomendasi Sancaka (DOKU)</span>
                            <span class="payment-desc">Semua Pembayaran Tersedia</span>
                        </div>
                    </div>

                    <!-- 2. DANA ENTERPRISE -->
                    <div class="group-title col-span-full">DANA Enterprise</div>

                    @php
                        $user = Auth::user();
                        $userDanaToken = $user ? $user->dana_access_token : null;
                        $userDanaBalance = $user ? ($user->dana_user_balance ?? 0) : 0;
                        $hasDanaBinding = !empty($userDanaToken);
                    @endphp

                    <div class="payment-card" data-value="DANA" data-label="DANA (Web Checkout)" data-img="{{ asset('public/assets/dana.webp') }}">
                        <img src="{{ asset('public/assets/dana.webp') }}" onerror="this.src='https://upload.wikimedia.org/wikipedia/commons/7/72/Logo_dana_blue.svg'">
                        <div class="payment-info">
                            <span class="payment-title">DANA Checkout</span>
                            <span class="payment-desc">Diarahkan ke aplikasi DANA</span>
                        </div>
                    </div>

                    @if($hasDanaBinding)
                        <div class="payment-card" data-value="DANA_BINDING" data-label="DANA Auto-Debit" data-img="{{ asset('public/assets/dana.webp') }}" style="background: #eff6ff; border-color: #bfdbfe;">
                            <img src="{{ asset('public/assets/dana.webp') }}">
                            <div class="payment-info">
                                <span class="payment-title">DANA Auto-Debit</span>
                                <span class="payment-desc" style="color:#1d4ed8;">Saldo: Rp{{ number_format($userDanaBalance, 0, ',', '.') }}</span>
                            </div>
                            <span class="badge">Tersambung</span>
                        </div>
                    @else
                        <div class="payment-card grayscale" style="cursor: default;">
                            <img src="{{ asset('public/assets/dana.webp') }}">
                            <div class="payment-info">
                                <span class="payment-title" style="color: #9ca3af;">DANA Auto-Debit</span>
                                <span class="payment-desc">Bayar instan 1-klik</span>
                            </div>
                            <a href="{{ url('/dana/start-binding') }}" class="btn-link">Hubungkan</a>
                        </div>
                    @endif

                    <!-- 3. LAINNYA (PAYPAL DLL) -->
                    <div class="group-title col-span-full">Lainnya</div>
                    <div class="payment-card" data-value="PAYPAL" data-label="PayPal / Credit Card" data-img="https://tokosancaka.com/public/assets/paypal.png">
                        <img src="https://tokosancaka.com/public/assets/paypal.png" onerror="this.src='https://placehold.co/32x32/EFEFEF/AAAAAA?text=PP'">
                        <div class="payment-info">
                            <span class="payment-title">PayPal / Kartu Kredit</span>
                            <span class="payment-desc">Pembayaran Global (USD)</span>
                        </div>
                    </div>

                    <!-- 4. VIRTUAL ACCOUNT -->
                    <div class="group-title col-span-full">Virtual Account (Transfer Bank)</div>
                    <div class="payment-card" data-value="DOKU_BCA_VA" data-label="BCA Virtual Account" data-img="{{ asset('public/assets/bca.webp') }}">
                        <img src="{{ asset('public/assets/bca.webp') }}">
                        <div class="payment-info">
                            <span class="payment-title">BCA Virtual Account</span>
                            <span class="payment-desc">Diverifikasi Otomatis</span>
                        </div>
                    </div>
                    <div class="payment-card" data-value="DOKU_MANDIRI_VA" data-label="Mandiri Virtual Account" data-img="{{ asset('public/assets/mandiri.webp') }}">
                        <img src="{{ asset('public/assets/mandiri.webp') }}">
                        <div class="payment-info">
                            <span class="payment-title">Mandiri Virtual Account</span>
                            <span class="payment-desc">Diverifikasi Otomatis</span>
                        </div>
                    </div>
                    <div class="payment-card" data-value="DOKU_BRI_VA" data-label="BRI Virtual Account" data-img="{{ asset('public/assets/bri.webp') }}">
                        <img src="{{ asset('public/assets/bri.webp') }}">
                        <div class="payment-info">
                            <span class="payment-title">BRIVA</span>
                            <span class="payment-desc">Diverifikasi Otomatis</span>
                        </div>
                    </div>
                    <div class="payment-card" data-value="DOKU_BNI_VA" data-label="BNI Virtual Account" data-img="{{ asset('public/assets/bni.webp') }}">
                        <img src="{{ asset('public/assets/bni.webp') }}">
                        <div class="payment-info">
                            <span class="payment-title">BNI Virtual Account</span>
                            <span class="payment-desc">Diverifikasi Otomatis</span>
                        </div>
                    </div>
                    <div class="payment-card" data-value="DOKU_BSI_VA" data-label="BSI Virtual Account" data-img="{{ asset('public/assets/bsi.png') }}">
                        <img src="{{ asset('public/assets/bsi.png') }}">
                        <div class="payment-info">
                            <span class="payment-title">BSI Virtual Account</span>
                            <span class="payment-desc">Diverifikasi Otomatis</span>
                        </div>
                    </div>
                    <div class="payment-card" data-value="DOKU_PERMATA_VA" data-label="Permata Virtual Account" data-img="{{ asset('public/assets/permata.webp') }}">
                        <img src="{{ asset('public/assets/permata.webp') }}">
                        <div class="payment-info">
                            <span class="payment-title">Permata Virtual Account</span>
                            <span class="payment-desc">Diverifikasi Otomatis</span>
                        </div>
                    </div>
                    <div class="payment-card" data-value="DOKU_CIMB_VA" data-label="CIMB Niaga Virtual Account" data-img="{{ asset('public/assets/cimb.svg') }}">
                        <img src="{{ asset('public/assets/cimb.svg') }}">
                        <div class="payment-info">
                            <span class="payment-title">CIMB Niaga VA</span>
                            <span class="payment-desc">Diverifikasi Otomatis</span>
                        </div>
                    </div>
                    <div class="payment-card" data-value="DOKU_DANAMON_VA" data-label="Danamon Virtual Account" data-img="{{ asset('public/assets/danamon.png') }}">
                        <img src="{{ asset('public/assets/danamon.png') }}">
                        <div class="payment-info">
                            <span class="payment-title">Danamon Virtual Account</span>
                            <span class="payment-desc">Diverifikasi Otomatis</span>
                        </div>
                    </div>

                    <!-- 5. QRIS & MINIMARKET -->
                    <div class="group-title col-span-full">Scan QRIS & Minimarket</div>
                    <div class="payment-card" data-value="DOKU_QRIS" data-label="QRIS (Gopay, OVO, Dana, LinkAja)" data-img="{{ asset('public/assets/qris.png') }}">
                        <img src="{{ asset('public/assets/qris.png') }}">
                        <div class="payment-info">
                            <span class="payment-title">QRIS (E-Wallet & Bank)</span>
                            <span class="payment-desc">Scan kode barcode di Invoice</span>
                        </div>
                    </div>
                    <div class="payment-card" data-value="DOKU_ALFAMART" data-label="Alfamart / Alfamidi" data-img="{{ asset('public/assets/alfamart.webp') }}">
                        <img src="{{ asset('public/assets/alfamart.webp') }}">
                        <div class="payment-info">
                            <span class="payment-title">Alfamart / Alfamidi</span>
                            <span class="payment-desc">Tunjukkan kode bayar ke kasir</span>
                        </div>
                    </div>

                    <!-- 6. E-WALLET -->
                    <div class="group-title col-span-full">E-Wallet & Kartu Kredit</div>
                    <div class="payment-card" data-value="DOKU_SHOPEEPAY" data-label="ShopeePay" data-img="{{ asset('public/assets/shopeepay.webp') }}">
                        <img src="{{ asset('public/assets/shopeepay.webp') }}">
                        <div class="payment-info">
                            <span class="payment-title">ShopeePay</span>
                            <span class="payment-desc">Akan diarahkan ke aplikasi Shopee</span>
                        </div>
                    </div>
                    <div class="payment-card" data-value="DOKU_OVO" data-label="OVO" data-img="{{ asset('public/assets/ovo.webp') }}">
                        <img src="{{ asset('public/assets/ovo.webp') }}">
                        <div class="payment-info">
                            <span class="payment-title">OVO</span>
                            <span class="payment-desc">Akan diarahkan ke aplikasi OVO</span>
                        </div>
                    </div>
                    <div class="payment-card" data-value="DOKU_CREDIT_CARD" data-label="Kartu Kredit / Debit Online" data-img="{{ asset('public/assets/card.png') }}">
                        <img src="{{ asset('public/assets/card.png') }}">
                        <div class="payment-info">
                            <span class="payment-title">Kartu Kredit / Debit</span>
                            <span class="payment-desc">Pembayaran aman dengan 3D Secure</span>
                        </div>
                    </div>

                    <!-- 7. TRIPAY DINAMIS (Jika Disediakan dari Controller) -->
                    @if(isset($tripayChannels) && count($tripayChannels) > 0)
                        <div class="group-title col-span-full">Metode Pembayaran Otomatis (Tripay)</div>
                        @foreach($tripayChannels as $channel)
                            <div class="payment-card" data-value="{{ $channel['code'] }}" data-label="{{ $channel['name'] }}" data-img="{{ $channel['icon_url'] }}">
                                <img src="{{ $channel['icon_url'] }}" onerror="this.src='https://placehold.co/32x32?text=IMG'">
                                <div class="payment-info">
                                    <span class="payment-title">{{ $channel['name'] }}</span>
                                    <span class="payment-desc">Otomatis / Realtime</span>
                                </div>
                            </div>
                        @endforeach
                    @endif

                </div>
            </div>
        </div>
    </div>

    <!-- SCRIPT LOGIKA MODAL (VANILLA JS) -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const paymentModal = document.getElementById('paymentModal');
            const paymentMethodButton = document.getElementById('paymentMethodButton');
            const closeModalButton = document.getElementById('closeModalButton');
            const paymentMethodInput = document.getElementById('payment_method');
            const paymentOptions = document.querySelectorAll('.payment-card:not(.grayscale)'); // Hanya opsi aktif

            if(paymentMethodButton) {
                // Buka Modal
                paymentMethodButton.addEventListener('click', () => {
                    paymentModal.classList.add('active');
                });

                // Tutup Modal
                closeModalButton.addEventListener('click', () => {
                    paymentModal.classList.remove('active');
                });

                // Tutup saat klik di luar area konten
                paymentModal.addEventListener('click', (e) => {
                    if (e.target === paymentModal) {
                        paymentModal.classList.remove('active');
                    }
                });

                // Logika Klik Opsi
                paymentOptions.forEach(option => {
                    option.addEventListener('click', function() {
                        // Reset kelas aktif
                        paymentOptions.forEach(opt => opt.classList.remove('active'));

                        // Set aktif ke opsi yang diklik
                        this.classList.add('active');

                        // Update Value ke Form tersembunyi dan UI Trigger Button
                        const val = this.dataset.value;
                        const label = this.dataset.label;
                        const img = this.dataset.img;

                        paymentMethodInput.value = val;
                        document.getElementById('paymentMethodLabel').innerText = label;
                        document.getElementById('paymentMethodLabel').style.color = '#dc2626'; // Merah menandakan sudah pilih
                        document.getElementById('paymentMethodImg').src = img;

                        // Toggle Form Saldo (WA & PIN)
                        const fields = document.getElementById('pascaSaldoFields');
                        const wa = document.getElementById('wa_pembayaran');
                        const pin = document.getElementById('pin_pembayaran');

                        if(val === 'SALDO') {
                            fields.style.display = 'block';
                            wa.required = true;
                            pin.required = true;
                        } else {
                            fields.style.display = 'none';
                            wa.required = false;
                            pin.required = false;
                        }

                        // Tutup otomatis modal
                        paymentModal.classList.remove('active');
                    });
                });
            }
        });

        // Validasi dan Submit
        function validateAndSubmitPasca() {
            const paymentMethodInput = document.getElementById('payment_method');

            if(!paymentMethodInput.value) {
                alert('Silakan pilih metode pembayaran terlebih dahulu dengan mengeklik tombol "Pilih Pembayaran".');
                return;
            }

            // Ganti teks tombol dan nonaktifkan biar tidak diklik dobel
            const btn = event.target;
            btn.innerText = "Memproses Pembayaran...";
            btn.style.opacity = "0.7";
            btn.disabled = true;

            document.getElementById('formBayarTagihan').submit();
        }
    </script>
</body>
</html>
