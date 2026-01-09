   <!--{{-- SLIDER: fokus ke atas + opsi no-crop --}}-->
    <!--<div-->
    <!--    x-data="{ activeSlide: 0, slides: {{ json_encode($slides ?? []) }} }"-->
    <!--    x-init="if (slides.length > 1) { setInterval(() => { activeSlide = (activeSlide + 1) % slides.length }, 5000) }"-->
    <!--    id="customer-slider"-->
    <!--    class="relative w-full max-w-7xl mx-auto rounded-lg shadow-lg overflow-hidden"-->
    <!-->-->
        <!-- Slides -->
    <!--    <div class="relative w-full h-[320px] sm:h-[380px] md:h-[460px] lg:h-[560px] xl:h-[640px]">-->
    <!--        <template x-if="slides.length > 0">-->
    <!--            <template x-for="(slide, index) in slides" :key="index">-->
    <!--                <div-->
    <!--                    x-show="activeSlide === index"-->
    <!--                    x-transition:enter="transition ease-in-out duration-500"-->
    <!--                    x-transition:leave="transition ease-in-out duration-500"-->
    <!--                    class="absolute inset-0"-->
    <!--                >-->
    <!--                    {{-- Blur background agar 'contain' tetap penuh estetis --}}-->
    <!--                    <div-->
    <!--                        class="absolute inset-0 blur-lg scale-110 opacity-30"-->
    <!--                        :style="`background-image:url('${slide.img}'); background-size:cover; background-position:center;`"-->
    <!--                        aria-hidden="true"-->
    <!--                    ></div>-->

    <!--                    {{-- Jika slide.fit === 'contain' -> tidak dipotong. Default: cover + object-top (atas tidak kepotong) --}}-->
    <!--                    <img-->
    <!--                        :src="slide.img"-->
    <!--                        :alt="slide.alt ?? 'Informasi'"-->
    <!--                        :class="(slide.fit === 'contain')-->
    <!--                            ? 'relative w-full h-full object-contain'-->
    <!--                            : 'relative w-full h-full object-cover object-top'"-->
    <!--                    >-->
    <!--                </div>-->
    <!--            </template>-->
    <!--        </template>-->

            <!-- Placeholder -->
    <!--        <template x-if="slides.length === 0">-->
    <!--            <div class="w-full h-full bg-gray-200 flex items-center justify-center">-->
    <!--                <p class="text-gray-500">Tidak ada informasi saat ini.</p>-->
    <!--            </div>-->
    <!--        </template>-->
    <!--    </div>-->

        <!-- Navigation -->
    <!--    <template x-if="slides.length > 1">-->
    <!--        <div class="absolute inset-0 flex justify-between items-center px-4">-->
    <!--            <button @click="activeSlide = (activeSlide - 1 + slides.length) % slides.length" class="bg-white/50 hover:bg-white text-gray-800 rounded-full w-10 h-10 flex items-center justify-center">&#10094;</button>-->
    <!--            <button @click="activeSlide = (activeSlide + 1) % slides.length" class="bg-white/50 hover:bg-white text-gray-800 rounded-full w-10 h-10 flex items-center justify-center">&#10095;</button>-->
    <!--        </div>-->
    <!--    </template>-->

        <!-- Indicators -->
    <!--    <template x-if="slides.length > 1">-->
    <!--        <div class="absolute bottom-4 left-0 w-full flex justify-center gap-2">-->
    <!--            <template x-for="(slide, index) in slides" :key="index">-->
    <!--                <button-->
    <!--                    @click="activeSlide = index"-->
    <!--                    :class="{'bg-white': activeSlide === index, 'bg-white/50': activeSlide !== index}"-->
    <!--                    class="w-3 h-3 rounded-full transition"-->
    <!--                ></button>-->
    <!--            </template>-->
    <!--        </div>-->
    <!--    </template>-->
    <!--</div>-->