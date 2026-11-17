-- Structure only: wkp_demo / 10.4.32-MariaDB
-- Generated 2026-11-14T21:41:02+07:00
-- Import in Workbench: File > Import > Reverse Engineer MySQL Create Script.
-- MariaDB JSON aliases use native MySQL JSON here; foreign keys follow all CREATE TABLE statements.
CREATE SCHEMA IF NOT EXISTS `wkp_demo` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `wkp_demo`;

CREATE TABLE `barang` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `kode_barang` varchar(255) NOT NULL,
  `branch_id` bigint(20) unsigned NOT NULL,
  `kategori_barang_id` bigint(20) unsigned NOT NULL,
  `nama_barang` varchar(255) NOT NULL,
  `kadar` varchar(255) NOT NULL,
  `berat` decimal(10,3) NOT NULL,
  `harga_per_gram` decimal(15,2) NOT NULL,
  `ongkos` decimal(15,2) NOT NULL DEFAULT 0.00,
  `total_harga` decimal(15,2) NOT NULL,
  `deskripsi` text DEFAULT NULL,
  `photo_path` varchar(255) DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'tersedia',
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `creation_batch` char(26) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `barang_kode_barang_unique` (`kode_barang`),
  KEY `barang_branch_id_foreign` (`branch_id`),
  KEY `barang_creation_batch_index` (`creation_batch`),
  KEY `barang_kategori_barang_id_foreign` (`kategori_barang_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `cabang` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `kode_cabang` varchar(255) NOT NULL,
  `nama_cabang` varchar(255) NOT NULL,
  `alamat` text DEFAULT NULL,
  `telepon` varchar(255) DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'aktif',
  `is_login_enabled` tinyint(1) NOT NULL DEFAULT 1,
  `login_disabled_at` timestamp NULL DEFAULT NULL,
  `login_disabled_by` bigint(20) unsigned DEFAULT NULL,
  `login_disabled_reason` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `cabang_kode_cabang_unique` (`kode_cabang`),
  KEY `cabang_login_disabled_by_foreign` (`login_disabled_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` bigint(20) NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` bigint(20) NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_locks_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `failed_jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) NOT NULL,
  `connection` varchar(255) NOT NULL,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`),
  KEY `failed_jobs_connection_queue_failed_at_index` (`connection`,`queue`,`failed_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `harga_antam_harian` (
  `tanggal` date NOT NULL,
  `harga` decimal(15,2) NOT NULL,
  `tanggal_sumber` date NOT NULL,
  `sumber` varchar(255) NOT NULL,
  PRIMARY KEY (`tanggal`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `harga_emas` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tanggal` date NOT NULL,
  `kadar` varchar(255) NOT NULL,
  `harga_per_gram` decimal(15,2) NOT NULL,
  `keterangan` text DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `used_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `harga_emas_created_by_foreign` (`created_by`),
  KEY `harga_emas_kadar_tanggal_index` (`kadar`,`tanggal`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `inventory_forecasts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `branch_id` bigint(20) unsigned NOT NULL,
  `category_id` bigint(20) unsigned NOT NULL,
  `kadar` varchar(50) NOT NULL,
  `cutoff` date NOT NULL,
  `horizon` int(10) unsigned NOT NULL,
  `method` varchar(10) NOT NULL DEFAULT 'ARIMA',
  `version` varchar(40) NOT NULL,
  `input_hash` varchar(64) NOT NULL,
  `status` varchar(30) NOT NULL,
  `total` double DEFAULT NULL,
  `details` json NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `forecast_identity_unique` (`branch_id`,`category_id`,`kadar`,`cutoff`,`horizon`,`method`,`version`),
  KEY `inventory_forecasts_category_id_foreign` (`category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `item_nota_layanan` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `service_note_id` bigint(20) unsigned NOT NULL,
  `service_type_id` bigint(20) unsigned NOT NULL,
  `harga_service` decimal(15,2) NOT NULL DEFAULT 0.00,
  `keterangan` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `receipt_snapshot` json DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `item_nota_layanan_service_note_id_foreign` (`service_note_id`),
  KEY `item_nota_layanan_service_type_id_foreign` (`service_type_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `item_pembelian` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `purchase_id` bigint(20) unsigned NOT NULL,
  `product_id_ref` bigint(20) unsigned DEFAULT NULL,
  `nama_barang` varchar(255) NOT NULL,
  `kategori` varchar(255) DEFAULT NULL,
  `kadar` varchar(255) DEFAULT NULL,
  `berat` decimal(10,3) NOT NULL,
  `harga_per_gram_beli` decimal(15,2) NOT NULL,
  `potongan_per_gram` decimal(15,2) NOT NULL DEFAULT 0.00,
  `subtotal` decimal(15,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `receipt_snapshot` json DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `item_pembelian_purchase_id_foreign` (`purchase_id`),
  KEY `item_pembelian_product_id_ref_foreign` (`product_id_ref`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `item_penjualan` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `sale_id` bigint(20) unsigned NOT NULL,
  `product_id` bigint(20) unsigned NOT NULL,
  `berat` decimal(10,3) NOT NULL,
  `harga_per_gram` decimal(15,2) NOT NULL,
  `ongkos` decimal(15,2) NOT NULL DEFAULT 0.00,
  `subtotal` decimal(15,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `receipt_snapshot` json DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `item_penjualan_sale_id_foreign` (`sale_id`),
  KEY `item_penjualan_product_id_foreign` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `jenis_layanan` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `nama_service` varchar(255) NOT NULL,
  `harga_default` decimal(15,2) NOT NULL DEFAULT 0.00,
  `keterangan` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `used_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `jenis_potongan` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `nama_potongan` varchar(255) NOT NULL,
  `nominal` decimal(15,2) NOT NULL DEFAULT 0.00,
  `keterangan` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `used_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` smallint(5) unsigned NOT NULL,
  `reserved_at` int(10) unsigned DEFAULT NULL,
  `available_at` int(10) unsigned NOT NULL,
  `created_at` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext DEFAULT NULL,
  `cancelled_at` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `kalender_cabang` (
  `branch_id` bigint(20) unsigned NOT NULL,
  `tanggal` date NOT NULL,
  `buka` tinyint(1) NOT NULL,
  `pencatatan_lengkap` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`branch_id`,`tanggal`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `karyawan` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `nama` varchar(255) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `used_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `kategori_barang` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `nama_kategori` varchar(255) NOT NULL,
  `keterangan` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `used_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `kategori_barang_nama_kategori_unique` (`nama_kategori`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `konfigurasi_persediaan` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `branch_id` bigint(20) unsigned NOT NULL,
  `kategori` varchar(100) NOT NULL,
  `kadar` varchar(50) NOT NULL,
  `lead_time_hari` smallint(5) unsigned NOT NULL DEFAULT 14,
  `safety_stock` int(10) unsigned NOT NULL DEFAULT 2,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `method` varchar(8) NOT NULL DEFAULT 'SMA',
  PRIMARY KEY (`id`),
  UNIQUE KEY `konfigurasi_metode_kelompok_unique` (`branch_id`,`method`,`kategori`,`kadar`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `log_audit` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `action` varchar(255) NOT NULL,
  `table_name` varchar(255) DEFAULT NULL,
  `record_id` bigint(20) unsigned DEFAULT NULL,
  `old_values` json DEFAULT NULL,
  `new_values` json DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `log_audit_user_id_foreign` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `log_login_cabang` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `branch_id` bigint(20) unsigned NOT NULL,
  `admin_id` bigint(20) unsigned DEFAULT NULL,
  `old_status` tinyint(1) NOT NULL,
  `new_status` tinyint(1) NOT NULL,
  `alasan` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `log_login_cabang_branch_id_foreign` (`branch_id`),
  KEY `log_login_cabang_admin_id_foreign` (`admin_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `nota_layanan` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `nomor_nota` varchar(255) NOT NULL,
  `branch_id` bigint(20) unsigned NOT NULL,
  `customer_id` bigint(20) unsigned NOT NULL,
  `product_id_ref` bigint(20) unsigned DEFAULT NULL,
  `sale_id_ref` bigint(20) unsigned DEFAULT NULL,
  `asal_barang` varchar(255) NOT NULL DEFAULT 'barang_luar',
  `nama_barang_manual` varchar(255) DEFAULT NULL,
  `berat` decimal(10,3) DEFAULT NULL,
  `jenis_service_id` bigint(20) unsigned NOT NULL,
  `harga_service` decimal(15,2) NOT NULL DEFAULT 0.00,
  `status_service` varchar(255) NOT NULL DEFAULT 'diterima',
  `tanggal_masuk` date NOT NULL,
  `estimasi_selesai` date DEFAULT NULL,
  `tanggal_ambil` date DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `cancelled_by` bigint(20) unsigned DEFAULT NULL,
  `cancelled_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `employee_id` bigint(20) unsigned DEFAULT NULL,
  `employee_name` varchar(255) DEFAULT NULL,
  `receipt_snapshot` json DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nota_layanan_nomor_nota_unique` (`nomor_nota`),
  KEY `nota_layanan_branch_id_foreign` (`branch_id`),
  KEY `nota_layanan_customer_id_foreign` (`customer_id`),
  KEY `nota_layanan_product_id_ref_foreign` (`product_id_ref`),
  KEY `nota_layanan_jenis_service_id_foreign` (`jenis_service_id`),
  KEY `nota_layanan_created_by_foreign` (`created_by`),
  KEY `nota_layanan_cancelled_by_foreign` (`cancelled_by`),
  KEY `nota_layanan_sale_id_ref_foreign` (`sale_id_ref`),
  KEY `nota_layanan_employee_id_foreign` (`employee_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pelanggan` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `nama` varchar(255) NOT NULL,
  `alamat` text DEFAULT NULL,
  `telepon` varchar(255) DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `branch_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pelanggan_created_by_foreign` (`created_by`),
  KEY `pelanggan_branch_id_foreign` (`branch_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pembatalan_transaksi` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `transaction_type` varchar(255) NOT NULL,
  `transaction_id` bigint(20) unsigned NOT NULL,
  `alasan` text NOT NULL,
  `verified_admin_id` bigint(20) unsigned NOT NULL,
  `cancelled_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pembatalan_transaksi_verified_admin_id_foreign` (`verified_admin_id`),
  KEY `pembatalan_transaksi_transaction_type_transaction_id_index` (`transaction_type`,`transaction_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pembelian` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `nomor_nota` varchar(255) NOT NULL,
  `branch_id` bigint(20) unsigned NOT NULL,
  `customer_id` bigint(20) unsigned NOT NULL,
  `sumber_barang` varchar(255) NOT NULL DEFAULT 'barang_luar',
  `sale_id_ref` bigint(20) unsigned DEFAULT NULL,
  `tanggal` date NOT NULL,
  `total_berat` decimal(10,3) NOT NULL DEFAULT 0.000,
  `total_potongan` decimal(15,2) NOT NULL DEFAULT 0.00,
  `total_harga` decimal(15,2) NOT NULL DEFAULT 0.00,
  `status` varchar(255) NOT NULL DEFAULT 'valid',
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `cancelled_by` bigint(20) unsigned DEFAULT NULL,
  `cancelled_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `employee_id` bigint(20) unsigned DEFAULT NULL,
  `employee_name` varchar(255) DEFAULT NULL,
  `receipt_snapshot` json DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pembelian_nomor_nota_unique` (`nomor_nota`),
  KEY `pembelian_branch_id_foreign` (`branch_id`),
  KEY `pembelian_customer_id_foreign` (`customer_id`),
  KEY `pembelian_sale_id_ref_foreign` (`sale_id_ref`),
  KEY `pembelian_created_by_foreign` (`created_by`),
  KEY `pembelian_cancelled_by_foreign` (`cancelled_by`),
  KEY `pembelian_employee_id_foreign` (`employee_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pengguna` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role` varchar(255) NOT NULL DEFAULT 'toko_cabang',
  `branch_id` bigint(20) unsigned DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `last_login_at` timestamp NULL DEFAULT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pengguna_email_unique` (`email`),
  KEY `pengguna_branch_id_foreign` (`branch_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `penjualan` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `nomor_nota` varchar(255) NOT NULL,
  `branch_id` bigint(20) unsigned NOT NULL,
  `customer_id` bigint(20) unsigned NOT NULL,
  `tanggal` date NOT NULL,
  `total_berat` decimal(10,3) NOT NULL DEFAULT 0.000,
  `total_ongkos` decimal(15,2) NOT NULL DEFAULT 0.00,
  `total_harga` decimal(15,2) NOT NULL DEFAULT 0.00,
  `status` varchar(255) NOT NULL DEFAULT 'valid',
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `cancelled_by` bigint(20) unsigned DEFAULT NULL,
  `cancelled_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `employee_id` bigint(20) unsigned DEFAULT NULL,
  `employee_name` varchar(255) DEFAULT NULL,
  `receipt_snapshot` json DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `penjualan_nomor_nota_unique` (`nomor_nota`),
  KEY `penjualan_branch_id_foreign` (`branch_id`),
  KEY `penjualan_customer_id_foreign` (`customer_id`),
  KEY `penjualan_created_by_foreign` (`created_by`),
  KEY `penjualan_cancelled_by_foreign` (`cancelled_by`),
  KEY `penjualan_employee_id_foreign` (`employee_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `potongan_pembelian` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `purchase_id` bigint(20) unsigned NOT NULL,
  `deduction_type_id` bigint(20) unsigned DEFAULT NULL,
  `nominal` decimal(15,2) NOT NULL DEFAULT 0.00,
  `keterangan` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `receipt_snapshot` json DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `potongan_pembelian_purchase_id_foreign` (`purchase_id`),
  KEY `potongan_pembelian_deduction_type_id_foreign` (`deduction_type_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `urutan_harian_barang` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `date` date NOT NULL,
  `branch_id` bigint(20) unsigned NOT NULL,
  `last_number` bigint(20) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `urutan_harian_barang_date_branch_id_unique` (`date`,`branch_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `urutan_kode_barang` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `branch_id` bigint(20) unsigned NOT NULL,
  `kategori_barang_id` bigint(20) unsigned NOT NULL,
  `last_number` bigint(20) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `urutan_kode_barang_cabang_kategori_unique` (`branch_id`,`kategori_barang_id`),
  KEY `urutan_kode_barang_kategori_barang_id_foreign` (`kategori_barang_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `urutan_nomor_nota` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `branch_id` bigint(20) unsigned NOT NULL,
  `prefix` varchar(10) NOT NULL,
  `year` smallint(5) unsigned NOT NULL,
  `last_number` bigint(20) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `urutan_nomor_nota_cabang_prefix_tahun_unique` (`branch_id`,`prefix`,`year`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `barang` ADD CONSTRAINT `barang_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `cabang` (`id`) ON DELETE CASCADE;
ALTER TABLE `barang` ADD CONSTRAINT `barang_kategori_barang_id_foreign` FOREIGN KEY (`kategori_barang_id`) REFERENCES `kategori_barang` (`id`);
ALTER TABLE `cabang` ADD CONSTRAINT `cabang_login_disabled_by_foreign` FOREIGN KEY (`login_disabled_by`) REFERENCES `pengguna` (`id`) ON DELETE SET NULL;
ALTER TABLE `harga_emas` ADD CONSTRAINT `harga_emas_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `pengguna` (`id`) ON DELETE SET NULL;
ALTER TABLE `inventory_forecasts` ADD CONSTRAINT `inventory_forecasts_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `cabang` (`id`) ON DELETE CASCADE;
ALTER TABLE `inventory_forecasts` ADD CONSTRAINT `inventory_forecasts_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `kategori_barang` (`id`) ON DELETE CASCADE;
ALTER TABLE `item_nota_layanan` ADD CONSTRAINT `item_nota_layanan_service_note_id_foreign` FOREIGN KEY (`service_note_id`) REFERENCES `nota_layanan` (`id`) ON DELETE CASCADE;
ALTER TABLE `item_nota_layanan` ADD CONSTRAINT `item_nota_layanan_service_type_id_foreign` FOREIGN KEY (`service_type_id`) REFERENCES `jenis_layanan` (`id`);
ALTER TABLE `item_pembelian` ADD CONSTRAINT `item_pembelian_product_id_ref_foreign` FOREIGN KEY (`product_id_ref`) REFERENCES `barang` (`id`) ON DELETE SET NULL;
ALTER TABLE `item_pembelian` ADD CONSTRAINT `item_pembelian_purchase_id_foreign` FOREIGN KEY (`purchase_id`) REFERENCES `pembelian` (`id`) ON DELETE CASCADE;
ALTER TABLE `item_penjualan` ADD CONSTRAINT `item_penjualan_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `barang` (`id`);
ALTER TABLE `item_penjualan` ADD CONSTRAINT `item_penjualan_sale_id_foreign` FOREIGN KEY (`sale_id`) REFERENCES `penjualan` (`id`) ON DELETE CASCADE;
ALTER TABLE `kalender_cabang` ADD CONSTRAINT `kalender_cabang_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `cabang` (`id`) ON DELETE CASCADE;
ALTER TABLE `konfigurasi_persediaan` ADD CONSTRAINT `konfigurasi_persediaan_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `cabang` (`id`) ON DELETE CASCADE;
ALTER TABLE `log_audit` ADD CONSTRAINT `log_audit_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `pengguna` (`id`) ON DELETE SET NULL;
ALTER TABLE `log_login_cabang` ADD CONSTRAINT `log_login_cabang_admin_id_foreign` FOREIGN KEY (`admin_id`) REFERENCES `pengguna` (`id`) ON DELETE SET NULL;
ALTER TABLE `log_login_cabang` ADD CONSTRAINT `log_login_cabang_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `cabang` (`id`) ON DELETE CASCADE;
ALTER TABLE `nota_layanan` ADD CONSTRAINT `nota_layanan_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `cabang` (`id`) ON DELETE CASCADE;
ALTER TABLE `nota_layanan` ADD CONSTRAINT `nota_layanan_cancelled_by_foreign` FOREIGN KEY (`cancelled_by`) REFERENCES `pengguna` (`id`) ON DELETE SET NULL;
ALTER TABLE `nota_layanan` ADD CONSTRAINT `nota_layanan_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `pengguna` (`id`) ON DELETE SET NULL;
ALTER TABLE `nota_layanan` ADD CONSTRAINT `nota_layanan_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `pelanggan` (`id`);
ALTER TABLE `nota_layanan` ADD CONSTRAINT `nota_layanan_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `karyawan` (`id`);
ALTER TABLE `nota_layanan` ADD CONSTRAINT `nota_layanan_jenis_service_id_foreign` FOREIGN KEY (`jenis_service_id`) REFERENCES `jenis_layanan` (`id`);
ALTER TABLE `nota_layanan` ADD CONSTRAINT `nota_layanan_product_id_ref_foreign` FOREIGN KEY (`product_id_ref`) REFERENCES `barang` (`id`) ON DELETE SET NULL;
ALTER TABLE `nota_layanan` ADD CONSTRAINT `nota_layanan_sale_id_ref_foreign` FOREIGN KEY (`sale_id_ref`) REFERENCES `penjualan` (`id`) ON DELETE SET NULL;
ALTER TABLE `pelanggan` ADD CONSTRAINT `pelanggan_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `cabang` (`id`) ON DELETE CASCADE;
ALTER TABLE `pelanggan` ADD CONSTRAINT `pelanggan_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `pengguna` (`id`) ON DELETE SET NULL;
ALTER TABLE `pembatalan_transaksi` ADD CONSTRAINT `pembatalan_transaksi_verified_admin_id_foreign` FOREIGN KEY (`verified_admin_id`) REFERENCES `pengguna` (`id`);
ALTER TABLE `pembelian` ADD CONSTRAINT `pembelian_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `cabang` (`id`) ON DELETE CASCADE;
ALTER TABLE `pembelian` ADD CONSTRAINT `pembelian_cancelled_by_foreign` FOREIGN KEY (`cancelled_by`) REFERENCES `pengguna` (`id`) ON DELETE SET NULL;
ALTER TABLE `pembelian` ADD CONSTRAINT `pembelian_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `pengguna` (`id`) ON DELETE SET NULL;
ALTER TABLE `pembelian` ADD CONSTRAINT `pembelian_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `pelanggan` (`id`);
ALTER TABLE `pembelian` ADD CONSTRAINT `pembelian_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `karyawan` (`id`);
ALTER TABLE `pembelian` ADD CONSTRAINT `pembelian_sale_id_ref_foreign` FOREIGN KEY (`sale_id_ref`) REFERENCES `penjualan` (`id`) ON DELETE SET NULL;
ALTER TABLE `pengguna` ADD CONSTRAINT `pengguna_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `cabang` (`id`) ON DELETE SET NULL;
ALTER TABLE `penjualan` ADD CONSTRAINT `penjualan_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `cabang` (`id`) ON DELETE CASCADE;
ALTER TABLE `penjualan` ADD CONSTRAINT `penjualan_cancelled_by_foreign` FOREIGN KEY (`cancelled_by`) REFERENCES `pengguna` (`id`) ON DELETE SET NULL;
ALTER TABLE `penjualan` ADD CONSTRAINT `penjualan_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `pengguna` (`id`) ON DELETE SET NULL;
ALTER TABLE `penjualan` ADD CONSTRAINT `penjualan_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `pelanggan` (`id`);
ALTER TABLE `penjualan` ADD CONSTRAINT `penjualan_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `karyawan` (`id`);
ALTER TABLE `potongan_pembelian` ADD CONSTRAINT `potongan_pembelian_deduction_type_id_foreign` FOREIGN KEY (`deduction_type_id`) REFERENCES `jenis_potongan` (`id`) ON DELETE SET NULL;
ALTER TABLE `potongan_pembelian` ADD CONSTRAINT `potongan_pembelian_purchase_id_foreign` FOREIGN KEY (`purchase_id`) REFERENCES `pembelian` (`id`) ON DELETE CASCADE;
ALTER TABLE `urutan_kode_barang` ADD CONSTRAINT `urutan_kode_barang_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `cabang` (`id`) ON DELETE CASCADE;
ALTER TABLE `urutan_kode_barang` ADD CONSTRAINT `urutan_kode_barang_kategori_barang_id_foreign` FOREIGN KEY (`kategori_barang_id`) REFERENCES `kategori_barang` (`id`) ON DELETE CASCADE;
ALTER TABLE `urutan_nomor_nota` ADD CONSTRAINT `urutan_nomor_nota_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `cabang` (`id`) ON DELETE CASCADE;
