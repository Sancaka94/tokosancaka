@extends('layouts.admin')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="mb-0">Riwayat Nota</h3>
        <a href="{{ route('nota.create') }}" class="btn btn-primary">+ Buat Nota Baru</a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">Tanggal</th>
                            <th>No. Nota</th>
                            <th>Kepada</th>
                            <th>Total Item</th>
                            <th>Grand Total</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($notas as $nota)
                        <tr>
                            <td class="ps-3">{{ \Carbon\Carbon::parse($nota->tanggal)->format('d/m/Y') }}</td>
                            <td class="fw-bold">{{ $nota->no_nota }}</td>
                            <td>{{ $nota->kepada }}</td>
                            <td>{{ $nota->items->count() }} Barang</td>
                            <td class="fw-bold text-success">Rp {{ number_format($nota->total_harga, 0, ',', '.') }}</td>
                            <td class="text-center">
                                <form action="{{ route('nota.destroy', $nota->id) }}" method="POST" onsubmit="return confirm('Yakin ingin menghapus nota ini?');">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger">Hapus</button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center py-4">Belum ada data nota.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer bg-white">
            {{ $notas->links() }}
        </div>
    </div>
</div>
@endsection