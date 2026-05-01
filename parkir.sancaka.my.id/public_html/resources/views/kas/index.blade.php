@extends('layouts.app')

@section('content')
<div class="container mt-4 mb-5">
    
    {{-- Notifikasi Alert --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
            <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card shadow border-0">
        <div class="card-body p-5">
            
            <!-- HEADER PERUSAHAAN (Sama seperti halaman Input/Invoice) -->
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
                    <h2 class="text-uppercase fw-bold mt-2 mt-md-0 text-primary">Riwayat Kas</h2>
                    <p class="text-muted mb-0">Laporan Keuangan Harian / Periode</p>
                </div>
            </div>

            <!-- BAGIAN FILTER & TOMBOL TAMBAH -->
            <div class="row mb-4 align-items-end bg-light p-3 rounded border mx-0">
                <div class="col-md-8">
                    <form action="{{ route('kas.index') }}" method="GET">
                        <label class="fw-bold mb-2">Filter Rentang Waktu Laporan</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white">Dari</span>
                            <input type="date" name="start_date" class="form-control" value="{{ request('start_date') }}" required>
                            
                            <span class="input-group-text bg-white border-start-0 border-end-0">s/d</span>
                            
                            <input type="date" name="end_date" class="form-control" value="{{ request('end_date') }}" required>
                            
                            <button type="submit" class="btn btn-primary px-4">
                                <i class="fas fa-search me-1"></i> Filter
                            </button>
                            
                            @if(request('start_date'))
                                <a href="{{ route('kas.index') }}" class="btn btn-outline-danger">
                                    <i class="fas fa-sync-alt"></i> Reset
                                </a>
                            @endif
                        </div>
                    </form>
                </div>
                <div class="col-md-4 text-md-end mt-3 mt-md-0">
                    <a href="{{ route('kas.create') }}" class="btn btn-success fw-bold py-2 px-4 shadow-sm">
                        <i class="fas fa-plus-circle me-1"></i> Buat Laporan Baru
                    </a>
                </div>
            </div>

            <!-- TABEL DATA RIWAYAT -->
            <div class="table-responsive">
                <table class="table table-hover table-bordered align-middle text-center" id="kasTable">
                    <thead class="table-light">
                        <tr>
                            <th width="5%">No</th>
                            <th width="18%">Periode Laporan</th>
                            <th width="15%">Pemasukan (Sistem)</th>
                            <th width="15%">Total Pengeluaran</th>
                            <th width="15%">Saldo Bersih</th>
                            <th width="12%">Pembuat</th>
                            <th width="20%">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($laporanKas as $index => $kas)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>
                                @if($kas->tanggal_mulai == $kas->tanggal_akhir)
                                    <span class="fw-bold">{{ \Carbon\Carbon::parse($kas->tanggal_mulai)->translatedFormat('d M Y') }}</span>
                                @else
                                    <small class="d-block">{{ \Carbon\Carbon::parse($kas->tanggal_mulai)->format('d/m/Y') }}</small>
                                    <small class="d-block text-muted">s/d</small>
                                    <small class="d-block">{{ \Carbon\Carbon::parse($kas->tanggal_akhir)->format('d/m/Y') }}</small>
                                @endif
                            </td>
                            <td class="text-success fw-bold">Rp {{ number_format($kas->pemasukan_sistem, 0, ',', '.') }}</td>
                            <td class="text-danger fw-bold">Rp {{ number_format($kas->total_pengeluaran, 0, ',', '.') }}</td>
                            
                            <td>
                                @if($kas->saldo_bersih < 0)
                                    <span class="badge bg-danger fs-6 w-100 py-2">Rp {{ number_format($kas->saldo_bersih, 0, ',', '.') }}</span>
                                @else
                                    <span class="badge bg-success fs-6 w-100 py-2">Rp {{ number_format($kas->saldo_bersih, 0, ',', '.') }}</span>
                                @endif
                            </td>
                            
                            <td>{{ $kas->nama_pembuat ?? '-' }}</td>
                            <td>
                                <div class="d-flex justify-content-center gap-1 flex-wrap">
                                    {{-- Tombol Detail --}}
                                    <button type="button" class="btn btn-sm btn-info text-white" data-bs-toggle="modal" data-bs-target="#modalDetail{{ $kas->id }}" title="Lihat Detail">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    
                                    {{-- Tombol Edit --}}
                                    <a href="{{ route('kas.edit', $kas->id) }}" class="btn btn-sm btn-warning text-dark" title="Edit Data">
                                        <i class="fas fa-edit"></i>
                                    </a>

                                    {{-- Tombol PDF --}}
                                    <a href="{{ route('kas.pdf.single', $kas->id) }}" class="btn btn-sm btn-secondary" target="_blank" title="Cetak PDF">
                                        <i class="fas fa-file-pdf"></i>
                                    </a>

                                    {{-- Tombol Hapus --}}
                                    <form action="{{ route('kas.destroy', $kas->id) }}" method="POST" onsubmit="return confirm('Yakin ingin menghapus laporan kas ini?');" style="display: inline;">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger" title="Hapus Data">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>

                        <!-- ================= MODAL DETAIL ================= -->
                        <div class="modal fade" id="modalDetail{{ $kas->id }}" tabindex="-1" aria-labelledby="modalDetailLabel{{ $kas->id }}" aria-hidden="true">
                            <div class="modal-dialog modal-lg modal-dialog-centered">
                                <div class="modal-content border-0 shadow-lg">
                                    <div class="modal-header bg-primary text-white">
                                        <h5 class="modal-title fw-bold" id="modalDetailLabel{{ $kas->id }}">
                                            <i class="fas fa-file-invoice-dollar me-2"></i> Detail Laporan Kas
                                        </h5>
                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body text-start p-4">
                                        
                                        <div class="text-center mb-4">
                                            <p class="mb-0 text-muted">Periode Laporan</p>
                                            <h5 class="fw-bold">
                                                {{ \Carbon\Carbon::parse($kas->tanggal_mulai)->translatedFormat('d F Y') }} 
                                                @if($kas->tanggal_mulai != $kas->tanggal_akhir)
                                                    - {{ \Carbon\Carbon::parse($kas->tanggal_akhir)->translatedFormat('d F Y') }}
                                                @endif
                                            </h5>
                                        </div>

                                        <div class="row mb-4 border p-3 rounded bg-light text-center">
                                            <div class="col-md-4">
                                                <small class="text-muted d-block mb-1">Pemasukan Sistem</small>
                                                <h5 class="text-success fw-bold mb-0">Rp {{ number_format($kas->pemasukan_sistem, 0, ',', '.') }}</h5>
                                            </div>
                                            <div class="col-md-4 border-start border-end">
                                                <small class="text-muted d-block mb-1">Total Pengeluaran</small>
                                                <h5 class="text-danger fw-bold mb-0">Rp {{ number_format($kas->total_pengeluaran, 0, ',', '.') }}</h5>
                                            </div>
                                            <div class="col-md-4">
                                                <small class="text-muted d-block mb-1">Saldo Bersih</small>
                                                <h4 class="fw-bold mb-0 {{ $kas->saldo_bersih < 0 ? 'text-danger' : 'text-success' }}">
                                                    Rp {{ number_format($kas->saldo_bersih, 0, ',', '.') }}
                                                </h4>
                                            </div>
                                        </div>

                                        <h6 class="fw-bold border-bottom pb-2 mb-3">Rincian Pengeluaran Manual</h6>
                                        <table class="table table-sm table-bordered table-striped">
                                            <thead class="table-light text-center">
                                                <tr>
                                                    <th width="5%">No</th>
                                                    <th>Keterangan Pengeluaran</th>
                                                    <th width="35%">Nominal</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @forelse($kas->pengeluaran as $i => $item)
                                                <tr>
                                                    <td class="text-center">{{ $i + 1 }}</td>
                                                    <td>{{ $item->keterangan }}</td>
                                                    <td class="text-end fw-bold text-danger">Rp {{ number_format($item->nominal, 0, ',', '.') }}</td>
                                                </tr>
                                                @empty
                                                <tr>
                                                    <td colspan="3" class="text-center text-muted py-3"><i>Tidak ada pengeluaran manual pada periode ini.</i></td>
                                                </tr>
                                                @endforelse
                                            </tbody>
                                        </table>

                                        <div class="row mt-4 text-center">
                                            <div class="col-6">
                                                <p class="mb-1 text-muted small">Dibuat Oleh</p>
                                                <p class="fw-bold mb-0 text-uppercase">{{ $kas->nama_pembuat ?? '-' }}</p>
                                            </div>
                                            <div class="col-6">
                                                <p class="mb-1 text-muted small">Diketahui Oleh</p>
                                                <p class="fw-bold mb-0 text-uppercase">{{ $kas->nama_pimpinan ?? '-' }}</p>
                                            </div>
                                        </div>

                                    </div>
                                    <div class="modal-footer bg-light">
                                        <a href="{{ route('kas.pdf.single', $kas->id) }}" class="btn btn-danger shadow-sm" target="_blank">
                                            <i class="fas fa-print me-1"></i> Cetak PDF
                                        </a>
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- ================= END MODAL ================= -->

                        @empty
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="fas fa-folder-open fs-1 mb-3 d-block text-black-50"></i>
                                <h5 class="fw-bold text-dark">Data Tidak Ditemukan</h5>
                                <p>Belum ada riwayat laporan kas yang tersimpan atau sesuai dengan filter Anda.</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<style>
    #kasTable th { text-transform: uppercase; font-size: 0.85rem; letter-spacing: 0.5px; }
    #kasTable td { font-size: 0.95rem; }
    .table-responsive { overflow-x: auto; }
</style>
@endsection