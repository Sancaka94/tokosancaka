@extends('layouts.customer')

@section('title', 'Kasir Penjualan PPOB')

('content')
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
                            <input type="hidden" name="sku" id="pay_sku">
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
            <form action="{{ route('agent.transaction.store') }}" method="POST" class="p-4 bg-gray-50 border-t border-gray-100">
                @csrf
                <input type="hidden" name="sku" id="form_sku">
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
    let pbbCitiesCache = []; // Global cache for PBB cities

    // Inisialisasi saat window load
    window.onload = function() {
        // Cek jika tab Pascabayar aktif, langsung muat kota PBB
        if (document.getElementById('content-pascabayar').classList.contains('hidden') === false) {
            handlePascaSkuChange();
        }
        // Atur event listener untuk perubahan dropdown utama
        document.getElementById('pasca_sku').addEventListener('change', handlePascaSkuChange);

        // Tambahkan event listener untuk input pencarian PBB (dengan delay)
        const pbbSearchInput = document.getElementById('pbb_city_search');
        if (pbbSearchInput) {
            let searchTimeout;
            pbbSearchInput.addEventListener('keyup', function() {
                clearTimeout(searchTimeout);
                const query = this.value;
                searchTimeout = setTimeout(() => {
                    filterPbbCities(query);
                }, 300); // Tunggu 300ms setelah user berhenti mengetik
            });
            // FIX: Tambahkan listener untuk menyembunyikan hasil saat input kehilangan fokus
            pbbSearchInput.addEventListener('blur', function() {
                 setTimeout(() => {
                     document.getElementById('pbb_search_results').classList.add('hidden');
                 }, 200);
             });
            // FIX: Tambahkan listener untuk menampilkan hasil saat input mendapatkan fokus (jika cache penuh)
            pbbSearchInput.addEventListener('focus', function() {
                 if (document.getElementById('pbb_city_search').value.length > 0 && document.getElementById('pbb_search_results').innerHTML !== '') {
                      document.getElementById('pbb_search_results').classList.remove('hidden');
                 }
             });
        }
    };

    // --- HELPER: Formatter Periode (Carbon-like YYYYMM -> F Y) ---
    function formatPeriodeID(periodeStr) {
        if (!periodeStr) return '-';
        let str = periodeStr.toString().trim();
        
        // Format YYYYMM (Contoh: 202501 -> Januari 2025)
        if (/^\d{6}$/.test(str)) {
            let year = str.substring(0, 4);
            let month = parseInt(str.substring(4, 6));
            const months = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
            if (months[month]) return `${months[month]} ${year}`;
        }
        return str;
    }

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
            
            // PENTING: Panggil load PBB saat tab Pasca diaktifkan
            handlePascaSkuChange(); 
        }
    }

    function resetPasca() {
        document.getElementById('pasca_empty').innerHTML = `
            <i class="fas fa-file-invoice-dollar text-6xl mb-4 text-gray-200"></i>
            <p>Silakan lakukan cek tagihan terlebih dahulu.</p>
        `;
        document.getElementById('pasca_no').focus();
    }

    // --- LOGIKA PBB CITIES & DROPDOWN ---
    function handlePascaSkuChange() {
        const selectedSku = document.getElementById('pasca_sku').value;
        const citySelectionDiv = document.getElementById('dynamic_city_selection');
        const pascaNoInput = document.getElementById('pasca_no');
        const testCaseInfo = document.getElementById('test_case_info');

        // Reset state PBB
        citySelectionDiv.classList.add('hidden');
        pascaNoInput.placeholder = "Contoh: 5300xxxx";
        testCaseInfo.style.display = 'block';
        currentPbbSku = ''; // Reset SKU PBB yang dipilih

        // Logika PBB
        if (selectedSku === 'pbb-city') {
            citySelectionDiv.classList.remove('hidden');
            loadPbbCities(''); // Load semua kota saat pertama kali
            pascaNoInput.placeholder = "Masukkan NOP PBB (Nomor Objek Pajak)";
            testCaseInfo.innerHTML = '*Gunakan Test Case PBB: 329801092375999991';
        } 
        // Logika Internet
        else if (selectedSku === 'internet') {
            testCaseInfo.innerHTML = '*Gunakan Test Case Internet: 7391601001';
        }
        // Logika PLN
        else if (selectedSku === 'pln') {
            testCaseInfo.innerHTML = '*Gunakan Test Case PLN: 630000000001';
        }
        // Logika Default
        else {
            testCaseInfo.innerHTML = '*Untuk Samsat/PBB/Cicilan, pastikan format nomor sesuai.';
        }

        // Opsional: Reset hasil tagihan jika jenis produk berubah
        document.getElementById('pasca_result').classList.add('hidden');
        document.getElementById('pasca_empty').classList.remove('hidden');
    }

    function loadPbbCities(query) {
        const resultsDiv = document.getElementById('pbb_search_results');
        const searchInput = document.getElementById('pbb_city_search');
        resultsDiv.innerHTML = '<div class="p-2 text-gray-500">Memuat...</div>';
        resultsDiv.classList.remove('hidden');
        
        let url = '{{ route("admin.ppob.get-pbb-cities") }}';
        if (query) {
             url += `?q=${query}`;
        }

        fetch(url, { 
            method: 'GET',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
        })
        .then(res => res.json())
        .then(data => {
            resultsDiv.innerHTML = ''; // Clear loading
            
            if (data.success && data.cities && data.cities.length > 0) {
                pbbCitiesCache = data.cities;
                renderPbbResults(data.cities);
            } else {
                resultsDiv.innerHTML = '<div class="p-2 text-sm text-red-500">Kota tidak ditemukan.</div>';
            }
        })
        .catch(err => {
            console.error("Fetch City Error:", err);
            resultsDiv.innerHTML = '<div class="p-2 text-sm text-red-500">Error saat memuat data.</div>';
        });
    }

    function filterPbbCities(query) {
        const resultsDiv = document.getElementById('pbb_search_results');
        if (query.length > 1) {
             loadPbbCities(query); // Kirim query ke backend untuk pencarian DB
        } else {
             resultsDiv.classList.add('hidden');
        }
        document.getElementById('pbb_city_sku_selected').value = ''; // Reset SKU saat mulai mengetik
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
            item.setAttribute('data-sku', city.sku);
            item.setAttribute('data-name', city.name);
            
            // Logika Auto Select saat diklik
            item.onclick = function() {
                selectPbbCity(this.getAttribute('data-sku'), this.getAttribute('data-name'));
            };
            resultsDiv.appendChild(item);
        });
    }

    function selectPbbCity(sku, name) {
        document.getElementById('pbb_city_search').value = name;
        document.getElementById('pbb_city_sku_selected').value = sku;
        currentPbbSku = sku; // Simpan SKU final
        document.getElementById('pbb_search_results').classList.add('hidden');
    }
    
    // --- UTAMA: LOGIKA PASCABAYAR CEK TAGIHAN ---
    function cekTagihan() {
        // Ambil SKU yang benar (Jika PBB, ambil dari dropdown kota)
        let sku = document.getElementById('pasca_sku').value;
        const no = document.getElementById('pasca_no').value;

        if (sku === 'pbb-city') {
            sku = currentPbbSku; // Ambil SKU dari hasil pencarian yang dipilih
        }

        if(sku === 'pbb-city' || sku === '') {
            alert('Mohon pilih jenis tagihan atau kota terlebih dahulu.');
            return;
        }
        if(no.length < 5) { alert('Nomor pelanggan tidak valid'); return; }

        document.getElementById('pasca_empty').classList.add('hidden');
        document.getElementById('pasca_result').classList.add('hidden');
        document.getElementById('pasca_loading').classList.remove('hidden');
        document.getElementById('btn-cek-tagihan').disabled = true;

        fetch('{{ route("ppob.check.bill") }}', { 
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            body: JSON.stringify({ 
                sku: sku, // SKU yang sudah final (cimahi, pln, bpjs, dll)
                customer_no: no,
                ref_id: 'INQ-' + Date.now() + Math.floor(Math.random() * 1000)
            })
        })
        .then(res => res.json())
        .then(data => {
            document.getElementById('pasca_loading').classList.add('hidden');
            document.getElementById('btn-cek-tagihan').disabled = false;

            let d = data.data || data;
            let status = d.status ? d.status.toLowerCase() : '';
            let rc = d.rc ? String(d.rc) : '';

            if(status === 'sukses' || status === 'success' || rc === '00') {
                document.getElementById('pasca_result').classList.remove('hidden');
                
                // 1. Mapping Basic
                document.getElementById('res_nama').innerText = d.customer_name || d.name || '-';
                document.getElementById('res_id').innerText = d.customer_no || no;
                
                
                // 2. Mapping Desc & Details
                let periode = d.periode || '-';
                let lembar = '1 Lembar';
                let denda = 0;
                let alamat = '-';
                let infoTeknis = '-'; 
                let labelTeknis = 'Detail Teknis';

                if(d.desc) {
                    if(d.desc.lembar_tagihan) lembar = d.desc.lembar_tagihan + ' Lembar';
                    
                    if(d.desc.detail && Array.isArray(d.desc.detail) && d.desc.detail.length > 0) {
                        periode = d.desc.detail[0].periode || periode;
                        denda = d.desc.detail[0].denda || 0;
                    } else if (d.desc.periode) {
                        periode = d.desc.periode; 
                    }

                    // Ambil Alamat
                    alamat = d.desc.alamat || d.desc.kab_kota || d.desc.kelurahan || '-'; // Ambil alamat terbaik

                    // --- LOGIC DETIL TEKNIS PER PRODUK ---
                    if(sku === 'pln' || d.desc.tarif) {
                        let tarif = d.desc.tarif || '-';
                        let daya = d.desc.daya ? d.desc.daya + ' VA' : '';
                        infoTeknis = tarif + ' / ' + daya;
                        labelTeknis = 'Tarif / Daya';
                    } 
                    else if(sku === 'samsat' || d.desc.nomor_polisi) {
                        let nopol = d.desc.nomor_polisi || d.desc.no_pol || '-';
                        let merek = d.desc.merek_kb || '';
                        let model = d.desc.model_kb || '';
                        infoTeknis = `${nopol} (${merek} ${model})`;
                        labelTeknis = 'Kendaraan';
                    }
                    else if(sku === 'pbb' || sku === 'cimahi' || d.desc.luas_tanah) {
                        // Perbaikan Logic PBB
                        let lt = d.desc.luas_tanah || '0';
                        let lb = d.desc.luas_gedung || '0';
                        let tahun = d.desc.tahun_pajak || '-';
                        let kab_kota = d.desc.kab_kota || ''; 
                        let kec = d.desc.kecamatan || ''; 
                        let kel = d.desc.kelurahan || ''; 
                        
                        infoTeknis = `Tahun: ${tahun} / LT: ${lt}m² / LB: ${lb}m²`;
                        labelTeknis = `Tahun / Luas Tanah & Gedung (${kab_kota})`;
                        
                        // FIX: Gabungkan Alamat Lengkap
                        let addressParts = [d.desc.alamat, kel, kec].filter(p => p && p !== '-');
                        alamat = addressParts.length > 0 ? addressParts.join('<br>') : (kab_kota || '-');
                    }
                    else if(sku === 'bpjs') {
                        infoTeknis = (d.desc.jumlah_peserta || '1') + ' Peserta';
                        labelTeknis = 'Jumlah Peserta';
                    }
                    else if(sku === 'internet' || sku === 'telkom') {
                        infoTeknis = (d.desc.lembar_tagihan || '1') + ' Lembar Tagihan';
                        labelTeksis = 'Lembar Tagihan';
                    }
                }

                // Cek WA (Validasi sisi client untuk Pascabayar)
    const customerWaPasca = document.getElementById('input_customer_wa_pasca').value;
    if (customerWaPasca.length < 9) {
        // Jika WA kosong, tampilkan error dan JANGAN isi hidden field
        alert('Mohon isi Nomor WA Pembeli yang valid (minimal 9 digit).');
        document.getElementById('btn-cek-tagihan').disabled = false;
        document.getElementById('pasca_empty').classList.remove('hidden');
        document.getElementById('pasca_result').classList.add('hidden');
        document.getElementById('input_customer_wa_pasca').focus();
        return;
    }

  
                // Set field WA di form submit
                document.getElementById('pay_customer_wa').value = customerWaPasca; // <<< BARU
                // Render Periode & Lembar
                document.getElementById('res_periode').innerText = formatPeriodeID(periode);
                document.getElementById('res_lembar').innerText = lembar;

                // Render Detail Teknis (Alamat & Info)
                const rowDetail = document.getElementById('row_detail_teknis');
                if(alamat !== '-' || infoTeknis !== '-') {
                    rowDetail.classList.remove('hidden');
                    // FIX: Gunakan innerHTML untuk menampilkan <br>
                    document.getElementById('res_alamat').innerHTML = alamat;
                    document.getElementById('label_tarif').innerText = labelTeknis;
                    document.getElementById('res_tarif').innerText = infoTeknis;
                } else {
                    rowDetail.classList.add('hidden');
                }

                // 3. Mapping Harga
                let modalAgen = parseInt(d.price || d.selling_price || 0); 
                let adminBank = parseInt(d.admin || 0);
                let marginAgen = 2500;
                let hargaJual = modalAgen + marginAgen;

                document.getElementById('res_modal').innerText = 'Rp ' + modalAgen.toLocaleString('id-ID'); 
                document.getElementById('res_total').innerText = 'Rp ' + hargaJual.toLocaleString('id-ID'); 
                document.getElementById('res_admin').innerText = 'Rp ' + adminBank.toLocaleString('id-ID');
                document.getElementById('res_denda').innerText = 'Rp ' + parseInt(denda).toLocaleString('id-ID');
                
                // 4. Form Data
                document.getElementById('pay_sku').value = sku;
                document.getElementById('pay_no').value = d.customer_no;
                document.getElementById('pay_ref_id').value = d.ref_id; 
                document.getElementById('pay_price').value = hargaJual; 

                // 5. Mapping Detail Item
                const detailContainer = document.getElementById('res_detail_container');
                const detailList = document.getElementById('res_detail_list');
                
                detailList.innerHTML = '';
                let hasDetails = false;

                // Logic Populate List
                if (d.desc && d.desc.detail && Array.isArray(d.desc.detail)) {
                    hasDetails = true;
                    d.desc.detail.forEach(item => {
                        let totalItemTagihan = parseInt(item.nilai_tagihan || 0) + parseInt(item.admin || 0) + parseInt(item.denda || 0) + parseInt(item.biaya_lain || 0);

                        let meteran = (item.meter_awal && item.meter_akhir) ? 
                                `<span class="text-[10px] text-gray-500">Stand: ${item.meter_awal} - ${item.meter_akhir}</span>` : '';
                        
                        let biayaTambahan = [];
                        if(parseInt(item.denda || 0) > 0) biayaTambahan.push(`Denda: Rp ${parseInt(item.denda).toLocaleString('id-ID')}`);
                        if(parseInt(item.admin || 0) > 0) biayaTambahan.push(`Admin: Rp ${parseInt(item.admin).toLocaleString('id-ID')}`);
                        if(parseInt(item.biaya_lain || 0) > 0) biayaTambahan.push(`Lain: Rp ${parseInt(item.biaya_lain).toLocaleString('id-ID')}`);
                        
                        let additionalHtml = biayaTambahan.length > 0 ? `<div class="text-[10px] text-orange-500">${biayaTambahan.join(' | ')}</div>` : '';


                        let rowHtml = `
                            <div class="flex justify-between border-b border-gray-100 pb-1 mb-1 last:border-0 last:pb-0 last:mb-0">
                                <div>
                                    <span class="font-bold block">${item.periode || '-'} (${item.nilai_tagihan ? 'Rp ' + parseInt(item.nilai_tagihan).toLocaleString('id-ID') : 'Detail'})</span>
                                    ${meteran}
                                    ${additionalHtml}
                                </div>
                                <span class="font-bold text-base">Rp ${totalItemTagihan.toLocaleString('id-ID')}</span>
                            </div>
                        `;
                        detailList.insertAdjacentHTML('beforeend', rowHtml);
                    });
                } 
                // Jika SAMSAT/PBB/Flat Detail
                else if (d.desc && (d.desc.biaya_pokok_pkb || d.desc.biaya_admin_stnk || d.desc.tahun_pajak || d.desc.luas_tanah)) {
                    hasDetails = true;
                    const isPbb = sku === 'pbb' || sku === 'cimahi';

                    if(isPbb) {
                        const taxTotal = parseInt(d.price || 0) - parseInt(d.admin || 0);
                        let rowHtml = `
                            <div class="flex justify-between border-b border-gray-100 pb-1 mb-1">
                                <span class="text-gray-600">Pokok Pajak PBB (${d.desc.tahun_pajak})</span>
                                <span class="font-bold">Rp ${taxTotal.toLocaleString('id-ID')}</span>
                            </div>
                        `;
                        detailList.insertAdjacentHTML('beforeend', rowHtml);
                    } else if (sku === 'samsat') {
                         const fields = {
                            'Pokok PKB': d.desc.biaya_pokok_pkb,
                            'Pokok SWDKLLJ': d.desc.biaya_pokok_swd,
                            'Admin STNK': d.desc.biaya_admin_stnk,
                            'Denda PKB': d.desc.biaya_denda_pkb,
                            'Pajak Progresif': d.desc.biaya_pajak_progresif
                        };
                        
                        for (const [key, val] of Object.entries(fields)) {
                            if(parseInt(val || 0) > 0) {
                                let rowHtml = `
                                    <div class="flex justify-between border-b border-gray-100 pb-1 mb-1">
                                        <span class="text-gray-600">${key}</span>
                                        <span class="font-bold">Rp ${parseInt(val).toLocaleString('id-ID')}</span>
                                    </div>
                                `;
                                detailList.insertAdjacentHTML('beforeend', rowHtml);
                            }
                        }
                    } else {
                        // Untuk Multifinance / Lain-lain (jika ada item name di desc)
                        let itemName = d.desc.item_name || 'Item Tagihan';
                        let rowHtml = `
                            <div class="flex justify-between border-b border-gray-100 pb-1 mb-1">
                                <span class="text-gray-600">${itemName}</span>
                                <span class="font-bold">Rp ${parseInt(d.price || 0).toLocaleString('id-ID')}</span>
                            </div>
                        `;
                        detailList.insertAdjacentHTML('beforeend', rowHtml);
                    }
                }

                if(hasDetails) {
                    detailContainer.classList.remove('hidden');
                } else {
                    detailContainer.classList.add('hidden');
                }

            } else {
                const errorMsg = d.message || 'Tagihan tidak ditemukan / Gagal.';
                const emptyState = document.getElementById('pasca_empty');
                emptyState.innerHTML = `
                    <div class="text-center text-red-500 animate-pulse">
                        <i class="fas fa-times-circle text-5xl mb-3"></i>
                        <p class="font-bold text-lg">Gagal!</p>
                        <p class="sm">${errorMsg}</p>
                        <button onclick="resetPasca()" class="mt-4 text-sm text-gray-500 underline hover:text-gray-700">Coba Lagi</button>
                    </div>
                `;
                emptyState.classList.remove('hidden');
            }
        })
        .catch(err => {
            console.error(err);
            alert('Gagal menghubungi server');
            document.getElementById('pasca_loading').classList.add('hidden');
            document.getElementById('btn-cek-tagihan').disabled = false;
            document.getElementById('pasca_empty').classList.remove('hidden');
        });
    }

    // --- LOGIKA PRABAYAR (EXISTING) --
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
        let tbody = document.getElementById('product_table_body');
        let hasResult = false;

        document.getElementById('instruction_alert').classList.add('hidden');
        document.getElementById('product_container').classList.remove('hidden');
        
        let pagination = document.getElementById('pagination_links');
        if(pagination) pagination.classList.add('hidden');

        let matchedRows = [];

        rows.forEach(row => {
            let rowBrand = row.getAttribute('data-brand'); 
            let rowName = row.getAttribute('data-name');
            let rowCategory = row.getAttribute('data-category');
            
            let match = false;
            if (brand === 'pln') {
                if (rowBrand.includes('pln') || rowCategory.includes('token') || rowCategory.includes('listrik')) match = true;
            } else {
                if (rowBrand.includes(brand) || rowName.includes(brand)) match = true;
            }

            if (match) {
                row.classList.remove('hidden'); 
                matchedRows.push(row); 
                hasResult = true;
            } else {
                row.classList.add('hidden');
            }
        });

        if (hasResult) {
            matchedRows.sort((a, b) => {
                let priceA = parseInt(a.getAttribute('data-price'));
                let priceB = parseInt(b.getAttribute('data-price'));
                return priceA - priceB;
            });
            matchedRows.forEach(row => { tbody.appendChild(row); });
        }

        const noResultEl = document.getElementById('no_result');
        if (!hasResult) {
            noResultEl.classList.remove('hidden');
            noResultEl.innerText = "Produk " + brand + " sedang gangguan / tidak tersedia.";
        } else {
            noResultEl.classList.add('hidden');
        }
    }

    function showOperatorBadge(name, logoUrl) {
        let badge = document.getElementById('operator_badge');
        document.getElementById('operator_name').innerText = name;
        document.getElementById('operator_logo').src = logoUrl;
        badge.classList.remove('hidden');
        badge.classList.remove('scale-95', 'opacity-0');
        badge.classList.add('scale-100', 'opacity-100');
    }

    function hideOperatorBadge() {
        let badge = document.getElementById('operator_badge');
        badge.classList.add('scale-95', 'opacity-0');
        setTimeout(() => { badge.classList.add('hidden'); }, 200);
    }

    function showInstruction() {
        document.getElementById('instruction_alert').classList.remove('hidden');
        document.getElementById('product_container').classList.add('hidden');
    }

    function filterTableManual() {
        let keyword = document.getElementById('search_product').value.toLowerCase();
        let rows = document.querySelectorAll('.product-row');
        document.getElementById('instruction_alert').classList.add('hidden');
        document.getElementById('product_container').classList.remove('hidden');

        rows.forEach(row => {
            let name = row.getAttribute('data-name');
            if (name.includes(keyword)) row.classList.remove('hidden');
            else row.classList.add('hidden');
        });
    }

    // Perbaiki function confirmTransaction:
function confirmTransaction(sku, name, modal, jual) {
    // 1. Ambil Nilai (customerWa HARUS DIAMBIL di awal)
    const inputNoValue = document.getElementById('input_customer_no').value;
    // FIX 1: Pindahkan deklarasi di sini
    const customerWa = document.getElementById('input_customer_wa_pra').value; 

    // 2. Periksa WA (Validasi sisi client)
    if (customerWa.length < 9 || inputNoValue.length < 6) { 
        alert('Mohon lengkapi Nomor Tujuan dan Nomor WA Pembeli yang valid (minimal 9 digit).');
        document.getElementById('input_customer_wa_pra').focus();
        return;
    }
    
    // 3. Mapping Data ke Modal Display
    document.getElementById('modal_no').innerText = inputNoValue;
    document.getElementById('modal_product').innerText = name;
    document.getElementById('modal_jual').innerText = 'Rp ' + parseInt(jual).toLocaleString('id-ID');
    
    // 4. Mapping Data ke Hidden Form fields (untuk disubmit)
    document.getElementById('form_sku').value = sku;
    document.getElementById('form_no').value = inputNoValue;
    // FIX 2: Mapping WA ke hidden field
    document.getElementById('form_customer_wa').value = customerWa; 

    // 5. Tampilkan Modal
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

document.addEventListener('DOMContentLoaded', function() {
    // --- AMBIL FORM DAN TOMBOL ---
    const formPayPra = document.getElementById('form-pay-pra');
    
    // FIX 3: Hapus deklarasi ganda, gunakan querySelector untuk tombol submit
    const btnPayPra = formPayPra.querySelector('button[type="submit"]');
    
    const formPayPasca = document.getElementById('form-pay-pasca');
    // FIX 3: Hapus deklarasi ganda, gunakan querySelector untuk tombol submit
    const btnPayPasca = formPayPasca.querySelector('button[type="submit"]'); 

    function disableSubmitButton(form, button) {
        if (!form || !button) return;

        form.addEventListener('submit', function(e) {
            // Cek apakah form sudah pernah disubmit sebelumnya (flag custom)
            if (form.hasSubmitted) {
                e.preventDefault();
                return;
            }

            // Mencegah double submit
            form.hasSubmitted = true;
            
            // Nonaktifkan tombol
            button.disabled = true;
            button.classList.add('opacity-70', 'cursor-not-allowed');
            button.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Memproses...';
        });
    }

    // Terapkan fungsi pencegahan ke kedua form
    // PENTING: Periksa apakah elemen ditemukan sebelum memanggil disableSubmitButton
    if (formPayPra && btnPayPra) {
        disableSubmitButton(formPayPra, btnPayPra);
    }
    if (formPayPasca && btnPayPasca) {
        disableSubmitButton(formPayPasca, btnPayPasca);
    }
    
    // Khusus untuk Prabayar, kita harus menerapkan logika disable saat tombol PILIH di modal diklik
    // Tombol 'PROSES' di modal adalah tombol submit itu sendiri, jadi logika di atas sudah cukup.
});

</script>
@endpush