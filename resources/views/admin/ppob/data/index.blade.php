@extends('layouts.admin')

@section('title', 'Data Transaksi PPOB')

@section('content')
<div class="space-y-6">
    
    {{-- Header & Widget Saldo --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 items-start">
        {{-- Judul Halaman --}}
        <div class="md:col-span-2">
            <h2 class="text-2xl font-bold text-gray-800">Riwayat Transaksi PPOB</h2>
            <p class="text-sm text-gray-500 mt-1">Monitoring transaksi digital, pulsa, dan pembayaran tagihan secara realtime.</p>
            
            {{-- Tombol Export --}}
            <div class="flex gap-2 mt-4">
                <a href="{{ route('ppob.export.excel', request()->all()) }}" class="inline-flex items-center px-4 py-2 bg-green-600 text-white text-xs font-bold rounded-lg hover:bg-green-700 transition shadow-sm hover:shadow-md">
                    <i class="fas fa-file-excel mr-2"></i> EXCEL
                </a>
                <a href="{{ route('ppob.export.pdf', request()->all()) }}" class="inline-flex items-center px-4 py-2 bg-red-600 text-white text-xs font-bold rounded-lg hover:bg-red-700 transition shadow-sm hover:shadow-md">
                    <i class="fas fa-file-pdf mr-2"></i> PDF
                </a>
            </div>
        </div>

        {{-- Widget Saldo (AJAX) --}}
        <div class="bg-gradient-to-br from-blue-600 to-blue-700 rounded-xl p-5 text-white shadow-lg relative overflow-hidden">
            <div class="absolute right-0 top-0 opacity-10 transform translate-x-4 -translate-y-4">
                <i class="fas fa-wallet text-8xl"></i>
            </div>
            <div class="relative z-10">
                <p class="text-blue-100 text-xs font-medium uppercase tracking-wider mb-1">Sisa Deposit Digiflazz</p>
                <div class="flex items-center justify-between">
                    <h3 id="saldo-display" class="text-2xl font-bold">Rp ...</h3>
                    <button onclick="fetchSaldo()" id="btn-refresh-saldo" class="text-blue-200 hover:text-white transition p-1 rounded-full hover:bg-white/10" title="Refresh Saldo">
                        <i class="fas fa-sync-alt" id="icon-refresh"></i>
                    </button>
                </div>
                <p id="saldo-loading" class="text-[10px] text-blue-200 mt-1 hidden">Sedang memuat data...</p>
            </div>
        </div>
    </div>

    {{-- Filter Section --}}
    <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100">
        <form action="{{ route('admin.ppob.index') }}" method="GET">
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
                    <label class="text-xs font-medium text-gray-700 block mb-1">Tanggal Transaksi</label>
                    <div class="flex items-center gap-2">
                        <input type="date" name="start_date" value="{{ request('start_date') }}" class="w-full px-2 py-2 border border-gray-300 rounded-lg text-xs focus:ring-blue-500 focus:border-blue-500">
                        <span class="text-gray-400">-</span>
                        <input type="date" name="end_date" value="{{ request('end_date') }}" class="w-full px-2 py-2 border border-gray-300 rounded-lg text-xs focus:ring-blue-500 focus:border-blue-500">
                    </div>
                </div>

                {{-- Button --}}
                <div class="md:col-span-2">
                    <button type="submit" class="w-full bg-blue-600 text-white font-bold py-2 rounded-lg text-sm hover:bg-blue-700 transition shadow-sm flex items-center justify-center gap-2">
                        <i class="fas fa-filter"></i> Terapkan
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
                    <tr class="bg-gray-50 border-b border-gray-200 text-xs uppercase text-gray-500 font-semibold tracking-wider">
                        <th class="px-6 py-4">User / Pelanggan</th>
                        <th class="px-6 py-4">Produk</th>
                        <th class="px-6 py-4">Tujuan</th>
                        <th class="px-6 py-4">Nominal</th>
                        <th class="px-6 py-4">Status / Respon</th>
                        <th class="px-6 py-4 text-right">Waktu</th>
                        <th class="px-6 py-4 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($transactions as $trx)
                    <tr class="hover:bg-gray-50 transition duration-150 group">
                        
                        {{-- 1. USER INFO --}}
                        <td class="px-6 py-4 align-top">
                            <div class="flex items-start gap-3">
                                @php
                                    $userImage = !empty($trx->user->store_logo_path) 
                                                ? asset('public/storage/' . $trx->user->store_logo_path) 
                                                : 'https://ui-avatars.com/api/?name='.urlencode($trx->user->name ?? 'User').'&background=random&color=fff&size=64';
                                @endphp
                                <img src="{{ $userImage }}" alt="User" class="h-9 w-9 rounded-full object-cover border border-gray-200 shadow-sm mt-1">
                                <div>
                                    <div class="text-[10px] font-mono text-gray-400 mb-0.5">Order: #{{ $trx->order_id }}</div>
                                    <div class="text-sm font-bold text-gray-900 leading-tight">{{ $trx->user->nama_lengkap ?? ($trx->user->name ?? 'User Terhapus') }}</div>
                                    <div class="text-xs text-gray-500">{{ $trx->user->email ?? '-' }}</div>
                                </div>
                            </div>
                        </td>

                        {{-- 2. PRODUK INFO --}}
                        <td class="px-6 py-4 align-top">
                            <div class="flex items-start">
                                @php
                                    $brandName = strtolower($trx->brand ?? 'other');
                                    // Fallback icon fontawesome jika gambar tidak ada
                                    $defaultIcon = '<div class="h-8 w-8 bg-gray-100 rounded-full flex items-center justify-center text-gray-400"><i class="fas fa-box"></i></div>';
                                    $logoUrl = asset('storage/logo-ppob/' . $brandName . '.png');
                                @endphp
                                <div class="mr-3 shrink-0">
                                    <img class="h-8 w-8 object-contain" src="{{ $logoUrl }}" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';" alt="{{ $brandName }}">
                                    <div class="h-8 w-8 bg-gray-100 rounded-full flex items-center justify-center text-gray-400 hidden"><i class="fas fa-sim-card"></i></div>
                                </div>
                                <div>
                                    <div class="text-xs font-bold text-gray-900 uppercase tracking-wide">{{ $trx->brand }}</div>
                                    <div class="text-sm text-gray-600 line-clamp-1" title="{{ $trx->product_name }}">{{ $trx->product_name }}</div>
                                    <div class="text-[10px] text-gray-400 font-mono mt-0.5">{{ $trx->buyer_sku_code }}</div>
                                </div>
                            </div>
                        </td>

                        {{-- 3. NOMOR TUJUAN --}}
                        <td class="px-6 py-4 align-top">
                            <div class="bg-blue-50 px-2.5 py-1.5 rounded-lg border border-blue-100 inline-block">
                                <span class="font-mono font-bold text-gray-700 text-sm tracking-wide select-all">{{ $trx->customer_no }}</span>
                            </div>
                        </td>

                        {{-- 4. HARGA & PROFIT --}}
                        <td class="px-6 py-4 align-top">
                            <div class="text-sm font-bold text-gray-900">Rp {{ number_format($trx->selling_price, 0, ',', '.') }}</div>
                            <div class="flex items-center gap-1 mt-1">
                                <span class="text-[10px] text-gray-400">Profit:</span>
                                <span class="text-xs font-bold {{ $trx->profit > 0 ? 'text-green-600' : 'text-gray-400' }}">
                                    +Rp {{ number_format($trx->profit, 0, ',', '.') }}
                                </span>
                            </div>
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
                            @endphp
                            
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium border {{ $badgeClass }} mb-2">
                                <i class="fas {{ $icon }} mr-1.5"></i> {{ $trx->status }}
                            </span>

                            @if($trx->status == 'Success' && $trx->sn)
                                <div class="relative group/sn">
                                    <code class="text-[10px] bg-gray-50 px-2 py-1.5 border rounded block w-full max-w-[160px] truncate cursor-pointer hover:bg-gray-100 hover:text-blue-600 transition" onclick="navigator.clipboard.writeText('{{ $trx->sn }}'); alert('SN disalin!');" title="Klik untuk salin">
                                        SN: {{ $trx->sn }}
                                    </code>
                                </div>
                            @elseif($trx->status == 'Failed')
                                <div class="text-[11px] text-red-500 italic leading-tight max-w-[160px]">
                                    {{ $trx->note ?? ($trx->message ?? 'Transaksi Gagal') }}
                                </div>
                            @endif
                        </td>

                        {{-- 6. WAKTU --}}
                        <td class="px-6 py-4 align-top text-right whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900">{{ $trx->created_at->format('d M Y') }}</div>
                            <div class="text-xs text-gray-500">{{ $trx->created_at->format('H:i') }} WIB</div>
                        </td>

                        {{-- 7. AKSI --}}
                        <td class="px-6 py-4 align-top text-center">
                            <div class="flex justify-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity duration-200">
                                
                                {{-- Tombol Detail --}}
                                <a href="{{ route('admin.ppob.show', $trx->id) }}" 
                                   class="p-2 bg-white border border-gray-200 text-gray-500 rounded-lg hover:bg-blue-50 hover:text-blue-600 hover:border-blue-200 transition"
                                   title="Lihat Detail">
                                    <i class="fas fa-eye"></i>
                                </a>

                                {{-- Tombol Hapus (Hanya untuk failed/test) --}}
                                <form action="{{ route('admin.ppob.destroy', $trx->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus data ini?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="p-2 bg-white border border-gray-200 text-gray-500 rounded-lg hover:bg-red-50 hover:text-red-600 hover:border-red-200 transition" title="Hapus Transaksi">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-16 text-center bg-gray-50/50">
                            <div class="flex flex-col items-center justify-center">
                                <div class="bg-white p-4 rounded-full shadow-sm mb-3">
                                    <i class="fas fa-search-minus text-3xl text-gray-300"></i>
                                </div>
                                <h3 class="text-lg font-medium text-gray-900">Data Tidak Ditemukan</h3>
                                <p class="text-gray-500 text-sm mt-1">Belum ada transaksi yang sesuai dengan filter Anda.</p>
                                <a href="{{ route('admin.ppob.index') }}" class="mt-4 text-blue-600 hover:underline text-sm font-medium">Reset Filter</a>
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

{{-- SCRIPT AJAX SALDO --}}
@push('scripts')
<script>
    function fetchSaldo() {
        const display = document.getElementById('saldo-display');
        const loading = document.getElementById('saldo-loading');
        const icon = document.getElementById('icon-refresh');
        
        // UI Loading
        display.classList.add('opacity-50');
        loading.classList.remove('hidden');
        icon.classList.add('fa-spin');

        fetch("{{ route('admin.ppob.cek-saldo') }}")
            .then(response => response.json())
            .then(data => {
                if(data.status === 'success') {
                    display.innerText = data.formatted;
                } else {
                    display.innerText = "Error";
                    alert(data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                display.innerText = "Gagal";
            })
            .finally(() => {
                display.classList.remove('opacity-50');
                loading.classList.add('hidden');
                icon.classList.remove('fa-spin');
            });
    }

    // Auto load saldo saat halaman terbuka
    document.addEventListener("DOMContentLoaded", function() {
        fetchSaldo();
    });
</script>
@endpush

@endsection