@extends('layouts.app')

@section('title', 'Transaksi Kendaraan')

@section('content')
<div class="flex justify-between items-center mb-6">
    <h1 class="text-xl md:text-2xl font-bold text-gray-800">Operasional Transaksi Parkir</h1>
</div>

<div class="card mb-6 border-t-4 border-blue-600 shadow-md">
    <div class="card-header bg-white border-b-2 border-gray-100">
        <span class="font-bold text-gray-800 text-lg">Catat Kendaraan Masuk</span>
    </div>
    <div class="card-body bg-gray-50 rounded-b-lg p-4 md:p-6">
        <form action="{{ route('transactions.store') }}" method="POST" class="flex flex-col md:flex-row gap-4 md:gap-6 items-end">
            @csrf

            <div class="w-full md:w-1/4">
                <label class="block text-sm font-bold text-gray-700 mb-2">Jenis</label>
                <select name="vehicle_type" class="form-control bg-white shadow-sm text-lg py-3" required tabindex="2">
                    <option value="motor">🏍️ Motor</option>
                    <option value="mobil">🚗 Mobil</option>
                </select>
            </div>

            <div class="w-full md:w-2/4">
                <label class="block text-sm font-bold text-gray-700 mb-2">Nomor Tiket / Plat</label>
                <input type="text"
                       name="plate_number"
                       id="plate_number"
                       class="form-control shadow-sm uppercase font-black text-2xl tracking-widest py-3 text-center md:text-left"
                       placeholder="ANGKA..."
                       required
                       autocomplete="off"
                       autofocus
                       tabindex="1"
                       inputmode="numeric"
                       pattern="[A-Za-z0-9\s]*"
                       oninput="this.value = this.value.toUpperCase()">
            </div>

            <div class="w-full md:w-1/4 mt-2 md:mt-0">
                <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white py-5 md:py-3 px-4 rounded-xl shadow-lg flex justify-center items-center gap-3 transition-transform active:scale-95" tabindex="3">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                    <span class="font-black text-2xl md:text-xl uppercase tracking-wider">CETAK</span>
                </button>
            </div>
        </form>
        @error('plate_number')
            <p class="text-red-500 text-sm mt-3 font-bold text-center md:text-left">{{ $message }}</p>
        @enderror
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-header bg-white border-b-2 border-blue-600 flex flex-col md:flex-row justify-between items-center gap-4">
        <span class="font-bold text-gray-800 uppercase tracking-wide text-sm whitespace-nowrap">Data & Riwayat Parkir</span>

        <form action="{{ route('transactions.index') }}" method="GET" class="flex flex-col sm:flex-row gap-2 w-full md:w-auto">
            <input type="text" name="keyword" value="{{ request('keyword') }}" class="form-control text-sm py-1.5 w-full sm:w-auto" placeholder="Cari Plat / No Karcis">
            <input type="date" name="tanggal" value="{{ request('tanggal') }}" class="form-control text-sm py-1.5 w-full sm:w-auto">
            <button type="submit" class="bg-gray-800 hover:bg-gray-900 text-white px-4 py-1.5 rounded text-sm font-bold transition-colors shadow-sm whitespace-nowrap">
                Cari
            </button>
            @if(request('keyword') || request('tanggal'))
                <a href="{{ route('transactions.index') }}" class="bg-red-100 hover:bg-red-200 text-red-700 px-3 py-1.5 rounded text-sm font-bold transition-colors flex items-center justify-center whitespace-nowrap">
                    Reset
                </a>
            @endif
        </form>
    </div>

    <div class="card-body p-0 block w-full overflow-x-auto">
        <table class="table-custom min-w-full">
            <thead class="bg-gray-100">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">No. Parkir / Plat</th>
                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Jenis</th>
                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Waktu Masuk</th>
                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Tarif / Status</th>
                    <th class="px-4 py-3 text-center text-xs font-bold text-gray-600 uppercase tracking-wider">Aksi</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($transactions as $trx)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-4 py-3 whitespace-nowrap">
                            <div class="text-xs text-gray-500 font-bold mb-1">TRX-{{ str_pad($trx->id, 5, '0', STR_PAD_LEFT) }}</div>
                            <div class="font-black text-lg md:text-xl text-gray-800 tracking-widest">{{ $trx->plate_number }}</div>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap capitalize text-sm md:text-base text-gray-600 font-medium">{{ $trx->vehicle_type }}</td>
                        <td class="px-4 py-3 whitespace-nowrap text-sm md:text-base text-gray-600">
                            {{ $trx->entry_time->translatedFormat('d M Y') }} <br>
                            <span class="font-bold text-blue-600">{{ $trx->entry_time->translatedFormat('H:i') }} WIB</span>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-sm md:text-base">
                            @if($trx->status == 'masuk')
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-yellow-100 text-yellow-800 border border-yellow-200">
                                    <span class="w-2 h-2 mr-1.5 bg-yellow-500 rounded-full animate-pulse"></span>
                                    Sedang Parkir
                                </span>
                            @else
                                <span class="font-black text-green-600 text-base md:text-lg">Rp {{ number_format($trx->fee, 0, ',', '.') }}</span>
                                <br><span class="text-[10px] md:text-xs text-gray-500 font-bold">Keluar: {{ $trx->exit_time->translatedFormat('H:i') }} WIB</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-center">
                            @if($trx->status == 'masuk')
                                <form action="{{ route('transactions.update', $trx->id) }}" method="POST" class="inline-block">
                                    @csrf
                                    @method('PUT')
                                    <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-3 md:px-4 py-1.5 md:py-2 rounded text-xs md:text-sm font-bold shadow-md transition-colors">
                                        Keluar &rarr;
                                    </button>
                                </form>
                            @else
                                <span class="text-gray-400 text-xs md:text-sm italic font-medium">Selesai</span>
                            @endif

                            @if(auth()->user()->role != 'operator')
                                <form action="{{ route('transactions.destroy', $trx->id) }}" method="POST" class="inline-block ml-2 md:ml-3" onsubmit="return confirm('Yakin ingin menghapus riwayat ini? Data yang terhapus akan memengaruhi laporan keuangan.');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="bg-red-100 hover:bg-red-200 text-red-700 px-2 md:px-3 py-1.5 rounded text-xs font-bold transition-colors">
                                        Hapus
                                    </button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-10 text-center text-gray-500 italic text-sm">
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
        // Fokuskan ulang ke input plat nomor agar pegawai siap mengetik lagi
        const plateInput = document.getElementById('plate_number');
        if(plateInput) plateInput.focus();

        // Menggunakan Iframe tersembunyi agar tidak buka tab baru di HP/Tablet
        const iframe = document.createElement('iframe');
        iframe.style.display = 'none';
        iframe.src = "{{ route('transactions.print', session('print_id')) }}";

        // Memasukkan iframe ke dalam dokumen
        document.body.appendChild(iframe);

        // Langsung trigger print otomatis ketika struk selesai dimuat di background
        iframe.onload = function() {
            iframe.contentWindow.focus();
            iframe.contentWindow.print();
        };
    });
</script>
@endif

@endsection
