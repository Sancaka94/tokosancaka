import sys
import json
import os
import cv2
import numpy as np
import urllib.request

# Konfigurasi Cache Folder (PENTING DI CPANEL)
os.environ['YOLO_CONFIG_DIR'] = '/home/tokq3391/tmp'
os.environ['MPLCONFIGDIR'] = '/home/tokq3391/tmp'

# Paths
ROOT_DIR = '/home/tokq3391/public_html'
YOLO_MODEL_PATH = os.path.join(ROOT_DIR, 'yolov8n.pt')
FACE_CASCADE_PATH = os.path.join(ROOT_DIR, 'haarcascade_frontalface_default.xml')
HAAR_URL = "https://raw.githubusercontent.com/opencv/opencv/master/data/haarcascades/haarcascade_frontalface_default.xml"

try:
    from ultralytics import YOLO
    
    # Download Haar jika belum ada
    if not os.path.exists(FACE_CASCADE_PATH):
        urllib.request.urlretrieve(HAAR_URL, FACE_CASCADE_PATH)

    # Load Model
    model = YOLO(YOLO_MODEL_PATH)
    face_cascade = cv2.CascadeClassifier(FACE_CASCADE_PATH)

    def detect(img_path):
        output = []
        img = cv2.imread(img_path)
        if img is None: return

        # 1. DETEKSI OBJEK UMUM (YOLO - SEMUA BENDA)
        results = model.predict(source=img, conf=0.25, save=False, verbose=False)
        
        for r in results:
            for box in r.boxes:
                cls_id = int(box.cls[0])
                label = r.names[cls_id]
                conf = float(box.conf[0])
                x1, y1, x2, y2 = box.xyxy[0].tolist()

                # Filter khusus
                obj_type = "benda"
                if label == 'person': obj_type = "manusia"
                if label in ['car', 'motorcycle', 'bus', 'truck']: obj_type = "kendaraan"
                if label in ['cat', 'dog', 'bird', 'horse', 'sheep', 'cow']: obj_type = "hewan"

                output.append({
                    "type": obj_type,
                    "label_raw": label, # Label asli bahasa inggris (misal: cup)
                    "conf": round(conf * 100, 1),
                    "box": [x1, y1, x2, y2]
                })

        # 2. DETEKSI WAJAH (HAAR CASCADE)
        gray = cv2.cvtColor(img, cv2.COLOR_BGR2GRAY)
        faces = face_cascade.detectMultiScale(gray, 1.1, 4)
        for (x, y, w, h) in faces:
            output.append({
                "type": "wajah",
                "label_raw": "face",
                "conf": 95.0,
                "box": [float(x), float(y), float(x+w), float(y+h)]
            })

        # 3. DETEKSI BARCODE (RESIP/PRODUK) - Pake OpenCV QR Decoder ringan
        qr_detector = cv2.QRCodeDetector()
        data, bbox, _ = qr_detector.detectAndDecode(img)
        if bbox is not None and data:
            pts = bbox[0]
            x1, y1 = pts[0]
            x2, y2 = pts[2]
            output.append({
                "type": "barcode",
                "label_raw": data,
                "conf": 100.0,
                "box": [float(x1), float(y1), float(x2), float(y2)]
            })

        print(json.dumps(output))

    if __name__ == "__main__":
        if len(sys.argv) > 1:
            detect(sys.argv[1])

except Exception as e:
    print(json.dumps({"error": str(e)}))