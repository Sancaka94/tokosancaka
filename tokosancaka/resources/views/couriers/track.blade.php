@extends('layouts.admin')

@section('styles')
    {{-- Leaflet CSS --}}
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
    <style>
        #map { height: 600px; }
    </style>
@endsection

@section('content')
<div class="container mx-auto p-4 sm:p-6 lg:p-8">
    <header class="mb-8">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-bold text-gray-800">Lacak Posisi Kurir</h1>
                <p class="text-gray-600 mt-1">Lokasi terakhir dari {{ $courier->full_name }}.</p>
            </div>
             <a href="{{ route('admin.couriers.index') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-2 px-4 rounded-lg transition duration-300">
                <i class="fa fa-arrow-left mr-2"></i>Kembali
            </a>
        </div>
    </header>

    <main class="bg-white rounded-lg shadow-md overflow-hidden">
        @if($courier->latitude && $courier->longitude)
            <div id="map"></div>
        @else
            <div class="p-16 text-center">
                <i class="fa fa-map-marker-alt text-6xl text-gray-300 mb-4"></i>
                <h3 class="text-xl font-semibold text-gray-700">Data Lokasi Tidak Ditemukan</h3>
                <p class="text-gray-500 mt-2">Kurir ini belum pernah mengirimkan data lokasi GPS.</p>
            </div>
        @endif
    </main>
</div>
@endsection

@section('scripts')
    @if($courier->latitude && $courier->longitude)
    {{-- Leaflet JS --}}
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <script>
        var lat = {{ $courier->latitude }};
        var lng = {{ $courier->longitude }};

        var map = L.map('map').setView([lat, lng], 15);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '© OpenStreetMap'
        }).addTo(map);

        var marker = L.marker([lat, lng]).addTo(map);
        marker.bindPopup("<b>{{ $courier->full_name }}</b><br>Posisi terakhir.").openPopup();
    </script>
    @endif
@endsection
