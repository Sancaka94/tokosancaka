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
            <button type="button" class="btn-close" data-bs-dismiss="alert" data-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert" style="border-left: 5px solid #dc3545; font-weight: bold;">
            <i class="fas fa-exclamation-triangle me-2 fs-5"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" data-dismiss="alert" aria-label="Close"></button>
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
            <div class="table-responsive shadow-sm border rounded-3 mb-4">
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
                                    {{-- Tombol Detail (Dipaksa pakai JS murni) --}}
                                    <button type="button" class="btn btn-light text-primary border shadow-sm" onclick="bukaModal({{ $kas->id }})" title="Lihat Detail">
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
            
           <!-- ================= MODAL DETAIL MANUAL JS ================= -->
            @foreach($laporanKas as $kas)
            <div id="modalDetailManual{{ $kas->id }}" style="display: none; position: fixed; z-index: 9999; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.6);">
                <div style="background-color: #fff; margin: 5% auto; border-radius: 12px; width: 80%; max-width: 800px; box-shadow: 0 5px 15px rgba(0,0,0,0.3); overflow: hidden; position: relative;">
                    
                    <!-- Header Modal -->
                    <div style="background-color: #0d6efd; color: white; padding: 15px 20px; display: flex; justify-content: space-between; align-items: center;">
                        <h5 style="margin: 0; font-weight: bold;"><i class="fas fa-file-invoice-dollar me-2"></i> Detail Laporan Kas</h5>
                        <button onclick="tutupModal({{ $kas->id }})" style="background: none; border: none; color: white; font-size: 24px; cursor: pointer;">&times;</button>
                    </div>
                    
                    <!-- Body Modal -->
                    <div style="padding: 30px;">
                        <!-- Header Tanggal -->
                        <div style="text-align: center; margin-bottom: 25px; border-bottom: 1px solid #eee; padding-bottom: 15px;">
                            <p style="margin: 0 0 5px 0; color: #6c757d; text-transform: uppercase; font-weight: 600; font-size: 14px;">Periode Laporan</p>
                            <h4 style="margin: 0; font-weight: bold; color: #212529;">
                                {{ \Carbon\Carbon::parse($kas->tanggal_mulai)->translatedFormat('d F Y') }} 
                                @if($kas->tanggal_mulai != $kas->tanggal_akhir)
                                    <span style="color: #adb5bd;"> - </span> {{ \Carbon\Carbon::parse($kas->tanggal_akhir)->translatedFormat('d F Y') }}
                                @endif
                            </h4>
                        </div>

                        <!-- Ringkasan Keuangan (Flexbox) -->
                        <div style="display: flex; flex-wrap: wrap; background-color: #f8f9fa; border: 1px solid #dee2e6; border-radius: 8px; margin-bottom: 30px; overflow: hidden;">
                            <div style="flex: 1; min-width: 200px; text-align: center; padding: 20px; border-right: 1px solid #dee2e6;">
                                <small style="display: block; margin-bottom: 8px; color: #6c757d; font-weight: bold; font-size: 12px; text-transform: uppercase;">Pemasukan Sistem</small>
                                <h4 style="margin: 0; font-weight: bold; color: #198754;">Rp {{ number_format($kas->pemasukan_sistem, 0, ',', '.') }}</h4>
                            </div>
                            <div style="flex: 1; min-width: 200px; text-align: center; padding: 20px; border-right: 1px solid #dee2e6;">
                                <small style="display: block; margin-bottom: 8px; color: #6c757d; font-weight: bold; font-size: 12px; text-transform: uppercase;">Total Pengeluaran</small>
                                <h4 style="margin: 0; font-weight: bold; color: #dc3545;">Rp {{ number_format($kas->total_pengeluaran, 0, ',', '.') }}</h4>
                            </div>
                            <div style="flex: 1; min-width: 200px; text-align: center; padding: 20px; background-color: {{ $kas->saldo_bersih < 0 ? '#fff5f5' : '#f0fdf4' }};">
                                <small style="display: block; margin-bottom: 8px; color: #6c757d; font-weight: bold; font-size: 12px; text-transform: uppercase;">Saldo Bersih</small>
                                <h3 style="margin: 0; font-weight: 900; color: {{ $kas->saldo_bersih < 0 ? '#dc3545' : '#198754' }};">
                                    Rp {{ number_format($kas->saldo_bersih, 0, ',', '.') }}
                                </h3>
                            </div>
                        </div>

                        <!-- Rincian Tabel -->
                        <h6 style="font-weight: bold; margin-bottom: 15px; color: #212529;"><i class="fas fa-list" style="color: #0d6efd; margin-right: 8px;"></i>Rincian Pengeluaran Manual</h6>
                        <div style="border: 1px solid #dee2e6; border-radius: 6px; overflow: hidden;">
                            <table style="width: 100%; border-collapse: collapse; text-align: left;">
                                <thead style="background-color: #f8f9fa;">
                                    <tr>
                                        <th style="padding: 10px; border-bottom: 1px solid #dee2e6; text-align: center; width: 10%; color: #6c757d;">No</th>
                                        <th style="padding: 10px; border-bottom: 1px solid #dee2e6; color: #6c757d;">Keterangan Pengeluaran</th>
                                        <th style="padding: 10px; border-bottom: 1px solid #dee2e6; text-align: right; width: 35%; color: #6c757d;">Nominal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($kas->pengeluaran as $i => $item)
                                    <tr>
                                        <td style="padding: 10px; border-bottom: 1px solid #eee; text-align: center; font-weight: bold; color: #6c757d;">{{ $i + 1 }}</td>
                                        <td style="padding: 10px; border-bottom: 1px solid #eee;">{{ $item->keterangan }}</td>
                                        <td style="padding: 10px; border-bottom: 1px solid #eee; text-align: right; font-weight: bold; color: #dc3545;">Rp {{ number_format($item->nominal, 0, ',', '.') }}</td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="3" style="padding: 20px; text-align: center; color: #6c757d; font-style: italic;">Tidak ada pengeluaran manual.</td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            @endforeach

            <!-- Script Pemicu Modal -->
            <script>
                function bukaModal(id) {
                    document.getElementById('modalDetailManual' + id).style.display = 'block';
                    document.body.style.overflow = 'hidden'; // Biar background gak bisa di-scroll
                }

                function tutupModal(id) {
                    document.getElementById('modalDetailManual' + id).style.display = 'none';
                    document.body.style.overflow = 'auto'; // Kembalikan scroll
                }

                // Tutup modal kalau klik area gelap di luarnya
                window.onclick = function(event) {
                    if (event.target.id.startsWith('modalDetailManual')) {
                        event.target.style.display = "none";
                        document.body.style.overflow = 'auto';
                    }
                }
            </script>
            <!-- ================= END MODAL MANUAL JS ================= -->

        </div>
    </div>
</div>
@endsection