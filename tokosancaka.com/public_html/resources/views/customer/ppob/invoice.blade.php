<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Invoice #{{ $transaction->order_id }}</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <!-- LIBRARIES -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <script src="https://html2canvas.hertzen.com/dist/html2canvas.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- TAMBAHAN LIBRARY UNTUK BARCODE -->
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>

    @php
        // 1. LOGIKA STATUS
        $statusRaw = strtolower($transaction->status ?? '');
        $isLunas = in_array($statusRaw, ['sukses', 'lunas', 'berhasil', 'success', 'paid']);
        $isCancelled = in_array($statusRaw, ['batal', 'gagal', 'cancel', 'refund']);
        $statusText = $isLunas ? 'LUNAS' : ($isCancelled ? 'BATAL' : 'BELUM LUNAS');

        // 2. SENSOR NAMA
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

        // 3. SENSOR HP
        $maskPhone = function($phone) {
            $phone = preg_replace('/[^0-9]/', '', (string) $phone);
            if (strlen($phone) > 7) {
                return substr($phone, 0, 7) . str_repeat('*', strlen($phone) - 7);
            }
            return $phone ?: '-';
        };

        // 4. WATERMARK
        $tglTerbitWmk = $transaction->created_at ? $transaction->created_at->format('d M Y') : date('d M Y');
        $wmText = "VALID {$statusText} SANCAKA STORE CREATED {$tglTerbitWmk} {$transaction->order_id}";
    @endphp

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap');
        body { font-family: 'Inter', sans-serif; background-color: #f3f4f6; }

        /* KERTAS INVOICE UTAMA */
        .invoice-content {
            position: relative;
            background: white;
            overflow: hidden;
            min-height: 1123px; /* Standar A4 */
            display: flex;
            flex-direction: column;
        }

        /* WATERMARK (Ditumpuk di dalam container agar ikut ter-print html2pdf) */
        .watermark-overlay {
            position: absolute;
            top: -50%; left: -50%;
            width: 200%; height: 200%;
            z-index: 1; pointer-events: none;
            transform: rotate(-35deg);
            display: flex; flex-wrap: wrap; align-content: flex-start; justify-content: center;
            overflow: hidden;
        }
        .watermark-overlay p {
            color: rgba(0, 0, 0, 0.03);
            font-size: 14px; font-weight: 900;
            margin: 20px 30px; white-space: nowrap;
            letter-spacing: 1px;
        }

        /* PITA STATUS POJOK KANAN */
        .ribbon-wrapper {
            position: absolute; top: -5px; right: -5px; z-index: 50;
            overflow: hidden; width: 140px; height: 140px; text-align: right;
        }
        .ribbon {
            font-size: 11px; font-weight: 800; color: #FFF;
            text-transform: uppercase; text-align: center; line-height: 32px;
            transform: rotate(45deg); width: 180px; display: block;
            background: #dc2626; /* Merah */
            position: absolute; top: 25px; right: -40px;
            letter-spacing: 1px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .ribbon.paid { background: #16a34a; } /* Hijau */

        /* DEKORASI SUDUT KANAN ATAS */
        .corner-stripes {
            position: absolute; top: 0; right: 0; width: 250px; height: 250px;
            background: linear-gradient(135deg, transparent 50%, #f1f5f9 50%, #f1f5f9 65%, transparent 65%, transparent 75%, #f8fafc 75%);
            z-index: 0; pointer-events: none;
        }

        /* ACTION BAR (Toolbar Atas) */
        .action-bar {
            background-color: white; border-bottom: 1px solid #e5e7eb;
            position: sticky; top: 0; z-index: 1000; box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }

        /* CSS KHUSUS CETAK & EXPORT */
        @media print {
            .no-print { display: none !important; }
            .invoice-wrapper { margin: 0 !important; max-width: 100% !important; box-shadow: none !important; border: none !important; }
            body { background-color: white !important; -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
        }
    </style>
</head>
<body class="text-black">

    <!-- TOOLBAR AKSI (TIDAK IKUT TERCETAK) -->
    <div class="action-bar no-print py-4 mb-8">
        <div class="max-w-4xl mx-auto flex flex-col sm:flex-row justify-between items-center px-4 md:px-0 gap-4">
            <a href="https://tokosancaka.com/customer/ppob/history" class="w-full sm:w-auto text-center border border-gray-300 text-black px-4 py-2 rounded-md hover:bg-gray-50 text-sm font-semibold transition">
                <i class="fas fa-arrow-left mr-2"></i> Kembali
            </a>
            <div class="flex flex-wrap gap-2 justify-center w-full sm:w-auto">
                <button onclick="sendWhatsapp()" class="bg-[#25D366] text-white px-4 py-2 rounded-md hover:opacity-90 text-sm font-semibold transition shadow-sm">
                    <i class="fab fa-whatsapp mr-1"></i> Kirim WA
                </button>
                <button onclick="downloadJPG()" class="bg-gray-800 text-white px-4 py-2 rounded-md hover:bg-gray-700 text-sm font-semibold transition shadow-sm">
                    <i class="fas fa-image mr-1"></i> JPG
                </button>
                <button onclick="downloadPDF()" class="bg-black text-white px-4 py-2 rounded-md hover:bg-gray-900 text-sm font-semibold transition shadow-sm">
                    <i class="fas fa-file-pdf mr-1"></i> PDF
                </button>
            </div>
        </div>
    </div>

    <!-- AREA INVOICE (TARGET EXPORT) -->
    <div class="max-w-4xl mx-auto invoice-wrapper border border-gray-200 shadow-xl mb-12" id="invoice-area">
        <div class="invoice-content p-8 md:p-12 relative">

            <!-- LAYER WATERMARK -->
            <div class="watermark-overlay">
                @for($i=0; $i<60; $i++)
                    <p>{{ $wmText }}</p>
                @endfor
            </div>

            <!-- DEKORASI & PITA -->
            <div class="corner-stripes"></div>
            <div class="ribbon-wrapper">
                <div class="ribbon {{ $isLunas ? 'paid' : '' }}">
                    {{ $isLunas ? 'LUNAS' : ($isCancelled ? 'BATAL' : 'BELUM LUNAS') }}
                </div>
            </div>

            <!-- HEADER SECTIONS -->
            <div class="flex flex-col md:flex-row justify-between items-start mb-10 pb-8 border-b border-gray-100 relative z-10 gap-8">
                <!-- Kiri: Logo & Info Store -->
                <div class="w-full md:w-1/2">
                    <!-- KODE LOGO DITARUH DI SINI -->
                    <img src="https://tokosancaka.com/storage/uploads/sancaka.png" alt="Logo Sancaka" class="h-14 object-contain mb-3" onerror="this.style.display='none'">

                    <h1 class="text-3xl font-black tracking-tighter uppercase mb-2">SANCAKA STORE</h1>
                    <div class="text-[11px] text-gray-500 leading-relaxed font-medium">
                        <p>Pusat Belanja Online No. 1 di Indonesia.</p>
                        <p class="mb-3">Belanja lebih hemat, aman, dan cepat. Dijamin!</p>
                        <p><i class="fas fa-phone mr-1"></i> +62 881-9435-180</p>
                        <p><i class="fas fa-globe mr-1"></i> www.tokosancaka.com</p>
                        <p><i class="fas fa-envelope mr-1"></i> admin@tokosancaka.com</p>
                    </div>
                </div>

                <!-- Kanan: Judul Invoice & Barcode -->
                <div class="w-full md:w-1/2 flex flex-col items-start md:items-end mt-4 md:mt-0">
                    <h2 class="text-4xl font-black text-black tracking-tight uppercase mb-1">Invoice</h2>
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-2">#{{ $transaction->order_id }}</p>
                    <!-- KOTAK BARCODE -->
                    <div class="bg-white px-2 py-1 rounded">
                        <svg id="barcodeInvoice"></svg>
                    </div>
                </div>
            </div>

            <!-- INFO PELANGGAN & TRANSAKSI -->
            <div class="flex flex-col md:flex-row justify-between items-start mb-10 relative z-10 gap-8">

                <!-- Kiri: Customer Data -->
                @php
                    $user = \App\Models\User::select('store_name', 'province', 'regency', 'district', 'village', 'postal_code', 'address_detail')->find($transaction->user_id);
                @endphp
                <div class="w-full md:w-1/2">
                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-2 border-b border-gray-100 pb-2">Invoice To</p>
                    <p class="font-bold text-sm text-black uppercase">{{ $maskName($user->store_name ?? 'Pelanggan Setia') }}</p>

                    @if($user)
                    <p class="text-[11px] text-gray-500 mt-2 leading-relaxed">
                        {{ collect([$user->address_detail, $user->village, $user->district])->filter()->implode(', ') }}<br>
                        {{ collect([$user->regency, $user->province, $user->postal_code])->filter()->implode(', ') }}
                    </p>
                    @endif
                    <p class="text-[11px] font-medium text-black mt-3">ID Customer: <span class="font-bold">{{ $maskPhone($transaction->customer_no) }}</span></p>
                </div>

                <!-- Kanan: Detail Order -->
                <div class="w-full md:w-auto md:text-right">
                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-2 border-b border-gray-100 pb-2 text-left md:text-right">Detail Transaksi</p>
                    <table class="w-full text-[11px] text-left md:text-right">
                        <tr>
                            <td class="py-1 text-gray-500 pr-4">Invoice No</td>
                            <td class="py-1 font-bold text-black">: {{ $transaction->order_id }}</td>
                        </tr>
                        <tr>
                            <td class="py-1 text-gray-500 pr-4">Date</td>
                            <td class="py-1 font-bold text-black">: {{ $transaction->created_at->format('d / m / Y') }}</td>
                        </tr>
                        <tr>
                            <td class="py-1 text-gray-500 pr-4">Status</td>
                            <td class="py-1 font-bold {{ $isLunas ? 'text-green-600' : 'text-red-600' }} uppercase">: {{ $transaction->status }}</td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- TABEL ITEM UTAMA -->
            <div class="relative z-10 flex-1 mb-8">
                <table class="w-full text-[11px] text-left">
                    <thead class="bg-black text-white">
                        <tr>
                            <th class="py-3 px-4 font-bold uppercase tracking-wider w-[5%] text-center rounded-tl-md">No</th>
                            <th class="py-3 px-4 font-bold uppercase tracking-wider w-[50%]">Item Description</th>
                            <th class="py-3 px-4 font-bold uppercase tracking-wider w-[15%] text-right">Price</th>
                            <th class="py-3 px-4 font-bold uppercase tracking-wider w-[10%] text-center">Qty</th>
                            <th class="py-3 px-4 font-bold uppercase tracking-wider w-[20%] text-right rounded-tr-md">Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <!-- ITEM 1 -->
                        <tr class="hover:bg-gray-50">
                            <td class="py-4 px-4 text-center font-medium align-top">1.</td>
                            <td class="py-4 px-4 align-top">
                                <strong class="text-[12px] uppercase text-black block mb-1">{{ $transaction->buyer_sku_code }}</strong>
                                <span class="text-gray-500 block mb-2">Metode: {{ str_replace('_', ' ', $transaction->payment_method) }}</span>

                                <!-- LOGIKA TOKEN PLN / SERIAL NUMBER -->
                                @if($transaction->sn)
                                    @php
                                        $parts = explode('/', $transaction->sn);
                                        $isPln = count($parts) > 1;
                                    @endphp

                                    @if($isPln)
                                        <div class="mt-3 p-4 bg-slate-50 border border-gray-200 rounded-lg">
                                            <div class="text-[9px] text-gray-400 font-bold uppercase tracking-widest mb-1">Token Listrik:</div>
                                            <div class="font-mono text-lg font-black text-black tracking-widest mb-3">
                                                {{ $parts[0] }}
                                            </div>
                                            <div class="border-t border-dashed border-gray-200 mb-3"></div>

                                            <div class="text-[10px] text-gray-600 space-y-1.5">
                                                @if(isset($parts[1])) <div class="flex"><span class="w-12 font-medium">Nama</span><span>: <strong class="text-black">{{ $parts[1] }}</strong></span></div> @endif
                                                @if(isset($parts[2])) <div class="flex"><span class="w-12 font-medium">Tarif</span><span>: {{ $parts[2] }}</span></div> @endif
                                                @if(isset($parts[3])) <div class="flex"><span class="w-12 font-medium">Daya</span><span>: {{ $parts[3] }}</span></div> @endif
                                                @if(isset($parts[4])) <div class="flex"><span class="w-12 font-medium">KWH</span><span>: {{ $parts[4] }}</span></div> @endif
                                            </div>
                                        </div>
                                    @else
                                        <div class="mt-2 inline-block bg-slate-100 px-3 py-1.5 border border-gray-200 rounded text-[10px] font-mono font-bold text-black">
                                            SN: {{ $transaction->sn }}
                                        </div>
                                    @endif
                                @endif
                            </td>
                            <td class="py-4 px-4 text-right align-top">Rp {{ number_format($transaction->selling_price, 0, ',', '.') }}</td>
                            <td class="py-4 px-4 text-center font-bold align-top">1</td>
                            <td class="py-4 px-4 text-right font-bold text-black align-top">Rp {{ number_format($transaction->selling_price, 0, ',', '.') }}</td>
                        </tr>

                        <!-- ITEM 2 (BIAYA ADMIN) -->
                        <tr class="bg-slate-50">
                            <td class="py-4 px-4 text-center font-medium align-top">2.</td>
                            <td class="py-4 px-4 align-top font-medium text-gray-600">Biaya Layanan / Admin</td>
                            <td class="py-4 px-4 text-right align-top">Rp 0</td>
                            <td class="py-4 px-4 text-center font-bold align-top">0</td>
                            <td class="py-4 px-4 text-right font-bold text-black align-top">Rp 0</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- TOTALS -->
            <div class="flex justify-end relative z-10 border-t border-black pt-6 mb-12">
                <div class="w-full md:w-[45%]">
                    <div class="flex justify-between py-1.5 text-[11px] text-gray-600">
                        <span class="font-bold uppercase tracking-wider">Sub Total</span>
                        <span class="font-medium">Rp {{ number_format($transaction->selling_price, 0, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between py-1.5 text-[11px] text-gray-600">
                        <span class="font-bold uppercase tracking-wider">Tax (0%)</span>
                        <span class="font-medium">0.00%</span>
                    </div>
                    <div class="flex justify-between py-3 mt-2 border-t-2 border-b-2 border-black text-sm">
                        <span class="font-black text-black uppercase tracking-widest">Grand Total</span>
                        <span class="font-black text-black">Rp {{ number_format($transaction->selling_price, 0, ',', '.') }}</span>
                    </div>
                </div>
            </div>

            <!-- FOOTER -->
            <div class="mt-auto relative z-10 pt-6">
                <div class="flex flex-col md:flex-row justify-between items-end gap-4">
                    <div>
                        <h4 class="text-[12px] font-black text-black uppercase tracking-wider mb-1">Thank You For Your Business</h4>
                        <p class="text-[10px] text-gray-500 font-medium">CV. SANCAKA KARYA HUTAMA - SANCAKA EXPRESS</p>
                        <p class="text-[9px] text-gray-400 mt-2 border-t border-gray-100 pt-2 inline-block">Payment Info: {{ $transaction->order_id }} | Acc Name: Sancaka Store</p>
                    </div>

                    <div class="text-right">
                        <p class="text-[9px] font-bold text-gray-400 uppercase tracking-widest mb-1">Total Amount Due</p>
                        <p class="text-2xl font-black text-black">Rp {{ number_format($transaction->selling_price, 0, ',', '.') }}</p>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- SCRIPT JS -->
    <script>
        // RENDER BARCODE SAAT HALAMAN DIMUAT
        document.addEventListener('DOMContentLoaded', function() {
            try {
                JsBarcode("#barcodeInvoice", "{{ $transaction->order_id }}", {
                    format: "CODE128",
                    lineColor: "#000000",
                    width: 1.5,
                    height: 35,
                    displayValue: false, // Nilai text disembunyikan karena sudah ada #TRX-PRA diatasnya
                    margin: 0
                });
            } catch (e) {
                console.error("Gagal memuat barcode:", e);
            }
        });

        // FUNGSI PDF
        function downloadPDF() {
            const element = document.getElementById('invoice-area');
            Swal.fire({ title: 'Memproses PDF...', didOpen: () => { Swal.showLoading() } });
            html2pdf().set({
                margin: 0, filename: 'Invoice-{{ $transaction->order_id }}.pdf',
                image: { type: 'jpeg', quality: 0.98 },
                html2canvas: { scale: 2, useCORS: true },
                jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' }
            }).from(element).save().then(() => Swal.close());
        }

        // FUNGSI JPG
        function downloadJPG() {
            const element = document.getElementById('invoice-area');
            Swal.fire({ title: 'Memproses JPG...', didOpen: () => { Swal.showLoading() } });
            html2canvas(element, { scale: 2, useCORS: true }).then(canvas => {
                const link = document.createElement('a');
                link.download = 'Invoice-{{ $transaction->order_id }}.jpg';
                link.href = canvas.toDataURL('image/jpeg', 0.9);
                link.click();
                Swal.close();
            });
        }

        // FUNGSI WA
        function sendWhatsapp() {
            const transactionId = "{{ $transaction->order_id }}";
            const customerPhone = "{{ $transaction->customer_wa ?? $transaction->customer_no }}";

            Swal.fire({
                title: 'Kirim Invoice ke WA?',
                text: "Ke nomor: " + customerPhone,
                icon: 'question', showCancelButton: true,
                confirmButtonColor: '#25D366', confirmButtonText: 'Ya, Kirim!'
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({ title: 'Mengirim...', didOpen: () => Swal.showLoading() });
                    fetch('/transaction/resend-wa', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content') },
                        body: JSON.stringify({ order_id: transactionId, phone: customerPhone })
                    })
                    .then(r => r.json())
                    .then(d => {
                        if(d.status === 'success' || d.status === true) Swal.fire('Sukses', 'Terkirim!', 'success');
                        else Swal.fire('Gagal', d.message, 'error');
                    })
                    .catch(() => Swal.fire('Error', 'Kesalahan server.', 'error'));
                }
            })
        }
    </script>
</body>
</html>
