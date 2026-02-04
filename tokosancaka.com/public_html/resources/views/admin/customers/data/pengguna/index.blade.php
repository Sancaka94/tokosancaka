@extends('layouts.admin')

@section('title', 'Data Pengguna & Pelanggan')

{{-- Bagian head yang digunakan ApexCharts dihapus --}}
@push('head')
{{-- <script src="https://www.google.com/search?q=https://cdn.jsdelivr.net/npm/apexcharts"></script> --}}
@endpush

@section('content')

<!-- Header Konten -->

<div class="bg-white shadow-sm border-b border-gray-200 px-4 py-4 sm:px-6 lg:px-8">
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
<div class="flex-1 min-w-0">
<h1 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
Data Pengguna & Pelanggan
</h1>
</div>
<div class="mt-4 flex sm:mt-0 sm:ml-4">
<nav class="flex" aria-label="Breadcrumb">
<ol role="list" class="flex items-center space-x-2 text-sm text-gray-500">
<li><a href="{{ route('admin.dashboard') }}" class="hover:text-gray-700">Dashboard</a></li>
<li class="flex items-center">
<svg class="h-5 w-5 flex-shrink-0 text-gray-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M5.555 17.776l8-15.552m-8 15.552h11.11M5.555 17.776H2.222" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
<span class="ml-2">Data Pengguna</span>
</li>
</ol>
</nav>
</div>
</div>
</div>
<!-- --- -->

<section class="py-6 px-4 sm:px-6 lg:px-8">
<div class="max-w-full mx-auto space-y-6">

    {{-- 🔎 Filter, Pencarian, & Ekspor Data --}}
    <div class="bg-white shadow sm:rounded-lg p-6 flex flex-col md:flex-row md:items-center justify-between space-y-4 md:space-y-0">
        
        {{-- Area Pencarian dan Filter --}}
        <form action="{{ route('admin.customers.data.pengguna.index') }}" method="GET" class="flex flex-wrap items-center space-x-2">
            <input type="text" name="search" placeholder="Cari ID, Nama, No. HP..." value="{{ request('search') }}"
                   class="mt-1 block w-full md:w-auto rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
            
            <input type="text" name="city" placeholder="Cari Kota/Kabupaten..." value="{{ request('city') }}"
                   class="mt-1 block w-full md:w-auto rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">

            <button type="submit" class="mt-1 inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                <svg class="-ml-1 mr-2 h-4 w-4" fill="currentColor" viewBox="0 0 20 20"><path d="M9 3.5a5.5 5.5 0 100 11 5.5 5.5 0 000-11zM2 9a7 7 0 1112.448 4.095l3.708 3.708a1 1 0 01-1.414 1.414l-3.708-3.708A7 7 0 012 9z" clip-rule="evenodd" fill-rule="evenodd"></path></svg>
                Cari
            </button>
        </form>

        {{-- Area Ekspor --}}
        <div class="flex space-x-3">
            <a href="{{ route('admin.customers.pengguna.export', ['type' => 'excel', 'search' => request('search'), 'city' => request('city')]) }}" 
               class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md shadow-sm text-gray-700 bg-white hover:bg-gray-50">
                Ekspor Excel
            </a>
            <a href="{{ route('admin.customers.pengguna.export', ['type' => 'pdf', 'search' => request('search'), 'city' => request('city')]) }}" 
               class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md shadow-sm text-gray-700 bg-white hover:bg-gray-50">
                Ekspor PDF
            </a>
        </div>
    </div>
    
    {{-- Tabel Data --}}
    <div class="bg-white shadow overflow-hidden sm:rounded-lg">
        
        <div class="px-4 py-5 sm:px-6 border-b border-gray-200 flex justify-between items-center">
            <h3 class="text-lg leading-6 font-medium text-gray-900">
                Daftar Pelanggan Terdaftar 
                <span class="text-sm font-normal text-gray-500">(Total: {{ $pengguna->total() }})</span>
            </h3>
        </div>

        <div class="flex flex-col">
            <div class="overflow-x-auto relative shadow-md sm:rounded-lg">
                <div class="align-middle inline-block min-w-full">
                    
                    <table class="min-w-full divide-y divide-gray-200">
                        {{-- Header Tabel --}}
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">No. WA</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama Toko</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Logo</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Alamat Lengkap</th>
                                
                                {{-- Kolom Bank --}}
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama Bank</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama Akun</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nomor Rekening</th>
                                
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Saldo</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Terdaftar</th>
                                <th scope="col" class="sticky right-0 z-10 px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider bg-gray-50 border-l border-gray-200 min-w-[150px]">Aksi</th>
                            </tr>
                        </thead>
                        
                        {{-- Body Tabel --}}
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse ($pengguna as $data)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $data->id_pengguna }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $data->nama_lengkap }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $data->email }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $data->no_wa }}</td>
                                <td class="px-6 py-4 text-sm text-gray-900 min-w-[150px]">{{ $data->store_name ?? '—' }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-center">
                                    @if ($data->store_logo_path)
                                    <img src="{{ $data->store_logo_path ? asset('public/storage/' . $data->store_logo_path) : 'https://ui-avatars.com/api/?name=' . urlencode($data->nama_lengkap ?? 'User') }}" 
                                        alt="Logo" 
                                        class="h-8 w-8 object-cover rounded-full mx-auto" 
                                        loading="lazy">
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500 min-w-[300px]">
                                    @if ($data->address_detail)
                                        {{ $data->address_detail }}, {{ $data->village ?? '' }}, {{ $data->district ?? '' }}, {{ $data->regency ?? '' }}, {{ $data->province ?? '' }} {{ $data->postal_code }}
                                    @else
                                        —
                                    @endif
                                </td>
                                
                                {{-- Data Bank --}}
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $data->bank_name ?? '—' }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $data->bank_account_name ?? '—' }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $data->bank_account_number ?? '—' }}</td>
                                
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                        @if ($data->role == 'Admin') bg-red-100 text-red-800
                                        @elseif ($data->role == 'Seller') bg-blue-100 text-blue-800
                                        @else bg-green-100 text-green-800 @endif">
                                        {{ $data->role }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 text-right">
                                    Rp{{ number_format($data->saldo, 0, ',', '.') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                        @if ($data->status == 'Aktif') bg-indigo-100 text-indigo-800
                                        @else bg-yellow-100 text-yellow-800 @endif">
                                        {{ $data->status }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ \Carbon\Carbon::parse($data->created_at)->format('d M Y') }}
                                </td>
                                
                                {{-- Kolom Aksi - Sticky Content --}}
                                <td class="sticky right-0 z-10 px-6 py-4 whitespace-nowrap text-sm font-medium text-center bg-white border-l border-gray-200 min-w-[150px]">
                                    <div class="flex items-center justify-center space-x-2">
                                        {{-- Lihat Detail --}}
                                        <a href="{{ route('admin.customers.data.pengguna.show', $data->id_pengguna) }}" class="inline-flex items-center p-2 border border-transparent rounded-full shadow-sm text-white bg-indigo-600 hover:bg-indigo-700" title="Lihat Detail">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path d="M10 12a2 2 0 100-4 2 2 0 000 4z"></path><path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd"></path></svg>
                                        </a>
                                        {{-- Edit Data --}}
                                        <a href="{{ route('admin.customers.data.pengguna.edit', $data->id_pengguna) }}" class="inline-flex items-center p-2 border border-transparent rounded-full shadow-sm text-white bg-blue-600 hover:bg-blue-700" title="Edit Data">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20"><path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zm-5.464 5.464a.5.5 0 000 .707l3.75 3.75a.5.5 0 00.707 0l2.828-2.828-3.535-3.535-3.75-3.75z" clip-rule="evenodd" fill-rule="evenodd"></path></svg>
                                        </a>
                                        {{-- Hapus Akun Pengguna --}}
                                        <form action="{{ route('admin.customers.data.pengguna.destroy', $data->id_pengguna) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus akun pengguna ini?');" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="inline-flex items-center p-2 border border-transparent rounded-full shadow-sm text-white bg-red-600 hover:bg-red-700" title="Hapus Akun">
                                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="15" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center italic">Tidak ada data pengguna yang ditemukan.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                    
                </div>
            </div>
        </div>
        
        <!-- Footer Kartu/Paginasi -->
        <div class="bg-gray-50 px-4 py-3 border-t border-gray-200 sm:px-6">
            <div class="flex justify-end">
                {{ $pengguna->links() }}
            </div>
        </div>
        
    </div>
</div>


</section>

@endsection