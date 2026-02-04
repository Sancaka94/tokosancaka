<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Member Area') - Sancaka</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    
    <style>
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
        body { background-color: #f8fafc; }
    </style>
</head>
<body class="font-sans text-slate-800 antialiased">

    <div class="flex h-screen overflow-hidden bg-slate-50" x-data="{ sidebarOpen: false }">

        @include('layouts.member.sidebar')

        <div class="relative flex flex-col flex-1 overflow-y-auto overflow-x-hidden">

            @include('layouts.member.header')

            <main class="w-full flex-grow p-6">
                <div class="w-full max-w-7xl mx-auto">
                    @yield('content')
                </div>
            </main>

            <footer class="w-full text-center py-4 text-[10px] text-slate-400">
                &copy; {{ date('Y') }} Sancaka System. All rights reserved.
            </footer>
        </div>

    </div>

</body>
</html>