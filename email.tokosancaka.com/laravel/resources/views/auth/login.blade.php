<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Masuk — Sancaka Mail</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', '-apple-system', 'BlinkMacSystemFont', 'sans-serif'],
                    }
                }
            }
        }
    </script>
</head>
<body class="font-sans text-slate-800 antialiased bg-[#f6f8fc]">
    <div class="min-h-screen flex flex-col justify-center items-center px-4 py-8">
        <div class="w-full max-w-[420px] bg-white rounded-2xl border border-slate-200/80 shadow-sm px-8 py-9">
            <!-- Logo Sancaka -->
            <div class="flex flex-col items-center text-center mb-7">
                <a href="/" class="inline-flex flex-col items-center focus:outline-none">
                    <img
                        src="https://tokosancaka.com/storage/uploads/sancaka.png"
                        alt="Logo Sancaka"
                        class="h-12 w-auto object-contain mb-3"
                    >
                    <span class="text-lg font-semibold text-slate-900 tracking-tight">Sancaka Mail</span>
                    <span class="text-xs text-slate-500 mt-0.5">Masuk ke ruang kerja email Anda</span>
                </a>
            </div>

            <!-- Session Status -->
            @if (session('status'))
                <div class="mb-4 text-xs text-emerald-700 bg-emerald-50 border border-emerald-200 rounded-xl px-3.5 py-2.5">
                    {{ session('status') }}
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}" class="space-y-4">
                @csrf

                <!-- Email Address -->
                <div>
                    <label for="email" class="block text-xs font-medium text-slate-700 mb-1.5">
                        Alamat Email
                    </label>
                    <input
                        id="email"
                        type="email"
                        name="email"
                        value="{{ old('email') }}"
                        required
                        autofocus
                        autocomplete="username"
                        placeholder="tokosancaka@gmail.com"
                        class="w-full px-3.5 py-2.5 text-sm text-slate-900 bg-white rounded-xl border border-slate-300 placeholder-slate-400 focus:border-[#0b57d0] focus:ring-2 focus:ring-[#0b57d0]/15 outline-none transition"
                    />
                    @if ($errors->has('email'))
                        <p class="mt-1.5 text-xs text-red-600">{{ $errors->first('email') }}</p>
                    @endif
                </div>

                <!-- Password -->
                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label for="password" class="block text-xs font-medium text-slate-700">
                            Kata Sandi
                        </label>
                        @if (Route::has('password.request'))
                            <a href="{{ route('password.request') }}" class="text-xs font-medium text-[#0b57d0] hover:text-[#0842a0] transition">
                                Lupa kata sandi?
                            </a>
                        @endif
                    </div>

                    <div class="relative">
                        <input
                            id="password"
                            type="password"
                            name="password"
                            required
                            autocomplete="current-password"
                            placeholder="••••••••"
                            class="w-full pl-3.5 pr-10 py-2.5 text-sm text-slate-900 bg-white rounded-xl border border-slate-300 placeholder-slate-400 focus:border-[#0b57d0] focus:ring-2 focus:ring-[#0b57d0]/15 outline-none transition"
                        />
                        <button
                            type="button"
                            onclick="toggleLoginPass()"
                            class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600 focus:outline-none"
                            title="Tampilkan / Sembunyikan kata sandi"
                        >
                            <svg id="eyeLoginOpen" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                            <svg id="eyeLoginClosed" class="w-4 h-4 hidden" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                            </svg>
                        </button>
                    </div>
                    @if ($errors->has('password'))
                        <p class="mt-1.5 text-xs text-red-600">{{ $errors->first('password') }}</p>
                    @endif
                </div>

                <!-- Remember Me -->
                <div class="flex items-center pt-1">
                    <label for="remember_me" class="inline-flex items-center cursor-pointer select-none">
                        <input
                            id="remember_me"
                            type="checkbox"
                            name="remember"
                            class="w-4 h-4 rounded border-slate-300 text-[#0b57d0] focus:ring-[#0b57d0]/20 cursor-pointer"
                        >
                        <span class="ms-2 text-xs text-slate-600">Ingat saya di perangkat ini</span>
                    </label>
                </div>

                <!-- Submit -->
                <div class="pt-2 space-y-3">
                    <button
                        type="submit"
                        class="w-full bg-[#0b57d0] hover:bg-[#0842a0] active:scale-[0.99] text-white text-sm font-medium py-2.5 px-4 rounded-full transition cursor-pointer"
                    >
                        Masuk
                    </button>

                    @if (Route::has('register'))
                        <div class="text-center">
                            <a href="{{ route('register') }}" class="text-xs font-medium text-[#0b57d0] hover:text-[#0842a0] transition">
                                Belum punya akun? Daftar sekarang
                            </a>
                        </div>
                    @endif
                </div>
            </form>
        </div>

        <div class="mt-6 text-center text-[11px] text-slate-400">
            &copy; {{ date('Y') }} CV Sancaka Karya Hutama &bull; email.tokosancaka.com
        </div>
    </div>

    <script>
        function toggleLoginPass() {
            const input = document.getElementById('password');
            const eyeOpen = document.getElementById('eyeLoginOpen');
            const eyeClosed = document.getElementById('eyeLoginClosed');
            if (input.type === 'password') {
                input.type = 'text';
                eyeOpen.classList.add('hidden');
                eyeClosed.classList.remove('hidden');
            } else {
                input.type = 'password';
                eyeOpen.classList.remove('hidden');
                eyeClosed.classList.add('hidden');
            }
        }
    </script>
</body>
</html>
