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
</head>
<body class="bg-slate-50 font-sans text-slate-800" x-data="partnerApp()">

    <div class="bg-gradient-to-r from-red-800 to-red-600 text-white pt-10 pb-20 px-6 text-center rounded-b-[3rem] shadow-xl relative overflow-hidden">
        <h1 class="text-3xl font-black mb-2">Program Partner Sancaka</h1>
        <p class="text-red-100 text-sm">Daftar baru atau perbarui data kemitraan Anda.</p>
    </div>

    <div class="max-w-xl mx-auto -mt-16 px-6 relative z-20 pb-20">

        @if(session('success'))
        <div class="bg-emerald-500 text-white p-4 rounded-xl mb-4 shadow-lg flex items-center gap-3">
            <i class="fas fa-check-circle"></i> <span>{{ session('success') }}</span>
        </div>
        @endif
        @if(session('error'))
        <div class="bg-red-500 text-white p-4 rounded-xl mb-4 shadow-lg flex items-center gap-3">
            <i class="fas fa-times-circle"></i> <span>{{ session('error') }}</span>
        </div>
        @endif

        <div class="bg-white rounded-3xl shadow-xl border border-slate-100 overflow-hidden">
            
            <div class="flex border-b border-slate-100">
                <button @click="switchMode('register')" 
                        class="flex-1 py-4 text-sm font-bold uppercase tracking-wider transition-colors duration-300"
                        :class="mode === 'register' ? 'bg-white text-red-600 border-b-2 border-red-600' : 'bg-slate-50 text-slate-400 hover:bg-slate-100'">
                    <i class="fas fa-user-plus mr-1"></i> Daftar Baru
                </button>
                <button @click="switchMode('edit')" 
                        class="flex-1 py-4 text-sm font-bold uppercase tracking-wider transition-colors duration-300"
                        :class="mode === 'edit' ? 'bg-white text-blue-600 border-b-2 border-blue-600' : 'bg-slate-50 text-slate-400 hover:bg-slate-100'">
                    <i class="fas fa-user-edit mr-1"></i> Edit Data
                </button>
            </div>

            <div class="p-8">

                <div x-show="mode === 'register'" x-transition:enter="transition ease-out duration-300">
                    <h2 class="text-xl font-bold mb-4 text-slate-800">Formulir Pendaftaran</h2>
                    <form action="{{ route('affiliate.store') }}" method="POST">
                        @csrf
                        @include('affiliate.partials.form_fields', ['isEdit' => false])
                        
                        <button type="submit" class="w-full bg-red-600 text-white font-bold py-3 rounded-xl shadow-lg hover:bg-red-700 transition mt-6">
                            Daftar Sekarang
                        </button>
                    </form>
                </div>

                <div x-show="mode === 'edit'" x-transition:enter="transition ease-out duration-300" style="display: none;">
                    
                    <div x-show="!isVerified">
                        <div class="text-center mb-6">
                            <div class="w-16 h-16 bg-blue-50 text-blue-500 rounded-full flex items-center justify-center mx-auto mb-3 text-2xl">
                                <i class="fas fa-lock"></i>
                            </div>
                            <h2 class="text-xl font-bold text-slate-800">Verifikasi Keamanan</h2>
                            <p class="text-xs text-slate-400">Masukkan WA dan PIN untuk mengedit data.</p>
                        </div>

                        <div class="space-y-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase mb-1">No. WhatsApp</label>
                                <input type="number" x-model="loginForm.whatsapp" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none" placeholder="08xxxxx">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase mb-1">PIN Keamanan</label>
                                <input type="password" x-model="loginForm.pin" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none text-center tracking-widest text-lg" placeholder="******" maxlength="6">
                            </div>

                            <div x-show="errorMessage" x-text="errorMessage" class="text-red-500 text-xs font-bold text-center"></div>

                            <button @click="checkAccount()" :disabled="isLoading" class="w-full bg-blue-600 text-white font-bold py-3 rounded-xl shadow-lg hover:bg-blue-700 transition disabled:opacity-50">
                                <span x-show="!isLoading">Cari & Edit Data</span>
                                <span x-show="isLoading"><i class="fas fa-spinner fa-spin"></i> Memproses...</span>
                            </button>
                        </div>
                    </div>

                    <div x-show="isVerified">
                        <div class="flex justify-between items-center mb-4">
                            <h2 class="text-xl font-bold text-slate-800">Edit Data Partner</h2>
                            <button @click="resetEdit()" class="text-xs text-slate-400 hover:text-red-500">Batal / Ganti Akun</button>
                        </div>

                        <form action="{{ route('affiliate.update_public') }}" method="POST">
                            @csrf
                            @method('PUT')
                            
                            <input type="hidden" name="id" x-model="editData.id">
                            <input type="hidden" name="verification_pin" x-model="loginForm.pin">

                            <div class="space-y-4">
                                <div>
                                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Nama Lengkap</label>
                                    <input type="text" name="name" x-model="editData.name" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl outline-none focus:ring-2 focus:ring-blue-500">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">No. WhatsApp</label>
                                    <input type="number" name="whatsapp" x-model="editData.whatsapp" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl outline-none focus:ring-2 focus:ring-blue-500">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Alamat</label>
                                    <textarea name="address" x-model="editData.address" rows="2" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                                </div>
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Bank</label>
                                        <input type="text" name="bank_name" x-model="editData.bank_name" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl outline-none focus:ring-2 focus:ring-blue-500">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">No. Rekening</label>
                                        <input type="number" name="bank_account_number" x-model="editData.bank_account_number" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl outline-none focus:ring-2 focus:ring-blue-500">
                                    </div>
                                </div>

                                <div class="border-t border-slate-100 pt-4 mt-4">
                                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Ganti PIN Baru (Opsional)</label>
                                    <input type="password" name="new_pin" placeholder="Kosongkan jika tidak ubah" maxlength="6" class="w-full px-4 py-3 bg-yellow-50 border border-yellow-200 rounded-xl outline-none focus:ring-2 focus:ring-yellow-500 text-center tracking-widest">
                                </div>
                            </div>

                            <button type="submit" class="w-full bg-blue-600 text-white font-bold py-3 rounded-xl shadow-lg hover:bg-blue-700 transition mt-6">
                                Simpan Perubahan
                            </button>
                        </form>
                    </div>

                </div>

            </div>
        </div>
    </div>

    <script>
        function partnerApp() {
            return {
                mode: 'register', // 'register' atau 'edit'
                isLoading: false,
                isVerified: false, // Apakah user sudah lolos cek WA+PIN
                errorMessage: '',
                
                // Data Login Guest
                loginForm: {
                    whatsapp: '',
                    pin: ''
                },

                // Data yang akan diedit (diisi oleh Server)
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
                },

                async checkAccount() {
                    if(!this.loginForm.whatsapp || !this.loginForm.pin) {
                        this.errorMessage = "Mohon isi WhatsApp dan PIN.";
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
                            // Sukses: Isi data ke form edit
                            this.editData = result.data;
                            this.isVerified = true; 
                        } else {
                            // Gagal
                            this.errorMessage = result.message || "Validasi gagal.";
                        }
                    } catch (error) {
                        this.errorMessage = "Terjadi kesalahan koneksi.";
                        console.error(error);
                    } finally {
                        this.isLoading = false;
                    }
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