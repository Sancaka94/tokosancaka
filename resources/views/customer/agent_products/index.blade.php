@extends('layouts.customer')

@section('title', 'Kelola Harga Toko Agen')

@section('content')
<div class="space-y-6">
    
    {{-- HEADER SECTION --}}
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Atur Harga Jual Toko</h2>
            <p class="text-sm text-gray-500 mt-1">
                Kelola keuntungan Anda sendiri. Harga Modal adalah harga beli Anda dari Kami.
            </p>
        </div>
    </div>

      <div class="flex flex-wrap items-center gap-3">
    
    {{-- 1. Tombol Cetak --}}
    <a href="#" onclick="alert('Fitur cetak brosur akan segera hadir!')" 
       class="inline-flex items-center justify-center px-4 py-2 bg-white border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 shadow-sm transition h-10">
        <i class="fas fa-print mr-2"></i> Cetak Daftar Harga
    </a>

    {{-- 2. Tombol Buka Kasir (SUDAH DIPERBAIKI) --}}
    <a href="{{ route('agent.transaction.create') }}" 
       class="inline-flex items-center justify-center px-4 py-2 bg-green-600 border border-transparent rounded-lg text-sm font-medium text-white hover:bg-green-700 shadow-sm transition h-10">
        <i class="fas fa-cash-register mr-2"></i> Buka Kasir / Jualan
    </a>

    {{-- 3. Tombol Naikkan Harga --}}
    <button onclick="openBulkModal()" 
            class="inline-flex items-center justify-center px-4 py-2 bg-blue-600 border border-transparent rounded-lg text-sm font-medium text-white hover:bg-blue-700 shadow-sm transition h-10">
        <i class="fas fa-magic mr-2"></i> Naikkan Harga Massal
    </button>

</div>

    {{-- STATISTIK SINGKAT & PENCARIAN --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        {{-- Card Info --}}
        <div class="md:col-span-1 bg-gradient-to-br from-blue-500 to-blue-600 rounded-2xl p-5 text-white shadow-lg relative overflow-hidden">
            <div class="relative z-10">
                <p class="text-blue-100 text-sm font-medium mb-1">Total Produk Aktif</p>
                <h3 class="text-3xl font-bold">{{ $products->total() }}</h3>
                <p class="text-xs text-blue-200 mt-2">Produk siap dijual kembali.</p>
            </div>
            <i class="fas fa-box absolute -right-3 -bottom-3 text-8xl text-white opacity-10"></i>
        </div>

        {{-- Form Pencarian --}}
        <div class="md:col-span-2 bg-white rounded-2xl p-6 shadow-sm border border-gray-100 flex items-center">
            <form action="{{ route('agent.products.index') }}" method="GET" class="w-full relative">
                <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari produk (contoh: Telkomsel, Token PLN)..." 
                       class="w-full pl-12 pr-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                    <i class="fas fa-search text-gray-400 text-lg"></i>
                </div>
            </form>
        </div>
    </div>

    {{-- TABEL PRODUK --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="bg-gray-50 text-gray-600 uppercase text-xs font-bold tracking-wider">
                    <tr>
                        <th class="px-6 py-4">Nama Produk</th>
                        <th class="px-6 py-4">Brand</th>
                        <th class="px-6 py-4 text-right bg-blue-50/50 text-blue-800 border-b border-blue-100">
                            Harga Modal Anda
                        </th>
                        <th class="px-6 py-4 text-right bg-green-50/50 text-green-800 border-b border-green-100">
                            Harga Jual User
                        </th>
                        <th class="px-6 py-4 text-center">Profit</th>
                        <th class="px-6 py-4 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($products as $product)
                        @php
                            // LOGIKA UTAMA: Harga Jual Admin = Harga Modal Agen
                            $modalAgen = $product->sell_price; 
                            
                            // Jika agen belum set harga, default markup +2000 (contoh)
                            $jualAgen = $product->agent_price ?? ($modalAgen + 2000); 
                            $profit = $jualAgen - $modalAgen;
                        @endphp
                        <tr class="hover:bg-gray-50 transition group">
                            <td class="px-6 py-4">
                                <div class="font-bold text-gray-800">{{ $product->product_name }}</div>
                                <div class="text-xs text-gray-400 font-mono mt-1">{{ $product->buyer_sku_code }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-2.5 py-1 text-xs font-medium rounded-full bg-gray-100 text-gray-600">
                                    {{ $product->brand }}
                                </span>
                            </td>
                            
                            {{-- Kolom Harga Modal (Readonly) --}}
                            <td class="px-6 py-4 text-right bg-blue-50/20">
                                <span class="text-gray-700 font-medium">Rp {{ number_format($modalAgen, 0, ',', '.') }}</span>
                            </td>

                            {{-- Kolom Harga Jual Agen --}}
                            <td class="px-6 py-4 text-right bg-green-50/20">
                                <span class="text-green-700 font-bold text-base">Rp {{ number_format($jualAgen, 0, ',', '.') }}</span>
                                @if(!$product->agent_price)
                                    <span class="block text-[10px] text-gray-400 italic mt-0.5">(Default)</span>
                                @endif
                            </td>

                            {{-- Kolom Profit --}}
                            <td class="px-6 py-4 text-center">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold {{ $profit >= 0 ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                                    {{ $profit >= 0 ? '+' : '' }} Rp {{ number_format($profit, 0, ',', '.') }}
                                </span>
                            </td>

                            <td class="px-6 py-4 text-center">
                                <button onclick="editPrice('{{ $product->id }}', '{{ addslashes($product->product_name) }}', '{{ $modalAgen }}', '{{ $jualAgen }}')" 
                                        class="text-blue-600 hover:text-blue-800 font-medium text-sm bg-blue-50 hover:bg-blue-100 px-3 py-1.5 rounded-lg transition border border-blue-200">
                                    <i class="fas fa-edit mr-1"></i> Edit
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                <div class="flex flex-col items-center justify-center">
                                    <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mb-3">
                                        <i class="fas fa-box-open text-2xl text-gray-400"></i>
                                    </div>
                                    <p class="font-medium">Produk tidak ditemukan.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        {{-- Pagination --}}
        <div class="bg-gray-50 px-6 py-4 border-t border-gray-200">
            {{ $products->links() }} 
        </div>
    </div>
</div>

{{-- ========================================== --}}
{{-- MODAL EDIT HARGA SATUAN (INDIVIDUAL)       --}}
{{-- ========================================== --}}
<div id="editModal" class="fixed inset-0 z-50 hidden backdrop-blur-sm" role="dialog" aria-modal="true">
    <div class="fixed inset-0 bg-gray-900 bg-opacity-50 transition-opacity" onclick="closeModal()"></div>
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md transform transition-all">
            <form action="{{ route('agent.products.update') }}" method="POST">
                @csrf
                @method('PUT')
                <input type="hidden" name="product_id" id="modal_product_id">

                <div class="p-6">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-xl font-bold text-gray-800">Atur Harga Jual</h3>
                        <button type="button" onclick="closeModal()" class="text-gray-400 hover:text-gray-600 transition">
                            <i class="fas fa-times text-xl"></i>
                        </button>
                    </div>

                    {{-- Nama Produk --}}
                    <div class="mb-5">
                        <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Produk</label>
                        <p class="text-gray-800 font-semibold leading-tight" id="modal_product_name">-</p>
                    </div>

                    {{-- Info Modal --}}
                    <div class="bg-blue-50 p-4 rounded-xl border border-blue-100 mb-6 flex justify-between items-center">
                        <div>
                            <span class="block text-xs text-blue-600 font-semibold uppercase">Modal Anda</span>
                            <span class="text-lg font-bold text-blue-900" id="modal_base_display">Rp 0</span>
                        </div>
                        <i class="fas fa-shopping-bag text-blue-300 text-2xl"></i>
                        <input type="hidden" id="modal_base_raw">
                    </div>

                    {{-- Input Harga Jual --}}
                    <div class="mb-6">
                        <label class="block text-sm font-bold text-gray-700 mb-2">Harga Jual ke Pelanggan</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <span class="text-gray-500 font-bold">Rp</span>
                            </div>
                            <input type="number" name="agent_price" id="modal_sell_input" 
                                   class="w-full pl-12 pr-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-xl font-bold text-gray-800 placeholder-gray-300" 
                                   placeholder="0" oninput="calculateProfit()" required>
                        </div>
                    </div>

                    {{-- Live Profit Calculator --}}
                    <div class="flex justify-between items-center p-3 bg-gray-50 rounded-lg border border-gray-200">
                        <span class="text-sm text-gray-600 font-medium">Estimasi Keuntungan:</span>
                        <span class="font-bold text-gray-800" id="modal_profit_display">Rp 0</span>
                    </div>
                </div>

                <div class="px-6 py-4 bg-gray-50 rounded-b-2xl border-t border-gray-100 flex justify-end gap-3">
                    <button type="button" onclick="closeModal()" class="px-5 py-2.5 rounded-xl border border-gray-300 text-gray-700 font-semibold hover:bg-white transition bg-white">Batal</button>
                    <button type="submit" class="px-5 py-2.5 rounded-xl bg-blue-600 text-white font-bold hover:bg-blue-700 shadow-lg shadow-blue-200 transition">Simpan Harga</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ========================================== --}}
{{-- MODAL BULK MARKUP (MASSAL)                 --}}
{{-- ========================================== --}}
<div id="bulkModal" class="fixed inset-0 z-50 hidden backdrop-blur-sm" role="dialog" aria-modal="true">
    <div class="fixed inset-0 bg-gray-900 bg-opacity-50 transition-opacity" onclick="closeBulkModal()"></div>
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md border-t-4 border-blue-500 transform transition-all">
            <form action="{{ route('agent.products.bulk_update') }}" method="POST" class="p-6">
                @csrf
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-xl font-bold text-gray-900">Naikkan Harga Otomatis</h3>
                    <button type="button" onclick="closeBulkModal()" class="text-gray-400 hover:text-gray-500 transition"><i class="fas fa-times text-xl"></i></button>
                </div>

                <div class="bg-yellow-50 border border-yellow-100 rounded-xl p-4 mb-6">
                    <div class="flex gap-3">
                        <i class="fas fa-lightbulb text-yellow-500 mt-1"></i>
                        <p class="text-sm text-yellow-800">
                            Fitur ini akan mengatur harga jual untuk <strong>SEMUA PRODUK</strong>. <br>
                            Rumus: <strong>Harga Modal + Keuntungan</strong>.
                        </p>
                    </div>
                </div>

                <div class="mb-6">
                    <label class="block text-sm font-bold text-gray-700 mb-2">Ingin untung berapa per transaksi?</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                            <span class="text-gray-500 font-bold">Rp</span>
                        </div>
                        <input type="number" name="markup_amount" placeholder="Contoh: 2000" required 
                               class="w-full pl-12 pr-4 py-3 border border-gray-300 rounded-xl focus:ring-blue-500 focus:border-blue-500 font-bold text-lg text-gray-800">
                    </div>
                    <p class="text-xs text-gray-400 mt-2">Contoh: Jika modal Rp 10.000 dan Anda isi 2.000, harga jual menjadi Rp 12.000.</p>
                </div>

                <div class="flex justify-end gap-3 mt-4">
                    <button type="button" onclick="closeBulkModal()" class="px-5 py-2.5 rounded-xl border border-gray-300 text-gray-600 font-semibold hover:bg-gray-50 transition">Batal</button>
                    <button type="submit" onclick="return confirm('Apakah Anda yakin? Harga semua produk akan berubah.')" class="px-5 py-2.5 rounded-xl bg-blue-600 text-white font-bold hover:bg-blue-700 shadow-lg transition">Terapkan</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    // Format Rupiah Helper
    const formatRupiah = (number) => {
        return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(number);
    }

    // --- Modal Edit Individual ---
    function editPrice(id, name, basePrice, sellPrice) {
        document.getElementById('modal_product_id').value = id;
        document.getElementById('modal_product_name').innerText = name;
        document.getElementById('modal_base_raw').value = basePrice;
        document.getElementById('modal_base_display').innerText = formatRupiah(basePrice);
        document.getElementById('modal_sell_input').value = parseInt(sellPrice);
        
        calculateProfit(); // Hitung profit awal
        document.getElementById('editModal').classList.remove('hidden');
    }

    // --- Hitung Profit Live ---
    function calculateProfit() {
        let base = parseFloat(document.getElementById('modal_base_raw').value) || 0;
        let sell = parseFloat(document.getElementById('modal_sell_input').value) || 0;
        let profit = sell - base;

        let display = document.getElementById('modal_profit_display');
        display.innerText = formatRupiah(profit);

        // Ubah warna text profit
        if(profit > 0) {
            display.classList.remove('text-red-600', 'text-gray-800');
            display.classList.add('text-green-600');
        } else if (profit < 0) {
            display.classList.remove('text-green-600', 'text-gray-800');
            display.classList.add('text-red-600');
        } else {
            display.classList.remove('text-green-600', 'text-red-600');
            display.classList.add('text-gray-800');
        }
    }

    function closeModal() {
        document.getElementById('editModal').classList.add('hidden');
    }

    // --- Modal Bulk ---
    function openBulkModal() {
        document.getElementById('bulkModal').classList.remove('hidden');
    }

    function closeBulkModal() {
        document.getElementById('bulkModal').classList.add('hidden');
    }
</script>
@endpush