<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ $goldPrice->exists ? 'Edit Harga Emas' : 'Tambah Harga Emas' }}</h2>
    </x-slot>
    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <form method="POST"
                action="{{ $goldPrice->exists ? route('gold-prices.update', $goldPrice) : route('gold-prices.store') }}"
                class="space-y-5 bg-white p-6 shadow-sm sm:rounded-lg">
                @csrf @if($goldPrice->exists) @method('PUT') @endif
                <x-input-error :messages="$errors->all()" />
                <div><x-input-label for="tanggal" value="Tanggal" /><x-text-input id="tanggal" name="tanggal"
                        type="date" class="mt-1 block w-full" :value="old('tanggal', now('Asia/Jakarta')->format('Y-m-d'))" :min="now('Asia/Jakarta')->format('Y-m-d')" :max="now('Asia/Jakarta')->format('Y-m-d')" required /><p class="mt-1 text-sm text-gray-600">Harga baru berlaku mulai hari ini agar riwayat penelitian tetap sesuai waktu pencatatan.</p></div>
                <div><x-input-label for="kadar" value="Kadar" /><x-text-input id="kadar" name="kadar"
                        class="mt-1 block w-full" :value="old('kadar', $goldPrice->kadar)" :readonly="$goldPrice->exists" required /></div>
                @if($goldPrice->exists)<p class="text-sm text-gray-600">Harga sebelumnya tetap tersimpan dalam riwayat. Perubahan memperbarui harga barang yang tersedia.</p>@endif
                <div><x-input-label for="harga_per_gram" value="Harga per Gram" /><x-text-input id="harga_per_gram"
                        name="harga_per_gram" type="number" step="0.01" class="mt-1 block w-full"
                        :value="old('harga_per_gram', $goldPrice->harga_per_gram)" required /></div>
                <div><x-input-label for="keterangan" value="Keterangan" /><textarea id="keterangan" name="keterangan"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">{{ old('keterangan', $goldPrice->keterangan) }}</textarea>
                </div>
                <x-primary-button>Simpan</x-primary-button>
            </form>
        </div>
    </div>
</x-app-layout>
