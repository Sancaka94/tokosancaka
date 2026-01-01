<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Toko Sancaka</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">

    <div class="max-w-4xl mx-auto py-10 px-4">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-gray-800">Manajemen Produk POS</h1>
            <a href="{{ route('orders.create') }}" class="bg-indigo-600 text-white px-4 py-2 rounded-lg font-bold hover:bg-indigo-700">Buka POS →</a>
        </div>

        @if(session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                {{ session('success') }}
            </div>
        @endif

        <div class="bg-white p-6 rounded-2xl shadow-sm mb-8">
            <h2 class="text-lg font-bold mb-4">Tambah Produk Baru</h2>
            <form action="{{ route('products.store') }}" method="POST" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-gray-700">Nama Produk</label>
                    <input type="text" name="name" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 p-2 border">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Harga (Rp)</label>
                    <input type="number" name="base_price" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 p-2 border">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Satuan (Unit)</label>
                    <select name="unit" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 p-2 border">
                        <option value="meter">Meter</option>
                        <option value="pcs">Pcs</option>
                        <option value="lembar">Lembar</option>
                        <option value="box">Box</option>
                    </select>
                </div>
                <div class="md:col-span-3">
                    <button type="submit" class="w-full bg-gray-800 text-white py-3 rounded-xl font-bold hover:bg-black transition-all">
                        Posting Produk ke POS
                    </button>
                </div>
            </form>
        </div>

        <div class="bg-white rounded-2xl shadow-sm overflow-hidden">
            <table class="w-full text-left">
                <thead class="bg-gray-50 border-b">
                    <tr>
                        <th class="px-6 py-3 text-sm font-bold text-gray-600">Nama Produk</th>
                        <th class="px-6 py-3 text-sm font-bold text-gray-600">Harga</th>
                        <th class="px-6 py-3 text-sm font-bold text-gray-600">Unit</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($products as $product)
                    <tr>
                        <td class="px-6 py-4 text-sm text-gray-800 font-medium">{{ $product->name }}</td>
                        <td class="px-6 py-4 text-sm text-gray-600">Rp {{ number_format($product->base_price, 0, ',', '.') }}</td>
                        <td class="px-6 py-4 text-sm text-gray-500 uppercase">{{ $product->unit }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

</body>
</html>