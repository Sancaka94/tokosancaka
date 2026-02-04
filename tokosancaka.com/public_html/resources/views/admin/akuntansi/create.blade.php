@extends('layouts.admin')

@section('title', 'Catat Transaksi Baru')

{{-- Add Flatpickr CSS in Head (Stack) --}}
@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<style>
    /* Custom Flatpickr Theme to match Tailwind Blue */
    .flatpickr-calendar {
        border-radius: 0.75rem;
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        border: 1px solid #f3f4f6;
    }
    .flatpickr-day.selected, .flatpickr-day.startRange, .flatpickr-day.endRange, .flatpickr-day.selected.inRange, .flatpickr-day.startRange.inRange, .flatpickr-day.endRange.inRange, .flatpickr-day.selected:focus, .flatpickr-day.startRange:focus, .flatpickr-day.endRange:focus, .flatpickr-day.selected:hover, .flatpickr-day.startRange:hover, .flatpickr-day.endRange:hover, .flatpickr-day.selected.prevMonthDay, .flatpickr-day.startRange.prevMonthDay, .flatpickr-day.endRange.prevMonthDay, .flatpickr-day.selected.nextMonthDay, .flatpickr-day.startRange.nextMonthDay, .flatpickr-day.endRange.nextMonthDay {
        background: #3b82f6; /* Tailwind Blue-500 */
        border-color: #3b82f6;
    }
    .flatpickr-months .flatpickr-month {
        background: #f9fafb; /* Gray-50 */
        border-top-left-radius: 0.75rem;
        border-top-right-radius: 0.75rem;
        padding-top: 10px;
    }
    .flatpickr-current-month .flatpickr-monthDropdown-months {
        font-weight: 600;
    }
</style>
@endpush

@section('content')
<div class="container mx-auto px-4 py-8 max-w-4xl">

    {{-- Header --}}
    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">Input Transaksi</h1>
            <p class="text-gray-500 mt-1">Catat pemasukan atau pengeluaran manual ke buku besar.</p>
        </div>
        <a href="{{ route('admin.akuntansi.index') }}" class="flex items-center gap-2 text-gray-600 hover:text-blue-600 transition-colors font-medium">
            <i class="fas fa-arrow-left"></i> Kembali
        </a>
    </div>

    <div class="bg-white rounded-2xl shadow-xl border border-gray-100 overflow-hidden">
        
        {{-- Form Header --}}
        <div class="bg-gray-50 px-8 py-4 border-b border-gray-100 flex items-center gap-3">
            <div class="w-3 h-3 rounded-full bg-blue-500"></div>
            <h2 class="font-semibold text-gray-700">Formulir Jurnal Umum</h2>
        </div>

        <div class="p-8">
            <form action="{{ route('admin.akuntansi.store') }}" method="POST" id="transactionForm">
                @csrf

                {{-- ======================================================== --}}
                {{-- STEP 1: PILIH UNIT USAHA (FILTER UTAMA) --}}
                {{-- ======================================================== --}}
                <div class="mb-8">
                    <label class="block text-sm font-bold text-gray-800 mb-2">1. Pilih Unit Usaha <span class="text-red-500">*</span></label>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <label class="cursor-pointer relative">
                            {{-- PERBAIKAN: name diganti jadi 'unit_usaha' --}}
                            <input type="radio" name="unit_usaha" value="Ekspedisi" class="peer sr-only" onchange="filterAccounts()">
                            <div class="p-4 rounded-xl border-2 border-gray-200 peer-checked:border-blue-500 peer-checked:bg-blue-50 hover:bg-gray-50 transition-all flex items-center gap-3">
                                <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center text-blue-600">
                                    <i class="fas fa-truck-fast"></i>
                                </div>
                                <div>
                                    <h3 class="font-bold text-gray-700">Jasa Ekspedisi</h3>
                                    <p class="text-xs text-gray-500">Logistik & Pengiriman</p>
                                </div>
                                <div class="absolute top-4 right-4 text-blue-500 opacity-0 peer-checked:opacity-100 transition-opacity">
                                    <i class="fas fa-check-circle text-xl"></i>
                                </div>
                            </div>
                        </label>

                        <label class="cursor-pointer relative">
                            {{-- PERBAIKAN: name diganti jadi 'unit_usaha' --}}
                            <input type="radio" name="unit_usaha" value="Percetakan" class="peer sr-only" onchange="filterAccounts()">
                            <div class="p-4 rounded-xl border-2 border-gray-200 peer-checked:border-purple-500 peer-checked:bg-purple-50 hover:bg-gray-50 transition-all flex items-center gap-3">
                                <div class="w-10 h-10 rounded-full bg-purple-100 flex items-center justify-center text-purple-600">
                                    <i class="fas fa-print"></i>
                                </div>
                                <div>
                                    <h3 class="font-bold text-gray-700">Percetakan</h3>
                                    <p class="text-xs text-gray-500">Digital Printing & Offset</p>
                                </div>
                                <div class="absolute top-4 right-4 text-purple-500 opacity-0 peer-checked:opacity-100 transition-opacity">
                                    <i class="fas fa-check-circle text-xl"></i>
                                </div>
                            </div>
                        </label>
                    </div>
                    @error('unit_usaha') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                {{-- Divider --}}
                <hr class="border-gray-100 mb-8 border-dashed">

                {{-- ======================================================== --}}
                {{-- STEP 2: DETAIL TRANSAKSI --}}
                {{-- ======================================================== --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-6 mb-6">
                    
                    {{-- Tanggal (UPGRADED DESIGN) --}}
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Tanggal Transaksi</label>
                        <div class="relative">
                            <input type="text" id="tanggal_picker" name="tanggal" value="{{ old('tanggal', date('Y-m-d')) }}" 
                                class="w-full pl-10 pr-4 py-3 rounded-lg border border-gray-300 focus:border-blue-500 focus:ring-4 focus:ring-blue-500/20 transition-all outline-none bg-white"
                                placeholder="Pilih tanggal...">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                                <i class="fas fa-calendar-alt"></i>
                            </div>
                        </div>
                        @error('tanggal') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    {{-- Jenis Arus --}}
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Jenis Arus Kas</label>
                        <select name="jenis" id="jenis_transaksi" onchange="filterAccounts()" 
                            class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:border-blue-500 focus:ring-4 focus:ring-blue-500/20 transition-all outline-none bg-white appearance-none">
                            <option value="Pengeluaran" {{ old('jenis') == 'Pengeluaran' ? 'selected' : '' }}>🔴 Pengeluaran (Uang Keluar)</option>
                            <option value="Pemasukan" {{ old('jenis') == 'Pemasukan' ? 'selected' : '' }}>🟢 Pemasukan (Uang Masuk)</option>
                        </select>
                        @error('jenis') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    {{-- Akun COA (Dinamis) --}}
                    <div class="md:col-span-2">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            Akun / Pos Keuangan <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            {{-- NAME adalah 'kode_akun' agar sesuai controller --}}
                            <select name="kode_akun" id="kode_akun" required disabled
                                class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:border-blue-500 focus:ring-4 focus:ring-blue-500/20 transition-all outline-none bg-gray-50 disabled:cursor-not-allowed disabled:bg-gray-100 disabled:text-gray-400 appearance-none">
                                <option value="">-- Pilih Unit Usaha Terlebih Dahulu --</option>
                            </select>
                            <div class="absolute inset-y-0 right-4 flex items-center pointer-events-none text-gray-500">
                                <i class="fas fa-chevron-down text-xs"></i>
                            </div>
                        </div>
                        <p class="text-xs text-gray-400 mt-2" id="akun_hint">Silakan pilih Unit Usaha di atas untuk memuat daftar akun.</p>
                        @error('kode_akun') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    {{-- Nominal --}}
                    <div class="md:col-span-2">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Jumlah Nominal (Rp)</label>
                        <div class="relative group">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <span class="text-gray-400 font-bold group-focus-within:text-blue-500 transition-colors">Rp</span>
                            </div>
                            <input type="number" name="jumlah" value="{{ old('jumlah') }}" placeholder="0" min="0" required
                                class="pl-12 w-full px-4 py-3 rounded-lg border border-gray-300 focus:border-blue-500 focus:ring-4 focus:ring-blue-500/20 transition-all outline-none text-lg font-bold text-gray-800 placeholder-gray-300">
                        </div>
                        @error('jumlah') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    {{-- Keterangan --}}
                    <div class="md:col-span-2">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Keterangan Detail</label>
                        <textarea name="keterangan" rows="3" required
                            class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:border-blue-500 focus:ring-4 focus:ring-blue-500/20 transition-all outline-none placeholder-gray-400"
                            placeholder="Contoh: Pembayaran tagihan listrik gudang periode Januari 2026...">{{ old('keterangan') }}</textarea>
                        @error('keterangan') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                </div>

                {{-- Action Buttons --}}
                <div class="flex items-center justify-end gap-4 pt-6 border-t border-gray-100 mt-6">
                    <button type="reset" class="px-6 py-2.5 rounded-lg text-gray-600 font-medium hover:bg-gray-100 transition-colors">
                        Reset Form
                    </button>
                    <button type="submit" class="px-8 py-2.5 rounded-lg bg-blue-600 text-white font-bold hover:bg-blue-700 shadow-lg shadow-blue-200 hover:shadow-blue-300 hover:-translate-y-0.5 transition-all flex items-center gap-2">
                        <i class="fas fa-save"></i> Simpan Transaksi
                    </button>
                </div>

            </form>
        </div>
    </div>
</div>

{{-- SCRIPT: Flatpickr & Filter Akun --}}
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://npmcdn.com/flatpickr/dist/l10n/id.js"></script> {{-- Bahasa Indonesia --}}

<script>
    // 1. Inisialisasi Flatpickr
    flatpickr("#tanggal_picker", {
        altInput: true,
        altFormat: "j F Y", 
        dateFormat: "Y-m-d", 
        locale: "id", 
        defaultDate: "{{ old('tanggal', date('Y-m-d')) }}",
        disableMobile: "true" 
    });

    // 2. Data Akun dari Controller (JSON)
    const allAccounts = @json($allAccounts);

    function filterAccounts() {
        // PERBAIKAN: Selector disesuaikan dengan name="unit_usaha"
        const selectedUnit = document.querySelector('input[name="unit_usaha"]:checked')?.value;
        const selectedType = document.getElementById('jenis_transaksi').value; // Pemasukan / Pengeluaran
        
        const selectBox = document.getElementById('kode_akun');
        const hintText = document.getElementById('akun_hint');

        // Reset Dropdown
        selectBox.innerHTML = '<option value="">-- Pilih Akun --</option>';

        if (!selectedUnit) {
            selectBox.disabled = true;
            selectBox.classList.add('bg-gray-50', 'cursor-not-allowed');
            selectBox.classList.remove('bg-white');
            hintText.innerText = "Silakan pilih Unit Usaha di atas untuk memuat daftar akun.";
            return;
        }

        // Enable Dropdown
        selectBox.disabled = false;
        selectBox.classList.remove('bg-gray-50', 'cursor-not-allowed');
        selectBox.classList.add('bg-white');
        hintText.innerText = `Menampilkan akun ${selectedType} untuk unit ${selectedUnit}.`;

        // Filter Data JSON
        const filtered = allAccounts.filter(acc => {
            const unitMatch = acc.unit_usaha === selectedUnit;
            
            let typeMatch = false;
            if (acc.tipe_arus === 'Netral') {
                typeMatch = true; 
            } else {
                typeMatch = (acc.tipe_arus === selectedType);
            }

            return unitMatch && typeMatch;
        });

        // Populate Options
        if (filtered.length === 0) {
            selectBox.innerHTML += '<option value="" disabled>Tidak ada akun yang cocok</option>';
        } else {
            filtered.forEach(acc => {
                const option = document.createElement('option');
                option.value = acc.kode_akun; // VALUE ADALAH KODE AKUN (Sesuai database)
                option.text = `[${acc.kode_akun}] ${acc.nama_akun} (${acc.kategori})`;
                selectBox.appendChild(option);
            });
        }
    }
</script>
@endpush

@endsection