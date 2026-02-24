<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Parkir - @yield('title', 'Dashboard')</title>

    <link rel="icon" type="image/jpeg" href="https://tokosancaka.com/storage/uploads/logo.jpeg">
    <link rel="apple-touch-icon" href="https://tokosancaka.com/storage/uploads/logo.jpeg">

    <script src="https://cdn.tailwindcss.com"></script>

    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <style type="text/tailwindcss">
        @layer utilities {
            .card { @apply bg-white rounded-lg shadow-sm border border-gray-200 mb-4; }
            .card-header { @apply px-4 py-3 border-b border-gray-200 bg-gray-50 text-gray-800 font-bold rounded-t-lg flex justify-between items-center; }
            .card-body { @apply p-4; }
            .btn-primary { @apply bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded transition-colors font-medium inline-block text-center cursor-pointer; }
            .btn-danger { @apply bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded transition-colors font-medium inline-block text-center cursor-pointer; }
            .form-control { @apply w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:border-blue-600 focus:ring-1 focus:ring-blue-600; }
            .table-custom { @apply w-full text-left border-collapse; }
            .table-custom th { @apply border-b-2 border-gray-200 bg-gray-50 px-4 py-2 font-semibold text-gray-700 whitespace-nowrap; }
            .table-custom td { @apply border-b border-gray-200 px-4 py-2 text-gray-800 whitespace-nowrap; }
        }
    </style>
</head>
<body class="bg-gray-100 flex h-screen overflow-hidden font-sans text-gray-900">

    @include('partials.sidebar')

    <div class="flex-1 flex flex-col overflow-hidden">

        @include('partials.header')

        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-100 p-6">

            @if(session('success'))
                <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded shadow-sm" role="alert">
                    <span class="font-bold">Berhasil!</span> {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded shadow-sm" role="alert">
                    <span class="font-bold">Gagal!</span> {{ session('error') }}
                </div>
            @endif

            @yield('content')

        </main>
    </div>

</body>
</html>
