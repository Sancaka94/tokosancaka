@extends('pondok.admin.layouts.app')

@section('title', 'Keuangan Pondok')
@section('page_title', 'Transaksi Kas & Bank')

@section('content')
<div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
    <div class="p-6 text-gray-900">
        
        <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
            <div>
                <h3 class="text-lg font-bold text-gray-800">Riwayat Transaksi Keuangan</h3>
                <p class="text-sm text-gray-500">Mencatat pemasukan dan pengeluaran operasional.</p>
            </div>
            
            <a href="{{ route('admin.transaksi-kas-bank.create') }}" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded shadow transition flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Catat Transaksi Baru
            </a>
        </div>

        @if(session('success'))
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 shadow-sm flex justify-between items-center">
                <div>
                    <p class="font-bold">Berhasil!</p>
                    <p class="text-sm">{{ session('success') }}</p>
                </div>
                <button onclick="this.parentElement.style.display='none'" class="text-green-700 hover:text-green-900 font-bold">&times;</button>
            </div>
        @endif

        <div class="overflow-x-auto border border-gray-200 rounded-lg">
            <table class="min-w-full bg-white">
                <thead class="bg-gray-100 border-b border-gray-200">
                    <tr>
                        <th class="py-3 px-4 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Tanggal</th>
                        <th class="py-3 px-4 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Akun Keuangan</th>
                        <th class="py-3 px-4 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Deskripsi / Keterangan</th>
                        <th class="py-3 px-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider">Jenis</th>
                        <th class="py-3 px-4 text-right text-xs font-bold text-gray-600 uppercase tracking-wider">Nominal (Rp)</th>
                        <th class="py-3 px-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($transaksi as $t)
                    <tr class="hover:bg-gray-50 transition duration-150">
                        
                        <td class="py-3 px-4 text-sm text-gray-600 whitespace-nowrap">
                            {{ \Carbon\Carbon::parse($t->tanggal_transaksi)->format('d M Y') }}
                        </td>

                        <td class="py-3 px-4 text-sm font-semibold text-gray-800">
                            {{ $t->nama_akun ?? 'Akun Terhapus' }}
                        </td>

                        <td class="py-3 px-4 text-sm text-gray-600">
                            <div class="font-medium text-gray-900">{{ $t->deskripsi }}</div>
                            @if($t->keterangan)
                                <div class="text-xs text-gray-400 italic">{{ Str::limit($t->keterangan, 30) }}</div>
                            @endif
                        </td>

                        <td class="py-3 px-4 text-center">
                            @if($t->jenis_transaksi == 'Masuk')
                                <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800 border border-green-200">
                                    Pemasukan
                                </span>
                            @else
                                <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800 border border-red-200">
                                    Pengeluaran
                                </span>
                            @endif
                        </td>

                        <td class="py-3 px-4 text-right font-mono text-sm font-bold {{ $t->jenis_transaksi == 'Masuk' ? 'text-green-600' : 'text-red-600' }}">
                            {{ $t->jenis_transaksi == 'Masuk' ? '+' : '-' }} 
                            Rp {{ number_format($t->jumlah, 0, ',', '.') }}
                        </td>

                        <td class="py-3 px-4 text-center">
                            <div class="flex item-center justify-center space-x-2">
                                {{-- <a href="{{ route('admin.transaksi-kas-bank.edit', $t->id) }}" class="text-yellow-500 hover:text-yellow-700">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                </a> --}}

                                <form action="{{ route('admin.transaksi-kas-bank.destroy', $t->id) }}" method="POST" onsubmit="return confirm('Yakin ingin menghapus transaksi ini? Saldo akan berubah kembali.');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-500 hover:text-red-700" title="Hapus">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="py-8 text-center text-gray-500 flex flex-col items-center justify-center">
                            <svg class="w-12 h-12 text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                            <span class="font-medium">Belum ada data transaksi.</span>
                            <span class="text-xs mt-1">Silakan tambah transaksi pemasukan atau pengeluaran.</span>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-6">
            {{ $transaksi->links() }}
        </div>
    </div>
</div>
@endsection