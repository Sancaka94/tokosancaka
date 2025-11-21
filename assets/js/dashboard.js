/**
 * dashboard.js
 * File ini berisi logika JavaScript khusus untuk Halaman Dashboard.
 * Tugasnya adalah untuk menginisialisasi grafik dan fungsionalitas widget.
 */
$(function () {
    'use strict';

    // Pastikan library Chart.js sudah dimuat sebelum menjalankan kode ini
    if (typeof Chart === 'undefined') {
        console.error('Error: Chart.js tidak ditemukan. Pastikan sudah dimuat di layout Anda.');
        return;
    }

    // --- GRAFIK PEMASUKAN BULANAN ---
    var salesChartCanvas = $('#salesChart').get(0);
    if (salesChartCanvas) {
        var ctx = salesChartCanvas.getContext('2d');

        // Data ini nantinya akan Anda kirim dari DashboardController
        var salesChartData = {
            labels: ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli'],
            datasets: [
                {
                    label: 'Pemasukan',
                    backgroundColor: 'rgba(60,141,188,0.9)',
                    borderColor: 'rgba(60,141,188,0.8)',
                    data: [28000, 48000, 40000, 19000, 86000, 27000, 90000] // Contoh data
                }
            ]
        };

        var salesChartOptions = {
            maintainAspectRatio: false,
            responsive: true,
            plugins: {
                legend: {
                    display: false // Menyembunyikan legenda
                }
            },
            scales: {
                x: {
                    grid: {
                        display: false, // Menyembunyikan garis grid sumbu X
                    }
                },
                y: {
                    grid: {
                        display: true, // Menampilkan garis grid sumbu Y
                    },
                    beginAtZero: true
                }
            }
        };

        // Membuat grafik baru
        new Chart(ctx, {
            type: 'bar', // Tipe grafik adalah bar chart
            data: salesChartData,
            options: salesChartOptions
        });
    }

    // --- Fungsionalitas untuk Tombol Widget Box ---
    // Menangani tombol collapse/expand
    $('.btn-tool[data-widget="collapse"]').on('click', function() {
        var box = $(this).closest('.box');
        var icon = $(this).find('i.fa');
        
        // Toggle (buka/tutup) body dan footer dari box
        box.find('.box-body, .box-footer').slideToggle(500);
        
        // Ganti ikon dari minus menjadi plus, dan sebaliknya
        icon.toggleClass('fa-minus fa-plus');
    });

    // Menangani tombol remove
    $('.btn-tool[data-widget="remove"]').on('click', function() {
        var box = $(this).closest('.box');
        box.slideUp(500);
    });

});
