import os
import sys

# ==============================================================================
# 1. KONFIGURASI RESOURCE (WAJIB PALING ATAS)
# ==============================================================================
# Memaksa library matematika (Numpy/PyTorch) hanya pakai 1 Thread.
# Ini mencegah error "Resource temporarily unavailable" di Shared Hosting cPanel.
os.environ["OMP_NUM_THREADS"] = "1"
os.environ["OPENBLAS_NUM_THREADS"] = "1"
os.environ["MKL_NUM_THREADS"] = "1"
os.environ["VECLIB_MAXIMUM_THREADS"] = "1"
os.environ["NUMEXPR_NUM_THREADS"] = "1"

# Konfigurasi Cache agar tidak error permission
os.environ['YOLO_CONFIG_DIR'] = '/home/tokq3391/tmp'
os.environ['MPLCONFIGDIR'] = '/home/tokq3391/tmp'

import json
import cv2
import numpy as np
import urllib.request

# ==============================================================================
# 2. KONFIGURASI PATH MODEL
# ==============================================================================
ROOT_DIR = '/home/tokq3391/public_html'
YOLO_MODEL_PATH = os.path.join(ROOT_DIR, 'yolov8n.pt')
FACE_CASCADE_PATH = os.path.join(ROOT_DIR, 'haarcascade_frontalface_default.xml')
HAAR_URL = "https://raw.githubusercontent.com/opencv/opencv/master/data/haarcascades/haarcascade_frontalface_default.xml"

try:
    from ultralytics import YOLO

    # Coba import pyzbar untuk barcode 1D (Resi), jika gagal pakai OpenCV (QR)
    try:
        from pyzbar.pyzbar import decode as decode_barcode
        HAS_PYZBAR = True
    except ImportError:
        HAS_PYZBAR = False

    # --------------------------------------------------------------------------
    # Download Model Wajah jika belum ada
    # --------------------------------------------------------------------------
    if not os.path.exists(FACE_CASCADE_PATH):
        try:
            urllib.request.urlretrieve(HAAR_URL, FACE_CASCADE_PATH)
        except:
            pass # Lanjut saja jika gagal download (nanti skip deteksi wajah)

    # --------------------------------------------------------------------------
    # Load Models
    # --------------------------------------------------------------------------
    model = YOLO(YOLO_MODEL_PATH)
    
    face_cascade = None
    if os.path.exists(FACE_CASCADE_PATH):
        face_cascade = cv2.CascadeClassifier(FACE_CASCADE_PATH)

    # ==========================================================================
    # 3. FUNGSI UTAMA DETEKSI
    # ==========================================================================
    def detect(img_path):
        final_output = []
        
        # Baca Gambar
        img = cv2.imread(img_path)
        if img is None:
            print(json.dumps({"error": "Gambar tidak bisa dibaca"}))
            return

        # A. DETEKSI BARCODE & RESI (Prioritas Utama)
        # -------------------------------------------
        # Metode 1: Pyzbar (Lebih kuat untuk Barcode Resi JNE/J&T)
        if HAS_PYZBAR:
            barcodes = decode_barcode(img)
            for barcode in barcodes:
                (x, y, w, h) = barcode.rect
                barcode_data = barcode.data.decode("utf-8")
                
                final_output.append({
                    "type": "barcode",
                    "label_raw": barcode_data,
                    "conf": 100.0,
                    "box": [float(x), float(y), float(x+w), float(y+h)]
                })
        
        # Metode 2: OpenCV (Backup untuk QR Code)
        qr_detector = cv2.QRCodeDetector()
        data, bbox, _ = qr_detector.detectAndDecode(img)
        if bbox is not None and data:
            # Cek apakah data ini sudah terdeteksi pyzbar? Jika belum, tambahkan.
            already_found = any(d['label_raw'] == data for d in final_output)
            if not already_found:
                pts = bbox[0]
                x1, y1 = pts[0]
                x2, y2 = pts[2]
                final_output.append({
                    "type": "barcode",
                    "label_raw": data,
                    "conf": 100.0,
                    "box": [float(x1), float(y1), float(x2), float(y2)]
                })

        # B. DETEKSI OBJEK UMUM (YOLOv8)
        # ------------------------------
        results = model.predict(source=img, conf=0.25, save=False, verbose=False)
        
        for r in results:
            for box in r.boxes:
                cls_id = int(box.cls[0])
                label = r.names[cls_id]
                conf = float(box.conf[0])
                x1, y1, x2, y2 = box.xyxy[0].tolist()

                # Tentukan Tipe Objek
                obj_type = "benda"
                if label == 'person': 
                    obj_type = "manusia"
                elif label in ['car', 'motorcycle', 'bus', 'truck', 'bicycle']: 
                    obj_type = "kendaraan"
                elif label in ['cat', 'dog', 'bird', 'horse', 'sheep', 'cow']: 
                    obj_type = "hewan"

                final_output.append({
                    "type": obj_type,
                    "label_raw": label,
                    "conf": round(conf * 100, 1),
                    "box": [x1, y1, x2, y2]
                })

        # C. DETEKSI WAJAH (Haar Cascade)
        # -------------------------------
        if face_cascade:
            gray = cv2.cvtColor(img, cv2.COLOR_BGR2GRAY)
            faces = face_cascade.detectMultiScale(gray, 1.1, 4)
            for (x, y, w, h) in faces:
                # Cek overlap: Jangan buat kotak wajah jika YOLO sudah deteksi 'person' di situ
                # (Sederhana: Kita tambahkan saja, biarkan frontend yang atur atau timpa)
                final_output.append({
                    "type": "wajah",
                    "label_raw": "face",
                    "conf": 95.0,
                    "box": [float(x), float(y), float(x+w), float(y+h)]
                })

        # CETAK JSON
        print(json.dumps(final_output))

    if __name__ == "__main__":
        if len(sys.argv) > 1:
            detect(sys.argv[1])
        else:
            print(json.dumps({"error": "No image path provided"}))

except Exception as e:
    # Tangkap error apapun dan jadikan JSON agar PHP tidak bingung
    print(json.dumps({"error": str(e)}))