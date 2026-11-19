<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-xl font-semibold leading-tight text-gray-800">Analisis Persediaan {{ $metode }}</h2>
                <p class="mt-1 text-sm text-gray-500">Tren penjualan dan rekomendasi persediaan berdasarkan kategori dan kadar.</p>
            </div>
            @if (Auth::user()->isAdmin())
                <a href="{{ route($settingsPrefix.'.index') }}" class="inline-flex items-center rounded-md border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Konfigurasi {{ $metode }}</a>
            @endif
        </div>
    </x-slot>

    @php
        $trendBadges = [
            'data_belum_lengkap' => 'bg-slate-100 text-slate-700',
            'baru' => 'bg-sky-100 text-sky-800',
            'meningkat' => 'bg-emerald-100 text-emerald-800',
            'stabil' => 'bg-slate-100 text-slate-700',
            'menurun' => 'bg-rose-100 text-rose-800',
        ];
        $stockBadges = [
            'data_belum_lengkap' => 'bg-slate-100 text-slate-700',
            'ramalan_belum_tersedia' => 'bg-slate-100 text-slate-700',
            'aman' => 'bg-emerald-100 text-emerald-800',
            'perlu_perhatian' => 'bg-amber-100 text-amber-800',
            'kritis' => 'bg-rose-100 text-rose-800',
        ];
    @endphp

    <div class="py-8">
        <div class="mx-auto max-w-screen-2xl space-y-5 sm:px-6 lg:px-8">
            <form method="GET" action="{{ route($analysisRoute) }}" class="grid items-end gap-3 rounded-lg bg-white p-4 shadow-sm md:grid-cols-4">
                <div><x-input-label for="cutoff" value="Tanggal potong" /><x-text-input type="date" id="cutoff" name="cutoff" :value="$cutoff" :max="now('Asia/Jakarta')->subDay()->toDateString()" class="mt-1 block w-full" /></div>
                @if (Auth::user()->isAdmin())
                    <div>
                        <x-input-label for="branch_id" value="Cabang" />
                        <select id="branch_id" name="branch_id" class="mt-1 block w-full rounded-md border-gray-300">
                            <option value="">Seluruh cabang</option>
                            @foreach ($branches as $branch)
                                <option value="{{ $branch->id }}" @selected($selectedBranchId === $branch->id)>{{ $branch->nama_cabang }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('branch_id')" class="mt-2" />
                    </div>
                @else
                    <div>
                        <x-input-label value="Cabang" />
                        <div class="mt-1 rounded-md border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-700">Data cabang Anda</div>
                    </div>
                @endif
                <div>
                    <x-input-label for="periode" value="Jendela analisis &amp; ramalan" />
                    <select id="periode" name="periode" class="mt-1 block w-full rounded-md border-gray-300">
                        @foreach (\App\Services\InventoryAnalysisService::PERIODS as $option)
                            <option value="{{ $option }}" @selected($periode === $option)>{{ $option }} hari</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('periode')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="q" value="Cari kelompok" />
                    <x-text-input id="q" name="q" class="mt-1 block w-full" :value="request('q')" placeholder="Cabang, kategori, atau kadar" />
                    <x-input-error :messages="$errors->get('q')" class="mt-2" />
                </div>
                <div class="flex h-10 gap-2">
                    <x-primary-button>Analisis</x-primary-button>
                    <a href="{{ route($analysisRoute) }}" class="inline-flex items-center rounded-md border border-gray-300 px-3 text-sm text-gray-600 hover:bg-gray-50">Reset</a>
                </div>
            </form>

            @if($metode === 'ARIMA' && $arimaPending > 0)
                <div role="status" aria-live="polite" class="space-y-2 rounded border border-sky-200 bg-sky-50 p-4 text-sm text-sky-900"
                    data-arima-prepare="{{ route('inventory-analysis.arima.prepare') }}" data-cutoff="{{ $cutoff }}" data-branch="{{ $selectedBranchId }}">
                    <p class="font-semibold">ARIMA sedang dihitung otomatis untuk tanggal yang dipilih.</p>
                    <p data-arima-progress>{{ $arimaPending }} kelompok menunggu perhitungan. Biarkan halaman ini terbuka; hasil akan diperbarui setelah selesai.</p>
                    <p>Kelompok yang belum memenuhi syarat riwayat tetap akan disertai alasan.</p>
                    <noscript>Aktifkan JavaScript agar perhitungan otomatis dapat berjalan.</noscript>
                </div>
            @endif

            @if($metode === 'LightGBM')
                @if($lightgbmStatus && $lightgbmStatus['state'] !== 'ready')
                    <div role="alert" class="space-y-2 rounded border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
                        <p class="font-semibold">{{ $lightgbmStatus['message'] }}</p>
                        @if($lightgbmStatus['state'] === 'unreachable')
                            <p>Jalankan perintah berikut dari folder proyek dan biarkan terminal tetap terbuka:</p>
                            <code class="block overflow-x-auto rounded bg-white p-2">.\.venv-lightgbm\Scripts\python.exe services\lightgbm\serve.py</code>
                        @endif
                        <p>Ramalan, ROP, dan jumlah yang perlu disiapkan belum dapat dihitung sampai layanan siap.</p>
                    </div>
                @endif
                <p class="rounded border border-sky-200 bg-sky-50 p-4 text-sm text-sky-900">Model LightGBM ini dilatih dengan transaksi sintetis. Model lead time tersedia untuk 3, 7, 14, dan 21 hari; proyeksi memakai model tersendiri untuk 30, 60, atau 90 hari. Jika model atau data belum mendukung, ramalan terkait ditandai belum tersedia beserta alasannya.</p>
            @endif

            @if($summary['belum_lengkap'])<p class="rounded border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">{{ $summary['belum_lengkap'] }} kelompok belum memiliki pencatatan lengkap pada tanggal potong ini. Ramalan dan ROP ditunda; pilih tanggal dengan pencatatan lengkap atau konfirmasikan pencatatannya terlebih dahulu.</p>@endif
            @if($summary['ramalan_belum_tersedia'] && ! ($lightgbmStatus && $lightgbmStatus['state'] !== 'ready'))<p class="rounded border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">{{ $summary['ramalan_belum_tersedia'] }} kelompok memiliki ramalan {{ $metode }} yang belum tersedia. Periksa keterangan pada tabel. ROP dan jumlah yang perlu disiapkan hanya dihitung jika ramalan lead time {{ $metode }} tersedia.</p>@endif
            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <div class="rounded-xl border border-emerald-100 bg-white p-5 shadow-sm"><p class="text-sm font-medium text-slate-500">Kelompok aman</p><p class="mt-2 text-3xl font-bold text-emerald-700">{{ $summary['aman'] }}</p></div>
                <div class="rounded-xl border border-amber-100 bg-white p-5 shadow-sm"><p class="text-sm font-medium text-slate-500">Perlu perhatian</p><p class="mt-2 text-3xl font-bold text-amber-700">{{ $summary['perlu_perhatian'] }}</p></div>
                <div class="rounded-xl border border-rose-100 bg-white p-5 shadow-sm"><p class="text-sm font-medium text-slate-500">Kritis</p><p class="mt-2 text-3xl font-bold text-rose-700">{{ $summary['kritis'] }}</p></div>
                <div class="rounded-xl border border-sky-100 bg-white p-5 shadow-sm">
                    <p class="text-sm font-medium text-slate-500">Tren penjualan tertinggi</p>
                    @if ($summary['tren_tertinggi'])
                        <p class="mt-2 truncate text-base font-bold text-slate-900">{{ $summary['tren_tertinggi']['kategori'] }} – {{ $summary['tren_tertinggi']['kadar'] }}</p>
                        <p class="mt-1 text-sm text-slate-600">{{ $summary['tren_tertinggi']['cabang'] }} · {{ ucfirst($summary['tren_tertinggi']['status_tren']) }}</p>
                    @else
                        <p class="mt-2 text-sm text-slate-500">Belum ada penjualan valid.</p>
                    @endif
                </div>
            </div>

            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                <div class="flex flex-wrap items-center justify-between gap-2 border-b border-gray-100 px-5 py-4">
                    <div>
                        <h3 class="font-semibold text-gray-900">Hasil analisis {{ $periode }} hari</h3>
                        <p class="mt-1 text-xs text-gray-500">Tanggal potong {{ $cutoff }}. Ramalan lead time memperkirakan penjualan selama menunggu barang datang; ramalan jendela untuk {{ $periode }} hari ke depan. Ramalan ditampilkan dalam unit bulat terdekat. ROP = pembulatan ke atas (ramalan lead time + safety stock).</p>
                        <p class="mt-1 text-xs text-gray-500">Tren memakai {{ $periode }} hari terakhir. {{ $metode === 'LightGBM' ? 'LightGBM memakai riwayat 90 hari, jadwal buka dan Idulfitri.' : ($metode === 'ARIMA' ? 'ARIMA memakai hasil batch dengan tanggal potong, horizon dan riwayat yang sesuai.' : $metode.' memakai riwayat sesuai jendela yang dipilih.') }}</p>
                    </div>
                    <span class="text-sm text-gray-500">{{ number_format($totalRows, 0, ',', '.') }} kelompok</span>
                </div>
                <div class="table-scroll inventory-analysis-scroll" role="region" aria-label="Tabel analisis persediaan" tabindex="0">
                    <table class="data-table inventory-analysis-table divide-y divide-gray-200 text-sm">
                        <colgroup>
                            <col class="inventory-col-group">
                            <col class="inventory-col-stock">
                            <col class="inventory-col-sales">
                            <col class="inventory-col-trend">
                            <col class="inventory-col-plan">
                            <col class="inventory-col-number">
                            <col class="inventory-col-number">
                            <col class="inventory-col-status">
                        </colgroup>
                        <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                            <tr>
                                <th scope="col">Kelompok barang</th>
                                <th scope="col" class="text-right">Stok</th>
                                <th scope="col">Penjualan</th>
                                <th scope="col">Perubahan &amp; tren</th>
                                <th scope="col">Perencanaan</th>
                                <th scope="col" class="text-right">ROP</th>
                                <th scope="col" class="text-right">Siapkan</th>
                                <th scope="col">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($rows as $row)
                                <tr class="hover:bg-amber-50/30">
                                    <td>
                                        <span class="inventory-primary">{{ $row['kategori'] }} · {{ $row['kadar'] }}</span>
                                        <span class="inventory-secondary">{{ $row['cabang'] }}</span>
                                    </td>
                                    <td class="text-right inventory-emphasis">{{ number_format($row['stok_tersedia'], 0, ',', '.') }}</td>
                                    <td>
                                        <dl class="inventory-metrics">
                                            <div><dt>Berjalan</dt><dd>{{ number_format($row['penjualan_periode_berjalan'], 0, ',', '.') }}</dd></div>
                                            <div><dt>Sebelumnya</dt><dd>{{ number_format($row['penjualan_periode_sebelumnya'], 0, ',', '.') }}</dd></div>
                                            @if(in_array($metode, ['SMA', 'WMA'], true))
                                                <div><dt>{{ $metode }}/hari</dt><dd>{{ $row['estimasi_harian_metode'] === null ? 'Data belum lengkap' : number_format($row['estimasi_harian_metode'], 0, ',', '.') }}</dd></div>
                                            @else
                                                <div><dt>Rata-rata/hari</dt><dd>{{ $row['rata_rata_penjualan_harian'] === null ? 'Data belum lengkap' : number_format($row['rata_rata_penjualan_harian'], 0, ',', '.') }}</dd></div>
                                            @endif
                                        </dl>
                                    </td>
                                    <td>
                                        <span class="inventory-primary tabular-nums">{{ $row['persentase_perubahan'] === null ? '—' : number_format($row['persentase_perubahan'], 1, ',', '.').'%' }}</span>
                                        <span class="inventory-badge {{ $trendBadges[$row['status_tren']] }}">{{ ucfirst($row['status_tren']) }}</span>
                                    </td>
                                    <td>
                                        <dl class="inventory-metrics">
                                            <div><dt>Ramalan lead time ({{ $row['lead_time_hari'] }} hari)</dt><dd>{{ $row['ramalan_unit'] === null ? 'Belum tersedia' : number_format($row['ramalan_unit'], 0, ',', '.').' unit' }}</dd></div>
                                            <div><dt>Metode</dt><dd title="{{ $row['versi_model'] ?? '' }}">{{ $metode }}</dd></div>
                                        </dl>
                                        <span class="inventory-secondary">Riwayat {{ $row['riwayat_mulai'] }} s.d. {{ $row['riwayat_akhir'] }}</span>
                                        @if($row['alasan_fallback'])<span class="inventory-secondary">Diminta {{ $row['metode_diminta'] }}: {{ $row['alasan_fallback'] }}</span>@endif
                                        <div class="mt-3">
                                            <dl class="inventory-metrics">
                                                <div><dt>Ramalan jendela ({{ $periode }} hari)</dt><dd>{{ $row['ramalan_unit_periode'] === null ? 'Belum tersedia' : number_format($row['ramalan_unit_periode'], 0, ',', '.').' unit' }}</dd></div>
                                                <div><dt>Metode</dt><dd title="{{ $row['versi_model_periode'] ?? '' }}">{{ $metode }}</dd></div>
                                            </dl>
                                            @if($row['riwayat_mulai_periode'] !== $row['riwayat_mulai'])<span class="inventory-secondary">Riwayat {{ $row['riwayat_mulai_periode'] }} s.d. {{ $row['riwayat_akhir'] }}</span>@endif
                                            @if($row['alasan_fallback_periode'])<span class="inventory-secondary">Diminta {{ $row['metode_diminta'] }}: {{ $row['alasan_fallback_periode'] }}</span>@endif
                                        </div>
                                        <div class="mt-3"><dl class="inventory-metrics"><div><dt>Safety stock</dt><dd>{{ $row['safety_stock'] }}</dd></div></dl></div>
                                    </td>
                                    <td class="text-right inventory-emphasis">{{ $row['rop'] ?? '—' }}</td>
                                    <td class="text-right inventory-emphasis">{{ $row['jumlah_perlu_disiapkan'] ?? '—' }}</td>
                                    <td><span class="inventory-badge {{ $stockBadges[$row['status_persediaan']] }}">{{ ucfirst(str_replace('_', ' ', $row['status_persediaan'])) }}</span></td>
                                </tr>
                            @empty
                                <tr><td colspan="8" class="px-4 py-10 text-center text-gray-500">Belum ada stok tersedia atau penjualan valid pada periode analisis ini.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-gray-100 px-5 py-4">{{ $rows->links() }}</div>
            </div>
        </div>
    </div>
</x-app-layout>
