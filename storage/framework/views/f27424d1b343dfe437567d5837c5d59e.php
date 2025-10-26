<?php $__env->startSection('title', 'Kategori: ' . $category->name); ?>

<?php $__env->startPush('styles'); ?>
<style>
    /* Styling for Swiper navigation buttons */
    .swiper-button-next, .swiper-button-prev {
        color: white; background-color: rgba(0, 0, 0, 0.3); width: 40px; height: 40px;
        border-radius: 50%; transition: background-color 0.3s;
    }
    .swiper-button-next:hover, .swiper-button-prev:hover { background-color: rgba(0, 0, 0, 0.5); }
    .swiper-button-next::after, .swiper-button-prev::after { font-size: 18px; font-weight: bold; }
    .swiper-pagination-bullet-active { background-color: #ef4444 !important; }
    .flash-sale-bg { background: linear-gradient(90deg, rgba(220,38,38,1) 0%, rgba(239,68,68,1) 100%); }
</style>
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>
<div class="container mx-auto py-6 px-4">

    <!-- Hero Section -->
    <section class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-10">
        <div class="lg:col-span-2 rounded-2xl overflow-hidden shadow-lg h-[200px] md:h-[300px] lg:h-[420px]" data-aos="fade-right">
            <div class="swiper heroSwiper w-full h-full">
                <div class="swiper-wrapper">
                    <?php $__empty_1 = true; $__currentLoopData = $banners; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $banner): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <div class="swiper-slide">
                            <img src="<?php echo e(asset('storage/' . $banner->image)); ?>" class="w-full h-full object-fill" alt="<?php echo e($banner->title ?? 'Promo Banner'); ?>">
                        </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <div class="swiper-slide">
                            <img src="https://placehold.co/800x420/e2e8f0/94a3b8?text=Sancaka+Express" class="w-full h-full object-fill" alt="Default Banner">
                        </div>
                    <?php endif; ?>
                </div>
                <div class="swiper-button-next"></div>
                <div class="swiper-button-prev"></div>
                <div class="swiper-pagination"></div>
            </div>
        </div>
        <div class="grid grid-rows-2 gap-6 h-[400px] sm:h-[250px] md:h-[300px] lg:h-[420px]" data-aos="fade-left">
            <?php if(isset($settings['banner_2'])): ?>
            <div class="rounded-2xl overflow-hidden shadow-lg"><img src="<?php echo e(asset('storage/' . $settings['banner_2'])); ?>" class="w-full h-full object-fill" alt="Banner 2"></div>
            <?php endif; ?>
            <?php if(isset($settings['banner_3'])): ?>
            <div class="rounded-2xl overflow-hidden shadow-lg"><img src="<?php echo e(asset('storage/' . $settings['banner_3'])); ?>" class="w-full h-full object-fill" alt="Banner 3"></div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Other Categories Section -->
    <?php if(isset($categories) && $categories->isNotEmpty()): ?>
    <section class="bg-white p-6 rounded-2xl shadow-md mb-10 relative" data-aos="fade-up">
        <h2 class="text-xl font-bold mb-5 text-gray-800">Kategori Lainnya</h2>
        <div class="swiper categoriesSwiper">
            <div class="swiper-wrapper">
                <?php $categoryChunks = $categories->chunk(20); ?>
                <?php $__currentLoopData = $categoryChunks; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $chunk): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div class="swiper-slide">
                    <div class="grid grid-cols-5 md:grid-cols-10 gap-x-4 gap-y-6 text-center">
                        <?php $__currentLoopData = $chunk; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $cat): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <a href="<?php echo e(route('etalase.categories-show', $cat->slug)); ?>" class="flex flex-col items-center space-y-2 text-gray-600 hover:text-red-500 transition-colors group">
                            <div class="w-16 h-16 bg-gray-100 rounded-2xl flex items-center justify-center group-hover:bg-red-100"><i class="fas <?php echo e($cat->icon ?? 'fa-tag'); ?> text-3xl text-gray-500 group-hover:text-red-500"></i></div>
                            <span class="text-sm font-medium"><?php echo e($cat->name); ?></span>
                        </a>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>
                </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        </div>
        <div class="swiper-button-next category-next !text-gray-600 !bg-white !shadow-md !w-10 !h-10"></div>
        <div class="swiper-button-prev category-prev !text-gray-600 !bg-white !shadow-md !w-10 !h-10"></div>
    </section>
    <?php endif; ?>
    
    <!-- Flash Sale Section -->
    <?php if(isset($flashSaleProducts) && $flashSaleProducts->isNotEmpty()): ?>
    <section class="mb-10" data-aos="fade-up">
        <div class="flash-sale-bg p-5 rounded-t-2xl flex justify-between items-center">
            <h2 class="text-3xl font-extrabold text-white tracking-wide">FLASH SALE</h2>
        </div>
        <div class="bg-white p-5 rounded-b-2xl shadow-md">
            <div class="relative">
                <div class="swiper flashSaleSwiper">
                    <div class="swiper-wrapper py-2">
                        <?php $__currentLoopData = $flashSaleProducts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $product): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <div class="swiper-slide h-auto pb-2">
                            <a href="<?php echo e(route('etalase.show', $product->slug)); ?>" class="block border rounded-xl overflow-hidden group hover:shadow-xl transition-all duration-300 h-full flex flex-col bg-white">
                                <div class="relative">
                                    <div class="h-48 bg-gray-50"><img src="<?php echo e($product->image_url ? asset('storage/' . $product->image_url) : 'https://placehold.co/400x400'); ?>" alt="<?php echo e($product->name); ?>" class="w-full h-full object-fill group-hover:scale-105 transition-transform"></div>
                                    <span class="absolute top-2 left-2 bg-red-600 text-white text-xs font-bold px-2 py-1 rounded-full"><?php echo e(round($product->discount_percentage)); ?>%</span>
                                </div>
                                <div class="p-3 flex flex-col flex-grow">
                                    <h3 class="text-sm font-semibold text-gray-800 truncate"><?php echo e($product->name); ?></h3>
                                    <p class="text-lg font-extrabold text-red-500 mt-1">Rp<?php echo e(number_format($product->price, 0, ',', '.')); ?></p>
                                    <?php if($product->original_price): ?><span class="text-xs text-gray-400 line-through">Rp<?php echo e(number_format($product->original_price, 0, ',', '.')); ?></span><?php endif; ?>
                                    <div class="mt-auto pt-2 text-xs text-gray-500">Terjual <?php echo e($product->sold_count ?? 0); ?></div>
                                </div>
                            </a>
                        </div>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>
                </div>
                <div class="swiper-button-next flash-sale-next !text-gray-600 !bg-white !shadow-md !w-10 !h-10"></div>
                <div class="swiper-button-prev flash-sale-prev !text-gray-600 !bg-white !shadow-md !w-10 !h-10"></div>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Current Category Products Section -->
    <section data-aos="fade-up">
        <div class="bg-white p-5 rounded-t-2xl border-b-2 border-gray-100">
            <h2 class="text-xl font-bold text-center text-gray-800">PRODUK KATEGORI: <?php echo e(strtoupper($category->name)); ?></h2>
        </div>
        <div class="p-5 bg-white rounded-b-2xl shadow-md">
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-5">
                <?php $__empty_1 = true; $__currentLoopData = $products; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $product): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <div class="bg-white border rounded-xl overflow-hidden group hover:shadow-2xl transition-all duration-300 flex flex-col">
                        <a href="<?php echo e(route('products.show', $product->slug)); ?>">
                            <div class="h-48 bg-gray-50 relative">
                               <img src="<?php echo e($product->image_url ? asset('storage/' . $product->image_url) : 'https://placehold.co/400x400'); ?>" alt="<?php echo e($product->name); ?>" class="w-full h-full object-fill group-hover:scale-105 transition-transform">
                            </div>
                        </a>
                        <div class="p-4 flex flex-col flex-grow">
                            <h3 class="text-sm font-semibold text-gray-800 mb-1 h-10"><?php echo e(Str::limit($product->name, 50)); ?></h3>
                            <div class="flex items-center text-xs text-gray-500 mb-2"><i class="fas fa-store w-3 h-3 mr-1.5 text-gray-400"></i><span class="truncate"><?php echo e($product->store->name ?? 'Toko Sancaka'); ?></span></div>
                            <p class="text-lg font-extrabold text-red-500">Rp<?php echo e(number_format($product->price, 0, ',', '.')); ?></p>
                            <?php if($product->original_price): ?>
                            <div class="flex items-center space-x-2 mt-1">
                                <span class="text-xs text-gray-400 line-through">Rp<?php echo e(number_format($product->original_price, 0, ',', '.')); ?></span>
                                <span class="text-xs font-bold text-red-600 bg-red-100 px-1.5 py-0.5 rounded"><?php echo e(round($product->discount_percentage)); ?>%</span>
                            </div>
                            <?php endif; ?>
                            <div class="mt-auto pt-3">
                                <form action="<?php echo e(route('cart.add', ['product' => $product->id])); ?>" method="POST">
                                    <?php echo csrf_field(); ?>
                                    <button type="submit" class="w-full bg-red-500 text-white font-bold py-2.5 rounded-lg text-sm hover:bg-red-600">Keranjang</button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <div class="col-span-full text-center py-16"><p class="text-gray-500">Belum ada produk untuk kategori ini.</p></div>
                <?php endif; ?>
            </div>
            <div class="text-center mt-10"><?php echo e($products->links()); ?></div>
        </div>
    </section>

</div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    new Swiper(".heroSwiper", { loop: true, effect: "fade", autoplay: { delay: 4000, disableOnInteraction: false }, pagination: { el: ".swiper-pagination", clickable: true }, navigation: { nextEl: ".swiper-button-next", prevEl: ".swiper-button-prev" } });
    new Swiper(".categoriesSwiper", { loop: false, slidesPerView: 1, spaceBetween: 20, navigation: { nextEl: ".category-next", prevEl: ".category-prev" } });
    new Swiper(".flashSaleSwiper", { slidesPerView: 2, spaceBetween: 10, navigation: { nextEl: ".flash-sale-next", prevEl: ".flash-sale-prev" }, breakpoints: { 640: { slidesPerView: 3 }, 768: { slidesPerView: 4 }, 1024: { slidesPerView: 5 } } });
});
</script>
<?php $__env->stopPush(); ?>


<?php echo $__env->make('layouts.marketplace', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/tokq3391/public_html/resources/views/etalase/category-show.blade.php ENDPATH**/ ?>