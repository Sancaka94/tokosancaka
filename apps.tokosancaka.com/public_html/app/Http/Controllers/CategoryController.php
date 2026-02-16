<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Tenant; // Import Model Tenant
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule; // Import Rule untuk validasi unique per tenant

class CategoryController extends Controller
{
    // 1. Variabel Global Tenant ID
    protected $tenantId;

    public function __construct(Request $request)
    {
        // 2. Deteksi Tenant dari Subdomain
        $host = $request->getHost();
        $subdomain = explode('.', $host)[0];

        $tenant = Tenant::where('subdomain', $subdomain)->first();

        // Default ke 1 jika tidak ada (Pusat)
        $this->tenantId = $tenant ? $tenant->id : 1;
    }

    /**
     * Menampilkan daftar kategori
     */
    public function index($subdomain)
    {
        // Filter kategori berdasarkan tenant_id
        $categories = Category::where('tenant_id', $this->tenantId)
            ->latest()
            ->paginate(10);

        return view('categories.index', compact('categories'));
    }

    /**
     * Menampilkan form tambah kategori
     */
    public function create($subdomain)
    {
        return view('categories.create');
    }

    /**
     * Menyimpan kategori baru ke database
     */
    public function store(Request $request, $subdomain)
    {
        // 1. Validasi Input
        $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                // Validasi nama unik TAPI hanya di dalam tenant ini
                Rule::unique('categories')->where(function ($query) {
                    return $query->where('tenant_id', $this->tenantId);
                })
            ],
            'type'                => 'required|in:physical,service',
            'default_unit'        => 'required|string|max:10',
            'description'         => 'nullable|string',
            'image'               => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'product_presets_input' => 'nullable|string',
        ]);

        try {
            $data = $request->all();

            // 2. Masukkan Tenant ID
            $data['tenant_id'] = $this->tenantId;

            // 3. Generate Slug Otomatis
            $data['slug'] = Str::slug($request->name);

            // 4. Handle Upload Gambar
            if ($request->hasFile('image')) {
                // Opsional: Bisa dipisah per tenant folder, misal: categories/{tenant_id}
                $data['image'] = $request->file('image')->store('categories', 'public');
            }

            // 5. Handle Product Presets (Textarea -> JSON Array)
            if ($request->filled('product_presets_input')) {
                $presets = explode("\n", str_replace("\r", "", $request->product_presets_input));
                $data['product_presets'] = array_values(array_filter(array_map('trim', $presets)));
            } else {
                $data['product_presets'] = null;
            }

            // 6. Handle Checkbox Active
            $data['is_active'] = $request->has('is_active') ? 1 : 0;

            // 7. Simpan Data
            Category::create($data);

            return redirect()->route('categories.index')->with('success', 'Kategori berhasil ditambahkan!');

        } catch (\Exception $e) {
            Log::error("Gagal store kategori tenant {$this->tenantId}: " . $e->getMessage());
            return back()->withInput()->with('error', 'Terjadi kesalahan sistem.');
        }
    }

    /**
     * Menampilkan form edit kategori
     */
    public function edit($subdomain, $id)
    {
        // Cari data dan pastikan milik tenant ini
        $category = Category::where('tenant_id', $this->tenantId)
            ->where('id', $id)
            ->firstOrFail();

        // Format JSON ke String untuk textarea
        $presetString = '';
        if (!empty($category->product_presets) && is_array($category->product_presets)) {
            $presetString = implode("\n", $category->product_presets);
        }

        return view('categories.edit', compact('category', 'presetString'));
    }

    /**
     * Memperbarui data kategori
     */
    public function update(Request $request, $subdomain, $id)
    {
        // Cari data dan pastikan milik tenant ini
        $category = Category::where('tenant_id', $this->tenantId)
            ->where('id', $id)
            ->firstOrFail();

        // 1. Validasi
        $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                // Validasi unik ignore ID sendiri & scope tenant
                Rule::unique('categories')->ignore($category->id)->where(function ($query) {
                    return $query->where('tenant_id', $this->tenantId);
                })
            ],
            'type'                => 'required|in:physical,service',
            'default_unit'        => 'required|string|max:10',
            'description'         => 'nullable|string',
            'image'               => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        try {
            $data = $request->all();

            // 2. Update Slug
            $data['slug'] = Str::slug($request->name);

            // 3. Handle Gambar Baru
            if ($request->hasFile('image')) {
                if ($category->image && Storage::disk('public')->exists($category->image)) {
                    Storage::disk('public')->delete($category->image);
                }
                $data['image'] = $request->file('image')->store('categories', 'public');
            }

            // 4. Handle Product Presets
            if ($request->filled('product_presets_input')) {
                $presets = explode("\n", str_replace("\r", "", $request->product_presets_input));
                $data['product_presets'] = array_values(array_filter(array_map('trim', $presets)));
            } else {
                $data['product_presets'] = null;
            }

            // 5. Handle Checkbox Active
            $data['is_active'] = $request->has('is_active') ? 1 : 0;

            // Note: tenant_id tidak perlu di-update

            // 6. Update Database
            $category->update($data);

            return redirect()->route('categories.index')->with('success', 'Kategori berhasil diperbarui!');

        } catch (\Exception $e) {
            Log::error("Gagal update kategori tenant {$this->tenantId}: " . $e->getMessage());
            return back()->withInput()->with('error', 'Terjadi kesalahan saat update.');
        }
    }

    /**
     * Menghapus kategori
     */
    public function destroy($subdomain, $id)
    {
        // Cari data dan pastikan milik tenant ini
        $category = Category::where('tenant_id', $this->tenantId)
            ->where('id', $id)
            ->firstOrFail();

        try {
            // 1. Hapus File Gambar Fisik
            if ($category->image && Storage::disk('public')->exists($category->image)) {
                Storage::disk('public')->delete($category->image);
            }

            // 2. Hapus Data Database
            $category->delete();

            return redirect()->route('categories.index')->with('success', 'Kategori berhasil dihapus.');

        } catch (\Exception $e) {
            return back()->with('error', 'Gagal menghapus: ' . $e->getMessage());
        }
    }
}
