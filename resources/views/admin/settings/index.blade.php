@extends('layouts.admin')



@section('title', 'Pengaturan Marketplace')

@section('page-title', 'Pengaturan Marketplace')



@section('content')

<div class="container mx-auto px-4 sm:px-8 py-8 space-y-8">



    <div class="bg-white rounded-2xl shadow-lg p-6">

        <h1 class="text-2xl font-bold mb-6">Settings & Banners</h1>

        <form action="{{ route('admin.settings.update') }}" method="POST" enctype="multipart/form-data" class="space-y-6" id="settings-form">

            @csrf

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

                @foreach(['logo','banner_2','banner_3'] as $key)

                <div class="space-y-2">

                    <label class="block font-medium mb-1">{{ strtoupper($key) }}</label>

                    

                    <div class="dropzone-custom border-2 border-dashed border-gray-300 rounded-xl p-6 flex flex-col items-center justify-center cursor-pointer" 

                        id="{{ $key }}-dropzone">

                        <span id="{{ $key }}-message">Click or drag file here</span>

                        <input type="file" name="{{ $key }}" id="{{ $key }}-input" class="hidden">

                        <img id="{{ $key }}-preview" class="mt-2 object-contain rounded hidden" style="width:200px;height:110px"/>

                    </div>

                    

                      <small class="text-gray-500 block mt-1">

                        @if($key == 'logo')

                            Recommended: 1800x400

                        @elseif($key == 'banner_2')

                            Recommended: 400x210

                        @elseif($key == 'banner_3')

                            Recommended: 400x210

                        @endif

                    </small>



                    @if(isset($settings[$key]))

                        <img src="{{ asset('storage/'.$settings[$key]) }}" class="mt-2 object-cover rounded mx-auto" style="width:200px;height:110px">

                    @endif

                </div>

                @endforeach

            </div>

            <button type="submit" class="mt-4 px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition">Simpan Pengaturan</button>

        </form>

    </div>



  <div class="bg-white rounded-2xl shadow-lg p-6 space-y-4">

    <h2 class="text-xl font-semibold mb-4">Banners</h2>



    {{-- Form Add New Banner --}}

    <form action="{{ route('admin.settings.banners.store') }}" method="POST" enctype="multipart/form-data" class="space-y-2" id="add-banner-form">

        @csrf

        <label class="block font-medium mb-1">Add New Banner</label>



        <div class="dropzone-custom border-2 border-dashed border-gray-300 rounded-xl p-6 flex flex-col items-center justify-center cursor-pointer" 

            id="banner-dropzone">

            <span id="banner-message">Click or drag file here</span>

            <input type="file" name="image" id="banner-input" class="hidden">

            <img id="banner-preview" class="mt-2 object-cover rounded hidden" style="width:200px;height:110px"/>

        </div>

        

        <small class="text-gray-500 block mt-1">

                       Recommended: 900x450

                    </small>





        <button type="submit" class="mt-4 px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition">Tambah Banner</button>

    </form>



    {{-- Table Banners --}}

   <div x-data="bannerModal()" class="bg-white rounded-2xl shadow-lg p-6 space-y-4">

    <h2 class="text-xl font-semibold mb-4">Banners</h2>



    {{-- Table Banners --}}

    <div class="overflow-x-auto">

        <table class="min-w-full border border-gray-300 mt-4 text-center border-collapse">

            <thead>

                <tr class="bg-gray-100">

                    <th class="border border-gray-300 px-4 py-2">#</th>

                    <th class="border border-gray-300 px-4 py-2">Image</th>

                    <th class="border border-gray-300 px-4 py-2">Actions</th>

                </tr>

            </thead>

            <tbody>

                @foreach($banners as $index => $banner)

                <tr>

                    <td class="border border-gray-300 px-4 py-2">{{ $index + 1 }}</td>

                    <td class="border border-gray-300 px-4 py-2">

                        <img src="{{ asset('storage/'.$banner->image) }}" class="w-40 h-20 object-cover rounded mx-auto">

                    </td>

                    <td class="border border-gray-300 px-4 py-2 flex flex-col justify-center items-center gap-2" style="height:100px;">

                        <div class="flex flex-col gap-2">

                            <button @click="openModal({{ $banner->id }}, '{{ asset('storage/'.$banner->image) }}')"

                                class="px-3 py-1 bg-blue-600 text-white rounded hover:bg-blue-700 transition">

                                Edit

                            </button>

                            <form action="{{ route('admin.settings.banners.destroy', $banner) }}" method="POST" class="m-0">

                                @csrf

                                @method('DELETE')

                                <button class="bg-red-600 text-white px-3 py-1 rounded hover:bg-red-700 transition">Delete</button>

                            </form>

                        </div>

                    </td>

                </tr>

                @endforeach

            </tbody>

        </table>

    </div>



    {{-- Modal --}}

    <div x-show="isOpen" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" x-cloak>

        <div @click.away="closeModal()" class="bg-white rounded-xl p-6 w-96 relative">

            <h3>Edit Banner</h3>

            <form :action="updateUrl" method="POST" enctype="multipart/form-data" class="space-y-2">

                @csrf

                <input type="hidden" name="_method" value="POST" x-ref="methodInput">

                <div class="dropzone-custom border-2 border-dashed border-gray-300 rounded-xl p-6 flex flex-col items-center justify-center cursor-pointer" 

                     @click="$refs.input.click()" @dragover.prevent="dragOver=true" @dragleave.prevent="dragOver=false" 

                     @drop.prevent="handleDrop($event)" :class="{'border-blue-500 bg-blue-50': dragOver}">

                    <span x-show="!preview" x-text="message"></span>

                    <img x-show="preview" :src="preview" class="mt-2 object-cover rounded" style="width:200px;height:110px"/>

                    <input type="file" x-ref="input" name="image" class="hidden" @change="previewFile">

                </div>

                <button type="submit" class="mt-2 px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition">Update</button>

            </form>

            <button @click="closeModal()" class="absolute top-2 right-2 text-gray-500 hover:text-gray-700">&times;</button>

        </div>

    </div>

</div>



</div>



</div>

<script>

document.addEventListener("DOMContentLoaded", function() {

    ['logo','banner_2','banner_3'].forEach(key => {

        const dropzone = document.getElementById(`${key}-dropzone`);

        const input = document.getElementById(`${key}-input`);

        const preview = document.getElementById(`${key}-preview`);

        const message = document.getElementById(`${key}-message`);



        dropzone.addEventListener('click', () => input.click());

        dropzone.addEventListener('dragover', e => {

            e.preventDefault();

            dropzone.classList.add('border-blue-500', 'bg-blue-50');

        });

        dropzone.addEventListener('dragleave', e => {

            e.preventDefault();

            dropzone.classList.remove('border-blue-500', 'bg-blue-50');

        });

        dropzone.addEventListener('drop', e => {

            e.preventDefault();

            dropzone.classList.remove('border-blue-500', 'bg-blue-50');

            if(e.dataTransfer.files.length) {

                input.files = e.dataTransfer.files;

                showPreview(input.files[0], preview, message);

            }

        });

        input.addEventListener('change', () => {

            if(input.files.length) showPreview(input.files[0], preview, message);

        });

    });



    function showPreview(file, preview, message) {

        const reader = new FileReader();

        reader.onload = e => {

            preview.src = e.target.result;

            preview.classList.remove('hidden');

            message.classList.add('hidden');

        }

        reader.readAsDataURL(file);

    }

});

</script>

<script>

function bannerModal() {

    return {

        isOpen: false,

        preview: null,

        message: 'Click or drag file here',

        dragOver: false,

        updateUrl: '',

        input: null,

        openModal(id, image) {

            this.isOpen = true;

            this.preview = image;

            this.updateUrl = `/admin/settings-markerplaces/${id}`; 

        },

        closeModal() {

            this.isOpen = false;

            this.preview = null;

            this.dragOver = false;

        },

        previewFile(event) {

            const file = event.target.files[0];

            if (!file) return;

            const reader = new FileReader();

            reader.onload = e => {

                this.preview = e.target.result;

            };

            reader.readAsDataURL(file);

        },

        handleDrop(event) {

            const file = event.dataTransfer.files[0];

            if (!file) return;

            this.$refs.input.files = event.dataTransfer.files;

            this.previewFile({ target: { files: [file] } });

            this.dragOver = false;

        }

    }

}



document.addEventListener("DOMContentLoaded", function() {

    const dropzone = document.getElementById('banner-dropzone');

    const input = document.getElementById('banner-input');

    const preview = document.getElementById('banner-preview');

    const message = document.getElementById('banner-message');



    dropzone.addEventListener('click', () => input.click());

    dropzone.addEventListener('dragover', e => {

        e.preventDefault();

        dropzone.classList.add('border-blue-500', 'bg-blue-50');

    });

    dropzone.addEventListener('dragleave', e => {

        e.preventDefault();

        dropzone.classList.remove('border-blue-500', 'bg-blue-50');

    });

    dropzone.addEventListener('drop', e => {

        e.preventDefault();

        dropzone.classList.remove('border-blue-500', 'bg-blue-50');

        if(e.dataTransfer.files.length) {

            input.files = e.dataTransfer.files;

            showPreview(input.files[0]);

        }

    });

    input.addEventListener('change', () => {

        if(input.files.length) showPreview(input.files[0]);

    });

    function showPreview(file) {

        const reader = new FileReader();

        reader.onload = e => {

            preview.src = e.target.result;

            preview.classList.remove('hidden');

            message.classList.add('hidden');

        }

        reader.readAsDataURL(file);

    }

});

</script>



<style>

.dropzone-custom:hover {

    background-color: #f0f8ff;

}

</style>

@endsection

