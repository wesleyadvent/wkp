"""Reference calculations from the user-supplied Colab notebook.

Copied function definitions only from cells 8, 10, 12, 14, 16, 18.
No notebook training, downloads, or top-level evaluation code is executed.
"""
import numpy as np
import pandas as pd
from scipy.optimize import minimize
from scipy.signal import lfilter

GROUP = ["kode_cabang", "kategori", "kadar"]
DATE = "tanggal_ramalan"
SALES = "penjualan_hari_ini"
PRICE = "harga_antam"
PRICE_CHANGE = "perubahan_harga_7_persen"
WINDOWS = (7, 14, 30, 60, 90)
HORIZONS = (3, 7, 14, 21, 30, 60, 90)
PROJECTION_HORIZONS = (30, 60, 90)
PERIODS = {"latih": ("2023-01-01", "2024-12-31"), "validasi": ("2025-01-01", "2025-09-30"), "uji": ("2025-10-01", "2026-07-31")}
EID_DATES = pd.to_datetime(["2023-04-22", "2024-04-10", "2025-03-31", "2026-03-21"])
CLOSED_WEEKDAY = {"PWG": 5, "MDR": 6, "SPH": 6, "DSA": None}


def load_panel(path):
    columns = [*GROUP, DATE, SALES, PRICE, PRICE_CHANGE, 'tanggal_sumber_harga']
    frame = pd.read_csv(path, usecols=columns)
    frame[DATE] = pd.to_datetime(frame[DATE], format='%Y-%m-%d')
    frame['tanggal_sumber_harga'] = pd.to_datetime(frame['tanggal_sumber_harga'], format='%Y-%m-%d')
    if frame[[*GROUP, DATE, SALES]].isna().any().any():
        raise ValueError('Identitas kelompok, tanggal, dan penjualan harian wajib terisi.')
    for name in GROUP[:2]:
        frame[name] = frame[name].astype(str).str.strip().str.upper()
    karat = pd.to_numeric(frame['kadar'].astype(str).str.upper().str.removesuffix('K'))
    if not (karat.between(8, 24) & karat.mod(1).eq(0)).all():
        raise ValueError('Kadar harus berupa bilangan bulat dalam rentang 8–24.')
    frame['kadar'] = karat.astype(int)
    if not np.isfinite(frame[SALES]).all() or not (frame[SALES].ge(0) & frame[SALES].mod(1).eq(0)).all():
        raise ValueError('Penjualan harian harus berupa jumlah unit nonnegatif.')
    if frame['tanggal_sumber_harga'].gt(frame[DATE]).any():
        raise ValueError('Tanggal sumber harga melewati tanggal ramalan.')
    if not set(frame['kode_cabang'].unique()).issubset(CLOSED_WEEKDAY):
        raise ValueError('Jadwal cabang belum tersedia.')
    series = frame.pivot(index=GROUP, columns=DATE, values=SALES).sort_index(axis=1)
    dates = series.columns
    if not dates.equals(pd.date_range(dates.min(), dates.max(), name=DATE)):
        raise ValueError('Ada tanggal yang hilang.')
    if series.isna().any().any():
        raise ValueError('Setiap kelompok perlu memiliki penjualan harian lengkap.')
    if dates[-1] < pd.Timestamp(PERIODS['uji'][1]):
        raise ValueError('Riwayat belum mencakup seluruh periode uji.')
    values = series.to_numpy(dtype=float)
    identities = series.index.to_frame(index=False)
    offsets = (dates.to_numpy()[:, None] - EID_DATES.to_numpy()[None, :]) / np.timedelta64(1, 'D')
    holiday = (offsets == 0).any(axis=1)
    extended = ((np.abs(offsets) <= 21) & (offsets != 0)).any(axis=1)
    pre_eid = ((offsets >= -21) & (offsets <= -1)).any(axis=1)
    schedules = {branch: ~holiday & (extended | (dates.dayofweek.to_numpy() != weekday)) for branch, weekday in CLOSED_WEEKDAY.items()}
    opened = np.stack([schedules[code] for code in identities['kode_cabang']])
    if values[~opened].any():
        raise ValueError('Ditemukan penjualan pada tanggal cabang tutup.')
    daily_price = frame[[DATE, PRICE, PRICE_CHANGE]].drop_duplicates()
    if daily_price[DATE].duplicated().any():
        raise ValueError('Harga acuan berbeda untuk tanggal yang sama.')
    daily_price = daily_price.set_index(DATE).reindex(dates)
    if not np.isfinite(daily_price[[PRICE, PRICE_CHANGE]].stack()).all():
        raise ValueError('Fitur harga harus berupa angka terbatas atau kosong.')
    if daily_price[PRICE].dropna().le(0).any():
        raise ValueError('Harga ANTAM harus positif.')
    return (values, dates, identities, opened, pre_eid, daily_price)

def origin_indexes(dates, horizon, split):
    start, end = map(pd.Timestamp, PERIODS[split])
    cutoff_horizon = horizon
    if split != 'latih' and horizon in PROJECTION_HORIZONS:
        cutoff_horizon = max(PROJECTION_HORIZONS)
    eligible = (dates >= start) & (dates + pd.Timedelta(days=cutoff_horizon) <= end)
    return np.flatnonzero(eligible & (np.arange(len(dates)) >= max(WINDOWS) - 1))

def prefix_sum(values):
    return np.pad(np.cumsum(values, axis=-1), [(0, 0)] * (values.ndim - 1) + [(1, 0)])

def feature_matrix(values, identities, opened, pre_eid, daily_price, horizon, origins, levels):
    n_groups = len(identities)
    n_dates = len(origins)
    sales = prefix_sum(values)
    opened_prefix = prefix_sum(opened)
    eid_prefix = prefix_sum(pre_eid)
    time_indexes = np.arange(values.shape[1])
    last_sale = np.maximum.accumulate(np.where(values > 0, time_indexes, -1), axis=1)
    last_sale = last_sale[:, origins]
    elapsed = np.where(last_sale < 0, np.nan, origins - last_sale)
    data = {'branch_code': np.repeat(identities['kode_cabang'].map(levels['branch_code']), n_dates), 'category_code': np.repeat(identities['kategori'].map(levels['category_code']), n_dates), 'karat': np.repeat(identities['kadar'].to_numpy(), n_dates)}
    for n in WINDOWS:
        data[f'sales_{n}d'] = (sales[:, origins + 1] - sales[:, origins + 1 - n]).ravel()
    data['days_since_last_sale'] = elapsed.ravel()
    data[f'open_days_next_{horizon}'] = (opened_prefix[:, origins + horizon + 1] - opened_prefix[:, origins + 1]).ravel()
    data[f'pre_eid_days_next_{horizon}'] = np.tile(eid_prefix[origins + horizon + 1] - eid_prefix[origins + 1], n_groups)
    data['antam_idr_per_gram'] = np.tile(daily_price[PRICE].to_numpy()[origins], n_groups)
    data['antam_change_7d_pct'] = np.tile(daily_price[PRICE_CHANGE].to_numpy()[origins], n_groups)
    actual = (sales[:, origins + horizon + 1] - sales[:, origins + 1]).ravel()
    return (pd.DataFrame(data), actual)

def score(actual, prediction):
    delta = np.asarray(prediction) - np.asarray(actual)
    return {'rows': int(delta.size), 'mae': float(np.abs(delta).mean()), 'rmse': float(np.sqrt(np.square(delta).mean())), 'bias': float(delta.mean())}

def sma_wma_forecasts(values):
    totals = prefix_sum(values)
    widths = np.arange(1, values.shape[1] + 1)
    weighted = prefix_sum(values * widths)
    forecasts = {}
    for n in (30, 60, 90):
        endpoints = np.arange(n, values.shape[1] + 1)
        sum_y = totals[:, endpoints] - totals[:, endpoints - n]
        sum_wy = weighted[:, endpoints] - weighted[:, endpoints - n]
        forecasts[f'SMA{n}'] = sum_y / n
        forecasts[f'WMA{n}'] = (sum_wy - (endpoints - n) * sum_y) / (n * (n + 1) / 2)
    return forecasts

def moving_average_predictions(classical, origins, horizon):
    return {name: daily[:, origins - int(name[3:]) + 1].ravel() * horizon for name, daily in classical.items()}

def fit_arima(train, order):
    """Fit a stationary/invertible ARIMA(p,d,q) with conditional least squares."""
    p, d, q = order
    series = np.diff(train) if d else train
    center = 0.0 if d else float(series.mean())
    centered = series - center
    if np.all(centered == 0):
        return (center, 0.0, 0.0)

    def coeffs(params):
        return (float(params[0]) if p else 0.0, float(params[p]) if q else 0.0)

    def objective(params):
        phi, theta = coeffs(params)
        errors = lfilter([1.0, -phi], [1.0, theta], centered)
        return float(np.mean(errors[1:] ** 2))
    bounds = [(-0.98, 0.98)] * (p + q)
    result = minimize(objective, np.zeros(p + q), method='L-BFGS-B', bounds=bounds)
    if not result.success:
        result = minimize(objective, np.zeros(p + q), method='Powell', bounds=bounds)
    if not result.success or not np.isfinite(result.fun):
        raise RuntimeError(f'ARIMA{order} tidak konvergen.')
    return (center, *coeffs(result.x))

def rolling_arima(values, dates, origins, order):
    """Keep fitted parameters fixed. Update each forecast state with actuals through t."""
    p, d, q = order
    train_end = dates.searchsorted(pd.Timestamp(PERIODS['latih'][1]), side='right')
    if train_end == 0 or int(origins.min()) < train_end:
        raise ValueError('Tanggal ramalan ARIMA harus sesudah periode estimasi.')
    predictions = np.empty((len(values), len(origins), max(HORIZONS)))
    steps = np.arange(max(HORIZONS))
    for index, sales in enumerate(values):
        center, phi, theta = fit_arima(sales[:train_end], order)
        series = np.diff(sales) if d else sales
        centered = series - center
        errors = lfilter([1.0, -phi], [1.0, theta], centered)
        state = origins - d
        first = phi * centered[state] + theta * errors[state]
        ahead = center + first[:, None] * np.power(phi, steps)
        if d:
            ahead = sales[origins, None] + np.cumsum(ahead, axis=1)
        predictions[index] = np.cumsum(np.maximum(ahead, 0), axis=1)
    return predictions
