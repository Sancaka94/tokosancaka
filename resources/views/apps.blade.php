<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Sancaka Smart Auto-Scanner</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />

    <style>
        body { background-color: #111; color: white; }
        .camera-wrapper {
            position: relative;
            width: 100%;
            max-width: 640px;
            margin: 0 auto;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 0 20px rgba(0, 150, 255, 0.3);
        }
        video { width: 100%; height: auto; display: block; }
        canvas { position: absolute; top: 0; left: 0; width: 100%; height: 100%; z-index: 10; }
        
        /* Efek Garis Scanning Biar Keren */
        .scan-line {
            position: absolute;
            width: 100%;
            height: 4px;
            background: #00ff00;
            box-shadow: 0 0 15px #00ff00;
            animation: scanAnim 3s infinite linear;
            z-index: 5;
            opacity: 0.6;
            display: none; /* Muncul saat scanning */
        }
        @keyframes scanAnim {
            0% { top: 0%; }
            50% { top: 100%; }
            100% { top: 0%; }
        }
    </style>
</head>
<body class="min-h-screen flex flex-col items-center p-4">

    <div class="text-center mb-4 z-10">
        <h1 class="text-2xl font-bold text-blue-400">Smart Auto Scanner</h1>
        <p class="text-xs text-gray-400">Powered by Python & Laravel</p>
    </div>

    <div class="camera-wrapper border-2 border-gray-700 relative">
        <video id="video" autoplay playsinline muted></video>
        <canvas id="canvas"></canvas>
        <div id="scanLine" class="scan-line"></div>
        
        <div id="statusBadge" class="absolute top-4 right-4 bg-black/70 px-3 py-1 rounded-full text-xs font-mono hidden">
            <span class="w-2 h-2 rounded-full bg-green-500 inline-block mr-1 animate-pulse"></span>
            <span id="statusText">Standby</span>
        </div>

        <div id="overlayStart" class="absolute inset-0 bg-black/80 flex items-center justify-center z-20">
            <button onclick="startAutoScan()" class="bg-blue-600 hover:bg-blue-700 text-white px-8 py-3 rounded-full font-bold shadow-lg transition transform hover:scale-105 flex items-center gap-2">
                <i class="fas fa-camera"></i> Mulai Auto Scan
            </button>
        </div>
    </div>

    <div id="resultCard" class="mt-4 w-full max-w-md bg-gray-800 p-4 rounded-xl border-l-4 border-green-500 hidden">
        <h3 class="text-xs font-bold text-gray-400 uppercase mb-1">Data Terbaca:</h3>
        <p id="resultText" class="text-lg font-mono text-green-400 break-all">...</p>
    </div>

    <div class="mt-6 flex gap-4">
        <button onclick="switchCamera()" class="px-4 py-2 bg-gray-700 rounded-lg text-sm hover:bg-gray-600">
            <i class="fas fa-sync-alt"></i> Balik Kamera
        </button>
        <button onclick="stopAutoScan()" class="px-4 py-2 bg-red-900/50 text-red-300 rounded-lg text-sm hover:bg-red-900">
            <i class="fas fa-stop"></i> Stop
        </button>
    </div>

    <script>
        const video = document.getElementById('video');
        const canvas = document.getElementById('canvas');
        const ctx = canvas.getContext('2d');
        const overlayStart = document.getElementById('overlayStart');
        const scanLine = document.getElementById('scanLine');
        const statusBadge = document.getElementById('statusBadge');
        const statusText = document.getElementById('statusText');
        const resultCard = document.getElementById('resultCard');
        const resultText = document.getElementById('resultText');

        let stream;
        let facingMode = 'environment';
        let isProcessing = false; // Mencegah request bertumpuk
        let scanInterval;

        // 1. MULAI KAMERA & AUTO SCAN
        async function startAutoScan() {
            try {
                if (stream) stream.getTracks().forEach(t => t.stop());
                
                stream = await navigator.mediaDevices.getUserMedia({
                    video: { facingMode: facingMode, width: { ideal: 640 }, height: { ideal: 480 } },
                    audio: false
                });
                
                video.srcObject = stream;
                overlayStart.classList.add('hidden');
                scanLine.style.display = 'block';
                statusBadge.classList.remove('hidden');

                video.onloadedmetadata = () => {
                    canvas.width = video.videoWidth;
                    canvas.height = video.videoHeight;
                    
                    // MULAI LOOPING OTOMATIS (Setiap 3 Detik)
                    // Jangan ubah jadi di bawah 2000ms (2 detik) agar server aman!
                    scanInterval = setInterval(processFrame, 3000); 
                };

            } catch (err) {
                alert("Gagal akses kamera: " + err.message);
            }
        }

        // 2. PROSES FRAME (Kirim ke Python)
        async function processFrame() {
            // Jika masih memproses foto sebelumnya, skip dulu (biar gak macet)
            if (isProcessing) return; 

            isProcessing = true;
            statusText.innerText = "Menganalisa...";
            statusText.classList.add('text-yellow-400');

            // Ambil gambar dari video
            const tempCanvas = document.createElement('canvas');
            tempCanvas.width = video.videoWidth;
            tempCanvas.height = video.videoHeight;
            tempCanvas.getContext('2d').drawImage(video, 0, 0);
            const imageData = tempCanvas.toDataURL('image/jpeg', 0.7); // Kualitas 0.7 biar ringan

            try {
                const response = await fetch("{{ route('detection.process') }}", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ image: imageData })
                });

                const result = await response.json();

                if (result.status === 'success') {
                    drawBoxes(result.data);
                } else {
                    console.error("Server Error:", result);
                }

            } catch (err) {
                console.error("Koneksi Error:", err);
            } finally {
                isProcessing = false;
                statusText.innerText = "Standby";
                statusText.classList.remove('text-yellow-400');
            }
        }

        // 3. GAMBAR HASIL DETEKSI
        function drawBoxes(objects) {
            // Bersihkan canvas lama
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            
            let detectedText = [];

            objects.forEach(obj => {
                const [x1, y1, x2, y2] = obj.box;
                const width = x2 - x1;
                const height = y2 - y1;
                
                let color = '#00FF00'; // Default Hijau
                let label = obj.label;

                // Logika Warna & Label khusus
                if (obj.type === 'barcode') {
                    color = '#ff9900'; // Oranye untuk Resi
                    label = "📦 " + obj.text_content;
                    detectedText.push(obj.text_content);
                } else if (obj.type === 'face') {
                    color = '#00ccff'; // Biru untuk Wajah
                    label = "👤 Wajah";
                }

                // Gambar Kotak
                ctx.strokeStyle = color;
                ctx.lineWidth = 4;
                ctx.strokeRect(x1, y1, width, height);

                // Background Label
                ctx.font = "bold 16px Arial";
                const textWidth = ctx.measureText(label).width;
                ctx.fillStyle = color;
                ctx.fillRect(x1, y1 > 25 ? y1 - 25 : 0, textWidth + 10, 25);

                // Teks Label
                ctx.fillStyle = "black";
                ctx.fillText(label, x1 + 5, y1 > 25 ? y1 - 7 : 18);
            });

            // Tampilkan hasil teks resi di bawah jika ada
            if (detectedText.length > 0) {
                resultText.innerText = detectedText.join(', ');
                resultCard.classList.remove('hidden');
            } else {
                resultCard.classList.add('hidden');
            }
        }

        // 4. Utility Functions
        function stopAutoScan() {
            clearInterval(scanInterval);
            if (stream) stream.getTracks().forEach(t => t.stop());
            scanLine.style.display = 'none';
            overlayStart.classList.remove('hidden');
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            resultCard.classList.add('hidden');
        }

        function switchCamera() {
            facingMode = facingMode === 'user' ? 'environment' : 'user';
            clearInterval(scanInterval); // Stop dulu
            startAutoScan(); // Mulai lagi dengan kamera baru
        }
    </script>
</body>
</html>