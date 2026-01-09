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

<body class="font-sans antialiased text-slate-900 bg-gray-100">

<div class="relative min-h-screen w-full overflow-hidden">

    <div class="absolute inset-0">
        <img src="https://ponpes.tokosancaka.com/storage/auth/bg_ponpes.jpg"
             class="w-full h-full object-cover">
        <div class="absolute inset-0 bg-gradient-to-r from-black/50 via-black/30 to-black/10"></div>
    </div>

    <div class="relative z-10 min-h-screen flex items-center justify-center px-6">

        <div class="w-full max-w-7xl grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">

            <div class="text-white hidden lg:block">
                <span class="inline-block mb-4 px-4 py-1 rounded-full bg-white/20 backdrop-blur text-xs font-semibold tracking-widest border border-white/10">
                    SANCAKA E-PESANTREN
                </span>

                <h1 class="text-4xl lg:text-5xl font-extrabold leading-tight mb-6 drop-shadow-md">
                    Manajemen<br>
                    Jadi Lebih <span class="italic text-green-300">Mudah.</span>
                </h1>

                <p class="text-white/90 max-w-md leading-relaxed text-lg drop-shadow-sm">
                    Platform digital terintegrasi untuk pengelolaan administrasi, keuangan,
                    dan akademik pesantren yang modern.
                </p>

                <div class="mt-10 flex items-center gap-4">
                    <div class="flex gap-1">
                        <span class="w-3 h-3 bg-white rounded-full"></span>
                        <span class="w-3 h-3 bg-white/60 rounded-full"></span>
                        <span class="w-3 h-3 bg-white/40 rounded-full"></span>
                    </div>
                    <p class="text-sm text-white/80 font-medium">
                        Dipercaya oleh banyak Pesantren
                    </p>
                </div>
            </div>

            <div class="flex justify-center lg:justify-end">
                <div class="w-full max-w-md bg-white rounded-3xl shadow-2xl p-8 md:p-10 relative z-20">

                    @if ($errors->any())
                        <div class="mb-4 p-3 bg-red-50 text-red-600 rounded-lg text-sm font-medium border border-red-100">
                            Email atau kata sandi salah.
                        </div>
                    @endif

                    <h2 class="text-2xl font-bold text-slate-900 mb-1">
                        Selamat Datang
                    </h2>
                    <p class="text-sm text-slate-500 mb-8">
                        Masuk ke dashboard admin Anda.
                    </p>

                    <form method="POST" action="{{ route('login') }}" class="space-y-5">
                        @csrf <div>
                            <label class="block text-sm font-semibold mb-2 text-slate-700">
                                Alamat Email
                            </label>
                            <input type="email" name="email" value="{{ old('email') }}" required autofocus
                                   class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none transition-all placeholder-slate-400 bg-slate-50 focus:bg-white"
                                   placeholder="admin@email.com">
                        </div>

                        <div>
                            <label class="block text-sm font-semibold mb-2 text-slate-700">
                                Kata Sandi
                            </label>
                            <div class="relative">
                                <input id="password" type="password" name="password" required autocomplete="current-password"
                                       class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none transition-all placeholder-slate-400 bg-slate-50 focus:bg-white"
                                       placeholder="••••••••">
                                <button type="button" onclick="togglePassword()"
                                        class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 hover:text-green-600 transition-colors">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <div class="flex items-center justify-between text-sm">
                            <label class="flex items-center gap-2 cursor-pointer group">
                                <input type="checkbox" name="remember" class="rounded border-gray-300 text-green-600 focus:ring-green-500">
                                <span class="text-slate-600 group-hover:text-green-600 transition-colors">Ingat saya</span>
                            </label>
                            
                            @if (Route::has('password.request'))
                                <a href="{{ route('password.request') }}" class="text-green-600 font-bold hover:text-green-800 transition-colors">
                                    Lupa Password?
                                </a>
                            @endif
                        </div>

                        <button type="submit"
                                class="w-full py-3.5 rounded-xl bg-green-600 hover:bg-green-700 text-white font-bold text-lg shadow-lg shadow-green-200 transition-all transform hover:-translate-y-0.5">
                            Masuk Sekarang
                        </button>

                        <div class="text-center pt-6 border-t border-slate-100">
                            <p class="text-sm text-slate-500">Belum memiliki akun?</p>
                            <a href="{{ route('register') }}" class="text-green-600 font-bold hover:text-green-800 transition-colors inline-flex items-center gap-1">
                                Daftar Gratis Disini <span>&rarr;</span>
                            </a>
                        </div>

                    </form>

                </div>
            </div>

        </div>
    </div>

    <p class="absolute bottom-4 w-full text-center text-xs text-white/70 z-20">
        &copy; {{ date('Y') }} Sancaka e-Pesantren
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