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
    @endphp

    <!-- Library Tambahan -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/qrcodejs/qrcode.min.js"></script>

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap');

        .invoice-body-font { font-family: 'Inter', sans-serif; }

        /* WATERMARK LAYER */
        .watermark-overlay {
            position: absolute;
            top: -50%; left: -50%;
            width: 200%; height: 200%;
            z-index: 0; pointer-events: none;
            transform: rotate(-35deg);
            display: flex; flex-wrap: wrap; align-content: flex-start; justify-content: center;
            overflow: hidden;
        }
        .watermark-overlay p {
            color: rgba(0, 0, 0, 0.03); /* Teks Samar */
            font-size: 13px; font-weight: 900;
            margin: 20px 30px; white-space: nowrap;
            letter-spacing: 1px;
            font-family: 'Inter', sans-serif;
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

        /* STRIP KANAN ATAS */
        .corner-stripes {
            position: absolute; top: 0; right: 0; width: 200px; height: 200px;
            background: linear-gradient(135deg, transparent 50%, #f1f5f9 50%, #f1f5f9 65%, transparent 65%, transparent 75%, #f8fafc 75%);
            z-index: 0; pointer-events: none;
        }

        @media print {
            .no-print { display: none !important; }
            .invoice-wrapper { margin: 0 !important; max-width: 100% !important; box-shadow: none !important; border: none !important; }
            body { background-color: white !important; -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
        }
    </style>

    <div class="bg-gradient-to-br from-gray-100 to-gray-300 min-h-screen flex items-center justify-center p-4 sm:p-6 invoice-body-font text-black">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-5xl overflow-hidden relative border border-gray-200 invoice-wrapper" id="invoice-area">

            <!-- LAYER WATERMARK -->
            <div class="watermark-overlay">
                @for($i=0; $i<100; $i++)
                    <p>{{ $wmText }}</p>
                @endfor
            </div>

            <!-- DEKORASI SUDUT & PITA STATUS -->
            <div class="corner-stripes"></div>
            <div class="ribbon-wrapper">
                <div class="ribbon {{ $isLunas ? 'paid' : '' }}">
                    {{ $isLunas ? 'LUNAS' : ($isCancelled ? 'BATAL' : 'UNPAID') }}
                </div>
            </div>

            <!-- HEADER SECTION -->
            <div class="p-8 md:p-10 border-b border-gray-100 relative z-10">
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-6">

                    <!-- Kiri: Logo & Info Perusahaan -->
                    <div class="flex items-start">
                        <!-- Logo Sancaka -->
                        <img src="https://tokosancaka.com/storage/uploads/sancaka.png" alt="CV. Sancaka Karya Hutama" class="h-16 w-auto object-contain mr-4" onerror="this.src='https://tokosancaka.com/storage/uploads/logo.jpeg'">
                        <div>
                            <h2 class="text-xl font-black text-black uppercase tracking-tight">CV. Sancaka Karya Hutama</h2>
                            <div class="text-[11px] text-gray-500 font-medium leading-relaxed mt-1 max-w-sm">
                                <p><i class="fas fa-map-marker-alt mr-1"></i> Jl. Dr. Wahidin No. 18A RT.22 RW.05 Kel. Ketanggi Kec. Ngawi Kab. Ngawi Jawa Timur 63211</p>
                                <p class="mt-1"><i class="fas fa-phone mr-1"></i> 0857-4580-8809 / 0881-9435-180</p>
                            </div>
                        </div>
                    </div>

                    <!-- Kanan: Judul & QR Code -->
                    <div class="text-left md:text-right w-full md:w-auto">
                        <span class="inline-block bg-black text-white text-[10px] font-bold px-3 py-1 rounded-full uppercase tracking-widest mb-2 shadow-sm">Invoice</span>
                        <p class="text-sm font-bold text-gray-400 uppercase tracking-widest mb-2">#{{ $order->invoice_number }}</p>

                        <!-- KOTAK QR CODE -->
                        <div class="inline-block bg-white p-1.5 border border-gray-200 rounded-lg shadow-sm">
                            <div id="qrCodeInvoice"></div>
                        </div>
                        <p class="text-[10px] text-gray-400 mt-1 font-semibold">Tgl: {{ $order->created_at->format('d/m/Y') }}</p>
                    </div>
                </div>
            </div>

            <div class="flex flex-col md:flex-row relative z-10">

                {{-- KOLOM KIRI: DETAIL PESANAN --}}
                <div class="w-full md:w-3/5 p-8 md:p-10 border-r border-gray-100">

                    @php
                        $shipMethod = strtolower($order->shipping_method ?? '');
                        $isPureDigital = str_contains($shipMethod, 'digital') || str_contains($shipMethod, 'eticket') || str_contains($shipMethod, 'jasa');

                        // Ekstrak nama dan nomor HP
                        $namaPelanggan = $order->user->nama_lengkap ?? ($order->receiver_name ?? 'Pelanggan');
                        $noHpPelanggan = $order->user->no_wa ?? ($order->receiver_phone ?? '');
                    @endphp

                    <!-- Info Penerima / Pengiriman -->
                    <div class="mb-8">
                        <h2 class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-2 border-b border-gray-100 pb-2">
                            {{ $isPureDigital ? 'Invoice To' : 'Detail Pengiriman' }}
                        </h2>

                        <div class="text-sm">
                            <!-- NAMA DISENSOR -->
                            <p class="font-bold text-black uppercase">{{ $maskName($namaPelanggan) }}</p>
                            <!-- NOMOR HP DISENSOR -->
                            <p class="text-xs font-semibold text-gray-600 mt-1">{{ $maskPhone($noHpPelanggan) }}</p>
                            <p class="text-[11px] text-gray-500 mt-2 leading-relaxed max-w-sm">{{ $order->shipping_address ?? $order->user->address_detail ?? 'Alamat tidak tersedia' }}</p>
                        </div>

                        <!-- Info Resi / Ekspedisi -->
                        @if($isLunas || in_array(strtolower($order->status), ['processing', 'shipped', 'completed']))
                            <div class="mt-4 pt-3 border-t border-gray-100">
                                @if($isPureDigital)
                                    <p class="text-[11px] font-semibold text-gray-600">Sistem: <span class="font-black text-black">Pengiriman Otomatis (E-Ticket)</span></p>
                                @else
                                    @php
                                        $kurirParts = explode('-', $order->shipping_method);
                                        $namaKurir = strtoupper(($kurirParts[1] ?? 'KURIR') . ' - ' . ($kurirParts[2] ?? ''));
                                        $nomorResi = $order->shipping_reference ?? $order->resi ?? null;
                                    @endphp
                                    <p class="text-[11px] font-semibold text-gray-600">Ekspedisi: <span class="font-black text-black">{{ $namaKurir }}</span></p>

                                    <div class="mt-2 flex items-center">
                                        <span class="text-[11px] font-semibold text-gray-500 mr-2">No. Resi:</span>
                                        @if(!empty($nomorResi) && $nomorResi !== '-' && !str_contains(strtolower($nomorResi), 'tunggu'))
                                            <span class="px-2 py-1 bg-gray-100 border border-gray-300 text-black font-mono font-bold rounded text-xs select-all">{{ $nomorResi }}</span>
                                        @else
                                            <span class="text-[11px] font-medium text-gray-400 italic">Menunggu update kurir...</span>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        @endif
                    </div>

                    <!-- Tabel Item -->
                    <h3 class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-2 border-b border-gray-100 pb-2">Rincian Pesanan</h3>

                    <div class="mb-8">
                        <table class="w-full text-left text-[11px]">
                            <thead class="border-b-2 border-black">
                                <tr>
                                    <th class="py-2 font-bold text-black uppercase tracking-wider w-[60%]">Description</th>
                                    <th class="py-2 font-bold text-black uppercase tracking-wider w-[10%] text-center">Qty</th>
                                    <th class="py-2 font-bold text-black uppercase tracking-wider w-[30%] text-right">Total</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @foreach($order->items as $item)
                                @php
                                    $katObj = $item->product ? $item->product->category()->first() : null;
                                    $isItemDigital = ($katObj && in_array($katObj->category_group, ['produk_digital', 'jasa'])) || $isPureDigital;
                                @endphp
                                <tr>
                                    <td class="py-4 align-top pr-4">
                                        <div class="flex items-start">
                                            <!-- Gambar Item -->
                                            <div class="h-10 w-10 flex-shrink-0 border border-gray-200 mr-3 rounded overflow-hidden hidden sm:block bg-white">
                                                @if($item->product && $item->product->image_url)
                                                    <img src="{{ asset('public/storage/'.$item->product->image_url) }}" alt="Img" class="h-full w-full object-cover">
                                                @else
                                                    <div class="h-full w-full bg-gray-100 flex items-center justify-center text-gray-400 text-xs">IMG</div>
                                                @endif
                                            </div>
                                            <div>
                                                <h4 class="font-bold text-[12px] text-black">{{ $item->product->name ?? 'Produk dihapus' }}</h4>
                                                <p class="text-gray-500 mt-1">@ Rp {{ number_format($item->price, 0, ',', '.') }}</p>
                                                @if($item->variant) <p class="text-[10px] text-gray-400 font-medium">Var: {{ $item->variant->name }}</p> @endif

                                                <!-- Akses Produk Digital -->
                                                @if($isItemDigital && $isLunas)
                                                    @php
                                                        $aksesData = $item->product->digital_url ?? ($item->product->digital_file_path ? asset('public/storage/' . $item->product->digital_file_path) : ($order->shipping_reference ?? null));
                                                        $aksesTipe = (!empty($item->product->digital_url) || !empty($item->product->digital_file_path)) ? 'link' : 'text';
                                                        if(empty($item->product->digital_url) && empty($item->product->digital_file_path) && filter_var($aksesData, FILTER_VALIDATE_URL)) $aksesTipe = 'link';
                                                    @endphp

                                                    @if($aksesData && strtolower($aksesData) !== 'menunggu penjual')
                                                        <div class="mt-2 p-2 bg-slate-50 border border-gray-200 rounded">
                                                            <p class="text-[9px] font-bold text-gray-500 uppercase tracking-widest mb-1">Akses / SN:</p>
                                                            @if($aksesTipe === 'link')
                                                                <a href="{{ $aksesData }}" target="_blank" class="text-blue-600 hover:underline font-semibold text-xs"><i class="fas fa-external-link-alt mr-1"></i> Buka Tautan</a>
                                                            @else
                                                                <code class="text-xs font-mono font-bold text-black select-all block overflow-hidden">{{ $aksesData }}</code>
                                                            @endif
                                                        </div>
                                                    @endif
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-4 text-center font-bold align-top">{{ $item->quantity }}</td>
                                    <td class="py-4 text-right font-bold text-black align-top">Rp {{ number_format($item->price * $item->quantity, 0, ',', '.') }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- Perhitungan Biaya (Gaya Clean) -->
                    <div class="w-full max-w-sm ml-auto">
                        <div class="flex justify-between py-1 text-[11px] text-gray-600 font-semibold">
                            <span>Subtotal</span><span>Rp {{ number_format($order->subtotal, 0, ',', '.') }}</span>
                        </div>
                        @if($order->shipping_cost > 0)
                        <div class="flex justify-between py-1 text-[11px] text-gray-600 font-semibold">
                            <span>Ongkos Kirim</span><span>Rp {{ number_format($order->shipping_cost, 0, ',', '.') }}</span>
                        </div>
                        @endif
                        @if($order->insurance_cost > 0)
                        <div class="flex justify-between py-1 text-[11px] text-gray-600 font-semibold">
                            <span>Asuransi</span><span>Rp {{ number_format($order->insurance_cost, 0, ',', '.') }}</span>
                        </div>
                        @endif
                        @if($order->cod_fee > 0)
                        <div class="flex justify-between py-1 text-[11px] text-gray-600 font-semibold">
                            <span>Biaya Layanan/COD</span><span>Rp {{ number_format($order->cod_fee, 0, ',', '.') }}</span>
                        </div>
                        @endif

                        <div class="flex justify-between border-t-2 border-b-2 border-black py-2 mt-2 mb-2 text-sm font-black text-black uppercase tracking-widest">
                            <span>Grand Total</span><span>Rp {{ number_format($order->total_amount, 0, ',', '.') }}</span>
                        </div>
                    </div>

                </div>

                {{-- KOLOM KANAN: INSTRUKSI PEMBAYARAN --}}
                <div class="w-full md:w-2/5 p-8 md:p-10 bg-slate-50 flex flex-col items-center text-center border-t md:border-t-0 md:border-l border-gray-200">

                    @if($statusRaw === 'pending')
                        <div class="w-full mb-6 text-left">
                            <h3 class="text-xs font-bold text-black uppercase tracking-wider mb-1">Status:</h3>
                            <span class="inline-block px-3 py-1 bg-white border border-gray-300 text-black text-[10px] font-bold uppercase rounded shadow-sm">Menunggu Pembayaran</span>
                        </div>

                        <div class="w-full bg-white p-6 rounded-xl border border-gray-200 shadow-sm flex-1 flex flex-col justify-center">
                            <h2 class="text-sm font-black text-black uppercase tracking-wide mb-4">Instruksi Pembayaran</h2>

                            @php
                                $method = strtoupper($order->payment_method ?? '');
                                $url    = $order->payment_url ?? $order->qr_url ?? '#';
                            @endphp

                            @if (in_array($order->payment_method, ['cod', 'CODBARANG']))
                                <div class="bg-white p-4 border border-dashed border-gray-300 rounded text-center mb-4">
                                    <i class="fas fa-hand-holding-usd text-2xl text-black mb-2"></i>
                                    <h3 class="font-bold text-black text-sm uppercase">Bayar Ditempat (COD)</h3>
                                    <p class="text-xs text-gray-500 mt-1">Siapkan uang tunai pas saat kurir tiba.</p>
                                </div>
                            @elseif (str_contains($method, 'QRIS') || !empty($order->qr_url))
                                <p class="text-xs text-gray-500 font-medium mb-3">Scan QR Code di bawah ini:</p>
                                <div class="bg-white p-2 border border-gray-200 rounded-lg shadow-sm mx-auto mb-4 w-40 h-40">
                                    <img src="{{ $url }}" alt="QRIS" class="w-full h-full object-cover">
                                </div>
                            @elseif (in_array($method, ['DANA', 'OVO']) || str_contains($method, 'DOKU_JOKUL') || $method === 'DANA')
                                <p class="text-xs text-gray-500 font-medium mb-4">Klik tombol di bawah untuk membuka aplikasi e-Wallet / Virtual Account Anda.</p>
                                <a href="{{ $url }}" target="_blank" class="block w-full py-3 bg-black text-white text-xs font-bold uppercase tracking-wider rounded hover:bg-gray-800 transition shadow-sm">
                                    <i class="fas fa-wallet mr-2"></i> Bayar Sekarang
                                </a>
                            @elseif(!empty($order->pay_code))
                                <p class="text-xs text-gray-500 font-medium mb-2">Kode Pembayaran / Virtual Account:</p>
                                <div class="relative group cursor-pointer" onclick="copyToClipboard('payCode')">
                                    <div class="bg-slate-100 py-4 border border-gray-300 rounded hover:bg-gray-200 transition">
                                        <span id="payCode" class="text-xl font-mono font-black text-black tracking-widest">{{ $order->pay_code }}</span>
                                    </div>
                                    <p class="text-[10px] text-black mt-2 font-medium opacity-0 group-hover:opacity-100 transition">Klik untuk menyalin</p>
                                </div>
                            @else
                                <a href="{{ $url }}" target="_blank" class="block w-full py-3 bg-black text-white text-xs font-bold uppercase tracking-wider rounded hover:bg-gray-800 transition shadow-sm">
                                    <i class="fas fa-credit-card mr-2"></i> Lanjutkan Pembayaran
                                </a>
                            @endif

                            <div class="mt-4 pt-4 border-t border-dashed border-gray-200 text-center">
                                <a href="https://tripay.co.id/cara-pembayaran" target="_blank" class="text-[10px] text-gray-500 hover:text-black hover:underline flex items-center justify-center font-semibold uppercase tracking-wider">
                                    <i class="fas fa-info-circle mr-1"></i> Cara Pembayaran
                                </a>
                            </div>
                        </div>
                    @else
                        <!-- Tampilan Jika Sudah Dibayar / Batal -->
                        <div class="w-full flex-1 flex flex-col justify-center items-center py-10">
                            @if($isLunas)
                                <div class="w-16 h-16 bg-white border border-gray-200 text-black rounded-full flex items-center justify-center text-3xl shadow-sm mb-4">
                                    <i class="fas fa-check"></i>
                                </div>
                                <h2 class="text-lg font-black text-black uppercase tracking-tight">Pembayaran Sukses</h2>
                                <p class="text-xs text-gray-500 mt-2 max-w-[200px] leading-relaxed">Pesanan Anda telah dibayar dan sedang diproses.</p>
                            @elseif($isCancelled)
                                <div class="w-16 h-16 bg-white border border-gray-200 text-black rounded-full flex items-center justify-center text-3xl shadow-sm mb-4">
                                    <i class="fas fa-times"></i>
                                </div>
                                <h2 class="text-lg font-black text-black uppercase tracking-tight">Pesanan Dibatalkan</h2>
                                <p class="text-xs text-gray-500 mt-2 max-w-[200px] leading-relaxed">Transaksi kadaluarsa atau telah dibatalkan.</p>
                            @endif

                            <!-- Tombol Aksi -->
                            <div class="w-full flex flex-col gap-2 mt-8">
                                <a href="{{ url('invoice/' . $order->invoice_number . '/pdf') }}" target="_blank" class="no-print w-full py-3 bg-white border border-black text-black text-[11px] font-bold uppercase tracking-wider hover:bg-gray-100 transition shadow-sm flex items-center justify-center">
                                    <i class="fas fa-download mr-2"></i> Unduh PDF
                                </a>
                                <a href="{{ route('checkout.index') }}" class="no-print w-full py-3 bg-black text-white text-[11px] font-bold uppercase tracking-wider hover:bg-gray-800 transition shadow-sm flex items-center justify-center">
                                    <i class="fas fa-shopping-bag mr-2"></i> Belanja Lagi
                                </a>
                            </div>
                        </div>
                    @endif

                </div>
            </div>

            <!-- FOOTER BAWAH -->
            <div class="bg-slate-50 border-t border-gray-200 p-6 text-center text-[10px] text-gray-400 font-medium relative z-10">
                Dicetak dari sistem pada {{ date('d M Y, H:i') }} WIB. Dokumen ini sah tanpa tanda tangan fisik.
            </div>

        </div>
    </div>

    @push('scripts')
    <!-- SCRIPT UNTUK GENERATE QR CODE 2D -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            try {
                const qrContainer = document.getElementById("qrCodeInvoice");
                if (qrContainer) {
                    new QRCode(qrContainer, {
                        text: "{{ $order->invoice_number }}", // Isi data barcode
                        width: 64,  // Ukuran compact
                        height: 64, // Ukuran compact
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
            }, function(err) {
                alert('Gagal menyalin text. Silakan copy manual.');
            });
        }
    </script>
    @endpush
@endsection
