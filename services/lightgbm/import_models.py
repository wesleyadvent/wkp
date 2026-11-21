"""Install the user's Colab export without executing any code from the archive."""
import argparse
import csv
import hashlib
import io
import json
from pathlib import Path
from zipfile import ZipFile

ROOT = Path(__file__).resolve().parents[2]
DEFAULT_ARCHIVE = ROOT / "docs/Hasil_LightGBM_ARIMA_Wijaya_Kusuma_Lead_Time_dan_Proyeksi.zip"


def install(archive=DEFAULT_ARCHIVE, destination=None):
    destination = Path(destination or Path(__file__).parent / "models")
    destination.mkdir(parents=True, exist_ok=True)
    manifest = {}
    with ZipFile(archive) as source:
        for horizon in (3, 7, 14, 21, 30, 60, 90):
            for name in (f"lightgbm_{horizon}d.pkl", f"metadata_{horizon}d.json"):
                data = source.read(name)
                (destination / name).write_bytes(data)
                manifest[name] = hashlib.sha256(data).hexdigest()
        fixture = Path(__file__).parent / "fixtures/colab_lead_time.json"
        fixture.parent.mkdir(parents=True, exist_ok=True)
        fixture.write_bytes(source.read("contoh_prediksi_lead_time.json"))
        examples = csv.DictReader(io.StringIO(source.read("Contoh_Prediksi_Uji.csv").decode("utf-8")))
        # The source archive may use a different label for the prediction date.
        date_column = next(name for name in examples.fieldnames if name.startswith('tanggal_'))
        predictions = {row['horizon_hari']: float(row['prediksi_lightgbm']) for row in examples
                       if row['kode_cabang'] == 'DSA' and row['kategori'] == 'ANTING'
                       and row['kadar'] == '8' and row[date_column] == '2025-10-01'}
        (fixture.parent / "colab_projection_units.json").write_text(json.dumps(predictions, indent=2) + "\n", encoding="utf-8")
    (destination / "manifest.json").write_text(json.dumps(manifest, indent=2) + "\n", encoding="utf-8")
    return manifest


if __name__ == "__main__":
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--archive", type=Path, default=DEFAULT_ARCHIVE)
    parser.add_argument("--destination", type=Path)
    args = parser.parse_args()
    installed = install(args.archive, args.destination)
    print(f"Installed {len(installed) // 2} models from {args.archive.name}.")
