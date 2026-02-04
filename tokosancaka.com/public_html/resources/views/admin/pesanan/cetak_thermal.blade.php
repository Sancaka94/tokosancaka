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
            // 1. Parsing Helper Asli
            $ship = \App\Helpers\ShippingHelper::parseShippingMethod($pesanan->expedition);
            $expeditionName = $ship['courier_name'] ?? 'SANCAKA'; // Contoh: "J&T Express", "JNE", "SiCepat"
            $expeditionService = $ship['service_name'] ?? 'Regular';

            // 2. Definisi Mapping Manual (DATABASE LINK GAMBAR)
            $courierMap = [
                'jne'           => 'https://tokosancaka.com/public/storage/logo-ekspedisi/jne.png',
                'tiki'          => 'https://tokosancaka.com/public/storage/logo-ekspedisi/tiki.png',
                'pos'           => 'https://tokosancaka.com/public/storage/logo-ekspedisi/posindonesia.png',
                'posindonesia'  => 'https://tokosancaka.com/public/storage/logo-ekspedisi/posindonesia.png',
                'sicepat'       => 'https://tokosancaka.com/public/storage/logo-ekspedisi/sicepat.png',
                'sap'           => 'https://tokosancaka.com/public/storage/logo-ekspedisi/sap.png',
                'ncs'           => 'https://blogger.googleusercontent.com/img/b/R29vZ2xl/AVvXsEjxj3iyyZEjK2L4A4yCIr_E-4W3hF2lk_yb-t0Oj2oFPErCPCMHie5LHqps02xMb6sNa-Gqz5NSX_P_hzWlYpUpJUlCD4iN6_QxiSG9fzY4bsZ9XvLFDn7HCiORtNvIlPfuQbSSdW96p7x7uN8ek3FWyHW9c2bznrFBQkoLd5A9sVAFVKWLfUhT3Dxh/s320/GKL41_NCS%20Kurir%20-%20Koleksilogo.com.jpg',
                'idx'           => 'https://tokosancaka.com/public/storage/logo-ekspedisi/idx.png',
                'idexpress'     => 'https://tokosancaka.com/public/storage/logo-ekspedisi/idx.png',
                'gojek'         => 'https://tokosancaka.com/public/storage/logo-ekspedisi/gosend.png',
                'gosend'        => 'https://tokosancaka.com/public/storage/logo-ekspedisi/gosend.png',
                'grab'          => 'https://tokosancaka.com/public/storage/logo-ekspedisi/grab.png',
                'jnt'           => 'https://tokosancaka.com/public/storage/logo-ekspedisi/jnt.png',
                'j&t'           => 'https://tokosancaka.com/public/storage/logo-ekspedisi/jnt.png',
                'indah'         => 'https://blogger.googleusercontent.com/img/b/R29vZ2xl/AVvXsEicOAaLoH2eElQ93_gbkzhvk4dRhWVlk5wQsGgilihIB58321aHchlJLdjyz1ToS25P_nWrHJ_E4QBiW_OVlI7tQt7cZ5I0HZqk6StS7jZltLVvDXp2d5ZDLB9yklhV4x6z2iXyURURDv_unhf-U6vyiD_8to9OC4PBwMwyU_5wAqOiCl6tKiaTA-ri1Q/s851/Logo%20Indah%20Logistik%20Cargo@0.5x.png',
                'jtcargo'       => 'https://tokosancaka.com/public/storage/logo-ekspedisi/jtcargo.png',
                'lion'          => 'https://tokosancaka.com/public/storage/logo-ekspedisi/lion.png',
                'spx'           => 'https://tokosancaka.com/public/storage/logo-ekspedisi/spx.png',
                'shopee'        => 'https://tokosancaka.com/public/storage/logo-ekspedisi/spx.png',
                'ninja'         => 'https://tokosancaka.com/public/storage/logo-ekspedisi/ninja.png',
                'anteraja'      => 'https://tokosancaka.com/public/storage/logo-ekspedisi/anteraja.png',
                'sentral'       => 'https://tokosancaka.com/public/storage/logo-ekspedisi/centralcargo.png',
                'borzo'         => 'https://tokosancaka.com/public/storage/logo-ekspedisi/borzo.png',
            ];

            // 3. Logika Pencocokan (Smart Matching)
            // Kita ubah nama ekspedisi menjadi huruf kecil semua dan hapus spasi agar mudah dicocokkan
            // Contoh: "J&T Express" -> "j&texpress", "SiCepat" -> "sicepat"
            $normalizedName = strtolower(str_replace(' ', '', $expeditionName));
            $finalLogoUrl = null;

            // Cek Khusus untuk J&T Cargo vs J&T Express (karena mirip)
            if (str_contains($normalizedName, 'cargo') && (str_contains($normalizedName, 'j&t') || str_contains($normalizedName, 'jt'))) {
                $finalLogoUrl = $courierMap['jtcargo'];
            } 
            // Cek Loop Normal
            else {
                foreach ($courierMap as $key => $url) {
                    // Jika nama ekspedisi mengandung kata kunci (misal 'sicepat' ada di 'sicepathalu')
                    if (str_contains($normalizedName, $key)) {
                        $finalLogoUrl = $url;
                        break; // Ketemu, berhenti looping
                    }
                }
            }

            // 4. Fallback Terakhir (Jika tidak ada di list manual, pakai logika lama)
            if (!$finalLogoUrl) {
                $logoUrlFromHelper = $ship['logo_url'] ?? null;
                $localLogoPath = strtolower(str_replace(' ', '', $expeditionName));
                $localLogoAssetUrl = asset('public/storage/logo-ekspedisi/' . $localLogoPath . '.png');
                $finalLogoUrl = $logoUrlFromHelper ?: $localLogoAssetUrl;
            }
        @endphp

        <div class="flex justify-between items-center border-b border-dashed border-gray-500 pb-2">
            <img src="https://tokosancaka.com/storage/uploads/sancaka.png" alt="Sancaka Express" class="h-10" onerror="this.style.display='none'">

            <img src="{{ $finalLogoUrl }}" 
                 alt="{{ $expeditionName }}" 
                 class="h-8 object-contain" 
                 onerror="this.style.opacity='0'">
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
                    <p class="value">- Harga Barang: Rp {{ number_format($pesanan->item_price, 0, ',', '.') }}</p>
                    <p class="value">- Isi Paket: {{ $pesanan->item_description }}</p>
                    <p class="value">- Dimensi: {{ $pesanan->length ?? 0 }} x {{ $pesanan->width ?? 0 }} x {{ $pesanan->height ?? 0 }} cm</p>
                    <p class="value">- Layanan: {{ strtoupper($expeditionService) }}</p><br>
                    <p class="label text-green-600"><strong>Total Ongkir:</strong></p>

<p class="value text-red-600 text-lg">
    <strong>
        Rp {{ number_format($pesanan->shipping_cost, 0, ',', '.') }}
    </strong>
</p><br>

                    <p class="value">CV. SANCAKA KARYA HUTAMA</p>
                     {{-- BAGIAN YANG DIPERBAIKI: LOGIKA COD ONGKIR vs COD BARANG --}}
        @php
            $pm = strtoupper($pesanan->payment_method);
            $isCodBarang = ($pm === 'CODBARANG');
            $isCodOngkir = ($pm === 'COD');
            
            // Variabel Default
            $labelCod = "NILAI COD";
            $nilaiCodFinal = 0;
            $showCodBlock = false;

            if ($isCodBarang) {
                // KASUS 1: COD BARANG (User bayar Barang + Ongkir)
                // Kita percaya angka di database ($pesanan->price) karena biasanya sudah benar totalnya
                $nilaiCodFinal = $pesanan->price;
                $labelCod = "NILAI COD (BARANG + ONGKIR)";
                $showCodBlock = true;

            } elseif ($isCodOngkir) {
                // KASUS 2: COD ONGKIR (User CUMA bayar Ongkir)
                // Kita lakukan HITUNG ULANG agar data lama yang salah (59rb) tidak muncul
                // Rumus: Ongkir Asli + Asuransi + Fee (2500)
                $ongkirAsli = $pesanan->shipping_cost ?? 0;
                $asuransiAsli = $pesanan->insurance_cost ?? 0;
                
                // Hitung Fee Layanan (Logic sama dengan OrderService)
                $feeLayanan = 2500;
                $feeHitung = max($feeLayanan, floor($ongkirAsli * 0.03));
                
                $nilaiCodFinal = $ongkirAsli + $asuransiAsli + $feeHitung;
                $labelCod = "NILAI COD (ONGKIR SAJA)";
                $showCodBlock = true;
            }
        @endphp

        @if($showCodBlock)
        
            <p class="label text-[8px] mb-1"><strong>{{ $labelCod }}</strong></p>
            <p class="value text-red-600 font-bold" style="font-size: 8px;">Rp {{ number_format($nilaiCodFinal, 0, ',', '.') }}</p>
            
            @if($isCodOngkir)
                <p class="text-[10px] italic mt-1 font-bold text-gray-500">(JANGAN TAGIH HARGA BARANG)</p>
            @endif
       
        @endif
        {{-- AKHIR PERBAIKAN --}}

                    
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
    <div class="border border-gray-400 rounded-md p-3 inline-block">
        <div id="qrcode"></div>
    </div>
                </div>

                <p class="flex justify-center"><strong>TRACKING ME</strong></p>
            </div>
        </div>

        <div class="grid grid-cols-3 gap-2 text-center mt-2 border-b border-dashed border-gray-400 pb-2">
            <div><p class="label"><strong>ORDER ID / RESI</strong></p><p class="value">{{ $pesanan->nomor_invoice }}</p></div>
            <div><p class="label"><strong>BERAT</strong></p><p class="value">{{ $pesanan->weight }} Gram</p></div>
            <div><p class="label"><strong>VOLUME (cm)</strong></p><p class="value">{{ $pesanan->length ?? 0 }} x {{ $pesanan->width ?? 0 }} x {{ $pesanan->height ?? 0 }}</p></div>
            <div><p class="label"><strong>LAYANAN</strong></p><p class="value">{{ strtoupper($expeditionService) }}</p></div>
            <div><p class="label"><strong>EKSPEDISI</strong></p><p class="value">{{ strtoupper($expeditionName) }}</p></div>
            <div>
    <p class="label"><strong>Pembayaran</strong></p>
    <p class="value">{{ strtoupper($pesanan->payment_method) === 'POTONG SALDO' ? 'SALDO / CASH' : $pesanan->payment_method }}</p></div>

        </div>

       

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
                     width: 75,
                     height: 75
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