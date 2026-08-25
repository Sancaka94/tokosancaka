<?php
// ==========================================
// KONFIGURASI SEO & DATA WEBSITE
// ==========================================
$site_name   = "CV. SANCAKA KARYA HUTAMA";
$title       = "LPK Hongkong Resmi & Terpercaya | CV. SANCAKA KARYA HUTAMA";
$description = "Daftar LPK Hongkong resmi dan terpercaya bersama CV. SANCAKA KARYA HUTAMA. Pelatihan kerja profesional, proses cepat, biaya transparan, dan siap kerja di Hongkong. Hubungi kami sekarang!";
$keywords    = "lpk hongkong, pelatihan kerja hongkong, pmi hongkong, agen pmi resmi, cv sancaka karya hutama, lpk ngawi, jawa timur, kerja di hongkong, lowongan kerja hongkong";
$canonical   = "https://lpkhongkong.tokosancaka.com/"; // Ganti dengan nama domain Anda

// Data Kontak
$wa_number   = "6285745808809";
$wa_text     = "Halo Admin CV. SANCAKA KARYA HUTAMA, saya ingin mendapat informasi lengkap mengenai pendaftaran LPK Hongkong.";
$wa_link     = "https://api.whatsapp.com/send?phone={$wa_number}&text=" . urlencode($wa_text);
$address     = "JL. DR. WAHIDIN NO.18A RT.22 RW.05 KELURAHAN KETANGGI KEC. NGAWI KAB. NGAWI JAWA TIMUR 63211";
?>
<!DOCTYPE html>
<html lang="id-ID">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">

    <!-- SEO Meta Tags -->
    <title><?php echo $title; ?></title>
    <meta name="description" content="<?php echo $description; ?>">
    <meta name="keywords" content="<?php echo $keywords; ?>">
    <meta name="author" content="<?php echo $site_name; ?>">
    <link rel="canonical" href="<?php echo $canonical; ?>">
    <meta name="robots" content="index, follow">

    <!-- Open Graph / Social Media Meta Tags -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?php echo $canonical; ?>">
    <meta property="og:title" content="<?php echo $title; ?>">
    <meta property="og:description" content="<?php echo $description; ?>">
    <meta property="og:site_name" content="<?php echo $site_name; ?>">

    <!-- Schema.org JSON-LD (Sangat disukai Google untuk SEO Lokal) -->
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "EducationalOrganization",
      "name": "<?php echo $site_name; ?> - LPK Hongkong",
      "description": "<?php echo $description; ?>",
      "url": "<?php echo $canonical; ?>",
      "telephone": "+<?php echo $wa_number; ?>",
      "address": {
        "@type": "PostalAddress",
        "streetAddress": "JL. DR. WAHIDIN NO.18A RT.22 RW.05 KELURAHAN KETANGGI",
        "addressLocality": "KEC. NGAWI",
        "addressRegion": "JAWA TIMUR",
        "postalCode": "63211",
        "addressCountry": "ID"
      }
    }
    </script>

    <!-- CSS Internal untuk Load Super Cepat (Skor PageSpeed Tinggi) -->
    <style>
        :root {
            --primary: #d32f2f;
            --secondary: #212121;
            --light: #f5f5f5;
            --white: #ffffff;
            --wa-color: #25D366;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        body { background-color: var(--light); color: var(--secondary); line-height: 1.6; }
        .container { max-width: 1000px; margin: 0 auto; padding: 0 20px; }

        /* Header */
        header { background: var(--primary); color: var(--white); padding: 40px 0; text-align: center; }
        header h1 { font-size: 2.5rem; margin-bottom: 10px; }
        header p { font-size: 1.2rem; opacity: 0.9; }

        /* Sections */
        section { padding: 60px 0; background: var(--white); margin-bottom: 10px; }
        section.bg-light { background: var(--light); }
        .section-title { text-align: center; font-size: 2rem; margin-bottom: 30px; color: var(--primary); }

        /* Grid Layout */
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 30px; }
        .card { background: var(--white); padding: 25px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); border-top: 4px solid var(--primary); }
        .card h3 { margin-bottom: 15px; color: var(--secondary); }

        /* Call to Action Button */
        .cta-container { text-align: center; margin-top: 40px; }
        .btn-wa { display: inline-flex; align-items: center; justify-content: center; background: var(--wa-color); color: var(--white); padding: 15px 30px; font-size: 1.2rem; font-weight: bold; text-decoration: none; border-radius: 50px; transition: transform 0.3s ease; box-shadow: 0 5px 15px rgba(37, 211, 102, 0.4); }
        .btn-wa:hover { transform: translateY(-3px); }

        /* Footer */
        footer { background: var(--secondary); color: var(--white); padding: 40px 0; text-align: center; }
        footer p { margin-bottom: 10px; }
        .address-box { background: rgba(255,255,255,0.1); padding: 20px; border-radius: 8px; margin-top: 20px; display: inline-block; max-width: 600px; }

        @media (max-width: 768px) {
            header h1 { font-size: 2rem; }
            section { padding: 40px 0; }
        }
    </style>
</head>
<body>

    <!-- Header Section (Mengandung H1 untuk SEO) -->
    <header>
        <div class="container">
            <h1>Lembaga Pelatihan Kerja (LPK) Hongkong</h1>
            <p>Jalan Pintas Sukses Berkarir di Hongkong Bersama <?php echo $site_name; ?></p>
        </div>
    </header>

    <!-- Keunggulan Section -->
    <section>
        <div class="container">
            <h2 class="section-title">Mengapa Memilih Kami?</h2>
            <div class="grid">
                <div class="card">
                    <h3>✅ Resmi & Berlegalitas</h3>
                    <p>Di bawah naungan <?php echo $site_name; ?>, proses keberangkatan Anda dijamin aman, terpantau, dan sesuai dengan prosedur hukum pemerintah Indonesia dan Hongkong.</p>
                </div>
                <div class="card">
                    <h3>✅ Proses Cepat & Transparan</h3>
                    <p>Tidak ada biaya tersembunyi. Kami memastikan dokumen, paspor, visa, hingga jadwal terbang Anda diurus dengan cepat (gercep) dan profesional.</p>
                </div>
                <div class="card">
                    <h3>✅ Pelatihan Bahasa Intensif</h3>
                    <p>Tingkatkan keahlian Bahasa Kanton (Cantonese) dan pemahaman budaya lokal Hongkong agar Anda siap kerja dan mendapatkan gaji maksimal.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Persyaratan Section -->
    <section class="bg-light">
        <div class="container">
            <h2 class="section-title">Syarat Pendaftaran Mudah</h2>
            <div style="max-width: 700px; margin: 0 auto; background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.05);">
                <ul style="list-style-type: none;">
                    <li style="margin-bottom: 15px;">✔️ Wanita / Pria sehat jasmani & rohani.</li>
                    <li style="margin-bottom: 15px;">✔️ E-KTP asli & Kartu Keluarga (KK).</li>
                    <li style="margin-bottom: 15px;">✔️ Akta Kelahiran / Ijazah terakhir.</li>
                    <li style="margin-bottom: 15px;">✔️ Surat izin dari keluarga / suami (bagi yang sudah menikah).</li>
                    <li style="margin-bottom: 15px;">✔️ Bersedia mengikuti pelatihan bahasa dan tata krama di asrama.</li>
                </ul>
            </div>
        </div>
    </section>

    <!-- Call to Action Section -->
    <section>
        <div class="container cta-container">
            <h2 class="section-title">Wujudkan Impian Anda Sekarang!</h2>
            <p style="font-size: 1.1rem; margin-bottom: 30px;">Jangan ragu! Konsultasikan rencana keberangkatan Anda ke Hongkong. Tim kami siap membantu dari nol hingga Anda sukses bekerja.</p>
            <a href="<?php echo $wa_link; ?>" target="_blank" class="btn-wa">
                Chat WhatsApp Sekarang (<?php echo $wa_number; ?>)
            </a>
        </div>
    </section>

    <!-- Footer Section -->
    <footer>
        <div class="container">
            <h3><?php echo $site_name; ?></h3>
            <p>Mitra Terpercaya Anda untuk Legalitas & Penempatan Tenaga Kerja</p>

            <div class="address-box">
                <strong>Alamat Kantor:</strong><br>
                <?php echo $address; ?>
            </div>

            <div style="margin-top: 30px; font-size: 0.9rem; opacity: 0.7;">
                &copy; <?php echo date("Y"); ?> <?php echo $site_name; ?>. All Rights Reserved.
            </div>
        </div>
    </footer>

</body>
</html>
<?php
// LOG LOG
// End of file. Do not remove this log marker.
?>
