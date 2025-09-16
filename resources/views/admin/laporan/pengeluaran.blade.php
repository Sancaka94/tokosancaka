@extends('layouts.admin')

@section('content')
<div class="bg-gray-800 p-6 rounded-lg shadow-lg">
    <div class="flex justify-between items-center mb-6 border-b border-gray-700 pb-4">
        <h1 class="text-3xl font-bold text-white">Laporan Pengeluaran</h1>
    </div>

    @if (session('success'))
        <div class="bg-green-500 text-white p-4 rounded-lg mb-6">
            {{ session('success') }}
        </div>
    @endif
    @if ($errors->any())
        <div class="bg-red-500 text-white p-4 rounded-lg mb-6">
            <strong class="font-bold">Oops! Terjadi beberapa kesalahan:</strong>
            <ul class="mt-2 list-disc list-inside">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Total Pengeluaran -->
    <div class="bg-gray-900 p-6 rounded-lg mb-6">
        <h2 class="text-lg font-semibold text-gray-400 mb-2">Total Seluruh Pengeluaran</h2>
        <p class="text-4xl font-bold text-red-400">Rp {{ number_format($totalPengeluaran / 100, 2, ',', '.') }}</p>
    </div>

    <!-- Tambah Pengeluaran Manual -->
    <div class="bg-gray-900 p-6 rounded-lg mb-8">
        <h2 class="text-xl font-semibold text-white mb-4">Tambah Pengeluaran Manual</h2>
        <form action="{{ route('admin.laporan.pengeluaran.store') }}" method="POST">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Deskripsi -->
                <div>
                    <label for="deskripsi" class="block text-sm font-medium text-gray-300 mb-1">Deskripsi</label>
                    <input type="text" id="deskripsi" name="deskripsi" class="w-full bg-gray-800 text-white rounded-lg border-gray-600 focus:border-indigo-500 focus:ring-indigo-500" placeholder="Contoh: Pembelian bahan bakar" required>
                </div>

                <!-- Kategori Pengeluaran -->
                <div>
                    <label for="coa_id" class="block text-sm font-medium text-gray-300 mb-1">Kategori Pengeluaran</label>
                    <select id="coa_id" name="coa_id" class="w-full bg-gray-800 text-white rounded-lg border-gray-600 focus:border-indigo-500 focus:ring-indigo-500" required>
                        <option value="">-- Pilih Kategori --</option>
                        {{-- PERBAIKAN: Menggunakan variabel $expenseCoas --}}
                        @foreach($expenseCoas as $coa)
                            <option value="{{ $coa->id }}">{{ $coa->kode }} - {{ $coa->nama }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Jumlah (Rp) -->
                <div>
                    <label for="jumlah" class="block text-sm font-medium text-gray-300 mb-1">Jumlah (Rp)</label>
                    <input type="number" id="jumlah" name="jumlah" class="w-full bg-gray-800 text-white rounded-lg border-gray-600 focus:border-indigo-500 focus:ring-indigo-500" placeholder="50000" required min="1">
                </div>
            </div>
            <div class="mt-6 text-right">
                <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded-lg">
                    <i class="fa-solid fa-save mr-2"></i> Simpan Pengeluaran
                </button>
            </div>
        </form>
    </div>

    <!-- Riwayat Pengeluaran -->
    <div class="bg-gray-900 p-6 rounded-lg">
        <h2 class="text-xl font-semibold text-white mb-4">Riwayat Pengeluaran</h2>
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left text-gray-400">
                <thead class="text-xs text-gray-300 uppercase bg-gray-700">
                    <tr>
                        <th scope="col" class="px-6 py-3">Tanggal</th>
                        <th scope="col" class="px-6 py-3">Deskripsi</th>
                        <th scope="col" class="px-6 py-3">Akun</th>
                        <th scope="col" class="px-6 py-3 text-right">Jumlah (Debit)</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($transactions as $transaction)
                        <tr class="bg-gray-800 border-b border-gray-700">
                            <td class="px-6 py-4">{{ $transaction->journal->created_at->format('d M Y, H:i') }}</td>
                            <td class="px-6 py-4">{{ $transaction->description ?? '-' }}</td>
                            <td class="px-6 py-4">{{ $transaction->coa->kode ?? '' }} - {{ $transaction->coa->nama ?? 'N/A' }}</td>
                            <td class="px-6 py-4 text-right text-red-400 font-mono">Rp {{ number_format($transaction->debit/100, 2, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center py-4">Belum ada data pengeluaran yang tercatat.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">
            {{ $transactions->links() }}
        </div>
    </div>
</div>
@endsection

