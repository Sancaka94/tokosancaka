<?php



use Illuminate\Support\Facades\Facade;



return [



    /*

    |--------------------------------------------------------------------------

    | Application Name

    |--------------------------------------------------------------------------

    */



    'name' => env('APP_NAME', 'Laravel'),



    /*

    |--------------------------------------------------------------------------

    | Application Environment

    |--------------------------------------------------------------------------

    */



    'env' => env('APP_ENV', 'production'),



    /*

    |--------------------------------------------------------------------------

    | Application Debug Mode

    |--------------------------------------------------------------------------

    */



    'debug' => (bool) env('APP_DEBUG', false),



    /*

    |--------------------------------------------------------------------------

    | Application URL

    |--------------------------------------------------------------------------

    */



    'url' => env('APP_URL', 'http://localhost'),



    /*

    |--------------------------------------------------------------------------

    | Application Timezone

    |--------------------------------------------------------------------------

    */



    'timezone' => env('APP_TIMEZONE', 'Asia/Jakarta'),



    /*

    |--------------------------------------------------------------------------

    | Application Locale Configuration

    |--------------------------------------------------------------------------

    */



    'locale' => env('APP_LOCALE', 'id'),



    'fallback_locale' => env('APP_FALLBACK_LOCALE', 'en'),



    'faker_locale' => env('APP_FAKER_LOCALE', 'id_ID'),



    /*

    |--------------------------------------------------------------------------

    | Encryption Key

    |--------------------------------------------------------------------------

    */



    'cipher' => 'AES-256-CBC',



    'key' => env('APP_KEY'),



    'previous_keys' => [

        ...array_filter(

            explode(',', env('APP_PREVIOUS_KEYS', ''))

        ),

    ],



    /*

    |--------------------------------------------------------------------------

    | Maintenance Mode Driver

    |--------------------------------------------------------------------------

    */



    'maintenance' => [

        'driver' => env('APP_MAINTENANCE_DRIVER', 'file'),

        // 'store'  => 'redis',

    ],



    /*

    |--------------------------------------------------------------------------

    | Autoloaded Service Providers

    |--------------------------------------------------------------------------

    */



    'providers' => [



        /*

         * Laravel Framework Service Providers...

         */

        Illuminate\Auth\AuthServiceProvider::class,

        Illuminate\Broadcasting\BroadcastServiceProvider::class,

        Illuminate\Bus\BusServiceProvider::class,

        Illuminate\Cache\CacheServiceProvider::class,

        Illuminate\Foundation\Providers\ConsoleSupportServiceProvider::class,

        Illuminate\Cookie\CookieServiceProvider::class,

        Illuminate\Database\DatabaseServiceProvider::class,

        Illuminate\Encryption\EncryptionServiceProvider::class,

        Illuminate\Filesystem\FilesystemServiceProvider::class,

        Illuminate\Foundation\Providers\FoundationServiceProvider::class,

        Illuminate\Hashing\HashServiceProvider::class,

        Illuminate\Mail\MailServiceProvider::class,

        Illuminate\Notifications\NotificationServiceProvider::class,

        Illuminate\Pagination\PaginationServiceProvider::class,

        Illuminate\Pipeline\PipelineServiceProvider::class,

        Illuminate\Queue\QueueServiceProvider::class,

        Illuminate\Redis\RedisServiceProvider::class,

        Illuminate\Auth\Passwords\PasswordResetServiceProvider::class,

        Illuminate\Session\SessionServiceProvider::class,

        Illuminate\Translation\TranslationServiceProvider::class,

        Illuminate\Validation\ValidationServiceProvider::class,

        Illuminate\View\ViewServiceProvider::class,



        /*

         * Package Service Providers...

         */

        Milon\Barcode\BarcodeServiceProvider::class, // <-- PERBAIKAN: Menambahkan provider barcode



        /*

         * Application Service Providers...

         */

        App\Providers\AppServiceProvider::class,

        App\Providers\AuthServiceProvider::class,

        App\Providers\BroadcastServiceProvider::class,

        App\Providers\EventServiceProvider::class,

        App\Providers\RouteServiceProvider::class,

        // ✅ DITAMBAHKAN: Mendaftarkan Composer Service Provider agar notifikasi di header berfungsi
        App\Providers\ComposerServiceProvider::class,

    ],



    /*

    |--------------------------------------------------------------------------

    | Class Aliases

    |--------------------------------------------------------------------------

    */



    'aliases' => Facade::defaultAliases()->merge([

        // ...

        'DNS1D' => Milon\Barcode\Facades\DNS1DFacade::class, // <-- PERBAIKAN: Menambahkan alias barcode 1D

        'DNS2D' => Milon\Barcode\Facades\DNS2DFacade::class, // <-- PERBAIKAN: Menambahkan alias barcode 2D

    ])->toArray(),



];

