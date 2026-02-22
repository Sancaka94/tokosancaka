<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice #{{ $order->order_number }} - {{ $order->tenant->name ?? 'SancakaPOS' }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @media print {
            @page { size: portrait; margin: 1cm; }
            .no-print { display: none !important; }
            body { background: white !important; padding: 0 !important; color: black !important; }
            .print-container { box-shadow: none !important; border: none !important; margin: 0 !important; padding: 0 !important; max-width: 100% !important; }
            /* Penyesuaian warna saat print agar hemat tinta */
            .bg-slate-900 { background-color: #f1f5f9 !important; color: #1e293b !important; border-bottom: 2px solid #cbd5e1 !important; }
            .text-white { color: #1e293b !important; }
            .bg-blue-50, .bg-amber-50 { background-color: white !important; border: 1px solid #e2e8f0 !important; }
        }
    </style>
</head>
<body class="bg-slate-100 font-sans text-slate-800 min-h-screen flex items-center justify-center p-4 md:p-8">

    <div class="print-container w-full max-w-4xl bg-white rounded-2xl shadow-xl overflow-hidden border border-slate-200 flex flex-col">

        <div class="bg-slate-900 text-white p-6 md:p-8 text-center md:text-left relative overflow-hidden flex flex-col md:flex-row justify-between md:items-end gap-6">
            <div class="relative z-10">
                <p class="text-xs font-bold text-blue-400 uppercase tracking-widest mb-1">INVOICE</p>
                <h2 class="text-3xl md:text-4xl font-black mb-1">#{{ $order->order_number }}</h2>
                <p class="text-sm text-slate-400">{{ $order->created_at->format('d M Y, H:i') }}</p>
            </div>

            <div class="relative z-10 md:text-right text-sm">
                <h1 class="text-xl md:text-2xl font-black tracking-wider uppercase text-white mb-1">{{ $order->tenant->name ?? 'SANCAKA STORE' }}</h1>
                <p class="text-slate-400">{{ $order->tenant->address ?? 'Jl.Dr.Wahidin No.18A RT.22/05 Ketanggi Ngawi' }}</p>
                <p class="text-slate-400">WA: {{ $order->tenant->phone ?? '0857-4580-8809' }}</p>
            </div>

            <div class="absolute -top-10 -right-10 w-32 h-32 md:w-48 md:h-48 bg-slate-800 rounded-full opacity-50 no-print"></div>
            <div class="absolute -bottom-10 -left-10 w-32 h-32 md:w-48 md:h-48 bg-slate-800 rounded-full opacity-50 no-print"></div>
        </div>

        <div class="flex flex-col md:flex-row flex-grow">

            <div class="w-full md:w-5/12 border-b md:border-b-0 md:border-r border-dashed border-slate-300 bg-slate-50 flex flex-col">
                <div class="p-6 space-y-6">

                    <div>
                        <p class="text-[10px] font-bold text-slate-400 uppercase mb-1">Tagihan Kepada:</p>
                        <h3 class="font-bold text-base text-slate-800">{{ $order->customer_name }}</h3>
                        <p class="text-sm text-slate-500"><i class="fab fa-whatsapp text-emerald-500 mr-1"></i> {{ $order->customer_phone }}</p>
                    </div>

                    @if($order->shipping_cost > 0 || $order->destination_address)
                    <div class="border-t border-slate-200 pt-4">
                        <p class="text-[10px] font-bold text-slate-400 uppercase mb-1">Informasi Pengiriman:</p>
                        <div class="bg-white p-3 border border-slate-200 rounded-lg text-sm shadow-sm mt-2">
                            <p class="font-bold text-slate-700 mb-1">{{ strtoupper($order->courier_service ?? 'Kurir') }}</p>
                            @if($order->shipping_ref)
                                <p class="text-xs text-blue-600 font-mono mb-2 bg-blue-50 inline-block px-2 py-1 rounded">Resi: {{ $order->shipping_ref }}</p>
                            @endif
                            <p class="text-xs text-slate-600 leading-relaxed"><i class="fas fa-map-marker-alt text-red-500 mr-1"></i> {{ $order->destination_address }}</p>
                        </div>
                    </div>
                    @endif

                    <div class="border-t border-slate-200 pt-4">
                        <p class="text-[10px] font-bold text-slate-400 uppercase mb-2">Status Pembayaran:</p>
                        <div class="bg-white p-3 border border-slate-200 rounded-lg text-center flex justify-between items-center shadow-sm">
                            <span class="text-xs font-bold text-slate-500 uppercase">{{ str_replace('_', ' ', $order->payment_method) }}</span>
                            <span class="font-black uppercase text-xs px-3 py-1 rounded-full {{ $order->payment_status == 'paid' ? 'bg-emerald-100 text-emerald-700 border border-emerald-200' : 'bg-red-100 text-red-600 border border-red-200' }}">
                                <i class="fas {{ $order->payment_status == 'paid' ? 'fa-check-circle' : 'fa-clock' }} mr-1"></i>
                                {{ $order->payment_status == 'paid' ? 'LUNAS' : 'BELUM LUNAS' }}
                            </span>
                        </div>
                    </div>

                </div>

                @if($order->payment_method == 'pay_later' && $order->payment_status == 'unpaid')
                <div class="p-6 bg-amber-50 border-t border-dashed border-slate-300 text-center flex-grow no-print">
                    <div class="flex flex-col items-center">
                        <div class="bg-amber-100 text-amber-600 w-10 h-10 rounded-full flex items-center justify-center mb-2">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <h4 class="text-sm font-bold text-amber-800 uppercase mb-1">Tagihan Belum Dibayar</h4>
                        <p class="text-xs text-slate-600 mb-4 px-2 leading-relaxed">
                            Mohon segera lakukan pelunasan melalui QRIS di bawah ini. Tunjukkan bukti transfer ke kasir.
                        </p>

                        <div class="bg-white p-2 border border-slate-200 rounded-lg shadow-sm mb-3">
                            <img src="https://tokosancaka.com/storage/loundry/qris_loundry.jpeg" alt="QRIS Pembayaran" class="w-32 h-32 md:w-40 md:h-40 object-contain">
                        </div>

                        <a href="https://tokosancaka.com/storage/loundry/qris_loundry.jpeg" download target="_blank"
                           class="flex items-center justify-center gap-2 px-4 py-2 bg-amber-600 text-white rounded-lg text-xs font-bold shadow hover:bg-amber-700 transition w-full">
                            <i class="fas fa-download"></i> Download QRIS
                        </a>
                    </div>
                </div>
                @endif

                @if($order->payment_status == 'unpaid' && in_array($order->payment_method, ['dana', 'dana_sdk', 'tripay', 'doku']))
                <div class="p-6 bg-blue-50 border-t border-dashed border-slate-300 no-print flex-grow">
                    <div class="flex items-center justify-center gap-2 mb-2 text-center">
                        <i class="fas fa-wallet text-blue-600 text-xl"></i>
                    </div>
                    <h4 class="text-sm font-bold text-blue-900 text-center uppercase mb-1">Pilih Metode Pembayaran</h4>
                    <p class="text-xs text-slate-600 text-center mb-4 leading-relaxed">Cek daftar promo dan selesaikan pembayaran Anda secara online via DANA/QRIS.</p>

                    <button onclick="fetchConsultPay({{ $order->final_price }})" id="btn-consult-pay" class="w-full py-3 bg-blue-600 text-white rounded-xl text-xs font-bold shadow-lg hover:bg-blue-700 transition flex justify-center items-center gap-2 active:scale-95">
                        <i class="fas fa-search"></i> Cek Pembayaran DANA
                    </button>

                    <div id="consult-pay-result" class="mt-4 hidden space-y-2"></div>
                </div>
                @endif
            </div>

            <div class="w-full md:w-7/12 flex flex-col bg-white">

                <div class="p-6 flex-grow overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-left text-[10px] text-slate-400 uppercase border-b-2 border-slate-200">
                                <th class="pb-3 font-bold">Produk</th>
                                <th class="pb-3 text-center font-bold w-12">Qty</th>
                                <th class="pb-3 text-right font-bold w-20">Harga</th>
                                <th class="pb-3 text-right font-bold w-24">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody class="text-slate-600">
                            @foreach($order->items as $item)
                            <tr class="border-b border-slate-100 last:border-0 hover:bg-slate-50 transition">
                                <td class="py-4 pr-2">
                                    <div class="font-bold text-slate-800">{{ $item->product_name }}</div>
                                    @if($item->product && $item->product->sku)
                                        <div class="text-[10px] text-slate-400 font-mono mt-0.5">SKU: {{ $item->product->sku }}</div>
                                    @endif
                                </td>
                                <td class="py-4 text-center font-bold">{{ $item->quantity }}</td>
                                <td class="py-4 text-right text-xs">Rp {{ number_format($item->price_at_order,0,',','.') }}</td>
                                <td class="py-4 text-right font-bold text-slate-800">
                                    Rp {{ number_format($item->subtotal,0,',','.') }}
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="bg-slate-50 p-6 border-t border-slate-200">
                    <div class="w-full sm:w-2/3 ml-auto space-y-2">
                        <div class="flex justify-between text-sm text-slate-500">
                            <span>Subtotal Produk</span>
                            <span class="font-medium">Rp {{ number_format($order->total_price, 0, ',', '.') }}</span>
                        </div>

                        @if($order->discount_amount > 0)
                        <div class="flex justify-between text-sm text-emerald-600 font-bold">
                            <span>Diskon</span>
                            <span>- Rp {{ number_format($order->discount_amount, 0, ',', '.') }}</span>
                        </div>
                        @endif

                        @if($order->shipping_cost > 0)
                        <div class="flex justify-between text-sm text-blue-600">
                            <span>Ongkos Kirim</span>
                            <span>+ Rp {{ number_format($order->shipping_cost, 0, ',', '.') }}</span>
                        </div>
                        @endif

                        <div class="border-t border-dashed border-slate-300 my-3 pt-3"></div>

                        <div class="flex justify-between items-center">
                            <span class="font-black text-slate-800 text-lg uppercase tracking-wide">TOTAL</span>
                            <span class="font-black text-2xl {{ $order->payment_status == 'unpaid' ? 'text-red-600' : 'text-slate-900' }}">
                                Rp {{ number_format($order->final_price, 0, ',', '.') }}
                            </span>
                        </div>
                    </div>
                </div>

                <div class="p-6 bg-white border-t border-slate-200 flex flex-col sm:flex-row gap-3 no-print">

                    @auth
                        <a href="{{ route('orders.index') }}" class="flex-1 py-3 text-center bg-slate-100 rounded-xl font-bold text-sm text-slate-600 hover:bg-slate-200 transition">
                            <i class="fas fa-arrow-left"></i> Kembali ke Kasir
                        </a>
                    @else
                        <a href="{{ url('/') }}" class="flex-1 py-3 text-center bg-slate-100 rounded-xl font-bold text-sm text-slate-600 hover:bg-slate-200 transition">
                            <i class="fas fa-home"></i> Beranda Toko
                        </a>
                    @endauth

                    <button onclick="printStrukThermal()" class="flex-1 sm:min-w-[120px] py-3 text-center bg-blue-600 text-white rounded-xl font-bold text-sm shadow-lg hover:bg-blue-700 transition">
                        <i class="fas fa-receipt mr-1"></i> Cetak 58mm
                    </button>

                    <button onclick="window.print()" class="flex-1 py-3 text-center bg-slate-900 text-white rounded-xl font-bold text-sm shadow-lg hover:bg-slate-800 transition">
                        <i class="fas fa-print mr-1"></i> Cetak A4
                    </button>
                </div>
            </div>

        </div>
    </div>

    <script>
    function printStrukThermal() {
        const printUrl = "{{ url('/orders') }}/{{ $order->id }}/print-struk";
        const printWindow = window.open(printUrl, 'PrintStruk', 'width=400,height=600');
        if (printWindow) {
            printWindow.focus();
        } else {
            alert('Mohon izinkan popup di browser Anda untuk mencetak struk.');
        }
    }

    // =================================================================
    // FUNGSI JAVASCRIPT UNTUK MEMANGGIL CONSULT PAY DANA
    // =================================================================
    async function fetchConsultPay(amount) {
        const btn = document.getElementById('btn-consult-pay');
        const resultDiv = document.getElementById('consult-pay-result');

        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sedang Mencari...';
        btn.disabled = true;
        resultDiv.innerHTML = '';
        resultDiv.classList.add('hidden');

        try {
            const response = await fetch(`/dana/consult-pay?amount=${amount}`, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                }
            });

            const res = await response.json();

            if (res.status === 'success' && res.payment_methods.length > 0) {
                let html = '<div class="grid grid-cols-1 gap-2 mt-3 text-left">';

                res.payment_methods.forEach(method => {
                    let promoBadge = '';
                    if (method.promoInfos && method.promoInfos.length > 0) {
                        promoBadge = `<span class="bg-red-100 text-red-600 px-2 py-0.5 rounded text-[10px] font-bold ml-2 animate-pulse"><i class="fas fa-tags"></i> Promo!</span>`;
                    }

                    let rawName = method.payOption ? method.payOption : method.payMethod;
                    let methodName = rawName.replace(/_/g, ' ');

                    // ==========================================
                    // MAPPING LOGO BERDASARKAN STRING
                    // ==========================================
                    let logoUrl = '';
                    let searchString = rawName.toUpperCase();
                    const baseUrl = "{{ url('/assets') }}/"; // Pastikan aset gambar ada di folder public/assets/

                    if (searchString.includes('BNI')) logoUrl = baseUrl + 'bni.webp';
                    else if (searchString.includes('BRI')) logoUrl = baseUrl + 'bri.webp';
                    else if (searchString.includes('BCA')) logoUrl = baseUrl + 'bca.webp';
                    else if (searchString.includes('MANDIRI')) logoUrl = baseUrl + 'mandiri.webp';
                    else if (searchString.includes('QRIS')) logoUrl = baseUrl + 'qris.png';
                    else if (searchString.includes('GOPAY')) logoUrl = baseUrl + 'gopay.webp';
                    else if (method.payMethod === 'BALANCE') logoUrl = baseUrl + 'dana.webp';

                    let iconHtml = '';
                    if (logoUrl) {
                        iconHtml = `<img src="${logoUrl}" alt="${methodName}" class="w-10 h-6 object-contain">`;
                    } else {
                        iconHtml = `<div class="w-10 h-6 rounded bg-blue-50 flex justify-center items-center text-blue-500"><i class="fas fa-credit-card"></i></div>`;
                    }

                    html += `
                    <div class="bg-white p-3 rounded-lg border border-blue-200 flex justify-between items-center shadow-sm hover:border-blue-400 transition cursor-default">
                        <div class="flex items-center gap-3 w-full">
                            <div class="flex-shrink-0 flex items-center justify-center w-10">
                                ${iconHtml}
                            </div>
                            <div class="flex flex-col text-left">
                                <span class="text-xs font-bold text-slate-700">${methodName}</span>
                                <span class="text-[9px] text-slate-400 uppercase">${method.payMethod}</span>
                            </div>
                        </div>
                        ${promoBadge}
                    </div>
                    `;
                });

                html += '</div>';

                // Tampilkan Tombol Bayar jika ada URL Pembayaran
                @if($order->payment_url)
                html += `
                <a href="{{ $order->payment_url }}" class="mt-4 w-full py-3 bg-emerald-500 text-white rounded-xl text-sm font-bold shadow-lg hover:bg-emerald-600 transition flex justify-center items-center gap-2 text-center">
                    <i class="fas fa-lock"></i> Lanjutkan Pembayaran
                </a>`;
                @endif

                resultDiv.innerHTML = html;
                resultDiv.classList.remove('hidden');
            } else {
                resultDiv.innerHTML = `<div class="text-xs text-red-600 p-3 bg-red-50 border border-red-200 rounded-lg text-center"><i class="fas fa-exclamation-circle"></i> Tidak ada metode pembayaran tambahan yang tersedia.</div>`;
                resultDiv.classList.remove('hidden');
            }
        } catch (error) {
            resultDiv.innerHTML = `<div class="text-xs text-red-600 p-3 bg-red-50 border border-red-200 rounded-lg text-center"><i class="fas fa-wifi"></i> Gagal terhubung ke server DANA.</div>`;
            resultDiv.classList.remove('hidden');
        } finally {
            btn.innerHTML = '<i class="fas fa-search"></i> Refresh Pembayaran DANA';
            btn.disabled = false;
        }
    }
    </script>
</body>
</html>
