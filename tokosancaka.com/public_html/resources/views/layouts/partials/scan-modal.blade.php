{{-- resources/views/layouts/partials/scan-modal.blade.php --}}

<div id="camera-modal" class="modal fade" tabindex="-1" aria-labelledby="cameraModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="cameraModalLabel">Arahkan Kamera ke Barcode</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body position-relative p-0">
                {{-- Container untuk notifikasi di dalam modal --}}
                <div id="flash-message-container" class="position-absolute top-0 start-50 translate-middle-x w-75 pt-3" style="z-index: 10;"></div>
                
                {{-- Elemen ini adalah tempat library html5-qrcode akan merender video kamera --}}
                <div id="reader" class="w-100"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Selesai & Tutup</button>
            </div>
        </div>
    </div>
</div>
