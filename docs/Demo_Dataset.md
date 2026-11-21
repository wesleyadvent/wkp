# Dataset demo operasional

Database aktif mengikuti `.env` (`wkp_demo` saat pengisian 14 November 2026).
Seluruh migrasi sudah dijalankan dan data langsung tersedia di database.

## Akun

Semua akun menggunakan password `password`.

| Peran / cabang | Email |
| --- | --- |
| Admin | admin@wijayakusuma.test |
| Purwonegoro | purwonegoro@wijayakusuma.test |
| Mandiraja | mandiraja@wijayakusuma.test |
| Sumpiuh | sumpiuh@wijayakusuma.test |
| Desa | desa@wijayakusuma.test |

## Isi dan pola data

- Riwayat 365 hari penuh: 14 November 2025 sampai 13 November 2026, berakhir pada kemarin agar sesuai tanggal potong analisis aplikasi.
- Setiap cabang memiliki 500 pelanggan dan tepat 600 barang tersedia. Barang historis yang terjual dicatat sebagai unit tersendiri.
- Setiap hari setiap cabang memiliki sedikitnya 50 penjualan valid, 30 pembelian valid, dan 5 nota service.
- Lima kategori unik: Anting, Cincin, Kalung, Gelang, dan Liontin. Penyebutan Anting yang berulang pada permintaan memakai satu master kategori.
- Kadar mencakup 8K–24K, dengan jumlah stok dan permintaan berbeda antar kategori, kadar, dan cabang. Berat mengikuti jenis perhiasan.
- Permintaan memakai preferensi cabang, tren kategori, musim, akhir pekan, tanggal gajian, kenaikan sebelum Lebaran, serta variasi harian. Pemilihan kategori dan kadar memakai hash SHA-256 terpisah agar distribusinya tidak saling mengunci.
- Untuk memenuhi jumlah transaksi pada **setiap hari**, kalender demo menyimpan seluruh hari sebagai buka dan riwayat sebagai pencatatan lengkap. Kalender mendatang juga tersedia selama 30 hari.
- Foto seluruh barang disalin dari `docs/image.png` ke `storage/app/public/product-photos/perhiasan-emas.png` dan dapat diakses melalui `public/storage`.
- Kode barang mengikuti format dan singkatan `ProductCode`, contohnya `MDR-ANT-004`. Counter barang dan nota dilanjutkan dari data yang sudah dibuat agar transaksi baru lewat web tidak bertabrakan.
- Total transaksi, berat, ongkos, potongan pembelian, item layanan, dan snapshot nota disimpan konsisten dengan rincian masing-masing.

Harga 24K pada 14 November 2026 memakai acuan Rp2.517.000/gram dari [laporan harga Antam yang mengutip Logam Mulia](https://www.liputan6.com/bisnis/read/8335135/harga-emas-antam-hari-ini-14-november-2026-naik-rp-8000-cek-di-sini). Kadar lain dihitung proporsional; ongkos dan harga pembelian merupakan simulasi. Riwayat memakai CSV harga yang tersedia di proyek dan meneruskan harga terakhir jika tidak ada observasi baru; `tanggal_sumber` mencatat tanggal observasi sebenarnya.

## Lead time dan prediksi

Konfigurasi SMA, WMA, ARIMA, dan LightGBM diisi untuk setiap kombinasi cabang/kategori/kadar (340 kelompok, total 1.360 konfigurasi). Lead time bervariasi antara 3, 7, 14, dan 21 hari. Setiap kelompok memakai lead time serta safety stock yang sama antar metode agar bisa dibandingkan.

Paket model LightGBM terbaru sudah terpasang: lead time **3/7/14/21 hari** dan proyeksi **30/60/90 hari**, masing-masing memakai PKL sesuai horizon di `services/lightgbm/models`. Model asli dari ZIP dipakai tanpa pelatihan ulang. Layanan Flask sudah dimuat ulang untuk tujuh model. Batch ARIMA lengkap dapat dibuat dengan perintah aplikasi `php artisan forecast:arima`.

Pemeriksaan database aktif pada **20 November 2026** untuk tanggal potong **19 November 2026** menemukan perhitungan ARIMA yang belum selesai. Perhitungan kemudian dilanjutkan hingga seluruh **1.360 hasil** tersimpan (340 kelompok, masing-masing lead time sesuai konfigurasi serta horizon 30/60/90), tanpa batch yang masih kosong atau hash riwayat yang perlu diperbarui. Prediksi tersedia untuk **270 dari 340 kelompok** pada seluruh horizon. Riwayat kalender seluruh kelompok lengkap selama 180 hari, tetapi **70 kelompok** hanya memiliki 3–13 hari penjualan positif sehingga belum memenuhi syarat minimal 14 hari: Purwonegoro 24 kelompok, Mandiraja 18, Sumpiuh 14, dan Desa 14. Konfigurasi lead time serta safety stock seluruh 340 kelompok tetap terisi; distribusi lead time adalah 3 hari (83 kelompok), 7 hari (87), 14 hari (79), dan 21 hari (91). Hasil diverifikasi melalui `InventoryAnalysisService` dengan `php scripts/check_arima_status.php 2026-11-19`; rincian tersedia di `storage/app/private/arima-status.json` dan `storage/app/private/arima-history-audit.json`. Angka 338 kelompok pada pemeriksaan dataset sebelumnya tidak menggambarkan database aktif ini.

Batch harus sesuai tanggal potong, horizon, versi model, dan riwayat. Halaman ARIMA sekarang otomatis menghitung hasil yang belum tersedia atau riwayatnya berubah, satu kelompok per permintaan agar waktu respons tetap terbatas. Pilih tanggal potong lalu tekan Analisis dan biarkan halaman terbuka; progres ditampilkan dan halaman dimuat ulang setelah selesai. Seluruh horizon 30/60/90 hari serta lead time kelompok disimpan sekaligus dan digunakan kembali pada kunjungan berikutnya. Pengguna cabang hanya dapat menyiapkan data cabangnya sendiri. Permintaan pada tanggal yang sama tidak menjalankan model bersamaan.

Hasil dengan riwayat tidak mencukupi, deret konstan, timeout, atau model yang tidak konvergen tetap ditampilkan beserta alasannya dan tidak dihitung ulang terus-menerus untuk riwayat yang sama. Jika riwayat atau konfigurasi lead time berubah, hasil terkait dihitung ulang otomatis. Bila terjadi kegagalan proses, hasil yang sudah tersimpan tetap dipertahankan dan halaman menampilkan pesan untuk mencoba kembali. Perhitungan berikutnya melanjutkan kelompok yang belum selesai. Otomatisasi ini tidak mengisi transaksi atau mengonfirmasi pencatatan kalender yang belum lengkap.

Perhitungan dari halaman memerlukan JavaScript dan tidak membutuhkan queue worker atau scheduler. Jadwal harian di `routes/console.php` tetap tersedia untuk menyiapkan hasil kemarin pukul 07.00 dan memerlukan Laravel scheduler yang berjalan. Perintah `php artisan forecast:arima --cutoff=YYYY-MM-DD` tetap dapat digunakan administrator untuk menghitung ulang seluruh hasil secara paksa, termasuk hasil timeout atau model gagal.

## Verifikasi dan pengisian ulang

Jalankan pemeriksaan dataset dengan:

```powershell
php -d memory_limit=1024M scripts/verify_demo.php
```

Laporan terperinci disimpan di `storage/app/private/demo-verification.json`. Pemeriksaan mencakup jumlah harian, stok, akun, foto, distribusi, semua seri penjualan, audit nilai nota dan snapshot, analisis SMA/WMA, fitur LightGBM, serta tiga contoh prediksi ARIMA. Uji counter dilakukan dalam transaksi yang dibatalkan, sehingga nomor berikutnya tidak terpakai oleh verifikasi.

Untuk sengaja menghapus seluruh isi database aktif dan membuat dataset ini kembali:

```powershell
php -d memory_limit=1024M artisan migrate:fresh --seed --force
```

Seeder memakai 365 hari secara bawaan. `DEMO_HISTORY_DAYS` dapat menambah panjang riwayat; nilainya tidak boleh kurang dari 365 hari.

## Pelengkapan riwayat demo ARIMA, 20 November 2026

Sesudah pemeriksaan awal yang menemukan 70 kelompok belum memenuhi syarat pada tanggal terakhir, database `wkp_demo` dilengkapi dengan **1.521 unit penjualan simulasi dalam 243 nota tambahan**, tersebar pada 174 kelompok. Pelengkapan mencakup seluruh tanggal potong yang sudah memiliki setidaknya 90 hari riwayat, bukan hanya tanggal terakhir. Transaksi sebelumnya dipertahankan. Barang tambahan dibuat sebagai unit historis berstatus `terjual`, sehingga stok tersedia tetap **600 unit per cabang**.

Barang tambahan ditandai `creation_batch = demo-arima-coverage-v1` dan deskripsi `SIMULASI DEMO ARIMA`. Data ini khusus pengujian/demo dan tidak merepresentasikan penjualan nyata. Penjualan simulasi menambah nilai laporan penjualan pada tanggal terkait. Nomor nota, rincian barang, total transaksi, harga historis, dan snapshot nota tetap konsisten.

Audit memeriksa **93.840 kombinasi kelompok dan tanggal**: 340 kelompok pada 276 tanggal potong, dari **17 Februari 2026 sampai 19 November 2026**. Seluruhnya memenuhi minimal 90 hari pencatatan lengkap, minimal 14 hari penjualan positif, dan deret yang tidak konstan. Hari positif tidak harus berturut-turut. Kalender operasional tidak diubah dan tidak ada transaksi yang ditambahkan pada hari tutup. Batas minimal pada model ARIMA juga tidak diubah.

Batch tanggal potong **19 November 2026** sudah dihitung ulang: **1.360 hasil berstatus `ok`** untuk seluruh 340 kelompok, mencakup lead time masing-masing dan proyeksi 30/60/90 hari. Satu kelompok sempat timeout pada perhitungan awal dan berhasil saat diulang. Pemeriksaan melalui `InventoryAnalysisService` memastikan seluruh 340 kelompok memiliki prediksi lead time dan proyeksi pada ketiga pilihan periode, tanpa alasan ketidaktersediaan. Rincian akhir tersedia di `storage/app/private/arima-status.json`. Batch untuk tanggal lain tetap disiapkan otomatis saat tanggal tersebut dipilih di halaman ARIMA.

Backup lengkap sebelum penambahan tersedia di `storage/app/private/backups/wkp_demo-before-arima-coverage-20261120-232609.sql.gz` (34 tabel, 212.715 baris sebelum perubahan). Manifest ID seluruh nota, barang, dan item tambahan tersedia di `storage/app/private/arima-demo-coverage-20261120-232609.json`. Hasil audit rentang disimpan di `storage/app/private/arima-coverage-audit.json`. Audit konsistensi 20 jenis pemeriksaan tidak menemukan masalah setelah penambahan.

Perintah berikut secara bawaan hanya menyusun rencana, tanpa mengubah database:

```powershell
php artisan demo:arima-history
```

Untuk menerapkan pelengkapan setelah pengisian ulang demo, gunakan `php artisan demo:arima-history --apply-demo`. Perintah ini dibatasi pada database `wkp_demo` dan lingkungan `local`/`testing`, membuat backup terlebih dahulu, dan menyimpan perubahan dalam satu transaksi. Pemanggilan ulang pada riwayat yang sudah lengkap tidak menambah penjualan lagi. Opsi `--from=YYYY-MM-DD` dan `--through=YYYY-MM-DD` dapat mempersempit tanggal potong dalam riwayat yang tersedia; perintah tidak membuat kalender atau riwayat sebelum data yang tersedia.

Untuk mengulang audit seluruh rentang dan menghitung batch tanggal tertentu secara bertahap:

```powershell
php scripts/audit_arima_coverage.php
php scripts/prepare_arima_cutoff.php 2026-11-19
php scripts/prepare_arima_cutoff.php 2026-11-19 --retry-failed
```

Skrip persiapan hanya menghitung kelompok dengan batch belum tersedia atau hash riwayat berubah; `--retry-failed` juga mengulang hasil `timeout`/`error`. Halaman ARIMA tetap menyiapkan batch otomatis untuk tanggal yang dipilih. Kelayakan riwayat tidak menjamin setiap estimasi akan konvergen atau selesai sebelum batas waktu. Tanggal sebelum 17 Februari 2026 masih kekurangan riwayat 90 hari; tanggal setelah 19 November 2026 membutuhkan pencatatan lengkap yang baru. Prediksi pada tanggal potong tertentu hanya menggunakan riwayat sampai tanggal tersebut.
