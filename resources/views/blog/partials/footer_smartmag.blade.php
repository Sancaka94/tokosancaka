{{--
    FILE: resources/views/blog/partials/footer_smartmag.blade.php
    DESAIN: BLACK ELEGANT FOOTER
--}}

{{-- Font khusus Logo Footer --}}
<link href="https://fonts.googleapis.com/css2?family=UnifrakturMaguntia&display=swap" rel="stylesheet">

<style>
    .smart-footer {
        background-color: #161616;
        color: #b0b0b0;
        font-family: 'Inter', sans-serif;
        padding-top: 60px;
        font-size: 13px;
        margin-top: 60px;
    }

    /* Logo Row */
    .footer-logo-row {
        border-bottom: 1px solid #333;
        padding-bottom: 30px;
        margin-bottom: 40px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .footer-logo-text {
        font-family: 'UnifrakturMaguntia', cursive; /* Font Koran */
        font-size: 36px;
        color: #fff;
        margin: 0;
        letter-spacing: 1px;
    }
    .footer-social a {
        color: #fff;
        margin-left: 15px;
        font-size: 14px;
        transition: 0.2s;
    }
    .footer-social a:hover { color: #dd0017; }

    /* Widgets */
    .f-widget-title {
        color: #fff;
        font-weight: 800;
        text-transform: uppercase;
        margin-bottom: 20px;
        font-size: 12px;
        letter-spacing: 0.5px;
    }
    .f-links { list-style: none; padding: 0; margin: 0; }
    .f-links li { margin-bottom: 12px; }
    .f-links a {
        color: #b0b0b0;
        text-decoration: none;
        font-weight: 500;
        font-size: 13px;
        transition: 0.2s;
    }
    .f-links a:hover { color: #fff; }

    /* Subscribe Form */
    .sub-text { margin-bottom: 20px; line-height: 1.6; }
    .sub-input {
        background: #222;
        border: 1px solid #333;
        color: #fff;
        padding: 10px 15px;
        font-size: 13px;
        width: 100%;
        margin-bottom: 10px;
    }
    .sub-input:focus { outline: none; border-color: #555; }
    .btn-sub-red {
        background-color: #dd0017;
        color: #fff;
        border: none;
        text-transform: uppercase;
        font-weight: 700;
        font-size: 11px;
        padding: 10px 20px;
        width: 100%;
        letter-spacing: 1px;
    }
    .btn-sub-red:hover { background-color: #b00012; }

    /* Copyright */
    .footer-bottom {
        border-top: 1px solid #333;
        padding: 30px 0;
        margin-top: 50px;
        display: flex;
        justify-content: space-between;
        font-size: 12px;
    }
    .footer-bottom a { color: #b0b0b0; margin-left: 15px; text-decoration: none; }
    .footer-bottom a:hover { color: #fff; }
</style>

<footer class="smart-footer">
    <div class="container">

        {{-- LOGO & SOCIAL ROW --}}
        <div class="footer-logo-row">
            <h2 class="footer-logo-text">SANCAKA LEGAL</h2>
            <div class="footer-social">
                <a href="#"><i class="fab fa-facebook-f"></i></a>
                <a href="#"><i class="fab fa-twitter"></i></a>
                <a href="#"><i class="fab fa-instagram"></i></a>
                <a href="#"><i class="fab fa-pinterest"></i></a>
                <a href="#"><i class="fab fa-tiktok"></i></a>
            </div>
        </div>

        {{-- WIDGETS ROW --}}
        <div class="row">
            {{-- Col 1: News --}}
            <div class="col-md-2 col-6 mb-4">
                <h5 class="f-widget-title">News</h5>
                <ul class="f-links">
                    <li><a href="#">World</a></li>
                    <li><a href="#">UK Politics</a></li>
                    <li><a href="#">EU Politics</a></li>
                    <li><a href="#">Business</a></li>
                    <li><a href="#">Opinions</a></li>
                    <li><a href="#">Science</a></li>
                </ul>
            </div>

            {{-- Col 2: Company --}}
            <div class="col-md-2 col-6 mb-4">
                <h5 class="f-widget-title">Company</h5>
                <ul class="f-links">
                    <li><a href="#">Information</a></li>
                    <li><a href="#">Advertising</a></li>
                    <li><a href="#">Contact Info</a></li>
                    <li><a href="#">Privacy Policy</a></li>
                    <li><a href="#">Terms</a></li>
                </ul>
            </div>

            {{-- Col 3: Services --}}
            <div class="col-md-2 col-6 mb-4">
                <h5 class="f-widget-title">Services</h5>
                <ul class="f-links">
                    <li><a href="#">Subscriptions</a></li>
                    <li><a href="#">Support</a></li>
                    <li><a href="#">Newsletters</a></li>
                    <li><a href="#">Sponsored</a></li>
                </ul>
            </div>

            {{-- Col 4: Subscribe --}}
            <div class="col-md-5 offset-md-1 mb-4">
                <h5 class="f-widget-title">Subscribe to Updates</h5>
                <p class="sub-text">Get the latest creative news from Sancaka about art, design and business.</p>
                <form action="#">
                    <div class="input-group mb-2">
                        <input type="email" class="sub-input" placeholder="Your email address..">
                        <button class="btn-sub-red" type="button">SUBSCRIBE</button>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input bg-dark border-secondary" type="checkbox" id="agreeCheck">
                        <label class="form-check-label small" for="agreeCheck" style="font-size: 11px; color: #888;">
                            By signing up, you agree to our terms and privacy policy.
                        </label>
                    </div>
                </form>
            </div>
        </div>

        {{-- BOTTOM COPYRIGHT --}}
        <div class="footer-bottom">
            <div>
                &copy; {{ date('Y') }} Sancaka Media. Designed by <a href="https://tokosancaka.com">SancakaDev</a>.
            </div>
            <div>
                <a href="#">Privacy Policy</a>
                <a href="#">Terms</a>
                <a href="#">Accessibility</a>
            </div>
        </div>

    </div>
</footer>
