{{--
  File ini adalah partial dan akan di-include ke dalam
  div x-data="dataTable(...)" di settings.blade.php.
  Jadi, semua variabel Alpine (seperti filteredUsers, sortBy) akan berfungsi.
--}}

<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
    <div>
        <label for="searchInput" class="block text-sm font-medium text-gray-700">Cari Pengguna</label>
        <input type="text" id="searchInput" x-model.debounce.300ms="searchTerm" placeholder="Cari nama, email, toko..."
               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
    </div>
    <div>
        <label for="filterRole" class="block text-sm font-medium text-gray-700">Filter Role</label>
        <select id="filterRole" x-model="filterRole" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
            <option value="all">Semua Role</option>
            <template x-for="role in availableRoles" :key="role">
                <option :value="role" x-text="role"></option>
            </template>
        </select>
    </div>
    <div>
        <label for="filterStatus" class="block text-sm font-medium text-gray-700">Filter Status</label>
        <select id="filterStatus" x-model="filterStatus" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
            <option value="all">Semua Status</option>
            <template x-for="status in availableStatuses" :key="status">
                <option :value="status" x-text="status"></option>
            </template>
        </select>
    </div>
</div>

<div class="overflow-x-auto relative shadow-md sm:rounded-lg">
    <table class="w-full text-sm text-left text-gray-500">
        <thead class="text-xs text-gray-700 uppercase bg-gray-50 sticky top-0 z-10">
            <tr>
                <th scope="col" class="px-6 py-3 cursor-pointer" @click="sortBy('id_pengguna')">
                    ID <span x-show="sortColumn === 'id_pengguna'" x-text="sortDirection === 'asc' ? '▲' : '▼'"></span>
                </th>
                <th scope="col" class="px-6 py-3 cursor-pointer" @click="sortBy('nama_lengkap')">
                    Nama Lengkap <span x-show="sortColumn === 'nama_lengkap'" x-text="sortDirection === 'asc' ? '▲' : '▼'"></span>
                </th>
                <th scope="col" class="px-6 py-3">Email</th>
                <th scope="col" class="px-6 py-3">No. WA</th>
                <th scope="col" class="px-6 py-3 cursor-pointer" @click="sortBy('store_name')">
                    Toko <span x-show="sortColumn === 'store_name'" x-text="sortDirection === 'asc' ? '▲' : '▼'"></span>
                </th>
                <th scope="col" class="px-6 py-3">Role</th>
                <th scope="col" class="px-6 py-3 cursor-pointer" @click="sortBy('saldo')">
                    Saldo <span x-show="sortColumn === 'saldo'" x-text="sortDirection === 'asc' ? '▲' : '▼'"></span>
                </th>
                <th scope="col" class="px-6 py-3">Status</th>
                <th scope="col" class="px-6 py-3">Terverifikasi</th>
                <th scope="col" class="px-6 py-3 cursor-pointer" @click="sortBy('last_seen_at')">
                    Terakhir Dilihat <span x-show="sortColumn === 'last_seen_at'" x-text="sortDirection === 'asc' ? '▲' : '▼'"></span>
                </th>
                <th scope="col" class="px-6 py-3 cursor-pointer" @click="sortBy('created_at')">
                    Bergabung <span x-show="sortColumn === 'created_at'" x-text="sortDirection === 'asc' ? '▲' : '▼'"></span>
                </th>
                <th scope="col" class="px-6 py-3">Logo</th>
                <th scope="col" class="px-6 py-3 sticky right-0 bg-gray-50 z-20 shadow-[-2px_0_3px_rgba(0,0,0,0.1)]">
                    Aksi
                </th>
            </tr>
        </thead>
        <tbody>
            {{-- Pesan jika data kosong --}}
            <template x-if="filteredUsers.length === 0">
                <tr>
                    <td colspan="13" class="px-6 py-4 text-center">
                        Tidak ada data yang cocok dengan pencarian atau filter.
                    </td>
                </tr>
            </template>
            {{-- Looping data pengguna --}}
            <template x-for="user in filteredUsers" :key="user.id_pengguna">
                <tr class="bg-white border-b hover:bg-gray-50">
                    <td class="px-6 py-4" x-text="user.id_pengguna"></td>
                    <td class="px-6 py-4 font-medium text-gray-900" x-text="user.nama_lengkap"></td>
                    <td class="px-6 py-4" x-text="user.email || '-'"></td>
                    <td class="px-6 py-4" x-text="user.no_wa || '-'"></td>
                    <td class="px-6 py-4" x-text="user.store_name || '-'"></td>
                    <td class="px-6 py-4">
                        <span x-text="user.role"
                              :class="{
                                  'bg-blue-100 text-blue-800': user.role === 'Admin',
                                  'bg-green-100 text-green-800': user.role === 'Seller',
                                  'bg-gray-100 text-gray-800': user.role === 'Pelanggan',
                                  'bg-yellow-100 text-yellow-800': user.role !== 'Admin' && user.role !== 'Seller' && user.role !== 'Pelanggan'
                              }"
                              class="text-xs font-medium mr-2 px-2.5 py-0.5 rounded-full">
                        </span>
                    </td>
                    <td class="px-6 py-4" x-text="formatCurrency(user.saldo)"></td>
                    <td class="px-6 py-4">
                        <span x-text="user.status"
                              :class="{
                                  'bg-green-100 text-green-800': user.status === 'Aktif',
                                  'bg-red-100 text-red-800': user.status !== 'Aktif',
                              }"
                              class="text-xs font-medium mr-2 px-2.5 py-0.5 rounded-full">
                        </span>
                    </td>
                    <td class="px-6 py-4">
                        <span x-text="user.is_verified ? 'Ya' : 'Tidak'"
                              :class="user.is_verified ? 'text-green-600 font-medium' : 'text-red-600'">
                        </span>
                    </td>
                    <td class="px-6 py-4" x-text="user.last_seen_at ? new Date(user.last_seen_at).toLocaleString('id-ID', { dateStyle: 'short', timeStyle: 'short' }) : '-'"></td>
                    <td class="px-6 py-4" x-text="user.created_at ? new Date(user.created_at).toLocaleDateString('id-ID', { dateStyle: 'short' }) : '-'"></td>
                    <td class="px-6 py-4">
                        {{-- [FIX] Menggunakan asset() dan disk 'public' --}}
                        <img :src="user.store_logo_path 
                                                ? '{{ asset('public/storage') }}/' + user.store_logo_path 
                                                : 'https://ui-avatars.com/api/?name=' + encodeURIComponent(user.nama_lengkap || 'User') + '&color=7F9CF5&background=EBF4FF'"
                                                 alt="Logo" class="w-10 h-10 rounded-full object-cover">
                    </td>
                    <td class="px-6 py-4 sticky right-0 bg-white z-10 shadow-[-2px_0_3px_rgba(0,0,0,0.05)]">
                        {{-- Kolom Aksi Sticky --}}
                        <div class="flex space-x-2">
                            <button @click="window.location.href=`{{ url('admin/users') }}/${user.id_pengguna}`"
                                    title="Lihat" class="text-blue-600 hover:text-blue-900">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M10 12a2 2 0 100-4 2 2 0 000 4z" />
                                    <path fill-rule="evenodd" d="M.458 10C3.18 4.292 8.98 1 10 1c1.02 0 6.82 3.292 9.542 9-.06.12-.119.238-.178.356a.97.97 0 01-1.638-.707C16.208 6.017 12.071 4 10 4 7.929 4 3.792 6.017 2.274 8.649a.97.97 0 01-1.638.707.03.03 0 01-.178-.356z" clip-rule="evenodd" />
                                </svg>
                            </button>
                            <button @click="window.location.href=`{{ url('admin/users') }}/${user.id_pengguna}/edit`"
                                    title="Edit" class="text-indigo-600 hover:text-indigo-900">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                            </button>
                            <button @click="confirmDelete(user.id_pengguna, user.nama_lengkap)"
                                    title="Hapus" class="text-red-600 hover:text-red-900">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                            </button>
                        </div>
                    </td>
                </tr>
            </template>
        </tbody>
    </table>
</div>
