@extends('admin')

@section('content')

@if(session('success'))
    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded shadow-sm">
        {{ session('success') }}
    </div>
@endif
@if(session('error'))
    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded shadow-sm">
        {{ session('error') }}
    </div>
@endif

@if(!$loggedIn)
    <div class="max-w-md mx-auto bg-white p-8 rounded-xl shadow-md border border-gray-100">
        <h2 class="text-2xl font-bold text-center mb-2 text-gray-800">Login Administrator</h2>
        <p class="text-sm text-center text-gray-500 mb-6">Silakan masuk untuk mengelola sumber pencarian.</p>

        <form action="{{ route('search.admin.login') }}" method="POST">
            @csrf
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-semibold mb-2">Username</label>
                <input type="text" name="username" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required>
            </div>
            <div class="mb-6">
                <label class="block text-gray-700 text-sm font-semibold mb-2">Password</label>
                <input type="password" name="password" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required>
            </div>
            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg transition duration-200">Masuk Panel</button>
        </form>
    </div>
@else

    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200 mb-8">
        <div class="flex justify-between items-center mb-6 border-b pb-4">
            <h2 class="text-xl font-bold text-gray-800">➕ Tambah Sumber Grup Baru</h2>
            <form action="{{ route('search.admin.logout') }}" method="POST">
                @csrf
                <button type="submit" class="text-red-600 hover:bg-red-50 px-3 py-1 rounded text-sm font-semibold border border-red-200 transition">🚪 Keluar</button>
            </form>
        </div>

        <form action="{{ route('search.group.store') }}" method="POST" class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end">
            @csrf
            <div class="md:col-span-5">
                <label class="block text-sm font-bold text-gray-700 mb-1">Nama Grup / Channel</label>
                <input type="text" name="nama" class="w-full px-3 py-2 border rounded bg-gray-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-400" placeholder="Contoh: Forum Salafy" required>
            </div>
            <div class="md:col-span-5">
                <label class="block text-sm font-bold text-gray-700 mb-1">URL atau @username</label>
                <input type="text" name="link" class="w-full px-3 py-2 border rounded bg-gray-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-400" placeholder="Contoh: https://t.me/forumsalafy" required>
            </div>
            <div class="md:col-span-2">
                <button type="submit" class="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded transition">Simpan</button>
            </div>
        </form>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b bg-gray-50">
            <h2 class="text-lg font-bold text-gray-800">📋 Daftar Database URL Terdaftar</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-100 text-gray-600 text-sm uppercase tracking-wider">
                        <th class="px-6 py-3 border-b font-semibold">ID</th>
                        <th class="px-6 py-3 border-b font-semibold">Nama Grup</th>
                        <th class="px-6 py-3 border-b font-semibold">URL Akses</th>
                        <th class="px-6 py-3 border-b font-semibold text-center">Aksi (CRUD)</th>
                    </tr>
                </thead>
                <tbody class="text-gray-700">
                    @forelse($groups as $grup)
                    <tr class="hover:bg-gray-50 transition border-b">
                        <td class="px-6 py-4">{{ $grup->id }}</td>
                        <td class="px-6 py-4 font-medium">{{ $grup->nama }}</td>
                        <td class="px-6 py-4 text-blue-600 hover:underline"><a href="{{ $grup->link }}" target="_blank">{{ $grup->link }}</a></td>
                        <td class="px-6 py-4 text-center">
                            <form action="{{ route('search.group.destroy', $grup->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus sumber ini secara permanen?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="inline-flex items-center justify-center bg-red-100 hover:bg-red-200 text-red-700 px-3 py-1 rounded text-sm font-semibold transition">
                                    🗑️ Hapus
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="px-6 py-8 text-center text-gray-500 italic">Belum ada URL grup Telegram yang tersimpan di database.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

@endif
@endsection
