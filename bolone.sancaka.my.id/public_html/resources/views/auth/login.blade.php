<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sancaka Express</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons (Untuk Icon Mata) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <style>
        body { background-color: #f8f9fa; }
        .input-group-text { cursor: pointer; }
    </style>
</head>
<body class="d-flex align-items-center justify-content-center vh-100">

    <div class="card shadow-sm border-0" style="width: 100%; max-width: 420px;">
        <div class="card-body p-5">
            <h4 class="card-title text-center mb-4 fw-bold">Log In</h4>

            <!-- LOG LOG - Menampilkan Pesan Status -->
            @if (session('status'))
                <div class="alert alert-success mb-4 text-sm" role="alert">
                    {{ session('status') }}
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}">
                @csrf

                <!-- LOG LOG - Email Address -->
                <div class="mb-3">
                    <label for="email" class="form-label small fw-semibold">Email</label>
                    <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" required autofocus autocomplete="username" placeholder="Masukkan email anda">
                    @error('email')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror
                </div>

                <!-- LOG LOG - Password dengan Icon Mata -->
                <div class="mb-3">
                    <label for="password" class="form-label small fw-semibold">Password</label>
                    <div class="input-group">
                        <input id="password" type="password" class="form-control @error('password') is-invalid @enderror" name="password" required autocomplete="current-password" placeholder="Masukkan password">
                        
                        <!-- Tombol Icon Mata -->
                        <span class="input-group-text bg-white" id="togglePassword">
                            <i class="bi bi-eye-slash" id="eyeIcon"></i>
                        </span>

                        @error('password')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>
                </div>

                <!-- Remember Me & Forgot Password -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div class="form-check">
                        <input class="form-check-input shadow-none" type="checkbox" name="remember" id="remember_me">
                        <label class="form-check-label text-secondary small" for="remember_me">
                            Ingat saya
                        </label>
                    </div>
                    @if (Route::has('password.request'))
                        <a class="text-decoration-none text-dark small fw-semibold" href="{{ route('password.request') }}">
                            Lupa password?
                        </a>
                    @endif
                </div>

                <!-- LOG LOG - Tombol Aksi (Login & Daftar) -->
                <div class="d-grid gap-2 mt-2">
                    <button type="submit" class="btn btn-dark py-2 fw-semibold">
                        Log in
                    </button>
                    
                    @if (Route::has('register'))
                        <a href="{{ route('register') }}" class="btn btn-outline-secondary py-2 fw-semibold">
                            Daftar Akun Baru
                        </a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <!-- Bootstrap JS & Custom Script -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // LOG LOG - Script JavaScript untuk fitur Show/Hide Password
        const togglePassword = document.querySelector('#togglePassword');
        const password = document.querySelector('#password');
        const eyeIcon = document.querySelector('#eyeIcon');

        togglePassword.addEventListener('click', function () {
            // Ubah tipe input dari 'password' ke 'text' atau sebaliknya
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);
            
            // Ganti icon dari mata tertutup (eye-slash) ke mata terbuka (eye)
            eyeIcon.classList.toggle('bi-eye-slash');
            eyeIcon.classList.toggle('bi-eye');
        });
    </script>
</body>
</html>