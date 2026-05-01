@extends('layouts.app')

@section('content')
<!-- LOG LOG -->
<style>
    /* Menyamakan style dengan halaman Create */
    .signature-box {
        transition: all 0.3s ease-in-out;
        background-color: #f8f9fa;
        border: 2px dashed #adb5bd !important;
        width: 100%;
        height: 140px;
        display: flex;
        justify-content: center;
        align-items: center;
        position: relative;
        overflow: hidden;
        border-radius: 8px;
        margin: 0 auto;
    }
    .signature-box:hover {
        background-color: #e2e6ea;
        border-color: #ffc107 !important;
    }
    .table-custom-header th {
        background-color: #f1f3f5;
        color: #495057;
        font-weight: 600;
    }
</style>

<div class="container-fluid mt-4 mb-5">
    <div class="card shadow border-0 rounded-3">
        <div class="card-body p-4">
            
            <!-- HEADER PERUSAHAAN (BULLETPROOF FLEXBOX) -->
            <div style="display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; border-bottom: 2px solid #eee; padding-bottom: 20px; margin-bottom: 30px;">
                
                <!-- Kiri: Logo & Nama -->
                <div style="display: flex; align-items: center; gap: 20px; flex: 1; min-width: 350px;">
                    <img src="https://tokosancaka.com/storage/uploads/sancaka.png" alt="Logo Sancaka" style="height: 85px; width: auto;">
                    <div>
                        <h4 style="font-weight: bold; margin: 0; color: #333; font-size: 1.2rem;">SANCAKA KARYA HUTAMA</h4>
                        <p style="margin: 5px 0 0 0; color: #666; font-size: 14px;"><i class="fas fa-map-marker-alt me-2"></i>Jl. Dr. Wahidin no. 18A (depan RSUD Soeroto Ngawi)</p>
                        <p style="margin: 0; color: #666; font-size: 14px;"><i class="fas fa-phone-alt me-2"></i>Telp: 0881-9435-180</p>
                    </div>
                </div>

                <!-- Kanan: Judul Laporan -->
                <div style="text-align: right; min-width: 200px; margin-top: 10px;">
                    <h2 style="font-weight: 900; color: #ffc107; margin: 0 0 10px 0; text-transform: uppercase; font-size: 1.8rem;">Edit Kas</h2>
                    <span style="background: #fff3cd; color: #856404; border: 1px solid #ffeeba; padding: 6px 15px; border-radius: 20px; font-weight: bold; font-size: 13px;">Revisi Laporan Keuangan</span>
                </div>
            </div>

            <form action="{{ route('kas.update', $kas->id) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                
                <!-- BAGIAN INFORMASI & PEMASUKAN OTOMATIS -->
                <div style="display: flex; flex-wrap: wrap; gap: 20px; background-color: #f8f9fa; padding: 20px; border-radius: 8px; border: 1px solid #dee2e6; margin-bottom: 30px;">
                    
                    <!-- Rentang Waktu -->
                    <div style="flex: 1; min-width: 300px;">
                        <label style="font-weight: bold; margin-bottom: 8px; display: block; color: #333;"><i class="fas fa-calendar-alt me-2 text-warning"></i>Rentang Waktu Laporan</label>
                        <div style="display: flex; align-items: stretch; border: 1px solid #ced4da; border-radius: 4px; overflow: hidden; background: #fff;">
                            <span style="background: #e9ecef; padding: 8px 15px; border-right: 1px solid #ced4da; font-weight: bold; display: flex; align-items: center;">Dari</span>
                            <input type="date" name="tanggal_mulai" id="tanggal_mulai" value="{{ $kas->tanggal_mulai }}" style="border: none; padding: 8px 12px; flex: 1; outline: none;" required>
                            
                            <span style="background: #e9ecef; padding: 8px 15px; border-left: 1px solid #ced4da; border-right: 1px solid #ced4da; font-weight: bold; display: flex; align-items: center;">s/d</span>
                            <input type="date" name="tanggal_akhir" id="tanggal_akhir" value="{{ $kas->tanggal_akhir }}" style="border: none; padding: 8px 12px; flex: 1; outline: none;" required>
                        </div>
                    </div>

                    <!-- Pemasukan Parkiran -->
                    <div style="flex: 1; min-width: 300px;">
                        <label style="font-weight: bold; margin-bottom: 8px; display: block; color: #198754;"><i class="fas fa-coins me-2"></i>Pemasukan Parkiran (Sistem)</label>
                        <div style="display: flex; align-items: stretch; border: 1px solid #198754; border-radius: 4px; overflow: hidden; background: #fff;">
                            <span style="background: #198754; color: white; padding: 8px 15px; font-weight: bold; display: flex; align-items: center;">Rp</span>
                            
                            <input type="text" id="displayPemasukan" style="border: none; padding: 8px 12px; flex: 1; text-align: right; font-weight: bold; color: #198754; font-size: 1.1rem; outline: none;" value="{{ number_format($kas->pemasukan_sistem, 0, ',', '.') }}" readonly>
                            
                            <input type="hidden" name="pemasukan_sistem" id="pemasukanOtomatis" value="{{ $kas->pemasukan_sistem }}">
                        </div>
                        <small class="text-muted" style="font-size: 11px;">*Ubah tanggal di samping jika ingin update saldo parkir secara otomatis dari sistem.</small>
                    </div>
                </div>

                <!-- BAGIAN PENGELUARAN MANUAL -->
                <div style="border-bottom: 2px solid #eee; padding-bottom: 10px; margin-bottom: 15px;">
                    <h5 style="font-weight: bold; color: #333; margin: 0;"><i class="fas fa-receipt me-2 text-danger"></i>Rincian Pengeluaran</h5>
                </div>
                
                <div class="table-responsive shadow-sm rounded-3 mb-5" style="border: 1px solid #dee2e6;">
                    <table class="table table-hover table-bordered table-sm mb-0" id="pengeluaranTable">
                        <thead class="table-custom-header text-center align-middle">
                            <tr>
                                <th width="5%" style="padding: 12px;">NO</th>
                                <th width="55%" style="padding: 12px;">KETERANGAN PENGELUARAN</th>
                                <th width="30%" style="padding: 12px;">NOMINAL (Rp)</th>
                                <th width="10%" style="padding: 12px;">
                                    <button type="button" class="btn btn-sm btn-success w-100 fw-bold shadow-sm" onclick="addRow()">
                                        + Tambah
                                    </button>
                                </th>
                            </tr>
                        </thead>
                        <tbody id="tbodyItem">
                            @forelse($kas->pengeluaran as $index => $item)
                            <tr>
                                <td class="text-center align-middle row-number fw-bold text-secondary">{{ $index + 1 }}</td>
                                <td>
                                    <input type="text" name="pengeluaran[{{ $index }}][keterangan]" class="form-control border-0 bg-transparent shadow-none" value="{{ $item->keterangan }}" required>
                                </td>
                                <td>
                                    <input type="number" name="pengeluaran[{{ $index }}][nominal]" class="form-control nominal text-end border-0 bg-transparent shadow-none" min="0" oninput="kalkulasi()" value="{{ $item->nominal }}" required>
                                </td>
                                <td class="text-center align-middle">
                                    <button type="button" class="btn btn-sm btn-outline-danger w-75" onclick="removeRow(this)">Hapus</button>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td class="text-center align-middle row-number fw-bold text-secondary">1</td>
                                <td>
                                    <input type="text" name="pengeluaran[0][keterangan]" class="form-control border-0 bg-transparent shadow-none" placeholder="Contoh: Bayar listrik..." required>
                                </td>
                                <td>
                                    <input type="number" name="pengeluaran[0][nominal]" class="form-control nominal text-end border-0 bg-transparent shadow-none" min="0" oninput="kalkulasi()" placeholder="0" required>
                                </td>
                                <td class="text-center align-middle">
                                    <button type="button" class="btn btn-sm btn-outline-danger w-75" onclick="removeRow(this)">Hapus</button>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                        <tfoot>
                            <tr style="background-color: #f8f9fa;">
                                <th colspan="2" class="text-end align-middle text-danger fw-bold" style="padding: 15px;">Total Pengeluaran Rp.</th>
                                <th style="padding: 0;"><input type="text" id="totalPengeluaran" class="form-control text-end fw-bold text-danger bg-transparent border-0 shadow-none h-100" style="font-size: 1.1rem;" readonly value="0"></th>
                                <th></th>
                            </tr>
                            <tr style="background-color: #fff3cd;">
                                <th colspan="2" class="text-end align-middle fw-bold" style="padding: 15px; font-size: 1.2rem; color: #856404;">SISA SALDO BERSIH Rp.</th>
                                <th style="padding: 0;"><input type="text" id="saldoBersih" class="form-control text-end fw-bold bg-transparent border-0 shadow-none h-100" style="font-size: 1.2rem; color: #856404;" readonly value="0"></th>
                                <th></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <!-- BAGIAN TANDA TANGAN (KIRI & KANAN FIXED) -->
                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-top: 50px; padding-top: 20px; border-top: 1px solid #eee; flex-wrap: wrap; gap: 20px;">
                    
                    <!-- Tanda Tangan Kiri (Pembuat) -->
                    <div style="width: 280px; text-align: center;">
                        <p style="font-weight: bold; color: #6c757d; margin-bottom: 10px;">Dibuat Oleh,</p>
                        <div class="signature-box">
                            <input type="file" name="ttd_pembuat" accept="image/png, image/jpeg" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; opacity: 0; z-index: 10; cursor: pointer;" onchange="previewSig(this, 'imgPembuat', 'textPembuat')">
                            
                            <div id="textPembuat" style="color: #6c757d; font-size: 14px; pointer-events: none; {{ $kas->ttd_pembuat ? 'display:none;' : '' }}">
                                <span style="font-size: 24px; color: #ffc107; display: block; margin-bottom: 5px;"><i class="fas fa-cloud-upload-alt"></i></span>
                                Klik Ubah TTD<br><small>(PNG/JPG)</small>
                            </div>
                            
                            <img id="imgPembuat" src="{{ $kas->ttd_pembuat ? asset('storage/' . $kas->ttd_pembuat) : '#' }}" alt="TTD Pembuat" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; object-fit: contain; z-index: 5; padding: 10px; {{ $kas->ttd_pembuat ? '' : 'display:none;' }}">
                        </div>
                        <div style="margin-top: 15px;">
                            <input type="text" name="nama_pembuat" class="form-control text-center bg-transparent shadow-none" style="border: none; border-bottom: 2px solid #333; font-weight: bold; border-radius: 0; padding: 5px 0;" value="{{ $kas->nama_pembuat }}" required>
                        </div>
                    </div>

                    <!-- Tanda Tangan Kanan (Pimpinan) -->
                    <div style="width: 280px; text-align: center;">
                        <p style="font-weight: bold; color: #6c757d; margin-bottom: 10px;">Diketahui Oleh,</p>
                        <div class="signature-box">
                            <input type="file" name="ttd_pimpinan" accept="image/png, image/jpeg" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; opacity: 0; z-index: 10; cursor: pointer;" onchange="previewSig(this, 'imgPimpinan', 'textPimpinan')">
                            
                            <div id="textPimpinan" style="color: #6c757d; font-size: 14px; pointer-events: none; {{ $kas->ttd_pimpinan ? 'display:none;' : '' }}">
                                <span style="font-size: 24px; color: #ffc107; display: block; margin-bottom: 5px;"><i class="fas fa-cloud-upload-alt"></i></span>
                                Klik Ubah TTD<br><small>(PNG/JPG)</small>
                            </div>
                            
                            <img id="imgPimpinan" src="{{ $kas->ttd_pimpinan ? asset('storage/' . $kas->ttd_pimpinan) : '#' }}" alt="TTD Pimpinan" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; object-fit: contain; z-index: 5; padding: 10px; {{ $kas->ttd_pimpinan ? '' : 'display:none;' }}">
                        </div>
                        <div style="margin-top: 15px;">
                            <input type="text" name="nama_pimpinan" class="form-control text-center bg-transparent shadow-none" style="border: none; border-bottom: 2px solid #333; font-weight: bold; border-radius: 0; padding: 5px 0;" value="{{ $kas->nama_pimpinan }}" required>
                        </div>
                    </div>
                    
                </div>

                <!-- TOMBOL SIMPAN -->
                <div style="text-align: right; margin-top: 40px; padding-top: 20px; border-top: 1px solid #eee;">
                    <a href="{{ route('kas.index') }}" class="btn btn-light" style="border: 1px solid #ccc; padding: 10px 25px; margin-right: 10px; font-weight: bold; color: #666; text-decoration: none;">Batal</a>
                    <button type="submit" class="btn btn-warning" style="padding: 10px 30px; font-weight: bold; color: #000; border: none; border-radius: 4px;"><i class="fas fa-save me-2"></i>Update Laporan Kas</button>
                </div>

            </form>
        </div>
    </div>
</div> 

<script>
    // LOG LOG
    let rowIdx = {{ count($kas->pengeluaran) > 0 ? count($kas->pengeluaran) : 1 }};

    window.onload = function() {
        kalkulasi(); // Panggil kalkulasi saat load untuk menjumlahkan data lama
    };

    // === FITUR AJAX (Hanya jika Admin iseng ganti tanggal saat edit) ===
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
            <td class="text-center align-middle"><button type="button" class="btn btn-sm btn-outline-danger w-75" onclick="removeRow(this)">Hapus</button></td>
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
            document.getElementById('saldoBersih').style.color = '#dc3545';
        } else {
            document.getElementById('saldoBersih').style.color = '#856404';
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