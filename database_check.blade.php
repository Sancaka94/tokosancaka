@extends('layouts.admin')

@section('title', 'Cek Database')

@section('content')
<div class="container mt-4">
    <h3>Cek Status Database</h3>
    <div class="card">
        <div class="card-body">
            <p><strong>Nama Database:</strong> {{ $status['database'] }}</p>
            <p><strong>Status Koneksi:</strong>
                @if(str_contains($status['connection'], 'ERROR'))
                    <span class="text-danger">{{ $status['connection'] }}</span>
                @else
                    <span class="text-success">Terkoneksi</span>
                @endif
            </p>

            @if(!empty($status['note']))
                <div class="alert alert-warning">{{ $status['note'] }}</div>
            @endif

            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Nama Tabel</th>
                        <th>Status</th>
                        <th>Jumlah Record</th>
                        <th>Error</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($status['tables'] as $table)
                        <tr>
                            <td>{{ $table['name'] }}</td>
                            <td>
                                @if($table['status'] === 'OK')
                                    <span class="badge bg-success">OK</span>
                                @else
                                    <span class="badge bg-danger">ERROR</span>
                                @endif
                            </td>
                            <td>{{ $table['rows'] }}</td>
                            <td>{{ $table['error'] ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted">Tidak ada tabel ditemukan.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
