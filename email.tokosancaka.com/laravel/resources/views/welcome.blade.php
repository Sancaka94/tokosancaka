<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Sancaka Mail (email.tokosancaka.com) — Layanan Email Bisnis & Ekosistem Digital Terpadu dari CV Sancaka Karya Hutama. Terhubung dengan Kirim Paket, Etalase Marketplace, dan Aplikasi Android Sancaka Express.">
    <title>Sancaka Mail — Email Bisnis & Ekosistem Digital Terpadu | Toko Sancaka</title>

    <!-- Favicon & Apple Touch Icon -->
    <link rel="icon" type="image/png" href="https://tokosancaka.com/storage/uploads/sancaka.png">
    <link rel="shortcut icon" type="image/png" href="https://tokosancaka.com/storage/uploads/sancaka.png">
    <link rel="apple-touch-icon" href="https://tokosancaka.com/storage/uploads/sancaka.png">

    <!-- Google Fonts: Plus Jakarta Sans & Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap" rel="stylesheet">

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', '-apple-system', 'BlinkMacSystemFont', 'sans-serif'],
                        display: ['Plus Jakarta Sans', 'Inter', 'sans-serif'],
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-[#f8fafc] text-slate-800 antialiased selection:bg-[#0b57d0] selection:text-white">

    <!-- ==================== HEADER / NAVBAR ==================== -->
    <header class="sticky top-0 z-50 bg-white/90 backdrop-blur-md border-b border-slate-200/80">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16 md:h-20">
                <!-- Brand Logo -->
                <a href="{{ url('/') }}" class="flex items-center gap-3 group focus:outline-none">
                    <img
                        src="https://tokosancaka.com/storage/uploads/sancaka.png"
                        alt="Logo Sancaka"
                        class="h-9 sm:h-10 w-auto object-contain transition-transform duration-200 group-hover:scale-105"
                    >
                    <div class="flex flex-col">
                        <span class="font-display font-extrabold text-base sm:text-lg tracking-tight text-slate-900 leading-none">
                            Sancaka <span class="text-[#0b57d0]">Mail</span>
                        </span>
                        <span class="text-[10px] sm:text-[11px] font-medium text-slate-500 mt-0.5">
                            by CV Sancaka Karya Hutama
                        </span>
                    </div>
                </a>

                <!-- Desktop Navigation Menu -->
                <nav class="hidden lg:flex items-center gap-7 text-sm font-medium text-slate-600">
                    <a href="#keunggulan" class="hover:text-[#0b57d0] transition">Keunggulan</a>
                    <a href="#ekosistem" class="hover:text-[#0b57d0] transition">Ekosistem</a>
                    <a href="#aplikasi" class="hover:text-[#0b57d0] transition">Aplikasi Android</a>
                    <a href="#visi-misi" class="hover:text-[#0b57d0] transition">Visi & Misi</a>
                    <a href="#lokasi" class="hover:text-[#0b57d0] transition">Lokasi</a>
                </nav>

                <!-- Desktop Auth Buttons -->
                <div class="hidden sm:flex items-center gap-3">
                    @if (Route::has('login'))
                        @auth
                            <a
                                href="{{ url('/dashboard') }}"
                                class="inline-flex items-center gap-2 px-5 py-2.5 rounded-full bg-[#0b57d0] hover:bg-[#0842a0] text-white text-sm font-semibold shadow-sm transition"
                            >
                                <span>Buka Kotak Masuk</span>
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                                </svg>
                            </a>
                        @else
                            <a
                                href="{{ route('login') }}"
                                class="px-4 py-2 rounded-full text-sm font-semibold text-slate-700 hover:text-[#0b57d0] hover:bg-slate-100 transition"
                            >
                                Masuk
                            </a>

                            @if (Route::has('register'))
                                <a
                                    href="{{ route('register') }}"
                                    class="inline-flex items-center gap-1.5 px-5 py-2.5 rounded-full bg-[#0b57d0] hover:bg-[#0842a0] text-white text-sm font-semibold shadow-sm transition"
                                >
                                    <span>Daftar Gratis</span>
                                </a>
                            @endif
                        @endauth
                    @endif
                </div>

                <!-- Mobile Hamburger Button -->
                <div class="flex items-center sm:hidden">
                    <button
                        type="button"
                        onclick="toggleMobileMenu()"
                        aria-label="Buka Menu"
                        class="p-2 rounded-xl text-slate-600 hover:text-slate-900 hover:bg-slate-100 focus:outline-none"
                    >
                        <svg id="menuIconOpen" class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                        <svg id="menuIconClose" class="w-6 h-6 hidden" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Mobile Dropdown Drawer -->
        <div id="mobileMenu" class="hidden lg:hidden bg-white border-t border-slate-100 px-4 pt-3 pb-6 space-y-3 shadow-lg">
            <div class="flex flex-col space-y-2 text-sm font-medium text-slate-700">
                <a href="#keunggulan" onclick="toggleMobileMenu()" class="px-3 py-2 rounded-lg hover:bg-slate-50">Keunggulan Sancaka Mail</a>
                <a href="#ekosistem" onclick="toggleMobileMenu()" class="px-3 py-2 rounded-lg hover:bg-slate-50">Ekosistem Toko Sancaka</a>
                <a href="#aplikasi" onclick="toggleMobileMenu()" class="px-3 py-2 rounded-lg hover:bg-slate-50">Aplikasi Android</a>
                <a href="#visi-misi" onclick="toggleMobileMenu()" class="px-3 py-2 rounded-lg hover:bg-slate-50">Visi & Misi Perusahaan</a>
                <a href="#lokasi" onclick="toggleMobileMenu()" class="px-3 py-2 rounded-lg hover:bg-slate-50">Alamat & Peta Lokasi</a>
            </div>

            <div class="pt-3 border-t border-slate-100 flex flex-col gap-2.5">
                @if (Route::has('login'))
                    @auth
                        <a href="{{ url('/dashboard') }}" class="w-full text-center py-2.5 rounded-full bg-[#0b57d0] text-white text-sm font-semibold">
                            Buka Kotak Masuk
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="w-full text-center py-2.5 rounded-full border border-slate-300 text-slate-800 text-sm font-semibold">
                            Masuk ke Akun Sancaka
                        </a>
                        @if (Route::has('register'))
                            <a href="{{ route('register') }}" class="w-full text-center py-2.5 rounded-full bg-[#0b57d0] text-white text-sm font-semibold">
                                Daftar Akun Sekarang
                            </a>
                        @endif
                    @endauth
                @endif
            </div>
        </div>
    </header>

    <!-- ==================== HERO SECTION (HOOK & BOMBASTIS) ==================== -->
    <section class="relative overflow-hidden bg-gradient-to-b from-white via-[#f3f7ff] to-[#f8fafc] pt-12 pb-16 sm:pt-20 sm:pb-24 lg:pt-24 lg:pb-28">
        <!-- Decorative Background Blurs -->
        <div class="pointer-events-none absolute -top-24 left-1/2 -translate-x-1/2 w-[700px] h-[350px] bg-blue-500/10 blur-3xl rounded-full"></div>

        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 lg:gap-10 items-center">
                <!-- Left Copywriting -->
                <div class="lg:col-span-7 text-center lg:text-left">
                    <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-blue-50 border border-blue-200/80 text-[#0b57d0] text-xs font-semibold mb-6">
                        <span class="w-2 h-2 rounded-full bg-[#0b57d0] animate-ping"></span>
                        <span>REVOLUSI KOMUNIKASI BISNIS & LOGISTIK UMKM INDONESIA</span>
                    </div>

                    <h1 class="font-display font-extrabold text-3xl sm:text-5xl lg:text-[54px] text-slate-900 tracking-tight leading-[1.15]">
                        Tinggalkan Cara Lama! Saatnya Bisnis Anda Tampil <span class="text-transparent bg-clip-text bg-gradient-to-r from-[#0b57d0] to-indigo-600">Berkelas & Terpercaya</span> dengan Sancaka Mail.
                    </h1>

                    <p class="mt-5 text-base sm:text-lg text-slate-600 leading-relaxed max-w-2xl mx-auto lg:mx-0">
                        Satu akun untuk menguasai segalanya! Kelola korespondensi bisnis profesional, lacak resi pengiriman paket, kelola toko online di Etalase, hingga pantau transaksi langsung dalam satu genggaman ekosistem <strong class="text-slate-800">Toko Sancaka</strong>.
                    </p>

                    <!-- Primary CTAs -->
                    <div class="mt-8 flex flex-col sm:flex-row items-center justify-center lg:justify-start gap-3.5">
                        @if (Route::has('register'))
                            <a
                                href="{{ route('register') }}"
                                class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-7 py-3.5 rounded-full bg-[#0b57d0] hover:bg-[#0842a0] text-white font-semibold text-sm sm:text-base shadow-md shadow-blue-600/20 transition transform hover:-translate-y-0.5"
                            >
                                <span>Buat Akun Email Sekarang</span>
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                                </svg>
                            </a>
                        @endif

                        <a
                            href="{{ route('login') }}"
                            class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-7 py-3.5 rounded-full bg-white hover:bg-slate-50 text-slate-800 border border-slate-300 font-semibold text-sm sm:text-base transition"
                        >
                            <span>Masuk ke Webmail</span>
                        </a>
                    </div>

                    <!-- Trust Metrics -->
                    <div class="mt-10 pt-6 border-t border-slate-200/80 grid grid-cols-3 gap-4 max-w-lg mx-auto lg:mx-0">
                        <div>
                            <p class="font-display font-extrabold text-xl sm:text-2xl text-slate-900">100%</p>
                            <p class="text-xs text-slate-500 mt-0.5">Terintegrasi Ekosistem</p>
                        </div>
                        <div>
                            <p class="font-display font-extrabold text-xl sm:text-2xl text-[#0b57d0]">24/7</p>
                            <p class="text-xs text-slate-500 mt-0.5">Akses Cepat Multi-Device</p>
                        </div>
                        <div>
                            <p class="font-display font-extrabold text-xl sm:text-2xl text-slate-900">1 Akun</p>
                            <p class="text-xs text-slate-500 mt-0.5">Mail, Paket & Etalase</p>
                        </div>
                    </div>
                </div>

                <!-- Right Preview Mockup -->
                <div class="lg:col-span-5">
                    <div class="bg-white rounded-2xl border border-slate-200/90 shadow-xl shadow-slate-200/60 overflow-hidden">
                        <!-- Fake Browser Top Bar -->
                        <div class="bg-slate-100/80 px-4 py-3 border-b border-slate-200 flex items-center justify-between">
                            <div class="flex items-center gap-1.5">
                                <span class="w-2.5 h-2.5 rounded-full bg-red-400"></span>
                                <span class="w-2.5 h-2.5 rounded-full bg-amber-400"></span>
                                <span class="w-2.5 h-2.5 rounded-full bg-emerald-400"></span>
                            </div>
                            <div class="px-3 py-1 rounded-md bg-white border border-slate-200 text-[11px] font-medium text-slate-500">
                                https://email.tokosancaka.com
                            </div>
                            <div class="w-6"></div>
                        </div>

                        <!-- Simulated Clean Inbox UI -->
                        <div class="p-5 sm:p-6 space-y-4">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2.5">
                                    <img src="https://tokosancaka.com/storage/uploads/sancaka.png" alt="Sancaka" class="w-7 h-7 object-contain">
                                    <span class="font-display font-bold text-sm text-slate-800">Kotak Masuk Prioritas</span>
                                </div>
                                <span class="px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-700 text-[11px] font-semibold">
                                    Aktif & Terenkripsi
                                </span>
                            </div>

                            <!-- Sample Email Item 1 -->
                            <div class="p-3.5 rounded-xl bg-blue-50/70 border border-blue-100">
                                <div class="flex items-center justify-between text-xs">
                                    <span class="font-bold text-slate-900">Sancaka Express Dispatch</span>
                                    <span class="text-[#0b57d0] font-semibold">Baru saja</span>
                                </div>
                                <p class="text-xs font-semibold text-slate-800 mt-1">Invoice & Resi Pengiriman #SCK-2026889 Terbit</p>
                                <p class="text-[11px] text-slate-500 mt-0.5 line-clamp-1">Paket tujuan Jakarta Selatan telah di-pickup kurir dan siap dikirim...</p>
                            </div>

                            <!-- Sample Email Item 2 -->
                            <div class="p-3.5 rounded-xl bg-white border border-slate-200/80">
                                <div class="flex items-center justify-between text-xs">
                                    <span class="font-semibold text-slate-700">Etalase Toko Sancaka</span>
                                    <span class="text-slate-400">10:15 WIB</span>
                                </div>
                                <p class="text-xs font-medium text-slate-800 mt-1">Pesanan Baru Masuk di Toko Anda!</p>
                                <p class="text-[11px] text-slate-500 mt-0.5 line-clamp-1">Pembeli telah menyelesaikan pembayaran instan. Segera proses pesanan...</p>
                            </div>

                            <!-- Sample Email Item 3 -->
                            <div class="p-3.5 rounded-xl bg-white border border-slate-200/80">
                                <div class="flex items-center justify-between text-xs">
                                    <span class="font-semibold text-slate-700">Mitra Korporat & Legalitas</span>
                                    <span class="text-slate-400">Kemarin</span>
                                </div>
                                <p class="text-xs font-medium text-slate-800 mt-1">Penawaran Kerja Sama Pengadaan & Distribusi</p>
                                <p class="text-[11px] text-slate-500 mt-0.5 line-clamp-1">Terima kasih telah menghubungi kami melalui email resmi Sancaka...</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ==================== STORYTELLING & SOFT-SELLING ==================== -->
    <section id="keunggulan" class="py-16 sm:py-24 bg-white border-y border-slate-200/70">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 items-center">
                <!-- Story Narrative -->
                <div class="lg:col-span-6 space-y-5">
                    <span class="text-xs font-bold uppercase tracking-wider text-[#0b57d0]">Cerita di Balik Kepercayaan Pelanggan</span>
                    <h2 class="font-display font-extrabold text-2xl sm:text-4xl text-slate-900 leading-tight">
                        Pernahkah Calon Pembeli Ragu Transaksi Hanya Karena Identitas Bisnis Anda Terlihat Kurang Meyakinkan?
                    </h2>
                    <p class="text-sm sm:text-base text-slate-600 leading-relaxed">
                        Bayangkan Anda sedang menawarkan produk unggulan atau mengirim penawaran kerja sama bernilai jutaan rupiah. Di era digital saat ini, <strong>kesan pertama menentukan keputusan transaksi dalam 5 detik pertama</strong>.
                    </p>
                    <p class="text-sm sm:text-base text-slate-600 leading-relaxed">
                        Banyak pelaku usaha dan seller kehilangan peluang besar bukan karena produknya kurang bagus, melainkan karena komunikasi bisnisnya masih berantakan—invoice tercecer di chat pribadi, notifikasi pengiriman terlewat, dan korespondensi tidak terpusat.
                    </p>
                    <div class="p-5 rounded-2xl bg-[#f6f8fc] border-l-4 border-[#0b57d0]">
                        <p class="text-sm text-slate-700 italic leading-relaxed">
                            "Di Sancaka, kami percaya bahwa setiap UMKM dan pebisnis lokal berhak memiliki infrastruktur komunikasi sekelas perusahaan multinasional—tanpa biaya mahal dan tanpa kerumitan teknis."
                        </p>
                    </div>
                </div>

                <!-- Feature Cards -->
                <div class="lg:col-span-6 grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div class="p-6 rounded-2xl bg-[#f8fafc] border border-slate-200/80 hover:border-blue-300 transition">
                        <div class="w-11 h-11 rounded-xl bg-blue-100 text-[#0b57d0] flex items-center justify-center mb-4">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <h3 class="font-display font-bold text-base text-slate-900">Antarmuka Bersih & Kilat</h3>
                        <p class="text-xs sm:text-sm text-slate-600 mt-2 leading-relaxed">
                            Desain modern tanpa iklan yang mengganggu fokus kerja. Baca, balas, dan kelola arsip email penting dalam hitungan detik.
                        </p>
                    </div>

                    <div class="p-6 rounded-2xl bg-[#f8fafc] border border-slate-200/80 hover:border-blue-300 transition">
                        <div class="w-11 h-11 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center mb-4">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                            </svg>
                        </div>
                        <h3 class="font-display font-bold text-base text-slate-900">Keamanan & Privasi Terjaga</h3>
                        <p class="text-xs sm:text-sm text-slate-600 mt-2 leading-relaxed">
                            Dilengkapi proteksi enkripsi SSL/TLS dan autentikasi terpusat dengan database resmi Toko Sancaka.
                        </p>
                    </div>

                    <div class="p-6 rounded-2xl bg-[#f8fafc] border border-slate-200/80 hover:border-blue-300 transition">
                        <div class="w-11 h-11 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center mb-4">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                            </svg>
                        </div>
                        <h3 class="font-display font-bold text-base text-slate-900">Sinkronisasi Satu Akun</h3>
                        <p class="text-xs sm:text-sm text-slate-600 mt-2 leading-relaxed">
                            Cukup daftar sekali, akun Anda langsung terhubung dengan layanan Kirim Paket, Etalase Marketplace, dan aplikasi mobile.
                        </p>
                    </div>

                    <div class="p-6 rounded-2xl bg-[#f8fafc] border border-slate-200/80 hover:border-blue-300 transition">
                        <div class="w-11 h-11 rounded-xl bg-purple-100 text-purple-700 flex items-center justify-center mb-4">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <h3 class="font-display font-bold text-base text-slate-900">Responsif di Semua Layar</h3>
                        <p class="text-xs sm:text-sm text-slate-600 mt-2 leading-relaxed">
                            Nyaman diakses dari komputer kantor, tablet saat bertemu klien, maupun smartphone di perjalanan.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ==================== EKOSISTEM TOKO SANCAKA ==================== -->
    <section id="ekosistem" class="py-16 sm:py-24 bg-[#f8fafc]">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-3xl mx-auto mb-14">
                <span class="text-xs font-bold uppercase tracking-wider text-[#0b57d0]">Ekosistem Digital Terpadu</span>
                <h2 class="font-display font-extrabold text-2xl sm:text-4xl text-slate-900 mt-2">
                    Lebih dari Sekadar Email — Ini Pusat Kendali Bisnis Anda
                </h2>
                <p class="text-sm sm:text-base text-slate-600 mt-3">
                    Dengan memiliki akun di <strong>email.tokosancaka.com</strong>, Anda otomatis menjadi bagian dari jaringan layanan digital Toko Sancaka yang saling terhubung.
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-7">
                <!-- Card 1: Kirim Paket -->
                <div class="bg-white rounded-2xl border border-slate-200/90 p-7 shadow-sm hover:shadow-md transition flex flex-col justify-between">
                    <div>
                        <div class="w-12 h-12 rounded-2xl bg-blue-50 text-[#0b57d0] flex items-center justify-center mb-5">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                            </svg>
                        </div>
                        <h3 class="font-display font-bold text-xl text-slate-900">Kirim Paket Multi-Ekspedisi</h3>
                        <p class="text-sm text-slate-600 mt-3 leading-relaxed">
                            Bandingkan tarif ongkir dari berbagai kurir terkemuka, cetak resi otomatis, jemput paket ke rumah (pick-up), dan pantau status pengiriman secara real-time.
                        </p>
                    </div>
                    <div class="mt-6 pt-5 border-t border-slate-100">
                        <a
                            href="https://tokosancaka.com/kirim"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="inline-flex items-center gap-2 text-sm font-semibold text-[#0b57d0] hover:text-[#0842a0] group"
                        >
                            <span>Kirim Paket Sekarang</span>
                            <svg class="w-4 h-4 transition-transform group-hover:translate-x-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                            </svg>
                        </a>
                    </div>
                </div>

                <!-- Card 2: Marketplace Etalase -->
                <div class="bg-white rounded-2xl border border-slate-200/90 p-7 shadow-sm hover:shadow-md transition flex flex-col justify-between">
                    <div>
                        <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center mb-5">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                            </svg>
                        </div>
                        <h3 class="font-display font-bold text-xl text-slate-900">Marketplace Etalase Sancaka</h3>
                        <p class="text-sm text-slate-600 mt-3 leading-relaxed">
                            Jual dan temukan ribuan produk pilihan langsung dari pelaku usaha terpercaya. Dilengkapi sistem pembayaran instan (QRIS, Virtual Account, E-Wallet) dan ongkir terintegrasi.
                        </p>
                    </div>
                    <div class="mt-6 pt-5 border-t border-slate-100">
                        <a
                            href="https://tokosancaka.com/etalase"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="inline-flex items-center gap-2 text-sm font-semibold text-emerald-600 hover:text-emerald-700 group"
                        >
                            <span>Jelajahi Etalase Produk</span>
                            <svg class="w-4 h-4 transition-transform group-hover:translate-x-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                            </svg>
                        </a>
                    </div>
                </div>

                <!-- Card 3: Blog & Edukasi -->
                <div class="bg-white rounded-2xl border border-slate-200/90 p-7 shadow-sm hover:shadow-md transition flex flex-col justify-between">
                    <div>
                        <div class="w-12 h-12 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center mb-5">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/>
                            </svg>
                        </div>
                        <h3 class="font-display font-bold text-xl text-slate-900">Blog & Wawasan Bisnis</h3>
                        <p class="text-sm text-slate-600 mt-3 leading-relaxed">
                            Dapatkan artikel strategi pemasaran digital, tips manajemen logistik efisien, informasi perizinan usaha, serta kabar inovasi terbaru dari tim Sancaka.
                        </p>
                    </div>
                    <div class="mt-6 pt-5 border-t border-slate-100">
                        <a
                            href="https://tokosancaka.com/blog"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="inline-flex items-center gap-2 text-sm font-semibold text-amber-600 hover:text-amber-700 group"
                        >
                            <span>Baca Artikel Terbaru</span>
                            <svg class="w-4 h-4 transition-transform group-hover:translate-x-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                            </svg>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ==================== APLIKASI ANDROID SANCAKA EXPRESS ==================== -->
    <section id="aplikasi" class="py-16 sm:py-24 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="rounded-3xl bg-gradient-to-br from-[#0b57d0] via-[#0a49b0] to-slate-900 text-white p-8 sm:p-12 lg:p-16 shadow-xl overflow-hidden relative">
                <!-- Background Accent -->
                <div class="pointer-events-none absolute -right-20 -bottom-20 w-96 h-96 bg-white/10 rounded-full blur-3xl"></div>

                <div class="relative grid grid-cols-1 lg:grid-cols-12 gap-10 items-center">
                    <!-- Left Content -->
                    <div class="lg:col-span-7 text-center lg:text-left">
                        <span class="inline-block px-3.5 py-1 rounded-full bg-white/15 text-blue-100 text-xs font-semibold tracking-wide mb-4">
                            TERSEDIA DI GOOGLE PLAY STORE
                        </span>
                        <h2 class="font-display font-extrabold text-2xl sm:text-4xl lg:text-[40px] leading-tight">
                            Kendalikan Pengiriman & Bisnis Langsung dari Smartphone Android Anda!
                        </h2>
                        <p class="mt-4 text-sm sm:text-base text-blue-100/90 leading-relaxed max-w-2xl mx-auto lg:mx-0">
                            Unduh aplikasi resmi <strong>Sancaka Express</strong> di Google Play Store. Cek ongkir instan, order kurir penjemputan, top-up saldo, belanja di etalase, dan nikmati notifikasi real-time di mana saja Anda berada.
                        </p>

                        <ul class="mt-6 space-y-2.5 text-xs sm:text-sm text-blue-50 max-w-md mx-auto lg:mx-0 text-left">
                            <li class="flex items-center gap-2.5">
                                <svg class="w-5 h-5 text-emerald-300 shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                </svg>
                                <span>Login menggunakan akun yang sama dengan Sancaka Mail</span>
                            </li>
                            <li class="flex items-center gap-2.5">
                                <svg class="w-5 h-5 text-emerald-300 shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                </svg>
                                <span>Lacak posisi paket & riwayat transaksi secara langsung</span>
                            </li>
                            <li class="flex items-center gap-2.5">
                                <svg class="w-5 h-5 text-emerald-300 shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                </svg>
                                <span>Ringan, cepat, dan mendukung pembayaran digital lengkap</span>
                            </li>
                        </ul>

                        <!-- Google Play Button -->
                        <div class="mt-8 flex flex-col sm:flex-row items-center justify-center lg:justify-start gap-4">
                            <a
                                href="https://play.google.com/store/apps/details?id=com.sancaka.express.app&hl=id"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="inline-flex items-center gap-3.5 px-6 py-3.5 rounded-2xl bg-slate-950 hover:bg-black border border-white/20 text-white shadow-lg transition transform hover:-translate-y-0.5"
                            >
                                <!-- Google Play Icon -->
                                <svg class="w-7 h-7 shrink-0" viewBox="0 0 512 512" fill="currentColor">
                                    <path fill="#4CAF50" d="M48 59.49v393a14.16 14.16 0 0 0 22.14 11.7L285.8 256 70.14 47.79A14.16 14.16 0 0 0 48 59.49z"/>
                                    <path fill="#2196F3" d="M353.44 188.36L70.14 47.79 285.8 256z"/>
                                    <path fill="#FFEB3B" d="M453.39 238.13l-99.95-49.77L285.8 256l67.64 67.64 99.95-49.77c14.15-7.05 14.15-28.69 0-35.74z"/>
                                    <path fill="#F44336" d="M70.14 464.21l283.3-140.57L285.8 256z"/>
                                </svg>
                                <div class="text-left">
                                    <div class="text-[10px] uppercase tracking-wider text-slate-300 leading-none">Unduh Sekarang di</div>
                                    <div class="font-display font-bold text-base sm:text-lg leading-tight mt-0.5">Google Play</div>
                                </div>
                            </a>
                        </div>
                    </div>

                    <!-- Right Mobile App Card Mockup -->
                    <div class="lg:col-span-5 flex justify-center">
                        <div class="w-full max-w-[290px] bg-white text-slate-800 rounded-[2rem] p-5 shadow-2xl border-4 border-slate-900/40">
                            <div class="flex items-center justify-between border-b border-slate-100 pb-3.5">
                                <div class="flex items-center gap-2.5">
                                    <img src="https://tokosancaka.com/storage/uploads/sancaka.png" alt="App Sancaka" class="w-8 h-8 object-contain">
                                    <div>
                                        <p class="font-display font-bold text-xs text-slate-900">Sancaka Express</p>
                                        <p class="text-[10px] text-emerald-600 font-semibold">Official Android App</p>
                                    </div>
                                </div>
                                <span class="px-2 py-0.5 rounded-full bg-blue-50 text-[#0b57d0] text-[10px] font-bold">v2.0</span>
                            </div>

                            <div class="mt-4 p-3.5 rounded-2xl bg-gradient-to-r from-[#0b57d0] to-blue-600 text-white">
                                <p class="text-[10px] text-blue-100">Satu Akun Terintegrasi</p>
                                <p class="font-display font-bold text-sm mt-0.5">Kirim Paket & Kelola Toko</p>
                                <p class="text-[11px] text-blue-100 mt-1">Cek ongkir, pickup kurir, & notifikasi email otomatis.</p>
                            </div>

                            <div class="mt-4 grid grid-cols-2 gap-2.5 text-center">
                                <div class="p-3 rounded-xl bg-slate-50 border border-slate-100">
                                    <p class="font-display font-bold text-xs text-slate-800">Cek Ongkir</p>
                                    <p class="text-[10px] text-slate-500 mt-0.5">Semua Kurir</p>
                                </div>
                                <div class="p-3 rounded-xl bg-slate-50 border border-slate-100">
                                    <p class="font-display font-bold text-xs text-slate-800">Lacak Resi</p>
                                    <p class="text-[10px] text-slate-500 mt-0.5">Real-Time</p>
                                </div>
                                <div class="p-3 rounded-xl bg-slate-50 border border-slate-100">
                                    <p class="font-display font-bold text-xs text-slate-800">Etalase</p>
                                    <p class="text-[10px] text-slate-500 mt-0.5">Belanja & Jual</p>
                                </div>
                                <div class="p-3 rounded-xl bg-slate-50 border border-slate-100">
                                    <p class="font-display font-bold text-xs text-slate-800">Sancaka Mail</p>
                                    <p class="text-[10px] text-slate-500 mt-0.5">Tersinkron</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ==================== VISI & MISI SANCAKA EXPRESS ==================== -->
    <section id="visi-misi" class="py-16 sm:py-24 bg-[#f8fafc] border-t border-slate-200/70">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-3xl mx-auto mb-14">
                <span class="text-xs font-bold uppercase tracking-wider text-[#0b57d0]">Komitmen & Arah Langkah Kami</span>
                <h2 class="font-display font-extrabold text-2xl sm:text-4xl text-slate-900 mt-2">
                    Visi & Misi Sancaka Express
                </h2>
                <p class="text-sm sm:text-base text-slate-600 mt-3">
                    Dikelola oleh <strong>CV Sancaka Karya Hutama</strong> sebagai wujud dedikasi dalam membangun infrastruktur logistik, perdagangan digital, dan komunikasi bisnis yang andal.
                </p>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-stretch">
                <!-- Visi Card -->
                <div class="lg:col-span-5 bg-white rounded-2xl border border-slate-200/90 p-8 shadow-sm flex flex-col justify-between">
                    <div>
                        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-blue-50 text-[#0b57d0] text-xs font-bold mb-5">
                            VISI PERUSAHAAN
                        </div>
                        <h3 class="font-display font-extrabold text-xl sm:text-2xl text-slate-900 leading-snug">
                            Menjadi Ekosistem Logistik, Perdagangan Digital, dan Komunikasi Bisnis Terdepan yang Memberdayakan UMKM Indonesia.
                        </h3>
                        <p class="mt-4 text-sm text-slate-600 leading-relaxed">
                            Kami membangun jembatan teknologi yang menghubungkan kecepatan pengiriman barang, kemudahan transaksi marketplace, dan kredibilitas komunikasi digital dalam satu platform yang inklusif bagi seluruh lapisan masyarakat.
                        </p>
                    </div>
                    <div class="mt-8 pt-5 border-t border-slate-100 flex items-center gap-3">
                        <img src="https://tokosancaka.com/storage/uploads/sancaka.png" alt="Logo CV Sancaka Karya Hutama" class="h-9 w-auto object-contain">
                        <div>
                            <p class="text-xs font-bold text-slate-900">CV Sancaka Karya Hutama</p>
                            <p class="text-[11px] text-slate-500">Sancaka Express &bull; Toko Sancaka</p>
                        </div>
                    </div>
                </div>

                <!-- Misi Card -->
                <div class="lg:col-span-7 bg-white rounded-2xl border border-slate-200/90 p-8 shadow-sm">
                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-50 text-emerald-700 text-xs font-bold mb-5">
                        MISI KAMI
                    </div>
                    <div class="space-y-4">
                        <div class="flex items-start gap-4">
                            <span class="w-7 h-7 rounded-full bg-[#0b57d0] text-white font-display font-bold text-xs flex items-center justify-center shrink-0 mt-0.5">1</span>
                            <div>
                                <h4 class="font-display font-bold text-base text-slate-900">Menghadirkan Layanan Pengiriman Cepat, Aman & Transparan</h4>
                                <p class="text-xs sm:text-sm text-slate-600 mt-1 leading-relaxed">
                                    Menyediakan solusi pengiriman paket multi-ekspedisi dengan pelacakan real-time dan tarif kompetitif untuk mendukung kelancaran distribusi bisnis.
                                </p>
                            </div>
                        </div>

                        <div class="flex items-start gap-4">
                            <span class="w-7 h-7 rounded-full bg-[#0b57d0] text-white font-display font-bold text-xs flex items-center justify-center shrink-0 mt-0.5">2</span>
                            <div>
                                <h4 class="font-display font-bold text-base text-slate-900">Menyediakan Infrastruktur Email & Identitas Bisnis Profesional</h4>
                                <p class="text-xs sm:text-sm text-slate-600 mt-1 leading-relaxed">
                                    Memfasilitasi pelaku usaha dan mitra dengan layanan surat elektronik modern yang cepat, aman, dan terintegrasi langsung dengan sistem transaksi.
                                </p>
                            </div>
                        </div>

                        <div class="flex items-start gap-4">
                            <span class="w-7 h-7 rounded-full bg-[#0b57d0] text-white font-display font-bold text-xs flex items-center justify-center shrink-0 mt-0.5">3</span>
                            <div>
                                <h4 class="font-display font-bold text-base text-slate-900">Mendorong Pertumbuhan Ekonomi Lokal Melalui Digitalisasi</h4>
                                <p class="text-xs sm:text-sm text-slate-600 mt-1 leading-relaxed">
                                    Membuka akses pasar yang lebih luas melalui platform Etalase Marketplace dan pendampingan operasional yang mudah digunakan oleh siapa saja.
                                </p>
                            </div>
                        </div>

                        <div class="flex items-start gap-4">
                            <span class="w-7 h-7 rounded-full bg-[#0b57d0] text-white font-display font-bold text-xs flex items-center justify-center shrink-0 mt-0.5">4</span>
                            <div>
                                <h4 class="font-display font-bold text-base text-slate-900">Inovasi Teknologi Berkelanjutan & Pelayanan Sepenuh Hati</h4>
                                <p class="text-xs sm:text-sm text-slate-600 mt-1 leading-relaxed">
                                    Terus mengembangkan aplikasi web dan mobile berbasis kebutuhan nyata pelanggan dengan dukungan layanan responsif.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ==================== ALAMAT KANTOR & EMBED GOOGLE MAPS ==================== -->
    <section id="lokasi" class="py-16 sm:py-24 bg-white border-t border-slate-200/70">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-10 items-center">
                <!-- Contact & Address Info -->
                <div class="lg:col-span-5 space-y-6">
                    <div>
                        <span class="text-xs font-bold uppercase tracking-wider text-[#0b57d0]">Kantor Pusat & Operasional</span>
                        <h2 class="font-display font-extrabold text-2xl sm:text-3xl text-slate-900 mt-2">
                            Hubungi & Kunjungi Kami
                        </h2>
                        <p class="text-sm text-slate-600 mt-2 leading-relaxed">
                            Kami siap melayani kebutuhan pengiriman paket, kerja sama bisnis, dan dukungan layanan ekosistem digital Sancaka.
                        </p>
                    </div>

                    <div class="space-y-4">
                        <div class="flex items-start gap-3.5">
                            <div class="w-10 h-10 rounded-xl bg-blue-50 text-[#0b57d0] flex items-center justify-center shrink-0">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                            </div>
                            <div>
                                <h3 class="font-display font-bold text-sm text-slate-900">Alamat Kantor Pusat</h3>
                                <p class="text-xs sm:text-sm text-slate-600 mt-1 leading-relaxed">
                                    <strong>CV Sancaka Karya Hutama (Sancaka Express)</strong><br>
                                    Jl. Dr. Wahidin No. 18A, RT 22 / RW 05, Kel. Ketanggi,<br>
                                    Kec. Ngawi, Kabupaten Ngawi, Jawa Timur 63211
                                </p>
                            </div>
                        </div>

                        <div class="flex items-start gap-3.5">
                            <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                </svg>
                            </div>
                            <div>
                                <h3 class="font-display font-bold text-sm text-slate-900">Email Resmi & Portal Web</h3>
                                <p class="text-xs sm:text-sm text-slate-600 mt-1">
                                    Email: <a href="mailto:admin@tokosancaka.com" class="text-[#0b57d0] font-medium hover:underline">admin@tokosancaka.com</a><br>
                                    Portal Utama: <a href="https://tokosancaka.com" target="_blank" rel="noopener noreferrer" class="text-[#0b57d0] font-medium hover:underline">tokosancaka.com</a>
                                </p>
                            </div>
                        </div>

                        <div class="flex items-start gap-3.5">
                            <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center shrink-0">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <div>
                                <h3 class="font-display font-bold text-sm text-slate-900">Jam Operasional Kantor</h3>
                                <p class="text-xs sm:text-sm text-slate-600 mt-1">
                                    Senin – Sabtu: 08.00 – 20.00 WIB<br>
                                    Layanan Webmail & Aplikasi: 24 Jam Nonstop
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Google Maps Embed -->
                <div class="lg:col-span-7">
                    <div class="rounded-2xl overflow-hidden border border-slate-200 shadow-sm bg-slate-100 h-[320px] sm:h-[400px]">
                        <iframe
                            title="Lokasi Kantor CV Sancaka Karya Hutama - Ngawi"
                            src="https://maps.google.com/maps?q=Jl.%20Dr.%20Wahidin%20No.%2018A,%20Ketanggi,%20Ngawi,%20Jawa%20Timur&t=&z=16&ie=UTF8&iwloc=&output=embed"
                            class="w-full h-full border-0"
                            allowfullscreen=""
                            loading="lazy"
                            referrerpolicy="no-referrer-when-downgrade"
                        ></iframe>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ==================== FINAL CTA BANNER ==================== -->
    <section class="py-14 bg-[#f6f8fc] border-t border-slate-200/80">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <img src="https://tokosancaka.com/storage/uploads/sancaka.png" alt="Logo Sancaka" class="h-12 w-auto mx-auto mb-4 object-contain">
            <h2 class="font-display font-extrabold text-2xl sm:text-3xl text-slate-900">
                Siap Meningkatkan Profesionalitas Bisnis Anda Hari Ini?
            </h2>
            <p class="mt-3 text-sm sm:text-base text-slate-600 max-w-xl mx-auto">
                Daftarkan akun Anda sekarang secara gratis dan nikmati kemudahan komunikasi email yang terintegrasi penuh dengan layanan Toko Sancaka.
            </p>
            <div class="mt-7 flex flex-col sm:flex-row items-center justify-center gap-3.5">
                @if (Route::has('register'))
                    <a href="{{ route('register') }}" class="w-full sm:w-auto px-8 py-3.5 rounded-full bg-[#0b57d0] hover:bg-[#0842a0] text-white font-semibold text-sm shadow-sm transition">
                        Daftar Akun Sancaka Gratis
                    </a>
                @endif
                <a href="{{ route('login') }}" class="w-full sm:w-auto px-8 py-3.5 rounded-full bg-white hover:bg-slate-50 border border-slate-300 text-slate-800 font-semibold text-sm transition">
                    Masuk ke Sancaka Mail
                </a>
            </div>
        </div>
    </section>

    <!-- ==================== FOOTER ==================== -->
    <footer class="bg-slate-900 text-slate-400 pt-14 pb-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8 pb-10 border-b border-slate-800">
                <!-- Col 1 -->
                <div class="space-y-3">
                    <div class="flex items-center gap-2.5">
                        <img src="https://tokosancaka.com/storage/uploads/sancaka.png" alt="Logo Sancaka" class="h-8 w-auto object-contain bg-white rounded-lg p-1">
                        <span class="font-display font-bold text-white text-base">Sancaka Mail</span>
                    </div>
                    <p class="text-xs leading-relaxed text-slate-400">
                        Layanan surat elektronik resmi dan gerbang ekosistem digital CV Sancaka Karya Hutama (Sancaka Express &amp; Toko Sancaka).
                    </p>
                </div>

                <!-- Col 2 -->
                <div>
                    <h4 class="font-display font-bold text-white text-sm mb-3">Layanan Utama</h4>
                    <ul class="space-y-2 text-xs">
                        <li><a href="{{ route('login') }}" class="hover:text-white transition">Masuk Webmail</a></li>
                        @if (Route::has('register'))
                            <li><a href="{{ route('register') }}" class="hover:text-white transition">Daftar Akun Baru</a></li>
                        @endif
                        <li><a href="https://tokosancaka.com/kirim" target="_blank" rel="noopener noreferrer" class="hover:text-white transition">Kirim Paket Multi-Ekspedisi</a></li>
                        <li><a href="https://tokosancaka.com/etalase" target="_blank" rel="noopener noreferrer" class="hover:text-white transition">Marketplace Etalase</a></li>
                    </ul>
                </div>

                <!-- Col 3 -->
                <div>
                    <h4 class="font-display font-bold text-white text-sm mb-3">Informasi & Aplikasi</h4>
                    <ul class="space-y-2 text-xs">
                        <li><a href="https://tokosancaka.com/blog" target="_blank" rel="noopener noreferrer" class="hover:text-white transition">Blog & Artikel Bisnis</a></li>
                        <li><a href="https://play.google.com/store/apps/details?id=com.sancaka.express.app&hl=id" target="_blank" rel="noopener noreferrer" class="hover:text-white transition">Unduh Aplikasi Android</a></li>
                        <li><a href="#visi-misi" class="hover:text-white transition">Visi & Misi Sancaka Express</a></li>
                        <li><a href="#lokasi" class="hover:text-white transition">Lokasi Kantor Pusat</a></li>
                    </ul>
                </div>

                <!-- Col 4 -->
                <div>
                    <h4 class="font-display font-bold text-white text-sm mb-3">Kantor Pusat</h4>
                    <p class="text-xs leading-relaxed text-slate-400">
                        Jl. Dr. Wahidin No. 18A, RT 22 / RW 05, Ketanggi, Kec. Ngawi, Kab. Ngawi, Jawa Timur 63211
                    </p>
                    <p class="text-xs text-slate-400 mt-2">
                        Web: <a href="https://tokosancaka.com" target="_blank" rel="noopener noreferrer" class="text-blue-400 hover:underline">tokosancaka.com</a>
                    </p>
                </div>
            </div>

            <div class="pt-6 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs text-slate-500">
                <p>&copy; {{ date('Y') }} CV Sancaka Karya Hutama. Seluruh hak cipta dilindungi.</p>
                <p>email.tokosancaka.com &bull; Sancaka Express</p>
            </div>
        </div>
    </footer>

    <script>
        function toggleMobileMenu() {
            const menu = document.getElementById('mobileMenu');
            const iconOpen = document.getElementById('menuIconOpen');
            const iconClose = document.getElementById('menuIconClose');

            menu.classList.toggle('hidden');
            iconOpen.classList.toggle('hidden');
            iconClose.classList.toggle('hidden');
        }
    </script>
</body>
</html>
