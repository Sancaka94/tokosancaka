<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice #{{ $order->order_number }} - Sancaka POS</title>
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
                <p class="text-xs text-slate-400">Jl. Contoh No. 123, Ngawi, Jawa Timur</p>
                <p class="text-xs text-slate-400">WhatsApp: 0857-4580-8809</p>
            </div>
            <div class="absolute -top-10 -right-10 w-32 h-32 bg-slate-800 rounded-full opacity-50"></div>
            <div class="absolute -bottom-10 -left-10 w-32 h-32 bg-slate-800 rounded-full opacity-50"></div>
        </div>

        <div class="p-6 border-b border-dashed border-slate-300 bg-slate-50/50">
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

            <div class="flex items-center justify-between bg-white p-2 rounded-lg border border-slate-200">
                <span class="text-xs font-bold text-slate-500">Status Pembayaran</span>
                @if($order->payment_status == 'paid')
                    <span class="bg-emerald-100 text-emerald-700 text-[10px] font-black px-2 py-1 rounded uppercase">LUNAS</span>
                @else
                    <span class="bg-amber-100 text-amber-700 text-[10px] font-black px-2 py-1 rounded uppercase">BELUM LUNAS</span>
                @endif
            </div>
        </div>

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
                            <div class="text-[10px] text-slate-400">@ Rp {{ number_format($item->price_at_order, 0, ',', '.') }}</div>
                        </td>
                        <td class="py-2 text-right align-top font-bold">x{{ $item->quantity }}</td>
                        <td class="py-2 text-right align-top font-bold text-slate-800">
                            Rp {{ number_format($item->subtotal, 0, ',', '.') }}
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
                <span>Diskon Kupon</span>
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
                <span class="font-black text-slate-800 text-lg">Total</span>
                <span class="font-black text-slate-800 text-lg">Rp {{ number_format($order->final_price, 0, ',', '.') }}</span>
            </div>

            <div class="text-[10px] text-slate-400 text-center mt-4">
                Metode Bayar: <span class="font-bold uppercase text-slate-600">{{ str_replace('_', ' ', $order->payment_method) }}</span>
            </div>
        </div>

        <div class="p-6 bg-white border-t border-slate-200 flex gap-3 no-print">
            <a href="{{ route('orders.create') }}" class="flex-1 py-3 text-center rounded-xl bg-slate-100 text-slate-600 font-bold text-sm hover:bg-slate-200 transition">
                <i class="fas fa-arrow-left mr-1"></i> Kembali
            </a>
            <button onclick="window.print()" class="flex-1 py-3 text-center rounded-xl bg-slate-900 text-white font-bold text-sm hover:bg-slate-800 transition shadow-lg shadow-slate-200">
                <i class="fas fa-print mr-1"></i> Cetak Struk
            </button>
        </div>

    </div>

</body>
</html>
