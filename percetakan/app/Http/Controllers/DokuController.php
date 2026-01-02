<?php

namespace App\Http\Controllers;

// --- Import Class Penting dari Laravel & PHP ---
use Doku\Snap\Snap; // Class utama DOKU (dari Service Provider)
use Illuminate\Http\Request; // Class Request Laravel
use Exception; // Untuk try-catch

// --- Import SEMUA DTO (Data Transfer Object) dari SDK DOKU ---
// (Anda mungkin tidak butuh semua ini, tapi ini adalah daftar lengkap dari contoh)

// Untuk TotalAmount (digunakan di banyak tempat)
use Doku\Snap\Models\TotalAmount\TotalAmount;

// Untuk Virtual Account
use Doku\Snap\Models\VA\Request\CreateVaRequestDto;
use Doku\Snap\Models\VA\AdditionalInfo\CreateVaRequestAdditionalInfo;
use Doku\Snap\Models\VA\VirtualAccountConfig\CreateVaVirtualAccountConfig;
use Doku\Snap\Models\VA\Request\UpdateVaRequestDto;
use Doku\Snap\Models\VA\AdditionalInfo\UpdateVaRequestAdditionalInfo;
use Doku\Snap\Models\VA\VirtualAccountConfig\UpdateVaVirtualAccountConfig;
use Doku\Snap\Models\VA\Request\DeleteVaRequestDto;
use Doku\Snap\Models\VA\AdditionalInfo\DeleteVaRequestAdditionalInfo;
use Doku\Snap\Models\VA\Request\CheckStatusVaRequestDto;

// Untuk Account Binding & Unbinding
use Doku\Snap\Models\AccountBinding\AccountBindingRequestDto;
use Doku\Snap\Models\AccountBinding\AccountBindingAdditionalInfoRequestDto;
use Doku\Snap\Models\AccountUnbinding\AccountUnbindingRequestDto;
use Doku\Snap\Models\AccountUnbinding\AccountUnbindingAdditionalInfoRequestDto;

// Untuk Card Registration
use Doku\Snap\Models\CardRegistration\CardRegistrationRequestDto;
use Doku\Snap\Models\CardRegistration\CardRegistrationAdditionalInfoRequestDto;
use Doku\Snap\Models\CardRegistration\CardRegistrationCardDataRequestDto;

// Untuk Payment (Direct Debit, E-Wallet)
use Doku\Snap\Models\Payment\PaymentRequestDto;
use Doku\Snap\Models\Payment\PaymentAdditionalInfoRequestDto;

// Untuk Payment Jump App (DANA, ShopeePay)
use Doku\Snap\Models\PaymentJumpApp\PaymentJumpAppRequestDto;
use Doku\Snap\Models\PaymentJumpApp\PaymentJumpAppAdditionalInfoRequestDto;
use Doku\Snap\Models\PaymentJumpApp\UrlParamDto;

// Untuk Operasi Lainnya
use Doku\Snap\Models\CheckStatus\DirectDebitCheckStatusRequestDto;
use Doku\Snap\Models\CheckStatus\CheckStatusAdditionalInfoRequestDto;
use Doku\Snap\Models\Refund\RefundRequestDto;
use Doku\Snap\Models\Refund\RefundAdditionalInfoRequestDto;
use Doku\Snap\Models\BalanceInquiry\BalanceInquiryRequestDto;
use Doku\Snap\Models\BalanceInquiry\BalanceInquiryAdditionalInfoRequestDto;

// Untuk DIPC (Inbound Webhook)
use Doku\Snap\Models\DirectInquiry\InquiryResponseBodyDto;
use Doku\Snap\Models\DirectInquiry\InquiryResponseVirtualAccountDataDto;
use Doku\Snap\Models\DirectInquiry\InquiryReasonDto;
use Doku\Snap\Models\DirectInquiry\InquiryResponseAdditionalInfoDto;
// (Kita juga butuh CreateVaVirtualAccountConfig untuk DIPC, tapi sudah di-import di atas)


/**
 * DokuController
 * Mengelola semua interaksi dengan DOKU SNAP API
 */
class DokuController extends Controller
{

    /**
     * Helper function untuk mengembalikan response JSON
     * Menerima response dari SDK, mengubah ke array, dan set status code
     */
    private function jsonResponse($response, $successCode = 200)
    {
        if (is_array($response) || is_object($response)) {
            $responseObject = (array)$response;
            
            // Default status code
            $statusCode = $successCode; 

            // Jika ada responseCode, gunakan 3 digit pertama sebagai HTTP status code
            if (isset($responseObject['responseCode'])) {
                $statusCode = (int)substr($responseObject['responseCode'], 0, 3);
            }
            return response()->json($responseObject, $statusCode);
        } else {
            // Fallback jika response bukan array/object
            return response()->json(['message' => 'Unexpected response type from SDK'], 500);
        }
    }

    /**
     * Helper function untuk mengembalikan error
     */
    private function jsonError(Exception $e, $message = 'Terjadi kesalahan')
    {
        // Untuk production, Anda mungkin ingin me-log $e->getMessage()
        // tapi hanya tampilkan pesan generik ke user.
        return response()->json([
            'error' => $message,
            'sdk_message' => $e->getMessage()
        ], 500); // 500 Internal Server Error
    }

    // =================================================================
    // == VIRTUAL ACCOUNT (VA)
    // =================================================================

    public function createVirtualAccount(Request $request, Snap $snap)
    {
        try {
            $totalAmount = new TotalAmount(
                $request->input('totalAmount.value'),
                $request->input('totalAmount.currency', 'IDR')
            );

            $vaConfig = new CreateVaVirtualAccountConfig(
                $request->input('additionalInfo.virtualAccountConfig.reusableStatus', true)
            );

            $additionalInfo = new CreateVaRequestAdditionalInfo(
                $request->input('additionalInfo.channel'),
                $vaConfig
            );

            $createVaRequestDto = new CreateVaRequestDto(
                $request->input('partnerServiceId'),
                $request->input('customerNo'),
                $request->input('virtualAccountNo'),
                $request->input('virtualAccountName'),
                $request->input('virtualAccountEmail'),
                $request->input('virtualAccountPhone'),
                $request->input('trxId'),
                $totalAmount,
                $additionalInfo,
                $request->input('virtualAccountTrxType', 'C'),
                $request->input('expiredDate')
            );

            $result = $snap->createVa($createVaRequestDto);
            return $this->jsonResponse($result, 201); // 201 Created

        } catch (Exception $e) {
            return $this->jsonError($e, 'Gagal membuat Virtual Account');
        }
    }

    public function updateVirtualAccount(Request $request, Snap $snap)
    {
        try {
            $totalAmount = new TotalAmount(
                $request->input('totalAmount.value'),
                $request->input('totalAmount.currency', 'IDR')
            );

            $vaConfig = new UpdateVaVirtualAccountConfig(
                "ACTIVE", // status (contoh dari doc)
                $request->input('additionalInfo.virtualAccountConfig.minAmount'),
                $request->input('additionalInfo.virtualAccountConfig.maxAmount')
            );

            $additionalInfo = new UpdateVaRequestAdditionalInfo(
                $request->input('additionalInfo.channel'),
                $vaConfig
            );

            $updateVaRequestDto = new UpdateVaRequestDto(
                $request->input('partnerServiceId'),
                $request->input('customerNo'),
                $request->input('virtualAccountNo'),
                $request->input('virtualAccountName'),
                $request->input('virtualAccountEmail'),
                $request->input('virtualAccountPhone'),
                $request->input('trxId'),
                $totalAmount,
                $additionalInfo,
                $request->input('virtualAccountTrxType', 'O'), // 'O' untuk Open
                $request->input('expiredDate')
            );

            $result = $snap->updateVa($updateVaRequestDto);
            return $this->jsonResponse($result);

        } catch (Exception $e) {
            return $this->jsonError($e, 'Gagal mengupdate Virtual Account');
        }
    }

    public function deleteVirtualAccount(Request $request, Snap $snap)
    {
        try {
            $additionalInfo = new DeleteVaRequestAdditionalInfo(
                $request->input('additionalInfo.channel')
            );

            $deleteVaRequestDto = new DeleteVaRequestDto(
                $request->input('partnerServiceId'),
                $request->input('customerNo'),
                $request->input('virtualAccountNo'),
                $request->input('trxId'),
                $additionalInfo
            );

            $result = $snap->deletePaymentCode($deleteVaRequestDto);
            return $this->jsonResponse($result);

        } catch (Exception $e) {
            return $this->jsonError($e, 'Gagal menghapus Virtual Account');
        }
    }

    public function checkStatusVirtualAccount(Request $request, Snap $snap)
    {
        try {
            $checkStatusVaRequestDto = new CheckStatusVaRequestDto(
                $request->input('partnerServiceId'),
                $request->input('customerNo'),
                $request->input('virtualAccountNo'),
                $request->input('inquiryRequestId'),
                $request->input('paymentRequestId'),
                null // additionalInfo (tidak ada di contoh doc)
            );

            // Fungsi di doc salah ketik: $snap-> ($checkStatusVaRequestDto)
            // Seharusnya:
            $result = $snap->checkStatusVa($checkStatusVaRequestDto);
            return $this->jsonResponse($result);

        } catch (Exception $e) {
            return $this->jsonError($e, 'Gagal mengecek status Virtual Account');
        }
    }


    // =================================================================
    // == ACCOUNT BINDING
    // =================================================================

    public function accountBinding(Request $request, Snap $snap)
    {
        try {
            $additionalInfo = new AccountBindingAdditionalInfoRequestDto(
                $request->input('additionalInfo.channel'),
                $request->input('additionalInfo.custIdMerchant'),
                $request->input('additionalInfo.customerName'),
                $request->input('additionalInfo.email'),
                $request->input('additionalInfo.idCard'),
                $request->input('additionalInfo.country'),
                $request->input('additionalInfo.address'),
                $request->input('additionalInfo.dateOfBirth'),
                $request->input('additionalInfo.successRegistrationUrl'),
                $request->input('additionalInfo.failedRegistrationUrl'),
                $request->input('additionalInfo.deviceModel'),
                $request->input('additionalInfo.osType'),
                $request->input('additionalInfo.channelId')
            );

            $requestBody = new AccountBindingRequestDto(
                $request->input('phoneNo'),
                $additionalInfo
            );
            
            $ipAddress = $request->ip();
            $deviceId = $request->header('X-DEVICE-ID');

            $response = $snap->doAccountBinding($requestBody, $ipAddress, $deviceId);
            return $this->jsonResponse($response);

        } catch (Exception $e) {
            return $this->jsonError($e, 'Gagal melakukan Account Binding');
        }
    }

    public function accountUnbinding(Request $request, Snap $snap)
    {
        try {
            $additionalInfo = new AccountUnbindingAdditionalInfoRequestDto(
                $request->input('additionalInfo.channel')
            );

            $requestBody = new AccountUnbindingRequestDto(
                $request->input('tokenId'),
                $additionalInfo
            );
            
            $ipAddress = $request->ip();

            $response = $snap->doAccountUnbinding($requestBody, $ipAddress);
            return $this->jsonResponse($response);

        } catch (Exception $e) {
            return $this->jsonError($e, 'Gagal melakukan Account Unbinding');
        }
    }

    // =================================================================
    // == CARD REGISTRATION
    // =================================================================

    public function cardRegistration(Request $request, Snap $snap)
    {
        try {
            $cardData = new CardRegistrationCardDataRequestDto(
                $request->input('cardData.bankCardNo'),
                $request->input('cardData.bankCardType'),
                $request->input('cardData.expiryDate'),
                $request->input('cardData.identificationNo'),
                $request->input('cardData.identificationType'),
                $request->input('cardData.email')
            );

            $additionalInfo = new CardRegistrationAdditionalInfoRequestDto(
                $request->input('additionalInfo.channel'),
                $request->input('additionalInfo.customerName'),
                $request->input('additionalInfo.email'),
                $request->input('additionalInfo.idCard'),
                $request->input('additionalInfo.country'),
                $request->input('additionalInfo.address'),
                $request->input('additionalInfo.dateOfBirth'),
                $request->input('additionalInfo.successRegistrationUrl'),
                $request->input('additionalInfo.failedRegistrationUrl')
            );

            $requestBody = new CardRegistrationRequestDto(
                $cardData,
                $request->input('custIdMerchant'),
                $request->input('phoneNo'),
                $additionalInfo
            );
            
            $response = $snap->doCardRegistration($requestBody);
            return $this->jsonResponse($response);

        } catch (Exception $e) {
            return $this->jsonError($e, 'Gagal melakukan Card Registration');
        }
    }

    public function cardUnbinding(Request $request, Snap $snap)
    {
        // Dokumen menggunakan fungsi doCardUnbinding tapi
        // DTO-nya sama dengan AccountUnbinding
        try {
            $additionalInfo = new AccountUnbindingAdditionalInfoRequestDto(
                $request->input('additionalInfo.channel')
            );

            $requestBody = new AccountUnbindingRequestDto(
                $request->input('tokenId'),
                $additionalInfo
            );
            
            $ipAddress = $request->ip();

            $response = $snap->doCardUnbinding($requestBody, $ipAddress);
            return $this->jsonResponse($response);

        } catch (Exception $e) {
            return $this->jsonError($e, 'Gagal melakukan Card Unbinding');
        }
    }

    // =================================================================
    // == PAYMENTS
    // =================================================================

    public function paymentDirectDebit(Request $request, Snap $snap)
    {
        try {
            $amount = new TotalAmount(
                $request->input('amount.value'),
                $request->input('amount.currency')
            );

            $additionalInfo = new PaymentAdditionalInfoRequestDto(
                $request->input('additionalInfo.channel'),
                $request->input('additionalInfo.remarks'),
                $request->input('additionalInfo.successPaymentUrl'),
                $request->input('additionalInfo.failedPaymentUrl'),
                $request->input('additionalInfo.lineItems'), // Ini harus array
                $request->input('additionalInfo.paymentType')
            );

            $requestDto = new PaymentRequestDto(
                $request->input('partnerReferenceNo'),
                $amount,
                $request->input('payOptionDetails'), // Ini object/array
                $additionalInfo,
                $request->input('feeType'),
                $request->input('chargeToken')
            );

            $ipAddress = $request->ip();
            $authCode = $request->input('authCode'); // Penting!
       
            $response = $snap->doPayment($requestDto, $authCode, $ipAddress);
            return $this->jsonResponse($response);

        } catch (Exception $e) {
            return $this->jsonError($e, 'Gagal melakukan Pembayaran Direct Debit');
        }
    }

    public function paymentJumpApp(Request $request, Snap $snap)
    {
        try {
            // Konversi array 'urlParam' dari request
            $urlParamData = $request->input('urlParam', []);
            $urlParam = array_map(function ($item) {
                return new UrlParamDto(
                    $item['url'] ?? null,
                    $item['type'] ?? null,
                    $item['isDeepLink'] ?? null
                );
            }, $urlParamData);

            $amount = new TotalAmount(
                $request->input('amount.value'),
                $request->input('amount.currency')
            );

            $additionalInfo = new PaymentJumpAppAdditionalInfoRequestDto(
                $request->input('additionalInfo.channel'),
                $request->input('additionalInfo.orderTitle'),
                $request->input('additionalInfo.metadata'),
                $request->input('additionalInfo.supportDeepLinkCheckoutUrl'),
                $request->input('additionalInfo.origin')
            );

            $requestBody = new PaymentJumpAppRequestDto(
                $request->input('partnerReferenceNo'),
                $request->input('validUpTo'),
                $request->input('pointOfInitiation'),
                $urlParam, // Array DTO yang sudah dibuat
                $amount,
                $additionalInfo
            );

            $ipAddress = $request->ip();
            $deviceId = $request->header('X-DEVICE-ID');

            $response = $snap->doPaymentJumpApp($requestBody, $deviceId, $ipAddress);
            return $this->jsonResponse($response);

        } catch (Exception $e) {
            return $this->jsonError($e, 'Gagal melakukan Pembayaran Jump App');
        }
    }

    // =================================================================
    // == OTHER OPERATIONS
    // =================================================================

    public function checkTransactionStatus(Request $request, Snap $snap)
    {
        try {
            $amount = new TotalAmount(
                $request->input('amount.value'),
                $request->input('amount.currency')
            );

            $additionalInfo = new CheckStatusAdditionalInfoRequestDto(
                $request->input('additionalInfo.deviceId'),
                $request->input('additionalInfo.channel')
            );

            $requestBody = new DirectDebitCheckStatusRequestDto(
                $request->input('originalPartnerReferenceNo'),
                $request->input('originalReferenceNo'),
                $request->input('originalExternalId'),
                $request->input('serviceCode'),
                $request->input('transactionDate'),
                $amount,
                $request->input('merchantId'),
                $request->input('subMerchantId'),
                $request->input('externalStoreId'),
                $additionalInfo
            );

            $response = $snap->doCheckStatus($requestBody);
            return $this->jsonResponse($response);

        } catch (Exception $e) {
            return $this->jsonError($e, 'Gagal mengecek status transaksi');
        }
    }

    public function refundTransaction(Request $request, Snap $snap)
    {
        try {
            $additionalInfo = new RefundAdditionalInfoRequestDto(
                $request->input('additionalInfo.channel')
            );

            $refundAmount = new TotalAmount(
                $request->input('refundAmount.value'),
                $request->input('refundAmount.currency')
            );

            $requestBody = new RefundRequestDto(
                $additionalInfo,
                $request->input('originalPartnerReferenceNo'),
                $request->input('originalExternalId'),
                $refundAmount,
                $request->input('reason'),
                $request->input('partnerRefundNo')
            );
            
            $ipAddress = $request->ip();
            $authCode = $request->input('authCode');
            $deviceId = $request->header('deviceId'); // Sesuai dokumen

            $response = $snap->doRefund($requestBody, $authCode, $ipAddress, $deviceId);
            return $this->jsonResponse($response);

        } catch (Exception $e) {
            return $this->jsonError($e, 'Gagal melakukan refund');
        }
    }

    public function balanceInquiry(Request $request, Snap $snap)
    {
        try {
            $additionalInfo = new BalanceInquiryAdditionalInfoRequestDto(
                $request->input('additionalInfo.channel')
            );

            $requestBody = new BalanceInquiryRequestDto(
                $additionalInfo
            );

            $ipAddress = $request->ip();
            $authCode = $request->input('authCode');

            $response = $snap->doBalanceInquiry($requestBody, $authCode, $ipAddress);
            return $this->jsonResponse($response);

        } catch (Exception $e) {
            return $this->jsonError($e, 'Gagal melakukan cek saldo');
        }
    }


    // =================================================================
    // == INBOUND WEBHOOKS (Panggilan dari DOKU ke Anda)
    // =================================================================

    /**
     * Menangani DIPC (Direct Inquiry Payment Code).
     * DOKU akan memanggil endpoint ini.
     */
    public function handleDirectInquiry(Request $request, Snap $snap)
    {
        // 1. Ambil data mentah (raw) dan header
        $requestBody = $request->all();
        $authorization = $request->header('Authorization');
        
        // 2. Validasi Token/Signature B2B dari DOKU
        // !! PENTING: Anda mungkin perlu parameter lain untuk validasi
        //    tergantung implementasi SDK `validateTokenB2B`.
        //    Contoh dokumen hanya $authorization.
        $isValid = $snap->validateTokenB2B($authorization);

        if (!$isValid) {
            // Jika tidak valid, kirim response error
            // (Kode response ini perlu dikonfirmasi dari DOKU)
            return response()->json([
                'responseCode' => 4010000,
                'responseMessage' => 'Unauthorized'
            ], 401);
        }

        // 3. Jika Valid, proses request
        //    Ambil data dari request DOKU
        $inquiryRequestId = $requestBody['inquiryRequestId'];
        $partnerServiceId = $requestBody['partnerServiceId'];
        $customerNo = $requestBody['customerNo'];
        $virtualAccountNo = $requestBody['virtualAccountNo'];

        // 4. Lakukan validasi di database Anda
        //    (Contoh: Cek apakah VA $virtualAccountNo ada dan aktif)
        //    $myVaData = YourVaModel::where('va_number', $virtualAccountNo)->first();
        //    if (!$myVaData) {
        //        // Kirim response "Not Found"
        //    }

        // 5. Buat DTO Response untuk DOKU (Contoh dari dokumen)
        $virtualAccountName = "Nama Pelanggan " . time(); // Ambil dari DB Anda
        $trxId = "INV_MERCHANT_" . time(); // Buat/Ambil dari DB Anda
        
        $totalAmount = new TotalAmount("25000.00", "IDR"); // Ambil dari DB Anda
        
        $vaConfig = new CreateVaVirtualAccountConfig(
            true, "100000.00", "10000.00"
        );
        $additionalInfo = new InquiryResponseAdditionalInfoDto(
            $requestBody['additionalInfo']['channel'],
            $trxId,
            $vaConfig
        );
        $inquiryReason = new InquiryReasonDto("Success", "Sukses");

        $vaData = new InquiryResponseVirtualAccountDataDto(
            $partnerServiceId,
            $customerNo,
            $virtualAccountNo,
            $virtualAccountName,
            "email." . time() . "@gmail.com", // Ambil dari DB
            time(), // Ambil dari DB
            $totalAmount,
            "C", // Tipe VA (C=Closed, O=Open)
            $additionalInfo,
            "00", // Inquiry Status
            $inquiryReason,
            $inquiryRequestId,
            null // freeText
        );

        $body = new InquiryResponseBodyDto(
            2002400, // Kode sukses
            'Successful',
            $vaData
        );
        
        // 6. Kirim response sukses ke DOKU
        return response()->json($body, 200);
    }
    
    /**
     * Menangani Notifikasi Pembayaran.
     * DOKU akan memanggil endpoint ini setelah pembayaran berhasil/gagal.
     */
// Di dalam app/Http/Controllers/DokuController.php

public function handleNotification(Request $request, Snap $snap)
{
    // 1. Ambil data
    $body = $request->all();
    $signature = $request->header('Signature'); // atau header yang sesuai
    
    // 2. TODO: Validasi Signature dari DOKU
    // $isValid = $snap->validateNotificationSignature($body, $signature);
    // if (!$isValid) {
    //     return response()->json(['status' => 'Signature invalid'], 401);
    // }

    // 3. Ambil data penting dari DOKU
    $transaction = $body['transaction'];
    $orderData = $body['order'];
    
    $invoiceNumber = $orderData['invoiceNumber']; // Ini adalah 'trxId' Anda
    $transactionStatus = $transaction['status']; // 'SUCCESS', 'FAILED', dll.

    // 4. Cari Order di Database Anda
    $order = Order::where('invoice_number', $invoiceNumber)->first();

    if (!$order) {
        // Order tidak ditemukan! Ini masalah.
        return response()->json(['status' => 'Order not found'], 404);
    }
    
    // 5. Cek apakah status masih PENDING
    //    (Untuk menghindari proses ganda)
    if ($order->status == 'PENDING') {
        
        // 6. Update Status Order Anda
        if ($transactionStatus == 'SUCCESS') {
            $order->update([
                'status' => 'PAID',
                'paid_at' => now()
            ]);
            
            // TODO: Kirim email ke pelanggan, aktifkan layanan, dll.
            
        } else {
            $order->update([
                'status' => 'FAILED'
            ]);
        }
    }

    // 7. Kirim response "OK" ke DOKU
    //    Ini memberitahu DOKU bahwa Anda sudah menerima notifikasinya.
    return response()->json(['status' => 'OK'], 200);
}

}