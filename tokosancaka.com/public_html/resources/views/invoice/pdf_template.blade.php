<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice #{{ $invoice->invoice_no }}</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    @php
        // 1. LOGIKA STATUS
        $isLunas = (isset($invoice->sisa_tagihan) && $invoice->sisa_tagihan <= 0) || (isset($invoice->grand_total) && $invoice->grand_total <= 0);
        $statusText = $isLunas ? 'LUNAS' : 'BELUM LUNAS';
        $isCancelled = false; // Ganti dengan logika batal jika ada

        // 2. FUNGSI SENSOR NAMA (A*** I*** M**********)
        $maskName = function($name) {
            if (empty($name)) return '';
            $words = explode(' ', $name);
            foreach ($words as &$word) {
                if (strlen($word) > 1) {
                    $word = substr($word, 0, 1) . str_repeat('*', strlen($word) - 1);
                }
            }
            return implode(' ', $words);
        };

        // 3. FUNGSI SENSOR HP (0857458*****)
        $maskPhone = function($phone) {
            $phone = preg_replace('/[^0-9]/', '', $phone ?? '');
            if (strlen($phone) > 7) {
                return substr($phone, 0, 7) . str_repeat('*', strlen($phone) - 7);
            }
            return $phone;
        };

        // 4. TEKS WATERMARK DINAMIS
        $tglTerbitWmk = date('d M Y', strtotime($invoice->date ?? now()));
        $nomorResiInvoice = $invoice->resi ?? $invoice->invoice_no;
        $wmText = "VALID {$statusText} CV SANCAKA KARYA HUTAMA SANCAKA EXPRESS CREATED {$tglTerbitWmk} {$nomorResiInvoice}";
    @endphp

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap');

        body { font-family: 'Inter', sans-serif; }

        /* WATERMARK PALING DEPAN (FULL SCREEN, RAPAT, SAMAR) */
        .watermark-overlay {
            position: fixed;
            inset: -50%; /* Margin negatif lebar agar rotasi tidak memotong sudut */
            width: 200%; height: 200%;
            z-index: 99999; /* Paling depan menutupi segalanya */
            pointer-events: none; /* Klik tembus ke bawah */
            transform: rotate(-45deg);
            /* KODE GANTI UKURAN & KERAPATAN WATERMARK (Lihat penjelasan di bawah) */
            background-image: url("data:image/svg+xml,%3Csvg width='550' height='60' xmlns='http://www.w3.org/2000/svg'%3E%3Ctext x='275' y='35' fill='black' font-family='Arial, sans-serif' font-size='12' font-weight='800' text-anchor='middle'%3E{{ rawurlencode($wmText) }}%3C/text%3E%3C/svg%3E");
            background-repeat: repeat;
            opacity: 0.04; /* Samar-samar */
        }

        /* PITA STATUS (Pojok Kanan Atas) */
        .ribbon-wrapper {
            position: absolute; right: -5px; top: -5px; z-index: 50;
            overflow: hidden; width: 120px; height: 120px; text-align: right;
        }
        .ribbon {
            font-size: 0.75rem; font-weight: 800; color: #FFF;
            text-transform: uppercase; text-align: center; line-height: 28px;
            transform: rotate(45deg); -webkit-transform: rotate(45deg);
            width: 150px; display: block; background: #dc2626; /* MERAH (Batal/Belum Lunas) */
            position: absolute; top: 20px; right: -35px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1); letter-spacing: 1px;
        }
        .ribbon.paid { background: #16a34a; } /* HIJAU (Lunas) */

        /* DEKORASI SUDUT KANAN ATAS (Monokrom ala gambar) */
        .corner-stripes {
            position: absolute; top: 0; right: 0; width: 250px; height: 250px;
            background: linear-gradient(135deg, transparent 50%, #f1f5f9 50%, #f1f5f9 65%, transparent 65%, transparent 75%, #f8fafc 75%);
            z-index: 0; pointer-events: none;
        }

        /* TATA LETAK CETAK SAMA PERSIS DENGAN LAYAR (RESPONSIVE PRINT) */
        @media print {
            @page { size: A4 portrait; margin: 10mm; }
            body {
                background: white !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
                zoom: 0.95;
            }
            .no-print { display: none !important; }
            .watermark-overlay { position: fixed !important; -webkit-print-color-adjust: exact !important; }
            .print-container { box-shadow: none !important; margin: 0 !important; padding: 0 !important; border: none !important; width: 100% !important; max-width: 100% !important; }

            /* Paksa baris sejajar saat diprint (mencegah grid hancur) */
            .print-flex-row { display: flex !important; flex-direction: row !important; }
            .print-w-1-2 { width: 50% !important; }
            .print-w-full { width: 100% !important; }
            .print-justify-between { justify-content: space-between !important; }

            * { font-size: 11px !important; line-height: 1.4 !important; }
            h1.text-3xl { font-size: 24px !important; }
            table, .ribbon-wrapper { page-break-inside: avoid !important; }
        }
    </style>
</head>
<body class="bg-gray-100 text-black min-h-screen relative overflow-x-hidden p-0 md:p-8">

    <!-- LAYER WATERMARK PALING DEPAN -->
    <div class="watermark-overlay"></div>

    <div class="max-w-4xl mx-auto mb-4 flex justify-between items-center no-print px-4 md:px-0 relative z-50">
        <button onclick="window.history.back()" class="bg-white border border-gray-300 text-black px-4 py-1.5 rounded hover:bg-gray-50 text-xs font-bold transition shadow-sm">
            <i class="fas fa-arrow-left mr-2"></i> KEMBALI
        </button>
        <button onclick="window.print()" class="bg-black text-white px-4 py-1.5 rounded hover:bg-gray-800 text-xs font-bold transition shadow-sm">
            <i class="fas fa-print mr-2"></i> PRINT INVOICE
        </button>
    </div>

    <!-- KERTAS INVOICE UTAMA -->
    <div class="max-w-4xl mx-auto bg-white p-8 md:p-12 print-container relative shadow-lg overflow-hidden border border-gray-200">

        <!-- DEKORASI MONOKROM KANAN ATAS -->
        <div class="corner-stripes no-print"></div>

        <!-- PITA STATUS -->
        <div class="ribbon-wrapper">
            <div class="ribbon {{ $isLunas ? 'paid' : '' }}">
                {{ $isLunas ? 'LUNAS' : ($isCancelled ? 'REFUND' : 'UNPAID') }}
            </div>
        </div>

        <!-- DROPDOWN PEMBAYARAN KECIL (Di bawah pita, Kanan Atas) -->
        @if(!$isLunas && !$isCancelled)
        <div class="absolute top-[80px] right-[40px] w-[140px] z-50 no-print text-right">
            <p class="text-[9px] font-bold text-gray-400 uppercase tracking-widest mb-1">Bayar Tagihan</p>
            <button class="w-full bg-white border border-black p-1.5 flex items-center justify-between transition-colors shadow-sm text-[10px] hover:bg-slate-50 group">
                <span class="font-bold text-black">Pilih Metode</span>
                <i class="fas fa-chevron-down text-black"></i>
            </button>
        </div>
        @endif

        <!-- HEADER SECTION (Logo Kiri, Teks Kanan) -->
        <div class="flex flex-col md:flex-row print-flex-row justify-between items-start mb-10 relative z-10">
            <!-- Kiri: Logo & Info Perusahaan -->
            <div class="w-full md:w-1/2 print-w-1-2">
                @if(file_exists(storage_path('app/public/uploads/logo.jpeg')))
                    <!-- Biarkan logo tetap berwarna -->
                    <img src="data:image/jpeg;base64,{{ base64_encode(file_get_contents(storage_path('app/public/uploads/logo.jpeg'))) }}" alt="Sancaka" class="h-14 object-contain mb-4 bg-white p-1 rounded border border-gray-100">
                @else
                    <div class="h-14 flex items-center mb-4">
                        <span class="text-xl font-black tracking-tighter uppercase">SANCAKA EXPRESS</span>
                    </div>
                @endif
            </div>

            <!-- Kanan: Judul Invoice -->
            <div class="w-full md:w-1/2 print-w-1-2 text-left md:text-right mt-6 md:mt-0">
                <h1 class="text-3xl font-black text-black tracking-tight uppercase mb-1">Invoice</h1>
            </div>
        </div>

        <!-- INFO SECTION (Identitas disejajarkan sesuai desain) -->
        <div class="flex flex-col md:flex-row print-flex-row justify-between items-start mb-10 text-[11px] relative z-10 gap-6">

            <!-- Detail Kiri -->
            <div class="w-full md:w-1/3">
                <table class="w-full">
                    <tr><td class="w-24 text-gray-500 font-semibold py-0.5">Invoice No.</td><td class="font-bold text-black">: {{ $invoice->invoice_no }}</td></tr>
                    <tr><td class="w-24 text-gray-500 font-semibold py-0.5">Date</td><td class="font-bold text-black">: {{ date('d / m / Y', strtotime($invoice->date)) }}</td></tr>
                    <tr><td class="w-24 text-gray-500 font-semibold py-0.5 align-top">Invoice To</td><td class="font-bold text-black align-top uppercase">: {{ $maskName($invoice->customer_name) }}</td></tr>
                    @if($invoice->alamat)
                    <tr><td class="w-24"></td><td class="text-gray-500 pt-1 leading-relaxed">{{ $invoice->alamat }}</td></tr>
                    @endif
                    <tr><td class="w-24 text-gray-500 font-semibold py-0.5">Phone</td><td class="font-bold text-black">: {{ $maskPhone($invoice->phone ?? '085745808809') }}</td></tr>
                </table>
            </div>

            <!-- QR Code Kanan -->
            <div class="w-full md:w-auto text-left md:text-right">
                <div class="inline-block p-1 border border-gray-200 bg-white shadow-sm">
                    <!-- Gunakan QR Code dummy hitam putih -->
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=80x80&data={{ urlencode($invoice->invoice_no) }}" alt="QR" class="w-16 h-16">
                </div>
            </div>
        </div>

        <!-- TABEL RINCIAN (Desain Pill/Rounded, Clean Next.js Hitam) -->
        <div class="mb-10 relative z-10">
            <!-- Header Tabel Rounded -->
            <div class="bg-black text-white rounded-full flex px-5 py-2.5 font-bold text-[10px] uppercase tracking-widest mb-3">
                <div class="w-[45%]">Description</div>
                <div class="w-[20%] text-center">Price</div>
                <div class="w-[15%] text-center">Qty.</div>
                <div class="w-[20%] text-right">Total</div>
            </div>

            <!-- Body Tabel -->
            <!-- CLASS WARNA TABEL: Jika ingin abu-abu muda, tambahkan 'bg-gray-50' di div bawah ini -->
            <div class="flex flex-col text-[11px] font-medium text-black" id="tabel-rincian">
                @foreach($invoice->items as $item)
                <div class="flex px-5 py-3 border-b border-gray-200 items-center hover:bg-gray-50 transition-colors">
                    <div class="w-[45%] pr-2">{{ $item->description }}</div>
                    <div class="w-[20%] text-center">Rp {{ number_format($item->price, 0, ',', '.') }}</div>
                    <div class="w-[15%] text-center">{{ $item->qty }}</div>
                    <div class="w-[20%] text-right font-bold">Rp {{ number_format($item->total, 0, ',', '.') }}</div>
                </div>
                @endforeach
            </div>
        </div>

        <!-- TOTALS & NOTES SECTION -->
        <div class="flex flex-col-reverse md:flex-row print-flex-row justify-between items-start gap-8 relative z-10 mb-16">

            <!-- Kiri: Catatan & Info Bank -->
            <div class="w-full md:w-1/2 print-w-1-2">
                @if($invoice->keterangan)
                <div class="mb-6">
                    <p class="text-[11px] font-bold text-black mb-1">Catatan Tambahan:</p>
                    <p class="text-[10px] text-gray-500 leading-relaxed max-w-sm">
                        {!! nl2br(e($invoice->keterangan)) !!}
                    </p>
                </div>
                @endif

                <div>
                    <p class="text-[11px] font-bold text-black mb-1">Payment Info:</p>
                    <p class="text-[10px] text-gray-500 leading-relaxed max-w-sm">
                        Pembayaran dianggap sah apabila telah divalidasi oleh sistem atau masuk ke rekening resmi CV SANCAKA KARYA HUTAMA.
                    </p>
                </div>
            </div>

            <!-- Kanan: Kalkulasi Total -->
            <div class="w-full md:w-[40%] print-w-1-2">
                <div class="flex justify-between py-1 text-[11px] font-bold text-black">
                    <span class="uppercase">Subtotal</span>
                    <span>Rp {{ number_format($invoice->subtotal, 0, ',', '.') }}</span>
                </div>

                @if($invoice->discount_amount > 0)
                <div class="flex justify-between py-1 text-[11px] font-bold text-black">
                    <span class="uppercase">Diskon</span>
                    <span>- Rp {{ number_format($invoice->discount_amount, 0, ',', '.') }}</span>
                </div>
                @endif

                <div class="flex justify-between items-center py-2 border-t-2 border-b-2 border-black mt-2 mb-2">
                    <span class="text-[12px] font-black text-black uppercase tracking-widest">Grand Total</span>
                    <span class="text-[14px] font-black text-black">Rp {{ number_format($invoice->grand_total, 0, ',', '.') }}</span>
                </div>

                @if($invoice->dp > 0)
                <div class="flex justify-between py-1 text-[10px] text-gray-500 font-bold mt-2">
                    <span class="uppercase">DP / Uang Muka</span>
                    <span>Rp {{ number_format($invoice->dp, 0, ',', '.') }}</span>
                </div>
                <div class="flex justify-between py-1 text-[11px] text-black font-black border-t border-dashed border-gray-300 mt-1">
                    <span class="uppercase">Sisa Kekurangan</span>
                    <span>Rp {{ number_format($invoice->sisa_tagihan, 0, ',', '.') }}</span>
                </div>
                @endif
            </div>
        </div>

        <!-- TANDA TANGAN (Kanan Bawah) -->
        <div class="flex justify-end relative z-10 border-t border-gray-200 pt-8">
            <div class="text-center w-48">
                @if($invoice->signature_path)
                    <img src="{{ storage_path('app/public/' . $invoice->signature_path) }}" class="h-12 object-contain mx-auto mb-2">
                @else
                    <div class="h-16"></div>
                @endif
                <div class="border-b border-black w-full mb-1"></div>
                <p class="font-black text-[11px] text-black uppercase tracking-wide">A*** I*** M**********</p> <!-- SENSOR NAMA DIREKTUR -->
                <p class="text-[9px] font-bold text-gray-500 uppercase tracking-widest">Director</p>
            </div>
        </div>

        <!-- WAVE BOTTOM MONOKROM -->
        <svg class="absolute bottom-0 left-0 w-full h-16 no-print z-0 opacity-10 pointer-events-none" viewBox="0 0 1440 120" preserveAspectRatio="none">
            <path d="M0,64L80,69.3C160,75,320,85,480,80C640,75,800,53,960,48C1120,43,1280,53,1360,58.7L1440,64L1440,120L1360,120C1280,120,1120,120,960,120C800,120,640,120,480,120C320,120,160,120,80,120L0,120Z" fill="#000000"></path>
        </svg>

    </div>
</body>
</html>
