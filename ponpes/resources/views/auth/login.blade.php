<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sancaka e-Pesantren</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <script src="https://cdn.tailwindcss.com"></script>
    
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    }
                }
            }
        }
    </script>

    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
        .glass-effect {
            backdrop-filter: blur(10px);
        }
    </style>
</head>
<body class="bg-gray-100 text-slate-900 antialiased">

    <div class="min-h-screen w-full flex items-center justify-center p-4 relative overflow-hidden">

        <div class="absolute inset-0 z-0">
            <img src="https://ponpes.tokosancaka.com/storage/auth/bg_ponpes.jpg" 
                 class="w-full h-full object-cover blur-[3px] opacity-60 scale-105" 
                 alt="Background Pesantren">
            <div class="absolute inset-0 bg-gradient-to-br from-indigo-900/60 via-slate-900/50 to-black/70"></div>
        </div>

        <div class="relative z-10 w-full max-w-[1000px] bg-white rounded-2xl shadow-2xl overflow-hidden flex flex-col lg:flex-row min-h-[600px] border border-white/10">
            
            <div class="hidden lg:flex flex-col justify-between w-1/2 bg-indigo-700 p-12 text-white relative overflow-hidden">
                
                <div class="absolute top-0 right-0 -mt-12 -mr-12 w-48 h-48 bg-indigo-500 rounded-full blur-3xl opacity-50"></div>
                <div class="absolute bottom-0 left-0 -mb-12 -ml-12 w-48 h-48 bg-indigo-900 rounded-full blur-3xl opacity-50"></div>

                <div class="relative z-10">
                    <div class="inline-flex items-center gap-2 bg-indigo-600 border border-indigo-500/50 px-3 py-1.5 rounded-lg shadow-sm mb-8">
                        <span class="w-2 h-2 rounded-full bg-green-400 animate-pulse"></span>
                        <span class="text-[11px] font-bold tracking-widest uppercase text-indigo-100">Sancaka e-Pesantren</span>
                    </div>

                    <h1 class="text-4xl font-bold leading-tight mb-4">
                        Manajemen <br> Jadi Lebih <span class="text-indigo-300">Mudah.</span>
                    </h1>
                    
                    <p class="text-indigo-100/90 text-sm leading-relaxed font-light max-w-sm">
                        Solusi digital terintegrasi untuk pengelolaan administrasi, keuangan, dan data santri secara real-time dan akurat.
                    </p>
                </div>

                <div class="relative z-10 pt-8 flex items-center gap-4">
                    <div class="flex -space-x-2">
                        <div class="w-8 h-8 rounded-full border-2 border-indigo-700 bg-gray-300"></div>
                        <div class="w-8 h-8 rounded-full border-2 border-indigo-700 bg-gray-400"></div>
                        <div class="w-8 h-8 rounded-full border-2 border-indigo-700 bg-gray-500"></div>
                    </div>
                    <div class="text-xs font-medium text-indigo-200">
                        <strong class="text-white block">100+ Pesantren</strong> Percaya pada kami
                    </div>
                </div>
            </div>

            <div class="w-full lg:w-1/2 bg-white flex flex-col justify-center p-8 md:p-12 lg:p-16">
                
                <div class="mb-8">
                    <h2 class="text-2xl font-bold text-slate-900">Selamat Datang</h2>
                    <p class="text-sm text-slate-500 mt-2">Silakan masuk untuk mengakses dashboard admin.</p>
                </div>

                <form action="#" method="POST" class="space-y-5">
                    
                    <div>
                        <label for="email" class="block text-sm font-semibold text-slate-700 mb-2">Alamat Email</label>
                        <div class="relative group">
                            <input type="email" id="email" name="email" placeholder="admin@sancaka.com" required
                                class="w-full px-4 py-3.5 rounded-xl border border-slate-200 bg-slate-50 text-slate-900 text-sm focus:bg-white focus:border-indigo-600 focus:ring-2 focus:ring-indigo-600/20 outline-none transition-all duration-200 placeholder-slate-400">
                            
                            <div class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none text-slate-400">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <label for="password" class="block text-sm font-semibold text-slate-700">Kata Sandi</label>
                        </div>
                        
                        <div class="relative group">
                            <input type="password" id="password" name="password" placeholder="••••••••" required
                                class="w-full px-4 py-3.5 rounded-xl border border-slate-200 bg-slate-50 text-slate-900 text-sm focus:bg-white focus:border-indigo-600 focus:ring-2 focus:ring-indigo-600/20 outline-none transition-all duration-200 placeholder-slate-400">
                            
                            <button type="button" onclick="togglePassword()" class="absolute inset-y-0 right-0 pr-4 flex items-center text-slate-400 hover:text-indigo-600 transition-colors cursor-pointer focus:outline-none">
                                <svg id="eye-icon" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div class="flex items-center justify-between pt-1">
                        <label class="flex items-center cursor-pointer">
                            <input type="checkbox" name="remember" class="w-4 h-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                            <span class="ml-2 text-sm text-slate-600 font-medium">Ingat saya</span>
                        </label>
                        <a href="#" class="text-sm font-bold text-indigo-600 hover:text-indigo-800 transition-colors">
                            Lupa Password?
                        </a>
                    </div>

                    <div class="pt-2">
                        <button type="submit" class="w-full py-3.5 px-4 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold rounded-xl shadow-lg shadow-indigo-200 transition-all duration-300 transform hover:-translate-y-0.5 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-600">
                            Masuk Sekarang
                        </button>
                    </div>

                    <div class="text-center mt-6 pt-6 border-t border-slate-100">
                        <p class="text-sm text-slate-500">Belum memiliki akun?</p>
                        <a href="#" class="inline-block mt-1 text-sm font-bold text-indigo-600 hover:text-indigo-800 transition-colors">
                            Daftar Gratis Disini &rarr;
                        </a>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="absolute bottom-4 w-full text-center z-10 opacity-70">
            <p class="text-[10px] text-white font-medium tracking-wide uppercase">&copy; 2024 Sancaka e-Pesantren. All rights reserved.</p>
        </div>

    </div>

    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const eyeIcon = document.getElementById('eye-icon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                // Ubah warna icon saat aktif (opsional)
                eyeIcon.style.stroke = '#4f46e5'; 
            } else {
                passwordInput.type = 'password';
                eyeIcon.style.stroke = 'currentColor';
            }
        }
    </script>
</body>
</html>