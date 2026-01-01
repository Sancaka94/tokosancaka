<x-guest-layout>
    <div class="flex min-h-screen w-full bg-white">
        
        <div class="hidden lg:flex lg:w-1/2 bg-indigo-600 relative overflow-hidden">
            <img src="https://images.unsplash.com/photo-1541829070764-84a7d30dee3f?q=80&w=2070&auto=format&fit=crop" 
                 alt="Login Visual" 
                 class="absolute inset-0 w-full h-full object-cover opacity-30">
            
            <div class="relative z-10 flex flex-col justify-center px-20 text-white">
                <div class="mb-6">
                    <span class="bg-white/20 px-4 py-1 rounded-full text-sm font-medium backdrop-blur-sm">Sancaka ePesantren</span>
                </div>
                <h2 class="text-5xl font-extrabold leading-tight">Manajemen Pesantren <br> Jadi Lebih Mudah.</h2>
                <p class="mt-6 text-xl text-indigo-100 max-w-md">Solusi digital modern untuk administrasi, keuangan, dan perkembangan santri dalam satu pintu.</p>
            </div>

            <div class="absolute -bottom-20 -left-20 w-80 h-80 bg-indigo-500 rounded-full mix-blend-multiply filter blur-3xl opacity-50"></div>
        </div>

        <div class="w-full lg:w-1/2 flex items-center justify-center p-6 sm:p-12 md:p-20 bg-white">
            <div class="w-full max-w-md">
                
                <div class="lg:hidden text-center mb-10">
                    <h2 class="text-3xl font-bold text-indigo-600">Sancaka ePesantren</h2>
                </div>

                <div class="mb-10 text-center lg:text-left">
                    <h2 class="text-3xl font-bold text-gray-900">Selamat Datang</h2>
                    <p class="text-gray-500 mt-2 text-lg">Silakan masuk ke akun Anda</p>
                </div>

                <x-auth-session-status class="mb-4" :status="session('status')" />

                <form method="POST" action="{{ route('login') }}" class="space-y-6">
                    @csrf

                    <div>
                        <x-input-label for="email" :value="__('Alamat Email')" class="font-semibold text-gray-700" />
                        <x-text-input id="email" class="block mt-1.5 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-xl py-3 shadow-sm" 
                                      type="email" name="email" :value="old('email')" 
                                      required autofocus placeholder="nama@instansi.com" />
                        <x-input-error :messages="$errors->get('email')" class="mt-2" />
                    </div>

                    <div>
                        <div class="flex justify-between items-center">
                            <x-input-label for="password" :value="__('Kata Sandi')" class="font-semibold text-gray-700" />
                        </div>
                        <div class="relative mt-1.5">
                            <x-text-input id="password" 
                                          class="block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-xl py-3 pr-12 shadow-sm"
                                          type="password"
                                          name="password"
                                          required autocomplete="current-password" 
                                          placeholder="••••••••" />
                            
                            <button type="button" onclick="togglePassword()" class="absolute inset-y-0 right-0 pr-4 flex items-center text-gray-400 hover:text-indigo-600 transition-colors">
                                <svg id="eye-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.644C3.399 8.049 7.21 5 12 5c4.789 0 8.601 3.049 9.964 6.678.045.166.045.338 0 .504C20.601 15.951 16.79 19 12 19c-4.789 0-8.601-3.049-9.964-6.678z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                            </button>
                        </div>
                        <x-input-error :messages="$errors->get('password')" class="mt-2" />
                    </div>

                    <div class="flex items-center justify-between">
                        <label for="remember_me" class="inline-flex items-center">
                            <input id="remember_me" type="checkbox" class="rounded-md border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 w-5 h-5" name="remember">
                            <span class="ms-2 text-sm text-gray-600 tracking-tight">{{ __('Ingat saya') }}</span>
                        </label>

                        @if (Route::has('password.request'))
                            <a class="text-sm font-bold text-indigo-600 hover:text-indigo-500 underline-offset-4 hover:underline" href="{{ route('password.request') }}">
                                Lupa Password?
                            </a>
                        @endif
                    </div>

                    <div class="pt-2">
                        <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-4 rounded-xl shadow-lg shadow-indigo-200 transition duration-300 transform hover:-translate-y-1">
                            Masuk Ke Dashboard
                        </button>
                    </div>

                    <div class="text-center pt-8 border-t border-gray-100">
                        <p class="text-gray-600">Belum memiliki akun?</p>
                        <a href="https://ponpes.tokosancaka.com/register" class="inline-block mt-2 text-indigo-600 font-extrabold text-lg hover:text-indigo-800 transition-colors">
                            Daftar Sekarang &rarr;
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function togglePassword() {
            const input = document.getElementById('password');
            const icon = document.getElementById('eye-icon');
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.add('text-indigo-600');
            } else {
                input.type = 'password';
                icon.classList.remove('text-indigo-600');
            }
        }
    </script>
</x-guest-layout>