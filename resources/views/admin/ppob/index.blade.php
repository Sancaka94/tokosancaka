@extends('layouts.admin')

@section('title', 'Manajemen Produk PPOB')

@section('content')
<div class="container mx-auto px-4 py-8">
    
    {{-- Header Section --}}
    <div class="flex flex-col xl:flex-row justify-between items-start xl:items-center mb-6 gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Daftar Produk PPOB</h1>
            <p class="text-sm text-gray-500 mt-1">Kelola harga dan margin keuntungan produk secara massal.</p>
        </div>
        
        <div class="flex flex-wrap gap-2">
            {{-- Tombol Export --}}
            <a href="{{ route('admin.ppob.export-pdf') }}" target="_blank" class="bg-red-600 hover:bg-red-700 text-white font-semibold px-4 py-2 rounded-lg shadow transition flex items-center gap-2 text-sm">
                <i class="fas fa-file-pdf"></i> PDF
            </a>
            <a href="{{ route('admin.ppob.export-excel') }}" target="_blank" class="bg-green-600 hover:bg-green-700 text-white font-semibold px-4 py-2 rounded-lg shadow transition flex items-center gap-2 text-sm">
                <i class="fas fa-file-excel"></i> Excel
            </a>

            <div class="w-px h-8 bg-gray-300 mx-1 hidden md:block"></div>

            {{-- Tombol Aksi Utama --}}
            <button onclick="openBulkModal()" class="bg-orange-500 hover:bg-orange-600 text-white font-semibold px-4 py-2 rounded-lg shadow transition flex items-center gap-2 text-sm">
                <i class="fas fa-tags"></i> Update Harga Massal
            </button>
            <a href="{{ route('ppob.sync') }}" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-4 py-2 rounded-lg shadow transition flex items-center gap-2 text-sm">
                <i class="fas fa-sync-alt animate-spin-hover"></i> Sinkronisasi
            </a>
        </div>
    </div>

    {{-- Alert Notification --}}
    @if(session('success'))
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded shadow-sm flex items-center justify-between animate-fade-in-down">
            <div class="flex items-center">
                <i class="fas fa-check-circle mr-2"></i> {{ session('success') }}
            </div>
            <button onclick="this.parentElement.remove()" class="text-green-700 hover:text-green-900"><i class="fas fa-times"></i></button>
        </div>
    @endif

    {{-- Filter & Search Bar --}}
    <div class="bg-white p-4 rounded-t-xl border-b border-gray-200 shadow-sm flex flex-col md:flex-row justify-between items-center gap-4">
        <div class="text-gray-600 text-sm">
            Menampilkan <span class="font-bold">{{ $products->firstItem() ?? 0 }}</span> sampai <span class="font-bold">{{ $products->lastItem() ?? 0 }}</span> dari <span class="font-bold">{{ $products->total() }}</span> produk
        </div>
        <form action="{{ route('admin.ppob.index') }}" method="GET" class="relative w-full md:w-1/3">
            <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari SKU, Nama Produk, atau Brand..." 
                   class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition text-sm">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <i class="fas fa-search text-gray-400"></i>
            </div>
        </form>
    </div>

    {{-- Table Section --}}
    <div class="bg-white shadow-md rounded-b-xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="bg-gray-50 text-gray-600 uppercase text-xs font-semibold tracking-wider">
                    <tr>
                        <th class="px-6 py-4">SKU</th>
                        <th class="px-6 py-4">Nama Produk</th>
                        <th class="px-6 py-4">Kategori</th>
                        <th class="px-6 py-4">Brand</th>
                        <th class="px-6 py-4 text-right">Harga Beli</th>
                        <th class="px-6 py-4 text-right">Harga Jual</th>
                        <th class="px-6 py-4 text-center">Status</th>
                        <th class="px-6 py-4 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white">
                    @forelse($products as $product)
                    <tr class="hover:bg-blue-50 transition duration-150 ease-in-out group">
                        <td class="px-6 py-4 font-mono text-xs font-bold text-gray-500 group-hover:text-blue-600">
                            {{ $product->buyer_sku_code }}
                        </td>
                        <td class="px-6 py-4">
                            <div class="font-medium text-gray-900">{{ Str::limit($product->product_name, 40) }}</div>
                            @if($product->multi)
                                <span class="inline-block mt-1 px-2 py-0.5 text-[10px] font-semibold rounded-full bg-purple-100 text-purple-700 border border-purple-200">
                                    Promo
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-gray-600">
                            {{ $product->category }}
                        </td>
                        <td class="px-6 py-4">
                            <span class="px-2 py-1 text-xs font-semibold rounded bg-gray-100 text-gray-600 border border-gray-200">
                                {{ $product->brand }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-right text-gray-500 font-medium">
                            Rp{{ number_format($product->price, 0, ',', '.') }}
                        </td>
                        <td class="px-6 py-4 text-right">
                            <span class="font-bold text-green-600 text-base">
                                Rp{{ number_format($product->sell_price, 0, ',', '.') }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-center">
                            @if(!$product->buyer_product_status)
                                <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800 border border-red-200">
                                    Gangguan
                                </span>
                            @elseif($product->seller_product_status)
                                <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800 border border-green-200">
                                    Aktif
                                </span>
                            @else
                                <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800 border border-gray-200">
                                    Nonaktif
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-center">
                            <div class="flex items-center justify-center space-x-3">
                                <button onclick="editPrice('{{ $product->id }}', '{{ $product->product_name }}', '{{ $product->price }}', '{{ $product->sell_price }}', '{{ $product->seller_product_status }}')" 
                                        class="text-yellow-500 hover:text-yellow-600 transition transform hover:scale-110 tooltip" title="Edit Harga">
                                    <i class="fas fa-edit text-lg"></i>
                                </button>
                                
                                <form action="{{ route('admin.ppob.destroy', $product->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus produk ini?');" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-500 hover:text-red-600 transition transform hover:scale-110 tooltip" title="Hapus">
                                        <i class="fas fa-trash-alt text-lg"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-6 py-10 text-center text-gray-500 bg-gray-50">
                            <div class="flex flex-col items-center justify-center">
                                <i class="fas fa-box-open text-4xl text-gray-300 mb-3"></i>
                                <p class="text-base font-medium">Data produk tidak ditemukan.</p>
                                <p class="text-sm mt-1">Coba kata kunci lain atau lakukan sinkronisasi.</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination Section --}}
        <div class="bg-gray-50 px-6 py-4 border-t border-gray-200">
            {{ $products->links() }} 
        </div>
    </div>
</div>

{{-- ========================================== --}}
{{-- MODAL EDIT HARGA SATUAN (INDIVIDUAL)       --}}
{{-- ========================================== --}}
<div id="priceModal" class="fixed inset-0 z-50 hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="fixed inset-0 bg-gray-900 bg-opacity-75 transition-opacity backdrop-blur-sm" onclick="closeModal()"></div>
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg w-full">
            <form id="priceForm" action="" method="POST" class="p-6">
                @csrf
                @method('PUT')
                <div class="flex justify-between items-center mb-5 border-b pb-4">
                    <h3 class="text-lg font-bold text-gray-900">Update Harga Satuan</h3>
                    <button type="button" onclick="closeModal()" class="text-gray-400 hover:text-gray-500"><i class="fas fa-times text-xl"></i></button>
                </div>
                
                <div class="mb-4">
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Nama Produk</label>
                    <input type="text" id="modal_product_name" class="w-full bg-gray-100 border-transparent rounded-lg px-3 py-2 text-gray-600 text-sm focus:ring-0" readonly>
                </div>

                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Harga Beli</label>
                        <input type="text" id="modal_base_price_display" class="w-full bg-red-50 text-red-600 font-bold border-red-100 rounded-lg px-3 py-2 text-sm focus:ring-0" readonly>
                        <input type="hidden" id="modal_base_price_raw">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-green-700 uppercase mb-1">Harga Jual</label>
                        <input type="number" name="sell_price" id="modal_sell_price" class="w-full border-green-300 rounded-lg px-3 py-2 text-green-800 font-bold focus:ring-green-500" required>
                    </div>
                </div>

                {{-- Profit Calculator --}}
                <div class="bg-gray-50 p-3 rounded-lg border border-gray-200 mb-4 text-sm">
                    <p class="font-bold text-gray-600 mb-2">Ambil Profit Otomatis:</p>
                    <div class="flex gap-2">
                        <select id="single_profit_type" class="border-gray-300 rounded text-sm py-1 px-2">
                            <option value="rupiah">Rupiah (Rp)</option>
                            <option value="percent">Persen (%)</option>
                        </select>
                        <input type="number" id="single_profit_value" class="border-gray-300 rounded w-24 py-1 px-2 text-sm" placeholder="Nilai">
                        <button type="button" onclick="calculateSinglePrice()" class="bg-blue-600 text-white px-3 py-1 rounded text-sm hover:bg-blue-700">Hitung</button>
                    </div>
                </div>

                <div class="mb-6 flex items-start">
                    <div class="flex h-5 items-center">
                        <input type="checkbox" name="status" id="modal_status" value="1" class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                    </div>
                    <div class="ml-3 text-sm">
                        <label for="modal_status" class="font-medium text-gray-700 cursor-pointer">Aktifkan produk ini?</label>
                    </div>
                </div>

                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" onclick="closeModal()" class="px-4 py-2 bg-white border border-gray-300 rounded-lg text-gray-700 font-medium hover:bg-gray-50">Batal</button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-bold shadow-lg">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ========================================== --}}
{{-- MODAL BULK UPDATE (MASSAL)                 --}}
{{-- ========================================== --}}
<div id="bulkPriceModal" class="fixed inset-0 z-50 hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="fixed inset-0 bg-gray-900 bg-opacity-75 transition-opacity backdrop-blur-sm" onclick="closeBulkModal()"></div>
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg w-full border-t-4 border-orange-500">
            <form action="{{ route('admin.ppob.bulk-update') }}" method="POST" class="p-6">
                @csrf
                <div class="flex justify-between items-center mb-5 border-b pb-4">
                    <div>
                        <h3 class="text-xl font-bold text-gray-900"><i class="fas fa-tags text-orange-500 mr-2"></i>Update Harga Massal</h3>
                        <p class="text-xs text-gray-500 mt-1">Aksi ini akan mengubah harga jual SEMUA produk.</p>
                    </div>
                    <button type="button" onclick="closeBulkModal()" class="text-gray-400 hover:text-gray-500"><i class="fas fa-times text-xl"></i></button>
                </div>
                
                <div class="bg-orange-50 p-4 rounded-xl border border-orange-200 mb-6">
                    <p class="text-sm text-orange-800 font-semibold mb-3">Pilih Metode Keuntungan:</p>
                    
                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <label class="flex items-center p-3 border border-orange-200 rounded-lg bg-white cursor-pointer hover:bg-orange-50 transition">
                            <input type="radio" name="profit_type" value="rupiah" checked class="w-5 h-5 text-orange-600 border-gray-300 focus:ring-orange-500">
                            <div class="ml-3">
                                <span class="block text-sm font-bold text-gray-900">Nominal (Rp)</span>
                                <span class="block text-xs text-gray-500">Contoh: + Rp 2.000</span>
                            </div>
                        </label>
                        <label class="flex items-center p-3 border border-orange-200 rounded-lg bg-white cursor-pointer hover:bg-orange-50 transition">
                            <input type="radio" name="profit_type" value="percent" class="w-5 h-5 text-orange-600 border-gray-300 focus:ring-orange-500">
                            <div class="ml-3">
                                <span class="block text-sm font-bold text-gray-900">Persentase (%)</span>
                                <span class="block text-xs text-gray-500">Contoh: + 5%</span>
                            </div>
                        </label>
                    </div>

                    <div class="relative">
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Nilai Keuntungan</label>
                        <input type="number" name="profit_value" placeholder="Masukkan angka (misal: 2000 atau 5)" required 
                               class="w-full border-gray-300 rounded-lg px-4 py-3 text-gray-900 font-bold focus:ring-orange-500 focus:border-orange-500">
                    </div>
                </div>

                <div class="bg-gray-50 p-3 rounded-lg mb-6 text-xs text-gray-600">
                    <i class="fas fa-info-circle mr-1"></i> Rumus: <strong>Harga Beli + Profit</strong> = Harga Jual Baru.
                </div>

                <div class="flex justify-end gap-3">
                    <button type="button" onclick="closeBulkModal()" class="px-4 py-2 bg-white border border-gray-300 rounded-lg text-gray-700 font-medium hover:bg-gray-50">Batal</button>
                    <button type="submit" onclick="return confirm('Yakin ingin mengubah harga SEMUA produk?')" class="px-6 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700 font-bold shadow-lg shadow-orange-200">
                        Terapkan ke Semua Produk
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    // --- JS MODAL EDIT INDIVIDUAL ---
    function editPrice(id, name, basePrice, sellPrice, status) {
        document.getElementById('modal_product_name').value = name;
        document.getElementById('modal_base_price_raw').value = parseInt(basePrice);
        document.getElementById('modal_base_price_display').value = 'Rp ' + parseInt(basePrice).toLocaleString('id-ID');
        document.getElementById('modal_sell_price').value = parseInt(sellPrice);
        
        const statusCheckbox = document.getElementById('modal_status');
        if(statusCheckbox) statusCheckbox.checked = (status == 1);

        let url = "{{ route('admin.ppob.update-price', ':id') }}";
        url = url.replace(':id', id);
        document.getElementById('priceForm').action = url;

        document.getElementById('priceModal').classList.remove('hidden');
    }

    function calculateSinglePrice() {
        let base = parseFloat(document.getElementById('modal_base_price_raw').value) || 0;
        let val = parseFloat(document.getElementById('single_profit_value').value) || 0;
        let type = document.getElementById('single_profit_type').value;
        let final = base;

        if(type === 'rupiah') final += val;
        else final += base * (val / 100);

        document.getElementById('modal_sell_price').value = Math.ceil(final);
    }

    function closeModal() {
        document.getElementById('priceModal').classList.add('hidden');
    }

    // --- JS MODAL BULK UPDATE ---
    function openBulkModal() {
        document.getElementById('bulkPriceModal').classList.remove('hidden');
    }

    function closeBulkModal() {
        document.getElementById('bulkPriceModal').classList.add('hidden');
    }
</script>
@endpush