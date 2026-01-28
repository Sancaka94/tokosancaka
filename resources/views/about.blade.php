@extends('layouts.app')

@section('title', 'Tentang Kami - CV. Sancaka Karya Hutama')

@section('content')
<div class="bg-gray-50 min-h-screen font-sans">

    {{-- 1. HERO SECTION --}}
    <div class="relative bg-gradient-to-r from-indigo-900 to-blue-800 text-white overflow-hidden">
        <div class="absolute inset-0">
            <img src="https://images.unsplash.com/photo-1497366216548-37526070297c?ixlib=rb-1.2.1&auto=format&fit=crop&w=1950&q=80" alt="Office Building" class="w-full h-full object-cover opacity-10">
        </div>
        <div class="relative max-w-7xl mx-auto py-20 px-4 sm:px-6 lg:px-8 flex flex-col items-center text-center">
            <span class="bg-indigo-800 bg-opacity-50 text-indigo-100 py-1 px-3 rounded-full text-sm font-semibold mb-4 border border-indigo-500">
                Terpercaya & Profesional
            </span>
            <h1 class="text-3xl font-extrabold tracking-tight sm:text-5xl lg:text-6xl mb-4">
                CV. SANCAKA KARYA HUTAMA
            </h1>
            <p class="text-xl max-w-3xl mx-auto text-indigo-100 font-light">
                Mitra Solusi Terbaik untuk Bisnis dan Kebutuhan Anda. Bergerak di bidang jual beli barang jasa, pengiriman, perizinan, hingga digital marketing.
            </p>
        </div>
    </div>

    {{-- 2. INTRODUCTION & VISI MISI --}}
    <div class="py-16 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            {{-- Intro --}}
            <div class="text-center mb-16 max-w-4xl mx-auto">
                <h2 class="text-base text-indigo-600 font-bold tracking-wide uppercase">Tentang Kami</h2>
                <p class="mt-4 text-lg text-gray-600 leading-relaxed">
                    Selamat datang di <b>CV. SANCAKA KARYA HUTAMA</b>, perusahaan terpercaya yang bergerak di bidang jual beli barang dan jasa. Kami hadir untuk memberikan solusi komprehensif dalam berbagai kebutuhan Anda, termasuk jasa pengiriman, desain grafis, pemasaran digital, percetakan, hingga layanan profesional lainnya.
                </p>
            </div>

            {{-- Grid Visi Misi --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-10 items-start">
                <div class="bg-indigo-50 p-8 rounded-2xl border-l-4 border-indigo-600 shadow-sm hover:shadow-md transition">
                    <div class="flex items-center mb-4">
                        <div class="bg-indigo-600 p-3 rounded-lg text-white mr-4">
                            <i class="fas fa-eye text-2xl"></i>
                        </div>
                        <h3 class="text-2xl font-bold text-gray-900">Visi Kami</h3>
                    </div>
                    <p class="text-gray-700 text-lg leading-relaxed italic">
                        "Menjadi perusahaan terkemuka di bidang jual beli barang dan jasa di Indonesia, yang dikenal karena inovasi, kepercayaan, dan layanan terbaik untuk memenuhi kebutuhan pelanggan."
                    </p>
                </div>

                <div>
                    <h3 class="text-2xl font-bold text-gray-900 mb-6 flex items-center">
                        <i class="fas fa-bullseye text-indigo-600 mr-3"></i> Misi Kami
                    </h3>
                    <ul class="space-y-4">
                        <li class="flex items-start">
                            <i class="fas fa-check-circle text-green-500 mt-1 mr-3 flex-shrink-0"></i>
                            <span class="text-gray-600">Memberikan layanan berkualitas tinggi dengan mengutamakan profesionalisme.</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-check-circle text-green-500 mt-1 mr-3 flex-shrink-0"></i>
                            <span class="text-gray-600">Menghadirkan solusi pengiriman barang aman, cepat, dan bergaransi.</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-check-circle text-green-500 mt-1 mr-3 flex-shrink-0"></i>
                            <span class="text-gray-600">Mendukung kebutuhan digital melalui desain grafis & website inovatif.</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-check-circle text-green-500 mt-1 mr-3 flex-shrink-0"></i>
                            <span class="text-gray-600">Menyediakan layanan perizinan (PBG, SLF, IMB) yang cepat & transparan.</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-check-circle text-green-500 mt-1 mr-3 flex-shrink-0"></i>
                            <span class="text-gray-600">Menjadi mitra andal dalam percetakan berkualitas di Ngawi.</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    {{-- 3. LAYANAN KAMI (Expanded) --}}
    <div class="py-16 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <h2 class="text-3xl font-extrabold text-gray-900">Layanan Unggulan</h2>
                <p class="mt-4 text-gray-500">Solusi lengkap untuk kebutuhan pribadi dan bisnis Anda.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">

                <div class="bg-white rounded-xl shadow-md p-6 border-t-4 border-blue-500 hover:-translate-y-1 transition duration-300">
                    <div class="w-14 h-14 bg-blue-100 rounded-full flex items-center justify-center text-blue-600 text-2xl mb-4">
                        <i class="fas fa-handshake"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-2">Jual Beli Barang & Jasa</h3>
                    <p class="text-gray-600 text-sm">Menyediakan beragam barang berkualitas tinggi serta layanan profesional dengan hasil terbaik untuk kepuasan Anda.</p>
                </div>

                <div class="bg-white rounded-xl shadow-md p-6 border-t-4 border-red-500 hover:-translate-y-1 transition duration-300">
                    <div class="w-14 h-14 bg-red-100 rounded-full flex items-center justify-center text-red-600 text-2xl mb-4">
                        <i class="fas fa-truck-fast"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-2">Jasa Pengiriman & Cargo</h3>
                    <p class="text-gray-600 text-sm mb-3">Pengiriman cepat, murah, amanah & bergaransi. Melayani paket reguler, kargo, pindahan rumah/kos, hingga kirim motor.</p>
                    <div class="flex flex-wrap gap-1">
                        @foreach(['JNE','J&T','SiCepat','POS','TiKi','Ninja','Lion','Wahana'] as $exp)
                            <span class="text-xs bg-gray-100 text-gray-600 px-2 py-1 rounded">{{ $exp }}</span>
                        @endforeach
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-md p-6 border-t-4 border-green-500 hover:-translate-y-1 transition duration-300">
                    <div class="w-14 h-14 bg-green-100 rounded-full flex items-center justify-center text-green-600 text-2xl mb-4">
                        <i class="fas fa-file-contract"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-2">Jasa Perizinan Bangunan</h3>
                    <ul class="text-sm text-gray-600 space-y-1 list-disc list-inside">
                        <li><b>PBG</b> (Persetujuan Bangunan Gedung)</li>
                        <li><b>SLF</b> (Sertifikat Laik Fungsi)</li>
                        <li><b>IMB</b> (Izin Mendirikan Bangunan)</li>
                    </ul>
                    <p class="text-xs text-gray-500 mt-2">Proses cepat, efisien, dan transparan.</p>
                </div>

                <div class="bg-white rounded-xl shadow-md p-6 border-t-4 border-purple-500 hover:-translate-y-1 transition duration-300">
                    <div class="w-14 h-14 bg-purple-100 rounded-full flex items-center justify-center text-purple-600 text-2xl mb-4">
                        <i class="fas fa-laptop-code"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-2">Desain & Digital Marketing</h3>
                    <p class="text-gray-600 text-sm">Layanan desain grafis kreatif untuk branding, pembuatan website profesional, dan strategi pemasaran digital yang efektif.</p>
                </div>

                <div class="bg-white rounded-xl shadow-md p-6 border-t-4 border-yellow-500 hover:-translate-y-1 transition duration-300">
                    <div class="w-14 h-14 bg-yellow-100 rounded-full flex items-center justify-center text-yellow-600 text-2xl mb-4">
                        <i class="fas fa-print"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-2">Percetakan & Printing</h3>
                    <p class="text-gray-600 text-sm">Layanan percetakan, printing, dan print copy dengan kualitas tinggi di wilayah Ngawi dan sekitarnya.</p>
                </div>

                <div class="bg-indigo-600 rounded-xl shadow-md p-6 text-white hover:-translate-y-1 transition duration-300 flex flex-col justify-center items-center text-center">
                    <i class="fas fa-headset text-4xl mb-4 opacity-80"></i>
                    <h3 class="text-xl font-bold mb-2">Butuh Bantuan?</h3>
                    <p class="text-indigo-100 text-sm mb-4">Tim kami siap membantu kebutuhan bisnis Anda kapan saja.</p>
                    <a href="https://wa.me/6285745808809" target="_blank" class="bg-white text-indigo-700 font-bold py-2 px-6 rounded-full hover:bg-gray-100 transition">
                        Hubungi WhatsApp
                    </a>
                </div>

            </div>
        </div>
    </div>

    {{-- 4. MITRA EKSPEDISI BAR --}}
    <div class="bg-white py-10 border-y border-gray-200">
        <div class="max-w-7xl mx-auto px-4 text-center">
            <p class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-6">Bekerja sama dengan Ekspedisi Terpercaya</p>
            <div class="flex flex-wrap justify-center gap-4 md:gap-8 opacity-70 grayscale hover:grayscale-0 transition-all duration-500">
                @php
                    $partners = ['JNE', 'TIKI', 'POS Indonesia', 'SiCepat', 'J&T Express', 'Ninja Xpress', 'Lion Parcel', 'Wahana', 'ID Express', 'Dakota Cargo', 'Indah Cargo', 'SAP Express'];
                @endphp
                @foreach($partners as $partner)
                    <span class="px-4 py-2 bg-gray-100 rounded-lg font-bold text-gray-700 border border-gray-300 shadow-sm">{{ $partner }}</span>
                @endforeach
            </div>
        </div>
    </div>

    {{-- 5. KONTAK & FOOTER INFO --}}
    <div class="bg-gray-900 text-white py-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-12 items-center">

                <div>
                    <h2 class="text-3xl font-extrabold mb-6">Hubungi Kami</h2>
                    <p class="text-gray-400 mb-8 text-lg">
                        Kami selalu siap membantu kebutuhan Anda. Untuk informasi lebih lanjut mengenai layanan kami, silakan hubungi kontak di bawah ini.
                    </p>

                    <div class="space-y-6">
                        <div class="flex items-start">
                            <i class="fas fa-map-marked-alt text-indigo-400 text-2xl mt-1 mr-4"></i>
                            <div>
                                <h4 class="font-bold text-lg">Alamat Kantor</h4>
                                <p class="text-gray-300 leading-relaxed">
                                    Jl. Dr. Wahidin No.18A RT.22 RW.05,<br>
                                    Kel. Ketanggi, Kec. Ngawi,<br>
                                    Kab. Ngawi, Jawa Timur 63211
                                </p>
                            </div>
                        </div>

                        <div class="flex items-center">
                            <i class="fab fa-whatsapp text-green-400 text-2xl mr-4"></i>
                            <div>
                                <h4 class="font-bold text-lg">WhatsApp</h4>
                                <p class="text-gray-300 font-mono text-lg">0857-4580-8809</p>
                            </div>
                        </div>

                        <div class="flex items-center">
                            <i class="fas fa-globe text-blue-400 text-2xl mr-4"></i>
                            <div>
                                <h4 class="font-bold text-lg">Website</h4>
                                <a href="https://sancaka.bisnis.pro" target="_blank" class="text-indigo-300 hover:text-white underline decoration-dotted">
                                    sancaka.bisnis.pro
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="relative h-80 w-full rounded-2xl overflow-hidden shadow-2xl border-4 border-gray-700">
                    <iframe
                        class="absolute inset-0 w-full h-full"
                        src="https://www.google.com/maps?q=Jl.+Dr.+Wahidin+No.18A,+Ngawi,+Jawa+Timur&output=embed"
                        frameborder="0"
                        style="border:0;"
                        allowfullscreen=""
                        aria-hidden="false"
                        tabindex="0">
                    </iframe>
                </div>

            </div>
        </div>
    </div>

</div>
@endsection
