{{-- File: resources/views/partials/footer.blade.php --}}
<footer class="footer pt-5 pb-4">
    <div class="container">
        <div class="row">
            <!-- About Us -->
            <div class="col-lg-4 col-md-6 mb-4 mb-lg-0">
                <h5 class="text-uppercase text-white fw-bold mb-4">PORTAL BLOG</h5>
                <p>
                    Portal berita terdepan yang menyajikan informasi terbaru, akurat, dan terpercaya dari berbagai penjuru dunia.
                </p>
                <div class="social-icons mt-4">
                    <a href="#" title="Facebook"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" title="Twitter"><i class="fab fa-twitter"></i></a>
                    <a href="#" title="Instagram"><i class="fab fa-instagram"></i></a>
                    <a href="#" title="LinkedIn"><i class="fab fa-linkedin-in"></i></a>
                </div>
            </div>

            <!-- Quick Links -->
            <div class="col-lg-2 col-md-6 mb-4 mb-lg-0">
                <h5 class="text-uppercase text-white fw-bold mb-4">Links</h5>
                <ul class="list-unstyled mb-0">
                    <li class="mb-2"><a href="#">Tentang Kami</a></li>
                    <li class="mb-2"><a href="#">Kontak</a></li>
                    <li class="mb-2"><a href="#">Kebijakan Privasi</a></li>
                    <li class="mb-2"><a href="#">Syarat & Ketentuan</a></li>
                </ul>
            </div>

            <!-- Categories -->
            <div class="col-lg-2 col-md-6 mb-4 mb-lg-0">
                <h5 class="text-uppercase text-white fw-bold mb-4">Kategori</h5>
                 <ul class="list-unstyled mb-0">
                    <li class="mb-2"><a href="#">Teknologi</a></li>
                    <li class="mb-2"><a href="#">Olahraga</a></li>
                    <li class="mb-2"><a href="#">Ekonomi</a></li>
                    <li class="mb-2"><a href="#">Gaya Hidup</a></li>
                </ul>
            </div>

            <!-- Newsletter -->
            <div class="col-lg-4 col-md-6 mb-4 mb-lg-0">
                <h5 class="text-uppercase text-white fw-bold mb-4">Berlangganan</h5>
                <p>Dapatkan berita terbaru langsung ke email Anda.</p>
                <div class="input-group mb-3">
                    <input type="email" class="form-control" placeholder="Alamat email Anda" aria-label="Alamat email Anda">
                    <button class="btn btn-primary" type="button">Daftar</button>
                </div>
            </div>
        </div>
    </div>
    <div class="text-center p-3 mt-4" style="background-color: rgba(0, 0, 0, 0.2);">
        © {{ date('Y') }} Hak Cipta Dilindungi:
        <a href="{{ url('/') }}">PortalBlog.com</a>
    </div>
</footer>
