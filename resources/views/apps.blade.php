<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Sancaka Super Scanner</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
</head>
<body class="bg-slate-900 text-white min-h-screen p-4 font-sans flex flex-col items-center">

    <div class="w-full max-w-md mb-4 flex justify-between items-center">
        <div>
            <h1 class="text-xl font-bold text-transparent bg-clip-text bg-gradient-to-r from-blue-400 to-green-400">
                Sancaka Scanner
            </h1>
            <p class="text-[10px] text-slate-400">Auto Detect: Produk & Resi</p>
        </div>
        <div id="statusBadge" class="px-3 py-1 bg-slate-800 border border-slate-700 rounded-full text-[10px] animate-pulse">
            Ready
        </div>
    </div>

    <div class="relative w-full max-w-md aspect-[3/4] rounded-2xl overflow-hidden border-2 border-slate-700 shadow-2xl bg-black">
        <video id="video" autoplay playsinline muted class="w-full h-full object-cover"></video>
        <canvas id="canvas" class="absolute inset-0 w-full h-full"></canvas>
        <div id="scanLine" class="absolute w-full h-0.5 bg-green-400 shadow-[0_0_20px_#4ade80] animate-[scan_2s_infinite] top-0 hidden"></div>
        
        <div id="startOverlay" class="absolute inset-0 bg-black/80 flex items-center justify-center z-20">
            <button onclick="startSystem()" class="bg-blue-600 px-8 py-3 rounded-full font-bold shadow-lg hover:scale-105 transition flex items-center gap-2">
                <i class="fas fa-camera"></i> Mulai Scan
            </button>
        </div>
    </div>

    <div id="resultContainer" class="w-full max-w-md mt-4 hidden">
        
        <div id="cardOrder" class="bg-white text-slate-800 rounded-xl shadow-lg overflow-hidden hidden">
            <div class="bg-blue-600 text-white px-4 py-2 flex justify-between items-center">
                <span class="font-bold text-xs"><i class="fas fa-shipping-fast mr-1"></i> LOGISTIK</span>
                <span id="ordEkspedisi" class="bg-white/20 px-2 py-0.5 rounded text-[10px] font-bold">JNE</span>
            </div>
            <div class="p-4">
                <p class="text-[10px] text-slate-500 uppercase font-bold">Resi</p>
                <h2 id="ordResi" class="text-lg font-mono font-bold leading-none mb-3">...</h2>
                
                <div class="flex gap-3 mb-3">
                    <div class="flex-1">
                        <p class="text-[10px] text-slate-500 uppercase font-bold">Penerima</p>
                        <p id="ordPenerima" class="font-bold text-sm">...</p>
                    </div>
                    <div class="flex-1 text-right">
                        <p class="text-[10px] text-slate-500 uppercase font-bold">Status</p>
                        <span id="ordStatus" class="inline-block bg-green-100 text-green-700 px-2 py-0.5 rounded text-[10px] font-bold">...</span>
                    </div>
                </div>
                <p id="ordAlamat" class="text-xs text-slate-500 border-t pt-2 border-slate-100">...</p>
            </div>
        </div>

        <div id="cardProduct" class="bg-slate-800 border border-slate-700 rounded-xl p-4 hidden">
            <h3 id="prdTitle" class="text-xs font-bold text-slate-400 uppercase mb-3 flex items-center gap-2">
                <i class="fas fa-box"></i> <span id="prdStatusText">Detail Produk</span>
            </h3>
            <form id="productForm" onsubmit="saveProduct(event)">
                <div class="space-y-3">
                    <div>
                        <label class="text-[10px] text-slate-500">Barcode / SKU</label>
                        <input type="text" id="inpSku" name="barcode" class="w-full bg-slate-900 border border-slate-600 rounded px-3 py-2 text-sm font-mono text-yellow-400 focus:outline-blue-500" readonly>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div class="col-span-2">
                            <label class="text-[10px] text-slate-500">Nama Produk</label>
                            <input type="text" id="inpName" name="name" class="w-full bg-slate-900 border border-slate-600 rounded px-3 py-2 text-sm text-white focus:outline-blue-500">
                        </div>
                        <div class="col-span-2">
                            <label class="text-[10px] text-slate-500">Harga (Rp)</label>
                            <input type="number" id="inpPrice" name="price" class="w-full bg-slate-900 border border-slate-600 rounded px-3 py-2 text-sm text-white focus:outline-blue-500">
                        </div>
                    </div>
                    <button type="submit" id="btnSavePrd" class="w-full bg-green-600 hover:bg-green-500 text-white font-bold py-2 rounded-lg text-sm mt-1 transition">
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>

    </div>

    <div id="toast" class="fixed bottom-10 bg-slate-800 border border-slate-600 px-4 py-2 rounded-lg text-sm shadow-xl hidden z-50 transition-all"></div>

    <script>
        const video = document.getElementById('video');
        const canvas = document.getElementById('canvas');
        const ctx = canvas.getContext('2d');
        let stream, lastCode = "";

        // UI Elements
        const resultContainer = document.getElementById('resultContainer');
        const cardOrder = document.getElementById('cardOrder');
        const cardProduct = document.getElementById('cardProduct');
        
        // Order Elements
        const ordEkspedisi = document.getElementById('ordEkspedisi');
        const ordResi = document.getElementById('ordResi');
        const ordPenerima = document.getElementById('ordPenerima');
        const ordStatus = document.getElementById('ordStatus');
        const ordAlamat = document.getElementById('ordAlamat');

        // Product Elements
        const inpSku = document.getElementById('inpSku');
        const inpName = document.getElementById('inpName');
        const inpPrice = document.getElementById('inpPrice');
        const btnSavePrd = document.getElementById('btnSavePrd');
        const prdStatusText = document.getElementById('prdStatusText');

        async function startSystem() {
            try {
                stream = await navigator.mediaDevices.getUserMedia({ 
                    video: { facingMode: 'environment', width: { ideal: 640 }, height: { ideal: 480 } } 
                });
                video.srcObject = stream;
                document.getElementById('startOverlay').classList.add('hidden');
                document.getElementById('scanLine').classList.remove('hidden');
                
                video.onloadedmetadata = () => {
                    canvas.width = video.videoWidth;
                    canvas.height = video.videoHeight;
                    setInterval(scanFrame, 2500); // Scan tiap 2.5 detik
                };
            } catch (e) { alert("Error Kamera: " + e); }
        }

        async function scanFrame() {
            const tCanvas = document.createElement('canvas');
            tCanvas.width = video.videoWidth;
            tCanvas.height = video.videoHeight;
            tCanvas.getContext('2d').drawImage(video, 0, 0);
            
            try {
                const res = await fetch("{{ route('detection.process') }}", {
                    method: "POST",
                    headers: { 
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content 
                    },
                    body: JSON.stringify({ image: tCanvas.toDataURL('image/jpeg', 0.6) })
                });
                const json = await res.json();
                if(json.status === 'success') processResult(json.data);
            } catch (e) { console.error(e); }
        }

        function processResult(objects) {
            ctx.clearRect(0, 0, canvas.width, canvas.height); // Bersihkan canvas

            objects.forEach(obj => {
                const [x, y, w, h] = [obj.box[0], obj.box[1], obj.box[2]-obj.box[0], obj.box[3]-obj.box[1]];
                
                // GAMBAR KOTAK
                ctx.lineWidth = 3;
                
                // --- KASUS 1: PESANAN ---
                if (obj.order_info && obj.order_info.found) {
                    ctx.strokeStyle = '#3b82f6'; // Biru
                    ctx.strokeRect(x, y, w, h);
                    
                    if (lastCode !== obj.order_info.resi) {
                        lastCode = obj.order_info.resi;
                        showOrderCard(obj.order_info);
                    }
                }
                // --- KASUS 2: PRODUK (ADA DI DB) ---
                else if (obj.product_info && obj.product_info.found) {
                    ctx.strokeStyle = '#22c55e'; // Hijau
                    ctx.strokeRect(x, y, w, h);

                    if (lastCode !== obj.product_info.code) {
                        lastCode = obj.product_info.code;
                        showProductCard(obj.product_info, true);
                    }
                }
                // --- KASUS 3: BARANG BARU (TIDAK ADA DI DB) ---
                else if (obj.product_info && !obj.product_info.found) {
                    ctx.strokeStyle = '#eab308'; // Kuning
                    ctx.strokeRect(x, y, w, h);

                    if (lastCode !== obj.product_info.code) {
                        lastCode = obj.product_info.code;
                        showProductCard(obj.product_info, false);
                    }
                }
                // --- KASUS 4: OBJEK LAIN (ORANG/AYAM) ---
                else {
                    ctx.strokeStyle = 'rgba(255,255,255,0.3)';
                    ctx.strokeRect(x, y, w, h);
                }
            });
        }

        function showOrderCard(data) {
            resultContainer.classList.remove('hidden');
            cardOrder.classList.remove('hidden');
            cardProduct.classList.add('hidden'); // Sembunyikan kartu produk

            ordEkspedisi.innerText = data.ekspedisi;
            ordResi.innerText = data.resi;
            ordPenerima.innerText = data.penerima;
            ordStatus.innerText = data.status;
            ordAlamat.innerText = data.alamat;
            
            showToast("📦 Paket Terdeteksi!", "success");
        }

        function showProductCard(data, isFound) {
            resultContainer.classList.remove('hidden');
            cardProduct.classList.remove('hidden');
            cardOrder.classList.add('hidden'); // Sembunyikan kartu order

            inpSku.value = data.code;
            
            if (isFound) {
                // Produk Lama
                prdStatusText.innerText = "Edit Produk";
                inpName.value = data.name;
                inpPrice.value = data.raw_price;
                btnSavePrd.innerText = "Update Harga/Nama";
                btnSavePrd.className = "w-full bg-blue-600 hover:bg-blue-500 text-white font-bold py-2 rounded-lg text-sm mt-1 transition";
                showToast("✅ Produk Ditemukan", "success");
            } else {
                // Produk Baru
                prdStatusText.innerText = "Input Produk Baru";
                inpName.value = "";
                inpPrice.value = "";
                btnSavePrd.innerText = "Simpan Produk Baru";
                btnSavePrd.className = "w-full bg-green-600 hover:bg-green-500 text-white font-bold py-2 rounded-lg text-sm mt-1 transition";
                showToast("⚠️ Produk Baru Terdeteksi", "warning");
            }
        }

        // Simpan Produk (Sama seperti sebelumnya)
        async function saveProduct(e) {
            e.preventDefault();
            try {
                const res = await fetch("{{ route('apps.store') }}", {
                    method: "POST",
                    headers: { 
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content 
                    },
                    body: JSON.stringify({
                        barcode: inpSku.value,
                        name: inpName.value,
                        price: inpPrice.value
                    })
                });
                const json = await res.json();
                if(json.status === 'success') {
                    showToast("Berhasil Disimpan!", "success");
                    lastCode = ""; // Reset biar bisa scan lagi
                }
            } catch (e) { alert("Gagal simpan"); }
        }

        function showToast(msg, type) {
            const t = document.getElementById('toast');
            t.innerText = msg;
            t.classList.remove('hidden');
            t.className = `fixed bottom-10 px-4 py-2 rounded-lg text-sm shadow-xl z-50 transition-all ${type === 'success' ? 'bg-green-600 text-white' : 'bg-yellow-600 text-white'}`;
            setTimeout(() => t.classList.add('hidden'), 3000);
        }
    </script>
    <style>@keyframes scan { 0%,100%{top:0%;opacity:0} 50%{opacity:1} 100%{top:100%;opacity:0} }</style>
</body>
</html>