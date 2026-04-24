@extends('layouts.app')

@section('content')
<div class="container mt-4 mb-5">
    <div class="card shadow">
        <div class="card-body p-5">
            <div class="row border-bottom pb-3 mb-4 align-items-center">
                <div class="col-md-2">
                    <img src="https://tokosancaka.com/storage/uploads/sancaka.png" alt="Logo" class="img-fluid" style="max-height: 80px;">
                </div>
                <div class="col-md-10">
                    <h4 class="fw-bold mb-0">EDIT NOTA: {{ $nota->no_nota }}</h4>
                </div>
            </div>

            <form action="{{ route('nota.update', $nota->id) }}" method="POST" enctype="multipart/form-data">
                @csrf @method('PUT')
                
                <div class="row mb-4">
                    <div class="col-md-4">
                        <label>Kepada</label>
                        <input type="text" class="form-control" name="kepada" value="{{ $nota->kepada }}" required>
                    </div>
                    <div class="col-md-4 offset-md-4">
                        <label>Tanggal</label>
                        <input type="date" class="form-control" name="tanggal" value="{{ $nota->tanggal }}" required>
                    </div>
                </div>

                <table class="table table-bordered table-sm" id="notaTable">
                    <thead class="table-light text-center">
                        <tr>
                            <th width="10%">QTY</th>
                            <th width="45%">NAMA BARANG</th>
                            <th width="20%">HARGA</th>
                            <th width="20%">JUMLAH</th>
                            <th width="5%"><button type="button" class="btn btn-sm btn-success w-100" onclick="addRow()">+</button></th>
                        </tr>
                    </thead>
                    <tbody id="tbodyItem">
                        @foreach($nota->items as $index => $item)
                        <tr>
                            <td><input type="number" name="barang[{{$index}}][banyaknya]" class="form-control qty" value="{{$item->banyaknya}}" oninput="kalkulasi()" required></td>
                            <td><input type="text" name="barang[{{$index}}][nama]" class="form-control" value="{{$item->nama_barang}}" required></td>
                            <td><input type="number" name="barang[{{$index}}][harga]" class="form-control hrg" value="{{$item->harga}}" oninput="kalkulasi()" required></td>
                            <td><input type="text" class="form-control jml text-end" value="{{ number_format($item->jumlah, 0, ',', '.') }}" readonly></td>
                            <td><button type="button" class="btn btn-sm btn-danger w-100" onclick="removeRow(this)">X</button></td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>

                <div class="text-end mt-4">
                    <button type="submit" class="btn btn-warning px-5">Simpan Perubahan</button>
                    <a href="{{ route('nota.index') }}" class="btn btn-secondary">Batal</a>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection