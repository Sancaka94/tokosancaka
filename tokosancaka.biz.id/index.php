<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="google-site-verification" content="l8gbY0TzOrB-5M6uNttXnJLu8gAGGAOverTTodmKW50" />
    <title>Sancaka Express - Jasa Perizinan, Kontruksi & Ekspedisi Ngawi Terlengkap</title>
    
    <meta name="description" content="Solusi Satu Pintu! Jasa Perizinan (PT, CV, PBG, SLF, BPOM), Kontraktor Bangunan, Arsitek, dan Ekspedisi Murah (JNT, JNE, Lion Parcel) di Ngawi. Cepat, Resmi, Terpercaya.">
    <meta name="keywords" content="Sancaka Express, Jasa Perizinan Ngawi, Buat PT CV Ngawi, Jasa Kontraktor Ngawi, Ekspedisi Murah Ngawi, Agen Lion Parcel Ngawi, Jasa Arsitek, Pengurusan IMB SLF, Marketing 6.0, Sancaka Karya Hutama">
    <meta name="author" content="Sancaka Group">
    
    <link rel="icon" href="https://tokosancaka.com/storage/uploads/logo.jpeg" type="image/jpeg">

    <script src="https://cdn.tailwindcss.com"></script>
    
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700;800&display=swap" rel="stylesheet">

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Poppins', 'sans-serif'],
                    },
                    colors: {
                        sancaka: {
                            yellow: '#FFC107', // Kuning Pajak
                            blue: '#0D47A1',   // Biru Pajak
                            red: '#D32F2F',    // Merah
                            green: '#388E3C',  // Hijau
                        }
                    },
                    animation: {
                        'marquee': 'marquee 25s linear infinite',
                        'fade-in-up': 'fadeInUp 1s ease-out forwards',
                    },
                    keyframes: {
                        marquee: {
                            '0%': { transform: 'translateX(0%)' },
                            '100%': { transform: 'translateX(-100%)' },
                        },
                        fadeInUp: {
                            '0%': { opacity: '0', transform: 'translateY(20px)' },
                            '100%': { opacity: '1', transform: 'translateY(0)' },
                        }
                    }
                }
            }
        }
    </script>

    <style>
        /* Custom Scrollbar */
        ::-webkit-scrollbar {
            width: 10px;
        }
        ::-webkit-scrollbar-track {
            background: #f1f1f1; 
        }
        ::-webkit-scrollbar-thumb {
            background: linear-gradient(to bottom, #0D47A1, #388E3C); 
            border-radius: 5px;
        }
        
        /* Hero Slider Styles */
        .slider-container {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            overflow: hidden;
        }
        .slide {
            position: absolute;
            width: 100%;
            height: 100%;
            background-size: cover;
            background-position: center;
            opacity: 0;
            transition: opacity 1s ease-in-out;
        }
        .slide.active {
            opacity: 1;
        }
        .gradient-overlay {
            background: linear-gradient(rgba(13, 71, 161, 0.8), rgba(56, 142, 60, 0.7));
        }
    </style>
</head>
<body class="font-sans text-gray-800 antialiased overflow-x-hidden">

    <header x-data="{ mobileMenu: false, serviceMenu: false }" class="fixed w-full z-50 bg-white shadow-xl transition-all duration-300">
        <div class="container mx-auto px-4 lg:px-8">
            <div class="flex justify-between items-center h-20">
                <a href="#" class="flex items-center gap-3 group">
                    <img src="https://tokosancaka.com/storage/uploads/logo.jpeg" alt="Logo Sancaka" class="h-12 w-12 rounded-full border-2 border-sancaka-blue shadow-md group-hover:rotate-12 transition duration-300">
                    <div class="flex flex-col">
                        <span class="text-xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-sancaka-blue to-sancaka-green tracking-tighter">SANCAKA</span>
                        <span class="text-sm font-bold text-sancaka-red tracking-widest -mt-1">EXPRESS</span>
                    </div>
                </a>

                <nav class="hidden lg:flex items-center gap-8">
                    <a href="#" class="font-semibold text-gray-700 hover:text-sancaka-blue transition">Beranda</a>
                    
                    <div class="relative group" @mouseenter="serviceMenu = true" @mouseleave="serviceMenu = false">
                        <button class="flex items-center gap-1 font-semibold text-gray-700 hover:text-sancaka-blue transition py-6">
                            Layanan Kami <i class="fa-solid fa-chevron-down text-xs transition-transform" :class="{'rotate-180': serviceMenu}"></i>
                        </button>
                        
                        <div x-show="serviceMenu" 
                             x-transition:enter="transition ease-out duration-200"
                             x-transition:enter-start="opacity-0 translate-y-2"
                             x-transition:enter-end="opacity-100 translate-y-0"
                             x-transition:leave="transition ease-in duration-150"
                             x-transition:leave-start="opacity-100 translate-y-0"
                             x-transition:leave-end="opacity-0 translate-y-2"
                             class="absolute top-full left-1/2 transform -translate-x-1/2 w-[800px] bg-white shadow-2xl rounded-xl border-t-4 border-sancaka-yellow p-6 grid grid-cols-3 gap-6 z-50">
                            
                            <div>
                                <h4 class="font-bold text-sancaka-blue mb-3 border-b pb-2"><i class="fa-solid fa-scale-balanced mr-2"></i>Legalitas & Perizinan</h4>
                                <ul class="space-y-2 text-sm text-gray-600">
                                    <li class="hover:text-sancaka-green cursor-pointer">Pendirian PT & CV</li>
                                    <li class="hover:text-sancaka-green cursor-pointer">PBG, SLF & IMB</li>
                                    <li class="hover:text-sancaka-green cursor-pointer">Sertifikat Halal & BPOM</li>
                                    <li class="hover:text-sancaka-green cursor-pointer">Pendaftaran Merk & Paten</li>
                                    <li class="hover:text-sancaka-green cursor-pointer">OSS RBA & NIB</li>
                                </ul>
                            </div>

                            <div>
                                <h4 class="font-bold text-sancaka-green mb-3 border-b pb-2"><i class="fa-solid fa-trowel-bricks mr-2"></i>Kontruksi & Bangunan</h4>
                                <ul class="space-y-2 text-sm text-gray-600">
                                    <li class="hover:text-sancaka-blue cursor-pointer">Jasa Bangun Rumah & Toko</li>
                                    <li class="hover:text-sancaka-blue cursor-pointer">Desain Arsitek (2D & 3D)</li>
                                    <li class="hover:text-sancaka-blue cursor-pointer">Hitung RAB Profesional</li>
                                    <li class="hover:text-sancaka-blue cursor-pointer">Pengeboran Sumur SIPA</li>
                                    <li class="hover:text-sancaka-blue cursor-pointer">Bangun Alfamart/Indomaret</li>
                                </ul>
                            </div>

                            <div>
                                <h4 class="font-bold text-sancaka-red mb-3 border-b pb-2"><i class="fa-solid fa-truck-fast mr-2"></i>Ekspedisi & Digital</h4>
                                <ul class="space-y-2 text-sm text-gray-600">
                                    <li class="hover:text-sancaka-yellow cursor-pointer">Lion Parcel, J&T, JNE</li>
                                    <li class="hover:text-sancaka-yellow cursor-pointer">SAP, ID Express, Indah Cargo</li>
                                    <li class="hover:text-sancaka-yellow cursor-pointer">Pembuatan Website Bisnis</li>
                                    <li class="hover:text-sancaka-yellow cursor-pointer">Agen PPOB & Tiket</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <a href="#harga" class="font-semibold text-gray-700 hover:text-sancaka-blue transition">Biaya</a>
                    <a href="#testimoni" class="font-semibold text-gray-700 hover:text-sancaka-blue transition">Testimoni</a>
                    <a href="#kontak" class="bg-gradient-to-r from-sancaka-blue to-sancaka-green text-white px-6 py-2 rounded-full font-bold shadow-lg hover:shadow-xl hover:scale-105 transition transform">Hubungi Kami</a>
                </nav>

                <button @click="mobileMenu = !mobileMenu" class="lg:hidden text-2xl text-sancaka-blue">
                    <i class="fa-solid fa-bars"></i>
                </button>
            </div>
        </div>

        <div x-show="mobileMenu" class="lg:hidden bg-white border-t border-gray-100 p-4 shadow-lg absolute w-full">
            <a href="#" class="block py-2 font-semibold">Beranda</a>
            <a href="#" class="block py-2 font-semibold">Layanan Legalitas</a>
            <a href="#" class="block py-2 font-semibold">Layanan Kontruksi</a>
            <a href="#" class="block py-2 font-semibold">Ekspedisi</a>
            <a href="#kontak" class="block mt-4 text-center bg-sancaka-blue text-white py-2 rounded-lg font-bold">Hubungi Kami</a>
        </div>
    </header>

    <section class="relative h-screen flex items-center justify-center text-white overflow-hidden pt-20">
        <div class="slider-container" id="hero-slider">
            <div class="slide active" style="background-image: url('https://images.unsplash.com/photo-1541888946425-d81bb19240f5?q=80&w=1920&auto=format&fit=crop');"></div>
            <div class="slide" style="background-image: url('https://images.unsplash.com/photo-1586528116311-ad8dd3c8310d?q=80&w=1920&auto=format&fit=crop');"></div>
            <div class="slide" style="background-image: url('https://images.unsplash.com/photo-1486406146926-c627a92ad1ab?q=80&w=1920&auto=format&fit=crop');"></div>
        </div>
        
        <div class="absolute inset-0 bg-gradient-to-r from-sancaka-blue/90 via-sancaka-green/80 to-sancaka-yellow/30"></div>

        <div class="container mx-auto px-4 relative z-10 text-center lg:text-left flex flex-col lg:flex-row items-center">
            <div class="lg:w-2/3 animate-fade-in-up">
                <span class="bg-sancaka-yellow text-black font-bold px-3 py-1 rounded text-xs uppercase tracking-wider mb-4 inline-block shadow-lg">#1 Terpercaya di Jawa Timur</span>
                <h1 class="text-4xl lg:text-6xl font-extrabold mb-6 leading-tight drop-shadow-lg">
                    Bangun Bisnis & <br> Hunian Impian Anda <br>
                    <span class="text-transparent bg-clip-text bg-gradient-to-r from-yellow-300 to-white">Tanpa Pusing!</span>
                </h1>
                <p class="text-lg mb-8 max-w-2xl font-light text-gray-100">
                    Solusi <strong>All-In-One</strong>: Urus Izin PT/CV, Bangun Ruko/Rumah, hingga Kirim Paket ke Seluruh Indonesia. Kami bereskan legalitas, konstruksi, dan logistik Anda dengan standar profesional <strong>Marketing 6.0</strong>.
                </p>
                <div class="flex flex-col sm:flex-row gap-4 justify-center lg:justify-start">
                    <a href="https://wa.me/6285745808809" class="bg-sancaka-red hover:bg-red-700 text-white font-bold py-4 px-8 rounded-full shadow-lg transition transform hover:scale-105 flex items-center justify-center gap-2">
                        <i class="fa-brands fa-whatsapp text-2xl"></i> Konsultasi Gratis
                    </a>
                    <a href="https://tokosancaka.com/etalase" target="_blank" class="bg-white text-sancaka-blue font-bold py-4 px-8 rounded-full shadow-lg transition transform hover:scale-105 flex items-center justify-center gap-2">
                        <i class="fa-solid fa-shop"></i> Kunjungi Marketplace
                    </a>
                </div>
            </div>
            <div class="hidden lg:block lg:w-1/3 mt-10 lg:mt-0 relative animate-fade-in-up" style="animation-delay: 0.3s;">
                <div class="bg-white/10 backdrop-blur-md border border-white/20 p-6 rounded-2xl shadow-2xl transform rotate-3 hover:rotate-0 transition duration-500">
                    <h3 class="font-bold text-xl mb-4 border-b border-white/30 pb-2">Status Layanan</h3>
                    <div class="flex items-center gap-3 mb-3">
                        <div class="w-3 h-3 bg-green-400 rounded-full animate-pulse"></div>
                        <span>Perizinan: <strong class="text-yellow-300">OPEN</strong></span>
                    </div>
                    <div class="flex items-center gap-3 mb-3">
                        <div class="w-3 h-3 bg-green-400 rounded-full animate-pulse"></div>
                        <span>Kontruksi: <strong class="text-yellow-300">OPEN</strong></span>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="w-3 h-3 bg-green-400 rounded-full animate-pulse"></div>
                        <span>Ekspedisi: <strong class="text-yellow-300">24 JAM</strong></span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="bg-gray-100 py-6 border-b border-gray-200 overflow-hidden">
        <div class="container mx-auto px-4 mb-2 text-center">
            <p class="text-sm font-bold text-gray-500 uppercase tracking-widest mb-4">Didukung Metode Pembayaran Terlengkap</p>
        </div>
        <div class="relative w-full overflow-hidden">
            <div class="flex whitespace-nowrap animate-marquee items-center gap-12">
                <span class="text-3xl font-bold text-gray-400 flex items-center gap-2"><i class="fa-solid fa-qrcode text-blue-600"></i> QRIS</span>
                <span class="text-3xl font-bold text-gray-400 flex items-center gap-2"><i class="fa-solid fa-building-columns text-blue-800"></i> BCA</span>
                <span class="text-3xl font-bold text-gray-400 flex items-center gap-2"><i class="fa-solid fa-building-columns text-blue-500"></i> BRI</span>
                <span class="text-3xl font-bold text-gray-400 flex items-center gap-2"><i class="fa-solid fa-building-columns text-yellow-600"></i> MANDIRI</span>
                <span class="text-3xl font-bold text-gray-400 flex items-center gap-2"><i class="fa-solid fa-wallet text-green-500"></i> GOPAY</span>
                <span class="text-3xl font-bold text-gray-400 flex items-center gap-2"><i class="fa-solid fa-wallet text-purple-600"></i> OVO</span>
                <span class="text-3xl font-bold text-gray-400 flex items-center gap-2"><i class="fa-solid fa-wallet text-blue-400"></i> DANA</span>
                <span class="text-3xl font-bold text-gray-400 flex items-center gap-2"><i class="fa-solid fa-qrcode text-blue-600"></i> QRIS</span>
                <span class="text-3xl font-bold text-gray-400 flex items-center gap-2"><i class="fa-solid fa-building-columns text-blue-800"></i> BCA</span>
                <span class="text-3xl font-bold text-gray-400 flex items-center gap-2"><i class="fa-solid fa-building-columns text-blue-500"></i> BRI</span>
            </div>
        </div>
    </section>

    <section class="py-20 bg-white">
        <div class="container mx-auto px-4">
            <div class="text-center mb-16">
                <span class="text-sancaka-blue font-bold tracking-wider">KENAPA MEMILIH SANCAKA?</span>
                <h2 class="text-4xl font-extrabold mt-2 text-gray-900">10 Keunggulan <span class="text-sancaka-red">Mutlak</span> Kami</h2>
                <div class="w-24 h-1 bg-gradient-to-r from-sancaka-blue to-sancaka-yellow mx-auto mt-4 rounded"></div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6">
                <div class="p-6 bg-white border border-gray-100 rounded-xl shadow hover:shadow-2xl hover:-translate-y-2 transition duration-300 group">
                    <div class="w-14 h-14 bg-blue-100 rounded-full flex items-center justify-center text-sancaka-blue text-2xl mb-4 group-hover:bg-sancaka-blue group-hover:text-white transition">
                        <i class="fa-solid fa-bolt"></i>
                    </div>
                    <h3 class="font-bold text-lg mb-2">Proses Kilat</h3>
                    <p class="text-sm text-gray-600">Layanan perizinan dan ekspedisi super cepat tanpa birokrasi berbelit.</p>
                </div>
                <div class="p-6 bg-white border border-gray-100 rounded-xl shadow hover:shadow-2xl hover:-translate-y-2 transition duration-300 group">
                    <div class="w-14 h-14 bg-green-100 rounded-full flex items-center justify-center text-sancaka-green text-2xl mb-4 group-hover:bg-sancaka-green group-hover:text-white transition">
                        <i class="fa-solid fa-shield-halved"></i>
                    </div>
                    <h3 class="font-bold text-lg mb-2">100% Legal & Aman</h3>
                    <p class="text-sm text-gray-600">Dokumen dijamin asli, terdaftar resmi di lembaga negara (Kemenkumham, dll).</p>
                </div>
                <div class="p-6 bg-white border border-gray-100 rounded-xl shadow hover:shadow-2xl hover:-translate-y-2 transition duration-300 group">
                    <div class="w-14 h-14 bg-yellow-100 rounded-full flex items-center justify-center text-sancaka-yellow text-2xl mb-4 group-hover:bg-sancaka-yellow group-hover:text-white transition">
                        <i class="fa-solid fa-money-bill-wave"></i>
                    </div>
                    <h3 class="font-bold text-lg mb-2">Harga Transparan</h3>
                    <p class="text-sm text-gray-600">Tidak ada biaya tersembunyi. RAB detail dan sesuai budget Anda.</p>
                </div>
                <div class="p-6 bg-white border border-gray-100 rounded-xl shadow hover:shadow-2xl hover:-translate-y-2 transition duration-300 group">
                    <div class="w-14 h-14 bg-red-100 rounded-full flex items-center justify-center text-sancaka-red text-2xl mb-4 group-hover:bg-sancaka-red group-hover:text-white transition">
                        <i class="fa-solid fa-user-tie"></i>
                    </div>
                    <h3 class="font-bold text-lg mb-2">Tim Ahli</h3>
                    <p class="text-sm text-gray-600">Didukung arsitek, notaris, dan ahli logistik berpengalaman puluhan tahun.</p>
                </div>
                <div class="p-6 bg-white border border-gray-100 rounded-xl shadow hover:shadow-2xl hover:-translate-y-2 transition duration-300 group">
                    <div class="w-14 h-14 bg-purple-100 rounded-full flex items-center justify-center text-purple-600 text-2xl mb-4 group-hover:bg-purple-600 group-hover:text-white transition">
                        <i class="fa-solid fa-headset"></i>
                    </div>
                    <h3 class="font-bold text-lg mb-2">Support 24/7</h3>
                    <p class="text-sm text-gray-600">Layanan pelanggan responsif siap membantu kapanpun Anda butuh.</p>
                </div>
                 <div class="p-6 bg-white border border-gray-100 rounded-xl shadow hover:shadow-2xl hover:-translate-y-2 transition duration-300 group">
                    <div class="w-14 h-14 bg-orange-100 rounded-full flex items-center justify-center text-orange-600 text-2xl mb-4 group-hover:bg-orange-600 group-hover:text-white transition">
                        <i class="fa-solid fa-truck-plane"></i>
                    </div>
                    <h3 class="font-bold text-lg mb-2">Multi Ekspedisi</h3>
                    <p class="text-sm text-gray-600">Pilih kurir favoritmu: JNT, JNE, Lion, SAP, ID Express, Indah Cargo.</p>
                </div>
                <div class="p-6 bg-white border border-gray-100 rounded-xl shadow hover:shadow-2xl hover:-translate-y-2 transition duration-300 group">
                    <div class="w-14 h-14 bg-teal-100 rounded-full flex items-center justify-center text-teal-600 text-2xl mb-4 group-hover:bg-teal-600 group-hover:text-white transition">
                        <i class="fa-solid fa-compass-drafting"></i>
                    </div>
                    <h3 class="font-bold text-lg mb-2">Desain Premium</h3>
                    <p class="text-sm text-gray-600">Desain 2D & 3D yang estetis, fungsional, dan modern.</p>
                </div>
                <div class="p-6 bg-white border border-gray-100 rounded-xl shadow hover:shadow-2xl hover:-translate-y-2 transition duration-300 group">
                    <div class="w-14 h-14 bg-indigo-100 rounded-full flex items-center justify-center text-indigo-600 text-2xl mb-4 group-hover:bg-indigo-600 group-hover:text-white transition">
                        <i class="fa-solid fa-earth-asia"></i>
                    </div>
                    <h3 class="font-bold text-lg mb-2">Jangkauan Luas</h3>
                    <p class="text-sm text-gray-600">Melayani Ngawi, Madiun, Magetan, Ponorogo, hingga Seluruh Indonesia.</p>
                </div>
                <div class="p-6 bg-white border border-gray-100 rounded-xl shadow hover:shadow-2xl hover:-translate-y-2 transition duration-300 group">
                    <div class="w-14 h-14 bg-pink-100 rounded-full flex items-center justify-center text-pink-600 text-2xl mb-4 group-hover:bg-pink-600 group-hover:text-white transition">
                        <i class="fa-solid fa-hand-holding-dollar"></i>
                    </div>
                    <h3 class="font-bold text-lg mb-2">Peluang Bisnis</h3>
                    <p class="text-sm text-gray-600">Kami membuka kemitraan agen ekspedisi dan PPOB untuk Anda.</p>
                </div>
                <div class="p-6 bg-white border border-gray-100 rounded-xl shadow hover:shadow-2xl hover:-translate-y-2 transition duration-300 group">
                    <div class="w-14 h-14 bg-gray-100 rounded-full flex items-center justify-center text-gray-600 text-2xl mb-4 group-hover:bg-gray-800 group-hover:text-white transition">
                        <i class="fa-solid fa-star"></i>
                    </div>
                    <h3 class="font-bold text-lg mb-2">Reputasi Terjaga</h3>
                    <p class="text-sm text-gray-600">Ribuan klien puas. Cek review Google Maps kami yang bintang 5.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="py-20 bg-gradient-to-b from-gray-50 to-white">
        <div class="container mx-auto px-4">
            <div class="flex flex-col lg:flex-row gap-12 items-center">
                <div class="lg:w-1/2">
                    <img src="https://tokosancaka.com/storage/uploads/agen.jpg" alt="Tim Sancaka" class="rounded-3xl shadow-2xl border-4 border-white rotate-2 hover:rotate-0 transition duration-500">
                </div>
                <div class="lg:w-1/2">
                    <h2 class="text-3xl lg:text-4xl font-extrabold text-gray-900 mb-6">Mitra Strategis Bisnis & Pembangunan Anda</h2>
                    <p class="text-gray-600 mb-4 leading-relaxed text-justify">
                        <strong>CV. SANCAKA KARYA HUTAMA</strong> hadir sebagai solusi terintegrasi di Ngawi, Jawa Timur. Kami memahami bahwa mengurus legalitas usaha seperti PT, CV, Yayasan, hingga izin teknis (PIRT, Halal, PBG/IMB) sangat melelahkan. Kami hadir untuk memangkas proses itu.
                    </p>
                    <p class="text-gray-600 mb-6 leading-relaxed text-justify">
                        Tak hanya itu, divisi konstruksi kami siap mewujudkan bangunan impian, mulai dari <strong>Jasa Bangun Rumah, Toko, Alfamart/Indomaret</strong>, hingga <strong>Pengeboran Sumur SIPA</strong>. Didukung divisi logistik yang bekerjasama dengan raksasa ekspedisi (JNT Cargo, Lion Parcel, anteraja), kami memastikan arus barang bisnis Anda lancar.
                    </p>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div class="bg-white p-4 rounded-lg shadow border-l-4 border-sancaka-blue">
                            <h4 class="font-bold text-lg">Legalitas</h4>
                            <p class="text-xs text-gray-500">PT, CV, Yayasan, Merk</p>
                        </div>
                        <div class="bg-white p-4 rounded-lg shadow border-l-4 border-sancaka-yellow">
                            <h4 class="font-bold text-lg">Kontruksi</h4>
                            <p class="text-xs text-gray-500">Bangun, Renovasi, RAB</p>
                        </div>
                        <div class="bg-white p-4 rounded-lg shadow border-l-4 border-sancaka-red">
                            <h4 class="font-bold text-lg">Ekspedisi</h4>
                            <p class="text-xs text-gray-500">Domestik & Cargo</p>
                        </div>
                        <div class="bg-white p-4 rounded-lg shadow border-l-4 border-sancaka-green">
                            <h4 class="font-bold text-lg">Digital</h4>
                            <p class="text-xs text-gray-500">Website & PPOB</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="harga" class="py-20 bg-sancaka-blue relative overflow-hidden">
        <div class="absolute top-0 left-0 w-full h-full opacity-10 pointer-events-none">
            <i class="fa-solid fa-file-contract absolute top-10 left-10 text-9xl text-white"></i>
            <i class="fa-solid fa-truck-fast absolute bottom-10 right-10 text-9xl text-white"></i>
        </div>

        <div class="container mx-auto px-4 relative z-10">
            <div class="text-center mb-16 text-white">
                <h2 class="text-4xl font-extrabold">Paket Layanan Favorit</h2>
                <p class="mt-2 text-blue-200">Investasi terbaik untuk masa depan bisnis dan aset Anda.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="bg-white rounded-2xl shadow-2xl p-8 transform hover:scale-105 transition duration-300">
                    <div class="text-center">
                        <span class="bg-blue-100 text-blue-800 text-xs font-bold px-3 py-1 rounded-full uppercase">Starter</span>
                        <h3 class="text-2xl font-bold mt-4 mb-2">Pendirian CV / Izin Dasar</h3>
                        <div class="text-4xl font-extrabold text-sancaka-blue mb-6">Rp 2.500.000<span class="text-sm font-normal text-gray-500">/mulai</span></div>
                    </div>
                    <ul class="space-y-4 text-gray-600 mb-8 text-sm">
                        <li class="flex items-center"><i class="fa-solid fa-check text-green-500 mr-2"></i> Akta Notaris</li>
                        <li class="flex items-center"><i class="fa-solid fa-check text-green-500 mr-2"></i> SK Kemenkumham</li>
                        <li class="flex items-center"><i class="fa-solid fa-check text-green-500 mr-2"></i> NIB (Nomor Induk Berusaha)</li>
                        <li class="flex items-center"><i class="fa-solid fa-check text-green-500 mr-2"></i> NPWP Badan</li>
                        <li class="flex items-center"><i class="fa-solid fa-check text-green-500 mr-2"></i> Konsultasi Gratis</li>
                    </ul>
                    <a href="https://wa.me/6285745808809?text=Halo%20Sancaka,%20saya%20tertarik%20paket%202.5%20Juta" class="block w-full py-3 bg-gray-100 text-sancaka-blue font-bold text-center rounded-lg hover:bg-sancaka-blue hover:text-white transition">Pilih Paket</a>
                </div>

                <div class="bg-white rounded-2xl shadow-2xl p-8 transform scale-110 border-4 border-sancaka-yellow relative z-20">
                    <div class="absolute top-0 left-1/2 transform -translate-x-1/2 -translate-y-1/2 bg-sancaka-red text-white px-4 py-1 rounded-full text-sm font-bold shadow-lg">TERLARIS</div>
                    <div class="text-center">
                        <span class="bg-yellow-100 text-yellow-800 text-xs font-bold px-3 py-1 rounded-full uppercase">Business</span>
                        <h3 class="text-2xl font-bold mt-4 mb-2">Pendirian PT Lengkap</h3>
                        <div class="text-4xl font-extrabold text-sancaka-red mb-6">Rp 5.000.000<span class="text-sm font-normal text-gray-500">/mulai</span></div>
                    </div>
                    <ul class="space-y-4 text-gray-600 mb-8 text-sm">
                        <li class="flex items-center"><i class="fa-solid fa-check text-green-500 mr-2"></i> Semua Fasilitas CV</li>
                        <li class="flex items-center"><i class="fa-solid fa-check text-green-500 mr-2"></i> SK Menteri</li>
                        <li class="flex items-center"><i class="fa-solid fa-check text-green-500 mr-2"></i> Akun OSS RBA Premium</li>
                        <li class="flex items-center"><i class="fa-solid fa-check text-green-500 mr-2"></i> Stempel Perusahaan</li>
                        <li class="flex items-center"><i class="fa-solid fa-check text-green-500 mr-2"></i> Prioritas Layanan</li>
                    </ul>
                    <a href="https://wa.me/6285745808809?text=Halo%20Sancaka,%20saya%20tertarik%20paket%20PT%205%20Juta" class="block w-full py-3 bg-gradient-to-r from-sancaka-yellow to-sancaka-red text-white font-bold text-center rounded-lg shadow-lg hover:shadow-xl transition">Ambil Promo Ini</a>
                </div>

                <div class="bg-white rounded-2xl shadow-2xl p-8 transform hover:scale-105 transition duration-300">
                    <div class="text-center">
                        <span class="bg-green-100 text-green-800 text-xs font-bold px-3 py-1 rounded-full uppercase">Construction</span>
                        <h3 class="text-2xl font-bold mt-4 mb-2">Jasa Bangun & Desain</h3>
                        <div class="text-4xl font-extrabold text-sancaka-green mb-6">Rp 15.000.000<span class="text-sm font-normal text-gray-500">/DP</span></div>
                    </div>
                    <ul class="space-y-4 text-gray-600 mb-8 text-sm">
                        <li class="flex items-center"><i class="fa-solid fa-check text-green-500 mr-2"></i> Desain Arsitek 2D & 3D</li>
                        <li class="flex items-center"><i class="fa-solid fa-check text-green-500 mr-2"></i> Hitung RAB Detail</li>
                        <li class="flex items-center"><i class="fa-solid fa-check text-green-500 mr-2"></i> Pengurusan PBG/IMB</li>
                        <li class="flex items-center"><i class="fa-solid fa-check text-green-500 mr-2"></i> Garansi Pemeliharaan</li>
                        <li class="flex items-center"><i class="fa-solid fa-check text-green-500 mr-2"></i> Tim Tukang Ahli</li>
                    </ul>
                    <a href="https://wa.me/6285745808809?text=Halo%20Sancaka,%20saya%20mau%20konsultasi%20bangunan" class="block w-full py-3 bg-gray-100 text-sancaka-green font-bold text-center rounded-lg hover:bg-sancaka-green hover:text-white transition">Konsultasi Sekarang</a>
                </div>
            </div>
        </div>
    </section>

    <section id="testimoni" class="py-20 bg-gray-50 overflow-hidden">
        <div class="container mx-auto px-4 text-center mb-12">
            <h2 class="text-3xl font-extrabold text-gray-900">Apa Kata Mereka?</h2>
            <p class="text-gray-500">Kepercayaan klien adalah prioritas utama kami.</p>
        </div>
        
        <div class="relative w-full overflow-hidden">
            <div class="flex animate-marquee gap-6 w-max hover:pause">
                <div class="w-80 p-6 bg-white rounded-xl shadow-md border-l-4 border-sancaka-yellow">
                    <div class="flex items-center mb-4 text-yellow-400 text-sm">
                        <i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i>
                    </div>
                    <p class="text-gray-600 italic text-sm mb-4">"Urus PT di Sancaka cepet banget, seminggu jadi. Pelayanan ramah poll!"</p>
                    <div class="font-bold text-gray-800">- Budi Santoso, Ngawi</div>
                </div>
                
                <div class="w-80 p-6 bg-white rounded-xl shadow-md border-l-4 border-sancaka-blue">
                    <div class="flex items-center mb-4 text-yellow-400 text-sm">
                        <i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i>
                    </div>
                    <p class="text-gray-600 italic text-sm mb-4">"Kirim paket pake Lion Parcel di sini murah, kurirnya jemput ke rumah."</p>
                    <div class="font-bold text-gray-800">- Siti Aminah, Madiun</div>
                </div>

                <div class="w-80 p-6 bg-white rounded-xl shadow-md border-l-4 border-sancaka-green">
                    <div class="flex items-center mb-4 text-yellow-400 text-sm">
                        <i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i>
                    </div>
                    <p class="text-gray-600 italic text-sm mb-4">"Desain rumahnya keren, RAB-nya masuk akal. Recommended kontraktor!"</p>
                    <div class="font-bold text-gray-800">- Pak Joko, Magetan</div>
                </div>

                <div class="w-80 p-6 bg-white rounded-xl shadow-md border-l-4 border-sancaka-red">
                    <div class="flex items-center mb-4 text-yellow-400 text-sm">
                        <i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i>
                    </div>
                    <p class="text-gray-600 italic text-sm mb-4">"Bikin NIB dan Halal dibantu sampe tuntas. Mantap Sancaka!"</p>
                    <div class="font-bold text-gray-800">- Rina UMKM, Ngawi</div>
                </div>
                 <div class="w-80 p-6 bg-white rounded-xl shadow-md border-l-4 border-sancaka-yellow">
                    <div class="flex items-center mb-4 text-yellow-400 text-sm">
                        <i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i>
                    </div>
                    <p class="text-gray-600 italic text-sm mb-4">"Urus PT di Sancaka cepet banget, seminggu jadi. Pelayanan ramah poll!"</p>
                    <div class="font-bold text-gray-800">- Budi Santoso, Ngawi</div>
                </div>
            </div>
        </div>
    </section>

    <section class="py-20 bg-gradient-to-r from-sancaka-blue to-sancaka-green text-white">
        <div class="container mx-auto px-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-12 items-center">
                <div>
                    <h2 class="text-4xl font-extrabold mb-4">Mau Penghasilan Tambahan?</h2>
                    <p class="text-lg mb-8 text-blue-100">Gabung menjadi **Agen Ekspedisi** atau **Loket PPOB** bersama Sancaka. Modal minim, untung maksimal. Atau jual produk Anda di Marketplace kami!</p>
                    <div class="flex flex-col sm:flex-row gap-4">
                        <a href="https://wa.me/6285745808809?text=Saya%20mau%20jadi%20Agen" class="bg-sancaka-yellow text-black font-bold py-3 px-8 rounded-full shadow-lg hover:bg-yellow-400 transition text-center">Daftar Agen Sekarang</a>
                        <a href="https://tokosancaka.com/etalase" class="bg-transparent border-2 border-white font-bold py-3 px-8 rounded-full hover:bg-white hover:text-sancaka-green transition text-center">Jualan di Toko Sancaka</a>
                    </div>
                </div>
                <div class="relative">
                    <div class="absolute inset-0 bg-white/20 rounded-full filter blur-3xl"></div>
                    <img src="https://cdni.iconscout.com/illustration/premium/thumb/logistics-service-5347631-4474347.png" alt="Ilustrasi Agen" class="relative z-10 w-3/4 mx-auto drop-shadow-2xl animate-bounce" style="animation-duration: 3s;">
                </div>
            </div>
        </div>
    </section>

    <section class="py-20 bg-white">
        <div class="container mx-auto px-4 max-w-3xl">
            <h2 class="text-3xl font-extrabold text-center mb-10 text-gray-900">Pertanyaan Umum (QnA)</h2>
            
            <div x-data="{ active: null }" class="space-y-4">
                <div class="border border-gray-200 rounded-lg">
                    <button @click="active !== 1 ? active = 1 : active = null" class="flex justify-between w-full px-4 py-4 text-left font-bold text-gray-700 hover:bg-gray-50 focus:outline-none">
                        <span>Berapa lama proses pembuatan PT/CV?</span>
                        <i :class="active === 1 ? 'fa-minus' : 'fa-plus'" class="fa-solid text-sancaka-blue"></i>
                    </button>
                    <div x-show="active === 1" class="px-4 py-4 text-gray-600 border-t bg-gray-50 text-sm">
                        Estimasi proses untuk CV sekitar 3-7 hari kerja, sedangkan PT sekitar 7-14 hari kerja setelah berkas lengkap.
                    </div>
                </div>

                <div class="border border-gray-200 rounded-lg">
                    <button @click="active !== 2 ? active = 2 : active = null" class="flex justify-between w-full px-4 py-4 text-left font-bold text-gray-700 hover:bg-gray-50 focus:outline-none">
                        <span>Apakah bisa hitung RAB pembangunan rumah saja?</span>
                        <i :class="active === 2 ? 'fa-minus' : 'fa-plus'" class="fa-solid text-sancaka-blue"></i>
                    </button>
                    <div x-show="active === 2" class="px-4 py-4 text-gray-600 border-t bg-gray-50 text-sm">
                        Bisa! Kami melayani jasa perhitungan RAB saja, jasa desain arsitek saja, atau borongan bangun sampai jadi (terima kunci).
                    </div>
                </div>

                <div class="border border-gray-200 rounded-lg">
                    <button @click="active !== 3 ? active = 3 : active = null" class="flex justify-between w-full px-4 py-4 text-left font-bold text-gray-700 hover:bg-gray-50 focus:outline-none">
                        <span>Ekspedisi apa saja yang tersedia?</span>
                        <i :class="active === 3 ? 'fa-minus' : 'fa-plus'" class="fa-solid text-sancaka-blue"></i>
                    </button>
                    <div x-show="active === 3" class="px-4 py-4 text-gray-600 border-t bg-gray-50 text-sm">
                        Kami adalah agen multi-ekspedisi. Anda bisa memilih Lion Parcel, J&T, JNE, SAP, ID Express, Indah Cargo, hingga Anteraja di satu tempat.
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="py-10 bg-gray-100">
        <div class="container mx-auto px-4">
            <h3 class="text-2xl font-bold text-center mb-6">Lokasi Kantor Kami</h3>
            <div class="w-full h-96 rounded-xl overflow-hidden shadow-xl border-4 border-white">
                <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3955.773539077202!2d111.44287567491104!3d-7.392982592616854!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2e79e79314052f63%3A0x1bf49943d0caef65!2sJalan%20Dokter%20Wahidin%20No.18A%2C%20RT.22%2FRW.05%2C%20Sidomakmur%2C%20Ketanggi%2C%20Kec.%20Ngawi%2C%20Kabupaten%20Ngawi%2C%20Jawa%20Timur%2063211!5e0!3m2!1sid!2sid!4v1716960000000!5m2!1sid!2sid" width="100%" height="100%" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
            </div>
        </div>
    </section>

    <footer id="kontak" class="bg-gray-900 text-white pt-16 pb-8 border-t-4 border-sancaka-yellow">
        <div class="container mx-auto px-4">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8 mb-12">
                <div class="col-span-1 md:col-span-1">
                    <h3 class="text-2xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-sancaka-yellow to-white mb-4">SANCAKA EXPRESS</h3>
                    <p class="text-gray-400 text-sm mb-4 leading-relaxed">
                        Bagian dari <strong>CV. SANCAKA KARYA HUTAMA</strong>. Solusi terpercaya untuk kebutuhan perizinan bisnis, konstruksi bangunan, dan logistik pengiriman di Indonesia.
                    </p>
                    <div class="flex gap-4">
                        <a href="#" class="w-10 h-10 rounded-full bg-gray-800 flex items-center justify-center hover:bg-sancaka-blue transition"><i class="fa-brands fa-facebook-f"></i></a>
                        <a href="#" class="w-10 h-10 rounded-full bg-gray-800 flex items-center justify-center hover:bg-pink-600 transition"><i class="fa-brands fa-instagram"></i></a>
                        <a href="#" class="w-10 h-10 rounded-full bg-gray-800 flex items-center justify-center hover:bg-red-600 transition"><i class="fa-brands fa-youtube"></i></a>
                    </div>
                </div>

                <div class="col-span-1 md:col-span-1">
                    <h4 class="text-lg font-bold mb-4 text-sancaka-yellow">Hubungi Kami</h4>
                    <ul class="space-y-3 text-sm text-gray-400">
                        <li class="flex items-start gap-3">
                            <i class="fa-solid fa-location-dot mt-1 text-sancaka-red"></i>
                            <span>Jl. Dr. Wahidin No. 18A RT 22 RW 05, Kel. Ketanggi, Kec. Ngawi, Kab. Ngawi, Jawa Timur 63211</span>
                        </li>
                        <li class="flex items-center gap-3">
                            <i class="fa-brands fa-whatsapp text-green-500"></i>
                            <span>0857-4580-8809</span>
                        </li>
                        <li class="flex items-center gap-3">
                            <i class="fa-solid fa-envelope text-blue-500"></i>
                            <span>admin@tokosancaka.com</span>
                        </li>
                    </ul>
                </div>

                <div class="col-span-1 md:col-span-1">
                    <h4 class="text-lg font-bold mb-4 text-sancaka-yellow">Layanan</h4>
                    <ul class="space-y-2 text-sm text-gray-400">
                        <li><a href="#" class="hover:text-white transition">Jasa Pendirian PT/CV</a></li>
                        <li><a href="#" class="hover:text-white transition">Kontraktor & Arsitek</a></li>
                        <li><a href="#" class="hover:text-white transition">Cek Ongkir Ekspedisi</a></li>
                        <li><a href="#" class="hover:text-white transition">Pendaftaran Halal & BPOM</a></li>
                        <li><a href="#" class="hover:text-white transition">PPOB & Tiket</a></li>
                    </ul>
                </div>

                <div class="col-span-1 md:col-span-1">
                    <h4 class="text-lg font-bold mb-4 text-sancaka-yellow">Pembayaran</h4>
                    <div class="grid grid-cols-4 gap-2">
                        <div class="bg-white p-1 rounded h-8 w-12 flex items-center justify-center"><i class="fa-solid fa-building-columns text-blue-800 text-xs"></i></div> <div class="bg-white p-1 rounded h-8 w-12 flex items-center justify-center"><i class="fa-solid fa-building-columns text-blue-500 text-xs"></i></div> <div class="bg-white p-1 rounded h-8 w-12 flex items-center justify-center"><i class="fa-solid fa-qrcode text-gray-800 text-xs"></i></div> <div class="bg-white p-1 rounded h-8 w-12 flex items-center justify-center"><i class="fa-solid fa-wallet text-blue-400 text-xs"></i></div> </div>
                </div>
            </div>

            <div class="border-t border-gray-800 pt-8 flex flex-col md:flex-row justify-between items-center text-xs text-gray-500">
                <p>&copy; 2024 CV. SANCAKA KARYA HUTAMA. All rights reserved.</p>
                <div class="flex gap-4 mt-4 md:mt-0">
                    <a href="#" class="hover:text-white">Privacy Policy</a>
                    <a href="#" class="hover:text-white">Terms of Service</a>
                </div>
            </div>
        </div>
    </footer>

    <a href="https://wa.me/6285745808809" target="_blank" class="fixed bottom-24 right-6 bg-green-500 text-white w-14 h-14 rounded-full shadow-2xl flex items-center justify-center text-3xl hover:scale-110 hover:bg-green-600 transition z-50 animate-bounce" title="Chat WhatsApp">
        <i class="fa-brands fa-whatsapp"></i>
    </a>

    <button x-data="{ show: false }" @scroll.window="show = (window.pageYOffset > 300) ? true : false" @click="window.scrollTo({top: 0, behavior: 'smooth'})" x-show="show" x-transition class="fixed bottom-6 right-6 bg-sancaka-blue text-white w-12 h-12 rounded-full shadow-lg flex items-center justify-center hover:bg-blue-800 transition z-50">
        <i class="fa-solid fa-arrow-up"></i>
    </button>

    <script>
        const slides = document.querySelectorAll('.slide');
        let currentSlide = 0;

        function nextSlide() {
            slides[currentSlide].classList.remove('active');
            currentSlide = (currentSlide + 1) % slides.length;
            slides[currentSlide].classList.add('active');
        }

        setInterval(nextSlide, 5000); // Change slide every 5 seconds
    </script>
</body>
</html>