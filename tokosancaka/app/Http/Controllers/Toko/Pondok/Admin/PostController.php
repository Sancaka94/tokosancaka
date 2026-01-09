<?php

namespace App\Http\Controllers\Pondok\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PostController extends Controller
{
    protected $connection;

    public function __construct()
    {
        $this->connection = DB::connection('pondok');
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $posts = $this->connection->table('posts')
            ->leftJoin('pengguna', 'posts.penulis_id', '=', 'pengguna.id')
            ->leftJoin('categories', 'posts.kategori_id', '=', 'categories.id')
            ->select('posts.*', 'pengguna.nama_lengkap as nama_penulis', 'categories.nama_kategori')
            ->paginate(15);

        return view('pondok.admin.posts.index', compact('posts'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $penulis = $this->connection->table('pengguna')->get();
        $kategori = $this->connection->table('categories')->get();
        return view('pondok.admin.posts.create', compact('penulis', 'kategori'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'judul' => 'required|string|max:255',
            'isi' => 'required|string',
            'penulis_id' => 'required|integer',
            'kategori_id' => 'required|integer',
            'status' => 'required|string', // Misal: 'Publish', 'Draft'
        ]);

        $data = $request->except('_token');
        $data['slug'] = Str::slug($request->judul, '-');

        // Cek keunikan slug, tambahkan angka jika sudah ada
        $slugCount = $this->connection->table('posts')->where('slug', $data['slug'])->count();
        if ($slugCount > 0) {
            $data['slug'] = $data['slug'] . '-' . ($slugCount + 1);
        }

        $this->connection->table('posts')->insert($data);
        return redirect()->route('admin.posts.index')->with('success', 'Post berhasil ditambahkan.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $post = $this->connection->table('posts')->find($id);
        return view('pondok.admin.posts.show', compact('post'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $post = $this->connection->table('posts')->find($id);
        $penulis = $this->connection->table('pengguna')->get();
        $kategori = $this->connection->table('categories')->get();
        return view('pondok.admin.posts.edit', compact('post', 'penulis', 'kategori'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $request->validate([
            'judul' => 'required|string|max:255',
            'isi' => 'required|string',
            'penulis_id' => 'required|integer',
            'kategori_id' => 'required|integer',
            'status' => 'required|string',
        ]);

        $data = $request->except(['_token', '_method']);
        $data['slug'] = Str::slug($request->judul, '-');

        // Cek keunikan slug, tambahkan angka jika sudah ada (dan bukan slug dari post yang sedang diedit)
        $slugCount = $this->connection->table('posts')->where('slug', $data['slug'])->where('id', '!=', $id)->count();
        if ($slugCount > 0) {
            $data['slug'] = $data['slug'] . '-' . ($slugCount + 1);
        }

        $this->connection->table('posts')->where('id', $id)->update($data);
        return redirect()->route('admin.posts.index')->with('success', 'Post berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $this->connection->table('posts')->where('id', $id)->delete();
        return redirect()->route('admin.posts.index')->with('success', 'Post berhasil dihapus.');
    }
}

