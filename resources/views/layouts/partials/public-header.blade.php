{{-- resources/views/layouts/partials/public-header.blade.php --}}



<header>

    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm sticky-top">

        <div class="container-fluid">

            <a class="navbar-brand d-flex align-items-center" href="/">

                <img src="https://sancaka.bisnis.pro/wp-content/uploads/sites/5/2024/10/WhatsApp_Image_2024-10-08_at_10.14.16-removebg-preview.png" alt="Sancaka Express Logo" style="max-height: 40px;" class="me-2">

                <strong>SANCAKA EXPRESS</strong>

            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">

                <span class="navbar-toggler-icon"></span>

            </button>

            <div class="collapse navbar-collapse" id="navbarNav">

                <ul class="navbar-nav ms-auto align-items-lg-center">

                    <li class="nav-item"><a class="nav-link" href="/">Beranda</a></li>

                    <li class="nav-item"><a class="nav-link" href="#">Layanan</a></li>

                    <li class="nav-item"><a class="nav-link" href="#">Kontak</a></li>

                     <li class="nav-item">

                        <a class="btn btn-outline-primary ms-lg-2" href="{{ route('login') }}">

                            <i class="fas fa-sign-in-alt me-1"></i> Login Pelanggan

                        </a>

                    </li>

                </ul>

            </div>

        </div>

    </nav>

</header>

