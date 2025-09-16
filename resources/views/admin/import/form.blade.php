@extends('layouts.admin')
@section('content')

<main class="p-6 sm:p-10 space-y-6">
    <!-- Header Halaman -->
    <div class="flex flex-col space-y-6 md:space-y-0 md:flex-row justify-between">
        <div class="mr-6">
            <h1 class="text-4xl font-semibold mb-2 text-gray-800 dark:text-gray-200">Import Postingan</h1>
            <h2 class="text-gray-600 dark:text-gray-400 ml-0.5">Upload file XML dari WordPress untuk impor massal.</h2>
        </div>
        <div class="flex flex-wrap items-start justify-end -mb-3">
            <a href="{{ route('admin.posts.index') }}" class="inline-flex px-5 py-3 text-white bg-purple-600 hover:bg-purple-700 focus:bg-purple-700 rounded-md ml-6 mb-3">
                <i class="fas fa-arrow-left mr-2"></i>
                Kembali ke Daftar Post
            </a>
        </div>
    </div>

    <!-- Konten Utama: Form Upload -->
    <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg p-8">
        @if (session('success'))
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded" role="alert">
                <p class="font-bold">Berhasil</p>
                <p>{{ session('success') }}</p>
            </div>
        @endif

        @if ($errors->any())
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded" role="alert">
                 <p class="font-bold">Error</p>
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <form action="{{ route('admin.import.wordpress.handle') }}" method="POST" enctype="multipart/form-data" id="import-form">
            @csrf

            <!-- Zona Drag and Drop -->
            <div>
                <label for="wordpress_xml" id="drop-zone" class="flex flex-col items-center justify-center w-full h-64 border-2 border-gray-300 border-dashed rounded-lg cursor-pointer bg-gray-50 dark:hover:bg-bray-800 dark:bg-gray-700 hover:bg-gray-100 dark:border-gray-600 dark:hover:border-gray-500 dark:hover:bg-gray-600 transition-colors">
                    <div class="flex flex-col items-center justify-center pt-5 pb-6">
                        <svg class="w-10 h-10 mb-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-4-4V6a4 4 0 014-4h10a4 4 0 014 4v6a4 4 0 01-4 4H7z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 16v2a2 2 0 01-2 2H10a2 2 0 01-2-2v-2m-3-10V4a2 2 0 012-2h10a2 2 0 012 2v2"></path></svg>
                        <p class="mb-2 text-sm text-gray-500 dark:text-gray-400"><span class="font-semibold">Klik untuk mengunggah</span> atau seret dan lepas</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Hanya file XML (Maks. 128MB)</p>
                        <p id="file-name-display" class="mt-4 text-sm font-semibold text-purple-700 dark:text-purple-300"></p>
                    </div>
                    <input type="file" name="wordpress_xml" id="wordpress_xml" class="hidden" accept=".xml" required>
                </label>
            </div>

            <!-- Progress Bar Section -->
            <div id="progress-container" class="w-full bg-gray-200 rounded-full h-4 mt-6 hidden">
                <div id="progress-bar" class="bg-purple-600 h-4 rounded-full text-center text-white text-xs leading-none" style="width: 0%">
                   <span id="progress-text">0%</span>
                </div>
            </div>

            <!-- Status Message Section -->
            <div id="upload-status" class="mt-4 text-sm font-medium"></div>

            <div class="mt-8 border-t border-gray-200 dark:border-gray-700 pt-5">
                <div class="flex justify-end">
                    <button type="submit" id="submit-button" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-purple-600 hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500">
                        <i class="fas fa-upload mr-2"></i>
                        Mulai Proses Impor
                    </button>
                </div>
            </div>
        </form>
    </div>
</main>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('import-form');
    const dropZone = document.getElementById('drop-zone');
    const fileInput = document.getElementById('wordpress_xml');
    const fileNameDisplay = document.getElementById('file-name-display');
    const progressBarContainer = document.getElementById('progress-container');
    const progressBar = document.getElementById('progress-bar');
    const progressText = document.getElementById('progress-text');
    const uploadStatus = document.getElementById('upload-status');
    const submitButton = document.getElementById('submit-button');

    // Mencegah default behavior browser
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        dropZone.addEventListener(eventName, preventDefaults, false);
        document.body.addEventListener(eventName, preventDefaults, false);
    });

    function preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }

    // Memberi highlight saat file diseret di atas zona drop
    ['dragenter', 'dragover'].forEach(eventName => {
        dropZone.addEventListener(eventName, () => dropZone.classList.add('border-purple-600', 'bg-purple-50'), false);
    });

    ['dragleave', 'drop'].forEach(eventName => {
        dropZone.addEventListener(eventName, () => dropZone.classList.remove('border-purple-600', 'bg-purple-50'), false);
    });

    // Menangani file yang di-drop
    dropZone.addEventListener('drop', function(e) {
        fileInput.files = e.dataTransfer.files;
        handleFiles(fileInput.files);
    }, false);

    // Menangani file yang dipilih lewat klik
    fileInput.addEventListener('change', function() {
        handleFiles(this.files);
    });

    function handleFiles(files) {
        if (files.length > 0) {
            fileNameDisplay.textContent = `File dipilih: ${files[0].name}`;
        } else {
            fileNameDisplay.textContent = '';
        }
    }

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        
        if (fileInput.files.length === 0) {
            uploadStatus.innerHTML = '<div class="text-red-600">Silakan pilih file XML terlebih dahulu.</div>';
            return;
        }

        const formData = new FormData(form);
        const xhr = new XMLHttpRequest();

        progressBarContainer.classList.remove('hidden');
        progressBar.style.width = '0%';
        progressText.textContent = '0%';
        uploadStatus.innerHTML = '';
        submitButton.disabled = true;
        submitButton.innerHTML = `<i class="fas fa-spinner fa-spin mr-2"></i> Mengunggah...`;

        xhr.upload.addEventListener('progress', function (e) {
            if (e.lengthComputable) {
                const percentComplete = Math.round((e.loaded / e.total) * 100);
                progressBar.style.width = percentComplete + '%';
                progressText.textContent = percentComplete + '%';
                if (percentComplete === 100) {
                   uploadStatus.textContent = 'Upload selesai. Memproses file di server...';
                   submitButton.innerHTML = `<i class="fas fa-cog fa-spin mr-2"></i> Memproses...`;
                }
            }
        });

        xhr.onload = function () {
            submitButton.disabled = false;
            submitButton.innerHTML = `<i class="fas fa-upload mr-2"></i> Mulai Proses Impor`;

            if (xhr.status >= 200 && xhr.status < 300) {
                const response = JSON.parse(xhr.responseText);
                uploadStatus.innerHTML = `<div class="text-green-600">${response.message}</div>`;
                form.reset();
                fileNameDisplay.textContent = '';
            } else {
                try {
                    const errorResponse = JSON.parse(xhr.responseText);
                    const errorMessage = errorResponse.message || 'Terjadi kesalahan tidak diketahui.';
                    uploadStatus.innerHTML = `<div class="text-red-600"><strong>Gagal:</strong> ${errorMessage}</div>`;
                } catch(e) {
                     uploadStatus.innerHTML = `<div class="text-red-600"><strong>Gagal:</strong> Terjadi kesalahan server yang tidak terduga.</div>`;
                }
            }
        };

        xhr.onerror = function () {
            submitButton.disabled = false;
            submitButton.innerHTML = `<i class="fas fa-upload mr-2"></i> Mulai Proses Impor`;
            uploadStatus.innerHTML = '<div class="text-red-600">Terjadi error jaringan. Periksa koneksi Anda.</div>';
        };

        xhr.open('POST', form.action, true);
        xhr.setRequestHeader('X-CSRF-TOKEN', document.querySelector('input[name="_token"]').value);
        xhr.setRequestHeader('Accept', 'application/json');
        xhr.send(formData);
    });
});
</script>
@endpush

