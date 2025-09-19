 

<?php $__env->startSection('title', '404 - Halaman Tidak Ditemukan'); ?>

<?php $__env->startSection('content'); ?>
<div class="d-flex align-items-center justify-content-center vh-100 bg-light text-center px-3">
    <div>
        <h1 class="display-1 fw-bold text-danger">404</h1>
        <p class="fs-3"> <span class="text-danger">Oops!</span> Halaman tidak ditemukan.</p>
        <p class="lead"> Halaman yang kamu cari mungkin telah dihapus, dipindahkan, atau tidak pernah ada.</p>
        <a href="<?php echo e(url('/')); ?>" class="btn btn-warning px-4 py-2 mt-3 fw-semibold">
            <i class="fas fa-arrow-left me-2"></i> Kembali ke Beranda
        </a>
    </div>
</div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/tokq3391/public_html/resources/views/errors/404.blade.php ENDPATH**/ ?>