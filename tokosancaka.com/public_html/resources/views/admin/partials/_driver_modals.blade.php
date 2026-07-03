<div class="modal fade" id="detailModal{{ $driver->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-none shadow-2xl rounded-2xl bg-white overflow-hidden">
            
            <div class="modal-header border-none px-6 pt-6 pb-2 flex justify-between items-start">
                <h5 class="text-xl font-bold text-gray-800">Detail Pendaftaran</h5>
                <button type="button" class="btn-close text-gray-400 hover:text-gray-600 focus:outline-none" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <div class="modal-body px-6 py-4 text-left text-sm text-gray-600">
                <ul class="divide-y divide-gray-100 mb-6">
                    <li class="py-3 flex flex-col sm:flex-row sm:justify-between sm:items-center">
                        <span class="text-gray-500">ID Akun Pengguna:</span>
                        <strong class="text-gray-800 mt-1 sm:mt-0">{{ $driver->id_pengguna ?? 'Belum Terhubung' }}</strong>
                    </li>
                    <li class="py-3 flex flex-col sm:flex-row sm:justify-between sm:items-center">
                        <span class="text-gray-500">Nama:</span>
                        <strong class="text-gray-800 mt-1 sm:mt-0">{{ $driver->nama_lengkap }}</strong>
                    </li>
                    <li class="py-3 flex flex-col sm:flex-row sm:justify-between sm:items-center">
                        <span class="text-gray-500">NIK:</span>
                        <strong class="text-gray-800 mt-1 sm:mt-0">{{ $driver->nomor_nik ?? '-' }}</strong>
                    </li>
                    <li class="py-3 flex flex-col sm:flex-row sm:justify-between sm:items-center">
                        <span class="text-gray-500">KK:</span>
                        <strong class="text-gray-800 mt-1 sm:mt-0">{{ $driver->nomor_kk ?? '-' }}</strong>
                    </li>
                    <li class="py-3 flex flex-col sm:flex-row sm:justify-between sm:items-center">
                        <span class="text-gray-500">WhatsApp:</span>
                        <strong class="text-gray-800 mt-1 sm:mt-0">{{ $driver->nomor_wa }}</strong>
                    </li>
                    <li class="py-3 flex flex-col sm:flex-row sm:justify-between sm:items-start">
                        <span class="text-gray-500 w-24 flex-shrink-0">Alamat:</span>
                        <strong class="text-gray-800 mt-1 sm:mt-0 sm:text-right">{{ $driver->alamat_lengkap }}</strong>
                    </li>
                    <li class="py-3 flex flex-col sm:flex-row sm:justify-between sm:items-center">
                        <span class="text-gray-500">Status Peta:</span>
                        <div class="mt-1 sm:mt-0">
                            @if($driver->is_active_map == 1)
                                <span class="px-2.5 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Online di Peta</span>
                            @else
                                <span class="px-2.5 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-600">Offline</span>
                            @endif
                        </div>
                    </li>
                    <li class="py-3 flex flex-col sm:flex-row sm:justify-between sm:items-center">
                        <span class="text-gray-500">Titik Lokasi:</span>
                        <div class="flex items-center gap-3 mt-1 sm:mt-0">
                            <strong class="text-gray-800">{{ $driver->latitude ?? '-' }}, {{ $driver->longitude ?? '-' }}</strong>
                            @if($driver->latitude && $driver->longitude)
                                <a href="https://www.google.com/maps?q={{ $driver->latitude }},{{ $driver->longitude }}" target="_blank" 
                                   class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-lg bg-blue-50 text-blue-600 hover:bg-blue-100 transition-colors">
                                    <i class="fa-solid fa-location-dot"></i> Cek Maps
                                </a>
                            @endif
                        </div>
                    </li>
                </ul>

                <div>
                    <span class="block text-gray-800 font-bold mb-3">Dokumen Lampiran:</span>
                    <div class="flex flex-wrap gap-2">
                        @if($driver->file_ktp) <a href="{{ asset('storage/' . $driver->file_ktp) }}" target="_blank" class="inline-flex items-center px-3 py-1.5 text-xs font-semibold rounded-lg bg-blue-100 text-blue-700 hover:bg-blue-200 transition-colors">KTP</a> @endif
                        @if($driver->file_kk) <a href="{{ asset('storage/' . $driver->file_kk) }}" target="_blank" class="inline-flex items-center px-3 py-1.5 text-xs font-semibold rounded-lg bg-cyan-100 text-cyan-700 hover:bg-cyan-200 transition-colors">KK</a> @endif
                        @if($driver->file_stnk) <a href="{{ asset('storage/' . $driver->file_stnk) }}" target="_blank" class="inline-flex items-center px-3 py-1.5 text-xs font-semibold rounded-lg bg-gray-100 text-gray-700 hover:bg-gray-200 transition-colors">STNK</a> @endif
                        @if($driver->file_bpkb) <a href="{{ asset('storage/' . $driver->file_bpkb) }}" target="_blank" class="inline-flex items-center px-3 py-1.5 text-xs font-semibold rounded-lg bg-gray-800 text-white hover:bg-gray-900 transition-colors">BPKB</a> @endif
                        @if($driver->foto_motor) <a href="{{ asset('storage/' . $driver->foto_motor) }}" target="_blank" class="inline-flex items-center px-3 py-1.5 text-xs font-semibold rounded-lg bg-yellow-100 text-yellow-800 hover:bg-yellow-200 transition-colors">Foto Motor</a> @endif
                        @if($driver->foto_wajah) <a href="{{ asset('storage/' . $driver->foto_wajah) }}" target="_blank" class="inline-flex items-center px-3 py-1.5 text-xs font-semibold rounded-lg bg-emerald-100 text-emerald-800 hover:bg-emerald-200 transition-colors">Foto Wajah</a> @endif
                        @if($driver->file_buku_nikah) <a href="{{ asset('storage/' . $driver->file_buku_nikah) }}" target="_blank" class="inline-flex items-center px-3 py-1.5 text-xs font-semibold rounded-lg bg-rose-100 text-rose-700 hover:bg-rose-200 transition-colors">Buku Nikah</a> @endif
                    </div>
                </div>

                <div class="flex flex-col sm:flex-row gap-3 mt-8">
                    <form action="{{ route('admin.drivers.status', $driver->id) }}" method="POST" class="w-full sm:w-1/2 m-0">
                        @csrf @method('PATCH')
                        <input type="hidden" name="status" value="approved">
                        <button type="submit" class="w-full inline-flex justify-center items-center gap-2 px-4 py-2.5 bg-green-600 hover:bg-green-700 text-white text-sm font-semibold rounded-xl shadow-sm transition-colors">
                            <i class="fa-solid fa-circle-check"></i> Setujui
                        </button>
                    </form>
                    <form action="{{ route('admin.drivers.status', $driver->id) }}" method="POST" class="w-full sm:w-1/2 m-0">
                        @csrf @method('PATCH')
                        <input type="hidden" name="status" value="rejected">
                        <button type="submit" class="w-full inline-flex justify-center items-center gap-2 px-4 py-2.5 bg-white border-2 border-red-500 text-red-600 hover:bg-red-50 text-sm font-semibold rounded-xl transition-colors">
                            <i class="fa-solid fa-circle-xmark"></i> Tolak
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="editModal{{ $driver->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-none shadow-2xl rounded-2xl bg-white overflow-hidden">
            
            <div class="modal-header border-none px-6 py-5 flex justify-between items-center bg-gray-50">
                <h5 class="text-lg font-bold text-gray-800">Edit Data Driver</h5>
                <button type="button" class="btn-close text-gray-400 hover:text-gray-600 focus:outline-none" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <form action="{{ route('admin.drivers.update', $driver->id) }}" method="POST">
                @csrf @method('PUT')
                
                <div class="modal-body px-6 py-5 text-left">
                    <div class="mb-4">
                        <label class="block mb-2 text-sm font-semibold text-gray-600">Nama Lengkap</label>
                        <input type="text" name="nama_lengkap" class="w-full bg-gray-50 border border-gray-200 text-gray-800 text-sm rounded-xl focus:ring-blue-500 focus:border-blue-500 block px-4 py-3 transition-colors" value="{{ $driver->nama_lengkap }}" required>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block mb-2 text-sm font-semibold text-gray-600">NIK</label>
                            <input type="text" name="nomor_nik" class="w-full bg-gray-50 border border-gray-200 text-gray-800 text-sm rounded-xl focus:ring-blue-500 focus:border-blue-500 block px-4 py-3 transition-colors" value="{{ $driver->nomor_nik }}" required>
                        </div>
                        <div>
                            <label class="block mb-2 text-sm font-semibold text-gray-600">KK</label>
                            <input type="text" name="nomor_kk" class="w-full bg-gray-50 border border-gray-200 text-gray-800 text-sm rounded-xl focus:ring-blue-500 focus:border-blue-500 block px-4 py-3 transition-colors" value="{{ $driver->nomor_kk }}" required>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label class="block mb-2 text-sm font-semibold text-gray-600">Nomor WhatsApp</label>
                        <input type="text" name="nomor_wa" class="w-full bg-gray-50 border border-gray-200 text-gray-800 text-sm rounded-xl focus:ring-blue-500 focus:border-blue-500 block px-4 py-3 transition-colors" value="{{ $driver->nomor_wa }}" required>
                    </div>
                    
                    <div class="mb-4">
                        <label class="block mb-2 text-sm font-semibold text-gray-600">Alamat Lengkap</label>
                        <textarea name="alamat_lengkap" class="w-full bg-gray-50 border border-gray-200 text-gray-800 text-sm rounded-xl focus:ring-blue-500 focus:border-blue-500 block px-4 py-3 transition-colors" rows="3" required>{{ $driver->alamat_lengkap }}</textarea>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block mb-2 text-sm font-semibold text-gray-600">Latitude</label>
                            <input type="text" name="latitude" class="w-full bg-gray-50 border border-gray-200 text-gray-800 text-sm rounded-xl focus:ring-blue-500 focus:border-blue-500 block px-4 py-3 transition-colors" value="{{ $driver->latitude }}">
                        </div>
                        <div>
                            <label class="block mb-2 text-sm font-semibold text-gray-600">Longitude</label>
                            <input type="text" name="longitude" class="w-full bg-gray-50 border border-gray-200 text-gray-800 text-sm rounded-xl focus:ring-blue-500 focus:border-blue-500 block px-4 py-3 transition-colors" value="{{ $driver->longitude }}">
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer border-none px-6 pb-6 pt-2 flex justify-end gap-3">
                    <button type="button" class="px-5 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-semibold rounded-xl transition-colors" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-xl shadow-sm transition-colors">Simpan Perubahan</button>
                </div>
                
            </form>
        </div>
    </div>
</div>