@extends('layouts.admin')

@section('title', 'Tambah Postingan Baru')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col space-y-6 md:space-y-0 md:flex-row justify-between">
        <div class="mr-6">
            <h1 class="text-4xl font-semibold mb-2 text-gray-800">Tambah Postingan Baru</h1>
            <h2 class="text-gray-600 ml-0.5">Tulis manual atau pilih bantuan AI untuk membuat artikel & gambar.</h2>
        </div>
    </div>

    <div class="bg-white shadow-md rounded-lg p-6">
        <form action="{{ route('admin.posts.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="ai_generated_image" id="ai_generated_image_path">

            <div class="mb-6 p-4 bg-red-50 rounded-lg border border-red-100">
                <div class="flex items-center justify-between">
                    <label class="text-sm font-medium text-gray-900 flex items-center">
                        <i class="fas fa-robot mr-2 text-red-600"></i> Penulis AI (Artikel)
                    </label>
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
                    <div>
                        <label for="title" class="block text-sm font-medium text-gray-700">Judul</label>
                        <div class="flex items-center space-x-2 mt-1">
                            <input type="text" name="title" id="title" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" placeholder="Ketik judul di sini..." required>
                            
                            <div id="generate-btn-container" class="hidden">
                                <button type="button" id="generate-btn" class="px-4 py-2 bg-red-600 text-white font-semibold rounded-md hover:bg-red-700 whitespace-nowrap transition">
                                    <i class="fas fa-magic mr-2"></i>Tulis Artikel
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div>
                        <label for="content" class="block text-sm font-medium text-gray-700">Konten</label>
                        <div id="loading-indicator" class="hidden my-2 text-sm text-red-500 font-semibold flex items-center space-x-2">
                            <i class="fas fa-spinner fa-spin mr-2"></i>Menghasilkan artikel, mohon tunggu...
                        </div>
                        <textarea name="content" id="content" rows="20" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">{{ old('content') }}</textarea>
                    </div>
                </div>

                <div class="space-y-6">
                    
                    <div>
                        <label for="category_id" class="block text-sm font-medium text-gray-700">Kategori</label>
                        <select id="category_id" name="category_id" class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" required>
                            <option value="">Pilih Kategori</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="tags" class="block text-sm font-medium text-gray-700">Tag</label>
                        <select id="tags" name="tags[]" multiple class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                            @foreach ($tags as $tag)
                                <option value="{{ $tag->id }}">{{ $tag->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <hr class="border-gray-200">

                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                        <div class="flex items-center mb-3">
                            <i class="fas fa-image text-blue-600 text-lg mr-2"></i>
                            <h3 class="font-bold text-blue-800 text-sm">AI Image Generator</h3>
                        </div>

                        <div class="mb-3">
                            <label class="text-xs font-semibold text-gray-600 block mb-1">1. Prompt Gambar (Inggris)</label>
                            <div class="relative">
                                <textarea id="ai_image_prompt" rows="3" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-xs p-2" placeholder="Klik tombol di bawah untuk buat prompt otomatis dari Judul..."></textarea>
                                <button type="button" id="btn-gen-prompt" onclick="generatePrompt()" class="mt-2 w-full bg-yellow-500 hover:bg-yellow-600 text-white text-xs font-bold py-1.5 px-3 rounded shadow transition">
                                    <i class="fas fa-magic mr-1"></i> Buat Prompt dari Judul
                                </button>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="text-xs font-semibold text-gray-600 block mb-1">2. Buat Gambar Fisik</label>
                            <button type="button" id="btn-gen-image" onclick="generateImage()" class="w-full bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold py-2 px-3 rounded shadow transition flex justify-center items-center">
                                <i class="fas fa-paint-brush mr-2"></i> Generate Gambar Sekarang
                            </button>
                        </div>

                        <div id="ai-image-loading" class="hidden text-center py-2">
                            <i class="fas fa-spinner fa-spin text-blue-600 text-xl"></i>
                            <p class="text-xs text-blue-600 mt-1">Sedang menggambar...</p>
                        </div>

                        <div id="ai-image-preview-container" class="hidden mt-3 text-center bg-white p-2 rounded border border-gray-200">
                            <p class="text-xs text-green-600 font-bold mb-2">Selesai!</p>
                            <img id="ai-result-image" src="" class="w-full h-auto rounded shadow-sm mb-2 object-cover" style="max-height: 200px;">
                            <button type="button" onclick="useAiImage()" class="w-full bg-green-600 hover:bg-green-700 text-white text-xs font-bold py-1 px-2 rounded transition">
                                <i class="fas fa-check mr-1"></i> Gunakan Gambar Ini
                            </button>
                            <span id="temp-ai-path" class="hidden"></span>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Upload Manual (Opsional)</label>
                        <input id="featured_image" name="featured_image" type="file" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-gray-100 file:text-gray-700 hover:file:bg-gray-200">
                        <p class="text-xs text-gray-500 mt-1">*Jika upload manual kosong, gambar AI yang dipilih akan digunakan.</p>
                    </div>

                </div>
            </div>

            <div class="pt-6 flex justify-end space-x-3 border-t mt-6 border-gray-200">
                <a href="{{ route('admin.posts.index') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-2 px-4 rounded-md transition">Batal</a>
                <button type="submit" class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded-md transition shadow-lg">Simpan Postingan</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
{{-- TinyMCE --}}
<script src="https://cdn.tiny.cloud/1/hsfvd81ihieoadc6tlyol8xucnq3i1n2vzuzfr1948kqqcx5/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // 1. Inisialisasi TinyMCE
    tinymce.init({
        selector: 'textarea#content',
        plugins: 'code table lists image link autoresize',
        toolbar: 'undo redo | blocks | bold italic | alignleft aligncenter alignright | indent outdent | bullist numlist | code | table | image link',
        height: 500,
    });

    // 2. Variabel Logika Artikel AI
    const titleInput = document.getElementById('title');
    const generateButton = document.getElementById('generate-btn');
    const loadingIndicator = document.getElementById('loading-indicator');
    
    // Toggle Switch Variables
    const aiModelInput = document.getElementById('ai-model-input');
    const aiToggleSlider = document.getElementById('ai-toggle-slider');
    const aiChoiceButtons = document.querySelectorAll('.ai-choice-btn');
    const generateBtnContainer = document.getElementById('generate-btn-container');

    // 3. Logika Toggle Switch AI Text
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

    // 4. Logika Generate Artikel (Text)
    generateButton.addEventListener('click', function() {
        const title = titleInput.value.trim();
        const selectedModel = aiModelInput.value;

        if (selectedModel === 'none') return;

        if (title.length < 5) {
            alert('Judul terlalu pendek untuk generate konten.');
            return;
        }

        loadingIndicator.style.display = 'block';
        generateButton.disabled = true;
        generateButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Generating...';
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
        .then(response => response.json())
        .then(data => {
            if (data.content) {
                tinymce.get('content').setContent(data.content);
            } else {
                tinymce.get('content').setContent('<p style="color: red;">Error: ' + (data.error || 'Gagal') + '</p>');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Terjadi kesalahan koneksi.');
        })
        .finally(() => {
            loadingIndicator.style.display = 'none';
            generateButton.disabled = false;
            generateButton.innerHTML = '<i class="fas fa-magic mr-2"></i>Tulis Artikel';
        });
    });
});

// ==========================================================
// 5. FUNGSI BARU: GENERATE GAMBAR AI (Global Functions)
// ==========================================================

function generatePrompt() {
    let title = document.getElementById('title').value;
    if(!title || title.length < 5) {
        alert('Harap isi Judul Artikel terlebih dahulu minimal 5 karakter!');
        return;
    }

    const btn = document.getElementById('btn-gen-prompt');
    const originalText = btn.innerHTML;
    
    // UI Loading
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading...';

    fetch("{{ route('admin.posts.generate_image_prompt') }}", {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
            "X-CSRF-TOKEN": "{{ csrf_token() }}"
        },
        body: JSON.stringify({ title: title })
    })
    .then(response => response.json())
    .then(data => {
        if(data.prompt) {
            document.getElementById('ai_image_prompt').value = data.prompt;
        } else {
            alert('Gagal membuat prompt: ' + (data.error || 'Unknown error'));
        }
    })
    .catch(error => alert('Error: ' + error))
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = originalText;
    });
}

function generateImage() {
    let prompt = document.getElementById('ai_image_prompt').value;
    if(!prompt) {
        alert('Prompt tidak boleh kosong!');
        return;
    }

    // UI Loading
    const loadingDiv = document.getElementById('ai-image-loading');
    const previewDiv = document.getElementById('ai-image-preview-container');
    const btn = document.getElementById('btn-gen-image');

    loadingDiv.classList.remove('hidden');
    previewDiv.classList.add('hidden');
    btn.disabled = true;
    btn.classList.add('opacity-50', 'cursor-not-allowed');

    fetch("{{ route('admin.posts.generate_image') }}", {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
            "X-CSRF-TOKEN": "{{ csrf_token() }}"
        },
        body: JSON.stringify({ prompt: prompt })
    })
    .then(response => response.json())
    .then(data => {
        if(data.success) {
            // Tampilkan Preview
            document.getElementById('ai-result-image').src = data.image_url;
            // Simpan path di element sementara
            document.getElementById('temp-ai-path').innerText = data.file_path;
            
            previewDiv.classList.remove('hidden');
        } else {
            alert('Gagal: ' + (data.error || 'API Error'));
        }
    })
    .catch(error => {
        console.error(error);
        alert('Terjadi kesalahan sistem saat generate gambar.');
    })
    .finally(() => {
        loadingDiv.classList.add('hidden');
        btn.disabled = false;
        btn.classList.remove('opacity-50', 'cursor-not-allowed');
    });
}

function useAiImage() {
    // Ambil path dari element sementara
    let filePath = document.getElementById('temp-ai-path').innerText;
    
    if(!filePath) {
        alert('Belum ada gambar yang digenerate.');
        return;
    }

    // Masukkan ke Input Hidden agar dikirim ke Controller
    document.getElementById('ai_generated_image_path').value = filePath;
    
    // Beri Feedback Visual
    const useBtn = document.querySelector('#ai-image-preview-container button');
    useBtn.innerHTML = '<i class="fas fa-check-circle"></i> Terpilih!';
    useBtn.classList.remove('bg-green-600', 'hover:bg-green-700');
    useBtn.classList.add('bg-gray-500', 'cursor-default');
    useBtn.disabled = true;

    // Reset input file manual agar tidak double
    document.getElementById('featured_image').value = '';

    alert('Gambar AI berhasil dipilih! Klik tombol "Simpan Postingan" di bawah untuk menyimpan artikel.');
}
</script>
@endpush