<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $customer->exists ? 'Edit Customer' : 'Tambah Customer' }}</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <form method="POST" action="{{ $customer->exists ? route('customers.update', $customer) : route('customers.store') }}" class="space-y-5 bg-white p-6 shadow-sm sm:rounded-lg">
                @csrf
                <x-input-error :messages="$errors->all()" />
                @if ($customer->exists)
                    @method('PUT')
                @endif

                <div>
                    <x-input-label for="nama" value="Nama" />
                    <x-text-input id="nama" name="nama" class="mt-1 block w-full" :value="old('nama', $customer->nama)" required />
                    <x-input-error :messages="$errors->get('nama')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="telepon" value="Telepon" />
                    <x-text-input id="telepon" name="telepon" class="mt-1 block w-full" :value="old('telepon', $customer->telepon)" />
                </div>
                <div>
                    <x-input-label for="alamat" value="Alamat" />
                    <textarea id="alamat" name="alamat" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">{{ old('alamat', $customer->alamat) }}</textarea>
                </div>
                @if (Auth::user()->isAdmin())
                    <div>
                        <x-input-label for="branch_id" value="Cabang" />
                        <select id="branch_id" name="branch_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                            @foreach ($branches as $branch)
                                <option value="{{ $branch->id }}" @selected((int) old('branch_id', $customer->branch_id) === $branch->id)>{{ $branch->nama_cabang }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
                <x-primary-button>Simpan</x-primary-button>
            </form>
        </div>
    </div>
</x-app-layout>
