<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sancaka POS - Aplikasi Kasir Terlengkap</title>

    <link rel="icon" href="https://tokosancaka.com/storage/uploads/logo.jpeg" type="image/jpeg">

    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://unpkg.com/lucide@latest"></script>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;900&display=swap" rel="stylesheet">

    <style>
        body { font-family: 'Inter', sans-serif; }
        [x-cloak] { display: none !important; }

        /* Utilitas Warna Solid */
        .bg-sancaka-blue { background-color: #1e3a8a; } /* Blue 900 */
        .bg-sancaka-red { background-color: #dc2626; } /* Red 600 */
        .text-sancaka-blue { color: #1e3a8a; }
        .text-sancaka-red { color: #dc2626; }
        .border-sancaka-blue { border-color: #1e3a8a; }

        /* Modal Transition */
        .modal-enter { transition: opacity 0.3s ease-out; }
        .modal-enter-start { opacity: 0; }
        .modal-enter-end { opacity: 1; }
        .modal-leave { transition: opacity 0.2s ease-in; }
        .modal-leave-start { opacity: 1; }
        .modal-leave-end { opacity: 0; }
    </style>
</head>
<body class="bg-gray-50 text-gray-800"
      x-data="{
          showModal: false,
          selectedPlan: 'monthly',
          openOrder(plan) {
              this.selectedPlan = plan;
              this.showModal = true;
          }
      }">

    <nav class="fixed top-0 w-full bg-white border-b border-gray-200 z-50 shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-20 items-center">
                <div class="flex items-center gap-3">
                    <img src="https://tokosancaka.com/storage/uploads/logo.jpeg" alt="Logo Sancaka" class="h-10 w-10 rounded-lg object-cover border border-gray-200">
                    <div>
                        <h1 class="text-xl font-bold text-sancaka-blue tracking-tight">SANCAKA POS</h1>
                        <p class="text-[10px] text-gray-500 font-semibold tracking-widest uppercase">Smart Business Solution</p>
                    </div>
                </div>
                <div class="hidden md:flex space-x-8 text-sm font-bold text-gray-600">
                    <a href="#fitur" class="hover:text-sancaka-blue transition">Fitur</a>
                    <a href="#harga" class="hover:text-sancaka-blue transition">Harga</a>
                    <a href="#faq" class="hover:text-sancaka-blue transition">FAQ</a>
                    <a href="#kontak" class="hover:text-sancaka-blue transition">Kontak</a>
                </div>
                <button @click="openOrder('trial')" class="bg-sancaka-red text-white px-5 py-2.5 rounded-lg font-bold text-sm hover:bg-red-700 transition shadow-sm">
                    Coba Gratis
                </button>
            </div>
        </div>
    </nav>

    <header class="pt-32 pb-20 bg-sancaka-blue text-white overflow-hidden relative">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10 flex flex-col md:flex-row items-center">
            <div class="md:w-1/2 mb-10 md:mb-0">
                <span class="inline-block py-1 px-3 rounded-full bg-blue-800 text-blue-200 text-xs font-bold mb-4 border border-blue-700">
                    VERSI 2.0 TELAH HADIR 🚀
                </span>
                <h2 class="text-4xl md:text-5xl font-black leading-tight mb-6">
                    Kelola Bisnis Jadi Lebih <span class="text-blue-300">Cepat & Efisien</span>
                </h2>
                <p class="text-lg text-blue-100 mb-8 leading-relaxed max-w-lg">
                    Aplikasi kasir online & offline yang dirancang khusus untuk Percetakan, Retail, F&B, dan Jasa. Pantau omset dari mana saja.
                </p>
                <div class="flex gap-4">
                    <button @click="openOrder('trial')" class="bg-sancaka-red text-white px-8 py-3.5 rounded-xl font-bold text-base hover:bg-red-700 transition shadow-lg flex items-center gap-2">
                        Mulai Sekarang <i data-lucide="arrow-right" class="w-5 h-5"></i>
                    </button>
                    <a href="#fitur" class="bg-white text-sancaka-blue px-8 py-3.5 rounded-xl font-bold text-base hover:bg-gray-100 transition shadow-lg">
                        Pelajari Fitur
                    </a>
                </div>
            </div>
            <div class="md:w-1/2 flex justify-center relative">
                <div class="absolute inset-0 bg-blue-500 rounded-full blur-3xl opacity-20 transform translate-x-10 translate-y-10"></div>
                <img src="https://tokosancaka.com/storage/uploads/logos/jWfpluPG2sSkvcvaOnYTNRqjizdUbSbeGKyv1F3A.jpg"
                     alt="Dashboard POS"
                     class="relative rounded-2xl shadow-2xl border-4 border-blue-800/50 transform rotate-2 hover:rotate-0 transition duration-500">
            </div>
        </div>
        <svg class="absolute bottom-0 w-full text-gray-50 h-16 md:h-24" preserveAspectRatio="none" viewBox="0 0 1440 320">
            <path fill="currentColor" fill-opacity="1" d="M0,224L48,213.3C96,203,192,181,288,181.3C384,181,480,203,576,224C672,245,768,267,864,261.3C960,256,1056,224,1152,197.3C1248,171,1344,149,1392,138.7L1440,128L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path>
        </svg>
    </header>

    <section id="fitur" class="py-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-3xl md:text-4xl font-black text-gray-900 mb-4">Fitur Unggulan</h2>
                <p class="text-gray-600 max-w-2xl mx-auto">Semua yang Anda butuhkan untuk mengelola operasional bisnis ada di sini.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm hover:shadow-xl hover:-translate-y-1 transition duration-300">
                    <div class="bg-blue-100 w-12 h-12 rounded-lg flex items-center justify-center text-sancaka-blue mb-4">
                        <i data-lucide="monitor-smartphone" class="w-6 h-6"></i>
                    </div>
                    <h3 class="font-bold text-lg mb-2">Full Responsive</h3>
                    <p class="text-sm text-gray-500 leading-relaxed">Akses lancar dari PC, Tablet, maupun Smartphone tanpa instalasi rumit.</p>
                </div>

                <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm hover:shadow-xl hover:-translate-y-1 transition duration-300">
                    <div class="bg-blue-100 w-12 h-12 rounded-lg flex items-center justify-center text-sancaka-blue mb-4">
                        <i data-lucide="app-window" class="w-6 h-6"></i>
                    </div>
                    <h3 class="font-bold text-lg mb-2">Aplikasi Windows</h3>
                    <p class="text-sm text-gray-500 leading-relaxed">Tersedia versi desktop native agar kasir lebih stabil dan cepat.</p>
                </div>

                <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm hover:shadow-xl hover:-translate-y-1 transition duration-300">
                    <div class="bg-blue-100 w-12 h-12 rounded-lg flex items-center justify-center text-sancaka-blue mb-4">
                        <i data-lucide="wifi" class="w-6 h-6"></i>
                    </div>
                    <h3 class="font-bold text-lg mb-2">Online & Offline</h3>
                    <p class="text-sm text-gray-500 leading-relaxed">Internet mati? Tetap jualan! Data tersinkronisasi otomatis saat online.</p>
                </div>

                <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm hover:shadow-xl hover:-translate-y-1 transition duration-300">
                    <div class="bg-blue-100 w-12 h-12 rounded-lg flex items-center justify-center text-sancaka-blue mb-4">
                        <i data-lucide="store" class="w-6 h-6"></i>
                    </div>
                    <h3 class="font-bold text-lg mb-2">Multi Usaha</h3>
                    <p class="text-sm text-gray-500 leading-relaxed">Cocok untuk F&B, Retail, Jasa, Laundry, Salon, hingga Bengkel.</p>
                </div>

                <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm hover:shadow-xl hover:-translate-y-1 transition duration-300">
                    <div class="bg-blue-100 w-12 h-12 rounded-lg flex items-center justify-center text-sancaka-blue mb-4">
                        <i data-lucide="scan-barcode" class="w-6 h-6"></i>
                    </div>
                    <h3 class="font-bold text-lg mb-2">Support Barcode</h3>
                    <p class="text-sm text-gray-500 leading-relaxed">Kompatibel dengan segala jenis Scanner Barcode USB/Bluetooth.</p>
                </div>

                <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm hover:shadow-xl hover:-translate-y-1 transition duration-300">
                    <div class="bg-blue-100 w-12 h-12 rounded-lg flex items-center justify-center text-sancaka-blue mb-4">
                        <i data-lucide="smartphone-charging" class="w-6 h-6"></i>
                    </div>
                    <h3 class="font-bold text-lg mb-2">HP Scanner</h3>
                    <p class="text-sm text-gray-500 leading-relaxed">Gunakan kamera HP sebagai scanner tanpa batas jarak (Via Internet).</p>
                </div>

                <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm hover:shadow-xl hover:-translate-y-1 transition duration-300">
                    <div class="bg-blue-100 w-12 h-12 rounded-lg flex items-center justify-center text-sancaka-blue mb-4">
                        <i data-lucide="file-bar-chart" class="w-6 h-6"></i>
                    </div>
                    <h3 class="font-bold text-lg mb-2">Laporan Otomatis</h3>
                    <p class="text-sm text-gray-500 leading-relaxed">Laba rugi, arus kas, dan stock opname terhitung otomatis realtime.</p>
                </div>

                <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm hover:shadow-xl hover:-translate-y-1 transition duration-300">
                    <div class="bg-blue-100 w-12 h-12 rounded-lg flex items-center justify-center text-sancaka-blue mb-4">
                        <i data-lucide="truck" class="w-6 h-6"></i>
                    </div>
                    <h3 class="font-bold text-lg mb-2">Multi Ekspedisi</h3>
                    <p class="text-sm text-gray-500 leading-relaxed">Input resi JNE, JNT, POS, SiCepat untuk usaha online shop.</p>
                </div>
            </div>
        </div>
    </section>

    <section id="harga" class="py-20 bg-white border-t border-gray-100">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-3xl md:text-4xl font-black text-gray-900 mb-4">Pilih Paket Sesuai Kebutuhan</h2>
                <p class="text-gray-600">Harga transparan, tanpa biaya tersembunyi.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 items-end">
                <div class="bg-white border border-gray-200 rounded-2xl p-8 hover:border-blue-300 transition">
                    <h3 class="text-xl font-bold text-gray-500">Starter</h3>
                    <div class="my-4">
                        <span class="text-4xl font-black text-gray-800">Rp 0</span>
                        <span class="text-gray-500">/ 14 hari</span>
                    </div>
                    <p class="text-sm text-gray-500 mb-6">Untuk mencoba fitur sebelum berlangganan.</p>
                    <button @click="openOrder('trial')" class="w-full py-3 border-2 border-sancaka-blue text-sancaka-blue font-bold rounded-xl hover:bg-blue-50 transition">
                        Coba Gratis
                    </button>
                    <ul class="mt-6 space-y-3 text-sm text-gray-600">
                        <li class="flex items-center gap-2"><i data-lucide="check" class="w-4 h-4 text-green-500"></i> 1 User Admin</li>
                        <li class="flex items-center gap-2"><i data-lucide="check" class="w-4 h-4 text-green-500"></i> Max 50 Transaksi</li>
                        <li class="flex items-center gap-2"><i data-lucide="check" class="w-4 h-4 text-green-500"></i> Laporan Standar</li>
                        <li class="flex items-center gap-2"><i data-lucide="check" class="w-4 h-4 text-green-500"></i> Support WA</li>
                    </ul>
                </div>

                <div class="bg-white border-2 border-sancaka-blue rounded-2xl p-8 shadow-2xl relative transform scale-105 z-10">
                    <div class="absolute top-0 right-0 bg-sancaka-blue text-white text-[10px] font-bold px-3 py-1 rounded-bl-xl rounded-tr-lg uppercase tracking-wide">
                        Paling Laris
                    </div>
                    <h3 class="text-xl font-bold text-sancaka-blue">Business</h3>
                    <div class="my-4">
                        <span class="text-4xl font-black text-gray-800">Rp 100rb</span>
                        <span class="text-gray-500">/ bulan</span>
                    </div>
                    <p class="text-sm text-gray-500 mb-6">Cocok untuk UMKM yang sedang berkembang.</p>
                    <button @click="openOrder('monthly')" class="w-full py-3 bg-sancaka-blue text-white font-bold rounded-xl hover:bg-blue-800 transition shadow-lg">
                        Pilih Paket Ini
                    </button>
                    <ul class="mt-6 space-y-3 text-sm text-gray-600">
                        <li class="flex items-center gap-2"><i data-lucide="check-circle" class="w-4 h-4 text-sancaka-blue"></i> <strong>Unlimited</strong> User & Transaksi</li>
                        <li class="flex items-center gap-2"><i data-lucide="check-circle" class="w-4 h-4 text-sancaka-blue"></i> Kelola Stok & Inventori</li>
                        <li class="flex items-center gap-2"><i data-lucide="check-circle" class="w-4 h-4 text-sancaka-blue"></i> Laporan Keuangan Lengkap</li>
                        <li class="flex items-center gap-2"><i data-lucide="check-circle" class="w-4 h-4 text-sancaka-blue"></i> Aplikasi Android & Windows</li>
                        <li class="flex items-center gap-2"><i data-lucide="check-circle" class="w-4 h-4 text-sancaka-blue"></i> Backup Data Cloud Harian</li>
                    </ul>
                </div>

                <div class="bg-white border border-gray-200 rounded-2xl p-8 hover:border-blue-300 transition">
                    <h3 class="text-xl font-bold text-sancaka-red">Enterprise</h3>
                    <div class="my-4">
                        <span class="text-4xl font-black text-gray-800">Rp 1jt</span>
                        <span class="text-gray-500">/ tahun</span>
                    </div>
                    <p class="text-sm text-gray-500 mb-6">Hemat 2 bulan pembayaran (Rp 200.000).</p>
                    <button @click="openOrder('yearly')" class="w-full py-3 bg-sancaka-red text-white font-bold rounded-xl hover:bg-red-700 transition">
                        Pilih Tahunan
                    </button>
                    <ul class="mt-6 space-y-3 text-sm text-gray-600">
                        <li class="flex items-center gap-2"><i data-lucide="check" class="w-4 h-4 text-green-500"></i> Semua Fitur Bulanan</li>
                        <li class="flex items-center gap-2"><i data-lucide="check" class="w-4 h-4 text-green-500"></i> <strong>Prioritas</strong> Support 24/7</li>
                        <li class="flex items-center gap-2"><i data-lucide="check" class="w-4 h-4 text-green-500"></i> Training Zoom Gratis</li>
                        <li class="flex items-center gap-2"><i data-lucide="check" class="w-4 h-4 text-green-500"></i> Custom Domain (Add-on)</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <section id="faq" class="py-20 bg-gray-50">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-3xl font-black text-center mb-10 text-gray-900">Sering Ditanyakan (FAQ)</h2>

            <div class="space-y-4" x-data="{ active: null }">
                <div class="bg-white border border-gray-200 rounded-lg overflow-hidden">
                    <button @click="active === 1 ? active = null : active = 1" class="w-full flex justify-between items-center p-5 text-left font-bold text-gray-700 hover:bg-gray-50">
                        <span>1. Apa itu Sancaka POS?</span>
                        <i :class="active === 1 ? 'rotate-180' : ''" data-lucide="chevron-down" class="transition-transform duration-300"></i>
                    </button>
                    <div x-show="active === 1" x-collapse class="p-5 pt-0 text-sm text-gray-600 leading-relaxed border-t border-gray-100">
                        Sancaka POS adalah sistem kasir berbasis cloud yang membantu mencatat penjualan, stok barang, dan laporan keuangan secara otomatis untuk berbagai jenis usaha.
                    </div>
                </div>

                <div class="bg-white border border-gray-200 rounded-lg overflow-hidden">
                    <button @click="active === 2 ? active = null : active = 2" class="w-full flex justify-between items-center p-5 text-left font-bold text-gray-700 hover:bg-gray-50">
                        <span>2. Apakah data saya aman?</span>
                        <i :class="active === 2 ? 'rotate-180' : ''" data-lucide="chevron-down" class="transition-transform duration-300"></i>
                    </button>
                    <div x-show="active === 2" x-collapse class="p-5 pt-0 text-sm text-gray-600 leading-relaxed border-t border-gray-100">
                        Sangat aman. Kami menggunakan enkripsi SSL tingkat bank dan backup data harian otomatis di server cloud yang terproteksi.
                    </div>
                </div>

                <div class="bg-white border border-gray-200 rounded-lg overflow-hidden">
                    <button @click="active === 3 ? active = null : active = 3" class="w-full flex justify-between items-center p-5 text-left font-bold text-gray-700 hover:bg-gray-50">
                        <span>3. Perangkat apa yang dibutuhkan?</span>
                        <i :class="active === 3 ? 'rotate-180' : ''" data-lucide="chevron-down" class="transition-transform duration-300"></i>
                    </button>
                    <div x-show="active === 3" x-collapse class="p-5 pt-0 text-sm text-gray-600 leading-relaxed border-t border-gray-100">
                        Anda bisa menggunakan Laptop/PC (Windows), Tablet, atau Smartphone Android. Tidak perlu membeli alat kasir mahal khusus.
                    </div>
                </div>

                <div class="bg-white border border-gray-200 rounded-lg overflow-hidden">
                    <button @click="active === 4 ? active = null : active = 4" class="w-full flex justify-between items-center p-5 text-left font-bold text-gray-700 hover:bg-gray-50">
                        <span>4. Apakah support printer thermal?</span>
                        <i :class="active === 4 ? 'rotate-180' : ''" data-lucide="chevron-down" class="transition-transform duration-300"></i>
                    </button>
                    <div x-show="active === 4" x-collapse class="p-5 pt-0 text-sm text-gray-600 leading-relaxed border-t border-gray-100">
                        Ya, Sancaka POS kompatibel dengan 99% printer thermal Bluetooth (58mm/80mm) dan printer USB yang ada di pasaran.
                    </div>
                </div>

                <div class="bg-white border border-gray-200 rounded-lg overflow-hidden">
                    <button @click="active === 5 ? active = null : active = 5" class="w-full flex justify-between items-center p-5 text-left font-bold text-gray-700 hover:bg-gray-50">
                        <span>5. Bagaimana jika internet mati?</span>
                        <i :class="active === 5 ? 'rotate-180' : ''" data-lucide="chevron-down" class="transition-transform duration-300"></i>
                    </button>
                    <div x-show="active === 5" x-collapse class="p-5 pt-0 text-sm text-gray-600 leading-relaxed border-t border-gray-100">
                        Aplikasi tetap bisa digunakan untuk transaksi (Offline Mode). Data akan tersinkronisasi otomatis ke cloud begitu internet kembali menyala.
                    </div>
                </div>

                 <div class="bg-white border border-gray-200 rounded-lg overflow-hidden">
                    <button @click="active === 6 ? active = null : active = 6" class="w-full flex justify-between items-center p-5 text-left font-bold text-gray-700 hover:bg-gray-50">
                        <span>6. Apakah ada biaya setup awal?</span>
                        <i :class="active === 6 ? 'rotate-180' : ''" data-lucide="chevron-down" class="transition-transform duration-300"></i>
                    </button>
                    <div x-show="active === 6" x-collapse class="p-5 pt-0 text-sm text-gray-600 leading-relaxed border-t border-gray-100">
                        Tidak ada. Anda hanya membayar biaya langganan. Setup akun gratis dan bisa dilakukan sendiri dalam 5 menit.
                    </div>
                </div>

                <div class="bg-white border border-gray-200 rounded-lg overflow-hidden">
                    <button @click="active === 7 ? active = null : active = 7" class="w-full flex justify-between items-center p-5 text-left font-bold text-gray-700 hover:bg-gray-50">
                        <span>7. Apakah bisa ganti paket di tengah jalan?</span>
                        <i :class="active === 7 ? 'rotate-180' : ''" data-lucide="chevron-down" class="transition-transform duration-300"></i>
                    </button>
                    <div x-show="active === 7" x-collapse class="p-5 pt-0 text-sm text-gray-600 leading-relaxed border-t border-gray-100">
                        Bisa. Anda dapat upgrade dari bulanan ke tahunan kapan saja melalui dashboard admin.
                    </div>
                </div>

                <div class="bg-white border border-gray-200 rounded-lg overflow-hidden">
                    <button @click="active === 8 ? active = null : active = 8" class="w-full flex justify-between items-center p-5 text-left font-bold text-gray-700 hover:bg-gray-50">
                        <span>8. Apakah ada panduan penggunaannya?</span>
                        <i :class="active === 8 ? 'rotate-180' : ''" data-lucide="chevron-down" class="transition-transform duration-300"></i>
                    </button>
                    <div x-show="active === 8" x-collapse class="p-5 pt-0 text-sm text-gray-600 leading-relaxed border-t border-gray-100">
                        Tentu. Kami menyediakan Video Tutorial lengkap dan Tim Support via WhatsApp yang siap membantu Anda.
                    </div>
                </div>

                <div class="bg-white border border-gray-200 rounded-lg overflow-hidden">
                    <button @click="active === 9 ? active = null : active = 9" class="w-full flex justify-between items-center p-5 text-left font-bold text-gray-700 hover:bg-gray-50">
                        <span>9. Bisakah menggunakan nama domain sendiri?</span>
                        <i :class="active === 9 ? 'rotate-180' : ''" data-lucide="chevron-down" class="transition-transform duration-300"></i>
                    </button>
                    <div x-show="active === 9" x-collapse class="p-5 pt-0 text-sm text-gray-600 leading-relaxed border-t border-gray-100">
                        Secara default Anda mendapatkan subdomain (nama.tokosancaka.com). Untuk custom domain (nama.com), silakan hubungi CS kami.
                    </div>
                </div>

                <div class="bg-white border border-gray-200 rounded-lg overflow-hidden">
                    <button @click="active === 10 ? active = null : active = 10" class="w-full flex justify-between items-center p-5 text-left font-bold text-gray-700 hover:bg-gray-50">
                        <span>10. Bagaimana cara pembayarannya?</span>
                        <i :class="active === 10 ? 'rotate-180' : ''" data-lucide="chevron-down" class="transition-transform duration-300"></i>
                    </button>
                    <div x-show="active === 10" x-collapse class="p-5 pt-0 text-sm text-gray-600 leading-relaxed border-t border-gray-100">
                        Pembayaran via Transfer Bank (BCA, Mandiri, BRI) atau E-Wallet (OVO, GoPay, Dana) yang dikonfirmasi otomatis.
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="py-10 bg-white" id="kontak">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-3xl font-black text-center mb-8 text-gray-900">Lokasi Kami</h2>
            <div class="w-full h-96 rounded-2xl overflow-hidden shadow-lg border border-gray-200 relative">
                 <iframe
                    src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3955.586182173163!2d111.4552431!3d-7.5109506!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2e79ec6370605963%3A0x62804369527f7082!2sNgawi%2C%20Jawa%20Timur!5e0!3m2!1sid!2sid!4v1700000000000!5m2!1sid!2sid"
                    width="100%"
                    height="100%"
                    style="border:0;"
                    allowfullscreen=""
                    loading="lazy"
                    referrerpolicy="no-referrer-when-downgrade">
                </iframe>
                <div class="absolute bottom-4 left-4 bg-white p-4 rounded-lg shadow-lg text-xs max-w-xs">
                    <p class="font-bold">CV. Sancaka Karya Hutama</p>
                    <p class="text-gray-600">Jawa Timur, Indonesia</p>
                </div>
            </div>
        </div>
    </section>

    <footer class="bg-sancaka-blue text-white pt-16 pb-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8 mb-12">
                <div class="col-span-1 md:col-span-2">
                    <div class="flex items-center gap-3 mb-4">
                        <img src="https://tokosancaka.com/storage/uploads/logo.jpeg" alt="Logo" class="h-10 w-10 rounded-lg border border-blue-700">
                        <h2 class="text-2xl font-bold">SANCAKA POS</h2>
                    </div>
                    <p class="text-blue-200 text-sm leading-relaxed max-w-sm">
                        Partner teknologi terbaik untuk pertumbuhan bisnis Anda. Kelola usaha jadi mudah, untung makin bertambah.
                    </p>
                </div>
                <div>
                    <h3 class="font-bold text-lg mb-4">Navigasi</h3>
                    <ul class="space-y-2 text-sm text-blue-200">
                        <li><a href="#fitur" class="hover:text-white transition">Fitur Unggulan</a></li>
                        <li><a href="#harga" class="hover:text-white transition">Daftar Harga</a></li>
                        <li><a href="#faq" class="hover:text-white transition">FAQ</a></li>
                        <li><a href="#" class="hover:text-white transition">Syarat & Ketentuan</a></li>
                    </ul>
                </div>
                <div>
                    <h3 class="font-bold text-lg mb-4">Hubungi Kami</h3>
                    <ul class="space-y-2 text-sm text-blue-200">
                        <li class="flex items-center gap-2"><i data-lucide="phone" class="w-4 h-4"></i> 0857-4580-8809</li>
                        <li class="flex items-center gap-2"><i data-lucide="mail" class="w-4 h-4"></i> info@tokosancaka.com</li>
                        <li class="flex items-center gap-2"><i data-lucide="map-pin" class="w-4 h-4"></i> Jawa Timur, Indonesia</li>
                    </ul>
                </div>
            </div>
            <div class="border-t border-blue-800 pt-8 flex flex-col md:flex-row justify-between items-center text-xs text-blue-300">
                <p>&copy; 2024 CV. Sancaka Karya Hutama. All rights reserved.</p>
                <div class="flex gap-4 mt-4 md:mt-0">
                    <a href="#" class="hover:text-white">Privacy Policy</a>
                    <a href="#" class="hover:text-white">Terms of Service</a>
                </div>
            </div>
        </div>
    </footer>

    <div x-show="showModal" style="display: none;"
         class="fixed inset-0 z-[999] flex items-center justify-center p-4"
         role="dialog" aria-modal="true">

        <div x-show="showModal"
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             @click="showModal = false"
             class="fixed inset-0 bg-black/60 backdrop-blur-sm"></div>

        <div x-show="showModal"
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             class="bg-white rounded-2xl shadow-2xl w-full max-w-4xl max-h-[90vh] overflow-y-auto relative z-10 flex flex-col md:flex-row">

            <button @click="showModal = false" class="absolute top-4 right-4 z-20 bg-gray-100 p-2 rounded-full hover:bg-gray-200 text-gray-500">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>

             <div class="w-full p-8"
                  x-data="{
                    businessName: '',
                    subdomain: '',
                    get price() {
                        if(selectedPlan === 'trial') return 'Gratis';
                        if(selectedPlan === 'monthly') return 'Rp 100.000';
                        if(selectedPlan === 'yearly') return 'Rp 1.000.000';
                        return '0';
                    },
                    generateSubdomain() {
                        let text = this.businessName.toLowerCase();
                        text = text.replace(/[^a-z0-9\s]/gi, '').replace(/[_\s]/g, '-');
                        this.subdomain = text;
                    }
                 }">

                <h3 class="text-2xl font-bold text-gray-800 mb-6">Formulir Pendaftaran</h3>

                <form action="{{ route('daftar.pos.store') }}" method="POST"
      x-data="{
          password: '',
          confirmPassword: '',
          get reqLength() { return this.password.length >= 8; },
          get reqNumber() { return /[0-9]/.test(this.password); },
          get reqLower() { return /[a-z]/.test(this.password); },
          get reqUpper() { return /[A-Z]/.test(this.password); },
          get reqSymbol() { return /[^A-Za-z0-9]/.test(this.password); },
          get isFormValid() {
              return this.reqLength && this.reqNumber && this.reqLower && this.reqUpper && this.reqSymbol && (this.password === this.confirmPassword) && this.password.length > 0;
          }
      }">
                    @csrf
                    <input type="hidden" name="package" :value="selectedPlan">

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-6">
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-1">Nama Lengkap</label>
                            <input type="text" name="owner_name" required class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-1">Email Bisnis</label>
                            <input type="email" name="email" required class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none">
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-1">Nomor WhatsApp</label>
                            <input type="tel" id="wa_modal" name="whatsapp" required oninput="cleanWA(this)" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none" placeholder="08xxxxxxxx">
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-1">Nama Usaha</label>
                            <input type="text" x-model="businessName" @input="generateSubdomain()" name="business_name" required class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none">
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-1">Subdomain</label>
                            <div class="flex items-center">
                                <input type="text" x-model="subdomain" name="subdomain" required minlength="3" oninput="cleanSubdomain(this)" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-l-xl focus:ring-2 focus:ring-blue-500 outline-none">
                                <span class="bg-gray-200 px-4 py-2.5 rounded-r-xl text-gray-600 text-sm font-bold">.tokosancaka.com</span>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-6">
                        <div x-data="{ show: false }">
                            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-1">Password</label>
                            <div class="relative flex items-center">
                                <input x-model="password" :type="show ? 'text' : 'password'" id="pass_m" name="password" required class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none pr-10">
                                <button type="button" @click="show = !show" class="absolute right-3 text-gray-400 hover:text-gray-600 focus:outline-none">
                                    <i data-lucide="eye" x-show="!show" class="w-5 h-5"></i>
                                    <i data-lucide="eye-off" x-show="show" class="w-5 h-5" style="display: none;"></i>
                                </button>
                            </div>

                            <ul class="mt-3 space-y-1.5 text-xs font-semibold">
                                <li :class="reqLength ? 'text-green-500' : 'text-red-500'" class="flex items-center gap-2 transition-colors duration-300">
                                    <i data-lucide="check-circle" x-show="reqLength" class="w-4 h-4"></i>
                                    <i data-lucide="x-circle" x-show="!reqLength" class="w-4 h-4"></i>
                                    Minimal 8 Karakter
                                </li>
                                <li :class="reqNumber ? 'text-green-500' : 'text-red-500'" class="flex items-center gap-2 transition-colors duration-300">
                                    <i data-lucide="check-circle" x-show="reqNumber" class="w-4 h-4"></i>
                                    <i data-lucide="x-circle" x-show="!reqNumber" class="w-4 h-4"></i>
                                    Terdapat Angka
                                </li>
                                <li :class="reqLower ? 'text-green-500' : 'text-red-500'" class="flex items-center gap-2 transition-colors duration-300">
                                    <i data-lucide="check-circle" x-show="reqLower" class="w-4 h-4"></i>
                                    <i data-lucide="x-circle" x-show="!reqLower" class="w-4 h-4"></i>
                                    Terdapat Huruf Kecil
                                </li>
                                <li :class="reqUpper ? 'text-green-500' : 'text-red-500'" class="flex items-center gap-2 transition-colors duration-300">
                                    <i data-lucide="check-circle" x-show="reqUpper" class="w-4 h-4"></i>
                                    <i data-lucide="x-circle" x-show="!reqUpper" class="w-4 h-4"></i>
                                    Terdapat Huruf Besar
                                </li>
                                <li :class="reqSymbol ? 'text-green-500' : 'text-red-500'" class="flex items-center gap-2 transition-colors duration-300">
                                    <i data-lucide="check-circle" x-show="reqSymbol" class="w-4 h-4"></i>
                                    <i data-lucide="x-circle" x-show="!reqSymbol" class="w-4 h-4"></i>
                                    Karakter atau Simbol
                                </li>
                            </ul>
                        </div>

                        <div x-data="{ showConfirm: false }">
                            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-1">Ulangi Password</label>
                            <div class="relative flex items-center">
                                <input x-model="confirmPassword" :type="showConfirm ? 'text' : 'password'" id="pass_c_m" name="password_confirmation" required class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none pr-10">
                                <button type="button" @click="showConfirm = !showConfirm" class="absolute right-3 text-gray-400 hover:text-gray-600 focus:outline-none">
                                    <i data-lucide="eye" x-show="!showConfirm" class="w-5 h-5"></i>
                                    <i data-lucide="eye-off" x-show="showConfirm" class="w-5 h-5" style="display: none;"></i>
                                </button>
                            </div>

                            <p x-show="confirmPassword.length > 0 && password !== confirmPassword" class="mt-2 text-xs text-red-500 font-semibold flex items-center gap-1 transition-opacity">
                                <i data-lucide="alert-circle" class="w-4 h-4"></i> Password tidak cocok!
                            </p>
                            <p x-show="confirmPassword.length > 0 && password === confirmPassword" class="mt-2 text-xs text-green-500 font-semibold flex items-center gap-1 transition-opacity">
                                <i data-lucide="check-circle" class="w-4 h-4"></i> Password cocok
                            </p>
                        </div>
                    </div>

                    <div class="bg-gray-800 rounded-xl p-5 text-white flex justify-between items-center">
                        <div>
                            <p class="text-[10px] text-gray-400 uppercase font-bold">Paket Dipilih</p>
                            <p class="text-xl font-black text-blue-400 uppercase" x-text="selectedPlan"></p>
                            <p class="text-xs text-gray-400" x-text="price"></p>
                        </div>
                        <button type="submit"
                                :disabled="!isFormValid"
                                :class="isFormValid ? 'bg-blue-600 hover:bg-blue-500 cursor-pointer shadow-lg' : 'bg-gray-600 text-gray-400 cursor-not-allowed'"
                                class="px-6 py-3 rounded-xl font-bold text-sm transition-all duration-300">
                            DAFTAR SEKARANG
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="fixed bottom-6 right-6 flex flex-col gap-4 z-40">
        <button onclick="window.scrollTo({top: 0, behavior: 'smooth'})" class="bg-gray-800 text-white p-3 rounded-full shadow-lg hover:bg-gray-700 transition transform hover:scale-110">
            <i data-lucide="arrow-up" class="w-6 h-6"></i>
        </button>
        <a href="https://wa.me/6285745808809" target="_blank" class="bg-green-500 text-white p-3 rounded-full shadow-lg hover:bg-green-600 transition transform hover:scale-110 animate-bounce">
            <svg class="w-6 h-6 fill-current" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.008-.57-.008-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/>
            </svg>
        </a>
    </div>

    <script>
        lucide.createIcons();

        // Fungsi helper untuk form (sama seperti kode sebelumnya)
        function cleanSubdomain(el) {
            let val = el.value.toLowerCase().replace(/[^a-z]/g, '');
            el.value = val;
        }
        function cleanWA(el) {
            let val = el.value.replace(/[^0-9]/g, '');
            if (val.startsWith('62')) val = '0' + val.substring(2);
            if (val.startsWith('8')) val = '0' + val;
            el.value = val;
        }
    </script>
</body>
</html>
