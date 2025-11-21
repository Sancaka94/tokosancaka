<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class BannerController extends Controller
{
    public function index()
    {
        $banners = Banner::orderBy('order')->get();
        return view('admin.banners.index', compact('banners'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'link' => 'nullable|url',
            'order' => 'required|integer',
            'is_active' => 'sometimes|boolean',
            'image' => 'required|image|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        $data['is_active'] = $request->has('is_active');
        $data['image'] = $request->file('image')->store('banners', 'public');
        
        $banner = Banner::create($data);
        return redirect()->back()->with('success', 'Banner berhasil ditambahkan.');
    }

    public function update(Request $request, Banner $banner)
    {
        // Logika update bisa ditambahkan di sini
        $banner->update(['is_active' => $request->has('is_active')]);
        return redirect()->back()->with('success', 'Status banner berhasil diperbarui.');
    }


    public function destroy(Banner $banner)
    {
        Storage::disk('public')->delete($banner->image);
        $banner->delete();
        return redirect()->back()->with('success', 'Banner berhasil dihapus.');
    }
}
