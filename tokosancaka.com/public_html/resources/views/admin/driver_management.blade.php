@extends('layouts.admin')

@section('content')
<div class="w-full px-4 py-6 mx-auto">
    <div class="flex justify-between items-center mb-6">
        <h3 class="text-2xl font-extrabold text-gray-800 m-0">Manajemen Pendaftaran Driver</h3>
    </div>

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

    <div class="bg-white border-none shadow-sm rounded-2xl mb-6">
        <div class="p-5">
            <form method="GET" action="{{ route('admin.drivers.index') }}" class="grid grid-cols-1 md:grid-cols-12 gap-4">
                <div class="md:col-span-5 relative">
                    <div class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none">
                        <i class="fa-solid fa-magnifying-glass text-gray-400"></i>
                    </div>
                    <input type="text" name="search" value="{{ request('search') }}" 
                           class="w-full bg-gray-50 border border-gray-200 text-gray-700 text-sm rounded-xl focus:ring-blue-500 focus:border-blue-500 block pl-11 pr-4 py-3 transition-colors outline-none" 
                           placeholder="Cari nama atau nomor WA...">
                </div>
                <div class="md:col-span-3">
                    <select name="status" class="w-full bg-gray-50 border border-gray-200 text-gray-700 text-sm rounded-xl focus:ring-blue-500 focus:border-blue-500 block px-4 py-3 transition-colors outline-none cursor-pointer">
                        <option value="">Semua Status</option>
                        <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Approved</option>
                        <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Rejected</option>
                    </select>
                </div>
                <div class="md:col-span-2">
                    <button type="submit" class="w-full flex justify-center items-center gap-2 bg-gray-800 hover:bg-gray-900 text-white font-semibold rounded-xl text-sm px-4 py-3 transition-colors shadow-sm">
                        <i class="fa-solid fa-filter"></i> Filter
                    </button>
                </div>
                <div class="md:col-span-2">
                    <button type="button" id="btn-bulk-delete" class="w-full flex justify-center items-center gap-2 bg-red-50 hover:bg-red-100 text-red-600 font-semibold rounded-xl text-sm px-4 py-3 transition-colors border border-red-100">
                        <i class="fa-solid fa-trash-can"></i> Hapus Massal
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="bg-white shadow-sm rounded-2xl overflow-hidden border border-gray-100">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left align-middle whitespace-nowrap">
                <thead class="bg-white border-b border-gray-100">
                    <tr>
                        <th scope="col" class="px-6 py-5 w-12 text-center">
                            <input type="checkbox" id="checkAll" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 cursor-pointer transition-all">
                        </th>
                        <th scope="col" class="px-6 py-5 text-[11px] font-bold text-gray-400 uppercase tracking-widest">Nama Lengkap</th>
                        <th scope="col" class="px-6 py-5 text-[11px] font-bold text-gray-400 uppercase tracking-widest">No. WhatsApp</th>
                        <th scope="col" class="px-6 py-5 text-[11px] font-bold text-gray-400 uppercase tracking-widest">Status</th>
                        <th scope="col" class="px-6 py-5 text-[11px] font-bold text-gray-400 uppercase tracking-widest">Tanggal Daftar</th>
                        <th scope="col" class="px-6 py-5 text-[11px] font-bold text-gray-400 uppercase tracking-widest text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($drivers as $driver)
                    <tr class="hover:bg-gray-50/50 transition-colors duration-200">
                        <td class="px-6 py-4 text-center">
                            <input type="checkbox" value="{{ $driver->id }}" class="driver-checkbox w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 cursor-pointer transition-all">
                        </td>
                        <td class="px-6 py-4 font-bold text-gray-800">{{ $driver->nama_lengkap }}</td>
                        <td class="px-6 py-4 text-gray-600 font-medium">{{ $driver->nomor_wa }}</td>
                        <td class="px-6 py-4">
                            @if($driver->status == 'pending')
                                <span class="bg-yellow-50 border border-yellow-200 text-yellow-700 text-[11px] font-bold uppercase tracking-wider px-3 py-1.5 rounded-full">Pending</span>
                            @elseif($driver->status == 'approved')
                                <span class="bg-green-50 border border-green-200 text-green-700 text-[11px] font-bold uppercase tracking-wider px-3 py-1.5 rounded-full">Approved</span>
                            @else
                                <span class="bg-red-50 border border-red-200 text-red-700 text-[11px] font-bold uppercase tracking-wider px-3 py-1.5 rounded-full">Rejected</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-gray-500 font-medium">{{ $driver->created_at->format('d M Y') }}</td>
                        <td class="px-6 py-4 text-center">
                            <div class="inline-flex items-center gap-1.5 p-1 bg-gray-50 rounded-xl border border-gray-100">
                                
                                <button type="button" data-bs-toggle="modal" data-bs-target="#detailModal{{ $driver->id }}" 
                                        class="w-8 h-8 flex items-center justify-center rounded-lg text-blue-600 hover:bg-white hover:shadow-sm transition-all" title="Detail & Verifikasi">
                                    <i class="fa-solid fa-eye"></i>
                                </button>
                                
                                <button type="button" data-bs-toggle="modal" data-bs-target="#editModal{{ $driver->id }}" 
                                        class="w-8 h-8 flex items-center justify-center rounded-lg text-yellow-500 hover:bg-white hover:shadow-sm transition-all" title="Edit Data">
                                    <i class="fa-solid fa-pen"></i>
                                </button>
                                
                                <form action="{{ route('admin.drivers.destroy', $driver->id) }}" method="POST" class="m-0 inline-block" onsubmit="return confirm('Apakah Anda yakin ingin menghapus permanen data ini?');">
                                    @csrf 
                                    @method('DELETE')
                                    <button type="submit" class="w-8 h-8 flex items-center justify-center rounded-lg text-red-500 hover:bg-white hover:shadow-sm transition-all" title="Hapus">
                                        <i class="fa-solid fa-trash-can"></i>
                                    </button>
                                </form>

                            </div>

                            @include('admin.partials._driver_modals', ['driver' => $driver])
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-16 text-center text-gray-400">
                            <div class="flex flex-col items-center justify-center">
                                <div class="w-16 h-16 mb-4 bg-gray-50 rounded-full flex items-center justify-center">
                                    <i class="fa-solid fa-folder-open text-2xl text-gray-300"></i>
                                </div>
                                <span class="text-sm font-semibold text-gray-500">Belum ada data pendaftaran driver.</span>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        @if($drivers->hasPages())
        <div class="px-6 py-4 border-t border-gray-100 bg-white">
            {{ $drivers->links() }}
        </div>
        @endif
    </div>
</div>

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
                // Animasi Loading
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