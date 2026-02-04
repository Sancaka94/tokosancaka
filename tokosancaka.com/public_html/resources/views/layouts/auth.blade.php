<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title') - Sancaka Express</title>

    
   <!-- Favicon -->
    <link rel="icon" type="image/png" href="https://sancaka.bisnis.pro/wp-content/uploads/sites/5/2024/10/WhatsApp_Image_2024-10-08_at_10.14.16-removebg-preview.png">

    <!-- Google Fonts: Poppins -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Vendor CSS (from CDN) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Custom Auth CSS -->
    <link rel="stylesheet" href="{{ asset('assets/css/auth-style.css') }}">

<style>

/*
 * Stylesheet Kustom untuk Halaman Otentikasi
 * Sancaka Express
 */

/* Pengaturan dasar untuk body dan html */
body, html {
    height: 100%;
    margin: 0;
    font-family: 'Poppins', sans-serif;
    background-color: #dc3545;
}

/* Kontainer utama sekarang menjadi latar belakang dinamis */
.auth-container {
    position: relative;
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 100vh;
    width: 100%;
    overflow: hidden; /* Sembunyikan gambar yang keluar dari layar */
    background-color: #dc3545;
}


/* === Latar Belakang Grafis Animasi === */
.graphics-grid {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: auto; /* Biarkan tinggi menyesuaikan konten */
    display: grid;
    /* Membuat grid lebih responsif di berbagai ukuran layar */
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 1rem;
    padding: 1rem;
    animation: scroll-graphics 80s linear infinite; /* Animasi lebih lambat agar tidak mengganggu */
    z-index: 1;
}

.graphics-grid img {
    width: 100%;
    border-radius: 0.75rem;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
    opacity: 0.2; /* Dibuat lebih transparan agar tidak terlalu ramai */
}

/* Overlay gelap di atas gambar untuk membuat teks lebih terbaca */
.graphics-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background:#dc3545;       
    z-index: 2;
}


/* === Area Form di Tengah === */
.auth-form-wrapper {
    position: relative; /* Agar muncul di atas overlay */
    z-index: 3;
    width: 100%;
    max-width: 480px; /* Sedikit lebih lebar untuk kenyamanan */
    padding: 2rem; /* Jarak dari tepi layar pada mode mobile */
}

.auth-content {
    width: 100%;
}

/* Kartu yang menjadi pembungkus form dengan efek glassmorphism */
.auth-card {
    background: rgba(255, 255, 255, 0.98); /* Sedikit transparan agar lebih menyatu */
    padding: 2.5rem;
    border-radius: 1rem;
    border: 1px solid rgba(255, 255, 255, 0.5);
    box-shadow: 0 10px 60px rgba(0, 0, 0, 0.2);
    transition: all 0.3s ease;
    backdrop-filter: blur(5px);
    -webkit-backdrop-filter: blur(5px);
}

.auth-logo {
    max-height: 60px;
    margin-bottom: 1rem;
}

.auth-header h3 {
    font-weight: 600;
    color: #343a40;
}

/* Styling untuk input form */
.form-control {
    border-radius: 0.5rem;
    padding: 0.85rem 1rem;
    background-color: rgba(255, 255, 255, 0.8);
    border: 1px solid #ced4da;
}

.form-floating > .form-control:focus ~ label,
.form-floating > .form-control:not(:placeholder-shown) ~ label {
    transform: scale(.85) translateY(-.5rem) translateX(.15rem);
    background-color: transparent;
    padding: 0 0.2rem;
}

/* Styling untuk tombol utama */
.btn-danger {
    background-color: #dc3545;
    border-color: #dc3545;
    padding: 0.75rem 1rem;
    font-weight: 500;
    border-radius: 0.5rem;
    transition: all 0.3s ease;
}

.btn-danger:hover {
    background-color: #c82333;
    border-color: #bd2130;
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(220, 53, 69, 0.4);
}

/* Keyframes untuk animasi scroll */
@keyframes scroll-graphics {
    0% {
        transform: translateY(0);
    }
    100% {
        /* Setengah dari tinggi total grid agar loop terlihat mulus */
        transform: translateY(-50%);
    }
}

/* Desain ini sudah responsif, tidak perlu media query khusus untuk layout */
 .partner-section {
            background: rgba(255, 255, 255, 0.9);
            border-radius: 1rem;
            padding: 1.5rem;
            margin-top: 1.5rem;
            backdrop-filter: blur(5px);
            -webkit-backdrop-filter: blur(5px);
            border: 1px solid rgba(255, 255, 255, 0.5);
        }
        .partner-title {
            text-align: center;
            font-weight: 500;
            color: #6c757d;
            margin-bottom: 1.5rem;
        }
        .partner-logos {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            align-items: center;
            gap: 1.5rem;
        }
        .partner-logo img {
            max-height: 40px; /* Uniform height for logos */
            max-width: 100px;
            object-fit: contain;
            filter: grayscale(100%);
            opacity: 0.7;
            transition: all 0.3s ease;
        }
        .partner-logo img:hover {
            filter: grayscale(0%);
            opacity: 1;
            transform: scale(1.1);
        }
        
        /* CSS for Mega Menu */
        .dropdown.mega-dropdown {
          position: static;
        }

        .dropdown-menu.dropdown-megamenu {
          width: 100%;
          left: 0;
          right: 0;
          padding: 20px 30px;
          margin-top: 0; /* Removes the small gap */
          border-top: 1px solid #eee;
          border-radius: 0 0 0.375rem 0.375rem;
        }

        .megamenu-heading {
            font-size: 1rem;
            font-weight: 600;
            color: #f57224; /* Orange color from your button */
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid #eee;
        }

        .megamenu-list a {
            padding: 6px 0;
            display: block;
            color: #495057;
            text-decoration: none;
            transition: color 0.2s;
            background-color: transparent !important; /* Override Bootstrap hover */
        }

        .megamenu-list a:hover {
            color: #0d6efd;
        }

        .megamenu-list a .fa-solid {
            width: 20px;
            text-align: center;
        }

        .megamenu-feature {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 0.375rem;
            text-align: center;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        
        /* Hanya target mega menu, jangan global .btn */
        .mega-dropdown { position: static; }
        .mega-menu {
        width: 100%;
        border-top: 3px solid #0d6efd;
        background: #fff;
        position: absolute; /* biar dropdown muncul di atas konten */
        z-index: 1050; /* cukup tinggi tapi tidak mengganggu nav lain */
        }

       /* Tombol Shopee agar selalu terlihat */
.btn-shopee {
    position: relative;
    z-index: 2000; /* lebih tinggi dari mega menu (1050) */
    background-color: #ff5722; /* orange */
    color: #fff;
    border: none;
}

.btn-shopee:hover {
    background-color: #e64a19;
    color: #fff;
}

</style>

</head>
<body>

   <!-- Kontainer utama -->
<div class="auth-container d-flex justify-content-center align-items-center min-vh-100 bg-dark">
    <!-- Wrapper -->
    <div class="container bg-white rounded-4 shadow p-4" style="max-width: 1100px;">
        <div class="row align-items-center">
            
            <!-- Kolom Form Login -->
            <div class="col-md-6 border-end">
                <div class="text-center mb-4">
                    <a href="{{ url('/') }}">
                        <img src="https://sancaka.bisnis.pro/wp-content/uploads/sites/5/2024/10/WhatsApp_Image_2024-10-08_at_10.14.16-removebg-preview.png" alt="Sancaka Express Logo" class="auth-logo mb-2" style="height: 60px;">
                    </a>
                    <h3>@yield('title')</h3>
                </div>
                @yield('content')
            </div>

            <!-- Kolom Logo Partner -->
            <div class="col-md-6 text-center">
                <h6 class="mb-3 text-muted">Didukung Oleh</h6>
                <div class="d-flex flex-wrap justify-content-center gap-2">
                    @php
                        $partnerLogos = [
                            'https://upload.wikimedia.org/wikipedia/commons/0/01/J%26T_Express_logo.svg',
                            'https://upload.wikimedia.org/wikipedia/commons/9/92/New_Logo_JNE.png',
                            'https://www.posindonesia.co.id/_next/image?url=https%3A%2F%2Fadmin-piol.posindonesia.co.id%2Fmedia%2FUntitled%20design%20(7).png&w=384&q=75',
                            'https://blogger.googleusercontent.com/img/b/R29vZ2xl/AVvXsEicOAaLoH2eElQ93_gbkzhvk4dRhWVlk5wQsGgilihIB58321aHchlJLdjyz1ToS25P_nWrHJ_E4QBiW_OVlI7tQt7cZ5I0HZqk6StS7jZltLVvDXp2d5ZDLB9yklhV4x6z2iXyURURDv_unhf-U6vyiD_8to9OC4PBwMwyU_5wAqOiCl6tKiaTA-ri1Q/s851/Logo%20Indah%20Logistik%20Cargo@0.5x.png',
                            'https://assets.bukalapak.com/beagle/images/courier_logo/sap.png',
                            'https://assets.bukalapak.com/beagle/images/courier_logo/id-express.png',
                            'https://i.pinimg.com/736x/22/cf/92/22cf92368c1f901d17e38e99061f4849.jpg',
                            'https://assets.bukalapak.com/beagle/images/courier_logo/lionparcel.png',
                            'https://placehold.co/150x60/EE4D2D/FFFFFF?text=SPX+Express',
                            'https://assets.bukalapak.com/beagle/images/courier_logo/sicepat.png',
                            'https://blogger.googleusercontent.com/img/b/R29vZ2xl/AVvXsEjxj3iyyZEjK2L4A4yCIr_E-4W3hF2lk_yb-t0Oj2oFPErCPCMHie5LHqps02xMb6sNa-Gqz5NSX_P_hzWlYpUpJUlCD4iN6_QxiSG9fzY4bsZ9XvLFDn7HCiORtNvIlPfuQbSSdW96p7x7uN8ek3FWyHW9c2bznrFBQkoLd5A9sVAFVKWLfUhT3Dxh/s320/GKL41_NCS%20Kurir%20-%20Koleksilogo.com.jpg',
                            'https://assets.bukalapak.com/beagle/images/courier_logo/ninja-express.png',
                            'https://assets.autokirim.com/courier/sc.png',
                            'https://assets.autokirim.com/courier/oexpress.png',
                            'https://assets.autokirim.com/courier/paxel.png',
                            'https://sancaka.bisnis.pro/wp-content/uploads/sites/5/2024/10/WhatsApp_Image_2024-10-08_at_10.14.16-removebg-preview.png',
                            'https://blogger.googleusercontent.com/img/b/R29vZ2xl/AVvXsEgkqxG7uCJYDs4MqqbV1aDd3MSLq_O3pxNpWTrAZNKI9ejwG5_iqLxFraNQSFKVg7vOj0HoJFPo5SnldCvMHkGEwFAGpCVQ4VIYtck__yDvTt9gvf-LurEtoY4L99uzJPo-wfGq29AWzbro8-W9cNttk4neFTyrbDy8jo59kBE487f_cYSjS5qE0XnZ/w400-h211/Lowongan%20Kerja%20PT%20Wahana%20Prestasi%20Logistik.png'
                        ];
                    @endphp
                    @foreach ($partnerLogos as $logo)
                        <div style="flex: 0 0 30%; max-width: 30%;">
                            <img src="{{ $logo }}" class="img-fluid mb-2" style="max-height: 50px;">
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <p class="text-center text-muted mt-4">&copy; {{ date('Y') }} Sancaka Express</p>
    </div>
</div>


    <!-- Vendor JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Page specific scripts -->
    @stack('scripts')
</body>
</html>