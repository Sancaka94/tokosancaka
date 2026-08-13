@extends('layouts.app')

@section('content')
<!-- LOG LOG -->
<!-- Layer Watermark -->
<div class="watermark-overlay"></div>

<div class="container py-4 relative-content">

    <!-- FORM BUNGKUS KESELURUHAN -->
    <form action="{{ route('proyek.updateShare', $proyek->id) }}" method="POST">
        @csrf
        @method('PUT')

        <!-- WADAH UNTUK MENAMPUNG ID ITEM LAMA YANG DIHAPUS -->
        <div id="deletedItemsContainer"></div>

        <!-- Bagian Header (Logo & Informasi Proyek) -->
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mb-4 gap-3 header-section">
            <div class="d-flex align-items-center gap-3">
                <img src="https://tokosancaka.com/storage/uploads/logo.jpeg" alt="Sancaka Logo" class="rounded shadow-sm" style="width: 60px; height: 60px; object-fit: cover;">
                <div>
                    <h4 class="mb-0 fw-bold text-dark">CV Sancaka Karya Hutama</h4>
                    <p class="text-muted small mb-0">PENAWARAN HARGA PROYEK SANCAKA</p>
                </div>
            </div>
            <div class="d-flex gap-2">
                <!-- Tombol Print Browser -->
                <button type="button" onclick="siapkanPrint()" class="btn btn-dark btn-sm px-4 rounded-3 shadow-sm fw-bold hide-on-print">
                    <i class="fas fa-print me-1"></i> Cetak / Simpan PDF
                </button>
                <!-- Tombol Simpan -->
                <button type="submit" class="btn btn-success btn-sm px-4 rounded-3 shadow-sm fw-bold hide-on-print">
                    <i class="fas fa-save me-1"></i> Simpan Perubahan
                </button>
            </div>
        </div>

        <!-- Card Info Proyek -->
        <div class="card border-0 shadow-sm rounded-3 mb-4 card-info">
            <div class="card-body p-4 bg-light rounded-3 border">
                <h2 class="h5 fw-bold text-uppercase mb-3 text-dark">{{ $proyek->nama_proyek }}</h2>
                <div class="row text-secondary small">
                    <div class="col-md-6 mb-2 mb-md-0">
                        <i class="fas fa-map-marker-alt me-2 text-muted"></i> {{ $proyek->lokasi_proyek }}
                    </div>
                    <div class="col-md-6">
                        <i class="fas fa-phone me-2 text-muted"></i> {{ $proyek->nomor_hp }}
                    </div>
                </div>
            </div>
        </div>

        <!-- Card Tabel RAB -->
        <div class="card border border-light-subtle shadow-sm rounded-3 overflow-hidden card-table">
            <div class="table-responsive" style="max-height: 70vh;">
                <table class="table table-hover align-middle mb-0 text-nowrap" id="rabTable">
                    <thead class="table-light sticky-top shadow-sm" style="z-index: 2;">
                        <tr>
                            <th class="text-center py-3 border-end text-muted" style="width: 5%;">No.</th>
                            <th class="py-3 border-end text-muted" style="width: 40%;">URAIAN PEKERJAAN</th>
                            <th class="text-center py-3 border-end text-muted" style="width: 10%;">VOL</th>
                            <th class="text-center py-3 border-end text-muted" style="width: 10%;">SAT</th>
                            <th class="text-end py-3 border-end text-muted" style="width: 15%;">HARGA SATUAN</th>
                            <th class="text-end py-3 text-muted" style="width: 20%;">TOTAL</th>
                        </tr>
                    </thead>
                    <tbody class="border-top-0" id="tableBody">
                        @php
                            $grandTotal = 0;
                            $romanNumerals = ['I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'];
                            $categoryIndex = 0;
                            $groupedItems = collect($items)->groupBy('kategori');
                        @endphp

                        @forelse ($groupedItems as $kategori => $kategoriItems)
                            @php
                                $subTotal = $kategoriItems->sum('total');
                                $grandTotal += $subTotal;
                                $roman = $romanNumerals[$categoryIndex] ?? ($categoryIndex + 1);
                            @endphp

                            <!-- Baris Kategori -->
                            <tr class="bg-light category-row">
                                <td class="text-center fw-bold text-dark border-end align-top">{{ $roman }}</td>
                                <td colspan="5" class="fw-bold text-dark text-uppercase">
                                    <input type="text" name="kategori[{{ $categoryIndex }}]" value="{{ $kategori ?: 'PEKERJAAN UMUM' }}" class="form-control form-control-sm fw-bold">
                                </td>
                            </tr>

                            <!-- Looping Item Lama -->
                            @foreach ($kategoriItems as $index => $item)
                                <tr class="item-row" data-kategori="{{ $categoryIndex }}">
                                    <!-- PERUBAHAN: Menampilkan Nomor & Tombol Hapus berdampingan -->
                                    <td class="text-center text-muted border-end align-middle">
                                        <span>{{ $index + 1 }}</span>
                                        <button type="button" class="btn btn-outline-danger btn-sm p-1 ms-2 hide-on-print" onclick="hapusBarisLama(this, {{ $item->id }})" title="Hapus item ini">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                    <td class="border-end align-middle">
                                        <input type="hidden" name="items[{{ $item->id }}][id]" value="{{ $item->id }}">
                                        <input type="text" name="items[{{ $item->id }}][uraian_pekerjaan]" class="form-control form-control-sm" value="{{ $item->uraian_pekerjaan }}" required>
                                    </td>
                                    <td class="border-end align-middle">
                                        <input type="number" step="0.01" name="items[{{ $item->id }}][volume]" class="form-control form-control-sm text-end volume-input" value="{{ $item->volume }}" required oninput="hitungTotal()">
                                    </td>
                                    <td class="border-end align-middle">
                                        <input type="text" name="items[{{ $item->id }}][satuan]" class="form-control form-control-sm text-center" value="{{ $item->satuan }}" required>
                                    </td>
                                    <td class="border-end align-middle">
                                        <input type="number" name="items[{{ $item->id }}][harga_satuan]" class="form-control form-control-sm text-end harga-input" value="{{ $item->harga_satuan }}" required oninput="hitungTotal()">
                                    </td>
                                    <td class="text-end fw-medium text-dark align-middle item-total-text pe-3">
                                        {{ number_format($item->total, 0, ',', '.') }}
                                    </td>
                                </tr>
                            @endforeach

                            <!-- Baris Sub Total -->
                            <tr class="subtotal-row" data-kategori="{{ $categoryIndex }}">
                                <td class="border-end"></td>
                                <td colspan="3" class="text-center fw-bold text-dark border-end">Sub Total {{ $roman }}</td>
                                <td class="text-end fw-bold text-dark bg-light border-end subtotal-cell pe-3" data-kategori="{{ $categoryIndex }}">
                                    {{ number_format($subTotal, 0, ',', '.') }}
                                </td>
                                <td></td>
                            </tr>

                            @php $categoryIndex++; @endphp
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">Data RAB belum tersedia untuk proyek ini.</td>
                            </tr>
                        @endforelse
                    </tbody>

                    <!-- Tombol Tambah Item -->
                    <tfoot class="hide-on-print">
                        <tr>
                            <td colspan="6" class="text-center bg-white p-3 border-bottom">
                                <button type="button" class="btn btn-outline-primary btn-sm" onclick="tambahBaris()">
                                    <i class="fas fa-plus me-1"></i> Tambah Baris Pekerjaan Baru
                                </button>
                            </td>
                        </tr>
                    </tfoot>

                    <!-- Grand Total Sticky Bottom -->
                    @if(count($items) > 0)
                    <tfoot class="sticky-bottom bg-light border-top border-2 shadow-sm" style="z-index: 1;">
                        <tr>
                            <td class="border-end"></td>
                            <th colspan="3" class="text-center py-3 fw-bold text-dark border-end text-uppercase">TOTAL KESELURUHAN</th>
                            <th class="text-end py-3 fw-bold text-dark border-end fs-6 text-nowrap pe-3" id="grandTotalText">Rp {{ number_format($grandTotal, 0, ',', '.') }}</th>
                            <th></th>
                        </tr>
                    </tfoot>
                    @endif
                </table>
            </div>
        </div>

        <!-- Kotak Catatan -->
        <div class="card border-0 bg-light shadow-sm rounded-3 mt-4 card-note">
            <div class="card-body p-4 border border-light-subtle rounded-3">
                <h6 class="fw-bold text-dark mb-2"><i class="fas fa-sticky-note me-2 text-muted hide-on-print"></i>Catatan Tambahan:</h6>
                
                <!-- Versi Layar (Editor TinyMCE) -->
                <div class="tampilan-layar">
                    <textarea id="catatanTinyMCE" name="catatan">{!! $proyek->catatan !!}</textarea>
                </div>

                <!-- Versi Cetak (Hanya Muncul Saat di-Print) -->
                <div id="catatanPrintView" class="tampilan-print text-dark">
                    {!! $proyek->catatan !!}
                </div>
            </div>
        </div>

    </form> <!-- AKHIR FORM -->

    <!-- Footer Credit Sancaka -->
    <div class="text-center mt-4 mb-2 pt-3 footer-credit">
        <p class="text-muted small mb-1">Diterbitkan oleh sistem <strong>tokosancaka.com</strong></p>
        <p class="text-muted" style="font-size: 0.75rem;">Jl. Dr. Wahidin No.18A RT.22 RW.05 Kel Ketanggi Kec.Ngawi Kab.Ngawi Jawa Timur 63211</p>
    </div>

</div>

<!-- CSS Khusus untuk Print dan Watermark -->
<style>
    /* Konfigurasi Watermark */
    .watermark-overlay {
        position: fixed;
        top: 0; left: 0; width: 100%; height: 100%; pointer-events: none; z-index: 9999;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='200' height='200'%3E%3Ctext x='50%25' y='50%25' transform='rotate(-45 100 100)' fill='rgba(100,100,100,0.12)' font-size='11' font-family='Arial, sans-serif' font-weight='bold' text-anchor='middle' letter-spacing='1'%3ECV SANCAKA KARYA HUTAMA%3C/text%3E%3C/svg%3E");
        background-repeat: repeat;
    }
    .relative-content { position: relative; z-index: 1; }

    /* Sembunyikan versi cetak di layar normal */
    .tampilan-print { display: none; }

    /* =========================================================
       PENGATURAN KHUSUS CETAK (PRINT) - APLIKASI GAYA EXCEL 
       ========================================================= */
    @media print {
        @page {
            size: A4 portrait;
            margin: 1cm; /* Mempersempit margin kertas agar tabel bisa lebar */
        }

        body { 
            background-color: #fff !important; 
            font-size: 11pt !important; /* Huruf lebih kecil dan rapi */
            color: #000 !important;
        }

        /* Memaksa kontainer menempati 100% layar (Justify Rata) */
        .container, .container-fluid {
            min-width: 100% !important;
            width: 100% !important;
            max-width: 100% !important;
            padding: 0 !important;
            margin: 0 !important;
        }

        /* Membersihkan padding/margin dari Card Bootstrap */
        .card { border: none !important; box-shadow: none !important; margin-bottom: 15px !important; }
        .card-body { padding: 0 !important; border: none !important; background-color: transparent !important; }
        
        /* Merapikan tabel menjadi gaya Excel (Garis tegas hitam) */
        .table-responsive { overflow: visible !important; max-height: none !important; }
        table { width: 100% !important; border-collapse: collapse !important; }
        table th, table td { 
            border: 1px solid #000 !important; 
            padding: 4px 6px !important; /* Jarak sel dipadatkan */
            font-size: 10pt !important; 
            vertical-align: middle !important;
        }
        .table-light, .bg-light { 
            background-color: #f0f0f0 !important; 
            -webkit-print-color-adjust: exact; 
            print-color-adjust: exact; 
        }

        /* Menyamarkan input field menjadi teks biasa */
        input.form-control { 
            border: none !important; 
            background: transparent !important; 
            padding: 0 !important; 
            margin: 0 !important; 
            box-shadow: none !important; 
            font-size: 10pt !important;
            color: #000 !important;
            width: 100% !important;
        }

        /* Sembunyikan elemen yang tidak perlu dicetak */
        .hide-on-print, nav, footer, header { display: none !important; }

        /* Pertahankan watermark */
        .watermark-overlay { -webkit-print-color-adjust: exact; print-color-adjust: exact; }

        /* Sembunyikan editor TinyMCE, tampilkan hasil Render HTML-nya */
        .tampilan-layar { display: none !important; }
        .tampilan-print { display: block !important; margin-top: 5px; font-size: 11pt; }
        
        /* Hilangkan garis border atas pada footer credit */
        .footer-credit { border-top: none !important; margin-top: 30px !important; }
    }
</style>

<!-- Script Tambahan: TinyMCE, Kalkulasi, & Print -->
<script src="https://cdn.tiny.cloud/1/hsfvd81ihieoadc6tlyol8xucnq3i1n2vzuzfr1948kqqcx5/tinymce/8/tinymce.min.js" referrerpolicy="origin" crossorigin="anonymous"></script>

<script>
    // 1. Inisialisasi TinyMCE
    tinymce.init({
        selector: '#catatanTinyMCE',
        menubar: false,
        height: 250,
        plugins: 'lists link image table code',
        toolbar: 'undo redo | formatselect | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | code'
    });

    // 2. Fungsi untuk sinkronisasi teks TinyMCE ke tampilan cetak sebelum nge-print
    function siapkanPrint() {
        let kontenTerbaru = tinymce.get('catatanTinyMCE').getContent();
        document.getElementById('catatanPrintView').innerHTML = kontenTerbaru;
        window.print();
    }

    // 3. Fungsi format rupiah
    function formatRupiah(angka) {
        return new Intl.NumberFormat('id-ID').format(Math.round(angka));
    }

    // 4. Fungsi Kalkulasi Otomatis (Baris, Subtotal, Grand Total)
    function hitungTotal() {
        let grandTotal = 0;
        let subTotals = {};

        // Inisialisasi semua subtotal kategori menjadi 0
        document.querySelectorAll('.subtotal-cell').forEach(cell => {
            let katIndex = cell.getAttribute('data-kategori');
            subTotals[katIndex] = 0;
        });

        // Loop setiap baris pekerjaan untuk menghitung ulang
        document.querySelectorAll('.item-row').forEach(row => {
            let volInput = row.querySelector('.volume-input');
            let hargaInput = row.querySelector('.harga-input');
            let totalCell = row.querySelector('.item-total-text');
            let katIndex = row.getAttribute('data-kategori');

            if (volInput && hargaInput) {
                let volume = parseFloat(volInput.value) || 0;
                let harga = parseFloat(hargaInput.value) || 0;
                let total = volume * harga;

                totalCell.innerText = formatRupiah(total);

                if (subTotals[katIndex] !== undefined) {
                    subTotals[katIndex] += total;
                }
                grandTotal += total;
            }
        });

        // Update text Subtotal per kategori
        for (let katIndex in subTotals) {
            let subCell = document.querySelector(`.subtotal-cell[data-kategori="${katIndex}"]`);
            if (subCell) {
                subCell.innerText = formatRupiah(subTotals[katIndex]);
            }
        }

        // Update text Grand Total
        let grandTotalCell = document.getElementById('grandTotalText');
        if (grandTotalCell) {
            grandTotalCell.innerText = 'Rp ' + formatRupiah(grandTotal);
        }
    }

    // 5. Fungsi Tambah Baris Baru Secara Dinamis
    let newRowIndex = 0;
    function tambahBaris() {
        const tbody = document.getElementById('tableBody');
        const newRow = document.createElement('tr');
        
        let lastKategoriIndex = document.querySelectorAll('.subtotal-cell').length - 1;
        if(lastKategoriIndex < 0) lastKategoriIndex = 0;
        
        newRow.classList.add('item-row');
        newRow.setAttribute('data-kategori', lastKategoriIndex);

        // PERUBAHAN: Menambahkan bintang (*) sebagai pengganti nomor baris baru, serta ikon hapus
        newRow.innerHTML = `
            <td class="text-center border-end align-middle">
                <span>*</span>
                <button type="button" class="btn btn-outline-danger btn-sm p-1 ms-2 hide-on-print" onclick="hapusBarisBaru(this)" title="Hapus baris ini">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
            <td class="border-end align-middle">
                <input type="text" name="new_items[${newRowIndex}][uraian_pekerjaan]" class="form-control form-control-sm" placeholder="Uraian Pekerjaan Baru" required>
            </td>
            <td class="border-end align-middle">
                <input type="number" step="0.01" name="new_items[${newRowIndex}][volume]" class="form-control form-control-sm text-end volume-input" value="1" required oninput="hitungTotal()">
            </td>
            <td class="border-end align-middle">
                <input type="text" name="new_items[${newRowIndex}][satuan]" class="form-control form-control-sm text-center" placeholder="ls/m2" required>
            </td>
            <td class="border-end align-middle">
                <input type="number" name="new_items[${newRowIndex}][harga_satuan]" class="form-control form-control-sm text-end harga-input" value="0" required oninput="hitungTotal()">
            </td>
            <td class="text-end fw-medium text-dark align-middle item-total-text pe-3">0</td>
        `;
        
        let lastSubtotalRow = document.querySelector(`.subtotal-row[data-kategori="${lastKategoriIndex}"]`);
        if (lastSubtotalRow) {
            lastSubtotalRow.parentNode.insertBefore(newRow, lastSubtotalRow);
        } else {
            tbody.appendChild(newRow);
        }

        newRowIndex++;
        hitungTotal();
    }

    // 6. Fungsi Hapus Baris Baru
    function hapusBarisBaru(button) {
        button.closest('tr').remove();
        hitungTotal();
    }

    // 7. Fungsi Hapus Baris Lama
    function hapusBarisLama(button, itemId) {
        const container = document.getElementById('deletedItemsContainer');
        container.innerHTML += `<input type="hidden" name="deleted_items[]" value="${itemId}">`;
        
        button.closest('tr').remove();
        hitungTotal();
    }
</script>
@endsection