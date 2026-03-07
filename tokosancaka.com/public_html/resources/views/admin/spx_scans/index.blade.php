@extends('layouts.admin')

@section('title', 'Data Scan SPX Masuk')
@section('page-title', 'Data Scan SPX Masuk')

@section('content')
<div class="bg-white shadow-md rounded-lg overflow-hidden">
    {{-- Header --}}
    <div class="px-6 py-4 border-b border-gray-200">
        <div class="flex justify-between items-center">
            <h4 class="text-lg font-bold text-gray-800">Daftar Paket SPX</h4>
            <div class="flex space-x-2">
                <a href="{{ route('admin.spx_scans.export.excel') }}" class="inline-flex items-center px-4 py-2 bg-green-500 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-600 active:bg-green-700 focus:outline-none focus:border-green-700 focus:ring focus:ring-green-200 disabled:opacity-25 transition">
                    <i class="fas fa-file-excel mr-2"></i> Ekspor Excel
                </a>
                <a href="{{ route('admin.spx_scans.export.pdf') }}" class="inline-flex items-center px-4 py-2 bg-red-500 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-600 active:bg-red-700 focus:outline-none focus:border-red-700 focus:ring focus:ring-red-200 disabled:opacity-25 transition">
                    <i class="fas fa-file-pdf mr-2"></i> Eksport PDF
                </a>
            </div>
        </div>
    </div>

    <div class="p-6">
        {{-- Filter Section --}}
        <div class="mb-4">
            <form action="{{ route('admin.spx_scans.index') }}" method="GET">
                <div class="flex flex-col md:flex-row gap-2">
                    {{-- Filter Tanggal Mulai --}}
                    <input type="date" name="start_date" class="px-4 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500" value="{{ request('start_date') }}" title="Tanggal Mulai">

                    <span class="self-center text-gray-500 font-medium hidden md:inline">s/d</span>

                    {{-- Filter Tanggal Selesai --}}
                    <input type="date" name="end_date" class="px-4 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500" value="{{ request('end_date') }}" title="Tanggal Selesai">

                    {{-- Filter Pencarian --}}
                    <div class="flex flex-grow">
                        <input type="text" name="search" class="w-full px-4 py-2 border border-gray-300 rounded-l-md focus:ring-indigo-500 focus:border-indigo-500" placeholder="Cari berdasarkan resi atau nama pengirim..." value="{{ request('search') }}">
                        <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-r-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 active:bg-indigo-800 focus:outline-none focus:border-indigo-800 focus:ring ring-indigo-300 disabled:opacity-25 transition ease-in-out duration-150">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>
            </form>
        </div>

        {{-- Table --}}
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nomor Resi</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama Pengirim</th>
                        <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Input System</th> {{-- KOLOM BARU --}}
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Waktu Scan</th>
                        <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse ($scans as $scan)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $scan->id }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900">
                                <div class="flex items-center gap-2">
                                    {{-- Mengirim parameter ID scan ke JS --}}
                                    <button type="button" onclick="copyResi('{{ $scan->resi_number }}', '{{ $scan->id }}')" class="text-red-400 hover:text-red-600 focus:outline-none transition-colors" title="Salin Nomor Resi">
                                        <i id="icon-copy-{{ $scan->id }}" class="fas fa-copy"></i>
                                    </button>
                                    <span id="resi-{{ $scan->id }}">{{ $scan->resi_number }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                @if($scan->user)
                                    {{ $scan->user->nama_lengkap }}
                                @elseif($scan->kontak)
                                    {{ $scan->kontak->nama }}
                                @else
                                    <span class="text-gray-400">Publik / N/A</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                @if($scan->status == 'Proses Pickup')
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">{{ $scan->status }}</span>
                                @else
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">{{ $scan->status }}</span>
                                @endif
                            </td>
                            {{-- KOLOM STATUS COPAS --}}
                            <td class="px-6 py-4 whitespace-nowrap text-center text-sm" id="status-copas-{{ $scan->id }}">
                                @if($scan->is_copied)
                                    <span class="text-green-600 font-semibold"><i class="fas fa-check-double"></i> DONE</span>
                                @else
                                    <span class="text-red-600 font-semibold"><i class="fas fa-minus"></i> Belum</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $scan->created_at->format('d M Y, H:i') }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                                <div class="flex items-center justify-center space-x-2">
                                    @if($scan->status == 'Proses Pickup')
                                        <form action="{{ route('admin.spx_scans.updateStatus', $scan->id) }}" method="POST" onsubmit="return confirm('Anda yakin ingin memproses paket ini?')">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="text-blue-600 hover:text-blue-900" title="Proses Lanjut">
                                                <i class="fas fa-check-circle fa-lg"></i>
                                            </button>
                                        </form>
                                    @endif
                                    <a href="{{ route('admin.spx_scans.edit', $scan->id) }}" class="text-yellow-500 hover:text-yellow-700" title="Edit">
                                        <i class="fas fa-pencil-alt fa-lg"></i>
                                    </a>
                                    <form action="{{ route('admin.spx_scans.destroy', $scan->id) }}" method="POST" onsubmit="return confirm('Yakin ingin menghapus data ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-900" title="Hapus">
                                            <i class="fas fa-trash-alt fa-lg"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center">
                                <div class="text-center">
                                    <i class="fas fa-box-open fa-4x text-gray-300"></i>
                                    <h3 class="mt-2 text-sm font-medium text-gray-900">Tidak ada data scan</h3>
                                    <p class="mt-1 text-sm text-gray-500">Tidak ada data yang cocok dengan pencarian Anda.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        <div class="mt-4">
            {{ $scans->appends(request()->query())->links() }}
        </div>
    </div>
</div>

<script>
    function copyResi(text, id) {
        console.log('LOG LOG: Fungsi copyResi dipanggil. Text:', text, 'ID:', id);

        let iconId = 'icon-copy-' + id;
        
        navigator.clipboard.writeText(text).then(function() {
            console.log('LOG LOG: Text berhasil dicopy ke clipboard.');
            
            let iconElement = document.getElementById(iconId);
            iconElement.className = 'fas fa-check text-green-500';

            console.log('LOG LOG: Mulai mengirim fetch (AJAX) ke server untuk ID:', id);

            fetch(`/admin/spx_scans/${id}/mark-copied`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            })
            .then(response => {
                console.log('LOG LOG: Menerima response HTTP dari server:', response.status, response.statusText);
                return response.json();
            })
            .then(data => {
                console.log('LOG LOG: Data JSON yang diterima dari server:', data);
                if(data.success) {
                    console.log('LOG LOG: Update berhasil! Mengubah status di HTML.');
                    document.getElementById('status-copas-' + id).innerHTML = '<span class="text-green-600 font-semibold"><i class="fas fa-check-double"></i> Done</span>';
                } else {
                    console.log('LOG LOG: Server membalas success = false. Pesan error:', data.message);
                }
            })
            .catch(error => {
                console.error('LOG LOG: Terjadi Error pada Fetch:', error);
            });

            setTimeout(() => {
                iconElement.className = 'fas fa-copy';
            }, 2000);
        }).catch(function(err) {
            console.error('LOG LOG: Gagal menyalin text:', err);
            alert('Gagal menyalin nomor resi.');
        });
    }
</script>
@endsection
