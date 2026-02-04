<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Akun - Sancaka e-Pesantren</title>

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

    <div class="relative z-10 min-h-screen flex items-center justify-center px-6 py-10">

        <div class="w-full max-w-7xl grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">

            <div class="text-white hidden lg:block">
                <span class="inline-block mb-4 px-4 py-1 rounded-full bg-white/20 backdrop-blur text-xs font-semibold tracking-widest border border-white/10">
                    SANCAKA E-PESANTREN
                </span>

                <h1 class="text-4xl lg:text-5xl font-extrabold leading-tight mb-6 drop-shadow-md">
                    Bergabung<br>
                    Bersama <span class="italic text-green-300">Kami.</span>
                </h1>

                <p class="text-white/90 max-w-md leading-relaxed text-lg drop-shadow-sm">
                    Mulai digitalisasi pesantren Anda hari ini untuk pengelolaan yang lebih transparan, efisien, dan modern.
                </p>

                <div class="mt-10 flex items-center gap-4">
                    <div class="flex gap-1">
                        <span class="w-3 h-3 bg-white rounded-full"></span>
                        <span class="w-3 h-3 bg-white/60 rounded-full"></span>
                        <span class="w-3 h-3 bg-white/40 rounded-full"></span>
                    </div>
                    <p class="text-sm text-white/80 font-medium">
                        Solusi Manajemen Terpercaya
                    </p>
                </div>
            </div>

            <div class="flex justify-center lg:justify-end">
                <div class="w-full max-w-md bg-white rounded-3xl shadow-2xl p-8 md:p-10 relative z-20">

                    <h2 class="text-2xl font-bold text-slate-900 mb-1">
                        Buat Akun Baru
                    </h2>
                    <p class="text-sm text-slate-500 mb-6">
                        Lengkapi data di bawah ini untuk mendaftar.
                    </p>

                    <form method="POST" action="{{ route('register') }}" class="space-y-4">
                    @csrf
                        <div>
                            <label class="block text-sm font-semibold mb-2 text-slate-700">Nama Lengkap</label>
                            <input type="text" name="name" required
                                   class="w-full px-4 py-3 rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none transition-all placeholder-slate-400"
                                   placeholder="Nama Pesantren / Admin">
                        </div>

                        <div>
                            <label class="block text-sm font-semibold mb-2 text-slate-700">Alamat Email</label>
                            <input type="email" name="email" required
                                   class="w-full px-4 py-3 rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none transition-all placeholder-slate-400"
                                   placeholder="admin@email.com">
                        </div>

                        <div>
                            <label class="block text-sm font-semibold mb-2 text-slate-700">Kata Sandi</label>
                            <div class="relative">
                                <input id="password" type="password" name="password" required
                                       class="w-full px-4 py-3 rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none transition-all placeholder-slate-400"
                                       placeholder="Minimal 8 karakter">
                                
                                <button type="button" onclick="toggleVisibility('password')"
                                        class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 hover:text-green-600 transition-colors cursor-pointer">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold mb-2 text-slate-700">Konfirmasi Sandi</label>
                            <div class="relative">
                                <input id="password_confirmation" type="password" name="password_confirmation" required
                                       class="w-full px-4 py-3 rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none transition-all placeholder-slate-400"
                                       placeholder="Ulangi kata sandi">
                                
                                <button type="button" onclick="toggleVisibility('password_confirmation')"
                                        class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 hover:text-green-600 transition-colors cursor-pointer">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <div class="pt-2">
                            <button type="submit"
                                    class="w-full py-3.5 rounded-xl bg-green-600 hover:bg-green-700 text-white font-bold text-lg shadow-lg shadow-green-200 transition-all transform hover:-translate-y-0.5">
                                Daftar Sekarang
                            </button>
                        </div>

                        <div class="text-center pt-6 border-t border-slate-100">
                            <p class="text-sm text-slate-500">Sudah memiliki akun?</p>
                            <a href="{{ route('login') }}" class="text-green-600 font-bold hover:text-green-800 transition-colors inline-flex items-center gap-1">
                                Masuk ke Akun <span>&rarr;</span>
                            </a>
                        </div>

                    </form>

                </div>
            </div>

        </div>
    </div>

    <p class="absolute bottom-4 w-full text-center text-xs text-white/70 z-20">
        &copy; 2024 Sancaka e-Pesantren
    </p>
</div>

<script>
    function toggleVisibility(fieldId) {
        const input = document.getElementById(fieldId);
        if (input.type === 'password') {
            input.type = 'text';
        } else {
            input.type = 'password';
        }
    }
</script>

</body>
</html>