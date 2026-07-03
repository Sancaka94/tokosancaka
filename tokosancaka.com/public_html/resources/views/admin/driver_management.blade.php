{{--
    File: resources/views/admin/driver_management.blade.php
    Deskripsi: Halaman Admin untuk manajemen Pendaftaran Driver (Match dengan UI Pesanan).
--}}

@extends('layouts.admin')

@section('title', 'Manajemen Pendaftaran Driver')
@section('page-title', 'Manajemen Pendaftaran Driver')

{{-- =========================================================== --}}
{{-- 1. CSS & STYLE (Sama persis dengan Pesanan)                 --}}
{{-- =========================================================== --}}
@push('styles')
    {{-- Flatpickr CSS --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" type="text/css" href="https://npmcdn.com/flatpickr/dist/themes/airbnb.css">

    <style>
        /* === DESKTOP VIEW === */
        @media (min-width: 768px) {
            .table-container {
                overflow-x: auto;
            }
            th.sticky-col, td.sticky-col {
                position: -webkit-sticky;
                position: sticky;
                right: 0;
                background-color: white;
                z-index: 10;
                border-left: 1px solid #e5e7eb;
                box-shadow: -4px 0 6px -1px rgba(0, 0, 0, 0.05);
            }
            thead th.sticky-col {
                background-color: #f3f4f6; /* Gray-100 untuk Driver */
                z-index: 20;
            }
            tr:hover td.sticky-col {
                background-color: #f9fafb;
            }
        }

        /* === MOBILE VIEW (KARTU & READ MORE) === */
        @media (max-width: 767px) {
            table, thead, tbody, th, td, tr {
                display: block;
            }
            thead tr {
                position: absolute;
                top: -9999px;
                left: -9999px;
            }
            tr {
                margin-bottom: 1rem;
                border: 1px solid #e5e7eb;
                border-radius: 0.75rem;
                background-color: white;
                box-shadow: 0 2px 4px rgba(0,0,0,0.05);
                overflow: hidden;
            }
            td {
                border: none;
                border-bottom: 1px solid #f3f4f6;
                position: relative;
                padding: 0.75rem 1rem !important;
            }
            td:last-child {
                border-bottom: none;
            }
            .mobile-details {
                transition: all 0.3s ease-in-out;
            }
        }

        /* Animasi untuk tombol Bulk Delete */
        .bulk-action-bar {
            transition: all 0.3s ease-in-out;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
        }
        .bulk-action-bar.active {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .flatpickr-calendar { z-index: 9999 !important; }
        .flatpickr-input { background-color: white !important; cursor: pointer !important; }
        .fade-in { animation: fadeIn 0.5s ease-in-out; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
    </style>
@endpush

@section('content')
<div class="container mx-auto px-4 py-6">

    {{-- =========================================================== --}}
    {{-- 2. NOTIFIKASI & ALERT                                       --}}
    {{-- =========================================================== --}}
    @if(session('success'))
        <div x-data="{ show: true }" x-show="show" class="mb-6 flex items-center justify-between p-4 text-sm text-green-800 bg-green-50 border-l-4 border-green-500 rounded shadow-sm fade-in">
            <div class="flex items-center gap-2">
                <i class="fa-solid fa-circle-check text-lg"></i>
                <span class="font-bold">{{ session('success') }}</span>
            </div>
            <button @click="show = false" type="button" class="text-green-600 hover:text-green-900 transition">
                <i class="fa-solid fa-times text-lg"></i>
            </button>
        </div>
    @endif

    @if(session('error'))
        <div x-data="{ show: true }" x-show="show" class="mb-6 flex items-center justify-between p-4 text-sm text-red-800 bg-red-50 border-l-4 border-red-500 rounded shadow-sm fade-in">
            <div class="flex items-center gap-2">
                <i class="fa-solid fa-triangle-exclamation text-lg"></i>
                <span class="font-bold">{{ session('error') }}</span>
            </div>
            <button @click="show = false" type="button" class="text-red-600 hover:text-red-900 transition">
                <i class="fa-solid fa-times text-lg"></i>
            </button>
        </div>
    @endif

    {{-- =========================================================== --}}
    {{-- 3. KONTEN UTAMA                                             --}}
    {{-- =========================================================== --}}
    <div class="bg-white p-4 sm:p-6 rounded-lg shadow-md">

        {{-- HEADER & SEARCH & FILTER DATE --}}
        <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center mb-6 gap-4">
            {{-- Form Pencarian Kiri --}}
            <div class="w-full lg:w-3/4">
                <form action="{{ route('admin.drivers.index') }}" method="GET" class="flex flex-col md:flex-row gap-3">
                    @if(request('status'))
                        <input type="hidden" name="status" value="{{ request('status') }}">
                    @endif

                    {{-- Search --}}
                    <div class="relative w-full md:w-1/3">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                            <i class="fas fa-search"></i>
                        </div>
                        <input type="text" name="search" value="{{ request('search') }}"
                            class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm transition shadow-sm"
                            placeholder="Cari Nama / No. WA...">
                    </div>

                    {{-- Date Picker --}}
                    <div class="relative w-full md:w-1/3 group">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                            <i class="far fa-calendar-alt"></i>
                        </div>
                        <input type="text" id="date_range_picker" name="date_range" value="{{ request('date_range') }}"
                            class="w-full pl-10 pr-10 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm bg-white transition shadow-sm"
                            placeholder="Filter Tanggal..." readonly>
                        <button type="button" id="clearDateBtn" class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-red-500 hidden cursor-pointer" style="z-index: 10;">
                            <i class="fas fa-times-circle"></i>
                        </button>
                    </div>

                    <button type="submit" class="bg-blue-600 text-white px-5 py-2.5 rounded-lg text-sm font-medium hover:bg-blue-700 shadow-sm transition flex items-center justify-center gap-2">
                        <i class="fas fa-filter"></i> Filter
                    </button>
                </form>
            </div>
            
            {{-- Tombol Kanan --}}
            <div class="flex items-center gap-2 w-full lg:w-auto justify-end">
                <a href="#" class="bg-gray-800 text-white px-4 py-2.5 rounded-lg text-sm font-medium hover:bg-gray-900 shadow-sm transition">
                    <i class="fas fa-plus me-2"></i>Tambah Driver
                </a>
            </div>
        </div>

        {{-- TAB STATUS (Sama seperti Pesanan) --}}
        <div class="flex flex-wrap gap-2 mb-4 border-b pb-4">
            @php
                $routeIndex = 'admin.drivers.index';
                $statuses = [
                    'Pending'  => 'pending',
                    'Approved' => 'approved',
                    'Rejected' => 'rejected'
                ];
                $currentStatus = request('status');
                $baseQuery = request()->except(['status', 'page']);
            @endphp

            <a href="{{ route($routeIndex, $baseQuery) }}"
               class="px-4 py-2 text-xs font-bold rounded-full border transition
                      {{ !$currentStatus ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-600 border-gray-300 hover:bg-gray-50' }}">
                Semua
            </a>

            @foreach($statuses as $label => $value)
                <a href="{{ route($routeIndex, array_merge($baseQuery, ['status' => $value, 'page' => 1])) }}"
                   class="px-4 py-2 text-xs font-bold rounded-full border transition
                          {{ $currentStatus == $value ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-600 border-gray-300 hover:bg-gray-50' }}">
                    {{ $label }}
                </a>
            @endforeach
        </div>

        {{-- === CARD MONITOR (STATISTIK DRIVER) === --}}
        {{-- Opsional: Lempar variabel ini dari Controller (misal: $totalDrivers, $pendingDrivers) --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <div class="relative overflow-hidden rounded-lg bg-gray-700 p-5 shadow-lg">
                <div class="relative z-10 text-white">
                    <p class="text-3xl font-bold">{{ $totalDrivers ?? 0 }}</p>
                    <p class="text-sm font-bold uppercase opacity-90 mt-1">Total Pendaftar</p>
                </div>
                <div class="absolute right-0 top-0 -mt-2 -mr-4 h-24 w-24 opacity-20 transform rotate-12"><i class="fas fa-users fa-5x text-white"></i></div>
            </div>
            <div class="relative overflow-hidden rounded-lg bg-yellow-500 p-5 shadow-lg">
                <div class="relative z-10 text-white">
                    <p class="text-3xl font-bold">{{ $pendingDrivers ?? 0 }}</p>
                    <p class="text-sm font-bold uppercase opacity-90 mt-1">Pending</p>
                </div>
                <div class="absolute right-0 top-0 -mt-2 -mr-4 h-24 w-24 opacity-20 transform rotate-12"><i class="fas fa-clock fa-5x text-white"></i></div>
            </div>
            <div class="relative overflow-hidden rounded-lg bg-green-500 p-5 shadow-lg">
                <div class="relative z-10 text-white">
                    <p class="text-3xl font-bold">{{ $approvedDrivers ?? 0 }}</p>
                    <p class="text-sm font-bold uppercase opacity-90 mt-1">Disetujui</p>
                </div>
                <div class="absolute right-0 top-0 -mt-2 -mr-4 h-24 w-24 opacity-20 transform rotate-12"><i class="fas fa-check-circle fa-5x text-white"></i></div>
            </div>
            <div class="relative overflow-hidden rounded-lg bg-red-500 p-5 shadow-lg">
                <div class="relative z-10 text-white">
                    <p class="text-3xl font-bold">{{ $rejectedDrivers ?? 0 }}</p>
                    <p class="text-sm font-bold uppercase opacity-90 mt-1">Ditolak</p>
                </div>
                <div class="absolute right-0 top-0 -mt-2 -mr-4 h-24 w-24 opacity-20 transform rotate-12"><i class="fas fa-times-circle fa-5x text-white"></i></div>
            </div>
        </div>

        {{-- === TOMBOL AKSI MASSAL (HAPUS) === --}}
        <div id="bulkActionBar" class="bulk-action-bar bg-red-50 border border-red-200 rounded-lg p-3 mb-4 flex flex-col sm:flex-row items-center justify-between gap-3">
            <div class="text-sm text-red-800 font-semibold flex items-center gap-2">
                <i class="fas fa-check-square text-red-500 text-lg"></i>
                <span id="selectedCount">0</span> Driver Terpilih
            </div>
            <div class="flex gap-2 w-full sm:w-auto">
                <button type="button" id="btnSelectAll" onclick="toggleSelectAll()" class="flex-1 sm:flex-none bg-white border border-gray-300 text-gray-700 px-4 py-2 rounded-lg text-sm font-bold hover:bg-gray-100 transition">
                    Pilih Semua
                </button>
                <button type="button" onclick="showBulkDeleteModal()" class="flex-1 sm:flex-none bg-red-600 text-white px-4 py-2 rounded-lg text-sm font-bold hover:bg-red-700 shadow-sm transition flex items-center justify-center gap-2">
                    <i class="fas fa-trash-alt"></i> Hapus Terpilih
                </button>
            </div>
        </div>

        {{-- FORM HAPUS MASSAL --}}
        <form id="bulkDeleteForm" action="{{ route('admin.drivers.bulk_destroy') }}" method="POST" class="hidden">
            @csrf
            @method('DELETE')
        </form>

        {{-- TABEL DATA DRIVER --}}
        <div class="table-container">
            <table class="w-full divide-y divide-gray-200">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-600 w-10">
                            <input type="checkbox" id="checkAllHeader" onclick="toggleSelectAllHeader(this)" class="w-4 h-4 text-blue-600 bg-white border-gray-300 rounded focus:ring-blue-500 cursor-pointer">
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">No</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Profil Driver</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Kontak & Alamat</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Status</th>
                        <th class="px-4 py-3 text-center text-xs font-bold text-gray-600 uppercase tracking-wider sticky-col">Aksi</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse ($drivers as $index => $driver)
                    <tr class="group hover:bg-gray-50 row-order transition-colors duration-200">

                        {{-- TD Checkbox --}}
                        <td class="px-4 py-4 align-top border-b-0 pb-0 md:pb-4 md:border-b bg-gray-50 md:bg-transparent">
                            <div class="flex items-center md:block">
                                <span class="md:hidden font-bold text-gray-400 text-xs mr-2">PILIH:</span>
                                <input type="checkbox" name="selected_ids[]" value="{{ $driver->id }}" data-name="{{ $driver->nama_lengkap }}" onchange="updateBulkActionUI()" class="row-checkbox w-4 h-4 text-blue-600 bg-white border-gray-300 rounded focus:ring-blue-500 cursor-pointer shadow-sm">
                            </div>
                        </td>

                        {{-- 1. NO --}}
                        <td class="px-4 py-4 align-top text-sm text-gray-500 md:w-12 border-b-0 pb-0 md:pb-4 md:border-b">
                            <div class="flex items-center justify-between md:block">
                                <div>
                                    <span class="md:hidden font-bold text-gray-400 text-xs mr-2">NO:</span>
                                    {{ $drivers->firstItem() + $index }}
                                </div>
                            </div>
                        </td>

                        {{-- 2. PROFIL DRIVER --}}
                        <td class="px-4 py-4 align-top text-sm relative">
                            <span class="md:hidden block font-bold text-gray-400 text-xs mb-1">PROFIL:</span>
                            <div class="font-bold text-gray-800 text-base mb-1">{{ $driver->nama_lengkap }}</div>
                            <div class="text-xs text-gray-500"><i class="fa-regular fa-id-card"></i> NIK: <strong>{{ $driver->nomor_nik ?? '-' }}</strong></div>
                            <div class="text-xs text-gray-500 mt-1"><i class="fa-regular fa-calendar"></i> Daftar: {{ \Carbon\Carbon::parse($driver->created_at)->format('d M Y, H:i') }}</div>

                            {{-- TOMBOL TRIGGER READ MORE (MOBILE) --}}
                            <div class="md:hidden mt-3">
                                <button type="button" onclick="toggleDetails({{$index}}, this)" class="w-full bg-gray-100 text-gray-600 py-2 rounded text-sm font-semibold hover:bg-gray-200 flex items-center justify-center gap-2 transition-colors duration-200">
                                    <span>Lihat Detail Lengkap</span>
                                    <i class="fas fa-chevron-down"></i>
                                </button>
                            </div>
                        </td>

                        {{-- 3. KONTAK & ALAMAT (Sembunyi di Mobile Awalnya) --}}
                        <td class="hidden md:table-cell px-4 py-4 align-top text-sm bg-gray-50 md:bg-white toggle-target-{{$index}}">
                            <span class="md:hidden block font-bold text-gray-500 text-xs mb-1 uppercase tracking-wider border-b pb-1 mt-2">📍 Kontak & Alamat</span>
                            
                            <div class="font-semibold text-blue-600 mb-1">
                                <i class="fa-brands fa-whatsapp text-green-500"></i> {{ $driver->nomor_wa }}
                            </div>
                            <div class="text-xs text-gray-600 leading-relaxed mt-2 whitespace-normal line-clamp-2" title="{{ $driver->alamat_lengkap }}">
                                {{ $driver->alamat_lengkap }}
                            </div>
                        </td>

                        {{-- 4. STATUS --}}
                        <td class="hidden md:table-cell px-4 py-4 align-top text-sm bg-gray-50 md:bg-white toggle-target-{{$index}}">
                            <span class="md:hidden block font-bold text-gray-500 text-xs mb-1 uppercase tracking-wider border-b pb-1">🔖 Status</span>
                            
                            @if($driver->status == 'pending')
                                <span class="bg-yellow-50 border border-yellow-200 text-yellow-700 text-xs font-bold uppercase tracking-wider px-2.5 py-1 rounded-md">Pending</span>
                            @elseif($driver->status == 'approved')
                                <span class="bg-green-50 border border-green-200 text-green-700 text-xs font-bold uppercase tracking-wider px-2.5 py-1 rounded-md">Approved</span>
                            @else
                                <span class="bg-red-50 border border-red-200 text-red-700 text-xs font-bold uppercase tracking-wider px-2.5 py-1 rounded-md">Rejected</span>
                            @endif
                        </td>

                        {{-- 5. AKSI (Sama visual icon pesanan) --}}
                        <td class="hidden md:table-cell px-4 py-4 align-middle whitespace-nowrap text-sm font-medium sticky-col bg-gray-50 md:bg-white border-t md:border-none toggle-target-{{$index}}">
                            <span class="md:hidden block font-bold text-gray-500 text-xs mb-2 text-center uppercase border-b pb-2">⚙️ Aksi</span>
                            <div class="flex items-center justify-center space-x-3 w-full py-2 md:py-0">
                                
                                {{-- Detail (Memanggil Modal Bootstrap) --}}
                                <button type="button" data-bs-toggle="modal" data-bs-target="#detailModal{{ $driver->id }}" class="text-gray-500 hover:text-blue-600 transform hover:scale-110 transition" title="Detail">
                                    <i class="fas fa-eye fa-lg"></i>
                                </button>

                                {{-- Edit (Memanggil Modal Bootstrap) --}}
                                <button type="button" data-bs-toggle="modal" data-bs-target="#editModal{{ $driver->id }}" class="text-gray-500 hover:text-yellow-500 transform hover:scale-110 transition" title="Edit">
                                    <i class="fas fa-pencil-alt fa-lg"></i>
                                </button>

                                {{-- Hapus Satuan --}}
                                <button type="button" onclick="hapusSatuan('{{ route('admin.drivers.destroy', $driver->id) }}')" class="text-gray-500 hover:text-red-600 transform hover:scale-110 transition" title="Hapus">
                                    <i class="fas fa-trash-alt fa-lg"></i>
                                </button>
                                
                            </div>
                        </td>
                    </tr>

                    {{-- INCLUDE MODAL DARI FILE TERPISAH / BAWAAN --}}
                    @include('admin.partials._driver_modals', ['driver' => $driver])

                    @empty
                    <tr>
                        <td colspan="6" class="text-center py-10 text-gray-500">
                            <div class="flex flex-col items-center justify-center">
                                <i class="fas fa-folder-open text-4xl mb-3 text-gray-300"></i>
                                <span class="text-sm font-semibold">Belum ada data pendaftaran driver.</span>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination Tailwind --}}
        @if ($drivers->hasPages())
        <div class="mt-4 p-4 border-t border-gray-200">
            {{ $drivers->appends(request()->query())->links('vendor.pagination.tailwind') }}
        </div>
        @endif
    </div>

    {{-- Form Single Delete (Hidden) --}}
    <form id="singleDeleteForm" method="POST" class="hidden">
        @csrf
        @method('DELETE')
    </form>

    {{-- ======================================================================= --}}
    {{-- MODAL KONFIRMASI HAPUS MASSAL (MENGGUNAKAN UI PESANAN)                  --}}
    {{-- ======================================================================= --}}
    <div id="bulkDeleteModal" class="hidden" style="position: fixed; inset: 0px; z-index: 99999;">
        <div class="fixed inset-0 bg-gray-900 bg-opacity-75 transition-opacity" onclick="closeModal('bulkDeleteModal')"></div>
        <div class="fixed inset-0 overflow-y-auto">
            <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
                <div class="relative transform overflow-hidden rounded-lg bg-white text-left shadow-2xl transition-all w-full sm:max-w-2xl border border-gray-200">
                    <div class="bg-white px-4 pb-4 pt-5 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mx-auto flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                                <i class="fas fa-trash-alt text-red-600 text-lg"></i>
                            </div>
                            <div class="mt-3 text-center sm:ml-4 sm:mt-0 sm:text-left w-full">
                                <h3 class="text-xl font-bold leading-6 text-gray-900">Konfirmasi Hapus Data</h3>
                                <div class="mt-3">
                                    <p class="text-sm text-gray-600 mb-4">
                                        Anda yakin ingin menghapus <strong id="modalSelectedCount" class="text-red-600 text-lg">0</strong> driver berikut secara permanen? Data yang dihapus tidak dapat dikembalikan.
                                    </p>
                                    <div class="bg-gray-50 border border-gray-200 rounded-lg p-3 max-h-64 overflow-y-auto">
                                        <ul id="deleteItemsList" class="divide-y divide-gray-200 text-sm text-gray-700 font-medium">
                                            {{-- Di-inject via JS --}}
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6 border-t border-gray-200">
                        <button type="button" onclick="submitBulkDelete()" id="btnConfirmDelete" class="inline-flex w-full justify-center rounded-md bg-red-600 px-4 py-2 text-sm font-bold text-white shadow-sm hover:bg-red-700 sm:ml-3 sm:w-auto transition items-center gap-2">
                            <i class="fas fa-trash"></i> Ya, Hapus Semua
                        </button>
                        <button type="button" onclick="closeModal('bulkDeleteModal')" class="mt-3 inline-flex w-full justify-center rounded-md bg-white px-4 py-2 text-sm font-bold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-100 sm:mt-0 sm:w-auto transition">
                            Batal
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection

{{-- =========================================================== --}}
{{-- 4. JAVASCRIPT & SCRIPTS                                     --}}
{{-- =========================================================== --}}
@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://npmcdn.com/flatpickr/dist/l10n/id.js"></script>

    <script>
        // Modal Logic
        function openModal(id) {
            const el = document.getElementById(id);
            if(el) el.classList.remove('hidden');
        }
        function closeModal(id) {
            const el = document.getElementById(id);
            if(el) el.classList.add('hidden');
        }

        // === LOGIC READ MORE (MOBILE) ===
        function toggleDetails(index, btn) {
            const targets = document.querySelectorAll('.toggle-target-' + index);
            const icon = btn.querySelector('i');
            const textSpan = btn.querySelector('span');

            targets.forEach(target => {
                if (target.classList.contains('hidden')) {
                    target.classList.remove('hidden');
                    target.classList.add('block');
                    target.style.animation = "fadeIn 0.5s";
                } else {
                    target.classList.add('hidden');
                    target.classList.remove('block');
                }
            });

            if (icon.classList.contains('fa-chevron-down')) {
                icon.classList.replace('fa-chevron-down', 'fa-chevron-up');
                textSpan.innerText = "Tutup Detail";
                btn.classList.add('bg-red-50', 'text-red-600');
                btn.classList.remove('bg-gray-100', 'text-gray-600');
            } else {
                icon.classList.replace('fa-chevron-up', 'fa-chevron-down');
                textSpan.innerText = "Lihat Detail Lengkap";
                btn.classList.remove('bg-red-50', 'text-red-600');
                btn.classList.add('bg-gray-100', 'text-gray-600');
            }
        }

        // === FLATPICKR LOGIC ===
        (function() {
            var dateInput = document.getElementById('date_range_picker');
            var clearBtn = document.getElementById('clearDateBtn');
            if (dateInput) {
                var fp = flatpickr(dateInput, {
                    mode: "range",
                    dateFormat: "Y-m-d",
                    altInput: true,
                    altFormat: "j F Y",
                    locale: "id",
                    disableMobile: "true",
                    theme: "airbnb",
                    onChange: function(selectedDates, dateStr) {
                        if (dateStr && clearBtn) clearBtn.classList.remove('hidden');
                        else if (clearBtn) clearBtn.classList.add('hidden');
                    }
                });
                if(clearBtn) {
                    clearBtn.addEventListener('click', function() {
                        fp.clear();
                        clearBtn.classList.add('hidden');
                    });
                }
            }
        })();

        // === LOGIKA BULK DELETE & CHECKBOX ===
        const bulkActionBar = document.getElementById('bulkActionBar');
        const selectedCountText = document.getElementById('selectedCount');
        const btnSelectAll = document.getElementById('btnSelectAll');
        const checkAllHeader = document.getElementById('checkAllHeader');

        function toggleSelectAllHeader(source) {
            document.querySelectorAll('.row-checkbox').forEach(cb => cb.checked = source.checked);
            updateBulkActionUI();
        }

        function toggleSelectAll() {
            const checkboxes = document.querySelectorAll('.row-checkbox');
            const allChecked = Array.from(checkboxes).every(cb => cb.checked);
            checkboxes.forEach(cb => cb.checked = !allChecked);
            if(checkAllHeader) checkAllHeader.checked = !allChecked;
            updateBulkActionUI();
        }

        function updateBulkActionUI() {
            const checkboxes = document.querySelectorAll('.row-checkbox');
            const count = document.querySelectorAll('.row-checkbox:checked').length;
            
            if(selectedCountText) selectedCountText.innerText = count;
            if(checkAllHeader) checkAllHeader.checked = (count === checkboxes.length && count > 0);

            if(btnSelectAll) {
                if (count === checkboxes.length && count > 0) {
                    btnSelectAll.innerText = "Batal Pilih Semua";
                    btnSelectAll.classList.replace('bg-white', 'bg-gray-200');
                } else {
                    btnSelectAll.innerText = "Pilih Semua";
                    btnSelectAll.classList.replace('bg-gray-200', 'bg-white');
                }
            }

            if(bulkActionBar) {
                if (count > 0) {
                    bulkActionBar.classList.add('active');
                    bulkActionBar.style.display = 'flex';
                } else {
                    bulkActionBar.classList.remove('active');
                }
            }
        }

        // Tampilkan Custom Modal Hapus Massal
        function showBulkDeleteModal() {
            const checkedBoxes = document.querySelectorAll('.row-checkbox:checked');
            const count = checkedBoxes.length;

            if (count === 0) {
                alert("Pilih minimal satu data untuk dihapus.");
                return;
            }

            document.getElementById('modalSelectedCount').innerText = count;
            const listContainer = document.getElementById('deleteItemsList');
            listContainer.innerHTML = ''; 

            let displayCount = 0;
            checkedBoxes.forEach((cb) => {
                if (displayCount < 10) {
                    const li = document.createElement('li');
                    li.className = "py-2 px-3 flex items-center gap-2";
                    li.innerHTML = `<i class="fa-solid fa-user text-gray-400"></i> ${cb.getAttribute('data-name')}`;
                    listContainer.appendChild(li);
                    displayCount++;
                }
            });

            if (count > 10) {
                const li = document.createElement('li');
                li.className = "py-2 px-3 text-red-500 font-semibold";
                li.innerText = `...dan ${count - 10} driver lainnya.`;
                listContainer.appendChild(li);
            }

            openModal('bulkDeleteModal');
        }

        // Hapus Satuan
        function hapusSatuan(url) {
            if(confirm('Yakin ingin menghapus permanen driver ini?')) {
                const form = document.getElementById('singleDeleteForm');
                if(form) {
                    form.action = url;
                    form.submit();
                }
            }
        }

        // Submit Form Hapus Massal
        function submitBulkDelete() {
            const btnConfirm = document.getElementById('btnConfirmDelete');
            const form = document.getElementById('bulkDeleteForm');
            const checkedBoxes = document.querySelectorAll('.row-checkbox:checked');

            if(form) {
                if(btnConfirm) {
                    btnConfirm.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menghapus...';
                    btnConfirm.classList.add('opacity-70', 'cursor-wait');
                    btnConfirm.disabled = true;
                }

                form.querySelectorAll('input[name="selected_ids[]"]').forEach(el => el.remove());

                checkedBoxes.forEach(cb => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'selected_ids[]';
                    input.value = cb.value;
                    form.appendChild(input);
                });

                form.submit();
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            updateBulkActionUI();
        });
    </script>
@endpush