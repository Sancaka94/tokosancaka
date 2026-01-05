<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Member Area') - Sancaka</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        /* Global Styles untuk Mobile */
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
        body { -webkit-tap-highlight-color: transparent; }
    </style>
</head>
<body class="bg-slate-50 text-slate-800 font-sans min-h-screen flex flex-col">

    <nav class="bg-white px-5 py-4 shadow-sm sticky top-0 z-50 flex justify-between items-center border-b border-slate-100">
        <div class="flex items-center gap-3">
            <div class="h-9 w-9 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 font-bold text-sm border border-blue-200">
                {{ substr(Auth::guard('member')->user()->name, 0, 1) }}
            </div>
            
            <div>
                <h1 class="font-bold text-sm leading-tight text-slate-800 truncate w-32">
                    {{ Auth::guard('member')->user()->name }}
                </h1>
                <p class="text-[10px] text-slate-400">
                    {{ Auth::guard('member')->user()->whatsapp }}
                </p>
            </div>
        </div>

        <form action="{{ route('member.logout') }}" method="POST">
            @csrf
            <button type="submit" class="text-xs font-bold text-red-500 hover:bg-red-50 px-3 py-1.5 rounded-lg transition border border-transparent hover:border-red-100">
                <i class="fas fa-sign-out-alt mr-1"></i> Keluar
            </button>
        </form>
    </nav>

    <main class="flex-1 w-full max-w-md mx-auto p-5 pb-24">
        @yield('content')
    </main>

    <div class="text-center py-4 text-[10px] text-slate-300">
        &copy; {{ date('Y') }} Sancaka POS Member
    </div>

</body>
</html>