@extends('layouts.admin')
@section('title', 'Manajemen Pendaftaran Driver')
@section('page-title', 'Manajemen Pendaftaran Driver')

@push('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" type="text/css" href="https://npmcdn.com/flatpickr/dist/themes/airbnb.css">
    <style>
        @media (min-width: 768px) {
            .table-container { overflow-x: auto; }
            th.sticky-col, td.sticky-col { position: sticky; right: 0; background-color: white; z-index: 10; border-left: 1px solid #f3f4f6; box-shadow: -4px 0 15px -3px rgba(0,0,0,0.03); }
            thead th.sticky-col { background-color: #f9fafb; z-index: 20; }
            tr:hover td.sticky-col { background-color: #f8fafc; }
        }
        @media (max-width: 767px) {
            table, thead, tbody, th, td, tr { display: block; }
            thead tr { position: absolute; top: -9999px; left: -9999px; }
            tr { margin-bottom: 1rem; border: 1px solid #f3f4f6; border-radius: 0.75rem; background-color: white; box-shadow: 0 1px 2px rgba(0,0,0,0.02); }
            td { border: none; border-bottom: 1px solid #f9fafb; padding: 1rem !important; }
            td:last-child { border-bottom: none; }
        }
        .bulk-action-bar { transition: all 0.3s ease; opacity: 0; visibility: hidden; transform: translateY(-10px); display: none; }
        .bulk-action-bar.active { opacity: 1; visibility: visible; transform: translateY(0); display: flex; }
        .flatpickr-calendar { z-index: 9999 !important; border: none !important; box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1) !important; }
        
        /* Custom Scrollbar for better elegance */
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: #f1f5f9; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
    </style>
@endpush

@section('content')
<div class="container mx-auto px-4 py-8 max-w-7xl">

    {{-- ALERT NOTIFIKASI --}}
    @if(session('success'))
        <div class="mb-6 flex items-center justify-between p-4 text-sm text-emerald-800 bg-emerald-50 border border-emerald-200 rounded-xl shadow-sm">
            <div class="flex items-center gap-3"><i class="fa-solid fa-circle-check text-emerald-500 text-lg"></i> <span class="font-medium">{{ session('success') }}</span></div>
        </div>
    @endif
    @if(session('error'))
        <div class="mb-6 flex items-center justify-between p-4 text-sm text-rose-800 bg-rose-50 border border-rose-200 rounded-xl shadow-sm">
            <div class="flex items-center gap-3"><i class="fa-solid fa-triangle-exclamation text-rose-500 text-lg"></i> <span class="font-medium">{{ session('error') }}</span></div>
        </div>
    @endif
    @if($errors->any())
        <div class="mb-6 p-5 text-sm text-rose-800 bg-rose-50 border border-rose-200 rounded-xl shadow-sm">
            <div class="font-bold mb-2 flex items-center gap-2"><i class="fa-solid fa-circle-exclamation text-rose-500 text-lg"></i> Terjadi Kesalahan pada Input Data:</div>
            <ul class="mb-0 ps-7 list-disc text-rose-700 space-y-1">
                @foreach($errors->all() as $error) 
                    <li>{{ $error }}</li> 
                @endforeach
            </ul>
        </div>
    @endif

    {{-- STATISTIK (CLEAN & ELEGANT) --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5 mb-8">
        <div class="bg-white rounded-2xl p-6 border border-gray-100 shadow-sm flex items-center justify-between transition hover:shadow-md">
            <div>
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1">Total Pendaftar</p>
                <p class="text-3xl font-bold text-gray-800">{{ $totalDrivers }}</p>
            </div>
            <div class="h-12 w-12 rounded-full bg-slate-50 border border-slate-100 flex items-center justify-center text-slate-500">
                <i class="fas fa-users text-xl"></i>
            </div>
        </div>
        
        <div class="bg-white rounded-2xl p-6 border border-gray-100 shadow-sm flex items-center justify-between transition hover:shadow-md">
            <div>
                <p class="text-xs font-semibold text-amber-500 uppercase tracking-wider mb-1">Pending</p>
                <p class="text-3xl font-bold text-gray-800">{{ $pendingDrivers }}</p>
            </div>
            <div class="h-12 w-12 rounded-full bg-amber-50 border border-amber-100 flex items-center justify-center text-amber-500">
                <i class="fas fa-clock text-xl"></i>
            </div>
        </div>
        
        <div class="bg-white rounded-2xl p-6 border border-gray-100 shadow-sm flex items-center justify-between transition hover:shadow-md">
            <div>
                <p class="text-xs font-semibold text-emerald-500 uppercase tracking-wider mb-1">Disetujui</p>
                <p class="text-3xl font-bold text-gray-800">{{ $approvedDrivers }}</p>
            </div>
            <div class="h-12 w-12 rounded-full bg-emerald-50 border border-emerald-100 flex items-center justify-center text-emerald-500">
                <i class="fas fa-check-circle text-xl"></i>
            </div>
        </div>
        
        <div class="bg-white rounded-2xl p-6 border border-gray-100 shadow-sm flex items-center justify-between transition hover:shadow-md">
            <div>
                <p class="text-xs font-semibold text-rose-500 uppercase tracking-wider mb-1">Ditolak</p>
                <p class="text-3xl font-bold text-gray-800">{{ $rejectedDrivers }}</p>
            </div>
            <div class="h-12 w-12 rounded-full bg-rose-50 border border-rose-100 flex items-center justify-center text-rose-500">
                <i class="fas fa-times-circle text-xl"></i>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        
        {{-- HEADER & FILTER SECTION --}}
        <div class="p-6 border-b border-gray-100">
            <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-5">
                
                {{-- TAB STATUS --}}
                <div class="flex flex-nowrap overflow-x-auto gap-2 pb-2 lg:pb-0 w-full lg:w-auto scrollbar-hide">
                    @php $baseQuery = request()->except(['status', 'page']); @endphp
                    <a href="{{ route('admin.drivers.index', $baseQuery) }}" class="whitespace-nowrap px-4 py-2 text-sm font-medium rounded-lg transition-all {{ !request('status') ? 'bg-slate-800 text-white shadow-md' : 'bg-white text-slate-600 border border-gray-200 hover:bg-slate-50' }}">Semua</a>
                    <a href="{{ route('admin.drivers.index', array_merge($baseQuery, ['status'=>'pending', 'page'=>1])) }}" class="whitespace-nowrap px-4 py-2 text-sm font-medium rounded-lg transition-all {{ request('status')=='pending' ? 'bg-amber-500 text-white shadow-md' : 'bg-white text-slate-600 border border-gray-200 hover:bg-slate-50' }}">Pending</a>
                    <a href="{{ route('admin.drivers.index', array_merge($baseQuery, ['status'=>'approved', 'page'=>1])) }}" class="whitespace-nowrap px-4 py-2 text-sm font-medium rounded-lg transition-all {{ request('status')=='approved' ? 'bg-emerald-500 text-white shadow-md' : 'bg-white text-slate-600 border border-gray-200 hover:bg-slate-50' }}">Approved</a>
                    <a href="{{ route('admin.drivers.index', array_merge($baseQuery, ['status'=>'rejected', 'page'=>1])) }}" class="whitespace-nowrap px-4 py-2 text-sm font-medium rounded-lg transition-all {{ request('status')=='rejected' ? 'bg-rose-500 text-white shadow-md' : 'bg-white text-slate-600 border border-gray-200 hover:bg-slate-50' }}">Rejected</a>
                </div>

                {{-- FILTER FORM --}}
                <div class="w-full lg:w-auto">
                    <form action="{{ route('admin.drivers.index') }}" method="GET" class="flex flex-col sm:flex-row gap-3">
                        @if(request('status')) <input type="hidden" name="status" value="{{ request('status') }}"> @endif
                        
                        <div class="relative w-full sm:w-64">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400"><i class="fas fa-search text-sm"></i></div>
                            <input type="text" name="search" value="{{ request('search') }}" class="w-full pl-9 pr-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-slate-800 focus:border-slate-800 transition" placeholder="Cari Nama / No. WA...">
                        </div>
                        
                        <div class="relative w-full sm:w-56">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400"><i class="far fa-calendar-alt text-sm"></i></div>
                            <input type="text" id="date_range_picker" name="date_range" value="{{ request('date_range') }}" class="w-full pl-9 pr-4 py-2.5 border border-gray-200 rounded-xl text-sm bg-white focus:ring-2 focus:ring-slate-800 focus:border-slate-800 transition cursor-pointer" placeholder="Filter Tanggal..." readonly>
                        </div>
                        
                        <button type="submit" class="bg-slate-800 text-white px-5 py-2.5 rounded-xl text-sm font-medium hover:bg-slate-700 transition shadow-sm flex items-center justify-center">
                            Filter
                        </button>
                    </form>
                </div>
            </div>
        </div>

        {{-- BULK ACTION --}}
        <div class="px-6 pt-4 pb-2">
            <div id="bulkActionBar" class="bulk-action-bar bg-rose-50/50 border border-rose-100 rounded-xl p-3 flex-col sm:flex-row items-center justify-between gap-4">
                <div class="text-sm text-rose-800 font-medium flex items-center gap-2">
                    <i class="fas fa-check-circle text-rose-500"></i> <span id="selectedCount">0</span> Driver Terpilih
                </div>
                <div class="flex gap-2 w-full sm:w-auto">
                    <button type="button" onclick="toggleSelectAll()" class="bg-white border border-gray-200 px-4 py-2 rounded-lg text-sm font-medium hover:bg-gray-50 transition text-gray-700">Pilih Semua</button>
                    <button type="button" onclick="showBulkDeleteModal()" class="bg-rose-600 text-white px-4 py-2 rounded-lg text-sm font-medium flex items-center gap-2 hover:bg-rose-700 transition shadow-sm">
                        <i class="fas fa-trash-alt text-xs"></i> Hapus Terpilih
                    </button>
                </div>
            </div>
            <form id="bulkDeleteForm" action="{{ route('admin.drivers.bulk_destroy') }}" method="POST" class="hidden">@csrf @method('DELETE')</form>
        </div>

        {{-- TABEL DATA --}}
        <div class="table-container w-full">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50/50 border-y border-gray-100">
                        <th class="px-6 py-4 text-xs font-semibold text-gray-400 w-12"><input type="checkbox" onclick="toggleSelectAllHeader(this)" class="w-4 h-4 text-slate-800 rounded border-gray-300 focus:ring-slate-800 transition cursor-pointer"></th>
                        <th class="px-6 py-4 text-xs font-semibold text-gray-400 uppercase tracking-wider">No</th>
                        <th class="px-6 py-4 text-xs font-semibold text-gray-400 uppercase tracking-wider">Profil Driver</th>
                        <th class="px-6 py-4 text-xs font-semibold text-gray-400 uppercase tracking-wider">Kendaraan & Layanan</th>
                        <th class="px-6 py-4 text-xs font-semibold text-gray-400 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-4 text-center text-xs font-semibold text-gray-400 uppercase tracking-wider sticky-col">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($drivers as $index => $driver)
                    <tr class="hover:bg-slate-50/50 transition duration-150 ease-in-out group">
                        
                        {{-- CEKBOX --}}
                        <td class="px-6 py-5 align-top">
                            <input type="checkbox" name="selected_ids[]" value="{{ $driver->id }}" data-name="{{ $driver->nama_lengkap }}" onchange="updateBulkActionUI()" class="row-checkbox w-4 h-4 text-slate-800 rounded border-gray-300 focus:ring-slate-800 transition cursor-pointer mt-1">
                        </td>
                        
                        {{-- NO --}}
                        <td class="px-6 py-5 align-top text-sm text-gray-500 font-medium">
                            <span class="md:hidden font-semibold text-gray-400 text-xs block mb-1">NO</span>
                            {{ $drivers->firstItem() + $index }}
                        </td>
                        
                        {{-- PROFIL --}}
                        <td class="px-6 py-5 align-top">
                            <span class="md:hidden font-semibold text-gray-400 text-xs mb-1 block">PROFIL</span>
                            <div class="flex items-center gap-3">
                                <div class="h-10 w-10 rounded-full bg-slate-100 flex items-center justify-center text-slate-500 font-bold border border-slate-200">
                                    {{ strtoupper(substr($driver->nama_lengkap ?? 'D', 0, 1)) }}
                                </div>
                                <div>
                                    <div class="font-semibold text-gray-900 text-sm">{{ $driver->nama_lengkap ?? 'Tanpa Nama' }}</div>
                                    <div class="text-xs text-gray-500 mt-1 flex items-center gap-3">
                                        <span class="flex items-center gap-1"><i class="fa-solid fa-cake-candles text-gray-400"></i> {{ $driver->tanggal_lahir ? \Carbon\Carbon::parse($driver->tanggal_lahir)->age . ' Thn' : '-' }}</span>
                                        <span class="flex items-center gap-1 text-slate-600 font-medium"><i class="fa-brands fa-whatsapp text-emerald-500"></i> {{ $driver->nomor_wa ?? '-' }}</span>
                                    </div>
                                </div>
                            </div>
                            <button class="md:hidden mt-4 w-full bg-white border border-gray-200 py-2 rounded-xl text-xs font-medium text-gray-600" onclick="toggleDetails({{$index}}, this)">Lihat Detail <i class="fas fa-chevron-down ml-1"></i></button>
                        </td>

                        {{-- KENDARAAN --}}
                        <td class="hidden md:table-cell px-6 py-5 align-top toggle-target-{{$index}}">
                            <span class="md:hidden font-semibold text-gray-400 text-xs mb-2 block mt-4">KENDARAAN</span>
                            <div class="flex flex-col gap-1.5">
                                <div>
                                    @if($driver->jenis_layanan == 'mobil')
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-indigo-50 border border-indigo-100 text-indigo-700 text-[11px] font-semibold"><i class="fa-solid fa-car text-xs"></i> Sancaka CAR</span>
                                    @elseif($driver->jenis_layanan == 'motor')
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-amber-50 border border-amber-100 text-amber-700 text-[11px] font-semibold"><i class="fa-solid fa-motorcycle text-xs"></i> Sancaka RIDE</span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-gray-50 border border-gray-200 text-gray-500 text-[11px] font-semibold">-</span>
                                    @endif
                                </div>
                                <div class="text-sm font-medium text-gray-800 mt-1">
                                    {{ $driver->merk_kendaraan ?? '-' }} 
                                    @if($driver->tahun_kendaraan) <span class="text-gray-400 font-normal">({{ $driver->tahun_kendaraan }})</span> @endif
                                </div>
                                <div>
                                    @if($driver->plat_nomor)
                                        <span class="inline-block px-2 py-1 bg-white border border-gray-300 rounded text-xs font-bold text-gray-700 uppercase tracking-wider">{{ $driver->plat_nomor }}</span>
                                    @else
                                        <span class="text-xs text-gray-400">-</span>
                                    @endif
                                </div>
                            </div>
                        </td>

                        {{-- STATUS --}}
                        <td class="hidden md:table-cell px-6 py-5 align-top toggle-target-{{$index}}">
                            <span class="md:hidden font-semibold text-gray-400 text-xs mb-2 block mt-4">STATUS</span>
                            @if($driver->status == 'pending') 
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-amber-50 border border-amber-200 text-amber-700 text-xs font-medium"><span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span> Pending</span>
                            @elseif($driver->status == 'approved') 
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-emerald-50 border border-emerald-200 text-emerald-700 text-xs font-medium"><span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Approved</span>
                            @else 
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-rose-50 border border-rose-200 text-rose-700 text-xs font-medium"><span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span> Rejected</span> 
                            @endif
                        </td>

                        {{-- AKSI --}}
                        <td class="hidden md:table-cell px-6 py-5 align-middle text-sm sticky-col toggle-target-{{$index}}">
                            <div class="flex items-center justify-center space-x-2 opacity-100 lg:opacity-0 group-hover:opacity-100 transition-opacity">
                                <button type="button" onclick="openModal('modalDetail_{{ $driver->id }}')" class="h-9 w-9 flex items-center justify-center text-gray-400 hover:text-blue-600 bg-white border border-gray-200 hover:border-blue-200 hover:bg-blue-50 rounded-lg transition-all" title="Lihat Detail">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button type="button" onclick="openModal('modalEdit_{{ $driver->id }}')" class="h-9 w-9 flex items-center justify-center text-gray-400 hover:text-amber-600 bg-white border border-gray-200 hover:border-amber-200 hover:bg-amber-50 rounded-lg transition-all" title="Edit Data">
                                    <i class="fas fa-pen"></i>
                                </button>
                                <form action="{{ route('admin.drivers.destroy', $driver->id) }}" method="POST" class="m-0 inline" onsubmit="return confirm('Yakin ingin menghapus permanen data ini?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="h-9 w-9 flex items-center justify-center text-gray-400 hover:text-rose-600 bg-white border border-gray-200 hover:border-rose-200 hover:bg-rose-50 rounded-lg transition-all" title="Hapus Data">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>

                    {{-- ======================================================== --}}
                    {{-- MODAL DETAIL DRIVER --}}
                    {{-- ======================================================== --}}
                    <div id="modalDetail_{{ $driver->id }}" class="hidden fixed inset-0 z-[99999]">
                        <div class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm transition-opacity" onclick="closeModal('modalDetail_{{ $driver->id }}')"></div>
                        <div class="fixed inset-0 overflow-y-auto py-10">
                            <div class="flex min-h-full items-center justify-center p-4">
                                <div class="bg-white rounded-2xl shadow-xl w-full max-w-4xl overflow-hidden transform transition-all border border-gray-100">
                                    
                                    {{-- Header Modal --}}
                                    <div class="border-b border-gray-100 px-6 py-5 flex justify-between items-center bg-white">
                                        <div class="flex items-center gap-3">
                                            <div class="h-10 w-10 rounded-full bg-blue-50 flex items-center justify-center text-blue-600"><i class="fa-solid fa-address-card"></i></div>
                                            <div>
                                                <h5 class="text-lg font-bold text-gray-900">Detail Driver</h5>
                                                <p class="text-xs text-gray-500 font-medium">{{ $driver->id_pengguna ? 'ID: '.$driver->id_pengguna : 'Data Pendaftar Baru' }}</p>
                                            </div>
                                        </div>
                                        <button type="button" onclick="closeModal('modalDetail_{{ $driver->id }}')" class="text-gray-400 hover:text-gray-600 transition bg-gray-50 hover:bg-gray-100 rounded-full h-8 w-8 flex items-center justify-center"><i class="fas fa-times"></i></button>
                                    </div>
                                    
                                    <div class="px-6 py-6 bg-slate-50/50">
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                                            
                                            {{-- Box Kiri: Data Pribadi --}}
                                            <div class="bg-white p-5 rounded-xl border border-gray-100 shadow-sm">
                                                <h6 class="font-semibold text-gray-800 mb-4 text-sm flex items-center gap-2"><i class="fa-regular fa-user text-gray-400"></i> Informasi Pribadi</h6>
                                                <div class="space-y-3 text-sm">
                                                    <div class="flex justify-between border-b border-gray-50 pb-2"><span class="text-gray-500">Nama Lengkap</span> <span class="font-medium text-gray-900 text-right">{{ $driver->nama_lengkap ?? '-' }}</span></div>
                                                    <div class="flex justify-between border-b border-gray-50 pb-2"><span class="text-gray-500">NIK KTP</span> <span class="font-medium text-gray-900">{{ $driver->nomor_nik ?? '-' }}</span></div>
                                                    <div class="flex justify-between border-b border-gray-50 pb-2"><span class="text-gray-500">No. Kartu Keluarga</span> <span class="font-medium text-gray-900">{{ $driver->nomor_kk ?? '-' }}</span></div>
                                                    <div class="flex justify-between border-b border-gray-50 pb-2">
                                                        <span class="text-gray-500">TTL</span> 
                                                        <span class="font-medium text-gray-900 text-right">
                                                            {{ $driver->tempat_lahir ?? '-' }}, {{ $driver->tanggal_lahir ? \Carbon\Carbon::parse($driver->tanggal_lahir)->format('d M Y') . ' ('.\Carbon\Carbon::parse($driver->tanggal_lahir)->age.' Thn)' : '-' }}
                                                        </span>
                                                    </div>
                                                    <div class="flex justify-between border-b border-gray-50 pb-2"><span class="text-gray-500">No. WhatsApp</span> <span class="font-medium text-gray-900">{{ $driver->nomor_wa ?? '-' }}</span></div>
                                                    <div class="flex flex-col gap-1 pb-1">
                                                        <span class="text-gray-500">Alamat Domisili</span>
                                                        <span class="font-medium text-gray-900 leading-relaxed">{{ $driver->alamat_lengkap ?? '-' }}</span>
                                                    </div>
                                                </div>
                                            </div>

                                            {{-- Box Kanan: Kendaraan --}}
                                            <div class="bg-white p-5 rounded-xl border border-gray-100 shadow-sm h-fit">
                                                <h6 class="font-semibold text-gray-800 mb-4 text-sm flex items-center gap-2"><i class="fa-solid fa-car-side text-gray-400"></i> Detail Kendaraan</h6>
                                                <div class="space-y-3 text-sm">
                                                    <div class="flex justify-between border-b border-gray-50 pb-2"><span class="text-gray-500">Layanan</span> <span class="font-medium text-gray-900">{{ $driver->jenis_layanan ? strtoupper($driver->jenis_layanan) : '-' }}</span></div>
                                                    <div class="flex justify-between border-b border-gray-50 pb-2"><span class="text-gray-500">Merek Kendaraan</span> <span class="font-medium text-gray-900">{{ $driver->merk_kendaraan ?? '-' }}</span></div>
                                                    <div class="flex justify-between border-b border-gray-50 pb-2"><span class="text-gray-500">Tahun Pembuatan</span> <span class="font-medium text-gray-900">{{ $driver->tahun_kendaraan ?? '-' }}</span></div>
                                                    <div class="flex justify-between pb-1"><span class="text-gray-500 align-middle">Plat Nomor</span> <span class="inline-block px-2 py-0.5 border border-gray-300 rounded bg-gray-50 font-bold text-gray-800 uppercase text-xs">{{ $driver->plat_nomor ?? '-' }}</span></div>
                                                </div>
                                            </div>
                                        </div>

                                        {{-- Box Bawah: Dokumen (CLEAN DESIGN) --}}
                                        <div class="bg-white p-5 rounded-xl border border-gray-100 shadow-sm mb-6">
                                            <h6 class="font-semibold text-gray-800 mb-4 text-sm flex items-center gap-2"><i class="fa-regular fa-folder-open text-gray-400"></i> Kelengkapan Berkas</h6>
                                            <div class="flex flex-wrap gap-3">
                                                @php
                                                    $docs = [
                                                        ['file' => $driver->foto_wajah, 'icon' => 'fa-image', 'label' => 'Foto Wajah'],
                                                        ['file' => $driver->file_ktp, 'icon' => 'fa-id-card', 'label' => 'KTP'],
                                                        ['file' => $driver->file_sim, 'icon' => 'fa-id-badge', 'label' => 'SIM'],
                                                        ['file' => $driver->file_skck, 'icon' => 'fa-file-shield', 'label' => 'SKCK'],
                                                        ['file' => $driver->file_stnk, 'icon' => 'fa-file-lines', 'label' => 'STNK'],
                                                        ['file' => $driver->foto_motor, 'icon' => 'fa-motorcycle', 'label' => 'Kendaraan'],
                                                        ['file' => $driver->file_buku_rekening, 'icon' => 'fa-money-check-dollar', 'label' => 'Rekening'],
                                                        ['file' => $driver->file_kk, 'icon' => 'fa-users-rectangle', 'label' => 'KK'],
                                                        ['file' => $driver->file_bpkb, 'icon' => 'fa-book', 'label' => 'BPKB'],
                                                        ['file' => $driver->file_buku_nikah, 'icon' => 'fa-heart', 'label' => 'Buku Nikah'],
                                                    ];
                                                    $hasDoc = false;
                                                @endphp

                                                @foreach($docs as $doc)
                                                    @if($doc['file'])
                                                        @php $hasDoc = true; @endphp
                                                        <a href="{{ asset('storage/'.$doc['file']) }}" target="_blank" class="flex items-center gap-2 px-3 py-2 bg-white border border-gray-200 hover:border-slate-400 hover:bg-slate-50 text-slate-700 font-medium rounded-lg text-xs transition-all shadow-sm">
                                                            <i class="fa-solid {{ $doc['icon'] }} text-slate-400"></i> {{ $doc['label'] }}
                                                        </a>
                                                    @endif
                                                @endforeach

                                                @if(!$hasDoc)
                                                    <div class="text-sm text-gray-400 italic py-2 w-full text-center border border-dashed border-gray-200 rounded-lg">Tidak ada berkas yang diunggah.</div>
                                                @endif
                                            </div>
                                        </div>

                                        {{-- LOGIKA TOMBOL STATUS --}}
                                        <div class="flex flex-col sm:flex-row gap-3 pt-2">
                                            @if($driver->status == 'pending')
                                                <form action="{{ route('admin.drivers.status', $driver->id) }}" method="POST" class="w-full sm:w-1/2 m-0">
                                                    @csrf @method('PATCH') <input type="hidden" name="status" value="approved">
                                                    <button type="submit" class="w-full bg-slate-800 hover:bg-slate-900 text-white py-3 rounded-xl font-medium text-sm transition"><i class="fa-solid fa-check mr-2"></i> Setujui Pendaftaran</button>
                                                </form>
                                                <form action="{{ route('admin.drivers.status', $driver->id) }}" method="POST" class="w-full sm:w-1/2 m-0">
                                                    @csrf @method('PATCH') <input type="hidden" name="status" value="rejected">
                                                    <button type="submit" class="w-full bg-white border border-rose-200 text-rose-600 hover:bg-rose-50 py-3 rounded-xl font-medium text-sm transition"><i class="fa-solid fa-xmark mr-2"></i> Tolak Data</button>
                                                </form>
                                            @elseif($driver->status == 'approved')
                                                <button type="button" onclick="closeModal('modalDetail_{{ $driver->id }}'); openModal('modalEdit_{{ $driver->id }}')" class="w-full sm:w-1/2 bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 py-3 rounded-xl font-medium text-sm transition"><i class="fa-solid fa-pen mr-2"></i> Edit Data</button>
                                                <form action="{{ route('admin.drivers.status', $driver->id) }}" method="POST" class="w-full sm:w-1/2 m-0">
                                                    @csrf @method('PATCH') <input type="hidden" name="status" value="rejected">
                                                    <button type="submit" onclick="return confirm('Yakin membatalkan persetujuan driver ini?')" class="w-full bg-white border border-rose-200 text-rose-600 hover:bg-rose-50 py-3 rounded-xl font-medium text-sm transition"><i class="fa-solid fa-ban mr-2"></i> Batalkan Status</button>
                                                </form>
                                            @else
                                                <button type="button" onclick="closeModal('modalDetail_{{ $driver->id }}'); openModal('modalEdit_{{ $driver->id }}')" class="w-full sm:w-1/2 bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 py-3 rounded-xl font-medium text-sm transition"><i class="fa-solid fa-pen mr-2"></i> Edit Data</button>
                                                <form action="{{ route('admin.drivers.status', $driver->id) }}" method="POST" class="w-full sm:w-1/2 m-0">
                                                    @csrf @method('PATCH') <input type="hidden" name="status" value="approved">
                                                    <button type="submit" class="w-full bg-slate-800 hover:bg-slate-900 text-white py-3 rounded-xl font-medium text-sm transition"><i class="fa-solid fa-rotate-left mr-2"></i> Pulihkan & Setujui</button>
                                                </form>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- ======================================================== --}}
                    {{-- MODAL EDIT DRIVER --}}
                    {{-- ======================================================== --}}
                    <div id="modalEdit_{{ $driver->id }}" class="hidden fixed inset-0 z-[99999]">
                        <div class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm transition-opacity" onclick="closeModal('modalEdit_{{ $driver->id }}')"></div>
                        <div class="fixed inset-0 overflow-y-auto py-6">
                            <div class="flex min-h-full items-center justify-center p-4">
                                <div class="bg-white rounded-2xl shadow-xl w-full max-w-5xl overflow-hidden border border-gray-100">
                                    
                                    <div class="border-b border-gray-100 px-6 py-5 flex justify-between items-center bg-white">
                                        <div class="flex items-center gap-3">
                                            <div class="h-10 w-10 rounded-full bg-amber-50 flex items-center justify-center text-amber-500"><i class="fa-solid fa-pen-to-square"></i></div>
                                            <div>
                                                <h5 class="text-lg font-bold text-gray-900">Edit Data Driver</h5>
                                                <p class="text-xs text-gray-500 font-medium">{{ $driver->nama_lengkap ?? '-' }}</p>
                                            </div>
                                        </div>
                                        <button type="button" onclick="closeModal('modalEdit_{{ $driver->id }}')" class="text-gray-400 hover:text-gray-600 transition bg-gray-50 hover:bg-gray-100 rounded-full h-8 w-8 flex items-center justify-center"><i class="fas fa-times"></i></button>
                                    </div>
                                    
                                    <form action="{{ route('admin.drivers.update', $driver->id) }}" method="POST" enctype="multipart/form-data" class="m-0">
                                        @csrf @method('PUT')
                                        <div class="px-6 py-6 max-h-[65vh] overflow-y-auto bg-slate-50/30">
                                            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
                                                
                                                {{-- KIRI: DATA TEKS --}}
                                                <div class="lg:col-span-7 space-y-5">
                                                    <h6 class="font-semibold text-gray-800 text-sm flex items-center gap-2 mb-4"><i class="fa-regular fa-id-card text-gray-400"></i> Identitas & Kendaraan</h6>
                                                    
                                                    <div>
                                                        <label class="block text-xs font-medium text-gray-500 mb-1.5">Nama Lengkap</label>
                                                        <input type="text" name="nama_lengkap" class="w-full border border-gray-200 p-2.5 rounded-xl text-sm bg-white focus:ring-2 focus:ring-slate-800 focus:border-slate-800 outline-none transition" value="{{ $driver->nama_lengkap }}" required>
                                                    </div>
                                                    
                                                    <div class="flex flex-col sm:flex-row gap-4">
                                                        <div class="w-full sm:w-1/2">
                                                            <label class="block text-xs font-medium text-gray-500 mb-1.5">Tempat Lahir</label>
                                                            <input type="text" name="tempat_lahir" class="w-full border border-gray-200 p-2.5 rounded-xl text-sm bg-white focus:ring-2 focus:ring-slate-800 focus:border-slate-800 outline-none transition" value="{{ $driver->tempat_lahir }}">
                                                        </div>
                                                        <div class="w-full sm:w-1/2">
                                                            <label class="block text-xs font-medium text-gray-500 mb-1.5">Tanggal Lahir</label>
                                                            <input type="date" name="tanggal_lahir" class="w-full border border-gray-200 p-2.5 rounded-xl text-sm bg-white focus:ring-2 focus:ring-slate-800 focus:border-slate-800 outline-none transition" value="{{ $driver->tanggal_lahir ? \Carbon\Carbon::parse($driver->tanggal_lahir)->format('Y-m-d') : '' }}">
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="flex flex-col sm:flex-row gap-4">
                                                        <div class="w-full sm:w-1/2">
                                                            <label class="block text-xs font-medium text-gray-500 mb-1.5">Nomor NIK KTP</label>
                                                            <input type="number" name="nomor_nik" class="w-full border border-gray-200 p-2.5 rounded-xl text-sm bg-white focus:ring-2 focus:ring-slate-800 focus:border-slate-800 outline-none transition" value="{{ $driver->nomor_nik }}" required>
                                                        </div>
                                                        <div class="w-full sm:w-1/2">
                                                            <label class="block text-xs font-medium text-gray-500 mb-1.5">Nomor WhatsApp</label>
                                                            <input type="text" name="nomor_wa" class="w-full border border-gray-200 p-2.5 rounded-xl text-sm bg-white focus:ring-2 focus:ring-slate-800 focus:border-slate-800 outline-none transition" value="{{ $driver->nomor_wa }}" required>
                                                        </div>
                                                    </div>
                                                    
                                                    <div>
                                                        <label class="block text-xs font-medium text-gray-500 mb-1.5">Alamat Domisili</label>
                                                        <textarea name="alamat_lengkap" class="w-full border border-gray-200 p-2.5 rounded-xl text-sm bg-white focus:ring-2 focus:ring-slate-800 focus:border-slate-800 outline-none transition" rows="3" required>{{ $driver->alamat_lengkap }}</textarea>
                                                    </div>

                                                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-2">
                                                        <div>
                                                            <label class="block text-xs font-medium text-gray-500 mb-1.5">Jenis Layanan</label>
                                                            <select name="jenis_layanan" class="w-full border border-gray-200 p-2.5 rounded-xl text-sm bg-white focus:ring-2 focus:ring-slate-800 focus:border-slate-800 outline-none transition" required>
                                                                <option value="" {{ !$driver->jenis_layanan ? 'selected' : '' }} disabled>Pilih</option>
                                                                <option value="motor" {{ $driver->jenis_layanan=='motor'?'selected':'' }}>RIDE (Motor)</option>
                                                                <option value="mobil" {{ $driver->jenis_layanan=='mobil'?'selected':'' }}>CAR (Mobil)</option>
                                                            </select>
                                                        </div>
                                                        <div>
                                                            <label class="block text-xs font-medium text-gray-500 mb-1.5">Merek Kendaraan</label>
                                                            <input type="text" name="merk_kendaraan" class="w-full border border-gray-200 p-2.5 rounded-xl text-sm bg-white focus:ring-2 focus:ring-slate-800 focus:border-slate-800 outline-none transition" value="{{ $driver->merk_kendaraan }}">
                                                        </div>
                                                        <div>
                                                            <label class="block text-xs font-medium text-gray-500 mb-1.5">Thn Pembuatan</label>
                                                            <input type="number" name="tahun_kendaraan" class="w-full border border-gray-200 p-2.5 rounded-xl text-sm bg-white focus:ring-2 focus:ring-slate-800 focus:border-slate-800 outline-none transition" value="{{ $driver->tahun_kendaraan }}">
                                                        </div>
                                                    </div>
                                                    
                                                    <div>
                                                        <label class="block text-xs font-medium text-gray-500 mb-1.5">Plat Nomor</label>
                                                        <input type="text" name="plat_nomor" class="w-full md:w-1/3 border border-gray-200 p-2.5 rounded-xl text-sm bg-white focus:ring-2 focus:ring-slate-800 focus:border-slate-800 outline-none transition uppercase font-medium" value="{{ $driver->plat_nomor }}">
                                                    </div>
                                                </div>

                                                {{-- KANAN: UPLOAD FILE --}}
                                                <div class="lg:col-span-5 space-y-4">
                                                    <h6 class="font-semibold text-gray-800 text-sm flex items-center gap-2 mb-4"><i class="fa-solid fa-cloud-arrow-up text-gray-400"></i> Update Dokumen</h6>
                                                    <div class="bg-blue-50 border border-blue-100 text-blue-700 p-3 rounded-lg text-xs mb-4 flex items-start gap-2">
                                                        <i class="fa-solid fa-circle-info mt-0.5"></i>
                                                        <p>Kosongkan input file jika tidak ingin mengubah dokumen lama.</p>
                                                    </div>
                                                    
                                                    @php
                                                    $dokumenList = [
                                                        'foto_wajah'=>'Foto Wajah', 'file_ktp'=>'KTP', 'file_sim'=>'SIM', 
                                                        'file_skck'=>'SKCK', 'file_buku_rekening'=>'Buku Rekening', 'file_stnk'=>'STNK', 
                                                        'foto_motor'=>'Foto Kendaraan', 'file_kk'=>'Kartu Keluarga'
                                                    ];
                                                    @endphp
                                                    
                                                    <div class="space-y-3">
                                                        @foreach($dokumenList as $field => $label)
                                                        <div>
                                                            <label class="block text-xs font-medium text-gray-600 mb-1">{{ $label }}</label>
                                                            <input type="file" name="{{ $field }}" class="w-full border border-dashed border-gray-300 p-2 rounded-xl text-xs bg-white focus:bg-gray-50 cursor-pointer hover:border-slate-400 transition-colors file:mr-3 file:py-1 file:px-3 file:rounded-md file:border-0 file:text-xs file:font-medium file:bg-slate-50 file:text-slate-700 hover:file:bg-slate-100" accept=".jpg,.png,.jpeg,.pdf">
                                                        </div>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="px-6 py-5 bg-white border-t border-gray-100 flex justify-end gap-3 rounded-b-2xl">
                                            <button type="button" onclick="closeModal('modalEdit_{{ $driver->id }}')" class="px-5 py-2.5 bg-white border border-gray-200 rounded-xl text-sm font-medium text-gray-700 hover:bg-gray-50 transition">Batal</button>
                                            <button type="submit" class="px-5 py-2.5 bg-slate-800 text-white rounded-xl text-sm font-medium hover:bg-slate-900 transition flex items-center gap-2"><i class="fa-solid fa-save"></i> Simpan Perubahan</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    @empty
                    <tr>
                        <td colspan="6" class="text-center py-16 text-gray-400 bg-white">
                            <div class="flex flex-col items-center justify-center">
                                <div class="h-16 w-16 bg-gray-50 rounded-full flex items-center justify-center mb-4"><i class="fa-regular fa-folder-open text-2xl text-gray-300"></i></div>
                                <span class="font-medium text-sm text-gray-500">Belum ada data pendaftaran driver.</span>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        @if ($drivers->hasPages())
        <div class="p-4 border-t border-gray-100 bg-white">
            {{ $drivers->links('vendor.pagination.tailwind') }}
        </div>
        @endif
    </div>
    
    {{-- ======================================================== --}}
    {{-- MODAL HAPUS MASSAL --}}
    {{-- ======================================================== --}}
    <div id="bulkDeleteModal" class="hidden fixed inset-0 z-[99999]">
        <div class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm transition-opacity" onclick="closeModal('bulkDeleteModal')"></div>
        <div class="fixed inset-0 flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl p-6 max-w-sm w-full shadow-xl transform transition-all border border-gray-100">
                <div class="text-center">
                    <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-rose-50 border border-rose-100 mb-4">
                        <i class="fa-solid fa-trash-can text-rose-500 text-xl"></i>
                    </div>
                    <h3 class="font-bold text-lg mb-2 text-gray-900">Konfirmasi Hapus</h3>
                    <p class="text-sm text-gray-500 mb-6 leading-relaxed">Anda yakin ingin menghapus <strong id="modalSelectedCount" class="text-rose-600 font-bold">0</strong> data driver terpilih secara permanen? Data tidak bisa dikembalikan.</p>
                </div>
                <div class="flex justify-center gap-3">
                    <button type="button" onclick="closeModal('bulkDeleteModal')" class="w-1/2 px-4 py-2.5 bg-white border border-gray-200 rounded-xl font-medium text-sm text-gray-700 hover:bg-gray-50 transition">Batal</button>
                    <button type="button" onclick="document.getElementById('bulkDeleteForm').submit()" class="w-1/2 px-4 py-2.5 bg-rose-600 hover:bg-rose-700 text-white rounded-xl font-medium text-sm transition">Ya, Hapus</button>
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
        let icon = btn.querySelector('i');
        if(icon.classList.contains('fa-chevron-down')){
            icon.classList.replace('fa-chevron-down', 'fa-chevron-up');
            btn.innerHTML = 'Tutup Detail <i class="fas fa-chevron-up ml-1"></i>';
        } else {
            icon.classList.replace('fa-chevron-up', 'fa-chevron-down');
            btn.innerHTML = 'Lihat Detail <i class="fas fa-chevron-down ml-1"></i>';
        }
    }
    
    function toggleSelectAllHeader(src) { 
        document.querySelectorAll('.row-checkbox').forEach(cb => cb.checked = src.checked); 
        updateBulkActionUI(); 
    }
    
    function toggleSelectAll() {
        const cbs = document.querySelectorAll('.row-checkbox');
        const allChecked = Array.from(cbs).every(cb => cb.checked);
        cbs.forEach(cb => cb.checked = !allChecked);
        document.querySelector('input[onclick="toggleSelectAllHeader(this)"]').checked = !allChecked;
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
        if (checked.length === 0) return alert("Pilih minimal satu data terlebih dahulu!");
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