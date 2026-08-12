@extends('layouts.app')

@section('content')
<!-- LOG LOG -->
<!-- Layer Watermark -->
<div class="watermark-overlay"></div>

<div class="container py-5 relative-content">
    
    <!-- Bagian Header (Logo & Informasi Proyek) -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mb-4 gap-3">
        <div class="d-flex align-items-center gap-3">
            <img src="https://tokosancaka.com/storage/uploads/logo.jpeg" alt="Sancaka Logo" class="rounded shadow-sm" style="width: 60px; height: 60px; object-fit: cover;">
            <div>
                <h4 class="mb-0 fw-bold text-dark">CV Sancaka Karya Hutama</h4>
                <p class="text-muted small mb-0">PENAWARAN HARGA PROYEK SANCAKA</p>
            </div>
        </div>
        <div>
            <!-- Tombol Print Browser -->
            <button onclick="window.print()" class="btn btn-dark btn-sm px-4 rounded-3 shadow-sm fw-bold">
                <i class="fas fa-print me-1"></i> Cetak / Simpan PDF
            </button>
        </div>
    </div>

    <!-- Card Info Proyek -->
    <div class="card border-0 shadow-sm rounded-3 mb-4">
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
    <div class="card border border-light-subtle shadow-sm rounded-3 overflow-hidden">
        <div class="table-responsive" style="max-height: 70vh;">
            <table class="table table-hover align-middle mb-0 text-nowrap">
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
                <tbody class="border-top-0">
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
                        <tr class="bg-light">
                            <td class="text-center fw-bold text-dark border-end align-top">{{ $roman }}</td>
                            <td colspan="5" class="fw-bold text-dark text-uppercase">
                                {{ $kategori ?: 'PEKERJAAN UMUM' }}
                            </td>
                        </tr>

                        <!-- Looping Item -->
                        @foreach ($kategoriItems as $index => $item)
                            <tr>
                                <td class="text-center text-muted border-end align-top">{{ $index + 1 }}</td>
                                <td class="text-wrap border-end text-dark align-top" style="min-width: 250px;">{{ $item->uraian_pekerjaan }}</td>
                                <td class="text-end text-dark border-end align-top">{{ rtrim(rtrim(number_format($item->volume, 2, ',', '.'), '0'), ',') }}</td>
                                <td class="text-center text-muted border-end align-top">{{ $item->satuan }}</td>
                                <td class="text-end text-dark border-end align-top">{{ number_format($item->harga_satuan, 0, ',', '.') }}</td>
                                <td class="text-end fw-medium text-dark align-top">{{ number_format($item->total, 0, ',', '.') }}</td>
                            </tr>
                        @endforeach

                        <!-- Baris Sub Total -->
                        <tr>
                            <td class="border-end"></td>
                            <td colspan="3" class="text-center fw-bold text-dark border-end">Sub Total {{ $roman }}</td>
                            <td class="text-end fw-bold text-dark bg-light border-end">{{ number_format($subTotal, 0, ',', '.') }}</td>
                            <td></td>
                        </tr>

                        @php $categoryIndex++; @endphp
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">
                                <i class="fas fa-folder-open fs-3 mb-3 text-light"></i><br>
                                Data RAB belum tersedia untuk proyek ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                
                <!-- Grand Total Sticky Bottom -->
                @if(count($items) > 0)
                <tfoot class="sticky-bottom bg-light border-top border-2 shadow-sm" style="z-index: 1;">
                    <tr>
                        <td class="border-end"></td>
                        <th colspan="3" class="text-center py-4 fw-bold text-dark border-end text-uppercase">TOTAL KESELURUHAN</th>
                        <th class="text-end py-4 fw-bold text-dark border-end fs-6 text-nowrap">Rp {{ number_format($grandTotal, 0, ',', '.') }}</th>
                        <th></th>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>

    <!-- Kotak Catatan (Jika Ada) -->
    @if($proyek->catatan)
    <div class="card border-0 bg-light shadow-sm rounded-3 mt-4">
        <div class="card-body p-4 border border-light-subtle rounded-3">
            <h6 class="fw-bold text-dark mb-2"><i class="fas fa-sticky-note me-2 text-muted"></i>Catatan Tambahan:</h6>
            <p class="text-muted small mb-0" style="white-space: pre-wrap;">{{ $proyek->catatan }}</p>
        </div>
    </div>
    @endif

    <!-- Footer Credit Sancaka -->
    <div class="text-center mt-5 mb-4 border-top pt-4">
        <p class="text-muted small mb-1">Diterbitkan oleh sistem <strong>tokosancaka.com</strong></p>
        <p class="text-muted" style="font-size: 0.75rem;">Jl. Dr. Wahidin No.18A RT.22 RW.05 Kel Ketanggi Kec.Ngawi Kab.Ngawi Jawa Timur 63211</p>
    </div>

</div>

<!-- CSS Khusus untuk Print dan Watermark -->
<style>
    /* Konfigurasi Watermark */
    .watermark-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        pointer-events: none; /* Agar tidak menghalangi klik/scroll pengguna */
        z-index: 9999; /* Pastikan selalu berada di paling atas */
        /* SVG Data URI: Teks "CV SANCAKA KARYA HUTAMA" miring -45 derajat, warna abu-abu opacity 12% */
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='200' height='200'%3E%3Ctext x='50%25' y='50%25' transform='rotate(-45 100 100)' fill='rgba(100,100,100,0.12)' font-size='11' font-family='Arial, sans-serif' font-weight='bold' text-anchor='middle' letter-spacing='1'%3ECV SANCAKA KARYA HUTAMA%3C/text%3E%3C/svg%3E");
        background-repeat: repeat;
    }

    /* Memastikan konten web berada di bawah watermark tapi tetap bisa diklik */
    .relative-content {
        position: relative;
        z-index: 1;
    }

    @media print {
        body { background-color: #fff !important; }
        .sticky-top, .sticky-bottom { position: static !important; }
        .card, .table-responsive { box-shadow: none !important; border: none !important; max-height: none !important; overflow: visible !important; }
        .btn, nav, footer, header { display: none !important; }
        
        /* Watermark tetap dicetak saat di-print via browser */
        .watermark-overlay {
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
    }
</style>
@endsection