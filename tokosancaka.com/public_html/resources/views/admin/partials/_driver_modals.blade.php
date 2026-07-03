<div class="modal fade" id="detailModal{{ $driver->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="modal-title fw-bold">Detail Pendaftaran</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-start">
                <ul class="list-group list-group-flush mb-4">
                    <li class="list-group-item bg-transparent px-0 border-light text-muted">Nama: <strong class="text-dark">{{ $driver->nama_lengkap }}</strong></li>
                    <li class="list-group-item bg-transparent px-0 border-light text-muted">No WhatsApp: <strong class="text-dark">{{ $driver->nomor_wa }}</strong></li>
                    <li class="list-group-item bg-transparent px-0 border-light text-muted">Alamat: <strong class="text-dark">{{ $driver->alamat_lengkap }}</strong></li>
                    <li class="list-group-item bg-transparent px-0 border-light text-muted">KTP: 
                        @if($driver->file_ktp)
                            <a href="{{ asset('storage/' . $driver->file_ktp) }}" target="_blank" class="text-primary text-decoration-none ms-1">Lihat Dokumen</a>
                        @else
                            -
                        @endif
                    </li>
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
                    <div class="mb-3">
                        <label class="form-label text-muted fw-semibold">Nomor WhatsApp</label>
                        <input type="text" name="nomor_wa" class="form-control bg-light border-0 rounded-3" value="{{ $driver->nomor_wa }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted fw-semibold">Alamat Lengkap</label>
                        <textarea name="alamat_lengkap" class="form-control bg-light border-0 rounded-3" rows="3" required>{{ $driver->alamat_lengkap }}</textarea>
                    </div>
                </div>
                <div class="modal-footer border-top-0 pt-0">
                    <button type="button" class="btn btn-light rounded-3 fw-semibold" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary rounded-3 fw-semibold px-4">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>