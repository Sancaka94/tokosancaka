@extends('layouts.app')

@section('content')
    <div class="px-4 sm:px-6 lg:px-8 py-8 w-full max-w-3xl mx-auto">

        <div class="sm:flex sm:justify-between sm:items-center mb-8">
            <div class="mb-4 sm:mb-0 flex items-center gap-4">
                <a href="{{ route('tenants.index') }}" class="p-2 bg-white border border-slate-200 rounded hover:bg-slate-50 transition">
                    <i class="fas fa-arrow-left text-slate-500"></i>
                </a>
                <div>
                    <h1 class="text-2xl md:text-3xl text-slate-800 font-bold">Edit Tenant</h1>
                    <p class="text-slate-500 text-sm mt-1">Ubah data dan status toko {{ $tenant->name }}.</p>
                </div>
            </div>
        </div>

        @if ($errors->any())
            <div class="mb-6 bg-red-50 border border-red-200 text-red-600 px-4 py-3 rounded-lg">
                <div class="font-semibold mb-1">Terdapat kesalahan:</div>
                <ul class="list-disc list-inside text-sm">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden">
            <form action="{{ route('tenants.update', $tenant->id) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="p-6 space-y-6">
                    
                    <div>
                        <label class="block text-sm font-medium mb-1 text-slate-700">Subdomain / URL Toko</label>
                        <div class="flex items-center">
                            <input type="text" class="form-input w-full bg-slate-100 text-slate-500 border-slate-200 cursor-not-allowed rounded-r-none" value="{{ $tenant->subdomain }}" disabled>
                            <span class="px-3 py-2 bg-slate-100 border border-l-0 border-slate-200 text-slate-500 rounded-r text-sm">.tokosancaka.com</span>
                        </div>
                        <p class="text-xs text-slate-400 mt-1"><i class="fas fa-info-circle mr-1"></i> Subdomain tidak dapat diubah setelah pendaftaran.</p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="name" class="block text-sm font-medium mb-1 text-slate-700">Nama Usaha <span class="text-red-500">*</span></label>
                            <input id="name" name="name" type="text" class="form-input w-full border-slate-200 rounded-lg focus:ring-blue-500 focus:border-blue-500" value="{{ old('name', $tenant->name) }}" required>
                        </div>

                        <div>
                            <label for="whatsapp" class="block text-sm font-medium mb-1 text-slate-700">No. WhatsApp <span class="text-red-500">*</span></label>
                            <input id="whatsapp" name="whatsapp" type="text" class="form-input w-full border-slate-200 rounded-lg focus:ring-blue-500 focus:border-blue-500" value="{{ old('whatsapp', $tenant->whatsapp) }}" required>
                        </div>

                        <div>
                            <label for="package" class="block text-sm font-medium mb-1 text-slate-700">Paket <span class="text-red-500">*</span></label>
                            <select id="package" name="package" class="form-select w-full border-slate-200 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                                <option value="trial" {{ old('package', $tenant->package) == 'trial' ? 'selected' : '' }}>Trial (Uji Coba)</option>
                                <option value="monthly" {{ old('package', $tenant->package) == 'monthly' ? 'selected' : '' }}>Monthly (Bulanan)</option>
                                <option value="yearly" {{ old('package', $tenant->package) == 'yearly' ? 'selected' : '' }}>Yearly (Tahunan)</option>
                            </select>
                        </div>

                        <div>
                            <label for="status" class="block text-sm font-medium mb-1 text-slate-700">Status Akun <span class="text-red-500">*</span></label>
                            <select id="status" name="status" class="form-select w-full border-slate-200 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                                <option value="active" {{ old('status', $tenant->status) == 'active' ? 'selected' : '' }}>Active</option>
                                <option value="inactive" {{ old('status', $tenant->status) == 'inactive' ? 'selected' : '' }}>Inactive</option>
                                <option value="suspended" {{ old('status', $tenant->status) == 'suspended' ? 'selected' : '' }}>Suspended</option>
                            </select>
                            <p class="text-[11px] text-slate-400 mt-1">Ubah ke <b>Suspended</b> untuk memblokir akses login toko.</p>
                        </div>

                        <div class="md:col-span-2">
                            <label for="expired_at" class="block text-sm font-medium mb-1 text-slate-700">Masa Aktif (Expired At) <span class="text-red-500">*</span></label>
                            <input id="expired_at" name="expired_at" type="datetime-local" class="form-input w-full md:w-1/2 border-slate-200 rounded-lg focus:ring-blue-500 focus:border-blue-500" value="{{ old('expired_at', \Carbon\Carbon::parse($tenant->expired_at)->format('Y-m-d\TH:i')) }}" required>
                        </div>
                    </div>

                </div>

                <div class="px-6 py-4 border-t border-slate-200 bg-slate-50 flex justify-end gap-3">
                    <a href="{{ route('tenants.index') }}" class="btn bg-white border-slate-200 hover:border-slate-300 text-slate-600 px-4 py-2 rounded-lg font-medium transition">
                        Batal
                    </a>
                    <button type="submit" class="btn bg-blue-500 hover:bg-blue-600 text-white px-6 py-2 rounded-lg font-medium transition flex items-center">
                        <i class="fas fa-save mr-2"></i> Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>

    </div>
@endsection