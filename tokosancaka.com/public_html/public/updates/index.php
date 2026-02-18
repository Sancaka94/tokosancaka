<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sancaka POS Repository</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap');
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-slate-100 min-h-screen py-10 px-4">

    <div class="max-w-5xl mx-auto">
        <div class="bg-white rounded-t-xl shadow-sm p-6 border-b border-slate-200 flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-bold text-slate-800">Sancaka POS Updates</h1>
                <p class="text-slate-500 text-sm mt-1">Repository download resmi aplikasi Sancaka POS</p>
            </div>
            <div class="text-right hidden md:block">
                <span class="bg-blue-100 text-blue-800 text-xs font-semibold px-2.5 py-0.5 rounded border border-blue-400">Windows x64</span>
            </div>
        </div>

        <div class="bg-white rounded-b-xl shadow-lg overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left text-slate-600">
                    <thead class="text-xs text-slate-700 uppercase bg-slate-50 border-b">
                        <tr>
                            <th scope="col" class="px-6 py-4 w-16">No</th>
                            <th scope="col" class="px-6 py-4">Nama Aplikasi</th>
                            <th scope="col" class="px-6 py-4">Versi</th>
                            <th scope="col" class="px-6 py-4">Platform</th>
                            <th scope="col" class="px-6 py-4">Rilis</th>
                            <th scope="col" class="px-6 py-4 text-center">Detail</th>
                            <th scope="col" class="px-6 py-4 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // SCAN FILE EXE
                        $files = glob("*.exe");
                        
                        // Fungsi untuk ekstrak versi dari nama file
                        function getVersion($filename) {
                            if (preg_match('/(\d+\.\d+\.\d+)/', $filename, $matches)) {
                                return $matches[1];
                            }
                            return '0.0.0';
                        }

                        // Sorting versi terbaru paling atas
                        usort($files, function($a, $b) {
                            return version_compare(getVersion($b), getVersion($a));
                        });

                        $no = 1;
                        if (count($files) > 0):
                            foreach ($files as $file):
                                $version = getVersion($file);
                                $filesize = round(filesize($file) / 1024 / 1024, 2) . ' MB';
                                $date = date("d M Y H:i", filemtime($file));
                                
                                // Cek apakah ada file catatan update (misal: 1.0.1.txt)
                                $noteFile = $version . '.txt';
                                $hasNote = file_exists($noteFile);
                                $noteContent = $hasNote ? file_get_contents($noteFile) : "";
                        ?>
                        <tr class="border-b hover:bg-slate-50 transition duration-150">
                            <td class="px-6 py-4 font-medium text-slate-900"><?= $no++ ?></td>
                            <td class="px-6 py-4 font-semibold text-slate-800">
                                Sancaka POS Setup
                                <?php if($no == 2): ?>
                                    <span class="ml-2 bg-green-100 text-green-800 text-xs font-medium px-2 py-0.5 rounded">Latest</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4">
                                <span class="bg-gray-100 text-gray-800 text-xs font-medium px-2.5 py-0.5 rounded border border-gray-300">v<?= $version ?></span>
                            </td>
                            <td class="px-6 py-4">Win x64</td>
                            <td class="px-6 py-4 text-slate-500 text-xs"><?= $date ?></td>
                            
                            <td class="px-6 py-4 text-center">
                                <?php if($hasNote): ?>
                                    <button onclick="openModal('v<?= $version ?>', `<?= htmlspecialchars($noteContent) ?>`)" 
                                            class="text-blue-600 hover:text-blue-900 font-medium text-xs underline decoration-dotted cursor-pointer">
                                        Lihat Changelog
                                    </button>
                                <?php else: ?>
                                    <span class="text-slate-300 text-xs">-</span>
                                <?php endif; ?>
                            </td>

                            <td class="px-6 py-4 text-center">
                                <a href="<?= $file ?>" class="inline-flex items-center px-3 py-2 text-xs font-medium text-center text-white bg-blue-700 rounded-lg hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 shadow-md transition-all">
                                    <i class="fas fa-download mr-2"></i> Download
                                </a>
                                <div class="text-[10px] text-slate-400 mt-1"><?= $filesize ?></div>
                            </td>
                        </tr>
                        <?php endforeach; 
                        else: ?>
                        <tr>
                            <td colspan="7" class="px-6 py-8 text-center text-slate-500">Belum ada file update yang diupload.</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <div class="mt-6 text-center text-slate-400 text-xs">
            &copy; <?= date('Y') ?> Sancaka Karya Hutama. All rights reserved.
        </div>
    </div>

    <div id="modal" class="fixed inset-0 z-50 hidden bg-gray-900 bg-opacity-50 flex justify-center items-center backdrop-blur-sm">
        <div class="bg-white w-full max-w-md rounded-lg shadow-2xl transform transition-all p-6">
            <div class="flex justify-between items-center mb-4 border-b pb-2">
                <h3 class="text-lg font-bold text-gray-900">Apa yang baru di <span id="modal-version"></span>?</h3>
                <button onclick="closeModal()" class="text-gray-400 hover:text-gray-900">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="text-sm text-gray-600 mb-6 whitespace-pre-line leading-relaxed bg-gray-50 p-3 rounded border" id="modal-content">
                </div>
            <div class="text-right">
                <button onclick="closeModal()" class="px-4 py-2 bg-slate-200 text-slate-800 rounded-md hover:bg-slate-300 text-sm font-medium">Tutup</button>
            </div>
        </div>
    </div>

    <script>
        function openModal(version, content) {
            document.getElementById('modal-version').innerText = version;
            document.getElementById('modal-content').innerText = content;
            document.getElementById('modal').classList.remove('hidden');
        }

        function closeModal() {
            document.getElementById('modal').classList.add('hidden');
        }
    </script>
</body>
</html>