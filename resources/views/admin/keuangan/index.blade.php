@extends('layouts.admin')

@section('title', 'Laporan Keuangan & Profit')

@section('content')
<div class="container mx-auto px-4 py-6">

    {{-- ALERT NOTIFIKASI --}}
    @if(session('success'))
    <div x-data="{ show: true }" x-show="show" class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative flex justify-between items-center">
        <span><i class="fas fa-check-circle me-2"></i> {{ session('success') }}</span>
        <button @click="show = false" class="text-green-700 hover:text-green-900"><i class="fas fa-times"></i></button>
    </div>
    @endif
    @if(session('error'))
    <div x-data="{ show: true }" x-show="show" class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative flex justify-between items-center">
        <span><i class="fas fa-exclamation-circle me-2"></i> {{ session('error') }}</span>
        <button @click="show = false" class="text-red-700 hover:text-red-900"><i class="fas fa-times"></i></button>
    </div>
    @endif

    {{-- 1. CARD RINGKASAN GLOBAL (DASHBOARD) --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-white rounded-xl shadow-md p-5 border-l-4 border-blue-500 hover:shadow-lg transition">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-xs text-gray-500 font-bold uppercase tracking-wider">Total Omzet</p>
                    <h3 class="text-2xl font-bold text-blue-700 mt-1">Rp{{ number_format($totalOmzet, 0, ',', '.') }}</h3>
                    <p class="text-[10px] text-gray-400 mt-1">Total Pemasukan Kotor</p>
                </div>
                <div class="bg-blue-100 p-3 rounded-full text-blue-600 shadow-sm">
                    <i class="fas fa-chart-line text-xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-md p-5 border-l-4 border-red-500 hover:shadow-lg transition">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-xs text-gray-500 font-bold uppercase tracking-wider">Total Modal</p>
                    <h3 class="text-2xl font-bold text-red-700 mt-1">Rp{{ number_format($totalModal, 0, ',', '.') }}</h3>
                    <p class="text-[10px] text-gray-400 mt-1">Pengeluaran & Modal Transaksi</p>
                </div>
                <div class="bg-red-100 p-3 rounded-full text-red-600 shadow-sm">
                    <i class="fas fa-wallet text-xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-md p-5 border-l-4 border-green-500 hover:shadow-lg transition">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-xs text-gray-500 font-bold uppercase tracking-wider">Total Profit Bersih</p>
                    <h3 class="text-2xl font-bold text-green-700 mt-1">Rp{{ number_format($totalProfit, 0, ',', '.') }}</h3>
                    <p class="text-[10px] text-gray-400 mt-1">Omzet - Modal (Netto)</p>
                </div>
                <div class="bg-green-100 p-3 rounded-full text-green-600 shadow-sm">
                    <i class="fas fa-coins text-xl"></i>
                </div>
            </div>
        </div>
    </div>

    {{-- 2. HEADER KONTEN & FILTER --}}
    <div class="bg-white rounded-lg shadow mb-6">
        <div class="p-4 border-b border-gray-200 flex flex-col md:flex-row justify-between items-center gap-4">
            <h2 class="text-lg font-bold text-gray-700 flex items-center">
                <i class="fas fa-table me-2 text-gray-400"></i> Rincian Transaksi
            </h2>
            
            <div class="flex flex-col md:flex-row gap-2 w-full md:w-auto">
                {{-- Form Pencarian --}}
                <form action="{{ route('admin.keuangan.index') }}" method="GET" class="relative w-full md:w-64">
                    <input type="text" name="search" value="{{ request('search') }}" 
                        class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 text-sm transition" 
                        placeholder="Cari Invoice / Resi...">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                        <i class="fas fa-search"></i>
                    </div>
                </form>

                {{-- Tombol Tambah Manual --}}
                <button onclick="openModal('modalCreate')" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition shadow-sm flex items-center justify-center gap-2">
                    <i class="fas fa-plus"></i> <span class="hidden md:inline">Input Manual</span>
                </button>
            </div>
        </div>

        {{-- 3. TABEL DATA --}}
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left text-gray-600">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50 border-b">
                    <tr>
                        <th class="px-4 py-3 font-bold text-center w-10">No</th>
                        <th class="px-4 py-3 font-bold">Tanggal</th>
                        <th class="px-4 py-3 font-bold">Kategori</th>
                        <th class="px-4 py-3 font-bold">Keterangan / Invoice</th>
                        {{-- Kolom Keuangan --}}
                        <th class="px-4 py-3 font-bold text-right text-blue-700 bg-blue-50 border-l">Omzet</th>
                        <th class="px-4 py-3 font-bold text-right text-red-700 bg-red-50 border-l">Modal</th>
                        <th class="px-4 py-3 font-bold text-right text-green-700 bg-green-50 border-l">Profit</th>
                        <th class="px-4 py-3 font-bold text-center w-20">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($transaksi as $index => $item)
                    <tr class="hover:bg-gray-50 transition group">
                        
                        {{-- NO --}}
                        <td class="px-4 py-3 text-center">
                            {{ $transaksi->firstItem() + $index }}
                        </td>

                        {{-- TANGGAL --}}
                        <td class="px-4 py-3 whitespace-nowrap text-xs text-gray-500">
                            {{ \Carbon\Carbon::parse($item->tanggal)->format('d M Y') }}
                            <div class="text-[10px] text-gray-400">{{ \Carbon\Carbon::parse($item->tanggal)->format('H:i') }}</div>
                        </td>

                        {{-- KATEGORI (BADGE WARNA) --}}
                        <td class="px-4 py-3">
                            @php
                                $cat = strtolower($item->kategori);
                                $badgeClass = 'bg-gray-100 text-gray-600 border-gray-200'; // Default Manual
                                $icon = 'fa-file-alt';

                                if(str_contains($cat, 'ppob')) {
                                    $badgeClass = 'bg-purple-100 text-purple-700 border-purple-200';
                                    $icon = 'fa-mobile-screen';
                                } elseif(str_contains($cat, 'ekspedisi')) {
                                    $badgeClass = 'bg-yellow-100 text-yellow-800 border-yellow-200';
                                    $icon = 'fa-truck';
                                } elseif(str_contains($cat, 'marketplace')) {
                                    $badgeClass = 'bg-orange-100 text-orange-700 border-orange-200';
                                    $icon = 'fa-store';
                                } elseif(str_contains($cat, 'top up')) {
                                    $badgeClass = 'bg-cyan-100 text-cyan-700 border-cyan-200';
                                    $icon = 'fa-wallet';
                                }
                            @endphp
                            <span class="inline-flex items-center px-2 py-1 rounded text-[10px] font-medium border {{ $badgeClass }}">
                                <i class="fas {{ $icon }} me-1.5"></i> {{ $item->kategori }}
                            </span>
                        </td>

                        {{-- KETERANGAN & INVOICE --}}
                        <td class="px-4 py-3">
                            @if($item->nomor_invoice)
                                <div class="font-mono font-bold text-gray-800 text-xs mb-0.5">{{ $item->nomor_invoice }}</div>
                            @endif
                            <div class="text-xs text-gray-500 truncate max-w-[250px]" title="{{ $item->keterangan }}">
                                {{ $item->keterangan ?? '-' }}
                            </div>
                        </td>

                        {{-- OMZET --}}
                        <td class="px-4 py-3 text-right bg-blue-50 group-hover:bg-blue-100 transition border-l border-blue-100">
                            <span class="font-medium text-blue-700 whitespace-nowrap">
                                {{ number_format($item->omzet, 0, ',', '.') }}
                            </span>
                        </td>

                        {{-- MODAL --}}
                        <td class="px-4 py-3 text-right bg-red-50 group-hover:bg-red-100 transition border-l border-red-100">
                            <span class="font-medium text-red-700 whitespace-nowrap">
                                {{ number_format($item->modal, 0, ',', '.') }}
                            </span>
                        </td>

                        {{-- PROFIT --}}
                        <td class="px-4 py-3 text-right bg-green-50 group-hover:bg-green-100 transition border-l border-green-100">
                            <span class="font-bold whitespace-nowrap {{ $item->profit >= 0 ? 'text-green-700' : 'text-red-600' }}">
                                {{ number_format($item->profit, 0, ',', '.') }}
                            </span>
                        </td>

                        {{-- AKSI --}}
                        <td class="px-4 py-3 text-center">
                            {{-- Deteksi jika data manual berdasarkan ID yang ada di tabel keuangans --}}
                            {{-- Karena kita pakai Union, kita asumsikan 'id' bisa duplikat. --}}
                            {{-- Logic View: Hanya tampilkan tombol edit/hapus jika bukan kategori Otomatis --}}
                            @php
                                $isAuto = in_array($item->kategori, ['PPOB', 'Ekspedisi', 'Top Up Saldo', 'Marketplace']);
                            @endphp

                            @if(!$isAuto)
                                <div class="inline-flex rounded shadow-sm" role="group">
                                    <button onclick='editData(@json($item))' class="bg-yellow-400 hover:bg-yellow-500 text-white px-2 py-1 text-xs rounded-l border-r border-yellow-500 transition" title="Edit Manual">
                                        <i class="fas fa-pencil-alt"></i>
                                    </button>
                                    <form action="{{ route('admin.keuangan.destroy', $item->id) }}" method="POST" onsubmit="return confirm('Yakin hapus data manual ini? Data tidak bisa dikembalikan.')" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="bg-red-600 hover:bg-red-700 text-white px-2 py-1 text-xs rounded-r transition" title="Hapus Manual">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </form>
                                </div>
                            @else
                                <span class="text-gray-300 cursor-not-allowed" title="Data Otomatis (Tidak bisa diedit manual)">
                                    <i class="fas fa-lock"></i>
                                </span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-6 py-8 text-center text-gray-400">
                            <div class="flex flex-col items-center justify-center">
                                <i class="fas fa-folder-open text-4xl mb-3 text-gray-300"></i>
                                <p>Belum ada data transaksi.</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>

                {{-- FOOTER TABLE (SUBTOTAL HALAMAN) --}}
                <tfoot class="bg-gray-100 border-t-2 border-gray-300 font-bold text-xs">
                    <tr>
                        <td colspan="4" class="px-4 py-3 text-right text-gray-600 uppercase tracking-wide">
                            Subtotal (Halaman Ini):
                        </td>
                        <td class="px-4 py-3 text-right text-blue-800">
                            Rp{{ number_format($transaksi->sum('omzet'), 0, ',', '.') }}
                        </td>
                        <td class="px-4 py-3 text-right text-red-800">
                            Rp{{ number_format($transaksi->sum('modal'), 0, ',', '.') }}
                        </td>
                        <td class="px-4 py-3 text-right text-green-800">
                            Rp{{ number_format($transaksi->sum('profit'), 0, ',', '.') }}
                        </td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
        
        {{-- PAGINATION --}}
        @if($transaksi->hasPages())
        <div class="p-4 border-t border-gray-200">
            {{ $transaksi->withQueryString()->links() }}
        </div>
        @endif
    </div>
</div>

{{-- ================================================================= --}}
{{-- MODAL CREATE (INPUT MANUAL) --}}
{{-- ================================================================= --}}
<div id="modalCreate" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        {{-- Overlay --}}
        <div class="fixed inset-0 bg-gray-900 bg-opacity-60 transition-opacity backdrop-blur-sm" onclick="closeModal('modalCreate')"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        
        {{-- Modal Content --}}
        <div class="inline-block align-bottom bg-white rounded-xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg w-full">
            <form action="{{ route('admin.keuangan.store') }}" method="POST">
                @csrf
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6">
                    <div class="flex justify-between items-center mb-4 border-b pb-3">
                        <h3 class="text-lg leading-6 font-bold text-gray-900" id="modal-title">
                            <i class="fas fa-plus-circle text-blue-600 me-2"></i> Input Transaksi Manual
                        </h3>
                        <button type="button" onclick="closeModal('modalCreate')" class="text-gray-400 hover:text-gray-500">
                            <i class="fas fa-times text-lg"></i>
                        </button>
                    </div>
                    
                    <div class="space-y-4">
                        {{-- Tanggal --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Transaksi</label>
                            <input type="date" name="tanggal" value="{{ date('Y-m-d') }}" class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm" required>
                        </div>
                        
                        {{-- Jenis & Kategori --}}
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Jenis</label>
                                <select name="jenis" class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm bg-gray-50" required>
                                    <option value="Pemasukan">Pemasukan (+)</option>
                                    <option value="Pengeluaran">Pengeluaran (-)</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Kategori</label>
                                <select name="kategori" class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm bg-gray-50" required>
                                    <option value="Operasional">Operasional</option>
                                    <option value="Gaji">Gaji Karyawan</option>
                                    <option value="Aset">Pembelian Aset</option>
                                    <option value="Lainnya">Lainnya</option>
                                    <option disabled>──────────</option>
                                    <option value="Ekspedisi">Ekspedisi (Manual)</option>
                                    <option value="Marketplace">Marketplace (Manual)</option>
                                </select>
                            </div>
                        </div>

                        {{-- Invoice --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">No. Invoice / Ref (Opsional)</label>
                            <input type="text" name="nomor_invoice" class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm" placeholder="Contoh: INV-MANUAL-001">
                        </div>

                        {{-- Jumlah Uang --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nominal (Rp)</label>
                            <div class="relative rounded-md shadow-sm">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                  <span class="text-gray-500 font-bold sm:text-sm">Rp</span>
                                </div>
                                <input type="number" name="jumlah" class="w-full pl-10 border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500 text-lg font-bold text-gray-800" placeholder="0" required>
                            </div>
                            <p class="text-[10px] text-gray-500 mt-1">* Masukkan angka saja tanpa titik/koma.</p>
                        </div>

                        {{-- Keterangan --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Keterangan / Catatan</label>
                            <textarea name="keterangan" rows="2" class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm" placeholder="Contoh: Beli Kertas Thermal 10 Roll"></textarea>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 flex flex-row-reverse gap-2">
                    <button type="submit" class="w-full sm:w-auto inline-flex justify-center rounded-lg border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none sm:text-sm">
                        <i class="fas fa-save me-2 mt-0.5"></i> Simpan Data
                    </button>
                    <button type="button" onclick="closeModal('modalCreate')" class="w-full sm:w-auto inline-flex justify-center rounded-lg border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-100 focus:outline-none sm:text-sm">
                        Batal
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ================================================================= --}}
{{-- MODAL EDIT (INPUT MANUAL) --}}
{{-- ================================================================= --}}
<div id="modalEdit" class="fixed inset-0 z-50 hidden overflow-y-auto">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-900 bg-opacity-60 transition-opacity backdrop-blur-sm" onclick="closeModal('modalEdit')"></div>
        <div class="inline-block align-bottom bg-white rounded-xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg w-full">
            <form id="formEdit" method="POST">
                @csrf
                @method('PUT')
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6">
                    <div class="flex justify-between items-center mb-4 border-b pb-3">
                        <h3 class="text-lg leading-6 font-bold text-gray-900">
                            <i class="fas fa-pencil-alt text-yellow-500 me-2"></i> Edit Data Manual
                        </h3>
                        <button type="button" onclick="closeModal('modalEdit')" class="text-gray-400 hover:text-gray-500">
                            <i class="fas fa-times text-lg"></i>
                        </button>
                    </div>

                    <div class="space-y-4">
                        {{-- Tanggal --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Tanggal</label>
                            <input type="date" id="edit_tanggal" name="tanggal" class="w-full border-gray-300 rounded-lg shadow-sm text-sm" required>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Jenis</label>
                                <select id="edit_jenis" name="jenis" class="w-full border-gray-300 rounded-lg shadow-sm text-sm" required>
                                    <option value="Pemasukan">Pemasukan</option>
                                    <option value="Pengeluaran">Pengeluaran</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Kategori</label>
                                <select id="edit_kategori" name="kategori" class="w-full border-gray-300 rounded-lg shadow-sm text-sm" required>
                                    <option value="Operasional">Operasional</option>
                                    <option value="Gaji">Gaji</option>
                                    <option value="Aset">Aset</option>
                                    <option value="Lainnya">Lainnya</option>
                                    <option value="Ekspedisi">Ekspedisi</option>
                                    <option value="Marketplace">Marketplace</option>
                                </select>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Nomor Invoice</label>
                            <input type="text" id="edit_invoice" name="nomor_invoice" class="w-full border-gray-300 rounded-lg shadow-sm text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Nominal (Rp)</label>
                            <input type="number" id="edit_jumlah" name="jumlah" class="w-full border-gray-300 rounded-lg shadow-sm text-sm font-bold" required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Keterangan</label>
                            <textarea id="edit_keterangan" name="keterangan" rows="2" class="w-full border-gray-300 rounded-lg shadow-sm text-sm"></textarea>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse gap-2">
                    <button type="submit" class="w-full sm:w-auto inline-flex justify-center rounded-lg border border-transparent shadow-sm px-4 py-2 bg-yellow-500 text-white font-medium hover:bg-yellow-600 sm:text-sm">
                        Update Data
                    </button>
                    <button type="button" onclick="closeModal('modalEdit')" class="w-full sm:w-auto inline-flex justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-gray-700 font-medium hover:bg-gray-100 sm:text-sm">
                        Batal
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    // Logic Modal
    function openModal(id) {
        document.getElementById(id).classList.remove('hidden');
    }

    function closeModal(id) {
        document.getElementById(id).classList.add('hidden');
    }

    // Logic Isi Form Edit
    function editData(data) {
        // Parsing data JSON
        document.getElementById('edit_tanggal').value = data.tanggal.substring(0, 10); // Ambil YYYY-MM-DD
        document.getElementById('edit_jenis').value = data.jenis;
        document.getElementById('edit_kategori').value = data.kategori;
        document.getElementById('edit_invoice').value = data.nomor_invoice;
        
        // Logika Mengisi Jumlah untuk Edit
        // Di tabel gabungan, kita punya 'omzet', 'modal'.
        // Jika Jenis = Pemasukan, ambil nilai omzet. Jika Pengeluaran, ambil modal.
        let amount = 0;
        if(data.jenis === 'Pemasukan') amount = data.omzet;
        else if(data.jenis === 'Pengeluaran') amount = data.modal;
        
        document.getElementById('edit_jumlah').value = amount;
        document.getElementById('edit_keterangan').value = data.keterangan;

        // Set URL Action Form
        let url = "{{ route('admin.keuangan.update', ':id') }}";
        url = url.replace(':id', data.id);
        document.getElementById('formEdit').action = url;

        openModal('modalEdit');
    }
</script>
@endpush

@endsection