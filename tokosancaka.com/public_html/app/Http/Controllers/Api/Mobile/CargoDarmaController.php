<?php

namespace App\Http\Controllers\Api\Mobile;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class CargoDarmaController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * =========================================================
     * TRACKING CARGO / PELACAKAN RESI
     * =========================================================
     */
    public function tracking(Request $request)
    {
        Log::info("\n========== [CARGO TRACKING - START] ==========");

        // Validasi sesuai dokumen PDF Cargo Darmawisata
        $validator = Validator::make($request->all(), [
            'supplierName' => 'required|string',
            'sttNumber'    => 'required|string',
            'accessToken'  => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'FAILED', 'errors' => $validator->errors()], 422);
        }

        $user = $request->user();
        $userId = $user->id_pengguna ?? $user->id;

        $payload = [
            'supplierName' => $request->supplierName,
            'sttNumber'    => $request->sttNumber,
            'accessToken'  => $request->accessToken
        ];

        try {
            $response = $this->forwardRequest('Cargo/Tracking', $payload);
            $json = json_decode($response->getContent(), true);

            // Jika tracking berhasil, simpan ke riwayat pencarian
            if (isset($json['status']) && $json['status'] === 'SUCCESS') {
                $info = $json['infos'][0] ?? null;
                $destination = $info['destination'] ?? '-';
                
                // Simpan atau update riwayat pencarian (Upsert)
                DB::table('dw_cargo_histories')->updateOrInsert(
                    [
                        'user_id'       => $userId,
                        'stt_number'    => $request->sttNumber,
                        'supplier_name' => $request->supplierName,
                    ],
                    [
                        'destination'   => $destination,
                        'last_tracked'  => now(),
                        'created_at'    => now(),
                        'updated_at'    => now(),
                    ]
                );
            }

            return response()->json($json);

        } catch (\Exception $e) {
            Log::error("Cargo Tracking Error: " . $e->getMessage());
            return response()->json(['status' => 'FAILED', 'message' => 'System Error: ' . $e->getMessage()], 500);
        }
    }

    /**
     * =========================================================
     * GET HISTORY TRACKING LOKAL
     * =========================================================
     */
    public function history(Request $request)
    {
        $user = $request->user();
        $userId = $user->id_pengguna ?? $user->id;

        try {
            // Ambil riwayat pencarian user ini, urutkan dari yang terakhir dicari
            $history = DB::table('dw_cargo_histories')
                ->where('user_id', $userId)
                ->orderBy('last_tracked', 'desc')
                ->get();

            return response()->json([
                'status' => 'SUCCESS',
                'data'   => $history
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => 'FAILED', 'message' => 'Gagal mengambil riwayat'], 500);
        }
    }

    /**
     * =========================================================
     * HAPUS HISTORY (BULK DELETE KHUSUS ADMIN)
     * =========================================================
     */
    public function bulkDestroyHistory(Request $request)
    {
        $user = $request->user();
        $userId = $user->id_pengguna ?? $user->id;

        // Proteksi: Hanya Admin (User ID: 4) yang bisa hapus massal
        if ($userId != 4) {
            return response()->json(['status' => 'FAILED', 'message' => 'Akses ditolak.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'ids'   => 'required|array',
            'ids.*' => 'integer'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'FAILED', 'errors' => $validator->errors()], 422);
        }

        try {
            DB::table('dw_cargo_histories')->whereIn('id', $request->ids)->delete();
            return response()->json(['status' => 'SUCCESS', 'message' => 'Riwayat berhasil dihapus']);
        } catch (\Exception $e) {
            return response()->json(['status' => 'FAILED', 'message' => 'Gagal menghapus data di database.'], 500);
        }
    }
}