<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice #{{ $invoice->invoice_no }}</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    @php
        // 1. LOGIKA STATUS LUNAS / UNPAID
        $isLunas = (isset($invoice->sisa_tagihan) && $invoice->sisa_tagihan <= 0) || (isset($invoice->grand_total) && $invoice->grand_total <= 0);
        $statusText = $isLunas ? 'LUNAS' : 'UNPAID';

        // 2. FUNGSI SENSOR NAMA
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

        // 3. TEKS WATERMARK DINAMIS
        $tglTerbitWmk = date('d M Y', strtotime($invoice->date ?? now()));
        $wmText = "VALID {$statusText} CV SANCAKA KARYA HUTAMA CREATED {$tglTerbitWmk} NO {$invoice->invoice_no}";
    @endphp

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap');

        body { font-family: 'Inter', sans-serif; }

        /* WATERMARK (Paling Depan, Rapat, Samar, Miring 45 Derajat) */
        /* KODE CARA KECILKAN FONT WATERMARK: Ubah angka pada font-size='11' di dalam URL SVG di bawah ini */
        .watermark-bg {
            position: fixed;
            top: -100%; left: -100%; width: 300%; height: 300%;
            z-index: 9999;
            pointer-events: none;
            transform: rotate(-45deg);
            background-image: url("data:image/svg+xml,%3Csvg width='400' height='40' xmlns='http://www.w3.org/2000/svg'%3E%3Ctext x='200' y='25' fill='black' font-family='Arial, sans-serif' font-size='11' font-weight='800' text-anchor='middle'%3E{{ rawurlencode($wmText) }}%3C/text%3E%3C/svg%3E");
            background-repeat: repeat;
            background-position: center;
            opacity: 0.03; /* Sangat samar */
        }

        /* DESAIN PITA (Hanya ini yang berwarna merah/hijau) */
        .ribbon-wrapper {
            position: absolute; right: -5px; top: -5px; z-index: 20;
            overflow: hidden; width: 150px; height: 150px; text-align: right;
        }
        .ribbon {
            font-size: 0.85rem; font-weight: 800; color: #FFF;
            text-transform: uppercase; text-align: center; line-height: 34px;
            transform: rotate(45deg); -webkit-transform: rotate(45deg);
            width: 200px; display: block; background: #dc2626; /* Merah untuk UNPAID/CANCEL */
            position: absolute; top: 25px; right: -45px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1); letter-spacing: 1px;
        }
        .ribbon.paid { background: #16a34a; } /* Hijau untuk LUNAS */

        /* MEDIA PRINT (1 Halaman, Skala Disesuaikan, Flexbox Tetap Utuh) */
        @media print {
            @page { size: A4 portrait; margin: 10mm; }
            body { background: white !important; -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; zoom: 0.90; }
            .no-print { display: none !important; }
            .watermark-bg { position: fixed !important; -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
            .print-container { box-shadow: none !important; width: 100% !important; margin: 0 !important; padding: 0 !important; border: none !important; }

            /* Menjaga Layout Grid & Flex saat diprint */
            .md\:flex-row { flex-direction: row !important; display: flex !important; }
            .md\:grid-cols-2 { display: grid !important; grid-template-columns: repeat(2, minmax(0, 1fr)) !important; }
            .md\:w-1\/2 { width: 50% !important; }
            table, .grid, .ribbon-wrapper { page-break-inside: avoid !important; }
            * { font-size: 11px !important; line-height: 1.4 !important; }
            .text-2xl { font-size: 18px !important; }
        }
    </style>
</head>
<body class="bg-slate-100 text-black min-h-screen relative overflow-x-hidden">

    <!-- Watermark Layer Front -->
    <div class="watermark-bg"></div>

    <div class="py-8 relative z-10">
        <!-- Action Buttons (No Print) -->
        <div class="max-w-4xl mx-auto mb-6 flex flex-col sm:flex-row justify-between items-center no-print px-4 md:px-0 gap-3">
            <button onclick="window.history.back()" class="w-full sm:w-auto text-center bg-white border border-gray-200 text-black px-4 py-2 rounded-md hover:bg-slate-50 text-sm font-medium transition shadow-sm">
                <i class="fas fa-arrow-left mr-2"></i> Kembali
            </button>
            <button onclick="window.print()" class="w-full sm:w-auto text-center bg-black text-white px-4 py-2 rounded-md hover:bg-gray-800 text-sm font-medium transition shadow-sm">
                <i class="fas fa-print mr-2"></i> Print Invoice
            </button>
        </div>

        <!-- Main Invoice Container -->
        <div class="max-w-4xl mx-auto bg-white p-8 md:p-12 print-container relative border border-gray-200 shadow-sm">

            <!-- Ribbon Status -->
            <div class="ribbon-wrapper no-print">
                <div class="ribbon {{ $isLunas ? 'paid' : '' }}">
                    {{ $isLunas ? 'LUNAS' : 'UNPAID' }}
                </div>
            </div>

            <!-- Header Section -->
            <div class="flex flex-col md:flex-row justify-between items-start mb-10 pb-8 border-b border-gray-100 gap-8">

                <!-- Left: Title & Logo -->
                <div class="w-full md:w-1/2">
                    <h1 class="text-3xl font-black text-black tracking-tight mb-1 uppercase">Invoice</h1>
                    <p class="text-sm text-gray-500 font-medium mb-6">NO: {{ $invoice->invoice_no }}</p>

                    @if(file_exists(storage_path('app/public/uploads/logo.jpeg')))
                        <img src="data:image/jpeg;base64,{{ base64_encode(file_get_contents(storage_path('app/public/uploads/logo.jpeg'))) }}" alt="Logo" class="h-16 object-contain mb-4">
                    @endif
                </div>

                <!-- Right: Dropdown Pembayaran & Info Perusahaan -->
                <div class="w-full md:w-1/2 flex flex-col md:items-end">

                    <!-- Dropdown Pembayaran (Tampil jika belum lunas) -->
                    @if(!$isLunas)
                    <div class="mb-6 w-full md:w-48 no-print z-50">
                        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1 text-right">Pembayaran</p>
                        <button class="w-full bg-white border border-gray-300 hover:border-black p-2 rounded flex items-center justify-between transition-colors shadow-sm text-xs group">
                            <span class="font-semibold text-black">Pilih Metode</span>
                            <i class="fas fa-chevron-down text-gray-400 group-hover:text-black"></i>
                        </button>
                    </div>
                    @endif

                    <div class="text-left md:text-right text-[12px] text-gray-500 leading-relaxed">
                        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">From</p>
                        <p class="font-bold text-black text-sm uppercase">CV. Sancaka Karya Hutama</p>
                        <p>Jl. Dr. Wahidin no.18A RT.22/05</p>
                        <p>Ketanggi, Ngawi, Jawa Timur 63211</p>
                        <p class="font-medium text-black mt-1">Phone: 0857*** *** ***</p> <!-- Sensor HP Statis -->
                    </div>
                </div>
            </div>

            <!-- Billing Info Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-10">
                <div>
                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-2 border-b border-gray-100 pb-2">Bill To</p>
                    <p class="font-bold text-sm text-black uppercase">{{ $maskName($invoice->customer_name) }}</p> <!-- Sensor Nama -->

                    @if($invoice->company_name)
                        <p class="text-black text-[12px] font-medium mt-1">{{ $maskName($invoice->company_name) }}</p>
                    @endif

                    @if($invoice->alamat)
                        <p class="text-gray-500 mt-2 text-[12px] leading-relaxed">{{ $invoice->alamat }}</p>
                    @endif
                </div>

                <div class="md:text-right">
                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-2 border-b border-gray-100 pb-2">Date Details</p>
                    <p class="text-[12px] text-gray-500">Tanggal Terbit:<br>
                    <span class="font-bold text-black text-sm">{{ date('d F Y', strtotime($invoice->date)) }}</span></p>
                </div>
            </div>

            <!-- Items Table -->
            <div class="mb-10 overflow-hidden relative z-40">
                <!-- KODE CARA GANTI WARNA TABEL KE ABU-ABU MUDA: -->
                <!-- Tambahkan class 'bg-slate-50' pada tag <table> di bawah ini -->
                <!-- Contoh: <table class="w-full text-sm text-left bg-slate-50"> -->
                <table class="w-full text-sm text-left">
                    <thead class="border-b-2 border-black">
                        <tr>
                            <th class="py-3 px-2 font-bold text-black uppercase text-[11px] tracking-wider w-[45%]">Description</th>
                            <th class="py-3 px-2 font-bold text-black uppercase text-[11px] tracking-wider text-center w-[15%]">Qty</th>
                            <th class="py-3 px-2 font-bold text-black uppercase text-[11px] tracking-wider text-right w-[20%]">Price</th>
                            <th class="py-3 px-2 font-bold text-black uppercase text-[11px] tracking-wider text-right w-[20%]">Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach($invoice->items as $item)
                        <tr>
                            <td class="py-4 px-2 text-[12px] text-black align-top">{{ $item->description }}</td>
                            <td class="py-4 px-2 text-[12px] text-black text-center align-top font-medium">{{ $item->qty }}</td>
                            <td class="py-4 px-2 text-[12px] text-black text-right align-top">Rp {{ number_format($item->price, 0, ',', '.') }}</td>
                            <td class="py-4 px-2 text-[12px] text-black text-right align-top font-semibold">Rp {{ number_format($item->total, 0, ',', '.') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Totals & Notes Section -->
            <div class="flex flex-col-reverse md:flex-row justify-between gap-8 relative z-40">

                <!-- Notes -->
                <div class="w-full md:w-1/2">
                    @if($invoice->keterangan)
                        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-2">Keterangan / Notes</p>
                        <p class="text-[11px] text-gray-500 leading-relaxed p-3 bg-slate-50 border border-gray-100 rounded-md">
                            {!! nl2br(e($invoice->keterangan)) !!}
                        </p>
                    @endif
                </div>

                <!-- Totals Calculation -->
                <div class="w-full md:w-[40%]">
                    <div class="flex justify-between py-2 text-[12px] text-gray-600">
                        <span>Subtotal</span>
                        <span class="font-medium text-black">Rp {{ number_format($invoice->subtotal, 0, ',', '.') }}</span>
                    </div>

                    @if($invoice->discount_amount > 0)
                    <div class="flex justify-between py-2 text-[12px] text-gray-600">
                        <span>Diskon</span>
                        <span class="font-medium text-black">- Rp {{ number_format($invoice->discount_amount, 0, ',', '.') }}</span>
                    </div>
                    @endif

                    <div class="flex justify-between py-3 border-t border-b border-black text-sm font-black text-black uppercase tracking-wide mt-2 mb-2">
                        <span>Grand Total</span>
                        <span>Rp {{ number_format($invoice->grand_total, 0, ',', '.') }}</span>
                    </div>

                    @if($invoice->dp > 0)
                    <div class="flex justify-between py-2 text-[12px] text-gray-600">
                        <span>DP / Uang Muka</span>
                        <span class="font-medium text-black">Rp {{ number_format($invoice->dp, 0, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between py-2 text-[12px] text-black font-bold border-t border-dashed border-gray-300 mt-1">
                        <span>Sisa Kekurangan</span>
                        <span>Rp {{ number_format($invoice->sisa_tagihan, 0, ',', '.') }}</span>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Signature Section -->
            <div class="mt-16 flex justify-end relative z-40">
                <div class="text-center">
                    <p class="text-[12px] text-gray-500 mb-2">Hormat Kami,</p>

                    <div class="h-20 flex items-center justify-center">
                        @if($invoice->signature_path)
                            <img src="{{ storage_path('app/public/' . $invoice->signature_path) }}" class="h-16 object-contain">
                        @endif
                    </div>

                    <p class="font-bold text-sm text-black mt-2">A*** I*** M**********</p> <!-- Sensor Nama Direktur -->
                    <p class="text-[11px] text-gray-400 font-medium">Direktur</p>
                </div>
            </div>

        </div>
    </div>
</body>
</html>
