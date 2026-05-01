@extends('layouts.app')

@section('content')
<div class="container mt-4 mb-5">
    
    {{-- Notifikasi Sukses --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card shadow border-0">
        <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
            <h4 class="fw-bold mb-0 text-primary">
                <i class="fas fa-history me-2"></i> Riwayat Laporan Kas
            </h4>
            <a href="{{ route('kas.create') }}" class="btn btn-primary shadow-sm">
                <i class="fas fa-plus me-1"></i> Buat Laporan Baru
            </a>
        </div>
        
        <div class="card-body p-4">
            <div class="table-responsive">
                <table class="table table-hover table-bordered align-middle text-center" id="kasTable">
                    <thead class="table-light">
                        <tr>
                            <th width="5%">No</th>
                            <th width="15%">Tanggal</th>
                            <th width="15%">Pemasukan</th>
                            <th width="15%">Pengeluaran</th>
                            <th width="15%">Saldo Bersih</th>
                            <th width="10%">Pembuat</th>
                            <th width="25%">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($laporanKas as $index => $kas)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>{{ \Carbon\Carbon::parse($kas->tanggal)->translatedFormat('d M Y') }}</td>
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
                                <div class="d-flex justify-content-center gap-1">
                                    {{-- Tombol Lihat Detail (Panggil Modal) --}}
                                    <button type="button" class="btn btn-sm btn-info text-white" data-bs-toggle="modal" data-bs-target="#modalDetail{{ $kas->id }}" title="Lihat Detail">
                                        <i class="fas fa-eye"></i> Detail
                                    </button>
                                    
                                    <a href="{{ route('kas.edit', $kas->id) }}" class="btn btn-sm btn-warning text-dark" title="Edit Data">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>

                                    {{-- Tombol Hapus (Form) --}}
                                    <form action="{{ route('kas.destroy', $kas->id) }}" method="POST" onsubmit="return confirm('Yakin ingin menghapus laporan kas ini? Data tidak bisa dikembalikan.');">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger" title="Hapus Data">
                                            <i class="fas fa-trash"></i> Hapus
                                        </button>
                                    </form>

                                    {{-- Tombol PDF --}}
                                    <a href="{{ route('kas.pdf.single', $kas->id) }}" class="btn btn-sm btn-secondary" target="_blank" title="Cetak PDF">
                                        <i class="fas fa-file-pdf"></i> PDF
                                    </a>
                                </div>
                            </td>
                        </tr>

                        <!-- ================= MODAL DETAIL ================= -->
                        <div class="modal fade" id="modalDetail{{ $kas->id }}" tabindex="-1" aria-labelledby="modalDetailLabel{{ $kas->id }}" aria-hidden="true">
                            <div class="modal-dialog modal-lg modal-dialog-centered">
                                <div class="modal-content">
                                    <div class="modal-header bg-light">
                                        <h5 class="modal-title fw-bold" id="modalDetailLabel{{ $kas->id }}">
                                            Detail Kas: {{ \Carbon\Carbon::parse($kas->tanggal)->translatedFormat('d F Y') }}
                                        </h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body text-start p-4">
                                        
                                        <div class="row mb-3 border-bottom pb-3">
                                            <div class="col-md-4">
                                                <small class="text-muted d-block">Pemasukan Sistem</small>
                                                <h5 class="text-success fw-bold">Rp {{ number_format($kas->pemasukan_sistem, 0, ',', '.') }}</h5>
                                            </div>
                                            <div class="col-md-4">
                                                <small class="text-muted d-block">Total Pengeluaran</small>
                                                <h5 class="text-danger fw-bold">Rp {{ number_format($kas->total_pengeluaran, 0, ',', '.') }}</h5>
                                            </div>
                                            <div class="col-md-4 border-start">
                                                <small class="text-muted d-block">Saldo Bersih</small>
                                                <h4 class="fw-bold {{ $kas->saldo_bersih < 0 ? 'text-danger' : 'text-success' }}">
                                                    Rp {{ number_format($kas->saldo_bersih, 0, ',', '.') }}
                                                </h4>
                                            </div>
                                        </div>

                                        <h6 class="fw-bold mb-2">Rincian Pengeluaran:</h6>
                                        <table class="table table-sm table-bordered">
                                            <thead class="table-light text-center">
                                                <tr>
                                                    <th width="5%">No</th>
                                                    <th>Keterangan</th>
                                                    <th width="30%">Nominal</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @forelse($kas->pengeluaran as $i => $item)
                                                <tr>
                                                    <td class="text-center">{{ $i + 1 }}</td>
                                                    <td>{{ $item->keterangan }}</td>
                                                    <td class="text-end">Rp {{ number_format($item->nominal, 0, ',', '.') }}</td>
                                                </tr>
                                                @empty
                                                <tr>
                                                    <td colspan="3" class="text-center text-muted">Tidak ada pengeluaran manual.</td>
                                                </tr>
                                                @endforelse
                                            </tbody>
                                        </table>

                                        <div class="row mt-4 text-center">
                                            <div class="col-6">
                                                <p class="mb-1 text-muted small">Pembuat</p>
                                                <p class="fw-bold mb-0">{{ $kas->nama_pembuat ?? '-' }}</p>
                                            </div>
                                            <div class="col-6">
                                                <p class="mb-1 text-muted small">Pimpinan</p>
                                                <p class="fw-bold mb-0">{{ $kas->nama_pimpinan ?? '-' }}</p>
                                            </div>
                                        </div>

                                    </div>
                                    <div class="modal-footer">
                                        <a href="{{ route('kas.pdf.single', $kas->id) }}" class="btn btn-danger" target="_blank">
                                            <i class="fas fa-print"></i> Cetak Bukti PDF
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
                                <i class="fas fa-folder-open fs-2 mb-3 d-block text-black-50"></i>
                                Belum ada riwayat laporan kas.
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