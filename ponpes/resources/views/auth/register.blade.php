<x-guest-layout>
    <div class="flex flex-col lg:flex-row min-h-screen w-full bg-white">
        
        <div class="hidden lg:flex lg:w-1/2 bg-indigo-600 relative items-center justify-center p-12 overflow-hidden">
            <img src="https://images.unsplash.com/photo-1541829070764-84a7d30dee3f?q=80&w=2070&auto=format&fit=crop" 
                 class="absolute inset-0 w-full h-full object-cover opacity-40">
            
            <div class="relative z-10 text-white">
                <div class="mb-6 inline-block bg-white/20 backdrop-blur-md px-4 py-2 rounded-2xl border border-white/30">
                    <span class="font-bold tracking-widest uppercase text-xs">Sancaka ePesantren</span>
                </div>
                <h1 class="text-6xl font-black leading-tight mb-6">Bergabung <br> Bersama <span class="text-indigo-200">Kami.</span></h1>
                <p class="text-xl text-indigo-50 font-light max-w-lg leading-relaxed">
                    Mulai digitalisasi pesantren Anda hari ini untuk pengelolaan yang lebih transparan dan efisien.
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
                    <h2 class="text-4xl font-extrabold text-gray-900 tracking-tight">Daftar Akun</h2>
                    <p class="text-gray-500 mt-3 text-lg font-medium">Lengkapi data untuk membuat akun baru</p>
                </div>

                <form method="POST" action="{{ route('register') }}" class="space-y-5">
                    @csrf

                    <div>
                        <x-input-label for="name" :value="__('Nama Lengkap')" class="font-bold text-gray-700 ml-1" />
                        <x-text-input id="name" class="block mt-2 w-full rounded-2xl border-gray-200 bg-gray-50/50 py-4 px-5 focus:bg-white focus:ring-2 focus:ring-indigo-500 transition-all duration-300" 
                                      type="text" name="name" :value="old('name')" required autofocus autocomplete="name" placeholder="Nama lengkap Anda" />
                        <x-input-error :messages="$errors->get('name')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="email" :value="__('Alamat Email')" class="font-bold text-gray-700 ml-1" />
                        <x-text-input id="email" class="block mt-2 w-full rounded-2xl border-gray-200 bg-gray-50/50 py-4 px-5 focus:bg-white focus:ring-2 focus:ring-indigo-500 transition-all duration-300" 
                                      type="email" name="email" :value="old('email')" required placeholder="email@instansi.com" />
                        <x-input-error :messages="$errors->get('email')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="password" :value="__('Kata Sandi')" class="font-bold text-gray-700 ml-1" />
                        <div class="relative mt-2">
                            <x-text-input id="password" 
                                          class="block w-full rounded-2xl border-gray-200 bg-gray-50/50 py-4 px-5 pr-14 focus:bg-white focus:ring-2 focus:ring-indigo-500 transition-all duration-300"
                                          type="password" name="password" required autocomplete="new-password" placeholder="Minimal 8 karakter" />
                            <button type="button" onclick="toggleVisibility('password', 'eye-1')" class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 hover:text-indigo-600">
                                <svg id="eye-1" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-6 h-6">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.644C3.399 8.049 7.21 5 12 5c4.789 0 8.601 3.049 9.964 6.678.045.166.045.338 0 .504C20.601 15.951 16.79 19 12 19c-4.789 0-8.601-3.049-9.964-6.678z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                            </button>
                        </div>
                        <x-input-error :messages="$errors->get('password')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="password_confirmation" :value="__('Konfirmasi Kata Sandi')" class="font-bold text-gray-700 ml-1" />
                        <div class="relative mt-2">
                            <x-text-input id="password_confirmation" 
                                          class="block w-full rounded-2xl border-gray-200 bg-gray-50/50 py-4 px-5 pr-14 focus:bg-white focus:ring-2 focus:ring-indigo-500 transition-all duration-300"
                                          type="password" name="password_confirmation" required placeholder="Ulangi kata sandi" />
                            <button type="button" onclick="toggleVisibility('password_confirmation', 'eye-2')" class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 hover:text-indigo-600">
                                <svg id="eye-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-6 h-6">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.644C3.399 8.049 7.21 5 12 5c4.789 0 8.601 3.049 9.964 6.678.045.166.045.338 0 .504C20.601 15.951 16.79 19 12 19c-4.789 0-8.601-3.049-9.964-6.678z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                            </button>
                        </div>
                        <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
                    </div>

                    <div class="pt-4">
                        <button type="submit" class="w-full py-4 border border-transparent text-lg font-bold rounded-2xl text-white bg-indigo-600 hover:bg-indigo-700 shadow-xl shadow-indigo-100 transition-all duration-300 transform hover:-translate-y-1">
                            Daftar Sekarang
                        </button>
                    </div>

                    <div class="mt-8 text-center border-t border-gray-100 pt-8">
                        <p class="text-gray-500 font-medium">Sudah memiliki akun?</p>
                        <a href="{{ route('login') }}" class="mt-2 inline-block text-lg font-black text-indigo-600 hover:text-indigo-800">
                            Masuk Ke Akun &rarr;
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function toggleVisibility(inputId, iconId) {
            const input = document.getElementById(inputId);
            const icon = document.getElementById(iconId);
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