<x-guest-layout>
    <div class="min-h-screen w-full flex items-center justify-center relative bg-gray-900 overflow-hidden">

        <div class="absolute inset-0 z-0">
            <img src="https://ponpes.tokosancaka.com/storage/auth/bg_ponpes.jpg" 
                 class="w-full h-full object-cover blur-[2px] opacity-60 scale-105" 
                 alt="Background Pesantren">
            <div class="absolute inset-0 bg-gradient-to-br from-indigo-900/50 via-gray-900/50 to-black/70"></div>
        </div>

        <div class="relative z-10 w-full max-w-[1000px] mx-4 bg-white rounded-[2rem] shadow-2xl overflow-hidden grid lg:grid-cols-2 min-h-[550px] border border-white/10 ring-1 ring-black/5">
            
            <div class="hidden lg:flex flex-col justify-between p-12 bg-indigo-600 text-white relative overflow-hidden">
                
                <div class="absolute top-0 right-0 -mt-8 -mr-8 w-48 h-48 bg-indigo-500 rounded-full blur-3xl opacity-50"></div>
                <div class="absolute bottom-0 left-0 -mb-8 -ml-8 w-48 h-48 bg-indigo-800 rounded-full blur-3xl opacity-50"></div>

                <div class="relative z-10">
                    <div class="inline-flex items-center gap-2 bg-indigo-500/40 backdrop-blur-md border border-indigo-400/30 px-3 py-1.5 rounded-lg mb-6 shadow-sm">
                        <span class="w-2 h-2 rounded-full bg-green-400 animate-pulse"></span>
                        <span class="text-[10px] font-bold tracking-widest uppercase">Sancaka e-Pesantren</span>
                    </div>

                    <h1 class="text-4xl font-black leading-tight mb-4 tracking-tight">
                        Manajemen <br> 
                        <span class="text-indigo-200">Terintegrasi.</span>
                    </h1>
                    
                    <p class="text-indigo-100 text-sm leading-relaxed font-medium opacity-90 max-w-xs">
                        Kelola data santri, keuangan, dan administrasi pesantren dalam satu dashboard modern.
                    </p>
                </div>

                <div class="relative z-10 mt-auto pt-6 border-t border-indigo-500/40 flex items-center gap-3">
                    <div class="flex -space-x-2">
                        <div class="w-8 h-8 rounded-full border border-indigo-600 bg-gray-200"></div>
                        <div class="w-8 h-8 rounded-full border border-indigo-600 bg-gray-300"></div>
                        <div class="w-8 h-8 rounded-full border border-indigo-600 bg-gray-400"></div>
                    </div>
                    <div>
                        <p class="text-[10px] font-semibold text-indigo-100 uppercase tracking-wider">Trusted Platform</p>
                        <p class="text-xs font-bold text-white">100+ Pesantren Mitra</p>
                    </div>
                </div>
            </div>

            <div class="w-full flex flex-col justify-center p-8 sm:p-12 bg-white">
                
                <div class="text-center lg:text-left mb-8">
                    <h2 class="text-2xl font-bold text-gray-900">Selamat Datang</h2>
                    <p class="text-sm text-gray-500 mt-1">Silakan masuk dengan akun admin Anda.</p>
                </div>

                <x-auth-session-status class="mb-4" :status="session('status')" />

                <form method="POST" action="{{ route('login') }}" class="space-y-5">
                    @csrf

                    <div>
                        <label for="email" class="block text-xs font-bold text-gray-700 uppercase mb-2 ml-1">Alamat Email</label>
                        <div class="relative group">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-gray-400 group-focus-within:text-indigo-600 transition-colors">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207" />
                                </svg>
                            </div>
                            <input id="email" class="block w-full rounded-xl border-gray-200 bg-gray-50 py-3.5 pl-11 pr-4 text-sm text-gray-900 focus:bg-white focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 outline-none transition-all duration-200" 
                                   type="email" name="email" :value="old('email')" required autofocus placeholder="admin@sancaka.com" />
                        </div>
                        <x-input-error :messages="$errors->get('email')" class="mt-2" />
                    </div>

                    <div>
                        <div class="flex items-center justify-between mb-2 ml-1">
                            <label for="password" class="block text-xs font-bold text-gray-700 uppercase">Kata Sandi</label>
                        </div>
                        
                        <div class="relative group">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-gray-400 group-focus-within:text-indigo-600 transition-colors">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                </svg>
                            </div>
                            
                            <input id="password" 
                                   class="block w-full rounded-xl border-gray-200 bg-gray-50 py-3.5 pl-11 pr-12 text-sm text-gray-900 focus:bg-white focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 outline-none transition-all duration-200"
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

                    <div class="flex items-center justify-between pt-1">
                        <label class="flex items-center cursor-pointer">
                            <input id="remember_me" type="checkbox" class="w-4 h-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" name="remember">
                            <span class="ml-2 text-sm text-gray-600">Ingat saya</span>
                        </label>
                        <a class="text-sm font-bold text-indigo-600 hover:text-indigo-800 transition-colors" href="{{ route('password.request') }}">
                            Lupa Password?
                        </a>
                    </div>

                    <div class="pt-2">
                        <button type="submit" class="w-full py-3.5 px-4 bg-indigo-600 hover:bg-indigo-700 text-white text-base font-bold rounded-xl shadow-lg shadow-indigo-200 transition-all duration-300 transform hover:-translate-y-0.5 focus:ring-2 focus:ring-offset-2 focus:ring-indigo-600">
                            Masuk Sekarang
                        </button>
                    </div>

                    <div class="text-center mt-6 pt-4 border-t border-gray-50">
                        <p class="text-xs text-gray-500">Belum memiliki akun?</p>
                        <a href="{{ route('register') }}" class="mt-1 inline-block text-sm font-bold text-indigo-600 hover:text-indigo-800 transition-colors">
                            Daftar Gratis Disini &rarr;
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