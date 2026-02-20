@extends('layouts.app')

@section('title', 'Transaksi Kendaraan')

@section('content')
<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Operasional Transaksi Parkir</h1>
</div>

<div class="card mb-6 border-t-4 border-blue-600 shadow-md">
    <div class="card-header bg-white border-b-2 border-gray-100">
        <span class="font-bold text-gray-800 text-lg">Catat Kendaraan Masuk</span>
    </div>
    <div class="card-body bg-gray-50 rounded-b-lg">
        <form action="{{ route('transactions.store') }}" method="POST" class="flex flex-col md:flex-row gap-4 items-end">
            @csrf
            <div class="w-full md:w-1/3">
                <label class="block text-sm font-bold text-gray-700 mb-1">Jenis Kendaraan</label>
                <select name="vehicle_type" class="form-control bg-white shadow-sm" required>
                    <option value="motor">Motor</option>
                    <option value="mobil">Mobil</option>
                </select>
            </div>
            <div class="w-full md:w-1/3">
                <label class="block text-sm font-bold text-gray-700 mb-1">Nomor Plat (Tanpa Spasi / Dengan Spasi)</label>
                <input type="text" name="plate_number" class="form-control shadow-sm uppercase" placeholder="Contoh: AE 1234 XX" required autocomplete="off">
            </div>
            <div class="w-full md:w-1/3">
                <button type="submit" class="btn-primary w-full py-2.5 text-lg shadow-md font-bold flex justify-center items-center gap-2">
                    <span>Masuk & Cetak Karcis</span> &rarr;
                </button>
            </div>
        </form>
        @error('plate_number')
            <p class="text-red-500 text-xs mt-2 font-bold">{{ $message }}</p>
        @enderror
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-header bg-white border-b-2 border-blue-600 flex flex-col md:flex-row justify-between items-center gap-4">
        <span class="font-bold text-gray-800 uppercase tracking-wide text-sm whitespace-nowrap">Data & Riwayat Parkir</span>

        <form action="{{ route('transactions.index') }}" method="GET" class="flex flex-col md:flex-row gap-2 w-full md:w-auto">
            <input type="text" name="keyword" value="{{ request('keyword') }}" class="form-control text-sm py-1.5" placeholder="Cari Plat / No Karcis">
            <input type="date" name="tanggal" value="{{ request('tanggal') }}" class="form-control text-sm py-1.5">
            <button type="submit" class="bg-gray-800 hover:bg-gray-900 text-white px-4 py-1.5 rounded text-sm font-bold transition-colors shadow-sm whitespace-nowrap">
                Cari
            </button>
            @if(request('keyword') || request('tanggal'))
                <a href="{{ route('transactions.index') }}" class="bg-red-100 hover:bg-red-200 text-red-700 px-3 py-1.5 rounded text-sm font-bold transition-colors flex items-center justify-center">
                    Reset
                </a>
            @endif
        </form>
    </div>

    <div class="card-body p-0 overflow-x-auto">
        <table class="table-custom min-w-full">
            <thead class="bg-gray-100">
                <tr>
                    <th class="text-left text-xs font-bold text-gray-600 uppercase tracking-wider">No. Parkir / Plat</th>
                    <th class="text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Jenis</th>
                    <th class="text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Waktu Masuk</th>
                    <th class="text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Tarif / Status</th>
                    <th class="text-center text-xs font-bold text-gray-600 uppercase tracking-wider">Aksi</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($transactions as $trx)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td>
                            <div class="text-xs text-gray-500 font-bold mb-1">TRX-{{ str_pad($trx->id, 5, '0', STR_PAD_LEFT) }}</div>
                            <div class="font-black text-xl text-gray-800 tracking-widest">{{ $trx->plate_number }}</div>
                        </td>
                        <td class="capitalize text-gray-600 font-medium">{{ $trx->vehicle_type }}</td>
                        <td class="text-gray-600">
                            {{ $trx->entry_time->translatedFormat('d M Y') }} <br>
                            <span class="font-bold text-blue-600">{{ $trx->entry_time->translatedFormat('H:i') }} WIB</span>
                        </td>
                        <td>
                            @if($trx->status == 'masuk')
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-yellow-100 text-yellow-800 border border-yellow-200">
                                    <span class="w-2 h-2 mr-1.5 bg-yellow-500 rounded-full animate-pulse"></span>
                                    Sedang Parkir
                                </span>
                            @else
                                <span class="font-black text-green-600 text-lg">Rp {{ number_format($trx->fee, 0, ',', '.') }}</span>
                                <br><span class="text-xs text-gray-500 font-bold">Keluar: {{ $trx->exit_time->translatedFormat('H:i') }} WIB</span>
                            @endif
                        </td>
                        <td class="text-center whitespace-nowrap">
                            @if($trx->status == 'masuk')
                                <form action="{{ route('transactions.update', $trx->id) }}" method="POST" class="inline-block" onsubmit="return confirm('Selesaikan parkir dan catat kendaraan keluar?');">
                                    @csrf
                                    @method('PUT')
                                    <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded text-sm font-bold shadow-md transition-colors">
                                        Keluar &rarr;
                                    </button>
                                </form>
                            @else
                                <span class="text-gray-400 text-sm italic font-medium">Selesai</span>
                            @endif

                            @if(auth()->user()->role != 'operator')
                                <form action="{{ route('transactions.destroy', $trx->id) }}" method="POST" class="inline-block ml-3" onsubmit="return confirm('Yakin ingin menghapus riwayat ini? Data yang terhapus akan memengaruhi laporan keuangan.');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="bg-red-100 hover:bg-red-200 text-red-700 px-3 py-1.5 rounded text-xs font-bold transition-colors">
                                        Hapus
                                    </button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-10 text-center text-gray-500 italic">
                            Data transaksi kendaraan tidak ditemukan.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($transactions->hasPages())
        <div class="px-4 py-3 border-t border-gray-200 bg-gray-50 rounded-b-lg">
            {{ $transactions->links() }}
        </div>
    @endif
</div>

@if(session('print_id'))
<script>
    document.addEventListener("DOMContentLoaded", function() {
        let printWindow = window.open("{{ route('transactions.print', session('print_id')) }}", "PrintKarcis", "width=400,height=600");
    });
</script>
@endif

@endsection
