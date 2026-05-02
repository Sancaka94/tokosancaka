<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar - Sancaka Express</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons (Untuk Icon Mata) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <style>
        body { background-color: #f8f9fa; }
        .input-group-text { cursor: pointer; }
    </style>
</head>
<body class="d-flex align-items-center justify-content-center min-vh-100 py-4">

    <div class="card shadow-sm border-0" style="width: 100%; max-width: 450px;">
        <div class="card-body p-4 p-md-5">
            <h4 class="card-title text-center mb-4 fw-bold">Daftar Akun Baru</h4>

            <form method="POST" action="{{ route('register') }}">
                @csrf

                <!-- LOG LOG - Nama Lengkap -->
                <div class="mb-3">
                    <label for="name" class="form-label small fw-semibold">Nama Lengkap</label>
                    <input id="name" type="text" class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name') }}" required autofocus autocomplete="name" placeholder="Masukkan nama anda">
                    @error('name')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror
                </div>

                <!-- LOG LOG - Email Address -->
                <div class="mb-3">
                    <label for="email" class="form-label small fw-semibold">Email</label>
                    <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" required autocomplete="username" placeholder="Masukkan email aktif">
                    @error('email')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror
                </div>

                <!-- LOG LOG - Password Pertama dengan Icon Mata -->
                <div class="mb-3">
                    <label for="password" class="form-label small fw-semibold">Password</label>
                    <div class="input-group">
                        <input id="password" type="password" class="form-control @error('password') is-invalid @enderror" name="password" required autocomplete="new-password" placeholder="Buat password baru">
                        
                        <span class="input-group-text bg-white toggle-password" data-target="password">
                            <i class="bi bi-eye-slash eye-icon"></i>
                        </span>

                        @error('password')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>
                </div>

                <!-- LOG LOG - Konfirmasi Password dengan Icon Mata -->
                <div class="mb-4">
                    <label for="password_confirmation" class="form-label small fw-semibold">Konfirmasi Password</label>
                    <div class="input-group">
                        <input id="password_confirmation" type="password" class="form-control @error('password_confirmation') is-invalid @enderror" name="password_confirmation" required autocomplete="new-password" placeholder="Ulangi password di atas">
                        
                        <span class="input-group-text bg-white toggle-password" data-target="password_confirmation">
                            <i class="bi bi-eye-slash eye-icon"></i>
                        </span>

                        @error('password_confirmation')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>
                </div>

                <!-- LOG LOG - Tombol Aksi (Daftar & Login) -->
                <div class="d-grid gap-2 mt-2">
                    <button type="submit" class="btn btn-dark py-2 fw-semibold">
                        Daftar Sekarang
                    </button>
                    
                    <a href="{{ route('login') }}" class="btn btn-outline-secondary py-2 fw-semibold mt-2">
                        Sudah punya akun? Log in
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Bootstrap JS & Custom Script -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // LOG LOG - Script JavaScript Dinamis untuk beberapa fitur Show/Hide Password sekaligus
        const toggleButtons = document.querySelectorAll('.toggle-password');

        toggleButtons.forEach(button => {
            button.addEventListener('click', function () {
                // Mencari target input berdasarkan atribut data-target
                const targetId = this.getAttribute('data-target');
                const passwordInput = document.getElementById(targetId);
                const eyeIcon = this.querySelector('.eye-icon');

                // Ubah tipe input
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);
                
                // Ganti icon
                eyeIcon.classList.toggle('bi-eye-slash');
                eyeIcon.classList.toggle('bi-eye');
            });
        });
    </script>
</body>
</html>