<header>
    <nav class="navbar navbar-expand-lg fixed-top navbar-light bg-light shadow-sm">
        <div class="container">
            <a class="navbar-brand" href="{{ route('home') }}">
                <img src="{{ asset('images/logo.png') }}" alt="Sancaka Express Logo" style="max-height: 40px;">
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#marketplaceNavbar" aria-controls="marketplaceNavbar" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="marketplaceNavbar">
                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item"><a class="nav-link" href="{{ route('home') }}">Beranda</a></li>
                    <li class="nav-item"><a class="nav-link active" href="{{ route('products.index') }}">Etalase</a></li>
                    <li class="nav-item me-3"><a class="nav-link" href="#"><i class="fas fa-shopping-cart"></i></a></li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="accountDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false"><i class="fas fa-user-circle me-1"></i> Akun</a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="accountDropdown">
                            @auth
                                <li><a class="dropdown-item" href="#">Dashboard</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><form action="{{ route('logout') }}" method="POST">@csrf<button type="submit" class="dropdown-item">Logout</button></form></li>
                            @else
                                <li><a class="dropdown-item" href="{{ route('login') }}">Login</a></li>
                                <li><a class="dropdown-item" href="{{ route('register') }}">Register</a></li>
                            @endauth
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
</header>
