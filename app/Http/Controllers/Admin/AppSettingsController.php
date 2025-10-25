<?php



namespace App\Http\Controllers\Admin;



use App\Http\Controllers\Controller;

use Illuminate\Http\Request;

use App\Models\Setting;

use App\Events\SliderUpdated;

use Illuminate\Support\Facades\Auth;

use Illuminate\Support\Facades\Hash;

use Illuminate\Support\Facades\Storage;

use Illuminate\Validation\Rules\Password;

use Illuminate\Validation\Rule; // ✅ DITAMBAHKAN: Untuk validasi unik



class AppSettingsController extends Controller

{

    /**

     * Menampilkan halaman pengaturan aplikasi.

     */

    public function index()

    {

        $sliderData = Setting::where('key', 'dashboard_slider')->first();

        $slides = $sliderData ? json_decode($sliderData->value, true) : [];



        $freezeSetting = Setting::where('key', 'auto_freeze_account')->first();

        $autoFreeze = $freezeSetting ? $freezeSetting->value : false;



        return view('admin.settings', [

            'admin' => Auth::user(),

            'slides' => $slides,

            'autoFreeze' => (bool)$autoFreeze

        ]);

    }



    /**

     * Memperbarui profil admin.

     */

    public function updateProfile(Request $request)

    {

        $admin = Auth::user();

        $request->validate([

            'nama_lengkap' => 'required|string|max:255',

            'email' => 'required|email|max:255|unique:Pengguna,email,' . $admin->id_pengguna . ',id_pengguna',

              // ✅ PERBAIKAN: Tambahkan aturan validasi unik untuk no_wa

            'no_hp' => 'nullable|string|max:20|unique:Pengguna,no_wa,' . $admin->id_pengguna . ',id_pengguna',

        

            'alamat' => 'nullable|string|max:500',

            'photo_profile' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',

        ]);



        // ✅ PERBAIKAN: Membuat array data dengan nama kolom database yang benar

        $updateData = [

            'nama_lengkap' => $request->nama_lengkap,

            'email' => $request->email,

            'no_wa' => $request->no_hp,           // Menggunakan 'no_wa'

            'address_detail' => $request->alamat, // Menggunakan 'address_detail'

        ];



        if ($request->hasFile('photo_profile')) {

            // Hapus foto lama jika ada

            if ($admin->store_logo_path && Storage::disk('public')->exists($admin->store_logo_path)) {

                Storage::disk('public')->delete($admin->store_logo_path);

            }

            $path = $request->file('photo_profile')->store('profile-photos', 'public');

            // Menyimpan ke kolom yang benar

            $updateData['store_logo_path'] = $path; 

        }



        $admin->update($updateData);



        return back()->with('success', 'Profil berhasil diperbarui.');

    }



    /**

     * Memperbarui password admin.

     */

    public function updatePassword(Request $request)

    {

        $request->validate([

            'current_password' => 'required|current_password',

            'password' => ['required', 'confirmed', Password::min(8)],

        ]);



        Auth::user()->update([

            'password' => Hash::make($request->password),

        ]);



        return back()->with('success', 'Password berhasil diubah.');

    }



    /**

     * Memperbarui pengaturan slider informasi dan mem-broadcast perubahannya.

     */

    public function updateSlider(Request $request)

    {

        $validated = $request->validate([

            'slides' => 'present|array',

            'slides.*.img' => 'required|url',

            'slides.*.title' => 'required|string|max:255',

            'slides.*.desc' => 'required|string|max:500',

        ]);



        Setting::updateOrCreate(

            ['key' => 'dashboard_slider'],

            ['value' => json_encode($validated['slides'])]

        );



        // Trigger the event to notify all clients

        event(new SliderUpdated($validated['slides']));



        return back()->with('success', 'Pengaturan slider berhasil disimpan dan disiarkan.');

    }



    /**

     * Memperbarui pengaturan umum aplikasi.

     */

    public function updateGeneral(Request $request)

    {

        Setting::updateOrCreate(

            ['key' => 'auto_freeze_account'],

            ['value' => $request->has('auto_freeze')]

        );



        return back()->with('success', 'Pengaturan umum berhasil disimpan.');

    }

}

