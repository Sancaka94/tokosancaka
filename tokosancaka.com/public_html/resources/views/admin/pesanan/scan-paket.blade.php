@extends('layouts.admin')

@section('title', 'Scan Paket Masuk')
@section('page-title', 'Scan Paket Masuk')

@section('content')
    {{-- Pustaka untuk Barcode Scanner --}}
    <script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>

    <div class="bg-white p-6 rounded-xl shadow-lg">
        
        <h1 class="text-2xl font-bold mb-4">Scan Paket</h1>

        {{-- Menampilkan pesan sukses atau error dari server --}}
        @if (session('success'))
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4" role="alert">
                <p>{{ session('success') }}</p>
            </div>
        @endif
        @if (session('error'))
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4" role="alert">
                <p>{{ session('error') }}</p>
            </div>
        @endif
        @if ($errors->any())
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4" role="alert">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Form yang akan di-submit --}}
        <form id="scanForm" action="{{ route('admin.pesanan.scan.process') }}" method="POST">
            @csrf
            <div class="mb-4">
                <label for="resi" class="block text-sm font-medium text-gray-700 mb-1">Scan Resi</label>
                <div class="relative">
                    <input type="text" id="resi" name="resi" placeholder="Scan dengan scanner fisik atau kamera..." class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500 transition-all" autofocus>
                </div>
            </div>

            <button type="button" id="start-camera-btn" class="w-full bg-orange-500 text-white font-semibold py-3 px-4 rounded-lg hover:bg-orange-600 transition-colors flex items-center justify-center gap-2">
                <i class="fas fa-camera"></i>
                <span>Scan dengan Kamera</span>
            </button>
        </form>

        {{-- Area untuk menampilkan kamera --}}
        <div id="camera-container" class="mt-4 hidden">
            <div id="reader" class="w-full rounded-lg overflow-hidden"></div>
            <div id="scan-result" class="mt-2 text-center font-medium"></div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const startCameraBtn = document.getElementById('start-camera-btn');
            const cameraContainer = document.getElementById('camera-container');
            const resiInput = document.getElementById('resi');
            const scanForm = document.getElementById('scanForm');
            const scanResultEl = document.getElementById('scan-result');

            let html5QrCode = null;

            const onScanSuccess = (decodedText, decodedResult) => {
                // Hentikan pemindaian agar tidak scan berulang kali
                stopCamera();
                
                // Pastikan input tidak kosong sebelum mengirim
                if (decodedText) {
                    scanResultEl.innerHTML = `<span class="text-green-600">Sukses! Mengirim resi: ${decodedText}</span>`;
                    
                    // PERBAIKAN: Mengisi nilai input
                    resiInput.value = decodedText;
                    
                    // Kirim form setelah jeda singkat
                    setTimeout(() => {
                        scanForm.submit();
                    }, 500);
                } else {
                    scanResultEl.innerHTML = `<span class="text-red-600">Gagal mendeteksi resi. Coba lagi.</span>`;
                }
            };

            const onScanFailure = (error) => {
                // Biarkan scanner terus mencoba
            };

            const startCamera = () => {
                html5QrCode = new Html5Qrcode("reader");
                cameraContainer.classList.remove('hidden');
                startCameraBtn.classList.add('hidden');
                html5QrCode.start(
                    { facingMode: "environment" },
                    { fps: 10, qrbox: { width: 250, height: 150 } },
                    onScanSuccess,
                    onScanFailure
                ).catch(err => {
                    scanResultEl.innerHTML = `<span class="text-red-600">Gagal memulai kamera. Pastikan Anda memberikan izin.</span>`;
                });
            };

            const stopCamera = () => {
                if (html5QrCode && html5QrCode.isScanning) {
                    html5QrCode.stop().then(() => {
                        cameraContainer.classList.add('hidden');
                        startCameraBtn.classList.remove('hidden');
                    }).catch(err => console.error("Gagal menghentikan kamera.", err));
                }
            };
            
            startCameraBtn.addEventListener('click', startCamera);
        });
    </script>
@endsection
