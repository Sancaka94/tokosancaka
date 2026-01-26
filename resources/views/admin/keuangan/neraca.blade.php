@extends('layouts.admin')

@section('title', 'Neraca Keuangan')

@section('content')
<div class="container mx-auto px-4 py-6">

    {{-- HEADER & FILTER --}}
    <div class="bg-white rounded-xl shadow-sm p-6 mb-6 border border-gray-100 flex flex-col md:flex-row justify-between items-center gap-4">
        <div>
            <h1 class="text-2xl font-extrabold text-gray-800">Neraca Keuangan</h1>
            <p class="text-sm text-gray-500">Periode: {{ \Carbon\Carbon::parse($startDate)->format('d M Y') }} s.d. {{ \Carbon\Carbon::parse($endDate)->format('d M Y') }}</p>
        </div>

        {{-- Filter Tanggal Sederhana --}}
        <form action="{{ route('admin.keuangan.neraca') }}" method="GET" class="flex items-center gap-2">
            <input type="date" name="date_start" value="{{ $startDate }}" class="border-gray-300 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500">
            <span class="text-gray-400">-</span>
            <input type="date" name="date_end" value="{{ $endDate }}" class="border-gray-300 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500">
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition">
                <i class="fas fa-filter me-1"></i> Filter
            </button>

            {{-- TAMBAHKAN TOMBOL INI DI SEBELAHNYA --}}
            <button type="button" onclick="openModal('modalNeraca')" class="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition shadow-sm ml-2">
                <i class="fas fa-plus-circle me-1"></i> Input Data Neraca
            </button>

        </form>
    </div>

    {{-- KONTEN NERACA (GRID 2 KOLOM) --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

        {{-- KOLOM KIRI: ASET (PEMASUKAN / KAS MASUK) --}}
        <div class="bg-white rounded-xl shadow-lg border-t-4 border-green-500 overflow-hidden">
            <div class="bg-green-50 px-6 py-4 border-b border-green-100 flex justify-between items-center">
                <h3 class="font-bold text-green-800 uppercase tracking-wide">
                    <i class="fas fa-wallet me-2"></i> Aset Lancar (Kas & Bank)
                </h3>
            </div>
            
            <div class="p-6">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-xs text-gray-400 uppercase border-b border-gray-100">
                            <th class="text-left py-2">Sumber Dana (Akun)</th>
                            <th class="text-right py-2">Nilai (IDR)</th>
                        </tr>
                    </thead>
                    <tbody>
                        {{-- 1. ASET LANCAR --}}
                        <tr><td colspan="2" class="bg-gray-50 font-bold text-xs text-gray-500 py-1 px-2">ASET LANCAR</td></tr>
                        @foreach($neraca['aset_lancar'] as $nama => $nilai)
                        <tr class="hover:bg-gray-50">
                            <td class="py-2 pl-4 text-gray-700">{{ $nama }}</td>
                            <td class="py-2 text-right font-bold text-gray-800">Rp{{ number_format($nilai, 0, ',', '.') }}</td>
                        </tr>
                        @endforeach

                        {{-- 2. ASET TETAP (BARU) --}}
                        <tr><td colspan="2" class="bg-gray-50 font-bold text-xs text-gray-500 py-1 px-2 mt-2">ASET TETAP</td></tr>
                        @foreach($neraca['aset_tetap'] as $nama => $nilai)
                        <tr class="hover:bg-gray-50">
                            <td class="py-2 pl-4 text-gray-700">{{ $nama }}</td>
                            <td class="py-2 text-right font-bold text-gray-800">Rp{{ number_format($nilai, 0, ',', '.') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                    
                    {{-- Subtotal Aset --}}
                    <tfoot class="border-t-2 border-gray-200">
                        <tr>
                            <td class="py-4 font-extrabold text-gray-800 text-base">TOTAL ASET</td>
                            <td class="py-4 text-right font-extrabold text-green-600 text-lg">
                                Rp{{ number_format($neraca['total_aset'], 0, ',', '.') }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        {{-- KOLOM KANAN: KEWAJIBAN & EKUITAS --}}
        <div class="space-y-6">

            {{-- 1. KEWAJIBAN (PENGELUARAN / MODAL) --}}
            <div class="bg-white rounded-xl shadow-lg border-t-4 border-red-500 overflow-hidden">
                <div class="bg-red-50 px-6 py-4 border-b border-red-100">
                    <h3 class="font-bold text-red-800 uppercase tracking-wide">
                        <i class="fas fa-file-invoice-dollar me-2"></i> Kewajiban & Beban
                    </h3>
                </div>
                <div class="p-6">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-xs text-gray-400 uppercase border-b border-gray-100">
                                <th class="text-left py-2">Jenis Beban</th>
                                <th class="text-right py-2">Nilai (IDR)</th>
                            </tr>
                        </thead>
                        <tbody>
                            {{-- 1. KEWAJIBAN --}}
                            <tr><td colspan="2" class="bg-gray-50 font-bold text-xs text-gray-500 py-1 px-2">KEWAJIBAN JANGKA PENDEK & PANJANG</td></tr>
                            @forelse($neraca['kewajiban'] as $nama => $nilai)
                            <tr class="hover:bg-gray-50">
                                <td class="py-2 pl-4 text-gray-700">{{ $nama }}</td>
                                <td class="py-2 text-right font-bold text-gray-800">Rp{{ number_format($nilai, 0, ',', '.') }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="2" class="py-2 pl-4 text-gray-400 italic text-xs">Tidak ada data hutang</td></tr>
                            @endforelse

                            {{-- 2. EKUITAS --}}
                            <tr><td colspan="2" class="bg-gray-50 font-bold text-xs text-gray-500 py-1 px-2 mt-2">EKUITAS</td></tr>
                            @foreach($neraca['ekuitas'] as $nama => $nilai)
                            <tr class="hover:bg-gray-50">
                                <td class="py-2 pl-4 text-gray-700">{{ $nama }}</td>
                                <td class="py-2 text-right font-bold {{ $nilai < 0 ? 'text-red-600' : 'text-blue-600' }}">
                                    Rp{{ number_format($nilai, 0, ',', '.') }}
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="border-t border-gray-200 bg-gray-50">
                            <tr>
                                <td class="py-3 pl-3 font-bold text-gray-600">Total Kewajiban</td>
                                <td class="py-3 pr-3 text-right font-bold text-red-600">
                                    Rp{{ number_format($neraca['total_kewajiban'], 0, ',', '.') }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            {{-- 2. EKUITAS (LABA DITAHAN) --}}
            <div class="bg-white rounded-xl shadow-lg border-t-4 border-blue-500 overflow-hidden">
                <div class="bg-blue-50 px-6 py-4 border-b border-blue-100">
                    <h3 class="font-bold text-blue-800 uppercase tracking-wide">
                        <i class="fas fa-chart-pie me-2"></i> Ekuitas Pemilik
                    </h3>
                </div>
                <div class="p-6">
                    <table class="w-full text-sm">
                        <tbody class="divide-y divide-gray-50">
                            <tr>
                                <td class="py-3 font-medium text-gray-600">Modal Disetor</td>
                                <td class="py-3 text-right font-bold text-gray-800">Rp0</td> {{-- Bisa diganti dinamis jika ada fitur input modal awal --}}
                            </tr>
                            <tr>
                                <td class="py-3 font-medium text-gray-600">
                                    Laba Bersih (Tahun Berjalan)
                                    <div class="text-[10px] text-gray-400">(Aset - Kewajiban)</div>
                                </td>
                                <td class="py-3 text-right font-bold {{ $neraca['ekuitas'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                    Rp{{ number_format($neraca['ekuitas'], 0, ',', '.') }}
                                </td>
                            </tr>
                        </tbody>
                        <tfoot class="border-t-2 border-gray-200">
                            <tr>
                                <td class="py-4 font-extrabold text-gray-800 text-base">TOTAL EKUITAS + KEWAJIBAN</td>
                                <td class="py-4 text-right font-extrabold text-blue-600 text-lg">
                                    {{-- BENAR: Gunakan 'total_ekuitas' yang berupa angka --}}
                                    Rp{{ number_format($neraca['total_kewajiban'] + $neraca['total_ekuitas'], 0, ',', '.') }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

        </div>
    </div>
</div>

{{-- =========================================================== --}}
    {{-- TABEL CRUD RINCIAN DATA NERACA (MANAJEMEN DATA) --}}
    {{-- =========================================================== --}}
    <div class="mt-8 bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50 flex justify-between items-center">
            <h3 class="font-bold text-gray-700 flex items-center gap-2">
                <i class="fas fa-list-alt text-emerald-600"></i> Rincian Inputan Neraca
            </h3>
            <span class="text-xs text-gray-500 bg-white px-2 py-1 rounded border">
                Data Manual
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left text-gray-600">
                <thead class="text-xs text-gray-700 uppercase bg-emerald-50 border-b border-emerald-100">
                    <tr>
                        <th class="px-4 py-3">Tanggal</th>
                        <th class="px-4 py-3">Kategori (Pos)</th>
                        <th class="px-4 py-3">Keterangan</th>
                        <th class="px-4 py-3 text-right">Nilai (Rp)</th>
                        <th class="px-4 py-3 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    {{-- 
                        AMBIL DATA KHUSUS NERACA 
                        Logic: Ambil data dari variabel $dataKeuangan yang dikirim controller,
                        tapi filter hanya yang kode_akun = 'NERACA' atau kategori2 tertentu.
                    --}}
                    @php
                        // Filter data yang ditampilkan di tabel ini hanya data inputan manual Neraca
                        $listNeraca = \App\Models\Keuangan::where('kode_akun', 'NERACA')
                                        ->whereBetween('tanggal', [$startDate, $endDate])
                                        ->orderBy('tanggal', 'desc')
                                        ->get();
                    @endphp

                    @forelse($listNeraca as $item)
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-4 py-3 whitespace-nowrap">
                            {{ \Carbon\Carbon::parse($item->tanggal)->format('d M Y') }}
                        </td>
                        <td class="px-4 py-3">
                            <span class="bg-gray-100 text-gray-800 text-xs font-semibold px-2 py-0.5 rounded border border-gray-200">
                                {{ $item->kategori }}
                            </span>
                             {{-- Badge Jenis Flow --}}
                            @if($item->jenis == 'Pemasukan')
                                <span class="text-[10px] text-green-600 ml-1"><i class="fas fa-arrow-down"></i> Masuk</span>
                            @else
                                <span class="text-[10px] text-red-600 ml-1"><i class="fas fa-arrow-up"></i> Keluar</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 font-medium text-gray-800">
                            {{ $item->keterangan }}
                        </td>
                        <td class="px-4 py-3 text-right font-bold text-gray-700">
                            Rp{{ number_format($item->jumlah, 0, ',', '.') }}
                        </td>
                        <td class="px-4 py-3 text-center">
                            <div class="flex justify-center gap-2">
                                {{-- TOMBOL EDIT (Trigger Modal) --}}
                                <button onclick='editNeraca(@json($item))' class="text-amber-500 hover:text-amber-600 transition" title="Edit">
                                    <i class="fas fa-pencil-alt"></i>
                                </button>
                                
                                {{-- TOMBOL HAPUS --}}
                                <form action="{{ route('admin.keuangan.destroy', $item->id) }}" method="POST" onsubmit="return confirm('Hapus data neraca ini?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-500 hover:text-red-700 transition" title="Hapus">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-6 py-8 text-center text-gray-400 italic">
                            Belum ada inputan data neraca manual. <br>
                            Klik tombol <b>"Input Data Neraca"</b> di atas untuk menambah.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

<div id="modalNeraca" class="fixed inset-0 z-[100] hidden overflow-y-auto">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-900 bg-opacity-60 transition-opacity backdrop-blur-sm" onclick="closeModal('modalNeraca')"></div>
        <div class="inline-block align-bottom bg-white rounded-xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg w-full">
            
            <form action="{{ route('admin.keuangan.store') }}" method="POST" id="formNeraca">
                @csrf
                {{-- ID HIDDEN: Jika ada isinya = Update, Jika kosong = Create --}}
                <input type="hidden" name="id" id="edit_id">

                <div class="bg-white px-4 pt-5 pb-4 sm:p-6">
                    <div class="flex justify-between items-center mb-5 border-b pb-3">
                        <h3 class="text-lg leading-6 font-bold text-gray-900 flex items-center" id="modalTitle">
                            <i class="fas fa-balance-scale text-emerald-600 me-2"></i> Input Pos Neraca
                        </h3>
                        <button type="button" onclick="closeModal('modalNeraca')" class="text-gray-400 hover:text-gray-500">
                            <i class="fas fa-times text-xl"></i>
                        </button>
                    </div>

                    <div class="space-y-4">
                        {{-- Tanggal --}}
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Tanggal</label>
                            <input type="date" name="tanggal" id="edit_tanggal" value="{{ date('Y-m-d') }}" class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-emerald-500 text-sm" required>
                        </div>

                        {{-- Kategori (Sama seperti sebelumnya) --}}
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Posisi Neraca</label>
                            <select id="edit_kategori" name="kategori" class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-emerald-500 text-sm bg-gray-50 font-medium" onchange="adjustNeracaType(this)" required>
                                <option value="" disabled selected>-- Pilih Posisi --</option>
                                <optgroup label="Aset (Harta)">
                                    <option value="Aset Tetap" data-jenis="Pengeluaran" data-desc="Pembelian Tanah, Gedung, Kendaraan">Aset Tetap</option>
                                    <option value="Investasi" data-jenis="Pengeluaran" data-desc="Investasi Jangka Panjang">Investasi</option>
                                </optgroup>
                                <optgroup label="Kewajiban (Hutang)">
                                    <option value="Hutang Bank" data-jenis="Pemasukan" data-desc="Pencairan Pinjaman Bank">Hutang Bank</option>
                                    <option value="Hutang Usaha" data-jenis="Pemasukan" data-desc="Hutang ke Supplier">Hutang Usaha</option>
                                </optgroup>
                                <optgroup label="Ekuitas (Modal)">
                                    <option value="Modal Disetor" data-jenis="Pemasukan" data-desc="Setoran Modal Awal Owner">Modal Disetor</option>
                                    <option value="Prive" data-jenis="Pengeluaran" data-desc="Penarikan Uang Owner">Prive (Tarik Modal)</option>
                                </optgroup>
                            </select>
                        </div>

                        <input type="hidden" name="jenis" id="auto_jenis">
                        
                        {{-- Keterangan --}}
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Keterangan</label>
                            <input type="text" name="keterangan" id="edit_keterangan" class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-emerald-500 text-sm" required>
                        </div>

                        {{-- Jumlah --}}
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Nilai (Rp)</label>
                            <input type="number" name="jumlah" id="edit_jumlah" class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-emerald-500 text-lg font-bold text-gray-800" required>
                        </div>
                    </div>
                </div>

                <div class="bg-gray-50 px-4 py-3 sm:px-6 flex flex-row-reverse gap-2">
                    <button type="submit" class="w-full inline-flex justify-center rounded-lg border border-transparent shadow-sm px-4 py-2 bg-emerald-600 text-base font-medium text-white hover:bg-emerald-700 sm:text-sm">
                        Simpan Data
                    </button>
                    <button type="button" onclick="closeModal('modalNeraca')" class="w-full inline-flex justify-center rounded-lg border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 sm:text-sm">
                        Batal
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // 1. Logic Create Baru (Reset Form)
    function openModal(id) {
        // Reset Form
        document.getElementById('formNeraca').reset();
        document.getElementById('edit_id').value = ''; // Kosongkan ID agar jadi CREATE
        document.getElementById('modalTitle').innerHTML = '<i class="fas fa-balance-scale text-emerald-600 me-2"></i> Input Pos Neraca';
        
        // Tampilkan Modal
        document.getElementById(id).classList.remove('hidden');
    }

    // 2. Logic Edit Data (Populate Form)
    function editNeraca(data) {
        // Isi Form dengan data dari database
        document.getElementById('edit_id').value = data.id; // Isi ID agar jadi UPDATE
        document.getElementById('edit_tanggal').value = data.tanggal;
        document.getElementById('edit_kategori').value = data.kategori;
        document.getElementById('edit_keterangan').value = data.keterangan;
        document.getElementById('edit_jumlah').value = data.jumlah;
        document.getElementById('auto_jenis').value = data.jenis;

        // Ubah Judul Modal
        document.getElementById('modalTitle').innerHTML = '<i class="fas fa-edit text-amber-500 me-2"></i> Edit Data Neraca';

        // Tampilkan Modal
        document.getElementById('modalNeraca').classList.remove('hidden');
    }

    // 3. Logic Auto Jenis (Sama seperti sebelumnya)
    function adjustNeracaType(select) {
        const selectedOption = select.options[select.selectedIndex];
        const jenis = selectedOption.getAttribute('data-jenis');
        document.getElementById('auto_jenis').value = jenis;
    }
</script>

@endsection