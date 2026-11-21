<x-guest-layout>
    <form method="POST" action="{{ route('register') }}" class="space-y-4">
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
                placeholder="Amal Ibnu Muharram"
                class="w-full px-3.5 py-2.5 text-sm text-slate-900 bg-white rounded-xl border border-slate-300 placeholder-slate-400 focus:border-[#0b57d0] focus:ring-2 focus:ring-[#0b57d0]/15 outline-none transition"
            />
            @if ($errors->has('nama_lengkap'))
                <p class="mt-1.5 text-xs text-red-600">{{ $errors->first('nama_lengkap') }}</p>
            @endif
        </div>

        <!-- Alamat Email -->
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
                autocomplete="username"
                placeholder="nama@tokosancaka.com"
                class="w-full px-3.5 py-2.5 text-sm text-slate-900 bg-white rounded-xl border border-slate-300 placeholder-slate-400 focus:border-[#0b57d0] focus:ring-2 focus:ring-[#0b57d0]/15 outline-none transition"
            />
            @if ($errors->has('email'))
                <p class="mt-1.5 text-xs text-red-600">{{ $errors->first('email') }}</p>
            @endif
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
                placeholder="085745808809"
                class="w-full px-3.5 py-2.5 text-sm text-slate-900 bg-white rounded-xl border border-slate-300 placeholder-slate-400 focus:border-[#0b57d0] focus:ring-2 focus:ring-[#0b57d0]/15 outline-none transition"
            />
            @if ($errors->has('no_wa'))
                <p class="mt-1.5 text-xs text-red-600">{{ $errors->first('no_wa') }}</p>
            @endif
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
                    onclick="toggleRegPassword('password', 'eyeRegOpen1', 'eyeRegClosed1')"
                    class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600 focus:outline-none"
                    title="Tampilkan / Sembunyikan kata sandi"
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
            @if ($errors->has('password'))
                <p class="mt-1.5 text-xs text-red-600">{{ $errors->first('password') }}</p>
            @endif
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
                    onclick="toggleRegPassword('password_confirmation', 'eyeRegOpen2', 'eyeRegClosed2')"
                    class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600 focus:outline-none"
                    title="Tampilkan / Sembunyikan kata sandi"
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
            @if ($errors->has('password_confirmation'))
                <p class="mt-1.5 text-xs text-red-600">{{ $errors->first('password_confirmation') }}</p>
            @endif
        </div>

        <!-- Tombol Daftar & Link Login -->
        <div class="pt-2 space-y-3">
            <button
                type="submit"
                class="w-full bg-[#0b57d0] hover:bg-[#0842a0] active:scale-[0.99] text-white text-sm font-medium py-2.5 px-4 rounded-full transition cursor-pointer"
            >
                Daftar Akun
            </button>

            <div class="text-center">
                <a href="{{ route('login') }}" class="text-xs font-medium text-[#0b57d0] hover:text-[#0842a0] transition">
                    Sudah punya akun? Masuk di sini
                </a>
            </div>
        </div>
    </form>

    <script>
        function toggleRegPassword(inputId, openId, closedId) {
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
    </script>
</x-guest-layout>
