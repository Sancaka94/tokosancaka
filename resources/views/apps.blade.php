<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Sancaka Neural Interface</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700&family=Rajdhani:wght@500;700&display=swap" rel="stylesheet">

    <style>
        body { 
            background-color: #020202; 
            color: #00ffcc; 
            font-family: 'Rajdhani', sans-serif; 
            overflow: hidden; /* Hilangkan scrollbar */
        }

        /* Container Kamera Fullscreen */
        .camera-container {
            position: relative;
            width: 100vw;
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            background: #000;
        }

        /* Video & Canvas Menumpuk Sempurna */
        video, canvas {
            position: absolute;
            top: 0; left: 0;
            width: 100%; height: 100%;
            object-fit: cover; /* Agar full layar HP */
        }

        /* Status Badge di Pojok Kanan Atas */
        .status-badge {
            position: absolute;
            top: 20px; right: 20px;
            padding: 5px 15px;
            border-radius: 4px;
            font-family: 'Orbitron', sans-serif;
            font-size: 10px;
            letter-spacing: 1px;
            backdrop-filter: blur(4px);
            border: 1px solid;
            z-index: 50;
        }

        /* Animasi Loading */
        .scanner-line {
            position: absolute;
            width: 100%; height: 2px;
            background: rgba(0, 255, 204, 0.5);
            box-shadow: 0 0 10px #00ffcc;
            animation: scan 3s infinite linear;
            z-index: 10;
            pointer-events: none;
        }
        @keyframes scan { 0% {top: 0%} 50% {top: 100%} 100% {top: 0%} }
    </style>
</head>
<body>

    <div id="statusBadge" class="status-badge border-gray-600 text-gray-400 bg-black/60">
        SYSTEM OFFLINE
    </div>

    <div class="camera-container">
        <video id="video" autoplay playsinline muted></video>
        <canvas id="canvas"></canvas>
        <div id="scanAnim" class="scanner-line hidden"></div>
        
        <div id="startOverlay" class="absolute inset-0 bg-black/90 flex flex-col items-center justify-center z-40">
            <div class="mb-6 w-16 h-16 border-t-4 border-b-4 border-teal-500 rounded-full animate-spin"></div>
            <h1 class="text-2xl font-bold font-['Orbitron'] text-teal-400 tracking-widest mb-2">SANCAKA AI</h1>
            <p class="text-xs text-gray-500 mb-6">Neural Object & Face Detection</p>
            <button onclick="initCamera()" class="px-8 py-3 border border-teal-500 text-teal-400 hover:bg-teal-500/20 font-bold rounded tracking-wider transition">
                ACTIVATE CAMERA
            </button>
        </div>
    </div>

    <div id="modalInput" class="fixed inset-0 bg-black/80 z-50 hidden flex items-center justify-center p-4 backdrop-blur-sm">
        <div class="bg-gray-900 border border-teal-500/50 p-6 w-full max-w-sm shadow-[0_0_30px_rgba(0,255,204,0.1)]">
            <h3 class="text-teal-400 font-['Orbitron'] mb-4 text-lg border-b border-gray-700 pb-2">DATA MEMORI BARU</h3>
            
            <input type="hidden" id="inpRawLabel"> <div class="space-y-4">
                <div>
                    <label class="text-[10px] text-gray-500 uppercase">Label AI</label>
                    <div id="displayRaw" class="text-xs font-mono text-yellow-500 mb-2">...</div>
                </div>
                <div>
                    <label class="text-[10px] text-gray-500 uppercase">Nama Asli / Plat</label>
                    <input type="text" id="inpName" class="w-full bg-black border border-gray-700 text-white p-2 text-sm focus:border-teal-500 outline-none" placeholder="Contoh: Kopi Kenangan">
                </div>
                <div>
                    <label class="text-[10px] text-gray-500 uppercase">Harga / Info</label>
                    <input type="text" id="inpPrice" class="w-full bg-black border border-gray-700 text-white p-2 text-sm focus:border-teal-500 outline-none" placeholder="Contoh: 15000">
                </div>
            </div>

            <div class="flex gap-2 mt-6">
                <button onclick="closeModal()" class="flex-1 py-2 bg-gray-800 text-gray-400 text-xs hover:bg-gray-700">BATAL</button>
                <button onclick="saveData()" class="flex-1 py-2 bg-teal-900/80 border border-teal-500 text-teal-400 text-xs font-bold hover:bg-teal-800">SIMPAN DATABASE</button>
            </div>
        </div>
    </div>

    <script>
        const video = document.getElementById('video');
        const canvas = document.getElementById('canvas');
        const ctx = canvas.getContext('2d');
        const statusBadge = document.getElementById('statusBadge');
        
        let isProcessing = false; // Flag Antrian
        let lastBoxes = []; // Simpan data terakhir untuk fitur klik

        // 1. AKTIVASI KAMERA
        async function initCamera() {
            try {
                const stream = await navigator.mediaDevices.getUserMedia({ 
                    video: { facingMode: 'environment' }, 
                    audio: false 
                });
                video.srcObject = stream;
                
                video.onloadedmetadata = () => {
                    document.getElementById('startOverlay').classList.add('hidden');
                    document.getElementById('scanAnim').classList.remove('hidden');
                    
                    // Set ukuran canvas sama persis dengan video
                    canvas.width = video.videoWidth;
                    canvas.height = video.videoHeight;
                    
                    updateStatus("STANDBY", "text-gray-400", "border-gray-600");
                    
                    // Mulai Loop Pintar
                    requestAnimationFrame(smartLoop);
                };
                
                // Event Listener Klik Canvas (Untuk Input Manual)
                canvas.addEventListener('click', handleCanvasClick);

            } catch (e) {
                alert("Gagal akses kamera: " + e.message);
            }
        }

        // 2. LOOP PINTAR (ANTRIAN)
        // Fungsi ini akan terus berputar, tapi hanya mengirim data jika Server sedang NGANGGUR
        function smartLoop() {
            if (!isProcessing) {
                processFrame();
            }
            // Cek ulang setiap 500ms agar UI responsif
            setTimeout(() => requestAnimationFrame(smartLoop), 500);
        }

        // 3. PROSES KIRIM KE SERVER
        async function processFrame() {
            isProcessing = true; // Kunci Antrian (Pintu Tertutup)
            updateStatus("ANALYZING...", "text-yellow-400", "border-yellow-500");

            // Ambil Snapshot & Kompres (0.5)
            const tmpCanvas = document.createElement('canvas');
            tmpCanvas.width = video.videoWidth;
            tmpCanvas.height = video.videoHeight;
            tmpCanvas.getContext('2d').drawImage(video, 0, 0);
            const jpgData = tmpCanvas.toDataURL('image/jpeg', 0.5);

            try {
                const res = await fetch("{{ route('detection.process') }}", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ image: jpgData })
                });

                // Handle jika Server menolak (429 Too Many Requests)
                if (res.status === 429) {
                    throw new Error("Queue Full");
                }

                const json = await res.json();

                if (json.status === 'success') {
                    lastBoxes = json.data; // Simpan data untuk klik
                    drawCyberHUD(json.data);
                    updateStatus("ONLINE", "text-teal-400", "border-teal-500");
                } else {
                    console.warn("AI Info:", json);
                }

            } catch (e) {
                console.log("Skip frame: " + e.message);
                // Jangan update UI error jika cuma antrian penuh
                if(e.message !== "Queue Full") {
                    updateStatus("RETRYING...", "text-red-400", "border-red-500");
                }
            } finally {
                // JEDA 3 DETIK SEBELUM MEMBUKA ANTRIAN LAGI
                // Ini sesuai request Anda agar server tidak berat
                setTimeout(() => {
                    isProcessing = false; // Pintu Dibuka Kembali
                    updateStatus("READY", "text-blue-400", "border-blue-500");
                }, 3000); 
            }
        }

        // 4. MENGGAMBAR HUD (CYBER STYLE)
        function drawCyberHUD(boxes) {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            
            boxes.forEach(box => {
                const [x, y, x2, y2] = box.box;
                const w = x2 - x;
                const h = y2 - y;

                // TENTUKAN WARNA BERDASARKAN TIPE
                let color = '#00ffcc'; // Default Cyan (Benda/Kendaraan)
                if (box.type === 'face') color = '#d946ef'; // Ungu Neon (Wajah)
                if (box.type === 'barcode') color = '#facc15'; // Kuning (Resi)

                // A. KOTAK TIPIS (Glow)
                ctx.shadowBlur = 10;
                ctx.shadowColor = color;
                ctx.strokeStyle = color;
                ctx.lineWidth = 2; // TIPIS (Sesuai Request)
                ctx.strokeRect(x, y, w, h);

                // B. SUDUT TEBAL (Corner Brackets) - Biar Keren
                ctx.shadowBlur = 0; // Matikan glow biar tajam
                ctx.lineWidth = 4;
                const cLen = 20; // Panjang sudut
                
                ctx.beginPath();
                // Kiri Atas
                ctx.moveTo(x, y + cLen); ctx.lineTo(x, y); ctx.lineTo(x + cLen, y);
                // Kanan Bawah
                ctx.moveTo(x + w, y + h - cLen); ctx.lineTo(x + w, y + h); ctx.lineTo(x + w - cLen, y + h);
                ctx.stroke();

                // C. LABEL & DETAIL
                let label = box.label;
                let detail = "";
                
                // Cek Data DB
                if (box.db_info && box.db_info.found) {
                    // Jika ketemu di DB, warna jadi Hijau
                    ctx.fillStyle = '#22c55e'; 
                    detail = box.db_info.detail;
                } else if (box.db_info && !box.db_info.found) {
                    // Jika barang baru (Unknown)
                    ctx.fillStyle = color;
                    detail = "TAP UNTUK INPUT";
                } else {
                    ctx.fillStyle = color;
                }

                // Gambar Background Label
                ctx.font = "bold 14px Rajdhani";
                const tw = ctx.measureText(label).width;
                ctx.globalAlpha = 0.8;
                ctx.fillRect(x, y - 24, tw + 20, 24);
                
                // Tulis Label
                ctx.globalAlpha = 1.0;
                ctx.fillStyle = "#000";
                ctx.fillText(label, x + 5, y - 7);

                // Tulis Detail di Bawah Kotak
                if (detail) {
                    ctx.fillStyle = color;
                    ctx.font = "12px monospace";
                    ctx.fillText(detail, x, y + h + 15);
                }
            });
        }

        // 5. FITUR KLIK UNTUK INPUT DATA
        function handleCanvasClick(e) {
            // Skalakan koordinat klik (jika ukuran layar beda dgn canvas)
            const rect = canvas.getBoundingClientRect();
            const scaleX = canvas.width / rect.width;
            const scaleY = canvas.height / rect.height;
            const clickX = (e.clientX - rect.left) * scaleX;
            const clickY = (e.clientY - rect.top) * scaleY;

            lastBoxes.forEach(box => {
                const [x, y, x2, y2] = box.box;
                if (clickX >= x && clickX <= x2 && clickY >= y && clickY <= y2) {
                    // Jika barang belum dikenal (found = false) & bukan wajah
                    if (box.db_info && !box.db_info.found && box.type !== 'face') {
                        openModal(box.label_raw);
                    }
                }
            });
        }

        function openModal(rawLabel) {
            document.getElementById('modalInput').classList.remove('hidden');
            document.getElementById('inpRawLabel').value = rawLabel;
            document.getElementById('displayRaw').innerText = rawLabel.toUpperCase();
            document.getElementById('inpName').value = "";
            document.getElementById('inpPrice').value = "";
            document.getElementById('inpName').focus();
        }

        function closeModal() {
            document.getElementById('modalInput').classList.add('hidden');
        }

        // 6. SIMPAN DATA KE LARAVEL
        async function saveData() {
            const raw = document.getElementById('inpRawLabel').value;
            const name = document.getElementById('inpName').value;
            const price = document.getElementById('inpPrice').value;

            try {
                // Gunakan route yang sudah Anda buat sebelumnya
                const res = await fetch("{{ route('apps.store') }}", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ barcode: raw, name: name, price: price })
                });
                
                alert("Data berhasil disimpan! Scan ulang objeknya.");
                closeModal();
            } catch(e) {
                alert("Gagal menyimpan data.");
            }
        }

        function updateStatus(text, textColor, borderColor) {
            statusBadge.innerText = text;
            statusBadge.className = `status-badge bg-black/60 ${textColor} ${borderColor}`;
        }
    </script>
</body>
</html>