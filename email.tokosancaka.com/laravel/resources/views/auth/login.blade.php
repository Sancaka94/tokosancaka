<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Sancaka Mail') }}</title>

        <!-- Inter Font -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

        <!-- Tailwind CSS CDN -->
        <script src="https://cdn.tailwindcss.com"></script>
        <script>
            tailwind.config = {
                theme: {
                    extend: {
                        fontFamily: {
                            sans: ['Inter', '-apple-system', 'BlinkMacSystemFont', 'sans-serif'],
                        }
                    }
                }
            }
        </script>
    </head>
    <body class="font-sans text-slate-800 antialiased bg-[#f6f8fc]">
        <div class="min-h-screen flex flex-col justify-center items-center px-4 py-8">
            <!-- Card Container -->
            <div class="w-full max-w-[420px] bg-white rounded-2xl border border-slate-200/80 shadow-sm px-8 py-9">
                <!-- Logo Sancaka -->
                <div class="flex flex-col items-center text-center mb-7">
                    <a href="/" class="inline-flex flex-col items-center focus:outline-none">
                        <img
                            src="https://tokosancaka.com/storage/uploads/sancaka.png"
                            alt="Logo Sancaka"
                            class="h-12 w-auto object-contain mb-3"
                        >
                        <span class="text-lg font-semibold text-slate-900 tracking-tight">Sancaka Mail</span>
                        <span class="text-xs text-slate-500 mt-0.5">Masuk ke ruang kerja email Anda</span>
                    </a>
                </div>

                {{ $slot }}
            </div>

            <!-- Footer -->
            <div class="mt-6 text-center text-[11px] text-slate-400">
                &copy; {{ date('Y') }} CV Sancaka Karya Hutama &bull; email.tokosancaka.com
            </div>
        </div>
    </body>
</html>
