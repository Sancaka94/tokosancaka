<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mesin Tiket Parkir Mandiri</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { background-color: #e5e7eb; } /* Gray 200 */
    </style>
</head>
<body class="min-h-screen flex flex-col items-center justify-start pt-6 pb-4 px-3 md:pt-10 md:px-4">

    <div class="w-full max-w-3xl bg-white rounded-3xl shadow-2xl overflow-hidden p-5 md:p-10 border-t-8 border-blue-600">

        @if(session('success'))
            <div class="bg-green-100 border-l-8 border-green-500 text-green-700 p-4 md:p-5 rounded-lg mb-6 md:mb-8 font-black text-center text-lg md:text-xl shadow-inner">
                {{ session('success') }}
            </div>
        @endif

        @if($errors->any())
            <div class="bg-red-100 border-l-8 border-red-500 text-red-700 p-4 md:p-5 rounded-lg mb-6 md:mb-8 font-bold text-center text-sm md:text-base">
                Mohon masukkan Plat Nomor dengan benar!
            </div>
        @endif

        <form action="{{ route('transactions.public.store') }}" method="POST" class="w-full bg-blue-50/50 p-4 md:p-6 rounded-2xl border-2 border-blue-100">
            @csrf

            <div class="text-center mb-3 md:mb-4">
                <h3 class="font-black text-gray-700 text-base md:text-xl">CUSTOMER UMUM</h3>
            </div>

            <div class="mb-4">
                <input type="text"
                       name="plate_number"
                       id="plate_number"
                       inputmode="numeric"
                       pattern="[0-9]*"
                       class="w-full text-center text-3xl md:text-4xl font-black uppercase tracking-widest py-4 md:py-5 border-4 border-gray-300 rounded-xl focus:outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-100 transition-all placeholder-gray-300"
                       placeholder="PLAT NOMOR"
                       autocomplete="off"
                       autofocus
                       oninput="this.value = this.value.replace(/[^0-9]/g, '')">
            </div>

            <div class="flex gap-2 md:gap-3">
                <button type="submit" name="kategori" value="umum" class="w-1/2 bg-gray-800 hover:bg-gray-900 active:bg-black text-white font-black text-lg md:text-xl py-4 rounded-xl shadow-lg transition-transform active:scale-95 flex justify-center items-center gap-2 md:gap-3">
                    <svg class="w-5 h-5 md:w-8 md:h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                    CETAK TIKET
                </button>

                <button type="submit" name="kategori" value="tanpa_plat" onclick="document.getElementById('plate_number').value=''" class="w-1/4 bg-amber-500 hover:bg-amber-600 active:bg-amber-700 text-white font-black rounded-xl shadow-lg transition-transform active:scale-95 flex flex-col justify-center items-center py-2">
                    <span class="text-2xl md:text-3xl mb-1">🎫</span>
                    <span class="text-[10px] md:text-sm leading-tight text-center">CETAK<br class="md:hidden"> LANGSUNG</span>
                </button>

                <button type="submit" name="kategori" value="toilet" onclick="document.getElementById('plate_number').value=''" class="w-1/4 bg-cyan-600 hover:bg-cyan-700 active:bg-cyan-800 text-white font-black rounded-xl shadow-lg transition-transform active:scale-95 flex flex-col justify-center items-center py-2">
                    <span class="text-2xl md:text-3xl mb-1">🚽</span>
                    <span class="text-sm md:text-lg leading-none">TOILET</span>
                    <span class="text-[10px] md:text-sm mt-1 font-medium">Rp 2.000</span>
                </button>
            </div>
        </form>

        <div class="relative flex py-5 md:py-6 items-center">
            <div class="flex-grow border-t-2 border-gray-200"></div>
            <span class="flex-shrink-0 mx-4 text-gray-400 font-bold text-[10px] md:text-sm">ATAU KATEGORI KHUSUS</span>
            <div class="flex-grow border-t-2 border-gray-200"></div>
        </div>

        <div class="grid grid-cols-3 gap-2 md:gap-4">

            <form action="{{ route('transactions.public.store') }}" method="POST" class="w-full">
                @csrf
                <input type="hidden" name="kategori" value="sepeda">
                <button type="submit" class="w-full h-full min-h-[90px] md:min-h-[120px] bg-red-500 hover:bg-red-600 active:bg-red-700 text-white font-black rounded-xl md:rounded-2xl shadow-lg transition-transform active:scale-95 flex flex-col justify-center items-center gap-1 p-1 md:p-4 text-center">
                    <span class="text-2xl md:text-4xl mb-1">🚴</span>
                    <span class="text-[10px] md:text-lg leading-tight">SEPEDA<br class="md:hidden"> BIASA</span>
                </button>
            </form>

            <form action="{{ route('transactions.public.store') }}" method="POST" class="w-full">
                @csrf
                <input type="hidden" name="kategori" value="sepeda_listrik">
                <button type="submit" class="w-full h-full min-h-[90px] md:min-h-[120px] bg-green-500 hover:bg-green-600 active:bg-green-700 text-white font-black rounded-xl md:rounded-2xl shadow-lg transition-transform active:scale-95 flex flex-col justify-center items-center gap-1 p-1 md:p-4 text-center">
                    <span class="text-2xl md:text-4xl mb-1">🛵</span>
                    <span class="text-[10px] md:text-lg leading-tight">SEPEDA<br class="md:hidden"> LISTRIK</span>
                </button>
            </form>

            <form action="{{ route('transactions.public.store') }}" method="POST" class="w-full">
                @csrf
                <input type="hidden" name="kategori" value="pegawai_rsud">
                <button type="submit" class="w-full h-full min-h-[90px] md:min-h-[120px] bg-blue-500 hover:bg-blue-600 active:bg-blue-700 text-white font-black rounded-xl md:rounded-2xl shadow-lg transition-transform active:scale-95 flex flex-col justify-center items-center gap-1 p-1 md:p-4 text-center">
                    <span class="text-2xl md:text-4xl mb-1">🏥</span>
                    <span class="text-[10px] md:text-lg leading-tight">PEGAWAI<br class="md:hidden"> RSUD</span>
                </button>
            </form>

        </div>

    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            @if(session('print_id'))
                let printUrl = "{{ route('transactions.print', session('print_id')) }}";
                fetch(printUrl)
                    .then(response => {
                        if (!response.ok) throw new Error('Gagal memuat struk');
                        return response.text();
                    })
                    .then(textData => {
                        let encodedText = encodeURIComponent(textData);
                        let intentUrl = "intent:" + encodedText + "#Intent;scheme=rawbt;package=ru.a402d.rawbtprinter;end;";
                        window.location.href = intentUrl;
                    })
                    .catch(error => console.error('Gagal memicu RawBT:', error));
            @endif
        });
    </script>
</body>
</html>
