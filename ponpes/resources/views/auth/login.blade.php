<x-guest-layout>
    <div class="flex min-h-screen w-full bg-white overflow-hidden">
        
        <div class="hidden lg:relative lg:flex lg:w-1/2 bg-gray-900 items-center justify-center overflow-hidden">
            
            <img src="https://ponpes.tokosancaka.com/storage/auth/bg_ponpes.jpg" 
                 class="absolute inset-0 w-full h-full object-cover opacity-80 transition-transform duration-[20s] hover:scale-110 ease-in-out" 
                 alt="Pesantren Background">
            
            <div class="absolute inset-0 bg-gradient-to-t from-indigo-900/90 via-indigo-900/40 to-black/30"></div>

            <div class="relative z-10 w-full max-w-lg px-8">
                <div class="inline-flex items-center gap-2 bg-white/10 backdrop-blur-md border border-white/20 px-4 py-1.5 rounded-full mb-6 shadow-lg">
                    <span class="w-2 h-2 rounded-full bg-green-400 animate-pulse"></span>
                    <span class="font-bold tracking-widest uppercase text-xs text-white">Sancaka e-Pesantren</span>
                </div>

                <h1 class="text-5xl font-black leading-tight text-white mb-6 drop-shadow-lg">
                    Manajemen <br> Jadi Lebih <span class="text-indigo-300 italic">Mudah.</span>
                </h1>

                <p class="text-lg text-indigo-50 font-light leading-relaxed opacity-90">
                    Solusi digital terintegrasi untuk pengelolaan administrasi, keuangan, dan data santri secara real-time.
                </p>

                <div class="mt-12 flex gap-4">
                    <div class="flex -space-x-4">
                        <div class="w-10 h-10 rounded-full border-2 border-indigo-900 bg-gray-200"></div>
                        <div class="w-10 h-10 rounded-full border-2 border-indigo-900 bg-gray-300"></div>
                        <div class="w-10 h-10 rounded-full border-2 border-indigo-900 bg-gray-400"></div>
                    </div>
                    <div class="text-white text-sm font-medium flex items-center">
                        <span class="font-bold mr-1">100+</span> Pesantren Percaya
                    </div>
                </div>
            </div>
        </div>

        <div class="flex w-full lg:w-1/2 items-center justify-center p-8 sm:p-12 lg:p-20 bg-white">
            <div class="w-full max-w-md space-y-8">
                
                <div class="text-center lg:text-left">
                    <h2 class="text-3xl font-extrabold text-gray-900 tracking-tight">Selamat Datang</h2>
                    <p class="text-gray-500 mt-2 text-sm">Masuk ke dashboard admin pesantren Anda.</p>
                </div>

                <x-auth-session-status class="mb-4" :status="session('status')" />

                <form method="POST" action="{{ route('login') }}" class="space-y-6">
                    @csrf

                    <div>
                        <label for="email" class="block text-sm font-bold text-gray-700 mb-2 ml-1">Alamat Email</label>
                        <div class="relative group">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-gray-400 group-focus-within:text-indigo-600 transition-colors">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                            </div>
                            <input id="email" class="block w-full rounded-2xl border-gray-200 bg-gray-50 py-4 pl-12 pr-4 text-gray-900 placeholder-gray-400 focus:bg-white focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 focus:outline-none transition-all duration-200 sm:text-sm" 
                                   type="email" name="email" :value="old('email')" required autofocus placeholder="admin@sancaka.com" />
                        </div>
                        <x-input-error :messages="$errors->get('email')" class="mt-2" />
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-bold text-gray-700 mb-2 ml-1">Kata Sandi</label>
                        <div class="relative group">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-gray-400 group-focus-within:text-indigo-600 transition-colors">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                </svg>
                            </div>
                            
                            <input id="password" 
                                   class="block w-full rounded-2xl border-gray-200 bg-gray-50 py-4 pl-12 pr-12 text-gray-900 placeholder-gray-400 focus:bg-white focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 focus:outline-none transition-all duration-200 sm:text-sm"
                                   type="password" name="password" required autocomplete="current-password" placeholder="••••••••" />
                            
                            <button type="button" onclick="togglePasswordVisibility()" class="absolute inset-y-0 right-0 pr-4 flex items-center cursor-pointer text-gray-400 hover:text-indigo-600 transition-colors focus:outline-none">
                                <svg id="eye-icon" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path id="eye-path" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path id="eye-body" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                            </button>
                        </div>
                        <x-input-error :messages="$errors->get('password')" class="mt-2" />
                    </div>

                    <div class="flex items-center justify-between">
                        <label class="flex items-center cursor-pointer group">
                            <input id="remember_me" type="checkbox" class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 cursor-pointer" name="remember">
                            <span class="ml-2 text-sm text-gray-600 group-hover:text-indigo-600">Ingat saya</span>
                        </label>
                        <a class="text-sm font-bold text-indigo-600 hover:text-indigo-800" href="{{ route('password.request') }}">
                            Lupa Password?
                        </a>
                    </div>

                    <button type="submit" class="w-full py-4 px-4 border border-transparent text-lg font-bold rounded-2xl text-white bg-indigo-600 hover:bg-indigo-700 shadow-xl shadow-indigo-200 transition-all duration-300 transform hover:-translate-y-1 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        Masuk Sekarang
                    </button>

                    <div class="text-center mt-6">
                        <p class="text-sm text-gray-500">Belum memiliki akun?</p>
                        <a href="{{ route('register') }}" class="font-bold text-indigo-600 hover:text-indigo-500 transition-colors">
                            Daftar Gratis Sekarang &rarr;
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function togglePasswordVisibility() {
            const pwd = document.getElementById('password');
            if (pwd.type === 'password') {
                pwd.type = 'text';
            } else {
                pwd.type = 'password';
            }
        }
    </script>
</x-guest-layout>