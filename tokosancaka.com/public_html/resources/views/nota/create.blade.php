@extends('layouts.app')

@section('content')
<div class="container mt-4 mb-5">

    <!-- Blok untuk Menampilkan Error Validasi -->
    @if ($errors->any())
        <div class="alert alert-danger shadow-sm mb-4">
            <h6 class="fw-bold"><i class="fa-solid fa-triangle-exclamation"></i> Gagal Menyimpan Nota!</h6>
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card shadow">
        <div class="card-body p-5">
            <div class="row border-bottom pb-3 mb-4 align-items-center">
                <div class="col-md-2 text-center text-md-start mb-3 mb-md-0">
                    <img src="https://tokosancaka.com/storage/uploads/sancaka.png" alt="Logo Sancaka" class="img-fluid" style="max-height: 85px;">
                </div>
                <div class="col-md-6">
                    <h4 class="fw-bold mb-0">SANCAKA KARYA HUTAMA</h4>
                    <p class="mb-0">Jl. Dr. Wahidin no. 18A (depan RSUD Soeroto Ngawi)</p>
                    <p class="mb-0">Telp: 0881-9435-180</p>
                </div>
                <div class="col-md-4 text-md-end">
                    <h2 class="text-uppercase fw-bold mt-2 mt-md-0">Nota</h2>
                </div>
            </div>

            <form action="{{ route('nota.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="row mb-4">
                    <div class="col-md-4">
                        <label class="fw-bold text-muted small">NOTA NO.</label>
                        <input type="text" class="form-control fw-bold bg-light" name="no_nota" value="{{ $no_nota }}" readonly>
                    </div>
                    <div class="col-md-4 offset-md-4">
                        <div class="mb-3">
                            <label class="fw-bold text-muted small">TANGGAL</label>
                            <input type="date" class="form-control" name="tanggal" value="{{ date('Y-m-d') }}" required>
                        </div>
                        <div>
                            <label class="fw-bold text-muted small">KEPADA YTH.</label>
                            <input type="text" class="form-control" name="kepada" value="{{ old('kepada', 'Customer Sancaka') }}" placeholder="Nama Instansi / Perusahaan" required>
                        </div>
                    </div>
                </div>

                <table class="table table-bordered table-sm" id="notaTable">
                    <thead class="table-light text-center align-middle">
                        <tr>
                            <th width="10%">BANYAKNYA</th>
                            <th width="40%">NAMA BARANG / JASA</th>
                            <th width="20%">HARGA</th>
                            <th width="25%">JUMLAH</th>
                            <th width="5%"><button type="button" class="btn btn-sm btn-success w-100 fw-bold" onclick="addRow()">+</button></th>
                        </tr>
                    </thead>
                    <tbody id="tbodyItem">
                        <!-- Baris Pertama Default -->
                        <tr>
                            <td>
                                <input type="number" name="barang[0][banyaknya]" class="form-control text-center qty" min="1" value="1" oninput="kalkulasi()" required>
                            </td>
                            <td>
                                <input type="text" name="barang[0][nama]" class="form-control" placeholder="Deskripsi Barang..." required>
                            </td>
                            <td>
                                <input type="number" name="barang[0][harga]" class="form-control text-end hrg" min="0" placeholder="0" oninput="kalkulasi()" required>
                            </td>
                            <td>
                                <input type="text" class="form-control jml text-end bg-light" placeholder="Rp 0" readonly>
                            </td>
                            <td>
                                <button type="button" class="btn btn-sm btn-danger w-100 fw-bold" onclick="removeRow(this)">X</button>
                            </td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="3" class="text-end align-middle fs-5">Grand Total Rp.</th>
                            <th>
                                <input type="text" id="grandTotal" class="form-control text-end fw-bold fs-5 text-danger bg-light" placeholder="Rp 0" readonly>
                            </th>
                            <th></th>
                        </tr>
                    </tfoot>
                </table>

                <div class="row mt-5 text-center">
                    <!-- Tanda Tangan Pembeli -->
                    <div class="col-md-4">
                        <p class="mb-2 fw-bold text-muted">Tanda Terima,</p>
                        <div class="signature-box bg-light border rounded position-relative d-flex justify-content-center align-items-center mx-auto transition" style="height: 120px; width: 80%; cursor: pointer;">
                            <input type="file" name="ttd_pembeli" accept="image/png, image/jpeg" class="position-absolute w-100 h-100" style="opacity: 0; z-index: 2; cursor: pointer;" onchange="previewSig(this, 'imgPembeli', 'textPembeli')">
                            <span id="textPembeli" class="text-muted small"><i class="fa-solid fa-cloud-arrow-up fs-4 mb-1"></i><br>Upload TTD<br>(Opsional)</span>
                            <img id="imgPembeli" src="#" alt="TTD Pembeli" style="max-height: 100px; max-width: 100%; display: none; position: relative; z-index: 1;">
                        </div>
                        <div class="mt-3 mx-auto" style="width: 80%;">
                            <input type="text" name="nama_pembeli" class="form-control text-center border-0 border-bottom bg-transparent fw-bold" value="{{ old('nama_pembeli', 'Customer') }}" placeholder="Ketik Nama Pembeli..." required>

                            <!-- INPUT NOMOR HP (UNTUK PIN PEMBAYARAN) -->
                            <small class="text-muted d-block mt-2" style="font-size: 11px;">Nomor WA Pembeli (Untuk PIN Tagihan)</small>
                            <input type="text" name="no_hp_pembeli" class="form-control text-center border-0 border-bottom bg-transparent" value="{{ old('no_hp_pembeli') }}" placeholder="Contoh: 08123456789" required>
                        </div>
                    </div>

                    <!-- Tanda Tangan Penjual -->
                    <div class="col-md-4 offset-md-4 mt-4 mt-md-0">
                        <p class="mb-2 fw-bold text-muted">Hormat Kami,</p>
                        <div class="signature-box bg-light border rounded position-relative d-flex justify-content-center align-items-center mx-auto transition" style="height: 120px; width: 80%; cursor: pointer;">
                            <input type="file" name="ttd_penjual" accept="image/png, image/jpeg" class="position-absolute w-100 h-100" style="opacity: 0; z-index: 2; cursor: pointer;" onchange="previewSig(this, 'imgPenjual', 'textPenjual')">
                            <span id="textPenjual" class="text-muted small"><i class="fa-solid fa-cloud-arrow-up fs-4 mb-1"></i><br>Upload TTD<br>(Opsional)</span>
                            <img id="imgPenjual" src="#" alt="TTD Penjual" style="max-height: 100px; max-width: 100%; display: none; position: relative; z-index: 1;">
                        </div>
                        <div class="mt-3 mx-auto" style="width: 80%;">
                            <input type="text" name="nama_penjual" class="form-control text-center border-0 border-bottom bg-transparent fw-bold" value="{{ old('nama_penjual', 'Sancaka Express') }}" placeholder="Ketik Nama Penjual..." required>
                        </div>
                    </div>
                </div>

                <div class="text-end mt-5 border-top pt-4">
                    <button type="submit" class="btn btn-dark px-5 py-3 fw-bold">
                        <i class="fa-solid fa-paper-plane me-2"></i> Simpan & Terbitkan Link Pembayaran
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ========================================== -->
<!-- MODAL POPUP JIKA BERHASIL MENYIMPAN NOTA -->
<!-- ========================================== -->
@if(session('success_nota_no'))
<div class="modal fade" id="successModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content text-center border-0 shadow-lg">
            <div class="modal-body p-5">
                <div class="mb-4">
                    <i class="fa-solid fa-circle-check text-success" style="font-size: 5rem;"></i>
                </div>

                <h3 class="fw-bold mb-3">Berhasil!</h3>
                <p class="text-muted mb-4">{{ session('success') }}</p>

                <!-- BAGIAN LINK PEMBAYARAN -->
                <div class="text-start mb-4">
                    <label class="fw-bold small text-muted mb-1">Link Pembayaran Tagihan:</label>
                    <div class="input-group">
                        <input type="text" id="paymentLink" class="form-control bg-light" value="{{ route('nota.pay', session('success_nota_no')) }}" readonly>
                        <button class="btn btn-dark fw-bold" onclick="copyLink()">
                            <i class="fa-regular fa-copy"></i> Copy
                        </button>
                    </div>
                    <small class="text-danger mt-2 d-block"><i class="fa-solid fa-lock"></i> Kunci PIN Nota ini adalah 4 angka terakhir dari Nomor HP pembeli.</small>
                </div>

                <div class="d-flex justify-content-center gap-2">
                    <a href="{{ route('nota.download', session('success_nota_id')) }}" class="btn btn-outline-danger px-4 py-2 fw-bold" target="_blank">
                        <i class="fa-solid fa-print me-1"></i> Cetak / PDF
                    </a>
                    <button type="button" class="btn btn-secondary px-4 py-2 fw-bold" data-bs-dismiss="modal">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        var myModal = new bootstrap.Modal(document.getElementById('successModal'));
        myModal.show();
    });

    function copyLink() {
        var copyText = document.getElementById("paymentLink");
        copyText.select();
        copyText.setSelectionRange(0, 99999); // Untuk perangkat mobile
        navigator.clipboard.writeText(copyText.value);

        // Ubah text tombol sementara sebagai indikator sukses
        let btn = event.currentTarget;
        let originalHtml = btn.innerHTML;
        btn.innerHTML = '<i class="fa-solid fa-check"></i> Copied!';
        btn.classList.replace('btn-dark', 'btn-success');

        setTimeout(() => {
            btn.innerHTML = originalHtml;
            btn.classList.replace('btn-success', 'btn-dark');
        }, 2000);
    }
</script>
@endif

<!-- ========================================== -->
<!-- JAVASCRIPT UNTUK FUNGSI FORM NOTA -->
<!-- ========================================== -->
<script>
    // Mencegah submit dobel
    document.querySelector('form').addEventListener('submit', function(e) {
        let btn = this.querySelector('button[type="submit"]');
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-2"></i> Menyimpan...';
        btn.disabled = true;
    });

    let rowIdx = 1;

    // Fitur Tambah Baris
    function addRow() {
        let tr = `
        <tr>
            <td><input type="number" name="barang[${rowIdx}][banyaknya]" class="form-control text-center qty" min="1" value="1" oninput="kalkulasi()" required></td>
            <td><input type="text" name="barang[${rowIdx}][nama]" class="form-control" placeholder="Deskripsi Barang..." required></td>
            <td><input type="number" name="barang[${rowIdx}][harga]" class="form-control text-end hrg" min="0" placeholder="0" oninput="kalkulasi()" required></td>
            <td><input type="text" class="form-control jml text-end bg-light" placeholder="Rp 0" readonly></td>
            <td><button type="button" class="btn btn-sm btn-danger w-100 fw-bold" onclick="removeRow(this)">X</button></td>
        </tr>`;
        document.getElementById('tbodyItem').insertAdjacentHTML('beforeend', tr);
        rowIdx++;
    }

    // Fitur Hapus Baris
    function removeRow(btn) {
        if(document.querySelectorAll('#tbodyItem tr').length > 1) {
            btn.closest('tr').remove();
            kalkulasi();
        } else {
            alert('Nota minimal harus memiliki 1 baris barang/jasa!');
        }
    }

    // Fitur Kalkulasi Otomatis Harga x Qty
    function kalkulasi() {
        let grandTotal = 0;
        let rows = document.querySelectorAll('#tbodyItem tr');

        rows.forEach(row => {
            let qty = parseFloat(row.querySelector('.qty').value) || 0;
            let hrg = parseFloat(row.querySelector('.hrg').value) || 0;
            let total = qty * hrg;

            row.querySelector('.jml').value = formatRupiah(total);
            grandTotal += total;
        });

        document.getElementById('grandTotal').value = formatRupiah(grandTotal);
    }

    // Format angka ke format Rupiah
    function formatRupiah(angka) {
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            minimumFractionDigits: 0
        }).format(angka);
    }

    // Fitur Preview Gambar (TTD) sebelum di-upload
    function previewSig(input, imgId, textId) {
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById(imgId).src = e.target.result;
                document.getElementById(imgId).style.display = 'block';
                document.getElementById(textId).style.display = 'none';
            }
            reader.readAsDataURL(input.files[0]);
        } else {
            document.getElementById(imgId).style.display = 'none';
            document.getElementById(textId).style.display = 'block';
        }
    }
</script>

<style>
    .signature-box:hover {
        background-color: #e9ecef !important;
        border-color: #6c757d !important;
    }
</style>
@endsection
