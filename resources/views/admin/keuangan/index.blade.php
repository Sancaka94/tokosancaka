@extends('layouts.admin') {{-- Sesuaikan dengan layout utama Anda --}}

@section('title', 'Laporan Keuangan')

@section('content')
<div class="container mx-auto px-4 py-6">

    {{-- ALERT NOTIFIKASI --}}
    @if(session('success'))
    <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
        <span class="block sm:inline">{{ session('success') }}</span>
    </div>
    @endif

    {{-- 1. CARD RINGKASAN (Gaya Dashboard) --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-white rounded-lg shadow p-5 border-l-4 border-green-500">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-sm text-gray-500 font-semibold uppercase">Total Pemasukan</p>
                    <h3 class="text-2xl font-bold text-gray-800">Rp{{ number_format($totalPemasukan, 0, ',', '.') }}</h3>
                </div>
                <div class="bg-green-100 p-3 rounded-full text-green-600">
                    <i class="fas fa-arrow-up"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-5 border-l-4 border-red-500">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-sm text-gray-500 font-semibold uppercase">Total Pengeluaran</p>
                    <h3 class="text-2xl font-bold text-gray-800">Rp{{ number_format($totalPengeluaran, 0, ',', '.') }}</h3>
                </div>
                <div class="bg-red-100 p-3 rounded-full text-red-600">
                    <i class="fas fa-arrow-down"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-5 border-l-4 border-blue-500">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-sm text-gray-500 font-semibold uppercase">Sisa Saldo</p>
                    <h3 class="text-2xl font-bold {{ $saldo < 0 ? 'text-red-600' : 'text-blue-600' }}">
                        Rp{{ number_format($saldo, 0, ',', '.') }}
                    </h3>
                </div>
                <div class="bg-blue-100 p-3 rounded-full text-blue-600">
                    <i class="fas fa-wallet"></i>
                </div>
            </div>
        </div>
    </div>

    {{-- 2. HEADER KONTEN & PENCARIAN --}}
    <div class="bg-white rounded-lg shadow mb-6">
        <div class="p-4 border-b border-gray-200 flex flex-col md:flex-row justify-between items-center gap-4">
            <h2 class="text-lg font-bold text-gray-700">
                <i class="fas fa-table me-2"></i> Daftar Transaksi
            </h2>
            
            <div class="flex flex-col md:flex-row gap-2 w-full md:w-auto">
                {{-- Form Search --}}
                <form action="{{ route('admin.keuangan.index') }}" method="GET" class="relative">
                    <input type="text" name="search" value="{{ request('search') }}" 
                        class="w-full md:w-64 pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 text-sm" 
                        placeholder="Cari Invoice / Keterangan...">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                        <i class="fas fa-search"></i>
                    </div>
                </form>

                {{-- Tombol Tambah (Trigger Modal) --}}
                <button onclick="openModal('modalCreate')" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition shadow-sm flex items-center gap-2">
                    <i class="fas fa-plus"></i> Tambah Transaksi
                </button>
            </div>
        </div>

        {{-- 3. TABEL DATA --}}
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left text-gray-600">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50 border-b">
                    <tr>
                        <th class="px-6 py-3 font-bold text-center w-12">No</th>
                        <th class="px-6 py-3 font-bold">Tanggal</th>
                        <th class="px-6 py-3 font-bold">Jenis</th>
                        <th class="px-6 py-3 font-bold">Invoice & Kategori</th>
                        <th class="px-6 py-3 font-bold">Keterangan</th>
                        <th class="px-6 py-3 font-bold text-right">Nilai (Rp)</th>
                        <th class="px-6 py-3 font-bold text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($transaksi as $index => $item)
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-6 py-4 text-center">
                            {{ $transaksi->firstItem() + $index }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            {{ \Carbon\Carbon::parse($item->tanggal)->format('d M Y') }}
                        </td>
                        <td class="px-6 py-4">
                            @if($item->jenis == 'Pemasukan')
                                <span class="bg-green-100 text-green-800 text-xs font-semibold px-2.5 py-0.5 rounded border border-green-200">
                                    <i class="fas fa-arrow-up me-1"></i> Pemasukan
                                </span>
                            @else
                                <span class="bg-red-100 text-red-800 text-xs font-semibold px-2.5 py-0.5 rounded border border-red-200">
                                    <i class="fas fa-arrow-down me-1"></i> Pengeluaran
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <div class="font-bold text-gray-800">{{ $item->nomor_invoice ?? '-' }}</div>
                            <div class="text-xs text-blue-600 mt-1 bg-blue-50 inline-block px-1 rounded">
                                {{ $item->kategori }}
                            </div>
                        </td>
                        <td class="px-6 py-4 text-gray-500 truncate max-w-xs">
                            {{ $item->keterangan ?? '-' }}
                        </td>
                        <td class="px-6 py-4 text-right font-mono font-bold {{ $item->jenis == 'Pemasukan' ? 'text-green-600' : 'text-red-600' }}">
                            {{ $item->jenis == 'Pengeluaran' ? '-' : '+' }} Rp{{ number_format($item->jumlah, 0, ',', '.') }}
                        </td>
                        <td class="px-6 py-4 text-center">
                            <div class="inline-flex rounded-md shadow-sm" role="group">
                                {{-- Tombol Edit --}}
                                <button onclick="editData({{ $item }})" class="bg-yellow-400 hover:bg-yellow-500 text-white px-3 py-1.5 text-xs rounded-l border-r border-yellow-500 transition" title="Edit">
                                    <i class="fas fa-pencil-alt"></i>
                                </button>
                                {{-- Tombol Hapus --}}
                                <form action="{{ route('admin.keuangan.destroy', $item->id) }}" method="POST" onsubmit="return confirm('Yakin hapus data ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="bg-red-600 hover:bg-red-700 text-white px-3 py-1.5 text-xs rounded-r transition" title="Hapus">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-8 text-center text-gray-400">
                            <i class="fas fa-folder-open text-4xl mb-3"></i>
                            <p>Belum ada data transaksi keuangan.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        {{-- Pagination --}}
        <div class="p-4 border-t border-gray-200">
            {{ $transaksi->withQueryString()->links() }}
        </div>
    </div>
</div>

{{-- MODAL CREATE (TAMBAH) --}}
<div id="modalCreate" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" onclick="closeModal('modalCreate')"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg w-full">
            <form action="{{ route('admin.keuangan.store') }}" method="POST">
                @csrf
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4" id="modal-title">
                        <i class="fas fa-plus-circle text-blue-600 me-2"></i> Tambah Transaksi Baru
                    </h3>
                    
                    <div class="grid grid-cols-1 gap-4">
                        {{-- Tanggal --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal</label>
                            <input type="date" name="tanggal" value="{{ date('Y-m-d') }}" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm" required>
                        </div>
                        
                        {{-- Jenis & Kategori --}}
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Jenis</label>
                                <select name="jenis" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm" required>
                                    <option value="Pemasukan">Pemasukan (+)</option>
                                    <option value="Pengeluaran">Pengeluaran (-)</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Kategori</label>
                                <select name="kategori" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm" required>
                                    <option value="Ekspedisi">Ekspedisi</option>
                                    <option value="Marketplace">Marketplace</option>
                                    <option value="PPOB">PPOB</option>
                                    <option value="Operasional">Operasional</option>
                                    <option value="Lainnya">Lainnya</option>
                                </select>
                            </div>
                        </div>

                        {{-- Invoice --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nomor Invoice (Opsional)</label>
                            <input type="text" name="nomor_invoice" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm" placeholder="Contoh: INV-2023001">
                        </div>

                        {{-- Jumlah Uang --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nilai (Rp)</label>
                            <div class="relative rounded-md shadow-sm">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                  <span class="text-gray-500 sm:text-sm">Rp</span>
                                </div>
                                <input type="number" name="jumlah" class="w-full pl-10 border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm" placeholder="0" required>
                            </div>
                        </div>

                        {{-- Keterangan --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Keterangan Detail</label>
                            <textarea name="keterangan" rows="2" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm" placeholder="Catatan tambahan..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none sm:ml-3 sm:w-auto sm:text-sm">
                        Simpan
                    </button>
                    <button type="button" onclick="closeModal('modalCreate')" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                        Batal
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- MODAL EDIT (DINAMIS via JS) --}}
<div id="modalEdit" class="fixed inset-0 z-50 hidden overflow-y-auto">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75" onclick="closeModal('modalEdit')"></div>
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg w-full">
            <form id="formEdit" method="POST">
                @csrf
                @method('PUT')
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Edit Transaksi</h3>
                    <div class="grid grid-cols-1 gap-4">
                        {{-- Field sama persis dengan Create, diberi ID agar bisa diisi JS --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Tanggal</label>
                            <input type="date" id="edit_tanggal" name="tanggal" class="w-full border-gray-300 rounded-md shadow-sm text-sm" required>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Jenis</label>
                                <select id="edit_jenis" name="jenis" class="w-full border-gray-300 rounded-md shadow-sm text-sm" required>
                                    <option value="Pemasukan">Pemasukan</option>
                                    <option value="Pengeluaran">Pengeluaran</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Kategori</label>
                                <select id="edit_kategori" name="kategori" class="w-full border-gray-300 rounded-md shadow-sm text-sm" required>
                                    <option value="Ekspedisi">Ekspedisi</option>
                                    <option value="Marketplace">Marketplace</option>
                                    <option value="PPOB">PPOB</option>
                                    <option value="Operasional">Operasional</option>
                                    <option value="Lainnya">Lainnya</option>
                                </select>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Nomor Invoice</label>
                            <input type="text" id="edit_invoice" name="nomor_invoice" class="w-full border-gray-300 rounded-md shadow-sm text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Nilai (Rp)</label>
                            <input type="number" id="edit_jumlah" name="jumlah" class="w-full border-gray-300 rounded-md shadow-sm text-sm" required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Keterangan</label>
                            <textarea id="edit_keterangan" name="keterangan" rows="2" class="w-full border-gray-300 rounded-md shadow-sm text-sm"></textarea>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse">
                    <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-yellow-500 text-white font-medium hover:bg-yellow-600 sm:ml-3 sm:w-auto text-sm">Update</button>
                    <button type="button" onclick="closeModal('modalEdit')" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-gray-700 font-medium hover:bg-gray-50 sm:mt-0 sm:ml-3 sm:w-auto text-sm">Batal</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- SCRIPT SEDERHANA UNTUK MODAL --}}
<script>
    function openModal(id) {
        document.getElementById(id).classList.remove('hidden');
    }

    function closeModal(id) {
        document.getElementById(id).classList.add('hidden');
    }

    function editData(data) {
        // Isi form modal edit dengan data JSON yang dikirim
        document.getElementById('edit_tanggal').value = data.tanggal;
        document.getElementById('edit_jenis').value = data.jenis;
        document.getElementById('edit_kategori').value = data.kategori;
        document.getElementById('edit_invoice').value = data.nomor_invoice;
        document.getElementById('edit_jumlah').value = data.jumlah;
        document.getElementById('edit_keterangan').value = data.keterangan;

        // Set action form URL
        let url = "{{ route('admin.keuangan.update', ':id') }}";
        url = url.replace(':id', data.id);
        document.getElementById('formEdit').action = url;

        openModal('modalEdit');
    }
</script>

@endsection