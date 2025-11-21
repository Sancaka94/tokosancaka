@extends('layouts.customer')

@section('title', 'Riwayat Scan SPX')

@section('content')
<div class="bg-slate-50 min-h-screen">
    <div class="container mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        
        <!-- Header Halaman -->
        <div class="mb-6">
            <h1 class="text-3xl font-bold tracking-tight text-slate-900 sm:text-4xl">Riwayat Scan Paket</h1>
            <p class="mt-2 text-lg text-slate-600">Cari, filter, dan kelola semua paket SPX yang pernah Anda scan.</p>
        </div>

        <!-- Form Pencarian dan Filter -->
        <div class="mb-6 rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <form action="{{ route('customer.scan.index') }}" method="GET">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <div>
                        <label for="search" class="block text-sm font-medium text-slate-700">Nomor Resi</label>
                        <input type="text" name="search" id="search" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" placeholder="Masukkan nomor resi..." value="{{ request('search') }}">
                    </div>
                    <div>
                        <label for="start_date" class="block text-sm font-medium text-slate-700">Dari Tanggal</label>
                        <input type="date" name="start_date" id="start_date" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" value="{{ request('start_date') }}">
                    </div>
                    <div>
                        <label for="end_date" class="block text-sm font-medium text-slate-700">Sampai Tanggal</label>
                        <input type="date" name="end_date" id="end_date" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" value="{{ request('end_date') }}">
                    </div>
                    <div class="self-end">
                        <button type="submit" class="inline-flex w-full items-center justify-center rounded-md border border-transparent bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700">
                            <i class="fas fa-search -ml-1 mr-2"></i>
                            Cari
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Tombol Aksi (Ekspor) -->
        <div class="mb-6 flex justify-end gap-3">
            <a href="{{ route('customer.scan.export.excel', request()->query()) }}" class="inline-flex items-center rounded-md border border-green-300 bg-white px-4 py-2 text-sm font-medium text-green-700 shadow-sm hover:bg-green-50">
                <i class="fas fa-file-excel mr-2 text-green-500"></i>
                Ekspor Excel
            </a>
            <a href="{{ route('customer.scan.export.pdf', request()->query()) }}" class="inline-flex items-center rounded-md border border-red-300 bg-white px-4 py-2 text-sm font-medium text-red-700 shadow-sm hover:bg-red-50">
                <i class="fas fa-file-pdf mr-2 text-red-500"></i>
                Ekspor PDF
            </a>
        </div>

        <!-- Konten Utama: Tabel Riwayat -->
        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-lg">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Nomor Resi</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Status</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Tanggal Scan</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 bg-white">
                        @forelse ($scans as $scan)
                            <tr>
                                <td class="whitespace-nowrap px-6 py-4">
                                    <div class="font-semibold text-indigo-600">{{ $scan->resi_number ?? 'N/A' }}</div>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4">
                                    @php
                                        $status = $scan->status ?? 'Proses';
                                        $statusClass = '';
                                        switch ($status) {
                                            case 'Diterima Sancaka': $statusClass = 'bg-blue-100 text-blue-800'; break;
                                            case 'Dijemput Kurir': $statusClass = 'bg-green-100 text-green-800'; break;
                                            case 'Ditolak': case 'Cancel': $statusClass = 'bg-red-100 text-red-800'; break;
                                            case 'Proses Pickup': default: $statusClass = 'bg-yellow-100 text-yellow-800'; break;
                                        }
                                    @endphp
                                    <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold leading-5 {{ $statusClass }}">
                                        {{ $status }}
                                    </span>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-600">{{ $scan->created_at->format('d M Y, H:i') }}</td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm font-medium">
                                    <div class="flex items-center gap-4">
                                        {{-- ✅ PERBAIKAN: Mengarahkan ke rute lacak internal customer --}}
                                        <a href="{{ route('customer.lacak.index', ['search' => $scan->resi_number]) }}" class="text-slate-600 hover:text-indigo-900">Lacak</a>
                                        <a href="{{ route('customer.scan.edit', $scan->resi_number) }}" class="text-slate-600 hover:text-indigo-900">Edit</a>
                                        <form action="{{ route('customer.scan.destroy', $scan->resi_number) }}" method="POST" onsubmit="return confirm('Anda yakin ingin menghapus resi ini?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:text-red-900">Hapus</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-12 text-center">
                                    <div class="mx-auto max-w-md">
                                        <i class="fas fa-barcode fa-3x text-slate-400"></i>
                                        <h3 class="mt-2 text-sm font-medium text-slate-900">Tidak ada riwayat scan</h3>
                                        <p class="mt-1 text-sm text-slate-500">Data tidak ditemukan. Coba ubah filter pencarian Anda.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            @if ($scans->hasPages())
                <div class="border-t border-slate-200 bg-white px-4 py-3 sm:px-6">
                    {{ $scans->appends(request()->query())->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
