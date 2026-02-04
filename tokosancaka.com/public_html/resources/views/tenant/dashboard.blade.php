<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Owner - {{ $tenant->name }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-slate-50 text-slate-800">

    <nav class="bg-white shadow-sm border-b border-slate-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center gap-2">
                    <i class="fas fa-store text-blue-600 text-xl"></i>
                    <span class="font-bold text-xl tracking-tight text-slate-800">{{ $tenant->name }}</span>
                </div>
                <div class="flex items-center gap-4">
                    <span class="text-sm text-slate-500">Halo, {{ $user->name }}</span>
                    <a href="{{ route('tenant.settings') }}" class="text-slate-500 hover:text-blue-600"><i class="fas fa-cog"></i></a>
                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button type="submit" class="text-red-500 hover:text-red-700 text-sm font-medium">Logout</button>
                    </form>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto py-10 px-4 sm:px-6 lg:px-8">

        <div class="mb-8">
            <h1 class="text-3xl font-bold text-slate-900">Dashboard Overview</h1>
            <p class="mt-1 text-slate-500">Pantau status berlangganan dan performa toko Anda.</p>
        </div>

        @if(session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-6">
                {{ session('success') }}
            </div>
        @endif

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">

            <div class="bg-white overflow-hidden shadow rounded-2xl border border-slate-100">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 bg-blue-100 rounded-md p-3">
                            <i class="fas fa-box text-blue-600 text-xl"></i>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-slate-500 truncate">Paket Saat Ini</dt>
                                <dd>
                                    <div class="text-lg font-bold text-slate-900 uppercase">{{ $tenant->package }}</div>
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
                <div class="bg-slate-50 px-5 py-3">
                    <div class="text-sm">
                        <span class="font-medium text-slate-500">Status: </span>
                        @if($tenant->status == 'active')
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                ACTIVE
                            </span>
                        @else
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                INACTIVE / UNPAID
                            </span>
                        @endif
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow rounded-2xl border border-slate-100">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 {{ $daysLeft < 7 ? 'bg-red-100' : 'bg-purple-100' }} rounded-md p-3">
                            <i class="fas fa-calendar-alt {{ $daysLeft < 7 ? 'text-red-600' : 'text-purple-600' }} text-xl"></i>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-slate-500 truncate">Masa Aktif Berakhir</dt>
                                <dd>
                                    <div class="text-lg font-bold text-slate-900">
                                        {{ \Carbon\Carbon::parse($tenant->expired_at)->format('d M Y') }}
                                    </div>
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
                <div class="bg-slate-50 px-5 py-3">
                    <div class="text-sm font-medium {{ $daysLeft < 7 ? 'text-red-600' : 'text-purple-600' }}">
                        {{ $daysLeft > 0 ? "Sisa $daysLeft Hari Lagi" : "Sudah Kadaluarsa!" }}
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow rounded-2xl border border-slate-100">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 bg-emerald-100 rounded-md p-3">
                            <i class="fas fa-globe text-emerald-600 text-xl"></i>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-slate-500 truncate">Link Toko Anda</dt>
                                <dd>
                                    <div class="text-lg font-bold text-slate-900 truncate">
                                        {{ $tenant->subdomain }}.tokosancaka.com
                                    </div>
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
                <div class="bg-slate-50 px-5 py-3">
                    <a href="http://{{ $tenant->subdomain }}.tokosancaka.com" target="_blank" class="text-sm font-medium text-blue-600 hover:text-blue-500">
                        Kunjungi Website &rarr;
                    </a>
                </div>
            </div>
        </div>

        <div class="bg-white shadow overflow-hidden sm:rounded-lg border border-slate-200">
            <div class="px-4 py-5 sm:px-6 flex justify-between items-center">
                <div>
                    <h3 class="text-lg leading-6 font-medium text-slate-900">Informasi Pemilik & Bisnis</h3>
                    <p class="mt-1 max-w-2xl text-sm text-slate-500">Detail data yang terdaftar di sistem.</p>
                </div>
                <a href="{{ route('tenant.settings') }}" class="bg-white py-2 px-4 border border-slate-300 rounded-md shadow-sm text-sm font-medium text-slate-700 hover:bg-slate-50">
                    Edit Data
                </a>
            </div>
            <div class="border-t border-slate-200">
                <dl>
                    <div class="bg-slate-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-slate-500">Nama Pemilik</dt>
                        <dd class="mt-1 text-sm text-slate-900 sm:mt-0 sm:col-span-2">{{ $user->name }}</dd>
                    </div>
                    <div class="bg-white px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-slate-500">Email Login</dt>
                        <dd class="mt-1 text-sm text-slate-900 sm:mt-0 sm:col-span-2">{{ $user->email }}</dd>
                    </div>
                    <div class="bg-slate-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-slate-500">WhatsApp</dt>
                        <dd class="mt-1 text-sm text-slate-900 sm:mt-0 sm:col-span-2">{{ $tenant->whatsapp }}</dd>
                    </div>
                    <div class="bg-white px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-slate-500">Tanggal Mendaftar</dt>
                        <dd class="mt-1 text-sm text-slate-900 sm:mt-0 sm:col-span-2">
                            {{ \Carbon\Carbon::parse($tenant->created_at)->format('d F Y H:i') }}
                        </dd>
                    </div>
                </dl>
            </div>
        </div>

    </div>
</body>
</html>
