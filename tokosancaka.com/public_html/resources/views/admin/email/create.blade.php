@extends('layouts.admin')



@section('title', 'Tulis Email Baru')

@section('page-title', 'Tulis Email Baru')



@section('content')

{{-- Letakkan di bawah @section('content') --}}
@if (Session::has('success'))
    <div class="mb-4 bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded-md" role="alert">
        <p class="font-bold">Berhasil</p>
        <p>{{ Session::get('success') }}</p>
    </div>
@endif

<div class="bg-white rounded-xl shadow-lg p-6 md:p-8">



    {{-- Form untuk mengirim email --}}

    {{-- PERBAIKAN: Mengubah action route agar sesuai dengan file web.php --}}

    <form action="{{ route('admin.email.send') }}" method="POST">

        @csrf



        {{-- Menampilkan pesan error umum dari controller --}}

        @if (Session::has('error'))

            <div class="mb-4 bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded-md" role="alert">

                <p class="font-bold">Error</p>

                <p>{{ Session::get('error') }}</p>

            </div>

        @endif



        {{-- Input untuk Penerima (To) --}}

        <div class="mb-5">

            <label for="to" class="block mb-2 text-sm font-medium text-gray-900">Kepada:</label>

            <input type="email" id="to" name="to" value="{{ old('to') }}"

                   class="bg-gray-50 border @error('to') border-red-500 @else border-gray-300 @enderror text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5"

                   placeholder="penerima@example.com" required>

            @error('to')

                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>

            @enderror

        </div>



        {{-- Input untuk Subjek --}}

        <div class="mb-5">

            <label for="subject" class="block mb-2 text-sm font-medium text-gray-900">Subjek:</label>

            <input type="text" id="subject" name="subject" value="{{ old('subject') }}"

                   class="bg-gray-50 border @error('subject') border-red-500 @else border-gray-300 @enderror text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5"

                   placeholder="Subjek email Anda" required>

            @error('subject')

                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>

            @enderror

        </div>



        {{-- Textarea untuk Isi Email (Body) --}}

        <div class="mb-6">

            <label for="body" class="block mb-2 text-sm font-medium text-gray-900">Isi Pesan:</label>

            <textarea id="body" name="body" rows="10"

                      class="bg-gray-50 border @error('body') border-red-500 @else border-gray-300 @enderror text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5"

                      placeholder="Tulis isi email Anda di sini..." required>{{ old('body') }}</textarea>

            @error('body')

                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>

            @enderror

        </div>



        {{-- Tombol Aksi --}}

        <div class="flex items-center justify-end gap-4">

            <a href="{{ route('admin.email.index') }}" class="text-gray-600 hover:text-gray-900 font-medium rounded-lg text-sm px-5 py-2.5 text-center">

                Batal

            </a>

            <button type="submit"

                    class="text-white bg-blue-600 hover:bg-blue-700 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center">

                <i class="fas fa-paper-plane mr-2"></i>

                Kirim Email

            </button>

        </div>



    </form>



</div>

@endsection

