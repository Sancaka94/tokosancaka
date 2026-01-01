<x-guest-layout>
    <div class="min-h-screen w-full flex items-center justify-center relative bg-gray-900 overflow-hidden font-sans">

        <div class="absolute inset-0 z-0">
            <img src="https://ponpes.tokosancaka.com/storage/auth/bg_ponpes.jpg" 
                 class="w-full h-full object-cover blur-[3px] scale-105 opacity-60" 
                 alt="Background Pesantren">
            <div class="absolute inset-0 bg-gradient-to-br from-indigo-900/40 to-black/60"></div>
        </div>

        <div class="relative z-10 w-full max-w-[1000px] mx-4 bg-white rounded-[2rem] shadow-2xl overflow-hidden grid lg:grid-cols-2 min-h-[600px] border border-white/20">
            
            <div class="hidden lg:flex flex-col justify-between p-12 bg-indigo-600 text-white relative overflow-hidden">
                
                <div class="absolute top-0 right-0 -mt-10 -mr-10 w-64 h-64 bg-indigo-500 rounded-full blur-3xl opacity-50"></div>
                <div class="absolute bottom-0 left-0 -mb-10 -ml-10 w-64 h-64 bg-indigo-700 rounded-full blur-3xl opacity-50"></div>

                <div class="relative z-10">
                    <div class="inline-flex items-center gap-2 bg-indigo-500/30 backdrop-blur-md border border-indigo-400/30 px-3 py-1.5 rounded-lg mb-8">
                        <span class="w-2 h-2 rounded-full bg-green-400 animate-pulse"></span>
                        <span class="text-[11px] font-bold tracking-widest uppercase">Sancaka e-Pesantren</span>
                    </div>

                    <h1 class="text-4xl font-black leading-tight mb-6">
                        Manajemen <br> Pesantren <br> 
                        <span class="text-indigo-200">Era Digital.</span>
                    </h1>
                    
                    <p class="text-indigo-100 text-base leading-relaxed font-light opacity-90">
                        Platform terintegrasi untuk mempermudah administrasi, keuangan, dan data santri Anda dalam satu dashboard yang modern.
                    </p>
                </div>

                <div class="relative z-10 mt-auto pt-8 border-t border-indigo-500/40 flex items-center gap-4">
                    <div class="flex -space-x-3">
                        <div class="w-9 h-9 rounded-full border-2 border-indigo-600 bg-gray-200"></div>
                        <div class="w-9 h-9 rounded-full border-2 border-indigo-600 bg-gray-300"></div>
                        <div class="w-9 h-9 rounded-full border-2 border-indigo-600 bg-gray-400"></div>
                    </div>
                    <div>
                        <p class="text-xs font-bold">100+ Pesantren</p>
                        <p class="text-[10px] text-indigo-200">Telah bergabung bersama kami</p>
                    </div>
                </div>
            </div>

            <div class="w-full flex flex-col justify-center p-8 sm:p-12 lg:p-14 bg-white">
                
                <div class="text-center lg:text-left mb-8">
                    <h2 class="text-2xl font-bold text-gray-900">Selamat Datang</h2>
                    <p class="text-sm text-gray-500 mt-2">Masuk untuk mengelola dashboard pesantren.</p>
                </div>

                <x-auth-session-status class="mb-4" :status="session('status')" />

                <form method="POST" action="{{ route('login') }}" class="space-y-5">
                    @csrf

                    <div>
                        <label for="email" class="block text-sm font-semibold text-gray-700 mb-2">Email</label>
                        <div class="relative">
                            <input id="email" 
                                   class="block w-full px-4 py-3.5 rounded-xl border border-gray-200 bg-gray-50 text-gray-900 text-sm focus:bg-white focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 transition-all duration-200 outline-none" 
                                   type="email" name="email" :value="old('email')" required autofocus placeholder="admin@pesantren.com" />
                            
                            <div class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none text-gray-400">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                </svg>
                            </div>
                        </div>
                        <x-input-error :messages="$errors->get('email')" class="mt-2" />
                    </div>

                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <label for="password" class="block text-sm font-semibold text-gray-700">Kata Sandi</label>
                            <a class="text-xs font-bold text-indigo-600 hover:text-indigo-800 transition-colors" href="{{ route('password.request') }}">
                                Lupa Password?
                            </a>
                        </div>
                        
                        <div class="relative group">
                            <input id="password" 
                                   class="block w-full px-4 py-3.5 rounded-xl border border-gray-200 bg-gray-50 text-gray-900 text-sm focus:bg-white focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 transition-all duration-200 outline-none"
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

                    <div class="flex items-center">
                        <input id="remember_me" type="checkbox" class="w-4 h-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 cursor-pointer" name="remember">
                        <span class="ml-2 text-sm text-gray-600">Ingat saya</span>
                    </div>

                    <div class="pt-2">
                        <button type="submit" class="w-full py-3.5 px-4 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-xl shadow-lg shadow-indigo-200 transition-all duration-300 transform hover:-translate-y-0.5 focus:ring-2 focus:ring-offset-2 focus:ring-indigo-600">
                            Masuk Sekarang
                        </button>
                    </div>

                    <div class="text-center mt-6">
                        <p class="text-xs text-gray-500">
                            Belum memiliki akun? 
                            <a href="{{ route('register') }}" class="font-bold text-indigo-600 hover:text-indigo-800 ml-1">
                                Daftar Gratis
                            </a>
                        </p>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="absolute bottom-4 w-full text-center z-10">
            <p class="text-xs text-gray-400 font-medium">&copy; {{ date('Y') }} Sancaka e-Pesantren. All rights reserved.</p>
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