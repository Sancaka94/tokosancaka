@extends('layouts.admin')

@section('title', 'Manajemen Produk PPOB')

{{-- Load SweetAlert2 CDN --}}
@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
@endpus

@section('content')

{{-- Notifikasi Flash Message (Session Laravel) --}}
@if(session('success'))
<div class="mb-4 bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded shadow-sm relative" role="alert">
    <strong class="font-bold">Berhasil!</strong>
    <span class="block sm:inline">{{ session('success') }}</span>
</div>
@endif

@if(session('error'))
<div class="mb-4 bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded shadow-sm relative" role="alert">
    <strong class="font-bold">Gagal!</strong>
    <span class="block sm:inline">{{ session('error') }}</span>
</div>
@endif

<div class="container mx-auto px-4 py-8">
    
    {{-- =================================================================== --}}
    {{-- HEADER & WIDGET SALDO --}}
    {{-- =================================================================== --}}
    <div class="flex flex-col xl:flex-row justify-between items-start xl:items-center mb-6 gap-6">
        
        {{-- Judul & Deskripsi --}}
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Daftar Produk PPOB</h1>
            <p class="text-sm text-gray-500 mt-1">Kelola harga beli, margin keuntungan, dan status produk Digiflazz.</p>
            
            <div class="mt-4 flex flex-wrap gap-2">
                {{-- TOMBOL EXPORT (BARU) --}}
                <div class="flex gap-1 mr-2">
                    <a href="{{ route('admin.ppob.export-excel', request()->all()) }}" target="_blank" class="bg-green-600 hover:bg-green-700 text-white px-3 py-2 rounded-lg text-xs font-bold shadow flex items-center gap-2 transition transform hover:scale-105">
                        <i class="fas fa-file-excel"></i> Excel
                    </a>
                    <a href="{{ route('admin.ppob.export-pdf', request()->all()) }}" target="_blank" class="bg-red-600 hover:bg-red-700 text-white px-3 py-2 rounded-lg text-xs font-bold shadow flex items-center gap-2 transition transform hover:scale-105">
                        <i class="fas fa-file-pdf"></i> PDF
                    </a>
                </div>

                {{-- Tombol Sync Data --}}
                <a href="{{ route('ppob.sync.prepaid') }}" id="btn-sync-prepaid" class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-2 rounded-lg text-xs font-bold shadow flex items-center gap-2">
                    <i class="fas fa-sync"></i> Sync Prabayar
                </a>
                
                <div class="w-px h-8 bg-gray-300 mx-1 hidden md:block"></div>

                {{-- Tombol Aksi Massal --}}
                <button onclick="openBulkModal()" class="bg-orange-500 hover:bg-orange-600 text-white px-3 py-2 rounded-lg text-xs font-bold shadow flex items-center gap-2">
                    <i class="fas fa-tags"></i> Update Margin Massal
                </button>
            </div>
        </div>

        {{-- Widget Saldo --}}
        <div class="w-full xl:w-auto bg-gradient-to-br from-gray-800 to-gray-900 rounded-xl p-5 text-white shadow-lg relative overflow-hidden">
            <div class="absolute right-0 top-0 opacity-10 transform translate-x-4 -translate-y-4">
                <i class="fas fa-wallet text-8xl"></i>
            </div>
            <div class="relative z-10 flex flex-col md:flex-row items-center gap-6">
                <div>
                    <p class="text-gray-400 text-xs font-medium uppercase tracking-wider mb-1">Sisa Deposit Digiflazz</p>
                    <div class="flex items-center gap-3">
                        <h3 id="saldo-display" class="text-2xl font-bold tracking-tight">Rp ...</h3>
                        <button onclick="fetchSaldo()" id="btn-refresh-saldo" class="text-gray-400 hover:text-white transition" title="Refresh">
                            <i class="fas fa-sync-alt" id="icon-refresh"></i>
                        </button>
                    </div>
                    <p id="saldo-loading" class="text-[10px] text-gray-400 hidden">Memuat...</p>
                </div>

                {{-- Tombol Aksi Saldo --}}
                <div class="flex gap-2">
                    <button onclick="openDepositModal()" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded-lg text-sm transition shadow flex items-center gap-2">
                        <i class="fas fa-plus-circle"></i> Isi Deposit
                    </button>
                    <button onclick="openTopupModal()" class="bg-white/10 hover:bg-white/20 text-white font-semibold py-2 px-4 rounded-lg text-sm transition border border-white/10 flex items-center gap-2">
                        <i class="fas fa-bolt"></i> Transaksi Manual
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- =================================================================== --}}
    {{-- TAB NAVIGASI & FILTER --}}
    {{-- =================================================================== --}}
    <div class="bg-white rounded-t-xl border border-gray-200 shadow-sm mt-8">
        <div class="flex flex-col md:flex-row justify-between items-center p-1 border-b border-gray-200 bg-gray-50 rounded-t-xl">
            {{-- Tabs --}}
            <div class="flex">
                @php $currentType = request('type', 'prepaid'); @endphp
                <a href="{{ route('admin.ppob.index', ['type' => 'prepaid', 'q' => request('q')]) }}" 
                   class="px-6 py-3 text-sm font-bold {{ $currentType === 'prepaid' ? 'text-blue-600 bg-white border-t-2 border-blue-600 shadow-sm' : 'text-gray-500 hover:text-gray-700' }}">
                   <i class="fas fa-mobile-alt mr-2"></i> Prabayar
                </a>
                <a href="{{ route('admin.ppob.index', ['type' => 'postpaid', 'q' => request('q')]) }}" 
                   class="px-6 py-3 text-sm font-bold {{ $currentType === 'postpaid' ? 'text-blue-600 bg-white border-t-2 border-blue-600 shadow-sm' : 'text-gray-500 hover:text-gray-700' }}">
                   <i class="fas fa-receipt mr-2"></i> Pascabayar
                </a>
            </div>

            {{-- Search Bar --}}
            <form action="{{ route('admin.ppob.index') }}" method="GET" class="p-2 w-full md:w-auto">
                <input type="hidden" name="type" value="{{ $currentType }}">
                <div class="relative">
                    <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari Nama, SKU, atau Brand..." 
                           class="w-full md:w-64 pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="fas fa-search text-gray-400"></i>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- =================================================================== --}}
    {{-- TABEL PRODUK --}}
    {{-- =================================================================== --}}
    <div class="bg-white shadow-md rounded-b-xl overflow-hidden border-x border-b border-gray-200">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left table-auto">
                <thead class="bg-gray-100 text-gray-600 uppercase text-xs font-bold tracking-wider">
                    <tr>
                        <th class="px-6 py-4">Kode SKU</th>
                        <th class="px-6 py-4">Produk</th>
                        <th class="px-6 py-4">Kategori / Brand</th>
                        <th class="px-6 py-4 text-right">Harga Beli</th>
                        <th class="px-6 py-4 text-right text-purple-600">Komisi</th>
                        <th class="px-6 py-4 text-right">Harga Jual</th>
                        <th class="px-6 py-4 text-center">Status</th>
                        <th class="px-6 py-4 text-center sticky right-0 bg-gray-100 z-10 shadow-sm">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white">
                    @forelse($products as $product)
                    <tr class="hover:bg-blue-50 transition duration-150 ease-in-out group">
                        {{-- SKU --}}
                        <td class="px-6 py-4 font-mono text-xs font-bold text-gray-500">
                            {{ $product->buyer_sku_code }}
                        </td>
                        
                        {{-- Nama Produk --}}
                        <td class="px-6 py-4">
                            <div class="font-medium text-gray-900">{{ Str::limit($product->product_name, 45) }}</div>
                            <div class="flex gap-1 mt-1">
                                @if($product->multi && $currentType === 'prepaid') 
                                    <span class="px-2 py-0.5 text-[10px] font-bold rounded bg-purple-100 text-purple-700">Promo</span>
                                @endif
                                @if(!$product->buyer_product_status)
                                    <span class="px-2 py-0.5 text-[10px] font-bold rounded bg-red-100 text-red-700">Gangguan Pusat</span>
                                @endif
                            </div>
                        </td>

                        {{-- Kategori --}}
                        <td class="px-6 py-4">
                            <div class="text-xs text-gray-500">{{ $product->category }}</div>
                            <div class="font-semibold text-gray-700">{{ $product->brand }}</div>
                        </td>

                        {{-- Harga Beli / Admin --}}
                        <td class="px-6 py-4 text-right font-mono text-gray-600">
                            @if($currentType === 'postpaid')
                                <span title="Admin Fee">Rp {{ number_format($product->admin_fee, 0, ',', '.') }}</span>
                            @else
                                Rp {{ number_format($product->price, 0, ',', '.') }}
                            @endif
                        </td>

                        {{-- Komisi (Khusus Pascabayar) --}}
                        <td class="px-6 py-4 text-right font-mono text-purple-600">
                            @if($currentType === 'postpaid')
                                Rp {{ number_format($product->commission, 0, ',', '.') }}
                            @else
                                -
                            @endif
                        </td>

                        {{-- Harga Jual --}}
                        <td class="px-6 py-4 text-right">
                            <span class="font-bold text-green-600 text-base font-mono">
                                Rp {{ number_format($product->sell_price, 0, ',', '.') }}
                            </span>
                        </td>
                        
                        {{-- Status Toko --}}
                        <td class="px-6 py-4 text-center">
                            @if($product->seller_product_status)
                                <span class="px-3 py-1 text-xs font-bold rounded-full bg-green-100 text-green-800 border border-green-200">Aktif</span>
                            @else
                                <span class="px-3 py-1 text-xs font-bold rounded-full bg-gray-100 text-gray-800 border border-gray-200">Nonaktif</span>
                            @endif
                        </td>
                        
                        {{-- Aksi --}}
                        <td class="px-6 py-4 text-center sticky right-0 bg-white group-hover:bg-blue-50 transition z-10 shadow-[-4px_0_6px_-4px_rgba(0,0,0,0.1)]">
                            <div class="flex items-center justify-center space-x-2">
                                {{-- Tombol Edit --}}
                                <button onclick="editPrice(
                                    '{{ $product->id }}', 
                                    '{{ addslashes($product->product_name) }}', 
                                    '{{ $product->price }}', 
                                    '{{ $product->sell_price }}', 
                                    '{{ $product->seller_product_status }}'
                                )" class="p-2 bg-yellow-100 text-yellow-600 rounded-lg hover:bg-yellow-200 transition" title="Edit Harga">
                                    <i class="fas fa-edit"></i>
                                </button>
                                
                                {{-- Tombol Hapus --}}
                                <button onclick="deleteProduct('{{ route('admin.ppob.destroy', $product->id) }}')" class="p-2 bg-red-100 text-red-600 rounded-lg hover:bg-red-200 transition" title="Hapus">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-6 py-12 text-center text-gray-500 bg-gray-50">
                            <div class="flex flex-col items-center">
                                <i class="fas fa-box-open text-4xl text-gray-300 mb-3"></i>
                                <p class="font-medium">Data produk tidak ditemukan.</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        <div class="bg-gray-50 px-6 py-4 border-t border-gray-200">
            {{ $products->appends(['type' => request('type'), 'q' => request('q')])->links() }}
        </div>
    </div>
</div>

{{-- =================================================================== --}}
{{-- MODAL 1: EDIT HARGA SATUAN --}}
{{-- =================================================================== --}}
<div id="priceModal" class="fixed inset-0 z-50 hidden transition-opacity" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="fixed inset-0 bg-gray-900 bg-opacity-75 backdrop-blur-sm transition-opacity" onclick="closeModal()"></div>
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
        <div class="inline-block align-bottom bg-white rounded-xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md w-full scale-100">
            <form id="priceForm" action="" method="POST" class="p-6">
                @csrf
                @method('PUT')
                
                <div class="flex justify-between items-center mb-5 border-b pb-4">
                    <h3 class="text-lg font-bold text-gray-900">Edit Harga Produk</h3>
                    <button type="button" onclick="closeModal()" class="text-gray-400 hover:text-gray-500"><i class="fas fa-times"></i></button>
                </div>

                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase">Nama Produk</label>
                        <input type="text" id="modal_product_name" class="w-full mt-1 bg-gray-100 border-gray-300 rounded-lg text-sm text-gray-600" readonly>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase">Harga Modal</label>
                            <input type="text" id="modal_base_price_display" class="w-full mt-1 bg-gray-100 border-gray-300 rounded-lg text-sm font-mono font-bold" readonly>
                            <input type="hidden" id="modal_base_price_raw">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase">Margin Profit</label>
                            <div class="flex mt-1">
                                <select id="single_profit_type" onchange="calculateSinglePrice()" class="bg-white border border-gray-300 text-gray-700 text-xs rounded-l-lg focus:ring-blue-500 focus:border-blue-500">
                                    <option value="rupiah">Rp</option>
                                    <option value="percent">%</option>
                                </select>
                                <input type="number" id="single_profit_value" oninput="calculateSinglePrice()" value="0" placeholder="0" class="w-full border-l-0 border-gray-300 rounded-r-lg text-sm focus:ring-blue-500 focus:border-blue-500">
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase">Harga Jual Akhir</label>
                        <div class="relative mt-1 rounded-md shadow-sm">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <span class="text-gray-500 sm:text-sm">Rp</span>
                            </div>
                            <input type="number" name="sell_price" id="modal_sell_price" class="focus:ring-green-500 focus:border-green-500 block w-full pl-10 text-lg font-bold border-gray-300 rounded-lg text-green-600" required>
                        </div>
                    </div>
                    
                    <div class="flex items-center pt-2">
                        <input id="modal_status" name="status" type="checkbox" class="w-5 h-5 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                        <label for="modal_status" class="ml-2 block text-sm text-gray-900 font-medium">Jual Produk Ini (Aktif)</label>
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <button type="button" onclick="closeModal()" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg font-medium hover:bg-gray-200">Batal</button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg font-bold hover:bg-blue-700 shadow">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- =================================================================== --}}
{{-- MODAL 2: UPDATE MASSAL --}}
{{-- =================================================================== --}}
<div id="bulkPriceModal" class="fixed inset-0 z-50 hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="fixed inset-0 bg-gray-900 bg-opacity-75 transition-opacity backdrop-blur-sm" onclick="closeBulkModal()"></div>
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
        <div class="inline-block align-bottom bg-white rounded-xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg w-full">
            <form action="{{ route('admin.ppob.bulk-update') }}" method="POST" class="p-6">
                @csrf
                <div class="flex justify-between items-center mb-5 border-b pb-4">
                    <div>
                        <h3 class="text-lg font-bold text-gray-900">Update Margin Massal</h3>
                        <p class="text-xs text-gray-500">Menerapkan margin ke SEMUA produk {{ $currentType }}.</p>
                    </div>
                    <button type="button" onclick="closeBulkModal()" class="text-gray-400 hover:text-gray-500"><i class="fas fa-times"></i></button>
                </div>
                
                <input type="hidden" name="product_type" value="{{ $currentType }}"> 

                <div class="bg-orange-50 p-4 rounded-lg border border-orange-200 mb-4">
                    <label class="block text-sm font-medium text-orange-900 mb-2">Pilih Tipe Keuntungan:</label>
                    <div class="flex gap-4">
                        <label class="flex items-center">
                            <input type="radio" name="profit_type" value="rupiah" checked class="text-orange-600 focus:ring-orange-500">
                            <span class="ml-2 text-sm text-gray-700">Nominal (Rp)</span>
                        </label>
                        <label class="flex items-center">
                            <input type="radio" name="profit_type" value="percent" class="text-orange-600 focus:ring-orange-500">
                            <span class="ml-2 text-sm text-gray-700">Persentase (%)</span>
                        </label>
                    </div>

                    <div class="mt-4">
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Nilai Keuntungan</label>
                        <input type="number" name="profit_value" placeholder="Cth: 2000 atau 5" required 
                               class="w-full border-gray-300 rounded-lg py-2 px-3 focus:ring-orange-500 focus:border-orange-500">
                    </div>
                </div>

                <div class="flex justify-end gap-3">
                    <button type="button" onclick="closeBulkModal()" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg font-medium hover:bg-gray-200">Batal</button>
                    <button type="submit" onclick="return confirm('Yakin update semua harga?')" class="px-4 py-2 bg-orange-600 text-white rounded-lg font-bold hover:bg-orange-700 shadow">Terapkan Massal</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- =================================================================== --}}
{{-- MODAL 3: DEPOSIT (POP UP - AJAX HANDLING) --}}
{{-- =================================================================== --}}
<div id="depositModal" class="fixed inset-0 z-50 hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="fixed inset-0 bg-gray-900 bg-opacity-75 transition-opacity backdrop-blur-sm" onclick="closeDepositModal()"></div>
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
        <div class="inline-block align-bottom bg-white rounded-xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md w-full">
            {{-- Form menggunakan AJAX Submission --}}
            <form id="formDeposit" onsubmit="submitDeposit(event)" class="p-6"> 
                @csrf
                <div class="flex justify-between items-center mb-5 border-b pb-4">
                    <h3 class="text-lg font-bold text-gray-900">Tambah Deposit Saldo</h3>
                    <button type="button" onclick="closeDepositModal()" class="text-gray-400 hover:text-gray-500"><i class="fas fa-times"></i></button>
                </div>
                
                <div class="space-y-4 mb-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Bank Transfer</label>
                        <select name="bank" class="w-full rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500">
                            <option value="BCA">BCA</option>
                            <option value="MANDIRI">MANDIRI</option>
                            <option value="BRI">BRI</option>
                            <option value="BNI">BNI</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nominal Deposit</label>
                        <div class="relative rounded-md shadow-sm">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <span class="text-gray-500 sm:text-sm">Rp</span>
                            </div>
                            <input type="number" name="amount" class="focus:ring-blue-500 focus:border-blue-500 block w-full pl-10 sm:text-sm border-gray-300 rounded-md" placeholder="0" min="50000" required>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nama Pemilik Rekening</label>
                        <input type="text" name="owner_name" class="w-full rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500" placeholder="Atas Nama" required>
                    </div>
                </div>

                <div class="flex justify-end gap-3">
                    <button type="button" onclick="closeDepositModal()" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg font-medium hover:bg-gray-200">Batal</button>
                    <button type="submit" id="btnSubmitDepo" class="px-4 py-2 bg-blue-600 text-white rounded-lg font-bold hover:bg-blue-700 shadow flex items-center">
                        <span id="btnDepoText">Request Tiket</span>
                        <i id="btnDepoIcon" class="fas fa-arrow-right ml-2"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- =================================================================== --}}
{{-- MODAL 4: TOPUP MANUAL (DENGAN MAX PRICE) --}}
{{-- =================================================================== --}}
<div id="topupModal" class="fixed inset-0 z-50 hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="fixed inset-0 bg-gray-900 bg-opacity-75 transition-opacity backdrop-blur-sm" onclick="closeTopupModal()"></div>
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
        <div class="inline-block align-bottom bg-white rounded-xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md w-full">
            <form action="{{ route('admin.ppob.topup') }}" method="POST" class="p-6">
                @csrf
                <div class="flex justify-between items-center mb-5 border-b pb-4">
                    <h3 class="text-lg font-bold text-gray-900">Transaksi Manual (Darurat)</h3>
                    <button type="button" onclick="closeTopupModal()" class="text-gray-400 hover:text-gray-500"><i class="fas fa-times"></i></button>
                </div>
                
                <div class="space-y-4 mb-6">
                    <div class="bg-yellow-50 p-3 rounded text-xs text-yellow-800 border border-yellow-200">
                        <i class="fas fa-exclamation-triangle mr-1"></i> Gunakan fitur ini hanya jika transaksi via website user gagal atau untuk kebutuhan tes.
                    </div>
                    
                    {{-- Input SKU --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Kode Produk (SKU)</label>
                        <input type="text" name="buyer_sku_code" class="w-full rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500 uppercase" placeholder="Contoh: xld10" required>
                    </div>
                    
                    {{-- Input Nomor Tujuan --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nomor Tujuan / ID Pelanggan</label>
                        <input type="text" name="customer_no" class="w-full rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500" placeholder="08..." required>
                    </div>

                    {{-- UPDATE DISINI: Input Max Price --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Limit Harga Beli (Max Price) 
                            <span class="text-xs text-gray-400 font-normal ml-1">(Opsional)</span>
                        </label>
                        <div class="relative rounded-md shadow-sm">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <span class="text-gray-500 sm:text-sm">Rp</span>
                            </div>
                            <input type="number" name="max_price" class="focus:ring-blue-500 focus:border-blue-500 block w-full pl-10 sm:text-sm border-gray-300 rounded-md" placeholder="Kosongkan jika tanpa limit">
                        </div>
                        <p class="text-[10px] text-gray-500 mt-1">Transaksi akan <b>GAGAL</b> jika harga dari pusat lebih mahal dari nominal ini.</p>
                    </div>
                </div>

                <div class="flex justify-end gap-3">
                    <button type="button" onclick="closeTopupModal()" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg font-medium hover:bg-gray-200">Batal</button>
                    <button type="submit" onclick="return confirm('Yakin ingin tembak transaksi ini? Saldo admin akan terpotong.')" class="px-4 py-2 bg-blue-600 text-white rounded-lg font-bold hover:bg-blue-700 shadow">Kirim Transaksi</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('scripts')
{{-- Load SweetAlert2 Library --}}
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    /**
     * 1. CONFIG & UTILITIES
     */
    const csrfToken = document.querySelector('meta[name="csrf-token"]') ? document.querySelector('meta[name="csrf-token"]').getAttribute('content') : '';
    
    // Format Rupiah Helper
    const formatRupiah = (number) => {
        return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(number);
    };

    /**
     * 2. FETCH SALDO (Realtime)
     */
    function fetchSaldo() {
        const display = document.getElementById('saldo-display');
        const loading = document.getElementById('saldo-loading');
        const icon = document.getElementById('icon-refresh');

        if (!display) return;

        // UI Loading State
        icon.classList.add('fa-spin');
        loading.classList.remove('hidden');
        display.classList.add('opacity-50');

        fetch("{{ route('admin.ppob.cek-saldo') }}")
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    display.innerText = data.formatted; // Pastikan controller return format "Rp 1.xxx.xxx"
                } else {
                    display.innerText = 'Gagal Memuat';
                }
            })
            .catch(error => {
                console.error('Error fetching saldo:', error);
                display.innerText = 'Error';
            })
            .finally(() => {
                icon.classList.remove('fa-spin');
                loading.classList.add('hidden');
                display.classList.remove('opacity-50');
            });
    }

    // Jalankan fetchSaldo saat halaman selesai dimuat
    document.addEventListener('DOMContentLoaded', function() {
        fetchSaldo();
    });

    /**
     * 3. MODAL CONTROLLERS (Open/Close)
     */
    function openModal(modalId) {
        const modal = document.getElementById(modalId);
        if(modal) {
            modal.classList.remove('hidden');
            // Animasi kecil (Opsional)
            const panel = modal.querySelector('div[class*="transform"]');
            if(panel) {
                panel.classList.remove('scale-95', 'opacity-0');
                panel.classList.add('scale-100', 'opacity-100');
            }
        }
    }

    function closeModalById(modalId) {
        const modal = document.getElementById(modalId);
        if(modal) {
            modal.classList.add('hidden');
        }
    }

    // Mapping Fungsi Spesifik ke Generic
    function openDepositModal() { openModal('depositModal'); }
    function closeDepositModal() { closeModalById('depositModal'); }
    
    function openTopupModal() { openModal('topupModal'); }
    function closeTopupModal() { closeModalById('topupModal'); }
    
    function openBulkModal() { openModal('bulkPriceModal'); }
    function closeBulkModal() { closeModalById('bulkPriceModal'); }

    // Edit Price Modal (Populate Data)
    function editPrice(id, name, basePrice, sellPrice, status) {
        // Isi Form
        document.getElementById('modal_product_name').value = name;
        document.getElementById('modal_base_price_raw').value = parseFloat(basePrice);
        document.getElementById('modal_base_price_display').value = formatRupiah(basePrice);
        document.getElementById('modal_sell_price').value = parseFloat(sellPrice);
        
        // Reset Calculator Inputs
        document.getElementById('single_profit_value').value = 0;
        document.getElementById('single_profit_type').value = 'rupiah';

        // Set Checkbox Status
        const statusCheckbox = document.getElementById('modal_status');
        if (statusCheckbox) statusCheckbox.checked = (status == 1);

        // Set Action URL Form
        let url = "{{ route('admin.ppob.update-price', ':id') }}";
        url = url.replace(':id', id);
        document.getElementById('priceForm').action = url;

        openModal('priceModal');
    }
    
    function closeModal() { closeModalById('priceModal'); }

    /**
     * 4. LOGIC CALCULATOR MARGIN (Edit Price Modal)
     */
    function calculateSinglePrice() {
        const basePrice = parseFloat(document.getElementById('modal_base_price_raw').value) || 0;
        const profitValue = parseFloat(document.getElementById('single_profit_value').value) || 0;
        const profitType = document.getElementById('single_profit_type').value;
        
        let finalPrice = basePrice;

        if (profitType === 'rupiah') {
            finalPrice += profitValue;
        } else {
            // Persentase
            finalPrice += basePrice * (profitValue / 100);
        }

        // Pembulatan ke atas (Ceiling) agar aman
        document.getElementById('modal_sell_price').value = Math.ceil(finalPrice);
    }

    /**
     * 5. LOGIC DEPOSIT (AJAX SUBMISSION)
     * Menangani respon JSON dari Controller agar tampil cantik di SweetAlert
     */
    function submitDeposit(e) {
        e.preventDefault();

        const form = document.getElementById('formDeposit');
        const btn = document.getElementById('btnSubmitDepo');
        const btnText = document.getElementById('btnDepoText');
        const formData = new FormData(form);

        // Loading State
        btn.disabled = true;
        btnText.innerText = 'Memproses...';

        fetch("{{ route('admin.ppob.deposit') }}", {
            method: "POST",
            headers: {
                "X-CSRF-TOKEN": csrfToken,
                "Accept": "application/json" // Memaksa respon JSON
            },
            body: formData
        })
        .then(async response => {
            const data = await response.json();
            if (!response.ok) {
                throw new Error(data.message || 'Terjadi kesalahan server.');
            }
            return data;
        })
        .then(data => {
            // Sukses
            closeDepositModal();
            form.reset();
            
            Swal.fire({
                icon: 'success',
                title: 'Tiket Deposit Dibuat!',
                html: `
                    <div class="text-left text-sm bg-gray-50 p-4 rounded-lg border">
                        <p class="mb-2">Silakan transfer nominal <b>PERSIS</b> (jangan dibulatkan):</p>
                        <h3 class="text-2xl font-bold text-blue-600 mb-3">${data.formatted_amount || formatRupiah(data.amount)}</h3>
                        <hr class="my-2">
                        <p><b>Bank:</b> ${data.bank}</p>
                        <p><b>No. Rekening:</b> ${data.account_no}</p>
                        <p><b>A.N:</b> ${data.account_name}</p>
                    </div>
                    <p class="mt-3 text-xs text-gray-500">Saldo akan masuk otomatis 5-10 menit setelah transfer.</p>
                `,
                confirmButtonText: 'OK, Siap Transfer'
            });
        })
        .catch(error => {
            // Error
            Swal.fire({
                icon: 'error',
                title: 'Gagal Membuat Tiket',
                text: error.message,
            });
        })
        .finally(() => {
            btn.disabled = false;
            btnText.innerText = 'Request Tiket';
        });
    }

    /**
     * 6. LOGIC DELETE PRODUK (Konfirmasi SweetAlert)
     */
    function deleteProduct(url) {
        Swal.fire({
            title: 'Hapus Produk?',
            text: "Produk akan dihapus dari database lokal. Anda bisa menyinkronkan ulang nanti.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Ya, Hapus!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                // Submit Form Delete secara Programmatic
                const form = document.createElement('form');
                form.action = url;
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="_token" value="${csrfToken}">
                    <input type="hidden" name="_method" value="DELETE">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        });
    }

    /**
     * 7. (Opsional) HANDLER TOPUP MANUAL
     * Memberikan loading saat submit form topup manual
     */
    document.querySelector('#topupModal form')?.addEventListener('submit', function(e) {
        const btn = this.querySelector('button[type="submit"]');
        if(btn) {
            const originalText = btn.innerText;
            btn.innerText = 'Mengirim...';
            btn.disabled = true;
            
            // Timeout safety jika submit memakan waktu lama (atau biarkan browser handle redirect)
            setTimeout(() => {
                btn.disabled = false;
                btn.innerText = originalText;
            }, 10000);
        }
    });

</script>
@endpush