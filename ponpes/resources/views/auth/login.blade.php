<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sancaka e-Pesantren</title>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    }
                }
            }
        }
    </script>
</head>

<body class="font-sans antialiased">

<div class="relative min-h-screen w-full overflow-hidden">

    <!-- Background -->
    <div class="absolute inset-0 overflow-hidden">

    <!-- Layer 1: Full layar (cover, blur) -->
    <img src="https://ponpes.tokosancaka.com/storage/auth/bg_ponpes.jpg"
         class="absolute inset-0 w-full h-full object-cover blur-lg scale-110">

    <!-- Overlay gelap -->
    <div class="absolute inset-0 bg-black/40"></div>

    <!-- Layer 2: Gambar UTUH (contain) -->
    <img src="https://ponpes.tokosancaka.com/storage/auth/bg_ponpes.jpg"
         class="relative z-10 w-full h-full object-contain">
</div>


    <!-- Content -->
    <div class="relative z-10 min-h-screen flex items-center justify-center px-6">

        <div class="w-full max-w-7xl grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">

            <!-- LEFT HERO -->
            <div class="text-white">
                <span class="inline-block mb-4 px-4 py-1 rounded-full bg-white/20 text-xs font-semibold tracking-widest">
                    SANCAKA E-PESANTREN
                </span>

                <h1 class="text-4xl lg:text-5xl font-extrabold leading-tight mb-6">
                    Manajemen<br>
                    Jadi Lebih <span class="italic">Mudah.</span>
                </h1>

                <p class="text-white/90 max-w-md leading-relaxed">
                    Platform digital terintegrasi untuk pengelolaan administrasi, keuangan,
                    dan akademik pesantren yang modern.
                </p>

                <div class="mt-10 flex items-center gap-4">
                    <div class="flex gap-1">
                        <span class="w-3 h-3 bg-white rounded-full"></span>
                        <span class="w-3 h-3 bg-white/60 rounded-full"></span>
                        <span class="w-3 h-3 bg-white/40 rounded-full"></span>
                    </div>
                    <p class="text-sm text-white/80">
                        Dipercaya oleh banyak Pesantren
                    </p>
                </div>
            </div>

            <!-- RIGHT LOGIN CARD -->
            <div class="flex justify-center lg:justify-end">
                <div class="w-full max-w-md bg-white rounded-3xl shadow-2xl p-8 md:p-10">

                    <h2 class="text-2xl font-bold text-slate-900 mb-1">
                        Selamat Datang
                    </h2>
                    <p class="text-sm text-slate-500 mb-8">
                        Masuk ke dashboard admin Anda.
                    </p>

                    <form class="space-y-5">

                        <div>
                            <label class="block text-sm font-semibold mb-2">
                                Alamat Email
                            </label>
                            <input type="email"
                                   class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none"
                                   placeholder="admin@email.com">
                        </div>

                        <div>
                            <label class="block text-sm font-semibold mb-2">
                                Kata Sandi
                            </label>
                            <div class="relative">
                                <input id="password" type="password"
                                       class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none"
                                       placeholder="••••••••">
                                <button type="button" onclick="togglePassword()"
                                        class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400">
                                    👁
                                </button>
                            </div>
                        </div>

                        <div class="flex items-center justify-between text-sm">
                            <label class="flex items-center gap-2">
                                <input type="checkbox" class="rounded text-indigo-600">
                                Ingat saya
                            </label>
                            <a href="#" class="text-indigo-600 font-semibold">
                                Lupa Password?
                            </a>
                        </div>

                        <button type="submit"
                                class="w-full py-3 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-lg transition">
                            Masuk Sekarang
                        </button>

                        <div class="text-center pt-6 border-t">
                            <p class="text-sm text-slate-500">Belum memiliki akun?</p>
                            <a href="#" class="text-indigo-600 font-bold">
                                Daftar Gratis Disini →
                            </a>
                        </div>

                    </form>

                </div>
            </div>

        </div>
    </div>

    <p class="absolute bottom-4 w-full text-center text-xs text-white/70">
        © 2024 Sancaka e-Pesantren
    </p>
</div>

<script>
    function togglePassword() {
        const input = document.getElementById('password');
        input.type = input.type === 'password' ? 'text' : 'password';
    }
</script>

</body>
</html>
