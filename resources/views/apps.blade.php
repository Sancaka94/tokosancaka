<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Sancaka Ultimate AI</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700&family=Rajdhani:wght@300;500;700&display=swap');
        body { background-color: #050505; color: #00ffcc; font-family: 'Rajdhani', sans-serif; overflow-x: hidden; }
        .hud-text { font-family: 'Orbitron', sans-serif; }
        
        .camera-frame {
            position: relative;
            width: 100%; 
            max-width: 640px; 
            /* Hapus aspect-ratio paksa, biarkan menyesuaikan video */
            margin: 0 auto;
            border: 1px solid #333;
            background: #000;
            overflow: hidden;
            display: flex;       /* Tambahan agar center */
            justify-content: center;
            align-items: center;
        }
        
        video, canvas { 
            position: absolute; 
            top: 0; 
            left: 0; 
            width: 100%; 
            height: 100%; 
            object-fit: contain; /* PENTING: Ganti COVER jadi CONTAIN agar koordinat pas */
        }
        
        /* Animasi Scanning */
        .scanner-bar {
            position: absolute; width: 100%; height: 2px;
            background: rgba(0, 255, 204, 0.8);
            box-shadow: 0 0 10px #00ffcc;
            animation: scan 3s infinite linear;
            z-index: 10;
        }
        @keyframes scan { 0%{top:0%} 50%{top:100%} 100%{top:0%} }

        /* HUD Corners */
        .corner { position: absolute; width: 20px; height: 20px; border-color: #00ffcc; border-style: solid; z-index: 20; }
        .tl { top: 10px; left: 10px; border-width: 2px 0 0 2px; }
        .tr { top: 10px; right: 10px; border-width: 2px 2px 0 0; }
        .bl { bottom: 10px; left: 10px; border-width: 0 0 2px 2px; }
        .br { bottom: 10px; right: 10px; border-width: 0 2px 2px 0; }
    </style>
</head>
<body class="flex flex-col items-center min-h-screen">

    <div class="w-full max-w-md flex justify-between items-center p-4 z-30 bg-black/50 backdrop-blur-sm fixed top-0 border-b border-teal-900/50">
        <div>
            <h1 class="hud-text text-lg font-bold tracking-widest text-teal-400">SANCAKA <span class="text-white text-xs">AI</span></h1>
            <p id="clock" class="text-xs text-gray-400">00:00:00</p>
        </div>
        <div class="text-right">
            <div id="weatherInfo" class="flex items-center gap-2 text-yellow-400">
                <i class="fas fa-spinner fa-spin"></i> <span class="text-xs">Detecting...</span>
            </div>
            <p id="locationInfo" class="text-[10px] text-gray-500">GPS Active</p>
        </div>
    </div>

    <div class="camera-frame mt-16 shadow-[0_0_30px_rgba(0,255,204,0.1)]">
        <video id="video" autoplay playsinline muted></video>
        <canvas id="canvas"></canvas>
        <div class="scanner-bar"></div>
        
        <div class="corner tl"></div><div class="corner tr"></div>
        <div class="corner bl"></div><div class="corner br"></div>

        <div id="startOverlay" class="absolute inset-0 bg-black/90 flex flex-col items-center justify-center z-50">
            <button onclick="startSystem()" class="px-8 py-3 bg-teal-600/20 border border-teal-500 hover:bg-teal-500/40 text-teal-300 rounded font-bold tracking-widest transition shadow-[0_0_15px_#00ffcc]">
                INITIALIZE SYSTEM
            </button>
        </div>
    </div>

    <div id="manualInputModal" class="fixed inset-0 bg-black/80 flex items-center justify-center z-50 hidden">
        <div class="bg-gray-900 border border-teal-500 p-6 rounded-lg w-11/12 max-w-sm shadow-[0_0_20px_#00ffcc]">
            <h3 class="hud-text text-teal-400 text-lg mb-4">IDENTIFIKASI MANUAL</h3>
            <p class="text-xs text-gray-400 mb-4">Objek: <span id="modalObjRaw" class="text-white font-bold">...</span></p>
            
            <form onsubmit="saveManualData(event)">
                <input type="hidden" id="rawLabel">
                <div class="mb-3">
                    <label class="text-xs text-teal-600 uppercase">Nama Asli / Plat Nomor</label>
                    <input type="text" id="inpName" class="w-full bg-black border border-gray-700 text-white p-2 rounded focus:border-teal-500 outline-none" placeholder="Contoh: Plat AE 1234 XX" required>
                </div>
                <div class="mb-4">
                    <label class="text-xs text-teal-600 uppercase">Harga / Info (Opsional)</label>
                    <input type="number" id="inpPrice" class="w-full bg-black border border-gray-700 text-white p-2 rounded focus:border-teal-500 outline-none" placeholder="0">
                </div>
                <div class="flex gap-2">
                    <button type="button" onclick="closeModal()" class="flex-1 py-2 bg-gray-800 text-gray-400 rounded hover:bg-gray-700">Batal</button>
                    <button type="submit" class="flex-1 py-2 bg-teal-700 text-white rounded hover:bg-teal-600 font-bold">SIMPAN MEMORI</button>
                </div>
            </form>
        </div>
    </div>

    <div class="fixed bottom-4 w-full text-center">
        <div id="statusBadge" class="inline-block px-4 py-1 bg-black/50 border border-teal-900 rounded-full text-xs text-teal-500">
            SYSTEM STANDBY
        </div>
    </div>

    <script>
        const video = document.getElementById('video');
        const canvas = document.getElementById('canvas');
        const ctx = canvas.getContext('2d');
        
        // Element UI
        const scanLaser = document.getElementById('scanLaser');
        const startOverlay = document.getElementById('startOverlay');
        
        // Modal Manual Input
        const manualInputModal = document.getElementById('manualInputModal');
        const inpName = document.getElementById('inpName');
        const inpPrice = document.getElementById('inpPrice');
        const rawLabelInput = document.getElementById('rawLabel');
        const modalObjRaw = document.getElementById('modalObjRaw');

        let stream;
        let lastObjects = []; // Simpan data terakhir untuk klik
        let isProcessing = false;

        // 1. START SYSTEM
        async function startSystem() {
            try {
                // Minta izin kamera
                stream = await navigator.mediaDevices.getUserMedia({ 
                    video: { 
                        facingMode: 'environment', // Kamera Belakang
                        width: { ideal: 640 },     // Ideal resolusi agar tidak terlalu berat
                        height: { ideal: 480 } 
                    }, 
                    audio: false 
                });
                
                video.srcObject = stream;
                
                // Tunggu video siap, baru mulai loop
                video.onloadedmetadata = () => {
                    video.play();
                    
                    // Sembunyikan Overlay Start
                    startOverlay.classList.add('hidden');
                    
                    // Setting ukuran Canvas agar SAMA PERSIS dengan Video asli
                    canvas.width = video.videoWidth;
                    canvas.height = video.videoHeight;
                    
                    console.log("Kamera Siap: " + canvas.width + "x" + canvas.height);

                    // Mulai Scanning Loop (Tiap 2 detik)
                    setInterval(scanFrame, 2000);
                    
                    updateStatus("ONLINE - SCANNING...", "text-teal-400");
                };

                // Event Klik Canvas (Input Manual)
                canvas.addEventListener('click', handleCanvasClick);

            } catch (err) {
                alert("Gagal Akses Kamera: " + err.message);
                console.error(err);
            }
        }

        // 2. PROSES FRAME
        async function scanFrame() {
            if(isProcessing) return; // Jangan tumpuk request
            isProcessing = true;
            updateStatus("MENGANALISA...", "text-yellow-400");

            // Ambil gambar snapshot
            const tempCanvas = document.createElement('canvas');
            tempCanvas.width = video.videoWidth;
            tempCanvas.height = video.videoHeight;
            const tCtx = tempCanvas.getContext('2d');
            tCtx.drawImage(video, 0, 0);
            
            // Kompres gambar (Quality 0.5) agar ringan dikirim
            const imageData = tempCanvas.toDataURL('image/jpeg', 0.5);

            try {
                const response = await fetch("{{ route('detection.process') }}", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content,
                        "Accept": "application/json"
                    },
                    body: JSON.stringify({ image: imageData })
                });

                // Cek jika server error (bukan JSON)
                if (!response.ok) {
                    throw new Error("Server Error: " + response.status);
                }

                const json = await response.json();

                if (json.status === 'success') {
                    // SUKSES! Gambar Kotak
                    console.log("Data Diterima:", json.data); // Cek di Console Browser
                    lastObjects = json.data;
                    drawHUD(json.data);
                    updateStatus("SYSTEM ACTIVE", "text-teal-500");
                } else {
                    console.warn("API Warning:", json);
                    updateStatus("AI GAGAL MEMBACA", "text-red-400");
                }

            } catch (err) {
                console.error("Error Fetch:", err);
                updateStatus("KONEKSI TERPUTUS", "text-red-600");
            } finally {
                isProcessing = false;
            }
        }

        // 3. GAMBAR HUD (KOTAK-KOTAK)
        function drawHUD(objects) {
            // Bersihkan canvas lama
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            
            // Jika tidak ada objek
            if (objects.length === 0) return;

            objects.forEach(obj => {
                // Koordinat dari Python [x1, y1, x2, y2]
                const [x1, y1, x2, y2] = obj.box;
                const width = x2 - x1;
                const height = y2 - y1;

                // Tentukan Warna
                let color = '#00ffcc'; // Teal (Default)
                let text = obj.display_label || obj.label_raw;
                
                if (obj.is_known) {
                    color = '#00ff00'; // Hijau (Dikenali Database)
                } else if (obj.type === 'manusia') {
                    color = '#00aaff'; // Biru (Manusia)
                } else {
                    color = '#ff3300'; // Merah (Unknown/Baru)
                }

                // GAMBAR KOTAK
                ctx.beginPath();
                ctx.lineWidth = 3;
                ctx.strokeStyle = color;
                ctx.rect(x1, y1, width, height);
                ctx.stroke();

                // GAMBAR LABEL BACKGROUND
                ctx.font = "bold 16px Arial";
                const textWidth = ctx.measureText(text).width;
                
                ctx.fillStyle = color;
                ctx.fillRect(x1, y1 > 30 ? y1 - 30 : 0, textWidth + 10, 30);

                // TULISAN LABEL
                ctx.fillStyle = '#000000';
                ctx.fillText(text, x1 + 5, y1 > 30 ? y1 - 8 : 22);

                // JIKA MANUSIA (Tampilkan Data Bio)
                if (obj.bio_data) {
                    ctx.fillStyle = '#ffffff';
                    ctx.font = "12px monospace";
                    ctx.fillText(`🌡️ ${obj.bio_data.suhu}`, x1, y1 + height + 15);
                    ctx.fillText(`👤 ${obj.bio_data.usia}`, x1, y1 + height + 30);
                }
            });
        }

        // 4. HANDLE KLIK (INPUT MANUAL)
        function handleCanvasClick(e) {
            // Hitung skala (karena ukuran canvas di layar HP beda dengan resolusi asli)
            const rect = canvas.getBoundingClientRect();
            const scaleX = canvas.width / rect.width;
            const scaleY = canvas.height / rect.height;

            const clickX = (e.clientX - rect.left) * scaleX;
            const clickY = (e.clientY - rect.top) * scaleY;

            lastObjects.forEach(obj => {
                const [x1, y1, x2, y2] = obj.box;
                
                // Cek apakah klik di dalam kotak
                if (clickX >= x1 && clickX <= x2 && clickY >= y1 && clickY <= y2) {
                    if (!obj.is_known && obj.type !== 'manusia') {
                        openModal(obj);
                    }
                }
            });
        }

        function openModal(obj) {
            manualInputModal.classList.remove('hidden');
            rawLabelInput.value = obj.label_raw; // Kunci pencarian (misal: 'cup')
            modalObjRaw.innerText = obj.label_raw.toUpperCase();
            inpName.value = ""; // Reset form
            inpPrice.value = "";
            inpName.focus();
        }

        function closeModal() {
            manualInputModal.classList.add('hidden');
        }

        async function saveManualData(e) {
            e.preventDefault();
            try {
                const res = await fetch("{{ route('apps.store') }}", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        barcode: rawLabelInput.value, // Kita pakai label raw sbg ID
                        name: inpName.value,
                        price: inpPrice.value
                    })
                });
                
                const json = await res.json();
                if(json.status === 'success') {
                    alert("✅ Berhasil Disimpan! Scan ulang objeknya.");
                    closeModal();
                } else {
                    alert("Gagal: " + json.message);
                }
            } catch(e) { 
                alert("Error Koneksi"); 
            }
        }

        function updateStatus(text, colorClass) {
            const badge = document.getElementById('statusBadge');
            badge.innerText = text;
            // Reset class warna
            badge.className = "inline-block px-4 py-1 bg-black/50 border border-teal-900 rounded-full text-xs " + colorClass;
        }

        // Jam Digital
        setInterval(() => {
            document.getElementById('clock').innerText = new Date().toLocaleTimeString();
        }, 1000);
    </script>
</body>
</html>