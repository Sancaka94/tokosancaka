@extends('layouts.customer')

@section('title', 'Kasir Penjualan PPOB')

@section('content')
<div class="max-w-7xl mx-auto space-y-6">

    {{-- Header --}}
    <div class="flex flex-col md:flex-row justify-between items-center gap-4 bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 flex items-center gap-2">
                <i class="fas fa-cash-register text-blue-600"></i> Kasir / Transaksi Offline
            </h1>
            <p class="text-sm text-gray-500">Layanan penjualan langsung untuk pelanggan yang datang ke lokasi Anda.</p>
        </div>
        <div class="bg-blue-50 px-5 py-3 rounded-xl text-right border border-blue-100">
            <p class="text-[10px] text-blue-600 font-bold uppercase tracking-wider">Saldo Aktif Anda</p>
            <p class="text-2xl font-extrabold text-blue-800">Rp {{ number_format(Auth::user()->saldo, 0, ',', '.') }}</p>
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

    {{-- KONTEN: PRABAYAR (PULSA/DATA/TOKEN) --}}
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
                                <p class="text-[10px] text-gray-400 font-bold uppercase">Terdeteksi:</p>
                                <p class="text-sm font-bold text-gray-800" id="operator_name">-</p>
                            </div>
                            <span class="ml-auto text-green-500"><i class="fas fa-check-circle"></i></span>
                        </div>
                    </div>
                </div>

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

    {{-- KONTEN: PASCABAYAR (TAGIHAN) --}}
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
                        <select id="pasca_sku" class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:ring-2 focus:ring-red-500 focus:border-red-500 bg-white">
                            <option value="pln">PLN Pascabayar</option>
                            <option value="bpjs">BPJS Kesehatan</option>
                            <option value="pdam">PDAM</option>
                            <option value="telkom">Telkom / Indihome</option>
                            <option value="pgn">Gas Negara</option>
                            <option value="multifinance">Multifinance / Cicilan</option>
                        </select>
                    </div>

                    {{-- Input Nomor --}}
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Nomor Pelanggan / ID</label>
                        <input type="number" id="pasca_no" 
                               class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:ring-2 focus:ring-red-500 font-bold text-gray-800 placeholder-gray-300"
                               placeholder="Contoh: 5300xxxx">
                    </div>

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

                {{-- State: Kosong --}}
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
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                            <div>
                                <p class="text-xs text-gray-500 uppercase">Nama Pelanggan</p>
                                <p class="font-bold text-gray-800 text-lg" id="res_nama">-</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 uppercase">ID Pelanggan</p>
                                <p class="font-bold text-gray-800" id="res_id">-</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 uppercase">Periode</p>
                                <p class="font-bold text-gray-800" id="res_periode">-</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 uppercase">Total Tagihan</p>
                                <p class="font-extrabold text-red-700 text-xl" id="res_total">-</p>
                            </div>
                        </div>
                        
                        {{-- Detail Tambahan --}}
                        <div class="mt-4 pt-4 border-t border-red-200 grid grid-cols-2 gap-4">
                            <div>
                                <p class="text-xs text-gray-500 uppercase">Admin Fee</p>
                                <p class="font-bold text-gray-700" id="res_admin">-</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 uppercase">Denda</p>
                                <p class="font-bold text-gray-700" id="res_denda">-</p>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end pt-4">
                        <form action="{{ route('agent.transaction.store') }}" method="POST" id="form-pay-pasca">
                            @csrf
                            {{-- Field Hidden untuk Data Pascabayar --}}
                            <input type="hidden" name="payment_type" value="pasca">
                            <input type="hidden" name="sku" id="pay_sku">
                            <input type="hidden" name="customer_no" id="pay_no">
                            <input type="hidden" name="ref_id" id="pay_ref_id"> {{-- PENTING: Ref ID dari Inquiry --}}
                            <input type="hidden" name="selling_price" id="pay_price">
                            
                            <button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-bold py-3 px-8 rounded-xl shadow-lg shadow-green-200 transition flex items-center gap-2">
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
                <h3 class="text-lg font-bold">Konfirmasi Transaksi</h3>
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
                    <span class="text-[10px] text-green-500 font-bold uppercase block">Modal Agen</span>
                    <span class="font-bold text-green-700 text-base" id="modal_jual">Rp 0</span>
                </div>
            </div>
            <form action="{{ route('agent.transaction.store') }}" method="POST" class="p-4 bg-gray-50 border-t border-gray-100">
                @csrf
                <input type="hidden" name="sku" id="form_sku">
                <input type="hidden" name="customer_no" id="form_no">
                <div class="flex gap-3">
                    <button type="button" onclick="closeModal()" class="flex-1 py-3 bg-white text-gray-700 font-bold rounded-xl border border-gray-300 hover:bg-gray-100 transition text-sm">Batal</button>
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

    // --- SWITCH TAB ---
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
        }
    }

    // --- LOGIKA PASCABAYAR (BARU) ---
    function cekTagihan() {
        const sku = document.getElementById('pasca_sku').value;
        const no = document.getElementById('pasca_no').value;

        if(no.length < 5) { alert('Nomor pelanggan tidak valid'); return; }

        // UI Loading
        document.getElementById('pasca_empty').classList.add('hidden');
        document.getElementById('pasca_result').classList.add('hidden');
        document.getElementById('pasca_loading').classList.remove('hidden');
        document.getElementById('btn-cek-tagihan').disabled = true;

        // AJAX Request (Pastikan route ini ada di web.php)
        // Gunakan route yang sama dengan index.blade.php jika memungkinkan, atau buat route khusus agent
        fetch('{{ route("ppob.check.bill") }}', { 
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            body: JSON.stringify({ 
                sku: sku, 
                customer_no: no,
                // Generate Ref ID Unik untuk Inquiry
                ref_id: 'INQ-' + Date.now() + Math.floor(Math.random() * 1000),
                testing: true // Sesuaikan dengan environment
            })
        })
        .then(res => res.json())
        .then(data => {
            document.getElementById('pasca_loading').classList.add('hidden');
            document.getElementById('btn-cek-tagihan').disabled = false;

            const d = data.data || data;

            if(d.status === 'Sukses' || d.rc === '00') {
                // Tampilkan Hasil
                document.getElementById('pasca_result').classList.remove('hidden');
                
                // Mapping Data
                document.getElementById('res_nama').innerText = d.customer_name || '-';
                document.getElementById('res_id').innerText = d.customer_no;
                
                // Format Harga
                let price = parseInt(d.selling_price || d.price || 0);
                let admin = parseInt(d.admin || 2500); 
                let total = price + admin; // Sesuaikan logic harga jual agen Anda

                document.getElementById('res_total').innerText = 'Rp ' + total.toLocaleString('id-ID');
                document.getElementById('res_admin').innerText = 'Rp ' + admin.toLocaleString('id-ID');
                
                // Isi Form Hidden untuk Pembayaran
                document.getElementById('pay_sku').value = sku;
                document.getElementById('pay_no').value = d.customer_no;
                document.getElementById('pay_ref_id').value = d.ref_id; // PENTING: ID Inquiry harus sama
                document.getElementById('pay_price').value = total;

            } else {
                alert(d.message || 'Tagihan tidak ditemukan');
                document.getElementById('pasca_empty').classList.remove('hidden');
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

    // --- LOGIKA PRABAYAR (EXISTING) ---
    function detectOperator() {
        let number = document.getElementById('input_customer_no').value;
        let prefix = number.substring(0, 4);
        let brand = null;
        let brandName = '-';
        let logoFile = '';

        if (number.length < 4) {
            hideOperatorBadge();
            showInstruction();
            return;
        }

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
        let rows = Array.from(document.querySelectorAll('.product-row'));
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

    function confirmTransaction(sku, name, modal, jual) {
        document.getElementById('modal_no').innerText = document.getElementById('input_customer_no').value;
        document.getElementById('modal_product').innerText = name;
        document.getElementById('modal_jual').innerText = 'Rp ' + parseInt(jual).toLocaleString('id-ID');
        document.getElementById('form_sku').value = sku;
        document.getElementById('form_no').value = document.getElementById('input_customer_no').value;
        
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
</script>
@endpush