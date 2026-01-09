use App\Services\DokuSacService; // Pakai SAC Service
use App\Models\Transaction;

public function requestWithdrawal(Request $request)
{
    $user = Auth::user();
    $amount = (int) $request->input('amount');
    
    // 1. Validasi Saldo LOKAL
    if ($user->balance < $amount) {
        return back()->with('error', 'Saldo tidak mencukupi.');
    }
    
    // 2. Ambil data bank user (Anda harus punya form untuk ini)
    $beneficiary = [
        'bank_code' => $request->input('bank_code'), // Misal: 'BNINIDJA' (BCA)
        'bank_account_number' => $request->input('bank_account_number'),
        'bank_account_name' => $request->input('bank_account_name')
    ];
    
    $invoiceNumber = 'WD-' . time() . '-' . $user->id;

    // 3. Kurangi saldo LOKAL dan buat catatan
    DB::beginTransaction();
    try {
        $user->decrement('balance', $amount);
        Transaction::create([
            'user_id' => $user->id,
            'amount' => -$amount, // Negatif
            'type' => 'withdrawal',
            'status' => 'processing', // Sedang diproses
            'reference_id' => $invoiceNumber,
            'description' => 'Tarik dana ke ' . $beneficiary['bank_code']
        ]);
        DB::commit();
    } catch (\Exception $e) {
        DB::rollBack();
        return back()->with('error', 'Gagal memproses. Coba lagi.');
    }
    
    // 4. Panggil DOKU untuk kirim uang SUNGGUHAN
    $sacService = new DokuSacService();
    $payoutSuccess = $sacService->sendPayout(
        $user->doku_sac_id, // Ambil uang DARI dompet DOKU user ini
        $invoiceNumber,
        $amount,
        $beneficiary
    );
    
    // 5. Update status transaksi LOKAL
    $transaction = Transaction::where('reference_id', $invoiceNumber)->first();
    if ($payoutSuccess) {
        $transaction->status = 'success';
        $transaction->save();
        return back()->with('success', 'Penarikan berhasil diproses.');
    } else {
        // UANG GAGAL TERKIRIM! Kembalikan saldo user.
        DB::beginTransaction();
        $user->increment('balance', $amount);
        $transaction->status = 'failed';
        $transaction->save();
        DB::commit();
        return back()->with('error', 'Penarikan gagal diproses oleh bank.');
    }
}