@extends('layouts.app')

@section('title', 'Manajemen Kupon')

@section('content')
<div x-data="couponHandler()">
    
    <div class="mb-8 flex flex-col sm:flex-row sm:items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-black text-slate-800 uppercase tracking-tight">Manajemen Kupon</h1>
            <p class="text-sm font-medium text-slate-500 mt-1">Buat kode promo diskon Persen (%) atau Potongan Harga (Rp).</p>
        </div>
        <button @click="openModal('create')" 
                class="bg-blue-600 text-white px-5 py-2.5 rounded-xl font-bold text-sm shadow-lg shadow-blue-200 hover:bg-blue-700 transition flex items-center gap-2">
            <i class="fas fa-plus"></i> Buat Kupon Baru
        </button>
    </div>

    <div class="bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left whitespace-nowrap">
                <thead class="bg-slate-50 border-b border-slate-100">
                    <tr>
                        <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Kode Kupon</th>
                        <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Tipe & Nilai</th>
                        <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Min. Belanja</th>
                        <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Masa Berlaku</th>
                        <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Penggunaan</th>
                        <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50 text-sm">
                    @forelse($coupons as $coupon)
                    <tr class="hover:bg-slate-50 transition">
                        <td class="px-6 py-4">
                            <span class="font-mono font-bold bg-slate-100 text-slate-700 px-2 py-1 rounded border border-slate-200 select-all">
                                {{ $coupon->code }}
                            </span>
                            @if(!$coupon->is_active)
                                <span class="ml-2 text-[9px] bg-red-100 text-red-600 px-1.5 py-0.5 rounded font-bold uppercase">Non-Aktif</span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            @if($coupon->type == 'percent')
                                <div class="flex items-center gap-2 text-blue-600 font-black">
                                    <i class="fas fa-percent"></i> {{ $coupon->value }}%
                                </div>
                            @else
                                <div class="flex items-center gap-2 text-emerald-600 font-black">
                                    <span class="text-xs">Rp</span> {{ number_format($coupon->value, 0, ',', '.') }}
                                </div>
                            @endif
                        </td>
                        <td class="px-6 py-4 font-medium text-slate-600">
                            Rp {{ number_format($coupon->min_order_amount, 0, ',', '.') }}
                        </td>
                        <td class="px-6 py-4">
                            @if($coupon->expiry_date)
                                <div class="flex flex-col">
                                    <span class="font-bold text-slate-700">{{ $coupon->expiry_date->format('d M Y') }}</span>
                                    <span class="text-[10px] text-slate-400">
                                        {{ $coupon->expiry_date->diffForHumans() }}
                                    </span>
                                </div>
                            @else
                                <span class="text-emerald-500 font-bold text-xs">Selamanya</span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex flex-col">
                                <span class="font-bold text-slate-700">{{ $coupon->used_count }}x Dipakai</span>
                                @if($coupon->usage_limit)
                                    <span class="text-[10px] text-slate-400">Batas: {{ $coupon->usage_limit }}</span>
                                @else
                                    <span class="text-[10px] text-slate-400">Tanpa Batas</span>
                                @endif
                            </div>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <div class="flex items-center justify-center gap-2">
                                <button @click="editCoupon({{ $coupon }})" class="h-8 w-8 rounded-full bg-white border border-slate-200 text-slate-400 hover:text-amber-500 hover:border-amber-200 transition flex items-center justify-center">
                                    <i class="fas fa-pencil-alt text-xs"></i>
                                </button>
                                <form action="{{ route('coupons.destroy', $coupon->id) }}" method="POST" onsubmit="return confirm('Hapus kupon ini selamanya?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="h-8 w-8 rounded-full bg-white border border-slate-200 text-slate-400 hover:text-red-500 hover:border-red-200 transition flex items-center justify-center">
                                        <i class="fas fa-trash text-xs"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center text-slate-400 italic">Belum ada kupon promo yang dibuat.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4 bg-slate-50 border-t border-slate-100">
            {{ $coupons->links() }}
        </div>
    </div>

    <div x-show="isModalOpen" style="display: none;" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/60 backdrop-blur-sm transition-opacity" @click="isModalOpen = false"></div>

        <div class="relative bg-white rounded-3xl shadow-2xl w-full max-w-md overflow-hidden transform transition-all">
            <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center bg-slate-50">
                <h3 class="font-black text-lg text-slate-800" x-text="modalTitle"></h3>
                <button @click="isModalOpen = false" class="text-slate-400 hover:text-red-500"><i class="fas fa-times"></i></button>
            </div>

            <form :action="formAction" method="POST" class="p-6 space-y-4">
                @csrf
                <input type="hidden" name="_method" :value="formMethod">

                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2">Kode Kupon</label>
                    <div class="flex gap-2">
                        <input type="text" name="code" x-model="formData.code" required placeholder="Contoh: MERDEKA45" 
                               class="flex-1 px-4 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-blue-500 uppercase font-bold text-slate-700">
                        <button type="button" @click="generateCode()" class="px-3 bg-slate-100 rounded-xl text-slate-500 hover:bg-slate-200 text-xs font-bold">
                            <i class="fas fa-random"></i> Auto
                        </button>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2">Tipe Diskon</label>
                    <div class="grid grid-cols-2 gap-3">
                        <div @click="formData.type = 'fixed'" 
                             class="cursor-pointer border-2 rounded-xl p-3 flex items-center justify-center gap-2 transition"
                             :class="formData.type === 'fixed' ? 'border-emerald-500 bg-emerald-50 text-emerald-700' : 'border-slate-100 bg-white text-slate-400'">
                            <i class="fas fa-money-bill-wave"></i> <span class="font-bold text-sm">Rupiah (Rp)</span>
                        </div>
                        <div @click="formData.type = 'percent'" 
                             class="cursor-pointer border-2 rounded-xl p-3 flex items-center justify-center gap-2 transition"
                             :class="formData.type === 'percent' ? 'border-blue-500 bg-blue-50 text-blue-700' : 'border-slate-100 bg-white text-slate-400'">
                            <i class="fas fa-percent"></i> <span class="font-bold text-sm">Persen (%)</span>
                        </div>
                    </div>
                    <input type="hidden" name="type" x-model="formData.type">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2">Besar Diskon</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                            <span x-show="formData.type === 'fixed'" class="font-bold text-emerald-600">Rp</span>
                            <span x-show="formData.type === 'percent'" class="font-bold text-blue-600">%</span>
                        </div>
                        <input type="number" name="value" x-model="formData.value" required placeholder="0" 
                               class="w-full pl-12 pr-4 py-3 text-lg font-black text-slate-800 rounded-xl border border-slate-200 focus:ring-2 transition"
                               :class="formData.type === 'fixed' ? 'focus:ring-emerald-500' : 'focus:ring-blue-500'">
                    </div>
                    <p x-show="formData.type === 'percent'" class="text-[10px] text-slate-400 mt-1">*Maksimal 100%</p>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Min. Belanja (Rp)</label>
                        <input type="number" name="min_order_amount" x-model="formData.min_order_amount" placeholder="0 = Tanpa min." 
                               class="w-full px-3 py-2 rounded-lg border border-slate-200 text-sm">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Batas Pemakaian</label>
                        <input type="number" name="usage_limit" x-model="formData.usage_limit" placeholder="Kosong = Unlimited" 
                               class="w-full px-3 py-2 rounded-lg border border-slate-200 text-sm">
                    </div>
                </div>

                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Berlaku Sampai</label>
                    <input type="date" name="expiry_date" x-model="formData.expiry_date" 
                           class="w-full px-3 py-2 rounded-lg border border-slate-200 text-sm font-bold text-slate-700">
                    <p class="text-[9px] text-slate-400 mt-1">*Kosongkan jika berlaku selamanya</p>
                </div>

                <button type="submit" class="w-full bg-slate-900 text-white py-3 rounded-xl font-bold hover:bg-black transition shadow-lg">
                    Simpan Kupon
                </button>
            </form>
        </div>
    </div>
</div>

<script>
    function couponHandler() {
        return {
            isModalOpen: false,
            modalTitle: 'Buat Kupon Baru',
            formAction: "{{ route('coupons.store') }}",
            formMethod: 'POST',
            formData: {
                code: '',
                type: 'fixed', // Default Rupiah
                value: '',
                min_order_amount: '',
                usage_limit: '',
                expiry_date: ''
            },

            openModal(mode) {
                this.isModalOpen = true;
                if(mode === 'create') {
                    this.resetForm();
                }
            },

            resetForm() {
                this.modalTitle = 'Buat Kupon Baru';
                this.formAction = "{{ route('coupons.store') }}";
                this.formMethod = 'POST';
                this.formData = { code: '', type: 'fixed', value: '', min_order_amount: '', usage_limit: '', expiry_date: '' };
            },

            editCoupon(coupon) {
                this.isModalOpen = true;
                this.modalTitle = 'Edit Kupon: ' + coupon.code;
                // PERBAIKAN: Gunakan helper route Laravel agar subfolder terbawa otomatis
                this.formAction = "{{ route('coupons.index') }}/" + coupon.id;
                this.formMethod = 'PUT'; // Method Spoofing Laravel
                
                // Format tanggal agar bisa masuk ke input type="date"
                let dateStr = '';
                if(coupon.expiry_date) {
                    dateStr = new Date(coupon.expiry_date).toISOString().split('T')[0];
                }

                this.formData = {
                    code: coupon.code,
                    type: coupon.type,
                    value: coupon.value,
                    min_order_amount: coupon.min_order_amount,
                    usage_limit: coupon.usage_limit,
                    expiry_date: dateStr
                };
            },

            generateCode() {
                const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
                let result = 'PROMO-';
                for (let i = 0; i < 5; i++) {
                    result += chars.charAt(Math.floor(Math.random() * chars.length));
                }
                this.formData.code = result;
            }
        }
    }
</script>
@endsection