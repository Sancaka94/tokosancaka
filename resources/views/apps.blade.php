<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Sancaka AI - Auto Mode</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />

    <style>
        body { background-color: #0f172a; color: white; }
        .camera-container {
            position: relative;
            width: 100%;
            max-width: 640px;
            margin: 0 auto;
            border-radius: 16px;
            overflow: hidden;
            border: 1px solid #334155; /* Border container tipis aja */
            box-shadow: 0 10px 30px -10px rgba(0, 0, 0, 0.5);
        }
        video { width: 100%; display: block; }
        canvas { position: absolute; top: 0; left: 0; width: 100%; height: 100%; pointer-events: none; }
        
        /* Garis Scan Animasi */
        .scan-laser {
            position: absolute;
            width: 100%;
            height: 2px;
            background: #00ff00;
            box-shadow: 0 0 10px #00ff00;
            animation: scanDown 2.5s infinite linear;
            z-index: 5;
            opacity: 0.6;
            display: none;
        }
        @keyframes scanDown {
            0% { top: 0%; opacity: 0; }
            10% { opacity: 1; }
            90% { opacity: 1; }
            100% { top: 100%; opacity: 0; }
        }
    </style>
</head>
<body class="min-h-screen flex flex-col items-center p-4 font-sans">

    <div class="text-center mb-6">
        <h1 class="text-xl font-bold text-white tracking-wide">
            Sancaka AI Scanner
        </h1>
        <p class="text-[10px] text-slate-400 uppercase tracking-widest">Auto Detect Mode</p>
    </div>

    <div class="camera-container">
        <video id="video" autoplay playsinline muted></video>
        <canvas id="canvas"></canvas>
        <div id="scanLaser" class="scan-laser"></div>

        <div class="absolute top-3 right-3 bg-black/60 backdrop-blur px-3 py-1 rounded-full border border-white/10 flex items-center gap-2 shadow-sm">
            <div id="statusDot" class="w-1.5 h-1.5 rounded-full bg-gray-500"></div>
            <span id="statusText" class="text-[10px] font-mono text-gray-300 uppercase">Idle</span>
        </div>

        <div id="startOverlay" class="absolute inset-0 bg-slate-900/95 flex flex-col items-center justify-center z-20">
            <button onclick="startAutoMode()" class="bg-blue-600 hover:bg-blue-500 text-white px-8 py-3 rounded-xl font-bold shadow-lg transition transform hover:scale-105 flex items-center gap-2">
                <i class="fas fa-play"></i> Mulai
            </button>
            <p class="text-slate-500 text-xs mt-4">Kamera akan memotret otomatis.</p>
        </div>
    </div>

    <div id="resultBox" class="mt-4 w-full max-w-md hidden">
        <div class="bg-slate-800 border border-slate-700 p-3 rounded-lg flex items-center gap-3 shadow-lg">
            <div class="bg-green-500/10 p-2 rounded-md">
                <i class="fas fa-qrcode text-green-500 text-xl"></i>
            </div>
            <div class="overflow-hidden">
                <h3 class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Terdeteksi</h3>
                <p id="resultText" class="text-sm font-mono text-white font-bold break-all leading-tight">...</p>
            </div>
        </div>
    </div>

    <button onclick="switchCamera()" class="mt-6 px-5 py-2 bg-slate-800 hover:bg-slate-700 border border-slate-700 rounded-full text-xs font-medium transition text-slate-300 flex items-center gap-2">
        <i class="fas fa-sync-alt text-gray-400"></i> Ganti Kamera
    </button>

    <script>
        const video = document.getElementById('video');
        const canvas = document.getElementById('canvas');
        const ctx = canvas.getContext('2d');
        const scanLaser = document.getElementById('scanLaser');
        const statusDot = document.getElementById('statusDot');
        const statusText = document.getElementById('statusText');
        const startOverlay = document.getElementById('startOverlay');
        const resultBox = document.getElementById('resultBox');
        const resultText = document.getElementById('resultText');

        let stream;
        let facingMode = 'environment'; // Kamera belakang
        let scanInterval;
        let isProcessing = false;

        // 1. Mulai Aplikasi
        async function startAutoMode() {
            try {
                if (stream) stream.getTracks().forEach(t => t.stop());
                
                stream = await navigator.mediaDevices.getUserMedia({
                    video: { facingMode: facingMode, width: { ideal: 640 }, height: { ideal: 480 } },
                    audio: false
                });
                
                video.srcObject = stream;
                startOverlay.classList.add('hidden');
                scanLaser.style.display = 'block';

                video.onloadedmetadata = () => {
                    canvas.width = video.videoWidth;
                    canvas.height = video.videoHeight;
                    
                    // MULAI LOOP OTOMATIS (Jeda 3 Detik)
                    scanInterval = setInterval(captureAndSend, 3000);
                    
                    updateStatus('Ready', 'green');
                };

            } catch (err) {
                alert("Gagal akses kamera: " + err.message);
            }
        }

        // 2. Fungsi Foto & Kirim ke Python
        async function captureAndSend() {
            if (isProcessing) return;
            
            isProcessing = true;
            updateStatus('Scanning...', 'yellow');

            const tempCanvas = document.createElement('canvas');
            tempCanvas.width = video.videoWidth;
            tempCanvas.height = video.videoHeight;
            const tCtx = tempCanvas.getContext('2d');
            tCtx.drawImage(video, 0, 0);
            
            const imageData = tempCanvas.toDataURL('image/jpeg', 0.6); // Kualitas 0.6 (lebih ringan)

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
                    drawResult(result.data);
                    updateStatus('OK', 'blue');
                } else {
                    console.error("Server Error:", result);
                    updateStatus('Error', 'red');
                }

            } catch (err) {
                console.error("Network Error:", err);
                updateStatus('Retry', 'red');
            } finally {
                isProcessing = false;
                setTimeout(() => {
                    if(!isProcessing) updateStatus('Ready', 'green');
                }, 1000);
            }
        }

        // 3. Gambar Kotak (YANG DIUBAH DI SINI)
        function drawResult(objects) {
            ctx.clearRect(0, 0, canvas.width, canvas.height); 
            
            let foundData = [];

            if (objects.length === 0) return;

            objects.forEach(obj => {
                const [x1, y1, x2, y2] = obj.box;
                const width = x2 - x1;
                const height = y2 - y1;

                let color = '#22c55e'; // Hijau
                let label = obj.label;

                if (obj.type === 'barcode') {
                    color = '#f59e0b'; // Orange
                    label = obj.text_content;
                    foundData.push(obj.text_content);
                } else if (obj.type === 'face') {
                    color = '#3b82f6'; // Biru
                    label = "Face";
                }

                // --- SETTINGAN BARU: GARIS TIPIS ---
                ctx.strokeStyle = color;
                ctx.lineWidth = 2; // TEBAL GARIS (Dulu 4, sekarang 2)
                ctx.strokeRect(x1, y1, width, height);

                // --- LABEL RAPI ---
                ctx.font = "bold 12px Arial"; // Font lebih kecil (12px)
                const textWidth = ctx.measureText(label).width;
                
                // Background label pas sesuai teks
                ctx.fillStyle = color;
                ctx.fillRect(x1, y1 > 20 ? y1 - 20 : 0, textWidth + 8, 20);

                // Teks Label
                ctx.fillStyle = "#000"; // Tulisan Hitam
                ctx.fillText(label, x1 + 4, y1 > 20 ? y1 - 5 : 14);
            });

            // Tampilkan data resi di kotak bawah
            if (foundData.length > 0) {
                resultText.innerText = foundData.join(', ');
                resultBox.classList.remove('hidden');
            }
        }

        function switchCamera() {
            facingMode = facingMode === 'user' ? 'environment' : 'user';
            clearInterval(scanInterval);
            startAutoMode();
        }

        function updateStatus(text, colorName) {
            statusText.innerText = text;
            const colors = {
                'green': 'bg-green-500',
                'yellow': 'bg-yellow-500',
                'red': 'bg-red-500',
                'blue': 'bg-blue-500'
            };
            statusDot.className = `w-1.5 h-1.5 rounded-full ${colors[colorName] || 'bg-gray-500'} ${colorName === 'green' ? 'animate-pulse' : ''}`;
        }
    </script>
</body>
</html>