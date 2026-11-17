<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">Perbandingan Akurasi Ramalan</h2>
        <p class="mt-1 text-sm text-gray-500">SMA, WMA, ARIMA dan LightGBM dinilai terhadap penjualan aktual pada observasi uji yang sama.</p>
    </x-slot>

    @php
        $metricLabels = ['mae' => 'MAE', 'rmse' => 'RMSE', 'mape' => 'MAPE'];
        $format = fn ($value, $places = 3) => $value === null ? '—' : number_format($value, $places, ',', '.');
        $metric = $filters['metric'];
    @endphp

    <div class="py-8">
        <div class="mx-auto max-w-screen-2xl space-y-5 sm:px-6 lg:px-8">
            <form method="GET" action="{{ route('forecast-comparison.index') }}" class="grid items-end gap-4 rounded-xl bg-white p-5 shadow-sm md:grid-cols-3 xl:grid-cols-4">
                <div>
                    <x-input-label for="horizon" value="Horizon ramalan" />
                    <select id="horizon" name="horizon" class="mt-1 block w-full rounded-md border-gray-300">
                        @foreach (\App\Services\ForecastComparisonService::HORIZONS as $days)
                            <option value="{{ $days }}" @selected($filters['horizon'] === $days)>{{ $days }} hari{{ in_array($days, \App\Services\ForecastComparisonService::LEAD_TIME_HORIZONS, true) ? ' · Lead time' : ' · Proyeksi' }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <x-input-label for="branch_id" value="Cabang" />
                    @if (Auth::user()->isAdmin())
                        <select id="branch_id" name="branch_id" class="mt-1 block w-full rounded-md border-gray-300">
                            <option value="">Seluruh cabang</option>
                            @foreach ($branches as $branch)
                                <option value="{{ $branch->id }}" @selected($selectedBranchId === $branch->id)>{{ $branch->nama_cabang }}</option>
                            @endforeach
                        </select>
                    @else
                        <div class="mt-1 rounded-md border border-gray-200 bg-gray-50 px-3 py-2 text-sm">{{ $branchName }}</div>
                    @endif
                </div>
                <div>
                    <x-input-label for="metric" value="Urutkan metode berdasarkan" />
                    <select id="metric" name="metric" class="mt-1 block w-full rounded-md border-gray-300">
                        @foreach ($metricLabels as $key => $label)
                            <option value="{{ $key }}" @selected($metric === $key)>{{ $label }} terendah</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <x-input-label for="category" value="Kategori barang" />
                    <select id="category" name="category" class="mt-1 block w-full rounded-md border-gray-300">
                        <option value="">Seluruh kategori</option>
                        @foreach (['ANTING', 'CINCIN', 'GELANG', 'KALUNG', 'LIONTIN'] as $category)
                            <option value="{{ $category }}" @selected($filters['category'] === $category)>{{ ucfirst(strtolower($category)) }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <x-input-label for="from" value="Tanggal potong mulai" />
                    <x-text-input type="date" id="from" name="from" :value="$filters['from']" class="mt-1 block w-full" />
                </div>
                <div>
                    <x-input-label for="to" value="Tanggal potong akhir" />
                    <x-text-input type="date" id="to" name="to" :value="$filters['to']" class="mt-1 block w-full" />
                </div>
                <div>
                    <x-input-label for="karat" value="Kadar" />
                    <select id="karat" name="karat" class="mt-1 block w-full rounded-md border-gray-300">
                        <option value="">Seluruh kadar</option>
                        @foreach (range(8, 24) as $karat)
                            <option value="{{ $karat }}" @selected($filters['karat'] === $karat)>{{ $karat }}K</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <x-input-label for="q" value="Cari kelompok" />
                    <x-text-input id="q" name="q" :value="$filters['q']" placeholder="Cabang, kategori, atau kadar" class="mt-1 block w-full" />
                </div>
                <div class="flex gap-2 md:col-span-3 xl:col-span-4">
                    <x-primary-button>Bandingkan</x-primary-button>
                    <a href="{{ route('forecast-comparison.index') }}" class="inline-flex items-center rounded-md border border-gray-300 px-3 py-2 text-sm text-gray-600">Reset</a>
                </div>
                @if ($errors->any())
                    <div class="text-sm text-rose-700 md:col-span-3 xl:col-span-4">{{ $errors->first() }}</div>
                @endif
            </form>

            @if ($error)
                <p class="rounded-lg border border-amber-200 bg-amber-50 p-5 text-sm text-amber-900">{{ $error }}</p>
            @elseif ($result['count'] === 0)
                <div class="rounded-xl border border-gray-200 bg-white p-8 text-center">
                    <p class="font-semibold text-gray-800">Belum ada observasi uji yang cocok.</p>
                    <p class="mt-2 text-sm text-gray-500">Sesuaikan cabang, kelompok, atau tanggal potong. Hasil pengujian tersedia pada periode historis yang tercantum di bawah.</p>
                </div>
            @else
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-5">
                    @if ($result['winners'])
                        <p class="text-xs font-semibold uppercase tracking-wide text-emerald-700">{{ count($result['winners']) > 1 ? 'Peringkat pertama bersama' : 'Metode paling akurat' }} berdasarkan {{ $metricLabels[$metric] }}</p>
                        <p class="mt-2 text-2xl font-bold text-emerald-900">{{ implode(' & ', $result['winners']) }}</p>
                        <p class="mt-1 text-sm text-emerald-800">{{ $metricLabels[$metric] }} {{ $format($result['best'][$metric]) }}{{ $metric === 'mape' ? '%' : ' unit' }} pada {{ number_format($result['count'], 0, ',', '.') }} observasi uji dengan horizon {{ $filters['horizon'] }} hari.</p>
                    @else
                        <p class="font-semibold text-emerald-900">Peringkat MAPE belum dapat ditentukan.</p>
                        <p class="mt-1 text-sm text-emerald-800">Semua aktual pada pilihan ini bernilai nol. Pilih MAE atau RMSE untuk membandingkan metode.</p>
                    @endif
                    <p class="mt-3 text-xs text-emerald-800">Hasil berlaku untuk pilihan data ini. Semakin kecil MAE, RMSE, atau MAPE, semakin kecil galat ramalannya.</p>
                </div>

                <div class="grid gap-4 sm:grid-cols-3">
                    @foreach ($metricLabels as $key => $label)
                        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                            <p class="text-sm text-gray-500">{{ $label }} terendah</p>
                            <p class="mt-2 text-2xl font-bold text-slate-900">{{ $format($result['best'][$key]) }}<span class="ml-1 text-sm font-normal text-gray-500">{{ $key === 'mape' ? '%' : 'unit' }}</span></p>
                            <p class="mt-2 text-sm font-medium text-slate-600">{{ implode(' & ', $result['metric_winners'][$key]) ?: 'Tidak terdefinisi' }}</p>
                        </div>
                    @endforeach
                </div>

                <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                    <div class="border-b border-gray-100 px-5 py-4">
                        <h3 class="font-semibold text-gray-900">Peringkat metode</h3>
                        <p class="mt-1 text-xs text-gray-500">{{ number_format($result['group_count'], 0, ',', '.') }} kelompok · {{ $result['date_count'] }} tanggal potong · {{ $result['first_cutoff'] }} s.d. {{ $result['last_cutoff'] }} · Aktual sampai {{ $result['target_end'] }}</p>
                    </div>
                    <div class="table-scroll">
                        <table class="data-table w-full text-sm">
                            <thead><tr><th>Peringkat</th><th>Metode &amp; konfigurasi uji</th><th class="text-right">MAE (unit)</th><th class="text-right">RMSE (unit)</th><th class="text-right">MAPE (%)</th><th class="text-right">Observasi</th></tr></thead>
                            <tbody>
                                @foreach ($result['rankings'] as $row)
                                    <tr class="{{ $row['rank'] === 1 ? 'bg-emerald-50' : '' }}">
                                        <td><span class="font-semibold text-gray-800">{{ $row['rank'] ?? '—' }}</span></td>
                                        <td><span class="font-semibold text-gray-900">{{ $row['method'] }}</span><span class="table-secondary">{{ $row['variant'] }}</span></td>
                                        @foreach (['mae', 'rmse', 'mape'] as $key)
                                            <td class="text-right tabular-nums {{ in_array($row['method'], $result['metric_winners'][$key], true) ? 'font-bold text-emerald-700' : '' }}">{{ $format($row[$key]) }}</td>
                                        @endforeach
                                        <td class="text-right tabular-nums">{{ number_format($row['n'], 0, ',', '.') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <p class="border-t border-gray-100 px-5 py-4 text-xs text-gray-500">MAPE dihitung pada {{ number_format($result['mape_n'], 0, ',', '.') }} observasi dengan aktual &gt; 0. {{ number_format($result['zero_actual'], 0, ',', '.') }} observasi aktual nol tetap masuk MAE/RMSE dan dikecualikan dari MAPE. Metrik memakai angka asli sebelum pembulatan.</p>
                    @if ($result['excluded'])
                        <p class="px-5 pb-4 text-xs text-amber-700">{{ $result['excluded'] }} observasi tidak lengkap dikeluarkan untuk seluruh metode agar cakupannya sama.</p>
                    @endif
                </div>

                <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                    <h3 class="font-semibold text-gray-900">Galat {{ $metricLabels[$metric] }} per metode</h3>
                    <p class="mt-1 text-xs text-gray-500">Batang lebih pendek menunjukkan galat lebih kecil.</p>
                    @php $maximum = max(array_map(fn ($row) => $row[$metric] ?? 0, $result['rankings'])); @endphp
                    <div class="mt-5 space-y-4">
                        @foreach ($result['rankings'] as $row)
                            <div class="grid grid-cols-4 items-center gap-3 text-sm">
                                <span class="font-medium text-slate-700">{{ $row['method'] }}</span>
                                <div class="col-span-2 h-3 overflow-hidden rounded-full bg-slate-100"><div class="h-full rounded-full {{ $row['rank'] === 1 ? 'bg-emerald-500' : 'bg-slate-400' }}" style="width: {{ $maximum > 0 ? 100 * ($row[$metric] ?? 0) / $maximum : 0 }}%"></div></div>
                                <span class="text-right tabular-nums text-slate-700">{{ $format($row[$metric]) }}{{ $metric === 'mape' && $row[$metric] !== null ? '%' : '' }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                    <div class="border-b border-gray-100 px-5 py-4"><h3 class="font-semibold text-gray-900">Prediksi dan penjualan aktual</h3><p class="mt-1 text-xs text-gray-500">Satu observasi adalah satu kelompok dan tanggal potong. Aktual menjumlahkan unit terjual dari hari berikutnya sampai hari ke-{{ $filters['horizon'] }}.</p></div>
                    <div class="table-scroll">
                        <table class="data-table w-full text-sm">
                            <thead><tr><th>Kelompok barang</th><th>Tanggal potong &amp; target</th><th class="text-right">Aktual</th>@foreach (\App\Services\ForecastComparisonService::METHODS as $method)<th class="text-right">{{ $method }}</th>@endforeach</tr></thead>
                            <tbody>
                                @forelse ($details as $row)
                                    <tr>
                                        <td><span class="font-semibold">{{ ucfirst(strtolower($row['category'])) }} · {{ $row['karat'] }}K</span><span class="table-secondary">{{ $row['branch'] }}</span></td>
                                        <td>{{ $row['cutoff'] }}<span class="table-secondary">{{ $row['target_start'] }} s.d. {{ $row['target_end'] }}</span></td>
                                        <td class="text-right font-bold tabular-nums">{{ $format($row['actual'], 0) }}</td>
                                        @foreach ($row['predictions'] as $prediction)
                                            <td class="text-right tabular-nums" title="Galat absolut {{ $format(abs($prediction - $row['actual'])) }} unit">{{ $format($prediction, 0) }}</td>
                                        @endforeach
                                    </tr>
                                @empty
                                    <tr><td colspan="7" class="py-6 text-center text-gray-500">Tidak ada observasi pada halaman ini.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="border-t border-gray-100 px-5 py-4">{{ $details->links() }}</div>
                </div>
            @endif

            <details class="rounded-xl border border-gray-200 bg-white p-5 text-sm text-gray-600" open>
                <summary class="cursor-pointer font-semibold text-gray-900">Dasar penilaian dan sumber data</summary>
                <div class="mt-4 space-y-3 leading-relaxed">
                    <p>Evaluasi historis mengikuti notebook Colab pada data transaksi <strong>sintetis</strong>. Parameter SMA/WMA, orde ARIMA dan varian LightGBM dipilih pada validasi, kemudian dinilai pada data uji Oktober 2025–Juli 2026. Jumlah kasus dan tanggal uji bergantung pada horizon agar target selesai dalam periode uji.</p>
                    <p>Semua metode memakai kelompok, tanggal potong dan aktual yang sama. MAE/RMSE hasil rekonstruksi diperiksa terhadap laporan Colab asli. Halaman membaca hasil evaluasi tersimpan, sehingga tetap dapat dibuka ketika Flask dimatikan.</p>
                    <p>Jendela SMA/WMA yang digunakan tercantum pada tabel. ARIMA mengikuti perhitungan notebook Colab; implementasi ARIMA operasional memakai batch statsmodels dengan aturan estimasi tersendiri. Peringkat ini berlaku untuk konfigurasi uji yang ditampilkan.</p>
                    <p><strong>MAE</strong> = rata-rata |ramalan − aktual|. <strong>RMSE</strong> = akar rata-rata (ramalan − aktual)². <strong>MAPE</strong> = rata-rata |ramalan − aktual| ÷ aktual × 100%, hanya untuk aktual &gt; 0. Lihat <a class="text-amber-700 underline" href="https://otexts.com/fpp3/accuracy.html" target="_blank" rel="noopener noreferrer">rujukan pengukuran akurasi</a>.</p>
                    @if ($metadata)
                        <p class="text-xs text-gray-500">Hasil dibangun {{ $metadata['generated_at'] ?? '—' }}. {{ number_format($metadata['total_rows'] ?? 0, 0, ',', '.') }} observasi tersedia untuk seluruh horizon.</p>
                    @endif
                </div>
            </details>
        </div>
    </div>
</x-app-layout>
