@extends('layouts.admin')

@section('title', 'Data Transaksi PPOB')

@section('content')
<div class="space-y-6">
    
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Riwayat Transaksi PPOB</h2>
            <p class="text-sm text-gray-500 mt-1">Monitoring transaksi digital (Pulsa, Data, PLN, dll).</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('admin.ppob.data.export.excel') }}" class="px-4 py-2 bg-green-600 text-white text-xs font-bold rounded hover:bg-green-700 transition">
                <i class="fas fa-file-excel mr-1"></i> EXCEL
            </a>
            <a href="{{ route('admin.ppob.data.export.pdf') }}" class="px-4 py-2 bg-red-600 text-white text-xs font-bold rounded hover:bg-red-700 transition">
                <i class="fas fa-file-pdf mr-1"></i> PDF
            </a>
        </div>
    </div>

    {{-- Filter Section --}}
    <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100">
        <form action="{{ route('admin.ppob.data.index') }}" method="GET">
            <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end">
                {{-- Search --}}
                <div class="md:col-span-4">
                    <label class="text-xs font-medium text-gray-700 block mb-1">Pencarian</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400"><i class="fas fa-search"></i></span>
                        <input type="text" name="search" value="{{ request('search') }}" class="w-full pl-10 pr-4 py-2 border rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500" placeholder="Order ID, User, atau No HP...">
                    </div>
                </div>
                {{-- Status --}}
                <div class="md:col-span-3">
                    <label class="text-xs font-medium text-gray-700 block mb-1">Status</label>
                    <select name="status" class="w-full px-3 py-2 border rounded-lg text-sm">
                        <option value="">Semua Status</option>
                        <option value="Success" {{ request('status') == 'Success' ? 'selected' : '' }}>Success</option>
                        <option value="Pending" {{ request('status') == 'Pending' ? 'selected' : '' }}>Pending</option>
                        <option value="Processing" {{ request('status') == 'Processing' ? 'selected' : '' }}>Processing</option>
                        <option value="Failed" {{ request('status') == 'Failed' ? 'selected' : '' }}>Failed</option>
                    </select>
                </div>
                {{-- Tanggal --}}
                <div class="md:col-span-3">
                    <label class="text-xs font-medium text-gray-700 block mb-1">Tanggal</label>
                    <div class="flex gap-1">
                        <input type="date" name="start_date" value="{{ request('start_date') }}" class="w-1/2 px-2 py-2 border rounded-lg text-xs">
                        <input type="date" name="end_date" value="{{ request('end_date') }}" class="w-1/2 px-2 py-2 border rounded-lg text-xs">
                    </div>
                </div>
                {{-- Tombol --}}
                <div class="md:col-span-2">
                    <button type="submit" class="w-full bg-blue-600 text-white font-bold py-2 rounded-lg text-sm hover:bg-blue-700 transition">Filter</button>
                </div>
            </div>
        </form>
    </div>

    {{-- Table Section --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50 border-b text-xs uppercase text-gray-500 font-semibold">
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
                    <tr class="hover:bg-gray-50 transition">
                        
                        {{-- 1. USER (Foto Profil dari store_logo_path) --}}
                        <td class="px-6 py-4 align-top">
                            <div class="flex items-center gap-3">
                                @php
                                    // Cek apakah user punya logo toko/profil
                                    $userImage = $trx->user->store_logo_path 
                                        ? asset('public/storage/' . $trx->user->store_logo_path) 
                                        : 'https://ui-avatars.com/api/?name='.urlencode($trx->user->name ?? 'User').'&background=random';
                                @endphp
                                <img src="{{ $userImage }}" alt="User" class="h-10 w-10 rounded-full object-cover border border-gray-200">
                                <div>
                                    <div class="text-xs font-mono text-gray-400 mb-0.5">#{{ $trx->order_id }}</div>
                                    <div class="text-sm font-bold text-gray-900">{{ $trx->user->nama_lengkap ?? ($trx->user->name ?? 'User Hapus') }}</div>
                                    <div class="text-xs text-gray-500">{{ $trx->user->email ?? '-' }}</div>
                                </div>
                            </div>
                        </td>

                        {{-- 2. PRODUK (Logo Operator Pakai Helper) --}}
                        <td class="px-6 py-4 align-top">
                            <div class="flex items-center">
                                @php
                                    // Ambil brand dari kolom 'brand', jika kosong coba tebak dari SKU
                                    $brandName = $trx->brand ?? explode(' ', $trx->product_name ?? 'unknown')[0];
                                    $logoOperator = get_operator_logo($brandName); 
                                @endphp
                                <div class="h-9 w-9 flex-shrink-0 mr-3 bg-white border border-gray-200 rounded-full p-1 flex items-center justify-center">
                                    <img class="h-full w-full object-contain" src="{{ $logoOperator }}" alt="{{ $brandName }}">
                                </div>
                                <div>
                                    <div class="text-sm font-medium text-gray-900 uppercase">{{ $trx->buyer_sku_code }}</div>
                                    <div class="text-[10px] text-gray-400">{{ Str::limit($trx->product_name, 20) }}</div>
                                </div>
                            </div>
                        </td>

                        {{-- 3. TUJUAN --}}
                        <td class="px-6 py-4 align-top">
                            <div class="bg-gray-50 px-2 py-1 rounded border border-gray-200 inline-block font-mono font-bold text-gray-700 text-sm">
                                {{ $trx->customer_no }}
                            </div>
                        </td>

                        {{-- 4. HARGA & PROFIT --}}
                        <td class="px-6 py-4 align-top">
                            <div class="text-sm font-bold text-gray-900">Rp {{ number_format($trx->selling_price, 0, ',', '.') }}</div>
                            <div class="text-xs font-medium {{ $trx->profit > 0 ? 'text-green-600' : 'text-gray-400' }}">
                                + Rp {{ number_format($trx->profit, 0, ',', '.') }}
                            </div>
                            <div class="text-[10px] uppercase text-gray-400 mt-0.5">{{ $trx->payment_method ?? 'SALDO' }}</div>
                        </td>

                        {{-- 5. STATUS --}}
                        <td class="px-6 py-4 align-top">
                            @php
                                $badgeClass = match($trx->status) {
                                    'Success' => 'bg-green-100 text-green-700 border-green-200',
                                    'Pending' => 'bg-yellow-100 text-yellow-700 border-yellow-200',
                                    'Processing' => 'bg-blue-100 text-blue-700 border-blue-200',
                                    'Failed' => 'bg-red-100 text-red-700 border-red-200',
                                    default => 'bg-gray-100 text-gray-700'
                                };
                                $icon = match($trx->status) {
                                    'Success' => 'fa-check-circle',
                                    'Failed' => 'fa-times-circle',
                                    'Processing' => 'fa-spinner fa-spin',
                                    default => 'fa-clock'
                                };
                            @endphp
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium border {{ $badgeClass }} mb-1">
                                <i class="fas {{ $icon }} mr-1.5"></i> {{ $trx->status }}
                            </span>
                            @if($trx->sn)
                                <div class="mt-1">
                                    <code class="text-[10px] bg-gray-50 px-2 py-1 border rounded block w-full max-w-[140px] truncate select-all" title="{{ $trx->sn }}">
                                        SN: {{ $trx->sn }}
                                    </code>
                                </div>
                            @elseif($trx->status == 'Failed')
                                <div class="mt-1 text-[10px] text-red-500 italic leading-tight max-w-[140px]">
                                    {{ Str::limit($trx->message, 50) }}
                                </div>
                            @endif
                        </td>

                        {{-- 6. WAKTU --}}
                        <td class="px-6 py-4 align-top text-right whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900">{{ $trx->created_at->format('d M Y') }}</div>
                            <div class="text-xs text-gray-500">{{ $trx->created_at->format('H:i:s') }}</div>
                        </td>

                        {{-- 7. AKSI --}}
                        <td class="px-6 py-4 align-top text-center">
                            <a href="{{ route('ppob.invoice', ['invoice' => $trx->order_id]) }}" target="_blank" class="p-2 bg-blue-50 text-blue-600 rounded hover:bg-blue-600 hover:text-white transition">
                                <i class="fas fa-eye"></i>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center text-gray-500 bg-gray-50">
                            <div class="flex flex-col items-center">
                                <i class="fas fa-search text-3xl mb-2 text-gray-300"></i>
                                <p>Tidak ada data transaksi ditemukan.</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        {{-- Pagination --}}
        <div class="bg-white px-6 py-4 border-t">
            {{ $transactions->withQueryString()->links() }}
        </div>
    </div>
</div>
@endsection