@extends('layouts.customer')

@section('title', 'Checkout Pesanan')

@section('content')
<div class="container mx-auto py-10 px-4">
    <h1 class="text-3xl font-bold text-gray-800 mb-6">Checkout</h1>

    <div class="flex flex-col lg:flex-row gap-8">

        <!-- Form Alamat Pengiriman -->
        <div class="w-full lg:w-2/3">
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-bold text-gray-800 border-b pb-4 mb-4">Alamat Pengiriman</h2>
                <form>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="nama" class="block text-sm font-medium text-gray-700">Nama Lengkap</label>
                            <input type="text" id="nama" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>
                        </div>
                        <div>
                            <label for="telepon" class="block text-sm font-medium text-gray-700">Nomor Telepon</label>
                            <input type="text" id="telepon" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>
                        </div>
                    </div>
                    <div class="mt-4">
                        <label for="alamat" class="block text-sm font-medium text-gray-700">Alamat Lengkap</label>
                        <textarea id="alamat" rows="3" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required></textarea>
                    </div>
                </form>
            </div>
        </div>

        <!-- Ringkasan Pesanan -->
        <div class="w-full lg:w-1/3">
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-bold text-gray-800 border-b pb-4 mb-4">Ringkasan Pesanan</h2>
                @php $total = 0 @endphp
                @foreach($cart as $id => $details)
                    @php $total += $details['price'] * $details['quantity'] @endphp
                    <div class="flex justify-between items-center mb-2 text-sm">
                        <span class="text-gray-600">{{ $details['name'] }} <span class="text-gray-500">x{{ $details['quantity'] }}</span></span>
                        <span class="font-semibold text-gray-800">Rp{{ number_format($details['price'] * $details['quantity']) }}</span>
                    </div>
                @endforeach
                <div class="border-t mt-4 pt-4">
                    <div class="flex justify-between mb-2">
                        <span class="text-gray-600">Subtotal</span>
                        <span class="font-semibold text-gray-800">Rp{{ number_format($total) }}</span>
                    </div>
                    <div class="flex justify-between font-bold text-lg">
                        <span>Total</span>
                        <span>Rp{{ number_format($total) }}</span>
                    </div>
                </div>
                <button class="w-full mt-6 bg-red-600 text-white font-bold py-3 rounded-lg hover:bg-red-700 transition-colors">
                    Buat Pesanan
                </button>
            </div>
        </div>

    </div>
</div>
@endsection
