@extends('layouts.app')

@section('content')
    <div class="px-4 sm:px-6 lg:px-8 py-8 w-full max-w-9xl mx-auto">

        <div class="sm:flex sm:justify-between sm:items-center mb-8">
            <div class="mb-4 sm:mb-0 flex items-center gap-4">
                <a href="{{ route('tenants.index') }}" class="p-2 bg-white border border-slate-200 rounded hover:bg-slate-50 transition">
                    <i class="fas fa-arrow-left text-slate-500"></i>
                </a>
                <div>
                    <h1 class="text-2xl md:text-3xl text-slate-800 font-bold">Detail Tenant: {{ $tenant->name }}</h1>
                    <p class="text-slate-500 text-sm mt-1">Informasi lengkap terkait pendaftar dan usahanya.</p>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <a href="{{ route('tenants.edit', $tenant->id) }}" class="btn bg-amber-500 hover:bg-amber-600 text-white px-4 py-2 rounded-lg font-medium text-sm transition">
                    <i class="fas fa-edit mr-2"></i> Edit Data
                </a>
                <form action="{{ route('tenants.destroy', $tenant->id) }}" method="POST" onsubmit="return confirm('Yakin ingin menghapus tenant ini beserta seluruh datanya?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg font-medium text-sm transition">
                        <i class="fas fa-trash mr-2"></i> Hapus
                    </button>
                </form>
            </div>
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
            
            <div class="xl:col-span-1 space-y-6">
                <div class="bg-white p-5 border border-slate-200 rounded-xl shadow-sm">
                    <h2 class="text-lg font-semibold text-slate-800 mb-4 border-b pb-2">Informasi Toko</h2>
                    
                    <ul class="space-y-4">
                        <li>
                            <div class="text-xs text-slate-500 font-medium uppercase mb-1">Nama Usaha</div>
                            <div class="text-sm font-semibold text-slate-800">{{ $tenant->name }}</div>
                        </li>
                        <li>
                            <div class="text-xs text-slate-500 font-medium uppercase mb-1">Subdomain</div>
                            <div class="text-sm">
                                <a href="https://{{ $tenant->subdomain }}.tokosancaka.com" target="_blank" class="text-blue-500 hover:text-blue-700 font-medium flex items-center gap-1">
                                    {{ $tenant->subdomain }}.tokosancaka.com
                                    <i class="fas fa-external-link-alt text-[10px]"></i>
                                </a>
                            </div>
                        </li>
                        <li>
                            <div class="text-xs text-slate-500 font-medium uppercase mb-1">Nomor WhatsApp</div>
                            <div class="text-sm font-medium text-slate-800 flex items-center gap-2">
                                <i class="fab fa-whatsapp text-green-500 text-lg"></i>
                                <a href="https://wa.me/{{ $tenant->whatsapp }}" target="_blank" class="hover:text-green-600">
                                    {{ $tenant->whatsapp }}
                                </a>
                            </div>
                        </li>
                        <li>
                            <div class="text-xs text-slate-500 font-medium uppercase mb-1">Paket Berlangganan</div>
                            @php
                                $pkgColor = match($tenant->package) {
                                    'trial' => 'bg-yellow-100 text-yellow-700',
                                    'yearly' => 'bg-purple-100 text-purple-700',
                                    default => 'bg-emerald-100 text-emerald-700',
                                };
                            @endphp
                            <span class="px-2 py-1 rounded text-xs font-bold {{ $pkgColor }} uppercase inline-block">
                                {{ $tenant->package }}
                            </span>
                        </li>
                        <li>
                            <div class="text-xs text-slate-500 font-medium uppercase mb-1">Status Akun</div>
                            @if($tenant->status == 'active')
                                <span class="px-2 py-1 rounded text-xs font-bold bg-green-100 text-green-700 uppercase inline-block">Active</span>
                            @elseif($tenant->status == 'suspended')
                                <span class="px-2 py-1 rounded text-xs font-bold bg-orange-100 text-orange-700 uppercase inline-block">Suspended</span>
                            @else
                                <span class="px-2 py-1 rounded text-xs font-bold bg-red-100 text-red-700 uppercase inline-block">Inactive</span>
                            @endif
                        </li>
                    </ul>
                </div>

                <div class="bg-white p-5 border border-slate-200 rounded-xl shadow-sm">
                    <h2 class="text-lg font-semibold text-slate-800 mb-4 border-b pb-2">Timeline</h2>
                    <ul class="space-y-4">
                        <li>
                            <div class="text-xs text-slate-500 font-medium uppercase mb-1">Tanggal Daftar</div>
                            <div class="text-sm font-medium text-slate-800">
                                {{ \Carbon\Carbon::parse($tenant->created_at)->translatedFormat('d F Y - H:i') }}
                            </div>
                        </li>
                        <li>
                            <div class="text-xs text-slate-500 font-medium uppercase mb-1">Masa Aktif Berakhir (Expired)</div>
                            <div class="text-sm font-medium text-slate-800">
                                {{ \Carbon\Carbon::parse($tenant->expired_at)->translatedFormat('d F Y - H:i') }}
                            </div>
                            @php
                                $daysLeft = round(now()->diffInDays($tenant->expired_at, false));
                            @endphp
                            <div class="text-xs mt-1 font-semibold {{ $daysLeft < 0 ? 'text-red-500' : 'text-slate-500' }}">
                                @if($daysLeft < 0)
                                    (Sudah lewat {{ abs($daysLeft) }} hari)
                                @else
                                    (Sisa {{ $daysLeft }} hari)
                                @endif
                            </div>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="xl:col-span-2 space-y-6">
                
                <div class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden">
                    <div class="px-5 py-4 border-b border-slate-200">
                        <h2 class="font-semibold text-slate-800">Daftar Pengguna (User/Kasir)</h2>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="table-auto w-full divide-y divide-slate-200">
                            <thead class="text-xs uppercase text-slate-500 bg-slate-50">
                                <tr>
                                    <th class="px-4 py-3 text-left font-semibold">Nama</th>
                                    <th class="px-4 py-3 text-left font-semibold">Email</th>
                                    <th class="px-4 py-3 text-left font-semibold">Role</th>
                                </tr>
                            </thead>
                            <tbody class="text-sm divide-y divide-slate-200">
                                @forelse($users as $user)
                                <tr class="hover:bg-slate-50">
                                    <td class="px-4 py-3 font-medium text-slate-800">{{ $user->name }}</td>
                                    <td class="px-4 py-3 text-slate-600">{{ $user->email }}</td>
                                    <td class="px-4 py-3">
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-600 border border-slate-200 uppercase">
                                            {{ $user->role ?? 'Staff' }}
                                        </span>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="3" class="px-4 py-6 text-center text-slate-500 italic">Belum ada pengguna untuk tenant ini.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden">
                    <div class="px-5 py-4 border-b border-slate-200">
                        <h2 class="font-semibold text-slate-800">Riwayat Lisensi</h2>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="table-auto w-full divide-y divide-slate-200">
                            <thead class="text-xs uppercase text-slate-500 bg-slate-50">
                                <tr>
                                    <th class="px-4 py-3 text-left font-semibold">Kode Lisensi</th>
                                    <th class="px-4 py-3 text-left font-semibold">Paket / Tipe</th>
                                    <th class="px-4 py-3 text-left font-semibold">Status</th>
                                    <th class="px-4 py-3 text-left font-semibold">Tgl Digunakan</th>
                                </tr>
                            </thead>
                            <tbody class="text-sm divide-y divide-slate-200">
                                @forelse($licenses as $license)
                                <tr class="hover:bg-slate-50">
                                    <td class="px-4 py-3 font-mono font-medium text-blue-600">{{ $license->license_code }}</td>
                                    <td class="px-4 py-3 text-slate-600 uppercase text-xs">{{ $license->package_type }}</td>
                                    <td class="px-4 py-3">
                                        @if($license->status == 'used')
                                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-indigo-100 text-indigo-600">USED</span>
                                        @else
                                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-600">{{ strtoupper($license->status) }}</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-slate-500 text-xs">
                                        {{ $license->used_at ? \Carbon\Carbon::parse($license->used_at)->format('d M Y') : '-' }}
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="4" class="px-4 py-6 text-center text-slate-500 italic">Belum ada riwayat lisensi.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </div>
@endsection