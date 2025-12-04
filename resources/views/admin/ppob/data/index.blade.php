@extends('layouts.admin')

@section('title', 'Data Seluruh Transaksi PPOB')

@section('content')
<div class="space-y-6">
    
    {{-- ================= HEADER SECTION ================= --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Data Transaksi PPOB</h2>
            <p class="text-sm text-gray-500 mt-1">Monitoring seluruh transaksi produk digital member.</p>
        </div>
        
        {{-- Tombol Export --}}
        <div class="flex gap-2">
            {{-- Pastikan route export sudah dibuat di web.php --}}
            <a href="{{ route('admin.ppob.export.excel') }}" class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-xs font-bold uppercase tracking-widest rounded-lg transition shadow-sm">
                <i class="fas fa-file-excel mr-2 text-sm"></i> Export Excel
            </a>
            <a href="{{ route('admin.ppob.export.pdf') }}" class="inline-flex items-center px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-xs font-bold uppercase tracking-widest rounded-lg transition shadow-sm">
                <i class="fas fa-file-pdf mr-2 text-sm"></i> Export PDF
            </a>
        </div>
    </div>

    {{-- ================= FILTER SECTION ================= --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
        {{-- Mengarah ke route index saat ini --}}
        <form action="{{ route('admin.ppob.data.index') }}" method="GET">
            <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end">
                
                {{-- Search Input --}}
                <div class="md:col-span-4">
                    <label class="block text-xs font-medium text-gray-700 mb-1">Pencarian</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-search text-gray-400"></i>
                        </span>
                        <input type="text" name="search" value="{{ request('search') }}" 
                               class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500" 
                               placeholder="Cari Order ID, Nama User, No HP...">
                    </div>
                </div>

                {{-- Filter Status --}}
                <div class="md:col-span-3">
                    <label class="block text-xs font-medium text-gray-700 mb-1">Status</label>
                    <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Semua Status</option>
                        <option value="Success" {{ request('status') == 'Success' ? 'selected' : '' }}>Berhasil (Success)</option>
                        <option value="Pending" {{ request('status') == 'Pending' ? 'selected' : '' }}>Menunggu (Pending)</option>
                        <option value="Processing" {{ request('status') == 'Processing' ? 'selected' : '' }}>Diproses (Processing)</option>
                        <option value="Failed" {{ request('status') == 'Failed' ? 'selected' : '' }}>Gagal (Failed)</option>
                    </select>
                </div>

                {{-- Filter Tanggal --}}
                <div class="md:col-span-3">
                    <label class="block text-xs font-medium text-gray-700 mb-1">Periode Transaksi</label>
                    <div class="flex gap-2">
                        <input type="date" name="start_date" value="{{ request('start_date') }}" class="w-1/2 px-2 py-2 border border-gray-300 rounded-lg text-xs">
                        <input type="date" name="end_date" value="{{ request('end_date') }}" class="w-1/2 px-2 py-2 border border-gray-300 rounded-lg text-xs">
                    </div>
                </div>

                {{-- Tombol Submit --}}
                <div class="md:col-span-2">
                    <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 rounded-lg text-sm transition shadow-sm">
                        <i class="fas fa-filter mr-1"></i> Filter
                    </button>
                </div>
            </div>
        </form>
    </div>

    {{-- ================= TABLE DATA ================= --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-200 text-xs uppercase text-gray-500 font-semibold tracking-wider">
                        <th class="px-6 py-4">User / Pelanggan</th>
                        <th class="px-6 py-4">Produk</th>
                        <th class="px-6 py-4">Tujuan</th>
                        <th class="px-6 py-4">Harga & Profit</th>
                        <th class="px-6 py-4">Status / SN</th>
                        <th class="px-6 py-4 text-right">Waktu</th>
                        <th class="px-6 py-4 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($transactions as $trx)
                    <tr class="hover:bg-gray-50 transition duration-150">
                        
                        {{-- 1. USER INFO (Khusus Admin) --}}
                        <td class="px-6 py-4 align-top">
                            <div class="flex flex-col">
                                <span class="text-[10px] font-mono text-gray-400 mb-1">#{{ $trx->order_id }}</span>
                                <div class="flex items-center gap-3">
                                    <div class="h-9 w-9 rounded-full bg-blue-100 flex items-center justify-center text-blue-700 font-bold text-sm shadow-sm">
                                        {{ substr($trx->user->name ?? 'G', 0, 1) }}
                                    </div>
                                    <div>
                                        <div class="text-sm font-bold text-gray-900">{{ $trx->user->name ?? 'User Terhapus' }}</div>
                                        <div class="text-xs text-gray-500">{{ $trx->user->email ?? '-' }}</div>
                                    </div>
                                </div>
                            </div>
                        </td>

                        {{-- 2. PRODUK --}}
                        <td class="px-6 py-4 align-top">
                            <div class="flex items-center">
                                @php
                                    $sku = strtolower($trx->buyer_sku_code);
                                    // Logika Logo Cepat
                                    $logo = 'https://cdn-icons-png.flaticon.com/512/1067/1067566.png'; 
                                    if(str_contains($sku, 'pln')) $logo = 'https://upload.wikimedia.org/wikipedia/commons/9/97/Logo_PLN.png';
                                    elseif(str_contains($sku, 'telkomsel') || str_contains($sku, 'simpati')) $logo = 'https://upload.wikimedia.org/wikipedia/commons/b/bc/Telkomsel_2021_icon.svg';
                                    elseif(str_contains($sku, 'indosat')) $logo = 'https://upload.wikimedia.org/wikipedia/commons/a/ac/Indosat_Ooredoo_Logo.png';
                                    elseif(str_contains($sku, 'xl')) $logo = 'https://upload.wikimedia.org/wikipedia/commons/5/55/XL_Axiata_2016_icon.svg';
                                    elseif(str_contains($sku, 'axis')) $logo = 'https://upload.wikimedia.org/wikipedia/commons/8/83/Axis_logo_2015.svg';
                                    elseif(str_contains($sku, 'tri') || str_contains($sku, 'three')) $logo = 'https://upload.wikimedia.org/wikipedia/commons/thumb/8/80/Tri_2019_logo.svg/1200px-Tri_2019_logo.svg.png';
                                    elseif(str_contains($sku, 'dana')) $logo = 'https://upload.wikimedia.org/wikipedia/commons/7/72/Logo_dana_blue.svg';
                                    elseif(str_contains($sku, 'gopay')) $logo = 'https://upload.wikimedia.org/wikipedia/commons/8/86/Gopay_logo.svg';
                                    elseif(str_contains($sku, 'ovo')) $logo = 'https://upload.wikimedia.org/wikipedia/commons/e/eb/Logo_ovo_purple.svg';
                                @endphp
                                <div class="h-9 w-9 flex-shrink-0 mr-3 bg-white border border-gray-200 rounded-full p-1 flex items-center justify-center overflow-hidden">
                                    <img class="h-full w-full object-contain" src="{{ $logo }}" alt="{{ $trx->buyer_sku_code }}">
                                </div>
                                <div>
                                    <div class="text-sm font-medium text-gray-900 uppercase">{{ $trx->buyer_sku_code }}</div>
                                    <span class="text-[10px] text-gray-400">Produk</span>
                                </div>
                            </div>
                        </td>

                        {{-- 3. NOMOR TUJUAN --}}
                        <td class="px-6 py-4 align-top">
                            <div class="bg-gray-50 px-2 py-1 rounded border border-gray-200 inline-block">
                                <span class="text-sm font-mono font-bold text-gray-700">{{ $trx->customer_no }}</span>
                            </div>
                            <div class="text-[10px] text-gray-400 mt-1">No. Pelanggan</div>
                        </td>

                        {{-- 4. HARGA & PROFIT (Penting untuk Admin) --}}
                        <td class="px-6 py-4 align-top">
                            <div class="text-sm font-bold text-gray-900">
                                Rp {{ number_format($trx->selling_price, 0, ',', '.') }}
                            </div>
                            {{-- Indikator Profit --}}
                            <div class="text-xs mt-1 font-medium {{ $trx->profit > 0 ? 'text-green-600' : 'text-gray-500' }}">
                                <i class="fas fa-chart-line mr-1"></i>
                                + Rp {{ number_format($trx->profit, 0, ',', '.') }}
                            </div>
                            <div class="text-[10px] text-gray-400 mt-0.5 uppercase">
                                {{ $trx->payment_method ?? 'Unknown' }}
                            </div>
                        </td>

                        {{-- 5. STATUS & SN --}}
                        <td class="px-6 py-4 align-top">
                            @php
                                $statusClasses = match($trx->status) {
                                    'Success' => 'bg-green-100 text-green-700 border-green-200',
                                    'Pending' => 'bg-yellow-100 text-yellow-700 border-yellow-200',
                                    'Processing' => 'bg-blue-100 text-blue-700 border-blue-200',
                                    'Failed' => 'bg-red-100 text-red-700 border-red-200',
                                    default => 'bg-gray-100 text-gray-700 border-gray-200',
                                };
                                
                                $statusIcon = match($trx->status) {
                                    'Success' => 'fa-check-circle',
                                    'Pending' => 'fa-clock',
                                    'Processing' => 'fa-spinner fa-spin',
                                    'Failed' => 'fa-times-circle',
                                    default => 'fa-question-circle',
                                };
                            @endphp
                            
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium border {{ $statusClasses }} mb-2">
                                <i class="fas {{ $statusIcon }} mr-1.5"></i> {{ $trx->status }}
                            </span>
                            
                            @if($trx->sn)
                                <div class="mt-1">
                                    <code class="text-[11px] bg-gray-50 px-2 py-1 rounded border border-gray-200 select-all font-mono text-gray-600 block w-full max-w-[150px] truncate" title="{{ $trx->sn }}">
                                        SN: {{ $trx->sn }}
                                    </code>
                                </div>
                            @elseif($trx->status == 'Failed')
                                <div class="mt-1 text-[11px] text-red-500 italic leading-tight max-w-[150px]" title="{{ $trx->message }}">
                                    {{ Str::limit($trx->message ?? 'Gagal tanpa pesan', 40) }}
                                </div>
                            @endif
                        </td>

                        {{-- 6. TANGGAL --}}
                        <td class="px-6 py-4 align-top text-right whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900">{{ $trx->created_at->format('d M Y') }}</div>
                            <div class="text-xs text-gray-500">{{ $trx->created_at->format('H:i:s') }}</div>
                        </td>

                        {{-- 7. AKSI --}}
                        <td class="px-6 py-4 align-top text-center">
                            <div class="flex justify-center items-center gap-2">
                                {{-- Tombol Invoice (View Customer) --}}
                                <a href="{{ route('ppob.invoice', ['invoice' => $trx->order_id]) }}" 
                                   target="_blank"
                                   class="group p-2 bg-blue-50 text-blue-600 rounded-lg hover:bg-blue-600 hover:text-white transition shadow-sm" 
                                   title="Lihat Invoice">
                                    <i class="fas fa-eye"></i>
                                </a>
                                
                                {{-- Tombol Refresh Status (Opsional, jika ada fitur cek status manual) --}}
                                {{-- <button type="button" class="p-2 bg-gray-50 text-gray-600 rounded-lg hover:bg-gray-600 hover:text-white transition shadow-sm" title="Cek Status Provider">
                                    <i class="fas fa-sync-alt"></i>
                                </button> --}}
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-16 text-center bg-gray-50/50">
                            <div class="flex flex-col items-center justify-center">
                                <div class="bg-white p-4 rounded-full shadow-sm mb-3">
                                    <i class="fas fa-search text-3xl text-gray-300"></i>
                                </div>
                                <h3 class="text-lg font-medium text-gray-900">Data Tidak Ditemukan</h3>
                                <p class="text-gray-500 text-sm mt-1">Coba ubah filter pencarian atau tanggal.</p>
                                <a href="{{ route('admin.ppob.data.index') }}" class="mt-4 text-blue-600 hover:underline text-sm font-medium">Reset Filter</a>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        {{-- Pagination --}}
        <div class="bg-white px-6 py-4 border-t border-gray-200">
            {{ $transactions->withQueryString()->links() }}
        </div>
    </div>
</div>
@endsection