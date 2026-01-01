<x-guest-layout>
    <div class="flex flex-col md:flex-row h-screen w-full bg-white overflow-hidden">
        
        <div class="hidden md:relative md:flex md:w-1/2 bg-green-900 items-center justify-center text-center px-6 lg:px-16 z-0">
            
            <img src="https://ponpes.tokosancaka.com/storage/auth/ponpes.jpg" 
                 class="absolute inset-0 w-full h-full object-cover opacity-50 blur-[1px]" 
                 style="object-position: center;" 
                 alt="Background Sancaka">
            
            <div class="relative z-10 w-full max-w-lg">
                <div class="inline-flex items-center gap-2 bg-white/10 backdrop-blur-md border border-white/20 px-4 py-2 rounded-xl mb-6 shadow-lg">
                    <span class="font-bold tracking-widest uppercase text-xs text-white drop-shadow-md">Sancaka ePesantren</span>
                </div>
                
                <h1 class="text-4xl lg:text-5xl font-black leading-tight text-white mb-6 drop-shadow-[0_4px_3px_rgba(0,0,0,0.8)]">
                    Manajemen <br> Jadi Lebih <span class="text-green-300">Mudah.</span>
                </h1>
                
                <p class="text-lg text-gray-100 font-medium leading-relaxed drop-shadow-md">
                    Solusi digital terintegrasi untuk pengelolaan administrasi, keuangan, dan data santri.
                </p>
            </div>
        </div>

        <div class="flex w-full md:w-1/2 items-center justify-center bg-white h-full overflow-y-auto py-10 px-6 sm:px-12">
            <div class="w-full max-w-md mx-auto">
                
                <div class="mb-8 text-center md:text-left">
                    <h2 class="text-3xl font-extrabold text-gray-900 tracking-tight">Selamat Datang</h2>
                    <p class="text-gray-500 mt-2 text-sm font-medium">Silakan masuk untuk akses dashboard.</p>
                </div>

                <x-auth-session-status class="mb-6" :status="session('status')" />

                <form method="POST" action="{{ route('login') }}" class="space-y-5">
                    @csrf

                    <div>
                        <x-input-label for="email" :value="__('Alamat Email')" class="font-bold text-gray-700 ml-1" />
                        <div class="relative mt-2">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-gray-400">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                            </div>
                            <x-text-input id="email" class="block w-full rounded-xl border-gray-300 bg-gray-50 py-3.5 pl-11 pr-4 focus:bg-white focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all" 
                                          type="email" name="email" :value="old('email')" 
                                          required autofocus placeholder="admin@sancaka.com" />
                        </div>
                        <x-input-error :messages="$errors->get('email')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="password" :value="__('Kata Sandi')" class="font-bold text-gray-700 ml-1" />
                        <div class="relative mt-2">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-gray-400">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                </svg>
                            </div>
                            
                            <x-text-input id="password" 
                                          class="block w-full rounded-xl border-gray-300 bg-gray-50 py-3.5 pl-11 pr-11 focus:bg-white focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all"
                                          type="password" name="password" required autocomplete="current-password" 
                                          placeholder="••••••••" />
                            
                            <button type="button" onclick="togglePasswordVisibility()" class="absolute inset-y-0 right-0 pr-4 flex items-center text-gray-400 hover:text-green-600 transition-colors focus:outline-none">
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
                            <input id="remember_me" type="checkbox" class="w-4 h-4 rounded border-gray-300 text-green-600 focus:ring-green-500" name="remember">
                            <span class="ml-2 text-sm text-gray-600 group-hover:text-green-600 transition-colors">Ingat saya</span>
                        </label>
                        <a class="text-sm font-bold text-green-600 hover:text-green-800 transition-colors" href="{{ route('password.request') }}">
                            Lupa Password?
                        </a>
                    </div>

                    <div class="pt-2">
                        <button type="submit" class="w-full py-3.5 px-4 border border-transparent text-lg font-bold rounded-xl text-white bg-green-600 hover:bg-green-700 shadow-lg shadow-green-200 transition-all duration-300 transform hover:-translate-y-1">
                            Masuk
                        </button>
                    </div>

                    <div class="mt-8 text-center border-t border-gray-100 pt-6">
                        <p class="text-gray-500 text-sm">Belum memiliki akun?</p>
                        <a href="{{ route('register') }}" class="mt-1 inline-block text-base font-bold text-green-600 hover:text-green-800 transition-colors">
                            Daftar Disini &rarr;
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