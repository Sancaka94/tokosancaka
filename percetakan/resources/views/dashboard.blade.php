<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Monitoring - Sancaka POS</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <link href="https://fonts.googleapis.com/css2?family=Source+Sans+3:ital,wght@0,300;0,400;0,600;0,700;1,400&display=swap" rel="stylesheet">

    <style>
        [x-cloak] { display: none !important; }
        body { font-family: 'Source Sans 3', sans-serif; }
        
        /* Custom Scrollbar for a cleaner look */
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: #f1f1f1; }
        ::-webkit-scrollbar-thumb { background: #c1c1c1; border-radius: 3px; }
        ::-webkit-scrollbar-thumb:hover { background: #a8a8a8; }
    </style>
</head>
<body class="bg-gray-100 text-gray-800 antialiased" x-data="{ sidebarOpen: true, darkMode: false }">

    <div class="flex h-screen overflow-hidden">
        
        <aside class="flex-shrink-0 w-64 bg-slate-800 text-white transition-all duration-300 ease-in-out flex flex-col"
               :class="sidebarOpen ? 'translate-x-0' : '-translate-x-64 absolute h-full z-50 lg:relative lg:translate-x-0 lg:w-64'">
               
            <div class="h-14 flex items-center justify-center border-b border-slate-700 bg-slate-900 px-4">
                <span class="text-xl font-bold tracking-wider">SANCAKA<span class="font-light">POS</span></span>
            </div>

            <nav class="flex-1 overflow-y-auto py-4 px-2 space-y-1">
                @include('layouts.partials.sidebar') 
                </nav>
        </aside>

        <div class="flex-1 flex flex-col min-w-0 overflow-hidden">
            
            <header class="bg-white border-b border-gray-200 h-16 flex items-center justify-between px-4 lg:px-6">
                    <div class="flex items-center gap-4">@include('layouts.partials.header')</div>
            </header>

            <main class="flex-1 overflow-y-auto p-4 lg:p-6">
                
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
                    <div>
                        <h2 class="text-2xl font-bold text-gray-800 m-0">Ringkasan Operasional</h2>
                        <p class="text-sm text-gray-500 mt-1">Data transaksi per hari ini, {{ $hariIni }}</p>
                    </div>
                    <a href="{{ route('orders.create') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded shadow-sm transition-colors">
                        <i class="fas fa-plus"></i>
                        <span>Transaksi Baru</span>
                    </a>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                    
                    <div class="bg-white rounded shadow-sm flex overflow-hidden group">
                        <div class="w-[70px] bg-blue-500 flex items-center justify-center text-white text-3xl group-hover:bg-blue-600 transition-colors">
                            <i class="fas fa-money-bill-wave"></i>
                        </div>
                        <div class="flex-1 p-3">
                            <span class="block text-sm font-medium text-gray-500 uppercase truncate">Total Omzet (Paid)</span>
                            <span class="block text-lg font-bold text-gray-800">Rp {{ number_format($totalOmzet, 0, ',', '.') }}</span>
                            
                            <div class="mt-1 h-1 w-full bg-gray-100 rounded-full overflow-hidden">
                                <div class="h-full bg-blue-500 w-full"></div>
                            </div>
                            <span class="text-xs text-blue-600 font-medium mt-1 inline-block">Real-time Data</span>
                        </div>
                    </div>

                    <div class="bg-white rounded shadow-sm flex overflow-hidden group">
                        <div class="w-[70px] bg-emerald-500 flex items-center justify-center text-white text-3xl group-hover:bg-emerald-600 transition-colors">
                            <i class="fas fa-boxes"></i>
                        </div>
                        <div class="flex-1 p-3">
                            <span class="block text-sm font-medium text-gray-500 uppercase truncate">Item Terjual</span>
                            <span class="block text-lg font-bold text-gray-800">{{ number_format($totalTerjual) }} <small class="text-xs font-normal text-gray-400">Unit</small></span>
                            
                            <div class="mt-1 h-1 w-full bg-gray-100 rounded-full overflow-hidden">
                                <div class="h-full bg-emerald-500 w-[70%]"></div>
                            </div>
                            <span class="text-xs text-gray-400 font-medium mt-1 inline-block">Produk POS</span>
                        </div>
                    </div>

                    <div class="bg-white rounded shadow-sm flex overflow-hidden group">
                        <div class="w-[70px] bg-amber-400 flex items-center justify-center text-white text-3xl group-hover:bg-amber-500 transition-colors">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="flex-1 p-3">
                            <span class="block text-sm font-medium text-gray-500 uppercase truncate">Pelanggan Unik</span>
                            <span class="block text-lg font-bold text-gray-800">{{ number_format($totalPelanggan) }} <small class="text-xs font-normal text-gray-400">Orang</small></span>
                            
                            <div class="mt-1 h-1 w-full bg-gray-100 rounded-full overflow-hidden">
                                <div class="h-full bg-amber-400 w-[45%]"></div>
                            </div>
                            <span class="text-xs text-amber-600 font-medium mt-1 inline-block">Loyalitas Tinggi</span>
                        </div>
                    </div>

                    <div class="bg-white rounded shadow-sm flex overflow-hidden group">
                        <div class="w-[70px] bg-red-500 flex items-center justify-center text-white text-3xl group-hover:bg-red-600 transition-colors">
                            <i class="fas fa-user-shield"></i>
                        </div>
                        <div class="flex-1 p-3">
                            <span class="block text-sm font-medium text-gray-500 uppercase truncate">User / Staff</span>
                            <span class="block text-lg font-bold text-gray-800">{{ number_format($totalUser) }} <small class="text-xs font-normal text-gray-400">Aktif</small></span>
                            
                            <div class="mt-1 h-1 w-full bg-gray-100 rounded-full overflow-hidden">
                                <div class="h-full bg-red-500 w-full"></div>
                            </div>
                            <span class="text-xs text-red-500 font-medium mt-1 inline-block">Otoritas Admin</span>
                        </div>
                    </div>

                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    
                    <div class="lg:col-span-2">
                        <div class="bg-white border-t-4 border-blue-500 rounded shadow-sm mb-6">
                            <div class="px-4 py-3 border-b border-gray-100 flex justify-between items-center">
                                <h3 class="text-base font-semibold text-gray-800">
                                    <i class="fas fa-shopping-cart text-gray-400 mr-2"></i>Aktivitas Transaksi Terbaru
                                </h3>
                                <span class="text-xs font-medium px-2 py-1 bg-gray-100 text-gray-600 rounded">5 Data Terakhir</span>
                            </div>
                            
                            <div class="overflow-x-auto">
                                <table class="w-full text-sm text-left">
                                    <thead class="bg-gray-50 text-gray-600 font-semibold border-b border-gray-200">
                                        <tr>
                                            <th class="px-4 py-3 whitespace-nowrap">Order ID</th>
                                            <th class="px-4 py-3 whitespace-nowrap">Pelanggan</th>
                                            <th class="px-4 py-3 whitespace-nowrap">Waktu</th>
                                            <th class="px-4 py-3 whitespace-nowrap text-right">Total</th>
                                            <th class="px-4 py-3 whitespace-nowrap text-center">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100">
                                        @foreach($recentOrders as $order)
                                        <tr class="hover:bg-gray-50 transition-colors">
                                            <td class="px-4 py-3 font-medium text-blue-600">
                                                <a href="#" class="hover:underline">#{{ $order->order_number }}</a>
                                            </td>
                                            <td class="px-4 py-3 text-gray-700 font-medium">
                                                {{ $order->customer_name }}
                                            </td>
                                            <td class="px-4 py-3 text-gray-500 text-xs">
                                                {{ $order->created_at->diffForHumans() }}
                                            </td>
                                            <td class="px-4 py-3 text-right font-bold text-gray-800">
                                                Rp {{ number_format($order->final_price, 0, ',', '.') }}
                                            </td>
                                            <td class="px-4 py-3 text-center">
                                                @if($order->payment_status == 'paid')
                                                    <span class="inline-block px-2 py-1 text-xs font-semibold leading-none text-green-800 bg-green-100 rounded">
                                                        Paid
                                                    </span>
                                                @else
                                                    <span class="inline-block px-2 py-1 text-xs font-semibold leading-none text-yellow-800 bg-yellow-100 rounded">
                                                        Unpaid
                                                    </span>
                                                @endif
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            
                            <div class="bg-gray-50 px-4 py-3 border-t border-gray-100 text-center">
                                <a href="#" class="text-sm font-medium text-blue-600 hover:text-blue-800">Lihat Semua Transaksi</a>
                            </div>
                        </div>
                    </div>

                    <div class="lg:col-span-1">
                        <div class="bg-white border-t-4 border-amber-400 rounded shadow-sm">
                            <div class="px-4 py-3 border-b border-gray-100">
                                <h3 class="text-base font-semibold text-gray-800">
                                    <i class="fas fa-star text-gray-400 mr-2"></i>Layanan Baru
                                </h3>
                            </div>
                            
                            <ul class="divide-y divide-gray-100">
                                @foreach($newProducts as $product)
                                <li class="px-4 py-3 flex items-center justify-between hover:bg-gray-50 transition-colors">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded bg-indigo-50 text-indigo-600 flex items-center justify-center text-lg shadow-sm">
                                            <i class="fas fa-print"></i>
                                        </div>
                                        <div>
                                            <a href="#" class="block text-sm font-semibold text-gray-700 hover:text-blue-600 truncate max-w-[140px]">
                                                {{ $product->name }}
                                            </a>
                                            <span class="text-xs text-gray-400 uppercase tracking-wide">{{ $product->unit }}</span>
                                        </div>
                                    </div>
                                    <span class="inline-block px-2 py-1 bg-gray-100 text-gray-700 text-xs font-bold rounded">
                                        Rp {{ number_format($product->base_price, 0, ',', '.') }}
                                    </span>
                                </li>
                                @endforeach
                            </ul>
                            
                            <div class="p-4">
                                <a href="{{ route('products.index') }}" class="block w-full text-center py-2 px-4 border border-gray-300 rounded text-sm font-medium text-gray-600 hover:bg-gray-50 hover:text-gray-800 transition-colors">
                                    Lihat Semua Layanan
                                </a>
                            </div>
                        </div>
                    </div>

                </div>

            </main>

            <footer class="bg-white border-t border-gray-200 p-4 text-sm text-gray-600 flex flex-col md:flex-row justify-between items-center">
                @include('layouts.partials.footer')
                <div class="mb-2 md:mb-0">
                    <strong>Copyright &copy; {{ date('Y') }} <a href="#" class="text-blue-600 hover:text-blue-800">Sancaka POS</a>.</strong> All rights reserved.
                </div>
                <div class="hidden md:block">
                    <b>Version</b> 1.0.0
                </div>
            </footer>
            
        </div>
    </div>

</body>
</html>