<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            {{ __('Informasi Profil & Alamat') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            {{ __("Update informasi akun, foto profil, dan alamat pengiriman Anda.") }}
        </p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    {{-- ========================================================================= --}}
    {{-- BAGIAN 1: PROFIL DASAR (Nama, Email, Foto, WA) --}}
    {{-- ========================================================================= --}}
    <form method="post" action="{{ route('profile.update') }}" class="mt-6 space-y-6" enctype="multipart/form-data">
        @csrf
        @method('patch')

        {{-- 1. FOTO PROFIL --}}
        <div x-data="{ photoName: null, photoPreview: null }" class="col-span-6 sm:col-span-4">
            <input type="file" class="hidden" x-ref="photo" name="logo"
                        x-on:change="
                                photoName = $refs.photo.files[0].name;
                                const reader = new FileReader();
                                reader.onload = (e) => { photoPreview = e.target.result; };
                                reader.readAsDataURL($refs.photo.files[0]);
                        " />

            <x-input-label for="photo" :value="__('Foto Profil')" />

            <div class="mt-2" x-show="! photoPreview">
                @if($user->logo)
                    <img src="{{ asset('storage/' . $user->logo) }}" class="rounded-full h-20 w-20 object-cover border border-gray-200 shadow-sm">
                @else
                    <img src="https://ui-avatars.com/api/?name={{ urlencode($user->name) }}&color=7F9CF5&background=EBF4FF" class="rounded-full h-20 w-20 object-cover border border-gray-200 shadow-sm">
                @endif
            </div>

            <div class="mt-2" x-show="photoPreview" style="display: none;">
                <span class="block rounded-full w-20 h-20 bg-cover bg-no-repeat bg-center border border-gray-200 shadow-sm"
                      x-bind:style="'background-image: url(\'' + photoPreview + '\');'">
                </span>
            </div>

            <button type="button" class="mt-2 inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none transition ease-in-out duration-150" x-on:click.prevent="$refs.photo.click()">
                {{ __('Pilih Foto Baru') }}
            </button>
            <x-input-error class="mt-2" :messages="$errors->get('logo')" />
        </div>

        {{-- 2. NAMA --}}
        <div>
            <x-input-label for="name" :value="__('Nama Lengkap')" />
            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $user->name)" required autocomplete="name" />
            <x-input-error class="mt-2" :messages="$errors->get('name')" />
        </div>

        {{-- 3. EMAIL --}}
        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $user->email)" required autocomplete="username" />
            <x-input-error class="mt-2" :messages="$errors->get('email')" />

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div>
                    <p class="text-sm mt-2 text-gray-800">
                        {{ __('Alamat email Anda belum diverifikasi.') }}
                        <button form="send-verification" class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            {{ __('Klik di sini untuk mengirim ulang.') }}
                        </button>
                    </p>
                </div>
            @endif
        </div>

        {{-- 4. WHATSAPP --}}
        <div>
            <x-input-label for="phone" :value="__('No. WhatsApp')" />
            <x-text-input id="phone" name="phone" type="text" class="mt-1 block w-full" :value="old('phone', $user->phone)" placeholder="08xxxxxxxxxx" />
            <x-input-error class="mt-2" :messages="$errors->get('phone')" />
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('Simpan Profil Dasar') }}</x-primary-button>
            @if (session('status') === 'profile-updated')
                <p x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 2000)" class="text-sm text-green-600">{{ __('Tersimpan.') }}</p>
            @endif
        </div>
    </form>

    <hr class="my-8 border-gray-200">

    {{-- ========================================================================= --}}
    {{-- BAGIAN 2: ALAMAT LENGKAP & KOORDINAT (FORM BARU) --}}
    {{-- ========================================================================= --}}
    <header class="mb-4">
        <h2 class="text-lg font-medium text-gray-900">
            {{ __('Alamat Pengiriman') }}
        </h2>
        <p class="mt-1 text-sm text-gray-600">
            {{ __("Data ini digunakan untuk pengiriman barang (COD/Saldo) dan pengecekan ongkir.") }}
        </p>
    </header>

    {{-- Form Address mengarah ke route 'profile.address.update' --}}
    <form method="post" action="{{ route('profile.address.update') }}" class="space-y-6">
        @csrf
        @method('patch')

        {{-- 1. ALAMAT DETAIL --}}
        <div>
            <x-input-label for="address_detail" :value="__('Detail Alamat (Jalan, No. Rumah, RT/RW)')" />
            <textarea id="address_detail" name="address_detail" rows="3"
                class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                required>{{ old('address_detail', $user->address_detail) }}</textarea>
            <x-input-error class="mt-2" :messages="$errors->get('address_detail')" />
        </div>

        {{-- 2. AUTOCOMPLETE PENCARIAN WILAYAH --}}
        <div x-data="addressSearch()" class="relative">
            <x-input-label for="search_location" :value="__('Cari Kelurahan / Kecamatan')" />

            <div class="relative">
                <input type="text"
                    id="search_location"
                    class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm pl-10"
                    placeholder="Ketik nama kelurahan..."
                    x-model="query"
                    @input.debounce.500ms="search()"
                />
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <i class="fas fa-search text-gray-400"></i>
                </div>
            </div>

            <ul x-show="results.length > 0"
                class="absolute z-50 w-full bg-white border border-gray-300 rounded-md shadow-lg mt-1 max-h-60 overflow-y-auto"
                style="display: none;"
                @click.outside="results = []">
                <template x-for="item in results" :key="item.id">
                    <li @click="selectAddress(item)"
                        class="px-4 py-2 hover:bg-indigo-50 cursor-pointer text-sm text-gray-700 border-b last:border-b-0 flex justify-between items-center">
                        <div>
                            <span class="font-bold block" x-text="item.text"></span>
                            <span class="text-xs text-gray-500" x-text="item.zip_code"></span>
                        </div>
                        <i class="fas fa-chevron-right text-xs text-gray-400"></i>
                    </li>
                </template>
            </ul>
            <p x-show="loading" class="text-xs text-blue-500 mt-1 animate-pulse">Sedang mencari data...</p>
        </div>

        {{-- 3. HASIL DATA WILAYAH (READONLY) --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 bg-slate-50 p-4 rounded-xl border border-slate-200">
            <div>
                <x-input-label :value="__('Provinsi')" class="text-xs uppercase text-slate-500" />
                <input type="text" name="province" x-model="selected.province" class="w-full bg-transparent border-0 border-b border-slate-300 focus:ring-0 p-0 text-sm font-semibold text-slate-700" readonly />
            </div>
            <div>
                <x-input-label :value="__('Kota/Kabupaten')" class="text-xs uppercase text-slate-500" />
                <input type="text" name="regency" x-model="selected.regency" class="w-full bg-transparent border-0 border-b border-slate-300 focus:ring-0 p-0 text-sm font-semibold text-slate-700" readonly />
            </div>
            <div>
                <x-input-label :value="__('Kecamatan')" class="text-xs uppercase text-slate-500" />
                <input type="text" name="district" x-model="selected.district" class="w-full bg-transparent border-0 border-b border-slate-300 focus:ring-0 p-0 text-sm font-semibold text-slate-700" readonly />
            </div>
            <div>
                <x-input-label :value="__('Kelurahan')" class="text-xs uppercase text-slate-500" />
                <input type="text" name="village" x-model="selected.village" class="w-full bg-transparent border-0 border-b border-slate-300 focus:ring-0 p-0 text-sm font-semibold text-slate-700" readonly />
            </div>
            <div>
                <x-input-label :value="__('Kode Pos')" class="text-xs uppercase text-slate-500" />
                <input type="text" name="postal_code" x-model="selected.postal_code" class="w-full bg-transparent border-0 border-b border-slate-300 focus:ring-0 p-0 text-sm font-semibold text-slate-700" readonly />
            </div>
        </div>

        {{-- 4. DATA HIDDEN (ID WILAYAH & KOORDINAT) --}}
        <input type="hidden" name="district_id" :value="selected.district_id">
        <input type="hidden" name="subdistrict_id" :value="selected.subdistrict_id">
        <input type="hidden" name="latitude" :value="selected.lat">
        <input type="hidden" name="longitude" :value="selected.lng">

        {{-- INFO KOORDINAT --}}
        <div class="flex items-center gap-2 text-xs text-gray-500 bg-yellow-50 p-2 rounded border border-yellow-100">
            <i class="fas fa-info-circle text-yellow-600"></i>
            <span>Koordinat (Lat/Lng) akan otomatis dicari oleh sistem jika belum tersedia.</span>
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('Simpan Alamat') }}</x-primary-button>
            @if (session('status') === 'address-updated')
                <p x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 2000)" class="text-sm text-green-600">{{ __('Alamat Berhasil Disimpan.') }}</p>
            @endif
        </div>
    </form>

    {{-- SCRIPT ALPINE JS UNTUK PENCARIAN --}}
    <script>
        function addressSearch() {
            return {
                query: '',
                results: [],
                loading: false,
                // Data Awal dari Database
                selected: {
                    province: '{{ $user->province }}',
                    regency: '{{ $user->regency }}',
                    district: '{{ $user->district }}',
                    village: '{{ $user->village }}',
                    postal_code: '{{ $user->postal_code }}',
                    district_id: '{{ $user->district_id }}',
                    subdistrict_id: '{{ $user->subdistrict_id }}',
                    lat: '{{ $user->latitude }}',
                    lng: '{{ $user->longitude }}'
                },

                search() {
                    if (this.query.length < 3) { this.results = []; return; }
                    this.loading = true;

                    // Panggil API Search Address (Pastikan route ini ada dan bisa diakses user)
                    // Gunakan route 'api.search_address' atau sesuaikan dengan route Anda
                    fetch(`{{ route('profile.search_address') }}?search=${this.query}`)
                        .then(res => res.json())
                        .then(data => {
                            this.results = data;
                            this.loading = false;
                        })
                        .catch(() => {
                            this.loading = false;
                            this.results = [];
                        });
                },

                selectAddress(item) {
                    // Parsing Text dari API KiriminAja (Format: Kelurahan, Kecamatan, Kota, Provinsi)
                    const parts = item.text.split(',').map(s => s.trim());

                    this.selected.village = parts[0] || '';
                    this.selected.district = parts[1] || '';
                    this.selected.regency = parts[2] || '';
                    this.selected.province = parts[3] || '';
                    this.selected.postal_code = item.zip_code || '';

                    // Mapping ID untuk Ongkir
                    this.selected.district_id = item.kecamatan_id || 0;
                    this.selected.subdistrict_id = item.id || 0;

                    // Reset pencarian
                    this.query = '';
                    this.results = [];
                }
            }
        }
    </script>
</section>
