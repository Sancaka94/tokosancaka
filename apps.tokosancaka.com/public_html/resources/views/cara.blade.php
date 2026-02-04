<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cara Kerja Afiliasi - Toko Sancaka</title>

    <link rel="icon" href="https://tokosancaka.com/storage/uploads/sancaka.png" type="image/png">
    <link rel="shortcut icon" href="https://tokosancaka.com/storage/uploads/sancaka.png" type="image/png">

    <link rel="apple-touch-icon" href="https://tokosancaka.com/storage/uploads/sancaka.png">

    <script src="https://cdn.tailwindcss.com"></script>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800&display=swap" rel="stylesheet">

    <style>
        body { font-family: 'Inter', sans-serif; }
        .gradient-text {
            background: linear-gradient(to right, #60a5fa, #34d399);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        /* Animasi halus untuk icon */
        .hover-bounce:hover {
            animation: bounce 1s infinite;
        }
        @keyframes bounce {
            0%, 100% { transform: translateY(-5%); }
            50% { transform: translateY(5%); }
        }
    </style>
</head>
<body class="bg-slate-900 text-slate-300 antialiased selection:bg-blue-500 selection:text-white">

    <nav class="fixed w-full z-50 top-0 start-0 border-b border-slate-700 bg-slate-900/90 backdrop-blur-md">
        <div class="max-w-screen-xl flex flex-wrap items-center justify-between mx-auto p-4">
            <a href="{{ url('/') }}" class="flex items-center space-x-3 rtl:space-x-reverse">
                <img src="https://tokosancaka.com/storage/uploads/sancaka.png" class="h-8" alt="Sancaka Logo">
                <span class="self-center text-xl font-bold whitespace-nowrap text-white">SANCAKA<span class="text-blue-500">POS</span></span>
            </a>
            <div class="flex md:order-2 space-x-3 md:space-x-0 rtl:space-x-reverse">
                <a href="https://wa.me/6285745808809" target="_blank" class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-4 py-2 text-center transition-all">
                    <i class="fab fa-whatsapp me-2"></i>Hubungi Admin
                </a>
            </div>
        </div>
    </nav>

    <section class="pt-32 pb-10 px-4 mx-auto max-w-screen-xl text-center lg:pt-40 lg:px-12">
        <div class="inline-flex justify-between items-center py-1 px-1 pr-4 mb-7 text-sm rounded-full bg-slate-800 text-blue-400 hover:bg-slate-700 border border-slate-700 transition-colors">
            <span class="text-xs bg-emerald-600 rounded-full text-white px-4 py-1.5 mr-3">Simpel</span> <span class="text-sm font-medium">Cukup Modal WhatsApp!</span>
        </div>
        <h1 class="mb-4 text-4xl font-extrabold tracking-tight leading-none text-white md:text-5xl lg:text-6xl">
            Alur Menjadi <span class="gradient-text">Partner Sukses</span>
        </h1>
        <p class="mb-8 text-lg font-normal text-slate-400 lg:text-xl sm:px-16 xl:px-48">
            Ikuti 3 langkah mudah ini agar komisi cair lancar ke rekening Anda.
        </p>
    </section>

    <section id="tutorial" class="pb-20">
        <div class="max-w-screen-xl px-4 mx-auto">

            <div class="grid gap-8 lg:grid-cols-3 md:grid-cols-1">

                <div class="relative p-8 bg-slate-800 rounded-2xl border border-slate-700 shadow-xl hover:border-blue-500 transition-all duration-300 group text-center">
                    <div class="absolute -top-6 left-1/2 transform -translate-x-1/2 w-12 h-12 bg-blue-600 rounded-full flex items-center justify-center text-white font-bold text-2xl shadow-lg border-4 border-slate-900">1</div>

                    <div class="mt-4 mb-6 flex justify-center">
                        <div class="w-20 h-20 rounded-full bg-blue-900/30 flex items-center justify-center text-blue-400 group-hover:scale-110 transition-transform duration-300">
                            <i class="fas fa-user-edit text-4xl"></i>
                        </div>
                    </div>

                    <h3 class="mb-3 text-2xl font-bold text-white">Daftar Akun</h3>
                    <p class="text-slate-400 mb-6 leading-relaxed">
                        Isi data diri Anda di halaman pendaftaran. <br><span class="text-blue-400 font-semibold">Pastikan Nomor WA Aktif</span> agar sistem bisa mengirim info.
                    </p>

                    <a href="{{ url('/join-partner') }}" class="inline-flex items-center justify-center w-full px-5 py-3 text-sm font-bold text-white transition-all bg-blue-600 rounded-lg hover:bg-blue-700 focus:ring-4 focus:ring-blue-800 shadow-lg hover:shadow-blue-500/30">
                        Daftar Di Sini <i class="fas fa-arrow-right ml-2"></i>
                    </a>
                </div>

                <div class="relative p-8 bg-slate-800 rounded-2xl border border-slate-700 shadow-xl hover:border-emerald-500 transition-all duration-300 group text-center">
                    <div class="absolute -top-6 left-1/2 transform -translate-x-1/2 w-12 h-12 bg-emerald-600 rounded-full flex items-center justify-center text-white font-bold text-2xl shadow-lg border-4 border-slate-900">2</div>

                    <div class="mt-4 mb-6 flex justify-center">
                        <div class="w-20 h-20 rounded-full bg-emerald-900/30 flex items-center justify-center text-emerald-400 hover-bounce">
                            <i class="fab fa-whatsapp text-5xl"></i>
                        </div>
                    </div>

                    <h3 class="mb-3 text-2xl font-bold text-white">Cek WhatsApp</h3>
                    <p class="text-slate-400 mb-6 leading-relaxed">
                        Sistem otomatis mengirimkan <span class="text-emerald-400 font-bold">Link Order Khusus</span> & <span class="text-emerald-400 font-bold">Kode Kupon</span> ke nomor WhatsApp Anda.
                    </p>

                    <div class="inline-block px-4 py-2 bg-slate-900 rounded border border-slate-600 text-xs text-slate-400">
                        <i class="fas fa-check-circle text-emerald-500 mr-1"></i> Pesan masuk otomatis
                    </div>
                </div>

                <div class="relative p-8 bg-slate-800 rounded-2xl border border-slate-700 shadow-xl hover:border-amber-500 transition-all duration-300 group text-center">
                    <div class="absolute -top-6 left-1/2 transform -translate-x-1/2 w-12 h-12 bg-amber-500 rounded-full flex items-center justify-center text-white font-bold text-2xl shadow-lg border-4 border-slate-900">3</div>

                    <div class="mt-4 mb-6 flex justify-center">
                        <div class="w-20 h-20 rounded-full bg-amber-900/30 flex items-center justify-center text-amber-400 group-hover:rotate-12 transition-transform duration-300">
                            <i class="fas fa-share-nodes text-4xl"></i>
                        </div>
                    </div>

                    <h3 class="mb-3 text-2xl font-bold text-white">Sebarkan Link</h3>
                    <p class="text-slate-400 mb-6 leading-relaxed">
                        Teruskan (Forward) pesan WA tersebut ke <span class="text-amber-400 font-semibold">Teman, Saudara, atau Grup</span>. Saat mereka klik & order, Anda dapat komisi!
                    </p>

                    <a href="{{ url('/join-partner') }}" target="_blank" class="inline-flex items-center justify-center w-full px-5 py-3 text-sm font-bold text-slate-900 transition-all bg-amber-400 rounded-lg hover:bg-amber-500 focus:ring-4 focus:ring-amber-600 shadow-lg hover:shadow-amber-400/30">
                        Coba Share Sekarang <i class="fas fa-paper-plane ml-2"></i>
                    </a>
                </div>

            </div>
        </div>
    </section>

    <section class="py-12 px-4 text-center">
        <div class="max-w-3xl mx-auto bg-gradient-to-r from-blue-900 to-slate-900 border border-blue-800 rounded-2xl p-8 shadow-2xl relative overflow-hidden">
            <h2 class="text-2xl font-bold text-white mb-2">Belum Mendaftar?</h2>
            <p class="text-slate-300 mb-6">Jangan lewatkan kesempatan dapat penghasilan tambahan tanpa modal.</p>
            <a href="{{ url('/join-partner') }}" class="inline-block bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-3 px-8 rounded-full transition-transform hover:scale-105 shadow-lg">
                Daftar Jadi Partner Sekarang
            </a>
        </div>
    </section>

    <footer class="bg-slate-900 border-t border-slate-800 py-8 text-center mt-auto">
        <p class="text-slate-500 text-sm">
            © 2026 Sancaka POS. Butuh bantuan? <a href="https://wa.me/6285745808809" class="text-emerald-500 hover:underline">Chat WhatsApp Admin</a>
        </p>
    </footer>

</body>
</html>
