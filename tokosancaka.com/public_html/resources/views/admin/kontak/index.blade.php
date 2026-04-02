{{-- resources/views/admin/kontak/index.blade.php --}}
{{-- LOG LOG: File integrity maintained --}}

@extends('layouts.admin')

@section('title', 'Data Pelanggan')
@section('page-title', 'Buku Alamat (Pengirim & Penerima)')

@section('content')
<div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">

    {{-- Notifikasi --}}
    @if (session('success'))
        <div class="bg-green-50 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded-md shadow-sm" role="alert">
            <p class="font-medium">{{ session('success') }}</p>
        </div>
    @endif
    @if (session('error'))
        <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-md shadow-sm" role="alert">
            <p class="font-medium">{{ session('error') }}</p>
        </div>
    @endif
    @if ($errors->any())
        <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-md shadow-sm" role="alert">
            <ul class="list-disc list-inside">
                @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif

    {{-- Header Section --}}
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
        <h2 class="text-2xl font-bold text-gray-800">Data Pelanggan</h2>
        <div class="flex gap-2 w-full md:w-auto">
            <button type="button" onclick="openModal('importModal')" class="w-full md:w-auto bg-purple-700 hover:bg-purple-800 text-white px-5 py-2.5 rounded-lg text-sm font-semibold flex items-center justify-center gap-2 transition-colors shadow-sm">
                Export / Import
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
            </button>
        </div>
    </div>

    {{-- Statistik Bar (Monitoring Repeat Order) --}}
    <div class="mb-8 bg-gray-50 p-4 rounded-lg border border-gray-100">
        <p class="text-gray-600 mb-3 text-sm font-medium">Total Pelanggan <span class="text-lg font-extrabold text-gray-900 ml-1">{{ $kontaks->total() }}</span></p>

        <div class="w-full flex h-8 rounded-md overflow-hidden mb-4 shadow-inner">
            @if($stats['persen_baru'] > 0)
                <div class="bg-blue-600 flex items-center justify-center text-white text-xs font-bold transition-all duration-500" style="width: {{ $stats['persen_baru'] }}%">{{ $stats['persen_baru'] }}%</div>
            @endif
            @if($stats['persen_repeat'] > 0)
                <div class="bg-yellow-500 flex items-center justify-center text-white text-xs font-bold transition-all duration-500" style="width: {{ $stats['persen_repeat'] }}%">{{ $stats['persen_repeat'] }}%</div>
            @endif
            @if($stats['persen_loyal'] > 0)
                <div class="bg-purple-700 flex items-center justify-center text-white text-xs font-bold transition-all duration-500" style="width: {{ $stats['persen_loyal'] }}%">{{ $stats['persen_loyal'] }}%</div>
            @endif
            @if($kontaks->total() == 0)
                <div class="bg-gray-200 flex items-center justify-center text-gray-500 text-xs font-bold w-full">0%</div>
            @endif
        </div>

        <div class="flex flex-wrap gap-5 text-sm font-semibold text-gray-700">
            <div class="flex items-center gap-2"><span class="w-4 h-4 bg-blue-600 rounded shadow-sm"></span> Pelanggan Baru ({{ $stats['count_baru'] }}) <span class="text-gray-400 text-xs ml-1 cursor-pointer" title="Order 1x">ⓘ</span></div>
            <div class="flex items-center gap-2"><span class="w-4 h-4 bg-yellow-500 rounded shadow-sm"></span> Pelanggan Repeat Order ({{ $stats['count_repeat'] }}) <span class="text-gray-400 text-xs ml-1 cursor-pointer" title="Order 2x">ⓘ</span></div>
            <div class="flex items-center gap-2"><span class="w-4 h-4 bg-purple-700 rounded shadow-sm"></span> Pelanggan Loyal ({{ $stats['count_loyal'] }}) <span class="text-gray-400 text-xs ml-1 cursor-pointer" title="Order > 2x">ⓘ</span></div>
        </div>
    </div>

    {{-- Filter Row --}}
    <div class="grid grid-cols-1 md:grid-cols-12 gap-3 mb-6">
        <form action="{{ route('admin.kontak.index') }}" method="GET" class="md:col-span-5 flex">
            @if(request('filter')) <input type="hidden" name="filter" value="{{ request('filter') }}"> @endif
            @if(request('status')) <input type="hidden" name="status" value="{{ request('status') }}"> @endif

            <div class="relative w-full flex shadow-sm">
                <span class="inline-flex items-center px-3 rounded-l-lg border border-r-0 border-gray-300 bg-gray-50 text-gray-500 text-sm">
                    No. HP / Nama
                </span>
                <input type="text" name="search" class="flex-1 block w-full rounded-none rounded-r-lg border-gray-300 px-4 py-2.5 text-sm focus:ring-purple-500 focus:border-purple-500 border" placeholder="Cth: 082243xxxx" value="{{ request('search') }}">
                <button type="submit" class="absolute inset-y-0 right-0 px-4 text-gray-500 hover:text-purple-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </button>
            </div>
        </form>

        <div class="md:col-span-3">
            <select class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm bg-white shadow-sm focus:ring-purple-500 focus:border-purple-500" onchange="updateUrlParameter('status', this.value)">
                <option value="">Pilih Filter Status</option>
                <option value="baru" {{ request('status') == 'baru' ? 'selected' : '' }}>Pelanggan Baru (1x Order)</option>
                <option value="repeat" {{ request('status') == 'repeat' ? 'selected' : '' }}>Repeat Order (2x Order)</option>
                <option value="loyal" {{ request('status') == 'loyal' ? 'selected' : '' }}>Loyal (>2x Order)</option>
            </select>
        </div>

        <div class="md:col-span-2">
            <select class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm bg-white shadow-sm focus:ring-purple-500 focus:border-purple-500" onchange="updateUrlParameter('filter', this.value)">
                <option value="Semua" {{ request('filter') == 'Semua' ? 'selected' : '' }}>Semua Tipe</option>
                <option value="Pengirim" {{ request('filter') == 'Pengirim' ? 'selected' : '' }}>Pengirim</option>
                <option value="Penerima" {{ request('filter') == 'Penerima' ? 'selected' : '' }}>Penerima</option>
                <option value="Keduanya" {{ request('filter') == 'Keduanya' ? 'selected' : '' }}>Keduanya</option>
            </select>
        </div>

        <div class="md:col-span-2 flex justify-end">
            <button onclick="openAddModal()" class="w-full bg-white border border-purple-600 text-purple-700 hover:bg-purple-50 px-4 py-2.5 rounded-lg text-sm font-bold transition-colors shadow-sm">
                + Tambah Kontak
            </button>
        </div>
    </div>

    {{-- Table Section --}}
    <div class="overflow-hidden border border-gray-200 rounded-xl shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-[#f0e6f7]">
                    <tr>
                        <th scope="col" class="px-5 py-4 text-left text-[11px] font-bold text-gray-700 uppercase tracking-wider">NO</th>
                        <th scope="col" class="px-5 py-4 text-left text-[11px] font-bold text-gray-700 uppercase tracking-wider">PELANGGAN</th>
                        <th scope="col" class="px-5 py-4 text-left text-[11px] font-bold text-gray-700 uppercase tracking-wider">ALAMAT</th>
                        <th scope="col" class="px-5 py-4 text-left text-[11px] font-bold text-gray-700 uppercase tracking-wider">TERAKHIR KIRIM</th>
                        <th scope="col" class="px-5 py-4 text-center text-[11px] font-bold text-gray-700 uppercase tracking-wider">TOTAL PENGIRIMAN</th>
                        <th scope="col" class="px-5 py-4 text-center text-[11px] font-bold text-gray-700 uppercase tracking-wider">CATATAN</th>
                        <th scope="col" class="px-5 py-4 text-center text-[11px] font-bold text-gray-700 uppercase tracking-wider">AKSI</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse ($kontaks as $index => $kontak)
                    <tr class="hover:bg-gray-50 transition duration-150">
                        <td class="px-5 py-5 text-sm font-bold text-gray-700 whitespace-nowrap">{{ $kontaks->firstItem() + $index }}</td>
                        <td class="px-5 py-5 whitespace-nowrap">
                            <div class="text-sm font-semibold text-gray-800 uppercase tracking-wide">{{ $kontak->nama }}</div>
                            <div class="text-sm text-purple-700 font-semibold mt-0.5">{{ $kontak->no_hp }}</div>

                            {{-- Logika Badge Monitoring --}}
                            @php
                                $totalOrder = $kontak->total_pengiriman ?? 0;
                                $statusLabel = 'Baru';
                                $statusClass = 'bg-blue-100 text-blue-700';

                                if($totalOrder == 2) {
                                    $statusLabel = 'Repeat Order';
                                    $statusClass = 'bg-yellow-100 text-yellow-800';
                                } elseif($totalOrder > 2) {
                                    $statusLabel = 'Loyal';
                                    $statusClass = 'bg-purple-100 text-purple-800';
                                }
                                if($totalOrder == 0) {
                                    $statusLabel = 'Belum Order';
                                    $statusClass = 'bg-gray-100 text-gray-600';
                                }
                            @endphp
                            <div class="flex items-center gap-2 mt-2">
                                <span class="px-2 py-0.5 inline-flex items-center text-[10px] leading-4 font-bold rounded-full {{ $statusClass }}">
                                    <span class="w-1.5 h-1.5 rounded-full mr-1
                                        {{ $totalOrder == 1 ? 'bg-blue-600' : ($totalOrder == 2 ? 'bg-yellow-600' : ($totalOrder > 2 ? 'bg-purple-600' : 'bg-gray-500')) }}"></span>
                                    {{ $statusLabel }}
                                </span>
                                <span class="px-2 py-0.5 inline-flex text-[10px] leading-4 font-semibold rounded-full border border-gray-200 text-gray-500 bg-gray-50">
                                    {{ $kontak->tipe }}
                                </span>
                            </div>
                        </td>
                        <td class="px-5 py-5 text-sm text-gray-600">
                            <div class="max-w-xs truncate whitespace-normal leading-relaxed" title="{{ $kontak->alamat }}">{{ $kontak->alamat }}</div>
                        </td>
                        <td class="px-5 py-5 whitespace-nowrap">
                            @if($kontak->updated_at)
                                <span class="px-3 py-1 bg-[#e1effe] text-blue-600 rounded-full text-[11px] font-semibold border border-blue-100 shadow-sm">
                                    {{ $kontak->updated_at->diffForHumans() }}
                                </span>
                            @else
                                <span class="text-gray-400 text-xs">-</span>
                            @endif
                        </td>
                        <td class="px-5 py-5 whitespace-nowrap text-center">
                            <span class="text-sm font-bold text-gray-900">{{ $totalOrder }}</span>
                        </td>
                        <td class="px-5 py-5 text-center">
                             <button class="border border-purple-600 text-purple-700 px-4 py-1.5 rounded-full text-xs font-semibold hover:bg-purple-50 transition-colors bg-white">
                                Catatan
                            </button>
                        </td>
                        <td class="px-5 py-5 whitespace-nowrap text-right text-sm font-medium">
                            <div class="flex items-center justify-center gap-2">
                                <a href="https://wa.me/{{ $kontak->no_hp }}" target="_blank" class="flex items-center gap-1.5 border border-purple-600 text-purple-700 px-3 py-1.5 rounded-lg text-xs font-semibold hover:bg-purple-50 transition-colors bg-white">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path></svg>
                                    Hubungi
                                </a>
                                <button onclick="openHistoryModal({{ $kontak->id }})" class="p-1.5 bg-purple-700 text-white rounded-lg hover:bg-purple-800 shadow-sm transition-colors" title="Lihat Riwayat">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                </button>
                                <button onclick="openEditModal({{ $kontak->id }})" class="p-1.5 bg-gray-100 text-gray-600 rounded-lg hover:bg-gray-200 shadow-sm transition-colors" title="Edit Data">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-5 py-12 text-center text-gray-500 font-medium">
                            <svg class="mx-auto h-12 w-12 text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                            Data pelanggan tidak ditemukan.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Pagination --}}
    <div class="mt-6">
        {{ $kontaks->links() }}
    </div>
</div>

<div id="kontakModal" class="fixed inset-0 bg-gray-900 bg-opacity-50 z-[60] hidden flex items-center justify-center backdrop-blur-sm transition-opacity">
    <div class="relative p-6 border w-full max-w-lg shadow-2xl rounded-2xl bg-white m-4">
        <form id="kontakForm" action="" method="POST">
            @csrf
            <input type="hidden" name="_method" id="formMethod" value="POST">

            <div class="flex justify-between items-center mb-5 border-b pb-4">
                <h3 id="modalTitle" class="text-xl font-extrabold text-gray-800">Tambah Kontak Baru</h3>
                <button type="button" onclick="closeModal('kontakModal')" class="text-gray-400 hover:text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-full p-1 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>

            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1">Nama Pelanggan</label>
                    <input type="text" id="nama" name="nama" class="w-full border border-gray-300 rounded-xl p-2.5 text-sm focus:ring-purple-500 focus:border-purple-500" required placeholder="Masukkan nama lengkap">
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1">No. HP / WhatsApp</label>
                    <input type="text" id="no_hp" name="no_hp" class="w-full border border-gray-300 rounded-xl p-2.5 text-sm focus:ring-purple-500 focus:border-purple-500" required placeholder="08xxxxxxxxxx">
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1">Tipe Kontak</label>
                    <select id="tipe" name="tipe" class="w-full border border-gray-300 rounded-xl p-2.5 text-sm focus:ring-purple-500 focus:border-purple-500">
                        <option value="Pengirim">Pengirim</option>
                        <option value="Penerima">Penerima</option>
                        <option value="Keduanya">Keduanya</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1">Alamat Lengkap</label>
                    <textarea id="alamat" name="alamat" rows="3" class="w-full border border-gray-300 rounded-xl p-2.5 text-sm focus:ring-purple-500 focus:border-purple-500" required placeholder="Nama Jalan, RT/RW, Desa/Kelurahan..."></textarea>
                </div>
            </div>

            <div class="flex justify-end gap-3 mt-6 pt-4 border-t">
                <button type="button" onclick="closeModal('kontakModal')" class="bg-white border border-gray-300 hover:bg-gray-50 text-gray-700 font-bold text-sm px-5 py-2.5 rounded-xl transition-colors">Batal</button>
                <button type="submit" class="bg-purple-700 hover:bg-purple-800 text-white font-bold text-sm px-5 py-2.5 rounded-xl transition-colors shadow-md">Simpan Data</button>
            </div>
        </form>
    </div>
</div>

<div id="importModal" class="fixed inset-0 bg-gray-900 bg-opacity-50 z-[60] hidden flex items-center justify-center backdrop-blur-sm transition-opacity">
    <div class="relative p-6 border w-full max-w-lg shadow-2xl rounded-2xl bg-white m-4">
        <div class="flex justify-between items-center mb-5 border-b pb-4">
            <h3 class="text-xl font-extrabold text-gray-800">Export & Import Data</h3>
            <button type="button" onclick="closeModal('importModal')" class="text-gray-400 hover:text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-full p-1 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>

        <div class="space-y-6">
            {{-- Bagian Export --}}
            <div>
                <h4 class="font-bold text-gray-700 mb-3 text-sm uppercase tracking-wider">Export Data Pelanggan</h4>
                <div class="grid grid-cols-2 gap-3">
                    <a href="{{ route('admin.kontak.export.excel') }}" class="flex flex-col items-center justify-center bg-green-50 hover:bg-green-100 border border-green-200 text-green-700 p-4 rounded-xl font-bold text-sm transition-colors">
                        <svg class="w-6 h-6 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        Download Excel
                    </a>
                    <a href="{{ route('admin.kontak.export.pdf') }}" class="flex flex-col items-center justify-center bg-red-50 hover:bg-red-100 border border-red-200 text-red-700 p-4 rounded-xl font-bold text-sm transition-colors">
                        <svg class="w-6 h-6 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                        Download PDF
                    </a>
                </div>
            </div>

            {{-- Bagian Import --}}
            <form action="{{ route('admin.kontak.import.excel') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="border-t pt-5">
                    <h4 class="font-bold text-gray-700 mb-2 text-sm uppercase tracking-wider">Import Data Excel</h4>
                    <p class="text-xs text-gray-500 mb-3 bg-gray-50 p-2 rounded-lg border border-gray-200">Format Kolom Wajib: <b>nama, no_hp, alamat, tipe</b>.</p>
                    <input type="file" name="file" class="w-full text-sm text-gray-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-bold file:bg-purple-50 file:text-purple-700 hover:file:bg-purple-100 border border-gray-300 rounded-xl p-2 cursor-pointer" required accept=".xlsx, .xls">
                </div>
                <div class="flex justify-end mt-6 pt-4 border-t">
                    <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-sm w-full py-3 rounded-xl transition-colors shadow-md flex justify-center items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                        Upload & Proses Import
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div id="historyModal" class="fixed inset-0 bg-gray-900 bg-opacity-50 z-[70] hidden flex items-center justify-center backdrop-blur-sm transition-opacity p-4 sm:p-6">
    <div class="relative bg-white w-full max-w-6xl shadow-2xl rounded-2xl flex flex-col max-h-[90vh]">
        <div class="flex justify-between items-center px-6 py-5 border-b border-gray-200">
            <h3 class="text-xl font-extrabold text-gray-800 tracking-tight">Daftar Pengiriman Pelanggan</h3>
            <button type="button" onclick="closeModal('historyModal')" class="text-gray-400 hover:text-gray-800 bg-gray-100 hover:bg-gray-200 rounded-full p-1.5 focus:outline-none transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>

        <div class="p-6 overflow-y-auto custom-scrollbar">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-6 mb-8">
                <div>
                    <p class="text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1">Nama Pelanggan</p>
                    <p class="text-sm font-extrabold text-gray-900 uppercase" id="h_nama">-</p>
                </div>
                <div>
                    <p class="text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1">Nomor HP</p>
                    <p class="text-sm font-extrabold text-gray-900" id="h_nohp">-</p>
                </div>
                <div>
                    <p class="text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1">Jumlah Paket</p>
                    <p class="text-sm font-extrabold text-gray-900" id="h_paket">-</p>
                </div>
                <div>
                    <p class="text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1">Total Omzet dari Pelanggan</p>
                    <p class="text-sm font-extrabold text-green-600" id="h_omzet">-</p>
                </div>
            </div>

            <div class="border border-gray-200 rounded-xl overflow-x-auto shadow-sm">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-[#f0e6f7]">
                        <tr>
                            <th class="px-5 py-3.5 text-left text-[11px] font-extrabold text-purple-900 uppercase tracking-wider w-16">NO</th>
                            <th class="px-5 py-3.5 text-left text-[11px] font-extrabold text-purple-900 uppercase tracking-wider">RESI & STATUS</th>
                            <th class="px-5 py-3.5 text-left text-[11px] font-extrabold text-purple-900 uppercase tracking-wider">TANGGAL DIKIRIM</th>
                            <th class="px-5 py-3.5 text-left text-[11px] font-extrabold text-purple-900 uppercase tracking-wider">EKSPEDISI & ONGKIR</th>
                            <th class="px-5 py-3.5 text-left text-[11px] font-extrabold text-purple-900 uppercase tracking-wider">PRODUK</th>
                            <th class="px-5 py-3.5 text-left text-[11px] font-extrabold text-purple-900 uppercase tracking-wider">NILAI BARANG</th>
                        </tr>
                    </thead>
                    <tbody id="historyTbody" class="bg-white divide-y divide-gray-100">
                        </tbody>
                </table>
            </div>

            <div id="historyPagination" class="mt-5 flex justify-between items-center"></div>
        </div>
    </div>
</div>

<style>
    .custom-scrollbar::-webkit-scrollbar { width: 6px; height: 6px; }
    .custom-scrollbar::-webkit-scrollbar-track { background: #f1f1f1; border-radius: 4px; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background: #d1d5db; border-radius: 4px; }
    .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #9ca3af; }
</style>

<script>
// Fungsi Basic
function openModal(id) { document.getElementById(id).classList.remove('hidden'); }
function closeModal(id) { document.getElementById(id).classList.add('hidden'); }

// Fungsi Update URL Filter Dropdown
function updateUrlParameter(key, value) {
    let url = new URL(window.location.href);
    if (value) {
        url.searchParams.set(key, value);
    } else {
        url.searchParams.delete(key);
    }
    url.searchParams.delete('page');
    window.location.href = url.toString();
}

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
        const response = await fetch(`/admin/kontak/${id}`);
        if (!response.ok) throw new Error('Gagal ambil data server');
        const kontak = await response.json();

        document.getElementById('nama').value = kontak.nama;
        document.getElementById('no_hp').value = kontak.no_hp;
        document.getElementById('alamat').value = kontak.alamat;
        document.getElementById('tipe').value = kontak.tipe;

        form.action = `/admin/kontak/${id}`;
        document.getElementById('formMethod').value = 'PUT';
        document.getElementById('modalTitle').innerText = 'Edit Data Pelanggan';
        openModal('kontakModal');
    } catch (error) {
        console.error(error);
        alert('Gagal memuat data kontak.');
    }
}

// ==========================================
// SCRIPT RENDER MODAL HISTORY (AJAX)
// ==========================================
async function openHistoryModal(id, page = 1) {
    try {
        openModal('historyModal');
        const tbody = document.getElementById('historyTbody');
        const pagination = document.getElementById('historyPagination');

        tbody.innerHTML = '<tr><td colspan="6" class="text-center py-10"><div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-purple-700"></div><p class="mt-2 text-gray-500 text-sm font-medium">Memuat riwayat...</p></td></tr>';
        pagination.innerHTML = '';

        // Panggil endpoint history
        const baseUrl = "{{ url('admin/kontak') }}";
        const response = await fetch(`${baseUrl}/${id}/history?page=${page}`);

        if (!response.ok) throw new Error('Error network response');
        const data = await response.json();

        const formatRupiah = (number) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0}).format(number);

        // Render Header Bar Modal
        document.getElementById('h_nama').innerText = data.kontak.nama || '-';
        document.getElementById('h_nohp').innerText = data.kontak.no_hp || '-';
        document.getElementById('h_paket').innerText = data.total_paket || '0';
        document.getElementById('h_omzet').innerText = formatRupiah(data.total_omzet || 0);

        // Render Tabel Detail
        let htmlBody = '';
        if(data.history.data.length === 0) {
            htmlBody = '<tr><td colspan="6" class="text-center py-12 text-gray-400 font-medium italic">Pelanggan ini belum memiliki riwayat pengiriman.</td></tr>';
        } else {
            data.history.data.forEach((item, index) => {
                let no = data.history.from + index;
                let resiAktual = item.resi || item.nomor_invoice;

                let tglBuat = new Date(item.created_at).toLocaleString('id-ID', {day:'numeric', month:'short', year:'numeric', hour:'2-digit', minute:'2-digit'});
                let tglKirim = item.tanggal_pesanan ? new Date(item.tanggal_pesanan).toLocaleString('id-ID', {day:'numeric', month:'long', year:'numeric', hour:'2-digit', minute:'2-digit'}) : '-';

                let statusRaw = item.status_pesanan || 'Baru';
                let statusBadge = 'bg-blue-100 text-blue-700';
                if(['Batal', 'Kadaluarsa', 'Gagal Bayar', 'Dibatalkan'].includes(statusRaw)) statusBadge = 'bg-red-100 text-red-700';
                else if(['Selesai', 'Terkirim', 'paid'].includes(statusRaw)) statusBadge = 'bg-green-100 text-green-700';
                else if(['Sedang Dikirim', 'Diproses'].includes(statusRaw)) statusBadge = 'bg-purple-100 text-purple-700';

                let iconEkspedisi = `<span class="inline-flex items-center justify-center p-1 bg-red-50 text-red-600 rounded border border-red-100 text-[10px] font-bold mr-1">EX</span>`;

                htmlBody += `
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-5 py-4 text-sm font-bold text-gray-700">${no}</td>
                        <td class="px-5 py-4">
                            <div class="font-extrabold text-gray-900 text-sm tracking-wide">${resiAktual}</div>
                            <div class="text-[11px] text-gray-500 mt-0.5">Dibuat: ${tglBuat}</div>
                            <span class="inline-block mt-2 px-2 py-0.5 text-[10px] font-extrabold rounded bg-purple-100 text-purple-700 shadow-sm">${statusRaw}</span>
                        </td>
                        <td class="px-5 py-4 text-sm text-gray-700 font-medium">${tglKirim}</td>
                        <td class="px-5 py-4">
                            <div class="flex items-center text-xs font-extrabold text-gray-800 uppercase tracking-wide">
                                ${iconEkspedisi} ${item.expedition || '-'}
                            </div>
                            <div class="text-sm font-extrabold text-green-600 mt-1">${formatRupiah(item.shipping_cost || 0)}</div>
                        </td>
                        <td class="px-5 py-4 text-sm text-gray-700 font-medium capitalize">${item.item_description || 'Barang Umum'}</td>
                        <td class="px-5 py-4 text-sm text-gray-800 font-bold">${formatRupiah(item.total_harga_barang || item.item_price || 0)}</td>
                    </tr>
                `;
            });
        }
        tbody.innerHTML = htmlBody;

        // Render Paginasi
        if(data.history.total > 0) {
            let pageHtml = `<div class="text-xs font-semibold text-gray-500">Tampil ${data.history.from} - ${data.history.to} dari ${data.history.total} data</div>`;
            pageHtml += `<div class="flex gap-1.5">`;

            if(data.history.prev_page_url) {
                pageHtml += `<button onclick="openHistoryModal(${id}, ${data.history.current_page - 1})" class="p-1.5 px-3 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 text-gray-600 font-bold text-sm transition-colors">&lt;</button>`;
            }

            pageHtml += `<span class="p-1.5 px-3.5 bg-purple-700 text-white rounded-lg font-bold text-sm shadow-sm">${data.history.current_page}</span>`;

            if(data.history.next_page_url) {
                pageHtml += `<button onclick="openHistoryModal(${id}, ${data.history.current_page + 1})" class="p-1.5 px-3 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 text-gray-600 font-bold text-sm transition-colors">&gt;</button>`;
            }
            pageHtml += `</div>`;
            pagination.innerHTML = pageHtml;
        }

    } catch (error) {
        console.error('Gagal meload history:', error);
        document.getElementById('historyTbody').innerHTML = `<tr><td colspan="6" class="text-center py-8 text-red-500 font-bold text-sm"><svg class="w-8 h-8 mx-auto mb-2 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>Gagal memuat data. Periksa kembali route dan controller Anda.</td></tr>`;
    }
}
</script>
@endsection
