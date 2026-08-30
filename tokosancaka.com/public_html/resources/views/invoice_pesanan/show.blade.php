<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice #{{ $pesanan->nomor_invoice }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        /* Desain Pita (Ribbon) UNPAID / LUNAS */
        .ribbon-wrapper {
            position: absolute;
            right: -5px; top: -5px;
            z-index: 10;
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
            background: #dc2626; /* red-600 */
            position: absolute;
            top: 25px; right: -45px;
            box-shadow: 0 3px 10px -5px rgba(0, 0, 0, 1);
        }
        .ribbon.paid { background: #16a34a; /* green-600 */ }
        
        @media print {
            .no-print { display: none !important; }
            body { background: white; }
            .print-container { box-shadow: none; max-width: 100%; margin: 0; padding: 0; border: none; }
        }

        /* Custom Scrollbar untuk Modal */
        .custom-scrollbar::-webkit-scrollbar { width: 6px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: #f1f1f1; border-radius: 8px; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 8px; }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
    </style>
</head>
<body class="bg-gray-100 py-10 font-sans text-gray-800">

    @php
        // 1. Parsing Helper Ekspedisi
        $ship = \App\Helpers\ShippingHelper::parseShippingMethod($pesanan->expedition);
        $expeditionName = $ship['courier_name'] ?? 'SANCAKA'; 
        $expeditionService = $ship['service_name'] ?? 'Regular';

        // 2. Format Alamat Lengkap
        $senderAddress = implode(', ', array_filter([
            $pesanan->sender_address, $pesanan->sender_village, 
            $pesanan->sender_district, $pesanan->sender_regency, 
            $pesanan->sender_province, $pesanan->sender_postal_code
        ]));

        $receiverAddress = implode(', ', array_filter([
            $pesanan->receiver_address, $pesanan->receiver_village, 
            $pesanan->receiver_district, $pesanan->receiver_regency, 
            $pesanan->receiver_province, $pesanan->receiver_postal_code
        ]));
    @endphp

    <div class="max-w-4xl mx-auto bg-white p-10 print-container relative shadow-lg border border-gray-200">
        
        <!-- PITA STATUS (UNPAID / LUNAS) -->
        <div class="ribbon-wrapper no-print">
            <div class="ribbon {{ $statusLunas ? 'paid' : '' }}">
                {{ $statusLunas ? 'LUNAS' : 'UNPAID' }}
            </div>
        </div>

        <!-- HEADER INVOICE -->
        <div class="flex justify-between items-start mb-8">
            <div>
                <img src="https://tokosancaka.com/storage/uploads/sancaka.png" alt="Sancaka Express" class="h-16 mb-4 object-contain" onerror="this.src='https://placehold.co/200x50?text=Logo+Sancaka'">
            </div>
            <div class="text-right text-sm">
                <p class="font-bold text-lg">Sancaka Express</p>
                <p>Jl. Dr. Wahidin No. 18A</p>
                <p>Kabupaten Ngawi, Jawa Timur 63211</p>
                <p>Indonesia</p>
            </div>
        </div>

        <!-- INVOICE INFO BLOCK -->
        <div class="bg-gray-100 p-4 border border-gray-300 mb-8 text-sm grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <h2 class="text-xl font-bold mb-2">Invoice #{{ $pesanan->nomor_invoice }}</h2>
                <p><strong>Tanggal Order:</strong> {{ \Carbon\Carbon::parse($pesanan->tanggal_pesanan)->format('d/m/Y H:i') }}</p>
                <p><strong>Batas Bayar:</strong> {{ \Carbon\Carbon::parse($pesanan->tanggal_pesanan)->addDays(1)->format('d/m/Y H:i') }}</p>
            </div>
            <div class="md:text-right">
                <!-- LOGIKA TAMPIL RESI (HANYA MUNCUL JIKA LUNAS) -->
                @if($statusLunas && $pesanan->resi)
                    <p class="text-sm"><strong>No. Resi (AWB):</strong></p>
                    <p class="text-xl font-bold text-blue-600">{{ $pesanan->resi }}</p>
                @else
                    <p class="text-sm"><strong>No. Resi (AWB):</strong></p>
                    <p class="text-sm italic text-gray-500 bg-gray-200 inline-block px-2 py-1 rounded mt-1">Diterbitkan setelah lunas</p>
                @endif
            </div>
        </div>

        <!-- PENGIRIM & PENERIMA -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8 text-sm">
            <!-- PENGIRIM -->
            <div>
                <p class="font-bold mb-1 border-b border-gray-300 pb-1">Pengirim (Invoiced To)</p>
                <p class="font-bold uppercase text-red-600">{{ $pesanan->sender_name }}</p>
                <p class="text-gray-600 mt-1 leading-snug">{{ $senderAddress }}</p>
                <p class="mt-1"><i class="fas fa-phone-alt text-gray-400 mr-1"></i> {{ $pesanan->sender_phone }}</p>
            </div>
            
            <!-- PENERIMA -->
            <div>
                <p class="font-bold mb-1 border-b border-gray-300 pb-1">Penerima (Ship To)</p>
                <p class="font-bold uppercase text-green-600">{{ $pesanan->receiver_name }}</p>
                <p class="text-gray-600 mt-1 leading-snug">{{ $receiverAddress }}</p>
                <p class="mt-1"><i class="fas fa-phone-alt text-gray-400 mr-1"></i> {{ $pesanan->receiver_phone }}</p>
            </div>
        </div>

        <!-- RINCIAN TAGIHAN TABLE -->
        <table class="w-full text-sm border-collapse border border-gray-300 mb-8">
            <thead>
                <tr class="bg-gray-100">
                    <th class="border border-gray-300 px-4 py-2 text-left font-bold w-3/4">Detail Pesanan & Layanan</th>
                    <th class="border border-gray-300 px-4 py-2 text-center font-bold w-1/4">Total</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="border border-gray-300 px-4 py-3">
                        <p class="font-bold mb-1">Pengiriman Paket: {{ $pesanan->item_description }}</p>
                        <ul class="text-gray-600 space-y-1 list-disc list-inside ml-1">
                            <li><strong>Ekspedisi:</strong> {{ strtoupper($expeditionName) }} - {{ strtoupper($expeditionService) }}</li>
                            <li><strong>Berat Aktual:</strong> {{ $pesanan->weight }} gram</li>
                            <li><strong>Dimensi (PxLxT):</strong> {{ $pesanan->length ?? 0 }} x {{ $pesanan->width ?? 0 }} x {{ $pesanan->height ?? 0 }} cm</li>
                            <li><strong>Nilai Barang:</strong> Rp {{ number_format($pesanan->item_price, 0, ',', '.') }}</li>
                        </ul>
                    </td>
                    <td class="border border-gray-300 px-4 py-3 text-right align-top font-medium">
                        Rp {{ number_format($pesanan->shipping_cost, 0, ',', '.') }}
                    </td>
                </tr>
                
                @if($pesanan->insurance_cost > 0)
                <tr>
                    <td class="border border-gray-300 px-4 py-2">Biaya Asuransi Pengiriman</td>
                    <td class="border border-gray-300 px-4 py-2 text-right">Rp {{ number_format($pesanan->insurance_cost, 0, ',', '.') }}</td>
                </tr>
                @endif
                
                @if($pesanan->cod_fee > 0)
                <tr>
                    <td class="border border-gray-300 px-4 py-2">Biaya Penanganan (Fee COD)</td>
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
                    <td class="border border-gray-300 px-4 py-3 text-right font-bold bg-gray-200 text-base">Grand Total</td>
                    <td class="border border-gray-300 px-4 py-3 text-right font-bold bg-gray-200 text-base text-red-600">Rp {{ number_format($pesanan->price, 0, ',', '.') }}</td>
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
                    <td class="border border-gray-300 px-4 py-2 uppercase">{{ str_replace('_', ' ', $pesanan->payment_method) }}</td>
                    <td class="border border-gray-300 px-4 py-2">{{ $pesanan->nomor_invoice }}</td>
                    <td class="border border-gray-300 px-4 py-2 text-right text-green-600 font-medium">Rp {{ number_format($pesanan->price, 0, ',', '.') }}</td>
                </tr>
                @else
                <tr>
                    <td colspan="4" class="border border-gray-300 px-4 py-4 italic text-gray-500">No Related Transactions Found</td>
                </tr>
                @endif
                <tr>
                    <td colspan="3" class="border border-gray-300 px-4 py-2 text-right font-bold bg-gray-50">Balance Due</td>
                    <td class="border border-gray-300 px-4 py-2 text-right font-bold bg-gray-50 {{ !$statusLunas ? 'text-red-600' : 'text-gray-900' }}">
                        Rp {{ $statusLunas ? '0,00' : number_format($pesanan->price, 0, ',', '.') }}
                    </td>
                </tr>
            </tbody>
        </table>

        <div class="text-center text-xs text-gray-500 mb-8">
            PDF Generated on {{ date('d M Y, H:i') }} WIB
        </div>

        <!-- ======================================================== -->
        <!-- AREA PEMBAYARAN (HANYA MUNCUL JIKA UNPAID)               -->
        <!-- ======================================================== -->
        @if(session('error'))
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4 text-center font-medium shadow-sm">
                <i class="fas fa-exclamation-triangle mr-1"></i> {{ session('error') }}
            </div>
        @endif

        <!-- 1. JIKA BELUM MEMILIH METODE PEMBAYARAN SAMA SEKALI -->
        @if(!$statusLunas && empty($pesanan->payment_url) && !in_array($pesanan->payment_method, ['COD', 'CODBARANG', 'Cash', 'Potong Saldo']))
        <div class="no-print border-t border-gray-300 pt-6">
            <form id="invoice-payment-form" action="{{ route('invoice.proses_bayar', $pesanan->nomor_invoice) }}" method="POST" class="max-w-2xl mx-auto">
                @csrf
                
                <!-- TOMBOL TRIGGER MODAL -->
                <div class="bg-gray-50 rounded-xl shadow-sm border border-gray-200 p-5 mb-6 relative">
                    <h2 class="text-sm font-bold text-gray-700 mb-3 uppercase tracking-wider text-center">Pilih Metode Pembayaran</h2>
                    
                    <button type="button" id="paymentMethodButton" class="flex items-center justify-between w-full bg-white border border-gray-300 p-4 rounded-lg cursor-pointer hover:border-red-500 hover:shadow-md focus:outline-none transition-all">
                        <div class="flex items-center">
                            <img id="paymentMethodImg" src="https://tokosancaka.com/public/assets/saldo.png" alt="Logo" class="h-8 w-12 object-contain mr-4 border rounded p-1">
                            <span id="paymentMethodLabel" class="text-sm font-bold text-gray-900">Klik di sini untuk memilih bank...</span>
                        </div>
                        <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    
                    <input type="hidden" name="payment_method" id="payment_method" required>
                </div>
                
                <div class="text-center">
                    <button type="submit" id="submit-button" class="bg-red-600 hover:bg-red-700 text-white font-bold py-3 px-10 rounded-lg text-lg transition duration-300 w-full md:w-2/3 mx-auto shadow-md disabled:opacity-50 disabled:cursor-not-allowed">
                        <i class="fas fa-lock mr-2"></i> Bayar Sekarang
                    </button>
                </div>
            </form>
        </div>

        <!-- 2. JIKA SUDAH MEMILIH & ADA URL/QRIS -->
        @elseif(!$statusLunas && !empty($pesanan->payment_url))
        <div class="no-print border-t border-gray-300 pt-6 text-center">
            <h4 class="text-lg font-bold mb-4">Selesaikan Pembayaran Anda</h4>
            
            @if($pesanan->payment_method == 'BCA_QRIS')
                <p class="mb-2 text-sm text-gray-600">Scan QR Code di bawah ini menggunakan M-Banking / E-Wallet Anda:</p>
                <div class="flex justify-center mb-4">
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=250x250&data={{ urlencode($pesanan->payment_url) }}" alt="QRIS BCA" class="border p-2 rounded shadow">
                </div>
            @else
                <p class="mb-4 text-sm text-gray-600">Anda telah memilih pembayaran menggunakan <strong>{{ str_replace('_', ' ', strtoupper($pesanan->payment_method)) }}</strong></p>
                <a href="{{ $pesanan->payment_url }}" class="inline-block bg-green-600 hover:bg-green-700 text-white font-bold py-3 px-8 rounded text-lg transition duration-300 shadow">
                    Lanjutkan Pembayaran <i class="fas fa-arrow-right ml-2"></i>
                </a>
            @endif
        </div>
        
        <!-- 3. JIKA METODE OFFLINE (CASH/COD/SALDO) MENUNGGU KONFIRMASI ADMIN -->
        @elseif(!$statusLunas)
        <div class="no-print border-t border-gray-300 pt-6 text-center">
            <h4 class="text-lg font-bold mb-2">Menunggu Konfirmasi Pembayaran</h4>
            <p class="text-sm text-gray-600">Metode: <strong>{{ strtoupper($pesanan->payment_method) }}</strong></p>
            <p class="text-red-500 italic mt-2">Pesanan ini sedang dalam antrean verifikasi manual oleh admin Sancaka.</p>
        </div>
        @endif
        
        <!-- TOMBOL PRINT -->
        <div class="no-print absolute top-10 left-10">
            <button onclick="window.print()" class="bg-gray-800 text-white px-4 py-2 rounded text-sm hover:bg-gray-700 shadow">
                <i class="fas fa-print mr-1"></i> Print Invoice
            </button>
        </div>

    </div>

    <!-- ======================================================== -->
    <!-- MODAL PEMILIHAN PEMBAYARAN                               -->
    <!-- ======================================================== -->
    <div id="paymentModal" class="no-print fixed inset-0 bg-gray-900 bg-opacity-75 flex items-center justify-center z-50 hidden transition-opacity">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-3xl mx-4 transform transition-all flex flex-col max-h-[90vh]">

            <!-- Modal Header -->
            <div class="flex justify-between items-center p-5 border-b border-gray-200">
                <h3 class="text-lg font-bold text-gray-900">Pilih Metode Pembayaran</h3>
                <button type="button" id="closeModalButton" class="text-gray-400 hover:text-red-600 bg-gray-100 hover:bg-red-50 p-2 rounded-full transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>

            <!-- Modal Body (Custom Scrollbar) -->
            <div class="p-2 overflow-y-auto custom-scrollbar flex-1">
                <ul id="paymentOptionsList" class="grid grid-cols-1 md:grid-cols-2 gap-3 p-4">

                    <!-- DIRECT PAYMENT HEADER -->
                    <li class="col-span-full px-1 pt-2 pb-1 text-xs font-bold text-gray-500 uppercase tracking-wider border-b border-gray-100">
                        Direct Payment (Bebas Biaya Tripay)
                    </li>
                    
                    <!-- BCA QRIS -->
                    <li class="payment-option col-span-1 cursor-pointer flex items-center p-3 border rounded-lg hover:bg-red-50 transition-colors"
                        data-value="BCA_QRIS" data-label="BCA QRIS (Generate Barcode)" data-img="https://tokosancaka.com/assets/bca.png">
                        <img src="https://tokosancaka.com/assets/bca.png" class="h-8 w-12 object-contain mr-4 bg-white p-1 border rounded" alt="BCA">
                        <div class="flex flex-col">
                            <span class="text-sm font-bold text-gray-900">BCA QRIS</span>
                            <span class="text-[11px] text-gray-500 mt-0.5">Generate Barcode Pembayaran</span>
                        </div>
                    </li>

                    <!-- DOKU -->
                    <li class="payment-option col-span-1 cursor-pointer flex items-center p-3 border rounded-lg hover:bg-red-50 transition-colors"
                        data-value="DOKU_JOKUL" data-label="DOKU Payment Gateway" data-img="https://tokosancaka.com/public/assets/doku.png">
                        <img src="https://tokosancaka.com/public/assets/doku.png" class="h-8 w-12 object-contain mr-4 bg-white p-1 border rounded" alt="DOKU">
                        <div class="flex flex-col">
                            <span class="text-sm font-bold text-gray-900">DOKU Gateway</span>
                            <span class="text-[11px] text-gray-500 mt-0.5">VA, E-Wallet, Kartu Kredit Lokal</span>
                        </div>
                    </li>

                    <!-- E-WALLET & KARTU KREDIT GLOBAL -->
                    <li class="col-span-full px-1 pt-4 pb-1 text-xs font-bold text-gray-500 uppercase tracking-wider border-b border-gray-100">
                        Kartu Kredit Global & PayPal
                    </li>

                    <!-- PAYPAL -->
                    <li class="payment-option col-span-1 cursor-pointer flex items-center p-3 border rounded-lg hover:bg-red-50 transition-colors"
                        data-value="PAYPAL" data-label="PayPal / Credit Card" data-img="https://tokosancaka.com/public/assets/paypal.png">
                        <img src="https://tokosancaka.com/public/assets/paypal.png" alt="PayPal" class="h-8 w-12 object-contain mr-4 bg-white p-1 border rounded" onerror="this.src='https://placehold.co/32x32/EFEFEF/AAAAAA?text=PP'">
                        <div class="flex flex-col">
                            <span class="text-sm font-bold text-gray-900">PayPal / Kartu Kredit</span>
                            <span class="text-[11px] text-gray-500 mt-0.5">Pembayaran Global (Otomatis USD)</span>
                        </div>
                    </li>

                    <!-- TRIPAY CHANNELS -->
                    @if(isset($tripayChannels) && count($tripayChannels) > 0)
                    <li class="col-span-full px-1 pt-4 pb-1 text-xs font-bold text-gray-500 uppercase tracking-wider border-b border-gray-100">
                        Transfer Bank & Minimarket (Otomatis)
                    </li>
                    @foreach($tripayChannels as $channel)
                        @if($channel['active'])
                        <li class="payment-option col-span-1 cursor-pointer flex items-center p-3 border rounded-lg hover:bg-red-50 transition-colors"
                            data-value="{{ $channel['code'] }}" data-label="{{ $channel['name'] }}" data-img="{{ $channel['icon_url'] }}">
                            <img src="{{ $channel['icon_url'] }}" alt="{{ $channel['name'] }}" class="h-8 w-12 object-contain mr-4 bg-white p-1 border rounded" onerror="this.src='https://placehold.co/32x32?text=IMG'">
                            <div class="flex flex-col">
                                <span class="text-sm font-bold text-gray-900">{{ $channel['name'] }}</span>
                                <span class="text-[11px] text-gray-500 mt-0.5">Tripay Payment</span>
                            </div>
                        </li>
                        @endif
                    @endforeach
                    @endif
                </ul>
            </div>
        </div>
    </div>

    <!-- JAVASCRIPT MODAL PEMBAYARAN -->
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const paymentModal = document.getElementById('paymentModal');
        const paymentMethodButton = document.getElementById('paymentMethodButton');
        const closeModalButton = document.getElementById('closeModalButton');
        const paymentOptionsList = document.getElementById('paymentOptionsList');
        const paymentMethodInput = document.getElementById('payment_method');
        const invoiceForm = document.getElementById('invoice-payment-form');
        const submitButton = document.getElementById('submit-button');

        // Fungsi Buka Modal
        function openPaymentModal() {
            paymentModal.classList.remove('hidden');
            document.body.style.overflow = 'hidden'; 
        }

        // Fungsi Tutup Modal
        function closePaymentModal() {
            paymentModal.classList.add('hidden');
            document.body.style.overflow = 'auto'; 
        }

        if(paymentMethodButton) {
            paymentMethodButton.addEventListener('click', openPaymentModal);
            closeModalButton.addEventListener('click', closePaymentModal);
            
            // Tutup jika klik area background gelap
            paymentModal.addEventListener('click', function(e) {
                if (e.target === paymentModal) {
                    closePaymentModal();
                }
            });

            // Logika Klik Opsi Pembayaran di Modal
            paymentOptionsList.querySelectorAll('.payment-option').forEach(item => {
                item.addEventListener('click', function () {
                    const paymentValue = this.dataset.value;
                    const label = this.dataset.label;
                    const img = this.dataset.img;

                    // Isi hidden input
                    paymentMethodInput.value = paymentValue;

                    // Hilangkan highlight dari semua opsi
                    paymentOptionsList.querySelectorAll('.payment-option').forEach(li => {
                        li.classList.remove('bg-red-50', 'border-red-500');
                    });
                    
                    // Beri highlight pada opsi yang dipilih
                    this.classList.add('bg-red-50', 'border-red-500');

                    // Update UI Tombol Utama
                    document.getElementById('paymentMethodLabel').textContent = label;
                    document.getElementById('paymentMethodImg').src = img;

                    closePaymentModal();
                });
            });

            // Validasi dan Loading State saat Form disubmit
            invoiceForm.addEventListener('submit', function(e) {
                if (paymentMethodInput.value === "") {
                    e.preventDefault();
                    alert('Silakan ketuk tombol untuk memilih metode pembayaran terlebih dahulu.');
                    return;
                }
                submitButton.disabled = true;
                submitButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Mengarahkan ke Gateway...';
            });
        }
    });
    </script>
</body>
</html>