<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice #{{ $order->order_number }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @media print {
            .no-print { display: none !important; }
            body { background: white; }
            .print-container { box-shadow: none; border: none; margin: 0; padding: 0; }
        }
    </style>
</head>
<body class="bg-slate-100 font-sans text-slate-800 min-h-screen flex items-center justify-center p-4">

    <div class="print-container w-full max-w-md bg-white rounded-2xl shadow-xl overflow-hidden border border-slate-200">

        <div class="bg-slate-900 text-white p-6 text-center relative overflow-hidden">
            <div class="relative z-10">
                <h1 class="text-2xl font-black tracking-widest uppercase mb-1">Sancaka Store</h1>
                <p class="text-xs text-slate-400">Jl.Dr.Wahidin No.18A RT.22/05 Ketanggi Ngawi 63211</p>
                <p class="text-xs text-slate-400">WhatsApp: 0857-4580-8809</p>
            </div>
            <div class="absolute -top-10 -right-10 w-32 h-32 bg-slate-800 rounded-full opacity-50"></div>
            <div class="absolute -bottom-10 -left-10 w-32 h-32 bg-slate-800 rounded-full opacity-50"></div>
        </div>

        <div class="p-6 border-b border-dashed border-slate-300 bg-slate-50">
            <div class="flex justify-between items-start mb-4">
                <div>
                    <p class="text-[10px] font-bold text-slate-400 uppercase">Pelanggan</p>
                    <h3 class="font-bold text-sm text-slate-800">{{ $order->customer_name }}</h3>
                    <p class="text-xs text-slate-500">{{ $order->customer_phone }}</p>
                </div>
                <div class="text-right">
                    <p class="text-[10px] font-bold text-slate-400 uppercase">Invoice</p>
                    <h3 class="font-bold text-sm text-slate-800">#{{ $order->order_number }}</h3>
                    <p class="text-xs text-slate-500">{{ $order->created_at->format('d M Y, H:i') }}</p>
                </div>
            </div>

            <div class="bg-white p-2 border rounded text-center flex justify-between items-center">
                <span class="text-xs font-bold text-slate-500">Status Pembayaran</span>
                <span class="font-black uppercase text-xs px-2 py-1 rounded {{ $order->payment_status == 'paid' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                    {{ $order->payment_status == 'paid' ? 'LUNAS' : 'BELUM LUNAS' }}
                </span>
            </div>
        </div>

        @if($order->payment_method == 'pay_later' && $order->payment_status == 'unpaid')
        <div class="p-6 bg-amber-50 border-b border-dashed border-slate-300 text-center">
            <div class="flex flex-col items-center">
                <div class="bg-amber-100 text-amber-600 w-10 h-10 rounded-full flex items-center justify-center mb-2">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <h4 class="text-sm font-bold text-amber-800 uppercase mb-1">Tagihan Belum Dibayar</h4>
                <p class="text-xs text-slate-600 mb-4 px-4 leading-relaxed">
                    Mohon segera lakukan pelunasan melalui QRIS di bawah ini. Tunjukkan bukti transfer ke kasir.
                </p>

                <div class="bg-white p-2 border border-slate-200 rounded-lg shadow-sm mb-3">
                    <img src="https://tokosancaka.com/storage/loundry/qris_loundry.jpeg" alt="QRIS Pembayaran" class="w-32 h-32 object-contain">
                </div>

                <a href="https://tokosancaka.com/storage/loundry/qris_loundry.jpeg" download target="_blank"
                   class="flex items-center gap-2 px-4 py-2 bg-amber-600 text-white rounded-lg text-xs font-bold shadow hover:bg-amber-700 transition">
                    <i class="fas fa-download"></i> Download QRIS
                </a>
            </div>
        </div>
        @endif

        {{-- ================================================================= --}}
        {{-- FITUR BARU: CONSULT PAY DANA (Hanya muncul jika belum lunas) --}}
        {{-- ================================================================= --}}
        @if($order->payment_status == 'unpaid')
        <div class="p-6 bg-blue-50 border-b border-dashed border-slate-300 no-print">
            <div class="flex items-center gap-2 mb-2">
                <i class="fas fa-wallet text-blue-600"></i>
                <h4 class="text-sm font-bold text-blue-900 uppercase">Promo & Metode DANA</h4>
            </div>
            <p class="text-xs text-slate-600 mb-4">Cek daftar promo dan metode pembayaran yang tersedia di DANA untuk tagihan ini.</p>

            <button onclick="fetchConsultPay({{ $order->final_price }})" id="btn-consult-pay" class="w-full py-2.5 bg-blue-600 text-white rounded-lg text-xs font-bold shadow hover:bg-blue-700 transition flex justify-center items-center gap-2">
                <i class="fas fa-search"></i> Cek Pembayaran DANA
            </button>

            <div id="consult-pay-result" class="mt-4 hidden space-y-2"></div>
        </div>
        @endif
        {{-- ================================================================= --}}

        <div class="p-6">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-[10px] text-slate-400 uppercase border-b border-slate-200">
                        <th class="pb-2 font-bold">Produk</th>
                        <th class="pb-2 text-right font-bold">Jml</th>
                        <th class="pb-2 text-right font-bold">Total</th>
                    </tr>
                </thead>
                <tbody class="text-slate-600">
                    @foreach($order->items as $item)
                    <tr class="border-b border-dashed border-slate-100 last:border-0">
                        <td class="py-2 pr-2">
                            <div class="font-bold text-slate-800">{{ $item->product_name }}</div>
                            <div class="text-[10px] text-slate-400">@ Rp {{ number_format($item->price_at_order,0,',','.') }}</div>
                        </td>
                        <td class="py-2 text-right align-top font-bold">x{{ $item->quantity }}</td>
                        <td class="py-2 text-right align-top font-bold text-slate-800">
                            Rp {{ number_format($item->subtotal,0,',','.') }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="bg-slate-50 p-6 border-t border-slate-200 space-y-2">
            <div class="flex justify-between text-xs text-slate-500">
                <span>Subtotal</span>
                <span>Rp {{ number_format($order->total_price, 0, ',', '.') }}</span>
            </div>

            @if($order->discount_amount > 0)
            <div class="flex justify-between text-xs text-emerald-600 font-bold">
                <span>Diskon</span>
                <span>- Rp {{ number_format($order->discount_amount, 0, ',', '.') }}</span>
            </div>
            @endif

            @if($order->shipping_cost > 0)
            <div class="flex justify-between text-xs text-blue-600">
                <span>Ongkir ({{ $order->courier_service }})</span>
                <span>+ Rp {{ number_format($order->shipping_cost, 0, ',', '.') }}</span>
            </div>
            @endif

            <div class="border-t border-dashed border-slate-300 my-2 pt-2"></div>

            <div class="flex justify-between items-center">
                <span class="font-black text-slate-800 text-lg">TOTAL TAGIHAN</span>
                <span class="font-black text-slate-800 text-lg {{ $order->payment_status == 'unpaid' ? 'text-red-600' : '' }}">
                    Rp {{ number_format($order->final_price, 0, ',', '.') }}
                </span>
            </div>

            <div class="text-[10px] text-slate-400 text-center mt-4 bg-white p-2 rounded border border-slate-100">
                Metode Bayar: <span class="font-bold uppercase text-slate-600">{{ str_replace('_', ' ', $order->payment_method) }}</span>
            </div>
        </div>

        <div class="p-6 bg-white border-t border-slate-200 flex gap-3 no-print">
            <a href="{{ route('orders.create') }}" class="flex-1 py-3 text-center bg-slate-100 rounded-xl font-bold text-sm text-slate-600 hover:bg-slate-200 transition">
                <i class="fas fa-arrow-left"></i> Kembali
            </a>

            <button onclick="printStrukThermal()" class="flex-1 min-w-[120px] py-3 text-center bg-blue-600 text-white rounded-xl font-bold text-sm shadow-lg hover:bg-blue-700 transition">
                <i class="fas fa-receipt"></i> Cetak 58mm
            </button>

            <button onclick="window.print()" class="flex-1 py-3 text-center bg-slate-900 text-white rounded-xl font-bold text-sm shadow-lg hover:bg-slate-800 transition">
                <i class="fas fa-print"></i> Cetak A4
            </button>
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

        // Ubah tampilan tombol menjadi loading
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sedang Mencari...';
        btn.disabled = true;
        resultDiv.innerHTML = '';
        resultDiv.classList.add('hidden');

        try {
            // Memanggil endpoint DANA Consult Pay yang kita buat di Controller
            const response = await fetch(`/dana/consult-pay?amount=${amount}`, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                }
            });

            const res = await response.json();

            if (res.status === 'success' && res.payment_methods.length > 0) {
                let html = '<div class="grid grid-cols-1 gap-2 mt-3">';

                res.payment_methods.forEach(method => {
                    // Cek jika ada promo
                    let promoBadge = '';
                    if (method.promoInfos && method.promoInfos.length > 0) {
                        promoBadge = `<span class="bg-red-100 text-red-600 px-2 py-0.5 rounded text-[10px] font-bold ml-2 animate-pulse"><i class="fas fa-tags"></i> Ada Promo!</span>`;
                    }

                    // Format nama metode biar lebih rapi
                    let rawName = method.payOption ? method.payOption : method.payMethod;
                    let methodName = rawName.replace(/_/g, ' ');

                    html += `
                    <div class="bg-white p-3 rounded-lg border border-blue-200 flex justify-between items-center shadow-sm hover:border-blue-400 transition">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded bg-blue-50 flex justify-center items-center text-blue-500">
                                <i class="fas fa-credit-card"></i>
                            </div>
                            <div class="flex flex-col text-left">
                                <span class="text-xs font-bold text-slate-700">${methodName}</span>
                                <span class="text-[10px] text-slate-400 uppercase">${method.payMethod}</span>
                            </div>
                        </div>
                        ${promoBadge}
                    </div>
                    `;
                });

                html += '</div>';

                // Jika pesanan dibuat menggunakan DANA, tampilkan tombol bayar juga
                @if($order->payment_url)
                html += `
                <a href="{{ $order->payment_url }}" class="mt-3 w-full py-2.5 bg-green-500 text-white rounded-lg text-xs font-bold shadow hover:bg-green-600 transition flex justify-center items-center gap-2 block text-center">
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
            // Kembalikan tombol ke bentuk semula
            btn.innerHTML = '<i class="fas fa-search"></i> Cek Pembayaran DANA';
            btn.disabled = false;
        }
    }
    </script>
</body>
</html>
