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

Batch ARIMA sudah dihitung dan disimpan untuk tanggal potong **31 Oktober 2026** dan **13 November 2026**. Masing-masing memiliki 1.360 hasil (lead time sesuai konfigurasi serta horizon 30/60/90), dengan ramalan tersedia untuk 338 dari 340 kelompok. Kalung 9K dan Liontin 23K di Purwonegoro belum memiliki 14 hari penjualan positif dalam riwayat 180 hari, sehingga estimator menandainya belum tersedia. Safety stock seluruh 340 kelompok tetap terisi. Hasil diverifikasi melalui `InventoryAnalysisService`, termasuk kecocokan hash riwayat dan ketiadaan pesan batch yang hilang, dengan `php scripts/check_arima_status.php 2026-10-31 2026-11-13`.

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
