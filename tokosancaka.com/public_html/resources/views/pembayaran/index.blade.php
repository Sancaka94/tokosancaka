@extends('layouts.app')

@section('content')

<style>
    .payment-card-input {
        display: none;
    }
    .payment-card-label {
        cursor: pointer;
        border: 2px solid #e9ecef;
        border-radius: 0.75rem;
        transition: all 0.2s ease-in-out;
        background-color: #ffffff;
    }
    .payment-card-label:hover {
        background-color: #f8f9fa;
        border-color: #ced4da;
    }
    .payment-card-input:checked + .payment-card-label {
        border-color: #dc3545 !important;
        background-color: #fff5f5 !important;
        box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.1);
    }
    .payment-card-icon {
        height: 25px;
        width: auto;
        max-width: 50px;
        object-fit: contain;
    }
</style>

<div class="container py-4 py-md-5">

    @if(session('error'))
        <div class="row justify-content-center mb-3">
            <div class="col-12 col-md-8 col-lg-6">
                <div class="alert alert-danger alert-dismissible fade show shadow-sm border-0 rounded-4" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2 fs-5"></i>
                    <span>{{ session('error') }}</span>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            </div>
        </div>
    @endif

    <div class="row justify-content-center mb-4">
        <div class="col-12 col-md-8 text-center">
            <h2 class="fw-bold text-dark fs-3 fs-md-2">Portal Pembayaran Sancaka</h2>
            <p class="text-muted fs-6 fs-md-5">Cek tagihan dan selesaikan pembayaran Anda dengan aman.</p>
        </div>
    </div>

    <div class="row justify-content-center mb-4 mb-md-5">
        <div class="col-12 col-md-8 col-lg-6">
            <div class="card shadow-sm border-0 rounded-4">
                <div class="card-body p-3 p-md-4">
                    <form action="{{ route('pembayaran.cek') }}" method="GET">
                        <label for="akun" class="form-label fw-bold">Cek Identitas Pelanggan</label>
                        <div class="input-group input-group-lg">
                            <span class="input-group-text bg-light border-end-0"><i class="bi bi-person-badge text-secondary"></i></span>
                            <input type="text" name="akun" id="akun" class="form-control border-start-0 ps-0"
                                   placeholder="Nomor WA / Email..."
                                   value="{{ request('akun') }}" required>
                            <button type="submit" class="btn btn-danger px-3 px-md-4 fw-bold">Cek</button>
                        </div>
                        <small class="text-muted mt-2 d-block" style="font-size: 0.8rem;">Masukkan data yang terdaftar pada aplikasi Sancaka Express.</small>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @if(isset($user))
        <div class="row justify-content-center">
            <div class="col-12 col-xl-10">
                <div class="card shadow-lg border-0 overflow-hidden rounded-4">

                    <div class="card-header bg-dark text-white p-3 p-md-4 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
                        <div>
                            <h5 class="mb-0 fw-bold fs-5">Rincian Tagihan Pelanggan</h5>
                            <small class="text-light opacity-75">Sancaka Express Gateway</small>
                        </div>
                        <div class="text-start text-md-end border-top border-md-0 border-secondary pt-2 pt-md-0 mt-2 mt-md-0">
                            <span class="d-block" style="font-size: 0.85rem;">ID Pelanggan</span>
                            <h5 class="mb-0 text-warning fw-bold fs-5">#{{ str_pad($userId, 5, '0', STR_PAD_LEFT) }}</h5>
                        </div>
                    </div>

                    <div class="card-body p-0 bg-light">
                        <div class="p-3 p-md-4 bg-white border-bottom">
                            <div class="row">
                                <div class="col-12 col-md-8">
                                    <h6 class="text-muted mb-1" style="font-size: 0.85rem;">Ditagihkan Kepada:</h6>
                                    <h5 class="fw-bold mb-2 text-dark fs-5">{{ $user->nama_lengkap ?? $user->name }}</h5>
                                    <div class="text-muted d-flex flex-column gap-1" style="font-size: 0.9rem;">
                                        <span><i class="bi bi-telephone-fill me-2 text-secondary"></i> {{ $user->no_wa ?? $user->phone }}</span>
                                        <span><i class="bi bi-geo-alt-fill me-2 text-secondary"></i> {{ $user->address_detail ?? 'Alamat tidak tersedia' }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- ==========================================================
                             BUNGKUS SEMUA TAGIHAN MENJADI SATU FORMAT (NORMALISASI)
                             ========================================================== --}}
                        @php
                            $allBills = collect();

                            // 1. Tagihan Order (Belanja Toko)
                            if(isset($invoices)) {
                                foreach($invoices as $inv) {
                                    $allBills->push((object)[
                                        'id'      => $inv->invoice_number,
                                        'type'    => 'Belanja Marketplace',
                                        'icon'    => 'bi-shop',
                                        'total'   => $inv->total_amount,
                                        'sender'  => $inv->sender_name ?? 'Toko Sancaka',
                                        'courier' => explode('-', $inv->shipping_method)[1] ?? ($inv->shipping_method ?? 'Ambil di Toko'),
                                        'items'   => json_decode($inv->item_description, true),
                                    ]);
                                }
                            }

                            // 2. Tagihan Top Up (Isi Saldo)
                            if(isset($topups)) {
                                foreach($topups as $topup) {
                                    $allBills->push((object)[
                                        'id'      => $topup->reference_id,
                                        'type'    => 'Top Up Saldo',
                                        'icon'    => 'bi-wallet2',
                                        'total'   => $topup->amount,
                                        'sender'  => 'Sistem Sancaka',
                                        'courier' => '-',
                                        'items'   => [ ['name' => 'Top Up Saldo Aplikasi', 'qty' => 1, 'price' => $topup->amount] ],
                                    ]);
                                }
                            }

                            // 3. Tagihan Ekspedisi (Pengiriman Barang)
                            if(isset($ekspedisi)) {
                                foreach($ekspedisi as $eks) {
                                    $totalEks = $eks->price ?? ($eks->shipping_cost + $eks->insurance_cost + $eks->cod_fee);
                                    $allBills->push((object)[
                                        'id'      => $eks->nomor_invoice,
                                        'type'    => 'Pengiriman Paket',
                                        'icon'    => 'bi-box-seam',
                                        'total'   => $totalEks,
                                        'sender'  => $eks->sender_name ?? 'Pelanggan Sancaka',
                                        'courier' => explode('-', $eks->shipping_method)[1] ?? ($eks->expedition ?? 'Sancaka Express'),
                                        'items'   => [ ['name' => 'Ongkos Kirim (' . ($eks->weight ?? 1) . ' Kg)', 'qty' => 1, 'price' => $totalEks] ],
                                    ]);
                                }
                            }
                        @endphp

                        <div class="p-3 p-md-4">
                            @if($allBills->count() > 0)
                                <div class="d-flex flex-column gap-3">
                                    @foreach($allBills as $bill)
                                        <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
                                            <div class="card-body p-0">
                                                <div class="row g-0">

                                                    <div class="col-12 col-lg-8 p-3 p-md-4 border-end-lg">
                                                        <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
                                                            <span class="badge bg-light text-dark border fw-bold px-3 py-2">
                                                                <i class="bi {{ $bill->icon }} me-1"></i> {{ $bill->id }}
                                                            </span>
                                                            <span class="badge bg-warning text-dark px-3 py-2 rounded-pill">{{ $bill->type }}</span>
                                                        </div>

                                                        <div class="d-flex flex-column">
                                                            <strong class="text-dark mb-2" style="font-size: 0.9rem;">Rincian Tagihan:</strong>

                                                            @if(is_array($bill->items) && count($bill->items) > 0)
                                                                <div class="d-flex flex-column gap-2 mb-3">
                                                                    @foreach($bill->items as $item)
                                                                        <div class="d-flex align-items-center bg-light p-2 rounded-3 border border-light">
                                                                            @if(!empty($item['image_url']))
                                                                                <img src="{{ asset('storage/' . $item['image_url']) }}" alt="{{ $item['name'] ?? 'Produk' }}" class="rounded me-3 border bg-white" style="width: 50px; height: 50px; object-fit: cover;">
                                                                            @else
                                                                                <div class="bg-white rounded border me-3 d-flex align-items-center justify-content-center text-secondary" style="width: 50px; height: 50px;">
                                                                                    <i class="bi bi-box fs-4"></i>
                                                                                </div>
                                                                            @endif
                                                                            <div style="font-size: 0.85rem; line-height: 1.3;">
                                                                                <div class="fw-bold text-dark mb-1">{{ $item['name'] ?? 'Produk Sancaka' }}</div>
                                                                                <div class="text-muted fw-semibold">
                                                                                    {{ $item['qty'] ?? $item['quantity'] ?? 1 }} x Rp {{ number_format($item['price'] ?? 0, 0, ',', '.') }}
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    @endforeach
                                                                </div>
                                                            @else
                                                                <span class="mb-3 text-muted" style="font-size: 0.9rem;">Detail tagihan tidak tersedia.</span>
                                                            @endif

                                                            <div class="bg-light p-3 rounded-3 border border-light" style="font-size: 0.85rem;">
                                                                <div class="row g-2">
                                                                    <div class="col-12 col-sm-6">
                                                                        <span class="text-muted d-block mb-1"><i class="bi bi-person me-1"></i> Asal Tagihan:</span>
                                                                        <strong class="text-dark">{{ $bill->sender }}</strong>
                                                                    </div>
                                                                    <div class="col-12 col-sm-6">
                                                                        <span class="text-muted d-block mb-1"><i class="bi bi-truck me-1"></i> Layanan:</span>
                                                                        <strong class="text-dark text-uppercase">{{ $bill->courier }}</strong>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="col-12 col-lg-4 p-3 p-md-4 bg-white d-flex flex-column justify-content-center align-items-center text-center">
                                                        <span class="text-muted fw-bold mb-2" style="font-size: 0.9rem;">Total Tagihan</span>
                                                        <h3 class="fw-black text-danger mb-4">Rp {{ number_format($bill->total, 0, ',', '.') }}</h3>

                                                        <button type="button" class="btn btn-danger btn-lg w-100 fw-bold shadow-sm rounded-3" data-bs-toggle="modal" data-bs-target="#payModal{{ $bill->id }}">
                                                            <i class="bi bi-credit-card me-2"></i> Bayar Sekarang
                                                        </button>
                                                    </div>

                                                </div>
                                            </div>
                                        </div>

                                        {{-- MODAL PEMBAYARAN --}}
                                        <div class="modal fade text-start" id="payModal{{ $bill->id }}" tabindex="-1" aria-hidden="true">
                                            <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
                                                <form action="{{ route('pembayaran.proses', $bill->id) }}" method="POST" class="modal-content border-0 shadow-lg rounded-4">
                                                    @csrf
                                                    <div class="modal-header bg-white border-bottom px-4 py-3">
                                                        <h5 class="modal-title fw-bold text-dark">Pilih Metode Pembayaran</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>

                                                    <div class="modal-body p-3 p-md-4 bg-light">
                                                        <div class="d-flex justify-content-between align-items-center bg-white p-3 rounded-3 border shadow-sm mb-4">
                                                            <span class="text-muted fw-bold" style="font-size: 0.9rem;">Total Tagihan</span>
                                                            <span class="fs-4 fw-black text-danger mb-0">Rp {{ number_format($bill->total, 0, ',', '.') }}</span>
                                                        </div>

                                                        <h6 class="fw-bold text-secondary mb-3 text-uppercase" style="font-size: 0.8rem; letter-spacing: 1px;">E-Wallet & QRIS Utama</h6>
                                                        <div class="row g-2 g-md-3 mb-4">
                                                            <div class="col-12 col-md-6">
                                                                <input type="radio" name="payment_method" value="DANA" id="dana_{{ $bill->id }}" class="payment-card-input" required>
                                                                <label for="dana_{{ $bill->id }}" class="payment-card-label d-flex align-items-center p-3 w-100 h-100 m-0">
                                                                    <div class="bg-light border rounded p-2 me-3 d-flex align-items-center justify-content-center" style="width: 55px; height: 35px;">
                                                                        <img src="https://upload.wikimedia.org/wikipedia/commons/7/72/Logo_dana_blue.svg" alt="DANA" style="height: 15px; object-fit: contain;">
                                                                    </div>
                                                                    <div>
                                                                        <div class="fw-bold text-dark" style="font-size: 0.9rem;">DANA Otomatis</div>
                                                                    </div>
                                                                </label>
                                                            </div>
                                                            <div class="col-12 col-md-6">
                                                                <input type="radio" name="payment_method" value="DOKU_JOKUL" id="doku_{{ $bill->id }}" class="payment-card-input">
                                                                <label for="doku_{{ $bill->id }}" class="payment-card-label d-flex align-items-center p-3 w-100 h-100 m-0">
                                                                    <div class="bg-light border rounded p-2 me-3 d-flex align-items-center justify-content-center" style="width: 55px; height: 35px;">
                                                                        <span class="fw-black text-danger" style="font-size: 0.8rem;">DOKU</span>
                                                                    </div>
                                                                    <div>
                                                                        <div class="fw-bold text-dark" style="font-size: 0.9rem;">DOKU Payment</div>
                                                                        <div class="text-muted" style="font-size: 0.75rem;">Kartu Kredit & QRIS</div>
                                                                    </div>
                                                                </label>
                                                            </div>
                                                        </div>

                                                        @if(isset($tripayChannels) && count($tripayChannels) > 0)
                                                            @php $groupedChannels = collect($tripayChannels)->groupBy('group'); @endphp
                                                            @foreach($groupedChannels as $groupName => $channels)
                                                                <h6 class="fw-bold text-secondary mb-3 mt-2 text-uppercase" style="font-size: 0.8rem; letter-spacing: 1px;">{{ $groupName }}</h6>
                                                                <div class="row g-2 g-md-3 mb-4">
                                                                    @foreach($channels as $channel)
                                                                        <div class="col-12 col-md-6">
                                                                            <input type="radio" name="payment_method" value="{{ $channel['code'] }}" id="chan_{{ $channel['code'] }}_{{ $bill->id }}" class="payment-card-input">
                                                                            <label for="chan_{{ $channel['code'] }}_{{ $bill->id }}" class="payment-card-label d-flex align-items-center p-3 w-100 h-100 m-0">
                                                                                <div class="bg-white border rounded p-1 me-3 d-flex align-items-center justify-content-center" style="width: 55px; height: 35px;">
                                                                                    <img src="{{ $channel['icon_url'] }}" alt="{{ $channel['name'] }}" class="payment-card-icon">
                                                                                </div>
                                                                                <div class="flex-grow-1">
                                                                                    <div class="fw-bold text-dark" style="font-size: 0.85rem;">{{ $channel['name'] }}</div>
                                                                                </div>
                                                                            </label>
                                                                        </div>
                                                                    @endforeach
                                                                </div>
                                                            @endforeach
                                                        @endif
                                                    </div>

                                                    <div class="modal-footer bg-white border-top p-3 d-flex flex-column flex-sm-row">
                                                        <button type="button" class="btn btn-light px-4 w-100 w-sm-auto mb-2 mb-sm-0" data-bs-dismiss="modal">Batal</button>
                                                        <button type="submit" class="btn btn-danger px-5 fw-bold shadow-sm w-100 w-sm-auto">
                                                            Lanjutkan Pembayaran <i class="bi bi-arrow-right ms-2"></i>
                                                        </button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="text-center py-5">
                                    <div class="bg-light rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                                        <i class="bi bi-check2-circle text-success fs-1"></i>
                                    </div>
                                    <h5 class="text-dark fw-bold">Semua Tagihan Lunas!</h5>
                                    <p class="text-muted">Terima kasih telah menggunakan layanan Sancaka Express.</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @elseif(request()->has('akun'))
        <div class="row justify-content-center">
            <div class="col-12 col-md-8 text-center">
                <div class="alert alert-danger shadow-sm border-0 rounded-4" role="alert">
                    <i class="bi bi-exclamation-circle-fill me-2"></i> Akun dengan identitas tersebut tidak ditemukan di sistem.
                </div>
            </div>
        </div>
    @endif
</div>
@endsection
