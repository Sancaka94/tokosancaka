@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')

    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-10">
        <div>
            <h1 class="text-3xl font-black text-slate-900 tracking-tight">Ringkasan Operasional</h1>
            <p class="text-slate-500 font-medium text-sm mt-1">
                Pantau performa bisnis Anda hari ini, <span class="text-blue-600 font-bold">{{ \Carbon\Carbon::now()->translatedFormat('d F Y') }}</span>
            </p>
        </div>

        <a href="{{ route('orders.create') }}"
           class="flex items-center gap-3 px-6 py-3.5 bg-blue-600 hover:bg-blue-700 text-white rounded-2xl font-bold shadow-xl shadow-blue-200 transition-all transform hover:-translate-y-1 active:scale-95">
            <i class="fas fa-plus-circle text-lg"></i>
            <span>Buat Transaksi Baru</span>
        </a>
    </div>

    <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-100 mb-8">
        <form action="{{ route('dashboard') }}" method="GET" class="flex flex-col md:flex-row gap-4 items-end">
            <div class="w-full md:w-auto flex-1">
                <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Periode Mulai</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="fas fa-calendar-alt text-slate-400"></i>
                    </div>
                    <input type="date" name="start_date" value="{{ request('start_date') }}" class="w-full pl-10 pr-4 py-2.5 bg-slate-50 border border-slate-200 text-slate-700 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none transition-all">
                </div>
            </div>

            <div class="w-full md:w-auto flex-1">
                <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Sampai Tanggal</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="fas fa-calendar-check text-slate-400"></i>
                    </div>
                    <input type="date" name="end_date" value="{{ request('end_date') }}" class="w-full pl-10 pr-4 py-2.5 bg-slate-50 border border-slate-200 text-slate-700 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none transition-all">
                </div>
            </div>

            <div class="w-full md:w-1/3 flex-1">
                <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Cari Resi / Order / Ref</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="fas fa-search text-slate-400"></i>
                    </div>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Ketik nomor resi..." class="w-full pl-10 pr-4 py-2.5 bg-slate-50 border border-slate-200 text-slate-700 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none transition-all">
                </div>
            </div>

            <div class="w-full md:w-auto flex-1">
                <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Status Kiriman</label>
                <select name="shipping_status" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 text-slate-700 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none transition-all">
                    <option value="">Semua Status</option>
                    <option value="Selesai Terkirim" {{ request('shipping_status') == 'Selesai Terkirim' ? 'selected' : '' }}>Selesai Terkirim</option>
                    <option value="Belum Dikirim" {{ request('shipping_status') == 'Belum Dikirim' ? 'selected' : '' }}>Belum Dikirim</option>
                    <option value="Menunggu Pickup" {{ request('shipping_status') == 'Menunggu Pickup' ? 'selected' : '' }}>Menunggu Pickup</option>
                    <option value="Gagal" {{ request('shipping_status') == 'Gagal' ? 'selected' : '' }}>Gagal / Cancel</option>
                </select>
            </div>

            <div class="w-full md:w-auto">
                <button type="submit" class="w-full md:w-auto px-6 py-2.5 bg-slate-800 hover:bg-slate-900 text-white font-bold rounded-xl shadow-md transition-all">
                    <i class="fas fa-filter mr-2"></i> Terapkan
                </button>
            </div>
        </form>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
        <div class="bg-white border-l-4 border-emerald-500 p-4 rounded-xl shadow-sm hover:shadow-md transition-all">
            <p class="text-[10px] uppercase font-bold text-slate-400 mb-1"><i class="fas fa-box-check text-emerald-500 mr-1"></i> Selesai Terkirim</p>
            <h4 class="text-2xl font-black text-slate-800">{{ $countSelesai ?? 0 }} <span class="text-xs font-normal text-slate-400">Paket</span></h4>
        </div>
        <div class="bg-white border-l-4 border-slate-400 p-4 rounded-xl shadow-sm hover:shadow-md transition-all">
            <p class="text-[10px] uppercase font-bold text-slate-400 mb-1"><i class="fas fa-box text-slate-400 mr-1"></i> Belum Dikirim</p>
            <h4 class="text-2xl font-black text-slate-800">{{ $countBelumDikirim ?? 0 }} <span class="text-xs font-normal text-slate-400">Paket</span></h4>
        </div>
        <div class="bg-white border-l-4 border-amber-500 p-4 rounded-xl shadow-sm hover:shadow-md transition-all">
            <p class="text-[10px] uppercase font-bold text-slate-400 mb-1"><i class="fas fa-truck-loading text-amber-500 mr-1"></i> Menunggu Pickup</p>
            <h4 class="text-2xl font-black text-slate-800">{{ $countPickup ?? 0 }} <span class="text-xs font-normal text-slate-400">Paket</span></h4>
        </div>
        <div class="bg-white border-l-4 border-red-500 p-4 rounded-xl shadow-sm hover:shadow-md transition-all">
            <p class="text-[10px] uppercase font-bold text-slate-400 mb-1"><i class="fas fa-box-open text-red-500 mr-1"></i> Gagal / Cancel</p>
            <h4 class="text-2xl font-black text-slate-800">{{ $countGagal ?? 0 }} <span class="text-xs font-normal text-slate-400">Paket</span></h4>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-6 mb-10">

        {{-- Total Omzet --}}
        <div class="bg-white rounded-[2rem] p-6 shadow-sm border border-slate-100 relative overflow-hidden group hover:shadow-2xl hover:shadow-slate-200 transition-all duration-500">
            <div class="absolute -right-4 -top-4 w-24 h-24 bg-blue-50 rounded-full opacity-50 group-hover:scale-150 transition-transform duration-700"></div>
            <div class="relative z-10">
                <div class="w-12 h-12 rounded-2xl bg-blue-600 flex items-center justify-center text-white mb-4 shadow-lg shadow-blue-100">
                    <i class="fas fa-wallet text-xl"></i>
                </div>
                <p class="text-xs font-bold text-slate-400 uppercase tracking-[0.1em] mb-1">Total Omzet</p>
                <h3 class="text-2xl font-black text-slate-900 truncate">
                    <span class="text-sm font-bold text-blue-600">Rp</span> {{ number_format($totalOmzet ?? 0, 0, ',', '.') }}
                </h3>
                <div class="flex items-center gap-1.5 mt-3 text-[10px] font-black text-emerald-600 bg-emerald-50 w-fit px-2.5 py-1 rounded-full uppercase">
                    <i class="fas fa-circle-check"></i> Paid Only
                </div>
            </div>
        </div>

        {{-- Item Terjual --}}
        <div class="bg-white rounded-[2rem] p-6 shadow-sm border border-slate-100 relative overflow-hidden group hover:shadow-2xl hover:shadow-slate-200 transition-all duration-500">
            <div class="relative z-10">
                <div class="w-12 h-12 rounded-2xl bg-emerald-500 flex items-center justify-center text-white mb-4 shadow-lg shadow-emerald-100">
                    <i class="fas fa-box-open text-xl"></i>
                </div>
                <p class="text-xs font-bold text-slate-400 uppercase tracking-[0.1em] mb-1">Item Terjual</p>
                <h3 class="text-2xl font-black text-slate-900">
                    {{ number_format($totalTerjual ?? 0) }} <span class="text-sm font-medium text-slate-400">Pcs</span>
                </h3>
                <p class="text-[10px] text-slate-400 font-bold mt-4 uppercase">Volume Produk</p>
            </div>
        </div>

        {{-- Saldo DANA --}}
        {{-- Saldo DANA (Dibatasi hanya untuk Super Admin di subdomain apps & admin) --}}
        @if(Auth::check() && Auth::user()->role === 'super_admin' && in_array(explode('.', request()->getHost())[0], ['apps', 'admin']))
        <div class="bg-gradient-to-br from-blue-600 to-blue-700 rounded-[2rem] p-6 shadow-xl shadow-blue-100 relative overflow-hidden group active:scale-95 transition-all">
            <div class="absolute right-0 bottom-0 opacity-10 transform translate-x-4 translate-y-4">
                <i class="fas fa-vault text-[100px]"></i>
            </div>
            <div class="relative z-10">
                <div class="flex justify-between items-start mb-4">
                    <div class="w-10 h-10 rounded-xl bg-white/20 backdrop-blur-md flex items-center justify-center text-white">
                        <i class="fas fa-shield-halved text-lg"></i>
                    </div>
                    <form action="{{ route('dana.checkMerchantBalance') }}" method="POST">
                        @csrf
                        <input type="hidden" name="affiliate_id" value="11">
                        <button type="submit" class="p-2 bg-white/10 hover:bg-white/30 rounded-lg text-white transition-colors">
                            <i class="fas fa-arrows-rotate text-sm"></i>
                        </button>
                    </form>
                </div>
                <p class="text-[10px] font-bold text-blue-100 uppercase tracking-widest mb-1">Saldo Deposit DANA</p>
                <h3 class="text-xl font-black text-white truncate">
                    Rp {{ number_format($merchantBalance ?? 0, 0, ',', '.') }}
                </h3>
                <p class="text-[9px] font-bold text-blue-200 mt-3 italic">*Data Terkini API</p>
            </div>
        </div>
        @endif

        {{-- Saldo DANA (Khusus Admin & Finance/Keuangan) --}}
        @if(Auth::check() && in_array(Auth::user()->role, ['admin', 'finance', 'keuangan']))
        <div class="bg-white rounded-[2rem] p-6 shadow-sm border border-slate-100 relative overflow-hidden group hover:shadow-2xl hover:shadow-slate-200 transition-all duration-500">
            <div class="relative z-10 flex flex-col h-full">

                {{-- JIKA SUDAH CONNECT DANA --}}
                @if(Auth::user()->dana_access_token)
                    <div class="flex justify-between items-start mb-4">
                        <div class="w-12 h-12 rounded-2xl bg-blue-50 flex items-center justify-center p-2 shadow-lg shadow-blue-100">
                            <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/7/72/Logo_dana_blue.svg/1200px-Logo_dana_blue.svg.png" class="w-full h-full object-contain" alt="DANA">
                        </div>
                        <a href="{{ route('tenant.dana.sync') }}" class="p-2 text-blue-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-all group/sync" title="Sinkronkan Saldo">
                            <i class="fas fa-sync-alt group-hover/sync:rotate-180 transition-transform duration-700"></i>
                        </a>
                    </div>
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-[0.1em] mb-1">Saldo DANA</p>
                    <h3 class="text-2xl font-black text-slate-900 truncate group-hover:text-blue-600 transition-colors">
                        <span class="text-sm font-bold text-slate-500">Rp</span> {{ number_format(Auth::user()->dana_balance ?? 0, 0, ',', '.') }}
                    </h3>
                    <div class="flex items-center gap-1.5 mt-4 text-[10px] font-black text-blue-600 bg-blue-50 w-fit px-2.5 py-1 rounded-full uppercase">
                        <i class="fas fa-check-circle"></i> Terhubung
                    </div>

                {{-- JIKA BELUM CONNECT DANA --}}
                @else
                    <div class="w-12 h-12 rounded-2xl bg-slate-50 flex items-center justify-center text-slate-400 mb-4 shadow-inner text-xl">
                        <i class="fas fa-wallet text-slate-300"></i>
                    </div>
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-[0.1em] mb-1">Saldo DANA</p>
                    <h3 class="text-lg font-black text-slate-400 mb-4">
                        Belum Terhubung
                    </h3>
                    <a href="{{ route('tenant.dana.start') }}" class="flex items-center justify-center gap-2 w-full px-4 py-2.5 bg-slate-100 hover:bg-blue-600 text-slate-500 hover:text-white text-xs font-bold rounded-xl transition-all shadow-sm mt-auto group/link">
                        <img src="https://tokosancaka.com/storage/logo/dana.png" class="h-3 w-auto grayscale opacity-60 group-hover/link:grayscale-0 group-hover/link:opacity-100 group-hover/link:brightness-0 group-hover/link:invert transition-all">
                        <span>Hubungkan</span>
                    </a>
                @endif

            </div>
        </div>
        @endif


        {{-- Pelanggan --}}
        <div class="bg-white rounded-[2rem] p-6 shadow-sm border border-slate-100 relative overflow-hidden group hover:shadow-2xl transition-all duration-500">
            <div class="relative z-10">
                <div class="w-12 h-12 rounded-2xl bg-amber-500 flex items-center justify-center text-white mb-4 shadow-lg shadow-amber-100 text-xl">
                    <i class="fas fa-users"></i>
                </div>
                <p class="text-xs font-bold text-slate-400 uppercase tracking-[0.1em] mb-1">Pelanggan</p>
                <h3 class="text-2xl font-black text-slate-900">
                    {{ number_format($totalPelanggan ?? 0) }}
                </h3>
                <p class="text-[10px] text-slate-400 font-bold mt-4 uppercase text-amber-600">Terdaftar Aktif</p>
            </div>
        </div>

        {{-- Staff --}}
        <div class="bg-white rounded-[2rem] p-6 shadow-sm border border-slate-100 relative overflow-hidden group hover:shadow-2xl transition-all duration-500">
            <div class="relative z-10">
                <div class="w-12 h-12 rounded-2xl bg-red-600 flex items-center justify-center text-white mb-4 shadow-lg shadow-red-100 text-xl text-white">
                    <i class="fas fa-user-shield"></i>
                </div>
                <p class="text-xs font-bold text-slate-400 uppercase tracking-[0.1em] mb-1">User / Staff</p>
                <h3 class="text-2xl font-black text-slate-900">
                    {{ number_format($totalUser ?? 0) }}
                </h3>
                <p class="text-[10px] text-red-600 font-bold mt-4 uppercase">Akun Terotorisasi</p>
            </div>
        </div>
    </div>


    <div class="bg-white p-6 rounded-[2.5rem] shadow-sm border border-slate-100 mb-10">
        <div class="flex justify-between items-center mb-6">
            <h3 class="font-black text-slate-800 text-lg flex items-center gap-3">
                <span class="w-2 h-6 bg-purple-600 rounded-full"></span>
                Perbandingan Omzet (Bulan Ini vs Lalu)
            </h3>
        </div>
        <div id="chart-comparison" class="w-full h-[380px]"></div>
    </div>


    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

        <div class="lg:col-span-2 bg-white rounded-[2.5rem] shadow-sm border border-slate-100 overflow-hidden">
            <div class="p-8 border-b border-slate-50 flex justify-between items-center">
                <div>
                    <h3 class="font-black text-slate-800 text-lg flex items-center gap-3">
                        <span class="w-2 h-6 bg-blue-600 rounded-full"></span>
                        Transaksi Terbaru
                    </h3>
                </div>
                <a href="{{ route('orders.index') }}" class="text-xs font-bold text-blue-600 hover:bg-blue-50 px-4 py-2 rounded-xl transition-all">Lihat Semua</a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left border-separate border-spacing-y-2 px-6">
                    <thead class="text-slate-400 uppercase text-[10px] font-bold tracking-[0.15em]">
                        <tr>
                            <th class="px-4 py-4">Order ID</th>
                            <th class="px-4 py-4">Pelanggan</th>
                            <th class="px-4 py-4 text-right">Total</th>
                            <th class="px-4 py-4 text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentOrders ?? [] as $order)
                        <tr class="group hover:bg-slate-50/80 transition-all duration-300">
                            <td class="px-4 py-5 bg-slate-50 rounded-l-2xl group-hover:bg-white transition-colors">
                                <span class="font-black text-blue-600">#{{ $order->order_number }}</span>
                            </td>
                            <td class="px-4 py-5 group-hover:bg-white transition-colors">
                                <p class="font-bold text-slate-800 leading-none mb-1">{{ $order->customer_name }}</p>
                                <span class="text-[10px] text-slate-400 font-bold">{{ $order->created_at->diffForHumans() }}</span>
                            </td>
                            <td class="px-4 py-5 text-right group-hover:bg-white transition-colors">
                                <span class="font-black text-slate-900">Rp {{ number_format($order->final_price, 0, ',', '.') }}</span>
                            </td>
                            <td class="px-4 py-5 text-center rounded-r-2xl group-hover:bg-white transition-colors">
                                <span class="px-3 py-1.5 rounded-xl text-[10px] font-black uppercase tracking-wider {{ $order->payment_status == 'paid' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">
                                    {{ $order->payment_status }}
                                </span>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="px-6 py-12 text-center">
                                <div class="flex flex-col items-center gap-2">
                                    <i class="fas fa-file-circle-xmark text-4xl text-slate-200"></i>
                                    <p class="text-slate-400 text-sm font-medium italic">Belum ada transaksi hari ini.</p>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="bg-white rounded-[2.5rem] shadow-sm border border-slate-100 p-8">
            <h3 class="font-black text-slate-800 text-lg flex items-center gap-3 mb-8">
                <span class="w-2 h-6 bg-red-600 rounded-full"></span>
                Layanan Populer
            </h3>

            <div class="space-y-5">
                @forelse($newProducts ?? [] as $product)
                <div class="flex items-center gap-4 group cursor-pointer">
                    <div class="w-14 h-14 rounded-2xl bg-slate-50 flex items-center justify-center text-slate-400 group-hover:bg-blue-600 group-hover:text-white group-hover:shadow-lg group-hover:shadow-blue-200 transition-all duration-300 transform group-hover:-rotate-6">
                        <i class="fas fa-cube text-xl"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <h4 class="text-sm font-black text-slate-800 group-hover:text-blue-600 transition-colors truncate">{{ $product->name }}</h4>
                        <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest">{{ $product->unit }}</p>
                    </div>
                    <div class="text-right">
                        <p class="text-sm font-black text-slate-900 leading-none">Rp {{ number_format($product->sell_price, 0, ',', '.') }}</p>
                    </div>
                </div>
                @empty
                <div class="text-center py-10">
                    <i class="fas fa-box-open text-3xl text-slate-100 mb-2"></i>
                    <p class="text-xs text-slate-400 font-medium">Kosong</p>
                </div>
                @endforelse

                <div class="pt-6 border-t border-slate-50">
                    <a href="{{ route('products.index') }}" class="flex items-center justify-center gap-2 w-full py-4 text-xs font-black text-slate-500 bg-slate-50 rounded-2xl hover:bg-blue-600 hover:text-white transition-all shadow-sm">
                        LIHAT SEMUA PRODUK <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
            </div>
        </div>

    </div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        // Ambil data dari Controller (Pastikan data ini sudah dipassing dari DashboardController)
        var dataBulanIni = {!! json_encode(array_values($dataBulanIni ?? array_fill(0, 31, 0))) !!};
        var dataBulanLalu = {!! json_encode(array_values($dataBulanLalu ?? array_fill(0, 31, 0))) !!};
        var dataTigaBulanLalu = {!! json_encode(array_values($dataTigaBulanLalu ?? array_fill(0, 31, 0))) !!};

        var options = {
            series: [{
                name: 'Bulan Ini',
                type: 'area', // Gelombang (Area)
                data: dataBulanIni
            }, {
                name: 'Bulan Lalu',
                type: 'column', // Balok
                data: dataBulanLalu
            }, {
                name: '3 Bulan Lalu',
                type: 'line', // Garis
                data: dataTigaBulanLalu
            }],
            chart: {
                height: 380,
                type: 'line',
                toolbar: { show: false },
                fontFamily: 'Inter, sans-serif'
            },
            stroke: {
                curve: 'smooth',
                width: [3, 0, 3] // Ketebalan: Area(3), Balok(0), Garis(3)
            },
            fill: {
                type: ['gradient', 'solid', 'solid'],
                gradient: {
                    shadeIntensity: 1,
                    opacityFrom: 0.4,
                    opacityTo: 0.05,
                    stops: [0, 90, 100]
                }
            },
            colors: ['#ef4444', '#10b981', '#3b82f6'], // Merah, Hijau, Biru
            xaxis: {
                categories: Array.from({length: 31}, (_, i) => i + 1), // Tgl 1 - 31
                title: {
                    text: 'Tanggal (1-31)',
                    style: { color: '#94a3b8', fontSize: '10px', fontWeight: 700 }
                },
                labels: { style: { colors: '#64748b' } }
            },
            yaxis: {
                labels: {
                    style: { colors: '#64748b' },
                    formatter: function (value) {
                        if(value >= 1000000) return "Rp " + (value / 1000000).toFixed(1) + " Jt";
                        if(value >= 1000) return "Rp " + (value / 1000).toFixed(1) + " Rb";
                        return "Rp " + value;
                    }
                }
            },
            tooltip: {
                shared: true,
                intersect: false,
                y: {
                    formatter: function (y) {
                        if (typeof y !== "undefined") {
                            return "Rp " + new Intl.NumberFormat('id-ID').format(y);
                        }
                        return y;
                    }
                }
            },
            dataLabels: { enabled: false },
            legend: {
                position: 'top',
                horizontalAlign: 'right'
            }
        };

        var chart = new ApexCharts(document.querySelector("#chart-comparison"), options);
        chart.render();
    });
</script>
@endpush
