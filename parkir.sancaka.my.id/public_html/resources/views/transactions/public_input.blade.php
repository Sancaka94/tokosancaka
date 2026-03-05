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
<body class="min-h-screen flex flex-col items-center justify-center p-4">

    <div class="w-full max-w-2xl bg-white rounded-3xl shadow-2xl overflow-hidden p-6 md:p-10 border-t-8 border-blue-600">

        <div class="text-center mb-8">
            <h1 class="text-3xl md:text-4xl font-black text-gray-800 mb-2">SELAMAT DATANG</h1>
            <p class="text-gray-500 font-bold text-sm md:text-lg">SILAKAN MASUKKAN PLAT ATAU PILIH KATEGORI</p>
        </div>

        @if(session('success'))
            <div class="bg-green-100 border-l-8 border-green-500 text-green-700 p-5 rounded-lg mb-8 font-black text-center text-xl shadow-inner">
                {{ session('success') }}
            </div>
        @endif

        @if($errors->any())
            <div class="bg-red-100 border-l-8 border-red-500 text-red-700 p-5 rounded-lg mb-8 font-bold text-center">
                Mohon masukkan Plat Nomor dengan benar!
            </div>
        @endif

        <form action="{{ route('transactions.public.store') }}" method="POST" class="w-full bg-blue-50/50 p-6 rounded-2xl border-2 border-blue-100">
            @csrf
            <input type="hidden" name="kategori" value="umum">

            <div class="text-center mb-4">
                <h3 class="font-black text-gray-700 text-lg md:text-xl">CUSTOMER UMUM</h3>
            </div>

            <div class="mb-4">
                <input type="text"
                       name="plate_number"
                       class="w-full text-center text-4xl font-black uppercase tracking-widest py-5 border-4 border-gray-300 rounded-xl focus:outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-100 transition-all placeholder-gray-300"
                       placeholder="PLAT NOMOR"
                       autocomplete="off"
                       autofocus
                       oninput="this.value = this.value.toUpperCase()">
            </div>

            <button type="submit" class="w-full bg-gray-800 hover:bg-gray-900 active:bg-black text-white font-black text-xl md:text-2xl py-4 rounded-xl shadow-lg transition-transform active:scale-95 flex justify-center items-center gap-3">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                CETAK TIKET UMUM
            </button>
        </form>

        <div class="relative flex py-6 items-center">
            <div class="flex-grow border-t-2 border-gray-200"></div>
            <span class="flex-shrink-0 mx-4 text-gray-400 font-bold text-xs md:text-sm">ATAU KATEGORI KHUSUS</span>
            <div class="flex-grow border-t-2 border-gray-200"></div>
        </div>

        <div class="grid grid-cols-2 gap-3 md:gap-6">
            <form action="{{ route('transactions.public.store') }}" method="POST" class="w-full">
                @csrf
                <input type="hidden" name="kategori" value="sepeda_listrik">
                <button type="submit" class="w-full h-full min-h-[100px] md:min-h-[120px] bg-green-500 hover:bg-green-600 active:bg-green-700 text-white font-black text-sm md:text-xl rounded-xl md:rounded-2xl shadow-lg transition-transform active:scale-95 flex flex-col justify-center items-center gap-1 p-2 md:p-4 text-center leading-tight">
                    <span class="text-3xl md:text-4xl">🚲</span>
                    <span>SEPEDA<br class="md:hidden"> LISTRIK</span>
                </button>
            </form>

            <form action="{{ route('transactions.public.store') }}" method="POST" class="w-full">
                @csrf
                <input type="hidden" name="kategori" value="pegawai_rsud">
                <button type="submit" class="w-full h-full min-h-[100px] md:min-h-[120px] bg-blue-500 hover:bg-blue-600 active:bg-blue-700 text-white font-black text-sm md:text-xl rounded-xl md:rounded-2xl shadow-lg transition-transform active:scale-95 flex flex-col justify-center items-center gap-1 p-2 md:p-4 text-center leading-tight">
                    <span class="text-3xl md:text-4xl">🏥</span>
                    <span>PEGAWAI<br class="md:hidden"> RSUD</span>
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
