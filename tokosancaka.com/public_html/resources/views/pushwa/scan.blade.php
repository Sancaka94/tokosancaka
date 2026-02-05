<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scan WhatsApp</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 flex items-center justify-center h-screen">

    <div class="bg-white p-8 rounded-lg shadow-lg text-center max-w-sm">
        <h2 class="text-2xl font-bold mb-4 text-green-600">Scan QR Code</h2>
        <p class="mb-6 text-gray-600">Silakan buka WhatsApp di HP Anda, menu Perangkat Tertaut, lalu scan kode ini.</p>

        <div class="border-4 border-gray-200 p-2 inline-block rounded-lg">
            <img src="{{ $qrImage }}" alt="Scan Me" class="w-64 h-64">
        </div>

        <div class="mt-6">
            <a href="{{ url()->current() }}" class="text-sm text-blue-500 hover:underline">Refresh QR Code</a>
        </div>
    </div>

</body>
</html>
