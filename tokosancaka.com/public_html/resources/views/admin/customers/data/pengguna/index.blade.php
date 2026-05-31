@extends('layouts.admin')

@section('title', 'Data Pengguna & Pelanggan')

@section('content')
{{-- Style tambahan untuk mencegah flicker Alpine.js sebelum script dimuat --}}
<style>
    [x-cloak] { display: none !important; }
</style>

<div class="bg-[#f8f9fa] min-h-screen pb-10" x-data="customerTableData()">

    <div class="bg-white border-b border-[#dee2e6] px-4 py-4 sm:px-6 lg:px-8 mb-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between max-w-[1400px] mx-auto">
            <div class="flex-1 min-w-0">
                <h1 class="text-2xl font-normal leading-7 text-[#212529] sm:text-3xl sm:truncate">
                    Data Pengguna & Pelanggan
                </h1>
            </div>
            <div class="mt-4 flex sm:mt-0 sm:ml-4">
                <nav class="flex" aria-label="Breadcrumb">
                    <ol role="list" class="flex items-center space-x-2 text-sm text-[#6c757d]">
                        <li><a href="{{ route('admin.dashboard') }}" class="hover:text-[#0d6efd] transition-colors text-decoration-none">Dashboard</a></li>
                        <li class="flex items-center">
                            <i class="fa-solid fa-chevron-right text-xs mx-2"></i>
                            <span class="text-[#495057]">Data Pengguna</span>
                        </li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    <section class="px-4 sm:px-6 lg:px-8">
        <div class="max-w-[1400px] mx-auto space-y-6">

            {{-- 🔎 Filter, Pencarian, & Ekspor Data --}}
            <div class="bg-white border border-[#dee2e6] rounded shadow-sm p-4 flex flex-col md:flex-row md:items-center justify-between gap-4">
                
                {{-- Area Pencarian dan Filter --}}
                <form action="{{ route('admin.customers.data.pengguna.index') }}" method="GET" class="flex flex-wrap items-center gap-2">
                    <input type="text" name="search" placeholder="Cari ID, Nama, No. HP..." value="{{ request('search') }}"
                           class="block w-full md:w-auto px-3 py-2 text-base font-normal text-[#212529] bg-white border border-[#ced4da] rounded focus:border-[#86b7fe] focus:outline-none focus:ring-[4px] focus:ring-[#0d6efd]/25 transition">
                    
                    <input type="text" name="city" placeholder="Cari Kota/Kabupaten..." value="{{ request('city') }}"
                           class="block w-full md:w-auto px-3 py-2 text-base font-normal text-[#212529] bg-white border border-[#ced4da] rounded focus:border-[#86b7fe] focus:outline-none focus:ring-[4px] focus:ring-[#0d6efd]/25 transition">

                    <button type="submit" class="inline-block px-4 py-2 bg-[#0d6efd] text-white font-medium text-base leading-tight rounded shadow-sm hover:bg-[#0b5ed7] focus:bg-[#0b5ed7] focus:outline-none focus:ring-4 focus:ring-[#0d6efd]/50 transition">
                        <i class="fa-solid fa-search mr-1"></i> Cari
                    </button>
                </form>

                {{-- Area Ekspor --}}
                <div class="flex gap-2 w-full md:w-auto">
                    <a href="{{ route('admin.customers.pengguna.export', ['type' => 'excel', 'search' => request('search'), 'city' => request('city')]) }}" 
                       class="inline-block flex-1 md:flex-none text-center px-4 py-2 bg-[#198754] text-white font-medium text-base leading-tight rounded shadow-sm hover:bg-[#157347] focus:ring-4 focus:ring-[#198754]/50 transition">
                        <i class="fa-solid fa-file-excel mr-1"></i> Excel
                    </a>
                    <a href="{{ route('admin.customers.pengguna.export', ['type' => 'pdf', 'search' => request('search'), 'city' => request('city')]) }}" 
                       class="inline-block flex-1 md:flex-none text-center px-4 py-2 bg-[#dc3545] text-white font-medium text-base leading-tight rounded shadow-sm hover:bg-[#bb2d3b] focus:ring-4 focus:ring-[#dc3545]/50 transition">
                        <i class="fa-solid fa-file-pdf mr-1"></i> PDF
                    </a>
                </div>
            </div>
            
            {{-- Tabel Data --}}
            <div class="bg-white border border-[#dee2e6] rounded shadow-sm overflow-hidden">
                
                <div class="bg-[#f8f9fa] border-b border-[#dee2e6] px-5 py-3 flex justify-between items-center">
                    <h5 class="text-lg font-medium text-[#212529] m-0">
                        <i class="fa-solid fa-users text-[#0d6efd] mr-2"></i> Daftar Pelanggan Terdaftar 
                        <span class="text-sm font-normal text-[#6c757d] ml-1">(Total: {{ $pengguna->total() }})</span>
                    </h5>
                </div>

                <div class="overflow-x-auto w-full">
                    <table class="w-full text-left border-collapse">
                        <thead class="bg-white border-b-2 border-[#dee2e6]">
                            <tr>
                                <th scope="col" class="px-4 py-3 text-sm font-bold text-[#495057] uppercase tracking-wider whitespace-nowrap">ID / Logo</th>
                                <th scope="col" class="px-4 py-3 text-sm font-bold text-[#495057] uppercase tracking-wider whitespace-nowrap">Nama & Kontak</th>
                                <th scope="col" class="px-4 py-3 text-sm font-bold text-[#495057] uppercase tracking-wider whitespace-nowrap">Toko & Lokasi</th>
                                <th scope="col" class="px-4 py-3 text-sm font-bold text-[#495057] uppercase tracking-wider whitespace-nowrap">Keuangan</th>
                                <th scope="col" class="px-4 py-3 text-sm font-bold text-[#495057] uppercase tracking-wider whitespace-nowrap text-center">Role & Status</th>
                                <th scope="col" class="sticky right-0 z-10 px-4 py-3 text-sm font-bold text-[#495057] uppercase tracking-wider text-center bg-white border-l border-[#dee2e6] shadow-[-4px_0_6px_rgba(0,0,0,0.02)]">Aksi</th>
                            </tr>
                        </thead>
                        
                        <tbody class="divide-y divide-[#dee2e6]">
                            @forelse ($pengguna as $data)
                            <tr class="hover:bg-[#f8f9fa] transition-colors">
                                {{-- Kolom 1: ID & Logo --}}
                                <td class="px-4 py-3 whitespace-nowrap align-middle">
                                    <div class="flex items-center gap-3">
                                        @if ($data->store_logo_path)
                                            <img src="{{ asset('storage/' . $data->store_logo_path) }}" alt="Logo" class="w-10 h-10 rounded object-cover border border-[#dee2e6]">
                                        @else
                                            <div class="w-10 h-10 rounded bg-[#e9ecef] flex items-center justify-center border border-[#dee2e6] text-[#6c757d] font-bold">
                                                {{ substr($data->nama_lengkap, 0, 1) }}
                                            </div>
                                        @endif
                                        <span class="text-sm font-bold text-[#212529]">#{{ $data->id_pengguna }}</span>
                                    </div>
                                </td>

                                {{-- Kolom 2: Nama & Kontak --}}
                                <td class="px-4 py-3 align-middle">
                                    <div class="text-sm font-bold text-[#0d6efd]">{{ $data->nama_lengkap }}</div>
                                    <div class="text-sm text-[#495057] mt-0.5"><i class="fa-regular fa-envelope w-4"></i> {{ $data->email }}</div>
                                    <div class="text-sm text-[#495057] mt-0.5"><i class="fa-brands fa-whatsapp text-[#198754] w-4"></i> {{ $data->no_wa }}</div>
                                </td>

                                {{-- Kolom 3: Toko & Lokasi --}}
                                <td class="px-4 py-3 align-middle min-w-[200px]">
                                    <div class="text-sm font-bold text-[#212529] mb-1">{{ $data->store_name ?? '—' }}</div>
                                    <div class="text-xs text-[#6c757d] leading-tight">
                                        @if($data->province || $data->regency)
                                            {{ $data->regency ?? '' }}, {{ $data->province ?? '' }}
                                        @else
                                            <span class="italic text-[#adb5bd]">Lokasi belum diatur</span>
                                        @endif
                                    </div>
                                </td>

                                {{-- Kolom 4: Keuangan (Saldo & Bank) --}}
                                <td class="px-4 py-3 align-middle whitespace-nowrap">
                                    <div class="text-sm font-bold text-[#198754]">Rp {{ number_format($data->saldo ?? 0, 0, ',', '.') }}</div>
                                    <div class="text-xs text-[#6c757d] mt-1">
                                        <i class="fa-solid fa-building-columns mr-1"></i> 
                                        {{ $data->bank_name ?? 'Bank (-)' }}
                                    </div>
                                </td>

                                {{-- Kolom 5: Role & Status --}}
                                <td class="px-4 py-3 align-middle text-center whitespace-nowrap">
                                    <div class="mb-1.5">
                                        <span class="inline-block px-2 py-1 text-xs font-bold rounded 
                                            @if ($data->role == 'Admin') bg-[#dc3545] text-white
                                            @elseif ($data->role == 'Seller') bg-[#0dcaf0] text-black
                                            @else bg-[#6c757d] text-white @endif">
                                            {{ $data->role }}
                                        </span>
                                    </div>
                                    <div>
                                        @if ($data->status == 'Aktif')
                                            <span class="inline-block px-2 py-1 text-xs font-bold rounded bg-[#198754] text-white">Aktif</span>
                                        @else
                                            <span class="inline-block px-2 py-1 text-xs font-bold rounded bg-[#ffc107] text-black">{{ $data->status }}</span>
                                        @endif
                                    </div>
                                </td>
                                
                                {{-- Kolom 6: Aksi (Sticky) --}}
                                <td class="sticky right-0 z-10 px-4 py-3 align-middle text-center bg-white border-l border-[#dee2e6] shadow-[-4px_0_6px_rgba(0,0,0,0.02)]">
                                    <div class="flex items-center justify-center gap-2">
                                        {{-- Tombol Mata (Modal Detail) --}}
                                        <button type="button" @click="openModal(JSON.parse($el.dataset.user))" data-user="{{ json_encode($data) }}" class="inline-flex items-center justify-center w-8 h-8 rounded bg-[#0dcaf0] text-black hover:bg-[#31d2f2] transition" title="Lihat Detail Lengkap">
                                            <i class="fa-solid fa-eye"></i>
                                        </button>

                                        {{-- Edit --}}
                                        <a href="{{ route('admin.customers.data.pengguna.edit', $data->id_pengguna) }}" class="inline-flex items-center justify-center w-8 h-8 rounded bg-[#ffc107] text-black hover:bg-[#ffcd39] transition" title="Edit Data">
                                            <i class="fa-solid fa-pencil"></i>
                                        </a>

                                        {{-- Hapus --}}
                                        <form action="{{ route('admin.customers.data.pengguna.destroy', $data->id_pengguna) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus akun pengguna ini?');" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="inline-flex items-center justify-center w-8 h-8 rounded bg-[#dc3545] text-white hover:bg-[#bb2d3b] transition" title="Hapus Akun">
                                                <i class="fa-solid fa-trash-can"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="px-6 py-8 text-center text-[#6c757d] italic bg-[#f8f9fa]">Tidak ada data pengguna yang ditemukan.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="bg-[#f8f9fa] px-4 py-3 border-t border-[#dee2e6]">
                    <div class="flex justify-end">
                        {{ $pengguna->links() }}
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ================= MODAL DETAIL PENGGUNA LENGKAP (Bootstrap 5 Style) ================= --}}
    <div x-show="isModalOpen" x-cloak class="relative z-[9999]" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        
        {{-- Backdrop --}}
        <div x-show="isModalOpen" 
             x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" 
             x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" 
             class="fixed inset-0 bg-black bg-opacity-50 transition-opacity" @click="closeModal()"></div>

        {{-- Modal Dialog --}}
        <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
            <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                <div x-show="isModalOpen" 
                     x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" 
                     x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" 
                     class="relative transform overflow-hidden bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-5xl border border-[rgba(0,0,0,0.175)] rounded">
                    
                    {{-- Modal Header --}}
                    <div class="bg-white border-b border-[#dee2e6] px-4 py-3 flex items-center justify-between">
                        <h4 class="text-xl font-medium text-[#212529] m-0" id="modal-title">
                            <i class="fa-solid fa-address-card text-[#0d6efd] mr-2"></i> Detail Lengkap Database
                        </h4>
                        <button type="button" @click="closeModal()" class="text-[#6c757d] hover:text-[#212529] focus:outline-none">
                            <i class="fa-solid fa-xmark text-2xl"></i>
                        </button>
                    </div>

                    {{-- Modal Body --}}
                    <div class="px-5 py-4 max-h-[75vh] overflow-y-auto bg-[#f8f9fa]">
                        <template x-if="selectedUser">
                            <div class="space-y-4">
                                
                                {{-- Identitas & Kontak --}}
                                <div class="bg-white p-4 border border-[#dee2e6] rounded shadow-sm">
                                    <h5 class="text-base font-bold text-[#0d6efd] border-b border-[#dee2e6] pb-2 mb-3 uppercase tracking-wider">Identitas & Kontak</h5>
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                                        <div><span class="block text-xs font-bold text-[#6c757d] uppercase">ID Pengguna</span><div class="text-base text-[#212529]" x-text="selectedUser.id_pengguna || '-'"></div></div>
                                        <div><span class="block text-xs font-bold text-[#6c757d] uppercase">Nama Lengkap</span><div class="text-base text-[#212529] font-medium" x-text="selectedUser.nama_lengkap || '-'"></div></div>
                                        <div><span class="block text-xs font-bold text-[#6c757d] uppercase">Nama Toko</span><div class="text-base text-[#212529]" x-text="selectedUser.store_name || '-'"></div></div>
                                        <div><span class="block text-xs font-bold text-[#6c757d] uppercase">Email</span><div class="text-base text-[#212529]" x-text="selectedUser.email || '-'"></div></div>
                                        <div><span class="block text-xs font-bold text-[#6c757d] uppercase">No. WhatsApp</span><div class="text-base text-[#212529]" x-text="selectedUser.no_wa || '-'"></div></div>
                                        <div><span class="block text-xs font-bold text-[#6c757d] uppercase">Status / Role / Verifikasi</span>
                                            <div class="text-base text-[#212529]">
                                                <span x-text="selectedUser.status || '-'"></span> / 
                                                <span x-text="selectedUser.role || '-'"></span> / 
                                                <span x-text="selectedUser.is_verified == 1 ? 'Terverifikasi' : 'Belum'"></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- Keuangan & Bank --}}
                                <div class="bg-white p-4 border border-[#dee2e6] rounded shadow-sm">
                                    <h5 class="text-base font-bold text-[#198754] border-b border-[#dee2e6] pb-2 mb-3 uppercase tracking-wider">Keuangan & Bank</h5>
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                                        <div><span class="block text-xs font-bold text-[#6c757d] uppercase">Saldo Utama</span><div class="text-lg font-bold text-[#198754]" x-text="formatCurrency(selectedUser.saldo)"></div></div>
                                        <div><span class="block text-xs font-bold text-[#6c757d] uppercase">Saldo DANA</span><div class="text-base text-[#212529]" x-text="formatCurrency(selectedUser.dana_user_balance)"></div></div>
                                        <div><span class="block text-xs font-bold text-[#6c757d] uppercase">Saldo IAK</span><div class="text-base text-[#212529]" x-text="formatCurrency(selectedUser.balance_iak)"></div></div>
                                        <div class="mt-2"><span class="block text-xs font-bold text-[#6c757d] uppercase">Nama Bank</span><div class="text-base text-[#212529]" x-text="selectedUser.bank_name || '-'"></div></div>
                                        <div class="mt-2"><span class="block text-xs font-bold text-[#6c757d] uppercase">Atas Nama Rekening</span><div class="text-base text-[#212529]" x-text="selectedUser.bank_account_name || '-'"></div></div>
                                        <div class="mt-2"><span class="block text-xs font-bold text-[#6c757d] uppercase">Nomor Rekening</span><div class="text-base text-[#212529] font-mono" x-text="selectedUser.bank_account_number || '-'"></div></div>
                                    </div>
                                </div>

                                {{-- Alamat Lengkap --}}
                                <div class="bg-white p-4 border border-[#dee2e6] rounded shadow-sm">
                                    <h5 class="text-base font-bold text-[#dc3545] border-b border-[#dee2e6] pb-2 mb-3 uppercase tracking-wider">Alamat & Lokasi</h5>
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                                        <div class="md:col-span-3"><span class="block text-xs font-bold text-[#6c757d] uppercase">Detail Jalan / RT RW</span><div class="text-base text-[#212529]" x-text="selectedUser.address_detail || '-'"></div></div>
                                        <div><span class="block text-xs font-bold text-[#6c757d] uppercase">Desa/Kelurahan</span><div class="text-base text-[#212529]" x-text="selectedUser.village || '-'"></div></div>
                                        <div><span class="block text-xs font-bold text-[#6c757d] uppercase">Kecamatan</span><div class="text-base text-[#212529]" x-text="selectedUser.district || '-'"></div></div>
                                        <div><span class="block text-xs font-bold text-[#6c757d] uppercase">Kabupaten/Kota</span><div class="text-base text-[#212529]" x-text="selectedUser.regency || '-'"></div></div>
                                        <div><span class="block text-xs font-bold text-[#6c757d] uppercase">Provinsi</span><div class="text-base text-[#212529]" x-text="selectedUser.province || '-'"></div></div>
                                        <div><span class="block text-xs font-bold text-[#6c757d] uppercase">Kode Pos</span><div class="text-base text-[#212529]" x-text="selectedUser.postal_code || '-'"></div></div>
                                        <div><span class="block text-xs font-bold text-[#6c757d] uppercase">Koordinat (Lat, Long)</span><div class="text-base text-[#212529] font-mono"><span x-text="selectedUser.latitude || '-'"></span>, <span x-text="selectedUser.longitude || '-'"></span></div></div>
                                    </div>
                                </div>

                                {{-- Integrasi & Metadata --}}
                                <div class="bg-white p-4 border border-[#dee2e6] rounded shadow-sm">
                                    <h5 class="text-base font-bold text-[#6f42c1] border-b border-[#dee2e6] pb-2 mb-3 uppercase tracking-wider">Integrasi API & Sistem Log</h5>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                        <div><span class="block text-xs font-bold text-[#6c757d] uppercase">Expo Push Token</span><div class="text-sm font-mono bg-[#f8f9fa] border border-[#dee2e6] p-1.5 rounded break-all" x-text="selectedUser.expo_token || '-'"></div></div>
                                        <div><span class="block text-xs font-bold text-[#6c757d] uppercase">DANA Access Token</span><div class="text-sm font-mono bg-[#f8f9fa] border border-[#dee2e6] p-1.5 rounded break-all" x-text="selectedUser.dana_access_token || '-'"></div></div>
                                        <div><span class="block text-xs font-bold text-[#6c757d] uppercase">Setup Token</span><div class="text-sm font-mono bg-[#f8f9fa] border border-[#dee2e6] p-1.5 rounded break-all" x-text="selectedUser.setup_token || '-'"></div></div>
                                        <div><span class="block text-xs font-bold text-[#6c757d] uppercase">Reset Token</span><div class="text-sm font-mono bg-[#f8f9fa] border border-[#dee2e6] p-1.5 rounded break-all" x-text="selectedUser.reset_token || '-'"></div></div>
                                        
                                        <div><span class="block text-xs font-bold text-[#6c757d] uppercase">DANA Auth Code</span><div class="text-sm font-mono text-[#212529]" x-text="selectedUser.dana_auth_code || '-'"></div></div>
                                        <div><span class="block text-xs font-bold text-[#6c757d] uppercase">DANA Username</span><div class="text-sm text-[#212529]" x-text="selectedUser.dana_user_name || '-'"></div></div>
                                        
                                        <div class="md:col-span-2 border-t border-[#dee2e6] pt-3 mt-1"></div>
                                        
                                        <div><span class="block text-xs font-bold text-[#6c757d] uppercase">Created At</span><div class="text-sm text-[#212529]" x-text="selectedUser.created_at || '-'"></div></div>
                                        <div><span class="block text-xs font-bold text-[#6c757d] uppercase">Last Seen At</span><div class="text-sm text-[#212529]" x-text="selectedUser.last_seen_at || '-'"></div></div>
                                        <div><span class="block text-xs font-bold text-[#6c757d] uppercase">Token Expiry</span><div class="text-sm text-[#212529]" x-text="selectedUser.token_expiry || '-'"></div></div>
                                        <div><span class="block text-xs font-bold text-[#6c757d] uppercase">IP Address</span><div class="text-sm font-mono text-[#212529]" x-text="selectedUser.ip_address || '-'"></div></div>
                                        <div class="md:col-span-2"><span class="block text-xs font-bold text-[#6c757d] uppercase">User Agent</span><div class="text-xs text-[#6c757d] bg-[#e9ecef] p-2 rounded" x-text="selectedUser.user_agent || '-'"></div></div>
                                    </div>
                                </div>

                            </div>
                        </template>
                    </div>

                    {{-- Modal Footer --}}
                    <div class="bg-white border-t border-[#dee2e6] px-4 py-3 flex justify-end">
                        <button type="button" @click="closeModal()" class="inline-block px-4 py-2 bg-[#6c757d] text-white font-medium text-base leading-tight rounded shadow-sm hover:bg-[#5c636a] focus:ring-4 focus:ring-[#6c757d]/50 transition">
                            Tutup
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

@push('scripts')
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('customerTableData', () => ({
            isModalOpen: false,
            selectedUser: null,

            openModal(data) {
                this.selectedUser = data;
                this.isModalOpen = true;
                document.body.style.overflow = 'hidden';
            },

            closeModal() {
                this.isModalOpen = false;
                setTimeout(() => { this.selectedUser = null; }, 300);
                document.body.style.overflow = 'auto';
            },

            formatCurrency(value) {
                if (value === null || value === undefined || value === '') return 'Rp 0';
                return new Intl.NumberFormat('id-ID', {
                    style: 'currency',
                    currency: 'IDR',
                    maximumFractionDigits: 0
                }).format(value);
            }
        }));
    });
</script>
@endpush

@endsection