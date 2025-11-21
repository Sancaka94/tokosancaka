@extends('layouts.admin')

@section('content')
<div class="bg-gray-800 p-6 rounded-lg shadow-lg">
    <h1 class="text-3xl font-bold text-white mb-6 border-b border-gray-700 pb-4">Laporan Pemasukan</h1>

    <!-- Notifikasi -->
    @if(session('success'))
        <div class="bg-green-500 text-white p-4 rounded-lg mb-6">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="bg-red-500 text-white p-4 rounded-lg mb-6">
            {{ session('error') }}
        </div>
    @endif

    <!-- Ringkasan Total Pemasukan -->
    <div class="bg-gray-900 p-6 rounded-lg mb-8">
        <h2 class="text-xl font-semibold text-gray-300 mb-2">Total Seluruh Pemasukan</h2>
        <p class="text-4xl font-extrabold text-green-400">Rp {{ number_format($totalPemasukan, 2, ',', '.') }}</p>
    </div>

    <!-- Form Input Pemasukan Manual -->
    <div class="bg-gray-700 p-6 rounded-lg mb-8">
        <h2 class="text-2xl font-bold text-white mb-4">Tambah Pemasukan Manual</h2>
        <form action="{{ route('admin.laporan.pemasukan.store') }}" method="POST">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Deskripsi -->
                <div>
                    <label for="deskripsi" class="block text-sm font-medium text-gray-300 mb-1">Deskripsi</label>
                    <input type="text" name="deskripsi" id="deskripsi" class="w-full bg-gray-800 text-white rounded-lg border-gray-600 focus:ring-indigo-500 focus:border-indigo-500" placeholder="Contoh: Pendapatan jasa packing" required>
                </div>

                <!-- Kategori Pemasukan (COA) -->
                <div>
                    <label for="coa_id" class="block text-sm font-medium text-gray-300 mb-1">Kategori Pemasukan</label>
                    <select name="coa_id" id="coa_id" class="w-full bg-gray-800 text-white rounded-lg border-gray-600 focus:ring-indigo-500 focus:border-indigo-500" required>
                        <option value="">-- Pilih Kategori --</option>
                        @foreach($incomeCoas as $coa)
                            <option value="{{ $coa->id }}">{{ $coa->kode }} - {{ $coa->nama }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Jumlah -->
                <div>
                    <label for="jumlah" class="block text-sm font-medium text-gray-300 mb-1">Jumlah (Rp)</label>
                    <input type="number" name="jumlah" id="jumlah" class="w-full bg-gray-800 text-white rounded-lg border-gray-600 focus:ring-indigo-500 focus:border-indigo-500" placeholder="100000" required>
                </div>
            </div>
            <div class="mt-6 text-right">
                <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-6 rounded-lg transition duration-300">
                    Simpan Pemasukan
                </button>
            </div>
        </form>
    </div>

    <!-- Tabel Riwayat Pemasukan -->
    <div>
        <h2 class="text-2xl font-bold text-white mb-4">Riwayat Pemasukan</h2>
        <div class="overflow-x-auto bg-gray-700 rounded-lg">
            <table class="min-w-full divide-y divide-gray-600">
                <thead class="bg-gray-800">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Tanggal</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Deskripsi</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Akun</th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-300 uppercase tracking-wider">Jumlah (Kredit)</th>
                    </tr>
                </thead>
                <tbody class="bg-gray-700 divide-y divide-gray-600">
                    @forelse($transactions as $transaction)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-300">{{ $transaction->created_at->format('d M Y, H:i') }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-300">{{ $transaction->journal->memo }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-300">
                                <span class="bg-gray-800 px-2 py-1 rounded-full">{{ $transaction->coa->kode }} - {{ $transaction->coa->nama }}</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-green-400 font-semibold text-right">
                                Rp {{ number_format($transaction->credit, 2, ',', '.') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-4 text-center text-sm text-gray-400">
                                Belum ada data pemasukan yang tercatat.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <!-- Pagination -->
        <div class="mt-6">
            {{ $transactions->links() }}
        </div>
    </div>
</div>
@endsection

