@extends('layouts.admin')

@section('content')

<main class="p-6 sm:p-10 space-y-6">

    <div class="flex flex-col space-y-6 md:space-y-0 md:flex-row justify-between">

        <div class="mr-6">

            <h1 class="text-4xl font-semibold mb-2 text-gray-800">Tambah Tag Baru</h1>

            <h2 class="text-gray-600 ml-0.5">Buat tag baru untuk dikelompokkan pada postingan.</h2>

        </div>

    </div>



    <div class="bg-white shadow-md rounded-lg p-6">

        <form action="{{ route('admin.tags.store') }}" method="POST">

            @csrf

            <div class="space-y-4">

                <div>

                    <label for="name" class="block text-sm font-medium text-gray-700">Nama Tag</label>

                    <input type="text" name="name" id="name" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>

                </div>

                <div>

                    <label for="slug" class="block text-sm font-medium text-gray-700">Slug</label>

                    <input type="text" name="slug" id="slug" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">

                    <p class="mt-2 text-xs text-gray-500">Opsional. Jika dikosongkan, slug akan dibuat otomatis dari nama.</p>

                </div>

            </div>

            <div class="mt-6 flex justify-end space-x-3">

                <a href="{{ route('admin.tags.index') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-2 px-4 rounded-md">Batal</a>

                <button type="submit" class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded-md">Simpan Tag</button>

            </div>

        </form>

    </div>

</main>

@endsection

