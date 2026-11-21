<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
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

        $rawUsername = strtolower(trim((string) $request->input('username')));
        $rawUsername = explode('@', $rawUsername)[0];
        $fullWebmail = "{$rawUsername}@{$domain}";

        $request->merge([
            'clean_username' => $rawUsername,
            'webmail_email'  => $fullWebmail,
        ]);

        $request->validate([
            'nama_lengkap'   => ['required', 'string', 'max:255'],
            'clean_username' => ['required', 'string', 'min:3', 'max:30', 'regex:/^[a-z0-9._-]+$/'],
            'webmail_email'  => ['required', 'string', 'email', 'max:191', Rule::unique('Pengguna', 'webmail_email'), Rule::unique('Pengguna', 'email')],
            'no_wa'          => ['required', 'string', 'min:9', 'max:20', 'regex:/^[0-9+]+$/'],
            'password'       => ['required', 'confirmed', Rules\Password::min(8)->mixedCase()->numbers()],
        ], [
            'clean_username.regex' => 'Username email hanya boleh berisi huruf kecil, angka, titik (.), strip (-), atau garis bawah (_).',
            'webmail_email.unique' => "Alamat {$fullWebmail} sudah digunakan. Silakan pilih username lain.",
            'no_wa.regex'          => 'Nomor WhatsApp hanya boleh berisi angka.',
            'password.mixed'       => 'Password harus mengandung minimal 1 huruf besar dan 1 huruf kecil.',
            'password.numbers'     => 'Password harus mengandung minimal 1 angka.',
        ]);

        // 1. Buat mailbox fisik di DirectAdmin server4.hostdata.id
        $mailResult = $this->createDirectAdminMailbox($rawUsername, $request->password);

        if (!$mailResult['success']) {
            throw ValidationException::withMessages([
                'username' => $mailResult['message'],
            ]);
        }

        // 2. Simpan user ke tabel Pengguna
        $user = User::create([
            'nama_lengkap'     => $request->nama_lengkap,
            'email'            => $fullWebmail,
            'webmail_email'    => $fullWebmail,
            'webmail_password' => Crypt::encryptString($request->password),
            'no_wa'            => $request->no_wa,
            'password_hash'    => $request->password,
            'role'             => 'user',
            'status'           => 'aktif',
            'ip_address'       => $request->ip(),
            'user_agent'       => $request->userAgent(),
        ]);

        Log::info('LOG LOG: [REGISTER WEBMAIL] Akun publik baru berhasil dibuat otomatis.', [
            'user_id'       => $user->id_pengguna,
            'webmail_email' => $fullWebmail,
            'no_wa'         => $request->no_wa,
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

            return [
                'success' => false,
                'message' => $result['details'] ?? $result['text'] ?? 'Gagal membuat alamat email di server.',
            ];
        } catch (\Throwable $e) {
            Log::error('LOG LOG: [DIRECTADMIN MAIL EXCEPTION] ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Tidak dapat terhubung ke mail server. Silakan coba lagi.',
            ];
        }
    }
}
