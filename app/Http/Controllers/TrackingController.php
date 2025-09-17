<?php



namespace App\Http\Controllers;



use Illuminate\Http\Request;

use App\Models\Pesanan;

use App\Models\SpxScan;

use App\Models\Order;

use App\Models\ScannedPackage; // Pastikan model ini ada

use App\Models\ScanHistory;

use App\Services\KiriminAjaService;

class TrackingController extends Controller

{

    /**

     * Menampilkan halaman tracking utama.

     */

    public function showTrackingPage(Request $request)

    {

        if ($request->has('resi')) {

            return $this->trackPackage($request);

        }

        

        return view('public.tracking.index');

    }



    /**

     * Mencari dan menampilkan hasil pelacakan paket dari berbagai sumber.

     */

    public function trackPackage(Request $request)

    {

        $request->validate(['resi' => 'required|string|max:255']);

        $resi = $request->input('resi');

        $result = null;



        // Langkah 1: Cari di tabel pesanan

        $pesanan = Pesanan::where('resi', $resi)

                  ->orWhere('resi_aktual', $resi)

                   ->orWhere('nomor_invoice', $resi)

                  ->first();



$orderModel = null;



if (!$pesanan) {

    $orderModel = Order::where('shipping_reference', $resi)->orWhere('invoice_number', $resi)->first();

    

    if ($orderModel) {

       $pesanan = (object)[

            'resi' => $orderModel->shipping_reference,

            'resi_aktual' => $orderModel->shipping_reference,

        

            // Sender

            'sender_name' => $orderModel->store->name ?? 'N/A',

            'sender_address' => $orderModel->store->address_detail ?? 'N/A',

            'sender_province' => $orderModel->store->province ?? 'N/A',

            'sender_regency' => $orderModel->store->regency ?? 'N/A',

            'sender_district' => $orderModel->store->district ?? 'N/A',

            'sender_village' => $orderModel->store->village ?? 'N/A',

            'sender_postal_code' => $orderModel->store->postal_code ?? 'N/A',

            'sender_phone' => $orderModel->store->user->no_wa ?? 'N/A',

        

            // Receiver

            'receiver_name' => $orderModel->user->nama_lengkap ?? 'N/A',

            'receiver_address' => $orderModel->shipping_address ?? 'N/A',

            'receiver_province' => $orderModel->user->province ?? 'N/A',

            'receiver_regency' => $orderModel->user->regency ?? 'N/A',

            'receiver_district' => $orderModel->user->district ?? 'N/A',

            'receiver_village' => $orderModel->user->village ?? 'N/A',

            'receiver_postal_code' => $orderModel->user->postal_code ?? 'N/A',

              'receiver_phone' => $orderModel->user->no_wa ?? 'N/A',

        

            'status' => $orderModel->status ?? 'N/A',

            'jasa_ekspedisi_aktual' => $orderModel->courier ?? null,

            'service_type' => explode('-', $orderModel->service_type)[0] ?? null, // ambil kata pertama

            'created_at' => $orderModel->created_at,

        ];

    }

}



if ($pesanan) {

    $kiriminAja = new KiriminAjaService();



    $orderId = $pesanan->nomor_invoice ?? $pesanan->resi;



    $trackingData = null;

    $trackingData = $kiriminAja->track($pesanan->service_type, $orderId);



    // Mapping histories

    $histories = collect();

    if ($trackingData) {

        if ($pesanan->service_type === 'instant') {

            $result = $trackingData['result'] ?? [];

            $histories->push((object)[

                'status' => 'Pesanan dibuat',

                'lokasi' => $result['origin']['address'] ?? '-',

                'keterangan' => 'Pesanan telah diterima oleh driver',

                'created_at' => $result['date']['created_at'] ?? null,

            ]);

        } else {

            foreach ($trackingData['histories'] ?? [] as $h) {

                $histories->push((object)[

                    'status' => $h['status'] ?? null,

                    'lokasi' => $h['receiver'] ?? '-',

                    'keterangan' => $h['status'] ?? null,

                    'created_at' => $h['created_at'] ?? null,

                ]);

            }

        }

    }



   $result = [

        'is_pesanan' => true,

        'resi' => $pesanan->resi,

        'pengirim' => $pesanan->sender_name ?? 'N/A',

        'alamat_pengirim' => implode(', ', array_filter([

            $pesanan->sender_address ?? null,

            $pesanan->sender_village ?? null,

            $pesanan->sender_district ?? null,

            $pesanan->sender_regency ?? null,

            $pesanan->sender_province ?? null,

            $pesanan->sender_postal_code ?? null,

        ])) ?: 'N/A',

        'no_pengirim' => $pesanan->sender_phone,

        'penerima' => $pesanan->receiver_name ?? 'N/A',

        'alamat_penerima' => implode(', ', array_filter([

            $pesanan->receiver_address ?? null,

            $pesanan->receiver_village ?? null,

            $pesanan->receiver_district ?? null,

            $pesanan->receiver_regency ?? null,

            $pesanan->receiver_province ?? null,

            $pesanan->receiver_postal_code ?? null,

        ])) ?: 'N/A',

        'no_penerima' => $pesanan->receiver_phone,

        'status' => $pesanan->status,

        'tanggal_dibuat' => $pesanan->created_at,

        'histories' => $histories,

        'resi_aktual' => $pesanan->resi_aktual,

        'jasa_ekspedisi_aktual' => $pesanan->jasa_ekspedisi_aktual,

    ];



}







        // Langkah 2: Jika tidak ditemukan, cari di tabel spx_scans

        if (!$result) {

            $spxScan = SpxScan::with('kontak')->where('resi', $resi)->first();

            if ($spxScan) {

                $result = [

                    'is_pesanan' => false,

                    'resi' => $spxScan->resi,

                    'pengirim' => $spxScan->kontak->nama ?? 'N/A',

                    'alamat_pengirim' => $spxScan->kontak->alamat ?? 'N/A', // Tambahkan alamat pengirim

                    'penerima' => 'Agen SPX Express (Sancaka Express)',

                    'alamat_penerima' => 'Jl.Dr.Wahidin No.18 A RT.22 RW.05 Kel.Ketanggi',

                    'status' => $spxScan->status,

                    'tanggal_dibuat' => $spxScan->created_at,

                    'histories' => collect([

                        (object)[

                            'status' => $spxScan->status,

                            'lokasi' => 'SPX Ngawi',

                            'keterangan' => 'Paket telah di-scan oleh pengirim.',

                            'created_at' => $spxScan->created_at

                        ]

                    ]),

                ];

            }

        }

        

        // Langkah 3: Jika masih tidak ditemukan, cari di tabel scanned_packages

        if (!$result) {

            $scannedHistories = ScannedPackage::with(['user', 'kontak'])

                                                ->where('resi_number', $resi)

                                                ->orderBy('created_at', 'desc')

                                                ->get();

            

            if ($scannedHistories->isNotEmpty()) {

                $latestScan = $scannedHistories->first();

                $firstScan = $scannedHistories->last(); 



                $senderName = 'Mitra Sancaka Express';

                $senderAddress = 'N/A'; // Default alamat pengirim



                if ($firstScan->kontak) {

                    $senderName = $firstScan->kontak->nama;

                    $senderAddress = $firstScan->kontak->alamat; // Ambil alamat dari kontak

                } elseif ($firstScan->user) {

                    $senderName = $firstScan->user->name;

                    // Asumsi user punya relasi ke kontak atau kolom alamat

                    $senderAddress = $firstScan->user->address ?? 'Alamat tidak tersedia'; 

                }



                $result = [

                    'is_pesanan' => false,

                    'resi' => $latestScan->resi_number,

                    'pengirim' => $senderName,

                    'alamat_pengirim' => $senderAddress, // Tambahkan alamat pengirim

                    'penerima' => 'Agen Drop Point SPX Sancaka Express',

                    'alamat_penerima' => 'Jl.Dr.Wahidin No.18 A RT.22 RW.05 Kel.Ketanggi Kec.Ngawi Kab.Ngawi Jawa Timur 63211',

                    'status' => $latestScan->status,

                    'tanggal_dibuat' => $firstScan->created_at,

                    'histories' => $scannedHistories->map(function ($item) {

                        return (object)[

                            'status' => $item->status,

                            'lokasi' => 'Gudang Sancaka',

                            'keterangan' => 'Paket telah diproses di gudang.',

                            'created_at' => $item->created_at

                        ];

                    }),

                ];

            }

        }



        if ($result) {

            return view('public.tracking.index', compact('result'));

        }



        return redirect()->route('tracking.index')->with('error', "Nomor resi '{$resi}' tidak ditemukan. Pastikan nomor yang Anda masukkan sudah benar.");

    }

}

