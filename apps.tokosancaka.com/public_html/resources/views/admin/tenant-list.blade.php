@extends('layouts.app')

@section('content')
    <div class="px-4 sm:px-6 lg:px-8 py-8 w-full max-w-9xl mx-auto">

        <div class="sm:flex sm:justify-between sm:items-center mb-8">
            <div class="mb-4 sm:mb-0">
                <h1 class="text-2xl md:text-3xl text-slate-800 font-bold">Data Customer (Tenant)</h1>
                <p class="text-slate-500 text-sm mt-1">Monitoring status dan masa aktif semua pendaftar.</p>
            </div>

            <div class="grid grid-flow-col sm:auto-cols-max justify-start sm:justify-end gap-2">
                <div class="bg-white border border-slate-200 px-4 py-2 rounded-lg shadow-sm text-sm font-semibold text-slate-600 flex items-center gap-2">
                    <i class="fas fa-users text-blue-500"></i>
                    <span>Total: <span class="text-slate-800 font-bold ml-1">{{ $tenants->count() }}</span></span>
                </div>
            </div>
        </div>

        <div class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="table-auto w-full divide-y divide-slate-200">
                    <thead class="text-xs font-semibold uppercase text-slate-500 bg-slate-50 border-b border-slate-200">
                        <tr>
                            <th class="px-4 py-3 whitespace-nowrap text-left">
                                <div class="font-semibold text-left">Tgl Daftar</div>
                            </th>
                            <th class="px-4 py-3 whitespace-nowrap text-left">
                                <div class="font-semibold text-left">Nama Usaha / Subdomain</div>
                            </th>
                            <th class="px-4 py-3 whitespace-nowrap text-left">
                                <div class="font-semibold text-left">WhatsApp</div>
                            </th>
                            <th class="px-4 py-3 whitespace-nowrap text-left">
                                <div class="font-semibold text-left">Paket</div>
                            </th>
                            <th class="px-4 py-3 whitespace-nowrap text-left">
                                <div class="font-semibold text-left">Status</div>
                            </th>
                            <th class="px-4 py-3 whitespace-nowrap text-left">
                                <div class="font-semibold text-left">Expired</div>
                            </th>
                            <th class="px-4 py-3 whitespace-nowrap text-center">
                                <div class="font-semibold text-center">Aksi</div>
                            </th>
                        </tr>
                    </thead>

                    <tbody class="text-sm divide-y divide-slate-200">
                        @forelse($tenants as $tenant)
                        <tr class="hover:bg-slate-50 transition duration-150">
                            <td class="px-4 py-3 whitespace-nowrap text-slate-600">
                                <div class="flex items-center gap-2">
                                    {{ \Carbon\Carbon::parse($tenant->created_at)->format('d M Y') }}
                                    <span class="text-xs text-slate-400">{{ \Carbon\Carbon::parse($tenant->created_at)->format('H:i') }}</span>

                                    @if(\Carbon\Carbon::parse($tenant->created_at)->isToday())
                                        <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-blue-100 text-blue-600 border border-blue-200">
                                            NEW
                                        </span>
                                    @endif
                                </div>
                            </td>

                            <td class="px-4 py-3 whitespace-nowrap">
                                <div class="font-bold text-slate-800">{{ $tenant->name }}</div>
                                <a href="http://{{ $tenant->subdomain }}.tokosancaka.com" target="_blank" class="text-xs text-blue-500 hover:text-blue-700 hover:underline">
                                    {{ $tenant->subdomain }}.tokosancaka.com
                                </a>
                            </td>

                            <td class="px-4 py-3 whitespace-nowrap">
                                <a href="https://wa.me/{{ $tenant->whatsapp }}" target="_blank" class="flex items-center gap-2 text-slate-600 hover:text-green-600 transition">
                                    <i class="fab fa-whatsapp text-lg text-green-500"></i>
                                    <span class="text-xs font-medium">{{ $tenant->whatsapp }}</span>
                                </a>
                            </td>

                            <td class="px-4 py-3 whitespace-nowrap">
                                @php
                                    $pkgColor = match($tenant->package) {
                                        'trial' => 'bg-yellow-100 text-yellow-700 border-yellow-200',
                                        'yearly' => 'bg-purple-100 text-purple-700 border-purple-200',
                                        default => 'bg-emerald-100 text-emerald-700 border-emerald-200',
                                    };
                                @endphp
                                <span class="px-2 py-0.5 rounded text-xs font-bold border {{ $pkgColor }} uppercase">
                                    {{ $tenant->package }}
                                </span>
                            </td>

                            <td class="px-4 py-3 whitespace-nowrap">
                                @if($tenant->status == 'active')
                                    <div class="inline-flex font-medium bg-green-100 text-green-600 rounded-full text-center px-2.5 py-0.5 text-xs">
                                        Active
                                    </div>
                                @else
                                    <div class="inline-flex font-medium bg-red-100 text-red-600 rounded-full text-center px-2.5 py-0.5 text-xs">
                                        Inactive
                                    </div>
                                @endif
                            </td>

                            <td class="px-4 py-3 whitespace-nowrap">
                                <div class="text-slate-800 font-medium">
                                    {{ \Carbon\Carbon::parse($tenant->expired_at)->format('d M Y') }}
                                </div>

                                @php
                                    // Hitung selisih hari & Bulatkan (round)
                                    $daysLeft = round(now()->diffInDays($tenant->expired_at, false));
                                @endphp

                                <div class="text-xs font-medium {{ $daysLeft < 0 ? 'text-red-500' : 'text-slate-500' }}">
                                    @if($daysLeft < 0)
                                        ({{ abs($daysLeft) }} hari lalu)
                                    @else
                                        ({{ $daysLeft }} hari lagi)
                                    @endif
                                </div>
                            </td>

                            <td class="px-4 py-3 whitespace-nowrap text-center">
                                <a href="http://{{ $tenant->subdomain }}.tokosancaka.com/login" target="_blank" class="text-slate-400 hover:text-indigo-600 transition-colors">
                                    <div class="flex items-center justify-center gap-1 border border-slate-200 rounded px-2 py-1 hover:bg-slate-50 hover:border-indigo-200">
                                        <i class="fas fa-external-link-alt text-xs"></i>
                                        <span class="text-xs font-semibold">Kunjungi</span>
                                    </div>
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="px-6 py-10 text-center text-slate-500 italic">
                                Belum ada data tenant yang mendaftar.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
