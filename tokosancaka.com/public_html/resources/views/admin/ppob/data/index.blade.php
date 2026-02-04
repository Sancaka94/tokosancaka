@extends('layouts.admin')

@section('title', 'Data Transaksi PPOB')

@section('content')
<div class="space-y-6">
    
    {{-- =================================================================== --}}
    {{-- HEADER & WIDGET SALDO --}}
    {{-- =================================================================== --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 items-start">
        
        {{-- Judul Halaman --}}
        <div class="md:col-span-2">
            <h2 class="text-2xl font-bold text-gray-800">Riwayat Transaksi PPOB</h2>
            <p class="text-sm text-gray-500 mt-1">Monitoring transaksi digital, pulsa, dan pembayaran tagihan secara realtime.</p>
            
            {{-- Tombol Export --}}
            <div class="flex gap-2 mt-4">
                <a href="{{ route('admin.ppob.export.excel', request()->all()) }}" class="inline-flex items-center px-4 py-2 bg-green-600 text-white text-xs font-bold rounded-lg hover:bg-green-700 transition shadow-sm hover:shadow-md">
                    <i class="fas fa-file-excel mr-2"></i> EXCEL
                </a>
                <a href="{{ route('admin.ppob.export.pdf', request()->all()) }}" class="inline-flex items-center px-4 py-2 bg-red-600 text-white text-xs font-bold rounded-lg hover:bg-red-700 transition shadow-sm hover:shadow-md">
                    <i class="fas fa-file-pdf mr-2"></i> PDF
                </a>
            </div>
        </div>

        {{-- Widget Saldo (Fixed ID) --}}
        <div class="bg-gradient-to-br from-blue-600 to-blue-700 rounded-xl p-5 text-white shadow-lg relative overflow-hidden">
            <div class="absolute right-0 top-0 opacity-10 transform translate-x-4 -translate-y-4">
                <i class="fas fa-wallet text-8xl"></i>
            </div>
            <div class="relative z-10">
                <p class="text-blue-100 text-xs font-medium uppercase tracking-wider mb-1">Sisa Deposit Digiflazz</p>
                
                {{-- Angka Saldo --}}
                <div class="flex items-center justify-between mb-4">
                    <h3 id="saldo-display" class="text-3xl font-bold tracking-tight">Rp ...</h3>
                    
                    {{-- Tombol Refresh Kecil --}}
                    <button onclick="fetchSaldo()" id="btn-refresh-saldo" class="text-blue-200 hover:text-white transition p-1.5 rounded-full hover:bg-white/10" title="Refresh Saldo">
                        <i class="fas fa-sync-alt" id="icon-refresh"></i>
                    </button>
                </div>
                
                {{-- Indikator Loading --}}
                <p id="saldo-loading" class="text-[10px] text-blue-200 mb-2 hidden">Sedang memuat data...</p>

                <div class="grid grid-cols-2 gap-2 mt-3">
                    {{-- Tombol Deposit --}}
                    <button onclick="openDepositModal()" class="bg-white/20 hover:bg-white/30 text-white font-semibold py-2 px-4 rounded-lg text-sm transition border border-white/10">
                        <i class="fas fa-plus-circle"></i> Deposit
                    </button>
                    
                    {{-- TOMBOL BARU: TOPUP MANUAL --}}
                    <button onclick="openTopupModal()" class="bg-white text-blue-600 font-bold py-2 px-4 rounded-lg text-sm transition hover:bg-gray-100 shadow-sm">
                        <i class="fas fa-bolt"></i> Transaksi
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- =================================================================== --}}
    {{-- FILTER SECTION --}}
    {{-- =================================================================== --}}
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

    {{-- =================================================================== --}}
    {{-- TABLE SECTION --}}
    {{-- =================================================================== --}}
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
                                    $logoUrl = asset('public/storage/logo-ppob/' . $brandName . '.png');
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
<form action="{{ route('admin.ppob.transaction.destroy', $trx->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus data ini?');">
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

{{-- MODAL TOPUP MANUAL --}}
<div id="topupModal" class="fixed inset-0 z-[999] hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="fixed inset-0 bg-gray-900 bg-opacity-75 transition-opacity backdrop-blur-sm" onclick="closeTopupModal()"></div>
    <div class="flex items-center justify-center min-h-screen p-4 text-center sm:p-0">
        <div class="relative bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:max-w-md w-full">
            
            <div class="bg-gradient-to-r from-gray-800 to-gray-900 px-6 py-4 flex justify-between items-center">
                <h3 class="text-lg font-bold text-white"><i class="fas fa-bolt text-yellow-400 mr-2"></i> Transaksi Manual</h3>
                <button onclick="closeTopupModal()" class="text-gray-400 hover:text-white"><i class="fas fa-times"></i></button>
            </div>

            <div class="p-6">
                <form id="formTopup" onsubmit="submitTopup(event)">
                    @csrf
                    
                    {{-- Kode Produk --}}
                    <div class="mb-4">
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Kode Produk (SKU)</label>
                        <input type="text" name="buyer_sku_code" class="w-full border-gray-300 rounded-lg shadow-sm focus:border-gray-500 focus:ring-gray-500 py-2.5 uppercase" placeholder="Contoh: xld10, pulsa5" required>
                        <p class="text-[10px] text-gray-500 mt-1">Pastikan kode produk benar sesuai Pricelist.</p>
                    </div>

                    {{-- Nomor Tujuan --}}
                    <div class="mb-6">
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Nomor Tujuan / ID Pelanggan</label>
                        <input type="text" name="customer_no" class="w-full border-gray-300 rounded-lg shadow-sm focus:border-gray-500 focus:ring-gray-500 py-2.5" placeholder="08xxxx" required>
                    </div>

                    <button type="submit" id="btn-submit-topup" class="w-full bg-gray-800 hover:bg-gray-900 text-white font-bold py-3 rounded-xl shadow transition">
                        Kirim Transaksi
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

{{-- ======================================================================= --}}
{{-- MODAL DEPOSIT (POP UP) --}}
{{-- ======================================================================= --}}
<div id="depositModal" class="fixed inset-0 z-[999] hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="fixed inset-0 bg-gray-900 bg-opacity-75 transition-opacity backdrop-blur-sm" onclick="closeDepositModal()"></div>

    <div class="flex items-center justify-center min-h-screen p-4 text-center sm:p-0">
        <div class="relative bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:max-w-md w-full animate-fade-in-up">
            
            {{-- Header Modal --}}
            <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-6 py-4 flex justify-between items-center">
                <h3 class="text-lg font-bold text-white flex items-center gap-2">
                    <i class="fas fa-wallet"></i> Request Deposit
                </h3>
                <button onclick="closeDepositModal()" class="text-blue-100 hover:text-white transition">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>

            <div class="p-6">
                
                {{-- FORM INPUT (Tampil Awal) --}}
                <form id="formDeposit" onsubmit="submitDeposit(event)">
                    @csrf
                    
                    <div class="bg-blue-50 border-l-4 border-blue-500 p-3 mb-4 rounded-r text-xs text-blue-700">
                        <p>Tiket deposit berlaku hingga pukul 21:00 WIB. Transfer harus <b>PERSIS</b> sesuai nominal tiket.</p>
                    </div>

                    {{-- Input Bank --}}
                    <div class="mb-4">
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Metode Pembayaran</label>
                        <div class="relative">
                            <select name="bank" id="depo_bank" class="w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-blue-500 py-2.5 pl-3 pr-10 appearance-none bg-white">
                                <optgroup label="Transfer Bank (Cek Otomatis)">
                                    <option value="BCA">BCA</option>
                                    <option value="MANDIRI">MANDIRI</option>
                                    <option value="BRI">BRI</option>
                                    <option value="BNI">BNI</option>
                                </optgroup>
                                <optgroup label="E-Wallet & Lainnya">
                                    <option value="FLIP">FLIP (Bebas Admin)</option>
                                    <option value="SHOPEEPAY">SHOPEEPAY</option>
                                    <option value="GOPAY">GOPAY</option>
                                    <option value="DANA">DANA</option>
                                    <option value="OVO">OVO</option>
                                </optgroup>
                            </select>
                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-700">
                                <i class="fas fa-chevron-down text-xs"></i>
                            </div>
                        </div>
                    </div>

                    {{-- Input Nama Pemilik --}}
                    <div class="mb-4">
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Nama Pemilik Rekening</label>
                        <input type="text" name="owner_name" class="w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-blue-500 py-2.5" placeholder="Contoh: Sancaka Jaya" required>
                    </div>

                    {{-- Input Nominal --}}
                    <div class="mb-6">
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Nominal Deposit</label>
                        <div class="relative rounded-md shadow-sm">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <span class="text-gray-500 sm:text-sm font-bold">Rp</span>
                            </div>
                            <input type="number" name="amount" class="focus:ring-blue-500 focus:border-blue-500 block w-full pl-10 sm:text-lg font-bold border-gray-300 rounded-lg py-3" placeholder="0" min="200000" required>
                        </div>
                        <p class="text-xs text-red-500 mt-1 font-medium">*Minimal Rp 200.000</p>
                    </div>

                    <button type="submit" id="btn-submit-depo" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 rounded-xl shadow transition transform hover:scale-[1.02]">
                        Buat Tiket Deposit
                    </button>
                </form>

                {{-- TAMPILAN HASIL TIKET (Hidden by Default) --}}
                <div id="depositResult" class="hidden">
                    <div class="text-center mb-6">
                        <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-green-100 mb-4">
                            <i class="fas fa-check text-3xl text-green-600"></i>
                        </div>
                        <h3 class="text-lg leading-6 font-bold text-gray-900">Tiket Berhasil Dibuat!</h3>
                        <p class="text-sm text-gray-500 mt-1">Silakan transfer ke rekening berikut:</p>
                    </div>

                    <div class="bg-gray-50 rounded-xl border border-gray-200 p-4 space-y-4">
                        
                        {{-- Nominal --}}
                        <div>
                            <p class="text-xs text-gray-500 uppercase font-semibold mb-1">Total Transfer (Harus Persis)</p>
                            <div class="flex justify-between items-center bg-white p-3 rounded border border-blue-200">
                                <span class="text-2xl font-bold text-blue-600" id="res_amount">-</span>
                                <button onclick="copyToClipboard('res_amount')" class="text-gray-400 hover:text-blue-600 transition" title="Salin">
                                    <i class="far fa-copy text-lg"></i>
                                </button>
                            </div>
                            <p class="text-[10px] text-red-500 mt-1 animate-pulse font-bold">Jangan dibulatkan! Transfer hingga 3 digit terakhir.</p>
                        </div>

                        {{-- Bank --}}
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <p class="text-xs text-gray-500 uppercase font-semibold mb-1">Bank</p>
                                <p class="font-bold text-gray-800 text-lg" id="res_bank">-</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 uppercase font-semibold mb-1">No. Rekening</p>
                                <div class="flex items-center gap-2">
                                    <p class="font-bold text-gray-800 text-lg" id="res_rek">-</p>
                                    <button onclick="copyToClipboard('res_rek')" class="text-gray-400 hover:text-blue-600"><i class="far fa-copy"></i></button>
                                </div>
                            </div>
                        </div>

                        {{-- Notes --}}
                        <div class="bg-yellow-50 p-3 rounded border border-yellow-200">
                            <p class="text-[10px] text-yellow-700 uppercase font-bold mb-1">Berita Transfer (PENTING)</p>
                            <div class="flex justify-between items-center">
                                <p class="font-mono font-bold text-gray-800" id="res_notes">-</p>
                                <button onclick="copyToClipboard('res_notes')" class="text-gray-400 hover:text-blue-600"><i class="far fa-copy"></i></button>
                            </div>
                        </div>
                    </div>

                    <button onclick="closeDepositModal()" class="mt-6 w-full bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-3 rounded-xl transition">
                        Tutup & Cek Saldo Nanti
                    </button>
                </div>

            </div>
        </div>
    </div>
</div>



@push('scripts')
<script>
    // --- 1. LOGIC SALDO (AJAX) ---
    function fetchSaldo() {
        // Ambil elemen dengan ID yang BENAR
        const display = document.getElementById('saldo-display');
        const loading = document.getElementById('saldo-loading');
        const icon = document.getElementById('icon-refresh');
        
        // Cek jika elemen ada (mencegah error 'null')
        if(!display) return;

        // UI Loading State
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
                    console.error(data.message);
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

    // --- 2. LOGIC MODAL DEPOSIT ---
    const depositModal = document.getElementById('depositModal');
    const formDeposit = document.getElementById('formDeposit');
    const resultDeposit = document.getElementById('depositResult');

    function openDepositModal() {
        formDeposit.classList.remove('hidden');
        formDeposit.reset();
        resultDeposit.classList.add('hidden');
        depositModal.classList.remove('hidden');
    }

    function closeDepositModal() {
        depositModal.classList.add('hidden');
        fetchSaldo(); // Refresh saldo saat tutup modal
    }

    function submitDeposit(e) {
        e.preventDefault();
        
        const btn = document.getElementById('btn-submit-depo');
        const originalText = btn.innerHTML;
        
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Menghubungi Digiflazz...';

        const formData = new FormData(formDeposit);

        fetch("{{ route('admin.ppob.deposit') }}", {
            method: "POST",
            body: formData,
            headers: {
                "X-CSRF-TOKEN": "{{ csrf_token() }}",
                "Accept": "application/json"
            }
        })
        .then(res => res.json())
        .then(resp => {
            btn.disabled = false;
            btn.innerHTML = originalText;

            if(resp.status === 'success') {
                formDeposit.classList.add('hidden');
                resultDeposit.classList.remove('hidden');

                const data = resp.data;
                const formattedAmount = 'Rp ' + parseInt(data.amount).toLocaleString('id-ID');
                
                document.getElementById('res_amount').innerText = formattedAmount;
                document.getElementById('res_bank').innerText = data.bank;
                document.getElementById('res_rek').innerText = data.account_no;
                document.getElementById('res_notes').innerText = data.notes;
                
            } else {
                alert('Gagal Request: ' + resp.message);
            }
        })
        .catch(err => {
            btn.disabled = false;
            btn.innerHTML = originalText;
            console.error(err);
            alert('Terjadi kesalahan koneksi ke server.');
        });
    }

    // --- 3. HELPER COPY TEXT ---
    function copyToClipboard(elementId) {
        const text = document.getElementById(elementId).innerText.replace(/[^0-9a-zA-Z ]/g, "").replace('Rp', '').trim();
        navigator.clipboard.writeText(text).then(() => {
            alert('Teks berhasil disalin!');
        });
    }

    // --- 4. AUTO RUN ---
    document.addEventListener("DOMContentLoaded", function() {
        fetchSaldo();
    });

    // --- LOGIC TOPUP MANUAL ---
    const topupModal = document.getElementById('topupModal');
    const formTopup = document.getElementById('formTopup');

    function openTopupModal() {
        topupModal.classList.remove('hidden');
        formTopup.reset();
    }

    function closeTopupModal() {
        topupModal.classList.add('hidden');
    }

    function submitTopup(e) {
        e.preventDefault();
        
        const btn = document.getElementById('btn-submit-topup');
        const originalText = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Memproses...';

        const formData = new FormData(formTopup);

        fetch("{{ route('admin.ppob.topup') }}", {
            method: "POST",
            body: formData,
            headers: {
                "X-CSRF-TOKEN": "{{ csrf_token() }}",
                "Accept": "application/json"
            }
        })
        .then(res => res.json())
        .then(resp => {
            btn.disabled = false;
            btn.innerHTML = originalText;

            if(resp.status === 'success') {
                const d = resp.data;
                // Tampilkan Alert Sukses/Pending
                let msg = `Status: ${d.status}\nSN: ${d.sn}\nPesan: ${d.message}`;
                alert(msg);
                
                // Refresh halaman untuk melihat data di tabel (jika sudah disimpan ke DB)
                // location.reload(); 
                closeTopupModal();
                fetchSaldo(); // Update saldo otomatis
            } else {
                alert('Gagal: ' + resp.message);
            }
        })
        .catch(err => {
            btn.disabled = false;
            btn.innerHTML = originalText;
            console.error(err);
            alert('Terjadi kesalahan koneksi.');
        });
    }
</script>
@endpush

@endsection