<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Surat Jalan Kurir - {{ $courier->full_name }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @media print {
            body { -webkit-print-color-adjust: exact; }
            .no-print { display: none; }
        }
    </style>
</head>
<body class="bg-white">
    <div class="container mx-auto p-8 max-w-4xl">
        <div class="no-print mb-6 flex justify-between">
            <a href="{{ route('admin.couriers.show', $courier->id) }}" class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-2 px-4 rounded-lg">&larr; Kembali</a>
            <button onclick="window.print()" class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded-lg">Cetak</button>
        </div>

        <header class="text-center border-b-2 border-black pb-4">
            <h1 class="text-4xl font-bold">SURAT JALAN KURIR</h1>
            <p class="text-lg">Sancaka Express</p>
        </header>

        <main class="mt-8">
            <div class="grid grid-cols-2 gap-8">
                <div>
                    <h3 class="font-bold text-lg">Kurir:</h3>
                    <p>{{ $courier->full_name }} ({{ $courier->courier_id }})</p>
                    <p>{{ $courier->phone_number }}</p>
                </div>
                <div class="text-right">
                    <h3 class="font-bold text-lg">Tanggal Cetak:</h3>
                    <p>{{ \Carbon\Carbon::now()->format('d F Y') }}</p>
                </div>
            </div>

            <h3 class="font-bold text-xl mt-8 mb-4 text-center">DAFTAR PAKET DIAMBIL</h3>
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-200">
                        <th class="border p-2 w-12">No</th>
                        <th class="border p-2">Nomor Resi / Surat Jalan</th>
                        <th class="border p-2">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($packages as $index => $package)
                        <tr>
                            <td class="border p-2 text-center">{{ $index + 1 }}</td>
                            <td class="border p-2 font-mono">{{ $package->shipping_code }}</td>
                            <td class="border p-2">{{ $package->status }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="border p-2 text-center">Tidak ada paket yang diambil hari ini.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <div class="grid grid-cols-2 gap-16 mt-16">
                <div class="text-center">
                    <p class="mb-20">Diserahkan oleh Admin,</p>
                    <p>( .......................... )</p>
                </div>
                <div class="text-center">
                    <p class="mb-20">Diterima oleh Kurir,</p>
                    <p class="font-bold">( {{ $courier->full_name }} )</p>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
