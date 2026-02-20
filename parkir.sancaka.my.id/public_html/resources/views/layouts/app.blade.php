<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Parkir - @yield('title')</title>

    <script src="https://cdn.tailwindcss.com"></script>

    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <style>
        /* Custom Utilities Menyerupai Bootstrap 5 */
        .card { @apply bg-white rounded-lg shadow-sm border border-gray-200 mb-4; }
        .card-header { @apply px-4 py-3 border-b border-gray-200 bg-gray-50 text-gray-800 font-bold rounded-t-lg; }
        .card-body { @apply p-4; }
        .btn-primary { @apply bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded transition-colors font-medium; }
        .btn-danger { @apply bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded transition-colors font-medium; }
        .form-control { @apply w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:border-blue-600 focus:ring-1 focus:ring-blue-600; }
        .table-custom { @apply w-full text-left border-collapse; }
        .table-custom th { @apply border-b-2 border-gray-200 bg-gray-50 px-4 py-2 font-semibold text-gray-700; }
        .table-custom td { @apply border-b border-gray-200 px-4 py-2 text-gray-600; }
    </style>
</head>
<body class="bg-gray-100 flex h-screen overflow-hidden font-sans">

    @include('partials.sidebar')

    <div class="flex-1 flex flex-col overflow-hidden">

        @include('partials.header')

        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-100 p-6">

            @if(session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                    <span class="block sm:inline">{{ session('success') }}</span>
                </div>
            @endif

            @if(session('error'))
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                    <span class="block sm:inline">{{ session('error') }}</span>
                </div>
            @endif

            @yield('content')

        </main>
    </div>

</body>
</html>
