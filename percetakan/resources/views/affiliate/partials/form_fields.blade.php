<div class="space-y-4">
    <div>
        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Nama Lengkap</label>
        <div class="relative">
            <i class="fas fa-user absolute left-4 top-3.5 text-slate-400"></i>
            <input type="text" name="name" required class="w-full pl-10 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-red-500 outline-none">
        </div>
    </div>

    <div>
        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Nomor WhatsApp</label>
        <div class="relative">
            <i class="fab fa-whatsapp absolute left-4 top-3.5 text-slate-400 text-lg"></i>
            <input type="number" name="whatsapp" required class="w-full pl-10 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-red-500 outline-none">
        </div>
    </div>

    <div>
        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">PIN Keamanan (6 Digit)</label>
        <div class="relative">
            <i class="fas fa-lock absolute left-4 top-3.5 text-slate-400"></i>
            <input type="password" name="pin" required maxlength="6" class="w-full pl-10 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-red-500 outline-none text-center tracking-widest text-lg" placeholder="******">
        </div>
        <p class="text-[10px] text-slate-400 mt-1">*Ingat PIN ini untuk mengedit data nanti.</p>
    </div>

    <div>
        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Alamat</label>
        <textarea name="address" required rows="2" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-red-500 outline-none"></textarea>
    </div>

    <div class="grid grid-cols-2 gap-3">
        <div>
            <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Bank</label>
            <input type="text" name="bank_name" required class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-red-500 outline-none" placeholder="BCA/DANA">
        </div>
        <div>
            <label class="block text-xs font-bold text-slate-500 uppercase mb-1">No. Rekening</label>
            <input type="number" name="bank_account_number" required class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-red-500 outline-none">
        </div>
    </div>
</div>