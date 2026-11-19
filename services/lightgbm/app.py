"""Internal inference API for the exact horizons exported by the Colab notebook."""
from datetime import date, datetime
from importlib.metadata import version
from pathlib import Path
from zoneinfo import ZoneInfo
import hashlib
import json
import logging
import math
import os
import pickle
import re

from flask import Flask, jsonify, request
import numpy as np
import pandas as pd
from werkzeug.exceptions import BadRequest, UnsupportedMediaType

HORIZONS = (3, 7, 14, 21, 30, 60, 90)
SCHEMA = "multihorizon-1"
LOG = logging.getLogger(__name__)


class InvalidInput(ValueError):
    pass


def load_models(folder):
    models, errors = {}, {}
    for horizon in HORIZONS:
        try:
            metadata = json.loads((folder / f"metadata_{horizon}d.json").read_text(encoding="utf-8"))
            if metadata["schema_version"] != SCHEMA or metadata["horizon_days"] != horizon:
                raise ValueError("Metadata horizon/schema mismatch")
            if metadata["timezone"] != "Asia/Jakarta" or metadata["history_days"] != 90:
                raise ValueError("Unsupported calendar/history contract")
            for package, expected in metadata["package_versions"].items():
                if expected != version(package):
                    raise ValueError(f"{package} runtime version mismatch")
            path = folder / f"lightgbm_{horizon}d.pkl"
            manifest = json.loads((folder / "manifest.json").read_text(encoding="utf-8"))
            for file_path in (path, folder / f"metadata_{horizon}d.json"):
                if hashlib.sha256(file_path.read_bytes()).hexdigest() != manifest[file_path.name]:
                    raise ValueError("Model/metadata checksum mismatch")
            with path.open("rb") as handle:
                bundle = pickle.load(handle)
            if json.loads(json.dumps(bundle["metadata"])) != metadata or bundle["model"].feature_name() != metadata["feature_names"]:
                raise ValueError("Pickle and metadata feature contract mismatch")
            models[horizon] = bundle
        except Exception:
            LOG.exception("Could not load LightGBM horizon %s", horizon)
            errors[horizon] = "model_load_failed"
    return models, errors


def validate_request(payload, models):
    if not isinstance(payload, dict):
        raise InvalidInput("Permintaan harus berupa objek JSON.")
    if payload.get("schema_version") != SCHEMA:
        raise InvalidInput("Versi skema tidak sesuai.")
    horizon = payload.get("horizon_days")
    if type(horizon) is not int or horizon not in HORIZONS:
        raise InvalidInput("Horizon tidak didukung; gunakan lead time 3, 7, 14, 21 atau proyeksi 30, 60, 90 hari.")
    metadata = models[horizon]["metadata"]
    if payload.get("model_version") != metadata["model_version"]:
        raise InvalidInput("Versi model tidak sesuai.")
    if payload.get("forecast_use") not in metadata["forecast_uses"]:
        raise InvalidInput("Model tidak dilatih untuk penggunaan ramalan ini.")
    value = payload.get("as_of_date")
    if not isinstance(value, str) or not re.fullmatch(r"\d{4}-\d{2}-\d{2}", value):
        raise InvalidInput("Tanggal potong harus YYYY-MM-DD.")
    try:
        origin = date.fromisoformat(value)
    except ValueError as error:
        raise InvalidInput("Tanggal potong tidak sah.") from error
    if origin >= datetime.now(ZoneInfo("Asia/Jakarta")).date():
        raise InvalidInput("Tanggal potong harus sebelum hari ini di Asia/Jakarta.")
    groups = payload.get("groups")
    if not isinstance(groups, list) or not 1 <= len(groups) <= 500:
        raise InvalidInput("groups harus memuat 1 sampai 500 kelompok.")
    keys, rows = set(), []
    for group in groups:
        if not isinstance(group, dict):
            raise InvalidInput("Kelompok harus berupa objek.")
        key = group.get("group_key")
        if not isinstance(key, str) or not key.strip() or len(key) > 200 or key in keys:
            raise InvalidInput("group_key harus unik dan tidak kosong.")
        keys.add(key)
        features = group.get("features")
        if not isinstance(features, dict) or set(features) != set(metadata["feature_names"]):
            raise InvalidInput(f"Fitur {key} tidak sesuai metadata.")
        for name, number in features.items():
            if number is None and name in metadata["nullable_features"]:
                continue
            if type(number) not in (int, float) or not math.isfinite(number):
                raise InvalidInput(f"Fitur {name} pada {key} harus numerik dan finite.")
            if name in metadata["category_levels"]:
                if number not in metadata["category_levels"][name].values():
                    raise InvalidInput(f"Kategori {name} pada {key} belum didukung.")
            elif name == "karat":
                if number not in metadata["known_karat"]:
                    raise InvalidInput(f"Kadar pada {key} belum didukung.")
            elif name.startswith(("sales_", "open_days_", "pre_eid_")) or name == "days_since_last_sale":
                if number < 0 or number != int(number):
                    raise InvalidInput(f"Fitur {name} harus bilangan bulat nonnegatif.")
                if name.startswith(("open_days_", "pre_eid_")) and number > horizon:
                    raise InvalidInput(f"Fitur {name} melampaui horizon.")
            elif name == "antam_idr_per_gram" and number <= 0:
                raise InvalidInput("Harga ANTAM harus positif.")
        rows.append(features)
    return horizon, metadata, groups, rows


def create_app(model_dir=None):
    app = Flask(__name__)
    app.config["MAX_CONTENT_LENGTH"] = 2 * 1024 * 1024
    folder = Path(model_dir or os.environ.get("LIGHTGBM_MODEL_DIR", Path(__file__).parent / "models"))
    models, errors = load_models(folder)
    app.extensions["lightgbm_models"] = models

    @app.get("/health")
    def health():
        ready = len(models) == len(HORIZONS)
        return jsonify(
            ready=ready, schema_version=SCHEMA,
            models=[{"horizon_days": h, "model_version": b["metadata"]["model_version"],
                     "forecast_uses": b["metadata"]["forecast_uses"],
                     "training_transactions": b["metadata"]["training_transactions"]}
                    for h, b in sorted(models.items())],
            errors=errors,
            runtime={name: version(name) for name in ("lightgbm", "numpy", 
                                                      "pandas", "scipy")},
        ), 200 if ready else 503

    @app.post("/predict")
    def predict():
        try:
            payload = request.get_json()
        except (BadRequest, UnsupportedMediaType):
            return jsonify(error={"code": "invalid_json", "message": "JSON tidak sah."}), 400
        if len(models) != len(HORIZONS):
            return jsonify(error={"code": "model_not_ready", "message": "Model belum siap."}), 503
        try:
            horizon, metadata, groups, rows = validate_request(payload, models)
        except InvalidInput as error:
            return jsonify(error={"code": "invalid_input", "message": str(error)}), 422
        try:
            matrix = pd.DataFrame(rows, columns=metadata["feature_names"]).astype(float)
            values = np.maximum(0.0, models[horizon]["model"].predict(matrix, validate_features=True, num_threads=1))
            if len(values) != len(groups) or not np.isfinite(values).all():
                raise ValueError("Invalid prediction output")
            return jsonify(
                schema_version=SCHEMA, model_version=metadata["model_version"],
                as_of_date=payload["as_of_date"], horizon_days=horizon, forecast_use=payload["forecast_use"],
                predictions=[{"group_key": group["group_key"], "predicted_units": float(units)}
                             for group, units in zip(groups, values)],
            )
        except Exception:
            LOG.exception("LightGBM prediction failed")
            return jsonify(error={"code": "prediction_failed", "message": "Inferensi model gagal."}), 503

    return app
