<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductReview;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http; // Untuk HTTP Client ke Fonte

class AdminReviewController extends Controller
{
    // 1. TAMPILKAN SEMUA ULASAN
    public function index()
    {
        $reviews = ProductReview::with(['user', 'product.store'])
            ->latest()
            ->paginate(10);

        return view('admin.reviews.index', compact('reviews'));
    }

    // 2. FORM EDIT (MODERASI KATA-KATA)
    public function edit($id)
    {
        $review = ProductReview::findOrFail($id);
        return view('admin.reviews.edit', compact('review'));
    }

    // 3. UPDATE ULASAN (SIMPAN EDITAN)
    public function update(Request $request, $id)
    {
        $request->validate([
            'rating' => 'required|numeric|min:1|max:5',
            'comment' => 'required|string'
        ]);

        $review = ProductReview::findOrFail($id);
        $review->update([
            'rating' => $request->rating,
            'comment' => $request->comment
        ]);

        return redirect()->route('admin.reviews.index')->with('success', 'Ulasan berhasil diperbarui.');
    }

    // 4. LIHAT DETAIL & FORM BALAS
    public function show($id)
    {
        $review = ProductReview::with(['user', 'product.store.user'])->findOrFail($id);
        return view('admin.reviews.show', compact('review'));
    }

    // 5. PROSES BALAS ULASAN & KIRIM WA (FONTE)
    public function reply(Request $request, $id)
    {
        $request->validate(['reply' => 'required|string']);

        $review = ProductReview::with(['user', 'product.store.user'])->findOrFail($id);

        // Simpan Balasan ke Database
        $review->update([
            'reply' => $request->reply,
            'reply_at' => now(),
        ]);

        // --- KIRIM WA VIA FONTE ---
        $this->sendFonteNotification($review, $request->reply);

        return redirect()->route('admin.reviews.index')->with('success', 'Balasan terkirim dan notifikasi WA sedang diproses.');
    }

    // 6. HAPUS ULASAN
    public function destroy($id)
    {
        $review = ProductReview::findOrFail($id);
        $review->delete();
        return redirect()->route('admin.reviews.index')->with('success', 'Ulasan berhasil dihapus.');
    }

    // --- PRIVATE HELPER: FONTE API ---
    private function sendFonteNotification($review, $adminReply)
    {
        // Token Fonte Anda (Sebaiknya taruh di .env)
        $token = 'tw2v5GDT1u4pZ4iBHUbD'; 

        // Ambil Nomor HP Pembeli
        $buyerPhone = $this->formatPhone($review->user->no_wa ?? null);
        
        // Ambil Nomor HP Seller
        $sellerPhone = $this->formatPhone($review->product->store->user->no_wa ?? null);

        // Pesan untuk Pembeli
        if ($buyerPhone) {
            $msgBuyer = "Halo {$review->user->name},\n\nUlasan Anda pada produk *{$review->product->name}* telah dibalas oleh Admin:\n\n_\"$adminReply\"_\n\nTerima kasih telah berbelanja di Toko Sancaka.";
            $this->curlFonte($token, $buyerPhone, $msgBuyer);
        }

        // Pesan untuk Seller
        if ($sellerPhone) {
            $msgSeller = "Halo Seller,\n\nUlasan baru pada produk *{$review->product->name}* telah ditanggapi oleh Admin.\n\nUlasan Pembeli: \"{$review->comment}\"\nBalasan Admin: \"$adminReply\"";
            $this->curlFonte($token, $sellerPhone, $msgSeller);
        }
    }

    private function curlFonte($token, $target, $message)
    {
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://api.fonte.com/send',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => array(
                'target' => $target,
                'message' => $message,
                'url' => 'https://md.fonte.com/images/logo_hitam.png', // Opsional
            ),
            CURLOPT_HTTPHEADER => array(
                "Authorization: $token"
            ),
        ));
        $response = curl_exec($curl);
        curl_close($curl);
    }

    private function formatPhone($noHP)
    {
        if (!$noHP) return null;
        $noHP = preg_replace('/[^0-9]/', '', $noHP);
        if (substr($noHP, 0, 1) === '0') return '62' . substr($noHP, 1);
        if (substr($noHP, 0, 2) !== '62') return '62' . $noHP;
        return $noHP;
    }
}