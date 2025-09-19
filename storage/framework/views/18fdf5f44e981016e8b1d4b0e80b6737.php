<?php if($paginator->hasPages()): ?>
    <nav>
        <ul class="pagination justify-content-center">
            
            <?php if($paginator->onFirstPage()): ?>
                <li class="page-item disabled">
                    <span class="page-link">&laquo;</span>
                </li>
            <?php else: ?>
                <li class="page-item">
                    <a class="page-link" href="<?php echo e($paginator->previousPageUrl()); ?>" rel="prev">&laquo;</a>
                </li>
            <?php endif; ?>

            
            <?php
                $maxLinks = 5;
                $current = $paginator->currentPage();
                $last = $paginator->lastPage();

                $start = max(1, $current - floor($maxLinks / 2));
                $end = $start + $maxLinks - 1;

                if ($end > $last) {
                    $end = $last;
                    $start = max(1, $end - $maxLinks + 1);
                }
            ?>

            
            <?php if($start > 1): ?>
                <li class="page-item"><a class="page-link" href="<?php echo e($paginator->url(1)); ?>">1</a></li>
                <?php if($start > 2): ?>
                    <li class="page-item disabled"><span class="page-link">...</span></li>
                <?php endif; ?>
            <?php endif; ?>

            
            <?php for($page = $start; $page <= $end; $page++): ?>
                <?php if($page == $current): ?>
                    <li class="page-item active"><span class="page-link"><?php echo e($page); ?></span></li>
                <?php else: ?>
                    <li class="page-item"><a class="page-link" href="<?php echo e($paginator->url($page)); ?>"><?php echo e($page); ?></a></li>
                <?php endif; ?>
            <?php endfor; ?>

            
            <?php if($end < $last): ?>
                <?php if($end < $last - 1): ?>
                    <li class="page-item disabled"><span class="page-link">...</span></li>
                <?php endif; ?>
                <li class="page-item"><a class="page-link" href="<?php echo e($paginator->url($last)); ?>"><?php echo e($last); ?></a></li>
            <?php endif; ?>

            
            <?php if($paginator->hasMorePages()): ?>
                <li class="page-item">
                    <a class="page-link" href="<?php echo e($paginator->nextPageUrl()); ?>" rel="next">&raquo;</a>
                </li>
            <?php else: ?>
                <li class="page-item disabled">
                    <span class="page-link">&raquo;</span>
                </li>
            <?php endif; ?>
        </ul>
    </nav>
<?php endif; ?>
<?php /**PATH /home/tokq3391/public_html/resources/views/vendor/pagination/bootstrap-5.blade.php ENDPATH**/ ?>