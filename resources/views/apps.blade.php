<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sancaka Live Scanner</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    
    <script src="https://cdn.jsdelivr.net/npm/@tensorflow/tfjs"></script>
    <script src="https://cdn.jsdelivr.net/npm/@tensorflow-models/coco-ssd"></script>

    <style>
        body { background-color: #1a1a1a; color: white; }
        .camera-container {
            position: relative;
            width: 100%;
            max-width: 640px;
            margin: 0 auto;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0,0,0,0.5);
        }
        /* Video dan Canvas harus bertumpuk presisi */
        video {
            display: block;
            width: 100%;
            height: auto;
        }
        canvas {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 10;
        }
    </style>
</head>
<body class="min-h-screen flex flex-col items-center justify-center p-4">

    <div class="text-center mb-4">
        <h1 class="text-2xl font-bold text-blue-400">Sancaka Live AI</h1>
        <p class="text-xs text-gray-400">Mode Real-time (Client Side)</p>
    </div>

    <div class="camera-container ring-2 ring-blue-500">
        <video id="video" playsinline muted autoplay></video>
        <canvas id="canvas"></canvas>
        
        <div id="loading" class="absolute inset-0 bg-black flex flex-col items-center justify-center z-20">
            <div class="animate-spin rounded-full h-10 w-10 border-4 border-blue-500 border-t-transparent mb-3"></div>
            <p class="text-blue-400 font-semibold animate-pulse">Menyiapkan Otak AI...</p>
        </div>
    </div>

    <div class="mt-6 w-full max-w-md flex justify-center gap-4">
        <button id="btnStart" onclick="startApp()" class="hidden px-8 py-3 bg-green-600 rounded-full font-bold shadow-lg hover:bg-green-500 transition active:scale-95">
            Mulai Kamera
        </button>
        
        <button id="btnSwitch" onclick="switchCamera()" class="hidden px-6 py-3 bg-gray-700 rounded-full font-bold shadow-lg hover:bg-gray-600 transition active:scale-95 flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
            Ganti Kamera
        </button>
    </div>

    <div class="mt-4 bg-gray-800 p-3 rounded-lg text-xs text-gray-400 text-center max-w-xs">
        Mendeteksi: Orang, HP, Laptop, Botol, Kursi, dll.<br>
        <span class="text-yellow-500">*Tidak bisa membaca teks Resi secara spesifik di mode ini.</span>
    </div>

    <script>
        const video = document.getElementById('video');
        const canvas = document.getElementById('canvas');
        const ctx = canvas.getContext('2d');
        const loading = document.getElementById('loading');
        const btnStart = document.getElementById('btnStart');
        const btnSwitch = document.getElementById('btnSwitch');

        let model = undefined;
        let stream = undefined;
        let facingMode = 'environment'; // Kamera belakang
        let isDetecting = false;

        // 1. Load Model Saat Halaman Dibuka
        cocoSsd.load().then(loadedModel => {
            model = loadedModel;
            // Sembunyikan loading, munculkan tombol start
            loading.classList.add('hidden');
            btnStart.classList.remove('hidden');
            
            // Langsung coba start kamera jika user sudah pernah izinkan (opsional)
        }).catch(err => {
            alert("Gagal memuat model AI: " + err);
        });

        // 2. Fungsi Mulai Kamera
        async function startApp() {
            btnStart.classList.add('hidden');
            
            try {
                if (stream) {
                    stream.getTracks().forEach(track => track.stop());
                }

                stream = await navigator.mediaDevices.getUserMedia({
                    video: { 
                        facingMode: facingMode,
                        width: { ideal: 640 },
                        height: { ideal: 480 }
                    }, 
                    audio: false
                });

                video.srcObject = stream;

                // Tunggu video siap
                video.onloadedmetadata = () => {
                    video.play();
                    
                    // Sesuaikan ukuran canvas dengan video asli
                    canvas.width = video.videoWidth;
                    canvas.height = video.videoHeight;

                    btnSwitch.classList.remove('hidden');
                    
                    if (!isDetecting) {
                        isDetecting = true;
                        detectFrame(); // Mulai loop deteksi
                    }
                };

            } catch (err) {
                alert("Gagal akses kamera: " + err);
                btnStart.classList.remove('hidden');
            }
        }

        // 3. Loop Deteksi (Jantung Aplikasi Live)
        function detectFrame() {
            // Prediksi frame video saat ini
            model.detect(video).then(predictions => {
                renderPredictions(predictions);
                
                // Panggil fungsi ini lagi secepat mungkin (Loop)
                requestAnimationFrame(detectFrame);
            });
        }

        // 4. Gambar Kotak Hasil
        function renderPredictions(predictions) {
            // Bersihkan canvas dari frame sebelumnya
            ctx.clearRect(0, 0, canvas.width, canvas.height);

            predictions.forEach(prediction => {
                // Filter: Hanya tampilkan jika yakin > 60%
                if (prediction.score > 0.6) {
                    const [x, y, width, height] = prediction.bbox;
                    const text = prediction.class.toUpperCase();
                    
                    // Warna khusus
                    let color = '#00FF00'; // Hijau (Default)
                    if (text === 'PERSON') color = '#00FFFF'; // Cyan untuk Orang
                    if (text === 'CELL PHONE') color = '#FF00FF'; // Ungu untuk HP

                    // Gambar Kotak
                    ctx.strokeStyle = color;
                    ctx.lineWidth = 4;
                    ctx.strokeRect(x, y, width, height);

                    // Gambar Background Label
                    ctx.font = 'bold 18px Arial';
                    const textWidth = ctx.measureText(text).width;
                    const textHeight = 24;
                    
                    ctx.fillStyle = color;
                    ctx.fillRect(x, y > 24 ? y - 24 : 0, textWidth + 10, textHeight);

                    // Gambar Teks
                    ctx.fillStyle = '#000000';
                    ctx.fillText(text, x + 5, y > 24 ? y - 6 : 18);
                }
            });
        }

        // 5. Ganti Kamera
        function switchCamera() {
            facingMode = facingMode === 'user' ? 'environment' : 'user';
            startApp();
        }
    </script>
</body>
</html>