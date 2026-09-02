<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pembayaran Nota #{{ $nota->no_nota }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <!-- Load QRCode.js untuk BCA QRIS -->
    <script src="https://cdn.jsdelivr.net/npm/qrcodejs/qrcode.min.js"></script>

    <!-- ✅ TAMBAHAN: Library untuk Cetak PDF Persis Tampilan Blade -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap');
        body { font-family: 'Inter', sans-serif; }
        .custom-scrollbar::-webkit-scrollbar { width: 4px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: #fafafa; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #e5e5e5; border-radius: 4px; }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #d4d4d4; }

        .ribbon-wrapper {
            position: absolute; right: -5px; top: -5px; z-index: 50;
            overflow: hidden; width: 150px; height: 150px; text-align: right;
        }
        .ribbon {
            font-size: 0.9rem; font-weight: 800; color: #FFF;
            text-transform: uppercase; text-align: center; line-height: 36px;
            transform: rotate(45deg); -webkit-transform: rotate(45deg);
            width: 200px; display: block;
            position: absolute; top: 25px; right: -45px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1); letter-spacing: 1px;
        }
        .ribbon.paid { background: #16a34a; } /* Hijau */
        .ribbon.unpaid { background: #ef4444; } /* Merah */

        /* ✅ TAMBAHAN: CSS Khusus Print Agar Rapi */
        @media print {
            body { background: white !important; }
            #mainContentWrapper {
                box-shadow: none !important;
                margin: 0 !important;
                padding: 10px !important;
                max-width: 100% !important;
            }
            .no-print { display: none !important; }
            * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
        }
    </style>
</head>
<body id="bodyMain" class="bg-slate-100 text-black overflow-hidden relative min-h-screen flex items-center justify-center p-4">

    @php
        $isPaid = strtoupper($nota->status ?? 'UNPAID') === 'PAID';
    @endphp

    <!-- Modal PIN (Kunci Dokumen) -->
    <div id="pinValidationModal" class="fixed inset-0 bg-slate-900/80 backdrop-blur-md z-[10000] flex items-center justify-center p-4 transition-opacity duration-300">
        <div class="bg-white rounded-2xl shadow-2xl p-8 max-w-sm w-full text-center relative z-[10001]">
            <div class="w-16 h-16 bg-slate-100 rounded-full flex items-center justify-center mx-auto mb-4 border-2 border-slate-200">
                <i class="fas fa-shield-alt text-2xl text-gray-700"></i>
            </div>
            <h3 class="text-xl font-black text-gray-900 mb-2">Nota Terkunci</h3>

            <p class="text-sm text-gray-500 mb-6 leading-relaxed">
                Masukkan <strong class="text-red-600">4 Angka Terakhir</strong> Nomor HP Anda untuk membuka tagihan ini.<br>
                <span class="inline-block mt-3 px-4 py-1.5 bg-slate-100 border border-slate-200 rounded-lg text-gray-700 font-mono tracking-widest text-[14px] font-bold shadow-sm">
                    {{ $kisiKisiHp }}
                </span>
            </p>

            <input type="password" id="pinInput" maxlength="4" pattern="\d*" inputmode="numeric" class="w-full text-center text-3xl tracking-[0.5em] font-bold border-2 border-gray-200 rounded-xl p-4 mb-2 focus:outline-none focus:border-black focus:ring-4 focus:ring-gray-100 transition-all" placeholder="••••">
            <p id="pinError" class="text-xs text-red-600 mb-4 hidden font-bold animate-pulse"><i class="fas fa-exclamation-circle mr-1"></i> PIN Salah! Silakan coba lagi.</p>

            <button onclick="verifyPin()" id="btnVerify" class="w-full bg-black text-white font-bold py-3.5 rounded-xl hover:bg-gray-800 transition-colors shadow-md mt-2">
                <i class="fas fa-unlock-alt mr-2"></i> Buka Tagihan
            </button>
        </div>
    </div>

    <!-- Wrapper Konten Utama -->
    <div id="mainContentWrapper" class="w-full max-w-2xl bg-white rounded-2xl shadow-xl p-8 blur-xl opacity-20 pointer-events-none select-none transition-all duration-700 relative z-40 overflow-hidden">

        <!-- PITA STATUS DITAMPILKAN SELALU -->
        <div class="ribbon-wrapper">
            <div class="ribbon {{ $isPaid ? 'paid' : 'unpaid' }}">
                {{ $isPaid ? 'LUNAS' : 'UNPAID' }}
            </div>
        </div>

        <!-- ✅ TAMBAHAN: Tombol Aksi Print & PDF (Akan otomatis disembunyikan saat di-print) -->
        <div class="flex flex-wrap gap-2 justify-end mb-6 no-print border-b border-gray-100 pb-4 pr-24 sm:pr-28 relative z-50">
            <button onclick="window.print()" class="flex items-center gap-2 px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-bold rounded-lg transition-colors border border-gray-200 shadow-sm">
                <i class="fas fa-print"></i> Cetak Print
            </button>
            <button onclick="downloadPDF()" id="btnDownloadPdf" class="flex items-center gap-2 px-4 py-2 bg-red-50 text-red-600 hover:bg-red-100 text-sm font-bold rounded-lg transition-colors border border-red-200 shadow-sm">
                <i class="fas fa-file-pdf"></i> Unduh PDF
            </button>
        </div>

        <div class="flex flex-col sm:flex-row justify-between items-center border-b border-gray-100 pb-6 mb-6">
            <div class="flex items-center gap-4 mb-4 sm:mb-0">
                <img src="https://tokosancaka.com/storage/uploads/sancaka.png" alt="Sancaka" class="h-14 object-contain">
                <div>
                    <h1 class="text-lg font-black uppercase">SANCAKA EXPRESS</h1>
                    <p class="text-xs text-gray-500">Powered By CV Sancaka Karya Hutama</p>
                    <p class="text-xs text-gray-500">Jl.Dr.Wahidin No.18A Ketanggi Ngawi Jawa Timur 63211</p>
                </div>
            </div>
           <div class="text-right pr-12 sm:pr-16 relative z-50">
                <p class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-1">NO. NOTA</p>
                <p class="text-md font-bold text-black">{{ $nota->no_nota }}</p>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-4 mb-8">
            <div class="bg-slate-50 p-4 rounded-xl border border-gray-100">
                <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Kepada Yth:</p>
                <p class="font-bold text-sm text-black">{{ $nota->kepada }}</p>
                <p class="text-xs text-gray-500 mt-1">{{ $nota->nama_pembeli }}</p>
            </div>
            <div class="bg-slate-50 p-4 rounded-xl border border-gray-100 text-right">
                <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Tanggal Terbit</p>
                <p class="font-bold text-sm text-black">{{ \Carbon\Carbon::parse($nota->tanggal)->format('d F Y') }}</p>
            </div>
        </div>

        <div class="mb-8">
            <table class="w-full text-sm text-left border-collapse">
                <thead class="bg-slate-100 border-y border-gray-200">
                    <tr>
                        <th class="py-3 px-4 font-bold text-black uppercase text-[11px] tracking-wider">Item / Barang</th>
                        <th class="py-3 px-4 font-bold text-black uppercase text-[11px] tracking-wider text-center">Qty</th>
                        <th class="py-3 px-4 font-bold text-black uppercase text-[11px] tracking-wider text-right">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($nota->items as $item)
                    <tr>
                        <td class="py-4 px-4 font-semibold text-gray-800">{{ $item->nama_barang }}<br><span class="text-xs text-gray-400 font-normal">@ Rp {{ number_format($item->harga, 0, ',', '.') }}</span></td>
                        <td class="py-4 px-4 text-center text-gray-600">{{ $item->banyaknya }}</td>
                        <td class="py-4 px-4 text-right font-bold text-black">Rp {{ number_format($item->jumlah, 0, ',', '.') }}</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot class="bg-slate-50 border-y border-gray-200">
                    <tr>
                        <td colspan="2" class="py-4 px-4 text-right font-bold text-gray-500 uppercase tracking-widest text-[11px]">Grand Total</td>
                        <td class="py-4 px-4 text-right font-black text-red-600 text-lg">Rp {{ number_format($nota->total_harga, 0, ',', '.') }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <!-- AREA PEMBAYARAN DINAMIS -->
        <div class="mt-6 border-t border-gray-200 pt-6">
            @if (session('error'))
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4 text-sm no-print" role="alert">
                    <strong class="font-bold">Error!</strong>
                    <span class="block sm:inline">{{ session('error') }}</span>
                </div>
            @endif

            @if($isPaid)
                <div class="text-center">
                    <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-green-100 mb-4">
                        <i class="fas fa-check text-2xl text-green-500"></i>
                    </div>
                    <h3 class="text-xl font-bold text-black mb-1">Pembayaran Selesai</h3>
                    <p class="text-sm text-gray-500">Terima kasih, pembayaran untuk Nota ini telah kami terima.</p>
                </div>
            @elseif(!empty($nota->payment_url))
                <!-- Jika URL/QRIS Pembayaran Sudah Di-Generate -->
                <div class="bg-slate-50 border border-gray-200 rounded-xl p-6 text-center">
                    <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-2">Menunggu Pembayaran</p>

                    @if($nota->payment_method === 'BCA_QRIS')
                        <p class="text-sm font-semibold text-black mb-4">Scan QRIS BCA di Bawah Ini</p>
                        <div class="bg-white p-3 rounded-lg border border-gray-200 inline-block mb-3 shadow-sm">
                            <div id="qrcode" class="flex justify-center mx-auto"></div>
                        </div>
                    @else
                        <p class="text-sm font-semibold text-black mb-4">Lanjutkan Pembayaran via {{ str_replace('_', ' ', $nota->payment_method) }}</p>
                        <a href="{{ $nota->payment_url }}" class="inline-block w-full sm:w-auto bg-black text-white font-bold py-3 px-8 rounded-lg shadow-lg hover:bg-gray-800 transition-colors no-print">
                            <i class="fas fa-external-link-alt mr-2"></i> Lanjutkan Pembayaran
                        </a>
                    @endif
                    <p class="text-xs text-gray-400 mt-4"><i class="fas fa-info-circle mr-1"></i> Harap segera selesaikan pembayaran agar pesanan dapat diproses.</p>
                </div>
            @else
                <!-- Form Pilih Metode Pembayaran ditambahkan class no-print agar dropdown tidak tercetak di kertas fisik -->
                <div class="no-print">
                    <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-2">Selesaikan Pembayaran</p>
                    <form id="invoice-payment-form" action="{{ route('nota.proses_bayar', $nota->no_nota) }}" method="POST">
                        @csrf
                        <button type="button" id="paymentMethodButton" class="w-full bg-white border border-gray-300 hover:border-black p-4 rounded-xl flex items-center justify-between transition-colors mb-4 shadow-sm group">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 flex items-center justify-center">
                                    <img id="paymentMethodImg" src="https://tokosancaka.com/public/assets/saldo.png" class="max-w-full max-h-full">
                                </div>
                                <span id="paymentMethodLabel" class="text-sm font-bold text-black">Pilih Metode Pembayaran...</span>
                            </div>
                            <i class="fas fa-chevron-down text-gray-400 group-hover:text-black"></i>
                        </button>

                        <input type="hidden" name="payment_method" id="payment_method" required>

                        <button type="submit" id="submit-button" class="w-full bg-green-500 text-white font-bold py-4 rounded-xl shadow-lg hover:bg-green-600 transition-all disabled:opacity-50">
                            <i class="fas fa-wallet mr-2"></i> Bayar Tagihan Sekarang
                        </button>
                    </form>
                    <p class="text-xs text-center text-gray-400 mt-4"><i class="fas fa-lock mr-1"></i> Pembayaran dienkripsi dan dijamin aman.</p>
                </div>
            @endif
        </div>

    </div>

    <!-- MODAL PEMBAYARAN -->
    <div id="paymentModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-[10500] hidden transition-all duration-300 p-4 no-print">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-3xl flex flex-col max-h-[85vh]">
            <div class="flex justify-between items-center p-5 border-b border-gray-100">
                <h3 class="text-lg font-bold text-black tracking-wide">Pilih Metode Pembayaran</h3>
                <button type="button" id="closeModalButton" class="text-gray-400 hover:text-black transition-colors p-1">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
            <div class="p-5 overflow-y-auto custom-scrollbar flex-1 bg-slate-50">
                <ul id="paymentOptionsList" class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                    <li class="col-span-full pb-1 text-[11px] font-bold text-gray-400 uppercase tracking-widest border-b border-gray-200">Direct Payment</li>
                    <li class="payment-option cursor-pointer flex items-center p-3 border border-gray-200 rounded-xl bg-white hover:border-black hover:shadow-md transition-all group" data-value="BCA_QRIS" data-label="BCA QRIS" data-img="https://tokosancaka.com/assets/bca.png">
                        <img src="https://tokosancaka.com/assets/bca.png" class="w-12 h-auto mr-4 object-contain" alt="BCA">
                        <div class="flex flex-col"><span class="text-[13px] font-bold text-black">BCA QRIS</span><span class="text-[11px] text-gray-500">Generate Barcode</span></div>
                    </li>
                    <li class="payment-option cursor-pointer flex items-center p-3 border border-gray-200 rounded-xl bg-white hover:border-black hover:shadow-md transition-all group" data-value="DOKU_JOKUL" data-label="DOKU Gateway" data-img="https://tokosancaka.com/public/assets/doku.png">
                        <img src="https://tokosancaka.com/public/assets/doku.png" class="w-12 h-auto mr-4 object-contain" alt="DOKU">
                        <div class="flex flex-col"><span class="text-[13px] font-bold text-black">DOKU Gateway</span><span class="text-[11px] text-gray-500">VA, E-Wallet, CC Lokal</span></div>
                    </li>

                    <li class="col-span-full pt-4 pb-1 text-[11px] font-bold text-gray-400 uppercase tracking-widest border-b border-gray-200">DANA Enterprise</li>

                    <li class="payment-option cursor-pointer flex items-center p-3 border border-gray-200 rounded-xl bg-white hover:border-black hover:shadow-md transition-all group" data-value="DANA" data-label="DANA Checkout" data-img="{{ asset('public/assets/dana.webp') }}">
                        <img src="{{ asset('public/assets/dana.webp') }}" class="w-12 h-auto mr-4 object-contain" onerror="this.src='https://upload.wikimedia.org/wikipedia/commons/7/72/Logo_dana_blue.svg'">
                        <div class="flex flex-col">
                            <span class="text-[13px] font-bold text-black">DANA Web / App</span>
                            <span class="text-[11px] text-gray-500">Arahkan ke aplikasi DANA</span>
                        </div>
                    </li>

                    <li class="col-span-full pt-4 pb-1 text-[11px] font-bold text-gray-400 uppercase tracking-widest border-b border-gray-200">Global & Otomatis</li>
                    <li class="payment-option cursor-pointer flex items-center p-3 border border-gray-200 rounded-xl bg-white hover:border-black hover:shadow-md transition-all group" data-value="PAYPAL" data-label="PayPal" data-img="https://tokosancaka.com/public/assets/paypal.png">
                        <img src="https://tokosancaka.com/public/assets/paypal.png" class="w-12 h-auto mr-4 object-contain" onerror="this.src='https://placehold.co/40x40/EFEFEF/AAAAAA?text=PP'">
                        <div class="flex flex-col"><span class="text-[13px] font-bold text-black">PayPal / CC</span><span class="text-[11px] text-gray-500">Pembayaran USD</span></div>
                    </li>

                    @if(isset($tripayChannels) && count($tripayChannels) > 0)
                        @foreach($tripayChannels as $channel)
                            @if($channel['active'])
                            <li class="payment-option cursor-pointer flex items-center p-3 border border-gray-200 rounded-xl bg-white hover:border-black hover:shadow-md transition-all group" data-value="{{ $channel['code'] }}" data-label="{{ $channel['name'] }}" data-img="{{ $channel['icon_url'] }}">
                                <img src="{{ $channel['icon_url'] }}" class="w-12 h-auto mr-4 object-contain" onerror="this.src='https://placehold.co/40x40/EFEFEF/AAAAAA?text=IMG'">
                                <div class="flex flex-col"><span class="text-[13px] font-bold text-black">{{ $channel['name'] }}</span></div>
                            </li>
                            @endif
                        @endforeach
                    @endif
                </ul>
            </div>
        </div>
    </div>

    <script>
        const correctPin = "{{ $pinRahasia }}";

        function verifyPin() {
            const pinInput = document.getElementById('pinInput');
            const pinError = document.getElementById('pinError');
            const pinModal = document.getElementById('pinValidationModal');
            const mainContent = document.getElementById('mainContentWrapper');

            if (pinInput.value === correctPin) {
                pinModal.style.opacity = '0';
                setTimeout(() => {
                    pinModal.remove();
                    document.getElementById('bodyMain').classList.remove('overflow-hidden', 'flex', 'items-center', 'justify-center');
                    mainContent.classList.remove('blur-xl', 'opacity-20', 'pointer-events-none', 'select-none');
                    mainContent.classList.add('mx-auto', 'my-8');

                    // Render QR Code JIKA ada BCA QRIS
                    @if(!$isPaid && $nota->payment_method === 'BCA_QRIS' && !empty($nota->payment_url))
                        new QRCode(document.getElementById("qrcode"), {
                            text: "{{ $nota->payment_url }}",
                            width: 200,
                            height: 200,
                            colorDark : "#000000",
                            colorLight : "#ffffff",
                            correctLevel : QRCode.CorrectLevel.M
                        });
                    @endif

                }, 300);
            } else {
                pinError.classList.remove('hidden');
                pinInput.value = '';
                pinInput.focus();
                pinInput.classList.add('translate-x-[-10px]', 'border-red-500');
                setTimeout(() => pinInput.classList.remove('translate-x-[-10px]', 'border-red-500'), 150);
            }
        }

        // ✅ TAMBAHAN: Fungsi Konversi HTML ke PDF murni bawaan tampilan
        async function downloadPDF() {
            const btnPdf = document.getElementById('btnDownloadPdf');
            const originalText = btnPdf.innerHTML;

            // Ubah teks tombol jadi loading
            btnPdf.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Memproses...';
            btnPdf.disabled = true;

            // Sembunyikan elemen dengan class 'no-print' agar tidak ikut di-screenshot
            const noPrintElements = document.querySelectorAll('.no-print');
            noPrintElements.forEach(el => el.style.display = 'none');

            const element = document.getElementById('mainContentWrapper');

            try {
                // Potret tampilan div menggunakan html2canvas
                const canvas = await html2canvas(element, {
                    scale: 2, // Resolusi tinggi 2x lipat
                    useCORS: true, // Izinkan ambil gambar eksternal (logo/QR)
                    backgroundColor: '#ffffff'
                });

                const imgData = canvas.toDataURL('image/jpeg', 1.0);
                const { jsPDF } = window.jspdf;

                // Set ukuran kertas jadi A4
                const pdf = new jsPDF('p', 'mm', 'a4');
                const pdfWidth = pdf.internal.pageSize.getWidth();
                // Hitung proporsi tinggi agar tidak gepeng
                const pdfHeight = (canvas.height * pdfWidth) / canvas.width;

                pdf.addImage(imgData, 'JPEG', 0, 0, pdfWidth, pdfHeight);
                pdf.save(`Nota_{{ $nota->no_nota }}.pdf`);
            } catch (error) {
                console.error("Error generating PDF", error);
                alert("Gagal membuat PDF. Pastikan koneksi internet stabil.");
            } finally {
                // Kembalikan lagi elemen yang disembunyikan
                noPrintElements.forEach(el => el.style.display = '');
                btnPdf.innerHTML = originalText;
                btnPdf.disabled = false;
            }
        }

        document.addEventListener('DOMContentLoaded', function () {
            // Setup PIN logic
            const pinInput = document.getElementById('pinInput');
            pinInput.focus();
            pinInput.addEventListener('keypress', function (e) {
                if (e.key === 'Enter') verifyPin();
            });
            pinInput.addEventListener('input', function() {
                this.value = this.value.replace(/[^0-9]/g, '');
                if(this.value.length === 4) verifyPin();
            });

            // Setup Modal Pembayaran Logic
            const paymentModal = document.getElementById('paymentModal');
            const paymentMethodButton = document.getElementById('paymentMethodButton');
            const closeModalButton = document.getElementById('closeModalButton');
            const paymentOptionsList = document.getElementById('paymentOptionsList');
            const paymentMethodInput = document.getElementById('payment_method');
            const invoiceForm = document.getElementById('invoice-payment-form');
            const submitButton = document.getElementById('submit-button');

            function openPaymentModal() { paymentModal.classList.remove('hidden'); document.body.style.overflow = 'hidden'; }
            function closePaymentModal() { paymentModal.classList.add('hidden'); document.body.style.overflow = 'auto'; }

            if(paymentMethodButton) {
                paymentMethodButton.addEventListener('click', openPaymentModal);
                closeModalButton.addEventListener('click', closePaymentModal);
                paymentModal.addEventListener('click', e => { if (e.target === paymentModal) closePaymentModal(); });

                paymentOptionsList.querySelectorAll('.payment-option').forEach(item => {
                    item.addEventListener('click', function () {
                        paymentMethodInput.value = this.dataset.value;
                        paymentOptionsList.querySelectorAll('.payment-option').forEach(li => {
                            li.classList.remove('border-black', 'ring-1', 'ring-black');
                            li.classList.add('border-gray-200');
                        });
                        this.classList.remove('border-gray-200');
                        this.classList.add('border-black', 'ring-1', 'ring-black');

                        document.getElementById('paymentMethodLabel').textContent = this.dataset.label;
                        document.getElementById('paymentMethodImg').src = this.dataset.img;
                        closePaymentModal();
                    });
                });

                invoiceForm.addEventListener('submit', function(e) {
                    if (paymentMethodInput.value === "") {
                        e.preventDefault();
                        alert('Silakan pilih metode pembayaran terlebih dahulu.');
                        return;
                    }
                    submitButton.disabled = true;
                    submitButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Memproses...';
                });
            }
        });
    </script>
</body>
</html>
