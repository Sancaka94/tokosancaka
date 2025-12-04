@extends('layouts.customer')

@section('title', 'Riwayat Transaksi Digital')

@section('content')
<div class="space-y-6">
    
    {{-- Header Section --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Riwayat Transaksi PPOB</h2>
            <p class="text-sm text-gray-500 mt-1">Daftar pembelian pulsa, data, dan pembayaran tagihan Anda.</p>
        </div>
        
        {{-- Tombol Export (EXCEL & PDF) --}}
        <div class="flex gap-2">
            {{-- Tombol Excel --}}
            <a href="{{ route('customer.ppob.export.excel') }}" class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-xs font-bold uppercase tracking-widest rounded-lg transition shadow-sm">
                <i class="fas fa-file-excel mr-2 text-sm"></i> ExportExcel
            </a>
            {{-- Tombol PDF --}}
            <a href="{{ route('customer.ppob.export.pdf') }}" class="inline-flex items-center px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-xs font-bold uppercase tracking-widest rounded-lg transition shadow-sm">
                <i class="fas fa-file-pdf mr-2 text-sm"></i> ExportPDF
            </a>
        </div>
    </div>

    {{-- Filter Section --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
        <form action="{{ route('customer.ppob.history') }}" method="GET">
            <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end">
                
                {{-- Search --}}
                <div class="md:col-span-4">
                    <label class="block text-xs font-medium text-gray-700 mb-1">Cari Transaksi</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-search text-gray-400"></i>
                        </span>
                        <input type="text" name="search" value="{{ request('search') }}" 
                               class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500" 
                               placeholder="Order ID, No HP, atau Produk...">
                    </div>
                </div>

                {{-- Status --}}
                <div class="md:col-span-3">
                    <label class="block text-xs font-medium text-gray-700 mb-1">Status</label>
                    <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Semua Status</option>
                        <option value="Success" {{ request('status') == 'Success' ? 'selected' : '' }}>Berhasil</option>
                        <option value="Pending" {{ request('status') == 'Pending' ? 'selected' : '' }}>Menunggu</option>
                        <option value="Processing" {{ request('status') == 'Processing' ? 'selected' : '' }}>Diproses</option>
                        <option value="Failed" {{ request('status') == 'Failed' ? 'selected' : '' }}>Gagal</option>
                    </select>
                </div>

                {{-- Tanggal --}}
                <div class="md:col-span-3">
                    <label class="block text-xs font-medium text-gray-700 mb-1">Rentang Tanggal</label>
                    <div class="flex gap-2">
                        <input type="date" name="start_date" value="{{ request('start_date') }}" class="w-1/2 px-2 py-2 border border-gray-300 rounded-lg text-xs">
                        <input type="date" name="end_date" value="{{ request('end_date') }}" class="w-1/2 px-2 py-2 border border-gray-300 rounded-lg text-xs">
                    </div>
                </div>

                {{-- Tombol Filter --}}
                <div class="md:col-span-2">
                    <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 rounded-lg text-sm transition">
                        Filter
                    </button>
                </div>
            </div>
        </form>
    </div>

    {{-- Tabel Data --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-200 text-xs uppercase text-gray-500 font-semibold tracking-wider">
                        <th class="px-6 py-4">Produk</th>
                        <th class="px-6 py-4">Pelanggan</th>
                        <th class="px-6 py-4">Harga / Metode</th>
                        <th class="px-6 py-4">Status / SN</th>
                        <th class="px-6 py-4 text-right">Tanggal</th>
                        <th class="px-6 py-4 text-center">Detail</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($transactions as $trx)
                    <tr class="hover:bg-gray-50 transition duration-150">
                        
                        {{-- 1. PRODUK & LOGO --}}
                        <td class="px-6 py-4 align-top">
                            <div class="flex items-center">
                                @php
                                    $sku = strtolower($trx->buyer_sku_code);
                                    // Logika Logo Sederhana
                                    $logo = 'https://cdn-icons-png.flaticon.com/512/1067/1067566.png'; // Default
                                    if(str_contains($sku, 'pln')) $logo = 'https://tokosancaka.com/public/storage/logo-ppob/pln.png';
                                    elseif(str_contains($sku, 'bpjs')) $logo = 'https://tokosancaka.com/public/storage/logo-ppob/bpjs.png';
                                    elseif(str_contains($sku, 'telkomsel') || str_contains($sku, 'simpati')) $logo = 'https://upload.wikimedia.org/wikipedia/commons/b/bc/Telkomsel_2021_icon.svg';
                                    elseif(str_contains($sku, 'indosat')) $logo = 'https://upload.wikimedia.org/wikipedia/commons/a/ac/Indosat_Ooredoo_Logo.png';
                                    elseif(str_contains($sku, 'xl')) $logo = 'https://upload.wikimedia.org/wikipedia/commons/5/55/XL_Axiata_2016_icon.svg';
                                    elseif(str_contains($sku, 'dana')) $logo = 'https://upload.wikimedia.org/wikipedia/commons/7/72/Logo_dana_blue.svg';
                                    elseif(str_contains($sku, 'gopay')) $logo = 'https://upload.wikimedia.org/wikipedia/commons/8/86/Gopay_logo.svg';
                                @endphp
                                <div class="h-10 w-10 flex-shrink-0 mr-3 bg-white border border-gray-200 rounded-full p-1 flex items-center justify-center overflow-hidden">
                                    <img class="h-full w-full object-contain" src="{{ $logo }}" alt="{{ $trx->buyer_sku_code }}">
                                </div>
                                <div>
                                    <div class="text-sm font-bold text-gray-900 uppercase">{{ $trx->buyer_sku_code }}</div>
                                    <div class="text-xs text-gray-500 font-mono">{{ $trx->order_id }}</div>
                                </div>
                            </div>
                        </td>

                        {{-- 2. PELANGGAN --}}
                        <td class="px-6 py-4 align-top">
                            <div class="text-sm font-medium text-gray-900 font-mono">{{ $trx->customer_no }}</div>
                            <div class="text-xs text-gray-500">ID Pelanggan</div>
                        </td>

                        {{-- 3. HARGA & METODE --}}
                        <td class="px-6 py-4 align-top">
                            <div class="text-sm font-bold text-gray-900">Rp {{ number_format($trx->selling_price, 0, ',', '.') }}</div>
                            <div class="text-xs text-gray-500 uppercase">{{ $trx->payment_method ?? 'Unknown' }}</div>
                        </td>

                        {{-- 4. STATUS & SN --}}
                        <td class="px-6 py-4 align-top">
                            @php
                                $statusClasses = match($trx->status) {
                                    'Success' => 'bg-green-100 text-green-800 border-green-200',
                                    'Pending' => 'bg-yellow-100 text-yellow-800 border-yellow-200',
                                    'Processing' => 'bg-blue-100 text-blue-800 border-blue-200',
                                    'Failed' => 'bg-red-100 text-red-800 border-red-200',
                                    default => 'bg-gray-100 text-gray-800 border-gray-200',
                                };
                            @endphp
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium border {{ $statusClasses }} mb-1">
                                {{ $trx->status }}
                            </span>
                            
                            @if($trx->sn)
                                <div class="mt-1">
                                    <code class="text-xs bg-gray-100 px-2 py-1 rounded border border-gray-300 select-all font-mono text-gray-600 block max-w-[160px] truncate" title="{{ $trx->sn }}">
                                        SN: {{ $trx->sn }}
                                    </code>
                                </div>
                            @elseif($trx->status == 'Failed')
                                <div class="mt-1 text-xs text-red-500 italic max-w-[160px] truncate" title="{{ $trx->message }}">
                                    {{ $trx->message }}
                                </div>
                            @endif
                        </td>

                        {{-- 5. TANGGAL --}}
                        <td class="px-6 py-4 align-top text-right">
                            <div class="text-sm text-gray-900 font-medium">{{ $trx->created_at->format('d M Y') }}</div>
                            <div class="text-xs text-gray-500">{{ $trx->created_at->format('H:i') }} WIB</div>
                        </td>

                        {{-- 6. AKSI --}}
                        <td class="px-6 py-4 align-top text-center">
                            <a href="{{ route('ppob.invoice', ['invoice' => $trx->order_id]) }}" 
                               class="text-gray-400 hover:text-blue-600 transition duration-150" 
                               title="Lihat Invoice">
                                <i class="fas fa-eye text-lg"></i>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center">
                            <div class="flex flex-col items-center justify-center">
                                <div class="bg-gray-100 p-4 rounded-full mb-3">
                                    <i class="fas fa-receipt text-3xl text-gray-400"></i>
                                </div>
                                <h3 class="text-lg font-medium text-gray-900">Belum ada transaksi</h3>
                                <p class="text-gray-500 text-sm mt-1">Transaksi PPOB Anda akan muncul di sini.</p>
                                <a href="https://tokosancaka.com/daftar-harga" class="mt-4 px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-bold hover:bg-blue-700 transition">
                                    Mulai Transaksi
                                </a>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        {{-- Pagination --}}
        <div class="bg-gray-50 px-6 py-4 border-t border-gray-200">
            {{ $transactions->links() }}
        </div>
    </div>

</div>
@endsection