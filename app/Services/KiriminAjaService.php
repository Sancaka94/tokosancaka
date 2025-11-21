<?php



namespace App\Services;



use Illuminate\Support\Facades\Http;

use Illuminate\Support\Facades\Log; // PERBAIKAN: Menambahkan import Log





class KiriminAjaService

{

    protected $baseUrl;

    protected $token;



    public function __construct()

    {

        $this->baseUrl = config('services.kiriminaja.base_url', 'https://tdev.kiriminaja.com');

        $this->token   = config('services.kiriminaja.token');

    }

    
    /**
     * Melakukan pelacakan paket (Express atau Instant).
     */
    public function track(?string $serviceType, string $orderId): ?array
    {
        if (empty($orderId)) {
            return null;
        }

        if ($serviceType == 'instant') {
            return $this->request('GET', "/api/mitra/v4/instant/tracking/{$orderId}");
        }
        
        return $this->request('POST', '/api/mitra/tracking', ['order_id' => $orderId]);
    }



  public function getExpressPricing($origin, $subOrigin, $destination, $subDestination, $weight, $length, $width, $height, $itemValue, $couriers = null, $category = 'regular', $insurance = 0)

    {

        if ($category === 'trucking') {

            $volumetricWeight = ($width * $length * $height) / 4000 * 1000; // Standar trucking

        } else {

            $volumetricWeight = ($width * $length * $height) / 6000 * 1000; // Standar reguler

        }

    

        $finalWeight = max($weight, $volumetricWeight);

    

        $payload = [

            "origin"                  => $origin,

            "subdistrict_origin"      => $subOrigin,

            "destination"             => $destination,

            "subdistrict_destination" => $subDestination,

            "weight"                  => (int) ceil($finalWeight),

            "length"                  => $length,

            "width"                   => $width,

            "height"                  => $height,

            "item_value"              => $itemValue,

            "insurance"               => $insurance,

            "courier"                 => $couriers ?? []

        

        ];



        Log::info('KiriminAja Pricing Payload:', $payload);



        $response = Http::withToken($this->token)

            ->acceptJson()

            ->post($this->baseUrl . '/api/mitra/v6.1/shipping_price', $payload);

        

        Log::info('KiriminAja Pricing Response:', ['body' => $response->json()]);

        return $response->json();

    }



    public function getInstantPricing($originLat, $originLng, $originAddress, $destLat, $destLng, $destAddress, $weight, $itemPrice, $vehicle = 'motor', $services = ['gosend','grab_express'])

    {

        $response = Http::withToken($this->token)

            ->acceptJson()

            ->post($this->baseUrl . '/api/mitra/v4/instant/pricing', [

                "service"     => $services,

                "item_price"  => (int)$itemPrice,

                "origin"      => [

                    "lat"     => $originLat,

                    "long"    => $originLng,

                    "address" => $originAddress,

                ],

                "destination" => [

                    "lat"     => $destLat,

                    "long"    => $destLng,

                    "address" => $destAddress,

                ],

                "weight"     => (int)$weight,

                "vehicle"    => $vehicle,

                "timezone"   => "WIB"

            ]);



        return $response->json();

    }

    

    public function searchAddress($keyword)

    {

        $response = Http::withToken($this->token)

            ->acceptJson()

            ->get($this->baseUrl . '/api/mitra/v6.1/addresses', [

                'search' => $keyword

            ]);

    

        return $response->json();

    }

    

    public function createExpressOrder(array $data)

    {

        return $this->request('/api/mitra/v6.1/request_pickup', 'POST', $data);

    }

    

    public function createInstantOrder(array $data)

    {

        return $this->request('/api/mitra/v4/instant/pickup/request', 'POST', $data);

    }

    

    public function setCallback(string $url)

    {

        return $this->request('/api/mitra/set_callback', 'POST', [

            'url' => $url,

        ]);

    }

    

    private function request($endpoint, $method, $payload = [])

    {

        $url = $this->baseUrl . $endpoint;

        $apiKey = $this->token;

    

        $ch = curl_init();

        curl_setopt_array($ch, [

            CURLOPT_URL => $url,

            CURLOPT_RETURNTRANSFER => true,

            CURLOPT_CUSTOMREQUEST => $method,

            CURLOPT_POSTFIELDS => json_encode($payload),

            CURLOPT_HTTPHEADER => [

                'Content-Type: application/json',

                'Authorization: Bearer ' . $apiKey,

            ],

            CURLOPT_FAILONERROR => false,

        ]);

    

        $response = curl_exec($ch);

        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

    

        if ($httpcode >= 400) {

            throw new \Exception("HTTP error $httpcode | Response: " . $response);

        }

    

        return json_decode($response, true);

    }

    

    public function getSchedules()

    {

        $url = $this->baseUrl . '/api/mitra/v2/schedules';



        $response = Http::withToken($this->token)

            ->post($url);



        if ($response->successful() && isset($response['schedules'])) {

            foreach ($response['schedules'] as $schedule) {

                if (!$schedule['libur']) {

                    return $schedule;

                }

            }

        }



        return null;

    }



}

