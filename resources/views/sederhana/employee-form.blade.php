<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl">{{ $employee->exists ? 'Edit Karyawan' : 'Tambah Karyawan' }}</h2>
    </x-slot>
    <div class="max-w-3xl mx-auto p-6">
        <form method="POST"
            action="{{ $employee->exists ? route('employees.update', $employee) : route('employees.store') }}"
            class="space-y-5 rounded-xl bg-white p-6 shadow-sm">
            @csrf @if($employee->exists) @method('PUT') @endif
            <x-input-error :messages="$errors->all()" />
            <div><x-input-label for="nama" value="Nama karyawan" /><x-text-input id="nama" name="nama"
                    :value="old('nama', $employee->nama)" class="mt-1 w-full" required maxlength="255" /></div>
            <div><x-input-label for="is_active" value="Status" /><select id="is_active" name="is_active"
                    class="mt-1 w-full rounded-md border-gray-300">
                    <option value="1" @selected(old('is_active', $employee->is_active ?? true))>Aktif</option>
                    <option value="0" @selected(!old('is_active', $employee->is_active ?? true))>Nonaktif</option>
                </select>
                <p class="mt-2 text-sm text-gray-500">Karyawan nonaktif tidak dapat dipilih untuk nota baru. Nama pada
                    nota lama tetap tersimpan.</p>
            </div>
            <x-primary-button>Simpan</x-primary-button><a href="{{ route('employees.index') }}"
                class="action-button action-detail">Kembali</a>
        </form>
    </div>
</x-app-layout>