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
            width: 100%; max-width: 640px; aspect-ratio: 3/4;
            margin: 0 auto;
            border: 1px solid #333;
            background: #000;
            overflow: hidden;
        }
        video, canvas { position: absolute; top:0; left:0; width:100%; height:100%; object-fit: cover; }
        
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
        let scanInterval;
        let lastObjects = []; // Simpan hasil scan terakhir agar bisa diklik

        // --- 1. SYSTEM START & WEATHER ---
        async function startSystem() {
            try {
                // Jam
                setInterval(() => {
                    document.getElementById('clock').innerText = new Date().toLocaleTimeString();
                }, 1000);

                // Cuaca (Open-Meteo API - Free)
                if (navigator.geolocation) {
                    navigator.geolocation.getCurrentPosition(async (pos) => {
                        const { latitude, longitude } = pos.coords;
                        try {
                            const res = await fetch(`https://api.open-meteo.com/v1/forecast?latitude=${latitude}&longitude=${longitude}&current_weather=true`);
                            const data = await res.json();
                            const temp = data.current_weather.temperature;
                            const code = data.current_weather.weathercode;
                            // Mapping kode cuaca sederhana
                            let weatherDesc = "Cerah";
                            if(code > 3) weatherDesc = "Berawan";
                            if(code > 50) weatherDesc = "Hujan";
                            
                            document.getElementById('weatherInfo').innerHTML = `<i class="fas fa-temperature-high"></i> ${temp}°C | ${weatherDesc}`;
                            document.getElementById('locationInfo').innerText = `Lat: ${latitude.toFixed(2)}, Long: ${longitude.toFixed(2)}`;
                        } catch(e) { console.error(e); }
                    });
                }

                // Kamera
                const stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } });
                video.srcObject = stream;
                document.getElementById('startOverlay').classList.add('hidden');
                
                video.onloadedmetadata = () => {
                    canvas.width = video.videoWidth;
                    canvas.height = video.videoHeight;
                    // Auto Scan tiap 2.5 Detik
                    scanInterval = setInterval(scanFrame, 2500);
                    updateStatus("SCANNING ACTIVE", "animate-pulse");
                };

                // Event Listener Klik Canvas (Untuk Input Manual)
                canvas.addEventListener('click', handleCanvasClick);

            } catch (e) { alert("Camera Error: " + e); }
        }

        // --- 2. SCANNING PROCESS ---
        async function scanFrame() {
            const tCanvas = document.createElement('canvas');
            tCanvas.width = video.videoWidth;
            tCanvas.height = video.videoHeight;
            tCanvas.getContext('2d').drawImage(video, 0, 0);
            
            // Gunakan kualitas lebih rendah (0.5) agar ukuran file kecil dan tidak bikin server timeout
            const imageData = tCanvas.toDataURL('image/jpeg', 0.5); 

            try {
                const res = await fetch("{{ route('detection.process') }}", {
                    method: "POST",
                    headers: { 
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content,
                        "Accept": "application/json" // Pastikan minta JSON
                    },
                    body: JSON.stringify({ image: imageData })
                });

                // Cek jika server error (500)
                if (!res.ok) {
                    const errorText = await res.text();
                    console.error("SERVER ERROR:", errorText);
                    updateStatus("SERVER ERROR", "text-red-500");
                    return; // Stop
                }

                const json = await res.json();
                
                if (json.status === 'success') {
                    lastObjects = json.data;
                    drawHUD(json.data);
                    updateStatus("ONLINE", "text-teal-500");
                } else {
                    console.error("APP ERROR:", json.message);
                    console.warn("PYTHON DEBUG:", json.debug_output);
                    updateStatus("AI ERROR", "text-red-500");
                }

            } catch (e) { 
                console.error("NETWORK ERROR:", e); 
                updateStatus("CONNECTION LOST", "text-red-500"); 
            }
        }

        // --- 3. DRAW HUD (TAMPILAN CANGGIH) ---
        function drawHUD(objects) {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            
            objects.forEach(obj => {
                const [x, y, x2, y2] = obj.box;
                const w = x2 - x;
                const h = y2 - y;

                // Warna HUD
                let color = '#00ffcc'; // Default Teal
                if (!obj.is_known) color = '#ff3300'; // Merah (Unknown)
                if (obj.type === 'manusia') color = '#00ccff'; // Biru (Manusia)

                // Kotak
                ctx.strokeStyle = color;
                ctx.lineWidth = 1.5;
                ctx.strokeRect(x, y, w, h);

                // Garis Konektor ke Label
                ctx.beginPath();
                ctx.moveTo(x + w, y);
                ctx.lineTo(x + w + 20, y - 20);
                ctx.lineTo(x + w + 100, y - 20);
                ctx.stroke();

                // INFO UTAMA (Label)
                ctx.fillStyle = color;
                ctx.font = "bold 14px Rajdhani";
                ctx.fillText(obj.display_label.toUpperCase(), x + w + 25, y - 25);

                // INFO TAMBAHAN (Suhu, Usia, dll)
                let yOffset = y - 5;
                if (obj.bio_data) {
                    ctx.fillStyle = "#ffffff";
                    ctx.font = "12px monospace";
                    ctx.fillText(`🌡️ ${obj.bio_data.suhu}`, x, y + h + 15);
                    ctx.fillText(`👤 ${obj.bio_data.usia} (${obj.bio_data.gender})`, x, y + h + 30);
                }
                
                // Prompt Input Manual (Jika Unknown)
                if (!obj.is_known && obj.type !== 'manusia') {
                    ctx.fillStyle = "#ff3300";
                    ctx.font = "italic 10px sans-serif";
                    ctx.fillText("[TAP UNTUK IDENTIFIKASI]", x, y - 5);
                }
            });
        }

        // --- 4. MANUAL INPUT LOGIC ---
        function handleCanvasClick(e) {
            const rect = canvas.getBoundingClientRect();
            // Skala koordinat klik karena canvas mungkin di-resize CSS
            const scaleX = canvas.width / rect.width;
            const scaleY = canvas.height / rect.height;
            const clickX = (e.clientX - rect.left) * scaleX;
            const clickY = (e.clientY - rect.top) * scaleY;

            // Cek apakah klik kena kotak objek
            lastObjects.forEach(obj => {
                const [x, y, x2, y2] = obj.box;
                if (clickX >= x && clickX <= x2 && clickY >= y && clickY <= y2) {
                    // Hanya izinkan edit jika bukan manusia (karena manusia data bio-nya random)
                    // Atau bisa juga diedit namanya misal "Budi"
                    openModal(obj);
                }
            });
        }

        function openModal(obj) {
            document.getElementById('manualInputModal').classList.remove('hidden');
            document.getElementById('rawLabel').value = obj.label_raw; // Ini kuncinya (misal: 'cup' atau 'car')
            document.getElementById('modalObjRaw').innerText = obj.label_raw.toUpperCase();
            document.getElementById('inpName').focus();
        }

        function closeModal() {
            document.getElementById('manualInputModal').classList.add('hidden');
        }

        async function saveManualData(e) {
            e.preventDefault();
            const raw = document.getElementById('rawLabel').value;
            const name = document.getElementById('inpName').value;
            const price = document.getElementById('inpPrice').value;

            try {
                // Gunakan route existing 'apps.store'
                const res = await fetch("{{ route('apps.store') }}", {
                    method: "POST",
                    headers: { 
                        "Content-Type": "application/json", 
                        "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content 
                    },
                    // KITA SIMPAN 'rawLabel' (misal: cup) SEBAGAI SKU/BARCODE
                    // Agar nanti kalau AI nemu 'cup' lagi, dia cek SKU 'cup' dan nemu nama barunya
                    body: JSON.stringify({ barcode: raw, name: name, price: price })
                });
                
                const json = await res.json();
                if(json.status === 'success') {
                    alert("✅ Data Tersimpan! Sistem sekarang mengenali objek ini.");
                    closeModal();
                }
            } catch(e) { alert("Gagal Simpan"); }
        }

        function updateStatus(text, extraClass) {
            const el = document.getElementById('statusBadge');
            el.innerText = text;
            el.className = `inline-block px-4 py-1 bg-black/50 border border-teal-900 rounded-full text-xs text-teal-500 ${extraClass}`;
        }
    </script>
</body>
</html>