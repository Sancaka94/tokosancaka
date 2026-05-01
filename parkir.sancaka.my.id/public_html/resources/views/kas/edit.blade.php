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
                    <h2 class="text-uppercase fw-bold mt-2 mt-md-0 text-warning">Edit Kas</h2>
                    <p class="text-muted mb-0">Revisi Laporan Harian</p>
                </div>
            </div>

            <form action="{{ route('kas.update', $kas->id) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                
                <div class="row mb-4 bg-light p-3 rounded border">
                    <div class="col-md-6">
                        <div class="mb-2">
                            <label class="fw-bold">Tanggal Laporan</label>
                            <input type="date" class="form-control" name="tanggal" value="{{ $kas->tanggal }}" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-2 text-md-end">
                            <label class="fw-bold text-success">Pemasukan Parkiran (Tersimpan)</label>
                            <div class="input-group">
                                <span class="input-group-text bg-success text-white">Rp</span>
                                <input type="text" class="form-control text-end fw-bold text-success bg-white" value="{{ number_format($kas->pemasukan_sistem, 0, ',', '.') }}" readonly>
                                
                                {{-- Hidden input agar nilai tetap tersubmit dan bisa dihitung JS --}}
                                <input type="hidden" name="pemasukan_sistem" id="pemasukanOtomatis" value="{{ $kas->pemasukan_sistem }}">
                            </div>
                        </div>
                    </div>
                </div>

                <h5 class="fw-bold mb-3 border-bottom pb-2">Rincian Pengeluaran</h5>
                <table class="table table-bordered table-sm" id="pengeluaranTable">
                    <thead class="table-light text-center align-middle">
                        <tr>
                            <th width="5%">NO</th>
                            <th width="60%">KETERANGAN PENGELUARAN</th>
                            <th width="30%">NOMINAL (Rp)</th>
                            <th width="5%"><button type="button" class="btn btn-sm btn-success w-100" onclick="addRow()">+</button></th>
                        </tr>
                    </thead>
                    <tbody id="tbodyItem">
                        @foreach($kas->pengeluaran as $index => $item)
                        <tr>
                            <td class="text-center align-middle row-number">{{ $index + 1 }}</td>
                            <td><input type="text" name="pengeluaran[{{ $index }}][keterangan]" class="form-control" value="{{ $item->keterangan }}" required></td>
                            <td><input type="number" name="pengeluaran[{{ $index }}][nominal]" class="form-control nominal text-end" min="0" oninput="kalkulasi()" value="{{ $item->nominal }}" required></td>
                            <td><button type="button" class="btn btn-sm btn-danger w-100" onclick="removeRow(this)">X</button></td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="2" class="text-end align-middle text-danger">Total Pengeluaran Rp.</th>
                            <th><input type="text" id="totalPengeluaran" class="form-control text-end fw-bold text-danger bg-white" readonly value="0"></th>
                            <th></th>
                        </tr>
                        <tr class="table-warning">
                            <th colspan="2" class="text-end align-middle fs-5">SISA SALDO BERSIH Rp.</th>
                            <th><input type="text" id="saldoBersih" class="form-control text-end fw-bold fs-5 bg-white" readonly value="0"></th>
                            <th></th>
                        </tr>
                    </tfoot>
                </table>

                <!-- BAGIAN TANDA TANGAN -->
                <div class="row mt-5 text-center">
                    <div class="col-md-4">
                        <p class="mb-1">Dibuat Oleh,</p>
                        <div class="signature-box bg-light border rounded position-relative d-flex justify-content-center align-items-center mx-auto" style="height: 120px; width: 80%; cursor: pointer;">
                            <input type="file" name="ttd_pembuat" accept="image/png, image/jpeg" class="position-absolute w-100 h-100" style="opacity: 0; z-index: 2; cursor: pointer;" onchange="previewSig(this, 'imgPembuat', 'textPembuat')">
                            <span id="textPembuat" class="text-muted small" style="{{ $kas->ttd_pembuat ? 'display:none;' : '' }}"><i class="fa-solid fa-cloud-arrow-up"></i> Upload TTD Baru</span>
                            <img id="imgPembuat" src="{{ $kas->ttd_pembuat ? asset('storage/' . $kas->ttd_pembuat) : '#' }}" alt="TTD Pembuat" style="max-height: 100px; max-width: 100%; position: relative; z-index: 1; {{ $kas->ttd_pembuat ? '' : 'display:none;' }}">
                        </div>
                        <div class="mt-2 mx-auto" style="width: 80%;">
                            <input type="text" name="nama_pembuat" class="form-control text-center border-0 border-bottom bg-transparent fw-bold" value="{{ $kas->nama_pembuat }}" required>
                        </div>
                    </div>

                    <div class="col-md-4 offset-md-4">
                        <p class="mb-1">Diketahui Oleh,</p>
                        <div class="signature-box bg-light border rounded position-relative d-flex justify-content-center align-items-center mx-auto" style="height: 120px; width: 80%; cursor: pointer;">
                            <input type="file" name="ttd_pimpinan" accept="image/png, image/jpeg" class="position-absolute w-100 h-100" style="opacity: 0; z-index: 2; cursor: pointer;" onchange="previewSig(this, 'imgPimpinan', 'textPimpinan')">
                            <span id="textPimpinan" class="text-muted small" style="{{ $kas->ttd_pimpinan ? 'display:none;' : '' }}"><i class="fa-solid fa-cloud-arrow-up"></i> Upload TTD Baru</span>
                            <img id="imgPimpinan" src="{{ $kas->ttd_pimpinan ? asset('storage/' . $kas->ttd_pimpinan) : '#' }}" alt="TTD Pimpinan" style="max-height: 100px; max-width: 100%; position: relative; z-index: 1; {{ $kas->ttd_pimpinan ? '' : 'display:none;' }}">
                        </div>
                        <div class="mt-2 mx-auto" style="width: 80%;">
                            <input type="text" name="nama_pimpinan" class="form-control text-center border-0 border-bottom bg-transparent fw-bold" value="{{ $kas->nama_pimpinan }}" required>
                        </div>
                    </div>
                </div>

                <div class="text-end mt-5 border-top pt-3">
                    <a href="{{ route('kas.index') }}" class="btn btn-secondary px-4 btn-lg me-2">Batal</a>
                    <button type="submit" class="btn btn-warning px-5 btn-lg text-dark fw-bold">Update Laporan</button>
                </div>
            </form>
        </div>
    </div>
</div> 

<script>
    // Set index baris sesuai jumlah data pengeluaran yang ada
    let rowIdx = {{ count($kas->pengeluaran) }};
    
    // Jalankan kalkulasi saat halaman pertama kali diload agar nilai Total dan Saldo sesuai
    window.onload = function() {
        kalkulasi();
    };

    function addRow() {
        let tr = `
        <tr>
            <td class="text-center align-middle row-number"></td>
            <td><input type="text" name="pengeluaran[${rowIdx}][keterangan]" class="form-control" placeholder="Keterangan pengeluaran..." required></td>
            <td><input type="number" name="pengeluaran[${rowIdx}][nominal]" class="form-control nominal text-end" min="0" oninput="kalkulasi()" value="0" required></td>
            <td><button type="button" class="btn btn-sm btn-danger w-100" onclick="removeRow(this)">X</button></td>
        </tr>`;
        document.getElementById('tbodyItem').insertAdjacentHTML('beforeend', tr);
        rowIdx++;
        updateRowNumbers();
    }

    function removeRow(btn) {
        if(document.querySelectorAll('#tbodyItem tr').length > 1) {
            btn.closest('tr').remove();
            updateRowNumbers();
            kalkulasi();
        } else {
            alert('Minimal harus ada 1 form pengeluaran (Bisa diisi 0 jika tidak ada).');
        }
    }

    function updateRowNumbers() {
        let rows = document.querySelectorAll('#tbodyItem tr');
        rows.forEach((row, index) => {
            row.querySelector('.row-number').innerText = index + 1;
        });
    }

    function kalkulasi() {
        let totalPengeluaran = 0;
        let rows = document.querySelectorAll('#tbodyItem tr');
        
        rows.forEach(row => {
            let nom = parseFloat(row.querySelector('.nominal').value) || 0;
            totalPengeluaran += nom;
        });

        let pemasukan = parseFloat(document.getElementById('pemasukanOtomatis').value) || 0;
        let saldoBersih = pemasukan - totalPengeluaran;

        document.getElementById('totalPengeluaran').value = formatRupiah(totalPengeluaran);
        document.getElementById('saldoBersih').value = formatRupiah(saldoBersih);

        if(saldoBersih < 0) {
            document.getElementById('saldoBersih').classList.add('text-danger');
        } else {
            document.getElementById('saldoBersih').classList.remove('text-danger');
        }
    }

    function formatRupiah(angka) {
        let isNegative = angka < 0;
        angka = Math.abs(angka);
        let formatted = new Intl.NumberFormat('id-ID').format(angka);
        return isNegative ? "- " + formatted : formatted;
    }

    function previewSig(input, imgId, textId) {
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById(imgId).src = e.target.result;
                document.getElementById(imgId).style.display = 'block';
                document.getElementById(textId).style.display = 'none';
            }
            reader.readAsDataURL(input.files[0]);
        }
    }
</script>
@endsection