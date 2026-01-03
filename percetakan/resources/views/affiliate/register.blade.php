<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Partner Area - Toko Sancaka</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>[x-cloak] { display: none !important; }</style>
</head>
<body class="bg-slate-50 font-sans text-slate-800" x-data="partnerApp()" x-cloak>

    <div class="bg-gradient-to-br from-red-900 via-red-700 to-red-600 text-white pt-12 pb-24 px-6 text-center rounded-b-[3rem] shadow-2xl relative overflow-hidden">
        <h1 class="text-4xl font-black mb-3 tracking-tight">Partner Sancaka</h1>
        <p class="text-red-100 text-base font-medium">Daftar atau Kelola Akun Partner Anda</p>
    </div>

    <div class="max-w-xl mx-auto -mt-20 px-4 sm:px-6 relative z-20 pb-20">

        @if(session('success'))
        <div class="bg-emerald-500 text-white p-4 rounded-xl mb-6 shadow-lg flex items-center gap-3 animate-fade-in-down">
            <i class="fas fa-check-circle text-xl"></i> 
            <span class="font-medium text-sm">{{ session('success') }}</span>
        </div>
        @endif
        @if(session('error'))
        <div class="bg-red-500 text-white p-4 rounded-xl mb-6 shadow-lg flex items-center gap-3 animate-fade-in-down">
            <i class="fas fa-times-circle text-xl"></i> 
            <span class="font-medium text-sm">{{ session('error') }}</span>
        </div>
        @endif

        <div class="bg-white rounded-3xl shadow-2xl border border-slate-100 overflow-hidden">
            
            <div class="flex border-b border-slate-100 bg-slate-50/50">
                <button @click="switchMode('register')" 
                        class="flex-1 py-5 text-sm font-bold uppercase tracking-wider transition-all duration-300 border-b-2"
                        :class="mode === 'register' ? 'bg-white text-red-600 border-red-600 shadow-sm' : 'text-slate-400 border-transparent hover:text-slate-600 hover:bg-slate-100'">
                    <i class="fas fa-user-plus mr-2"></i> Daftar
                </button>
                <button @click="switchMode('edit')" 
                        class="flex-1 py-5 text-sm font-bold uppercase tracking-wider transition-all duration-300 border-b-2"
                        :class="mode === 'edit' ? 'bg-white text-blue-600 border-blue-600 shadow-sm' : 'text-slate-400 border-transparent hover:text-slate-600 hover:bg-slate-100'">
                    <i class="fas fa-sign-in-alt mr-2"></i> Login / Edit
                </button>
            </div>

            <div class="p-6 sm:p-8">

                <div x-show="mode === 'register'" x-transition:enter="transition ease-out duration-300 transform" x-transition:enter-start="opacity-0 translate-x-4" x-transition:enter-end="opacity-100 translate-x-0">
                    <form action="{{ route('affiliate.store') }}" method="POST" class="space-y-5">
                        @csrf
                        @include('affiliate.partials.form_inputs', ['isEdit' => false])
                        
                        <button type="submit" class="w-full bg-red-600 text-white font-bold py-4 rounded-xl shadow-lg shadow-red-200 hover:bg-red-700 hover:-translate-y-0.5 transition-all duration-200 mt-6 flex items-center justify-center gap-2">
                            <span>Daftar Sekarang</span> <i class="fas fa-arrow-right"></i>
                        </button>
                    </form>
                </div>

                <div x-show="mode === 'edit'" style="display: none;" x-transition:enter="transition ease-out duration-300 transform" x-transition:enter-start="opacity-0 translate-x-4" x-transition:enter-end="opacity-100 translate-x-0">
                    
                    <div x-show="!isVerified" x-transition>
                        <div class="text-center mb-6">
                            <div class="w-16 h-16 bg-blue-50 text-blue-600 rounded-full flex items-center justify-center mx-auto mb-4 text-2xl shadow-inner">
                                <i class="fas fa-user-lock"></i>
                            </div>
                            <h2 class="text-xl font-bold text-slate-800">Login Partner</h2>
                            <p class="text-xs text-slate-500 mt-1">Gunakan No. WhatsApp atau ID Partner Anda.</p>
                        </div>

                        <div class="space-y-5">
                            <div>
                                <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1 ml-1">No. WhatsApp / ID Partner</label>
                                <div class="relative">
                                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                                        <i class="fas fa-id-card"></i>
                                    </span>
                                    <input type="text" x-model="loginForm.login_key" class="w-full pl-10 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none transition-all placeholder-slate-400 font-medium text-slate-700" placeholder="Contoh: 08123... atau 15">
                                </div>
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1 ml-1">PIN Keamanan</label>
                                <div class="relative">
                                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                                        <i class="fas fa-lock"></i>
                                    </span>
                                    <input type="password" x-model="loginForm.pin" class="w-full pl-10 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none transition-all placeholder-slate-400 tracking-widest text-center font-bold text-lg" placeholder="******" maxlength="6" inputmode="numeric">
                                </div>
                            </div>

                            <div x-show="errorMessage" x-transition class="bg-red-50 text-red-600 text-xs font-bold p-3 rounded-lg flex items-center justify-center gap-2 border border-red-100">
                                <i class="fas fa-exclamation-triangle"></i> <span x-text="errorMessage"></span>
                            </div>

                            <button @click="checkAccount()" :disabled="isLoading" class="w-full bg-blue-600 text-white font-bold py-4 rounded-xl shadow-lg shadow-blue-200 hover:bg-blue-700 hover:-translate-y-0.5 transition-all duration-200 disabled:opacity-70 disabled:cursor-not-allowed">
                                <span x-show="!isLoading"><i class="fas fa-search mr-1"></i> Cari & Edit Data</span>
                                <span x-show="isLoading"><i class="fas fa-spinner fa-spin mr-1"></i> Memproses...</span>
                            </button>

                            <div class="mt-4 text-center">
                                <button @click="showForgotModal = true" class="text-xs font-bold text-slate-400 hover:text-blue-600 transition-colors flex items-center justify-center gap-1 mx-auto">
                                    <i class="fas fa-question-circle"></i> Lupa PIN atau No HP?
                                </button>
                            </div>
                        </div>
                    </div>

                    <div x-show="isVerified" x-transition>
                        <div class="flex justify-between items-center mb-6 pb-4 border-b border-slate-100">
                            <div>
                                <h2 class="text-lg font-bold text-slate-800">Halo, <span x-text="editData.name"></span></h2>
                                <p class="text-xs text-slate-500">ID: <span x-text="editData.id"></span> | WA: <span x-text="editData.whatsapp"></span></p>
                            </div>
                            <button @click="resetEdit()" class="text-[10px] font-bold text-red-500 bg-red-50 hover:bg-red-100 px-3 py-2 rounded-lg transition-colors">
                                <i class="fas fa-sign-out-alt"></i> Keluar
                            </button>
                        </div>

                        <form action="{{ route('affiliate.update_public') }}" method="POST" class="space-y-5">
                            @csrf
                            @method('PUT')
                            
                            <input type="hidden" name="id" x-model="editData.id">
                            <input type="hidden" name="verification_pin" x-model="loginForm.pin">

                            @include('affiliate.partials.form_inputs', ['isEdit' => true])

                            <div class="border-t border-slate-100 pt-5 mt-2">
                                <label class="flex items-center gap-2 text-[10px] font-bold text-orange-600 uppercase mb-2">
                                    <i class="fas fa-key"></i> Ganti PIN Baru (Opsional)
                                </label>
                                <input type="password" name="new_pin" placeholder="Biarkan kosong jika tidak ubah" maxlength="6" inputmode="numeric" class="w-full px-4 py-3 bg-orange-50 border border-orange-200 rounded-xl focus:ring-2 focus:ring-orange-500 outline-none transition-all text-center tracking-widest font-bold text-orange-800" autocomplete="new-password">
                            </div>

                            <button type="submit" class="w-full bg-blue-600 text-white font-bold py-4 rounded-xl shadow-lg shadow-blue-200 hover:bg-blue-700 hover:-translate-y-0.5 transition-all duration-200 mt-6">
                                Simpan Perubahan
                            </button>
                        </form>
                    </div>

                </div>
            </div>
        </div>
        
        <div class="mt-8 text-center text-slate-400 text-xs">
            &copy; {{ date('Y') }} Toko Sancaka.
        </div>
    </div>

    <div x-show="showForgotModal" 
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/80 backdrop-blur-sm"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         style="display: none;">
         
         <div class="bg-white rounded-3xl w-full max-w-sm p-6 shadow-2xl relative" @click.away="showForgotModal = false">
             <button @click="showForgotModal = false" class="absolute top-4 right-4 text-slate-300 hover:text-red-500 transition"><i class="fas fa-times text-xl"></i></button>

             <div class="text-center mb-6">
                 <h3 class="text-lg font-bold text-slate-800">Pusat Bantuan</h3>
                 <p class="text-xs text-slate-500">Pilih kendala Anda</p>
             </div>

             <div class="space-y-3">
                 <div class="p-4 bg-blue-50 rounded-xl border border-blue-100 text-center">
                    <i class="fas fa-mobile-alt text-2xl text-blue-500 mb-2"></i>
                    <h4 class="font-bold text-sm text-slate-700">Lupa No. HP?</h4>
                    <p class="text-xs text-slate-500 mb-3">Login menggunakan ID Partner & PIN Anda di menu utama.</p>
                    <button @click="showForgotModal = false" class="text-xs bg-blue-600 text-white px-3 py-1.5 rounded-lg font-bold">Oke, Saya Paham</button>
                 </div>

                 <div class="p-4 bg-orange-50 rounded-xl border border-orange-100 text-center">
                    <i class="fas fa-key text-2xl text-orange-500 mb-2"></i>
                    <h4 class="font-bold text-sm text-slate-700">Lupa PIN?</h4>
                    <p class="text-xs text-slate-500 mb-3">Masukkan No. WA, kami kirim PIN baru.</p>
                    <input type="number" x-model="forgotWhatsapp" class="w-full px-3 py-2 text-sm border rounded-lg mb-2 text-center" placeholder="No. WA Anda">
                    <button @click="requestNewPin()" :disabled="isLoadingForgot" class="w-full bg-orange-500 text-white py-2 rounded-lg text-xs font-bold">
                        <span x-show="!isLoadingForgot">Kirim PIN Baru</span>
                        <span x-show="isLoadingForgot">...</span>
                    </button>
                 </div>
             </div>
         </div>
    </div>

    <script>
        function partnerApp() {
            return {
                mode: 'register',
                isLoading: false,
                isVerified: false,
                errorMessage: '',
                
                showForgotModal: false,
                forgotWhatsapp: '',
                isLoadingForgot: false,

                // Login Form (Key bisa WA atau ID)
                loginForm: { login_key: '', pin: '' },
                editData: { id: '', name: '', whatsapp: '', address: '', bank_name: '', bank_account_number: '' },

                switchMode(newMode) {
                    this.mode = newMode;
                    this.errorMessage = '';
                },

                async checkAccount() {
                    if(!this.loginForm.login_key || !this.loginForm.pin) {
                        this.errorMessage = "Mohon isi ID/WA dan PIN.";
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
                            this.editData = result.data;
                            this.isVerified = true; 
                        } else {
                            this.errorMessage = result.message || "Data tidak ditemukan.";
                        }
                    } catch (error) {
                        this.errorMessage = "Kesalahan koneksi.";
                    } finally {
                        this.isLoading = false;
                    }
                },

                async requestNewPin() {
                    if(!this.forgotWhatsapp) { alert("Isi No. WA!"); return; }
                    this.isLoadingForgot = true;
                    try {
                        const response = await fetch("{{ route('affiliate.forgot_pin') }}", {
                            method: "POST",
                            headers: { "Content-Type": "application/json", "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content },
                            body: JSON.stringify({ whatsapp: this.forgotWhatsapp })
                        });
                        const res = await response.json();
                        alert(res.message);
                        if(res.status === 'success') this.showForgotModal = false;
                    } catch(e) { alert('Gagal.'); } 
                    finally { this.isLoadingForgot = false; }
                },

                resetEdit() {
                    this.isVerified = false;
                    this.loginForm.pin = '';
                    this.editData = {};
                }
            }
        }
    </script>
</body>
</html>