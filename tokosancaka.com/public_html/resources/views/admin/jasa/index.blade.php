@extends('layouts.admin')

@section('title', 'Master Data Jasa')
@section('page-title', 'Konfigurasi Bidang & Tarif Jasa')

@push('styles')
<style>
    /* Custom Scrollbar untuk Table agar bersih */
    .table-container::-webkit-scrollbar { height: 6px; }
    .table-container::-webkit-scrollbar-track { background: #f8fafc; rounded: 8px; }
    .table-container::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 8px; }
    .table-container::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
</style>
@endpush

@section('content')
<div class="mb-6">
    <h2 class="text-2xl font-bold text-gray-800 tracking-tight">Manajemen Layanan Jasa</h2>
    <p class="text-sm text-gray-500 mt-1">Atur Divisi Bidang, Kategori, dan Spesifikasi Tarif Layanan Aplikasi Anda.</p>
</div>

@include('layouts.partials.notifications')

<div class="grid grid-cols-1 xl:grid-cols-3 gap-6 items-start">

    {{-- ========================================== --}}
    {{-- KOTAK 1: DIVISI BIDANG --}}
    {{-- ========================================== --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden flex flex-col">
        <div class="p-5 border-b border-gray-50 flex justify-between items-center bg-white">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg bg-red-50 flex items-center justify-center text-[#d0011b]">
                    <i class="fas fa-layer-group text-sm"></i>
                </div>
                <h3 class="font-semibold text-gray-800">Divisi Utama</h3>
            </div>
            <button onclick="openModalBidang()" class="bg-gray-900 hover:bg-gray-800 text-white p-1.5 px-3 rounded-lg text-xs font-medium transition-colors shadow-sm flex items-center gap-1">
                <i class="fas fa-plus"></i> Tambah
            </button>
        </div>
        <div class="p-0 table-container overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50/50 text-[10px] uppercase tracking-wider text-gray-400">
                        <th class="px-5 py-3 font-semibold">Nama Bidang</th>
                        <th class="px-5 py-3 font-semibold text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($bidangs as $bidang)
                    <tr class="hover:bg-gray-50/50 transition-colors group">
                        <td class="px-5 py-3 text-sm font-medium text-gray-700">{{ $bidang->nama_bidang }}</td>
                        <td class="px-5 py-3 text-right opacity-0 group-hover:opacity-100 transition-opacity">
                            <div class="flex justify-end gap-2">
                                <button onclick="openModalBidang('{{ $bidang->id }}', '{{ $bidang->nama_bidang }}')" class="text-blue-500 hover:bg-blue-50 p-1.5 rounded transition-colors" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <form action="{{ route('admin.master_jasa.bidang.destroy', $bidang->id) }}" method="POST" onsubmit="return confirm('Hapus divisi ini? Semua sub bidang di dalamnya akan ikut terhapus!');" class="inline">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-red-500 hover:bg-red-50 p-1.5 rounded transition-colors" title="Hapus">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="2" class="px-5 py-8 text-center text-xs text-gray-400">Belum ada data Divisi.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ========================================== --}}
    {{-- KOTAK 2: SUB BIDANG --}}
    {{-- ========================================== --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden flex flex-col">
        <div class="p-5 border-b border-gray-50 flex justify-between items-center bg-white">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg bg-indigo-50 flex items-center justify-center text-indigo-600">
                    <i class="fas fa-tags text-sm"></i>
                </div>
                <h3 class="font-semibold text-gray-800">Sub Kategori</h3>
            </div>
            <button onclick="openModalSubBidang()" class="bg-gray-900 hover:bg-gray-800 text-white p-1.5 px-3 rounded-lg text-xs font-medium transition-colors shadow-sm flex items-center gap-1">
                <i class="fas fa-plus"></i> Tambah
            </button>
        </div>
        <div class="p-0 table-container overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50/50 text-[10px] uppercase tracking-wider text-gray-400">
                        <th class="px-5 py-3 font-semibold">Sub Bidang</th>
                        <th class="px-5 py-3 font-semibold text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($subBidangs as $sub)
                    <tr class="hover:bg-gray-50/50 transition-colors group">
                        <td class="px-5 py-3">
                            <span class="block text-sm font-medium text-gray-700">{{ $sub->nama_sub_bidang }}</span>
                            <span class="block text-[10px] text-gray-400 mt-0.5"><i class="fas fa-level-up-alt rotate-90 me-1"></i> {{ $sub->nama_bidang }}</span>
                        </td>
                        <td class="px-5 py-3 text-right opacity-0 group-hover:opacity-100 transition-opacity">
                            <div class="flex justify-end gap-2">
                                <button onclick="openModalSubBidang('{{ $sub->id }}', '{{ $sub->id_bidang }}', '{{ $sub->nama_sub_bidang }}')" class="text-blue-500 hover:bg-blue-50 p-1.5 rounded transition-colors">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <form action="{{ route('admin.master_jasa.sub_bidang.destroy', $sub->id) }}" method="POST" onsubmit="return confirm('Hapus kategori ini?');" class="inline">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-red-500 hover:bg-red-50 p-1.5 rounded transition-colors">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="2" class="px-5 py-8 text-center text-xs text-gray-400">Belum ada data Kategori.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ========================================== --}}
    {{-- KOTAK 3: LAYANAN & TARIF (DENGAN BULK DESTROY) --}}
    {{-- ========================================== --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden flex flex-col">
        <form action="{{ route('admin.master_jasa.bulk_destroy') }}" method="POST" id="formBulkDestroy">
            @csrf
            <input type="hidden" name="type" value="layanan">
            
            <div class="p-5 border-b border-gray-50 flex justify-between items-center bg-white">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-green-50 flex items-center justify-center text-green-600">
                        <i class="fas fa-clipboard-list text-sm"></i>
                    </div>
                    <h3 class="font-semibold text-gray-800">Layanan & Tarif</h3>
                </div>
                <div class="flex gap-2">
                    <button type="submit" onclick="return confirm('Hapus semua layanan yang dicentang?')" class="hidden bg-red-100 text-red-600 hover:bg-red-200 p-1.5 px-3 rounded-lg text-xs font-medium transition-colors shadow-sm items-center gap-1" id="btnBulkDelete">
                        <i class="fas fa-trash"></i> Hapus
                    </button>
                    <button type="button" onclick="openModalLayanan()" class="bg-gray-900 hover:bg-gray-800 text-white p-1.5 px-3 rounded-lg text-xs font-medium transition-colors shadow-sm flex items-center gap-1">
                        <i class="fas fa-plus"></i> Tambah
                    </button>
                </div>
            </div>
            <div class="p-0 table-container overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-50/50 text-[10px] uppercase tracking-wider text-gray-400">
                            <th class="px-4 py-3 w-10 text-center"><input type="checkbox" id="checkAll" class="rounded border-gray-300 text-gray-900 focus:ring-gray-900"></th>
                            <th class="px-2 py-3 font-semibold">Nama & Kategori</th>
                            <th class="px-4 py-3 font-semibold">Tarif Dasar</th>
                            <th class="px-4 py-3 font-semibold text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @forelse($layanans as $layanan)
                        <tr class="hover:bg-gray-50/50 transition-colors group">
                            <td class="px-4 py-3 text-center">
                                <input type="checkbox" name="ids[]" value="{{ $layanan->id }}" class="checkItem rounded border-gray-300 text-gray-900 focus:ring-gray-900">
                            </td>
                            <td class="px-2 py-3">
                                <span class="block text-sm font-medium text-gray-700">{{ $layanan->nama_layanan }}</span>
                                <span class="block text-[10px] text-gray-400 mt-0.5">{{ $layanan->nama_bidang }} • {{ $layanan->nama_sub_bidang }}</span>
                            </td>
                            <td class="px-4 py-3">
                                <span class="block text-sm font-bold text-gray-800">Rp{{ number_format($layanan->tarif_dasar, 0, ',', '.') }}</span>
                                <span class="block text-[9px] text-gray-400 uppercase tracking-wide">/ {{ $layanan->tipe_satuan }}</span>
                            </td>
                            <td class="px-4 py-3 text-right opacity-0 group-hover:opacity-100 transition-opacity">
                                <div class="flex justify-end gap-1">
                                    <button type="button" onclick="openModalLayanan('{{ $layanan->id }}', '{{ $layanan->id_sub_bidang }}', '{{ $layanan->nama_layanan }}', '{{ $layanan->tarif_dasar }}', '{{ $layanan->tipe_satuan }}')" class="text-blue-500 hover:bg-blue-50 p-1.5 rounded transition-colors">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="4" class="px-5 py-8 text-center text-xs text-gray-400">Belum ada data Layanan.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </form>
    </div>

</div>

{{-- ========================================================================= --}}
{{-- ⚡ MODAL COMPONENTS (Menggunakan JS Murni & Tailwind UI) ⚡ --}}
{{-- ========================================================================= --}}

{{-- 1. Modal Divisi Bidang --}}
<div id="modal-bidang" class="fixed inset-0 z-[100] hidden items-center justify-center">
    <div class="fixed inset-0 bg-gray-900/40 backdrop-blur-sm transition-opacity" onclick="closeModal('modal-bidang')"></div>
    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-sm p-6 transform transition-all scale-95 opacity-0" id="modal-bidang-content">
        <div class="flex justify-between items-center mb-5">
            <h3 class="text-lg font-bold text-gray-800" id="title-modal-bidang">Tambah Divisi</h3>
            <button type="button" onclick="closeModal('modal-bidang')" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times"></i></button>
        </div>
        <form id="form-bidang" action="{{ route('admin.master_jasa.bidang.store') }}" method="POST">
            @csrf
            <input type="hidden" name="_method" id="method-bidang" value="POST">
            
            <div class="mb-4">
                <label class="block text-xs font-semibold text-gray-600 mb-1.5 uppercase tracking-wide">Nama Divisi</label>
                <input type="text" name="nama_bidang" id="input_nama_bidang" class="w-full bg-gray-50 border border-gray-200 text-gray-800 text-sm rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-gray-900 focus:bg-white outline-none transition-all" placeholder="Contoh: Sancaka Home" required>
            </div>
            
            <button type="submit" class="w-full bg-gray-900 hover:bg-gray-800 text-white font-medium rounded-xl py-2.5 transition-colors">Simpan Divisi</button>
        </form>
    </div>
</div>

{{-- 2. Modal Sub Bidang --}}
<div id="modal-sub-bidang" class="fixed inset-0 z-[100] hidden items-center justify-center">
    <div class="fixed inset-0 bg-gray-900/40 backdrop-blur-sm transition-opacity" onclick="closeModal('modal-sub-bidang')"></div>
    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-sm p-6 transform transition-all scale-95 opacity-0" id="modal-sub-bidang-content">
        <div class="flex justify-between items-center mb-5">
            <h3 class="text-lg font-bold text-gray-800" id="title-modal-sub-bidang">Tambah Kategori</h3>
            <button type="button" onclick="closeModal('modal-sub-bidang')" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times"></i></button>
        </div>
        <form id="form-sub-bidang" action="{{ route('admin.master_jasa.sub_bidang.store') }}" method="POST">
            @csrf
            <input type="hidden" name="_method" id="method-sub-bidang" value="POST">
            
            <div class="mb-4">
                <label class="block text-xs font-semibold text-gray-600 mb-1.5 uppercase tracking-wide">Induk Divisi</label>
                <select name="id_bidang" id="input_id_bidang" class="w-full bg-gray-50 border border-gray-200 text-gray-800 text-sm rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-gray-900 focus:bg-white outline-none transition-all" required>
                    <option value="">-- Pilih Induk --</option>
                    @foreach($bidangs as $b)
                        <option value="{{ $b->id }}">{{ $b->nama_bidang }}</option>
                    @endforeach
                </select>
            </div>

            <div class="mb-5">
                <label class="block text-xs font-semibold text-gray-600 mb-1.5 uppercase tracking-wide">Nama Sub Kategori</label>
                <input type="text" name="nama_sub_bidang" id="input_nama_sub_bidang" class="w-full bg-gray-50 border border-gray-200 text-gray-800 text-sm rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-gray-900 focus:bg-white outline-none transition-all" placeholder="Contoh: Tukang Bangunan" required>
            </div>
            
            <button type="submit" class="w-full bg-gray-900 hover:bg-gray-800 text-white font-medium rounded-xl py-2.5 transition-colors">Simpan Kategori</button>
        </form>
    </div>
</div>

{{-- 3. Modal Layanan & Tarif --}}
<div id="modal-layanan" class="fixed inset-0 z-[100] hidden items-center justify-center">
    <div class="fixed inset-0 bg-gray-900/40 backdrop-blur-sm transition-opacity" onclick="closeModal('modal-layanan')"></div>
    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md p-6 transform transition-all scale-95 opacity-0" id="modal-layanan-content">
        <div class="flex justify-between items-center mb-5">
            <h3 class="text-lg font-bold text-gray-800" id="title-modal-layanan">Tambah Layanan</h3>
            <button type="button" onclick="closeModal('modal-layanan')" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times"></i></button>
        </div>
        <form id="form-layanan" action="{{ route('admin.master_jasa.layanan.store') }}" method="POST">
            @csrf
            <input type="hidden" name="_method" id="method-layanan" value="POST">
            
            <div class="mb-4">
                <label class="block text-xs font-semibold text-gray-600 mb-1.5 uppercase tracking-wide">Sub Kategori</label>
                <select name="id_sub_bidang" id="input_id_sub_bidang" class="w-full bg-gray-50 border border-gray-200 text-gray-800 text-sm rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-gray-900 focus:bg-white outline-none transition-all" required>
                    <option value="">-- Pilih Kategori --</option>
                    @foreach($subBidangs as $s)
                        <option value="{{ $s->id }}">{{ $s->nama_bidang }} - {{ $s->nama_sub_bidang }}</option>
                    @endforeach
                </select>
            </div>

            <div class="mb-4">
                <label class="block text-xs font-semibold text-gray-600 mb-1.5 uppercase tracking-wide">Nama Pekerjaan/Layanan</label>
                <input type="text" name="nama_layanan" id="input_nama_layanan" class="w-full bg-gray-50 border border-gray-200 text-gray-800 text-sm rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-gray-900 focus:bg-white outline-none transition-all" placeholder="Contoh: Pasang Keramik Lantai" required>
            </div>

            <div class="grid grid-cols-2 gap-4 mb-5">
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5 uppercase tracking-wide">Tarif Dasar (Rp)</label>
                    <input type="number" name="tarif_dasar" id="input_tarif_dasar" class="w-full bg-gray-50 border border-gray-200 text-gray-800 text-sm rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-gray-900 focus:bg-white outline-none transition-all" placeholder="50000" required>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5 uppercase tracking-wide">Satuan</label>
                    <input type="text" name="tipe_satuan" id="input_tipe_satuan" class="w-full bg-gray-50 border border-gray-200 text-gray-800 text-sm rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-gray-900 focus:bg-white outline-none transition-all" placeholder="Contoh: Per Meter" required>
                </div>
            </div>
            
            <button type="submit" class="w-full bg-gray-900 hover:bg-gray-800 text-white font-medium rounded-xl py-2.5 transition-colors">Simpan Layanan</button>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
    // ===============================================
    // FUNGSI ANIMASI MODAL (MIMIC NEXT.JS EFFECT)
    // ===============================================
    function openModal(id) {
        const modal = document.getElementById(id);
        const content = document.getElementById(id + '-content');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        
        // Timeout sedikit untuk memicu transisi CSS Tailwind
        setTimeout(() => {
            content.classList.remove('scale-95', 'opacity-0');
            content.classList.add('scale-100', 'opacity-100');
        }, 10);
    }

    function closeModal(id) {
        const modal = document.getElementById(id);
        const content = document.getElementById(id + '-content');
        
        content.classList.remove('scale-100', 'opacity-100');
        content.classList.add('scale-95', 'opacity-0');
        
        setTimeout(() => {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }, 200); // Waktu transisi
    }

    // ===============================================
    // FUNGSI POPULATE DATA KE FORM MODAL
    // ===============================================

    // 1. Modal Bidang
    function openModalBidang(id = null, name = '') {
        const form = document.getElementById('form-bidang');
        if (id) {
            document.getElementById('title-modal-bidang').innerText = 'Edit Divisi';
            document.getElementById('method-bidang').value = 'PUT';
            form.action = `/admin/master-jasa/bidang/${id}`;
            document.getElementById('input_nama_bidang').value = name;
        } else {
            document.getElementById('title-modal-bidang').innerText = 'Tambah Divisi';
            document.getElementById('method-bidang').value = 'POST';
            form.action = `{{ route('admin.master_jasa.bidang.store') }}`;
            form.reset();
        }
        openModal('modal-bidang');
    }

    // 2. Modal Sub Bidang
    function openModalSubBidang(id = null, id_bidang = '', name = '') {
        const form = document.getElementById('form-sub-bidang');
        if (id) {
            document.getElementById('title-modal-sub-bidang').innerText = 'Edit Kategori';
            document.getElementById('method-sub-bidang').value = 'PUT';
            form.action = `/admin/master-jasa/sub-bidang/${id}`;
            document.getElementById('input_id_bidang').value = id_bidang;
            document.getElementById('input_nama_sub_bidang').value = name;
        } else {
            document.getElementById('title-modal-sub-bidang').innerText = 'Tambah Kategori';
            document.getElementById('method-sub-bidang').value = 'POST';
            form.action = `{{ route('admin.master_jasa.sub_bidang.store') }}`;
            form.reset();
        }
        openModal('modal-sub-bidang');
    }

    // 3. Modal Layanan
    function openModalLayanan(id = null, id_sub_bidang = '', name = '', tarif = '', satuan = '') {
        const form = document.getElementById('form-layanan');
        if (id) {
            document.getElementById('title-modal-layanan').innerText = 'Edit Layanan';
            document.getElementById('method-layanan').value = 'PUT';
            form.action = `/admin/master-jasa/layanan/${id}`;
            document.getElementById('input_id_sub_bidang').value = id_sub_bidang;
            document.getElementById('input_nama_layanan').value = name;
            document.getElementById('input_tarif_dasar').value = Math.floor(tarif); // buang desimal .00
            document.getElementById('input_tipe_satuan').value = satuan;
        } else {
            document.getElementById('title-modal-layanan').innerText = 'Tambah Layanan';
            document.getElementById('method-layanan').value = 'POST';
            form.action = `{{ route('admin.master_jasa.layanan.store') }}`;
            form.reset();
        }
        openModal('modal-layanan');
    }

    // ===============================================
    // FUNGSI CHECKBOX BULK DELETE (Tabel Layanan)
    // ===============================================
    document.addEventListener("DOMContentLoaded", function() {
        const checkAll = document.getElementById('checkAll');
        const checkItems = document.querySelectorAll('.checkItem');
        const btnBulkDelete = document.getElementById('btnBulkDelete');

        function toggleBulkDeleteButton() {
            const checkedCount = document.querySelectorAll('.checkItem:checked').length;
            if (checkedCount > 0) {
                btnBulkDelete.classList.remove('hidden');
                btnBulkDelete.classList.add('flex');
            } else {
                btnBulkDelete.classList.add('hidden');
                btnBulkDelete.classList.remove('flex');
            }
        }

        if (checkAll) {
            checkAll.addEventListener('change', function() {
                checkItems.forEach(item => { item.checked = this.checked; });
                toggleBulkDeleteButton();
            });
        }

        checkItems.forEach(item => {
            item.addEventListener('change', toggleBulkDeleteButton);
        });
    });
</script>
@endpush