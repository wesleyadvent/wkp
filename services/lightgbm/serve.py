"""Run the internal Flask API using Waitress, including on Windows."""
import logging
import os
from waitress import serve
from app import HORIZONS, create_app

if __name__ == "__main__":
    logging.basicConfig(level=logging.INFO, format="%(asctime)s %(levelname)s %(message)s")
    host = os.environ.get("LIGHTGBM_HOST", "127.0.0.1")
    port = int(os.environ.get("LIGHTGBM_PORT", "5001"))
    logging.info("Memuat model LightGBM...")
    application = create_app()
    logging.info("Model berhasil dimuat: %s dari %s.", len(application.extensions["lightgbm_models"]), len(HORIZONS))
    logging.info("Cek kesiapan layanan: http://%s:%s/health", host, port)
    logging.info("Biarkan terminal ini terbuka. Tekan Ctrl+C untuk menghentikan server.")
    try:
        serve(application, host=host, port=port, threads=4)
    except OSError:
        logging.exception("Server gagal dijalankan. Periksa apakah port %s sudah dipakai proses lain.", port)
        raise SystemExit(1)
