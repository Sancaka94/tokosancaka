<x-guest-layout>
    <div class="flex min-h-[600px] bg-white rounded-2xl shadow-xl overflow-hidden max-w-5xl mx-auto border border-gray-100">
        
        <div class="hidden md:flex md:w-1/2 bg-indigo-600 relative">
            <img src="https://images.unsplash.com/photo-1541829070764-84a7d30dee3f?q=80&w=2070&auto=format&fit=crop" 
                 alt="Login Visual" 
                 class="absolute inset-0 w-full h-full object-cover opacity-40">
            <div class="relative z-10 flex flex-col justify-center px-12 text-white">
                <h2 class="text-4xl font-extrabold tracking-tight">Sancaka ePesantren</h2>
                <p class="mt-4 text-indigo-100 text-lg">Solusi digital modern untuk manajemen pondok pesantren yang lebih efisien dan terintegrasi.</p>
            </div>
        </div>

        <div class="w-full md:w-1/2 p-8 md:p-16 flex flex-col justify-center bg-white">
            <div class="mb-10 text-center md:text-left">
                <h2 class="text-3xl font-bold text-gray-900">Selamat Datang</h2>
                <p class="text-gray-500 mt-2">Silakan masuk untuk mengakses dashboard Anda</p>
            </div>

            <x-auth-session-status class="mb-4" :status="session('status')" />

            <form method="POST" action="{{ route('login') }}">
                @csrf

                <div class="mb-5">
                    <x-input-label for="email" :value="__('Alamat Email')" class="text-sm font-semibold text-gray-700" />
                    <x-text-input id="email" class="block mt-1.5 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-lg shadow-sm" 
                                  type="email" name="email" :value="old('email')" 
                                  required autofocus placeholder="contoh@pesantren.com" />
                    <x-input-error :messages="$errors->get('email')" class="mt-2" />
                </div>

                <div class="mb-5">
                    <div class="flex justify-between items-center">
                        <x-input-label for="password" :value="__('Kata Sandi')" class="text-sm font-semibold text-gray-700" />
                    </div>
                    <div class="relative mt-1.5">
                        <x-text-input id="password" 
                                      class="block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-lg shadow-sm pr-10"
                                      type="password"
                                      name="password"
                                      required autocomplete="current-password" 
                                      placeholder="••••••••" />
                        
                        <button type="button" onclick="togglePasswordVisibility()" class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-indigo-600 focus:outline-none">
                            <svg id="eye-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.644C3.399 8.049 7.21 5 12 5c4.789 0 8.601 3.049 9.964 6.678.045.166.045.338 0 .504C20.601 15.951 16.79 19 12 19c-4.789 0-8.601-3.049-9.964-6.678z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                        </button>
                    </div>
                    <x-input-error :messages="$errors->get('password')" class="mt-2" />
                </div>

                <div class="flex items-center justify-between mb-8">
                    <label for="remember_me" class="inline-flex items-center">
                        <input id="remember_me" type="checkbox" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" name="remember">
                        <span class="ms-2 text-sm text-gray-600">{{ __('Ingat saya') }}</span>
                    </label>

                    @if (Route::has('password.request'))
                        <a class="text-sm font-semibold text-indigo-600 hover:text-indigo-500 transition-colors" href="{{ route('password.request') }}">
                            Lupa Password?
                        </a>
                    @endif
                </div>

                <x-primary-button class="w-full justify-center py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-lg transition duration-200">
                    Masuk Sekarang
                </x-primary-button>

                <div class="mt-8 text-center text-sm text-gray-600 border-t pt-6">
                    Belum memiliki akun? 
                    <a href="https://ponpes.tokosancaka.com/register" class="text-indigo-600 font-bold hover:text-indigo-800 transition-colors">
                        Daftar Gratis
                    </a>
                </div>
            </form>
        </div>
    </div>

    <script>
        function togglePasswordVisibility() {
            const passwordInput = document.getElementById('password');
            const eyeIcon = document.getElementById('eye-icon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                // Ganti ikon ke mata dicoret (eye-slash) jika diinginkan, atau biarkan tetap
                eyeIcon.setAttribute('stroke', '#4f46e5'); // Beri warna saat aktif
            } else {
                passwordInput.type = 'password';
                eyeIcon.setAttribute('stroke', 'currentColor');
            }
        }
    </script>
</x-guest-layout>