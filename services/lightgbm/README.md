# Layanan LightGBM Wijaya Kusuma

Model dan fitur mengikuti PKL/metadata Colab pada folder `docs`. Dokumentasi pemakaian, pemasangan ulang, ekspor, status prediksi dan pengujian berada di [panduan integrasi](../../docs/LightGBM_Integrasi_Flask.md); [kamus data](../../docs/LightGBM_Kamus_Data.md) menjelaskan setiap kolom. Kontrak API tersimpan di [openapi.json](openapi.json).

Model aktif berada di `services/lightgbm/models/`: `lightgbm_3d.pkl`, `lightgbm_7d.pkl`, `lightgbm_14d.pkl`, `lightgbm_21d.pkl` untuk lead time, serta `lightgbm_30d.pkl`, `lightgbm_60d.pkl`, `lightgbm_90d.pkl` untuk proyeksi. Setiap model didampingi `metadata_Hd.json`; `manifest.json` mencatat checksum. Sumbernya adalah ZIP terbaru di `docs/Hasil_LightGBM_ARIMA_Wijaya_Kusuma_Lead_Time_dan_Proyeksi.zip`.

Jalankan dari direktori proyek:

```powershell
.\.venv-lightgbm\Scripts\python.exe services\lightgbm\serve.py
```

Kesiapan: `http://127.0.0.1:5001/health`. Halaman Laravel: `/inventory-analysis/lightgbm`. Proses Waitress perlu berjalan selama halaman mengakses model.

Terminal menampilkan proses pemuatan model dan pesan `Serving on http://127.0.0.1:5001`, lalu tetap menunggu permintaan. Prompt PowerShell tidak kembali selama server berjalan; ini normal. Biarkan terminal terbuka dan gunakan terminal lain untuk Laravel. Tekan `Ctrl+C` untuk menghentikan server. Jalankan satu proses saja pada port yang sama.
