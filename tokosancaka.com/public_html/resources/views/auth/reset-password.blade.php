<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Sancaka Express</title>
    
    {{-- CSS Libraries --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        body, html {
            height: 100%;
            margin: 0;
            font-family: 'Poppins', sans-serif;
            background-color: #f8f9fa; /* Warna background abu-abu bersih */
        }
        .auth-wrapper {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 2rem 1rem;
        }
        .auth-card {
            max-width: 500px;
            width: 100%;
            border: none;
            border-radius: 1rem;
        }
        .auth-logo {
            max-height: 50px;
        }
        .btn-primary-custom {
            background-color: #0d6efd; /* Menyesuaikan warna tombol Simpan & Login di screenshot kamu */
            border-color: #0d6efd;
            padding: 0.75rem 1rem;
            font-weight: 500;
            border-radius: 0.5rem;
            transition: all 0.3s ease;
        }
        .btn-primary-custom:hover {
            background-color: #0b5ed7;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(13, 110, 253, 0.3);
        }
        .form-control:focus {
            border-color: #0d6efd;
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
        }
        .password-toggle-icon {
            position: absolute;
            top: 50%;
            right: 1rem;
            transform: translateY(-50%);
            cursor: pointer;
            color: #6c757d;
            z-index: 10;
        }
        
        /* Jika kamu pakai 6 kotak OTP, ini CSS tambahannya agar rapi */
        .otp-boxes {
            display: flex;
            justify-content: space-between;
            gap: 10px;
            margin-bottom: 1.5rem;
        }
        .otp-box {
            width: 50px;
            height: 50px;
            text-align: center;
            font-size: 1.5rem;
            font-weight: bold;
            border: 1px solid #ced4da;
            border-radius: 0.5rem;
        }
        .otp-box:focus {
            border-color: #0d6efd;
            outline: none;
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
        }
    </style>
</head>
<body>

<div class="auth-wrapper">
    <div class="container bg-white shadow p-4 p-lg-5 auth-card">
        <div class="text-center mb-4">
            <a href="{{ url('/') }}">
                <img src="{{ asset('storage/uploads/sancaka.png') }}" alt="Logo Sancaka Express" class="auth-logo mb-2" onerror="this.src='https://placehold.co/150x50?text=Sancaka'">
            </a>
            <h4 class="fw-bold mt-2">Buat Password Baru</h4>
            <p class="text-muted small">Silakan masukkan Kode OTP dan password baru untuk akun Anda.</p>
        </div>

        {{-- Menampilkan Pesan Status --}}
        @if (session('status'))
            <div class="alert alert-success py-2 small mb-3">
                <i class="fas fa-check-circle me-1"></i> {{ session('status') }}
            </div>
        @endif

        {{-- Menampilkan Error Validasi --}}
        @if ($errors->any())
            <div class="alert alert-danger py-2 small mb-3">
                <ul class="mb-0 ps-3">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('password.otp.submit') }}">
            @csrf

            {{-- Identifier Hidden (Biar UI bersih, karena identifier sudah ada di URL) --}}
            <input type="hidden" name="identifier" value="{{ request('identifier') }}">

            {{-- Input OTP (Saya sesuaikan dengan konsep 6 kotak di screenshot kamu) --}}
            <div class="mb-2">
                <label class="form-label small fw-bold text-muted">Kode OTP (6 Digit)</label>
                {{-- Kita bungkus 6 input ini menjadi 1 array atau kita gabung via JS nanti --}}
                <div class="otp-boxes">
                    <input type="text" class="otp-box" maxlength="1" autofocus>
                    <input type="text" class="otp-box" maxlength="1">
                    <input type="text" class="otp-box" maxlength="1">
                    <input type="text" class="otp-box" maxlength="1">
                    <input type="text" class="otp-box" maxlength="1">
                    <input type="text" class="otp-box" maxlength="1">
                </div>
                {{-- Input aslinya yang dikirim ke backend disembunyikan --}}
                <input type="hidden" name="otp" id="real-otp-input" required>
            </div>

            {{-- Password Baru --}}
            <div class="mb-3 position-relative">
                <label class="form-label small fw-bold text-muted">Password Baru</label>
                <input type="password" class="form-control form-control-lg @error('password') is-invalid @enderror" id="password" name="password" required>
                <i class="fas fa-eye password-toggle-icon" onclick="togglePasswordVisibility('password')" style="top: 70%;"></i>
            </div>

            {{-- Konfirmasi Password Baru --}}
            <div class="mb-4 position-relative">
                <label class="form-label small fw-bold text-muted">Konfirmasi Password Baru</label>
                <input type="password" class="form-control form-control-lg @error('password_confirmation') is-invalid @enderror" id="password_confirmation" name="password_confirmation" required>
                <i class="fas fa-eye password-toggle-icon" onclick="togglePasswordVisibility('password_confirmation')" style="top: 70%;"></i>
            </div>

            {{-- Submit Button --}}
            <div class="d-grid mb-2">
                <button type="submit" class="btn btn-primary-custom text-white btn-lg" onclick="combineOtp()">
                    Simpan & Login
                </button>
            </div>
        </form>
        
        <p class="text-center text-muted small mt-4 mb-0">&copy; {{ date('Y') }} Sancaka Express. All Rights Reserved.</p>
    </div>
</div>

<script>
    // Fitur Hide/Show Password
    function togglePasswordVisibility(fieldId) {
        const input = document.getElementById(fieldId);
        const icon = input.parentElement.querySelector('.password-toggle-icon');

        if (input.type === "password") {
            input.type = "text";
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            input.type = "password";
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    }

    // JS Khusus untuk 6 Kotak OTP (Pindah otomatis saat ngetik)
    const otpBoxes = document.querySelectorAll('.otp-box');
    otpBoxes.forEach((box, index) => {
        box.addEventListener('input', function() {
            // Ubah jadi huruf besar semua
            this.value = this.value.toUpperCase();
            // Pindah ke kotak selanjutnya jika sudah diisi
            if (this.value.length === 1 && index < otpBoxes.length - 1) {
                otpBoxes[index + 1].focus();
            }
        });
        
        box.addEventListener('keydown', function(e) {
            // Pindah ke kotak sebelumnya jika menekan Backspace dan kotak kosong
            if (e.key === 'Backspace' && this.value === '' && index > 0) {
                otpBoxes[index - 1].focus();
            }
        });
    });

    // Menggabungkan 6 kotak OTP menjadi 1 string saat tombol submit ditekan
    function combineOtp() {
        let otpValue = '';
        otpBoxes.forEach(box => {
            otpValue += box.value;
        });
        document.getElementById('real-otp-input').value = otpValue;
    }
</script>

</body>
</html>