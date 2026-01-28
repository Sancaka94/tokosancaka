<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Presentasi UMKM - Tahun Pertama</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .slide { display: none; }
        .slide.active { display: flex; animation: fadeIn 0.5s ease-in-out; }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .bg-gradient-custom {
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
        }
    </style>
</head>
<body class="bg-gradient-custom text-white font-sans overflow-hidden">

    <div class="h-screen w-full flex flex-col justify-center items-center p-6 relative">
        
        <div class="slide active flex-col items-center text-center max-w-4xl">
            <h1 class="text-5xl md:text-7xl font-extrabold mb-6 text-yellow-400">SURVIVAL YEAR</h1>
            <p class="text-2xl md:text-3xl font-light text-slate-300">Kendala & Cobaan Umum UMKM di Tahun Pertama Berdiri</p>
            <div class="mt-10 p-4 bg-white/10 rounded-lg backdrop-blur-md">
                <p class="text-sm italic italic">Klik tombol di bawah atau gunakan panah untuk lanjut</p>
            </div>
        </div>

        <div class="slide flex-col max-w-5xl">
            <h2 class="text-4xl font-bold mb-8 text-orange-400 border-b-2 border-orange-400 pb-2">🔥 Kendala Internal (1-3)</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-white/5 p-6 rounded-xl border border-white/10">
                    <h3 class="text-xl font-bold mb-3 text-yellow-300">1. Modal Terbatas</h3>
                    <ul class="text-sm space-y-2 text-slate-300">
                        <li>• Modal cepat habis (sewa/alat)</li>
                        <li>• Cashflow tidak stabil</li>
                        <li>• Sulit memutar uang</li>
                    </ul>
                </div>
                <div class="bg-white/5 p-6 rounded-xl border border-white/10">
                    <h3 class="text-xl font-bold mb-3 text-yellow-300">2. Manajemen Keuangan</h3>
                    <ul class="text-sm space-y-2 text-slate-300">
                        <li>• Uang usaha vs pribadi campur</li>
                        <li>• Tidak ada pencatatan rapi</li>
                        <li>• Tidak tahu untung-rugi real</li>
                    </ul>
                </div>
                <div class="bg-white/5 p-6 rounded-xl border border-white/10">
                    <h3 class="text-xl font-bold mb-3 text-yellow-300">3. SDM Terbatas</h3>
                    <ul class="text-sm space-y-2 text-slate-300">
                        <li>• Owner merangkap semua peran</li>
                        <li>• Karyawan belum terlatih</li>
                        <li>• Sistem belum terbentuk</li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="slide flex-col max-w-5xl">
            <h2 class="text-4xl font-bold mb-8 text-orange-400 border-b-2 border-orange-400 pb-2">🔥 Kendala Internal (4-5)</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div class="bg-white/5 p-8 rounded-xl border border-white/10">
                    <h3 class="text-2xl font-bold mb-4 text-yellow-300">4. Produk Belum Matang</h3>
                    <p class="text-slate-300 leading-relaxed">Kualitas belum konsisten, standar operasional (SOP) belum jelas, dan masih dalam tahap banyak trial & error.</p>
                </div>
                <div class="bg-white/5 p-8 rounded-xl border border-white/10">
                    <h3 class="text-2xl font-bold mb-4 text-yellow-300">5. Mental & Emosi</h3>
                    <p class="text-slate-300 leading-relaxed">Mudah stres, overthinking, ragu untuk lanjut, dan sering merasa "kok sepi ya?".</p>
                </div>
            </div>
        </div>

        <div class="slide flex-col max-w-5xl">
            <h2 class="text-4xl font-bold mb-8 text-blue-400 border-b-2 border-blue-400 pb-2">🌍 Kendala Eksternal</h2>
            <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                <div class="bg-blue-900/30 p-4 rounded-lg text-center border border-blue-500/30">
                    <i class="fas fa-bullhorn mb-2 text-2xl text-blue-300"></i>
                    <p class="text-xs font-bold">Brand Awareness Nol</p>
                </div>
                <div class="bg-blue-900/30 p-4 rounded-lg text-center border border-blue-500/30">
                    <i class="fas fa-handshake-slash mb-2 text-2xl text-blue-300"></i>
                    <p class="text-xs font-bold">Persaingan Harga</p>
                </div>
                <div class="bg-blue-900/30 p-4 rounded-lg text-center border border-blue-500/30">
                    <i class="fas fa-map-marker-alt mb-2 text-2xl text-blue-300"></i>
                    <p class="text-xs font-bold">Lokasi Sepi</p>
                </div>
                <div class="bg-blue-900/30 p-4 rounded-lg text-center border border-blue-500/30">
                    <i class="fas fa-gavel mb-2 text-2xl text-blue-300"></i>
                    <p class="text-xs font-bold">Bingung Perizinan</p>
                </div>
                <div class="bg-blue-900/30 p-4 rounded-lg text-center border border-blue-500/30">
                    <i class="fas fa-laptop-code mb-2 text-2xl text-blue-300"></i>
                    <p class="text-xs font-bold">Gaptek Digital</p>
                </div>
            </div>
            <div class="mt-8 p-6 bg-red-500/10 border-l-4 border-red-500 italic">
                "Banyak UMKM tumbang karena fokus ke produk tapi lupa marketing, fokus omzet tapi lupa profit."
            </div>
        </div>

        <div class="slide flex-col max-w-4xl text-center">
            <h2 class="text-4xl font-bold mb-6 text-green-400 italic">✨ Fakta Realita</h2>
            <div class="text-3xl md:text-5xl font-extrabold text-white leading-tight">
                "Tahun pertama bukan tentang untung besar, tapi tentang <span class="text-red-500">BERTAHAN HIDUP</span>."
            </div>
            <div class="mt-12 grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="bg-green-600 p-4 rounded-lg shadow-lg">Konsistensi</div>
                <div class="bg-green-600 p-4 rounded-lg shadow-lg">Kesabaran</div>
                <div class="bg-green-600 p-4 rounded-lg shadow-lg">Ketahanan</div>
                <div class="bg-green-600 p-4 rounded-lg shadow-lg">Tawakal</div>
            </div>
        </div>

        <div class="slide flex-col max-w-5xl">
            <h2 class="text-4xl font-bold mb-6 text-yellow-400">🔑 Kunci Lolos Tahun Pertama</h2>
            <div class="columns-1 md:columns-2 gap-10 space-y-4 text-xl">
                <p>✅ Cashflow > Profit Besar</p>
                <p>✅ Sistem sederhana tapi jalan</p>
                <p>✅ Branding meski kecil</p>
                <p>✅ Konsisten posting & promosi</p>
                <p>✅ Bangun database pelanggan</p>
                <p>✅ Catat keuangan & perbaiki produk</p>
                <p>✅ Adaptif & Mental tahan banting</p>
            </div>
        </div>

        <div class="absolute bottom-10 flex gap-4">
            <button onclick="prevSlide()" class="bg-white/10 hover:bg-white/20 px-6 py-2 rounded-full transition border border-white/20">
                <i class="fas fa-chevron-left"></i> Prev
            </button>
            <button onclick="nextSlide()" class="bg-yellow-500 hover:bg-yellow-400 text-black font-bold px-8 py-2 rounded-full transition shadow-lg">
                Next <i class="fas fa-chevron-right ml-2"></i>
            </button>
        </div>

        <div class="absolute bottom-4 text-xs text-slate-500" id="slideNumber">Slide 1 / 6</div>
    </div>

    <script>
        let currentSlide = 0;
        const slides = document.querySelectorAll('.slide');
        const slideNumberText = document.getElementById('slideNumber');

        function showSlide(index) {
            slides.forEach(s => s.classList.remove('active'));
            slides[index].classList.add('active');
            slideNumberText.innerText = `Slide ${index + 1} / ${slides.length}`;
        }

        function nextSlide() {
            if (currentSlide < slides.length - 1) {
                currentSlide++;
                showSlide(currentSlide);
            }
        }

        function prevSlide() {
            if (currentSlide > 0) {
                currentSlide--;
                showSlide(currentSlide);
            }
        }

        // Navigasi Keyboard
        document.addEventListener('keydown', (e) => {
            if (e.key === "ArrowRight") nextSlide();
            if (e.key === "ArrowLeft") prevSlide();
        });
    </script>
</body>
</html>