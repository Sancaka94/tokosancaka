<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Scanner POS - Sancaka</title>

    {{-- Kita gunakan CSS Manual agar ringan (Tanpa load Bootstrap berat) --}}
    <style>
        /* === RESET & LAYOUT === */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            background-color: #000;
            color: white;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            overflow: hidden; /* Mencegah scroll */
            height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        /* === AREA KAMERA === */
        #reader {
            width: 100%;
            height: 100%;
            object-fit: cover;
            position: absolute;
            top: 0; left: 0; z-index: 1;
        }

        /* Sembunyikan UI Bawaan Library yang jelek */
        #reader__dashboard_section_csr span,
        #reader__dashboard_section_swaplink,
        #reader__scan_region img { display: none !important; }

        /* === OVERLAY KOTAK FOKUS (GAYA SPX) === */
        .scan-overlay {
            position: absolute;
            top: 50%; left: 50%;
            transform: translate(-50%, -50%);
            width: 280px;
            height: 150px; /* Persegi Panjang untuk Barcode */

            /* Border Hijau Neon khas SPX */
            border: 3px solid #00ff00;
            border-radius: 12px;

            /* Gelapkan area luar kotak agar fokus */
            box-shadow: 0 0 0 9999px rgba(0, 0, 0, 0.75);

            z-index: 10;
            pointer-events: none; /* Agar klik tembus ke bawah */
        }

        /* === LASER MERAH === */
        .scan-line {
            position: absolute;
            top: 0; left: 0;
            width: 100%;
            height: 2px;
            background: red;
            box-shadow: 0 0 10px red; /* Efek glowing */
            animation: scanMove 1.5s infinite linear;
        }

        @keyframes scanMove {
            0% { top: 0; opacity: 0; }
            50% { opacity: 1; }
            100% { top: 100%; opacity: 0; }
        }

        /* === STATUS BADGE (ATAS) === */
        .status-badge {
            position: absolute;
            top: 40px;
            z-index: 20;
            background: rgba(0,0,0,0.6);
            border: 1px solid rgba(255,255,255,0.3);
            backdrop-filter: blur(4px);
            padding: 8px 20px;
            border-radius: 30px;
            font-size: 14px;
            font-weight: 600;
            letter-spacing: 0.5px;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }

        /* === TOMBOL FLASH (BAWAH) === */
        .flash-control {
            position: absolute;
            bottom: 50px;
            z-index: 20;
            background: rgba(255,255,255,0.15);
            border: 1px solid rgba(255,255,255,0.5);
            color: white;
            padding: 12px 24px;
            border-radius: 50px;
            backdrop-filter: blur(4px);
            font-weight: bold;
            cursor: pointer;
            display: none; /* Hidden default, muncul via JS */
            text-transform: uppercase;
            font-size: 13px;
            letter-spacing: 1px;
            transition: background 0.2s;
        }
        .flash-control:active { background: rgba(255,255,255,0.3); }

    </style>
</head>
<body>

    <div class="status-badge" id="statusText">
        <span>📷</span> <span id="msg">Siap Scan Barcode...</span>
    </div>

    <div class="scan-overlay">
        <div class="scan-line"></div>
    </div>

    <button id="flash-toggle" class="flash-control">🔦 Flash: OFF</button>

    <div id="reader"></div>

    <audio id="beepSound" src="https://tokosancaka.com/public/sound/beep.mp3"></audio>

    {{-- Library Scanner --}}
    <script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>

    <script>
        // === KONFIGURASI ===
        const routeProcess = "{{ route('scanner.process') }}"; // Pastikan route ini ada
        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

        // Elemen UI
        const statusEl = document.getElementById('statusText');
        const msgEl = document.getElementById('msg');
        const flashBtn = document.getElementById('flash-toggle');
        const beep = document.getElementById('beepSound');

        let isProcessing = false;
        let html5QrCode = null;

        // --- 1. LOGIC SAAT BARCODE TERBACA ---
        function onScanSuccess(decodedText, decodedResult) {
            if (isProcessing) return; // Cegah spam

            // Validasi Panjang (Filter Barcode Sampah)
            if (decodedText.length < 5) return;

            isProcessing = true;

            // Efek UI: Sukses
            beep.play().catch(()=>{});
            statusEl.style.borderColor = "#00ff00";
            statusEl.style.color = "#00ff00";
            msgEl.innerText = "🚀 Mengirim: " + decodedText;

            // Kirim ke Laptop via Server
            fetch(routeProcess, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": csrfToken
                },
                body: JSON.stringify({ barcode: decodedText })
            })
            .then(res => res.json())
            .then(data => {
                console.log("Sukses:", data);
                // Jeda 1.5 detik
                setTimeout(() => {
                    resetUI();
                }, 1500);
            })
            .catch(err => {
                console.error("Error:", err);
                msgEl.innerText = "❌ Gagal Kirim!";
                statusEl.style.borderColor = "red";
                statusEl.style.color = "red";
                setTimeout(resetUI, 2000);
            });
        }

        function resetUI() {
            isProcessing = false;
            msgEl.innerText = "Siap Scan Barcode...";
            statusEl.style.borderColor = "rgba(255,255,255,0.3)";
            statusEl.style.color = "white";
        }

        // --- 2. SETUP KAMERA (TURBO MODE ala SPX) ---
        function startCamera() {
            html5QrCode = new Html5Qrcode("reader");

            const config = {
                fps: 20, // Super Cepat
                qrbox: { width: 250, height: 130 }, // Fokus area (Persegi Panjang)
                aspectRatio: 1.0,
                // Fitur Wajib: Hardware Acceleration
                experimentalFeatures: { useBarCodeDetectorIfSupported: true },
                // Format Lengkap (Barcode Barang + QR)
                formatsToSupport: [
                    Html5QrcodeSupportedFormats.EAN_13,
                    Html5QrcodeSupportedFormats.CODE_128,
                    Html5QrcodeSupportedFormats.QR_CODE
                ]
            };

            // Paksa Kamera Belakang
            html5QrCode.start({ facingMode: "environment" }, config, onScanSuccess)
            .then(() => {
                // Cek Kapabilitas Flash setelah kamera jalan
                checkFlashCapability();
            })
            .catch(err => {
                msgEl.innerText = "⚠️ Kamera Error: " + err;
                statusEl.style.borderColor = "red";
            });
        }

        // --- 3. LOGIC FLASH / SENTER ---
        function checkFlashCapability() {
            // Coba ambil track video yang sedang jalan
            const videoTrack = html5QrCode.getRunningTrackCameraCapabilities();

            // Cara lain deteksi via html5QrCode (Versi baru)
            // Biasanya kita cek manual via applyVideoConstraints

            // Kita munculkan saja tombolnya, nanti logic toggle yang menentukan jalan/tidaknya
            flashBtn.style.display = 'block';

            let isFlashOn = false;

            flashBtn.addEventListener('click', () => {
                if (html5QrCode.getState() === Html5QrcodeScannerState.SCANNING) {
                    isFlashOn = !isFlashOn;

                    html5QrCode.applyVideoConstraints({
                        advanced: [{ torch: isFlashOn }]
                    })
                    .then(() => {
                        flashBtn.innerText = isFlashOn ? "🔦 Flash: ON" : "🔦 Flash: OFF";
                        flashBtn.style.background = isFlashOn ? "rgba(255,255,255,0.8)" : "rgba(255,255,255,0.15)";
                        flashBtn.style.color = isFlashOn ? "black" : "white";
                    })
                    .catch(err => {
                        console.log("Flash tidak support di HP ini", err);
                        flashBtn.style.display = 'none'; // Sembunyikan jika gagal
                    });
                }
            });
        }

        // Jalankan Kamera
        startCamera();

    </script>
</body>
</html>
