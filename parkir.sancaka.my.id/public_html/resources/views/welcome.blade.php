<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>sancakaPARKIR - Solusi Aplikasi Parkir Terpercaya</title>
    <link rel="icon" href="{{ asset('storage/uploads/sancaka.png') }}" type="image/png">
    <link rel="shortcut icon" href="{{ asset('storage/uploads/sancaka.png') }}" type="image/png">
    <link rel="apple-touch-icon" href="{{ asset('storage/uploads/sancaka.png') }}">

    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <style>
        /* Menghapus gradasi dan mengganti dengan warna solid biru */
        .bg-sancaka-blue { background-color: #2563eb; }
        .text-sancaka-blue { color: #2563eb; }
    </style>
</head>
<body class="bg-white font-sans text-slate-900">

    <header class="fixed w-full z-50 bg-white/90 backdrop-blur-md border-b border-slate-100">
        <nav class="max-w-7xl mx-auto px-4 h-20 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <img src="https://tokosancaka.com/storage/uploads/sancaka.png" alt="Logo" class="h-10 w-10">
                <span class="text-xl font-black tracking-tighter uppercase">sancaka<span class="text-blue-600">PARKIR</span></span>
            </div>

            <div class="hidden md:flex items-center gap-8 text-sm font-bold text-slate-500 uppercase tracking-widest">
                <a href="#layanan" class="hover:text-blue-600 transition">Layanan</a>
                <a href="#ekspedisi" class="hover:text-blue-600 transition">Sewa</a>
                <a href="#lokasi" class="hover:text-blue-600 transition">Lokasi</a>
                <a href="{{ route('affiliate.create') }}" class="hover:text-blue-600 transition">Join</a>
            </div>

            <div class="flex items-center gap-4">
                @auth
                    <a href="{{ url('/dashboard') }}" class="px-6 py-2 bg-blue-600 text-white rounded-full font-bold text-sm shadow-lg shadow-blue-200">Panel Admin</a>
                @else
                    <a href="{{ route('daftar.parkir') }}" class="text-sm font-bold text-slate-700 hover:text-blue-600">Buka Tenant</a>

                    <a href="{{ route('daftar.parkir') }}" class="px-6 py-2 border-2 border-blue-600 text-blue-600 rounded-full font-bold text-sm hover:bg-blue-600 hover:text-white transition">
                        Daftar Gratis
                    </a>
                @endauth
            </div>
        </nav>
    </header>

    <section class="pt-40 pb-20 bg-slate-50">
        <div class="max-w-7xl mx-auto px-4 flex flex-col lg:flex-row items-center gap-12">
            <div class="lg:w-1/2 text-left">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-blue-100 text-blue-700 text-xs font-bold mb-6">
                    <span class="relative flex h-2 w-2">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-blue-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2 w-2 bg-blue-500"></span>
                    </span>
                    Tersedia Versi Desktop & Mobile
                </div>
                <h1 class="text-5xl lg:text-7xl font-black leading-none mb-6">
                    Jual Beli & <br><span class="text-blue-600">Sewa Aplikasi</span> Parkir Modern.
                </h1>
                <p class="text-slate-500 text-lg mb-10 max-w-lg">
                    Kelola area parkir Anda lebih mudah dengan sancakaPARKIR. Platform SaaS Parkir terpadu untuk segala jenis area, mulai dari ruko hingga gedung bertingkat.
                </p>
                <div class="flex flex-wrap gap-4">
                    <a href="{{ route('daftar.parkir') }}" class="px-8 py-4 bg-blue-600 text-white font-bold rounded-2xl shadow-xl shadow-blue-200 flex items-center gap-3 hover:scale-105 transition">
                        🚀 Mulai Bisnis Sekarang
                    </a>
                    <a href="https://wa.me/6285745808809" class="flex items-center gap-3 px-6 py-4 bg-white rounded-2xl border border-slate-200 shadow-sm hover:bg-slate-50 transition">
                        <span class="font-bold text-slate-600">Hubungi Admin</span>
                    </a>
                </div>
            </div>
            <div class="lg:w-1/2 relative">
                <img src="https://tokosancaka.com/storage/uploads/sancaka.png" class="w-64 h-64 mx-auto animate-bounce duration-[3000ms] opacity-20 absolute top-0 left-0 right-0" alt="">
                <div class="relative bg-white p-8 rounded-[40px] shadow-2xl border border-slate-100">
                    <h3 class="font-bold text-blue-600 uppercase tracking-widest text-xs mb-4">Fitur sancakaPARKIR</h3>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="p-4 bg-slate-50 rounded-2xl text-center hover:bg-blue-50 transition cursor-pointer">
                            <span class="block text-2xl mb-2">💻</span>
                            <span class="text-xs font-bold">Aplikasi Parkir</span>
                        </div>
                        <div class="p-4 bg-slate-50 rounded-2xl text-center hover:bg-blue-50 transition cursor-pointer">
                            <span class="block text-2xl mb-2">📊</span>
                            <span class="text-xs font-bold">Laporan Real-time</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="harga" class="py-24 bg-white">
        <div class="max-w-7xl mx-auto px-4">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="border border-slate-100 rounded-[32px] p-8 hover:shadow-2xl transition">
                    <h3 class="text-xl font-bold mb-4">Paket Basic</h3>
                    <div class="flex items-baseline gap-1 mb-6">
                        <span class="text-4xl font-black italic">GRATIS</span>
                    </div>
                    <ul class="space-y-4 mb-8 text-sm font-medium">
                        <li>✅ Manajemen Kendaraan</li>
                        <li>✅ Laporan Keuangan</li>
                        <li>✅ Subdomain Sendiri</li>
                        <li>✅ Integrasi DANA/QRIS</li>
                    </ul>
                    <a href="{{ route('daftar.parkir') }}" class="block py-3 text-center rounded-xl bg-slate-100 font-bold hover:bg-blue-600 hover:text-white transition">Daftar Sekarang</a>
                </div>

                <div id="ekspedisi" class="bg-blue-600 rounded-[32px] p-8 text-white shadow-2xl shadow-blue-200 transform md:-translate-y-6">
                    <h3 class="text-xl font-bold mb-4">Sewa sancakaPARKIR</h3>
                    <div class="flex items-baseline gap-1 mb-6">
                        <span class="text-4xl font-black italic text-white">Langganan</span>
                    </div>
                    <ul class="space-y-4 mb-8 text-sm font-medium text-blue-100">
                        <li>✅ Support 24/7</li>
                        <li>✅ Update Fitur Berkala</li>
                        <li>✅ Full Custom Domain</li>
                        <li>✅ Training Operator</li>
                    </ul>
                    <a href="https://wa.me/628819435180" class="block py-4 text-center rounded-xl bg-white text-blue-600 font-black shadow-lg hover:bg-slate-100">HUBUNGI WA</a>
                </div>

                <div class="border border-slate-100 rounded-[32px] p-8 hover:shadow-2xl transition">
                    <h3 class="text-xl font-bold mb-4">Beli Lisensi</h3>
                    <div class="flex items-baseline gap-1 mb-6">
                        <span class="text-4xl font-black italic">Full</span>
                    </div>
                    <ul class="space-y-4 mb-8 text-sm font-medium">
                        <li>✅ Sekali Bayar Selamanya</li>
                        <li>✅ Instalasi di Server Sendiri</li>
                        <li>✅ Tanpa Biaya Bulanan</li>
                        <li>✅ Source Code Opsional</li>
                    </ul>
                    <a href="https://wa.me/6285745808809" class="block py-3 text-center rounded-xl bg-slate-100 font-bold hover:bg-blue-600 hover:text-white transition">Tanya Harga</a>
                </div>
            </div>
        </div>
    </section>

    <section id="lokasi" class="py-20 bg-slate-50">
        <div class="max-w-7xl mx-auto px-4 grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
            <div>
                <h2 class="text-3xl font-bold mb-6 italic">Pusat Operasional</h2>
                <div class="space-y-6">
                    <div class="flex gap-4">
                        <div class="w-12 h-12 rounded-full bg-white shadow-sm flex items-center justify-center flex-shrink-0 text-2xl">📍</div>
                        <p class="text-slate-600 leading-relaxed">
                            <strong>Alamat:</strong> Jalan Dokter Wahidin No.18A, RT.22/RW.05, Sidomakmur, Ketanggi, Ngawi, Jawa Timur 63211
                        </p>
                    </div>
                    <div class="flex gap-4">
                        <div class="w-12 h-12 rounded-full bg-white shadow-sm flex items-center justify-center flex-shrink-0 text-2xl">📞</div>
                        <div>
                            <p class="text-slate-600"><strong>WhatsApp:</strong> 0857-4580-8809</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="h-96 bg-slate-200 rounded-[40px] overflow-hidden shadow-2xl border-4 border-white relative">
                <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3957.514486503468!2d111.4421!3d-7.4048!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0x0!2zN8KwMjQnMTcuMyJTIDExMcKwMjYnMzEuNiJF!5e0!3m2!1sid!2sid!4v1620000000000" width="100%" height="100%" style="border:0;" allowfullscreen="" loading="lazy"></iframe>
            </div>
        </div>
    </section>

    <footer class="bg-white pt-20 pb-10 border-t border-slate-100">
        <div class="max-w-7xl mx-auto px-4 text-center">
            <h3 class="text-2xl font-black italic mb-2 uppercase text-blue-600">sancakaPARKIR</h3>
            <p class="text-slate-400 mb-8 max-w-sm mx-auto">Solusi Sistem Parkir & SaaS Terpercaya.</p>
            <div class="text-slate-300 text-[10px] font-bold tracking-[0.2em] uppercase">
                © {{ date('Y') }} sancakaPARKIR
            </div>
        </div>
    </footer>

</body>
</html>
