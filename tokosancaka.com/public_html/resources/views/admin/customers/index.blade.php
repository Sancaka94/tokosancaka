@extends('layouts.admin')

@section('title', 'Manajemen Pelanggan & Pendaftaran')

@section('content')
{{-- Style tambahan untuk mencegah flicker Alpine.js sebelum script dimuat --}}
<style>
    [x-cloak] { display: none !important; }
</style>

{{-- Wrapper utama dengan state Alpine.js --}}
<div class="space-y-8" x-data="customerManagement()">
    
    {{-- Header Halaman --}}
    <div>
        <h1 class="text-2xl md:text-3xl font-bold text-gray-900">Manajemen Pelanggan & Pendaftaran</h1>
        <p class="mt-1 text-sm text-gray-600">Setujui permintaan baru dan kelola pelanggan yang sudah terdaftar di satu tempat.</p>
    </div>

    {{-- Notifikasi --}}
    @if(session('success') || session('error'))
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)"
             x-transition:leave="transition ease-in duration-300"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="flex items-center p-4 mb-4 text-sm rounded-lg border {{ session('success') ? 'bg-green-50 text-green-800 border-green-200' : 'bg-red-50 text-red-800 border-red-200' }}" role="alert">
            
            @if(session('success'))
                <i class="fa-solid fa-check-circle w-5 h-5"></i>
            @else
                <i class="fa-solid fa-exclamation-triangle w-5 h-5"></i>
            @endif

            <div class="ml-3 font-medium flex items-center">
                {{ session('success') ?? session('error') }}
                @if(session('success') && session('whatsapp_url'))
                    <a href="{{ session('whatsapp_url') }}" target="_blank" class="ml-3 inline-flex items-center px-3 py-1.5 text-xs font-medium text-center text-white bg-green-600 rounded-lg hover:bg-green-700 focus:ring-4 focus:outline-none focus:ring-green-300 transition-colors">
                        <i class="fa-brands fa-whatsapp mr-2"></i> Kirim Link Setup
                    </a>
                @endif
            </div>
            <button @click="show = false" type="button" class="ml-auto -mx-1.5 -my-1.5 rounded-lg focus:ring-2 p-1.5 inline-flex items-center justify-center h-8 w-8 {{ session('success') ? 'bg-green-50 text-green-500 hover:bg-green-200 focus:ring-green-400' : 'bg-red-50 text-red-500 hover:bg-red-200 focus:ring-red-400' }}" aria-label="Close">
                <i class="fa-solid fa-times"></i>
            </button>
        </div>
    @endif
    
    {{-- Bagian Tabel Permintaan Pending --}}
    <div class="space-y-4">
        <h2 class="text-xl font-semibold text-gray-800">Daftar Permintaan Pending</h2>
        <div class="overflow-x-auto relative shadow-md sm:rounded-lg border border-gray-200">
            <table class="w-full text-sm text-left text-gray-500">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th scope="col" class="py-3 px-6">Tanggal</th>
                        <th scope="col" class="py-3 px-6">Nama</th>
                        <th scope="col" class="py-3 px-6">Email</th>
                        <th scope="col" class="py-3 px-6">No. WA</th>
                        <th scope="col" class="py-3 px-6">Nama Toko</th>
                        <th scope="col" class="py-3 px-6 sticky right-0 bg-gray-50 shadow-[-2px_0_4px_rgba(0,0,0,0.05)]">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($requests as $request)
                        <tr class="bg-white border-b hover:bg-gray-50 transition-colors">
                            <td class="py-4 px-6">{{ $request->created_at ? \Carbon\Carbon::parse($request->created_at)->translatedFormat('d M Y, H:i') : '-' }}</td>
                            <td class="py-4 px-6 font-medium text-gray-900">{{ $request->nama_lengkap }}</td>
                            <td class="py-4 px-6">{{ $request->email }}</td>
                            <td class="py-4 px-6">{{ $request->no_wa }}</td>
                            <td class="py-4 px-6">{{ $request->store_name ?? '-' }}</td>
                            <td class="py-4 px-6 sticky right-0 bg-white shadow-[-2px_0_4px_rgba(0,0,0,0.05)]">
                                <div class="flex items-center space-x-4">
                                    {{-- Tombol Mata (Lihat Detail) --}}
                                    <button type="button" @click="openModal($event.currentTarget.dataset.user)" data-user="{{ json_encode($request) }}" title="Lihat Detail" class="text-blue-600 hover:text-blue-900 transition-colors">
                                        <i class="fa-solid fa-eye fa-lg"></i>
                                    </button>
                                    {{-- Tombol Setujui --}}
                                    <form action="{{ route('admin.registrations.approve', $request->id_pengguna) }}" method="POST" onsubmit="return confirm('Anda yakin ingin menyetujui pendaftar ini?');">
                                        @csrf
                                        <button type="submit" title="Setujui" class="text-green-600 hover:text-green-900 transition-colors">
                                            <i class="fa-solid fa-check fa-lg"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-6 px-6 text-center text-gray-500 bg-white">
                                Tidak ada permintaan pendaftaran baru.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Bagian Tabel Pengguna Terdaftar --}}
    <div class="space-y-4">
        <h2 class="text-xl font-semibold text-gray-800">Daftar Pelanggan Terdaftar</h2>
        <div class="overflow-x-auto relative shadow-md sm:rounded-lg border border-gray-200">
            <table class="w-full text-sm text-left text-gray-500">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th scope="col" class="py-3 px-6 whitespace-nowrap">ID / Nama</th>
                        <th scope="col" class="py-3 px-6">Kontak (Email & WA)</th>
                        <th scope="col" class="py-3 px-6">Toko / Perusahaan</th>
                        <th scope="col" class="py-3 px-6">Role</th>
                        <th scope="col" class="py-3 px-6">Saldo</th>
                        <th scope="col" class="py-3 px-6 text-center">Status</th>
                        <th scope="col" class="py-3 px-6 sticky right-0 bg-gray-50 shadow-[-2px_0_4px_rgba(0,0,0,0.05)] text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($customers as $user)
                        <tr class="bg-white border-b hover:bg-gray-50 transition-colors">
                            <td class="py-4 px-6">
                                <span class="text-xs text-gray-400 block mb-1">#{{ $user->id_pengguna }}</span>
                                <span class="font-medium text-gray-900">{{ $user->nama_lengkap }}</span>
                            </td>
                            <td class="py-4 px-6">
                                <div class="text-gray-900">{{ $user->email }}</div>
                                <div class="text-xs text-gray-500 mt-1"><i class="fa-brands fa-whatsapp text-green-500 mr-1"></i>{{ $user->no_wa }}</div>
                            </td>
                            <td class="py-4 px-6">{{ $user->store_name ?? '-' }}</td>
                            <td class="py-4 px-6">
                                <span class="px-2.5 py-1 text-xs font-semibold rounded-md 
                                    {{ $user->role == 'Admin' ? 'bg-purple-100 text-purple-800' : ($user->role == 'Seller' ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800') }}">
                                    {{ $user->role }}
                                </span>
                            </td>
                            <td class="py-4 px-6 font-medium text-gray-900 whitespace-nowrap">
                                Rp {{ number_format($user->saldo ?? 0, 0, ',', '.') }}
                            </td>
                            <td class="py-4 px-6 text-center">
                                <div class="flex flex-col items-center space-y-1.5">
                                    @if($user->status == 'Aktif')
                                        <span class="px-2 py-0.5 text-[10px] font-semibold rounded-full bg-green-100 text-green-800 border border-green-200">Aktif</span>
                                    @else
                                        <span class="px-2 py-0.5 text-[10px] font-semibold rounded-full bg-red-100 text-red-800 border border-red-200">{{ $user->status ?? 'Nonaktif' }}</span>
                                    @endif

                                    @if($user->is_verified)
                                        <i class="fa-solid fa-circle-check text-blue-500" title="Terverifikasi"></i>
                                    @else
                                        <i class="fa-solid fa-circle-exclamation text-yellow-500" title="Belum Verifikasi"></i>
                                    @endif
                                </div>
                            </td>
                            <td class="py-4 px-6 sticky right-0 bg-white shadow-[-2px_0_4px_rgba(0,0,0,0.05)]">
                                <div class="flex items-center justify-center space-x-4">
                                    {{-- Tombol Mata (Lihat Detail Modal) --}}
                                    <button type="button" @click="openModal($event.currentTarget.dataset.user)" data-user="{{ json_encode($user) }}" title="Lihat Detail Lengkap" class="text-blue-600 hover:text-blue-900 transition-colors">
                                        <i class="fa-solid fa-eye fa-lg"></i>
                                    </button>

                                    <a href="{{ route('admin.customers.edit', $user->id_pengguna) }}" title="Edit" class="text-indigo-600 hover:text-indigo-900 transition-colors">
                                        <i class="fa-solid fa-pencil fa-lg"></i>
                                    </a>
                                    
                                    <form action="{{ route('admin.customers.send-setup-link', $user->id_pengguna) }}" method="POST">
                                        @csrf
                                        <button type="submit" title="Kirim Link Setup" class="text-teal-600 hover:text-teal-900 transition-colors">
                                            <i class="fa-solid fa-paper-plane fa-lg"></i>
                                        </button>
                                    </form>
                                    
                                    <form action="{{ route('admin.customers.destroy', $user->id_pengguna) }}" method="POST" onsubmit="return confirm('PERINGATAN: Menghapus pengguna tidak dapat diurungkan. Anda yakin?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" title="Hapus" class="text-red-600 hover:text-red-900 transition-colors">
                                            <i class="fa-solid fa-trash-can fa-lg"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-6 px-6 text-center text-gray-500 bg-white">
                                Belum ada pelanggan yang terdaftar.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        <div class="mt-4">
            {{ $customers->links() }}
        </div>
    </div>

    {{-- ================= MODAL DETAIL PENGGUNA LENGKAP ================= --}}
    <div x-show="showModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            
            {{-- Background overlay --}}
            <div x-show="showModal" 
                 x-transition:enter="ease-out duration-300" 
                 x-transition:enter-start="opacity-0" 
                 x-transition:enter-end="opacity-100" 
                 x-transition:leave="ease-in duration-200" 
                 x-transition:leave-start="opacity-100" 
                 x-transition:leave-end="opacity-0" 
                 class="fixed inset-0 bg-gray-900 bg-opacity-75 transition-opacity" 
                 @click="closeModal()" aria-hidden="true"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            {{-- Modal Panel --}}
            <div x-show="showModal" 
                 x-transition:enter="ease-out duration-300" 
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" 
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" 
                 x-transition:leave="ease-in duration-200" 
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" 
                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" 
                 class="inline-block align-bottom bg-white rounded-xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl w-full">
                
                {{-- Header Modal --}}
                <div class="bg-gray-50 px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                    <h3 class="text-xl leading-6 font-bold text-gray-900 flex items-center" id="modal-title">
                        <i class="fa-solid fa-address-card text-blue-600 mr-3"></i> Detail Lengkap Pengguna
                    </h3>
                    <button type="button" @click="closeModal()" class="bg-white rounded-md text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        <span class="sr-only">Close</span>
                        <i class="fa-solid fa-xmark text-xl"></i>
                    </button>
                </div>

                {{-- Body Modal --}}
                <div class="px-6 py-5 max-h-[75vh] overflow-y-auto">
                    <template x-if="user">
                        <div class="space-y-8">
                            
                            {{-- Section: Info Dasar & Kontak --}}
                            <div>
                                <h4 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-4 border-b pb-2">Informasi Dasar & Kontak</h4>
                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-y-4 gap-x-6">
                                    <div><span class="block text-xs text-gray-500 mb-1">ID Pengguna</span><p class="text-sm font-medium text-gray-900" x-text="'#' + (user.id_pengguna || '-')"></p></div>
                                    <div><span class="block text-xs text-gray-500 mb-1">Nama Lengkap</span><p class="text-sm font-medium text-gray-900" x-text="user.nama_lengkap || '-'"></p></div>
                                    <div><span class="block text-xs text-gray-500 mb-1">Email</span><p class="text-sm font-medium text-gray-900" x-text="user.email || '-'"></p></div>
                                    <div><span class="block text-xs text-gray-500 mb-1">Nomor WhatsApp</span><p class="text-sm font-medium text-gray-900" x-text="user.no_wa || '-'"></p></div>
                                    <div><span class="block text-xs text-gray-500 mb-1">Nama Toko</span><p class="text-sm font-medium text-gray-900" x-text="user.store_name || '-'"></p></div>
                                    <div><span class="block text-xs text-gray-500 mb-1">Status Akun / Role</span>
                                        <p class="text-sm font-medium text-gray-900">
                                            <span x-text="user.status || '-'"></span> / <span x-text="user.role || '-'"></span>
                                        </p>
                                    </div>
                                </div>
                            </div>

                            {{-- Section: Alamat & Lokasi --}}
                            <div>
                                <h4 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-4 border-b pb-2">Alamat & Lokasi Geografis</h4>
                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-y-4 gap-x-6">
                                    <div class="sm:col-span-3">
                                        <span class="block text-xs text-gray-500 mb-1">Detail Alamat</span>
                                        <p class="text-sm font-medium text-gray-900" x-text="user.address_detail || 'Data kosong'"></p>
                                    </div>
                                    <div><span class="block text-xs text-gray-500 mb-1">Provinsi</span><p class="text-sm font-medium text-gray-900" x-text="user.province || '-'"></p></div>
                                    <div><span class="block text-xs text-gray-500 mb-1">Kabupaten/Kota</span><p class="text-sm font-medium text-gray-900" x-text="user.regency || '-'"></p></div>
                                    <div><span class="block text-xs text-gray-500 mb-1">Kecamatan</span><p class="text-sm font-medium text-gray-900" x-text="user.district || '-'"></p></div>
                                    <div><span class="block text-xs text-gray-500 mb-1">Desa/Kelurahan</span><p class="text-sm font-medium text-gray-900" x-text="user.village || '-'"></p></div>
                                    <div><span class="block text-xs text-gray-500 mb-1">Kode Pos</span><p class="text-sm font-medium text-gray-900" x-text="user.postal_code || '-'"></p></div>
                                    <div><span class="block text-xs text-gray-500 mb-1">Koordinat Peta (Lat, Long)</span>
                                        <p class="text-sm font-medium text-gray-900 font-mono">
                                            <span x-text="user.latitude || '-'"></span>, <span x-text="user.longitude || '-'"></span>
                                        </p>
                                    </div>
                                </div>
                            </div>

                            {{-- Section: Keuangan & Bank --}}
                            <div>
                                <h4 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-4 border-b pb-2">Keuangan & Data Bank</h4>
                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-y-4 gap-x-6">
                                    <div><span class="block text-xs text-gray-500 mb-1">Saldo Utama</span><p class="text-sm font-bold text-blue-600" x-text="formatCurrency(user.saldo)"></p></div>
                                    <div><span class="block text-xs text-gray-500 mb-1">Saldo DANA</span><p class="text-sm font-medium text-gray-900" x-text="formatCurrency(user.dana_user_balance)"></p></div>
                                    <div><span class="block text-xs text-gray-500 mb-1">Saldo IAK</span><p class="text-sm font-medium text-gray-900" x-text="formatCurrency(user.balance_iak)"></p></div>
                                    
                                    <div><span class="block text-xs text-gray-500 mb-1">Nama Bank</span><p class="text-sm font-medium text-gray-900" x-text="user.bank_name || '-'"></p></div>
                                    <div><span class="block text-xs text-gray-500 mb-1">Atas Nama Rekening</span><p class="text-sm font-medium text-gray-900" x-text="user.bank_account_name || '-'"></p></div>
                                    <div><span class="block text-xs text-gray-500 mb-1">Nomor Rekening</span><p class="text-sm font-medium text-gray-900 font-mono" x-text="user.bank_account_number || '-'"></p></div>
                                </div>
                            </div>

                            {{-- Section: Token & Keamanan --}}
                            <div>
                                <h4 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-4 border-b pb-2">API Tokens & Keamanan</h4>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-y-4 gap-x-6">
                                    <div><span class="block text-xs text-gray-500 mb-1">Expo Push Token</span>
                                        <p class="text-xs p-1.5 bg-gray-100 rounded border border-gray-200 text-gray-800 break-all" x-text="user.expo_token || 'Tidak tersedia'"></p>
                                    </div>
                                    <div><span class="block text-xs text-gray-500 mb-1">DANA Access Token</span>
                                        <p class="text-xs p-1.5 bg-gray-100 rounded border border-gray-200 text-gray-800 break-all" x-text="user.dana_access_token || 'Tidak tersedia'"></p>
                                    </div>
                                    <div><span class="block text-xs text-gray-500 mb-1">Setup Token</span>
                                        <p class="text-xs p-1.5 bg-gray-100 rounded border border-gray-200 text-gray-800 break-all" x-text="user.setup_token || 'Tidak tersedia'"></p>
                                    </div>
                                    <div><span class="block text-xs text-gray-500 mb-1">Token Expiry</span>
                                        <p class="text-sm font-medium text-gray-900" x-text="formatDate(user.token_expiry)"></p>
                                    </div>
                                </div>
                            </div>

                            {{-- Section: Log & Metadata --}}
                            <div>
                                <h4 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-4 border-b pb-2">Log Sistem & Metadata</h4>
                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-y-4 gap-x-6">
                                    <div><span class="block text-xs text-gray-500 mb-1">Tanggal Bergabung</span><p class="text-sm font-medium text-gray-900" x-text="formatDate(user.created_at)"></p></div>
                                    <div><span class="block text-xs text-gray-500 mb-1">Terakhir Aktif (Last Seen)</span><p class="text-sm font-medium text-gray-900" x-text="formatDate(user.last_seen_at)"></p></div>
                                    <div><span class="block text-xs text-gray-500 mb-1">IP Address Terakhir</span><p class="text-sm font-medium text-gray-900 font-mono" x-text="user.ip_address || '-'"></p></div>
                                    <div class="sm:col-span-3">
                                        <span class="block text-xs text-gray-500 mb-1">User Agent Log</span>
                                        <p class="text-xs text-gray-600 bg-gray-50 p-2 rounded border border-gray-200" x-text="user.user_agent || '-'"></p>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </template>
                </div>

                {{-- Footer Modal --}}
                <div class="bg-gray-50 px-6 py-4 border-t border-gray-200 flex justify-end">
                    <button type="button" @click="closeModal()" class="inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:text-sm transition-colors">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    </div>

</div>

@push('scripts')
{{-- KEMBALIKAN KE FORMAT ASLI: Define fungsi langsung di window agar tidak bentrok dengan sistem --}}
<script>
    window.addressFinder = function(searchUrl, geocodeUrl) {
        return {
            fields: {
                province: @json(old('province', $user->province)),
                city: @json(old('regency', $user->regency)), 
                subdistrict: @json(old('district', $user->district)), 
                village: @json(old('village', $user->village)),
                zip_code: @json(old('postal_code', $user->postal_code)),
                address_detail: @json(old('address_detail', $user->address_detail)),
                latitude: @json(old('latitude', $user->latitude)),
                longitude: @json(old('longitude', $user->longitude)),
            },
            searchQuery: '',
            results: [],
            loading: false,
            message: '',
            geocoding: false,
            geocodeMessage: '',
            
            async search() {
                if (this.searchQuery.length < 3) {
                    this.results = [];
                    this.message = '';
                    return;
                }
                
                this.loading = true;
                this.message = '';
                
                try {
                    const response = await fetch(`${searchUrl}?query=${encodeURIComponent(this.searchQuery)}`, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        }
                    });
                    
                    const data = await response.json();
                    
                    if (data.success && data.data.length > 0) {
                        this.results = data.data;
                    } else {
                        this.results = [];
                        this.message = data.message || 'Alamat tidak ditemukan.';
                    }
                } catch (error) {
                    console.error('Error searching address:', error);
                    this.message = 'Gagal terhubung ke server pencari alamat.';
                } finally {
                    this.loading = false;
                }
            },
            
            selectAddress(result) {
                this.fields.province = result.province;
                this.fields.city = result.city;
                this.fields.subdistrict = result.subdistrict;
                this.fields.village = result.village;
                this.fields.zip_code = result.zip_code;
                
                this.searchQuery = '';
                this.results = [];
                
                this.$nextTick(() => {
                    document.getElementById('address_detail').focus();
                });
            },

            async getCoords() {
                this.geocoding = true;
                this.geocodeMessage = 'Mencari koordinat...';
                
                const fullAddress = [
                    this.fields.address_detail,
                    this.fields.village,
                    this.fields.subdistrict,
                    this.fields.city,
                    this.fields.province,
                    this.fields.zip_code
                ].filter(Boolean).join(', ');

                if (fullAddress.length < 10) {
                    this.geocodeMessage = 'Harap isi detail alamat lebih lengkap.';
                    this.geocoding = false;
                    return;
                }

                try {
                    const response = await fetch(`${geocodeUrl}?address=${encodeURIComponent(fullAddress)}`, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        }
                    });
                    const data = await response.json();
                    
                    if (data.success && data.data.lat) {
                        this.fields.latitude = data.data.lat;
                        this.fields.longitude = data.data.lng;
                        this.geocodeMessage = `Berhasil! Lat: ${data.data.lat}, Lng: ${data.data.lng}`;
                    } else {
                        this.geocodeMessage = 'Koordinat tidak ditemukan untuk alamat ini.';
                    }
                } catch (error) {
                    console.error('Error geocoding:', error);
                    this.geocodeMessage = 'Gagal terhubung ke server geocoding.';
                } finally {
                    this.geocoding = false;
                }
            }
        };
    }
</script>
<style>
    [x-cloak] { display: none !important; }
</style>
@endpush


@endsection