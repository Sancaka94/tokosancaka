@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Halo, {{ $user->name }}</h2>
    <div class="card">
        <div class="card-header">Informasi Toko: {{ $tenant->name }}</div>
        <div class="card-body">
            <p>Subdomain: <strong>{{ $tenant->subdomain }}</strong></p>
            <p>Status: <span class="badge bg-success">{{ $tenant->status }}</span></p>
            <p>Sisa Masa Aktif: {{ $daysLeft }} Hari</p>
            
            <hr>
            <a href="https://{{ $tenant->subdomain }}.tokosancaka.com/dashboard" 
               class="btn btn-primary" target="_blank">
               Buka Dashboard Toko
            </a>
            <a href="{{ route('tenant.settings') }}" class="btn btn-secondary">
               Pengaturan Profil
            </a>
        </div>
    </div>
</div>
@endsection