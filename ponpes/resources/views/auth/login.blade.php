<x-guest-layout>
    <div class="flex flex-col lg:flex-row min-h-screen w-full bg-white">
        
        <div class="hidden lg:flex lg:w-1/2 bg-indigo-600 relative items-center justify-center p-12 overflow-hidden">
            <img src="https://images.unsplash.com/photo-1541829070764-84a7d30dee3f?q=80&w=2070&auto=format&fit=crop" 
                 class="absolute inset-0 w-full h-full object-cover opacity-40">
            
            <div class="relative z-10 text-white">
                <div class="mb-6 inline-block bg-white/20 backdrop-blur-md px-4 py-2 rounded-2xl border border-white/30">
                    <span class="font-bold tracking-widest uppercase text-xs">Sancaka ePesantren</span>
                </div>
                <h1 class="text-6xl font-black leading-tight mb-6">Manajemen <br> Jadi Lebih <span class="text-indigo-200">Mudah.</span></h1>
                <p class="text-xl text-indigo-50 font-light max-w-lg leading-relaxed">
                    Solusi digital terintegrasi untuk pengelolaan administrasi, keuangan, dan data santri secara real-time.
                </p>
            </div>

            <div class="absolute -bottom-24 -left-24 w-96 h-96 bg-white/10 rounded-full blur-3xl"></div>
        </div>

        <div class="flex-1 flex items-center justify-center p-8 sm:p-16 lg:p-24 bg-white">
            <div class="w-full max-w-md">
                
                <div class="lg:hidden mb-8 flex justify-center">
                    <div class="bg-indigo-600 text-white p-3 rounded-2xl shadow-lg font-bold">
                        ePesantren
                    </div>
                </div>

                <div class="mb-10 text-center lg:text-left">
                    <h2 class="text-4xl font-extrabold text-gray-900 tracking-tight">Selamat Datang</h2>
                    <p class="text-gray-500 mt-3 text-lg font-medium">Silakan masuk untuk akses dashboard</p>
                </div>

                <x-auth-session-status class="mb-6" :status="session('status')" />

                <form method="POST" action="{{ route('login') }}" class="space-y-6">
                    @csrf

                    <div>
                        <x-input-label for="email" :value="__('Alamat Email')" class="font-bold text-gray-700 ml-1" />
                        <div class="relative mt-2">
                            <x-text-input id="email" class="block w-full rounded-2xl border-gray-200 bg-gray-50/50 py-4 px-5 focus:bg-white focus:ring-2 focus:ring-indigo-500 transition-all duration-300" 
                                          type="email" name="email" :value="old('email')" 
                                          required autofocus placeholder="admin@sancaka.com" />
                        </div>
                        <x-input-error :messages="$errors->get('email')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="password" :value="__('Kata Sandi')" class="font-bold text-gray-700 ml-1" />
                        <div class="relative mt-2" x-data="{ show: false }">
                            <x-text-input id="password" 
                                          class="block w-full rounded-2xl border-gray-200 bg-gray-50/50 py-4 px-5 pr-14 focus:bg-white focus:ring-2 focus:ring-indigo-500 transition-all duration-300"
                                          :type=" 'password' "
                                          name="password"
                                          required autocomplete="current-password" 
                                          placeholder="••••••••" />
                            
                            <button type="button" onclick="togglePasswordVisibility()" class="absolute right-4 top-1/2 -translate-y-1/2 p-2 text-gray-400 hover:text-indigo-600 transition-colors">
                                <svg id="eye-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-6 h-6">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.644C3.399 8.049 7.21 5 12 5c4.789 0 8.601 3.049 9.964 6.678.045.166.045.338 0 .504C20.601 15.951 16.79 19 12 19c-4.789 0-8.601-3.049-9.964-6.678z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                            </button>
                        </div>
                        <x-input-error :messages="$errors->get('password')" class="mt-2" />
                    </div>

                    <div class="flex items-center justify-between px-1">
                        <label class="flex items-center cursor-pointer group">
                            <input id="remember_me" type="checkbox" class="w-5 h-5 rounded-md border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 transition-all" name="remember">
                            <span class="ml-3 text-sm text-gray-600 group-hover:text-indigo-600 transition-colors">Ingat saya</span>
                        </label>

                        @if (Route::has('password.request'))
                            <a class="text-sm font-bold text-indigo-600 hover:text-indigo-500 hover:underline underline-offset-4 decoration-2 transition-all" href="{{ route('password.request') }}">
                                Lupa Password?
                            </a>
                        @endif
                    </div>

                    <div class="pt-4">
                        <button type="submit" class="group relative w-full flex justify-center py-4 px-4 border border-transparent text-lg font-bold rounded-2xl text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 shadow-xl shadow-indigo-100 transition-all duration-300 transform hover:-translate-y-1">
                            Masuk Ke Dashboard
                        </button>
                    </div>

                    <div class="mt-12 text-center border-t border-gray-100 pt-8">
                        <p class="text-gray-500 font-medium">Belum memiliki akun?</p>
                        <a href="https://ponpes.tokosancaka.com/register" class="mt-2 inline-block text-lg font-black text-indigo-600 hover:text-indigo-800 transition-colors">
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
            const icon = document.getElementById('eye-icon');
            if (pwd.type === 'password') {
                pwd.type = 'text';
                icon.classList.add('text-indigo-600');
            } else {
                pwd.type = 'password';
                icon.classList.remove('text-indigo-600');
            }
        }
    </script>
</x-guest-layout>