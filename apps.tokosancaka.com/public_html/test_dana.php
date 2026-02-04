<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require __DIR__ . '/vendor/autoload.php';

// Gunakan path .env dari folder UAT agar aman
$uatEnvPath = '/home/tokq3391/public_html/percetakan/uat-script';
$dotenv = Dotenv\Dotenv::createImmutable($uatEnvPath);
$dotenv->load();

// Import namespace yang sama dengan file CreateOrderTest Anda
use Dana\PaymentGateway\v1\Api\PaymentGatewayApi;
use Dana\Configuration;
use Dana\ObjectSerializer;
use Dana\Env;
use GuzzleHttp\Client;

echo "--- START TEST DANA SDK (CreateOrder Logic) ---\n";

// 1. Setup Configuration (Sesuai setUpBeforeClass di script Anda)
$configuration = new Configuration();
$configuration->setApiKey('PRIVATE_KEY', $_ENV['PRIVATE_KEY']);
$configuration->setApiKey('ORIGIN', $_ENV['ORIGIN']);
$configuration->setApiKey('X_PARTNER_ID', $_ENV['X_PARTNER_ID']);
$configuration->setApiKey('ENV', Env::SANDBOX);

// 2. Inisialisasi API Instance
$apiInstance = new PaymentGatewayApi(new Client(), $configuration);

// 3. Simulasi Data Request (Seperti mengambil dari JSON)
$partnerReferenceNo = 'REF' . date('YmdHis');
$jsonDict = [
    'partnerReferenceNo' => $partnerReferenceNo,
    'amount' => [
        'value' => '1000.00',
        'currency' => 'IDR'
    ],
    'payOptionDetails' => [
        [
            'payMethod' => 'BALANCE',
            'transAmount' => [
                'value' => '1000.00',
                'currency' => 'IDR'
            ]
        ]
    ],
    // Tambahkan field lain sesuai kebutuhan CreateOrder DANA
];

echo "Partner Ref No: $partnerReferenceNo\n";
echo "Menyusun Object Request...\n";

try {
    // 4. Deserialisasi (Sama dengan cara script UAT Anda)
    $createOrderRequestObj = ObjectSerializer::deserialize(
        $jsonDict,
        'Dana\PaymentGateway\v1\Model\CreateOrderByApiRequest'
    );

    echo "Memanggil API CreateOrder...\n";
    
    // 5. Eksekusi API Call
    // Kita gunakan dummy access token untuk memancing respon Signature
    $apiResponse = $apiInstance->createOrder($createOrderRequestObj, 'DUMMY_TOKEN_123');
    
    echo "RESPON SUKSES:\n";
    print_r($apiResponse);

} catch (\Dana\ApiException $e) {
    echo "--- HASIL API CALL ---\n";
    echo "HTTP CODE: " . $e->getCode() . "\n";
    $body = $e->getResponseBody();
    echo "BODY: " . $body . "\n";

    // Analisis Signature
    if (strpos($body, 'Invalid Signature') !== false) {
        echo "\nKESIMPULAN: X-SIGNATURE Ditolak. Periksa Private/Public Key.\n";
    } else {
        echo "\nKESIMPULAN: SIGNATURE LOLOS (Diterima DANA).\n";
    }
} catch (\Exception $e) {
    echo "ERROR SISTEM: " . $e->getMessage() . "\n";
}

echo "--- SELESAI ---\n";