<?php

namespace App\Http\Controllers;

use App\Models\CashflowContact;
use Illuminate\Http\Request;

class CashflowContactController extends Controller
{
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
