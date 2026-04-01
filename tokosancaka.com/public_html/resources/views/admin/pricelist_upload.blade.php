@extends('layouts.admin')

@section('content')
<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Upload Pricelist Prabayar (Excel)</h1>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Form Upload Pricelist</h6>
        </div>
        <div class="card-body">
            <form action="{{ route('admin.pricelist.upload') }}" method="POST" enctype="multipart/form-data">
                @csrf

                <div class="mb-3">
                    <label class="form-label font-weight-bold">Kategori / Tipe Produk</label>
                    <select name="type" class="form-select" required>
                        <option value="">-- Pilih Tipe --</option>
                        <option value="Pulsa">Pulsa</option>
                        <option value="Data">Paket Data</option>
                        <option value="Game">Voucher Game</option>
                        <option value="Etoll">E-Toll / Saldo Digital</option>
                        <option value="PLN">Token PLN</option>
                    </select>
                    <small class="text-muted">Pilih sesuai dengan isi sheet Excel yang Anda upload saat ini.</small>
                </div>

                <div class="mb-4">
                    <label class="form-label font-weight-bold">File Excel (.xlsx / .csv)</label>
                    <input type="file" name="file" class="form-control" accept=".xlsx, .xls, .csv" required>
                    <small class="text-danger mt-1 d-block">
                        *Pastikan kolom berurutan: A (No), B (Operator), C (Kode), D (Nominal), F (Harga Rp), G (Status).
                    </small>
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-upload"></i> Upload & Simpan Data
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
