@extends('layouts.admin')
@section('content')
<div class="container py-5">
    <form action="{{ route('invoice.store') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="card p-4">
            <h3>Buat Invoice Baru</h3>
            <div class="row mb-3">
                <div class="col-md-6">
                    <label>No. Invoice</label>
                    <input type="text" name="invoice_no" value="{{ $invoiceNo }}" class="form-control" readonly>
                </div>
                <div class="col-md-6">
                    <label>Tanggal</label>
                    <input type="date" name="date" value="{{ date('Y-m-d') }}" class="form-control">
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label>Nama Penerima</label>
                    <input type="text" name="customer_name" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <label>Nama Perusahaan</label>
                    <input type="text" name="company_name" class="form-control">
                </div>
            </div>

            <table class="table border mt-4" id="itemTable">
                <thead class="bg-primary text-white">
                    <tr>
                        <th>Deskripsi</th>
                        <th>Qty</th>
                        <th>Harga</th>
                        <th>Total</th>
                        <th>#</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><input type="text" name="items[0][description]" class="form-control" required></td>
                        <td><input type="number" name="items[0][qty]" class="form-control qty" value="1"></td>
                        <td><input type="number" name="items[0][price]" class="form-control price" value="0"></td>
                        <td><input type="text" name="items[0][total]" class="form-control total" readonly></td>
                        <td><button type="button" class="btn btn-danger removeRow">x</button></td>
                    </tr>
                </tbody>
            </table>
            <button type="button" id="addRow" class="btn btn-secondary mb-3">+ Tambah Baris</button>

            <div class="row">
                <div class="col-md-6">
                    <label>Upload Tanda Tangan (PNG Transparan Disarankan)</label>
                    <input type="file" name="signature" class="form-control">
                </div>
                <div class="col-md-6 text-end">
                    <h4>Subtotal: <span id="displaySubtotal">0</span></h4>
                    <input type="hidden" name="subtotal" id="inputSubtotal">
                </div>
            </div>
            <button type="submit" class="btn btn-success mt-4">Simpan & Cetak PDF</button>
        </div>
    </form>
</div>

<script>
    // Script sederhana untuk tambah baris dan hitung total otomatis
    document.getElementById('addRow').addEventListener('click', function() {
        let table = document.getElementById('itemTable').getElementsByTagName('tbody')[0];
        let rowCount = table.rows.length;
        let row = table.insertRow(rowCount);
        row.innerHTML = `
            <td><input type="text" name="items[${rowCount}][description]" class="form-control" required></td>
            <td><input type="number" name="items[${rowCount}][qty]" class="form-control qty" value="1"></td>
            <td><input type="number" name="items[${rowCount}][price]" class="form-control price" value="0"></td>
            <td><input type="text" name="items[${rowCount}][total]" class="form-control total" readonly></td>
            <td><button type="button" class="btn btn-danger removeRow">x</button></td>
        `;
    });

    document.addEventListener('input', function(e) {
        if(e.target.classList.contains('qty') || e.target.classList.contains('price')) {
            let row = e.target.closest('tr');
            let qty = row.querySelector('.qty').value;
            let price = row.querySelector('.price').value;
            let total = qty * price;
            row.querySelector('.total').value = total;
            calculateSubtotal();
        }
    });

    function calculateSubtotal() {
        let totals = document.querySelectorAll('.total');
        let subtotal = 0;
        totals.forEach(t => subtotal += parseFloat(t.value || 0));
        document.getElementById('displaySubtotal').innerText = subtotal.toLocaleString();
        document.getElementById('inputSubtotal').value = subtotal;
    }
</script>
@endsection
