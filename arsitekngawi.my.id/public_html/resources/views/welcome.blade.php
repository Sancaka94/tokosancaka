<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Arsitek Ngawi - Jasa Konstruksi & Renovasi Terpercaya</title>

    <link rel="icon" href="https://tokosancaka.com/storage/uploads/logo.jpeg" type="image/jpeg">

    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://unpkg.com/lucide@latest"></script>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;900&display=swap" rel="stylesheet">

    <style>
        body { font-family: 'Inter', sans-serif; }
        [x-cloak] { display: none !important; }

        /* Utilitas Warna Konstruksi */
        /* Dominan Hitam (Asphalt/Dark) */
        .bg-constr-black { background-color: #171717; } /* Neutral 900 */
        .bg-constr-dark { background-color: #262626; } /* Neutral 800 */
        
        /* Aksen Kuning (Safety Yellow) */
        .bg-constr-yellow { background-color: #facc15; } /* Yellow 400 */
        .hover-constr-yellow:hover { background-color: #eab308; } /* Yellow 500 */
        
        .text-constr-black { color: #171717; }
        .text-constr-yellow { color: #facc15; }
        
        .border-constr-black { border-color: #171717; }
        .border-constr-yellow { border-color: #facc15; }

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
          selectedPlan: 'konsultasi',
          openOrder(plan) {
              this.selectedPlan = plan;
              this.showModal = true;
          }
      }">

    <nav class="fixed top-0 w-full bg-white border-b-4 border-yellow-400 z-50 shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-20 items-center">
                <div class="flex items-center gap-3">
                    <img src="https://tokosancaka.com/storage/uploads/logo.jpeg" alt="Logo Sancaka" class="h-10 w-10 rounded-lg object-cover border border-gray-200">
                    <div>
                        <h1 class="text-xl font-black text-gray-900 tracking-tight uppercase">ARSITEK NGAWI</h1>
                        <p class="text-[10px] text-yellow-600 font-bold tracking-widest uppercase">Kontraktor & Desain</p>
                    </div>
                </div>
                <div class="hidden md:flex space-x-8 text-sm font-bold text-gray-700">
                    <a href="#fitur" class="hover:text-yellow-600 transition">Layanan</a>
                    <a href="#harga" class="hover:text-yellow-600 transition">Harga</a>
                    <a href="#faq" class="hover:text-yellow-600 transition">FAQ</a>
                    <a href="#kontak" class="hover:text-yellow-600 transition">Lokasi</a>
                </div>
                <button @click="openOrder('konsultasi')" class="bg-constr-black text-yellow-400 px-5 py-2.5 rounded-none skew-x-[-10deg] font-bold text-sm hover:bg-gray-800 transition shadow-sm border-b-2 border-yellow-500">
                    <span class="block skew-x-[10deg]">Konsultasi Gratis</span>
                </button>
            </div>
        </div>
    </nav>

    <header class="pt-32 pb-20 bg-constr-black text-white overflow-hidden relative">
        <div class="absolute inset-0 opacity-10" style="background-image: repeating-linear-gradient(45deg, #000 0, #000 10px, #333 10px, #333 20px);"></div>
        
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10 flex flex-col md:flex-row items-center">
            <div class="md:w-1/2 mb-10 md:mb-0">
                <span class="inline-block py-1 px-3 bg-yellow-400 text-black text-xs font-black mb-4 uppercase tracking-wider">
                    <i data-lucide="hard-hat" class="inline w-3 h-3 mr-1"></i> Partner Konstruksi No. 1
                </span>
                <h2 class="text-4xl md:text-5xl font-black leading-tight mb-6">
                    Bangun Hunian Impian <span class="text-yellow-400 underline decoration-4 decoration-white">Tanpa Pusing</span>
                </h2>
                <p class="text-lg text-gray-300 mb-8 leading-relaxed max-w-lg">
                    Jasa rancang bangun, renovasi rumah, hingga konstruksi gudang dengan material SNI dan tukang berpengalaman. Tepat waktu, transparan, dan bergaransi.
                </p>
                <div class="flex gap-4">
                    <button @click="openOrder('project')" class="bg-constr-yellow text-black px-8 py-3.5 rounded-none font-bold text-base hover:bg-yellow-300 transition shadow-lg flex items-center gap-2">
                        Mulai Proyek <i data-lucide="hammer" class="w-5 h-5"></i>
                    </button>
                    <a href="#fitur" class="border-2 border-white text-white px-8 py-3.5 rounded-none font-bold text-base hover:bg-white hover:text-black transition shadow-lg">
                        Lihat Portofolio
                    </a>
                </div>
            </div>
            <div class="md:w-1/2 flex justify-center relative">
                <div class="absolute inset-0 bg-yellow-500 rounded-full blur-3xl opacity-20 transform translate-x-10 translate-y-10"></div>
                <img src="https://images.unsplash.com/photo-1541888946425-d81bb19240f5?q=80&w=800&auto=format&fit=crop"
                     alt="Proyek Konstruksi"
                     class="relative rounded-sm shadow-2xl border-b-8 border-r-8 border-yellow-400 transform rotate-2 hover:rotate-0 transition duration-500 object-cover h-80 w-full">
            </div>
        </div>
        <svg class="absolute bottom-0 w-full text-gray-50 h-16 md:h-24" preserveAspectRatio="none" viewBox="0 0 1440 320">
            <path fill="currentColor" fill-opacity="1" d="M0,288L48,272C96,256,192,224,288,197.3C384,171,480,149,576,165.3C672,181,768,235,864,250.7C960,267,1056,245,1152,224C1248,203,1344,181,1392,170.7L1440,160L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path>
        </svg>
    </header>

    <section id="fitur" class="py-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-3xl md:text-4xl font-black text-gray-900 mb-4">Mengapa Memilih Kami?</h2>
                <p class="text-gray-600 max-w-2xl mx-auto">Standar kualitas tinggi untuk setiap jengkal bangunan Anda.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <div class="bg-white p-6 border-l-4 border-yellow-400 shadow-sm hover:shadow-xl hover:-translate-y-1 transition duration-300">
                    <div class="bg-gray-900 w-12 h-12 flex items-center justify-center text-yellow-400 mb-4">
                        <i data-lucide="ruler" class="w-6 h-6"></i>
                    </div>
                    <h3 class="font-bold text-lg mb-2">Desain Presisi</h3>
                    <p class="text-sm text-gray-500 leading-relaxed">Gambar kerja detail 2D & 3D agar hasil bangun sesuai ekspektasi.</p>
                </div>

                <div class="bg-white p-6 border-l-4 border-yellow-400 shadow-sm hover:shadow-xl hover:-translate-y-1 transition duration-300">
                    <div class="bg-gray-900 w-12 h-12 flex items-center justify-center text-yellow-400 mb-4">
                        <i data-lucide="brick-wall" class="w-6 h-6"></i>
                    </div>
                    <h3 class="font-bold text-lg mb-2">Material SNI</h3>
                    <p class="text-sm text-gray-500 leading-relaxed">Hanya menggunakan bahan bangunan berkualitas standar nasional.</p>
                </div>

                <div class="bg-white p-6 border-l-4 border-yellow-400 shadow-sm hover:shadow-xl hover:-translate-y-1 transition duration-300">
                    <div class="bg-gray-900 w-12 h-12 flex items-center justify-center text-yellow-400 mb-4">
                        <i data-lucide="clock" class="w-6 h-6"></i>
                    </div>
                    <h3 class="font-bold text-lg mb-2">Tepat Waktu</h3>
                    <p class="text-sm text-gray-500 leading-relaxed">Timeline kerja terukur dengan penalti jika kami terlambat.</p>
                </div>

                <div class="bg-white p-6 border-l-4 border-yellow-400 shadow-sm hover:shadow-xl hover:-translate-y-1 transition duration-300">
                    <div class="bg-gray-900 w-12 h-12 flex items-center justify-center text-yellow-400 mb-4">
                        <i data-lucide="home" class="w-6 h-6"></i>
                    </div>
                    <h3 class="font-bold text-lg mb-2">All-in-One</h3>
                    <p class="text-sm text-gray-500 leading-relaxed">Mulai dari tanah kosong, renovasi atap, hingga serah terima kunci.</p>
                </div>

                <div class="bg-white p-6 border-l-4 border-yellow-400 shadow-sm hover:shadow-xl hover:-translate-y-1 transition duration-300">
                    <div class="bg-gray-900 w-12 h-12 flex items-center justify-center text-yellow-400 mb-4">
                        <i data-lucide="shield-check" class="w-6 h-6"></i>
                    </div>
                    <h3 class="font-bold text-lg mb-2">Garansi Struktur</h3>
                    <p class="text-sm text-gray-500 leading-relaxed">Jaminan retensi pemeliharaan selama 3-6 bulan setelah proyek selesai.</p>
                </div>

                <div class="bg-white p-6 border-l-4 border-yellow-400 shadow-sm hover:shadow-xl hover:-translate-y-1 transition duration-300">
                    <div class="bg-gray-900 w-12 h-12 flex items-center justify-center text-yellow-400 mb-4">
                        <i data-lucide="file-text" class="w-6 h-6"></i>
                    </div>
                    <h3 class="font-bold text-lg mb-2">RAB Transparan</h3>
                    <p class="text-sm text-gray-500 leading-relaxed">Rincian Anggaran Biaya detail, tanpa mark-up harga tersembunyi.</p>
                </div>

                <div class="bg-white p-6 border-l-4 border-yellow-400 shadow-sm hover:shadow-xl hover:-translate-y-1 transition duration-300">
                    <div class="bg-gray-900 w-12 h-12 flex items-center justify-center text-yellow-400 mb-4">
                        <i data-lucide="users" class="w-6 h-6"></i>
                    </div>
                    <h3 class="font-bold text-lg mb-2">Tukang Ahli</h3>
                    <p class="text-sm text-gray-500 leading-relaxed">Tenaga kerja berpengalaman, bukan tukang cabutan sembarangan.</p>
                </div>

                <div class="bg-white p-6 border-l-4 border-yellow-400 shadow-sm hover:shadow-xl hover:-translate-y-1 transition duration-300">
                    <div class="bg-gray-900 w-12 h-12 flex items-center justify-center text-yellow-400 mb-4">
                        <i data-lucide="truck" class="w-6 h-6"></i>
                    </div>
                    <h3 class="font-bold text-lg mb-2">Armada Sendiri</h3>
                    <p class="text-sm text-gray-500 leading-relaxed">Memiliki kendaraan operasional untuk mobilisasi material cepat.</p>
                </div>
            </div>
        </div>
    </section>

    <section id="harga" class="py-20 bg-gray-100 border-t border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-3xl md:text-4xl font-black text-gray-900 mb-4">Paket Jasa Konstruksi</h2>
                <p class="text-gray-600">Harga fleksibel menyesuaikan budget dan spesifikasi Anda.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 items-end">
                <div class="bg-white border-2 border-gray-200 rounded-none p-8 hover:border-yellow-400 transition">
                    <h3 class="text-xl font-bold text-gray-500">Konsultasi</h3>
                    <div class="my-4">
                        <span class="text-4xl font-black text-gray-900">GRATIS</span>
                    </div>
                    <p class="text-sm text-gray-500 mb-6">Diskusi awal perencanaan bangunan.</p>
                    <button @click="openOrder('konsultasi')" class="w-full py-3 border-2 border-gray-900 text-gray-900 font-bold hover:bg-gray-900 hover:text-white transition">
                        Hubungi Kami
                    </button>
                    <ul class="mt-6 space-y-3 text-sm text-gray-600">
                        <li class="flex items-center gap-2"><i data-lucide="check" class="w-4 h-4 text-yellow-500"></i> Survey Lokasi</li>
                        <li class="flex items-center gap-2"><i data-lucide="check" class="w-4 h-4 text-yellow-500"></i> Estimasi Biaya Kasar</li>
                        <li class="flex items-center gap-2"><i data-lucide="check" class="w-4 h-4 text-yellow-500"></i> Konsultasi Material</li>
                    </ul>
                </div>

                <div class="bg-white border-4 border-yellow-400 rounded-none p-8 shadow-2xl relative transform scale-105 z-10">
                    <div class="absolute top-0 right-0 bg-yellow-400 text-black text-[10px] font-bold px-3 py-1 uppercase tracking-wide">
                        Paling Dicari
                    </div>
                    <h3 class="text-xl font-bold text-black">Borongan Standar</h3>
                    <div class="my-4">
                        <span class="text-2xl font-black text-gray-800">Mulai </span>
                        <span class="text-3xl font-black text-gray-900">3,5 Jt</span>
                        <span class="text-gray-500">/ m²</span>
                    </div>
                    <p class="text-sm text-gray-500 mb-6">Rumah tinggal standar minimalis.</p>
                    <button @click="openOrder('borongan')" class="w-full py-3 bg-constr-black text-yellow-400 font-bold hover:bg-gray-800 transition shadow-lg">
                        Pilih Paket Ini
                    </button>
                    <ul class="mt-6 space-y-3 text-sm text-gray-600">
                        <li class="flex items-center gap-2"><i data-lucide="check-circle" class="w-4 h-4 text-black"></i> <strong>Terima Kunci</strong> (All-in)</li>
                        <li class="flex items-center gap-2"><i data-lucide="check-circle" class="w-4 h-4 text-black"></i> Pondasi Batu Kali</li>
                        <li class="flex items-center gap-2"><i data-lucide="check-circle" class="w-4 h-4 text-black"></i> Dinding Bata Ringan</li>
                        <li class="flex items-center gap-2"><i data-lucide="check-circle" class="w-4 h-4 text-black"></i> Atap Baja Ringan</li>
                        <li class="flex items-center gap-2"><i data-lucide="check-circle" class="w-4 h-4 text-black"></i> Lantai Granit 60x60</li>
                    </ul>
                </div>

                <div class="bg-white border-2 border-gray-200 rounded-none p-8 hover:border-yellow-400 transition">
                    <h3 class="text-xl font-bold text-gray-800">Premium / Mewah</h3>
                    <div class="my-4">
                        <span class="text-2xl font-black text-gray-800">Mulai </span>
                        <span class="text-3xl font-black text-gray-900">5 Jt</span>
                        <span class="text-gray-500">/ m²</span>
                    </div>
                    <p class="text-sm text-gray-500 mb-6">Untuk hunian mewah 2 lantai ke atas.</p>
                    <button @click="openOrder('premium')" class="w-full py-3 bg-yellow-400 text-black font-bold hover:bg-yellow-500 transition">
                        Pilih Premium
                    </button>
                    <ul class="mt-6 space-y-3 text-sm text-gray-600">
                        <li class="flex items-center gap-2"><i data-lucide="check" class="w-4 h-4 text-yellow-500"></i> Desain Arsitek Custom</li>
                        <li class="flex items-center gap-2"><i data-lucide="check" class="w-4 h-4 text-yellow-500"></i> Pondasi Cakar Ayam</li>
                        <li class="flex items-center gap-2"><i data-lucide="check" class="w-4 h-4 text-yellow-500"></i> Cat Premium (Jotun/Dulux)</li>
                        <li class="flex items-center gap-2"><i data-lucide="check" class="w-4 h-4 text-yellow-500"></i> Sanitary Toto/Setara</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <section id="faq" class="py-20 bg-gray-50">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-3xl font-black text-center mb-10 text-gray-900">Sering Ditanyakan (FAQ)</h2>

            <div class="space-y-4" x-data="{ active: null }">
                <div class="bg-white border-l-4 border-black rounded-sm shadow-sm">
                    <button @click="active === 1 ? active = null : active = 1" class="w-full flex justify-between items-center p-5 text-left font-bold text-gray-800 hover:bg-gray-50">
                        <span>1. Apakah melayani renovasi kecil?</span>
                        <i :class="active === 1 ? 'rotate-180' : ''" data-lucide="chevron-down" class="transition-transform duration-300"></i>
                    </button>
                    <div x-show="active === 1" x-collapse class="p-5 pt-0 text-sm text-gray-600 leading-relaxed border-t border-gray-100">
                        Tentu. Kami melayani segala jenis pekerjaan konstruksi, mulai dari perbaikan atap bocor, pengecatan ulang, penambahan ruangan, hingga bangun baru dari nol.
                    </div>
                </div>

                <div class="bg-white border-l-4 border-black rounded-sm shadow-sm">
                    <button @click="active === 2 ? active = null : active = 2" class="w-full flex justify-between items-center p-5 text-left font-bold text-gray-800 hover:bg-gray-50">
                        <span>2. Apakah survey lokasi dikenakan biaya?</span>
                        <i :class="active === 2 ? 'rotate-180' : ''" data-lucide="chevron-down" class="transition-transform duration-300"></i>
                    </button>
                    <div x-show="active === 2" x-collapse class="p-5 pt-0 text-sm text-gray-600 leading-relaxed border-t border-gray-100">
                        Untuk wilayah Ngawi dan sekitarnya, survey lokasi dan konsultasi awal **GRATIS** tanpa dipungut biaya apapun.
                    </div>
                </div>

                <div class="bg-white border-l-4 border-black rounded-sm shadow-sm">
                    <button @click="active === 3 ? active = null : active = 3" class="w-full flex justify-between items-center p-5 text-left font-bold text-gray-800 hover:bg-gray-50">
                        <span>3. Bagaimana sistem pembayarannya?</span>
                        <i :class="active === 3 ? 'rotate-180' : ''" data-lucide="chevron-down" class="transition-transform duration-300"></i>
                    </button>
                    <div x-show="active === 3" x-collapse class="p-5 pt-0 text-sm text-gray-600 leading-relaxed border-t border-gray-100">
                        Pembayaran dilakukan bertahap (termin) sesuai progress di lapangan. DP masuk setelah SPK ditandatangani, sisanya mengikuti progres fisik bangunan (misal: 30%, 50%, 80%, 100%).
                    </div>
                </div>

                <div class="bg-white border-l-4 border-black rounded-sm shadow-sm">
                    <button @click="active === 4 ? active = null : active = 4" class="w-full flex justify-between items-center p-5 text-left font-bold text-gray-800 hover:bg-gray-50">
                        <span>4. Berapa lama garansi bangunan?</span>
                        <i :class="active === 4 ? 'rotate-180' : ''" data-lucide="chevron-down" class="transition-transform duration-300"></i>
                    </button>
                    <div x-show="active === 4" x-collapse class="p-5 pt-0 text-sm text-gray-600 leading-relaxed border-t border-gray-100">
                        Kami memberikan masa retensi (garansi pemeliharaan) selama 3 bulan untuk perbaikan minor (retak rambut, bocor, dll) setelah serah terima kunci.
                    </div>
                </div>

                <div class="bg-white border-l-4 border-black rounded-sm shadow-sm">
                    <button @click="active === 5 ? active = null : active = 5" class="w-full flex justify-between items-center p-5 text-left font-bold text-gray-800 hover:bg-gray-50">
                        <span>5. Apakah bisa pakai material sendiri?</span>
                        <i :class="active === 5 ? 'rotate-180' : ''" data-lucide="chevron-down" class="transition-transform duration-300"></i>
                    </button>
                    <div x-show="active === 5" x-collapse class="p-5 pt-0 text-sm text-gray-600 leading-relaxed border-t border-gray-100">
                        Bisa. Kami melayani jasa tenaga saja (Borongan Tenaga). Namun, kami menyarankan sistem All-in agar kualitas material lebih terkontrol dan efisien waktu.
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="py-10 bg-white" id="kontak">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-3xl font-black text-center mb-8 text-gray-900">Lokasi Proyek & Kantor</h2>
            <div class="w-full h-96 rounded-none border-4 border-black overflow-hidden shadow-lg relative">
                 <iframe
                    src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3955.586182173163!2d111.4552431!3d-7.5109506!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2e79ec6370605963%3A0x62804369527f7082!2sNgawi%2C%20Jawa%20Timur!5e0!3m2!1sid!2sid!4v1700000000000!5m2!1sid!2sid"
                    width="100%"
                    height="100%"
                    style="border:0;"
                    allowfullscreen=""
                    loading="lazy"
                    referrerpolicy="no-referrer-when-downgrade">
                </iframe>
                <div class="absolute bottom-4 left-4 bg-yellow-400 p-4 shadow-lg text-xs max-w-xs border border-black">
                    <p class="font-bold text-black uppercase">CV. Sancaka Karya Hutama</p>
                    <p class="text-gray-800">Jawa Timur, Indonesia</p>
                </div>
            </div>
        </div>
    </section>

    <footer class="bg-constr-black text-white pt-16 pb-8 border-t-8 border-yellow-400">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8 mb-12">
                <div class="col-span-1 md:col-span-2">
                    <div class="flex items-center gap-3 mb-4">
                        <img src="https://tokosancaka.com/storage/uploads/logo.jpeg" alt="Logo" class="h-10 w-10 rounded-lg border border-yellow-500">
                        <h2 class="text-2xl font-black text-yellow-400 uppercase">ARSITEK NGAWI</h2>
                    </div>
                    <p class="text-gray-400 text-sm leading-relaxed max-w-sm">
                        Mitra konstruksi profesional untuk mewujudkan bangunan yang kokoh, estetis, dan fungsional. Solusi tepat untuk masa depan aset Anda.
                    </p>
                </div>
                <div>
                    <h3 class="font-bold text-lg mb-4 text-yellow-400">Navigasi</h3>
                    <ul class="space-y-2 text-sm text-gray-300">
                        <li><a href="#fitur" class="hover:text-yellow-400 transition">Layanan</a></li>
                        <li><a href="#harga" class="hover:text-yellow-400 transition">Estimasi Harga</a></li>
                        <li><a href="#faq" class="hover:text-yellow-400 transition">FAQ</a></li>
                        <li><a href="#" class="hover:text-yellow-400 transition">Portofolio</a></li>
                    </ul>
                </div>
                <div>
                    <h3 class="font-bold text-lg mb-4 text-yellow-400">Hubungi Kami</h3>
                    <ul class="space-y-2 text-sm text-gray-300">
                        <li class="flex items-center gap-2"><i data-lucide="phone" class="w-4 h-4"></i> 0857-4580-8809</li>
                        <li class="flex items-center gap-2"><i data-lucide="mail" class="w-4 h-4"></i> info@tokosancaka.com</li>
                        <li class="flex items-center gap-2"><i data-lucide="map-pin" class="w-4 h-4"></i> Jawa Timur, Indonesia</li>
                    </ul>
                </div>
            </div>
            <div class="border-t border-gray-800 pt-8 flex flex-col md:flex-row justify-between items-center text-xs text-gray-500">
                <p>© 2024 CV. Sancaka Karya Hutama. All rights reserved.</p>
                <div class="flex gap-4 mt-4 md:mt-0">
                    <a href="#" class="hover:text-yellow-400">Privacy Policy</a>
                    <a href="#" class="hover:text-yellow-400">Terms of Service</a>
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
             class="fixed inset-0 bg-black/80 backdrop-blur-sm"></div>

        <div x-show="showModal"
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             class="bg-white rounded-none border-t-8 border-yellow-400 shadow-2xl w-full max-w-4xl max-h-[90vh] overflow-y-auto relative z-10 flex flex-col md:flex-row">

            <button @click="showModal = false" class="absolute top-4 right-4 z-20 bg-gray-100 p-2 rounded-full hover:bg-red-100 text-gray-500 hover:text-red-600">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>

             <div class="w-full p-8"
                  x-data="{
                    projectName: '',
                    projectType: '',
                    get price() {
                        if(selectedPlan === 'konsultasi') return 'Gratis';
                        if(selectedPlan === 'borongan') return 'Mulai Rp 3,5jt / m2';
                        if(selectedPlan === 'premium') return 'Mulai Rp 5jt / m2';
                        return 'Custom';
                    }
                  }">

                <h3 class="text-2xl font-black text-gray-900 mb-6 uppercase">Formulir Order Proyek</h3>

                <form action="#" method="POST">
                    <input type="hidden" name="package" :value="selectedPlan">

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-6">
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-1">Nama Pemilik</label>
                            <input type="text" name="owner_name" required class="w-full px-4 py-2.5 bg-gray-50 border-2 border-gray-200 focus:border-yellow-400 focus:ring-0 outline-none transition">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-1">Email</label>
                            <input type="email" name="email" required class="w-full px-4 py-2.5 bg-gray-50 border-2 border-gray-200 focus:border-yellow-400 focus:ring-0 outline-none transition">
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-1">Nomor WhatsApp</label>
                            <input type="tel" id="wa_modal" name="whatsapp" required oninput="cleanWA(this)" class="w-full px-4 py-2.5 bg-gray-50 border-2 border-gray-200 focus:border-yellow-400 focus:ring-0 outline-none transition" placeholder="08xxxxxxxx">
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-1">Lokasi Proyek (Kota/Kecamatan)</label>
                            <input type="text" x-model="projectName" name="location" required class="w-full px-4 py-2.5 bg-gray-50 border-2 border-gray-200 focus:border-yellow-400 focus:ring-0 outline-none transition">
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-1">Jenis Pekerjaan</label>
                            <select name="type" class="w-full px-4 py-2.5 bg-gray-50 border-2 border-gray-200 focus:border-yellow-400 focus:ring-0 outline-none transition">
                                <option>Bangun Baru</option>
                                <option>Renovasi Rumah</option>
                                <option>Gudang / Pabrik</option>
                                <option>Desain Arsitek (Gambar Saja)</option>
                                <option>Lainnya</option>
                            </select>
                        </div>
                    </div>

                    <div class="bg-black rounded-none p-5 text-white flex justify-between items-center border-l-4 border-yellow-400">
                        <div>
                            <p class="text-[10px] text-gray-400 uppercase font-bold">Paket Dipilih</p>
                            <p class="text-xl font-black text-yellow-400 uppercase" x-text="selectedPlan"></p>
                            <p class="text-xs text-gray-300" x-text="price"></p>
                        </div>
                        <button type="submit" class="bg-yellow-400 hover:bg-yellow-300 text-black px-6 py-3 font-bold text-sm shadow-lg transition uppercase">
                            Kirim Permintaan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="fixed bottom-6 right-6 flex flex-col gap-4 z-40">
        <button onclick="window.scrollTo({top: 0, behavior: 'smooth'})" class="bg-black text-yellow-400 border border-yellow-400 p-3 rounded-full shadow-lg hover:bg-gray-800 transition transform hover:scale-110">
            <i data-lucide="arrow-up" class="w-6 h-6"></i>
        </button>
        <a href="https://wa.me/6285745808809" target="_blank" class="bg-green-600 text-white p-3 rounded-full shadow-lg hover:bg-green-500 transition transform hover:scale-110 animate-bounce">
            <svg class="w-6 h-6 fill-current" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.008-.57-.008-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/>
            </svg>
        </a>
    </div>

    <script>
        lucide.createIcons();

        function cleanWA(el) {
            let val = el.value.replace(/[^0-9]/g, '');
            if (val.startsWith('62')) val = '0' + val.substring(2);
            if (val.startsWith('8')) val = '0' + val;
            el.value = val;
        }
    </script>
</body>
</html>