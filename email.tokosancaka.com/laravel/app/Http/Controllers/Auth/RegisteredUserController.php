<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Daftar username resmi yang tidak boleh didaftarkan oleh publik
     */
    private const RESERVED_USERNAMES = [
        'admin', 'administrator', 'superadmin', 'root', 'system',
        'postmaster', 'webmaster', 'hostmaster', 'abuse', 'security',
        'noreply', 'no-reply', 'support', 'help', 'info', 'contact',
        'billing', 'finance', 'hrd', 'legal', 'marketing', 'sales',
        'cs', 'customerservice', 'official', 'staff', 'operator',
        'mail', 'email', 'smtp', 'imap', 'pop', 'pop3', 'ftp',
        'sancaka', 'tokosancaka', 'sancakaexpress',
    ];

    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request & auto-create @tokosancaka.com mailbox.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $domain = (string) env('DA_MAIL_DOMAIN', 'tokosancaka.com');

        // Bersihkan username email (ambil bagian sebelum '@')
        $rawUsername = strtolower(trim((string) $request->input('username')));
        $rawUsername = explode('@', $rawUsername)[0];
        $fullWebmail = "{$rawUsername}@{$domain}";

        // Normalisasi nomor WhatsApp ke format 08xxx agar pengecekan unique akurat
        $cleanWa = preg_replace('/[^0-9]/', '', (string) $request->input('no_wa'));
        if (str_starts_with($cleanWa, '62')) {
            $cleanWa = '0' . substr($cleanWa, 2);
        }

        $request->merge([
            'clean_username' => $rawUsername,
            'webmail_email'  => $fullWebmail,
            'no_wa'          => $cleanWa,
        ]);

        $request->validate([
            'nama_lengkap'   => ['required', 'string', 'max:255'],
            'clean_username' => [
                'required',
                'string',
                'min:3',
                'max:30',
                'regex:/^[a-z0-9._-]+$/',
                Rule::notIn(self::RESERVED_USERNAMES),
            ],
            'webmail_email'  => [
                'required',
                'string',
                'email',
                'max:191',
                Rule::unique('Pengguna', 'webmail_email'),
                Rule::unique('Pengguna', 'email'),
            ],
            'no_wa'          => [
                'required',
                'string',
                'min:9',
                'max:20',
                'regex:/^[0-9]+$/',
                Rule::unique('Pengguna', 'no_wa'),
            ],
            'password'       => ['required', 'confirmed', Rules\Password::min(8)->mixedCase()->numbers()],
        ], [
            'clean_username.regex'  => 'Username email hanya boleh berisi huruf kecil, angka, titik (.), strip (-), atau garis bawah (_).',
            'clean_username.not_in' => "Username '{$rawUsername}' dicadangkan oleh sistem. Silakan pilih nama lain.",
            'webmail_email.unique'  => "Alamat {$fullWebmail} sudah digunakan. Silakan pilih username lain.",
            'no_wa.regex'           => 'Nomor WhatsApp hanya boleh berisi angka.',
            'no_wa.unique'          => 'Nomor WhatsApp ini sudah terdaftar pada akun lain.',
            'password.mixed'        => 'Password harus mengandung minimal 1 huruf besar dan 1 huruf kecil.',
            'password.numbers'      => 'Password harus mengandung minimal 1 angka.',
        ]);

        // 1. Buat mailbox fisik di DirectAdmin server4.hostdata.id
        $mailResult = $this->createDirectAdminMailbox($rawUsername, $request->password);

        if (!$mailResult['success']) {
            throw ValidationException::withMessages([
                'username' => $mailResult['message'],
            ]);
        }

        // 2. Simpan user ke tabel Pengguna (dengan auto-rollback mailbox jika DB gagal)
        try {
            $user = DB::transaction(function () use ($request, $fullWebmail, $cleanWa) {
                return User::create([
                    'nama_lengkap'     => trim($request->nama_lengkap),
                    'email'            => $fullWebmail,
                    'webmail_email'    => $fullWebmail,
                    'webmail_password' => Crypt::encryptString($request->password),
                    'no_wa'            => $cleanWa,
                    'password_hash'    => $request->password,
                    'role'             => 'user',
                    'status'           => 'aktif',
                    'ip_address'       => $request->ip(),
                    'user_agent'       => $request->userAgent(),
                ]);
            });
        } catch (\Throwable $e) {
            // Hapus kembali mailbox di DirectAdmin agar tidak menjadi akun yatim
            $this->deleteDirectAdminMailbox($rawUsername);

            Log::error('LOG LOG: [REGISTER DB ERROR] Gagal menyimpan user ke database setelah mailbox dibuat.', [
                'webmail_email' => $fullWebmail,
                'error'         => $e->getMessage(),
            ]);

            throw ValidationException::withMessages([
                'username' => 'Terjadi kendala saat menyimpan data akun. Silakan coba beberapa saat lagi.',
            ]);
        }

        Log::info('LOG LOG: [REGISTER WEBMAIL] Akun publik baru berhasil dibuat otomatis.', [
            'user_id'       => $user->id_pengguna,
            'webmail_email' => $fullWebmail,
            'no_wa'         => $cleanWa,
        ]);

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('dashboard', absolute: false));
    }

    /**
     * Helper internal: Membuat mailbox di DirectAdmin via CMD_API_POP
     */
    private function createDirectAdminMailbox(string $username, string $password): array
    {
        $host   = rtrim((string) env('DA_HOST', 'https://server4.hostdata.id:2222'), '/');
        $daUser = (string) env('DA_USERNAME');
        $daKey  = (string) env('DA_LOGIN_KEY');
        $domain = (string) env('DA_MAIL_DOMAIN', 'tokosancaka.com');
        $quota  = (int) env('DA_MAIL_QUOTA', 500);

        if (empty($daUser) || empty($daKey)) {
            Log::error('LOG LOG: [DIRECTADMIN MAIL] DA_USERNAME atau DA_LOGIN_KEY belum diatur di .env');
            return [
                'success' => false,
                'message' => 'Konfigurasi server mail belum lengkap. Hubungi administrator.',
            ];
        }

        try {
            $response = Http::withBasicAuth($daUser, $daKey)
                ->asForm()
                ->timeout(15)
                ->post("{$host}/CMD_API_POP", [
                    'action'  => 'create',
                    'domain'  => $domain,
                    'user'    => $username,
                    'passwd'  => $password,
                    'passwd2' => $password,
                    'quota'   => $quota,
                ]);

            parse_str($response->body(), $result);

            if (($result['error'] ?? '1') === '0') {
                Log::info("LOG LOG: [DIRECTADMIN MAIL] Sukses membuat mailbox {$username}@{$domain}");
                return [
                    'success' => true,
                    'email'   => "{$username}@{$domain}",
                ];
            }

            Log::error('LOG LOG: [DIRECTADMIN MAIL GAGAL]', [
                'username' => $username,
                'response' => $result,
            ]);

            $rawError = trim(strip_tags((string) ($result['details'] ?? $result['text'] ?? 'Gagal membuat alamat email di server.')));

            if (stripos($rawError, 'already exists') !== false) {
                $rawError = "Alamat {$username}@{$domain} sudah terdaftar di server email. Silakan pilih nama lain.";
            }

            return [
                'success' => false,
                'message' => $rawError,
            ];
        } catch (\Throwable $e) {
            Log::error('LOG LOG: [DIRECTADMIN MAIL EXCEPTION] ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Tidak dapat terhubung ke mail server. Silakan coba lagi.',
            ];
        }
    }

    /**
     * Helper internal: Menghapus mailbox di DirectAdmin jika penyimpanan DB gagal (Rollback)
     */
    private function deleteDirectAdminMailbox(string $username): void
    {
        $host   = rtrim((string) env('DA_HOST', 'https://server4.hostdata.id:2222'), '/');
        $daUser = (string) env('DA_USERNAME');
        $daKey  = (string) env('DA_LOGIN_KEY');
        $domain = (string) env('DA_MAIL_DOMAIN', 'tokosancaka.com');

        if (empty($daUser) || empty($daKey)) {
            return;
        }

        try {
            Http::withBasicAuth($daUser, $daKey)
                ->asForm()
                ->timeout(10)
                ->post("{$host}/CMD_API_POP", [
                    'action' => 'delete',
                    'domain' => $domain,
                    'user'   => $username,
                ]);

            Log::warning("LOG LOG: [DIRECTADMIN MAIL ROLLBACK] Mailbox {$username}@{$domain} dihapus karena penyimpanan database gagal.");
        } catch (\Throwable $e) {
            Log::error('LOG LOG: [DIRECTADMIN MAIL ROLLBACK GAGAL] ' . $e->getMessage());
        }
    }
}
