@extends('layouts.app')

@section('title','Audit Kode Laravel Lengkap')

@section('content')
<div class="container py-5">
    <h1>Audit Kode Laravel Lengkap</h1>
    <hr>

    <h3>1. Orphan Blade Files</h3>
    <ul>
        @forelse($orphanBlades as $blade)
            <li>{{ $blade }}</li>
        @empty
            <li>Semua Blade digunakan</li>
        @endforelse
    </ul>

    <h3>2. Orphan Controllers</h3>
    <ul>
        @forelse($orphanControllers as $ctrl)
            <li>{{ $ctrl }}</li>
        @empty
            <li>Semua Controller digunakan</li>
        @endforelse
    </ul>

    <h3>3. Orphan Models</h3>
    <ul>
        @forelse($orphanModels as $model)
            <li>{{ $model }}</li>
        @empty
            <li>Semua Model digunakan</li>
        @endforelse
    </ul>

    <h3>4. Orphan Helpers / Services</h3>
    <ul>
        @forelse($orphanHelpers as $helper)
            <li>{{ $helper }}</li>
        @empty
            <li>Semua Helper digunakan</li>
        @endforelse
    </ul>

    <h3>5. Orphan Public Assets (CSS, JS, Image)</h3>
    <ul>
        @forelse($orphanAssets as $asset)
            <li>{{ $asset }}</li>
        @empty
            <li>Semua asset digunakan</li>
        @endforelse
    </ul>
</div>
@endsection
