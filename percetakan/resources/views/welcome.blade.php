<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Toko Sancaka - Solusi Percetakan & Fotocopy</title>
    <link rel="icon" type="image/png" href="https://tokosancaka.com/storage/uploads/sancaka.png">
    
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    <style>
        .gradient-text {
            background: linear-gradient(90deg, #4F46E5, #06B6D4);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
    </style>
</head>
<body class="bg-slate-50 font-sans text-slate-900">

    <header class="fixed w-full z-50 bg-white/80 backdrop-blur-md border-b border-slate-200">
        <nav class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-20 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <img src="https://tokosancaka.com/storage/uploads/sancaka.png" alt="Logo" class="h-10 w-10">
                <span class="text-xl font-bold tracking-tight">TOKO <span class="text-indigo-600">SANCAKA</span></span>
            </div>
            
            <div class="hidden md:flex items-center gap-8 text-sm font-medium text-slate-600">
                <a href="#layanan" class="hover:text-indigo-600 transition">Layanan</a>
                <a href="#harga" class="hover:text-indigo-600 transition">Harga</a>
                <a href="#testimoni" class="hover:text-indigo-600 transition">Testimoni</a>
            </div>

            <div class="flex items-center gap-4">
                @if (Route::has('login'))
                    @auth
                        <a href="{{ url('/dashboard') }}" class="text-sm font-semibold text-indigo-600 hover:text-indigo-500">Dashboard</a>
                    @else
                        <a href="{{ route('login') }}" class="text-sm font-semibold text-slate-700 hover:text-slate-900">Masuk</a>
                        <a href="{{ route('register') }}" class="hidden sm:block px-5 py-2.5 bg-indigo-600 text-white text-sm font-bold rounded-full hover:bg-indigo-700 transition shadow-lg shadow-indigo-200">Daftar</a>
                    @endauth
                @endif
            </div>
        </nav>
    </header>

    <section class="pt-32 pb-20 px-4">
        <div class="max-w-7xl mx-auto text-center">
            <span class="inline-block px-4 py-1.5 rounded-full bg-indigo-50 text-indigo-600 text-xs font-bold uppercase tracking-wider mb-6">Tercepat & Berkualitas di Ngawi</span>
            <h1 class="text-5xl md:text-7xl font-extrabold mb-6 tracking-tight">
                Cetak Dokumen Anda <br><span class="gradient-text text-indigo-600">Tanpa Ribet Antri.</span>
            </h1>
            <p class="text-slate-500 text-lg max-w-2xl mx-auto mb-10 leading-relaxed">
                Layanan fotocopy, print warna, hingga jilid kilat dengan kualitas premium. Pesan sekarang, ambil kemudian atau kirim ke alamat Anda.
            </p>
            <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                <a href="https://wa.me/6285745808809" class="w-full sm:w-auto px-8 py-4 bg-green-500 text-white font-bold rounded-2xl hover:bg-green-600 transition-all flex items-center justify-center gap-2 shadow-xl shadow-green-100">
                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.588-5.946 0-6.556 5.332-11.888 11.888-11.888 3.176 0 6.161 1.237 8.404 3.48s3.481 5.229 3.481 8.406c0 6.555-5.332 11.887-11.887 11.887-2.01 0-3.987-.51-5.742-1.47l-6.143 1.614zm5.883-4.088c1.616.96 3.2 1.484 4.93 1.484 5.39 0 9.773-4.382 9.773-9.774 0-2.611-1.015-5.065-2.859-6.909s-4.298-2.859-6.91-2.859c-5.391 0-9.774 4.383-9.774 9.774 0 1.832.51 3.608 1.475 5.122l-1.04 3.801 3.905-1.025z"/></svg>
                    Order via WhatsApp
                </a>
                <a href="#harga" class="w-full sm:w-auto px-8 py-4 bg-white text-slate-700 font-bold rounded-2xl hover:bg-slate-50 transition border border-slate-200">
                    Lihat Price List
                </a>
            </div>
        </div>
    </section>

    <section id="harga" class="py-20 bg-white">
        <div class="max-w-7xl mx-auto px-4">
            <div class="text-center mb-16">
                <h2 class="text-3xl font-bold mb-4">Harga Layanan Unggulan</h2>
                <p class="text-slate-500">Transparan tanpa biaya tambahan tersembunyi.</p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="bg-white border border-slate-200 rounded-3xl p-8 hover:border-indigo-600 transition-all hover:shadow-2xl hover:-translate-y-2 group">
                    <h3 class="text-xl font-bold mb-2">Fotocopy</h3>
                    <p class="text-slate-500 text-sm mb-6">Hitam Putih HVS 70-80gr</p>
                    <div class="mb-8">
                        <span class="text-4xl font-black italic">Rp 250</span>
                        <span class="text-slate-400">/lembar</span>
                    </div>
                    <ul class="space-y-4 mb-8 text-sm text-slate-600">
                        <li class="flex items-center gap-3 font-medium">✅ Kertas HVS Premium</li>
                        <li class="flex items-center gap-3">✅ Hasil Tajam & Pekat</li>
                        <li class="flex items-center gap-3 text-slate-400">❌ Termasuk Jilid</li>
                    </ul>
                    <a href="https://wa.me/6285745808809" class="block text-center py-3 bg-slate-100 text-slate-800 font-bold rounded-xl group-hover:bg-indigo-600 group-hover:text-white transition">Pesan Sekarang</a>
                </div>

                <div class="bg-slate-900 border-2 border-indigo-500 rounded-3xl p-8 shadow-2xl shadow-indigo-200 relative transform md:scale-105">
                    <div class="absolute -top-4 left-1/2 -translate-x-1/2 bg-indigo-500 text-white text-[10px] font-bold px-4 py-1 rounded-full uppercase tracking-widest">Paling Laris</div>
                    <h3 class="text-xl font-bold mb-2 text-white">Print Warna</h3>
                    <p class="text-slate-400 text-sm mb-6">Tinta Ori, Warna Akurat</p>
                    <div class="mb-8 text-white">
                        <span class="text-4xl font-black italic">Rp 1.000</span>
                        <span class="text-slate-500">/lembar</span>
                    </div>
                    <ul class="space-y-4 mb-8 text-sm text-slate-300">
                        <li class="flex items-center gap-3 font-medium">✅ High Resolution Print</li>
                        <li class="flex items-center gap-3 font-medium">✅ Bisa File PDF/Word/JPG</li>
                        <li class="flex items-center gap-3 font-medium">✅ Kertas HVS/Art Paper</li>
                    </ul>
                    <a href="https://wa.me/6285745808809" class="block text-center py-3 bg-indigo-500 text-white font-bold rounded-xl hover:bg-indigo-600 transition">Pesan Sekarang</a>
                </div>

                <div class="bg-white border border-slate-200 rounded-3xl p-8 hover:border-indigo-600 transition-all hover:shadow-2xl hover:-translate-y-2 group">
                    <h3 class="text-xl font-bold mb-2">Jilid Buku</h3>
                    <p class="text-slate-500 text-sm mb-6">Hardcover & Softcover</p>
                    <div class="mb-8">
                        <span class="text-4xl font-black italic">Rp 5.000</span>
                        <span class="text-slate-400">/mulai dari</span>
                    </div>
                    <ul class="space-y-4 mb-8 text-sm text-slate-600">
                        <li class="flex items-center gap-3 font-medium">✅ Jilid Mika / Buffalo</li>
                        <li class="flex items-center gap-3 font-medium">✅ Jilid Lakban Rapih</li>
                        <li class="flex items-center gap-3 font-medium">✅ Proses Cepat 5 Menit</li>
                    </ul>
                    <a href="https://wa.me/6285745808809" class="block text-center py-3 bg-slate-100 text-slate-800 font-bold rounded-xl group-hover:bg-indigo-600 group-hover:text-white transition">Pesan Sekarang</a>
                </div>
            </div>
        </div>
    </section>

    <section id="testimoni" class="py-20 bg-slate-50">
        <div class="max-w-7xl mx-auto px-4">
            <h2 class="text-3xl font-bold text-center mb-16">Apa Kata Mereka?</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div class="bg-white p-8 rounded-2xl border border-slate-100 shadow-sm">
                    <div class="flex text-yellow-400 mb-4 font-bold">★★★★★</div>
                    <p class="text-slate-600 italic mb-6">"Print tugas kuliah di sini hasilnya bagus banget, warna sesuai file. Penjualnya ramah dan sat-set!"</p>
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-indigo-100 rounded-full flex items-center justify-center font-bold text-indigo-600">A</div>
                        <span class="font-bold text-sm">Andini - Mahasiswa</span>
                    </div>
                </div>
                </div>
        </div>
    </section>

    <footer class="bg-white border-t border-slate-200 pt-20 pb-10">
        <div class="max-w-7xl mx-auto px-4 grid grid-cols-1 md:grid-cols-4 gap-12 mb-16">
            <div class="col-span-1 md:col-span-2">
                <img src="https://tokosancaka.com/storage/uploads/sancaka.png" alt="Logo" class="h-12 w-12 mb-6">
                <h3 class="text-xl font-bold mb-4">Toko Sancaka Percetakan</h3>
                <p class="text-slate-500 max-w-sm">Partner cetak terbaik Anda di Ngawi. Melayani dengan sepenuh hati sejak 2024.</p>
            </div>
            <div>
                <h4 class="font-bold mb-6">Navigasi</h4>
                <ul class="space-y-4 text-sm text-slate-500">
                    <li><a href="#" class="hover:text-indigo-600">Beranda</a></li>
                    <li><a href="#harga" class="hover:text-indigo-600">Daftar Harga</a></li>
                    <li><a href="{{ route('login') }}" class="hover:text-indigo-600">Login Admin</a></li>
                </ul>
            </div>
            <div>
                <h4 class="font-bold mb-6">Kontak Kami</h4>
                <p class="text-sm text-slate-500 leading-relaxed">
                    Jl. Raya Ngawi - Solo, Jawa Timur<br>
                    WhatsApp: 085745808809<br>
                    Email: halo@tokosancaka.com
                </p>
            </div>
        </div>
        <div class="max-w-7xl mx-auto px-4 pt-10 border-t border-slate-100 text-center text-slate-400 text-sm font-medium">
            &copy; 2026 Toko Sancaka. Built with ❤️ for Better Printing.
        </div>
    </footer>

</body>
</html>