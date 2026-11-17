<x-guest-layout :wide="true">
    <div class="min-h-screen bg-white lg:grid lg:grid-cols-2">
        <section class="flex min-h-screen flex-col px-6 py-8 sm:px-10 lg:px-14">
            <a href="/" class="inline-flex w-fit items-center gap-3">
                <span class="grid h-11 w-11 place-items-center rounded-lg bg-amber-500 text-lg font-bold text-white shadow-sm">WK</span>
                <span>
                    <span class="block text-sm font-bold uppercase tracking-wide text-slate-950">Wijaya Kusuma</span>
                    <span class="block text-xs font-medium text-slate-500">Admin Toko Emas</span>
                </span>
            </a>

            <div class="mx-auto flex w-full max-w-md flex-1 flex-col justify-center py-10">
                <div class="mb-8">
                    <p class="mb-2 text-sm font-semibold text-amber-600">Masuk sistem</p>
                    <h1 class="text-3xl font-bold text-slate-950">Login Admin</h1>
                    <p class="mt-2 text-sm leading-6 text-slate-500">Gunakan akun yang sudah terdaftar untuk mengelola transaksi toko dan cabang.</p>
                </div>

                <x-auth-session-status class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800" :status="session('status')" />

                <form method="POST" action="{{ route('login') }}" class="space-y-5">
                    @csrf

                    <div>
                        <x-input-label for="email" value="Email" class="mb-1.5 text-sm font-semibold text-slate-700" />
                        <x-text-input id="email" class="block h-12 w-full rounded-lg border-slate-300 px-4 text-sm shadow-sm focus:border-amber-500 focus:ring-amber-500" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" placeholder="admin@wijayakusuma.test" />
                        <x-input-error :messages="$errors->get('email')" class="mt-2" />
                    </div>

                    <div>
                        <div class="mb-1.5 flex items-center justify-between gap-3">
                            <x-input-label for="password" value="Password" class="text-sm font-semibold text-slate-700" />
                            @if (Route::has('password.request'))
                                <a class="text-sm font-semibold text-amber-700 hover:text-amber-800" href="{{ route('password.request') }}">Lupa password?</a>
                            @endif
                        </div>
                        <div x-data="{ showPassword: false }" class="relative">
                            <x-text-input id="password" class="block h-12 w-full rounded-lg border-slate-300 px-4 pr-12 text-sm shadow-sm focus:border-amber-500 focus:ring-amber-500" x-bind:type="showPassword ? 'text' : 'password'" name="password" required autocomplete="current-password" placeholder="Masukkan password" />
                            <button type="button" class="absolute inset-y-0 right-0 grid w-12 place-items-center text-slate-400 hover:text-slate-700" x-on:click="showPassword = ! showPassword" aria-label="Tampilkan password">
                                <svg x-show="!showPassword" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                                <svg x-cloak x-show="showPassword" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m3 3 18 18"/><path d="M10.6 10.6A3 3 0 0 0 12 15a3 3 0 0 0 2.4-1.2"/><path d="M9.9 4.2A10.5 10.5 0 0 1 12 4.03c6.5 0 10 7 10 7a18 18 0 0 1-3.2 4.2"/><path d="M6.6 6.7C3.6 8.7 2 12 2 12s3.5 7 10 7a10.8 10.8 0 0 0 5.4-1.5"/></svg>
                            </button>
                        </div>
                        <x-input-error :messages="$errors->get('password')" class="mt-2" />
                    </div>

                    <label for="remember_me" class="flex items-center gap-3 text-sm font-medium text-slate-600">
                        <input id="remember_me" type="checkbox" class="rounded border-slate-300 text-amber-600 shadow-sm focus:ring-amber-500" name="remember">
                        <span>Ingat saya</span>
                    </label>

                    <button type="submit" class="flex h-12 w-full items-center justify-center gap-2 rounded-lg bg-slate-950 px-4 text-sm font-bold text-white shadow-sm transition hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><path d="m10 17 5-5-5-5"/><path d="M15 12H3"/></svg>
                        Masuk
                    </button>
                </form>
            </div>
        </section>

        <section class="relative hidden overflow-hidden bg-slate-950 lg:block">
            <div class="absolute inset-0 bg-[linear-gradient(rgba(255,255,255,.06)_1px,transparent_1px),linear-gradient(90deg,rgba(255,255,255,.06)_1px,transparent_1px)] bg-[size:48px_48px]"></div>
            <div class="absolute inset-x-0 bottom-0 h-1/2 bg-gradient-to-t from-amber-500/20 to-transparent"></div>
            <div class="relative flex h-full flex-col justify-between p-14 text-white">
                <div class="flex items-center justify-between">
                    <span class="rounded-full border border-white/15 px-4 py-2 text-xs font-semibold uppercase tracking-wider text-amber-200">Dashboard Operasional</span>
                    <span class="text-sm font-semibold text-white/60">{{ now()->format('Y') }}</span>
                </div>

                <div class="max-w-lg">
                    <div class="mb-8 grid grid-cols-3 gap-3">
                        <div class="rounded-lg border border-white/10 bg-white/10 p-4 backdrop-blur">
                            <div class="text-2xl font-bold text-amber-300">24K</div>
                            <div class="mt-1 text-xs text-white/60">Kadar Emas</div>
                        </div>
                        <div class="rounded-lg border border-white/10 bg-white/10 p-4 backdrop-blur">
                            <div class="text-2xl font-bold text-amber-300">PJL</div>
                            <div class="mt-1 text-xs text-white/60">Penjualan</div>
                        </div>
                        <div class="rounded-lg border border-white/10 bg-white/10 p-4 backdrop-blur">
                            <div class="text-2xl font-bold text-amber-300">SRV</div>
                            <div class="mt-1 text-xs text-white/60">Service</div>
                        </div>
                    </div>
                    <h2 class="text-4xl font-bold leading-tight">Kontrol transaksi toko dan cabang dalam satu panel.</h2>
                    <p class="mt-4 text-base leading-7 text-slate-300">Pantau stok, nota penjualan, pembelian, service, customer, dan laporan cabang dengan akses admin yang terpusat.</p>
                </div>

                <div class="grid grid-cols-2 gap-4 text-sm text-slate-300">
                    <div class="rounded-lg border border-white/10 bg-white/10 p-4 backdrop-blur">
                        <div class="font-semibold text-white">Multi cabang</div>
                        <div class="mt-1 text-white/60">Filter data per cabang untuk admin.</div>
                    </div>
                    <div class="rounded-lg border border-white/10 bg-white/10 p-4 backdrop-blur">
                        <div class="font-semibold text-white">Audit transaksi</div>
                        <div class="mt-1 text-white/60">Riwayat aktivitas tetap tercatat.</div>
                    </div>
                </div>
            </div>
        </section>
    </div>
</x-guest-layout>
