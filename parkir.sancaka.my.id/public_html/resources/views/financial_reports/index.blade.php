@extends('layouts.app')

@section('content')
<div class="py-4 md:py-8">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">

        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
            <div>
                <h2 class="text-xl md:text-2xl font-bold leading-tight text-gray-800">Buku Kas (Laporan Manual)</h2>
                <p class="mt-1 text-xs md:text-sm text-gray-600">Pencatatan pemasukan dan pengeluaran di luar sistem tiket otomatis.</p>
            </div>

            <div class="flex flex-wrap items-center gap-2 w-full sm:w-auto">
                <a href="{{ route('financial.export.pdf', request()->all()) }}" class="bg-red-500 hover:bg-red-600 text-white font-bold py-2 px-4 rounded-lg shadow-sm transition-colors text-sm flex items-center justify-center gap-2">
                    📄 PDF
                </a>

                <a href="{{ route('financial.export.excel', request()->all()) }}" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded-lg shadow-sm transition-colors text-sm flex items-center justify-center gap-2">
                    📊 Excel
                </a>

                <button type="button" onclick="document.getElementById('modalTambahKas').classList.remove('hidden')" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg shadow-md transition-colors text-sm flex items-center justify-center gap-2">
                    <span>+</span> Tambah Kas
                </button>
            </div>
        </div>


        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-8">
            <div class="bg-white rounded-lg shadow-sm p-4 md:p-5 border-l-4 border-green-500 flex flex-col justify-center">
                <p class="text-xs md:text-sm text-gray-500 font-semibold uppercase tracking-wider">Total Semua Pemasukan</p>
                <p class="text-xl md:text-2xl font-bold text-green-600 mt-1">Rp {{ number_format($totalPemasukan, 0, ',', '.') }}</p>
            </div>
            <div class="bg-white rounded-lg shadow-sm p-4 md:p-5 border-l-4 border-red-500 flex flex-col justify-center">
                <p class="text-xs md:text-sm text-gray-500 font-semibold uppercase tracking-wider">Total Semua Pengeluaran</p>
                <p class="text-xl md:text-2xl font-bold text-red-600 mt-1">Rp {{ number_format($totalPengeluaran, 0, ',', '.') }}</p>
            </div>
            <div class="bg-white rounded-lg shadow-sm p-4 md:p-5 border-l-4 border-blue-500 flex flex-col justify-center">
                <p class="text-xs md:text-sm text-gray-500 font-semibold uppercase tracking-wider">Saldo Kas Akhir</p>
                <p class="text-xl md:text-2xl font-bold text-blue-600 mt-1">Rp {{ number_format($saldo, 0, ',', '.') }}</p>
            </div>
        </div>

        <div class="mb-8">
            <h3 class="text-lg font-bold text-gray-800 mb-4 border-b pb-2 flex items-center gap-2">
                <span class="text-green-500">📈</span> Statistik Pemasukan
            </h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">

                <div class="bg-white rounded-lg shadow-sm p-4 border border-gray-100">
                    <p class="text-xs text-gray-500 font-semibold uppercase">1. Hari Ini</p>
                    <h4 class="text-xl font-bold text-green-600 mt-1">Rp {{ number_format($pemasukanHariIni, 0, ',', '.') }}</h4>
                    <div class="mt-2 flex items-center text-xs">
                        @if($selisihMasukHari['rp'] >= 0)
                            <svg class="w-3 h-3 text-green-500 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"></path></svg>
                            <span class="text-green-600 font-bold">Naik {{ number_format(abs($selisihMasukHari['persen']), 1, ',', '.') }}%</span>
                            <span class="text-green-600 ml-1">(+Rp {{ number_format(abs($selisihMasukHari['rp']), 0, ',', '.') }})</span>
                        @else
                            <svg class="w-3 h-3 text-red-500 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path></svg>
                            <span class="text-red-600 font-bold">Turun {{ number_format(abs($selisihMasukHari['persen']), 1, ',', '.') }}%</span>
                            <span class="text-red-600 ml-1">(-Rp {{ number_format(abs($selisihMasukHari['rp']), 0, ',', '.') }})</span>
                        @endif
                        <span class="text-gray-400 ml-1">vs Kemarin</span>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-sm p-4 border border-gray-100">
                    <p class="text-xs text-gray-500 font-semibold uppercase">2. Bulan Ini</p>
                    <h4 class="text-xl font-bold text-green-600 mt-1">Rp {{ number_format($pemasukanBulanIni, 0, ',', '.') }}</h4>
                    <div class="mt-2 flex items-center text-xs flex-wrap">
                        @if($selisihMasukBulan['rp'] >= 0)
                            <svg class="w-3 h-3 text-green-500 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"></path></svg>
                            <span class="text-green-600 font-bold">Naik {{ number_format(abs($selisihMasukBulan['persen']), 1, ',', '.') }}%</span>
                            <span class="text-green-600 ml-1">(+Rp {{ number_format(abs($selisihMasukBulan['rp']), 0, ',', '.') }})</span>
                        @else
                            <svg class="w-3 h-3 text-red-500 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path></svg>
                            <span class="text-red-600 font-bold">Turun {{ number_format(abs($selisihMasukBulan['persen']), 1, ',', '.') }}%</span>
                            <span class="text-red-600 ml-1">(-Rp {{ number_format(abs($selisihMasukBulan['rp']), 0, ',', '.') }})</span>
                        @endif
                        <span class="text-gray-400 ml-1">vs Bln Lalu</span>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-sm p-4 border border-gray-100">
                    <p class="text-xs text-gray-500 font-semibold uppercase">3. Bulan Kemarin</p>
                    <h4 class="text-xl font-bold text-gray-700 mt-1">Rp {{ number_format($pemasukanBulanLalu, 0, ',', '.') }}</h4>
                </div>

                <div class="bg-white rounded-lg shadow-sm p-4 border border-gray-100">
                    <p class="text-xs text-gray-500 font-semibold uppercase">4. Rata-Rata Bulan Ini</p>
                    <h4 class="text-xl font-bold text-gray-700 mt-1">Rp {{ number_format($rataPemasukanBulanIni, 0, ',', '.') }} <span class="text-xs font-normal">/ hari</span></h4>
                    <div class="mt-2 flex items-center text-xs">
                        @if($selisihMasukRata['rp'] >= 0)
                            <svg class="w-3 h-3 text-green-500 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"></path></svg>
                            <span class="text-green-600 font-bold">+ {{ number_format(abs($selisihMasukRata['persen']), 1, ',', '.') }}%</span>
                            <span class="text-green-600 ml-1">(+Rp {{ number_format(abs($selisihMasukRata['rp']), 0, ',', '.') }})</span>
                        @else
                            <svg class="w-3 h-3 text-red-500 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path></svg>
                            <span class="text-red-600 font-bold">- {{ number_format(abs($selisihMasukRata['persen']), 1, ',', '.') }}%</span>
                            <span class="text-red-600 ml-1">(-Rp {{ number_format(abs($selisihMasukRata['rp']), 0, ',', '.') }})</span>
                        @endif
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-sm p-4 border border-gray-100">
                    <p class="text-xs text-gray-500 font-semibold uppercase">5. Rata-Rata Bulan Kemarin</p>
                    <h4 class="text-xl font-bold text-gray-700 mt-1">Rp {{ number_format($rataPemasukanBulanLalu, 0, ',', '.') }} <span class="text-xs font-normal">/ hari</span></h4>
                </div>

                <div class="bg-green-50 rounded-lg shadow-sm p-4 border border-green-200">
                    <p class="text-xs text-green-700 font-bold uppercase tracking-wider">6. Total 1 Tahun (Jan-Des)</p>
                    <h4 class="text-xl font-bold text-green-700 mt-1">Rp {{ number_format($pemasukanTahunIni, 0, ',', '.') }}</h4>
                </div>
            </div>
        </div>

        <div class="mb-8">
            <h3 class="text-lg font-bold text-gray-800 mb-4 border-b pb-2 flex items-center gap-2">
                <span class="text-red-500">📉</span> Statistik Pengeluaran
            </h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">

                <div class="bg-white rounded-lg shadow-sm p-4 border border-gray-100">
                    <p class="text-xs text-gray-500 font-semibold uppercase">1. Hari Ini</p>
                    <h4 class="text-xl font-bold text-red-600 mt-1">Rp {{ number_format($pengeluaranHariIni, 0, ',', '.') }}</h4>
                    <div class="mt-2 flex items-center text-xs">
                        {{-- Logika Pengeluaran Dibalik: Naik = Merah (Buruk), Turun = Hijau (Baik) --}}
                        @if($selisihKeluarHari['rp'] > 0)
                            <svg class="w-3 h-3 text-red-500 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"></path></svg>
                            <span class="text-red-600 font-bold">Naik {{ number_format(abs($selisihKeluarHari['persen']), 1, ',', '.') }}%</span>
                            <span class="text-red-600 ml-1">(+Rp {{ number_format(abs($selisihKeluarHari['rp']), 0, ',', '.') }})</span>
                        @elseif($selisihKeluarHari['rp'] < 0)
                            <svg class="w-3 h-3 text-green-500 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path></svg>
                            <span class="text-green-600 font-bold">Turun {{ number_format(abs($selisihKeluarHari['persen']), 1, ',', '.') }}%</span>
                            <span class="text-green-600 ml-1">(-Rp {{ number_format(abs($selisihKeluarHari['rp']), 0, ',', '.') }})</span>
                        @else
                            <span class="text-gray-500 font-bold">Sama dengan kemarin</span>
                        @endif
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-sm p-4 border border-gray-100">
                    <p class="text-xs text-gray-500 font-semibold uppercase">2. Bulan Ini</p>
                    <h4 class="text-xl font-bold text-red-600 mt-1">Rp {{ number_format($pengeluaranBulanIni, 0, ',', '.') }}</h4>
                    <div class="mt-2 flex items-center text-xs flex-wrap">
                        @if($selisihKeluarBulan['rp'] > 0)
                            <svg class="w-3 h-3 text-red-500 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"></path></svg>
                            <span class="text-red-600 font-bold">Naik {{ number_format(abs($selisihKeluarBulan['persen']), 1, ',', '.') }}%</span>
                            <span class="text-red-600 ml-1">(+Rp {{ number_format(abs($selisihKeluarBulan['rp']), 0, ',', '.') }})</span>
                        @elseif($selisihKeluarBulan['rp'] < 0)
                            <svg class="w-3 h-3 text-green-500 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path></svg>
                            <span class="text-green-600 font-bold">Turun {{ number_format(abs($selisihKeluarBulan['persen']), 1, ',', '.') }}%</span>
                            <span class="text-green-600 ml-1">(-Rp {{ number_format(abs($selisihKeluarBulan['rp']), 0, ',', '.') }})</span>
                        @else
                            <span class="text-gray-500 font-bold">Sama dengan bln lalu</span>
                        @endif
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-sm p-4 border border-gray-100">
                    <p class="text-xs text-gray-500 font-semibold uppercase">3. Bulan Kemarin</p>
                    <h4 class="text-xl font-bold text-gray-700 mt-1">Rp {{ number_format($pengeluaranBulanLalu, 0, ',', '.') }}</h4>
                </div>

                <div class="bg-white rounded-lg shadow-sm p-4 border border-gray-100">
                    <p class="text-xs text-gray-500 font-semibold uppercase">4. Rata-Rata Bulan Ini</p>
                    <h4 class="text-xl font-bold text-gray-700 mt-1">Rp {{ number_format($rataPengeluaranBulanIni, 0, ',', '.') }} <span class="text-xs font-normal">/ hari</span></h4>
                    <div class="mt-2 flex items-center text-xs">
                        @if($selisihKeluarRata['rp'] > 0)
                            <svg class="w-3 h-3 text-red-500 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"></path></svg>
                            <span class="text-red-600 font-bold">+ {{ number_format(abs($selisihKeluarRata['persen']), 1, ',', '.') }}%</span>
                            <span class="text-red-600 ml-1">(+Rp {{ number_format(abs($selisihKeluarRata['rp']), 0, ',', '.') }})</span>
                        @elseif($selisihKeluarRata['rp'] < 0)
                            <svg class="w-3 h-3 text-green-500 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path></svg>
                            <span class="text-green-600 font-bold">- {{ number_format(abs($selisihKeluarRata['persen']), 1, ',', '.') }}%</span>
                            <span class="text-green-600 ml-1">(-Rp {{ number_format(abs($selisihKeluarRata['rp']), 0, ',', '.') }})</span>
                        @endif
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-sm p-4 border border-gray-100">
                    <p class="text-xs text-gray-500 font-semibold uppercase">5. Rata-Rata Bulan Kemarin</p>
                    <h4 class="text-xl font-bold text-gray-700 mt-1">Rp {{ number_format($rataPengeluaranBulanLalu, 0, ',', '.') }} <span class="text-xs font-normal">/ hari</span></h4>
                </div>
            </div>
        </div>

        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-gray-100">
            <div class="block w-full overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 md:px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider whitespace-nowrap">Tanggal</th>
                            <th class="px-4 md:px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider whitespace-nowrap">Kategori</th>
                            <th class="px-4 md:px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider whitespace-nowrap">Keterangan</th>
                            <th class="px-4 md:px-6 py-3 text-right text-xs font-bold text-gray-500 uppercase tracking-wider whitespace-nowrap">Pemasukan</th>
                            <th class="px-4 md:px-6 py-3 text-right text-xs font-bold text-gray-500 uppercase tracking-wider whitespace-nowrap">Pengeluaran</th>
                            <th class="px-4 md:px-6 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider whitespace-nowrap">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($reports as $row)
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-4 md:px-6 py-3 md:py-4 whitespace-nowrap text-xs md:text-sm font-medium text-gray-900">
                                    {{ \Carbon\Carbon::parse($row->tanggal)->format('d/m/Y') }}
                                </td>
                                <td class="px-4 md:px-6 py-3 md:py-4 whitespace-nowrap text-xs md:text-sm text-gray-600">{{ $row->kategori }}</td>
                                <td class="px-4 md:px-6 py-3 md:py-4 text-xs md:text-sm text-gray-600 min-w-[150px]">{{ $row->keterangan ?? '-' }}</td>

                                @if($row->jenis == 'pemasukan')
                                    <td class="px-4 md:px-6 py-3 md:py-4 whitespace-nowrap text-xs md:text-sm text-green-600 text-right font-bold">Rp {{ number_format($row->nominal, 0, ',', '.') }}</td>
                                    <td class="px-4 md:px-6 py-3 md:py-4 whitespace-nowrap text-xs md:text-sm text-gray-400 text-right font-medium">-</td>
                                @else
                                    <td class="px-4 md:px-6 py-3 md:py-4 whitespace-nowrap text-xs md:text-sm text-gray-400 text-right font-medium">-</td>
                                    <td class="px-4 md:px-6 py-3 md:py-4 whitespace-nowrap text-xs md:text-sm text-red-600 text-right font-bold">Rp {{ number_format($row->nominal, 0, ',', '.') }}</td>
                                @endif

                                <td class="px-4 md:px-6 py-3 md:py-4 whitespace-nowrap text-center align-middle">
                                    <div class="flex items-center justify-center gap-2 md:gap-3">
                                        <a href="{{ route('financial.edit', $row->id) }}" class="text-blue-500 hover:text-blue-700 font-semibold text-xs md:text-sm transition-colors bg-blue-50 hover:bg-blue-100 px-2 md:px-3 py-1 md:py-1.5 rounded">
                                            Edit
                                        </a>
                                        <form action="{{ route('financial.destroy', $row->id) }}" method="POST" onsubmit="return confirm('Yakin ingin menghapus data kas ini?');" class="inline-block">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-500 hover:text-red-700 font-semibold text-xs md:text-sm transition-colors bg-red-50 hover:bg-red-100 px-2 md:px-3 py-1 md:py-1.5 rounded">
                                                Hapus
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 md:px-6 py-8 text-center text-sm text-gray-500 italic">Belum ada catatan keuangan manual.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if(isset($reports) && method_exists($reports, 'links') && $reports->hasPages())
                <div class="px-4 md:px-6 py-3 border-t border-gray-200 bg-gray-50">
                    {{ $reports->links() }}
                </div>
            @endif
        </div>
    </div>
</div>

<div id="modalTambahKas" class="fixed inset-0 z-[60] hidden overflow-y-auto" role="dialog">
    <div class="flex items-end justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-900 bg-opacity-50 backdrop-blur-sm" onclick="document.getElementById('modalTambahKas').classList.add('hidden')"></div>

        <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>

        <div class="inline-block overflow-hidden text-left align-bottom transition-all transform bg-white rounded-xl shadow-2xl sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full border border-gray-100">
            <form action="{{ route('financial.store') }}" method="POST">
                @csrf
                <div class="px-4 pt-5 pb-4 bg-white sm:p-6">
                    <h3 class="text-lg font-bold text-gray-900 mb-4 border-b pb-2">Catat Pemasukan / Pengeluaran & Gaji</h3>

                    <div id="transactions-wrapper">

                        <div class="transaction-block bg-white border border-gray-200 p-4 rounded-lg mb-4 relative">
                            <div class="flex justify-between items-center mb-4 border-b pb-2">
                                <h4 class="text-md font-bold text-blue-700 block-title">Transaksi #1</h4>
                                <button type="button" class="text-red-500 hover:text-red-700 font-bold text-sm hidden btn-remove" onclick="this.closest('.transaction-block').remove()">Hapus Form Ini</button>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700">Tanggal</label>
                                    <input type="date" name="transactions[0][tanggal]" value="{{ date('Y-m-d') }}" required class="mt-1 block w-full border border-gray-300 rounded-md py-2 px-3 sm:text-sm">
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700">Jenis Transaksi Utama</label>
                                    <select name="transactions[0][jenis]" required class="mt-1 block w-full border border-gray-300 rounded-md py-2 px-3 sm:text-sm">
                                        <option value="pemasukan">Setoran Parkir (Pemasukan)</option>
                                        <option value="pengeluaran">Biaya Operasional (Pengeluaran)</option>
                                    </select>
                                </div>
                            </div>

                            <div class="bg-blue-50 p-4 rounded-lg mb-6">
                                <h4 class="text-sm font-bold text-blue-800 mb-3 flex items-center gap-2">
                                    <span>💰</span> Input Gaji Pegawai
                                </h4>
                                <div class="space-y-3">
                                    @foreach($employees as $emp)
                                    <div class="flex items-center justify-between bg-white p-3 rounded-md shadow-sm border border-blue-100">
                                        <div class="flex flex-col">
                                            <span class="text-sm font-bold text-gray-800">{{ $emp->name }}</span>
                                            <span class="text-[10px] text-blue-600 uppercase font-bold">
                                                {{ $emp->salary_type == 'percentage' ? $emp->salary_amount . '%' : 'Rp ' . number_format($emp->salary_amount, 0, ',', '.') }}
                                            </span>
                                        </div>
                                        <div class="w-32 md:w-48">
                                            <div class="relative">
                                                <span class="absolute inset-y-0 left-0 flex items-center pl-2 text-xs font-bold text-gray-400">Rp</span>
                                                <input type="number"
                                                       name="transactions[0][salaries][{{ $emp->id }}]"
                                                       placeholder="Besaran Gaji"
                                                       class="w-full pl-7 pr-3 py-1.5 border border-gray-300 rounded text-sm focus:ring-blue-500"
                                                       value="{{ $emp->salary_type == 'nominal' ? $emp->salary_amount : '' }}">
                                            </div>
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                            </div>

                            <div class="space-y-4">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700">Kategori Utama</label>
                                        <select name="transactions[0][kategori]" required class="mt-1 block w-full border border-gray-300 rounded-md py-2 px-3 sm:text-sm">
                                            <option value="" disabled selected>-- Pilih Kategori --</option>
                                            <option value="Parkiran">Parkiran</option>
                                            <option value="Toilet">Toilet</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700">Nominal Transaksi (Rp)</label>
                                        <input type="number" name="transactions[0][nominal]" required min="1" placeholder="Contoh: 500000" class="mt-1 block w-full border border-gray-300 rounded-md py-2 px-3 sm:text-sm">
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-sm font-semibold text-gray-700">Keterangan (Opsional)</label>
                                    <textarea name="transactions[0][keterangan]" rows="2" placeholder="Catatan tambahan..." class="mt-1 block w-full border border-gray-300 rounded-md py-2 px-3 sm:text-sm"></textarea>
                                </div>
                            </div>
                        </div>

                    </div>

                    <button type="button" onclick="tambahFormTransaksi()" class="w-full bg-green-50 hover:bg-green-100 text-green-700 border border-green-200 font-bold py-2 px-4 rounded-lg shadow-sm transition-colors text-sm flex items-center justify-center gap-2 mb-2">
                        <span>+</span> Tambah Hari / Transaksi Lain
                    </button>

                </div>

                <div class="px-4 py-3 bg-gray-50 sm:px-6 sm:flex sm:flex-row-reverse border-t">
                    <button type="submit" class="w-full inline-flex justify-center rounded-md bg-blue-600 px-4 py-2 text-base font-bold text-white hover:bg-blue-700 sm:ml-3 sm:w-auto sm:text-sm">
                        Simpan Semua Data
                    </button>
                    <button type="button" onclick="document.getElementById('modalTambahKas').classList.add('hidden')" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-base font-bold text-gray-700 hover:bg-gray-100 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                        Batal
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    let trxIndex = 1;

    function tambahFormTransaksi() {
        const wrapper = document.getElementById('transactions-wrapper');
        const firstBlock = wrapper.querySelector('.transaction-block');

        // Clone elemen HTML
        const newBlock = firstBlock.cloneNode(true);

        // Update Judul Transaksi
        newBlock.querySelector('.block-title').innerText = 'Transaksi #' + (trxIndex + 1);

        // Tampilkan tombol "Hapus Form Ini" untuk form tambahan
        newBlock.querySelector('.btn-remove').classList.remove('hidden');

        // Update atribut name agar index array bertambah (transactions[0] -> transactions[1])
        // UBAHAN DI SINI: Tambahkan `textarea` dalam query selector
        const inputs = newBlock.querySelectorAll('input, select, textarea');

        inputs.forEach(input => {
            if (input.name) {
                // Regex untuk mencari transactions[0] dan menggantinya dengan index baru
                input.name = input.name.replace(/transactions\[0\]/g, `transactions[${trxIndex}]`);
            }

            // Kosongkan dropdown Kategori kembali ke opsi default
            if (input.name && input.name.includes('kategori')) {
                input.value = '';
            }
            // Kosongkan nominal
            if (input.name && input.name.includes('nominal')) {
                input.value = '';
            }
            // Kosongkan keterangan
            if (input.name && input.name.includes('keterangan')) {
                input.value = '';
            }
            // Note: input tanggal dan gaji sengaja dibiarkan ter-copy sebagai default
        });

        // Masukkan form baru ke dalam wrapper
        wrapper.appendChild(newBlock);

        trxIndex++;
    }
</script>

@endsection
