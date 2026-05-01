@extends('layouts.app')

@section('content')
<div class="container mt-4 mb-5">
    <div class="card shadow border-0">
        <div class="card-body p-5">
            <!-- HEADER PERUSAHAAN -->
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
                    <h2 class="text-uppercase fw-bold mt-2 mt-md-0 text-primary">Input Kas</h2>
                    <p class="text-muted mb-0">Pengeluaran & Pemasukan Periode</p>
                </div>
            </div>

            <form action="{{ route('kas.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                
                <!-- BAGIAN INFORMASI & PEMASUKAN OTOMATIS -->
                <div class="row mb-4 bg-light p-3 rounded border mx-0">
                    <div class="col-md-7">
                        <label class="fw-bold mb-2">Rentang Waktu Laporan</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white">Dari</span>
                            <input type="date" class="form-control" name="tanggal_mulai" id="tanggal_mulai" value="{{ date('Y-m-d') }}" required>
                            
                            <span class="input-group-text bg-white border-start-0 border-end-0">s/d</span>
                            
                            <input type="date" class="form-control" name="tanggal_akhir" id="tanggal_akhir" value="{{ date('Y-m-d') }}" required>
                        </div>
                    </div>
                    <div class="col-md-5 mt-3 mt-md-0">
                        <label class="fw-bold text-success mb-2">Pemasukan Parkiran (Otomatis)</label>
                        <div class="input-group">
                            <span class="input-group-text bg-success text-white">Rp</span>
                            
                            {{-- Input visual --}}
                            <input type="text" id="displayPemasukan" class="form-control text-end fw-bold text-success bg-white" value="Menghitung..." readonly>
                            
                            {{-- Input hidden ke controller --}}
                            <input type="hidden" name="pemasukan_sistem" id="pemasukanOtomatis" value="0">
                        </div>
                    </div>
                </div>

                <!-- BAGIAN PENGELUARAN MANUAL -->
                <h5 class="fw-bold mb-3 border-bottom pb-2">Rincian Pengeluaran</h5>
                <div class="table-responsive">
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
                            <tr>
                                <td class="text-center align-middle row-number">1</td>
                                <td><input type="text" name="pengeluaran[0][keterangan]" class="form-control" placeholder="Contoh: Bayar listrik..." required></td>
                                <td><input type="number" name="pengeluaran[0][nominal]" class="form-control nominal text-end" min="0" oninput="kalkulasi()" placeholder="0" required></td>
                                <td><button type="button" class="btn btn-sm btn-danger w-100" onclick="removeRow(this)">X</button></td>
                            </tr>
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
                </div>

                <!-- BAGIAN TANDA TANGAN -->
                <div class="d-flex justify-content-between mt-5 px-3 text-center">
                    
                    <!-- Tanda Tangan Kiri (Pembuat) -->
                    <div style="width: 280px;">
                        <p class="mb-2 fw-bold text-muted">Dibuat Oleh,</p>
                        <div class="bg-light border border-2 border-secondary border-opacity-25 rounded position-relative d-flex justify-content-center align-items-center mx-auto" style="height: 130px; cursor: pointer; overflow: hidden; border-style: dashed !important;">
                            
                            {{-- Input File (Z-index paling tinggi, w-100 h-100, top-0 start-0 agar menutupi seluruh kotak dan BISA DIKLIK) --}}
                            <input type="file" name="ttd_pembuat" accept="image/png, image/jpeg" class="position-absolute top-0 start-0 w-100 h-100" style="opacity: 0; z-index: 10; cursor: pointer;" onchange="previewSig(this, 'imgPembuat', 'textPembuat')">
                            
                            {{-- Teks Panduan Upload --}}
                            <div id="textPembuat" class="text-muted small" style="pointer-events: none;">
                                <i class="fas fa-cloud-upload-alt mb-1 fs-3 text-primary"></i><br>Klik Upload TTD<br><small>(PNG/JPG)</small>
                            </div>
                            
                            {{-- Preview Gambar --}}
                            <img id="imgPembuat" src="#" alt="TTD Pembuat" class="position-absolute top-0 start-0 w-100 h-100" style="object-fit: contain; display: none; z-index: 5; padding: 5px;">
                            
                        </div>
                        <div class="mt-2">
                            <input type="text" name="nama_pembuat" class="form-control text-center border-0 border-bottom border-dark bg-transparent fw-bold px-0 rounded-0" placeholder="Ketik Nama Admin..." value="{{ auth()->user()->name ?? '' }}" required>
                        </div>
                    </div>

                    <!-- Tanda Tangan Kanan (Pimpinan) -->
                    <div style="width: 280px;">
                        <p class="mb-2 fw-bold text-muted">Diketahui Oleh,</p>
                        <div class="bg-light border border-2 border-secondary border-opacity-25 rounded position-relative d-flex justify-content-center align-items-center mx-auto" style="height: 130px; cursor: pointer; overflow: hidden; border-style: dashed !important;">
                            
                            {{-- Input File --}}
                            <input type="file" name="ttd_pimpinan" accept="image/png, image/jpeg" class="position-absolute top-0 start-0 w-100 h-100" style="opacity: 0; z-index: 10; cursor: pointer;" onchange="previewSig(this, 'imgPimpinan', 'textPimpinan')">
                            
                            {{-- Teks Panduan Upload --}}
                            <div id="textPimpinan" class="text-muted small" style="pointer-events: none;">
                                <i class="fas fa-cloud-upload-alt mb-1 fs-3 text-primary"></i><br>Klik Upload TTD<br><small>(PNG/JPG)</small>
                            </div>
                            
                            {{-- Preview Gambar --}}
                            <img id="imgPimpinan" src="#" alt="TTD Pimpinan" class="position-absolute top-0 start-0 w-100 h-100" style="object-fit: contain; display: none; z-index: 5; padding: 5px;">
                            
                        </div>
                        <div class="mt-2">
                            <input type="text" name="nama_pimpinan" class="form-control text-center border-0 border-bottom border-dark bg-transparent fw-bold px-0 rounded-0" value="Pimpinan AZKEN PARKIR" placeholder="Ketik Nama Pimpinan..." required>
                        </div>
                    </div>
                    
                </div>

                <!-- TOMBOL SIMPAN -->
                <div class="text-end mt-5 border-top pt-4">
                    <a href="{{ route('kas.index') }}" class="btn btn-secondary px-4 btn-lg me-2">Batal</a>
                    <button type="submit" class="btn btn-primary px-5 btn-lg fw-bold"><i class="fas fa-save me-2"></i> Simpan Laporan Kas</button>
                </div>

            </form>
        </div>
    </div>
</div> 

<script>
    let rowIdx = 1;

    // === JALANKAN SAAT HALAMAN DIBUKA ===
    window.onload = function() {
        fetchPemasukan(); // Langsung tarik data hari ini saat halaman dimuat
    };

    // === FITUR AJAX AMBIL DATA PEMASUKAN ===
    document.getElementById('tanggal_mulai').addEventListener('change', fetchPemasukan);
    document.getElementById('tanggal_akhir').addEventListener('change', fetchPemasukan);

    function fetchPemasukan() {
        let tglMulai = document.getElementById('tanggal_mulai').value;
        let tglAkhir = document.getElementById('tanggal_akhir').value;

        if (tglMulai && tglAkhir) {
            let displayInput = document.getElementById('displayPemasukan');
            displayInput.value = 'Menghitung...';

            fetch('{{ route("kas.getPemasukan") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    tanggal_mulai: tglMulai,
                    tanggal_akhir: tglAkhir
                })
            })
            .then(response => response.json())
            .then(data => {
                document.getElementById('pemasukanOtomatis').value = data.total;
                displayInput.value = formatRupiah(data.total);
                kalkulasi(); 
            })
            .catch(error => {
                console.error('Error fetching data:', error);
                displayInput.value = 'Gagal memuat';
            });
        }
    }

    // === FITUR DINAMIS BARIS ===
    function addRow() {
        let tr = `
        <tr>
            <td class="text-center align-middle row-number"></td>
            <td><input type="text" name="pengeluaran[${rowIdx}][keterangan]" class="form-control" placeholder="Keterangan pengeluaran..." required></td>
            <td><input type="number" name="pengeluaran[${rowIdx}][nominal]" class="form-control nominal text-end" min="0" oninput="kalkulasi()" placeholder="0" required></td>
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

    // === KALKULASI & PREVIEW ===
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