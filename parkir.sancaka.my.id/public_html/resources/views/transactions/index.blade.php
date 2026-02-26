@extends('layouts.app')

@section('title', 'Transaksi Kendaraan')

@section('content')
<script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>

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
                <label class="block text-sm font-bold text-gray-700 mb-2">Jenis Kendaraan</label>
                <select name="vehicle_type" class="form-control bg-white shadow-sm text-lg py-3" required tabindex="2">
                    <option value="motor">🏍️ Motor</option>
                    <option value="mobil">🚗 Mobil</option>
                </select>
            </div>

            <div class="w-full md:w-2/4">
                <label class="block text-sm font-bold text-gray-700 mb-2">Nomor Tiket / Plat (Tanpa Spasi)</label>
                <input type="text"
                       name="plate_number"
                       id="plate_number"
                       class="form-control shadow-sm uppercase font-black text-2xl tracking-widest py-3 text-center md:text-left"
                       placeholder="Masukan Plat Nomor"
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
    <div class="card-header bg-white border-b-2 border-blue-600 flex flex-col md:flex-row justify-between items-center gap-4 py-4">
        <span class="font-bold text-gray-800 uppercase tracking-wide text-sm whitespace-nowrap">Data & Riwayat Parkir</span>

        <button type="button" onclick="openScanModal()" class="bg-gray-800 hover:bg-gray-900 text-white px-6 py-3 md:py-2.5 rounded-xl text-sm font-bold shadow-md flex items-center justify-center gap-2 w-full md:w-auto transition-transform active:scale-95">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h4a1 1 0 010 2H5v3a1 1 0 01-2 0V4zm14-1a1 1 0 011 1v3a1 1 0 01-2 0V5h-3a1 1 0 010-2h4zM3 20a1 1 0 001 1h4a1 1 0 000-2H5v-3a1 1 0 00-2 0v4zm14 1a1 1 0 001-1v-3a1 1 0 00-2 0v3h-3a1 1 0 000 2h4z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 10h4v4h-4v-4z"></path></svg>
            SCAN KARCIS / CARI
        </button>
    </div>

    @if(request('keyword') || request('tanggal'))
        <div class="px-4 py-3 bg-blue-50 border-b border-blue-100 flex justify-between items-center">
            <span class="text-sm text-blue-800 font-medium">Menampilkan hasil: <span class="font-bold">{{ request('keyword') }} {{ request('tanggal') }}</span></span>
            <a href="{{ route('transactions.index') }}" class="text-red-600 font-bold text-xs bg-red-100 hover:bg-red-200 px-3 py-1.5 rounded-lg transition-colors">Reset Filter</a>
        </div>
    @endif

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


<div id="scanModal" class="fixed inset-0 z-[100] hidden bg-gray-900 bg-opacity-80 backdrop-blur-sm flex items-center justify-center p-4 transition-opacity">
    <div class="bg-white w-full max-w-lg rounded-2xl shadow-2xl overflow-hidden flex flex-col">
        <div class="px-5 py-4 border-b border-gray-200 flex justify-between items-center bg-gray-50">
            <h3 class="font-black text-lg text-gray-800 flex items-center gap-2">
                <span>📷</span> Scan Karcis / Cari Manual
            </h3>
            <button type="button" onclick="closeScanModal()" class="text-red-500 hover:bg-red-100 p-2 rounded-lg font-bold text-xl transition-colors leading-none">&times;</button>
        </div>

        <div class="p-5 overflow-y-auto max-h-[80vh]">
            <div id="reader" class="w-full bg-black rounded-xl overflow-hidden shadow-inner min-h-[250px] flex items-center justify-center text-white text-sm font-medium">
                Kamera sedang disiapkan...
            </div>

            <div class="my-5 flex items-center text-center text-sm text-gray-400 font-bold before:flex-1 before:border-t before:border-gray-200 before:mr-3 after:flex-1 after:border-t after:border-gray-200 after:ml-3">
                ATAU KETIK MANUAL
            </div>

            <form action="{{ route('transactions.index') }}" method="GET" class="flex flex-col gap-4">
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">Nomor TRX / Plat Nomor</label>
                    <input type="text" name="keyword" class="form-control text-xl py-3 uppercase font-black text-center tracking-widest border-2 focus:border-blue-500" placeholder="Cth: TRX-00012 / AE123">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">Filter Tanggal Masuk (Opsional)</label>
                    <input type="date" name="tanggal" class="form-control py-2 text-center font-bold text-gray-600">
                </div>
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-black text-lg py-4 rounded-xl shadow-lg w-full mt-2 transition-transform active:scale-95">
                    TAMPILKAN DATA
                </button>
            </form>
        </div>
    </div>
</div>


<script>
    // 1. Logika Auto-Print (Menggunakan Iframe tersembunyi)
    @if(session('print_id'))
    document.addEventListener("DOMContentLoaded", function() {
        const plateInput = document.getElementById('plate_number');
        if(plateInput) plateInput.focus();

        const iframe = document.createElement('iframe');
        iframe.style.display = 'none';
        iframe.src = "{{ route('transactions.print', session('print_id')) }}";

        document.body.appendChild(iframe);

        iframe.onload = function() {
            iframe.contentWindow.focus();
            iframe.contentWindow.print();
        };
    });
    @endif

    // 2. Logika Scanner Barcode (HTML5-QRCode)
    let html5QrcodeScanner = null;

    function openScanModal() {
        document.getElementById('scanModal').classList.remove('hidden');

        // Render scanner jika belum berjalan
        if (!html5QrcodeScanner) {
            html5QrcodeScanner = new Html5QrcodeScanner(
                "reader",
                { fps: 10, qrbox: {width: 250, height: 250}, aspectRatio: 1.0 },
                /* verbose= */ false);

            html5QrcodeScanner.render(onScanSuccess, onScanFailure);
        }
    }

    function closeScanModal() {
        document.getElementById('scanModal').classList.add('hidden');

        // Matikan kamera saat modal ditutup untuk menghemat baterai HP
        if (html5QrcodeScanner) {
            html5QrcodeScanner.clear().then(() => {
                html5QrcodeScanner = null;
            }).catch(error => {
                console.error("Gagal mematikan scanner", error);
            });
        }
    }

    function onScanSuccess(decodedText, decodedResult) {
        // Jika berhasil scan barcode
        closeScanModal(); // Tutup modal & matikan kamera

        // Arahkan browser ke URL pencarian dengan hasil scan (TRX)
        window.location.href = "{{ route('transactions.index') }}?keyword=" + encodeURIComponent(decodedText);
    }

    function onScanFailure(error) {
        // Jika tidak menemukan barcode di frame, abaikan saja sampai ketemu.
        // Console log dimatikan agar browser tidak berat.
    }
</script>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        const plateInput = document.getElementById('plate_number');

        if(plateInput) {
            // Trik 1: Beri sedikit delay agar browser selesai memuat seluruh elemen
            setTimeout(function() {
                // Trik 2: Paksa fokus dan simulasikan klik pada elemen
                plateInput.focus();
                plateInput.click();

                // Trik 3 (Khusus beberapa versi Android): scroll sedikit ke inputan
                plateInput.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }, 500); // Delay 0.5 detik
        }
    });
</script>

@endsection
