<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Scanner Cepat - Sancaka</title>

    {{-- CSS Framework --}}
    <script src="https://cdn.tailwindcss.com"></script>

    {{-- Library Scanner (Versi Stabil) --}}
    <script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>

    <style>
        body { background-color: #000; color: white; overflow: hidden; }
        #reader { width: 100%; height: 100vh; object-fit: cover; }

        /* Hilangkan elemen UI bawaan yang jelek */
        #reader__dashboard_section_csr span,
        #reader__dashboard_section_swaplink { display: none !important; }

        /* Custom Bingkai Scanner Modern */
        .scan-overlay {
            position: absolute; top: 50%; left: 50%;
            transform: translate(-50%, -50%);
            width: 280px; height: 150px; /* Persegi Panjang untuk Barcode */
            border: 2px solid rgba(255, 255, 255, 0.5);
            border-radius: 10px;
            box-shadow: 0 0 0 9999px rgba(0, 0, 0, 0.5); /* Gelapkan area luar */
            z-index: 10;
        }
        /* Garis Merah Laser */
        .scan-line {
            position: absolute; top: 0; left: 0; width: 100%; height: 2px;
            background: red;
            animation: scanMove 1.5s infinite linear;
            box-shadow: 0 0 4px red;
        }
        @keyframes scanMove {
            0% { top: 0; } 50% { top: 100%; } 100% { top: 0; }
        }

        /* Status Text */
        .status-badge {
            position: absolute; top: 20px; left: 50%; transform: translateX(-50%);
            background: rgba(0,0,0,0.7); padding: 8px 16px; border-radius: 20px;
            font-size: 14px; font-weight: bold; z-index: 20; border: 1px solid #444;
        }
    </style>
</head>
<body>

    {{-- Overlay Visual --}}
    <div class="status-badge" id="statusText">📷 Siapkan Barcode...</div>
    <div class="scan-overlay">
        <div class="scan-line"></div>
    </div>

    {{-- Area Kamera --}}
    <div id="reader"></div>

    {{-- Audio Beep --}}
    <audio id="beepSound" src="https://www.soundjay.com/button/beep-07.wav"></audio>

    {{-- SCRIPT PROSES --}}
    <script>
        // === 1. KONFIGURASI PUSHER (JALUR LANGIT) ===
        // Ganti dengan script Pusher Anda yang TADI SUDAH BERHASIL di Laptop
        // (Biasanya di halaman mobile scanner ini hanya perlu KIRIM data via AJAX ke server)
        // TAPI jika Anda pakai client-event, masukkan Pusher disini.

        // PENTING: Di halaman mobile scanner, fokus utamanya adalah SCAN -> AJAX POST -> SERVER -> PUSHER -> LAPTOP
        // Jadi kita fokus perbaiki kualitas Scannernya saja disini.

        let isProcessing = false;
        const beep = document.getElementById('beepSound');
        const statusText = document.getElementById('statusText');

        function onScanSuccess(decodedText, decodedResult) {
            if (isProcessing) return; // Cegah scan dobel
            isProcessing = true;

            // 1. Efek Audio & Visual
            beep.play().catch(e => console.log('Audio blocked'));
            statusText.innerHTML = "✅ " + decodedText;
            statusText.style.borderColor = "#00ff00";
            statusText.style.color = "#00ff00";

            // 2. Kirim Data ke Server (Agar diteruskan ke Laptop via Pusher)
            // GANTI URL INI dengan Route Laravel Anda yang menangani scan
            fetch("{{ route('api.scan.process') }}", { // Pastikan route ini ada
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": "{{ csrf_token() }}"
                },
                body: JSON.stringify({ barcode: decodedText })
            })
            .then(res => res.json())
            .then(data => {
                console.log("Terkirim:", data);
                // Reset setelah 1.5 detik
                setTimeout(() => {
                    isProcessing = false;
                    statusText.innerHTML = "📷 Siapkan Barcode...";
                    statusText.style.borderColor = "#444";
                    statusText.style.color = "white";
                }, 1500);
            })
            .catch(err => {
                console.error("Gagal kirim:", err);
                isProcessing = false;
            });
        }

        // === 2. SETUP SCANNER CANGGIH (TURBO MODE) ===
        const html5QrCode = new Html5Qrcode("reader");

        const config = {
            fps: 20, // Kecepatan tinggi (Standar 10)
            qrbox: { width: 250, height: 150 }, // Kotak Lebar (Khusus Barcode Barang)
            aspectRatio: 1.0,
            // [RAHASIA] Fitur Eksperimental Hardware Acceleration
            experimentalFeatures: {
                useBarCodeDetectorIfSupported: true
            },
            // Hanya cari Barcode Produk (Biar gak pusing nyari QR)
            formatsToSupport: [
                Html5QrcodeSupportedFormats.EAN_13,
                Html5QrcodeSupportedFormats.CODE_128,
                Html5QrcodeSupportedFormats.CODE_39
            ]
        };

        // Mulai Kamera Belakang
        html5QrCode.start(
            { facingMode: "environment" }, // Paksa Kamera Belakang
            config,
            onScanSuccess
        ).catch(err => {
            console.error("Kamera Error:", err);
            statusText.innerHTML = "❌ Kamera Gagal: " + err;
        });

    </script>
</body>
</html>
