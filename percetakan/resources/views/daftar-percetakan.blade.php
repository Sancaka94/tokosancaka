<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Layanan Percetakan Sancaka</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center py-10 px-4">

    <div class="max-w-4xl w-full bg-white rounded-2xl shadow-xl overflow-hidden flex flex-col md:flex-row">

        <div class="md:w-5/12 bg-blue-900 p-8 text-white flex flex-col justify-between relative overflow-hidden">
            <div class="relative z-10">
                <h2 class="text-3xl font-bold mb-2">Sancaka POS</h2>
                <p class="text-blue-200 text-sm uppercase tracking-widest font-semibold">Special Edition: Percetakan</p>

                <div class="mt-8 space-y-4">
                    <div class="flex items-start">
                        <span class="bg-blue-700 p-2 rounded-lg mr-3">🚀</span>
                        <p class="text-sm text-blue-100">Manajemen order cetak banner, undangan, dll dengan mudah.</p>
                    </div>
                    <div class="flex items-start">
                        <span class="bg-blue-700 p-2 rounded-lg mr-3">💰</span>
                        <p class="text-sm text-blue-100">Hitung HPP dan keuntungan per cm/meter otomatis.</p>
                    </div>
                </div>
            </div>
            <div class="absolute -bottom-10 -right-10 w-40 h-40 bg-blue-600 rounded-full opacity-50 blur-2xl"></div>
        </div>

        <div class="md:w-7/12 p-8 md:p-12"
             x-data="{
                businessName: '{{ old('business_name') }}',
                subdomain: '{{ old('subdomain') }}',
                generateSubdomain() {
                    let text = this.businessName.toLowerCase();
                    text = text.replace(/[^a-z0-9\s]/gi, '').replace(/[_\s]/g, '-');
                    this.subdomain = text;
                }
             }">

            <h3 class="text-2xl font-bold text-gray-800 mb-6">Buat Akun Baru</h3>

            @if(session('error'))
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4" role="alert">
                    <p>{{ session('error') }}</p>
                </div>
            @endif

            <form action="{{ route('daftar.percetakan.store') }}" method="POST">
                @csrf

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Nama Lengkap</label>
                        <input type="text" name="owner_name" value="{{ old('owner_name') }}" required
                               class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Alamat Email</label>
                        <input type="email" name="email" value="{{ old('email') }}" required
                               class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Nama Usaha Percetakan</label>
                        <input type="text" x-model="businessName" @input="generateSubdomain()" name="business_name" required
                               class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                               placeholder="Contoh: Sancaka Digital Printing">
                    </div>

                    <div class="bg-blue-50 p-4 rounded-lg border border-blue-100">
                        <label class="block text-xs font-bold text-blue-800 uppercase mb-1">Link Akses Admin Anda</label>
                        <div class="flex items-center flex-wrap">
                            <span class="text-gray-500 text-sm">https://</span>
                            <input type="text" name="subdomain" x-model="subdomain" required
                                   class="bg-transparent font-bold text-blue-700 outline-none w-auto min-w-[50px] border-b border-blue-300 focus:border-blue-600 px-1 text-sm"
                                   placeholder="nama-toko">
                            <span class="text-gray-500 text-sm">.tokosancaka.com/percetakan</span>
                        </div>
                        @error('subdomain') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Password</label>
                        <input type="password" name="password" required
                               class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                    </div>
                </div>

                <div class="mt-8">
                    <button type="submit" class="w-full flex justify-center py-3 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-900 hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
                        Daftar Sekarang
                    </button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
