<style>
/* ============================= */
/* HEADER / NAVBAR */
/* ============================= */
.navbar {
    transition: all 0.3s ease;
}

.navbar-brand strong {
    font-weight: 700;
    font-size: 1.1rem;
    letter-spacing: 0.5px;
}

.navbar-light .navbar-nav .nav-link {
    color: #333;
    transition: color 0.2s ease;
    font-weight: 500;
    padding: 10px 16px;
}

.navbar-light .navbar-nav .nav-link:hover,
.navbar-light .navbar-nav .nav-link.active {
    color: #0d6efd;
    background-color: rgba(13, 110, 253, 0.1);
    border-radius: 8px;
}

.navbar .dropdown-menu {
    border-radius: 8px;
    box-shadow: 0 8px 16px rgba(0,0,0,0.05);
    font-size: 14px;
}

.dropdown-item {
    transition: all 0.2s;
}

.dropdown-item:hover {
    background-color: #f1f1f1;
    color: #0d6efd;
}

.navbar .btn-outline-primary {
    border-radius: 6px;
}

.navbar .btn-danger {
    border-radius: 6px;
}

/* Ikon */
.icon {
    width: 1rem;
    text-align: center;
}

/* ============================= */
/* FOOTER */
/* ============================= */
footer {
    background-color: #0d6efd;
    color: #fff;
    padding: 40px 0;
}

footer a {
    color: #fff;
    text-decoration: none;
    transition: 0.2s;
}

footer a:hover {
    color: #ffd600;
    text-decoration: underline;
}

.footer-bottom {
    border-top: 1px solid rgba(255,255,255,0.1);
    padding-top: 20px;
    text-align: center;
    font-size: 14px;
}

/* Responsive */
@media (max-width: 991.98px) {
    .navbar .dropdown-menu {
        box-shadow: none;
    }
}
</style>

<header>
  <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm sticky-top">
    <div class="container">
      <a class="navbar-brand d-flex align-items-center" href="/">
        <img src="https://sancaka.bisnis.pro/wp-content/uploads/sites/5/2024/10/WhatsApp_Image_2024-10-08_at_10.14.16-removebg-preview.png" alt="Sancaka Express Logo" style="max-height: 40px;" class="me-2">
        <strong>SANCAKA EXPRESS</strong>
      </a>

      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
        <span class="navbar-toggler-icon"></span>
      </button>

      <div class="collapse navbar-collapse" id="navbarNav">
        <ul class="navbar-nav ms-auto align-items-lg-center">
          <li class="nav-item">
            <a class="nav-link active fw-bold" href="/">Beranda</a>
          </li>
          <li class="nav-item"><a class="nav-link" href="#layanan">Layanan</a></li>
          <li class="nav-item"><a class="nav-link" href="#tentang">Tentang Kami</a></li>
          <li class="nav-item"><a class="nav-link" href="/etalase">Marketplace</a></li>

          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" id="rekananDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">Rekanan</a>
            <ul class="dropdown-menu dropdown-menu-lg-start" aria-labelledby="rekananDropdown">
              <li><a class="dropdown-item" href="#"><i class="icon icon-truck-fast me-2"></i>JNE</a></li>
              <li><a class="dropdown-item" href="#"><i class="icon icon-truck me-2"></i>J&T EXPRESS</a></li>
              <li><a class="dropdown-item" href="#"><i class="icon icon-box me-2"></i>J&T CARGO</a></li>
              <li><a class="dropdown-item" href="#"><i class="icon icon-boxes-stacked me-2"></i>WAHANA EXPRESS</a></li>
              <li><a class="dropdown-item" href="#"><i class="icon icon-envelope me-2"></i>POS INDONESIA</a></li>
              <li><a class="dropdown-item" href="#"><i class="icon icon-paper-plane me-2"></i>SAP EXPRESS</a></li>
              <li><a class="dropdown-item" href="#"><i class="icon icon-cube me-2"></i>INDAH CARGO</a></li>
              <li><a class="dropdown-item" href="#"><i class="icon icon-plane-departure me-2"></i>LION PARCEL</a></li>
              <li><a class="dropdown-item" href="#"><i class="icon icon-truck-front me-2"></i>ID EXPRESS</a></li>
              <li><a class="dropdown-item" href="#"><i class="icon icon-truck-arrow-right me-2"></i>SPX EXPRESS</a></li>
              <li><a class="dropdown-item" href="#"><i class="icon icon-road me-2"></i>NCS</a></li>
              <li><a class="dropdown-item" href="#"><i class="icon icon-truck-ramp-box me-2"></i>SENTRAL CARGO</a></li>
              <li><hr class="dropdown-divider"></li>
              <li><a class="dropdown-item" href="#"><i class="icon icon-bolt me-2"></i>SANCAKA EXPRESS</a></li>
            </ul>
          </li>

          <li class="nav-item"><a class="nav-link" href="#kontak">Kontak</a></li>
        </ul>

        <div class="d-lg-flex align-items-center mt-3 mt-lg-0 ms-lg-3">
          <div class="dropdown me-2">
            <a class="btn btn-outline-primary dropdown-toggle" href="#" id="akunDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
              <i class="icon icon-user me-1"></i> Akun
            </a>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="akunDropdown">
              <li><a class="dropdown-item" href="/login"><i class="icon icon-login me-2"></i> Login</a></li>
              <li><a class="dropdown-item" href="/register"><i class="icon icon-user-plus me-2"></i> Daftar</a></li>
            </ul>
          </div>

          <div class="dropdown">
            <button class="btn btn-danger dropdown-toggle fw-bold" type="button" id="orderDropdown" data-bs-toggle="dropdown" aria-expanded="false">
              Order Sekarang
            </button>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="orderDropdown">
              <li><a class="dropdown-item" href="/pesanan/customer/create"><i class="icon icon-shipping-fast me-2"></i> Order via Sancaka Express</a></li>
              <li><a class="dropdown-item" href="/scan/spx"><i class="icon icon-barcode me-2"></i> Input Resi SPX</a></li>
            </ul>
          </div>
        </div>
      </div>
    </div>
  </nav>
</header>
