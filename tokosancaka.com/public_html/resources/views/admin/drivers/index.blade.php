@extends('layouts.admin')
@section('title', 'Manajemen Pendaftaran Driver')
@section('page-title', 'Manajemen Pendaftaran Driver')

@push('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" type="text/css" href="https://npmcdn.com/flatpickr/dist/themes/airbnb.css">
    <style>
        @media (min-width: 768px) {
            .table-container { overflow-x: auto; }
            th.sticky-col, td.sticky-col { position: sticky; right: 0; background-color: white; z-index: 10; border-left: 1px solid #e5e7eb; box-shadow: -4px 0 6px -1px rgba(0,0,0,0.05); }
            thead th.sticky-col { background-color: #f3f4f6; z-index: 20; }
            tr:hover td.sticky-col { background-color: #f9fafb; }
        }
        @media (max-width: 767px) {
            table, thead, tbody, th, td, tr { display: block; }
            thead tr { position: absolute; top: -9999px; left: -9999px; }
            tr { margin-bottom: 1rem; border: 1px solid #e5e7eb; border-radius: 0.75rem; background-color: white; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
            td { border: none; border-bottom: 1px solid #f3f4f6; padding: 0.75rem 1rem !important; }
            td:last-child { border-bottom: none; }
        }
        .bulk-action-bar { transition: all 0.3s ease-in-out; opacity: 0; visibility: hidden; transform: translateY(-10px); display: none; }
        .bulk-action-bar.active { opacity: 1; visibility: visible; transform: translateY(0); display: flex; }
        .flatpickr-calendar { z-index: 9999 !important; }
    </style>
@endpush

@section('content')
<div class="container mx-auto px-4 py-6">

    @if(session('success'))
        <div class="mb-6 flex items-center justify-between p-4 text-sm text-green-800 bg-green-50 border-l-4 border-green-500 rounded">
            <div class="flex items-center gap-2"><i class="fa-solid fa-circle-check"></i> <b>{{ session('success') }}</b></div>
        </div>
    @endif
    @if(session('error'))
        <div class="mb-6 flex items-center justify-between p-4 text-sm text-red-800 bg-red-50 border-l-4 border-red-500 rounded">
            <div class="flex items-center gap-2"><i class="fa-solid fa-triangle-exclamation"></i> <b>{{ session('error') }}</b></div>
        </div>
    @endif
    @if($errors->any())
        <div class="mb-6 p-4 text-sm text-red-800 bg-red-50 border-l-4 border-red-500 rounded">
            <div class="fw-bold mb-1"><i class="fa-solid fa-circle-exclamation"></i> Terjadi Kesalahan:</div>
            <ul class="mb-0 ps-3">@foreach($errors->all() as $error) <li>{{ $error }}</li> @endforeach</ul>
        </div>
    @endif

    <div class="bg-white p-4 sm:p-6 rounded-lg shadow-md">
        
        {{-- FILTER FORM --}}
        <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center mb-6 gap-4">
            <div class="w-full lg:w-3/4">
                <form action="{{ route('admin.drivers.index') }}" method="GET" class="flex flex-col md:flex-row gap-3">
                    @if(request('status')) <input type="hidden" name="status" value="{{ request('status') }}"> @endif
                    <div class="relative w-full md:w-1/3">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400"><i class="fas fa-search"></i></div>
                        <input type="text" name="search" value="{{ request('search') }}" class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-lg text-sm" placeholder="Cari Nama / No. WA / NIK...">
                    </div>
                    <div class="relative w-full md:w-1/3 group">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400"><i class="far fa-calendar-alt"></i></div>
                        <input type="text" id="date_range_picker" name="date_range" value="{{ request('date_range') }}" class="w-full pl-10 pr-10 py-2.5 border border-gray-300 rounded-lg text-sm bg-white" placeholder="Filter Tanggal..." readonly>
                    </div>
                    <button type="submit" class="bg-blue-600 text-white px-5 py-2.5 rounded-lg text-sm font-medium hover:bg-blue-700 transition flex items-center justify-center gap-2">
                        <i class="fas fa-filter"></i> Filter
                    </button>
                </form>
            </div>
        </div>

        {{-- TAB STATUS --}}
        <div class="flex flex-wrap gap-2 mb-4 border-b pb-4">
            @php $baseQuery = request()->except(['status', 'page']); @endphp
            <a href="{{ route('admin.drivers.index', $baseQuery) }}" class="px-4 py-2 text-xs font-bold rounded-full border {{ !request('status') ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-600 hover:bg-gray-50' }}">Semua</a>
            <a href="{{ route('admin.drivers.index', array_merge($baseQuery, ['status'=>'pending', 'page'=>1])) }}" class="px-4 py-2 text-xs font-bold rounded-full border {{ request('status')=='pending' ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-600 hover:bg-gray-50' }}">Pending</a>
            <a href="{{ route('admin.drivers.index', array_merge($baseQuery, ['status'=>'approved', 'page'=>1])) }}" class="px-4 py-2 text-xs font-bold rounded-full border {{ request('status')=='approved' ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-600 hover:bg-gray-50' }}">Approved</a>
            <a href="{{ route('admin.drivers.index', array_merge($baseQuery, ['status'=>'rejected', 'page'=>1])) }}" class="px-4 py-2 text-xs font-bold rounded-full border {{ request('status')=='rejected' ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-600 hover:bg-gray-50' }}">Rejected</a>
        </div>

        {{-- STATISTIK --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <div class="relative overflow-hidden rounded-lg bg-gray-700 p-5 shadow-lg text-white">
                <p class="text-3xl font-bold">{{ $totalDrivers }}</p>
                <p class="text-sm font-bold uppercase mt-1">Total Pendaftar</p>
                <i class="fas fa-users fa-5x absolute right-0 top-0 -mt-2 -mr-4 opacity-20"></i>
            </div>
            <div class="relative overflow-hidden rounded-lg bg-yellow-500 p-5 shadow-lg text-white">
                <p class="text-3xl font-bold">{{ $pendingDrivers }}</p>
                <p class="text-sm font-bold uppercase mt-1">Pending</p>
                <i class="fas fa-clock fa-5x absolute right-0 top-0 -mt-2 -mr-4 opacity-20"></i>
            </div>
            <div class="relative overflow-hidden rounded-lg bg-green-500 p-5 shadow-lg text-white">
                <p class="text-3xl font-bold">{{ $approvedDrivers }}</p>
                <p class="text-sm font-bold uppercase mt-1">Disetujui</p>
                <i class="fas fa-check-circle fa-5x absolute right-0 top-0 -mt-2 -mr-4 opacity-20"></i>
            </div>
            <div class="relative overflow-hidden rounded-lg bg-red-500 p-5 shadow-lg text-white">
                <p class="text-3xl font-bold">{{ $rejectedDrivers }}</p>
                <p class="text-sm font-bold uppercase mt-1">Ditolak</p>
                <i class="fas fa-times-circle fa-5x absolute right-0 top-0 -mt-2 -mr-4 opacity-20"></i>
            </div>
        </div>

        {{-- BULK ACTION --}}
        <div id="bulkActionBar" class="bulk-action-bar bg-red-50 border border-red-200 rounded-lg p-3 mb-4 flex-col sm:flex-row items-center justify-between gap-3">
            <div class="text-sm text-red-800 font-semibold flex items-center gap-2">
                <i class="fas fa-check-square text-red-500"></i> <span id="selectedCount">0</span> Driver Terpilih
            </div>
            <div class="flex gap-2 w-full sm:w-auto">
                <button type="button" onclick="toggleSelectAll()" class="bg-white border border-gray-300 px-4 py-2 rounded text-sm font-bold">Pilih Semua</button>
                <button type="button" onclick="showBulkDeleteModal()" class="bg-red-600 text-white px-4 py-2 rounded text-sm font-bold flex items-center gap-2"><i class="fas fa-trash-alt"></i> Hapus</button>
            </div>
        </div>
        <form id="bulkDeleteForm" action="{{ route('admin.drivers.bulk_destroy') }}" method="POST" class="hidden">@csrf @method('DELETE')</form>

        {{-- TABEL DATA --}}
        <div class="table-container">
            <table class="w-full divide-y divide-gray-200">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-600 w-10"><input type="checkbox" onclick="toggleSelectAllHeader(this)" class="w-4 h-4 text-blue-600 rounded"></th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-600">No</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-600">Profil & Usia</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-600">Layanan & Kendaraan</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-600">Status</th>
                        <th class="px-4 py-3 text-center text-xs font-bold text-gray-600 sticky-col">Aksi</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse ($drivers as $index => $driver)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-4 align-top border-b md:border-none">
                            <span class="md:hidden font-bold text-gray-400 text-xs mr-2">PILIH:</span>
                            <input type="checkbox" name="selected_ids[]" value="{{ $driver->id }}" data-name="{{ $driver->nama_lengkap }}" onchange="updateBulkActionUI()" class="row-checkbox w-4 h-4 text-blue-600 rounded">
                        </td>
                        <td class="px-4 py-4 align-top text-sm text-gray-500"><span class="md:hidden font-bold text-gray-400 text-xs mr-2">NO:</span>{{ $drivers->firstItem() + $index }}</td>
                        
                        {{-- PROFIL --}}
                        <td class="px-4 py-4 align-top text-sm">
                            <span class="md:hidden font-bold text-gray-400 text-xs mb-1 block">PROFIL:</span>
                            <div class="font-bold text-gray-800">{{ $driver->nama_lengkap }}</div>
                            <div class="text-xs text-gray-500 mt-1">
                                <i class="fa-solid fa-cake-candles"></i> {{ \Carbon\Carbon::parse($driver->tanggal_lahir)->age }} Tahun
                            </div>
                            <div class="text-xs text-blue-600 font-semibold mt-1"><i class="fa-brands fa-whatsapp"></i> {{ $driver->nomor_wa }}</div>
                            <button class="md:hidden mt-3 w-full bg-gray-100 py-1.5 rounded text-xs font-semibold" onclick="toggleDetails({{$index}}, this)">Lihat Detail <i class="fas fa-chevron-down"></i></button>
                        </td>

                        {{-- KENDARAAN --}}
                        <td class="hidden md:table-cell px-4 py-4 align-top text-sm toggle-target-{{$index}}">
                            <span class="md:hidden font-bold text-gray-400 text-xs mb-1 block">LAYANAN:</span>
                            @if($driver->jenis_layanan == 'mobil')
                                <span class="bg-indigo-100 text-indigo-800 text-xs font-bold px-2 py-0.5 rounded border border-indigo-200"><i class="fa-solid fa-car"></i> Sancaka CAR</span>
                            @else
                                <span class="bg-orange-100 text-orange-800 text-xs font-bold px-2 py-0.5 rounded border border-orange-200"><i class="fa-solid fa-motorcycle"></i> Sancaka RIDE</span>
                            @endif
                            <div class="font-semibold text-gray-700 mt-2">{{ $driver->merk_kendaraan }} ({{ $driver->tahun_kendaraan }})</div>
                            <div class="text-xs font-bold text-gray-900 border border-gray-400 px-2 py-0.5 inline-block mt-1 uppercase">{{ $driver->plat_nomor }}</div>
                        </td>

                        {{-- STATUS --}}
                        <td class="hidden md:table-cell px-4 py-4 align-top text-sm toggle-target-{{$index}}">
                            <span class="md:hidden font-bold text-gray-400 text-xs mb-1 block">STATUS:</span>
                            @if($driver->status == 'pending') <span class="bg-yellow-100 text-yellow-800 text-xs font-bold px-2 py-1 rounded">Pending</span>
                            @elseif($driver->status == 'approved') <span class="bg-green-100 text-green-800 text-xs font-bold px-2 py-1 rounded">Approved</span>
                            @else <span class="bg-red-100 text-red-800 text-xs font-bold px-2 py-1 rounded">Rejected</span> @endif
                        </td>

                        {{-- AKSI --}}
                        <td class="hidden md:table-cell px-4 py-4 align-middle text-sm sticky-col toggle-target-{{$index}}">
                            <div class="flex items-center justify-center space-x-3">
                                <button onclick="openModal('modalDetail_{{ $driver->id }}')" class="text-gray-500 hover:text-blue-600"><i class="fas fa-eye fa-lg"></i></button>
                                <button onclick="openModal('modalEdit_{{ $driver->id }}')" class="text-gray-500 hover:text-yellow-500"><i class="fas fa-pencil-alt fa-lg"></i></button>
                                <form action="{{ route('admin.drivers.destroy', $driver->id) }}" method="POST" class="m-0 inline" onsubmit="return confirm('Hapus permanen?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-gray-500 hover:text-red-600"><i class="fas fa-trash-alt fa-lg"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>

                    {{-- ======================================================== --}}
                    {{-- MODAL DETAIL DRIVER (Murni Tailwind)                     --}}
                    {{-- ======================================================== --}}
                    <div id="modalDetail_{{ $driver->id }}" class="hidden fixed inset-0 z-[99999]">
                        <div class="fixed inset-0 bg-gray-900 bg-opacity-75" onclick="closeModal('modalDetail_{{ $driver->id }}')"></div>
                        <div class="fixed inset-0 overflow-y-auto pt-10 pb-10">
                            <div class="flex min-h-full items-center justify-center p-4">
                                <div class="bg-white rounded-xl shadow-2xl w-full max-w-4xl border border-gray-200">
                                    <div class="border-b px-6 py-4 bg-gray-50 flex justify-between items-center rounded-t-xl">
                                        <h5 class="text-lg font-bold text-gray-800">Detail Pendaftaran: {{ $driver->nama_lengkap }}</h5>
                                        <button onclick="closeModal('modalDetail_{{ $driver->id }}')" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times fa-lg"></i></button>
                                    </div>
                                    <div class="px-6 py-5">
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                            <div class="border p-3 rounded bg-gray-50 text-sm">
                                                <b class="block border-b pb-1 mb-2">Data Pribadi</b>
                                                <div>ID Akun: {{ $driver->id_pengguna ?? '-' }}</div>
                                                <div>NIK: {{ $driver->nomor_nik }}</div>
                                                <div>TTL: {{ $driver->tempat_lahir }}, {{ \Carbon\Carbon::parse($driver->tanggal_lahir)->format('d M Y') }} ({{ \Carbon\Carbon::parse($driver->tanggal_lahir)->age }} Thn)</div>
                                                <div>Alamat: {{ $driver->alamat_lengkap }}</div>
                                            </div>
                                            <div class="border p-3 rounded bg-gray-50 text-sm">
                                                <b class="block border-b pb-1 mb-2">Kendaraan & Layanan</b>
                                                <div>Layanan: {{ strtoupper($driver->jenis_layanan) }}</div>
                                                <div>Merek & Thn: {{ $driver->merk_kendaraan }} ({{ $driver->tahun_kendaraan }})</div>
                                                <div>Plat: <span class="uppercase font-bold">{{ $driver->plat_nomor }}</span></div>
                                            </div>
                                        </div>

                                        <div class="border p-3 rounded bg-gray-50 text-sm mb-4">
                                            <b class="block border-b pb-1 mb-2">Kelengkapan Dokumen</b>
                                            <div class="flex flex-wrap gap-2">
                                                @if($driver->foto_wajah) <a href="{{ asset('storage/'.$driver->foto_wajah) }}" target="_blank" class="px-3 py-1 bg-blue-100 text-blue-700 font-bold rounded text-xs">Wajah</a> @endif
                                                @if($driver->file_ktp) <a href="{{ asset('storage/'.$driver->file_ktp) }}" target="_blank" class="px-3 py-1 bg-cyan-100 text-cyan-700 font-bold rounded text-xs">KTP</a> @endif
                                                @if($driver->file_sim) <a href="{{ asset('storage/'.$driver->file_sim) }}" target="_blank" class="px-3 py-1 bg-indigo-100 text-indigo-700 font-bold rounded text-xs">SIM</a> @endif
                                                @if($driver->file_skck) <a href="{{ asset('storage/'.$driver->file_skck) }}" target="_blank" class="px-3 py-1 bg-emerald-100 text-emerald-700 font-bold rounded text-xs">SKCK</a> @endif
                                                @if($driver->file_stnk) <a href="{{ asset('storage/'.$driver->file_stnk) }}" target="_blank" class="px-3 py-1 bg-gray-200 text-gray-800 font-bold rounded text-xs">STNK</a> @endif
                                                @if($driver->foto_motor) <a href="{{ asset('storage/'.$driver->foto_motor) }}" target="_blank" class="px-3 py-1 bg-yellow-100 text-yellow-700 font-bold rounded text-xs">Kendaraan</a> @endif
                                                @if($driver->file_buku_rekening) <a href="{{ asset('storage/'.$driver->file_buku_rekening) }}" target="_blank" class="px-3 py-1 bg-orange-100 text-orange-700 font-bold rounded text-xs">Rekening</a> @endif
                                                @if($driver->file_kk) <a href="{{ asset('storage/'.$driver->file_kk) }}" target="_blank" class="px-3 py-1 bg-gray-100 text-gray-600 font-bold rounded text-xs">KK</a> @endif
                                            </div>
                                        </div>

                                        {{-- LOGIKA TOMBOL STATUS (Seperti yang diminta sebelumnya) --}}
                                        <div class="flex flex-col sm:flex-row gap-3 pt-3 border-t">
                                            @if($driver->status == 'pending')
                                                <form action="{{ route('admin.drivers.status', $driver->id) }}" method="POST" class="w-full sm:w-1/2">
                                                    @csrf @method('PATCH') <input type="hidden" name="status" value="approved">
                                                    <button type="submit" class="w-full bg-green-600 hover:bg-green-700 text-white py-2 rounded font-bold text-sm"><i class="fa-solid fa-check"></i> Setujui</button>
                                                </form>
                                                <form action="{{ route('admin.drivers.status', $driver->id) }}" method="POST" class="w-full sm:w-1/2">
                                                    @csrf @method('PATCH') <input type="hidden" name="status" value="rejected">
                                                    <button type="submit" class="w-full bg-white border-2 border-red-500 text-red-600 hover:bg-red-50 py-2 rounded font-bold text-sm"><i class="fa-solid fa-xmark"></i> Tolak</button>
                                                </form>
                                            @elseif($driver->status == 'approved')
                                                <button onclick="closeModal('modalDetail_{{ $driver->id }}'); openModal('modalEdit_{{ $driver->id }}')" class="w-full sm:w-1/2 bg-yellow-500 text-white py-2 rounded font-bold text-sm"><i class="fa-solid fa-pen"></i> Edit Data</button>
                                                <form action="{{ route('admin.drivers.status', $driver->id) }}" method="POST" class="w-full sm:w-1/2">
                                                    @csrf @method('PATCH') <input type="hidden" name="status" value="rejected">
                                                    <button type="submit" onclick="return confirm('Yakin tolak driver ini?')" class="w-full bg-white border-2 border-red-500 text-red-600 py-2 rounded font-bold text-sm"><i class="fa-solid fa-ban"></i> Batalkan Status</button>
                                                </form>
                                            @else
                                                <button onclick="closeModal('modalDetail_{{ $driver->id }}'); openModal('modalEdit_{{ $driver->id }}')" class="w-full sm:w-1/2 bg-yellow-500 text-white py-2 rounded font-bold text-sm"><i class="fa-solid fa-pen"></i> Edit Data</button>
                                                <form action="{{ route('admin.drivers.status', $driver->id) }}" method="POST" class="w-full sm:w-1/2">
                                                    @csrf @method('PATCH') <input type="hidden" name="status" value="approved">
                                                    <button type="submit" class="w-full bg-green-600 text-white py-2 rounded font-bold text-sm"><i class="fa-solid fa-rotate-left"></i> Pulihkan & Setujui</button>
                                                </form>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- ======================================================== --}}
                    {{-- MODAL EDIT DRIVER (Murni Tailwind & File Input)          --}}
                    {{-- ======================================================== --}}
                    <div id="modalEdit_{{ $driver->id }}" class="hidden fixed inset-0 z-[99999]">
                        <div class="fixed inset-0 bg-gray-900 bg-opacity-75" onclick="closeModal('modalEdit_{{ $driver->id }}')"></div>
                        <div class="fixed inset-0 overflow-y-auto pt-10 pb-10">
                            <div class="flex min-h-full items-center justify-center p-4">
                                <div class="bg-white rounded-xl shadow-2xl w-full max-w-5xl border border-gray-200">
                                    <div class="border-b px-6 py-4 bg-gray-50 flex justify-between items-center rounded-t-xl">
                                        <h5 class="text-lg font-bold text-gray-800">Edit Data: {{ $driver->nama_lengkap }}</h5>
                                        <button onclick="closeModal('modalEdit_{{ $driver->id }}')" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times fa-lg"></i></button>
                                    </div>
                                    
                                    <form action="{{ route('admin.drivers.update', $driver->id) }}" method="POST" enctype="multipart/form-data">
                                        @csrf @method('PUT')
                                        <div class="px-6 py-5 h-[65vh] overflow-y-auto">
                                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                                
                                                {{-- Kiri: Data Teks --}}
                                                <div class="space-y-3">
                                                    <b class="block border-b pb-1 text-sm text-gray-700">Identitas Pribadi & Kendaraan</b>
                                                    <input type="text" name="nama_lengkap" class="w-full border p-2 rounded text-sm" value="{{ $driver->nama_lengkap }}" required>
                                                    
                                                    <div class="flex gap-2">
                                                        <input type="text" name="tempat_lahir" class="w-1/2 border p-2 rounded text-sm" value="{{ $driver->tempat_lahir }}" required>
                                                        <input type="date" name="tanggal_lahir" class="w-1/2 border p-2 rounded text-sm" value="{{ \Carbon\Carbon::parse($driver->tanggal_lahir)->format('Y-m-d') }}" required>
                                                    </div>
                                                    
                                                    <div class="flex gap-2">
                                                        <input type="number" name="nomor_nik" class="w-1/2 border p-2 rounded text-sm" value="{{ $driver->nomor_nik }}" required>
                                                        <input type="text" name="nomor_wa" class="w-1/2 border p-2 rounded text-sm" value="{{ $driver->nomor_wa }}" required>
                                                    </div>
                                                    <textarea name="alamat_lengkap" class="w-full border p-2 rounded text-sm" required>{{ $driver->alamat_lengkap }}</textarea>

                                                    <div class="flex gap-2">
                                                        <select name="jenis_layanan" class="w-1/3 border p-2 rounded text-sm" required>
                                                            <option value="motor" {{ $driver->jenis_layanan=='motor'?'selected':'' }}>RIDE (Motor)</option>
                                                            <option value="mobil" {{ $driver->jenis_layanan=='mobil'?'selected':'' }}>CAR (Mobil)</option>
                                                        </select>
                                                        <input type="text" name="merk_kendaraan" class="w-1/3 border p-2 rounded text-sm" value="{{ $driver->merk_kendaraan }}" required>
                                                        <input type="number" name="tahun_kendaraan" class="w-1/3 border p-2 rounded text-sm" value="{{ $driver->tahun_kendaraan }}" required>
                                                    </div>
                                                    <input type="text" name="plat_nomor" class="w-full border p-2 rounded text-sm uppercase" value="{{ $driver->plat_nomor }}" required>
                                                </div>

                                                {{-- Kanan: Upload File --}}
                                                <div class="space-y-3">
                                                    <b class="block border-b pb-1 text-sm text-gray-700">Update Dokumen (Kosongkan jika tidak diganti)</b>
                                                    
                                                    @foreach([
                                                        'foto_wajah'=>'Foto Wajah', 'file_ktp'=>'KTP', 'file_sim'=>'SIM', 
                                                        'file_skck'=>'SKCK', 'file_buku_rekening'=>'Rekening', 'file_stnk'=>'STNK', 'foto_motor'=>'Kendaraan'
                                                    ] as $field => $label)
                                                    <div class="flex items-center gap-2">
                                                        <label class="w-1/3 text-xs font-bold text-gray-600">{{ $label }}</label>
                                                        <input type="file" name="{{ $field }}" class="w-2/3 border p-1 rounded text-xs bg-gray-50" accept=".jpg,.png,.pdf">
                                                    </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="px-6 py-4 bg-gray-50 border-t flex justify-end gap-3 rounded-b-xl">
                                            <button type="button" onclick="closeModal('modalEdit_{{ $driver->id }}')" class="px-4 py-2 bg-white border rounded text-sm font-bold text-gray-700">Batal</button>
                                            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded text-sm font-bold shadow hover:bg-blue-700"><i class="fa-solid fa-save"></i> Simpan Perubahan</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    @empty
                    <tr><td colspan="6" class="text-center py-10 text-gray-500">Belum ada data driver.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        @if ($drivers->hasPages())
        <div class="mt-4 border-t pt-4">{{ $drivers->links('vendor.pagination.tailwind') }}</div>
        @endif
    </div>
    
    {{-- Modal Hapus Massal --}}
    <div id="bulkDeleteModal" class="hidden fixed inset-0 z-[99999]">
        <div class="fixed inset-0 bg-gray-900 bg-opacity-75" onclick="closeModal('bulkDeleteModal')"></div>
        <div class="fixed inset-0 flex items-center justify-center p-4">
            <div class="bg-white rounded-lg p-6 max-w-sm w-full shadow-2xl">
                <h3 class="font-bold text-lg mb-2 text-gray-800">Konfirmasi Hapus</h3>
                <p class="text-sm text-gray-600 mb-4">Hapus <strong id="modalSelectedCount" class="text-red-600">0</strong> data terpilih permanen?</p>
                <div class="flex justify-end gap-2">
                    <button onclick="closeModal('bulkDeleteModal')" class="px-4 py-2 border rounded font-bold text-sm">Batal</button>
                    <button onclick="document.getElementById('bulkDeleteForm').submit()" class="px-4 py-2 bg-red-600 text-white rounded font-bold text-sm">Ya, Hapus</button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script>
    function openModal(id) { document.getElementById(id).classList.remove('hidden'); }
    function closeModal(id) { document.getElementById(id).classList.add('hidden'); }
    function toggleDetails(idx, btn) {
        document.querySelectorAll('.toggle-target-'+idx).forEach(el => {
            el.classList.toggle('hidden'); el.classList.toggle('block');
        });
    }
    function toggleSelectAllHeader(src) { document.querySelectorAll('.row-checkbox').forEach(cb => cb.checked = src.checked); updateBulkActionUI(); }
    function toggleSelectAll() {
        const cbs = document.querySelectorAll('.row-checkbox');
        const allChecked = Array.from(cbs).every(cb => cb.checked);
        cbs.forEach(cb => cb.checked = !allChecked);
        updateBulkActionUI();
    }
    function updateBulkActionUI() {
        const count = document.querySelectorAll('.row-checkbox:checked').length;
        document.getElementById('selectedCount').innerText = count;
        const bar = document.getElementById('bulkActionBar');
        if(count > 0) { bar.classList.add('active'); bar.style.display = 'flex'; }
        else { bar.classList.remove('active'); bar.style.display = 'none'; }
    }
    function showBulkDeleteModal() {
        const checked = document.querySelectorAll('.row-checkbox:checked');
        if (checked.length === 0) return alert("Pilih minimal satu data!");
        document.getElementById('modalSelectedCount').innerText = checked.length;
        
        const form = document.getElementById('bulkDeleteForm');
        form.querySelectorAll('input[name="selected_ids[]"]').forEach(el => el.remove());
        checked.forEach(cb => {
            const input = document.createElement('input');
            input.type = 'hidden'; input.name = 'selected_ids[]'; input.value = cb.value;
            form.appendChild(input);
        });
        openModal('bulkDeleteModal');
    }
    flatpickr("#date_range_picker", { mode: "range", dateFormat: "Y-m-d" });
</script>
@endpush