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

    {{-- Notifikasi Sukses/Gagal --}}
    @if(session('success'))
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded shadow-sm flex items-center gap-2 animate-fade-in-down">
            <i class="fas fa-check-circle text-xl"></i>
            <div>
                <p class="font-bold">Transaksi Berhasil!</p>
                <p>{{ session('success') }}</p>
            </div>
        </div>
    @endif
    
    @if(session('error'))
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded shadow-sm flex items-center gap-2 animate-fade-in-down">
            <i class="fas fa-exclamation-circle text-xl"></i>
            <div>
                <p class="font-bold">Transaksi Gagal!</p>
                <p>{{ session('error') }}</p>
            </div>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        {{-- KOLOM KIRI: FORM INPUT NOMOR --}}
        <div class="lg:col-span-1">
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200 sticky top-24">
                <h3 class="font-bold text-gray-800 mb-4 border-b pb-2 flex items-center gap-2">
                    <span class="bg-blue-600 text-white w-6 h-6 rounded-full flex items-center justify-center text-xs">1</span> 
                    Input Nomor Tujuan
                </h3>
                
                <div class="mb-4 relative">
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Nomor HP / ID Pelanggan / No. Meter</label>
                    
                    <div class="relative group">
                        <input type="number" id="input_customer_no" 
                               class="w-full pl-4 pr-12 py-3.5 rounded-xl border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-xl font-bold tracking-wide transition placeholder-gray-300"
                               placeholder="08xxxxxxxxxx" onkeyup="detectOperator()">
                        
                        {{-- Loading Icon --}}
                        <div id="loading_detect" class="absolute right-4 top-1/2 transform -translate-y-1/2 hidden">
                            <i class="fas fa-circle-notch fa-spin text-gray-400"></i>
                        </div>
                    </div>

                    {{-- Operator Badge (Hasil Deteksi) --}}
                    <div id="operator_badge" class="mt-3 hidden transition-all duration-300 transform scale-95 opacity-0">
                        <div class="flex items-center gap-2 bg-gray-50 border border-gray-200 p-2 rounded-lg shadow-sm">
                            {{-- Image Tag diperbaiki untuk handle error --}}
                            <img id="operator_logo" src="" 
                                 onerror="this.src='https://via.placeholder.com/50?text=IMG'"
                                 class="w-10 h-10 object-contain rounded bg-white p-1 border border-gray-100">
                            <div>
                                <p class="text-[10px] text-gray-400 font-bold uppercase">Terdeteksi:</p>
                                <p class="text-sm font-bold text-gray-800 leading-tight" id="operator_name">-</p>
                            </div>
                            <span class="ml-auto text-green-500"><i class="fas fa-check-circle"></i></span>
                        </div>
                    </div>
                </div>

                <div class="bg-yellow-50 p-4 rounded-xl border border-yellow-100 text-xs text-yellow-800 leading-relaxed">
                    <i class="fas fa-lightbulb mr-1 text-yellow-600"></i> 
                    <strong>Tips Cerdas:</strong> Masukkan nomor HP, token listrik, atau ID e-wallet. Logo operator akan muncul otomatis sesuai data Anda.
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

                {{-- Alert jika belum input nomor --}}
                <div id="instruction_alert" class="flex flex-col items-center justify-center py-10 text-center text-gray-400 animate-pulse">
                    <i class="fas fa-keyboard text-5xl mb-3 text-gray-200"></i>
                    <p class="font-medium">Silakan masukkan nomor tujuan terlebih dahulu.</p>
                    <p class="text-xs mt-1">Produk akan muncul otomatis.</p>
                </div>

                {{-- Tabel Produk --}}
                <div id="product_container" class="hidden overflow-hidden rounded-xl border border-gray-100">
                    <table class="w-full text-sm text-left">
                        <thead class="bg-gray-50 text-gray-500 uppercase text-[10px] font-bold tracking-wider">
                            <tr>
                                <th class="px-4 py-3">Produk</th>
                                <th class="px-4 py-3 text-center">Brand</th>
                                <th class="px-4 py-3 text-right">Harga</th>
                                <th class="px-4 py-3 text-center">Action</th>
                            </tr>
                        </thead>
                        {{-- UPDATE BAGIAN TBODY TABLE INI --}}
<tbody class="divide-y divide-gray-100" id="product_table_body">
    @foreach($products as $product)
        @php
            $modal = $product->modal_agen;
            // Logika harga jual default jika null
            $jual = $product->harga_jual_agen ?? ($modal + 2000);
        @endphp

        {{-- PERUBAHAN PENTING DI SINI: --}}
        {{-- 1. Tambahkan data-price untuk sorting --}}
        {{-- 2. Tambahkan data-type untuk pencarian luas --}}
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
                {{-- Badge warna-warni sesuai kategori (Opsional) --}}
                <span class="px-2 py-1 rounded text-[10px] font-bold bg-gray-100 text-gray-600 uppercase">
                    {{ $product->brand }}
                </span>
            </td>
            <td class="px-4 py-3 text-right">
                <div class="font-extrabold text-green-700">Rp {{ number_format($jual, 0, ',', '.') }}</div>
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
                    
                    {{-- Pesan Tidak Ada Hasil --}}
                    <div id="no_result" class="hidden py-8 text-center text-gray-500 text-sm">
                        Tidak ada produk yang cocok dengan operator ini.
                    </div>
                </div>

                {{-- Pagination --}}
                <div id="pagination_links" class="mt-4">
                    {{ $products->appends(request()->query())->links() }}
                </div>
            </div>
        </div>

    </div>
</div>

{{-- MODAL KONFIRMASI (Sama seperti sebelumnya) --}}
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
                    <span class="text-[10px] text-green-500 font-bold uppercase block">Total Bayar</span>
                    <span class="font-bold text-green-700 text-xl" id="modal_jual">Rp 0</span>
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
    // --- KONFIGURASI PATH LOGO (PENTING) ---
    // Pastikan Anda sudah menjalankan: php artisan storage:link
    // Path ini mengarah ke folder public/storage/logo-ppob/
    const logoBasePath = "{{ asset('public/storage/logo-ppob') }}/";

    // --- LOGIKA DETEKSI OPERATOR (Disesuaikan dengan Nama File Anda) ---
    function detectOperator() {
        let number = document.getElementById('input_customer_no').value;
        // Ambil 4 digit pertama
        let prefix = number.substring(0, 4); 
        let brand = null;
        let brandName = '-';
        let logoFile = ''; // Nama file sesuai folder Anda

        // Reset UI jika input pendek
        if (number.length < 4) {
            hideOperatorBadge();
            showInstruction();
            return;
        }

        // 1. DETEKSI SELULER (Mapping Prefix ke Nama File)
        if (/^08(11|12|13|21|22|23|51|52|53)/.test(prefix)) { 
            brand = 'telkomsel'; brandName = 'Telkomsel'; logoFile = 'telkomsel.png'; 
        }
        else if (/^08(14|15|16|55|56|57|58)/.test(prefix)) { 
            brand = 'indosat'; brandName = 'Indosat Ooredoo'; logoFile = 'indosat.png'; 
        }
        else if (/^08(17|18|19|59|77|78)/.test(prefix)) { 
            brand = 'xl'; brandName = 'XL Axiata'; logoFile = 'xl.png'; 
        }
        else if (/^08(31|32|33|38)/.test(prefix)) { 
            brand = 'axis'; brandName = 'AXIS'; logoFile = 'axis.png'; 
        }
        else if (/^08(95|96|97|98|99)/.test(prefix)) { 
            brand = 'tri'; brandName = 'Tri (3)'; logoFile = 'tri.png'; 
        }
        else if (/^08(81|82|83|84|85|86|87|88|89)/.test(prefix)) { 
            brand = 'smartfren'; brandName = 'Smartfren'; logoFile = 'smartfren.png'; 
        }
        
        // 2. DETEKSI PLN (Token Listrik)
        // Token PLN biasanya 11 digit (No Meter) atau 12 digit (ID Pel)
        // Kita asumsikan jika tidak diawali 08 dan panjang >= 6 maka PLN
        else if (!number.startsWith('08') && number.length >= 6) { 
            brand = 'pln'; brandName = 'PLN / Token'; logoFile = 'pln.png';
        }

        // 3. Update UI jika terdeteksi
        if (brand) {
            // Gabungkan Base Path dengan Nama File
            let fullLogoUrl = logoBasePath + logoFile;
            showOperatorBadge(brandName, fullLogoUrl);
            filterTableByBrand(brand);
        } else {
            // Jika tidak match pattern apapun, sembunyikan badge tapi user bisa cari manual
            hideOperatorBadge();
        }
    }

    function showOperatorBadge(name, logoUrl) {
        let badge = document.getElementById('operator_badge');
        document.getElementById('operator_name').innerText = name;
        document.getElementById('operator_logo').src = logoUrl;
        
        badge.classList.remove('hidden');
        // Efek animasi halus
        setTimeout(() => {
            badge.classList.remove('scale-95', 'opacity-0');
            badge.classList.add('scale-100', 'opacity-100');
        }, 50);
    }

    function hideOperatorBadge() {
        let badge = document.getElementById('operator_badge');
        badge.classList.add('scale-95', 'opacity-0');
        badge.classList.remove('scale-100', 'opacity-100');
        setTimeout(() => {
            badge.classList.add('hidden');
        }, 300);
    }

    function showInstruction() {
        document.getElementById('instruction_alert').classList.remove('hidden');
        document.getElementById('product_container').classList.add('hidden');
        document.getElementById('pagination_links').classList.add('hidden');
    }

    // --- PERBAIKAN LOGIKA FILTER & SORTING ---
    function filterTableByBrand(brand) {
        let rows = Array.from(document.querySelectorAll('.product-row')); // Ubah ke Array agar bisa di-sort
        let tbody = document.getElementById('product_table_body');
        let hasResult = false;

        document.getElementById('instruction_alert').classList.add('hidden');
        document.getElementById('product_container').classList.remove('hidden');
        document.getElementById('pagination_links').classList.add('hidden'); 

        // 1. FILTER DULU
        // Kita simpan row yang cocok ke dalam array sementara
        let matchedRows = [];

        rows.forEach(row => {
            let rowBrand = row.getAttribute('data-brand'); 
            let rowName = row.getAttribute('data-name');
            let rowCategory = row.getAttribute('data-category');
            
            let match = false;
            
            // Logika Pencarian yang Diperluas
            // Agar Aktivasi, Data, & Pulsa masuk semua
            if (brand === 'pln') {
                if (rowBrand.includes('pln') || rowCategory.includes('token') || rowCategory.includes('listrik')) match = true;
            } else {
                // Cek jika Brand mengandung kata kunci (misal: indosat)
                // ATAU nama produk mengandung kata kunci (untuk jaga-jaga jika brand tidak lengkap)
                if (rowBrand.includes(brand) || rowName.includes(brand)) match = true;
            }

            if (match) {
                row.classList.remove('hidden');
                matchedRows.push(row); // Masukkan ke daftar yang cocok
                hasResult = true;
            } else {
                row.classList.add('hidden');
            }
        });

        // 2. SORTING (Termurah ke Termahal)
        if (hasResult) {
            matchedRows.sort((a, b) => {
                let priceA = parseInt(a.getAttribute('data-price'));
                let priceB = parseInt(b.getAttribute('data-price'));
                return priceA - priceB; // Ascending (Kecil ke Besar)
            });

            // 3. RE-APPEND (Susun ulang HTML berdasarkan urutan baru)
            // Teknik ini memindahkan elemen HTML ke urutan yang benar
            matchedRows.forEach(row => {
                tbody.appendChild(row);
            });
        }

        // Tampilkan pesan jika kosong
        const noResultEl = document.getElementById('no_result');
        if (!hasResult) {
            noResultEl.classList.remove('hidden');
            noResultEl.innerText = "Produk untuk " + brand + " tidak ditemukan.";
        } else {
            noResultEl.classList.add('hidden');
        }
    }

    // Update juga fungsi Manual Filter agar sorting tetap jalan
    function filterTableManual() {
        let keyword = document.getElementById('search_product').value.toLowerCase();
        let rows = Array.from(document.querySelectorAll('.product-row'));
        let tbody = document.getElementById('product_table_body');
        
        document.getElementById('instruction_alert').classList.add('hidden');
        document.getElementById('product_container').classList.remove('hidden');
        document.getElementById('pagination_links').classList.add('hidden');

        let matchedRows = [];

        rows.forEach(row => {
            let name = row.getAttribute('data-name');
            if (name.includes(keyword)) {
                row.classList.remove('hidden');
                matchedRows.push(row);
            } else {
                row.classList.add('hidden');
            }
        });

        // Sorting manual search juga
        matchedRows.sort((a, b) => {
            return parseInt(a.getAttribute('data-price')) - parseInt(b.getAttribute('data-price'));
        });

        matchedRows.forEach(row => tbody.appendChild(row));
    }

    // --- MODAL TRANSAKSI ---
    function confirmTransaction(sku, name, modal, jual) {
        let no = document.getElementById('input_customer_no').value;
        if(no.length < 4) { 
            alert('Mohon masukkan Nomor Tujuan dengan benar!');
            document.getElementById('input_customer_no').focus();
            return;
        }

        document.getElementById('modal_no').innerText = no;
        document.getElementById('modal_product').innerText = name;
        document.getElementById('modal_jual').innerText = 'Rp ' + parseInt(jual).toLocaleString('id-ID');
        
        document.getElementById('form_sku').value = sku;
        document.getElementById('form_no').value = no;

        let modalEl = document.getElementById('confirmModal');
        let contentEl = document.getElementById('modal_content');
        
        modalEl.classList.remove('hidden');
        setTimeout(() => {
            contentEl.classList.remove('scale-95', 'opacity-0');
            contentEl.classList.add('scale-100', 'opacity-100');
        }, 50);
    }

    function closeModal() {
        let modalEl = document.getElementById('confirmModal');
        let contentEl = document.getElementById('modal_content');

        contentEl.classList.remove('scale-100', 'opacity-100');
        contentEl.classList.add('scale-95', 'opacity-0');
        
        setTimeout(() => {
            modalEl.classList.add('hidden');
        }, 200);
    }
</script>
@endpush