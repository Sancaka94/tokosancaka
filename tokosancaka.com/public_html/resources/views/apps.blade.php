<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>AI Super Scanner - Toko Sancaka</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />

    <style>
        /* Container Kamera agar Responsif */
        .camera-wrapper {
            position: relative;
            width: 100%;
            max-width: 640px;
            aspect-ratio: 3/4; /* Rasio Portrait HP */
            background: #000;
            border-radius: 16px;
            overflow: hidden;
            margin: 0 auto;
        }
        video, canvas {
            position: absolute;
            top: 0; 
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .scan-line {
            position: absolute;
            width: 100%;
            height: 2px;
            background: #00ff00;
            box-shadow: 0 0 10px #00ff00;
            animation: scan 2s infinite linear;
            z-index: 10;
            display: none;
        }
        @keyframes scan {
            0% { top: 0%; }
            50% { top: 100%; }
            100% { top: 0%; }
        }
    </style>
</head>
<body class="bg-gray-100 min-h-screen flex flex-col font-sans">

    <nav class="bg-white shadow p-4 sticky top-0 z-50">
        <div class="max-w-md mx-auto flex justify-between items-center">
            <h1 class="font-bold text-lg text-blue-600">
                <i class="fas fa-robot mr-2"></i>Sancaka AI
            </h1>
            <span class="text-xs text-gray-500 bg-gray-100 px-2 py-1 rounded">Server Mode</span>
        </div>
    </nav>

    <main class="flex-grow p-4 flex flex-col items-center justify-center gap-4">

        <div class="camera-wrapper shadow-2xl ring-4 ring-white relative">
            <video id="video" autoplay playsinline muted></video>
            <canvas id="canvas"></canvas>
            
            <div id="scanLine" class="scan-line"></div>

            <div id="loading" class="absolute inset-0 bg-black/50 hidden flex-col items-center justify-center z-20">
                <div class="animate-spin rounded-full h-12 w-12 border-4 border-blue-500 border-t-transparent"></div>
                <p class="text-white mt-2 font-semibold text-sm">Menganalisa...</p>
            </div>
            
            <div id="permissionMsg" class="absolute inset-0 flex items-center justify-center text-white text-center p-6 bg-gray-800 z-30">
                <div>
                    <i class="fas fa-camera-slash text-4xl mb-3 text-gray-400"></i>
                    <p>Klik "Mulai Kamera" untuk mengizinkan akses.</p>
                </div>
            </div>
        </div>

        <div class="w-full max-w-md grid grid-cols-2 gap-3">
            <button id="btnStart" onclick="startCamera()" class="col-span-2 bg-blue-600 text-white py-3 rounded-xl font-bold hover:bg-blue-700 transition shadow-lg active:scale-95">
                <i class="fas fa-power-off mr-2"></i> Mulai Kamera
            </button>
            
            <button id="btnSwitch" onclick="switchCamera()" class="hidden bg-gray-200 text-gray-700 py-3 rounded-xl font-bold hover:bg-gray-300 transition active:scale-95">
                <i class="fas fa-sync-alt mr-2"></i> Balik
            </button>
            
            <button id="btnScan" onclick="processImage()" class="hidden bg-green-500 text-white py-3 rounded-xl font-bold hover:bg-green-600 transition shadow-lg active:scale-95">
                <i class="fas fa-expand mr-2"></i> Deteksi
            </button>
        </div>

        <div id="resultCard" class="hidden w-full max-w-md bg-white p-4 rounded-xl shadow border-l-4 border-green-500 mt-2">
            <h3 class="text-xs font-bold text-gray-400 uppercase">Terdeteksi:</h3>
            <p id="resultText" class="text-sm font-mono text-gray-800 break-all mt-1">...</p>
        </div>

    </main>

    <script>
        const video = document.getElementById('video');
        const canvas = document.getElementById('canvas');
        const ctx = canvas.getContext('2d');
        const loading = document.getElementById('loading');
        const scanLine = document.getElementById('scanLine');
        const resultCard = document.getElementById('resultCard');
        const resultText = document.getElementById('resultText');
        const permissionMsg = document.getElementById('permissionMsg');
        
        // Buttons
        const btnStart = document.getElementById('btnStart');
        const btnSwitch = document.getElementById('btnSwitch');
        const btnScan = document.getElementById('btnScan');

        let stream;
        let facingMode = 'environment'; // Default kamera belakang

        // 1. MULAI KAMERA
        async function startCamera() {
            try {
                if (stream) stream.getTracks().forEach(t => t.stop());
                
                stream = await navigator.mediaDevices.getUserMedia({
                    video: { facingMode: facingMode, width: { ideal: 640 }, height: { ideal: 480 } },
                    audio: false
                });
                
                video.srcObject = stream;
                permissionMsg.classList.add('hidden');
                
                // Update UI setelah kamera nyala
                btnStart.classList.add('hidden');
                btnSwitch.classList.remove('hidden');
                btnScan.classList.remove('hidden');
                
                // Set ukuran canvas saat video siap
                video.onloadedmetadata = () => {
                    canvas.width = video.videoWidth;
                    canvas.height = video.videoHeight;
                };

            } catch (err) {
                alert("Gagal akses kamera: " + err.message);
            }
        }

        // 2. GANTI KAMERA
        function switchCamera() {
            facingMode = facingMode === 'user' ? 'environment' : 'user';
            startCamera();
        }

        // 3. PROSES GAMBAR (Kirim ke Server Laravel)
        async function processImage() {
            // Visual feedback
            loading.classList.remove('hidden');
            scanLine.style.display = 'block';
            ctx.clearRect(0, 0, canvas.width, canvas.height); // Bersihkan kotak lama
            resultCard.classList.add('hidden');

            // Tangkap gambar dari video
            const tempCanvas = document.createElement('canvas');
            tempCanvas.width = video.videoWidth;
            tempCanvas.height = video.videoHeight;
            const tempCtx = tempCanvas.getContext('2d');
            tempCtx.drawImage(video, 0, 0, tempCanvas.width, tempCanvas.height);
            
            // Konversi ke Base64 (JPG quality 0.8 agar ringan diupload)
            const imageData = tempCanvas.toDataURL('image/jpeg', 0.8);

            try {
                // Kirim AJAX POST
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
                    alert('Error: ' + result.message);
                }

            } catch (err) {
                console.error(err);
                alert("Gagal menghubungi server AI.");
            } finally {
                loading.classList.add('hidden');
                scanLine.style.display = 'none';
            }
        }

        // 4. GAMBAR KOTAK HASIL
        function drawBoxes(objects) {
            if (objects.length === 0) {
                alert("Tidak ada objek yang dikenali.");
                return;
            }

            let foundText = [];

            objects.forEach(obj => {
                const [x1, y1, x2, y2] = obj.box;
                const width = x2 - x1;
                const height = y2 - y1;

                // Tentukan Warna Berdasarkan Tipe (Sesuai Script Python)
                let color = '#00FF00'; // Default Hijau (YOLO Object)
                let labelPrefix = '';

                if (obj.type === 'face') {
                    color = '#00AAFF'; // Biru (Wajah)
                } else if (obj.type === 'barcode') {
                    color = '#FF4500'; // Merah Oranye (Resi/Barcode)
                    labelPrefix = '📦 ';
                    foundText.push(obj.text_content); // Simpan isi resi
                }

                // Gambar Kotak
                ctx.strokeStyle = color;
                ctx.lineWidth = 3;
                ctx.strokeRect(x1, y1, width, height);

                // Gambar Background Label
                const text = `${labelPrefix}${obj.label} (${Math.round(obj.confidence * 100)}%)`;
                ctx.font = "bold 16px Arial";
                const textWidth = ctx.measureText(text).width;
                
                ctx.fillStyle = color;
                ctx.fillRect(x1, y1 > 25 ? y1 - 25 : 0, textWidth + 10, 25);

                // Tulis Teks
                ctx.fillStyle = "#FFFFFF";
                ctx.fillText(text, x1 + 5, y1 > 25 ? y1 - 7 : 18);
            });

            // Jika ada resi/barcode, tampilkan di bawah
            if (foundText.length > 0) {
                resultText.innerText = foundText.join(', ');
                resultCard.classList.remove('hidden');
            }
        }
    </script>
</body>
</html>