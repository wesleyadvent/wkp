<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ $serviceType->exists ? 'Edit Jenis Service' : 'Tambah Jenis Service' }}</h2>
    </x-slot>
    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <form method="POST"
                action="{{ $serviceType->exists ? route('service-types.update', $serviceType) : route('service-types.store') }}"
                class="space-y-5 bg-white p-6 shadow-sm sm:rounded-lg">
                @csrf @if($serviceType->exists) @method('PUT') @endif
                <x-input-error :messages="$errors->all()" />
                <div><x-input-label for="nama_service" value="Nama Service" /><x-text-input id="nama_service"
                        name="nama_service" class="mt-1 block w-full" :value="old('nama_service', $serviceType->nama_service)" required /></div>
                <div><x-input-label for="harga_default" value="Harga Default" /><x-text-input id="harga_default"
                        name="harga_default" type="number" step="0.01" class="mt-1 block w-full"
                        :value="old('harga_default', $serviceType->harga_default)" required /></div>
                <div><x-input-label for="keterangan" value="Keterangan" /><textarea id="keterangan" name="keterangan"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">{{ old('keterangan', $serviceType->keterangan) }}</textarea>
                </div>
                <label class="flex items-center gap-2"><input type="checkbox" name="is_active" value="1"
                        @checked(old('is_active', $serviceType->is_active ?? true))><span>Aktif</span></label>
                <x-primary-button>Simpan</x-primary-button>
            </form>
        </div>
    </div>
</x-app-layout>
