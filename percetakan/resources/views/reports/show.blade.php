<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Pesanan #{{ $order->order_number }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-slate-50 font-sans text-slate-800 p-6">

    <div class="max-w-4xl mx-auto bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden">
        
        <div class="bg-slate-800 text-white p-6 flex justify-between items-center">
            <div>
                <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">Detail Pesanan</p>
                <h1 class="text-2xl font-black">#{{ $order->order_number }}</h1>
            </div>
            <div class="text-right">
                <p class="text-sm font-medium opacity-80">{{ $order->created_at->translatedFormat('d F Y, H:i') }}</p>
                <div class="mt-1">
                    <span class="px-2 py-1 text-[10px] font-bold uppercase rounded bg-white/20 text-white border border-white/20">
                        {{ $order->status }}
                    </span>
                </div>
            </div>
        </div>

        <div class="p-8 grid grid-cols-1 md:grid-cols-2 gap-8">
            <div>
                <h3 class="text-xs font-black text-slate-400 uppercase tracking-widest mb-3 border-b pb-2">Informasi Pelanggan</h3>
                <p class="font-bold text-lg text-slate-800">{{ $order->customer_name }}</p>
                <p class="text-slate-500 text-sm mt-1">
                    <i class="fas fa-phone mr-1"></i> {{ $order->customer_phone ?? '-' }}
                </p>
                
                <h3 class="text-xs font-black text-slate-400 uppercase tracking-widest mt-6 mb-3 border-b pb-2">Catatan</h3>
                <p class="text-sm text-slate-600 italic bg-slate-50 p-3 rounded-xl border border-slate-100">
                    "{{ $order->note ?? 'Tidak ada catatan' }}"
                </p>
            </div>

            <div>
                <h3 class="text-xs font-black text-slate-400 uppercase tracking-widest mb-3 border-b pb-2">Berkas Lampiran</h3>
                @if($order->attachments->count() > 0)
                    <div class="space-y-2">
                        @foreach($order->attachments as $file)
                        <a href="{{ asset('storage/' . $file->file_path) }}" target="_blank" 
                           class="flex items-center gap-3 p-3 bg-slate-50 border border-slate-200 rounded-xl hover:border-red-300 hover:bg-red-50 transition group">
                            <div class="h-10 w-10 bg-white rounded-lg flex items-center justify-center text-red-500 shadow-sm group-hover:scale-110 transition-transform">
                                <i class="fas fa-file-download"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-bold text-slate-700 truncate group-hover:text-red-600">{{ $file->file_name }}</p>
                                <p class="text-[10px] text-slate-400 uppercase">{{ $file->file_type }}</p>
                            </div>
                            <i class="fas fa-external-link-alt text-slate-300 group-hover:text-red-500"></i>
                        </a>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-6 bg-slate-50 rounded-xl border border-dashed border-slate-200">
                        <p class="text-sm text-slate-400">Tidak ada file dilampirkan</p>
                    </div>
                @endif
            </div>
        </div>

        <div class="p-8 border-t border-slate-100">
            <h3 class="text-xs font-black text-slate-400 uppercase tracking-widest mb-4">Rincian Item</h3>
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 text-slate-500 font-bold">
                    <tr>
                        <th class="px-4 py-3 rounded-l-lg">Produk</th>
                        <th class="px-4 py-3 text-center">Qty</th>
                        <th class="px-4 py-3 text-right">Harga</th>
                        <th class="px-4 py-3 text-right rounded-r-lg">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($order->details as $item)
                    <tr>
                        <td class="px-4 py-3 font-bold text-slate-700">{{ $item->product_name }}</td>
                        <td class="px-4 py-3 text-center">{{ $item->qty }}</td>
                        <td class="px-4 py-3 text-right text-slate-500">Rp {{ number_format($item->price, 0, ',', '.') }}</td>
                        <td class="px-4 py-3 text-right font-bold text-slate-800">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot class="border-t border-slate-200">
                    <tr>
                        <td colspan="3" class="px-4 py-4 text-right font-black text-slate-500 uppercase tracking-widest text-xs">Total Akhir</td>
                        <td class="px-4 py-4 text-right font-black text-xl text-red-600">Rp {{ number_format($order->final_price, 0, ',', '.') }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <div class="bg-slate-50 p-6 flex justify-end gap-3 border-t border-slate-100">
            <a href="{{ route('reports.index') }}" class="px-6 py-2.5 rounded-xl text-sm font-bold text-slate-600 hover:bg-slate-200 transition">Kembali</a>
            <a href="{{ route('reports.edit', $order->id) }}" class="px-6 py-2.5 rounded-xl text-sm font-bold bg-slate-800 text-white hover:bg-black transition">Edit Status</a>
        </div>
    </div>

</body>
</html>