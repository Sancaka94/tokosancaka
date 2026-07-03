<div class="modal fade" id="detailModal{{ $driver->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="modal-title fw-bold">Detail Pendaftaran</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-start">
                <ul class="list-group list-group-flush mb-4">
                    <li class="list-group-item bg-transparent px-0 border-light text-muted">ID Akun Pengguna: 
                        <strong class="text-dark">{{ $driver->id_pengguna ?? 'Belum Terhubung' }}</strong>
                    </li>
                    <li class="list-group-item bg-transparent px-0 border-light text-muted">Nama: <strong class="text-dark">{{ $driver->nama_lengkap }}</strong></li>
                    <li class="list-group-item bg-transparent px-0 border-light text-muted">NIK: <strong class="text-dark">{{ $driver->nomor_nik ?? '-' }}</strong></li>
                    <li class="list-group-item bg-transparent px-0 border-light text-muted">KK: <strong class="text-dark">{{ $driver->nomor_kk ?? '-' }}</strong></li>
                    <li class="list-group-item bg-transparent px-0 border-light text-muted">WhatsApp: <strong class="text-dark">{{ $driver->nomor_wa }}</strong></li>
                    <li class="list-group-item bg-transparent px-0 border-light text-muted">Alamat: <strong class="text-dark">{{ $driver->alamat_lengkap }}</strong></li>
                    
                    <li class="list-group-item bg-transparent px-0 border-light text-muted">Status Peta: 
                        @if($driver->is_active_map == 1)
                            <span class="badge bg-success">Online di Peta</span>
                        @else
                            <span class="badge bg-secondary">Offline</span>
                        @endif
                    </li>
                    <li class="list-group-item bg-transparent px-0 border-light text-muted d-flex align-items-center">
                        Titik Lokasi: 
                        <strong class="text-dark ms-2">{{ $driver->latitude ?? '-' }}, {{ $driver->longitude ?? '-' }}</strong>
                        @if($driver->latitude && $driver->longitude)
                            <a href="https://www.google.com/maps?q={{ $driver->latitude }},{{ $driver->longitude }}" target="_blank" class="badge bg-primary text-decoration-none ms-auto p-2">
                                <i class="bi bi-geo-alt"></i> Cek Maps
                            </a>
                        @endif
                    </li>

                    <li class="list-group-item bg-transparent px-0 border-light text-muted mt-2"><strong>Dokumen Lampiran:</strong></li>
                    <div class="d-flex flex-wrap gap-2 mt-2">
                        @if($driver->file_ktp) <a href="{{ asset('storage/' . $driver->file_ktp) }}" target="_blank" class="badge bg-primary text-decoration-none p-2 shadow-sm">KTP</a> @endif
                        @if($driver->file_kk) <a href="{{ asset('storage/' . $driver->file_kk) }}" target="_blank" class="badge bg-info text-decoration-none p-2 shadow-sm">KK</a> @endif
                        @if($driver->file_stnk) <a href="{{ asset('storage/' . $driver->file_stnk) }}" target="_blank" class="badge bg-secondary text-decoration-none p-2 shadow-sm">STNK</a> @endif
                        @if($driver->file_bpkb) <a href="{{ asset('storage/' . $driver->file_bpkb) }}" target="_blank" class="badge bg-dark text-decoration-none p-2 shadow-sm">BPKB</a> @endif
                        @if($driver->foto_motor) <a href="{{ asset('storage/' . $driver->foto_motor) }}" target="_blank" class="badge bg-warning text-dark text-decoration-none p-2 shadow-sm">Foto Motor</a> @endif
                        @if($driver->foto_wajah) <a href="{{ asset('storage/' . $driver->foto_wajah) }}" target="_blank" class="badge bg-success text-decoration-none p-2 shadow-sm">Foto Wajah</a> @endif
                        @if($driver->file_buku_nikah) <a href="{{ asset('storage/' . $driver->file_buku_nikah) }}" target="_blank" class="badge bg-danger text-decoration-none p-2 shadow-sm">Buku Nikah</a> @endif
                    </div>
                </ul>

                <div class="d-flex gap-2 mt-3">
                    <form action="{{ route('admin.drivers.status', $driver->id) }}" method="POST" class="w-50">
                        @csrf @method('PATCH')
                        <input type="hidden" name="status" value="approved">
                        <button type="submit" class="btn btn-success w-100 rounded-3 fw-semibold shadow-sm"><i class="bi bi-check-circle"></i> Setujui</button>
                    </form>
                    <form action="{{ route('admin.drivers.status', $driver->id) }}" method="POST" class="w-50">
                        @csrf @method('PATCH')
                        <input type="hidden" name="status" value="rejected">
                        <button type="submit" class="btn btn-outline-danger w-100 rounded-3 fw-semibold"><i class="bi bi-x-circle"></i> Tolak</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="editModal{{ $driver->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-bottom-0">
                <h5 class="modal-title fw-bold">Edit Data Driver</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('admin.drivers.update', $driver->id) }}" method="POST">
                @csrf @method('PUT')
                <div class="modal-body text-start">
                    <div class="mb-3">
                        <label class="form-label text-muted fw-semibold">Nama Lengkap</label>
                        <input type="text" name="nama_lengkap" class="form-control bg-light border-0 rounded-3" value="{{ $driver->nama_lengkap }}" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted fw-semibold">NIK</label>
                            <input type="text" name="nomor_nik" class="form-control bg-light border-0 rounded-3" value="{{ $driver->nomor_nik }}" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted fw-semibold">KK</label>
                            <input type="text" name="nomor_kk" class="form-control bg-light border-0 rounded-3" value="{{ $driver->nomor_kk }}" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted fw-semibold">Nomor WhatsApp</label>
                        <input type="text" name="nomor_wa" class="form-control bg-light border-0 rounded-3" value="{{ $driver->nomor_wa }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted fw-semibold">Alamat Lengkap</label>
                        <textarea name="alamat_lengkap" class="form-control bg-light border-0 rounded-3" rows="3" required>{{ $driver->alamat_lengkap }}</textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted fw-semibold">Latitude</label>
                            <input type="text" name="latitude" class="form-control bg-light border-0 rounded-3" value="{{ $driver->latitude }}">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted fw-semibold">Longitude</label>
                            <input type="text" name="longitude" class="form-control bg-light border-0 rounded-3" value="{{ $driver->longitude }}">
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top-0 pt-0">
                    <button type="button" class="btn btn-light rounded-3 fw-semibold" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary rounded-3 fw-semibold px-4 shadow-sm">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>