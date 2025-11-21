{{-- resources/views/admin/pesanan/riwayat-scan.blade.php --}}

@extends('layouts.admin')

@section('title', 'Riwayat Scan Barcode')
@section('page-title', 'Riwayat Scan Barcode')

@section('content')
<div class="bg-white p-6 rounded-lg shadow-md">
    <!-- Header: Kontrol & Aksi -->
    <div class="space-y-4">
        {{-- Form Filter Utama --}}
        <form id="filterForm" action="{{ route('pesanan.riwayat.scan') }}" method="GET" class="space-y-4">
            {{-- Baris Pertama: Pencarian & Tombol Export --}}
            <div class="flex flex-col md:flex-row justify-between items-center gap-4">
                <div class="relative w-full md:w-1/3">
                    <input type="search" name="search" id="searchInput" class="w-full pl-10 pr-4 py-2 border rounded-lg" placeholder="Cari Resi..." value="{{ request('search') }}">
                    <div class="absolute top-0 left-0 inline-flex items-center p-2 h-full text-gray-400"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg></div>
                </div>
                <div class="flex items-center gap-2 w-full md:w-auto justify-end">
                    <button type="button" onclick="openModal('exportModal')" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm font-medium">Export</button>
                </div>
            </div>
            
            {{-- Baris Kedua: Filter Tanggal & Paginasi --}}
            <div class="flex flex-col md:flex-row justify-between items-center gap-4 border-t pt-4">
                <!-- Filter Tanggal menggunakan Radio Button -->
                <div class="flex flex-wrap gap-2">
                    <input type="radio" name="range" value="" id="range_semua" class="hidden" onchange="this.form.submit()" {{ !request('range') ? 'checked' : '' }}>
                    <label for="range_semua" class="px-3 py-1 text-sm font-medium rounded-full cursor-pointer {{ !request('range') ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-600' }}">Semua</label>

                    <input type="radio" name="range" value="harian" id="range_harian" class="hidden" onchange="this.form.submit()" {{ request('range') == 'harian' ? 'checked' : '' }}>
                    <label for="range_harian" class="px-3 py-1 text-sm font-medium rounded-full cursor-pointer {{ request('range') == 'harian' ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-600' }}">Hari Ini</label>
                    
                    <input type="radio" name="range" value="mingguan" id="range_mingguan" class="hidden" onchange="this.form.submit()" {{ request('range') == 'mingguan' ? 'checked' : '' }}>
                    <label for="range_mingguan" class="px-3 py-1 text-sm font-medium rounded-full cursor-pointer {{ request('range') == 'mingguan' ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-600' }}">Minggu Ini</label>

                    <input type="radio" name="range" value="bulanan" id="range_bulanan" class="hidden" onchange="this.form.submit()" {{ request('range') == 'bulanan' ? 'checked' : '' }}>
                    <label for="range_bulanan" class="px-3 py-1 text-sm font-medium rounded-full cursor-pointer {{ request('range') == 'bulanan' ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-600' }}">Bulan Ini</label>
                </div>
                <!-- Filter Per Halaman -->
                <div>
                    <select name="per_page" onchange="this.form.submit()" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg">
                        @foreach ([10, 20, 30, 50, 100] as $val)
                            <option value="{{ $val }}" {{ request('per_page', 10) == $val ? 'selected' : '' }}>{{ $val }} per halaman</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </form>
    </div>

    {{-- Notifikasi --}}
    @if (session('success'))
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 my-4" role="alert"><p>{{ session('success') }}</p></div>
    @endif

    <!-- Tabel Riwayat Scan -->
    <div class="overflow-x-auto mt-6">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal Scan</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Resi</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Penerima</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse ($scannedOrders as $order)
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ \Carbon\Carbon::parse($order->updated_at)->format('d M Y, H:i') }}</td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm font-medium text-gray-900">{{ $order->resi }}</div>
                        <div class="text-xs text-indigo-600">{{ $order->resi_aktual }} ({{ $order->jasa_ekspedisi_aktual }})</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $order->receiver_name }}</td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $order->status == 'Diproses' ? 'bg-blue-100 text-blue-800' : ($order->status == 'Terkirim' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800') }}">
                            {{ $order->status }}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                        @if($order->status == 'Diproses')
                            <form action="{{ route('pesanan.update.status', $order->resi) }}" method="POST" onsubmit="return confirm('Anda yakin ingin mengirim paket ini?');">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="status" value="Terkirim">
                                <button type="submit" class="text-white bg-green-500 hover:bg-green-600 px-3 py-1 rounded-md text-xs">Kirim Paket</button>
                            </form>
                        @else
                            <span class="text-gray-400">-</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" class="text-center py-4 text-gray-500">Data tidak ditemukan.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $scannedOrders->appends(request()->query())->links() }}</div>
</div>

<!-- Modal Export -->
<div id="exportModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 z-50 hidden">
    <div class="relative top-20 mx-auto p-5 border w-full max-w-md shadow-lg rounded-md bg-white">
        <h3 class="text-xl font-semibold mb-4">Export Data Riwayat Scan</h3>
        <div class="space-y-4">
            <p class="text-sm text-gray-600">Pilih format file yang ingin Anda unduh.</p>
            <div class="flex gap-4">
                <a href="{{ route('admin.pesanan.riwayat.export.excel', request()->query()) }}" class="flex-1 text-center bg-green-600 text-white p-3 rounded-lg hover:bg-green-700">Export Excel</a>
                <a href="{{ route('admin.pesanan.riwayat.export.pdf', request()->query()) }}" class="flex-1 text-center bg-red-600 text-white p-3 rounded-lg hover:bg-red-700">Export PDF</a>
            </div>
        </div>
        <div class="flex justify-end mt-6">
            <button type="button" onclick="closeModal('exportModal')" class="bg-gray-200 px-4 py-2 rounded-md hover:bg-gray-300">Tutup</button>
        </div>
    </div>
</div>

<script>
function openModal(id) { document.getElementById(id).classList.remove('hidden'); }
function closeModal(id) { document.getElementById(id).classList.add('hidden'); }

document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.addEventListener('search', function(event) {
            if (event.target.value === '') {
                document.getElementById('filterForm').submit();
            }
        });
    }
});
</script>
@endsection
