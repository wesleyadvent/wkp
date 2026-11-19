"""JSON stdin/stdout contract. One isolated worker, bounded time per group."""
import json
import math
import multiprocessing as mp
import sys
import warnings
import os

os.environ.setdefault('OPENBLAS_NUM_THREADS', '1')
os.environ.setdefault('OMP_NUM_THREADS', '1')
ORDERS = [(p, d, q) for p in range(3) for d in range(2) for q in range(3) if p + q <= 3]

def fit_group(group):
    from statsmodels.tsa.arima.model import ARIMA
    import statsmodels
    values = group['values']
    last_missing = max((i for i, y in enumerate(values) if y is None), default=-1)
    values = values[last_missing + 1:]
    result = {'key': group['key'], 'cutoff': group['cutoff'], 'horizon': group['horizon'], 'input_hash': group['input_hash'], 'status': 'unavailable', 'total': None, 'history_days': len(values), 'library_version': statsmodels.__version__}
    if len(values) < 90 or sum(y > 0 for y in values) < 14:
        return dict(result, reason='Riwayat lengkap <90 hari atau hari penjualan positif <14.')
    if len(set(values)) == 1:
        return dict(result, reason='Deret konstan; model ARIMA tidak diestimasi.')
    best = None
    with warnings.catch_warnings():
        warnings.simplefilter('ignore')
        for order in ORDERS:
            try:
                fit = ARIMA(values, order=order).fit(method_kwargs={'maxiter': 100})
                if not fit.mle_retvals.get('converged', False) or not math.isfinite(fit.aicc):
                    continue
                predicted = [max(0.0, float(y)) for y in fit.forecast(steps=group['horizon'])]
                if not all(math.isfinite(y) for y in predicted):
                    continue
                if best is None or fit.aicc < best['aicc']:
                    best = dict(result, status='ok', total=sum(predicted), order=list(order), 
                                aicc=float(fit.aicc), daily=predicted, reason=None)
            except Exception:
                continue
    return best or dict(result, reason='Tidak ada kandidat yang konvergen dan valid.')

def run(payload):
    results = []
    pool = mp.Pool(1)
    try:
        for group in payload['groups']:
            try:
                result = pool.apply_async(fit_group, (group,)).get(timeout=payload.get('group_timeout', 12))
            except mp.TimeoutError:
                pool.terminate(); pool.join(); pool = mp.Pool(1)
                result = dict(key=group['key'], cutoff=group['cutoff'], horizon=group['horizon'], input_hash=group['input_hash'], status='timeout', total=None, reason='Batas waktu model terlampaui.')
            except Exception as error:
                result = dict(key=group['key'], cutoff=group['cutoff'], horizon=group['horizon'], input_hash=group['input_hash'], status='error', total=None, reason='Proses model gagal: ' + type(error).__name__)
            results.append(result)
    finally:
        pool.terminate(); pool.join()
    return {'version': payload['version'], 'results': results}

if __name__ == '__main__':
    mp.freeze_support()
    print(json.dumps(run(json.load(sys.stdin)), allow_nan=False))
