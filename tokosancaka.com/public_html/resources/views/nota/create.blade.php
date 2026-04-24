@extends('layouts.app')

@section('content')
<div class="container mt-4 mb-5">
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
                        <label>NOTA NO.</label>
                        <input type="text" class="form-control fw-bold" name="no_nota" value="{{ $no_nota }}" readonly>
                    </div>
                    <div class="col-md-4 offset-md-4">
                        <div class="mb-2">
                            <label>Tanggal</label>
                            <input type="date" class="form-control" name="tanggal" value="{{ date('Y-m-d') }}" required>
                        </div>
                        <div>
                            <label>Kepada</label>
                            <input type="text" class="form-control" name="kepada" placeholder="Nama Pelanggan / Perusahaan" required>
                        </div>
                    </div>
                </div>

                <table class="table table-bordered table-sm" id="notaTable">
                    <thead class="table-light text-center align-middle">
                        <tr>
                            <th width="10%">BANYAKNYA</th>
                            <th width="40%">NAMA BARANG</th>
                            <th width="20%">HARGA</th>
                            <th width="25%">JUMLAH</th>
                            <th width="5%"><button type="button" class="btn btn-sm btn-success w-100" onclick="addRow()">+</button></th>
                        </tr>
                    </thead>
                    <tbody id="tbodyItem">
                        <tr>
                            <td><input type="number" name="barang[0][banyaknya]" class="form-control qty" min="1" value="1" oninput="kalkulasi()" required></td>
                            <td><input type="text" name="barang[0][nama]" class="form-control" required></td>
                            <td><input type="number" name="barang[0][harga]" class="form-control hrg" min="0" oninput="kalkulasi()" required></td>
                            <td><input type="text" class="form-control jml text-end" readonly></td>
                            <td><button type="button" class="btn btn-sm btn-danger w-100" onclick="removeRow(this)">X</button></td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="3" class="text-end align-middle">Jumlah Rp.</th>
                            <th><input type="text" id="grandTotal" class="form-control text-end fw-bold" readonly></th>
                            <th></th>
                        </tr>
                    </tfoot>
                </table>

                <div class="row mt-5 text-center">
                    <div class="col-md-4">
                        <p class="mb-1">Tanda Terima</p>
                        <div class="signature-box bg-light border rounded position-relative d-flex justify-content-center align-items-center mx-auto" style="height: 120px; width: 80%; cursor: pointer;">
                            <input type="file" name="ttd_pembeli" accept="image/png, image/jpeg" class="position-absolute w-100 h-100" style="opacity: 0; z-index: 2; cursor: pointer;" onchange="previewSig(this, 'imgPembeli', 'textPembeli')">
                            <span id="textPembeli" class="text-muted small"><i class="fa-solid fa-cloud-arrow-up"></i> Upload TTD<br>(PNG/JPG)</span>
                            <img id="imgPembeli" src="#" alt="TTD Pembeli" style="max-height: 100px; max-width: 100%; display: none; position: relative; z-index: 1;">
                        </div>
                        <div class="mt-2 mx-auto" style="width: 80%;">
                            <input type="text" name="nama_pembeli" class="form-control text-center border-0 border-bottom bg-transparent fw-bold" placeholder="Ketik Nama Pembeli..." required>
                        </div>
                    </div>

                    <div class="col-md-4 offset-md-4">
                        <p class="mb-1">Hormat Kami,</p>
                        <div class="signature-box bg-light border rounded position-relative d-flex justify-content-center align-items-center mx-auto" style="height: 120px; width: 80%; cursor: pointer;">
                            <input type="file" name="ttd_penjual" accept="image/png, image/jpeg" class="position-absolute w-100 h-100" style="opacity: 0; z-index: 2; cursor: pointer;" onchange="previewSig(this, 'imgPenjual', 'textPenjual')">
                            <span id="textPenjual" class="text-muted small"><i class="fa-solid fa-cloud-arrow-up"></i> Upload TTD<br>(PNG/JPG)</span>
                            <img id="imgPenjual" src="#" alt="TTD Penjual" style="max-height: 100px; max-width: 100%; display: none; position: relative; z-index: 1;">
                        </div>
                        <div class="mt-2 mx-auto" style="width: 80%;">
                            <input type="text" name="nama_penjual" class="form-control text-center border-0 border-bottom bg-transparent fw-bold" value="Sancaka Express" placeholder="Ketik Nama Penjual..." required>
                        </div>
                    </div>
                </div>

                <div class="text-end mt-5 border-top pt-3">
                    <button type="submit" class="btn btn-primary px-5 btn-lg">Simpan & Cetak Nota</button>
                </div>
            </form>
        </div>
    </div>
</div> @if(session('success_nota_id'))
<div class="modal fade" id="successModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content text-center border-0 shadow-lg">
            <div class="modal-body p-5">
                <div class="mb-4">
                    <i class="fa-solid fa-circle-check text-success" style="font-size: 5rem;"></i>
                </div>
                
                <h3 class="fw-bold mb-3">Berhasil!</h3>
                <p class="text-muted mb-4">{{ session('success') }}</p>
                
                <div class="d-flex justify-content-center gap-3">
                    <a href="{{ route('nota.download', session('success_nota_id')) }}" class="btn btn-primary px-4 py-2 fw-bold" target="_blank">
                        <i class="fa-solid fa-print me-1"></i> Cetak / Download
                    </a>
                    
                    <button type="button" class="btn btn-outline-secondary px-4 py-2 fw-bold" data-bs-dismiss="modal">
                        Buat Nota Baru
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
</script>
@endif
<script>
    // Fitur Tambah Baris
    let rowIdx = 1;
    function addRow() {
        let tr = `
        <tr>
            <td><input type="number" name="barang[${rowIdx}][banyaknya]" class="form-control qty" min="1" value="1" oninput="kalkulasi()" required></td>
            <td><input type="text" name="barang[${rowIdx}][nama]" class="form-control" required></td>
            <td><input type="number" name="barang[${rowIdx}][harga]" class="form-control hrg" min="0" oninput="kalkulasi()" required></td>
            <td><input type="text" class="form-control jml text-end" readonly></td>
            <td><button type="button" class="btn btn-sm btn-danger w-100" onclick="removeRow(this)">X</button></td>
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
            alert('Minimal harus ada 1 barang!');
        }
    }

    // Fitur Kalkulasi Otomatis
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

    function formatRupiah(angka) {
        return new Intl.NumberFormat('id-ID').format(angka);
    }

    // Fitur Preview TTD Upload
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
@endsection