@extends('layouts.admin')

@section('title', 'Data Escrow / Penahanan Dana')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="mb-6 flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-semibold text-gray-800">Data Escrow (Penahanan Dana)</h2>
            <p class="text-sm text-gray-500 mt-1">Kelola pencairan dana ke penjual atau mediasi komplain pembeli.</p>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Invoice & Status
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Info Penjual
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Info Pembeli
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Detail Produk & Ongkir
                        </th>
                        <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Aksi
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    
                    {{-- @foreach($orders as $order) --}}
                    <tr class="hover:bg-gray-50">
                        
                        <td class="px-6 py-4 whitespace-nowrap align-top">
                            <div class="text-sm font-bold text-blue-600">SCK-ORD-12345678</div> <div class="text-xs text-gray-500 mt-1">Total: <span class="font-semibold text-gray-800">Rp 150.000</span></div> <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-yellow-100 text-yellow-800 mt-2">
                                Dana Tertahan
                            </span>
                        </td>

                        <td class="px-6 py-4 align-top max-w-xs">
                            <div class="text-sm font-semibold text-gray-800">Toko Sancaka Jaya</div> <div class="text-xs text-gray-500 flex items-center mt-1">
                                <i class="fab fa-whatsapp text-green-500 mr-1"></i> 081234567890 </div>
                            <div class="text-xs text-gray-500 mt-2 whitespace-normal">
                                <span class="font-semibold">Alamat:</span><br>
                                Jl. Raya Ngawi - Solo Km 5, Desa Watualang, Ngawi, Jawa Timur. </div>
                        </td>

                        <td class="px-6 py-4 align-top max-w-xs">
                            <div class="text-sm font-semibold text-gray-800">Budi Santoso</div> <div class="text-xs text-gray-500 flex items-center mt-1">
                                <i class="fab fa-whatsapp text-green-500 mr-1"></i> 089876543210 </div>
                            <div class="text-xs text-gray-500 mt-2 whitespace-normal">
                                <span class="font-semibold">Dikirim ke:</span><br>
                                Perumahan Indah Asri Blok C2, Madiun, Jawa Timur. </div>
                        </td>

                        <td class="px-6 py-4 align-top max-w-sm whitespace-normal">
                            <ul class="text-xs text-gray-600 space-y-1 mb-2">
                                {{-- @foreach($order->items as $item) --}}
                                <li>
                                    <span class="font-semibold text-gray-800">Baju Koko Pria (XL)</span> <br> 2 pcs x Rp 50.000 </li>
                                {{-- @endforeach --}}
                            </ul>
                            
                            <div class="bg-gray-50 p-2 rounded border border-gray-100 mt-2">
                                <div class="text-xs text-gray-500">
                                    <span class="font-semibold">Berat:</span> 1.500 gram </div>
                                <div class="text-xs text-gray-500">
                                    <span class="font-semibold">Volumetrik (PxLxT):</span> 20x15x10 cm </div>
                                <div class="text-xs text-gray-800 mt-1 border-t border-gray-200 pt-1">
                                    <span class="font-semibold">Ongkos Kirim:</span> Rp 25.000 </div>
                            </div>
                        </td>

                        <td class="px-6 py-4 whitespace-nowrap text-center align-top">
                            <div class="flex flex-col space-y-2">
                                <form action="#" method="POST" onsubmit="return confirm('Yakin ingin mencairkan dana ini ke penjual? Pastikan barang sudah diterima pembeli.');">
                                    @csrf
                                    <button type="submit" class="w-full inline-flex justify-center items-center px-3 py-2 border border-transparent text-xs leading-4 font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 shadow-sm transition-colors">
                                        <i class="fas fa-money-bill-wave mr-1.5"></i> Cairkan Dana
                                    </button>
                                </form>

                                <a href="#" class="w-full inline-flex justify-center items-center px-3 py-2 border border-transparent text-xs leading-4 font-medium rounded-md text-white bg-orange-500 hover:bg-orange-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-orange-500 shadow-sm transition-colors">
                                    <i class="fas fa-balance-scale mr-1.5"></i> Mediasi / Wasit
                                </a>
                            </div>
                        </td>
                    </tr>
                    {{-- @endforeach --}}

                </tbody>
            </table>
        </div>
        
        <div class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
            {{-- {{ $orders->links() }} --}}
        </div>
    </div>
</div>
@endsection