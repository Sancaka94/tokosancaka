<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Join Partner / Edit Data - Toko Sancaka</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="bg-slate-50 font-sans text-slate-800" x-data="partnerApp()" x-cloak>

    <div class="bg-gradient-to-br from-red-900 via-red-700 to-red-600 text-white pt-12 pb-24 px-6 text-center rounded-b-[3rem] shadow-2xl relative overflow-hidden">
        <div class="absolute inset-0 bg-pattern opacity-10"></div> <h1 class="text-4xl font-black mb-3 tracking-tight">Program Partner Sancaka</h1>
        <p class="text-red-100 text-base font-medium">Bergabunglah bersama kami atau perbarui data kemitraan Anda.</p>
    </div>

    <div class="max-w-xl mx-auto -mt-20 px-4 sm:px-6 relative z-20 pb-20">

        @if(session('success'))
        <div class="bg-emerald-500 text-white p-4 rounded-xl mb-6 shadow-lg flex items-center gap-3 animate-fade-in-down">
            <i class="fas fa-check-circle text-xl"></i> 
            <span class="font-medium">{{ session('success') }}</span>
        </div>
        @endif
        @if(session('error'))
        <div class="bg-red-500 text-white p-4 rounded-xl mb-6 shadow-lg flex items-center gap-3 animate-fade-in-down">
            <i class="fas fa-times-circle text-xl"></i> 
            <span class="font-medium">{{ session('error') }}</span>
        </div>
        @endif

        <div class="bg-white rounded-3xl shadow-2xl border border-slate-100 overflow-hidden">
            
            <div class="flex border-b border-slate-100 bg-slate-50/50">
                <button @click="switchMode('register')" 
                        class="flex-1 py-5 text-sm font-bold uppercase tracking-wider transition-all duration-300 border-b-2"
                        :class="mode === 'register' ? 'bg-white text-red-600 border-red-600 shadow-sm' : 'text-slate-400 border-transparent hover:text-slate-600 hover:bg-slate-100'">
                    <i class="fas fa-user-plus mr-2"></i> Daftar Baru
                </button>
                <button @click="switchMode('edit')" 
                        class="flex-1 py-5 text-sm font-bold uppercase tracking-wider transition-all duration-300 border-b-2"
                        :class="mode === 'edit' ? 'bg-white text-blue-600 border-blue-600 shadow-sm' : 'text-slate-400 border-transparent hover:text-slate-600 hover:bg-slate-100'">
                    <i class="fas fa-user-edit mr-2"></i> Edit Data
                </button>
            </div>

            <div class="p-6 sm:p-8">

                <div x-show="mode === 'register'" x-transition:enter="transition ease-out duration-300 transform" x-transition:enter-start="opacity-0 translate-x-4" x-transition:enter-end="opacity-100 translate-x-0">
                    <div class="text-center mb-6">
                        <h2 class="text-2xl font-bold text-slate-800">Formulir Pendaftaran</h2>
                        <p class="text-slate-500 text-sm mt-1">Isi data diri Anda untuk bergabung.</p>
                    </div>

                    <form action="{{ route('affiliate.store') }}" method="POST" class="space-y-5">
                        @csrf
                        
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-1 ml-1">Nama Lengkap</label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                                    <i class="fas fa-user"></i>
                                </span>
                                <input type="text" name="name" required class="w-full pl-10 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-red-500 focus:border-red-500 outline-none transition-all placeholder-slate-400" placeholder="Nama Lengkap Anda">
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-1 ml-1">No. WhatsApp</label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                                    <i class="fab fa-whatsapp text-lg"></i>
                                </span>
                                <input type="number" name="whatsapp" required class="w-full pl-10 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-red-500 focus:border-red-500 outline-none transition-all placeholder-slate-400" placeholder="08xxxxxxxxxx">
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-1 ml-1">Buat PIN Keamanan</label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                                    <i class="fas fa-lock"></i>
                                </span>
                                <input type="password" name="pin" required maxlength="6" inputmode="numeric" class="w-full pl-10 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-red-500 focus:border-red-500 outline-none transition-all placeholder-slate-400 tracking-widest text-center font-bold text-lg" placeholder="******" autocomplete="new-password">
                            </div>
                            <p class="text-[10px] text-slate-500 mt-1 ml-1"><i class="fas fa-info-circle mr-1"></i>PIN 6 digit angka untuk keamanan akun Anda.</p>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-1 ml-1">Alamat Lengkap</label>
                            <div class="relative">
                                <span class="absolute top-3 left-3 text-slate-400">
                                    <i class="fas fa-map-marker-alt"></i>
                                </span>
                                <textarea name="address" required rows="3" class="w-full pl-10 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-red-500 focus:border-red-500 outline-none transition-all placeholder-slate-400" placeholder="Alamat lengkap domisili..."></textarea>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase mb-1 ml-1">Nama Bank / E-Wallet</label>
                                <div class="relative">
                                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                                        <i class="fas fa-university"></i>
                                    </span>
                                    <input type="text" name="bank_name" required class="w-full pl-10 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-red-500 focus:border-red-500 outline-none transition-all placeholder-slate-400" placeholder="BCA / DANA">
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase mb-1 ml-1">No. Rekening</label>
                                <div class="relative">
                                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                                        <i class="fas fa-credit-card"></i>
                                    </span>
                                    <input type="number" name="bank_account_number" required class="w-full pl-10 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-red-500 focus:border-red-500 outline-none transition-all placeholder-slate-400" placeholder="Nomor Rekening">
                                </div>
                            </div>
                        </div>
                        
                        <button type="submit" class="w-full bg-red-600 text-white font-bold py-4 rounded-xl shadow-lg shadow-red-200 hover:bg-red-700 hover:shadow-xl hover:-translate-y-0.5 transition-all duration-200 mt-8 flex items-center justify-center gap-2">
                            <span>Daftar Sekarang</span> <i class="fas fa-arrow-right"></i>
                        </button>
                    </form>
                </div>

                <div x-show="mode === 'edit'" x-transition:enter="transition ease-out duration-300 transform" x-transition:enter-start="opacity-0 translate-x-4" x-transition:enter-end="opacity-100 translate-x-0" style="display: none;">
                    
                    <div x-show="!isVerified" x-transition>
                        <div class="text-center mb-8">
                            <div class="w-20 h-20 bg-blue-50 text-blue-600 rounded-full flex items-center justify-center mx-auto mb-4 text-3xl shadow-inner">
                                <i class="fas fa-shield-alt"></i>
                            </div>
                            <h2 class="text-2xl font-bold text-slate-800">Verifikasi Keamanan</h2>
                            <p class="text-sm text-slate-500 mt-1">Masukkan WhatsApp dan PIN untuk mengakses data Anda.</p>
                        </div>

                        <div class="space-y-5">
                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase mb-1 ml-1">No. WhatsApp</label>
                                <div class="relative">
                                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                                        <i class="fab fa-whatsapp text-lg"></i>
                                    </span>
                                    <input type="number" x-model="loginForm.whatsapp" class="w-full pl-10 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all placeholder-slate-400" placeholder="08xxxxxxxxxx">
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase mb-1 ml-1">PIN Keamanan</label>
                                <div class="relative">
                                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                                        <i class="fas fa-lock"></i>
                                    </span>
                                    <input type="password" x-model="loginForm.pin" class="w-full pl-10 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all placeholder-slate-400 tracking-widest text-center font-bold text-lg" placeholder="******" maxlength="6" inputmode="numeric">
                                </div>
                            </div>

                            <div x-show="errorMessage" x-transition class="bg-red-50 text-red-600 text-sm font-medium p-3 rounded-lg flex items-center justify-center gap-2 border border-red-100">
                                <i class="fas fa-exclamation-triangle"></i> <span x-text="errorMessage"></span>
                            </div>

                            <button @click="checkAccount()" :disabled="isLoading" class="w-full bg-blue-600 text-white font-bold py-4 rounded-xl shadow-lg shadow-blue-200 hover:bg-blue-700 hover:shadow-xl transition-all duration-200 disabled:opacity-70 disabled:cursor-not-allowed flex items-center justify-center gap-2">
                                <template x-if="!isLoading">
                                    <span><i class="fas fa-search mr-1"></i> Cari & Edit Data</span>
                                </template>
                                <template x-if="isLoading">
                                    <span><i class="fas fa-spinner fa-spin mr-1"></i> Memproses...</span>
                                </template>
                            </button>
                        </div>
                    </div>

                    <div x-show="isVerified" x-transition>
                        <div class="flex justify-between items-center mb-6 pb-4 border-b border-slate-100">
                            <div>
                                <h2 class="text-xl font-bold text-slate-800">Edit Data Partner</h2>
                                <p class="text-xs text-slate-500">Perbarui informasi terbaru Anda.</p>
                            </div>
                            <button @click="resetEdit()" class="text-xs font-bold text-red-500 bg-red-50 hover:bg-red-100 px-3 py-2 rounded-lg transition-colors">
                                <i class="fas fa-sign-out-alt mr-1"></i> Ganti Akun
                            </button>
                        </div>

                        <form action="{{ route('affiliate.update_public') }}" method="POST" class="space-y-5">
                            @csrf
                            @method('PUT')
                            
                            <input type="hidden" name="id" x-model="editData.id">
                            <input type="hidden" name="verification_pin" x-model="loginForm.pin">

                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase mb-1 ml-1">Nama Lengkap</label>
                                <input type="text" name="name" x-model="editData.name" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all">
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase mb-1 ml-1">No. WhatsApp</label>
                                <input type="number" name="whatsapp" x-model="editData.whatsapp" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all">
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase mb-1 ml-1">Alamat</label>
                                <textarea name="address" x-model="editData.address" rows="2" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all"></textarea>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1 ml-1">Nama Bank</label>
                                    <input type="text" name="bank_name" x-model="editData.bank_name" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1 ml-1">No. Rekening</label>
                                    <input type="number" name="bank_account_number" x-model="editData.bank_account_number" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all">
                                </div>
                            </div>

                            <div class="border-t border-slate-100 pt-5 mt-2">
                                <label class="flex items-center gap-2 text-xs font-bold text-orange-600 uppercase mb-2">
                                    <i class="fas fa-key"></i> Ganti PIN Baru (Opsional)
                                </label>
                                <input type="password" name="new_pin" placeholder="Biarkan kosong jika tidak ingin mengubah PIN" maxlength="6" inputmode="numeric" class="w-full px-4 py-3 bg-orange-50 border border-orange-200 rounded-xl focus:ring-2 focus:ring-orange-500 focus:border-orange-500 outline-none transition-all text-center tracking-widest placeholder-orange-300 text-orange-800 font-bold" autocomplete="new-password">
                            </div>

                            <button type="submit" class="w-full bg-blue-600 text-white font-bold py-4 rounded-xl shadow-lg shadow-blue-200 hover:bg-blue-700 hover:shadow-xl hover:-translate-y-0.5 transition-all duration-200 mt-6">
                                Simpan Perubahan
                            </button>
                        </form>
                    </div>

                </div>

            </div>
        </div>
        
        <div class="mt-8 text-center text-slate-400 text-sm">
            &copy; {{ date('Y') }} Toko Sancaka. Partner Program.
        </div>
    </div>

    <script>
        function partnerApp() {
            return {
                mode: 'register', // 'register' or 'edit'
                isLoading: false,
                isVerified: false, // User verified state
                errorMessage: '',
                
                // Login Data
                loginForm: {
                    whatsapp: '',
                    pin: ''
                },

                // Edit Data (Filled by Server)
                editData: {
                    id: '',
                    name: '',
                    whatsapp: '',
                    address: '',
                    bank_name: '',
                    bank_account_number: ''
                },

                switchMode(newMode) {
                    this.mode = newMode;
                    this.errorMessage = '';
                    // Optional: Reset form states when switching if desired
                },

                async checkAccount() {
                    if(!this.loginForm.whatsapp || !this.loginForm.pin) {
                        this.errorMessage = "Mohon isi WhatsApp dan PIN terlebih dahulu.";
                        return;
                    }

                    this.isLoading = true;
                    this.errorMessage = '';

                    try {
                        const response = await fetch("{{ route('affiliate.check_account') }}", {
                            method: "POST",
                            headers: {
                                "Content-Type": "application/json",
                                "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content
                            },
                            body: JSON.stringify(this.loginForm)
                        });

                        const result = await response.json();

                        if (response.ok && result.status === 'success') {
                            // Success: Populate edit form
                            this.editData = result.data;
                            this.isVerified = true; 
                        } else {
                            // Failure: Show error
                            this.errorMessage = result.message || "Validasi gagal. Cek kembali data Anda.";
                        }
                    } catch (error) {
                        this.errorMessage = "Terjadi kesalahan koneksi ke server.";
                        console.error(error);
                    } finally {
                        this.isLoading = false;
                    }
                },

                resetEdit() {
                    this.isVerified = false;
                    this.loginForm.pin = ''; // Clear PIN for security
                    this.editData = {};
                    this.errorMessage = '';
                }
            }
        }
    </script>
</body>
</html>