@extends('layouts.admin')

@section('title', 'Catat Transaksi Baru')

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
                            <input type="radio" name="filter_unit" value="Ekspedisi" class="peer sr-only" onchange="filterAccounts()">
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
                            <input type="radio" name="filter_unit" value="Percetakan" class="peer sr-only" onchange="filterAccounts()">
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
                </div>

                {{-- Divider --}}
                <hr class="border-gray-100 mb-8 border-dashed">

                {{-- ======================================================== --}}
                {{-- STEP 2: DETAIL TRANSAKSI --}}
                {{-- ======================================================== --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-6 mb-6">
                    
                    {{-- Tanggal --}}
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Tanggal Transaksi</label>
                        <input type="date" name="tanggal" value="{{ old('tanggal', date('Y-m-d')) }}" 
                            class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:border-blue-500 focus:ring-4 focus:ring-blue-500/20 transition-all outline-none">
                        @error('tanggal') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    {{-- Jenis Arus --}}
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Jenis Arus Kas</label>
                        <select name="jenis" id="jenis_transaksi" onchange="filterAccounts()" 
                            class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:border-blue-500 focus:ring-4 focus:ring-blue-500/20 transition-all outline-none bg-white">
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
                            <select name="kode_akun" id="kode_akun" required disabled
                                class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:border-blue-500 focus:ring-4 focus:ring-blue-500/20 transition-all outline-none bg-gray-50 disabled:cursor-not-allowed disabled:bg-gray-100 disabled:text-gray-400">
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

{{-- SCRIPT FILTER AKUN --}}
@push('scripts')
<script>
    // Data Akun dari Controller (JSON)
    const allAccounts = @json($allAccounts);

    function filterAccounts() {
        // 1. Ambil nilai filter
        const selectedUnit = document.querySelector('input[name="filter_unit"]:checked')?.value;
        const selectedType = document.getElementById('jenis_transaksi').value; // Pemasukan / Pengeluaran
        
        const selectBox = document.getElementById('kode_akun');
        const hintText = document.getElementById('akun_hint');

        // 2. Reset Dropdown
        selectBox.innerHTML = '<option value="">-- Pilih Akun --</option>';

        if (!selectedUnit) {
            selectBox.disabled = true;
            selectBox.classList.add('bg-gray-50', 'cursor-not-allowed');
            selectBox.classList.remove('bg-white');
            hintText.innerText = "Silakan pilih Unit Usaha di atas untuk memuat daftar akun.";
            return;
        }

        // 3. Enable Dropdown
        selectBox.disabled = false;
        selectBox.classList.remove('bg-gray-50', 'cursor-not-allowed');
        selectBox.classList.add('bg-white');
        hintText.innerText = `Menampilkan akun ${selectedType} untuk unit ${selectedUnit}.`;

        // 4. Filter Data JSON
        // Logika Filter:
        // - Unit Usaha harus sama
        // - Tipe Arus (Pemasukan/Pengeluaran) harus sesuai, ATAU tipe akunnya 'Netral' (Kas/Bank bisa masuk/keluar)
        const filtered = allAccounts.filter(acc => {
            const unitMatch = acc.unit_usaha === selectedUnit;
            
            // Logic Arus:
            // Jika akun Pemasukan -> Hanya muncul saat pilih Pemasukan
            // Jika akun Pengeluaran -> Hanya muncul saat pilih Pengeluaran
            // Jika akun Netral (Kas/Bank/Utang) -> Muncul di Keduanya
            
            let typeMatch = false;
            if (acc.tipe_arus === 'Netral') {
                typeMatch = true; // Selalu muncul
            } else {
                typeMatch = (acc.tipe_arus === selectedType);
            }

            return unitMatch && typeMatch;
        });

        // 5. Populate Options
        if (filtered.length === 0) {
            selectBox.innerHTML += '<option value="" disabled>Tidak ada akun yang cocok</option>';
        } else {
            // Grouping biar rapi (Optional, sederhana dulu)
            filtered.forEach(acc => {
                const option = document.createElement('option');
                option.value = acc.kode_akun;
                option.text = `[${acc.kode_akun}] ${acc.nama_akun} (${acc.kategori})`;
                selectBox.appendChild(option);
            });
        }
    }
</script>
@endpush

@endsection