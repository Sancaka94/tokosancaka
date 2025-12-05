@extends('layouts.admin')

@section('title', 'Data Transaksi PPOB')

@section('content')
<div class="space-y-6">
    
    {{-- Header Section --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Riwayat Transaksi PPOB</h2>
            <p class="text-sm text-gray-500 mt-1">Monitoring transaksi digital, pulsa, dan pembayaran tagihan.</p>
        </div>
        
        {{-- Tombol Export --}}
        <div class="flex gap-2">
            <a href="{{ route('admin.ppob.data.export.excel') }}" class="px-4 py-2 bg-green-600 text-white text-xs font-bold rounded hover:bg-green-700 transition shadow-sm">
                <i class="fas fa-file-excel mr-1"></i> EXCEL
            </a>
            <a href="{{ route('admin.ppob.data.export.pdf') }}" class="px-4 py-2 bg-red-600 text-white text-xs font-bold rounded hover:bg-red-700 transition shadow-sm">
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
                        <input type="text" name="search" value="{{ request('search') }}" class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500" placeholder="Order ID, User, atau No HP...">
                    </div>
                </div>

                {{-- Status Filter --}}
                <div class="md:col-span-3">
                    <label class="text-xs font-medium text-gray-700 block mb-1">Status</label>
                    <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Semua Status</option>
                        <option value="Success" {{ request('status') == 'Success' ? 'selected' : '' }}>Success (Berhasil)</option>
                        <option value="Pending" {{ request('status') == 'Pending' ? 'selected' : '' }}>Pending (Menunggu)</option>
                        <option value="Processing" {{ request('status') == 'Processing' ? 'selected' : '' }}>Processing (Diproses)</option>
                        <option value="Failed" {{ request('status') == 'Failed' ? 'selected' : '' }}>Failed (Gagal)</option>
                    </select>
                </div>

                {{-- Date Filter --}}
                <div class="md:col-span-3">
                    <label class="text-xs font-medium text-gray-700 block mb-1">Tanggal</label>
                    <div class="flex gap-1">
                        <input type="date" name="start_date" value="{{ request('start_date') }}" class="w-1/2 px-2 py-2 border border-gray-300 rounded-lg text-xs focus:ring-blue-500 focus:border-blue-500">
                        <input type="date" name="end_date" value="{{ request('end_date') }}" class="w-1/2 px-2 py-2 border border-gray-300 rounded-lg text-xs focus:ring-blue-500 focus:border-blue-500">
                    </div>
                </div>

                {{-- Button --}}
                <div class="md:col-span-2">
                    <button type="submit" class="w-full bg-blue-600 text-white font-bold py-2 rounded-lg text-sm hover:bg-blue-700 transition shadow-sm">
                        <i class="fas fa-filter mr-1"></i> Filter
                    </button>
                </div>
            </div>
        </form>
    </div>

    {{-- Table Section --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-200 text-xs uppercase text-gray-500 font-semibold">
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
                        
                        {{-- 1. USER INFO --}}
                        <td class="px-6 py-4 align-top">
                            <div class="flex items-center gap-3">
                                @php
                                    $userImage = !empty($trx->user->store_logo_path) 
                                            ? asset('public/storage/' . $trx->user->store_logo_path) 
                                            : 'https://ui-avatars.com/api/?name='.urlencode($trx->user->name ?? 'User').'&background=random&color=fff';
                                @endphp
                                <img src="{{ $userImage }}" alt="User" class="h-10 w-10 rounded-full object-cover border border-gray-200 shadow-sm">
                                <div>
                                    <div class="text-[10px] font-mono text-gray-400 mb-0.5">#{{ $trx->order_id }}</div>
                                    <div class="text-sm font-bold text-gray-900">{{ $trx->user->nama_lengkap ?? ($trx->user->name ?? 'User Terhapus') }}</div>
                                    <div class="text-xs text-gray-500">{{ $trx->user->email ?? '-' }}</div>
                                </div>
                            </div>
                        </td>

                        {{-- 2. PRODUK INFO --}}
                        <td class="px-6 py-4 align-top">
                            <div class="flex items-center">
                                @php
                                    // LOGIKA LOGO MANUAL (Pengganti Helper get_operator_logo)
                                    $brandName = strtolower($trx->brand ?? 'other');
                                    // Pastikan file gambar ada di public/storage/logo-ppob/
                                    // Nama file biasanya: telkomsel.png, indosat.png, pln.png, dll
                                    $logoOperator = asset('storage/logo-ppob/' . $brandName . '.png');
                                @endphp
                                <div class="h-9 w-9 flex-shrink-0 mr-3 bg-white border border-gray-200 rounded-full p-1 flex items-center justify-center shadow-sm" title="{{ $trx->product_name }}">
                                    <img class="h-full w-full object-contain" src="{{ $logoOperator }}" onerror="this.src='https://via.placeholder.com/40?text={{ substr($brandName, 0, 1) }}'" alt="{{ $brandName }}">
                                </div>
                                <div>
                                    <div class="text-sm font-medium text-gray-900 uppercase">{{ $trx->buyer_sku_code }}</div>
                                    <div class="text-[10px] text-gray-400 truncate max-w-[120px]" title="{{ $trx->product_name }}">{{ $trx->product_name }}</div>
                                </div>
                            </div>
                        </td>

                        {{-- 3. NOMOR TUJUAN --}}
                        <td class="px-6 py-4 align-top">
                            <div class="bg-blue-50 px-2 py-1 rounded border border-blue-100 inline-block font-mono font-bold text-gray-700 text-sm">
                                {{ $trx->customer_no }}
                            </div>
                            <div class="text-[10px] text-gray-400 mt-1">No. Pelanggan</div>
                        </td>

                        {{-- 4. HARGA & PROFIT --}}
                        <td class="px-6 py-4 align-top">
                            <div class="text-sm font-bold text-gray-900">Rp {{ number_format($trx->selling_price, 0, ',', '.') }}</div>
                            <div class="text-xs font-medium mt-0.5 {{ $trx->profit > 0 ? 'text-green-600' : 'text-gray-400' }}">
                                + Rp {{ number_format($trx->profit, 0, ',', '.') }}
                            </div>
                            <div class="text-[10px] uppercase text-gray-400 mt-0.5">{{ $trx->payment_method ?? 'SALDO' }}</div>
                        </td>

                        {{-- 5. STATUS & SN --}}
                        <td class="px-6 py-4 align-top">
                            @php
                                $badgeClass = match($trx->status) {
                                    'Success' => 'bg-green-100 text-green-700 border-green-200',
                                    'Pending' => 'bg-yellow-100 text-yellow-700 border-yellow-200',
                                    'Processing' => 'bg-blue-100 text-blue-700 border-blue-200',
                                    'Failed' => 'bg-red-100 text-red-700 border-red-200',
                                    default => 'bg-gray-100 text-gray-700 border-gray-200'
                                };
                                $icon = match($trx->status) {
                                    'Success' => 'fa-check-circle',
                                    'Failed' => 'fa-times-circle',
                                    'Processing' => 'fa-spinner fa-spin',
                                    default => 'fa-clock'
                                };

                                // LOGIKA PESAN DIGIFLAZZ (Pengganti Helper get_ppob_message)
                                $rcMessages = [
                                    '00' => 'Transaksi Sukses',
                                    '03' => 'Transaksi Pending',
                                    '40' => 'Payload Error',
                                    '41' => 'Signature Invalid',
                                    '42' => 'Akun Provider Salah',
                                    '43' => 'Produk Non-Aktif/Gangguan',
                                    '44' => 'Saldo Server Kurang',
                                    '50' => 'Transaksi Tidak Ditemukan',
                                    '51' => 'Nomor Diblokir',
                                    '52' => 'Prefix Salah Operator',
                                    '53' => 'Produk Ditutup',
                                    '54' => 'Nomor Tujuan Salah',
                                    '55' => 'Produk Gangguan',
                                    '99' => 'Router Issue (Pending)',
                                ];
                                $statusMessage = isset($trx->rc) && isset($rcMessages[$trx->rc]) 
                                                ? $rcMessages[$trx->rc] 
                                                : ($trx->message ?? 'Transaksi Gagal');
                            @endphp
                            
                            {{-- Badge Status --}}
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium border {{ $badgeClass }} mb-1">
                                <i class="fas {{ $icon }} mr-1.5"></i> {{ $trx->status }}
                            </span>

                            {{-- Logic Tampilan SN atau Pesan Error --}}
                            @if($trx->status == 'Success' && $trx->sn)
                                <div class="mt-1">
                                    <code class="text-[10px] bg-gray-50 px-2 py-1 border rounded block w-full max-w-[150px] truncate select-all text-gray-600 font-mono" title="{{ $trx->sn }}">
                                        SN: {{ $trx->sn }}
                                    </code>
                                </div>
                            @elseif($trx->status == 'Failed')
                                <div class="mt-1 text-[11px] text-red-500 italic leading-tight max-w-[160px]">
                                    {{ $statusMessage }}
                                </div>
                            @elseif($trx->status == 'Processing')
                                <div class="mt-1 text-[10px] text-blue-500 animate-pulse">
                                    Menunggu respon provider...
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
                            <div class="flex justify-center">
                                <a href="{{ route('ppob.invoice', ['invoice' => $trx->order_id]) }}" 
                                   target="_blank" 
                                   class="group p-2 bg-white border border-gray-200 text-gray-500 rounded-lg hover:bg-blue-600 hover:text-white hover:border-blue-600 transition shadow-sm"
                                   title="Lihat Invoice">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-16 text-center bg-gray-50/50">
                            <div class="flex flex-col items-center justify-center">
                                <div class="bg-white p-4 rounded-full shadow-sm mb-3">
                                    <i class="fas fa-receipt text-3xl text-gray-300"></i>
                                </div>
                                <h3 class="text-lg font-medium text-gray-900">Data Tidak Ditemukan</h3>
                                <p class="text-gray-500 text-sm mt-1">Belum ada transaksi yang sesuai dengan filter Anda.</p>
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