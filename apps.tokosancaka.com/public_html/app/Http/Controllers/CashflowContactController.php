<?php

namespace App\Http\Controllers;

use App\Models\CashflowContact;
use Illuminate\Http\Request;
use App\Models\Tenant;


class CashflowContactController extends Controller
{
     // 1. Siapkan variabel penampung ID Tenant
    protected $tenantId;

    public function __construct(Request $request)
    {
        // 2. Deteksi Tenant dari Subdomain URL (Berlaku untuk semua fungsi)
        $host = $request->getHost();
        $subdomain = explode('.', $host)[0];

        // 3. Cari data Tenant-nya
        $tenant = \App\Models\Tenant::where('subdomain', $subdomain)->first();

        // 4. Simpan ID-nya. Jika tidak ketemu, default ke 1 (Pusat)
        $this->tenantId = $tenant ? $tenant->id : 1;
    }

    public function index()
    {
        $contacts = CashflowContact::orderBy('name', 'asc')->paginate(20);
        return view('cashflow.contacts.index', compact('contacts'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'phone' => 'nullable|numeric'
        ]);

        CashflowContact::create($request->all());
        return redirect()->back()->with('success', 'Kontak berhasil ditambahkan');
    }

    public function update(Request $request, $id)
    {
        $contact = CashflowContact::findOrFail($id);
        $contact->update($request->all());
        return redirect()->back()->with('success', 'Kontak berhasil diperbarui');
    }

    public function destroy($id)
    {
        CashflowContact::findOrFail($id)->delete();
        return redirect()->back()->with('success', 'Kontak dihapus');
    }
}
