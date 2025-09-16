<?php

namespace App\Http\Livewire;

use App\Models\Product;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class ProductManager extends Component
{
    use WithPagination, WithFileUploads;

    // CRUD state
    public $productId = null;
    public $name, $description, $price, $stock, $weight, $category, $status, $image_url;
    public $product_image;

    // Restock state
    public $productToRestock = null;
    public $restockAmount = 1;

    // Table state
    public $search = '';
    public $perPage = 10;
    public $sortField = 'created_at';
    public $sortAsc = false;

    // UI state
    public $showModal = false;
    public $showRestockModal = false;
    public $isEditMode = false;

    // Bulk selection
    public $selectedProducts = [];
    public $selectAllOnPage = false;

    protected $listeners = [
        'deleteConfirmed' => 'destroy',
    ];

    // Optional: persist query string
    protected $queryString = [
        'search'   => ['except' => ''],
        'sortField' => ['except' => 'created_at'],
        'sortAsc'   => ['except' => false],
        'perPage'   => ['except' => 10],
    ];

    protected function rules()
    {
        return [
            'name'           => 'required|string|max:255',
            'description'    => 'nullable|string',
            'price'          => 'required|numeric|min:0',
            'stock'          => 'required|integer|min:0',
            'weight'         => 'required|integer|min:0',
            'category'       => 'required|string|max:255',
            'status'         => 'required|in:active,inactive',
            'product_image'  => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ];
    }

    protected $messages = [
        'name.required'      => 'Nama produk wajib diisi.',
        'price.required'     => 'Harga wajib diisi.',
        'stock.required'     => 'Stok wajib diisi.',
        'weight.required'    => 'Berat wajib diisi.',
        'category.required'  => 'Kategori wajib diisi.',
        'status.in'          => 'Status harus active atau inactive.',
        'product_image.image'=> 'File harus berupa gambar.',
    ];

    public function mount()
    {
        // Default status saat create
        $this->status = 'active';
    }

    public function updated($field)
    {
        $this->validateOnly($field);
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingPerPage()
    {
        $this->resetPage();
    }

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortAsc = !$this->sortAsc;
        } else {
            $this->sortField = $field;
            $this->sortAsc = true;
        }
        $this->resetPage();
    }

    // -------- CRUD --------

    public function create()
    {
        $this->resetInputFields();
        $this->isEditMode = false;
        $this->showModal = true;
    }

    public function edit($id)
    {
        $product = Product::findOrFail($id);
        $this->productId   = $product->id;
        $this->name        = $product->name;
        $this->description = $product->description;
        $this->price       = $product->price;
        $this->stock       = $product->stock;
        $this->weight      = $product->weight;
        $this->category    = $product->category;
        $this->status      = $product->status;
        $this->image_url   = $product->image_url; // relative path stored in DB
        $this->product_image = null;

        $this->isEditMode = true;
        $this->showModal  = true;
    }

    public function store()
    {
        $validated = $this->validate();

        // Handle image upload & replace
        if ($this->product_image) {
            // Delete old if editing
            if ($this->isEditMode && $this->image_url && Storage::disk('public')->exists($this->image_url)) {
                Storage::disk('public')->delete($this->image_url);
            }
            $validated['image_url'] = $this->product_image->store('products', 'public'); // relative path
        } else {
            // Keep existing image on edit
            if ($this->isEditMode && $this->image_url) {
                $validated['image_url'] = $this->image_url;
            }
        }

        // Always ensure slug exists, regenerate if name changed
        if ($this->isEditMode) {
            $product = Product::findOrFail($this->productId);
            if ($product->name !== $this->name || empty($product->slug)) {
                $validated['slug'] = Str::slug($this->name) . '-' . uniqid();
            }
        } else {
            $validated['slug'] = Str::slug($this->name) . '-' . uniqid();
        }

        Product::updateOrCreate(
            ['id' => $this->productId],
            $validated
        );

        session()->flash('success', $this->isEditMode ? 'Produk berhasil diperbarui.' : 'Produk baru berhasil ditambahkan.');
        $this->closeModal();

        // Refresh table
        $this->emit('productUpdated');
        $this->resetPage();
    }

    public function confirmDelete($id)
    {
        $this->productId = $id;
        $this->dispatchBrowserEvent('show-delete-confirmation'); // dipakai oleh JS kecil di blade
    }

    public function destroy()
    {
        $product = Product::find($this->productId);
        if ($product) {
            if ($product->image_url && Storage::disk('public')->exists($product->image_url)) {
                Storage::disk('public')->delete($product->image_url);
            }
            $product->delete();
            session()->flash('success', 'Produk berhasil dihapus.');
        }
        $this->productId = null;
        $this->emit('productUpdated');
        $this->resetPage();
    }

    // -------- RESTOCK --------

    public function openRestockModal($id)
    {
        $this->productToRestock = Product::findOrFail($id);
        $this->restockAmount = 1;
        $this->showRestockModal = true;
    }

    public function restockProduct()
    {
        $this->validate(['restockAmount' => 'required|integer|min:1']);

        if ($this->productToRestock) {
            $this->productToRestock->increment('stock', (int)$this->restockAmount);
            session()->flash('success', 'Stok untuk produk "' . $this->productToRestock->name . '" berhasil ditambahkan (+'.$this->restockAmount.').');
            $this->closeRestockModal();
            $this->emit('productUpdated');
        }
    }

    // -------- BULK --------

    public function toggleSelectAllOnPage($currentPageIds)
    {
        $this->selectAllOnPage = !$this->selectAllOnPage;

        if ($this->selectAllOnPage) {
            $this->selectedProducts = array_unique(array_merge($this->selectedProducts, $currentPageIds));
        } else {
            // Hapus yang di halaman ini dari selected
            $this->selectedProducts = array_values(array_diff($this->selectedProducts, $currentPageIds));
        }
    }

    public function deleteSelected()
    {
        if (empty($this->selectedProducts)) {
            session()->flash('error', 'Tidak ada produk yang dipilih.');
            return;
        }

        $products = Product::whereIn('id', $this->selectedProducts)->get();
        foreach ($products as $product) {
            if ($product->image_url && Storage::disk('public')->exists($product->image_url)) {
                Storage::disk('public')->delete($product->image_url);
            }
            $product->delete();
        }
        $count = count($this->selectedProducts);
        $this->selectedProducts = [];
        $this->selectAllOnPage = false;

        session()->flash('success', "$count produk terpilih berhasil dihapus.");
        $this->emit('productUpdated');
        $this->resetPage();
    }

    // -------- UI helpers --------

    public function closeModal()
    {
        $this->showModal = false;
        $this->resetInputFields();
    }

    public function closeRestockModal()
    {
        $this->showRestockModal = false;
        $this->reset(['productToRestock', 'restockAmount']);
    }

    private function resetInputFields()
    {
        $this->reset([
            'productId', 'name', 'description', 'price', 'stock',
            'weight', 'category', 'status', 'image_url', 'product_image'
        ]);
        $this->status = 'active';
    }

    // -------- Render --------

    protected function getProductsQuery()
    {
        return Product::query()
            ->when($this->search, function ($query) {
                $s = trim($this->search);
                $query->where(function ($q) use ($s) {
                    $q->where('name', 'like', "%{$s}%")
                      ->orWhere('category', 'like', "%{$s}%")
                      ->orWhere('slug', 'like', "%{$s}%");
                });
            });
    }

    public function render()
    {
        $query = $this->getProductsQuery()
            ->orderBy($this->sortField, $this->sortAsc ? 'asc' : 'desc');

        $products = $query->paginate($this->perPage);

        // id di halaman saat ini (untuk select all)
        $currentPageIds = $products->pluck('id')->map(fn($i) => (int) $i)->toArray();

        return view('livewire.product-manager', [
            'products'        => $products,
            'currentPageIds'  => $currentPageIds,
        ]);
    }
}
