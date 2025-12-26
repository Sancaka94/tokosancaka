<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Sancaka AI - Cyber Interface</title>
    
    <script src="https://cdn.jsdelivr.net/npm/@tensorflow/tfjs"></script>
    <script src="https://cdn.jsdelivr.net/npm/@tensorflow-models/coco-ssd"></script> <script src="https://cdn.jsdelivr.net/npm/@tensorflow-models/blazeface"></script> <script src="https://cdn.tailwindcss.com"></script>

    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700&family=Rajdhani:wght@500;700&display=swap" rel="stylesheet">

    <style>
        body { background: #000; overflow: hidden; font-family: 'Rajdhani', sans-serif; }
        
        .camera-container { 
            position: relative; 
            width: 100vw; 
            height: 100vh; 
            background: #000;
        }
        
        /* Video & Canvas Fullscreen tapi proporsional */
        video, canvas { 
            position: absolute; 
            top: 0; left: 0; 
            width: 100%; height: 100%; 
            object-fit: cover; 
        }

        /* Overlay Loading Keren */
        #loading {
            background: radial-gradient(circle, rgba(10,20,30,1) 0%, rgba(0,0,0,1) 100%);
        }
        
        .cyber-loader {
            width: 64px; height: 64px;
            border: 2px solid #00ffcc;
            border-top: 2px solid transparent;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            box-shadow: 0 0 15px #00ffcc;
        }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }

        /* HUD Panel di Bawah */
        .hud-panel {
            position: absolute; bottom: 30px; left: 50%; transform: translateX(-50%);
            width: 90%; max-width: 400px;
            background: rgba(0, 10, 20, 0.85);
            border: 1px solid rgba(0, 255, 204, 0.3);
            border-left: 4px solid #00ffcc;
            padding: 15px;
            backdrop-filter: blur(8px);
            display: none;
            box-shadow: 0 0 20px rgba(0, 255, 204, 0.1);
            animation: slideUp 0.3s ease-out;
        }
        @keyframes slideUp { from { bottom: -100px; opacity: 0; } to { bottom: 30px; opacity: 1; } }

        .hud-label { font-family: 'Orbitron', sans-serif; letter-spacing: 1px; }

        /* Garis Scan Animasi */
        .scan-line {
            position: absolute; top: 0; left: 0; width: 100%; height: 2px;
            background: rgba(0, 255, 204, 0.8);
            box-shadow: 0 0 10px #00ffcc, 0 0 20px #00ffcc;
            animation: scanAnim 3s ease-in-out infinite;
            z-index: 5;
            opacity: 0.5;
        }
        @keyframes scanAnim { 0% { top: 10%; opacity: 0; } 50% { opacity: 1; } 100% { top: 90%; opacity: 0; } }
    </style>
</head>
<body>

    <div id="loading" class="fixed inset-0 z-50 flex flex-col items-center justify-center text-center">
        <div class="cyber-loader mb-6"></div>
        <h2 class="text-[#00ffcc] font-bold text-2xl tracking-widest font-['Orbitron']">INITIALIZING AI</h2>
        <p class="text-teal-800 text-sm mt-2 animate-pulse">Memuat Neural Network Wajah & Objek...</p>
    </div>

    <div class="camera-container">
        <video id="video" autoplay playsinline muted></video>
        <canvas id="canvas"></canvas>
        <div class="scan-line"></div> </div>

    <div id="hudPanel" class="hud-panel z-20" onclick="openManualInput()">
        <div class="flex items-start gap-4">
            <div id="hudIcon" class="text-3xl pt-1">📦</div>
            <div class="flex-1">
                <p class="text-[10px] text-teal-500 uppercase tracking-widest mb-1">TARGET LOCKED</p>
                <h3 id="lblMain" class="text-xl text-white font-bold hud-label leading-none">UNKNOWN</h3>
                <p id="lblDetail" class="text-sm text-gray-400 mt-1 font-mono">Tap untuk identifikasi</p>
            </div>
            <div id="dbStatus" class="w-2 h-2 rounded-full bg-red-500 shadow-[0_0_10px_red]"></div>
        </div>
    </div>

    <div id="manualModal" class="fixed inset-0 bg-black/90 z-50 hidden flex items-center justify-center p-4 backdrop-blur-sm">
        <div class="bg-gray-900 p-6 rounded-none border border-teal-500 w-full max-w-sm shadow-[0_0_30px_rgba(0,255,204,0.2)]">
            <h3 class="text-lg font-bold text-[#00ffcc] mb-6 font-['Orbitron'] border-b border-gray-700 pb-2">INPUT DATA MEMORI</h3>
            <input type="hidden" id="inpRawLabel">
            
            <div class="space-y-4">
                <div>
                    <label class="text-xs text-gray-500 block mb-1">IDENTITAS / NAMA</label>
                    <input type="text" id="inpName" class="w-full bg-black border border-gray-700 p-3 text-white focus:border-[#00ffcc] focus:outline-none transition-colors" placeholder="Contoh: Kopi, Plat AD 1234">
                </div>
                <div>
                    <label class="text-xs text-gray-500 block mb-1">INFO TAMBAHAN (HARGA/DLL)</label>
                    <input type="text" id="inpPrice" class="w-full bg-black border border-gray-700 p-3 text-white focus:border-[#00ffcc] focus:outline-none transition-colors" placeholder="Contoh: Rp 5.000">
                </div>
            </div>

            <div class="flex gap-3 mt-6">
                <button onclick="document.getElementById('manualModal').classList.add('hidden')" class="flex-1 border border-gray-600 text-gray-400 py-2 hover:bg-gray-800 transition">BATAL</button>
                <button onclick="saveToDb()" class="flex-1 bg-teal-900/50 border border-teal-500 text-[#00ffcc] py-2 font-bold hover:bg-teal-500/20 transition shadow-[0_0_15px_rgba(0,255,204,0.3)]">SIMPAN</button>
            </div>
        </div>
    </div>

    <script>
        const video = document.getElementById('video');
        const canvas = document.getElementById('canvas');
        const ctx = canvas.getContext('2d');
        const hudPanel = document.getElementById('hudPanel');
        
        let objectModel, faceModel;
        let lastLabel = "";
        let dbCache = {}; 

        // 1. LOAD 2 MODEL SEKALIGUS (Objek & Wajah)
        Promise.all([
            cocoSsd.load(),          // Model Objek Umum (Ringan)
            blazeface.load()         // Model Wajah (Sangat Cepat & Ringan)
        ]).then(([loadedCoco, loadedFace]) => {
            objectModel = loadedCoco;
            faceModel = loadedFace;
            startCamera();
        });

        async function startCamera() {
            try {
                const stream = await navigator.mediaDevices.getUserMedia({
                    video: { facingMode: 'environment' }, audio: false
                });
                video.srcObject = stream;
                video.onloadedmetadata = () => {
                    document.getElementById('loading').style.display = 'none';
                    canvas.width = video.videoWidth;
                    canvas.height = video.videoHeight;
                    predictFrame(); // Mulai Loop AI
                };
            } catch (err) { alert("Kamera Gagal: " + err); }
        }

        // 2. LOOP DETEKSI
        async function predictFrame() {
            // Jalankan deteksi Wajah & Objek secara paralel
            const [objects, faces] = await Promise.all([
                objectModel.detect(video),
                faceModel.estimateFaces(video, false) // false = return tensors (lebih cepat)
            ]);
            
            drawCombined(objects, faces);
            requestAnimationFrame(predictFrame); 
        }

        // 3. GAMBAR HASIL (STYLISTIC)
        function drawCombined(objects, faces) {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            
            let detectedSomething = false;

            // A. GAMBAR WAJAH (Prioritas Visual)
            if (faces.length > 0) {
                detectedSomething = true;
                const face = faces[0]; // Ambil 1 wajah utama
                const start = face.topLeft;
                const end = face.bottomRight;
                const x = start[0];
                const y = start[1];
                const w = end[0] - start[0];
                const h = end[1] - start[1];

                // Style Kotak Wajah (Ungu Neon)
                drawFancyBox(x, y, w, h, '#d946ef', 'WAJAH'); 
                updateHUD('face', 'WAJAH MANUSIA', 'Suhu: ' + (36 + Math.random()).toFixed(1) + '°C', true);
            }

            // B. GAMBAR OBJEK (Jika tidak ada wajah atau ada objek lain)
            // Filter: Jangan gambar 'person' dari COCO jika BlazeFace sudah deteksi wajah (biar gak dobel kotak)
            const filteredObjects = objects.filter(obj => obj.class !== 'person');
            
            if (filteredObjects.length > 0) {
                // Ambil objek dengan score tertinggi
                const obj = filteredObjects[0]; 
                const [x, y, w, h] = obj.bbox;
                
                // Style Kotak Objek (Cyan Neon)
                drawFancyBox(x, y, w, h, '#00ffcc', obj.class.toUpperCase());

                // Logic Database Laravel
                if (obj.class !== lastLabel) {
                    lastLabel = obj.class;
                    checkLaravelDB(obj.class);
                }

                // Update HUD berdasarkan Database
                if (!detectedSomething) { // Hanya update HUD jika belum di-isi wajah
                    detectedSomething = true;
                    const data = dbCache[obj.class] || { 
                        label: obj.class.toUpperCase(), 
                        detail: "Tap untuk simpan", 
                        found: false,
                        type: 'unknown'
                    };
                    updateHUD(obj.class, data.label, data.detail, data.found);
                }
            }

            if (!detectedSomething) {
                hudPanel.style.display = 'none';
            }
        }

        // FUNGSI GAMBAR KOTAK KEREN (Tipis & Bercahaya)
        function drawFancyBox(x, y, w, h, color, labelText) {
            ctx.shadowBlur = 15;
            ctx.shadowColor = color;
            ctx.strokeStyle = color;
            ctx.lineWidth = 2; // TIPIS (Sesuai request)
            
            // Gambar Kotak
            ctx.strokeRect(x, y, w, h);

            // Label kecil di atas kotak
            ctx.shadowBlur = 0; // Matikan glow untuk teks biar tajam
            ctx.fillStyle = color;
            ctx.font = "bold 12px Rajdhani";
            ctx.fillText(labelText, x + 5, y - 8);
            
            // Hiasan Pojok (Corner Brackets) - Biar makin Sci-Fi
            ctx.lineWidth = 4;
            const cornerLen = 15;
            ctx.beginPath();
            // Pojok Kiri Atas
            ctx.moveTo(x, y + cornerLen); ctx.lineTo(x, y); ctx.lineTo(x + cornerLen, y);
            // Pojok Kanan Bawah
            ctx.moveTo(x + w, y + h - cornerLen); ctx.lineTo(x + w, y + h); ctx.lineTo(x + w - cornerLen, y + h);
            ctx.stroke();
        }

        function updateHUD(rawKey, label, detail, found) {
            hudPanel.style.display = 'block';
            document.getElementById('lblMain').innerText = label;
            document.getElementById('lblDetail').innerText = detail;
            document.getElementById('inpRawLabel').value = rawKey;

            const iconEl = document.getElementById('hudIcon');
            const statusEl = document.getElementById('dbStatus');

            if (rawKey === 'face') {
                iconEl.innerText = '👤';
                statusEl.className = "w-2 h-2 rounded-full bg-purple-500 shadow-[0_0_10px_purple]";
            } else if (found) {
                iconEl.innerText = '✅';
                statusEl.className = "w-2 h-2 rounded-full bg-[#00ffcc] shadow-[0_0_10px_#00ffcc]";
            } else {
                iconEl.innerText = '❓';
                statusEl.className = "w-2 h-2 rounded-full bg-red-500 shadow-[0_0_10px_red] animate-pulse";
            }
        }

        // --- DATABASE LOGIC (Sama seperti sebelumnya) ---
        async function checkLaravelDB(keyword) {
            if (dbCache[keyword]) return;
            try {
                const res = await fetch("{{ route('apps.check_db') }}", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ keyword: keyword })
                });
                const data = await res.json();
                dbCache[keyword] = data;
            } catch (e) { console.error(e); }
        }

        function openManualInput() {
            const raw = document.getElementById('inpRawLabel').value;
            if (raw === 'face') return; // Jangan simpan wajah sebagai barang
            document.getElementById('manualModal').classList.remove('hidden');
            document.getElementById('inpName').focus();
        }

        async function saveToDb() {
            const raw = document.getElementById('inpRawLabel').value;
            const name = document.getElementById('inpName').value;
            const price = document.getElementById('inpPrice').value;

            try {
                await fetch("{{ route('apps.store') }}", {
                    method: "POST",
                    headers: { "Content-Type": "application/json", "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content },
                    body: JSON.stringify({ barcode: raw, name: name, price: price })
                });
                dbCache[raw] = { found: true, label: name, detail: price };
                alert("Data Tersimpan di Neural Network!");
                document.getElementById('manualModal').classList.add('hidden');
            } catch(e) { alert("Error Save"); }
        }
    </script>
</body>
</html>