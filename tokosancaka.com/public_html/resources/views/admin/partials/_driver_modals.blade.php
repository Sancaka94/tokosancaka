<div class="modal fade" id="detailModal{{ $driver->id }}" tabindex="-1" aria-hidden="true">
    {{-- Tambahkan modal-lg agar modal lebih lebar (landscape) di desktop --}}
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
        <div class="modal-content border-none shadow-2xl rounded-2xl bg-white overflow-hidden">
            
            <div class="modal-header border-b border-gray-200 px-6 py-4 bg-gray-50 flex justify-between items-center">
                <h5 class="text-xl font-bold text-gray-800 m-0">Detail Pendaftaran</h5>
                <button type="button" class="btn-close text-gray-400 hover:text-gray-600 focus:outline-none" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <div class="modal-body px-6 py-5 text-left">
                
                {{-- Desain Tabel Landscape Modern --}}
                <div class="overflow-hidden border border-gray-200 rounded-xl mb-6 shadow-sm">
                    <table class="w-full text-sm text-left">
                        <tbody class="divide-y divide-gray-200">
                            <tr class="divide-x divide-gray-200 hover:bg-gray-50/50 transition-colors">
                                <td class="px-4 py-3 bg-gray-50 text-gray-500 font-semibold w-1/3 sm:w-1/4 align-top">ID Akun</td>
                                <td class="px-4 py-3 text-gray-800 font-bold bg-white align-top">{{ $driver->id_pengguna ?? 'Belum Terhubung' }}</td>
                            </tr>
                            <tr class="divide-x divide-gray-200 hover:bg-gray-50/50 transition-colors">
                                <td class="px-4 py-3 bg-gray-50 text-gray-500 font-semibold align-top">Nama Lengkap</td>
                                <td class="px-4 py-3 text-gray-800 font-bold bg-white align-top">{{ $driver->nama_lengkap }}</td>
                            </tr>
                            <tr class="divide-x divide-gray-200 hover:bg-gray-50/50 transition-colors">
                                <td class="px-4 py-3 bg-gray-50 text-gray-500 font-semibold align-top">Nomor NIK</td>
                                <td class="px-4 py-3 text-gray-800 font-bold bg-white align-top">{{ $driver->nomor_nik ?? '-' }}</td>
                            </tr>
                            <tr class="divide-x divide-gray-200 hover:bg-gray-50/50 transition-colors">
                                <td class="px-4 py-3 bg-gray-50 text-gray-500 font-semibold align-top">Nomor KK</td>
                                <td class="px-4 py-3 text-gray-800 font-bold bg-white align-top">{{ $driver->nomor_kk ?? '-' }}</td>
                            </tr>
                            <tr class="divide-x divide-gray-200 hover:bg-gray-50/50 transition-colors">
                                <td class="px-4 py-3 bg-gray-50 text-gray-500 font-semibold align-top">WhatsApp</td>
                                <td class="px-4 py-3 text-gray-800 font-bold bg-white align-top">{{ $driver->nomor_wa }}</td>
                            </tr>
                            <tr class="divide-x divide-gray-200 hover:bg-gray-50/50 transition-colors">
                                <td class="px-4 py-3 bg-gray-50 text-gray-500 font-semibold align-top">Alamat Lengkap</td>
                                <td class="px-4 py-3 text-gray-800 font-bold bg-white align-top">{{ $driver->alamat_lengkap }}</td>
                            </tr>
                            <tr class="divide-x divide-gray-200 hover:bg-gray-50/50 transition-colors">
                                <td class="px-4 py-3 bg-gray-50 text-gray-500 font-semibold align-middle">Status Peta</td>
                                <td class="px-4 py-3 bg-white align-middle">
                                    @if($driver->is_active_map == 1)
                                        <span class="inline-flex items-center px-2.5 py-1 text-xs font-bold rounded-md bg-green-100 text-green-800 border border-green-200"><i class="fa-solid fa-earth-asia mr-1.5"></i> Online di Peta</span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-1 text-xs font-bold rounded-md bg-gray-100 text-gray-600 border border-gray-200"><i class="fa-solid fa-earth-asia mr-1.5"></i> Offline</span>
                                    @endif
                                </td>
                            </tr>
                            <tr class="divide-x divide-gray-200 hover:bg-gray-50/50 transition-colors">
                                <td class="px-4 py-3 bg-gray-50 text-gray-500 font-semibold align-middle">Titik Lokasi</td>
                                <td class="px-4 py-3 bg-white align-middle">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <strong class="text-gray-800">{{ $driver->latitude ?? '-' }}, {{ $driver->longitude ?? '-' }}</strong>
                                        @if($driver->latitude && $driver->longitude)
                                            <a href="https://www.google.com/maps?q={{ $driver->latitude }},{{ $driver->longitude }}" target="_blank" 
                                               class="inline-flex items-center gap-1.5 px-3 py-1 text-xs font-bold rounded-md bg-blue-50 text-blue-600 hover:bg-blue-100 border border-blue-200 transition-colors">
                                                <i class="fa-solid fa-map-location-dot"></i> Buka Maps
                                            </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            <tr class="divide-x divide-gray-200 hover:bg-gray-50/50 transition-colors">
                                <td class="px-4 py-3 bg-gray-50 text-gray-500 font-semibold align-top">Dokumen</td>
                                <td class="px-4 py-3 bg-white align-top">
                                    <div class="flex flex-wrap gap-2">
                                        @if($driver->file_ktp) <a href="{{ asset('storage/' . $driver->file_ktp) }}" target="_blank" class="inline-flex items-center px-2.5 py-1 text-xs font-bold rounded-md bg-blue-100 text-blue-700 hover:bg-blue-200 transition-colors">KTP</a> @endif
                                        @if($driver->file_kk) <a href="{{ asset('storage/' . $driver->file_kk) }}" target="_blank" class="inline-flex items-center px-2.5 py-1 text-xs font-bold rounded-md bg-cyan-100 text-cyan-700 hover:bg-cyan-200 transition-colors">KK</a> @endif
                                        @if($driver->file_stnk) <a href="{{ asset('storage/' . $driver->file_stnk) }}" target="_blank" class="inline-flex items-center px-2.5 py-1 text-xs font-bold rounded-md bg-gray-200 text-gray-800 hover:bg-gray-300 transition-colors">STNK</a> @endif
                                        @if($driver->file_bpkb) <a href="{{ asset('storage/' . $driver->file_bpkb) }}" target="_blank" class="inline-flex items-center px-2.5 py-1 text-xs font-bold rounded-md bg-gray-800 text-white hover:bg-gray-900 transition-colors">BPKB</a> @endif
                                        @if($driver->foto_motor) <a href="{{ asset('storage/' . $driver->foto_motor) }}" target="_blank" class="inline-flex items-center px-2.5 py-1 text-xs font-bold rounded-md bg-yellow-100 text-yellow-800 hover:bg-yellow-200 transition-colors">Foto Motor</a> @endif
                                        @if($driver->foto_wajah) <a href="{{ asset('storage/' . $driver->foto_wajah) }}" target="_blank" class="inline-flex items-center px-2.5 py-1 text-xs font-bold rounded-md bg-emerald-100 text-emerald-800 hover:bg-emerald-200 transition-colors">Foto Wajah</a> @endif
                                        @if($driver->file_buku_nikah) <a href="{{ asset('storage/' . $driver->file_buku_nikah) }}" target="_blank" class="inline-flex items-center px-2.5 py-1 text-xs font-bold rounded-md bg-rose-100 text-rose-700 hover:bg-rose-200 transition-colors">Buku Nikah</a> @endif
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="flex flex-col sm:flex-row gap-3 pt-2">
                    <form action="{{ route('admin.drivers.status', $driver->id) }}" method="POST" class="w-full sm:w-1/2 m-0">
                        @csrf @method('PATCH')
                        <input type="hidden" name="status" value="approved">
                        <button type="submit" class="w-full inline-flex justify-center items-center gap-2 px-4 py-2.5 bg-green-600 hover:bg-green-700 text-white text-sm font-semibold rounded-xl shadow-sm transition-colors">
                            <i class="fa-solid fa-circle-check"></i> Setujui Pendaftaran
                        </button>
                    </form>
                    <form action="{{ route('admin.drivers.status', $driver->id) }}" method="POST" class="w-full sm:w-1/2 m-0">
                        @csrf @method('PATCH')
                        <input type="hidden" name="status" value="rejected">
                        <button type="submit" class="w-full inline-flex justify-center items-center gap-2 px-4 py-2.5 bg-white border-2 border-red-500 text-red-600 hover:bg-red-50 text-sm font-semibold rounded-xl transition-colors">
                            <i class="fa-solid fa-circle-xmark"></i> Tolak Data
                        </button>
                    </form>
                </div>

            </div>
        </div>
    </div>
</div>