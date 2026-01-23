<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="google-site-verification" content="M5GwyjoDoCcRA93IrehnwMAWLPXZPP2HNPMYU8pnIk8" />
    <title>Sancaka Express - Kirim Paket Murah & Cepat Ngawi | Partner Resmi SPX, J&T, JNE</title>

    <meta name="description" content="Agen Ekspedisi Termurah Ngawi mulai Rp 2.424! Partner resmi SPX Express, J&T, JNE, Lion Parcel. Melayani Jasa Konstruksi & Perizinan. Cek Ongkir Disini!">
    <meta name="keywords" content="Sancaka Express, SPX Ngawi, J&T Ngawi, Ongkir Murah Shopee, Ekspedisi Termurah, Cargo Ngawi, Jasa Kirim Paket, Konstruksi Bangunan Ngawi, CV Sancaka Karya Hutama">
    <meta name="author" content="Sancaka Team">

    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        /* --- VARIABLE & RESET (Shopee/SPX Style) --- */
        :root {
            --shopee-orange: #ee4d2d; /* Warna Khas Shopee */
            --shopee-dark-orange: #d73211;
            --text-black: #333333;
            --text-grey: #757575;
            --bg-light: #f5f5f5;
            --white: #ffffff;
            --border-color: #e8e8e8;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Roboto', sans-serif; background-color: var(--bg-light); color: var(--text-black); overflow-x: hidden; }
        a { text-decoration: none; color: inherit; transition: 0.2s; }
        ul { list-style: none; }

        /* --- UTILITY CLASSES --- */
        .container { max-width: 1200px; margin: 0 auto; padding: 0 15px; }
        .text-orange { color: var(--shopee-orange); }
        .bg-white { background: var(--white); }
        .btn-shopee {
            background: var(--shopee-orange);
            color: var(--white);
            padding: 10px 20px;
            border-radius: 4px;
            border: none;
            font-weight: 500;
            cursor: pointer;
            box-shadow: 0 2px 5px rgba(238, 77, 45, 0.2);
            display: inline-block;
        }
        .btn-shopee:hover { background: var(--shopee-dark-orange); }

        /* --- HEADER & NAVIGATION --- */
        header {
            background: var(--shopee-orange); /* Gradient khas Shopee */
            background: linear-gradient(-180deg, #f53d2d, #f63);
            color: var(--white);
            position: sticky;
            top: 0;
            z-index: 1000;
            padding-bottom: 10px;
        }

        .top-bar {
            font-size: 0.8rem;
            padding: 5px 0;
            display: flex;
            justify-content: space-between;
            border-bottom: 1px solid rgba(255,255,255,0.2);
        }

        .main-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
        }

        .logo { font-size: 1.8rem; font-weight: 700; color: var(--white); display: flex; align-items: center; gap: 10px; }
        .logo i { font-size: 2rem; }

        /* MENU & DROPDOWN */
        .nav-menu { display: flex; gap: 20px; align-items: center; }
        .nav-item { position: relative; font-weight: 500; font-size: 1rem; cursor: pointer; }
        .nav-item > a:hover { color: rgba(255,255,255,0.8); }

        .dropdown {
            position: absolute;
            top: 100%;
            left: 0;
            background: var(--white);
            color: var(--text-black);
            min-width: 200px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border-radius: 2px;
            display: none;
            flex-direction: column;
            padding: 5px 0;
            margin-top: 10px;
        }

        .dropdown::before {
            content: "";
            position: absolute;
            top: -8px; left: 10px;
            border-left: 8px solid transparent;
            border-right: 8px solid transparent;
            border-bottom: 8px solid var(--white);
        }

        .nav-item:hover .dropdown { display: flex; animation: fadeIn 0.3s; }
        .dropdown a { padding: 10px 15px; font-size: 0.9rem; }
        .dropdown a:hover { background: #fafafa; color: var(--shopee-orange); }

        /* --- HERO SLIDER (Gambar Konstruksi) --- */
        .hero-section {
            background: var(--white);
            padding: 20px 0;
            box-shadow: 0 1px 2px rgba(0,0,0,0.05);
        }

        .slider-container {
            position: relative;
            width: 100%;
            height: 400px;
            overflow: hidden;
            border-radius: 5px;
        }

        .slide {
            position: absolute;
            width: 100%;
            height: 100%;
            opacity: 0;
            transition: opacity 1s ease;
            background-size: cover;
            background-position: center;
        }

        .slide.active { opacity: 1; }

        .slide-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            background: linear-gradient(transparent, rgba(0,0,0,0.8));
            padding: 40px 20px;
            color: var(--white);
        }

        /* --- LAYANAN EKSPEDISI (Grid Style) --- */
        .section-title {
            text-align: center;
            font-size: 1.8rem;
            color: var(--shopee-orange);
            margin: 40px 0 20px;
            text-transform: uppercase;
            font-weight: 700;
        }

        .brands-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-bottom: 40px;
        }

        .brand-card {
            background: var(--white);
            padding: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1px solid var(--border-color);
            border-radius: 2px;
            transition: 0.3s;
            height: 100px;
        }

        .brand-card:hover {
            border-color: var(--shopee-orange);
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .brand-card img { max-width: 100%; max-height: 60px; filter: grayscale(100%); transition: 0.3s; }
        .brand-card:hover img { filter: grayscale(0%); }

        /* --- HARGA SPESIAL (Price Tags) --- */
        .price-section {
            background: linear-gradient(180deg, #fff, #fef6f5);
            padding: 50px 0;
        }

        .price-cards {
            display: flex;
            justify-content: center;
            gap: 20px;
            flex-wrap: wrap;
        }

        .price-tag {
            background: var(--white);
            border: 1px solid var(--shopee-orange);
            border-radius: 8px;
            width: 250px;
            text-align: center;
            overflow: hidden;
            position: relative;
            transition: transform 0.3s;
        }

        .price-tag:hover { transform: translateY(-5px); box-shadow: 0 5px 15px rgba(238, 77, 45, 0.3); }

        .price-header {
            background: var(--shopee-orange);
            color: var(--white);
            padding: 15px;
            font-weight: bold;
            font-size: 1.2rem;
        }

        .price-amount {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--shopee-orange);
            padding: 20px 0 5px;
        }

        .price-desc { padding: 0 20px 20px; font-size: 0.9rem; color: var(--text-grey); }

        /* --- SEO TEXT & MARKETING --- */
        .seo-box {
            background: var(--white);
            padding: 40px;
            margin: 40px 0;
            border-radius: 4px;
            border-left: 5px solid var(--shopee-orange);
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        /* --- Q&A ACCORDION --- */
        .qa-container { max-width: 800px; margin: 0 auto 50px; }
        .accordion-item {
            background: var(--white);
            margin-bottom: 10px;
            border-radius: 4px;
            box-shadow: 0 1px 2px rgba(0,0,0,0.05);
            overflow: hidden;
        }
        .accordion-header {
            padding: 15px 20px;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            font-weight: 500;
            transition: 0.3s;
        }
        .accordion-header:hover { color: var(--shopee-orange); background: #fef6f5; }
        .accordion-body {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease-out;
            padding: 0 20px;
            color: var(--text-grey);
            font-size: 0.95rem;
        }
        .accordion-body p { padding: 15px 0; }

        /* --- PAYMENT & TESTIMONI --- */
        .payment-row { display: flex; gap: 20px; justify-content: center; margin: 30px 0; flex-wrap: wrap; }
        .payment-logo { font-weight: bold; font-size: 1.5rem; color: #004d9a; display: flex; align-items: center; gap: 10px; background: #fff; padding: 10px 20px; border-radius: 5px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }

        .testi-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin-bottom: 40px; }
        .testi-card { background: var(--white); padding: 20px; border-radius: 4px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
        .stars { color: #ffca28; margin-bottom: 10px; }

        /* --- FOOTER --- */
        footer {
            background: #fbfbfb;
            border-top: 4px solid var(--shopee-orange);
            color: var(--text-grey);
            font-size: 0.9rem;
        }

        .footer-top { padding: 50px 0; border-bottom: 1px solid #e8e8e8; }
        .footer-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 30px; }

        .footer-title { color: var(--text-black); font-weight: 700; margin-bottom: 20px; text-transform: uppercase; font-size: 0.85rem; }
        .footer-links li { margin-bottom: 10px; }
        .footer-links a:hover { color: var(--shopee-orange); }

        .footer-bottom { background: #f5f5f5; padding: 20px 0; text-align: center; }

        /* --- SCROLL TO TOP --- */
        .scroll-top {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background: var(--shopee-orange);
            color: var(--white);
            width: 45px;
            height: 45px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 4px 10px rgba(0,0,0,0.2);
            opacity: 0;
            transition: 0.3s;
            z-index: 999;
        }
        .scroll-top.show { opacity: 1; }

        /* Mobile Responsive */
        @media (max-width: 768px) {
            .nav-menu { display: none; }
            .mobile-btn { display: block; font-size: 1.5rem; }
            .hero-section { padding: 0; }
            .slider-container { height: 250px; }
            .price-amount { font-size: 2rem; }
        }

        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
    </style>
</head>
<body>

    <header>
        <div class="container top-bar">
            <div>
                <a href="#">Jual di Sancaka</a> | <a href="#">Download Aplikasi</a> | <a href="#">Ikuti Kami di <i class="fab fa-instagram"></i> <i class="fab fa-facebook"></i></a>
            </div>
            <div>
                <a href="#"><i class="far fa-bell"></i> Notifikasi</a>
                <a href="#" style="margin-left: 10px;"><i class="far fa-question-circle"></i> Bantuan</a>
            </div>
        </div>

        <div class="container main-header">
            <a href="#" class="logo">
                <i class="fas fa-shipping-fast"></i> SANCAKA POS
            </a>

            <div style="flex: 1; max-width: 600px; margin: 0 20px; position: relative;">
                <input type="text" placeholder="Lacak Resi SPX / J&T / JNE disini..." style="width: 100%; padding: 12px; border-radius: 2px; border: none; outline: none;">
                <button style="position: absolute; right: 5px; top: 5px; background: var(--shopee-orange); color: white; border: none; padding: 8px 20px; border-radius: 2px; cursor: pointer;">
                    <i class="fas fa-search"></i>
                </button>
            </div>

            <nav>
                <ul class="nav-menu">
                    <li class="nav-item">
                        <a href="#">LAYANAN <i class="fas fa-chevron-down" style="font-size: 0.7rem;"></i></a>
                        <div class="dropdown">
                            <a href="#">Ekspedisi & Cargo</a>
                            <a href="#">Konstruksi Bangunan</a>
                            <a href="#">Perizinan (PT/CV)</a>
                        </div>
                    </li>
                    <li class="nav-item">
                        <a href="#">HARGA <i class="fas fa-chevron-down" style="font-size: 0.7rem;"></i></a>
                        <div class="dropdown">
                            <a href="#">Paket Hemat</a>
                            <a href="#">Paket Corporate</a>
                        </div>
                    </li>
                    <li class="nav-item"><a href="#testimoni">TESTIMONI</a></li>
                    <li class="nav-item"><a href="https://wa.me/6285745808809" class="btn-shopee">HUBUNGI WA</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <section class="hero-section">
        <div class="container">
            <div class="slider-container">
                <div class="slide active" style="background-image: url('https://source.unsplash.com/1200x400/?construction,crane');">
                    <div class="slide-overlay">
                        <h2>KIRIM PAKET AMAN, BANGUN RUMAH NYAMAN</h2>
                        <p>Solusi Satu Pintu: Ekspedisi Multi Kurir & Jasa Konstruksi Terpercaya di Ngawi.</p>
                    </div>
                </div>
                <div class="slide" style="background-image: url('https://source.unsplash.com/1200x400/?logistics,warehouse');">
                    <div class="slide-overlay">
                        <h2>JARINGAN EKSPEDISI TERLUAS</h2>
                        <p>Partner Resmi SPX Express, J&T, JNE, dan Lion Parcel.</p>
                    </div>
                </div>
                <div class="slide" style="background-image: url('https://source.unsplash.com/1200x400/?architect,building');">
                    <div class="slide-overlay">
                        <h2>DESAIN ARSITEK & PERIZINAN</h2>
                        <p>Kami urus IMB/PBG Anda hingga tuntas.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="container">
        <h2 class="section-title">MITRA LOGISTIK KAMI</h2>
        <div class="brands-grid">
            <div class="brand-card"><i class="fas fa-truck text-orange fa-2x"></i> &nbsp; SPX EXPRESS</div>
            <div class="brand-card"><span style="font-weight:900; color:red; font-size:1.5rem;">J&T</span> &nbsp; EXPRESS</div>
            <div class="brand-card"><span style="font-weight:900; color:blue; font-size:1.5rem;">JNE</span></div>
            <div class="brand-card"><i class="fas fa-dove fa-2x" style="color:orange;"></i> &nbsp; POS INDONESIA</div>
            <div class="brand-card">LION PARCEL</div>
            <div class="brand-card">ANTERAJA</div>
            <div class="brand-card">INDAH CARGO</div>
            <div class="brand-card">ID EXPRESS</div>
            <div class="brand-card">SAP EXPRESS</div>
        </div>
    </div>

    <section class="price-section">
        <div class="container text-center">
            <h2 class="section-title">ONGKIR TERMURAH SE-JAGAT RAYA!</h2>
            <p style="margin-bottom: 30px;">Harga spesial untuk UMKM dan Seller Online.</p>

            <div class="price-cards">
                <div class="price-tag">
                    <div class="price-header">PAKET HEMAT</div>
                    <div class="price-amount">Rp 2.424</div>
                    <div class="price-desc">Per Kg / Dokumen<br>Dalam Kota Ngawi</div>
                </div>
                <div class="price-tag">
                    <div style="position:absolute; top:0; right:0; background:red; color:white; padding:2px 10px; font-size:0.7rem;">POPULER</div>
                    <div class="price-header">REGULER</div>
                    <div class="price-amount">Rp 3.030</div>
                    <div class="price-desc">Jawa Timur Area<br>Estimasi 1-2 Hari</div>
                </div>
                <div class="price-tag">
                    <div class="price-header">ANTAR PULAU</div>
                    <div class="price-amount">Rp 5.050</div>
                    <div class="price-desc">Jawa - Bali<br>Via Darat</div>
                </div>
                <div class="price-tag">
                    <div class="price-header">CARGO</div>
                    <div class="price-amount">Rp 7.000</div>
                    <div class="price-desc">Min. 10 Kg<br>Seluruh Indonesia</div>
                </div>
            </div>
            <p style="margin-top:20px; font-size:0.8rem; color:#666;">*Harga tergantung berat tonase, volume dimensi, dan jarak tempuh.</p>
        </div>
    </section>

    <div class="container">
        <div class="seo-box">
            <h3 style="color: var(--shopee-orange); margin-bottom: 15px;">KENAPA MEMILIH SANCAKA EXPRESS?</h3>
            <p><strong>Cari Ekspedisi Murah di Ngawi?</strong> Sancaka Express solusinya! Kami adalah <span class="text-orange">RAJA ONGKIR MURAH</span> yang siap melayani pengiriman paket Anda dengan kecepatan cahaya. Jangan biarkan paket Anda nyangkut! Gunakan layanan <strong>JNT Cargo</strong>, <strong>SPX Express</strong>, dan <strong>Indah Cargo</strong> melalui kami dengan harga yang <strong>GILA-GILAAN MURAHNYA!</strong></p>
            <br>
            <p>Kami juga melayani <strong>Jasa Konstruksi Bangunan Profesional</strong>. Mau bangun Ruko? Gudang untuk stok Shopee? Atau urus IMB/PBG tanpa ribet? <strong>CV. SANCAKA KARYA HUTAMA</strong> adalah jawabannya. Kami satu-satunya agen logistik yang bisa bangun gudang Anda sekaligus isinya!</p>
        </div>
    </div>

    <div class="container text-center">
        <h3 style="margin-bottom: 20px;">METODE PEMBAYARAN TERLENGKAP</h3>
        <div class="payment-row">
            <div class="payment-logo"><i class="fas fa-qrcode"></i> QRIS</div>
            <div class="payment-logo">BANK BCA</div>
            <div class="payment-logo">BANK BRI</div>
            <div class="payment-logo">BANK MANDIRI</div>
            <div class="payment-logo">SHOPEEPAY</div>
        </div>
    </div>

    <div class="container" id="testimoni">
        <h2 class="section-title">APA KATA MEREKA?</h2>
        <div class="testi-grid">
            <div class="testi-card">
                <div class="stars"><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i></div>
                <p>"Ongkirnya beneran Rp 2.424 buat dalam kota! Cocok banget buat saya yang jualan online di Shopee. SPX Express lewat Sancaka paling top!"</p>
                <br><strong>- Rina, Seller Online Ngawi</strong>
            </div>
            <div class="testi-card">
                <div class="stars"><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i></div>
                <p>"Gak cuma kirim paket, saya juga urus IMB ruko disini. Pelayanan CV Sancaka Karya Hutama sangat profesional. Mantap!"</p>
                <br><strong>- Bpk. Budi, Pengusaha</strong>
            </div>
        </div>

        <div style="background: #e8e8e8; height: 300px; display: flex; align-items: center; justify-content: center; border-radius: 4px; margin-bottom: 50px;">
            <div class="text-center">
                <i class="fas fa-map-marked-alt fa-3x text-orange"></i>
                <h3>Google Maps Review</h3>
                <p>Lokasi Terverifikasi: Jl. Dr. Wahidin No. 18A, Ngawi</p>
                <div class="stars"><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i> (4.9/5)</div>
            </div>
        </div>
    </div>

    <div class="container qa-container">
        <h2 class="section-title">PERTANYAAN SERING DIAJUKAN (Q&A)</h2>

        <div class="accordion-item">
            <div class="accordion-header">
                Berapa lama paket sampai jika pakai SPX Express?
                <i class="fas fa-chevron-down"></i>
            </div>
            <div class="accordion-body">
                <p>Untuk layanan Reguler SPX, estimasi pengiriman adalah 1-3 hari kerja untuk area Pulau Jawa, dan 3-7 hari untuk luar Jawa.</p>
            </div>
        </div>

        <div class="accordion-item">
            <div class="accordion-header">
                Apakah bisa jemput paket ke rumah (Pick Up)?
                <i class="fas fa-chevron-down"></i>
            </div>
            <div class="accordion-body">
                <p>Tentu bisa! Kami menyediakan layanan FREE PICKUP untuk area Ngawi Kota dengan minimal pengiriman 5 paket.</p>
            </div>
        </div>

        <div class="accordion-item">
            <div class="accordion-header">
                Apakah harga Rp 2.424 berlaku untuk semua kurir?
                <i class="fas fa-chevron-down"></i>
            </div>
            <div class="accordion-body">
                <p>Harga promo Rp 2.424 berlaku untuk pengiriman dokumen dalam kota Ngawi menggunakan kurir tertentu. Untuk J&T, JNE, dll tarif menyesuaikan berat dan volume.</p>
            </div>
        </div>
    </div>

    <footer>
        <div class="container footer-top">
            <div class="footer-grid">
                <div>
                    <h4 class="footer-title">LAYANAN PELANGGAN</h4>
                    <ul class="footer-links">
                        <li><a href="#">Bantuan</a></li>
                        <li><a href="#">Metode Pembayaran</a></li>
                        <li><a href="#">Lacak Pesanan Pembeli</a></li>
                        <li><a href="#">Lacak Pengiriman Kami</a></li>
                        <li><a href="#">Garansi Sancaka</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="footer-title">JELAJAHI SANCAKA</h4>
                    <ul class="footer-links">
                        <li><a href="#">Tentang Kami</a></li>
                        <li><a href="#">Karir</a></li>
                        <li><a href="#">Kebijakan Privasi</a></li>
                        <li><a href="#">Blog Ekspedisi</a></li>
                        <li><a href="#">Kontak Media</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="footer-title">LOGISTIK & PEMBAYARAN</h4>
                    <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                        <i class="fab fa-cc-visa fa-2x"></i>
                        <i class="fab fa-cc-mastercard fa-2x"></i>
                        <i class="fas fa-money-bill-wave fa-2x"></i>
                        <i class="fas fa-truck-moving fa-2x"></i>
                    </div>
                </div>
                <div>
                    <h4 class="footer-title">IKUTI KAMI</h4>
                    <ul class="footer-links">
                        <li><a href="#"><i class="fab fa-facebook"></i> Facebook</a></li>
                        <li><a href="#"><i class="fab fa-instagram"></i> Instagram</a></li>
                        <li><a href="#"><i class="fab fa-linkedin"></i> LinkedIn</a></li>
                    </ul>
                </div>
            </div>

            <hr style="margin: 30px 0; border: 0; border-top: 1px solid #e8e8e8;">

            <div style="display: flex; align-items: flex-start; gap: 15px;">
                <img src="https://tokosancaka.com/storage/uploads/logo.jpeg" style="width: 60px; border-radius: 50%;">
                <div>
                    <h4 style="color: var(--text-black); margin-bottom: 5px;">CV. SANCAKA KARYA HUTAMA</h4>
                    <p><strong>WA:</strong> 0857-4580-8809</p>
                    <p><strong>Alamat:</strong> JL. DR. WAHIDIN NO.18A, RT.22 RW.05, KELURAHAN KETANGGI, KEC. NGAWI, KAB. NGAWI, JAWA TIMUR 63211</p>
                </div>
            </div>
        </div>

        <div class="footer-bottom">
            <div class="container">
                <p>&copy; 2026 Sancaka POS. Hak Cipta Dilindungi.</p>
                <p style="font-size: 0.8rem; margin-top: 5px;">Negara: Indonesia | Singapura | Thailand | Malaysia | Vietnam | Filipina</p>
            </div>
        </div>
    </footer>

    <div class="scroll-top" id="scrollTopBtn">
        <i class="fas fa-arrow-up"></i>
    </div>

    <script>
        // Slider Script
        let currentSlide = 0;
        const slides = document.querySelectorAll('.slide');

        function nextSlide() {
            slides[currentSlide].classList.remove('active');
            currentSlide = (currentSlide + 1) % slides.length;
            slides[currentSlide].classList.add('active');
        }
        setInterval(nextSlide, 4000); // Ganti gambar tiap 4 detik

        // Accordion Script
        const accHeaders = document.querySelectorAll('.accordion-header');
        accHeaders.forEach(header => {
            header.addEventListener('click', () => {
                const body = header.nextElementSibling;
                header.querySelector('i').classList.toggle('fa-chevron-up');
                header.querySelector('i').classList.toggle('fa-chevron-down');

                if (body.style.maxHeight) {
                    body.style.maxHeight = null;
                } else {
                    body.style.maxHeight = body.scrollHeight + "px";
                }
            });
        });

        // Scroll Top Script
        const scrollBtn = document.getElementById('scrollTopBtn');
        window.onscroll = function() {
            if (document.body.scrollTop > 300 || document.documentElement.scrollTop > 300) {
                scrollBtn.classList.add('show');
            } else {
                scrollBtn.classList.remove('show');
            }
        };

        scrollBtn.addEventListener('click', () => {
            window.scrollTo({top: 0, behavior: 'smooth'});
        });
    </script>
</body>
</html>
