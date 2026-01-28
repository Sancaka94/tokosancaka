<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Layanan Percetakan Sancaka</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center py-10 px-4">

    <div class="max-w-5xl w-full bg-white rounded-2xl shadow-xl overflow-hidden flex flex-col md:flex-row"
         x-data="{
            businessName: '{{ old('business_name') }}',
            subdomain: '{{ old('subdomain') }}',
            selectedPackage: '{{ old('package', 'trial') }}',
            generateSubdomain() {
                let text = this.businessName.toLowerCase();
                text = text.replace(/[^a-z0-9\s]/gi, '').replace(/[_\s]/g, '-');
                this.subdomain = text;
            },
            get price() {
                if(this.selectedPackage === 'trial') return 'Gratis';
                if(this.selectedPackage === 'monthly') return 'Rp 100.000';
                if(this.selectedPackage === 'yearly') return 'Rp 1.000.000';
                return '0';
            }
         }">

        <div class="md:w-4/12 bg-blue-900 p-8 text-white flex flex-col justify-between relative overflow-hidden">
            <div class="relative z-10">
                <h2 class="text-3xl font-bold mb-2">Sancaka POS</h2>
                <p class="text-blue-300 text-xs uppercase tracking-widest font-semibold mb-8">Special Edition: Percetakan</p>

                <div class="space-y-6">
                    <div class="flex items-start">
                        <span class="bg-blue-800 p-2 rounded-lg mr-4 shadow-lg">🚀</span>
                        <div>
                            <p class="font-bold text-sm">Cepat & Efisien</p>
                            <p class="text-xs text-blue-200">Manajemen order cetak banner & undangan dalam satu klik.</p>
                        </div>
                    </div>
                    <div class="flex items-start">
                        <span class="bg-blue-800 p-2 rounded-lg mr-4 shadow-lg">💰</span>
                        <div>
                            <p class="font-bold text-sm">Hitung Otomatis</p>
                            <p class="text-xs text-blue-200">Kalkulasi HPP per meter atau per cm secara akurat.</p>
                        </div>
                    </div>
                    <div class="flex items-start">
                        <span class="bg-blue-800 p-2 rounded-lg mr-4 shadow-lg">☁️</span>
                        <div>
                            <p class="font-bold text-sm">Cloud Based</p>
                            <p class="text-xs text-blue-200">Akses data transaksi kapanpun dan di manapun.</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-12 relative z-10 border-t border-blue-800 pt-6">
                <p class="text-xs text-blue-300">Butuh bantuan?</p>
                <p class="text-sm font-bold">0857-4580-8809</p>
            </div>

            <div class="absolute -bottom-10 -left-10 w-40 h-40 bg-blue-600 rounded-full opacity-30 blur-3xl"></div>
            <div class="absolute top-10 -right-10 w-32 h-32 bg-blue-400 rounded-full opacity-20 blur-2xl"></div>
        </div>

        <div class="md:w-8/12 p-8 md:p-12">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-2xl font-bold text-gray-800">Pendaftaran Tenant</h3>
                <span class="text-xs bg-blue-100 text-blue-700 px-2 py-1 rounded-full font-bold uppercase">Langkah 1 dari 1</span>
            </div>

            @if(session('error'))
                <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-r-lg" role="alert">
                    <p class="text-sm font-bold">Terjadi Kesalahan</p>
                    <p class="text-xs">{{ session('error') }}</p>
                </div>
            @endif

            <form action="{{ route('daftar.percetakan.store') }}" method="POST">
                @csrf

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-6">
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-1">Nama Lengkap</label>
                        <input type="text" name="owner_name" value="{{ old('owner_name') }}" required
                               class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:bg-white transition-all outline-none">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-1">Email Bisnis</label>
                        <input type="email" name="email" value="{{ old('email') }}" required
                               class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:bg-white transition-all outline-none">
                    </div>

                    <div class="mb-5">
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-1">Nomor WhatsApp (Aktif)</label>
                        <input type="tel"
                            id="whatsapp_input"
                            name="whatsapp"
                            value="{{ old('whatsapp') }}"
                            required
                            oninput="cleanWA(this)"
                            class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:bg-white transition-all outline-none"
                            placeholder="Contoh: 085745808xxx">
                        <p class="text-[10px] text-gray-400 mt-1 italic">*Format otomatis dibersihkan menjadi 08xxxxxxxxxx</p>
                        @error('whatsapp') <p class="text-red-500 text-[10px] mt-1 font-bold">{{ $message }}</p> @enderror
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-1">Nama Usaha Percetakan</label>
                        <input type="text" x-model="businessName" @input="generateSubdomain()" name="business_name" required
                               class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:bg-white transition-all outline-none"
                               placeholder="Contoh: Sancaka Digital Printing">
                    </div>

                    <div class="mb-5">
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-1">Alamat Subdomain</label>
                        <div class="flex items-center">
                            <input type="text"
                                id="subdomain_input"
                                name="subdomain"
                                value="{{ old('subdomain') }}"
                                required
                                minlength="3"
                                oninput="cleanSubdomain(this)"
                                class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-l-xl focus:ring-2 focus:ring-blue-500 focus:bg-white transition-all outline-none"
                                placeholder="contoh: app">
                            <span class="bg-gray-200 px-4 py-2.5 border border-l-0 border-gray-200 rounded-r-xl text-gray-600 text-sm font-bold">
                                .tokosancaka.com
                            </span>
                        </div>
                        <p id="subdomain_error" class="text-[10px] text-gray-400 mt-1 italic">*Hanya boleh huruf, minimal 3 karakter.</p>
                        @error('subdomain') <p class="text-red-500 text-[10px] mt-1 font-bold">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="mb-8">
                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-4 text-center">Pilih Paket Langganan</label>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">

                        <label class="relative cursor-pointer group" @click="selectedPackage = 'trial'">
                            <input type="radio" name="package" value="trial" class="peer hidden" x-model="selectedPackage">
                            <div class="p-4 border-2 rounded-2xl transition-all peer-checked:border-blue-600 peer-checked:bg-blue-50 hover:border-blue-300">
                                <div class="text-[10px] font-bold text-gray-400 uppercase">Personal</div>
                                <div class="font-black text-gray-800">14 Hari</div>
                                <div class="text-xs text-blue-600 font-bold">Free Trial</div>
                            </div>
                        </label>

                        <label class="relative cursor-pointer" @click="selectedPackage = 'monthly'">
                            <input type="radio" name="package" value="monthly" class="peer hidden" x-model="selectedPackage">
                            <div class="p-4 border-2 rounded-2xl transition-all peer-checked:border-blue-600 peer-checked:bg-blue-50 hover:border-blue-300">
                                <div class="text-[10px] font-bold text-blue-500 uppercase">Standard</div>
                                <div class="font-black text-gray-800">Bulanan</div>
                                <div class="text-xs text-blue-600 font-bold">Rp 100rb</div>
                                <span class="absolute -top-2 -right-1 bg-green-500 text-white text-[8px] font-bold px-2 py-0.5 rounded-full uppercase shadow-sm">Populer</span>
                            </div>
                        </label>

                        <label class="relative cursor-pointer" @click="selectedPackage = 'yearly'">
                            <input type="radio" name="package" value="yearly" class="peer hidden" x-model="selectedPackage">
                            <div class="p-4 border-2 rounded-2xl transition-all peer-checked:border-blue-600 peer-checked:bg-blue-50 hover:border-blue-300">
                                <div class="text-[10px] font-bold text-orange-500 uppercase">Premium</div>
                                <div class="font-black text-gray-800">Tahunan</div>
                                <div class="text-xs text-blue-600 font-bold">Rp 1jt</div>
                                <span class="absolute -top-2 -right-1 bg-orange-500 text-white text-[8px] font-bold px-2 py-0.5 rounded-full uppercase shadow-sm">Hemat 2 Bln</span>
                            </div>
                        </label>
                    </div>
                </div>

                <div class="mb-5">
                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-1">Set Password Admin</label>
                    <div class="relative">
                        <input type="password"
                            id="password"
                            name="password"
                            required
                            oninput="validateSecurity(this)"
                            class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:bg-white transition-all outline-none pr-12"
                            placeholder="••••••••">
                        <button type="button" onclick="togglePass('password', 'eye-1')" class="absolute right-4 top-2.5 text-gray-400">
                            <i id="eye-1" data-lucide="eye"></i>
                        </button>
                    </div>
                    <div id="security_notif" class="text-[10px] mt-1 space-y-1 text-gray-400 italic">
                        <p id="req_len">• Minimal 8 Karakter</p>
                        <p id="req_char">• Wajib: Huruf Besar, Kecil, Angka, & Simbol (*, @, dll)</p>
                    </div>
                </div>

                <div class="mb-5">
                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-1">Konfirmasi Password</label>
                    <div class="relative">
                        <input type="password"
                            id="password_confirmation"
                            name="password_confirmation"
                            required
                            oninput="checkMatch()"
                            class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:bg-white transition-all outline-none pr-12"
                            placeholder="Ulangi password">
                        <button type="button" onclick="togglePass('password_confirmation', 'eye-2')" class="absolute right-4 top-2.5 text-gray-400">
                            <i id="eye-2" data-lucide="eye"></i>
                        </button>
                    </div>
                    <p id="match_error" class="text-red-500 text-[10px] mt-1 font-bold hidden">❌ Password tidak cocok!</p>
                </div>

                <div class="bg-gray-800 rounded-2xl p-5 mb-8 text-white flex justify-between items-center shadow-xl">
                    <div>
                        <p class="text-[10px] text-gray-400 uppercase font-bold">Total Pembayaran</p>
                        <p class="text-xl font-black text-blue-400" x-text="price"></p>
                    </div>
                    <button type="submit" class="bg-blue-600 hover:bg-blue-500 text-white px-6 py-3 rounded-xl font-black text-sm transition-all transform hover:scale-105 active:scale-95 shadow-lg">
                        AKTIFKAN SEKARANG
                    </button>
                </div>

                <p class="text-[10px] text-center text-gray-400">
                    Dengan mendaftar, Anda menyetujui <a href="#" class="underline">Syarat & Ketentuan</a> CV. Sancaka Karya Hutama.
                </p>
            </form>
        </div>
    </div>

    <script>
        // 1. Fungsi Toggle Lihat Password
        function togglePass(inputId, iconId) {
            const input = document.getElementById(inputId);
            const icon = document.getElementById(iconId);

            if (input.type === "password") {
                input.type = "text";
                icon.setAttribute('data-lucide', 'eye-off');
            } else {
                input.type = "password";
                icon.setAttribute('data-lucide', 'eye');
            }
            lucide.createIcons(); // Refresh icon
        }

        // 2. Validasi Keamanan Password (Ketentuan Bapak)
        function validateSecurity(el) {
            const val = el.value;
            const reqLen = document.getElementById('req_len');
            const reqChar = document.getElementById('req_char');

            // RegEx: Huruf besar, kecil, angka, dan simbol
            const regex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{1,}$/;

            // Cek Panjang
            if (val.length >= 8) {
                reqLen.classList.add('text-green-500', 'not-italic', 'font-bold');
                reqLen.innerText = "✅ Minimal 8 Karakter";
            } else {
                reqLen.classList.remove('text-green-500', 'not-italic', 'font-bold');
                reqLen.innerText = "• Minimal 8 Karakter";
            }

            // Cek Karakter Komplit
            if (regex.test(val)) {
                reqChar.classList.add('text-green-500', 'not-italic', 'font-bold');
                reqChar.innerText = "✅ Keamanan Terpenuhi";
            } else {
                reqChar.classList.remove('text-green-500', 'not-italic', 'font-bold');
                reqChar.innerText = "• Wajib: Huruf Besar, Kecil, Angka, & Simbol";
            }
            checkMatch(); // Ikut cek kecocokan saat ngetik di pass utama
        }

        // 3. Cek Kecocokan Password 1 & 2
        function checkMatch() {
            const p1 = document.getElementById('password').value;
            const p2 = document.getElementById('password_confirmation').value;
            const error = document.getElementById('match_error');

            if (p2.length > 0 && p1 !== p2) {
                error.classList.remove('hidden');
            } else {
                error.classList.add('hidden');
            }
        }

        // 4. Mencegah Form Submit Jika Belum Benar
        document.querySelector('form').addEventListener('submit', function(e) {
            const p1 = document.getElementById('password').value;
            const p2 = document.getElementById('password_confirmation').value;
            const regex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/;

            if (!regex.test(p1)) {
                e.preventDefault();
                alert("Password belum memenuhi syarat keamanan!");
            } else if (p1 !== p2) {
                e.preventDefault();
                alert("Konfirmasi password tidak cocok!");
            }
        });

        // Inisialisasi awal icon
        lucide.createIcons();

        function cleanSubdomain(el) {
            // 1. Ambil nilai dan paksa jadi huruf kecil (lowercase) agar rapi di URL
            let val = el.value.toLowerCase();

            // 2. Hapus semua karakter yang BUKAN huruf (a-z)
            // Angka, simbol, spasi, dan karakter aneh akan langsung hilang
            val = val.replace(/[^a-z]/g, '');

            // 3. Update kembali nilai di dalam kotak input
            el.value = val;

            // 4. Validasi panjang minimal (Visual feedback)
            let errorText = document.getElementById('subdomain_error');
            if (val.length > 0 && val.length < 3) {
                errorText.classList.add('text-red-500');
                errorText.classList.remove('text-gray-400');
                errorText.innerText = "⚠️ Terlalu pendek! Minimal 3 huruf.";
            } else if (val.length >= 3) {
                errorText.classList.add('text-green-500');
                errorText.classList.remove('text-gray-400', 'text-red-500');
                errorText.innerText = "✅ Subdomain valid.";
            } else {
                errorText.classList.add('text-gray-400');
                errorText.classList.remove('text-green-500', 'text-red-500');
                errorText.innerText = "*Hanya boleh huruf, minimal 3 karakter.";
            }
        }

        function cleanWA(el) {
            // 1. Ambil nilai input
            let val = el.value;

            // 2. Hapus semua karakter yang BUKAN angka
            val = val.replace(/[^0-9]/g, '');

            // 3. Jika diawali '62', ubah jadi '0'
            if (val.startsWith('62')) {
                val = '0' + val.substring(2);
            }

            // 4. Jika diawali '8' (langsung angka 8 tanpa 0), tambahkan '0' di depan
            if (val.startsWith('8')) {
                val = '0' + val;
            }

            // 5. Update kembali nilai di dalam kotak input
            el.value = val;
        }

        // Tambahan: Pastikan saat form disubmit, nomor WA sudah bersih total
        document.querySelector('form').addEventListener('submit', function() {
            let waInput = document.getElementById('whatsapp_input');
            cleanWA(waInput);
        });
        </script>

</body>
</html>
