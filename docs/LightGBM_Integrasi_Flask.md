# Integrasi LightGBM Laravel dan Flask

Halaman SMA, WMA, ARIMA dan LightGBM hanya menampilkan ramalan metode masing-masing. Penggantian otomatis ke SMA pada halaman ARIMA/LightGBM dinonaktifkan. Jika hasil belum tersedia, ramalan terkait kosong dengan alasan; ROP dan Siapkan ditunda bila ramalan lead time belum tersedia. Konfigurasi data demo memakai lead time bervariasi 3, 7, 14, dan 21 hari.

Sistem memakai tujuh PKL asli dari paket terbaru `docs/Hasil_LightGBM_ARIMA_Wijaya_Kusuma_Lead_Time_dan_Proyeksi.zip`. Model tidak dilatih ulang dalam integrasi ini. Metadata, contoh prediksi, dan laporan CSV pada ZIP terbaru menjadi acuan; panduan dan notebook di `docs` tetap menjadi referensi proses eksperimen.

## Model yang digunakan

| Model | Fungsi sesuai metadata | Versi |
| --- | --- | --- |
| `lightgbm_3d.pkl` | Lead time tepat 3 hari | `wk-lightgbm-3d-v2` |
| `lightgbm_7d.pkl` | Lead time tepat 7 hari | `wk-lightgbm-7d-v2` |
| `lightgbm_14d.pkl` | Lead time tepat 14 hari | `wk-lightgbm-14d-v2` |
| `lightgbm_21d.pkl` | Lead time tepat 21 hari | `wk-lightgbm-21d-v2` |
| `lightgbm_30d.pkl` | Proyeksi 30 hari | `wk-lightgbm-30d-v2` |
| `lightgbm_60d.pkl` | Proyeksi 60 hari | `wk-lightgbm-60d-v2` |
| `lightgbm_90d.pkl` | Proyeksi 90 hari | `wk-lightgbm-90d-v2` |

Semua model terpilih menggunakan `LightGBM_riwayat_jadwal_idulfitri`. Riwayat input tetap 90 hari walaupun pengguna memilih proyeksi 30 atau 60 hari. Harga ANTAM bukan fitur PKL terpilih ini. Data latih ditandai **sintetis**; hasilnya bukan bukti akurasi transaksi riil toko. Perbandingan ARIMA dan gabungan WMA–LightGBM 50:50 tetap merupakan eksperimen di notebook/CSV. PKL ARIMA tidak ada dalam arsip; halaman ARIMA Laravel tetap menggunakan batch statsmodels yang sudah ada, dengan aturan estimasi berbeda dari eksperimen notebook.

## Menjalankan layanan

Environment `.venv-lightgbm` sudah disiapkan terpisah dari `.venv-forecast`. Dari direktori proyek:

```powershell
.\.venv-lightgbm\Scripts\python.exe services\lightgbm\serve.py
```

Layanan memakai Waitress dan hanya mendengarkan `127.0.0.1:5001` secara bawaan. Buka `http://127.0.0.1:5001/health`; hasil sehat adalah HTTP 200 dengan `ready: true` dan tujuh model. Setelah komputer atau proses berhenti, jalankan kembali perintah tersebut. Pada server operasional, kelola proses Waitress sebagai service dengan restart otomatis. Ini mengikuti [panduan deployment Flask](https://flask.palletsprojects.com/en/stable/deploying/waitress/).

Konfigurasi Laravel tersedia di `config/forecast.php` dan `.env.example`:

```dotenv
LIGHTGBM_URL=http://127.0.0.1:5001
LIGHTGBM_CONNECT_TIMEOUT=1
LIGHTGBM_TIMEOUT=5
LIGHTGBM_CACHE_SECONDS=3600
```

Jika URL diubah, jalankan `php artisan config:clear`. Laravel memakai [HTTP client dengan connect timeout dan request timeout](https://laravel.com/framework/docs/13.x/http-client#timeout). Flask dapat memakai `LIGHTGBM_HOST`, `LIGHTGBM_PORT` dan `LIGHTGBM_MODEL_DIR` dari environment prosesnya sendiri; `.env` Laravel tidak otomatis dibaca Flask.

Untuk pemasangan ulang, buat venv dengan Python 3.12 atau 3.13, pasang `services/lightgbm/requirements.lock.txt`, lalu jalankan:

```powershell
.\.venv-lightgbm\Scripts\python.exe services\lightgbm\import_models.py
php artisan migrate
```

Importer menyalin tujuh pasangan PKL/metadata dan fixture dari ZIP; kode Python di dalam ZIP tidak dijalankan. Model aktif berada di `D:\project\toko-emas-wijaya-kusuma\services\lightgbm\models`, di luar direktori publik. Folder tersebut berisi `lightgbm_Hd.pkl`, `metadata_Hd.json` untuk H = 3/7/14/21/30/60/90, dan `manifest.json` berisi SHA-256 setiap berkas. Flask memuat model sekali saat mulai, memeriksa checksum, versi paket, kesamaan metadata dalam PKL/JSON, serta urutan fitur Booster. PKL tidak dimasukkan ke Git; ZIP sumber dipertahankan untuk pemasangan ulang. Setelah mengimpor model pengganti, restart layanan Flask dan jalankan `php artisan cache:clear` agar cache prediksi lama tidak digunakan, termasuk bila paket mempertahankan nama versi model.

Metadata Colab mencatat Python 3.13.18. Runtime lokal yang diuji adalah Python 3.12.14, dengan versi LightGBM 4.6.0, NumPy 2.4.6, pandas 2.2.3 dan SciPy 1.16.3 sama persis dengan metadata. Fixture menguji kompatibilitas numeriknya; kecocokan versi Python tidak diasumsikan tanpa pemeriksaan prediksi.

## Pemakaian di aplikasi

Buka menu **Analisis LightGBM** (`/inventory-analysis/lightgbm`). Admin memiliki **Konfigurasi LightGBM** terpisah. Migrasi menyalin konfigurasi SMA sebagai nilai awal. Lead time 3/7/14/21 hari memakai PKL masing-masing secara langsung. Jika dikonfigurasi ke horizon lain, ramalan lead time akan ditandai belum tersedia.

Tabel menampilkan ramalan selama lead time dan proyeksi sesuai jendela 30/60/90 hari. Tampilan ramalan lead time, proyeksi, dan jumlah harian dibulatkan ke unit terdekat; nilai desimal asli dipakai untuk `ROP = ceil(ramalan_lead_time + safety_stock)`. `Siapkan = max(0, ROP - stok tersedia)`. Proyeksi tidak dipakai untuk ROP. Tabel perbandingan juga menampilkan prediksi dalam unit bulat, sedangkan metrik akurasi tetap dihitung dari nilai asli. Metode halaman dan alasan ketidaktersediaan ditampilkan untuk masing-masing ramalan; versi model tersedia pada tooltip metode.

Setiap model hanya dipakai pada horizon dan `forecast_uses` yang dilatih. Model 30 hari bertanda `projection` tidak dialihkan menjadi model lead time 30 hari. Keempat model lead time digunakan sesuai horizon aslinya; tidak ada perkalian atau interpolasi dari model 14 hari.

## Fitur dan kalender

`LightgbmFeatureBuilder` dipakai bersama oleh inferensi dan `data:export-lightgbm`. Identitas cabang berasal dari `kode_cabang`, kategori dari nama kategori yang dinormalisasi, dan kadar harus berupa `8K`–`24K`. Pemetaan numerik berasal dari metadata, bukan ID database. `days_since_last_sale` menghitung penjualan valid terakhir sampai tanggal potong, termasuk jika lebih lama dari 180 hari. Informasi setelah tanggal potong tidak masuk fitur penjualan.

Hari tutup dihitung nol; hari buka tanpa pencatatan lengkap bernilai hilang dan menunda ramalan yang memerlukan riwayat tersebut. Jadwal mendatang memakai kalender cabang yang tersimpan bila ada, atau aturan bersama `BranchCalendar`. Semua cabang tutup pada hari H Idulfitri dan buka pada H−21..H−1 serta H+1..H+21. Purwonegoro tutup Sabtu; Mandiraja dan Sumpiuh tutup Minggu; Desa buka setiap hari di luar pengecualian tersebut.

Tanggal 2027 ditambahkan sebagai **tanggal perencanaan 10 Maret 2027**, berdasarkan [pengumuman resmi Kemnaker tanggal 15 September 2026](https://kemnaker.go.id/news/detail/pemerintah-tetapkan-18-hari-libur-nasional-dan-8-hari-cuti-bersama-tahun-2027). `EID_AVAILABLE_FROM` mencegah pemakaiannya sebelum tanggal publikasi. Seperti tanggal kalender perencanaan lainnya, perbarui bila penetapan resmi berubah. Tahun berikutnya yang belum memiliki sumber resmi menunda ramalan terkait; sistem tidak menebak Idulfitri.

## Kontrak API dan cache

Sumber kontrak adalah `services/lightgbm/openapi.json`, dengan contoh permintaan dan respons asli Colab. `GET /health` memeriksa kesiapan. `POST /predict` menerima `schema_version`, `model_version`, `as_of_date`, `horizon_days`, `forecast_use`, serta `groups` berisi `group_key` dan objek `features` sesuai metadata. Maksimum satu batch 500 kelompok. Model menolak fitur tambahan, nilai wajib hilang, kode kategori/kadar baru, bilangan tidak finite, tanggal tidak sah dan penggunaan horizon yang salah.

JSON rusak menghasilkan 400; kontrak tidak cocok menghasilkan 422; model belum siap atau inferensi gagal menghasilkan 503. Respons sukses berisi semua identitas permintaan dan tepat satu `predicted_units` desimal nonnegatif per kelompok. Tidak ada prediksi parsial di Flask.

Laravel hanya mengirim kelompok pada cabang yang boleh diakses pengguna. Respons diperiksa untuk versi, tanggal, horizon, fungsi, cakupan, duplikasi, identitas dan nilai prediksi sebelum satu pun hasil batch disimpan. Cache menyertakan kelompok, tanggal, horizon, fungsi, metadata/versi, fitur dan hash riwayat. Isi cache diperiksa kembali saat dibaca. Perubahan input membentuk kunci baru. Hasil gagal tidak dicache sebagai prediksi berhasil.

Setiap halaman menggunakan metode masing-masing. Model horizon/fungsi belum tersedia, kalender belum diketahui, kode cabang/kategori/kadar baru, riwayat 90 hari kurang lengkap, timeout, HTTP gagal, serta respons yang salah atau tidak lengkap membuat ramalan terkait ditandai belum tersedia. Hasil metode lain tidak menggantikan ramalan tersebut. ROP dan Siapkan hanya dihitung ketika ramalan lead time dari metode halaman tersedia; proyeksi yang tersedia tetap ditampilkan secara independen.

## Ekspor dan notebook

```powershell
php artisan data:export-lightgbm --from=2023-01-01 --to=2026-11-12 --horizon=14 --output=storage/app/private/dataset-lightgbm.csv
```

Pilihan tambahan: `--branch=ID`; `--horizon=3`, `7`, `14`, `21` untuk lead time atau `30`, `60`, `90` untuk proyeksi. Tanggal awal/akhir wajib eksplisit dan sebelum hari berjalan. Ekspor menyertakan fitur model, target untuk evaluasi, kolom panel harian dan audit harga. Tidak ada identitas pelanggan, barcode, stok, safety stock atau ROP. Identitas kelompok hanya diterbitkan bila produk atau transaksi kelompok sudah diketahui pada tanggal itu. Baris fitur yang belum lengkap tetap ditandai melalui `feature_error` dan kolom kosong; jangan diisi nol untuk mengakali kelengkapan. Target yang belum selesai atau aktualnya belum lengkap tetap kosong. Kamus kolom berada di `LightGBM_Kamus_Data.md`.

Contoh target yang diuji: pada tanggal potong 28 Februari 2026 terdapat 2 unit penjualan historis; 3 unit valid terjual 1 Maret dan 4 unit nota batal pada 2 Maret. `sales_14d = 2`, sedangkan `target_sales_14d = 3` untuk 1–14 Maret. Penjualan batal dan unit pada tanggal potong tidak masuk target. Harga 1 Maret juga tidak masuk fitur/audit harga 28 Februari.

Notebook yang diberikan sudah melatih model, mengevaluasi varian fitur dan gabungan 50:50, serta mengekspor ZIP ini. Untuk menjalankannya kembali di Colab, gunakan runtime CPU dan jalankan sel dari atas ke bawah; unggah satu CSV/CSV.GZ pada sel dataset. Pilih dataset harian lengkap yang sama untuk perbandingan, bukan hanya fixture satu baris. Loader notebook mensyaratkan panel kelompok/tanggal lengkap: jika kelompok baru belum ada pada awal periode atau pencatatan hilang, sesuaikan periode/kelompok eksperimen dengan informasi yang tersedia, tanpa membuat nol palsu. Hapus baris riwayat 90 hari yang belum lengkap dari komparasi utama. Pertahankan pembagian latih 2023–2024, validasi Januari–September 2025 dan uji Oktober 2025–Juli 2026; target harus selesai dalam split yang sama.

Sel 11–13 menyimpan metadata, PKL, evaluasi dan ZIP ke penyimpanan sesi lalu mengunduhnya. Simpan ZIP unduhan di luar runtime Colab yang sementara. Notebook asli hanya mematok LightGBM di sel pemasangan; bila melatih ulang untuk reproduksi, pin juga NumPy/pandas/SciPy sesuai metadata dan simpan versi runtime baru. Untuk mengaktifkan hasil baru, ganti arsip lokal yang telah ditinjau, impor ulang, ubah versi model pada ekspor bila bobot berubah, restart Waitress dan jalankan pengujian fixture sebelum menggunakan hasilnya.

## Pengujian

```powershell
.\.venv-lightgbm\Scripts\python.exe -m unittest discover -s services/lightgbm/tests -v
$env:LIGHTGBM_LIVE_URL = 'http://127.0.0.1:5001'
php vendor/bin/phpunit tests/Feature/LightgbmIntegrationTest.php
```

PHPUnit mengikuti database khusus pengujian dalam `phpunit.xml`; jangan arahkan pengujian `RefreshDatabase` ke database operasional. SQLite in-memory juga dapat dipakai dengan extension PDO SQLite dan override environment database. Pengujian live membuat transaksi fixture di database pengujian, membentuk fitur Laravel, memanggil Flask melalui HTTP asli, lalu membandingkan hasil PKL dengan Colab. Tanpa `LIGHTGBM_LIVE_URL`, hanya tes live tersebut yang dilewati.

Uji Python memuat tujuh PKL asli dan membandingkan hasil terhadap fixture ekspor hingga toleransi `1e-8`, termasuk keempat lead time. Uji Laravel mencakup kesamaan fitur ekspor/API, target tanpa kebocoran waktu, ROP, cache, ekspor keempat lead time, horizon tidak didukung, kalender belum tersedia, fitur hilang, cabang baru, batas akses, identitas respons, duplikasi, hasil parsial, HTTP 503 dan exception timeout. Tes live membandingkan prediksi keempat lead time dari transaksi fixture terhadap Colab. Kegagalan HTTP pada unit integrasi memakai fake berlabel fixture; pengujian HTTP asli dinyatakan terpisah. Postman CLI pada mesin ini tidak dapat berjalan karena kebijakan Private Cloud manifest, sehingga pengujian tidak mengklaim hasil Postman Simulation.

Pemeriksaan data demo seluruh cabang melalui layanan nyata:

```powershell
php scripts/check_lightgbm_status.php 2026-10-31 2026-11-13
```

Laporan tersimpan di `storage/app/private/lightgbm-status.json`, meliputi kesehatan tujuh model, jumlah kelompok yang memiliki ramalan lead time/proyeksi, distribusi lead time, dan alasan bila hasil belum tersedia.

Verifikasi paket terbaru: 5 tes Python dan 7 tes Laravel lulus (180 assertions Laravel), termasuk HTTP asli dan perbandingan Colab untuk 3/7/14/21 hari. Pada tanggal potong 31 Oktober serta 13 November 2026, seluruh 340 kelompok memiliki ramalan lead time, proyeksi 30/60/90, dan ROP; tidak ada kelompok yang belum tersedia.

Catatan verifikasi implementasi awal, sebelum penyeragaman lead time dan pemisahan hasil per metode: seluruh 89 tes Laravel lulus (1.302 assertions), termasuk tes HTTP asli Laravel–Flask; empat tes Python lulus; contoh request/response OpenAPI cocok dengan prediksi PKL; pemeriksaan format PHP dan build Vite berhasil. Pada database aplikasi dengan tanggal potong 12 November 2026, masing-masing proyeksi 30/60/90 hari berhasil memakai LightGBM untuk seluruh 340 kelompok. Sebanyak 17 kelompok memiliki lead time tepat 14 hari dan memakai LightGBM untuk ROP, sementara 323 kelompok lain memakai fallback SMA sesuai konfigurasi lead time. Contoh Anting 16K Cabang Desa, jendela 30 hari dan lead time 20 hari: ramalan lead time SMA 27,333333 unit, proyeksi LightGBM 30 hari 35,856895 unit, ROP 33 dan Siapkan 31. Ini memverifikasi alur integrasi pada data sintetis aplikasi, bukan evaluasi akurasi transaksi riil.
