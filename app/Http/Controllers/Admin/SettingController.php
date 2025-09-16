<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Setting;
use App\Models\BannerEtalase;
use Illuminate\Support\Facades\Storage;
use Exception;

class SettingController extends Controller
{
    public function index()
    {
        try {
            $banners = BannerEtalase::all();
            $settings = Setting::whereIn('key', ['logo','banner_2','banner_3'])
                                ->pluck('value','key');
            return view('admin.settings.index', compact('banners','settings'));
        } catch (Exception $e) {
            return back()->with('error', 'Failed to load settings: '.$e->getMessage());
        }
    }

    public function updateSettings(Request $request)
    {
        try {
            foreach (['logo','banner_2','banner_3'] as $key) {
                if ($request->hasFile($key)) {
                    $file = $request->file($key);
                    $path = $file->store('settings', 'public');

                    Setting::updateOrCreate(
                        ['key' => $key],
                        ['value' => $path]
                    );
                }
            }

            return back()->with('success', 'Settings updated successfully');
        } catch (Exception $e) {
            return back()->with('error', 'Failed to update settings: '.$e->getMessage());
        }
    }

    public function store(Request $request)
    {
        try {
            $request->validate([
                'image' => 'required|image|max:2048',
            ]);

            $path = $request->file('image')->store('banners', 'public');
            BannerEtalase::create(['image' => $path]);

            return back()->with('success', 'Banner added successfully');
        } catch (Exception $e) {
            return back()->with('error', 'Failed to add banner: '.$e->getMessage());
        }
    }

    public function update(Request $request, BannerEtalase $banner)
    {
        try {
            $request->validate([
                'image' => 'nullable|image|max:2048',
            ]);

            if ($request->hasFile('image')) {
                if ($banner->image) {
                    Storage::disk('public')->delete($banner->image);
                }
                $path = $request->file('image')->store('banners', 'public');
                $banner->update(['image' => $path]);
            }

            return back()->with('success', 'Banner updated successfully');
        } catch (Exception $e) {
            return back()->with('error', 'Failed to update banner: '.$e->getMessage());
        }
    }

    public function destroy(BannerEtalase $banner)
    {
        try {
            if ($banner->image) {
                Storage::disk('public')->delete($banner->image);
            }
            $banner->delete();
            return back()->with('success', 'Banner deleted successfully');
        } catch (Exception $e) {
            return back()->with('error', 'Failed to delete banner: '.$e->getMessage());
        }
    }
}
