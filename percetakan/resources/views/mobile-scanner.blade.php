<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    {{-- Token CSRF Wajib untuk kirim data POST di Laravel --}}
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Scanner HP - Sancaka POS</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>
</head>
<body class="bg-slate-900 text-white h-screen flex flex-col items-center justify-center p-4">

    <div class="w-full max-w-sm text-center">
        <h1 class="text-xl font-bold text-emerald-400 mb-1">Sancaka Remote Scanner</h1>
        <p class="text-xs text-slate-400 mb-4">Arahkan kamera ke Barcode Produk</p>

        {{-- Area Kamera --}}
        <div id="reader" class="w-full bg-black rounded-xl overflow-hidden border-2 border-emerald-500 shadow-lg shadow-emerald-500/20"></div>

        {{-- Status Log --}}
        <div id="status" class="mt-4 p-3 bg-slate-800 rounded-lg text-sm text-slate-300 min-h-[50px] flex items-center justify-center border border-slate-700">
            Siap memindai...
        </div>
    </div>

    {{-- Audio Beep --}}
    <audio id="beep" src="https://tokosancaka.com/public/sound/beep.mp3"></audio>

    <script>
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        const statusDiv = document.getElementById('status');
        const beepAudio = document.getElementById('beep');
        let isScanning = false; // Mencegah double scan cepat

        function onScanSuccess(decodedText, decodedResult) {
            if (isScanning) return; // Jika sedang proses kirim, abaikan scan baru
            isScanning = true;

            // 1. Bunyi Beep di HP
            beepAudio.currentTime = 0;
            beepAudio.play().catch(e => console.log('Audio error:', e));

            // 2. Update Status UI
            statusDiv.innerHTML = `<span class="text-emerald-400 font-bold animate-pulse">Mengirim: ${decodedText}...</span>`;

            // 3. Kirim ke Server
            fetch("{{ route('scanner.send') }}", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": csrfToken
                },
                body: JSON.stringify({ barcode: decodedText })
            })
            .then(response => response.json())
            .then(data => {
                // Sukses
                statusDiv.innerHTML = `<span class="text-white font-bold">✅ Terkirim: ${decodedText}</span>`;
                statusDiv.classList.add('bg-emerald-900/50');

                // Jeda 1.5 detik sebelum bisa scan lagi
                setTimeout(() => {
                    isScanning = false;
                    statusDiv.innerHTML = "Siap memindai...";
                    statusDiv.classList.remove('bg-emerald-900/50');
                }, 1500);
            })
            .catch((error) => {
                console.error("Error:", error);
                statusDiv.innerHTML = `<span class="text-red-400">❌ Gagal Kirim! Cek Koneksi.</span>`;
                isScanning = false;
            });
        }

        // Konfigurasi Scanner
        const html5QrcodeScanner = new Html5QrcodeScanner(
            "reader",
            {
                fps: 10,
                qrbox: { width: 250, height: 250 },
                aspectRatio: 1.0
            },
            false
        );

        html5QrcodeScanner.render(onScanSuccess, (errorMessage) => {
            // Abaikan error frame kosong agar console bersih
        });
    </script>
</body>
</html>
