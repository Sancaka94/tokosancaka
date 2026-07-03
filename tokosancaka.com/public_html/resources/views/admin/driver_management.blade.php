@extends('layouts.admin')

@section('content')
<div class="w-full px-4 py-6 mx-auto">
    <!-- Header & Title -->
    <div class="flex justify-between items-center mb-6">
        <h3 class="text-2xl font-bold text-gray-800 m-0">Manajemen Pendaftaran Driver</h3>
    </div>

    <!-- Alert Notifications (Menggunakan Alpine.js untuk fitur dismiss) -->
    @if(session('success'))
        <div x-data="{ show: true }" x-show="show" class="flex items-center justify-between p-4 mb-6 text-sm text-green-800 bg-green-50 border border-green-200 rounded-xl shadow-sm">
            <div class="flex items-center gap-2">
                <i class="fa-solid fa-circle-check text-lg"></i>
                <span class="font-medium">{{ session('success') }}</span>
            </div>
            <button @click="show = false" type="button" class="text-green-600 hover:text-green-900 focus:outline-none">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>
    @endif

    @if(session('error'))
        <div x-data="{ show: true }" x-show="show" class="flex items-center justify-between p-4 mb-6 text-sm text-red-800 bg-red-50 border border-red-200 rounded-xl shadow-sm">
            <div class="flex items-center gap-2">
                <i class="fa-solid fa-triangle-exclamation text-lg"></i>
                <span class="font-medium">{{ session('error') }}</span>
            </div>
            <button @click="show = false" type="button" class="text-red-600 hover:text-red-900 focus:outline-none">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>
    @endif

    <!-- Filter & Search Section -->
    <div class="bg-white border border-gray-100 shadow-sm rounded-xl mb-6">
        <div class="p-5">
            <form method="GET" action="{{ route('admin.drivers.index') }}" class="grid grid-cols-1 md:grid-cols-12 gap-4">
                <div class="md:col-span-5">
                    <input type="text" name="search" value="{{ request('search') }}" 
                           class="w-full bg-gray-50 border border-gray-200 text-gray-700 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block px-4 py-2.5 transition-colors" 
                           placeholder="Cari nama atau nomor WA...">
                </div>
                <div class="md:col-span-3">
                    <select name="status" class="w-full bg-gray-50 border border-gray-200 text-gray-700 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block px-4 py-2.5 transition-colors">
                        <option value="">Semua Status</option>
                        <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Approved</option>
                        <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Rejected</option>
                    </select>
                </div>
                <div class="md:col-span-2">
                    <button type="submit" class="w-full flex justify-center items-center gap-2 bg-gray-800 hover:bg-gray-900 text-white font-medium rounded-lg text-sm px-4 py-2.5 transition-colors shadow-sm">
                        <i class="fa-solid fa-filter"></i> Filter
                    </button>
                </div>
                <div class="md:col-span-2">
                    <button type="button" id="btn-bulk-delete" class="w-full flex justify-center items-center gap-2 bg-red-500 hover:bg-red-600 text-white font-medium rounded-lg text-sm px-4 py-2.5 transition-colors shadow-sm">
                        <i class="fa-solid fa-trash-can"></i> Hapus Massal
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Data Table Section -->
    <div class="bg-white border border-gray-100 shadow-sm rounded-xl overflow-hidden mb-6">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left text-gray-600 align-middle">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th scope="col" class="px-6 py-4 w-12">
                            <input type="checkbox" id="checkAll" class="w-4 h-4 text-blue-600 bg-white border-gray-300 rounded focus:ring-blue-500 cursor-pointer">
                        </th>
                        <th scope="col" class="px-6 py-4 font-semibold">Nama Lengkap</th>
                        <th scope="col" class="px-6 py-4 font-semibold">No. WhatsApp</th>
                        <th scope="col" class="px-6 py-4 font-semibold">Status</th>
                        <th scope="col" class="px-6 py-4 font-semibold">Tanggal Daftar</th>
                        <th scope="col" class="px-6 py-4 font-semibold text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($drivers as $driver)
                    <tr class="bg-white hover:bg-gray-50 transition-colors duration-200">
                        <td class="px-6 py-4">
                            <input type="checkbox" value="{{ $driver->id }}" class="driver-checkbox w-4 h-4 text-blue-600 bg-white border-gray-300 rounded focus:ring-blue-500 cursor-pointer">
                        </td>
                        <td class="px-6 py-4 font-semibold text-gray-800 whitespace-nowrap">{{ $driver->nama_lengkap }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">{{ $driver->nomor_wa }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($driver->status == 'pending')
                                <span class="bg-yellow-100 text-yellow-800 text-xs font-semibold px-3 py-1 rounded-full">Pending</span>
                            @elseif($driver->status == 'approved')
                                <span class="bg-green-100 text-green-800 text-xs font-semibold px-3 py-1 rounded-full">Approved</span>
                            @else
                                <span class="bg-red-100 text-red-800 text-xs font-semibold px-3 py-1 rounded-full">Rejected</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-gray-500 whitespace-nowrap">{{ $driver->created_at->format('d M Y') }}</td>
                        <td class="px-6 py-4 text-center">
                            <!-- Action Buttons -->
                            <div class="flex items-center justify-center gap-2">
                                <!-- Catatan: Atribut data-bs-toggle dipertahankan agar Modal Bootstrap lama tetap bisa terpanggil -->
                                <button type="button" data-bs-toggle="modal" data-bs-target="#detailModal{{ $driver->id }}" class="p-2 text-blue-600 bg-blue-50 hover:bg-blue-100 hover:text-blue-700 rounded-lg transition-colors shadow-sm" title="Detail & Verifikasi">
                                    <i class="fa-solid fa-eye fa-fw"></i>
                                </button>
                                <button type="button" data-bs-toggle="modal" data-bs-target="#editModal{{ $driver->id }}" class="p-2 text-yellow-600 bg-yellow-50 hover:bg-yellow-100 hover:text-yellow-700 rounded-lg transition-colors shadow-sm" title="Edit Data">
                                    <i class="fa-solid fa-pen fa-fw"></i>
                                </button>
                                <form action="{{ route('admin.drivers.destroy', $driver->id) }}" method="POST" class="inline-block m-0" onsubmit="return confirm('Apakah Anda yakin ingin menghapus permanen data ini?');">
                                    @csrf 
                                    @method('DELETE')
                                    <button type="submit" class="p-2 text-red-600 bg-red-50 hover:bg-red-100 hover:text-red-700 rounded-lg transition-colors shadow-sm" title="Hapus">
                                        <i class="fa-solid fa-trash-can fa-fw"></i>
                                    </button>
                                </form>
                            </div>

                            <!-- Modals (Detail & Edit) -->
                            @include('admin.partials._driver_modals', ['driver' => $driver])
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center text-gray-400">
                            <i class="fa-solid fa-inbox text-4xl block mb-3"></i>
                            <span class="text-sm font-medium">Belum ada data pendaftaran.</span>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        @if($drivers->hasPages())
        <div class="p-4 border-t border-gray-100 bg-white">
            {{ $drivers->links() }}
        </div>
        @endif
    </div>
</div>

<!-- Script Hapus Massal (Bulk Delete) -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    const checkAll = document.getElementById('checkAll');
    const checkboxes = document.querySelectorAll('.driver-checkbox');
    const btnBulkDelete = document.getElementById('btn-bulk-delete');

    // Fitur Centang Semua
    if (checkAll) {
        checkAll.addEventListener('change', function () {
            checkboxes.forEach(cb => cb.checked = checkAll.checked);
        });
    }

    // Aksi Hapus Massal via Fetch API
    if (btnBulkDelete) {
        btnBulkDelete.addEventListener('click', function () {
            let selectedIds = Array.from(checkboxes)
                                   .filter(cb => cb.checked)
                                   .map(cb => cb.value);
            
            if (selectedIds.length === 0) {
                alert('Pilih setidaknya satu data untuk dihapus.');
                return;
            }

            if (confirm('Anda yakin ingin menghapus ' + selectedIds.length + ' data terpilih secara permanen?')) {
                // Tampilkan loading state pada tombol dengan icon FontAwesome spinner
                const originalText = btnBulkDelete.innerHTML;
                btnBulkDelete.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i> Menghapus...';
                btnBulkDelete.disabled = true;
                btnBulkDelete.classList.add('opacity-75', 'cursor-not-allowed');

                fetch("{{ route('admin.drivers.bulk_destroy') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ ids: selectedIds })
                })
                .then(response => response.json())
                .then(data => {
                    if(data.success) {
                        location.reload();
                    } else {
                        alert(data.message);
                        resetButton(btnBulkDelete, originalText);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Terjadi kesalahan pada sistem.');
                    resetButton(btnBulkDelete, originalText);
                });
            }
        });
    }
    
    function resetButton(btn, text) {
        btn.innerHTML = text;
        btn.disabled = false;
        btn.classList.remove('opacity-75', 'cursor-not-allowed');
    }
});
</script>
@endsection