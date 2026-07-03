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

    {{-- ALERT NOTIFIKASI --}}
    @if(session('success'))
        <div class="mb-6 flex items-center justify-between p-4 text-sm text-green-800 bg-green-50 border-l-4 border-green-500 rounded shadow-sm">
            <div class="flex items-center gap-2"><i class="fa-solid fa-circle-check"></i> <b>{{ session('success') }}</b></div>
        </div>
    @endif
    @if(session('error'))
        <div class="mb-6 flex items-center justify-between p-4 text-sm text-red-800 bg-red-50 border-l-4 border-red-500 rounded shadow-sm">
            <div class="flex items-center gap-2"><i class="fa-solid fa-triangle-exclamation"></i> <b>{{ session('error') }}</b></div>
        </div>
    @endif
    @if($errors->any())
        <div class="mb-6 p-4 text-sm text-red-800 bg-red-50 border-l-4 border-red-500 rounded shadow-sm">
            <div class="font-bold mb-1"><i class="fa-solid fa-circle-exclamation"></i> Terjadi Kesalahan pada Input Data:</div>
            <ul class="mb-0 ps-5 list-disc">
                @foreach($errors->all() as $error) 
                    <li>{{ $error }}</li> 
                @endforeach
            </ul>
        </div>
    @endif

    <div class="bg-white p-4 sm:p-6 rounded-lg shadow-md border border-gray-100">
        
        {{-- FILTER FORM --}}
        <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center mb-6 gap-4">
            <div class="w-full lg:w-3/4">
                <form action="{{ route('admin.drivers.index') }}" method="GET" class="flex flex-col md:flex-row gap-3">
                    @if(request('status')) <input type="hidden" name="status" value="{{ request('status') }}"> @endif
                    
                    <div class="relative w-full md:w-1/3">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400"><i class="fas fa-search"></i></div>
                        <input type="text" name="search" value="{{ request('search') }}" class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500" placeholder="Cari Nama / No. WA / NIK...">
                    </div>
                    
                    <div class="relative w-full md:w-1/3 group">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400"><i class="far fa-calendar-alt"></i></div>
                        <input type="text" id="date_range_picker" name="date_range" value="{{ request('date_range') }}" class="w-full pl-10 pr-10 py-2.5 border border-gray-300 rounded-lg text-sm bg-white focus:ring-blue-500 focus:border-blue-500" placeholder="Filter Tanggal..." readonly>
                    </div>
                    
                    <button type="submit" class="bg-blue-600 text-white px-5 py-2.5 rounded-lg text-sm font-bold hover:bg-blue-700 transition flex items-center justify-center gap-2 shadow-sm">
                        <i class="fas fa-filter"></i> Filter
                    </button>
                </form>
            </div>
        </div>

        {{-- TAB STATUS --}}
        <div class="flex flex-wrap gap-2 mb-6 border-b pb-4">
            @php $baseQuery = request()->except(['status', 'page']); @endphp
            <a href="{{ route('admin.drivers.index', $baseQuery) }}" class="px-4 py-2 text-xs font-bold rounded-full border transition-colors {{ !request('status') ? 'bg-blue-600 text-white border-blue-600 shadow-sm' : 'bg-white text-gray-600 border-gray-300 hover:bg-gray-100' }}">Semua</a>
            <a href="{{ route('admin.drivers.index', array_merge($baseQuery, ['status'=>'pending', 'page'=>1])) }}" class="px-4 py-2 text-xs font-bold rounded-full border transition-colors {{ request('status')=='pending' ? 'bg-blue-600 text-white border-blue-600 shadow-sm' : 'bg-white text-gray-600 border-gray-300 hover:bg-gray-100' }}">Pending</a>
            <a href="{{ route('admin.drivers.index', array_merge($baseQuery, ['status'=>'approved', 'page'=>1])) }}" class="px-4 py-2 text-xs font-bold rounded-full border transition-colors {{ request('status')=='approved' ? 'bg-blue-600 text-white border-blue-600 shadow-sm' : 'bg-white text-gray-600 border-gray-300 hover:bg-gray-100' }}">Approved</a>
            <a href="{{ route('admin.drivers.index', array_merge($baseQuery, ['status'=>'rejected', 'page'=>1])) }}" class="px-4 py-2 text-xs font-bold rounded-full border transition-colors {{ request('status')=='rejected' ? 'bg-blue-600 text-white border-blue-600 shadow-sm' : 'bg-white text-gray-600 border-gray-300 hover:bg-gray-100' }}">Rejected</a>
        </div>

        {{-- STATISTIK --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <div class="relative overflow-hidden rounded-xl bg-slate-700 p-5 shadow-sm text-white">
                <p class="text-3xl font-extrabold">{{ $totalDrivers }}</p>
                <p class="text-xs font-bold uppercase mt-1 tracking-wider opacity-80">Total Pendaftar</p>
                <i class="fas fa-users fa-4x absolute right-0 top-0 -mt-2 -mr-2 opacity-20"></i>
            </div>
            <div class="relative overflow-hidden rounded-xl bg-amber-500 p-5 shadow-sm text-white">
                <p class="text-3xl font-extrabold">{{ $pendingDrivers }}</p>
                <p class="text-xs font-bold uppercase mt-1 tracking-wider opacity-80">Pending</p>
                <i class="fas fa-clock fa-4x absolute right-0 top-0 -mt-2 -mr-2 opacity-20"></i>
            </div>
            <div class="relative overflow-hidden rounded-xl bg-emerald-500 p-5 shadow-sm text-white">
                <p class="text-3xl font-extrabold">{{ $approvedDrivers }}</p>
                <p class="text-xs font-bold uppercase mt-1 tracking-wider opacity-80">Disetujui</p>
                <i class="fas fa-check-circle fa-4x absolute right-0 top-0 -mt-2 -mr-2 opacity-20"></i>
            </div>
            <div class="relative overflow-hidden rounded-xl bg-rose-500 p-5 shadow-sm text-white">
                <p class="text-3xl font-extrabold">{{ $rejectedDrivers }}</p>
                <p class="text-xs font-bold uppercase mt-1 tracking-wider opacity-80">Ditolak</p>
                <i class="fas fa-times-circle fa-4x absolute right-0 top-0 -mt-2 -mr-2 opacity-20"></i>
            </div>
        </div>

        {{-- BULK ACTION --}}
        <div id="bulkActionBar" class="bulk-action-bar bg-red-50 border border-red-200 rounded-lg p-3 mb-4 flex-col sm:flex-row items-center justify-between gap-3 shadow-sm">
            <div class="text-sm text-red-800 font-bold flex items-center gap-2">
                <i class="fas fa-check-square text-red-500 text-lg"></i> <span id="selectedCount">0</span> Driver Terpilih
            </div>
            <div class="flex gap-2 w-full sm:w-auto">
                <button type="button" onclick="toggleSelectAll()" class="bg-white border border-gray-300 px-4 py-2 rounded-lg text-sm font-bold hover:bg-gray-100 transition">Pilih Semua</button>
                <button type="button" onclick="showBulkDeleteModal()" class="bg-red-600 text-white px-4 py-2 rounded-lg text-sm font-bold flex items-center gap-2 hover:bg-red-700 transition shadow-sm"><i class="fas fa-trash-alt"></i> Hapus Terpilih</button>
            </div>
        </div>
        <form id="bulkDeleteForm" action="{{ route('admin.drivers.bulk_destroy') }}" method="POST" class="hidden">@csrf @method('DELETE')</form>

        {{-- TABEL DATA --}}
        <div class="table-container border border-gray-200 rounded-lg overflow-hidden">
            <table class="w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 w-10"><input type="checkbox" onclick="toggleSelectAllHeader(this)" class="w-4 h-4 text-blue-600 rounded border-gray-300 cursor-pointer"></th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">No</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Profil & Usia</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Layanan & Kendaraan</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-4 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider sticky-col bg-gray-50">Aksi</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse ($drivers as $index => $driver)
                    <tr class="hover:bg-blue-50 transition-colors">
                        {{-- CEKBOX --}}
                        <td class="px-4 py-4 align-top border-b md:border-none bg-gray-50 md:bg-transparent">
                            <span class="md:hidden font-bold text-gray-400 text-xs mr-2">PILIH:</span>
                            <input type="checkbox" name="selected_ids[]" value="{{ $driver->id }}" data-name="{{ $driver->nama_lengkap }}" onchange="updateBulkActionUI()" class="row-checkbox w-4 h-4 text-blue-600 rounded border-gray-300 cursor-pointer shadow-sm">
                        </td>
                        
                        {{-- NO --}}
                        <td class="px-4 py-4 align-top text-sm text-gray-500"><span class="md:hidden font-bold text-gray-400 text-xs mr-2">NO:</span>{{ $drivers->firstItem() + $index }}</td>
                        
                        {{-- PROFIL --}}
                        <td class="px-4 py-4 align-top text-sm">
                            <span class="md:hidden font-bold text-gray-400 text-xs mb-1 block">PROFIL:</span>
                            <div class="font-bold text-gray-900">{{ $driver->nama_lengkap ?? '-' }}</div>
                            <div class="text-xs text-gray-500 mt-1 flex items-center gap-1.5">
                                <i class="fa-solid fa-cake-candles text-gray-400"></i> 
                                @if($driver->tanggal_lahir)
                                    {{ \Carbon\Carbon::parse($driver->tanggal_lahir)->age }} Tahun
                                @else
                                    <span class="text-red-400 italic">- (Belum diisi)</span>
                                @endif
                            </div>
                            <div class="text-xs text-blue-600 font-semibold mt-1 flex items-center gap-1.5">
                                <i class="fa-brands fa-whatsapp text-green-500"></i> {{ $driver->nomor_wa ?? '-' }}
                            </div>
                            <button class="md:hidden mt-3 w-full bg-white border border-gray-300 py-2 rounded-lg text-xs font-bold text-gray-600 shadow-sm" onclick="toggleDetails({{$index}}, this)">Lihat Detail <i class="fas fa-chevron-down ml-1"></i></button>
                        </td>

                        {{-- KENDARAAN --}}
                        <td class="hidden md:table-cell px-4 py-4 align-top text-sm toggle-target-{{$index}} bg-gray-50 md:bg-transparent">
                            <span class="md:hidden font-bold text-gray-400 text-xs mb-1 block mt-2">LAYANAN:</span>
                            
                            @if($driver->jenis_layanan == 'mobil')
                                <span class="bg-indigo-100 text-indigo-800 text-[11px] font-bold px-2 py-0.5 rounded border border-indigo-200"><i class="fa-solid fa-car mr-1"></i> Sancaka CAR</span>
                            @elseif($driver->jenis_layanan == 'motor')
                                <span class="bg-orange-100 text-orange-800 text-[11px] font-bold px-2 py-0.5 rounded border border-orange-200"><i class="fa-solid fa-motorcycle mr-1"></i> Sancaka RIDE</span>
                            @else
                                <span class="bg-gray-100 text-gray-600 text-[11px] font-bold px-2 py-0.5 rounded border border-gray-200">-</span>
                            @endif
                            
                            <div class="font-semibold text-gray-700 mt-2">
                                {{ $driver->merk_kendaraan ?? '-' }} 
                                @if($driver->tahun_kendaraan)
                                    <span class="text-gray-500 font-normal">({{ $driver->tahun_kendaraan }})</span>
                                @endif
                            </div>
                            
                            @if($driver->plat_nomor)
                                <div class="text-xs font-bold text-gray-900 border border-gray-400 px-2 py-0.5 inline-block mt-1 bg-white rounded uppercase shadow-sm">{{ $driver->plat_nomor }}</div>
                            @else
                                <div class="text-xs text-gray-400 mt-1">-</div>
                            @endif
                        </td>

                        {{-- STATUS --}}
                        <td class="hidden md:table-cell px-4 py-4 align-top text-sm toggle-target-{{$index}} bg-gray-50 md:bg-transparent">
                            <span class="md:hidden font-bold text-gray-400 text-xs mb-1 block mt-2">STATUS:</span>
                            @if($driver->status == 'pending') 
                                <span class="bg-yellow-100 border border-yellow-200 text-yellow-800 text-xs font-bold px-2.5 py-1 rounded-full">Pending</span>
                            @elseif($driver->status == 'approved') 
                                <span class="bg-green-100 border border-green-200 text-green-800 text-xs font-bold px-2.5 py-1 rounded-full">Approved</span>
                            @else 
                                <span class="bg-red-100 border border-red-200 text-red-800 text-xs font-bold px-2.5 py-1 rounded-full">Rejected</span> 
                            @endif
                        </td>

                        {{-- AKSI --}}
                        <td class="hidden md:table-cell px-4 py-4 align-middle text-sm sticky-col bg-white toggle-target-{{$index}}">
                            <div class="flex items-center justify-center space-x-3">
                                <button type="button" onclick="openModal('modalDetail_{{ $driver->id }}')" class="text-gray-400 hover:text-blue-600 bg-gray-50 hover:bg-blue-50 p-2 rounded-lg transition" title="Lihat Detail">
                                    <i class="fas fa-eye fa-lg"></i>
                                </button>
                                <button type="button" onclick="openModal('modalEdit_{{ $driver->id }}')" class="text-gray-400 hover:text-yellow-600 bg-gray-50 hover:bg-yellow-50 p-2 rounded-lg transition" title="Edit Data">
                                    <i class="fas fa-pencil-alt fa-lg"></i>
                                </button>
                                <form action="{{ route('admin.drivers.destroy', $driver->id) }}" method="POST" class="m-0 inline" onsubmit="return confirm('Yakin ingin menghapus permanen data ini?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-gray-400 hover:text-red-600 bg-gray-50 hover:bg-red-50 p-2 rounded-lg transition" title="Hapus Data">
                                        <i class="fas fa-trash-alt fa-lg"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>

                    {{-- ======================================================== --}}
                    {{-- MODAL DETAIL DRIVER (Desain Rapi & Handle Null)          --}}
                    {{-- ======================================================== --}}
                    <div id="modalDetail_{{ $driver->id }}" class="hidden fixed inset-0 z-[99999]">
                        <div class="fixed inset-0 bg-gray-900 bg-opacity-75 transition-opacity" onclick="closeModal('modalDetail_{{ $driver->id }}')"></div>
                        <div class="fixed inset-0 overflow-y-auto pt-10 pb-10">
                            <div class="flex min-h-full items-center justify-center p-4">
                                <div class="bg-white rounded-xl shadow-2xl w-full max-w-4xl border border-gray-200 overflow-hidden transform transition-all">
                                    <div class="border-b px-6 py-4 bg-slate-50 flex justify-between items-center">
                                        <h5 class="text-lg font-bold text-slate-800"><i class="fa-solid fa-address-card text-blue-600 mr-2"></i> Detail Driver</h5>
                                        <button type="button" onclick="closeModal('modalDetail_{{ $driver->id }}')" class="text-gray-400 hover:text-red-600 transition bg-white rounded-full p-1"><i class="fas fa-times fa-lg"></i></button>
                                    </div>
                                    
                                    <div class="px-6 py-5">
                                        {{-- Row Data --}}
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-5">
                                            
                                            {{-- Box Kiri: Data Pribadi --}}
                                            <div class="border border-gray-200 p-4 rounded-lg bg-white shadow-sm">
                                                <h6 class="font-bold text-gray-800 border-b pb-2 mb-3 text-sm"><i class="fa-solid fa-user text-gray-400 mr-2"></i> Data Pribadi</h6>
                                                <table class="w-full text-sm text-gray-600">
                                                    <tbody>
                                                        <tr><td class="py-1.5 w-1/3 font-semibold">Nama</td><td class="py-1.5 font-bold text-gray-900">: {{ $driver->nama_lengkap ?? '-' }}</td></tr>
                                                        <tr><td class="py-1.5 font-semibold">ID Akun</td><td class="py-1.5">: {{ $driver->id_pengguna ?? '-' }}</td></tr>
                                                        <tr><td class="py-1.5 font-semibold">NIK KTP</td><td class="py-1.5">: {{ $driver->nomor_nik ?? '-' }}</td></tr>
                                                        <tr><td class="py-1.5 font-semibold">No. KK</td><td class="py-1.5">: {{ $driver->nomor_kk ?? '-' }}</td></tr>
                                                        <tr>
                                                            <td class="py-1.5 font-semibold">TTL / Usia</td>
                                                            <td class="py-1.5">: {{ $driver->tempat_lahir ?? '-' }}, 
                                                                @if($driver->tanggal_lahir)
                                                                    {{ \Carbon\Carbon::parse($driver->tanggal_lahir)->format('d M Y') }} ({{ \Carbon\Carbon::parse($driver->tanggal_lahir)->age }} Thn)
                                                                @else
                                                                    -
                                                                @endif
                                                            </td>
                                                        </tr>
                                                        <tr><td class="py-1.5 font-semibold align-top">Alamat</td><td class="py-1.5 align-top">: {{ $driver->alamat_lengkap ?? '-' }}</td></tr>
                                                        <tr><td class="py-1.5 font-semibold align-top">Titik GPS</td><td class="py-1.5 align-top">: {{ $driver->latitude ?? '-' }}, {{ $driver->longitude ?? '-' }}</td></tr>
                                                    </tbody>
                                                </table>
                                            </div>

                                            {{-- Box Kanan: Kendaraan --}}
                                            <div class="border border-gray-200 p-4 rounded-lg bg-white shadow-sm">
                                                <h6 class="font-bold text-gray-800 border-b pb-2 mb-3 text-sm"><i class="fa-solid fa-motorcycle text-gray-400 mr-2"></i> Kendaraan & Layanan</h6>
                                                <table class="w-full text-sm text-gray-600">
                                                    <tbody>
                                                        <tr>
                                                            <td class="py-1.5 w-1/3 font-semibold">Layanan</td>
                                                            <td class="py-1.5 font-bold text-gray-900">: {{ $driver->jenis_layanan ? strtoupper($driver->jenis_layanan) : '-' }}</td>
                                                        </tr>
                                                        <tr><td class="py-1.5 font-semibold">Merek Kendaraan</td><td class="py-1.5">: {{ $driver->merk_kendaraan ?? '-' }}</td></tr>
                                                        <tr><td class="py-1.5 font-semibold">Tahun Dibuat</td><td class="py-1.5">: {{ $driver->tahun_kendaraan ?? '-' }}</td></tr>
                                                        <tr><td class="py-1.5 font-semibold">Plat Nomor</td><td class="py-1.5 uppercase font-bold text-gray-900">: {{ $driver->plat_nomor ?? '-' }}</td></tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>

                                        {{-- Box Bawah: Dokumen --}}
                                        <div class="border border-gray-200 p-4 rounded-lg bg-white shadow-sm mb-5">
                                            <h6 class="font-bold text-gray-800 border-b pb-2 mb-3 text-sm"><i class="fa-solid fa-folder-open text-gray-400 mr-2"></i> Kelengkapan Berkas Dokumen</h6>
                                            <div class="flex flex-wrap gap-2">
                                                @if($driver->foto_wajah) <a href="{{ asset('storage/'.$driver->foto_wajah) }}" target="_blank" class="px-3 py-1.5 bg-blue-50 hover:bg-blue-100 text-blue-700 font-bold rounded border border-blue-200 text-xs transition"><i class="fa-solid fa-image"></i> Foto Wajah</a> @endif
                                                @if($driver->file_ktp) <a href="{{ asset('storage/'.$driver->file_ktp) }}" target="_blank" class="px-3 py-1.5 bg-cyan-50 hover:bg-cyan-100 text-cyan-700 font-bold rounded border border-cyan-200 text-xs transition"><i class="fa-solid fa-id-card"></i> KTP</a> @endif
                                                @if($driver->file_sim) <a href="{{ asset('storage/'.$driver->file_sim) }}" target="_blank" class="px-3 py-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 font-bold rounded border border-indigo-200 text-xs transition"><i class="fa-solid fa-id-badge"></i> SIM</a> @endif
                                                @if($driver->file_skck) <a href="{{ asset('storage/'.$driver->file_skck) }}" target="_blank" class="px-3 py-1.5 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 font-bold rounded border border-emerald-200 text-xs transition"><i class="fa-solid fa-file-shield"></i> SKCK</a> @endif
                                                @if($driver->file_stnk) <a href="{{ asset('storage/'.$driver->file_stnk) }}" target="_blank" class="px-3 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-800 font-bold rounded border border-gray-300 text-xs transition"><i class="fa-solid fa-file-lines"></i> STNK</a> @endif
                                                @if($driver->foto_motor) <a href="{{ asset('storage/'.$driver->foto_motor) }}" target="_blank" class="px-3 py-1.5 bg-yellow-50 hover:bg-yellow-100 text-yellow-800 font-bold rounded border border-yellow-200 text-xs transition"><i class="fa-solid fa-motorcycle"></i> Kendaraan</a> @endif
                                                @if($driver->file_buku_rekening) <a href="{{ asset('storage/'.$driver->file_buku_rekening) }}" target="_blank" class="px-3 py-1.5 bg-orange-50 hover:bg-orange-100 text-orange-700 font-bold rounded border border-orange-200 text-xs transition"><i class="fa-solid fa-money-check-dollar"></i> Buku Rekening</a> @endif
                                                @if($driver->file_kk) <a href="{{ asset('storage/'.$driver->file_kk) }}" target="_blank" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded border border-slate-300 text-xs transition"><i class="fa-solid fa-users-rectangle"></i> Kartu Keluarga</a> @endif
                                                @if($driver->file_bpkb) <a href="{{ asset('storage/'.$driver->file_bpkb) }}" target="_blank" class="px-3 py-1.5 bg-zinc-800 hover:bg-zinc-900 text-white font-bold rounded border border-zinc-700 text-xs transition"><i class="fa-solid fa-book"></i> BPKB</a> @endif
                                                @if($driver->file_buku_nikah) <a href="{{ asset('storage/'.$driver->file_buku_nikah) }}" target="_blank" class="px-3 py-1.5 bg-rose-50 hover:bg-rose-100 text-rose-700 font-bold rounded border border-rose-200 text-xs transition"><i class="fa-solid fa-heart"></i> Buku Nikah</a> @endif
                                            </div>
                                            
                                            {{-- Jika tidak ada satupun dokumen --}}
                                            @if(!$driver->foto_wajah && !$driver->file_ktp && !$driver->file_sim && !$driver->file_skck && !$driver->file_stnk && !$driver->foto_motor && !$driver->file_buku_rekening && !$driver->file_kk && !$driver->file_bpkb && !$driver->file_buku_nikah)
                                                <div class="text-sm text-red-500 italic mt-2">Tidak ada berkas yang diunggah.</div>
                                            @endif
                                        </div>

                                        {{-- LOGIKA TOMBOL STATUS --}}
                                        <div class="flex flex-col sm:flex-row gap-3 pt-4 border-t border-gray-200">
                                            @if($driver->status == 'pending')
                                                <form action="{{ route('admin.drivers.status', $driver->id) }}" method="POST" class="w-full sm:w-1/2 m-0">
                                                    @csrf @method('PATCH') <input type="hidden" name="status" value="approved">
                                                    <button type="submit" class="w-full bg-green-600 hover:bg-green-700 text-white py-2.5 rounded-lg font-bold text-sm shadow-sm transition"><i class="fa-solid fa-check mr-1"></i> Setujui Pendaftaran</button>
                                                </form>
                                                <form action="{{ route('admin.drivers.status', $driver->id) }}" method="POST" class="w-full sm:w-1/2 m-0">
                                                    @csrf @method('PATCH') <input type="hidden" name="status" value="rejected">
                                                    <button type="submit" class="w-full bg-white border border-red-500 text-red-600 hover:bg-red-50 py-2.5 rounded-lg font-bold text-sm shadow-sm transition"><i class="fa-solid fa-xmark mr-1"></i> Tolak Data</button>
                                                </form>
                                            @elseif($driver->status == 'approved')
                                                <button type="button" onclick="closeModal('modalDetail_{{ $driver->id }}'); openModal('modalEdit_{{ $driver->id }}')" class="w-full sm:w-1/2 bg-amber-500 hover:bg-amber-600 text-white py-2.5 rounded-lg font-bold text-sm shadow-sm transition"><i class="fa-solid fa-pen-to-square mr-1"></i> Edit Data Driver</button>
                                                <form action="{{ route('admin.drivers.status', $driver->id) }}" method="POST" class="w-full sm:w-1/2 m-0">
                                                    @csrf @method('PATCH') <input type="hidden" name="status" value="rejected">
                                                    <button type="submit" onclick="return confirm('Yakin membatalkan persetujuan driver ini?')" class="w-full bg-white border border-red-500 text-red-600 hover:bg-red-50 py-2.5 rounded-lg font-bold text-sm shadow-sm transition"><i class="fa-solid fa-ban mr-1"></i> Batalkan Status (Tolak)</button>
                                                </form>
                                            @else
                                                <button type="button" onclick="closeModal('modalDetail_{{ $driver->id }}'); openModal('modalEdit_{{ $driver->id }}')" class="w-full sm:w-1/2 bg-amber-500 hover:bg-amber-600 text-white py-2.5 rounded-lg font-bold text-sm shadow-sm transition"><i class="fa-solid fa-pen-to-square mr-1"></i> Edit Data Driver</button>
                                                <form action="{{ route('admin.drivers.status', $driver->id) }}" method="POST" class="w-full sm:w-1/2 m-0">
                                                    @csrf @method('PATCH') <input type="hidden" name="status" value="approved">
                                                    <button type="submit" class="w-full bg-green-600 hover:bg-green-700 text-white py-2.5 rounded-lg font-bold text-sm shadow-sm transition"><i class="fa-solid fa-rotate-left mr-1"></i> Pulihkan & Setujui</button>
                                                </form>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- ======================================================== --}}
                    {{-- MODAL EDIT DRIVER (Desain Form Berlabel, Rapi & Lega)    --}}
                    {{-- ======================================================== --}}
                    <div id="modalEdit_{{ $driver->id }}" class="hidden fixed inset-0 z-[99999]">
                        <div class="fixed inset-0 bg-gray-900 bg-opacity-75 transition-opacity" onclick="closeModal('modalEdit_{{ $driver->id }}')"></div>
                        <div class="fixed inset-0 overflow-y-auto pt-6 pb-6">
                            <div class="flex min-h-full items-center justify-center p-4">
                                <div class="bg-white rounded-xl shadow-2xl w-full max-w-5xl border border-gray-200 overflow-hidden">
                                    
                                    {{-- Header Edit Modal --}}
                                    <div class="border-b px-6 py-4 bg-slate-50 flex justify-between items-center">
                                        <h5 class="text-lg font-bold text-slate-800"><i class="fa-solid fa-pen-to-square text-amber-500 mr-2"></i> Edit Data Driver: {{ $driver->nama_lengkap ?? '-' }}</h5>
                                        <button type="button" onclick="closeModal('modalEdit_{{ $driver->id }}')" class="text-gray-400 hover:text-red-600 transition bg-white rounded-full p-1"><i class="fas fa-times fa-lg"></i></button>
                                    </div>
                                    
                                    <form action="{{ route('admin.drivers.update', $driver->id) }}" method="POST" enctype="multipart/form-data" class="m-0">
                                        @csrf @method('PUT')
                                        
                                        {{-- Body Edit Modal (Scrollable) --}}
                                        <div class="px-6 py-6 max-h-[65vh] overflow-y-auto">
                                            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
                                                
                                                {{-- KIRI: DATA TEKS (Lebar: 7 Kolom) --}}
                                                <div class="lg:col-span-7 space-y-5">
                                                    <h6 class="font-bold text-gray-800 border-b border-gray-200 pb-2 text-base">Identitas Pribadi & Kendaraan</h6>
                                                    
                                                    {{-- Input Nama --}}
                                                    <div>
                                                        <label class="block text-xs font-bold text-gray-700 mb-1.5 uppercase tracking-wide">Nama Lengkap</label>
                                                        <input type="text" name="nama_lengkap" class="w-full border border-gray-300 p-2.5 rounded-lg text-sm bg-gray-50 focus:bg-white focus:ring-2 focus:ring-blue-500 outline-none" value="{{ $driver->nama_lengkap }}" required>
                                                    </div>
                                                    
                                                    {{-- Input TTL --}}
                                                    <div class="flex flex-col sm:flex-row gap-4">
                                                        <div class="w-full sm:w-1/2">
                                                            <label class="block text-xs font-bold text-gray-700 mb-1.5 uppercase tracking-wide">Tempat Lahir</label>
                                                            <input type="text" name="tempat_lahir" class="w-full border border-gray-300 p-2.5 rounded-lg text-sm bg-gray-50 focus:bg-white focus:ring-2 focus:ring-blue-500 outline-none" value="{{ $driver->tempat_lahir }}">
                                                        </div>
                                                        <div class="w-full sm:w-1/2">
                                                            <label class="block text-xs font-bold text-gray-700 mb-1.5 uppercase tracking-wide">Tanggal Lahir</label>
                                                            <input type="date" name="tanggal_lahir" class="w-full border border-gray-300 p-2.5 rounded-lg text-sm bg-gray-50 focus:bg-white focus:ring-2 focus:ring-blue-500 outline-none" value="{{ $driver->tanggal_lahir ? \Carbon\Carbon::parse($driver->tanggal_lahir)->format('Y-m-d') : '' }}">
                                                        </div>
                                                    </div>
                                                    
                                                    {{-- Input NIK & WA --}}
                                                    <div class="flex flex-col sm:flex-row gap-4">
                                                        <div class="w-full sm:w-1/2">
                                                            <label class="block text-xs font-bold text-gray-700 mb-1.5 uppercase tracking-wide">Nomor NIK KTP</label>
                                                            <input type="number" name="nomor_nik" class="w-full border border-gray-300 p-2.5 rounded-lg text-sm bg-gray-50 focus:bg-white focus:ring-2 focus:ring-blue-500 outline-none" value="{{ $driver->nomor_nik }}" required>
                                                        </div>
                                                        <div class="w-full sm:w-1/2">
                                                            <label class="block text-xs font-bold text-gray-700 mb-1.5 uppercase tracking-wide">Nomor WhatsApp</label>
                                                            <input type="text" name="nomor_wa" class="w-full border border-gray-300 p-2.5 rounded-lg text-sm bg-gray-50 focus:bg-white focus:ring-2 focus:ring-blue-500 outline-none" value="{{ $driver->nomor_wa }}" required>
                                                        </div>
                                                    </div>
                                                    
                                                    {{-- Input Alamat --}}
                                                    <div>
                                                        <label class="block text-xs font-bold text-gray-700 mb-1.5 uppercase tracking-wide">Alamat Domisili</label>
                                                        <textarea name="alamat_lengkap" class="w-full border border-gray-300 p-2.5 rounded-lg text-sm bg-gray-50 focus:bg-white focus:ring-2 focus:ring-blue-500 outline-none" rows="3" required>{{ $driver->alamat_lengkap }}</textarea>
                                                    </div>

                                                    {{-- Input Kendaraan --}}
                                                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                                        <div>
                                                            <label class="block text-xs font-bold text-gray-700 mb-1.5 uppercase tracking-wide">Jenis Layanan</label>
                                                            <select name="jenis_layanan" class="w-full border border-gray-300 p-2.5 rounded-lg text-sm bg-gray-50 focus:bg-white focus:ring-2 focus:ring-blue-500 outline-none" required>
                                                                <option value="" {{ !$driver->jenis_layanan ? 'selected' : '' }} disabled>Pilih</option>
                                                                <option value="motor" {{ $driver->jenis_layanan=='motor'?'selected':'' }}>RIDE (Motor)</option>
                                                                <option value="mobil" {{ $driver->jenis_layanan=='mobil'?'selected':'' }}>CAR (Mobil)</option>
                                                            </select>
                                                        </div>
                                                        <div>
                                                            <label class="block text-xs font-bold text-gray-700 mb-1.5 uppercase tracking-wide">Merek Kendaraan</label>
                                                            <input type="text" name="merk_kendaraan" class="w-full border border-gray-300 p-2.5 rounded-lg text-sm bg-gray-50 focus:bg-white focus:ring-2 focus:ring-blue-500 outline-none" value="{{ $driver->merk_kendaraan }}">
                                                        </div>
                                                        <div>
                                                            <label class="block text-xs font-bold text-gray-700 mb-1.5 uppercase tracking-wide">Tahun Pembuatan</label>
                                                            <input type="number" name="tahun_kendaraan" class="w-full border border-gray-300 p-2.5 rounded-lg text-sm bg-gray-50 focus:bg-white focus:ring-2 focus:ring-blue-500 outline-none" value="{{ $driver->tahun_kendaraan }}">
                                                        </div>
                                                    </div>
                                                    
                                                    <div>
                                                        <label class="block text-xs font-bold text-gray-700 mb-1.5 uppercase tracking-wide">Plat Nomor Kendaraan</label>
                                                        <input type="text" name="plat_nomor" class="w-full border border-gray-300 p-2.5 rounded-lg text-sm bg-gray-50 focus:bg-white focus:ring-2 focus:ring-blue-500 outline-none uppercase" value="{{ $driver->plat_nomor }}">
                                                    </div>
                                                </div>

                                                {{-- KANAN: UPLOAD FILE (Lebar: 5 Kolom) --}}
                                                <div class="lg:col-span-5 space-y-4">
                                                    <h6 class="font-bold text-gray-800 border-b border-gray-200 pb-2 text-base">Update Dokumen Berkas</h6>
                                                    <div class="bg-blue-50 border border-blue-100 text-blue-700 p-3 rounded-lg text-xs mb-3 shadow-sm">
                                                        <i class="fa-solid fa-circle-info mr-1"></i> <b>Info:</b> Kosongkan kotak file di bawah ini jika dokumen lama tidak ingin diganti.
                                                    </div>
                                                    
                                                    @php
                                                    $dokumenList = [
                                                        'foto_wajah'=>'Foto Wajah Pribadi', 'file_ktp'=>'Kartu Tanda Penduduk (KTP)', 'file_sim'=>'Surat Izin Mengemudi (SIM)', 
                                                        'file_skck'=>'SKCK Aktif', 'file_buku_rekening'=>'Buku Rekening Bank', 'file_stnk'=>'STNK Pajak Hidup', 
                                                        'foto_motor'=>'Foto Kendaraan Samping', 'file_kk'=>'Kartu Keluarga (KK)'
                                                    ];
                                                    @endphp
                                                    
                                                    @foreach($dokumenList as $field => $label)
                                                    <div class="mb-3">
                                                        <label class="block text-xs font-bold text-gray-700 mb-1.5">{{ $label }}</label>
                                                        <div class="flex items-center gap-3">
                                                            <input type="file" name="{{ $field }}" class="w-full border border-dashed border-gray-300 p-1.5 rounded text-xs bg-gray-50 focus:bg-white cursor-pointer hover:border-blue-400 transition" accept=".jpg,.png,.jpeg,.pdf">
                                                        </div>
                                                    </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        </div>
                                        
                                        {{-- Footer Edit Modal --}}
                                        <div class="px-6 py-4 bg-gray-50 border-t flex justify-end gap-3 rounded-b-xl">
                                            <button type="button" onclick="closeModal('modalEdit_{{ $driver->id }}')" class="px-5 py-2.5 bg-white border border-gray-300 rounded-lg text-sm font-bold text-gray-700 hover:bg-gray-100 transition shadow-sm">Batal</button>
                                            <button type="submit" class="px-5 py-2.5 bg-blue-600 text-white rounded-lg text-sm font-bold shadow-sm hover:bg-blue-700 transition"><i class="fa-solid fa-save mr-1.5"></i> Simpan Perubahan</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    @empty
                    <tr><td colspan="6" class="text-center py-12 text-gray-500 bg-gray-50/50"><i class="fa-solid fa-folder-open text-4xl mb-3 text-gray-300"></i><br><span class="font-semibold text-sm">Belum ada data pendaftaran driver.</span></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        @if ($drivers->hasPages())
        <div class="mt-4 pt-2">{{ $drivers->links('vendor.pagination.tailwind') }}</div>
        @endif
    </div>
    
    {{-- ======================================================== --}}
    {{-- MODAL HAPUS MASSAL                                       --}}
    {{-- ======================================================== --}}
    <div id="bulkDeleteModal" class="hidden fixed inset-0 z-[99999]">
        <div class="fixed inset-0 bg-gray-900 bg-opacity-75 transition-opacity" onclick="closeModal('bulkDeleteModal')"></div>
        <div class="fixed inset-0 flex items-center justify-center p-4">
            <div class="bg-white rounded-xl p-6 max-w-sm w-full shadow-2xl transform transition-all border border-gray-200">
                <div class="text-center">
                    <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-red-100 mb-4">
                        <i class="fa-solid fa-trash-can text-red-600 text-2xl"></i>
                    </div>
                    <h3 class="font-extrabold text-xl mb-2 text-gray-800">Konfirmasi Hapus</h3>
                    <p class="text-sm text-gray-600 mb-6">Anda yakin ingin menghapus <strong id="modalSelectedCount" class="text-red-600 text-base">0</strong> data driver terpilih secara permanen?</p>
                </div>
                <div class="flex justify-center gap-3">
                    <button type="button" onclick="closeModal('bulkDeleteModal')" class="w-1/2 px-4 py-2.5 bg-white border border-gray-300 rounded-lg font-bold text-sm text-gray-700 hover:bg-gray-50 transition shadow-sm">Batal</button>
                    <button type="button" onclick="document.getElementById('bulkDeleteForm').submit()" class="w-1/2 px-4 py-2.5 bg-red-600 hover:bg-red-700 text-white rounded-lg font-bold text-sm transition shadow-sm">Ya, Hapus</button>
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