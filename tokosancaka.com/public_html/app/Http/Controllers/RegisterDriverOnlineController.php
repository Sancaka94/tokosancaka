<?php

namespace App\Http\Controllers;

use App\Models\RegistrasiDriverSancaka;
use App\Models\Pengguna;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Intervention\Image\Laravel\Facades\Image; // Pastikan library ini sudah terinstall

class RegisterDriverOnlineController extends Controller
{
    public function create()
    {
        return view('public.register_driver');
    }

    public function store(Request $request)
    {
        Log::info("LOG: Pendaftaran driver baru masuk.");

        $minTahun = date('Y') - 8;

        $messages = [
            'tanggal_lahir.before' => 'Usia Anda harus minimal 18 tahun untuk mendaftar.',
            'tahun_kendaraan.min'  => "Tahun pembuatan kendaraan maksimal berusia 8 tahun (Minimal {$minTahun}).",
        ];

        $request->validate([
            'nama_lengkap'    => 'required|string|max:255',
            'tempat_lahir'    => 'required|string|max:100',
            'tanggal_lahir'   => 'required|date|before:-18 years',
            'nomor_nik'       => 'required|string|max:20',
            'nomor_kk'        => 'nullable|string|max:20',
            'nomor_wa'        => 'required|string|max:20',
            'alamat_lengkap'  => 'required|string',
            'jenis_layanan'   => 'required|in:motor,mobil',
            'merk_kendaraan'  => 'required|string|max:100',
            'tahun_kendaraan' => 'required|integer|min:' . $minTahun . '|max:' . date('Y'),
            'plat_nomor'      => 'required|string|max:15',
            'latitude'        => 'nullable|numeric',
            'longitude'       => 'nullable|numeric',
            
            // Dokumen Inti
            'foto_wajah'         => 'required|file|mimes:jpeg,png,jpg|max:5120',
            'file_ktp'           => 'required|file|mimes:jpeg,png,jpg,pdf|max:5120',
            'file_sim'           => 'required|file|mimes:jpeg,png,jpg,pdf|max:5120',
            'file_skck'          => 'required|file|mimes:jpeg,png,jpg,pdf|max:5120',
            'file_stnk'          => 'required|file|mimes:jpeg,png,jpg,pdf|max:5120',
            'foto_motor'         => 'required|file|mimes:jpeg,png,jpg,pdf|max:5120',
            'file_buku_rekening' => 'required|file|mimes:jpeg,png,jpg,pdf|max:5120',
            
            // Opsional
            'file_kk'         => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:5120',
            'file_bpkb'       => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:5120',
            'file_buku_nikah' => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:5120',
        ], $messages);

        try {
            $uploadPath = 'drivers';
            $filePaths = [];
            
            $fields = ['foto_wajah', 'file_ktp', 'file_sim', 'file_skck', 'file_stnk', 'foto_motor', 'file_buku_rekening', 'file_kk', 'file_bpkb', 'file_buku_nikah'];

            foreach ($fields as $field) {
                if ($request->hasFile($field)) {
                    $file = $request->file($field);
                    
                    // PROSES KEAMANAN FILE SEBELUM DISIMPAN
                    $pathAman = $this->amankanDanSimpanFile($file, $uploadPath);
                    
                    if (!$pathAman) {
                        return redirect()->back()->withInput()->with('error', "Pendaftaran Gagal: Sistem keamanan mendeteksi anomali/malware pada berkas Anda di kolom: {$field}.");
                    }
                    
                    $filePaths[$field] = $pathAman;
                } else {
                    $filePaths[$field] = null;
                }
            }

            RegistrasiDriverSancaka::create(array_merge(
                $request->only(['nama_lengkap', 'tempat_lahir', 'tanggal_lahir', 'nomor_nik', 'nomor_kk', 'nomor_wa', 'alamat_lengkap', 'jenis_layanan', 'merk_kendaraan', 'tahun_kendaraan', 'plat_nomor', 'latitude', 'longitude']),
                $filePaths,
                ['status' => 'pending', 'is_active_map' => 0]
            ));

            return redirect()->back()->with('success', 'Pendaftaran berhasil! Tim kami akan melakukan verifikasi berkas Anda maksimal 2x24 Jam.');
        } catch (\Exception $e) {
            Log::error("LOG: Error Register Driver - " . $e->getMessage());
            return redirect()->back()->with('error', 'Terjadi kesalahan sistem saat mengunggah berkas.');
        }
    }

    public function update(Request $request, $id)
    {
        $driver = RegistrasiDriverSancaka::findOrFail($id);
        $minTahun = date('Y') - 8;
        
        $request->validate([
            'nama_lengkap'    => 'required|string|max:255',
            'tempat_lahir'    => 'required|string|max:100',
            'tanggal_lahir'   => 'required|date|before:-18 years',
            'nomor_nik'       => 'required|string|max:20',
            'nomor_wa'        => 'required|string|max:20',
            'alamat_lengkap'  => 'required|string',
            'jenis_layanan'   => 'required|in:motor,mobil',
            'merk_kendaraan'  => 'required|string|max:100',
            'tahun_kendaraan' => 'required|integer|min:' . $minTahun . '|max:' . date('Y'),
            'plat_nomor'      => 'required|string|max:15',
            
            'foto_wajah' => 'nullable|file|mimes:jpeg,png,jpg|max:5120',
            'file_ktp'   => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:5120',
            'file_sim'   => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:5120',
            'file_skck'  => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:5120',
            'file_stnk'  => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:5120',
            'foto_motor' => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:5120',
            'file_buku_rekening' => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:5120',
        ]);

        try {
            $updateData = $request->only(['nama_lengkap', 'tempat_lahir', 'tanggal_lahir', 'nomor_nik', 'nomor_kk', 'nomor_wa', 'alamat_lengkap', 'jenis_layanan', 'merk_kendaraan', 'tahun_kendaraan', 'plat_nomor', 'latitude', 'longitude']);
            
            $fields = ['foto_wajah', 'file_ktp', 'file_sim', 'file_skck', 'file_stnk', 'foto_motor', 'file_buku_rekening', 'file_kk', 'file_bpkb', 'file_buku_nikah'];

            foreach ($fields as $field) {
                if ($request->hasFile($field)) {
                    $file = $request->file($field);
                    
                    // PROSES KEAMANAN FILE
                    $pathAman = $this->amankanDanSimpanFile($file, 'drivers');
                    
                    if (!$pathAman) {
                        return redirect()->back()->with('error', "Gagal memperbarui: File {$field} terindikasi berbahaya!");
                    }

                    // Hapus file lama jika aman dan berhasil dibuat
                    if (!empty($driver->$field) && Storage::disk('public')->exists($driver->$field)) {
                        Storage::disk('public')->delete($driver->$field);
                    }
                    
                    $updateData[$field] = $pathAman;
                }
            }

            $driver->update($updateData);
            return redirect()->back()->with('success', 'Data driver dan dokumen berhasil diperbarui!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Terjadi kesalahan sistem.');
        }
    }

    // =========================================================================
    // MESIN KEAMANAN FILE (INTERVENTION IMAGE + VIRUSTOTAL API)
    // =========================================================================
    private function amankanDanSimpanFile($file, $folder)
    {
        $ekstensi = strtolower($file->getClientOriginalExtension());
        $namaAcak = Str::uuid();

       // 1. JIKA GAMBAR -> Cuci dengan Intervention (Instant, No API Limit)
        if (in_array($ekstensi, ['jpg', 'jpeg', 'png'])) {
            try {
                $namaFileBaru = $folder . '/' . $namaAcak . '.jpg';
                
                // PERUBAHAN V4: 
                // read() -> decode()
                // toJpeg() -> encodeUsingFileExtension()
                $img = Image::decode($file->getRealPath())->scaleDown(width: 1200); 
                $encoded = $img->encodeUsingFileExtension('jpg', quality: 85);
                
                Storage::put('public/' . $namaFileBaru, (string) $encoded);
                return $namaFileBaru; // Return relative path
            } catch (\Exception $e) {
                Log::error('Intervention Image Error: ' . $e->getMessage());
                return false;
            }
        }

        // 2. JIKA PDF -> Scan lewat VirusTotal API
        if ($ekstensi === 'pdf') {
            $isSafe = $this->scanPdfVirusTotal($file);
            
            if ($isSafe) {
                $namaFileBaru = $namaAcak . '.pdf';
                return $file->storeAs($folder, $namaFileBaru, 'public');
            } else {
                return false;
            }
        }

        return false;
    }

    private function scanPdfVirusTotal($file)
    {
        $apiKey = env('VIRUSTOTAL_API_KEY');
        if (empty($apiKey)) {
            Log::warning('VirusTotal API Key belum diatur. File PDF lolos secara default.');
            return true; // Bypass jika API Key tidak ada agar sistem tidak macet
        }

        $fileHash = hash_file('sha256', $file->getRealPath());

        try {
            // TAHAP 1: Cek Hash ke VirusTotal (Pencarian Instan)
            $cekHash = Http::withHeaders(['x-apikey' => $apiKey])
                ->get("https://www.virustotal.com/api/v3/files/{$fileHash}");

            if ($cekHash->successful()) {
                $stats = $cekHash->json('data.attributes.last_analysis_stats');
                return ($stats['malicious'] == 0 && $stats['suspicious'] == 0);
            }

            // TAHAP 2: Jika File Baru, Upload ke VirusTotal
            $upload = Http::withHeaders(['x-apikey' => $apiKey])
                ->attach('file', file_get_contents($file->getRealPath()), 'berkas.pdf')
                ->post('https://www.virustotal.com/api/v3/files');

            if (!$upload->successful()) return false;

            $analysisId = $upload->json('data.id');

            // TAHAP 3: Polling Tunggu Hasil (Maks 4x 5 Detik = 20 Detik)
            for ($i = 0; $i < 4; $i++) {
                sleep(5);
                
                $analisis = Http::withHeaders(['x-apikey' => $apiKey])
                    ->get("https://www.virustotal.com/api/v3/analyses/{$analysisId}");

                if ($analisis->successful() && $analisis->json('data.attributes.status') === 'completed') {
                    $stats = $analisis->json('data.attributes.stats');
                    return ($stats['malicious'] == 0 && $stats['suspicious'] == 0);
                }
            }

            // Jika timeout (VT server sibuk), asumsikan gagal/error agar aman
            Log::warning('VirusTotal Timeout pada file pendaftaran.');
            return false;

        } catch (\Exception $e) {
            Log::error('VirusTotal Exception: ' . $e->getMessage());
            return false; // Tolak file jika API gagal terhubung
        }
    }
    // =========================================================================

    public function index(Request $request)
    {
        $query = RegistrasiDriverSancaka::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('nama_lengkap', 'like', "%{$search}%")
                  ->orWhere('nomor_wa', 'like', "%{$search}%")
                  ->orWhere('nomor_nik', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) $query->where('status', $request->status);

        if ($request->filled('date_range')) {
            $dates = explode(' to ', $request->date_range);
            if (count($dates) == 2) $query->whereBetween('created_at', [$dates[0] . ' 00:00:00', $dates[1] . ' 23:59:59']);
            elseif (count($dates) == 1) $query->whereDate('created_at', $dates[0]);
        }

        $totalDrivers = RegistrasiDriverSancaka::count();
        $pendingDrivers = RegistrasiDriverSancaka::where('status', 'pending')->count();
        $approvedDrivers = RegistrasiDriverSancaka::where('status', 'approved')->count();
        $rejectedDrivers = RegistrasiDriverSancaka::where('status', 'rejected')->count();
        $frozenDrivers = RegistrasiDriverSancaka::where('status', 'freeze')->count();

        $drivers = $query->orderBy('created_at', 'desc')->paginate(10);
        return view('admin.drivers.index', compact('drivers', 'totalDrivers', 'pendingDrivers', 'approvedDrivers', 'rejectedDrivers', 'frozenDrivers'));
    }

    public function updateStatus(Request $request, $id)
    {
        $request->validate(['status' => 'required|in:approved,rejected,freeze']);
        $status = $request->status;

        DB::beginTransaction();
        try {
            $driver = RegistrasiDriverSancaka::findOrFail($id);
            $updateData = ['status' => $status];

            if ($status === 'freeze') {
                $updateData['is_active_map'] = 0; 
            } elseif ($status === 'approved') {
                $updateData['is_active_map'] = 1; 
            }

            $driver->update($updateData);

            if ($status === 'approved' && $driver->id_pengguna) {
                Pengguna::where('id_pengguna', $driver->id_pengguna)->update(['role' => 'Driver']);
            } elseif ($status === 'freeze' && $driver->id_pengguna) {
                Pengguna::where('id_pengguna', $driver->id_pengguna)->update(['role' => 'Pelanggan']);
            }

            DB::commit();
            return redirect()->back()->with('success', "Status akun berhasil diubah menjadi {$status}.");
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("LOG: Error merubah status driver - " . $e->getMessage());
            return redirect()->back()->with('error', 'Gagal merubah status.');
        }
    }

    public function destroy($id)
    {
        $driver = RegistrasiDriverSancaka::findOrFail($id);
        $fields = ['foto_wajah', 'file_ktp', 'file_sim', 'file_skck', 'file_stnk', 'foto_motor', 'file_buku_rekening', 'file_kk', 'file_bpkb', 'file_buku_nikah'];
        foreach($fields as $field){
            if (!empty($driver->$field) && Storage::disk('public')->exists($driver->$field)) {
                Storage::disk('public')->delete($driver->$field);
            }
        }
        $driver->delete();
        return redirect()->back()->with('success', 'Data berhasil dihapus.');
    }

    public function bulkDestroy(Request $request)
    {
        $ids = $request->input('selected_ids');
        if (!$ids || empty($ids)) return redirect()->back()->with('error', 'Pilih minimal satu data.');
        
        $drivers = RegistrasiDriverSancaka::whereIn('id', $ids)->get();
        foreach ($drivers as $driver) {
            $this->destroy($driver->id); 
        }
        return redirect()->back()->with('success', count($ids) . ' data terpilih berhasil dihapus massal.');
    }
}