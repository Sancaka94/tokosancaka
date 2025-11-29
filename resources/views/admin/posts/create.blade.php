@extends('layouts.admin')



@section('title', 'Tambah Postingan Baru')



@section('content')

<div class="space-y-6">

    <div class="flex flex-col space-y-6 md:space-y-0 md:flex-row justify-between">

        <div class="mr-6">

            <h1 class="text-4xl font-semibold mb-2 text-gray-800">Tambah Postingan Baru</h1>

            <h2 class="text-gray-600 ml-0.5">Tulis manual atau pilih bantuan AI untuk membuat artikel.</h2>

        </div>

    </div>

    <div class="bg-white shadow-md rounded-lg p-6">

        <form action="{{ route('admin.posts.store') }}" method="POST" enctype="multipart/form-data">

            @csrf



            <!-- PERUBAHAN: Desain Toggle Switch dengan 3 Pilihan -->

            <div class="mb-6 p-4 bg-red-50 rounded-lg">

                <div class="flex items-center justify-between">

                    <label class="text-sm font-medium text-gray-900">Bantuan AI</label>

                    <div id="ai-toggle-container" class="relative flex w-72 items-center rounded-full bg-gray-200 p-1">

                        <div id="ai-toggle-slider" class="absolute h-8 w-1/3 transform rounded-full bg-red-600 shadow-md transition-transform"></div>

                        <button type="button" data-model="none" class="ai-choice-btn relative z-10 w-1/3 py-1 text-sm font-bold text-white">None</button>

                        <button type="button" data-model="openai" class="ai-choice-btn relative z-10 w-1/3 py-1 text-sm font-bold text-gray-600">OpenAI</button>

                        <button type="button" data-model="gemini" class="ai-choice-btn relative z-10 w-1/3 py-1 text-sm font-bold text-gray-600">Gemini</button>

                    </div>

                </div>

                 <input type="hidden" name="model" id="ai-model-input" value="none">

            </div>





            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                <div class="lg:col-span-2 space-y-6">

                    <!-- Judul Postingan -->

                    <div>

                        <label for="title" class="block text-sm font-medium text-gray-700">Judul</label>

                        <div class="flex items-center space-x-2 mt-1">

                            <input type="text" name="title" id="title" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" placeholder="Ketik judul di sini..." required>

                            <!-- Tombol Generate disembunyikan secara default -->

                            <div id="generate-btn-container" class="hidden">

                                <button type="button" id="generate-btn" class="px-4 py-2 bg-red-600 text-white font-semibold rounded-md hover:bg-red-700 whitespace-nowrap">

                                    <i class="fas fa-magic mr-2"></i>Generate

                                </button>

                            </div>

                        </div>

                    </div>

                    

                    <!-- Konten Postingan (Editor Teks) -->

                    <div>

                        <label for="content" class="block text-sm font-medium text-gray-700">Konten</label>

                        <div id="loading-indicator" class="hidden my-2 text-sm text-red-500 font-semibold flex items-center space-x-2"><i class="fas fa-spinner fa-spin mr-2"></i>Menghasilkan artikel, mohon tunggu...</div>

                        <textarea name="content" id="content" rows="20" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">{{ old('content') }}</textarea>

                    </div>

                </div>

                <div class="space-y-6">

                    <!-- Kategori -->

                    <div>

                        <label for="category_id" class="block text-sm font-medium text-gray-700">Kategori</label>

                        <select id="category_id" name="category_id" class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" required>

                            <option value="">Pilih Kategori</option>

                            @foreach ($categories as $category)

                                <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>

                            @endforeach

                        </select>

                    </div>

                    <!-- Tag -->

                    <div>

                        <label for="tags" class="block text-sm font-medium text-gray-700">Tag</label>

                        <select id="tags" name="tags[]" multiple class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">

                            @foreach ($tags as $tag)

                                <option value="{{ $tag->id }}">{{ $tag->name }}</option>

                            @endforeach

                        </select>

                    </div>

                    <!-- Gambar Unggulan -->

                    <div>

                        <label class="block text-sm font-medium text-gray-700">Gambar Unggulan</label>

                        <input id="featured_image" name="featured_image" type="file" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-red-50 file:text-red-700 hover:file:bg-red-100">

                    </div>

                </div>

            </div>

            <div class="pt-6 flex justify-end space-x-3">

                <a href="{{ route('admin.posts.index') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-2 px-4 rounded-md">Batal</a>

                <button type="submit" class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded-md">Simpan Postingan</button>

            </div>

        </form>

    </div>

</div>

@endsection



@push('scripts')

{{-- Mengembalikan API Key TinyMCE Anda --}}

<script src="https://cdn.tiny.cloud/1/hsfvd81ihieoadc6tlyol8xucnq3i1n2vzuzfr1948kqqcx5/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>



<script>

document.addEventListener('DOMContentLoaded', function () {

    // Inisialisasi TinyMCE

    tinymce.init({

        selector: 'textarea#content',

        plugins: 'code table lists image link autoresize',

        toolbar: 'undo redo | blocks | bold italic | alignleft aligncenter alignright | indent outdent | bullist numlist | code | table | image link',

        height: 500,

    });



    const titleInput = document.getElementById('title');

    const generateButton = document.getElementById('generate-btn');

    const loadingIndicator = document.getElementById('loading-indicator');

    

    // Logika untuk Toggle Switch 3 pilihan

    const aiModelInput = document.getElementById('ai-model-input');

    const aiToggleSlider = document.getElementById('ai-toggle-slider');

    const aiChoiceButtons = document.querySelectorAll('.ai-choice-btn');

    const generateBtnContainer = document.getElementById('generate-btn-container');



    aiChoiceButtons.forEach((button, index) => {

        button.addEventListener('click', function() {

            const selectedModel = this.dataset.model;

            aiModelInput.value = selectedModel;



            // Update slider position

            aiToggleSlider.style.transform = `translateX(${index * 100}%)`;

            

            // Update text colors

            aiChoiceButtons.forEach(btn => {

                btn.classList.remove('text-white');

                btn.classList.add('text-gray-600', 'dark:text-gray-300');

            });

            this.classList.add('text-white');

            this.classList.remove('text-gray-600', 'dark:text-gray-300');



            // Show/hide generate button

            if (selectedModel === 'none') {

                generateBtnContainer.classList.add('hidden');

            } else {

                generateBtnContainer.classList.remove('hidden');

            }

        });

    });





    generateButton.addEventListener('click', function() {

        const title = titleInput.value.trim();

        const selectedModel = aiModelInput.value;



        if (selectedModel === 'none') {

            // Seharusnya tidak terjadi karena tombol disembunyikan, tapi sebagai pengaman

            return;

        }



        if (title.length < 10) {

            alert('Judul harus memiliki minimal 10 karakter untuk generate konten AI.');

            return;

        }



        loadingIndicator.style.display = 'block';

        generateButton.disabled = true;

        tinymce.get('content').setContent('');



        fetch('{{ route("admin.posts.generateContent") }}', {

            method: 'POST',

            headers: {

                'Content-Type': 'application/json',

                'X-CSRF-TOKEN': '{{ csrf_token() }}'

            },

            body: JSON.stringify({ 

                title: title,

                model: selectedModel 

            })

        })

        .then(response => {

            if (!response.ok) {

                return response.json().then(err => { throw err; });

            }

            return response.json();

        })

        .then(data => {

            if (data.content) {

                tinymce.get('content').setContent(data.content);

            } else {

                tinymce.get('content').setContent('<p style="color: red;"><strong>Error:</strong> ' + (data.error || 'Gagal mendapatkan konten dari server.') + '</p>');

            }

        })

        .catch(error => {

            console.error('Error:', error);

            let errorMessage = error.error || 'Terjadi kesalahan saat menghubungi server. Silakan periksa konsol untuk detail.';

            tinymce.get('content').setContent('<p style="color: red;"><strong>Error:</strong> ' + errorMessage + '</p>');

        })

        .finally(() => {

            loadingIndicator.style.display = 'none';

            generateButton.disabled = false;

        });

    });

});

</script>

@endpush

