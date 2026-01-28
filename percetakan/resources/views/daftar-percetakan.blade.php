<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Layanan Percetakan Sancaka</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
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

                    <div class="md:col-span-2">
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-1">Nama Usaha Percetakan</label>
                        <input type="text" x-model="businessName" @input="generateSubdomain()" name="business_name" required
                               class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:bg-white transition-all outline-none"
                               placeholder="Contoh: Sancaka Digital Printing">
                    </div>

                    <div class="md:col-span-2 bg-blue-50 p-4 rounded-2xl border border-blue-100">
                        <label class="block text-[10px] font-black text-blue-800 uppercase mb-2">Konfigurasi Alamat URL (Subdomain)</label>
                        <div class="flex items-center text-sm md:text-base">
                            <span class="text-gray-400 font-mono">https://</span>
                            <input type="text" name="subdomain" x-model="subdomain" required
                                   class="bg-transparent font-bold text-blue-700 border-b-2 border-blue-200 focus:border-blue-600 outline-none px-1 transition-colors mx-1 min-w-[80px]"
                                   placeholder="nama-toko">
                            <span class="text-blue-800 font-semibold italic">.tokosancaka.com/percetakan</span>
                        </div>
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

                <div class="mb-6">
                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-1">Set Password Admin</label>
                    <input type="password" name="password" required
                           class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:bg-white transition-all outline-none">
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

</body>
</html>
