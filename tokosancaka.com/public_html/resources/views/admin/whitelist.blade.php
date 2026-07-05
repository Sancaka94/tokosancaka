@extends('layouts.admin') @section('content')
<div class="p-6 sm:p-10 space-y-6">
    <div class="flex flex-col space-y-2">
        <h1 class="text-2xl font-bold text-gray-900">Manajemen Whitelist & Akun Dummy Sancaka</h1>
        <p class="text-sm text-gray-500">Buat akun dummy atau kelola akses whitelist untuk bypass OTP dan Captcha.</p>
    </div>

    @if(session('success'))
    <div class="p-4 mb-4 text-sm text-green-800 rounded-lg bg-green-50 border border-green-200" role="alert">
        <span class="font-medium">Berhasil!</span> {{ session('success') }}
    </div>
    @endif

    @if(session('error'))
    <div class="p-4 mb-4 text-sm text-red-800 rounded-lg bg-red-50 border border-red-200" role="alert">
        <span class="font-medium">Gagal!</span> {{ session('error') }}
    </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

        <div class="col-span-1 bg-white border border-gray-200 rounded-xl shadow-sm">
            <div class="border-b border-gray-200 px-6 py-4">
                <h2 class="text-lg font-semibold text-gray-800">Buat Akun Dummy</h2>
            </div>
            <div class="p-6">
                <form action="{{ route('admin.dummy.store') }}" method="POST" class="space-y-4">
                    @csrf

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nama Lengkap</label>
                        <input type="text" name="nama_lengkap" required placeholder="Contoh: Dummy Admin"
                            class="w-full rounded-lg border border-gray-300 px-4 py-2 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none transition">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Email atau No. WA</label>
                        <input type="text" name="login_value" required placeholder="dummy@sancaka.store / 0812..."
                            class="w-full rounded-lg border border-gray-300 px-4 py-2 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none transition">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                        <input type="password" name="password" required placeholder="••••••••"
                            class="w-full rounded-lg border border-gray-300 px-4 py-2 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none transition">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Role</label>
                        <select name="role" required
                            class="w-full rounded-lg border border-gray-300 px-4 py-2 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none transition bg-white">
                            <option value="pelanggan">Pelanggan</option>
                            <option value="admin">Admin</option>
                            <option value="agent">Agent</option>
                        </select>
                    </div>

                    <input type="hidden" name="is_whitelisted" value="1">

                    <button type="submit"
                        class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2.5 px-4 rounded-lg transition duration-200 text-sm shadow-sm">
                        Simpan & Whitelist
                    </button>
                </form>
            </div>
        </div>

        <div class="col-span-1 lg:col-span-2 bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
            <div class="border-b border-gray-200 px-6 py-4 flex justify-between items-center bg-gray-50">
                <h2 class="text-lg font-semibold text-gray-800">Daftar Pengguna Ter-Whitelist</h2>
                <span class="bg-blue-100 text-blue-800 text-xs font-semibold px-2.5 py-0.5 rounded">Otomatis Bypass</span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left text-gray-500">
                    <thead class="text-xs text-gray-700 uppercase bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th scope="col" class="px-6 py-3">Informasi Pengguna</th>
                            <th scope="col" class="px-6 py-3">Peran (Role)</th>
                            <th scope="col" class="px-6 py-3 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($whitelistedUsers as $user)
                        <tr class="hover:bg-gray-50 transition">
                            <td class="px-6 py-4">
                                <div class="font-medium text-gray-900">{{ $user->nama_lengkap }}</div>
                                <div class="text-xs text-gray-500">{{ $user->email ?? $user->no_wa }}</div>
                            </td>
                            <td class="px-6 py-4">
                                @if(strtolower($user->role) == 'admin')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                                        Admin
                                    </span>
                                @elseif(strtolower($user->role) == 'agent')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800">
                                        Agent
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        Pelanggan
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-center">
                                <form action="{{ route('admin.whitelist.toggle', $user->id_pengguna) }}" method="POST" onsubmit="return confirm('Yakin ingin menghapus akses whitelist untuk pengguna ini?');">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="text-red-600 hover:text-red-900 bg-red-50 hover:bg-red-100 px-3 py-1 rounded-md transition text-xs font-medium">
                                        Cabut Whitelist
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="3" class="px-6 py-8 text-center">
                                <div class="flex flex-col items-center justify-center text-gray-400">
                                    <svg class="w-12 h-12 mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                                    <p class="text-sm font-medium text-gray-500">Belum ada akun yang masuk daftar whitelist.</p>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>
@endsection
