<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sancaka e-Pesantren</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
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

<body class="font-sans antialiased bg-slate-900 text-slate-900">

<div class="relative min-h-screen flex items-center justify-center px-4 overflow-hidden">

    <!-- Background -->
    <div class="absolute inset-0">
        <img src="https://ponpes.tokosancaka.com/storage/auth/bg_ponpes.jpg"
             class="w-full h-full object-cover scale-105 blur-[2px] opacity-70">
        <div class="absolute inset-0 bg-gradient-to-br from-indigo-950/80 via-slate-900/70 to-black/80"></div>
    </div>

    <!-- Card -->
    <div class="relative z-10 w-full max-w-5xl bg-white rounded-3xl shadow-[0_40px_120px_rgba(0,0,0,0.35)] overflow-hidden grid grid-cols-1 lg:grid-cols-2">

        <!-- Left -->
        <div class="hidden lg:flex flex-col justify-between p-14 bg-gradient-to-br from-indigo-700 to-indigo-900 text-white relative">
            <div class="absolute -top-20 -right-20 w-64 h-64 bg-indigo-500/30 rounded-full blur-3xl"></div>
            <div class="absolute -bottom-20 -left-20 w-64 h-64 bg-indigo-900/40 rounded-full blur-3xl"></div>

            <div>
                <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/10 border border-white/20 text-[11px] font-semibold tracking-widest uppercase">
                    <span class="w-2 h-2 bg-green-400 rounded-full animate-pulse"></span>
                    Sancaka e-Pesantren
                </span>

                <h1 class="mt-10 text-4xl font-bold leading-tight">
                    Manajemen Pesantren<br>
                    <span class="text-indigo-200">Lebih Modern & Rapi</span>
                </h1>

                <p class="mt-4 text-sm text-indigo-100/90 max-w-sm leading-relaxed">
                    Platform terintegrasi untuk administrasi, keuangan, dan data santri secara real-time dan aman.
                </p>
            </div>

            <div class="flex items-center gap-4">
                <div class="flex -space-x-2">
                    <div class="w-9 h-9 rounded-full border-2 border-indigo-800 bg-slate-300"></div>
                    <div class="w-9 h-9 rounded-full border-2 border-indigo-800 bg-slate-400"></div>
                    <div class="w-9 h-9 rounded-full border-2 border-indigo-800 bg-slate-500"></div>
                </div>
                <div class="text-xs text-indigo-200">
                    <strong class="block text-white">100+ Pesantren</strong>
                    Telah bergabung
                </div>
            </div>
        </div>

        <!-- Right -->
        <div class="flex flex-col justify-center px-8 py-12 md:px-14">

            <div class="mb-10">
                <h2 class="text-2xl font-bold tracking-tight">Selamat Datang 👋</h2>
                <p class="mt-2 text-sm text-slate-500">
                    Silakan login untuk mengakses dashboard admin
                </p>
            </div>

            <form class="space-y-6">

                <div>
                    <label class="block text-sm font-semibold mb-2">Alamat Email</label>
                    <input type="email"
                           placeholder="admin@sancaka.com"
                           class="w-full px-4 py-3 rounded-xl bg-slate-50 border border-slate-200 text-sm focus:bg-white focus:border-indigo-600 focus:ring-2 focus:ring-indigo-600/20 transition">
                </div>

                <div>
                    <label class="block text-sm font-semibold mb-2">Kata Sandi</label>
                    <div class="relative">
                        <input id="password" type="password"
                               placeholder="••••••••"
                               class="w-full px-4 py-3 rounded-xl bg-slate-50 border border-slate-200 text-sm focus:bg-white focus:border-indigo-600 focus:ring-2 focus:ring-indigo-600/20 transition">
                        <button type="button"
                                onclick="togglePassword()"
                                class="absolute inset-y-0 right-4 flex items-center text-slate-400 hover:text-indigo-600">
                            <svg id="eye-icon" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                                 viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="flex items-center justify-between text-sm">
                    <label class="flex items-center gap-2 text-slate-600">
                        <input type="checkbox" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                        Ingat saya
                    </label>
                    <a href="#" class="font-semibold text-indigo-600 hover:text-indigo-800">
                        Lupa password?
                    </a>
                </div>

                <button type="submit"
                        class="w-full py-3 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold shadow-lg shadow-indigo-200 transition transform hover:-translate-y-0.5">
                    Masuk Sekarang
                </button>

                <div class="pt-6 border-t text-center">
                    <p class="text-sm text-slate-500">Belum punya akun?</p>
                    <a href="#" class="text-sm font-bold text-indigo-600 hover:text-indigo-800">
                        Daftar Gratis →
                    </a>
                </div>

            </form>
        </div>
    </div>

    <p class="absolute bottom-4 text-[10px] text-white/70 tracking-widest uppercase">
        © 2024 Sancaka e-Pesantren
    </p>
</div>

<script>
    function togglePassword() {
        const input = document.getElementById('password');
        const icon = document.getElementById('eye-icon');
        input.type = input.type === 'password' ? 'text' : 'password';
        icon.style.stroke = input.type === 'text' ? '#4f46e5' : 'currentColor';
    }
</script>

</body>
</html>
