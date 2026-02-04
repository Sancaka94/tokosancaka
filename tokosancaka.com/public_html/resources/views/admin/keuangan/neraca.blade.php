@extends('layouts.admin')

@section('title', 'Laporan Neraca Standar')

@section('content')
<div class="container mx-auto px-4 py-6">

    {{-- HEADER & FILTER --}}
    <div class="bg-white rounded-xl shadow-sm p-6 mb-6 border border-gray-100 flex flex-col md:flex-row justify-between items-center gap-4">
        <div class="text-center md:text-left">
            <h1 class="text-xl font-extrabold text-gray-800 uppercase tracking-widest">PT. SANCAKA (Nama Bisnis Anda)</h1>
            <h2 class="text-lg font-bold text-gray-600">NERACA</h2>
            <p class="text-sm text-gray-500">Per Tanggal: {{ \Carbon\Carbon::parse($endDate)->format('d F Y') }}</p>
        </div>
        
        <div class="flex items-center gap-2 print:hidden">
            <form action="{{ route('admin.keuangan.neraca') }}" method="GET" class="flex items-center gap-2">
                <input type="date" name="date_start" value="{{ $startDate }}" class="border-gray-300 rounded-lg text-sm">
                <input type="date" name="date_end" value="{{ $endDate }}" class="border-gray-300 rounded-lg text-sm">
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-blue-700">Filter</button>
            </form>
            <button onclick="openModal('modalNeraca')" class="bg-emerald-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-emerald-700">Input Manual</button>
        </div>
    </div>

    {{-- LAYOUT NERACA STANDAR (GRID 2 KOLOM) --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-0 border border-gray-300 bg-white shadow-lg">

        {{-- ====================== KOLOM KIRI (AKTIVA) ====================== --}}
        {{-- Tambahkan 'flex flex-col' agar footer bisa didorong ke bawah --}}
        <div class="border-r border-gray-300 flex flex-col">
            
            {{-- HEADER --}}
            <div class="bg-gray-100 p-3 border-b border-gray-300 text-center font-bold text-lg text-gray-800">
                AKTIVA
            </div>
            
            {{-- CONTENT WRAPPER (Tambahkan 'flex-1' agar mengisi ruang kosong) --}}
            <div class="p-4 flex-1">
                {{-- AKTIVA LANCAR --}}
                <div class="mb-6">
                    <h3 class="font-bold text-gray-700 underline mb-2">Aktiva Lancar</h3>
                    <table class="w-full text-sm">
                        @foreach($neraca['aktiva_lancar'] as $akun => $nilai)
                        <tr class="border-b border-gray-100 hover:bg-gray-50">
                            <td class="py-1 pl-2 text-gray-600">{{ $akun }}</td>
                            <td class="py-1 pr-2 text-right font-medium text-gray-800">
                                {{ $nilai == 0 ? '-' : 'Rp'.number_format($nilai, 0, ',', '.') }}
                            </td>
                        </tr>
                        @endforeach
                        {{-- Subtotal --}}
                        <tr class="bg-gray-50 font-bold">
                            <td class="py-2 pl-2 text-gray-800 italic">Total Aktiva Lancar</td>
                            <td class="py-2 pr-2 text-right text-gray-900 border-t border-gray-300">
                                Rp{{ number_format(array_sum($neraca['aktiva_lancar']), 0, ',', '.') }}
                            </td>
                        </tr>
                    </table>
                </div>

                {{-- AKTIVA TETAP --}}
                <div class="mb-4">
                    <h3 class="font-bold text-gray-700 underline mb-2">Aktiva Tetap</h3>
                    <table class="w-full text-sm">
                        @foreach($neraca['aktiva_tetap'] as $akun => $nilai)
                        <tr class="border-b border-gray-100 hover:bg-gray-50">
                            <td class="py-1 pl-2 text-gray-600">{{ $akun }}</td>
                            <td class="py-1 pr-2 text-right font-medium text-gray-800">
                                {{ $nilai == 0 ? '-' : 'Rp'.number_format($nilai, 0, ',', '.') }}
                            </td>
                        </tr>
                        @endforeach
                        {{-- Subtotal --}}
                        <tr class="bg-gray-50 font-bold">
                            <td class="py-2 pl-2 text-gray-800 italic">Total Aktiva Tetap</td>
                            <td class="py-2 pr-2 text-right text-gray-900 border-t border-gray-300">
                                Rp{{ number_format(array_sum($neraca['aktiva_tetap']), 0, ',', '.') }}
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            {{-- FOOTER TOTAL (Akan selalu di bawah sejajar) --}}
            <div class="bg-emerald-50 p-4 border-t border-emerald-200 flex justify-between items-center mt-auto">
                <span class="font-bold text-lg text-emerald-900">TOTAL AKTIVA</span>
                <span class="font-bold text-xl text-emerald-700">Rp{{ number_format($neraca['total_aset'], 0, ',', '.') }}</span>
            </div>
        </div>


        {{-- ====================== KOLOM KANAN (PASIVA) ====================== --}}
        {{-- Tambahkan 'flex flex-col' --}}
        <div class="flex flex-col">
            
            {{-- HEADER --}}
            <div class="bg-gray-100 p-3 border-b border-gray-300 text-center font-bold text-lg text-gray-800">
                PASIVA
            </div>

            {{-- CONTENT WRAPPER (Tambahkan 'flex-1') --}}
            <div class="p-4 flex-1">
                {{-- KEWAJIBAN --}}
                <div class="mb-6">
                    <h3 class="font-bold text-gray-700 underline mb-2">Kewajiban (Utang)</h3>
                    <table class="w-full text-sm">
                        @foreach($neraca['kewajiban'] as $akun => $nilai)
                        <tr class="border-b border-gray-100 hover:bg-gray-50">
                            <td class="py-1 pl-2 text-gray-600">{{ $akun }}</td>
                            <td class="py-1 pr-2 text-right font-medium text-gray-800">
                                {{ $nilai == 0 ? '-' : 'Rp'.number_format($nilai, 0, ',', '.') }}
                            </td>
                        </tr>
                        @endforeach
                        {{-- Subtotal --}}
                        <tr class="bg-gray-50 font-bold">
                            <td class="py-2 pl-2 text-gray-800 italic">Total Kewajiban</td>
                            <td class="py-2 pr-2 text-right text-gray-900 border-t border-gray-300">
                                Rp{{ number_format(array_sum($neraca['kewajiban']), 0, ',', '.') }}
                            </td>
                        </tr>
                    </table>
                </div>

                {{-- EKUITAS --}}
                <div class="mb-4">
                    <h3 class="font-bold text-gray-700 underline mb-2">Ekuitas (Modal)</h3>
                    <table class="w-full text-sm">
                        @foreach($neraca['ekuitas'] as $akun => $nilai)
                        <tr class="border-b border-gray-100 hover:bg-gray-50 {{ str_contains($akun, 'Penyeimbang') ? 'bg-amber-50' : '' }}">
                            <td class="py-1 pl-2 text-gray-600">
                                {{ $akun }}
                                @if(str_contains($akun, 'Penyeimbang')) 
                                    <i class="fas fa-balance-scale text-amber-500 ms-1" title="Balancing Figure"></i>
                                @endif
                            </td>
                            <td class="py-1 pr-2 text-right font-medium {{ $nilai < 0 ? 'text-red-600' : 'text-gray-800' }}">
                                Rp{{ number_format($nilai, 0, ',', '.') }}
                            </td>
                        </tr>
                        @endforeach
                        {{-- Subtotal --}}
                        <tr class="bg-gray-50 font-bold">
                            <td class="py-2 pl-2 text-gray-800 italic">Total Ekuitas</td>
                            <td class="py-2 pr-2 text-right text-gray-900 border-t border-gray-300">
                                Rp{{ number_format(array_sum($neraca['ekuitas']), 0, ',', '.') }}
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            {{-- FOOTER TOTAL (Akan selalu di bawah sejajar) --}}
            <div class="bg-blue-50 p-4 border-t border-blue-200 flex justify-between items-center mt-auto">
                <span class="font-bold text-lg text-blue-900">TOTAL PASIVA</span>
                <span class="font-bold text-xl text-blue-700">Rp{{ number_format($neraca['total_pasiva'], 0, ',', '.') }}</span>
            </div>
        </div>

    </div>

    {{-- =========================================================== --}}
    {{-- 3. TABEL CRUD (RINCIAN DATA NERACA MANUAL) --}}
    {{-- =========================================================== --}}
    <div class="mt-8 bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50 flex justify-between items-center">
            <h3 class="font-bold text-gray-700 flex items-center gap-2">
                <i class="fas fa-list-alt text-emerald-600"></i> Rincian Inputan Neraca Manual
            </h3>
            <span class="text-xs text-gray-500 bg-white px-2 py-1 rounded border">
                Data yang Anda input manual
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left text-gray-600">
                <thead class="text-xs text-gray-700 uppercase bg-emerald-50 border-b border-emerald-100">
                    <tr>
                        <th class="px-4 py-3">Tanggal</th>
                        <th class="px-4 py-3">Posisi</th>
                        <th class="px-4 py-3">Keterangan</th>
                        <th class="px-4 py-3 text-right">Nilai (Rp)</th>
                        <th class="px-4 py-3 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @php
                        // Ambil Data Manual Khusus Table ini (kode_akun = NERACA)
                        $listNeraca = \App\Models\Keuangan::where('kode_akun', 'NERACA')
                                                ->whereBetween('tanggal', [$startDate, $endDate])
                                                ->orderBy('tanggal', 'desc')
                                                ->get();
                    @endphp

                    @forelse($listNeraca as $item)
                    <tr class="hover:bg-gray-50 transition group">
                        <td class="px-4 py-3 whitespace-nowrap">
                            {{ \Carbon\Carbon::parse($item->tanggal)->format('d M Y') }}
                        </td>
                        <td class="px-4 py-3">
                            <span class="bg-gray-100 text-gray-800 text-xs font-bold px-2 py-0.5 rounded border border-gray-200">
                                {{ $item->kategori }}
                            </span>
                        </td>
                        <td class="px-4 py-3 font-medium text-gray-800">
                            {{ $item->keterangan }}
                        </td>
                        <td class="px-4 py-3 text-right font-bold text-gray-700 group-hover:text-emerald-600">
                            Rp{{ number_format($item->jumlah, 0, ',', '.') }}
                        </td>
                        <td class="px-4 py-3 text-center">
                            <div class="flex justify-center gap-2">
                                <button onclick='editNeraca(@json($item))' class="bg-amber-100 text-amber-600 hover:bg-amber-200 px-2 py-1 rounded text-xs transition" title="Edit">
                                    <i class="fas fa-pencil-alt"></i>
                                </button>
                                <form action="{{ route('admin.keuangan.destroy', $item->id) }}" method="POST" onsubmit="return confirm('Hapus data neraca ini?');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="bg-red-100 text-red-600 hover:bg-red-200 px-2 py-1 rounded text-xs transition" title="Hapus">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-6 py-8 text-center text-gray-400 italic">
                            Belum ada data manual. Klik tombol <b>"Input Data Neraca"</b> di atas.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div> {{-- End Container --}}

{{-- =========================================================== --}}
{{-- 4. MODAL FORM INPUT (CREATE & EDIT) --}}
{{-- =========================================================== --}}
<div id="modalNeraca" class="fixed inset-0 z-[100] hidden overflow-y-auto">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        {{-- Overlay Gelap --}}
        <div class="fixed inset-0 bg-gray-900 bg-opacity-60 transition-opacity backdrop-blur-sm" onclick="closeModal('modalNeraca')"></div>

        <div class="inline-block align-bottom bg-white rounded-xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg w-full">
            <form action="{{ route('admin.keuangan.store') }}" method="POST" id="formNeraca">
                @csrf
                {{-- ID Hidden untuk Edit --}}
                <input type="hidden" name="id" id="edit_id">

                <div class="bg-white px-6 pt-6 pb-4">
                    <div class="flex justify-between items-center mb-5 border-b pb-3">
                        <h3 class="text-lg leading-6 font-bold text-gray-900 flex items-center" id="modalTitle">
                            <i class="fas fa-balance-scale text-emerald-600 me-2"></i> Input Data Neraca
                        </h3>
                        <button type="button" onclick="closeModal('modalNeraca')" class="text-gray-400 hover:text-gray-500 transition">
                            <i class="fas fa-times text-xl"></i>
                        </button>
                    </div>

                    <div class="space-y-4">
                        {{-- TANGGAL --}}
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Tanggal Pencatatan</label>
                            <input type="date" name="tanggal" id="edit_tanggal" value="{{ date('Y-m-d') }}" class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-emerald-500 text-sm" required>
                        </div>

                        {{-- KATEGORI (DISESUAIKAN DENGAN FORMAT LAPORAN BARU) --}}
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Posisi Neraca</label>
                            <select id="edit_kategori" name="kategori" class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-emerald-500 text-sm bg-gray-50 font-medium" onchange="adjustNeracaType(this)" required>
                                <option value="" disabled selected>-- Pilih Akun Neraca --</option>
                                
                                {{-- 1. AKTIVA LANCAR --}}
                                <optgroup label="I. AKTIVA LANCAR">
                                    <option value="Kas Tunai" data-jenis="Pemasukan" data-desc="Uang tunai di tangan / kasir">Kas Tunai</option>
                                    <option value="Kas Besar" data-jenis="Pemasukan" data-desc="Brankas utama / Kas Besar">Kas Besar</option>
                                    <option value="Bank BCA" data-jenis="Pemasukan" data-desc="Rekening BCA Perusahaan">Bank BCA</option>
                                    <option value="Bank BRI" data-jenis="Pemasukan" data-desc="Rekening BRI Perusahaan">Bank BRI</option>
                                    <option value="E-Wallet" data-jenis="Pemasukan" data-desc="GoPay, OVO, Dana, ShopeePay">E-Wallet</option>
                                    <option value="Piutang Usaha" data-jenis="Pemasukan" data-desc="Tagihan ke pelanggan (Bon)">Piutang Usaha</option>
                                    <option value="Persediaan" data-jenis="Pemasukan" data-desc="Nilai stok barang dagang">Persediaan Barang</option>
                                    <option value="Perlengkapan" data-jenis="Pemasukan" data-desc="Perlengkapan habis pakai (ATK/Plastik)">Perlengkapan</option>
                                </optgroup>

                                {{-- 2. AKTIVA TETAP --}}
                                <optgroup label="II. AKTIVA TETAP">
                                    <option value="Tanah" data-jenis="Pengeluaran" data-desc="Tanah hak milik perusahaan">Tanah</option>
                                    <option value="Bangunan" data-jenis="Pengeluaran" data-desc="Gedung / Ruko permanen">Bangunan</option>
                                    <option value="Kendaraan" data-jenis="Pengeluaran" data-desc="Mobil Box, Motor Operasional">Kendaraan</option>
                                    <option value="Mesin" data-jenis="Pengeluaran" data-desc="Mesin produksi / cetak">Mesin</option>
                                    <option value="Peralatan" data-jenis="Pengeluaran" data-desc="Laptop, AC, Meja, Kursi">Peralatan Kantor</option>
                                    <option value="Inventaris" data-jenis="Pengeluaran" data-desc="Inventaris lainnya">Inventaris Lainnya</option>
                                </optgroup>

                                {{-- 3. KEWAJIBAN (HUTANG) --}}
                                <optgroup label="III. KEWAJIBAN (HUTANG)">
                                    <option value="Hutang Dagang" data-jenis="Pemasukan" data-desc="Hutang ke Supplier / Vendor">Hutang Dagang</option>
                                    <option value="Hutang Bank" data-jenis="Pemasukan" data-desc="Pinjaman Bank (KUR/Modal Kerja)">Hutang Bank</option>
                                    <option value="Hutang Lainnya" data-jenis="Pemasukan" data-desc="Hutang ke pihak lain">Hutang Lain-lain</option>
                                </optgroup>

                                {{-- 4. MODAL (EKUITAS) --}}
                                <optgroup label="IV. MODAL (EKUITAS)">
                                    <option value="Modal Disetor" data-jenis="Pemasukan" data-desc="Setoran tambahan dari pemilik">Modal Disetor (Tambah Modal)</option>
                                    <option value="Prive" data-jenis="Pengeluaran" data-desc="Pengambilan uang untuk pribadi">Prive (Ambil Modal)</option>
                                </optgroup>
                            </select>
                            
                            {{-- Hint Kecil --}}
                            <p id="neraca_hint" class="text-[10px] text-emerald-600 mt-1 italic font-bold hidden"></p>
                        </div>

                        {{-- Auto Input Jenis --}}
                        <input type="hidden" name="jenis" id="auto_jenis">
                        <input type="hidden" name="unit_usaha" value="Pusat">
                        <input type="hidden" name="kode_akun" value="NERACA"> 

                        {{-- KETERANGAN --}}
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Keterangan Detail</label>
                            <input type="text" name="keterangan" id="edit_keterangan" class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-emerald-500 text-sm" placeholder="Contoh: Saldo Awal, Beli Mobil Box, Pinjaman KUR..." required>
                        </div>

                        {{-- JUMLAH --}}
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Nilai Rupiah</label>
                            <div class="relative rounded-md shadow-sm">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <span class="text-gray-500 font-bold sm:text-sm">Rp</span>
                                </div>
                                <input type="number" name="jumlah" id="edit_jumlah" class="w-full pl-10 border-gray-300 rounded-lg shadow-sm focus:ring-emerald-500 text-lg font-bold text-gray-800" placeholder="0" required>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-gray-50 px-6 py-4 flex flex-row-reverse gap-2">
                    <button type="submit" class="w-full sm:w-auto inline-flex justify-center rounded-lg border border-transparent shadow-sm px-5 py-2 bg-emerald-600 text-base font-medium text-white hover:bg-emerald-700 focus:outline-none sm:text-sm transition">
                        Simpan Data
                    </button>
                    <button type="button" onclick="closeModal('modalNeraca')" class="w-full sm:w-auto inline-flex justify-center rounded-lg border border-gray-300 shadow-sm px-5 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-100 sm:text-sm transition">
                        Batal
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    // 1. Fungsi Buka Modal (Mode Create)
    function openModal(id) {
        document.getElementById('formNeraca').reset();
        document.getElementById('edit_id').value = ''; 
        document.getElementById('modalTitle').innerHTML = '<i class="fas fa-balance-scale text-emerald-600 me-2"></i> Input Data Neraca';
        document.getElementById('neraca_hint').classList.add('hidden');
        
        const modal = document.getElementById(id);
        modal.classList.remove('hidden');
    }

    // 2. Fungsi Tutup Modal
    function closeModal(id) {
        document.getElementById(id).classList.add('hidden');
    }

    // 3. Fungsi Isi Form (Mode Edit)
    function editNeraca(data) {
        document.getElementById('edit_id').value = data.id;
        document.getElementById('edit_tanggal').value = data.tanggal;
        document.getElementById('edit_kategori').value = data.kategori;
        document.getElementById('edit_keterangan').value = data.keterangan;
        document.getElementById('edit_jumlah').value = data.jumlah;
        document.getElementById('auto_jenis').value = data.jenis;

        // Trigger perubahan dropdown manual agar hint muncul
        const select = document.getElementById('edit_kategori');
        adjustNeracaType(select);

        document.getElementById('modalTitle').innerHTML = '<i class="fas fa-pencil-alt text-amber-500 me-2"></i> Edit Data Neraca';
        document.getElementById('modalNeraca').classList.remove('hidden');
    }

    // 4. Logic Otomatis Set Jenis (Masuk/Keluar) berdasarkan Kategori
    function adjustNeracaType(select) {
        const selectedOption = select.options[select.selectedIndex];
        if(selectedOption.value === "") return;

        const jenis = selectedOption.getAttribute('data-jenis');
        const desc = selectedOption.getAttribute('data-desc');

        document.getElementById('auto_jenis').value = jenis;
        
        const hint = document.getElementById('neraca_hint');
        hint.innerText = desc;
        hint.classList.remove('hidden');
    }
</script>
@endpush