{{-- File: resources/views/emails/otp_login.blade.php --}}
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Kode OTP Login Sancaka Express</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            background-color: #f4f6f9;
            margin: 0;
            padding: 0;
        }
        .email-container {
            max-width: 600px;
            margin: 40px auto;
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.05);
            overflow: hidden;
        }
        .email-header {
            background-color: #dc3545; /* Merah ala Sancaka */
            padding: 25px;
            text-align: center;
            color: #ffffff;
        }
        .email-header h2 {
            margin: 0;
            font-size: 24px;
            letter-spacing: 1px;
        }
        .email-body {
            padding: 30px;
            color: #333333;
            line-height: 1.6;
        }
        .otp-box {
            background-color: #f8f9fa;
            border: 2px dashed #dc3545;
            color: #dc3545;
            text-align: center;
            font-size: 36px;
            font-weight: bold;
            letter-spacing: 8px;
            padding: 20px;
            margin: 25px 0;
            border-radius: 8px;
        }
        .btn-container {
            text-align: center;
            margin-top: 20px;
            margin-bottom: 20px;
        }
        .btn {
            display: inline-block;
            padding: 12px 30px;
            background-color: #dc3545;
            color: #ffffff !important;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            font-size: 16px;
        }
        .email-footer {
            background-color: #f8f9fa;
            padding: 20px;
            text-align: center;
            font-size: 13px;
            color: #777777;
            border-top: 1px solid #eeeeee;
        }
        .text-muted {
            color: #6c757d;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="email-header">
            <h2>SANCAKA EXPRESS</h2>
        </div>
        <div class="email-body">
            <p>Halo <strong>{{ $namaLengkap }}</strong>,</p>
            <p>Sistem kami mendeteksi upaya login ke akun Sancaka Anda. Berikut adalah kode verifikasi (OTP) Anda. <strong>Jangan bagikan kode ini kepada siapa pun.</strong></p>

            <div class="otp-box">
                {{ $otpCode }}
            </div>

            <p style="text-align: center;">Atau, Anda dapat masuk secara otomatis dengan menekan tombol di bawah ini:</p>

            <div class="btn-container">
                <a href="{{ $otpLink }}" class="btn">Verifikasi Otomatis</a>
            </div>

            <p class="text-muted" style="margin-top: 30px; text-align: center;">
                Kode ini hanya berlaku selama <strong>1 menit</strong>.<br>
                Jika Anda tidak merasa melakukan aktivitas login, mohon abaikan email ini untuk menjaga keamanan akun Anda.
            </p>
        </div>
        <div class="email-footer">
            &copy; {{ date('Y') }} Sancaka Express. All Rights Reserved.<br>
            <small>Solusi pengiriman paket cepat, aman, dan terpercaya.</small>
        </div>
    </div>
</body>
</html>
