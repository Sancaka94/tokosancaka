import os
import sys
import json

# === KONFIGURASI RAMAH HOSTING ===
os.environ["OMP_NUM_THREADS"] = "1"
os.environ["OPENBLAS_NUM_THREADS"] = "1"
os.environ["MKL_NUM_THREADS"] = "1"
os.environ["VECLIB_MAXIMUM_THREADS"] = "1"
os.environ["NUMEXPR_NUM_THREADS"] = "1"
# Path cache
os.environ['YOLO_CONFIG_DIR'] = '/home/tokq3391/tmp'
os.environ['MPLCONFIGDIR'] = '/home/tokq3391/tmp'

try:
    import cv2
    import torch
    torch.set_num_threads(1) # Kunci 1 Thread
    from ultralytics import YOLO
    
    try:
        from pyzbar.pyzbar import decode as decode_barcode
        HAS_PYZBAR = True
    except:
        HAS_PYZBAR = False

except Exception as e:
    # Print JSON kosong agar tidak error 500
    print("[]")
    sys.exit(0)

# === SETUP MODEL ===
ROOT_DIR = '/home/tokq3391/public_html'
# Gunakan YOLO Nano (paling kecil)
YOLO_PATH = os.path.join(ROOT_DIR, 'yolov8n.pt') 
FACE_PATH = os.path.join(ROOT_DIR, 'haarcascade_frontalface_default.xml')

# Load Model di luar fungsi (supaya kalau error ketahuan di awal)
try:
    # imgsz=320 adalah kunci agar tidak memakan RAM besar
    model = YOLO(YOLO_PATH, task='detect') 
    face_cascade = cv2.CascadeClassifier(FACE_PATH) if os.path.exists(FACE_PATH) else None
except:
    pass

def detect(img_path):
    output = []
    
    try:
        img = cv2.imread(img_path)
        if img is None: 
            print("[]")
            return

        # 1. BARCODE / RESI
        if HAS_PYZBAR:
            barcodes = decode_barcode(img)
            for barcode in barcodes:
                data = barcode.data.decode("utf-8")
                (x, y, w, h) = barcode.rect
                output.append({
                    "type": "barcode",
                    "label": data,
                    "box": [float(x), float(y), float(x+w), float(y+h)]
                })

        # 2. WAJAH (Haar Cascade - Ringan)
        if face_cascade:
            gray = cv2.cvtColor(img, cv2.COLOR_BGR2GRAY)
            faces = face_cascade.detectMultiScale(gray, 1.1, 4)
            for (x, y, w, h) in faces:
                output.append({
                    "type": "face",
                    "label": "WAJAH",
                    "box": [float(x), float(y), float(x+w), float(y+h)]
                })

        # 3. BENDA, HEWAN, KENDARAAN (YOLOv8 Nano)
        # imgsz=320 sangat penting untuk hosting cPanel!
        results = model.predict(source=img, conf=0.35, imgsz=320, save=False, verbose=False, device='cpu')
        
        for r in results:
            for box in r.boxes:
                cls_id = int(box.cls[0])
                label_name = r.names[cls_id] # Contoh: 'cat', 'car', 'cup'
                x1, y1, x2, y2 = box.xyxy[0].tolist()

                # Kategorisasi Sederhana
                category = "benda"
                
                # Filter 'person' karena sudah ada Wajah (opsional, tapi saya masukkan agar lengkap)
                if label_name == 'person':
                    category = "manusia"
                    label_display = "MANUSIA"
                elif label_name in ['cat', 'dog', 'horse', 'sheep', 'cow', 'elephant', 'bear', 'zebra', 'giraffe', 'bird']:
                    category = "hewan"
                    label_display = label_name.upper()
                elif label_name in ['car', 'motorcycle', 'bus', 'truck', 'bicycle']:
                    category = "kendaraan"
                    # Plat nomor biasanya ada di kendaraan, user bisa input manual nanti
                    label_display = label_name.upper() 
                else:
                    label_display = label_name.upper()

                output.append({
                    "type": category,
                    "label": label_display,
                    "label_raw": label_name, # Disimpan untuk kunci database (misal: 'car')
                    "box": [x1, y1, x2, y2]
                })

        print(json.dumps(output))

    except Exception as e:
        print("[]")

if __name__ == "__main__":
    if len(sys.argv) > 1:
        detect(sys.argv[1])