<x-guest-layout>
    <div class="grid lg:grid-cols-2 min-h-screen w-full bg-white overflow-hidden">
        
        <div class="hidden lg:relative lg:flex bg-green-600 items-center justify-center p-16 overflow-hidden">
            
            <img src="https://ponpes.tokosancaka.com/storage/auth/ponpes.jpg" 
                 class="absolute inset-0 w-full h-full object-contain opacity-80 transition-transform duration-700 hover:scale-105" 
                 alt="Background">
            
            <div class="relative z-10 w-full max-w-lg">
    <div class="inline-flex items-center gap-2 bg-white/20 backdrop-blur-md border border-white/30 px-4 py-2 rounded-xl mb-8 shadow-lg">
        <span class="font-bold tracking-widest uppercase text-[10px] text-white drop-shadow-md">Sancaka e-Pesantren</span>
    </div>

    <h1 class="text-5xl font-black leading-tight text-white mb-6 drop-shadow-[0_2px_10px_rgba(255,255,255,0.3)]" 
        style="text-shadow: 2px 2px 4px rgba(0,0,0,0.5), 0 0 20px rgba(255,255,255,0.2);">
        Manajemen <br> Jadi Lebih <span class="text-indigo-200">Mudah.</span>
    </h1>

    <p class="text-xl text-indigo-50 font-medium leading-relaxed drop-shadow-md">
        Solusi digital terintegrasi untuk pengelolaan administrasi, keuangan, dan data santri secara real-time.
    </p>
</div>
        </div>

        <div class="flex items-center justify-center p-8 sm:p-12 lg:p-20 bg-white">
            <div class="w-full max-w-md mx-auto">
                
                <div class="mb-10 text-center lg:text-left">
                    <h2 class="text-4xl font-extrabold text-gray-900 tracking-tight">Selamat Datang</h2>
                    <p class="text-gray-500 mt-3 text-lg font-medium">Silakan masuk untuk akses akun Anda</p>
                </div>

                <x-auth-session-status class="mb-6" :status="session('status')" />

                <form method="POST" action="{{ route('login') }}" class="space-y-6">
                    @csrf

                    <div>
                        <x-input-label for="email" :value="__('Alamat Email')" class="font-bold text-gray-700 ml-1" />
                        <div class="relative mt-2 group">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-gray-400 group-focus-within:text-indigo-600">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                            </div>
                            <x-text-input id="email" class="block w-full rounded-2xl border-gray-200 bg-gray-50/50 py-4 pl-12 pr-5 focus:bg-white focus:ring-2 focus:ring-indigo-500 transition-all duration-300" 
                                          type="email" name="email" :value="old('email')" 
                                          required autofocus placeholder="admin@sancaka.com" />
                        </div>
                        <x-input-error :messages="$errors->get('email')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="password" :value="__('Kata Sandi')" class="font-bold text-gray-700 ml-1" />
                        <div class="relative mt-2 group">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-gray-400 group-focus-within:text-indigo-600">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                </svg>
                            </div>
                            
                            <x-text-input id="password" 
                                          class="block w-full rounded-2xl border-gray-200 bg-gray-50/50 py-4 pl-12 pr-12 focus:bg-white focus:ring-2 focus:ring-indigo-500 transition-all duration-300"
                                          type="password" name="password" required autocomplete="current-password" 
                                          placeholder="••••••••" />
                            
                            <button type="button" onclick="togglePasswordVisibility()" class="absolute inset-y-0 right-0 pr-4 flex items-center text-gray-400 hover:text-indigo-600 transition-colors focus:outline-none">
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
                            <input id="remember_me" type="checkbox" class="w-5 h-5 rounded-md border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" name="remember">
                            <span class="ml-3 text-sm text-gray-600 group-hover:text-indigo-600 transition-colors">Ingat saya</span>
                        </label>
                        <a class="text-sm font-bold text-indigo-600 hover:text-indigo-800 transition-colors" href="{{ route('password.request') }}">
                            Lupa Password?
                        </a>
                    </div>

                    <div class="pt-4">
                        <button type="submit" class="w-full py-4 px-4 border border-transparent text-lg font-bold rounded-2xl text-white bg-indigo-600 hover:bg-indigo-700 shadow-xl shadow-indigo-100 transition-all duration-300 transform hover:-translate-y-1">
                            Masuk Sekarang
                        </button>
                    </div>

                    <div class="mt-12 text-center border-t border-gray-100 pt-8">
                        <p class="text-gray-500 font-medium">Belum memiliki akun?</p>
                        <a href="{{ route('register') }}" class="mt-2 inline-block text-lg font-black text-indigo-600 hover:text-indigo-800 transition-colors">
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
            const iconBody = document.getElementById('eye-body');
            
            if (pwd.type === 'password') {
                pwd.type = 'text';
            } else {
                pwd.type = 'password';
            }
        }
    </script>
</x-guest-layout>