import unittest
import numpy as np
import pandas as pd

import colab_comparison as reference
from build_comparison import metrics


class ComparisonTests(unittest.TestCase):
    def test_all_lead_times_have_complete_test_targets(self):
        self.assertEqual((3, 7, 14, 21, 30, 60, 90), reference.HORIZONS)
        dates = pd.date_range('2023-01-01', '2026-07-31', name=reference.DATE)
        for horizon, count, end in ((3, 301, '2026-07-28'), (7, 297, '2026-07-24'), (14, 290, '2026-07-17'), (21, 283, '2026-07-10')):
            with self.subTest(horizon=horizon):
                indexes = reference.origin_indexes(dates, horizon, 'uji')
                self.assertEqual(count, len(indexes))
                self.assertEqual(pd.Timestamp('2025-10-01'), dates[indexes[0]])
                self.assertEqual(pd.Timestamp(end), dates[indexes[-1]])
                self.assertEqual(pd.Timestamp('2026-07-31'), dates[indexes[-1]] + pd.Timedelta(days=horizon))

    def test_mape_excludes_only_zero_actuals(self):
        result = metrics(np.array([10., 0., 20.]), np.array([12., 5., 15.]))
        self.assertEqual(3, result['n'])
        self.assertAlmostEqual(4, result['mae'])
        self.assertAlmostEqual(np.sqrt(18), result['rmse'])
        self.assertAlmostEqual(22.5, result['mape'])
        self.assertEqual(2, result['mape_n'])
        self.assertIsNone(metrics(np.array([0.]), np.array([1.]))['mape'])

    def test_future_actual_changes_targets_but_not_features_or_forecasts(self):
        dates = pd.date_range('2023-01-01', '2026-07-31', name=reference.DATE)
        values = np.array([[1 + (i % 7 == 0) + (i % 3 == 0) for i in range(len(dates))]], dtype=float)
        origin = np.array([1004])
        edited = values.copy()
        edited[:, origin[0] + 1:] += 10
        identities = pd.DataFrame([{'kode_cabang': 'DSA', 'kategori': 'ANTING', 'kadar': 8}])
        opened = np.ones_like(values)
        pre_eid = np.zeros(len(dates))
        prices = pd.DataFrame({reference.PRICE: np.full(len(dates), 100), reference.PRICE_CHANGE: np.zeros(len(dates))}, index=dates)
        levels = {'branch_code': {'DSA': 0}, 'category_code': {'ANTING': 0}}
        before_x, before_y = reference.feature_matrix(values, identities, opened, pre_eid, prices, 14, origin, levels)
        after_x, after_y = reference.feature_matrix(edited, identities, opened, pre_eid, prices, 14, origin, levels)
        np.testing.assert_array_equal(before_x, after_x)
        self.assertEqual(140, (after_y - before_y)[0])
        for method in ('SMA60', 'WMA90'):
            before = reference.moving_average_predictions(reference.sma_wma_forecasts(values), origin, 14)[method]
            after = reference.moving_average_predictions(reference.sma_wma_forecasts(edited), origin, 14)[method]
            np.testing.assert_allclose(before, after)
        np.testing.assert_allclose(reference.rolling_arima(values, dates, origin, (1, 0, 1)),
                                   reference.rolling_arima(edited, dates, origin, (1, 0, 1)))


if __name__ == '__main__':
    unittest.main()
