<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Daftar — Sancaka Mail</title>

    <link rel="icon" type="image/png" href="https://tokosancaka.com/storage/uploads/sancaka.png">
    <link rel="shortcut icon" type="image/png" href="https://tokosancaka.com/storage/uploads/sancaka.png">
    <link rel="apple-touch-icon" href="https://tokosancaka.com/storage/uploads/sancaka.png">

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
    @php
        $mailDomain = env('DA_MAIL_DOMAIN', 'tokosancaka.com');
    @endphp

    <div class="min-h-screen flex flex-col justify-center items-center px-4 py-8">
        <div class="w-full max-w-[440px] bg-white rounded-2xl border border-slate-200/80 shadow-sm px-6 sm:px-8 py-8 sm:py-9">
            <!-- Logo Sancaka -->
            <div class="flex flex-col items-center text-center mb-7">
                <a href="/" class="inline-flex flex-col items-center focus:outline-none">
                    <img
                        src="https://tokosancaka.com/storage/uploads/sancaka.png"
                        alt="Logo Sancaka"
                        class="h-12 w-auto object-contain mb-3"
                    >
                    <span class="text-lg font-semibold text-slate-900 tracking-tight">Buat Akun Sancaka Mail</span>
                    <span class="text-xs text-slate-500 mt-0.5">Dapatkan alamat email {{ '@' . $mailDomain }} gratis</span>
                </a>
            </div>

            <form method="POST" action="{{ route('register') }}" id="registerForm" class="space-y-4">
                @csrf

                <!-- Nama Lengkap -->
                <div>
                    <label for="nama_lengkap" class="block text-xs font-medium text-slate-700 mb-1.5">
                        Nama Lengkap
                    </label>
                    <input
                        id="nama_lengkap"
                        type="text"
                        name="nama_lengkap"
                        value="{{ old('nama_lengkap') }}"
                        required
                        autofocus
                        autocomplete="name"
                        placeholder="Masukkan nama lengkap Anda"
                        class="w-full px-3.5 py-2.5 text-sm text-slate-900 bg-white rounded-xl border border-slate-300 placeholder-slate-400 focus:border-[#0b57d0] focus:ring-2 focus:ring-[#0b57d0]/15 outline-none transition"
                    />
                    @error('nama_lengkap')
                        <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Pilihan Alamat Email (@tokosancaka.com) -->
                <div>
                    <label for="username" class="block text-xs font-medium text-slate-700 mb-1.5">
                        Pilih Alamat Email
                    </label>
                    <div class="flex items-stretch rounded-xl border border-slate-300 bg-white overflow-hidden focus-within:border-[#0b57d0] focus-within:ring-2 focus-within:ring-[#0b57d0]/15 transition">
                        <input
                            id="username"
                            type="text"
                            name="username"
                            value="{{ old('username') }}"
                            required
                            autocomplete="off"
                            spellcheck="false"
                            placeholder="nama.anda"
                            oninput="sanitizeEmailUsername(this)"
                            class="flex-1 min-w-0 px-3.5 py-2.5 text-sm text-slate-900 bg-transparent border-0 placeholder-slate-400 outline-none"
                        />
                        <span class="inline-flex items-center px-3 bg-slate-50 text-slate-600 text-xs sm:text-sm font-medium border-l border-slate-200 select-none shrink-0">
                            {{ '@' . $mailDomain }}
                        </span>
                    </div>
                    <p id="emailPreviewText" class="mt-1 text-[11px] text-slate-500">
                        Gunakan huruf kecil, angka, titik (.), atau strip (-).
                    </p>
                    @error('username')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                    @error('clean_username')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                    @error('webmail_email')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                    @error('email')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Nomor WhatsApp -->
                <div>
                    <label for="no_wa" class="block text-xs font-medium text-slate-700 mb-1.5">
                        Nomor WhatsApp
                    </label>
                    <input
                        id="no_wa"
                        type="tel"
                        name="no_wa"
                        value="{{ old('no_wa') }}"
                        required
                        autocomplete="tel"
                        placeholder="08xxxxxxxxxx"
                        oninput="this.value = this.value.replace(/[^0-9+]/g, '')"
                        class="w-full px-3.5 py-2.5 text-sm text-slate-900 bg-white rounded-xl border border-slate-300 placeholder-slate-400 focus:border-[#0b57d0] focus:ring-2 focus:ring-[#0b57d0]/15 outline-none transition"
                    />
                    @error('no_wa')
                        <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Kata Sandi -->
                <div>
                    <label for="password" class="block text-xs font-medium text-slate-700 mb-1.5">
                        Kata Sandi
                    </label>
                    <div class="relative">
                        <input
                            id="password"
                            type="password"
                            name="password"
                            required
                            autocomplete="new-password"
                            placeholder="Minimal 8 karakter"
                            class="w-full pl-3.5 pr-10 py-2.5 text-sm text-slate-900 bg-white rounded-xl border border-slate-300 placeholder-slate-400 focus:border-[#0b57d0] focus:ring-2 focus:ring-[#0b57d0]/15 outline-none transition"
                        />
                        <button
                            type="button"
                            onclick="toggleRegPass('password', 'eyeRegOpen1', 'eyeRegClosed1')"
                            class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600 focus:outline-none"
                            aria-label="Tampilkan kata sandi"
                        >
                            <svg id="eyeRegOpen1" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                            <svg id="eyeRegClosed1" class="w-4 h-4 hidden" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                            </svg>
                        </button>
                    </div>
                    <p class="mt-1 text-[11px] text-slate-500">
                        Wajib kombinasi huruf besar, huruf kecil, dan angka (min. 8 karakter).
                    </p>
                    @error('password')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Konfirmasi Kata Sandi -->
                <div>
                    <label for="password_confirmation" class="block text-xs font-medium text-slate-700 mb-1.5">
                        Konfirmasi Kata Sandi
                    </label>
                    <div class="relative">
                        <input
                            id="password_confirmation"
                            type="password"
                            name="password_confirmation"
                            required
                            autocomplete="new-password"
                            placeholder="Ulangi kata sandi"
                            class="w-full pl-3.5 pr-10 py-2.5 text-sm text-slate-900 bg-white rounded-xl border border-slate-300 placeholder-slate-400 focus:border-[#0b57d0] focus:ring-2 focus:ring-[#0b57d0]/15 outline-none transition"
                        />
                        <button
                            type="button"
                            onclick="toggleRegPass('password_confirmation', 'eyeRegOpen2', 'eyeRegClosed2')"
                            class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600 focus:outline-none"
                            aria-label="Tampilkan konfirmasi kata sandi"
                        >
                            <svg id="eyeRegOpen2" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                            <svg id="eyeRegClosed2" class="w-4 h-4 hidden" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                            </svg>
                        </button>
                    </div>
                    @error('password_confirmation')
                        <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Submit -->
                <div class="pt-2 space-y-3">
                    <button
                        type="submit"
                        id="submitRegisterBtn"
                        class="w-full bg-[#0b57d0] hover:bg-[#0842a0] active:scale-[0.99] disabled:opacity-60 disabled:pointer-events-none text-white text-sm font-medium py-2.5 px-4 rounded-full transition cursor-pointer flex items-center justify-center gap-2"
                    >
                        <span id="submitBtnText">Buat Akun Email</span>
                    </button>

                    <div class="text-center">
                        <a href="{{ route('login') }}" class="text-xs font-medium text-[#0b57d0] hover:text-[#0842a0] transition">
                            Sudah punya akun? Masuk di sini
                        </a>
                    </div>
                </div>
            </form>
        </div>

        <div class="mt-6 text-center text-[11px] text-slate-400">
            &copy; {{ date('Y') }} CV Sancaka Karya Hutama &bull; email.tokosancaka.com
        </div>
    </div>

    <script>
        const MAIL_DOMAIN = @json($mailDomain);

        function sanitizeEmailUsername(input) {
            // Hapus karakter ilegal, otomatis huruf kecil, dan cegah user mengetik @domain lagi
            let val = input.value.toLowerCase().split('@')[0].replace(/[^a-z0-9._-]/g, '');
            input.value = val;

            const preview = document.getElementById('emailPreviewText');
            if (val.length > 0) {
                preview.innerHTML = `Alamat email Anda: <strong class="text-slate-700">${val}@${MAIL_DOMAIN}</strong>`;
            } else {
                preview.textContent = 'Gunakan huruf kecil, angka, titik (.), atau strip (-).';
            }
        }

        function toggleRegPass(inputId, openId, closedId) {
            const input = document.getElementById(inputId);
            const eyeOpen = document.getElementById(openId);
            const eyeClosed = document.getElementById(closedId);
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

        document.getElementById('registerForm').addEventListener('submit', function () {
            const btn = document.getElementById('submitRegisterBtn');
            const text = document.getElementById('submitBtnText');
            btn.disabled = true;
            text.textContent = 'Membuat akun email...';
        });
    </script>
</body>
</html>
