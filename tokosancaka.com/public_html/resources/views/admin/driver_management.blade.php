@extends('layouts.admin')

@section('content')
<div class="container-fluid py-4">
    <!-- Header & Title -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold m-0 text-dark">Manajemen Pendaftaran Driver</h3>
    </div>

    <!-- Alert Notifications -->
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show rounded-3 shadow-sm" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show rounded-3 shadow-sm" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Filter & Search Section -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-4">
            <form method="GET" action="{{ route('admin.drivers.index') }}" class="row g-3">
                <div class="col-md-5">
                    <input type="text" name="search" value="{{ request('search') }}" class="form-control form-control-lg bg-light border-0 rounded-3" placeholder="Cari nama atau nomor WA...">
                </div>
                <div class="col-md-3">
                    <select name="status" class="form-select form-select-lg bg-light border-0 rounded-3">
                        <option value="">Semua Status</option>
                        <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Approved</option>
                        <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Rejected</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-dark btn-lg w-100 rounded-3">
                        <i class="bi bi-funnel"></i> Filter
                    </button>
                </div>
                <div class="col-md-2">
                    <button type="button" class="btn btn-danger btn-lg w-100 rounded-3" id="btn-bulk-delete">
                        <i class="bi bi-trash"></i> Hapus Massal
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Data Table Section -->
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4" style="width: 50px;">
                                <input class="form-check-input" type="checkbox" id="checkAll">
                            </th>
                            <th>Nama Lengkap</th>
                            <th>No. WhatsApp</th>
                            <th>Status</th>
                            <th>Tanggal Daftar</th>
                            <th class="text-center pe-4">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="border-top-0">
                        @forelse($drivers as $driver)
                        <tr>
                            <td class="ps-4">
                                <input class="form-check-input driver-checkbox" type="checkbox" value="{{ $driver->id }}">
                            </td>
                            <td class="fw-semibold text-dark">{{ $driver->nama_lengkap }}</td>
                            <td>{{ $driver->nomor_wa }}</td>
                            <td>
                                @if($driver->status == 'pending')
                                    <span class="badge bg-warning text-dark rounded-pill px-3 py-2">Pending</span>
                                @elseif($driver->status == 'approved')
                                    <span class="badge bg-success rounded-pill px-3 py-2">Approved</span>
                                @else
                                    <span class="badge bg-danger rounded-pill px-3 py-2">Rejected</span>
                                @endif
                            </td>
                            <td class="text-muted">{{ $driver->created_at->format('d M Y') }}</td>
                            <td class="text-center pe-4">
                                <!-- Action Buttons -->
                                <div class="btn-group shadow-sm rounded-3">
                                    <button type="button" class="btn btn-light btn-sm text-primary" data-bs-toggle="modal" data-bs-target="#detailModal{{ $driver->id }}" title="Detail & Verifikasi">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                    <button type="button" class="btn btn-light btn-sm text-warning" data-bs-toggle="modal" data-bs-target="#editModal{{ $driver->id }}" title="Edit Data">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <form action="{{ route('admin.drivers.destroy', $driver->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus permanen data ini?');">
                                        @csrf 
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-light btn-sm text-danger" title="Hapus">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>

                                <!-- Modals (Detail & Edit) dipanggil dari file partial -->
                                @include('admin.partials._driver_modals', ['driver' => $driver])
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                Belum ada data pendaftaran.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Pagination -->
        @if($drivers->hasPages())
        <div class="card-footer bg-white border-0 py-3">
            {{ $drivers->links() }}
        </div>
        @endif
    </div>
</div>

<!-- Script Hapus Massal (Bulk Delete) -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    const checkAll = document.getElementById('checkAll');
    const checkboxes = document.querySelectorAll('.driver-checkbox');
    const btnBulkDelete = document.getElementById('btn-bulk-delete');

    // Fitur Centang Semua
    if (checkAll) {
        checkAll.addEventListener('change', function () {
            checkboxes.forEach(cb => cb.checked = checkAll.checked);
        });
    }

    // Aksi Hapus Massal via Fetch API
    if (btnBulkDelete) {
        btnBulkDelete.addEventListener('click', function () {
            let selectedIds = Array.from(checkboxes)
                                   .filter(cb => cb.checked)
                                   .map(cb => cb.value);
            
            if (selectedIds.length === 0) {
                alert('Pilih setidaknya satu data untuk dihapus.');
                return;
            }

            if (confirm('Anda yakin ingin menghapus ' + selectedIds.length + ' data terpilih secara permanen?')) {
                // Tampilkan loading state pada tombol
                const originalText = btnBulkDelete.innerHTML;
                btnBulkDelete.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Menghapus...';
                btnBulkDelete.disabled = true;

                fetch("{{ route('admin.drivers.bulk_destroy') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ ids: selectedIds })
                })
                .then(response => response.json())
                .then(data => {
                    if(data.success) {
                        location.reload();
                    } else {
                        alert(data.message);
                        btnBulkDelete.innerHTML = originalText;
                        btnBulkDelete.disabled = false;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Terjadi kesalahan pada sistem.');
                    btnBulkDelete.innerHTML = originalText;
                    btnBulkDelete.disabled = false;
                });
            }
        });
    }
});
</script>
@endsection