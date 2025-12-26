<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Sancaka Manual Scanner</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700&family=Rajdhani:wght@500;700&display=swap" rel="stylesheet">

    <style>
        body { background-color: #000; color: #00ffcc; font-family: 'Rajdhani', sans-serif; overflow: hidden; }
        .camera-container { position: relative; width: 100vw; height: 100vh; background: #000; }
        video, canvas { position: absolute; top: 0; left: 0; width: 100%; height: 100%; object-fit: cover; }
        
        /* Tombol Shutter Cyberpunk */
        .shutter-btn {
            position: absolute; bottom: 40px; left: 50%; transform: translateX(-50%);
            width: 80px; height: 80px;
            border-radius: 50%;
            background: rgba(0, 0, 0, 0.5);
            border: 2px solid #00ffcc;
            box-shadow: 0 0 15px #00ffcc;
            display: flex; justify-content: center; align-items: center;
            cursor: pointer; z-index: 50;
            transition: all 0.2s;
        }
        .shutter-btn:active { transform: translateX(-50%) scale(0.9); background: #00ffcc; box-shadow: 0 0 30px #00ffcc; }
        .shutter-inner { width: 60px; height: 60px; background: rgba(0, 255, 204, 0.2); border-radius: 50%; border: 1px solid #00ffcc; }

        /* Loading Spinner saat Scan */
        .scan-loader {
            width: 100%; height: 100%; border-radius: 50%;
            border: 4px solid transparent; border-top-color: #fff;
            animation: spin 1s linear infinite; display: none;
        }
        @keyframes spin { 100% { transform: rotate(360deg); } }

        /* Status Badge */
        .status-badge {
            position: absolute; top: 20px; right: 20px;
            padding: 5px 15px; border-radius: 4px;
            font-family: 'Orbitron'; font-size: 10px;
            border: 1px solid #333; background: rgba(0,0,0,0.7);
            z-index: 50;
        }
    </style>
</head>
<body>

    <div id="statusBadge" class="status-badge text-gray-400 border-gray-600">STANDBY</div>

    <div class="camera-container">
        <video id="video" autoplay playsinline muted></video>
        <canvas id="canvas"></canvas>
        
        <div id="startOverlay" class="absolute inset-0 bg-black/90 flex flex-col items-center justify-center z-40">
            <h1 class="text-3xl font-bold font-['Orbitron'] text-teal-400 tracking-widest mb-6">SANCAKA AI</h1>
            <button onclick="initCamera()" class="px-8 py-3 border border-teal-500 text-teal-400 font-bold rounded tracking-wider hover:bg-teal-500/20 transition">
                AKTIFKAN KAMERA
            </button>
        </div>

        <div id="shutterBtn" class="shutter-btn hidden" onclick="captureFrame()">
            <div class="shutter-inner flex items-center justify-center">
                <i class="fas fa-camera text-2xl text-white"></i>
                <div id="loader" class="scan-loader"></div>
            </div>
        </div>
    </div>

    <div id="modalInput" class="fixed inset-0 bg-black/80 z-50 hidden flex items-center justify-center p-4 backdrop-blur-sm">
        <div class="bg-gray-900 border border-teal-500/50 p-6 w-full max-w-sm shadow-[0_0_30px_rgba(0,255,204,0.1)]">
            <h3 class="text-teal-400 font-['Orbitron'] mb-4 text-lg border-b border-gray-700 pb-2">INPUT DATA BARU</h3>
            <input type="hidden" id="inpRawLabel">
            <div class="mb-2 text-xs text-yellow-500 font-mono" id="displayRaw">...</div>
            <input type="text" id="inpName" class="w-full bg-black border border-gray-700 text-white p-3 mb-3 text-sm focus:border-teal-500 outline-none" placeholder="Nama Barang / Plat Nomor">
            <input type="text" id="inpPrice" class="w-full bg-black border border-gray-700 text-white p-3 mb-4 text-sm focus:border-teal-500 outline-none" placeholder="Harga / Info Lain">
            <div class="flex gap-2">
                <button onclick="closeModal()" class="flex-1 py-3 bg-gray-800 text-gray-400 text-xs font-bold">BATAL</button>
                <button onclick="saveData()" class="flex-1 py-3 bg-teal-900 border border-teal-500 text-teal-400 text-xs font-bold">SIMPAN</button>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
    <script>
        const video = document.getElementById('video');
        const canvas = document.getElementById('canvas');
        const ctx = canvas.getContext('2d');
        const shutterBtn = document.getElementById('shutterBtn');
        const loader = document.getElementById('loader');
        const statusBadge = document.getElementById('statusBadge');
        
        let isProcessing = false;
        let lastBoxes = []; 

        // 1. START KAMERA
        async function initCamera() {
            try {
                const stream = await navigator.mediaDevices.getUserMedia({ 
                    video: { facingMode: 'environment' }, audio: false 
                });
                video.srcObject = stream;
                video.onloadedmetadata = () => {
                    document.getElementById('startOverlay').classList.add('hidden');
                    shutterBtn.classList.remove('hidden');
                    canvas.width = video.videoWidth;
                    canvas.height = video.videoHeight;
                    updateStatus("READY TO SCAN", "text-blue-400", "border-blue-500");
                };
                
                // Klik Canvas untuk Input Manual
                canvas.addEventListener('click', handleCanvasClick);
            } catch (e) { alert("Gagal akses kamera"); }
        }

        // 2. FUNGSI CAPTURE (DIPANGGIL SAAT TOMBOL DITEKAN)
        async function captureFrame() {
            if (isProcessing) return; // Jangan dobel klik
            isProcessing = true;
            
            // Efek UI Loading
            loader.style.display = 'block';
            updateStatus("SCANNING...", "text-yellow-400", "border-yellow-500");

            // Ambil Gambar
            const tmp = document.createElement('canvas');
            tmp.width = video.videoWidth; tmp.height = video.videoHeight;
            tmp.getContext('2d').drawImage(video, 0, 0);
            const jpg = tmp.toDataURL('image/jpeg', 0.6);

            try {
                const res = await fetch("{{ route('detection.process') }}", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ image: jpg })
                });

                const json = await res.json();

                if (json.status === 'success') {
                    lastBoxes = json.data;
                    drawHUD(json.data);
                    
                    if(json.data.length > 0) {
                        updateStatus("OBJECT DETECTED", "text-teal-400", "border-teal-500");
                    } else {
                        updateStatus("NO OBJECT", "text-red-400", "border-red-500");
                    }
                }
            } catch (e) {
                console.error(e);
                updateStatus("ERROR / TIMEOUT", "text-red-500", "border-red-600");
            } finally {
                isProcessing = false;
                loader.style.display = 'none';
            }
        }

        // 3. GAMBAR HUD
        function drawHUD(boxes) {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            
            boxes.forEach(box => {
                const [x, y, x2, y2] = box.box;
                const w = x2 - x; const h = y2 - y;
                
                // Warna Keren
                let color = '#00ffcc';
                if(box.type === 'face') color = '#d946ef';
                if(box.type === 'barcode') color = '#facc15';

                // Kotak Utama
                ctx.shadowBlur = 10; ctx.shadowColor = color;
                ctx.strokeStyle = color; ctx.lineWidth = 2;
                ctx.strokeRect(x, y, w, h);

                // Sudut Tebal
                ctx.shadowBlur = 0; ctx.lineWidth = 4;
                const c = 20;
                ctx.beginPath();
                ctx.moveTo(x, y+c); ctx.lineTo(x, y); ctx.lineTo(x+c, y);
                ctx.moveTo(x+w, y+h-c); ctx.lineTo(x+w, y+h); ctx.lineTo(x+w-c, y+h);
                ctx.stroke();

                // Label
                let label = box.label;
                let detail = box.db_info && box.db_info.found ? box.db_info.detail : "TAP INPUT";
                
                // Latar Label
                ctx.fillStyle = color; ctx.globalAlpha = 0.8;
                const tw = ctx.measureText(label).width;
                ctx.fillRect(x, y-24, tw+20, 24);
                
                // Teks Label
                ctx.globalAlpha = 1; ctx.fillStyle = "#000"; 
                ctx.font = "bold 14px Rajdhani";
                ctx.fillText(label, x+5, y-7);

                // Detail
                ctx.fillStyle = color;
                ctx.font = "12px monospace";
                ctx.fillText(detail, x, y+h+15);
            });
        }

        // 4. INPUT MANUAL
        function handleCanvasClick(e) {
            const rect = canvas.getBoundingClientRect();
            const sx = canvas.width / rect.width;
            const sy = canvas.height / rect.height;
            const cx = (e.clientX - rect.left) * sx;
            const cy = (e.clientY - rect.top) * sy;

            lastBoxes.forEach(box => {
                const [x, y, x2, y2] = box.box;
                if (cx>=x && cx<=x2 && cy>=y && cy<=y2) {
                    if (box.db_info && !box.db_info.found && box.type !== 'face') {
                        openModal(box.label_raw);
                    }
                }
            });
        }

        function openModal(raw) {
            document.getElementById('modalInput').classList.remove('hidden');
            document.getElementById('inpRawLabel').value = raw;
            document.getElementById('displayRaw').innerText = "AI CODE: " + raw.toUpperCase();
            document.getElementById('inpName').focus();
        }
        function closeModal() { document.getElementById('modalInput').classList.add('hidden'); }

        async function saveData() {
            const raw = document.getElementById('inpRawLabel').value;
            const name = document.getElementById('inpName').value;
            const price = document.getElementById('inpPrice').value;

            try {
                const res = await fetch("{{ route('apps.store') }}", {
                    method: "POST",
                    headers: { "Content-Type": "application/json", "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content },
                    body: JSON.stringify({ barcode: raw, name: name, price: price })
                });
                alert("Tersimpan!"); closeModal();
            } catch(e) { alert("Gagal Simpan"); }
        }

        function updateStatus(text, txtCls, borCls) {
            statusBadge.innerText = text;
            statusBadge.className = `status-badge bg-black/60 ${txtCls} ${borCls}`;
        }
    </script>
</body>
</html>