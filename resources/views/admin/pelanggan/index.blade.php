@extends('layouts.admin')

@section('title', 'Data Pelanggan')
@section('page-title', 'Manajemen Data Pelanggan')

@section('content')
<div class="bg-white p-6 rounded-lg shadow-md">
    <!-- Header: Search and Actions -->
    <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
        <form action="{{ route('admin.pelanggan.index') }}" method="GET" class="relative w-full md:w-1/3">
            <input type="text" name="search" class="w-full pl-10 pr-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="Cari ID, Nama, atau No. WA..." value="{{ request('search') }}">
            <div class="absolute top-0 left-0 inline-flex items-center p-2 h-full text-gray-400">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
            </div>
        </form>
        <div class="flex items-center gap-2 w-full md:w-auto justify-end">
            {{-- Tombol import/export bisa diaktifkan nanti --}}
            <button type="button" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm font-medium" disabled>Import/Export</button>
            <button type="button" onclick="openAddModal()" class="bg-indigo-600 text-white px-4 py-2 rounded-lg text-sm font-medium">Tambah Pelanggan</button>
        </div>
    </div>

    {{-- Notifikasi --}}
    @if (session('success'))
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4" role="alert"><p>{{ session('success') }}</p></div>
    @endif
    @if ($errors->any())
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4" role="alert">
            <p class="font-bold">Terjadi Kesalahan</p>
            <ul>@foreach ($errors->all() as $error)<li>- {{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <!-- Tabel Data Pelanggan -->
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID Pelanggan</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama Pelanggan</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">No. WA</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Alamat</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Keterangan</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse ($pelanggans as $pelanggan)
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $pelanggan->id_pelanggan }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-800">{{ $pelanggan->nama_pelanggan }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $pelanggan->nomor_wa ?? '-' }}</td>
                    <td class="px-6 py-4 text-sm text-gray-500 max-w-xs truncate" title="{{ $pelanggan->alamat }}">{{ $pelanggan->alamat }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $pelanggan->keterangan ?? '-' }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                        <div class="flex items-center space-x-3">
                            <button onclick="openEditModal({{ $pelanggan->id }})" class="text-gray-500 hover:text-blue-600">Edit</button>
                            <form action="{{ route('admin.pelanggan.destroy', $pelanggan->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus pelanggan ini?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-gray-500 hover:text-red-600">Hapus</button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center py-4 text-gray-500">Data pelanggan tidak ditemukan.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $pelanggans->appends(request()->query())->links() }}</div>
</div>

<!-- Modal Tambah/Edit Pelanggan -->
<div id="pelangganModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 z-50 hidden">
    <div class="relative top-10 mx-auto p-5 border w-full max-w-lg shadow-lg rounded-md bg-white">
        <form id="pelangganForm" action="" method="POST">
            @csrf
            <input type="hidden" name="_method" id="formMethod" value="POST">
            <h3 id="modalTitle" class="text-xl font-semibold mb-4"></h3>
            <div class="space-y-4">
                <div><label class="block text-sm font-medium text-gray-700">ID Pelanggan</label><input type="text" id="id_pelanggan" name="id_pelanggan" class="mt-1 block w-full border rounded-md p-2 shadow-sm" required></div>
                <div><label class="block text-sm font-medium text-gray-700">Nama Pelanggan</label><input type="text" id="nama_pelanggan" name="nama_pelanggan" class="mt-1 block w-full border rounded-md p-2 shadow-sm" required></div>
                <div><label class="block text-sm font-medium text-gray-700">Nomor WA</label><input type="text" id="nomor_wa" name="nomor_wa" class="mt-1 block w-full border rounded-md p-2 shadow-sm"></div>
                <div><label class="block text-sm font-medium text-gray-700">Alamat</label><textarea id="alamat" name="alamat" rows="3" class="mt-1 block w-full border rounded-md p-2 shadow-sm" required></textarea></div>
                <div><label class="block text-sm font-medium text-gray-700">Keterangan</label><input type="text" id="keterangan" name="keterangan" class="mt-1 block w-full border rounded-md p-2 shadow-sm"></div>
            </div>
            <div class="flex justify-end gap-3 mt-6">
                <button type="button" onclick="closeModal('pelangganModal')" class="bg-gray-200 px-4 py-2 rounded-md">Batal</button>
                <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-md">Simpan</button>
            </div>
        </form>
    </div>
</div>

<script>
function openModal(id) { document.getElementById(id).classList.remove('hidden'); }
function closeModal(id) { document.getElementById(id).classList.add('hidden'); }

function openAddModal() {
    const form = document.getElementById('pelangganForm');
    form.reset();
    form.action = "{{ route('admin.pelanggan.store') }}";
    document.getElementById('formMethod').value = 'POST';
    document.getElementById('modalTitle').innerText = 'Tambah Pelanggan Baru';
    openModal('pelangganModal');
}

async function openEditModal(id) {
    const form = document.getElementById('pelangganForm');
    form.reset();
    try {
        // Pastikan URL ini sesuai dengan setup rute Anda
        const response = await fetch(`/admin/pelanggan/${id}`);
        if (!response.ok) throw new Error('Gagal mengambil data dari server.');
        const pelanggan = await response.json();
        
        document.getElementById('id_pelanggan').value = pelanggan.id_pelanggan;
        document.getElementById('nama_pelanggan').value = pelanggan.nama_pelanggan;
        document.getElementById('nomor_wa').value = pelanggan.nomor_wa;
        document.getElementById('alamat').value = pelanggan.alamat;
        document.getElementById('keterangan').value = pelanggan.keterangan;
        
        form.action = `/admin/pelanggan/${id}`;
        document.getElementById('formMethod').value = 'PUT';
        document.getElementById('modalTitle').innerText = 'Edit Data Pelanggan';
        openModal('pelangganModal');
    } catch (error) {
        console.error('Error:', error);
        alert('Gagal memuat data untuk diedit.');
    }
}
</script>
@endsection

