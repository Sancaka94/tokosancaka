<x-guest-layout>
    <div class="flex flex-col lg:flex-row min-h-screen w-full bg-white overflow-x-hidden">
        
        <div class="hidden lg:flex lg:w-1/2 bg-indigo-600 relative items-center justify-center p-12">
            <img src="https://images.unsplash.com/photo-1541829070764-84a7d30dee3f?q=80&w=2070&auto=format&fit=crop" 
                 class="absolute inset-0 w-full h-full object-cover opacity-40" alt="Login Visual">
            
            <div class="relative z-10 text-white text-center lg:text-left">
                <div class="mb-6 inline-block bg-white/20 backdrop-blur-md px-4 py-2 rounded-2xl border border-white/30">
                    <span class="font-bold tracking-widest uppercase text-xs">Sancaka ePesantren</span>
                </div>
                <h1 class="text-5xl xl:text-6xl font-black leading-tight mb-6">Manajemen <br> Jadi Lebih <span class="text-indigo-200">Mudah.</span></h1>
                <p class="text-lg xl:text-xl text-indigo-50 font-light max-w-lg leading-relaxed">
                    Solusi digital terintegrasi untuk pengelolaan administrasi, keuangan, dan data santri secara real-time.
                </p>
            </div>
        </div>

        <div class="flex-1 flex items-center justify-center p-6 sm:p-12 lg:p-20 bg-white">
            <div class="w-full max-w-md mx-auto">
                
                <div class="lg:hidden mb-8 flex justify-center">
                    <span class="bg-indigo-600 text-white px-6 py-2 rounded-2xl shadow-lg font-bold text-lg">ePesantren</span>
                </div>

                <div class="mb-10 text-center lg:text-left">
                    <h2 class="text-3xl font-extrabold text-gray-900 tracking-tight">Selamat Datang</h2>
                    <p class="text-gray-500 mt-2 text-base font-medium">Silakan masuk untuk akses akun Anda</p>
                </div>

                <x-auth-session-status class="mb-6" :status="session('status')" />

                <form method="POST" action="{{ route('login') }}" class="space-y-6">
                    @csrf

                    <div>
                        <x-input-label for="email" :value="__('Alamat Email')" class="font-bold text-gray-700 ml-1" />
                        <x-text-input id="email" class="block mt-2 w-full rounded-2xl border-gray-200 bg-gray-50 py-4 px-5 focus:ring-2 focus:ring-indigo-500" 
                                      type="email" name="email" :value="old('email')" required autofocus />
                        <x-input-error :messages="$errors->get('email')" class="mt-2" />
                    </div>

                    <div class="relative">
                        <x-input-label for="password" :value="__('Kata Sandi')" class="font-bold text-gray-700 ml-1" />
                        <div class="relative mt-2">
                            <x-text-input id="password" 
                                          class="block w-full rounded-2xl border-gray-200 bg-gray-50 py-4 pl-5 pr-12 focus:ring-2 focus:ring-indigo-500"
                                          type="password" name="password" required />
                            
                            <button type="button" onclick="togglePassword('password', 'eye-login')" 
                                    class="absolute right-4 top-1/2 -translate-y-1/2 flex items-center justify-center w-10 h-10 text-gray-400 hover:text-indigo-600 transition-colors">
                                <svg id="eye-login" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-6 h-6">
                                    <path d="M2.036 12.322a1.012 1.012 0 010-.644C3.399 8.049 7.21 5 12 5c4.789 0 8.601 3.049 9.964 6.678.045.166.045.338 0 .504C20.601 15.951 16.79 19 12 19c-4.789 0-8.601-3.049-9.964-6.678z" />
                                    <path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                            </button>
                        </div>
                        <x-input-error :messages="$errors->get('password')" class="mt-2" />
                    </div>

                    <div class="flex items-center justify-between">
                        <label class="flex items-center cursor-pointer">
                            <input id="remember_me" type="checkbox" class="w-4 h-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" name="remember">
                            <span class="ml-2 text-sm text-gray-600">Ingat saya</span>
                        </label>
                        @if (Route::has('password.request'))
                            <a class="text-sm font-bold text-indigo-600 hover:underline" href="{{ route('password.request') }}">Lupa Password?</a>
                        @endif
                    </div>

                    <button type="submit" class="w-full py-4 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-2xl shadow-lg transition duration-300 transform hover:-translate-y-1">
                        Masuk Sekarang
                    </button>

                    <div class="mt-10 text-center border-t border-gray-100 pt-8">
                        <p class="text-gray-500 text-sm">Belum memiliki akun?</p>
                        <a href="{{ route('register') }}" class="mt-2 inline-block text-base font-black text-indigo-600 hover:text-indigo-800">Daftar Gratis Sekarang &rarr;</a>
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