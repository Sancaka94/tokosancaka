{{-- File: resources/views/partials/blog/sidebar.blade.php --}}
<div class="sticky-top" style="top: 2rem;">
    <!-- Widget Populer -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-danger text-white">
            <h5 class="mb-0 fw-bold"><i class="fas fa-fire me-2"></i>Terpopuler</h5>
        </div>
        <ul class="list-group list-group-flush">
            {{-- Loop untuk berita populer --}}
            <a href="#" class="list-group-item list-group-item-action d-flex align-items-center">
                <span class="fw-bold me-3 fs-4 text-muted">1</span>
                <span>Judul artikel populer pertama yang sangat menarik perhatian pembaca.</span>
            </a>
            <a href="#" class="list-group-item list-group-item-action d-flex align-items-center">
                <span class="fw-bold me-3 fs-4 text-muted">2</span>
                <span>Berita kedua yang sedang hangat dibicarakan.</span>
            </a>
            <a href="#" class="list-group-item list-group-item-action d-flex align-items-center">
                <span class="fw-bold me-3 fs-4 text-muted">3</span>
                <span>Analisis mendalam tentang topik ketiga.</span>
            </a>
            <a href="#" class="list-group-item list-group-item-action d-flex align-items-center">
                <span class="fw-bold me-3 fs-4 text-muted">4</span>
                <span>Kisah inspiratif dari tokoh terkenal.</span>
            </a>
        </ul>
    </div>

    <!-- Widget Kategori -->
    <div class="card shadow-sm">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0 fw-bold"><i class="fas fa-tags me-2"></i>Kategori</h5>
        </div>
        <div class="list-group list-group-flush">
            {{-- Loop untuk kategori --}}
            <a href="#" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                Teknologi
                <span class="badge bg-primary rounded-pill">14</span>
            </a>
            <a href="#" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                Olahraga
                <span class="badge bg-primary rounded-pill">8</span>
            </a>
            <a href="#" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                Ekonomi
                <span class="badge bg-primary rounded-pill">5</span>
            </a>
            <a href="#" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                Gaya Hidup
                <span class="badge bg-primary rounded-pill">12</span>
            </a>
        </div>
    </div>
</div>
