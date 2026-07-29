<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BlockedIp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class BlockedIpController extends Controller
{
    public function index()
    {
        $blockedIps = BlockedIp::latest()->get();
        return view('admin.blocked-ips.index', compact('blockedIps'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'ip_address' => 'required|ip|unique:blocked_ips,ip_address',
            'reason' => 'nullable|string|max:255'
        ]);

        BlockedIp::create($request->all());

        // Hapus cache agar middleware mengambil data terbaru
        Cache::forget('blocked_ips');

        return back()->with('success', 'IP berhasil diblokir.');
    }

    public function destroy($id)
    {
        $blockedIp = BlockedIp::findOrFail($id);
        $blockedIp->delete();

        // Hapus cache agar middleware mengambil data terbaru
        Cache::forget('blocked_ips');

        return back()->with('success', 'Blokir IP berhasil dibuka.');
    }
}
