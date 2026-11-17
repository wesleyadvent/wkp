"""Replay the full held-out Colab evaluation and publish paired predictions.

Uses existing PKLs and validation-selected SMA/WMA windows and ARIMA order.
No LightGBM training or test-set parameter selection takes place.
"""
import argparse
import ast
import csv
import hashlib
import io
import json
from datetime import datetime, timezone
from pathlib import Path
import sys
from zipfile import ZipFile

import numpy as np

import colab_comparison as reference

ROOT = Path(__file__).resolve().parents[2]
sys.path.insert(0, str(ROOT / "services/lightgbm"))
from app import load_models

METHODS = ("SMA", "WMA", "ARIMA", "LightGBM")
FIELDS = ["horizon", "branch_code", "category", "karat", "cutoff", "target_start", "target_end", "actual", *METHODS]


def metrics(actual, prediction):
    error = np.asarray(prediction) - np.asarray(actual)
    positive = actual > 0
    return dict(n=int(len(actual)), mae=float(np.abs(error).mean()), rmse=float(np.sqrt(np.square(error).mean())),
                mape=float(100 * np.abs(error[positive] / actual[positive]).mean()) if positive.any() else None,
                mape_n=int(positive.sum()), zero_actual=int((~positive).sum()))


def build(dataset, archive, output, model_dir):
    output.parent.mkdir(parents=True, exist_ok=True)
    with ZipFile(archive) as source:
        report = list(csv.DictReader(io.StringIO(source.read("Evaluasi_MAE_Lead_Time_dan_Proyeksi.csv").decode())))
    selected = {}
    for horizon in reference.HORIZONS:
        rows = [r for r in report if int(r["horizon_hari"]) == horizon and r["split"] == "validasi" and r["terpilih_pada_validasi"] == "True"]
        selected[horizon] = {method: next(r["metode"] for r in rows if r["metode"].startswith(method)) for method in METHODS}
    bundles, errors = load_models(model_dir)
    if errors:
        raise ValueError(f"Model tidak siap: {errors}")
    print("Membaca dan memvalidasi panel harian...", flush=True)
    values, dates, identities, opened, pre_eid, prices = reference.load_panel(dataset)
    classical = reference.sma_wma_forecasts(values)
    origins = {h: reference.origin_indexes(dates, h, "uji") for h in reference.HORIZONS}
    combined = np.unique(np.concatenate(list(origins.values())))
    orders = {ast.literal_eval(selected[h]["ARIMA"].removeprefix("ARIMA")) for h in reference.HORIZONS}
    arima = {}
    for order in orders:
        print(f"Menghitung ulang keadaan ARIMA{order} untuk {len(values)} kelompok...", flush=True)
        arima[order] = reference.rolling_arima(values, dates, combined, order)
    temporary = output.with_suffix(".csv.tmp")
    metadata = dict(schema_version="forecast-comparison-v1", source="colab-full-test-replay", synthetic=True,
                    generated_at=datetime.now(timezone.utc).isoformat(), test_period=list(reference.PERIODS["uji"]),
                    dataset_sha256=hashlib.sha256(dataset.read_bytes()).hexdigest(), archive_sha256=hashlib.sha256(archive.read_bytes()).hexdigest(),
                    group_count=len(identities), horizons={}, total_rows=0,
                    methodology="Parameter dipilih pada validasi; evaluasi uji berpasangan, tanpa fallback. ARIMA conditional least squares sesuai notebook, bukan batch statsmodels aplikasi.")
    with temporary.open("w", encoding="utf-8", newline="") as handle:
        writer = csv.writer(handle)
        writer.writerow(FIELDS)
        for horizon in reference.HORIZONS:
            inds = origins[horizon]
            bundle = bundles[horizon]
            model_meta = bundle["metadata"]
            if model_meta["feature_set"] != selected[horizon]["LightGBM"]:
                raise ValueError("Model PKL tidak sesuai varian pilihan validasi.")
            matrix, actual = reference.feature_matrix(values, identities, opened, pre_eid, prices, horizon, inds, model_meta["category_levels"])
            moving = reference.moving_average_predictions(classical, inds, horizon)
            order = ast.literal_eval(selected[horizon]["ARIMA"].removeprefix("ARIMA"))
            predictions = {
                "SMA": moving[selected[horizon]["SMA"]], "WMA": moving[selected[horizon]["WMA"]],
                "ARIMA": arima[order][:, np.searchsorted(combined, inds), horizon - 1].ravel(),
                "LightGBM": np.maximum(0, bundle["model"].predict(matrix[model_meta["feature_names"]], validate_features=True, num_threads=4)),
            }
            summary, max_difference = {}, 0.0
            for method, predicted in predictions.items():
                if len(predicted) != len(actual) or not np.isfinite(predicted).all() or (predicted < 0).any():
                    raise ValueError(f"Prediksi tidak lengkap/valid: {method}, {horizon} hari.")
                score = metrics(actual, predicted)
                expected = next(r for r in report if r["split"] == "uji" and int(r["horizon_hari"]) == horizon and r["metode"] == selected[horizon][method])
                difference = max(abs(score[key] - float(expected[key])) for key in ("mae", "rmse"))
                if score["n"] != int(expected["rows"]) or difference > 1e-5:
                    raise ValueError(f"Hasil rekonstruksi berbeda dari laporan Colab: {method}, {horizon} hari, selisih={difference}.")
                max_difference = max(max_difference, difference)
                summary[method] = {**score, "variant": selected[horizon][method]}
            for group_index, identity in enumerate(identities.to_dict("records")):
                for time_index, origin in enumerate(inds):
                    index = group_index * len(inds) + time_index
                    cutoff = dates[origin]
                    writer.writerow([horizon, identity["kode_cabang"], identity["kategori"], int(identity["kadar"]), cutoff.date().isoformat(),
                                     (cutoff + reference.pd.Timedelta(days=1)).date().isoformat(),
                                     (cutoff + reference.pd.Timedelta(days=horizon)).date().isoformat(), int(actual[index]),
                                     *(float(predictions[m][index]) for m in METHODS)])
            metadata["horizons"][str(horizon)] = dict(rows=len(actual), cutoff_start=dates[inds[0]].date().isoformat(), cutoff_end=dates[inds[-1]].date().isoformat(),
                                                    target_end=(dates[inds[-1]] + reference.pd.Timedelta(days=horizon)).date().isoformat(),
                                                    model_version=model_meta["model_version"], selected=selected[horizon], metrics=summary,
                                                    reference_max_difference=max_difference)
            metadata["total_rows"] += len(actual)
            print(f"Horizon {horizon}: {len(actual)} kasus, MAE/RMSE cocok dengan Colab; MAPE selesai.", flush=True)
    metadata["csv_sha256"] = hashlib.sha256(temporary.read_bytes()).hexdigest()
    temporary.replace(output)
    manifest = output.with_suffix(".json")
    manifest_temporary = manifest.with_suffix(".json.tmp")
    manifest_temporary.write_text(json.dumps(metadata, indent=2, allow_nan=False) + "\n", encoding="utf-8")
    manifest_temporary.replace(manifest)
    print(f"Tersimpan {metadata['total_rows']} kasus: {output}", flush=True)
    return metadata


if __name__ == "__main__":
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--dataset", type=Path, default=ROOT / "storage/app/private/dataset-forecast.csv")
    parser.add_argument("--archive", type=Path, default=ROOT / "docs/Hasil_LightGBM_ARIMA_Wijaya_Kusuma_Lead_Time_dan_Proyeksi.zip")
    parser.add_argument("--model-dir", type=Path, default=ROOT / "services/lightgbm/models")
    parser.add_argument("--output", type=Path, default=ROOT / "storage/app/private/forecast-comparison.csv")
    args = parser.parse_args()
    build(args.dataset, args.archive, args.output, args.model_dir)
