<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Pesanan;
use App\Models\Kontak;
use App\Models\User;
use App\Services\KiriminAjaService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Exception;
use Carbon\Carbon;
use App\Exports\PesanansExport;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Services\FonnteService;


class PesananController extends Controller
{
    public function index(Request $request)
    {
        \App\Models\Pesanan::where('status', 'baru')
                         ->where('telah_dilihat', false)
                         ->update(['telah_dilihat' => true]);
                                         
        $query = Pesanan::query();
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function($q) use ($search) {
                $q->where('resi', 'like', "%{$search}%")
                  ->orWhere('sender_name', 'like', "%{$search}%")
                  ->orWhere('receiver_name', 'like', "%{$search}%")
                  ->orWhere('sender_phone', 'like', "%{$search}%")
                  ->orWhere('receiver_phone', 'like', "%{$search}%");
            });
        }
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }
        $orders = $query->latest()->paginate(10); 
        return view('admin.pesanan.index', compact('orders'));
    }

    public function create()
    {
        $customers = User::orderBy('nama_lengkap', 'asc')->get();
        return view('admin.pesanan.create', compact('customers'));
    }

    public function store(Request $request, KiriminAjaService $kirimaja)
    {
        DB::beginTransaction();
        try {
            $validatedData = $this->_validateOrderRequest($request);
            
            $this->_saveOrUpdateKontak($validatedData, 'sender', 'Pengirim');
            $this->_saveOrUpdateKontak($validatedData, 'receiver', 'Penerima');

            $validatedData['sender_phone'] = $this->_sanitizePhoneNumber($validatedData['sender_phone']);
            $validatedData['receiver_phone'] = $this->_sanitizePhoneNumber($validatedData['receiver_phone']);
            
            list($serviceGroup, $courier, $service, $shipping_cost, $ansuransi_fee, $cod_fee) = array_pad(explode('-', $validatedData['expedition']), 6, 0);
            
            $shipping_cost = (int) $shipping_cost;
            $ansuransi_fee = (int) $ansuransi_fee;
            $cod_fee = (int) $cod_fee;

            $total_paid = 0;
            $cod_value = 0;

            if ($validatedData['payment_method'] === 'CODBARANG') {
                $total_paid = (int)$validatedData['item_price'] + $shipping_cost + $cod_fee;
                if ($validatedData['ansuransi'] == 'iya') {
                    $total_paid += $ansuransi_fee;
                }
                $cod_value = $total_paid;
            } elseif ($validatedData['payment_method'] === 'COD') {
                $total_paid = $shipping_cost + $cod_fee;
                if ($validatedData['ansuransi'] == 'iya') {
                    $total_paid += $ansuransi_fee;
                }
                $cod_value = $total_paid;
            } else { // Termasuk Potong Saldo dan metode Tripay lainnya
                $total_paid = $shipping_cost;
                if ($validatedData['ansuransi'] == 'iya') {
                    $total_paid += $ansuransi_fee;
                }
            }
            
            $pesananData = $this->_preparePesananData($validatedData, $total_paid, $request->ip(), $request->userAgent());
            $pesanan = Pesanan::create($pesananData);
            
            if (in_array($validatedData['payment_method'], ['COD', 'CODBARANG', 'Potong Saldo'])) {
                $senderAddressData = $this->_getAddressData($request, 'sender');
                $receiverAddressData = $this->_getAddressData($request, 'receiver');
                
                $kiriminResponse = $this->_createKiriminAjaOrder($validatedData, $pesanan, $kirimaja, $senderAddressData, $receiverAddressData, $cod_value);
                
                if ($kiriminResponse['status'] !== true) {
                    throw new Exception($kiriminResponse['text'] ?? ($kiriminResponse['errors'][0]['text'] ?? 'Gagal membuat order di sistem ekspedisi.'));
                }
                
                $pesanan->status = 'Menunggu Pickup';
                $pesanan->status_pesanan = 'Menunggu Pickup';
                $pesanan->resi = $kiriminResponse['result']['awb_no'] ?? ($kiriminResponse['results'][0]['awb'] ?? null);
            } else { // Logika untuk pembayaran online via Tripay
                $tripay_amount = $total_paid;
                $orderItemsPayload = $this->_prepareOrderItemsPayload($shipping_cost, $ansuransi_fee, $validatedData['ansuransi']);
                $response = $this->_createTripayTransaction($validatedData, $pesanan, $tripay_amount, $orderItemsPayload);

                Log::channel('daily')->info('Tripay Response: ', $response ?? ['message' => 'No response from Tripay']);
                
                if (empty($response['success'])) {
                    $errorMessage = 'Gagal membuat transaksi pembayaran online. Pesan dari Server: ' . ($response['message'] ?? 'Tidak ada pesan.');
                    throw new Exception($errorMessage);
                }

                $pesanan->payment_url = $response['data']['checkout_url'];
            }
            
            $pesanan->price = $total_paid;
            $pesanan->save();
            DB::commit();

            // Kirim notifikasi WA setelah pesanan berhasil dibuat
            $waStatusMessage = $this->_sendWhatsappNotification($pesanan, $validatedData, $shipping_cost, $ansuransi_fee, $cod_fee, $total_paid);
            
            if (!empty($pesanan->payment_url)) return redirect()->away($pesanan->payment_url);
            
            return redirect()->route('admin.pesanan.index')->with('success', 'Pesanan baru dengan resi ' . $pesanan->resi . ' berhasil dibuat! ' . $waStatusMessage);
        } catch (ValidationException $e) {
            DB::rollBack();
            return redirect()->back()->withErrors($e->errors())->withInput();
        } catch (Exception $e) {
            DB::rollBack();
            Log::channel('daily')->error('Order Creation Failed: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return redirect()->back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage())->withInput();
        }
    }

    public function show($resi)
    {
        $order = Pesanan::where('resi', $resi)->firstOrFail();
        return view('admin.pesanan.show', compact('order'));
    }

    public function edit($resi)
    {
        $order = Pesanan::where('resi', $resi)->firstOrFail();
        return view('admin.pesanan.edit', compact('order'));
    }

    public function update(Request $request, $resi)
    {
        $validatedData = $request->validate([
            'sender_name' => 'required|string|max:255',
            'sender_phone' => 'required|string|max:20',
            'sender_address' => 'required|string',
            'receiver_name' => 'required|string|max:255',
            'receiver_phone' => 'required|string|max:20',
            'receiver_address' => 'required|string',
            'item_description' => 'required|string',
            'weight' => 'required|numeric',
            'length' => 'nullable|numeric',
            'width' => 'nullable|numeric',
            'height' => 'nullable|numeric',
            'payment_method' => 'required|string',
            'service_type' => 'required|string',
            'expedition' => 'required|string',
            'kelengkapan' => 'nullable|array',
        ]);
        $order = Pesanan::where('resi', $resi)->firstOrFail();
        $validatedData['kelengkapan'] = $request->has('kelengkapan') ? $request->input('kelengkapan') : null;
        $order->update($validatedData);
        return redirect()->route('admin.pesanan.index')->with('success', 'Pesanan ' . $resi . ' berhasil diperbarui.');
    }

    public function destroy($resi)
    {
        $order = Pesanan::where('resi', $resi)->firstOrFail();
        $order->delete();
        return redirect()->route('admin.pesanan.index')->with('success', 'Pesanan ' . $resi . ' berhasil dihapus.');
    }

    public function searchAddressApi(Request $request, KiriminAjaService $kirimaja)
    {
        $request->validate(['search' => 'required|string|min:3']);
        $searchQuery = $request->input('search');
        try {
            $results = $kirimaja->searchAddress($searchQuery);
            if (empty($results['status']) || empty($results['data'])) {
                return response()->json([]);
            }
            return response()->json($results['data']);
        } catch (Exception $e) {
            Log::channel('daily')->error('KiriminAja Address Search Failed: ' . $e->getMessage());
            return response()->json(['error' => 'Gagal mengambil data alamat dari ekspedisi.'], 500);
        }
    }
    
    public function searchKontak(Request $request)
    {
        Log::info('API Search Kontak dipanggil dengan:', $request->all());

        $request->validate([
            'search' => 'required|string|min:2',
            'tipe'   => 'nullable|in:Pengirim,Penerima',
        ]);

        $searchTerm = $request->input('search');
        $tipe = $request->input('tipe');

        $query = Kontak::query();

        $query->where(function ($q) use ($searchTerm) {
            $q->where(DB::raw('LOWER(nama)'), 'LIKE', '%' . strtolower($searchTerm) . '%')
              ->orWhere('no_hp', 'LIKE', "%{$searchTerm}%");
        });

        if ($tipe) {
            $query->where(function ($q) use ($tipe) {
                $q->where('tipe', $tipe)
                  ->orWhere('tipe', 'Keduanya');
            });
        }

        Log::info('SQL Query untuk Pencarian Kontak:', ['sql' => $query->toSql(), 'bindings' => $query->getBindings()]);
        $kontaks = $query->limit(10)->get();
        Log::info('Kontak yang ditemukan:', ['count' => $kontaks->count()]);

        return response()->json($kontaks);
    }
    
    public function cek_Ongkir(Request $request, KiriminAjaService $kirimaja)
    {
        try {
            $validated = $request->validate([
                'sender_district_id' => 'required|integer', 'sender_subdistrict_id' => 'required|integer',
                'receiver_district_id' => 'required|integer', 'receiver_subdistrict_id' => 'required|integer',
                'item_price' => 'required|numeric', 'weight' => 'required|numeric',
                'service_type' => 'required|string',
            ]);

            $senderData = $this->_getAddressData($request, 'sender');
            $receiverData = $this->_getAddressData($request, 'receiver');
            
            if (in_array($request->service_type, ['instant', 'sameday']) && (!$senderData['lat'] || !$receiverData['lat'])) {
                 return response()->json(['status' => false, 'message' => 'Koordinat alamat tidak ditemukan, tidak dapat menghitung ongkir instan/sameday.'], 422);
            }
    
            $itemValue = $request->item_price; 
            $options = [];
            
            $isMandatory = in_array((int) $request->item_type, [1, 3, 4, 8]) ? 1 : 0;
            
            if($isMandatory && $request->ansuransi == 'tidak') {
                return response()->json(['status' => false, 'message' => 'Wajib ada asuransi.'], 422);
            }

            if (in_array($request->service_type, ['instant', 'sameday'])) {
                $options = $kirimaja->getInstantPricing($senderData['lat'], $senderData['lng'], $request->sender_address, $receiverData['lat'], $receiverData['lng'], $request->receiver_address, $request->weight, $itemValue, 'motor');
            } else { 
                $category = $request->service_type === 'cargo' ? 'trucking' : 'regular';
                $options = $kirimaja->getExpressPricing($validated['sender_district_id'], $validated['sender_subdistrict_id'], $validated['receiver_district_id'], $validated['receiver_subdistrict_id'], $request->weight, $request->length ?? 1, $request->width ?? 1, $request->height ?? 1, $itemValue, null, $category, $request->ansuransi == 'iya' ? 1 : 0);
            }
            
            return response()->json($options);
        } catch (Exception $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function count()
    {
        $count = \App\Models\Pesanan::where('status', 'baru')
                                   ->where('telah_dilihat', false)
                                   ->count();
        return response()->json(['count' => $count]);
    }

    // --- HELPER METHODS ---

    /**
     * @param string $resi
     * @return \Illuminate\Http\Response
     */
    public function cetakResiThermal(string $resi)
    {
        $pesanan = Pesanan::where('resi', $resi)->firstOrFail();
        return view('admin.pesanan.cetak_thermal', compact('pesanan'));
    }

    private function _validateOrderRequest(Request $request)
{
    return $request->validate([
        'sender_name'       => 'required|string|max:255',
        'sender_phone'      => 'required|string|max:20',
        'sender_address'    => 'required|string',
        'nama_pembeli'      => 'required|string|max:255',
        'telepon_pembeli'   => 'required|string|max:20',
        'alamat_pengiriman' => 'required|string',
        'weight'            => 'required|numeric|min:1',
        'service_type'      => 'required|string',
        'expedition'        => 'required|string',
        'price'             => 'nullable|numeric|min:0',
    ]);
}


    /**
     * Mengirim notifikasi WhatsApp kepada pengirim dan penerima.
     * @param Pesanan $pesanan
     * @return string
     */
    private function _sendWhatsappNotification(Pesanan $pesanan, array $validatedData, int $shipping_cost, int $ansuransi_fee, int $cod_fee, int $total_paid): string
    {
        $messageTemplate = <<<TEXT
*Terimakasih Ya Kak Atas Orderannya 🙏*

Berikut adalah Nomor Order ID dan Invoice:
*{NOMOR_INVOICE}*

📦 Dari: *{SENDER_NAME}* ( {SENDER_PHONE} )
➡️ Ke: *{RECEIVER_NAME}* ( {RECEIVER_PHONE} )

----------------------------------------
*Rincian Biaya:*
- Ongkir: Rp {ONGKIR}
- Nilai Barang: Rp {NILAI_BARANG}
- Asuransi: Rp {ASURANSI}
- COD Fee: Rp {COD_FEE}
----------------------------------------
*Total Bayar: Rp {TOTAL_BAYAR}*

----------------------------------------
*Detail Paket:*
Deskripsi Barang: {DESKRIPSI}
Berat: {BERAT} Gram
Dimensi: {PANJANG} x {LEBAR} x {TINGGI} cm
Ekspedisi: {EKSPEDISI}
Layanan: {LAYANAN}
----------------------------------------

Semoga Paket Kakak
*{SENDER_NAME} ➡️ {RECEIVER_NAME}*
aman dan selamat sampai tujuan. ✅

Kak {NAMA_TUJUAN} bisa cek resi dengan klik link berikut:
https://tokosancaka.com/tracking/{NOMOR_INVOICE}

*Manajemen Sancaka*
TEXT;
        $message = str_replace(
            [
                '{NOMOR_INVOICE}', '{SENDER_NAME}', '{SENDER_PHONE}', '{RECEIVER_NAME}', '{RECEIVER_PHONE}',
                '{ONGKIR}', '{NILAI_BARANG}', '{ASURANSI}', '{COD_FEE}', '{TOTAL_BAYAR}',
                '{DESKRIPSI}', '{BERAT}', '{PANJANG}', '{LEBAR}', '{TINGGI}', '{EKSPEDISI}', '{LAYANAN}'
            ],
            [
                $pesanan->nomor_invoice,
                $validatedData['sender_name'],
                $validatedData['sender_phone'],
                $validatedData['receiver_name'],
                $validatedData['receiver_phone'],
                number_format($shipping_cost, 0, ',', '.'),
                number_format($validatedData['item_price'], 0, ',', '.'),
                number_format($ansuransi_fee, 0, ',', '.'),
                number_format($cod_fee, 0, ',', '.'),
                number_format($total_paid, 0, ',', '.'),
                $validatedData['item_description'],
                $validatedData['weight'],
                $validatedData['length'] ?? 1,
                $validatedData['width'] ?? 1,
                $validatedData['height'] ?? 1,
                $validatedData['expedition'],
                $validatedData['service_type'],
            ],
            $messageTemplate
        );

        $receiverMessage = str_replace('{NAMA_TUJUAN}', $validatedData['receiver_name'], $message);
        $receiverWa = preg_replace('/^0/', '62', $validatedData['receiver_phone']);
        $senderMessage = str_replace('{NAMA_TUJUAN}', $validatedData['sender_name'], $message);
        $senderWa = preg_replace('/^0/', '62', $validatedData['sender_phone']);

        $receiverStatus = FonnteService::sendMessage($receiverWa, $receiverMessage);
        $senderStatus = FonnteService::sendMessage($senderWa, $senderMessage);

        $success = true;
        $message = '';
        if (!$receiverStatus) {
            $success = false;
            $message .= 'Gagal mengirim notifikasi WA ke penerima. ';
        }
        if (!$senderStatus) {
            $success = false;
            $message .= 'Gagal mengirim notifikasi WA ke pengirim.';
        }
        
        return $success ? 'Notifikasi WA berhasil dikirim.' : $message;
    }
}
