@extends('layouts.admin') {{-- Sesuaikan dengan layout admin Anda --}}

@section('title', 'Manajemen Produk PPOB')

@section('content')
<div class="container mx-auto px-4 py-6">
    
    {{-- Header Section --}}
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Daftar Produk PPOB</h1>
        <div class="flex gap-2">
            <a href="{{ route('ppob.sync') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg shadow transition flex items-center gap-2">
                <i class="fas fa-sync-alt"></i> Sinkronisasi Produk
            </a>
        </div>
    </div>

    {{-- Alert --}}
    @if(session('success'))
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4 rounded shadow-sm">
            <p>{{ session('success') }}</p>
        </div>
    @endif

    {{-- Table Card --}}
    <div class="bg-white rounded-xl shadow-lg overflow-hidden border border-gray-100">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left text-gray-600" id="table-ppob">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50 border-b">
                    <tr>
                        <th class="px-4 py-3">SKU</th>
                        <th class="px-4 py-3">Nama Produk</th>
                        <th class="px-4 py-3">Kategori</th>
                        <th class="px-4 py-3">Brand</th>
                        <th class="px-4 py-3 text-right">Harga Beli</th>
                        <th class="px-4 py-3 text-right">Harga Jual</th>
                        <th class="px-4 py-3 text-center">Status</th>
                        <th class="px-4 py-3 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($products as $product)
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-4 py-3 font-mono text-xs font-bold">{{ $product->buyer_sku_code }}</td>
                        <td class="px-4 py-3 font-medium text-gray-900">
                            {{ Str::limit($product->product_name, 30) }}
                            @if($product->multi)
                                <span class="bg-purple-100 text-purple-700 text-[10px] px-1 rounded ml-1">Promo</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">{{ $product->category }}</td>
                        <td class="px-4 py-3">
                            <span class="bg-gray-100 text-gray-600 px-2 py-1 rounded text-xs">{{ $product->brand }}</span>
                        </td>
                        <td class="px-4 py-3 text-right font-medium text-red-500">
                            Rp{{ number_format($product->price, 0, ',', '.') }}
                        </td>
                        <td class="px-4 py-3 text-right font-bold text-green-600">
                            Rp{{ number_format($product->sell_price, 0, ',', '.') }}
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if($product->seller_product_status && $product->buyer_product_status)
                                <span class="bg-green-100 text-green-700 px-2 py-1 rounded-full text-xs font-bold">Aktif</span>
                            @elseif(!$product->buyer_product_status)
                                <span class="bg-red-100 text-red-700 px-2 py-1 rounded-full text-xs font-bold">Gangguan</span>
                            @else
                                <span class="bg-gray-100 text-gray-500 px-2 py-1 rounded-full text-xs font-bold">Nonaktif</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center">
                            <div class="flex items-center justify-center gap-2">
                                {{-- Tombol View (Detail) --}}
                                <button onclick="showDetail({{ $product->id }})" class="text-blue-500 hover:text-blue-700 tooltip" title="Lihat Detail">
                                    <i class="fas fa-eye"></i>
                                </button>
                                
                                {{-- Tombol Update Harga (Modal) --}}
                                <button onclick="editPrice('{{ $product->id }}', '{{ $product->product_name }}', '{{ $product->price }}', '{{ $product->sell_price }}', '{{ $product->seller_product_status }}')" 
                                    class="text-yellow-500 hover:text-yellow-700 tooltip" title="Update Harga">
                                    <i class="fas fa-edit"></i>
                                </button>

                                {{-- Tombol Hapus --}}
                                <form action="{{ route('admin.ppob.destroy', $product->id) }}" method="POST" onsubmit="return confirm('Yakin ingin menghapus produk ini?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-500 hover:text-red-700 tooltip" title="Hapus">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        {{-- Jika pakai pagination --}}
        {{-- <div class="p-4">{{ $products->links() }}</div> --}}
    </div>
</div>

{{-- ================= MODAL UPDATE HARGA ================= --}}
<div id="priceModal" class="fixed inset-0 z-50 hidden bg-gray-900 bg-opacity-50 flex items-center justify-center backdrop-blur-sm">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-md p-6 transform transition-all scale-100">
        <div class="flex justify-between items-center mb-4 border-b pb-2">
            <h3 class="text-lg font-bold text-gray-800">Update Harga Jual</h3>
            <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times"></i></button>
        </div>
        
        <form id="priceForm" action="" method="POST">
            @csrf
            @method('PUT')
            
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Nama Produk</label>
                <input type="text" id="modal_product_name" class="w-full bg-gray-100 border border-gray-300 rounded-lg px-3 py-2 text-gray-600" readonly>
            </div>

            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Harga Beli (Pusat)</label>
                    <input type="text" id="modal_base_price" class="w-full bg-red-50 border border-red-200 text-red-600 font-bold rounded-lg px-3 py-2" readonly>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Harga Jual (Agen)</label>
                    <input type="number" name="sell_price" id="modal_sell_price" class="w-full border border-gray-300 focus:ring-blue-500 focus:border-blue-500 rounded-lg px-3 py-2 font-bold text-gray-900" required>
                </div>
            </div>

            <div class="mb-6 flex items-center">
                <input type="checkbox" name="status" id="modal_status" value="1" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500">
                <label for="modal_status" class="ml-2 text-sm font-medium text-gray-900">Aktifkan Produk untuk Dijual?</label>
            </div>

            <div class="flex justify-end gap-2">
                <button type="button" onclick="closeModal()" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-300">Batal</button>
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 font-bold">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

{{-- ================= MODAL DETAIL PRODUK ================= --}}
<div id="detailModal" class="fixed inset-0 z-50 hidden bg-gray-900 bg-opacity-50 flex items-center justify-center backdrop-blur-sm">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg p-6">
        <h3 class="text-xl font-bold text-gray-800 mb-4 border-b pb-2">Detail Produk</h3>
        <div id="detailContent" class="space-y-2 text-sm text-gray-600">
            {{-- Konten diisi via JS --}}
            <div class="animate-pulse flex space-x-4">
                <div class="flex-1 space-y-4 py-1">
                    <div class="h-4 bg-gray-200 rounded w-3/4"></div>
                    <div class="space-y-2">
                        <div class="h-4 bg-gray-200 rounded"></div>
                        <div class="h-4 bg-gray-200 rounded w-5/6"></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="mt-6 flex justify-end">
            <button onclick="document.getElementById('detailModal').classList.add('hidden')" class="bg-gray-800 text-white px-4 py-2 rounded-lg">Tutup</button>
        </div>
    </div>
</div>

@endsection

@push('scripts')
{{-- Pastikan load library DataTables jika ingin fitur search/paging --}}
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.tailwindcss.min.css">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>

<script>
    $(document).ready(function() {
        $('#table-ppob').DataTable({
            "pageLength": 10,
            "lengthMenu": [10, 25, 50, 100],
            "language": {
                "search": "Cari Produk:",
                "lengthMenu": "Tampilkan _MENU_ data",
                "info": "Menampilkan _START_ sampai _END_ dari _TOTAL_ produk",
                "paginate": { "next": ">", "previous": "<" }
            }
        });
    });

    // Fungsi Modal Update Harga
    function editPrice(id, name, basePrice, sellPrice, status) {
        document.getElementById('modal_product_name').value = name;
        document.getElementById('modal_base_price').value = parseInt(basePrice).toLocaleString('id-ID');
        document.getElementById('modal_sell_price').value = parseInt(sellPrice); // Input number butuh raw value
        
        // Checkbox status
        const statusCheckbox = document.getElementById('modal_status');
        if(status == 1) { statusCheckbox.checked = true; } else { statusCheckbox.checked = false; }

        // Set Action URL
        let url = "{{ route('admin.ppob.update-price', ':id') }}";
        url = url.replace(':id', id);
        document.getElementById('priceForm').action = url;

        document.getElementById('priceModal').classList.remove('hidden');
    }

    // Fungsi View Detail (Simple Fetch)
    function showDetail(id) {
        document.getElementById('detailModal').classList.remove('hidden');
        const content = document.getElementById('detailContent');
        content.innerHTML = '<p class="text-center">Memuat data...</p>';

        let url = "{{ route('admin.ppob.show', ':id') }}";
        url = url.replace(':id', id);

        fetch(url)
            .then(response => response.json())
            .then(data => {
                let html = `
                    <div class="grid grid-cols-3 gap-2">
                        <span class="font-bold">Nama:</span> <span class="col-span-2">${data.product_name}</span>
                        <span class="font-bold">SKU:</span> <span class="col-span-2">${data.buyer_sku_code}</span>
                        <span class="font-bold">Kategori:</span> <span class="col-span-2">${data.category} (${data.brand})</span>
                        <span class="font-bold">Deskripsi:</span> <span class="col-span-2 italic">${data.desc}</span>
                        <span class="font-bold">Jam Cut Off:</span> <span class="col-span-2">${data.start_cut_off} - ${data.end_cut_off}</span>
                        <span class="font-bold">Stok:</span> <span class="col-span-2">${data.unlimited_stock ? 'Unlimited' : data.stock}</span>
                        <span class="font-bold">Multi Trx:</span> <span class="col-span-2">${data.multi ? 'Ya' : 'Tidak'}</span>
                    </div>
                `;
                content.innerHTML = html;
            });
    }

    function closeModal() {
        document.getElementById('priceModal').classList.add('hidden');
    }
</script>
@endpush