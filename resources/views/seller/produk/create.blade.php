@extends('layouts.customer')

@section('content')
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <h2 class="text-2xl font-semibold text-gray-800 mb-6">Tambah Produk Baru</h2>
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 bg-white border-b border-gray-200">
                {{-- Menambahkan enctype untuk upload file --}}
                <form method="POST" action="{{ route('seller.produk.store') }}" enctype="multipart/form-data">
                    @csrf
                    {{-- Memuat isian form dari file partial --}}
                    @include('seller.produk.partials.form')
                </form>
            </div>
        </div>
    </div>
</div>
@endsection