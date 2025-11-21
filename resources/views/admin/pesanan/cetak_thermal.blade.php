<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="icon" type="image/png" href="https://tokosancaka.com/storage/uploads/sancaka.png">
    
    <title>Cetak Resi - {{ $pesanan->resi }}</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;900&display=swap" rel="stylesheet">
    
    {{-- LIBRARY WAJIB: JSBARCODE (1D) --}}
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
    {{-- LIBRARY WAJIB: QRCODE.JS (2D) --}}
    <script src="https://cdn.jsdelivr.net/npm/qrcodejs/qrcode.min.js"></script> 

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    {{-- WAJIB UNTUK AJAX FONTTE --}}
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #F3F4F6;
            color: #111827;
        }
        .page {
            width: 100mm;
            min-height: 150mm;
            padding: 6mm;
            margin: 10mm auto;
            background: #fff;
            border: 1px solid #E5E7EB;
            border-radius: 6px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            display: flex;
            flex-direction: column;
            font-size: 8pt;
        }
        /* Penyesuaian Mobile */
        @media (max-width: 640px) {
            .page {
                margin: 5px auto;
                box-shadow: none;
                border: none;
            }
        }
        .barcode { 
            width: 100%; 
            height: 50px; 
            /* Tambahkan sedikit margin vertikal agar tidak terlalu mepet dengan tulisan di atas/bawahnya */
            margin-top: 5px; 
            margin-bottom: 5px;
        }
        .label { font-weight: 600; font-size: 12px; color: #374151; }
        .value { font-weight: 500; font-size: 9px; }
        @media print {
            body { background: none; }
            .no-print { display: none; }
            .page {
                margin: 0;
                border: none;
                border-radius: 0;
                width: 100mm;
                min-height: 150mm;
                box-shadow: none;
                page-break-after: always;
            }
        }
    </style>
</head>
<body>

    <div class="no-print p-3 bg-white border-b border-gray-200 shadow-sm sticky top-0 z-10">
        @php
            // Logika Auth: Menentukan Back URL
            $backUrl = url()->previous();

            if (Auth::check()) {
                if (Auth::user()->hasRole('Admin')) {
                    $backUrl = route('admin.pesanan.index');
                } elseif (Auth::user()->hasRole('Pelanggan')) {
                    $backUrl = route('customer.pesanan.index');
                }
            }
        @endphp

        <div class="flex flex-wrap justify-center gap-2 max-w-5xl mx-auto">
            
            <button onclick="window.print()" class="w-full sm:w-auto lg:flex-none lg:min-w-[150px] bg-red-600 text-white px-5 py-2 rounded-md shadow hover:bg-red-700 transition flex justify-center items-center">
                <i class="fas fa-print mr-1"></i> Cetak Resi
            </button>
            
            <button id="downloadBtn" class="w-full sm:w-auto lg:flex-none lg:min-w-[150px] bg-blue-600 text-white px-5 py-2 rounded-md shadow hover:bg-blue-700 transition flex justify-center items-center">
                <i class="fas fa-download mr-2"></i> Download JPG
            </button>
            
            <button onclick="sendWaNotificationApi('receiver')" class="w-full sm:w-auto lg:flex-none lg:min-w-[150px] bg-green-600 text-white px-5 py-2 rounded-md shadow hover:bg-green-700 transition flex justify-center items-center">
                <i class="fab fa-whatsapp mr-1"></i> Kirim WA (Penerima)
            </button>
            
            <button onclick="sendWaNotificationApi('sender')" class="w-full sm:w-auto lg:flex-none lg:min-w-[150px] bg-green-600 text-white px-5 py-2 rounded-md shadow hover:bg-green-700 transition flex justify-center items-center">
                <i class="fab fa-whatsapp mr-1"></i> Kirim WA (Pengirim)
            </button>
            
            <a href="{{ $backUrl }}" class="w-full sm:w-auto lg:flex-none lg:min-w-[150px] bg-gray-200 text-gray-800 px-5 py-2 rounded-md shadow hover:bg-gray-300 transition flex justify-center items-center">
                <i class="fas fa-arrow-left mr-1"></i> Kembali
            </a>
            
        </div>
    </div>

    <div class="page" id="label-resi">

        @php
            $ship = \App\Helpers\ShippingHelper::parseShippingMethod($pesanan->expedition);

            $expeditionName = $ship['courier_name'] ?? 'SANCAKA';
            $expeditionService = $ship['service_name'] ?? 'Regular';
            $logoUrlFromHelper = $ship['logo_url'] ?? null;

            $localLogoPath = strtolower(str_replace(' ', '', $expeditionName));
            $localLogoAssetUrl = asset('public/storage/logo-ekspedisi/' . $localLogoPath . '.png'); 

            $finalLogoUrl = $logoUrlFromHelper ?: $localLogoAssetUrl;
        @endphp

        <div class="flex justify-between items-center border-b border-dashed border-gray-500 pb-2">
            <img src="https://tokosancaka.com/storage/uploads/sancaka.png" alt="Sancaka Express" class="h-10" onerror="this.style.display='none'">

            <img src="{{ $finalLogoUrl }}" alt="{{ $expeditionName }} Logo" class="h-8" crossorigin="anonymous">
        </div>

        <div class="text-center mt-2"> 
            <p class="font-bold text-sm tracking-wide"><strong>NOMOR RESI TOKOSANCAKA.COM</strong></p>
            {{-- Elemen SVG barcode, margin vertikal ditangani oleh CSS .barcode --}}
            <svg id="barcodeSancaka" class="barcode"></svg>
        </div>

        <div class="grid grid-cols-2 gap-3 mt-2 border-b border-dashed border-gray-400 pb-2">
            <div class="pr-2">
                <p class="label"><strong>PENGIRIM:</strong></p>
                <p class="value">{{ $pesanan->sender_name }}</p>
                <p class="text-xs">{{ $pesanan->sender_phone }}</p>
                <p class="text-xs leading-snug mt-1">
                    {{ implode(', ', array_filter([
                        $pesanan->sender_address,
                        $pesanan->sender_village,
                        $pesanan->sender_district,
                        $pesanan->sender_regency,
                        $pesanan->sender_province,
                        $pesanan->sender_postal_code,
                    ])) }}
                </p>

                <div class="mt-2 pt-2">
                    <p class="label"><strong>Rincian Paket:</strong></p>
                    <p class="value">- Berat: {{ $pesanan->weight }} Gram</p>
                    <p class="value">- Dimensi: {{ $pesanan->length ?? 0 }} x {{ $pesanan->width ?? 0 }} x {{ $pesanan->height ?? 0 }} cm</p>
                    <p class="value">- Layanan: {{ strtoupper($expeditionService) }}</p><br><br>

                    <p class="label"><strong>Total Ongkir:</strong></p>
                    <p class="value"><strong>Rp {{ number_format($pesanan->shipping_cost, 0, ',', '.') }}</strong></p>
                </div>
            </div>

            <div class="pl-2">
                <p class="label"><strong>PENERIMA:</strong></p>
                <p class="value">{{ $pesanan->receiver_name }}</p>
                <p class="text-xs">{{ $pesanan->receiver_phone }}</p>
                <p class="text-xs leading-snug mt-1">
                    {{ implode(', ', array_filter([
                        $pesanan->receiver_address,
                        $pesanan->receiver_village,
                        $pesanan->receiver_district,
                        $pesanan->receiver_regency,
                        $pesanan->receiver_province,
                        $pesanan->receiver_postal_code,
                    ])) }}
                </p>

                <div class="flex justify-center mt-4">
                    {{-- WADAH QR CODE (BARCODE 2D) --}}
                    <div id="qrcode"></div> 
                </div>
            </div>
        </div>

        <div class="grid grid-cols-3 gap-2 text-center mt-2 border-b border-dashed border-gray-400 pb-2">
            <div><p class="label"><strong>ORDER ID</strong></p><p class="value">{{ $pesanan->nomor_invoice }}</p></div>
            <div><p class="label"><strong>BERAT</strong></p><p class="value">{{ $pesanan->weight }} Gram</p></div>
            <div><p class="label"><strong>VOLUME</strong></p><p class="value">{{ $pesanan->length ?? 0 }} x {{ $pesanan->width ?? 0 }} x {{ $pesanan->height ?? 0 }} cm</p></div>
            <div><p class="label"><strong>LAYANAN</strong></p><p class="value">{{ strtoupper($expeditionService) }}</p></div>
            <div><p class="label"><strong>EKSPEDISI</strong></p><p class="value">{{ strtoupper($expeditionName) }}</p></div>
            <div><p class="label"><strong>Pembayaran</strong></p><p class="value">{{ $pesanan->payment_method }}</p></div>
        </div>

        @if($pesanan->payment_method == 'COD' || $pesanan->payment_method == 'CODBARANG')
        <div class="text-center mt-2 border-b border-dashed border-gray-400 pb-2">
            <p class="label"><strong>NILAI COD</strong></p>
            <p class="value text-red-600">Rp {{ number_format($pesanan->price, 0, ',', '.') }}</p>
        </div>
        @endif

        @if($pesanan->resi_aktual)
        <div class="text-center mt-3 pt-2 border-t border-dashed border-gray-400">
            <p class="label">RESI AKTUAL ({{ $pesanan->jasa_ekspedisi_aktual }})</p>
            {{-- Elemen SVG barcode aktual --}}
            <svg id="barcodeAktual" class="barcode"></svg>
        </div>
        @endif

        <div class="mt-auto pt-3 text-center text-xs">
            <p>Terima kasih telah menggunakan <span class="font-semibold">Sancaka Express</span>.</p>
            <p class="font-bold mt-1">{{ \Carbon\Carbon::parse($pesanan->created_at)->format('d M Y H:i') }} Kirim Paket DI TOKOSANCAKA.COM</p>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>

    <script>
        const RESI = {!! json_encode($pesanan->resi) !!};
        const TOKEN = document.querySelector('meta[name="csrf-token"]').content;

        document.addEventListener('DOMContentLoaded', function() {
            // --- BARCODE GENERATION (1D) ---
            try {
                const resiSancaka = RESI;
                if (resiSancaka) {
                    JsBarcode("#barcodeSancaka", resiSancaka, {
                        format: "CODE128", 
                        textMargin: 10,  // Jarak garis Barcode ke teks RESI
                        fontOptions: "bold", 
                        height: 50,      // Tinggi barcode
                        width: 3.5,      // LEBAR GARIS BARCODE (MEMPENGARUHI PANJANG KESELURUHAN)
                        fontSize: 30     // Ukuran font teks di bawah barcode
                    });
                }
                @if($pesanan->resi_aktual)
                    const resiAktual = {!! json_encode($pesanan->resi_aktual ?? '') !!};
                    if (resiAktual) {
                        JsBarcode("#barcodeAktual", resiAktual, {
                            format: "CODE128", 
                            textMargin: 10,  // Jarak garis Barcode ke teks RESI
                            fontOptions: "bold", 
                            height: 50,      // Tinggi barcode
                            width: 3.5,      // LEBAR GARIS BARCODE
                            fontSize: 30     // Ukuran font teks di bawah barcode
                        });
                    }
                @endif
            } catch (e) {
                console.error("Gagal membuat barcode:", e);
            }
            
            // --- QR CODE GENERATION (2D) ---
            try {
                 new QRCode(document.getElementById("qrcode"), {
                     text: "https://tokosancaka.com/tracking/search?resi=" + RESI, 
                     width: 100,
                     height: 100
                 });
            } catch (e) {
                console.error("Gagal membuat QR Code:", e);
            }
        });
        
        // --- FUNGSI FONTTE/WHATSAPP API ---
        function sendWaNotificationApi(target) {
            const button = (target === 'receiver') 
                ? document.querySelector('button[onclick*="receiver"]') 
                : document.querySelector('button[onclick*="sender"]');
            
            if (button) {
                button.disabled = true;
                button.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Mengirim...';
            }

            fetch('{{ route('api.whatsapp.send_resi') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': TOKEN 
                },
                body: JSON.stringify({
                    resi: RESI,
                    target: target 
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    Swal.fire('Berhasil!', data.message, 'success');
                } else {
                    Swal.fire('Gagal!', data.message || 'Terjadi kesalahan tidak dikenal.', 'error');
                }
                if (button) {
                    button.disabled = false;
                    button.innerHTML = (target === 'receiver') 
                        ? '<i class="fab fa-whatsapp mr-1"></i> Kirim WA (Penerima)'
                        : '<i class="fab fa-whatsapp mr-1"></i> Kirim WA (Pengirim)';
                }
            })
            .catch(error => {
                console.error('API Error:', error);
                Swal.fire('Error', 'Gagal terhubung ke API Fonnte/Server. Periksa koneksi.', 'error');
                if (button) {
                    button.disabled = false;
                    button.innerHTML = (target === 'receiver') 
                        ? '<i class="fab fa-whatsapp mr-1"></i> Kirim WA (Penerima)'
                        : '<i class="fab fa-whatsapp mr-1"></i> Kirim WA (Pengirim)';
                }
            });
        }

        // --- DOWNLOAD JPG SCRIPT ---
        document.getElementById('downloadBtn').addEventListener('click', function() {
            const downloadBtn = this;
            const labelElement = document.getElementById('label-resi');
            const resi = RESI;

            downloadBtn.disabled = true;
            downloadBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Mengonversi...';

            html2canvas(labelElement, {
                useCORS: true,
                scale: 2 
            }).then(canvas => {
                const link = document.createElement('a');
                link.href = canvas.toDataURL('image/jpeg', 0.95);
                link.download = `resi-${resi}.jpg`;
                
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);

                downloadBtn.disabled = false;
                downloadBtn.innerHTML = '<i class="fas fa-download mr-2"></i> Download JPG';

            }).catch(err => {
                console.error('Gagal konversi ke JPG:', err);
                Swal.fire('Gagal', 'Maaf, gagal mengunduh gambar. Silakan coba lagi.', 'error');
                downloadBtn.disabled = false;
                downloadBtn.innerHTML = '<i class="fas fa-download mr-2"></i> Download JPG';
            });
        });

    </script>

</body>
</html>