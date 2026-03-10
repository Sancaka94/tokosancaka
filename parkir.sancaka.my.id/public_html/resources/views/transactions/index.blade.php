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
                <select name="vehicle_type" class="form-control bg-white shadow-sm text-lg py-3 rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500" required tabindex="2">
                    <option value="motor">🏍️ Motor</option>
                    <option value="mobil">🚗 Mobil</option>
                </select>
            </div>

            <div class="w-full md:w-2/4">
                <label class="block text-sm font-bold text-gray-700 mb-2">Nomor Tiket / Plat (Tanpa Spasi)</label>
                <input type="text"
                       name="plate_number"
                       id="plate_number"
                       class="form-control shadow-sm uppercase font-black text-2xl tracking-widest py-3 text-center md:text-left rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                       placeholder="Masukan Plat Nomor"
                       required
                       autocomplete="off"
                       autofocus
                       tabindex="1"
                       inputmode="numeric"
                       pattern="[A-Za-z0-9\s]*"
                       oninput="this.value = this.value.toUpperCase()">
            </div>

            <div class="w-full md:w-1/4 mt-2 md:mt-0 flex flex-col gap-2">
                <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white py-4 md:py-3 px-4 rounded-xl shadow-lg flex justify-center items-center gap-2 transition-transform active:scale-95" tabindex="3">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                    <span class="font-black text-xl uppercase tracking-wider">CETAK</span>
                </button>

                <div class="flex gap-2 w-full">
                    <button type="button" onclick="openRapelModal()" class="w-1/2 bg-emerald-500 hover:bg-emerald-600 active:bg-emerald-700 text-white py-2 px-1 rounded-lg shadow flex justify-center items-center gap-1 transition-transform active:scale-95">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        <span class="font-bold text-[10px] md:text-[11px] uppercase tracking-wide">Rapel Parkir</span>
                    </button>

                    <button type="button" onclick="openKasModal()" class="w-1/2 bg-purple-500 hover:bg-purple-600 active:bg-purple-700 text-white py-2 px-1 rounded-lg shadow flex justify-center items-center gap-1 transition-transform active:scale-95">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        <span class="font-bold text-[10px] md:text-[11px] uppercase tracking-wide">Kas Manual</span>
                    </button>
                </div>
            </div>
        </form>
        @error('plate_number')
            <p class="text-red-500 text-sm mt-3 font-bold text-center md:text-left">{{ $message }}</p>
        @enderror
    </div>
</div>

<div class="card shadow-sm border-0 rounded-xl overflow-hidden">
    <div class="card-header bg-white border-b-2 border-blue-600 flex flex-col xl:flex-row justify-between items-center gap-4 py-4 px-5">
        <span class="font-bold text-gray-800 uppercase tracking-wide text-sm whitespace-nowrap w-full xl:w-auto text-center xl:text-left">Data & Riwayat Parkir</span>

        <div class="flex flex-wrap justify-center xl:justify-end gap-3 w-full xl:w-auto items-center">

            <button type="button" onclick="openScanModal()" class="bg-gray-800 hover:bg-gray-900 text-white px-4 py-2.5 rounded-xl text-sm font-bold shadow-md flex items-center justify-center gap-2 w-full md:w-auto transition-transform active:scale-95 whitespace-nowrap">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h4a1 1 0 010 2H5v3a1 1 0 01-2 0V4zm14-1a1 1 0 011 1v3a1 1 0 01-2 0V5h-3a1 1 0 010-2h4zM3 20a1 1 0 001 1h4a1 1 0 000-2H5v-3a1 1 0 00-2 0v4zm14 1a1 1 0 001-1v-3a1 1 0 00-2 0v3h-3a1 1 0 000 2h4z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 10h4v4h-4v-4z"></path></svg>
                SCAN / CARI
            </button>

            <div class="hidden md:block w-px h-8 bg-gray-300"></div>

            <form action="{{ route('transactions.checkoutAll') }}" method="POST" class="w-full md:w-auto flex gap-2 items-center" onsubmit="return confirm('YAKIN INGIN MENYELESAIKAN SEMUA PARKIR PADA TANGGAL INI?');">
                @csrf
                <input type="date" name="checkout_date" class="form-control rounded-xl py-2 px-3 text-sm font-bold text-gray-700 border-2 border-red-200 focus:border-red-500 focus:ring-red-500 shadow-sm w-full md:w-auto" value="{{ request('tanggal') ?? date('Y-m-d') }}" required title="Pilih tanggal parkir yang ingin dikeluarkan">
                <button type="submit" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2.5 rounded-xl text-sm font-bold shadow-md flex items-center justify-center gap-2 transition-transform active:scale-95 whitespace-nowrap">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                    KELUARKAN SEMUA
                </button>
            </form>

            @if(auth()->user()->role != 'operator')
                <form action="{{ route('transactions.destroyAllByDate') }}" method="POST" class="w-full md:w-auto flex gap-2 items-center" onsubmit="return confirm('🚨 PERINGATAN FATAL 🚨\n\nApakah Anda benar-benar yakin ingin MENGHAPUS SEMUA DATA TRANSAKSI pada tanggal tersebut?\n\nData yang dihapus akan HILANG PERMANEN dan tidak masuk ke dalam Total Laporan Pendapatan Anda!');">
                    @csrf
                    @method('DELETE')
                    <input type="date" name="delete_date" class="form-control rounded-xl py-2 px-3 text-sm font-bold text-red-700 border-2 border-red-800 bg-red-50 focus:border-red-900 shadow-sm w-full md:w-auto" value="{{ request('tanggal') ?? date('Y-m-d') }}" required title="Pilih tanggal parkir yang ingin DIHAPUS PERMANEN">
                    <button type="submit" class="bg-red-800 hover:bg-red-900 text-white px-4 py-2.5 rounded-xl text-sm font-bold shadow-md flex items-center justify-center gap-2 transition-transform active:scale-95 whitespace-nowrap">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                        HAPUS SEMUA
                    </button>
                </form>
            @endif

        </div>
    </div>

    @if(request('keyword') || request('tanggal'))
        <div class="px-5 py-3 bg-blue-50 border-b border-blue-100 flex justify-between items-center">
            <span class="text-sm text-blue-800 font-medium">Menampilkan hasil: <span class="font-bold">{{ request('keyword') }} {{ request('tanggal') }}</span></span>
            <a href="{{ route('transactions.index') }}" class="text-red-600 font-bold text-xs bg-red-100 hover:bg-red-200 px-3 py-1.5 rounded-lg transition-colors">Reset Filter</a>
        </div>
    @endif

    <div class="card-body p-0 block w-full overflow-x-auto">
        <table class="table-custom min-w-full">
            <thead class="bg-gray-100 border-b border-gray-200">
                <tr>
                    <th class="px-5 py-4 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">No. Parkir / Plat</th>
                    <th class="px-5 py-4 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Jenis</th>
                    <th class="px-5 py-4 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Waktu Masuk</th>
                    <th class="px-5 py-4 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Tarif / Status</th>
                    <th class="px-5 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider">Aksi</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-100">
                @forelse($transactions as $trx)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-5 py-4 whitespace-nowrap">
                            <div class="text-xs text-gray-500 font-bold mb-1">TRX-{{ str_pad($trx->id, 5, '0', STR_PAD_LEFT) }}</div>
                            <div class="font-black text-lg md:text-xl text-gray-800 tracking-widest">{{ $trx->plate_number }}</div>
                        </td>
                        <td class="px-5 py-4 whitespace-nowrap capitalize text-sm md:text-base text-gray-600 font-medium">
                            {{ $trx->vehicle_type }}
                        </td>
                        <td class="px-5 py-4 whitespace-nowrap text-sm md:text-base text-gray-600">
                            {{ \Carbon\Carbon::parse($trx->entry_time)->translatedFormat('d M Y') }} <br>
                            <span class="font-bold text-blue-600">{{ \Carbon\Carbon::parse($trx->entry_time)->translatedFormat('H:i') }} WIB</span>
                        </td>
                        <td class="px-5 py-4 whitespace-nowrap text-sm md:text-base">
                            @if($trx->status == 'masuk')
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-yellow-100 text-yellow-800 border border-yellow-200">
                                    <span class="w-2 h-2 mr-1.5 bg-yellow-500 rounded-full animate-pulse"></span>
                                    Sedang Parkir
                                </span>
                            @else
                                <span class="font-black text-green-600 text-base md:text-lg">Rp {{ number_format($trx->fee, 0, ',', '.') }}</span>
                                <br>
                                <span class="text-[10px] md:text-xs text-gray-500 font-bold">
                                    Keluar: {{ \Carbon\Carbon::parse($trx->exit_time)->translatedFormat('d M Y') }} -
                                    <span class="text-red-500">{{ \Carbon\Carbon::parse($trx->exit_time)->translatedFormat('H:i') }} WIB</span>
                                </span>
                            @endif
                        </td>
                        <td class="px-5 py-4 whitespace-nowrap text-center">
                            @if($trx->status == 'masuk')
                                <form action="{{ route('transactions.update', $trx->id) }}" method="POST" class="inline-block">
                                    @csrf
                                    @method('PUT')
                                    <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-3 md:px-4 py-2 rounded-lg text-xs md:text-sm font-bold shadow-md transition-colors">
                                        Keluar &rarr;
                                    </button>
                                </form>
                            @else
                                <span class="text-gray-400 text-xs md:text-sm italic font-medium px-3 py-2 bg-gray-100 rounded-lg">Selesai</span>
                            @endif

                            @if(auth()->user()->role != 'operator')
                                <form action="{{ route('transactions.destroy', $trx->id) }}" method="POST" class="inline-block ml-2 md:ml-3" onsubmit="return confirm('Yakin ingin menghapus riwayat ini? Data yang terhapus akan memengaruhi laporan keuangan.');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="bg-red-50 text-red-600 hover:bg-red-600 hover:text-white border border-red-200 px-3 py-2 rounded-lg text-xs font-bold transition-all">
                                        Hapus
                                    </button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-5 py-10 text-center text-gray-500 italic text-sm">
                            <div class="flex flex-col items-center justify-center space-y-3">
                                <svg class="w-12 h-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path></svg>
                                <span>Data transaksi kendaraan tidak ditemukan.</span>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($transactions->hasPages())
        <div class="px-5 py-4 border-t border-gray-200 bg-gray-50">
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
                </div>

            <div class="my-5 flex items-center text-center text-sm text-gray-400 font-bold before:flex-1 before:border-t before:border-gray-200 before:mr-3 after:flex-1 after:border-t after:border-gray-200 after:ml-3">
                ATAU KETIK MANUAL
            </div>

            <form action="{{ route('transactions.index') }}" method="GET" class="flex flex-col gap-4">
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">Nomor TRX / Plat Nomor</label>
                    <input type="text" name="keyword" class="form-control rounded-lg text-xl py-3 uppercase font-black text-center tracking-widest border-2 focus:border-blue-500" placeholder="Cth: TRX-00012 / AE123">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">Filter Tanggal Masuk (Opsional)</label>
                    <input type="date" name="tanggal" class="form-control rounded-lg py-2 text-center font-bold text-gray-600 border-2 focus:border-blue-500">
                </div>
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-black text-lg py-4 rounded-xl shadow-lg w-full mt-2 transition-transform active:scale-95">
                    TAMPILKAN DATA
                </button>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        // --- 1. Logika Fokus Input Otomatis ---
        const plateInput = document.getElementById('plate_number');
        if(plateInput) {
            setTimeout(function() {
                plateInput.focus();
                plateInput.click();
            }, 500);
        }

        // --- 2. Logika Auto-Print RawBT (Aplikasi Android) ---
        @if(session('print_id'))
            let printUrl = "{{ route('transactions.print', session('print_id')) }}";

            fetch(printUrl)
                .then(response => {
                    if (!response.ok) throw new Error('Gagal memuat struk');
                    return response.text();
                })
                .then(textData => {
                    // Encode teks dan arahkan ke URL Intent RawBT
                    let encodedText = encodeURIComponent(textData);
                    let intentUrl = "intent:" + encodedText + "#Intent;scheme=rawbt;package=ru.a402d.rawbtprinter;end;";
                    window.location.href = intentUrl;
                })
                .catch(error => console.error('Gagal memicu RawBT:', error));
        @endif
    });

    // --- 3. Logika Scanner Barcode (HTML5-QRCode) ---
    let html5QrcodeScanner = null;

    function openScanModal() {
        document.getElementById('scanModal').classList.remove('hidden');

        if (!html5QrcodeScanner) {
            html5QrcodeScanner = new Html5QrcodeScanner(
                "reader",
                { fps: 10, qrbox: {width: 250, height: 250}, aspectRatio: 1.0 },
                false
            );
            html5QrcodeScanner.render(onScanSuccess, onScanFailure);
        }
    }

    function closeScanModal() {
        document.getElementById('scanModal').classList.add('hidden');

        if (html5QrcodeScanner) {
            html5QrcodeScanner.clear().then(() => {
                html5QrcodeScanner = null;
            }).catch(error => {
                console.error("Gagal mematikan scanner", error);
            });
        }
    }

    function onScanSuccess(decodedText, decodedResult) {
        closeScanModal(); // Tutup modal & matikan kamera
        // Redirect ke pencarian
        window.location.href = "{{ route('transactions.index') }}?keyword=" + encodeURIComponent(decodedText);
    }

    function onScanFailure(error) {
        // Abaikan agar console tidak penuh
    }

    // --- Logika Modal Rapel Uang ---
    function openRapelModal() {
        document.getElementById('rapelModal').classList.remove('hidden');
        setTimeout(() => document.getElementById('rapel_total_uang').focus(), 100);
    }

    function closeRapelModal() {
        document.getElementById('rapelModal').classList.add('hidden');
    }

    // --- Logika Modal Kas Manual ---
    function openKasModal() {
        document.getElementById('kasModal').classList.remove('hidden');
        setTimeout(() => document.getElementsByName('nominal')[0].focus(), 100);
    }

    function closeKasModal() {
        document.getElementById('kasModal').classList.add('hidden');
    }

    function hitungOtomatis() {
        let select = document.getElementById('rapel_vehicle_type');
        let tarif = parseInt(select.options[select.selectedIndex].getAttribute('data-tarif'));
        let jenis = select.options[select.selectedIndex].text.split(' ')[1]; // Ambil kata Motor/Mobil
        let uangInput = document.getElementById('rapel_total_uang').value;
        let uang = uangInput ? parseInt(uangInput) : 0;

        let hasilUnitElement = document.getElementById('hasil_unit');
        let errorHitung = document.getElementById('error_hitung');
        let btnSubmit = document.getElementById('btnSubmitRapel');
        let inputJumlah = document.getElementById('rapel_jumlah_kendaraan');

        document.getElementById('label_kendaraan').innerText = jenis;

        if (uang > 0 && uang >= tarif) {
            // BULATKAN KE BAWAH agar tidak strict
            let unit = Math.floor(uang / tarif);
            let sisaUang = uang - (unit * tarif);

            hasilUnitElement.innerText = unit;
            inputJumlah.value = unit;
            btnSubmit.disabled = false; // Tombol langsung aktif

            // Jika ada sisa uang (tidak pas kelipatan), munculkan sekadar info (bukan error)
            if (sisaUang > 0) {
                errorHitung.innerText = "💡 Catatan: Ada sisa/kembalian Rp " + sisaUang.toLocaleString('id-ID');
                errorHitung.className = "text-amber-600 text-xs font-bold mt-2"; // Ubah warna jadi peringatan kuning/oranye
            } else {
                errorHitung.classList.add('hidden');
            }
        } else {
            hasilUnitElement.innerText = "0";
            inputJumlah.value = 0;
            errorHitung.classList.add('hidden');
            btnSubmit.disabled = true; // Kunci tombol jika uang kurang dari 1 tarif
        }
    }

</script>

<div id="rapelModal" class="fixed inset-0 z-[100] hidden bg-gray-900 bg-opacity-80 backdrop-blur-sm flex items-center justify-center p-4 transition-opacity">
    <div class="bg-white w-full max-w-md rounded-2xl shadow-2xl overflow-hidden flex flex-col">
        <div class="px-5 py-4 border-b border-gray-200 flex justify-between items-center bg-gray-50">
            <h3 class="font-black text-lg text-gray-800 flex items-center gap-2">
                <span>💰</span> Rapel / Setoran Parkir
            </h3>
            <button type="button" onclick="closeRapelModal()" class="text-red-500 hover:bg-red-100 p-2 rounded-lg font-bold text-xl transition-colors leading-none">&times;</button>
        </div>

        <form action="{{ route('transactions.storeRapel') }}" method="POST" class="p-5">
            @csrf

            <div class="mb-4">
                <label class="block text-sm font-bold text-gray-700 mb-2">Tanggal Transaksi</label>
                <input type="date" name="tanggal_rapel" id="rapel_tanggal" class="form-control w-full py-3 text-center rounded-lg border-2 border-emerald-100 focus:border-emerald-500 font-bold text-gray-700" value="{{ date('Y-m-d') }}" required>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-bold text-gray-700 mb-2">Jenis Kendaraan & Tarif</label>
                <select name="vehicle_type" id="rapel_vehicle_type" onchange="hitungOtomatis()" class="form-control w-full bg-white shadow-sm py-3 rounded-lg border-gray-300 focus:border-emerald-500 font-bold" required>
                    <option value="motor" data-tarif="3000">🏍️ Motor (Rp 3.000 / unit)</option>
                    <option value="mobil" data-tarif="5000">🚗 Mobil (Rp 5.000 / unit)</option>
                </select>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-bold text-gray-700 mb-2">Total Uang Diterima (Rp)</label>
                <input type="number" name="total_uang" id="rapel_total_uang" oninput="hitungOtomatis()" class="form-control w-full text-2xl font-black text-center py-3 rounded-lg border-2 border-emerald-300 focus:border-emerald-500 focus:ring-emerald-500" placeholder="Misal: 9000" required>
            </div>

            <div class="bg-emerald-50 border border-emerald-200 rounded-xl p-4 mb-5 text-center">
                <p class="text-emerald-800 text-sm font-bold mb-1">Sistem akan memasukkan otomatis:</p>
                <div class="text-4xl font-black text-emerald-600">
                    <span id="hasil_unit">0</span> <span class="text-lg text-emerald-700 font-bold">Unit <span id="label_kendaraan">Motor</span></span>
                </div>
                <p id="error_hitung" class="text-red-500 text-xs font-bold mt-2 hidden">⚠️ Jumlah uang tidak pas dengan kelipatan tarif!</p>
            </div>

            <input type="hidden" name="jumlah_kendaraan" id="rapel_jumlah_kendaraan" value="0">

            <button type="submit" id="btnSubmitRapel" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white py-3 rounded-xl font-black text-lg shadow-lg transition-transform active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed" disabled>
                SIMPAN DATA RAPEL
            </button>
        </form>
    </div>
</div>

<div id="kasModal" class="fixed inset-0 z-[100] hidden bg-gray-900 bg-opacity-80 backdrop-blur-sm flex items-center justify-center p-4 transition-opacity">
    <div class="bg-white w-full max-w-md rounded-2xl shadow-2xl overflow-hidden flex flex-col">
        <div class="px-5 py-4 border-b border-gray-200 flex justify-between items-center bg-gray-50">
            <h3 class="font-black text-lg text-gray-800 flex items-center gap-2">
                <span>📓</span> Input Kas Manual
            </h3>
            <button type="button" onclick="closeKasModal()" class="text-red-500 hover:bg-red-100 p-2 rounded-lg font-bold text-xl transition-colors leading-none">&times;</button>
        </div>

        <form action="{{ route('transactions.storeKas') }}" method="POST" class="p-5">
            @csrf

            <div class="mb-4">
                <label class="block text-sm font-bold text-gray-700 mb-2">Tanggal Transaksi</label>
                <input type="date" name="tanggal" class="form-control w-full py-2 text-center rounded-lg border-2 border-purple-100 focus:border-purple-500 font-bold text-gray-700" value="{{ date('Y-m-d') }}" required>
            </div>

            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Jenis Kas</label>
                    <select name="jenis" class="form-control w-full bg-white shadow-sm py-2 rounded-lg border-2 border-gray-200 focus:border-purple-500 font-bold" required>
                        <option value="pemasukan">Pemasukan (+)</option>
                        <option value="pengeluaran">Pengeluaran (-)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Kategori</label>
                    <select name="kategori" class="form-control w-full bg-white shadow-sm py-2 rounded-lg border-2 border-gray-200 focus:border-purple-500 font-bold" required>
                        <option value="Parkiran">Parkiran</option>
                        <option value="Toilet">Toilet</option>
                        <option value="Operasional (Umum)">Operasional (Umum)</option>
                    </select>
                </div>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-bold text-gray-700 mb-2">Nominal (Rp)</label>
                <input type="number" name="nominal" class="form-control w-full text-2xl font-black text-center py-3 rounded-lg border-2 border-purple-300 focus:border-purple-500 focus:ring-purple-500" placeholder="0" required min="1">
            </div>

            <div class="mb-5">
                <label class="block text-sm font-bold text-gray-700 mb-2">Keterangan (Wajib)</label>
                <input type="text" name="keterangan" class="form-control w-full py-2 px-3 rounded-lg border-2 border-gray-200 focus:border-purple-500" placeholder="Misal: Beli sapu / Setoran toilet manual" required>
            </div>

            <button type="submit" class="w-full bg-purple-600 hover:bg-purple-700 text-white py-3 rounded-xl font-black text-lg shadow-lg transition-transform active:scale-95">
                SIMPAN KAS
            </button>
        </form>
    </div>
</div>

@endsection
