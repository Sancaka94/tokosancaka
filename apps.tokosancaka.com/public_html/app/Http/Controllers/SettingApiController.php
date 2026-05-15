<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SettingApi;
use Illuminate\Support\Facades\Log;

class SettingApiController extends Controller
{
    public function index()
    {
        // Ambil semua data setting jadikan array associative ['key' => 'value']
        $settings = SettingApi::pluck('value', 'key')->toArray();
        $danaMode = $settings['dana_production_mode'] ?? '0';

        return view('admin.settingapi.index', compact('danaMode', 'settings'));
    }

    public function updateDanaMode(Request $request)
    {
        Log::info("LOG LOG: Memulai update status Mode DANA.");
        try {
            $request->validate(['mode' => 'required|in:0,1']);
            SettingApi::updateOrCreate(
                ['key' => 'dana_production_mode'],
                ['value' => $request->mode]
            );

            $modeText = $request->mode == '1' ? 'PRODUCTION' : 'SANDBOX';
            Log::info("LOG LOG: Mode DANA berhasil diubah menjadi {$modeText}!");

            return response()->json([
                'success' => true,
                'message' => "Mode DANA berhasil diubah ke {$modeText}!"
            ]);
        } catch (\Exception $e) {
            Log::error("LOG LOG: Gagal update Mode DANA - " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Terjadi kesalahan sistem.'], 500);
        }
    }

    // [FUNGSI BARU] Menyimpan formulir kredensial ke database
    public function saveCredentials(Request $request)
    {
        Log::info("LOG LOG: Memulai penyimpanan kredensial DANA dari form UI.");

        $keys = [
            'dana_sandbox_merchant_id', 'dana_sandbox_client_id', 'dana_sandbox_client_secret', 'dana_sandbox_private_key',
            'dana_prod_merchant_id', 'dana_prod_client_id', 'dana_prod_client_secret', 'dana_prod_private_key'
        ];

        foreach ($keys as $key) {
            if ($request->has($key)) {
                SettingApi::updateOrCreate(
                    ['key' => $key],
                    ['value' => $request->input($key)]
                );
            }
        }

        Log::info("LOG LOG: Kredensial DANA berhasil disimpan ke database.");
        
        // Asumsi aplikasi kamu pakai session 'success' untuk alert/toastr
        return back()->with('success', 'Data kredensial API berhasil disimpan!');
    }
}