# Kamus data LightGBM

Fitur ekspor dan permintaan API berasal dari `LightgbmFeatureBuilder`. Urutan dan pemetaan merupakan isi `services/lightgbm/models/metadata_Hd.json`, dengan H sesuai horizon. Satu baris ekspor adalah satu tanggal potong dan satu kelompok cabang, kategori, kadar. API hanya menerima fitur model, bukan target atau kolom audit.

| Kolom | Tipe dan definisi |
| --- | --- |
| `schema_version` | String `multihorizon-1`. |
| `as_of_date` / `tanggal_ramalan` | Tanggal potong t, ISO `YYYY-MM-DD`, Asia/Jakarta. |
| `target_end_date` | t + H hari kalender. |
| `group_key` | Kunci internal kelompok; dipakai mencocokkan batch, bukan fitur model. |
| `branch_code` | Kategori numerik: DSA=0, MDR=1, PWG=2, SPH=3. |
| `category_code` | Kategori numerik: ANTING=0, CINCIN=1, GELANG=2, KALUNG=3, LIONTIN=4. |
| `karat` | Integer 8–24. Persentase kadar tidak dikonversi otomatis menjadi karat. |
| `sales_7d`, `sales_14d`, `sales_30d`, `sales_60d`, `sales_90d` | Jumlah item pada penjualan valid, selama N tanggal kalender berakhir di t. Unit integer nonnegatif. Hari tutup nol; pencatatan tidak lengkap hilang. |
| `days_since_last_sale` | Integer selisih tanggal t terhadap penjualan valid terakhir sampai t. Nol bila terjual pada t; kosong/null bila belum pernah tercatat terjual. |
| `open_days_next_H` | Integer jumlah hari buka pada t+1 sampai t+H, dari kalender yang sama dengan aplikasi. Tidak memasukkan t atau penjualan aktual masa depan. |
| `pre_eid_days_next_H` | Integer jumlah hari pada t+1 sampai t+H yang berada di H−21..H−1 terhadap Idulfitri. Hari H tidak dihitung. |
| `target_sales_Hd` | Target evaluasi, jumlah unit valid t+1 sampai t+H. Kosong jika target belum selesai atau aktual tidak lengkap. Tidak pernah dikirim ke Flask. |
| `feature_error` | Alasan fitur baris belum lengkap/didukung. Fitur baris tersebut kosong, tidak diimputasi nol. |
| `kode_cabang`, `kategori`, `kadar` | Identitas panel untuk notebook: kode cabang string, kategori huruf besar, kadar seperti `16K`. |
| `penjualan_hari_ini` | Unit valid pada t; kosong jika pencatatan t belum lengkap. |
| `harga_antam` | Harga ANTAM 1 gram terakhir dengan tanggal observasi dan sumber tidak melewati t, rupiah. Audit/eksperimen; bukan fitur PKL terpilih. |
| `perubahan_harga_7_persen` | 100 × (P(t)/P(t−7) − 1), dengan harga terakhir yang tersedia pada masing-masing tanggal; kosong jika pembanding belum ada. |
| `tanggal_sumber_harga`, `sumber_harga` | Audit tanggal sumber dan keterangan sumber harga yang digunakan. |

Model terpilih memakai tepat 11 fitur. Hanya `days_since_last_sale` yang boleh null dalam permintaan model ini. Encoding kategori disimpan sebagai angka sesuai notebook; jangan menggantinya dengan ID cabang/kategori database atau string mentah. Semua nilai desimal API harus finite. `predicted_units` adalah jumlah unit untuk horizon yang diminta, dengan awal t+1 dan akhir t+H.

CSV tidak memuat data pelanggan, barcode individual, stok sekarang, safety stock atau ROP. Filter waktu pada fitur berhenti di t. Label target baru digunakan dalam pelatihan/evaluasi dan harus dipisah dari fitur.
