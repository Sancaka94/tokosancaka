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
            <button type="button" onclick="openModal('importExportModal')" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm font-medium">Import/Export</button>
            <button type="button" onclick="openAddModal()" class="bg-indigo-600 text-white px-4 py-2 rounded-lg text-sm font-medium">Tambah Pelanggan</button>
        </div>
    </div>

    {{-- Container Notifikasi --}}
    <div id="notification-container" class="mb-4">
        @if (session('success'))
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4" role="alert">{{ session('success') }}</div>
        @endif
        @if ($errors->any())
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4" role="alert">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        {{-- Notifikasi dinamis dari JavaScript akan muncul di sini --}}
        <div id="notification" class="hidden p-4 mb-4 rounded-md"></div>
    </div>
    
    <!-- Tabel Data -->
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
                <tr id="pelanggan-{{ $pelanggan->id }}">
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $pelanggan->id_pelanggan }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-800">{{ $pelanggan->nama_pelanggan }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $pelanggan->nomor_wa }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 max-w-xs truncate" title="{{ $pelanggan->alamat }}">{{ $pelanggan->alamat }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $pelanggan->keterangan }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                        <div class="flex items-center space-x-3">
                            <button onclick="openEditModal({{ $pelanggan->id }})" class="text-gray-500 hover:text-blue-600">Edit</button>
                            <button onclick="deletePelanggan({{ $pelanggan->id }})" class="text-gray-500 hover:text-red-600">Hapus</button>
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

<!-- Modal Tambah/Edit -->
<div id="pelangganModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 z-50 hidden flex items-center justify-center">
    <div class="relative p-5 border w-full max-w-lg shadow-lg rounded-md bg-white">
        <form id="pelangganForm">
            {{-- CSRF token akan diambil dari sini --}}
            @csrf
            <input type="hidden" id="pelangganId">
            <h3 id="modalTitle" class="text-xl font-semibold mb-4"></h3>
            <div class="space-y-4">
                <div><label class="block text-sm">ID Pelanggan</label><input type="text" id="id_pelanggan" name="id_pelanggan" class="w-full border rounded-md p-2" required></div>
                <div><label class="block text-sm">Nama Pelanggan</label><input type="text" id="nama_pelanggan" name="nama_pelanggan" class="w-full border rounded-md p-2" required></div>
                <div><label class="block text-sm">No. WA</label><input type="text" id="nomor_wa" name="nomor_wa" class="w-full border rounded-md p-2"></div>
                <div><label class="block text-sm">Alamat</label><textarea id="alamat" name="alamat" class="w-full border rounded-md p-2" required></textarea></div>
                <div><label class="block text-sm">Keterangan</label><input type="text" id="keterangan" name="keterangan" class="w-full border rounded-md p-2"></div>
            </div>
            <div class="flex justify-end gap-3 mt-6">
                <button type="button" onclick="closeModal('pelangganModal')" class="bg-gray-200 px-4 py-2 rounded-md">Batal</button>
                <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-md">Simpan</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Import/Export -->
<div id="importExportModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 z-50 hidden flex items-center justify-center">
    <div class="relative p-6 border w-full max-w-lg shadow-lg rounded-md bg-white">
        <h3 class="text-xl font-semibold mb-4">Import & Export Data Pelanggan</h3>
        <div class="mb-6">
            <h4 class="font-medium mb-2">Export Data</h4>
            <div class="flex gap-3">
                <a href="{{ route('admin.pelanggan.export.excel') }}" class="flex-1 text-center bg-green-600 text-white p-3 rounded-lg hover:bg-green-700"><i class="fa-solid fa-file-excel mr-2"></i>Export Excel</a>
                <a href="{{ route('admin.pelanggan.export.pdf') }}" class="flex-1 text-center bg-red-600 text-white p-3 rounded-lg hover:bg-red-700"><i class="fa-solid fa-file-pdf mr-2"></i>Export PDF</a>
            </div>
        </div>
        <form action="{{ route('admin.pelanggan.import.excel') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="border-t pt-4">
                <h4 class="font-medium mb-2">Import Data dari Excel</h4>
                <p class="text-sm text-gray-500 mb-2">Pastikan file Excel Anda memiliki kolom: id_pelanggan, nama_pelanggan, nomor_wa, alamat, keterangan.</p>
                <input type="file" name="file" class="w-full border p-2 rounded-md" required>
            </div>
            <div class="flex justify-end gap-3 mt-6">
                <button type="button" onclick="closeModal('importExportModal')" class="bg-gray-200 px-4 py-2 rounded-md">Tutup</button>
                <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-md">Proses Import</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
// ======================================================= //
// == SCRIPT BARU YANG TELAH DIPERBAIKI DAN DISEMPURNAKAN == //
// ======================================================= //

// Fungsi dasar untuk modal
function openModal(id) { document.getElementById(id).classList.remove('hidden'); }
function closeModal(id) { document.getElementById(id).classList.add('hidden'); }

// Fungsi untuk menampilkan notifikasi dinamis
function showNotification(message, type = 'success') {
    const notificationDiv = document.getElementById('notification');
    const successClasses = 'bg-green-100 border-l-4 border-green-500 text-green-700';
    const errorClasses = 'bg-red-100 border-l-4 border-red-500 text-red-700';

    notificationDiv.innerHTML = `<p>${message}</p>`;
    notificationDiv.className = `p-4 mb-4 rounded-md ${type === 'success' ? successClasses : errorClasses}`;
    notificationDiv.classList.remove('hidden');

    setTimeout(() => { notificationDiv.classList.add('hidden'); }, 5000);
}

// Membuka modal untuk Tambah Data
function openAddModal() {
    document.getElementById('pelangganForm').reset();
    document.getElementById('modalTitle').innerText = 'Tambah Pelanggan Baru';
    document.getElementById('pelangganId').value = '';
    openModal('pelangganModal');
}

// Membuka modal untuk Edit Data
async function openEditModal(id) {
    try {
        const response = await fetch(`/admin/pelanggan/${id}`);
        if (!response.ok) throw new Error('Gagal mengambil data');
        const pelanggan = await response.json();

        document.getElementById('pelangganForm').reset();
        document.getElementById('modalTitle').innerText = 'Edit Pelanggan';
        document.getElementById('pelangganId').value = id;

        document.getElementById('id_pelanggan').value = pelanggan.id_pelanggan;
        document.getElementById('nama_pelanggan').value = pelanggan.nama_pelanggan;
        document.getElementById('nomor_wa').value = pelanggan.nomor_wa;
        document.getElementById('alamat').value = pelanggan.alamat;
        document.getElementById('keterangan').value = pelanggan.keterangan;

        openModal('pelangganModal');
    } catch (error) {
        showNotification('Gagal memuat data untuk diedit.', 'error');
    }
}

// Menangani submit form (Tambah & Edit)
document.getElementById('pelangganForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const id = document.getElementById('pelangganId').value;
    const method = id ? 'PUT' : 'POST';
    const url = id ? `/admin/pelanggan/${id}` : "{{ route('admin.pelanggan.store') }}";
    
    const formData = new FormData(this);
    const data = Object.fromEntries(formData.entries());

    try {
        const response = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': data._token,
                'X-HTTP-Method-Override': method
            },
            body: JSON.stringify(data),
        });

        const result = await response.json();

        if (response.ok) {
            closeModal('pelangganModal');
            showNotification(id ? 'Data pelanggan berhasil diperbarui.' : 'Data pelanggan berhasil ditambahkan.');
            
            if (id) {
                updateTableRow(result);
            } else {
                addTableRow(result);
            }
        } else {
            let errorMessages = '<ul>';
            for (const key in result.errors) {
                errorMessages += `<li>${result.errors[key][0]}</li>`;
            }
            errorMessages += '</ul>';
            showNotification(errorMessages, 'error');
        }
    } catch (error) {
        showNotification('Terjadi kesalahan. Silakan coba lagi.', 'error');
    }
});

// Fungsi untuk memperbarui baris tabel secara dinamis
function updateTableRow(pelanggan) {
    const row = document.getElementById(`pelanggan-${pelanggan.id}`);
    if (row) {
        row.cells[0].textContent = pelanggan.id_pelanggan;
        row.cells[1].textContent = pelanggan.nama_pelanggan;
        row.cells[2].textContent = pelanggan.nomor_wa || '';
        row.cells[3].textContent = pelanggan.alamat;
        row.cells[3].title = pelanggan.alamat;
        row.cells[4].textContent = pelanggan.keterangan || '';
    }
}

// Fungsi untuk menambah baris tabel baru secara dinamis
function addTableRow(pelanggan) {
    const tableBody = document.querySelector('tbody');
    const emptyRow = tableBody.querySelector('td[colspan="6"]');
    if (emptyRow) {
        emptyRow.parentElement.remove();
    }
    const newRow = `
        <tr id="pelanggan-${pelanggan.id}">
            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">${pelanggan.id_pelanggan}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-800">${pelanggan.nama_pelanggan}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${pelanggan.nomor_wa || ''}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 max-w-xs truncate" title="${pelanggan.alamat}">${pelanggan.alamat}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${pelanggan.keterangan || ''}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                <div class="flex items-center space-x-3">
                    <button onclick="openEditModal(${pelanggan.id})" class="text-gray-500 hover:text-blue-600">Edit</button>
                    <button onclick="deletePelanggan(${pelanggan.id})" class="text-gray-500 hover:text-red-600">Hapus</button>
                </div>
            </td>
        </tr>`;
    tableBody.insertAdjacentHTML('afterbegin', newRow);
}

// Fungsi untuk menghapus data secara dinamis
async function deletePelanggan(id) {
    if (!confirm('Apakah Anda yakin ingin menghapus data ini?')) return;

    try {
        const csrfToken = document.querySelector('input[name="_token"]').value;
        const response = await fetch(`/admin/pelanggan/${id}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-HTTP-Method-Override': 'DELETE'
            },
            body: JSON.stringify({ _method: 'DELETE' })
        });
        
        const result = await response.json();
        if (response.ok) {
            document.getElementById(`pelanggan-${id}`).remove();
            showNotification(result.success);
        } else {
            showNotification(result.message || 'Gagal menghapus data.', 'error');
        }
    } catch (error) {
        showNotification('Terjadi kesalahan. Silakan coba lagi.', 'error');
    }
}
</script>
@endpush
@endsection

