import unittest
from unittest.mock import patch, MagicMock
from arima_batch import fit_group, run

class ArimaTests(unittest.TestCase):
    def group(self, values, horizon=14):
        return dict(key='1|1|18K', values=values, horizon=horizon, cutoff='2026-01-01', input_hash='test')

    def test_empty_constant_and_sparse(self):
        for values in [[], [0]*180, [2]*180, [None]*180, [1]*10+[0]*170]:
            self.assertEqual('unavailable', fit_group(self.group(values))['status'])

    def test_valid_forecasts_and_horizon(self):
        values=[1+(i%7==0)+(i%3==0) for i in range(180)]
        for horizon in [14,21]:
            r=fit_group(self.group(values,horizon))
            self.assertEqual('ok', r['status'])
            self.assertEqual(horizon, len(r['daily']))
            self.assertTrue(all(y>=0 for y in r['daily']))
            self.assertAlmostEqual(sum(r['daily']),r['total'])

    def test_process_timeout(self):
        r=run(dict(version='test',group_timeout=.00001,groups=[self.group([1,2,3]*60)]))
        self.assertEqual('timeout',r['results'][0]['status'])

    def test_nonconvergent_models_are_not_used(self):
        fake=MagicMock()
        fake.fit.return_value.mle_retvals={'converged':False}
        with patch('statsmodels.tsa.arima.model.ARIMA',return_value=fake):
            result=fit_group(self.group([1,2,3]*60))
        self.assertEqual('unavailable',result['status'])
        self.assertIsNone(result['total'])

if __name__ == '__main__':
    unittest.main()
