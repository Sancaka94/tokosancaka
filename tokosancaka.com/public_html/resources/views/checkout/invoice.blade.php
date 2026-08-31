@extends('layouts.marketplace')

@section('title', 'Invoice #' . $order->invoice_number)

@section('content')
    @php
        // 1. LOGIKA STATUS LUNAS / UNPAID
        $statusRaw = strtolower($order->status ?? '');
        $isLunas = in_array($statusRaw, ['paid', 'processing', 'shipped', 'completed', 'lunas', 'sukses', 'success']);
        $isCancelled = in_array($statusRaw, ['batal', 'cancel', 'canceled', 'failed', 'expired', 'refund']);
        $statusText = $isLunas ? 'LUNAS' : ($isCancelled ? 'BATAL' : 'BELUM LUNAS');

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

        // 3. FUNGSI SENSOR HP
        $maskPhone = function($phone) {
            $phone = preg_replace('/[^0-9]/', '', (string) $phone);
            if (strlen($phone) > 7) {
                return substr($phone, 0, 7) . str_repeat('*', strlen($phone) - 7);
            }
            return $phone ?: '-';
        };

        // 4. TEKS WATERMARK DINAMIS
        $tglTerbitWmk = date('d M Y', strtotime($order->created_at ?? now()));
        $wmText = "VALID {$statusText} CV SANCAKA KARYA HUTAMA CREATED {$tglTerbitWmk} NO {$order->invoice_number}";

        // 5. PENGATURAN LOGO EKSPEDISI
        $shipMethod = strtolower($order->shipping_method ?? '');
        $isPureDigital = str_contains($shipMethod, 'digital') || str_contains($shipMethod, 'eticket') || str_contains($shipMethod, 'jasa');

        $expeditionName = 'Kurir';
        $expeditionService = 'Reguler';
        $finalLogoUrl = '';

        if (!$isPureDigital && !empty($order->shipping_method)) {
            $kurirParts = explode('-', $order->shipping_method);
            $expeditionName = strtoupper($kurirParts[1] ?? 'Kurir');
            $expeditionService = strtoupper($kurirParts[2] ?? 'Reguler');

            // Map logo ekspedisi (sama seperti di invoice PPOB)
            $courierMap = [
                'jne' => 'https://tokosancaka.com/public/storage/logo-ekspedisi/jne.png',
                'tiki' => 'https://tokosancaka.com/public/storage/logo-ekspedisi/tiki.png',
                'pos' => 'https://tokosancaka.com/public/storage/logo-ekspedisi/posindonesia.png',
                'posindonesia' => 'https://tokosancaka.com/public/storage/logo-ekspedisi/posindonesia.png',
                'sicepat' => 'https://tokosancaka.com/public/storage/logo-ekspedisi/sicepat.png',
                'sap' => 'https://tokosancaka.com/public/storage/logo-ekspedisi/sap.png',
                'jnt' => 'https://tokosancaka.com/public/storage/logo-ekspedisi/jnt.png',
                'j&t' => 'https://tokosancaka.com/public/storage/logo-ekspedisi/jnt.png',
                'jtcargo' => 'https://tokosancaka.com/public/storage/logo-ekspedisi/jtcargo.png',
                'lion' => 'https://tokosancaka.com/public/storage/logo-ekspedisi/lion.png',
                'spx' => 'https://tokosancaka.com/public/storage/logo-ekspedisi/spx.png',
                'ninja' => 'https://tokosancaka.com/public/storage/logo-ekspedisi/ninja.png',
                'anteraja' => 'https://tokosancaka.com/public/storage/logo-ekspedisi/anteraja.png',
            ];

            $normalizedName = strtolower(str_replace(' ', '', $expeditionName));
            $finalLogoUrl = asset('public/storage/logo-ekspedisi/' . $normalizedName . '.png');

            if (str_contains($normalizedName, 'cargo') && (str_contains($normalizedName, 'j&t') || str_contains($normalizedName, 'jt'))) {
                $finalLogoUrl = $courierMap['jtcargo'];
            } else {
                foreach ($courierMap as $key => $url) {
                    if (str_contains($normalizedName, $key)) { $finalLogoUrl = $url; break; }
                }
            }
        }
    @endphp

    <!-- Library Tambahan -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/qrcodejs/qrcode.min.js"></script>

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap');

        .invoice-body-font { font-family: 'Inter', sans-serif; }

        /* KUNCI WATERMARK: Pastikan menempel HANYA ke dalam .invoice-wrapper */
        /* Dan tambahkan background-image menggunakan SVG agar repeat-nya sempurna tanpa bocor */
        .watermark-overlay {
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            z-index: 0; pointer-events: none;
            background-image: url("data:image/svg+xml,%3Csvg width='400' height='100' xmlns='http://www.w3.org/2000/svg'%3E%3Ctext x='50%25' y='50%25' font-size='10' font-weight='900' fill='black' font-family='Arial, sans-serif' opacity='0.03' text-anchor='middle' transform='rotate(-35, 200, 50)'%3E{{ rawurlencode($wmText) }}%3C/text%3E%3C/svg%3E");
            background-repeat: repeat;
        }

        /* DESAIN PITA */
        .ribbon-wrapper {
            position: absolute; right: -5px; top: -5px; z-index: 20;
            overflow: hidden; width: 140px; height: 140px; text-align: right;
        }
        .ribbon {
            font-family: 'Inter', sans-serif;
            font-size: 0.75rem; font-weight: 800; color: #FFF;
            text-transform: uppercase; text-align: center; line-height: 28px;
            transform: rotate(45deg); width: 180px; display: block;
            background: #dc2626; /* Merah untuk UNPAID/CANCEL */
            position: absolute; top: 25px; right: -40px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1); letter-spacing: 1px;
        }
        .ribbon.paid { background: #16a34a; } /* Hijau untuk LUNAS */

        @media print {
            .no-print { display: none !important; }
            .invoice-wrapper { margin: 0 !important; max-width: 100% !important; box-shadow: none !important; border: none !important; }
            body { background-color: white !important; -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
        }
    </style>

    <!-- WRAPPER UTAMA -->
    <div class="bg-gray-100 py-10 flex flex-col items-center justify-start min-h-screen invoice-body-font text-black relative">

        <!-- KARTU INVOICE -->
        <!-- relative dan overflow-hidden MENCEGAH watermark bocor keluar kotak invoice -->
        <div class="bg-white shadow-xl w-full max-w-4xl relative invoice-wrapper overflow-hidden rounded-xl border border-gray-200 z-10" id="invoice-area">

            <!-- LAYER WATERMARK MENGGUNAKAN BACKGROUND CSS SVG (LEBIH RAPI & TIDAK BOCOR) -->
            <div class="watermark-overlay"></div>

            <!-- PITA STATUS -->
            <div class="ribbon-wrapper">
                <div class="ribbon {{ $isLunas ? 'paid' : '' }}">
                    {{ $isLunas ? 'LUNAS' : ($isCancelled ? 'BATAL' : 'UNPAID') }}
                </div>
            </div>

            <!-- ISI INVOICE -->
            <div class="flex flex-col md:flex-row relative z-10">

                {{-- KOLOM KIRI: KONTEN UTAMA INVOICE --}}
                <div class="w-full md:w-2/3 p-8 md:p-12 pb-6 md:pb-12">

                    <!-- HEADER: Logo & Info Perusahaan -->
                    <div class="flex flex-col sm:flex-row justify-between items-start gap-6 mb-12">
                        <div class="flex items-start">
                            <img src="https://tokosancaka.com/storage/uploads/sancaka.png" alt="CV. Sancaka Karya Hutama" class="h-12 w-auto object-contain mr-4" onerror="this.src='https://tokosancaka.com/storage/uploads/logo.jpeg'">
                            <div>
                                <h2 class="text-lg font-black text-black uppercase tracking-tight">CV. SANCAKA KARYA HUTAMA</h2>
                                <div class="text-[10px] text-gray-500 font-medium leading-relaxed mt-1">
                                    <p><i class="fas fa-map-marker-alt mr-1 w-3 text-center"></i> Jl. Dr. Wahidin No. 18A RT.22 RW.05 Kel. Ketanggi Kec. Ngawi Kab. Ngawi Jawa Timur 63211</p>
                                    <p class="mt-1"><i class="fas fa-phone mr-1 w-3 text-center"></i> 0857-4580-8809 / 0881-9435-180</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    @php
                        $namaPelanggan = $order->user->nama_lengkap ?? ($order->receiver_name ?? 'Pelanggan');
                        $noHpPelanggan = $order->user->no_wa ?? ($order->receiver_phone ?? '');
                    @endphp

                    <!-- INFO PENGIRIMAN -->
                    <div class="mb-10">
                        <h3 class="text-[9px] font-bold text-gray-400 uppercase tracking-widest mb-3">
                            {{ $isPureDigital ? 'Informasi Penerima' : 'Detail Pengiriman' }}
                        </h3>
                        <div class="text-sm">
                            <p class="font-black text-black uppercase">{{ $maskName($namaPelanggan) }}</p>
                            <p class="text-[11px] font-bold text-gray-600 mt-1">{{ $maskPhone($noHpPelanggan) }}</p>
                            <p class="text-[11px] text-gray-500 mt-1 leading-relaxed max-w-sm">{{ $order->shipping_address ?? $order->user->address_detail ?? 'Alamat tidak tersedia' }}</p>
                        </div>
                    </div>

                    <!-- TABEL PESANAN -->
                    <h3 class="text-[9px] font-bold text-gray-400 uppercase tracking-widest mb-3">Rincian Pesanan</h3>
                    <div class="mb-10">
                        <table class="w-full text-left text-[11px]">
                            <thead class="border-b border-gray-200">
                                <tr>
                                    <th class="py-2 font-bold text-black uppercase tracking-wider w-[65%]">Description</th>
                                    <th class="py-2 font-bold text-black uppercase tracking-wider w-[10%] text-center">Qty</th>
                                    <th class="py-2 font-bold text-black uppercase tracking-wider w-[25%] text-right">Total</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach($order->items as $item)
                                @php
                                    $katObj = $item->product ? $item->product->category()->first() : null;
                                    $isItemDigital = ($katObj && in_array($katObj->category_group, ['produk_digital', 'jasa'])) || $isPureDigital;
                                @endphp
                                <tr>
                                    <td class="py-4 align-top pr-4">
                                        <div class="flex items-start">
                                            <div class="h-10 w-10 flex-shrink-0 border border-gray-200 mr-3 rounded hidden sm:block bg-white p-1">
                                                @if($item->product && $item->product->image_url)
                                                    <img src="{{ asset('public/storage/'.$item->product->image_url) }}" alt="Img" class="h-full w-full object-contain">
                                                @else
                                                    <div class="h-full w-full bg-gray-50 flex items-center justify-center text-gray-400 text-[8px] font-bold">IMG</div>
                                                @endif
                                            </div>
                                            <div>
                                                <h4 class="font-bold text-[11px] text-black uppercase">{{ $item->product->name ?? 'Produk dihapus' }}</h4>
                                                <p class="text-gray-500 mt-0.5">@ Rp {{ number_format($item->price, 0, ',', '.') }}</p>
                                                @if($item->variant) <p class="text-[9px] text-gray-400 font-medium">Varian: {{ $item->variant->name }}</p> @endif

                                                <!-- Akses Produk Digital -->
                                                @if($isItemDigital && $isLunas)
                                                    @php
                                                        $aksesData = $item->product->digital_url ?? ($item->product->digital_file_path ? asset('public/storage/' . $item->product->digital_file_path) : ($order->shipping_reference ?? null));
                                                        $aksesTipe = (!empty($item->product->digital_url) || !empty($item->product->digital_file_path)) ? 'link' : 'text';
                                                        if(empty($item->product->digital_url) && empty($item->product->digital_file_path) && filter_var($aksesData, FILTER_VALIDATE_URL)) $aksesTipe = 'link';
                                                    @endphp

                                                    @if($aksesData && strtolower($aksesData) !== 'menunggu penjual')
                                                        <div class="mt-2">
                                                            @if($aksesTipe === 'link')
                                                                <a href="{{ $aksesData }}" target="_blank" class="text-blue-600 hover:underline font-bold text-[10px]"><i class="fas fa-external-link-alt mr-1"></i> Buka Akses</a>
                                                            @else
                                                                <code class="text-[10px] font-mono font-bold text-black bg-gray-100 px-1 py-0.5 rounded">{{ $aksesData }}</code>
                                                            @endif
                                                        </div>
                                                    @endif
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-4 text-center font-bold text-black align-top">{{ $item->quantity }}</td>
                                    <td class="py-4 text-right font-bold text-black align-top">Rp {{ number_format($item->price * $item->quantity, 0, ',', '.') }}</td>
                                </tr>
                                @endforeach

                                <!-- ROW EKSPEDISI / PENGIRIMAN -->
                                @if(!$isPureDigital)
                                <tr>
                                    <td class="py-4 align-top pr-4">
                                        <div class="flex items-start">
                                            <div class="h-10 w-10 flex-shrink-0 border border-gray-200 mr-3 rounded hidden sm:block bg-white p-1">
                                                <img src="{{ $finalLogoUrl }}" alt="Ekspedisi" class="h-full w-full object-contain" onerror="this.style.display='none'">
                                            </div>
                                            <div>
                                                <h4 class="font-bold text-[11px] text-black uppercase">{{ $expeditionName }} - {{ $expeditionService }}</h4>
                                                <p class="text-[10px] text-gray-500 mt-0.5">Biaya Pengiriman Ekspedisi</p>

                                                @php $nomorResi = $order->shipping_reference ?? $order->resi ?? null; @endphp
                                                @if($isLunas && !empty($nomorResi) && $nomorResi !== '-' && !str_contains(strtolower($nomorResi), 'tunggu'))
                                                    <p class="text-[10px] font-bold text-black mt-1">Resi: <span class="font-mono">{{ $nomorResi }}</span></p>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-4 text-center font-bold text-black align-top">1</td>
                                    <td class="py-4 text-right font-bold text-black align-top">Rp {{ number_format($order->shipping_cost ?? 0, 0, ',', '.') }}</td>
                                </tr>
                                @endif

                                <!-- ROW ASURANSI -->
                                @if($order->insurance_cost > 0)
                                <tr>
                                    <td class="py-4 align-top pr-4">
                                        <div class="ml-13 sm:ml-0">
                                            <h4 class="font-bold text-[11px] text-black uppercase">Asuransi Pengiriman</h4>
                                        </div>
                                    </td>
                                    <td class="py-4 text-center font-bold text-black align-top">1</td>
                                    <td class="py-4 text-right font-bold text-black align-top">Rp {{ number_format($order->insurance_cost, 0, ',', '.') }}</td>
                                </tr>
                                @endif

                                <!-- ROW COD FEE -->
                                @if($order->cod_fee > 0)
                                <tr>
                                    <td class="py-4 align-top pr-4">
                                        <div class="ml-13 sm:ml-0">
                                            <h4 class="font-bold text-[11px] text-black uppercase">Biaya Layanan (COD/Admin)</h4>
                                        </div>
                                    </td>
                                    <td class="py-4 text-center font-bold text-black align-top">1</td>
                                    <td class="py-4 text-right font-bold text-black align-top">Rp {{ number_format($order->cod_fee, 0, ',', '.') }}</td>
                                </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>

                    <!-- TOTALS (Clean Format) -->
                    <div class="w-full max-w-xs ml-auto mb-2">
                        <div class="flex justify-between py-1 text-[10px] text-gray-500 font-bold uppercase">
                            <span>Subtotal</span><span>Rp {{ number_format($order->subtotal, 0, ',', '.') }}</span>
                        </div>
                        <div class="flex justify-between border-t border-black py-2 mt-2 text-sm font-black text-black uppercase tracking-widest">
                            <span>Grand Total</span><span>Rp {{ number_format($order->total_amount, 0, ',', '.') }}</span>
                        </div>
                    </div>
                </div>

                {{-- KOLOM KANAN: STATUS, INSTRUKSI & QR CODE --}}
                <div class="w-full md:w-1/3 bg-gray-50 p-8 md:p-12 border-t md:border-t-0 md:border-l border-gray-200 flex flex-col relative">

                    <!-- QR Code & Judul (Dipindah ke kanan atas) -->
                    <div class="flex flex-col items-end mb-12 relative z-10">
                        <span class="inline-block bg-black text-white text-[9px] font-bold px-2 py-0.5 rounded-full uppercase tracking-widest mb-2">Invoice</span>
                        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-3 text-right break-all">#{{ $order->invoice_number }}</p>
                        <div class="bg-white p-1 border border-gray-200 rounded-lg shadow-sm">
                            <div id="qrCodeInvoice"></div>
                        </div>
                        <p class="text-[9px] text-gray-400 mt-2 font-bold">Tgl: {{ $order->created_at->format('d/m/Y') }}</p>
                    </div>

                    <!-- Status -->
                    <div class="w-full mb-4 relative z-10">
                        <h3 class="text-[9px] font-bold text-black uppercase tracking-wider mb-2">Status:</h3>
                        <div class="inline-block w-full text-center py-2 px-3 bg-white border border-gray-300 text-black text-[10px] font-bold uppercase tracking-wider rounded">
                            {{ $statusRaw === 'pending' ? 'Menunggu Pembayaran' : ($isLunas ? 'Berhasil Dibayar' : 'Dibatalkan') }}
                        </div>
                    </div>

                    <!-- Kotak Instruksi / Download -->
                    <div class="w-full bg-white p-6 rounded-xl border border-gray-200 shadow-sm mt-4 relative z-10">

                        @if($statusRaw === 'pending')
                            <h2 class="text-[11px] font-black text-black uppercase tracking-wide mb-3 text-center">Instruksi Pembayaran</h2>
                            <p class="text-[9px] text-gray-500 font-medium mb-4 text-center">Klik tombol di bawah untuk membuka aplikasi e-Wallet / Virtual Account Anda.</p>

                            @php
                                $method = strtoupper($order->payment_method ?? '');
                                $url    = $order->payment_url ?? $order->qr_url ?? '#';
                            @endphp

                            @if (in_array($order->payment_method, ['cod', 'CODBARANG']))
                                <div class="text-center mb-3">
                                    <i class="fas fa-hand-holding-usd text-xl text-black mb-1"></i>
                                    <h3 class="font-bold text-black text-[11px] uppercase">Bayar Ditempat</h3>
                                </div>
                            @elseif(!empty($order->pay_code))
                                <div class="bg-gray-100 py-3 rounded text-center mb-3 cursor-pointer" onclick="copyToClipboard('payCode')">
                                    <span id="payCode" class="text-lg font-mono font-black text-black tracking-widest">{{ $order->pay_code }}</span>
                                </div>
                            @else
                                <a href="{{ $url }}" target="_blank" class="block w-full py-2.5 bg-black text-white text-[10px] font-bold uppercase tracking-wider rounded text-center hover:bg-gray-800 transition">
                                    <i class="fas fa-wallet mr-2"></i> Bayar Sekarang
                                </a>
                            @endif

                            <div class="mt-4 pt-4 border-t border-gray-100 text-center">
                                <a href="https://tripay.co.id/cara-pembayaran" target="_blank" class="text-[9px] text-gray-400 hover:text-black font-bold uppercase tracking-wider">
                                    <i class="fas fa-info-circle mr-1"></i> Cara Pembayaran
                                </a>
                            </div>
                        @else
                            <!-- Tombol Download / Kembali -->
                            <div class="flex flex-col gap-2">
                                <a href="{{ url('invoice/' . $order->invoice_number . '/pdf') }}" target="_blank" class="no-print w-full py-2.5 bg-white border border-black text-black text-[10px] font-bold uppercase tracking-wider text-center hover:bg-gray-100 transition">
                                    <i class="fas fa-download mr-1"></i> PDF
                                </a>
                                <a href="{{ route('checkout.index') }}" class="no-print w-full py-2.5 bg-black text-white text-[10px] font-bold uppercase tracking-wider text-center hover:bg-gray-800 transition">
                                    Kembali Belanja
                                </a>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- FOOTER INVOICE (Tetap menempel di bawah kotak putih) -->
            <div class="bg-white border-t border-gray-200 p-5 text-center text-[9px] text-gray-400 font-medium relative z-10 w-full">
                Dicetak dari sistem pada {{ date('d M Y, H:i') }} WIB. Dokumen ini sah tanpa tanda tangan fisik.
            </div>

        </div>

    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            try {
                const qrContainer = document.getElementById("qrCodeInvoice");
                if (qrContainer) {
                    new QRCode(qrContainer, {
                        text: "{{ $order->invoice_number }}",
                        width: 60,
                        height: 60,
                        colorDark : "#000000",
                        colorLight : "#ffffff",
                        correctLevel : QRCode.CorrectLevel.M
                    });
                }
            } catch (e) {
                console.error("Gagal memuat QR Code:", e);
            }
        });

        function copyToClipboard(elementId) {
            var text = document.getElementById(elementId).innerText.trim();
            navigator.clipboard.writeText(text).then(function() {
                alert('Berhasil disalin: ' + text);
            });
        }
    </script>
    @endpush
@endsection
