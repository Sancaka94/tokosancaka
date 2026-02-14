<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Product;
use App\Models\Category;
use App\Models\Tenant;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PosSystem extends Component
{
    use WithPagination;

    // State Data
    public $search = '';
    public $activeCategory = 'all';
    public $cart = [];
    public $subtotal = 0;
    public $discountAmount = 0;
    public $grandTotal = 0;
    public $customerNote = '';
    public $couponCode = '';

    // Super Admin State
    public $targetTenantId;
    public $isSuperAdmin = false;

    // Scanner State
    public $scannerModalOpen = false;

    // Listeners untuk event dari JS (Scanner)
    protected $listeners = ['scanProduct' => 'handleScan'];

    public function mount()
    {
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login');
        }

        // Logika Super Admin
        $this->isSuperAdmin = ($user->role === 'super_admin');
        $this->targetTenantId = $user->tenant_id;

        // Cek Expired
        $tenant = Tenant::find($this->targetTenantId);
        if ($tenant && $tenant->expired_at && now()->gt($tenant->expired_at)) {
            return redirect()->route('tenant.suspended');
        }
    }

    // Fungsi untuk Ganti Tenant (Super Admin)
    public function switchTenant($tenantId)
    {
        if ($this->isSuperAdmin) {
            $this->targetTenantId = $tenantId;
            $this->resetPage(); // Reset pagination produk
            $this->cart = [];   // Kosongkan keranjang biar ga nyampur
            $this->calculateTotal();
        }
    }

    // Tambah ke Keranjang
    public function addToCart($productId)
    {
        $product = Product::where('tenant_id', $this->targetTenantId)->find($productId);

        if (!$product || $product->stock <= 0) {
            $this->dispatch('swal:error', message: 'Stok Habis!');
            return;
        }

        // Cek jika produk sudah ada di cart
        if (isset($this->cart[$productId])) {
            $this->cart[$productId]['qty']++;
        } else {
            $this->cart[$productId] = [
                'id' => $product->id,
                'name' => $product->name,
                'price' => $product->sell_price,
                'qty' => 1,
                'image' => $product->image,
                'unit' => $product->unit
            ];
        }

        $this->calculateTotal();
    }

    // Update Qty (+ / -)
    public function updateQty($productId, $change)
    {
        if (isset($this->cart[$productId])) {
            $this->cart[$productId]['qty'] += $change;

            // Hapus jika qty <= 0
            if ($this->cart[$productId]['qty'] <= 0) {
                unset($this->cart[$productId]);
            }
        }
        $this->calculateTotal();
    }

    // Hapus Item
    public function removeFromCart($productId)
    {
        unset($this->cart[$productId]);
        $this->calculateTotal();
    }

    // Hitung Total
    public function calculateTotal()
    {
        $this->subtotal = 0;
        foreach ($this->cart as $item) {
            $this->subtotal += ($item['price'] * $item['qty']);
        }

        // Logika Diskon sederhana (bisa diperluas)
        $this->grandTotal = $this->subtotal - $this->discountAmount;
    }

    // Handle Scan dari JS
    public function handleScan($barcode)
    {
        $product = Product::where('tenant_id', $this->targetTenantId)
                          ->where('barcode', $barcode)
                          ->first();

        if ($product) {
            $this->addToCart($product->id);
            $this->dispatch('play-audio', type: 'success'); // Trigger suara di JS
        } else {
            $this->dispatch('play-audio', type: 'error');
            $this->dispatch('swal:error', message: 'Produk tidak ditemukan!');
        }
    }

    public function render()
    {
        // Query Kategori
        $categories = Category::where('tenant_id', $this->targetTenantId)->get();

        // Query Produk
        $query = Product::where('tenant_id', $this->targetTenantId);

        if ($this->search) {
            $query->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('sku', 'like', '%' . $this->search . '%');
        }

        if ($this->activeCategory !== 'all') {
            $query->whereHas('category', function($q) {
                $q->where('slug', $this->activeCategory);
            });
        }

        $products = $query->paginate(12);

        // Jika Super Admin, ambil list tenant
        $allTenants = $this->isSuperAdmin ? Tenant::orderBy('name')->get() : [];

        // Ambil Data Tenant Aktif untuk Header
        $currentTenant = Tenant::find($this->targetTenantId);

        return view('livewire.pos-system', [
            'products' => $products,
            'categories' => $categories,
            'allTenants' => $allTenants,
            'currentTenant' => $currentTenant
        ]);
    }
}
