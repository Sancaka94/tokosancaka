<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sahabat Wisata Iman - Travel Umroh & Haji</title>
    
    <!-- Favicon -->
<link rel="icon" href="https://tokosancaka.com/storage/uploads/fav-umroh.jpg" type="image/jpeg">
<link rel="shortcut icon" href="https://tokosancaka.com/storage/uploads/fav-umroh.jpg" type="image/jpeg">

    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://unpkg.com/swiper/swiper-bundle.min.css" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8fafc; /* slate-50 */
        }
        .cta-button {
            transition: all 0.3s ease;
        }
        .cta-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }
        .paket-card {
            transition: all 0.3s ease;
            border: 1px solid #e2e8f0; /* slate-200 */
        }
        .paket-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            border-color: #ca8a04; /* yellow-600 */
        }
        .paket-populer {
            border: 2px solid #ca8a04; /* yellow-600 */
            transform: scale(1.05);
        }
        .floating-whatsapp {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 100;
        }
        .swiper-button-next, .swiper-button-prev {
            color: #ffffff !important;
        }
        .swiper-pagination-bullet-active {
            background-color: #ca8a04 !important; /* yellow-600 */
        }
        .modal {
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.8);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .modal-content-image {
            position: relative;
            padding: 20px;
            max-width: 90%;
            max-height: 90%;
        }
        .close-button-image {
            position: absolute;
            top: 15px;
            right: 35px;
            color: #f1f1f1;
            font-size: 40px;
            font-weight: bold;
            transition: 0.3s;
            cursor: pointer;
        }
        .close-button-image:hover,
        .close-button-image:focus {
            color: #bbb;
            text-decoration: none;
            cursor: pointer;
        }
        #modalImage {
            width: auto;
            height: auto;
            max-width: 100%;
            max-height: 80vh;
        }
    </style>
</head>
<body>

    <!-- Header -->
    <header class="bg-white shadow-md sticky top-0 z-50">
        <nav class="container mx-auto px-6 py-4 flex justify-between items-center">
            <a href="#" class="text-2xl font-bold text-gray-800">
                PT. SAHABAT WISATA <span class="text-yellow-600"><strong>IMAN</strong></span>
            </a>
            <div class="hidden md:flex space-x-6 items-center">
                <a href="#paket" class="text-gray-600 hover:text-yellow-600">Paket Umroh</a>
                <a href="#keunggulan" class="text-gray-600 hover:text-yellow-600">Keunggulan</a>
                <a href="#kontak" class="text-gray-600 hover:text-yellow-600">Kontak</a>
            </div>
            <a href="https://wa.me/6285745808809?text=Assalamualaikum,%20saya%20tertarik%20dengan%20paket%20umroh%20Sahabat%20Wisata%20Iman." target="_blank" class="cta-button bg-red-500 text-white font-bold py-2 px-4 rounded-lg flex items-center space-x-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                  <path d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C6.477 18 2 13.523 2 8V3z" />
                </svg>
                <span>DAFTAR SEKARANG !</span>
            </a>
        </nav>
    </header>

    <!-- Announcement Bar -->
    <section class="bg-gray-900 text-white py-4">
        <div class="container mx-auto px-6 text-center">
            <div class="flex flex-col md:flex-row items-center justify-center space-y-3 md:space-y-0 md:space-x-6">
                <p class="font-bold text-lg"><span class="bg-yellow-500 text-gray-900 px-2 py-1 rounded-md mr-2">PROMO!</span>Daftar 5 Gratis 1 Berakhir Dalam:</p>
                <div id="countdown" class="text-2xl font-bold text-yellow-400 tracking-widest">
                    <span id="hours">00</span>:<span id="minutes">00</span>:<span id="seconds">00</span>
                </div>
                <a href="#paket" class="cta-button bg-yellow-600 text-gray-900 font-bold py-2 px-5 rounded-lg text-sm">Daftar Rombongan</a>
            </div>
        </div>
    </section>

    <main>
        <!-- Hero Section -->
        <section id="hero-slider" class="relative">
            <!-- Slider main container -->
            <div class="swiper">
                <!-- Additional required wrapper -->
                <div class="swiper-wrapper">
                    <!-- Slides -->
                    <div class="swiper-slide"><img src="https://tokosancaka.com/storage/uploads/umroh-1.jpg" alt="Suasana Umroh 1" class="w-full h-auto object-contain"></div>
                    <div class="swiper-slide"><img src="https://tokosancaka.com/storage/uploads/umroh-2.jpg" alt="Suasana Umroh 2" class="w-full h-auto object-contain"></div>
                    <div class="swiper-slide"><img src="https://tokosancaka.com/storage/uploads/umroh-3.jpg" alt="Suasana Umroh 3" class="w-full h-auto object-contain"></div>
                </div>
                <!-- Pagination -->
                <div class="swiper-pagination"></div>
                <!-- Navigation buttons -->
                <div class="swiper-button-prev"></div>
                <div class="swiper-button-next"></div>
            </div>
        </section>

        <!-- Intro Text Section -->
        <section class="bg-white pt-16 pb-12 md:pt-20 md:pb-16 text-center">
            <div class="container mx-auto px-6">
                <h1 class="text-3xl md:text-4xl font-extrabold text-gray-800 leading-tight mb-4">Umroh Spesial 2025 Bersama Sahabat Wisata Iman</h1>
                <p class="text-lg md:text-xl text-gray-600 mb-8 max-w-3xl mx-auto">Dibimbing oleh Ustadz Aris Sugiantoro, wujudkan perjalanan ibadah yang lebih khusyuk, mendalam, dan tak terlupakan dengan harga terbaik.</p>
                <a href="#paket" class="cta-button bg-yellow-600 text-gray-900 font-bold py-3 px-8 rounded-lg text-lg">Lihat Pilihan Paket</a>
            </div>
        </section>

        <!-- Paket Section -->
        <section id="paket" class="py-20">
            <div class="container mx-auto px-6">
                <div class="text-center mb-12">
                    <h2 class="text-3xl md:text-4xl font-bold text-gray-800">Pilihan Paket Umroh Terbaik Untuk Anda</h2>
                    <p class="text-gray-600 mt-2">Pilih paket yang paling sesuai dengan kebutuhan ibadah Anda dan keluarga.</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 lg:gap-12">
                    <!-- Paket Hemat -->
                    <div class="paket-card bg-white rounded-lg shadow-lg p-8 flex flex-col">
                        <h3 class="text-2xl font-bold text-gray-800 text-center">Paket Spesial</h3>
                        <p class="text-center text-gray-500 mb-6">Pilihan terbaik untuk pengalaman umroh yang khusyuk.</p>
                        <div class="text-center my-4">
                            <span class="text-lg text-gray-500 line-through">Rp 28,5 Jt</span>
                            <p class="text-4xl font-extrabold text-yellow-600">Rp 25,2 Jt</p>
                            <p class="text-gray-500">/ per orang</p>
                        </div>
                        <ul class="space-y-4 mb-8 flex-grow">
                            <li class="flex items-center"><svg class="w-6 h-6 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>Tiket Pesawat PP</li>
                            <li class="flex items-center"><svg class="w-6 h-6 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>Visa Umroh & Asuransi</li>
                            <li class="flex items-center"><svg class="w-6 h-6 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>Hotel Bintang 3 (Setaraf)</li>
                            <li class="flex items-center"><svg class="w-6 h-6 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>Makan 3x Sehari</li>
                            <li class="flex items-center"><svg class="w-6 h-6 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>Perlengkapan Umroh</li>
                        </ul>
                        <a href="https://wa.me/6285745808809?text=Assalamualaikum,%20saya%20mau%20bertanya%20tentang%20Paket%20Spesial%20Rp%2025,2%20Juta." target="_blank" class="w-full text-center cta-button bg-gray-800 text-white font-bold py-3 px-6 rounded-lg">Pilih Paket Ini</a>
                    </div>

                    <!-- Paket Populer -->
                    <div class="paket-card bg-white rounded-lg shadow-lg p-8 flex flex-col relative paket-populer">
                         <span class="absolute top-0 right-0 bg-yellow-600 text-white text-xs font-bold px-3 py-1 rounded-bl-lg rounded-tr-lg">Paling Populer</span>
                        <h3 class="text-2xl font-bold text-gray-800 text-center">Paket Bimbingan Ustadz</h3>
                        <p class="text-center text-gray-500 mb-6">Ibadah lebih mendalam bersama Ustadz Aris Sugiantoro.</p>
                        <div class="text-center my-4">
                             <span class="text-lg text-gray-500 line-through">Rp 28,5 Jt</span>
                            <p class="text-4xl font-extrabold text-yellow-600">Rp 25,2 Jt</p>
                            <p class="text-gray-500">/ per orang</p>
                        </div>
                        <ul class="space-y-4 mb-8 flex-grow">
                             <li class="flex items-center font-bold text-yellow-700"><svg class="w-6 h-6 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>Bimbingan Eksklusif</li>
                            <li class="flex items-center"><svg class="w-6 h-6 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>Semua Fasilitas Paket Spesial</li>
                            <li class="flex items-center"><svg class="w-6 h-6 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>Kajian Intensif</li>
                            <li class="flex items-center"><svg class="w-6 h-6 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>Muthawwif & Tour Leader</li>
                             <li class="flex items-center"><svg class="w-6 h-6 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>Durasi 9 Hari</li>
                        </ul>
                        <a href="https://wa.me/6285745808809?text=Assalamualaikum,%20saya%20tertarik%20dengan%20Paket%20Bimbingan%20Ustadz." target="_blank" class="w-full text-center cta-button bg-yellow-600 text-gray-900 font-bold py-3 px-6 rounded-lg">Pilih Paket Ini</a>
                    </div>

                    <!-- Paket Rombongan -->
                    <div class="paket-card bg-white rounded-lg shadow-lg p-8 flex flex-col">
                        <h3 class="text-2xl font-bold text-gray-800 text-center">Paket Rombongan</h3>
                         <p class="text-center text-gray-500 mb-6">Berangkat bersama lebih hemat. Daftar 5 Gratis 1!</p>
                        <div class="text-center my-4">
                            <p class="text-4xl font-extrabold text-yellow-600">Rp 29,5 Jt</p>
                            <p class="text-gray-500">/ per orang</p>
                        </div>
                        <ul class="space-y-4 mb-8 flex-grow">
                             <li class="flex items-center font-bold text-yellow-700"><svg class="w-6 h-6 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>Promo Daftar 5 Gratis 1</li>
                            <li class="flex items-center"><svg class="w-6 h-6 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>Tiket Pesawat Domestik & Internasional</li>
                             <li class="flex items-center"><svg class="w-6 h-6 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>Handling Bandara</li>
                            <li class="flex items-center"><svg class="w-6 h-6 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>Semua Fasilitas Paket Spesial</li>
                        </ul>
                        <a href="https://wa.me/6285745808809?text=Assalamualaikum,%20saya%20mau%20bertanya%20mengenai%20Paket%20Rombongan." target="_blank" class="w-full text-center cta-button bg-gray-800 text-white font-bold py-3 px-6 rounded-lg">Pilih Paket Ini</a>
                    </div>
                </div>
            </div>
        </section>

        <!-- Keunggulan Section -->
        <section id="keunggulan" class="py-20">
            <div class="container mx-auto px-6">
                 <div class="text-center mb-12">
                    <h2 class="text-3xl md:text-4xl font-bold text-gray-800">Kenapa Memilih Kami?</h2>
                    <p class="text-gray-600 mt-2">Kami berkomitmen memberikan pelayanan terbaik untuk kenyamanan ibadah Anda.</p>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
                    <div class="text-center p-6">
                        <div class="flex items-center justify-center h-16 w-16 rounded-full bg-yellow-100 text-yellow-600 mx-auto mb-4">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                              <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold mb-2">Travel Resmi & Amanah</h3>
                        <p class="text-gray-600">Terdaftar resmi di Kemenag RI (PPIU & PIHK), perjalanan Anda dijamin aman dan terpercaya.</p>
                    </div>
                     <div class="text-center p-6">
                        <div class="flex items-center justify-center h-16 w-16 rounded-full bg-yellow-100 text-yellow-600 mx-auto mb-4">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                              <path d="M12 14l9-5-9-5-9 5 9 5z" />
                              <path d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-5.998 12.078 12.078 0 01.665-6.479L12 14z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-5.998 12.078 12.078 0 01.665-6.479L12 14zm-4 6v-7.5l4-2.222 4 2.222V20M12 14L2 9l10-5 10 5-10 5z" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold mb-2">Bimbingan Ibadah</h3>
                        <p class="text-gray-600">Dapatkan bimbingan manasik dan kajian intensif dari ustadz berpengalaman agar ibadah lebih bermakna.</p>
                    </div>
                     <div class="text-center p-6">
                        <div class="flex items-center justify-center h-16 w-16 rounded-full bg-yellow-100 text-yellow-600 mx-auto mb-4">
                           <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                              <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v.01" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold mb-2">Harga Terbaik</h3>
                        <p class="text-gray-600">Kami menawarkan paket dengan harga kompetitif tanpa mengurangi kualitas fasilitas dan pelayanan.</p>
                    </div>
                     <div class="text-center p-6">
                        <div class="flex items-center justify-center h-16 w-16 rounded-full bg-yellow-100 text-yellow-600 mx-auto mb-4">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                              <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold mb-2">Fasilitas Lengkap</h3>
                        <p class="text-gray-600">Mulai dari tiket, visa, hotel, makan, hingga perlengkapan umroh, semua telah kami siapkan untuk Anda.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Lokasi Section -->
        <section id="lokasi" class="py-20 bg-white">
            <div class="container mx-auto px-6">
                <div class="text-center mb-12">
                    <h2 class="text-3xl md:text-4xl font-bold text-gray-800">Temukan Kami di Sini</h2>
                    <p class="text-gray-600 mt-2">Kunjungi kantor perwakilan kami di Ngawi untuk informasi lebih lanjut.</p>
                </div>
                <div class="rounded-lg shadow-lg overflow-hidden">
                     <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3955.760133448135!2d111.4428528759547!3d-7.392976992500791!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2e79e72a876cad9d%3A0x6189c8dadc39bef1!2sJl.%20Dokter%20Wahidin%20No.18A%2C%20RT.22%2FRW.05%2C%20Sidomakmur%2C%20Ketanggi%2C%20Kec.%20Ngawi%2C%20Kabupaten%20Ngawi%2C%20Jawa%20Timur%2063211!5e0!3m2!1sid!2sid!4v1662200000000!5m2!1sid!2sid" width="100%" height="450" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                </div>
            </div>
        </section>
        
        <!-- Metode Pembayaran -->
        <section class="py-16 bg-gray-50">
            <div class="container mx-auto px-4 text-center">
                <h2 class="text-3xl font-bold text-gray-800 mb-4 md:text-4xl">Metode Pembayaran</h2>
                <p class="text-gray-600 mb-12 text-lg">Kami menyediakan berbagai kemudahan transaksi untuk Anda.</p>
                <div class="flex flex-wrap justify-center items-center gap-8 md:gap-12">
                    <div onclick="openImageModal('http://sancaka.bisnis.pro/wp-content/uploads/sites/5/2025/05/WhatsApp-Image-2025-05-12-at-14.44.32.jpeg')" class="p-4 bg-white rounded-lg shadow-md flex flex-col items-center justify-center h-28 w-36 transform hover:scale-105 transition-transform duration-200 cursor-pointer">
                        <img src="http://sancaka.bisnis.pro/wp-content/uploads/sites/5/2025/05/WhatsApp-Image-2025-05-12-at-14.44.32.jpeg" alt="QRIS" class="h-12 object-contain mb-2">
                        <p class="text-gray-700 font-semibold text-sm text-center">QRIS</p>
                    </div>
                    <div onclick="openImageModal('http://sancaka.bisnis.pro/wp-content/uploads/sites/5/2025/05/WhatsApp-Image-2025-05-12-at-14.44.32.jpeg')" class="p-4 bg-white rounded-lg shadow-md flex flex-col items-center justify-center h-28 w-36 transform hover:scale-105 transition-transform duration-200 cursor-pointer">
                        <img src="https://upload.wikimedia.org/wikipedia/commons/5/5c/Bank_Central_Asia.svg" alt="Bank BCA" class="h-12 object-contain mb-2">
                        <p class="text-gray-700 font-semibold text-sm">BANK BCA</p>
                    </div>
                    <div onclick="openImageModal('http://sancaka.bisnis.pro/wp-content/uploads/sites/5/2025/05/WhatsApp-Image-2025-05-12-at-14.44.32.jpeg')" class="p-4 bg-white rounded-lg shadow-md flex flex-col items-center justify-center h-28 w-36 transform hover:scale-105 transition-transform duration-200 cursor-pointer">
                        <img src="https://upload.wikimedia.org/wikipedia/commons/6/68/BANK_BRI_logo.svg" alt="Bank BRI" class="h-12 object-contain mb-2">
                        <p class="text-gray-700 font-semibold text-sm">BANK BRI</p>
                    </div>
                    <div onclick="openImageModal('http://sancaka.bisnis.pro/wp-content/uploads/sites/5/2025/05/WhatsApp-Image-2025-05-12-at-14.44.32.jpeg')" class="p-4 bg-white rounded-lg shadow-md flex flex-col items-center justify-center h-28 w-36 transform hover:scale-105 transition-transform duration-200 cursor-pointer">
                        <img src="https://upload.wikimedia.org/wikipedia/id/5/55/BNI_logo.svg" alt="Bank BNI" class="h-12 object-contain mb-2">
                        <p class="text-gray-700 font-semibold text-sm">BANK BNI</p>
                    </div>
                    <div onclick="openImageModal('http://sancaka.bisnis.pro/wp-content/uploads/sites/5/2025/05/WhatsApp-Image-2025-05-12-at-14.44.32.jpeg')" class="p-4 bg-white rounded-lg shadow-md flex flex-col items-center justify-center h-28 w-36 transform hover:scale-105 transition-transform duration-200 cursor-pointer">
                        <img src="https://upload.wikimedia.org/wikipedia/commons/a/ad/Bank_Mandiri_logo_2016.svg" alt="Bank Mandiri" class="h-12 object-contain mb-2">
                        <p class="text-gray-700 font-semibold text-sm">BANK MANDIRI</p>
                    </div>
                    <div onclick="openImageModal('http://sancaka.bisnis.pro/wp-content/uploads/sites/5/2025/05/WhatsApp-Image-2025-05-12-at-14.44.32.jpeg')" class="p-4 bg-white rounded-lg shadow-md flex flex-col items-center justify-center h-28 w-36 transform hover:scale-105 transition-transform duration-200 cursor-pointer">
                        <img src="https://upload.wikimedia.org/wikipedia/commons/a/a0/Bank_Syariah_Indonesia.svg" alt="Bank BSI" class="h-12 object-contain mb-2">
                        <p class="text-gray-700 font-semibold text-sm">BANK BSI</p>
                    </div>
                </div>
            </div>
        </section>
        
        <!-- Modal Gambar Pembayaran -->
        <div id="imageModal" class="modal" style="display: none;">
          <div class="modal-content-image">
            <span class="close-button-image" onclick="closeImageModal()">&times;</span>
            <img id="modalImage" src="" alt="Gambar Pembayaran" class="max-w-full max-h-full object-contain rounded-md">
          </div>
        </div>

        <!-- Final CTA Section -->
        <section class="bg-yellow-600">
            <div class="container mx-auto px-6 py-16 text-center">
                 <h2 class="text-3xl font-bold text-gray-900 mb-2">Tunggu Apa Lagi? Wujudkan Niat Anda Sekarang!</h2>
                <p class="text-gray-800 text-lg mb-8">Kursi sangat terbatas! Hubungi kami untuk konsultasi dan pendaftaran.</p>
                 <a href="https://wa.me/6285745808809?text=Assalamualaikum,%20saya%20tertarik%20untuk%20mendaftar%20umroh." target="_blank" class="cta-button bg-gray-900 text-white font-bold py-3 px-8 rounded-lg text-lg inline-flex items-center space-x-2">
                     <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                       <path stroke-linecap="round" stroke-linejoin="round" d="M17 8h2a2 2 0 012 2v6a2 2 0 01-2 2h-2v4l-4-4H9a2 2 0 01-2-2V7a2 2 0 012-2h2m8-4H5a2 2 0 00-2 2v10a2 2 0 002 2h11l4 4V7a2 2 0 00-2-2z" />
                     </svg>
                     <span>Hubungi via WhatsApp</span>
                 </a>
            </div>
        </section>

    </main>

    <!-- Footer -->
    <footer id="kontak" class="bg-gray-800 text-white">
        <div class="container mx-auto px-6 py-10">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 text-center md:text-left">
                <div>
                    <h3 class="text-xl font-bold mb-4">PT. Sahabat Wisata Iman</h3>
                    <p class="text-gray-400">Travel Umroh & Haji Berizin Resmi Kemenag RI. Kami melayani jamaah dengan sepenuh hati.</p>
                     <p class="text-gray-400 mt-2 text-sm">PPIU No. 386 Th. 2020</p>
                     <p class="text-gray-400 text-sm">PIHK No. 91200005028590004</p>
                </div>
                 <div>
                    <h3 class="text-xl font-bold mb-4">Alamat Kantor</h3>
                    <p class="text-gray-400 font-semibold">Kantor Cabang Semarang:</p>
                    <p class="text-gray-400">Jl. Menoreh Utara VIII, No 15B, Kota Semarang.</p>
                    <p class="text-gray-400 font-semibold mt-2">Kantor Perwakilan Ngawi:</p>
                    <p class="text-gray-400">Jl. dr. Wahidin No. 18A RT.022 RW.005, Kab. Ngawi Jawa Timur 63211 (Depan RSUD Soeroto)</p>
                </div>
                 <div>
                    <h3 class="text-xl font-bold mb-4">Informasi & Pendaftaran</h3>
                    <p class="text-gray-400">Amal Ibnu Muharam</p>
                    <a href="tel:+6285745808809" class="text-yellow-400 text-lg hover:underline">0857-4580-8809</a>
                    <div class="flex justify-center md:justify-start space-x-4 mt-4">
                        <a href="https://www.instagram.com/p/DNprOHmTzEA/?igsh=MWJ0MWFmY2VzNWVx" class="text-gray-400 hover:text-white"><span class="sr-only">Instagram</span><svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path fill-rule="evenodd" d="M12.315 2c2.43 0 2.784.013 3.808.06 1.064.049 1.791.218 2.427.465a4.902 4.902 0 011.772 1.153 4.902 4.902 0 011.153 1.772c.247.636.416 1.363.465 2.427.048 1.024.06 1.378.06 3.808s-.012 2.784-.06 3.808c-.049 1.064-.218 1.791-.465 2.427a4.902 4.902 0 01-1.153 1.772 4.902 4.902 0 01-1.772 1.153c-.636.247-1.363.416-2.427.465-1.024.048-1.378.06-3.808.06s-2.784-.013-3.808-.06c-1.064-.049-1.791-.218-2.427-.465a4.902 4.902 0 01-1.772-1.153 4.902 4.902 0 01-1.153-1.772c-.247-.636-.416-1.363-.465-2.427-.048-1.024-.06-1.378-.06-3.808s.012-2.784.06-3.808c.049 1.064.218 1.791.465 2.427a4.902 4.902 0 011.153-1.772A4.902 4.902 0 016.08 2.525c.636-.247 1.363-.416 2.427.465C9.53 2.013 9.884 2 12.315 2zM12 7a5 5 0 100 10 5 5 0 000-10zm0 8a3 3 0 110-6 3 3 0 010 6zm6.406-11.845a1.25 1.25 0 100 2.5 1.25 1.25 0 000-2.5z" clip-rule="evenodd" /></svg></a>
                        <a href="#" class="text-gray-400 hover:text-white"><span class="sr-only">Facebook</span><svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path fill-rule="evenodd" d="M22 12c0-5.523-4.477-10-10-10S2 6.477 2 12c0 4.991 3.657 9.128 8.438 9.878v-6.987h-2.54V12h2.54V9.797c0-2.506 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.63.771-1.63 1.562V12h2.773l-.443 2.89h-2.33v6.988C18.343 21.128 22 16.991 22 12z" clip-rule="evenodd" /></svg></a>
                    </div>
                </div>
            </div>
            <div class="mt-8 border-t border-gray-700 pt-6 text-center text-gray-400 text-sm">
                <p>&copy; 2025 Sahabat Wisata Iman. All Rights Reserved.</p>
            </div>
        </div>
    </footer>
    
    <!-- Floating WhatsApp button -->
    <a href="https://wa.me/6285745808809?text=Assalamualaikum,%20saya%20tertarik%20dengan%20paket%20umroh%20Sahabat%20Wisata%20Iman." target="_blank" class="floating-whatsapp cta-button bg-green-500 text-white p-4 rounded-full shadow-lg">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M18 10c0 3.866-3.582 7-8 7a8.841 8.841 0 01-4.083-.98L2 17l1.03-3.59A8.966 8.966 0 012 10c0-4.418 4.477-8 10-8s8 3.582 8 7zM4.773 13.334A6.987 6.987 0 0010 15a7 7 0 007-7c0-1.554-.624-2.96-1.637-4.007A6.974 6.974 0 0010 3c-1.612 0-3.08.57-4.227 1.515L4 4.545v.228l.773.773zm7.227-4.334a.5.5 0 00-.707-.707l-2 2a.5.5 0 00.707.707l2-2z" clip-rule="evenodd" />
        </svg>
    </a>

    <script src="https://unpkg.com/swiper/swiper-bundle.min.js"></script>
    <script>
        const swiper = new Swiper('.swiper', {
            loop: true,
            autoplay: {
                delay: 5000,
                disableOnInteraction: false,
            },
            effect: 'fade',
            pagination: {
                el: '.swiper-pagination',
                clickable: true,
            },
            navigation: {
                nextEl: '.swiper-button-next',
                prevEl: '.swiper-button-prev',
            },
        });

        function openImageModal(imageUrl) {
            const modal = document.getElementById('imageModal');
            const modalImg = document.getElementById('modalImage');
            modalImg.src = imageUrl;
            modal.style.display = 'flex';
        }

        function closeImageModal() {
            const modal = document.getElementById('imageModal');
            modal.style.display = 'none';
        }

        // Countdown Timer
        const countdownDate = new Date().getTime() + 24 * 60 * 60 * 1000;

        const countdownFunction = setInterval(function() {
            const now = new Date().getTime();
            const distance = countdownDate - now;

            const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((distance % (1000 * 60)) / 1000);

            document.getElementById("hours").innerText = hours.toString().padStart(2, '0');
            document.getElementById("minutes").innerText = minutes.toString().padStart(2, '0');
            document.getElementById("seconds").innerText = seconds.toString().padStart(2, '0');

            if (distance < 0) {
                clearInterval(countdownFunction);
                document.getElementById("countdown").innerHTML = "PROMO BERAKHIR";
            }
        }, 1000);
    </script>
</body>
</html>

