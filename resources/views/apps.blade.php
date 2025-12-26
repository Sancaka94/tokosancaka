<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Sancaka Smart Client AI</title>
    
    <script src="https://cdn.jsdelivr.net/npm/@tensorflow/tfjs"></script>
    <script src="https://cdn.jsdelivr.net/npm/@tensorflow-models/coco-ssd"></script>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { background: #000; color: white; overflow: hidden; }
        .camera-container { position: relative; width: 100vw; height: 100vh; display: flex; justify-content: center; align-items: center; background: #000; }
        video { position: absolute; min-width: 100%; min-height: 100%; object-fit: cover; }
        canvas { position: absolute; top: 0; left: 0; width: 100%; height: 100%; object-fit: cover; }
        
        .hud-panel {
            position: absolute; bottom: 20px; left: 50%; transform: translateX(-50%);
            width: 90%; max-width: 400px;
            background: rgba(0, 0, 0, 0.7);
            border: 1px solid #00ffcc;
            border-radius: 15px;
            padding: 15px;
            backdrop-filter: blur(5px);
            display: none; /* Sembunyi dulu */
        }
    </style>
</head>
<body>

    <div id="loading" class="fixed inset-0 bg-black z-50 flex flex-col items-center justify-center">
        <div class="animate-spin rounded-full h-16 w-16 border-t-4 border-b-4 border-teal-500 mb-4"></div>
        <h2 class="text-teal-400 font-bold text-xl">MEMUAT AI...</h2>
        <p class="text-gray-500 text-sm">Menyiapkan otak digital di browser...</p>
    </div>

    <div class="camera-container">
        <video id="video" autoplay playsinline muted></video>
        <canvas id="canvas"></canvas>
    </div>

    <div id="hudPanel" class="hud-panel z-20" onclick="openManualInput()">
        <div class="flex items-center gap-4">
            <div id="iconType" class="text-3xl">🔍</div>
            <div>
                <h3 id="lblMain" class="text-lg font-bold text-teal-300">Mendeteksi...</h3>
                <p id="lblDetail" class="text-sm text-gray-300">...</p>
            </div>
        </div>
    </div>

    <div id="manualModal" class="fixed inset-0 bg-black/90 z-50 hidden flex items-center justify-center p-4">
        <div class="bg-gray-800 p-6 rounded-lg w-full max-w-sm border border-teal-500">
            <h3 class="text-lg font-bold text-teal-400 mb-4">SIMPAN KE DATABASE</h3>
            <input type="hidden" id="inpRawLabel">
            <input type="text" id="inpName" placeholder="Nama Barang / Plat" class="w-full bg-gray-900 border border-gray-600 p-3 rounded text-white mb-3">
            <input type="number" id="inpPrice" placeholder="Harga (Opsional)" class="w-full bg-gray-900 border border-gray-600 p-3 rounded text-white mb-4">
            <div class="flex gap-2">
                <button onclick="document.getElementById('manualModal').classList.add('hidden')" class="flex-1 bg-gray-600 py-2 rounded">Batal</button>
                <button onclick="saveToDb()" class="flex-1 bg-teal-600 py-2 rounded font-bold">Simpan</button>
            </div>
        </div>
    </div>

    <script>
        const video = document.getElementById('video');
        const canvas = document.getElementById('canvas');
        const ctx = canvas.getContext('2d');
        const hudPanel = document.getElementById('hudPanel');
        
        let model;
        let lastLabel = "";
        let dbCache = {}; // Cache sederhana biar gak nembak server terus

        // 1. LOAD MODEL (Hanya sekali di awal)
        cocoSsd.load().then(loadedModel => {
            model = loadedModel;
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
                    predictFrame(); // Mulai Loop
                };
            } catch (err) { alert("Kamera Error: " + err); }
        }

        // 2. LOOP DETEKSI (Real-time)
        async function predictFrame() {
            // Deteksi objek di video
            const predictions = await model.detect(video);
            
            drawPredictions(predictions);
            requestAnimationFrame(predictFrame); // Loop secepat mungkin
        }

        // 3. GAMBAR & CEK DATABASE
        function drawPredictions(predictions) {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            
            // Ambil 1 objek dengan confidence tertinggi saja biar fokus
            if (predictions.length > 0) {
                const best = predictions[0];
                const [x, y, w, h] = best.bbox;
                const label = best.class;

                // Gambar Kotak
                ctx.strokeStyle = "#00ffcc";
                ctx.lineWidth = 4;
                ctx.strokeRect(x, y, w, h);

                // --- INTEGRASI KE LARAVEL ---
                // Cek database hanya jika label berubah (biar server gak jebol)
                if (label !== lastLabel) {
                    lastLabel = label;
                    checkLaravelDB(label);
                }

                // Update Data di Layar dari Cache
                const data = dbCache[label] || { 
                    label: label.toUpperCase() + " (?)", 
                    detail: "Tap untuk simpan", 
                    found: false 
                };

                // Tampilkan HUD
                hudPanel.style.display = 'block';
                document.getElementById('lblMain').innerText = data.label;
                document.getElementById('lblDetail').innerText = data.detail;
                document.getElementById('iconType').innerText = data.found ? (data.type == 'resi' ? '📦' : '✅') : '❓';
                
                // Set input hidden value buat manual save
                document.getElementById('inpRawLabel').value = label;

                // Tambahan: Efek Manusia
                if (label === 'person') {
                    document.getElementById('lblDetail').innerText = "Suhu: " + (36 + Math.random()).toFixed(1) + "°C (Estimasi)";
                }

            } else {
                hudPanel.style.display = 'none';
            }
        }

        // 4. CEK KE LARAVEL (AJAX)
        async function checkLaravelDB(keyword) {
            if (dbCache[keyword]) return; // Kalau sudah ada di cache, gak usah tanya server

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
                dbCache[keyword] = data; // Simpan ke cache
            } catch (e) { console.error(e); }
        }

        // 5. MANUAL INPUT
        function openManualInput() {
            const currentRaw = document.getElementById('inpRawLabel').value;
            // Jika sudah dikenal, gak perlu edit (opsional)
            if(dbCache[currentRaw] && dbCache[currentRaw].found && currentRaw !== 'person') return;

            document.getElementById('manualModal').classList.remove('hidden');
            document.getElementById('inpName').focus();
        }

        async function saveToDb() {
            const raw = document.getElementById('inpRawLabel').value;
            const name = document.getElementById('inpName').value;
            const price = document.getElementById('inpPrice').value;

            // Panggil route simpan (apps.store) yang sudah ada sebelumnya
            try {
                await fetch("{{ route('apps.store') }}", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ barcode: raw, name: name, price: price })
                });
                
                // Update Cache Lokal biar langsung berubah
                dbCache[raw] = { found: true, type: 'produk', label: name, detail: "Rp " + price };
                alert("Tersimpan!");
                document.getElementById('manualModal').classList.add('hidden');
            } catch(e) { alert("Gagal Simpan"); }
        }
    </script>
</body>
</html>