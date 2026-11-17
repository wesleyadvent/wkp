<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ $productCategory->exists ? 'Edit Kategori Barang' : 'Tambah Kategori Barang' }}</h2>
    </x-slot>
    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <form method="POST"
                action="{{ $productCategory->exists ? route('product-categories.update', $productCategory) : route('product-categories.store') }}"
                class="space-y-5 bg-white p-6 shadow-sm sm:rounded-lg">
                @csrf @if($productCategory->exists) @method('PUT') @endif
                <x-input-error :messages="$errors->all()" />
                <div><x-input-label for="nama_kategori" value="Nama Kategori" /><x-text-input id="nama_kategori"
                        name="nama_kategori" class="mt-1 block w-full" :value="old('nama_kategori', $productCategory->nama_kategori)" required /></div>
                <div><x-input-label for="keterangan" value="Keterangan" /><textarea id="keterangan" name="keterangan"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">{{ old('keterangan', $productCategory->keterangan) }}</textarea>
                </div>
                <label class="flex items-center gap-2"><input type="checkbox" name="is_active" value="1"
                        @checked(old('is_active', $productCategory->is_active ?? true))><span>Aktif</span></label>
                <x-primary-button>Simpan</x-primary-button>
            </form>
        </div>
    </div>
</x-app-layout>
