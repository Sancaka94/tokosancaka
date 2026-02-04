<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengaturan Toko - {{ $tenant->name }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 text-slate-800">

    <div class="max-w-3xl mx-auto py-10 px-4">
        <div class="mb-6 flex items-center justify-between">
            <h1 class="text-2xl font-bold text-slate-900">Pengaturan Toko</h1>
            <a href="{{ route('tenant.dashboard') }}" class="text-blue-600 hover:underline">&larr; Kembali ke Dashboard</a>
        </div>

        <form action="{{ route('tenant.update') }}" method="POST" class="bg-white shadow rounded-2xl p-8 border border-slate-200">
            @csrf
            @method('PUT')

            <div class="mb-6">
                <h2 class="text-lg font-semibold text-slate-700 border-b pb-2 mb-4">Data Bisnis</h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Nama Usaha</label>
                        <input type="text" name="business_name" value="{{ old('business_name', $tenant->name) }}" class="w-full border rounded-lg px-3 py-2">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Subdomain (Tidak bisa diubah)</label>
                        <input type="text" value="{{ $tenant->subdomain }}.tokosancaka.com" disabled class="w-full border bg-slate-100 text-slate-500 rounded-lg px-3 py-2 cursor-not-allowed">
                    </div>
                </div>
            </div>

            <div class="mb-6">
                <h2 class="text-lg font-semibold text-slate-700 border-b pb-2 mb-4">Kontak & Keamanan</h2>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-slate-700 mb-1">Nama Pemilik</label>
                    <input type="text" name="owner_name" value="{{ old('owner_name', $user->name) }}" class="w-full border rounded-lg px-3 py-2">
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-slate-700 mb-1">Nomor WhatsApp</label>
                    <input type="text" name="whatsapp" value="{{ old('whatsapp', $tenant->whatsapp) }}" class="w-full border rounded-lg px-3 py-2">
                </div>

                <div class="border-t pt-4 mt-4">
                    <label class="block text-sm font-medium text-slate-700 mb-1">Ganti Password (Opsional)</label>
                    <input type="password" name="password" placeholder="Biarkan kosong jika tidak ingin mengganti" class="w-full border rounded-lg px-3 py-2 mb-2">
                    <input type="password" name="password_confirmation" placeholder="Konfirmasi Password Baru" class="w-full border rounded-lg px-3 py-2">
                </div>
            </div>

            <div class="flex justify-end">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded-lg transition">
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </div>

</body>
</html>
