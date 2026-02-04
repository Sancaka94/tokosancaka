<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Toko Sancaka - Percetakan & Ekspedisi Murah Ngawi</title>
    <link rel="icon" href="https://tokosancaka.com/storage/uploads/sancaka.png" type="image/png">
    <link rel="shortcut icon" href="https://tokosancaka.com/storage/uploads/sancaka.png" type="image/png">

    <link rel="apple-touch-icon" href="https://tokosancaka.com/storage/uploads/sancaka.png">

    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <style>
        .gradient-red { background: linear-gradient(135deg, #ef4444 0%, #b91c1c 100%); }
        .text-gradient { background: linear-gradient(90deg, #4F46E5, #06B6D4); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
    </style>
</head>
<body class="bg-white font-sans text-slate-900">

    <header class="fixed w-full z-50 bg-white/90 backdrop-blur-md border-b border-slate-100">
        <nav class="max-w-7xl mx-auto px-4 h-20 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <img src="https://tokosancaka.com/storage/uploads/sancaka.png" alt="Logo" class="h-10 w-10">
                <span class="text-xl font-black tracking-tighter uppercase">Sancaka<span class="text-red-600">Group</span></span>
            </div>

            <div class="hidden md:flex items-center gap-8 text-sm font-bold text-slate-500 uppercase tracking-widest">
                <a href="#layanan" class="hover:text-red-600 transition">Layanan</a>
                <a href="https://tokosancaka.com/buat-pesanan" class="hover:text-red-600 transition">Ekspedisi</a>
                <a href="#lokasi" class="hover:text-red-600 transition">Lokasi</a>
                <a href="{{ url('/join-partner') }}" class="hover:text-red-600 transition">Join</a>
                <a href="{{ url('/orders/create') }}"
                    class="ml-4 inline-flex items-center
                            px-4 py-2
                            text-sm font-semibold
                            text-white
                            border border-red-600
                            rounded-md
                            hover:text-slate-100 hover:bg-red-700 bg-red-600
                            focus:ring-2 focus:ring-green-600
                            transition">
                    🚀 Order Sekarang!
                </a>

            </div>

            <div class="flex items-center gap-4">
                @auth
                    <a href="{{ url('/dashboard') }}" class="px-6 py-2 bg-red-600 text-white rounded-full font-bold text-sm shadow-lg shadow-red-200">Panel Admin</a>
                @else
                    <a href="{{ route('member.login') }}" class="text-sm font-bold text-slate-700">Login</a>
                    <a href="{{ url('/join-partner') }}" class="px-6 py-2 border-2 border-red-600 text-red-600 rounded-full font-bold text-sm hover:bg-red-600 hover:text-white transition">Daftar</a>
                @endauth
            </div>
        </nav>
    </header>

    <section class="pt-40 pb-20 bg-slate-50">
        <div class="max-w-7xl mx-auto px-4 flex flex-col lg:flex-row items-center gap-12">
            <div class="lg:w-1/2 text-left">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-red-100 text-red-700 text-xs font-bold mb-6">
                    <span class="relative flex h-2 w-2">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2 w-2 bg-red-500"></span>
                    </span>
                    Buka Sampai 21:00 WIB
                </div>
                <h1 class="text-5xl lg:text-7xl font-black leading-none mb-6">
                    Percetakan & <br><span class="text-gradient">Ekspedisi Murah</span> di Ngawi.
                </h1>
                <p class="text-slate-500 text-lg mb-10 max-w-lg">
                    Layanan terpadu cetak dokumen, fotocopy kilat, hingga pengiriman paket ke seluruh Indonesia dengan tarif termurah.
                </p>
                <div class="flex flex-wrap gap-4">
                    <a href="https://wa.me/6285745808809" class="px-8 py-4 gradient-red text-white font-bold rounded-2xl shadow-xl shadow-red-200 flex items-center gap-3">
                        Order via WhatsApp
                    </a>
                    <div class="flex items-center gap-3 px-6 py-4 bg-white rounded-2xl border border-slate-200 shadow-sm">
                        <div class="flex text-yellow-400 text-xl font-black">4.9 ★</div>
                        <div class="text-[10px] uppercase font-bold text-slate-400">Google Rating<br>56 Ulasan</div>
                    </div>
                </div>
            </div>
            <div class="lg:w-1/2 relative">
                <img src="https://tokosancaka.com/storage/uploads/sancaka.png" class="w-64 h-64 mx-auto animate-bounce duration-[3000ms] opacity-20 absolute top-0 left-0 right-0" alt="">
                <div class="relative bg-white p-8 rounded-[40px] shadow-2xl border border-slate-100">
                    <h3 class="font-bold text-red-600 uppercase tracking-widest text-xs mb-4">Layanan Ekspedisi Kami</h3>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="p-4 bg-slate-50 rounded-2xl text-center">
                            <span class="block text-2xl mb-2">📦</span>
                            <span class="text-xs font-bold">Kirim Paket</span>
                        </div>
                        <div class="p-4 bg-slate-50 rounded-2xl text-center">
                            <span class="block text-2xl mb-2">🚚</span>
                            <span class="text-xs font-bold">Cargo Murah</span>
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
                    <h3 class="text-xl font-bold mb-4">Fotocopy HVS</h3>
                    <div class="flex items-baseline gap-1 mb-6">
                        <span class="text-4xl font-black italic">Rp 250</span>
                        <span class="text-slate-400">/lbr</span>
                    </div>
                    <ul class="space-y-4 mb-8 text-sm font-medium">
                        <li>✅ Kertas 70-80gr</li>
                        <li>✅ Hasil Copy Tajam</li>
                        <li>✅ Grosir Lebih Murah</li>
                        <li>✅ Potensi Diskon 20-30%</li>
                        <li>✅ Potensi Komisi 10%</li>
                    </ul>
                    <a href="https://wa.me/6285745808809" class="block py-3 text-center rounded-xl bg-slate-100 font-bold hover:bg-red-600 hover:text-white transition">Pesan</a>
                </div>
                <div id="ekspedisi" class="gradient-red rounded-[32px] p-8 text-white shadow-2xl shadow-red-200 transform md:-translate-y-6">
                    <h3 class="text-xl font-bold mb-4">Jasa Pengiriman Paket</h3>
                    <div class="flex items-baseline gap-1 mb-6">
                        <span class="text-4xl font-black italic text-cyan-300">Ekspedisi</span>
                    </div>
                    <ul class="space-y-4 mb-8 text-sm font-medium text-red-100">
                        <li>✅ Ekspedisi Terdekat Ngawi</li>
                        <li>✅ Tarif Termurah & Aman</li>
                        <li>✅ Pickup Paket ke Rumah</li>
                        <li>✅ Tracking Real-time</li>
                        <li>✅ Paket Barang & Dokumen</li>
                        <li>✅ Paket Motor & Pindahan</li>
                    </ul>
                    <a href="https://wa.me/628819435180" class="block py-4 text-center rounded-xl bg-white text-red-600 font-black shadow-lg">HUBUNGI EKSPEDISI</a>
                </div>
                <div class="border border-slate-100 rounded-[32px] p-8 hover:shadow-2xl transition">
                    <h3 class="text-xl font-bold mb-4">Jilid & Print</h3>
                    <div class="flex items-baseline gap-1 mb-6">
                        <span class="text-4xl font-black italic">Mulai Rp 300</span>
                        <span class="text-slate-400">/ Hal</span>
                    </div>
                    <ul class="space-y-4 mb-8 text-sm font-medium">
                        <li>✅ Print Warna High Res</li>
                        <li>✅ Jilid Mika / Buffalo</li>
                        <li>✅ Jilid Lakban Kilat</li>
                        <li>✅ Print Bolak Balik</li>
                        <li>✅ Potensi Diskon 20-30%</li>
                    </ul>
                    <a href="https://wa.me/6285745808809" class="block py-3 text-center rounded-xl bg-slate-100 font-bold hover:bg-red-600 hover:text-white transition">Pesan</a>
                </div>
            </div>
        </div>
    </section>

    <section id="lokasi" class="py-20 bg-slate-50">
        <div class="max-w-7xl mx-auto px-4 grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
            <div>
                <h2 class="text-3xl font-bold mb-6 italic">Kunjungi Outlet Kami</h2>
                <div class="space-y-6">
                    <div class="flex gap-4">
                        <div class="w-12 h-12 rounded-full bg-white shadow-sm flex items-center justify-center flex-shrink-0 text-2xl">📍</div>
                        <p class="text-slate-600 leading-relaxed">
                            <strong>Alamat:</strong> Jalan Dokter Wahidin No.18A, RT.22/RW.05, Sidomakmur, Ketanggi, Kec. Ngawi, Kabupaten Ngawi, Jawa Timur 63211
                        </p>
                    </div>
                    <div class="flex gap-4">
                        <div class="w-12 h-12 rounded-full bg-white shadow-sm flex items-center justify-center flex-shrink-0 text-2xl">📞</div>
                        <div>
                            <p class="text-slate-600"><strong>Telepon Ekspedisi:</strong> 0881-9435-180</p>
                            <p class="text-slate-600"><strong>Telepon Percetakan:</strong> 0857-4580-8809</p>
                        </div>
                    </div>
                    <div class="flex gap-4">
                        <div class="w-12 h-12 rounded-full bg-white shadow-sm flex items-center justify-center flex-shrink-0 text-2xl">🕒</div>
                        <p class="text-slate-600 leading-relaxed">
                            <strong>Jam Buka:</strong> Setiap Hari (Buka s/d 21.00 WIB)
                        </p>
                    </div>
                </div>
            </div>
            <div class="h-96 bg-slate-200 rounded-[40px] overflow-hidden shadow-2xl border-4 border-white relative">
                <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3955.975440748102!2d111.4429948748921!3d-7.468200192510255!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2e79e00023223a29%3A0xb353590595368a4!2sJl.%20Dokter%20Wahidin%20No.18a%2C%20Sidomakmur%2C%20Ketanggi%2C%20Kec.%20Ngawi%2C%20Kabupaten%20Ngawi%2C%20Jawa%20Timur%2063211!5e0!3m2!1sen!2sid!4v1720345535355!5m2!1sen!2sid" width="100%" height="100%" style="border:0;" allowfullscreen="" loading="lazy"></iframe>
            </div>
        </div>
    </section>

    <footer class="bg-white pt-20 pb-10 border-t border-slate-100">
        <div class="max-w-7xl mx-auto px-4 text-center">
            <img src="https://tokosancaka.com/storage/uploads/sancaka.png" alt="Logo" class="h-16 w-16 mx-auto mb-6">
            <h3 class="text-2xl font-black italic mb-2 uppercase">Sancaka Group Ngawi</h3>
            <p class="text-slate-400 mb-8 max-w-sm mx-auto">Bisnis yang dikelola dengan dedikasi untuk melayani kebutuhan masyarakat Ngawi.</p>
            <div class="flex justify-center gap-6 mb-12">
                <a href="https://wa.me/6285745808809" class="w-12 h-12 rounded-full bg-slate-100 flex items-center justify-center hover:bg-red-600 hover:text-white transition">WA</a>
                <a href="https://tokosancaka.com" class="w-12 h-12 rounded-full bg-slate-100 flex items-center justify-center hover:bg-red-600 hover:text-white transition">WEB</a>
            </div>
            <div class="text-slate-300 text-[10px] font-bold tracking-[0.2em] uppercase">
                &copy; 2026 Sancaka Group - Jasa Pengiriman & Percetakan Terpercaya
            </div>
        </div>
    </footer>

</body>
</html>
