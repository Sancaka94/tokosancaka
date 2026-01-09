<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Affiliate - {{ $affiliate->name }}</title>
    <link rel="icon" href="https://tokosancaka.com/storage/uploads/sancaka.png" type="image/png">
<link rel="shortcut icon" href="https://tokosancaka.com/storage/uploads/sancaka.png" type="image/png">

<link rel="apple-touch-icon" href="https://tokosancaka.com/storage/uploads/sancaka.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container mt-5 mb-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Edit Data Affiliate</h5>
                    <a href="{{ route('affiliate.index') }}" class="btn btn-sm btn-light">Kembali</a>
                </div>
                <div class="card-body">
                    
                    @if(session('error'))
                        <div class="alert alert-danger">{{ session('error') }}</div>
                    @endif

                    <form action="{{ route('affiliate.update', $affiliate->id) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nama Lengkap</label>
                                <input type="text" name="name" class="form-control" value="{{ old('name', $affiliate->name) }}" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nomor WhatsApp</label>
                                <input type="number" name="whatsapp" class="form-control" value="{{ old('whatsapp', $affiliate->whatsapp) }}" required>
                            </div>
                            
                            <div class="col-12 mb-3">
                                <label class="form-label">Alamat Lengkap</label>
                                <textarea name="address" class="form-control" rows="2">{{ old('address', $affiliate->address) }}</textarea>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nama Bank (BCA/BRI/dll)</label>
                                <input type="text" name="bank_name" class="form-control" value="{{ old('bank_name', $affiliate->bank_name) }}">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nomor Rekening</label>
                                <input type="number" name="bank_account_number" class="form-control" value="{{ old('bank_account_number', $affiliate->bank_account_number) }}">
                            </div>

                            <hr>

                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Kode Kupon (Diskon)</label>
                                <input type="text" name="coupon_code" class="form-control border-warning" value="{{ old('coupon_code', $affiliate->coupon_code) }}" required>
                                <small class="text-muted">Mengubah ini akan mengubah kode promo affiliate.</small>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Saldo Komisi (Rp)</label>
                                <input type="number" name="balance" class="form-control border-success" value="{{ old('balance', $affiliate->balance) }}" required>
                                <small class="text-muted">Edit manual jika ada koreksi saldo.</small>
                            </div>

                            <div class="col-12 mb-3">
                                <label class="form-label fw-bold text-danger">Reset PIN (Opsional)</label>
                                <input type="number" name="pin" class="form-control" placeholder="Kosongkan jika tidak ingin mengubah PIN">
                                <small class="text-danger">Hanya isi jika ingin mengganti PIN Login member.</small>
                            </div>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-lg">Simpan Perubahan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>