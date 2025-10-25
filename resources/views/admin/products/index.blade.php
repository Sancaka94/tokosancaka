@extends('layouts.admin')

@section('title', 'Manajemen Produk')
@section('page-title', 'Manajemen Produk')

@push('styles')
    {{-- CSS DataTables + Bootstrap --}}
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
@endpush

@section('content')
<div class="bg-white p-4 rounded shadow-sm">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="h5 fw-semibold mb-0">Daftar Semua Produk</h2>
        <a href="{{ route('admin.products.create') }}" class="btn btn-primary btn-sm">
            <i class="fa fa-plus me-1"></i> Tambah Produk Baru
        </a>
    </div>

    {{-- Notifikasi --}}
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fa fa-check-circle me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Tabel Produk --}}
    <div class="table-responsive">
        <table class="table table-bordered table-striped align-middle" id="product-table" width="100%">
            <thead class="table-light">
                <tr>
                    <th style="width:5%">No</th>
                    <th style="width:10%">Gambar</th>
                    <th>Nama Produk</th>
                    <th>Kategori</th>
                    <th>Harga</th>
                    <th>Stok</th>
                    <th>Status</th>
                    <th style="width:15%">Aksi</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>

{{-- Modal Restock --}}
<div id="restockModal" class="modal fade" tabindex="-1" aria-labelledby="restockLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="restockForm" action="" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="restockLabel">Restock Produk</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <p>Anda akan menambahkan stok untuk produk: <strong id="productName"></strong></p>
                    <div class="mb-3">
                        <label for="stock" class="form-label">Jumlah Stok Baru</label>
                        <input type="number" name="stock" id="stock" class="form-control" min="1" required>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Stok</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
    {{-- JS jQuery + DataTables + Bootstrap --}}
    <script src="https://code.jquery.com/jquery-3.7.0.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        $(document).ready(function () {
            const table = $('#product-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: "{{ route('admin.products.data') }}",
                columns: [
                    { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                    { data: 'image', name: 'image', orderable: false, searchable: false },
                    { data: 'name', name: 'name' },
                    { data: 'category_name', name: 'category_name' },
                    { data: 'price', name: 'price' },
                    { data: 'stock', name: 'stock' },
                    { data: 'status_badge', name: 'status', orderable: false, searchable: false },
                    { data: 'action', name: 'action', orderable: false, searchable: false },
                ],
                order: [[2, 'asc']],
                language: {
                    search: "Cari:",
                    lengthMenu: "Tampilkan _MENU_ data",
                    info: "Menampilkan _START_ - _END_ dari _TOTAL_ data",
                    paginate: { next: ">", previous: "<" },
                    processing: "Memuat data..."
                }
            });

            // Modal Restock
            window.openRestockModal = function (productId, productName) {
                const form = document.getElementById('restockForm');
                const nameEl = document.getElementById('productName');
                form.action = `{{ url('admin/products') }}/${productId}/restock`;
                nameEl.textContent = productName;
                const modal = new bootstrap.Modal(document.getElementById('restockModal'));
                modal.show();
            }
        });
    </script>
@endpush
