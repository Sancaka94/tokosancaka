@extends('layouts.member')

@section('title', 'Pengaturan Akun')

@section('content')

@if(session('success'))
<div class="mb-6 p-4 bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-xl flex items-center gap-3">
    <i class="fas fa-check-circle text-xl"></i>
    <div class="text-sm font-bold">{{ session('success') }}</div>
</div>
@endif

<div class="grid md:grid-cols-2 gap-6">
    
    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6 h-fit">
        <h2 class="text-lg font-bold text-slate-800 mb-1">Data Diri</h2>
        <p class="text-xs text-slate-500 mb-6">Perbarui informasi profil dan rekening Anda.</p>

        <form action="{{ route('member.settings.update') }}" method="POST" class="space-y-4">
            @csrf
            @method('PUT')

            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Nama Lengkap</label>
                <input type="text" name="name" value="{{ old('name', $member->name) }}" class="w-full px-4 py-2 rounded-lg border border-slate-200 focus:ring-2 focus:ring-blue-500 text-sm font-bold text-slate-700">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Nomor WhatsApp</label>
                <input type="text" value="{{ $member->whatsapp }}" disabled class="w-full px-4 py-2 rounded-lg border border-slate-100 bg-slate-50 text-slate-400 text-sm font-bold cursor-not-allowed" title="Nomor WA tidak dapat diubah">
                <p class="text-[10px] text-slate-400 mt-1">*Hubungi admin jika ingin ubah nomor.</p>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Alamat Lengkap</label>
                <textarea name="address" rows="2" class="w-full px-4 py-2 rounded-lg border border-slate-200 focus:ring-2 focus:ring-blue-500 text-sm font-bold text-slate-700">{{ old('address', $member->address) }}</textarea>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Nama Bank / E-Wallet</label>
                    <input type="text" name="bank_name" value="{{ old('bank_name', $member->bank_name) }}" placeholder="BCA / DANA" class="w-full px-4 py-2 rounded-lg border border-slate-200 focus:ring-2 focus:ring-blue-500 text-sm font-bold text-slate-700">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">No. Rekening</label>
                    <input type="number" name="bank_account_number" value="{{ old('bank_account_number', $member->bank_account_number) }}" class="w-full px-4 py-2 rounded-lg border border-slate-200 focus:ring-2 focus:ring-blue-500 text-sm font-bold text-slate-700">
                </div>
            </div>

            <button type="submit" class="w-full py-3 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xl shadow-lg shadow-blue-200 transition mt-2">
                Simpan Perubahan
            </button>
        </form>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6 h-fit">
        <h2 class="text-lg font-bold text-slate-800 mb-1">Keamanan</h2>
        <p class="text-xs text-slate-500 mb-6">Ganti PIN login Anda secara berkala.</p>

        @if($errors->any())
        <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg text-xs text-red-600 font-bold">
            <ul class="list-disc ml-4">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        <form action="{{ route('member.settings.update-pin') }}" method="POST" class="space-y-4">
            @csrf
            @method('PUT')

            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase mb-1">PIN Lama</label>
                <div class="relative">
                    <input type="password" name="current_pin" maxlength="6" required class="w-full px-4 py-2 rounded-lg border border-slate-200 focus:ring-2 focus:ring-blue-500 text-sm font-bold text-slate-700 tracking-widest">
                    <i class="fas fa-lock absolute right-4 top-3 text-slate-300"></i>
                </div>
            </div>

            <div class="border-t border-slate-100 my-2"></div>

            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase mb-1">PIN Baru</label>
                <div class="relative">
                    <input type="password" name="new_pin" maxlength="6" required class="w-full px-4 py-2 rounded-lg border border-slate-200 focus:ring-2 focus:ring-blue-500 text-sm font-bold text-slate-700 tracking-widest">
                </div>
                <p class="text-[10px] text-slate-400 mt-1">Minimal 6 angka.</p>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Konfirmasi PIN Baru</label>
                <input type="password" name="new_pin_confirmation" maxlength="6" required class="w-full px-4 py-2 rounded-lg border border-slate-200 focus:ring-2 focus:ring-blue-500 text-sm font-bold text-slate-700 tracking-widest">
            </div>

            <button type="submit" class="w-full py-3 bg-slate-800 hover:bg-slate-900 text-white font-bold rounded-xl shadow-lg transition mt-2">
                Update PIN
            </button>
        </form>
    </div>
</div>
@endsection