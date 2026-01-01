<x-guest-layout>
    <div class="min-h-screen flex items-center justify-center p-4 relative overflow-hidden">
        
        <div class="absolute inset-0 z-0">
            <img src="https://ponpes.tokosancaka.com/storage/auth/bg_ponpes.jpg" 
                 class="w-full h-full object-cover blur-sm opacity-50" 
                 alt="Background">
            <div class="absolute inset-0 bg-slate-900/60 mix-blend-multiply"></div>
        </div>

        <div class="relative z-10 w-full max-w-[1000px] bg-white rounded-2xl shadow-2xl overflow-hidden flex flex-col lg:flex-row min-h-[600px]">
            
            <div class="hidden lg:flex flex-col justify-between w-1/2 bg-indigo-700 p-12 text-white relative overflow-hidden">
                
                <div class="absolute top-0 right-0 -mr-10 -mt-10 w-40 h-40 rounded-full bg-white/10 blur-2xl"></div>
                <div class="absolute bottom-0 left-0 -ml-10 -mb-10 w-40 h-40 rounded-full bg-indigo-500 blur-2xl"></div>

                <div class="relative z-10">
                    <div class="inline-flex items-center gap-2 bg-indigo-600 border border-indigo-500 px-3 py-1.5 rounded-lg shadow-sm">
                        <div class="w-2 h-2 rounded-full bg-green-400"></div>
                        <span class="text-xs font-bold tracking-widest uppercase text-indigo-100">Sancaka e-Pesantren</span>
                    </div>
                </div>

                <div class="relative z-10">
                    <h1 class="text-4xl font-bold leading-tight mb-4">
                        Kelola Pesantren <br> Secara <span class="text-indigo-200">Profesional.</span>
                    </h1>
                    <p class="text-indigo-100 text-sm leading-relaxed opacity-90 max-w-sm">
                        Sistem manajemen terintegrasi untuk administrasi, keuangan, dan akademik yang lebih efisien, transparan, dan akuntabel.
                    </p>
                </div>

                <div class="relative z-10 text-xs text-indigo-200 font-medium">
                    &copy; {{ date('Y') }} PT Toko Sancaka. All rights reserved.
                </div>
            </div>

            <div class="w-full lg:w-1/2 bg-white flex flex-col justify-center p-8 md:p-12 lg:p-16">
                
                <div class="mb-8">
                    <h2 class="text-2xl font-bold text-slate-900">Selamat Datang Kembali</h2>
                    <p class="text-sm text-slate-500 mt-2">Silakan masukkan detail akun Anda untuk masuk.</p>
                </div>

                <x-auth-session-status class="mb-4" :status="session('status')" />

                <form method="POST" action="{{ route('login') }}" class="space-y-6">
                    @csrf

                    <div class="space-y-2">
                        <label for="email" class="text-sm font-semibold text-slate-700">Alamat Email</label>
                        <div class="relative">
                            <input id="email" 
                                   class="w-full px-4 py-3 rounded-lg border border-slate-300 bg-white text-slate-900 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500 focus:ring-opacity-20 outline-none transition-all duration-200 placeholder-slate-400" 
                                   type="email" 
                                   name="email" 
                                   :value="old('email')" 
                                   required 
                                   autofocus 
                                   placeholder="nama@pesantren.com" />
                            
                            <div class="absolute inset-y-0 right-3 flex items-center pointer-events-none text-slate-400">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                </svg>
                            </div>
                        </div>
                        <x-input-error :messages="$errors->get('email')" class="mt-1" />
                    </div>

                    <div class="space-y-2">
                        <div class="flex items-center justify-between">
                            <label for="password" class="text-sm font-semibold text-slate-700">Kata Sandi</label>
                        </div>
                        
                        <div class="relative">
                            <input id="password" 
                                   class="w-full px-4 py-3 rounded-lg border border-slate-300 bg-white text-slate-900 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500 focus:ring-opacity-20 outline-none transition-all duration-200 placeholder-slate-400"
                                   type="password" 
                                   name="password" 
                                   required 
                                   autocomplete="current-password" 
                                   placeholder="Masukkan kata sandi" />
                            
                            <button type="button" onclick="togglePasswordVisibility()" class="absolute inset-y-0 right-3 flex items-center cursor-pointer text-slate-400 hover:text-indigo-600 transition-colors focus:outline-none">
                                <svg id="eye-icon" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path id="eye-path" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path id="eye-body" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                            </button>
                        </div>
                        <x-input-error :messages="$errors->get('password')" class="mt-1" />
                    </div>

                    <div class="flex items-center justify-between">
                        <label class="flex items-center cursor-pointer">
                            <input id="remember_me" type="checkbox" class="w-4 h-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500" name="remember">
                            <span class="ml-2 text-sm text-slate-600 font-medium">Ingat saya</span>
                        </label>
                        <a class="text-sm font-bold text-indigo-600 hover:text-indigo-800 transition-colors" href="{{ route('password.request') }}">
                            Lupa Password?
                        </a>
                    </div>

                    <button type="submit" class="w-full py-3 px-4 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold rounded-lg shadow-md hover:shadow-lg transition-all duration-200 transform hover:-translate-y-0.5 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-600">
                        Masuk Dashboard
                    </button>

                    <div class="text-center pt-2">
                        <p class="text-sm text-slate-500">
                            Belum memiliki akun? 
                            <a href="{{ route('register') }}" class="font-bold text-indigo-600 hover:text-indigo-800 transition-colors ml-1">
                                Daftar disini
                            </a>
                        </p>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function togglePasswordVisibility() {
            const pwd = document.getElementById('password');
            const iconPath = document.getElementById('eye-path');
            
            if (pwd.type === 'password') {
                pwd.type = 'text';
                // Opsional: Logika ganti icon visual disini jika diinginkan
            } else {
                pwd.type = 'password';
            }
        }
    </script>
</x-guest-layout>