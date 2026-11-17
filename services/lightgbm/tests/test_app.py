import copy
import json
import logging
from pathlib import Path
import sys
import unittest

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))
from app import create_app

ROOT = Path(__file__).resolve().parents[1]


class InferenceTests(unittest.TestCase):
    @classmethod
    def setUpClass(cls):
        cls.app = create_app()
        cls.client = cls.app.test_client()
        cls.fixtures = {entry["input"]["lead_time_days"]: entry for entry in json.loads((ROOT / "fixtures/colab_lead_time.json").read_text())}
        cls.fixture = cls.fixtures[14]

    def payload(self, horizon=14):
        fixture = self.fixtures.get(horizon, self.fixture)
        features = copy.deepcopy(fixture["input"]["features"])
        if horizon not in self.fixtures:
            features[f"open_days_next_{horizon}"] = horizon
            features[f"pre_eid_days_next_{horizon}"] = features.pop("pre_eid_days_next_14")
            features.pop("open_days_next_14")
        return dict(schema_version="multihorizon-1", model_version=f"wk-lightgbm-{horizon}d-v2",
                    as_of_date="2025-10-01", horizon_days=horizon,
                    forecast_use="lead_time" if horizon in (3, 7, 14, 21) else "projection",
                    groups=[dict(group_key="DSA:ANTING:8", features=features)])

    def test_actual_pickle_matches_colab_fixture(self):
        for horizon, fixture in self.fixtures.items():
            with self.subTest(horizon=horizon):
                response = self.client.post("/predict", json=self.payload(horizon))
                self.assertEqual(200, response.status_code, response.json)
                self.assertAlmostEqual(fixture["output"]["predicted_units"], response.json["predictions"][0]["predicted_units"], delta=1e-8)
        health = self.client.get("/health")
        self.assertEqual(200, health.status_code)
        self.assertEqual([3, 7, 14, 21, 30, 60, 90], [m["horizon_days"] for m in health.json["models"]])

    def test_real_projection_models_and_batch_identifiers(self):
        expected = json.loads((ROOT / "fixtures/colab_projection_units.json").read_text())
        for horizon in (30, 60, 90):
            payload = self.payload(horizon)
            payload["groups"].append(dict(group_key="another-group", features=payload["groups"][0]["features"]))
            response = self.client.post("/predict", json=payload)
            self.assertEqual(200, response.status_code, response.json)
            self.assertEqual(["DSA:ANTING:8", "another-group"], [p["group_key"] for p in response.json["predictions"]])
            self.assertTrue(all(p["predicted_units"] >= 0 for p in response.json["predictions"]))
            self.assertAlmostEqual(expected[str(horizon)], response.json["predictions"][0]["predicted_units"], delta=1e-8)

    def test_invalid_contracts_are_rejected(self):
        cases = []
        for changes in ({"horizon_days": 20}, {"model_version": "wrong"}, {"as_of_date": "2025-02-30"}, {"schema_version": "wrong"}, {"forecast_use": "projection"}):
            cases.append({**self.payload(), **changes})
        for feature, value in (("branch_code", 999), ("karat", 1), ("sales_7d", None), ("sales_14d", -1), ("sales_30d", float("inf")), ("open_days_next_14", 15), ("karat", True)):
            payload = self.payload()
            payload["groups"][0]["features"][feature] = value
            cases.append(payload)
        duplicate = self.payload()
        duplicate["groups"] *= 2
        cases.append(duplicate)
        for payload in cases:
            response = self.client.post("/predict", json=payload)
            self.assertEqual(422, response.status_code, response.json)
        self.assertEqual(400, self.client.post("/predict", data="{bad", content_type="application/json").status_code)

    def test_horizons_reject_the_wrong_forecast_use(self):
        for horizon in (3, 7, 14, 21, 30, 60, 90):
            with self.subTest(horizon=horizon):
                payload = self.payload(horizon)
                payload["forecast_use"] = "projection" if horizon <= 21 else "lead_time"
                self.assertEqual(422, self.client.post("/predict", json=payload).status_code)

    def test_missing_model_is_not_reported_as_ready(self):
        logging.disable(logging.CRITICAL)
        try:
            client = create_app(ROOT / "tests/nonexistent-models").test_client()
        finally:
            logging.disable(logging.NOTSET)
        self.assertEqual(503, client.get("/health").status_code)
        self.assertEqual(503, client.post("/predict", json=self.payload()).status_code)


if __name__ == "__main__":
    unittest.main()
