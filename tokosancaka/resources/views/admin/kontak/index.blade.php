{{-- resources/views/admin/kontak/index.blade.php --}}

@extends('layouts.admin')

@section('title', 'Data Kontak')
@section('page-title', 'Buku Alamat (Pengirim & Penerima)')

@section('content')
<div class="bg-white p-6 rounded-lg shadow-md">
    <!-- Header: Search, Filter, and Actions -->
    <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
        <form action="{{ route('admin.kontak.index') }}" method="GET" class="relative w-full md:w-1/3">
            <input type="text" name="search" class="w-full pl-10 pr-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="Cari Nama atau No. HP..." value="{{ request('search') }}">
            <div class="absolute top-0 left-0 inline-flex items-center p-2 h-full text-gray-400">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
            </div>
        </form>
        <div class="flex items-center gap-2 w-full md:w-auto justify-end">
            <button type="button" onclick="openModal('importModal')" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm font-medium">Import/Export</button>
            <button type="button" onclick="openAddModal()" class="bg-indigo-600 text-white px-4 py-2 rounded-lg text-sm font-medium">Tambah Kontak</button>
        </div>
    </div>
    
    <!-- Filter by Tipe -->
    <div class="flex flex-wrap gap-2 mb-4 border-b pb-4">
        <a href="{{ route('admin.kontak.index', ['filter' => 'Semua']) }}" class="px-3 py-1 text-sm font-medium rounded-full {{ !request('filter') || request('filter') == 'Semua' ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-600 hover:bg-gray-300' }}">Semua</a>
        <a href="{{ route('admin.kontak.index', ['filter' => 'Pengirim']) }}" class="px-3 py-1 text-sm font-medium rounded-full {{ request('filter') == 'Pengirim' ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-600 hover:bg-gray-300' }}">Pengirim</a>
        <a href="{{ route('admin.kontak.index', ['filter' => 'Penerima']) }}" class="px-3 py-1 text-sm font-medium rounded-full {{ request('filter') == 'Penerima' ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-600 hover:bg-gray-300' }}">Penerima</a>
        <a href="{{ route('admin.kontak.index', ['filter' => 'Keduanya']) }}" class="px-3 py-1 text-sm font-medium rounded-full {{ request('filter') == 'Keduanya' ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-600 hover:bg-gray-300' }}">Keduanya</a>
    </div>

    {{-- Notifikasi --}}
    @if (session('success'))
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4" role="alert"><p>{{ session('success') }}</p></div>
    @endif
    @if ($errors->any())
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4" role="alert">
            <ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <!-- Tabel Data Kontak -->
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">No. HP</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Alamat</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tipe</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse ($kontaks as $kontak)
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $kontak->nama }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $kontak->no_hp }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 max-w-xs truncate" title="{{ $kontak->alamat }}">{{ $kontak->alamat }}</td>
                    <td class="px-6 py-4 whitespace-nowrap"><span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">{{ $kontak->tipe }}</span></td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                        <div class="flex items-center space-x-3">
                            <button onclick="openEditModal({{ $kontak->id }})" class="text-gray-500 hover:text-blue-600">Edit</button>
                            <form action="{{ route('admin.kontak.destroy', $kontak->id) }}" method="POST" onsubmit="return confirm('Yakin hapus kontak ini?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-gray-500 hover:text-red-600">Hapus</button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="text-center py-4 text-gray-500">Data kontak tidak ditemukan.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $kontaks->appends(request()->query())->links() }}</div>
</div>

<!-- Modal Tambah/Edit Kontak -->
<div id="kontakModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 z-50 hidden">
    <div class="relative top-10 mx-auto p-5 border w-full max-w-lg shadow-lg rounded-md bg-white">
        <form id="kontakForm" action="" method="POST">
            @csrf
            <input type="hidden" name="_method" id="formMethod" value="POST">
            <h3 id="modalTitle" class="text-xl font-semibold mb-4"></h3>
            <div class="space-y-4">
                <div><label class="block text-sm">Nama</label><input type="text" id="nama" name="nama" class="w-full border rounded-md p-2" required></div>
                <div><label class="block text-sm">No. HP</label><input type="text" id="no_hp" name="no_hp" class="w-full border rounded-md p-2" required></div>
                <div><label class="block text-sm">Alamat</label><textarea id="alamat" name="alamat" class="w-full border rounded-md p-2" required></textarea></div>
                <div><label class="block text-sm">Tipe</label><select id="tipe" name="tipe" class="w-full border rounded-md p-2"><option>Pengirim</option><option>Penerima</option><option>Keduanya</option></select></div>
            </div>
            <div class="flex justify-end gap-3 mt-6">
                <button type="button" onclick="closeModal('kontakModal')" class="bg-gray-200 px-4 py-2 rounded-md">Batal</button>
                <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-md">Simpan</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Import/Export -->
<div id="importModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 z-50 hidden">
    <div class="relative top-20 mx-auto p-5 border w-full max-w-lg shadow-lg rounded-md bg-white">
        <h3 class="text-xl font-semibold mb-4">Import & Export Data Kontak</h3>
        <div class="space-y-4">
            <div>
                <h4 class="font-medium mb-2">Export Data</h4>
                <div class="flex gap-3">
                    <a href="{{ route('admin.kontak.export.excel') }}" class="flex-1 text-center bg-green-600 text-white p-3 rounded-lg">Export Excel</a>
                    <a href="{{ route('admin.kontak.export.pdf') }}" class="flex-1 text-center bg-red-600 text-white p-3 rounded-lg">Export PDF</a>
                    <button onclick="window.print()" class="flex-1 text-center bg-blue-600 text-white p-3 rounded-lg">Print</button>
                </div>
            </div>
            <form action="{{ route('admin.kontak.import.excel') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="border-t pt-4 mt-4">
                    <h4 class="font-medium mb-2">Import Data dari Excel</h4>
                    <p class="text-sm text-gray-500 mb-2">Pastikan file Excel Anda memiliki kolom: nama, no_hp, alamat, tipe.</p>
                    <input type="file" name="file" class="w-full border p-2 rounded-md" required>
                </div>
                <div class="flex justify-end gap-3 mt-6">
                    <button type="button" onclick="closeModal('importModal')" class="bg-gray-200 px-4 py-2 rounded-md">Tutup</button>
                    <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-md">Proses Import</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openModal(id) { document.getElementById(id).classList.remove('hidden'); }
function closeModal(id) { document.getElementById(id).classList.add('hidden'); }

function openAddModal() {
    const form = document.getElementById('kontakForm');
    form.reset();
    form.action = "{{ route('admin.kontak.store') }}";
    document.getElementById('formMethod').value = 'POST';
    document.getElementById('modalTitle').innerText = 'Tambah Kontak Baru';
    openModal('kontakModal');
}

async function openEditModal(id) {
    const form = document.getElementById('kontakForm');
    form.reset();
    try {
        const response = await fetch(`/kontak/${id}`);
        if (!response.ok) throw new Error('Network response was not ok.');
        const kontak = await response.json();
        
        document.getElementById('nama').value = kontak.nama;
        document.getElementById('no_hp').value = kontak.no_hp;
        document.getElementById('alamat').value = kontak.alamat;
        document.getElementById('tipe').value = kontak.tipe;
        
        form.action = `/kontak/${id}`;
        document.getElementById('formMethod').value = 'PUT';
        document.getElementById('modalTitle').innerText = 'Edit Kontak';
        openModal('kontakModal');
    } catch (error) {
        console.error('Gagal mengambil data kontak:', error);
        alert('Gagal memuat data untuk diedit.');
    }
}
</script>
@endsection
