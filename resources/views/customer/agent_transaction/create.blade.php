@extends('layouts.customer')

@section('title', 'Kasir Penjualan PPOB')

@section('content')

{{-- Notifikasi Error Validasi Laravel (PENTING) --}}
    @if ($errors->any())
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded shadow-sm mb-4 animate-pulse">
            <div class="flex items-center gap-2 mb-1">
                <i class="fas fa-exclamation-triangle"></i>
                <p class="font-bold">Gagal Memproses Transaksi:</p>
            </div>
            <ul class="list-disc list-inside text-sm">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
<div class="max-w-7xl mx-auto space-y-6">

    {{-- Header --}}
    <div class="flex flex-col md:flex-row justify-between items-center gap-4 bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
        <div>
            <h1 class="2xl font-bold text-gray-800 flex items-center gap-2">
                <i class="fas fa-cash-register text-blue-600"></i> Kasir / Transaksi Offline
            </h1>
            <p class="text-sm text-gray-500">Layanan penjualan langsung untuk pelanggan yang datang ke lokasi Anda.</p>
        </div>
        <div class="bg-blue-50 px-5 py-3 rounded-xl text-right border border-blue-100">
            <p class="text-[10px] text-blue-600 font-bold uppercase tracking-wider">Saldo Aktif Anda</p>
            <p class="2xl font-extrabold text-blue-800">Rp {{ number_format(Auth::user()->saldo, 0, ',', '.') }}</p>
        </div>
    </div>

    {{-- Notifikasi --}}
    @if(session('success'))
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded shadow-sm flex items-center gap-2 animate-fade-in-down">
            <i class="fas fa-check-circle text-xl"></i>
            <div>
                <p class="font-bold">Berhasil!</p>
                <p>{{ session('success') }}</p>
            </div>
        </div>
    @endif
    @if(session('error'))
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded shadow-sm flex items-center gap-2 animate-fade-in-down">
            <i class="fas fa-exclamation-circle text-xl"></i>
            <div>
                <p class="font-bold">Gagal!</p>
                <p>{{ session('error') }}</p>
            </div>
        </div>
    @endif

    {{-- TABS NAVIGATION --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-2 flex gap-2">
        <button onclick="switchTab('prabayar')" id="tab-prabayar" class="flex-1 py-3 rounded-lg font-bold text-sm transition bg-blue-600 text-white shadow-md">
            <i class="fas fa-mobile-alt mr-2"></i> Isi Ulang (Prabayar)
        </button>
        <button onclick="switchTab('pascabayar')" id="tab-pascabayar" class="flex-1 py-3 rounded-lg font-bold text-sm text-gray-500 hover:bg-gray-50 transition">
            <i class="fas fa-file-invoice-dollar mr-2"></i> Bayar Tagihan (Pascabayar)
        </button>
    </div>

    {{-- ========================================================= --}}
    {{-- KONTEN: PRABAYAR (PULSA/DATA/TOKEN) --}}
    {{-- ========================================================= --}}
    <div id="content-prabayar" class="grid grid-cols-1 lg:grid-cols-3 gap-6 transition-all duration-300">
        
        {{-- KOLOM KIRI: INPUT NOMOR --}}
        <div class="lg:col-span-1">
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200 sticky top-24">
                <h3 class="font-bold text-gray-800 mb-4 border-b pb-2 flex items-center gap-2">
                    <span class="bg-blue-600 text-white w-6 h-6 rounded-full flex items-center justify-center text-xs">1</span> 
                    Input Nomor Tujuan
                </h3>
                
                <div class="mb-4 relative">
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Nomor HP / Token / E-Wallet</label>
                    <div class="relative group">
                        <input type="number" id="input_customer_no" 
                               class="w-full pl-4 pr-12 py-3.5 rounded-xl border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-xl font-bold tracking-wide transition placeholder-gray-300"
                               placeholder="08xxxxxxxxxx" onkeyup="detectOperator()">
                        <div id="loading_detect" class="absolute right-4 top-1/2 transform -translate-y-1/2 hidden">
                            <i class="fas fa-circle-notch fa-spin text-gray-400"></i>
                        </div>
                    </div>

                    {{-- Operator Badge --}}
                    <div id="operator_badge" class="mt-3 hidden transition-all duration-300 transform scale-95 opacity-0">
                        <div class="flex items-center gap-2 bg-gray-50 border border-gray-200 p-2 rounded-lg">
                            <img id="operator_logo" src="" class="w-8 h-8 object-contain rounded bg-white p-0.5 border border-gray-100">
                            <div>
                                <p class="xs text-gray-400 font-bold uppercase">Terdeteksi:</p>
                                <p class="sm font-bold text-gray-800" id="operator_name">-</p>
                            </div>
                            <span class="ml-auto text-green-500"><i class="fas fa-check-circle"></i></span>
                        </div>
                    </div>
                </div>

                {{-- <<< INPUT BARU: NOMOR WA PEMBELI >>> --}}
<div class="mb-6">
    <label class="block text-[11px] font-semibold text-gray-500 tracking-wide mb-1 uppercase">
        Nomor WA Pembeli <span class="text-red-500">*</span>
    </label>

    <div class="relative">
        <img 
            src="https://tokosancaka.com/public/storage/logo/wa.png" 
            class="w-5 h-5 absolute left-3 top-1/2 -translate-y-1/2 opacity-90"
            alt="WA Logo"
        >

        <input 
            type="number" 
            id="input_customer_wa_pra"
            placeholder="08xxxxxxxxxx (Untuk kirim SN)"
            class="w-full pl-12 pr-4 py-3 
                   rounded-xl 
                   border border-green-300 
                   bg-white shadow-sm
                   focus:ring-2 focus:ring-green-500 focus:border-green-500
                   text-base font-medium
                   transition-all
                   placeholder-red-600"
        >
    </div>

    <p class="text-[11px] text-gray-400 mt-1">Nomor WhatsApp untuk pengiriman SN otomatis.</p>
</div>
{{-- <<< AKHIR INPUT BARU >>> --}}


                <div class="bg-yellow-50 p-4 rounded-xl border border-yellow-100 text-xs text-yellow-800 leading-relaxed">
                    <i class="fas fa-lightbulb mr-1 text-yellow-600"></i> 
                    <strong>Tips:</strong> Masukkan nomor HP untuk Pulsa/Data, atau ID Pelanggan untuk Token PLN/E-Wallet.
                </div>
            </div>
        </div>

        {{-- KOLOM KANAN: PILIH PRODUK --}}
        <div class="lg:col-span-2">
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200 min-h-[500px]">
                <div class="flex flex-col sm:flex-row justify-between items-center mb-4 gap-4 border-b pb-4">
                    <h3 class="font-bold text-gray-800 flex items-center gap-2">
                        <span class="bg-blue-600 text-white w-6 h-6 rounded-full flex items-center justify-center text-xs">2</span> 
                        Pilih Produk
                    </h3>
                    
                    {{-- Search Manual --}}
                    <div class="w-full sm:w-1/2 relative">
                        <input type="text" id="search_product" onkeyup="filterTableManual()"
                               class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-blue-500 bg-gray-50 focus:bg-white transition" 
                               placeholder="Cari manual (cth: Telkomsel 10rb)...">
                        <i class="fas fa-search absolute left-3 top-2.5 text-gray-400"></i>
                    </div>
                </div>

                <div id="instruction_alert" class="flex flex-col items-center justify-center py-10 text-center text-gray-400 animate-pulse">
                    <i class="fas fa-keyboard text-5xl mb-3 text-gray-200"></i>
                    <p class="font-medium">Silakan masukkan nomor tujuan terlebih dahulu.</p>
                </div>

                <div id="product_container" class="hidden overflow-hidden rounded-xl border border-gray-100">
                    <table class="w-full text-sm text-left">
                        <thead class="bg-gray-50 text-gray-500 uppercase text-[10px] font-bold tracking-wider">
                            <tr>
                                <th class="px-4 py-3">Produk</th>
                                <th class="px-4 py-3 text-center">Brand</th>
                                <th class="px-4 py-3 text-right text-green-700 bg-green-50/50">Harga Jual</th>
                                <th class="px-4 py-3 text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100" id="product_table_body">
                            @foreach($products as $product)
                                @php
                                    $modal = $product->modal_agen;
                                    $jual = $product->harga_jual_agen ?? ($modal + 2000);
                                    $profit = $jual - $modal;
                                @endphp
                                <tr class="hover:bg-blue-50 transition group product-row" 
                                    data-brand="{{ strtolower($product->brand) }}" 
                                    data-name="{{ strtolower($product->product_name) }}"
                                    data-category="{{ strtolower($product->category) }}"
                                    data-price="{{ $jual }}">
                                    
                                    <td class="px-4 py-3">
                                        <div class="font-bold text-gray-800 text-sm">{{ $product->product_name }}</div>
                                        <div class="text-[10px] text-gray-400 font-mono">{{ $product->buyer_sku_code }}</div>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <span class="px-2 py-1 rounded text-[10px] font-bold bg-gray-100 text-gray-600 uppercase">{{ $product->brand }}</span>
                                    </td>
                                    <td class="px-4 py-3 text-right bg-green-50/30">
                                        <div class="font-extrabold text-green-700">Rp {{ number_format($jual, 0, ',', '.') }}</div>
                                        <div class="text-[9px] text-green-500">Untung: Rp {{ number_format($profit) }}</div>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <button onclick="confirmTransaction('{{ $product->buyer_sku_code }}', '{{ addslashes($product->product_name) }}', '{{ $modal }}', '{{ $jual }}')" 
                                                class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-1.5 rounded-lg font-bold text-xs shadow-md transition transform hover:scale-105 flex items-center justify-center w-full">
                                            PILIH
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <div id="no_result" class="hidden py-8 text-center text-gray-500 text-sm">
                        Tidak ada produk yang cocok.
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ========================================================= --}}
    {{-- KONTEN: PASCABAYAR (TAGIHAN) --}}
    {{-- ========================================================= --}}
    <div id="content-pascabayar" class="hidden grid grid-cols-1 lg:grid-cols-3 gap-6 transition-all duration-300">
        
        {{-- FORM CEK TAGIHAN --}}
        <div class="lg:col-span-1">
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200">
                <h3 class="font-bold text-gray-800 mb-6 border-b pb-2 flex items-center gap-2">
                    <span class="bg-red-600 text-white w-6 h-6 rounded-full flex items-center justify-center text-xs">1</span> 
                    Cek Tagihan
                </h3>

                <div class="space-y-4">
                    {{-- Select Jenis --}}
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Jenis Tagihan</label>
                        <select id="pasca_sku" class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:ring-2 focus:ring-red-500 focus:border-red-500 bg-white" onchange="handlePascaSkuChange()">
                            <option value="pln">PLN Pascabayar</option>
                            <option value="bpjs">BPJS Kesehatan</option>
                            <option value="pdam">PDAM</option>
                            <option value="internet">Telkom / Indihome</option>
                            <option value="pgas">Gas Negara</option>
                            <option value="multifinance">Multifinance / Cicilan</option>
                            <option value="pbb-city">Pajak PBB (Pilih Kota)</option>
                            <option value="samsat">E-Samsat</option>
                        </select>
                    </div>

                    {{-- Dynamic PBB City Selection / Search Input --}}
                    <div id="dynamic_city_selection" class="hidden relative">
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Pilih Kota PBB</label>
                        <input type="text" id="pbb_city_search" 
                            class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:ring-2 focus:ring-red-500 focus:border-red-500 font-bold text-gray-800 placeholder-gray-300"
                            placeholder="Ketik nama kota, misal: Cimahi"
                            onkeyup="filterPbbCities(this.value)">
                        
                        <input type="hidden" id="pbb_city_sku_selected"> {{-- Tempat menyimpan SKU yang terpilih --}}

                        {{-- Dropdown Hasil Pencarian --}}
                        <div id="pbb_search_results" class="absolute z-10 w-full bg-white border border-gray-300 rounded-xl shadow-lg mt-1 max-h-48 overflow-y-auto hidden">
                            {{-- Results will be injected here --}}
                        </div>
                    </div>

                    {{-- Input Nomor --}}
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Nomor Pelanggan / ID</label>
                        <input type="text" id="pasca_no" 
                               class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:ring-2 focus:ring-red-500 font-bold text-gray-800 placeholder-gray-300"
                               placeholder="Contoh: 5300xxxx">
                        <p id="test_case_info" class="text-xs text-red-500 mt-1 italic">
                            *Gunakan Test Case PBB: 329801092375999991, Internet: 7391601001, PLN: 630000000001
                        </p>
                    </div>

                    {{-- <<< INPUT BARU: NOMOR WA PEMBELI >>> --}}
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Nomor WA Pembeli (Wajib)</label>
                        <input type="number" id="input_customer_wa_pasca" 
                               class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:ring-2 focus:ring-green-500 focus:border-green-500 text-base font-bold transition placeholder-gray-300"
                               placeholder="08xxxxxxxxxx (Untuk kirim SN)">
                    </div>
                    {{-- <<< AKHIR INPUT BARU >>> --}}

                    {{-- Button Cek --}}
                    <button onclick="cekTagihan()" id="btn-cek-tagihan" class="w-full bg-red-600 hover:bg-red-700 text-white font-bold py-3 px-4 rounded-xl shadow-lg shadow-red-200 transition flex justify-center items-center gap-2">
                        <i class="fas fa-search"></i> Cek Tagihan
                    </button>
                </div>
            </div>
        </div>

        {{-- HASIL TAGIHAN --}}
        <div class="lg:col-span-2">
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200 min-h-[300px]">
                <h3 class="font-bold text-gray-800 mb-6 border-b pb-2 flex items-center gap-2">
                    <span class="bg-red-600 text-white w-6 h-6 rounded-full flex items-center justify-center text-xs">2</span> 
                    Rincian Tagihan
                </h3>

                {{-- State: Kosong / Error --}}
                <div id="pasca_empty" class="flex flex-col items-center justify-center py-10 text-gray-400">
                    <i class="fas fa-file-invoice-dollar text-6xl mb-4 text-gray-200"></i>
                    <p>Silakan lakukan cek tagihan terlebih dahulu.</p>
                </div>

                {{-- State: Loading --}}
                <div id="pasca_loading" class="hidden flex flex-col items-center justify-center py-10 text-red-600">
                    <i class="fas fa-circle-notch fa-spin text-4xl mb-4"></i>
                    <p class="font-bold animate-pulse">Sedang mengecek tagihan...</p>
                </div>

                {{-- State: Hasil --}}
                <div id="pasca_result" class="hidden space-y-4">
                    <div class="bg-red-50 rounded-xl p-5 border border-red-100">
                        
                        {{-- Data Utama --}}
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm mb-4">
                            <div>
                                <p class="text-xs text-gray-500 uppercase">Nama Pelanggan</p>
                                <p class="font-bold text-gray-800 text-lg break-words" id="res_nama">-</p>
                            </div>
                            <div>
                                <p class="xs text-gray-500 uppercase">ID Pelanggan</p>
                                <p class="font-bold text-gray-800 break-words" id="res_id">-</p>
                            </div>
                            <div>
                                <p class="xs text-gray-500 uppercase">Periode</p>
                                <p class="font-bold text-gray-800" id="res_periode">-</p>
                            </div>
                            <div>
                                <p class="xs text-gray-500 uppercase">Total Tagihan</p>
                                <p class="font-extrabold text-red-700 text-xl" id="res_total">-</p>
                            </div>
                        </div>
                        
                        {{-- Detail Tambahan (Alamat, Tarif, Daya, Kendaraan, Luas) --}}
                        <div id="row_detail_teknis" class="hidden border-t border-red-200 pt-4 mb-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                             <div>
                                <p class="text-xs text-gray-500 uppercase">Alamat / Lokasi</p>
                                <p class="font-bold text-gray-700 break-words" id="res_alamat">-</p>
                            </div>
                             <div>
                                <p class="xs text-gray-500 uppercase" id="label_tarif">Detail Teknis</p>
                                <p class="font-bold text-gray-700" id="res_tarif">-</p>
                            </div>
                        </div>

                        {{-- Data Keuangan (Admin, Denda, Lembar) --}}
                        <div class="pt-4 border-t border-red-200 grid grid-cols-2 md:grid-cols-4 gap-4">
                            <div>
                                <p class="xs text-gray-500 uppercase">Admin Fee</p>
                                <p class="font-bold text-gray-700" id="res_admin">-</p>
                            </div>
                            <div>
                                <p class="xs text-gray-500 uppercase">Denda</p>
                                <p class="font-bold text-gray-700" id="res_denda">-</p>
                            </div>
                            <div>
                                <p class="xs text-gray-500 uppercase">Lembar</p>
                                <p class="font-bold text-gray-700" id="res_lembar">-</p>
                            </div>
                            {{-- Info Modal Agen --}}
                            <div>
                                <p class="text-xs text-gray-500 uppercase text-red-500 font-bold"><i class="fas fa-lock"></i> Modal Agen</p>
                                <p class="font-bold text-red-600" id="res_modal">-</p>
                            </div>
                        </div>

                        {{-- Rincian Item (Desc Detail) --}}
                        <div id="res_detail_container" class="mt-4 pt-4 border-t border-dashed border-red-300 hidden">
                            <p class="xs font-bold text-red-700 uppercase mb-2">Rincian Item</p>
                            <div id="res_detail_list" class="space-y-2 text-xs bg-white p-3 rounded-lg border border-red-100">
                                {{-- Item details injected via JS --}}
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end pt-4">
                        <form action="{{ route('agent.transaction.store') }}" method="POST" id="form-pay-pasca">
                            @csrf
                            <input type="hidden" name="payment_type" value="pasca">
                            {{-- UBAH ID DISINI --}}
                            <input type="hidden" name="sku" id="pay_sku_pasca_final"> 
                            {{-- ---------------- --}}
                            <input type="hidden" name="customer_no" id="pay_no">
                            <input type="hidden" name="ref_id" id="pay_ref_id"> 
                            <input type="hidden" name="selling_price" id="pay_price">
                        

                                {{-- <<< IDEMPOTENCY KEY PASCABAYAR >>> --}}
                                <input type="hidden" name="idempotency_key" value="{{ $idempotencyKey ?? \Illuminate\Support\Str::uuid() }}" id="form_idempotency_pasca">
                                {{-- <<< AKHIR IDEMPOTENCY KEY PASCABAYAR >>> --}}

                                {{-- <<< HIDDEN FIELD WA PASCABAYAR >>> --}}
                                <input type="hidden" name="customer_wa" id="pay_customer_wa">
                                {{-- <<< AKHIR HIDDEN FIELD WA PASCABAYAR >>> --}}
                            
                            <button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-bold py-3 px-8 rounded-xl shadow-lg shadow-green-200 transition flex items-center gap-2 transform hover:scale-105">
                                <i class="fas fa-check-circle"></i> Bayar Sekarang
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

{{-- MODAL KONFIRMASI PRABAYAR --}}
<div id="confirmModal" class="fixed inset-0 z-50 hidden backdrop-blur-sm" role="dialog">
    <div class="fixed inset-0 bg-gray-900 bg-opacity-60 transition-opacity" onclick="closeModal()"></div>
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm transform transition-all p-0 overflow-hidden scale-95 opacity-0" id="modal_content">
            <div class="bg-blue-600 p-4 text-white text-center">
                <h3 class="lg font-bold">Konfirmasi Transaksi</h3>
            </div>
            <div class="p-6 space-y-4">
                <div class="flex justify-between items-end border-b border-gray-100 pb-3">
                    <span class="text-xs text-gray-500 uppercase font-bold">Nomor Tujuan</span>
                    <span class="font-mono font-bold text-gray-800 text-lg tracking-wide" id="modal_no">-</span>
                </div>
                <div>
                    <span class="text-xs text-gray-500 uppercase font-bold mb-1 block">Produk</span>
                    <span class="font-bold text-gray-800 text-sm leading-tight block" id="modal_product">-</span>
                </div>
                <div class="bg-green-50 p-3 rounded-xl border border-green-100">
                    <span class="text-xs text-green-500 font-bold uppercase block">Modal Agen</span>
                    <span class="font-bold text-green-700 text-base" id="modal_jual">Rp 0</span>
                </div>
            </div>
            <form id="form-pay-pra" action="{{ route('agent.transaction.store') }}" method="POST" class="p-4 bg-gray-50 border-t border-gray-100">
                @csrf
                <input type="hidden" name="sku" id="form_sku">
                <input type="hidden" name="payment_type" value="pra">
                <input type="hidden" name="customer_no" id="form_no">
                <input type="hidden" name="customer_wa" id="form_customer_wa">
        

                {{-- <<< IDEMPOTENCY KEY PRABAYAR >>> --}}
                <input type="hidden" name="idempotency_key" value="{{ $idempotencyKey ?? \Illuminate\Support\Str::uuid() }}" id="form_idempotency_pra">
                {{-- <<< AKHIR IDEMPOTENCY KEY PRABAYAR >>> --}}

                <div class="flex gap-3">
                    <button type="button" onclick="closeModal()" class="flex-1 py-3 bg-white text-gray-700 font-bold rounded-xl border border-gray-300 hover:bg-gray-50 transition text-sm">Batal</button>
                    <button type="submit" class="flex-1 py-3 bg-blue-600 text-white font-bold rounded-xl hover:bg-blue-700 transition shadow-lg shadow-blue-200 text-sm">
                        PROSES
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    const logoBasePath = "{{ asset('public/storage/logo-ppob') }}/";
    let pbbCitiesCache = []; 

    // --- 1. INISIALISASI SAAT LOAD ---
    window.onload = function() {
        // Cek jika tab Pascabayar aktif (misal setelah refresh error)
        if (document.getElementById('content-pascabayar') && !document.getElementById('content-pascabayar').classList.contains('hidden')) {
            handlePascaSkuChange();
        }
        
        // Listener Dropdown Jenis Tagihan
        const pascaSku = document.getElementById('pasca_sku');
        if(pascaSku) pascaSku.addEventListener('change', handlePascaSkuChange);

        // Listener Search PBB
        const pbbSearchInput = document.getElementById('pbb_city_search');
        if (pbbSearchInput) {
            let searchTimeout;
            pbbSearchInput.addEventListener('keyup', function() {
                clearTimeout(searchTimeout);
                const query = this.value;
                searchTimeout = setTimeout(() => { filterPbbCities(query); }, 300);
            });
            pbbSearchInput.addEventListener('blur', function() {
                 setTimeout(() => { document.getElementById('pbb_search_results').classList.add('hidden'); }, 200);
            });
            pbbSearchInput.addEventListener('focus', function() {
                 if (this.value.length > 0) document.getElementById('pbb_search_results').classList.remove('hidden');
            });
        }
    };

    // --- FORMATTER PERIODE ---
    function formatPeriodeID(periodeStr) {
        if (!periodeStr || periodeStr === '-' || periodeStr === 'null') return '-';
        let str = periodeStr.toString().trim();
        
        // Cek jika formatnya angka 6 digit (YYYYMM), misal: 202512
        if (/^\d{6}$/.test(str)) {
            let year = str.substring(0, 4);
            let month = parseInt(str.substring(4, 6));
            const months = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
            if (months[month]) return `${months[month]} ${year}`;
        }
        
        // Jika formatnya sudah teks seperti "DES 25" atau "DEC 2025", kembalikan apa adanya
        return str;
    }

    // --- 3. NAVIGASI TAB ---
    function switchTab(tab) {
        const btnPra = document.getElementById('tab-prabayar');
        const btnPasca = document.getElementById('tab-pascabayar');
        const contentPra = document.getElementById('content-prabayar');
        const contentPasca = document.getElementById('content-pascabayar');

        if(tab === 'prabayar') {
            btnPra.className = 'flex-1 py-3 rounded-lg font-bold text-sm transition bg-blue-600 text-white shadow-md';
            btnPasca.className = 'flex-1 py-3 rounded-lg font-bold text-sm text-gray-500 hover:bg-gray-50 transition';
            contentPra.classList.remove('hidden');
            contentPasca.classList.add('hidden');
        } else {
            btnPasca.className = 'flex-1 py-3 rounded-lg font-bold text-sm transition bg-red-600 text-white shadow-md';
            btnPra.className = 'flex-1 py-3 rounded-lg font-bold text-sm text-gray-500 hover:bg-gray-50 transition';
            contentPra.classList.add('hidden');
            contentPasca.classList.remove('hidden');
            handlePascaSkuChange(); 
        }
    }

    // --- 4. LOGIKA PASCABAYAR (PBB/PLN/DLL) ---
    let currentPbbSku = '';

    function handlePascaSkuChange() {
        const selectedSku = document.getElementById('pasca_sku').value;
        const citySelectionDiv = document.getElementById('dynamic_city_selection');
        const pascaNoInput = document.getElementById('pasca_no');
        const testCaseInfo = document.getElementById('test_case_info');

        citySelectionDiv.classList.add('hidden');
        pascaNoInput.placeholder = "Contoh: 5300xxxx";
        testCaseInfo.style.display = 'block';
        currentPbbSku = ''; 

        if (selectedSku === 'pbb-city') {
            citySelectionDiv.classList.remove('hidden');
            loadPbbCities('');
            pascaNoInput.placeholder = "Masukkan NOP PBB";
            testCaseInfo.innerHTML = '*Test Case PBB: 329801092375999991';
        } 
        else if (selectedSku === 'internet') testCaseInfo.innerHTML = '*Test Case Internet: 7391601001';
        else if (selectedSku === 'pln') testCaseInfo.innerHTML = '*Test Case PLN: 530000000001';
        else testCaseInfo.innerHTML = '*Pastikan nomor pelanggan benar.';

        resetPascaUI();
    }

    function resetPascaUI() {
        document.getElementById('pasca_result').classList.add('hidden');
        document.getElementById('pasca_empty').classList.remove('hidden');
    }

    function resetPasca() {
        resetPascaUI();
        document.getElementById('pasca_no').focus();
    }

    // --- 5. LOGIKA PENCARIAN KOTA PBB ---
    function loadPbbCities(query) {
        const resultsDiv = document.getElementById('pbb_search_results');
        resultsDiv.innerHTML = '<div class="p-2 text-gray-500">Memuat...</div>';
        resultsDiv.classList.remove('hidden');
        
        let url = '{{ route("admin.ppob.get-pbb-cities") }}';
        if (query) url += `?q=${query}`;

        fetch(url)
        .then(res => res.json())
        .then(data => {
            resultsDiv.innerHTML = '';
            if (data.success && data.cities && data.cities.length > 0) {
                renderPbbResults(data.cities);
            } else {
                resultsDiv.innerHTML = '<div class="p-2 text-sm text-red-500">Kota tidak ditemukan.</div>';
            }
        })
        .catch(err => {
            console.error(err);
            resultsDiv.innerHTML = '<div class="p-2 text-sm text-red-500">Error.</div>';
        });
    }

    function filterPbbCities(query) {
        if (query.length > 1) loadPbbCities(query);
        else document.getElementById('pbb_search_results').classList.add('hidden');
        
        document.getElementById('pbb_city_sku_selected').value = '';
        currentPbbSku = '';
    }

    function renderPbbResults(cities) {
        const resultsDiv = document.getElementById('pbb_search_results');
        resultsDiv.innerHTML = '';
        resultsDiv.classList.remove('hidden');

        cities.forEach(city => {
            let item = document.createElement('div');
            item.className = 'p-2 text-sm cursor-pointer hover:bg-red-100/70';
            item.innerText = city.name;
            item.onclick = function() {
                document.getElementById('pbb_city_search').value = city.name;
                document.getElementById('pbb_city_sku_selected').value = city.sku;
                currentPbbSku = city.sku;
                resultsDiv.classList.add('hidden');
            };
            resultsDiv.appendChild(item);
        });
    }

   // --- 6. LOGIKA CEK TAGIHAN (SMART FINDER EDITION) ---
    function cekTagihan() {
        let sku = document.getElementById('pasca_sku').value;
        const no = document.getElementById('pasca_no').value;
        
        // 1. Validasi Input
        const customerWaPasca = document.getElementById('input_customer_wa_pasca').value;
        if (customerWaPasca.length < 9) {
            alert('Mohon isi Nomor WA Pembeli (min 9 digit).');
            document.getElementById('input_customer_wa_pasca').focus();
            return;
        }

        if (sku === 'pbb-city') sku = currentPbbSku;
        if (sku === '' || (document.getElementById('pasca_sku').value === 'pbb-city' && sku === '')) {
            alert('Pilih Kota PBB terlebih dahulu.');
            return;
        }
        if (no.length < 5) { alert('Nomor pelanggan tidak valid'); return; }

        // 2. UI Loading
        document.getElementById('pasca_empty').classList.add('hidden');
        document.getElementById('pasca_result').classList.add('hidden');
        document.getElementById('pasca_loading').classList.remove('hidden');
        document.getElementById('btn-cek-tagihan').disabled = true;

        // Set WA di Hidden Input
        document.getElementById('pay_customer_wa').value = customerWaPasca;

        // 3. Request ke Server
        fetch('{{ route("ppob.check.bill") }}', { 
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            body: JSON.stringify({ 
                sku: sku, 
                customer_no: no,
                ref_id: 'INQ-' + Date.now() + Math.floor(Math.random() * 1000)
            })
        })
        .then(res => res.json())
        .then(data => {
            document.getElementById('pasca_loading').classList.add('hidden');
            document.getElementById('btn-cek-tagihan').disabled = false;

            // --- DEBUGGER: TAMPILKAN STRUKTUR JSON DI CONSOLE ---
            console.group("DEBUG INQUIRY RESPONSE");
            console.log("Raw JSON:", data);
            console.groupEnd();

            // --- SMART FINDER FUNCTION ---
            // Fungsi ini akan mencari key 'buyer_sku_code' sedalam apapun posisinya
            function findValueByKey(obj, keyToFind) {
                if (obj === null || typeof obj !== 'object') return null;
                if (obj.hasOwnProperty(keyToFind)) return obj[keyToFind];
                for (let key in obj) {
                    if (obj.hasOwnProperty(key) && typeof obj[key] === 'object') {
                        let result = findValueByKey(obj[key], keyToFind);
                        if (result) return result;
                    }
                }
                return null;
            }

            // Cari data penting menggunakan Smart Finder
            let foundSku = findValueByKey(data, 'buyer_sku_code');
            let foundRefId = findValueByKey(data, 'ref_id');
            let foundPrice = findValueByKey(data, 'price') || findValueByKey(data, 'selling_price');
            let foundName = findValueByKey(data, 'customer_name') || findValueByKey(data, 'name');
            let foundSn = findValueByKey(data, 'sn'); // Kadang status ada di sini
            let foundStatus = findValueByKey(data, 'status');
            let foundRc = findValueByKey(data, 'rc');

            // Normalisasi Status
            let status = foundStatus ? foundStatus.toLowerCase() : '';
            let rc = foundRc ? String(foundRc) : '';

            // --- JIKA SUKSES ---
            if(status === 'sukses' || status === 'success' || rc === '00') {
                document.getElementById('pasca_result').classList.remove('hidden');

                // A. Mapping Tampilan
                document.getElementById('res_nama').innerText = foundName || '-';
                document.getElementById('res_id').innerText = no;

                // Cari Periode & Lembar (Smart Search juga di 'desc')
                let foundDesc = findValueByKey(data, 'desc');
                let periodeStr = findValueByKey(data, 'periode'); 
                
                // Fallback periode ke dalam detail jika root kosong
                if((!periodeStr || periodeStr==='-') && foundDesc && foundDesc.detail && foundDesc.detail[0]) {
                    periodeStr = foundDesc.detail[0].periode;
                }
                
                document.getElementById('res_periode').innerText = formatPeriodeID(periodeStr);
                document.getElementById('res_lembar').innerText = (foundDesc && foundDesc.lembar_tagihan) ? foundDesc.lembar_tagihan + ' Lembar' : '1 Lembar';

                // B. Hitung Harga
                let tagihanAsli = parseInt(foundPrice || 0);
                let adminBank = parseInt(findValueByKey(data, 'admin') || 0);
                let marginAgen = 2500;
                let totalBayar = tagihanAsli + marginAgen;

                document.getElementById('res_modal').innerText = 'Rp ' + tagihanAsli.toLocaleString('id-ID');
                document.getElementById('res_admin').innerText = 'Rp ' + adminBank.toLocaleString('id-ID');
                document.getElementById('res_total').innerText = 'Rp ' + totalBayar.toLocaleString('id-ID');

                // --- C. FORM HIDDEN (BAGIAN KRITIS) ---
                
                // 1. SKU FINAL: Gunakan hasil pencarian Smart Finder
                let finalSku = (foundSku && foundSku !== 'pln') ? foundSku : sku;
                
                // Debugging Log untuk memastikan
                console.log(`%c[SKU FOUND] API: ${foundSku} | FINAL: ${finalSku}`, "color: green; font-weight: bold");

                let inputSku = document.getElementById('pay_sku_pasca_final');
                if(inputSku) inputSku.value = finalSku;
                else console.error("FATAL: Input ID 'pay_sku_pasca_final' tidak ditemukan!");

                document.getElementById('pay_ref_id').value = foundRefId; 
                document.getElementById('pay_price').value = totalBayar; 
                document.getElementById('pay_no').value = no;

                // D. Render Detail
                // Gunakan object 'd' (fallback root) untuk render fungsi lama
                let d = data.data || data; 
                renderDetailTeknis(sku, d, periodeStr);
                renderItemList(sku, d);

            } else {
                // --- JIKA GAGAL ---
                let foundMsg = findValueByKey(data, 'message') || 'Tagihan tidak ditemukan.';
                const emptyState = document.getElementById('pasca_empty');
                emptyState.innerHTML = `<div class="text-center text-red-500 animate-pulse"><p class="font-bold">Gagal!</p><p>${foundMsg}</p><button onclick="resetPasca()" class="underline mt-2">Coba Lagi</button></div>`;
                emptyState.classList.remove('hidden');
                
                console.error("INQUIRY FAILED:", foundMsg);
            }
        })
        .catch(err => {
            console.error("FETCH ERROR:", err);
            alert('Gagal menghubungi server: ' + err.message);
            document.getElementById('pasca_loading').classList.add('hidden');
            document.getElementById('btn-cek-tagihan').disabled = false;
        });
    }

    // Helper Render Detail
    function renderDetailTeknis(sku, d, periodeStr) {
        let alamat = '-';
        let infoTeknis = '-';
        
        if(d.desc) {
            alamat = d.desc.alamat || d.desc.kab_kota || '-';
            if(sku === 'pln' || d.desc.tarif) infoTeknis = `${d.desc.tarif || '-'} / ${d.desc.daya || '-'} VA`;
            else if(sku === 'bpjs') infoTeknis = (d.desc.jumlah_peserta || '1') + ' Peserta';
            else if(sku === 'pbb' || sku === 'pbb-city') infoTeknis = `LT: ${d.desc.luas_tanah || 0} / LB: ${d.desc.luas_gedung || 0}`;
        }

        const rowDetail = document.getElementById('row_detail_teknis');
        if(alamat !== '-' || infoTeknis !== '-') {
            rowDetail.classList.remove('hidden');
            document.getElementById('res_alamat').innerHTML = alamat;
            document.getElementById('res_tarif').innerText = infoTeknis;
        } else {
            rowDetail.classList.add('hidden');
        }
    }

    function renderItemList(sku, d) {
        const detailList = document.getElementById('res_detail_list');
        const detailContainer = document.getElementById('res_detail_container');
        detailList.innerHTML = '';
        let hasDetails = false;

        // Jika ada array detail
        if (d.desc && d.desc.detail && Array.isArray(d.desc.detail)) {
            hasDetails = true;
            d.desc.detail.forEach(item => {
                let total = parseInt(item.nilai_tagihan||0) + parseInt(item.admin||0) + parseInt(item.denda||0);
                detailList.insertAdjacentHTML('beforeend', `
                    <div class="flex justify-between border-b border-gray-100 pb-1 mb-1 text-xs">
                        <span>${item.periode || 'Tagihan'}</span>
                        <span class="font-bold">Rp ${total.toLocaleString('id-ID')}</span>
                    </div>
                `);
            });
        } 
        // Jika detail flat (Samsat/PBB)
        else if (d.desc && (d.desc.biaya_pokok_pkb || d.desc.tahun_pajak)) {
             hasDetails = true;
             let label = (sku === 'pbb' || sku === 'pbb-city') ? 'Pokok Pajak' : 'Pokok PKB';
             let val = (sku === 'pbb' || sku === 'pbb-city') ? (parseInt(d.price||0)-parseInt(d.admin||0)) : d.desc.biaya_pokok_pkb;
             detailList.insertAdjacentHTML('beforeend', `
                <div class="flex justify-between border-b border-gray-100 pb-1 mb-1 text-xs">
                    <span>${label}</span>
                    <span class="font-bold">Rp ${parseInt(val||0).toLocaleString('id-ID')}</span>
                </div>
            `);
        }

        if(hasDetails) detailContainer.classList.remove('hidden');
        else detailContainer.classList.add('hidden');
    }

    // --- 7. LOGIKA PRABAYAR (PULSA/DATA) ---
    function detectOperator() {
        let number = document.getElementById('input_customer_no').value;
        let prefix = number.substring(0, 4);
        let brand = null;
        let brandName = '-';
        let logoFile = '';

        if (/^08(11|12|13|21|22|23|51|52|53)/.test(prefix)) { brand = 'telkomsel'; brandName = 'Telkomsel'; logoFile = 'telkomsel.png'; }
        else if (/^08(14|15|16|55|56|57|58)/.test(prefix)) { brand = 'indosat'; brandName = 'Indosat Ooredoo'; logoFile = 'indosat.png'; }
        else if (/^08(17|18|19|59|77|78)/.test(prefix)) { brand = 'xl'; brandName = 'XL Axiata'; logoFile = 'xl.png'; }
        else if (/^08(31|32|33|38)/.test(prefix)) { brand = 'axis'; brandName = 'AXIS'; logoFile = 'axis.png'; }
        else if (/^08(95|96|97|98|99)/.test(prefix)) { brand = 'tri'; brandName = 'Tri (3)'; logoFile = 'tri.png'; }
        else if (/^08(81|82|83|84|85|86|87|88|89)/.test(prefix)) { brand = 'smartfren'; brandName = 'Smartfren'; logoFile = 'smartfren.png'; }
        else if (!number.startsWith('08') && number.length >= 6) { brand = 'pln'; brandName = 'PLN / Token'; logoFile = 'pln.png'; }

        if (brand) {
            showOperatorBadge(brandName, logoBasePath + logoFile);
            filterTableByBrand(brand);
        } else {
            hideOperatorBadge();
        }
    }

    function filterTableByBrand(brand) {
        let rows = document.querySelectorAll('.product-row');
        let hasResult = false;
        document.getElementById('instruction_alert').classList.add('hidden');
        document.getElementById('product_container').classList.remove('hidden');

        rows.forEach(row => {
            let rowBrand = row.getAttribute('data-brand');
            let rowCategory = row.getAttribute('data-category');
            let match = (brand === 'pln') ? (rowBrand.includes('pln') || rowCategory.includes('token')) : rowBrand.includes(brand);
            
            if (match) { row.classList.remove('hidden'); hasResult = true; }
            else row.classList.add('hidden');
        });

        if (!hasResult) document.getElementById('no_result').classList.remove('hidden');
        else document.getElementById('no_result').classList.add('hidden');
    }

    function showOperatorBadge(name, logoUrl) {
        let badge = document.getElementById('operator_badge');
        document.getElementById('operator_name').innerText = name;
        document.getElementById('operator_logo').src = logoUrl;
        badge.classList.remove('hidden');
        setTimeout(() => { badge.classList.add('scale-100', 'opacity-100'); }, 10);
    }

    function hideOperatorBadge() {
        let badge = document.getElementById('operator_badge');
        badge.classList.remove('scale-100', 'opacity-100');
        setTimeout(() => { badge.classList.add('hidden'); }, 200);
    }

    function filterTableManual() {
        let keyword = document.getElementById('search_product').value.toLowerCase();
        let rows = document.querySelectorAll('.product-row');
        document.getElementById('instruction_alert').classList.add('hidden');
        document.getElementById('product_container').classList.remove('hidden');
        rows.forEach(row => {
            if (row.getAttribute('data-name').includes(keyword)) row.classList.remove('hidden');
            else row.classList.add('hidden');
        });
    }

    // --- 8. KONFIRMASI PRABAYAR & MODAL ---
    function confirmTransaction(sku, name, modal, jual) {
        const no = document.getElementById('input_customer_no').value;
        const wa = document.getElementById('input_customer_wa_pra').value;

        if (wa.length < 9 || no.length < 6) { 
            alert('Mohon lengkapi Nomor Tujuan dan WA Pembeli.');
            document.getElementById('input_customer_wa_pra').focus();
            return;
        }
        
        document.getElementById('modal_no').innerText = no;
        document.getElementById('modal_product').innerText = name;
        document.getElementById('modal_jual').innerText = 'Rp ' + parseInt(jual).toLocaleString('id-ID');
        
        document.getElementById('form_sku').value = sku;
        document.getElementById('form_no').value = no;
        document.getElementById('form_customer_wa').value = wa;

        document.getElementById('confirmModal').classList.remove('hidden');
        setTimeout(() => {
            document.getElementById('modal_content').classList.remove('scale-95', 'opacity-0');
            document.getElementById('modal_content').classList.add('scale-100', 'opacity-100');
        }, 50);
    }

    function closeModal() {
        document.getElementById('modal_content').classList.remove('scale-100', 'opacity-100');
        document.getElementById('modal_content').classList.add('scale-95', 'opacity-0');
        setTimeout(() => { document.getElementById('confirmModal').classList.add('hidden'); }, 200);
    }

    // --- 9. EVENT LISTENER GLOBAL (DOM READY) ---
    document.addEventListener('DOMContentLoaded', function() {
        // Handle Submit Button Loading State
        function disableSubmitButton(form) {
            if (!form) return;
            const button = form.querySelector('button[type="submit"]');
            if (!button) return;

            form.addEventListener('submit', function(e) {
                if (form.hasSubmitted) { e.preventDefault(); return; }
                form.hasSubmitted = true;
                button.disabled = true;
                button.classList.add('opacity-70', 'cursor-not-allowed');
                button.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Memproses...';
            });
        }

        const formPayPra = document.getElementById('form-pay-pra');
        const formPayPasca = document.getElementById('form-pay-pasca');
        
        disableSubmitButton(formPayPra);
        disableSubmitButton(formPayPasca);
    });

</script>
@endpush