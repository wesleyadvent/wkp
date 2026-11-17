# Audit struktur database dan crash MySQL Workbench

Audit terhadap database aktif `wkp_demo` pada 14 November 2026. Pemeriksaan membaca metadata, definisi tabel, integritas referensi, dan log aplikasi. Skema dan data operasional tidak diubah.

## Kesimpulan

Struktur relasional dasar valid pada server yang digunakan. Tidak ditemukan pelanggaran primary key/foreign key atau kesalahan penyimpanan pada pemeriksaan yang dijalankan. Ada peluang perapian normalisasi dan tabel lama, sehingga hasil ini bukan sertifikasi bahwa seluruh skema memenuhi 3NF secara ketat.

Crash terjadi di parser MySQL Workbench. Bukti lokal menunjukkan ketidakcocokan Workbench dengan MariaDB sebagai dugaan pemicu terkuat; penyebab internal parser yang persis memerlukan debugging aplikasi Workbench lebih lanjut.

## Hasil pemeriksaan database

| Pemeriksaan | Hasil |
| --- | --- |
| Server | MariaDB 10.4.32 |
| Jumlah tabel | 34 |
| Mesin penyimpanan | InnoDB pada seluruh tabel |
| Primary key | Seluruh 34 tabel memiliki primary key |
| Foreign key | 45 relasi terdefinisi |
| Kecocokan tipe kolom FK | Tidak ditemukan ketidakcocokan tipe, unsigned, charset, atau collation |
| Indeks pendukung FK | Seluruh relasi memiliki indeks yang sesuai |
| Kunci tabel induk | Seluruh relasi mengacu pada kunci unik yang berindeks |
| Aturan SET NULL | Seluruh kolom terkait mengizinkan NULL |
| Referensi yatim pada FK | Tidak ditemukan |
| CHECK TABLE QUICK | Seluruh 34 tabel menghasilkan status OK |
| Kategori konfigurasi tanpa master | 0 baris |
| Tabel urutan harian lama | 0 baris |

Uang transaksi menggunakan DECIMAL dan berat menggunakan DECIMAL dengan pecahan gram. Pemisahan nota dan item transaksi sudah tersedia. Snapshot nota dan total transaksi tersimpan merupakan denormalisasi untuk kebutuhan riwayat; bagian ini perlu dinilai berdasarkan tujuan penyimpanan, bukan dianggap otomatis sebagai kesalahan.

Pemeriksaan QUICK dan referensi tidak membuktikan semua aturan bisnis. Contohnya, kesamaan cabang pelanggan dengan transaksi serta aturan perubahan status juga bergantung pada validasi aplikasi.

## Bukti crash Workbench

Versi terpasang: **MySQL Workbench 8.0.46 CE**.

Log `%APPDATA%\MySQL\Workbench\log\wb.1.log` mencatat pada 21:34:43:

```text
Unsupported server version: mariadb.org binary distribution 10.4.32-MariaDB
```

Pada 21:34:55, daftar **34 tabel** berhasil diambil. Windows Application Event ID 1000 pada 21:34:57 kemudian mencatat:

```text
Faulting application name: MySQLWorkbench.exe, version: 8.0.46.0
Faulting module name: db.mysql.parser.grt.dll
Exception code: 0xc0000005
```

Log sesi berikutnya, `wb.log`, kembali mencatat server tidak didukung dan pesan:

```text
Error fetching index information ... invalid value of 'columnIndex'
```

Pesan tersebut muncul saat Workbench membaca indeks `harga_antam_harian`, `harga_emas`, `inventory_forecasts`, `item_nota_layanan`, dan `item_pembelian`. Pemeriksaan langsung dari server tidak menemukan kekurangan indeks FK.

Tim MySQL menyatakan MariaDB tidak didukung oleh Workbench pada [laporan crash reverse engineering yang serupa](https://bugs.mysql.com/bug.php?id=107975). Bukti tersebut mendukung dugaan kompatibilitas, tetapi tidak membuktikan bahwa kasus ini memiliki bug internal yang persis sama.

MariaDB merepresentasikan JSON sebagai LONGTEXT dan validasi JSON. Database ini memiliki 10 kolom dengan pola tersebut. Ini adalah representasi yang sah menurut [dokumentasi MariaDB](https://mariadb.com/docs/server/reference/data-types/string-data-types/json); belum dibuktikan bahwa salah satu kolom tersebut merupakan pemicu crash.

## Perapian desain yang disarankan

1. **Kategori pada konfigurasi persediaan.** `konfigurasi_persediaan.kategori` masih VARCHAR(100), sedangkan master kategori menggunakan VARCHAR(255). Lebih konsisten bila konfigurasi mengacu pada `kategori_barang_id` dengan foreign key. Aplikasi saat ini sudah menyinkronkan pergantian nama kategori dalam transaksi database, tetapi integritas relasi tersebut tetap bergantung pada kode aplikasi. Saat audit seluruh nilainya cocok dengan master.
2. **Urutan barang lama.** `urutan_harian_barang.branch_id` belum memiliki foreign key dan tabelnya kosong. Generator kode aktif memakai `urutan_kode_barang`, yang sudah memiliki FK. Tentukan apakah tabel lama akan dipertahankan atau dipensiunkan dalam perubahan terpisah.

Kolom lain yang tidak memakai FK tidak otomatis merupakan kesalahan:

- `log_audit.record_id` dan `pembatalan_transaksi.transaction_id` menunjuk jenis objek yang berbeda berdasarkan kolom pendampingnya, sehingga merupakan referensi polimorfik.
- `sessions.user_id` mengikuti struktur sesi Laravel yang saat ini menggunakan indeks tanpa FK.
- Relasi dua arah `pengguna.branch_id` ke cabang serta `cabang.login_disabled_by` ke pengguna adalah relasi SQL yang valid. Tidak ada dasar menghapus relasi ini untuk menangani crash Workbench.

Perapian di atas belum diterapkan ke database aktif. Perubahan normalisasi membutuhkan penyesuaian model, controller, seeder, dan pengujian.

## SQL struktur untuk pemodelan

Berkas hasil audit:

- `wkp_demo_schema_mariadb.sql`: definisi asli dari SHOW CREATE TABLE, tanpa data.
- `wkp_demo_schema_workbench.sql`: salinan untuk pemodelan MySQL, berisi 34 CREATE TABLE dan 45 ALTER TABLE untuk relasi. Sepuluh alias JSON MariaDB diubah menjadi tipe JSON MySQL pada salinan ini; nilai awal AUTO_INCREMENT dihilangkan dan FK ditempatkan setelah seluruh definisi tabel.

Untuk mencoba alur offline di Workbench:

1. Buka **File > New Model**.
2. Pilih **File > Import > Reverse Engineer MySQL Create Script**.
3. Pilih `wkp_demo_schema_workbench.sql`.
4. Aktifkan penempatan objek pada diagram bila diperlukan, lalu jalankan impor model.

Alur ini didukung oleh [manual Workbench](https://dev.mysql.com/doc/workbench/en/wb-reverse-engineer-create-script.html) dan melewati pembacaan metadata dari koneksi langsung MariaDB. Salinan berkas sudah diperiksa jumlah tabel, relasi, dan konversi JSON-nya. Impor melalui UI Workbench belum diuji, sehingga keberhasilannya belum dikonfirmasi.

Untuk diagram dari koneksi langsung MariaDB, alternatifnya adalah [driver MariaDB DBeaver](https://dbeaver.com/docs/dbeaver/Database-driver-MariaDB/) dan fitur [ER Diagram](https://dbeaver.com/docs/dbeaver/ER-Diagrams/). Tidak ada instalasi, perubahan server, atau perubahan konfigurasi Workbench yang dilakukan selama audit ini.

## Mengulang audit

```powershell
php scripts/audit_database_structure.php
```

Laporan mesin berada di `storage/app/private/database-structure-audit.json`. Bukti log dan event Windows terpilih berada di `storage/app/private/workbench-crash-evidence.json`.
