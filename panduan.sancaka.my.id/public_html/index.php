<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panduan Drop Paket SPX - Sancaka Express</title>
    <meta name="description" content="Panduan dan tata cara drop paket SPX di Sancaka Express Ngawi.">

    <!-- Favicon -->
    <link rel="icon" href="https://tokosancaka.com/storage/uploads/sancaka.png" type="image/png">

    <!-- Fonts: Inter (Professional & Clean) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>

    <!-- Configuration -->
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    },
                    colors: {
                        // Dominant colors as requested
                        primary: {
                            50: '#eff6ff',
                            100: '#dbeafe',
                            500: '#3b82f6', // blue-500
                            600: '#2563eb', // blue-600
                            700: '#1d4ed8',
                            900: '#1e3a8a',
                        }
                    },
                    boxShadow: {
                        'soft': '0 4px 20px -2px rgba(0, 0, 0, 0.05)',
                    }
                }
            }
        }
    </script>

    <style>
        /* Custom Utilities */
        .glass-nav {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
        }
        .step-card {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .step-card:hover {
            transform: translateY(-5px);
            border-color: #3b82f6;
        }
        
      
    </style>
</head>
<body class="bg-slate-50 text-slate-800 antialiased selection:bg-primary-600 selection:text-white">

    <!-- Navbar -->
    <nav class="fixed w-full z-50 glass-nav border-b border-slate-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-20">
                <!-- Logo Area -->
                <div class="flex items-center gap-3">
                    <img class="h-10 w-auto" src="https://tokosancaka.com/storage/uploads/sancaka.png" alt="Sancaka Logo">
                    <div class="hidden sm:block">
                        <h1 class="font-bold text-xl text-slate-900 leading-none">Sancaka Express</h1>
                        <span class="text-xs font-medium text-primary-600 tracking-wider uppercase">Official Drop Point</span>
                    </div>
                </div>

                <!-- Desktop Menu -->
                <div class="hidden md:flex items-center space-x-8">
                    <a href="#panduan" class="text-sm font-medium text-slate-600 hover:text-primary-600 transition-colors">Panduan</a>
                    <a href="#lokasi" class="text-sm font-medium text-slate-600 hover:text-primary-600 transition-colors">Lokasi</a>
                </div>

                <!-- CTA Button -->
                <div>
                    <a href="https://wa.me/6285745808809" target="_blank" class="inline-flex items-center gap-2 bg-primary-600 hover:bg-primary-700 text-white px-5 py-2.5 rounded-full text-sm font-medium transition-all shadow-lg shadow-primary-500/30">
                        <i data-lucide="message-circle" class="w-4 h-4"></i>
                        <span>Hubungi Admin</span>
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <header class="pt-32 pb-20 lg:pt-40 lg:pb-28 relative overflow-hidden">
        <!-- Background Decor -->
        <div class="absolute top-0 right-0 -mr-24 -mt-24 w-96 h-96 bg-primary-100 rounded-full blur-3xl opacity-50"></div>
        <div class="absolute bottom-0 left-0 -ml-24 -mb-24 w-80 h-80 bg-blue-100 rounded-full blur-3xl opacity-50"></div>

        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center relative z-10">
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-primary-50 text-primary-600 text-xs font-bold uppercase tracking-wide mb-6 border border-primary-100">
                <span class="relative flex h-2 w-2">
                  <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-primary-400 opacity-75"></span>
                  <span class="relative inline-flex rounded-full h-2 w-2 bg-primary-500"></span>
                </span>
                Sancaka Express Drop Point
            </div>
            
            <h1 class="text-4xl md:text-5xl lg:text-6xl font-extrabold text-slate-900 tracking-tight mb-6">
                Tempat Drop Paket <br>
                <span class="text-transparent bg-clip-text bg-gradient-to-r from-primary-600 to-primary-500">SPX SANCAKA</span>
            </h1>
            
            <p class="text-lg text-slate-600 max-w-2xl mx-auto leading-relaxed mb-10">
                Selamat datang. Demi kenyamanan bersama, mohon ikuti Syarat dan Ketentuan pelanggan di bawah ini untuk memproses paket Anda dengan cepat.
            </p>

            <div class="flex flex-col sm:flex-row justify-center gap-4">
                <a href="#panduan" class="px-8 py-3.5 rounded-xl bg-slate-900 text-white font-semibold hover:bg-slate-800 transition shadow-soft flex items-center justify-center">
                    Lihat Panduan
                </a>
                
                <a href="https://tokosancaka.com/scan-spx" target="_blank" class="px-8 py-3.5 rounded-xl bg-white text-slate-700 border border-slate-200 font-semibold hover:border-primary-200 hover:text-primary-600 transition flex items-center justify-center gap-2 shadow-sm">
                    <i data-lucide="qr-code" class="w-5 h-5"></i> Scan Barcode
                </a>
            </div>
        </div>
    </header>

    <!-- Guide Section (Core Content) -->
    <section id="panduan" class="py-20 bg-white relative">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-3xl font-bold text-slate-900">Prosedur Pengiriman</h2>
                <p class="mt-4 text-slate-500">Ikuti 5 langkah mudah berikut ini</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                
                <!-- Step 1 -->
                <div class="step-card group bg-slate-50 p-8 rounded-2xl border border-slate-100 relative overflow-hidden">
                    <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity">
                        <i data-lucide="package" class="w-24 h-24 text-primary-600"></i>
                    </div>
                    <div class="w-12 h-12 bg-white rounded-xl shadow-sm flex items-center justify-center text-primary-600 font-bold text-xl mb-6 group-hover:bg-primary-600 group-hover:text-white transition-colors">1</div>
                    <h3 class="text-xl font-bold text-slate-900 mb-3">Drop Paket</h3>
                    <p class="text-slate-600 text-sm leading-relaxed">
                        Silakan letakkan paket Anda dengan rapi <strong>di atas meja</strong> yang telah disediakan oleh petugas.
                    </p>
                </div>

                <!-- Step 2 -->
                <div class="step-card group bg-slate-50 p-8 rounded-2xl border border-slate-100 relative overflow-hidden flex flex-col h-full">
                    <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity">
                        <i data-lucide="printer" class="w-24 h-24 text-primary-600"></i>
                    </div>
                    <div class="w-12 h-12 bg-white rounded-xl shadow-sm flex items-center justify-center text-primary-600 font-bold text-xl mb-6 group-hover:bg-primary-600 group-hover:text-white transition-colors">2</div>
                    <h3 class="text-xl font-bold text-slate-900 mb-3">Wajib Print Resi</h3>
                    <p class="text-slate-600 text-sm leading-relaxed mb-4 flex-grow">
                        Pastikan resi sudah tertempel. Jika belum, <strong>wajib print resi</strong>.
                        <br><span class="text-red-500 text-xs mt-2 block font-medium">*Dikenakan biaya Rp 1.000 jika tidak print mandiri.</span>
                    </p>
                    
                    <!-- Tombol Bayar (Moved Here) -->
                    <button onclick="openModal()" class="w-full mt-auto px-4 py-2.5 rounded-lg bg-primary-600 text-white font-medium hover:bg-primary-700 transition shadow-sm hover:shadow-md flex items-center justify-center gap-2 text-sm">
                        <i data-lucide="wallet" class="w-4 h-4"></i>
                        Bayar QRIS
                    </button>
                </div>

                <!-- Step 3 (Interactive) -->
                <div class="step-card group bg-gradient-to-br from-primary-50 to-white p-8 rounded-2xl border border-primary-100 relative overflow-hidden shadow-sm flex flex-col h-full">
                    <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity">
                        <i data-lucide="scan-line" class="w-24 h-24 text-primary-600"></i>
                    </div>
                    <div class="w-12 h-12 bg-primary-600 rounded-xl shadow-lg shadow-primary-500/30 flex items-center justify-center text-white font-bold text-xl mb-6">3</div>
                    <h3 class="text-xl font-bold text-slate-900 mb-3">Scan Barcode</h3>
                    <p class="text-slate-600 text-sm leading-relaxed mb-5 flex-grow">
                        Wajib scan barcode paket Anda untuk input data ke sistem Sancaka.
                    </p>
                    <a href="https://tokosancaka.com/scan-spx" target="_blank" class="inline-flex w-full mt-auto items-center justify-center gap-2 bg-primary-600 text-white px-4 py-2.5 rounded-lg text-sm font-medium hover:bg-primary-700 transition">
                        <i data-lucide="qr-code" class="w-4 h-4"></i> Klik untuk Scan
                    </a>
                </div>

                <!-- Step 4 -->
                <div class="step-card group bg-slate-50 p-8 rounded-2xl border border-slate-100 relative overflow-hidden">
                    <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity">
                        <i data-lucide="file-check" class="w-24 h-24 text-primary-600"></i>
                    </div>
                    <div class="w-12 h-12 bg-white rounded-xl shadow-sm flex items-center justify-center text-primary-600 font-bold text-xl mb-6 group-hover:bg-primary-600 group-hover:text-white transition-colors">4</div>
                    <h3 class="text-xl font-bold text-slate-900 mb-3">Simpan Surat Jalan</h3>
                    <p class="text-slate-600 text-sm leading-relaxed">
                        Jangan lupa untuk <strong>Save/Screenshot</strong> Surat Jalan digital yang muncul dari website Sancaka sebagai bukti serah terima.
                    </p>
                </div>

                <!-- Step 5 -->
                <div class="step-card group bg-slate-50 p-8 rounded-2xl border border-slate-100 relative overflow-hidden md:col-span-2 lg:col-span-2">
                    <div class="flex flex-col md:flex-row gap-6 items-start md:items-center h-full">
                        <div class="w-12 h-12 flex-shrink-0 bg-white rounded-xl shadow-sm flex items-center justify-center text-primary-600 font-bold text-xl group-hover:bg-primary-600 group-hover:text-white transition-colors">5</div>
                        <div>
                            <h3 class="text-xl font-bold text-slate-900 mb-2">Selesai & Tinggalkan</h3>
                            <p class="text-slate-600 text-sm leading-relaxed">
                                Jika semua proses di atas sudah selesai, paket bisa langsung ditinggal. <br>
                                <strong>Terimakasih Kak!</strong>
                            </p>
                        </div>
                        <div class="ml-auto opacity-20 hidden md:block">
                            <i data-lucide="thumbs-up" class="w-20 h-20 text-primary-600"></i>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </section>

    <!-- Map & Location Section -->
    <section id="lokasi" class="py-20 bg-slate-50 border-t border-slate-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white rounded-3xl shadow-soft overflow-hidden flex flex-col lg:flex-row">
                
                <!-- Info Side -->
                <div class="lg:w-1/3 bg-slate-900 p-10 text-white flex flex-col justify-center relative overflow-hidden">
                    <div class="absolute top-0 right-0 w-64 h-64 bg-primary-600 rounded-full mix-blend-multiply filter blur-3xl opacity-20 -mr-16 -mt-16"></div>
                    
                    <h3 class="text-2xl font-bold mb-8 relative z-10">Informasi Lokasi</h3>
                    
                    <div class="space-y-8 relative z-10">
                        <div class="flex gap-4">
                            <div class="w-10 h-10 rounded-lg bg-slate-800 flex items-center justify-center flex-shrink-0 text-primary-500">
                                <i data-lucide="map-pin" class="w-5 h-5"></i>
                            </div>
                            <div>
                                <h4 class="font-semibold text-lg text-slate-100">Alamat</h4>
                                <p class="text-slate-400 text-sm mt-1 leading-relaxed">
                                    Jl. Dr. Wahidin No.18A<br>
                                    RT.22/05 Ketanggi, Ngawi<br>
                                    Jawa Timur 63211
                                </p>
                            </div>
                        </div>

                        <div class="flex gap-4">
                            <div class="w-10 h-10 rounded-lg bg-slate-800 flex items-center justify-center flex-shrink-0 text-primary-500">
                                <i data-lucide="building-2" class="w-5 h-5"></i>
                            </div>
                            <div>
                                <h4 class="font-semibold text-lg text-slate-100">Kantor</h4>
                                <p class="text-slate-400 text-sm mt-1">
                                    CV. Sancaka Karya Hutama
                                </p>
                            </div>
                        </div>

                        <div class="flex gap-4">
                            <div class="w-10 h-10 rounded-lg bg-slate-800 flex items-center justify-center flex-shrink-0 text-primary-500">
                                <i data-lucide="phone" class="w-5 h-5"></i>
                            </div>
                            <div>
                                <h4 class="font-semibold text-lg text-slate-100">Kontak Admin</h4>
                                <p class="text-slate-400 text-sm mt-1 mb-3">
                                    Butuh bantuan atau informasi lebih lanjut?
                                </p>
                                <a href="https://wa.me/6285745808809" class="text-primary-400 hover:text-white font-medium transition flex items-center gap-2">
                                    0857 4580 8809 <i data-lucide="arrow-right" class="w-4 h-4"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Map Side -->
                <div class="lg:w-2/3 min-h-[400px] relative bg-slate-200">
                    <!-- Google Maps Embed: Using generic query for the address provided -->
                    <iframe 
                        src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3956.270769854737!2d111.442!3d-7.412!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2e79e623a078440d%3A0xc6e4313f8d388647!2sJl.%20Dr.%20Wahidin%20No.18A%2C%20Ketanggi%2C%20Kec.%20Ngawi%2C%20Kabupaten%20Ngawi%2C%20Jawa%20Timur%2063211!5e0!3m2!1sid!2sid!4v1700000000000!5m2!1sid!2sid" 
                        class="absolute inset-0 w-full h-full border-0"
                        allowfullscreen="" 
                        loading="lazy" 
                        referrerpolicy="no-referrer-when-downgrade"
                        title="Peta Lokasi Sancaka Ngawi">
                    </iframe>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-white border-t border-slate-200 pt-16 pb-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col md:flex-row justify-between items-center md:items-start text-center md:text-left">
                <div class="mb-8 md:mb-0">
                    <div class="flex items-center justify-center md:justify-start gap-2 mb-4">
                        <img class="h-8 w-auto grayscale opacity-80" src="https://tokosancaka.com/storage/uploads/sancaka.png" alt="Sancaka Logo">
                        <span class="font-bold text-lg text-slate-800">Sancaka Express</span>
                    </div>
                    <h3 class="font-bold text-slate-900 text-lg">CV. Sancaka Karya Hutama</h3>
                    <p class="mt-2 text-slate-500 text-sm max-w-xs mx-auto md:mx-0">
                        Mitra logistik terpercaya Anda. Melayani dengan sepenuh hati di Ngawi, Jawa Timur.
                    </p>
                </div>
                
                <div class="text-sm text-slate-500">
                    <p class="font-medium text-slate-900 mb-2">Alamat Operasional</p>
                    <p>Jl. Dr. Wahidin No.18A RT.22/05</p>
                    <p>Ketanggi, Ngawi</p>
                    <p>Jawa Timur 63211</p>
                </div>
            </div>

            <div class="mt-12 pt-8 border-t border-slate-100 text-center text-xs text-slate-400">
                <p>&copy; 2026 Sancaka Express. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- Payment Modal (Hidden by Default) -->
    <div id="paymentModal" class="fixed inset-0 z-[100] hidden bg-slate-900/80 backdrop-blur-sm items-center justify-center p-4 transition-all duration-300 opacity-0">
        <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full relative overflow-hidden transform scale-95 transition-all duration-300" id="modalContent">
            <!-- Header Modal -->
            <div class="p-4 flex justify-between items-center border-b border-slate-100">
                <h3 class="font-bold text-lg text-slate-800 flex items-center gap-2">
                    <i data-lucide="scan-line" class="w-5 h-5 text-primary-600"></i> QRIS Pembayaran
                </h3>
                <button onclick="closeModal()" class="text-slate-400 hover:text-red-500 transition-colors p-1 rounded-full hover:bg-red-50">
                    <i data-lucide="x" class="w-6 h-6"></i>
                </button>
            </div>
            
            <!-- Body Modal -->
            <div class="p-6 flex flex-col items-center">
                <div class="bg-white p-2 border border-slate-200 rounded-xl shadow-sm mb-6 w-full max-w-[320px] mx-auto overflow-hidden">
                    <img src="https://tokosancaka.com/public/storage/logo/qris-sancaka.jpeg" 
                         alt="QRIS Sancaka" 
                         class="w-full h-auto max-h-[50vh] object-contain rounded-lg">
                </div>
                
                <a href="https://tokosancaka.com/public/storage/logo/qris-sancaka.jpeg" download="QRIS-Sancaka.jpeg" target="_blank" class="inline-flex items-center gap-2 bg-primary-600 text-white px-6 py-3.5 rounded-xl font-medium hover:bg-primary-700 transition w-full justify-center shadow-lg shadow-primary-500/20">
                    <i data-lucide="download" class="w-5 h-5"></i>
                    Download Gambar
                </a>
                
                <p class="text-xs text-slate-400 mt-4 text-center leading-relaxed">
                    Setelah pembayaran, mohon konfirmasi ke Admin via WhatsApp.<br>
                    Terima kasih telah menggunakan layanan kami.
                </p>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script>
        lucide.createIcons();

        // Modal Logic
        function openModal() {
            const modal = document.getElementById('paymentModal');
            const content = document.getElementById('modalContent');
            
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            
            // Timeout agar transition effect berjalan setelah display flex
            setTimeout(() => {
                modal.classList.remove('opacity-0');
                content.classList.remove('scale-95');
                content.classList.add('scale-100');
            }, 10);
        }

        function closeModal() {
            const modal = document.getElementById('paymentModal');
            const content = document.getElementById('modalContent');
            
            modal.classList.add('opacity-0');
            content.classList.remove('scale-100');
            content.classList.add('scale-95');
            
            // Timeout sesuai durasi transisi (300ms)
            setTimeout(() => {
                modal.classList.remove('flex');
                modal.classList.add('hidden');
            }, 300);
        }

        // Close modal when clicking outside
        document.getElementById('paymentModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });

        // Close on Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && !document.getElementById('paymentModal').classList.contains('hidden')) {
                closeModal();
            }
        });
    </script>
    
    <a href="https://wa.me/6285745808809" 
   target="_blank" 
   class="fixed bottom-6 right-6 z-[60] flex items-center justify-center w-16 h-16 bg-white text-slate-900 rounded-full shadow-2xl hover:-translate-y-1 hover:shadow-primary-500/20 transition-all duration-300 animate-wa group border border-slate-100"
   title="Chat WhatsApp">
   
    <div class="relative w-full h-full p-3">
        <img src="https://tokosancaka.com/public/storage/logo/wa.png" 
             alt="WhatsApp" 
             class="w-full h-full object-contain">
    </div>

    <span class="absolute top-1 right-1 flex h-3 w-3">
        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
        <span class="relative inline-flex rounded-full h-3 w-3 bg-red-500"></span>
    </span>

    <span class="absolute right-20 top-1/2 -translate-y-1/2 bg-slate-900 text-white text-xs font-medium py-2 px-4 rounded-lg opacity-0 group-hover:opacity-100 transition-opacity duration-300 whitespace-nowrap shadow-xl pointer-events-none">
        Chat Admin Sancaka
        <span class="absolute top-1/2 -right-1 -mt-1 border-4 border-transparent border-l-slate-900"></span>
    </span>
</a>
    
</body>
</html>