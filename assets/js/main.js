/**
 * main.js
 * File ini berisi logika JavaScript global untuk layout admin,
 * seperti toggle sidebar dan fungsionalitas menu treeview.
 */
$(document).ready(function () {
    // Sidebar Toggle
    const sidebarToggle = document.getElementById("sidebarToggle");
    if(sidebarToggle) {
        sidebarToggle.addEventListener("click", function (e) {
            e.preventDefault();
            document.getElementById("wrapper").classList.toggle("toggled");
        });
    }

    // Fungsionalitas Menu Treeview ala AdminLTE
    $('.nav-sidebar .nav-item > a').on('click', function(e) {
        let that = $(this);
        let checkElement = that.next();

        // Cek jika elemen berikutnya adalah menu dropdown
        if (checkElement.is('.nav-treeview')) {
            e.preventDefault(); // Mencegah navigasi untuk link dropdown
            
            // Jika menu sudah terbuka, tutup
            if (checkElement.is(':visible')) {
                checkElement.slideUp(300);
                that.parent(".nav-item").removeClass("menu-open");
            } else {
                // Tutup semua menu lain yang terbuka pada level yang sama
                let parent = that.parents('ul').first();
                parent.find('.nav-treeview:visible').slideUp(300);
                parent.find('.menu-open').removeClass('menu-open');
                
                // Buka menu yang diklik
                checkElement.slideDown(300, function() {
                    that.parent(".nav-item").addClass("menu-open");
                });
            }
        }
    });

    // Pastikan menu yang aktif terbuka saat halaman dimuat
    $('.nav-sidebar .nav-item.active').parents('.nav-item').addClass('menu-open');
    $('.nav-sidebar .nav-item.menu-open > .nav-treeview').show();
});
