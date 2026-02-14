@extends('layouts.app')

@section('title', 'Scanner Absensi Seminar')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="max-w-md mx-auto bg-white rounded-lg shadow-lg overflow-hidden">
        <div class="bg-blue-600 p-4 text-white text-center">
            <h2 class="text-xl font-bold">SCANNER ABSENSI</h2>
            <p class="text-sm text-blue-100">Arahkan kamera ke QR Code Peserta</p>
        </div>

        <div class="p-4">
            {{-- AREA KAMERA --}}
            <div id="reader" width="600px" class="bg-gray-100 rounded-lg overflow-hidden"></div>

            {{-- HASIL SCAN --}}
            <div id="result-container" class="mt-4 hidden text-center">
                <div id="result-icon" class="text-6xl mb-2"></div>
                <h3 id="result-title" class="text-xl font-bold"></h3>
                <p id="result-name" class="text-lg"></p>
                <p id="result-time" class="text-sm text-gray-500 font-mono mt-1"></p>
                <button onclick="resetScan()" class="mt-4 bg-gray-600 text-white px-4 py-2 rounded text-sm w-full">Scan Berikutnya</button>
            </div>
        </div>
    </div>
</div>

{{-- LIBRARY SCANNER --}}
<script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>

<script>
    const html5QrCode = new Html5Qrcode("reader");
    const resultContainer = document.getElementById('result-container');
    const readerDiv = document.getElementById('reader');

    // Suara Beep saat scan sukses
    const beepSound = new Audio('https://www.soundjay.com/buttons/beep-01a.mp3');

    function onScanSuccess(decodedText, decodedResult) {
        // Stop scanning sementara biar gak double
        html5QrCode.pause();
        beepSound.play();

        // Kirim data ke server Laravel
        fetch('{{ route("admin.seminar.process_scan") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ code: decodedText })
        })
        .then(response => response.json())
        .then(data => {
            showResult(data);
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Terjadi kesalahan koneksi.');
            html5QrCode.resume();
        });
    }

    function showResult(data) {
        readerDiv.classList.add('hidden');
        resultContainer.classList.remove('hidden');

        const iconDiv = document.getElementById('result-icon');
        const titleDiv = document.getElementById('result-title');
        const nameDiv = document.getElementById('result-name');
        const timeDiv = document.getElementById('result-time');

        if(data.status === 'success') {
            // BERHASIL ABSEN
            iconDiv.innerHTML = '✅';
            titleDiv.className = 'text-xl font-bold text-green-600';
            titleDiv.innerText = 'ABSENSI BERHASIL';
            nameDiv.innerText = data.data.nama;
            timeDiv.innerText = 'Jam Masuk: ' + data.time;
        } else if(data.status === 'warning') {
            // SUDAH PERNAH ABSEN
            iconDiv.innerHTML = '⚠️';
            titleDiv.className = 'text-xl font-bold text-orange-500';
            titleDiv.innerText = 'SUDAH HADIR';
            nameDiv.innerText = data.data.nama;
            timeDiv.innerText = 'Tercatat pada: ' + data.time;
        } else {
            // TIKET SALAH
            iconDiv.innerHTML = '❌';
            titleDiv.className = 'text-xl font-bold text-red-600';
            titleDiv.innerText = 'TIKET TIDAK DITEMUKAN';
            nameDiv.innerText = '-';
            timeDiv.innerText = '';
        }
    }

    function resetScan() {
        resultContainer.classList.add('hidden');
        readerDiv.classList.remove('hidden');
        html5QrCode.resume();
    }

    // Config Camera
    const config = { fps: 10, qrbox: { width: 250, height: 250 } };

    // Start Camera (Prioritas Kamera Belakang)
    html5QrCode.start({ facingMode: "environment" }, config, onScanSuccess);

</script>
@endsection
