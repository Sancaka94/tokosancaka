import React, { useState, useEffect } from 'react';
import { View, Text, StyleSheet, TouchableOpacity, ActivityIndicator, Alert, SafeAreaView, Platform } from 'react-native';
import { Ionicons, FontAwesome5 } from '@expo/vector-icons';
import { useRouter, useLocalSearchParams } from 'expo-router';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { WebView } from 'react-native-webview';

const APP_USER_AGENT = 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/114.0.0.0 Mobile Safari/537.36';

export default function CetakThermalResiScreen() {
  const router = useRouter();
  const { resi } = useLocalSearchParams();
  const [pesanan, setPesanan] = useState(null);
  const [isLoading, setIsLoading] = useState(true);

  // --- FETCH DATA ---
  useEffect(() => {
    const fetchPesanan = async () => {
      try {
        const token = await AsyncStorage.getItem('userToken');
        const response = await fetch(`https://tokosancaka.com/api/mobile/customer/pesanan/detail/${resi}`, {
          headers: { 'Authorization': `Bearer ${token}`, 'Accept': 'application/json', 'User-Agent': APP_USER_AGENT }
        });
        const json = await response.json();

        if (json.success && json.data) {
          setPesanan(json.data);
        } else {
          Alert.alert('Gagal', 'Data pesanan tidak ditemukan.');
          router.back();
        }
      } catch (error) {
        Alert.alert('Error', 'Gagal memuat data resi.');
        router.back();
      } finally {
        setIsLoading(false);
      }
    };

    if (resi) fetchPesanan();
  }, [resi]);

  // --- HELPER MASKING ---
  const maskText = (text, keepFirst = 3, keepLast = 3) => {
    if (!text || text === '-') return '-';
    const str = text.trim();
    const len = str.length;
    if (len <= 4) return str.substring(0, 1) + '*'.repeat(len - 1);
    if (len <= (keepFirst + keepLast)) { keepFirst = 1; keepLast = 1; }
    return str.substring(0, keepFirst) + '*'.repeat(len - keepFirst - keepLast) + str.substring(len - keepLast);
  };

  if (isLoading || !pesanan) {
    return (
      <View style={styles.center}>
        <ActivityIndicator size="large" color="#dc2626" />
        <Text style={{ marginTop: 10 }}>Memuat Label Resi...</Text>
      </View>
    );
  }

  // --- PREPARE DATA ---
  const expeditionParts = (pesanan.expedition || '').split('-');
  const expName = (expeditionParts[1] || 'sancaka').toUpperCase();
  const expService = (expeditionParts[2] || 'regular').toUpperCase();
  const pm = String(pesanan.payment_method || '').toUpperCase();
  const isCodBarang = pm === 'CODBARANG' || pm === '#COD_BARANG';
  const isCodOngkir = pm === 'COD' || pm === '#COD_ONGKIR';
  const showCodBlock = isCodBarang || isCodOngkir;
  const labelCod = isCodBarang ? 'NILAI COD (BARANG + ONGKIR)' : 'NILAI COD (ONGKIR)';
  const displayPayment = (pm === 'POTONG SALDO' || pm === '#SALDO') ? 'SALDO / CASH' : pesanan.payment_method;

  // Render HTML String sesuai template web Anda (dinamis)
  const htmlContent = `
    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Cetak Resi</title>
        <script src="https://cdn.tailwindcss.com"></script>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;900&display=swap" rel="stylesheet">
        <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/qrcodejs/qrcode.min.js"></script>
        <style>
            body { font-family: 'Inter', sans-serif; background-color: #F3F4F6; color: #111827; margin: 0; padding: 10px; }
            .page { width: 100mm; min-height: 150mm; padding: 6mm; margin: 0 auto; background: #fff; border-radius: 6px; box-shadow: 0 4px 12px rgba(0,0,0,0.08); display: flex; flex-direction: column; font-size: 8pt; }
            .barcode { width: 100%; height: 50px; margin-top: 5px; margin-bottom: 5px; }
            .label { font-weight: 600; font-size: 12px; color: #374151; }
            .value { font-weight: 500; font-size: 9px; }
        </style>
    </head>
    <body>
        <div class="page" id="label-resi">
            <div class="flex justify-between items-center border-b border-gray-700 pb-2">
                <img src="https://tokosancaka.com/storage/uploads/sancaka.png" alt="Sancaka" class="h-10" onerror="this.style.display='none'">
                <img src="https://tokosancaka.com/public/storage/logo-ekspedisi/${expName.toLowerCase().replace(/\s+/g, '')}.png" class="h-8 object-contain" onerror="this.style.display='none'">
            </div>

            <div class="text-center mt-2">
                <p class="font-bold text-sm tracking-wide"><strong>NOMOR RESI TOKOSANCAKA.COM</strong></p>
                <svg id="barcodeSancaka" class="barcode"></svg>
            </div>

            <div class="grid grid-cols-2 gap-3 mt-2 border-b border-gray-700 pb-2">
                <div class="pr-2 border-r border-gray-300">
                    <p class="label"><strong>PENGIRIM:</strong></p>
                    <p class="value">${maskText(pesanan.sender_name)}</p>
                    <p class="text-xs">${maskText(pesanan.sender_phone)}</p>
                    <p class="text-[8px] leading-snug mt-1">${[pesanan.sender_address, pesanan.sender_village, pesanan.sender_district, pesanan.sender_regency, pesanan.sender_province].filter(Boolean).join(', ')}</p>

                    <div class="mt-2 pt-2 border-t border-dashed border-gray-300">
                        <p class="label"><strong>Rincian Paket:</strong></p>
                        <p class="value">- Berat: ${pesanan.weight} Gram</p>
                        <p class="value">- Harga Brg: Rp ${Number(pesanan.item_price).toLocaleString('id-ID')}</p>
                        <p class="value">- Isi: ${pesanan.item_description}</p>
                        <p class="value">- Dimensi: ${pesanan.length || 0}x${pesanan.width || 0}x${pesanan.height || 0} cm</p>
                        <p class="value">- Layanan: ${expService}</p>
                        <br>
                        ${showCodBlock ? `
                            <p class="label text-gray-700"><strong>${labelCod}:</strong></p>
                            <p class="value text-gray-700 text-lg mb-0"><strong>Rp ${Number(pesanan.price).toLocaleString('id-ID')}</strong></p>
                            ${isCodOngkir ? `<p class="text-[8px] italic mt-0 font-bold text-red-600 mb-2">(JANGAN TAGIH HARGA BARANG)</p>` : ''}
                        ` : `
                            <p class="label text-green-600"><strong>Total Ongkir:</strong></p>
                            <p class="value text-red-600 text-lg mb-2"><strong>Rp ${Number(pesanan.shipping_cost).toLocaleString('id-ID')}</strong></p>
                        `}
                    </div>
                </div>

                <div class="pl-2">
                    <p class="label"><strong>PENERIMA:</strong></p>
                    <p class="value">${maskText(pesanan.receiver_name)}</p>
                    <p class="text-xs">${maskText(pesanan.receiver_phone)}</p>
                    <p class="text-[8px] leading-snug mt-1">${[pesanan.receiver_address, pesanan.receiver_village, pesanan.receiver_district, pesanan.receiver_regency, pesanan.receiver_province].filter(Boolean).join(', ')}</p>

                    <div class="flex justify-center mt-4">
                        <div class="border border-gray-400 rounded-md p-2 inline-block"><div id="qrcode"></div></div>
                    </div>
                    <p class="flex justify-center mt-1 mb-1 text-[9px]"><strong>TRACKING ME</strong></p>
                    <p class="value text-center text-[8px]">CV. SANCAKA KARYA HUTAMA<br>Helpdesk: 08574580809</p>
                </div>
            </div>

            <div class="grid grid-cols-3 gap-2 text-center mt-2 border-b border-gray-700 pb-2">
                <div><p class="label text-[9px]"><strong>RESI</strong></p><p class="value text-[8px]">${pesanan.nomor_invoice}</p></div>
                <div><p class="label text-[9px]"><strong>BERAT</strong></p><p class="value text-[8px]">${pesanan.weight} Gr</p></div>
                <div><p class="label text-[9px]"><strong>VOLUME</strong></p><p class="value text-[8px]">${pesanan.length || 0}x${pesanan.width || 0}x${pesanan.height || 0}</p></div>
                <div><p class="label text-[9px]"><strong>LAYANAN</strong></p><p class="value text-[8px]">${expService}</p></div>
                <div><p class="label text-[9px]"><strong>EKSPEDISI</strong></p><p class="value text-[8px]">${expName}</p></div>
                <div><p class="label text-[9px]"><strong>BAYAR</strong></p><p class="value text-[8px]">${displayPayment}</p></div>
            </div>

            ${pesanan.resi_aktual ? `
            <div class="text-center mt-3 pt-2 border-t border-gray-700">
                <p class="label">RESI AKTUAL (${pesanan.jasa_ekspedisi_aktual || expName})</p>
                <svg id="barcodeAktual" class="barcode"></svg>
            </div>
            ` : ''}

            <div class="mt-auto pt-3 text-center text-[8px]">
                <p>Terima kasih menggunakan <strong>Sancaka Express</strong>.</p>
                <p class="font-bold mt-1">${pesanan.created_at} Kirim Paket DI TOKOSANCAKA.COM</p>
            </div>
        </div>

        <script>
            document.addEventListener("DOMContentLoaded", function() {
                // Generate 1D Barcode
                JsBarcode("#barcodeSancaka", "${pesanan.resi}", { format: "CODE128", width: 2, height: 40, fontSize: 16 });
                ${pesanan.resi_aktual ? `JsBarcode("#barcodeAktual", "${pesanan.resi_aktual}", { format: "CODE128", width: 2, height: 40, fontSize: 16 });` : ''}

                // Generate 2D QRCode
                new QRCode(document.getElementById("qrcode"), { text: "https://tokosancaka.com/tracking/search?resi=${pesanan.resi}", width: 60, height: 60 });
            });
        </script>
    </body>
    </html>
  `;

  return (
    <SafeAreaView style={styles.safeArea}>
      {/* HEADER ACTIONS */}
      <View style={styles.actionHeader}>
        <TouchableOpacity style={styles.backBtn} onPress={() => router.back()}>
          <Ionicons name="arrow-back" size={24} color="#1f2937" />
        </TouchableOpacity>
        <View style={styles.actionButtons}>
          <TouchableOpacity style={[styles.btn, styles.btnRed]}>
            <Ionicons name="print" size={16} color="white" />
            <Text style={styles.btnText}>Cetak Thermal</Text>
          </TouchableOpacity>
        </View>
      </View>

      {/* WEBVIEW AREA */}
      <WebView
        source={{ html: htmlContent }}
        style={{ flex: 1, backgroundColor: '#f3f4f6' }}
        showsVerticalScrollIndicator={false}
        originWhitelist={['*']}
      />
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  safeArea: { flex: 1, backgroundColor: '#ffffff' },
  center: { flex: 1, justifyContent: 'center', alignItems: 'center' },
  actionHeader: { flexDirection: 'row', backgroundColor: 'white', paddingHorizontal: 15, paddingVertical: 12, alignItems: 'center', borderBottomWidth: 1, borderColor: '#e5e7eb', justifyContent: 'space-between' },
  backBtn: { padding: 5 },
  actionButtons: { flexDirection: 'row', gap: 8 },
  btn: { flexDirection: 'row', alignItems: 'center', paddingHorizontal: 16, paddingVertical: 10, borderRadius: 6, gap: 6 },
  btnRed: { backgroundColor: '#dc2626' },
  btnText: { color: 'white', fontSize: 14, fontWeight: 'bold' }
});
