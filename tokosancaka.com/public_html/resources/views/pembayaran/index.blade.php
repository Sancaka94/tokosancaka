@extends('layouts.app')

@section('content')
<div class="container py-5">

    <div class="row justify-content-center mb-4">
        <div class="col-md-8 text-center">
            <h2 class="fw-bold text-dark">Portal Pembayaran Sancaka Express</h2>
            <p class="text-muted">Cek tagihan dan selesaikan pembayaran Anda dengan aman.</p>
        </div>
    </div>

    <div class="row justify-content-center mb-5">
        <div class="col-md-8">
            <div class="card shadow-sm border-0">
                <div class="card-body p-4">
                    <form action="{{ route('pembayaran.cek') }}" method="GET">
                        <label for="akun" class="form-label fw-bold">Cek Identitas Pelanggan</label>
                        <div class="input-group input-group-lg">
                            <span class="input-group-text bg-light"><i class="bi bi-person-badge"></i></span>
                            <input type="text" name="akun" id="akun" class="form-control"
                                   placeholder="Masukkan Nomor WhatsApp atau Email Anda..."
                                   value="{{ request('akun') }}" required>
                            <button type="submit" class="btn btn-danger px-4 fw-bold">Cek Tagihan</button>
                        </div>
                        <small class="text-muted mt-2 d-block">Masukkan data yang terdaftar pada aplikasi Sancaka Express.</small>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @if(isset($user))
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="card shadow-lg border-0 overflow-hidden">

                    <div class="card-header bg-dark text-white p-4 d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-0 fw-bold">Rincian Tagihan Pelanggan</h5>
                            <small class="text-light opacity-75">Sancaka Express Gateway</small>
                        </div>
                        <div class="text-end">
                            <span class="d-block">ID Pelanggan</span>
                            <h5 class="mb-0 text-warning fw-bold">#{{ str_pad($user->id, 5, '0', STR_PAD_LEFT) }}</h5>
                        </div>
                    </div>

                    <div class="card-body p-0 bg-light">
                        <div class="p-4 bg-white border-bottom">
                            <div class="row">
                                <div class="col-md-6">
                                    <h6 class="text-muted mb-1">Ditagihkan Kepada:</h6>
                                    <h5 class="fw-bold mb-1 text-dark">{{ $user->nama_lengkap ?? $user->name }}</h5>
                                    <p class="mb-0 text-muted">
                                        <i class="bi bi-telephone-fill me-2"></i> {{ $user->no_wa ?? $user->phone }}<br>
                                        <i class="bi bi-geo-alt-fill me-2"></i> {{ $user->address_detail ?? 'Alamat tidak tersedia' }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="table-responsive p-4">
                            @if($invoices->count() > 0)
                                <table class="table table-hover table-bordered align-middle bg-white mb-0">
                                    <thead class="table-light text-center">
                                        <tr>
                                            <th width="15%">No. Invoice</th>
                                            <th width="35%">Rincian Belanja</th>
                                            <th width="20%">Total Tagihan</th>
                                            <th width="15%">Status</th>
                                            <th width="15%">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($invoices as $inv)
                                        <tr>
                                            <td class="text-center fw-bold text-primary">{{ $inv->invoice_number }}</td>
                                            <td>
                                                <div class="d-flex flex-column">
                                                    <span class="mb-1"><strong class="text-dark">Produk:</strong> {{ $inv->item_description ?? 'Pembelian Marketplace' }}</span>
                                                    <span class="mb-1"><strong class="text-dark">Pengirim:</strong> {{ $inv->sender_name ?? 'Toko Sancaka' }}</span>
                                                    <span><strong class="text-dark">Ekspedisi:</strong> {{ $inv->shipping_method ?? 'Ambil di Toko' }}</span>
                                                </div>
                                            </td>
                                            <td class="text-center text-danger fw-bold fs-5">
                                                Rp {{ number_format($inv->total_amount, 0, ',', '.') }}
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-warning text-dark px-3 py-2 rounded-pill">Pending</span>
                                            </td>
                                            <td class="text-center">
                                                <a href="{{ route('pembayaran.proses', $inv->invoice_number) }}" class="btn btn-danger btn-sm w-100 fw-bold shadow-sm">
                                                    <i class="bi bi-credit-card me-1"></i> Bayar Sekarang
                                                </a>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            @else
                                <div class="text-center py-5">
                                    <h5 class="text-muted">Tidak ada tagihan yang tertunda.</h5>
                                    <p class="text-muted">Terima kasih telah menggunakan layanan Sancaka Express!</p>
                                </div>
                            @endif
                        </div>
                    </div>

                </div>
            </div>
        </div>
    @elseif(request()->has('akun'))
        <div class="row justify-content-center">
            <div class="col-md-8 text-center">
                <div class="alert alert-danger shadow-sm" role="alert">
                    <i class="bi bi-exclamation-circle-fill me-2"></i> Akun dengan identitas tersebut tidak ditemukan di sistem Sancaka Express.
                </div>
            </div>
        </div>
    @endif

</div>

@endsection
