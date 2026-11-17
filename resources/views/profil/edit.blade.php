<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Profile') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if ($user->isBranchUser())
                <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                    <div class="max-w-xl space-y-5">
                        <div>
                            <h3 class="text-lg font-medium text-gray-900">Informasi Profil</h3>
                            <p class="mt-1 text-sm text-gray-600">
                                Profil dan password pengguna cabang hanya dapat diubah oleh admin.
                            </p>
                        </div>

                        <dl class="space-y-4">
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Nama</dt>
                                <dd class="mt-1 text-gray-900">{{ $user->name }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Email</dt>
                                <dd class="mt-1 text-gray-900">{{ $user->email }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Cabang</dt>
                                <dd class="mt-1 text-gray-900">{{ $user->branch?->nama_cabang ?? '-' }}</dd>
                            </div>
                        </dl>
                    </div>
                </div>
            @else
                <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                    <div class="max-w-xl">
                        @include('profil.partials.update-profile-information-form')
                    </div>
                </div>

                <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                    <div class="max-w-xl">
                        @include('profil.partials.update-password-form')
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
