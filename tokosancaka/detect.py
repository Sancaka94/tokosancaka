import sys
import json
import os
import cv2
import numpy as np
import urllib.request

# Konfigurasi Cache Folder
os.environ['YOLO_CONFIG_DIR'] = '/home/tokq3391/tmp'
os.environ['MPLCONFIGDIR'] = '/home/tokq3391/tmp'

# Path Model
ROOT_DIR = '/home/tokq3391/public_html'
YOLO_MODEL_PATH = os.path.join(ROOT_DIR, 'yolov8n.pt')
FACE_CASCADE_PATH = os.path.join(ROOT_DIR, 'haarcascade_frontalface_default.xml')

# URL Download Haar Cascade (jika belum ada)
HAAR_URL = "https://raw.githubusercontent.com/opencv/opencv/master/data/haarcascades/haarcascade_frontalface_default.xml"

try:
    from ultralytics import YOLO
    # Coba import pyzbar, jika gagal pakai OpenCV nanti
    try:
        from pyzbar.pyzbar import decode as decode_barcode
        HAS_PYZBAR = True
    except ImportError:
        HAS_PYZBAR = False

    # 1. DOWNLOAD MODEL WAJAH JIKA BELUM ADA
    if not os.path.exists(FACE_CASCADE_PATH):
        # Download manual agar tidak error
        urllib.request.urlretrieve(HAAR_URL, FACE_CASCADE_PATH)

    # 2. LOAD MODELS
    yolo_model = YOLO(YOLO_MODEL_PATH)
    face_cascade = cv2.CascadeClassifier(FACE_CASCADE_PATH)

    def detect_all(img_path):
        final_output = []
        
        # Baca Gambar dengan OpenCV
        img = cv2.imread(img_path)
        if img is None:
            print(json.dumps({"error": "Gambar tidak bisa dibaca"}))
            return

        gray = cv2.cvtColor(img, cv2.COLOR_BGR2GRAY)

        # ==========================================================
        # A. DETEKSI OBJEK UMUM (YOLOv8)
        # Target: Manusia, Ayam (Bird), Mobil, Motor
        # ==========================================================
        results = yolo_model.predict(source=img, conf=0.3, save=False, verbose=False)
        
        target_classes = {
            0: 'Manusia',           # person
            1: 'Sepeda',            # bicycle
            2: 'Mobil',             # car
            3: 'Motor',             # motorcycle
            14: 'Ayam/Burung',      # bird (COCO tidak punya ayam spesifik, pakai bird)
            15: 'Kucing',           # cat
            16: 'Anjing',           # dog
            39: 'Botol',            # bottle
        }

        for r in results:
            for box in r.boxes:
                cls_id = int(box.cls[0])
                
                # Jika kelas ada di target kita, atau ambil semua saja biar keren
                label_default = r.names[cls_id]
                label_custom = target_classes.get(cls_id, label_default)

                x1, y1, x2, y2 = box.xyxy[0].tolist()
                
                final_output.append({
                    "label": label_custom.upper(),
                    "confidence": f"{float(box.conf[0]):.2f}",
                    "type": "object",
                    "box": [x1, y1, x2, y2]
                })

        # ==========================================================
        # B. DETEKSI WAJAH (Haar Cascade)
        # ==========================================================
        faces = face_cascade.detectMultiScale(gray, 1.1, 4)
        for (x, y, w, h) in faces:
            final_output.append({
                "label": "WAJAH",
                "confidence": "0.90", # Haar tidak return confidence, kita hardcode
                "type": "face",
                "box": [float(x), float(y), float(x+w), float(y+h)]
            })

        # ==========================================================
        # C. DETEKSI BARCODE & QR (Resi Paket)
        # ==========================================================
        
        # Metode 1: Pakai Pyzbar (Lebih ampuh untuk 1D Barcode/Resi)
        if HAS_PYZBAR:
            barcodes = decode_barcode(img)
            for barcode in barcodes:
                (x, y, w, h) = barcode.rect
                barcode_data = barcode.data.decode("utf-8")
                barcode_type = barcode.type
                
                final_output.append({
                    "label": f"RESI: {barcode_type}",
                    "text_content": barcode_data, # Kirim isi resi juga
                    "confidence": "1.0",
                    "type": "barcode",
                    "box": [float(x), float(y), float(x+w), float(y+h)]
                })
        
        # Metode 2: Pakai OpenCV QR Detector (Cadangan untuk QR)
        else:
            qr_detector = cv2.QRCodeDetector()
            data, bbox, _ = qr_detector.detectAndDecode(img)
            if bbox is not None and data:
                # Konversi bbox numpy ke format flat [x1, y1, x2, y2] kira-kira
                pts = bbox[0]
                x1, y1 = pts[0]
                x2, y2 = pts[2]
                
                final_output.append({
                    "label": "QR CODE",
                    "text_content": data,
                    "confidence": "1.0",
                    "type": "barcode",
                    "box": [float(x1), float(y1), float(x2), float(y2)]
                })

        # CETAK JSON FINAL
        print(json.dumps(final_output))

    if __name__ == "__main__":
        if len(sys.argv) > 1:
            detect_all(sys.argv[1])
        else:
            print(json.dumps({"error": "No image path provided"}))

except Exception as e:
    # Tangkap error apapun agar Laravel tidak blank
    print(json.dumps({"error": str(e)}))