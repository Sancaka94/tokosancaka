@extends('layouts.app')

@section('content')

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<!-- LOG LOG -->
<style>
    .table-custom-header th {
        background-color: #f1f3f5;
        color: #495057;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.85rem;
        letter-spacing: 0.5px;
        padding: 15px 10px;
    }
    .table-custom-body td {
        font-size: 0.95rem;
        vertical-align: middle;
        padding: 12px 10px;
    }
    .action-buttons {
        display: flex;
        justify-content: center;
        gap: 6px;
        flex-wrap: wrap;
    }
    .action-buttons .btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 32px;
        height: 32px;
        padding: 0;
    }
</style>

<div class="container-fluid mt-4 mb-5">
    
    {{-- Notifikasi Alert --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert" style="border-left: 5px solid #198754; font-weight: bold;">
            <i class="fas fa-check-circle me-2 fs-5"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert" style="border-left: 5px solid #dc3545; font-weight: bold;">
            <i class="fas fa-exclamation-triangle me-2 fs-5"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

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
                    <h2 style="font-weight: 900; color: #0d6efd; margin: 0 0 10px 0; text-transform: uppercase; font-size: 1.8rem;">Riwayat Kas</h2>
                    <span style="background: #e7f1ff; color: #0d6efd; border: 1px solid #0d6efd; padding: 6px 15px; border-radius: 20px; font-weight: bold; font-size: 13px;">Keuangan Harian / Periode</span>
                </div>
            </div>

            <!-- BAGIAN FILTER & TOMBOL TAMBAH (FLEXBOX) -->
            <div style="display: flex; flex-wrap: wrap; justify-content: space-between; align-items: flex-end; gap: 20px; background-color: #f8f9fa; padding: 20px; border-radius: 8px; border: 1px solid #dee2e6; margin-bottom: 30px;">
                
                <!-- Kiri: Form Filter -->
                <div style="flex: 1; min-width: 400px;">
                    <form action="{{ route('kas.index') }}" method="GET" style="margin: 0;">
                        <label style="font-weight: bold; margin-bottom: 8px; display: block; color: #333;"><i class="fas fa-filter me-2 text-primary"></i>Filter Rentang Waktu Laporan</label>
                        <div style="display: flex; align-items: stretch; border: 1px solid #ced4da; border-radius: 4px; overflow: hidden; background: #fff;">
                            <span style="background: #e9ecef; padding: 8px 15px; border-right: 1px solid #ced4da; font-weight: bold; display: flex; align-items: center;">Dari</span>
                            <input type="date" name="start_date" class="form-control shadow-none" value="{{ request('start_date') }}" style="border: none; padding: 8px 12px; flex: 1; outline: none; border-radius: 0;" required>
                            
                            <span style="background: #e9ecef; padding: 8px 15px; border-left: 1px solid #ced4da; border-right: 1px solid #ced4da; font-weight: bold; display: flex; align-items: center;">s/d</span>
                            <input type="date" name="end_date" class="form-control shadow-none" value="{{ request('end_date') }}" style="border: none; padding: 8px 12px; flex: 1; outline: none; border-radius: 0;" required>
                            
                            <button type="submit" style="background-color: #0d6efd; color: white; border: none; padding: 0 20px; font-weight: bold; cursor: pointer; transition: 0.2s;">
                                <i class="fas fa-search me-1"></i> Filter
                            </button>
                            
                            @if(request('start_date'))
                                <a href="{{ route('kas.index') }}" style="background-color: #dc3545; color: white; border-left: 1px solid white; padding: 8px 20px; text-decoration: none; display: flex; align-items: center; font-weight: bold;">
                                    <i class="fas fa-sync-alt me-1"></i> Reset
                                </a>
                            @endif
                        </div>
                    </form>
                </div>

                <!-- Kanan: Tombol Tambah -->
                <div style="text-align: right;">
                    <a href="{{ route('kas.create') }}" class="btn btn-success shadow-sm" style="font-weight: bold; padding: 10px 20px; border-radius: 6px;">
                        <i class="fas fa-plus-circle me-2"></i> Buat Laporan Baru
                    </a>
                </div>
            </div>

            <!-- TABEL DATA RIWAYAT -->
            <div class="table-responsive shadow-sm border rounded-3">
                <table class="table table-hover table-bordered text-center mb-0" id="kasTable">
                    <thead class="table-custom-header">
                        <tr>
                            <th width="5%">No</th>
                            <th width="18%">Periode Laporan</th>
                            <th width="15%">Pemasukan (Sistem)</th>
                            <th width="15%">Total Pengeluaran</th>
                            <th width="15%">Saldo Bersih</th>
                            <th width="15%">Pembuat</th>
                            <th width="17%">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="table-custom-body">
                        @forelse($laporanKas as $index => $kas)
                        <tr>
                            <td class="fw-bold text-secondary">{{ $index + 1 }}</td>
                            <td>
                                @if($kas->tanggal_mulai == $kas->tanggal_akhir)
                                    <span class="fw-bold text-dark">{{ \Carbon\Carbon::parse($kas->tanggal_mulai)->translatedFormat('d M Y') }}</span>
                                @else
                                    <span class="d-block text-dark fw-bold" style="font-size: 13px;">{{ \Carbon\Carbon::parse($kas->tanggal_mulai)->format('d/m/Y') }}</span>
                                    <span class="d-block text-muted" style="font-size: 11px;">s/d</span>
                                    <span class="d-block text-dark fw-bold" style="font-size: 13px;">{{ \Carbon\Carbon::parse($kas->tanggal_akhir)->format('d/m/Y') }}</span>
                                @endif
                            </td>
                            <td class="text-success fw-bold">Rp {{ number_format($kas->pemasukan_sistem, 0, ',', '.') }}</td>
                            <td class="text-danger fw-bold">Rp {{ number_format($kas->total_pengeluaran, 0, ',', '.') }}</td>
                            
                            <td>
                                @if($kas->saldo_bersih < 0)
                                    <span style="background-color: #ffe1e1; color: #dc3545; padding: 6px 12px; border-radius: 4px; font-weight: bold; border: 1px solid #dc3545; display: inline-block; width: 100%;">
                                        Rp {{ number_format($kas->saldo_bersih, 0, ',', '.') }}
                                    </span>
                                @else
                                    <span style="background-color: #d1e7dd; color: #0f5132; padding: 6px 12px; border-radius: 4px; font-weight: bold; border: 1px solid #0f5132; display: inline-block; width: 100%;">
                                        Rp {{ number_format($kas->saldo_bersih, 0, ',', '.') }}
                                    </span>
                                @endif
                            </td>
                            
                            <td class="text-uppercase fw-bold text-secondary" style="font-size: 13px;">{{ $kas->nama_pembuat ?? '-' }}</td>
                            
                            <td>
                                <div class="action-buttons">
                                    {{-- Tombol Detail --}}
                                    <button type="button" class="btn btn-info text-white shadow-sm" data-bs-toggle="modal" data-bs-target="#modalDetail{{ $kas->id }}" title="Lihat Detail">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    
                                    {{-- Tombol Edit --}}
                                    <a href="{{ route('kas.edit', $kas->id) }}" class="btn btn-warning text-dark shadow-sm" title="Edit Data">
                                        <i class="fas fa-edit"></i>
                                    </a>

                                    {{-- Tombol PDF --}}
                                    <a href="{{ route('kas.pdf.single', $kas->id) }}" class="btn btn-secondary shadow-sm" target="_blank" title="Cetak PDF">
                                        <i class="fas fa-file-pdf"></i>
                                    </a>

                                    {{-- Tombol Hapus --}}
                                    <form action="{{ route('kas.destroy', $kas->id) }}" method="POST" onsubmit="return confirm('Yakin ingin menghapus laporan kas ini?');" style="display: inline;">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-danger shadow-sm" title="Hapus Data">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>

                        <!-- ================= MODAL DETAIL (FLEXBOX INSIDE) ================= -->
                        <div class="modal fade" id="modalDetail{{ $kas->id }}" tabindex="-1" aria-labelledby="modalDetailLabel{{ $kas->id }}" aria-hidden="true">
                            <div class="modal-dialog modal-lg modal-dialog-centered">
                                <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                                    <div class="modal-header bg-primary text-white border-0" style="padding: 20px;">
                                        <h5 class="modal-title fw-bold m-0" id="modalDetailLabel{{ $kas->id }}">
                                            <i class="fas fa-file-invoice-dollar me-2"></i> Detail Laporan Kas
                                        </h5>
                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body text-start p-4 p-md-5">
                                        
                                        <!-- Header Tanggal -->
                                        <div class="text-center mb-4 pb-3 border-bottom">
                                            <p class="mb-1 text-muted text-uppercase" style="font-weight: 600; font-size: 14px; letter-spacing: 1px;">Periode Laporan</p>
                                            <h4 class="fw-bold text-dark m-0">
                                                {{ \Carbon\Carbon::parse($kas->tanggal_mulai)->translatedFormat('d F Y') }} 
                                                @if($kas->tanggal_mulai != $kas->tanggal_akhir)
                                                    <span style="color: #adb5bd;"> - </span> {{ \Carbon\Carbon::parse($kas->tanggal_akhir)->translatedFormat('d F Y') }}
                                                @endif
                                            </h4>
                                        </div>

                                        <!-- Ringkasan Keuangan (Flexbox) -->
                                        <div style="display: flex; flex-wrap: wrap; background-color: #f8f9fa; border: 1px solid #dee2e6; border-radius: 8px; margin-bottom: 30px; overflow: hidden;">
                                            <!-- Box 1 -->
                                            <div style="flex: 1; min-width: 200px; text-align: center; padding: 20px; border-right: 1px solid #dee2e6;">
                                                <small class="text-muted d-block mb-2 fw-bold text-uppercase" style="font-size: 12px;">Pemasukan Sistem</small>
                                                <h4 class="text-success fw-bold mb-0">Rp {{ number_format($kas->pemasukan_sistem, 0, ',', '.') }}</h4>
                                            </div>
                                            <!-- Box 2 -->
                                            <div style="flex: 1; min-width: 200px; text-align: center; padding: 20px; border-right: 1px solid #dee2e6;">
                                                <small class="text-muted d-block mb-2 fw-bold text-uppercase" style="font-size: 12px;">Total Pengeluaran</small>
                                                <h4 class="text-danger fw-bold mb-0">Rp {{ number_format($kas->total_pengeluaran, 0, ',', '.') }}</h4>
                                            </div>
                                            <!-- Box 3 -->
                                            <div style="flex: 1; min-width: 200px; text-align: center; padding: 20px; background-color: {{ $kas->saldo_bersih < 0 ? '#fff5f5' : '#f0fdf4' }};">
                                                <small class="text-muted d-block mb-2 fw-bold text-uppercase" style="font-size: 12px;">Saldo Bersih</small>
                                                <h3 class="fw-black mb-0 {{ $kas->saldo_bersih < 0 ? 'text-danger' : 'text-success' }}">
                                                    Rp {{ number_format($kas->saldo_bersih, 0, ',', '.') }}
                                                </h3>
                                            </div>
                                        </div>

                                        <!-- Rincian Tabel -->
                                        <h6 class="fw-bold mb-3 text-dark"><i class="fas fa-list text-primary me-2"></i>Rincian Pengeluaran Manual</h6>
                                        <div class="border rounded overflow-hidden mb-4">
                                            <table class="table table-sm table-striped table-hover mb-0">
                                                <thead class="bg-light text-center" style="font-size: 14px;">
                                                    <tr>
                                                        <th width="10%" class="py-2 text-muted">No</th>
                                                        <th class="py-2 text-muted">Keterangan Pengeluaran</th>
                                                        <th width="35%" class="py-2 text-muted">Nominal</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @forelse($kas->pengeluaran as $i => $item)
                                                    <tr>
                                                        <td class="text-center align-middle fw-bold text-secondary">{{ $i + 1 }}</td>
                                                        <td class="align-middle px-3">{{ $item->keterangan }}</td>
                                                        <td class="text-end align-middle fw-bold text-danger px-3">Rp {{ number_format($item->nominal, 0, ',', '.') }}</td>
                                                    </tr>
                                                    @empty
                                                    <tr>
                                                        <td colspan="3" class="text-center text-muted py-4">
                                                            <i class="fas fa-ban fs-4 d-block mb-2 text-black-50"></i>
                                                            <i>Tidak ada pengeluaran manual pada periode ini.</i>
                                                        </td>
                                                    </tr>
                                                    @endforelse
                                                </tbody>
                                            </table>
                                        </div>

                                        <!-- Signatures in Modal (Flexbox) -->
                                        <div style="display: flex; justify-content: space-around; text-align: center; margin-top: 40px; padding-top: 20px; border-top: 1px dashed #dee2e6;">
                                            <div>
                                                <p class="mb-1 text-muted" style="font-size: 14px;">Dibuat Oleh,</p>
                                                <p class="fw-bold mt-5 mb-0 text-uppercase text-dark border-bottom border-dark pb-1 px-3">{{ $kas->nama_pembuat ?? '-' }}</p>
                                            </div>
                                            <div>
                                                <p class="mb-1 text-muted" style="font-size: 14px;">Diketahui Oleh,</p>
                                                <p class="fw-bold mt-5 mb-0 text-uppercase text-dark border-bottom border-dark pb-1 px-3">{{ $kas->nama_pimpinan ?? '-' }}</p>
                                            </div>
                                        </div>

                                    </div>
                                    <div class="modal-footer bg-light" style="padding: 15px 25px;">
                                        <button type="button" class="btn btn-outline-secondary fw-bold px-4" data-bs-dismiss="modal">Tutup</button>
                                        <a href="{{ route('kas.pdf.single', $kas->id) }}" class="btn btn-danger shadow-sm fw-bold px-4" target="_blank">
                                            <i class="fas fa-print me-1"></i> Cetak PDF
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- ================= END MODAL ================= -->

                        @empty
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted bg-light">
                                <i class="fas fa-folder-open fs-1 mb-3 d-block text-black-50" style="opacity: 0.5;"></i>
                                <h5 class="fw-bold text-dark">Data Tidak Ditemukan</h5>
                                <p class="mb-0">Belum ada riwayat laporan kas yang tersimpan atau sesuai dengan filter Anda.</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection