@extends('layouts.admin')

@section('title', 'Manajemen Komisi Agen Sancaka')

@section('content')
<!-- LOG LOG -->
<div class="max-w-7xl mx-auto space-y-6 p-4 md:p-8 font-sans">
    
    <!-- Header & Action Buttons -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center space-y-4 md:space-y-0">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight text-black">Logistik & Komisi</h1>
            <p class="text-sm text-gray-500 mt-1">Kelola data cashback, admin COD, dan persentase komisi Agen Sancaka secara dinamis.</p>
        </div>
        
        <div class="flex items-center space-x-3">
            <!-- Import Excel & Download Template -->
            <form action="{{ route('admin.data-autokirim.import') }}" method="POST" enctype="multipart/form-data" class="flex items-center space-x-2">
                @csrf
                <a href="{{ route('admin.data-autokirim.template') }}" class="bg-blue-50 border border-blue-200 text-blue-700 hover:bg-blue-100 px-3 py-2 rounded-md text-sm font-medium transition-colors flex items-center" title="Download Template Excel">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 mr-1">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                    </svg>
                    Template
                </a>
                
                <input type="file" name="file" class="text-sm border border-gray-200 rounded-md py-1.5 px-2 bg-white w-48 focus:outline-none focus:border-black" accept=".xlsx,.csv,.xls" required>
                <button type="submit" class="bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 px-4 py-2 rounded-md text-sm font-medium transition-colors">
                    Import
                </button>
            </form>

            <a href="{{ route('admin.data-autokirim.export.excel') }}" class="bg-green-50 text-green-700 border border-green-200 hover:bg-green-100 px-4 py-2 rounded-md text-sm font-medium transition-colors">
                Export Excel
            </a>
            <a href="{{ route('admin.data-autokirim.export.pdf') }}" class="bg-red-50 text-red-700 border border-red-200 hover:bg-red-100 px-4 py-2 rounded-md text-sm font-medium transition-colors">
                Export PDF
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="p-4 rounded-md border border-green-200 bg-green-50 text-sm text-green-800">
            {{ session('success') }}
        </div>
    @endif

    <!-- Form Tambah Data Manual -->
    <div class="bg-white border border-gray-200 rounded-lg p-5 shadow-sm mt-4">
        <h2 class="text-base font-medium text-black mb-4">Tambah Skema Komisi Manual</h2>
        <form action="{{ route('admin.data-autokirim.store') }}" method="POST" class="grid grid-cols-1 md:grid-cols-6 gap-4 items-end">
            @csrf
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Brand Logistik</label>
                <input type="text" name="brand_logistik" placeholder="e.g., AnterAja" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-black focus:border-black" required>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Service</label>
                <input type="text" name="service" placeholder="e.g., cod reg" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-black focus:border-black" required>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Cashback (%)</label>
                <input type="number" step="0.01" name="cashback" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-black focus:border-black" required>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Admin COD (%)</label>
                <input type="number" step="0.01" name="admin_cod" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-black focus:border-black" required>
            </div>
            <div>
                <label class="block text-xs font-medium text-blue-600 mb-1">Komisi Agen (%)</label>
                <input type="number" step="0.01" name="komisi_agen" placeholder="Bagi deviden" class="w-full border border-blue-300 bg-blue-50 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-blue-600 focus:border-blue-600" required>
            </div>
            <div>
                <button type="submit" class="w-full bg-black text-white hover:bg-gray-800 px-4 py-2 rounded-md text-sm font-medium transition-colors">
                    Simpan Data
                </button>
            </div>
        </form>
    </div>

    <!-- Tabel Data Next.js Style -->
    <div class="bg-white border border-gray-200 rounded-lg shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50/50 border-b border-gray-200">
                        <th class="py-3 px-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">Brand</th>
                        <th class="py-3 px-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">Service</th>
                        <th class="py-3 px-4 text-xs font-semibold text-gray-500 uppercase tracking-wider text-center">Satuan</th>
                        <th class="py-3 px-4 text-xs font-semibold text-gray-500 uppercase tracking-wider text-right">Cashback</th>
                        <th class="py-3 px-4 text-xs font-semibold text-gray-500 uppercase tracking-wider text-right">Admin COD</th>
                        <th class="py-3 px-4 text-xs font-semibold text-blue-600 uppercase tracking-wider text-right bg-blue-50/30">Komisi Agen</th>
                        <th class="py-3 px-4 text-xs font-semibold text-gray-500 uppercase tracking-wider text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-sm">
                    @forelse($data as $item)
                    <tr class="hover:bg-gray-50/50 transition-colors">
                        <td class="py-3 px-4 font-medium text-gray-900">{{ $item->brand_logistik }}</td>
                        <td class="py-3 px-4 text-gray-600">{{ $item->service }}</td>
                        <td class="py-3 px-4 text-gray-500 text-center">{{ $item->satuan }}</td>
                        <td class="py-3 px-4 text-gray-700 text-right">{{ $item->cashback }}</td>
                        <td class="py-3 px-4 text-gray-700 text-right">{{ $item->admin_cod }}</td>
                        <td class="py-3 px-4 font-semibold text-blue-700 text-right bg-blue-50/10">{{ $item->komisi_agen }}</td>
                        <td class="py-3 px-4 text-right">
                            <form action="{{ route('admin.data-autokirim.destroy', $item->id) }}" method="POST" onsubmit="return confirm('Hapus data ini?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-gray-400 hover:text-red-600 transition-colors">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 inline">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                    </svg>
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="py-8 text-center text-gray-500 text-sm">Belum ada data skema komisi.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection