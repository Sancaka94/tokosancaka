<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Kota</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 text-gray-900 font-sans antialiased">

    <div class="max-w-6xl mx-auto p-8">
        
        <!-- Header -->
        <div class="flex justify-between items-center mb-8 border-b border-gray-200 pb-4">
            <h1 class="text-2xl font-semibold tracking-tight text-black">Data Kota</h1>
            <a href="{{ route('dashboard') }}" class="text-sm text-gray-500 hover:text-black transition-colors">
                &larr; Kembali ke Dashboard
            </a>
        </div>

        @if(session('success'))
            <div class="mb-4 px-4 py-3 bg-white border border-black text-black text-sm rounded-md shadow-sm">
                {{ session('success') }}
            </div>
        @endif

       <!-- Form Upload Card -->
        <div class="bg-white border border-gray-200 rounded-lg shadow-sm p-6 mb-8">
            <div class="flex justify-between items-center mb-5">
                <div>
                    <h2 class="text-sm font-semibold text-black">Upload File CSV / XLSX</h2>
                    <p class="text-xs text-gray-500 mt-1">Tarik dan lepas file Anda ke dalam kotak putus-putus di bawah.</p>
                </div>
                
                <!-- LOG LOG - Tombol Download Contoh CSV (Next.js Secondary Button Style) -->
                <a href="{{ route('cities.example') }}" class="inline-flex items-center gap-2 px-3 py-1.5 text-xs font-medium text-black bg-white border border-gray-200 rounded-md hover:bg-gray-50 hover:border-gray-300 transition-all shadow-sm">
                    <svg class="w-3.5 h-3.5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                    Contoh CSV
                </a>
            </div>

            <!-- Tambahkan id="upload-form" di sini -->
            <form id="upload-form" action="{{ route('cities.import') }}" method="POST" enctype="multipart/form-data" class="flex flex-col gap-4">
                @csrf
                
                <!-- Area Drag & Drop -->
                <div class="w-full">
                    <label for="file-upload" id="drop-zone" class="flex flex-col items-center justify-center w-full h-32 border-2 border-green-300 border-dashed rounded-lg cursor-pointer bg-green-50 hover:bg-green-100 transition-all duration-200 ease-in-out group">
                        <div class="flex flex-col items-center justify-center pt-5 pb-6 pointer-events-none">
                            <svg class="w-8 h-8 mb-3 text-gray-400 group-hover:text-black transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path></svg>
                            <p class="mb-2 text-sm text-gray-500"><span class="font-semibold text-black">Klik untuk memilih</span> atau drag and drop file ke sini</p>
                            <p class="text-xs text-gray-400">Format: CSV atau XLSX</p>
                            <p id="file-name" class="mt-3 px-3 py-1 bg-green-200 text-black text-xs font-semibold rounded-md hidden"></p>
                        </div>
                        <input id="file-upload" name="file" type="file" class="hidden" accept=".csv, .xlsx" required />
                    </label>
                </div>

                <!-- LOG LOG - Container Progress Bar (Sembunyi secara default) -->
                <div id="progress-container" class="hidden w-full mt-2">
                    <div class="flex justify-between items-center mb-1.5 text-xs font-medium">
                        <span id="progress-text" class="text-red-600">Mengunggah... 0%</span>
                        <span id="time-text" class="text-gray-500 tracking-tight">Menghitung durasi...</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2.5 overflow-hidden">
                        <div id="progress-bar" class="bg-red-500 h-2.5 rounded-full transition-all duration-200 ease-out" style="width: 0%"></div>
                    </div>
                </div>

                <div class="flex justify-end border-t border-gray-100 pt-4 mt-2">
                    <button type="submit" id="submit-btn" class="px-6 py-2.5 bg-black text-white text-sm font-medium rounded-md hover:bg-gray-800 transition-colors shadow-sm focus:ring-2 focus:ring-offset-2 focus:ring-black">
                        Upload & Proses
                    </button>
                </div>
            </form>

        </div>

        <!-- Tambahkan Script ini tepat di atas tag penutup </body> pada file yang sama -->
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const dropZone = document.getElementById('drop-zone');
                const fileInput = document.getElementById('file-upload');
                const fileNameDisplay = document.getElementById('file-name');

                // Mencegah browser membuka file secara default saat di-drag
                ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                    dropZone.addEventListener(eventName, preventDefaults, false);
                    document.body.addEventListener(eventName, preventDefaults, false);
                });

                function preventDefaults (e) {
                    e.preventDefault();
                    e.stopPropagation();
                }

                // Memberikan efek visual (hijau lebih gelap) saat file berada di atas kotak
                ['dragenter', 'dragover'].forEach(eventName => {
                    dropZone.addEventListener(eventName, () => {
                        dropZone.classList.add('bg-green-200', 'border-green-500');
                        dropZone.classList.remove('bg-green-50', 'border-green-300');
                    }, false);
                });

                // Menghilangkan efek visual saat file keluar kotak
                ['dragleave', 'drop'].forEach(eventName => {
                    dropZone.addEventListener(eventName, () => {
                        dropZone.classList.remove('bg-green-200', 'border-green-500');
                        dropZone.classList.add('bg-green-50', 'border-green-300');
                    }, false);
                });

                // Menangkap file yang di-drop
                dropZone.addEventListener('drop', (e) => {
                    const dt = e.dataTransfer;
                    const files = dt.files;
                    handleFiles(files);
                }, false);

                // Menangkap file yang dipilih lewat klik
                fileInput.addEventListener('change', function(e) {
                    handleFiles(this.files);
                });

                // Menampilkan nama file ke layar
                function handleFiles(files) {
                    if (files.length > 0) {
                        fileInput.files = files; // Memasukkan file ke input tersembunyi
                        fileNameDisplay.textContent = 'File siap: ' + files[0].name;
                        fileNameDisplay.classList.remove('hidden');
                    }
                }

                // LOG LOG - Intersep Form Submit untuk Progress Bar Realtime
                const form = document.getElementById('upload-form');
                const progressBarContainer = document.getElementById('progress-container');
                const progressBar = document.getElementById('progress-bar');
                const progressText = document.getElementById('progress-text');
                const timeText = document.getElementById('time-text');
                const submitBtn = document.getElementById('submit-btn');

                form.addEventListener('submit', function(e) {
                    e.preventDefault(); // Mencegah loading halaman standar

                    if (fileInput.files.length === 0) return;

                    // Tampilkan UI Loading
                    progressBarContainer.classList.remove('hidden');
                    submitBtn.disabled = true;
                    submitBtn.classList.add('opacity-50', 'cursor-not-allowed');
                    submitBtn.innerText = "Memproses...";

                    const formData = new FormData(form);
                    const xhr = new XMLHttpRequest();
                    let startTime = new Date().getTime();

                    // Event saat proses upload berjalan
                    xhr.upload.addEventListener('progress', function(e) {
                        if (e.lengthComputable) {
                            let percentComplete = Math.round((e.loaded / e.total) * 100);
                            
                            // Animasi Lebar Bar
                            progressBar.style.width = percentComplete + '%';

                            if (percentComplete < 100) {
                                // Warna Merah jika belum 100%
                                progressBar.classList.remove('bg-green-500');
                                progressBar.classList.add('bg-red-500');
                                progressText.classList.remove('text-green-600');
                                progressText.classList.add('text-red-600');
                                progressText.innerText = 'Mengunggah... ' + percentComplete + '%';

                                // Kalkulasi Sisa Waktu Nyata
                                let currentTime = new Date().getTime();
                                let elapsedSeconds = (currentTime - startTime) / 1000;
                                
                                if (elapsedSeconds > 0) {
                                    let bytesPerSecond = e.loaded / elapsedSeconds;
                                    let remainingBytes = e.total - e.loaded;
                                    let remainingSeconds = Math.round(remainingBytes / bytesPerSecond);
                                    
                                    let minutes = Math.floor(remainingSeconds / 60);
                                    let seconds = remainingSeconds % 60;
                                    
                                    // Format menit dan detik
                                    let timeString = '';
                                    if (minutes > 0) timeString += minutes + 'm ';
                                    timeString += seconds + 's';
                                    timeText.innerText = 'Sisa durasi: ' + timeString;
                                }
                            } else {
                                // Warna Hijau saat 100% dan menunggu respons server database
                                progressBar.classList.remove('bg-red-500');
                                progressBar.classList.add('bg-green-500');
                                progressText.classList.remove('text-red-600');
                                progressText.classList.add('text-green-600');
                                progressText.innerText = 'Selesai 100% - Memproses ke Database...';
                                timeText.innerText = 'Mohon tunggu...';
                            }
                        }
                    });

                    // Event saat selesai dan dapat respon dari Laravel
                    xhr.onload = function() {
                        if (xhr.status >= 200 && xhr.status < 300) {
                            // Sukses! Refresh halaman untuk memunculkan flash message Laravel
                            window.location.reload();
                        } else {
                            alert('Terjadi kesalahan saat memproses file Anda.');
                            submitBtn.disabled = false;
                            submitBtn.classList.remove('opacity-50', 'cursor-not-allowed');
                            submitBtn.innerText = "Upload & Proses";
                            progressBarContainer.classList.add('hidden');
                        }
                    };

                    xhr.open('POST', form.action, true);
                    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest'); // Beritahu Laravel ini AJAX
                    xhr.send(formData);
                });
                
            });
        </script>

        <!-- Tabel Data -->
        <div class="bg-white border border-gray-200 rounded-lg shadow-sm overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left font-medium text-gray-500 w-16">No</th>
                        <th class="px-6 py-3 text-left font-medium text-gray-500">Nama Kota</th>
                        <th class="px-6 py-3 text-left font-medium text-gray-500">Keterangan</th>
                        <th class="px-6 py-3 text-right font-medium text-gray-500 w-32">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white">
                    @forelse($cities as $index => $city)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4 text-gray-500">{{ $index + 1 }}</td>
                            <td class="px-6 py-4 font-medium text-black">{{ $city->nama_kota }}</td>
                            <td class="px-6 py-4 text-gray-600">{{ $city->keterangan }}</td>
                            <td class="px-6 py-4 text-right">
                                <form action="{{ route('cities.destroy', $city->id) }}" method="POST" onsubmit="return confirm('Hapus data ini?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-gray-400 hover:text-black font-medium transition-colors">
                                        Hapus
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-8 text-center text-gray-400">
                                Belum ada data. Silakan upload file CSV di atas.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</body>
</html>