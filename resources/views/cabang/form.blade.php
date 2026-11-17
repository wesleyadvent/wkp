<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800">
            {{ $branch->exists ? 'Edit Cabang' : 'Tambah Cabang' }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto space-y-6 sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="rounded border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                    {{ session('status') }}
                </div>
            @endif

            <form method="POST"
                action="{{ $branch->exists ? route('branches.update', $branch) : route('branches.store') }}"
                class="space-y-5 bg-white p-6 shadow-sm sm:rounded-lg">
                @csrf
                @if ($branch->exists)
                    @method('PUT')
                @endif

                <div>
                    <x-input-label for="kode_cabang" value="Kode Cabang" />
                    <x-text-input id="kode_cabang" name="kode_cabang" class="mt-1 block w-full"
                        :value="old('kode_cabang', $branch->kode_cabang)" required />
                    <x-input-error :messages="$errors->get('kode_cabang')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="nama_cabang" value="Nama Cabang" />
                    <x-text-input id="nama_cabang" name="nama_cabang" class="mt-1 block w-full"
                        :value="old('nama_cabang', $branch->nama_cabang)" required />
                    <x-input-error :messages="$errors->get('nama_cabang')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="telepon" value="Telepon" />
                    <x-text-input id="telepon" name="telepon" class="mt-1 block w-full"
                        :value="old('telepon', $branch->telepon)" />
                    <x-input-error :messages="$errors->get('telepon')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="alamat" value="Alamat" />
                    <textarea id="alamat" name="alamat"
                        class="mt-1 block w-full rounded-md border-gray-300">{{ old('alamat', $branch->alamat) }}</textarea>
                    <x-input-error :messages="$errors->get('alamat')" class="mt-2" />
                </div>

                <x-primary-button>Simpan Data Cabang</x-primary-button>
            </form>

            @if ($branch->exists)
                <section class="bg-white p-6 shadow-sm sm:rounded-lg">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <h3 class="text-lg font-medium text-gray-900">Akses Login Cabang</h3>
                            <p class="mt-1 text-sm text-gray-600">
                                Atur apakah seluruh pengguna pada cabang ini dapat masuk ke aplikasi.
                            </p>
                        </div>

                        <span @class([
                            'rounded-full px-3 py-1 text-xs font-semibold',
                            'bg-green-100 text-green-800' => $branch->is_login_enabled,
                            'bg-red-100 text-red-800' => ! $branch->is_login_enabled,
                        ])>
                            {{ $branch->is_login_enabled ? 'Login Aktif' : 'Login Dinonaktifkan' }}
                        </span>
                    </div>

                    @if (! $branch->is_login_enabled && $branch->login_disabled_reason)
                        <div class="mt-4 rounded-md bg-red-50 px-4 py-3 text-sm text-red-800">
                            <span class="font-semibold">Alasan penonaktifan:</span>
                            {{ $branch->login_disabled_reason }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('branches.toggle-login', $branch) }}" class="mt-5 space-y-4">
                        @csrf
                        @method('PATCH')

                        <div>
                            <x-input-label for="alasan" :value="$branch->is_login_enabled ? 'Alasan menonaktifkan login' : 'Alasan mengaktifkan kembali login'" />
                            <textarea id="alasan" name="alasan" rows="3"
                                class="mt-1 block w-full rounded-md border-gray-300"
                                placeholder="Tuliskan alasan perubahan akses login..." required>{{ old('alasan') }}</textarea>
                            <x-input-error :messages="$errors->get('alasan')" class="mt-2" />
                        </div>

                        @if ($branch->is_login_enabled)
                            <x-danger-button type="submit"
                                onclick="return confirm('Nonaktifkan login untuk seluruh pengguna cabang ini?')">
                                Nonaktifkan Login Cabang
                            </x-danger-button>
                        @else
                            <x-primary-button type="submit"
                                onclick="return confirm('Aktifkan kembali login cabang ini?')">
                                Aktifkan Login Cabang
                            </x-primary-button>
                        @endif
                    </form>
                </section>
            @endif
        </div>
    </div>
</x-app-layout>
