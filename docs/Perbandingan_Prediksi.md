# Perbandingan akurasi prediksi

Menu **Perbandingan Prediksi** tersedia di `/forecast-comparison` untuk pengguna yang sudah login. Admin dapat membatasi cabang atau menilai seluruh cabang. Pengguna cabang selalu dibatasi ke cabangnya sendiri, termasuk ketika parameter URL diubah.

Halaman membandingkan SMA, WMA, ARIMA dan LightGBM menggunakan MAE, RMSE dan MAPE. Pengguna dapat memilih lead time 3/7/14/21 hari atau proyeksi 30/60/90 hari, kategori, kadar, tanggal potong, pencarian kelompok dan metrik pengurutan. Peringkat dan ketiga metrik dihitung ulang pada observasi hasil filter yang sama untuk semua metode. Nilai lebih rendah menunjukkan galat lebih kecil. Nilai seri berbagi peringkat; halaman tidak memaksakan satu pemenang.

## Data yang dinilai

Sumber adalah seluruh periode uji Colab, dataset harian `storage/app/private/dataset-forecast.csv`, PKL terlatih, serta parameter yang sudah dipilih pada validasi dalam paket terbaru `docs/Hasil_LightGBM_ARIMA_Wijaya_Kusuma_Lead_Time_dan_Proyeksi.zip`. Notebook di `docs` menjadi referensi metodologi, sedangkan pilihan parameter dan hasil pembanding mengikuti laporan dalam ZIP terbaru. Data bersifat **sintetis**. Hasil bukan ukuran akurasi transaksi riil atau jaminan kinerja pada periode mendatang.

Script rekonstruksi tidak melatih ulang LightGBM atau memilih parameter berdasarkan data uji. Ia memuat PKL asli, memakai jendela SMA/WMA dan orde ARIMA pilihan validasi, lalu memperbarui keadaan prediksi dengan informasi sampai tanggal potong. Fungsi referensi yang disalin dari notebook tersimpan dalam `scripts/forecast/colab_comparison.py` dan dapat ditinjau tanpa menjalankan kode pelatihan atau unduhan notebook.

Evaluasi mencakup **616.420 observasi** pada 340 kelompok:

| Horizon | Observasi | Tanggal potong |
| --- | ---: | --- |
| 3 hari | 102.340 | 1 Oktober 2025–28 Juli 2026 |
| 7 hari | 100.980 | 1 Oktober 2025–24 Juli 2026 |
| 14 hari | 98.600 | 1 Oktober 2025–17 Juli 2026 |
| 21 hari | 96.220 | 1 Oktober 2025–10 Juli 2026 |
| 30/60/90 hari | masing-masing 72.760 | 1 Oktober 2025–2 Mei 2026 |

Horizon proyeksi memakai tanggal potong yang sama sesuai eksperimen Colab. Semua target selesai dalam periode uji yang berakhir 31 Juli 2026.

Sebelum diterbitkan, jumlah kasus dan MAE/RMSE setiap metode/horizon harus cocok dengan laporan Colab dalam toleransi 0,00001. Jika tidak cocok, proses gagal dan hasil yang lama dipertahankan. CSV diterbitkan bersama manifest yang mencatat checksum sumber/hasil, varian metode, periode, versi model, metrik dan selisih terhadap laporan asli. Laravel memeriksa checksum sebelum membaca hasil. Pembacaan CSV berlangsung secara streaming; detail hanya disimpan untuk halaman yang sedang dibuka.

SMA90 dipilih untuk 3/7/30/60/90 hari dan SMA60 untuk 14/21 hari. WMA90 dipakai untuk seluruh horizon. Orde ARIMA pilihan validasi adalah ARIMA(1,0,0) untuk 3/7 hari serta ARIMA(1,0,1) untuk 14/21/30/60/90 hari. ARIMA evaluasi ini memakai estimasi conditional least squares Colab, sedangkan halaman operasional ARIMA menggunakan batch statsmodels dengan aturan estimasi tersendiri. Keterangan ini juga ada pada halaman. Perbandingan yang ditampilkan berlaku untuk konfigurasi eksperimen tersebut.

## Definisi metrik

MAE = rata-rata nilai absolut (prediksi − aktual), dalam unit. RMSE = akar rata-rata kuadrat (prediksi − aktual), dalam unit. MAPE = 100 × rata-rata nilai absolut (prediksi − aktual)/aktual, dalam persen, hanya pada kasus dengan aktual > 0. Tidak ada pembulatan prediksi sebelum penghitungan metrik.

Aktual nol tetap dihitung dalam MAE/RMSE. Kasus tersebut tidak memiliki persentase galat yang terdefinisi sehingga dikecualikan dari MAPE untuk **seluruh metode**. Jumlah kasus MAPE dan pengecualian ditampilkan. Jika seluruh aktual nol, MAPE ditampilkan sebagai tidak terdefinisi dan tidak ada pemenang MAPE. Kasus prediksi tidak lengkap dikeluarkan untuk seluruh metode sehingga tidak ada metode yang diuntungkan oleh perbedaan cakupan. Dasar pengukuran merujuk [Forecasting Principles and Practice](https://otexts.com/fpp3/accuracy.html).

Sebagai pemeriksaan hitungan tangan: aktual [10, 0, 20] dan prediksi [12, 5, 15] menghasilkan MAE 4, RMSE √18 dan MAPE 22,5%, dengan dua kasus MAPE. Kasus nol tidak diubah menjadi galat nol.

## Membangun hasil dan menjalankan pengujian

```powershell
php artisan forecast:build-comparison
# Jika memakai panel harian lain yang konsisten dengan eksperimen:
php artisan forecast:build-comparison --dataset=storage/app/private/dataset-forecast.csv
```

Perintah memakai `.venv-lightgbm` dan tidak memerlukan Flask hidup karena ini evaluasi historis dari model tersimpan. `LIGHTGBM_PYTHON` dapat mengubah executable Python. Penggantian dataset yang tidak cocok dengan laporan akan ditolak; untuk eksperimen baru, siapkan laporan, notebook dan model baru yang konsisten terlebih dahulu.

Hasil tersimpan pada `storage/app/private/forecast-comparison.csv` dan `.json`, di luar direktori publik. Tidak ada data pelanggan, barcode atau data stok individual. Halaman membaca hasil ini tanpa melakukan pelatihan atau memanggil Flask pada setiap permintaan.

```powershell
php vendor/bin/phpunit tests/Unit/ForecastErrorMetricsTest.php tests/Feature/ForecastComparisonTest.php
.\.venv-lightgbm\Scripts\python.exe -m unittest discover -s scripts/forecast -p test_comparison.py -v
```

Pengujian mencakup hitungan metrik, aktual nol, data kosong, peringkat seri, filter/paginasi, kelompok tanpa prediksi lengkap, integritas berkas, login dan isolasi cabang. Uji Python mengubah aktual setelah tanggal potong dan memastikan fitur serta prediksi SMA/WMA/ARIMA tidak ikut berubah, sementara target berubah sebagaimana mestinya.
