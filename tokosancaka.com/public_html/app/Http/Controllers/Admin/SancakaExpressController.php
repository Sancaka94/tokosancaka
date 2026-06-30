<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Api;
use Illuminate\Support\Facades\Log;

class SancakaExpressController extends Controller
{
    /**
     * Menampilkan halaman pengaturan Sancaka Express.
     */
    public function index()
    {
        // Mengambil data dari database, berikan nilai default jika belum diatur
        $mapboxToken = Api::getValue('MAPBOX_TOKEN', 'global', '');
        $baseFare    = Api::getValue('SANCAKA_EXPRESS_BASE_FARE', 'global', 5000);
        $pricePerKm  = Api::getValue('SANCAKA_EXPRESS_PER_KM', 'global', 2000);
        $pricePerKg  = Api::getValue('SANCAKA_EXPRESS_PER_KG', 'global', 1500);

        return view('admin.sancaka_express.index', compact(
            'mapboxToken', 'baseFare', 'pricePerKm', 'pricePerKg'
        ));
    }

    /**
     * Memperbarui pengaturan Sancaka Express & Mapbox.
     */
    public function update(Request $request)
    {
        $request->validate([
            'mapbox_token' => 'required|string',
            'base_fare'    => 'required|numeric|min:0',
            'price_per_km' => 'required|numeric|min:0',
            'price_per_kg' => 'required|numeric|min:0',
        ], [
            'mapbox_token.required' => 'Token Mapbox wajib diisi agar sistem GPS dapat berjalan.',
            'base_fare.required'    => 'Tarif dasar pengiriman wajib diisi.',
            'price_per_km.required' => 'Tarif per KM wajib diisi.',
            'price_per_kg.required' => 'Tarif per KG wajib diisi.',
        ]);

        try {
            // Gunakan method Api::setValue() agar tersinkronisasi persis dengan ApiSettingsController
            Api::setValue('MAPBOX_TOKEN', $request->mapbox_token, 'mapbox', 'global');
            Api::setValue('SANCAKA_EXPRESS_BASE_FARE', $request->base_fare, 'mapbox', 'global');
            Api::setValue('SANCAKA_EXPRESS_PER_KM', $request->price_per_km, 'mapbox', 'global');
            Api::setValue('SANCAKA_EXPRESS_PER_KG', $request->price_per_kg, 'mapbox', 'global');

            return redirect()->back()->with('success', 'Pengaturan Tarif & Mapbox Sancaka Express berhasil diperbarui.');
        } catch (\Exception $e) {
            Log::error('Sancaka Express Settings Error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Terjadi kesalahan sistem saat menyimpan pengaturan.');
        }
    }
}
