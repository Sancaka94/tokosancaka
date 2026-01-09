@extends('layouts.admin')

@section('title', 'Edit Data SPX Scan')
@section('page-title', 'Edit Data SPX Scan')

@section('content')
<div class="card">
    <div class="card-header">
        <h5 class="card-title">Edit Resi: {{ $spxScan->resi }}</h5>
    </div>
    <div class="card-body">
        <form action="{{ route('admin.spx-scans-scans.update', $spxScan->id) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="mb-3">
                <label for="resi" class="form-label">Nomor Resi</label>
                <input type="text" class="form-control @error('resi') is-invalid @enderror" id="resi" name="resi" value="{{ old('resi', $spxScan->resi) }}" required>
                @error('resi')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="mb-3">
                <label for="status" class="form-label">Status</label>
                <input type="text" class="form-control @error('status') is-invalid @enderror" id="status" name="status" value="{{ old('status', $spxScan->status) }}" required>
                 @error('status')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <a href="{{ route('admin.spx-scans-scans.index') }}" class="btn btn-secondary">Batal</a>
            <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
        </form>
    </div>
</div>
@endsection
