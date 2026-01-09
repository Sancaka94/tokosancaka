@extends('layouts.admin')

@section('title', isset($role) ? 'Edit Role' : 'Tambah Role Baru')
@section('page-title', isset($role) ? 'Edit Role' : 'Tambah Role Baru')

@section('content')
<div class="bg-white p-4 sm:p-6 rounded-lg shadow-md max-w-xl mx-auto">
    <form action="{{ isset($role) ? route('admin.roles.update', $role) : route('admin.roles.store') }}" method="POST">
        @csrf
        @if(isset($role))
            @method('PUT')
        @endif

        <div class="mb-6">
            <label for="name" class="block text-sm font-medium text-gray-700 mb-2">Nama Role</label>
            <input type="text" name="name" id="name" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 @error('name') border-red-500 @enderror" value="{{ old('name', $role->name ?? '') }}" required>
            @error('name')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>
        
        <div class="flex justify-end space-x-4">
            <a href="{{ route('admin.roles.index') }}" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm font-medium hover:bg-gray-300">
                Batal
            </a>
            <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-indigo-700">
                <i class="fas fa-save me-2"></i>Simpan
            </button>
        </div>
    </form>
</div>
@endsection
