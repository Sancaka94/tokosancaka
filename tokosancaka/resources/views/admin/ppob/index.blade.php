@extends('layouts.admin')
@section('title', 'Manajemen Produk PPOB')

{{-- Load SweetAlert2 --}}
@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
@endpush

@section('content')

{{-- Notifikasi --}}
@if(session('success'))
<div class="mb-4 bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded shadow-sm relative">
    <strong class="font-bold">Berhasil!</strong> <span class="block sm:inline">{{ session('success') }}</span>
</div>
@endif

@if(session('error'))
<div class="mb-4 bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded shadow-sm relative">
    <strong class="font-bold">Gagal!</strong> <span class="block sm:inline">{{ session('error') }}</span>
</div>
@endif

<div class="container mx-auto px-4 py-8">
    
    {{-- Header & Widget Saldo --}}
    <div class="flex flex-col xl:flex-row justify-between items-start xl:items-center mb-6 gap-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Daftar Produk PPOB</h1>
            <p class="text-sm text-gray-500 mt-1">Kelola harga, komisi pascabayar, dan limit transaksi.</p>
            
            <div class="mt-4 flex flex-wrap gap-2">
                {{-- Tombol Export --}}
                <div class="flex gap-1 mr-2">
                    <a href="{{ route('admin.ppob.export-excel', request()->all()) }}" target="_blank" class="bg-green-600 hover:bg-green-700 text-white px-3 py-2 rounded-lg text-xs font-bold shadow flex items-center gap-2">
                        <i class="fas fa-file-excel"></i> Excel
                    </a>
                    <a href="{{ route('admin.ppob.export-pdf', request()->all()) }}" target="_blank" class="bg-red-600 hover:bg-red-700 text-white px-3 py-2 rounded-lg text-xs font-bold shadow flex items-center gap-2">
                        <i class="fas fa-file-pdf"></i> PDF
                    </a>
                </div>

                {{-- Tombol Sync --}}
                <a href="{{ route('ppob.sync.prepaid') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-2 rounded-lg text-xs font-bold shadow flex items-center gap-2" onclick="return confirm('Sync Prabayar?')">
                    <i class="fas fa-sync"></i> Sync Prabayar
                </a>
                <a href="{{ route('ppob.sync.postpaid') }}" class="bg-indigo-600 hover:bg-indigo-700 text-white px-3 py-2 rounded-lg text-xs font-bold shadow flex items-center gap-2" onclick="return confirm('Sync Pascabayar?')">
                    <i class="fas fa-sync"></i> Sync Pascabayar
                </a>
                
                <div class="w-px h-8 bg-gray-300 mx-1 hidden md:block"></div>

                {{-- Tombol Massal --}}
                <button onclick="openBulkModal()" class="bg-orange-500 hover:bg-orange-600 text-white px-3 py-2 rounded-lg text-xs font-bold shadow flex items-center gap-2">
                    <i class="fas fa-tags"></i> Update Massal
                </button>
            </div>
        </div>

        {{-- Widget Saldo --}}
        <div class="w-full xl:w-auto bg-gradient-to-br from-gray-800 to-gray-900 rounded-xl p-5 text-white shadow-lg relative overflow-hidden">
            <div class="relative z-10 flex flex-col md:flex-row items-center gap-6">
                <div>
                    <p class="text-gray-400 text-xs font-medium uppercase tracking-wider mb-1">Sisa Saldo Digiflazz</p>
                    <div class="flex items-center gap-3">
                        <h3 id="saldo-display" class="text-2xl font-bold tracking-tight">Rp ...</h3>
                        <button onclick="fetchSaldo()" id="btn-refresh-saldo" class="text-gray-400 hover:text-white transition"><i class="fas fa-sync-alt" id="icon-refresh"></i></button>
                    </div>
                    <p id="saldo-loading" class="text-[10px] text-gray-400 hidden">Memuat...</p>
                </div>
                <div class="flex gap-2">
                    <button onclick="openDepositModal()" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded-lg text-sm shadow"><i class="fas fa-plus-circle"></i> Deposit</button>
                    <button onclick="openTopupModal()" class="bg-white/10 hover:bg-white/20 text-white font-semibold py-2 px-4 rounded-lg text-sm border border-white/10"><i class="fas fa-bolt"></i> Manual Trx</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Filter & Navigasi --}}
    <div class="bg-white rounded-t-xl border border-gray-200 shadow-sm mt-8">
        <div class="flex flex-col md:flex-row justify-between items-center p-1 border-b border-gray-200 bg-gray-50 rounded-t-xl">
            <div class="flex">
                @php $currentType = request('type', 'prepaid'); @endphp
                <a href="{{ route('admin.ppob.index', ['type' => 'prepaid', 'q' => request('q')]) }}" class="px-6 py-3 text-sm font-bold {{ $currentType === 'prepaid' ? 'text-blue-600 bg-white border-t-2 border-blue-600' : 'text-gray-500 hover:text-gray-700' }}">Prabayar</a>
                <a href="{{ route('admin.ppob.index', ['type' => 'postpaid', 'q' => request('q')]) }}" class="px-6 py-3 text-sm font-bold {{ $currentType === 'postpaid' ? 'text-blue-600 bg-white border-t-2 border-blue-600' : 'text-gray-500 hover:text-gray-700' }}">Pascabayar</a>
            </div>
            <form action="{{ route('admin.ppob.index') }}" method="GET" class="p-2 w-full md:w-auto">
                <input type="hidden" name="type" value="{{ $currentType }}">
                <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari SKU / Brand..." class="w-full md:w-64 px-4 py-2 border border-gray-300 rounded-lg text-sm">
            </form>
        </div>
    </div>

    {{-- Tabel Produk --}}
    <div class="bg-white shadow-md rounded-b-xl overflow-hidden border-x border-b border-gray-200">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left table-auto">
                <thead class="bg-gray-100 text-gray-600 uppercase text-xs font-bold">
                    <tr>
                        <th class="px-6 py-4">SKU</th>
                        <th class="px-6 py-4">Produk</th>
                        <th class="px-6 py-4 text-right">Modal</th>
                        {{-- KOLOM KOMISI (Ditambahkan) --}}
                        <th class="px-6 py-4 text-right text-purple-600">Komisi</th>
                        <th class="px-6 py-4 text-right text-red-500" title="Max Price">Max Limit</th>
                        <th class="px-6 py-4 text-right">Jual</th>
                        <th class="px-6 py-4 text-center">Status</th>
                        <th class="px-6 py-4 text-center sticky right-0 bg-gray-100 z-10">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($products as $product)
                    <tr class="hover:bg-blue-50 transition">
                        <td class="px-6 py-4 font-mono text-xs font-bold text-gray-500">{{ $product->buyer_sku_code }}</td>
                        <td class="px-6 py-4">
                            <div class="font-medium text-gray-900">{{ Str::limit($product->product_name, 40) }}</div>
                            <div class="text-xs text-gray-500">{{ $product->brand }} • {{ $product->category }}</div>
                        </td>
                        
                        {{-- Harga Modal --}}
                        <td class="px-6 py-4 text-right font-mono text-gray-600">
                            Rp {{ number_format($product->price, 0, ',', '.') }}
                        </td>

                        {{-- KOLOM KOMISI (Isi) --}}
                        <td class="px-6 py-4 text-right font-mono text-purple-600 font-medium">
                            @if($product->commission > 0)
                                +Rp {{ number_format($product->commission, 0, ',', '.') }}
                            @else
                                <span class="text-gray-300">-</span>
                            @endif
                        </td>
                        
                        {{-- Max Limit --}}
                        <td class="px-6 py-4 text-right font-mono text-red-500 font-bold">
                            @if($product->max_buy_price > 0)
                                Rp {{ number_format($product->max_buy_price, 0, ',', '.') }}
                            @else
                                <span class="text-gray-300">-</span>
                            @endif
                        </td>
                        
                        <td class="px-6 py-4 text-right font-bold text-green-600">Rp {{ number_format($product->sell_price, 0, ',', '.') }}</td>
                        <td class="px-6 py-4 text-center">
                            <span class="px-2 py-1 text-xs font-bold rounded-full {{ $product->seller_product_status ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                {{ $product->seller_product_status ? 'Aktif' : 'Nonaktif' }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-center sticky right-0 bg-white group-hover:bg-blue-50 transition z-10">
                            {{-- BUTTON EDIT (KUNING) --}}
                            <button onclick="editPrice(
                                '{{ $product->id }}', 
                                '{{ addslashes($product->product_name) }}', 
                                '{{ $product->price }}', 
                                '{{ $product->sell_price }}', 
                                '{{ $product->seller_product_status }}',
                                '{{ $product->max_buy_price ?? 0 }}' 
                            )" class="p-2 bg-yellow-100 text-yellow-600 rounded hover:bg-yellow-200 transition" title="Edit Harga & Limit">
                                <i class="fas fa-edit"></i>
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="8" class="text-center py-6 text-gray-500">Data tidak ditemukan.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="bg-gray-50 px-6 py-4 border-t border-gray-200">
            {{ $products->appends(request()->all())->links() }}
        </div>
    </div>
</div>

{{-- MODAL 1: EDIT HARGA & LIMIT --}}
<div id="priceModal" class="fixed inset-0 z-50 hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="fixed inset-0 bg-gray-900 bg-opacity-75 transition-opacity" onclick="closeModal()"></div>
    <div class="flex items-center justify-center min-h-screen px-4">
        <div class="bg-white rounded-xl overflow-hidden shadow-xl transform transition-all sm:max-w-md w-full">
            <form id="priceForm" action="" method="POST" class="p-6">
                @csrf
                @method('PUT')
                <div class="flex justify-between items-center mb-5 border-b pb-4">
                    <h3 class="text-lg font-bold text-gray-900">Setting Produk</h3>
                    <button type="button" onclick="closeModal()" class="text-gray-400 hover:text-gray-500"><i class="fas fa-times"></i></button>
                </div>
                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase">Nama Produk</label>
                        <input type="text" id="modal_product_name" class="w-full mt-1 bg-gray-100 border-gray-300 rounded text-sm text-gray-600" readonly>
                    </div>
                    
                    {{-- Input Limit Harga (Tanpa Nomor Tujuan) --}}
                    <div class="bg-red-50 p-3 rounded border border-red-100">
                        <label class="block text-xs font-bold text-red-600 uppercase mb-1">Limit Harga Beli (Max Price)</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500 text-sm">Rp</span>
                            <input type="number" name="max_buy_price" id="modal_max_buy_price" class="w-full pl-8 border-red-200 rounded focus:ring-red-500 focus:border-red-500" placeholder="0 (Tanpa Limit)">
                        </div>
                        <p class="text-[10px] text-red-500 mt-1">*Jika harga modal pusat > ini, transaksi gagal.</p>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase">Harga Modal Asli</label>
                            <input type="text" id="modal_base_price_display" class="w-full mt-1 bg-gray-100 border-gray-300 rounded text-sm font-bold" readonly>
                            <input type="hidden" id="modal_base_price_raw">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase">Margin Profit</label>
                            <div class="flex mt-1">
                                <select id="single_profit_type" onchange="calculateSinglePrice()" class="bg-white border border-gray-300 text-xs rounded-l focus:ring-blue-500">
                                    <option value="rupiah">Rp</option>
                                    <option value="percent">%</option>
                                </select>
                                <input type="number" id="single_profit_value" oninput="calculateSinglePrice()" value="0" class="w-full border-l-0 border-gray-300 rounded-r text-sm focus:ring-blue-500">
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase">Harga Jual User</label>
                        <div class="relative mt-1">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500 text-sm">Rp</span>
                            <input type="number" name="sell_price" id="modal_sell_price" class="w-full pl-10 text-lg font-bold border-gray-300 rounded text-green-600" required>
                        </div>
                    </div>
                    
                    <div class="flex items-center pt-2">
                        <input id="modal_status" name="status" type="checkbox" class="w-4 h-4 text-blue-600 border-gray-300 rounded">
                        <label for="modal_status" class="ml-2 block text-sm text-gray-900 font-medium">Produk Aktif</label>
                    </div>
                </div>
                <div class="mt-6 flex justify-end gap-3">
                    <button type="button" onclick="closeModal()" class="px-4 py-2 bg-gray-100 text-gray-700 rounded font-medium">Batal</button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded font-bold shadow">Simpan Setting</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- MODAL 2: DEPOSIT --}}
<div id="depositModal" class="fixed inset-0 z-50 hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="fixed inset-0 bg-gray-900 bg-opacity-75 transition-opacity" onclick="closeDepositModal()"></div>
    <div class="flex items-center justify-center min-h-screen px-4">
        <div class="bg-white rounded-xl shadow-xl transform transition-all sm:max-w-md w-full">
            <form id="formDeposit" onsubmit="submitDeposit(event)" class="p-6"> 
                @csrf
                <div class="flex justify-between items-center mb-5 border-b pb-4">
                    <h3 class="text-lg font-bold text-gray-900">Isi Deposit Saldo</h3>
                    <button type="button" onclick="closeDepositModal()" class="text-gray-400 hover:text-gray-500"><i class="fas fa-times"></i></button>
                </div>
                <div class="space-y-4 mb-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Bank</label>
                        <select name="bank" class="w-full rounded border-gray-300"><option value="BCA">BCA</option><option value="MANDIRI">MANDIRI</option><option value="BRI">BRI</option></select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nominal</label>
                        <input type="number" name="amount" class="w-full rounded border-gray-300" placeholder="Min. 50000" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Pemilik Rekening</label>
                        <input type="text" name="owner_name" class="w-full rounded border-gray-300" required>
                    </div>
                </div>
                <div class="flex justify-end gap-3">
                    <button type="button" onclick="closeDepositModal()" class="px-4 py-2 bg-gray-100 text-gray-700 rounded">Batal</button>
                    <button type="submit" id="btnSubmitDepo" class="px-4 py-2 bg-blue-600 text-white rounded font-bold shadow">Request Tiket</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- MODAL 3: TRANSAKSI MANUAL --}}
<div id="topupModal" class="fixed inset-0 z-50 hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="fixed inset-0 bg-gray-900 bg-opacity-75 transition-opacity" onclick="closeTopupModal()"></div>
    <div class="flex items-center justify-center min-h-screen px-4">
        <div class="bg-white rounded-xl shadow-xl transform transition-all sm:max-w-md w-full">
            <form action="{{ route('admin.ppob.topup') }}" method="POST" class="p-6">
                @csrf
                <div class="flex justify-between items-center mb-5 border-b pb-4">
                    <h3 class="text-lg font-bold text-gray-900">Transaksi Manual</h3>
                    <button type="button" onclick="closeTopupModal()" class="text-gray-400 hover:text-gray-500"><i class="fas fa-times"></i></button>
                </div>
                <div class="space-y-4 mb-6">
                    <div class="bg-yellow-50 p-3 rounded text-xs text-yellow-800 border border-yellow-200">Fitur ini untuk mengirim pulsa darurat. Saldo admin terpotong.</div>
                    <div><label class="block text-sm font-medium text-gray-700 mb-1">SKU</label><input type="text" name="buyer_sku_code" class="w-full rounded border-gray-300 uppercase" placeholder="XLD10" required></div>
                    <div><label class="block text-sm font-medium text-gray-700 mb-1">Nomor Tujuan</label><input type="text" name="customer_no" class="w-full rounded border-gray-300" placeholder="08..." required></div>
                </div>
                <div class="flex justify-end gap-3">
                    <button type="button" onclick="closeTopupModal()" class="px-4 py-2 bg-gray-100 text-gray-700 rounded">Batal</button>
                    <button type="submit" onclick="return confirm('Kirim transaksi sekarang?')" class="px-4 py-2 bg-red-600 text-white rounded font-bold shadow">Kirim</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- MODAL 4: MASSAL UPDATE --}}
<div id="bulkPriceModal" class="fixed inset-0 z-50 hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="fixed inset-0 bg-gray-900 bg-opacity-75 transition-opacity" onclick="closeBulkModal()"></div>
    <div class="flex items-center justify-center min-h-screen px-4">
        <div class="bg-white rounded-xl shadow-xl transform transition-all sm:max-w-lg w-full">
            <form action="{{ route('admin.ppob.bulk-update') }}" method="POST" class="p-6">
                @csrf
                <div class="flex justify-between items-center mb-5 border-b pb-4">
                    <h3 class="text-lg font-bold text-gray-900">Update Massal</h3>
                    <button type="button" onclick="closeBulkModal()" class="text-gray-400 hover:text-gray-500"><i class="fas fa-times"></i></button>
                </div>
                <input type="hidden" name="product_type" value="{{ $currentType }}"> 
                <div class="bg-orange-50 p-4 rounded mb-4">
                    <div class="flex gap-4 mb-3">
                        <label><input type="radio" name="profit_type" value="rupiah" checked> Rp</label>
                        <label><input type="radio" name="profit_type" value="percent"> %</label>
                    </div>
                    <input type="number" name="profit_value" placeholder="Nilai keuntungan" class="w-full rounded border-gray-300" required>
                </div>
                <div class="flex justify-end gap-3">
                    <button type="button" onclick="closeBulkModal()" class="px-4 py-2 bg-gray-100 text-gray-700 rounded">Batal</button>
                    <button type="submit" class="px-4 py-2 bg-orange-600 text-white rounded font-bold shadow">Update</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    function fetchSaldo() {
        const display = document.getElementById('saldo-display');
        if(!display) return;
        document.getElementById('icon-refresh').classList.add('fa-spin');
        fetch("{{ route('admin.ppob.cek-saldo') }}")
            .then(res => res.json())
            .then(data => { display.innerText = data.status === 'success' ? data.formatted : 'Error'; })
            .catch(() => { display.innerText = 'Error'; })
            .finally(() => { document.getElementById('icon-refresh').classList.remove('fa-spin'); });
    }
    document.addEventListener('DOMContentLoaded', fetchSaldo);

    function openModal(id) { document.getElementById(id).classList.remove('hidden'); }
    function closeModalById(id) { document.getElementById(id).classList.add('hidden'); }
    function closeDepositModal() { closeModalById('depositModal'); }
    function openDepositModal() { openModal('depositModal'); }
    function closeTopupModal() { closeModalById('topupModal'); }
    function openTopupModal() { openModal('topupModal'); }
    function closeBulkModal() { closeModalById('bulkPriceModal'); }
    function openBulkModal() { openModal('bulkPriceModal'); }
    function closeModal() { closeModalById('priceModal'); }

    // EDIT PRICE: Mengisi Form dengan Data
    function editPrice(id, name, basePrice, sellPrice, status, maxBuyPrice) {
        document.getElementById('modal_product_name').value = name;
        document.getElementById('modal_base_price_raw').value = parseFloat(basePrice);
        document.getElementById('modal_base_price_display').value = 'Rp ' + parseFloat(basePrice).toLocaleString('id-ID');
        document.getElementById('modal_sell_price').value = parseFloat(sellPrice);
        
        // ISI INPUT MAX BUY PRICE
        const maxInput = document.getElementById('modal_max_buy_price');
        maxInput.value = (maxBuyPrice && maxBuyPrice > 0) ? parseFloat(maxBuyPrice) : '';

        const statusCheckbox = document.getElementById('modal_status');
        if(statusCheckbox) statusCheckbox.checked = (status == 1);

        let url = "{{ route('admin.ppob.update-price', ':id') }}";
        url = url.replace(':id', id);
        document.getElementById('priceForm').action = url;

        openModal('priceModal');
    }

    function calculateSinglePrice() {
        let base = parseFloat(document.getElementById('modal_base_price_raw').value) || 0;
        let val = parseFloat(document.getElementById('single_profit_value').value) || 0;
        let type = document.getElementById('single_profit_type').value;
        let final = type === 'rupiah' ? base + val : base + (base * (val / 100));
        document.getElementById('modal_sell_price').value = Math.ceil(final);
    }

    function submitDeposit(e) {
        e.preventDefault();
        const btn = document.getElementById('btnSubmitDepo');
        btn.disabled = true; btn.innerText = 'Memproses...';

        fetch("{{ route('admin.ppob.deposit') }}", {
            method: "POST",
            headers: { "X-CSRF-TOKEN": csrfToken, "Accept": "application/json" },
            body: new FormData(document.getElementById('formDeposit'))
        })
        .then(async res => {
            const data = await res.json();
            if(!res.ok) throw new Error(data.message || 'Gagal');
            closeDepositModal();
            Swal.fire({
                icon: 'success', title: 'Tiket Dibuat',
                html: `Transfer <b>${data.formatted_amount}</b><br>Ke: ${data.bank} - ${data.account_no}`,
            });
        })
        .catch(err => {
            Swal.fire({ icon: 'error', title: 'Gagal', text: err.message });
        })
        .finally(() => { btn.disabled = false; btn.innerText = 'Request Tiket'; });
    }
</script>
@endpush
