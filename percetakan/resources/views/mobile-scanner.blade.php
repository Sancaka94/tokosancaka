<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Scanner Sancaka v2 (Strict)</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>

    <style>
        body { background-color: #000; overflow: hidden; }
        #reader { width: 100%; height: 100vh; object-fit: cover; }

        /* Matikan UI Bawaan Library yang jelek */
        #reader__dashboard_section_csr span,
        #reader__dashboard_section_swaplink,
        #reader__scan_region img { display: none !important; }

        /* Kotak Fokus Murni (Tanpa garis hijau library) */
        .scan-overlay {
            position: absolute; top: 50%; left: 50%;
            transform: translate(-50%, -50%);
            width: 280px; height: 130px; /* Lebar & Pendek (Khusus EAN) */
            border: 3px solid #00ff00; /* Hijau terang biar jelas */
            border-radius: 8px;
            box-shadow: 0 0 0 9999px rgba(0, 0, 0, 0.7); /* Gelap total sekeliling */
            z-index: 10;
        }

        /* Garis Laser Merah */
        .scan-line {
            position: absolute; top: 0; left: 0; width: 100%; height: 3px;
            background: red; box-shadow: 0 0 10px red;
            animation: scanMove 1.2s infinite ease-in-out;
        }
        @keyframes scanMove { 0% { top: 10%; opacity: 0; } 50% { opacity: 1; } 100% { top: 90%; opacity: 0; } }

        /* Tombol Flash & Status */
        .controls {
            position: absolute; bottom: 50px; left: 0; width: 100%;
            display: flex; justify-content: center; gap: 20px; z-index: 20;
        }
        .btn-flash {
            background: rgba(255,255,255,0.2); border: 1px solid white; color: white;
            padding: 15px 25px; border-radius: 50px; font-weight: bold; backdrop-filter: blur(5px);
        }
        .status-badge {
            position: absolute; top: 30px; left: 50%; transform: translateX(-50%);
            background: rgba(0,0,0,0.8); padding: 8px 20px; border-radius: 20px;
            color: #fff; font-weight: bold; z-index: 20; border: 1px solid #555;
            white-space: nowrap; font-family: monospace;
        }
    </style>
</head>
<body>

    <div class="status-badge" id="statusText">📷 FOKUS BARCODE...</div>

    <div class="scan-overlay">
        <div class="scan-line"></div>
    </div>

    <div id="reader"></div>

    <div class="controls">
        <button id="flashBtn" class="btn-flash">🔦 Senter: OFF</button>
    </div>

    <audio id="beepSound" src="https://www.soundjay.com/button/beep-07.wav"></audio>

    <script>
        let isProcessing = false;
        let html5QrCode;
        const beep = document.getElementById('beepSound');
        const statusText = document.getElementById('statusText');
        const flashBtn = document.getElementById('flashBtn');

        // Fungsi Filter Angka Ngawur
        function isValidBarcode(code) {
            // Barcode barang itu panjangnya 8, 12, atau 13 digit.
            // Kalau panjangnya aneh (misal 5 digit atau 20 digit) pasti salah baca.
            return code.length === 8 || code.length === 12 || code.length === 13;
        }

        function onScanSuccess(decodedText, decodedResult) {
            // Filter 1: Cegah spam
            if (isProcessing) return;

            // Filter 2: Buang angka ngawur (Validasi panjang karakter)
            if (!isValidBarcode(decodedText)) {
                console.warn("Sampah dibuang:", decodedText);
                return;
            }

            isProcessing = true;

            // Efek Sukses
            beep.play().catch(()=>{});
            statusText.innerHTML = "✅ " + decodedText;
            statusText.style.borderColor = "#00ff00";
            statusText.style.color = "#00ff00";
            document.querySelector('.scan-overlay').style.borderColor = "#00ff00";

            // Kirim ke Laptop
            fetch("{{ route('scanner.process') }}", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({ barcode: decodedText })
            })
            .then(res => res.json())
            .then(data => {
                setTimeout(() => {
                    isProcessing = false;
                    statusText.innerHTML = "📷 SIAP SCAN";
                    statusText.style.borderColor = "#555";
                    statusText.style.color = "white";
                    document.querySelector('.scan-overlay').style.borderColor = "white";
                }, 1000);
            })
            .catch(err => {
                statusText.innerHTML = "❌ Gagal Kirim";
                isProcessing = false;
            });
        }

        // --- KONFIGURASI STRICT (HANYA EAN-13) ---
        html5QrCode = new Html5Qrcode("reader");

        const config = {
            fps: 20,
            qrbox: { width: 250, height: 120 }, // Kotak lebih gepeng sesuai barcode
            aspectRatio: 1.0,
            // PENTING: Matikan 'useBarCodeDetectorIfSupported' dulu kalau hasilnya ngawur di HP tertentu
            // experimentalFeatures: { useBarCodeDetectorIfSupported: true },

            // HANYA EAN (Produk Retail) & UPC. Matikan Code 128/39 yang sering bikin error
            formatsToSupport: [
                Html5QrcodeSupportedFormats.EAN_13,
                Html5QrcodeSupportedFormats.EAN_8,
                Html5QrcodeSupportedFormats.UPC_A
            ]
        };

        html5QrCode.start({ facingMode: "environment" }, config, onScanSuccess)
        .then(() => {
            // Aktifkan Tombol Flash setelah kamera jalan
            flashBtn.addEventListener('click', () => {
                html5QrCode.getState() === Html5QrcodeScannerState.SCANNING
                ? html5QrCode.applyVideoConstraints({ advanced: [{ torch: true }] }) // Coba nyalakan
                  .then(() => flashBtn.innerText = "🔦 Senter: ON")
                  .catch(() => {
                      // Jika torch gagal (biasanya karena belum toggle off dulu)
                      html5QrCode.applyVideoConstraints({ advanced: [{ torch: false }] });
                      flashBtn.innerText = "🔦 Senter: OFF";
                  })
                : null;
            });
        })
        .catch(err => {
            statusText.innerHTML = "⚠️ Kamera Error: " + err;
        });
    </script>
</body>
</html>
