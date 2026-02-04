{{-- resources/views/admin/pesanan/scan-aktual.blade.php --}}

@extends('layouts.admin')

@section('title', 'Scan Resi Aktual')
@section('page-title', 'Update Resi Aktual')

@section('content')
<div class="max-w-4xl mx-auto grid grid-cols-1 lg:grid-cols-2 gap-8">
    
    <!-- Kolom Kiri: Detail Pesanan & Form -->
    <div class="bg-white p-6 rounded-lg shadow-md">
        <h3 class="text-xl font-semibold text-gray-800 border-b pb-4 mb-4">Detail Pesanan</h3>
        <div class="space-y-2 text-sm">
            <div class="flex justify-between"><span class="text-gray-500">Resi Internal:</span><span class="font-medium text-gray-900">{{ $pesanan->resi }}</span></div>
            <div class="flex justify-between"><span class="text-gray-500">Penerima:</span><span class="font-medium text-gray-900">{{ $pesanan->nama_pembeli }}</span></div>
            <div class="flex justify-between"><span class="text-gray-500">Tujuan:</span><span class="font-medium text-gray-900">{{ $pesanan->alamat_pengiriman }}</span></div>
            <div class="flex justify-between"><span class="text-gray-500">Ekspedisi Awal:</span><span class="font-medium text-gray-900">{{ $pesanan->expedition }}</span></div>
        </div>

        <form action="{{ route('admin.pesanan.update.resi', $pesanan->resi) }}" method="POST" class="mt-6 border-t pt-6">
            @csrf
            @method('PUT')

            <div class="mb-4">
                <label for="jasa_ekspedisi_aktual" class="block mb-2 text-sm font-medium text-gray-700">Jasa Ekspedisi Aktual</label>
                <select id="jasa_ekspedisi_aktual" name="jasa_ekspedisi_aktual" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5">
                    @foreach ($ekspedisiList as $exp)
                        <option value="{{ $exp->nama_ekspedisi }}" {{ $pesanan->expedition == $exp->nama_ekspedisi ? 'selected' : '' }}>{{ $exp->nama_ekspedisi }}</option>
                    @endforeach
                </select>
            </div>

            <div class="mb-4">
                <label for="resi_aktual" class="block mb-2 text-sm font-medium text-gray-700">Resi Aktual (Hasil Scan)</label>
                <input type="text" id="resi_aktual" name="resi_aktual" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5" placeholder="Hasil scan akan muncul di sini" value="{{ old('resi_aktual', $pesanan->resi_aktual) }}" required>
            </div>

            <div class="mb-4">
                <label for="total_harga_barang" class="block mb-2 text-sm font-medium text-gray-700">Total Ongkir (Rp)</label>
                <input type="number" id="total_harga_barang" name="total_harga_barang" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5" placeholder="Masukkan total ongkir aktual" value="{{ old('total_harga_barang', $pesanan->total_harga_barang) }}">
            </div>

            <button type="submit" class="w-full text-white bg-indigo-600 hover:bg-indigo-700 font-medium rounded-lg text-sm px-5 py-3 text-center">
                Simpan & Update Status
            </button>
        </form>
    </div>

    <!-- Kolom Kanan: Tombol untuk memulai scan -->
    <div class="bg-white p-6 rounded-lg shadow-md flex flex-col items-center justify-center text-center">
        <svg class="w-16 h-16 text-indigo-500 mb-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 4.875c0-1.036.84-1.875 1.875-1.875h4.5c1.036 0 1.875.84 1.875 1.875v4.5c0 1.036-.84 1.875-1.875 1.875h-4.5A1.875 1.875 0 013.75 9.375v-4.5zM3.75 14.625c0-1.036.84-1.875 1.875-1.875h4.5c1.036 0 1.875.84 1.875 1.875v4.5c0 1.036-.84 1.875-1.875 1.875h-4.5a1.875 1.875 0 01-1.875-1.875v-4.5zM13.5 4.875c0-1.036.84-1.875 1.875-1.875h4.5c1.036 0 1.875.84 1.875 1.875v4.5c0 1.036-.84 1.875-1.875 1.875h-4.5a1.875 1.875 0 01-1.875-1.875v-4.5zM13.5 14.625c0-1.036.84-1.875 1.875-1.875h4.5c1.036 0 1.875.84 1.875 1.875v4.5c0 1.036-.84 1.875-1.875 1.875h-4.5a1.875 1.875 0 01-1.875-1.875v-4.5z" /></svg>
        <h3 class="text-xl font-semibold text-gray-800">Scan Resi Ekspedisi</h3>
        <p class="text-gray-500 mt-2 mb-6">Gunakan kamera atau unggah gambar dari galeri untuk mengisi kolom Resi Aktual secara otomatis.</p>
        <button id="openScannerButton" class="bg-green-600 text-white px-8 py-3 rounded-lg hover:bg-green-700 text-lg font-medium">
            Mulai Scan
        </button>
    </div>
</div>

<!-- Scanner dalam Tampilan Layar Penuh (Hidden by default) -->
<div id="scanner-container" class="fixed inset-0 bg-black z-50 hidden">
    <div id="reader" style="width: 100%; height: 100%;"></div>
    <div id="scanner-ui" class="absolute inset-0 flex flex-col justify-between p-4">
        <!-- Tombol Atas: Ambil dari Galeri -->
        <div class="text-right">
            <label for="file-input" class="bg-white/20 backdrop-blur-sm text-white px-4 py-2 rounded-lg cursor-pointer hover:bg-white/30">
                Ambil dari Galeri
            </label>
            <input type="file" id="file-input" accept="image/*" class="hidden">
        </div>
        <!-- Tombol Bawah: Batal -->
        <div class="text-center">
            <button id="closeScannerButton" class="bg-white/20 backdrop-blur-sm text-white px-8 py-3 rounded-lg hover:bg-white/30">
                Batal
            </button>
        </div>
    </div>
</div>

<script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const resiAktualInput = document.getElementById('resi_aktual');
    const scannerContainer = document.getElementById('scanner-container');
    const openScannerButton = document.getElementById('openScannerButton');
    const closeScannerButton = document.getElementById('closeScannerButton');
    const fileInput = document.getElementById('file-input');
    
    let html5Qrcode;
    let audioUnlocked = false; // Penanda audio

    // ✅ KELENGKAPAN: Menyiapkan audio
    const successSound = new Audio("{{ asset('sound/beep.mp3') }}");
    const failSound = new Audio("{{ asset('sound/beep-gagal.mp3') }}");
    successSound.load();
    failSound.load();

    // ✅ PERBAIKAN: Fungsi untuk membuka kunci audio
    const unlockAudio = () => {
        if (audioUnlocked) return;
        successSound.play().catch(() => {});
        successSound.pause();
        successSound.currentTime = 0;
        failSound.play().catch(() => {});
        failSound.pause();
        failSound.currentTime = 0;
        audioUnlocked = true;
        document.body.removeEventListener('click', unlockAudio);
        document.body.removeEventListener('keydown', unlockAudio);
    };
    document.body.addEventListener('click', unlockAudio);
    document.body.addEventListener('keydown', unlockAudio);

    function onScanSuccess(decodedText, decodedResult) {
        successSound.play().catch(e => {}); // Mainkan suara sukses
        resiAktualInput.value = decodedText;
        alert(`Scan Berhasil: ${decodedText}`);
        closeScanner();
    }

    function onScanFailure(error) {
        // Abaikan error, karena ini akan terus berjalan saat kamera aktif
    }

    function openScanner() {
        scannerContainer.classList.remove('hidden');
        
        if (!html5Qrcode) {
            html5Qrcode = new Html5Qrcode("reader");
        }

        html5Qrcode.start(
            { facingMode: "environment" },
            { fps: 10, qrbox: (viewfinderWidth, viewfinderHeight) => {
                let minEdge = Math.min(viewfinderWidth, viewfinderHeight);
                let qrboxSize = Math.floor(minEdge * 0.7);
                return { width: qrboxSize, height: qrboxSize };
            }},
            onScanSuccess,
            onScanFailure
        ).catch((err) => {
            failSound.play().catch(e => {}); // Mainkan suara gagal
            alert(`Error: Tidak dapat memulai kamera. ${err}`);
            closeScanner();
        });
    }

    function closeScanner() {
        if (html5Qrcode && html5Qrcode.isScanning) {
            html5Qrcode.stop().catch(err => console.error("Gagal menghentikan scanner.", err));
        }
        scannerContainer.classList.add('hidden');
    }

    // Event Listeners
    openScannerButton.addEventListener('click', openScanner);
    closeScannerButton.addEventListener('click', closeScanner);

    fileInput.addEventListener('change', e => {
        if (e.target.files.length == 0) return;
        const imageFile = e.target.files[0];
        
        if (!html5Qrcode) {
            html5Qrcode = new Html5Qrcode("reader");
        }
        
        scannerContainer.classList.remove('hidden');
        
        html5Qrcode.scanFile(imageFile, true)
            .then(decodedText => {
                onScanSuccess(decodedText);
            })
            .catch(err => {
                failSound.play().catch(e => {}); // Mainkan suara gagal
                alert(`Error memindai file: ${err}`);
                closeScanner();
            });
    });
});
</script>
@endsection
