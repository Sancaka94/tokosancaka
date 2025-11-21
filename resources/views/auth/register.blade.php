@extends('layouts.app')



{{-- Menyisipkan style kustom ke dalam <head> di layout.

     Style ini sama persis dengan halaman login untuk konsistensi. --}}

@push('styles')

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />

<style>

    body, html {

        height: 100%;

        margin: 0;

        font-family: 'Poppins', sans-serif;

        background-color: #f8f9fa; /* Latar belakang abu-abu muda */

    }

    .auth-wrapper {

        display: flex;

        justify-content: center;

        align-items: center;

        min-height: 100vh;

        padding: 2rem 1rem;

    }

    .auth-card {

        max-width: 1100px;

        width: 100%;

        border: none;

    }

    .auth-logo {

        max-height: 50px;

    }

    .btn-danger {

        background-color: #dc3545;

        border-color: #dc3545;

        padding: 0.75rem 1rem;

        font-weight: 500;

        transition: all 0.3s ease;

    }

    .btn-danger:hover {

        background-color: #c82333;

        border-color: #bd2130;

        transform: translateY(-2px);

        box-shadow: 0 4px 15px rgba(220, 53, 69, 0.3);

    }

    .partner-logos-grid {

        display: grid;

        grid-template-columns: repeat(auto-fit, minmax(100px, 1fr));

        gap: 1.5rem;

        align-items: center;

    }

    .partner-logo img {

        max-height: 45px;

        width: 100%;

        object-fit: contain;

        filter: grayscale(100%);

        opacity: 0.6;

        transition: all 0.3s ease;

    }

    .partner-logo img:hover {

        filter: grayscale(0%);

        opacity: 1;

        transform: scale(1.05);

    }

    .form-control:focus {

        border-color: #dc3545;

        box-shadow: 0 0 0 0.25rem rgba(220, 53, 69, 0.25);

    }

    .password-toggle-icon {

        position: absolute;

        top: 50%;

        right: 1rem;

        transform: translateY(-50%);

        cursor: pointer;

        color: #6c757d;

    }

    .auth-card {
    margin-top: 5px; /* biar tidak ketutup header */
}


</style>

@endpush





@section('content')

<div class="auth-wrapper">

    <div class="container bg-white rounded-4 shadow p-4 p-lg-5 auth-card">

        <div class="row align-items-center g-5">



            <!-- Kolom Form Registrasi -->

            <div class="col-lg-6 border-end-lg pe-lg-5">

                <div class="text-center mb-4">

                    <a href="{{ url('/') }}">

                        <img src="https://tokosancaka.biz.id/storage/uploads/sancaka.png" alt="Sancaka Express Logo" class="auth-logo mb-2">

                    </a>

                    <h3 class="fw-bold">Buat Akun Baru</h3>

                    <p class="text-muted">Daftar dan nikmati kemudahan pengiriman.</p>

                </div>



                {{-- PENAMBAHAN: Alert untuk Pendaftaran Sukses --}}

                @if (session('success'))

                    <div class="alert alert-success d-flex align-items-center" role="alert">

                        <i class="fas fa-check-circle me-2"></i>

                        <div>

                            {{ session('success') }}

                        </div>

                    </div>

                @endif



                {{-- Alert Gagal (Error Validasi) --}}

                @if ($errors->any())

                    <div class="alert alert-danger py-2">

                        <ul class="mb-0 small">

                            @foreach ($errors->all() as $error)

                                <li>{{ $error }}</li>

                            @endforeach

                        </ul>

                    </div>

                @endif



                {{-- ✅ PERBAIKAN: Menggunakan nama route 'register' yang benar --}}

                <form action="{{ route('register') }}" method="POST">

                    @csrf

                    <div class="row g-3">

                        <div class="col-md-6">

                            <div class="form-floating">

                                {{-- ✅ PERBAIKAN: Mengganti name menjadi 'nama_lengkap' agar sesuai validator --}}

                                <input type="text" class="form-control" id="nama_lengkap" name="nama_lengkap" placeholder="Nama Lengkap" value="{{ old('nama_lengkap') }}" required>

                                <label for="nama_lengkap">Nama Lengkap</label>

                            </div>

                        </div>

                        <div class="col-md-6">

                            <div class="form-floating">

                                <input type="text" class="form-control" id="store_name" name="store_name" placeholder="Nama Toko" value="{{ old('store_name') }}" required>

                                <label for="store_name">Nama Toko</label>

                            </div>

                        </div>

                        <div class="col-12">

                            <div class="form-floating">

                                <input type="email" class="form-control" id="email" name="email" placeholder="nama@contoh.com" value="{{ old('email') }}" required>

                                <label for="email">Alamat Email</label>

                            </div>

                        </div>

                         <div class="col-12">

                            <div class="form-floating">

                                <input type="text" class="form-control" id="no_wa" name="no_wa" placeholder="08xxxxxxxxxx" value="{{ old('no_wa') }}" required>

                                <label for="no_wa">Nomor WhatsApp Aktif</label>

                            </div>

                        </div>

                        <div class="col-md-6">

                            <div class="form-floating position-relative">

                                <input type="password" class="form-control" id="password" name="password" placeholder="Password" required>

                                <label for="password">Password</label>

                                <i class="fas fa-eye password-toggle-icon" onclick="togglePasswordVisibility('password')"></i>

                            </div>

                        </div>

                        <div class="col-md-6">

                            <div class="form-floating position-relative">

                                <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" placeholder="Konfirmasi Password" required>

                                <label for="password_confirmation">Konfirmasi Password</label>

                                <i class="fas fa-eye password-toggle-icon" onclick="togglePasswordVisibility('password_confirmation')"></i>

                            </div>

                        </div>

                    </div>



                    <div class="d-grid mt-4">

                        <button type="submit" class="btn btn-danger btn-lg">Daftar Sekarang</button>

                    </div>



                    <p class="text-center mt-4 mb-0">

                        {{-- ✅ PERBAIKAN: Menggunakan nama route 'login' yang benar --}}

                        Sudah punya akun? <a href="{{ route('login') }}" class="fw-bold text-danger text-decoration-none">Masuk di sini</a>

                    </p>

                </form>

            </div>



            <!-- Kolom Logo Partner -->

            <div class="col-lg-6 text-center d-none d-lg-block">

                <h5 class="mb-4 text-muted fw-normal">Didukung oleh Ekspedisi Terpercaya</h5>

                <div class="partner-logos-grid px-lg-4">

                    @php

                        $partners = [

        'J&T Express' => 'https://upload.wikimedia.org/wikipedia/commons/0/01/J%26T_Express_logo.svg',

        'JNE' => 'https://upload.wikimedia.org/wikipedia/commons/9/92/New_Logo_JNE.png',

        'POS Indonesia' => 'https://kiriminaja.com/assets/home-v4/pos.png',

        'Indah Cargo' => 'https://blogger.googleusercontent.com/img/b/R29vZ2xl/AVvXsEicOAaLoH2eElQ93_gbkzhvk4dRhWVlk5wQsGgilihIB58321aHchlJLdjyz1ToS25P_nWrHJ_E4QBiW_OVlI7tQt7cZ5I0HZqk6StS7jZltLVvDXp2d5ZDLB9yklhV4x6z2iXyURURDv_unhf-U6vyiD_8to9OC4PBwMwyU_5wAqOiCl6tKiaTA-ri1Q/s851/Logo%20Indah%20Logistik%20Cargo@0.5x.png',

        'SAP Express' => 'https://kiriminaja.com/assets/home-v4/sap.png',

        'ID Express' => 'https://assets.bukalapak.com/beagle/images/courier_logo/id-express.png',

        'J&T Cargo' => 'https://i.pinimg.com/736x/22/cf/92/22cf92368c1f901d17e38e99061f4849.jpg',

        'Lion Parcel' => 'https://kiriminaja.com/assets/home-v4/lion.png',

        'SPX Express' => 'https://images.seeklogo.com/logo-png/49/1/spx-express-indonesia-logo-png_seeklogo-499970.png',

        'Sicepat' => 'https://kiriminaja.com/assets/home-v4/sicepat.png',

        'NCS Kurir' => 'https://blogger.googleusercontent.com/img/b/R29vZ2xl/AVvXsEjxj3iyyZEjK2L4A4yCIr_E-4W3hF2lk_yb-t0Oj2oFPErCPCMHie5LHqps02xMb6sNa-Gqz5NSX_P_hzWlYpUpJUlCD4iN6_QxiSG9fzY4bsZ9XvLFDn7HCiORtNvIlPfuQbSSdW96p7x7uN8ek3FWyHW9c2bznrFBQkoLd5A9sVAFVKWLfUhT3Dxh/s320/GKL41_NCS%20Kurir%20-%20Koleksilogo.com.jpg',

        'Ninja Express' => 'https://kiriminaja.com/assets/home-v4/ninja.png',

        'Anteraja' => 'https://kiriminaja.com/assets/home-v4/anter-aja.png',

        'TIKI' => 'https://kiriminaja.com/assets/home-v4/tiki.png',

        'Sentral Cargo' => 'https://kiriminaja.com/assets/home-v4/central-cargo.png',

        'Borzo' => 'https://kiriminaja.com/assets/home-v4/borzo.png',

        'GoSend' => 'https://kiriminaja.com/assets/home-v4/gosend.png',

        'GrabExpress' => 'https://kiriminaja.com/assets/home-v4/grab.svg',

    ];

                    @endphp

                    @foreach ($partners as $name => $logoUrl)

                    <div class="partner-logo">

                        <img src="{{ $logoUrl }}" alt="Logo {{ $name }}">

                    </div>

                    @endforeach

                </div>

            </div>



        </div>

        <p class="text-center text-muted small mt-5 mb-0">&copy; {{ date('Y') }} Sancaka Express. All Rights Reserved.</p>

    </div>

</div>

@endsection





{{-- Menyisipkan script ke bagian bawah <body> di layout --}}

@push('scripts')

<script>

    function togglePasswordVisibility(fieldId) {

        const input = document.getElementById(fieldId);

        const icon = input.nextElementSibling;

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

</script>

@endpush

