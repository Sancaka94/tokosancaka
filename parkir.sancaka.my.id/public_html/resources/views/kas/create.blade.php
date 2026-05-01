@extends('layouts.app')

@section('content')
<!-- LOG LOG -->
<style>
    /* Tambahan efek visual agar UI lebih interaktif */
    .signature-box {
        transition: all 0.3s ease-in-out;
        background-color: #f8f9fa;
        border: 2px dashed #adb5bd !important;
    }
    .signature-box:hover {
        background-color: #e2e6ea;
        border-color: #0d6efd !important;
    }
    .table-custom-header th {
        background-color: #f1f3f5;
        color: #495057;
        font-weight: 600;
        letter-spacing: 0.5px;
    }
</style>

<div class="container mt-4 mb-5">
    <div class="card shadow-lg border-0 rounded-4">
        <div class="card-body p-4 p-md-5">
            
            <!-- HEADER PERUSAHAAN -->
            <div class="row border-bottom pb-4 mb-4 align-items-center">
                <div class="col-md-2 text-center text-md-start mb-3 mb-md-0">
                    <img src="https://tokosancaka.com/storage/uploads/sancaka.png" alt="Logo Sancaka" class="img-fluid" style="max-height: 85px;">
                </div>
                <div class="col-md-6 text-center text-md-start">
                    <h4 class="fw-bolder mb-1 text-dark">SANCAKA KARYA HUTAMA</h4>
                    <p class="mb-1 text-secondary"><i class="fas fa-map-marker-alt me-2"></i>Jl. Dr. Wahidin no. 18A (depan RSUD Soeroto Ngawi)</p>
                    <p class="mb-0 text-secondary"><i class="fas fa-phone-alt me-2"></i>Telp: 0881-9435-180</p>
                </div>
                <div class="col-md-4 text-center text-md-end mt-4 mt-md-0">
                    <h2 class="text-uppercase fw-black mb-1 text-primary">Input Kas</h2>
                    <span class="badge bg-soft-primary text-primary border border-primary px-3 py-2 rounded-pill shadow-sm">Pengeluaran & Pemasukan</span>
                </div>
            </div>

            <form action="{{ route('kas.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                
                <!-- BAGIAN INFORMASI & PEMASUKAN OTOMATIS -->
                <div class="row mb-5 bg-light p-4 rounded-3 border mx-0 shadow-sm">
                    <div class="col-lg-7 mb-3 mb-lg-0">
                        <label class="fw-bold mb-2 text-dark"><i class="fas fa-calendar-alt me-2 text-primary"></i>Rentang Waktu Laporan</label>
                        <div class="input-group shadow-sm">
                            <span class="input-group-text bg-white fw-bold">Dari</span>
                            <input type="date" class="form-control" name="tanggal_mulai" id="tanggal_mulai" value="{{ date('Y-m-d') }}" required>
                            
                            <span class="input-group-text bg-white border-start-0 border-end-0 fw-bold">s/d</span>
                            
                            <input type="date" class="form-control" name="tanggal_akhir" id="tanggal_akhir" value="{{ date('Y-m-d') }}" required>
                        </div>
                    </div>
                    <div class="col-lg-5">
                        <label class="fw-bold text-success mb-2"><i class="fas fa-coins me-2"></i>Pemasukan Parkiran (Sistem)</label>
                        <div class="input-group shadow-sm">
                            <span class="input-group-text bg-success text-white fw-bold">Rp</span>
                            
                            {{-- Input visual --}}
                            <input type="text" id="displayPemasukan" class="form-control text-end fw-bold text-success bg-white fs-5" value="Menghitung..." readonly>
                            
                            {{-- Input hidden ke controller --}}
                            <input type="hidden" name="pemasukan_sistem" id="pemasukanOtomatis" value="0">
                        </div>
                    </div>
                </div>

                <!-- BAGIAN PENGELUARAN MANUAL -->
                <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-2">
                    <h5 class="fw-bold mb-0 text-dark"><i class="fas fa-receipt me-2 text-danger"></i>Rincian Pengeluaran</h5>
                </div>
                
                <div class="table-responsive shadow-sm rounded-3 mb-5">
                    <table class="table table-hover table-bordered table-sm mb-0" id="pengeluaranTable">
                        <thead class="table-custom-header text-center align-middle">
                            <tr>
                                <th width="5%" class="py-3">NO</th>
                                <th width="55%" class="py-3">KETERANGAN PENGELUARAN</th>
                                <th width="30%" class="py-3">NOMINAL (Rp)</th>
                                <th width="10%" class="py-3">
                                    <button type="button" class="btn btn-sm btn-primary w-100 fw-bold shadow-sm" onclick="addRow()">
                                        <i class="fas fa-plus"></i> Tambah
                                    </button>
                                </th>
                            </tr>
                        </thead>
                        <tbody id="tbodyItem">
                            <tr>
                                <td class="text-center align-middle row-number fw-bold text-secondary">1</td>
                                <td>
                                    <input type="text" name="pengeluaran[0][keterangan]" class="form-control border-0 bg-transparent shadow-none" placeholder="Contoh: Bayar listrik..." required>
                                </td>
                                <td>
                                    <input type="number" name="pengeluaran[0][nominal]" class="form-control nominal text-end border-0 bg-transparent shadow-none" min="0" oninput="kalkulasi()" placeholder="0" required>
                                </td>
                                <td class="text-center align-middle">
                                    <button type="button" class="btn btn-sm btn-outline-danger w-75" onclick="removeRow(this)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr class="bg-light">
                                <th colspan="2" class="text-end align-middle text-danger fw-bold py-3">Total Pengeluaran Rp.</th>
                                <th><input type="text" id="totalPengeluaran" class="form-control text-end fw-bold text-danger bg-transparent border-0 shadow-none fs-6" readonly value="0"></th>
                                <th></th>
                            </tr>
                            <tr class="table-warning border-warning">
                                <th colspan="2" class="text-end align-middle fs-5 fw-black py-3">SISA SALDO BERSIH Rp.</th>
                                <th><input type="text" id="saldoBersih" class="form-control text-end fw-bold fs-5 bg-transparent border-0 shadow-none" readonly value="0"></th>
                                <th></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <!-- BAGIAN TANDA TANGAN (KIRI & KANAN RESPONSIVE) -->
                <div class="row mt-5 pt-3">
                    <!-- Tanda Tangan Kiri (Pembuat) -->
                    <div class="col-6 d-flex flex-column align-items-center align-items-md-start text-center text-md-start">
                        <div style="width: 100%; max-width: 280px;">
                            <p class="mb-2 fw-bold text-muted text-center">Dibuat Oleh,</p>
                            <div class="signature-box rounded position-relative d-flex justify-content-center align-items-center mx-auto" style="height: 140px; cursor: pointer; overflow: hidden;">
                                
                                {{-- Input File --}}
                                <input type="file" name="ttd_pembuat" accept="image/png, image/jpeg" class="position-absolute top-0 start-0 w-100 h-100" style="opacity: 0; z-index: 10; cursor: pointer;" onchange="previewSig(this, 'imgPembuat', 'textPembuat')">
                                
                                {{-- Teks Panduan Upload --}}
                                <div id="textPembuat" class="text-muted small text-center" style="pointer-events: none;">
                                    <i class="fas fa-cloud-upload-alt mb-2 fs-2 text-primary"></i><br>Klik Upload TTD<br><small class="text-secondary">(PNG/JPG)</small>
                                </div>
                                
                                {{-- Preview Gambar --}}
                                <img id="imgPembuat" src="#" alt="TTD Pembuat" class="position-absolute top-0 start-0 w-100 h-100" style="object-fit: contain; display: none; z-index: 5; padding: 10px;">
                            </div>
                            <div class="mt-3">
                                <input type="text" name="nama_pembuat" class="form-control text-center border-0 border-bottom border-2 border-dark bg-transparent fw-bold px-0 rounded-0 shadow-none" placeholder="Ketik Nama Admin..." value="{{ auth()->user()->name ?? '' }}" required>
                            </div>
                        </div>
                    </div>

                    <!-- Tanda Tangan Kanan (Pimpinan) -->
                    <div class="col-6 d-flex flex-column align-items-center align-items-md-end text-center text-md-end">
                        <div style="width: 100%; max-width: 280px;">
                            <p class="mb-2 fw-bold text-muted text-center">Diketahui Oleh,</p>
                            <div class="signature-box rounded position-relative d-flex justify-content-center align-items-center mx-auto" style="height: 140px; cursor: pointer; overflow: hidden;">
                                
                                {{-- Input File --}}
                                <input type="file" name="ttd_pimpinan" accept="image/png, image/jpeg" class="position-absolute top-0 start-0 w-100 h-100" style="opacity: 0; z-index: 10; cursor: pointer;" onchange="previewSig(this, 'imgPimpinan', 'textPimpinan')">
                                
                                {{-- Teks Panduan Upload --}}
                                <div id="textPimpinan" class="text-muted small text-center" style="pointer-events: none;">
                                    <i class="fas fa-cloud-upload-alt mb-2 fs-2 text-primary"></i><br>Klik Upload TTD<br><small class="text-secondary">(PNG/JPG)</small>
                                </div>
                                
                                {{-- Preview Gambar --}}
                                <img id="imgPimpinan" src="#" alt="TTD Pimpinan" class="position-absolute top-0 start-0 w-100 h-100" style="object-fit: contain; display: none; z-index: 5; padding: 10px;">
                            </div>
                            <div class="mt-3">
                                <input type="text" name="nama_pimpinan" class="form-control text-center border-0 border-bottom border-2 border-dark bg-transparent fw-bold px-0 rounded-0 shadow-none" value="Pimpinan AZKEN PARKIR" placeholder="Ketik Nama Pimpinan..." required>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- TOMBOL SIMPAN -->
                <div class="d-flex justify-content-end mt-5 pt-4 border-top">
                    <a href="{{ route('kas.index') }}" class="btn btn-light border px-4 py-2 me-3 fw-bold text-secondary">Batal</a>
                    <button type="submit" class="btn btn-primary px-5 py-2 fw-bold shadow"><i class="fas fa-paper-plane me-2"></i> Simpan Laporan</button>
                </div>

            </form>
        </div>
    </div>
</div> 

<script>
    // LOG LOG
    let rowIdx = 1;

    // === JALANKAN SAAT HALAMAN DIBUKA ===
    window.onload = function() {
        fetchPemasukan(); 
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
            <td class="text-center align-middle row-number fw-bold text-secondary"></td>
            <td><input type="text" name="pengeluaran[${rowIdx}][keterangan]" class="form-control border-0 bg-transparent shadow-none" placeholder="Keterangan pengeluaran..." required></td>
            <td><input type="number" name="pengeluaran[${rowIdx}][nominal]" class="form-control nominal text-end border-0 bg-transparent shadow-none" min="0" oninput="kalkulasi()" placeholder="0" required></td>
            <td class="text-center align-middle"><button type="button" class="btn btn-sm btn-outline-danger w-75" onclick="removeRow(this)"><i class="fas fa-trash"></i></button></td>
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