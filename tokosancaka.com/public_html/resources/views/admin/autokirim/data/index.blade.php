@extends('layouts.admin')

@section('title', 'Manajemen Komisi Agen Sancaka')

@section('content')
<!-- LOG LOG -->
<div class="max-w-7xl mx-auto space-y-6 p-4 md:p-8 font-sans relative">
    
    <!-- Header & Action Buttons -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center space-y-4 md:space-y-0">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight text-black">Logistik & Komisi</h1>
            <p class="text-sm text-gray-500 mt-1">Kelola data cashback, admin COD, dan persentase komisi Agen Sancaka secara dinamis.</p>
        </div>
        
        <div class="flex items-center space-x-3">
            <!-- Import Excel & Download Template -->
            <form action="{{ route('admin.data-autokirim.import') }}" method="POST" enctype="multipart/form-data" class="flex items-center space-x-2">
                @csrf
                <a href="{{ route('admin.data-autokirim.template') }}" class="bg-blue-50 border border-blue-200 text-blue-700 hover:bg-blue-100 px-3 py-2 rounded-md text-sm font-medium transition-colors flex items-center" title="Download Template Excel">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 mr-1">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                    </svg>
                    Template
                </a>
                
                <input type="file" name="file" class="text-sm border border-gray-200 rounded-md py-1.5 px-2 bg-white w-48 focus:outline-none focus:border-black" accept=".xlsx,.csv,.xls" required>
                <button type="submit" class="bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 px-4 py-2 rounded-md text-sm font-medium transition-colors">
                    Import
                </button>
            </form>

            <a href="{{ route('admin.data-autokirim.export.excel') }}" class="bg-green-50 text-green-700 border border-green-200 hover:bg-green-100 px-4 py-2 rounded-md text-sm font-medium transition-colors">
                Export Excel
            </a>
            <a href="{{ route('admin.data-autokirim.export.pdf') }}" class="bg-red-50 text-red-700 border border-red-200 hover:bg-red-100 px-4 py-2 rounded-md text-sm font-medium transition-colors">
                Export PDF
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="p-4 rounded-md border border-green-200 bg-green-50 text-sm text-green-800">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="p-4 rounded-md border border-red-200 bg-red-50 text-sm text-red-800">
            {{ session('error') }}
        </div>
    @endif

    <!-- Form Tambah Data Manual -->
    <div class="bg-white border border-gray-200 rounded-lg p-5 shadow-sm mt-4">
        <h2 class="text-base font-medium text-black mb-4">Tambah Skema Komisi Manual</h2>
        <form action="{{ route('admin.data-autokirim.store') }}" method="POST" class="grid grid-cols-1 md:grid-cols-6 gap-4 items-end">
            @csrf
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Brand Logistik</label>
                <input type="text" name="brand_logistik" placeholder="e.g., AnterAja" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-black focus:border-black" required>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Service</label>
                <input type="text" name="service" placeholder="e.g., cod reg" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-black focus:border-black" required>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Cashback (%)</label>
                <input type="number" step="0.01" name="cashback" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-black focus:border-black" required>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Admin COD (%)</label>
                <input type="number" step="0.01" name="admin_cod" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-black focus:border-black" required>
            </div>
            <div>
                <label class="block text-xs font-medium text-blue-600 mb-1">Komisi Agen (%)</label>
                <input type="number" step="0.01" name="komisi_agen" placeholder="Bagi deviden" class="w-full border border-blue-300 bg-blue-50 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-blue-600 focus:border-blue-600" required>
            </div>
            <div>
                <button type="submit" class="w-full bg-black text-white hover:bg-gray-800 px-4 py-2 rounded-md text-sm font-medium transition-colors">
                    Simpan Data
                </button>
            </div>
        </form>
    </div>

    <!-- MAIN FORM FOR BULK ACTIONS -->
    <form id="bulkActionForm" method="POST">
        @csrf
        
        <!-- Toolbar Bulk Action -->
        <div class="flex space-x-2 py-2">
            <button type="button" onclick="submitBulkDestroy()" class="bg-red-50 text-red-700 border border-red-200 hover:bg-red-100 px-4 py-1.5 rounded-md text-sm font-medium transition-colors shadow-sm">
                Hapus Terpilih
            </button>
            <button type="button" onclick="openBulkEditModal()" class="bg-blue-50 text-blue-700 border border-blue-200 hover:bg-blue-100 px-4 py-1.5 rounded-md text-sm font-medium transition-colors shadow-sm">
                Edit Terpilih
            </button>
        </div>

        <!-- Tabel Data Next.js Style -->
        <div class="bg-white border border-gray-200 rounded-lg shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-50/50 border-b border-gray-200">
                            <!-- Checkbox Select All -->
                            <th class="py-3 px-4 w-10 text-center">
                                <input type="checkbox" onclick="toggleSelectAll(this)" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 cursor-pointer">
                            </th>
                            <th class="py-3 px-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">Brand</th>
                            <th class="py-3 px-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">Service</th>
                            <th class="py-3 px-4 text-xs font-semibold text-gray-500 uppercase tracking-wider text-center">Satuan</th>
                            <th class="py-3 px-4 text-xs font-semibold text-gray-500 uppercase tracking-wider text-right">Cashback</th>
                            <th class="py-3 px-4 text-xs font-semibold text-gray-500 uppercase tracking-wider text-right">Admin COD</th>
                            <th class="py-3 px-4 text-xs font-semibold text-blue-600 uppercase tracking-wider text-right bg-blue-50/30">Komisi Agen</th>
                            <th class="py-3 px-4 text-xs font-semibold text-gray-500 uppercase tracking-wider text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 text-sm">
                        @forelse($data as $item)
                        <tr class="hover:bg-gray-50/50 transition-colors">
                            <td class="py-3 px-4 text-center">
                                <input type="checkbox" name="ids[]" value="{{ $item->id }}" class="row-checkbox rounded border-gray-300 text-blue-600 focus:ring-blue-500 cursor-pointer">
                            </td>
                            <td class="py-3 px-4 font-medium text-gray-900">{{ $item->brand_logistik }}</td>
                            <td class="py-3 px-4 text-gray-600">{{ $item->service }}</td>
                            <td class="py-3 px-4 text-gray-500 text-center">{{ $item->satuan }}</td>
                            <td class="py-3 px-4 text-gray-700 text-right">{{ $item->cashback }}</td>
                            <td class="py-3 px-4 text-gray-700 text-right">{{ $item->admin_cod }}</td>
                            <td class="py-3 px-4 font-semibold text-blue-700 text-right bg-blue-50/10">{{ $item->komisi_agen }}</td>
                            <td class="py-3 px-4">
                                <div class="flex items-center justify-center space-x-3">
                                    <!-- Tombol Edit Satuan -->
                                    <button type="button" 
                                        onclick="openSingleEditModal({{ $item->id }}, '{{ addslashes($item->brand_logistik) }}', '{{ addslashes($item->service) }}', '{{ $item->cashback }}', '{{ $item->admin_cod }}', '{{ $item->komisi_agen }}')" 
                                        class="text-gray-400 hover:text-blue-600 transition-colors" title="Edit Data">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                                        </svg>
                                    </button>

                                    <!-- Tombol Hapus Individual -->
                                    <button type="button" onclick="deleteSingle({{ $item->id }})" class="text-gray-400 hover:text-red-600 transition-colors" title="Hapus Data">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="py-8 text-center text-gray-500 text-sm">Belum ada data skema komisi.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </form>
    
    <!-- Form Hapus Individual (Tersembunyi) -->
    <form id="deleteSingleForm" method="POST" class="hidden">
        @csrf
        @method('DELETE')
    </form>
</div>

<!-- ============================================== -->
<!-- MODAL SINGLE EDIT (UBAH DATA SATUAN) -->
<!-- ============================================== -->
<div id="singleEditModal" class="hidden fixed inset-0 z-50 bg-black/60 overflow-y-auto h-full w-full flex items-center justify-center transition-opacity">
    <div class="relative bg-white rounded-lg shadow-xl w-full max-w-md p-6 m-4">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Edit Data Komisi</h3>
        
        <form id="singleEditForm" method="POST">
            @csrf
            @method('PUT')
            
            <div class="mb-4">
                <label class="block text-xs font-medium text-gray-600 mb-1">Brand Logistik</label>
                <input type="text" id="edit_brand_logistik" name="brand_logistik" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:border-black focus:ring-1 focus:ring-black" required>
            </div>
            
            <div class="mb-4">
                <label class="block text-xs font-medium text-gray-600 mb-1">Service</label>
                <input type="text" id="edit_service" name="service" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:border-black focus:ring-1 focus:ring-black" required>
            </div>

            <div class="mb-4">
                <label class="block text-xs font-medium text-gray-600 mb-1">Cashback (%)</label>
                <input type="number" step="0.01" id="edit_cashback" name="cashback" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:border-black focus:ring-1 focus:ring-black" required>
            </div>
            <div class="mb-4">
                <label class="block text-xs font-medium text-gray-600 mb-1">Admin COD (%)</label>
                <input type="number" step="0.01" id="edit_admin_cod" name="admin_cod" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:border-black focus:ring-1 focus:ring-black" required>
            </div>
            <div class="mb-6">
                <label class="block text-xs font-bold text-blue-600 mb-1">Komisi Agen (%)</label>
                <input type="number" step="0.01" id="edit_komisi_agen" name="komisi_agen" class="w-full border border-blue-300 bg-blue-50 rounded-md px-3 py-2 text-sm focus:outline-none focus:border-blue-600 focus:ring-1 focus:ring-blue-600" required>
            </div>
            
            <div class="flex justify-end space-x-2">
                <button type="button" onclick="closeSingleEditModal()" class="bg-gray-100 text-gray-700 hover:bg-gray-200 border border-gray-200 px-4 py-2 rounded-md text-sm font-medium transition-colors">
                    Batal
                </button>
                <button type="submit" class="bg-blue-600 text-white hover:bg-blue-700 px-4 py-2 rounded-md text-sm font-medium transition-colors shadow-sm">
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ============================================== -->
<!-- MODAL BULK EDIT (UBAH DATA MASSAL) -->
<!-- ============================================== -->
<div id="bulkEditModal" class="hidden fixed inset-0 z-50 bg-black/60 overflow-y-auto h-full w-full flex items-center justify-center transition-opacity">
    <div class="relative bg-white rounded-lg shadow-xl w-full max-w-md p-6 m-4">
        <h3 class="text-lg font-semibold text-gray-900 mb-2">Edit Data Terpilih</h3>
        <p class="text-xs text-gray-500 mb-5">Biarkan kosong jika nilai tidak ingin diubah.</p>
        
        <form id="bulkEditForm" action="{{ route('admin.data-autokirim.bulk-update') }}" method="POST">
            @csrf
            <div id="hiddenIdsContainer"></div>
            
            <div class="mb-4">
                <label class="block text-xs font-medium text-gray-600 mb-1">Set Brand Logistik Baru</label>
                <input type="text" name="brand_logistik" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:border-black focus:ring-1 focus:ring-black" placeholder="Contoh: AnterAja">
            </div>
            
            <div class="mb-4">
                <label class="block text-xs font-medium text-gray-600 mb-1">Set Service Baru</label>
                <input type="text" name="service" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:border-black focus:ring-1 focus:ring-black" placeholder="Contoh: Reguler">
            </div>

            <div class="mb-4">
                <label class="block text-xs font-medium text-gray-600 mb-1">Set Cashback Baru (%)</label>
                <input type="number" step="0.01" name="cashback" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:border-black focus:ring-1 focus:ring-black" placeholder="Contoh: 10">
            </div>
            <div class="mb-4">
                <label class="block text-xs font-medium text-gray-600 mb-1">Set Admin COD Baru (%)</label>
                <input type="number" step="0.01" name="admin_cod" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:border-black focus:ring-1 focus:ring-black" placeholder="Contoh: 3.5">
            </div>
            <div class="mb-6">
                <label class="block text-xs font-bold text-blue-600 mb-1">Set Komisi Agen Baru (%)</label>
                <input type="number" step="0.01" name="komisi_agen" class="w-full border border-blue-300 bg-blue-50 rounded-md px-3 py-2 text-sm focus:outline-none focus:border-blue-600 focus:ring-1 focus:ring-blue-600" placeholder="Contoh: 5">
            </div>
            
            <div class="flex justify-end space-x-2">
                <button type="button" onclick="closeBulkEditModal()" class="bg-gray-100 text-gray-700 hover:bg-gray-200 border border-gray-200 px-4 py-2 rounded-md text-sm font-medium transition-colors">
                    Batal
                </button>
                <button type="submit" class="bg-black text-white hover:bg-gray-800 px-4 py-2 rounded-md text-sm font-medium transition-colors shadow-sm">
                    Terapkan Perubahan
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    // Fitur Checkbox Select All
    function toggleSelectAll(source) {
        const checkboxes = document.querySelectorAll('.row-checkbox');
        checkboxes.forEach(cb => cb.checked = source.checked);
    }

    function getSelectedIds() {
        const checkboxes = document.querySelectorAll('.row-checkbox:checked');
        return Array.from(checkboxes).map(cb => cb.value);
    }

    // Submit Bulk Destroy (Hapus Massal)
    function submitBulkDestroy() {
        const ids = getSelectedIds();
        if (ids.length === 0) {
            alert('Silakan centang minimal satu data untuk dihapus.');
            return;
        }
        if (confirm('Yakin ingin menghapus ' + ids.length + ' data terpilih?')) {
            const form = document.getElementById('bulkActionForm');
            form.action = "{{ route('admin.data-autokirim.bulk-destroy') }}";
            form.submit();
        }
    }

    // Modal Bulk Edit (Ubah Massal)
    function openBulkEditModal() {
        const ids = getSelectedIds();
        if (ids.length === 0) {
            alert('Silakan centang minimal satu data yang ingin diedit.');
            return;
        }
        const container = document.getElementById('hiddenIdsContainer');
        container.innerHTML = '';
        ids.forEach(id => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'ids[]';
            input.value = id;
            container.appendChild(input);
        });
        document.getElementById('bulkEditModal').classList.remove('hidden');
    }

    function closeBulkEditModal() {
        document.getElementById('bulkEditModal').classList.add('hidden');
        document.getElementById('bulkEditForm').reset();
    }

    // ============================================== //
    // MODAL SINGLE EDIT (UBAH SATUAN)                //
    // ============================================== //
    function openSingleEditModal(id, brand, service, cashback, admin_cod, komisi_agen) {
        // Set action form ke route update resource
        const form = document.getElementById('singleEditForm');
        form.action = "{{ url('admin/data-autokirim') }}/" + id;
        
        // Isi input dengan data sebelumnya
        document.getElementById('edit_brand_logistik').value = brand;
        document.getElementById('edit_service').value = service;
        document.getElementById('edit_cashback').value = cashback;
        document.getElementById('edit_admin_cod').value = admin_cod;
        document.getElementById('edit_komisi_agen').value = komisi_agen;
        
        // Tampilkan modal
        document.getElementById('singleEditModal').classList.remove('hidden');
    }

    function closeSingleEditModal() {
        document.getElementById('singleEditModal').classList.add('hidden');
        document.getElementById('singleEditForm').reset();
    }

    // Hapus Individual (Satuan)
    function deleteSingle(id) {
        if (confirm('Hapus data ini?')) {
            const form = document.getElementById('deleteSingleForm');
            form.action = "{{ url('admin/data-autokirim') }}/" + id; 
            form.submit();
        }
    }
</script>
@endsection