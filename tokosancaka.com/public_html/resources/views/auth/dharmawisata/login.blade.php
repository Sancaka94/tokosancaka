@extends('layouts.app') @section('content')
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow border-0">
                <div class="card-header bg-dark text-white text-center py-3">
                    <h5 class="mb-0"><i class="fas fa-plane-departure me-2"></i> Darmawisata API Login</h5>
                </div>
                <div class="card-body p-4">

                    @if (session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    @if (session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {!! session('success') !!}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    <form action="{{ route('api.dharmawisata.login') }}" method="POST">
                        @csrf

                        <div class="mb-3">
                            <label for="token" class="form-label fw-bold">Token (Timestamp)</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-key"></i></span>
                                <input type="text" name="token" id="token"
                                       class="form-control @error('token') is-invalid @enderror"
                                       value="{{ old('token', now()->format('Y-m-d\TH:i:sP')) }}"
                                       placeholder="YYYY-MM-DDTHH:mm:ss+07:00">
                            </div>
                            <small class="text-muted">Format: ISO8601 atau timestamp saat ini.</small>
                            @error('token') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-3">
                            <label for="securityCode" class="form-label fw-bold">Security Code</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                <input type="text" name="securityCode" id="securityCode"
                                       class="form-control @error('securityCode') is-invalid @enderror"
                                       placeholder="Masukkan hash security code">
                            </div>
                            @error('securityCode') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-4">
                            <label for="language" class="form-label fw-bold">Language</label>
                            <select name="language" id="language" class="form-select">
                                <option value="ID" selected>Indonesia (ID)</option>
                                <option value="EN">English (EN)</option>
                            </select>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary shadow-sm">
                                <i class="fas fa-sign-in-alt me-1"></i> Login ke Darmawisata
                            </button>
                            <a href="{{ url()->previous() }}" class="btn btn-outline-secondary">Kembali</a>
                        </div>
                    </form>
                </div>
                <div class="card-footer bg-light text-center py-3">
                    <small class="text-muted">User ID akan otomatis diambil dari <strong>Database API Settings</strong></small>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
