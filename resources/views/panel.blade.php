<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Developer Access Panel</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <div class="container mx-auto p-8">
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-8 rounded-lg" role="alert">
            <p class="font-bold">PERINGATAN!</p>
            <p>Halaman ini adalah halaman khusus Developer. Jangan pernah bagikan URL halaman ini kepada siapa pun.</p>
        </div>

        <h1 class="text-3xl font-bold mb-6 text-gray-800">Developer Quick Login Panel</h1>

        <div class="bg-white shadow-md rounded-lg overflow-hidden">
            <table class="min-w-full leading-normal">
                <thead>
                    <tr>
                        <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-50 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                            ID
                        </th>
                        <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-50 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                            Nama Lengkap
                        </th>
                        <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-50 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                            Email
                        </th>
                        <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-50 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                            Role
                        </th>
                        <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-50 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">
                            Aksi
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($users as $user)
                        <tr>
                            <td class="px-5 py-4 border-b border-gray-200 bg-white text-sm">
                                <p class="text-gray-900 whitespace-no-wrap">{{ $user->id }}</p>
                            </td>
                            <td class="px-5 py-4 border-b border-gray-200 bg-white text-sm">
                                <p class="text-gray-900 whitespace-no-wrap">{{ $user->nama_lengkap }}</p>
                            </td>
                            <td class="px-5 py-4 border-b border-gray-200 bg-white text-sm">
                                <p class="text-gray-900 whitespace-no-wrap">{{ $user->email }}</p>
                            </td>
                            <td class="px-5 py-4 border-b border-gray-200 bg-white text-sm">
                                <span class="relative inline-block px-3 py-1 font-semibold leading-tight {{ $user->role === 'Admin' ? 'text-red-900' : 'text-green-900' }}">
                                    <span aria-hidden class="absolute inset-0 {{ $user->role === 'Admin' ? 'bg-red-200' : 'bg-green-200' }} opacity-50 rounded-full"></span>
                                    <span class="relative">{{ $user->role }}</span>
                                </span>
                            </td>
                            <td class="px-5 py-4 border-b border-gray-200 bg-white text-sm text-center">
                               @isset($user->id)
    <a href="{{ route('developer.login', ['userId' => $user->id]) }}" ...>
        Login Sebagai User Ini
    </a>
@endisset
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-4">Tidak ada pengguna ditemukan.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>