<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="google-site-verification" content="VuyWnIQA7O9C8rt_MoH-l1GDzsZGwCK7YzWIY4LJ48E" />
    <title>Sancaka Karya Hutama - Jasa Perizinan, Konstruksi & Ekspedisi Terpercaya Ngawi</title>
    <meta name="description" content="Solusi satu pintu untuk jasa perizinan (IMB, PBG, PT, CV), kontraktor bangun rumah & toko, arsitek, serta agen ekspedisi lengkap di Ngawi, Jawa Timur. Cepat, Aman, Profesional.">
    <meta name="keywords" content="Jasa Perizinan Ngawi, Kontraktor Bangunan Ngawi, Urus PBG SLF IMB, Jasa Arsitek Ngawi, Ekspedisi Murah Ngawi, Sancaka Pos, Agen JNT JNE, Biaya Bangun Rumah, Konsultan Legalitas Bisnis">
    
    <link rel="icon" href="https://tokosancaka.com/storage/uploads/logo.jpeg" type="image/jpeg">

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        /* --- CSS VARIABLES & GLOBAL RESET --- */
        :root {
            /* Gradasi "Kantor Pajak" inspired colors */
            --primary-yellow: #FFC107;
            --primary-blue: #0277BD;
            --primary-red: #D32F2F;
            --primary-green: #388E3C;
            --grad-main: linear-gradient(135deg, var(--primary-blue), var(--primary-green));
            --grad-accent: linear-gradient(135deg, var(--primary-yellow), var(--primary-red));
            --text-dark: #333;
            --text-light: #f4f4f4;
            --section-bg: #f9faff;
            --white: #ffffff;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            line-height: 1.6;
            color: var(--text-dark);
            overflow-x: hidden;
        }

        a { text-decoration: none; color: inherit; }
        ul { list-style: none; }
        img { max-width: 100%; height: auto; }

        /* Utility Classes */
        .container { max-width: 1200px; margin: 0 auto; padding: 0 20px; }
        .text-center { text-align: center; }
        .section-padding { padding: 80px 0; }
        .section-title { font-size: 2.5rem; font-weight: 700; margin-bottom: 20px; background: var(--grad-main); -webkit-background-clip: text; -webkit-text-fill-color: transparent; display: inline-block;}
        .section-subtitle { font-size: 1.1rem; color: #666; margin-bottom: 40px; max-width: 800px; margin-left: auto; margin-right: auto;}
        .btn { display: inline-block; padding: 12px 30px; border-radius: 50px; font-weight: 600; transition: 0.3s; cursor: pointer; border: none;}
        .btn-grad { background: var(--grad-main); color: var(--white); box-shadow: 0 4px 15px rgba(2, 119, 189, 0.3); }
        .btn-grad:hover { transform: translateY(-3px); box-shadow: 0 6px 20px rgba(2, 119, 189, 0.4); }
        .btn-accent { background: var(--grad-accent); color: var(--white); }

        /* --- HEADER & NAVIGATION (Dropdown Menu) --- */
        .header {
            background: var(--white);
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1000;
        }

        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
        }

        .logo img {
            height: 50px;
            vertical-align: middle;
        }

        .nav-links {
            display: flex;
            gap: 20px;
        }

        .nav-item { position: relative; }
        .nav-link { font-weight: 600; padding: 10px 15px; display: block; transition: color 0.3s;}
        .nav-link:hover { color: var(--primary-blue); }

        /* Dropdown */
        .dropdown-menu {
            position: absolute;
            top: 100%;
            left: 0;
            background: var(--white);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            min-width: 220px;
            opacity: 0;
            visibility: hidden;
            transform: translateY(10px);
            transition: 0.3s;
            border-top: 3px solid var(--primary-blue);
        }

        .nav-item:hover .dropdown-menu { opacity: 1; visibility: visible; transform: translateY(0); }
        .dropdown-item { padding: 12px 20px; border-bottom: 1px solid #eee; font-size: 0.95rem; display: block; }
        .dropdown-item:hover { background: #f4f4f4; color: var(--primary-blue); }

        .menu-toggle { display: none; font-size: 1.5rem; cursor: pointer; }

        /* --- HERO SLIDER (Gambar Konstruksi) --- */
        .hero-slider {
            position: relative;
            height: 90vh;
            margin-top: 80px; /* Height of header */
            overflow: hidden;
        }

        .slide {
            position: absolute;
            top: 0; left: 0; width: 100%; height: 100%;
            background-size: cover;
            background-position: center;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            color: var(--white);
            opacity: 0;
            transition: opacity 1s ease-in-out;
        }
        
        .slide.active { opacity: 1; }
        .slide::before { content: ''; position: absolute; top:0; left:0; width:100%; height:100%; background: rgba(0,0,0,0.5); }
        
        .slide-content { position: relative; z-index: 2; max-width: 800px; padding: 20px; }
        .slide-title { font-size: 3rem; font-weight: 700; margin-bottom: 20px; text-transform: uppercase; letter-spacing: 1px; }
        .slide-text { font-size: 1.2rem; margin-bottom: 30px; }

        /* --- PROFILE SECTION --- */
        .profile-section { background: var(--white); }
        .profile-grid { display: flex; align-items: center; gap: 50px; }
        .profile-img img { border-radius: 10px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); border-bottom: 5px solid var(--primary-green); }
        .profile-content h3 { color: var(--primary-blue); margin-bottom: 15px; }
        .SEO-highlight { font-weight: bold; color: var(--primary-red); }

        /* --- KEUNGGULAN (8 Cards) --- */
        .advantages-section { background: var(--section-bg); position: relative; }
        .advantages-section::before { content:''; position:absolute; top:0; left:0; width:100%; height: 5px; background: var(--grad-main); }
        .cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 30px;
        }
        .card {
            background: var(--white);
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
            text-align: center;
            transition: 0.3s;
            border-top: 3px solid transparent;
        }
        .card:hover { transform: translateY(-10px); border-top: 3px solid var(--primary-yellow); }
        .card-icon { font-size: 3rem; margin-bottom: 20px; background: var(--grad-accent); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .card h4 { margin-bottom: 15px; font-weight: 600; }

        /* --- LAYANAN (Services List) --- */
        .services-container { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 30px; }
        .service-box { background: var(--white); padding: 30px; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.05); border-left: 4px solid var(--primary-blue); }
        .service-box h3 { margin-bottom: 20px; display: flex; align-items: center; gap: 10px; color: var(--primary-blue); }
        .service-list li { margin-bottom: 10px; padding-left: 25px; position: relative; }
        .service-list li::before { content: '\f00c'; font-family: 'Font Awesome 6 Free'; font-weight: 900; color: var(--primary-green); position: absolute; left: 0; }

        /* --- PRICING --- */
        .pricing-grid { display: flex; justify-content: center; gap: 30px; flex-wrap: wrap; }
        .price-card { background: var(--white); padding: 40px; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); text-align: center; flex: 1; min-width: 280px; max-width: 350px; position: relative; overflow: hidden; }
        .price-card.popular { transform: scale(1.05); border: 2px solid var(--primary-yellow); }
        .price-header h4 { font-size: 1.5rem; margin-bottom: 15px; }
        .price { font-size: 2.5rem; font-weight: 700; color: var(--primary-blue); margin-bottom: 30px; }
        .price small { font-size: 1rem; font-weight: 400; color: #666; }
        
        /* --- PAYMENT METHODS BANNER --- */
        .payment-banner { background: #f4f4f4; padding: 30px 0; }
        .payment-logos { display: flex; justify-content: center; align-items: center; flex-wrap: wrap; gap: 30px; }
        .payment-item { font-weight: 700; color: #555; font-size: 1.2rem; display: flex; align-items: center; gap: 10px;}
        .payment-item i { font-size: 2rem; color: var(--primary-blue); }

        /* --- TESTIMONIAL SLIDER --- */
        .testimonial-section { background: var(--grad-main); color: var(--white); }
        .testimonial-section .section-title { background: none; -webkit-text-fill-color: var(--white); color: var(--white); }
        .testimonial-section .section-subtitle { color: rgba(255,255,255,0.8); }
        
        .testimonial-slider-container {
            overflow-x: auto;
            display: flex;
            gap: 20px;
            padding-bottom: 20px;
            scroll-snap-type: x mandatory;
            -webkit-overflow-scrolling: touch;
            scrollbar-width: none; /* Firefox */
        }
        .testimonial-slider-container::-webkit-scrollbar { display: none; /* Chrome/Safari */ }

        .testimonial-card {
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(10px);
            padding: 30px;
            border-radius: 15px;
            min-width: 300px;
            flex: 0 0 auto;
            scroll-snap-align: center;
        }
        .testi-stars { color: var(--primary-yellow); margin-bottom: 15px; }
        .testi-text { font-style: italic; margin-bottom: 20px; }
        .testi-user { font-weight: 700; }

        /* --- GOOGLE REVIEWS (Static Simulation) --- */
        .google-reviews-box { background: var(--white); padding: 30px; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); max-width: 800px; margin: 0 auto; }
        .g-header { display: flex; align-items: center; gap: 15px; margin-bottom: 20px; }
        .g-logo { width: 40px; }
        .g-rating { color: var(--primary-yellow); }

        /* --- CTA BANNERS --- */
        .cta-section { padding: 60px 0; text-align: center; color: var(--white); }
        .cta-agen { background: linear-gradient(rgba(0,0,0,0.7), rgba(0,0,0,0.7)), url('https://source.unsplash.com/1200x400/?logistics,delivery'); background-size: cover; background-attachment: fixed; }
        .cta-marketplace { background: var(--grad-accent); }
        .cta-title { font-size: 2rem; margin-bottom: 20px; }

        /* --- Q&A DROPDOWN (Accordion) --- */
        .qa-section { background: #f9f9f9; }
        .accordion-item { background: var(--white); border-radius: 8px; margin-bottom: 15px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); overflow: hidden; }
        .accordion-header { padding: 20px; cursor: pointer; display: flex; justify-content: space-between; align-items: center; font-weight: 600; transition: 0.3s; }
        .accordion-header:hover { background: #f0f0f0; color: var(--primary-blue); }
        .accordion-header i { transition: 0.3s; }
        .accordion-header.active i { transform: rotate(180deg); }
        .accordion-body { padding: 0 20px; max-height: 0; overflow: hidden; transition: max-height 0.3s ease-out; }
        .accordion-body p { padding-bottom: 20px; color: #555; }

        /* --- FOOTER --- */
        .footer { background: #1a1a1a; color: #aaa; padding: 80px 0 30px; position: relative; }
        .footer::before { content:''; position:absolute; top:0; left:0; width:100%; height: 5px; background: var(--grad-accent); }
        .footer-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 40px; margin-bottom: 60px; }
        .footer-about p { margin-bottom: 20px; }
        .footer-title { color: var(--white); font-size: 1.2rem; margin-bottom: 25px; position: relative; padding-bottom: 10px; }
        .footer-title::after { content: ''; position: absolute; bottom: 0; left: 0; width: 50px; height: 2px; background: var(--primary-yellow); }
        .footer-links li { margin-bottom: 12px; }
        .footer-links a { transition: 0.3s; }
        .footer-links a:hover { color: var(--primary-yellow); padding-left: 5px; }
        .contact-info li { display: flex; gap: 15px; margin-bottom: 15px; }
        .contact-info i { color: var(--primary-blue); margin-top: 5px; }
        .map-embed iframe { width: 100%; height: 250px; border-radius: 10px; border: none; filter: grayscale(20%); transition: 0.3s; }
        .map-embed iframe:hover { filter: grayscale(0%); }
        .footer-bottom { text-align: center; padding-top: 30px; border-top: 1px solid #333; font-size: 0.9rem; }

        /* --- FLOATING BUTTONS --- */
        .floating-btns { position: fixed; bottom: 30px; right: 30px; z-index: 999; display: flex; flex-direction: column; gap: 15px; }
        .float-btn { width: 50px; height: 50px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: var(--white); font-size: 1.2rem; box-shadow: 0 5px 15px rgba(0,0,0,0.2); transition: 0.3s; cursor: pointer; opacity: 0; visibility: hidden; }
        .float-btn.show { opacity: 1; visibility: visible; }
        .btn-wa { background: #25D366; }
        .btn-top { background: var(--primary-blue); }
        .float-btn:hover { transform: scale(1.1); }

        /* --- RESPONSIVE --- */
        @media (max-width: 768px) {
            .menu-toggle { display: block; }
            .nav-links { position: fixed; top: 80px; left: -100%; width: 100%; background: var(--white); flex-direction: column; padding: 20px 0; transition: 0.4s; box-shadow: 0 5px 10px rgba(0,0,0,0.1); }
            .nav-links.active { left: 0; }
            .nav-item { width: 100%; text-align: center; }
            .dropdown-menu { position: static; box-shadow: none; border-top: none; background: #f9f9f9; display: none; opacity: 1; visibility: visible; transform: none; }
            .nav-item:hover .dropdown-menu { display: block; }
            
            .hero-slider { height: 70vh; }
            .slide-title { font-size: 2rem; }
            .profile-grid { flex-direction: column; }
            .section-title { font-size: 2rem; }
            .price-card.popular { transform: scale(1); }
            .floating-btns { bottom: 20px; right: 20px; }
        }
    </style>
</head>
<body>

    <header class="header">
        <div class="container navbar">
            <a href="#" class="logo">
                <img src="https://tokosancaka.com/storage/uploads/logo.jpeg" alt="Sancaka Logo">
            </a>
            <div class="menu-toggle" id="mobile-menu">
                <i class="fas fa-bars"></i>
            </div>
            <ul class="nav-links">
                <li class="nav-item"><a href="#beranda" class="nav-link">Beranda</a></li>
                <li class="nav-item"><a href="#tentang" class="nav-link">Tentang Kami</a></li>
                <li class="nav-item">
                    <a href="#layanan" class="nav-link">Layanan <i class="fas fa-chevron-down" style="font-size: 0.8rem;"></i></a>
                    <ul class="dropdown-menu">
                        <li><a href="#legalitas" class="dropdown-item">Perizinan & Legalitas</a></li>
                        <li><a href="#konstruksi" class="dropdown-item">Konstruksi & Arsitek</a></li>
                        <li><a href="#ekspedisi" class="dropdown-item">Ekspedisi & Logistik</a></li>
                    </ul>
                </li>
                <li class="nav-item"><a href="#harga" class="nav-link">Harga</a></li>
                <li class="nav-item"><a href="#testimoni" class="nav-link">Testimoni</a></li>
                <li class="nav-item"><a href="#faq" class="nav-link">Q&A</a></li>
                <li class="nav-item"><a href="#kontak" class="btn btn-grad" style="padding: 8px 25px; margin-top: 5px;">Hubungi Kami</a></li>
            </ul>
        </div>
    </header>

    <section id="beranda" class="hero-slider">
        <div class="slide active" style="background-image: url('https://source.unsplash.com/1600x900/?construction,building');">
            <div class="slide-content">
                <h1 class="slide-title">Membangun Masa Depan Bisnis Anda</h1>
                <p class="slide-text">Solusi Satu Pintu: Jasa Konstruksi Profesional, Pengurusan Perizinan Kilat, dan Logistik Terpercaya di Ngawi.</p>
                <a href="https://wa.me/6285745808809?text=Halo%20Sancaka,%20saya%20tertarik%20dengan%20jasa%20Anda." class="btn btn-grad">KONSULTASI GRATIS SEKARANG</a>
            </div>
        </div>
        <div class="slide" style="background-image: url('https://source.unsplash.com/1600x900/?architect,blueprint');">
            <div class="slide-content">
                <h1 class="slide-title">Desain Arsitek Impian & Legalitas Terjamin</h1>
                <p class="slide-text">Wujudkan bangunan impian Anda dengan desain 2D/3D presisi dan IMB/PBG yang kami urus hingga tuntas.</p>
                <a href="#layanan" class="btn btn-accent">LIHAT LAYANAN KAMI</a>
            </div>
        </div>
        <div class="slide" style="background-image: url('https://source.unsplash.com/1600x900/?logistics,truck');">
            <div class="slide-content">
                <h1 class="slide-title">Kirim Paket Tanpa Batas</h1>
                <p class="slide-text">Hubungkan bisnis Anda ke seluruh Indonesia dengan jaringan ekspedisi terlengkap: JNT, JNE, POS, Lion Parcel, dan banyak lagi.</p>
            </div>
        </div>
    </section>

    <section id="tentang" class="section-padding profile-section">
        <div class="container">
            <div class="profile-grid">
                <div class="profile-img wow fadeInLeft">
                    <img src="https://tokosancaka.com/storage/uploads/logo.jpeg" alt="Profile CV Sancaka Karya Hutama" style="max-width: 400px;">
                </div>
                <div class="profile-content wow fadeInRight">
                    <h2 class="section-title">Tentang CV. Sancaka Karya Hutama</h2>
                    <h3>Mitra Terpercaya Pembangunan & Legalitas Bisnis di Ngawi</h3>
                    <p>Kami adalah perusahaan yang berdedikasi untuk memberikan solusi terintegrasi bagi kebutuhan bisnis dan properti Anda. Dengan pengalaman bertahun-tahun, <strong>CV. Sancaka Karya Hutama</strong> hadir sebagai jawaban atas kerumitan birokrasi perizinan dan kebutuhan konstruksi berkualitas tinggi di Jawa Timur, khususnya Ngawi.</p>
                    <p style="margin-top: 15px;">Sebagai <span class="SEO-highlight">Jasa Pengurusan Perizinan Termurah dan Tercepat di Ngawi</span>, kami menjamin legalitas usaha Anda seperti PT, CV, NIB, hingga sertifikat Halal dan BPOM tertangani secara profesional. Kami juga merupakan <span class="SEO-highlight">Kontraktor Bangunan Terpercaya</span> yang siap mewujudkan hunian atau tempat usaha impian (Toko, Alfamart/Indomart) dari nol hingga terima kunci.</p>
                    <a href="#kontak" class="btn btn-grad" style="margin-top: 25px;">Hubungi Tim Ahli Kami</a>
                </div>
            </div>
        </div>
    </section>

    <section class="section-padding advantages-section">
        <div class="container">
            <div class="text-center">
                <h2 class="section-title">Kenapa Memilih Sancaka?</h2>
                <p class="section-subtitle">8 Alasan Kuat Mengapa Kami Adalah Partner Terbaik Untuk Pertumbuhan Bisnis Anda.</p>
            </div>
            <div class="cards-grid">
                <div class="card">
                    <i class="fas fa-rocket card-icon"></i>
                    <h4>Proses Kilat & Transparan</h4>
                    <p>Kami memahami waktu adalah uang. Pengurusan perizinan dilakukan dengan jalur efisien tanpa biaya tersembunyi.</p>
                </div>
                <div class="card">
                    <i class="fas fa-handshake card-icon"></i>
                    <h4>Legalitas Terjamin 100%</h4>
                    <p>Semua dokumen yang kami urus dijamin keasliannya dan sesuai dengan peraturan perundang-undangan terbaru.</p>
                </div>
                <div class="card">
                    <i class="fas fa-users-cog card-icon"></i>
                    <h4>Tim Ahli & Profesional</h4>
                    <p>Didukung oleh arsitek bersertifikat, ahli hukum, dan teknisi sipil yang berpengalaman di bidangnya.</p>
                </div>
                <div class="card">
                    <i class="fas fa-tags card-icon"></i>
                    <h4>Harga Paling Kompetitif</h4>
                    <p>Penawaran harga terbaik di kelasnya dengan opsi paket yang fleksibel menyesuaikan budget Anda.</p>
                </div>
                <div class="card">
                    <i class="fas fa-building card-icon"></i>
                    <h4>Layanan Satu Pintu (All-in-One)</h4>
                    <p>Tidak perlu repot berpindah vendor. Dari desain, izin, bangun, hingga logistik, semua ada di sini.</p>
                </div>
                <div class="card">
                    <i class="fas fa-drafting-compass card-icon"></i>
                    <h4>Desain Arsitek Premium</h4>
                    <p>Gratis konsultasi desain 2D/3D yang estetis dan fungsional untuk setiap proyek pembangunan.</p>
                </div>
                <div class="card">
                    <i class="fas fa-shipping-fast card-icon"></i>
                    <h4>Jaringan Logistik Luas</h4>
                    <p>Bekerjasama dengan semua ekspedisi besar di Indonesia untuk memastikan paket Anda sampai tepat waktu.</p>
                </div>
                <div class="card">
                    <i class="fas fa-headset card-icon"></i>
                    <h4>Support Responsif 24/7</h4>
                    <p>Layanan pelanggan yang ramah dan siap membantu konsultasi kapanpun Anda butuhkan via WhatsApp.</p>
                </div>
            </div>
        </div>
    </section>

    <section id="layanan" class="section-padding">
        <div class="container">
            <div class="text-center">
                <h2 class="section-title">Layanan Unggulan Kami</h2>
                <p class="section-subtitle">Cakupan layanan luas untuk memenuhi segala kebutuhan legalitas dan infrastruktur bisnis Anda.</p>
            </div>
            
            <div class="services-container">
                <div class="service-box" id="legalitas">
                    <h3><i class="fas fa-file-contract"></i> Perizinan & Legalitas Badan Usaha</h3>
                    <ul class="service-list">
                        <li>Pendirian PT & CV Lengkap (Akta, SK Kemenkumham)</li>
                        <li>Pendirian Yayasan & Perkumpulan</li>
                        <li>Pengurusan NIB (Nomor Induk Berusaha) OSS RBA</li>
                        <li>Izin Usaha Mikro Kecil (IUMK)</li>
                        <li>Sertifikasi Halal MUI & BPOM</li>
                        <li>Pendaftaran Hak Merk & Paten (HAKI)</li>
                        <li>Izin PIRT (Pangan Industri Rumah Tangga)</li>
                    </ul>
                </div>

                 <div class="service-box" id="konstruksi">
                    <h3><i class="fas fa-hard-hat"></i> Konstruksi & Perizinan Bangunan</h3>
                    <ul class="service-list">
                        <li>Jasa Kontraktor Bangun Rumah, Toko, Ruko</li>
                        <li>Spesialis Bangun Alfamart & Indomart Standar</li>
                        <li>Pengurusan PBG (Persetujuan Bangunan Gedung) / IMB</li>
                        <li>Pengurusan SLF (Sertifikat Laik Fungsi)</li>
                        <li>Jasa Hitung RAB (Rencana Anggaran Biaya) Detail</li>
                        <li>Desain Gambar Arsitek Denah 2D & Visual 3D</li>
                        <li>Pengeboran Sumur Dalam (SIPA)</li>
                    </ul>
                </div>

                 <div class="service-box" id="ekspedisi">
                    <h3><i class="fas fa-truck-fast"></i> Ekspedisi & Layanan Digital</h3>
                    <ul class="service-list">
                        <li><strong>Agen Multi Ekspedisi Lengkap:</strong></li>
                        <li>Lion Parcel, J&T Express, J&T Cargo</li>
                        <li>JNE, POS Indonesia, SiCepat, Anteraja</li>
                        <li>Indah Cargo, SAP Express, ID Express</li>
                        <li>Jasa Pembuatan Website Company Profile & Toko Online</li>
                        <li>Konsultasi Digital Marketing SEO</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <section id="harga" class="section-padding" style="background: #f4f7f6;">
        <div class="container">
            <div class="text-center">
                <h2 class="section-title">Penawaran Harga Spesial</h2>
                <p class="section-subtitle">Investasi terbaik untuk legalitas dan masa depan bisnis Anda dengan harga transparan.</p>
            </div>
            <div class="pricing-grid">
                <div class="price-card">
                    <div class="price-header">
                        <h4>Paket Basic Legalitas</h4>
                    </div>
                    <div class="price">
                        <small>Mulai dari</small><br>Rp 2.500.000
                    </div>
                    <ul class="service-list" style="text-align: left;">
                        <li>Pendirian CV Lengkap</li>
                        <li>NIB OSS RBA</li>
                        <li>NPWP Badan</li>
                        <li>Konsultasi Gratis</li>
                    </ul>
                    <a href="https://wa.me/6285745808809?text=Saya%20tertarik%20Paket%20Basic%20Rp2.5jt" class="btn btn-grad" style="margin-top: 20px; width: 100%;">Pilih Paket</a>
                </div>
                
                <div class="price-card popular">
                    <div style="position: absolute; top: 0; right: 0; background: var(--primary-yellow); color: #333; padding: 5px 15px; font-size: 0.8rem; font-weight: bold;">PALING LARIS</div>
                    <div class="price-header">
                        <h4>Paket PT & Perizinan</h4>
                    </div>
                    <div class="price">
                        <small>Mulai dari</small><br>Rp 5.000.000
                    </div>
                    <ul class="service-list" style="text-align: left;">
                        <li>Pendirian PT Perorangan/Biasa</li>
                        <li>Akta & SK Kemenkumham</li>
                        <li>NIB, NPWP, Akun OSS</li>
                        <li>Bonus Desain Logo/Kop Surat</li>
                    </ul>
                    <a href="https://wa.me/6285745808809?text=Saya%20tertarik%20Paket%20PT%20Rp5jt" class="btn btn-grad" style="margin-top: 20px; width: 100%;">Pilih Paket</a>
                </div>

                <div class="price-card">
                    <div class="price-header">
                        <h4>Paket Konstruksi & IMB</h4>
                    </div>
                    <div class="price">
                        <small>Mulai dari</small><br>Rp 15.000.000
                    </div>
                    <ul class="service-list" style="text-align: left;">
                        <li>Pengurusan PBG/IMB Rumah Tinggal</li>
                        <li>Gambar Arsitek 2D & 3D Lengkap</li>
                        <li>Perhitungan RAB Detail</li>
                        <li>Survei Lokasi Gratis</li>
                    </ul>
                    <a href="https://wa.me/6285745808809?text=Saya%20tertarik%20Paket%20Konstruksi%20Rp15jt" class="btn btn-grad" style="margin-top: 20px; width: 100%;">Pilih Paket</a>
                </div>
            </div>
             <p class="text-center" style="margin-top: 30px; color: #666;"><em>*Harga dapat berubah menyesuaikan kompleksitas proyek dan lokasi. Hubungi kami untuk penawaran custom.</em></p>
        </div>
    </section>

    <div class="payment-banner">
        <div class="container">
            <h4 class="text-center" style="margin-bottom: 20px; color: #555;">Metode Pembayaran Lengkap & Aman</h4>
            <div class="payment-logos">
                <div class="payment-item"><i class="fas fa-qrcode"></i> QRIS</div>
                <div class="payment-item"><i class="fas fa-university"></i> BANK BCA</div>
                <div class="payment-item"><i class="fas fa-university"></i> BANK BRI</div>
                <div class="payment-item"><i class="fas fa-university"></i> BANK MANDIRI</div>
                <div class="payment-item"><i class="fas fa-money-check-alt"></i> ALL BANK TRANSFER</div>
                <div class="payment-item"><i class="fas fa-wallet"></i> E-WALLET (OVO, DANA, GOPAY)</div>
            </div>
        </div>
    </div>

    <section id="testimoni" class="section-padding testimonial-section">
        <div class="container">
            <div class="text-center">
                <h2 class="section-title">Kata Mereka Tentang Sancaka</h2>
                <p class="section-subtitle">Kepuasan klien adalah prioritas utama kami. Berikut adalah pengalaman nyata mereka.</p>
            </div>
            
            <div class="testimonial-slider-container">
                <div class="testimonial-card">
                    <div class="testi-stars"><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i></div>
                    <p class="testi-text">"Luar biasa! Urus PT dan NIB di Sancaka beneran kilat. Gak pake ribet, timnya suportif banget jelasin detailnya. Recommended buat pengusaha Ngawi!"</p>
                    <p class="testi-user">- Bpk. Hartono, Pengusaha Kuliner</p>
                </div>
                <div class="testimonial-card">
                    <div class="testi-stars"><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i></div>
                    <p class="testi-text">"Bangun ruko pake jasa kontraktor Sancaka hasilnya rapih banget. Sesuai RAB dan desain 3D nya. PBG juga diurusin sekalian. Terima kasih!"</p>
                    <p class="testi-user">- Ibu Siti Aminah, Pemilik Ruko</p>
                </div>
                <div class="testimonial-card">
                    <div class="testi-stars"><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i></div>
                    <p class="testi-text">"Sebagai seller online, kebantu banget sama layanan ekspedisinya. Bisa pilih banyak kurir di satu tempat. Sukses terus Sancaka Pos."</p>
                    <p class="testi-user">- Andi, Online Seller</p>
                </div>
                <div class="testimonial-card">
                    <div class="testi-stars"><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i></div>
                    <p class="testi-text">"Urus sertifikat halal ternyata mudah kalau sama ahlinya. Pendampingannya mantap sampai sertifikat keluar."</p>
                    <p class="testi-user">- CV. Berkah Pangan</p>
                </div>
                 <div class="testimonial-card">
                    <div class="testi-stars"><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i></div>
                    <p class="testi-text">"Pelayanan ramah, harga sangat bersaing dibanding biro jasa lain di Ngawi. Transparan sekali soal biaya."</p>
                    <p class="testi-user">- Mas Dimas, Startup Founder</p>
                </div>
                 <div class="testimonial-card">
                    <div class="testi-stars"><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i></div>
                    <p class="testi-text">"Desain arsiteknya keren abis! Revisi dilayani dengan sabar sampai kita bener-bener sreg sama gambarnya."</p>
                    <p class="testi-user">- Keluarga Bpk. Rahmat</p>
                </div>
                 <div class="testimonial-card">
                    <div class="testi-stars"><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i></div>
                    <p class="testi-text">"Profesionalisme tingkat tinggi. Semua janji ditepati, pengerjaan SIPA sumur bor berjalan lancar."</p>
                    <p class="testi-user">- PT. Agro Jaya</p>
                </div>
                 <div class="testimonial-card">
                    <div class="testi-stars"><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i></div>
                    <p class="testi-text">"Solusi terbaik buat yang mau buka Alfamart tapi bingung mulai darimana. Sancaka bantu dari izin sampai bangun."</p>
                    <p class="testi-user">- Ibu Yanti, Investor</p>
                </div>
                 <div class="testimonial-card">
                    <div class="testi-stars"><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i></div>
                    <p class="testi-text">"Respon admin WA nya cepet banget walaupun tanya-tanya di luar jam kerja. Sangat membantu."</p>
                    <p class="testi-user">- Pengguna Jasa PIRT</p>
                </div>
                 <div class="testimonial-card">
                    <div class="testi-stars"><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i></div>
                    <p class="testi-text">"Top Markotop! Jasa perizinan nomor 1 di Ngawi."</p>
                    <p class="testi-user">- Bpk. Eko</p>
                </div>
            </div>
        </div>
    </section>

    <section class="section-padding">
        <div class="container">
             <div class="google-reviews-box">
                <div class="g-header">
                    <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/a/a9/Google_Maps_icon.svg/1200px-Google_Maps_icon.svg.png" alt="Google Maps" class="g-logo">
                    <div>
                        <h4 style="margin: 0;">Review di Google Maps</h4>
                        <div class="g-rating">
                            <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star-half-alt"></i>
                            <span style="color: #555; font-weight: bold;">4.9 / 5.0</span> <span style="color: #999; font-size: 0.9rem;">(Berdasarkan ulasan pelanggan)</span>
                        </div>
                    </div>
                </div>
                <p style="font-style: italic; color: #555;">"Cek lokasi kami di Google Maps dan lihat lebih banyak ulasan positif dari pelanggan yang telah membuktikan kualitas layanan CV. Sancaka Karya Hutama."</p>
                <a href="https://goo.gl/maps/YourMapLinkHere" target="_blank" style="color: var(--primary-blue); font-weight: 600; margin-top: 10px; display: inline-block;">Lihat Semua Review di Google Maps <i class="fas fa-external-link-alt"></i></a>
            </div>
        </div>
    </section>

    <section class="cta-section cta-agen">
        <div class="container">
            <h2 class="cta-title">PELUANG BISNIS MENJANJIKAN!</h2>
            <p style="font-size: 1.2rem; margin-bottom: 30px; max-width: 800px; margin-left: auto; margin-right: auto;">Jadilah bagian dari jaringan kami. Buka keagenan ekspedisi (Multi Kurir) dan loket PPOB di lokasi Anda dengan modal terjangkau dan potensi keuntungan jutaan rupiah.</p>
            <a href="https://wa.me/6285745808809?text=Saya%20tertarik%20jadi%20Agen%20Ekspedisi%20Sancaka" class="btn btn-grad">DAFTAR JADI AGEN SEKARANG</a>
        </div>
    </section>

    <section class="cta-section cta-marketplace">
        <div class="container">
            <h2 class="cta-title" style="color: var(--text-dark);">TINGKATKAN PENJUALAN ANDA!</h2>
            <p style="color: var(--text-dark); font-size: 1.2rem; margin-bottom: 30px;">Bergabunglah dengan ribuan seller lain dan pasarkan produk Anda di Marketplace Toko Sancaka. Jangkauan luas, mudah digunakan.</p>
            <a href="https://tokosancaka.com/etalase" target="_blank" class="btn btn-grad">MULAI JUALAN DI MARKETPLACE</a>
        </div>
    </section>

    <section id="faq" class="section-padding qa-section">
        <div class="container">
            <div class="text-center">
                <h2 class="section-title">Pertanyaan yang Sering Diajukan (Q&A)</h2>
                <p class="section-subtitle">Jawaban cepat atas pertanyaan umum seputar layanan perizinan dan konstruksi kami.</p>
            </div>
            <div style="max-width: 800px; margin: 0 auto;">
                <div class="accordion-item">
                    <div class="accordion-header">
                        Berapa lama proses pembuatan PT atau CV?
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="accordion-body">
                        <p>Untuk CV dan PT Perorangan, proses bisa selesai dalam 1-3 hari kerja setelah data lengkap. Untuk PT Biasa, estimasi 7-14 hari kerja tergantung kelengkapan dokumen dan proses di Kemenkumham.</p>
                    </div>
                </div>
                <div class="accordion-item">
                    <div class="accordion-header">
                        Apakah biaya bangun rumah sudah termasuk desain arsitek?
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="accordion-body">
                        <p>Ya, jika Anda menggunakan jasa konstruksi kami (borongan), kami memberikan fasilitas GRATIS desain arsitek (Denah 2D dan Visual 3D) serta perhitungan RAB.</p>
                    </div>
                </div>
                <div class="accordion-item">
                    <div class="accordion-header">
                        Ekspedisi apa saja yang tersedia di Sancaka Pos?
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="accordion-body">
                        <p>Kami adalah agen multi-ekspedisi. Anda bisa mengirim paket via J&T, JNE, POS Indonesia, Lion Parcel, SiCepat, Anteraja, ID Express, SAP Express, dan Indah Cargo dalam satu lokasi.</p>
                    </div>
                </div>
                <div class="accordion-item">
                    <div class="accordion-header">
                        Apakah melayani pengurusan IMB/PBG untuk bangunan lama?
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="accordion-body">
                        <p>Tentu. Kami melayani pengurusan PBG (Persetujuan Bangunan Gedung) baik untuk rencana pembangunan baru maupun pemutihan bangunan yang sudah berdiri (SLF - Sertifikat Laik Fungsi).</p>
                    </div>
                </div>
                 <div class="accordion-item">
                    <div class="accordion-header">
                        Bagaimana sistem pembayarannya?
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="accordion-body">
                        <p>Kami menerima pembayaran via Transfer Bank (BCA, BRI, Mandiri, dll), QRIS, dan E-Wallet. Untuk jasa konstruksi dan perizinan nominal besar, pembayaran dapat dilakukan bertahap (termin) sesuai kesepakatan di kontrak.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <footer id="kontak" class="footer">
        <div class="container">
            <div class="footer-grid">
                <div class="footer-about">
                    <img src="https://tokosancaka.com/storage/uploads/logo.jpeg" alt="Sancaka Logo White" style="height: 50px; margin-bottom: 20px; filter: brightness(0) invert(1);">
                    <h4 style="color: var(--white); margin-bottom: 15px;">CV. SANCAKA KARYA HUTAMA</h4>
                    <p>Partner profesional Anda dalam jasa perizinan, legalitas bisnis, kontraktor bangunan, dan logistik terpadu di Ngawi, Jawa Timur.</p>
                    <ul class="contact-info">
                        <li>
                            <i class="fas fa-map-marker-alt"></i>
                            <span>JL. DR. WAHIDIN NO.18A RT.22 RW.05, KEL. KETANGGI, KEC. NGAWI, KAB. NGAWI, JAWA TIMUR 63211</span>
                        </li>
                        <li>
                            <i class="fab fa-whatsapp"></i>
                            <span>0857-4580-8809 (WA Fast Respon)</span>
                        </li>
                    </ul>
                </div>

                <div class="footer-links">
                    <h4 class="footer-title">Link Cepat</h4>
                    <ul>
                        <li><a href="#beranda">Beranda</a></li>
                        <li><a href="#tentang">Tentang Kami</a></li>
                        <li><a href="#legalitas">Jasa Perizinan</a></li>
                        <li><a href="#konstruksi">Jasa Konstruksi</a></li>
                        <li><a href="#ekspedisi">Cek Ongkir Ekspedisi</a></li>
                        <li><a href="https://tokosancaka.com/etalase" target="_blank">Marketplace Sancaka</a></li>
                    </ul>
                </div>

                <div class="footer-links">
                    <h4 class="footer-title">Layanan Populer</h4>
                    <ul>
                        <li><a href="#">Urus Pendirian PT Ngawi</a></li>
                        <li><a href="#">Jasa Kontraktor Rumah Ngawi</a></li>
                        <li><a href="#">Biro Jasa IMB/PBG Cepat</a></li>
                        <li><a href="#">Sertifikasi Halal & BPOM</a></li>
                        <li><a href="#">Agen Lion Parcel Ngawi</a></li>
                        <li><a href="#">Desain Arsitek Murah</a></li>
                    </ul>
                </div>

                <div class="footer-map">
                    <h4 class="footer-title">Lokasi Kami</h4>
                    <div class="map-embed">
                        <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3956.272781108874!2d111.4454506!3d-7.3929826!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2e79e79314052f63%3A0x1bf49943d0caef65!2sJl.%20Dr.%20Wahidin%20No.18A%2C%20RT.22%2FRW.05%2C%20Sidomakmur%2C%20Ketanggi%2C%20Kec.%20Ngawi%2C%20Kabupaten%20Ngawi%2C%20Jawa%20Timur%2063211!5e0!3m2!1sid!2sid!4v1700000000000!5m2!1sid!2sid" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                    </div>
                </div>
            </div>

            <div class="payment-banner" style="background: transparent; border-top: 1px solid #333; border-bottom: 1px solid #333; margin-bottom: 30px; padding: 20px 0;">
                <h5 class="text-center" style="color: #aaa; margin-bottom: 15px;">Didukung Pembayaran Digital Lengkap</h5>
                <div class="payment-logos" style="color: #aaa; justify-content: center; gap: 25px;">
                    <div class="payment-item" style="color: #aaa;"><i class="fas fa-qrcode" style="color: #aaa;"></i> QRIS</div>
                    <div class="payment-item" style="color: #aaa;"><i class="fas fa-university" style="color: #aaa;"></i> BANK BCA</div>
                    <div class="payment-item" style="color: #aaa;"><i class="fas fa-university" style="color: #aaa;"></i> BANK BRI</div>
                    <div class="payment-item" style="color: #aaa;"><i class="fas fa-university" style="color: #aaa;"></i> MANDIRI</div>
                    <div class="payment-item" style="color: #aaa;"><i class="fas fa-wallet" style="color: #aaa;"></i> E-WALLET</div>
                </div>
            </div>

            <div class="footer-bottom">
                <p>&copy; 2024 CV. Sancaka Karya Hutama. All Rights Reserved. | Website Profesional & Jasa SEO Ngawi.</p>
            </div>
        </div>
    </footer>

    <div class="floating-btns">
        <a href="https://wa.me/6285745808809?text=Halo%20Admin%20Sancaka,%20saya%20mau%20konsultasi." class="float-btn btn-wa show" target="_blank" title="Hubungi via WhatsApp">
            <i class="fab fa-whatsapp" style="font-size: 1.8rem;"></i>
        </a>
        <div class="float-btn btn-top" id="scrollTopBtn" title="Kembali ke Atas">
            <i class="fas fa-arrow-up"></i>
        </div>
    </div>


    <script>
        // Mobile Menu Toggle
        const mobileMenuBtn = document.getElementById('mobile-menu');
        const navLinks = document.querySelector('.nav-links');

        mobileMenuBtn.addEventListener('click', () => {
            navLinks.classList.toggle('active');
        });

        // Close menu when clicking a link (for single page navigation)
        document.querySelectorAll('.nav-link').forEach(link => {
            link.addEventListener('click', () => {
                if (navLinks.classList.contains('active')) {
                    navLinks.classList.remove('active');
                }
            });
        });

        // Hero Slider Script (Simple Auto-fade)
        let currentSlide = 0;
        const slides = document.querySelectorAll('.slide');
        const totalSlides = slides.length;

        function nextSlide() {
            slides[currentSlide].classList.remove('active');
            currentSlide = (currentSlide + 1) % totalSlides;
            slides[currentSlide].classList.add('active');
        }
        // Ganti slide setiap 5 detik
        setInterval(nextSlide, 5000);

        // Accordion (FAQ) Script
        const accordionHeaders = document.querySelectorAll('.accordion-header');

        accordionHeaders.forEach(header => {
            header.addEventListener('click', () => {
                const activeHeader = document.querySelector('.accordion-header.active');
                if (activeHeader && activeHeader !== header) {
                    activeHeader.classList.remove('active');
                    activeHeader.nextElementSibling.style.maxHeight = null;
                }

                header.classList.toggle('active');
                const body = header.nextElementSibling;
                if (header.classList.contains('active')) {
                    body.style.maxHeight = body.scrollHeight + "px";
                } else {
                    body.style.maxHeight = null;
                }
            });
        });

        // Scroll to Top Button Script
        const scrollTopBtn = document.getElementById('scrollTopBtn');

        window.onscroll = function() {
            if (document.body.scrollTop > 300 || document.documentElement.scrollTop > 300) {
                scrollTopBtn.classList.add('show');
            } else {
                scrollTopBtn.classList.remove('show');
            }
        };

        scrollTopBtn.addEventListener('click', function() {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });

        // Smooth Scroll for Anchor Links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const targetId = this.getAttribute('href');
                if (targetId === '#') return;
                const targetElement = document.querySelector(targetId);
                if (targetElement) {
                    const headerOffset = 80;
                    const elementPosition = targetElement.getBoundingClientRect().top;
                    const offsetPosition = elementPosition + window.pageYOffset - headerOffset;

                    window.scrollTo({
                        top: offsetPosition,
                        behavior: 'smooth'
                    });
                }
            });
        });
    </script>
</body>
</html>