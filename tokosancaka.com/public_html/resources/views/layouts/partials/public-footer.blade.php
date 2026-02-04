{{-- resources/views/layouts/partials/public-footer.blade.php --}}

<footer class="bg-dark text-white mt-5 pt-5 pb-3">
    <div class="container">
        <div class="row gy-4">
            {{-- Brand --}}
            <div class="col-lg-4 col-md-6">
                <h5 class="fw-bold mb-3">Sancaka Express</h5>
                <p class="text-white-50">
                    Solusi pengiriman terpercaya untuk kebutuhan personal dan bisnis Anda.
                    Cepat, aman, dan dapat diandalkan.
                </p>
                <img src="https://tokosancaka.com/storage/uploads/sectigo.png" alt="Sectigo Secure" style="max-width: 120px; margin-top: 15px;">
            </div>

            {{-- Navigasi --}}
            <div class="col-lg-2 col-md-6">
                <h5 class="fw-bold mb-3">Navigasi</h5>
                <ul class="list-unstyled">
                    <li class="mb-2">
                        <a href="#" class="text-white-50 text-decoration-none footer-link">Layanan</a>
                    </li>
                    <li class="mb-2">
                        <a href="#" class="text-white-50 text-decoration-none footer-link">Rekanan</a>
                    </li>
                    <li class="mb-2">
                        <a href="#" class="text-white-50 text-decoration-none footer-link">Testimoni</a>
                    </li>
                </ul>
            </div>

            {{-- Kontak --}}
            <div class="col-lg-3 col-md-6">
                <h5 class="fw-bold mb-3">Hubungi Kami</h5>
                <p class="text-white-50 mb-2">
                    <i class="fas fa-map-marker-alt me-2"></i>
                    Jl. Dr. Wahidin No.18A RT.22 RW.05, Ngawi, Jawa Timur 63211
                </p>
                <p class="text-white-50 mb-2">
                    <i class="fas fa-envelope me-2"></i>
                    admin@tokosancaka.com
                </p>
                <p class="text-white-50 mb-2">
                    <i class="fas fa-phone me-2"></i>
                    +62 881 9435 180
                </p>
            </div>

            {{-- Sosial Media --}}
            <div class="col-lg-3 col-md-6">
                <h5 class="fw-bold mb-3">Ikuti Kami</h5>
                <div class="d-flex gap-3">
                    <a href="https://www.facebook.com/sancakakarya.hutama" class="text-white fs-4 footer-social">
                        <i class="fab fa-facebook"></i>
                    </a>
                    <a href="https://www.instagram.com/cvsancakakaryahutama" class="text-white fs-4 footer-social">
                        <i class="fab fa-instagram"></i>
                    </a>
                    <a href="https://www.tiktok.com/@sancaka.legal" class="text-white fs-4 footer-social">
                        <i class="fab fa-tiktok"></i>
                    </a>
                    <a href="https://wa.me/628819435180" class="text-white fs-4 footer-social">
                        <i class="fab fa-whatsapp"></i>
                    </a>
                </div>
            </div>
        </div>

        <hr class="border-secondary mt-5">
        <div class="text-center text-white-50">
            <p class="mb-0">&copy; {{ date('Y') }} Sancaka Express. All Rights Reserved.</p>
        </div>
    </div>
</footer>

{{-- Tambahkan style kecil di bawah --}}
<style>
    .footer-link:hover {
        color: #ffffff !important;
    }
    .footer-social:hover {
        color: #0dcaf0 !important; /* bisa diganti sesuai warna brand */
    }
</style>
