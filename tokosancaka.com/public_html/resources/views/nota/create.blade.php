@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <div class="card shadow">
        <div class="card-body">
            <div class="row border-bottom pb-3 mb-4">
                <div class="col-md-6">
                    <h4 class="fw-bold mb-0">SANCAKA KARYA HUTAMA</h4>
                    <p class="mb-0">Jl. Dr. Wahidin no. 18A (depan RSUD Soeroto Ngawi)</p>
                    <p class="mb-0">Telp: 0881-9435-180</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <h2 class="text-uppercase fw-bold">Nota</h2>
                </div>
            </div>

            <form action="{{ route('nota.store') }}" method="POST">
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
                    <div class="col-4">
                        <p>Tanda Terima</p>
                        <br><br><br>
                        <p>( ............................ )</p>
                    </div>
                    <div class="col-4 offset-4">
                        <p>Hormat Kami,</p>
                        <br><br><br>
                        <p>( ............................ )</p>
                    </div>
                </div>

                <div class="text-end mt-4">
                    <button type="submit" class="btn btn-primary px-5">Simpan Nota</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
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

    function removeRow(btn) {
        if(document.querySelectorAll('#tbodyItem tr').length > 1) {
            btn.closest('tr').remove();
            kalkulasi();
        } else {
            alert('Minimal harus ada 1 barang!');
        }
    }

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
</script>
@endsection