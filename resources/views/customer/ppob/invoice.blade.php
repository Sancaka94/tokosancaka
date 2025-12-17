<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Invoice #{{ $transaction->order_id }}</title>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">

    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <script src="https://html2canvas.hertzen.com/dist/html2canvas.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        body {
            font-family: 'Roboto', sans-serif;
            background-color: #f2f2f2;
            margin: 0;
            padding: 0;
            color: #333;
        }

        /* --- TOOLBAR AKSI (TIDAK IKUT DI PRINT) --- */
        .action-bar {
            background-color: #1a2a47;
            padding: 15px 0;
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: 0 4px 10px rgba(0,0,0,0.2);
            margin-bottom: 30px;
        }
        .action-container {
            max-width: 800px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 20px;
        }
        .btn-group {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        .btn {
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            transition: transform 0.2s, opacity 0.2s;
        }
        .btn:hover { transform: translateY(-2px); opacity: 0.9; }
        .btn:active { transform: translateY(0); }
        
        .btn-back { background-color: #6c757d; color: white; }
        .btn-jpg { background-color: #ffc107; color: #1a2a47; }
        .btn-pdf { background-color: #dc3545; color: white; }
        .btn-wa { background-color: #25D366; color: white; }

        /* --- INVOICE CONTAINER --- */
        .invoice-wrapper {
            max-width: 800px;
            margin: 0 auto 50px auto;
            background: white;
            /* Shadow dihapus saat capture agar bersih */
            box-shadow: 0 0 20px rgba(0,0,0,0.05); 
        }

        /* --- DESAIN INVOICE KUNING/BIRU (COMPANY STYLE) --- */
        .invoice-content {
            position: relative;
            background: white;
            overflow: hidden;
            min-height: 1000px; /* Tinggi A4 proporsional */
        }

        /* Header Lengkung */
        .header-bg {
            background-color: #1a2a47;
            color: white;
            padding: 40px 50px 80px 50px;
            position: relative;
        }
        .header-bg::after {
            content: '';
            position: absolute;
            bottom: -50px;
            left: 0;
            width: 100%;
            height: 100px;
            background-color: #1a2a47;
            border-radius: 0 0 50% 50% / 0 0 100% 100%; /* Lengkungan */
            z-index: 1;
        }
        .yellow-accent {
            position: absolute;
            bottom: -60px; /* Posisi di bawah biru */
            left: 0;
            width: 100%;
            height: 120px;
            background-color: #f8b300;
            border-radius: 0 0 50% 50% / 0 0 100% 100%;
            z-index: 0;
        }

        .top-nav {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            position: relative;
            z-index: 2;
        }
        .logo-area h1 { margin: 0; font-size: 24px; font-weight: 700; letter-spacing: 1px; }
        .logo-area p { margin: 5px 0 0; font-size: 12px; opacity: 0.8; }
        
        .contact-list { text-align: right; font-size: 12px; line-height: 1.6; }
        .contact-list i { margin-right: 8px; color: #f8b300; }

        /* Judul Invoice */
        .invoice-title {
            text-align: center;
            margin-top: 60px;
            position: relative;
            z-index: 2;
        }
        .invoice-title h2 {
            font-size: 36px;
            color: #f8b300;
            margin: 0;
            text-transform: uppercase;
            font-weight: 800;
            letter-spacing: 2px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.1);
        }

        /* Info Section */
        .info-section {
            display: flex;
            justify-content: space-between;
            padding: 40px 50px;
            margin-top: 20px;
        }
        .info-box h3 {
            font-size: 14px;
            color: #999;
            text-transform: uppercase;
            margin: 0 0 10px 0;
        }
        .info-box p { margin: 0; font-weight: bold; color: #333; font-size: 16px; }
        .info-box .sub { font-weight: normal; font-size: 14px; color: #555; }

        /* Table */
        .table-container { padding: 0 50px; }
        table { width: 100%; border-collapse: collapse; }
        thead { background-color: #1a2a47; color: white; }
        th { padding: 15px; text-align: left; font-size: 12px; text-transform: uppercase; }
        td { padding: 15px; border-bottom: 1px solid #eee; font-size: 14px; color: #555; }
        tr:nth-child(even) { background-color: #f9f9f9; }
        
        /* Total Section */
        .total-section {
            display: flex;
            justify-content: flex-end;
            padding: 30px 50px;
        }
        .total-box { width: 40%; }
        .total-row { display: flex; justify-content: space-between; margin-bottom: 10px; font-size: 14px; color: #555; }
        .grand-total {
            background-color: #f8b300;
            color: #1a2a47;
            padding: 15px;
            display: flex;
            justify-content: space-between;
            font-weight: bold;
            font-size: 18px;
            border-radius: 4px;
            margin-top: 10px;
        }

        /* Footer */
        .footer-invoice {
            background-color: #1a2a47;
            color: white;
            padding: 30px 50px;
            margin-top: 50px;
            position: absolute;
            bottom: 0;
            width: 100%;
            box-sizing: border-box;
        }
        .footer-invoice h4 { margin: 0 0 5px 0; font-size: 14px; color: #f8b300; }
        .footer-invoice p { margin: 0; font-size: 12px; opacity: 0.7; }
        .footer-total-float {
            float: right;
            font-size: 20px;
            font-weight: bold;
            color: #f8b300;
        }

        /* Helper Utility */
        .sn-badge {
            background: #eee;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: monospace;
            border: 1px solid #ddd;
        }

        /* PRINT MEDIA QUERY */
        @media print {
            .action-bar { display: none !important; }
            .invoice-wrapper { box-shadow: none; margin: 0; width: 100%; max-width: 100%; }
            body { background: white; -webkit-print-color-adjust: exact; }
            @page { margin: 0; size: A4; }
        }
    </style>
</head>
<body>

    <div class="action-bar">
        <div class="action-container">
            <a href="https://tokosancaka.com/customer/ppob/history" class="btn btn-back">
                <i class="fas fa-arrow-left"></i> Kembali
            </a>

            <div class="btn-group">
                <button onclick="sendWhatsapp()" class="btn btn-wa">
                    <i class="fab fa-whatsapp"></i> Kirim ke HP
                </button>

                <button onclick="downloadJPG()" class="btn btn-jpg">
                    <i class="fas fa-image"></i> Download JPG
                </button>

                <button onclick="downloadPDF()" class="btn btn-pdf">
                    <i class="fas fa-file-pdf"></i> Download PDF
                </button>
            </div>
        </div>
    </div>

    <div class="invoice-wrapper" id="invoice-area">
        <div class="invoice-content">
            
            <div class="yellow-accent"></div>

            <div class="header-bg">
                <div class="top-nav">
                    <div class="logo-area">
                        <h1>SANCAKA STORE</h1>
                        <p>Pusat Belanja Online No. 1 di Indonesia. </p>
                        <p>Belanja lebih hemat, aman, dan cepat. Dijamin!</p>
                    </div>
                    <div class="contact-list">
                        <div><i class="fas fa-phone"></i> +62 881-9435-180 +6285 745 808 809</div>
                        <div><i class="fas fa-globe"></i> www.tokosancaka.com</div>
                        <div><i class="fas fa-envelope"></i> admin@tokosancaka.com</div>
                    </div>
                </div>
            </div>

            <div class="invoice-title">
                <h2>INVOICE</h2>
            </div>

            <div class="info-section">
                <div class="info-box">
                    <h3>Invoice To:</h3>
                    <p>{{ $transaction->customer_no }}</p>
                    <div class="sub">
                        {{ $transaction->desc['detail'][0]['nama_pelanggan'] ?? 'Pelanggan Setia' }}<br>
                        Indonesia
                    </div>
                </div>
                <div class="info-box" style="text-align: right;">
                    <h3>Detail:</h3>
                    <div class="total-row">
                        <span style="margin-right: 15px;">Invoice No:</span>
                        <strong>{{ $transaction->order_id }}</strong>
                    </div>
                    <div class="total-row">
                        <span style="margin-right: 15px;">Date:</span>
                        <strong>{{ $transaction->created_at->format('d/m/Y') }}</strong>
                    </div>
                    <div class="total-row">
                        <span style="margin-right: 15px;">Status:</span>
                        <strong style="color: #00b843;">{{ strtoupper($transaction->status) }}</strong>
                    </div>
                </div>
            </div>

            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th width="10%">NO.</th>
                            <th width="50%">ITEM DESCRIPTION</th>
                            <th width="15%">PRICE</th>
                            <th width="10%">QTY</th>
                            <th width="15%" style="text-align: right;">TOTAL</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>01.</td>
                            <td>
                                <strong>{{ strtoupper($transaction->buyer_sku_code) }}</strong><br>
                                <span style="font-size: 12px; color: #888;">Metode Pembayaran: {{ str_replace('_', ' ', $transaction->payment_method) }}</span>
                                
                                @if($transaction->sn)
                                    @php
                                        // Pecah string SN berdasarkan tanda '/'
                                        $parts = explode('/', $transaction->sn);
                                        // Cek apakah formatnya PLN (memiliki lebih dari 1 bagian)
                                        $isPln = count($parts) > 1; 
                                    @endphp

                                    @if($isPln)
                                        <div style="margin-top: 10px; padding: 15px; background-color: #f0fdf4; border: 1px dashed #16a34a; border-radius: 6px;">
                                            
                                            <div style="font-size: 10px; color: #666; text-transform: uppercase; margin-bottom: 4px;">
                                                Token Listrik:
                                            </div>
                                            
                                            <div style="font-family: 'Courier New', monospace; font-size: 18px; font-weight: 800; color: #16a34a; letter-spacing: 1px; margin-bottom: 12px;">
                                                {{ $parts[0] }}
                                            </div>

                                            <div style="border-top: 1px dashed #cbd5e1; margin-bottom: 12px;"></div>

                                            <div style="font-size: 12px; color: #334155;">
                                                
                                                @if(isset($parts[1]))
                                                <div style="display: flex; margin-bottom: 8px;">
                                                    <span style="width: 50px; color: #64748b;">Nama</span>
                                                    <span style="margin-right: 8px;">:</span>
                                                    <strong style="flex: 1;">{{ $parts[1] }}</strong>
                                                </div>
                                                @endif

                                                @if(isset($parts[2]))
                                                <div style="display: flex; margin-bottom: 8px;">
                                                    <span style="width: 50px; color: #64748b;">Tarif</span>
                                                    <span style="margin-right: 8px;">:</span>
                                                    <span style="flex: 1;">{{ $parts[2] }}</span>
                                                </div>
                                                @endif

                                                @if(isset($parts[3]))
                                                <div style="display: flex; margin-bottom: 8px;">
                                                    <span style="width: 50px; color: #64748b;">Daya</span>
                                                    <span style="margin-right: 8px;">:</span>
                                                    <span style="flex: 1;">{{ $parts[3] }}</span>
                                                </div>
                                                @endif

                                                @if(isset($parts[4]))
                                                <div style="display: flex; margin-bottom: 8px;">
                                                    <span style="width: 50px; color: #64748b;">KWH</span>
                                                    <span style="margin-right: 8px;">:</span>
                                                    <span style="flex: 1;">{{ $parts[4] }}</span>
                                                </div>
                                                @endif

                                            </div>
                                        </div>
                                    @else
                                        <br><br>
                                        <span style="color:#16a34a; font-weight:600; font-family: monospace; background: #f0fdf4; padding: 6px 10px; border-radius: 4px; border: 1px solid #dcfce7;">
                                            SN: {{ $transaction->sn }}
                                        </span>
                                    @endif
                                @endif
                            </td>
                            <td>Rp {{ number_format($transaction->selling_price, 0, ',', '.') }}</td>
                            <td>1</td>
                            <td style="text-align: right;">Rp {{ number_format($transaction->selling_price, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td>02.</td>
                            <td>Biaya Layanan / Admin</td>
                            <td>Rp 0</td>
                            <td>1</td>
                            <td style="text-align: right;">Rp 0</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="total-section">
                <div class="total-box">
                    <div class="total-row">
                        <span>SUB TOTAL:</span>
                        <span>Rp {{ number_format($transaction->selling_price, 0, ',', '.') }}</span>
                    </div>
                    
                    <div class="total-row">
                        <span>TAX (0%):</span>
                        <span>0.00%</span>
                    </div>
                    
                    <div class="grand-total">
                        <span>TOTAL:</span>
                        <span>Rp {{ number_format($transaction->selling_price, 0, ',', '.') }}</span>
                    </div>
                </div>
            </div>

            <div class="footer-invoice">
                <div class="footer-total-float">
                    Rp {{ number_format($transaction->selling_price, 0, ',', '.') }}
                </div>
                <h4>THANK YOU FOR YOUR BUSINESS</h4>
                <p>CV. SANCAKA KARYA HUTAMA -  SANCAKA EXPRESS</p>
                <div style="margin-top: 10px; font-size: 11px;">
                    Payment Info: {{ $transaction->order_id }} | Acc Name: Sancaka Store
                </div>
            </div>

        </div>
    </div>

    <script>
        // 1. Fungsi Download PDF
        function downloadPDF() {
            const element = document.getElementById('invoice-area');
            const opt = {
                margin:       0,
                filename:     'Invoice-{{ $transaction->order_id }}.pdf',
                image:        { type: 'jpeg', quality: 0.98 },
                html2canvas:  { scale: 2, useCORS: true },
                jsPDF:        { unit: 'mm', format: 'a4', orientation: 'portrait' }
            };
            
            // Tampilkan loading swal
            Swal.fire({
                title: 'Memproses PDF...',
                didOpen: () => { Swal.showLoading() }
            });

            html2pdf().set(opt).from(element).save().then(() => {
                Swal.close();
            });
        }

        // 2. Fungsi Download JPG
        function downloadJPG() {
            const element = document.getElementById('invoice-area');
            
            Swal.fire({
                title: 'Memproses JPG...',
                didOpen: () => { Swal.showLoading() }
            });

            html2canvas(element, { scale: 2, useCORS: true }).then(canvas => {
                const link = document.createElement('a');
                link.download = 'Invoice-{{ $transaction->order_id }}.jpg';
                link.href = canvas.toDataURL('image/jpeg', 0.9);
                link.click();
                Swal.close();
            });
        }

        // 3. Fungsi Kirim WA via Fonnte (AJAX)
        function sendWhatsapp() {
            const transactionId = "{{ $transaction->order_id }}";
            const customerPhone = "{{ $transaction->customer_wa ?? $transaction->customer_no }}"; // Prioritas WA, fallback No HP Pelanggan
            
            Swal.fire({
                title: 'Kirim Invoice ke WA?',
                text: "Invoice akan dikirim ke nomor: " + customerPhone,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#25D366',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Ya, Kirim Sekarang!'
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'Mengirim...',
                        didOpen: () => { Swal.showLoading() }
                    });

                    // AJAX Request ke Backend Laravel
                    // PENTING: Anda harus membuat Route & Controller untuk ini
                    // Contoh Route: Route::post('/transaction/resend-wa', [Controller::class, 'resendWa']);
                    
                    fetch('/transaction/resend-wa', { 
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify({ 
                            order_id: transactionId,
                            phone: customerPhone 
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if(data.status === 'success' || data.status === true) {
                            Swal.fire('Terikirim!', 'Invoice berhasil dikirim ke WhatsApp.', 'success');
                        } else {
                            Swal.fire('Gagal!', 'Gagal mengirim pesan: ' + (data.message || 'Error'), 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        // Mockup Success jika backend belum siap (Hapus bagian ini di production)
                        // Swal.fire('Info', 'Backend belum dikonfigurasi. Cek Console.', 'info');
                        
                        // Default Error
                        Swal.fire('Error!', 'Terjadi kesalahan jaringan atau server.', 'error');
                    });
                }
            })
        }
    </script>
</body>
</html>